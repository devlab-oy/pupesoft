<?php

	//print "<pre>";

	$ulos = "LUM290 ";
  	$ulos .= sprintf ('%011.11s', $yrityytunnus);
	$ulos .= sprintf ('%5.5s', $lum_st_5kentta);	//asiakastunnus
	$ulos .= "  ";	
	$ulos .= "0";
	$ulos .= sprintf ('%05d', $makskpl);
	$ulos .= sprintf ('%05d', '0');
	$ulos .= sprintf ('%015d', round($makssumma * 100, 0));
    $ulos .= sprintf ('%549s', ' ');
	
	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";

	fputs($toot,$ulos . "\r\n");
?>
