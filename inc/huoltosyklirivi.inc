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
if (mysql_field_type($result, $i) == "tyyppi") {
	$query = "	SELECT *
				FROM tuotteen_avainsanat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'sammutin_tyyppi'";
	$tyyppi_result = pupe_query($query);
	$tyypit = array();
	while ($tyyppi = mysql_fetch_assoc($tyyppi_result)) {
		$tyypit[] = $tyyppi;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($tyypit as $tyyppi) {
		$ulos .= "<option value='{$tyyppi['selite']}'>".t($tyyppi['selite'])."</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

//koko on numero??
if (mysql_field_type($result, $i) == "koko") {

}

//olosuhde pitää löytyä avainsanat taulusta OLOSUHDE
if (mysql_field_type($result, $i) == "olosuhde") {
	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'OLOSUHDE'";
	$olosuhde_result = pupe_query($query);
	$olosuhteet = array();
	while ($olosuhde = mysql_fetch_assoc($result)) {
		$olosuhteet[] = $olosuhde;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($olosuhteet as $olosuhde) {
		$ulos .= "<option value='{$olosuhde['selite']}'>".t($olosuhde['selite_tark'])."</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";
}

//toimenpide????
if (mysql_field_type($result, $i) == "toimenpide") {

}

//huoltoväli float aluksi annetaan päivissä, myöhemmin voi olla mittarilukema, käyttökerta
if (mysql_field_type($result, $i) == "huoltovali") {

}

//pakollisuus kyllä / ei
if (mysql_field_type($result, $i) == "tyyppi") {
	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	$ulos .= "<option value=1>".t("Kyllä")."</option>";
	$ulos .= "<option value=0>".t("Ei")."</option>";
	$ulos .= "</select>";
	$ulos .= "</td>";
}