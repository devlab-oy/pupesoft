<?php

function viidakko_hae_tuotteet($tyyppi = "viidakko_tuotteet") {
  global $kukarow, $yhtiorow, $viidakko_varastot, $ajetaanko_kaikki, $viidakko_kuvaurl;

  viidakko_echo("Haetaan kaikki tuotteet");

  $tuoterajaus = viidakko_tuoterajaus();

  if (!is_array($viidakko_varastot)) {
    die('viidakko varastot ei ole array!');
  }

  // Haetaan aika nyt
  $query = "SELECT now() as aika";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $aloitusaika = $row['aika'];

  if (isset($viidakko_kuvaurl) and $viidakko_kuvaurl != '') {
    if (substr($viidakko_kuvaurl, -1) == '/') {
      $viidakko_kuvaurl = substr($viidakko_kuvaurl, 0, -1);
    }
  }
  else {
    $viidakko_kuvaurl = '';
  }

  if ($tyyppi == "viidakko_saldot") {
    $datetime_checkpoint = cron_aikaleima("VIID_SALDO_CRON");
  }
  else {
    $datetime_checkpoint = cron_aikaleima("VIID_TUOTE_CRON");
  }

  pupesoft_log($tyyppi, "Aloitetaan tuotehaku {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log($tyyppi, "Haetaan {$datetime_checkpoint} jälkeen muuttuneet");

    $muutoslisa1 = "AND tapahtuma.laadittu  >= '{$datetime_checkpoint}'";
    $muutoslisa2 = "AND tilausrivi.laadittu >= '{$datetime_checkpoint}'";
    $muutoslisa3 = "AND tuote.muutospvm     >= '{$datetime_checkpoint}'";

    // Haetaan tuotteet, joille on tehty tunnin sisällä tilausrivi tai tapahtuma
    $query =  "(SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi,
                tuotteen_avainsanat.tunnus
                FROM tapahtuma
                JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
                  AND tuote.tuoteno = tapahtuma.tuoteno
                  {$tuoterajaus})
                LEFT JOIN tuotteen_avainsanat ON (
                  tuotteen_avainsanat.yhtio = tuote.yhtio
                  AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                  AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                  AND tuotteen_avainsanat.kieli = 'fi'
                )
                WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
                AND tuotteen_avainsanat.tunnus is null
                {$muutoslisa1})

                UNION

                (SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi,
                tuotteen_avainsanat.tunnus
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                  AND tuote.tuoteno = tilausrivi.tuoteno
                  {$tuoterajaus})
                LEFT JOIN tuotteen_avainsanat ON (
                  tuotteen_avainsanat.yhtio = tuote.yhtio
                  AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                  AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                  AND tuotteen_avainsanat.kieli = 'fi'
                )
                WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
                AND tuotteen_avainsanat.tunnus is null
                {$muutoslisa2})

                UNION

                (SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi,
                tuotteen_avainsanat.tunnus
                FROM tuote
                LEFT JOIN tuotteen_avainsanat ON (
                  tuotteen_avainsanat.yhtio = tuote.yhtio
                  AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                  AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                  AND tuotteen_avainsanat.kieli = 'fi'
                )
                WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                AND tuotteen_avainsanat.tunnus is null
                {$tuoterajaus}
                {$muutoslisa3})

                ORDER BY 1";
  }
  else {
    $query = "SELECT
              tuote.tunnus,
              tuote.tuoteno,
              tuote.eankoodi,
              tuotteen_avainsanat.tunnus
              FROM tuote
              LEFT JOIN tuotteen_avainsanat ON (
                tuotteen_avainsanat.yhtio = tuote.yhtio
                AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                AND tuotteen_avainsanat.kieli = 'fi'
              )
              WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              AND tuotteen_avainsanat.tunnus is null
              {$tuoterajaus}";
  }

  $res = pupe_query($query);

  if ($tyyppi == "viidakko_saldot") {
    cron_aikaleima("VIID_SALDO_CRON", $aloitusaika);
  }
  else {
    cron_aikaleima("VIID_TUOTE_CRON", $aloitusaika);
  }

  $data = array();
  $i    = 0;

  while ($row = mysql_fetch_assoc($res)) {

    $tuoteno = $row['tuoteno'];

    list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $viidakko_varastot);

    $query = "  SELECT *
                FROM tuotteen_avainsanat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '$tuoteno'
                AND laji = 'viidakko_tuoteno'";
    $avainsana_res = pupe_query($query);
    $avainsana_row = mysql_fetch_assoc($avainsana_res);

    $_id = $avainsana_row['selite'];

    if ($tyyppi == "viidakko_saldot") {
      $data[] = array(array(
          "code"  => utf8_encode($tuoteno),
          "stock" => $myytavissa,
        )
      );
    }
    elseif ($tyyppi == "viidakko_kuvat") {

      $product_row = product_row($tuoteno);

      $liite_tk_url = "";

      //kuvalinkit tarvittaessa
      if ($viidakko_kuvaurl != '') {

        // normaalikuva
        $query = "SELECT liitetiedostot.tunnus,
                  liitetiedostot.external_id,
                  if(liitetiedostot.jarjestys = 0, 9999, liitetiedostot.jarjestys) jarjestys,
                  liitetiedostot.kieli,
                  liitetiedostot.selite,
                  liitetiedostot.tunnus,
                  ( SELECT if(max(max.muutospvm) != '0000-00-00 00:00:00', max(max.muutospvm), max(max.luontiaika))
                    FROM liitetiedostot max
                    WHERE max.yhtio = liitetiedostot.yhtio
                    AND max.liitos  = liitetiedostot.liitos
                    AND max.liitostunnus = liitetiedostot.liitostunnus
                    AND max.kayttotarkoitus = liitetiedostot.kayttotarkoitus
                  ) muutospvm
                  FROM liitetiedostot
                  WHERE liitetiedostot.yhtio = '{$kukarow['yhtio']}'
                  AND liitetiedostot.liitos  = 'tuote'
                  AND liitetiedostot.liitostunnus = '{$product_row['tunnus']}'
                  AND liitetiedostot.kayttotarkoitus = 'TK'
                  ORDER BY if(liitetiedostot.jarjestys = 0, 9999, liitetiedostot.jarjestys)";
        $result = pupe_query($query);

        $ii = 0;

        while ($liite_row = mysql_fetch_assoc($result)) {

          $liite_tk_url = "{$viidakko_kuvaurl}/view.php?id={$liite_row['tunnus']}";

          // for testing..
          if ($liite_row['tunnus'] != '24875') $liite_tk_url = "https://www.sprintit.fi/website/image/ir.attachment/5509_4ac6bcc/datas";

          // X = delete all pics and then install
          // Y = just install, pics have been deleted already
          // "" = do nothing, no updates
          $updated = (1==1 or $liite_row['muutospvm'] > $datetime_checkpoint);
          if ($updated and $ii == 0) {
            $_updated = "X";
          }
          elseif ($updated and $ii > 0) {
            $_updated = "Y";
          }
          else {
            $_updated = "";
          }

          if (empty($liite_row['selite'])) $liite_row['selite'] = "Tuotekuva";

          $data[$i] = array(
            "id"          => $_id,
            "code"        => utf8_encode($tuoteno),
            "image_id"    => (int) $liite_row['external_id'],
            "image"       => $liite_tk_url,
            "title" => array(
              array(
                "language"=> utf8_encode(strtoupper($liite_row['kieli'])),
                "title"   => utf8_encode($liite_row['selite'])
              )
            ),
            "description" => array(
              array(
                "language"    => utf8_encode(strtoupper($liite_row['kieli'])),
                "description" => utf8_encode($liite_row['selite'])
              )
            ),
            "position"    => (int) $liite_row['jarjestys'],
            "is_default"  => (bool) false,
            "is_teaser"   => (bool) false,
            "liitetiedostot_tunnus" => $liite_row['tunnus'],
            "updated" => $_updated,
          );

          if ($ii == 0) {
            $data[$i]['is_default'] = (bool) true;
            $data[$i]['is_teaser'] = (bool) true;
          }
          $i++;
          $ii++;
        }
      }
    }
    else {

      $product_row = product_row($tuoteno);

      //  haetaan kielikäännökset avainsanoista
      $query = "  SELECT *
                  FROM tuotteen_avainsanat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tuoteno}'
                  AND laji in ('kuvaus', 'nimitys')
                  AND kieli = 'en'";
      $avainsana_res = pupe_query($query);
      $nimitys_en = $kuvaus_en = "";

      while ($avainsana_row = mysql_fetch_assoc($avainsana_res)) {
        if ($avainsana_row['laji'] == 'nimitys') {
          $nimitys_en = $avainsana_row['selite'];
        }
        elseif ($avainsana_row['laji'] == 'kuvaus') {
          $kuvaus_en = $avainsana_row['selite'];
        }
      }

      if (empty($nimitys_en)) {
        $nimitys_en = $product_row['nimitys'];
      }

      if (empty($kuvaus_en)) {
        $kuvaus_en = $product_row['kuvaus'];
      }

      $try_res = t_avainsana("TRY", "", "and avainsana.selite  = '{$product_row['try']}'");
      $try_row = mysql_fetch_assoc($try_res);
      $try = trim($try_row['selitetark_3']);

      // tyhjä = verolliset, x = verottomat
      // viidakkoon siirretään verollisena
      if ($yhtiorow['alv_kasittely'] != '') {
        $product_row['myyntihinta'] = $product_row['myyntihinta'] * ((100+$product_row['alv'])/100);
      }

      if ($product_row['status'] == 'P') {
        $hidden = true;
      }
      else {
        $hidden = false;
      }

      $data[] = array(
        "id"                      => $_id,
        "product_code"            => utf8_encode($tuoteno),
        "erp_id"                  => utf8_encode($tuoteno),
        "category"                => (float)  $try,
        "ean_code"                => $product_row['eankoodi'],
        "stock"                   => $myytavissa,
        "names"                   => array(
          array(
            "language" => "FI",
            "name" => utf8_encode($product_row['nimitys'])
          ),
          array(
            "language" => "EN",
            "name" => utf8_encode($nimitys_en),
          ),
        ),
        "base_price"              => (float) $product_row['myyntihinta'],
        "hidden"                  => $hidden,
        #"" => "",
        #"" => "",
        #"" => "",
        #"supplier_code"           => "", #todo
        "descriptions"            => array(
          array(
            "language" => "FI",
            "description" => utf8_encode($product_row['kuvaus']),
          ),
          array(
            "language" => "EN",
            "description" => utf8_encode($kuvaus_en),
          ),
        ),
        "inventory_price"         => (float) $product_row['kehahin'],
        #"msrp"                    => "",
        "vat_percent"             => (float) $product_row['alv'],
        #"use_default_vat_percent" => "",
        #"availability_begins_at"  => "",
        #"availability_ends_at"    => "",
        #"delivery_cost_unit" => "",      # product weight!
      );
    }
  }

  return $data;
}

function viidakko_tuoterajaus() {
  $tuoterajaus = " AND tuote.tuoteno != ''
                   AND tuote.ei_saldoa = ''
                   AND tuote.tuotetyyppi NOT in ('A','B')
                   AND tuote.status != 'P'
                   AND tuote.nimitys != ''
                   AND tuote.tuoteno = 'TAIGA99' # for testing..
                   #AND tuote.try = '57'
                   #AND tuote.hinnastoon in ('W')";

  return $tuoterajaus;
}

function viidakko_ajetaanko_sykronointi($ajo, $ajolista) {
  // jos ajo ei ole ajolistalla, ei ajeta
  if (array_search(strtolower(trim($ajo)), $ajolista) === false) {
    return false;
  }

  // Sallitaan vain yksi instanssi tästä ajosta kerrallaan
  $lock_params = array(
    "lockfile" => "viidakko-{$ajo}-flock.lock",
    "locktime" => 5400,
    "return"   => true,
  );

  $status = pupesoft_flock($lock_params);

  if ($status === false) {
    viidakko_echo("{$ajo} -ajo on jo käynnissä, ei ajeta uudestaan.");
  }

  return $status;
}

function viidakko_echo($string) {
  if ($GLOBALS['viidakko_debug'] !== true) {
    return;
  }

  echo date("d.m.Y @ G:i:s")." - {$string}\n";
}

function product_row($tuoteno) {
  global $kukarow;

  // haetaan tuotetiedot
  $query = "  SELECT *
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'
              and tuoteno = '{$tuoteno}'";
  $res2 = pupe_query($query);

  return mysql_fetch_assoc($res2);
}

function viidakko_hae_variaatiot() {
  global $kukarow, $yhtiorow, $viidakko_varastot, $ajetaanko_kaikki, $viidakko_kuvaurl;

  viidakko_echo("Haetaan kaikki variaatiotuotteet");

  $tuoterajaus = viidakko_tuoterajaus();

  if (!is_array($viidakko_varastot)) {
    die('viidakko varastot ei ole array!');
  }

  // Haetaan aika nyt
  $query = "SELECT now() as aika";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $aloitusaika = $row['aika'];

  if (isset($viidakko_kuvaurl) and $viidakko_kuvaurl != '') {
    if (substr($viidakko_kuvaurl, -1) == '/') {
      $viidakko_kuvaurl = substr($viidakko_kuvaurl, 0, -1);
    }
  }
  else {
    $viidakko_kuvaurl = '';
  }

  $datetime_checkpoint = cron_aikaleima("VIID_VARIA_CRON");

  pupesoft_log($tyyppi, "Aloitetaan variaatioiden tuotehaku {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log($tyyppi, "Haetaan {$datetime_checkpoint} jälkeen muuttuneet");

    $muutoslisa1 = "AND tapahtuma.laadittu  >= '{$datetime_checkpoint}'";
    $muutoslisa2 = "AND tilausrivi.laadittu >= '{$datetime_checkpoint}'";
    $muutoslisa3 = "AND tuote.muutospvm     >= '{$datetime_checkpoint}'";

    // Haetaan variaatiotuotteet, joille on tehty tunnin sisällä tilausrivi tai tapahtuma
    $query =  "(SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi
                FROM tapahtuma
                JOIN tuote ON (
                  tuote.yhtio = tapahtuma.yhtio
                  AND tuote.tuoteno = tapahtuma.tuoteno
                  {$tuoterajaus}
                )
                JOIN tuotteen_avainsanat ON (
                  tuotteen_avainsanat.yhtio = tuote.yhtio
                  AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                  AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                  AND tuotteen_avainsanat.kieli = 'fi'
                )
                WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
                {$muutoslisa1})

                UNION

                (SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi
                FROM tilausrivi
                JOIN tuote ON (
                  tuote.yhtio = tilausrivi.yhtio
                  AND tuote.tuoteno = tilausrivi.tuoteno
                  {$tuoterajaus}
                )
                JOIN tuotteen_avainsanat ON (
                  tuotteen_avainsanat.yhtio = tuote.yhtio
                  AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                  AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                  AND tuotteen_avainsanat.kieli = 'fi'
                )
                WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
                {$muutoslisa2})

                UNION

                (SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi
                FROM tuote
                JOIN tuotteen_avainsanat ON (
                  tuotteen_avainsanat.yhtio = tuote.yhtio
                  AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                  AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                  AND tuotteen_avainsanat.kieli = 'fi'
                )
                WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                {$tuoterajaus}
                {$muutoslisa3})

                ORDER BY 1";
  }
  else {
    $query = "SELECT
              tuote.tunnus,
              tuote.tuoteno,
              tuote.eankoodi
              FROM tuote
              JOIN tuotteen_avainsanat ON (
                tuotteen_avainsanat.yhtio = tuote.yhtio
                AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                AND tuotteen_avainsanat.laji = 'parametri_variaatio'
                AND tuotteen_avainsanat.kieli = 'fi'
              )
              WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              {$tuoterajaus}";
  }

  $res = pupe_query($query);

  cron_aikaleima("VIID_VARIA_CRON", $aloitusaika);

  $data = array();
  $i    = 0;

  while ($row = mysql_fetch_assoc($res)) {

  }
}
