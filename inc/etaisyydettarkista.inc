<?php

	if (mysql_field_name($result, $i) == "tunnus") {
		$tunnus = $t[$i];
	}

	if (mysql_field_name($result, $i) == "varasto_postino") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = t("Valitse varasto!");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "postino") {
		if (trim($t[$i]) == '' or !is_numeric(trim($t[$i]))) {
			$virhe[$i] = t("Postinumero ei saa olla tyhjä tai sisältää kirjaimia!");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "postitp") {
		if (trim($t[$i]) == '' or is_numeric(trim($t[$i]))) {
			$virhe[$i] = t("Postitoimipaikka ei saa olla tyhjä tai numero!");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "km") {
		if (!is_numeric(trim($t[$i]))) {
			if (stristr(trim($t[$i]), ",")) {
				$output = str_replace(",", ".", trim($t[$i]));
				if (!is_numeric($output)) {
					$virhe[$i] = t("Vain numerot ovat sallittuja!");
					$errori = 1;
				}
			} else {
				$virhe[$i] = t("Vain numerot ovat sallittuja!");
				$errori = 1;
			}
		}

		if (trim($t[$i]) == '' or trim($t[$i]) == 0) {
			$virhe[$i] = t("Kilometrit ei saa olla tyhjä tai nolla!");
			$errori = 1;
		}		
	}

?>