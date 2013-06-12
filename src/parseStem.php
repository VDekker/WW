<?php

//regelt de stem van de speler, gebaseerd op fase, rol, etc.
function parseStem($id,$adres,$spel,$bericht,$onderwerp) {

	global $thuis;
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$rol = $speler['ROL'];
	$naam = $speler['NAAM'];
	$sid = $spel['SID'];
	$init = ($spel['RONDE'] == 0);
	$fase = $spel['FASE'];
	$tweede = ($spel['FLAGS'] & 1);
	$max = $spel['MAX_SPELERS'];

	if($init) {
		switch($fase) {
			case 0:
				if(inschrijving($adres,$bericht,$sid)) {
					schrijfLog($sid,"Speler ingeschreven.\n");
					stuurInschrijving($adres,$sid);
				}
				else {
					schrijfLog($sid,"Inschrijven mislukt.\n");
					stuurInschrijvingFout($adres,$sid);
				}
				break;
			case 2:
				if($rol == "Dief") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$id wil van $stem stelen.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 4:
				if($rol == "Cupido") {
					geldigeStemCupi($bericht,$id,$sid,$stem,$stem2);
					if($stem != false && $stem2 != false) {
						zetStem($id,$stem,$sid,"STEM");
						zetStem($id,$stem2,$sid,"EXTRA_STEM");
						stuurStem2($naam,$adres,$stem,$stem2,$sid);
						schrijfLog($sid,"$id wil $stem en $stem2 " . 
						   "verliefd maken\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 6: 
				if($rol == "Opdrachtgever") {
					$stem = geldigeStemVerleidOpdracht($bericht,$rol,$sid);
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$id wil $stem als Lijfwacht.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			default:
				//spel zit in een niet-wachtfase
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
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$id wil van $stem roven.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 4:
				if($rol == "Klaas Vaak") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $speler['VORIGE_STEM']) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$id wil $stem laten slapen.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
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
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$rol $id kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Verleidster") {
					$stem = geldigeStemVerleidOpdracht($bericht,$rol,$sid);
					if($stem != false && $stem != $spelers['VORIGE_STEM']) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Verleidster $id wil $stem " . 
							"verleiden.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Psychopaat") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $id) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Psychopaat $id wil $stem " . 
							"vermoorden.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Weerwolf") {
					$stem = geldigeStemWWVP($bericht,$sid,"Weerwolf");
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Weerwolf $id wil $stem opeten.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Vampier") {
					$stem = geldigeStemWWVP($bericht,$sid);
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Vampier $id wil $stem bijten.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Witte Weerwolf") {
					//kijk of het de WW-stem is, of de WitteWW-stem
					if(preg_match("/\bwitte\b/i",$onderwerp) || 
						preg_match("/\bwitte\b/i",$bericht)) {
						$stem = geldigeStem($bericht,$sid,1);
						if($stem != false && $stem != $id && $tweede) {
							zetStem($id,$stem,$sid,"EXTRA_STEM");
							stuurStem($naam,$adres,$stem,$sid);
							schrijfLog($sid,"Witte WW $id wil $stem " . 
								"verscheuren.\n");
						}
						else {
							schrijfLog($sid,"Error: geen goede stem " . 
								"gevonden.\n");
							stuurFoutStem($naam,$adres,$sid);
						}
					}//if
					else { // anders is het de WW stem
						$stem = geldigeStemWWVP($bericht,$sid);
						if($stem != false) {
							zetStem($id,$stem,$sid,"STEM");
							stuurStem($naam,$adres,$stem,$sid);
							schrijfLog($sid,"Witte WW $id wil $stem " . 
								"opeten.\n");
						}
						else {
							schrijfLog($sid,"Error: geen goede stem " .
								"gevonden.\n");
							stuurFoutStem($naam,$adres,$sid);
						}
					}//else
				}//else if
				else if($rol == "Goochelaar") {
					geldigeStemGoochel($bericht,$id,$sid,$stem,$stem2);
					if((($stem == -1) || 
						($stem != false && 
						$stem2 != false && $stem != $stem2)) &&
						($stem != $speler['VORIGE_STEM'] || 
						$stem2 != $speler['VORIGE_STEM_EXTRA']) &&
						($stem != $speler['VORIGE_STEM_EXTRA'] ||
						$stem2 != $speler['VORIGE_STEM'])) {
						zetStem($id,$stem,$sid,"STEM");
						zetStem($id,$stem2,$sid,"EXTRA_STEM");
						stuurStem2($naam,$adres,$stem,$stem2,$sid);
						schrijfLog($sid,"$id wil $stem en $stem2 " .
							"verwisselen.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 8:
				if($rol == "Heks") { 
//TODO mail Heks van haar stemmen
					$resultaat = sqlSel(3,"ID=$id");
					$heks = sqlFet($resultaat);
					$drank = $heks['SPELFLAGS'];
					$flag = 0; //houdt de keuze bij: voor mailen
					$stem = geldigeStemHeks($bericht,$sid,3);//speler redden
					$stem2 = geldigeStemHeks($bericht,$sid,1);//vergiftigen
					if($stem != false && 
						($stem == -1 || (($drank & 16) == 16))) {
						zetStem($id,$stem,$sid,"STEM");
						$flag += 1;
						schrijfLog($sid,"Heks $id wil $stem te redden.\n");
					}
					else{
						zetStemNULL($id,$sid,"STEM");
					}
					if($stem2 != false && $stem2 != $id && 
						($stem2 == -1 || (($drank & 32) == 32))) {
						zetStem($id,$stem2,$sid,"EXTRA_STEM");
						$flag += 2;
						schrijfLog($sid,"Heks $id wil $stem2 vergiftigen.\n");
					}
					else{
						zetStemNULL($id,$sid,"EXTRA_STEM");
					}
					if(($stem == false || 
						($drank & 16 != 16)) && 
						($stem2 == false || $stem2 == $id || 
						($drank & 32 != 32))) {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Fluitspeler") { 
//TODO mail FS met zijn stemmen
					geldigeStemFS($bericht,$sid,$stem,$stem2);
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						if($stem2 != false) {
							zetStem($id,$stem2,$sid,"EXTRA_STEM");
							schrijfLog($sid,"$id wil $stem en $stem2 " .
								"betoveren.\n");
						}
						else {
							zetStemNULL($id,$sid,"EXTRA_STEM");
							schrijfLog($sid,"$id wil enkel $stem " .
								"betoveren.\n");
						}
					}//if
					else {
						schrijfLog($sid,"Error: geen geldige stemmen " .
							"gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 11:
				if(isDodeBurg($speler,$spel) &&
					(preg_match("/burgemeester/i",$onderwerp) || 
						preg_match("/burgemeester/i",$bericht) ||
						preg_match("/testament/i",$onderwerp) ||
						preg_match("/testament/i",$bericht) ||
						preg_match("/opvolger/i",$onderwerp) ||
						preg_match("/opvolger/i",$bericht))) {
					$stem = geldigeStemBurg($bericht,$sid,1);
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Burgemeester $id wil dat " .
							"$stem hem opvolgt.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Raaf") {
					$stem = geldigeStemRaaf($bericht,$sid,1);
					if($stem != false) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Raaf $id kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Schout") {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false && $stem != $speler['VORIGE_STEM']) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Schout $id kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Waarschuwer") { //mag niet op zichzelf stemmen
					$stem = geldigeStemWaarschuw($bericht,$sid,1);
					if($stem != false && $stem != $id) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$rol $id kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Jager" && isNieuwDood($id)) {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Jager $id wil $stem neerschieten.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler...\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 13:
				$stem = geldigeStem($bericht,$sid,1);
				if($stem != false) {
					zetStem($id,$stem,$sid,"STEM");
					stuurStem($naam,$adres,$stem,$sid);
					schrijfLog($sid,"$id stemt op $stem als Burgemeester.\n");
				}
				else {
					schrijfLog($sid,"Error: geen goede stem gevonden.\n");
					stuurFoutStem($naam,$adres,$sid);
				}
				break;
			case 15:
				$stem = geldigeStemBrand($id,$bericht,$sid);
				if($stem != false) {
					zetStem($id,$stem,$sid,"STEM");
					stuurStem($naam,$adres,$stem,$sid);
					schrijfLog($sid,"$id stemt op $stem voor " . 
						"de Brandstapel.\n");
				}
				else {
					schrijfLog($sid,"Error: geen goede stem gevonden.\n");
					stuurFoutStem($naam,$adres,$sid);
				}
				break;
			case 18:
				if($rol == "Jager" && isNieuwDood($id)) {
					$stem = geldigeStem($bericht,$sid,1);
					if($stem != false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Jager $id wil $stem neerschieten.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Zondebok" && isNieuwDood($id)) {
//TODO mail Zondebok
					$stem = geldigeStemZonde($bericht,$sid);
					if($stem != false) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						schrijfLog($sid,"Zondebok $id wil schuldgevoel " . 
							"opwekken in $stem.\n");
					}
					else {
						schrijfLog($sid,"Error: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: verkeerde rol/speler.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			default:
				//spel zit in een niet-wachtfase
				houJeMond($naam,$adres,$sid);
				break;

		}//switch
	}//else
	return;
}//parseStem

?>
