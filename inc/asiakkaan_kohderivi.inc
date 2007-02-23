<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas") {

		if ($ytunnus != ''){
			echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
			$trow[$i] = $ytunnus;
			
			$tyyppi = 2; 		
		}
		elseif($trow[$i] != "" ) {
			$query = "SELECT nimi FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$trow[$i]' LIMIT 1";
			$aresult = mysql_query($query) or pupe_error($query);
			$arow = mysql_fetch_array($aresult);

			$ulos = "<td><input type='hidden' name='$nimi' value ='$trow[$i]'>$trow[$i] - $arow[nimi]</td>";

	 		$tyyppi = 2;
			$jatko = 0;
		}
	}

	if(mysql_field_name($result, $i) == "kuvaus") {
		$ulos = "<td><textarea cols='33' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;

	}

	
	if (mysql_field_name($result, $i) == "lisannyt" or
		mysql_field_name($result, $i) == "lisatty" or
		mysql_field_name($result, $i) == "muokannut" or
		mysql_field_name($result, $i) == "muokattu")
	{
		$tyyppi = 2;
	}
	
?>
