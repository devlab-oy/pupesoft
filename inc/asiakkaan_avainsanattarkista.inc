<?php

if(!function_exists("asiakkaan_avainsanattarkista")) {
	function asiakkaan_avainsanattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow;

		if (mysql_field_name($result, $i) == "avainsana" and trim($t[$i]) == '') {
			$virhe[$i] = t("Selite ei voi olla tyhjä")."!";
		}
	}
}

?>