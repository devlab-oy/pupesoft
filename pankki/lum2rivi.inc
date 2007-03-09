<?php

	//	print "<pre>";
	
	$ulos = "LUM210";
	$ulos .= sprintf ('%-35.35s', $laskunimi1);
	$ulos .= sprintf ('%-35.35s', $laskunimi2);
	$ulos .= sprintf ('%-35.35s', $laskunimi3);
	$ulos .= sprintf ('%-35.35s', $laskunimi4);
	$ulos .= sprintf ('%-2.2s',   strtoupper($laskumaakoodi));
	$ulos .= sprintf ('%-11.11s', strtoupper($laskuswift));
	$ulos .= sprintf ('%-35.35s', $laskupankki1);
	$ulos .= sprintf ('%-35.35s', $laskupankki2);
	$ulos .= sprintf ('%-35.35s', $laskupankki3);
	$ulos .= sprintf ('%-35.35s', $laskupankki4);
	$ulos .= sprintf ('%-34.34s', strtoupper($laskutilino));
	$ulos .= sprintf ('%-140.140s', $laskuaihe);
	$ulos .= sprintf ('%015d', round($laskusumma * 100, 0));
	$ulos .= sprintf ('%-3.3s', strtoupper($laskuvaluutta));
	$ulos .= sprintf ('%3s','');					//Sampo vaatii tyhj‰‰!! ??? (OKO vaatinee 3 nollaa??, miten hoidetaan)
	$ulos .= sprintf ('%14s','');					//Sampo vaatii tyhj‰‰!!  OKO vaatii nollia??? kurssisopimusnumero
	$ulos .= sprintf ('%-1.1s', $lum_maksutapa);	//EUmaksu OKO ei oikein tunnu handlaavan n‰it‰ EU maksuja joten ne on parempi hoitaa jo t‰‰ll‰
	$ulos .= "J";									//oletus: saajalta v‰hennet‰‰n kulut kotimaassa
	$ulos .= sprintf ('%034d', '0'); 				//T‰m‰n alussa voi olla er‰pvm muodossa ppkkvvvv
	$ulos .= sprintf ('%14.14s', $yritystilino);	//veloitustili
	$ulos .= sprintf ('%-3.3s', strtoupper($laskuyritivaluutta));	
	$ulos .= sprintf ('%015d', '0');
	$ulos .= sprintf ('%20s', ' ');
	$ulos .= "1";									//valuuttakoodi
	$ulos .= "   ";									//varalla

	//print strlen($ulos) . "/" . $ulos;
	//print "</pre>";

	fputs($toot,$ulos . "\r\n");
?>
