<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0; 
	}
	
	if (mysql_field_name($result, $i) == "asiakkaan_kohde") {

		if ($asiakkaan_kohde != ''){
			$trow[$i] = $asiakkaan_kohde;
			$tyyppi = 3;
		}
		elseif($trow[$i] != "") {
			$query = "	SELECT concat_ws(' - ', asiakkaan_kohde.kohde, left(asiakas.nimi, 30)) kohde
						FROM asiakkaan_kohde
						LEFT JOIN asiakas ON asiakas.yhtio = asiakkaan_kohde.yhtio and asiakas.tunnus = asiakkaan_kohde.asiakas
						WHERE asiakkaan_kohde.yhtio = '$kukarow[yhtio]' and asiakkaan_kohde.tunnus='$trow[$i]'";
			$aresult = mysql_query($query) or pupe_error($query);
			$arow = mysql_fetch_array($aresult);		
			$ulos = "<td><input type='hidden' name='$nimi' value ='$trow[$i]'>$trow[$i] - $arow[kohde]</td>";

	 		$tyyppi = 2;
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "lisannyt" or
		mysql_field_name($result, $i) == "lisatty" or
		mysql_field_name($result, $i) == "muokannut" or
		mysql_field_name($result, $i) == "muokattu")
	{
		$tyyppi = 2;
	}
	
?>
