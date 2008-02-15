<?php

if(!function_exists("asiakastarkista")) {
	function asiakastarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow;
		
		if (mysql_field_name($result, $i) == "liitostunnus") {
			$query = "SELECT * FROM tuote WHERE yhtio='$kukarow[yhtio]' AND tunnus='$t[$i]'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 0) {
				$virhe[$i] = t("Valitsemasi tuotetta ei löytynyt.");
			}
		}

	}
}
?>