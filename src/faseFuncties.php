<?php

function schrijfLog($sid,$bericht) {
	global $tabellen;
	echo $bericht;
	$datum = new DateTime();
	$log = date_format($datum,'Y-m-d H:i:s');
	$log .= " - ";
	$log .= sqlEscape($bericht);
	sqlUp(4,"LOG=CONCAT(LOG,'$log')","SID=$sid");
	return;
}//schrijfLog

function delArrayElement($array,$key) {
	unset($array[$key]);
	$array = array_values($array);
	return $array;
}//verwijder

//pakt de DUUR in database
function krijgDatum($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$datum = date_create($spel['DUUR']);
	return $datum;
}//krijgDatum

//geeft het verschil tussen de huidige datum, en die van DUUR (in database)
function vergelijkDatum($sid) {
	$duur = krijgDatum($sid);
	$datum = date_create(date('Y-m-d'));
	$verschil = date_diff($duur,$datum);
	return $verschil->format('%a'); // %a is het integer-deel
}//vergelijkDatum

//geeft voor getallen 1-7 de bijbehordende weekdag "maandag"-"zondag"
//dit doet hij ZONDER HOOFDLETTERS
function geefWeekdag($getal) {
	$getal = $getal % 7; //in case "zondag" = 0
	switch($getal) {
		case 0:
			return "zondag";
		case 1:
			return "maandag";
		case 2:
			return "dinsdag";
		case 3:
			return "woensdag";
		case 4:
			return "donderdag";
		case 5:
			return "vrijdag";
		case 6:
			return "zaterdag";
		default: //dit hoort niet te gebeuren
			return false;
	}
}//geefWeekdag

//geeft een string met de eerstvolgende deadline
//(systeemtijd + snelheid)
function geefDeadline($sid) {
	$datum = date_create(date('Y-m-d'));
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snelheid = $spel['SNELHEID'];
	$duur = date_interval_create_from_date_string("$snelheid days");
	$deadline = date_add($datum,$duur);
	$weekdag = geefWeekdag(date_format($deadline,'N'));
	$dagnummer = date_format($deadline,'j');
	return "$weekdag de $dagnummer<sup>e</sup>";
}//geefDeadline

//vergelijkt de faseduur met huidige datum
//true als het tijd is om naar de volgende fase te gaan
function genoegGewacht($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snelheid = $spel['SNELHEID'];
	$duur = vergelijkDatum($sid);
	return ($duur >= $snelheid);
}//genoegGewacht

//returned true als gegeven rol in het spel zit, anders false
function inSpel($rol,$sid) {
	$resultaat = sqlSel(3,"SID=$sid AND ROL='$rol'");
	return (sqlNum($resultaat) > 0);
}//inSpel

//checkt of een speler levend is
//ook nieuw_dode spelers vallen hieronder 
//(ze komen nog aan de beurt als ze net die nacht zijn vermoord...)
function isLevend($id) {
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	return (($speler['LEVEND'] & 1) == 1);
}//isLevend

//geeft de rol van een speler
function heeftRol($id) {
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	return $speler['ROL'];
}//heeftRol

//zet de fase van een spel op de gegeven waarde
function zetFase($waarde,$sid) {
	$sqlDatum = date('Y-m-d');
	sqlUp(4,"FASE=$waarde,DUUR='$sqlDatum'","SID=$sid");
	schrijfLog($sid,"Fase op $waarde gezet.\n");
	return;
}//zetFase

//geeft de fase van het spel
function geefFase($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	return $spel['FASE'];
}//geefFase

//zet de stem van een speler op NULL
//waarbij stem STEM of EXTRA_STEM kan zijn
//(dit staat in $plek)
function verwijderStem($id,$plek) {
	sqlUp(3,"$plek=NULL","ID=$id");
	return;
}//verwijderStem

//hoogt het aantal gemiste stemmen van een speler met 1 op
function stemGemist($id) {
	sqlUp(3,"GEMIST=GEMIST+1","ID=$id");
	return;
}//stemGemist

//zet het aantal gemiste stemmen van een speler op 0
function heeftGestemd($id) {
	sqlUp(3,"GEMIST=0","ID=$id");
	return;
}//heeftGestemd

//checkt of een speler wakker wordt
function wordtWakker($id) {
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	return(($speler['SPELFLAGS'] & 32) == 0);
}//wordtWakker

//controleert of een speler beschermt is door de Genezer
function beschermd($id) {
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	return (($speler['SPELFLAGS'] & 64) == 64);
}//beschermd

//dood de speler
function zetDood($id,$sid) {
	sqlUp(3,"LEVEND=3","ID=$id");
	sqlUp(4,"LEVEND=LEVEND-1,DOOD=DOOD+1","SID=$sid");
	return;
}//zetDood

//wekt de speler weer tot leven
function herleef($id,$sid) {
	sqlUp(3,"LEVEND=1","ID=$id");
	sqlUp(4,"LEVEND=LEVEND+1,DOOD=DOOD-1","SID=$sid");
	return;
}//herleef

//neemt alle spelers die nieuwdood zijn, en maakt ze echt dood
//leegt ook de EXTRA_STEM van overleden Jagers
//(deze werd onthouden ivm. algemene mail)
function zetDood2($sid) {
	sqlUp(3,"LEVEND=0","SID=$sid AND ((LEVEND & 2) = 2)");
	sqlUp(3,"EXTRA_STEM=NULL","SID=$sid AND ROL='Jager' AND LEVEND=2");
	schrijfLog($sid,"Alle nieuw-dode spelers gedood.\n");
	return;
}//zetDood2

//check of de speler niet beschermd wordt, danwel aanwezig is
//en vermoord hem (en eventueel andere slachtoffers...)
function vermoord($id,$sid) {
	schrijfLog($sid,"Speler $id wordt vermoord.\n");
	$rol = heeftRol($id);
	$dorpsoudsteInSpel = inSpel("Dorpsoudste",$sid);
	$targets = array($id); // Voeg alle targets toe aan deze array!

	//check Goochelaar...
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Goochelaar'");
	if(sqlNum($resultaat) > 0) {
		while($goochelaar = sqlFet($resultaat)) {
			$stem = $goochelaar['STEM'];
			$stem2 = $goochelaar['EXTRA_STEM'];
			if($stem == $id) {
				schrijfLog($sid,"De Goochelaar heeft $id met $stem2 " . 
					"verwisseld.\n");
				array_push($targets,$stem2);
				$targets = delArrayElement($targets,0); //verwissel
			}
			else if($stem2 == $id) {
				schrijfLog($sid,"De Goochelaar heeft $id met $stem " . 
					"verwisseld.\n");
				array_push($targets,$stem);
				$targets = delArrayElement($targets,0); //verwissel
			}
		}//while
	}//if
	
	//check Slet: als de Slet bij het echte target slaapt
	$sletInSpel = false;
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Slet'");
	if(sqlNum($resultaat) > 0) {
		$sletInSpel = true;
		while($slet = sqlFet($resultaat)) {
			if($slet['STEM'] == $id) { //de slet slaapt bij het echte target
				schrijfLog($sid,$slet['ID'] . " slaapt bij $id.\n");
				array_push($targets,$slet['ID']);
			}
		}//while
	}//if

	//check Verleidster: als het echte target de Verleidster is...
	if($rol == "Verleidster") {
		$resultaat = sqlSel(3,"ID=$id");
		$speler = sqlFet($resultaat);
		$stem = $speler['STEM'];
		if($stem != "" && $stem != 0 && $stem != $id) {
			schrijfLog($sid,"$id heeft $stem verleidt.\n");
			array_push($targets,$stem);
		}
	}//if

	//nu alle targets verzameld zijn: kijk welke beschermd zijn, 
	//of misschien niet aanwezig...
	foreach($targets as $key => $target) {
		if(beschermd($target,$sid)) {
			schrijfLog($sid,"$target is beschermd.\n");
			$targets = delArrayElement($targets,$key);
			continue; //vermoord hem dan niet
		}
		if($dorpsoudsteInSpel && heeftRol($target) == "Dorpsoudste") {
			$resultaat = sqlSel(3,"ID=$target");
			$speler = sqlFet($resultaat);
			if(($speler['SPELFLAGS'] & 128) == 128) {
				schrijfLog($sid,"Dorpsoudste $target overleeft de aanval.\n");
				sqlUp(3,"SPELFLAGS=SPELFLAGS-128",
					"ID=$id");
				$targets = delArrayElement($targets,$key);
				continue;
			}
		}//if
		else if($sletInSpel && heeftRol($target) == "Slet") {
			$resultaat = sqlSel(3,"ID=$id");
			$speler = sqlFet($resultaat);
			$stem = $speler['STEM'];
			if($stem != "" && $stem != 0 && $stem != $id) {
				schrijfLog($sid,"$target slaapt niet hier, maar bij $stem");
				$targets = delArrayElement($targets,$key);
				continue; //vermoord hem dan niet
			}
		}//else if
		if(inSpel("Verleidster",$sid)) {
			$resultaat = sqlSel(3,"SID=$sid AND ROL='Verleidster'");
			while($speler = sqlFet($resultaat)) {
				if($speler['STEM'] == $target && $speler['ID'] != $id) {
					schrijfLog($sid,"$target is verleid door " . 
						$speler['ID'] . ".\n");
					$targets = delArrayElement($targets,$key);
					continue(2); //vermoord hem dan niet
				}
			}//while
		}//if
		
		//vermoord...
		schrijfLog($sid,"$target is dood.\n");
		zetDood($target,$sid);
	}//foreach
	return;
}//vermoord

//checkt of een speler door de Waarschuwer(s) gewaarschuwd is
function isGewaarschuwd($id,$sid) {
	$resultaat = sqlSel(3,"ID=$id AND ((SPELFLAGS & 16) = 16)");
	return sqlNum($resultaat);
}//isGewaarschuwd

//checkt of een speler door de Schout(en) is opgesloten
function isOpgesloten($id,$sid) {
	$resultaat = sqlSel(3,"ID=$id AND ((SPELFLAGS & 8) = 8)");
	return sqlNum($resultaat);
}//isOpgesloten

//checkt of een speler door de Raaf (of Raven) is beschuldigd
function isBeschuldigd($id,$sid) {
	$resultaat = sqlSel(3,"ID=$id AND ((SPELFLAGS & 4) = 4)");
	return sqlNum($resultaat);
}//isBeschuldigd

//zet de stemmen van Slet, 
//Verleidster en Goochelaar op NULL, en onthoudt de oude stemmen
function regelZetNULL1($sid) {
	$resultaat = sqlSel(3,
		"SID=$sid AND (ROL='Slet' OR ROL='Verleidster' OR ROL='Goochelaar')");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if(empty($stem)){
			continue;
		}
		if($speler['ROL'] == "Goochelaar") {
			sqlUp(3,
				"STEM=NULL,EXTRA_STEM=NULL,VORIGE_STEM='$stem',
				VORIGE_STEM_EXTRA='$stem2'",
				"ID=$id");
			continue;
		}
		sqlUp(3,"STEM=NULL,VORIGE_STEM='$stem'",
			"ID=$id");
	}

	//zet ook stemmen van slapende spelers op NULL
	sqlUp(3,"STEM=NULL,EXTRA_STEM=NULL","SID=$sid AND ((SPELFLAGS & 32) = 32)");
	return;
}//regelZetNULL1

//berekent de zwaarte van de stem van een speler 
//(+0.5 als Burgemeester, +1 als Gewaarschuwd)
//ontdekte Dorpsgek en spelers opgesloten door de Schout 
//of aangewezen door de Zondebok moeten door de parser worden afgevangen.
function stemWaarde($speler,$spel) {
	$waarde = 1;

	//check of speler gewaarschuwd is
	if(($speler['SPELFLAGS'] & 16) == 16) {
		$waarde++;
	}

	//check of speler burgemeester is
	if($speler['ID'] == $spel['BURGEMEESTER']) {
		$waarde += 0.5;
	}
	return $waarde;
}//stemWaarde

//voor een gegeven array met stemmen, returned de keys van de hoogste stem(men).
function hoogsteStem($stemmen) {
	$hoogsteStem = $stemmen[0];
	foreach($stemmen as $stem) {
		if($stem > $hoogsteStem) {
			$hoogsteStem = $stem;
		}
	}
	$keys = array_keys($stemmen,$hoogsteStem);
	return $keys;
}//hoogsteStem

//checkt of de Fluitspelers een spel gewonnen hebben:
//als alle andere levende spelers betoverd zijn
function gewonnenFS($sid) {
	$resultaat = sqlSel(3,
		"SID=$sid AND ((LEVEND & 1) = 1) AND ROL<>'Fluitspeler'");
	while($speler = sqlFet($resultaat)) {
		if(!($speler['SPELFLAGS'] & 1)) {
			return false;
		}
	}
	return true;
}//gewonnenFS

//checkt of de Weerwolven een spel gewonnen hebben:
//als de enige levende spelers Weerwolf of Welp zijn.
//uitzondering is een eventuele Geliefde, die wel mag leven.
function gewonnenWW($sid,$uitzondering,$uitzondering2) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] != "Weerwolf" && $speler['ROL'] != "Welp" && 
			$speler['ID'] != $uitzondering && $speler['ID'] != $uitzondering2) {
			return false;
		}
	}
	return true;
}//gewonnenWW

//checkt of de Vampiers een spel gewonnen hebben:
//als de enige levende spelers Vampier zijn.
function gewonnenVP($sid,$uitzondering,$uitzondering2) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] != "Vampier" && 
			$speler['ID'] != $uitzondering && $speler['ID'] != $uitzondering2) {
			return false;
		}
	}
	return true;
}//gewonnenVP

//checkt of een Psychopaat of Witte Weerwolf een spel gewonnen heeft:
//als dat de enige overgebleven speler is.
function gewonnenPsyWit($sid,$uitzondering,$uitzondering2) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	if(sqlNum($resultaat) > 2) {
		return false;
	}
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] != "Psychopaat" && 
			$speler['ROL'] != "Witte Weerwolf" && 
			$speler['ID'] != $uitzondering && $speler['ID'] != $uitzondering2) {
			return false;
		}
	}
	return true;
}//gewonnenPsyWit

//checkt of de Burgers een spel gewonnen hebben:
//als er geen levende Weerwolven, Welpen, Vampiers, Witte Weerwolven,
//Psychopaten of Fluitspelers zijn
function gewonnenB($sid,$uitzondering,$uitzondering2) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$rol = $speler['ROL'];
		if(($rol == "Weerwolf" || $rol == "Welp" ||
			$rol == "Vampier" || $rol == "Witte Weerwolf" ||
			$rol == "Psychopaat" || $rol == "Fluitspeler") &&
			$speler['ID'] != $uitzondering && $speler['ID'] != $uitzondering2) {
			return false;
		}
	}
	return true;
}//gewonnenB

//checkt of een speler gewonnen heeft:
//als lijfwacht -> opdrachtgever moet ook winnen
//als geliefde -> geliefde moet ook winnen
//en dan -> check of zijn rol wint (optimalisatie mogelijk...)
//flag is true als een geliefde wordt gecheckt
function gewonnenSpeler($sp,$sid,$flag) {
	$id = $sp['ID'];
	$rol = $sp['ROL'];
	$geliefde = $sp['GELIEFDE'];
	$lijfwacht = $sp['LIJFWACHT'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ((LEVEND & 1) = 1) AND LIJFWACHT=$id");
	if(sqlNum($resultaat) == 1) { //als je een lijfwacht bent...
		$opdracht = sqlFet($resultaat);
		if(!gewonnenSpeler($opdracht,$sid,false)) {
			return false;
		}
	}
	if(!empty($geliefde) && !$flag) { //als je geliefde bent...
		$resultaat = sqlSel(3,"ID=$geliefde");
		$speler = sqlFet($resultaat);
		if(!gewonnenSpeler($speler,$sid,true)) {
			return false;
		}
	}
	if($rol == "Fluitspeler" && gewonnenFS($sid)) {
		return true;
	}
	else if($rol == "Vampier" && gewonnenVP($sid,$geliefde,$lijfwacht)) {
		return true;
	}
	else if(($rol == "Weerwolf" || $rol == "Welp") && 
		gewonnenWW($sid,$geliefde,$lijfwacht)) {
		return true;
	}
	else if(($rol == "Psychopaat" || $rol == "Witte Weerwolf") && 
		gewonnenPsyWit($sid,$geliefde,$lijfwacht)) {
		return true;
	}
	else if(gewonnenB($sid,$geliefde,$lijfwacht)){ //alleen Burgers blijven over
		return true;
	}
}//gewonnenSpeler

//checkt of een spel gewonnen is door een team
function gewonnen($fase,$spel) {
	$sid = $spel['SID'];
	$gewonnenSpelers = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");

	//check op gelijkspel
	if(sqlNum($resultaat) == 0) {
		schrijfLog($sid,"Alle spelers dood; gelijkspel.\n");
		$resultaat = sqlSel(3,"SID=$sid");
		while($speler = sqlFet($resultaat)) {
			array_push($gewonnenSpelers,$speler);
		}
		mailGewonnen(-1,$gewonnenSpelers,$fase,$spel);
		return true;
	}

	//check of er 1 speler over is (deze is dan de winnaar)
	if(sqlNum($resultaat) == 1) {
		$speler = sqlFet($resultaat);
		$id = $speler['ID'];
		$naam = $speler['NAAM'];
		schrijfLog($sid,"Enkele speler over: $naam wint.\n");
		array_push($gewonnenSpelers,$speler);
		$rol = 0;
		switch($speler['ROL']) {
			case "Fluitspeler":
				$rol = 5;
				break;
			case "Weerwolf":
			case "Welp":
				$rol = 1;
				break;
			case "Vampier":
				$rol = 2;
				break;
			case "Psychopaat":
				$rol = 3;
				break;
			case "Witte Weerwolf":
				$rol = 4;
				break;
			default: //burger
				$rol = 0;
				break;
		}
		mailGewonnen($rol,$gewonnenSpelers,$fase,$spel);
		return true;
	}
	
	//check of een hele groep heeft gewonnen:
	//teams is boolean array (Burgers, WW, VP, Psycho, Witte, FS)
	$teams = array(true,true,true,true,true,true);
	while($speler = sqlFet($resultaat)) {
		array_push($gewonnenSpelers,$speler);
		switch($speler['ROL']) {
			case "Fluitspeler":
				$teams[0] = false;
				$teams[1] = false;
				$teams[2] = false;
				$teams[3] = false;
				$teams[4] = false;
				break;
			case "Weerwolf":
			case "Welp":
				$teams[0] = false;
				$teams[2] = false;
				$teams[3] = false;
				$teams[4] = false;
				break;
			case "Vampier":
				$teams[0] = false;
				$teams[1] = false;
				$teams[3] = false;
				$teams[4] = false;
				break;
			case "Psychopaat":
				$teams[0] = false;
				$teams[1] = false;
				$teams[2] = false;
				$teams[4] = false;
				break;
			case "Witte Weerwolf":
				$teams[0] = false;
				$teams[1] = false;
				$teams[2] = false;
				$teams[3] = false;
				break;
			default: //burger
				$teams[1] = false;
				$teams[2] = false;
				$teams[3] = false;
				$teams[4] = false;
				break;
		}//switch
		if($speler['ROL'] != "Fluitspeler" && 
			(($speler['SPELFLAGS'] & 1) != 1)) {
				$teams[5] = false;
		}
	}//while

	$key = array_search(true,$teams);
	if($key !== false) {
		schrijfLog($sid,"Team $key wint.\n");
		mailGewonnen($key,$gewonnenSpelers,$fase,$spel);
		return true;
	}
	$gewonnenSpelers = array();

	//check of geliefden/opdrachtgevers-lijfwachten hebben gewonnen
	$resultaat = sqlSel(3,"SID=$sid AND (GELIEFDE<>NULL OR LIJFWACHT<>NULL)");
	while($speler = sqlFet($resultaat)) {
		if(gewonnenSpeler($speler,$sid,false)) {
			$naam = $speler['NAAM'];
			schrijfLog($sid,"$naam heeft gewonnen.\n");
			array_push($gewonnenSpelers,$speler);
		}
	}
	if(empty($gewonnenSpelers)) { //niemand gewonnen
		schrijfLog($sid,"Niemand heeft nog gewonnen.\n");
		return false;
	}

	//check of gewonnenSpelers Geliefden of Lijfwachten, of beiden zijn
	if(count($gewonnenSpelers) == 2) {
		if($gewonnenSpelers[0]['GELIEFDE'] == $gewonnenSpelers[1]['ID']) {
			$rol = 6;
		}
		else {
			$rol = 7;
		}
	}//if
	else {
		$rol = 8;
	}

	mailGewonnen($rol,$gewonnenSpelers,$fase,$spel);
	return true;
}//gewonnen

?>
