<?php

if(!function_exists("asiakaskommenttitarkista")) {
	function asiakaskommenttitarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;

		if (mysql_field_name($result, $i) == "kommentti") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = "".t("Tieto puuttuu")."";
			}
		}

		if (mysql_field_name($result, $i) == "ytunnus") {
			$query = "	SELECT ytunnus
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and ytunnus = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = "".t("Asiakas puuttuu tai sitä ei löydy!")."";
			}
		}

		if (mysql_field_name($result, $i) == "tuoteno") {
				$query = "	SELECT tuoteno
							FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($sresult) != 1) {
					$virhe[$i] = "".t("Tuotenumero puuttuu tai sitä ei löydy!")."";
				}
		}
	}
}

?>