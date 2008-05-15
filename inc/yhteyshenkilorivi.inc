<?php
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if($popparista or $ajax_menu_yp != "") {
		if (mysql_field_name($result, $i) == "liitostunnus") {
			if ($liitostunnus != ''){
				$trow[$i] = $liitostunnus;
			}
			$tyyppi = 4;
		}
	}
	else {
		if (mysql_field_name($result, $i) == "asiakas") {
			if ($liitostunnus != ''){
				$trow[$i] = $liitostunnus;
			}
			$tyyppi = 3;
		}		
	}

	if (mysql_field_name($result, $i) == "tyyppi") {
		if($trow[$i] == "") {
			if($yhtyyppi == "asiakas") {
				$trow[$i]="A";
			}
			elseif($yhtyyppi == "toimi") {
				$trow[$i]="T";
			}
			$tyyppi=4;
		}
		else {
			$tyyppi=0;
		}
	}
	
	if (mysql_field_name($result, $i) == "tilausyhteyshenkilo") {
		
		
		if ($trow[$i] == "o") {
			$sel = "SELECTED";
		}
		
		
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value='' >".t("Ei")."</option>";
		$ulos .= "<option value='K' $sel>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "oletusyhteyshenkilo") {
		
		
		if ($trow[$i] == "o") {
			$sel = "CHECKED";
		}
		
		
		$ulos = "<td><input type='checkbox' name='oletusyhteyshenkilo' $sel $state></td>";
		$jatko = 0;
	}
	
?>
