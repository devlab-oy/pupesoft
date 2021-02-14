<?php
// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if(!isset($argv[1]) || !$argv[1]) { echo "Anna yhtio"; exit; }

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

$php_cli = true;
$fitek_xml_cron = true;
$fitek_xml_cron_dirname = realpath('datain/fitek_import');

// ytiorow. Jos ei löydy, lopeta cron
if($yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]))) { } else { echo "Vaara yhtio"; exit; }
// kukarow. Jos ei annettu, oletuksena on admin
if(isset($argv[1]) && $kukarow = hae_kukarow(pupesoft_cleanstring($argv[2]), $yhtiorow['yhtio'])) { } else { $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']); }
// toimi. Jos ei annettu, yriteraan etsia laskusta.
if(isset($argv[3])) { $fitek_xml_cron_toimi = pupesoft_cleanstring($argv[3]); } else { $fitek_xml_cron_toimi = false; }

echo "\nValittu kayttaja: ".$kukarow["kuka"].", valittu toimitus: ".$fitek_xml_cron_toimi."\n";

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