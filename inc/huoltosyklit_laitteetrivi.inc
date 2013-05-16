<?php

require_once('huoltosykli_functions.inc');

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_type($result, $i) == "blob") {
	$ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "huoltosykli_tunnus") {

	//jos lukitse avaimeen, niin ollaan näyttämässä huoltosyklit_laitteita iframessa laite näkymässä
	//tällöin haetaan laitteen tiedot sekä paikka ja dropdownissa näytetään vain kyseisen laitteen huoltosyklit
	if (!empty($lukitse_avaimeen)) {
		$laite = hae_laite($lukitse_avaimeen);
		$laite['paikka'] = hae_paikka($laite['paikka']);
	}
	$query = "	SELECT huoltosykli.tunnus, concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko,
			(
				SELECT selitetark
				FROM avainsana
				WHERE avainsana.yhtio = huoltosykli.yhtio
				AND avainsana.selite = huoltosykli.olosuhde
				AND avainsana.laji = 'OLOSUHDE')
			) as dropdown_text
				FROM huoltosykli
				JOIN tuote
				ON ( tuote.yhtio = huoltosykli.yhtio
					AND tuote.tuoteno = huoltosykli.toimenpide )
				WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
				AND huoltosykli.tyyppi = '{$laite['laite_tyyppi']}'
				AND huoltosykli.koko = '{$laite['laite_koko']}'
				AND huoltosykli.olosuhde = '{$laite['paikka']['olosuhde']}'";
	$huoltosykli_result = pupe_query($query);
	$huoltosyklit = array();
	while ($huoltosykli = mysql_fetch_assoc($huoltosykli_result)) {
		if ($trow[$i] == $huoltosykli['tunnus']) {
			$huoltosykli['selected'] = "SELECTED";
		}
		else {
			$huoltosykli['selected'] = "";
		}
		$huoltosyklit[] = $huoltosykli;
	}

	$ulos = "<td>";
	$ulos .= "<select class='{$otsikko}' name='{$nimi}'>";
	foreach ($huoltosyklit as $huoltosykli) {
		$ulos .= "<option value='{$huoltosykli['tunnus']}' {$huoltosykli['selected']}>".t($huoltosykli['dropdown_text'])."</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

if (mysql_field_name($result, $i) == "laite_tunnus") {
//
//	$query = "	SELECT laite.*,
//				tuote.nimitys
//				FROM laite
//				JOIN tuote
//				ON ( tuote.yhtio = laite.yhtio
//					AND tuote.tuoteno = laite.tuoteno )
//				WHERE laite.yhtio = '{$kukarow['yhtio']}'";
//	$laite_result = pupe_query($query);
//	$laitteet = array();
//	while ($laite = mysql_fetch_assoc($laite_result)) {
//		if ($trow[$i] == $laite['tunnus']) {
//			$laite['selected'] = "SELECTED";
//		}
//		else if ($lukitse_avaimeen == $laite['tunnus']) {
//			$laite['selected'] = "SELECTED";
//		}
//		else {
//			$laite['selected'] = "";
//		}
//		$laitteet[] = $laite;
//	}
//
//	$ulos = "<td>";
//	$ulos .= "<select class='{$otsikko}' name='{$nimi}'>";
//	foreach ($laitteet as $laite) {
//		$ulos .= "<option value='{$laite['tunnus']}' {$laite['selected']}>{$laite['nimitys']} / {$laite['sarjanro']}</option>";
//	}
//	$ulos .= "</select>";
//	$ulos .= "</td>";
//
//$jatko = 0;
	// Jos tämä on liitetty tunnuksella:
	if ($trow[$i] > 0) {
		$ulos = "<input type='hidden' name='$nimi' value='$trow[$i]'>";
	}
	// Jos tää on uusi itemi
	elseif ((int)$tunnus == 0) {
		$ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
	}

	$tyyppi = 4;
	$jatko = 0;
}

//huoltoväli float aluksi annetaan päivissä, myöhemmin voi olla mittarilukema, käyttökerta
if (mysql_field_name($result, $i) == "huoltovali") {
	
}

//pakollisuus kyllä / ei
if (mysql_field_name($result, $i) == "pakollisuus") {
	$ulos = "<td>";
	$ulos .= "<select class='{$otsikko}' name='{$nimi}'>";
	$ulos .= "<option value=1>".t("Kyllä")."</option>";
	$ulos .= "<option value=0>".t("Ei")."</option>";
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

