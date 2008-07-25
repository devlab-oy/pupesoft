<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	// yhtiötä ei näytetä
	if ($i == 0) {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tyyppi") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = 'S' $sel[S]>".t("Sisäinen")."</option>";
		$ulos .= "<option value = 'U' $sel[U]>".t("Ulkoinen")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("ALV")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = 'O' $sel[O]>".t("Normaali")."</option>";
		$ulos .= "<option value = 'N' $sel[N]>".t("Tasoa ei nollata")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>