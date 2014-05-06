<?php

if (!function_exists("toimitustavat_toimipaikattarkista")) {
	function toimitustavat_toimipaikattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow;

		if ((mysql_field_name($result, $i) == "toimitustapa_tunnus")) {
			$toimitustavan_toimipaikat = hae_toimitustavan_toimipaikat($t['toimitustapa_tunnus']);

			$values				 = array(
				'toimipaikka_tunnus'	 => $t['toimipaikka_tunnus'],
				'toimitustapa_tunnus'	 => $t['toimitustapa_tunnus'],
			);
			$onko_jo_kannassa	 = array_find($toimitustavan_toimipaikat, $values);

			if (!empty($onko_jo_kannassa) and $tunnus != $onko_jo_kannassa[0]['toimitustavat_toimipaikat_tunnus']) {
				$virhe[$i] = t("Toimitustapa toimipaikka kombinaatio on jo tietokannassa");
			}

			$toimitustavat			 = hae_kaikki_toimitustavat();
			$toimitustapa_tunnukset	 = array_column($toimitustavat, 'tunnus');

			if (!in_array($t[$i], $toimitustapa_tunnukset)) {
				$virhe[$i] .= "<br/>".t("Toimitustapaa ei ole olemassa");
			}
		}

		if ((mysql_field_name($result, $i) == "toimipaikka_tunnus")) {
			$toimipaikat			 = hae_toimipaikat();
			$toimipaikka_tunnukset	 = array_column($toimipaikat, 'tunnus');
			//Pääkallopaikan "ei toimipaikkaa" relaatio tehdään 0. Tämä on siis myös sallittu arvo
			$toimipaikka_tunnukset[] = 0;

			if (!in_array($t[$i], $toimipaikka_tunnukset)) {
				$virhe[$i] = t("Toimipaikkaa ei ole olemassa");
			}
		}
	}
}
