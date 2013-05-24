<?php

function delArrayElement($array,$key) {
	unset($array[$key]);
	$array = array_values($array);
	return $array;
}//verwijder

//pakt de DUUR in database
function krijgDatum($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
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
	$resultaat = sqlSel("Spellen","SID=$sid");
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
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snelheid = $spel['SNELHEID'];
	$duur = vergelijkDatum($sid);
	return ($duur >= $snelheid);
}//genoegGewacht

//returned true als gegeven rol in het spel zit, anders false
function inSpel($rol,$sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='$rol'");
	return (sqlNum($resultaat) > 0);
}//inSpel

//checkt of een speler levend is
//ook nieuw_dode spelers vallen hieronder 
//(ze komen nog aan de beurt als ze net die nacht zijn vermoord...)
function isLevend($id) {
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	return ($speler['LEVEND']);
}//isLevend

function isNieuwDood($id) {
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	return ($speler['NIEUW_DOOD']);
}//isNieuwDood

//geeft de rol van een speler
function heeftRol($id) {
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	return $speler['ROL'];
}//heeftRol

//zet de fase van een spel op de gegeven waarde
function zetFase($waarde,$sid) {
	$datum = date_create(date('Y-m-d'));
	$sqlDatum = date_format($datum, 'Y-m-d');
	sqlUp("Spellen","FASE=$waarde,DUUR='$sqlDatum'","SID=$sid");
	return;
}//zetFase

//geeft de fase van het spel
function geefFase($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	return $spel['FASE'];
}//geefFase

//zet de stem van een speler op NULL
//waarbij stem STEM of EXTRA_STEM kan zijn
//(dit staat in $plek)
function verwijderStem($id,$plek) {
	sqlUp("Spelers","$plek=NULL","ID=$id");
	return;
}//verwijderStem

//hoogt het aantal gemiste stemmen van een speler met 1 op
function stemGemist($id) {
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$gemist = $speler['GEMIST'] + 1;
	sqlUp("Spelers","GEMIST=$gemist","ID=$id");
	return;
}//stemGemist

//zet het aantal gemiste stemmen van een speler op 0
function heeftGestemd($id) {
	sqlUp("Spelers","GEMIST=0","ID=$id");
	return;
}//heeftGestemd

//checkt of een speler wakker wordt: 
//staat hij in de stem van Klaas Vaak of niet?
function wordtWakker($id,$sid) {
	if(!inSpel("Klaas Vaak",$sid)) {
		return true;
	}
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Klaas Vaak'");
	while($speler = sqlFet($resultaat)) {
		if($speler['STEM'] == $id) {
			return false;
		}
	}//while
	return true;
}//wordtWakker

//controleert of een speler beschermt is door de Genezer
function beschermd($id,$sid) {
	if(!inSpel("Genezer",$sid)) {
		return false;
	}
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Genezer' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$stem = $speler['STEM'];
		if($stem == $id) {
			return true;
		}
	}//while
	return false;
}//beschermd

//dood de speler
function zetDood($id,$sid) {
	sqlUp("Spelers","LEVEND=1,NIEUW_DOOD=1","SPEL=$sid AND ID=$id");
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$levend = $spel['LEVEND'] - 1;
	$dood = $spel['DOOD'] + 1;
	sqlUp("Spellen","LEVEND=$levend,DOOD=$dood","SID=$sid");
	return;
}//zetDood

//wekt de speler weer tot leven
function herleef($id,$sid) {
	sqlUp("Spelers","LEVEND=1,NIEUW_DOOD=0","SPEL=$sid AND ID=$id");
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$levend = $spel['LEVEND'] + 1;
	$dood = $spel['DOOD'] - 1;
	sqlUp("Spellen","LEVEND=$levend,DOOD=$dood","SID=$sid");
	return;
}//herleef

//check of de speler niet beschermd wordt, danwel aanwezig is
//en vermoord hem (en eventueel andere slachtoffers...)
function vermoord($id,$sid) {
	$rol = heeftRol($id);
	$genezerInSpel = inSpel("Genezer",$sid);
	$dorpsoudsteInSpel = inSpel("Dorpsoudste",$sid);
	$targets = array($id); // Voeg alle targets toe aan deze array!

	//check Goochelaar...
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Goochelaar'");
	if(sqlNum($resultaat) > 0) {
		while($goochelaar = sqlFet($resultaat)) {
			$stem = $goochelaar['STEM'];
			$stem2 = $goochelaar['EXTRA_STEM'];
			if($stem == $id) {
				echo "De Goochelaar heeft $id met $stem2 verwisseld.\n";
				array_push($targets,$stem2);
				$targets = delArrayElement($targets,0); //verwissel
			}
			else if($stem2 == $id) {
				echo "De Goochelaar heeft $id met $stem verwisseld.\n";
				array_push($targets,$stem);
				$targets = delArrayElement($targets,0); //verwissel
			}
		}//while
	}//if
	
	//check Slet: als de Slet bij het echte target slaapt
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Slet'");
	if(sqlNum($resultaat) > 0) {
		$sletInSpel = true;
		while($slet = sqlFet($resultaat)) {
			if($slet['STEM'] == $id) { //de slet slaapt bij het echte target
				echo $slet['ID'] . " slaapt bij $id.\n";
				array_push($targets,$slet['ID']);
			}
		}//while
	}//if

	//check Verleidster: als het echte target de Verleidster is...
	if($rol == "Verleidster") {
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
		$speler = sqlFet($resultaat);
		$stem = $speler['STEM'];
		if($stem != "" && $stem != 0 && $stem != $id) {
			echo "$id heeft $stem verleidt.\n";
			array_push($targets,$stem);
		}
	}//if

	//nu alle targets verzameld zijn: kijk welke beschermd zijn, 
	//of misschien niet aanwezig...
	foreach($targets as $key => $target) {
		if($genezerInSpel && beschermd($target,$sid)) {
			echo "$target is beschermd.\n";
			$targets = delArrayElement($targets,$key);
			continue; //vermoord hem dan niet
		}
		if($dorpsoudsteInSpel && heeftRol($target) == "Dorpsoudste") {
			$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$target");
			$speler = sqlFet($resultaat);
			if(($speler['SPELFLAGS'] & 64) == 64) {
				echo "Dorpsoudste $target overleeft de aanval.\n";
				sqlUp("Spelers","SPELFLAGS=SPELFLAGS-64",
					"SPEL=$sid AND ID=$id");
				$targets = delArrayElement($targets,$key);
				continue;
			}
		}//if
		else if($sletInSpel && heeftRol($target) == "Slet") {
			$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
			$speler = sqlFet($resultaat);
			$stem = $speler['STEM'];
			if($stem != "" && $stem != 0 && $stem != $id) {
				echo "$target slaapt niet hier, maar bij $stem";
				$targets = delArrayElement($targets,$key);
				continue; //vermoord hem dan niet
			}
		}//else if
		if(inSpel("Verleidster",$sid)) {
			$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Verleidster'");
			while($speler = sqlFet($resultaat)) {
				if($speler['STEM'] == $target && $speler['ID'] != $id) {
					echo "$target is verleid door " . $speler['ID'] . ".\n";
					$targets = delArrayElement($targets,$key);
					continue(2); //vermoord hem dan niet
				}
			}//while
		}//if
		
		//vermoord...
		echo "$target wordt vermoord...\n";
		zetDood($target,$sid);
	}//foreach
	return;
}//vermoord

//checkt of een speler door de Waarschuwer(s) gewaarschuwd is
function isGewaarschuwd($id,$sid) {
	if(!inSpel("Waarschuwer",$sid)) {
		return false;
	}
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Waarschuwer'");
	while($speler = sqlFet($resultaat)) {
		if($speler['EXTRA_STEM'] == $id) {
			return true;
		}
	}//while
	return false;
}//isGewaarschuwd

//checkt of een speler door de Schout(en) is opgesloten
function isOpgesloten($id,$sid) {
	if(!inSpel("Schout",$sid)) {
		return false;
	}
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Schout'");
	while($speler = sqlFet($resultaat)) {
		if($speler['EXTRA_STEM'] == $id) {
			return true;
		}
	}//while
	return false;
}//isOpgesloten

//checkt of een speler door de Raaf (of Raven) is beschuldigd
function isBeschuldigd($id,$sid) {
	if(!inSpel("Raaf",$sid)) {
		return false;
	}
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Raaf'");
	while($speler = sqlFet($resultaat)) {
		if($speler['EXTRA_STEM'] == $id) {
			return true;
		}
	}//while
	return false;
}//isBeschuldigd

//zet de stemmen van Klaas Vaak, Genezer, Slet, 
//Verleidster en Goochelaar op NULL, en onthoudt de oude stemmen
function regelZetNULL1($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND (ROL='Klaas Vaak' OR ROL='Genezer' OR 
		ROL='Slet' OR ROL='Verleidster' OR ROL='Goochelaar')");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if(empty($stem)){
			continue;
		}
		if($speler['ROL'] == "Goochelaar") {
			sqlUp("Spelers",
				"STEM=NULL,EXTRA_STEM=NULL,VORIGE_STEM='$stem',
				VORIGE_STEM_EXTRA='$stem2'",
				"SPEL=$sid AND ID=$id");
			continue;
		}
		sqlUp("Spelers","STEM=NULL,VORIGE_STEM='$stem'",
			"SPEL=$sid AND ID=$id");
	}
	return;
}//regelZetNULL1

//zet de stemmen van de Raaf, Schout en Waarschuwer
//op NULL en onthoudt de stem van de Schout
function regelZetNULL2($sid) {
	sqlUp("Spelers","EXTRA_STEM=NULL","SPEL=$sid AND (ROL='Raaf' OR 
		ROL='Waarschuwer')");
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Schout'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['EXTRA_STEM'];
		if(empty($stem)) {
			continue;
		}
		sqlUp("Speler","EXTRA_STEM=NULL,VORIGE_STEM='$stem'",
			"SPEL=$sid AND ID=$id");
	}
	return;
}//regelZetNULL2

//berekent de zwaarte van de stem van een speler 
//(+1 als Burgemeester, +1 als Gewaarschuwd)
//ontdekte Dorpsgek en spelers opgesloten door de Schout 
//of aangewezen door de Zondebok moeten door de parser worden afgevangen.
function stemWaarde($id,$sid) {
	$waarde = 1;
	if(isGewaarschuwd($id,$sid)) {
		$waarde++;
	}
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ID IN 
		(SELECT BURGEMEESTER FROM Spellen WHERE SID=$sid)");
	$burgemeester = sqlFet($resultaat);
	if($id == $burgemeester['ID']) {
		$waarde++;
	}
	return $waarde;
}//stemWaarde

//voor een gegeven array met stemmen, returned de keys van de hoogste stem(men).
function hoogsteStem($stemmen) {
	$hoogsteStem = max($stemmen);
	$keys = array_keys($stemmen,$hoogsteStem);
	return $keys;
}//hoogsteStem

//checkt of de Fluitspelers een spel gewonnen hebben:
//als alle andere levende spelers betoverd zijn
function gewonnenFS($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND LEVEND=1 AND ROL<>'Fluitspeler'");
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
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1");
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
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1");
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
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1");
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
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1");
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
function gewonnenSpeler($id,$rol,$geliefde,$lijfwacht,$sid,$flag) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND LEVEND=1 AND LIJFWACHT=$id");
	if(sqlNum($resultaat) == 1) { //als je een lijfwacht bent...
		$opdracht = sqlFet($resultaat);
		if(!gewonnenSpeler($opdracht['ID'],$opdracht['ROL'],
			$opdracht['GELIEFDE'],$id,$sid,false)) {
			return false;
		}
	}
	if(!empty($geliefde) && !$flag) { //als je geliefde bent...
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$geliefde");
		$speler = sqlFet($resultaat);
		if(!gewonnenSpeler($speler['ID'],$speler['ROL'],$id,
			$speler['LIJFWACHT'],$sid,true)) {
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
function gewonnen($sid) {
	$gewonnenSpelers = array();
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1");
	if(sqlNum($resultaat)) {
		echo "Alle spelers dood; gelijkspel...\n";
		//TODO maak verhaaltje
		return;
	}
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$rol = $speler['ROL'];
		$geliefde = $speler['GELIEFDE'];
		$lijfwacht = $speler['LIJFWACHT'];
		if(gewonnenSpeler($id,$rol,$geliefde,$lijfwacht,$sid,false)) {
			echo "$id heeft gewonnen!\n";
			array_push($gewonnenSpelers,$naam);
		}
	}
	if(empty($gewonnenSpelers)) { //niemand gewonnen
		echo "Niemand heeft nog gewonnen.\n";
		return false;
	}
	//TODO mail enzo
	return true;
}//gewonnen

?>
