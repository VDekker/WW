<?php

//mailt iedere speler AFZONDERLIJK met diens rol
function mailRolverdeling($spel) {
	$sid = $spel['SID'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Rol";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"SID=$sid");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$naam = $speler['NAAM'];
		$tuple = array($speler);
		$adres = $speler['EMAIL'];

		$verhaal = geefVerhaalRolverdeling($thema,$speler['ROL'],$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];

		$text = vulIn($tuple,"","",$text,$geswoorden);

		stuurMail($adres,$onderwerp,$text,array($auteur));
		schrijfLog($sid,"Rol naar $naam gemaild.\n");
	}//while
	return 0;
}//mailRolverdeling

//voor een gegeven rol, 
//stuurt een mail naar alle spelers met die rol AFZONDERLIJK,
//met het juiste verhaaltje
function mailWakker($rol,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel(3,"SID=$sid AND ROL='$rol' AND ((LEVEND & 1) = 1)");
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$naam = $speler['NAAM'];
		$tuples = array($speler);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,$rol,0,1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,"",$deadline,$text,$geswoorden);

		$text .= "<br /><br />-=-=-=-<br /><br />";
		$text .= "Je hebt tot $deadline om je keuze te maken.<br />";
		$text = keuzeVeel($text,$speler,$rol,$sid);

		stuurMail($adres,$onderwerp,$text,array($auteur));
		schrijfLog($sid,"$rol: Mail gestuurd naar $naam (wakker).\n");
	}//while
	return;
}//mailWakker


//TODO
//Vanaf hier verder met schrijfLogs. 
//Also: al het regelen/fases nog doen met schrijfLogs

//maakt een groep spelers wakker:
//Weerwolven (inc. Witte), Vampiers of Fluitspelers
function mailGroepWakker($rol,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];
	$deadline = geefDeadline($sid);
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
		schrijfLog($sid,"Geen levende $rol te bekennen.\n");
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
		schrijfLog($sid,"Enkel slapende $rol.\n");
		return;
	}
	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", " . $adressen[$i];
	}

	$verhaal = geefVerhaal($thema,$rol,0,count($tuples),0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuples);
	$text = vulIn($tuples,"",$deadline,$text,$geswoorden);

	$text .= "<br /><br />-=-=-=-<br /><br />";
	if(count($adressen) > 1) {
		$text .= "Jullie hebben ";
	}
	else {
		$text .= "Je hebt ";
	}
	$text .= "tot $deadline om je keuze te maken.<br />";
	$text = keuzeGroep($text,$rol,$sid);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"$rol: Mail gestuurd naar $adres (wakker).\n");
	
	return;
}//mailGroepWakker

//wekt alle Heksen afzonderlijk: geeft ook namen van slachtoffers mee
//(alle nieuw_dode spelers; deze kunnen geredt worden)
function mailHeksWakker($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];
	$deadline = geefDeadline($sid);
	$tuplesD = array(); // vul een array van slachtoffers
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=3");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesD,$speler);
	}
	$aantal = count($tuplesD);

	$resultaat = sqlSel(3,"SID=$sid AND ROL='Heks' AND 
		((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		$adres = $speler['EMAIL'];
		$key = array_search($speler,$tuplesD);
		if($key === false) {
			$verhaal = geefVerhaal2($thema,"Heks",0,1,$aantal,$ronde,$sid);
			$text = $verhaal['VERHAAL'];
			$geswoorden = $verhaal['GESLACHT'];
			$auteur = $verhaal['AUTEUR'];
			$text = vulIn(array($speler),$tuplesD,$deadline,$text,$geswoorden);
		}
		else {
			$tuplesTemp = delArrayElement($tuplesD,$key);
			$verhaal = geefVerhaal2($thema,"Heks",3,1,$aantal-1,$ronde,$sid);
			$text = $verhaal['VERHAAL'];
			$geswoorden = $verhaal['GESLACHT'];
			$auteur = $verhaal['AUTEUR'];
			$text = vulIn(array($speler),$tuplesTemp,
				$deadline,$text,$geswoorden);
		}

		$text .= "<br /><br />-=-=-=-<br /><br />";
		$text .= "Je hebt tot $deadline om je keuze te maken.<br />";
		$text = keuzeHeks($text,$speler['NAAM'],$tuplesD,$sid);

		stuurMail($adres,$onderwerp,$text,array($auteur));
		schrijfLog($sid,"Heks: Mail gestuurd naar $adres (wakker).\n");
	}//while
	return;
}//mailHeksWakker

//mailt alle overleden jagers wakker die niet geschoten hebben
function mailJagerWakker($fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];
	$deadline = geefDeadline($sid);
	$vlag = false;

	$resultaat = sqlSel(3,"SID=$sid AND ROL='Jager' AND 
		((LEVEND & 2) = 2) AND ((SPELFLAGS & 128) = 0)");
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
		$naam = $speler['NAAM'];
		$verhaal = geefVerhaal($thema,"Jager",$fase,1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,"",$deadline,$text,$geswoorden);

		$text .= "<br /><br />-=-=-=-<br /><br />";
		$text .= "Je hebt tot $deadline om je keuze te maken.<br />";
		$text = keuzeJager($text,$naam,$sid);

		stuurMail($adres,$onderwerp,$text,array($auteur));
		schrijfLog($sid,"Jager: Mail gestuurd naar $naam (wakker).\n");
	}//while
	return $vlag;
}//mailJagerWakker

//mailt de Burgemeester wakker als hij dood is (voor testament)
function mailBurgWakker($spel) {
	$burg = $spel['BURGEMEESTER'];
	if(empty($burg)) {
		return;
	}

	//is er een dode burgemeester?
	$resultaat = sqlSel(3,"LEVEND<>1 AND ID=$burg");
	if(sqlNum($resultaat) == 0) {
		return false;
	}

	//zo ja, ga door
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Testament";
	$thema = $spel['THEMA'];
	$deadline = geefDeadline($sid);

	$speler = sqlFet($resultaat);
	$tuples = array($speler);
	$adres = $speler['EMAIL'];
	$naam = $speler['NAAM'];
	$verhaal = geefVerhaal($thema,"Burgemeester",0,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuples,"",$deadline,$text,$geswoorden);

	$text .= "<br /><br />-=-=-=-<br /><br />";
	$text .= "Je hebt tot $deadline om je keuze te maken.<br />";
	$text = keuzeTestament($text,$sid);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Burgemeester: Mail gestuurd naar $naam (wakker).\n");
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
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];
	$deadline = geefDeadline($sid);

	while($speler = sqlFet($resultaat)) {
		$tuples = array($speler);
		$adres = $speler['EMAIL'];
		$naam = $speler['NAAM'];
		$verhaal = geefVerhaal($thema,"Zondebok",0,1,0,$ronde,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($tuples,"",$deadline,$text,$geswoorden);
	
		$text .= "<br /><br />-=-=-=-<br /><br />";
		$text .= "Je hebt tot $deadline om je keuze te maken.<br />";
		$text = keuzeZonde($text,$naam,$sid);

		stuurMail($adres,$onderwerp,$text,array($auteur));
		schrijfLog($sid,"Zondebok: Mail gestuurd naar $naam (wakker).\n");
	}//while
	return true;
}//mailZondeWakker

//standaard functie voor mailen van een speler-actie 
//(met slachtoffer opgeslagen in 'plek')
//blanco werkt ook
function mailActie($id,$fase,$spel,$plek) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$tuplesB = array();
	$rol = $speler['ROL'];
	$stem = $speler[$plek];
	$adres = $speler['EMAIL'];
	$naam = $speler['NAAM'];
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
	}

	schrijfLog($sid,"$rol $naam kiest $stem.\n");

	$verhaal = geefVerhaal($thema,$rol,$fase,1,count($tuplesB),
		$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"$rol: Mail gestuurd naar $naam (actie).\n");
	return;
}//mailActie

//aparte functie voor Dief omdat het slachtoffer ook gemaild moet worden
//#dief en #slachtoffer (tuplesA en tuplesB) zijn altijd 1
function mailDief($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$onderwerp2 = "$snaam: Bestolen";
	$thema = $spel['THEMA'];
	
	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$naam = $speler['NAAM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];
	$naam2 = $speler['NAAM'];
	
	$verhaal = geefVerhaal($thema,'Dief',2,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Dief: Mail gestuurd naar $naam (actie).\n");

	$verhaal = geefVerhaal($thema,'Dief',3,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesB,"","",$text,$geswoorden);

	stuurMail($adres2,$onderwerp2,$text);
	schrijfLog($sid,"Dief: Mail gestuurd naar $naam2 (slachtoffer).\n");
	return;
}//mailDief

//aparte functie voor Cupido omdat de Geliefden ook gemaild moeten worden
//hierbij altijd 1 Cupido, en 2 Geliefden
function mailCupido($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$onderwerp = "$snaam: Geliefde";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$naam = $speler['NAAM'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];

	$resultaat = sqlSel(3,"ID=$stem1");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];
	$naam2 = $speler['NAAM'];
	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($tuplesB,$speler);
	$adres2 .= ", " . $speler['EMAIL'];
	$naam3 = $speler['NAAM'];

	schrijfLog($sid,"Cupido $naam maakt $naam2 en $naam3 verliefd op elkaar.\n");

	//maak Cupido's verhaaltje en stuur deze naar hem
	$verhaal = geefVerhaal($thema,'Cupido',1,1,2,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesB);
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Cupido: Mail gestuurd naar $naam (actie).\n");

	//mail de geliefden met hun verhaaltje
	$verhaal = geefVerhaal($thema,'Cupido',3,2,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesB,"","",$text,$geswoorden);

	stuurMail($adres2,$onderwerp2,$text,array($auteur));
	schrijfLog($sid,"Cupido: Mail gestuurd naar $naam2 en $naam3 (slachtoffer).\n");
	return;
}//mailCupido

//aparte functie voor Opdrachtgever omdat Lijfwacht gemaild moet worden
//altijd 1 Opdrachtgever en 1 Lijfwacht
function mailOpdracht($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Opdrachtgever";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$naam = $speler['NAAM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres .= ", " . $speler['EMAIL'];
	$naam2 = $speler['NAAM'];

	schrijfLog($sid,"Opdrachtgever $naam stelt $naam2 aan tot zijn lijfwacht.\n");

	$verhaal = geefVerhaal($thema,'Opdrachtgever',1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Opdrachtgever: Mail gestuurd naar $naam en $naam2 (actie).\n");
	return;
}//mailOpdracht

//aparte functie voor Welp omdat fase ongebruikelijk is
function mailWelp($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$naam = $speler['NAAM'];

	$verhaal = geefVerhaal($thema,'Welp',0,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,"","",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Welp: Mail gestuurd naar $naam (actie).\n");
	return;
}//mailWelp

//aparte functie voor Klaas Vaak omdat slachtoffer ook gemaild moet worden
//altijd 1 Klaas Vaak en 1 slachtoffer
function mailKlaas($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$onderwerp = "$snaam: Slaap";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$naam = $speler['NAAM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];
	$naam2 = $speler['NAAM'];

	schrijfLog($sid,"Klaas Vaak $naam laat $naam2 slapen.\n");

	$verhaal = geefVerhaal($thema,'Klaas Vaak',1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Klaas Vaak: Mail gestuurd naar $naam (actie).\n");

	$verhaal = geefVerhaal($thema,'Klaas Vaak',2,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesB,"","",$text,$geswoorden);

	stuurMail($adres2,$onderwerp2,$text,array($auteur));
	schrijfLog($sid,"Klaas Vaak: Mail gestuurd naar $naam2 (slachtoffer).\n");
	return;
}//mailKlaas

//aparte functie voor de Dwaas vanwege $gezien
function mailDwaas($id,$gezien,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$naam = $speler['NAAM'];
	$tupleA = array($speler);

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$naam2 = $speler['NAAM'];
	$tupleB = array($speler);

	schrijfLog($sid,"Dwaas $naam denkt dat $naam2 een $gezien is.\n");

	$verhaal = geefVerhaal($thema,'Ziener',1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulInDwaas($tupleA,$tupleB,$gezien,$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Dwaas: Mail gestuurd naar $naam (actie).\n");
	return;
}//mailDwaas

//aparte functie voor Goochelaar vanwege STEM en EXTRA_STEM
//altijd 1 goochelaar en 2 slachtoffers
function mailGoochel($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];
	$naam = $speler['NAAM'];

	$resultaat = sqlSel(3,"ID=$stem1");
	$speler = sqlFet($resultaat);
	$naam2 = $speler['NAAM'];
	$tuplesB = array($speler);

	$resultaat = sqlSel(3,"ID=$stem2");
	$speler = sqlFet($resultaat);
	$naam3 = $speler['NAAM'];
	array_push($tuplesB,$speler);

	schrijfLog($sid,"Goochelaar $naam verwisselt $naam2 en $naam3 met elkaar.\n");

	$verhaal = geefVerhaal($thema,'Goochelaar',2,1,2,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesB);
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Goochelaar: Mail gestuurd naar $naam (actie).\n");
	return;
}//mailGoochel

//mailt verhaaltje van WW of VP naar de groep
function mailWWVPActie($spelers,$slachtoffer,$rol,$fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
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
		$slachtNaam = $speler['NAAM'];
		array_push($tuplesB,$speler);
		schrijfLog($sid,"$rol: $slachtNaam vermoord.\n");
	}
	else {
		schrijfLog($sid,"$rol: Niemand vermoord.\n");
	}

	$verhaal = geefVerhaal($thema,$rol,$fase,count($tuplesA),
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesA);
	$text = vulInWW($tuplesA,$tuplesB,"",$text,$geswoorden);

	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"$rol: Mail gestuurd naar $adres (actie).\n");
	return;
}//mailWWVPActie

function mailOnschuldig($id,$targets,$stemmen,$fase,$rol,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];
	
	$resultaat = sqlSel(3,"ID=$id");
	$onschuldig = sqlFet($resultaat);
	$tuplesA = array($onschuldig);
	$adres = $onschuldig['EMAIL'];
	$onschuldigNaam = $onschuldig['NAAM'];

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

	$log = (($rol & 1) == 1) ? "Weerwolf" : "Vampier" . 
		": Onschuldige Meisje $onschuldigNaam ziet de stemmen.\n";
	schrijfLog($sid,$log);

	$verhaal = geefVerhaal2($thema,'Onschuldige Meisje',$fase,1,
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesB);
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	$text .= "<br /><br />";
	$text .= $overzicht;

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,
		"Onschuldige Meisje: Mail gestuurd naar $onschuldigNaam (actie).\n");
	return;
}//mailOnschuldig

//aparte functie vanwege de combinaties STEM en EXTRA_STEM 
//die soms niet/wel nodig zijn
function mailHeksActie($id,$stem1,$stem2,$verhaal,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$naam = $speler['NAAM'];

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

	$verhaal = geefVerhaal($thema,"Heks",$verhaal,1,count($tuplesB),
		$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Heks: Mail gestuurd naar $naam (actie).\n");

	return;
}//mailHeks

//mailt verhaaltje van FS naar groep,
//en mailt ook de Betoverden
function mailFSActie($spelers,$betoverd1,$betoverd2,$fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$onderwerp = "$snaam: Betoverd";
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
	if($fase != 9 && $fase != 5) {
		$resultaat = sqlSel(3,"ID=$betoverd1");
		$speler = sqlFet($resultaat);
		array_push($tuplesB,$speler);
		$adres2 = $speler['EMAIL'];
		$betNaam1 = $speler['NAAM'];

		if(($fase & 1) != 1) {
			$resultaat = sqlSel(3,"ID=$betoverd2");
			$speler = sqlFet($resultaat);
			array_push($tuplesB,$speler);
			$adres2 .= ", " . $speler['EMAIL'];
			$betNaam2 = $speler['NAAM'];
			schrijfLog($sid,"Fluitspelers betoveren $betNaam1 en " . 
				"$betNaam2.\n");
		}
		else {
			schrijfLog($sid,"Fluitspelers betoveren enkel $betNaam1.\n");
		}
	}
	else {
		schrijfLog($sid,"Fluitspelers stemmen blanco.\n");
	}

	//pak verhaal voor de Fluitspelers en mail deze
	$verhaal = geefVerhaal($thema,$rol,$fase,count($tuplesA),
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesA);
	shuffle($tuplesB);
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	$adres = $adressen[0];
	for($i = 1; $i < count($adressen); $i++) {
		$adres .= ", $adressen[$i]";
	}

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Fluitspelers: Mail gestuurd naar $adres (actie).\n");

	if($fase == 9 ) {
		return;
	}

	//mail de Betoverden
	$verhaal = geefVerhaal($thema,$rol,(count($tuplesB)+2),
		count($tuplesB),0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesB,"","",$text,$geswoorden);

	stuurMail($adres2,$onderwerp2,$text,array($auteur));
	schrijfLog($sid,"Fluitspelers: Mail gestuurd naar $adres2 (slachtoffer).\n");
	return;
}//mailFSActie

//mailt de Waarschuwer en zijn keuze (afzonderlijk)
function mailWaarschuwer($id,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$onderwerp = "$snaam: Waarschuwing";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler["EXTRA_STEM"];
	$naam = $speler['NAAM'];

	$resultaat = sqlSel(3,"ID=$stem");
	$speler = sqlFet($resultaat);
	$tuplesB = array($speler);
	$adres2 = $speler['EMAIL'];
	$naam2 = $speler['NAAM'];

	schrijfLog($sid,"Waarschuwer $naam heeft op $naam2 gestemd.\n");

	$verhaal = geefVerhaal($thema,"Waarschuwer",1,1,1,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Waarschuwer: Mail gestuurd naar $naam (actie).\n");

	$verhaal = geefVerhaal($thema,"Waarschuwer",2,1,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesB,"","",$text,$geswoorden);

	stuurMail($adres2,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Waarchuwer: Mail gestuurd naar $naam2 (slachtoffer).\n");
	return;
}//mailWaarschuwer

//mailt het testament van de Burgemeester (naar de dode burg)
function mailTestament($id,$fase,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Testament";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$stem = $speler["STEM"];
	$naam = $speler['NAAM'];

	$tuplesB = array();
	if($fase != 9) {
		$resultaat = sqlSel(3,"ID=$stem");
		$speler = sqlFet($resultaat);
		$naam2 = speler['NAAM'];
		array_push($tuplesB,$speler);
	}

	schrijfLog($sid,"Burgemeester $naam kiest als opvolger: $naam2.\n");

	$verhaal = geefVerhaal($thema,"Burgemeester",$fase,1,
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Burgemeester: Mail gestuurd naar $naam (actie).\n");
	return;
}//mailTestament

//mailt het verhaaltje van de Zondebok
function mailZonde($id,$slachtoffers,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$onderwerp = "$snaam: Actie";
	$thema = $spel['THEMA'];

	$resultaat = sqlSel(3,"ID=$id");
	$speler = sqlFet($resultaat);
	$tuplesA = array($speler);
	$adres = $speler['EMAIL'];
	$naam = $speler['NAAM'];

	$tuplesB = array();
	foreach($slachtoffers as $slachtoffer) {
		$resultaat = sqlSel(3,"ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		$naam2 = $speler['NAAM'];
		array_push($tuplesB,$speler);
		schrijfLog($sid,"Zondebok $naam wekt schuldgevoel op in $naam2.\n");
	}

	$verhaal = geefVerhaal2($thema,"Zondebok",1,1,
		count($tuplesB),$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesB);
	$text = vulIn($tuplesA,$tuplesB,"",$text,$geswoorden);

	//infodump: alle schuldige spelers
	$text .= "<br /><br />";
	$text .= "Je hebt schuldgevoel opgewekt in:<br />";
	$text .= "<ul>";
	foreach($tuplesB as $key => $speler) {
		$text .= "<li>" . $speler['NAAM'] . "</li>";
	}
	$text .= "</ul>";

	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Zondebok: Mail gestuurd naar $naam (actie).\n");
	return;
}//mailZonde

function mailAlgemeenVerkiezing($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Dag $ronde"; 

	//pak ontwaakverhaal
	$text = "";
	$samenvatting = "";
	$auteur = array();
	ontwaakVerhaal($text,$samenvatting,$auteur,$spel);

	//pak burgemeester-verhaal
	verkiezingInleiding($text,$samenvatting,$auteur,$spel);
	
	//samenvatting: levende en dode spelers
	$samenvatting .= spelerOverzicht($spel);

	//en voeg samenvatting samen met text
	$text = plakSamenvatting($samenvatting,$text);

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text,$auteur);
	schrijfLog($sid,"Burgemeester: Mail gestuurd naar $adres (verkiezing).\n");
}//mailAlgemeenVerkiezing

function mailAlgemeenBrandstapel($vlag,$overzicht,$spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Dag $ronde";
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
	$samenvatting .= spelerOverzicht($spel);

	//en voeg samenvatting samen met text
	$text = plakSamenvatting($samenvatting,$text);

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text,$auteur);
	schrijfLog($sid,"Brandstapel: Mail gestuurd naar $adres (inleiding).\n");
}//mailAlgemeenBrandstapel

function mailAlgemeenInslapen($spel) {
	$sid = $spel['SID'];
	$ronde = $spel['RONDE'];
	$nieuweRonde = $ronde + 1;
	$snaam = $spel['SNAAM'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Dag $ronde";
	$tuplesL = array();

	//pak brandstapel-uitslag-verhaal
	$text = "";
	$samenvatting = "";
	$auteur = array();
	brandstapelUitslag($text,$samenvatting,$auteur,$spel);
	
	//pak nacht-inleiding-verhaal
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	$verhaal = geefVerhaal($thema,"Algemeen",6,count($tuplesL),
		0,$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	shuffle($tuplesL);
	$text = vulIn($tuplesL,"","",$text,$geswoorden);
	$samenvatting .= "Nacht $nieuweRonde begint.<br />";
	
	//en voeg samenvatting samen met text
	$samenvatting .= spelerOverzicht($spel);
	$text = plakSamenvatting($samenvatting,$text);

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text,$auteur);
	schrijfLog($sid,"Brandstapel: Mail gestuurd naar $adres (uitslag).\n");

	return;
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
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}

	//pak het verhaal
	$verhaal = geefVerhaal($thema,"Algemeen",-1,$aantal,0,$ronde,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	shuffle($tuplesL);
	$text = vulIn($tuplesL,"","",$text,$geswoorden);

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

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text,array($auteur));
	schrijfLog($sid,"Inleiding: Mail gestuurd naar $adres.\n");
}

function mailGewonnen($gewonnen,$gewonnenSpelers,$fase,$spel) {
	$sid = $spel['SID'];
	$snaam = $spel['SNAAM'];
	$ronde = $spel['RONDE'];
	$thema = $spel['THEMA'];
	$onderwerp = "$snaam: Dag $ronde";

	$text = "";
	$samenvatting = "";
	$auteur = array();

	if($fase) {
		//na brandstapel: maak brandstapelverhaaltje
		brandstapelUitslag($text,$samenvatting,$auteur,$spel);
	}
	else {
		//na nacht: maak ontwaakverhaaltje
		ontwaakVerhaal($text,$samenvatting,$auteur,$spel);
	}

	//pak alle levende, niet-gewonnen spelers (alleen van toepassing bij FS)
	$gew = "('" . $gewonnenSpelers[0]['NAAM'] . "'";
	for($i = 1; $i < count($gewonnenSpelers); $i++) {
		$gew .= ", '" . $gewonnenSpelers[$i]['NAAM'] . "'";
	}
	$gew .= ")";
	$verlorenSpelers = array();
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND= 1");
	while($speler = sqlFet($resultaat)) {
		if(!check_in_array($speler,$gewonnenSpelers)) {
			array_push($verlorenSpelers,$speler);
		}
	}

	var_dump($verlorenSpelers);

	//in case of opdrachtgever/lijfwacht:
	//opdrachtgever in [0] en lijfwacht in [1]
	if($gewonnen == 7) {
		if($gewonnenSpelers[0]['ROL'] != "Opdrachtgever" || 
			$gewonnenSpelers[0]['LIJFWACHT'] != $gewonnenSpelers[1]['ID']) {
				$tmp = $gewonnenSpelers[0];
				$gewonnenSpelers[0] = $gewonnenSpelers[1];
				$gewonnenSpelers[1] = $tmp;
			}
	}

	//maak gewonnenverhaaltje
	$verhaal = geefVerhaal($thema,"Gewonnen",$gewonnen,count($gewonnenSpelers),
		count($verlorenSpelers),$ronde,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulIn($gewonnenSpelers,$verlorenSpelers,"",$text,$geswoorden);

	//maak samenvatting
	switch($gewonnen) {
		case -1:
			$samenvatting .= "Alle spelers zijn dood; ";
			$samenvatting .= "het spel eindigt in gelijkspel.<br />";
			break;
		case 0:
			$samenvatting .= "De Burgers hebben gewonnen.<br />";
			break;
		case 1:
			$samenvatting .= "De Weerwolven hebben gewonnen.<br />";
			break;
		case 2:
			$samenvatting .= "De Vampiers hebben gewonnen.<br />";
			break;
		case 3:
			$samenvatting .= "De Psychopaat heeft gewonnen.<br />";
			break;
		case 4:
			$samenvatting .= "De Witte Weerwolf heeft gewonnen.<br />";
			break;
		case 5:
			$samenvatting .= "De Fluitspelers hebben gewonnen.<br />";
			break;
		case 6:
			$samenvatting .= "De Geliefden hebben gewonnen.<br />";
			break;
		case 7:
			$samenvatting .= "De Opdrachtgever en Lijfwacht ";
			$samenvatting .= "hebben gewonnen.<br />";
			break;
		default: //case 8:
			$samenvatting .= "Het spel is gewonnen.<br />";
			break;
	}//switch

	//maak het totale speleroverzicht
	$samenvatting .= totaalOverzicht($gewonnenSpelers,$spel);

	//voeg samenvatting achter het verhaal
	$text = plakSamenvatting($samenvatting,$text);

	//mail naar iedereen in maillijst
	$adres = maillijst($sid);
	stuurMail($adres,$onderwerp,$text,$auteur);
	schrijfLog($sid,"Gewonnen: Mail gestuurd naar $adres.\n");

	//meld aan admins dat het spel af is
	stuurGewonnenAdmins($spel);
	schrijfLog($sid,"Gewonnen: Mail gestuurd naar de admins.\n");

	return;
}//mailGewonnen

function totaalOverzicht($gewonnenSpelers,$spel) {
	$sid = $spel['SID'];
	$burg = $spel['BURGEMEESTER'];
	$resultaat = sqlSel(3,"SID=$sid");
	if(sqlNum($resultaat) == 0) {
		return "";
	}

	$samenvatting = "<br />-=-=-=-<br /><br />";
	$samenvatting .= "Speleroverzicht:<br />";
	$samenvatting .= "<br />";
	$samenvatting .= "<table border='1'><tr>";
	$samenvatting .= "<th>Naam</th>";
	$samenvatting .= "<th>Gewonnen?</th>";
	$samenvatting .= "<th>Levend?</th>";
	$samenvatting .= "<th>Rol</th>";
	$samenvatting .= "<th>Geliefde</th>";
	$samenvatting .= "<th>Lijfwacht</th>";
	$samenvatting .= "<th>Burgemeester?</th>";
	$samenvatting .= "</tr>";

	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		$gewonnen = (check_in_array($speler,$gewonnenSpelers))? "Ja" : "";
		$levend = ($speler['LEVEND'] & 1)? "Ja" : "";
		$rol = $speler['ROL'];
		$geliefde = "";
		if($speler['GELIEFDE'] != "") {
			$gid = $speler['GELIEFDE'];
			$res = sqlSel(3,"ID=$gid");
			$sp = sqlFet($res);
			$geliefde = $sp['NAAM'];
		}
		$lijfwacht = "";
		if($rol == "Opdrachtgever" && $speler['LIJFWACHT'] != "") {
			$lid = $speler['LIJFWACHT'];
			$res = sqlSel(3,"ID=$lid");
			$sp = sqlFet($res);
			$lijfwacht = $sp['NAAM'];
		}
		$burgemeester = ($burg == $speler['ID'])? "Ja" : "";
		$samenvatting .= "<tr>";
		$samenvatting .= "<td align='center'>$naam</td>";
		$samenvatting .= "<td align='center'>$gewonnen</td>";
		$samenvatting .= "<td align='center'>$levend</td>";
		$samenvatting .= "<td align='center'>$rol</td>";
		$samenvatting .= "<td align='center'>$geliefde</td>";
		$samenvatting .= "<td align='center'>$lijfwacht</td>";
		$samenvatting .= "<td align='center'>$burgemeester</td>";
		$samenvatting .= "</tr>";
	}
	$samenvatting .= "</table>";

	return $samenvatting;
}//totaalOverzicht

?>
