<?php

function hinnastorivi($tuote, $laskurow) {
  global $kukarow;

  $myyntihinta = tuotteen_myyntihinta($laskurow, $tuote, 1, "ei");

  // Jos tuotteen status on P, hinnastoon KYLLÄ, tuote on korvaavuusketjussa ja tuotteen saldo on 0,
  // laitetaan myyntihinta nollaksi
  if ($tuote['status'] == 'P' and $tuote['hinnastoon'] != 'E' and $tuote['id'] != '') {

    // Tarkistetaan saldo
    $query = "SELECT sum(saldo) as saldo
              FROM tuotepaikat
              WHERE yhtio='{$kukarow['yhtio']}'
              AND tuoteno='{$tuote['tuoteno']}'";
    $result = pupe_query($query);

    // jos saldo on nolla laitetaan myyntihinnaksi 0
    $saldo = mysql_fetch_assoc($result);
    if ($saldo['saldo'] == 0) {
      $myyntihinta = 0;
    }
  }
  // Jos tuote on poistettu ja hinnastoon on kyllä, mutta tuote ei ole korvaavuusketjussa.
  // Ei laiteta hinnastoon.
  elseif ($tuote['status'] == 'P' and $tuote['hinnastoon'] != 'E' and $tuote['id'] == '') {
    return;
  }

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

  // muutetaan yksiköt ISO-standardin mukaisiksi
  $yksikko="";
  if ($tuote['yksikko']=='KPL' or $tuote['yksikko']=='PCE')
    $yksikko = "PCE";
  if ($tuote['yksikko']=='SRJ')
    $yksikko = "SET";
  if ($tuote['yksikko']=='PAR')
    $yksikko = "PAR";

  // tehdään tietuetta
  $out   = sprintf("%-1.1s",   "D");                 // vakio, D=datarivi
  $out  .= sprintf("%-35.25s", $tuote['tuoteno']);            // tuotenumero
  $out  .= sprintf("%-10.10s", sprintf("%02s", $tuote['try']));    // tuotteen tuoteryhmä kahteen merkkiin asti zeropaddattuna
  $out  .= sprintf("%-10.10s", sprintf("%02s", $tuote['aleryhma']));  // tuotteen alennusryhmä kahteen merkkiin asti zeropaddattuna
  $out  .= sprintf("%-50.50s", $tuote['nimitys']);               // tuotteen nimitys
  $out  .= sprintf("%-1.1s",   "1");                    // verollisuus 1=verollinen 0=veroton
  $out  .= sprintf("%-11.11s", $myyntihinta);             // tuotteen luettelohinta
  $out  .= sprintf("%-1.1s",   "");                     // hinnan yksikkö
  $out  .= sprintf("%-35.35s", $tuote['eankoodi']);             // tuotteen eankoodi
  $out  .= sprintf("%-1.1s",   "");                     // elinkaaren vaihe 1-9
  $out  .= sprintf("%-11.11s", "0");                     // minimitoimituserä
  $out  .= sprintf("%-3.3s",   $yksikko);                 // minimitoimituksen yksikkö
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
  $out  .= sprintf("%-35.35s", $tuote['muuta']);        // tuotteen "muuta"
  $out .= "\n";

  return $out;
}
