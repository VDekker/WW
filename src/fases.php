<?php

function fases() {
	$resultaat = sqlSel(4,"STATUS=0");
	while($spel = sqlFet($resultaat)) { // voor elk spel...
		$sid = $spel['SID'];
		schrijfLog($sid,"Ongewonnen spel gevonden: $sid.\n");
		$fase = $spel['FASE'];
		$duur = $spel['DUUR'];
		schrijfLog($sid,"Fase: $fase, sinds $duur.\n");
		if($spel['RONDE'] == 0) { // initialiseer-fase van het spel
			
			switch($fase) {
				case 0: //wacht op inschrijvingen
					if(genoegGewacht($sid)) {
						zetFase(1,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 1: //verdeel rollen, initialiseer alles
					verdeelRol($sid);
					mailRolverdeling($spel);
					mailInleiding($spel);//mail algemeen (iedereen gaat slapen...)
					if(inSpel("Dief",$sid)) {
						mailWakker("Dief",$spel);
						zetFase(2,$sid);
					}
					else {
						zetFase(3,$sid);
					}
					break;
				case 2: //wacht op Dief
					if(genoegGewacht($sid)) {
						zetFase(3,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 3:
					if(inSpel("Dief",$sid)) {
						regelDief($spel);
					}
					if(inSpel("Cupido",$sid)) {
						mailWakker("Cupido",$spel);
						zetFase(4,$sid);
					}
					else {
						zetFase(5,$sid);
					}
					break;
				case 4: //wacht op Cupido
					if(genoegGewacht($sid)) {
						zetFase(5,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 5:
					if(inSpel("Cupido",$sid)) {
						regelCupido($spel);
					}
					if(inSpel("Opdrachtgever",$sid)) {
						mailWakker("Opdrachtgever",$spel);
						zetFase(6,$sid);
					}
					else {
						zetFase(7,$sid);
					}
					break;
				case 6: //wacht op Opdrachtgever
					if(genoegGewacht($sid)) {
						zetFase(7,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 7:
					if(inSpel("Opdrachtgever",$sid)) {
						regelOpdracht($spel);
					}
					sqlUp(4,"RONDE=RONDE+1","SID=$sid");
					zetFase(3,$sid);
			}//switch

		}//if
		else { // loop-fase van het spel

			switch($fase) {
				case 0:
					if(inSpel("Welp",$sid)) {
						regelWelp($spel);
					}
					if(inSpel("Grafrover",$sid)) {
						mailWakker("Grafrover",$spel);
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
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 2:
					if(inSpel("Grafrover",$sid)) {
						regelGrafrover($spel);
					}
					zetFase(3,$sid);
				case 3:
					if(inSpel("Klaas Vaak",$sid)) {
						mailWakker("Klaas Vaak",$spel);
						zetFase(4,$sid);
					}
					else {
						zetFase(5,$sid);
					}
					break;
				case 4:
					if(genoegGewacht($sid)) {
						zetFase(5,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 5:
					if(inSpel("Klaas Vaak",$sid)) {
						regelKlaasVaak($spel);
					}
					if(inSpel("Genezer",$sid)) {
						mailWakker("Genezer",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Ziener",$sid)) {
						mailWakker("Ziener",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Dwaas",$sid)) {
						mailWakker("Dwaas",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Priester",$sid)) {
						mailWakker("Priester",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Slet",$sid)) {
						mailWakker("Slet",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Verleidster",$sid)) {
						mailWakker("Verleidster",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Goochelaar",$sid)) {
						mailWakker("Goochelaar",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Weerwolf",$sid)) {
						mailGroepWakker("Weerwolf",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Vampier",$sid)) {
						mailGroepWakker("Vampier",$spel);
						zetFase(6,$sid);
					}
					if(inSpel("Psychopaat",$sid)) {
						mailWakker("Psychopaat",$spel);
						zetFase(6,$sid);
					}
					if((($spel['FLAGS'] & 1) == 1) && 
						inSpel("Witte Weerwolf",$sid)) {
						mailWakker("Witte Weerwolf",$spel);
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
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 7:
					if(inSpel("Genezer",$sid)) {
						regelGenezer($spel);
					}
					if(inSpel("Ziener",$sid)) { 
						regelZiener($spel);
					}
					if(inSpel("Dwaas",$sid)) {
						regelDwaas($spel);
					}
					if(inSpel("Priester",$sid)) {
						regelPriester($spel);
					}
					if(inSpel("Slet",$sid)) {
						regelSlet($spel);
					}
					if(inSpel("Verleidster",$sid)) {
						regelVerleid($spel);
					}
					if(inSpel("Goochelaar",$sid)) {
						regelGoochel($spel);
					}
					if(inSpel("Weerwolf",$sid)) {
						regelWWVP("Weerwolf",$spel);
					}
					if(inSpel("Vampier",$sid)) {
						regelWWVP("Vampier",$spel);
					}
					if(inSpel("Psychopaat",$sid)) {
						regelPsycho($spel);
					}
					if(inSpel("Witte Weerwolf",$sid)) {
						regelWitteWW($spel);
					}
					if(inSpel("Heks",$sid)) {
						mailHeksWakker($spel);
						zetFase(8,$sid);
					}
					if(inSpel("Fluitspeler",$sid)) {
						mailGroepWakker("Fluitspeler",$spel);
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
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 9:
					if(inSpel("Heks",$sid)) {
						regelHeksActie($spel);
					}
					if(inSpel("Fluitspeler",$sid)) {
						regelFluit($spel);
					}
					if(inSpel("Waarschuwer",$sid)) {
						mailWakker("Waarschuwer",$spel);
						zetFase(11,$sid);
					}
					if(inSpel("Raaf",$sid)) {
						mailWakker("Raaf",$spel);
						zetFase(11,$sid);
					}
					if(inSpel("Schout",$sid)) {
						mailWakker("Schout",$spel);
						zetFase(11,$sid);
					}
				case 10:
					regelDood1($sid);
					if(inSpel("Jager",$sid)) {
						mailJagerWakker(0,$spel);
						zetFase(11,$sid);
					}
					if(mailBurgWakker($spel)) {
						zetFase(11,$sid);
					}
					if(geefFase($sid) == 9 || geefFase($sid) == 10) {
						zetFase(12,$sid);
					}
					break;
				case 11:
					if(genoegGewacht($sid)) {
						zetFase(12,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 12:
					if(inSpel("Jager",$sid)) {
						regelJager(2,$spel);
					}
					if(inSpel("Waarschuwer",$sid)) {
						regelWaarschuw($spel);
					}
					if(inSpel("Schout",$sid)) {
						regelSchout($spel);
					}
					if(inSpel("Raaf",$sid)) {
						regelRaaf($spel);
					}
					regelBurgemeester($spel);
					if(geefFase($sid) == 12) {
						regelDood2($sid,10);
						regelZetNULL1($sid);
						if(gewonnen(0,$spel)) {
							sqlUp(4,"STATUS=3","SID=$sid");
						}
						else if(empty($spel['BURGEMEESTER'])) {
							mailAlgemeenVerkiezing($spel);
							zetDood2($sid);
							zetFase(13,$sid);
						}
						else {
							mailAlgemeenBrandstapel(0,array(),$spel);
							zetDood2($sid);
							zetFase(15,$sid);
						}
					}//if
					break;
				case 13:
					if(genoegGewacht($sid)) {
						zetFase(14,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 14:
					$overzicht = regelBurgVerk($sid);
					mailAlgemeenBrandstapel(1,$overzicht,$spel);
					zetFase(15,$sid);
				case 15:
					if(genoegGewacht($sid)) {
						zetFase(16,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 16:
					regelBrand($spel);
				case 17:
					regelDood1($sid);
					$vlag = false;
					if(mailJagerWakker(1,$spel)) {
						zetFase(18,$sid);
						$vlag = true;	
					}
					if(mailZondeWakker($spel)) {
						zetFase(18,$sid);
						$vlag = true;	
					}
					if(!$vlag) {
						zetFase(20,$sid);
					}
					break;
				case 18:
					if(genoegGewacht($sid)) {
						zetFase(19,$sid);
					}
					else {
						schrijfLog($sid,"Blijf wachten.\n");
						break;
					}
				case 19:
					if(inSpel("Jager",$sid)) {
						regelJager(3,$spel);
					}
					if(inSpel("Zondebok",$sid)) {
						regelZonde($spel);
					}
					if(geefFase($sid) == 19) {
						zetFase(20,$sid);
					}
					break;
				case 20:
					regelDood2($sid,17);
					if(geefFase($sid) == 20) {
						if(gewonnen(1,$spel)) {
							sqlUp(4,"STATUS=3","SID=$sid");
						}
						else {
							mailAlgemeenInslapen($spel);
							zetDood2($sid);
							zetFase(0,$sid);
							sqlUp(4,"RONDE=RONDE+1","SID=$sid");
						}
					}//if
					break;
			}//switch

		}//else
	}//while
	return;
}//fases

?>
