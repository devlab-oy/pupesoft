<?php

ini_set("memory_limit", "5G");

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  //$pupe_root_polku = "/Users/satu/Sites/pupesoft";
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";
  require "tilauskasittely/luo_myyntitilausotsikko.inc";

  // Logitetaan ajo
  cron_log();

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
    die ("User admin not found");
  }

  // Adminin oletus
  $kukarow = mysql_fetch_assoc($result);
}
else {
  die("Tiedostoa voi ajaa vain komentoriviltä");
}

if ($futursoft_kansio == "" or $futursoft_kansio_valmis == "" or $futursoft_kansio_error == "") {
  die("Kansiot määriteltävä!\n");
}

if (!is_dir($futursoft_kansio) or !is_dir($futursoft_kansio_valmis) or !is_dir($futursoft_kansio_error)) {
  die("Kansio virheellinen!\n");
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
      $kateismyynti_data = parsi_xml_tiedosto($xml);
      $maksuehto_kat = hae_kateis_maksuehto($yhtio);
      $kateisme = $maksuehto_kat['tunnus'];
      $maksuehto_kor = hae_kortti_maksuehto($yhtio);
      $korttime = $maksuehto_kor['tunnus'];
      echo count($kateismyynti_data)." kateismyyntiä löytyi\n";

      $kasitellyt_kateismyynnit = kasittele_kateismyynnit_data($kateismyynti_data, $yhtio, $kateisme, $korttime);

      echo $kasitellyt_kateismyynnit['tilausnumero_count']." tilausta luotiin.\n";

      if (!$_REQUEST['debug']) {
        siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_valmis);
      }
    }
    else {
      echo "Tiedosto ei ole olemassa\n";
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
          if (stristr($tiedosto, 'kateis') !== FALSE) {
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


function parsi_xml_tiedosto(SimpleXMLElement $xml) {
  //i ja indeksi on sitä varten, että aineistosta saadaan yhteen kuuluvat käteismyynti tiliöinnit eriytettyä muista
  $data = array();
  if ($xml !== FALSE) {
    foreach ($xml->LedgerJournalTable->LedgerJournalTrans as $kateismyynti) {
      $lasku_numero = (string)$kateismyynti->Invoice;
      $indeksi = (string)$kateismyynti->Dim2;
      $tyyppi = utf8_decode((string)$kateismyynti->Txt);

      if (stristr($tyyppi, 'käteis') !== FALSE) {
        $data[$indeksi.'_1'][] = array(
          'siirtopaiva'   => (string)$kateismyynti->TransDate,
          'tapahtumapaiva' => (string)$kateismyynti->DocumentDate,
          'laskunro'     => preg_replace('/[^0-9]/', '', $lasku_numero),
          'tilinumero'   => (string)$kateismyynti->AccountNum,
          'selite'     => utf8_decode((string)$kateismyynti->Txt),
          'summa'       => ((string)$kateismyynti->AmountCurDebit == '') ? ((float)$kateismyynti->AmountCurCredit * -1) : (float)$kateismyynti->AmountCurDebit,
          'valkoodi'     => (string)$kateismyynti->Currency,
          'kurssi'     => (string)$kateismyynti->ExchRate,
          'alv_ryhma'     => (string)$kateismyynti->TaxGroup,
          'alv'       => ((string)$kateismyynti->TaxItemGroup == '') ? 0 : (string)$kateismyynti->TaxItemGroup,
          'alv_maara'     => (string)$kateismyynti->FixedTaxAmount,
          'kustp'       => (string)$kateismyynti->Dim2,
          'maksuehto'     => $maksuehto_kat[tunnus],
          'onko_kortti'   => false, //käytetään tee_tiliointi funkkarissa, jotta saadaan kustannuspaikan takaa tulevan kassalippaan tilino oikein
        );
      }
      elseif (stristr($tyyppi, 'kortti') !== FALSE) {
        $data[$indeksi.'_2'][] = array(
          'siirtopaiva'   => (string)$kateismyynti->TransDate,
          'tapahtumapaiva' => (string)$kateismyynti->DocumentDate,
          'laskunro'     => preg_replace('/[^0-9]/', '', $lasku_numero),
          'tilinumero'   => (string)$kateismyynti->AccountNum,
          'selite'     => utf8_decode((string)$kateismyynti->Txt),
          'summa'       => ((string)$kateismyynti->AmountCurDebit == '') ? ((float)$kateismyynti->AmountCurCredit * -1) : (float)$kateismyynti->AmountCurDebit,
          'valkoodi'     => (string)$kateismyynti->Currency,
          'kurssi'     => (string)$kateismyynti->ExchRate,
          'alv_ryhma'     => (string)$kateismyynti->TaxGroup,
          'alv'       => ((string)$kateismyynti->TaxItemGroup == '') ? 0 : (string)$kateismyynti->TaxItemGroup,
          'alv_maara'     => (string)$kateismyynti->FixedTaxAmount,
          'kustp'       => (string)$kateismyynti->Dim2,
          'maksuehto'     => $maksuehto_kor['tunnus'],
          'onko_kortti'   => true, //käytetään tee_tiliointi funkkarissa, jotta saadaan kustannuspaikan takaa tulevan kassalippaan tilino oikein
        );
      }
      else {
        echo "Aineisto on virheellinen emme voi jatkaa. Sallitut kortti- ja käteismyynti. Debug: {$tyyppi}\n";
        die("Virheellinen aineisto\n");
      }
    }
  }

  return $data;
}


function kasittele_kateismyynnit_data($kateismyynnit, $yhtio, $kateisme, $korttime) {
  $kateismyynti_count = 0;
  $tilausnumero_count = 0;
  $tiliointi_idt = array();
  $tilausnumerot = array();


  foreach ($kateismyynnit as $kateismyynnin_osat) {
    $kassalipas_row = hae_kassalipas($kateismyynnin_osat, $yhtio);
    $tilausnumero = luo_myyntiotsikko($kateismyynnin_osat, $kassalipas_row, $yhtio, $kateisme, $korttime);

    if ($tilausnumero) {
      $tilausnumerot[] = $tilausnumero;
      $tilausnumero_count++;

      echo "Käteislasku"." ".$tilausnumero." luotiin\n";

      //jos kassalipasta ei löydy pupesta niin tehdään ylempänä käteismyyntiotsikko, johon merkitään, että kassalipasta ei löytynyt, jotta hyväksymis näkymässä nähdään että kyseisessä laskussa on virhe, mutta ei tehdä tiliöintejä kyseiselle laskulle koska sitä ei voi hyväksyä
      if (!empty($kassalipas_row)) {
        foreach ($kateismyynnin_osat as $kateismyynti) {
          $tiliointi_tunnukset = tee_tiliointi($tilausnumero, $kateismyynti, $kassalipas_row, $yhtio);
          if ($tiliointi_tunnukset) {
            foreach ($tiliointi_tunnukset as $tunnus) {
              $tiliointi_idt[] = $tunnus;
              $kateismyynti_count++;
            }
          }
        }
      }
    }
  }

  return array(
    'tiliointi_idt'     => $tiliointi_idt,
    'kateismyynti_count' => $kateismyynti_count,
    'tilausnumerot'     => $tilausnumerot,
    'tilausnumero_count' => $tilausnumero_count,
  );
}


function hae_kassalipas($kateismyynnin_osat, $yhtio) {
  $kassalipas = tarkista_tilinumero($kateismyynnin_osat[0]['kustp'], $yhtio);

  return $kassalipas;
}


function luo_myyntiotsikko($kateismyynnin_osat, $kassalipas_row, $yhtio, $kateisme, $korttime) {
  $asiakas = luo_kateis_asiakas($yhtio);
  $yhtio_row = hae_yhtion_parametrit($yhtio);

  if (empty($kassalipas_row)) {
    $insert_lasku = "  kassalipas = '',
              yhtio_toimipaikka = '',
              comments = '".t("Kassalipasta ei löytynyt kustannuspaikalle")." {$kateismyynnin_osat[0]['kustp']}',";
  }
  else {
    $insert_lasku = "  kassalipas = '{$kassalipas_row['tunnus']}',
              yhtio_toimipaikka = '{$kassalipas_row['toimipaikka']}',";
  }

  $laskunro = $kateismyynnin_osat[0]['laskunro'];
  $laskunro = ($laskunro + 10000000000) * -1;
  $apu = $kateismyynnin_osat[0]['selite'];

  if (stristr($apu, 'kortti') !== FALSE) {
    $maksuehto = $korttime;
  }
  else {
    $maksuehto = $kateisme;
  }
  //$kateismyynnin_osat[0] koska halutaan lasku otsikolle summa positiiviselle
  $query = "INSERT INTO futur_lasku
            SET yhtio = '{$yhtio}',
            yhtio_nimi       = '{$yhtio_row['nimi']}',
            yhtio_osoite     = '{$yhtio_row['osoite']}',
            yhtio_postino    = '{$yhtio_row['postino']}',
            yhtio_postitp    = '{$yhtio_row['postitp']}',
            yhtio_maa        = '{$yhtio_row['maa']}',
            nimi             = '{$asiakas['nimi']}',
            osoite           = '{$asiakas['osoite']}',
            postino          = '{$asiakas['postino']}',
            postitp          = '{$asiakas['postitp']}',
            maa              = '{$asiakas['maa']}',
            toim_nimi        = '{$asiakas['nimi']}',
            toim_osoite      = '{$asiakas['osoite']}',
            toim_postino     = '{$asiakas['postino']}',
            toim_postitp     = '{$asiakas['postitp']}',
            toim_maa         = '{$asiakas['maa']}',
            ytunnus          = '{$asiakas['ytunnus']}',
            valkoodi         = '{$kateismyynnin_osat[0]['valkoodi']}',
            liitostunnus     = '{$asiakas['tunnus']}',
            summa            = '{$kateismyynnin_osat[0]['summa']}',
            summa_valuutassa = '{$kateismyynnin_osat[0]['summa']}',
            vienti_kurssi    = '{$kateismyynnin_osat[0]['kurssi']}',
            laatija          = 'futursoft',
            luontiaika       = NOW(),
            laskunro         = '{$laskunro}',
            maksuehto        = '{$maksuehto}',
            tapvm            = '{$kateismyynnin_osat[0]['tapahtumapaiva']}',
            lapvm            = '{$kateismyynnin_osat[0]['tapahtumapaiva']}',
            mapvm            = '{$kateismyynnin_osat[0]['tapahtumapaiva']}',
            toimaika         = '{$kateismyynnin_osat[0]['tapahtumapaiva']}',
            kerayspvm        = '{$kateismyynnin_osat[0]['tapahtumapaiva']}',
            alv              = '{$kateismyynnin_osat[0]['alv']}',
            {$insert_lasku}
            tila             = 'U',
            alatila          = 'X',
            hyvaksytty       = 0,
            kuka_hyvaksyi    = '',
            hyvaksytty_aika  = 0";
  pupe_query($query);

  return mysql_insert_id();
}


function luo_kateis_asiakas($yhtio) {
  //HUOM! KÄTEISASIAKKAAN ASIAKASNUMERO SOVITTIIN 70500 JOTEN SE ON KOVAKOODATTU
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio    = '{$yhtio}'
            AND asiakasnro = '70500'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    //käteis-asiakkaan maksuehdoksi laitetaan käteinen, koska tarvitsemme validin maksuehdon
    $query_maksuehto = "SELECT *
                        FROM maksuehto
                        WHERE yhtio = '{$yhtio}'
                        AND teksti  LIKE '%Käteinen%'
                        ORDER BY luontiaika DESC
                        LIMIT 1";
    $result_maksuehto = pupe_query($query_maksuehto);
    $maksuehto_row = mysql_fetch_assoc($result_maksuehto);
    //kaato-asiakkaan toimitustavaksi laitetaan nouto, koska tarvitsemme validin toimitustavan
    $query_toimitustapa = "SELECT *
                           FROM toimitustapa
                           WHERE yhtio = '{$yhtio}'
                           AND selite  LIKE '%Nouto%'
                           LIMIT 1";
    $result_toimitustapa = pupe_query($query_toimitustapa);
    $toimitustapa_row = mysql_fetch_assoc($result_toimitustapa);
    //kaato-asiakasta ei ole olemassa, luodaan kaato-asiakas
    $query2 = "INSERT INTO asiakas
               SET yhtio = '{$yhtio}',
               nimi ='Käteis asiakas',
               asiakasnro   = '70500',
               maksuehto    = '{$maksuehto_row['tunnus']}',
               toimitustapa = '{$toimitustapa_row['tunnus']}',
               laatija      = 'futursoft',
               luontiaika   = NOW()";
    pupe_query($query2);
  }

  return mysql_fetch_assoc($result);
}


function tee_tiliointi($tilausnumero, $kateismyynti, $kassalipas, $yhtio) {
  $kustannuspaikka = hae_kustannuspaikka($kateismyynti['kustp'], $yhtio);

  $tiliointi_tunnukset = array();
  if (!empty($kateismyynti['alv']) and !empty($kateismyynti['alv_maara'])) {
    $kassalippaan_tilinumero = $kassalipas['kateistilitys'];

    //tehdään alv tiliöinti ja tiliöinti - alv
    //aineistoon merkataan AmountCurCredit miinus merkkikseksi ja FixedTaxAmount plus merkkiseksi, katelaskentojen takia. siitä syystä vähän oudolta näyttää
    $alviton_summa = $kateismyynti['summa'] + $kateismyynti['alv_maara'];
    $yhtio_row = hae_yhtion_parametrit($yhtio);

    $query = "INSERT INTO futur_tiliointi
              SET tilino       = '{$kassalippaan_tilinumero}',
              tapvm            = '{$kateismyynti['tapahtumapaiva']}',
              summa            = '{$alviton_summa}',
              summa_valuutassa = '{$alviton_summa}',
              vero             = '{$kateismyynti['alv']}',
              kustp            = '{$kustannuspaikka['tunnus']}',
              ltunnus          = '{$tilausnumero}',
              laatija          = 'futursoft',
              laadittu         = NOW(),
              selite           = '{$kateismyynti['selite']}',
              yhtio            = '{$yhtio}',
              hyvaksytty       = 0,
              kuka_hyvaksyi    = '',
              hyvaksytty_aika  = 0";
    pupe_query($query);

    $tiliointi_tunnukset[] = mysql_insert_id();

    $alv_maara = $kateismyynti['alv_maara'] * -1;
    $query = "INSERT INTO futur_tiliointi
              SET tilino       = '{$yhtio_row['alv']}',
              tapvm            = '{$kateismyynti['tapahtumapaiva']}',
              summa            = '{$alv_maara}',
              summa_valuutassa = '{$alv_maara}',
              vero             = 0,
              kustp            = '{$kustannuspaikka['tunnus']}',
              ltunnus          = '{$tilausnumero}',
              laatija          = 'futursoft',
              laadittu         = NOW(),
              selite           = '".t("Alv tiliöinti")."',
              yhtio            = '{$yhtio}',
              hyvaksytty       = 0,
              kuka_hyvaksyi    = '',
              hyvaksytty_aika  = 0";
    pupe_query($query);

    $tiliointi_tunnukset[] = mysql_insert_id();
  }
  else {
    if (stristr($kateismyynti['selite'], 'käteiskassa') !== FALSE) {
      $kassalippaan_tilinumero = $kassalipas['kassa'];
    }
    elseif (stristr($kateismyynti['selite'], 'korttimyynti') !== FALSE) {
      $kassalippaan_tilinumero = $kassalipas['pankkikortti'];
    }
    else {
      $kassalippaan_tilinumero = $kateismyynti['tilinumero'];
    }
    //tehdään kate tiliöinti
    $query = "INSERT INTO futur_tiliointi
              SET tilino       = '{$kassalippaan_tilinumero}',
              selite           = '{$kateismyynti['selite']}',
              tapvm            = '{$kateismyynti['tapahtumapaiva']}',
              summa            = '{$kateismyynti['summa']}',
              summa_valuutassa = '{$kateismyynti['summa']}',
              vero             = '{$kateismyynti['alv']}',
              kustp            = '{$kustannuspaikka['tunnus']}',
              ltunnus          = '{$tilausnumero}',
              yhtio            = '{$yhtio}',
              hyvaksytty       = 0,
              kuka_hyvaksyi    = '',
              hyvaksytty_aika  = 0";
    pupe_query($query);

    $tiliointi_tunnukset[] = mysql_insert_id();
  }

  return $tiliointi_tunnukset;
}


function tarkista_tilinumero($kustannuspaikka, $yhtio) {
  //haetaan kustannuspaikan tiedot
  $query = "SELECT *
            FROM kustannuspaikka
            WHERE yhtio = '{$yhtio}'
            AND koodi   = '{$kustannuspaikka}'";
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
    return $kassalipas_row;
  }
  else {
    return array();
  }
}


function hae_kateis_maksuehto($yhtio) {
  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio  = '{$yhtio}'
            AND kateinen = 'p'
            AND kaytossa = ''";
  $katresult = pupe_query($query);

  if (mysql_num_rows($katresult) == 0) {
    return "1551";
  }

  return mysql_fetch_assoc($katresult);
}


function hae_kortti_maksuehto($yhtio) {
  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio  = '{$yhtio}'
            AND kateinen = 'o'
            AND kaytossa = ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return "1572";
  }

  return mysql_fetch_assoc($result);
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
