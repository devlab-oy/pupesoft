<?php

function viidakko_hae_tuotteet($tyyppi = "viidakko_tuotteet") {
  global $kukarow, $yhtiorow, $viidakko_varastot, $ajetaanko_kaikki, $viidakko_kuvaurl;

  viidakko_echo("Haetaan kaikki tuotteet ja varastosaldot.");

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
                tuote.eankoodi
                FROM tapahtuma
                JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
                  AND tuote.tuoteno = tapahtuma.tuoteno
                  {$tuoterajaus})
                WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
                {$muutoslisa1})

                UNION

                (SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                  AND tuote.tuoteno = tilausrivi.tuoteno
                  {$tuoterajaus})
                WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
                {$muutoslisa2})

                UNION

                (SELECT
                tuote.tunnus,
                tuote.tuoteno,
                tuote.eankoodi
                FROM tuote
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
              WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              {$tuoterajaus}";
  }

  $res = pupe_query($query);

  if ($tyyppi == "viidakko_saldot") {
    cron_aikaleima("VIID_SALDO_CRON", $aloitusaika);
  }
  else {
    cron_aikaleima("VIID_TUOTE_CRON", $aloitusaika);
  }

  $tuotteet = array();

  while ($row = mysql_fetch_array($res)) {
    $tuoteno = $row['tuoteno'];

    list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $viidakko_varastot);

    if ($tyyppi == "viidakko_saldot") {
      // normituote
      $tuotteet[] = array(
        "product_code"            => $tuoteno,
        "stock"                   => $myytavissa,
      );
    }
    else {
      $myytavissa = 0;

      //  haetaan loput tuotetiedot
      $query = "  SELECT *
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  {$tuoterajaus}";
      $res2 = pupe_query($query);
      $product_row = mysql_fetch_array($res2);

      //  haetaan kielikäännökset avainsanoista
      $query = "  SELECT *
                  FROM tuotteen_avainsanat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '$tuoteno'
                  AND laji in ('kuvaus', 'nimitys')";
      $avainsana_res = pupe_query($query);
      $nimitys_en = $kuvaus_en = "";

      while ($avainsana_row = mysql_fetch_array($avainsana_res)) {
        if ($avainsana_row['laji'] == 'nimitys') {
          $nimitys_en = $avainsana_row['selite'];
        }
        elseif ($avainsana_row['laji'] == 'kuvaus') {
          $kuvaus_en = $avainsana_row['selite'];
        }
      }

      $liite_tk_url = "";
      $liite_th_url = "";

      //kuvalinkit tarvittaessa
      if ($viidakko_kuvaurl != '') {

        // normaalikuva
        $query = "SELECT liitetiedostot.*
                  FROM liitetiedostot
                  WHERE liitetiedostot.yhtio = '{$kukarow['yhtio']}'
                  AND liitetiedostot.liitos  = 'tuote'
                  AND liitetiedostot.liitostunnus = '{$row['tunnus']}'
                  AND liitetiedostot.kayttotarkoitus = 'TK'
                  ORDER BY if(liitetiedostot.jarjestys = 0, 9999, liitetiedostot.jarjestys)
                  LIMIT 1";
        $result = pupe_query($query);
        if (mysql_num_rows($result) == 1) {
          $liite_row = mysql_fetch_array($result);
          $liite_tk_url = "{$viidakko_kuvaurl}/view.php?id={$liite_row['tunnus']}";
        }

        // thumbnail
        $query = "SELECT liitetiedostot.tunnus
                  FROM liitetiedostot
                  WHERE liitetiedostot.yhtio = '{$kukarow['yhtio']}'
                  AND liitetiedostot.liitos  = 'tuote'
                  AND liitetiedostot.liitostunnus = '{$row['tunnus']}'
                  AND liitetiedostot.kayttotarkoitus = 'TH'
                  ORDER BY if(liitetiedostot.jarjestys = 0, 9999, liitetiedostot.jarjestys)
                  LIMIT 1";
        $result = pupe_query($query);
        if (mysql_num_rows($result) == 1) {
          $liite_row = mysql_fetch_array($result);
          $liite_th_url = "{$viidakko_kuvaurl}/view.php?id={$liite_row['tunnus']}";
        }
      }

      $tuotteet[] = array(
        "product_code"            => $tuoteno,
        "category"                => "1", #todo
        "ean_code"                => $row['eankoodi'],
        "stock"                   => $myytavissa,
        "ean_code"                => $product_row['eankoodi'],
        "names"                       => array(
          array(
            "language" => "FI",
            "name" => $row['nimitys']
          ),
          array(
            "language" => "EN",
            "name" => $nimitys_en
          ),
        ),
        "base_price"              => $product_row['myyntihinta'],
        "hidden"                  => false,
        "" => "",
        "" => "",
        "" => "",
        "supplier_code"           => "", #todo
        "descriptions"                       => array(
          array(
            "language" => "FI",
            "description" => $product_row['kuvaus']
          ),
          array(
            "language" => "EN",
            "description" => $kuvaus_en
          ),
        ),
        "images"                       => array(
          array(
            "language" => "FI",
            "image" => $liite_tk_url
          ),
          array(
            "language" => "EN",
            "image" => $liite_tk_url
          ),
        ),
        "teaser_image"            => $liite_th_url,
        "inventory_price"         => $product_row['kehahin'],
        #"msrp"                    => "",
        "vat_percent"             => $product_row['alv'],
        #"use_default_vat_percent" => "",
        #"availability_begins_at"  => "",
        #"availability_ends_at"    => "",
      );
    }
  }

  return $tuotteet;
}

function viidakko_tuoterajaus() {
  $tuoterajaus = " AND tuote.tuoteno != ''
                   AND tuote.ei_saldoa = ''
                   AND tuote.tuotetyyppi NOT in ('A','B')
                   AND tuote.status != 'P'
                   AND tuote.hinnastoon in ('W')";

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
