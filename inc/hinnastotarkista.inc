<?php

	if (mysql_field_name($result, $i) == "tunnus") {
		$tunnus = $t[$i];
	}

	if (mysql_field_name($result, $i) == "hinta") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = t("Tieto puuttuu!");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "tuoteno") {
		$chtuoteno = $t[$i];
		if ($chtuoteno != '') {
			$query = "	SELECT tuoteno
						FROM tuote
						WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tuotenumero puuttuu tai sitä ei löydy!");
				$errori = 1;
			}
		}
	}

	if (mysql_field_name($result, $i) == "alkupvm") {
		$chalkupvm = $t[$i];
		if ($chalkupvm == '') {
			$chalkupvm = '0000-00-00';
		}
	}

	if (mysql_field_name($result, $i) == "loppupvm") {
		$chloppupvm = $t[$i];
		if ($chloppupvm == '') {
			$chloppupvm = '0000-00-00';
		}
	}

?>
