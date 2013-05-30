<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	// yhtiötä ei näytetä
	if ($i == 0) {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tyyppi") {

		$sel = strtolower($trow[$i]) == 'cv' ? "selected" : "";

		$ulos  = "<td><select name='{$nimi}'>";
		$ulos .= "<option value='pc'>".t("PC")."</option>";
		$ulos .= "<option value='cv' {$sel}>".t("CV")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "autoid_lukittu") {

		$chk = $trow[$i] ? "checked" : "";

		$ulos  = "<td>";
		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";
		$ulos .= "<input type='checkbox' name='{$nimi}[]' {$chk} />";
		$ulos .= "</td>";
		$jatko = 0;
	}
