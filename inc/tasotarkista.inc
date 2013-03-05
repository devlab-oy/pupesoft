<?php

if(!function_exists("tasotarkista")) {
	function tasotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;

		static $tyyppitalteen;

		if (mysql_field_name($result, $i) == "tyyppi") {
			$tyyppitalteen = $t[$i];
		}

		if (mysql_field_name($result, $i) == "taso") {

			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Taso on syötettävä!");
			}
			elseif (strpos(trim($t[$i]), "Ö") !== FALSE ) {
				$virhe[$i] = t("Ö ei ole sallittu kirjain tasossa!");
			}
			elseif (in_array(strtoupper($tyyppitalteen), array('S','U')) and !in_array(substr(trim($t[$i]), 0, 1), array("1", "2", "3"))) {
				$virhe[$i] = t("Taso tulee alkaa numerolla 1, 2 tai 3!");
			}
			elseif ((strtoupper($tyyppitalteen) == 'B') and !is_numeric($t[$i])) {
				$virhe[$i] = t("Tulosseuranta taso tulee olla numero!");
			}
			else {
				$query = "	SELECT tunnus
							FROM taso
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND taso = BINARY '$t[$i]'
							AND tyyppi = '$tyyppitalteen'
							AND tunnus != '$tunnus'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					$virhe[$i] = t("Tämä taso on jo perustettu!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "nimi" and trim($t[$i]) == '') {
			$virhe[$i] = t("Syötä nimitys!");
		}

		if (mysql_field_name($result, $i) == "summattava_taso" and trim($t[$i]) != '') {
			$t[$i] = trim($t[$i]);

			foreach (explode(",", $t[$i]) as $sumtas) {
				$query = "	SELECT tunnus
							FROM taso
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND taso = BINARY '$sumtas'
							AND tyyppi = '$tyyppitalteen'
							AND tunnus != '$tunnus'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = t("Summattavaa tasoa %s ei ole perustettu!", "", $sumtas);
				}
			}
		}
	}
}
