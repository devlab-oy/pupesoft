<?php
	if (mysql_field_name($result, $i) == "nimitys") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = "".t("Tieto puuttuu")."";
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
			$virhe[$i] = "".t("Tili puuttuu tai sitä ei loydy")."";
			$errori = 1;
		}
	}
	
	if ((mysql_field_name($result, $i) == "tullinimike1") and ($t[$i] != 0)) {
		$query = "	SELECT cn
					FROM tullinimike
					WHERE cn = '$t[$i]' and kieli = '$yhtiorow[kieli]'";
		$sresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($sresult) != 1) {
			$virhe[$i] = "".t("Virheellinen tullinimike")."";
			$errori = 1;
		}
	}
	
	if (mysql_field_name($result, $i) == "tuoteno") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = "".t("Tieto puuttuu")."";
			$errori = 1;
		}
		$tem_tuoteno = $t[$i];
	}
	
	if (mysql_field_name($result, $i) == "laatija") {
		if ($tunnus == "") {
			$t[$i] = $kukarow['kuka'];
		}
	}
	
	if (mysql_field_name($result, $i) == "luontiaika") {
		if ($tunnus == "") {
			$t[$i] = date('Y-m-d H:i:s');
		}
	}
	
	if (mysql_field_name($result, $i) == "ei_saldoa") {
		if ($t[$i] != '') {
			$query = "SELECT *
					FROM tuotepaikat
					WHERE tuoteno = '$tem_tuoteno' and yhtio = '$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($sresult) != 0) {
				$virhe[$i] = "".t("Tuotteella on varastopaikkoja. Poista ne ensin!")."";
				$errori = 1;
			}
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
