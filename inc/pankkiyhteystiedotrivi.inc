<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "viite") {

		$sela = $selb = '';
		if ($trow[$i] != '') {
			$selb = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}
		
		
		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' $sela>".t("Suomi")."</option>";
		$ulos .= "<option value = 'SE' $selb>".t("Ruotsi")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	

?>
