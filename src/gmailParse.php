<?php

function gmailParse() {
	global $thuis,$gmconnect,$admins;

	$berichtstatus = "UNSEEN";
	$emails = imap_search($gmconnect,$berichtstatus);
	$totaal = imap_num_msg($gmconnect);
	schrijfLog(-1,"Totaal aantal emails: $totaal.\n");

	if($emails) {
		//sorteer de emails met oudste eerst. Voor nieuwste eerst: sort()
		rsort($emails);

		//ga elke mail langs
		foreach($emails as $email_nummer) {

			//pak informatie
			$header = imap_fetch_overview($gmconnect,$email_nummer,0);
			$bericht1 = imap_fetchbody($gmconnect,$email_nummer,1.1);
			if(empty($bericht1)) {
				$bericht1 = imap_fetchbody($gmconnect,$email_nummer,1);
			}
			$onderwerp = $header[0]->subject;
			$afzender = $header[0]->from;

			//pak de echte afzender: slechts het adres
			$matches = array();
			preg_match("'<(.*?)>'si",$afzender,$matches);
			if(!empty($matches)) {
				$afzender = $matches[1];
			}

			//mails van onszelf kunnen worden genegeerd
			if($afzender == $thuis) {
				continue;
			}

			$bericht = htmlentities($bericht1);
			
			schrijflog(-1,"Mail van: '$afzender'\n");

			//ga nu parsen: zoek wat voor mail het is
			if(preg_match("/config/i",$onderwerp)) {
				schrijfLog(-1,"Config mail gevonden.\n");
				if(!in_array($afzender,$admins)) {
					schrijfLog(-1,"Afzender is geen admin; doe niets.\n");
					continue;
				}
				config($afzender,$onderwerp,$bericht);
			}
			else if(preg_match("/\bhelp\b/i",$onderwerp)) {
				help($afzender,$onderwerp,$bericht);
			}
			else { //anders is het een stem/inschrijving
				//zoek welk spel
				$resultaat = sqlSel(4,"");
				$gevonden = false;
				while($spel = sqlFet($resultaat)) {
					$snaam = $spel['SNAAM'];
					if(preg_match("/\b$snaam\b/i",$onderwerp)) {
						$gevonden = true;
						break;
					}
				}//while
				if($gevonden) { // als een speltitel in het onderwerp staat
					$sid = $spel['SID'];
					if($spel['STATUS'] > 1) { // voor gewonnen en gestopte spellen
						stuurFoutStop($adres,$snaam);
					}
					else if ($spel['STATUS'] == 1) {
						stuurFoutPauze($adres,$snaam);
					}
					else { //anders: check of speler, of inschrijving-fase
						$id = spelerID($afzender,$sid);
						if($id == -1 && 
							($spel['RONDE'] != 0 || $spel['FASE'] != 0)) {
								//adres niet herkend in het spel
								schrijfLog($spel['SID'],
									"Geen speler herkend.\n");
								stuurFoutAdres($afzender,$spel['SNAAM']);
						}
						else if ($id == -2) {
							//speler is dood en hoeft niet te stemmen
							schrijfLog($spel['SID'],"Speler is dood.\n");
							stuurFoutDood($afzender,$spel['SNAAM']);
						}
						else {
							parseStem($id,$afzender,$spel,$bericht,$onderwerp);
						}
					}//else
				}//if
				else {
					//geen spelnaam herkend: mail dit naar de afzender
					schrijfLog(-1,"Verkeerd onderwerp (geen spelnaam " . 
						"herkend).\n");
					stuurFoutOnderwerp($afzender);
				}
			}//else
		}//foreach
	}//if

	return;
}//gmailParse()

function zoekControle() {
	global $thuis,$gmconnect;

	$flag = false; // is Controle gevonden?
	$berichtstatus = "UNSEEN";
	$emails = imap_search($gmconnect,$berichtstatus);
	if($emails) {
		// sorteer de emails (nieuwste eerst)
		sort($emails);
		foreach($emails as $email_nummer) {
			$header = imap_fetch_overview($gmconnect,$email_nummer,0);
			$onderwerp = $header[0]->subject;
			$afzender = $header[0]->from;

			if($onderwerp == "Controle" && $afzender == $thuis) {
				$flag = true;
				$bericht = imap_fetchbody($gmconnect,$email_nummer,1);
				break;
			}
		}//foreach
	}//if

	return $flag;
}//zoekControle

//stuurt alle mails in tabel Mails opnieuw, en haalt ze uit de tabel
//(aangeroepen als controle niet gevonden is)
function herhaalMails() {
	$resultaat = sqlSel(1,"");
	while($mail = sqlFet($resultaat)) {
		mail($mail['ADRES'],$mail['ONDERWERP'],
			$mail['BERICHT'],$mail['HEADERS']);
	}
	return;
}//herhaalMails

function verwijderMails() {
	global $tabellen;
	$tabel = $tabellen[1];
	$sql = "DELETE FROM $tabel";
	sqlQuery($sql);
	return;
}//verwijderMails

//maakt verbinding met gmail
function gmailConnect () {
	global $thuis;
	$wachtwoord = mailPass();

	$map = "INBOX";
	$imapadres = "{imap.gmail.com:993/imap/ssl}";
	$hostnaam = $imapadres . $map;
	$connection = imap_open($hostnaam,$thuis,$wachtwoord) or 
		stuurError("Kan niet verbinden met Gmail:<br /><br /> " . 
		imap_last_error());
	return($connection);
}//gmailConnect

//sluit verbinding met gmail
function gmailSluit() {
	global $gmconnect;

	imap_close($gmconnect) or 
		stuurError("Kan verbinding met Gmail niet sluiten:<br /><br />" . 
		imap_last_error());
	return;
}//gmailSluit

?>
