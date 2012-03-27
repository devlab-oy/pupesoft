<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "neutraali") {
		$chk = (isset($trow[$i]) and $trow[$i] != '') ? " checked" : "";

		$ulos .= "<td><input type='checkbox' name='{$nimi}' {$chk} /></td>";

		$jatko = 0;
	}