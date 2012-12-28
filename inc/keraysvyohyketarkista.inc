<?php

	if (!function_exists("keraysvyohyketarkista")) {
		function keraysvyohyketarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {

			if (mysql_field_name($result, $i) == "sallitut_alustat" or mysql_field_name($result, $i) == "yhdistelysaanto") {
				if (count($t[$i]) > 0) {
					if (count($t[$i]) == 1 and $t[$i][0] == 'default') {
						unset($t[$i]);
						$t[$i] = '';
					}
					else {
						unset($t[$i][0]);
						$t[$i] = implode(",", $t[$i]);
					}
				}
			}

			if (mysql_field_name($result, $i) == "lahtojen_valinen_enimmaisaika" or mysql_field_name($result, $i) == "max_keraysera_pintaala") {
				if (trim($t[$i]) == '' or trim($t[$i]) == 0) {
					$virhe[$i] .= t("Arvo ei saa olla nolla tai tyhj‰‰")."!";
				}
			}
		}
	}
