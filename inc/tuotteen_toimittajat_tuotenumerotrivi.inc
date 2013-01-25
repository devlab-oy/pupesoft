<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "liitostunnus") {

		$liitostunnus = isset($liitostunnus) ? $liitostunnus : $trow[$i];

		$query = "	SELECT *
					from toimi
					where yhtio = '{$kukarow['yhtio']}'
					and tyyppi != 'P'
					and tunnus = ".(int) $liitostunnus;
		$sresult = pupe_query($query);

		if (mysql_num_rows($sresult) == 1) {
			$srow = mysql_fetch_assoc($sresult);

			$ulos = "<td><input type = 'hidden' name = '{$nimi}' value = '{$liitostunnus}' />{$srow['nimi']} {$srow['nimitark']}</td>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "toim_tuoteno") {
		if ($lukitse_avaimeen != "") {
			$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
			$jatko = 0;
		}
	}
