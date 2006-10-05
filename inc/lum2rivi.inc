<?php
//	print "<pre>";
	$ulos = "LUM210";
	$ulos .= sprintf ('%-35.35s', $laskunimi1);
	$ulos .= sprintf ('%-35.35s', $laskunimi2);
	$ulos .= sprintf ('%-35.35s', $laskunimi3);
	$ulos .= sprintf ('%-35.35s', $laskunimi4);
	$ulos .= sprintf ('%-2.2s', $laskumaakoodi);
	$ulos .= sprintf ('%-11.11s', $laskuswift);
	$ulos .= sprintf ('%-35.35s', $laskupankki1);
	$ulos .= sprintf ('%-35.35s', $laskupankki2);
	$ulos .= sprintf ('%-35.35s', $laskupankki3);
	$ulos .= sprintf ('%-35.35s', $laskupankki4);
	$ulos .= sprintf ('%-34.34s', $laskutilino);
	$ulos .= sprintf ('%-140.140s', $laskuaihe);
	$ulos .= sprintf ('%15.15s', round($laskusumma * 100, 0));
	$ulos .= sprintf ('%-3.3s', $laskuvaluutta);
	$ulos .= "C";
	$ulos .= sprintf ('%16s', ' ');
	$ulos .= "M";
	$ulos .= "J";
	$ulos .= sprintf ('%34s', ' '); //Tämän alussa voi olla eräpvm muodossa ppkkvvvv
	$ulos .= sprintf ('%14.14s', $yritystilino);
	$ulos .= "EUR";	
	$ulos .= sprintf ('%39s', ' ');
//	print strlen($ulos) . "/" . $ulos;
//	print "</pre>";
	fputs($toot,$ulos . "\r");
?>
