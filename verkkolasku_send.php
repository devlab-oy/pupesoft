<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

if ($php_cli) {

  if (trim($argv[1]) == '') {
    echo "Et antanut yhtiötä!\n";
    exit;
  }

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio = pupesoft_cleanstring($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan käyttäjän tiedot
  $kukarow  = hae_kukarow('admin', $yhtio);

  $PHP_SELF = "verkkolasku_send.php";
}

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

// Katsotaan, että tarvittavat muuttujat on setattu
if (!isset(  $verkkolaskut_siirto["host"],
    $verkkolaskut_siirto["user"],
    $verkkolaskut_siirto["pass"],
    $verkkolaskut_siirto["path"],
    $verkkolaskut_siirto["type"],
    $verkkolaskut_siirto["local_dir"],
    $verkkolaskut_siirto["local_dir_ok"],
    $verkkolaskut_siirto["local_dir_error"])) {
  echo "verkkolasku-send parametrit puuttuu!\n";
  exit;
}

// Setataan oikeat muuttujat
$ftphost = $verkkolaskut_siirto["host"];
$ftpuser = $verkkolaskut_siirto["user"];
$ftppass = $verkkolaskut_siirto["pass"];
$ftppath = $verkkolaskut_siirto["path"];
$ftptype = $verkkolaskut_siirto["type"];
$localdir = $verkkolaskut_siirto["local_dir"];
$localdir_error = $verkkolaskut_siirto["local_dir_error"];
$ftpsucc = $verkkolaskut_siirto["local_dir_ok"];
$ftpfail = $localdir_error;



// Loopataan läpi pankkipolku
if ($handle = opendir($localdir)) {
  while (($file = readdir($handle)) !== FALSE) {
    $ftpfile = realpath($localdir."/".$file);
    if (is_file($ftpfile)) {
      require "inc/ftp-send.inc";
    }
  }
  closedir($handle);
}

// Loopataan läpi epäonnistuneet dirikka
if ($handle = opendir($localdir_error)) {
  // Ei siirretä feilattuja enää uudestaan jos feilaa taas
  unset($ftpfail);
  while (($file = readdir($handle)) !== FALSE) {
    $ftpfile = realpath($localdir_error."/".$file);
    if (is_file($ftpfile)) {
      require "inc/ftp-send.inc";
    }
  }
  closedir($handle);
}
