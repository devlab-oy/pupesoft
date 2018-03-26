<?php

function viidakko_hae_tuotteet($tyyppi = "viidakko_tuotteet") {
  global $kukarow, $yhtiorow, $viidakko_varastot, $ajetaanko_kaikki;

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

    if ($tyyppi == "viidakko_saldot") {
      // normituote
      list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $viidakko_varastot);

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

      $tuotteet[] = array(
        "product_code"            => $tuoteno,
        "category"                => "", #todo
        "ean_code"                => $row['eankoodi'],
        "names"                       => array(
          "fi"                        => "$row['nimitys']",
          "en"                        => $nimitys_en),
        "base_price"              => $product_row['myyntihinta'],
        "supplier_code"           => "", #todo
        "descriptions"                => array(
          "fi"                        => $product_row['kuvaus'],
          "en"                        => $kuvaus_en),
        "image"                   => $row,
        "teaser_image"            => $row,
        "inventory_price"         => $product_row['kehahin'],
        "msrp"                    => "", #todo
        "vat_percent"             => $row['alv'],
        "use_default_vat_percent" => $row,
        "availability_begins_at"  => $row,
        "availability_ends_at"    => $row,
      );
    }

/* jeesiä varten
$data_json = json_encode(array( "product_code"                => "{$product_row["tuoteno"]}",
                                "ean_code"                    => "{$product_row["tuoteno"]}",
                                "category"                    => "{$product_row["tuoteno"]}",
                                "names"                       => array(
                                  "fi"                        => "",
                                  "en"                        => "",),
                                "base_price"                  => "{$product_row["tuoteno"]}",
                                "stock"                       => "", #??????
                                "supplier_code"               => "",
                                "descriptions"                => array(
                                  "fi"                        => "",
                                  "en"                        => "",),
                                "image"                       => "{$product_row["tuoteno"]}",
                                "teaser_image"                => "{$product_row["tuoteno"]}",
                                "inventory_price"             => "{$product_row["tuoteno"]}",
                                "msrp"                        => "{$product_row["tuoteno"]}",
                                "vat_percent"                 => "{$product_row["tuoteno"]}",
                                "use_default_vat_percent"     => "{$product_row["alv"]}",
                                "availability_begins_at"      => "",
                                "availability_ends_at"        => "",
                              ));
*/
  }

  return $tuotteet;
}

function viidakko_tuoterajaus() {
  $tuoterajaus = " AND tuote.tuoteno != ''
                   AND tuote.ei_saldoa = ''
                   AND tuote.tuotetyyppi NOT in ('A','B')
                   AND tuote.status != 'P'
                   AND hinnastoon in ('','W') ";

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
