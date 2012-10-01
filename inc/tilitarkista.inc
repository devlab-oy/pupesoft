<?php

if(!function_exists("tilitarkista")) {
	function tilitarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;

		if (mysql_field_name($result, $i) == "tilino" and trim($t[$i]) != '') {

			$query   = "select tunnus from tili where yhtio='$kukarow[yhtio]' and tilino='$t[$i]' and tunnus != '$tunnus'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) > 0) {
				$virhe[$i] = t("Tämä tilinumero on jo perustettu.");
			}

		}

		if ((mysql_field_name($result, $i) == "projekti" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "kustp") and (int) trim($t[$i]) != 0) {

			if (mysql_field_name($result, $i) == "projekti") $kptyyppi = "P";
			if (mysql_field_name($result, $i) == "kohde") 	 $kptyyppi = "O";
			if (mysql_field_name($result, $i) == "kustp") 	 $kptyyppi = "K";

			$tpque = "	SELECT tunnus
						from kustannuspaikka
						where yhtio = '$kukarow[yhtio]'
						and tyyppi = '$kptyyppi'
						and kaytossa != 'E'
						and tunnus = '$t[$i]'";
			$tpres = pupe_query($tpque);

			if (mysql_num_rows($tpres) == 0) {
				$virhe[$i] .= t("Tilin tarkennetta ei löydy")."!";
			}
		}

		if (mysql_field_name($result, $i) == "ulkoinen_taso" and trim($t[$i]) == "") {
			$virhe[$i] = t("Ulkoinen taso on syötettävä")."!";
		}

		if (mysql_field_name($result, $i) == "oletus_alv" and trim($t[$i]) == "") {
			$t[$i] = NULL;
		}
	}
}


?>