<?php

require_once('validation/Validation.php');
if (!function_exists("laitetarkista")) {

	function laitetarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		//laitetta ei saa perustaa jos sen sammutin tuotteelle (tuote-taulu) ei ole linkattu koko ja tyyppiä, koska tällöin työmääräysten generointi ei toimi
		if (mysql_field_name($result, $i) == "tunnus" and !empty($t['tuoteno'])) {
			$query = "	SELECT DISTINCT laji
						FROM tuotteen_avainsanat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$t['tuoteno']}'";
			$tuotteen_atribuutit_result = pupe_query($query);

			if (mysql_num_rows($tuotteen_atribuutit_result) < 1) {
				$virhe[$i] = t("Tuotteen avainsanat on perustamatta").". ".t("Tarvittuja avainsanoja on koko ja tyyppi");

				$virhe[1] .= t("Tuotteen avainsanat on perustamatta").". ".t("Tarvittuja avainsanoja on koko ja tyyppi");
			}
		}

		if (mysql_field_name($result, $i) == "paikka") {
			$tunnus = trim($t['paikka']);

			if (!empty($tunnus)) {
				$query = "	SELECT *
							FROM paikka
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$tunnus}'";
				$kohde_result = pupe_query($query);

				if (mysql_num_rows($kohde_result) != 1) {
					$virhe[$i] = t("Paikka puuttuu tai sitä ei löydy").'!';
				}
			}
		}

		if (mysql_field_name($result, $i) == "tuoteno") {
			$tuoteno = trim($t['tuoteno']);

			if (!empty($tuoteno)) {
				$query = "	SELECT *
							FROM tuote
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tuoteno = '{$tuoteno}'";
				$tuote_result = pupe_query($query);

				if (mysql_num_rows($tuote_result) != 1) {
					$virhe[$i] = t("Tuote puuttuu tai sitä ei löydy").'!';
				}
			}
			else {
				$virhe[$i] = t("Tuote annettava").'!';
			}
		}

		if (mysql_field_name($result, $i) == "valm_pvm") {
			$valm_pvm = trim($t['valm_pvm']);

			if (empty($valm_pvm)) {
				$virhe[$i] = t("Valmistuspäivä on pakollinen").'!';
			}
			else {
				$validator = new FormValidator();
				if (!$validator->validateItem($valm_pvm, 'paiva')) {
					$virhe[$i] = t("Virheellinen päivämäärä").'!';
				}
			}
		}

		if (mysql_field_name($result, $i) == "sarjanro") {
			$sarjanro = trim($t['sarjanro']);

			if (empty($sarjanro)) {
				$virhe[$i] = t("Sarjanumero on pakollinen").'!';
			}
		}

		if (mysql_field_name($result, $i) == "tila") {
			$tila = trim($t['tila']);

			if (empty($tila)) {
				$virhe[$i] = t("Tila on pakollinen").'!';
			}
			else {
				$query = "	SELECT selite
							FROM avainsana
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = 'LAITE_TILA'
							AND selite = '{$tila}'";
				$tilat_result = pupe_query($query);

				if (mysql_num_rows($tilat_result) != 1) {
					$virhe[$i] = t("Laitteen tilaa ei löydy avainsanoista").'!';
				}
			}
		}
	}

}