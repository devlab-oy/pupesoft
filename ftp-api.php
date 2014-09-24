#!/usr/bin/php
<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  echo "Tätä scriptiä voi ajaa vain komentoriviltä!";
  exit(1);
}

$pupesoft_polku = dirname(__FILE__);

// Otetaan tietokanta connect
require "{$pupesoft_polku}/inc/connect.inc";
require "{$pupesoft_polku}/inc/functions.inc";

// Otetaan defaultti, jos ei olla yliajettu salasanat.php:ssä
$verkkolaskut_in = empty($verkkolaskut_in) ? "/home/verkkolaskut" : rtrim($verkkolaskut_in, "/");

// VIRHE: verkkolasku-in on väärin määritelty!
if (!is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) {
  exit;
}

$sql_query = "SELECT verkkotunnus_vas, verkkosala_vas
              FROM yhtion_parametrit
              WHERE verkkotunnus_vas != ''
              AND verkkosala_vas != ''";
$api_result = pupe_query($sql_query);

while ($api_keys = mysql_fetch_assoc($api_result)) {
  // ftp-get vaatii komentiriviltä, että ensimmäinen parametri on annettu.
  $operaattori = $argv[1] = 'ftp-api';

  // ftp.verkkolasku.net tiedot ftp-getille
  $ftpget_host[$operaattori] = 'ftp.verkkolasku.net';
  $ftpget_path[$operaattori] = '/bills-new/by-ebid';
  $ftpget_dest[$operaattori] = $verkkolaskut_in;
  $ftpget_user[$operaattori] = $api_keys["verkkotunnus_vas"];
  $ftpget_pass[$operaattori] = $api_keys["verkkosala_vas"];

  // Haetaan kaikki tiedostot hakemistosta
  require "{$pupesoft_polku}/ftp-get.php";
}
