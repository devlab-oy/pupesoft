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

foreach ($bookkaukset as $bookkaus) {
  $temp_file = tempnam("/tmp", "IFF-");
  ftp_get($yhteys, $temp_file, $bookkaus, FTP_ASCII);
  $edi_data = file_get_contents($temp_file);
  kasittele_bookkaussanoma($edi_data);
  unlink($temp_file);
}

foreach ($rahtikirjat as $rahtikirja) {
  $temp_file = tempnam("/tmp", "DAD-");
  ftp_get($yhteys, $temp_file, $rahtikirja, FTP_ASCII);
  $edi_data = file_get_contents($temp_file);
  kasittele_rahtikirjasanoma($edi_data);
  unlink($temp_file);
}

foreach ($iftstat as $iftsta) {
  $temp_file = tempnam("/tmp", "IFT-");
  ftp_get($yhteys, $temp_file, $iftsta, FTP_ASCII);
  $edi_data = file_get_contents($temp_file);
  kasittele_iftsta($edi_data);
  unlink($temp_file);
}

ftp_close($yhteys);
