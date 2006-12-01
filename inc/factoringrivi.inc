<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if ((mysql_field_name($result, $i) == "teksti_fi") or
		(mysql_field_name($result, $i) == "teksti_en") or
		(mysql_field_name($result, $i) == "teksti_se")) {
		$ulos = "<td><textarea cols='85' rows='8' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "factoringyhtio") {
		
		if (strtoupper($trow[$i]) == 'NORDEA') {
			$sel2 = "SELECTED";
		}
		if (strtoupper($trow[$i]) == 'SAMPO') {
			$sel3 = "SELECTED";
		}
		if (strtoupper($trow[$i]) == 'SEB') {
			$sel4 = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='NORDEA' $sel2> ".t("Nordea Factoring")."</option>";
		$ulos .= "<option value='SAMPO' $sel3> ".t("Sampo Factoring")."</option>";
		$ulos .= "<option value='SEB' $sel4> ".t("SEB Factoring")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valkoodi") { //valuutta
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
	
	if (mysql_field_name($result, $i) == "pankki_tili") { //valuutta
		$query = "	SELECT nimi, tilino
	                FROM yriti
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tilino']) {
					$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tilino]' $sel>$vrow[nimi] $vrow[tilino]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
?>
