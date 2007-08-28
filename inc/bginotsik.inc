<?php
	$ulos  = "11";
  	$ulos .= sprintf ('%010d', str_replace('-','',$yritirow['tilino']));
  	$ulos .= date('ymd');
	$ulos .= 'LEVERANTÖRSBETALNINGAR';
	$ulos .= '      ';
	$ulos .= 'SEK';
	$ulos .= sprintf ('%-18d', ' ');
	fputs($toot,$ulos . "\r\n");
	
	$ulos  = "13";
  	$ulos .= "Faktura      Kundnr             Netto                                         ";
	fputs($toot,$ulos . "\r\n");
?>