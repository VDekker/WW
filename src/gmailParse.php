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
			if($afzender == $thuis) {
				continue;
			}
			$bericht = htmlentities($bericht1);
			
			echo "Mail van: '$afzender'\n";

			if(preg_match("/\bhelp\b/i",$onderwerp)) {
				help($afzender,$onderwerp,$bericht);
			}
			else if(preg_match("/config/i",$onderwerp)) {
				echo "Config mail gevonden!\n";
				if(!in_array($afzender,$admins)) {
					echo "Afzender is geen admin; doe niets.\n";
					continue;
				}
				config($afzender,$onderwerp,$bericht);
			}
			else {
				$resultaat = sqlSel("Spellen","");
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
						$init = $spel['INIT'];
						$fase = $spel['FASE'];
						$tweede = (($spel['FLAGS'] & 1) == 1);
						$max = $spel['MAX_SPELERS'];
						$id = spelerID($afzender,$sid);
						if(!empty($naam) || ($init && $fase == 1)) {
							parseStem($id,$afzender,$sid,
								$bericht,$onderwerp,$init,$fase,$tweede,$max);
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
	$resultaat = sqlSel("Mails","");
	while($mail = sqlFet($resultaat)) {
		mail($mail['ADRES'],$mail['ONDERWERP'],
			$mail['BERICHT'],$mail['HEADERS']);
	}
	return;
}//herhaalMails

function verwijderMails() {
	$sql = "DELETE FROM Mails";
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
		stuurError("Kan niet verbinden met Gmail:\n\n " . 
		imap_last_error());
	return($connection);
}//gmailConnect

//sluit verbinding met gmail
function gmailSluit() {
	global $gmconnect;

	imap_close($gmconnect) or 
		stuurError("Kan verbinding met Gmail niet sluiten:\n\n" . 
		imap_last_error());
	return;
}//gmailSluit

?>
