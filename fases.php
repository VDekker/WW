<?php

function fases() {
	$resultaat = sqlSel("Spellen","");
	echo "Fases begonnen.\n";
	while($spel = sqlFet($resultaat)) { // voor elk spel...
		if($spel['GEWONNEN'] == 1) { // dit werkt niet in query
			continue;
		}
		$sid = $spel['SID'];
		echo "Ongewonnen spel gevonden: $sid.\n";
		$fase = $spel['FASE'];
		echo "Fase: $fase.\n";
		if($spel['INIT']) { // initialiseer-fase van het spel
			
			switch($fase) {
				case 0:
					//mail uitgenodigden?
					zetFase(1,$sid);
					break;
				case 1:
					if(genoegGewacht($sid)) {
						zetFase(2,$sid);
					}
					else {
						break;
					}
				case 2:
					verdeelRol($sid);
					//mailRolverdeling($sid); TODO uncomment
					//mail algemeen (iedereen gaat slapen...) TODO maak
					if(inSpel("Dief",$sid)) {
						//mailWakker("Dief",$sid); TODO uncomment
						zetFase(3,$sid);
					}
					else {
						zetFase(4,$sid);
					}
					break;
				case 3:
					if(genoegGewacht($sid)) {
						zetFase(4,$sid);
					}
					else {
						break;
					}
				case 4:
					if(inSpel("Dief",$sid)) {
						regelDief($sid);
					}
					if(inSpel("Cupido",$sid)) {
						//mailWakker("Cupido",$sid); TODO uncomment
						zetFase(5,$sid);
					}
					else {
						zetFase(6,$sid);
					}
					break;
				case 5:
					if(genoegGewacht($sid)) {
						zetFase(6,$sid);
					}
					else {
						break;
					}
				case 6:
					if(inSpel("Cupido",$sid)) {
						regelCupido($sid);
					}
					if(inSpel("Opdrachtgever",$sid)) {
						//mailWakker("Opdrachtgever",$sid); TODO uncomment
						zetFase(7,$sid);
					}
					else {
						// ga naar de loop (begin na grafrover, bij case 3!)
					}
					break;
				case 7:
					if(genoegGewacht($sid)) {
						zetFase(8,$sid);
					}
					else {
						break;
					}
				case 8:
					regelOpdracht($sid);
					//ga naar de loop (begin na grafrover, bij case 3!)
			}//switch

		}//if
		else { // loop-fase van het spel

			switch($fase) {
				case 0:
					if(inSpel("Welp",$sid)) {
						regelWelp($sid);
					}
					if(inSpel("Grafrover",$sid)) {
						//mailWakker("Grafrover",$sid); TODO uncomment
						zetFase(1,$sid);
					}
					else {
						zetFase(3,$sid);
					}
					break;
				case 1:
					if(genoegGewacht($sid)) {
						zetFase(2,$sid);
					}
					else {
						break;
					}
				case 2:
					if(inSpel("Grafrover",$sid)) {
						regelGrafrover($sid);
					}
					zetFase(3,$sid);
				case 3:
					if(inSpel("Klaas Vaak",$sid)) {
						mailWakker("Klaas Vaak",$sid);
						zetFase(4,$sid);
					}
					else {
						//zetFase(5,$sid); TODO uncomment
					}
					break;
				case 4:
					if(genoegGewacht($sid)) {
						zetFase(5,$sid);
					}
					else {
						break;
					}
				case 5:
					if(inSpel("Klaas Vaak",$sid)) {
						regelKlaasVaak($sid);
					}
					if(inSpel("Genezer",$sid)) {
						//mailWakker("Genezer",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Ziener",$sid)) {
						//mailWakker("Ziener",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Dwaas",$sid)) {
						//mailWakker("Dwaas",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Priester",$sid)) {
						//mailWakker("Priester",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Slet",$sid)) {
						//mailWakker("Slet",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Verleidster",$sid)) {
						//mailWakker("Verleidster",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Goochelaar",$sid)) {
						//mailWakker("Goochelaar",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Weerwolf",$sid)) {
						//mailGroepWakker("Weerwolf",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Vampier",$sid)) {
						//mailGroepWakker("Vampier",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(inSpel("Psychopaat",$sid)) {
						//mailWakker("Psychopaat",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if($spel['TWEEDE_NACHT'] && inSpel("Witte Weerwolf",$sid)) {
						//mailWakker("Witte Weerwolf",$sid); TODO uncomment
						zetFase(6,$sid);
					}
					if(geefFase($sid) == 5) { //fase is nog onaangepast
						zetFase(7,$sid);
					}
					break;	
				case 6:
					if(genoegGewacht($sid)) {
						zetFase(7,$sid);
					}
					else {
						break;
					}
				case 7:
					if(inSpel("Genezer",$sid)) {
						regelGenezer($sid);
					}
					if(inSpel("Ziener",$sid)) { 
						regelZiener($sid);
					}
					if(inSpel("Dwaas",$sid)) {
						regelDwaas($sid);
					}
					if(inSpel("Priester",$sid)) {
						regelPriester($sid);
					}
					if(inSpel("Slet",$sid)) {
						regelSlet($sid);
					}
					if(inSpel("Verleidster",$sid)) {
						regelVerleid($sid);
					}
					if(inSpel("Goochelaar",$sid)) {
						regelGoochel($sid);
					}
					if(inSpel("Weerwolf",$sid)) {
						regelWWVP("Weerwolf",$sid);
					}
					if(inSpel("Vampier",$sid)) {
						regelWWVP("Vampier",$sid);
					}
					if(inSpel("Psychopaat",$sid)) {
						regelPsycho($sid);
					}
					if(inSpel("Witte Weerwolf",$sid)) {
						regelWitteWW($sid);
					}
					if(inSpel("Heks",$sid)) {
						//mailHeksWakker($sid); TODO uncomment
						zetFase(8,$sid);
					}
					if(inSpel("Fluitspeler",$sid)) {
						//mailGroepWakker("Fluitspeler",$sid);
						zetFase(8,$sid);
					}
					if(geefFase($sid) == 7) {
						zetFase(9,$sid);
					}
					break;
				case 8:
					if(genoegGewacht($sid)) {
						zetFase(9,$sid);
					}
					else {
						break;
					}
				case 9:
					if(inSpel("Heks",$sid)) {
						regelHeksActie($sid);
					}
					if(inSpel("Fluitspeler",$sid)) {
						regelFluit($sid);
					}
					if(inSpel("Waarschuwer",$sid)) {
						//mailWakker("Waarschuwer",$sid); TODO uncomment
						zetFase(11,$sid);
					}
					if(inSpel("Raaf",$sid)) {
						//mailWakker("Raaf",$sid); TODO uncomment
						zetFase(11,$sid);
					}
					if(inSpel("Schout",$sid)) {
						//mailWakker("Schout",$sid); TODO uncomment
						zetFase(11,$sid);
					}
				case 10:
					echo "Begin regeldood.\n";
					regelDood1($sid);
					if(inSpel("Jager",$sid)) {
						//TODO misschien mail Jager
						zetFase(11,$sid);
					}
					//als Burgemeester dood
					//  mail deze
					//  zetFase(11,$sid);
					if(geefFase($sid) == 9 || geefFase($sid) == 10) {
						zetFase(12,$sid);
					}
					break;
				case 11:
					if(genoegGewacht($sid)) {
						zetFase(12,$sid);
					}
					else {
						break;
					}
				case 12:
					if(inSpel("Jager",$sid)) {
						regelJager($sid,10);
					}
					if(inSpel("Waarschuwer",$sid)) {
						regelWaarschuw($sid);
					}
					if(inSpel("Schout",$sid)) {
						regelSchout($sid);
					}
					if(inSpel("Raaf",$sid)) {
						regelRaaf($sid);
					}
					regelBurgemeester($sid);
					if(geefFase($sid) == 12) {
						regelDood2($sid,10);
						regelZetNULL1($sid);
						if(gewonnen($sid)) {
							sqlUp("Spellen","GEWONNEN=1","SID='$sid'");
							//stuur mails
						}
						else if(empty($spel['BURGEMEESTER'])) {
							//stuur mails (ontwaken, 
							//nieuwe burgemeesterverkiezing) en
							//zetFase(13,$sid);
						}
						else {
							//stuur mails (ontwaken, brandstapelverkiezing) en
							//zetFase(15,$sid);
						}
					}//if
					break;
				case 13:
					if(genoegGewacht($sid)) {
						zetFase(14,$sid);
					}
					else {
						break;
					}
				case 14:
					regelBurgVerk($sid);
					//stuur mails (yay, burgemeester en brandstapel...)
					zetFase(15,$sid);
				case 15:
					if(genoegGewacht($sid)) {
						zetFase(16,$sid);
					}
					else {
						break;
					}
				case 16:
					regelBrand($sid);
				case 17:
					regelDood1($sid);
					//TODO if Jager dood of Zondebok dood
					//  mail deze, en 
					//  zetFase(18,$sid);
					//  break;
					//anders
					//  zetFase(20,$sid);
					//  break;
				case 18:
					if(genoegGewacht($sid)) {
						zetFase(19,$sid);
					}
					else {
						break;
					}
				case 19:
					if(inSpel("Jager",$sid)) {
						regelJager($sid,17);
					}
					if(inSpel("Zondebok",$sid)) {
						regelZonde($sid);
					}
					if(geefFase($sid) == 19) {
						zetFase(20,$sid);
					}
					break;
				case 20:
					//regelDood2($sid,17); TODO uncomment
					if(geefFase($sid) == 20) {
						if(gewonnen($sid)) {
							sqlUp("Spellen","GEWONNEN=1","SID='$sid'");
							//mail gewonnen
						}
						//mail algemeen (brandstapel)
						//regelZetNULL2($sid); //TODO uncomment
						//zetFase(0,$sid); //TODO uncomment
					}
					break;
			}//switch

		}//else
	}//while
	return;
}//fases

?>