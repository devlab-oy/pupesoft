<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

if ($php_cli) {

  if (trim($argv[1]) == '') {
    echo "Et antanut yhtiˆt‰!\n";
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

  // Haetaan k‰ytt‰j‰n tiedot
  $kukarow  = hae_kukarow('admin', $yhtio);

  $PHP_SELF = "maksuehto_send.php";
}

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

// Katsotaan, ett‰ tarvittavat muuttujat on setattu
$y = $kukarow["yhtio"];

if (!isset(  $maksuaineiston_siirto[$y]["host"],
    $maksuaineiston_siirto[$y]["user"],
    $maksuaineiston_siirto[$y]["pass"],
    $maksuaineiston_siirto[$y]["path"],
    $maksuaineiston_siirto[$y]["type"],
    $maksuaineiston_siirto[$y]["file"],
    $maksuaineiston_siirto[$y]["local_dir"],
    $maksuaineiston_siirto[$y]["local_dir_ok"],
    $maksuaineiston_siirto[$y]["local_dir_error"])) {
  echo "maksuaineisto-send parametrit puuttuu!\n";
  exit;
}

// Setataan oikeat muuttujat
$ftphost = $maksuaineiston_siirto[$y]["host"];
$ftpuser = $maksuaineiston_siirto[$y]["user"];
$ftppass = $maksuaineiston_siirto[$y]["pass"];
$ftppath = $maksuaineiston_siirto[$y]["path"];
$ftptype = $maksuaineiston_siirto[$y]["type"];
$renameftpfile = $maksuaineiston_siirto[$y]["file"];
$localdir = $maksuaineiston_siirto[$y]["local_dir"];
$localdir_error = $maksuaineiston_siirto[$y]["local_dir_error"];
$ftpsucc = $maksuaineiston_siirto[$y]["local_dir_ok"];
$ftpfail = $localdir_error;

// L‰hetet‰‰n UTF-8 muodossa jos PUPE_UNICODE on true
$ftputf8 = PUPE_UNICODE;

if (isset($maksuaineiston_siirto[$y]["port"]) and (int)$maksuaineiston_siirto[$y]["port"] > 0) {
  $ftpport = (int) $maksuaineiston_siirto[$y]["port"];
}

// Loopataan l‰pi pankkipolku
if ($handle = opendir($localdir)) {
  while (($file = readdir($handle)) !== FALSE) {
    $ftpfile = realpath($localdir."/".$file);
    if (is_file($ftpfile)) {
      require "inc/ftp-send.inc";
    }
  }
  closedir($handle);
}

// Loopataan l‰pi ep‰onnistuneet dirikka
if ($handle = opendir($localdir_error)) {
  // Ei siirret‰ feilattuja en‰‰ uudestaan jos feilaa taas
  unset($ftpfail);
  while (($file = readdir($handle)) !== FALSE) {
    $ftpfile = realpath($localdir_error."/".$file);
    if (is_file($ftpfile)) {
      require "inc/ftp-send.inc";
    }
  }
  closedir($handle);
}
