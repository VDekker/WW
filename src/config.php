<?php

//configuratie voor admins
function config($adres,$onderwerp,$bericht) {
	global $admins;

	$adminPass = adminPass() . "\r\n";
	if(strstr($bericht,$adminPass) == false) {
		schrijfLog(-1,"Wachtwoord fout, commando niet uitgevoerd.\n");
		return;
	}
	$bericht = str_replace($adminPass,"",$bericht);

	if(preg_match("/query/i",$onderwerp)) {
		adminQuery($bericht,$adres);
	}
	if(preg_match("/pause/i",$onderwerp)) {
		adminPause($bericht,$adres);
	}
	if(preg_match("/continue/i",$onderwerp)) {
		adminContinue($bericht,$adres);
	}
	if(preg_match("/stop/i",$onderwerp)) {
		adminStop($bericht,$adres);
	}
	if(preg_match("/delete/i",$onderwerp)) {
		adminDelete($bericht,$adres);
	}
	if(preg_match("/start/i",$onderwerp)) {
		adminStart($bericht,$adres);
	}
	if(preg_match("/players/i",$onderwerp)) {
		adminPlayers($bericht,$adres);
	}
	if(preg_match("/nomail/i",$onderwerp)) {
		adminNoMail($bericht,$adres);
	}
	if(preg_match("/games/i",$onderwerp)) {
		adminGames($adres);
	}
	if(preg_match("/story/i",$onderwerp)) {
		adminStory($bericht,$adres);
	}
	if(preg_match("/help/i",$onderwerp)) {
		adminHelp($bericht,$onderwerp,$adres);
	}
	return;
}//config

function adminQuery($text,$adres) {
	$sql = explode("\r\n",$text);
	foreach($sql as $query) {
		if(preg_match("/\bselect\b/i",$query) ||
			preg_match("/\bshow\b/i",$query) ||
			preg_match("/\bdescribe\b/i",$query) ||
			preg_match("/\bexplain\b/i",$query)) {
				$resultaat = sqlQuery($query);
				if(sqlNum($resultaat) > 0) {
					$bericht = "De query is met succes uitgevoerd.<br />";
					$bericht .= "Query: $query<br />";
					$bericht .= "<br />";
					stuurResultaatHTML($adres,$bericht,$resultaat);
					schrijfLog(-1,"Resultaat van query gestuurd.\n");
				}
				else {
					schrijfLog(-1,"Query gaf geen resultaten.\n");
					$onderwerp = "Query";
					$bericht = "De query gaf geen resultaten.<br />";
					$bericht .= "Query: $query<br />";
					stuurMail($adres,$onderwerp,$bericht);
					return;
				}
			}
		else {
			if(preg_match("/\bdrop\b/i",$query)) {
				schrijfLog(-1,"Tabellen verwijderen mag niet.\n");
				$onderwerp = "Foute query: 'DROP' mag niet";
				$bericht = "In jouw query stond het woord 'DROP'. ";
				$bericht .= "Tabellen mogen niet verwijderd worden; ";
				$bericht .= "dus jouw query is niet uitgevoerd.";
				stuurMail($adres,$onderwerp,$bericht);
				return;
			}
			sqlQuery($query);
			schrijfLog(-1,"Query uitgevoerd.\n");
			$onderwerp = "Query";
			$bericht = "De query is met succes uitgevoerd.<br />";
			$bericht .= "Query: $query<br />";
			stuurMail($adres,$onderwerp,$bericht);
			return;
		}
	}//foreach
	return;
}//adminQuery

function adminPause($text,$adres) {
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel(4,NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		$snaam = $spel['SNAAM'];
		if(!in_array($snaam,$spellen) || $spel['STATUS'] != 0) {
			continue;
		}
		$vlag = true;
		sqlUp(4,"STATUS=1","SID=$sid");
		schrijfLog(-1,"Spel gepauzeerd: $snaam.\n");
		stuurPauze($sid);
		$onderwerp = "Spel gepauzeerd: $snaam";
		$bericht = "Het spel $snaam is met succes gepauzeerd, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		schrijfLog(-1,"Geen spel gevonden.\n");
		$onderwerp = "Spel pauzeren mislukt";
		$bericht = "Er is geen geldige spelnaam van een lopend spel gevonden ";
		$bericht .= "in jouw bericht; er is geen enkel spel gepauzeerd.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminPause

function adminContinue($text,$adres) {
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel(4,NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		$snaam = $spel['SNAAM'];
		if(!in_array($snaam,$spellen) || $spel['STATUS'] != 1) {
			continue;
		}
		$vlag = true;
		$datum = date_create(date('Y-m-d'));
		$sqlDatum = date_format($datum, 'Y-m-d');
		sqlUp(4,"STATUS=0,DUUR='$sqlDatum'","SID=$sid");
		schrijfLog(-1,"Spel hervat: $snaam.\n");
		stuurHervat($sid);
		$onderwerp = "Spel hervat: $snaam";
		$bericht = "Het spel $snaam is met succes hervat, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		schrijfLog(-1,"Geen spel gevonden.\n");
		$onderwerp = "Spel hervatten mislukt";
		$bericht = "Er is geen geldige spelnaam van een gepauzeerd spel gevonden ";
		$bericht .= "in jouw bericht; er is geen enkel spel hervat.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminContinue

function adminStop($text,$adres) {
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel(4,NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		$snaam = $spel['SNAAM'];
		if(!in_array($snaam,$spellen)) {
			continue;
		}
		$vlag = true;
		sqlUp(4,"STATUS=2,FASE=99","SID=$sid");
		schrijfLog(-1,"Spel gestopt: $snaam.\n");
		stuurStop($sid);
		$onderwerp = "Spel gestopt: $snaam";
		$bericht = "Het spel $snaam is met succes gestopt, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		schrijfLog(-1,"Geen spel gevonden.\n");
		$onderwerp = "Spel stoppen mislukt";
		$bericht = "Er is geen geldige spelnaam gevonden in jouw bericht; ";
		$bericht .= "er is geen enkel spel gestopt.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminStop

function adminDelete($text,$adres) {
	global $tabellen;
	$tabel = $tabellen[4];
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel(4,NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		$snaam = $spel['SNAAM'];
		if(!in_array($snaam,$spellen)) {
			continue;
		}
		$vlag = true;
		if($spel['STATUS'] == 0 || $spel['STATUS'] == 1) {
			schrijfLog(-1,"Spel is nog bezig, kan niet worden verwijderd: $snaam.\n");
			$onderwerp = "Verwijdering mislukt: $snaam";
			$bericht = "Spel $snaam is nog bezig ";
			$bericht .= "en kan niet worden verwijderd.";
			$bericht .= "Om het toch te verwijderen, ";
			$bericht .= "moet het eerst stopgezet worden.";
			stuurMail($adres,$onderwerp,$bericht);
			continue;
		}
		$sql = "DELETE FROM $tabel WHERE SID=$sid";
		sqlQuery($sql);
		schrijfLog(-1,"Spel verwijderd: $snaam.\n");
		$onderwerp = "Spel verwijderd: $snaam";
		$bericht = "Spel $snaam is met succes van de database verwijderd.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		schrijfLog(-1,"Geen spel gevonden.\n");
		$onderwerp = "Spel verwijderen mislukt";
		$bericht = "Er is geen geldige spelnaam gevonden in jouw bericht; ";
		$bericht .= "er is geen enkel spel verwijderd.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminDelete

function adminStart($text,$adres) {
	global $thuis,$admins,$tabellen;

	$tabel = $tabellen[4];
	$details = explode("\r\n",$text);
	$snaam = $details[0];
	$max = intval($details[1]);
	$snel = intval($details[2]);
	$streng = intval($details[3]);
	$tnaam = $details[4];
	if(empty($snaam) || !is_string($snaam)) {
		schrijfLog(-1,"Geen spelnaam gevonden: fout\n");
		$onderwerp = "Spel aanmaken mislukt";
		$bericht = "Om een spel aan te maken moet een spelnaam gegeven worden, ";
		$bericht .= "en eventueel een MAX_SPELERS, SNELHEID, STRENGHEID ";
		$bericht .= "en THEMA (in deze volgorde, gescheiden door [enter]). ";
		$bericht .= "Probeer het nog eens.";
		stuurMail($adres,$onderwerp,$bericht);
		return;
	}
	if(empty($max) || !is_int($max)) {
		//standaard spel:
		schrijfLog(-1,"Standaard spel gevraagd.\n");
		$max = 18;
	}
	if(empty($snel) || !is_int($snel)) {
		$snel = 2;
	}
	if(empty($streng) || !is_int($streng)) {
		$streng = 2;
	}
	if(empty($tnaam) || !is_string($tnaam)) {
		$tnaam = "default";
	}
	$resultaat = sqlSel(5,"TNAAM='$tnaam'");
	if(sqlNum($resultaat) == 0) {
		schrijfLog(-1,"Opgegeven thema bestaat niet: default genomen\n");
		$tnaam = "default";
		$resultaat = sqlSel(5,"TNAAM='default'");
		if(sqlNum($resultaat) == 0) {
			$bericht = "Er bestaat geen default thema; ";
			$bericht .= "maak dit aan, anders loopt het hele systeem vast!";
			stuurError($bericht);
			return;
		}
	}//if
	$thema = sqlFet($resultaat);
	$tid = $thema['TID'];

	//pak huidige systeemdatum (voor DUUR)
	$duur = date('Y-m-d');
	echo "Datum: $duur.\n";
	
	//upload nieuw spel
	$sql = "INSERT INTO $tabel(SNAAM,MAX_SPELERS,SNELHEID,";
	$sql .= "STRENGHEID,THEMA,DUUR) ";
	$sql .= "VALUES ('$snaam',$max,$snel,$streng,$tid,'$duur')";
	sqlQuery($sql);
	$id = sqlID();
	schrijfLog($id,"Spel gemaakt: $snaam.\n");

	//mail admin dat het geslaagd is
	$onderwerp = "Spel aangemaakt: $snaam";
	$bericht = "Spel $snaam is met succes aangemaakt:<br />";
	$bericht .= "<br />";
	$bericht .= "Maximaal aantal spelers = $max<br />";
	$bericht .= "Snelheid = $snel<br />";
	$bericht .= "Strengheid = $streng<br />";
	$bericht .= "Thema = $tnaam<br />";
	stuurMail($adres,$onderwerp,$bericht);

	//delete alle gebruikte variabelen (enkel bericht en adressen blijft over)
	for($i = 0; $i < 5; $i++) {
		$details = delArrayElement($details,0);
	}

	//maak uitnodiging
	$resultaat = sqlSel(4,"SNAAM='$snaam'");
	$spel = sqlFet($resultaat);
	$sid = $spel['SID'];
	$deadline = geefDeadline($sid);
	$onderwerp = "Uitnodiging: $snaam";

	$bericht = "";
	foreach($details as $string) {
		if(strpos($string,'@') === false) {
			$bericht .= $string;
		}
	}
	$bericht .= "<br /><br />";
	$bericht .= "Een nieuw spel Weerwolven over de Mail is aangemaakt; ";
	$bericht .= "het zal beginnen op $deadline. ";
	$bericht .= "Als je wilt meedoen met het spel, schrijf je dan in ";
	$bericht .= "door een email naar $thuis te sturen, ";
	$bericht .= "met daarin je naam en je geslacht, ";
	$bericht .= "gescheiden door een komma.<br />";
	$bericht .= "<br />";
	$bericht .= "<u>Speldetails:</u><br />";
	$bericht .= "Spelnaam: $snaam<br />";
	$bericht .= "Maximaal aantal spelers: $max<br />";
	$bericht .= "Duur van een stemronde: $snel ";
	$bericht .= ($snel == 1) ? "dag" : "dagen";
	$bericht .= "<br />";
	$bericht .= "Toegestane inactiviteit: ";
	$bericht .= "minder dan $streng ";
	$bericht .= ($streng == 1) ? "stemming" : "stemmingen";
	$bericht .= "<br />";
	$bericht .= "Thema van het spel: $tnaam";

	//mail de uitnodigingen
	foreach($details as $string) {
		if(strpos($string,'@') !== false) {
			stuurMail($string,$onderwerp,$bericht);
			schrijfLog($id,"Uitgenodigd: $string.\n");
		}
	}

	return;
}//adminStart

function adminPlayers($bericht,$adres) {
	global $tabellen;
	$tabel = $tabellen[3];
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel(4,NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		$snaam = $spel['SNAAM'];
		if(!in_array($snaam,$spellen)) {
			continue;
		}
		$vlag = true;
		$sql = "SELECT ID,NAAM,SPELERFLAGS,EMAIL,LEVEND ";
		$sql .= "FROM $tabel WHERE SID=$sid";
		$resultaat = sqlQuery($sql);
		if(sqlNum($resultaat) == 0) {
			schrijfLog(-1,"Geen spelers in spel $snaam.\n");
			$onderwerp = "Spelers zoeken mislukt";
			$bericht = "Spel $snaam heeft geen spelers; ";
			$bericht .= "zo zijn er geen spelers gevonden.";
			stuurMail($adres,$onderwerp,$bericht);
			continue;
		}
		schrijfLog(-1,"Spelers van spel $snaam verzonden.\n");
		$bericht .= "Alle spelers van spel $snaam:<br />";
		$bericht .= "<br />";
		stuurResultaatHTML($adres,$bericht,$resultaat);
	}//while
	if(!$vlag) {
		schrijfLog(-1,"Geen spel gevonden.\n");
		$onderwerp = "Spelers zoeken mislukt";
		$bericht = "Er is geen geldige spelnaam gevonden in jouw bericht; ";
		$bericht .= "geen enkele speler is gevonden.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminPlayers

function adminNoMail($bericht,$adres) {
	$vlag = false;
	$resultaat = sqlSel(4,NULL);
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		$snaam = $spel['SNAAM'];
		if(!strstr($bericht,$snaam)) {
			continue;
		}
		$resultaat2 = sqlSel(3,"SID=$sid AND LEVEND=0");
		while($speler = sqlFet($resultaat)) {
			$naam = $speler['NAAM'];
			if(!strstr($bericht,$naam)) {
				continue;
			}
			$vlag = true;
			$id = $speler['ID'];
			$resultaat = sqlSel(3,"ID=$id");
			$speler = sqlFet($resultaat);
			$spelerflag = $speler['SPELERFLAGS'];
			if(!($spelerflag & 2)) {
				schrijfLog(-1,"$snaam: $naam ontving al geen mails meer");
				$onderwerp = "$naam: uit maillijst";
				$bericht = "$naam in spel $snaam was al uit de maillijst ";
				$bericht .= "gehaalt.";
				stuurMail($adres,$onderwerp,$bericht);
				continue;
			}
			$spelerflag -= 2;
			sqlUp(3,"SPELERFLAGS=$spelerflag","ID=$id");
			schrijfLog(-1,"$snaam: $naam ontvangt geen mails meer.\n");
			$onderwerp = "$naam: uit maillijst";
			$bericht = "$naam in spel $snaam is uit de maillijst gehaald, ";
			$bericht .= "en zal geen mails van dit spel meer ontvangen.";
			stuurMail($adres,$onderwerp,$bericht);
		}//while
		schrijfLog(-1,"Geen speler in spel $snaam gevonden.\n");
	}//while
	if(!$vlag) {
		schrijfLog(-1,"Geen speler/spel-combinatie gevonden.\n");
		$onderwerp = "Verwijderen uit maillijst mislukt";
		$bericht = "Er is geen speler uit de maillijst gehaald. ";
		$bericht .= "Zijn de spelnaam en speler-naam goed gespeld, ";
		$bericht .= "en is de speler dood?";
		stuurMail($adres,$onderwerp,$bericht);
	}
}//adminNoMail

function adminGames($adres) {
	$resultaat = sqlSel(4,NULL);
	if(sqlNum($resultaat) == 0) {
		schrijfLog(-1,"Geen spellen aanwezig.\n");
		$onderwerp = "Spellen zoeken mislukt";
		$bericht = "Er zijn geen spellen in de database.";
		stuurMail($adres,$onderwerp,$bericht);
		return;
	}
	schrijfLog(-1,"Spellen verzonden.\n");
	$bericht = "Alle spellen van het systeem:<br />";
	$bericht .= "<br />";
	stuurResultaatHTML($adres,$bericht,$resultaat);
	return;
}//adminGames

//TODO thema's uitvogelen voor verhalen invoegen; werkt nu met foreign keys
//also: deze hele functie fixen
function adminStory($bericht,$adres) {
	global $tabellen;
	$tabel = $tabellen[6];
	$stukken = explode("\r\n\r\n\r\n",$bericht);
	$header = explode("\r\n\r\n",$stukken[0]);
	$auteur = sqlEscape($header[0]);
	$thema = sqlEscape($header[1]);
	delArrayElement($stukken,0);
	schrijfLog(-1,"Auteur: $auteur, en thema: $thema.\n");
	foreach($stukken as $key => $stuk) {
		if($key == 0) {
			continue;
		}
		$onderdelen = explode("\r\n\r\n",$stuk);
		$rol = sqlEscape($onderdelen[0]);
		$fase = (int)$onderdelen[1];
		$levend = (int)$onderdelen[2];
		$dood = (int)$onderdelen[3];
		$verhaal = sqlEscape($onderdelen[4]);
		$geslacht = ($onderdelen[5] == "NULL") ? 
			"NULL" : "'" . sqlEscape($onderdelen[5]) . "'";
		if(empty($rol) || ($fase == 0 && $onderdelen[1] != "0") || 
			empty($verhaal)) {
			schrijfLog(-1,"Fout: rol, fase of verhaal ontbreekt.\n");
			continue;
		}
		if(($levend == 0 && $onderdelen[2] != "0")) {
			$levend = "NULL";
		}
		if(($dood == 0 && $onderdelen[3] != "0")) {
			$dood = "NULL";
		}
		$sql = "INSERT INTO $tabel(THEMA,AUTEUR,LEVEND,DOOD,ROL,FASE,";
		$sql .= "VERHAAL,GESLACHT) VALUES ('$thema','$auteur',$levend,$dood,";
		$sql .= "'$rol',$fase,'$verhaal',$geslacht)";
		sqlQuery($sql);
	}//foreach
	return;
}//adminStory

function adminHelp($bericht,$onderwerp,$adres) {
	$nummers = array();
	if(!preg_match_all('!\d+!',$onderwerp,$nummers)) {
		schrijfLog(-1,"Geen HID gevonden.\n");
		$onderwerp = "Help antwoorden mislukt";
		$bericht = "Er was geen HID gevonden in het onderwerp.";
		stuurMail($adres,$onderwerp,$bericht);
		return;
	}
	$hid = implode('', $nummers[0]);
	schrijfLog(-1,"$hid beantwoord.\n");
	$resultaat = sqlSel(1,"HID=$hid");
	$help = sqlFet($resultaat);
	$ontvanger = $help['ADRES'];
	$onderwerp = "RE:Help";
	stuurMail($ontvanger,$onderwerp,$bericht);
	
	$onderwerp = "Help verzonden";
	$bericht = "De help-mail is verzonden: HID $hid is beantwoord.";
	stuurMail($adres,$onderwerp,$bericht);
	return;	
}//adminHelp

//hulp aangevraagd: $onderwerp is het originele onderwerp, 
//en $bericht het originele bericht
function help($afzender,$onderwerp,$bericht) {
	global $admins,$tabellen;
	$tabel = $tabellen[1];
	$sql = "INSERT INTO $tabel(ADRES) VALUES('$afzender')";
	sqlQuery($sql);
	$hid = sqlID();
	$subject = "Hulp gevraagd: $hid";
	if(preg_match("/anoniem/i",$onderwerp)) {
		schrijfLog(-1,"Anonieme hulp gevraagd.\n");
		$message = "Anonieme hulp gevraagd.<br />";
	}
	else {
		schrijfLog(-1,"Hulp gevraagd door $afzender.\n");
		$message = "Hulp gevraagd door $afzender:<br />";
	}
	$message .= "Onderwerp: $onderwerp <br />";
	$message .= "HID: $hid <br />";
	$message .= "<br />";
	$message .= $bericht;

	//pak de adressen van de admins
	$alleAdmins = $admins[0];
	for($i = 1; $i < count($admins); $i++) {
		$alleAdmins .= ", $admins[$i]";
	}
	stuurMail($alleAdmins,$subject,$message);
	return;
}//help

?>
