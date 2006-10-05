<?php
// Onko kyseessä väliviivallinen tilino, jos on poistetaan se...
	$tarkiste = 0;
	$pankki = substr($pankkitili,0,1);
	$pos = strrpos($pankkitili, "-");
	while(!($pos === false)) { // note: three equal signs
		// Väliviiva pois....
		$pankkitili = substr($pankkitili,0,$pos) . substr($pankkitili,$pos+1);
		$pos = strrpos($pankkitili, "-");
		//echo "Viivanpoisto: '$pankkitili'<br>";
	}
	if (strlen($pankkitili != 14)) {
		if (($pankki == "4") or ($pankki == "5")) { // Pankit 4 ja 5 hoitavat tämän näin
		//	echo "Tapaus 4 ja 5<br>";
			$alku = substr($pankkitili,0,7);
			$nollat = substr("000000000",0, 14 - strlen($pankkitili)); 
			$loppu = substr($pankkitili,7);
		}
		else { // ja muut
			$alku = substr($pankkitili,0,6);
			$nollat = substr("000000000",0, 14 - strlen($pankkitili));
			$loppu = substr($pankkitili,6);
		}
		$pankkitili = $alku . $nollat. $loppu;
	}
//	echo "Pankkiili = '$pankkitili' Pituus " . strlen($pankkitili) . "<br>";
// Tarkistussumman laskenta
	for ($ip=0;  $ip<13; $ip++) {
		if (($ip % 2) == 0) { // hmm, tilinumero on aina 14 pitkä, joten eka kerroin on aina 2
			$kerroin = 2;
		}
		else {
			$kerroin = 1;
		}
		$tulo = $kerroin * substr($pankkitili, $ip, 1);
		if ($tulo > 9) { // jos > 10, lasketaan numerot yhteen....
			$tulo = substr($tulo, 0, 1) + substr($tulo, 1, 1);
		}
		$tarkiste += $tulo;
//		echo "$kerroin " . substr($pankkitili, $i, 1) . " --> $tulo --> $tarkiste <br>";
	}

	$tarkiste = 10 - substr($tarkiste, -1); // Viimeinen merkki
	if ($tarkiste == 10) {
		$tarkiste = 0;
	}
	if (substr($pankkitili, 13, 1) != $tarkiste) {
		$pankkitili = "";		
	}
//	echo "$pankkitili";
?>
