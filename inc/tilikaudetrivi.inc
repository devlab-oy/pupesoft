<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "tilikausi_alku" or 
		mysql_field_name($result, $i) == "tilikausi_loppu") {
		$tyyppi = 1.5;
	}
	
	
?>
