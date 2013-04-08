<?php

if (!function_exists("huoltosykli")) {

	function huoltosyklitarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		//tämän avulla tarkistetaan, että kantaan ei mene kahta huoltosykliä samoilla atribuuteilla
		//merkitseviä atribuutteja on laitteen koko, paikan olosuhde ja laitteen tyyppi
		if (mysql_field_name($result, $i) == "tunnus") {
			$query = "	SELECT koko,
						olosuhde,
						tyyppi
						FROM huoltosykli
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND koko = '{$t['koko']}'
						AND olosuhde = '{$t['olosuhde']}'
						AND tyyppi = '{$t['tyyppi']}'";
			$vanha_huoltosykli_result = pupe_query($query);

			if (mysql_num_rows($vanha_huoltosykli_result) > 0) {
				//tämä on sitä varten, että virhe tilanteessa ei pystytä luomaan uutta
				$virhe[$i] .= t("Huoltosykli on jo perustettu");

				//tämä on sitä varten, että virheilmoitus tulee näkyviin
				$virhe[1] .= t("Huoltosykli on jo perustettu");
			}
		}

		//tyyppi pitää löytyä tuotteen_avainsanat taulusta
		if (mysql_field_name($result, $i) == "tyyppi") {
			if (empty($t[$i])) {
				$virhe[$i] = t("Tyyppi ei voi olla tyhjä");
			}
			else {
				$query = "	SELECT *
							FROM tuotteen_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND selite = '{$t[$i]}'";
				$tyyppi_result = pupe_query($query);

				if (mysql_num_rows($tyyppi_result) < 0) {
					$virhe[$i] = t("Tuoteen tyyppi ei ole perustettu");
				}
			}
		}

		if (mysql_field_name($result, $i) == "koko") {
			if (empty($t[$i])) {
				$virhe[$i] = t("Koko ei voi olla tyhjä");
			}
		}

//olosuhde pitää löytyä avainsanat taulusta OLOSUHDE
		if (mysql_field_name($result, $i) == "olosuhde") {
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'OLOSUHDE'
						AND selite = '{$t[$i]}'";
			$tyyppi_result = pupe_query($query);

			if (mysql_num_rows($tyyppi_result) < 0) {
				$virhe[$i] = t("Olosuhdetta ei ole perustettu");
			}
		}

		if (mysql_field_name($result, $i) == "toimenpide") {
			$query = "	SELECT tunnus
						FROM tuote
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$t[$i]}'";
			$toimenpide_result = pupe_query($query);

			if (mysql_num_rows($toimenpide_result) < 0) {
				$virhe[$i] = t("Virheellinen toimenpide").". ".t("Toimenpide pitää olla tuote");
			}
		}

//huoltoväli float aluksi annetaan päivissä, myöhemmin voi olla mittarilukema, käyttökerta
		if (mysql_field_name($result, $i) == "huoltovali") {
			if (!is_numeric($t[$i])) {
				$virhe[$i] = t("Huoltoväli annettava päivinä");
				//$virhe[$i] = t("Huoltoväli annettava päivinä, mittarilukemana tai käyttökertana");
			}

			if (empty($t[$i])) {
				$virhe[$i] = t("Huoltoväli ei voi olla tyhjä!");
			}
		}

		if (mysql_field_name($result, $i) == "pakollisuus") {
			if ((int)$t[$i] != 1 and (int)$t[$i] != 0) {
				$virhe[$i] = t("Virheellinen pakollisuus syötetty");
			}
		}
	}
}