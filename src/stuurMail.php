<?php

function footnote() {
	global $thuis,$handleiding;

	$footnote = "<br />";
	$footnote .= "<br />";
	$footnote .= "<hr>";
	$footnote .= "<font size='1'>";
	$footnote .= "Automatische Verteller, gemaakt door Victor Dekker. ";
	$footnote .= "Gebaseerd op het spel <i>'De Weerwolven van Wakkerdam'</i> ";
	$footnote .= "van 999Games, en de uitbreidingen ";
	$footnote .= "<i>'Volle Maan in Wakkerdam'</i> en ";
	$footnote .= "<i>'Het Dorp'</i>.<br />";
	$footnote .= "Voor hulp bij het gebruik van de Automatische Verteller, ";
	$footnote .= "zie <a href=$handleiding>handleiding</a>, ";
	$footnote .= "of stuur een email met onderwerp 'Help' naar $thuis: ";
	$footnote .= "deze mail zal zo snel mogelijk worden beantwoord.";
	$footnote .= "</font>";
	return $footnote;
}//footnote

//stuurt een mail en slaat deze ook op in de tabel Mails
function stuurMail($adres,$onderwerp,$bericht) {
	global $thuis;
	$from = "From: $thuis";
	$headers = "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
	$headers .= $from;
	$message = "
		<html>
		<head>
		  <title>$onderwerp</title>
		</head>
		<body>";
	$message .= $bericht;
	$bericht .= footnote();
	$bericht .= "
		</body>
		</html>";
	
	mail($adres,$onderwerp,$bericht,$headers);
	$text = sqlEscape($bericht);
	$sql = "INSERT INTO Mails(ADRES,ONDERWERP,BERICHT,HEADERS)
		VALUES ('$adres','$onderwerp','$text','$headers')";
	sqlQuery($sql);
	return;
}//stuurMail

function stuurControle() {
	global $thuis;
	$onderwerp = "Controle";
	$bericht = "Controle.";
	$headers = "From: $thuis";
	mail($thuis,$onderwerp,$bericht,$headers);
}//stuurControle

//stuurt een error naar de systeembeheerder
function stuurError($error) {
	global $admins,$thuis;
	$onderwerp = "Error";
	$alleAdmins = $admins[0];
	for($i = 1; $i < count($admins); $i++) {
		$alleAdmins .= ", $admins[$i]";
	}
	stuurMail($alleAdmins,$onderwerp,$error);
	die($error);
}//stuurError

function stuurResultaatHTML($adres,$resultaat) {
	global $thuis,$footnote;
	$onderwerp = "Query";
	
	$bericht = "
		<table border='1'>
		    <tr>";
	$tuple = sqlFet($resultaat);
	foreach($tuple as $key => $value) {
		if(is_int($key)) {
			continue;
		}//if
		$bericht .= "<th>";
		$bericht .= htmlspecialchars($key);
		$bericht .= "</th>";
	}//foreach
	$bericht .= "</tr>";
	sqlData($resultaat,0);
	while($tuple = sqlFet($resultaat)) {
		$bericht .= "<tr>";
		foreach($tuple as $key => $value) {
			if(is_int($key)) {
				continue;
			}//if
			$bericht .= "<th>";
			$bericht .= htmlspecialchars($value);
			$bericht .= "</th>";
		}//foreach
		$bericht .= "</tr>";
	}//while
	$bericht .= "</table>";
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurResultaatHTML

function stuurStop($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid");
	$adressen = array();
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($adressen)) {
		echo "Niemand om te mailen.\n";
		return;
	}

	$onderwerp = "$snaam: Spel gestopt";
	$bericht .= "Helaas is het mailspel $snaam door het systeembeheer gestopt. ";
	$bericht .= "Dit spel zal niet meer doorgaan, ";
	$bericht .= "en hierover worden geen automatische mails meer verzonden. ";
	$bericht .= "Emails over dit spel zullen niet worden geparsed ";
	$bericht .= "door het systeem.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";
	foreach($adressen as $adres) {
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//stuurStop

function stuurPauze($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid");
	$adressen = array();
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($adressen)) {
		echo "Niemand om te mailen.\n";
		return;
	}

	$onderwerp = "$snaam: Spel gepauzeerd";
	$bericht .= "Het mailspel $snaam is door het systeembeheer gepauzeerd. ";
	$bericht .= "Als het spel wordt hervat, zal je hiervan worden bericht, ";
	$bericht .= "maar tot die tijd worden hierover geen automatische mails ";
	$bericht .= "meer verzonden. ";
	$bericht .= "Emails over dit spel zullen niet worden geparsed ";
	$bericht .= "door het systeem.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";
	foreach($adressen as $adres) {
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//stuurPauze

function stuurHervat($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid");
	$adressen = array();
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($adressen)) {
		echo "Niemand om te mailen.\n";
		return;
	}

	$onderwerp = "$snaam: Spel hervat";
	$bericht .= "Het mailspel $snaam gaat weer door. ";
	$bericht .= "Vanaf nu begint het weer waar het was gebleven. ";
	$bericht .= "Mails over dit spel worden weer geparsed.";
	$bericht .= "<br /><br />";
	$bericht .= "Nog veel speelplezier!";
	foreach($adressen as $adres) {
		stuurMail($adres,$onderwerp,$bericht);
	}
	return;
}//stuurHervat

function stuurFoutStop($adres,$snaam) {
	$onderwerp = "$snaam: Mail niet gelezen";
	$bericht .= "Het mailspel $snaam is geeindigd. ";
	$bericht .= "Jouw mailtje is dus niet gelezen, ";
	$bericht .= "en wordt verder genegeerd.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurFoutStop

function stuurFoutPauze($adres,$snaam) {
	$onderwerp = "$snaam: Mail niet gelezen";
	$bericht .= "Het mailspel $snaam is gepauzeerd. ";
	$bericht .= "Jouw mailtje is dus niet gelezen, ";
	$bericht .= "en wordt verder genegeerd, ";
	$bericht .= "en hier wordt ook niets mee gedaan ";
	$bericht .= "als het spel wordt hervat.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurFoutPauze

function stuurInschrijving($adres,$sid) {
	global $thuis;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND EMAIL='$adres'");
	$speler = sqlFet($resultaat);
	$naam = $speler['NAAM'];
	$geslacht = ($speler['SPELERFLAGS'] & 1) ? "Man" : "Vrouw";
	$adres2 = htmlentities($adres);
	$onderwerp = "Inschrijving: $snaam";
	
	$bericht = "De inschrijving is ontvangen. ";
	$bericht .= "Controleer aub de volgende gegevens: <br />";
	$bericht .= "Spel: $snaam <br />";
	$bericht .= "Naam: $naam <br />";
	$bericht .= "Geslacht: $geslacht <br />";
	$bericht .= "Email-adres: $adres2 <br />";
	$bericht .= "<br />";
	$bericht .= "Mochten deze gegevens niet kloppen, ";
	$bericht .= "probeer je dan opnieuw in te schrijven	";
	$bericht .= "door een bericht te sturen naar: ";
	$bericht .= "$thuis. Zet in het onderwerp de naam van het spel, ";
	$bericht .= "en in het bericht je eigen naam, gevolgd door een komma, ";
	$bericht .= "en vervolgens of je een man (m) of vrouw (v) bent.";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurInschrijving

function stuurInschrijvingFout($adres,$sid) {
	global $thuis;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Inschrijving mislukt";
	
	$bericht = "Jouw inschrijving is fout gegaan. ";
	$bericht .= "Probeer het nog eens door een bericht te sturen naar: ";
	$bericht .= "$thuis. Zet in het onderwerp de naam van het spel, ";
	$bericht .= "en in het bericht je eigen naam, gevolgd door een komma, ";
	$bericht .= "en vervolgens of je een man (m) of vrouw (v) bent.<br />";
	$bericht .= "<br />";
	$bericht .= "Mocht deze fout aanhouden, ";
	$bericht .= "stuur dan een bericht met onderwerp 'Help', ";
	$bericht .= "en een beschrijving van de fout in de boodschap; ";
	$bericht .= "dan wordt je zo snel mogelijk geholpen.";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurInschrijving

//als een stem goed ontvangen/geparsed is: stuur bericht terug naar de speler
function stuurStem($naam,$adres,$stem,$sid) {
	global $thuis;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stem ontvangen";
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem is ontvangen: je stemt op $stem.<br />";
	$bericht .= "<br />";
	$bericht .= "Mocht dit verkeerd zijn, stem dan zo snel mogelijk opnieuw. ";
	$bericht .= "Stuur een bericht naar $thuis, met als onderwerp '$spel', ";
	$bericht .= "en met de naam van de speler op wie je stemt ";
	$bericht .= "in het bericht. ";
	$bericht .= "Zet geen andere namen in het bericht, ";
	$bericht .= "om problemen te voorkomen. ";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurStem

//als meerdere stemmen goed ontvangen/geparsed zijn: 
//stuur bericht naar de speler
function stuurStem2($naam,$adres,$stem,$stem2,$sid) {
	global $thuis;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stem ontvangen";
	
	$bericht .= "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem is ontvangen: je stemt op $stem en $stem2.<br />";
	$bericht .= "<br />";
	$bericht .= "Mocht dit verkeerd zijn, stem dan zo snel mogelijk opnieuw. ";
	$bericht .= "Stuur een bericht naar $thuis, met als onderwerp '$spel', ";
	$bericht .= "en met de naam van de speler op wie je stemt ";
	$bericht .= "in het bericht. ";
	$bericht .= "Zet geen andere namen in het bericht, ";
	$bericht .= "om problemen te voorkomen. ";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurStem2

//als een mail binnen is gekomen wanneer een speler niet aan de beurt komt
function houJeMond($naam,$adres,$sid) {
	global $thuis;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stemmen mislukt";
	
	$bericht .= "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Het systeem is momenteel niet open voor jouw stem. ";
	$bericht .= "Kan het zo zijn dat het wellicht jouw beurt niet is? ";
	$bericht .= "Of misschien heb je een deadline gemist? ";
	$bericht .= "Of ben je wellicht dood? <br />";
	$bericht .= "Als het tijd wordt om weer te stemmen, ";
	$bericht .= "dan zal je bericht worden; ";
	$bericht .= "tot die tijd moet je even wachten. <br />";
	$bericht .= "<br />";
	$bericht .= "Mocht het zo zijn dat het wel jouw beurt was om te stemmen, ";
	$bericht .= "probeer je stem dan nog eens te sturen. ";
	$bericht .= "Krijg je dit bericht weer, ";
	$bericht .= "neem dan contact op met het systeembeheer: ";
	$bericht .= "stuur een email met onderwerp 'Help', ";
	$bericht .= "waarin je het probleem uitlegt ";
	$bericht .= "naar $thuis. ";
	$bericht .= "Het probleem zal dan zo snel mogelijk worden opgelost. ";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//houJeMond

//als een mail een onbekende afzender heeft, of verkeerd onderwerp
function stuurFoutAdres($adres) {
	global $thuis;
	$onderwerp = "Foutmelding";
	
	$bericht = "Helaas gaf jouw bericht een foutmelding; ";
	$bericht .= "het kon niet door het systeem worden geparsed. ";
	$bericht .= "Er was geen duidelijk spel aangegeven in het onderwerp, ";
	$bericht .= "of dit emailadres is niet bekend ";
	$bericht .= "als speler bij dit spel.<br />";
	$bericht .= "<br />";
	$bericht .= "Als je een bericht naar $thuis stuurt over een spel, ";
	$bericht .= "zet dan de naam van het spel in het onderwerp. ";
	$bericht .= "Let hierbij op de spelling, ";
	$bericht .= "zodat een computer deze goed kan lezen.<br />";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurFoutAdres

function stuurFoutStem($naam,$adres,$sid) {
	global $thuis;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Fout bij stemmen";
	
	$bericht .= "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem was onduidelijk, en dus niet geteld; ";
	$bericht .= "het kon niet door het systeem worden geparsed. ";
	$bericht .= "Dit kan komen om meerdere redenen:<br />";
	$bericht .= "<br />";
	$bericht .= "- Er stond geen speler in het bericht, ";
	$bericht .= "of de naam was verkeerd gespeld.<br />";
	$bericht .= "- Er stonden te veel namen in het bericht.<br />";
	$bericht .= "- De speler die je aanwees kan niet worden gekozen ";
	$bericht .= "(dit is tegen bepaalde criteria van de stem).<br />";
	$bericht .= "- Of er is iets anders fout gegaan.<br />";
	$bericht .= "<br />";
	$bericht .= "Probeer nog eens te stemmen, en zorg ervoor dat je bericht " ;
	$bericht .= "goed door het systeem gelezen kan worden. ";
	$bericht .= "Vermeld enkel de naam (of namen) van de speler(s) ";
	$bericht .= "op wie je stemt, of 'blanco' als je op niemand stemt.<br />";
	$bericht .= "<br />";
	$bericht .= "Als deze fout aanhoudt, en het is onduidelijk waardoor, ";
	$bericht .= "neem dan contact op met het systeembeheer: ";
	$bericht .= "stuur een bericht met onderwerp 'Help' en het probleem ";
	$bericht .= "naar $thuis. ";
	$bericht .= "Dit probleem wordt dan zo snel mogelijk opgelost.";
	
	stuurMail($adres,$onderwerp,$bericht);
	return;
}//stuurFoutStem

?>
