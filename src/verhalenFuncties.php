<?php

//geeft een willekeurig verhaal volgens de criteria
function geefVerhaal($thema,$rol,$fase,$sid) {
	$resultaat = sqlSel("Verhalen",
		"THEMA=$thema AND ROL='$rol' AND FASE=$fase");
	if(sqlNum($resultaat) == 0) {
		echo "Geen verhalen, probeer default...\n";
		$resultaat = sqlSel("Verhalen",
			"ROL='$rol' AND FASE=$fase AND THEMA IN
			(SELECT TID FROM Themas WHERE TNAAM='default')");
		if(sqlNum($resultaat) == 0) { //ook geen default verhaal...
			echo "Geen default, error.\n";
			stuurError2(
				"Geen default verhaal voor fase $fase van $rol.",$sid);
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
function geefVerhaalGroep($thema,$rol,$fase,$levend,$dood,$sid) {
	$resultaat = sqlSel("Verhalen",
		"THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
		LEVEND<=$levend AND DOOD=$dood");
	if(sqlNum($resultaat) == 0) {
		echo "Geen verhalen, probeer default...\n";
		$resultaat = sqlSel("Verhalen",
			"ROL='$rol' AND FASE=$fase AND LEVEND<=$levend AND DOOD=$dood AND
			THEMA IN (SELECT TID FROM Themas WHERE TNAAM='default')");
		if(sqlNum($resultaat) == 0) { //ook geen default verhaal...
			echo "Geen default, error.\n";
			stuurError2(
				"Geen default verhaal voor fase $fase van $rol," .
				"met $levend levende spelers en $dood slachtoffers",$sid);
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
function geefVerhaalGroep2($thema,$rol,$fase,$levend,$dood,$sid) {
	$resultaat = sqlSel("Verhalen",
		"THEMA=$thema AND ROL='$rol' AND FASE=$fase AND 
		LEVEND=$levend AND DOOD<=$dood");
	if(sqlNum($resultaat) == 0) {
		echo "Geen verhalen, probeer default...\n";
		$resultaat = sqlSel("Verhalen",
			"ROL='$rol' AND FASE=$fase AND LEVEND=$levend AND DOOD<=$dood AND
			THEMA IN (SELECT TID FROM Themas WHERE TNAAM='default'");
		if(sqlNum($resultaat) == 0) { //ook geen default verhaal...
			echo "Geen default, error.\n";
			stuurError2(
				"Geen default verhaal voor fase $fase van $rol," .
				"met $levend levende spelers en $dood slachtoffers",$sid);
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

function geefVerhaalRolverdeling($thema,$rol,$sid) {
	$resultaat = sqlSel("Verhalen",
		"THEMA=$thema AND ROL='$rol' AND FASE=-1");
	if(sqlNum($resultaat) == 0) {
		echo "Geen intro voor specifieke rol, probeer algemeen...\n";
		$resultaat = sqlSel("Verhalen",
			"THEMA=$thema AND ROL='Rolverdeling' AND FASE=-1");
		if(sqlNum($resultaat) == 0) {
			echo "Geen algemene intro voor dit thema, ";
			echo "probeer specifieke default...\n";
			$resultaat = sqlSel("Verhalen",
				"ROL='$rol' AND FASE=-1 AND THEMA IN
				(SELECT TID FROM Themas WHERE TNAAM='default')");
			if(sqlNum($resultaat) == 0) {
				echo "Geen specifieke default, probeer algemene default...\n";
				$resultaat = sqlSel("Verhalen",
					"ROL='Rolverdeling' AND FASE=-1 AND THEMA IN
					(SELECT TID FROM Themas WHERE TNAAM='default')");
				if(sqlNum($resultaat) == 0) { //helemaal fucked
					echo "Geen algemene default; error.\n";				
					stuurError2(
						"Geen default verhaal voor rolverdeling.",$sid);
				}
			}//if
		}//if
	}//if
	$tuples = array();
	while($verhaal = sqlFet($resultaat)) {
		array_push($tuples,$verhaal);
	}
	$key = array_rand($tuples);
	return $tuples[$key];

}//geefVerhaalRolverdeling

function auteur($auteur,$text) {
	$text .= "<br /><br />";
	$text .= "<font size='1'>";
	$text .= "Verhaaltje geschreven door $auteur.";
	$text .= "</font>";
	return $text;
}//auteur

?>
