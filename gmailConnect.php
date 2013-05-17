<?php

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

function gmailSluit() {
	global $gmconnect;

	imap_close($gmconnect) or 
		stuurError("Kan verbinding met Gmail niet sluiten:\n\n" . 
		imap_last_error());
	return;
}//gmailSluit

?>
