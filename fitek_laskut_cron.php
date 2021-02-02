<?php
// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

$php_cli = true;
$fitek_xml_cron = true;
$fitek_xml_cron_dirname = realpath('datain/fitek_import');

$_yhtio   = pupesoft_cleanstring($argv[1]);
$yhtiorow = hae_yhtion_parametrit($_yhtio);
$kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

//Testausta varten
$fitek_xml_cron_toimi = 105;
$fitek_xml_cron_tiedot = array(
  "yhtiorow" => $yhtiorow,
  "kukarow" => $kukarow,
  "yhtio" => $yhtiorow['yhtio'],
  "toimi" => $fitek_xml_cron_toimi
);

$verkkolaskut_in     = $fitek_xml_cron_dirname;
$verkkolaskut_ok     = $fitek_xml_cron_dirname."/ok";
$verkkolaskut_orig   = $fitek_xml_cron_dirname."/orig";
$verkkolaskut_error  = $fitek_xml_cron_dirname."/error";
$verkkolaskut_reject = $fitek_xml_cron_dirname."/reject";

include_once("verkkolasku-in.php");

//chdir("tilauskasittely");

//$laskutettavat = array("154");
//include_once("verkkolasku.php");
?>