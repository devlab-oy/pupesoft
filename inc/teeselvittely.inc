<?php

$vientiselite = str_replace("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden
$tpv = substr($pvm, 0, 2);
$tpk = substr($pvm, 2, 2);
$tpp = substr($pvm, 4, 2);

if ($tpv < 1000) $tpv += 2000;

// Onko jotain kirjattavaa (tämä toimii jopa vastaviennin tapauksessa)?
if (round($maara, 2) != 0) {

  // Kirjoitetaan lasku, jos sellaista ei ole vielä tehty
  if ($laskuid == 0) {

    $query = "INSERT into lasku set
              yhtio      = '$yritirow[yhtio]',
              tapvm      = '$tpv-$tpk-$tpp',
              tila       = 'X',
              laatija    = 'tiliote',
              luontiaika = now()";
    $result = pupe_query($query);
    $laskuid = mysql_insert_id($GLOBALS["masterlink"]);

    echo "<font class='message'>*** ".t("Tositteen otsikko luotu")." ***</font><br>\n";
  }

  // Tehdään vastakirjaus
  if ($tkesken == 0 and $laskuid != 0) {

    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yritirow["oletus_rahatili"]);

    // Rahatili
    $query = "INSERT into tiliointi set
              yhtio            = '$yritirow[yhtio]',
              ltunnus          = '$laskuid',
              tilino           = '$yritirow[oletus_rahatili]',
              kustp            = '{$kustp_ins}',
              kohde            = '{$kohde_ins}',
              projekti         = '{$projekti_ins}',
              tapvm            = '$tpv-$tpk-$tpp',
              summa            = '$vastavienti',
              summa_valuutassa = '$vastavienti_valuutassa',
              valkoodi         = '$tilinvaluutta',
              vero             = 0,
              lukko            = '',
              selite           = 'Vastavienti rahatilille',
              laatija          = 'tiliote',
              laadittu         = now()";
    $result = pupe_query($query);

    $vastavienti       = 0;
    $vastavienti_valuutassa = 0;
    $laskuid         = 0;

    echo "<font class='message'>*** ".t("Vastavienti luotu")." ($yritirow[oletus_rahatili]) ***</font><br>\n";
  }

  // Tehdään normaali kirjaus
  if ($laskuid != 0) {
    unset($tiliointisaantorow);
    require "tiliotesaannot.inc";

    // Luottokunta tai vastaava jaettava
    if ($tiliointisaantorow['tilino2'] != 0) {

      $luottokuntatieto = str_replace(".", "",  $luottokuntatieto);
      $luottokuntatieto = str_replace(",", ".", $luottokuntatieto);

      // Kantaluku: alusta ekaan spaceen
      $kanta = (float) substr($luottokuntatieto, 0, strpos($luottokuntatieto, " "));

      // Palkkio: vikasta spacesta loppuun
      $palkkio = (float) substr($luottokuntatieto, (strrpos($luottokuntatieto, " ") + 1));

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tiliointisaantorow["tilino2"], $tiliointisaantorow["kustp2"]);

      // Kulutili 2
      $query = "INSERT into tiliointi set
                yhtio            = '$yritirow[yhtio]',
                ltunnus          = '$laskuid',
                tilino           = '$tiliointisaantorow[tilino2]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tpv-$tpk-$tpp',
                summa            = $palkkio * -1,
                summa_valuutassa = round($palkkio * $kurssi * -1, 2),
                valkoodi         = '$tilinvaluutta',
                vero             = 0,
                lukko            = '',
                selite           = '$vientiselite',
                laatija          = 'tiliote',
                laadittu         = now()";
      $result = pupe_query($query);

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tiliointisaantorow["tilino"], $tiliointisaantorow["kustp"]);

      // Kulutili 1
      $query = "INSERT into tiliointi set
                yhtio            = '$yritirow[yhtio]',
                ltunnus          = '$laskuid',
                tilino           = '$tiliointisaantorow[tilino]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tpv-$tpk-$tpp',
                summa            = $kanta * -1,
                summa_valuutassa = round($kanta * $kurssi * -1, 2),
                valkoodi         = '$tilinvaluutta',
                vero             = 0,
                lukko            = '',
                selite           = '$vientiselite',
                laatija          = 'tiliote',
                laadittu         = now()";
      $result = pupe_query($query);
    }
    else {

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tiliointisaantorow["tilino"], $tiliointisaantorow["kustp"]);

      // Kulutili
      $query = "INSERT into tiliointi set
                yhtio            = '$yritirow[yhtio]',
                ltunnus          = '$laskuid',
                tilino           = '$tiliointisaantorow[tilino]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tpv-$tpk-$tpp',
                summa            = $maara * -1,
                summa_valuutassa = $kohdm * -1,
                valkoodi         = '$tilinvaluutta',
                vero             = 0,
                lukko            = '',
                selite           = '$vientiselite',
                laatija          = 'tiliote',
                laadittu         = now()";
      $result = pupe_query($query);
    }

    $_tiliointi_id = mysql_insert_id($GLOBALS["masterlink"]);

    $query = "UPDATE tiliotedata
              SET kasitelty = now(),
              tiliointitunnus = {$_tiliointi_id}
              WHERE tunnus    = {$tiliotedataid}";
    $dummyresult = pupe_query($query);

    if ($loydettiinko_sopiva_tili != '') {
      $query = "UPDATE tiliotedata
                SET kuitattu = 'tiliote',
                kuitattuaika = now()
                WHERE yhtio  = '$yritirow[yhtio]'
                and perheid  = '$tiliotedatape'";
      $dummyresult = pupe_query($query);
    }

    echo "<font class='message'>*** ".t("tiliöinti luotu")." ($tiliotesaantotilino) ***</font><br>\n";

    if ($yhtiorow['tiliotteen_selvittelyt'] == "" and $tiliotedatarow['alku'] == $tiliotedatarow['loppu']) {
      // Jos yhtiön parametri sallii ja käsittelyssä on yhden päivän tiliote niin voidaan tiliöidä kaikki selvittelyt samalle tositteelle.
      $vastavienti += $maara;
      $vastavienti_valuutassa += $kohdm;
    }
    else {
      // Muuten tiliöidään kaikki selivttelyt omille tositteilleen ja tehdään vastavientiki per tosite, tietenki!
      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yritirow["oletus_rahatili"]);

      // Rahatili
      $query = "INSERT into tiliointi set
                yhtio            = '$yritirow[yhtio]',
                ltunnus          = '$laskuid',
                tilino           = '$yritirow[oletus_rahatili]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$tpv-$tpk-$tpp',
                summa            = '$maara',
                summa_valuutassa = '$kohdm',
                valkoodi         = '$tilinvaluutta',
                vero             = 0,
                lukko            = '',
                selite           = 'Vastavienti rahatilille',
                laatija          = 'tiliote',
                laadittu         = now()";
      $result = pupe_query($query);

      $vastavienti       = 0;
      $vastavienti_valuutassa = 0;
      $laskuid         = 0;

      echo "<font class='message'>*** ".t("Vastavienti luotu")." ($yritirow[oletus_rahatili]) ***</font><br>\n";
    }
  }
}
