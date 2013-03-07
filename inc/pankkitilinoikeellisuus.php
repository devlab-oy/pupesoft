<?php

	// onko kyseessä väliviivallinen tilino, jos on poistetaan se...
	$tarkiste = 0;

	// katsotaan mikä pankki
	$pankki = substr($pankkitili,0,1);

	// poistetaan väliviiva
	$pankkitili = preg_replace("/[^0-9]/", "", $pankkitili);

	// Sisäisen pankin tilejä ei tsekata
	if ($pankki == 9) {
		$pankkitili = $pankkitili;
	}
	else {
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
		}

		$tarkiste = 10 - substr($tarkiste, -1); // Viimeinen merkki

		if ($tarkiste == 10) {
			$tarkiste = 0;
		}

		if (substr($pankkitili, 13, 1) == '' or substr($pankkitili, 13, 1) != $tarkiste) {
			#echo "Tarkiste on väärin $tarkiste<br>";
			$pankkitili = "";
		}
	}