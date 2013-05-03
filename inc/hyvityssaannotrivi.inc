<?php
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tuote_kentta") {

		$tuotteen_kentat = array(
							'aleryhma',
							'alv',
							'eankoodi',
							'ei_saldoa',
							'halytysraja',
							'hinnastoon',
							'kehahin',
							'keraysvyohyke',
							'kuluprosentti',
							'malli',
							'mallitarkenne',
							'myyjanro',
							'myymalahinta',
							'myyntihinta',
							'myyntihinta_maara',
							'myynti_era',
							'nakyvyys',
							'nettohinta',
							'nimitys',
							'osasto',
							'ostajanro',
							'sarjanumeroseuranta',
							'status',
							'suoratoimitus',
							'tahtituote',
							'try',
							'tuotemerkki',
							'tuoteno',
							'tuotepaallikko',
							'tuotetyyppi',
							'varmuus_varasto',
							'vihahin',
							'yksikko',
							'yksin_kerailyalustalle');

		$sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys($tuotteen_kentat, '');

		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value=''>".t("Ei Valintaa")."</option>";

		foreach ($tuotteen_kentat as $kentta) {
			$ulos .= "<option value='{$kentta}'{$sel[$kentta]}>".t("{$kentta}")."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas_kentta") {

		$asiakkaan_kentat = array(
							'osasto',
							'ryhma',
							'ytunnus',
							'asiakasnro');

		$sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys($asiakkaan_kentat, '');

		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value=''>".t("Ei Valintaa")."</option>";

		foreach ($asiakkaan_kentat as $kentta) {
			$ulos .= "<option value='{$kentta}'{$sel[$kentta]}>".t("{$kentta}")."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "palautuskielto") {

		$sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys(array('E', 'K'), '');

		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 200).">";
		$ulos .= "<option value=''>".t("Ei Valintaa")."</option>";
		$ulos .= "<option value='X'{$sel['X']}>".t("Palautuskielto")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
