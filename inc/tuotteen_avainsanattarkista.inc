<?php
	if (mysql_field_name($result, $i) == "tuoteno") {
		if ($t[$i] != '') {
			$query = "SELECT *
					FROM tuote
					WHERE tuoteno = '$t[$i]' and yhtio = '$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = "".t("Tuotenumeroa ei löydy. Skarppaa!")."";
				$errori = 1;
			}
		}
		else {
			$virhe[$i] = "".t("Tuotenumeroa ei voi jättää tyhjäksi. Skarppaa!")."";
			$errori = 1;
		}
	}
	if (mysql_field_name($result, $i) == "laji") {
		if ($t[$i] == '') {
			$virhe[$i] = "".t("Lajia ei voi jättää tyhjäksi. Skarppaa!")."";
			$errori = 1;
		}
		$lajitalteen = $t[$i];
	}
	
	if (mysql_field_name($result, $i) == "selite" and $lajitalteen == "SISTOIMITTAJA") {
		if ($t[$i] == '') {
			$virhe[$i] = t("Selitettä ei voi jättää tyhjäksi. Skarppaa!")."";
			$errori = 1;
		}
		else {
			// salitaan vaan konserniyhtiö joka ei ole me itse
			$query = "SELECT *
					FROM yhtio
					WHERE yhtio = '$t[$i]' and konserni='$yhtiorow[konserni]' and yhtio!='$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("Yhtiötä ei löydy. Skarppaa!");
				$errori = 1;
			}
		}
	}
?>