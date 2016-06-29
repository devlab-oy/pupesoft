<?php

// Kirjoitetaan laskulle maksutiliöinti
$selite = str_replace("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden
$tpv   = substr($pvm, 0, 2);
$tpk   = substr($pvm, 2, 2);
$tpp   = substr($pvm, 4, 2);
if ($tpv < 1000) $tpv += 2000;

$teemaksutosite = TRUE;

// Haetaan ostovelkatili
$query = "SELECT tilino, kustp, kohde, projekti
          FROM tiliointi
          WHERE ltunnus = '$trow[tunnus]'
          and yhtio     = '$yritirow[yhtio]'
          and tapvm     = '$trow[tapvm]'
          and abs(summa + $trow[vietysumma]) <= 0.02
          and tilino    in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
          and korjattu  = ''";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $ostovelkarow = mysql_fetch_assoc($result);
}
else {
  echo "<font class='error'>".t("VIRHE: Ostovelkatilin määrittely epäonnistui")."!<br>\n".t("Summaa ei kirjattu")."!</font><br>\n";
  $teemaksutosite = FALSE;
}

// Haetaan kulutiliönti
$query = "SELECT tiliointi.*
          FROM tiliointi
          JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
          LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
          WHERE tiliointi.ltunnus = '$trow[tunnus]'
          AND tiliointi.yhtio     = '$yritirow[yhtio]'
          AND tiliointi.tapvm     = '$trow[tapvm]'
          AND tiliointi.tilino    not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
          AND tiliointi.korjattu  = ''
          AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))
          ORDER BY tiliointi.tunnus";
$yresult = pupe_query($query);

if (mysql_num_rows($yresult) == 0) {
  echo "<font class='error'>".t("VIRHE: Kulutilin määrittely epäonnistui")."!<br>\n".t("Summaa ei kirjattu")."!</font><br>\n";
  $teemaksutosite = FALSE;
}

if ($teemaksutosite) {

  // Kassa-ale
  if ($trow['alatila'] == 'K' and $trow['vietykasumma'] != 0) {

    // Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
    echo "<font class='message'>".t("Kirjaan kassa-alennusta yhteensä")." $trow[vietykasumma]</font><br>\n";

    $totkasumma = 0;

    mysql_data_seek($yresult, 0);

    while ($tiliointirow = mysql_fetch_assoc($yresult)) {

      // Kuinka paljon on tämän viennin osuus
      $summa = round(($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $trow['vietysumma']) * $trow['vietykasumma'], 2);
      $alv = 0;

      echo "<font class='message'>".t("Kirjaan kassa-alennusta")." $summa</font><br>\n";

      if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
        $alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)), 2);
        $summa -= $alv;
      }

      $totkasumma += $summa + $alv;

      // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["kassaale"], $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

      // Kassa-ale
      $query = "INSERT into tiliointi set
                yhtio    = '$yritirow[yhtio]',
                ltunnus  = '$trow[tunnus]',
                tilino   = '$yhtiorow[kassaale]',
                kustp    = '{$kustp_ins}',
                kohde    = '{$kohde_ins}',
                projekti = '{$projekti_ins}',
                tapvm    = '$tpv-$tpk-$tpp',
                summa    = $summa * -1,
                vero     = '$tiliointirow[vero]',
                lukko    = '',
                laatija  = 'tiliote',
                laadittu = now()";
      $xresult = pupe_query($query);
      $isa = mysql_insert_id($GLOBALS["masterlink"]); // Näin löydämme tähän liittyvät alvit....

      if ($tiliointirow['vero'] != 0) {
        // Kassa-alen alv
        $query = "INSERT into tiliointi set
                  yhtio     = '$yritirow[yhtio]',
                  ltunnus   = '$trow[tunnus]',
                  tilino    = '$yhtiorow[alv]',
                  kustp     = 0,
                  kohde     = 0,
                  projekti  = 0,
                  tapvm     = '$tpv-$tpk-$tpp',
                  summa     = $alv * -1,
                  vero      = 0,
                  selite    = '$selite',
                  lukko     = '1',
                  laatija   = 'tiliote',
                  laadittu  = now(),
                  aputunnus = $isa";
        $xresult = pupe_query($query);
      }
    }

    //Hoidetaan mahdolliset pyöristykset
    $heitto = round($totkasumma - $trow["vietykasumma"], 2);

    if (abs($heitto) >= 0.01) {
      echo "<font class='message'>".t("Joudun pyöristämään kassa-alennusta")."</font><br>\n";

      $query = "UPDATE tiliointi
                SET summa = summa + $heitto
                WHERE tunnus = '$isa'
                and yhtio    = '$yritirow[yhtio]'";
      $xresult = pupe_query($query);
    }
  }

  // Valuutta-ero
  if ($trow['vienti_kurssi'] != $kurssi) {

    $vesumma = ($maara*-1) - $trow['vietysumma'];

    if ($trow['alatila'] == 'K' and $trow['vietykasumma'] != 0) {
      $vesumma = round(($maara*-1) - ($trow['vietysumma'] - $trow['vietykasumma']), 2);
    }

    if (round($vesumma, 2) != 0) {
      echo "<font class='message'>".t("Kirjaan valuuttaeroa yhteensä")." $vesumma</font><br>\n";

      $totvesumma = 0;

      mysql_data_seek($yresult, 0);

      while ($tiliointirow = mysql_fetch_assoc($yresult)) {
        // Kuinka paljon on tämän viennin osuus
        $summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $trow['vietysumma'] * $vesumma, 2);

        echo "<font class='message'>".t("Kirjaan valuuttaeroa")." $summa</font><br>\n";

        if (round($summa, 2) != 0) {

          // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
          list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["valuuttaero"], $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

          // Valuuttaero
          $query = "INSERT into tiliointi set
                    yhtio    = '$yritirow[yhtio]',
                    ltunnus  = '$trow[tunnus]',
                    tilino   = '$yhtiorow[valuuttaero]',
                    kustp    = '{$kustp_ins}',
                    kohde    = '{$kohde_ins}',
                    projekti = '{$projekti_ins}',
                    tapvm    = '$tpv-$tpk-$tpp',
                    summa    = $summa,
                    vero     = 0,
                    lukko    = '',
                    laatija  = 'tiliote',
                    laadittu = now()";
          $xresult = pupe_query($query);
          $isa = mysql_insert_id($GLOBALS["masterlink"]);

          $totvesumma += $summa;
        }
      }

      // Hoidetaan mahdolliset pyöristykset
      if ($totvesumma != $vesumma) {
        echo "<font class='message'>".t("Joudun pyöristämään valuuttaeroa")."</font><br>\n";

        $query = "UPDATE tiliointi
                  SET summa = summa - $totvesumma + $vesumma
                  WHERE tunnus = '$isa' and yhtio='$yritirow[yhtio]'";
        $xresult = pupe_query($query);
      }
    }
  }

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yritirow["oletus_rahatili"]);

  if ($trow["valkoodi"] != '' and $trow["valkoodi"] != $yritirow["valkoodi"]) {
    $summavaluutassa = $trow["summa"];
    $summavaluuttakoodi = $trow["valkoodi"];
  }
  else {
    $summavaluutassa = 0;
    $summavaluuttakoodi = '';
  }

  // Rahatili
  $query = "INSERT into tiliointi set
            yhtio            = '$yritirow[yhtio]',
            ltunnus          = '$trow[tunnus]',
            tilino           = '$yritirow[oletus_rahatili]',
            kustp            = '{$kustp_ins}',
            kohde            = '{$kohde_ins}',
            projekti         = '{$projekti_ins}',
            tapvm            = '$tpv-$tpk-$tpp',
            summa            = '$maara',
            summa_valuutassa = '{$summavaluutassa}',
            valkoodi         = '{$summavaluuttakoodi}',
            vero             = 0,
            lukko            = '',
            laatija          = 'tiliote',
            laadittu         = now()";
  $result = pupe_query($query);

  // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($ostovelkarow["tilino"], $ostovelkarow["kustp"], $ostovelkarow["kohde"], $ostovelkarow["projekti"]);

  // Ostovelat, tarkenteet kopsataan alkuperäisesltä tiliöinniltä
  $query = "INSERT into tiliointi set
            yhtio            = '$yritirow[yhtio]',
            ltunnus          = '$trow[tunnus]',
            tilino           = '$ostovelkarow[tilino]',
            kustp            = '{$kustp_ins}',
            kohde            = '{$kohde_ins}',
            projekti         = '{$projekti_ins}',
            tapvm            = '$tpv-$tpk-$tpp',
            summa            = '$trow[vietysumma]',
            summa_valuutassa = '{$summavaluutassa}',
            valkoodi         = '{$summavaluuttakoodi}',
            vero             = 0,
            lukko            = '',
            laatija          = 'tiliote',
            laadittu         = now()";
  $result = pupe_query($query);
  $_tiliointi_id = mysql_insert_id($GLOBALS["masterlink"]);

  $query = "UPDATE tiliotedata
            SET kasitelty = now(),
            tiliointitunnus = {$_tiliointi_id}
            WHERE tunnus    = {$tiliotedataid}";
  $dummyresult = pupe_query($query);

  $query = "UPDATE tiliotedata
            SET kuitattu = 'tiliote',
            kuitattuaika = now()
            WHERE yhtio  = '$yritirow[yhtio]'
            and perheid  = '$tiliotedatape'";
  $dummyresult = pupe_query($query);

  // Merkataan käsitellyiksi & lisätään linkki
  echo "<font class='message'>*** ".t("laskuun lisätty maksutiliöinti")." ***</font><br>\n";

  //Lasku maksetuksi
  $kurssi += 0;

  if ($kurssi == 0) {
    $kurssi = 1;
  }

  $query = "UPDATE lasku
            set tila = 'Y',
            maksu_kurssi = $kurssi,
            mapvm        = '$tpv-$tpk-$tpp'
            WHERE tunnus = '$trow[tunnus]'";
  $result = pupe_query($query);

  echo "<font class='message'>*** ".t("lasku suoritettu")." ***</font><br>\n";

}
else {
  // Päivitetään virhetapauksissa kasitelty nollaksi jotta tämä käsitellään sit seuraavan kerran kun tiliotetta katsotaan!!!!
  $query = "UPDATE tiliotedata
            SET kasitelty = '0000-00-00 00:00:00'
            WHERE tunnus = '$tiliotedataid'";
  $dummyresult = pupe_query($query);
}
