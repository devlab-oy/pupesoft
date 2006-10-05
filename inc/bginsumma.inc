<?php
	print "<pre>";
	$ulos = "29";
  	$ulos .= sprintf ('%010d', $yritirow['tilino']);
  	$ulos .= sprintf ('%08d', $makskpl);
	$ulos .= sprintf ('%012d',  round($makssumma * 100, 0));
	$ulos .= ' ';
	$ulos .= sprintf ('%-47s', ' ');
	print strlen($ulos) . "/" . $ulos;
	print "</pre>";
	fputs($toot,$ulos . "\r\n");
?>
