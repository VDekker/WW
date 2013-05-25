<?php

//mailt iedere speler AFZONDERLIJK met diens rol
function mailRolverdeling($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid");
	$onderwerp = "$sid: Rol";
	$verhaal = geefVerhaal($thema,"Rolverdeling",0);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];

	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$namen = array($speler['NAAM']);
		$adres = $speler['EMAIL'];
		$rollen = array($speler['ROL']);
		if($rollen[0] == "Dwaas") {
			$rollen[0] = "Ziener";
		}
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		$geslachten = array($geslacht);
		$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$bericht);
		echo "Rol naar $id gemaild.\n";
	}//while
	return 0;
}//mailRolverdeling

//voor een gegeven rol, 
//stuurt een mail naar alle spelers met die rol AFZONDERLIJK,
//met het juiste verhaaltje
function mailWakker($rol,$sid) {
	$onderwerp = "$sid: Actie";
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='$rol' AND ((LEVEND & 1) = 1)");
	if($rol == "Dwaas") {
		$rol = "Ziener";
	}
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$namen = array($speler['NAAM']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		$geslachten = array($geslacht);
		$rollen = array($rol);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,$rol,0);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);
		$text = auteur($auteur,$text);

		stuurMail($adres,$onderwerp,$text);
		echo "Mail gestuurd naar $id.\n";
	}//while
	return;
}//mailWakker

//maakt een groep spelers wakker:
//Weerwolven (inc. Witte), Vampiers of Fluitspelers
function mailGroepWakker($rol,$sid) {
	$onderwerp = "$sid: Actie";
	$deadline = geefDeadline($sid);
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$namen = array();
	$geslachten = array();
	$rollen = array();
	$adressen = array();
	if($rol == "Weerwolf") {
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND ((LEVEND & 1) = 1) AND 
			(ROL='Weerwolf' OR ROL='Witte Weerwolf')");
	}
	else {
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND ((LEVEND & 1) = 1) AND ROL='$rol'");
	}
	if(sqlNum($resultaat) == 0) {
		echo "Geen levende $rol te bekennen.\n";
		return;
	}
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$rol);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
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
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$namen = array(); // vul een array van slachtoffers
	$rollen = array();
	$geslachten = array();
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=3");
	while($speler = sqlFet($resultaat)) {
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Heks' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		if(!wordtWakker($speler['ID'],$sid)) {
			continue;
		}
		array_splice($namen,0,0,$speler['NAAM']);
		array_splice($rollen,0,0,"Heks");
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_splice($geslachten,0,0,$geslacht);
		$adres = $speler['EMAIL'];
		$verhaal = geefVerhaal($thema,"Heks",0);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		$auteur = $verhaal['AUTEUR'];
		$text = vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden);

		//infodump: op wie kan de speler stemmen voor redden/vergiftigen:
		$text .= "<br /><br />";
		$text .= "Je kunt een van deze speler(s) tot leven wekken:<br />";
		$text .= "<ul>";
		foreach($namen as $key => $naam) {
			if($key == 0 && !$speler['NIEUWDOOD']) {
				continue;
			}
			$text .= "<li>$naam</li>";
		}
		$text .= "</ul>";
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
function mailActie($id,$fase,$sid,$plek) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
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
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	echo "$rollen[0] $id heeft op $stem gestemd.\n";
	$verhaal = geefVerhaal($thema,$rollen[0],$fase);
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
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Bestolen";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Dief");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];
	echo "Dief $id steelt de rol '$rollen[1]' van $stem.\n";

	$verhaal = geefVerhaal($thema,'Dief',2);
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
	$verhaal = geefVerhaal($thema,'Dief',3);
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
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Geliefde";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];

	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Cupido");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];

	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem1");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 .= ", " . $speler['EMAIL'];

	echo "Cupido $id maakt $stem en $stem2 verliefd op elkaar.\n";

	$verhaal = geefVerhaal($thema,'Cupido',1);
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
function mailOpdracht($id,$sid) {
	$onderwerp = "$sid: Opdrachtgever";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Opdrachtgever");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres .= ", " . $speler['EMAIL'];

	echo "Opdrachtgever $id stelt $stem aan tot zijn lijfwacht.\n";

	$verhaal = geefVerhaal($thema,'Opdrachtgever',1);
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
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Welp");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];

	echo "Welp $id wordt een Weerwolf.\n";

	$verhaal = geefVerhaal($thema,'Welp',0);
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
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Slaap";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];

	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Klaas Vaak");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler['STEM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$adres2 = $speler['EMAIL'];

	echo "Klaas Vaak $id laat $stem slapen.\n";

	$verhaal = geefVerhaal($thema,'Klaas Vaak',1);
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
function mailDwaas($id,$gezien,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Ziener");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$gezien);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);

	echo "Dwaas $id denkt dat $stem een $gezien is.\n";

	$verhaal = geefVerhaal($thema,'Ziener',1);
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
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Goochelaar");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem1 = $speler['STEM'];
	$stem2 = $speler['EXTRA_STEM'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem1");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem2");
	$speler = sqlFet($resultaat);
	array_push($namen,$speler['NAAM']);
	array_push($rollen,$speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	array_push($geslachten,$geslacht);

	echo "Goochelaar $id verwisselt $stem en $stem2 met elkaar.\n";

	$verhaal = geefVerhaal($thema,'Goochelaar',2);
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
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$slachtoffer");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array($speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adressen = array();
	foreach($spelers as $id) {
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
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

	echo "$rol: $slachtoffer vermoord.\n";

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($spelers),1);
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
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Onschuldige Meisje");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];

	echo (($rol & 1) == 1) ? "Weerwolf" : "Vampier" . 
		": Onschuldige Meisje $id ziet de stemmen.\n";

	$verhaal = geefVerhaal($thema,'Onschuldige Meisje',$fase);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	$auteur = $verhaal['AUTEUR'];
	$text = vulIn($namen,$rollen,$geslachten,"",$text,$geswoorden);

	$text .= "<br /><br />";
	foreach($targets as $key => $target) {
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
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
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Heks");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	if(($verhaal & 2) == 2) { //andere speler geredt, dus $stem1 is belangrijk
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem1");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}
	if(($verhaal & 4) == 4) { //speler gedood, dus $stem2 is belangrijk
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem2");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	$verhaal = geefVerhaal($thema,"Heks",$verhaal);
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
	$onderwerp = "$sid: Actie";
	$onderwerp2 = "$sid: Betoverd";
	$numBetoverd = 0;
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$namen = array();
	$rollen = array();
	$geslachten = array();
	$adressen = array();
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$betoverd1");
		$speler = sqlFet($resultaat);
		$betoverdNaam = array($speler['NAAM']);
		$betoverdRol = array($speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		$betoverdGeslacht = array($geslacht);
		$adres2 = $speler['EMAIL'];
		if(($fase & 1) != 1) {
			$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$betoverd2");
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
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
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

	$verhaal = geefVerhaalGroep($thema,$rol,$fase,count($spelers),$numBetoverd);
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
	$verhaal = geefVerhaal($thema,$rol,$numBetoverd);
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

function mailTestament($id,$fase,$sid) {
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array($speler['ROL']);
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	$stem = $speler["STEM"];
	if($fase != 9) {
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
	}

	echo "Burgemeester $id kiest als opvolger: $stem.\n";

	$verhaal = geefVerhaal($thema,"Burgemeester",$fase);
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
	$onderwerp = "$sid: Actie";
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$thema = $spel['THEMA'];
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$id");
	$speler = sqlFet($resultaat);
	$namen = array($speler['NAAM']);
	$rollen = array("Zondebok");
	$geslacht = ($speler['SPELERFLAGS'] & 1);
	$geslachten = array($geslacht);
	$adres = $speler['EMAIL'];
	foreach($slachtoffers as $slachtoffer) {
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ID=$slachtoffer");
		$speler = sqlFet($resultaat);
		array_push($namen,$speler['NAAM']);
		array_push($rollen,$speler['ROL']);
		$geslacht = ($speler['SPELERFLAGS'] & 1);
		array_push($geslachten,$geslacht);
		echo "Zondebok $id wekt schuldgevoel op in $slachtoffer.\n";
	}

	$verhaal = geefVerhaalGroep2($thema,"Zondebok",1,1,(count($namen)-1));
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

?>
