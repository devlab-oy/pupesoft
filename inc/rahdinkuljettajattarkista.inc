<?php

	if (!function_exists("rahdinkuljettajattarkista")) {
		function rahdinkuljettajattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {

			if (mysql_field_name($result, $i) == "koodi" or mysql_field_name($result, $i) == "nimi") {
				if (trim($t[$i]) == '') {
					$virhe[$i] .= t("Tietue ei saa olla tyhjä")."!";
				}
				else {
					$t[$i] = trim(mysql_real_escape_string($t[$i]));
				}
			}

			if (mysql_field_name($result, $i) == "neutraali") {
				$t[$i] = trim($t[$i]) == '' ? "" : $t[$i];
			}
		}
	}
