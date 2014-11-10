<?php

/*
 * Siirret‰‰n asiakastiedot Relexiin
 * 5.3 CUSTOMER DATA
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

if (isset($argv[2]) and $argv[2] != '') {

  if (strpos($argv[2], "-") !== FALSE) {
    list($y, $m, $d) = explode("-", $argv[2]);
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($d, $m, $y)) {
      $ajopaiva = $argv[2];
    }
  }
  $paiva_ajo = TRUE;
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/customer_update_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;customer_group\n";
fwrite($fp, $header);

$asiakasrajaus = "";

// Otetaan mukaan vain viimeisen vuorokauden j‰lkeen muuttuneet
if ($paiva_ajo) {
  $asiakasrajaus = " AND (asiakas.muutospvm >= date_sub(now(), interval 24 HOUR) or asiakas.luontiaika >= date_sub(now(), interval 24 HOUR))";
}

// Haetaan asiakkaat
$query = "SELECT
          yhtio.maa,
          asiakas.tunnus,
          concat_ws(' ', asiakas.nimi, asiakas.nimitark) nimi,
          asiakas.ryhma
          FROM asiakas
          JOIN yhtio ON (asiakas.yhtio = yhtio.yhtio)
          WHERE asiakas.yhtio = '$yhtio'
          AND asiakas.laji    not in ('P','R')
          {$asiakasrajaus}
          ORDER BY asiakas.tunnus";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Asiakasrivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi  = "{$row['maa']}-{$row['tunnus']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= pupesoft_csvstring($row['ryhma']);
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

// Tehd‰‰n FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo "Valmis.\n";
