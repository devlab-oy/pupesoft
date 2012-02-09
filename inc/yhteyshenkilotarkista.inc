<?php

if(!function_exists("yhteyshenkilotarkista")) {
	function yhteyshenkilotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $errori;

		static $liitostunnus_tarkista, $tyyppi_tarkista;

		if (mysql_field_name($result, $i) == "nimi" and $t[$i] == "") {
			$virhe[$i] = t("Tieto puuttuu");
			$errori = 1;
		}

		if (mysql_field_name($result, $i) == "tyyppi" and $t[$i] != "A" and $t[$i] != "T") {
			$tyyppi_tarkista = $t[$i];

			if ($t[$i] != "A" and $t[$i] != "T") {
				$virhe[$i] = t("Virheellinen tyyppi");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "liitostunnus") {
			$liitostunnus_tarkista = $t[$i];
		}

		if (mysql_field_name($result, $i) == "oletusyhteyshenkilo" and $t[$i] != "") {
			$query = "	SELECT tunnus
						FROM yhteyshenkilo
						WHERE yhtio 			 = '$kukarow[yhtio]'
						and oletusyhteyshenkilo != ''
						and liitostunnus 		 = '$liitostunnus_tarkista'
						and tyyppi 		 		 = '$tyyppi_tarkista'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 1) {
				$virhe[$i] = t("Oletusyhteyshenkilö on jo valittu")."!";
				$errori = 1;
			}
		}
	}
}

?>