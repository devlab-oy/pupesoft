<?php

$ulos	 = "";
$jatko	 = 1; // oletetaan normaali käsittely
$tyyppi	 = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "tunnus" or
		mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_name($result, $i) == "toimipaikka_tunnus") {
	$toimipaikat = hae_toimipaikat();

	$ulos = "<td>";
	$ulos .= "<select name='$nimi'>";

	$sel = '';
	foreach ($toimipaikat as $toimipaikka) {
		if (isset($trow[$i]) and $trow[$i] == $toimipaikka['tunnus']) {
			$sel = 'SELECTED';
		}
		$ulos .= "<option value='{$toimipaikka['tunnus']}' {$sel}>{$toimipaikka['nimi']}</option>";
		$sel = '';
	}

	$ulos .= "</select>";
	$ulos .= "</td>";
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "toimitustapa_tunnus") {
	$toimitustavat = hae_toimitustavat();

	$ulos = "<td>";
	$ulos .= "<select name='$nimi'>";

	$sel = '';
	foreach ($toimitustavat as $toimitustapa) {
		if (isset($trow[$i]) and $trow[$i] == $toimitustapa['tunnus'] or $lukitse_avaimeen == $toimitustapa['tunnus']) {
			$sel = 'SELECTED';
		}
		$ulos .= "<option value='{$toimitustapa['tunnus']}' {$sel}>{$toimitustapa['selite']}</option>";
		$sel = '';
	}

	$ulos .= "</select>";
	$ulos .= "</td>";
	$jatko = 0;
}