<?php
	$ulos  = "0";
  	$ulos .= sprintf ('%08.8d', $laskurow['masku_tili']);
	$ulos .= date('ymd');
	$ulos .= sprintf ('%22.22s', strtoupper($yhtiorow['nimi']));
	$ulos .= sprintf ('%35.35s', strtoupper($yhtiorow['osoite']. " " . $yhtiorow['postino']. " " . $yhtiorow['postitp']));
	$ulos .= '      '; // 6 tyhjהה
	$ulos .= 2;
	$ulos .= ' '; // 1 tyhjה
	fputs($toot,$ulos . "\r\n");
?>