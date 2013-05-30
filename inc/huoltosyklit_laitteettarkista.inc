<?php

if (!function_exists("huoltosyklit_laitteet")) {

	function huoltosyklit_laitteettarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		if (mysql_field_name($result, $i) == "huoltosykli_tunnus") {
			$query = "	SELECT *
						FROM huoltosykli
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = {$t[$i]}";
			$huoltosykli_result = pupe_query($query);

			if (mysql_num_rows($huoltosykli_result) == 0) {
				$virhe[$i] .= t("Huoltosykliä ei ole olemassa");
			}

			if (empty($virhe[$i])) {
				//jos ei virheitä, asetetaan koeponnistus toimenpiteellisten huoltosyklien viimeinen_tapahtuma = laite.valm_pvm
				//tällöin työmääräysten generoiminen helpottuu, jos laitteelle ei ole ikinä ennen generoitu koeponnistus työmääräystä
				$query = "	SELECT tuotteen_avainsanat.selite as toimenpide_tuotteen_tyyppi
							FROM huoltosykli
							JOIN tuote
							ON ( tuote.yhtio = huoltosykli.yhtio
								AND tuote.tuoteno = huoltosykli.toimenpide )
							JOIN tuotteen_avainsanat
							ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
								AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
								AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely')
							WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
							AND huoltosykli.tunnus = '{$t[$i]}'";
				$result = pupe_query($query);
				$huoltosykli = mysql_fetch_assoc($result);

				if ($huoltosykli['toimenpide_tuotteen_tyyppi'] == 'koeponnistus') {
					$query = "	SELECT valm_pvm
								FROM laite
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$t['laite_tunnus']}'";
					$result = pupe_query($query);
					$laite = mysql_fetch_assoc($result);

					$t['viimeinen_tapahtuma'] = $laite['valm_pvm'];
				}
			}
		}

		if (mysql_field_name($result, $i) == "laite_tunnus") {
			$query = "	SELECT *
						FROM laite
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = {$t[$i]}";
			$laite_result = pupe_query($query);

			if (mysql_num_rows($laite_result) == 0) {
				$virhe[$i] .= t("Laitetta ei ole olemassa");
			}
		}

		if (mysql_field_name($result, $i) == "huoltovali") {
			if (!is_numeric($t[$i])) {
				$virhe[$i] = t("Huoltoväli annettava päivinä");
				//$virhe[$i] = t("Huoltoväli annettava päivinä, mittarilukemana tai käyttökertana");
			}

			if (empty($t[$i])) {
				$virhe[$i] = t("Huoltoväli ei voi olla tyhjä").'!';
			}

			//huoltoväli ei voi olla pidempi kuin huoltoskyli_tunnus:ksen huoltoväli
			$huoltosykli = hae_huoltosykli($t['huoltosykli_tunnus']);

			if ($t[$i] > $huoltosykli['huoltovali']) {
				$virhe[$i] = t("Annettu huoltoväli ei voi olla pidmepi kuin oletushuoltosyklin huoltoväli") . " {$huoltosykli['huoltovali']}";
			}
		}

		if (mysql_field_name($result, $i) == "pakollisuus") {
			if ((int)$t[$i] != 1 and (int)$t[$i] != 0) {
				$virhe[$i] = t("Virheellinen pakollisuus syötetty");
			}
		}
	}
}