<?php

if(!function_exists("hyvityssaannottarkista")) {
	function hyvityssaannottarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
		static $temp_tuotekentta, $temp_asiakaskentta;

		if (mysql_field_name($result, $i) == "tuote_kentta" and trim($t[$i]) != '') {
			$temp_tuotekentta = $t[$i];
		}

		if (mysql_field_name($result, $i) == "asiakas_kentta" and trim($t[$i]) != '') {
			$temp_asiakaskentta = $t[$i];
		}

		if (mysql_field_name($result, $i) == "tuote_arvo" and trim($t[$i]) == '' and $temp_tuotekentta != '') {
			$virhe[$i] = t("Arvo puuttuu");
		}

		if (mysql_field_name($result, $i) == "asiakas_arvo" and trim($t[$i]) == '' and $temp_asiakaskentta != '') {
			$virhe[$i] = t("Arvo puuttuu");
		}

		if (mysql_field_name($result, $i) == "rokotusselite" and trim($t[$i]) == '') {
			$virhe[$i] = t("Tieto puuttuu");
		}

		if (mysql_field_name($result, $i) == "rokotusprosentti" and trim($t[$i]) != '' and ($t[$i] < 0 or $t[$i] > 100 or !is_numeric($t[$i]))) {
			$virhe[$i] = t("Virheellinen prosentti");
		}

	}
}

?>
