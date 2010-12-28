<?php

	if (!function_exists("varaston_hyllypaikattarkista")) {
		function varaston_hyllypaikattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
			if (mysql_field_name($result, $i) != 'laatija' and
				mysql_field_name($result, $i) != 'luontiaika' and
				mysql_field_name($result, $i) != 'muutospvm' and
				mysql_field_name($result, $i) != 'muuttaja' and
				mysql_field_name($result, $i) != 'tunnus') {
			
				if (trim($t[$i]) != '') {
					$t[$i] = mysql_real_escape_string(trim($t[$i]));
				}
				else {
					$virhe[$i] = ucwords(mysql_field_name($result, $i)).' '.t("ei saa olla tyhjä")."!";
				}
			}
		}
	}
