<?php

	//print "<pre>";

	/*
	Kentt‰	Tieto					Pakollisuus	Muoto	Pituus	E/P	Sis‰ltˆ
	-------------------------------------------------------------------------------------------
	1		Sovellustunnus			* 			AN 		4 		EP 	LUM2 Palautteella VLU2
	2		Tietuetunnus 			* 			N 		1 		EP 	9
	3		Aineistolaji 			* 			N 		1 		EP 	0 tai 9 = palaute
	4		Varalla  							AN 		1 		EP
	5		Maksajan Y-tunnus 		* 			AN 		11 		EP
	6		Asiakastunnuksen lis‰osa  			AN 		5 		EP
	7		Varalla  							AN 		2 		EP
	8		Hyv‰ksymiskoodi  					N 		1 		P 	0 = hyv‰ksytty 4 = hyl‰tty
	9		Maksujen lukum‰‰r‰ 		* 			N 		5 		EP
	10		Laskujen lukum‰‰r‰  				N 		5 		EP 	Jos on laskutietueita
	11		Maksujen valuuttam‰‰r‰ 	* 			N 		15 		EP 	13 kok.+ 2 desim.
	12		Varalla  							AN 		549 	EP
	-------------------------------------------------------------------------------------------
			Yhteens‰									600
	*/

	$ulos  = "LUM2";
	$ulos .= "9";
	$ulos .= "0";
	$ulos .= sprintf ('%1s', 		' ');
  	$ulos .= sprintf ('%011.11s',	$ytunnus);
	$ulos .= sprintf ('%5.5s',		$lum_st_5kentta);	//asiakastunnus
	$ulos .= sprintf ('%2s', 		' ');
	$ulos .= sprintf ('%1s', 		' ');
	$ulos .= sprintf ('%05d', 		$makskpl);
	$ulos .= sprintf ('%5s', 		' ');
	$ulos .= sprintf ('%015d',  	round($makssumma * 100, 0));
	$ulos .= sprintf ('%549s', 		' ');

	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";

	fputs($toot,$ulos . "\r\n");
?>
