<?php

	if (mysql_field_name($result, $i) == "tuoteno") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = "".t("Tieto puuttuu")."";
			$errori = 1;
		}
		else {
			$query = "SELECT *
					FROM tuote
					WHERE tuoteno = '$t[$i]' and yhtio = '$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);
        	
			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tuotetta ei löydy!");
				$errori = 1;
			}
			else {
				$srivi = mysql_fetch_array($sresult);
				if ($srivi["sarjanumeroseuranta"] == "") {
					$virhe[$i] = t("Tuote ei ole sarjanumeroseurannassa!");
					$errori = 1;
				}
			}
		}
	}	
?>