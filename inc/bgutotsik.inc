<?php
	//print "<pre>";
	//print "xx/         1         2         3         4         5         6         7         8\n";	
	//print "xx/12345678901234567890123456789012345678901234567890123456789012345678901234567890\n";
	$ulos = "0";
  	$ulos .= sprintf ('%08.8d', $laskurow['masku_tili']);
	$ulos .= date('ymd');
	$ulos .= sprintf ('%22.22s', strtoupper($yhtiorow['nimi']));
	$ulos .= sprintf ('%35.35s', strtoupper($yhtiorow['osoite']. " " . $yhtiorow['postino']. " " . $yhtiorow['postitp']));
	$ulos .= '      '; // 6 tyhj‰‰
	$ulos .= 2;
	$ulos .= ' '; // 1 tyhj‰
	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";
	fputs($toot,$ulos . "\r\n");
?>
