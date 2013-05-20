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

function adminStop($text,$adres) {
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel("Spellen",NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		if(!in_array($sid,$spellen)) {
			continue;
		}
		$vlag = true;
		sqlUp("Spellen","GEWONNEN=1,FASE=99","SID='$sid'");
		echo "Spel gestopt: $sid.\n";
		stuurStop($sid);
		$onderwerp = "Spel gestopt: $sid";
		$bericht = "Het spel $sid is met succes gestopt, ";
		$bericht .= "en alle spelers zijn hiervan op de hoogte gesteld.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
		$onderwerp = "Spel stoppen mislukt";
		$bericht = "Er is geen geldige SID gevonden in jouw bericht; ";
		$bericht .= "er is geen enkel spel gestopt.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminStop

function adminDelete($text,$adres) {
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel("Spellen",NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		if(!in_array($sid,$spellen)) {
			continue;
		}
		$vlag = true;
		if($spel['GEWONNEN'] == false) {
			echo "Spel is nog bezig, kan niet worden verwijderd: $sid.\n";
			$onderwerp = "Verwijdering mislukt: $sid";
			$bericht = "Spel $sid is nog bezig ";
			$bericht .= "en kan niet worden verwijderd.";
			$bericht .= "Om het toch te verwijderen, ";
			$bericht .= "moet het eerst stopgezet worden.";
			stuurMail($adres,$onderwerp,$bericht);
			continue;
		}
		$sql = "DELETE FROM Spellen WHERE SID='$sid'";
		sqlQuery($sql);
		echo "Spel verwijderd: $sid.\n";
		$onderwerp = "Spel verwijderd: $sid";
		$bericht = "Spel $sid is met succes van de database verwijderd.";
		stuurMail($adres,$onderwerp,$bericht);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
		$onderwerp = "Spel verwijderen mislukt";
		$bericht = "Er is geen geldige SID gevonden in jouw bericht; ";
		$bericht .= "er is geen enkel spel verwijderd.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminDelete

function adminStart($text,$adres) {
	global $thuis;

	$details = explode("\r\n",$text);
	$sid = $details[0];
	$max = intval($details[1]);
	$snel = intval($details[2]);
	$streng = intval($details[3]);
	$thema = $details[4];
	if(empty($sid) || !is_string($sid)) {
		echo "Geen SID gevonden: fout\n";
		$onderwerp = "Spel aanmaken mislukt";
		$bericht = "Om een spel aan te maken moet een SID gegeven worden, ";
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
	if(empty($thema) || !is_string($thema)) {
		$thema = "default";
	}
	$sql = "INSERT INTO Spellen(SID,MAX_SPELERS,SNELHEID,STRENGHEID,THEMA) ";
	$sql .= "VALUES ('$sid',$max,$snel,$streng,'$thema')";
	sqlQuery($sql);
	echo "Spel gemaakt: $sid.\n";
	$onderwerp = "Spel aangemaakt: $sid";
	$bericht = "Spel $sid is met succes aangemaakt:<br />";
	$bericht .= "<br />";
	$bericht .= "Maximaal aantal spelers = $max<br />";
	$bericht .= "Snelheid = $snel<br />";
	$bericht .= "Strengheid = $streng<br />";
	$bericht .= "Thema = $thema<br />";
	stuurMail($adres,$onderwerp,$bericht);

	for($i = 0; $i < 5; $i++) {
		$details = delArrayElement($details,0);
	}
	$deadline = geefDeadline($sid);
	$onderwerp = "Uitnodiging: $sid";
	$bericht = "Een nieuw spel Weerwolven over de Mail is aangemaakt; ";
	$bericht .= "het zal beginnen op $deadline. ";
	$bericht .= "Als je wilt meedoen met het spel, schrijf je dan in ";
	$bericht .= "door een email naar $thuis te sturen, ";
	$bericht .= "met daarin je naam en je geslacht, ";
	$bericht .= "gescheiden door een komma.<br />";
	$bericht .= "<br />";
	$bericht .= "<u>Speldetails:</u><br />";
	$bericht .= "Spelnaam: $sid<br />";
	$bericht .= "Maximaal aantal spelers: $max<br />";
	$bericht .= "Duur van een stemronde: $snel ";
	$bericht .= ($snel == 1) ? "dag" : "dagen";
	$bericht .= "<br />";
	$bericht .= "Toegestane inactiviteit: ";
	$bericht .= "minder dan $streng stemmingen <br />";
	$bericht .= "Thema van het spel: $thema";
	foreach($details as $email) {
		if(!empty($email)) {
			stuurMail($email,$onderwerp,$bericht);
			echo "Uitgenodigd: $email.\n";
		}
	}

	return;
}//adminStart

function adminPlayers($bericht,$adres) {
	$spellen = explode("\r\n",$text);
	$resultaat = sqlSel("Spellen",NULL);
	$vlag = false;
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		if(!in_array($sid,$spellen)) {
			continue;
		}
		$vlag = true;
		$sql = "SELECT NAAM,GESLACHT,EMAIL,TO_MAIL,LEVEND ";
		$sql .= "FROM Spelers WHERE SPEL='$sid'";
		$resultaat = sqlQuery($sql);
		if(sqlNum($resultaat) == 0) {
			echo "Geen spelers in spel $sid.\n";
			$onderwerp = "Spelers zoeken mislukt";
			$bericht = "Spel $sid heeft geen spelers; ";
			$bericht .= "zo zijn er geen spelers gevonden.";
			stuurMail($adres,$onderwerp,$bericht);
			continue;
		}
		echo "Spelers van spel $sid verzonden.\n";
		stuurResultaatHTML($adres,$resultaat);
	}//while
	if(!$vlag) {
		echo "Geen spel gevonden.\n";
		$onderwerp = "Spelers zoeken mislukt";
		$bericht = "Er is geen geldige SID gevonden in jouw bericht; ";
		$bericht .= "geen enkele speler is gevonden.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//adminPlayers

function adminNoMail($bericht,$adres) {
	$vlag = false;
	$resultaat = sqlSel("Spellen",NULL);
	while($spel = sqlFet($resultaat)) {
		$sid = $spel['SID'];
		if(!strstr($bericht,$sid)) {
			continue;
		}
		$resultaat2 = sqlSel("Spelers","SPEL='$sid' AND LEVEND=0");
		while($speler = sqlFet($resultaat)) {
			$naam = $speler['NAAM'];
			if(!strstr($bericht,$naam)) {
				continue;
			}
			$vlag = true;
			sqlUp("Spelers","TO_MAIL=0","SPEL='$sid' AND NAAM='$naam'");
			echo "$sid: $naam ontvangt geen mails meer.\n";
			$onderwerp = "$naam: uit maillijst";
			$bericht = "$naam in spel $sid is uit de maillijst gehaald, ";
			$bericht .= "en zal geen mails van dit spel meer ontvangen.";
			stuurMail($adres,$onderwerp,$bericht);
		}//while
		echo "Geen speler in spel $sid gevonden.\n";
	}//while
	if(!$vlag) {
		echo "Geen speler/spel-combinatie gevonden.\n";
		$onderwerp = "Verwijderen uit maillijst mislukt";
		$bericht = "Er is geen speler uit de maillijst gehaald. ";
		$bericht .= "Zijn de SID en speler-naam goed gespeld, ";
		$bericht .= "en is de speler dood?";
		stuurMail($adres,$onderwerp,$bericht);
	}
}//adminNoMail

function adminGames($adres) {
	$resultaat = sqlSel("Spellen",NULL);
	if(sqlNum($resultaat) == 0) {
		echo "Geen spellen aanwezig.\n";
		$onderwerp = "Spellen zoeken mislukt";
		$bericht = "Er zijn geen spellen in de database.";
		stuurMail($adres,$onderwerp,$bericht);
	}
	echo "Spellen verzonden.\n";
	stuurResultaatHTML($adres,$resultaat);
	return;
}//adminGames

function adminStory($bericht,$adres) {
	var_dump($bericht);
	$stukken = explode("\r\n\r\n\r\n",$bericht);
	var_dump($stukken);
	$header = explode("\r\n\r\n",$stukken[0]);
	$ver_dump($header);
	$auteur = sqlEscape($header[0]);
	$thema = sqlEscape($header[1]);
	delArrayElement($stukken,0);
	echo "Auteur: $auteur, en thema: $thema.\n";
	foreach($stukken as $stuk) {
		$onderdelen = explode("\r\n\r\n",$stuk);
		var_dump($onderdelen);
		$rol = sqlEscape($onderdelen[0]);
		$fase = intval($onderdelen[1]);
		$levend = intval($onderdelen[2]);
		$dood = intval($onderdelen[3]);
		$verhaal = sqlEscape($onderdelen[4]);
		$geslacht = sqlEscape($onderdelen[5]);
		if(empty($rol) || empty($fase) || empty($verhaal)) {
			continue;
		}
		if(empty($levend)) {
			$levend = "NULL";
		}
		if(empty($dood)) {
			$dood = "NULL";
		}
		if(empty($geslacht)) {
			$geslacht = "NULL";
		}
		$sql = "INSERT INTO Verhalen(THEMA,AUTEUR,LEVEND,DOOD,ROL,FASE,";
		$sql .= "VERHAAL,GESLACHT) VALUES ('$thema','$auteur',$levend,$dood,";
		$sql .= "'$rol',$fase,'$verhaal','$geslacht')";
		sqlQuery($sql);
		echo "Rol: $rol, fase: $fase, levend: $levend en dood: $dood.\n";
	}//foreach
	//TODO stuur alle gevonden verhaaltjes (rol en fase), en auteur en thema
	return;
}//adminStory

//hulp aangevraagd: $onderwerp is het originele onderwerp, 
//en $bericht het originele bericht
function help($afzender,$onderwerp,$bericht) {
	global $admins;

	$bericht1 = base64_decode($bericht);
	$subject = "Hulp gevraagd";
	$message = "Hulp gevraagd door $afzender:<br />";
	$message .= "Onderwerp: $onderwerp <br />";
	$message .= "<br />";
	$message .= $bericht1;
	$alleAdmins = $admins[0];
	for($i = 1; $i < count($admins); $i++) {
		$alleAdmins .= ", $admins[$i]";
	}
	stuurMail($alleAdmins,$subject,$message);
	echo "Onderwerp: $onderwerp\n";
	echo "$bericht1\n";
	return;
}//help

?>
