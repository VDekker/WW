<?php

//regelt de stem van de Dief (steelt de rol van een andere speler)
function regelDief($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Dief'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { // niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}//if
		heeftGestemd($id);
		if($stem == -1 || $stem == $id) { //dief doet niets
			mailActie($id,9,$spel,"STEM");
			sqlUp(3,"ROL='Burger'","ID=$id");
			continue;
		}
		$resultaat2 = sqlSel(3,"ID=$stem");
		$target = sqlFet($resultaat2);
		$rol = $target['ROL'];
		if($rol == "Burger" || $rol == "Dief") {
			mailActie($id,1,$spel,"STEM");
			sqlUp(3,"ROL='Burger'","ID=$id");
			continue;
		}
		mailDief($id,$spel);
		sqlUp(3,"ROL='$rol'","ID=$id");
		sqlUp(3,"ROL='Burger'","ID=$stem");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelDief

//regelt de stemmen van Cupido('s)
function regelCupido($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Cupido'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") {//niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		$stem2 = $speler['EXTRA_STEM'];
		if($id == $stem) { //als zichzelf verliefd gemaakt: ander verhaal
			mailActie($id,2,$spel,"EXTRA_STEM");
		}
		else if($id == $stem2) {
			mailActie($id,2,$spel,"STEM");
		}
		else { //twee andere spelers verliefd
			mailCupido($id,$spel);
		}
		sqlUp(3,"GELIEFDE=$stem2","ID=$stem");
		sqlUp(3,"GELIEFDE=$stem","ID=$stem2");
		verwijderStem($id,"STEM");
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelCupido

//regelt de stem van de Opdrachtgever(s)
function regelOpdracht($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Opdrachtgever'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") {//niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}//if
		heeftGestemd($id);
		if($stem == -1 || $stem == $id) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}//if
		mailOpdracht($id,$spel);
		sqlUp(3,"LIJFWACHT=$stem","ID=$id");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelOpdracht

//regelt de Welp(en) 
function regelWelp($spel) {
	$sid = $spel['SID'];
	$welpen = array();
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Welp' AND ((LEVEND & 1) = 1)");
	if(sqlNum($resultaat) == 0) { //geen levende welpen
		return;
	}
	while($speler = sqlFet($resultaat)) {
		array_push($welpen,$speler['ID']);
	}
	shuffle($welpen); //randomise lijst (in geval van meerdere dode WW)
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Weerwolf' AND ((LEVEND & 1) = 0) AND 
		((SPELFLAGS & 128) = 0)"); // deze dode wolf is nog niet geteld...
	if(sqlNum($resultaat) == 0) { //geen nieuwe dode wolven
		return;
	}
	while($dodewolf = sqlFet($resultaat)) {
		if(empty($welpen)) { //geen welpen meer over
			return;
		}
		$id = $welpen[0];
		$doodid = $dodewolf['ID'];
		sqlUp(3,"ROL='Weerwolf'","ID=$id");
		sqlUp(3,"SPELFLAGS=SPELFLAGS+128","ID=$doodid");
		delArrayElement($welpen,0);
		mailWelp($id,$spel);
	}//while
	return;
}//regelWelp

//regelt de stem van de Grafrover(s)
function regelGrafrover($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Grafrover' AND ((LEVEND & 1) = 1)");
	$waardes = array(); //  om de rolverwisselingen allemaal
	$eisen = array();   //  achter elkaar uit te voeren
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		$resultaat2 = sqlSel(3,"ID=$stem");
		$speler2 = sqlFet($resultaat2);
		$rol = $speler2['ROL'];
		$waarde = "ROL='$rol'";
		if($rol == "Heks") {
			$waarde .= ",SPELFLAGS=SPELFLAGS+384"; //beide drankjes
		}
		else if($rol == "Dorpsoudste") {
			$waarde .= ",SPELFLAGS=SPELFLAGS+128"; //extra leven
		}
		$eis = "ID=$id";
		array_push($waardes,$waarde);
		array_push($eisen,$eis);
		mailActie($id,1,$spel,"STEM");
		verwijderStem($id,"STEM");
	}//while
	for($i = 0; $i < count($waardes); $i++) {
		sqlUp(3,$waardes[$i],$eisen[$i]);
	}
	return;
}//regelGrafrover

//regelt de stem van Klaas Vaak(en)
function regelKlaasVaak($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Klaas Vaak' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		schrijfLog($sid,"Klaas Vaak $id laat $stem slapen.\n");
		sqlUp(3,"SPELFLAGS=SPELFLAGS+32",
			"ID=$stem AND ((SPELFLAGS & 32) = 0)");
		sqlUp(3,"VORIGE_STEM=$stem","ID=$id");
		mailKlaas($id,$spel);
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelKlaasVaak

//regelt de stem van de Genezer(s)
function regelGenezer($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Genezer' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)){
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		schrijfLog($sid,"Genezer $id beschermt $stem.\n");
		sqlUp(3,"SPELFLAGS=SPELFLAGS+64",
			"ID=$stem AND ((SPELFLAGS & 64) = 0)");
		sqlUp(3,"VORIGE_STEM=$stem","ID=$id");
		mailActie($id,1,$spel,"STEM");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelGenezer

//regelt de stem van de Ziener(s)
function regelZiener($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Ziener' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)){
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		mailActie($id,1,$spel,"STEM");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelZiener

//regelt de stem van de Dwaas (of Dwazen): 
//mailt een rol die ongelijk is aan de gevraagde rol
function regelDwaas($spel) {
	$sid = $spel['SID'];
	$andereRollen = 0; //andere rollen dan de Dwaas: liefst 2
	$rollen = array();
	$resultaat = sqlSel(3,"SID=$sid"); //pak alle rollen in het spel
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] != "Dwaas" && !in_array($speler['ROL'],$rol)) {
			$andereRollen++;
		}
		array_push($rollen,$speler['ROL']);
	}
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Dwaas' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		$resultaat2 = sqlSel(3,"ID=$stem");
		$speler2 = sqlFet($resultaat2);
		$rol = $speler2['ROL'];
		$gezien = $rol;
		if($andereRollen == 0 || ($andereRollen == 1 && $rol != "Dwaas")) {
			$gezien = "Dwaas";
		}
		else {
			while($rol == $gezien && $rol != "Dwaas") {
				$key = array_rand($rollen);
				$gezien = $rollen[$key];
			} //nu is $gezien een andere rol dan $rol
		}
		mailDwaas($id,$gezien,$spel);
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelDwaas

//regelt de stem van de Priester(s)
function regelPriester($spel) {
	$sid = $spel['SID'];
	//vul een array met alle 'onzuivere' rollen:
	$rollen = array("Weerwolf","Witte Weerwolf","Welp","Vampier","Psychopaat",
		"Fluitspeler","Heks","Grafrover","Slet","Verleidster");
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Priester' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		$resultaat2 = sqlSel(3,"ID=$stem");
		$speler2 = sqlFet($resultaat2);
		$rol = $speler2['ROL'];
		if(in_array($rol,$rollen)) {
			mailActie($id,1,$spel,"STEM");
		}
		else {
			mailActie($id,2,$spel,"STEM");
		}
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelPriester

//regelt de stem van de Slet(ten)
function regelSlet($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Slet' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			sqlUp(3,"STEM=-1","ID=$id"); //stem wordt blanco (voor VORIGE_STEM)
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			sqlUp(3,"STEM=-1","ID=$id"); //stem wordt blanco (voor VORIGE_STEM)
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		mailActie($id,1,$spel,"STEM");
	}//while
	return;
}//regelSlet

//regelt de stem van de Verleidster(s)
function regelVerleid($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Verleidster' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			sqlUp(3,"STEM=-1","ID=$id"); //stem wordt blanco (voor VORIGE_STEM)
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			sqlUp(3,"STEM=-1","ID=$id"); //stem wordt blanco (voor VORIGE_STEM)
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($target == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		mailActie($id,1,$spel,"STEM");
	}//while
	return;
}//regelVerleid

//regelt de stem van de Goochelaar(s)
function regelGoochel($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Goochelaar' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			sqlUp(3,"STEM=-1","ID=$id"); //stem wordt blanco (voor VORIGE_STEM)
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			sqlUp(3,"STEM=-1","ID=$id"); //stem wordt blanco (voor VORIGE_STEM)
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		$stem2 = $speler['EXTRA_STEM'];
		if($id == $stem) {
			mailActie($id,1,$spel,"EXTRA_STEM");
		}
		else if($id == $stem2) {
			mailActie($id,1,$spel,"STEM");
		}
		else {
			mailGoochel($id,$spel);
		}
	}//while
	return;
}//regelGoochel

//regelt alle stemmen van de Weerwolven/Vampiers 
//(geef rol mee die van toepassing is)
function regelWWVP($rol,$spel) {
	$sid = $spel['SID'];
	$spelers = array(); //voor alle WW's/VP's (wie moeten gemaild worden?)
	$vlag = ($rol == "Weerwolf") ? 0 : 1; //voor het Onschuldige Meisje
	$alleTargets = array(-1); //init met blanco om errors te voorkomen
	$stemmen = array(0);
	if($rol == "Weerwolf") {
		$resultaat = sqlSel(3,
			"SID=$sid AND (ROL='Weerwolf' OR ROL='Witte Weerwolf') 
			AND ((LEVEND & 1) = 1)");
	}
	else if($rol == "Vampier") {
		$resultaat = sqlSel(3,
			"SID=$sid AND ROL='Vampier' AND ((LEVEND & 1) = 1)");
	}
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		array_push($spelers,$id);
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		verwijderStem($id,"STEM");
		$key = array_search($stem,$alleTargets);
		if($key == false) { // nog niet eerder op deze persoon gestemd
			array_push($alleTargets,$stem);
			array_push($stemmen,1);
		}
		else { // anders: tel 1 stem erbij op
			$stemmen[$key]++;
		}
	}//while

	$blanco = array_keys($alleTargets,-1); //verwijder blanco's
	foreach($blanco as $blancokey) {
		$alleTargets = delArrayElement($alleTargets,$blancokey);
		$stemmen = delArrayElement($stemmen,$blancokey);
	}
	if(empty($alleTargets)) { // geen doelwit
		mailWWVPActie($spelers,NULL,$rol,9,$spel);
		$vlag += 2;
	}
	else {
		$keys = hoogsteStem($stemmen); //bepaalt de hoogste stem
		if(count($keys) > 1) { //gelijkspel: geen slachtoffer
			mailWWVPActie($spelers,NULL,$rol,2,$spel);
		}
		$slachtoffer = $alleTargets[array_rand($keys)];
		shuffle($spelers); // randomise spelers
		mailWWVPActie($spelers,$slachtoffer,$rol,1,$spel);
		vermoord($slachtoffer,$sid);
	}
	if(inSpel("Onschuldige Meisje",$sid)) {
		$resultaat = sqlSel(3,
			"SID=$sid AND ROL='Onschuldige Meisje' AND ((LEVEND & 1) = 1)");
		while($meisje = sqlFet($resultaat)) {
			$id = $meisje['ID'];
			if(!wordtWakker($id,$sid)) {
				continue;
			}
			mailOnschuldig($id,$alleTargets,$stemmen,$vlag,$rol,$spel);
		}//while
	}//if
	return;
}//regelWWVP

//regelt de stem van de Psychopaat(en)
function regelPsycho($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Psychopaat' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		schrijfLog($sid,"Psychopaat: $stem vermoordt.\n");
		vermoord($stem,$sid);
		mailActie($id,1,$spel,"STEM");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelPsycho

//regelt de stem van de Witte Weerwolf(of meerdere...)
function regelWitteWW($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	if(($spel['FLAGS'] & 1) != 1) { // flipt de tweede nacht-flag
		sqlUp(4,"FLAGS=FLAGS+1","SID=$sid");
		return;
	}
	sqlUp(4,"FLAGS=FLAGS-1","SID=$sid");

	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Witte Weerwolf' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"STEM");
			continue;
		}
		schrijfLog($sid,"Witte Weerwolf: $stem vermoordt.\n");
		vermoord($stem,$sid);
		mailActie($id,1,$spel,"EXTRA_STEM");
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelWitteWW

//regelt de stemmen van de Heks(en): 
//als beide stemmen leeg zijn heeft zij niet gestemd
//als de eerste stem 'blanco' is, doet zij niets.
function regelHeks($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,"SID=$sid AND ROL='Heks' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		$stem2 = $speler['EXTRA_STEM'];
		$drank = $speler['SPELFLAGS']; 
		if($stem == "" && $stem2 == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		$verhaal = 0; //weer een vlag: 
					//1 = zichzelf redden, 2 = ander redden, 4 = vergiftigen
		heeftGestemd($id);
		if($stem != "") {
			if($stem == -1) {
				mailActie($id,9,$spel,"STEM");
				continue;
			}
			schrijfLog($sid,"Heks $id wekt $stem tot leven.\n");
			herleef($stem,$sid);
			$drank -= 128;
			if($id == $stem) {
				$verhaal += 1;
			}
			else {
				$verhaal += 2;
			}
		}
		if($stem2 != "") {
			schrijfLog($sid,"Heks $id vergiftigt $stem2.\n");
			zetDood($stem2,$sid);
			$drank -= 256;
			$verhaal += 4;
		}
		sqlUp(3,"SPELFLAGS=$drank","ID=$id");
		mailHeksActie($id,$stem,$stem2,$verhaal,$spel);
		verwijderStem($id,"STEM");
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelHeks

//regelt de stemmen van de Fluitspeler(s): 
//als de eerste stem leeg is heeft hij niet gestemd
function regelFluit($spel) {
	$sid = $spel['SID'];
	$alleTargets = array(-1);
	$stemmen = array(0);
	$spelers = array();
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Fluitspeler' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		array_push($spelers,$id);
		$stem = $speler['STEM'];
		$stem2 = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		verwijderStem($id,"STEM");
		verwijderStem($id,"EXTRA_STEM");
		$key = array_search($stem,$alleTargets);
		if($key == false) { // nog niet eerder op deze persoon gestemd
			array_push($alleTargets,$stem);
			array_push($stemmen,1);
		}
		else { // anders: tel 1 stem erbij op
			$stemmen[$key]++;
		}
		if($stem2 != "") {
			$key = array_search($stem2,$alleTargets);
			if($key == false) {
				array_push($alleTargets,$stem2);
				array_push($stemmen,1);
			}
			else {
				$stemmen[$key]++;
			}
		}//if
	}//while

	$blanco = array_keys($alleTargets,-1);
	foreach($blanco as $blancokey) {
		$alleTargets = delArrayElement($alleTargets,$blancokey);
		$stemmen = delArrayElement($stemmen,$blancokey);
	}
	if(empty($alleTargets)) {
		mailFSActie($spelers,NULL,NULL,9,$spel);
		return;
	}
	$keys = hoogsteStem($stemmen);
	if(count($keys) > 2) { //gelijkspel: geen slachtoffers
		mailFSActie($spelers,NULL,NULL,5,$spel);
	}
	if(count($keys) > 1) { // meerdere slachtoffers met evenveel stemmen
		shuffle($keys);
		$slachtoffer = $alleTargets[$keys[0]];
		$slachtoffer2 = $alleTargets[$keys[1]];
	}
	else {
		$slachtoffer = $alleTargets[$keys[0]]; //pak de hoogste
		$alleTargets = delArrayElement($alleTargets,$keys[0]);
		$stemmen = delArrayElement($stemmen,$keys[0]);
		if(empty($alleTargets)) {
			mailFSActie($spelers,$slachtoffer,NULL,1,$spel);
			return;
		}
		$keys = hoogsteStem($stemmen);
		if(count($keys) > 1) { //gelijkspel voor 2e plek: 1 slachtoffer
			mailFSActie($spelers,$slachtoffer,NULL,1,$spel);
		}
		$slachtoffer2 = $alleTargets[$keys[0]];
	}//else
	mailFSActie($spelers,$slachtoffer,$slachtoffer2,2,$spel);
	sqlUp(3,"SPELFLAGS=SPELFLAGS+1",
		"((SPELFLAGS & 1) = 0) AND (ID=$slachtoffer OR ID=$slachtoffer2)");

	return;
}//regelFluit

//regelt de EXTRA_STEM van de Waarschuwer(s): 
//mailt de Waarschuwer en ook zijn keuze
function regelWaarschuw($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Waarschuwer' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($target == -1) {
			mailActie($id,9,$spel,"EXTRA_STEM");
			continue;
		}
		sqlUp(3,"SPELFLAGS=SPELFLAGS+16",
			"ID=$stem AND ((SPELFLAGS & 16) = 0)");
		mailWaarschuwer($id,$spel); //mail zowel Waarschuwer als keuze
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelWaarschuw

//regelt de EXTRA_STEM van de Schout(en)
function regelSchout($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Schout' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"EXTRA_STEM");
			sqlUp(3,"VORIGE_STEM=-1","ID=$id");
			continue;
		}
		sqlUp(3,"SPELFLAGS=SPELFLAGS+8",
			"ID=$stem AND ((SPELFLAGS & 8) = 0)");
		sqlUp(3,"VORIGE_STEM=$stem","ID=$id"); //tegen herhaling
		if($stem == $id) {
			mailActie($id,1,$spel,"EXTRA_STEM");
		}
		else {
			mailActie($id,2,$spel,"EXTRA_STEM");
		}
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelSchout

//regelt de EXTRA_STEM van de Raaf (of Raven)
function regelRaaf($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Raaf' AND ((LEVEND & 1) = 1)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$spel,"EXTRA_STEM");
			continue;
		}
		sqlUp(3,"SPELFLAGS=SPELFLAGS+4",
			"ID=$stem AND ((SPELFLAGS & 4) = 0)");
		if($stem == $id) {
			mailActie($id,1,$spel,"EXTRA_STEM");
		}
		else {
			mailActie($id,2,$spel,"EXTRA_STEM");
		}
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelRaaf

//regelt de Jager: indien er een dode Jager is, doodt dan ook zijn stem
//dood hij nog een Jager, Opdrachtgever, Geliefde of Burgemeester:
//ga dan terug in fase naar dood1.
function regelJager($fase,$spel) {
	$sid = $spel['SID'];
	$flag = false;
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Jager' AND LEVEND=3 AND ((SPELFLAGS & 128) = 0)");
	$resultaat2 = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat2);
	
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['EXTRA_STEM'];
		schrijfLog($sid,"Jager gevonden: $id\n");
		if($stem == "") {
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		if($stem == -1 || $stem == $id) {
			mailActie($id,9,$spel,"EXTRA_STEM");
			continue;
		}
		else {
			zetDood($stem,$sid);
			mailActie($id,$fase,$spel,"EXTRA_STEM");
			$resultaat2 = sqlSel(3,"ID=$stem");
			$slachtoffer = sqlFet($resultaat2);
			if($slachtoffer['ROL'] == "Jager" || 
				$slachtoffer['GELIEFDE'] != "" || 
				$slachtoffer['LIJFWACHT'] != "" || 
				$stem == $spel['BURGEMEESTER']) { //opnieuw naar regeldood1
					schrijfLog($sid,"$stem is een Jager, Geliefde, " . 
						"Burgemeester of heeft een Lijfwacht.\n");
					zetFase($fase,$sid);
					$flag = true;
			}
		}
	}//while
	if($flag) {
		sqlData($resultaat,0);
		while($speler = sqlFet($resultaat)) {
			$id = $speler['ID'];
			sqlUp(3,"SPELFLAGS=SPELFLAGS+128","ID=$id");
		}
	}
	return;
}//regelJager

//regelt het testament van de Burgemeester
//als hij "blanco" heeft gestemd, dan komen nieuwe verkiezingen.
function regelBurgemeester($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(4,"SID=$sid");
	$spel = sqlFet($resultaat);
	$id = $spel['BURGEMEESTER'];
	if($id == -1) { //geen Burgemeester in het spel
		return;
	}
	$resultaat = sqlSel(3,
		"ID=$id AND LEVEND<>1");
	if(sqlNum($resultaat) == 0) { //geen dode Burgemeester
		return;
	}
	$speler = sqlFet($resultaat);
	schrijfLog($sid,"Burgemeester $id is gestorven.\n");
	$stem = $speler['STEM'];
	if($stem == "") {
		stemGemist($id);
		sqlUp(4,"BURGEMEESTER=NULL,VORIGE_BURG=$id","SID=$sid");
		schrijfLog($sid,"$id heeft niet gestemd.\n");
		return;
	}
	heeftGestemd($id);
	if($stem == -1) {
		mailTestament($id,9,$spel);
		sqlUp(4,"BURGEMEESTER=NULL,VORIGE_BURG=$id","SID=$sid");
		return;
	}
	mailTestament($id,1,$spel);
	sqlUp(4,"BURGEMEESTER=$stem,VORIGE_BURG=$id","SID=$sid");
	verwijderStem($id,"STEM");
	return;
}//regelBurgemeester

//checkt voor alle doden of er nog Geliefden of Lijfwachten zijn.
//eerst controleren op Lijfwachten
//daarna controleren op Geliefden
//bij Geliefden letten of ze niet beiden dood zijn
//(zowel: zet dan beide flags omhoog!)
function regelDood1($sid) {
	//eerst lijfwachten doodmaken; 
	//hartgebroken geliefden kunnen niet worden gered.
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=3 AND 
		((SPELFLAGS & 512) = 0)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$lijfwacht = $speler['LIJFWACHT'];
		while(!empty($lijfwacht) && isLevend($lijfwacht)) {
			zetDood($lijfwacht,$sid);
			sqlUp(3,"SPELFLAGS=SPELFLAGS+1024",
				"ID=$lijfwacht AND ((SPELFLAGS & 1024) = 0)");
			herleef($id,$sid);
			sqlUp(3,"SPELFLAGS=SPELFLAGS-1024",
				"ID=$id AND ((SPELFLAGS & 1024) = 1024)");
			$res = sqlSel(3,"ID=$lijfwacht");
			$sp = sqlFet($res);
			$id = $sp['ID'];
			$lijfwacht = $sp['LIJFWACHT'];
			schrijfLog($sid,"Lijfwacht $lijfwacht sterft om $id te redden.\n");
		}
	}//while

	//nu met nieuwe resultaten geliefdes doodmaken
	//(hartgebroken geliefdes hoeven niet opnieuw worden gecheckt)
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=3 AND 
		((SPELFLAGS & 512) = 0)");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$geliefdeID = $speler['GELIEFDE'];
		if($geliefdeID != "") {
			$resultaat2 = sqlSel(3,"ID=$geliefdeID");
			$geliefde = sqlFet($resultaat2);
			if($geliefde['LEVEND'] == 1) {
				zetDood($geliefdeID,$sid);
				sqlUp(3,"SPELFLAGS=SPELFLAGS+512",
					"ID=$geliefdeID AND ((SPELFLAGS & 512) = 0)");
				schrijfLog($sid,"Geliefde $geliefdeID kan niet leven " . 
					"zonder $id en sterft.\n");
			}
			else {
				sqlUp(3,"SPELFLAGS=SPELFLAGS+512",
					"(ID=$geliefdeID OR ID=$id) AND ((SPELFLAGS & 512) = 0)");
			}
		}//if
	}//while

	return;
}//regelDood1

//zet alle nieuw_dode spelers op dood
//en, als Dorpsoudste dood is: verwijder dan de gaven van alle burgers
//(Opdrachtgever verliest Lijfwacht)
function regelDood2($sid,$fase) {
	$resultaat = sqlSel(3,
		"SID=$sid AND ((LEVEND & 2) = 2) AND ROL='Dorpsoudste'");
	if(sqlNum($resultaat) > 0) {
		sqlUp(3,"ROL='Burger',LIJFWACHT=NULL",
			"SID=$sid AND LEVEND=1 AND ROL IN (
			'Cupido',
			'Dief',
			'Dorpsgek',
			'Dorpsoudste',
			'Dwaas',
			'Genezer',
			'Goochelaar',
			'Grafrover',
			'Heks',
			'Jager',
			'Klaas Vaak',
			'Onschuldige Meisje',
			'Opdrachtgever',
			'Priester',
			'Raaf',
			'Schout',
			'Slet',
			'Verleidster',
			'Waarschuwer',
			'Ziener',
			'Zondebok'
			)");
		schrijfLog($sid,"Dorpsoudste is dood, " . 
			"en iedereen verliest zijn gaven.\n");
		
		//zet nieuw-dode spelers in volgende stadium: LEVEND=2
		sqlUp(3,"LEVEND=2",
			"SID=$sid AND LEVEND=3");
		
		//inactieve spelers eruit halen:
		$resultaat = sqlSel(4,"SID=$sid");
		$spel = sqlFet($resultaat);
		$strengheid = $spel['STRENGHEID'];
		$resultaat2 = sqlSel(3,
			"SID=$sid AND LEVEND=1 AND GEMIST>=$strengheid");
		$num = sqlNum($resultaat2);
		if($num > 0) {
			sqlUp(3,"LEVEND=3",
				"SID=$sid AND LEVEND=1 AND GEMIST>=$strengheid");
			sqlUp(4,"LEVEND=LEVEND-$num,DOOD=DOOD+$num","SID=$sid");
			schrijfLog($sid,"$num inactieve spelers gedood.\n");
		}
	}
	return;
}//regelDood2

//regelt alle stemmen van de Burgemeesterverkiezing
function regelBurgVerk($sid) {
	$overzichtTotaal = array(-1 => "blanco");
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$naam = $speler['NAAM'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			$stem = -2;
		}
		else {
			heeftGestemd($id);
			verwijderStem($id,"STEM");
			schrijfLog($sid,"$id stemt: $stem.\n");
		}
		if(array_key_exists($stem,$overzichtTotaal)) {
			array_push($overzichtTotaal[$stem],$naam);
		}
		else {
			$overzichtTotaal[$stem] = array($naam);
		}
	}//while

	//verbeter overzicht: namen waar id's staan (bij de stemmen)
	sqlData($resultaat,0);
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$naam = $speler['NAAM'];
		if(array_key_exists($id,$overzichtTotaal)) {
			$overzichtTotaal[$naam] = $overzichtTotaal[$id];
			unset($overzichtTotaal[$id]);
		}
	}//while

	//haal de blanco stemmen weg
	$blanco = array_keys($overzichtTotaal,-1);
	foreach($blanco as $blancokey) {
		$overzichtTotaal = delArrayElement($overzichtTotaal,$blancokey);
	}

	//haal de lege stemmen weg
	$blanco = array_keys($overzichtTotaal,-2);
	foreach($blanco as $blancokey) {
		$overzichtTotaal = delArrayElement($overzichtTotaal,$blancokey);
	}

	//als er geen stemmen overblijven: voortaan geen Burgemeester meer
	if(empty($overzichtTotaal)) {
		sqlUp(4,"BURGEMEESTER=-1","SID=$sid");
		schrijfLog($sid,"Burgemeesterverkiezing: " . 
			"enkel blanco stemmen geteld.\n");
		return;
	}

	//pak nu de hoogste stem (bij meerdere: neem een willekeurige)
	$max = 0;
	shuffle($overzichtTotaal);
	foreach($overzichtTotaal as $stem => $namen) {
		if(count($namen) > $max) {
			$burgemeester = $stem;
			$max = $count($namen);
		}
	}
	sqlUp(4,"BURGEMEESTER='$burgemeester'","SID=$sid");
	schrijfLog($sid,"De nieuwe Burgemeester is $burgemeester.\n");

	return $overzichtTotaal;
}//regelBurgVerk

//regelt alle stemmen van de Brandstapel
function regelBrand($spel) {
	$sid = $spel['SID'];
	$kandidaten = array(-1); // init met blanco om errors te voorkomen
	$stemmen = array(0);
	$resultaat = sqlSel(3,"SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			schrijfLog($sid,"$id heeft niet gestemd.\n");
			continue;
		}
		heeftGestemd($id);
		schrijfLog($sid,"$id stemt: $stem.\n");
		$i = stemWaarde($speler,$spel);
		$key = array_search($stem,$kandidaten);
		if($key == false) { // nog niet eerder op deze persoon gestemd
			array_push($kandidaten,$stem);
			array_push($stemmen,$i);
		}
		else { // anders: tel er $i aantal stemmen erbij op
			$stemmen[$key] += $i;
		}
	}//while

	//voeg het Teken van de Raaf bij de stemmen
	$eis = "SID=$sid AND LEVEND=1 AND ((SPELFLAGS & 4) = 4) AND ";
	$eis .= "((SPELFLAGS & 8) = 0) AND "; //tegen opgesloten spelers
	$eis .= "(((SPELFLAGS & 128) = 0) OR ROL<>'Dorpsgek')"; //tegen dorpsgekken
	$resultaat = sqlSel(3,$eis);
	if(sqlNum($resultaat)) {
		while($speler = sqlFet($resultaat)) {
			$id = $speler['ID'];
			schrijfLog($sid,"$id krijgt het Teken van de Raaf.\n");
			$key = array_search($id,$kandidaten);
			if(!$key) {
				array_push($kandidaten,$id);
				array_push($stemmen,2);
			}
			else{
				$stemmen[$key] += 2;
			}
		}//while
	}//if

	//haal alle schuldgevoel, tekens van de raaf en opgeslotenheid weg
	sqlUp(3,"SPELFLAGS=(SPELFLAGS-2)",
		"SID=$sid AND ((SPELFLAGS & 2) = 2)");
	sqlUp(3,"SPELFLAGS=(SPELFLAGS-4)",
		"SID=$sid AND ((SPELFLAGS & 4) = 4)");
	sqlUp(3,"SPELFLAGS=(SPELFLAGS-8)",
		"SID=$sid AND ((SPELFLAGS & 8) = 8)");

	//haal alle blanco stemmen weg
	$blanco = array_keys($kandidaten,-1);
	foreach($blanco as $blancokey) {
		$kandidaten = delArrayElement($kandidaten,$blancokey);
		$stemmen = delArrayElement($stemmen,$blancokey);
	}

	//er waren enkel blanco stemmen: check voor Zondebok, anders niks.
	if(empty($stemmen)) {
		$resultaat = sqlSel(3,
			"SID=$sid AND LEVEND=1 AND ROL='Zondebok'");
		if(sqlNum($resultaat) > 0) {
			$zondebokken = array();
			while($speler = sqlFet($resultaat)) {
				$id = $speler['ID'];
				array_push($zondebokken,$id);
			}
			$slachtoffer = array_rand($zondebokken);
			$id = $slachtoffer['ID'];
			sqlUp(3,"SPELFLAGS=SPELFLAGS+256","ID=$id");
			zetDood($slachtoffer,$sid);
			schrijfLog($sid,"Zondebok $slachtoffer gaat op de Brandstapel!\n");
		}//if
		else {
			schrijfLog($sid,"Geen slachtoffer.\n");
		}
		return;
	}//if

	//pak de hoogste stem
	$keys = hoogsteStem($stemmen);
	if(count($keys) > 1) { // gelijkspel
		schrijfLog($sid,"Gelijkspel! Hoogste stemmen: " . 
			$kandidaten[$keys[0]] . "(" . $stemmen[$keys[0]] . ") en " . 
			$kandidaten[$keys[1]] . " (" . $stemmen[$keys[1]] . ").\n");
		$resultaat = sqlSel(3,
			"SID=$sid AND LEVEND=1 AND ROL='Zondebok'");
		if(sqlNum($resultaat) > 0) { //check voor Zondebok
			$zondebokken = array();
			while($speler = sqlFet($resultaat)) {
				$id = $speler['ID'];
				array_push($zondebokken,$id);
			}
			$slachtoffer = array_rand($zondebokken);
			$id = $slachtoffer['ID'];
			sqlUp(3,"SPELFLAGS=SPELFLAGS+256","ID=$id");
			zetDood($slachtoffer,$sid);
			schrijfLog($sid,"Zondebok $slachtoffer gaat op de Brandstapel!\n");
		}//if
		else { // anders geen slachtoffer
			schrijfLog($sid,"Gelijkspel en geen Zondebok: geen slachtoffer.\n");
			return;
		}//else
	}//if

	//bij geen gelijkspel
	$slachtoffer = $kandidaten[$keys[0]];
	$aantal = $stemmen[$keys[0]];
	$resultaat = sqlSel(3,
		"ID=$slachtoffer AND ROL='Dorpsgek'");
	if(sqlNum($resultaat) > 0) { // slachtoffer is gek
		schrijfLog($sid,"$slachtoffer is gek verklaard en mag leven.\n");
		sqlUp(3,"SPELFLAGS=SPELFLAGS+128",
			"ID=$slachtoffer");
		return;
	}
	zetDood($slachtoffer,$sid);
	schrijfLog($sid,"$slachtoffer eindigt op de Brandstapel " . 
		"met $aantal stemmen.\n");
	return;
}//regelBrand

//regelt de stemmen van de Zondebok
function regelZonde($spel) {
	$sid = $spel['SID'];
	$resultaat = sqlSel(3,
		"SID=$sid AND ROL='Zondebok' AND ((LEVEND & 2) = 2) AND 
		((SPELFLAGS & 256) = 256) AND ((SPELFLAGS & 128) = 0)");
	if(sqlNum($resultaat) == 0) {
		return;
	}
	$zondebok = sqlFet($resultaat);
	$id = $zondebok['ID'];
	$stem = $zondebok['SPECIALE_STEM'];
	sqlUp(3,"SPELFLAGS=SPELFLAGS+128","ID=$id");
	if($stem == "") {
		stemGemist($id);
		schrijfLog($sid,"$id heeft niet gestemd.\n");
		return;
	}
	heeftGestemd($id);
	verwijderStem($id,"STEM");
	if($stem == "-1") {
		mailActie($id,9,$spel,"SPECIALE_STEM");
		return;
	}
	$stem = explode(',',$stem);

	//ga alle levende, spelers af (die nog geen schuldgevoel hebben)
	//en kijk of deze in de stem zitten
	$slachtoffers = array();
	$resultaat = sqlSel(3,
		"SID=$sid AND ((LEVEND & 1) = 1) AND ((SPELFLAGS & 2) = 0)");
	while($speler = sqlFet($resultaat)) { //voor elke speler
		$spID = $speler['ID'];
		if(in_array($spID,$stem)) {
			sqlUp(3,"SPELFLAGS=SPELFLAGS+2",
				"ID=$spID AND ((SPELFLAGS & 2) = 0)");
			array_push($slachtoffers,$spID);
		}
	}//while
	mailZonde($id,$slachtoffers,$spel);
	return;
}//regelZonde

?>
