<?php

if (!function_exists("vaihtoehtoiset_verkkolaskutunnuksettarkista")) {

	function vaihtoehtoiset_verkkolaskutunnuksettarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		if (mysql_field_name($result, $i) == "toimi_tunnus") {
			$toimittaja = hae_toimittaja($t[$i]);

			if (empty($toimittaja)) {
				$virhe[$i] = t("Toimittaja puuttuu tai sitä ei löydy");
			}
		}

		if (mysql_field_name($result, $i) == "kohde_sarake") {
			$kohde_sarakkeet = array(
				'nimi',
				'ytunnus',
				'ovt_tunnus',
			);

			if (!in_array($t[$i], $kohde_sarakkeet)) {
				$virhe[$i] = t("Kohde sarake ei ole sallittujen listalla").'(nimi, ytunnus, ovt_tunnus)';
			}
		}

		if (mysql_field_name($result, $i) == "arvo") {

			if (empty($t[$i])) {
				$virhe[$i] = t("Arvo ei voi olla tyhjä");
			}
			else {
				//tarkistetaan, että kyseistä arvoa ei ole jo kannassa
				$query = "	SELECT *
							FROM vaihtoehtoiset_verkkolaskutunnukset
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND arvo = '{$t[$i]}'";
				$arvo_result = pupe_query($query);

				if (mysql_num_rows($arvo_result) > 0) {
					$virhe[$i] = t("Arvo löytyy jo tietokannasta");
				}
			}
		}
	}
}