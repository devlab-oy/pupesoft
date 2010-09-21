<?PHP
// tuotteen_alkiotarkista.inc

if(!function_exists("tuotteen_alkiotarkista")) {
	function tuotteen_alkiotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;


		if (mysql_field_name($result, $i) == "laji") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Lajia ei voi jättää tyhjäksi")."!";
			}
		}

		if (mysql_field_name($result, $i) == "selite") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Selitettä ei voi jättää tyhjäksi")."!";
			}
		}		
	}
}
?>