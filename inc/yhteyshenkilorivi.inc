<?php
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	
	if($popparista) {
		if (mysql_field_name($result, $i) == "liitostunnus") {
			if ($liitostunnus != ''){
				$trow[$i] = $liitostunnus;
			}
			$tyyppi = 3;
		}
	}
	else {
		if (mysql_field_name($result, $i) == "asiakas") {
			if ($liitostunnus != ''){
				$trow[$i] = $liitostunnus;
			}
			$tyyppi = 3;
		}		
	}

	if (mysql_field_name($result, $i) == "tyyppi") {
		if($trow[$i] == "") {
			if($yhtyyppi == "asiakas") {
				$trow[$i]="A";
			}
			elseif($yhtyyppi == "toimi") {
				$trow[$i]="T";
			}
			$tyyppi=4;
		}
		else {
			$tyyppi=0;
		}
	}
	
?>
