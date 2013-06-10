<?php

require("config.php");
require("dbFuncties.php");
require("faseFuncties.php");
require("fases.php");
require("gmailParse.php");
require("parseFuncties.php");
require("parseStem.php");
require("regelRollen.php");
require("rollen.php");
require("stuurMail.php");
require("verhalen.php");
require("verhalenFuncties.php");

date_default_timezone_set("Europe/Amsterdam");

$admin = "Victor Dekker <eudyptes.crestatus@gmail.com>";
$admin2 = "Jenneke Buwalda <ciel.celestis@gmail.com>";
$admins = array($admin,$admin2);
$tabellen = array("Help","Mails","Rollen","Spelers",
	"Spellen","Themas","Verhalen");
$thuis = "WWautoVerteller@gmail.com";
$handleiding = "http://www.liacs.nl/~vdekker/WW/pdf/man.pdf";
$dbconnect = dbConnect();
$gmconnect = gmailConnect();

/*
if(!zoekControle()) {
	echo "Controle niet gevonden.\n";
	herhaalMails(); //stuur alle vorige mails opnieuw
}
else {
	echo "Controle gevonden.\n";
	verwijderMails(); //haalt alle oude mails uit de database
	echo "Begin parsen.\n";
	gmailParse(); // check of er mails zijn en reageer hierop
	echo "Begin regelen.\n";
	fases(); // regel alle stemmen, fases, stuurt mails
}
 */

$text = "";
$samenvatting = "";
$auteur = array();
$resultaat = sqlSel(4,"SID=1");
$spel = sqlFet($resultaat);
ontwaakVerhaal($text,$samenvatting,$auteur,$spel);

echo "Verhaaltje:\n";
echo "$text\n\n";
echo "Samenvatting:\n";
echo "$samenvatting\n\n";

echo "Sluit alles.\n";
gmailSluit();
dbSluit();

stuurControle(); // voor controleren van mailen
echo "Klaar.\n";

?>
