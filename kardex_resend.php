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

  $kukarow['yhtio'] = (string) $argv[1];
  $kukarow['kuka']  = 'admin';
  $kukarow['kieli'] = 'fi';

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
}

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

if (!is_dir($kardex_sscc)) {
  echo "Kardex_sscc-kansio virheellinen!\n";
  exit;
}

// jos ulkoinen sscc on puuttunut, koitetaan lähettää kardex-tiedosto uudelleen
if ($handle = opendir($kardex_sscc)) {

  while (($file = readdir($handle)) !== FALSE) {

    if (is_file($kardex_sscc."/".$file)) {
      $kerayseran_numero = preg_replace("/[^0-9]/", "", $file);

      require "inc/kardex_send.inc";
    }
  }

  closedir($handle);
}

// koitetaan uudelleen lähettää kardex-tiedosto, jos FTP-siirto on feilannut aikaisemmin
if ($kardex_host != "" and $kardex_user != "" and $kardex_pass != "" and $kardex_path != "" and $kardex_fail != "") {
  $ftphost = $kardex_host;
  $ftpuser = $kardex_user;
  $ftppass = $kardex_pass;
  $ftppath = $kardex_path;
  $ftpport = $kardex_port;
  $ftpfail = $kardex_fail;
  $ftpsucc = $kardex_succ;
  $ftpfile = $kardexnimi;

  if ($handle = opendir($ftpfail)) {

    while (($file = readdir($handle)) !== FALSE) {

      if (is_file($ftpfail."/".$file)) {
        $ftpfile = realpath($ftpfail."/".$file);

        require "inc/ftp-send.inc";

        // Jos siirto meni ok, niin remmataan faili
        if ($palautus == 0) {
          @unlink($ftpfail."/".$file);
        }
      }
    }
  }

  closedir($handle);
}
