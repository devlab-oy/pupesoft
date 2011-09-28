<?php

	// onko kyseessä väliviivallinen tilino, jos on poistetaan se...
	$tarkiste = 0;

	// katsotaan mikä pankki
	$pankki = substr($pankkitili,0,1);

	// poistetaan väliviiva
	$pankkitili = str_replace("-", "", $pankkitili);

	// jos tilinumero on liian lyhyt
	if (strlen($pankkitili != 14)) {
 		// Pankit 4 ja 5 hoitavat tämän näin
		if ($pankki == "4" or $pankki == "5") {
			$alku = substr($pankkitili,0,7);
			$nollat = substr("000000000",0, 14 - strlen($pankkitili));
			$loppu = substr($pankkitili,7);
		}
		else {
			$alku = substr($pankkitili,0,6);
			$nollat = substr("000000000",0, 14 - strlen($pankkitili));
			$loppu = substr($pankkitili,6);
		}
		$pankkitili = $alku . $nollat. $loppu;
	}
	//echo "Pankkiili = '$pankkitili' Pituus " . strlen($pankkitili) . "<br>";

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

 	//echo "laskettu tarkiste: 10 - ".substr($tarkiste, -1)." = ";
	$tarkiste = 10 - substr($tarkiste, -1); // Viimeinen merkki
	//echo "$tarkiste<br>";

	if ($tarkiste == 10) {
		$tarkiste = 0;
	}

 	//echo "tilinumeron tarkiste: ".substr($pankkitili, 13, 1)."<br>";

	if (substr($pankkitili, 13, 1) != $tarkiste) {
		//echo "Tarkiste on väärin $tarkiste<br>";
		$pankkitili = "";
	}
    //echo "Pankkiili = '$pankkitili' Pituus " . strlen($pankkitili) . "<br>";

?>
