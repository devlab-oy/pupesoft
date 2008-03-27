<?php

if(!function_exists("avainsanatarkista")) {
	function avainsanatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray;
		
		static $seliteerrori, $laji;
		
		if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
			$seliteerrori = 1;
		}

		if ($seliteerrori == 1 and mysql_field_name($result, $i) == "selitetark" and trim($t[$i]) == '') {
			$virhe[$i] = t("Selite ja selitetark ei voi olla tyhjiä")."!";
		}

		if (mysql_field_name($result, $i) == "laji") {
			$laji = trim($t[$i]);
		}
	}
}
	
	
?>