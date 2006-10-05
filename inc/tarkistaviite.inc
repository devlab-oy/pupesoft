<?php
	// otetaan sisään $viite muuttuja
	// palautetaan $ok muuttujassa numero 1 jos viite on ok
	// sallitaan viitteessä vaan numerot 0-9

	$viite  = ereg_replace("[^0-9]","",$viite);
	$kerroin = 7;
	$tulo    = 0;

	for ($i=2; $i<=strlen($viite); $i++) {
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
	$tmerkki = substr($viite, -1);
	$tulo = substr($tulo, -1) + 0;

	if ($tulo != 0) {
		$tulo = 10 - $tulo;
	}

	$ok = 0;
	if ($tulo == $tmerkki) {
		$ok = 1;
	}
?>