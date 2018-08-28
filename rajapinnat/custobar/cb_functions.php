<?php

function cb_hae_asiakkaat() {
  global $kukarow, $yhtiorow, $ajetaanko_kaikki, $cb_shortname;

  cb_echo("Haetaan kaikki asiakkaat.");

  $asiakasrajaus = cb_asiakasrajaus();

  // Haetaan aika nyt
  $query = "SELECT now() as aika";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $aloitusaika = $row['aika'];

  // Haetaan aika jolloin tämä skripti on viimeksi ajettu
  $datetime_checkpoint = cron_aikaleima("CB_AS_CRON");

  pupesoft_log("cb_customers", "Aloitetaan asiakaspäivitys {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log("cb_customers", "Haetaan {$datetime_checkpoint} jälkeen muuttuneet");

    $muutoslisa1 = "AND (luontiaika  >= '{$datetime_checkpoint}' or muutospvm >= '{$datetime_checkpoint}')";
  }
  else {
    $muutoslisa1 = "";
  }

  // Haetaan asiakkaat
  $query =  "SELECT *, if(tyopuhelin != '', tyopuhelin,
                  if(gsm != '', gsm, puhelin)) AS as_puh
             FROM asiakas
             WHERE yhtio = '{$kukarow["yhtio"]}'
             {$muutoslisa1}
             {$asiakasrajaus}";

  $res = pupe_query($query);

  $asiakkaat = array();

  while ($row = mysql_fetch_array($res)) {

    // yhteyshenkilo
    $yhtquery = "SELECT nimi
                 FROM yhteyshenkilo
                 WHERE yhtio              = '{$kukarow['yhtio']}'
                 AND liitostunnus         = '{$row['tunnus']}'
                 AND tyyppi               = 'A'
                 ORDER BY oletusyhteyshenkilo desc,
                 tilausyhteyshenkilo desc,
                 nimi
                 LIMIT 1";
    $yhtresult = pupe_query($yhtquery);
    $yht = mysql_fetch_assoc($yhtresult);

    // Magento-yhteyshenkilo
    $yhtquery = "SELECT ulkoinen_asiakasnumero
                 FROM yhteyshenkilo
                 WHERE yhtio              = '{$kukarow['yhtio']}'
                 AND liitostunnus         = '{$row['tunnus']}'
                 AND tyyppi               = 'A'
                 AND rooli                = 'MAGENTO'
                 AND ulkoinen_asiakasnumero != ''
                 LIMIT 1";
    $magyhtresult = pupe_query($yhtquery);
    $magyht = mysql_fetch_assoc($magyhtresult);

    if (trim($row["toim_nimi"]) == "") {
      $row["toim_nimi"]     = $row["nimi"];
      $row["toim_nimitark"] = $row["nimitark"];
      $row["toim_osoite"]   = $row["osoite"];
      $row["toim_postino"]  = $row["postino"];
      $row["toim_postitp"]  = $row["postitp"];
      $row["toim_maa"]      = $row["maa"];
    }

    if (!empty($yht['nimi'])) {
      list($etunimi, $sukunimi) = explode(" ", $yht['nimi']);
    }
    else {
      $etunimi = $row["toim_nimi"] . " " . $row["toim_nimitark"];
      $sukunimi = "";
    }

    $row['kieli'] = strtoupper($row['kieli']);
    if ($row['kieli'] == 'SE') {
      $row['kieli'] = "SV";
    }
    elseif ($row['kieli'] == 'FI') {
      $row['kieli'] = "FI";
    }
    else {
      $row['kieli'] = "EN";
    }

    $piiri_res = t_avainsana("PIIRI", "", "and avainsana.selite  = '{$row['piiri']}'");
    $piiri_row = mysql_fetch_assoc($piiri_res);
    $piiri = $piiri_row['selitetark'];

    $osasto_res = t_avainsana("ASIAKASOSASTO", "", "and avainsana.selite  = '{$row['osasto']}'");
    $osasto_row = mysql_fetch_assoc($osasto_res);
    $osasto = $osasto_row['selitetark'];

    $ryhma_res = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite  = '{$row['ryhma']}'");
    $ryhma_row = mysql_fetch_assoc($ryhma_res);
    $ryhma = $ryhma_row['selitetark'];

    $row['toim_nimi']       = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $row['toim_nimi']))));
    $row['toim_nimitark']   = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $row['toim_nimitark']))));
    $row['toim_osoite']     = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $row['toim_osoite']))));
    $row['toim_postitp']    = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $row['toim_postitp']))));
    $etunimi                = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $etunimi))));
    $sukunimi               = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $sukunimi))));
    $piiri                  = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $piiri))));
    $osasto                 = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $osasto))));
    $ryhma                  = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $ryhma))));
    $row['as_puh']          = trim(pupesoft_cleanstring(pupesoft_csvstring(str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), $row['as_puh']))));


    // lisätään asiakkaan päivittämiseen tarvittavat tiedot
    $asiakkaat[] = array(
      "external_id"     => $row['tunnus'],
      "phone_number"    => $row['as_puh'],
      "email"           => $row['email'],
      "first_name"      => $etunimi,
      "last_name"       => $sukunimi,
      "date_joined"     => $row['luontiaika'],
      "company"         => $row['toim_nimi'] . " " . $row['toim_nimitark'],
      "street_address"  => $row['toim_osoite'],
      "zip_code"        => $row['toim_postino'],
      "city"            => $row['toim_postitp'],
      "country"         => $row['toim_maa'],
      "language"        => $row['kieli'],
      "vat_number"      => $row['ytunnus'],
      "{$cb_shortname}__asiakasnro" => $row['asiakasnro'],
      "{$cb_shortname}__magento_id" => $magyht['ulkoinen_asiakasnumero'],
      "{$cb_shortname}__piiri"      => $piiri,
      "{$cb_shortname}__osasto"     => $osasto,
      "{$cb_shortname}__ryhma"      => $ryhma,
    );
  }

  cron_aikaleima("CB_AS_CRON", $aloitusaika);

  return $asiakkaat;
}

function cb_asiakasrajaus() {
  $rajaus = " AND asiakas.laji in ('', 'H')";
  return $rajaus;
}

function cb_hae_myynnit() {
  global $kukarow, $yhtiorow, $ajetaanko_kaikki, $cb_shortname;

  cb_echo("Haetaan kaikki myynnit.");

  // Haetaan aika nyt
  $query = "SELECT now() as aika";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $aloitusaika = $row['aika'];

  // Haetaan aika jolloin tämä skripti on viimeksi ajettu
  $datetime_checkpoint = cron_aikaleima("CB_MY_CRON");

  pupesoft_log("cb_sales", "Aloitetaan myyntien päivitys {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log("cb_sales", "Haetaan {$datetime_checkpoint} jälkeen muodostuneet laskut");

    $muutoslisa1 = "AND lasku.laskutettu  >= '{$datetime_checkpoint}'";
  }
  else {
    $muutoslisa1 = " AND lasku.laskutettu >= '2018-01-01'";
  }

  // Haetaan laskut
  $query =  "SELECT tilausrivi.*, lasku.*, lasku.tunnus as laskutunnus, tilausrivi.tunnus as tilausrivitunnus
             FROM lasku
             JOIN tilausrivi ON (
              tilausrivi.yhtio = lasku.yhtio AND
              tilausrivi.uusiotunnus = lasku.tunnus AND
              tilausrivi.tyyppi = 'L' AND
              tilausrivi.var not in ('P','J','O','S')
             )
             WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
             AND lasku.tila = 'U'
             AND lasku.alatila = 'X'
             AND lasku.laskunro != 0
             {$muutoslisa1}";

  $res = pupe_query($query);

  $laskut     = array();
  $maksuehdot = array();

  // Haetaan maksuehdot
  $query =  "SELECT *
             FROM maksuehto
             WHERE yhtio = '{$kukarow["yhtio"]}'";
  $maksuehtores = pupe_query($query);
  while ($row = mysql_fetch_array($maksuehtores)) {
    $maksuehdot[$row['tunnus']] = $row['teksti'];
  }

  $summa = 0;

  while ($row = mysql_fetch_array($res)) {

    $maksuehdot[$row['maksuehto']]  = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($maksuehdot[$row['maksuehto']]))));
    $row['tuoteno']                 = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['tuoteno']))));

    // lisätään laskun lisäämiseen tarvittavat tiedot
    $laskut[] = array(
      "sale_external_id"          => $row['laskutunnus'],
      "sale_customer_id"          => $row['liitostunnus'],
      "sale_date"                 => $row['laskutettu'],
      "sale_payment_method"       => $maksuehdot[$row['maksuehto']],
      "sale_state"                => "COMPLETE",
      "sale_shop_id"              => $row['ohjelma_moduli'],
      "external_id"               => $row['tilausrivitunnus'],
      "product_id"                => $row['tuoteno'],
      "unit_price"                => round($row['rivihinta'] / $row['kpl'] * 100, 0),
      "quantity"                  => $row['kpl'],
      "total"                     => round($row['rivihinta'] * 100, 0),
    );

    $summa = $summa + round($row['rivihinta'] * 100, 0);

    unset($row);
  }

  if (!empty($summa)) $laskut['sale_total'] = $summa;

  cron_aikaleima("CB_MY_CRON", $aloitusaika);

  return $laskut;
}

function cb_hae_tuotteet() {
  global $kukarow, $yhtiorow, $ajetaanko_kaikki, $cb_shortname, $cb_picture_url;

  cb_echo("Haetaan kaikki tuotteet.");

  // Haetaan aika nyt
  $query = "SELECT now() as aika";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $aloitusaika = $row['aika'];

  // Haetaan aika jolloin tämä skripti on viimeksi ajettu
  $datetime_checkpoint = cron_aikaleima("CB_TU_CRON");

  pupesoft_log("cb_products", "Aloitetaan tuotteiden päivitys {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log("cb_products", "Haetaan {$datetime_checkpoint} jälkeen muodostuneet laskut");

    $muutoslisa1 = "AND (luontiaika  >= '{$datetime_checkpoint}' or muutospvm  >= '{$datetime_checkpoint}')";
  }
  else {
    $muutoslisa1 = "";
  }

  // Haetaan tuotteet
  $query =  "SELECT *
             FROM tuote
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND tuotetyyppi NOT IN ('A', 'B')
             {$muutoslisa1}";

  $res = pupe_query($query);

  $tuotteet     = array();
  $i = 0;

  while ($row = mysql_fetch_array($res)) {

    $liite_tk_url = "";

    if (isset($cb_picture_url) and !empty($cb_picture_url)) {
      // normaalikuva
      $query = "SELECT liitetiedostot.tunnus
                FROM liitetiedostot
                WHERE liitetiedostot.yhtio = '{$kukarow['yhtio']}'
                AND liitetiedostot.liitos  = 'tuote'
                AND liitetiedostot.liitostunnus = '{$row['tunnus']}'
                AND liitetiedostot.kayttotarkoitus = 'TK'
                ORDER BY if(liitetiedostot.jarjestys = 0, 9999, liitetiedostot.jarjestys)
                LIMIT 1";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $liite_row = mysql_fetch_assoc($result);
        $liite_tk_url = "{$cb_picture_url}/view.php?id={$liite_row['tunnus']}";
      }
    }

    $query = "SELECT toimi.nimi, toimi.nimitark, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
              FROM tuotteen_toimittajat
              JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio
                AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
              WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
              and tuotteen_toimittajat.tuoteno = '{$row['tuoteno']}'
              ORDER BY sorttaus
              LIMIT 1";
    $otres = pupe_query($query);
    $otrow = mysql_fetch_assoc($otres);
    $toimittaja = $otrow['nimi'] . " " . $otrow['nimitark'];

    $osasto_res = t_avainsana("OSASTO", "", "and avainsana.selite  = '{$row['osasto']}'");
    $osasto_row = mysql_fetch_assoc($osasto_res);
    $osasto = $osasto_row['selitetark'];

    $try_res = t_avainsana("TRY", "", "and avainsana.selite  = '{$row['try']}'");
    $try_row = mysql_fetch_assoc($try_res);
    $try = $try_row['selitetark'];

    $row['nakyvyys'] = str_replace("_", "-", $row['nakyvyys']);

    $osasto               = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($osasto))));
    $row['tuoteno']       = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['tuoteno']))));
    $try                  = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($try))));
    $toimittaja           = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($toimittaja))));
    $row['tuotemerkki']   = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['tuotemerkki']))));
    $row['nimitys']       = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['nimitys']))));
    $row['lyhytkuvaus']   = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['lyhytkuvaus']))));
    $row['yksikko']       = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['yksikko']))));
    $row['mainosteksti']  = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['mainosteksti']))));
    $row['nakyvyys']      = str_replace(array('Ä','ä','Ö','ö','Å','å'), array('A','a','O','o','A','a'), trim(pupesoft_cleanstring(pupesoft_csvstring($row['nakyvyys']))));

    list($saldo, $hyllyssa, $myytavissa, $devnull) = saldo_myytavissa($row['tuoteno']);

    if (empty($row['kehahin'])) {
      $katepros = 100;
    }
    elseif (empty($row['myyntihinta'])) {
      $katepros = 0;
    }
    else {
      $katepros = 100 - ($row['kehahin'] / $row['myyntihinta'] * 100);
    }

    // lisätään tuotteen lisäämiseen tarvittavat tiedot
    $tuotteet[$i] = array(
      "external_id"               => $row['tuoteno'],
      "price"                     => round($row['myyntihinta'] * 100, 0),
      "sale_price"                => '',
      "type"                      => $osasto,
      "category"                  => $try,
      "category_id"               => $row['try'],
      "vendor"                    => $toimittaja,
      "ean"                       => $row['eankoodi'],
      "brand"                     => $row['tuotemerkki'],
      "title"                     => $row['nimitys'],
      "image"                     => $liite_tk_url,
      "date"                      => $row['muutospvm'],
      "description"               => $row['mainosteksti'],
      "language"                  => strtoupper($yhtiorow['kieli']),
      "visible"                   => ($row['status'] == 'P' ? false : true),
      "exclude_from_recommendations" => ($row['status'] == 'P' ? true : false),
      "in_stock"                  => ($myytavissa > 0 ? true : false),
      "unit"                      => $row['yksikko'],
      "weight"                    => $row['tuotemassa'],

      "{$cb_shortname}__keskihankintahinta" => $row['kehahin'],
      "{$cb_shortname}__lyhytkuvaus"        => $row['lyhytkuvaus'],
      "{$cb_shortname}__myymalahinta"       => $row['myymalahinta'],
      "{$cb_shortname}__nakyvyys"           => $row['nakyvyys'],
      "{$cb_shortname}__laskennallinen_katepros" => round($katepros, 2) . "%",
    );

    if (!empty($row['nettohinta'])) {
      $tuotteet[$i]['sale_price'] = round($row['nettohinta'] * 100, 0);
    }

    $i++;
  }

  cron_aikaleima("CB_TU_CRON", $aloitusaika);

  return $tuotteet;
}

function cb_ajetaanko_sykronointi($ajo, $ajolista) {
  // jos ajo ei ole ajolistalla, ei ajeta
  if (array_search(strtolower(trim($ajo)), $ajolista) === false) {
    return false;
  }

  // Sallitaan vain yksi instanssi tästä ajosta kerrallaan
  $lock_params = array(
    "lockfile" => "cb-{$ajo}-flock.lock",
    "locktime" => 5400,
    "return"   => true,
  );

  $status = pupesoft_flock($lock_params);

  if ($status === false) {
    cb_echo("{$ajo} -ajo on jo käynnissä, ei ajeta uudestaan.");
  }

  return $status;
}

function cb_echo($string) {
  if ($GLOBALS['cb_debug'] !== true) {
    return;
  }

  echo date("d.m.Y @ G:i:s")." - {$string}\n";
}
