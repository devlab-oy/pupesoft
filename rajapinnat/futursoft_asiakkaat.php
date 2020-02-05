<?php

ini_set("memory_limit", "5G");

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
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

        die("Tiedoston {$tiedosto_polku} lukeminen epäonnistui");
      }
      $asiakas_data = parsi_xml_tiedosto($xml);

      $kasitellyt_asiakkaat = kasittele_asiakkaat_data($asiakas_data, $yhtio);

      echo $kasitellyt_asiakkaat['asiakas_count']." asiakas luotiin / päivitettiin\n";

      siirra_tiedosto_kansioon($tiedosto_polku, $futursoft_kansio_valmis);
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
          if (stristr($tiedosto, 'customers')) {
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
  $data = array();
  if ($xml !== FALSE) {
    foreach ($xml->CustTable as $asiakas) {
      $data[] = array(
        'asiakasnumero'   => (string)$asiakas->AccountNum,
        'nimi'       => utf8_decode(htmlspecialchars_decode((string)$asiakas->Name)),
        'nimi_alias'   => utf8_decode((string)$asiakas->NameAlias),
        'asiakas_ryhma'   => (string)$asiakas->CustGroup,
        'valuutta'     => (string)$asiakas->Currency,
        'kieli'       => (string)$asiakas->LanguageId,
        'max_luotto'   => (float)$asiakas->CreditMax,
        'ytunnus'     => ((string)$asiakas->VATNum == '') ? '' : str_replace(' ', '', 'FI'.str_replace('-', '', (string)$asiakas->VATNum)),
        'ovttunnus'     => ((string)$asiakas->VATNum == '') ? '' : str_replace(' ', '', '0037'.str_replace('-', '', (string)$asiakas->VATNum)),
        'osoite'     => utf8_decode((string)$asiakas->Street),
        'postino'     => (string)$asiakas->ZipCode,
        'kaupunki'     => utf8_decode((string)$asiakas->City),
        'maa'       => (string)$asiakas->Country,
        'puhelin'     => (string)$asiakas->Phone,
        'fax'       => (string)$asiakas->Telefax,
        'maksuehto'     => (string)$asiakas->PaymTermId,
        'status'     => (string)$asiakas->StatusCode,
        'vero_ryhma'   => (string)$asiakas->TaxGroup,
        'dim'       => (string)$asiakas->Dim1,
        'kustp'       => (string)$asiakas->Dim2,
      );
    }
  }

  return $data;
}


function kasittele_asiakkaat_data($asiakkaat, $yhtio) {
  $asiakas_count = 0;
  foreach ($asiakkaat as $asiakas) {
    paivita_tai_luo_asiakas($asiakas, $yhtio);
    $asiakas_count++;
  }

  return array(
    'asiakas_count' => $asiakas_count,
  );
}


function paivita_tai_luo_asiakas($asiakas, $yhtio) {
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio     = '{$yhtio}'
            AND asiakasnro  = '{$asiakas['asiakasnumero']}'
            AND laji       != 'P'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    paivita_asiakas($asiakas, $yhtio, $result);
  }
  else {
    luo_asiakas($asiakas, $yhtio);
  }
}


function luo_asiakas($asiakas, $yhtio) {
  $maksuehto = tarkista_maksuehto($asiakas['maksuehto'], $yhtio);
  $toimitustapa = hae_toimitustapa($yhtio);

  $query = "INSERT INTO asiakas
            SET yhtio = '{$yhtio}',
            ytunnus         = '{$asiakas['ytunnus']}',
            ovttunnus       = '{$asiakas['ovttunnus']}',
            nimi            = '{$asiakas['nimi']}',
            osoite          = '{$asiakas['osoite']}',
            postino         = '{$asiakas['postino']}',
            postitp         = '{$asiakas['kaupunki']}',
            maa             = '{$asiakas['maa']}',
            kansalaisuus    = '{$asiakas['maa']}',
            puhelin         = '{$asiakas['puhelin']}',
            gsm             = '{$asiakas['puhelin']}',
            fax             = '{$asiakas['fax']}',
            kieli           = '".strtolower($asiakas['kieli'])."',
            valkoodi        = '{$asiakas['valuutta']}',
            maksuehto       = '{$maksuehto['tunnus']}',
            toimitustapa    = '{$toimitustapa['selite']}',
            luottoraja      = '{$asiakas['max_luotto']}',
            kustannuspaikka = '{$asiakas['kustp']}',
            laatija         = 'futursoft',
            luontiaika      = NOW(),
            asiakasnro      = '{$asiakas['asiakasnumero']}'";
  pupe_query($query);

  echo "Asiakas ".$yhtio." ".$asiakas['nimi']." ".$asiakas['asiakasnumero']." luotiin\n";
  $parametrit = array("to"     => $yhtiorow['talhal_email'],
    "subject"   => "Uusi asiakas luotu pupeen!",
    "ctype"     => "html",
    "body"     => "Asiakas ".$yhtio." ".$asiakas['nimi']." ".$asiakas['asiakasnumero']." luotiin", );

  pupesoft_sahkoposti($parametrit);
}


function paivita_asiakas($asiakas, $yhtio, $olemassa_oleva_result) {
  $olemassa_oleva_asiakas_row = mysql_fetch_assoc($olemassa_oleva_result);
  $maksuehto = tarkista_maksuehto($asiakas['maksuehto'], $yhtio);
  $toimitustapa = hae_toimitustapa($yhtio);

  $query = "UPDATE asiakas
            SET nimi = '{$asiakas['nimi']}',
            osoite       = '{$asiakas['osoite']}',
            postino      = '{$asiakas['postino']}',
            postitp      = '{$asiakas['kaupunki']}',
            maa          = '{$asiakas['maa']}',
            kansalaisuus = '{$asiakas['maa']}',
            puhelin      = '{$asiakas['puhelin']}',
            gsm          = '{$asiakas['puhelin']}',
            fax          = '{$asiakas['fax']}',
            kieli        = '".strtolower($asiakas['kieli'])."',
            valkoodi     = '{$asiakas['valuutta']}',
            maksuehto    = '{$maksuehto['tunnus']}',
            toimitustapa = '{$toimitustapa['selite']}',
            muuttaja     = 'futursoft',
            muutospvm    = NOW()
            WHERE yhtio  = '{$yhtio}'
            AND tunnus   = '{$olemassa_oleva_asiakas_row['tunnus']}'";
  pupe_query($query);

  // echo "Asiakas ".$yhtio." ".$asiakas['nimi']." ".$asiakas['asiakasnumero']." päivitettiin\n";
}


function tarkista_maksuehto($maksuehto, $yhtio) {
  $futur_xml_me = array(
    901   => 1551,
    904   => 1535,
    906   => 1520,
    907   => 1515,
    908   => 1513,
    909   => 2019,
    910   => 1551,
    912   => 1514,
    931   => 1494,
    932   => 1525,
    933   => 1500,
    934   => 1496,
    936   => 1538,
  );
  if (array_key_exists($maksuehto, $futur_xml_me)) {
    $maksuehto = $futur_xml_me[$maksuehto];
  }
  // jos maksuehtoa ei löydy, laitetaan heti
  else {
    $maksuehto = 1548;
  }

  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio = '{$yhtio}'
            AND tunnus  = '{$maksuehto}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    return mysql_fetch_assoc($result);
  }
  else {
    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio = '{$yhtio}'
              AND teksti  LIKE '%heti%'
              LIMIT 1";
    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
  }
}


function hae_toimitustapa($yhtio) {
  $query = "SELECT *
            FROM toimitustapa
            WHERE yhtio = '{$yhtio}'
            AND selite  LIKE '%Oletus%'
            LIMIT 1";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}
