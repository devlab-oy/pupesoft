<?php
	if (mysql_field_name($result, $i) == "tuoteno") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = t("Tieto puuttuu");
			$errori = 1;
		}
		else {
			if ($tunnus == "") {
				$query  = "	SELECT yhtio 
							FROM tuote 
							WHERE yhtio = '$kukarow[yhtio]' 
							and tuoteno = '".trim($t[$i])."'";
				$chkressiresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($chkressiresult) > 0) {
					$virhe[$i] = t("Tuotenumero on jo perustettu järjestelmään");
					$errori = 1;
				}
			}
		}
		$tem_tuoteno = $t[$i];
	}

	if (mysql_field_name($result, $i) == "nimitys") {
		// Nimitystä ei saa vaihtaa, jos tuote on viranomaistuote
		$query  = "	SELECT nimitys 
	    			FROM tuote 
	    			WHERE yhtio = '$kukarow[yhtio]' 
	    			and tuoteno = '$tem_tuoteno'
					and tuotetyyppi in ('A','B')";
	    $sresult = mysql_query($query) or pupe_error($query);
	    $srow = mysql_fetch_array($sresult);
	
		if (mysql_num_rows($sresult) > 0 and trim(strtoupper($srow["nimitys"])) != trim(strtoupper($t[$i]))) {
			$virhe[$i] = t("Viranomaistuotteen nimitystä ei voi muuttaa")."! ";
			$errori = 1;	
		}
		
		if (trim($t[$i]) == '') {
			$virhe[$i] .= t("Nimitys puuttuu")."!";
			$errori = 1;
		}
	}

	if ((mysql_field_name($result, $i) == "tilino" or
		mysql_field_name($result, $i) == "tilino_eu" or
		mysql_field_name($result, $i) == "tilino_ei_eu") and $t[$i] != 0) {
		
		$query = "	SELECT nimi
					FROM tili
					WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
		$sresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($sresult) != 1) {
			$virhe[$i] = t("Tili puuttuu tai sitä ei loydy");
			$errori = 1;
		}
	}

	if ((mysql_field_name($result, $i) == "tullinimike1") and ($t[$i] != 0)) {
		$query = "	SELECT cn
					FROM tullinimike
					WHERE cn = '$t[$i]' and kieli = '$yhtiorow[kieli]'";
		$sresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($sresult) != 1) {
			$virhe[$i] = t("Virheellinen tullinimike");
			$errori = 1;
		}
	}
	
	if (mysql_field_name($result, $i) == "tuotetyyppi") {
		// Tyyppiä ei saa vaihtaa, jos tuotteella on saldoa
		$query  = "	SELECT tuotetyyppi 
	    			FROM tuote 
	    			WHERE yhtio = '$kukarow[yhtio]' 
	    			and tuoteno = '$tem_tuoteno'";
	    $sresult = mysql_query($query) or pupe_error($query);
	    $srow = mysql_fetch_array($sresult);
	    	
	    if (trim(strtoupper($srow["tuotetyyppi"])) != trim(strtoupper($t[$i]))) {
	    	$query = "	SELECT count(*) kpl
	    				FROM tuotepaikat
	    				WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tem_tuoteno' and saldo != 0";
	    	$sresult = mysql_query($query) or pupe_error($query);
	    	$srow = mysql_fetch_array($sresult);

	    	if ($srow["kpl"] > 0) {
				$virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on saldoa")."!";
	    		$errori = 1;
			}
	    }				
	}
	
	if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {
		// Tyyppiä ei saa vaihtaa, jos tuotteella on saldoa
		$query  = "	SELECT sarjanumeroseuranta 
	    			FROM tuote 
	    			WHERE yhtio = '$kukarow[yhtio]' 
	    			and tuoteno = '$tem_tuoteno'";
	    $sresult = mysql_query($query) or pupe_error($query);
	    $srow = mysql_fetch_array($sresult);
	    	
	    if (trim(strtoupper($srow["sarjanumeroseuranta"])) != trim(strtoupper($t[$i]))) {
	    	$query = "	SELECT count(*) kpl
	    				FROM tuotepaikat
	    				WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tem_tuoteno' and saldo != 0";
	    	$sresult = mysql_query($query) or pupe_error($query);
	    	$srow = mysql_fetch_array($sresult);

	    	if ($srow["kpl"] > 0) {
				$virhe[$i] = t("Seurantatyyppiä ei voida muuttaa, koska tuotteella on saldoa")."!";
	    		$errori = 1;
			}
	    }				
	}

	if (mysql_field_name($result, $i) == "ei_saldoa") {
		if ($t[$i] != '') {
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tem_tuoteno' and yhtio = '$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($sresult) != 0) {
				$virhe[$i] = t("Tuotteella on varastopaikkoja. Poista ne ensin!");
				$errori = 1;
			}
		}
	}

	if (mysql_field_name($result, $i) == "vienti") {
		if ($t[$i] != '' and strpos($t[$i], "+") !== FALSE and strpos($t[$i], "-") !== FALSE) {
			$virhe[$i] = t("Laita samanaikaisesti vain + tai - maakoodeja!");
			$errori = 1;
		}
	}

	/*
	if (mysql_field_name($result, $i) == "tuotekuva" and $t[$i] != '') {
		if (!file_exists($t[$i])) {
			$virhe[$i] = t("Tiedosto ei löydy")."!";
			$errori = 1;
		}
	}
	*/

?>
