<?php

# Tilauksien tunnukset tulevat $otunnukset muuttujassa
$query = "SELECT *
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus IN ({$otunnukset})";
$laskures = pupe_query($query);

while ($laskurow = mysql_fetch_assoc($laskures)) {

  $query = "SELECT *, IF(puh != '', puh, gsm) AS puhelin
            FROM yhteyshenkilo
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tyyppi = 'A'
            AND liitostunnus = '{$laskurow['liitostunnus']}'
            AND nimi = '{$laskurow['tilausyhteyshenkilo']}'";
  $yhteyshenkilores = pupe_query($query);
  $yhteyshenkilorow = mysql_fetch_assoc($yhteyshenkilores);

  $query  = "SELECT *
             FROM maksuehto
             WHERE yhtio = '{$kukarow['yhtio']}'
             and tunnus  = '{$laskurow['maksuehto']}'";
  $maksuehtores = pupe_query($query);
  $maksuehtorow = mysql_fetch_assoc($maksuehtores);

  $lask_yritys  = trim($laskurow['nimi'].' '.$laskurow['nimitark']);
  $lask_osoite  = $laskurow['osoite'];
  $lask_postitp = $laskurow['postitp'];
  $lask_postino = $laskurow['postino'];
  $lask_maa     = $laskurow['maa'];

  $toim_ovttunnus = $laskurow['toim_ovttunnus'];
  $toim_nimi      = substr(trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']), 0, 30);
  $toim_osoite    = substr(trim($laskurow['toim_osoite']), 0, 25);
  $toim_postitp   = substr(trim($laskurow['toim_postitp']), 0, 25);
  $toim_postino   = trim($laskurow['toim_postino']);
  $toim_maa       = trim($laskurow['toim_maa']);

  $edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$laskurow['tunnus']} version:AFP-1.0 *MS\n";
  $edi_order .= "*MS {$laskurow['tunnus']}\n";
  $edi_order .= "*RS OSTOTIL\n";
  $edi_order .= "OSTOTIL.OT_NRO:{$laskurow['tunnus']}\n";

  $edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:{$yhtiorow['ovttunnus']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:{$yhtiorow['nimi']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:{$yhtiorow['osoite']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:{$yhtiorow['postitp']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:{$yhtiorow['postino']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:{$yhtiorow['maa']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:{$yhtiorow['puhelin']}\n";
  $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:{$yhtiorow['fax']}\n";

  $edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:1\n";
  $edi_order .= "OSTOTIL.OT_TILAUSAIKA:{$laskurow['h1time']}\n";
  $edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSPVM:{$laskurow['toimaika']}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:{$laskurow['toimitustapa']}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:{$laskurow['toimitusehto']}\n";
  $edi_order .= "OSTOTIL.OT_MAKSUEHTO:{$maksuehtorow['teksti']}\n";
  $edi_order .= "OSTOTIL.OT_VIITTEEMME:{$laskurow['viesti']}\n";
  $edi_order .= "OSTOTIL.OT_VIITTEENNE:{$laskurow['asiakkaan_tilausnumero']}\n";
  $edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:{$laskurow['valkoodi']}\n";
  $edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
  $edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
  $edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
  $edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";

  $edi_order .= "OSTOTIL.OT_OVTTUNNUS:{$laskurow['ovttunnus']}\n";
  $edi_order .= "OSTOTIL.OT_YRITYS:{$lask_yritys}\n";
  $edi_order .= "OSTOTIL.OT_KATUOSOITE:{$lask_osoite}\n";
  $edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$lask_postitp}\n";
  $edi_order .= "OSTOTIL.OT_POSTINRO:{$lask_postino}\n";
  $edi_order .= "OSTOTIL.OT_MAAKOODI:{$lask_maa}\n";
  $edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:{$yhteyshenkilorow['nimi']}\n";
  $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:{$yhteyshenkilorow['puhelin']}\n";
  $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:{$yhteyshenkilorow['fax']}\n";

  $edi_order .= "OSTOTIL.OT_TOIMITUS_OVTTUNNUS:{$toim_ovttunnus}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:{$toim_nimi}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:{$toim_osoite}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$toim_postitp}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$toim_postino}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$toim_maa}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_EMAIL:{$laskurow['toim_email']}\n";
  $edi_order .= "OSTOTIL.OT_TOIMITUS_PUH:{$laskurow['toim_puh']}\n";

  $edi_order .= "*RE OSTOTIL\n";

  $expansion_code = "";

  if (!empty($yhtiorow['ean'])) {
    $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = '{$laskurow['toimitustapa']}'", "", "", "selitetark");

    if ($_selitetark == '') {
      $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = 'kaikki'", "", "", "selitetark");
    }

    if ($_selitetark != '') {
      $expansioncode = $_selitetark;
    }
  }

  $jjfi = false;
  $uniq_merkki = 1;
  $counter = 1;

  // tarkistetaan onko painoon perustuva keräyserä käytössä
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$laskurow['liitostunnus']}'";
  $asiakasres = pupe_query($query);
  $asiakasrow = mysql_fetch_assoc($asiakasres);

  // vain yksi rivi rahtikirjoista, koska keräyseristä haetaan kerralla kaikki
  if ($asiakasrow['kerayserat'] == 'A') {
    $_limit = " LIMIT 1";
  }
  else {
    $_limit = "";
  }

  $query = "SELECT *
            FROM rahtikirjat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otsikkonro = '{$laskurow['tunnus']}'
            {$_limit}";
  $rahtikirjares = pupe_query($query);

  while ($rahtikirjarow = mysql_fetch_assoc($rahtikirjares)) {

    $sscc_array = array();

    // kollien lukumäärä otetaan keräyseristä, joten ei loopata tässä kuin kerran
    if ($asiakasrow['kerayserat'] == 'A') {
      $kollit = 1;
    }
    else {
      $kollit = $rahtikirjarow['kollit'];
    }

    for (;$kollit > 0; $kollit--) {

      if (!empty($rahtikirjarow['sscc_ulkoinen'])) {
        if (strpos($rahtikirjarow['sscc_ulkoinen'], "\n") !== false) {
          # Oletetaan että sscc_ulkoinen-kentässä on niin monta SSCC-numeroa kuin on kolleja
          $kollit = 0; # Ei loopata tuplasti koska sscc_ulkoinen-kentässä on kaikkien kollien SSCC:t
          $sscc_array = explode("\n", $rahtikirjarow['sscc_ulkoinen']);
        }
        else {
          $sscc_array[] = $rahtikirjarow['sscc_ulkoinen'];
        }
      }
      elseif (stripos($rahtikirjarow['rahtikirjanro'], "JJFI") !== false) {
        $kollit = 0; # Ei loopata tuplasti koska rahtikirjanro-kentässä on kaikkien kollien JJFI:t
        $jjfi = true;
        preg_match_all("/JJFI ?[0-9]{6} ?[0-9]{11}/", $rahtikirjarow['rahtikirjanro'], $match);

        foreach ($match[0] as $nro) {
          $nro = str_replace(" ", "", $nro);
          $sscc_array[] = $nro;
        }

        # JJFI-casessa tiedot ovat joka tietokantarivillä, joten halutaan vain kerran JJFI-tiedot
        # Summataan kappalemäärät ja otetaan ensimmäiseltä riviltä JJFI-tiedot
        $query = "SELECT SUM(kilot) AS kilot, SUM(kuutiot) AS kuutiot
                  FROM rahtikirjat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$laskurow['tunnus']}'";
        $rahtikirjares_sum = pupe_query($query);
        $rahtikirjarow_sum = mysql_fetch_assoc($rahtikirjares_sum);

        $rahtikirjarow['kollit'] = $rahtikirjarow_sum['kollit'] / count($sscc_array);
        $rahtikirjarow['kuutiot'] = $rahtikirjarow_sum['kuutiot'] / count($sscc_array);
      }
      elseif ($asiakasrow['kerayserat'] == 'A') {

        $query = "SELECT distinct sscc_ulkoinen
                  FROM kerayserat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND otunnus = '{$laskurow['tunnus']}'";
        $kerayserares = pupe_query($query);

        if (mysql_num_rows($kerayserares) > 0) {
          while ($kerayserarow = mysql_fetch_assoc($kerayserares)) {
            $sscc_array[] = $kerayserarow['sscc_ulkoinen'];
          }
        }
      }
      elseif ($expansioncode != "") {
        $sscc_array[] = gs1_sscc($expansioncode, $laskurow['tunnus'], $uniq_merkki);
      }
      else {
        // tehdään SSCC :
        // (00)
        // 1
        // ean (8)
        // lähetenro (6) viimeistä
        // tulostuskpl (2)
        // tarkiste (1)
        $sscc  = 1;
        $sscc .= sprintf("%08.8s", $yhtiorow["ytunnus"]);
        $sscc .= sprintf('%06.6s', substr($laskurow["tunnus"], -6));
        $sscc .= sprintf('%02.2s', $uniq_merkki);
        $loppu = tarkiste($sscc);
        $sscc  = $sscc.$loppu;

        $sscc_array[] = $sscc;
      }

      $uniq_merkki++;
    }

    foreach ($sscc_array as $sscc) {
      $edi_order .= "*RS OSTOTILPAK {$counter}\n";
      $edi_order .= "OSTOTILPAK.OTP_SSCC:{$sscc}\n";
      $edi_order .= "OSTOTILPAK.OTP_BRUTTOPAINO:{$rahtikirjarow['kilot']}\n";
      $edi_order .= "OSTOTILPAK.OTP_TILAVUUS:{$rahtikirjarow['kuutiot']}\n";
      $edi_order .= "OSTOTILPAK.OTP_TILAVUUSYKS:m3\n";
      $edi_order .= "*RE OSTOTILPAK {$counter}\n";

      $counter++;
    }

    if ($jjfi) break;
  }

  # Koontirahtikirjan pakkaus
  $edi_order .= "*RS OSTOTILPAK {$counter}\n";
  $edi_order .= "OSTOTILPAK.OTP_KOONTI_SSCC:\n";
  $edi_order .= "OSTOTILPAK.OTP_KOONTI_BRUTTOPAINO:\n";
  $edi_order .= "OSTOTILPAK.OTP_KOONTI_TILAVUUS:\n";
  $edi_order .= "OSTOTILPAK.OTP_KOONTI_TILAVUUSYKS:\n";
  $edi_order .= "*RE OSTOTILPAK {$counter}\n";

  $i = 1;

  $query = "SELECT tilausrivi.*, tuote.eankoodi
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
            AND tilausrivi.var     not in ('O','S')
            AND tilausrivi.tyyppi = 'L'
            ORDER BY tilausrivi.tilaajanrivinro";
  $tilausrivi_res = pupe_query($query);

  $_rivi_check = array();

  while ($tilausrivi_row = mysql_fetch_assoc($tilausrivi_res)) {

    if (in_array($tilausrivi_row['tuoteno'], $_rivi_check) and empty($tilausrivi_row['varattu'])) {

      if ($tilausrivi_row['tilaajanrivinro'] == $_rivi_check[$tilausrivi_row['tuoteno']]) {
        continue;
      }
      $_rivi_check[$tilausrivi_row['tuoteno']] = $tilausrivi_row['tilaajanrivinro'];
    }

    $kpl = $tilausrivi_row['varattu'];

    // HUOM: Reklamaatioissa laitetaan nettohyvitysyksikköhinta
    $hinta = $tilausrivi_row['hinta'] * generoi_alekentta_php($tilausrivi_row, 'M', 'kerto');

    // nettorivihinta
    $rivihinta = $kpl * $hinta;

    $tuotenumero = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", " ", $tilausrivi_row['tuoteno']);
    $tuotteen_nimitys = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", " ", $tilausrivi_row['nimitys']);

    if ($tilausrivi_row['tilaajanrivinro'] != 0) {
      $_rivinro = $tilausrivi_row['tilaajanrivinro'];
    }
    else {
      $_rivinro = $i;
    }

    if ($asiakasrow['kerayserat'] == 'A' and $kpl != 0) {
      $query = "SELECT distinct sscc_ulkoinen
                FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi = '{$tilausrivi_row['tunnus']}'";
      $kerayserares = pupe_query($query);
      $keraysera_row = mysql_fetch_assoc($kerayserares);
      $_sscc = $keraysera_row['sscc_ulkoinen'];
    }
    else {
      $_sscc = "";
    }

    $edi_order .= "*RS OSTOTILRIV {$i}\n";
    $edi_order .= "OSTOTILRIV.OTR_NRO:{$laskurow['asiakkaan_tilausnumero']}\n";
    $edi_order .= "OSTOTILRIV.OTR_RIVINRO:{$_rivinro}\n";
    $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
    $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tuotenumero}\n";
    $edi_order .= "OSTOTILRIV.OTR_EANKOODI:{$tilausrivi_row['eankoodi']}\n";
    $edi_order .= "OSTOTILRIV.OTR_SSCC:{$_sscc}\n";
    $edi_order .= "OSTOTILRIV.OTR_NIMI:{$tuotteen_nimitys}\n";
    $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";
    $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$rivihinta}\n";
    $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$tilausrivi_row['hinta']}\n";

    $ale_default = array('ale1' => '', 'ale2' => '', 'ale3' => '');
    $ale = generoi_alekentta_php($tilausrivi_row, $myynti_vai_osto, 'erikseen') + $ale_default;

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      $edi_order .= "OSTOTILRIV.OTR_ALENNUS_{$alepostfix}:".$ale["ale".$alepostfix]."\n";
    }

    $edi_order .= "OSTOTILRIV.OTR_ERIKOISALENNUS:{$laskurow['erikoisale']}\n";
    $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$tilausrivi_row['alv']}\n";
    $edi_order .= "OSTOTILRIV.OTR_VIITE:{$tilausrivi_row['kommentti']}\n";
    $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:{$tilausrivi_row['yksikko']}\n";
    $edi_order .= "*RE OSTOTILRIV {$i}\n";
    $i++;
  }

  if ($i > 1) {

    $edi_order .= "*ME\n";
    $edi_order .= "*IE\n";

    $dir = "{$pupe_root_polku}/dataout/apix_error/";
    $filename = "out_apix_{$laskurow['tunnus']}.dat";

    file_put_contents($dir.$filename, $edi_order);

    $status = apix_edi_put_file($filename, $laskurow['tunnus']);
    echo "APIX-lähetys {$status}<br>\n";
  }
}
