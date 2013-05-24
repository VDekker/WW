<?php

//alle php mysql functies bij elkaar, zodat deze eenvoudig en op één plek omgezet kunnen worden
//naar mysqli, of zodat er eenvoudig andere dingen mee kunnen gebeuren.

function dbConnect() {
	$server='mysql.liacs.nl';
	$username='vdekker';
	$password='p1nquin';
	$database='vdekker';
	return mysqli_connect($server,$username,$password,'vdekker'); 
}//dbConnect

function dbSluit() {
	global $dbconnect;
	mysqli_close($dbconnect) or
		stuurError("Kan verbinding met database niet sluiten:\n\n" . 
		mysqli_error($dbconnect));
}

function sqlQuery($sql) {
	global $dbconnect;
	$resultaat = mysqli_query($dbconnect,$sql) or 
		stuurError("Kon query niet uitvoeren.\nQuery: $sql\n\n" . 
		mysqli_error($dbconnect));
	return $resultaat;
}//sqlQuery

function sqlUp($tabel,$waardes,$eisen) {
	global $dbconnect;
	$sql = "UPDATE $tabel SET $waardes";
	if(!empty($eisen)) {
		$sql .= " WHERE $eisen";
	}
	mysqli_query($dbconnect,$sql) or 
		stuurError("Kon query niet uitvoeren.\nQuery: $sql\n\n" . 
		mysqli_error($dbconnect));
	return;
}//sqlUp

function sqlSel($tabel,$eisen) {
	global $dbconnect;
	$sql = "SELECT * FROM $tabel";
	if(!empty($eisen)) {
		$sql .= " WHERE $eisen";
	}
	$resultaat = mysqli_query($dbconnect,$sql) or 
		stuurError("Kon query niet uitvoeren.\nQuery: $sql\n\n" . 
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

?>
