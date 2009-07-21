<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "try") {
		// tehd‰‰n avainsana query
		$sresult = t_avainsana("TRY");

		$ulos = "<td><select name='$nimi'>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow["selite"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}

		$sel = '';
		if ($trow[$i] == "*") {
			$sel = "selected";
		}
		$ulos .= "<option value='*' $sel>".t("Kaikki tuoteryhm‰t")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osasto") {
		// tehd‰‰n avainsana query
		$sresult = t_avainsana("OSASTO");

		$ulos = "<td><select name='$nimi'>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow["selite"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}

		$sel = '';
		if ($trow[$i] == "*") {
			$sel = "selected";
		}
		$ulos .= "<option value='*' $sel>".t("Kaikki osastot")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		if ($trow[$i] == "O") $osel = "SELECTED";
		else $ksel = "SELECTED";

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value='K' $ksel>".t("Korvaavat kielto")."</option>";
		$ulos .= "<option value='O' $osel>".t("Osatoimitus kielto")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>
