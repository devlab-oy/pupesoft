<?php
	
	//print "<pre>";
	
	$ulos = "LM0300";
  	$ulos .= sprintf ('%-14.14s', $yritystilino);
  	$ulos .= sprintf ('%09d', $yrityytunnus);
	$luonti = getdate();
	$ulos .= sprintf ('%02d', substr($luonti['year'],3,2));
	$ulos .= sprintf ('%02d', $luonti['mon']);
	$ulos .= sprintf ('%02d', $luonti['mday']);
	$ulos .= sprintf ('%02d', $luonti['hours']);
	$ulos .= sprintf ('%02d', $luonti['minutes']);
	$ulos .= $rahalaitos;
	$ulos .= sprintf ('%02d', substr($maksupvm,2,2)); 	//Tämä on eräpvm
    $ulos .= sprintf ('%02d', substr($maksupvm,5,2));
    $ulos .= sprintf ('%02d', substr($maksupvm,8,2));
	$ulos .= sprintf ('%-35.35s', $yritysnimi);
  	$ulos .= sprintf ('%-35.35s', $luonti['hours'] . $luonti['minutes']);
	$ulos .= sprintf ('%17.17s', ' ');
	$ulos .= "1";
    $ulos .= sprintf ('%165s', ' ');

	//print strlen($ulos) . "/" . $ulos;
	//print "</pre><br>";

	fputs($toot,$ulos . "\r\n");
?>
