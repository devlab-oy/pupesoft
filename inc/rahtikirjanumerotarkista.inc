<?php

if(!function_exists("rahtikirjanumerotarkista")) {
	function rahtikirjanumerotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $laji, $poistolukko;

		if (mysql_field_name($result, $i) == "rahtikirjanro") {
			$t[$i] = trim($t[$i]);

			if ($t[$i] == '') {
				$virhe[$i] = t("Tieto ei saa olla tyhjä")."!";
			}
			else {
				$query  = "	SELECT tunnus
			    			FROM rahtikirjanumero
			    			WHERE yhtio = '$kukarow[yhtio]'
			    			and rahtikirjanro = '$t[$i]'
							and tunnus != '$tunnus'";
			    $sresult = pupe_query($query);

				if (mysql_num_rows($sresult) != 0) {
					// Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
					$virhe[$i] = t("Rahtikirjanumero on jo perustettu")."!";
				}
				else {
					if (strtoupper($yhtiorow['maa']) == 'FI') {
						//Oikeellisuustarkistus LOGY
						if (strlen($t[$i]) != 12) {
							$virhe[$i] = t("Rahtikirjanumero on oltava 12-merkkiä pitkä")."!";
						}
						elseif($t[$i] != mod10_tarkiste(substr($t[$i],0,-1))) {
							$virhe[$i] = t("Rahtikirjanumeron tarkiste on virheellinen")."!";
						}
					}
				}
			}
		}
	}
}

?>