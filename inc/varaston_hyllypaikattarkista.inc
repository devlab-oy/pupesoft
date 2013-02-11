<?php

	if (!function_exists("varaston_hyllypaikattarkista")) {
		function varaston_hyllypaikattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
			global $kukarow;

			static $chk_hyllyalue, $chk_hyllynro, $chk_hyllyvali, $chk_hyllytaso;

			if (mysql_field_name($result, $i) != 'laatija' and
				mysql_field_name($result, $i) != 'luontiaika' and
				mysql_field_name($result, $i) != 'muutospvm' and
				mysql_field_name($result, $i) != 'muuttaja' and
				mysql_field_name($result, $i) != 'tunnus') {

				if (trim($t[$i]) != '') {
					$t[$i] = mysql_real_escape_string(trim($t[$i]));

					if (mysql_field_name($result, $i) == "hyllyalue" or
						mysql_field_name($result, $i) == "hyllynro" or
						mysql_field_name($result, $i) == "hyllyvali" or
						mysql_field_name($result, $i) == "hyllytaso") {

						${'chk_'.mysql_field_name($result, $i)} = $t[$i];
					}

					if (mysql_field_name($result, $i) == "hyllytaso" and $chk_hyllyalue != '' and $chk_hyllynro != '' and $chk_hyllyvali != '' and $chk_hyllytaso != ''
						and ($chk_hyllyalue != $trow['hyllyalue'] or $chk_hyllynro != $trow['hyllynro'] or $chk_hyllyvali != $trow['hyllyvali'] or $chk_hyllytaso != $trow['hyllytaso'])) {

						$query = "	SELECT tunnus
									FROM varaston_hyllypaikat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND hyllyalue = '{$chk_hyllyalue}'
									AND hyllynro = '{$chk_hyllynro}'
									AND hyllyvali = '{$chk_hyllyvali}'
									AND hyllytaso = '{$chk_hyllytaso}'";
						$chk_res = pupe_query($query);

						if (mysql_num_rows($chk_res) != 0) {
							$virhe[$i] = t("Syötetty varastopaikka on jo tietokannassa")."!";
						}
					}
				}
				else {
					$virhe[$i] = ucwords(mysql_field_name($result, $i)).' '.t("ei saa olla tyhjä")."!";
				}
			}
		}
	}
