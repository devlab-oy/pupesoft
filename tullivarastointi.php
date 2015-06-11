<?php

if (isset($_POST['task']) and (strpos($_POST['task'], "_pdf") !== false)) {
  $no_head = "yes";
}

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (isset($task) and $task == 'purkuraportti_pdf') {

  $pdf_data = unserialize(base64_decode($pdf_data));

  $logo_info = pdf_logo();
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = purkuraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  header("Content-Disposition:attachment;filename='purkuraportti_{$tulonumero}.pdf'");

  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($task) and $task == 'viivakoodi_pdf') {

  $pdf_tiedosto = viivakoodi_pdf($tuotenumero);

  header("Content-type: application/pdf");
  header("Content-Disposition:attachment;filename='viivakoodi_{$tuotenumero}.pdf'");

  echo file_get_contents($pdf_tiedosto);
  die;
}

$errors = array();

if (isset($task) and $task == 'nollaus') {
  tullinollaus();
  unset($task);
}

if (isset($task) and $task == 'suorita_toimenpide') {
  $task = $toimenpide;
}

if (isset($task) and $task == 'viivakoodit') {

  $tulon_tuotteet_ja_tiedot = tulon_tuotteet_ja_tiedot($tulonumero);
  extract($tulon_tuotteet_ja_tiedot);
  $otsikko = t("Viivakoodien lataus");
  $view = 'viivakoodit';
}

if (isset($task) and $task == 'suorita_eusiirto') {

  $siirrettavat = array();

  foreach ($siirtotuotteet as $key => $tuote) {

    if ($tuote['siirrettava_maara'] > $tuote['varastossa']) {
      $errors[$key] = t("Liian suuri määrä");
    }

    if (!ctype_digit($tuote['siirrettava_maara']) and $tuote['siirrettava_maara'] != '') {
      $errors[$key] = t("Tarkista määrä");
    }

    if (empty($tuote['siirrettava_maara'])) {
      continue;
    }

    if ($tuote['siirrettava_maara'] < $tuote['varastossa']) {

      $siirrettavat[$tuote['tilausrivitunnus']]['siirrettava_maara'] = $tuote['siirrettava_maara'];
      $siirrettavat[$tuote['tilausrivitunnus']]['tuoteno'] = $tuote['tuoteno'];
      $siirrettavat[$tuote['tilausrivitunnus']]['hyllyalue'] = $tuote['hyllyalue'];
      $siirrettavat[$tuote['tilausrivitunnus']]['hyllynro'] = $tuote['hyllynro'];
      $siirrettavat[$tuote['tilausrivitunnus']]['tyyppi'] = 'splittaus';
    }
    elseif ($tuote['siirrettava_maara'] == $tuote['varastossa']) {

      $siirrettavat[$tuote['tilausrivitunnus']]['siirrettava_maara'] = $tuote['siirrettava_maara'];
      $siirrettavat[$tuote['tilausrivitunnus']]['tuoteno'] = $tuote['tuoteno'];
      $siirrettavat[$tuote['tilausrivitunnus']]['hyllyalue'] = $tuote['hyllyalue'];
      $siirrettavat[$tuote['tilausrivitunnus']]['hyllynro'] = $tuote['hyllynro'];
      $siirrettavat[$tuote['tilausrivitunnus']]['tyyppi'] = 'kokonaissiirto';
    }
  }

  if (count($errors) > 0 or count($siirrettavat) == 0) {
    $task = 'eusiirto';
  }
  else {

    // pitääkö perustaa uusi tulonumero
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sisviesti2 = '{$vanha_tulonumero}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {

      // perustetaan uusi EU-tyyppinen tulonumero
      $query = "SELECT *
                FROM toimi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$toimittajatunnus}'";
      $toimres = pupe_query($query);
      $toimrow = mysql_fetch_assoc($toimres);

      $params = array(
        'liitostunnus' => $toimrow['tunnus'],
        'nimi' => $toimrow['nimi'],
        'myytil_toimaika' => $toimaika,
        'varasto' => $varastotunnus,
        'osoite' => $toimrow['osoite'],
        'postino' => $toimrow['postino'],
        'postitp' => $toimrow['postitp'],
        'maa' => $toimrow['maa'],
        'uusi_ostotilaus' => 'JOO',
        'toimipaikka' => ''
      );

      require_once "inc/luo_ostotilausotsikko.inc";

      $laskurow = luo_ostotilausotsikko($params);

      $uusi_tulonumero = seuraava_vapaa_tulonumero('EU');

      $query = "UPDATE lasku SET
                asiakkaan_tilausnumero = '{$uusi_tulonumero}',
                viesti = 'tullivarasto',
                sisviesti2 = '{$vanha_tulonumero}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$laskurow['tunnus']}'";
      pupe_query($query);

    }
    else {

      $laskurow = mysql_fetch_assoc($result);
      $uusi_tulonumero = $laskurow['asiakkaan_tilausnumero'];
    }

    foreach ($siirrettavat as $rivitunnus => $tiedot) {

      $kopioitavat = array();
      $kopiointiparametrit = array();

      $kopiointiparametrit['rivitiedot'] = $tiedot;
      $kopiointiparametrit['uusi_tulonumero'] = $uusi_tulonumero;
      $kopiointiparametrit['uusi_tulotunnus'] = $laskurow['tunnus'];
      $kopiointiparametrit['rivitunnus'] = $rivitunnus;

      // pitääkö perustaa uusi tuote ja toimittaja
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno LIKE '{$uusi_tulonumero}-%'
                ORDER BY tuoteno ASC";
      $result = pupe_query($query);

      if (isset($tuotenumero)) {
        unset($tuotenumero);
      }

      while ($tuote = mysql_fetch_assoc($result)) {
        if ($tuote['tilausrivi_kommentti'] == $tiedot['tuoteno']) {
          $tuotenumero = $tuote['tuoteno'];
        }
        $suurin_tuotenumero = $tuote['tuoteno'];
      }

      if (mysql_num_rows($result) == 0 or !isset($tuotenumero)) {

        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tiedot['tuoteno']}'";
        $result = pupe_query($query);
        $kopioitavat['tuote'] = mysql_fetch_assoc($result);

        $query = "SELECT *
                  FROM tuotteen_toimittajat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tiedot['tuoteno']}'
                  AND liitostunnus = '{$toimittajatunnus}'";
        $result = pupe_query($query);
        $kopioitavat['tuotteen_toimittajat'] = mysql_fetch_assoc($result);

        if (isset($suurin_tuotenumero)) {
          list(,,,$tuotejuoksu) = explode('-', $suurin_tuotenumero);
          $uusi_tuotejuoksu = $tuotejuoksu + 1;
        }
        else {
          $uusi_tuotejuoksu = '1';
        }

        $tuotenumero = $uusi_tulonumero . '-' . $uusi_tuotejuoksu;
      }

      $kopiointiparametrit['tuotenumero'] = $tuotenumero;

      // pitääkö perustaa tuotepaikka
      $query = "SELECT *
                FROM tuotepaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tuotenumero}'
                AND hyllyalue = '{$tiedot['hyllyalue']}'
                AND hyllynro = '{$tiedot['hyllynro']}'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {

        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tiedot['tuoteno']}'
                  AND hyllyalue = '{$tiedot['hyllyalue']}'
                  AND hyllynro = '{$tiedot['hyllynro']}'";
        $result = pupe_query($query);
        $kopioitavat['tuotepaikat']  = mysql_fetch_assoc($result);
      }

      // pitääkö kopioida uusi tilausrivi lisätietoineen
      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tuotenumero}'
                AND hyllyalue = '{$tiedot['hyllyalue']}'
                AND hyllynro = '{$tiedot['hyllynro']}'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {

        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus = '{$rivitunnus}'";
        $result = pupe_query($query);
        $kopioitavat['tilausrivi'] = mysql_fetch_assoc($result);

        $query = "SELECT *
                  FROM tilausrivin_lisatiedot
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivitunnus = '{$rivitunnus}'";
        $result = pupe_query($query);
        $kopioitavat['tilausrivin_lisatiedot'] = mysql_fetch_assoc($result);
      }
      else {

        $tilausrivi = mysql_fetch_assoc($result);

        $query = "UPDATE tilausrivi SET
                  tilkpl = tilkpl + {$tiedot['siirrettava_maara']},
                  kpl = kpl + {$tiedot['siirrettava_maara']}
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tilausrivi['tunnus']}'";
        pupe_query($query);

        $query = "UPDATE tuotepaikat SET
                  saldo = saldo + {$tiedot['siirrettava_maara']}
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tuotenumero}'
                  AND hyllyalue = '{$tiedot['hyllyalue']}'
                  AND hyllynro = '{$tiedot['hyllynro']}'";
        pupe_query($query);
      }

      $kopiointiparametrit['kopioitavat'] = $kopioitavat;

      eu_kopioi_rivit($kopiointiparametrit);
    }

    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$tulotunnus}'
              AND kommentti != 'kokonaan siirretty eu-numerolle'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {

      $muutos = "UPDATE lasku
                 SET sisviesti1 = 'siirretty eu-numerolle'
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND tunnus  = '{$tulotunnus}'";
      pupe_query($muutos);
    }

    unset($task);
  }
}

if (isset($task) and $task == 'eusiirto') {
  $otsikko = t("Siirto EU-numerolle");
  $view = 'eusiirto';
  $tulon_tuotteet_ja_tiedot = tulon_tuotteet_ja_tiedot($tulonumero);
  extract($tulon_tuotteet_ja_tiedot);
}

if (isset($task) and $task == 'purkuraportti') {
  $otsikko = t("Purkuraportin lataus");
  $view = 'purkuraportin_lataus';

  $purkuraportti_parametrit = purkuraportti_parametrit($tulotunnus);
  extract($purkuraportti_parametrit);
  $pdf_data = serialize($purkuraportti_parametrit);
  $pdf_data = base64_encode($pdf_data);
}

if (isset($task) and $task == 'varaustaydennys') {
  $otsikko = t("Täydennä tulon {$tulonumero} tiedot");
  $view = "tulotiedot";
}

if (isset($task) and ($task == 'muokkaus' or $task == 'tarkastelu')) {

  $otsikko = t("Tulon tuotetiedot");
  $view = "tuotetiedot";
  $tulon_tuotteet_ja_tiedot = tulon_tuotteet_ja_tiedot($tulonumero);

  extract($tulon_tuotteet_ja_tiedot);

  if ($lisatuote == 1) {
   $uusi = $tuotteet[1];

   foreach ($uusi as $key => $value) {

    if ($key == 'tyyppi') {
      $uusi[$key] = 'uusi';
    }
    else {
      $uusi[$key] = '';
    }
   }

   $uusi_key = key(array_slice($tuotteet, -1, 1, TRUE)) + 1;
   $tuotteet[$uusi_key] = $uusi;
  }
  $tuoteryhmien_maara = count($tuotteet);
}

if (isset($task) and $task == 'aloita_perustus') {
  $otsikko = t("Syötä tulon tiedot");
  $view = "tulotiedot";
}

if (isset($task) and $task == 'aloita_varaus') {
  $otsikko = t("Valitse varasto");
  $view = "aloita_varaus";
}

if (isset($task) and $task == 'varaa_tulonumero' and is_null($varastotunnus_ja_koodi)) {
  $otsikko = t("Valitse varasto");
  $view = "aloita_varaus";
}

if (isset($task) and $task == 'anna_tulotiedot') {

  if (is_null($varastotunnus_ja_koodi)) {
    $errors['varastotunnus_ja_koodi'] = t("Valitse varasto");
  }

  if (is_null($toimittajatunnus)) {
    $errors['toimittajatunnus'] = t("Valitse toimittaja");
  }

  if (empty($edeltava_asiakirja)) {
    $errors['edeltava_asiakirja'] = t("Edeltävä asiakirja puuttuu");
  }

  if (empty($rekisterinumero) and empty($konttinumero)) {
    $errors['rekisterinumero'] = t("Rekisterinumero tai konttinumero tarvitaan");
    $errors['konttinumero'] = t("Konttinumero tai rekisterinumero tarvitaan");
  }

  if (!empty($rekisterinumero) and !empty($konttinumero)) {
    $errors['rekisterinumero'] = t("Syötä joko rekisterinumero tai konttinumero");
    $errors['konttinumero'] = t("Syötä joko konttinumero tai rekisterinumero");
  }

  if (empty($sinettinumero)) {
    $errors['sinettinumero'] = t("Sinettinumero puuttuu");
  }

  if (!is_numeric($tuoteryhmien_maara)) {
    $errors['tuoteryhmien_maara'] = t("Tarkista määra");
  }
  elseif (empty($tuoteryhmien_maara)) {
   $tuoteryhmien_maara = 1;
  }

  $palat = explode('.', $tulopaiva);
  $toimaika = $palat[2] . '-' . $palat[1] . '-' . $palat[0];

  $syotetty_aika = strtotime($toimaika);
  $tanaan = strtotime('today midnight');

  if ($syotetty_aika < $tanaan) {
    $errors['tulopaiva'] = t("Tulopäivä ei voi olla menneisyydessä");
  }

  if (count($errors) > 0) {

    $otsikko = t("Syötä tulon tiedot");
    $view = 'tulotiedot';
  }
  else {
    $otsikko = t("Täydennä tulotiedot");
    $view = "tuotetiedot";
  }
}

if (isset($task) and $task == 'varaa_tulonumero') {

  $_varastotunnus_ja_koodi = explode("#", $varastotunnus_ja_koodi);
  $varastotunnus = $_varastotunnus_ja_koodi[0];
  $varastokoodi = $_varastotunnus_ja_koodi[1];
  $tulonumero = seuraava_vapaa_tulonumero($varastokoodi);

  require_once "inc/luo_ostotilausotsikko.inc";

  $params = array(
    'liitostunnus' => 0,
    'nimi' => '',
    'myytil_toimaika' => '000-00-00 00:00:00',
    'varasto' => $varastotunnus,
    'osoite' => '',
    'postino' => '',
    'postitp' => '',
    'maa' => '',
    'uusi_ostotilaus' => 'JOO'
  );

  $laskurow = luo_ostotilausotsikko($params);

  $query = "UPDATE lasku SET
            asiakkaan_tilausnumero = '{$tulonumero}',
            viesti = 'tullivarasto'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$laskurow['tunnus']}'";
  pupe_query($query);

  echo "<meta http-equiv='refresh' content='0;url=tullivarastointi.php?va=ok&tn={$tulonumero}'>";
  die;
}

if (isset($task) and $task == 'tullisiirto') {

  if ($varastokoodi == 'ROVV') {
    $uusi_koodi = 'ROTV';
  }
  elseif ($varastokoodi == 'VRP') {
    $uusi_koodi = 'RP';
  }

  $uusi_tulonumero = seuraava_vapaa_tulonumero($uusi_koodi);

  $query = "SELECT tuoteno
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tulotunnus}'";
  $result = pupe_query($query);

  $tuotenumerot = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    $tuotenumerot[] = $rivi['tuoteno'];
  }

  $tuotenumerot = array_unique($tuotenumerot);

  foreach ($tuotenumerot as  $tuotenumero) {

    list($varasto, $tulojuoksu, $vuosi, $tuotejuoksu) = explode('-', $tuotenumero);

    $uusi_tuotenumero = $uusi_tulonumero . '-' . $tuotejuoksu;

    $query = "UPDATE tuotepaikat SET
              tuoteno = '{$uusi_tuotenumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    pupe_query($query);

    $query = "UPDATE tuote SET
              tuoteno = '{$uusi_tuotenumero}',
              tilausrivi_kommentti = '{$tuotenumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    pupe_query($query);

    $query = "UPDATE tuotteen_toimittajat SET
              tuoteno = '{$uusi_tuotenumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    pupe_query($query);

    $query = "UPDATE tilausrivi SET
              tuoteno = '{$uusi_tuotenumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    pupe_query($query);

    $query = "UPDATE tilausrivin_lisatiedot SET
              asiakkaan_tilausnumero = '{$uusi_tulonumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tulonumero}'";
    pupe_query($query);
  }

  $query = "SELECT toimaika, luontiaika
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tulotunnus}'";
  $result = pupe_query($query);
  $tulorivi = mysql_fetch_assoc($result);

  $query = "UPDATE lasku SET
            asiakkaan_tilausnumero = '{$uusi_tulonumero}',
            luontiaika = NOW()
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tulotunnus}'";
  pupe_query($query);

  $query = "INSERT INTO lasku
            SET yhtio = '{$kukarow['yhtio']}',
            viesti = 'tullivarasto',
            tila = 'O',
            asiakkaan_tilausnumero = '{$tulonumero}',
            sisviesti1 = '{$uusi_tulonumero}',
            toimaika = '{$tulorivi['toimaika']}',
            luontiaika = '{$tulorivi['luontiaika']}'";
  pupe_query($query);

  unset($task);
}

if (isset($task) and ($task == 'perusta' or $task == 'tallenna')) {

  foreach ($tuotteet as $key => $tiedot) {

    if (empty($tiedot['nimitys'])) {
      $errors[$key]["nimitys"] = t("Syötä nimitys");
    }

    if (empty($tiedot['malli'])) {
      $errors[$key]["malli"] = t("Syötä malli");
    }

    if (empty($tiedot['maara1'])) {
      $errors[$key]["maara1"] = t("Syötä määra");
    }
    elseif (!is_numeric($tiedot['maara1'])) {
      $errors[$key]["maara1"] = t("Tarkista määra");
    }

    if (empty($tiedot['maara2'])) {
      $errors[$key]["maara2"] = t("Syötä pakkausmäärä");
    }
    elseif (!is_numeric($tiedot['maara2'])) {
      $errors[$key]["maara2"] = t("Tarkista pakkausmäärä");
    }

    if (empty($tiedot['nettopaino'])) {
      $errors[$key]["nettopaino"] = t("Syötä nettopaino");
    }
    else {
      $tiedot['nettopaino'] = str_replace(',', '.', $tiedot['nettopaino']);

      if (!is_numeric($tiedot['nettopaino'])) {
        $errors[$key]["nettopaino"] = t("Tarkista nettopaino");
      }
      else {
        $tuotteet[$key]['nettopaino'] = $tiedot['nettopaino'];
      }
    }

    if (empty($tiedot['bruttopaino'])) {
      $errors[$key]["bruttopaino"] = t("Syötä bruttopaino");
    }
    else {
      $tiedot['bruttopaino'] = str_replace(',', '.', $tiedot['bruttopaino']);

      if (!is_numeric($tiedot['bruttopaino'])) {
        $errors[$key]["bruttopaino"] = t("Tarkista bruttopaino");
      }
      else {
        $tuotteet[$key]['bruttopaino'] = $tiedot['bruttopaino'];
      }
    }

    if (empty($tiedot['tilavuus'])) {
      $errors[$key]["tilavuus"] = t("Syötä tilavuus");
    }
    else {
      $tiedot['tilavuus'] = str_replace(',', '.', $tiedot['tilavuus']);

      if (!is_numeric($tiedot['tilavuus'])) {
        $errors[$key]["tilavuus"] = t("Tarkista tilavuus");
      }
      else {
        $tuotteet[$key]['tilavuus'] = $tiedot['tilavuus'];
      }
    }

    if (empty($tiedot['pakkauslaji'])) {
      $errors[$key]["pakkauslaji"] = t("Syötä pakkauslaji");
    }
    elseif (strlen($tiedot['pakkauslaji']) > 6) {
      $errors[$key]["pakkauslaji"] = t("Liian pitkä pakkauslaji");
    }

    if (strlen($tiedot['lisatieto']) > 250) {
      $errors[$key]["lisatieto"] = t("Maksimimerkkimäärä: 250");
    }
  }

  if (count($errors) > 0) {
    $view = 'tuotetiedot';

    if ($task == 'perusta') {
      $otsikko = t("Täydennä tulotiedot");
    }
    elseif ($task == 'tallenna') {
      $otsikko = t("Täydennä tulotiedot");
      $task = 'muokkaus';
    }
    else {
      $otsikko = t("Tulon tuotetiedot");
      $task = 'muokkaus';
    }
  }
  elseif ($task == 'perusta') {

    require_once "inc/luo_ostotilausotsikko.inc";

    // haetaan toimittajan tiedot
    $query = "SELECT *
              FROM toimi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$toimittajatunnus}'";
    $toimres = pupe_query($query);
    $toimrow = mysql_fetch_assoc($toimres);

    $palat = explode('.', $tulopaiva);
    $toimaika = $palat[2] . '-' . $palat[1] . '-' . $palat[0];

    $params = array(
      'liitostunnus' => $toimrow['tunnus'],
      'nimi' => $toimrow['nimi'],
      'myytil_toimaika' => $toimaika,
      'varasto' => $varastotunnus,
      'osoite' => $toimrow['osoite'],
      'postino' => $toimrow['postino'],
      'postitp' => $toimrow['postitp'],
      'maa' => $toimrow['maa'],
      'uusi_ostotilaus' => 'JOO',
      'toimipaikka' => ''
    );

    $laskurow = luo_ostotilausotsikko($params);

    $query = "UPDATE lasku SET
              asiakkaan_tilausnumero = '{$tulonumero}',
              viesti = 'tullivarasto'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$laskurow['tunnus']}'";
    pupe_query($query);

    foreach ($tuotteet as $key => $tiedot) {

      $tuotteen_lisays_parametrit = array(
        'laskurow' => $laskurow,
        'tulonumero' => $tulonumero,
        'nimitys' => mysql_real_escape_string($tiedot['nimitys']),
        'malli' => mysql_real_escape_string($tiedot['malli']),
        'lisatieto' => mysql_real_escape_string($tiedot['lisatieto']),
        'pakkauslaji' => strtoupper(mysql_real_escape_string($tiedot['pakkauslaji'])),
        'pakkauskpl' => $tiedot['maara2'],
        'bruttopaino' => $tiedot['bruttopaino'],
        'nettopaino' => $tiedot['nettopaino'],
        'tilavuus' => $tiedot['tilavuus'],
        'kpl' => $tiedot['maara1'],
        'varasto' => $varastotunnus,
        'toimittajan_tunnus' => $toimittajatunnus,
        'edeltava_asiakirja' => $edeltava_asiakirja,
        'kuljetuksen_rekno' => $kuljetuksen_rekno,
        'konttinumero' => $konttinumero,
        'sinettinumero' => $sinettinumero,
        'kerayspvm' => $toimaika,
        'toimaika' => $toimaika
      );

      tullituotelisays($tuotteen_lisays_parametrit);

    }
    echo "<meta http-equiv='refresh' content='0;url=tullivarastointi.php?pe=ok&tn={$tulonumero}'>";
    die;
  }
  elseif ($task == 'tallenna') {

    $palat = explode('.', $tulopaiva);
    $toimaika = $palat[2] . '-' . $palat[1] . '-' . $palat[0];

    if (isset($varaustaydennys)) {

      $query = "SELECT *
                FROM toimi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$toimittajatunnus}'";
      $result = pupe_query($query);
      $toimittaja = mysql_fetch_assoc($result);

      $query = "UPDATE lasku SET
                nimi  = '{$toimittaja['nimi']}',
                osoite = '{$toimittaja['osoite']}',
                postino = '{$toimittaja['postino']}',
                postitp  = '{$toimittaja['postitp']}',
                maa       = '{$toimittaja['maa']}',
                toim_nimi  = '{$toimittaja['nimi']}',
                toim_osoite = '{$toimittaja['osoite']}',
                toim_postino = '{$toimittaja['postino']}',
                toim_postitp  = '{$toimittaja['postitp']}',
                toim_maa       = '{$toimittaja['maa']}',
                ytunnus         = '{$toimittaja['ytunnus']}',
                ovttunnus        = '{$toimittaja['ovttunnus']}',
                liitostunnus      = '{$toimittaja['tunnus']}',
                toimaika           = '{$toimaika}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$tulotunnus}'";
      pupe_query($query);
    }

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$tulotunnus}'";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_assoc($result);

    $paivitetyt_tuotteet = array();

    foreach ($tuotteet as $key => $tiedot) {

      if ($tiedot['tyyppi'] == 'vanha') {

        paivita_tullivarastointituote($tiedot);
        paivita_tullivarastointirivi($tiedot);
      }
      elseif ($tiedot['tyyppi'] == 'uusi') {

        $tuotteen_lisays_parametrit = array(
          'laskurow' => $laskurow,
          'tulonumero' => $tulonumero,
          'nimitys' => mysql_real_escape_string($tiedot['nimitys']),
          'malli' => mysql_real_escape_string($tiedot['malli']),
          'lisatieto' => mysql_real_escape_string($tiedot['lisatieto']),
          'pakkauslaji' => strtoupper(mysql_real_escape_string($tiedot['pakkauslaji'])),
          'pakkauskpl' => $tiedot['maara2'],
          'bruttopaino' => $bruttopaino,
          'nettopaino' => $nettopaino,
          'tilavuus' => $tilavuus,
          'kpl' => $tiedot['maara1'],
          'varasto' => $varastotunnus,
          'toimittajan_tunnus' => $toimittajatunnus,
          'edeltava_asiakirja' => $edeltava_asiakirja,
          'kuljetuksen_rekno' => $kuljetuksen_rekno,
          'konttinumero' => $konttinumero,
          'sinettinumero' => $sinettinumero,
          'kerayspvm' => $toimaika,
          'toimaika' => $toimaika
        );

        tullituotelisays($tuotteen_lisays_parametrit);

      }
    } //muutetut vanhat sekä uudet tuotteet käyty läpi
  echo "<meta http-equiv='refresh' content='0;url=tullivarastointi.php'>";
  die;
  }
}

if (!isset($task)) {
  $otsikko = t("Perustetut tulonumerot");
  $view = "perus";
}

if ($view != 'perus') {
  echo "<a href='tullivarastointi.php'>« " . t("Takaisin tulonumeroihin") . "</a><br><br>";
}

echo "<font class='head'>{$otsikko}</font><hr><br>";

if (isset($pe) and $pe == "ok" and !isset($task)) {
  echo "<p class='green'>", t("Tulonumero: "), $tn, ' ', t("perustettu"), "</p>";
  echo "<br>";
}

if (isset($va) and $va == "ok" and !isset($task)) {
  echo "<p class='green'>", t("Tulonumero: "), $tn, ' ', t("varattu"), "</p>";
  echo "<br>";
}

//////////////////////////
// tuotetieto-näkymä alkaa
//////////////////////////

if (isset($view) and $view == "tuotetiedot") {

  if(empty($tulonumero)) {

    $_varastotunnus_ja_koodi = explode("#", $varastotunnus_ja_koodi);
    $varastotunnus = $_varastotunnus_ja_koodi[0];
    $varastokoodi = $_varastotunnus_ja_koodi[1];
    $tulonumero = seuraava_vapaa_tulonumero($varastokoodi);
  }

  $toimittajat = toimittajat();

  if ((isset($task) and $task == 'muokkaus') or isset($varaustaydennys)) {
    $taskvalue = 'tallenna';
  }
  else {
   $taskvalue = 'perusta';
  }

  if ($task != 'tarkastelu') {

   echo "
   <form action='tullivarastointi.php' action='tullivarastointi.php' method='post'>
   <input type='hidden' name='task' value='{$taskvalue}' />
   <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}' />
   <input type='hidden' name='varastotunnus_ja_koodi' value='{$varastotunnus_ja_koodi}' />
   <input type='hidden' name='tuoteryhmien_maara' value='{$tuoteryhmien_maara}' />
   <input type='hidden' name='varastotunnus' value='{$varastotunnus}' />
   <input type='hidden' name='kuljetuksen_rekno' value='{$rekisterinumero}' />
   <input type='hidden' name='konttinumero' value='{$konttinumero}' />
   <input type='hidden' name='sinettinumero' value='{$sinettinumero}' />
   <input type='hidden' name='varastokoodi' value='{$varastokoodi}' />
   <input type='hidden' name='tulopaiva' value='{$tulopaiva}' />
   <input type='hidden' name='edeltava_asiakirja' value='{$edeltava_asiakirja}' />
   <input type='hidden' name='tulonumero' value='{$tulonumero}' />
   <input type='hidden' name='tulotunnus' value='{$tulotunnus}' />";

   if (isset($varaustaydennys)) {
     echo "<input type='hidden' name='varaustaydennys' value='1'>";
   }
  }

  if (!empty($konttinumero)) {
    $rekisteri_tai_kontti_otsikko = t("Konttinumero");
    $rekisteri_tai_kontti = $konttinumero;
  }
  else {
    $rekisteri_tai_kontti_otsikko = t("Rekisterinumero");
    $rekisteri_tai_kontti = $rekisterinumero;
  }

  echo "
  <table>
  <tr>
    <th>" . t("Toimittaja") ."</th>
    <th>" . t("Tulonumero") ."</th>
    <th>" . t("Edeltävä asiakirja") ."</th>
    <th>" . $rekisteri_tai_kontti_otsikko ."</th>
    <th>" . t("Sinettinumero") ."</th>
    <th>" . t("Tulopäiva") ."</th>
  </tr>
  <tr>
    <td>{$toimittajat[$toimittajatunnus]}</td>
    <td>{$tulonumero}</td>
    <td>{$edeltava_asiakirja}</td>
    <td>{$rekisteri_tai_kontti}</td>
    <td>{$sinettinumero}</td>
    <td>{$tulopaiva}</td>
  </tr>
  </table>";

  if (isset($tuotteet)) {

    echo "
    <script type='text/javascript'>
    $( document ).ready(function() {

      $('input').attr('autocomplete','off');

      $('.tuote').bind('keyup change', function(e) {
        var muutosluokka = $(this).attr('class').split(' ')[1];
        $('.'+muutosluokka).val($(this).val());
      });

    });
    </script>";
  }

  $laskuri = 1;
  while ($laskuri <= $tuoteryhmien_maara) {

    if (isset($tuotteet)) {
      $nimitys = $tuotteet[$laskuri]['nimitys'];
      $malli = $tuotteet[$laskuri]['malli'];
      $maara1 = $tuotteet[$laskuri]['maara1'];
      $maara2 = $tuotteet[$laskuri]['maara2'];
      $nettopaino = $tuotteet[$laskuri]['nettopaino'];
      $bruttopaino = $tuotteet[$laskuri]['bruttopaino'];
      $tilavuus = $tuotteet[$laskuri]['tilavuus'];
      $lisatieto = $tuotteet[$laskuri]['lisatieto'];
      $pakkauslaji = $tuotteet[$laskuri]['pakkauslaji'];
      $tyyppi = $tuotteet[$laskuri]['tyyppi'];
      $tuotetunnus = $tuotteet[$laskuri]['tuotetunnus'];
      $tilausrivitunnus = $tuotteet[$laskuri]['tilausrivitunnus'];
      $hyllyalue = $tuotteet[$laskuri]['hyllyalue'];
      $hyllynro = $tuotteet[$laskuri]['hyllynro'];
      $tuotenumero = $tuotteet[$laskuri]['tuoteno'];
    }
    else {
      $nimitys = '';
      $malli = '';
      $maara1 = '';
      $maara2 = '';
      $nettopaino = '';
      $bruttopaino = '';
      $tilavuus = '';
      $lisatieto = '';
      $pakkauslaji = '';
      $tyyppi = 'uusi';
      $tuotetunnus = '';
      $tilausrivitunnus = '';
      $hyllyalue = '';
      $hyllynro = '';
    }

    $hylly = substr($hyllyalue, 1) . $hyllynro;

    if (empty($hylly)) {
      $hylly = '&mdash;';
    }

    if ($task != 'tarkastelu') {

      echo "
      <input type='hidden' name='tuotteet[{$laskuri}][hyllynro]' value='{$hyllynro}' />
      <input type='hidden' name='tuotteet[{$laskuri}][hyllyalue]' value='{$hyllyalue}' />
      <input type='hidden' name='tuotteet[{$laskuri}][tyyppi]' value='{$tyyppi}' />
      <input type='hidden' name='tuotteet[{$laskuri}][tuotetunnus]' value='{$tuotetunnus}' />
      <input type='hidden' name='tuotteet[{$laskuri}][tuotenumero]' value='{$tuotenumero}' />
      <input type='hidden' name='tuotteet[{$laskuri}][tilausrivitunnus]' value='{$tilausrivitunnus}' />
      <input type='hidden' name='tuotteet[{$laskuri}][hidden_maara1]' value='$maara1' />
      <input type='hidden' id='mm{$laskuri}' name='tuotteet[{$laskuri}][muutosmittari]' value='' />";
    }

    echo "
    <table id='tuotetaulu{$laskuri}' class='tuotetaulu' style='display:inline-block; margin:10px 10px 0 0;'>";

    if (!isset($tuotteet)) {

      echo "
      <tr>
        <th colspan='2'>" . t("Tuote") ." {$laskuri}</th>
        <td class='back error'></td>
      </tr>";
     }

     echo "
      <tr>
        <th>" . t("Nimitys") ."</th>
        <td>";

    if ($task != 'tarkastelu') {
      echo "<input type='text' class='tuote t1{$tuotetunnus}' name='tuotteet[{$laskuri}][nimitys]' value='{$nimitys}' />";
    }
    else {
      echo $nimitys;
    }

    echo "
      </td>
        <td class='back error'>{$errors[$laskuri]['nimitys']}</td>
      </tr>

      <tr>
        <th>" . t("Malli") ."</th>
        <td>";

    if ($task != 'tarkastelu') {
      echo "<input type='text' class='tuote t2{$tuotetunnus}' name='tuotteet[{$laskuri}][malli]' value='{$malli}' />";
    }
    else {
      echo $malli;
    }

    echo "
      </td>
        <td class='back error'>{$errors[$laskuri]['malli']}</td>
      </tr>";

      if (isset($tuotteet)) {
        echo "
        <tr>
          <th>" . t("Varastopaikka") ."</th>
          <td>{$hylly}</td>
          <td class='back'></td>
        </tr>";
      }

      echo "
        <tr>
          <th>" . t("Kpl.") ."</th>
          <td>";

      if ($task != 'tarkastelu') {
        echo "<input type='text' name='tuotteet[{$laskuri}][maara1]' value='{$maara1}' />";
      }
      else {
        echo $maara1;
      }

      echo "
        </td>
          <td class='back error'>{$errors[$laskuri]['maara1']}</td>
        </tr>";

    echo "
      <tr>
        <th>" . t("Pakkauslaji") ."</th>
        <td>";

    if ($task != 'tarkastelu') {
      echo "<input type='text' class='tuote t3{$tuotetunnus}' name='tuotteet[{$laskuri}][pakkauslaji]' value='{$pakkauslaji}' />";
    }
    else {
      echo $pakkauslaji;
    }

    echo "
      </td>
        <td class='back error'>{$errors[$laskuri]['pakkauslaji']}</td>
      </tr>

      <tr>
        <th>" . t("Määrä pakkauksessa") ."</th>
        <td>";

    if ($task != 'tarkastelu') {

      echo "
        <input type='text' class='tuote t4{$tuotetunnus}' style='width:45px;' name='tuotteet[{$laskuri}][maara2]' value='$maara2' />";
    }
    else {
      echo $maara2;
    }

    echo "
      </td>
        <td class='back error'>{$errors[$laskuri]['maara2']}</td>
      </tr>

      <tr>
        <th>" . t("Nettopaino") ."</th>
        <td>";

        if ($task != 'tarkastelu') {
          echo "<input type='text' class='tuote t5{$tuotetunnus}' name='tuotteet[{$laskuri}][nettopaino]' value='{$nettopaino}' />";
        }
        else {
          echo $nettopaino;
        }

        echo "
          </td>
        <td class='back error'>{$errors[$laskuri]['nettopaino']}</td>
      </tr>

      <tr>
        <th>" . t("Bruttopaino") ."</th>
        <td>";

        if ($task != 'tarkastelu') {
          echo "<input type='text' class='tuote t6{$tuotetunnus}' name='tuotteet[{$laskuri}][bruttopaino]' value='{$bruttopaino}' />";
        }
        else {
          echo $bruttopaino;
        }

        echo "
          </td>
        <td class='back error'>{$errors[$laskuri]['bruttopaino']}</td>
      </tr>

      <tr>
        <th>" . t("Tilavuus") ."</th>
        <td>";

        if ($task != 'tarkastelu') {
          echo "<input type='text' class='tuote t7{$tuotetunnus}' name='tuotteet[{$laskuri}][tilavuus]' value='{$tilavuus}' />";
        }
        else {
          echo $tilavuus;
        }

        echo "
          </td>
        <td class='back error'>{$errors[$laskuri]['tilavuus']}</td>
      </tr>

      <tr>
        <th>" . t("Lisätietoja") ."</th>
        <td>";

        if ($task != 'tarkastelu') {
          echo "<textarea class='tuote t8{$tuotetunnus}' name='tuotteet[{$laskuri}][lisatieto]'>{$lisatieto}</textarea>";
        }
        else {
          echo $lisatieto;
        }

      echo "
      </td>
        <td class='back error'>{$errors[$laskuri]['lisatieto']}</td>
      </tr>

    </table>";

    $laskuri++;
  }

  if ($task != 'tarkastelu' ) {

    if (isset($varaustaydennys)) {
      echo "<br><br><input type='submit' value='". t("Lisaa tuotteet") ."' /></form>&nbsp;";
    }
    elseif (isset($task) and $task == 'muokkaus') {
      echo "<br><br><input type='submit' value='". t("Tallenna") ."' /></form>&nbsp;";

      if (!$lisatuote and count($errors) == 0) {
        echo "<form action='tullivarastointi.php' method='post'><input type='hidden' name='tulonumero' value='{$tulonumero}' />";
        echo "<input type='hidden' name='task' value='muokkaus' />";
        echo "<input type='hidden' name='lisatuote' value='1' />";
        echo "<input type='submit' value='". t("Lisää tuote") ."' /></form>";
      }
    }
    else {
      echo "<br><br><input type='submit' value='". t("Perusta tulo ja tuotteet") ."' /></form>";
    }

  }
}

//////////////////////////
// varaus-näkymä alkaa
//////////////////////////

if (isset($view) and $view == 'aloita_varaus') {

  echo "
  <form action='tullivarastointi.php' method='post'>
  <input type='hidden' name='task' value='varaa_tulonumero' />
  <table>
  <tr>
    <th>" . t("Varasto") . "</th>
    <td>
      <select name='varastotunnus_ja_koodi'>
        <option selected disabled>" . t("Valitse varasto") ."</option>";

        $varastot = hae_tullivarastot();

        foreach ($varastot as $varasto) {

          if ($varastotunnus_ja_koodi == $varasto['koodi']) {
            $selected = 'selected';
          }
          else {
            $selected = '';
          }

          echo "<option value='{$varasto['koodi']}' {$selected}>{$varasto['nimi']}</option>";
        }

    echo "</select></td><td class='back'><input type='submit' value='". t("Varaa") ."' /></td>
  </tr>
  </table>
  </form>";
}

//////////////////////////
// tulotieto-näkymä alkaa
//////////////////////////

if (isset($view) and $view == 'tulotiedot') {

  $toimittajat = toimittajat();

  if (!isset($tuoteryhmien_maara)) {
    $tuoteryhmien_maara = 1;
  }

  datepicker('tulopaiva');

  if (!isset($tulopaiva)) {
    $tulopaiva = date("d.m.Y", time());
  }

  echo "
  <form action='tullivarastointi.php' method='post'>
  <input type='hidden' name='task' value='anna_tulotiedot' />
  <input type='hidden' name='' value='' />
  <table>
  <tr>
    <th>" . t("Toimittaja") ."</th>
    <td>
    <select name='toimittajatunnus'>
      <option selected disabled>" . t("Valitse toimittaja") ."</option>";

      foreach ($toimittajat as $tunnus => $nimi) {

        if ($tunnus == $toimittajatunnus) {
          $selected = 'selected';
        }
        else {
          $selected = '';
        }

        echo "<option value='{$tunnus}' {$selected}>{$nimi}</option>";
      }

    echo "</select></td>
      <td class='back error'>{$errors['toimittajatunnus']}</td>
    </tr>

    <tr>
      <th>" . t("Tuoteryhmien määrä") . "</th>
      <td><input type='text' name='tuoteryhmien_maara' value='{$tuoteryhmien_maara}' /></td>
      <td class='back error'>{$errors['tuoteryhmien_maara']}</td>
    </tr>

    <tr>
      <th>" . t("Varasto") . "</th>
      <td>";

  if (isset($varastonimi)) {
    echo $varastonimi;

    $varastotunnus_ja_koodi = $varastotunnus.'#'.$varastokoodi;

    echo "<input type='hidden' name='varastotunnus_ja_koodi' value='{$varastotunnus_ja_koodi}'>";
    echo "<input type='hidden' name='varastonimi' value='{$varastonimi}'>";
    echo "<input type='hidden' name='tulonumero' value='{$tulonumero}'>";
    echo "<input type='hidden' name='tulotunnus' value='{$tulotunnus}'>";
    echo "<input type='hidden' name='varaustaydennys' value='1'>";
  }
  else {

    echo "<select name='varastotunnus_ja_koodi'>
          <option selected disabled>" . t("Valitse varasto") ."</option>";

          $varastot = hae_tullivarastot();

          foreach ($varastot as $varasto) {

            if ($varastotunnus_ja_koodi == $varasto['koodi']) {
              $selected = 'selected';
            }
            else {
              $selected = '';
            }

            echo "<option value='{$varasto['koodi']}' {$selected}>{$varasto['nimi']}</option>";
          }

      echo "</select>";
  }

  echo "</td><td class='back error'>{$errors['varastotunnus_ja_koodi']}</td>
  </tr>


  <tr>
    <th>" . t("Edeltävä asiakirja") . "</th>
    <td><input type='text' name='edeltava_asiakirja' value='{$edeltava_asiakirja}' /></td>
    <td class='back error'>{$errors['edeltava_asiakirja']}</td>
  </tr>

  <tr>
    <th>" . t("Rekisterinumero") . "</th>
    <td><input type='text' name='rekisterinumero' value='{$rekisterinumero}' /></td>
    <td class='back error'>{$errors['rekisterinumero']}</td>
  </tr>

  <tr>
    <th>" . t("Konttinumero") . "</th>
    <td><input type='text' name='konttinumero' value='{$konttinumero}' /></td>
    <td class='back error'>{$errors['konttinumero']}</td>
  </tr>

  <tr>
    <th>" . t("Sinettinumero") . "</th>
    <td><input type='text' name='sinettinumero' value='{$sinettinumero}' /></td>
    <td class='back error'>{$errors['sinettinumero']}</td>
  </tr>

  <tr>
    <th>" . t("Tulopäivä") . "</th>
    <td><input type='text' name='tulopaiva' id='tulopaiva' value='{$tulopaiva}' /></td>
    <td class='back error'>{$errors['tulopaiva']}</td>
  </tr>

  <tr>
    <th></th>
    <td align='right'><input type='submit' value='". t("Jatka") ."' /></td>
    <td class='back'></td>
  </tr>
  </table>
  </form>";
}

//////////////////////////
// perus-näkymä alkaa
//////////////////////////

if (isset($view) and $view == "perus") {

  $query = "SELECT
            lasku.asiakkaan_tilausnumero,
            lasku.tunnus,
            SUM(FLOOR(tilausrivi.tilkpl)) as kpl,
            concat(tilausrivi.nimitys, '&nbsp;&mdash;&nbsp;', tuote.malli) as tuote,
            tuote.muuta AS lisatieto,
            tilausrivi.tuoteno,
            GROUP_CONCAT(DISTINCT CONCAT(tilausrivi.hyllyalue, '#', tilausrivi.hyllynro)) AS varastopaikat,
            lasku.varasto,
            varastopaikat.nimitys AS varastonimi,
            varastopaikat.tunnus AS varastotunnus,
            lasku.nimi,
            lasku.toimaika,
            tilausrivi.tunnus AS rivitunnus
            FROM lasku
            LEFT JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            LEFT JOIN varastopaikat
              ON varastopaikat.yhtio = lasku.yhtio
              AND varastopaikat.tunnus = lasku.varasto
            LEFT JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND viesti = 'tullivarasto'
            AND sisviesti1 = ''
            GROUP BY tilausrivi.tuoteno, lasku.tunnus
            ORDER BY lasku.tunnus DESC";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='message'>";
    echo t("Ei perustettuja tulonumeroita!");
    echo "</font><br><br>";
  }


  echo "
    <form action='tullivarastointi.php' method='post'>
    <input type='hidden' name='task' value='nollaus' />
    <input type='submit' value='". t("Nollaa") . "' />
    </form>";

  echo "
    <form action='tullivarastointi.php' method='post'>
    <input type='hidden' name='task' value='aloita_perustus' />
    <input type='submit' value='". t("Perusta uusi tulonumero") . "' />
    </form>";

  echo "
    <form action='tullivarastointi.php' method='post'>
    <input type='hidden' name='task' value='aloita_varaus' />
    <input type='submit' value='". t("Varaa tulonumero") . "' />
    </form><br><br>";

  if (mysql_num_rows($result) != 0) {

    $tulot = array();
    $tuotteet = array();


    while ($tulo = mysql_fetch_assoc($result)) {

      if (is_null($tulo['rivitunnus'])) {
        $tulo['nimi'] = '';
        $tulo['tuote'] = '';
        $tulo['kpl'] = '';
        $tulo['varastopaikka'] = '';
        $tulo['lisatieto'] = '';
      }

      list($varastokoodi) = explode('-', $tulo['asiakkaan_tilausnumero']);

      switch ($varastokoodi) {
        case 'EU':
          $nimilisa = ' - EU';
          $varastotyyppi = 'normaali';
          break;
        case 'ROTV':
        case 'RP':
          $nimilisa = ' - tulli';
          $varastotyyppi = 'normaali';
          break;
        case 'ROVV':
        case 'VRP':
          $nimilisa = ' - väliaikainen';
          $varastotyyppi = 'väliaikainen';
          break;
        default:
          # code...
          break;
      }


      if ($tulo['varastopaikat'] == '#') {
        $tarjolla = false;
      }
      else {

        $varastopaikat = explode(',', $tulo['varastopaikat']);

        $tarjolla = 0;

        foreach ($varastopaikat as $varastopaikka) {

          list($hyllyalue, $hyllynro) = explode('#', $varastopaikka);

          $saldot = saldo_myytavissa(
              $tulo['tuoteno'],
              '',
              $tulo['varastotunnus'],
              '',
              $hyllyalue,
              $hyllynro,
              0,
              0
          );

          $tarjolla += $saldot[2];
        }
      }

      $tuoteinfo = array(
        'varastotunnus' => $tulo['varastotunnus'],
        'rivitunnus' => $tulo['rivitunnus'],
        'lisatieto' => $tulo['lisatieto'],
        'tarjolla' => $tarjolla,
        'tuoteno' => $tulo['tuoteno'],
        'tuote' => $tulo['tuote'],
        'kpl' => $tulo['kpl']
      );

      $tuotteet[$tulo['asiakkaan_tilausnumero']]['toimittaja'] = $tulo['nimi'];
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['varastonimi'] = $tulo['varastonimi'].$nimilisa;
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['toimaika'] = $tulo['toimaika'];
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['vt'] = $varastotyyppi;
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['tulotunnus'] = $tulo['tunnus'];
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['varastokoodi'] = $varastokoodi;
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['varastotunnus'] = $tulo['varastotunnus'];
      $tuotteet[$tulo['asiakkaan_tilausnumero']]['tuoteinfo'][] = $tuoteinfo;
    }

    echo "
    <script type='text/javascript'>

      $( document ).ready(function() {

        $('.tpselect').change(function() {
          var tunnus = $(this).attr('id');
          var valittu = $(this).val();
          var nappitunnus = tunnus+'_nappi';
          $('.nappi').prop('disabled', true);
          $('.tpselect').val('.');
          $(this).val(valittu);
          $('#'+nappitunnus).prop('disabled', false);
        });

      });

    </script>";

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tulo")."</th>";
    echo "<th>".t("Tuotteet")."</th>";
    echo "<th>".t("Status")."</th>";
    echo "<th>".t("Varasto")."</th>";
    echo "<th>".t("Toimenpiteet")."</th>";
    echo "<th class='back'></th>";
    echo "</tr>";

    foreach ($tuotteet as $tulonumero => $info) {

      echo "<tr>";

      // Tulosolu
      echo "<td valign='top'>";
      echo "<span class='message'>".$tulonumero."</span>";

      if ($info['toimaika'] != '0000-00-00') {
        $toimaika = date("d.m.Y", strtotime($info['toimaika']));
        echo "&nbsp;&mdash;&nbsp;" . $toimaika;
      }

      if ($info['toimittaja'] != '') {
        echo '<br>';
        echo $info['toimittaja'];
      }
      else {
        echo '<br><br>';
      }
      echo "</td>";

      // tuotesolu
      echo "<td valign='top'>";
      $statukset = array();
      $tuotemaara = count($info['tuoteinfo']);
      $ei_varastossa = 0;
      $varastossa = 0;
      $liitetty_toimituksiin = false;

      foreach ($info['tuoteinfo'] as $tuote) {

        if ($tuote['tarjolla'] === false) {
          $statukset[$tulonumero][$tuote['rivitunnus']] = t("Ei varastossa");
        }
        elseif ($tuote['tarjolla'] == 0) {
          $statukset[$tulonumero][$tuote['rivitunnus']] = t("Liitetty toimituksiin");
          $liitetty_toimituksiin = true;
        }
        elseif ($tuote['tarjolla'] < $tuote['kpl']) {
          $statukset[$tulonumero][$tuote['rivitunnus']] = t("Osa liitetty toimituksiin");
        }
        elseif ($tuote['tarjolla'] == $tuote['kpl']) {
          $statukset[$tulonumero][$tuote['rivitunnus']] = t("Varastossa");
        }
        else {
          $statukset[$tulonumero][$tuote['rivitunnus']] = $tarjolla ." = ". $tuote['kpl'];
        }

        if ($tuote['vp'] == '') {
          $ei_varastossa++;
        }
        else {
          $varastossa++;
        }

        if ($tuote['kpl'] == '') {
          echo t("Varaus");
        }
        else {
          echo "<div>". $tuote['tuote'] . "&nbsp;&mdash;&nbsp;" . $tuote['kpl'] . " " .t("kpl.") . "</div>";
        }
      }
      echo "</td>";

      // statussolu
      echo "<td align='center' valign='top'>";

      if ($tuote['kpl'] == '') {
        echo t("Varaus");
      }
      else{
        foreach ($statukset as $tulo => $tuotteet) {
          foreach ($tuotteet as $tuoteno => $status) {
            echo $status . '<br>';
          }
        }
      }
      echo "</td>";

      // varastosolu
      echo "<td valign='top'>";
      echo $info['varastonimi'];

      $varoitusehto1 = ($info['vt'] == 'väliaikainen');
      $varoitusehto2 = (!empty($info['toimittaja']));
      $varoitusehto3 = ($varastossa > 0);

      if ($varoitusehto1 and $varoitusehto2 and $varoitusehto3) {
        $date1 = new DateTime($info['toimaika']);
        $date2 = new DateTime('today');
        $interval = $date1->diff($date2);
        $jaljella = (20 - $interval->days);

        if ($jaljella > 0) {
         echo '<br>';
         echo "<span class='error'>";
         echo $jaljella . ' ' . t("Päivää jäljellä");
         echo "</span>";
        }
      }
      echo "</td>";

      // toimenpidesolu
      echo "<td valign='top'>";

      if ($toimenpiteet = tulotoimenpiteet($info['tulotunnus'])) {

        if ($liitetty_toimituksiin) {
          unset($toimenpiteet['eusiirto']);
          unset($toimenpiteet['tullisiirto']);
          unset($toimenpiteet['muokkaus']);
        }

        echo "<form action='tullivarastointi.php' method='post'>";
        echo "<input type='hidden' name='task' value='suorita_toimenpide' />";
        echo "<input type='hidden' name='varastokoodi' value='{$info['varastokoodi']}'>";
        echo "<input type='hidden' name='varastotunnus' value='{$info['varastotunnus']}'>";
        echo "<input type='hidden' name='varastonimi' value='{$info['varastonimi']}'>";
        echo "<input type='hidden' name='tulonumero' value='{$tulonumero}' />";
        echo "<input type='hidden' name='tulotunnus' value='{$info['tulotunnus']}' />";
        echo "<select name='toimenpide' id='{$info['tulotunnus']}' class='tpselect' style='width:90px;'>";

        echo "<option value='.' selected disabled>". t("Valitse") ."</option>";

        foreach ($toimenpiteet as $koodi => $teksti) {
          echo "<option value='{$koodi}'>{$teksti}</option>";
        }

        echo "</select>";
        echo "&nbsp;";
        echo "<input id='{$info['tulotunnus']}_nappi' class='nappi' disabled type='submit' value='" . t("Suorita") . "'/>";
        echo "</form>";
      }
      else {

      }

      echo "</td>";

      echo "<td class='back' valign='top'></td>";
      echo "</tr>";
    }
    echo "</table>";

  }
}

//////////////////////////
// purkuraportin-lataus-näkymä alkaa
//////////////////////////

if (isset($view) and $view == "purkuraportin_lataus") {

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Toimittaja")."</th>";
  echo "<th>".t("Tulonumero")."</th>";
  echo "<th>".t("Saapumispäivä")."</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td>{$toimittaja}</td>";
  echo "<td>{$tulonumero}</td>";
  echo "<td>{$saapumispaiva}</td>";
  echo "</tr>";
  echo "</table>";

  echo '<br>';

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Nimitys")."</th>";
  echo "<th>".t("Malli")."</th>";
  echo "<th>".t("kpl.")."</th>";
  echo "<th>".t("Purkuaika")."</th>";
  echo "<th class='back'></th>";
  echo "</tr>";

  foreach ($puretut_tuotteet as $tuote) {

    echo "<tr>";
    echo "<td>";
    echo $tuote['nimitys'];
    echo "</td>";
    echo "<td>";
    echo $tuote['malli'];
    echo "</td>";
    echo "<td>";
    echo $tuote['kpl'];
    echo "</td>";
    echo "<td>";
    echo $tuote['purkuaika'];
    echo "</td>";
    echo "</tr>";

  }

  echo "</table>";
  echo '<br>';

  echo "
    <form method='post' class='multisubmit' action='tullivarastointi.php'>
    <input type='hidden' name='task' value='purkuraportti_pdf' />
    <input type='hidden' name='tulonumero' value='{$tulonumero}' />
    <input type='hidden' name='pdf_data' value='{$pdf_data}' />
    <input type='submit' value='" . t("Lataa PDF") . "' />
    </form>";

}

//////////////////////////
// Viivakoodi-näkymä alkaa
//////////////////////////

if (isset($view) and $view == "viivakoodit") {


  echo "<table>";

  echo "<tr>";

  echo "<th>";
  echo t("Nimitys");
  echo "</th>";

  echo "<th>";
  echo t("Malli");
  echo "</th>";

  echo "<th class='back'>";
  echo "</th>";
  echo "</tr>";

  foreach ($tuotteet as $key => $tuote) {

    echo "<tr>";

    echo "<td>";
    echo $tuote['nimitys'];
    echo "</td>";

    echo "<td>";
    echo $tuote['malli'];
    echo "</td>";


    echo "<td class='back'>";
    echo "<form method='post' class='multisubmit' action='tullivarastointi.php'>";
    echo "<input type='hidden' name='tuotenumero' value='{$tuote['tuoteno']}' />";
    echo "<input type='hidden' name='task' value='viivakoodi_pdf' />";
    echo "<input type='submit' value='" . t("Lataa tiedosto") . "' />";
    echo "</form>";
    echo "</td>";

    echo "<td class='back error'>";

    if (isset($errors[$key])) {
      echo $errors[$key];
    }

    echo "</td>";
    echo "</tr>";
  }

  echo "</table>";
}

//////////////////////////
// EU-siirto-näkymä alkaa
//////////////////////////

if (isset($view) and $view == "eusiirto") {

  echo "<form method='post' action='tullivarastointi.php'>";
  echo "<table>";

  echo "<tr>";

  echo "<th>";
  echo t("Tuote");
  echo "</th>";

  echo "<th>";
  echo t("Varastossa");
  echo "</th>";

  echo "<th>";
  echo t("Siirrettävä määrä");
  echo "</th>";
  echo "</tr>";

  foreach ($tuotteet as $key => $tuote) {

    if ($tuote['hyllyalue'] != '' and $tuote['maara1'] != 0) {

      if (isset($siirtotuotteet[$key]['siirrettava_maara'])) {
        $siirtomaara = $siirtotuotteet[$key]['siirrettava_maara'];
      }
      else {
        $siirtomaara = '';
      }

      echo "<tr>";
      echo "<td>";
      echo $tuote['nimitys'] . ' - ' . $tuote['malli'];
      echo "</td>";

      echo "<td>";
      echo $tuote['maara1'];
      echo "<input type='hidden' name='siirtotuotteet[{$key}][tuoteno]' value='{$tuote['tuoteno']}' />";
      echo "<input type='hidden' name='siirtotuotteet[{$key}][hyllyalue]' value='{$tuote['hyllyalue']}' />";
      echo "<input type='hidden' name='siirtotuotteet[{$key}][hyllynro]' value='{$tuote['hyllynro']}' />";
      echo "<input type='hidden' name='siirtotuotteet[{$key}][tilausrivitunnus]' value='{$tuote['tilausrivitunnus']}' />";
      echo "<input type='hidden' name='siirtotuotteet[{$key}][varastossa]' value='{$tuote['maara1']}' />";
      echo "</td>";

      echo "<td align='right'>";
      echo "<input type='text' size='8'  name='siirtotuotteet[{$key}][siirrettava_maara]' value='{$siirtomaara}' />";
      echo "</td>";

      echo "<td class='back error'>";

      if (isset($errors[$key])) {
        echo $errors[$key];
      }

      echo "</td>";
      echo "</tr>";
    }
  }

  echo "</table>";

  echo "<br>
    <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}' />
    <input type='hidden' name='vanha_tulonumero' value='{$tulonumero}' />
    <input type='hidden' name='tulonumero' value='{$tulonumero}' />
    <input type='hidden' name='toimaika' value='{$toimaika}' />
    <input type='hidden' name='tulotunnus' value='{$tulotunnus}' />
    <input type='hidden' name='varastotunnus' value='{$varastotunnus}' />
    <input type='hidden' name='task' value='suorita_eusiirto' />
    <input type='hidden' name='pdf_data' value='{$pdf_data}' />
    <input type='submit' value='" . t("Siirrä") . "' />
    </form>";
}

require "inc/footer.inc";
