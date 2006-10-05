<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0; 
	}
	if (mysql_field_name($result, $i) == "oletusalv") {
			require ('alvpopup.inc');
			if ($trow[$i]=='') $trow[$i]=alv_oletus();
			$ulos = "<td>".alv_popup($nimi,$trow[$i])."</td>";
			$jatko = 0;
	}
?>
