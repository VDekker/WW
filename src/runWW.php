<?php

require("config.php");
require("dbFuncties.php");
require("easter.php");
require("faseFuncties.php");
require("fases.php");
require("gmailParse.php");
require("parseFuncties.php");
require("parseStem.php");
require("protected.php");
require("regelRollen.php");
require("rollen.php");
require("stuurMail.php");
require("verhalen.php");
require("verhalenFuncties.php");

//tijdzone instellen voor systeemtijd etc.
date_default_timezone_set("Europe/Amsterdam");

//zet globale variabelen
$admins = admins();
$tabellen = tabellen();
$thuis = thuis();
$handleiding = handleiding();
$dbconnect = dbConnect();
$gmconnect = gmailConnect();

//zoek controle-mail (ivm. mail-storingen)
if(!zoekControle()) {
	schrijfLog(-1,"Controle niet gevonden.\nStuur alle mails opnieuw.\n");
	herhaalMails(); //stuur alle vorige mails opnieuw
}
else {
	schrijfLog(-1,"Controle gevonden.\nVerwijder oude mails.\n");
	verwijderMails(); //haalt alle oude mails uit de database

	schrijfLog(-1,"Parse nieuwe mails.\n");
	gmailParse(); // check of er mails zijn en reageer hierop

	schrijfLog(-1,"Regel alle spellen.\n");
	fases(); // regel alle stemmen, fases, stuurt mails
}

schrijfLog(-1,"Sluit alles.\n");
gmailSluit();
dbSluit();

stuurControle(); // voor controleren van mailen

?>
