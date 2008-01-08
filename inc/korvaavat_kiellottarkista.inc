<?php

if(!function_exists("korvaavat_kiellottarkista")) {
	function korvaavat_kiellottarkista (&$t, $i, $result, $tunnus, &$virhe) {
		global $kukarow, $yhtiorow, $alias_set;
		
		if (mysql_field_name($result, $i) == "ytunnus" and $t[$i] != '*') {

			$query   = "select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("Ytunnus ei löydy!");
			}

		}
	}
}


?>