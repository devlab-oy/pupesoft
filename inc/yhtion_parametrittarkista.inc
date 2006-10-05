<?php

	if (mysql_field_name($result, $i) == "admin_email") {

		if (trim($t[$i]) == '') {
			$virhe[$i] = t("Tieto puuttuu!");
			$errori = 1;
		}

	}

	if (mysql_field_name($result, $i) == "maksuehto_tuotenumero" or
	    mysql_field_name($result, $i) == "rahti_tuotenumero") {

		$tuote = trim($t[$i]);

		// katotaan löytyykö tällänen saldoton tuote
		if ($tuote != "") {

			$query = "	select *
						from tuote
						where yhtio	= '$kukarow[yhtio]'
						and tuoteno = '$tuote'
						and ei_saldoa != ''";
			$xyresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($xyresult) == 0) {
				$virhe[$i] = t("Tuotetta ei löydy!")." / ".t("Tuote pitää olla saldoton!");
				$errori = 1;
			}

		}
	}

	if (mysql_field_name($result, $i) == "maksuehto_tuotenumero") {

		$tuote = trim($t[$i]);

		$query = "	select *
					from maksuehto
					where yhtio	= '$kukarow[yhtio]'
					and summanjakoprososa2 != 0";
		$xyresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($xyresult) != 0 and $tuote=='') {
			$virhe[$i] = t("Yhtiölla on moniehto-maksuehtoja, maksuehto_tuotenumero on syötettävä!");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "pankki_polku" or
	    mysql_field_name($result, $i) == "lasku_polku_abs") {

		// jos polussa on @-merkki niin hyväksytään se aina.. hehe
		if (trim($t[$i]) != "" and strpos(trim($t[$i]), "@") === FALSE) {
			if (!is_writable(trim($t[$i]))) {
				$virhe[$i] = t("Virheellinen polku tai kirjoitusoikeus puuttuu!");
				$errori = 1;
			}
		}
	}

?>