<?php

	//print "<pre>";

	$ulos = "LUM290 ";
  	$ulos .= sprintf ('%011.11s', $ytunnus);
	$ulos .= sprintf ('%5.5s', $lum_st_5kentta);	//asiakastunnus
	$ulos .= "  ";	
	$ulos .= " ";
	$ulos .= sprintf ('%05d', $makskpl);
	$ulos .= "     ";
	$ulos .= sprintf ('%015d',  round($makssumma * 100, 0));
	$ulos .= sprintf ('%549s', ' ');
	
	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";

	fputs($toot,$ulos . "\r\n");
?>
