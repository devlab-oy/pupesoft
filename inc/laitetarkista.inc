<?php

require_once('validation/Validation.php');
if (!function_exists("laitetarkista")) {

	function laitetarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		//laitetta ei saa perustaa jos sen sammutin tuotteelle (tuote-taulu) ei ole linkattu koko ja tyyppi‰, koska t‰llˆin tyˆm‰‰r‰ysten generointi ei toimi
		if (mysql_field_name($result, $i) == "tunnus") {
			$query = "	SELECT DISTINCT laji
						FROM tuotteen_avainsanat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$t['tuoteno']}'";
			$tuotteen_atribuutit_result = pupe_query($query);

			if (mysql_num_rows($tuotteen_atribuutit_result) != 2) {
				$virhe[$i] = t("Tuotteen avainsanat on perustamatta").". ".t("Tarvittuja avainsanoja on koko ja tyyppi");

				$virhe[1] .= t("Tuotteen avainsanat on perustamatta").". ".t("Tarvittuja avainsanoja on koko ja tyyppi");
			}
		}

		if (mysql_field_name($result, $i) == "paikka") {
			$tunnus = trim($t[$i]);

			if (!empty($tunnus)) {
				$query = "	SELECT *
							FROM paikka
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$tunnus}'";
				$kohde_result = pupe_query($query);

				if (mysql_num_rows($kohde_result) != 1) {
					$virhe[$i] = t("Paikka puuttuu tai sit‰ ei lˆydy").'!';
				}
			}
		}

		if (mysql_field_name($result, $i) == "tuoteno") {
			$tuoteno = trim($t[$i]);

			if (!empty($tuoteno)) {
				$query = "	SELECT *
							FROM tuote
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tuoteno = '{$tuoteno}'";
				$tuote_result = pupe_query($query);

				if (mysql_num_rows($tuote_result) != 1) {
					$virhe[$i] = t("Tuote puuttuu tai sit‰ ei lˆydy").'!';
				}
			}
		}

		if (mysql_field_name($result, $i) == "valm_pvm") {
			$valm_pvm = trim($t[$i]);

			if (empty($valm_pvm)) {
				$virhe[$i] = t("Valmistusp‰iv‰ on pakollinen").'!';
			}
			else {
				$validator = new FormValidator();
				if (!$validator->validateItem($valm_pvm, 'paiva')) {
					$virhe[$i] = t("Virheellinen p‰iv‰m‰‰r‰").'!';
				}
			}
		}

		if (mysql_field_name($result, $i) == "sarjanro") {
			$sarjanro = trim($t[$i]);

			if (empty($sarjanro)) {
				$virhe[$i] = t("Sarjanumero on pakollinen").'!';
			}
			else {
				$validator = new FormValidator();
				if (!$validator->validateItem($sarjanro, 'numero')) {
					$virhe[$i] = t("Sarjanumero pit‰‰ olla numero").'!';
				}
			}
		}
	}
}