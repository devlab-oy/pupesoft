<?php
// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) || !$argv[1]) { 
  echo "Anna yhtio"; 
  exit; 
}

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

$php_cli = true;
$impsaloh_csv_cron = true;
$impsaloh_csv_cron_dirname = realpath('datain/saldo_ostohinta_import');

// ytiorow. Jos ei l?ydy, lopeta cron¨
$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if(!$yhtiorow) {
  echo "Vaara yhtio"; exit; 
}

// kukarow. Jos ei annettu, oletuksena on admin
$kukarow = hae_kukarow(pupesoft_cleanstring($argv[2]), $yhtiorow['yhtio']);
if(!isset($argv[1]) or !$kukarow) {
  $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']); 
}

$impsaloh_csv_cron_tiedot = array(
  "yhtiorow" => $yhtiorow,
  "kukarow" => $kukarow,
  "yhtio" => $yhtiorow['yhtio']
);

$impsaloh_polku_in     = $impsaloh_csv_cron_dirname;
$impsaloh_polku_ok     = $impsaloh_csv_cron_dirname."/ok";
$impsaloh_polku_orig   = $impsaloh_csv_cron_dirname."/orig";
$impsaloh_polku_error  = $impsaloh_csv_cron_dirname."/error";

$ftphost = $ftphost_impsaloh;
$ftpuser = $ftpuser_impsaloh;
$ftppass = $ftppass_impsaloh;
$ftpport = $ftpport_impsaloh;
$ftppath = $ftppath_impsaloh;
$ftpdest = $ftpdest_impsaloh;



$ftp_exclude_files = array_diff(scandir($impsaloh_polku_orig), $huonot_tiedostonimet);

//require 'sftp-get.php';

$impsaloh_csv_files = scandir($impsaloh_polku_in);

foreach($impsaloh_csv_files as $impsaloh_csv_file) {

  $impsaloh_csv_file = $impsaloh_polku_in."/".$impsaloh_csv_file;
  // skipataan kansiot, orig kansiossa olevat tiedostot sekä pisteet
  if (is_dir($impsaloh_csv_file) or substr($impsaloh_csv_file, 0, 1) == '.' or in_array($impsaloh_csv_file, $ftp_exclude_files)) continue;

  $impsaloh_csv = fopen($impsaloh_csv_file, 'r');
  if (!$impsaloh_csv) die($php_errormsg);

  while ($rivi = fgets($impsaloh_csv)) {

    // luetaan rivi tiedostosta..
    $rivi = explode("\t", pupesoft_cleanstring($rivi));
    $count++;
  }

}

