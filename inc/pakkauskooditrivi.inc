<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "pakkaus") {
		if ($lukitse_avaimeen != "") {
			$tyyppi = 4;
			
			$ulos = "<input type='hidden' name='{$nimi}' value='{$lukitse_avaimeen}'>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "rahdinkuljettaja") {

		$sel = '';
		if ($trow[$i] != '') $sel[$trow[$i]] = 'SELECTED';

		$ulos = "<td><select name='$nimi'>";

		$query = "	SELECT *
					FROM rahdinkuljettajat
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY nimi";
		$rahdinkuljettajat_res = pupe_query($query);

		while ($rahdinkuljettajat_row = mysql_fetch_assoc($rahdinkuljettajat_res)) {
			$ulos .= "<option value='{$rahdinkuljettajat_row['koodi']}'>{$rahdinkuljettajat_row['nimi']}</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
