<?php

if(!function_exists("yhteyshenkilotarkista")) {
	function yhteyshenkilotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
				
		if (mysql_field_name($result, $i) == "nimi" and $t[$i] == "") {
			$virhe[$i] = t("Yhteyshenkilöllä on oltava ainakin joku nimi");
		}
	}
}

?>