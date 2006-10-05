<?php

$tulo    = 0;

// sallitaan vain numerot 0-9
$ytunnus = ereg_replace("[^0-9]","",$ytunnus);

// tehdään kaheksen merkkiä pitkäksi
$ytunnus = sprintf("%08.8s",$ytunnus);

// loopataan ytunnus
for ($ytunnusi=0; $ytunnusi<7; $ytunnusi++) {
	$merkki = substr($ytunnus, $ytunnusi, 1);
	switch ($ytunnusi) {
		case 0:
			$kerroin = 7;
			break;
		case 1:
			$kerroin = 9;
			break;
		case 2:
			$kerroin = 10;
			break;
		case 3:
			$kerroin = 5;
			break;
		case 4:
			$kerroin = 8;
			break;
		case 5:
			$kerroin = 4;
			break;
		case 6:
			$kerroin = 2;
			break;
	}
	$tulo += $kerroin * $merkki;
}

// otetaan tarkastusmerkki
$tmerkki = substr($ytunnus, -1);

// summasta mod 11
$tulo = $tulo % 11;

if ($tulo <> 0) {
	$tulo = 11 - $tulo;
}

// oliko tarkastusmerkki ok
if ($tulo == $tmerkki) {
	$ok = 1;
	$ytunnus = (int) $ytunnus; // etunollat vielä pois
}
else {
	$ok = 0;
}

?>
