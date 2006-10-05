<?php

	if ((mysql_field_name($result, $i) == "ytunnus") or
		(mysql_field_name($result, $i) == "rahtisopimus")) {
		if (trim($t[$i]) == '') {
			$virhe[$i] = "".t("Tieto puuttuu")."";
			$errori = 1;
		}
	}


	if (mysql_field_name($result, $i) == "ytunnus") {
		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$t[$i]'";
		$tarkrow = mysql_query($query) or pupe_error($query);

		// löytykö yhtään tai onko kyseessä oman yrityksen ytunnus
		if ((mysql_num_rows($tarkrow)==0) and ($t[$i]!=$yhtiorow['ytunnus'])) {
			$virhe[$i] = "".t("Ytunnus ei löydy")."";
			$errori = 1;
		}
	}
?>