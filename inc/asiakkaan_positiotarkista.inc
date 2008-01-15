<?php

if(!function_exists("asiakkaan_positiotarkista")) {
	function asiakkaan_positiotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
		
		static $kohde;
		if (mysql_field_name($result, $i) == "kohde") {
			$query = "	SELECT tunnus
						FROM asiakkaan_kohde
						WHERE yhtio='$kukarow[yhtio]' and tunnus = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) < 1) {
				$virhe[$i] = "".t("Kohde puuttuu tai sitä ei löydy!")."";
			}

			$kohde = $t[$i];
		}

		// tarkistetaan onko kohde jo olemassa
		if (mysql_field_name($result, $i) == "positio") {
			$query = "	SELECT tunnus
						FROM asiakkaan_positio
						WHERE yhtio='$kukarow[yhtio]' and positio = '$t[$i]' and asiakkaan_kohde = '$kohde'";
			$sresult = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($sresult)>1) {
				$virhe[$i] = t("Kohteella on jo tämä positio perustettuna!");
			}
		}

		if (mysql_field_name($result, $i) == "lisannyt") {
			if ($tunnus == "") {
				$t[$i] = $kukarow['kuka'];
			}
		}

		if (mysql_field_name($result, $i) == "lisatty") {
			if ($tunnus == "") {
				$t[$i] = date('Y-m-d');
			}
		}

		if (mysql_field_name($result, $i) == "muokannut") {
			$t[$i] = $kukarow['kuka'];
		}

		if (mysql_field_name($result, $i) == "muokattu") {
			$t[$i] = date('Y-m-d H:i:s');

		}
	}
}


?>
