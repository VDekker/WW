<?php

//geeft een willekeurig verhaal volgens de criteria met als extra: 
//het aantal levende en overleden (in het verhaal) spelers
//eventueel mogen minder levende spelers gebruikt worden
function geefVerhaal($thema,$rol,$fase,$numA,$numB,$ronde,$sid) {
	$i = 0;
	while($i == 0 || sqlNum($resultaat) == 0) {
		$sql = "";
		switch($i) {
			case 0:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A=$numA AND NUM_B=$numB AND VLAG=3";
				break;
			case 1:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A<=$numA AND NUM_B=$numB AND VLAG=2";
				break;
			case 2:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A=$numA AND NUM_B=$numB AND VLAG=3";
				break;
			case 3:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A<=$numA AND NUM_B=$numB AND VLAG=2";
				break;
			default:
				$res = sqlSel(5,"TID=$thema");
				$tuple = sqlFet($res);
				if($tuple['TNAAM'] == "default") {
					stuurError2("Geen default verhaal voor fase $fase " . 
						"van $rol, met $levend levende spelers " .
						"en $dood slachtoffers. ",$sid);
					return;
				}
				schrijfLog($sid,"Geen verhalen, probeer default...\n");
				$res = sqlSel(5,"TNAAM='default'");
				$tuple = sqlFet($res);
				$thema = $tuple['TID'];
				return geefVerhaal($thema,$rol,$fase,
					$levend,$dood,$ronde,$sid);
		}//switch
		$resultaat = sqlSel(6,$sql);
		$i++;
	}//while
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaal

//geeft een willekeurig verhaal volgens de criteria met als extra: 
//het aantal levende en overleden (in het verhaal) spelers
//eventueel mogen minder dode spelers gebruikt worden
//(gebruikt voor Zondebok en Onschuldige Meisje)
function geefVerhaal2($thema,$rol,$fase,$levend,$dood,$ronde,$sid) {
	$i = 0;
	while($i == 0 || sqlNum($resultaat) == 0) {
		$sql = "";
		switch($i) {
			case 0:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A=$numA AND NUM_B=$numB AND VLAG=3";
				break;
			case 1:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A=$numA AND NUM_B<=$numB AND VLAG=1";
				break;
			case 2:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A=$numA AND NUM_B=$numB AND VLAG=3";
				break;
			case 3:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A=$numA AND NUM_B<=$numB AND VLAG=1";
				break;
			default:
				$res = sqlSel(5,"TID=$thema");
				$tuple = sqlFet($res);
				if($tuple['TNAAM'] == "default") {
					stuurError2("Geen default verhaal voor fase $fase " . 
						"van $rol, met $levend levende spelers " .
						"en $dood slachtoffers. ",$sid);
					return;
				}
				schrijfLog($sid,"Geen verhalen, probeer default...\n");
				$res = sqlSel(5,"TNAAM='default'");
				$tuple = sqlFet($res);
				$thema = $tuple['TID'];
				return geefVerhaal2($thema,$rol,$fase,
					$levend,$dood,$ronde,$sid);
		}//switch
		$resultaat = sqlSel(6,$sql);
		$i++;
	}//while
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaal2

//geeft een willekeurig verhaal volgens de criteria met als extra: 
//het aantal levende en overleden (in het verhaal) spelers
//eventueel mogen minder levende en dode spelers gebruikt worden,
//maar de voorkeur ligt op genoeg levende spelers
//(gebruikt voor Gewonnen, Dorpsoudse en Zondebok)
function geefVerhaal3($thema,$rol,$fase,$levend,$dood,$ronde,$sid) {
	$i = 0;
	while($i == 0 || sqlNum($resultaat) == 0) {
		$sql = "";
		switch($i) {
			case 0:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A=$numA AND NUM_B=$numB AND VLAG=3";
				break;
			case 1:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A=$numA AND NUM_B<=$numB AND VLAG=1";
				break;
			case 2:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A<=$numA AND NUM_B=$numB AND VLAG=2";
				break;
			case 3:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE=$ronde AND NUM_A<=$numA AND NUM_B<=$numB AND VLAG=0";
				break;
			case 4:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A=$numA AND NUM_B=$numB AND VLAG=3";
				break;
			case 5:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A=$numA AND NUM_B<=$numB AND VLAG=1";
				break;
			case 6:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A<=$numA AND NUM_B=$numB AND VLAG=2";
				break;
			case 7:
				$sql = "THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
					RONDE IS NULL AND NUM_A<=$numA AND NUM_B<=$numB AND 
					VLAG=0";
				break;
			default: //check of thema default is
				$res = sqlSel(5,"TID=$thema");
				$tuple = sqlFet($res);
				if($tuple['TNAAM'] == "default") {
					stuurError2("Geen default verhaal voor fase $fase " . 
						"van $rol, met $levend levende spelers " .
						"en $dood slachtoffers. ",$sid);
					return;
				}
				schrijfLog($sid,"Geen verhalen, probeer default...\n");
				$res = sqlSel(5,"TNAAM='default'");
				$tuple = sqlFet($res);
				$thema = $tuple['TID'];
				return geefVerhaal3($thema,$rol,$fase,$levend,$dood,
					$ronde,$sid);
		}//switch
		$resultaat = sqlSel(6,$sql);
		$i++;
	}//while
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaal3

//geeft een verhaal voor de rolverdeling: eerst voor de specifieke rol
//als deze niet bestaat: voor algemene rolverdeling
//als deze niet bestaat: probeer hetzelfde met thema default
function geefVerhaalRolverdeling($thema,$rol,$sid) {
	$resultaat = sqlSel(6,
		"THEMA=$thema AND ROL='$rol' AND FASE=-1");
	if(sqlNum($resultaat) == 0) {
		schrijfLog($sid,"Geen intro voor specifieke rol, probeer algemeen...\n");
		$resultaat = sqlSel(6,
			"THEMA=$thema AND ROL='Rolverdeling' AND FASE=-1");
		if(sqlNum($resultaat) == 0) {
			$resultaat = sqlSel(5,"TID=$thema");
			$tuple = sqlFet($resultaat);
			if($tuple['TNAAM'] == "default") {
				stuurError2("Geen default verhaal rolverdeling. ",$sid);
				return;
			}
			schrijfLog($sid,"Geen verhalen, probeer default.\n");
			$resultaat = sqlSel(5,"TNAAM='default'");
			$tuple = sqlFet($resultaat);
			$thema = $tuple['TID'];
			$verhaal = geefVerhaalRolverdeling($thema,$rol,$sid);
			return $verhaal;
		}//if
	}//if
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaalRolverdeling

//aparte functie om de Dwaas een verkeerde rol te geven
//tupleA is de Dwaas en tupleB is zijn slachtoffer
function vulInDwaas($tupleA,$tupleB,$gezien,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	$geslachtA = $tupleA['SPELERFLAGS'] & 1;
	$naamA = $tupleA['NAAM'];
	$rolA = "Ziener";
	$geslachtB = $tupleB['SPELERFLAGS'] & 1;
	$naamB = $tupleB['NAAM'];
	$rolB = $gezien;
	
	if(!empty($geswoorden)) {
		foreach($paren as $key => $paar) {
			$alternatief = explode('&',$paar);
			$text = str_replace("geslachtA[$i][$key]",
				$alternatief[$geslachtA],$text);
		}
	}//if
	$text = str_replace("naamA[$i]",$naamA,$text);
	$text = str_replace("rolA[$i]",$rolA,$text);
	
	if(!empty($geswoorden)) {
		foreach($paren as $key => $paar) {
			$alternatief = explode('&',$paar);
			$text = str_replace("geslachtB[$i][$key]",
				$alternatief[$geslachtB],$text);
		}
	}//if
	$text = str_replace("naamB[$i]",$naamB,$text);
	$text = str_replace("rolB[$i]",$rolB,$text);

	return $text;
}//vulInDwaas

//aparte functie om de rol van de Witte WW te verbergen
function vulInWW($tuplesA,$tuplesB,$deadline,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($tuplesA as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Witte Weerwolf") {
			$rol = "Weerwolf";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslachtA[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}//if
		$text = str_replace("naamA[$i]",$naam,$text);
		$text = str_replace("rolA[$i]",$rol,$text);
	}//foreach
	
	foreach($tuplesB as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslachtB[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}//if
		$text = str_replace("naamB[$i]",$naam,$text);
		$text = str_replace("rolB[$i]",$rol,$text);
	}//foreach

	$text = str_replace("deadline[0]",$deadline,$text);
	return $text;
}//vulInWW

//vult een verhaaltje in met de juiste variabelen
function vulIn($tuplesA,$tuplesB,$deadline,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($tuplesA as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Dwaas") {
			$rol = "Ziener";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslachtA[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}
		$text = str_replace("naamA[$i]",$naam,$text);
		$text = str_replace("rolA[$i]",$rol,$text);
	}
	if(empty($tuplesB)) {
		$text = str_replace("deadline[0]",$deadline,$text);
		return $text;
	}
	foreach($tuplesB as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Dwaas") {
			$rol = "Ziener";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslachtB[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}
		$text = str_replace("naamB[$i]",$naam,$text);
		$text = str_replace("rolB[$i]",$rol,$text);
	}
	$text = str_replace("deadline[0]",$deadline,$text);
	return $text;
}//vulIn

//geeft de mogelijke keuzes van de Heks in een lijst weer
function keuzeHeks($text,$heks,$doden,$sid) {
	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 2) = 2)");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			array_push($keuzes,$speler['NAAM']);
		}//while
	}//if
	$text .= "<br />";
	$text .= "Voor het redden van een speler:<br />";
	$text = keuze($keuzes,false,$text);
	$text .= "<br />";

	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			if($speler['NAAM'] == $heks) { //niet zichzelf vergiftigen
				continue;
			}
			array_push($keuzes,$speler['NAAM']);
		}//while
	}//if
	$text .= "Voor het vergiftigen van een speler:<br />";
	$text = keuze($keuzes,false,$text);
	$text .= "Alleen als er helemaal niks wordt gekozen ";
	$text .= "moet 'blanco' worden gestemd; ";
	$text .= "anders de naam (of namen) van de gekozen speler(s).<br />";
	return $text;
}//keuzeHeks

//zet de mogelijke keuzes van de Jager in een lijst:
//alle niet-dode spelers
function keuzeJager($text,$jager,$sid) {
	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			if($speler['NAAM'] == $jager) { //niet op zichzelf schieten
				continue;
			}
			array_push($keuzes,$speler['NAAM']);
		}//while
	}//if
	$text = keuze($keuzes,true,$text);
	return $text;
}//keuzeJager

//de mogelijke keuze van de oude burgemeester:
//hij kan enkel levende (niet nieuw-dode) spelers kiezen
function keuzeTestament($text,$sid) {
	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			array_push($keuzes,$speler['NAAM']);
		}
	}//if
	$text = keuze($keuzes,true,$text);
	return $text;
}//keuzeTestament

//de keuze van de zondebok:
//hij kan alle niet-dode spelers kiezen, muv. zichzelf
function keuzeZonde($text,$zonde,$sid) {
	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			if($speler['NAAM'] == $zonde) { //niet zichzelf kiezen
				continue;
			}
			array_push($keuzes,$speler['NAAM']);
		}//while
	}//if
	$text = keuze($keuzes,true,$text);
	return $text;
}//keuzeZonde

function keuzeGroep($text,$rol,$sid) {
	$keuzes = array();
	$resultaat = "";
	switch($rol) {
		case "Weerwolf":
			//pak alle niet-dode spelers, muv. niet-slapende WW/Witte WW
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
				((ROL<>'Weerwolf' AND ROL<>'Witte Weerwolf') OR 
				((SPELFLAGS & 32) = 32))");
			break;
		case "Vampier":
			//pak alle niet-dode spelers, muv. niet-slapende VP
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
				(ROL<>'Vampier' OR ((SPELFLAGS & 32) = 32))");
			break;
		case "Fluitspeler":
			//pak alle niet-dode, niet-betoverde spelers, muv. niet-slapende FS
			$resultaat = sqlSel(3,
				"SID=$sid AND ((LEVEND & 1) = 1) AND ((SPELFLAGS & 1) = 0) AND 
				(ROL<>'Fluitspeler' OR ((SPELFLAGS & 32) = 32))");
			break;
		case "Brandstapel":
			//alle niet-dode, niet-opgesloten, niet-ontdekte spelers
			$resultaat = sqlSel(3,
				"SID=$sid AND ((LEVEND & 1) = 1) AND ((SPELFLAGS & 8) = 0) AND 
				(ROL<>'Dorpsgek' OR ((SPELFLAGS & 128) = 0))");
			break;
		default: //burgemeesterverkiezing
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
			break;
	}
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			array_push($keuzes,$speler['NAAM']);
		}
	}//if
	$text = keuze($keuzes,true,$text);
	return $text;
}//keuzeGroep

//aangeroepen door mailWakker; regelt de keuzes van veel verschillende rollen
function keuzeVeel($text,$speler,$rol,$sid) {
	$twee = false;
	$blanco = true;
	switch($rol) {
		case "Cupido":
			$resultaat = sqlSel(3,"SID=$sid");
			$text .= "<br />";
			$text .= "Je moet twee spelers kiezen.<br />";
			$twee = true;
			break;
		case "Grafrover":
			$resultaat = sqlSel(3,"SID=$sid AND LEVEND=0");
			break;
		case "Goochelaar":
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
			$id1 = $speler['VORIGE_STEM'];
			$res = sqlSel(3,"ID=$id");
			$stem = sqlFet($res);
			$naam1 = $stem['NAAM'];
			$id2 = $speler['VORIGE_STEM_EXTRA'];
			$res = sqlSel(3,"ID=$id");
			$stem = sqlFet($res);
			$naam2 = $stem['NAAM'];
			$text .= "<br />";
			$text .= "Je moet twee spelers kiezen, ";
			if($id1 == -1) {
				$text .= "je kunt niet 'blanco' stemmen, ";
				$blanco = false;
			}
			else {
				$text .= "maar dit koppel mag niet $naam1 en $naam2 zijn, ";
			}
			$text .= "om herhaling te voorkomen.<br />";
			$twee = true;
			break;
		case "Raaf":
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
				(ROL<>'Dorpsgek' OR ((SPELFLAGS & 128) = 0))");
			break;
		case "Klaas Vaak":
		case "Slet":
		case "Verleidster":
			$id = $speler['ID'];
			$stem = $speler['VORIGE_STEM'];
			if($stem == -1) {
				$blanco = false;
			}
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
				ID<>$id AND ID<>$stem");
			break;
		case "Genezer":
		case "Schout":
			$stem = $speler['VORIGE_STEM'];
			if($stem == -1) {
				$blanco = false;
			}
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
				ID<>$stem");
			break;
		default:
			//Dief, Dwaas, Opdrachtgever, Priester, Psychopaat, Witte WW, 
			//Waarschuwer en Ziener
			$id = $speler['ID'];
			$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
				ID<>$id");
			break;
	}//switch

	$keuzes = array();
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			array_push($keuzes,$speler['NAAM']);
		}
	}//if
	if($twee) {
		$text = keuzeTwee($keuzes,$blanco,$text);
	}
	else {
		$text = keuze($keuzes,$blanco,$text);
	}
	return $text;
}//keuzeVeel

//als een rol twee spelers moet kiezen ipv. een
function keuzeTwee($keuzes,$blanco,$text) {
	shuffle($keuzes);
	if(count($keuzes) < 2) {
		if($blanco) {
			$text .= "<br />";
			$text .= "Je kunt enkel 'blanco' kiezen.<br />";
			return $text;
		}
		else {
			$text .= "<br />";
			$text .= "Je hebt geen opties om uit te kiezen.<br />";
			return $text;
		}
	}

	if($blanco) {
		array_push($keuzes,"Blanco");
	}
	$text .= "<br />";
	$text .= "Je hebt de volgende opties:<br />";
	$text .= "<ul>";
	foreach($keuzes as $naam) {
		$text .= "<li>$naam</li>";
	}
	$text .= "</ul><br />";
	return $text;
}//keuze

function keuze($keuzes,$blanco,$text) {
	shuffle($keuzes);
	if($blanco) {
		array_push($keuzes,"Blanco");
	}
	switch (count($keuzes)) {
		case 0:
			$text .= "<br />";
			$text .= "Je hebt geen opties om uit te kiezen.<br />";
			return $text;
		case 1:
			$text .= "<br />";
			$text .= "Je kunt enkel deze keuze maken:<br />";
			break;
		default:
			$text .= "<br />";
			$text .= "Je kunt een van deze keuzes maken:<br />";
			break;
	}
	$text .= "<ul>";
	foreach($keuzes as $naam) {
		$text .= "<li>$naam</li>";
	}
	$text .= "</ul><br />";
	return $text;
}//keuze

//vult tuplesL,D,S en resArray met goede waarden,
//en geeft aan of een speciaal verhaal nodig is 
//(wanneer Jager/Geliefde dood is)
//ook: returned of een Dorpsoudste dood is (true of false)
function geefGebeurd(&$tuplesL,&$tuplesD,&$tuplesS,&$resArray,$spel) {
	$sid = $spel['SID'];
	$vlag = false;
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 2) = 2)");

	$doelwitten = array(); //id's van de doelwitten van de jagers
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] == "Jager" && ($speler['SPELFLAGS'] & 128) == 128) {
			$stem = $speler['EXTRA_STEM'];
			$res = sqlSel(3,"ID=$stem");
			$target = sqlFet($res);
			array_push($tuplesS,$target);
			array_push($doelwitten,$stem);
		}
		if($speler['GELIEFDE'] != "" && 
			($speler['SPELFLAGS'] & 512) == 0) {
				$speciaalVerhaal = true;
				$geliefde = $speler['GELIEFDE'];
				$res = sqlSel(3,"ID=$geliefde");
				schrijfLog($sid,"Geliefde gevonden: $geliefde.\n");
				$target = sqlFet($res);
				array_push($tuplesS,$target);
			}
		if($speler['ROL'] == "Dorpsoudste") {
			$vlag = true;
		}
		array_push($resArray,$speler);
	}//while

	foreach($resArray as $speler) {
		if(!in_array($speler,$tuplesS) && 
			!($speler['ROL'] == "Jager" && 
			($speler['SPELFLAGS'] & 128) == 128) && 
			!($speler['GELIEFDE'] != "" && 
			($speler['SPELFLAGS'] & 512) == 0)) {
			array_push($tuplesD,$speler);
		}
	}

	return $vlag;
}//geefGebeurd

function burgemeesterOpvolger(&$text,&$samenvatting,&$auteur,$tuplesL,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$thema = $spel['THEMA'];

	$vorigeBurgID = $spel['VORIGE_BURG'];
	$burgID = $spel['BURGEMEESTER'];
	if(empty($vorigeBurgID) || empty($burgID) || $burgID == -1) {
		return;
	}
	$resultaat = sqlSel(3,"ID=$vorigeBurgID AND ((LEVEND & 1) = 1)");
	if(sqlNum($resultaat) == 0) {
		return;
	}
	$vorigeBurg = sqlFet($resultaat);
	$resultaat = sqlSel(3,"ID=$burgID");
	$burg = sqlFet($resultaat);
	$alleBurg = array($burg,$vorigeBurg);

	$verhaal = geefVerhaal($thema,"Burgemeester",2,
		count($tuplesL),2,$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	shuffle($tuplesL);
	$text = vulIn($tuplesL,$alleBurg,"",$text,$geswoorden);

	$vorigeNaam = $vorigeBurg['NAAM'];
	$burgNaam = $burg['NAAM'];
	$samenvatting .= "$vorigeNaam heeft $burgNaam als opvolger benoemt: ";
	$samenvatting .= "$burgNaam is de nieuwe Burgemeester.<br />";

	return;
}//burgemeesterOpvolger

//maakt het ontwaak-deel van een algemene mail
function ontwaakVerhaal(&$text,&$samenvatting,&$auteur,$spel) {
	$tuplesL = array(); //L voor levende spelers
	$tuplesD = array(); //D voor dode spelers
	$tuplesS = array(); //S voor Jagers/Geliefden (speciaal verhaal)
	$resArray = array();
	$thema = $spel['THEMA'];
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];

	//vul de arrays en kijk of speciaal verhaal nodig is
	$vlag = geefGebeurd($tuplesL,$tuplesD,$tuplesS,$resArray,$spel);
	shuffle($tuplesL);
	shuffle($tuplesD);
	shuffle($tuplesS);
	$dood = count($tuplesD);
	$levend = count($tuplesL) + count($tuplesS);

	//bij normaal verhaal (geen jagers/geliefden dood)
	if(count($tuplesS) == 0) {
		schrijfLog($sid,"Normaal verhaal gewenst.\n");
		$verhaal = geefVerhaal($thema,"Algemeen",0,$levend,$dood,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		array_push($auteur,$verhaal['AUTEUR']);
		$text = vulIn($tuplesL,$tuplesD,"",$text,$geswoorden);

		//samenvatting maken
		foreach($tuplesD as $speler) {
			$naam = $speler['NAAM'];
			$rol = $speler['ROL'];
			$samenvatting .= "$naam ($rol) is dood.<br />";
		}

		//dorpsoudste dood... voeg extra verhaal achter!
		if($vlag) {
			schrijfLog($sid,"Dorpsoudste dood.\n");
			dodeDorpsoudste($spel,0,$tuplesL,$text,$samenvatting,$auteur);
		}//if

		burgemeesterOpvolger($text,$samenvatting,$auteur,$tuplesL,$spel);

		$samenvatting .= "Dag $ronde begint.<br />";
		return;
	}
	
	//maak de boom van jagers/geliefden
	$boom = array();
	$boom = maakBoom(-1,$tuplesS,$boom,0,$resArray);

	//ontwaken/begin
	$verhaal = geefVerhaal($thema,"Algemeen",1,$levend,$dood,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$erge = array_merge($tuplesL,$tuplesS);
	shuffle($merge);
	$text = vulIn($merge,$tuplesD,"",$text,$geswoorden);

	//tuplesS bijvullen (beginnende jagers/geliefden toevoegen)
	//en samenvatting maken
	foreach($boom as $id => $target) {
		$resultaat = sqlSel(3,"NAAM='$id' AND SID=$sid");
		$tuple = sqlFet($resultaat);
		array_push($tuplesS,$tuple);
		$naam = $tuple['NAAM'];
		$rol = $tuple['ROL'];
		$samenvatting .= "$naam ($rol) is dood.<br />";
	}

	//nu dingen aanvullen met behulp van de boom van jagers/geliefden
	foreach($boom as $id => $target) {
		leesBoom($boom[$id],$id,NULL,$text,$samenvatting,$auteur,
			$tuplesL,$tuplesS,$thema,"Algemeen",$spel);
	}

	if($vlag) {
		//dorpsoudste dood
		dodeDorpsoudste($spel,0,$tuplesL,$text,$samenvatting,$auteur);
	}

	burgemeesterOpvolger($text,$samenvatting,$auteur,$tuplesL,$spel);

	$samenvatting .= "Dag $ronde begint.<br />";
	return;
}//ontwaakVerhaal

//plakt de samenvatting achter een text
function plakSamenvatting($samenvatting,$text) {
	$text .= "<br /><br />-=-=-=-<br /><br />";
	$text .= "Samenvatting:<br />";
	$text .= "<br />";
	$text .= $samenvatting;
	return $text;
}//plakSamenvatting

function dodeDorpsoudste($spel,$fase,$tuplesL,&$text,&$samenvatting,&$auteur) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$thema = $spel['THEMA'];
	$oud = array();
	$res = sqlSel(3,"ROL='Dorpsoudste' AND ((LEVEND & 2) = 2)");
	while($oudste = sqlFet($res)) {
		array_push($oud,$oudste);
	}
	$verhaal = geefVerhaal($thema,"Dorpsoudste",$fase,count($tuplesL),
		count($oud),$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	shuffle($tuplesL);
	shuffle($oud);
	$text = vulIn($tuplesL,$oud,"",$text,$geswoorden);

	$samenvatting .= "De Dorpsoudste is dood ";
	$samenvatting .= "en iedereen verliest zijn rol.<br />";
	return;
}//dodeDorpsoudste

//maakt een 'boom' van de jagers en hun doelwitten, evenals de geliefden.
//Dit komt allemaal in een array terecht, met veel sub-arrays.
//De array-keys wijzen op de id van de speler (jager/geliefde), 
//behalve bij de bladeren: dan is het de array-value.
function maakBoom($id,$specialeTuples,$boom,$diepte,$resultaat) {
	if($diepte == 0) {
		foreach($resultaat as $speler) {
			$key = array_search($speler,$specialeTuples);
			if($key === false) {
				if($speler['ROL'] == "Jager" && 
					($speler['SPELFLAGS'] & 128) == 128) {
						$id = $speler['NAAM'];
						$boom[$id] = array();
						array_push($boom[$id],$speler['EXTRA_STEM']);
						$boom[$id] = maakBoom($id,$specialeTuples,$boom[$id],
							$diepte+1,$resultaat);
					}
				if($speler['GELIEFDE'] != "" &&
					($speler['SPELFLAGS'] & 512) == 0) {
						$id = $speler['NAAM'];
						$boom[$id] = array();
						array_push($boom[$id],$speler['GELIEFDE']);
						$boom[$id] = maakBoom($id,$specialeTuples,$boom[$id],
							$diepte+1,$resultaat);
					}
			}
		}
	}
	else {
		foreach($specialeTuples as $key => $speler) {
			$vlag = false;
			$key = array_search($speler['ID'],$boom);
			if($key !== false) { //speler zit in een blad van de boom...
				$vlag2 = false;
				$id = $speler['NAAM'];

				//als jager: maak een knoop van het blad
				if($speler['ROL'] == "Jager" &&
					($speler['SPELFLAGS'] & 128) == 128) {
						$vlag2 = true;
						if(!$vlag) {
							$boom[$id] = array();
							$vlag = true;
						}
						array_push($boom[$id],$speler['EXTRA_STEM']);
					}
				if($speler['GELIEFDE'] != "" &&
					($speler['SPELFLAGS'] & 512) == 0) {
						$vlag2 = true;
						if(!$vlag) {
							$boom[$id] = array();
							$vlag = true;
						}
						array_push($boom[$id],$speler['GELIEFDE']);
					}
				if($vlag2) {
					unset($boom[$key]);//verwijder het oude blad
					$boom[$id] = maakBoom($id,$specialeTuples,$boom[$id],
						$diepte+1,$resultaat);
				}
			}
		}
	}
	return $boom;
}//maakBoom

//zet een 'boom' om in verhaal
//hierbij kan $rol 'Algemeen', 'Jager', 'Geliefde' of 'Brandstapel' zijn
//$levende zijn de levende spelers, en $speciale zijn de spelers in de boom,
//elk van hen zijn tuple-arrays
//$parent is een speler-tuple van de speler die naar $id wijst
function leesBoom($boom,$id,$parent,&$text,&$samenvatting,&$auteur,
	$levende,&$speciale,$thema,$rol,$spel) {

		$sid = $spel['SID'];
		$ronde = $spel['RONDE'];

		//vind de id
		$index = array();
		if(!array_search_recursive($id,$speciale,$index)) {
			//hier hoor je niet te komen...
			$error = "Iets ging fout in leesBoom, ";
			$error .= "en de functie kwam op onontdekt terrein: ";
			$error .= "array_search_recursive() kon de naam $id";
			$error .= "die in de boom stond niet vinden ";
			$error .= "in de array met speciale tuples:<br />";
			$error .= "<table border = '1'><tr>";
			foreach($speciale[0] as $key => $value) {
				if(is_int($key)) {
					continue;
				}//if
				$error .= "<th>";
				$error .= htmlspecialchars($key);
				$error .= "</th>";
			}//foreach
			$error .= "</tr>";
			foreach($speciale as $tuple) {
				$error .= "<tr>";
				foreach($tuple as $key => $value) {
					if(is_int($key)) {
						continue;
					}//if
					$error .= "<th>";
					$error .= htmlspecialchars($value);
					$error .= "</th>";
				}//foreach
				$error .= "</tr>";
			}
			$error .= "</table>";
			stuurError2($error,$sid);
		}
		$tuple = $speciale[$index[0]];
		$naam = $tuple['NAAM'];
		$speciale = delArrayElement($speciale,$index[0]);
		$dood = array($tuple);
		if(!empty($parent)) {
			array_push($dood,$parent);
		}

		//kondig id aan
		$verhaal = geefVerhaal($thema,$rol,4,
			(count($levende)+count($speciale)),count($dood),$ronde,$sid);
		$text .= $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		array_push($auteur,$verhaal['AUTEUR']);
		$merge = array_merge($levende,$speciale);
		shuffle($merge);
		$text = vulIn($merge,$dood,"",$text,$geswoorden);

		//als id == jager: leesBoom op zijn target
		if($tuple['ROL'] == "Jager" &&
			($tuple['SPELFLAGS'] & 128) == 128) {
				$doelwit = $tuple['EXTRA_STEM'];
				$resultaat = sqlSel(3,"ID=$doelwit");
				$tuple2 = sqlFet($resultaat);
				$naam2 = $tuple2['NAAM'];
				$rol2 = $tuple2['ROL'];
				$samenvatting .= "Jager $naam schiet $naam2 ($rol2) neer.";
				$samenvatting .= "<br />";
				if(array_key_exists($naam2,$boom)) {
					//doelwit is een knoop: recursief
					leesBoom($boom[$naam2],$naam2,$tuple,$text,$samenvatting,
						$auteur,$levende,$speciale,$thema,"Jager",$spel);
				}
				else {
					//doelwit is een blad
					leesBlad($naam2,$tuple,$text,$samenvatting,$auteur,
						$levende,$speciale,$thema,"Jager",$spel);
				}
		}//if

		//maak id dood
		$verhaal = geefVerhaal($thema,$rol,5,
			(count($levende)+count($speciale)),count($dood),$ronde,$sid);
		$text .= $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		array_push($auteur,$verhaal['AUTEUR']);
		$merge = array_merge($levende,$speciale);
		shuffle($merge);
		$text = vulIn($merge,$dood,"",$text,$geswoorden);

		//als id == geliefde: kondig geliefde aan etc.
		if($tuple['GELIEFDE'] != "" &&
			($tuple['SPELFLAGS'] & 512) == 0) {
				$geliefde = $tuple['GELIEFDE'];
				$resultaat = sqlSel(3,"ID=$geliefde");
				$tuple2 = sqlFet($resultaat);
				$naam2 = $tuple2['NAAM'];
				$rol2 = $tuple2['ROL'];
				$samenvatting .= "Geliefde $naam2 ($rol2) kan niet leven ";
				$samenvatting .= "zonder $naam.<br />";
				if(array_key_exists($naam2,$boom)) {
					//doelwit is knoop: recursief
					leesBoom($boom[$naam2],$naam2,$tuple,$text,$samenvatting,
						$auteur,$levende,$speciale,$thema,"Cupido",$spel);
				}
				else {
					//doelwit is een blad
					leesBlad($naam2,$tuple,$text,$samenvatting,$auteur,
						$levende,$speciale,$thema,"Cupido",$spel);
				}
		}//if
}//leesBoom

//leest een blad van de boom (zie leesBoom()).
function leesBlad($id,$parent,&$text,&$samenvatting,&$auteur,
	$levende,&$speciale,$thema,$rol,$spel) {

	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];

	//vind de id
	$index = array();
	array_search_recursive($id,$speciale,$index);
	$tuple = $speciale[$index[0]];
	$dood = array($tuple);
	$speciale = delArrayElement($speciale,$index[0]);
	if(!empty($parent)) {
		array_push($dood,$parent);
	}

	//kondig aan
	$verhaal = geefVerhaal($thema,$rol,4,(count($levende)+count($speciale)),
		count($dood),$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$merge = array_merge($levende,$speciale);
	shuffle($merge);
	$text = vulIn($merge,$dood,"",$text,$geswoorden);

	//en vermoord
	$verhaal = geefVerhaal($thema,$rol,5,(count($levende)+count($speciale)),
		count($dood),$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$merge = array_merge($levende,$speciale);
	shuffle($merge);
	$text = vulIn($merge,$dood,"",$text,$geswoorden);

	return;
}//leesBlad

//vind een value in multidimensional arrays;
//indexes wordt gevuld met de keys die leiden naar de value
//van internet: 
//http://stackoverflow.com/questions/4232497/
//array-search-recursive-help-me-find-where-value-exists-in-multidimensional
function array_search_recursive($needle, $haystack, &$indexes=array()) {
	foreach ($haystack as $key => $value) {
		if (is_array($value)) {
			$indexes[] = $key;
			$status = array_search_recursive($needle, $value, $indexes);
			if ($status) {
				return true;
			} else {
				$indexes = array();
			}
		} else if ($value == $needle) {
			$indexes[] = $key;
			return true;
		}
	}
	return false;
}//array_search_recursive

function verkiezingInleiding(&$text,&$samenvatting,&$auteur,$spel) {
	$sid = $spel['SID'];
	$thema = $spel['THEMA'];
	$ronde = $spel['RONDE'];
	$burgID = $spel['VORIGE_BURG'];
	$vlag = false;
	$burgArray = array();
	if(empty($burgID)) {
		//geen vorige burgemeester
		$vlag = true;
		$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	}
	else {
		$resultaat = sqlSel(3,"ID=$burgID");
		$burgemeester = sqlFet($resultaat);
		$burgNaam = $burgemeester['NAAM'];
		array_push($burgArray,$burgemeester);
		$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1 AND ID<>$burgID");
	}
	$tuplesL = array(); //L voor levende spelers
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	shuffle($tuplesL);

	//maak verhaal (met burg apart van alle andere levende spelers)
	$verhaal = geefVerhaal($thema,"Burgemeester",3,count($tuplesL),
		count($burgArray),$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulIn($tuplesL,$burgArray,"",$text,$geswoorden);

	//samenvatting maken
	if(!$vlag) {
		$samenvatting .= "De vorige Burgemeester ($naam) is dood.<br />";
		$samenvatting .= "Een nieuwe Burgemeesterverkiezing begint.<br />";
	}
	else {
		$samenvatting .= "De Burgemeesterverkiezing begint.<br />";
	}

	//keuzes toevoegen
	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		array_push($keuzes,$speler['NAAM']);
	}
	array_push($keuzes,"blanco");

	$samenvatting .= "Er kan gestemd worden op:<br />";
	$samenvatting .= "<ul>";
	foreach($keuzes as $naam) {
		$samenvatting .= "<li>$naam</li>";
	}
	$samenvatting .= "</ul><br />";

	return;
}//verkiezingInleiding

function verkiezingUitslag(&$text,&$samenvatting,&$auteur,$overzicht,$spel) {
	$sid = $spel['SID'];
	$thema = $spel['THEMA'];
	$ronde = $spel['RONDE'];
	$burgID = $spel['BURGEMEESTER'];
	$vlag = false;
	if($burgID == -1) {
		//geen burg gekozen: doe iets anders
		$vlag = true;
		$burgArray = array();
		$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	}
	else {
		$resultaat = sqlSel(3,"ID=$burgID");
		$burgemeester = sqlFet($resultaat);
		$burgNaam = $burgemeester['NAAM'];
		$burgArray = array($burgemeester);
		$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1 AND ID<>$burgID");
	}
	$tuplesL = array(); //L voor levende spelers
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	shuffle($tuplesL);

	//maak verhaal (met burg apart van alle andere levende spelers)
	$verhaal = geefVerhaal($thema,"Burgemeester",4,count($tuplesL),
		count($burgArray),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulIn($tuplesL,$burgArray,"",$text,$geswoorden);

	//samenvatting maken
	$samenvatting .= "De Burgemeesterverkiezing is geweest.<br />";
	if($vlag) {
		$samenvatting .= "Vanwege gelijkspel ";
		$samenvatting .= "is er geen Burgemeester gekozen.<br />";
	}
	else {
		$samenvatting .= "$burgNaam is tot Burgemeester verkozen.<br />";
	}
	$samenvatting .= "<br />";
	$samenvatting = stemmingOverzicht($overzicht,1);

	return;
}//verkiezingUitslag

function stemmingOverzicht($overzicht,$vlag) {
	$samenvatting = "Uitslag:<br />";
	$samenvatting .= "<ul>";

	//eerst de spelers
	foreach($overzicht as $stem => $namen) {
		if($stem == -1) {
			continue;
		}
		else if($stem == -2) {
			continue;
		}
		else {
			$samenvatting .= "<li>Op $stem gestemd ";
		}
		$aantal = count($namen)/$vlag;
		$samenvatting .= "($aantal): ";
		$namen = array_unique($namen);
		$namen = array_values($namen);
		$aantal = count($namen); //opnieuw tellen
		for($i = 0; $i < $aantal; $i++) {
			if($aantal - $i == 1) {
				$samenvatting .= $namen[$i]  . ".";
			}
			else if($aantal - $i == 2) {
				$samenvatting .= $namen[$i] . " en ";
			}
			else {
				$samenvatting .= $namen[$i] . ", ";
			}
		}//for
		$samenvatting .= "</li>";
	}//foreach

	//dan de blanco
	if(array_key_exists(-1,$overzicht)) {
		$namen = $overzicht[-1];
		$samenvatting .= "<li>Blanco gestemd ";
		$aantal = count($namen)/$vlag;
		$samenvatting .= "($aantal): ";
		$namen = array_unique($namen);
		$namen = array_values($namen);
		$aantal = count($namen); //opnieuw tellen
		for($i = 0; $i < $aantal; $i++) {
			if($aantal - $i == 1) {
				$samenvatting .= $namen[$i]  . ".";
			}
			else if($aantal - $i == 2) {
				$samenvatting .= $namen[$i] . " en ";
			}
			else {
				$samenvatting .= $namen[$i] . ", ";
			}
		}//for
		$samenvatting .= "</li>";
	}

	//dan de inactieven
	if(array_key_exists(-2,$overzicht)) {
		$namen = $overzicht[-2];
		$samenvatting .= "<li>Niet gestemd ";
		$aantal = count($namen)/$vlag;
		$samenvatting .= "($aantal): ";
		$namen = array_unique($namen);
		$namen = array_values($namen);
		$aantal = count($namen); //opnieuw tellen
		for($i = 0; $i < $aantal; $i++) {
			if($aantal - $i == 1) {
				$samenvatting .= $namen[$i]  . ".";
			}
			else if($aantal - $i == 2) {
				$samenvatting .= $namen[$i] . " en ";
			}
			else {
				$samenvatting .= $namen[$i] . ", ";
			}
		}//for
		$samenvatting .= "</li>";
	}

	$samenvatting .= "</ul>";

	return $samenvatting;
}//stemmingOverzicht

function spelerOverzicht($spel) {
	$sid = $spel['SID'];
	$burg = $spel['BURGEMEESTER'];
	$samenvatting = "<br />-=-=-=-<br /><br />";
	$samenvatting .= "Overzicht:<br />";
	$samenvatting .= "<br />";
	if($burg == "") {
		$samenvatting .= "Geen Burgemeester.<br />";
		$samenvatting .= "<br />";
	}
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	$aantal = sqlNum($resultaat);
	$samenvatting .= "$aantal levende spelers:<br />";
	$samenvatting .= "<ul>";
	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		$samenvatting .= "<li>$naam ";
		if($speler['ROL'] == "Dorpsgek" && 
			($speler['SPELFLAGS'] & 128) == 128) {
				$samenvatting .= "(Dorpsgek)";
			}
		if($speler['ID'] == $burg) {
			$samenvatting .= "(Burgemeester)";
		}
		if(($speler['SPELFLAGS'] & 2) == 2) {
				$samenvatting .= "(Schuldgevoel)";
		}
		$samenvatting .= "</li>";
	}
	$samenvatting .= "</ul><br />";
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND<>1");
	$aantal = sqlNum($resultaat);
	$samenvatting .= "$aantal dode spelers:<br />";
	$samenvatting .= "<ul>";
	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		$samenvatting .= "<li>$naam ($rol)</li>";
	}
	$samenvatting .= "</ul><br />";

	return $samenvatting;
}//spelerOverzicht

function brandstapelInleiding(&$text,&$samenvatting,&$auteur,$spel) {
	$tuplesL = array(); //L voor levende spelers
	$tuplesD = array(); //D voor nieuwdode spelers
	$thema = $spel['THEMA'];
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesD,$speler);
	}
	shuffle($tuplesL);
	shuffle($tuplesD);

	//maak verhaal
	$verhaal = geefVerhaal($thema,"Brandstapel",0,count($tuplesL),
		count($tuplesD),$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulIn($tuplesL,$tuplesD,"",$text,$geswoorden);

	//Raaf verhaaltje achtervoegen
	$resultaat = sqlSel(3,
		"SID=$sid AND LEVEND=1 AND ((SPELFLAGS & 4) = 4)");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			$naam = $speler['NAAM'];
			$key = array_search($speler,$tuplesL);
			delArrayElement($tuplesL,$key);
			$verhaal = geefVerhaal($thema,"Raaf",3,count($tuplesL),
				1,$ronde,$sid);
			$text .= $verhaal['VERHAAL'];
			$geswoorden = $verhaal['GESLACHT'];
			array_push($auteur,$verhaal['AUTEUR']);
			$text = vulIn($tuplesL,array($speler),"",$text,$geswoorden);
			array_push($tuplesL,$speler);
			shuffle($tuplesL);
			$samenvatting .= "$naam krijgt het Teken van de Raaf.<br />";
		}
	}

	//Schout verhaaltje achtervoegen
	$resultaat = sqlSel(3,
		"SID=$sid AND ((SPELFLAGS & 8) = 8)");
	if(sqlNum($resultaat) > 0) {
		while($speler = sqlFet($resultaat)) {
			$naam = $speler['NAAM'];
			$key = array_search($speler,$tuplesL);
			delArrayElement($tuplesL,$key);
			$verhaal = geefVerhaal($thema,"Schout",3,count($tuplesL),
				1,$ronde,$sid);
			$text .= $verhaal['VERHAAL'];
			$geswoorden = $verhaal['GESLACHT'];
			array_push($auteur,$verhaal['AUTEUR']);
			$text = vulIn($tuplesL,array($speler),"",$text,$geswoorden);
			array_push($tuplesL,$speler);
			shuffle($tuplesL);
			$samenvatting .= "$naam is opgesloten ";
			$samenvatting .= "en doet niet mee met de stemming.<br />";
		}
	}

	//samenvatting maken
	$samenvatting .= "De Brandstapelstemming begint.<br />";

	//keuzes toevoegen
	$keuzes = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
		(ROL<>'Dorpsgek' OR ((SPELFLAGS & 128) = 0)) AND 
		((SPELFLAGS & 8) = 0)");
	while($speler = sqlFet($resultaat)) {
		array_push($keuzes,$speler['NAAM']);
	}
	array_push($keuzes,"blanco");

	$samenvatting .= "Er kan gestemd worden op:<br />";
	$samenvatting .= "<ul>";
	foreach($keuzes as $naam) {
		$samenvatting .= "<li>$naam</li>";
	}
	$samenvatting .= "</ul><br />";

	return;
}//brandstapelInleiding

function brandstapelUitslag(&$text,&$samenvatting,&$auteur,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$thema = $spel['THEMA'];
	$burgemeester = $spel['BURGEMEESTER'];

	//maak een stem-overzicht
	$overzichtTotaal = brandstapelOverzicht($spel);
	
	//verhaal maken
	$tuplesL = array(); //L voor levende spelers
	$tuplesD = array(); //D voor dode spelers
	$tuplesS = array(); //S voor Jagers/Geliefden (speciaal verhaal)
	$resArray = array();
	$vlag = geefGebeurd($tuplesL,$tuplesD,$tuplesS,$resArray,$spel);
	shuffle($tuplesL);
	shuffle($tuplesD);
	shuffle($tuplesS);

	//bij normaal verhaal (geen jagers/geliefden dood)
	if(count($tuplesS) == 0) {
		schrijfLog($sid,"Normaal verhaal gewenst.\n");
		$samenvatting .= "De Brandstapelstemming is geweest.<br />";
		if(count($tuplesD) == 1) { //altijd 0 of 1
			$speler = $tuplesD[0];
			$id = $speler['ID'];
			$naam = $speler['NAAM'];
			$rol = $speler['ROL'];
			$flags = $speler['SPELFLAGS'];

			if($rol == "Zondebok" && (($flags & 256) == 256)) {
				//pak alle spelers met schuldgevoel
				$schuldgevoel = array();
				$resultaat = sqlSel(3,"LEVEND=1 AND ((SPELFLAGS & 2) = 2)");
				if(sqlNum($resultaat) > 0) {
					while($sp = sqlFet($resultaat)) {
						$key = array_search($sp,$tuplesL);
						if($key !== false) {
							$tuplesL = delArrayElement($tuplesL,$key);
							array_push($schuldgevoel,$sp);
						}
					}
				}
				shuffle($schuldgevoel);
				$zonde = $tuplesD + $schuldgevoel;

				//maak verhaal
				$verhaal = geefVerhaal($thema,"Zondebok",2,count($tuplesL),
					count($zonde),$ronde,$sid);
				$text .= $verhaal['VERHAAL'];
				$geswoorden = $verhaal['GESLACHT'];
				array_push($auteur,$verhaal['AUTEUR']);
				$text = vulIn($tuplesL,$zonde,"",$text,$geswoorden);
				$samenvatting .= "Vanwege gelijkspel ";
				$samenvatting .= "is Zondebok $naam verbrand.<br />"; 
				foreach($schuldgevoel as $schuld) {
					$schuldNaam = $schuld['NAAM'];
					$samenvatting .= "$schuldNaam voelt zich schuldig, ";
					$samenvatting .= "en mag de volgende ronde ";
					$samenvatting .= "niet stemmen.<br />";
				}
			}
			else if(($flags & 1024) == 1024) {
				//dode lijfwacht (met diens Opdrachtgever in tuplesD[1]):
				$resultaat = sqlSel(3,"ROL='Opdrachtgever' AND LIJFWACHT=$id");
				$opdrachtgever = sqlFet($resultaat);
				while(($opdrachtgever['SPELFLAGS'] & 1024) == 1024) {
					$opdrachtID = $opdrachtgever['ID'];
					$resultaat = sqlSel(3,
						"ROL='Opdrachtgever' AND LIJFWACHT=$opdrachtID");
					$opdrachtgever = sqlFet($resultaat);
				}
				$key = array_search($opdrachtgever,$tuplesL);
				if($key !== false) {
					delArrayElement($tuplesL,$key);
					array_push($tuplesD,$opdrachtgever);
				}
				$opNaam = $opdrachtgever['NAAM'];

				$verhaal = geefVerhaal($thema,"Opdrachtgever",2,count($tuplesL),
					$count($tuplesD),$ronde,$sid);
				$text .= $verhaal['VERHAAL'];
				$geswoorden = $verhaal['GESLACHT'];
				array_push($auteur,$verhaal['AUTEUR']);
				$text = vulIn($tuplesL,$tuplesD,"",$text,$geswoorden);
				if($key !== false) {
					$tuplesD = delArrayElement($tuplesD,1);
				}
				$samenvatting .= "$opNaam kreeg de meeste stemmen.<br />";
				$samenvatting .= "Lijfwacht $naam offert zich ";
				$samenvatting .= "voor $opNaam op.<br />";
				$samenvatting .= "$naam ($rol) is op de Brandstapel ";
				$samenvatting .= "verbrand.<br />";
			}
			else {
				//normaal verhaal
				$verhaal = geefVerhaal($thema,"Brandstapel",1,count($tuplesL),
					1,$ronde,$sid);
				$text .= $verhaal['VERHAAL'];
				$geswoorden = $verhaal['GESLACHT'];
				array_push($auteur,$verhaal['AUTEUR']);
				$text = vulIn($tuplesL,$tuplesD,"",$text,$geswoorden);
				$samenvatting .= "$naam ($rol) kreeg de meeste stemmen en ";
				$samenvatting .= "is op de Brandstapel verbrand.<br />";
			}
		}//if
		else {
			//check of er geen pasontdekte (ongetelde) dorpsgek is
			$vlag2 = false;
			foreach($tuplesL as $key => $speler) {
				$flags = $speler['SPELFLAGS'];
				if($speler['ROL'] == "Dorpsgek" && 
					(($flags & 128) == 128) && (($flags & 256) == 0)) {
						delArrayElement($tuplesL,$key);
						$id = $speler['ID'];
						$naam = $speler['NAAM'];
						$verhaal = geefVerhaal($thema,"Dorpsgek",0,
							count($tuplesL),1,$ronde,$sid);
						$text .= $verhaal['VERHAAL'];
						$geswoorden = $verhaal['GESLACHT'];
						array_push($auteur,$verhaal['AUTEUR']);
						$text = vulIn($tuplesL,array($speler),"",$text,
							$geswoorden);
						$samenvatting .= "$naam kreeg de meeste stemmen.";
						$samenvatting .= "<br />";
						$samenvatting .= "$naam is een Dorpsgek ";
						$samenvatting .= "en wordt niet verbrand.<br />";
						$vlag2 = true;
						sqlUp(3,"SPELFLAGS=SPELFLAGS+256","ID=$id");
						break;		
				}
			}
			if(!$vlag2) {
				$verhaal = geefVerhaal($thema,"Brandstapel",1,
					count($tuplesL),0,$ronde,$sid);
				$text .= $verhaal['VERHAAL'];
				$geswoorden = $verhaal['GESLACHT'];
				array_push($auteur,$verhaal['AUTEUR']);
				$text = vulIn($tuplesL,array(),"",$text,$geswoorden);
				$samenvatting .= "Er is geen slachtoffer gevallen.<br />";
			}
		}
	}//if
	else {//speciaal verhaal gewenst

		//boom maken
		$boom = array();
		$boom = maakBoom(-1,$tuplesS,$boom,0,$resArray);

		//begin: check of zondebok-intro, of normale
		$levend = count($tuplesL) + count($tuplesS);
		$resultaat = sqlSel(3,"SID=$sid AND ROL='Zondebok' AND 
			((LEVEND & 1) = 1) AND ((SPELFLAGS & 256) = 256)");
		if(sqlNum($resultaat) > 0) { //zondebok-intro
			$schuldgevoel = array();
			$resultaat = sqlSel(3,"LEVEND=1 AND ((SPELFLAGS & 2) = 2)");
			if(sqlNum($resultaat) > 0) {
				while($sp = sqlFet($resultaat)) {
					$key = array_search($sp,$tuplesL);
					if($key !== false) {
						$tuplesL = delArrayElement($tuplesL,$key);
						array_push($schuldgevoel,$sp);
					}
				}
			}
			shuffle($schuldgevoel);
			$zonde = $tuplesD + $schuldgevoel;

			$verhaal = geefVerhaal($thema,"Zondebok",3,$levend,
				count($zonde),$ronde,$sid);
			$verhaal = geefVerhaal($thema,"Zondebok",2,count($tuplesL),
				count($zonde),$ronde,$sid);
			$text .= $verhaal['VERHAAL'];
			$geswoorden = $verhaal['GESLACHT'];
			array_push($auteur,$verhaal['AUTEUR']);
			$text = vulIn($tuplesL,$zonde,"",$text,$geswoorden);
		}
		else { //normale introductie
			$verhaal = geefVerhaal($thema,"Brandstapel",2,$levend,
				count($tuplesD),$ronde,$sid);
			$text .= $verhaal['VERHAAL'];
			$geswoorden = $verhaal['GESLACHT'];
			array_push($auteur,$verhaal['AUTEUR']);
			$merge = array_merge($tuplesL,$tuplesS);
			shuffle($merge);
			$text = vulIn($merge,$tuplesD,"",$text,$geswoorden);
		}

		//tuplesS bijvullen (beginnende jagers/geliefden toevoegen)
		//en samenvatting maken
		foreach($boom as $id => $target) {
			$resultaat = sqlSel(3,"NAAM='$id' AND SID=$sid");
			$tuple = sqlFet($resultaat);
			array_push($tuplesS,$tuple);
			$naam = $tuple['NAAM'];
			$rol = $tuple['ROL'];
			$samenvatting .= "$naam ($rol) kreeg de meeste stemmen, ";
			$samenvatting .= "en is op de Brandstapel verbrandt.<br />";
		}

		//nu dingen aanvullen met behulp van de boom van jagers/geliefden
		foreach($boom as $id => $target) {
			leesBoom($boom[$id],$id,NULL,$text,$samenvatting,$auteur,
				$tuplesL,$tuplesS,$thema,"Brandstapel",$spel);
		}
	}//else

	if($vlag) {
		dodeDorpsoudste($spel,1,$tuplesL,$text,$samenvatting,$auteur);
	}

	//en maak een samenvatting
	$samenvatting .= "<br />";
	$samenvatting .= stemmingOverzicht($overzichtTotaal,2);
	
	return;
}//brandstapelUitslag

function brandstapelOverzicht($spel) {
	$sid = $spel['SID'];
	$burgemeester = $spel['BURGEMEESTER'];
	if(empty($burgemeester)) {
		$burgemeester = -1;
	}

	$overzicht1 = array();
	$overzicht2 = array();
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND<>0");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$naam = $speler['NAAM'];
		$stem = $speler['STEM'];
		verwijderStem($id,"STEM");
		$flags = $speler['SPELFLAGS'];
		$waarde = 2;
		if(($flags & 16) == 16) { //gewaarschuwd
			$naam .= " (gewaarschuwd)";
			$waarde += 2;
		}
		if($speler['ID'] == $burgemeester) {
			$naam .= " (Burgemeester)";
			$waarde += 1;
		}
		if(($flags & 2) == 2) { //schuldgevoel
			$naam .= " (schuldgevoel)";
		}
		if(($flags & 384) == 384 && $speler['ROL'] == "Dorpsgek") {
			$naam .= " (Dorpsgek)";
		}
		if(($flags & 8) == 8) { //opgesloten
			$naam .= " (opgesloten)";
		}
		if(empty($stem)) {
			$stem = -2;
			$waarde = 2;
		}
		if($stem == -1) {
			$waarde = 2;
		}
		for($i = 0; $i < $waarde; $i++) {
			$key = array_search($stem,$overzicht1);
			if($key === false) { //niet eerder op deze speler gestemd
				array_push($overzicht1,$stem);
				array_push($overzicht2,array($naam));
			}
			else {
				array_push($overzicht2[$key],$naam);
			}
		}//for
		if(($flags & 4) == 4) { //teken van de raaf toevoegen
			for($i = 0; $i < 4; $i++) {
				$key = array_search($id,$overzicht1);
				if($key === false) { //niet eerder op deze speler gestemd
					array_push($overzicht1,$id);
					array_push($overzicht2,array("Teken van de Raaf"));
				}
				array_push($overzicht2[$key],"Teken van de Raaf");
			}//for
		}//if
	}//while
	sqlData($resultaat,0);
	while($speler = sqlFet($resultaat)) {
		$key = array_search($speler['ID'],$overzicht1);
		if($key !== false) {
			$overzicht1[$key] = $speler['NAAM'];
		}
	}//while
	return array_combine($overzicht1,$overzicht2);
}//brandstapelOverzicht

function check_in_array($speler,$array) {
	foreach($array as $tuple) {
		if($tuple['ID'] == $speler['ID']) {
			return true;
		}
	}
	return false;
}//check_in_array

?>
