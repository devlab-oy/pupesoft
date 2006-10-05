<?php
//	print "<pre>";
	$ulos = "LM03";
	$ulos .= $laskutapahtuma;
  	$ulos .= sprintf ('%-14.14s', $yritystilino);
	$ulos .= sprintf ('%-30.30s', $laskunimi1);
	$ulos .= sprintf ('%-20.20s', $laskunimi2);
	$ulos .= sprintf ('%-20.20s', $laskunimi3);
	$ulos .= sprintf ('%-14.14s', $laskutilno);
	$ulos .= "   ";
	$ulos .= $laskutyyppi;
        $ulos .= sprintf ('%-70.70s', $laskuviesti);
	$ulos .=  "  ";
	$ulos .= sprintf ('%02d', substr($luonti['year'],3,2));
        $ulos .= sprintf ('%02d', $luonti['mon']);
        $ulos .= sprintf ('%02d', $luonti['mday']);
	$ulos .= sprintf ('%012d', round($laskusumma * 100, 0));
	$ulos .= sprintf ('%17.17s', ' '); 
	$ulos .= sprintf ('%-20.20s', $laskusis1);
	$ulos .= sprintf ('%-20.20s', $laskusis2); 
	$ulos .= sprintf ('%45s', ' ');
//	print strlen($ulos) . "/" . $ulos;
//	print "</pre>";
	fputs($toot,$ulos . "\r");
?>
