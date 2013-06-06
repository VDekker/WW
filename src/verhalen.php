<?php

//mailt iedere speler AFZONDERLIJK met diens rol
function mailRolverdeling($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Rol";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"SID=$sid");
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='$rol' AND ((LEVEND & 1) = 1)");
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$tuples = array();
	$adressen = array();
	if($rol == "Weerwolf") {
		$resultaat = sqlSel(3,
			"SID=$sid AND ((LEVEND & 1) = 1) AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel(3,
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
	if(empty($tuples)) {
		echo "Enkel slapende $rol.\n";
		return;
	}
	$adres = $adressen[0];
	for($i = 0; $i < count($adressen); $i++) {
		$adres .= ", " . $adressen[$i];
	}

	$verhaal = geefVerhaalGroep($thema,$rol,0,count($tuples),0,$sid);
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$tuples = array(); // vul een array van slachtoffers
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=3");
	while($speler = sqlFet($resultaat)) {
		array_push($tuples,$speler);
	}

	$resultaat = sqlSel(3,"SID=$sid AND ROL='Heks' AND 
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$vlag = false;

	$resultaat = sqlSel(3,"SID=$sid AND ROL='Jager' AND 
		((LEVEND & 2) = 2) AND ((SPELFLAGS & 4) = 0)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		$vlag = true;
		$tuples = array($speler);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Jager",$fase,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,$deadline,$text,$geswoorden);
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
	$resultaat = sqlSel(3,"LEVEND<>1 AND ID IN
		(SELECT BURGEMEESTER FROM Spellen WHERE SID=$sid)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}
	$speler = sqlFet($resultaat);

	$deadline = geefDeadline($sid);
	$resultaat = sqlFet(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Testament";
	$thema = $spel['THEMA'];

	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$verhaal = geefVerhaal($thema,"Burgemeester",0,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,$deadline,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $namen[0].\n";
	return true;
}//mailBurgWakker

//mailt alle overleden zondebokken met zonde-vlag aan
function mailZondeWakker($sid) {
	//zijn er uberhaupt dode zondebokken?
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Zondebok' AND
		((LEVEND & 2) = 2) AND ((SPELFLAGS & 256) = 256)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}

	$deadline = geefDeadline($sid);
	$resultaat2 = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat2);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	while($speler = sqlFet($resultaat)) {
		$tuples = array($speler);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Zondebok",0,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,$deadline,$text,$geswoorden);
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$rol = $speler['ROL'];
	$adres = $speler['EMAIL'];
	$stem = $speler["$plek"];
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
	}

	echo "$rol $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaal($thema,$rol,$fase,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailActie

//aparte functie voor Dief omdat het slachtoffer ook gemaild moet worden
function mailDief($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp2 = $spel['SNAAM'] . ": Bestolen";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$adres2 = $speler['EMAIL'];
	echo "Dief $id steelt de rol van $stem.\n";

	$verhaal = geefVerhaal($thema,'Dief',2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$tuples = delArrayElement($tuples,0);
	$verhaal = geefVerhaal($thema,'Dief',3,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailDief

//aparte functie voor Cupido omdat de Geliefden ook gemaild moeten worden
function mailCupido($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Geliefde";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];

	$resultaat = sqlSel(3,"ID=$stem1");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$adres2 = $speler['EMAIL'];
	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$adres2 .= ", " . $speler['EMAIL'];

	echo "Cupido $id maakt $stem en $stem2 verliefd op elkaar.\n";

	$verhaal = geefVerhaal($thema,'Cupido',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$tuples = delArrayElement($tuples,0);
	$verhaal = geefVerhaal($thema,'Cupido',2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem1 en $stem2.\n";
	return;
}//mailCupido

//aparte functie voor Opdrachtgever omdat Lijfwacht gemaild moet worden
function mailOpdracht($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Opdrachtgever";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$adres .= ", " . $speler['EMAIL'];

	echo "Opdrachtgever $id stelt $stem aan tot zijn lijfwacht.\n";

	$verhaal = geefVerhaal($thema,'Opdrachtgever',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id en $stem.\n";
	return;
}//mailOpdracht

//aparte functie voor Welp omdat fase ongebruikelijk is
function mailWelp($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];

	echo "Welp $id wordt een Weerwolf.\n";

	$verhaal = geefVerhaal($thema,'Welp',0,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailWelp

//aparte functie voor Klaas Vaak omdat slachtoffer ook gemaild moet worden
function mailKlaas($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Slaap";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$adres2 = $speler['EMAIL'];

	echo "Klaas Vaak $id laat $stem slapen.\n";

	$verhaal = geefVerhaal($thema,'Klaas Vaak',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$tuples = delArrayElement($tuples,0);
	$verhaal = geefVerhaal($thema,'Klaas Vaak',2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailKlaas

//aparte functie voor de Dwaas vanwege $gezien
function mailDwaas($id,$gezien,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	array_push($rollen,$gezien);

	echo "Dwaas $id denkt dat $stem een $gezien is.\n";

	$verhaal = geefVerhaal($thema,'Ziener',1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDwaas($tuples,$gezien,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailDwaas

//aparte functie voor Goochelaar vanwege STEM en EXTRA_STEM
function mailGoochel($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];
	$resultaat = sqlSel(3,"ID=$stem1");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);

	echo "Goochelaar $id verwisselt $stem en $stem2 met elkaar.\n";

	$verhaal = geefVerhaal($thema,'Goochelaar',2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailGoochel

//mailt verhaaltje van WW of VP naar de groep
function mailWWVPActie($spelers,$slachtoffer,$rol,$fase,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$tuples = array();
	$adressen = array();
	$numSlachtoffer = 0;
	if(!empty($slachtoffer)) {
		$resultaat = sqlSel(3,"ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
		$numSlachtoffer = 1;
		echo "$rol: $slachtoffer vermoord.\n";
	}
	else {
		echo "$rol: Niemand vermoord.\n";
	}
	foreach($spelers as $id) {
		$resultaat = sqlSel(3,"ID=$id");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
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
	$text = vulInWW($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $id) {
		echo "Mail gestuurd naar $id.\n";
	}
	return;
}//mailWWVPActie

function mailOnschuldig($id,$targets,$stemmen,$fase,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];

	echo (($rol & 1) == 1) ? "Weerwolf" : "Vampier" . 
		": Onschuldige Meisje $id ziet de stemmen.\n";

	$verhaal = geefVerhaal($thema,'Onschuldige Meisje',$fase,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);

	$text .= "<br /><br />";
	foreach($targets as $key => $target) {
		$resultaat = sqlSel(3,"ID=$id");
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	if(($verhaal & 2) == 2) { //andere speler geredt, dus $stem1 is belangrijk
		$resultaat = sqlSel(3,"ID=$stem1");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
	}
	if(($verhaal & 4) == 4) { //speler gedood, dus $stem2 is belangrijk
		$resultaat = sqlSel(3,"ID=$stem2");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
	}

	$verhaal = geefVerhaal($thema,"Heks",$verhaal,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	return;
}//mailHeks

//mailt verhaaltje van FS naar groep,
//en mailt ook de Betoverden
function mailFSActie($spelers,$betoverd1,$betoverd2,$fase,$sid) {
	$numBetoverd = 0;
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Betoverd";
	$thema = $spel['THEMA'];
	$tuples = array();
	$rollen = array();
	$geslachten = array();
	$adressen = array();
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$betoverd1");
		$speler = sqlFet($resultaat);
		$betoverd = array($speler);
		$adres2 = $speler['EMAIL'];
		if(($fase & 1) != 1) {
			$resultaat = sqlSel(3,"ID=$betoverd2");
			$speler = sqlFet($resultaat);
			array_push($betoverd,$speler);
			$adres2 .= ", " . $speler['EMAIL'];
			$numBetoverd = 2;
			echo "Fluitspelers betoveren $betoverd1 en $betoverd2.\n";
		}
		else {
			$numBetoverd = 1;
			echo "Fluitspelers betoveren enkel $betoverd1.\n";
		}
		$tuples = array_merge($tuples,$betoverd);
	}
	else {
		echo "Fluitspelers stemmen blanco.\n";
	}

	foreach($spelers as $id) {
		$resultaat = sqlSel(3,"ID=$id");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
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
	$text = vulIn($tuples,"",$text,$geswoorden);
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
	$text = vulIn($betoverd,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $adres2.\n";
	return;
}//mailFSActie

//mailt de Waarschuwer en zijn keuze
function mailWaarschuwer($id,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Waarschuwing";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler["EXTRA_STEM"];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($tuples,$speler);
	$adres2 = $speler['EMAIL'];

	echo "Waarschuwer $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaal($thema,"Waarschuwer",1,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	delArrayElement($tuples,0);
	$verhaal = geefVerhaal($thema,"Waarschuwer",2,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailWaarschuwer

function mailTestament($id,$fase,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Testament";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler["STEM"];
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
	}

	echo "Burgemeester $id kiest als opvolger: $stem.\n";

	$verhaal = geefVerhaal($thema,"Burgemeester",$fase,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailTestament

function mailZonde($id,$slachtoffers,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	foreach($slachtoffers as $slachtoffer) {
		$resultaat = sqlSel(3,"ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($tuples,$speler);
		echo "Zondebok $id wekt schuldgevoel op in $slachtoffer.\n";
	}

	$verhaal = geefVerhaalGroep2($thema,"Zondebok",1,1,(count($tuples)-1),$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$text,$geswoorden);

	//infodump: alle schuldige spelers
	$text .= "<br /><br />";
	$text .= "Je hebt schuldgevoel opgewekt in:<br />";
	$text .= "<ul>";
	foreach($tuples as $key => $speler) {
		if($key == 0) {
			continue;
		}
		$text .= "<li>" . $speler['NAAM'] . "</li>";
	}
	$text .= "</ul>";
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailZonde

function mailAlgemeenVerkiezing($sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen"; 
	//pak ontwaakverhaal TODO
	$text = "";
	$samenvatting = "Samenvatting:<br /><br />";
	$auteur = array();
	ontwaakVerhaal($text,$samenvatting,$auteur,$spel);
	//pak burgemeester-verhaal TODO
	//maak samenvatting
	//mail naar iedereen in maillijst
	$adressen = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((SPELERFLAGS & 2) = 2)");
	while($speler = sqlFet($resultaat)) {
		array_push($adressen,$speler['EMAIL']);
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adres); $i++) {
		$adres .= ", " . $adressen[$i];
	}
	stuurMail($adres,$onderwerp,$text);
}//mailAlgemeenVerkiezing

function mailAlgemeenBrandstapel($vlag,$overzicht,$sid) {
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen";
	$text = "";
	$samenvatting = "Samenvatting:<br /><br />";
	$auteur = array();
	if(!$vlag) {
		ontwaakVerhaal($text,$samenvatting,$auteur,$spel);
	}
	else {
		verkiezingVerhaal($text,$samenvatting,$auteur,$overzicht,$spel);
	}
	inleidingBrandstapel($text,$samenvatting,$auteur,$spel);//TODO maken
	$text = plakSamenvatting($samenvatting,$text);
	$text = auteur($auteur,$text);
	//pak brandstapel-verhaal
	//maak samenvatting
	//mail naar iedereen in maillijst
	$adressen = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((SPELERFLAGS & 2) = 2)");
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
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen";
	//pak brandstapel-uitslag-verhaal TODO
	//pak nacht-inleiding-verhaal
	//mail naar iedereen in maillijst
	$adressen = array();
	$resultaat = sqlSel(3,"SID=$sid AND ((SPELERFLAGS & 2) = 2)");
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
