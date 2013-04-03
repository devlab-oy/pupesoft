<?php

if (!function_exists("huoltosykli")) {

	function huoltosykli(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		//tyyppi pitää löytyä tuotteen_avainsanat taulusta
		if (mysql_field_type($result, $i) == "tyyppi") {
			$query = "	SELECT *
						FROM tuotteen_avainsanat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND selite = '{$t[$i]}'";
			$tyyppi_result = pupe_query($query);

			if (mysql_num_rows($tyyppi_result) > 0) {
				$virhe[$i] = t("Tuoteen tyyppi ei ole perustettu");
			}
		}

//koko on numero??
		if (mysql_field_type($result, $i) == "koko") {
			if (!is_numeric($t[$i])) {
				$virhe[$i] = t("Koko pitää olla numero");
			}
		}

//olosuhde pitää löytyä avainsanat taulusta OLOSUHDE
		if (mysql_field_type($result, $i) == "olosuhde") {
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'OLOSUHDE'
						AND selite = '{$t[$i]}'";
			$tyyppi_result = pupe_query($query);

			if (mysql_num_rows($tyyppi_result) > 0) {
				$virhe[$i] = t("Olosuhdetta ei ole perustettu");
			}
		}

//toimenpide????
		if (mysql_field_type($result, $i) == "toimenpide") {

		}

//huoltoväli float aluksi annetaan päivissä, myöhemmin voi olla mittarilukema, käyttökerta
		if (mysql_field_type($result, $i) == "huoltovali") {
			if (!is_numeric($t[$i])) {
				$virhe[$i] = t("Huoltoväli annettava päivinä");
				//$virhe[$i] = t("Huoltoväli annettava päivinä, mittarilukemana tai käyttökertana");
			}
		}

//pakollisuus kyllä / ei
		if (mysql_field_type($result, $i) == "tyyppi") {
			if ($t[$i] != 0 or $t[$i] != 1) {
				$virhe[$i] = t("Virheellinen pakollisuus syötetty");
			}
		}
	}
}