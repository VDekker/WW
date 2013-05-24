<?php

//regelt de stem van de Dief (steelt de rol van een andere speler)
function regelDief($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Dief'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { // niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}//if
		heeftGestemd($id);
		if($stem == -1 || $stem == $id) { //dief doet niets
			mailActie($id,9,$sid,"STEM");
			sqlUp("Spelers","ROL='Burger'","SPEL=$sid AND ID=$id");
			continue;
		}
		$resultaat2 = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
		$target = sqlFet($resultaat2);
		$rol = $target['ROL'];
		if($rol == "Burger" || $rol == "Dief") {
			mailActie($id,1,$sid,"STEM");
			sqlUp("Spelers","ROL='Burger'","SPEL=$sid AND ID=$id");
			continue;
		}
		mailDief($id,$sid);
		sqlUp("Spelers","ROL='$rol'","SPEL=$sid AND ID=$id");
		sqlUp("Spelers","ROL='Burger'","SPEL=$sid AND ID=$stem");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelDief

//regelt de stemmen van Cupido('s)
function regelCupido($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Cupido'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		$stem2 = $speler['EXTRA_STEM'];
		if($stem == "") {//niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		mailCupido($id,$sid);
		sqlUp("Spelers","GELIEFDE=$stem2","SPEL=$sid AND ID=$stem");
		sqlUp("Spelers","GELIEFDE=$stem","SPEL=$sid AND ID=$stem2");
		verwijderStem($id,"STEM");
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelCupido

//regelt de stem van de Opdrachtgever(s)
function regelOpdracht($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Opdrachtgever'");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") {//niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}//if
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}//if
		mailOpdracht($id,$sid);
		sqlUp("Spelers","LIJFWACHT=$stem","SPEL=$sid AND ID=$id");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelOpdracht

//regelt de Welp(en) 
//TODO als 1 WW sterft, wordt 1 welp een WW. 
//     Maar de volgende dag wordt de volgende welp ook een WW 
//     vanwege dezelfde dode WW.
//     Maak een vlag hiertegen...
function regelWelp($sid) {
	$welpen = array();
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Welp' AND LEVEND=1");
	if(sqlNum($resultaat) == 0) { //geen levende welpen
		return;
	}
	while($speler = sqlFet($resultaat)) {
		array_push($welpen,$speler['ID']);
	}
	shuffle($welpen); //randomise lijst (in geval van meerdere dode WW)
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Weerwolf' AND LEVEND=0 AND 
		((SPELFLAGS & 8) = 0"); // deze dode wolf is nog niet geteld...
	if(sqlNum($resultaat) == 0) { //geen nieuwe dode wolven
		return;
	}
	while($dodewolf = sqlFet($resultaat)) {
		if(empty($welpen[$i])) { //geen welpen meer over
			return;
		}
		$id = $welpen[0];
		$doodid = $dodewolf['ID'];
		sqlUp("Spelers","ROL='Weerwolf'","ID=$id");
		sqlUp("Spelers","SPELFLAGS=SPELFLAGS+8","ID=$doodid");
		delArrayElement($welpen,0);
		mailWelp($id,$sid);
	}//while
	return;
}//regelWelp

//regelt de stem van de Grafrover(s)
function regelGrafrover($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Grafrover' AND LEVEND=1");
	$waardes = array(); //  om de rolverwisselingen allemaal
	$eisen = array();   //  achter elkaar uit te voeren
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		$resultaat2 = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
		$speler2 = sqlFet($resultaat2);
		$rol = $speler2['ROL'];
		$waarde = "ROL='$rol'";
		if($rol == "Heks") {
			$waarde .= ",SPELFLAGS=SPELFLAGS+48"; // 16+32=48: beide drankjes
		}
		else if($rol == "Dorpsoudste") {
			$waarde .= ",SPELFLAGS=SPELFLAGS+64";
		}
		$eis = "SPEL=$sid AND ID=$id";
		array_push($waardes,$waarde);
		array_push($eisen,$eis);
		mailActie($id,1,$sid,"STEM");
		verwijderStem($id,"STEM");
	}//while
	for($i = 0; $i < count($waardes); $i++) {
		sqlUp("Spelers",$waardes[$i],$eisen[$i]);
	}
	return;
}//regelGrafrover

//regelt de stem van Klaas Vaak(en)
function regelKlaasVaak($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Klaas Vaak' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		mailKlaas($id,$sid);
	}//while
	return;
}//regelKlaasVaak

//regelt de stem van de Genezer(s)
function regelGenezer($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Genezer' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)){
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		echo "Genezer $id beschermt $stem.\n";
		mailActie($id,1,$sid,"STEM");
	}//while
	return;
}//regelGenezer

//regelt de stem van de Ziener(s)
function regelZiener($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Ziener' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)){
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		mailActie($id,1,$sid,"STEM");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelZiener

//regelt de stem van de Dwaas (of Dwazen): 
//mailt een rol die ongelijk is aan de gevraagde rol
function regelDwaas($sid) {
	$andereRollen = 0; //andere rollen dan de Dwaas: liefst 2
	$rollen = array();
	$resultaat = sqlSel("Spelers","SPEL=$sid"); //pak alle rollen in het spel
	while($speler = sqlFet($resultaat)) {
		if($speler['ROL'] != "Dwaas" && !in_array($speler['ROL'],$rol)) {
			$andereRollen++;
		}
		array_push($rollen,$speler['ROL']);
	}
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Dwaas' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		$resultaat2 = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
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
		mailDwaas($id,$gezien,$sid);
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelDwaas

//regelt de stem van de Priester(s)
function regelPriester($sid) {
	//vul een array met alle 'onzuivere' rollen:
	$rollen = array("Weerwolf","Witte Weerwolf","Welp","Vampier","Psychopaat",
		"Fluitspeler","Heks","Grafrover","Slet","Verleidster");
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Priester' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		$resultaat2 = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
		$speler2 = sqlFet($resultaat2);
		$rol = $speler2['ROL'];
		if(in_array($rol,$rollen)) {
			mailActie($id,1,$sid,"STEM");
		}
		else {
			mailActie($id,2,$sid,"STEM");
		}
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelPriester

//regelt de stem van de Slet(ten)
function regelSlet($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Slet' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		mailActie($id,1,$sid,"STEM");
	}//while
	return;
}//regelSlet

//regelt de stem van de Verleidster(s)
function regelVerleid($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Verleidster' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($target == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		mailActie($id,1,$sid,"STEM");
	}//while
	return;
}//regelVerleid

//regelt de stem van de Goochelaar(s)
function regelGoochel($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Goochelaar' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		$stem2 = $speler['EXTRA_STEM'];
		if($id == $stem) {
			mailActie($id,1,$sid,"EXTRA_STEM");
		}
		else if($id == $stem2) {
			mailActie($id,1,$sid,"STEM");
		}
		else {
			mailGoochel($id,$sid);
		}
	}//while
	return;
}//regelGoochel

//regelt alle stemmen van de Weerwolven/Vampiers 
//(geef rol mee die van toepassing is)
function regelWWVP($rol,$sid) {
	$spelers = array(); //voor alle WW's/VP's (wie moeten gemaild worden?)
	$vlag = ($rol == "Weerwolf") ? 0 : 1; //voor de mail naar het Onschuldige Meisje
	$alleTargets = array(-1); //init met blanco om errors te voorkomen
	$stemmen = array(0);
	if($rol == "Weerwolf") {
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND (ROL='Weerwolf' OR ROL='Witte Weerwolf') 
			AND LEVEND=1");
	}
	else if($rol == "Vampier") {
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND ROL='Vampier' AND LEVEND=1");
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
			echo "$id heeft niet gestemd.\n";
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
		mailWWVPActie($spelers,NULL,$rol,9,$sid);
		$vlag += 2;
	}
	else {
		$keys = hoogsteStem($stemmen); //bepaalt de hoogste stem
		$slachtoffer = $alleTargets[array_rand($keys)];
		shuffle($spelers); // randomise spelers
		mailWWVPActie($spelers,$slachtoffer,$rol,1,$sid);
		vermoord($slachtoffer,$sid);
	}
	if(inSpel("Onschuldige Meisje",$sid)) {
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND ROL='Onschuldige Meisje' AND LEVEND=1");
		while($meisje = sqlFet($resultaat)) {
			$id = $meisje['ID'];
			if(!wordtWakker($id,$sid)) {
				continue;
			}
			mailOnschuldig($id,$alleTargets,$stemmen,$vlag,$sid);
		}//while
	}//if
	return;
}//regelWWVP

//regelt de stem van de Psychopaat(en)
function regelPsycho($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Psychopaat' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		echo "Psychopaat: $stem vermoordt.\n";
		vermoord($stem,$sid);
		mailActie($id,1,$sid,"STEM");
		verwijderStem($id,"STEM");
	}//while
	return;
}//regelPsycho

//regelt de stem van de Witte Weerwolf(of meerdere...)
function regelWitteWW($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	if(!$spel['TWEEDE_NACHT']) {
		sqlUp("Spellen","TWEEDE_NACHT=1","SID=$sid");
		return;
	}
	sqlUp("Spellen","TWEEDE_NACHT=0","SID=$sid");

	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Witte Weerwolf' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"STEM");
			continue;
		}
		echo "Witte Weerwolf: $stem vermoordt.\n";
		vermoord($stem,$sid);
		mailActie($id,1,$sid,"EXTRA_STEM");
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelWitteWW

//regelt de stemmen van de Heks(en): 
//als beide stemmen leeg zijn heeft zij niet gestemd
//als de eerste stem 'blanco' is, doet zij niets.
function regelHeks($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Heks' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['STEM'];
		$stem2 = $speler['EXTRA_STEM'];
		//bitwise flag: 48 = beide dranken, 32 = gif, 16 = elixer, anders niks
		$drank = $speler['SPELFLAGS']; 
		if($stem == "" && $stem2 == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		$verhaal = 0; //weer een vlag: 
					//1 = zichzelf redden, 2 = ander redden, 4 = vergiftigen
		heeftGestemd($id);
		if($stem != "") {
			if($stem == -1) {
				mailActie($id,9,$sid,"STEM");
				continue;
			}
			echo "Heks $id wekt $stem tot leven.\n";
			herleef($stem,$sid);
			$drank -= 16;
			if($id == $stem) {
				$verhaal += 1;
			}
			else {
				$verhaal += 2;
			}
		}
		if($stem2 != "") {
			echo "Heks $id vergiftigt $stem2.\n";
			zetDood($stem2,$sid);
			$drank -= 32;
			$verhaal += 4;
		}
		sqlUp("Spelers","SPELFLAGS=$drank","SPEL=$sid AND ID=$id");
		mailHeks($id,$stem,$stem2,$verhaal,$sid);
		verwijderStem($id,"STEM");
		verwijderStem($id,"EXTRA_STEM");
	}//while
	return;
}//regelHeks

//regelt de stemmen van de Fluitspeler(s): 
//als de eerste stem leeg is heeft hij niet gestemd
function regelFluit($sid) {
	$alleTargets = array(-1);
	$stemmen = array(0);
	$spelers = array();
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Fluitspeler' AND LEVEND=1");
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
			echo "$id heeft niet gestemd.\n";
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
		mailFSActie($spelers,NULL,NULL,9,$sid);
		return;
	}
	$keys = hoogsteStem($stemmen);
	if(count($keys) > 1) { // een tweede slachtoffer met evenveel stemmen
		shuffle($keys);
		$slachtoffer = $alleTargets[$keys[0]];
		$slachtoffer2 = $alleTargets[$keys[1]];
	}
	else {
		$slachtoffer = $alleTargets[$keys[0]];
		$alleTargets = delArrayElement($alleTargets,$keys[0]);
		$stemmen = delArrayElement($stemmen,$keys[0]);
		if(empty($alleTargets)) {
			mailFSActie($spelers,$slachtoffer,NULL,1,$sid);
			return;
		}
		$keys = hoogsteStem($stemmen);
		$slachtoffer2 = $alleTargets[array_rand($keys)];
	}//else
	mailFSActie($spelers,$slachtoffer,$slachtoffer2,2,$sid);
	sqlUp("Spelers","SPELFLAGS=SPELFLAGS+1",
		"((SPELFLAGS & 1) = 0) AND (ID=$slachtoffer OR ID=$slachtoffer2)");

	return;
}//regelFluit

//regelt de EXTRA_STEM van de Waarschuwer(s)
function regelWaarschuw($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Waarschuwer' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($target == -1) {
			mailActie($id,9,$sid,"EXTRA_STEM");
			continue;
		}
		mailActie($id,1,$sid,"EXTRA_STEM"); 
	}//while
	return;
}//regelWaarschuw

//regelt de EXTRA_STEM van de Schout(en)
function regelSchout($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Schout' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"EXTRA_STEM");
			continue;
		}
		mailActie($id,1,$sid,"EXTRA_STEM");
	}//while
	return;
}//regelSchout

//regelt de EXTRA_STEM van de Raaf (of Raven)
function regelRaaf($sid) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Raaf' AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		if(!wordtWakker($id,$sid)) {
			continue;
		}
		$stem = $speler['EXTRA_STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1) {
			mailActie($id,9,$sid,"EXTRA_STEM");
			continue;
		}
		mailActie($id,1,$sid,"EXTRA_STEM");
	}//while
	return;
}//regelRaaf

//regelt de Jager: indien er een dode Jager is, doodt dan ook zijn stem
//dood hij nog een Jager, Opdrachtgever, Geliefde of Burgemeester:
//ga dan terug in fase naar dood1.
function regelJager($sid,$fase) {
	$flag = false;
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ROL='Jager' AND LEVEND=1 AND 
		NIEUW_DOOD=1 AND ((SPELFLAGS & 4) = 0)");
	$resultaat2 = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat2);
	
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['EXTRA_STEM'];
		echo "Jager gevonden: $id\n";
		sqlUp("Spelers","LEVEND=0","SPEL=$sid AND ID=$id");
		if($stem == "") {
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		if($stem == -1 || $stem == $id) {
			mailActie($id,9,$sid,"EXTRA_STEM");
			continue;
		}
		else {
			zetDood($stem,$sid);
			$fase2 = ($fase == 10) ? 1 : 2; //nacht of dag?
			mailActie($id,$fase2,$sid,"EXTRA_STEM");
			$resultaat2 = sqlSel("Spelers","SPEL=$sid AND ID=$stem");
			$slachtoffer = sqlFet($resultaat2);
			if($slachtoffer['ROL'] == "Jager" || 
				$slachtoffer['GELIEFDE'] != "" || 
				$slachtoffer['LIJFWACHT'] != "" || 
				$stem == $spel['BURGEMEESTER']) { //opnieuw naar regeldood1
					echo "$stem is een Jager, Geliefde, ";
					echo "Burgemeester of heeft een Lijfwacht.\n";
					zetFase($fase,$sid);
					$flag = true;
			}
		}
		verwijderStem($id,"EXTRA_STEM");
	}//while
	if($flag) {
		sqlData($resultaat,0);
		while($speler = sqlFet($resultaat)) {
			$id = $speler['ID'];
			sqlUp("Spelers","SPELFLAGS=SPELFLAGS+4","SPEL=$sid AND ID=$id");
		}
	}
	return;
}//regelJager

//regelt het testament van de Burgemeester
//als hij "blanco" heeft gestemd, dan komen nieuwe verkiezingen.
function regelBurgemeester($sid) {
	$resultaat = sqlSel("Spellen","SID=$sid");
	$spel = sqlFet($resultaat);
	$id = $spel['BURGEMEESTER'];
	if($id == -1) { //geen Burgemeester in het spel
		return;
	}
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND ID=$id AND NIEUW_DOOD=1");
	if(sqlNum($resultaat) == 0) { //geen dode Burgemeester
		return;
	}
	$speler = sqlFet($resultaat);
	echo "Burgemeester $id is gestorven.\n";
	$stem = $speler['STEM'];
	if($stem == "") {
		stemGemist($id);
		sqlUp("Spellen","BURGEMEESTER=NULL","SID=$sid");
		echo "$id heeft niet gestemd.\n";
		return;
	}
	heeftGestemd($id);
	if($stem == -1) {
		mailTestament($id,9,$sid);
		sqlUp("Spellen","BURGEMEESTER=NULL","SID=$sid");
		return;
	}
	mailTestament($id,1,$sid);
	sqlUp("Spellen","BURGEMEESTER=$stem","SID=$sid");
	verwijderStem($id,"STEM");
	return;
}//regelBurgemeester

//checkt voor alle doden of er nog Geliefden of Lijfwachten zijn.
//eerst controleren op Lijfwachten (misschien is deze nl. ook Geliefde...)
function regelDood1($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1 AND NIEUW_DOOD=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$lijfwacht = $speler['LIJFWACHT'];
		if($lijfwacht != "" && isLevend($lijfwacht)) {
			zetDood($lijfwacht,$sid);
			herleef($id,$sid);
			echo "Lijfwacht $lijfwacht sterft om $id te redden.\n";
		}
	}//while

	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1 AND NIEUW_DOOD=1");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$geliefde = $speler['GELIEFDE'];
		if($geliefde != "") { 
			zetDood($geliefde,$sid);
			echo "Geliefde $geliefde kan niet leven zonder $id en sterft.\n";
		}
	}//while
	return;
}//regelDood1

//zet alle nieuw_dode spelers op dood
//en, als Dorpsoudste dood is: verwijder dan de gaven van alle burgers
//(Opdrachtgever verliest Lijfwacht en ontdekte Dorpsgek sterft)
function regelDood2($sid,$fase) {
	$resultaat = sqlSel("Spelers",
		"SPEL=$sid AND NIEUW_DOOD=1 AND ROL='Dorpsoudste'");
	if(sqlNum($resultaat) > 0) {
		sqlUp("Spelers","NIEUW_DOOD=1",
			"SPEL=$sid AND ROL='Dorpsgek' AND LEVEND=1 AND 
			((SPELFLAGS & 128) = 128)");
		sqlUp("Spelers","ROL='Burger',LIJFWACHT=NULL",
			"SPEL=$sid AND LEVEND=1 AND NIEUW_DOOD=0 AND ROL IN
			('Cupido','Genezer','Ziener','Slet','Verleidster','Heks','Jager',
			'Klaas Vaak','Priester','Goochelaar','Onschuldige Meisje',
			'Grafrover','Waarschuwer','Raaf','Schout','Dorpsoudste','Zondebok',
			'Opdrachtgever','Dorpsgek')");
		echo "Dorpsoudste is dood, en iedereen verliest zijn gaven.\n";
		zetFase($fase,$sid); // opnieuw checken of een geliefde dood moet: loop
	}
	else {
		sqlUp("Spelers","LEVEND=0",
			"SPEL=$sid AND LEVEND=1 AND NIEUW_DOOD=1");
		
		//inactieve spelers eruit halen:
		$resultaat = sqlSel("Spellen","SID=$sid");
		$spel = sqlFet($resultaat);
		$strengheid = $spel['STRENGHEID'];
		sqlUp("Spelers","LEVEND=0,NIEUW_DOOD=1,GELIEFDE=NULL",
			"SPEL=$sid AND LEVEND=1 AND GEMIST>=$strengheid");
	}
	return;
}//regelDood2

//regelt alle stemmen van de Burgemeesterverkiezing
function regelBurgVerk($sid) {
	$kandidaten = array(-1); // init met blanco, om errors te voorkomen
	$stemmen = array(0);
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1 AND NIEUW_DOOD=0");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		verwijderStem($id,"STEM");
		echo "$id stemt: $stem.\n";
		$key = array_search($stem,$kandidaten);
		if($key == false) { // nog niet eerder op deze persoon gestemd
			array_push($kandidaten,$stem);
			array_push($stemmen,1);
		}
		else { // anders: tel 1 stem erbij op
			$stemmen[$key]++;
		}
	}//while

	$blanco = array_keys($kandidaten,-1);
	foreach($blanco as $blancokey) {
		$kandidaten = delArrayElement($kandidaten,$blancokey);
		$stemmen = delArrayElement($stemmen,$blancokey);
	}
	if(empty($stemmen)) { //er waren enkel blanco stemmen...
		//TODO mail verhaaltje
		sqlUp("Spellen","BURGEMEESTER=-1","SID=$sid");
		echo "Burgemeesterverkiezing: enkel blanco stemmen geteld.\n";
		return;
	}
	$keys = hoogsteStem($stemmen);
	$burgemeester = $kandidaten[array_rand($keys)];
	sqlUp("Spellen","BURGEMEESTER='$burgemeester'","SID=$sid");
	echo "De nieuwe Burgemeester is $burgemeester.\n";
	//TODO mail verhaaltje
	return;
}//regelBurgVerk

//regelt alle stemmen van de Brandstapel
function regelBrand($sid) {
	$kandidaten = array(-1); // init met blanco om errors te voorkomen
	$stemmen = array(0);
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1 AND NIEUW_DOOD=0");
	while($speler = sqlFet($resultaat)) {
		$id = $speler['ID'];
		$stem = $speler['STEM'];
		if($stem == "") { //niet gestemd...
			stemGemist($id);
			echo "$id heeft niet gestemd.\n";
			continue;
		}
		heeftGestemd($id);
		echo "$id stemt: $stem.\n";
		$i = stemWaarde($id,$sid);
		$key = array_search($stem,$kandidaten);
		if($key == false) { // nog niet eerder op deze persoon gestemd
			array_push($kandidaten,$stem);
			array_push($stemmen,$i);
		}
		else { // anders: tel er $i aantal stemmen erbij op
			$stemmen[$key] += $i;
		}
	}//while

	if(inSpel("Raaf",$sid)) { // voeg het Teken van de Raaf bij de stemmen
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Raaf'");
		while($speler = sqlFet($resultaat)) {
			$id = $speler['ID'];
			$teken = $speler['EXTRA_STEM'];
			verwijderStem($id,"EXTRA_STEM");
			if($teken == "" || $teken == -1) {
				continue;
			}
			$key = array_search($teken,$kandidaten);
			if($key == false) {
				array_push($kandidaten,$teken);
				array_push($stemmen,2);
			}
			else {
				$stemmen[$key] += 2;
			}
			echo "$teken krijgt het Teken van de Raaf.\n";
		}//while
	}//if

	$dorpsgek = inSpel("Dorpsgek",$sid);
	if($dorpsgek) { // check op ontdekte Dorpsgek
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND LEVEND=1 AND ROL='Dorpsgek' AND 
			((SPELFLAGS & 128) = 128");
		while($speler = sqlFet($resultaat)) {
			$id = $speler['ID'];
			echo "$id is gek en op hem wordt niet gestemd.\n";
			$key = array_search($id,$kandidaten);
			if($key == false) {
				continue;
			}
			$kandidaten = delArrayElement($kandidaten,$key);
			$stemmen = delArrayElement($stemmen,$key);
		}//while
	}//if

	if(inSpel("Schout",$sid)) { // haal opgesloten spelers uit de lijst
		$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Schout'");
		while($speler = sqlFet($resultaat)) {
			$id = $speler['ID'];
			$opgesloten = $speler['EXTRA_STEM'];
			verwijderStem($id,"EXTRA_STEM");
			if($opgesloten == "" || $opgesloten == -1) {
				continue;
			}
			echo "$opgesloten is opgesloten.\n";
			$key = array_search($opgesloten,$kandidaten);
			if($key == false) { // dan zit hij er niet tussen, doe niets
				continue;
			}
			$kandidaten = delArrayElement($kandidaten,$key);
			$stemmen = delArrayElement($stemmen,$key);
		}//while
	}//if

	//haal alle schuldgevoel weg
	sqlUp("Spelers","SPELFLAGS=(SPELFLAGS-2)",
		"SPEL=$sid AND (SPELFLAGS & 2)");

	$blanco = array_keys($kandidaten,-1);
	foreach($blanco as $blancokey) {
		$kandidaten = delArrayElement($kandidaten,$blancokey);
		$stemmen = delArrayElement($stemmen,$blancokey);
	}
	if(empty($stemmen)) { //enkel blanco stemmen, check voor Zondebok
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND LEVEND=1 AND ROL='Zondebok'");
		if(sqlNum($resultaat) > 0) {
			$zondebokken = array();
			while($speler = sqlFet($resultaat)) {
				$id = $speler['ID'];
				array_push($zondebokken,$id);
			}
			$slachtoffer = array_rand($zondebokken);
			sqlUp("Spelers","SPELFLAGS=SPELFLAGS+256","SPEL=$sid AND ID=$id");
			zetDood($slachtoffer,$sid);
			echo "Zondebok $slachtoffer gaat op de Brandstapel!\n";
		}//if
		else {
			echo "Geen slachtoffer.\n";
		}
		return;
	}//if
	$keys = hoogsteStem($stemmen);
	if(count($keys) > 1) { // gelijkspel
		echo "Gelijkspel! Hoogste stemmen: " . $kandidaten[$keys[0]] . 
			" (" . $stemmen[$keys[0]] . ") en " . $kandidaten[$keys[1]] . 
			" (" . $stemmen[$keys[1]] . ").\n";
		$resultaat = sqlSel("Spelers",
			"SPEL=$sid AND LEVEND=1 AND ROL='Zondebok'");
		if(sqlNum($resultaat) > 0) { //check voor Zondebok
			$zondebokken = array();
			while($speler = sqlFet($resultaat)) {
				$id = $speler['ID'];
				array_push($zondebokken,$id);
			}
			$slachtoffer = array_rand($zondebokken);
			sqlUp("Spelers","SPELFLAGS=SPELFLAGS+256","SPEL=$sid AND ID=$id");
			zetDood($slachtoffer,$sid);
			echo "Zondebok $slachtoffer gaat op de Brandstapel!\n";
		}//if
		else { // anders bepaalt de Burgemeester
			$resultaat = sqlSel("Spelers",
				"SPEL=$sid AND ID IN 
				(SELECT BURGEMEESTER FROM Spellen WHERE SID=$sid)");
			$burgemeester = sqlFet($resultaat);
			$id = $burgemeester['ID'];
			if($id == -1) { //geen burgemeester, geen slachtoffer
				echo "Geen Burgemeester, geen slachtoffer!\n";
				return;
			}
			$stem = $burgemeester['STEM'];
			if($stem == "" || $stem == -1) {
				echo "Burgemeester zegt: geen slachtoffer.\n";
				return;
			}
			zetDood($stem,$sid);
			echo "Burgemeester $id zegt dat $stem op de Brandstapel gaat.\n";
			return;
		}//else
	}//if

	$slachtoffer = $kandidaten[$keys[0]];
	$aantal = $stemmen[$keys[0]];
	if($dorpsgek) { //check op Dorpsgek
		$resultaat = sql("Spelers",
			"SPEL=$sid AND ID='$slachtoffer' AND ROL='Dorpsgek'");
		if(sqlNum($resultaat) > 0) { // slachtoffer is gek
			echo "$slachtoffer blijkt gek en mag leven.\n";
			sqlUp("Spelers","SPELFLAGS=SPELFLAGS+128",
				"SPEL=$sid AND ID='$slachtoffer'");
			return;
		}
	}//if	
	zetDood($slachtoffer,$sid);
	echo "$slachtoffer eindigt op de Brandstapel met $aantal stemmen.\n";
	return;
	//TODO mail verhaaltje
}//regelBrand

//regelt de stemmen van de Zondebok
function regelZonde($sid) {
	$resultaat = sqlSel("Spelers","SPEL=$sid AND ROL='Zondebok' AND 
		((SPELFLAGS & 256) = 256)");
	if(sqlNum($resultaat) == 0) {
		return;
	}
	$zondebok = sqlFet($resultaat);
	$id = $zondebok['ID'];
	$stem = $zondebok['STEM'];
	sqlUp("Spelers","SPELFLAGS=SPELFLAGS-256","SPEL=$sid AND ID=$id");
	if($stem == "") {
		stemGemist($id);
		echo "$id heeft niet gestemd.\n";
		continue;
	}
	heeftGestemd($id);
	verwijderStem($id,"STEM");
	if($stem == -1) {
		mailActie($id,9,$sid,"STEM");
		continue;
	}

	//ga alle spelers af en kijk of deze in de stem zitten (met preg_match)
	$slachtoffers = array();
	$resultaat = sqlSel("Spelers","SPEL=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) { //voor elke speler
		$spnaam = $speler['NAAM'];
		if(preg_match("/\b$spnaam\b/i",$stem)) {
			sqlUp("Spelers","SPELFLAGS=SPELFLAGS+2",
				"SPEL=$sid AND NAAM='$spnaam' AND ((SPELFLAGS & 2) = 0)");
			array_push($slachtoffers,$spnaam);
		}
	}//while
	shuffle($slachtoffers);
	mailZonde($id,$slachtoffers,$sid);
	return;
}//regelZonde

?>
