<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tilinro") {
		if ($trow[$i] == '') {
			$pankkitili = '999123';

			$query = "SELECT count(*) FROM TAMK_pankkitili";
			$vresult = mysql_query($query) or pupe_error($query);
			$tulos = mysql_fetch_array($vresult);

			$pankkitili .= sprintf('%05d', $tulos[0]);
			$pankkitili .= sprintf('%02d', date('s'));

			for ($ip=0;  $ip<13; $ip++) {
				// hmm, tilinumero on aina 14 pitkä, joten eka kerroin on aina 2
				if ($ip % 2 == 0) {
					$kerroin = 2;
				}
				else {
					$kerroin = 1;
				}
				$tulo = $kerroin * substr($pankkitili, $ip, 1);
		 		// jos > 10, lasketaan numerot yhteen....
				if ($tulo > 9) {
					$tulo = substr($tulo, 0, 1) + substr($tulo, 1, 1);
				}
				$tarkiste += $tulo;
				//echo "$kerroin " . substr($pankkitili, $ip, 1) . " --> $tulo --> $tarkiste <br>";
			}
			$tarkiste = 10 - substr($tarkiste, -1);
			if ($tarkiste == 10) {
				$tarkiste = 0;
			}
			$pankkitili .= $tarkiste;

			$ulos = "<td><input type='hidden' name='$nimi' id='$nimi' value='$pankkitili'>$pankkitili</td>";
			$jatko = 0;
		}
		else $tyyppi = 2; 
	}
?>
