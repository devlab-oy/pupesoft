<?php
// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) || !$argv[1]) { 
  echo "Anna yhtio"; 
  exit; 
}

if (!isset($argv[2]) || !$argv[2]) { 
  echo "Anna kayttaja!"; 
  exit; 
}

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";

$php_cli = true;
$fitek_xml_cron = true;
$fitek_xml_cron_dirname = realpath('datain/fitek_import');

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

// kansiot pitää luoda etukäteen ja groupina "apache" group
$verkkolaskut_in     = $fitek_xml_cron_dirname;
$verkkolaskut_ok     = $fitek_xml_cron_dirname."/ok";
$verkkolaskut_orig   = $fitek_xml_cron_dirname."/orig";
$verkkolaskut_error  = $fitek_xml_cron_dirname."/error";
$verkkolaskut_reject = $fitek_xml_cron_dirname."/reject";
$verkkolaskut_pdf    = $fitek_xml_cron_dirname."/pdf";

$ftphost = $ftphost_fitek_cron;
$ftpuser = $ftpuser_fitek_cron;
$ftppass = $ftppass_fitek_cron;
$ftpport = $ftpport_fitek_cron;

$ftppath = $ftppath_fitek_cron_pdf;
$ftpdest = $ftpdest_fitek_cron_pdf;
// jo olevia olemassa tiedostoja kansiossa orig ei saa käsitellä uudestaan
$ftp_exclude_files = array_diff(scandir($verkkolaskut_pdf), array('..', '.', '.DS_Store','.keep'));
include_once 'sftp-get.php';

$ftppath = $ftppath_fitek_cron;
$ftpdest = $ftpdest_fitek_cron;
// jo olevia olemassa tiedostoja kansiossa orig ei saa käsitellä uudestaan
$ftp_exclude_files = array_diff(scandir($verkkolaskut_orig), array('..', '.', '.DS_Store','.keep'));
$ftp_exclude_files[] = 'pdf';

$sftp->getFilesFrom($ftppath."/", $ftpdest."/", $ftp_exclude_files, $fitek_xml_cron);

include_once("verkkolasku-in.php");