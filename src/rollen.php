<?php

function verdeelRol($sid) {
	$alleSpelers = array();
	$rolTuples = array();
	$lijst = array("Burger",
		"Weerwolf",
		"Ziener",
		"Heks",
		"Cupido",
		"Jager",
		"Genezer",
		"Slet",
		"Dorpsoudste",
		"Dorpsgek",
		"Raaf",
		"Goochelaar",
		"Grafrover",
		"Vampier",
		"Welp",
		"Witte Weerwolf",
		"Klaas Vaak",
		"Zondebok",
		"Dwaas",
		"Schout",
		"Fluitspeler",
		"Onschuldige Meisje",
		"Priester",
		"Psychopaat",
		"Verleidster",
		"Opdrachtgever",
		"Dief",
		"Waarschuwer");
	$resultaat = sqlSel(3,"SID=$sid");
	while($speler = sqlFet($resultaat)) {
		array_push($alleSpelers,$speler['ID']);
	}
	$aantal = count($alleSpelers);
	$resultaat = sqlSel(2,"AANTAL=$aantal");
	if(sqlNum($resultaat) == 0) {
		schrijfLog($sid,"Geen rolverdeling voor speleraantal $aantal.\n");
		stuurError2("Geen rolverdeling voor speleraantal $aantal " . 
			"van spel $sid.\n",$sid);
		return;
	}
	while($rolverdeling = sqlFet($resultaat)) {
		array_push($rolTuples,$rolverdeling);
	}
	$key = array_rand($rolTuples);
	$rolverdeling = $rolTuples[$key];
	$rid = $rolverdeling['RID'];
	schrijfLog($sid,"Gekozen rolverdeling: $rid.\n");
	$rollen = explode(",",$rolverdeling['ROLLEN']);
	shuffle($alleSpelers);
	if($rolverdeling['BURGEMEESTER']) {
		$burgemeester = "NULL";
		schrijfLog($sid,"Met Burgemeester.\n");
	}
	else {
		$burgemeester = -1;
		schrijfLog($sid,"Zonder Burgemeester.\n");
	}
	sqlUp(4,"LEVEND=$aantal,DOOD=0,ROLLEN=$rid,BURGEMEESTER=$burgemeester",
		"SID=$sid");
	$teller = 0;
	for($i = 0; $i < count($rollen); $i++) {
		while($rollen[$i] > 0) {
			$dezeSpeler = $alleSpelers[$teller];
			$rol = $lijst[$i];
			$spelflags = 0;
			if($rol == "Heks") {
				$spelflags = 384; //beide drankjes (128 + 256)
			}
			else if($rol == "Dorpsoudste") {
				$spelflags = 128; //extra leven
			}
			sqlUp(3,"ROL='$rol',SPELFLAGS=$spelflags",
				"ID=$dezeSpeler");
			schrijfLog($sid,"$dezeSpeler is nu een $rol.\n");
			$teller++;
			$rollen[$i]--;
		}//while
	}//for

	//pas easter-eggs toe
	easterRol($sid);
	
	return;
}//verdeelRol

//als $rol in het spel zit, verwisselt de rol van $naam
//met een willekeurige speler met $rol
//(gebruikt voor easter-eggs)
function verwisselRol($naam,$rol,$sid) {
	//pak de rol
	$resultaat = sqlSel(3,"SID=$sid AND ROL='$rol'");
	if(sqlNum($resultaat) == 0) { //als rol niet in spel: stop
		return;
	}

	//pak de speler
	$resultaat2 = sqlSel(3,"SID=$sid AND NAAM='$naam'");
	if(sqlNum($resultaat2) == 0) { //als speler niet in spel: stop
		return;
	}
	$target = sqlFet($resultaat2);

	//pak nu een willekeurige andere speler om mee te verwisselen
	$spelers = array();
	while($speler = sqlFet($resultaat)) {
		array_push($spelers,$speler);
	}
	$key = array_rand($spelers);
	$speler = $spelers[$key];

	//pak de rollen en verwissel
	$id1 = $target['ID'];
	$rol1 = $target['ROL'];
	$flags1 = $target['SPELFLAGS'];
	$id2 = $speler['ID'];
	$rol2 = $speler['ROL'];
	$flags2 = $speler['SPELFLAGS'];

	sqlUp(3,"ROL='$rol2',SPELFLAGS=$flags2","ID=$id1");
	sqlUp(3,"ROL='$rol1',SPELFLAGS=$flags1","ID=$id2");

	return;
}//verwisselRol

?>
