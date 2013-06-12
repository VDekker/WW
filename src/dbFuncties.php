<?php

//alle php mysql functies bij elkaar, zodat deze eenvoudig en op één plek omgezet kunnen worden
//naar mysqli, of zodat er eenvoudig andere dingen mee kunnen gebeuren.

function dbConnect() {
	$dbparam = dbParameters();
	$server = $dbparam['server'];
	$username = $dbparam['username'];
	$password = $dbparam['password'];
	$database = $dbparam['database'];
	return mysqli_connect($server,$username,$password,$database); 
}//dbConnect

function dbSluit() {
	global $dbconnect;
	mysqli_close($dbconnect) or
		stuurError("Kan verbinding met database niet sluiten:<br /><br />" . 
		mysqli_error($dbconnect));
}

function sqlQuery($sql) {
	global $dbconnect;
	$resultaat = mysqli_query($dbconnect,$sql) or 
		stuurError("Kon query niet uitvoeren.<br />Query: $sql<br /><br />" . 
		mysqli_error($dbconnect));
	return $resultaat;
}//sqlQuery

function sqlUp($i,$waardes,$eisen) {
	global $dbconnect,$tabellen;
	$tabel = $tabellen[$i];
	$sql = "UPDATE $tabel SET $waardes";
	if(!empty($eisen)) {
		$sql .= " WHERE $eisen";
	}
	mysqli_query($dbconnect,$sql) or 
		stuurError("Kon query niet uitvoeren.<br />Query: $sql<br /><br />" . 
		mysqli_error($dbconnect));
	return;
}//sqlUp

function sqlSel($i,$eisen) {
	global $dbconnect,$tabellen;
	$tabel = $tabellen[$i];
	$sql = "SELECT * FROM $tabel";
	if(!empty($eisen)) {
		$sql .= " WHERE $eisen";
	}
	$resultaat = mysqli_query($dbconnect,$sql) or 
		stuurError("Kon query niet uitvoeren.<br />Query: $sql<br /><br />" . 
		mysqli_error($dbconnect));
	return $resultaat;
}//sqlSel

function sqlFet($resultaat) {
	return mysqli_fetch_array($resultaat);
}//sqlFet

function sqlNum($resultaat) {
	return mysqli_num_rows($resultaat);
}//sqlNum

function sqlData($resultaat,$plek) {
	mysqli_data_seek($resultaat,$plek);
	return;
}//sqlData

function sqlEscape($text) {
	global $dbconnect;
	return mysqli_real_escape_string($dbconnect,$text);
}//sqlEscape

function sqlID() {
	global $dbconnect;
	return mysqli_insert_id($dbconnect);
}//sqlID

?>
