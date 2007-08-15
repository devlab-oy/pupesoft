<?php
	// otetaan sisään $viite muuttuja jossa on laskunnumero
	// palautetaan valmis $viite

	//jos $viite = 123456;
	//vastaus on 12345682 josta 8 on merkkien määrä ja 2 on tarkistusnumero

	// poistetaan kaikki muut merkit paitsi numerot 0-9 sekä poistetaan etunollat
	$viite   = ltrim(ereg_replace("[^0-9]", "", $viite),"0");
	$kerroin = 2;
	
	$maara = strlen($viite)+2;
	
	$viite .= $maara;
	
	//lasketaan tarkista, ja tähän tarvitaan mukaan merkkimäärä numero
	for ($i=1; $i<=strlen($viite); $i++) {
		// oikealta vasemmalle
		$merkki = substr($viite, -1 * $i, 1);
		
		$kala = $merkki*$kerroin;
		
		//jos tulos suurempi kuin 9, esim 16 lasketaan 1+6 eli 7
		if (strlen($kala) > 1) {
			$kala2 = substr($kala,-1);
			$tulo += $kala2;
			$kala2 = substr($kala,0,1);
			$tulo += $kala2;
		}
		else {
			$tulo += $kala;
		}
		
		switch ($kerroin) {
			case 1:
				$kerroin = 2;
				break;
			case 2:
				$kerroin = 1;
				break;
		}
	}
	
	$tulo = ceil(intval($tulo)/10)*10 - $tulo;
	
	$viite .= $tulo;
	
?>