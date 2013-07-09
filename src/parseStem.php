<?php

//regelt de stem van de speler, gebaseerd op fase, rol, etc.
function parseStem($id,$adres,$spel,$bericht,$onderwerp) {

	global $thuis;
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$rol = $speler['ROL'];
	$naam = $speler['NAAM'];
	$levend = $speler['LEVEND'];
	$spelflags = $speler['SPELFLAGS'];
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
					$stem = geldigeStemUitzondering($bericht,$sid,1,$id);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Dief $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Dief: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 4:
				if($rol == "Cupido") {
					geldigeStemUniek2($bericht,$id,$sid,$stem,$stem2,"Cupido");
					if($stem !== false && $stem2 !== false && 
						$stem != $stem2) {
						zetStem($id,$stem,$sid,"STEM");
						zetStem($id,$stem2,$sid,"EXTRA_STEM");
						stuurStem2($naam,$adres,$stem,$stem2,$sid);
						schrijfLog($sid,
							"Cupido $naam kiest $stem en $stem2.\n");
						}
					else if($stem == -1) { //blanco
						zetStem($id,$stem,$sid,"STEM");
						zetStem($id,"NULL",$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Cupido $naam kiest -1.\n");
					}
					else if($stem == -2) {
						schrijfLog($sid,"Cupido: speler is al gekozen.\n");
						stuurFoutStem2($naam,$adres,$sid);
					}
					else {
						schrijfLog($sid,"Cupido: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 6: 
				if($rol == "Opdrachtgever") {
					$stem = geldigeStemOpdracht($bericht,$id,$sid);
					if($stem !== false && $stem != -2) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Opdrachtgever $naam kiest $stem.\n");
					}
					else if($stem == -2) {
						schrijfLog($sid,
							"Opdrachtgever: speler was al gekozen.\n");
						stuurFoutStem2($naam,$adres,$sid);
					}
					else {
						schrijfLog($sid,
							"Opdrachtgever: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			default:
				//spel zit in een niet-wachtfase
				schrijfLog($sid,"Error: speler mag niet stemmen.\n");
				houJeMond($naam,$adres,$sid);
				break;
		}//switch
	}//if
	else {
		switch($fase) {
			case 1:
				if($rol == "Grafrover") {
					$stem = geldigeStem($bericht,$sid,0);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Grafrover $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,
							"Grafrover: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 4:
				if($rol == "Klaas Vaak") {
					$stem = geldigeStemZelfHerhaling($bericht,$sid,1,
						$id,$speler['VORIGE_STEM']);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Klaas Vaak $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,
							"Klaas Vaak: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 6:
				if(($rol == "Psychopaat") || ($rol == "Ziener") || 
					($rol == "Priester") || ($rol == "Dwaas")) {
						$stem = geldigeStemUitzondering($bericht,$sid,1,$id);
						if($stem !== false) {
							zetStem($id,$stem,$sid,"STEM");
							stuurStem($naam,$adres,$stem,$sid);
							schrijfLog($sid,"$rol $naam kiest $stem.\n");
						}
						else {
							schrijfLog($sid,"$rol: geen goede stem gevonden.\n");
							stuurFoutStem($naam,$adres,$sid);
						}
					}//if
				else if($rol == "Verleidster" || $rol == "Slet") {
					$stem = geldigeStemZelfHerhaling($bericht,$sid,1,
						$id,$speler['VORIGE_STEM']);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"$rol $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"$rol: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Weerwolf") {
					$stem = geldigeStemWWVP($bericht,$sid,"Weerwolf");
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Weerwolf $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Weerwolf: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Vampier") {
					$stem = geldigeStemWWVP($bericht,$sid,"Vampier");
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Vampier $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Vampier: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Witte Weerwolf") {
					//kijk of het de WW-stem is, of de WitteWW-stem
					if($tweede && (preg_match("/\bwitte\b/i",$onderwerp) || 
						preg_match("/\bwitte\b/i",$bericht))) {
						$stem = geldigeStemUitzondering($bericht,$sid,1,$id);
						if($stem !== false) {
							zetStem($id,$stem,$sid,"EXTRA_STEM");
							stuurStem($naam,$adres,$stem,$sid);
							schrijfLog($sid,"Witte WW $naam kiest $stem.\n");
						}
						else {
							schrijfLog($sid,
								"Witte Weerwolf: geen goede stem gevonden.\n");
							stuurFoutStem($naam,$adres,$sid);
						}
					}//if
					else { // anders is het de WW stem
						$stem = geldigeStemWWVP($bericht,$sid,"Weerwolf");
						if($stem !== false) {
							zetStem($id,$stem,$sid,"STEM");
							stuurStem($naam,$adres,$stem,$sid);
							schrijfLog($sid,
								"Witte WW $naam kiest $stem (Weerwolf).\n");
						}
						else {
							schrijfLog($sid,"Witte Weerwolf: " . 
								"geen goede stem gevonden (Weerwolf).\n");
							stuurFoutStem($naam,$adres,$sid);
						}
					}//else
				}//else if
				else if($rol == "Goochelaar") {
					geldigeStemUniek2($bericht,$id,$sid,
						$stem,$stem2,"Goochelaar");
					if(($stem !== false && 
						$stem2 !== false && $stem != $stem2) &&
						($stem != $speler['VORIGE_STEM'] || 
						$stem2 != $speler['VORIGE_STEM_EXTRA']) &&
						($stem != $speler['VORIGE_STEM_EXTRA'] ||
						$stem2 != $speler['VORIGE_STEM'])) {
						zetStem($id,$stem,$sid,"STEM");
						zetStem($id,$stem2,$sid,"EXTRA_STEM");
						stuurStem2($naam,$adres,$stem,$stem2,$sid);
						schrijfLog($sid,"$naam wil $stem en $stem2 " .
							"verwisselen.\n");
						}
					else if ($stem == -1) { //blanco
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Goochelaar $naam kiest -1.\n");
					}
					else if($stem == -2) {
						schrijfLog($sid,
							"Goochelaar: speler was al gekozen.\n");
						stuurFoutStem2($naam,$adres,$sid);
					}
					else {
						schrijfLog($sid,
							"Goochelaar: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 8:
				if($rol == "Heks") { 
					$resultaat = sqlSel(3,"ID=$id");
					$heks = sqlFet($resultaat);
					$drank = $heks['SPELFLAGS'];
					$flag = 0; //houdt de keuze bij: voor mailen
					$stem = geldigeStemHeks($bericht,$sid,0,$id);
					$stem2 = geldigeStemUitzondering($bericht,$sid,1,$id);
					if($stem !== false && 
						($stem == -1 || (($drank & 128) == 128))) {
						zetStem($id,$stem,$sid,"STEM");
						$flag += 1;
						schrijfLog($sid,"Heks $naam wil $stem redden.\n");
					}
					else{
						zetStemNULL($id,$sid,"STEM");
						schrijfLog($sid,"Heks $naam wil niemand redden.\n");
					}
					if($stem2 !== false && $stem2 != $id && 
						($stem2 == -1 || (($drank & 256) == 256))) {
						zetStem($id,$stem2,$sid,"EXTRA_STEM");
						$flag += 2;
						schrijfLog($sid,
							"Heks $naam wil $stem2 vergiftigen.\n");
					}
					else{
						zetStemNULL($id,$sid,"EXTRA_STEM");
						schrijfLog($sid,
							"Heks $naam wil niemand vergiftigen.\n");
					}
					if(($stem === false || 
						($drank & 128 != 128)) && 
						($stem2 === false || $stem2 == $id || 
						($drank & 256 != 256))) {
						schrijfLog($sid,"Heks: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
					else {
						stuurStemHeks($naam,$adres,$stem,$stem2,$flag,$sid);
					}
				}//if
				else if($rol == "Fluitspeler") { 
					geldigeStemFS($bericht,$sid,$stem,$stem2);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						if($stem2 !== false) {
							zetStem($id,$stem2,$sid,"EXTRA_STEM");
							stuurStem2($naam,$adres,$stem,$stem2,$sid);
							schrijfLog($sid,
								"Fluitspeler $naam kiest $stem en $stem2.\n");
						}
						else {
							zetStemNULL($id,$sid,"EXTRA_STEM");
							stuurStem($naam,$adres,$stem,$sid);
							schrijfLog($sid,"Fluitspeler $naam kiest $stem.\n");
						}
					}//if
					else {
						schrijfLog($sid,
							"Fluitspeler: geen geldige stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 11:
				if(isDodeBurg($speler,$spel) && 
					(($rol != "Raaf" && $rol != "Schout" && 
					$rol != "Waarschuwer" && $rol != "Jager") || 
					preg_match("/burgemeester/i",$onderwerp) || 
					preg_match("/burgemeester/i",$bericht))) {
					$stem = geldigeStemHeks($bericht,$sid,1,$id);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Burgemeester $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,
							"Burgemeester: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Raaf") {
					$stem = geldigeStemRaaf($bericht,$sid,1);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Raaf $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Raaf: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Schout") {
					$stem = geldigeStemHerhaling($bericht,$sid,1,
						$speler['VORIGE_STEM']);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Schout $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Schout: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Waarschuwer") { //mag niet op zichzelf stemmen
					$stem = geldigeStemUitzondering($bericht,$sid,1,$id);
					if($stem !== false && $stem != $id) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Waarschuwer $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,
							"Waarschuwer: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else if($rol == "Jager" && (($speler['LEVEND'] & 2) == 2)) {
					$stem = geldigeStemUitzondering($bericht,$sid,1,$id);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"EXTRA_STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Jager $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Jager: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			case 13: //burgemeesterverkiezing
				$stem = geldigeStem($bericht,$sid,1);
				if($stem !== false) {
					zetStem($id,$stem,$sid,"STEM");
					stuurStem($naam,$adres,$stem,$sid);
					schrijfLog($sid,"$naam stemt op $stem (Burgemeester).\n");
				}
				else {
					schrijfLog($sid,"Burgemeester: geen goede stem gevonden.\n");
					stuurFoutStem($naam,$adres,$sid);
				}
				break;
			case 15:
				$stem = geldigeStemBrand($id,$bericht,$sid);
				if($stem !== false) {
					zetStem($id,$stem,$sid,"STEM");
					stuurStem($naam,$adres,$stem,$sid);
					schrijfLog($sid,"$naam stemt op $stem (Brandstapel).\n");
				}
				else {
					schrijfLog($sid,"Brandstapel: geen goede stem gevonden.\n");
					stuurFoutStem($naam,$adres,$sid);
				}
				break;
			case 18:
				if($rol == "Jager" && (($speler['LEVEND'] & 2) == 2)) {
					$stem = geldigeStemUitzondering($bericht,$sid,1,$id);
					if($stem !== false) {
						zetStem($id,$stem,$sid,"STEM");
						stuurStem($naam,$adres,$stem,$sid);
						schrijfLog($sid,"Jager $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,"Jager: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//if
				else if($rol == "Zondebok" && ($spelflags & 256) == 256) {
					$stem = geldigeStemZonde($bericht,$id,$sid);
					if($stem !== false) {
						$stem = "'$stem'";
						zetStem($id,$stem,$sid,"SPECIALE_STEM");
						schrijfLog($sid,"Zondebok $naam kiest $stem.\n");
					}
					else {
						schrijfLog($sid,
							"Zondebok: geen goede stem gevonden.\n");
						stuurFoutStem($naam,$adres,$sid);
					}
				}//else if
				else {
					schrijfLog($sid,"Error: speler mag niet stemmen.\n");
					houJeMond($naam,$adres,$sid);
				}
				break;
			default:
				//spel zit in een niet-wachtfase
				schrijfLog($sid,"Error: speler mag niet stemmen.\n");
				houJeMond($naam,$adres,$sid);
				break;

		}//switch
	}//else
	return;
}//parseStem

?>
