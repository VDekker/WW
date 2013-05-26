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
	$resultaat = sqlSel("Spelers","SID=$sid");
	while($speler = sqlFet($resultaat)) {
		array_push($alleSpelers,$speler['ID']);
	}
	$aantal = count($alleSpelers);
	$resultaat = sqlSel("Rollen","AANTAL=$aantal");
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
	$rollen = explode(",",$rolverdeling['ROLLEN']);
	shuffle($alleSpelers);
	if($rolverdeling['BURGEMEESTER']) {
		$burgemeester = "NULL";
	}
	else {
		$burgemeester = -1;
	}
	sqlUp("Spellen",
		"LEVEND=$aantal,DOOD=0,ROLLEN=$rid,BURGEMEESTER=$burgemeester",
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
			sqlUp("Spelers","ROL='$rol',SPELFLAGS=$spelflags",
				"ID=$dezeSpeler");
			echo "$dezeSpeler is nu een $rol.\n";
			$teller++;
			$rollen[$i]--;
		}
	}//for
	
	return;
}//verdeelRol

?>
