<?php

//configuratie voor admins
function config($adres,$onderwerp,$bericht) {
	global $admins;

	$adminPass = "wwwins\r\n";
	if(strstr($bericht,$adminPass) == false) {
		echo "Wachtwoord fout, commando niet uitgevoerd.\n";
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
					stuurResultaatHTML($adres,$resultaat);
					echo "Resultaat van query gestuurd.\n";
				}
				else {
					//TODO mail de admin geen resultaten
					echo "Query gaf geen resultaten.\n";
				}
			}
		else {
			if(preg_match("/\bdrop\b/i",$query)) {
				//TODO mail admin dat dit niet mag
				echo "Tabellen verwijderen mag niet.\n";
				return;
			}
			sqlQuery($query);
			//TODO mail admin dat het is gedaan
			echo "Query uitgevoerd.\n";
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
		echo "Spel gepauzeerd: $snaam.\n";
		stuurPauze($sid);
		$onderwerp = "Spel gepauzeerd: $snaam";
		$bericht = "Het spel $snaam is met succes gepauzeerd, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
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
		echo "Spel hervat: $snaam.\n";
		stuurHervat($sid);
		$onderwerp = "Spel hervat: $snaam";
		$bericht = "Het spel $snaam is met succes hervat, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
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
		echo "Spel gestopt: $snaam.\n";
		stuurStop($sid);
		$onderwerp = "Spel gestopt: $snaam";
		$bericht = "Het spel $snaam is met succes gestopt, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
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
			echo "Spel is nog bezig, kan niet worden verwijderd: $snaam.\n";
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
		echo "Spel verwijderd: $snaam.\n";
		$onderwerp = "Spel verwijderd: $snaam";
		$bericht = "Spel $snaam is met succes van de database verwijderd.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
		$onderwerp = "Spel verwijderen mislukt";
		$bericht = "Er is geen geldige spelnaam gevonden in jouw bericht; ";
		$bericht .= "er is geen enkel spel verwijderd.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminDelete

function adminStart($text,$adres) {
	global $thuis,$tabellen;

	$tabel = $tabellen[4];
	$details = explode("\r\n",$text);
	$snaam = $details[0];
	$max = intval($details[1]);
	$snel = intval($details[2]);
	$streng = intval($details[3]);
	$tnaam = $details[4];
	if(empty($snaam) || !is_string($snaam)) {
		echo "Geen spelnaam gevonden: fout\n";
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
		echo "Standaard spel gevraagd.\n";
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
	$vlag = false;
	while(sqlNum($resultaat) == 0) {
		if($vlag) {
			echo "Default-thema bestaat niet.\n";
			//TODO stuur error and such
			return;
		}
		$vlag = true;
		echo "Opgegeven thema bestaat niet: default genomen\n";
		$resultaat = sqlSel(5,"TNAAM='default'");
	}
	$thema = sqlFet($resultaat);
	$tid = $thema['TID'];
	$sql = "INSERT INTO $tabel(SNAAM,MAX_SPELERS,SNELHEID,STRENGHEID,THEMA) ";
	$sql .= "VALUES ('$snaam',$max,$snel,$streng,$tid)";
	sqlQuery($sql);
	echo "Spel gemaakt: $snaam.\n";
	$onderwerp = "Spel aangemaakt: $snaam";
	$bericht = "Spel $snaam is met succes aangemaakt:<br />";
	$bericht .= "<br />";
	$bericht .= "Maximaal aantal spelers = $max<br />";
	$bericht .= "Snelheid = $snel<br />";
	$bericht .= "Strengheid = $streng<br />";
	$bericht .= "Thema = $tnaam<br />";
	stuurMail($adres,$onderwerp,$bericht);

	for($i = 0; $i < 5; $i++) {
		$details = delArrayElement($details,0);
	}
	$resultaat = sqlSel(4,"SNAAM='$snaam'");
	$spel = sqlFet($resultaat);
	$sid = $spel['SID'];
	$deadline = geefDeadline($sid);
	$onderwerp = "Uitnodiging: $snaam";
	$bericht = "Een nieuw spel Weerwolven over de Mail is aangemaakt; ";
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
	$bericht .= "minder dan $streng stemmingen <br />";
	$bericht .= "Thema van het spel: $tnaam";
	foreach($details as $email) {
		if(!empty($email)) {
			stuurMail($email,$onderwerp,$bericht);
			echo "Uitgenodigd: $email.\n";
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
			echo "Geen spelers in spel $snaam.\n";
			$onderwerp = "Spelers zoeken mislukt";
			$bericht = "Spel $snaam heeft geen spelers; ";
			$bericht .= "zo zijn er geen spelers gevonden.";
			stuurMail($adres,$onderwerp,$bericht);
			continue;
		}
		echo "Spelers van spel $snaam verzonden.\n";
		stuurResultaatHTML($adres,$resultaat);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
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
				echo "$snaam: $naam ontving al geen mails meer";
				$onderwerp = "$naam: uit maillijst";
				$bericht = "$naam in spel $snaam was al uit de maillijst ";
				$bericht .= "gehaalt.";
				stuurMail($adres,$onderwerp,$bericht);
				continue;
			}
			$spelerflag -= 2;
			sqlUp(3,"SPELERFLAGS=$spelerflag","ID=$id");
			echo "$snaam: $naam ontvangt geen mails meer.\n";
			$onderwerp = "$naam: uit maillijst";
			$bericht = "$naam in spel $snaam is uit de maillijst gehaald, ";
			$bericht .= "en zal geen mails van dit spel meer ontvangen.";
			stuurMail($adres,$onderwerp,$bericht);
		}//while
		echo "Geen speler in spel $snaam gevonden.\n";
	}//while
	if(!$vlag) {
		echo "Geen speler/spel-combinatie gevonden.\n";
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
		echo "Geen spellen aanwezig.\n";
		$onderwerp = "Spellen zoeken mislukt";
		$bericht = "Er zijn geen spellen in de database.";
		stuurMail($adres,$onderwerp,$bericht);
		return;
	}
	echo "Spellen verzonden.\n";
	stuurResultaatHTML($adres,$resultaat);
	return;
}//adminGames

function adminStory($bericht,$adres) {//TODO thema's uitvogelen; 
								//     werkt nu met foreign keys
	global $tabellen;
	$tabel = $tabellen[6];
	$stukken = explode("\r\n\r\n\r\n",$bericht);
	$header = explode("\r\n\r\n",$stukken[0]);
	$auteur = sqlEscape($header[0]);
	$thema = sqlEscape($header[1]);
	delArrayElement($stukken,0);
	echo "Auteur: $auteur, en thema: $thema.\n";
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
			echo "Fout: rol, fase of verhaal ontbreekt.\n";
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
	//TODO stuur alle gevonden verhaaltjes (rol en fase), en auteur en thema
	return;
}//adminStory

function adminHelp($bericht,$onderwerp,$adres) {
	$nummers = array();
	if(!preg_match_all('!\d+!',$onderwerp,$nummers)) {
		echo "Geen HID gevonden.\n";
		$onderwerp = "Help antwoorden mislukt";
		$bericht = "Er was geen HID gevonden in het onderwerp.";
		stuurMail($adres,$onderwerp,$bericht);
		return;
	}
	$hid = implode('', $nummers[0]);
	echo "$hid beantwoord.\n";
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
		echo "Anonieme hulp gevraagd.\n";
		$message = "Anonieme hulp gevraagd.<br />";
	}
	else {
		echo "Hulp gevraagd door $afzender.\n";
		$message = "Hulp gevraagd door $afzender:<br />";
	}
	$message .= "Onderwerp: $onderwerp <br />";
	$message .= "HID: $hid <br />";
	$message .= "<br />";
	$message .= $bericht;
	$alleAdmins = $admins[0];
	for($i = 1; $i < count($admins); $i++) {
		$alleAdmins .= ", $admins[$i]";
	}
	echo "$onderwerp\n";
	echo "$message\n";
	echo "$hid\n";
	stuurMail($alleAdmins,$subject,$message);
	return;
}//help

?>
