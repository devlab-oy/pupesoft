<?PHP
// puun_alkiotarkista.inc

if(!function_exists("puun_alkiotarkista")) {
	function puun_alkiotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;


		if (mysql_field_name($result, $i) == "laji") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Lajia ei voi jättää tyhjäksi")."!";
			}
		}

		if (mysql_field_name($result, $i) == "puun_tunnus") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Puun tunnusta ei voi jättää tyhjäksi")."!";
			}
		}		
	}
}
?>