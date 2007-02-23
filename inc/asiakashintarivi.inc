<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys
	if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "ryhma") {
		$query = "	SELECT ryhma
					FROM perusalennus
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY ryhma+0";
		$sresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhm‰‰")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[0]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas_ryhma") {
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'ASIAKASRYHMA'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhm‰‰")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "muutospvm") {
		$tyyppi = 2;
	}

	if (mysql_field_name($result, $i) == "valkoodi") {
		$query = "	SELECT nimi, tunnus
	                FROM valuu
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['nimi']) {
					$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>
