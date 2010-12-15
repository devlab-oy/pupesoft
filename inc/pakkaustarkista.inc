<?php

	if (!function_exists("pakkaustarkista")) {
		function pakkaustarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {

			if (mysql_field_name($result, $i) == "pakkaus") {
				if (trim($t[$i]) == '') {
					$virhe[$i] .= t("Pakkaus ei saa olla tyhjä")."!";
				}
				else {
					$t[$i] = trim(mysql_real_escape_string($t[$i]));
				}
			}

			if (mysql_field_name($result, $i) == "pakkauskuvaus") {
				if (trim($t[$i]) == '') {
					$virhe[$i] .= t("Pakkauskuvaus ei saa olla tyhjä")."!";
				}
				else {
					$t[$i] = trim(mysql_real_escape_string($t[$i]));
				}
			}

			if (mysql_field_name($result, $i) == "korkeus" or mysql_field_name($result, $i) == "leveys" or mysql_field_name($result, $i) == "syvyys" or mysql_field_name($result, $i) == "paino") {
				if (trim($t[$i]) != '') {
					$t[$i] = trim(mysql_real_escape_string(str_replace(",", ".", $t[$i])));
				}
			}
		}
	}

?>