<?php

	if (!function_exists("pakkauskoodittarkista")) {
		function pakkauskoodittarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {

			if (mysql_field_name($result, $i) == "pakkaus" or mysql_field_name($result, $i) == "rahdinkuljettaja" or mysql_field_name($result, $i) == "koodi") {
				if (trim($t[$i]) == '') {
					$virhe[$i] .= t("Tietue ei saa olla tyhjä")."!";
				}
				else {
					$t[$i] = trim(mysql_real_escape_string($t[$i]));
				}
			}
		}
	}
