#!/usr/bin/php
<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

// otetaan tietokanta connect
require $pupe_root_polku."/inc/connect.inc";
require $pupe_root_polku."/inc/functions.inc";

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
}

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

if (!isset($kukarow)) {
  echo "VIRHE: admin-käyttäjää ei löydy!\n";
  exit;
}

// Logitetaan ajo
cron_log();

function huutokaupat_receive($date) {
  global $huutokaupat_url, $huutokaupat_company, $huutokaupat_secret;

  $urli = "{$huutokaupat_url}?company={$huutokaupat_company}&secret={$huutokaupat_secret}&date={$date}";

  $ch  = curl_init();

  curl_setopt($ch, CURLOPT_URL, $urli);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_USERAGENT, "Pupesoft");

  $tapahtumat = curl_exec($ch);

  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($code != 200) {
    return "";
  }
  else {
    $tapahtumat = json_decode($tapahtumat);

    return $tapahtumat;
  }
}

// Haetaan aika jolloin tämä skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("HK_CRON");
$today = date("Y-m-d");

if ($datetime_checkpoint == "") {
  $datetime_checkpoint = date("Y-m-d", strtotime("-1 days", strtotime($today)));
}

if ($huutokaupat_url == "") {
  exit;
}
if ($huutokaupat_company == "") {
  exit;
}
if ($huutokaupat_secret == "") {
  exit;
}
if ($huutokaupat_tilino == '') {
  exit;
}
if ($huutokaupat_payername == '') {
  exit;
}

// Otetaan default, jos ei olla yliajettu salasanat.php:ssä
$tiliotteet_in = empty($tiliotteet_in) ? "/home/tiliotteet" : rtrim($tiliotteet_in, "/");
$tiliotteet_ok = empty($tiliotteet_ok) ? "/home/tiliotteet/ok" : rtrim($tiliotteet_ok, "/");

// VIRHE: verkkolasku-kansio on väärin määritelty!
if (!is_dir($tiliotteet_in) or !is_writable($tiliotteet_in)) exit;
if (!is_dir($tiliotteet_ok) or !is_writable($tiliotteet_ok)) exit;

while ($datetime_checkpoint < $today) {

  $response = huutokaupat_receive($datetime_checkpoint);

  if ($response != "") {

    // Tallennetaan rivit tiedostoon
    $nimi = md5(uniqid(mt_rand(), true));
    $file = "{$nimi}_{$response->date}.txt";
    $filepath = "/tmp/{$file}";
    $fd = fopen($filepath, "w") or die("Tiedostoa ei voitu luoda!");

    $maksupvm = $response->date;
    $maksupvm = date("ymj", strtotime($maksupvm));

    // tietue 0
    $ulos  = sprintf('%-1.1s', 0);
    $ulos .= sprintf('%06.6s', $maksupvm);
    $ulos .= sprintf('%04.4s', "0000");
    $ulos .= sprintf('%-2.2s', 5);
    $ulos .= sprintf('%-9.9s', "");
    $ulos .= sprintf('%-1.1s', 1);
    $ulos .= "\r\n";

    foreach ($response->entries as $_entry) {

      // tietue 3
      $ulos .= sprintf('%-1.1s', 3);
      $ulos .= sprintf('%-14.14s', $huutokaupat_tilino);
      $ulos .= sprintf('%06.6s', $maksupvm);
      $ulos .= sprintf('%06.6s', $maksupvm);
      $ulos .= sprintf('%-16.16s', "");
      $ulos .= sprintf('%020.20s', $_entry->reference);
      $ulos .= sprintf('%-12.12s', $huutokaupat_payername);
      $ulos .= sprintf('%-1.1s', 1);
      $ulos .= sprintf('%-1.1s', "");
      $ulos .= sprintf('%010.10s', $_entry->amount * 100);
      $ulos .= sprintf('%01.1s', 0);
      $ulos .= "\r\n";
    }

    fwrite($fd, $ulos);
    fclose($fd);

    rename($filepath, $tiliotteet_in."/{$file}");

    // Laukaistaan itse sisäänajo
    exec("/bin/bash {$pupe_root_polku}/tiliote.sh {$tiliotteet_in} {$tiliotteet_ok} > /dev/null 2>/dev/null &");
  }

  // haetaan tarvittaessa seuraavan päivän aineisto
  $datetime_checkpoint = date("Y-m-d", strtotime("+1 days", strtotime($datetime_checkpoint)));
}

// Tallennetaan aikaleima seuraavaa hakua varten
cron_aikaleima("HK_CRON", $datetime_checkpoint);
