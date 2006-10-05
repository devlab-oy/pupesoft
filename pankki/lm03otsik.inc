<?php
//	print "<pre>";
	$ulos = "LM0300";
  	$ulos .= sprintf ('%014d', $yritystilino);
  	$ulos .= sprintf ('%09d', $yrityytunnus);
	$luonti = getdate();
	$ulos .= sprintf ('%02d', substr($luonti['year'],3,2));
	$ulos .= sprintf ('%02d', $luonti['mon']);
	$ulos .= sprintf ('%02d',  $luonti['mday']);
	$ulos .= sprintf ('%02d',  $luonti['hours']);
	$ulos .= sprintf ('%02d', $luonti['minutes']);
	$ulos .= "2 ";
	$ulos .= sprintf ('%02d', substr($maksupvm,2,2)); //Tämä on eräpvm
    $ulos .= sprintf ('%02d', substr($maksupvm,5,2));				//
    $ulos .= sprintf ('%02d', substr($maksupvm,8,2));			//
	$ulos .= sprintf ('%-35.35s', $yritysnimi);
  	$ulos .= sprintf ('%-35.35s', $luonti['hours'] . $luonti['minutes']);
	$ulos .= sprintf ('%17.17s', ' ');
	$ulos .= "1";
    $ulos .= sprintf ('%88s', ' ');
    $ulos .= "0";
    $ulos .= sprintf ('%-10.10s', $palvelutunnus);
    $ulos .= sprintf ('%66s', ' ');
//	print strlen($ulos) . "/" . $ulos;
//	print "</pre><br>";
	fputs($toot,$ulos . "\n");
?>
