<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");

require "inc/connect.inc";
require "inc/functions.inc";
require "inc/edifact_functions.inc";

if (!isset($argv[1])) {
  echo "Anna yhtio!\n";
  die;
}

// Haetaan yhtiörow ja kukarow
$yhtio = pupesoft_cleanstring($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

$session = "";
for ($i = 0; $i < 25; $i++) {
  $session .= chr(rand(65, 90));
}

$host = $ftp_info['host'];
$user = $ftp_info['user'];
$pass = $ftp_info['pass'];

$yhteys = ftp_connect($host);

$login = ftp_login($yhteys, $user, $pass);

if ((!$yhteys) || (!$login)) {
  echo t("Ftp-yhteyden muodostus epaonnistui! Tarkista salasanat."); die;
}
else {
  echo t("Ftp-yhteys muodostettu.")."<br/>";
}

ftp_chdir($yhteys, 'out-prod');

ftp_pasv($yhteys, true);

$files = ftp_nlist($yhteys, ".");

$bookkaukset = array();
$rahtikirjat = array();
$iftstat = array();

foreach ($files as $file) {

  if (substr($file, -3) == 'IFF') {
    $bookkaukset[] = $file;
  }

  if (substr($file, -3) == 'DAD') {
    $rahtikirjat[] = $file;
  }

  if (substr($file, -3) == 'IFS') {
    $iftstat[] = $file;
  }
}

foreach ($iftstat as $iftsta) {

  $lokaali_file =  dirname(__FILE__) . "/datain/lue-data-{$iftsta}";

  if (ftp_get($yhteys, $lokaali_file, $iftsta, FTP_ASCII)) {
    $edi_data = file_get_contents($lokaali_file);
    kasittele_iftsta($edi_data);
    ftp_delete($yhteys, $iftsta);
  }
}

foreach ($rahtikirjat as $rahtikirja) {

  $lokaali_file =  dirname(__FILE__) . "/datain/lue-data-{$rahtikirja}";

  if (ftp_get($yhteys, $lokaali_file, $rahtikirja, FTP_ASCII)) {
    $edi_data = file_get_contents($lokaali_file);
    kasittele_rahtikirjasanoma($edi_data);
    ftp_delete($yhteys, $rahtikirja);
  }
}

foreach ($bookkaukset as $bookkaus) {

  $lokaali_file =  dirname(__FILE__) . "/datain/lue-data-{$bookkaus}";

  if (ftp_get($yhteys, $lokaali_file, $bookkaus, FTP_ASCII)) {
    $edi_data = file_get_contents($lokaali_file);
    kasittele_bookkaussanoma($edi_data);
    ftp_delete($yhteys, $bookkaus);
  }
}

ftp_close($yhteys);
