<?php

//mailt iedere speler AFZONDERLIJK met diens rol
function mailRolverdeling($spel) {
	$sid = $spel['SID'];
	$onderwerp = $spel['SNAAM'] . ": Rol";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"SID=$sid");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$tuple = array($speler);
		$adres = $speler['EMAIL'];

		$verhaal = geefVerhaalRolverdeling($thema,$rollen[0],$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];

		$text = vulInDood($tuple,"","",$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Rol naar $id gemaild.\n";
	}//while
	return 0;
}//mailRolverdeling

//voor een gegeven rol, 
//stuurt een mail naar alle spelers met die rol AFZONDERLIJK,
//met het juiste verhaaltje
function mailWakker($rol,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
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
		$verhaal = geefVerhaalGroep($thema,$rol,0,1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulInDood($tuples,"",$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $id.\n";
	}//while
	return;
}//mailWakker

//maakt een groep spelers wakker:
//Weerwolven (inc. Witte), Vampiers of Fluitspelers
function mailGroepWakker($rol,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$deadline = geefDeadline($sid);
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

	$verhaal = geefVerhaalGroep($thema,$rol,0,count($tuples),0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuples);
	$text = vulInDood($tuples,"",$deadline,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $adres.\n";
	
	return;
}//mailGroepWakker

//wekt alle Heksen afzonderlijk: geeft ook namen van slachtoffers mee
//(alle nieuw_dode spelers; deze kunnen geredt worden)
function mailHeksWakker($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
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
		$verhaal = geefVerhaalGroep($thema,"Heks",0,1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulInDood($tuples,"",$deadline,$text,$geswoorden);

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
function mailJagerWakker($fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
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
		$verhaal = geefVerhaalGroep($thema,"Jager",$fase,$1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulInDood($tuples,"",$deadline,$text,$geswoorden);
		$text = keuzeJager($text,$speler['NAAM'],$sid);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $namen[0].\n";
	}//while
	return $vlag;
}//mailJagerWakker

//mailt de Burgemeester wakker als hij dood is (voor testament)
function mailBurgWakker($spel) {
	$burg = $spel['BURGEMEESTER'];

	//is er een dode burgemeester?
	$resultaat = sqlSel(3,"LEVEND<>1 AND ID=$burg");
	if(sqlNum($resultaat) == 0) {
		return false;
	}

	//zo ja, ga door
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$deadline = geefDeadline($sid);
	$onderwerp = $spel['SNAAM'] . ": Testament";
	$thema = $spel['THEMA'];

	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$verhaal = geefVerhaalGroep($thema,"Burgemeester",0,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuples,"",$deadline,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $namen[0].\n";
	return true;
}//mailBurgWakker

//mailt alle overleden zondebokken met zonde-vlag aan
function mailZondeWakker($spel) {
	$sid = $spel['SID'];

	//zijn er uberhaupt dode zondebokken?
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Zondebok' AND
		((LEVEND & 2) = 2) AND ((SPELFLAGS & 256) = 256)");
	if(sqlNum($resultaat) == 0) {
		return false;
	}

	$ronde = $spel['RONDE'];
	$deadline = geefDeadline($sid);
	$resultaat2 = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat2);
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	while($speler = sqlFet($resultaat)) {
		$tuples = array($speler);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaalGroep($thema,"Zondebok",0,1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulInDood($tuples,"",$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $namen[0].\n";
	}//while
	return true;
}//mailZondeWakker


//standaard functie voor mailen van een speler-actie 
//(met slachtoffer opgeslagen in 'plek')
//blanco werkt ook
function mailActie($id,$fase,$spel,$plek) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$tuplesB = array();
	$rol = $speler['ROL'];
	$adres = $speler['EMAIL'];
	$stem = $speler[$plek];
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
	}

	echo "$rol $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaalGroep($thema,$rol,$fase,1,count($tuplesB),
		$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailActie

//aparte functie voor Dief omdat het slachtoffer ook gemaild moet worden
//#dief en #slachtoffer (tuplesA en tuplesB) zijn altijd 1
function mailDief($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp2 = $spel['SNAAM'] . ": Bestolen";
	$thema = $spel['THEMA'];
	
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];
	
	echo "Dief $id steelt de rol van $stem.\n";

	$verhaal = geefVerhaalGroep($thema,'Dief',2,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$verhaal = geefVerhaalGroep($thema,'Dief',3,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesB,"","",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailDief

//aparte functie voor Cupido omdat de Geliefden ook gemaild moeten worden
//hierbij altijd 1 Cupido, en 2 Geliefden
function mailCupido($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Geliefde";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];

	$resultaat = sqlSel(3,"ID=$stem1");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];
	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($tuplesB,$speler);
	$adres2 .= ", " . $speler['EMAIL'];

	echo "Cupido $id maakt $stem en $stem2 verliefd op elkaar.\n";

	$verhaal = geefVerhaalGroep($thema,'Cupido',1,1,2,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$verhaal = geefVerhaalGroep($thema,'Cupido',2,2,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesB,"","",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem1 en $stem2.\n";
	return;
}//mailCupido

//aparte functie voor Opdrachtgever omdat Lijfwacht gemaild moet worden
//altijd 1 Opdrachtgever en 1 Lijfwacht
function mailOpdracht($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Opdrachtgever";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres .= ", " . $speler['EMAIL'];

	echo "Opdrachtgever $id stelt $stem aan tot zijn lijfwacht.\n";

	$verhaal = geefVerhaalGroep($thema,'Opdrachtgever',1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id en $stem.\n";
	return;
}//mailOpdracht

//aparte functie voor Welp omdat fase ongebruikelijk is
function mailWelp($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];

	echo "Welp $id wordt een Weerwolf.\n";

	$verhaal = geefVerhaalGroep($thema,'Welp',0,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,"","",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailWelp

//aparte functie voor Klaas Vaak omdat slachtoffer ook gemaild moet worden
//altijd 1 Klaas Vaak en 1 slachtoffer
function mailKlaas($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Slaap";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];

	echo "Klaas Vaak $id laat $stem slapen.\n";

	$verhaal = geefVerhaalGroep($thema,'Klaas Vaak',1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$verhaal = geefVerhaalGroep($thema,'Klaas Vaak',2,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesB,"","",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailKlaas

//aparte functie voor de Dwaas vanwege $gezien
function mailDwaas($id,$gezien,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$tupleA = sqlFet($resultaat);
	$adres = $tupleA['EMAIL'];
	$stem = $tupleA['STEM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$tupleB = sqlFet($resultaat);

	echo "Dwaas $id denkt dat $stem een $gezien is.\n";

	$verhaal = geefVerhaalGroep($thema,'Ziener',1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDwaas($tupleA,$tupleB,$gezien,$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailDwaas

//aparte functie voor Goochelaar vanwege STEM en EXTRA_STEM
//altijd 1 goochelaar en 2 slachtoffers
function mailGoochel($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];

	$resultaat = sqlSel(3,"ID=$stem1");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);

	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($tuplesB,$speler);

	echo "Goochelaar $id verwisselt $stem en $stem2 met elkaar.\n";

	$verhaal = geefVerhaalGroep($thema,'Goochelaar',2,1,2,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailGoochel

//mailt verhaaltje van WW of VP naar de groep
function mailWWVPActie($spelers,$slachtoffer,$rol,$fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	$tuplesA = array();
	$adressen = array();
	foreach($spelers as $id) {
		$resultaat = sqlSel(3,"ID=$id");
		$speler = sqlFet($resultaat);
		array_push($tuplesA,$speler);
		array_push($adressen,$speler['EMAIL']);
	}

	$tuplesB = array();
	if(!empty($slachtoffer)) {
		$resultaat = sqlSel(3,"ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
		echo "$rol: $slachtoffer vermoord.\n";
	}
	else {
		echo "$rol: Niemand vermoord.\n";
	}

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($tuplesA),
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInWW($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $id) {
		echo "Mail gestuurd naar $id.\n";
	}
	return;
}//mailWWVPActie

function mailOnschuldig($id,$targets,$stemmen,$fase,$rol,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];
	
	$resultaat = sqlSel(3,"ID=$id");
	$onschuldig = sqlFet($resultaat);
	$tuplesA = array($onschuldig);
	$adres = $onschuldig['EMAIL'];

	//maak alvast een overzicht en vul array met targets
	$tuplesB = array();
	$overzicht = "";
	foreach($targets as $key => $target) {
		$resultaat = sqlSel(3,"ID=$target");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
		$naam = $speler['NAAM'];
		$overzicht .= "$naam kreeg $stemmen[$key] stemmen.<br />";
	}

	echo (($rol & 1) == 1) ? "Weerwolf" : "Vampier" . 
		": Onschuldige Meisje $id ziet de stemmen.\n";

	$verhaal = geefVerhaalGroep2($thema,'Onschuldige Meisje',$fase,1,
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);

	$text .= "<br /><br />";
	$text .= $overzicht;
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailOnschuldig

//aparte functie vanwege de combinaties STEM en EXTRA_STEM 
//die soms niet/wel nodig zijn
function mailHeksActie($id,$stem1,$stem2,$verhaal,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];

	$tuplesB = array();
	if(($verhaal & 2) == 2) { //andere speler geredt, dus $stem1 is belangrijk
		$resultaat = sqlSel(3,"ID=$stem1");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
	}
	if(($verhaal & 4) == 4) { //speler gedood, dus $stem2 is belangrijk
		$resultaat = sqlSel(3,"ID=$stem2");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
	}

	$verhaal = geefVerhaalGroep($thema,"Heks",$verhaal,1,count($tuplesB),
		$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	return;
}//mailHeks

//mailt verhaaltje van FS naar groep,
//en mailt ook de Betoverden
function mailFSActie($spelers,$betoverd1,$betoverd2,$fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Betoverd";
	$thema = $spel['THEMA'];

	$tuplesA = array();
	$adressen = array();
	foreach($spelers as $id) {
		$resultaat = sqlSel(3,"ID=$id");
		$speler = sqlFet($resultaat);
		array_push($tuplesA,$speler);
		array_push($adressen,$speler['EMAIL']);
	}

	$tuplesB = array();
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$betoverd1");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
		$adres2 = $speler['EMAIL'];

		if(($fase & 1) != 1) {
			$resultaat = sqlSel(3,"ID=$betoverd2");
			$speler = sqlFet($resultaat);
			array_push($tuplesB,$speler);
			$adres2 .= ", " . $speler['EMAIL'];
			echo "Fluitspelers betoveren $betoverd1 en $betoverd2.\n";
		}
		else {
			echo "Fluitspelers betoveren enkel $betoverd1.\n";
		}
	}
	else {
		echo "Fluitspelers stemmen blanco.\n";
	}

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($tuplesA),
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}

	stuurMail($adres,$onderwerp,$text);
	foreach($spelers as $id) {
		echo "Mail gestuurd naar $id.\n";
	}

	if($fase == 9 ) {
		return;
	}

	//mail de Betoverden
	$verhaal = geefVerhaalGroep($thema,$rol,(count($tuplesB)+2),
		count($tuplesB),0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesB,"","",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp2,$text);
	echo "Mail gestuurd naar $adres2.\n";
	return;
}//mailFSActie

//mailt de Waarschuwer en zijn keuze (afzonderlijk)
function mailWaarschuwer($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$onderwerp = $spel['SNAAM'] . ": Waarschuwing";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler["EXTRA_STEM"];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];

	echo "Waarschuwer $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaalGroep($thema,"Waarschuwer",1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";

	$verhaal = geefVerhaalGroep($thema,"Waarschuwer",2,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesB,"","",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres2,$onderwerp,$text);
	echo "Mail gestuurd naar $stem.\n";
	return;
}//mailWaarschuwer

function mailTestament($id,$fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Testament";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler["STEM"];

	$tuplesB = array();
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
	}

	echo "Burgemeester $id kiest als opvolger: $stem.\n";

	$verhaal = geefVerhaalGroep($thema,"Burgemeester",$fase,1,
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailTestament

function mailZonde($id,$slachtoffers,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$onderwerp = $spel['SNAAM'] . ": Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];

	$tuplesB = array();
	foreach($slachtoffers as $slachtoffer) {
		$resultaat = sqlSel(3,"ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
		echo "Zondebok $id wekt schuldgevoel op in $slachtoffer.\n";
	}

	$verhaal = geefVerhaalGroep2($thema,"Zondebok",1,1,
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesA,$tuplesB,"",$text,$geswoorden);

	//infodump: alle schuldige spelers
	$text .= "<br /><br />";
	$text .= "Je hebt schuldgevoel opgewekt in:<br />";
	$text .= "<ul>";
	foreach($tuplesB as $key => $speler) {
		$text .= "<li>" . $speler['NAAM'] . "</li>";
	}
	$text .= "</ul>";
	$text = auteur($auteur,$text);

	stuurMail($adres,$onderwerp,$text);
	echo "Mail gestuurd naar $id.\n";
	return;
}//mailZonde

function mailAlgemeenVerkiezing($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen"; 

	//pak ontwaakverhaal
	$text = "";
	$samenvatting = "";
	$auteur = array();
	ontwaakVerhaal($text,$samenvatting,$auteur,$spel);

	//pak burgemeester-verhaal
	verkiezingInleiding($text,$samenvatting,$auteur,$spel);
	
	//samenvatting: levende en dode spelers
	$samenvatting .= "<br />";
	$samenvatting .= spelerOverzicht($spel);

	//en voeg samenvatting samen met text
	$text = plakSamenvatting($samenvatting,$text);
	$text = auteurMeerdere($auteur,$text);

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

function mailAlgemeenBrandstapel($vlag,$overzicht,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
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
		verkiezingUitslag($text,$samenvatting,$auteur,$overzicht,$spel);
	}

	//pak brandstapel-verhaal
	brandstapelInleiding($text,$samenvatting,$auteur,$spel);
	
	//maak samenvatting
	$samenvatting .= "<br />";
	$samenvatting .= spelerOverzicht($spel);

	//en voeg samenvatting samen met text
	$text = plakSamenvatting($samenvatting,$text);
	$text = auteurMeerdere($auteur,$text);

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

function mailAlgemeenInslapen($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$sid = $spel['SID'];
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Algemeen";
	$tuplesL = array();

	//pak brandstapel-uitslag-verhaal TODO
	
	//pak nacht-inleiding-verhaal
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	shuffle($tuplesL);
	$verhaal = geefVerhaalGroep($thema,"Algemeen",6,count($tuplesL),
		0,$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesL,"","",$text,$geswoorden);
	$samenvatting .= "De Nacht begint.<br />";
	
	//en voeg samenvatting samen met text
	$text = plakSamenvatting($samenvatting,$text);
	$text = auteurMeerdere($auteur,$text);

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text);
}//mailAlgemeenInslapen

function mailInleiding($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$snel = $spel['SNELHEID'];
	$streng = $spel['STRENGHEID'];
	$aantal = $spel['LEVEND'];
	$onderwerp = "$snaam: Begin";
	$tuplesL = array();

	//pak alle spelers
	$resultaat = sqlSel(3,"SID=$sid");
	while($spelers = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	shuffle($tuplesL);

	//pak het verhaal
	$verhaal = geefVerhaalGroep($thema,"Algemeen"-1,$aantal,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDood($tuplesL,"","",$text,$geswoorden);

	//maak de samenvatting
	$samenvatting = "Het spel $snaam is begonnen.<br />";
	$samenvatting .= "Het thema is: $thema.<br />";
	$samenvatting .= "De snelheid is: $snel.<br />";
	$samenvatting .= "De strengheid is: $streng.<br />";
	$samenvatting .= "Het aantal spelers is: $aantal.<br />";
	$samenvatting .= "<br />";
	$samenvatting .= "Nacht 1 begint.<br />";
	$samenvatting .= "<br />";

	//voeg een overzicht van spelers (namen, geslachten en email-adressen) toe
	$samenvatting .= "<table border='1'><tr>";
	$samenvatting .= "<th>Naam</th><th>Geslacht</th><th>Email</th>";
	$samenvatting .= "</tr>";
	foreach($tuplesL as $speler) {
		$naam = $speler['NAAM'];
		$geslacht = ($speler['SPELERFLAGS'] & 1)? "Man" : "Vrouw";
		$adres = $speler['EMAIL'];
		$samenvatting .= "<tr>";
		$samenvatting .= "<th>$naam</th>";
		$samenvatting .= "<th>$geslacht</th>";
		$samenvatting .= "<th>$adres</th>";
		$samenvatting .= "</tr>";
	}
	$samenvatting .= "</table>";

	//voeg samenvatting achter het verhaal
	$text = plakSamenvatting($samenvatting,$text);
	$text = auteur($auteur,$text);

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text);
}

?>
