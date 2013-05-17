<?php

//geeft een willekeurig verhaal volgens de criteria
function geefVerhaal($thema,$rol,$fase) {
	$resultaat = sqlSel("Verhalen",
		"THEMA='$thema' AND ROL='$rol' AND FASE=$fase");
	if(sqlNum($resultaat) == 0) {
		echo "Geen verhalen, probeer default...\n";
		$resultaat = sqlSel("Verhalen",
			"THEMA='default' AND ROL='$rol' AND FASE=$fase");
		if(sqlNum($resultaat) == 0) { //ook geen default verhaal...
			echo "Geen default, error.\n";
			stuurError(
				"Geen default verhaal voor fase $fase van $rol.");
		}
	}//if
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaal

//geeft een willekeurig verhaal volgens de criteria met als extra: 
//het aantal levende en overleden (in het verhaal) spelers
//eventueel mogen minder levende spelers gebruikt worden
function geefVerhaalGroep($thema,$rol,$fase,$levend,$dood) {
	$resultaat = sqlSel("Verhalen",
		"THEMA='$thema' AND ROL='$rol' AND FASE=$fase AND 
		LEVEND<=$levend AND DOOD=$dood");
	if(sqlNum($resultaat) == 0) {
		echo "Geen verhalen, probeer default...\n";
		$resultaat = sqlSel("Verhalen",
			"THEMA='default' AND ROL='$rol' AND FASE=$fase AND 
			LEVEND<=$levend AND DOOD=$dood");
		if(sqlNum($resultaat) == 0) { //ook geen default verhaal...
			echo "Geen default, error.\n";
			stuurError(
				"Geen default verhaal voor fase $fase van $rol," .
				"met $levend levende spelers en $dood slachtoffers");
		}
	}//if
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaalGroep

//geeft een willekeurig verhaal volgens de criteria met als extra: 
//het aantal levende en overleden (in het verhaal) spelers
//eventueel mogen minder dode spelers gebruikt worden
//(gebruikt voor Zondebok en Onschuldige Meisje)
function geefVerhaalGroep2($thema,$rol,$fase,$levend,$dood) {
	$resultaat = sqlSel("Verhalen",
		"THEMA='$thema' AND ROL='$rol' AND FASE=$fase AND 
		LEVEND=$levend AND DOOD<=$dood");
	if(sqlNum($resultaat) == 0) {
		echo "Geen verhalen, probeer default...\n";
		$resultaat = sqlSel("Verhalen",
			"THEMA='default' AND ROL='$rol' AND FASE=$fase AND 
			LEVEND=$levend AND DOOD<=$dood");
		if(sqlNum($resultaat) == 0) { //ook geen default verhaal...
			echo "Geen default, error.\n";
			stuurError(
				"Geen default verhaal voor fase $fase van $rol," .
				"met $levend levende spelers en $dood slachtoffers");
		}
	}//if
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];
}//geefVerhaalGroep2


function vulIn($namen,$rollen,$geslachten,$deadline,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($namen as $speler => $naam) {
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslacht[$speler][$key]",
					$alternatief[$geslachten[$speler]],$text);
			}
		}
		$text = str_replace("naam[$speler]",$naam,$text);
		$text = str_replace("rol[$speler]",$rollen[$speler],$text);
		$text = str_replace("deadline[0]",$deadline,$text);
	}
	return $text;
}//vulIn

function auteur($auteur,$text) {
	$text .= "<br /><br />";
	$text .= "<font size='1'>";
	$text .= "Verhaaltje geschreven door $auteur.";
	$text .= "</font>";
	return $text;
}//auteur

?>
