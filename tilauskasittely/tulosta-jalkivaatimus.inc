<?php

// haetaan printterin tiedot
$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
$pres  = mysql_query($query) or pupe_error($query);
$print = mysql_fetch_array($pres);

// haetaan printterille tulostuskomento, käytetään laskuprintteriä (printteri5)
$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$print[printteri5]'";
$pres  = mysql_query($query) or pupe_error($query);
$print = mysql_fetch_array($pres);

$kirjoitin = $print['komento'];
$pvm       = date("j.n.Y");

$sivu  = "__________________________________________________\n\r\n\r";
$sivu .= "J Ä L K I V A A T I M U S  -  $pvm\n\r";
$sivu .= "__________________________________________________\n\r\n\r";
$sivu .= "Asiakas\n\r";
$sivu .= "$row[toim_nimi]\n\r";
$sivu .= "$row[toim_nimitark]\n\r";
$sivu .= "$row[toim_osoite]\n\r";
$sivu .= "$row[toim_postino] $row[toim_postitp]\n\r";
$sivu .= "$toimitustapa\n\r";
$sivu .= "__________________________________________________\n\r\n\r";
$sivu .= "Tilausnumero: ";
foreach($otsikot as $doit) $sivu .= "$doit ";
$sivu .= "\n\r";
$sivu .= " Viitenumero: $viite\n\r";
$sivu .= "  Loppusumma: $summa\n\r";
$sivu .= "__________________________________________________\n\r\n\r";
$sivu .= "Rahtitiedot:\n\r";

//pakkaustiedot
for ($i=0; $i<count($pakkaus); $i++)
{
	$sivu .= "$pakkaus[$i]\n\r";
	$sivu .= "Kilot: $kilot[$i] Kollit: $kollit[$i] Kuutiot: $kuutiot[$i]\n\r";
}

$sivu .= "__________________________________________________\n\r\n\r";
$sivu .= "Vakkoodit:\n\r";

//haetaan tilauksen kaikki vakkoodit
for ($i=0; $i<count($vakit); $i++)
{
	$sivu .= "$vakit[$i]\n\r";
}

$sivu .= "__________________________________________________\n\r".chr(12);

$sivu = escapeshellarg($sivu);
$line = exec(" echo \"$sivu\" | $kirjoitin");

?>