<?php

	if (!function_exists("pakkaustarkista")) {
		function pakkaustarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
			global $yhtiorow;

			if (mysql_field_name($result, $i) == "pakkaus") {
				if (trim($t[$i]) == '') {
					$virhe[$i] .= t("Pakkaus ei saa olla tyhjä")."!";
				}
				else {
					$t[$i] = trim(mysql_real_escape_string($t[$i]));
				}
			}

			if (mysql_field_name($result, $i) == "pakkauskuvaus") {
				if (trim($t[$i]) == '') {
					$virhe[$i] .= t("Pakkauskuvaus ei saa olla tyhjä")."!";
				}
				else {
					$t[$i] = trim(mysql_real_escape_string($t[$i]));
				}
			}

			if (mysql_field_name($result, $i) == "korkeus" or mysql_field_name($result, $i) == "leveys" or mysql_field_name($result, $i) == "syvyys" or mysql_field_name($result, $i) == "paino") {
				$t[$i] = trim(str_replace(",", ".", $t[$i]));

				if ($yhtiorow['varastopaikkojen_maarittely'] == 'M' and ($t[$i] == '' or !is_numeric($t[$i]) or $t[$i] == 0)) {
					$virhe[$i] .= t("Mitta- ja painotiedot eivät saa olla tyhjiä")."!";
				}
				elseif ($t[$i] != '') {
					$t[$i] = mysql_real_escape_string($t[$i]);
				}
			}

			if (mysql_field_name($result, $i) == 'kayttoprosentti') {
				$t[$i] = (int) $t[$i];

				if (trim($t[$i]) == 0) $virhe[$i] .= t("Käyttöprosentti pitää olla kokonaisluku ja sen pitää olla suurempi kuin nolla");
			}
		}
	}

?>