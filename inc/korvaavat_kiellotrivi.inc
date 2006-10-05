<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "try") {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='TRY'";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
		}

		$sel = '';
		if ($trow[$i] == "*") {
			$sel = "selected";
		}
		$ulos .= "<option value='*' $sel>".t("Kaikki tuoteryhmät")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osasto") {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
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
