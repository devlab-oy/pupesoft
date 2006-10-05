<?php
	// otetaan sisההn $viite muuttuja jossa on laskunnumero
	// palautetaan valmis $viite

	// poistetaan kaikki muut merkit paitsi numerot 0-9 sekה poistetaan etunollat
	$viite   = ltrim(ereg_replace("[^0-9]", "", $viite),"0");
	$kerroin = 7;
	$tulo    = 0;

	for ($i=1; $i<=strlen($viite); $i++) {
		$merkki = substr($viite, -1 * $i, 1);
		$tulo += $kerroin * $merkki;
		switch ($kerroin) {
			case 7:
				$kerroin = 3;
				break;
			case 3:
				$kerroin = 1;
				break;
			case 1:
				$kerroin = 7;
				break;
		}
	}

	$tulo = substr($tulo, -1) + 0;

	if ($tulo != 0) {
		$tulo = 10 - $tulo;
	}

	$viite .= $tulo;
?>