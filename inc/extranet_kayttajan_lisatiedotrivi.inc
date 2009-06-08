<?php
	
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	//yhtiötä ei näytetä
	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "liitostunnus") {
		
		$liitostunnus = $liitostunnus != '' ? $liitostunnus : ($haku[4] != '' ? $haku[4] : '');
		
		$ulos .= "<td><input type='text' name='$nimi' id='$nimi' value='$liitostunnus'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$liitostunnus = $liitostunnus != '' ? $liitostunnus : ($haku[4] != '' ? $haku[4] : '');

		$query = "	SELECT laji
					FROM extranet_kayttajan_lisatiedot
					WHERE yhtio = '{$kukarow['yhtio']}' 
					AND laji = '{$trow['laji']}'
					AND liitostunnus = '$liitostunnus'";
		$sresult = mysql_query($query) or pupe_error($query);

		$srow = mysql_fetch_array($sresult);

		$ulos .= "<td><input type='text' name='$nimi' id='$nimi' value='{$srow['laji']}'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite") {

		$liitostunnus = $liitostunnus != '' ? $liitostunnus : ($haku[4] != '' ? $haku[4] : '');

		$query = "	SELECT selite
					FROM extranet_kayttajan_lisatiedot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laji = '{$trow['laji']}'
					AND liitostunnus = '$liitostunnus'
					ORDER BY selite";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$vrow = mysql_fetch_array($vresult);

		$ulos .= "<td><input type='text' name='$nimi' id='$nimi' value='{$vrow['selite']}'></td>";
		
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "selitetark") {

		$liitostunnus = $liitostunnus != '' ? $liitostunnus : ($haku[4] != '' ? $haku[4] : '');

		$query = "	SELECT selitetark
					FROM extranet_kayttajan_lisatiedot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laji = '{$trow['laji']}'
					AND liitostunnus = '$liitostunnus'
					ORDER BY selite";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$vrow = mysql_fetch_array($vresult);

		$ulos .= "<td><input type='text' name='$nimi' id='$nimi' value='{$vrow['selitetark']}'></td>";
		
		$jatko = 0;
	}
?>