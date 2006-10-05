<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	
	if (mysql_field_name($result, $i) == "saldo" or
	    mysql_field_name($result, $i) == "saldoaika" or
	    mysql_field_name($result, $i) == "inventointiaika" or
	    mysql_field_name($result, $i) == "inventointipoikkeama" or
	    mysql_field_name($result, $i) == "oletus" or
	    mysql_field_name($result, $i) == "inventointilista" or
	    mysql_field_name($result, $i) == "tuoteno" or
	    mysql_field_name($result, $i) == "inventointilista_aika") {
		$tyyppi = 2;
	}
	
	
	
	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}
		
	if (mysql_field_name($result, $i) == "tuoteno" and $uusi != 0) {
		$tyyppi = 1;
	}
		
?>
