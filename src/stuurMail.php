<?php

function footnote($auteurs) {
	global $thuis,$handleiding;

	if(!empty($auteurs)) {
		$auteurs = array_unique($auteurs);
		$auteurs = array_values($auteurs);
	}
	$max = count($auteurs);

	$footnote = "<br />";
	$footnote .= "<br />";
	$footnote .= "<hr>";
	$footnote .= "<font size='1'>";

	if($max == 1) {
		$footnote .= "Verhaaltje geschreven door " . $auteurs[0] . ".<br />";
	}
	else if($max > 1) {
		$footnote .= "Verhaaltje geschreven door ";
		for($i = 0; $i < $max; $i++) {
			if($max - $i == 1) { //de laatste auteur
				$footnote .= $auteurs[$i] . ".<br />";
			}
			else if($max - $i == 2) { //de een-na laatste auteur
				$footnote .= $auteurs[$i] . " en ";
			}
			else {
				$footnote .= $auteurs[$i] . ", ";
			}
		}
	}

	$footnote .= "De Automatische Verteller, gemaakt door Victor Dekker, is ";
	$footnote .= "gebaseerd op het spel <i>'De Weerwolven van Wakkerdam'</i> ";
	$footnote .= "van Philippe des Pallières en Hervé Marly, ";
	$footnote .= "en de uitbreidingen <i>'Volle Maan in Wakkerdam'</i> en ";
	$footnote .= "<i>'Het Dorp'</i>.<br />";
	$footnote .= "Voor hulp bij het gebruik van de Automatische Verteller, ";
	$footnote .= "zie <a href=$handleiding>handleiding</a>, ";
	$footnote .= "of stuur een email met onderwerp 'Help' naar ";
	$footnote .= '<a href="mailto:' . "$thuis" . '?subject=Help';
	$footnote .= "&body=Als deze mail anoniem is: ";
	$footnote .= "voeg 'anoniem' toe aan het onderwerp ";
	$footnote .= 'en zet hier niet je naam in!">';
	$footnote .= "$thuis</a>; ";
	$footnote .= "deze mail zal zo snel mogelijk worden beantwoord.";
	$footnote .= "</font>";
	return $footnote;
}//footnote

//stuurt een mail en slaat deze ook op in de tabel Mails
function stuurMail($adres,$onderwerp,$bericht,$auteurs) {
	global $thuis,$tabellen;
	$adres .= ", eudyptes.crestatus@gmail.com"; //TODO delete
	$tabel = $tabellen[1];
	$from = "From: $thuis";
	$headers = "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=UTF-8\r\n";
	$headers .= "Content-Transfer-Encoding: base64\r\n";
	$headers .= $from;
	$message = "<html><head><title>$onderwerp</title></head><body>";
	$message .= $bericht;
	$message .= footnote($auteurs);
	$message .= "</body></html>";

	//tegen willekeurige spaties
	$message2 = chunk_split(base64_encode($message));
	mail($adres,$onderwerp,$message2,$headers);

	//zet in database
	$text = sqlEscape($message);
	$sql = "INSERT INTO $tabel(ADRES,ONDERWERP,BERICHT,HEADERS)
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
	stuurMail($alleAdmins,$onderwerp,$error,NULL);
	schrijfLog(-1,"Error: $error\nGeen spel gepauzeerd.\n");
	die($error);
	return;
}//stuurError

//stuurt een error naar de systeembeheerder
//en zet spel $sid op pauze
function stuurError2($error,$sid) {
	global $admins,$thuis;
	$onderwerp = "Error";
	$alleAdmins = $admins[0];
	for($i = 1; $i < count($admins); $i++) {
		$alleAdmins .= ", $admins[$i]";
	}
	stuurMail($alleAdmins,$onderwerp,$error,NULL);

	sqlUp(4,"STATUS=1","SID=$sid");
	schrijfLog($sid,"Error: $error\nSpel gepauzeerd.\n");
	die($error);
	return;
}//stuurError2

function stuurResultaatHTML($adres,$bericht,$resultaat) {
	global $thuis,$footnote;
	$onderwerp = "Query";
	
	$bericht .= "<table border='1'><tr>";
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
			$bericht .= "<td align='center'>";
			$bericht .= htmlspecialchars($value);
			$bericht .= "</td>";
		}//foreach
		$bericht .= "</tr>";
	}//while
	$bericht .= "</table>";
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurResultaatHTML

function stuurGewonnenAdmins($spel) {
	global $admins;
	$sid = $spel['SID'];
	$snaam = $spel['SNAAM'];
	
	$onderwerp = "Spel gewonnen: $snaam";
	$bericht = "Het spel $snaam (ID: $sid) is gewonnen. ";
	$bericht .= "Dit spel zal dus niet voorzetten, ";
	$bericht .= "en kan verwijderd worden als dat nodig is.";

	//pak de adressen van de admins
	$alleAdmins = $admins[0];
	for($i = 1; $i < count($admins); $i++) {
		$alleAdmins .= ", $admins[$i]";
	}
	stuurMail($alleAdmins,$onderwerp,$bericht,NULL);
	return;
}//stuurGewonnenAdmins

function stuurStop($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel(3,"SID=$sid");
	$adressen = array();
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($adressen)) {
		schrijfLog($sid,"Niemand om te mailen.\n");
		return;
	}

	$onderwerp = "$snaam: Spel gestopt";
	$bericht = "Helaas is het mailspel $snaam door het systeembeheer gestopt. ";
	$bericht .= "Dit spel zal niet meer doorgaan, ";
	$bericht .= "en hierover worden geen automatische mails meer verzonden. ";
	$bericht .= "Emails over dit spel zullen niet worden geparsed ";
	$bericht .= "door het systeem.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";
	foreach($adressen as $adres) {
		stuurMail($adres,$onderwerp,$bericht,NULL);
	}
	return;
}//stuurStop

function stuurPauze($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel(3,"SID=$sid");
	$adressen = array();
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($adressen)) {
		schrijfLog($sid,"Niemand om te mailen.\n");
		return;
	}

	$onderwerp = "$snaam: Spel gepauzeerd";
	$bericht = "Het mailspel $snaam is door het systeembeheer gepauzeerd. ";
	$bericht .= "Als het spel wordt hervat, zal je hiervan worden bericht, ";
	$bericht .= "maar tot die tijd worden hierover geen automatische mails ";
	$bericht .= "meer verzonden. ";
	$bericht .= "Emails over dit spel zullen niet worden geparsed ";
	$bericht .= "door het systeem.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";
	foreach($adressen as $adres) {
		stuurMail($adres,$onderwerp,$bericht,NULL);
	}
	return;
}//stuurPauze

function stuurHervat($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel(3,"SID=$sid");
	$adressen = array();
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($adressen)) {
		schrijfLog($sid,"Niemand om te mailen.\n");
		return;
	}

	$onderwerp = "$snaam: Spel hervat";
	$bericht = "Het mailspel $snaam gaat weer door. ";
	$bericht .= "Vanaf nu begint het weer waar het was gebleven. ";
	$bericht .= "Mails over dit spel worden weer geparsed.";
	$bericht .= "<br /><br />";
	$bericht .= "Nog veel speelplezier!";
	foreach($adressen as $adres) {
		stuurMail($adres,$onderwerp,$bericht,NULL);
	}
	return;
}//stuurHervat

function stuurFoutStop($adres,$snaam) {
	$onderwerp = "$snaam: Mail niet gelezen";
	$bericht = "Het mailspel $snaam is geeindigd. ";
	$bericht .= "Jouw mailtje is dus niet gelezen, ";
	$bericht .= "en wordt verder genegeerd.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";

	$bericht .= disclaimer();

	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurFoutStop

function stuurFoutPauze($adres,$snaam) {
	$onderwerp = "$snaam: Mail niet gelezen";
	$bericht = "Het mailspel $snaam is gepauzeerd. ";
	$bericht .= "Jouw mailtje is dus niet gelezen, ";
	$bericht .= "en wordt verder genegeerd, ";
	$bericht .= "en hier wordt ook niets mee gedaan ";
	$bericht .= "als het spel wordt hervat.";
	$bericht .= "<br /><br />";
	$bericht .= "Excuses voor het ongemak.";

	$bericht .= disclaimer();

	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurFoutPauze

function stuurInschrijving($adres,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$resultaat = sqlSel(3,"SID=$sid AND EMAIL='$adres'");
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
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurInschrijving

function stuurInschrijvingFout($adres,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Inschrijving mislukt";
	
	$bericht = "Jouw inschrijving is fout gegaan. ";
	$bericht .= "Probeer het nog eens door een bericht te sturen naar: ";
	$bericht .= "$thuis. Zet in het onderwerp de naam van het spel, ";
	$bericht .= "en in het bericht je eigen naam, gevolgd door een komma, ";
	$bericht .= "en vervolgens of je een man (m) of vrouw (v) bent.<br />";
	$bericht .= "De naam mag enkel bestaan uit alfabetische letters.";

	$bericht .= disclaimer();
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurInschrijving

//als een stem goed ontvangen/geparsed is: stuur bericht terug naar de speler
function stuurStem($naam,$adres,$stem,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stem ontvangen";

	if($stem == -1) {
		$stem = "blanco";
	}
	else{
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		$stem = $speler['NAAM'];
	}
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	if($stem == "blanco") {
		$bericht .= "Jouw stem is ontvangen: je stemt blanco.<br />";
	}
	else {
		$bericht .= "Jouw stem is ontvangen: je stemt op $stem.<br />";
	}
	$bericht .= stem($snaam);
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurStem

//als meerdere stemmen goed ontvangen/geparsed zijn: 
//stuur bericht naar de speler
function stuurStem2($naam,$adres,$stem,$stem2,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stem ontvangen";
	
	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$stem = $speler['NAAM'];

	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	$stem2 = $speler['NAAM'];

	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem is ontvangen: je stemt op $stem en $stem2.<br />";
	$bericht .= stem($snaam);
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurStem2

function stuurStemHeks($naam,$adres,$stem,$stem2,$flag,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stem ontvangen";
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem is ontvangen: ";
	if($stem == -1) {
		$bericht .= "je stemt blanco.<br />";
	}
	else if(($flag & 1) == 1) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		$stem = $speler['NAAM'];
		$bericht .= "je redt $stem met het levenselixer";
		if(($flag & 2) == 2) {
			$resultaat = sqlSel(3,"ID=$stem2");
			$speler = sqlFet($resultaat);
			$stem2 = $speler['NAAM'];
			$bericht .= " en doodt $stem2 met het gif";
		}
		$bericht .= ".<br />";
	}//if
	else if(($flag & 2) == 2) {
		$resultaat = sqlSel(3,"ID=$stem2");
		$speler = sqlFet($resultaat);
		$stem2 = $speler['NAAM'];
		$bericht .= "je doodt $stem2 met het gif.<br />";
	}
	else {
		$bericht .= "je stemt blanco.<br />";
	}
	$bericht .= stem($snaam);
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurStemHeks

function stuurStemZonde($naam,$adres,$stemmen,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stem ontvangen";
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem is ontvangen: ";
	
	if($stemmen == "-1") {
		$bericht .= "je stemt blanco.<br />";
	}
	else {
		$bericht .= "je stemt op ";
		$stem = explode(",",$stemmen);
		$aantal = count($stem);
		for($i = 0; $i < $aantal; $i++) {
			$naam = $stem[$i];
			$resultaat = sqlSel(3,"ID=$naam");
			$speler = sqlFet($resultaat);
			$naam = $speler['NAAM'];
			if($aantal - $i == 1) { //laatste
				$bericht .= $naam . ".<br />";
			}
			else if($aantal - $i == 2) { //een-na-laatste
				$bericht .= $naam . " en ";
			}
			else {
				$bericht .= $naam . ", ";
			}
		}//for
	}//else
	$bericht .= stem($snaam);
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurStemZonde

//als een mail binnen is gekomen wanneer een speler niet aan de beurt komt
function houJeMond($naam,$adres,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Stemmen mislukt";
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Het systeem is momenteel niet open voor jouw stem. ";
	$bericht .= "Kan het zo zijn dat het wellicht jouw beurt niet is? ";
	$bericht .= "Of misschien heb je een deadline gemist? ";
	$bericht .= "Als het tijd wordt om weer te stemmen, ";
	$bericht .= "dan zal je bericht worden; ";
	$bericht .= "tot die tijd moet je even wachten.";

	$bericht .= disclaimer();
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//houJeMond

//als een mail een onbekende afzender heeft
function stuurFoutAdres($adres,$snaam) {
	$onderwerp = "$snaam: Foutmelding";
	
	$bericht = "Helaas gaf jouw bericht een foutmelding; ";
	$bericht .= "het kon niet door het systeem worden geparsed. ";
	$bericht .= "Dit emailadres is niet bekend ";
	$bericht .= "als speler bij dit spel.";

	$bericht .= disclaimer();
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurFoutAdres

//als een speler dood is, hoeft hij niet meer te stemmen
function stuurFoutDood($adres,$snaam) {
	global $thuis;
	$onderwerp = "$snaam: Foutmelding";

	$bericht = "Helaas ben jij dood in dit spel; ";
	$bericht .= "je hoeft dus niet meer te stemmen. ";
	$bericht .= "Je bericht is niet gelezen, ";
	$bericht .= "en je stem is niet geteld.";

	$bericht .= disclaimer();

	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}

//als een mail een verkeerd onderwerp heeft:
//geen 'help', 'config' of spelnaam herkent.
function stuurFoutOnderwerp($adres) {
	global $thuis;
	$onderwerp = "Foutmelding: verkeerd onderwerp";
	
	$bericht = "Helaas gaf jouw bericht een foutmelding; ";
	$bericht .= "het kon niet door het systeem worden geparsed. ";
	$bericht .= "Het onderwerp van jouw mail was onduidelijk. ";
	$bericht .= "Er kon geen spelnaam of 'Help' in worden herkend.<br />";
	$bericht .= "<br />";
	$bericht .= "Als je een bericht naar $thuis stuurt over een spel, ";
	$bericht .= "zet dan de naam van het spel in het onderwerp. ";
	$bericht .= "Let hierbij op de spelling, ";
	$bericht .= "zodat een computer deze goed kan lezen.";

	$bericht .= disclaimer();
	
	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurFoutOnderwerp

function stuurFoutStem($naam,$adres,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Fout bij stemmen";
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw stem was onduidelijk, en dus niet geteld; ";
	$bericht .= "het kon niet door het systeem worden geparsed. ";
	$bericht .= "Dit kan komen om meerdere redenen:<br />";
	$bericht .= "<br /><ul>";
	$bericht .= "<li>Er stond geen speler in het bericht, ";
	$bericht .= "of de naam was verkeerd gespeld.</li>";
	$bericht .= "<li>Er stonden te veel namen in het bericht.</li>";
	$bericht .= "<li>De speler die je aanwees kan niet worden gekozen ";
	$bericht .= "(dit is tegen bepaalde criteria van de stem).</li>";
	$bericht .= "<li>Of er is iets anders fout gegaan.</li>";
	$bericht .= "</ul><br />";
	$bericht .= "Probeer nog eens te stemmen, en zorg ervoor dat je bericht " ;
	$bericht .= "goed door het systeem gelezen kan worden. ";
	$bericht .= "Vermeld enkel de naam (of namen) van de speler(s) ";
	$bericht .= "op wie je stemt, of 'blanco' als je op niemand stemt.";

	$bericht .= disclaimer();

	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurFoutStem

function stuurFoutStem2($naam,$adres,$sid) {
	global $thuis;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Fout bij stemmen";
	
	$bericht = "Hallo $naam,<br />";
	$bericht .= "<br />";
	$bericht .= "Jouw keuze is helaas niet geteld door het systeem: ";
	$bericht .= "het leverde een onverwachte fout op. ";
	$bericht .= "Waarschijnlijk is jouw keuze ook door een andere speler ";
	$bericht .= "gekozen, en mag hij daarom niet nog eens worden gekozen. ";
	$bericht .= "Je moet je keuze herzien; ";
	$bericht .= "dit zal de fout waarschijnlijk oplossen.";

	$bericht .= disclaimer();

	stuurMail($adres,$onderwerp,$bericht,NULL);
	return;
}//stuurFoutStem2


//geeft heel de mail-lijst van een spel in een string
function maillijst($sid) {
	$adressen = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((SPELERFLAGS & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", " . $adressen[$i];
	}
	return $adres;
}

function stem($snaam) {
	global $thuis;

	$bericht = "<br />";
	$bericht .= "Mocht dit verkeerd zijn, stem dan zo snel mogelijk opnieuw. ";
	$bericht .= "Stuur een bericht naar $thuis, met als onderwerp '$snaam', ";
	$bericht .= "en met de naam (of namen) van de speler(s) op wie je stemt ";
	$bericht .= "in het bericht. ";
	$bericht .= "Zet geen andere namen in het bericht, ";
	$bericht .= "om problemen te voorkomen. ";
	
	return $bericht;
}//stem

//een beetje algemene hulp bij foutmeldingen
function disclaimer() {
	global $thuis;
	
	$bericht = "<br /><br />";
	$bericht .= "Als deze fout aanhoudt, en het is onduidelijk waardoor, ";
	$bericht .= "neem dan contact op met het systeembeheer: ";
	$bericht .= "stuur een bericht met onderwerp 'Help', ";
	$bericht .= "waarin je het probleem uitlegt, naar $thuis. ";
	$bericht .= "Dit probleem wordt dan zo snel mogelijk opgelost.";

	return $bericht;
}//disclaimer

?>
