<?php
	if (!function_exists("cobolkikka")) {
		function cobolkikka($numero) {
			$viimeinen = substr((string) $numero, -1 ,1);
			switch ($viimeinen) {
				case 1 :
					$viimeinen = '-';
					break;
				case 2 :
					$viimeinen = 'J';
					break;
				case 3 :
					$viimeinen = 'K';
					break;
				case 4 :
					$viimeinen = 'L';
					break;
				case 5 :
					$viimeinen = 'M';
					break;
				case 6 :
					$viimeinen = 'N';
					break;
				case 7 :
					$viimeinen = 'O';
					break;
				case 8 :
					$viimeinen = 'P';
					break;
				case 9 :
					$viimeinen = 'Q';
					break;
				case 0 :
					$viimeinen = 'R';
			}
			$numero = substr($numero,0,strlen($numero)-1) . $viimeinen;
			return ($numero);
		}
	}						
		
	if ($laskurow['alatila'] == 'K') {
		// maksetaan käteisalennuksella
		$laskusumma = $laskurow['summa'] - $laskurow['kasumma'];
	}
	else {
		$laskusumma = $laskurow['summa'];
	}
	
	$ulklaskusumma=round($laskurow['kurssi']*$laskusumma,2);
			
	$ulos  = "2";
	$ulos .= sprintf ('%-7.7s', $laskurow['ytunnus']);
	$ulos .= sprintf ('%-30.30s', strtoupper($laskurow['nimi']));
	$ulos .= sprintf ('%-35.35s', strtoupper($laskurow['nimitark']));
	$ulos .= '       '; // 7 tyhjää
	fputs($toot,$ulos . "\r\n");
	
	$ulos  = "3";
	$ulos .= sprintf ('%-7.7s', $laskurow['ytunnus']);
	$ulos .= sprintf ('%-30.30s', strtoupper($laskurow['osoite']));
	$ulos .= sprintf ('%-35.35s', strtoupper($laskurow['maa']. "-" . $laskurow['postino'] . " " . $laskurow['postitp']));
	$ulos .= '0';
	$ulos .= sprintf ('%-2.2s', $laskurow['maa']);
	$ulos .= " 010"; // Ei aavistustakaan, mutta näin oli esimerkissä
	fputs($toot,$ulos . "\r\n");

	$ulos  = "4";
	$ulos .= sprintf ('%-7.7s', $laskurow['ytunnus']);
	$ulos .= sprintf ('%-12.12s', strtoupper($laskurow['swift']));
	$ulos .= sprintf ('%-30.30s', $laskurow['ultilno']);
	$ulos .= sprintf ('%-30.30s', ' ');
	fputs($toot,$ulos . "\r\n");

	if ($laskusumma < 0) {
		$ulos  = "5";
		$ulos .= sprintf ('%-7.7s', $laskurow['ytunnus']);
		$ulos .= sprintf ('%-25.25s', $laskurow['viesti'] . " " . $laskurow['tunnus']);
		$ulos .= sprintf ('%011.11d', cobolkikka($ulklaskusumma*100));
		$ulos .= '0000000000';
		$ulos .= sprintf ('%-3.3s', $laskurow['valkoodi']);
		$ulos .= date('ymd');
		$ulos .= '0 ';
		$ulos .= sprintf ('%013.13d', $laskusumma*100);
		$ulos .= ' 0';
		fputs($toot,$ulos . "\r\n");
	}

	if ($laskusumma > 0) {
		$ulos  = "6";
		$ulos .= sprintf ('%-7.7s', $laskurow['ytunnus']);
		$ulos .= sprintf ('%-25.25s', $laskurow['viesti'] . " " . $laskurow['tunnus']);
		$ulos .= sprintf ('%011.11d', $ulklaskusumma*100);
		$ulos .= '0000000000';
		$ulos .= sprintf ('%-3.3s', $laskurow['valkoodi']);
		$ulos .= date('ymd');
		$ulos .= '0 ';
		$ulos .= sprintf ('%013.13d', $laskusumma*100);
		$ulos .= ' 0';
		fputs($toot,$ulos . "\r\n");
	}	
?>