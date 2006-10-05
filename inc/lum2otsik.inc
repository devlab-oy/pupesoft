<?php
//	print "<pre>";
	$ulos = "LUM201 ";
  	$ulos .= sprintf ('%-16.16s', $yrityytunnus);
	$ulos .= "   ";
	$luonti = getdate();
	$ulos .= sprintf ('%02d', substr($luonti['year'],3,2));
	$ulos .= sprintf ('%02d', $luonti['mon']);
	$ulos .= sprintf ('%02d',  $luonti['mday']);
	$ulos .= sprintf ('%02d',  $luonti['hours']);
	$ulos .= sprintf ('%02d', $luonti['minutes']);
	$ulos .= sprintf ('%14s', ' ');
	$ulos .= sprintf ('%04d', $luonti['year']);		//Tämä on eräpvm, jonka voi ohittaa laskulla
	$ulos .= sprintf ('%02d', $luonti['mon']);		//
	$ulos .= sprintf ('%02d',  $luonti['mday']);	//
	$ulos .= sprintf ('%542s', ' ');
//	print strlen($ulos) . "/" . $ulos;
//	print "</pre>";
	fputs($toot,$ulos . "\r");
?>
