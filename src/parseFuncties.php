<?php

//handelt een inschrijving af
function inschrijving($adres,$bericht,$sid) {
	echo "Nieuwe speler gevonden.\n";
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
	$sql = "INSERT INTO Spelers(NAAM,GESLACHT,EMAIL,SPEL) 
		VALUES ('$naam',$geslacht,'$adres','$sid')";
	sqlQuery($sql);
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$levend = $spel['LEVEND'] + 1; //één extra speler
	if($levend == $spel['MAX_SPELERS']) {
		zetFase(2,$sid);
	}
	sqlUp("Spellen","LEVEND=$levend","SID='$sid'");
	return true;
}//inschrijving

// geeft naam van speler gebaseerd op email adres en spel,
// of "" als het adres niet bij het spel hoort.
function spelerNaam($adres,$sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL='$sid' AND EMAIL='$adres' AND LEVEND=1");
	$speler = sqlFet($resultaat);
	return $speler['NAAM'];
}//spelerNaam()

//zet een stem van speler op NULL
function zetStemNULL($naam,$sid,$plek) {
	sqlUp("Spelers","$plek=NULL","SPEL='$sid' AND NAAM='$naam'");
	return;
}//zetStemNULL

//checkt of een stem een geldige stem is, daarbij ook lettend op 
//levende of dode spelers ($levend), en mogelijke vorige stemmen.
//Kijkt of er maar 1 naam in het bericht voorkomt, of 1 blanco; 
//deze returned hij. Als er meerdere voorkomen, geeft dan FALSE.
function geldigeStem($bericht,$sid,$levend) {
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=$levend");
	$naam = false;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$naam = "blanco";
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($naam != false) { // meerdere namen in bericht
				return false;
			}
			$naam = $speler['NAAM'];
		}//if
	}//while
	return $naam;
}//geldigeStem

//checkt of de stem van de Verleidster geldig is.
//Hierbij worden stemmen geweigerd als een andere Verleidster
//al op die speler heeft gestemd.
function geldigeStemVerleidOpdracht($bericht,$rol,$sid) {
	$naam = geldigeStem($bericht,$sid,1);
	if(!$naam) {
		echo "Geen speler gevonden...\n";
		return;
	}
	if($naam == "blanco") {
		return $naam;
	}
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] == $rol) {
			if($naam == $speler['STEM']) {
				echo "$naam is al eerder gekozen.\n";
				return false;
			}
		}//if
	}//while
	return $naam;
}//geldigeStemVerleidOpdracht

function geldigeStemHeks($bericht,$sid,$nieuw) {
	$resultaat = sqlSel("Spelers",
		"SPEL='$sid' AND LEVEND=1 AND NIEUW_DOOD=$nieuw");
	$naam = false;
	if(preg_match("/\bblanco\b/i",$bericht)) {
		$naam = "blanco";
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($naam != false) { //meerdere namen in bericht
				return false;
			}
			$naam = $speler['NAAM'];
		}//if
	}//while
	return $naam;
}//geldigeStemHeks

//checkt of een bericht een geldige Brandstapelstem bevat; 
//let hierop ook op de keuze van de Schout (wie is er opgesloten), 
//of de speler wel mag stemmen (Zondebok) en 
//of hij niet een ontdekte Dorpsgek is:
//deze drie stemmen altijd blanco.
function geldigeStemBrand($naam,$bericht,$sid) {
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	if($speler['GEK']) {
		echo "Dorpsgek $naam mag niet stemmen.\n";
		return "blanco";
	}
	if($speler['SCHULD']) {
		echo "$naam voelt zich schuldig en mag niet stemmen.\n";
		return "blanco";
	}
	$stem = geldigeStem($bericht,$sid,1);
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND ROL='Schout'");
	while($schout = sqlFet($resultaat)) {
		if($schout['EXTRA_STEM'] == $naam) {
			echo "$naam is opgesloten en mag niet stemmen.\n";
			return "blanco";
		}
		if($stem != "blanco" && $stem == $schout['EXTRA_STEM']) {
			echo "$naam stemt op $stem, die is opgesloten.\n";
			return false;
		}
	}//while
	return $stem;
}//geldigeStemBrand

//checkt voor WW (en Witte WW!) of de stem geldig is
function geldigeStemWWVP($bericht,$sid,$rol) {
	$naam = geldigeStem($bericht,$sid,1);
	if(!$naam || $naam == "blanco") {
		return $naam;
	}
	if($rol == "Weerwolf") {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1 AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1 AND 
			ROL='Vampier'");
	}
	while($speler = sqlFet($resultaat)) {
		if($speler['NAAM'] == $naam && wordtWakker($naam,$sid)) {
			return false;
		}
	}//while
	return $naam;
}//geldigeStemWWVP

//checkt voor FS of de stem geldig is: spelers die leven, 
//geen Fluitspeler zijn, en niet betoverd zijn. 
//Gebruikt call by reference om direct 2 stemmen te vullen
function geldigeStemFS($bericht,$sid,&$naam,&$naam2) {
	$resultaat = sqlSel("Spelers",
		"SPEL='$sid' AND LEVEND=1 AND ROL<>'Fluitspeler'");
	$naam = false;
	$naam2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$naam = "blanco";
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($naam != false) { //meerdere namen in bericht
				if($naam2 != false) { //te veel namen!
					$naam = false;
					$naam2 = false;
					return;
				}
				$naam2 = $speler['NAAM'];
			}//if
			else {
				$naam = $speler['NAAM'];
			}
		}//if
	}//while
	return;
}//geldigeStemFS

//checkt voor Goochelaar of de stem geldig is: spelers die leven. 
//Gebruikt call by reference om direct 2 stemmen te vullen
function geldigeStemGoochel($bericht,$afzender,$sid,&$naam,&$naam2) {
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1");
	$naam = false;
	$naam2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$naam = "blanco";
		return;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($naam != false) { //meerdere namen in bericht
				if($naam2 != false) { //te veel namen!
					$naam = false;
					$naam2 = false;
					return;
				}
				$naam2 = $speler['NAAM'];
			}//if
			else {
				$naam = $speler['NAAM'];
			}
		}//if
	}//while

	if($naam != false && $naam2 != false) {
		sqlData($resultaat,0);
		while($speler = sqlFet($resultaat)) {
			if($speler['ROL'] == "Goochelaar" && 
				$speler['NAAM'] != $afzender) { //check zijn stemmen
				if($naam != false && ($naam == $speler['STEM'] || 
					$naam == $speler['EXTRA_STEM'])) {
					echo "$naam was al eerder gewisseld.\n";
					$naam = false;
				}//if
				if($naam2 != false && ($naam2 == $speler['STEM'] || 
					$naam2 == $speler['EXTRA_STEM'])) {
					echo "$naam2 was al eerder gewisseld.\n";
					$naam2 = false;
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
function geldigeStemCupi($bericht,$afzender,$sid,&$naam,&$naam2) {
	$resultaat = sqlSel("Spelers","SPEL='$sid'");
	$naam = false;
	$naam2 = false;
	$teller = 0;
	if(preg_match("/\bblanco\b/i",$bericht)) { //check op blanco
		$naam = "blanco";
		return;
	}
	while($speler = sqlFet($resultaat)) {
		$zoek = "/\b" . $speler['NAAM'] . "\b/i";
		if(preg_match("$zoek",$bericht)) {
			if($naam != false) { //meerdere namen in bericht
				if($naam2 != false) { //te veel namen!
					$naam = false;
					$naam2 = false;
					return;
				}
				$naam2 = $speler['NAAM'];
			}//if
			else {
				$naam = $speler['NAAM'];
			}
		}//if
	}//while

	if($naam != false && $naam2 != false) {
		sqlData($resultaat,0);
		while($speler = sqlFet($resultaat)) {
			if($speler['ROL'] == "Cupido" && $speler['NAAM'] != $afzender) {
				if($naam != false && ($naam == $speler['STEM'] || 
					$naam == $speler['EXTRA_STEM'])) {
					echo "$naam is al verliefd op iemand.\n";
					$naam = false;
				}//if
				if($naam2 != false && ($naam2 == $speler['STEM'] || 
					$naam2 == $speler['EXTRA_STEM'])) {
					echo "$naam2 is al verliefd op iemand.\n";
					$naam2 = false;
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
	$stem = "";
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1");
	if(preg_match("/\bblanco\b/i",$bericht)) {
		$stem = "blanco";
	}
	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		if(preg_match("/\b$naam\b/i",$bericht)) {
			if($stem == "") {
				$stem = $naam;
			}
			else if($stem == "blanco") {
				return false;
			}
			else{
				$stem = $stem . ", $naam";
			}
		}//if
	}//while
	return $stem;
}//geldigeStemZonde

//checkt of de speler een dode Burgemeester is
function isDodeBurg($naam,$sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL='$sid' AND NIEUW_DOOD=1 AND NAAM IN 
		(SELECT BURGEMEESTER FROM Spellen WHERE SID='$sid')");
	$burgemeester = sqlFet($resultaat);
	return ($naam == $burgemeester['NAAM']);
}//dodeBurg

function zetStem($naam,$stem,$sid,$plek) {
	sqlUp("Spelers","$plek='$stem'","SPEL='$sid' AND NAAM='$naam'");
	return;
}//zetStem

?>
