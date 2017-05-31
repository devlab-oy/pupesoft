#!/usr/bin/php
<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() == 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupesoft_polku = dirname(dirname(__FILE__));

// otetaan tietokanta connect
require $pupesoft_polku."/inc/connect.inc";
require $pupesoft_polku."/inc/functions.inc";

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
  curl_setopt($ch, CURLOPT_USERAGENT, “Pupesoft”);

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
$datetime_checkpoint = cron_aikaleima("HUUTOKAUPAT_CRON");
$today = date("Y-m-d");

if ($huutokaupat_url == "") {
  //error
}
if ($huutokaupat_company == "") {
  //error
}
if ($huutokaupat_secret == "") {
  //error
}
if ($huutokaupat_tilino == '') {
  //error
}
if ($huutokaupat_payername == '') {
  //error
}

// Otetaan default, jos ei olla yliajettu salasanat.php:ssä
$verkkolaskut_in = empty($verkkolaskut_in) ? "/home/verkkolaskut" : rtrim($verkkolaskut_in, "/");

// VIRHE: verkkolasku-kansio on väärin määritelty!
if (!is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) exit;

while ($datetime_checkpoint < $today) {

  $datetime_checkpoint = date("Y-m-d", strtotime("+1 days", strtotime($datetime_checkpoint)));

  while (($response = huutokaupat_receive($datetime_checkpoint)) != "") {

    // Tallennetaan rivit tiedostoon
    $nimi = md5(uniqid(mt_rand(), true));
    $file = "{$nimi}_{$response->date}.txt";
    $filepath = "/tmp/{$file}";
    $fd = fopen($filepath, "w") or die("Tiedostoa ei voitu luoda!");

    $maksupvm = $response->date;
    $maksupvm = date("ymj", strtotime($maksupvm));
    $luontiaika = date("hs", strtotime());

    // tietue 0
    $ulos  = sprintf('%-1.1s', 0);
    $ulos .= sprintf('%06.6s', $maksupvm);
    $ulos .= sprintf('%04.4s', $luontiaika);
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

    rename($filepath, $verkkolaskut_in."/{$file}");
  }

  // Tallennetaan aikaleima
  $datetime_checkpoint_uusi = date('Y-m-d');
  cron_aikaleima("HUUTOKAUPAT_CRON", $datetime_checkpoint_uusi);
}
