<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_name($result, $i) == "toimi_tunnus") {
	$toimittajat = hae_toimittajat(array('','K'));

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($toimittajat as $toimittaja) {
		$sel = "";
		if ($lukitse_avaimeen == $toimittaja['tunnus'] or $trow[$i] == $toimittaja['tunnus']) {
			$sel = "SELECTED";
		}
		$ulos .= "<option value='{$toimittaja['tunnus']}' {$sel}>{$toimittaja['nimi']}</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

if (mysql_field_name($result, $i) == "kohde_sarake") {
	$kohde_sarakkeet = array(
		'nimi' => t('Nimi'),
		'ytunnus' => t('Ytunnus'),
		'ovt_tunnus' => t('OVT-tunnus'),
	);

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($kohde_sarakkeet as $kohde_sarake_index => $kohde_sarake) {
		$sel = "";
		if ($kohde_sarake_index == $trow[$i]) {
			$sel = "SELECTED";
		}
		$ulos .= "<option value='{$kohde_sarake_index}' $sel>{$kohde_sarake}</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

if (mysql_field_name($result, $i) == "arvo") {

}
