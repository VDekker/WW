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
		echo "Herinschrijving.\n";
		$vlag = true;
	}
	else {
		echo "Nieuwe speler gevonden.\n";
	}
	$text = explode(",",$bericht);
	$naam = sqlEscape($text[0]);
	if(empty($naam)) {
		echo "Geen naam gevonden.\n";
		return false;
	}
	if(!preg_match('/[^A-Za-z]/',$naam)) { //andere tekens dan gewone letters
		echo "Naam bevatte andere tekens dan letters.\n";
		return false;
	}
	$naam = strtolower($naam);
	$naam = ucfirst($naam);
	echo "Naam: $naam\n";
	$text = delArrayElement($text,0);
	$bericht = implode(",",$text);
	if(stristr($bericht,"m") != false) { // mannelijke speler
		$geslacht = 1;
		echo "Geslacht: Man\n";
	}
	else if(stristr($bericht,"v") != false) { // vrouwelijke speler
		$geslacht = 0;
		echo "Geslacht: Vrouw\n";
	}
	else {
		echo "Geen geslacht gevonden.\n";
		return false;
	}
	if($vlag) {
		sqlUp(3,"NAAM='$naam',SPELERFLAGS=$geslacht",
			"SID=$sid AND EMAIL='$adres'");
	}
	else {
		$sql = "INSERT INTO $tabel(NAAM,SPELERFLAGS,EMAIL,SPEL) 
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
// of "" als het adres niet bij het spel hoort.
// Speler moet levend zijn, anders niet.
function spelerID($adres,$sid) {
	$resultaat = sqlSel(3,
		"SID=$sid AND EMAIL='$adres' AND ((LEVEND & 1) = 1)");
	$speler = sqlFet($resultaat);
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
			if($naam != false) { // meerdere namen in bericht
				return false;
			}
			$id = $speler['ID'];
		}//if
	}//while
	return $id;
}//geldigeStem

//checkt of de stem van de Verleidster geldig is.
//Hierbij worden stemmen geweigerd als een andere Verleidster
//al op die speler heeft gestemd.
function geldigeStemVerleidOpdracht($bericht,$rol,$sid) {
	$id = geldigeStem($bericht,$sid,1);
	if(!$id) {
		echo "Geen speler gevonden...\n";
		return;
	}
	if($id == -1) {
		return $id;
	}
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] == $rol) {
			if($id == $speler['STEM']) {
				echo "$id is al eerder gekozen.\n";
				return false;
			}
		}//if
	}//while
	return $id;
}//geldigeStemVerleidOpdracht

//checkt of de stem geldig is:
//$nieuw kan 3 (dode speler redden) zijn of 1 (levende speler doden)
function geldigeStemHeks($bericht,$sid,$levend) {
	$resultaat = sqlSel(3,
		"SID=$sid AND LEVEND=$levend");
	$id = false;
	if(preg_match("/\bblanco\b/i",$bericht)) {
		$id = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id != false) { //meerdere namen in bericht
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
	if($speler['ROL'] == "Dorpsgek" && ($speler['SPELFLAGS'] & 128) == 128) {
		echo "Dorpsgek $id mag niet stemmen.\n";
		return -1;
	}
	if(($speler['SPELFLAGS'] & 2) == 2) {
		echo "$id voelt zich schuldig en mag niet stemmen.\n";
		return -1;
	}
	if(($speler['SPELFLAGS'] & 2048) == 2048) {
		echo "$id is opgesloten en mag niet stemmen.\n";
		return -1;
	}
	$stem = geldigeStem($bericht,$sid,1);
	if($stem != -1) { //controleer de stem
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		if($speler['ROL'] == "Dorpsgek" && 
			($speler['SPELFLAGS'] & 128) == 128) {
			echo "$id mag niet op Dorpsgek $stem stemmen.\n";
			return -1;
		}
		if(($speler['SPELFLAGS'] & 2048) == 2048) {
			echo "$id mag niet op opgesloten $stem stemmen.\n";
			return -1;
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
		$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1) AND 
			ROL='Vampier'");
	}
	while($speler = sqlFet($resultaat)) {
		if($speler['ID'] == $id && wordtWakker($id,$sid)) {
			return false;
		}
	}//while
	return $id;
}//geldigeStemWWVP

//checkt voor FS of de stem geldig is: spelers die leven, 
//geen Fluitspeler zijn, en niet betoverd zijn. 
//Gebruikt call by reference om direct 2 stemmen te vullen
function geldigeStemFS($bericht,$sid,&$id1,&$id2) {
	$resultaat = sqlSel(3,
		"SID=$sid AND ((LEVEND & 1) = 1) AND ROL<>'Fluitspeler'");
	$id1 = false;
	$id2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$id1 = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id1 != false) { //meerdere namen in bericht
				if($id2 != false) { //te veel namen!
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
}//geldigeStemFS

//checkt voor Goochelaar of de stem geldig is: spelers die leven. 
//Gebruikt call by reference om direct 2 stemmen te vullen
function geldigeStemGoochel($bericht,$afzender,$sid,&$id1,&$id2) {
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	$id1 = false;
	$id2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$id1 = -1;
		return;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id1 != false) { //meerdere namen in bericht
				if($id2 != false) { //te veel namen!
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

	//check of niet door andere Goochelaar verwisseld
	if($id1 != false && $id2 != false) {
		sqlData($resultaat,0);
		while($speler = sqlFet($resultaat)) {
			if($speler['ROL'] == "Goochelaar" && 
				$speler['ID'] != $afzender) { //check zijn stemmen
				if($id1 != false && ($id1 == $speler['STEM'] || 
					$id1 == $speler['EXTRA_STEM'])) {
					echo "$id1 was al eerder gewisseld.\n";
					$id1 = false;
				}//if
				if($id2 != false && ($id2 == $speler['STEM'] || 
					$id2 == $speler['EXTRA_STEM'])) {
					echo "$id2 was al eerder gewisseld.\n";
					$id2 = false;
				}//if
			}//if
		}//if
	}//if
	return;
}//geldigeStemGoochel

//checkt voor Cupido of de stem geldig is: 
//spelers op wie andere Cupido's nog niet hebben gestemd 
//(niet meerdere geliefden voor één speler)
//Gebruikt call by reference om direct 2 stemmen te vullen
function geldigeStemCupi($bericht,$afzender,$sid,&$id1,&$id2) {
	$resultaat = sqlSel(3,"SID=$sid");
	$id1 = false;
	$id2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$id1 = -1;
		return;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($id1 != false) { //meerdere namen in bericht
				if($id2 != false) { //te veel namen!
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

	//check of spelers niet door andere Cupido's zijn aangewezen
	if($id1 != false && $id2 != false) {
		sqlData($resultaat,0);
		while($speler = sqlFet($resultaat)) {
			if($speler['ROL'] == "Cupido" && $speler['ID'] != $afzender) {
				if($id1 != false && ($id1 == $speler['STEM'] || 
					$id1 == $speler['EXTRA_STEM'])) {
					echo "$id1 is al verliefd op iemand.\n";
					$id1 = false;
				}//if
				if($id2 != false && ($id2 == $speler['STEM'] || 
					$id2 == $speler['EXTRA_STEM'])) {
					echo "$id2 is al verliefd op iemand.\n";
					$id2 = false;
				}//if
			}//if
		}//while
	}//if
	return;
}//geldigeStemCupi

//bepaalt of een stem een geldige stem is voor de Zondebok
//zo niet, returned "false"
//bij blanco, returned "blanco"
//anders returned alle gevonden namen, met ","ertussen.
function geldigeStemZonde($bericht,$sid) {
	$stem = false;
	$resultaat = sqlSel(3,"SID=$sid AND ((LEVEND & 1) = 1)");
	if(preg_match("/\bblanco\b/i",$bericht)) {
		$stem = -1;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($stem == false) {
				$stem = $speler['ID'];
			}
			else if($stem == -1) {
				return false;
			}
			else{
				$stem = $stem . ", " . $speler['NAAM'];
			}
		}//if
	}//while
	return $stem;
}//geldigeStemZonde

//checkt of de speler een dode Burgemeester is
function isDodeBurg($id,$sid) {
	$resultaat = sqlSel(3,
		"LEVEND=1 AND ID IN 
		(SELECT BURGEMEESTER FROM Spellen WHERE SID=$sid)");
	if(sqlNum($resultaat) == 0) { //geen dode burg
		return false;
	}
	$burgemeester = sqlFet($resultaat);
	return ($id == $burgemeester['ID']);
}//dodeBurg

function zetStem($id,$stem,$sid,$plek) {
	sqlUp(3,"$plek=$stem","ID=$id");
	return;
}//zetStem

?>
