<?php

//mailt iedere speler AFZONDERLIJK met diens rol
function mailRolverdeling($sid) {
	$resultaat = sqlSel("Spelers","SPEL='$sid'");
	$onderwerp = "$sid: Rol";
	$verhaal = geefVerhaal($thema,"Rolverdeling",0);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];

	while($speler = sqlFet($resultaat)) {
		$namen = array($speler['NAAM']);
		$adres = $speler['EMAIL'];
		$rollen = array($speler['ROL']);
		if($rollen[0] == "Dwaas") {
			$rollen[0] = "Ziener";
		}
		$geslachten = array($speler['GESLACHT']);
		$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$bericht);
		echo "Rol naar $naam gemaild.\n";
	}//while
	return 0;
}//mailRolverdeling

//voor een gegeven rol, 
//stuurt een mail naar alle spelers met die rol AFZONDERLIJK,
//met het juiste verhaaltje
function mailWakker($rol,$sid) {
	$onderwerp = "$sid: Actie";
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND ROL='$rol' AND LEVEND=1");
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['NAAM'],$sid)) {
			continue;
		}
		$namen = array($speler['NAAM']);
		$geslachten = array($speler['GESLACHT']);
		$rollen = array($rol);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,$rol,0);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $namen[0].\n";
	}//while
	return;
}//mailWakker

//maakt een groep spelers wakker:
//Weerwolven (inc. Witte), Vampiers of Fluitspelers
function mailGroepWakker($rol,$sid) {
	$onderwerp = "$sid: Actie";
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$namen = array();
	$geslachten = array();
	$rollen = array();
	$adressen = array();
	if($rol == "Weerwolf") {
		$resultaat = sqlSel("Spelers",
			"SPEL='$sid' AND LEVEND=1 AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1 AND ROL='$rol'");
	}
	if(sqlNum($resultaat) == 0) {
		echo "Geen levende $rol te bekennen.\n";
		return;
	}
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['NAAM'],$sid)) {
			continue;
		}
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$rol);
		array_push($geslachten,$speler['GESLACHT']);
		array_push($adressen,$speler['EMAIL']);
	}
	if(empty($namen)) {
		echo "Enkel slapende $rol.\n";
		return;
	}
	$adres = $adressen[0];
	for($i = 0; $i < count($adressen); $i++) {
		$adres .= ", " . $adressen[$i];
	}

	$verhaal = geefVerhaalGroep($thema,$rol,0,count($namen),0);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	foreach($namen as $naam) {
		echo "Mail gestuurd naar $naam.\n";
	}
	return;
}//mailGroepWakker

//wekt alle Heksen afzonderlijk: geeft ook namen van slachtoffers mee
//(alle nieuw_dode spelers; deze kunnen geredt worden)
function mailHeksWakker($sid) {
	$onderwerp = "$sid: Actie";
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$namen = array(); // vul een array van slachtoffers
	$rollen = array();
	$geslachten = array();
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND LEVEND=1 AND NIEUW_DOOD=1");
	while($speler = sqlFet($resultaat)) {
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		array_push($geslachten,$speler['GESLACHT']);
	}

	$resultaat = sqlSel("Spelers","SPEL='$sid' AND ROL='Heks' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['NAAM'],$sid)) {
			continue;
		}
		array_splice($namen,0,0,$speler['NAAM']);
		array_splice($rollen,0,0,"Heks");
		array_splice($geslachten,0,0,$speler['GESLACHT']);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Heks",0);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $namen[0].\n";
		$namen = delArrayElement($namen,0);
		$rollen = delArrayElement($rollen,0);
		$geslachten = delArrayElement($geslachten,0);
	}//while
	return;
}//mailHeksWakker

//standaard functie voor mailen van een speler-actie 
//(met slachtoffer opgeslagen in 'stem')
//blanco werkt ook
function mailActie($naam,$fase,$sid,$plek) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array($speler['ROL']);
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem = $speler["$plek"];
	if($rollen[0] == "Dwaas") {
		$rollen[0] = "Ziener";
	}
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem'");
		$speler = sqlFet($resultaat);
		array_push($namen,$stem);
		array_push($rollen,$speler['ROL']);
		array_push($geslachten,$speler['GESLACHT']);
	}

	echo "$rollen[0] $naam heeft op $stem gestemd.\n";
	$verhaal = geefVerhaal($thema,$rollen[0],$fase);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailActie

//aparte functie voor Dief omdat het slachtoffer ook gemaild moet worden
function mailDief($naam,$sid) {
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Bestolen";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Dief");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);
	$adres2 = $speler['EMAIL'];
	echo "Dief $naam steelt de rol '$rollen[1]' van $stem.\n";

	$verhaal = geefVerhaal($thema,'Dief',2);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";

	$namen = delArrayElement($namen,0);
	$rollen = delArrayElement($rollen,0);
	$geslachten = delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,'Dief',3);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stemn.\n";
	return;
}//mailDief

//aparte functie voor Cupido omdat de Geliefden ook gemaild moeten worden
function mailCupido($naam,$stem1,$stem2,$sid) {
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Geliefde";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Cupido");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem1'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem1);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);
	$adres2 = $speler['EMAIL'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem2'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem2);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);
	$adres2 .= ", " . $speler['EMAIL'];

	echo "Cupido $naam maakt $stem en $stem2 verliefd op elkaar.\n";

	$verhaal = geefVerhaal($thema,'Cupido',1);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";

	$namen = delArrayElement($namen,0);
	$rollen = delArrayElement($rollen,0);
	$geslachten = delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,'Cupido',2);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem1 en $stem2.\n";
	return;
}//mailCupido

//aparte functie voor Opdrachtgever omdat Lijfwacht gemaild moet worden
function mailOpdracht($naam,$sid) {
	$onderwerp = "$sid: Opdrachtgever";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Opdrachtgever");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);
	$adres .= ", " . $speler['EMAIL'];

	echo "Opdrachtgever $naam stelt $stem aan tot zijn lijfwacht.\n";

	$verhaal = geefVerhaal($thema,'Opdrachtgever',1);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam en $stem.\n";
	return;
}//mailOpdracht

//aparte functie voor Welp omdat fase ongebruikelijk is
function mailWelp($naam,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Welp");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];

	echo "Welp $naam wordt een Weerwolf.\n";

	$verhaal = geefVerhaal($thema,'Welp',0);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailWelp

//aparte functie voor Klaas Vaak omdat slachtoffer ook gemaild moet worden
function mailKlaas($naam,$sid) {
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Slaap";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Klaas Vaak");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);
	$adres2 = $speler['EMAIL'];

	echo "Klaas Vaak $naam laat $stem slapen.\n";

	$verhaal = geefVerhaal($thema,'Klaas Vaak',1);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";

	$namen = delArrayElement($namen,0);
	$rollen = delArrayElement($rollen,0);
	$geslachten = delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,'Klaas Vaak',2);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailKlaas

//aparte functie voor de Dwaas vanwege $gezien
function mailDwaas($naam,$gezien,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Ziener");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem);
	array_push($rollen,$gezien);
	array_push($geslachten,$speler['GESLACHT']);

	echo "Dwaas $naam denkt dat $stem een $gezien is.\n";

	$verhaal = geefVerhaal($thema,'Ziener',1);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailDwaas

//aparte functie voor Goochelaar vanwege STEM en EXTRA_STEM
function mailGoochel($naam,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Goochelaar");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem1'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem1);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem2'");
	$speler = sqlFet($resultaat);
	array_push($namen,$stem2);
	array_push($rollen,$speler['ROL']);
	array_push($geslachten,$speler['GESLACHT']);

	echo "Goochelaar $naam verwisselt $stem en $stem2 met elkaar.\n";

	$verhaal = geefVerhaal($thema,'Goochelaar',2);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailGoochel

//mailt verhaaltje van WW of VP naar de groep
function mailWWVPActie($spelers,$slachtoffer,$rol,$fase,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$slachtoffer'");
	$speler = sqlFet($resultaat);
	$namen = array($slachtoffer);
	$rollen = array($speler['ROL']);
	$geslachten = array($speler['GESLACHT']);
	$adressen = array();
	foreach($spelers as $naam) {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
		$speler = sqlFet($resultaat);
		array_push($namen,$naam);
		array_push($rollen,$rol); //ook als Witte WW: Weerwolf of Vampier
		array_push($geslachten,$speler['GESLACHT']);
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}
	
	echo "$rol: $slachtoffer vermoord.\n";

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($spelers),1);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $naam) {
		echo "Mail gestuurd naar $naam.\n";
	}
	return;
}//mailWWVPActie

function mailOnschuldig($naam,$targets,$stemmen,$fase,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Onschuldige Meisje");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];

	echo (($rol & 1) == 1) ? "Weerwolf" : "Vampier" . 
		": Onschuldige Meisje $naam ziet de stemmen.\n";

	$verhaal = geefVerhaal($thema,'Onschuldige Meisje',$fase);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);

	$text .= "<br /><br />";
	foreach($targets as $key => $target) {
		$text .= "$target kreeg $stemmen[$key] stemmen.<br />";
	}
	$text = auteur($auteur,$text);
	
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailOnschuldig

//aparte functie vanwege de combinaties STEM en EXTRA_STEM 
//die soms niet/wel nodig zijn
function mailHeksActie($naam,$stem1,$stem2,$verhaal,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	if(($verhaal & 2) == 2) { //andere speler geredt, dus $stem1 is belangrijk
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem1'");
		$speler = sqlFet($resultaat);
		array_push($namen,$stem1);
		array_push($geslachten,$speler['GESLACHT']);
	}
	if(($verhaal & 4) == 4) { //speler gedood, dus $stem2 is belangrijk
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem2'");
		$speler = sqlFet($resultaat);
		array_push($namen,$stem2);
		array_push($geslachten,$speler['GESLACHT']);
	}

	$verhaal = geefVerhaal($thema,"Heks",$verhaal);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	foreach($namen as $key => $naam) {
		$text = str_replace("naam[$key]",$naam,$text);
		$paren = explode('%',$geswoorden);
		foreach($paren as $key => $paar) {
			$alternatief = explode('&',$paar);
			$text = str_replace("geslacht[$key][$key]",
				$alternatief[$geslachten[$key]],$text);
		}
	}
	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";

	return;
}//mailHeks

//mailt verhaaltje van FS naar groep,
//en mailt ook de Betoverden
function mailFSActie($spelers,$betoverd1,$betoverd2,$fase,$sid) {
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Betoverd";
	$numBetoverd = 0;
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$namen = array();
	$rollen = array();
	$geslachten = array();
	$adressen = array();
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$betoverd1'");
		$speler = sqlFet($resultaat);
		$betoverdNaam = array($betoverd1);
		$betoverdRol = array($speler['ROL']);
		$betoverdGeslacht = array($speler['GESLACHT']);
		$adres2 = $speler['EMAIL'];
		if(($fase & 1) != 1) {
			$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$betoverd2'");
			$speler = sqlFet($resultaat);
			array_push($betoverdNaam,$betoverd2);
			array_push($betoverdRol,$speler['ROL']);
			array_push($betoverdGeslacht,$speler['GESLACHT']);
			$adres2 .= ", " . $speler['EMAIL'];
			$numBetoverd = 2;
			echo "Fluitspelers betoveren $betoverd1 en $betoverd2.\n";
		}
		else {
			$numBetoverd = 1;
			echo "Fluitspelers betoveren enkel $betoverd1.\n";
		}
		$namen = array_merge($namen,$betoverdNaam);
		$rollen = array_merge($rollen,$betoverdRol);
		$geslachten = array_merge($geslachten,$betoverdGeslacht);
	}
	else {
		echo "Fluitspelers stemmen blanco.\n";
	}

	foreach($spelers as $naam) {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
		$speler = sqlFet($resultaat);
		array_push($namen,$naam);
		array_push($rollen,"Fluitspeler");
		array_push($geslachten,$speler['GESLACHT']);
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}
	
	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($spelers),$numBetoverd);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $naam) {
		echo "Mail gestuurd naar $naam.\n";
	}

	if($fase == 9 ) {
		return;
	}
	$numBetoverd += 2;
	$verhaal = geefVerhaal($thema,$rol,$numBetoverd);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($betoverdNaam,$betoverdRol,$betoverdGeslacht,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	foreach($betoverdNaam as $naam) {
		echo "Mail gestuurd naar $naam.\n";
	}
	return;
}//mailFSActie

function mailTestament($naam,$fase,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array($speler['ROL']);
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	$stem = $speler["STEM"];
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$stem'");
		$speler = sqlFet($resultaat);
		array_push($namen,$stem);
		array_push($rollen,$speler['ROL']);
		array_push($geslachten,$speler['GESLACHT']);
	}

	echo "Burgemeester $naam kiest als opvolger: $stem.\n";

	$verhaal = geefVerhaal($thema,"Burgemeester",$fase);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailTestament

function mailZonde($naam,$slachtoffers,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID='$sid'");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$naam'");
	$speler = sqlFet($resultaat);
	$namen = array($naam);
	$rollen = array("Zondebok");
	$geslachten = array($speler['GESLACHT']);
	$adres = $speler['EMAIL'];
	foreach($slachtoffers as $slachtoffer) {
		$resultaat = sqlSel("Spelers","SPEL='$sid' AND NAAM='$slachtoffer'");
		$speler = sqlFet($resultaat);
		array_push($namen,$slachtoffer);
		array_push($rollen,$speler['ROL']);
		array_push($geslachten,$speler['GESLACHT']);
		echo "Zondebok $naam wekt schuldgevoel op in $slachtoffer.\n";
	}

	$verhaal = geefVerhaalGroep2($thema,"Zondebok",1,1,(count($namen)-1));
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	//infodump: alle schuldige spelers
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";
	return;
}//mailZonde

?>
