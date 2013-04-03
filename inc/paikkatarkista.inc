<?php

if (!function_exists("paikkatarkista")) {

	function paikkatarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $asiakasid;

		if (mysql_field_name($result, $i) == "kohde") {
			$kohde_tunnus = trim($t[$i]);

			if(!empty($kohde_tunnus)) {
				$query = "	SELECT *
							FROM kohde
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$kohde_tunnus}'";
				$kohde_result = pupe_query($query);

				if(mysql_num_rows($kohde_result) != 1) {
					$virhe[$i] = t("Kohde puuttuu tai sitä ei löydy!");
				}
			}
		}
	}
}