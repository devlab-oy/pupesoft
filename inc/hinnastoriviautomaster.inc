<?php

function hinnastorivi($tuote, $laskurow) {
  global $kukarow;
  
  $myyntihinta = tuotteen_myyntihinta($laskurow, $tuote, 1, "ei");

  // muutetaan yksiköt ISO-standardin mukaisiksi
  $yksikko="";
  if ($tuote['yksikko']=='KPL' or $tuote['yksikko']=='PCE')
    $yksikko = "PCE";
  if ($tuote['yksikko']=='SRJ')
    $yksikko = "SET";
  if ($tuote['yksikko']=='PAR')
    $yksikko = "PAR";

  // Katsotaan, mistä löytyy enari
  if ($tuote["eankoodi"] == '') {
    $query = "SELECT *
              FROM tuotteen_toimittajat
              WHERE yhtio = '$kukarow[yhtio]'
              AND tuoteno = '$tuote[tuoteno]'
              ORDER BY if (jarjestys = 0, 9999, jarjestys) limit 1";
    $tuotetoim_res = pupe_query($query);
    $tuotetoimrow = mysql_fetch_assoc($tuotetoim_res);
    $tuote["eankoodi"] = $tuotetoimrow["viivakoodi"];
  }

  // tehdään tietuetta
  $out  .= sprintf("%-35.35s", $tuote['tuoteno']);          // tuotenumero
  $out  .= sprintf("%-10.10s", sprintf("%02s", $tuote['try']));    // tuotteen tuoteryhmä kahteen merkkiin asti zeropaddattuna
  $out  .= sprintf("%-10.10s", sprintf("%02s", $tuote['aleryhma']));  // tuotteen alennusryhmä kahteen merkkiin asti zeropaddattuna
  $out  .= sprintf("%-50.50s", $tuote['nimitys']);             // tuotteen nimitys
  $out  .= sprintf("%-1.1s",   "1");                      // verollisuus 1=verollinen 0=veroton
  $out  .= sprintf("%11.11s",  $myyntihinta);                 // tuotteen luettelohinta
  $out  .= sprintf("%-1.1s",   "");                     // hinnan yksikkö
  $out  .= sprintf("%-35.35s", $tuote['eankoodi']);             // tuotteen eankoodi
  $out  .= sprintf("%-1.1s",   "");                     // elinkaaren vaihe 1-9
  $out  .= sprintf("%-11.11s", "0");                     // minimitoimituserä
  $out  .= sprintf("%-3.3s",   $yksikko);                   // minimitoimituksen yksikkö
  $out  .= sprintf("%-3.3s",   "");                     // hinnoittelujakaja
  $out  .= sprintf("%-16.16s", "");                     // tarjouskampanjan voimassaolo CCYYMMDDCCYYMMDD
  $out  .= sprintf("%-11.11s", "");                     // tarjouskampanjahinta
  $out  .= sprintf("%-16.16s", "");                     // 1. alennuksen voimassaolo CCYYMMDDCCYYMMDD
  $out  .= sprintf("%-19.19s", "");                     // määrän minimi
  $out  .= sprintf("%-19.19s", "");                     // määrän maksimi
  $out  .= sprintf("%-11.11s", "");                     // alennushinta
  $out  .= sprintf("%-16.16s", "");                     // 2. alennuksen voimassaolo CCYYMMDDCCYYMMDD
  $out  .= sprintf("%-19.19s", "");                     // määrän minimi
  $out  .= sprintf("%-19.19s", "");                     // määrän maksimi
  $out  .= sprintf("%-11.11s", "");                     // alennushinta
  $out  .= sprintf("%-16.16s", "");                     // 3. alennuksen voimassaolo CCYYMMDDCCYYMMDD
  $out  .= sprintf("%-19.19s", "");                     // määrän minimi
  $out  .= sprintf("%-19.19s", "");                     // määrän maksimi
  $out  .= sprintf("%-11.11s", "");                     // alennushinta
  $out .= "\n";

  return $out;
}
