#!/usr/bin/php
<?php

	if (php_sapi_name() != 'cli') {
		die("1, Voidaan ajaa vain komentoriviltä\r\n\r\n");
	}

	require('inc/connect.inc');
	require('inc/functions.inc');

	$timeparts		= explode(" ",microtime());
	$starttime		= $timeparts[1].substr($timeparts[0],1);

	echo "\nSTART: $hname :$dbkanta\n";

	//$dbkanta --> tulee salasanat.php:stä
	$query  = "SHOW TABLES FROM $dbkanta";
	$tabresult = pupe_query($query);

	while ($tables = mysql_fetch_row($tabresult)) {
		$query  = "describe $tables[0]";
		$fieldresult = pupe_query($query);

		$sql = "ALTER TABLE $tables[0]\n";

		while ($fields = mysql_fetch_assoc($fieldresult)) {

		    $type = $fields["Type"];
		    $column_name = $fields["Field"];

			$default = "";
		    $default = stripos($type, "char")    !== FALSE ? "not null default ''" : $default;
		    $default = stripos($type, "decimal") !== FALSE ? "not null default 0.0" : $default;
		    $default = stripos($type, "float")   !== FALSE ? "not null default 0.0" : $default;
		    $default = stripos($type, "int")     !== FALSE ? "not null default 0" : $default;
		    $default = stripos($type, "date")    !== FALSE ? "not null default 0" : $default;
		    $default = stripos($type, "time")    !== FALSE ? "not null default 0" : $default;
		    $default = stripos($type, "text")    !== FALSE ? "null default null" : $default;
		    $default = stripos($type, "blob")    !== FALSE ? "null default null" : $default;

			$default = $fields["Key"] == "PRI" ? "auto_increment" : $default;

			if ($default != "") $sql .= "MODIFY COLUMN $column_name $type $default,\n";
		}

		$sql = substr($sql, 0, -2).";\n";

		#echo "$sql\n";

		$result = pupe_query($sql);
	}

	$timeparts = explode(" ",microtime());
	$endtime   = $timeparts[1].substr($timeparts[0],1);
	$aika      = round($endtime-$starttime,4);

	$hname = gethostname();

	echo "FINISH: $hname :$dbkanta: $aika\n";
