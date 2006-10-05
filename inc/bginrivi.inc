<?php
	print "<pre>";
	if ($laskurow['alatila'] == 'K') { // maksetaan käteisalennuksella
		$laskusumma = $laskurow['summa'] - $laskurow['kasumma'];
	}
	else {
		$laskusumma = $laskurow['summa'];
	}
	
	if ($laskusumma > 0) $ulos = "14";
 	else {
 		$ulos = "17";
 		$laskusumma = $laskusumma * -1;
 	}
 	$laskurow['tilinumero'] = str_replace('-','',$laskurow['tilinumero']);
 	$ulos .= sprintf ('%010d', $laskurow['tilinumero']);
	$ulos .= sprintf ('%-25.25s', $laskurow['viite'] . ' ' . $laskurow['viesti']);
	$ulos .= sprintf ('%012d', round($laskusumma*100,2));
	$ulos .= sprintf ('%02d', substr($pvmrow['olmapvm'],2,2)); //Tämä on eräpvm
    $ulos .= sprintf ('%02d', substr($pvmrow['olmapvm'],5,2)); //
    $ulos .= sprintf ('%02d', substr($pvmrow['olmapvm'],8,2)); //
	//$ulos .= '      ';
	$ulos .= sprintf ('%-5s', ' ');
	$ulos .= sprintf ('%-20.20s', $laskurow['tunnus']);	  
	print strlen($ulos) . "/" . $ulos;
	print "</pre>";
	fputs($toot,$ulos . "\r\n");
?>
