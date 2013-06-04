<?php

//mailt iedere speler AFZONDERLIJK met diens rol
function mailRolverdeling($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Rol";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel("Spelers","SID=$sid");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$tuples = array($speler);
		$adres = $speler['EMAIL'];

		$verhaal = geefVerhaalRolverdeling($thema,$rollen[0],$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];

		$text = vulIn($tuples,"",$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Rol naar $id gemaild.\n";
	}//while
	return 0;
}//mailRolverdeling

//voor een gegeven rol, 
//stuurt een mail naar alle spelers met die rol AFZONDERLIJK,
//met het juiste verhaaltje
function mailWakker($rol,$sid) {
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SID=$sid AND ROL='$rol' AND ((LEVEND & 1) = 1)");
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$tuples = array($speler);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,$rol,0,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $id.\n";
	}//while
	return;
}//mailWakker

//maakt een groep spelers wakker:
//Weerwolven (inc. Witte), Vampiers of Fluitspelers
function mailGroepWakker($rol,$sid) {
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$tuples = array();
	$adressen = array();
	if($rol == "Weerwolf") {
		$resultaat = sqlSel("Spelers",
			"SID=$sid AND ((LEVEND & 1) = 1) AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel("Spelers",
			"SID=$sid AND ((LEVEND & 1) = 1) AND ROL='$rol'");
	}
	if(sqlNum($resultaat) == 0) {
		echo "Geen levende $rol te bekennen.\n";
		return;
	}
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		array_push($tuples,$speler);
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

	$verhaal = geefVerhaalGroep($thema,$rol,0,count($namen),0,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,$deadline,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $adres.\n";
	
	return;
}//mailGroepWakker

//wekt alle Heksen afzonderlijk: geeft ook namen van slachtoffers mee
//(alle nieuw_dode spelers; deze kunnen geredt worden)
function mailHeksWakker($sid) {
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$tuples = array(); // vul een array van slachtoffers
	$resultaat = sqlSel("Spelers","SID=$sid AND LEVEND=3");
	while($speler = sqlFet($resultaat)) {
		array_push($tuples,$speler);
	}

	$resultaat = sqlSel("Spelers","SID=$sid AND ROL='Heks' AND 
		((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		array_splice($tuples,0,0,$speler);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Heks",0,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,$deadline,$text,$geswoorden);

		if(!(($speler['LEVEND'] & 2) == 2)) {
			delArrayElement($tuples,0);
		}
		$text = keuzeHeks($text,$speler['NAAM'],$tuples,$sid);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $adres.\n";
		$tuples = delArrayElement($tuples,0);
	}//while
	return;
}//mailHeksWakker

//mailt alle overleden jagers wakker die niet geschoten hebben
function mailJagerWakker($fase,$sid) {
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$vlag = false;

	$resultaat = sqlSel("Spelers","SID=$sid AND ROL='Jager' AND 
		((LEVEND & 2) = 2) AND ((SPELFLAGS & 4) = 0)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		$vlag = true;
		$namen = array($speler['NAAM']);
		$rollen = array("Jager");
		$geslachten = array($speler['SPELERFLAGS'] & 1);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Jager",$fase,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
		$text = keuzeJager($text,$speler['NAAM'],$sid);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $namen[0].\n";
	}//while
	return $vlag;
}//mailJagerWakker

//mailt de Burgemeester wakker als hij dood is (voor testament)
function mailBurgWakker($sid) {
	//is er een dode burgemeester?
	$resultaat = sqlSel("Spelers","LEVEND<>1 AND ID IN
		(SELECT BURGEMEESTER FROM Spellen WHERE SID=$sid)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}
	$speler = sqlFet($resultaat);

	$deadline = geefDeadline($sid);
	$resultaat = sqlFet("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Testament";
	$thema = $spel['THEMA'];

	$namen = array($speler['NAAM']);
	$rollen = array($speler['ROL']);
	$geslachten = array($speler['SPELERFLAGS'] & 1);
	$adres = $speler['EMAIL'];
	$verhaal = geefVerhaal($thema,"Burgemeester",0,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $namen[0].\n";
	return true;
}//mailBurgWakker

//mailt alle overleden zondebokken met zonde-vlag aan
function mailZondeWakker($sid) {
	//zijn er uberhaupt dode zondebokken?
	$resultaat = sqlSel("Spelers","SID=$sid AND ROL='Zondebok' AND
		((LEVEND & 2) = 2) AND ((SPELFLAGS & 256) = 256)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}

	$deadline = geefDeadline($sid);
	$resultaat2 = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat2);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	while($speler = sqlFet($resultaat)) {
		$namen = array($speler['NAAM']);
		$rollen = array("Zondebok");
		$geslachten = array($speler['SPELERFLAGS'] & 1);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Zondebok",0,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $namen[0].\n";
	}//while
	return true;
}//mailZondeWakker


//standaard functie voor mailen van een speler-actie 
//(met slachtoffer opgeslagen in 'stem')
//blanco werkt ook
function mailActie($id,$fase,$sid,$plek) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array($speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler["$plek"];
	if($rollen[0] == "Dwaas") {
		$rollen[0] = "Ziener";
	}
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	echo "$rollen[0] $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaal($thema,$rollen[0],$fase,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailActie

//aparte functie voor Dief omdat het slachtoffer ook gemaild moet worden
function mailDief($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp2 = $spel['SNAAM'] . ": Bestolen";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Dief");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];
	echo "Dief $id steelt de rol '$rollen[1]' van $stem.\n";

	$verhaal = geefVerhaal($thema,'Dief',2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$namen = delArrayElement($namen,0);
	$rollen = delArrayElement($rollen,0);
	$geslachten = delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,'Dief',3,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailDief

//aparte functie voor Cupido omdat de Geliefden ook gemaild moeten worden
function mailCupido($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Geliefde";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Cupido");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];

	$resultaat = sqlSel("Spelers","ID=$stem1");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];
	$resultaat = sqlSel("Spelers","ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 .= ", " . $speler['EMAIL'];

	echo "Cupido $id maakt $stem en $stem2 verliefd op elkaar.\n";

	$verhaal = geefVerhaal($thema,'Cupido',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$namen = delArrayElement($namen,0);
	$rollen = delArrayElement($rollen,0);
	$geslachten = delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,'Cupido',2,$sid);
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
function mailOpdracht($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Opdrachtgever";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Opdrachtgever");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres .= ", " . $speler['EMAIL'];

	echo "Opdrachtgever $id stelt $stem aan tot zijn lijfwacht.\n";

	$verhaal = geefVerhaal($thema,'Opdrachtgever',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id en $stem.\n";
	return;
}//mailOpdracht

//aparte functie voor Welp omdat fase ongebruikelijk is
function mailWelp($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Welp");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];

	echo "Welp $id wordt een Weerwolf.\n";

	$verhaal = geefVerhaal($thema,'Welp',0,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailWelp

//aparte functie voor Klaas Vaak omdat slachtoffer ook gemaild moet worden
function mailKlaas($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Slaap";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Klaas Vaak");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];

	echo "Klaas Vaak $id laat $stem slapen.\n";

	$verhaal = geefVerhaal($thema,'Klaas Vaak',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$namen = delArrayElement($namen,0);
	$rollen = delArrayElement($rollen,0);
	$geslachten = delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,'Klaas Vaak',2,$sid);
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
function mailDwaas($id,$gezien,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Ziener");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$resultaat = sqlSel("Spelers","ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$gezien);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);

	echo "Dwaas $id denkt dat $stem een $gezien is.\n";

	$verhaal = geefVerhaal($thema,'Ziener',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailDwaas

//aparte functie voor Goochelaar vanwege STEM en EXTRA_STEM
function mailGoochel($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Goochelaar");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];
	$resultaat = sqlSel("Spelers","ID=$stem1");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$resultaat = sqlSel("Spelers","ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);

	echo "Goochelaar $id verwisselt $stem en $stem2 met elkaar.\n";

	$verhaal = geefVerhaal($thema,'Goochelaar',2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailGoochel

//mailt verhaaltje van WW of VP naar de groep
function mailWWVPActie($spelers,$slachtoffer,$rol,$fase,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$namen = array();
	$rollen = array();
	$geslachten = array();
	$adressen = array();
	$numSlachtoffer = 0;
	if(!empty($slachtoffer)) {
		$resultaat = sqlSel("Spelers","ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
		$numSlachtoffer = 1;
		echo "$rol: $slachtoffer vermoord.\n";
	}
	else {
		echo "$rol: Niemand vermoord.\n";
	}
	foreach($spelers as $id) {
		$resultaat = sqlSel("Spelers","ID=$id");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$rol); //ook als Witte WW: Weerwolf of Vampier
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($spelers),
		$numSlachtoffer,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $id) {
		echo "Mail gestuurd naar $id.\n";
	}
	return;
}//mailWWVPActie

function mailOnschuldig($id,$targets,$stemmen,$fase,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Onschuldige Meisje");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];

	echo (($rol & 1) == 1) ? "Weerwolf" : "Vampier" . 
		": Onschuldige Meisje $id ziet de stemmen.\n";

	$verhaal = geefVerhaal($thema,'Onschuldige Meisje',$fase,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);

	$text .= "<br /><br />";
	foreach($targets as $key => $target) {
		$resultaat = sqlSel("Spelers","ID=$id");
		$speler = sqlFet($resultaat);
		$naam = $speler['NAAM'];
		$text .= "$naam kreeg $stemmen[$key] stemmen.<br />";
	}
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailOnschuldig

//aparte functie vanwege de combinaties STEM en EXTRA_STEM 
//die soms niet/wel nodig zijn
function mailHeksActie($id,$stem1,$stem2,$verhaal,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Heks");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	if(($verhaal & 2) == 2) { //andere speler geredt, dus $stem1 is belangrijk
		$resultaat = sqlSel("Spelers","ID=$stem1");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}
	if(($verhaal & 4) == 4) { //speler gedood, dus $stem2 is belangrijk
		$resultaat = sqlSel("Spelers","ID=$stem2");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	$verhaal = geefVerhaal($thema,"Heks",$verhaal,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $naam.\n";

	return;
}//mailHeks

//mailt verhaaltje van FS naar groep,
//en mailt ook de Betoverden
function mailFSActie($spelers,$betoverd1,$betoverd2,$fase,$sid) {
	$numBetoverd = 0;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Betoverd";
	$thema = $spel['THEMA'];
	$namen = array();
	$rollen = array();
	$geslachten = array();
	$adressen = array();
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","ID=$betoverd1");
		$speler = sqlFet($resultaat);
		$betoverdNaam = array($speler['NAAM']);
		$betoverdRol = array($speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		$betoverdGeslacht = array($geslacht);
		$adres2 = $speler['EMAIL'];
		if(($fase & 1) != 1) {
			$resultaat = sqlSel("Spelers","ID=$betoverd2");
			$speler = sqlFet($resultaat);
			array_push($betoverdNaam,$speler['NAAM']);
			array_push($betoverdRol,$speler['ROL']);
			$geslacht = ($speler['SPELERFLAGS'] & 1);
			array_push($betoverdGeslacht,$geslacht);
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

	foreach($spelers as $id) {
		$resultaat = sqlSel("Spelers","ID=$id");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,"Fluitspeler");
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($spelers),
		$numBetoverd,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $id) {
		echo "Mail gestuurd naar $id.\n";
	}

	if($fase == 9 ) {
		return;
	}
	$numBetoverd += 2;
	$verhaal = geefVerhaal($thema,$rol,$numBetoverd,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($betoverdNaam,$betoverdRol,$betoverdGeslacht,
		"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	foreach($betoverdNaam as $naam) {
		echo "Mail gestuurd naar $naam.\n";
	}
	return;
}//mailFSActie

//mailt de Waarschuwer en zijn keuze
function mailWaarschuwer($id,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Waarschuwing";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Waarschuwer");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler["EXTRA_STEM"];

	$resultaat = sqlSel("Spelers","ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];

	echo "Waarschuwer $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaal($thema,"Waarschuwer",1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	delArrayElement($namen,0);
	delArrayElement($rollen,0);
	delArrayElement($geslachten,0);
	$verhaal = geefVerhaal($thema,"Waarschuwer",2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailWaarschuwer

function mailTestament($id,$fase,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Testament";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array($speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler["STEM"];
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	echo "Burgemeester $id kiest als opvolger: $stem.\n";

	$verhaal = geefVerhaal($thema,"Burgemeester",$fase,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailTestament

function mailZonde($id,$slachtoffers,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Zondebok");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	foreach($slachtoffers as $slachtoffer) {
		$resultaat = sqlSel("Spelers","ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
		echo "Zondebok $id wekt schuldgevoel op in $slachtoffer.\n";
	}

	$verhaal = geefVerhaalGroep2($thema,"Zondebok",1,1,(count($namen)-1),$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);

	//infodump: alle schuldige spelers
	$text .= "<br /><br />";
	$text .= "Je hebt schuldgevoel opgewekt in:<br />";
	$text .= "<ul>";
	foreach($namen as $key => $naam) {
		if($key == 0) {
			continue;
		}
		$text .= "<li>$naam</li>";
	}
	$text .= "</ul>";
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailZonde

function mailAlgemeenVerkiezing($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen"; 
	//pak ontwaakverhaal
	//pak burgemeester-verhaal
	//maak samenvatting
	//mail naar iedereen in maillijst
	$adressen = array();
	$resultaat = sqlSel("Spelers","SID=$sid AND ((SPELERFLAGS & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adres); $i++) {
		$adres .= ", " . $adressen[$i];
	}
	stuurMail($adres,$onderwerp,$text);
}//mailAlgemeenVerkiezing

function mailAlgemeenBrandstapel($vlag,$sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen";
	$text = "";
	$samenvatting = "";
	$auteur = array();
	if(!$vlag) {
		ontwaakVerhaal($text,$samenvatting,$auteur,$spel);
	}
	else {
		verkiezingVerhaal($text,$samenvatting,$auteur,$spel);//TODO maken
	}
	inleidingBrandstapel($text,$samenvatting,$auteur,$spel);//TODO maken
	$text = plakSamenvatting($samenvatting,$text);
	$text = auteur($auteur,$text);
	//pak brandstapel-verhaal
	//maak samenvatting
	//mail naar iedereen in maillijst
	$adressen = array();
	$resultaat = sqlSel("Spelers","SID=$sid AND ((SPELERFLAGS & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adres); $i++) {
		$adres .= ", " . $adressen[$i];
	}
	stuurMail($adres,$onderwerp,$text);
}//mailAlgemeenBrandstapel

function mailAlgemeenInslapen($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen";
	//pak brandstapel-uitslag-verhaal
	//pak nacht-inleiding-verhaal
	//mail naar iedereen in maillijst
	$adressen = array();
	$resultaat = sqlSel("Spelers","SID=$sid AND ((SPELERFLAGS & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adres); $i++) {
		$adres .= ", " . $adressen[$i];
	}
	stuurMail($adres,$onderwerp,$text);
}//mailAlgemeenInslapen

?>
