<?php

//regelt de stem van de speler, gebaseerd op fase, rol, etc.
function parseStem($naam,$adres,$sid,$bericht,$onderwerp,
	$init,$fase,$tweede,$max) {

	global $thuis;
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$rol = $speler['ROL'];

	if($init) {
		switch($fase) {
			case 1:
				if(inschrijving($adres,$bericht,$sid)) {
					echo "Speler ingeschreven.\n";
					stuurInschrijving($adres,$sid);
				}
				else {
					echo "Inschrijven mislukt.\n";
					stuurInschrijvingFout($adres,$sid);
				}
				break;
			case 3:
				if($rol == "Dief") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$naam wil van $stem stelen.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 5:
				if($rol == "Cupido") {
					geldigeStemCupi($bericht,$naam,$sid,$stem,$stem2);
					if($stem != false && $stem2 != false) {
						zetStem($naam,$stem,$sid,"STEM");
						zetStem($naam,$stem2,$sid,"EXTRA_STEM");
						stuurStem2($naam,$adres,$stem,$stem2,$sid);
						echo "$naam wil $stem en $stem2 verliefd maken\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 7: 
				if($rol == "Opdrachtgever") {
					$stem = geldigeStemVerleidOpdracht($bericht,$rol,$sid);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$naam wil $stem als Lijfwacht.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			default:
				houJeMond($naam,$adres,$sid);
				break;
		}//switch
	}//if
	else {
		switch($fase) {
			case 1:
				if($rol == "Grafrover") {
					$stem = geldigeStem($bericht,$sid,0);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$naam wil van $stem roven.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 4:
				if($rol == "Klaas Vaak") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $speler['VORIGE_STEM']) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$naam wil $stem laten slapen.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 6:
				//ontvang stem van Genezer, Ziener, Priester, Slet en Dwaas
				if(($rol == "Genezer") || ($rol == "Ziener") || 
					($rol == "Priester") || ($rol == "Slet") || 
					($rol == "Dwaas")) {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $speler['VORIGE_STEM']) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$rol $naam kiest $stem.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Verleidster") {
					$stem = geldigeStemVerleidOpdracht($bericht,$rol,$sid);
					if($stem != false && $stem != $spelers['VORIGE_STEM']) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Verleidster $naam wil $stem verleiden.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Psychopaat") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $naam) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Psychopaat $naam wil $stem vermoorden.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Weerwolf") {
					$stem = geldigeStemWWVP($bericht,$sid,"Weerwolf");
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Weerwolf $naam wil $stem opeten.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Vampier") {
					$stem = geldigeStemWWVP($bericht,$sid);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Vampier $naam wil $stem bijten.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Witte Weerwolf") {
					//kijk of het de WW-stem is, of de WitteWW-stem
					if(preg_match("/\bwitte\b/i",$onderwerp) || 
						preg_match("/\bwitte\b/i",$bericht)) {
						$stem = geldigeStem($bericht,$sid,1);
						if($stem != false && $stem != $naam && $tweede) {
							zetStem($naam,$stem,$sid,"EXTRA_STEM");
							stuurStem($naam,$adres,$stem,$sid);
							echo "Witte WW $naam wil $stem verscheuren.\n";
						}
						else {
							echo "Error: geen goede stem gevonden...\n";
							stuurFoutStem($naam,$adres,$sid);
						}
					}//if
					else { // anders is het de WW stem
						$stem = geldigeStemWWVP($bericht,$sid);
						if($stem != false) {
							zetStem($naam,$stem,$sid,"STEM");
							stuurStem($naam,$adres,$stem,$sid);
							echo "Witte WW $naam wil $stem opeten.\n";
						}
						else {
							echo "Error: geen goede stem gevonden...\n";
							stuurFoutStem($naam,$adres,$sid);
						}
					}//else
				}//else if
				else if($rol == "Goochelaar") {
					geldigeStemGoochel($bericht,$naam,$sid,$stem,$stem2);
					if((($stem == "blanco") || 
						($stem != false && 
						$stem2 != false && $stem != $stem2)) &&
						($stem != $speler['VORIGE_STEM'] || 
						$stem2 != $speler['VORIGE_STEM_EXTRA']) &&
						($stem != $speler['VORIGE_STEM_EXTRA'] ||
						$stem2 != $speler['VORIGE_STEM'])) {
						zetStem($naam,$stem,$sid,"STEM");
						zetStem($naam,$stem2,$sid,"EXTRA_STEM");
						stuurStem2($naam,$adres,$stem,$stem2,$sid);
						echo "$naam wil $stem en $stem2 verwisselen.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 8:
				if($rol == "Heks") {
					$resultaat = sqlSel("Spelers",
						"SPEL='$sid' AND NAAM='$naam'");
					$heks = sqlFet($resultaat);
					$drank = $heks['HEKS_DRANK'];
					$flag = 0; //houdt de keuze bij: voor mailen
					$stem = geldigeStemHeks($bericht,$sid,1);
					$stem2 = geldigeStemHeks($bericht,$sid,0);
					if($stem != false && 
						($stem == "blanco" || (($drank & 1) == 1))) {
						zetStem($naam,$stem,$sid,"STEM");
						$flag += 1;
						//TODO mail
						echo "Heks $naam wil $stem te redden.\n";
					}
					else{
						zetStemNULL($naam,$sid,"STEM");
					}
					if($stem2 != false && $stem2 != $naam && 
						($stem2 == "blanco" || (($drank & 2) == 2))) {
						zetStem($naam,$stem2,$sid,"EXTRA_STEM");
						$flag += 2;
						//TODO mail
						echo "Heks $naam wil $stem2 vergiftigen.\n";
					}
					else{
						zetStemNULL($naam,$sid,"EXTRA_STEM");
					}
					if(($stem == false || 
						($drank & 1 != 1)) && 
						($stem2 == false || $stem2 == $naam || 
						($drank & 2 != 2))) {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Fluitspeler") {
					geldigeStemFS($bericht,$sid,$stem,$stem2);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						if($stem2 != false) {
							zetStem($naam,$stem2,$sid,"EXTRA_STEM");
							//TODO mail
							echo "$naam wil $stem en $stem2 betoveren.\n";
						}
						else {
							//TODO mail (eventueel met: tweede stem klopte niet
							zetStemNULL($naam,$sid,"EXTRA_STEM");
							echo "$naam wil enkel $stem betoveren.\n";
						}
					}//if
					else {
						echo "Error: geen geldige stemmen gevonden.\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 11:
				if(isDodeBurg($naam,$sid) &&
					(preg_match("/burgemeester/i",$onderwerp) || 
						preg_match("/burgemeester/i",$bericht) ||
						preg_match("/testament/i",$onderwerp) ||
						preg_match("/testament/i",$bericht) ||
						preg_match("/opvolger/i",$onderwerp) ||
						preg_match("/opvolger/i",$bericht))) {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Burgemeester $naam wil dat $stem hem opvolgt.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Raaf" || //TODO Raaf mag wel stem herhalen...
					$rol == "Schout") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $speler['VORIGE_STEM']) {
						zetStem($naam,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$rol $naam kiest $stem.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
					}//else if
				else if($rol == "Waarschuwer") { //mag niet op zichzelf stemmen
					$stem = geldigeStemWaarschuw($bericht,$sid,1);
					if($stem != false && $stem != $naam]) {
						zetStem($naam,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "$rol $naam kiest $stem.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Jager" && isNieuwDood($naam,$sid)) {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Jager $naam wil $stem neerschieten.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 13:
				$stem = geldigeStem($bericht,$sid,1);
				if($stem != false) {
					zetStem($naam,$stem,$sid,"STEM");
					stuurStem($naam,$adres,$stem,$sid);
					echo "$naam stemt op $stem als Burgemeester.\n";
				}
				else {
					echo "Error: geen goede stem gevonden...\n";
					stuurFoutStem($naam,$adres,$sid);
				}
				break;
			case 15:
				$stem = geldigeStemBrand($naam,$bericht,$sid);
				if($stem != false) {
					zetStem($naam,$stem,$sid,"STEM");
					stuurStem($naam,$adres,$stem,$sid);
					echo "$naam stemt op $stem voor de Brandstapel.\n";
				}
				else {
					echo "Error: geen goede stem gevonden...\n";
					stuurFoutStem($naam,$adres,$sid);
				}
				break;
			case 18:
				if($rol == "Jager" && isNieuwDood($naam,$sid)) {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						echo "Jager $naam wil $stem neerschieten.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Zondebok" && isNieuwDood($naam,$sid)) {
					$stem = geldigeStemZonde($bericht,$sid);
					if($stem != false) {
						zetStem($naam,$stem,$sid,"STEM");
						//TODO mail
						echo "Zondebok wil schuldgevoel opwekken in: $stem.\n";
					}
					else {
						echo "Error: geen goede stem gevonden...\n";
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					echo "Error: verkeerde rol/speler...\n";
					houJeMond($naam,$adres,$sid);
				}
				break;
			default:
				houJeMond($naam,$adres,$sid);
				break;

		}//switch
	}//else
	return;
}//parseStem

?>
