<?php
//	print "<pre>";
	$ulos = "LM0390";
  	$ulos .= sprintf ('%014.14d', $yritystilino);
  	$ulos .= sprintf ('%09d', $yrityytunnus);
	$ulos .= sprintf ('%02d', substr($luonti['year'],3,2));
	$ulos .= sprintf ('%02d', $luonti['mon']);
	$ulos .= sprintf ('%02d',  $luonti['mday']);
	$ulos .= sprintf ('%06d', $makskpl);
	$ulos .= sprintf ('%013d',  round($makssumma * 100, 0));
	$ulos .= "000000";
	$ulos .= "0000000000000";
	$ulos .= sprintf ('%-6s', ' ');
  	$ulos .= sprintf ('%-35.35s', $luonti['hours'] . $luonti['minutes']);
    $ulos .= sprintf ('%186s', ' ');
//	print strlen($ulos) . "/" . $ulos;
//	print "</pre>";
	fputs($toot,$ulos . "\n");
?>
