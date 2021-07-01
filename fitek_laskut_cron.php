<?php
// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
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
$fitek_xml_cron = true;
$fitek_xml_cron_dirname = realpath('datain/fitek_import');

// ytiorow. Jos ei l?ydy, lopeta cron®
$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if(!$yhtiorow) {
  echo "Vaara yhtio"; exit; 
}

// kukarow. Jos ei annettu, oletuksena on admin
$kukarow = hae_kukarow(pupesoft_cleanstring($argv[2]), $yhtiorow['yhtio']);
if(!isset($argv[1]) or !$kukarow) {
  $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']); 
}

// toimi. Jos ei annettu, yriteraan etsia laskusta.
if(isset($argv[3])) { 
  $fitek_xml_cron_toimi = pupesoft_cleanstring($argv[3]);
}
else {
  $fitek_xml_cron_toimi = false;
}

$fitek_xml_cron_tiedot = array(
  "yhtiorow" => $yhtiorow,
  "kukarow" => $kukarow,
  "yhtio" => $yhtiorow['yhtio'],
  "toimi" => $fitek_xml_cron_toimi
);

// kansiot pit‰‰ luoda etuk‰teen ja groupina "apache" group
$verkkolaskut_in     = $fitek_xml_cron_dirname;
$verkkolaskut_ok     = $fitek_xml_cron_dirname."/ok";
$verkkolaskut_orig   = $fitek_xml_cron_dirname."/orig";
$verkkolaskut_error  = $fitek_xml_cron_dirname."/error";
$verkkolaskut_reject = $fitek_xml_cron_dirname."/reject";

$ftphost = $ftphost_fitek_cron;
$ftpuser = $ftpuser_fitek_cron;
$ftppass = $ftppass_fitek_cron;
$ftpport = $ftpport_fitek_cron;
$ftppath = $ftppath_fitek_cron;
$ftpdest = $ftpdest_fitek_cron;

// jo olevia olemassa tiedostoja kansiossa orig ei saa k‰sitell‰ uudestaan
$ftp_exclude_files = array_diff(scandir($verkkolaskut_orig), array('..', '.', '.DS_Store'));
require 'sftp-get.php';

include_once("verkkolasku-in.php");