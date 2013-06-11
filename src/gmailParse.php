<?php

function gmailParse() {
	global $thuis,$gmconnect,$admins;

	$berichtstatus = "UNSEEN";
	$emails = imap_search($gmconnect,$berichtstatus);
	$totaal = imap_num_msg($gmconnect);
	echo "Totaal aantal emails: $totaal \n\n";

	if($emails) {
		// sorteer de emails (nieuwste eerst)
		sort($emails);
		foreach($emails as $email_nummer) {
			$header = imap_fetch_overview($gmconnect,$email_nummer,0);
			$bericht1 = imap_fetchbody($gmconnect,$email_nummer,1.1);
			if(empty($bericht1)) {
				$bericht1 = imap_fetchbody($gmconnect,$email_nummer,1);
			}
			$onderwerp = $header[0]->subject;
			$afzender = $header[0]->from;
			$matches = array();
			preg_match("'<(.*?)>'si",$afzender,$matches);
			if(!empty($matches)) {
				$afzender = $matches[1];
			}
			if($afzender == $thuis) {
				continue;
			}

			echo "$afzender\n";
			$bericht = htmlentities($bericht1);
			
			echo "Mail van: '$afzender'\n";

			if(preg_match("/config/i",$onderwerp)) {
				echo "Config mail gevonden!\n";
				if(!in_array($afzender,$admins)) {
					echo "Afzender is geen admin; doe niets.\n";
					continue;
				}
				config($afzender,$onderwerp,$bericht);
			}
			else if(preg_match("/\bhelp\b/i",$onderwerp)) {
				help($afzender,$onderwerp,$bericht);
			}
			else {
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
												//TODO deze twee aparte mails geven
						stuurFoutStop($adres,$snaam);
					}
					else if ($spel['STATUS'] == 1) {
						stuurFoutPauze($adres,$snaam);
					}
					else {
						$id = spelerID($afzender,$sid);
						if(!empty($naam) || ($init && $fase == 1)) {
							parseStem($id,$afzender,$spel,$bericht,$onderwerp);
						}
					}//else
				}//if
				else {
					echo "Verkeerd onderwerp (geen spelnaam herkend), ";
					echo "of verkeerd email-adres (geen afzender herkend).\n";
					// je komt hier als:
					// - email adres niet bekend in het spel
					// - of de invoer was gewoon fucked (onderwerp verkeerd)
					// - of de speler is dood en heeft geen reden 
					//      om naar het systeem te sturen (toch?)
					// mail dit naar de afzender.
					stuurFoutAdres($afzender);
				}
			}//else
			echo "\n";
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
	$wachtwoord = 'W@kkerd@m';

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
