#!/usr/bin/php
<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupesoft_polku = dirname(__FILE__);

// otetaan tietokanta connect
require $pupesoft_polku."/inc/connect.inc";
require $pupesoft_polku."/inc/functions.inc";

// Logitetaan ajo
cron_log();

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ssä
$editilaus_in     = empty($editilaus_in)     ? "/home/editilaus"        : rtrim($editilaus_in, "/");
$editilaus_ok     = empty($editilaus_ok)     ? "/home/editilaus/ok"     : rtrim($editilaus_ok, "/");
$editilaus_orig   = empty($editilaus_orig)   ? "/home/editilaus/orig"   : rtrim($editilaus_orig, "/");
$editilaus_error  = empty($editilaus_error)  ? "/home/editilaus/error"  : rtrim($editilaus_error, "/");
$editilaus_reject = empty($editilaus_reject) ? "/home/editilaus/reject" : rtrim($editilaus_reject, "/");

// VIRHE: editilaus-kansiot on väärin määritelty!
if (!is_dir($editilaus_in) or !is_writable($editilaus_in)) exit;
if (!is_dir($editilaus_ok) or !is_writable($editilaus_ok)) exit;
if (!is_dir($editilaus_orig) or !is_writable($editilaus_orig)) exit;
if (!is_dir($editilaus_error) or !is_writable($editilaus_error)) exit;
if (!is_dir($editilaus_reject) or !is_writable($editilaus_reject)) exit;

function apix_edi_receive($apix_keys) {

  // Asetukset
  $software = "PupesoftEDI";
  $version  = "1.0";

  // $url = "https://test-terminal.apix.fi/receive";
  $url = "https://terminal.apix.fi/receive";

  $timestamp  = gmdate("YmdHis");

  // Muodostetaan apixin vaatima salaus ja url
  $digest_src = "{$software}+{$version}+{$apix_keys['apix_edi_tunnus']}+{$timestamp}+{$apix_keys['apix_edi_avain']}";
  $dt  = substr(hash("sha256", $digest_src), 0, 64);
  $real_url = "{$url}?TraID={$apix_keys['apix_edi_tunnus']}&t={$timestamp}&soft={$software}&ver={$version}&d=SHA-256:{$dt}";

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $real_url);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close($ch);

  return $response;
}

// Haetaan api_keyt yhtion_parametreistä
$sql_query = "SELECT yhtion_parametrit.apix_edi_tunnus, yhtion_parametrit.apix_edi_avain, yhtio.nimi
              FROM yhtio
              JOIN yhtion_parametrit USING (yhtio)
              WHERE yhtion_parametrit.apix_edi_tunnus != ''
              AND yhtion_parametrit.apix_edi_avain    != ''";
$apix_result = pupe_query($sql_query);

while ($apix_keys = mysql_fetch_assoc($apix_result)) {

  while (($response = apix_edi_receive($apix_keys)) != "") {

    // Randomstringi filenimiin
    $apix_nimi = md5(uniqid(mt_rand(), true));

    // Luodaan temppidirikka jonne työnnetään tän haun kaikki apixfilet
    $apix_tmpdirnimi = "/tmp/apix-edi-".md5(uniqid(mt_rand(), true));

    if (mkdir($apix_tmpdirnimi)) {

      $tiedosto = $apix_tmpdirnimi."/apix_edipaketti.zip";
      $fd = fopen($tiedosto, "w") or die("Tiedostoa ei voitu luoda!");
      fwrite($fd, $response);

      $zip = new ZipArchive();

      if ($zip->open($tiedosto) === TRUE) {

        if ($zip->extractTo($apix_tmpdirnimi)) {

          // Loopataan tiedostot läpi
          for ($i = 0; $i < $zip->numFiles; $i++) {

            $file = $zip->getNameIndex($i);

            rename($apix_tmpdirnimi."/".$file, $editilaus_in."/apix_".$apix_nimi."_apix-$file");

            echo "Haettiin Apix-EDI yritykselle: {$apix_keys['nimi']}\n";
          }
        }
      }

      fclose($fd);

      // Poistetaan apix-tmpdir
      exec("rm -rf $apix_tmpdirnimi");
    }
    else {
      echo "Virhe Apix-EDI-tiedoston purkamisessa!\n";
    }
  }
}
