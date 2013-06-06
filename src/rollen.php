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
		echo "Geen rolverdeling voor speleraantal $aantal.\n";
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
	echo "Gekozen rolverdeling: $rid.\n";
	$rollen = explode(",",$rolverdeling['ROLLEN']);
	shuffle($alleSpelers);
	if($rolverdeling['BURGEMEESTER']) {
		$burgemeester = "NULL";
		echo "Met Burgemeester.\n";
	}
	else {
		$burgemeester = -1;
		echo "Zonder Burgemeester.\n";
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
				$spelflags = 48; //beide drankjes
			}
			else if($rol == "Dorpsoudste") {
				$spelflags = 64; //extra leven
			}
			sqlUp(3,"ROL='$rol',SPELFLAGS=$spelflags",
				"ID=$dezeSpeler");
			echo "$dezeSpeler is nu een $rol.\n";
			$teller++;
			$rollen[$i]--;
		}
	}//for
	
	return;
}//verdeelRol

?>
