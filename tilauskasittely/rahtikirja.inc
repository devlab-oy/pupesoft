<?php

// rahtikirjanumero on ensimmäisen tilauksen tilausnumero...
$rahtikirjanro = $lotsikot[0];

$sivu = "                              ".sprintf("%-11.s",$toitarow["sopimusnro"])." $pvm
         $yhtiorow[nimi]
         ".sprintf("%-25.s",$yhtiorow['osoite'])."           $rahtikirjanro
         $yhtiorow[postino] ".sprintf("%-36.s",$yhtiorow['postitp'])."  $toitarow[sopimusnro]
         Puh. $yhtiorow[puhelin]
      
                               $rakir_row[ytunnus]
                                              $toimitustapa
         ".sprintf("%-25.s",$rakir_row['toim_nimi'])." $rahsoprow[rahtisopimus]
         ".sprintf("%-36.s",$rakir_row['toim_osoite'])." $aputeksti
         ".sprintf("%-36.s",$rakir_row['toim_postino']." ".$rakir_row['toim_postitp'])."
      
      
      
      
      
      
      
      
      
                                              $rahdinmaksaja
         $rakir_row[toim_postino] $rakir_row[toim_postitp]
      
      
      
         ".sprintf("%-10.s",$lotsikot[0])."     ".sprintf("%10.s",$kollit[0])." ".sprintf("%-10.s",$pakkaus[0])."              ".sprintf("%10.s",$kilot[0]).sprintf("%10.s",$kuutiot[0])."
         ".sprintf("%-10.s",$lotsikot[1])."     ".sprintf("%10.s",$kollit[1])." ".sprintf("%-10.s",$pakkaus[1])."              ".sprintf("%10.s",$kilot[1]).sprintf("%10.s",$kuutiot[1])."
         ".sprintf("%-10.s",$lotsikot[2])."     ".sprintf("%10.s",$kollit[2])." ".sprintf("%-10.s",$pakkaus[2])."              ".sprintf("%10.s",$kilot[2]).sprintf("%10.s",$kuutiot[2])."
         ".sprintf("%-10.s",$lotsikot[3])."     ".sprintf("%10.s",$kollit[3])." ".sprintf("%-10.s",$pakkaus[3])."              ".sprintf("%10.s",$kilot[3]).sprintf("%10.s",$kuutiot[3])."
         ".sprintf("%-10.s",$lotsikot[4])."     ".sprintf("%10.s",$kollit[4])." ".sprintf("%-10.s",$pakkaus[4])."              ".sprintf("%10.s",$kilot[4]).sprintf("%10.s",$kuutiot[4])."
         ".sprintf("%-10.s",$lotsikot[5])."     ".sprintf("%10.s",$kollit[5])." ".sprintf("%-10.s",$pakkaus[5])."              ".sprintf("%10.s",$kilot[5]).sprintf("%10.s",$kuutiot[5])."
         ".sprintf("%-10.s",$lotsikot[6])."     ".sprintf("%10.s",$kollit[6])." ".sprintf("%-10.s",$pakkaus[6])."              ".sprintf("%10.s",$kilot[6]).sprintf("%10.s",$kuutiot[6])."
         ".sprintf("%-10.s",$lotsikot[7])."     ".sprintf("%10.s",$kollit[7])." ".sprintf("%-10.s",$pakkaus[7])."              ".sprintf("%10.s",$kilot[7]).sprintf("%10.s",$kuutiot[7])."
         ".sprintf("%-10.s",$lotsikot[8])."     ".sprintf("%10.s",$kollit[8])." ".sprintf("%-10.s",$pakkaus[8])."              ".sprintf("%10.s",$kilot[8]).sprintf("%10.s",$kuutiot[8])."
         ".sprintf("%-10.s",$lotsikot[9])."     ".sprintf("%10.s",$kollit[9])." ".sprintf("%-10.s",$pakkaus[9])."              ".sprintf("%10.s",$kilot[9]).sprintf("%10.s",$kuutiot[9])."
      
                            $kollityht                                  $kilotyht
      
         ".sprintf("%-30.s",$vakit[0])."                         $viite
         ".sprintf("%-30.s",$vakit[1])."
         ".sprintf("%-30.s",$vakit[2])."                         $yhtiorow[pankkitili1_eitulosteta]
         ".sprintf("%-30.s",$vakit[3])."
         ".sprintf("%-30.s",$vakit[4])."                         $yhteensa
         ".sprintf("%-30.s",$vakit[5])."                         $summa_eitulosteta
         ".sprintf("%-30.s",$vakit[6])."                         $rahinta_eitulosteta
      
                                                                $jvhinta_eitulosteta
      
      
      
                                                                $yhteensa_eitulosteta
      
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

$sivu = escapeshellarg($sivu);
$line = exec(" echo \"$sivu\" | $kirjoitin");

?>