<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}

if (!isset($argv[1])) {
  echo "Anna Puperoot\n";
  exit(1);
}

$pupesoft_root = $argv[1];

if (!is_dir($pupesoft_root) or !is_file("{$pupesoft_root}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root);

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

// vain toimipaikkoihin sidotut käyttäjät käydään tässä läpi (hallinto it ym on jo molemmissa yrityksissä ns kunnossa, joten ei tehdä niille mitään)
$query = "SELECT *
          FROM kuka
          WHERE yhtio      = 'atarv'
          AND toimipaikka != 0
          AND kuka        != 'admin'";
$query_result = mysql_query($query) or pupe_error($query);

while ($query_row = mysql_fetch_assoc($query_result)) {
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = 'artr'
            AND kuka    = '{$query_row['kuka']}'";
  $query_result2 = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($query_result2) == 1) {

    $query_row2 = mysql_fetch_assoc($query_result2);

    // Pitäisi päivittää tiedot Masista Örumille

    // Muutetaan profiilit täs välis jakelun profiileiks (skriptin lopuksi oikeu-taulun päivitykset samal logiikal)
    $profiili = "";
    $_profiilit = explode(',', $query_row['profiilit']);
    foreach ($_profiilit as $_profiili) {
      $profiili .= "Jakelu_".$_profiili.",";
    }
    $query_row['profiilit'] = substr($profiili, 0, -1);

    $query = "UPDATE kuka SET
              nimi                          = '{$query_row['nimi']}',
              salasana                      = '{$query_row['salasana']}',
              ip                            = '{$query_row['ip']}',
              profiilit                     = '{$query_row['profiilit']}',
              piirit                        = '{$query_row['piirit']}',
              naytetaan_katteet_tilauksella = '{$query_row['naytetaan_katteet_tilauksella']}',
              naytetaan_asiakashinta        = '{$query_row['naytetaan_asiakashinta']}',
              naytetaan_tuotteet            = '{$query_row['naytetaan_tuotteet']}',
              naytetaan_tilaukset           = '{$query_row['naytetaan_tilaukset']}',
              jyvitys                       = '{$query_row['jyvitys']}',
              hyvaksyja                     = '{$query_row['hyvaksyja']}',
              hyvaksyja_maksimisumma        = '{$query_row['hyvaksyja_maksimisumma']}',
              hierarkia                     = '{$query_row['hierarkia']}',
              extranet                      = '{$query_row['extranet']}',
              kayttoliittyma                = '{$query_row['kayttoliittyma']}',
              oletus_ohjelma                = '{$query_row['oletus_ohjelma']}',
              oletus_asiakas                = '{$query_row['oletus_asiakas']}',
              oletus_asiakastiedot          = '{$query_row['oletus_asiakastiedot']}',
              oletus_profiili               = '{$query_row['oletus_profiili']}',
              kassamyyja                    = '{$query_row['kassamyyja']}',
              kassalipas_otto               = '{$query_row['kassalipas_otto']}',
              kirjoitin                     = '{$query_row['kirjoitin']}',
              varasto                       = '{$query_row['varasto']}',
              oletus_varasto                = '{$query_row['oletus_varasto']}',
              oletus_ostovarasto            = '{$query_row['oletus_ostovarasto']}',
              oletus_pakkaamo               = '{$query_row['oletus_pakkaamo']}',
              fyysinen_sijainti             = '{$query_row['fyysinen_sijainti']}',
              keraysvyohyke                 = '{$query_row['keraysvyohyke']}',
              max_keraysera_alustat         = '{$query_row['max_keraysera_alustat']}',
              saatavat                      = '{$query_row['saatavat']}',
              hinnat                        = '{$query_row['hinnat']}',
              taso                          = '{$query_row['taso']}',
              kesken                        = '{$query_row['kesken']}',
              lastlogin                     = '{$query_row['lastlogin']}',
              keraajanro                    = '{$query_row['keraajanro']}',
              osasto                        = '{$query_row['osasto']}',
              myyja                         = '{$query_row['myyja']}',
              oletustili                    = '{$query_row['oletustili']}',
              myyjaryhma                    = '{$query_row['myyjaryhma']}',
              tuuraaja                      = '{$query_row['tuuraaja']}',
              kieli                         = '{$query_row['kieli']}',
              lomaoikeus                    = '{$query_row['lomaoikeus']}',
              asema                         = '{$query_row['asema']}',
              dynaaminen_kassamyynti        = '{$query_row['dynaaminen_kassamyynti']}',
              toimipaikka                   = '{$query_row['toimipaikka']}',
              eposti                        = '{$query_row['eposti']}',
              puhno                         = '{$query_row['puhno']}',
              tilaus_valmis                 = '{$query_row['tilaus_valmis']}',
              mitatoi_tilauksia             = '{$query_row['mitatoi_tilauksia']}',
              session                       = '{$query_row['session']}',
              api_key                       = '{$query_row['api_key']}',
              budjetti                      = '{$query_row['budjetti']}',
              aktiivinen                    = '{$query_row['aktiivinen']}'
              WHERE yhtio                   = 'artr' AND kuka = '{$query_row2['kuka']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);
  }
  else {
    // siirretään atarv käyttäjä artr semmoisenaan
    // Muutetaan profiilit täs välis jakelun profiileiks (skriptin lopuksi oikeu-taulun päivitykset samal logiikal)
    $profiili = "";
    $_profiilit = explode(',', $query_row['profiilit']);
    foreach ($_profiilit as $_profiili) {
      $profiili .= "Jakelu_".$_profiili.",";
    }
    $query_row['profiilit'] = substr($profiili, 0, -1);

    $query = "UPDATE kuka SET yhtio = 'artr', profiilit = '{$query_row['profiilit']}' WHERE yhtio = 'atarv' AND kuka = '{$query_row['kuka']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);
  }
}

// nimetään Mas profiilit ja siirretään ne atarv -> artr
$query = "UPDATE oikeu SET
          kuka         = concat('Jakelu_', kuka),
          profiili     = kuka,
          yhtio        = 'artr'
          WHERE yhtio  = 'atarv'
          AND kuka     = profiili
          AND kuka    != ''";
$query_result = mysql_query($query) or pupe_error($query);

// käyttöoikeudet atarv -> artr (ei siirretä jos käyttäjällä on jo oikeus)
$query = "SELECT *
          FROM oikeu
          WHERE yhtio  = 'atarv'
          AND kuka    != profiili
          AND kuka    != ''";
$query_result = mysql_query($query) or pupe_error($query);
while ($query_row = mysql_fetch_assoc($query_result)) {

  $query = "SELECT tunnus
            FROM oikeu
            WHERE yhtio  = 'artr'
            AND kuka     = '{$query_row['kuka']}'
            AND sovellus = '{$query_row['sovellus']}'
            AND nimi     = '{$query_row['nimi']}'
            AND alanimi  = '{$query_row['alanimi']}'";
  $query_result2 = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($query_result2) == 0) {
    $query = "UPDATE oikeu SET
              yhtio       = 'artr'
              WHERE yhtio = 'atarv'
              AND tunnus  = '{$query_row['tunnus']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);
  }
}
