<?php

if ($phpnimi == "rahtikirja_custom.php") {
	$rakir_row = $osoitelappurow;
}

$tulostusnimi = $rakir_row['toim_nimi']." ".$rakir_row['toim_nimitark'];

// rahtikirjanumero on ensimmäisen tilauksen tilausnumero...
$rahtikirjanro = $lotsikot[0];

$query = "	SELECT GROUP_CONCAT(distinct if(viesti!='',viesti,NULL) separator '. ') viesti
			from rahtikirjat use index (otsikko_index)
			where yhtio			= '$kukarow[yhtio]'
			and rahtikirjanro	= '$rahtikirjanro'";
$viestirar = mysql_query($query) or pupe_error($query);

$viestirarrow = mysql_fetch_array($viestirar);


$sivu = "
 
         ".sprintf("%-25.s",$tulostusnimi)." $rakir_row[rahtisopimus]
         ".sprintf("%-36.s",$rakir_row['toim_osoite'])." $aputeksti
         ".sprintf("%-36.s",$rakir_row['toim_postino']." ".$rakir_row['toim_postitp'])."
      
                                        $kollityht  $kilotyht    $kuutiotyht
      
                 $viestirarrow[viesti]
                                
      
".chr(12);

$from = array ('ä','å','ö','Ä','Å','Ö','|');

if ($merkisto == 1) {
	$to	= array ('{','}','|','[',']','\\',chr(179));								// 7 bittiset
}
elseif ($merkisto == 2) {
	$to	= array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153),chr(179));	// DOS charset
}
elseif ($merkisto == 3) {
	$to	= array (chr(228),chr(229),chr(246),chr(196),chr(197),chr(214),chr(124));	// ANSI charset
}
else {
	$to	= $from;
}

$sivu = str_replace($from, $to, $sivu);	// Tehdään käännös

echo "<pre>$sivu</pre><br>";


//$sivu = escapeshellarg($sivu);
//$line = exec(" echo \"$sivu\" | $kirjoitin");

?>