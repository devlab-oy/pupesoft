<?php

ini_set("memory_limit", "5G");

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  //  //for debug
  // $pupe_root_polku = "/Users/satu/Sites/pupesoft";
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";
  require "tilauskasittely/luo_myyntitilausotsikko.inc";

  // Logitetaan ajo
  cron_log();

  $require = true; //setataan muuttuja, jotta asiakas.php ei include juttuja turhaan
  require "pjbs/futursoft_asiakas_import.php";

  $yhtio = trim($argv[1]);

  //yhtiötä ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan käyttäjän tiedot
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = '$yhtio'
            AND kuka    = 'admin'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    die("User admin not found");
  }

  // Adminin oletus
  $kukarow = mysql_fetch_assoc($result);
}
else {
  die("Tiedostoa voi ajaa vain komentoriviltä");
}

if ($futursoft_kansio == "" or $futursoft_kansio_valmis == "" or $futursoft_kansio_error == "") {
  die("Kansiot määriteltävä!");
}

if (!is_dir($futursoft_kansio) or !is_dir($futursoft_kansio_valmis) or !is_dir($futursoft_kansio_error)) {
  die("Kansio virheellinen!");
}

$kukarow["kuka"] = "futursoft";

$tiedostot = lue_tiedostot($futursoft_kansio);

if (!empty($tiedostot)) {
  foreach ($tiedostot as $tiedosto) {
    $tiedosto_polku = $futursoft_kansio.$tiedosto;

    if (file_exists($tiedosto_polku)) {
      $xml = simplexml_load_file($tiedosto_polku);
      if (!$xml) {
        //file read failure, siirretään tiedosto error kansioon
        siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_error);

        die("Tiedoston {$tiedosto_polku} lukeminen epäonnistui\n");
      }

      $myyntilasku_data = parsi_xml_tiedosto($xml, $yhtio);

      unset($xml);

      $kasitellyt_tilaukset = kasittele_myyntilasku_data($myyntilasku_data, $yhtio);

      unset($myyntilasku_data);

      echo $kasitellyt_tilaukset['tilausnumero_count']." tilausta luotiin ja niihin ".$kasitellyt_tilaukset['tiliointi_count']." tiliöintiä\n";

      if (!$_REQUEST['debug']) {
        siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_valmis);
      }
    }
    else {
      die("Tiedostoa ei ole olemassa\n");
    }
  }
}
else {
  echo "Yhtään tiedostoa ei löytynyt\n";
}

function lue_tiedostot($polku) {
  $tiedostot = array();
  if ($handle = opendir($polku)) {
    while (false !== ($tiedosto = readdir($handle))) {
      if ($tiedosto != "." && $tiedosto != "..") {
        if (is_file($polku.$tiedosto)) {
          if (stristr($tiedosto, 'myynti')) {
            $tiedostot[] = $tiedosto;
          }
        }
      }
    }
    closedir($handle);
  }

  return $tiedostot;
}


function siirra_tiedosto_kansioon($tiedosto_polku, $kansio) {
  $tiedosto_array = explode('.', $tiedosto_polku);
  if (!empty($tiedosto_array)) {
    $tiedosto_array2 = explode('/', $tiedosto_array[0]);
    $hakemiston_syvyys = count($tiedosto_array2);

    $uusi_filename = $tiedosto_array2[$hakemiston_syvyys - 1].'_'.date('YmdHis').'.'.$tiedosto_array[1];
  }

  exec('cp "'.$tiedosto_polku.'" "'.$kansio.$uusi_filename.'"');
  exec('rm "'.$tiedosto_polku.'"');
}


function parsi_xml_tiedosto(SimpleXMLElement $xml, $yhtio) {
  $data = array();
  if ($xml !== FALSE) {
    foreach ($xml->LedgerJournalTable->LedgerJournalTrans as $myyntilasku) {
      if ($myyntilasku->AccountType == 'Cust') {
        //Myyntilasku otsikko
        $lasku_numero = (string)$myyntilasku->Invoice;
        $data[$lasku_numero] = array(
          'laskunro'     => preg_replace('/[^0-9]/', '', $lasku_numero),
          'asiakasnumero'   => (string)$myyntilasku->AccountNum,
          'siirtopaiva'   => (string)$myyntilasku->TransDate,
          'tapahtumapaiva' => date('Y-m-d', strtotime((string)$myyntilasku->DocumentDate)),
          'asiakkaan_nimi' => utf8_decode((string)$myyntilasku->Txt),
          'summa'       => ((string)$myyntilasku->AmountCurDebit == '') ? ((float)$myyntilasku->AmountCurCredit * -1) : (float)$myyntilasku->AmountCurDebit,
          'valuutta'     => (string)$myyntilasku->Currency,
          'kurssi'     => (float)$myyntilasku->ExchRate,
          'maksuehto'     => konvertoi_maksuehto($myyntilasku->Payment, $yhtio),
          'erapaiva'     => date('Y-m-d', strtotime((string)$myyntilasku->Due)),
          'viite'       => (string)$myyntilasku->PaymId,
        );
      }
      elseif ($myyntilasku->AccountType == 'Ledger') {
        $data[$lasku_numero]['tilioinnit'][] = array(
          'tilinumero'   => (string)$myyntilasku->AccountNum,
          'siirtopaiva'   => (string)$myyntilasku->TransDate,
          'tapahtumapaiva' => date('Y-m-d', strtotime((string)$myyntilasku->DocumentDate)),
          'asiakkaan_nimi' => utf8_decode((string)$myyntilasku->Txt),
          'summa'       => ((string)$myyntilasku->AmountCurDebit == '') ? ((float)$myyntilasku->AmountCurCredit * -1) : (float)$myyntilasku->AmountCurDebit,
          'valuutta'     => (string)$myyntilasku->Currency,
          'kurssi'     => (string)$myyntilasku->ExchRate,
          'alv'       => ((string)$myyntilasku->TaxItemGroup == '') ? 0 : (float)$myyntilasku->TaxItemGroup,
          'alv_maara'     => (float)$myyntilasku->FixedTaxAmount,
          'kustp'       => (string)$myyntilasku->Dim2,
          'maksuehto'     => konvertoi_maksuehto($myyntilasku->Payment, $yhtio),
          'erapaiva'     => date('Y-m-d', strtotime((string)$myyntilasku->Due)),
          'viite'       => (string)$myyntilasku->PaymId,
        );

        //laitetaan vero myös laskuotsikolle
        if (!isset($data[$lasku_numero]['alv'])) {
          $data[$lasku_numero]['alv'] = ((string)$myyntilasku->TaxItemGroup == '') ? 0 : (float)$myyntilasku->TaxItemGroup;
        }
        //laitetaan kustp myös laskuotsikolle
        if (!isset($data[$lasku_numero]['kustp'])) {
          $data[$lasku_numero]['kustp'] = (string)$myyntilasku->Dim2;
        }
      }
    }
  }

  return $data;
}


function konvertoi_maksuehto($maksuehto, $yhtio) {
  $maksuehto = (string)$maksuehto;

  if ($yhtio == 'artr') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $maksuehto_array = array(
      '931'   => array('id'   => '1494', 'selite' => '14 pv -3% 60 pv netto'),
      '934'   => array('id'   => '21', 'selite' => '60 pv netto'),
      '933'   => array('id'   => '20', 'selite' => '45 pv netto'),
      '908'   => array('id'   => '23', 'selite' => 'Lasku 30 pv'),
      '940'   => array('id'   => '1', 'selite' => '14 pv -2% 30 pv netto'),
      '907'   => array('id'   => '37', 'selite' => 'Lasku 21 pv'),
      '906'   => array('id'   => '15', 'selite' => 'Lasku 14 pv'),
      '932'   => array('id'   => '2016', 'selite' => '30 pv -2% 60 pv netto'),
      '904'   => array('id'   => '7', 'selite' => 'Lasku 7pv'),
      '936'   => array('id'   => '2015', 'selite' => '90 pv netto'),
      '901'   => array('id'   => '1551', 'selite' => 'Käteinen'),
      '909'   => array('id'   => '2019', 'selite' => 'Lasku 14 pv netto, 7 pv - 2 %'),
    );

    if (array_key_exists($maksuehto, $maksuehto_array)) {
      return (int)$maksuehto_array[$maksuehto]['id'];
    }
    else {
      echo "Maksuehdolle ".$maksuehto." ei löytynyt paria. Käytetään 14pv nettoa\n";
      //return (int)$maksuehto;
      return "15"; // 14pv netto atarv-yhtiöllä, tarvitaan viiteaineistojen kohdistuksessa, kun etsitään validia maksuehtoa
    }
  }
  else {
    return (int)$maksuehto;
  }
}


function kasittele_myyntilasku_data(&$myyntilaskut, $yhtio) {
  $tilausnumero_count = 0;
  $tiliointi_count = 0;
  $tilausnumerot = array();
  $tiliointi_tunnukset = array();
  foreach ($myyntilaskut as &$myyntilasku) {
    $tilausnumero = luo_myyntiotsikko($myyntilasku, $yhtio);

    $query = "SELECT *
              FROM kustannuspaikka
              WHERE yhtio = '{$yhtio}'
              AND koodi   = '{$myyntilasku['kustp']}'";
    $result = pupe_query($query);
    $kustannuspaikka_row = mysql_fetch_assoc($result);

    //haetaan kustannuspaikan kassalippaan tiedot
    $query = "SELECT *
              FROM kassalipas
              WHERE yhtio = '{$yhtio}'
              AND kustp   = '{$kustannuspaikka_row['tunnus']}'";
    $result = pupe_query($query);
    $kassalipas_row = mysql_fetch_assoc($result);

    if ($kassalipas_row) {

      $query = "UPDATE futur_lasku
                SET yhtio_toimipaikka = '{$kassalipas_row['toimipaikka']}'
                WHERE yhtio = '{$yhtio}'
                AND tunnus  = '{$tilausnumero}'";
      pupe_query($query);
    }

    if ($tilausnumero) {
      $tilausnumerot[] = $tilausnumero;
      $tilausnumero_count++;

      foreach ($myyntilasku['tilioinnit'] as &$tiliointi) {
        $tiliointi_tunnukset = tee_tiliointi($tilausnumero, $tiliointi, $yhtio);
        if ($tiliointi_tunnukset) {
          foreach ($tiliointi_tunnukset as $tunnus) {
            $tiliointi_tunnukset[] = $tunnus;
            $tiliointi_count++;
          }
        }
        else {
          echo "Tilaukselle ".$tilausnumero." EI TEHTY TILIÖINTIÄ\n";
          //päivitetään laskun comments kenttään tämä tieto jotta sitä voidaan käyttää laskun hyväksymis näkymässä
          //viesti kenttään tallennetaan _jotain_ (ei väliä mitä) tietoa, jotta myöhemmässä vaiheessa tiedetään, että laskuaineistosta on löytynyt lasku jolla on virheellinen tilinumero
          //tällöin kyseisen laskuaineiston mitään laskua ei voida hyväksyä
          $laskunro = $myyntilasku['laskunro'] * -1;
          $query = "UPDATE futur_lasku
                    SET comments = '".t("Tiliöintejä ei voitu tehdä laskulle")." {$laskunro} ".t("koska tili")." {$tiliointi['tilinumero']} ".t("puuttuu")."',
                    viesti      = 'aineistoa ei voi hyväksyä'
                    WHERE yhtio = '{$yhtio}'
                    AND tunnus  = '{$tilausnumero}'";
          pupe_query($query);
        }
      }
    }
  }

  return array(
    'tilausnumero_count'   => $tilausnumero_count,
    'tiliointi_count'     => $tiliointi_count,
    'tilausnumerot'       => $tilausnumerot,
    'tiliointi_tunnukset'   => $tiliointi_tunnukset,
  );
}


function luo_myyntiotsikko(&$myyntilasku, $yhtio) {
  $asiakas = tarkista_asiakas_olemassa($myyntilasku, $yhtio);
  $yhtio_row = hae_yhtion_parametrit($yhtio);

  // Tarkistetaan, onko maksuehdolla käteisalennus
  $query = "SELECT *
            FROM maksuehto
            WHERE tunnus = '$myyntilasku[maksuehto]'
            AND yhtio    = '$yhtio'
            AND (kassa_relpvm > 0 OR kassa_abspvm != '0000-00-00')";
  $presult = pupe_query($query);

  $kassa_erapvm           = "''";
  $kassa_loppusumma         = "";
  $kassa_loppusumma_valuutassa   = "";

  if (mysql_num_rows($presult) == 1) {

    $xrow = mysql_fetch_assoc($presult);

    if ($xrow["kassa_relpvm"] > 0) {
      $kassa_erapvm = "adddate('$myyntilasku[tapahtumapaiva]', interval $xrow[kassa_relpvm] day)";
    }
    elseif ($xrow['kassa_abspvm'] != '0000-00-00') {
      $kassa_erapvm = "'$xrow[kassa_abspvm]'";
    }

    $kassa_loppusumma = round((float) $myyntilasku['summa'] * $xrow['kassa_alepros'] / 100, 2);
    $kassa_loppusumma_valuutassa = $kassa_loppusumma;
  }

  $laskunro = $myyntilasku['laskunro'] * -1;
  $query = "INSERT INTO futur_lasku
            SET yhtio = '{$yhtio}',
            yhtio_nimi         = '{$yhtio_row['nimi']}',
            yhtio_osoite       = '{$yhtio_row['osoite']}',
            yhtio_postino      = '{$yhtio_row['postino']}',
            yhtio_postitp      = '{$yhtio_row['postitp']}',
            yhtio_maa          = '{$yhtio_row['maa']}',
            nimi               = '{$asiakas['nimi']}',
            osoite             = '{$asiakas['osoite']}',
            postino            = '{$asiakas['postino']}',
            postitp            = '{$asiakas['postitp']}',
            maa                = '{$asiakas['maa']}',
            toim_nimi          = '{$asiakas['nimi']}',
            toim_osoite        = '{$asiakas['osoite']}',
            toim_postino       = '{$asiakas['postino']}',
            toim_postitp       = '{$asiakas['postitp']}',
            toim_maa           = '{$asiakas['maa']}',
            ytunnus            = '{$asiakas['ytunnus']}',
            liitostunnus       = '{$asiakas['tunnus']}',
            valkoodi           = '{$myyntilasku['valuutta']}',
            summa              = '{$myyntilasku['summa']}',
            summa_valuutassa   = '{$myyntilasku['summa']}',
            kapvm              = $kassa_erapvm,
            kasumma            = '{$kassa_loppusumma}',
            kasumma_valuutassa = '{$kassa_loppusumma_valuutassa}',
            vienti_kurssi      = 1,
            laatija            = 'futursoft',
            luontiaika         = NOW(),
            viite              = '{$myyntilasku['viite']}',
            laskunro           = '{$laskunro}',
            maksuehto          = '{$myyntilasku['maksuehto']}',
            tapvm              = '{$myyntilasku['tapahtumapaiva']}',
            erpcm              = '{$myyntilasku['erapaiva']}',
            lapvm              = '{$myyntilasku['tapahtumapaiva']}',
            toimaika           = '{$myyntilasku['tapahtumapaiva']}',
            kerayspvm          = '{$myyntilasku['tapahtumapaiva']}',
            alv                = '{$myyntilasku['alv']}',
            tila               = 'U',
            alatila            = 'X',
            hyvaksytty         = 0,
            kuka_hyvaksyi      = '',
            hyvaksytty_aika    = 0,
            viikorkopros       = '{$yhtio_row['viivastyskorko']}'";
  pupe_query($query);
  $tilausnumero = mysql_insert_id();

  $query = "INSERT INTO futur_laskun_lisatiedot
            SET otunnus = '{$tilausnumero}',
            yhtio             = '{$yhtio}',
            laskutus_nimi     = '{$asiakas['laskutus_nimi']}',
            laskutus_osoite   = '{$asiakas['laskutus_osoite']}',
            laskutus_nimitark = '{$asiakas['laskutus_nimitark']}',
            laskutus_postino  = '{$asiakas['laskutus_postino']}',
            laskutus_postitp  = '{$asiakas['laskutus_postitp']}',
            laskutus_maa      = '{$asiakas['laskutus_maa']}',
            laatija           = 'futursoft',
            luontiaika        = NOW()";
  pupe_query($query);

  //tehdään myyntisaamiset tiliöinti
  $myyntisaamiset_array = array(
    'tilinumero'   => $yhtio_row['myyntisaamiset'],
    'tapahtumapaiva' => $myyntilasku['tapahtumapaiva'],
    'summa'       => $myyntilasku['summa'],
    'alv'       => $myyntilasku['alv'],
    'kustp'       => $myyntilasku['kustp'],
  );
  tee_tiliointi($tilausnumero, $myyntisaamiset_array, $yhtio);

  if ($asiakas['tunnus'] == 0) {

    if ($tilausnumero) {
      $query = "UPDATE futur_lasku
                SET comments = '".t("Laskun asiakasta ei löytynyt.")."'
                WHERE yhtio = '{$yhtio}'
                AND tunnus  = '{$tilausnumero}'";
      pupe_query($query);
    }
  }
  else {
    echo "Laskulle ".$tilausnumero." laskunumero ".$myyntilasku['laskunro']." liitettiin asiakas: ".$myyntilasku['asiakkaan_nimi']." ".$myyntilasku['asiakasnumero']."\n";

    //updatee kustannuspaikka asiakkaan taakse
    $kustannuspaikka = hae_kustannuspaikka($myyntisaamiset_array['kustp'], $yhtio);
    paivita_kustannuspaikka_asiakkaalle($asiakas, $kustannuspaikka);
  }

  return $tilausnumero;
}


function tarkista_asiakas_olemassa(&$myyntilasku, $yhtio) {
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio     = '{$yhtio}'
            AND asiakasnro  = '{$myyntilasku['asiakasnumero']}'
            AND laji       != 'P'
            LIMIT 1";
  $result = pupe_query($query);
  if (mysql_num_rows($result) == 0) {
    //jos asiakasta ei löydy tarkistetaan löytyisikö se futursoftin puolelta. tarkista_asiakas_futursoftista() palauttaa vain yhden asiakasnumeron, jos sille antaa yhden asiakasnumeron
    $palautetut_asiakasnumerot = tarkista_asiakas_futursoftista_ja_tuo_pupesoftiin(array($myyntilasku['asiakasnumero']));
    if ($palautetut_asiakasnumerot) {
      echo "Asiakas löytyis futursoftista ja se tuotiin pupesoftiin ".implode(',', $palautetut_asiakasnumerot).".\n";
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio     = '{$yhtio}'
                AND asiakasnro  IN ('".implode("','", $palautetut_asiakasnumerot)."')
                AND laji       != 'P'
                LIMIT 1";
      $result = pupe_query($query);
    }
    if (mysql_num_rows($result) == 0) {
      //jos asiakasta ei löydy, laskulle laitetaan asiakkaan kenttiin tyhjät arvot sekä futurista tullut asiakasnumero ytunnus kenttään, jotta asiakkaan pupesoftiin perustamisen jälkeen, asiakas voidaan liittää kyseiseen laskuun
      return array(
        'tunnus'     => 0,
        'nimi'       => $myyntilasku['asiakkaan_nimi'].' / '.$myyntilasku['asiakasnumero'],
        'osoite'     => '',
        'postino'     => '',
        'postitp'     => '',
        'maa'       => '',
        'toim_nimi'     => '',
        'toim_osoite'   => '',
        'toim_postino'   => '',
        'toim_postitp'   => '',
        'toim_maa'     => '',
        'ytunnus'     => $myyntilasku['asiakasnumero'],
        'liitostunnus'   => 0,
      );
    }
  }

  return mysql_fetch_assoc($result);
}


function tee_tiliointi($tilausnumero, &$tiliointi, $yhtio) {
  $tiliointi_tunnukset = array();
  $tili = tarkista_tilinumero($tiliointi['tilinumero'], $yhtio);
  $kustannuspaikka = hae_kustannuspaikka($tiliointi['kustp'], $yhtio);

  if (!empty($tili)) {
    if (!empty($tiliointi['alv']) and !empty($tiliointi['alv_maara'])) {
      //tehdään alv tiliöinti ja tiliöinti - alv
      if ($tiliointi['summa'] < 0) {
        $alviton_summa = $tiliointi['summa'] + $tiliointi['alv_maara'];
      }
      else {
        $alviton_summa = $tiliointi['summa'] - $tiliointi['alv_maara'];
      }

      $yhtio_row = hae_yhtion_parametrit($yhtio);

      $query = "INSERT INTO futur_tiliointi
                SET tilino       = '{$tiliointi['tilinumero']}',
                tapvm            = '{$tiliointi['tapahtumapaiva']}',
                summa            = '{$alviton_summa}',
                summa_valuutassa = '{$alviton_summa}',
                vero             = '{$tiliointi['alv']}',
                kustp            = '{$kustannuspaikka['tunnus']}',
                ltunnus          = '{$tilausnumero}',
                laatija          = 'futursoft',
                laadittu         = NOW(),
                selite           = '".t("Futursoftista siirretty")."',
                yhtio            = '{$yhtio}',
                hyvaksytty       = 0,
                kuka_hyvaksyi    = '',
                hyvaksytty_aika  = 0";
      pupe_query($query);

      $tiliointi_tunnukset[] = mysql_insert_id();

      if ($tiliointi['summa'] < 0) {
        $alv_maara = $tiliointi['alv_maara'] * -1;
      }
      else {
        $alv_maara = $tiliointi['alv_maara'];
      }
      $query = "INSERT INTO futur_tiliointi
                SET tilino       = '{$yhtio_row['alv']}',
                tapvm            = '{$tiliointi['tapahtumapaiva']}',
                summa            = '{$alv_maara}',
                summa_valuutassa = '{$alv_maara}',
                vero             = '0',
                kustp            = '{$kustannuspaikka['tunnus']}',
                ltunnus          = '{$tilausnumero}',
                laatija          = 'futursoft',
                laadittu         = NOW(),
                selite           = '".t("Futursoftista siirretty")."',
                yhtio            = '{$yhtio}',
                hyvaksytty       = 0,
                kuka_hyvaksyi    = '',
                hyvaksytty_aika  = 0";
      pupe_query($query);

      $tiliointi_tunnukset[] = mysql_insert_id();
    }
    else {
      //tehdään tiliöinti sellaisenaan
      $query = "INSERT INTO futur_tiliointi
                SET tilino       = '{$tiliointi['tilinumero']}',
                tapvm            = '{$tiliointi['tapahtumapaiva']}',
                summa            = '{$tiliointi['summa']}',
                summa_valuutassa = '{$tiliointi['summa']}',
                vero             = '0',
                kustp            = '{$kustannuspaikka['tunnus']}',
                ltunnus          = '{$tilausnumero}',
                laatija          = 'futursoft',
                laadittu         = NOW(),
                selite           = '".t("Futursoftista siirretty")."',
                yhtio            = '{$yhtio}',
                hyvaksytty       = 0,
                kuka_hyvaksyi    = '',
                hyvaksytty_aika  = 0";
      pupe_query($query);

      $tiliointi_tunnukset[] = mysql_insert_id();
    }

    return $tiliointi_tunnukset;
  }
  else {
    echo "Tilinumeroa ".$tiliointi['tilinumero']." EI LÖYTYNYT\n";
    return null;
  }
}


function tarkista_tilinumero($tilinumero, $yhtio) {
  $query = "SELECT *
            FROM tili
            WHERE yhtio = '{$yhtio}'
            AND tilino  = '{$tilinumero}'";
  $result = pupe_query($query);

  $tilinumero_row = mysql_fetch_assoc($result);
  if ($tilinumero_row) {
    return $tilinumero_row;
  }
  else {
    return array();
  }
}


function hae_kustannuspaikka($kustannuspaikka_koodi, $yhtio) {
  $query = "SELECT *
            FROM kustannuspaikka
            WHERE yhtio = '{$yhtio}'
            AND koodi   = '{$kustannuspaikka_koodi}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return null;
  }

  return mysql_fetch_assoc($result);
}


function paivita_kustannuspaikka_asiakkaalle($asiakas, $kustannuspaikka) {
  global $kukarow, $yhtiorow;

  // päivitetään vain jos asiakkaalla ei tällä hetkellä ole kustannuspaikkaa
  $query = "SELECT kustannuspaikka
            FROM asiakas
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus  = '{$asiakas['tunnus']}'";
  $res = pupe_query($query);
  $kustp_row = mysql_fetch_assoc($res);

  if ($kustp_row['kustannuspaikka'] == 0) {
    $query = "UPDATE asiakas
              SET kustannuspaikka = {$kustannuspaikka['tunnus']}
              WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
              AND asiakas.tunnus  = '{$asiakas['tunnus']}'";
    pupe_query($query);
  }
}
