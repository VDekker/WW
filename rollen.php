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
	$resultaat = sqlSel("Spelers","SPEL='$sid'");
	while($speler = sqlFet($resultaat)) {
		array_push($alleSpelers,$speler['NAAM']);
	}
	$aantal = count($alleSpelers);
	$resultaat = sqlSel("Rollen","AANTAL='$aantal'");
	if(sqlNum($resultaat) == 0) {
		echo "Geen rolverdeling voor speleraantal $aantal.\n";
		return false;
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
		$burgemeester = "'blanco'";
	}
	sqlUp("Spellen","LEVEND=$aantal,DOOD=0,ROLLEN=$rid,BURGEMEESTER=$burgemeester","SID='$sid'");
	$teller = 0;
	for($i = 0; $i < count($rollen); $i++) {
		while($rollen[$i] > 0) {
			$dezeSpeler = $alleSpelers[$teller];
			$rol = $lijst[$i];
			$drank = "NULL";
			if($rol == "Heks") {
				$drank = 3;
			}
			sqlUp("Spelers","ROL='$rol',HEKS_DRANK=$drank","SPEL='$sid' AND NAAM='$dezeSpeler'");
			echo "$dezeSpeler is nu een $rol.\n";
			$teller++;
			$rollen[$i]--;
		}
	}//for

	/*
	//vals spelen: Jenneke en Victor zijn nooit Burgers!
	$resultaat = sqlSel("Spelers",
		"SPEL='$sid' AND (NAAM='Jenneke' OR NAAM='Victor')");
	while($cheater = sqlFet($resultaat)) {
		if($cheater['ROL'] == "Burger") {
			verdeelRol($sid);
		}
	}//while
	*/
	
	return;
}//verdeelRol

?>
