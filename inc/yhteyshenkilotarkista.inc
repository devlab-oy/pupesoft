<?php

if(!function_exists("yhteyshenkilotarkista")) {
	function yhteyshenkilotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $errori;
		
		static $liitostunnus_tarkista;
				
		if (mysql_field_name($result, $i) == "nimi" and $t[$i] == "") {
			$virhe[$i] = t("Yhteyshenkilöllä on oltava ainakin joku nimi");
			$errori = 1;
		}
		
		if (mysql_field_name($result, $i) == "liitostunnus") {
			$liitostunnus_tarkista = $t[$i];
		}
		
		if (mysql_field_name($result, $i) == "oletusyhteyshenkilo" and $t[$i] != "") {
			$query = "	SELECT tunnus
						FROM yhteyshenkilo
						WHERE yhtio = '$kukarow[yhtio]' 
						and oletusyhteyshenkilo != '' 
						and liitostunnus = '$liitostunnus_tarkista'";
			$result = mysql_query($query) or pupe_error($query);
						
			if (mysql_num_rows($result) > 1) {
				$virhe[$i] = t("Oletusyhteyshenkilö on jo valittu")."!";
				$errori = 1;
			}
		}		
	}
}

?>