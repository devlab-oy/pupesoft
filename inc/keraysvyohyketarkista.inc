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
		}
	}
