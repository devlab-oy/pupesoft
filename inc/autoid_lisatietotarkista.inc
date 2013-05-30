<?php

	if (!function_exists("autoid_lisatietotarkista")) {
		function autoid_lisatietotarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
			global $kukarow, $yhtiorow, $alias_set;

			if (mysql_field_name($result, $i) == "autoid_lukittu") {

				if (count($t[$i]) > 0) {
					if (count($t[$i]) == 1 and $t[$i][0] == 'default') {
						unset($t[$i]);
						$t[$i] = 0;
					}
					else {
						if (isset($t[$i][0])) unset($t[$i][0]);
						$t[$i] = 1;
					}
				}
			}

		}
	}
