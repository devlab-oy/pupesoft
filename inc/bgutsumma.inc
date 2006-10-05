<?php
	//print "<pre>";
	$ulos = "9";
  	$ulos .= sprintf ('%08.8d', $laskurow['masku_tili']);
	$ulos .= sprintf ('%012d', round($maksulk*100,0));
	$ulos .= sprintf ('%6s',  ' ');
	$ulos .= sprintf ('%4s',  ' ');
	$ulos .= sprintf ('%012d',  0); //Nonsenstotal ????
	$ulos .= sprintf ('%012d', $makskpl);
	$ulos .= sprintf ('%8s',  ' ');
  	$ulos .= sprintf ('%015d', round($makssumma*100,0));
    $ulos .= '  ';
	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";
	fputs($toot,$ulos . "\r\n");
?>
