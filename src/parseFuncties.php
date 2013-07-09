<?php

//handelt een inschrijving af:
//als het adres al bij dit spel bekend is, is het een herinschrijving
//anders: schrijf opnieuw in
function inschrijving($adres,$bericht,$sid) {
	global $tabellen;
	$tabel = $tabellen[3];
	$vlag = false;
	$resultaat = sqlSel(3,"SID=$sid AND EMAIL='$adres'");
	if(sqlNum($resultaat) != 0) {
		schrijfLog($sid,"Herinschrijving.\n");
		$vlag = true;
	}
	else {
		schrijfLog($sid,"Nieuwe speler gevonden.\n");
	}
	$text = explode(",",$bericht);
	$naam = sqlEscape($text[0]);
	if(empty($naam)) {
		schrijfLog($sid,"Geen naam gevonden.\n");
		return false;
	}

	//pak alle gewone letters
	if(!preg_match('/^[A-Za-z]+$/',$naam,$naam)) {
		schrijfLog($sid,"Geen naam gevonden.\n");
		return false;
	}

	//zet hoofd- en kleine letters goed: alles klein behalve de eerste
	$naam = strtolower($naam[0]);
	$naam = ucfirst($naam);

	schrijfLog($sid,"Naam: $naam\n");
	$text = delArrayElement($text,0);
	$bericht = implode(",",$text);
	if(stristr($bericht,"m") != false) { // mannelijke speler
		$geslacht = 1;
		schrijfLog($sid,"Geslacht: Man\n");
	}
	else if(stristr($bericht,"v") != false) { // vrouwelijke speler
		$geslacht = 0;
		schrijfLog($sid,"Geslacht: Vrouw\n");
	}
	else {
		schrijfLog($sid,"Geen geslacht gevonden.\n");
		return false;
	}
	$geslacht += 2; // voor maillijst-flag
	if($vlag) {
		sqlUp(3,"NAAM='$naam',SPELERFLAGS=$geslacht",
			"SID=$sid AND EMAIL='$adres'");
	}
	else {
		$sql = "INSERT INTO $tabel(NAAM,SPELERFLAGS,EMAIL,SID) 
			VALUES ('$naam',$geslacht,'$adres',$sid)";
		sqlQuery($sql);
		$resultaat = sqlSel(4,"SID=$sid");
		$spel = sqlFet($resultaat);
		$levend = $spel['LEVEND'] + 1; //één extra speler
		if($levend == $spel['MAX_SPELERS']) {
			zetFase(2,$sid);
		}
		sqlUp(4,"LEVEND=$levend","SID=$sid");
	}//else

	return true;
}//inschrijving

// geeft naam van speler gebaseerd op email adres en spel,
// of -1 als het adres niet bij het spel hoort,
// of -2 als de speler dood is
function spelerID($bericht,$onderwerp,$adres,$spel) {
	$sid = $spel['SID'];

	$resultaat = sqlSel(3,
		"SID=$sid AND EMAIL='$adres'");
	if(sqlNum($resultaat) == 0) {
		return -1;
	}
	$speler = sqlFet($resultaat);
	$rol = $speler['ROL'];
	if($speler['LEVEND'] == 0) {
		//als dode burgemeester: hij mag nog testament geven
		if($spel['BURGEMEESTER'] == $speler['ID'] && 
			$spel['FASE'] == 11 && 
			(($rol != "Raaf" && $rol != "Schout" && 
			$rol != "Waarschuwer" && $rol != "Jager") || 
			preg_match("/burgemeester/i",$onderwerp) || 
			preg_match("/burgemeester/i",$bericht))) {
				return $speler['ID'];
		}
		return -2;
	}
	return $speler['ID'];
}//spelerID

//zet een stem van speler op NULL
function zetStemNULL($id,$sid,$plek) {
	sqlUp(3,"$plek=NULL","ID=$id");
	return;
}//zetStemNULL

//checkt of een stem een geldige stem is, daarbij ook lettend op 
//levende of dode spelers ($levend), en mogelijke vorige stemmen.
//Kijkt of er maar 1 naam in het bericht voorkomt, of 1 blanco; 
//deze returned hij. Als er meerdere voorkomen, geeft dan FALSE.
function geldigeStem($bericht,$sid,$levend) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1)=$levend)");
	$id = false;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$id = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id !== false) { // meerdere namen in bericht
				return false;
			}
			$id = $speler['ID'];
		}//if
	}//while
	return $id;
}//geldigeStem

//geldige stem, plus niet op $id gestemd
function geldigeStemUitzondering($bericht,$sid,$levend,$id) {
	$stem = geldigeStem($bericht,$sid,$levend);
	if($stem == $id) {
		return false;
	}
	return $stem;
}//geldigeStemUitzondering

function geldigeStemHerhaling($bericht,$sid,$levend,$vorige) {
	$stem = geldigeStem($bericht,$sid,$levend);
	if($stem == $vorige) {
		return false;
	}
	return $stem;
}//geldigeStemHerhaling

function geldigeStemZelfHerhaling($bericht,$sid,$levend,$id,$vorige) {
	$stem = geldigeStemHerhaling($bericht,$sid,$levend,$vorige);
	if($stem == $id) {
		return false;
	}
	return $stem;
}//geldigeStemHerhaling

//pakt 2 geldige stemmen, of blanco
function geldigeStem2($bericht,$sid,&$id1,&$id2) {
	$resultaat = sqlSel(3,
		"SID=$sid AND ((LEVEND & 1) = 1)");
	$id1 = false;
	$id2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$id1 = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id1 == -1) { //blanco en naam in bericht: fout
				$id1 = false;
				return;
			}
			if($id1 !== false) { //meerdere namen in bericht
				if($id2 !== false) { //te veel namen!
					$id1 = false;
					$id2 = false;
					return;
				}
				$id2 = $speler['ID'];
			}//if
			else {
				$id1 = $speler['ID'];
			}
		}//if
	}//while
	return;
}//geldigeStem2

//checkt of de stem van de Raaf geldig is: geldige stem en
//niet een ontdekte dorpsgek
function geldigeStemRaaf($bericht,$sid,$levend) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1)=$levend) AND 
		(ROL<>'Dorpsgek' OR ((SPELFLAGS & 128) = 0))");
	$id = false;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$id = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		if(preg_match("/\b$naam\b/i",$bericht)) {
			if($naam !== false) { // meerdere namen in bericht
				return false;
			}
			$id = $speler['ID'];
		}//if
	}//while
	return $id;
}//geldigeStemRaaf

function geldigeStemOpdracht($bericht,$opdracht,$sid) {
	$stem = geldigeStemUitzondering($bericht,$sid,1,$opdracht);

	if($stem == -1) {
		return $stem;
	}
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
		ROL='Opdrachtgever' AND ID<>$opdracht");
	if(sqlNum($resultaat) == 0) {
		return $stem;
	}
	while($speler = sqlFet($resultaat)) {
		if($stem == $speler['STEM']) {
			return -2;
		}
	}//while
	return $stem;
}//geldigeStemOpdracht

//aparte functie omdat de specifieke LEVEND moet worden gecheckt,
//niet de (LEVEND & 1)
//$flag is 0 (speler redden), of 1 (speler vergiftigen)
function geldigeStemHeks($bericht,$sid,$flag,$id) {
	if($flag) {
		$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1 AND ID<>$id");
	}
	else {
		$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 2) = 2)");
	}
	$id = false;
	if(preg_match("/\bblanco\b/i",$bericht)) {
		$id = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id !== false) { //meerdere namen in bericht
				return false;
			}
			$id = $speler['ID'];
		}//if
	}//while
	return $id;
}//geldigeStemHeks

//checkt of een bericht een geldige Brandstapelstem bevat; 
//let hierop ook op de keuze van de Schout (wie is er opgesloten), 
//of de speler wel mag stemmen (Zondebok) en 
//of hij niet een ontdekte Dorpsgek is:
//deze drie stemmen altijd blanco.
function geldigeStemBrand($id,$bericht,$sid) {
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$naam = $speler['NAAM'];
	if($speler['ROL'] == "Dorpsgek" && ($speler['SPELFLAGS'] & 128) == 128) {
		schrijfLog($sid,"Dorpsgek $naam mag niet stemmen.\n");
		return -1;
	}
	if(($speler['SPELFLAGS'] & 2) == 2) {
		schrijfLog($sid,"$naam voelt zich schuldig en mag niet stemmen.\n");
		return -1;
	}
	if(($speler['SPELFLAGS'] & 8) == 8) {
		schrijfLog($sid,"$naam is opgesloten en mag niet stemmen.\n");
		return -1;
	}
	$stem = geldigeStem($bericht,$sid,1);
	if($stem != -1) { //controleer de stem
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		if($speler['ROL'] == "Dorpsgek" && 
			($speler['SPELFLAGS'] & 128) == 128) {
			schrijfLog($sid,"$naam mag niet op Dorpsgek $stem stemmen.\n");
			return false;
		}
		if(($speler['SPELFLAGS'] & 8) == 8) {
			schrijfLog($sid,"$naam mag niet op opgesloten $stem stemmen.\n");
			return false;
		}
	}
	return $stem;
}//geldigeStemBrand

//checkt voor WW (en Witte WW!) of de stem geldig is
function geldigeStemWWVP($bericht,$sid,$rol) {
	$id = geldigeStem($bericht,$sid,1);
	if(!$id || $id == -1) {
		return $id;
	}
	if($rol == "Weerwolf") {
		$resultaat = sqlSel(3,
			"SID=$sid AND ((LEVEND & 1) = 1) AND ((SPELFLAGS & 32) = 0) AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel(3,
			"SID=$sid AND ((LEVEND & 1) = 1) AND ((SPELFLAGS & 32) = 0) AND 
			ROL='Vampier'");
	}
	while($speler = sqlFet($resultaat)) {
		if($speler['ID'] == $id) {
			return false;
		}
	}//while
	return $id;
}//geldigeStemWWVP

//checkt voor FS of de stem geldig is: spelers die leven, 
//geen Fluitspeler zijn, en niet betoverd zijn. 
//Gebruikt call by reference om direct 2 stemmen te vullen
function geldigeStemFS($bericht,$sid,&$id1,&$id2) {
	geldigeStem2($bericht,$sid,$id1,$id2);
	if($id1 == -1) { //blanco
		return;
	}
	
	//nu checken of geen wakkere Fluitspeler en niet betoverd
	if($id2 !== false) {
		$resultaat = sqlSel(3,"ID=$id2");
		$speler = sqlFet($resultaat);
		if(($speler['SPELFLAGS'] & 1) == 1 || 
			($speler['ROL'] == "Fluitspeler" && 
			(($speler['SPELFLAGS'] & 32) == 0))) {
				$id2 = false;
			}
	}
	$resultaat = sqlSel(3,"ID=$id1");
	$speler = sqlFet($resultaat);
	if(($speler['SPELFLAGS'] & 1) == 1 || ($speler['ROL'] == "Fluitspeler" && 
		(($speler['SPELFLAGS'] & 32) == 0))) {
			$id1 = $id2;
		}
	return;
}//geldigeStemFS

function geldigeStemUniek2($bericht,$afzender,$sid,&$id1,&$id2,$rol) {
	geldigeStem2($bericht,$sid,$id1,$id2);

	//check of spelers niet door andere $rol-en zijn aangewezen
	if($id1 != false && $id2 != false) {
		$resultaat = sqlSel(3,"SID=$sid AND ROL='$rol' AND ID<>$afzender");
		if(sqlNum($resultaat) != 0) {
			while($speler = sqlFet($resultaat)) {
				if($id1 != false && ($id1 == $speler['STEM'] || 
					$id1 == $speler['EXTRA_STEM']) &&
					$id2 != false && ($id2 == $speler['STEM'] || 
					$id2 == $speler['EXTRA_STEM'])) {
						$id1 = -2;
						$id2 = -2;
						break;
				}//if
			}//while
		}//if
	}//if

	return;
}//geldigeStemUniek2

//bepaalt of een stem een geldige stem is voor de Zondebok
//zo niet, returned "false"
//bij blanco, returned "blanco"
//anders returned alle gevonden ID's, met ","ertussen.
function geldigeStemZonde($bericht,$id,$sid) {
	$stem = "";
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	if(preg_match("/\bblanco\b/i",$bericht)) {
		$stem .= "-1";
	}
	while($speler = sqlFet($resultaat)) {
		if($speler['ID'] == $id) {
			continue;
		}
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if(empty($stem)) {
				$stem .= $speler['ID'];
			}
			else if($stem == "-1") { //blanco en naam in bericht
				return false;
			}
			else{
				$stem .= "," . $speler['ID'];
			}
		}//if
	}//while
	return $stem;
}//geldigeStemZonde

//checkt of de speler een dode Burgemeester is
function isDodeBurg($speler,$spel) {
	if($speler['ID'] == $spel['BURGEMEESTER'] &&
		$speler['LEVEND'] != 1) {
			return true;
		}
	return false;
}//isDodeBurg

function zetStem($id,$stem,$sid,$plek) {
	sqlUp(3,"$plek=$stem","ID=$id");
	return;
}//zetStem

?>
