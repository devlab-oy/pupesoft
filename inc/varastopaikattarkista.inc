<?php

	if (mysql_field_name($result, $i) == "alkuhyllyalue") {
		$t[$i] = trim(strtoupper($t[$i]));
		$alkuhyllyalue = $t[$i];
		
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Alkuhyllyalue ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	}
	
	if (mysql_field_name($result, $i) == "alkuhyllynro") {
		$t[$i] = trim(strtoupper($t[$i]));
		$alkuhyllynro = $t[$i];
		
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Alkuhyllynro ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	}
	
	if (mysql_field_name($result, $i) == "loppuhyllyalue") {
		$t[$i] = trim(strtoupper($t[$i]));
		$loppuhyllyalue = $t[$i];
		
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Loppuhyllyalue ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	}
	
	if (mysql_field_name($result, $i) == "loppuhyllynro") {
		$t[$i] = trim(strtoupper($t[$i]));
		$loppuhyllynro = $t[$i];
		
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Loppuhyllynro ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	
		//Katotaan osuuko alkuhyllyalue johonkin varastoon
		$query = "	SELECT tunnus
					FROM varastopaikat
					WHERE
					tunnus != '$tunnus'
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
					and yhtio = '$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vares) == 0) {
				//Katotaan osuuko loppuhyllyalue johonkin varastoon
				$query = "	SELECT tunnus
							FROM varastopaikat
							WHERE
							tunnus != '$tunnus'
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
							and yhtio = '$kukarow[yhtio]'";
				$vares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($vares) == 0) {
					//Katotaan onko jo joku varasto syötetyn alueen sisällä
					$query = "	SELECT tunnus
								FROM varastopaikat
								WHERE
								tunnus != '$tunnus'
								and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
								and yhtio = '$kukarow[yhtio]'";
					$vares = mysql_query($query) or pupe_error($query);
				}
		}

		if (mysql_num_rows($vares) != 0) {
			$virhe[$i] .= t("Päällekkäisiä varastoalueita")."!";
			$errori = 1;
		}
	}
	
	if (mysql_field_name($result, $i) == "printteri2") {
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Rahtikirjatulostimet ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	}
	if (mysql_field_name($result, $i) == "printteri4") {
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Rahtikirjatulostimet ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	}
	if (mysql_field_name($result, $i) == "printteri5") {
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Rahtikirjatulostimet ei saa olla tyhjiä")."!";
			$errori = 1;
		}
	}

?>