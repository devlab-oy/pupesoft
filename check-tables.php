#!/usr/bin/php
<?php

if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

require ("inc/connect.inc");

$query  = "show tables from $dbkanta";
$result =  mysql_query($query);

echo "\nChecking tables from $dbkanta:\n\n";

while ($row = mysql_fetch_array($result)) {

	$table = $row[0];

	// check table for errors
	$query = "check    table $table";
	echo sprintf("%-37s",  $query);
	$chkre = mysql_query($query);
	$chkro = mysql_fetch_array($chkre);
	echo " -> $chkro[Msg_text]\n";

	// optimize table
	$query = "optimize table $table";
	echo sprintf("%-37s",  $query);
	$chkre = mysql_query($query);
	$chkro = mysql_fetch_array($chkre);
	echo " -> $chkro[Msg_text]\n";

}

echo "\nDone.\n";

?>