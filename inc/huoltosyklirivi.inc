<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "tunnus" or mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_type($result, $i) == "blob") {
	$ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
	$jatko = 0;
}

//tyyppi pitää löytyä tuotteen_avainsanat taulusta
if (mysql_field_name($result, $i) == "tyyppi") {
	$query = "	SELECT *
				FROM tuotteen_avainsanat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'sammutin_tyyppi'
				GROUP BY selite";
	$tyyppi_result = pupe_query($query);
	$tyypit = array();
	while ($tyyppi_row = mysql_fetch_assoc($tyyppi_result)) {
		if ($trow[$i] == $tyyppi_row['selite']) {
			$tyyppi_row['selected'] = "SELECTED";
		}
		else {
			$tyyppi_row['selected'] = "";
		}
		$tyypit[] = $tyyppi_row;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($tyypit as $tyyppi_row) {
		$ulos .= "<option value='{$tyyppi_row['selite']}' {$tyyppi_row['selected']}>".t($tyyppi_row['selite'])."</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

//koko on numero??
if (mysql_field_name($result, $i) == "koko") {

}

//olosuhde pitää löytyä avainsanat taulusta OLOSUHDE
if (mysql_field_name($result, $i) == "olosuhde") {
	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'OLOSUHDE'";
	$olosuhde_result = pupe_query($query);
	$olosuhteet = array();
	while ($olosuhde = mysql_fetch_assoc($olosuhde_result)) {
		if ($trow[$i] == $olosuhde['tunnus']) {
			$olosuhde['selected'] = "SELECTED";
		}
		else {
			$olosuhde['selected'] = "";
		}
		$olosuhteet[] = $olosuhde;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($olosuhteet as $olosuhde) {
		$ulos .= "<option value='{$olosuhde['selite']}' {$olosuhde['selected']}>".t($olosuhde['selitetark'])."</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

//toimenpide on palvelu tuote joka pitää löytyä tuote taulusta
if (mysql_field_name($result, $i) == "toimenpide") {
	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuotetyyppi = 'K'";
	$toimenpide_result = pupe_query($query);
	$toimenpiteet = array();
	while($toimenpide = mysql_fetch_assoc($toimenpide_result)) {
		if ($trow[$i] == $toimenpide['tuoteno']) {
			$toimenpide['selected'] = "SELECTED";
		}
		else {
			$toimenpide['selected'] = "";
		}
		
		$toimenpiteet[] = $toimenpide;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach($toimenpiteet as $toimenpide) {
		$ulos .= "<option value='{$toimenpide['tuoteno']}' {$toimenpide['selected']}>{$toimenpide['nimitys']}</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

//huoltoväli float aluksi annetaan päivissä, myöhemmin voi olla mittarilukema, käyttökerta
if (mysql_field_name($result, $i) == "huoltovali") {

}

//pakollisuus kyllä / ei
if (mysql_field_name($result, $i) == "pakollisuus") {
	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	$ulos .= "<option value=1>".t("Kyllä")."</option>";
	$ulos .= "<option value=0>".t("Ei")."</option>";
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}