<?php
//	print "<pre>";
	$ulos = "LUM290  ";
  	$ulos .= sprintf ('%-11.11s', $yrityytunnus);
	$ulos .= "  ";
	$ulos .= "     ";
	$ulos .= " ";
	$ulos .= sprintf ('%05d', $makskpl);
	$ulos .= "     ";
	$ulos .= sprintf ('%15.15s',  round($makssumma * 100, 0));
    $ulos .= sprintf ('%548s', ' ');
//	print strlen($ulos) . "/" . $ulos;
//	print "</pre>";
	fputs($toot,$ulos . "\r");
?>
