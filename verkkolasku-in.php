<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set("Europe/Helsinki");

if ($php_cli) {
  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokantayhteys
  require "inc/connect.inc";
  require_once "inc/functions.inc";
}

$lock_params = array(
  "locktime" => 5400,
);

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock($lock_params);

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ss‰
$verkkolaskut_in     = empty($verkkolaskut_in)     ? "/home/verkkolaskut"        : rtrim($verkkolaskut_in, "/");
$verkkolaskut_ok     = empty($verkkolaskut_ok)     ? "/home/verkkolaskut/ok"     : rtrim($verkkolaskut_ok, "/");
$verkkolaskut_orig   = empty($verkkolaskut_orig)   ? "/home/verkkolaskut/orig"   : rtrim($verkkolaskut_orig, "/");
$verkkolaskut_error  = empty($verkkolaskut_error)  ? "/home/verkkolaskut/error"  : rtrim($verkkolaskut_error, "/");
$verkkolaskut_reject = empty($verkkolaskut_reject) ? "/home/verkkolaskut/reject" : rtrim($verkkolaskut_reject, "/");

// VIRHE: verkkolasku-kansiot on v‰‰rin m‰‰ritelty!
if (!is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) exit;
if (!is_dir($verkkolaskut_ok) or !is_writable($verkkolaskut_ok)) exit;
if (!is_dir($verkkolaskut_orig) or !is_writable($verkkolaskut_orig)) exit;
if (!is_dir($verkkolaskut_error) or !is_writable($verkkolaskut_error)) exit;
if (!is_dir($verkkolaskut_reject) or !is_writable($verkkolaskut_reject)) exit;

$laskut     = $verkkolaskut_in;
$oklaskut   = $verkkolaskut_ok;
$origlaskut = $verkkolaskut_orig;
$errlaskut  = $verkkolaskut_error;

if ($php_cli || strpos($_SERVER['SCRIPT_NAME'], "pankkiyhteys.php") !== FALSE) {
  // Ei tehd‰ mit‰‰n. T‰ll‰set iffit on sit‰ parasta koodia.
}
elseif (strpos($_SERVER['SCRIPT_NAME'], "tiliote.php") !== FALSE and $verkkolaskut_in != "" and $verkkolaskut_ok != "" and $verkkolaskut_orig != "" and $verkkolaskut_error != "") {
  //Pupesoftista
  echo "Aloitetaan verkkolaskun sis‰‰nluku...<br>\n<br>\n";

  // Kopsataan uploadatta faili verkkolaskudirikkaan
  $copy_boob = copy($filenimi, $laskut."/".$userfile);

  if ($copy_boob === FALSE) {
    echo "Kopiointi ep‰onnistui $filenimi $laskut/$userfile<br>\n";
    exit;
  }
}
else {
  echo "N‰ill‰ ehdoilla emme voi ajaa verkkolaskujen sis‰‰nlukua!";
  exit;
}

require "inc/verkkolasku-in.inc"; // t‰‰ll‰ on itse koodi
require "inc/verkkolasku-in-erittele-laskut.inc"; // t‰‰ll‰ pilkotaan Finvoiceaineiston laskut omiksi tiedostoikseen

// K‰sitell‰‰n ensin kaikki Finvoicet
if ($handle = opendir($laskut)) {
  while (($file = readdir($handle)) !== FALSE) {
    if (!is_file($laskut."/".$file)) {
      continue;
    }

    $nimi = $laskut."/".$file;

    // Muutetaan oikeaan merkistˆˆn
    $encoding = exec("file -b --mime-encoding '$nimi'");

    if (!PUPE_UNICODE and $encoding != "" and strtoupper($encoding) != 'ISO-8859-15') {
      exec("recode -f $encoding..ISO-8859-15 '$nimi'");
    }
    elseif (PUPE_UNICODE and $encoding != "" and strtoupper($encoding) != 'UTF-8') {
      exec("recode -f $encoding..UTF8 '$nimi'");
    }

    $luotiinlaskuja = erittele_laskut($nimi);

    // Jos tiedostosta luotiin laskuja siirret‰‰n se tielt‰ pois
    if ($luotiinlaskuja > 0) {

      // Logitetaan ajo
      cron_log($origlaskut."/".$file);

      rename($laskut."/".$file, $origlaskut."/".$file);
    }
  }
}

if ($handle = opendir($laskut)) {
  while (($file = readdir($handle)) !== FALSE) {
    if (!is_file($laskut."/".$file)) {
      continue;
    }

    // $yhtiorow ja $xmlstr
    unset($yhtiorow);
    unset($xmlstr);

    $nimi = $laskut."/".$file;
    $laskuvirhe = verkkolasku_in($nimi, TRUE);

    if ($laskuvirhe == "") {
      if (!$php_cli) {
        echo "Verkkolasku vastaanotettu onnistuneesti!<br>\n<br>\n";
      }

      rename($laskut."/".$file, $oklaskut."/".$file);
    }
    else {
      if (!$php_cli) {
        echo "<font class='error'>Verkkolaskun vastaanotossa virhe:</font><br>\n<pre>$laskuvirhe</pre><br>\n";
      }
      $alku = $loppu = "";
      list($alku, $loppu) = explode("####", $laskuvirhe);

      if (trim($loppu) == "ASN") {
        // ei tehd‰ mit‰‰n vaan annetaan j‰‰d‰ roikkumaan kansioon seuraavaan kierrokseen saakka, tai kunnes joku lukee postit.
      }
      else {
        rename($laskut."/".$file, $errlaskut."/".$file);
      }
    }
  }
}

if ($php_cli) {
  // laitetaan k‰yttˆoikeudet kuntoon
  system("chown -R :apache $verkkolaskut_in; chmod -R 770 $verkkolaskut_in;");
}

// siivotaan yli 90 p‰iv‰‰ vanhat aineistot
system("find $verkkolaskut_in -type f -mtime +90 -delete");
