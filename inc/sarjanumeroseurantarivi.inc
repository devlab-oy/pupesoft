<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
			$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "ostorivitunnus" or
		mysql_field_name($result, $i) == "myyntirivitunnus") {
			$tyyppi = 2;
	}
?>
