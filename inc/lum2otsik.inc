<?php
	
	//print "<pre>";
	
	$ulos = "LUM200 ";
  	$ulos .= sprintf ('%011.11s', $ytunnus);		//ytunnus
	$ulos .= sprintf ('%5.5s', $lum_et_5kentta);		//asiakastunnus
	$ulos .= "   ";
	$luonti = getdate();

	$ulos .= sprintf ('%02d', substr($luonti['year'],3,2));
	$ulos .= sprintf ('%02d', $luonti['mon']);
	$ulos .= sprintf ('%02d', $luonti['mday']);
	$ulos .= sprintf ('%02d', $luonti['hours']);
	$ulos .= sprintf ('%02d', $luonti['minutes']);
	
	//Nordean speksi
	$ulos .= sprintf ('%14s', ' ');
	$ulos .= sprintf ('%04d', $luonti['year']);		//Tämä on eräpvm, jonka voi ohittaa laskulla
	$ulos .= sprintf ('%02d', $luonti['mon']);		//
	$ulos .= sprintf ('%02d', $luonti['mday']);		//
	$ulos .= sprintf ('%542s', ' ');

	/*
	Tuomaksen versio
	//$ulos .= sprintf ('%14s', ' ');	
	$ulos .= sprintf ('%04d', $luonti['year']);		//Tämä on eräpvm, jonka voi ohittaa laskulla
	$ulos .= sprintf ('%02d', $luonti['mon']);		//
	$ulos .= sprintf ('%02d',  $luonti['mday']);	//
	$ulos .= sprintf ('%556s', ' ');				//varalla
	*/

	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";

	fputs($toot,$ulos . "\r\n");
?>
