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

	
	$text = "";
	$samenvatting = "";
	$auteur = array();
	$resultaat = sqlSel("Spellen","SID=1");
	$spel = sqlFet($resultaat);
	ontwaakVerhaal($text,$samenvatting,$auteur,$spel);
	echo $text;
	
	/*
	$boom = array();
	$speciaal = array();
	$resArray = array();
	$resultaat = sqlSel("Spelers","((LEVEND & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		if(($speler['ROL'] == "Jager" && ($speler['SPELFLAGS'] & 4) == 4)) {
			$res = sqlSel("Spelers","ID=" . $speler['EXTRA_STEM']);
			$target = sqlFet($res);
			array_push($speciaal,$target);
		}
		if(($speler['GELIEFDE'] != "" && ($speler['SPELFLAGS'] & 512) == 0)) {
			$res = sqlSel("Spelers","ID=" . $speler['GELIEFDE']);
			$target = sqlFet($res);
			array_push($speciaal,$target);
		}
		array_push($resArray,$speler);
	}
	echo "Speciaal:\n";
	var_dump($speciaal);
	echo "\n\nBoom vooraf:\n";
	var_dump($boom);
	echo "\n\nresArray:\n";
	var_dump($resArray);
	$boom = maakBoom(0,$speciaal,$boom,0,$resArray);
	echo "\n\nBoom na-af:\n";
	var_dump($boom);
*/
	

}
gmailSluit();
dbSluit();

stuurControle(); // voor controleren van mailen

?>
