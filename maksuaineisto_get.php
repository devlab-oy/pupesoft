<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

if (!$php_cli) {
  echo "Vain komentoriviltä!";
  exit;
}

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL);
ini_set("display_errors", 0);

require("inc/salasanat.php");
require("inc/functions.inc");

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

if (!isset(  $pankkiaineiston_haku["host"],
      $pankkiaineiston_haku["user"],
      $pankkiaineiston_haku["pass"],
      $pankkiaineiston_haku["tiliote_file"],
      $pankkiaineiston_haku["viite_file"],
      $pankkiaineiston_haku["local_dir"])) {
  echo "pankkiaineiston-haku parametrit puuttuu!\n";
  exit;
}

if (!is_writable($pankkiaineiston_haku["local_dir"])) {
  echo "Hakemistossa ongelma!\n";
  exit;
}

// Avataan yhteys
$conn_id = @ftp_connect($pankkiaineiston_haku["host"]);

if ($conn_id === FALSE) {
  echo "Yhteys epaonnistui {$pankkiaineiston_haku["host"]}!";
  exit;
}

$login_result = @ftp_login($conn_id, $pankkiaineiston_haku["user"], $pankkiaineiston_haku["pass"]);

if ($login_result === FALSE) {
  echo "Login epaonnistui {$pankkiaineiston_haku["host"]}!";
  exit;
}

$quote = ftp_site($conn_id, "NAMEFMT 0");

ftp_pasv($conn_id, true);

$local_file = $pankkiaineiston_haku["local_dir"]."tiliote-".date("YmdHis").".dat";
$fileget = @ftp_get($conn_id, $local_file, $pankkiaineiston_haku["tiliote_file"], FTP_ASCII);

if ($fileget !== FALSE) {
  $quote = ftp_raw($conn_id, "rcmd clrpfm file({$pankkiaineiston_haku["tiliote_file"]})");
}

$local_file = $pankkiaineiston_haku["local_dir"]."viite-".date("YmdHis").".dat";
$fileget = @ftp_get($conn_id, $local_file, $pankkiaineiston_haku["viite_file"], FTP_ASCII);

if ($fileget !== FALSE) {
  $quote = ftp_raw($conn_id, "rcmd clrpfm file({$pankkiaineiston_haku["viite_file"]})");
}

ftp_close($conn_id);
