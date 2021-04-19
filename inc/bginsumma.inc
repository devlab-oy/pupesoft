<?php
	$ulos  = "29";
  	$ulos .= sprintf ('%010d', str_replace('-','',$yritirow['tilino']));
  	$ulos .= sprintf ('%08d', $makskpl);
	$ulos .= sprintf ('%012d',  round($makssumma * 100, 0));
	$ulos .= ' ';
	$ulos .= sprintf ('%-47s', ' ');
	fputs($toot,$ulos . "\r\n");
?>