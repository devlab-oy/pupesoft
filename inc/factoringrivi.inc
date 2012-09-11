<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if ((mysql_field_name($result, $i) == "teksti_fi") or
		(mysql_field_name($result, $i) == "teksti_en") or
		(mysql_field_name($result, $i) == "teksti_se") or
		(mysql_field_name($result, $i) == "teksti_ee")) {
		$ulos = "<td><textarea cols='100' rows='8' name='$nimi'>$trow[$i]</textarea></td>";
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
		if (strtoupper($trow[$i]) == 'OKO') {
			$sel5 = "SELECTED";
		}
		if (strtoupper($trow[$i]) == 'SVEA') {
			$sel6 = "SELECTED";
		}
		if (strtoupper($trow[$i]) == 'COLLECTOR') {
			$sel7 = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='NORDEA' $sel2> ".t("Nordea Factoring")."</option>";
		$ulos .= "<option value='SAMPO' $sel3> ".t("Sampo Factoring")."</option>";
		$ulos .= "<option value='SEB' $sel4> ".t("SEB Factoring")."</option>";
		$ulos .= "<option value='OKO' $sel5> ".t("OKO Factoring")."</option>";
		$ulos .= "<option value='SVEA' $sel6> ".t("SVEA Factoring")."</option>";
		$ulos .= "<option value='COLLECTOR' $sel7> ".t("COLLECTOR Factoring")."</option>";
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
	             	WHERE yhtio  = '$kukarow[yhtio]'
					and kaytossa = ''
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


	if (mysql_field_name($result, $i) == "viitetyyppi") {

		$sel = "";

		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''> ".t("Laskun viite muodostetaan sopimusnumerosta, välimerkistä ja laskunumerosta")."</option>";
		$ulos .= "<option value='X' $sel> ".t("Laskun viite muodostetaan laskunumerosta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

?>