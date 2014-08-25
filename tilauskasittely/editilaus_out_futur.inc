<?php

// M = myyntitilaus, O = ostotilaus
if (!isset($myynti_vai_osto)) $myynti_vai_osto = 'M';
if (!isset($toim)) $toim = "";

$luodaan_aineisto = true;

if (!in_array($myynti_vai_osto, array('M', 'O')) or !isset($laskurow)) $luodaan_aineisto = false;

if ($luodaan_aineisto and $myynti_vai_osto == 'M') {

  $query = "SELECT *
            FROM asiakkaan_avainsanat
            WHERE yhtio       = '{$kukarow['yhtio']}'
            AND liitostunnus  = '{$laskurow['liitostunnus']}'
            AND laji          IN ('futur_host','futur_path','futur_user','futur_password')
            AND avainsana    != ''";
  $chk_res = pupe_query($query);

  $ftphost_futur = $ftppath_futur = $ftpuser_futur = $ftppass_futur = "";

  while ($chk_row = mysql_fetch_assoc($chk_res)) {

    switch ($chk_row['laji']) {
    case 'futur_host':
      $ftphost_futur = $chk_row['avainsana'];
      break;
    case 'futur_path':
      $ftppath_futur = $chk_row['avainsana'];
      break;
    case 'futur_user':
      $ftpuser_futur = $chk_row['avainsana'];
      break;
    case 'futur_password':
      $ftppass_futur = $chk_row['avainsana'];
      break;
    }
  }

  if (($ftphost_futur == 'localhost' and $ftppath_futur != '') or ($ftphost_futur != 'localhost' and $ftphost_futur != '' and $ftppath_futur != '' and $ftppass_futur != '' and $ftpuser_futur != '')) {
    $laskurow['tilaustyyppi'] = 3;
  }
  else {
    $luodaan_aineisto = false;
  }
}

if ($luodaan_aineisto) {

  $summa = $toim == 'REKLAMAATIO' ? $laskurow['summa'] : '';

  //tilauksen otsikko
  $edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$laskurow['tunnus']} version:AFP-1.0 *MS\n";
  $edi_order .= "*MS {$laskurow['tunnus']}\n";
  $edi_order .= "*RS OSTOTIL\n";

  if ($myynti_vai_osto == 'O') $_toimitustapa = $laskurow['kuljetus'];
  else $_toimitustapa = $laskurow['toimitustapa'];

  $edi_order .= "OSTOTIL.OT_NRO:{$laskurow['tunnus']}\n";
  // 0 = Örum
  $edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:0\n";
  $edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:{$laskurow['tilaustyyppi']}\n";
  $edi_order .= "OSTOTIL.OT_TILAUSAIKA:\n";
  $edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:{$_toimitustapa}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:{$laskurow['toimitusehto']}\n";
  $edi_order .= "OSTOTIL.OT_MAKSUEHTO:{$laskurow['maksuehto']}\n";
  $edi_order .= "OSTOTIL.OT_VIITTEEMME:{$laskurow['viesti']}\n";

  // Onko asiakkaan takana hyllypaikka jossa tavarat säilytetään
  if ($myynti_vai_osto == 'M') {

    $query = "SELECT avainsana
              FROM asiakkaan_avainsanat
              WHERE yhtio      = '$kukarow[yhtio]'
              AND liitostunnus = '$laskurow[liitostunnus]'
              AND laji         = 'JMALMIHYLLY'";
    $aares = pupe_query($query);

    if (mysql_num_rows($aares) > 0) {
      $aarow = mysql_fetch_assoc($aares);
      $laskurow['sisviesti2'] .= ";;".$aarow["avainsana"];
    }
  }

  $edi_order .= "OSTOTIL.OT_VIITTEENNE:{$laskurow['ohjausmerkki']}\n";
  $edi_order .= "OSTOTIL.OT_SUMMA:{$summa}\n";
  $edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:{$laskurow['valkoodi']}\n";
  $edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
  $edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
  $edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
  $edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
  $edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
  $edi_order .= "OSTOTIL.OT_FAKSI:\n";

  $query = "SELECT *
            FROM laskun_lisatiedot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$laskurow['tunnus']}'";
  $lisatiedot_result = pupe_query($query);
  $laskun_lisatiedot = mysql_fetch_assoc($lisatiedot_result);

  $lask_yritys = trim($lisatiedot['laskutus_nimi'].' '.$lisatiedot['laskutus_nimitark']);
  $lask_osoite = $lisatiedot['laskutus_osoite'];
  $lask_postitp = $lisatiedot['laskutus_postitp'];
  $lask_postino = $lisatiedot['laskutus_postino'];

  $asiakasnumero = $yhtiorow['ovttunnus'];

  // Generoidaan asiakasnumeroon ovtlisä, jos käytössä on toimipaikka
  if (($myynti_vai_osto == 'O' and $laskurow['vanhatunnus'] != 0) or ($myynti_vai_osto == 'M' and $laskurow['yhtio_toimipaikka'] != 0)) {

    $wherelisa = $myynti_vai_osto == 'O' ? $laskurow['vanhatunnus'] : $laskurow['yhtio_toimipaikka'];

    $query = "SELECT ovtlisa
              FROM yhtion_toimipaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$wherelisa}'";
    $toimipaikka_chk_res = pupe_query($query);

    if (mysql_num_rows($toimipaikka_chk_res) != 0) {
      $toimipaikka_chk_row = mysql_fetch_assoc($toimipaikka_chk_res);
      $asiakasnumero = "{$asiakasnumero}{$toimipaikka_chk_row['ovtlisa']}";
    }
  }

  $edi_order .= "OSTOTIL.OT_ASIAKASNRO:{$asiakasnumero}\n";
  $edi_order .= "OSTOTIL.OT_YRITYS:{$lask_yritys}\n";
  $edi_order .= "OSTOTIL.OT_KATUOSOITE:{$lask_osoite}\n";
  $edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$lask_postitp}\n";
  $edi_order .= "OSTOTIL.OT_POSTINRO:{$lask_postino}\n";
  $edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:\n";
  $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:\n";
  $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:{$yhtiorow['nimi']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:{$yhtiorow['osoite']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:{$yhtiorow['postitp']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:{$yhtiorow['postino']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:{$yhtiorow['maa']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";

  $toim_yritys   = substr(trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']), 0, 30);
  $toim_osoite   = substr(trim($laskurow['toim_osoite']), 0, 25);
  $toim_postitp  = substr(trim($laskurow['toim_postitp']), 0, 25);
  $toim_postino  = trim($laskurow['toim_postino']);
  $toim_maa      = trim($laskurow['toim_maa']);

  if ($toim_maa != '' and strtoupper($toim_maa) != strtoupper($yhtiorow["maa"])) {
    $toim_yritys  = '';
    $toim_osoite  = '';
    $toim_postitp = '';
    $toim_postino = '';
    $toim_maa    = '';
  }
  // jos toimitusosoite eroaa asiakkaan toimitusosoitteesta (tai jos toimitusosoite on tyhjä niin asiakkaan osoitteesta), niin silloin se lisätään aineistoon
  elseif ($myynti_vai_osto == 'M' and isset($asiakasrow) and trim($asiakasrow['toim_osoite']) != '') {
    // Kauttalaskutusasiakkaille ei tyhjennetä
    if ($asiakasrow["osasto"] != "6" and $toim_yritys == substr(trim($asiakasrow['toim_nimi'].' '.$asiakasrow['toim_nimitark']), 0, 30) and $toim_osoite == substr(trim($asiakasrow['toim_osoite']), 0, 25) and $toim_postitp == substr(trim($asiakasrow['toim_postitp']), 0, 25) and $toim_postino == trim($asiakasrow['toim_postino'])) {
      $toim_yritys   = '';
      $toim_osoite   = '';
      $toim_postitp   = '';
      $toim_postino   = '';
      $toim_maa     = '';
    }
  }
  else {
    if ($myynti_vai_osto == 'M' and isset($asiakasrow) and $toim_yritys == substr(trim($asiakasrow['nimi'].' '.$asiakasrow['nimitark']), 0, 30) and $toim_osoite == substr(trim($asiakasrow['osoite']), 0, 25) and $toim_postitp == substr(trim($asiakasrow['postitp']), 0, 25) and $toim_postino == trim($asiakasrow['postino'])) {
      $toim_yritys   = '';
      $toim_osoite   = '';
      $toim_postitp  = '';
      $toim_postino   = '';
      $toim_maa     = '';
    }
  }

  $edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:{$toim_yritys}\n";
  //$edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:{$toim_osoite}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$toim_postitp}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$toim_postino}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$toim_maa}\n";
  $edi_order .= "*RE OSTOTIL\n";

  $i = 1;

  $query = "SELECT tilausrivi.*
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.panttitili = '' AND tuote.ei_saldoa = '')
            LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tilausrivi.yhtio AND ta.tuoteno = tilausrivi.tuoteno AND ta.laji = 'ei_edi_ostotilaukseen')
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
            AND tilausrivi.var     not in ('U','J')
            AND ta.tunnus IS NULL";
  $tilausrivi_res = pupe_query($query);

  if (mysql_num_rows($tilausrivi_res) == 0) return false;

  while ($tilausrivi_row = mysql_fetch_assoc($tilausrivi_res)) {

    $kpl = $tilausrivi_row['jt'] + $tilausrivi_row['varattu'];

    if ($tilausrivi_row['var'] == 'P') {
      $kpl = $tilausrivi_row['tilkpl'] * 1;
    }

    // HUOM: Reklamaatioissa laitetaan nettohyvitysyksikköhinta
    $hinta = $tilausrivi_row['hinta'] * generoi_alekentta_php($tilausrivi_row, $myynti_vai_osto, 'kerto');

    // nettorivihinta
    $rivihinta = $kpl * $hinta;

    $edi_order .= "*RS OSTOTILRIV {$i}\n";
    $edi_order .= "OSTOTILRIV.OTR_NRO:{$laskurow['tunnus']}\n";
    $edi_order .= "OSTOTILRIV.OTR_RIVINRO:{$tilausrivi_row['tunnus']}\n";
    $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
    $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tilausrivi_row['tuoteno']}\n";
    $edi_order .= "OSTOTILRIV.OTR_NIMI:{$tilausrivi_row['nimitys']}\n";

    if ($myynti_vai_osto == 'O') {

      // Haetaan tuotteen toimittajan tuotekerroin
      $query = "SELECT tuotekerroin, if(jarjestys = 0, 9999, jarjestys) sorttauskentta
                FROM tuotteen_toimittajat
                WHERE yhtio       = '{$kukarow['yhtio']}'
                AND tuoteno       = '{$tilausrivi_row['tuoteno']}'
                AND liitostunnus  = '{$laskurow['liitostunnus']}'
                AND tuotekerroin != 0
                AND tuotekerroin != 1
                ORDER BY sorttauskentta";
      $tt_tuotekerroin_chk_res = pupe_query($query);

      if (mysql_num_rows($tt_tuotekerroin_chk_res) != 0) {
        $tt_tuotekerroin_chk_row = mysql_fetch_assoc($tt_tuotekerroin_chk_res);

        $kpl *= $tt_tuotekerroin_chk_row['tuotekerroin'];
      }
    }

    $kpl = round($kpl);

    $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";

    $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$rivihinta}\n";

    if ($toim == 'REKLAMAATIO') {
      $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$hinta}\n";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $edi_order .= "OSTOTILRIV.OTR_ALENNUS_{$alepostfix}:0\n";
      }
    }
    else {

      $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$tilausrivi_row['hinta']}\n";

      $ale = generoi_alekentta_php($tilausrivi_row, $myynti_vai_osto, 'erikseen');
      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $edi_order .= "OSTOTILRIV.OTR_ALENNUS_{$alepostfix}:".$ale["ale".$alepostfix]."\n";
      }
    }

    $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$tilausrivi_row['alv']}\n";

    //$edi_order .= "OSTOTILRIV.OTR_VIITE:{$tilausrivi_row['kommentti']}\n";
    $edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
    $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
    $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:{$tilausrivi_row['yksikko']}\n";

    if ($tilausrivi_row['var'] == 'J') {
      $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUS:J\n";
    }

    $edi_order .= "*RE  OSTOTILRIV $i\n";
    $i++;

    $sqlquery = "UPDATE tilausrivi SET
                 tilaajanrivinro = tunnus
                 WHERE yhtio     = '$kukarow[yhtio]'
                 AND tunnus      = '$tilausrivi_row[tunnus]'";
    $tresult = pupe_query($sqlquery, $GLOBALS["masterlink"]);

  }

  if ($i > 1) {

    if ($myynti_vai_osto == 'O') {
      $query = "UPDATE lasku
                SET kirjoitin = 'edi'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      pupe_query($query, $GLOBALS["masterlink"]);
    }

    $edi_order .= "*ME\n";
    $edi_order .= "*IE\n";

    $edipolku = "/tmp";

    $filename = $edipolku."/{$laskurow['tunnus']}.dat";
    file_put_contents($filename, $edi_order);

    $ftphost = $ftphost_futur;
    $ftpuser = $ftpuser_futur;
    $ftppass = $ftppass_futur;
    $ftppath = $ftppath_futur;

    $ftpfile = $unlink_filename = $filename;

    if (file_exists("ftp-send.inc")) {
      require "ftp-send.inc";
    }
    elseif (file_exists("../inc/ftp-send.inc")) {
      require "../inc/ftp-send.inc";
    }
    else {
      require "inc/ftp-send.inc";
    }

    if ($palautus != 0) $luodaan_aineisto = false;
  }
}
