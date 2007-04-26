<?php

	if (mysql_field_name($result, $i) == "tuoteno") {
		$query = "SELECT yhtio FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '".trim($t[$i])."'";
		$chkressiresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($chkressiresult) == 0) {
			$virhe[$i] = t("Tuotenumero ei löydy");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "alv") {
		if (!is_numeric($t[$i]) or $t[$i] > 100) {
			$virhe[$i] = t("Virheellinen ALV");
			$errori = 1;
		}
	}

?>
