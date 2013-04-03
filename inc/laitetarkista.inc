<?php

if (!function_exists("laitetarkista")) {

	function laitetarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		if (mysql_field_name($result, $i) == "paikka") {
			$tunnus = trim($t[$i]);

			if (!empty($tunnus)) {
				$query = "	SELECT *
							FROM paikka
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$tunnus}'";
				$kohde_result = pupe_query($query);

				if (mysql_num_rows($kohde_result) != 1) {
					$virhe[$i] = t("Paikka puuttuu tai sitä ei löydy!");
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
					$virhe[$i] = t("Tuote puuttuu tai sitä ei löydy!");
				}
			}
		}

		
	}
}