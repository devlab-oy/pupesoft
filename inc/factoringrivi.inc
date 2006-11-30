<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "valkoodi") { //valuutta
		$query = "	SELECT nimi, tunnus
	                FROM valuu
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['nimi']) {
					$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "pankki_tili") { //valuutta
		$query = "	SELECT nimi, tilino
	                FROM yriti
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tilino']) {
					$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tilino]' $sel>$vrow[nimi] $vrow[tilino]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
?>
