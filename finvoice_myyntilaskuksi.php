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

  // Logitetaan ajo
  cron_log();

  $yhtio = trim($argv[1]);

  //yhtiˆt‰ ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan k‰ytt‰j‰n tiedot
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
  $yhtiorow = hae_yhtion_parametrit($yhtio);
}
else {
  die("Ohjelmaa voi ajaa vain komentorivilt‰");
}

if ($finvoice_myyntilasku_kansio == "" or $finvoice_myyntilasku_kansio_valmis == "" or $finvoice_myyntilasku_kansio_error == "") {
  die("Kansiot m‰‰ritelt‰v‰!");
}

if (!is_dir($finvoice_myyntilasku_kansio)) {
  die("Kansio $finvoice_myyntilasku_kansio virheellinen!");
}
if (!is_dir($finvoice_myyntilasku_kansio_valmis)) {
  die("Kansio $finvoice_myyntilasku_kansio_valmis virheellinen!");
}
if (!is_dir($finvoice_myyntilasku_kansio_error)) {
  die("Kansio $finvoice_myyntilasku_kansio_error virheellinen!");
}

$kukarow["kuka"] = "finvoice_myyntilasku";
$tiedostot = lue_tiedostot($finvoice_myyntilasku_kansio);

if (!empty($tiedostot)) {
  foreach ($tiedostot as $tiedosto) {
    $tiedosto_polku = $finvoice_myyntilasku_kansio.$tiedosto;

    if (file_exists($tiedosto_polku)) {
      $xml = simplexml_load_file($tiedosto_polku);
      if (!$xml) {
        // File read failure, siirret‰‰n tiedosto error kansioon
        siirra_tiedosto_kansioon($tiedosto_polku, $finvoice_myyntilasku_kansio_error);

        die("Tiedoston {$tiedosto_polku} lukeminen ep‰onnistui\n");
      }

      kasittele_xml_tiedosto($xml, $yhtio);
      unset($xml);

      echo $kasitellyt_tilaukset['tilausnumero_count']." tilausta luotiin ja niihin ".$kasitellyt_tilaukset['tiliointi_count']." tiliˆinti‰\n";

      if (!$_REQUEST['debug']) {
        siirra_tiedosto_kansioon($tiedosto_polku, $finvoice_myyntilasku_kansio_valmis);
      }
    }
    else {
      die("Tiedostoa ei ole olemassa\n");
    }
  }
}
else {
  echo "Yht‰‰n tiedostoa ei lˆytynyt\n";
}

function lue_tiedostot($polku) {
  $tiedostot = array();
  if ($handle = opendir($polku)) {
    while (false !== ($tiedosto = readdir($handle))) {
      if ($tiedosto != "." && $tiedosto != "..") {
        if (is_file($polku.$tiedosto)) {
            $tiedostot[] = $tiedosto;
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

function kasittele_xml_tiedosto(SimpleXMLElement $xml) {
  global $kukarow, $yhtiorow;

  if ($xml !== FALSE) {
    $file = "";

    # Parsitaan finvoice
    require "inc/verkkolasku-in-finvoice.inc";

    // N‰m‰ muuttujat kuuluisi olla setattuna:
    // kauniimpi linebreak
    $_lb = "\n";

    echo "{$_lb}{$_lb}LASKUNTIEDOT:{$_lb}";
    echo "01: ".$yhtio."{$_lb}";
    echo "02: ".$verkkotunnus_vas."{$_lb}";
    echo "03: ".$laskun_tyyppi."{$_lb}";
    echo "04: ".$laskun_numero."{$_lb}";
    echo "05: ".$laskun_ebid."{$_lb}";
    echo "06: ".$laskun_tapvm."{$_lb}";
    echo "07: ".$laskun_lapvm."{$_lb}";
    echo "08: ".$laskun_erapaiva."{$_lb}";
    echo "09: ".$laskun_kapvm."{$_lb}";
    echo "10: ".$laskun_kasumma."{$_lb}";
    echo "11: ".$laskuttajan_ovt."{$_lb}";
    echo "12: ".$laskuttajan_nimi."{$_lb}";
    echo "13: ".$laskuttajan_vat."{$_lb}";
    echo "14: ".$laskun_pankkiviite."{$_lb}";
    echo "14.2: ".$laskun_iban."{$_lb}";
    echo "14.4: ".$laskun_bic."{$_lb}";
    echo "15: ".$laskun_asiakastunnus."{$_lb}";
    echo "16: ".$laskun_summa_eur."{$_lb}";
    echo "17: ".$laskun_tilausviite."{$_lb}";
    echo "18: ".$kauttalaskutus."{$_lb}";
    echo "19: ".$laskun_asiakkaan_tilausnumero."{$_lb}";
    echo "20: ".$toim_asiakkaantiedot["toim_ovttunnus"]."{$_lb}";
    echo "21: ".$toim_asiakkaantiedot["ytunnus"]."{$_lb}";
    echo "22: ".$toim_asiakkaantiedot["nimi"]."{$_lb}";
    echo "23: ".$toim_asiakkaantiedot["osoite"]."{$_lb}";
    echo "24: ".$toim_asiakkaantiedot["postino"]."{$_lb}";
    echo "25: ".$toim_asiakkaantiedot["postitp"]."{$_lb}";
    echo "25: ".$toim_asiakkaantiedot["maa"]."{$_lb}";
    echo "26: ".$ostaja_asiakkaantiedot["toim_ovttunnus"]."{$_lb}";
    echo "27: ".$ostaja_asiakkaantiedot["ytunnus"]."{$_lb}";
    echo "28: ".$ostaja_asiakkaantiedot["nimi"]."{$_lb}";
    echo "29: ".$ostaja_asiakkaantiedot["osoite"]."{$_lb}";
    echo "30: ".$ostaja_asiakkaantiedot["postino"]."{$_lb}";
    echo "31: ".$ostaja_asiakkaantiedot["postitp"]."{$_lb}";
    echo "31: ".$ostaja_asiakkaantiedot["maa"]."{$_lb}";
    echo "32: ".$laskuttajan_toimittajanumero."{$_lb}";
    echo "33: ".$laskuttajan_valkoodi."{$_lb}";
    echo "34: ".$laskun_toimitunnus."{$_lb}";

    #$asiakas = tarkista_asiakas_olemassa($toim_asiakkaantiedot, $ostaja_asiakkaantiedot);

    // Onko laskuriveill‰ useita alv-verokantoja?
    $ealvi = array_unique($ealvi);

    $alv = 0;
    foreach ($ealvi as $ealv) {
      if ($ealv > 0) {
        $alv = 24;
      }
    }

    $vienti = "";
    if (!empty($ostaja_asiakkaantiedot["maa"])) {
      $query = "SELECT distinct koodi, eu
                FROM maat
                WHERE koodi = '$ostaja_asiakkaantiedot[maa]'";
      $maaresult = pupe_query($query);

      if (mysql_num_rows($maaresult)) {
        $maarow = mysql_fetch_assoc($maaresult);

        if ($maarow["koodi"] == "FI") {
          $vienti = "";
        }
        elseif ($maarow["eu"] != ""){
          $vienti = "E";
        }
        else {
          $vienti = "K";
        }
      }
    }

    $laskun_tapvm = substr($laskun_tapvm, 0, 4)."-".substr($laskun_tapvm, 4, 2)."-".substr($laskun_tapvm, 6, 2);
    $laskun_lapvm = substr($laskun_lapvm, 0, 4)."-".substr($laskun_lapvm, 4, 2)."-".substr($laskun_lapvm, 6, 2);
    $laskun_erapaiva = substr($laskun_erapaiva, 0, 4)."-".substr($laskun_erapaiva, 4, 2)."-".substr($laskun_erapaiva, 6, 2);
    $laskun_kapvm = substr($laskun_kapvm, 0, 4)."-".substr($laskun_kapvm, 4, 2)."-".substr($laskun_kapvm, 6, 2);

    $query = "INSERT INTO lasku
              SET yhtio          = '{$yhtiorow['yhtio']}',
              yhtio_nimi         = '{$yhtiorow['nimi']}',
              yhtio_osoite       = '{$yhtiorow['osoite']}',
              yhtio_postino      = '{$yhtiorow['postino']}',
              yhtio_postitp      = '{$yhtiorow['postitp']}',
              yhtio_maa          = '{$yhtiorow['maa']}',
              liitostunnus       = '19535',
              ytunnus            = '{$ostaja_asiakkaantiedot['ytunnus']}',
              nimi               = '{$ostaja_asiakkaantiedot['nimi']}',
              osoite             = '{$ostaja_asiakkaantiedot['osoite']}',
              postino            = '{$ostaja_asiakkaantiedot['postino']}',
              postitp            = '{$ostaja_asiakkaantiedot['postitp']}',
              maa                = '{$ostaja_asiakkaantiedot['maa']}',
              toim_nimi          = '{$toim_asiakkaantiedot['nimi']}',
              toim_osoite        = '{$toim_asiakkaantiedot['osoite']}',
              toim_postino       = '{$toim_asiakkaantiedot['postino']}',
              toim_postitp       = '{$toim_asiakkaantiedot['postitp']}',
              toim_maa           = '{$toim_asiakkaantiedot['maa']}',
              valkoodi           = '{$laskuttajan_valkoodi}',
              summa              = '{$laskun_summa_eur}',
              summa_valuutassa   = '{$laskun_summa_eur}',
              kapvm              = '{$laskun_kapvm}',
              kasumma            = '{$laskun_kasumma}',
              kasumma_valuutassa = '{$laskun_kasumma}',
              vienti_kurssi      = 1,
              laatija            = 'finvoice_myyntilasku',
              luontiaika         = now(),
              viite              = '{$laskun_pankkiviite}',
              laskunro           = '{$laskun_numero}',
              maksuehto          = '14 pv netto',
              tapvm              = '{$laskun_tapvm}',
              erpcm              = '{$laskun_erapaiva}',
              lapvm              = '{$laskun_lapvm}',
              toimaika           = '{$laskun_lapvm}',
              kerayspvm          = '{$laskun_lapvm}',
              alv                = '{$alv}',
              vienti             = '$vienti',
              tila               = 'L',
              alatila            = 'X',
              viikorkopros       = '{$yhtiorow['viivastyskorko']}'";
    pupe_query($query);
    $tunnukset = mysql_insert_id();

    $query = "INSERT INTO laskun_lisatiedot
              SET otunnus       = '{$tunnukset}',
              yhtio             = '{$yhtiorow['yhtio']}',
              laskutus_nimi     = '{$ostaja_asiakkaantiedot['nimi']}',
              laskutus_osoite   = '{$ostaja_asiakkaantiedot['osoite']}',
              laskutus_postino  = '{$ostaja_asiakkaantiedot['postino']}',
              laskutus_postitp  = '{$ostaja_asiakkaantiedot['postitp']}',
              laskutus_maa      = '{$ostaja_asiakkaantiedot['maa']}',
              laatija           = 'finvoice_myyntilasku',
              luontiaika        = NOW()";
    pupe_query($query);

    # Luodaan rivit
    // N‰m‰ muuttujat ovat valinnaisia:
    /*
    RIVINTIEDOT:
    $rtuoteno[]["ale"]
    $rtuoteno[]["alv"]
    $rtuoteno[]["hinta"]
    $rtuoteno[]["kauttalaskutus"]
    $rtuoteno[]["kommentti"]
    $rtuoteno[]["kpl"]
    $rtuoteno[]["laskutettuaika"]
    $rtuoteno[]["nimitys"]
    $rtuoteno[]["rivihinta"]
    $rtuoteno[]["rivihinta_verolli"]
    $rtuoteno[]["riviinfo"]
    $rtuoteno[]["riviviite"]
    $rtuoteno[]["tilaajanrivinro"]
    $rtuoteno[]["tuoteno"]
    $rtuoteno[]["yksikko"]
    $rtuoteno[]["tilinumero"]
    $rtuoteno[]["rivihinta_valuutassa"]
    */
    foreach ($rtuoteno as $tuoterivi) {
      $query = "INSERT into tilausrivi set
                kpl                  = '{$tuoterivi['kpl']}',
                tilkpl               = '{$tuoterivi['kpl']}',
                otunnus              = {$tunnukset},
                kerayspvm            = '{$laskun_lapvm}',
                laatija              = 'finvoice_myyntilasku',
                laadittu             = {$laskun_lapvm},
                toimitettu           = 'finvoice_myyntilasku',
                toimitettuaika       = '{$laskun_lapvm}',
                keratty              = 'finvoice_myyntilasku',
                kerattyaika          = '{$laskun_lapvm}',
                toimaika             = '{$laskun_lapvm}',
                laskutettu           = 'finvoice_myyntilasku',
                laskutettuaika       = {$laskun_lapvm},
                yhtio                = '{$yhtiorow['yhtio']}',
                tuoteno              = '{$tuoterivi['tuoteno']}',
                ale1                 = '{$tuoterivi['ale']}',
                yksikko              = '{$tuoterivi['yksikko']}',
                try                  = '',
                osasto               = '',
                alv                  = '{$tuoterivi['alv']}',
                hinta                = '{$tuoterivi['hinta']}',
                nimitys              = '{$tuoterivi['nimitys']}',
                kate                 = '0',
                rivihinta            = '{$tuoterivi['rivihinta']}',
                rivihinta_valuutassa = '{$tuoterivi['rivihinta']}',
                tyyppi               = 'L',
                kommentti            = '{$tuoterivi['kommentti']}'";
      pupe_query($query);
    }

    // Kustannuspaikkak‰sittely
    #$kustannuspaikka = hae_kustannuspaikka($myyntisaamiset_array['kustp'], $yhtio);
    #paivita_kustannuspaikka_asiakkaalle($asiakas, $kustannuspaikka);
    $kateinen = "";

    // Tehd‰‰n ulasku ja tiliˆid‰‰n lasku
    require "tilauskasittely/teeulasku.inc";
  }
}

function konvertoi_maksuehto($maksuehto) {
  global $kukarow, $yhtiorow;

  $maksuehto = (string) $maksuehto;

  // Etsi sopiva maksuehto...
}

function tarkista_asiakas_olemassa($myyntilasku, $yhtio) {
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio     = '{$yhtio}'
            AND asiakasnro  = '{$myyntilasku['asiakasnumero']}'
            AND laji       != 'P'
            LIMIT 1";
  $result = pupe_query($query);
  if (mysql_num_rows($result) == 0) {
    //jos asiakasta ei lˆydy tarkistetaan lˆytyisikˆ se finvoice_myyntilaskun puolelta. tarkista_asiakas_finvoice_myyntilaskuista() palauttaa vain yhden asiakasnumeron, jos sille antaa yhden asiakasnumeron
    $palautetut_asiakasnumerot = tarkista_asiakas_finvoice_myyntilaskuista_ja_tuo_pupesoftiin(array($myyntilasku['asiakasnumero']));
    if ($palautetut_asiakasnumerot) {
      echo "Asiakas lˆytyis finvoice_myyntilaskuista ja se tuotiin pupesoftiin ".implode(',', $palautetut_asiakasnumerot).".\n";
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio     = '{$yhtio}'
                AND asiakasnro  IN ('".implode("','", $palautetut_asiakasnumerot)."')
                AND laji       != 'P'
                LIMIT 1";
      $result = pupe_query($query);
    }
    if (mysql_num_rows($result) == 0) {
      //jos asiakasta ei lˆydy, laskulle laitetaan asiakkaan kenttiin tyhj‰t arvot sek‰ futurista tullut asiakasnumero ytunnus kentt‰‰n, jotta asiakkaan pupesoftiin perustamisen j‰lkeen, asiakas voidaan liitt‰‰ kyseiseen laskuun
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

  // p‰ivitet‰‰n vain jos asiakkaalla ei t‰ll‰ hetkell‰ ole kustannuspaikkaa
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
