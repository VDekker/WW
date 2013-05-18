<?php

require("config.php");
require("dbFuncties.php");
require("faseFuncties.php");
require("fases.php");
require("gmailConnect.php");
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
$thuis = "WWautoVerteller@gmail.com";
$handleiding = "http://www.liacs.nl/~vdekker/WW/pdf/man.pdf";
$dbconnect = dbConnect();
$gmconnect = gmailConnect();

if(!zoekControle()) {
	echo "Controle niet gevonden.\n";
	herhaalMails(); //stuur alle vorige mails opnieuw
}
else {
	verwijderMails(); //haalt alle oude mails uit de database
	gmailParse(); // check of er mails zijn en reageer hierop
	fases(); // regel alle stemmen, fases, stuurt mails
}
gmailSluit();
dbSluit();

stuurControle(); // voor controleren van mailen

?>
