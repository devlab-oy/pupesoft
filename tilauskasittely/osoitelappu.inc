<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada lähettäjän tiedot

$query="select * from lasku where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
$oslre=mysql_query($query) or pupe_error($query);
$oslro=mysql_fetch_array($oslre);


$sivu  = "__________________________________________________\n\r\n\r";
$sivu .= "Tilaus $tunnus\n\r";
$sivu .= "__________________________________________________\n\r";
$sivu .= sprintf("%-24.s", "Lähettäjä")."| Vastaanottaja:\n\r";
$sivu .= "                        |\n\r";
$sivu .= sprintf("%-24.s",$yhtiorow['nimi'])."| $oslro[toim_nimi]\n\r";
$sivu .= "                        | $oslro[toim_nimitark]\n\r";
$sivu .= sprintf("%-24.s",$yhtiorow['osoite'])."| $oslro[toim_osoite]\n\r";
$sivu .= sprintf("%-5.s",$yhtiorow['postino'])." ".sprintf("%-18.s",$yhtiorow['postitp'])."| $oslro[toim_postino] $oslro[toim_postitp]\n\r";
$sivu .= "                        |\n\r";
$sivu .= sprintf("%-24.s",$oslro['toimitustapa'])."|\n\r";
$sivu .= sprintf("%-40.40s",$oslro['viesti'])."\n\r";
$sivu .= "________________________|_________________________\n\r";
$sivu .= sprintf("%-24.s", "Vakuutus")."| Jälkivaatimus\n\r";
$sivu .= "                        |\n\r";
$sivu .= "________________________|_________________________\n\r";
$sivu .= sprintf("%-24.s", "Kollit")."|\n\r";
$sivu .= "                        |\n\r";
$sivu .= "________________________|_________________________\n\r";
$sivu .= "\n\r";
$sivu .= "\n\r";
$sivu .= "\n\r";

//meneekö nyt paremmin?
//$sivu .= "\n\r";

$sivu .= "\n\r";

$from = array ('ä','å','ö','Ä','Å','Ö','|');
$to   = array ('{','}','|','[',']','\\',chr(179)); 								// 7 bittiset
$to   = array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153),chr(179)); // DOS charset
//$to   = array (chr(228),chr(229),chr(246),chr(196),chr(197),chr(214),chr(124)); // ANSI charset
$sivu = str_replace($from, $to, $sivu);											// Tehdään käännös

//kirjoitetaan  faili levylle..
$file   = "/tmp/Osoitelappu-".md5(uniqid(rand(),true)).".txt";

if (!$handle = fopen($file, "w"))
	die("VIRHE: Tiedoston luonti epäonnistui!");

if (fwrite($handle, $sivu) === FALSE)
	die("VIRHE: Tiedoston kirjoitus epäonnistui!");

fclose($handle);

$line = exec("$oslapp $file");

//poistetaan tiedosto
shell_exec("rm -f $file");

?>
