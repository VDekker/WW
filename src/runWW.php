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

date_default_timezone_set("Europe/Amsterdam");

$admins = admins();
$tabellen = tabellen();
$thuis = thuis();
$handleiding = handleiding();
$dbconnect = dbConnect();
$gmconnect = gmailConnect();

if(!zoekControle()) {
	schrijfLog(-1,"Controle niet gevonden.\n");

	schrijfLog(-1,"Stuur alle mails opnieuw.\n");
	herhaalMails(); //stuur alle vorige mails opnieuw
}
else {
	schrijfLog(-1,"Controle gevonden.\n");

	schrijfLog(-1,"Verwijder oude mails uit database.\n");
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
