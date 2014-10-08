#!/usr/bin/php
<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

$pupesoft_polku = dirname(__FILE__);

// otetaan tietokanta connect
require $pupesoft_polku."/inc/connect.inc";
require $pupesoft_polku."/inc/functions.inc";

// Logitetaan ajo
cron_log();

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ss�
$verkkolaskut_in     = empty($verkkolaskut_in)     ? "/home/verkkolaskut"        : rtrim($verkkolaskut_in, "/");
$verkkolaskut_ok     = empty($verkkolaskut_ok)     ? "/home/verkkolaskut/ok"     : rtrim($verkkolaskut_ok, "/");
$verkkolaskut_orig   = empty($verkkolaskut_orig)   ? "/home/verkkolaskut/orig"   : rtrim($verkkolaskut_orig, "/");
$verkkolaskut_error  = empty($verkkolaskut_error)  ? "/home/verkkolaskut/error"  : rtrim($verkkolaskut_error, "/");
$verkkolaskut_reject = empty($verkkolaskut_reject) ? "/home/verkkolaskut/reject" : rtrim($verkkolaskut_reject, "/");

// VIRHE: verkkolasku-kansiot on v��rin m��ritelty!
if (!is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) exit;
if (!is_dir($verkkolaskut_ok) or !is_writable($verkkolaskut_ok)) exit;
if (!is_dir($verkkolaskut_orig) or !is_writable($verkkolaskut_orig)) exit;
if (!is_dir($verkkolaskut_error) or !is_writable($verkkolaskut_error)) exit;
if (!is_dir($verkkolaskut_reject) or !is_writable($verkkolaskut_reject)) exit;

function apix_receive($apix_keys) {

  // Asetukset
  $software = "Pupesoft";
  $version  = "1.0";

  //$url = "https://test-terminal.apix.fi/receive";
  $url = "https://terminal.apix.fi/receive";

  $timestamp  = gmdate("YmdHis");

  // Muodostetaan apixin vaatima salaus ja url
  $digest_src = "$software+$version+".$apix_keys['apix_tunnus']."+".$timestamp."+".$apix_keys['apix_avain'];
  $dt  = substr(hash("sha256", $digest_src), 0, 64);
  $real_url = "$url?TraID={$apix_keys['apix_tunnus']}&t=$timestamp&soft=$software&ver=$version&d=SHA-256:$dt";

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $real_url);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close($ch);

  return $response;
}

// Haetaan api_keyt yhtion_parametreist�
$sql_query = "SELECT yhtion_parametrit.apix_tunnus, yhtion_parametrit.apix_avain, yhtio.nimi
              FROM yhtio
              JOIN yhtion_parametrit USING (yhtio)
              WHERE yhtion_parametrit.apix_tunnus != ''
              AND yhtion_parametrit.apix_avain    != ''";
$apix_result = pupe_query($sql_query);

while ($apix_keys = mysql_fetch_assoc($apix_result)) {

  while (($response = apix_receive($apix_keys)) != "") {

    // Randomstringi filenimiin
    $apix_nimi = md5(uniqid(mt_rand(), true));

    // Luodaan temppidirikka jonne ty�nnet��n t�n haun kaikki apixfilet
    $apix_tmpdirnimi = "/tmp/apix-".md5(uniqid(mt_rand(), true));

    if (mkdir($apix_tmpdirnimi)) {

      $tiedosto = $apix_tmpdirnimi."/apix_laskupaketti.zip";
      $fd = fopen($tiedosto, "w") or die("Tiedostoa ei voitu luoda!");
      fwrite($fd, $response);

      $zip = new ZipArchive();

      if ($zip->open($tiedosto) === TRUE) {

        if ($zip->extractTo($apix_tmpdirnimi)) {

          // Loopataan tiedostot l�pi
          for ($i = 0; $i < $zip->numFiles; $i++) {

            $file = $zip->getNameIndex($i);

            if (strtoupper(substr($file, -4)) == ".XML") {
              // T�m� on itse verkkolaskuaineisto
              rename($apix_tmpdirnimi."/".$file, $verkkolaskut_in."/apix_".$apix_nimi."_apix-$file");

              echo "Haettiin lasku yritykselle: {$apix_keys['nimi']}\n";
            }
            else {
              // N�m� ovat liitteit�
              rename($apix_tmpdirnimi."/".$file, $verkkolaskut_orig."/apix_".$apix_nimi."_apix-$file");
            }
          }
        }
      }

      fclose($fd);

      // Poistetaan apix-tmpdir
      exec("rm -rf $apix_tmpdirnimi");
    }
    else {
      echo "Virhe APIX-tiedoston purkamisessa!\n";
    }
  }
}
