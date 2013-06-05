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
				"Geen default verhaal voor fase $fase van $rol, " .
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
				"Geen default verhaal voor fase $fase van $rol, " .
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

function vulIn($spelers,$deadline,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($spelers as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Dwaas") {
			$rol = "Ziener";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslacht[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}//if
		$text = str_replace("naam[$i]",$naam,$text);
		$text = str_replace("rol[$i]",$rol,$text);
	}//foreach
	$text = str_replace("deadline[0]",$deadline,$text);
	return $text;
}//vulIn

//aparte functie om de Dwaas een verkeerde rol te geven
function vulInDwaas($spelers,$gezien,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($spelers as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Dwaas") {
			$rol = "Ziener";
		}
		if($i == 1) {
			$rol = $gezien;
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslacht[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}//if
		$text = str_replace("naam[$i]",$naam,$text);
		$text = str_replace("rol[$i]",$rol,$text);
	}//foreach
	return $text;
}//vulInDwaas

//aparte functie om de rol van de Witte WW te verbergen
function vulInWW($spelers,$deadline,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($spelers as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Witte Weerwolf") {
			$rol = "Weerwolf";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslacht[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}//if
		$text = str_replace("naam[$i]",$naam,$text);
		$text = str_replace("rol[$i]",$rol,$text);
	}//foreach
	$text = str_replace("deadline[0]",$deadline,$text);
	return $text;
}//vulInWW

function vulInDood($tuplesL,$tuplesD,$deadline,$text,$geswoorden) {
	$paren = explode('%',$geswoorden);
	foreach($tuplesL as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Dwaas") {
			$rol = "Ziener";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslacht[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}
		$text = str_replace("naam[$i]",$naam,$text);
		$text = str_replace("rol[$i]",$rol,$text);
	}
	foreach($tuplesD as $i => $speler) {
		$geslacht = $speler['SPELERFLAGS'] & 1;
		$naam = $speler['NAAM'];
		$rol = $speler['ROL'];
		if($rol == "Dwaas") {
			$rol = "Ziener";
		}
		if(!empty($geswoorden)) {
			foreach($paren as $key => $paar) {
				$alternatief = explode('&',$paar);
				$text = str_replace("geslachtDood[$i][$key]",
					$alternatief[$geslacht],$text);
			}
		}
		$text = str_replace("naamDood[$i]",$naam,$text);
		$text = str_replace("rolDood[$i]",$rol,$text);
	}
	$text = str_replace("deadline[0]",$deadline,$text);
	return $text;
}//vulInDood

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

function keuzeHeks($text,$heks,$doden,$sid) {
	if(count($doden) > 0) {
		$text .= "<br /><br />";
		if(count($doden) == 1) {
			$text .= "Je kunt deze speler tot leven wekken:<br />";
		}
		else {
			$text .= "Je kunt een van deze spelers tot leven wekken:<br />";
		}
		$text .= "<ul>";
		foreach($doden as $speler) {
			$text .= "<li>" . $speler['NAAM'] . "</li>";
		}
		$text .= "</ul>";
	}//if
	$resultaat = sqlSel("Spelers","SID=$sid AND LEVEND=1");
	$levenden = sqlNum($resultaat);
	if($levenden == 0) {
		return $text;
	}
	$vlag = false;
	while($speler = sqlFet($resultaat)) {
		if($speler['NAAM'] == $heks) {
			$vlag = true;
			break;
		}
	}//while
	if($levenden == 1 && $vlag) { //enige doel is ikzelf
		return $text;
	}
	$text .= "<br /><br />";
	if($levenden == 1) {
		$text .= "Je kunt deze speler vergiftigen:<br />";
	}
	else {
		$text .= "Je kunt een van deze spelers vergiftigen:<br />";
	}
	$text .= "<ul>";
	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		if($naam == $heks) {
			continue;
		}
		$text .= "<li>$naam</li>";
	}//while
	$text .= "</ul>";
	return $text;
}//keuzeHeks

function keuzeJager($text,$jager,$sid) {
	$resultaat = sqlSel("Spelers","SID=$sid AND ((LEVEND & 1) = 1)");
	$levenden = sqlNum($resultaat);
	if($levenden == 0) {
		return $text;
	}
	$vlag = false;
	while($speler = sqlFet($resultaat)) {
		if($speler['NAAM'] == $jager) {
			$vlag = true;
			break;
		}
	}//while
	if($levenden == 1 && $vlag) { //enige doel is ikzelf
		return $text;
	}
	$text .= "<br /><br />";
	if($levenden == 1) {
		$text .= "Je kunt deze speler neerschieten:<br />";
	}
	else {
		$text .= "Je kunt een van deze spelers neerschieten:<br />";
	}
	$text .= "<ul>";
	while($speler = sqlFet($resultaat)) {
		$naam = $speler['NAAM'];
		if($naam == $jager) {
			continue;
		}
		$text .= "<li>$naam</li>";
	}//while
	$text .= "</ul>";
	return $text;
}//keuzeJager

function auteur($auteur,$text) {
	$text .= "<br /><br />";
	$text .= "<font size='1'>";
	$text .= "Verhaaltje geschreven door $auteur.";
	$text .= "</font>";
	return $text;
}//auteur

function ontwaakVerhaal(&$text,&$samenvatting,&$auteur,$spel) {
	echo "Aangeroepen: ontwaakVerhaal.\n";
	$tuplesL = array(); //L voor levende spelers
	$tuplesD = array(); //D voor dode spelers
	$tuplesS = array(); //S voor Jagers/Geliefden (speciaal verhaal)
	$resArray = array();
	$thema = $spel['THEMA'];
	$sid = $spel['SID'];
	$resultaat = sqlSel("Spelers","SID=$sid AND LEVEND=1");
	while($speler = sqlFet($resultaat)) {
		array_push($tuplesL,$speler);
	}
	$resultaat2 = sqlSel("Spelers","SID=$sid AND ((LEVEND & 2) = 2)");

	$speciaalVerhaal = false;
	$doelwitten = array(); //id's van de doelwitten van de jagers
	while($speler = sqlFet($resultaat2)) {
		if($speler['ROL'] == "Jager" && ($speler['SPELFLAGS'] & 4) == 4) {
			$speciaalVerhaal = true;
			$res = sqlSel("Spelers","ID=" . $speler['EXTRA_STEM']);
			$target = sqlFet($res);
			array_push($tuplesS,$target);
			array_push($doelwitten,$speler['EXTRA_STEM']);
		}
		if($speler['GELIEFDE'] != "" && 
			($speler['LEVEND'] & 512) == 0) {
				$speciaalVerhaal = true;
				$res = sqlSel("Spelers","ID=" . $speler['GELIEFDE']);
				$target = sqlFet($res);
				array_push($tuplesS,$target);
		}
		array_push($resArray,$speler);
	}//while

	foreach($resArray as $speler) {
		if(!in_array($speler,$tuplesS) && 
			!($speler['ROL'] == "Jager" && ($speler['SPELFLAGS'] & 4) == 4) && 
			!($speler['GELIEFDE'] != "" && ($speler['LEVEND'] & 512) == 0)) {
			array_push($tuplesD,$speler);
		}
	}
	$dood = count($tuplesD);
	$levend = count($tuplesL) + count($tuplesS);

	//bij normaal verhaal (geen jagers/geliefden dood)
	if(!$speciaalVerhaal) {
		echo "Normaal verhaal gewenst.\n";
		$verhaal = geefVerhaalGroep($thema,"Algemeen",0,$levend,$dood,$sid);
		$text = $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		array_push($auteur,$verhaal['AUTEUR']);
		$text = vulInDood($tuplesL,$tuplesD,"",$text,$geswoorden);
		//TODO samenvatting maken
		return;
	}
	
	//maak de boom van jagers/geliefden
	sqlData($resultaat2,0);	
	$boom = array();
	$boom = maakBoom(0,$tuplesS,$boom,0,$resArray);

	//ontwaken/begin
	$verhaal = geefVerhaalGroep($thema,"Algemeen",1,$levend,$dood,$sid);
	$text = $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulInDood(array_merge($tuplesL,$tuplesS),
		$tuplesD,"",$text,$geswoorden);

	//tuplesS bijvullen (beginnende jagers/geliefden toevoegen)
	foreach($boom as $id => $target) {
		$resultaat = sqlSel("Spelers","NAAM='$id' AND SID=$sid");
		$tuple = sqlFet($resultaat);
		array_push($tuplesS,$tuple);
	}

	//nu dingen aanvullen met behulp van de boom van jagers/geliefden TODO
	foreach($boom as $id => $target) {
		leesBoom($boom[$id],$id,$text,$samenvatting,$auteur,
			$tuplesL,$tuplesS,$thema,"Algemeen",$sid);
	}

	//en samenvatting maken TODO
	return;
}//ontwaakVerhaal

function plakSamenvatting($samenvatting,$text) {
	$text .= "<br /><hr />";
	$text .= "Samenvatting:<br />";
	$text .= "<br />";
	$text .= $samenvatting;
	return $text;
}

//maakt een 'boom' van de jagers en hun doelwitten, evenals de geliefden.
//Dit komt allemaal in een array terecht, met veel sub-arrays.
//De array-keys wijzen op de id van de speler (jager/geliefde), 
//behalve bij de bladeren: dan is het de array-value.
function maakBoom($id,$specialeTuples,$boom,$diepte,$resultaat) {
	if($diepte == 0) {
		foreach($resultaat as $speler) {
			$key = array_search($speler,$specialeTuples);
			if($key === false) {
				if($speler['ROL'] == "Jager" && 
					($speler['SPELFLAGS'] & 4) == 4) {
						$id = $speler['NAAM'];
						$boom[$id] = array();
						array_push($boom[$id],$speler['EXTRA_STEM']);
						$boom[$id] = maakBoom($id,$specialeTuples,$boom[$id],
							$diepte+1,$resultaat);
					}
			}
		}
	}
	else {
		foreach($specialeTuples as $key => $speler) {
			$vlag = false;
			$key = array_search($speler['ID'],$boom);
			if($key !== false) { //speler zit in een blad van de boom...
				$vlag2 = false;
				$id = $speler['NAAM'];

				//als jager: maak een knoop van het blad
				if($speler['ROL'] == "Jager" &&
					($speler['SPELFLAGS'] & 4) == 4) {
						$vlag2 = true;
						if(!$vlag) {
							$boom[$id] = array();
							$vlag = true;
						}
						array_push($boom[$id],$speler['EXTRA_STEM']);
					}
				if($speler['GELIEFDE'] != "" &&
					($speler['SPELFLAGS'] & 512) == 0) {
						$vlag2 = true;
						if(!$vlag) {
							$boom[$id] = array();
							$vlag = true;
						}
						array_push($boom[$id],$speler['GELIEFDE']);
					}
				if($vlag2) {
					unset($boom[$key]);//verwijder het oude blad
					$boom[$id] = maakBoom($id,$specialeTuples,$boom[$id],
						$diepte+1,$resultaat);
				}
			}
		}
	}
	return $boom;
}//maakBoom

//zet een 'boom' om in verhaal
//hierbij kan $rol 'Algemeen', 'Jager', 'Geliefde' of 'Brandstapel' zijn
//$levende zijn de levende spelers, en $speciale zijn de spelers in de boom,
//elk van hen zijn tuple-arrays
//TODO samenvatting regelen
function leesBoom($boom,$id,&$text,&$samenvatting,&$auteur,
	$levende,&$speciale,$thema,$rol,$sid) {
		//vind de id
		$index = array();
		if(!array_search_recursive($id,$speciale,$index)) {
			//hier hoor je niet te komen...
			$error = "Iets ging fout in leesBoom, ";
			$error .= "en de functie kwam op onontdekt terrein: ";
			$error .= "array_search_recursive() kon de naam $id";
			$error .= "die in de boom stond niet vinden ";
			$error .= "in de array met speciale tuples:<br />";
			$error .= "<table border = '1'><tr>";
			foreach($speciale[0] as $key => $value) {
				if(is_int($key)) {
					continue;
				}//if
				$error .= "<th>";
				$error .= htmlspecialchars($key);
				$error .= "</th>";
			}//foreach
			$error .= "</tr>";
			foreach($speciale as $tuple) {
				$error .= "<tr>";
				foreach($tuple as $key => $value) {
					if(is_int($key)) {
						continue;
					}//if
					$error .= "<th>";
					$error .= htmlspecialchars($value);
					$error .= "</th>";
				}//foreach
				$error .= "</tr>";
			}
			$error .= "</table>";
			stuurError2($error,$sid);
		}
		$tuple = $speciale[$index[0]];
		$speciale = delArrayElement($speciale,$index[0]);

		//kondig id aan
		$verhaal = geefVerhaalGroep($thema,$rol,4,
			(count($levende)+count($speciale)),1,$sid);
		$text .= $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		array_push($auteur,$verhaal['AUTEUR']);
		$text = vulInDood(array_merge($levende,$speciale),
			array($tuple),"",$text,$geswoorden);

		//als id == jager: leesBoom op zijn target
		if($tuple['ROL'] == "Jager" &&
			($tuple['SPELFLAGS'] & 4) == 4) {
				$doelwit = $tuple['EXTRA_STEM'];
				$resultaat = sqlSel("Spelers","ID=$doelwit");
				$tuple2 = sqlFet($resultaat);
				$naam = $tuple2['NAAM'];
				if(array_key_exists($naam,$boom)) {
					//doelwit is een knoop: recursief
					leesBoom($boom[$naam],$naam,$text,$samenvatting,
						$auteur,$levende,$speciale,$thema,"Jager",$sid);
				}
				else {
					//doelwit is een blad
					leesBlad($naam,$text,$samenvatting,$auteur,
						$levende,$speciale,$thema,"Jager",$sid);
				}
		}//if

		//maak id dood
		$verhaal = geefVerhaalGroep($thema,$rol,5,
			(count($levende)+count($speciale)),1,$sid);
		$text .= $verhaal['VERHAAL'];
		$geswoorden = $verhaal['GESLACHT'];
		array_push($auteur,$verhaal['AUTEUR']);
		$text = vulInDood(array_merge($levende,$speciale),
			array($tuple),"",$text,$geswoorden);

		//als id == geliefde: kondig geliefde aan etc.
		if($tuple['GELIEFDE'] != "" &&
			($tuple['SPELFLAGS'] & 512) == 0) {
				$geliefde = $tuple['GELIEFDE'];
				$resultaat = sqlSel("Spelers","ID=$geliefde");
				$tuple2 = sqlFet($resultaat);
				$naam = $tuple2['NAAM'];
				if(array_key_exists($naam,$boom)) {
					//doelwit is knoop: recursief
					leesBoom($boom[$naam],$naam,$text,$samenvatting,
						$auteur,$levende,$speciale,$thema,"Geliefde",$sid);
				}
				else {
					//doelwit is een blad
					leesBlad($naam,$text,$samenvatting,$auteur,
						$levende,$speciale,$thema,"Geliefde",$sid);
				}
		}//if
	}//leesBoom

//leest een blad van de boom (zie leesBoom()).
//TODO samenvatting
function leesBlad($id,&$text,&$samenvatting,&$auteur,
	$levende,&$speciale,$thema,$rol,$sid) {

	//vind de id
	$index = array();
	array_search_recursive($id,$speciale,$index);
	$tuple = $speciale[$index[0]];
	$speciale = delArrayElement($speciale,$index[0]);

	//kondig aan
	$verhaal = geefVerhaalGroep($thema,$rol,4,
		(count($levende)+count($speciale)),1,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulInDood(array_merge($levende,$speciale),
		array($tuple),"",$text,$geswoorden);

	//en vermoord
	$verhaal = geefVerhaalGroep($thema,$rol,5,
		(count($levende)+count($speciale)),1,$sid);
	$text .= $verhaal['VERHAAL'];
	$geswoorden = $verhaal['GESLACHT'];
	array_push($auteur,$verhaal['AUTEUR']);
	$text = vulInDood(array_merge($levende,$speciale),
		array($tuple),"",$text,$geswoorden);

	return;
}//leesBlad

//vind een value in multidimensional arrays;
//indexes wordt gevuld met de keys die leiden naar de value
//van internet: 
//http://stackoverflow.com/questions/4232497/
//array-search-recursive-help-me-find-where-value-exists-in-multidimensional
function array_search_recursive($needle, $haystack, &$indexes=array()) {
	foreach ($haystack as $key => $value) {
		if (is_array($value)) {
			$indexes[] = $key;
			$status = array_search_recursive($needle, $value, $indexes);
			if ($status) {
				return true;
			} else {
				$indexes = array();
			}
		} else if ($value == $needle) {
			$indexes[] = $key;
			return true;
		}
	}
	return false;
}//array_search_recursive

?>
