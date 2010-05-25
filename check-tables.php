#!/usr/bin/php
<?php

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	require ("inc/connect.inc");

	$query  = "show tables from $dbkanta";
	$result =  mysql_query($query);

	echo date("d.m.Y @ G:i:s").": Check tables from $dbkanta.\n";

	while ($row = mysql_fetch_array($result)) {

		$table = $row[0];

		// check table for errors
		$query = "check table $table";
		$chkre = mysql_query($query);
		$chkro = mysql_fetch_array($chkre);

		if ($chkro["Msg_text"] != "OK") {
			echo "$query -> $chkro[Msg_text]\n";

			// repair table for errors
			$query = "repair table $table";
			$chkre = mysql_query($query);
			$chkro = mysql_fetch_array($chkre);
			echo "$query -> $chkro[Msg_text]\n";
		}

		// optimize table
		$query = "optimize table $table";
		$chkre = mysql_query($query);
		$chkro = mysql_fetch_array($chkre);

		if ($chkro["Msg_text"] != "OK" and $chkro["Msg_text"] != "Table is already up to date") {
			echo "$query -> $chkro[Msg_text]\n";
		}

	}

	echo date("d.m.Y @ G:i:s").": Check tables. Done.\n\n";

?>