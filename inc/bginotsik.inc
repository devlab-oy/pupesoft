<?php
	print "<pre>";
	$ulos = "11";
	$yritirow['tilino'] = str_replace('-','',$yritirow['tilino']);
  	$ulos .= sprintf ('%010d', $yritirow['tilino']);
  	$ulos .= date('ymd');
	$ulos .= 'LEVERANTÖRSBETALNINGAR';
	//$ulos .= sprintf ('%02d', substr($pvmrow['olmapvm'],2,2)); //Tämä on eräpvm
    //$ulos .= sprintf ('%02d', substr($pvmrow['olmapvm'],5,2)); //
    //$ulos .= sprintf ('%02d', substr($pvmrow['olmapvm'],8,2)); //
	//$ulos .= sprintf ('%-13d', ' ');
	$ulos .= '      ';
	$ulos .= 'SEK';
	$ulos .= sprintf ('%-18d', ' ');
	print strlen($ulos) . "/" . $ulos;
	print "</pre><br>";
	fputs($toot,$ulos . "\r\n");
	print "<pre>";

	$ulos = "13";
  	$ulos .= "Faktura      Kundnr             Netto                                         ";
	//Kopioin tämän suoraan esimerkkifilestä, koska en tätä ymärtänyt
	print strlen($ulos) . "/" . $ulos;
	print "</pre><br>";
	fputs($toot,$ulos . "\r\n");
?>
