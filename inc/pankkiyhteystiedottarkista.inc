<?php

if(!function_exists("pankkiyhteystiedottarkista")) {
	function pankkiyhteystiedottarkista (&$t, $i, $result, $tunnus, &$virhe) {
		global $kukarow, $yhtiorow, $alias_set;
		
		if (mysql_field_name($result, $i) == "pankkitili1" or
			mysql_field_name($result, $i) == "pankkitili2" or
			mysql_field_name($result, $i) == "pankkitili3") {

			if (strtoupper($yhtiorow['maa']) == "FI" and trim($t[$i]) != "") {

				$pankkitili = $t[$i];

		//		require ('pankkitilinoikeellisuus.php');

				if ($pankkitili == '') {
					$virhe[$i] = t("Pankkitili on virheellinen");
				}
			}
		}
	}
}


?>