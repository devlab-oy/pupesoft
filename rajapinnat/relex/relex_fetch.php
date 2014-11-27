<?php

// Kutsutaanko CLI:st�
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// haetaan yhti�n tiedot vain jos t�t� tiedostoa kutsutaan komentorivilt� suoraan
if ($php_cli) {

  if (trim($argv[1]) == '') {
    echo "Et antanut yhti�t�!\n";
    exit;
  }

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  $kukarow['yhtio'] = (string) $argv[1];
  $kukarow['kuka']  = 'admin';
  $kukarow['kieli'] = 'fi';
  $operaattori      = "relex";

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  // Pupeasennuksen root
  $pupe_root_polku = dirname(dirname(dirname(__FILE__)));
}

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

if (trim($ftpget_dest[$operaattori]) == '') {
  echo "Relex return-kansio puuttuu!\n";
  exit;
}

if (!is_dir($ftpget_dest[$operaattori])) {
  echo "Relex return-kansio virheellinen!\n";
  exit;
}

// Setataan t�m�, niin ftp-get.php toimii niin kuin pit�isikin
$argv[1] = $operaattori;

require 'ftp-get.php';

if ($handle = opendir($ftpget_dest[$operaattori])) {
  while (($file = readdir($handle)) !== FALSE) {
    if (is_file($ftpget_dest[$operaattori]."/".$file)) {

      // T�m� on ostotilaus
      if (preg_match("/_{$yhtiorow["yhtio"]}_normal_/i", $file)) {
        exec("php {$pupe_root_polku}/rajapinnat/relex/relex_order_import.php {$yhtiorow['yhtio']} ".$ftpget_dest[$operaattori]."/".$file);
      }

      // T�m� on varastosiirto
      if (preg_match("/_{$yhtiorow["yhtio"]}_transfer_/i", $file)) {
        exec("php {$pupe_root_polku}/rajapinnat/relex/relex_transfer_import.php {$yhtiorow['yhtio']} ".$ftpget_dest[$operaattori]."/".$file);
      }

      rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/done/".$file);

      // Logitetaan ajo
      cron_log($ftpget_dest[$operaattori]."/done/".$file);
    }
  }
}
