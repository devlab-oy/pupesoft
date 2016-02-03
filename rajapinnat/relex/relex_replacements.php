<?php

/*
 * Siirret‰‰n korvaavusketjut Relexiin
 * 4.4 PRODUCT REPLACEMENTS
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;
$weekly_ajo = FALSE;
$ajotext = "";
$ftppath = "/data/input";

if (isset($argv[2]) and $argv[2] != '') {

  if (strpos($argv[2], "-") !== FALSE) {
    list($y, $m, $d) = explode("-", $argv[2]);
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
      $ajopaiva = $argv[2];
    }
  }

  if (strtoupper($argv[2]) == 'WEEKLY') {
    $weekly_ajo = TRUE;
    $ajotext = "weekly_";
  }
  else {
    $paiva_ajo = TRUE;
  }
}

if (isset($argv[3]) and trim($argv[3]) != '') {
  $ftppath = trim($argv[3]);
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tuoterajaus = rakenna_relex_tuote_parametrit();

$tecd = FALSE;

if (@include "inc/tecdoc.inc") {
  $tecd = TRUE;
}


// Tallennetaan rivit tiedostoon
$filepath = "/tmp/product_replacement_update_{$yhtio}_{$ajotext}{$ajopaiva}.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "new_product_code;";
$header .= "old_product_code;";
$header .= "new_clean_product_code;";
$header .= "old_clean_product_code;";
$header .= "state\n";
fwrite($fp, $header);

$korvaavatrajaus = "";

// Haetaan aika jolloin t‰m‰ skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("RELEX_REPL_CRON");

// Otetaan mukaan vain edellisen ajon j‰lkeen muuttuneet
if ($paiva_ajo and $datetime_checkpoint != "") {
  $korvaavatrajaus = " AND korvaavat.luontiaika > '$datetime_checkpoint' ";
}
elseif ($paiva_ajo) {
  $korvaavatrajaus = " AND korvaavat.luontiaika >= date_sub(now(), interval 24 HOUR) ";
}

// Haetaan ketjut
$query = "SELECT DISTINCT yhtio.maa, korvaavat.id
          FROM tuote
          JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio AND tuote.tuoteno = korvaavat.tuoteno {$korvaavatrajaus})
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio = '$yhtio'
          {$tuoterajaus}";
$res = pupe_query($query);

// Tallennetaan aikaleima
cron_aikaleima("RELEX_REPL_CRON", date('Y-m-d H:i:s'));

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex Korvaavusketjuja {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Heataan ketjun tuotteet
  $kquery = "SELECT korvaavat.tuoteno
             FROM korvaavat
             JOIN tuote on (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
             WHERE korvaavat.yhtio = '{$yhtio}'
             AND korvaavat.id      = '{$row['id']}'
             ORDER BY if(korvaavat.jarjestys=0, 9999, korvaavat.jarjestys), korvaavat.tuoteno";
  $kresult = pupe_query($kquery);

  $korvaavat = array();

  while ($krow = mysql_fetch_assoc($kresult)) {
    $korvaavat[] = $krow["tuoteno"];
  }

  // K‰yd‰‰n array l‰pi ja listataan tuotteet niin, ett‰ rivill‰ on aina uusi_tuote ja vanha_tuote
  for ($k = 0; $k < count($korvaavat)-1; $k++) {
    // Korvaavuudet
    $rivi  = "{$row['maa']}-".pupesoft_csvstring($korvaavat[$k]).";";
    $rivi .= "{$row['maa']}-".pupesoft_csvstring($korvaavat[$k+1]).";";
    $rivi .= pupesoft_csvstring($korvaavat[$k]).";";
    $rivi .= pupesoft_csvstring($korvaavat[$k+1]).";";
    $rivi .= "REPLACEMENT";
    $rivi .= "\n";
    fwrite($fp, $rivi);
  }

  $k_rivi++;
}

fclose($fp);

// Tehd‰‰n FTP-siirto
if (($paiva_ajo or $weekly_ajo) and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relext korvaavat valmis.\n";
