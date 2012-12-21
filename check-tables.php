#!/usr/bin/php
<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

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

		// varmistetaan vielä indexien käytössäolo
		$query = "show index from $table";
		$chkre = mysql_query($query);

		while ($chkro = mysql_fetch_array($chkre)) {
			if (stripos($chkro["Comment"], "disabled") !== FALSE) {
				$query = "alter table $table enable keys";
				$chkre = mysql_query($query);
				echo "$query\n";
				break;
			}
		}
	}

	echo date("d.m.Y @ G:i:s").": Check tables. Done.\n\n";

?>