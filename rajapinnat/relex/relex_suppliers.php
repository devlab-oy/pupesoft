<?php

/*
 * Siirret‰‰n toimittajat Relexiin
 * 5.2 SUPPLIER DATA
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

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;

if (isset($argv[2]) and $argv[2] != '') {
  $paiva_ajo = TRUE;
  
  if ($argv[2] == "edpaiva") {
      $ajopaiva = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }
}

// Logitetaan ajo
cron_log();

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/supplier_update_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;country\n";
fwrite($fp, $header);

$toimittajarajaus = "";

// Otetaan mukaan vain viimeisen vuorokauden j‰lkeen muuttuneet
if ($paiva_ajo) {
  $toimittajarajaus = " AND (toimi.muutospvm >= date_sub(now(), interval 24 HOUR) or toimi.luontiaika >= date_sub(now(), interval 24 HOUR))";
}

// Haetaan toimittajat
$query = "SELECT
          yhtio.maa,
          toimi.tunnus,
          concat_ws(' ', toimi.nimi, toimi.nimitark) nimi,
          toimi.maa toimittajan_maa
          FROM toimi
          JOIN yhtio ON (toimi.yhtio = yhtio.yhtio)
          WHERE toimi.yhtio       = '$yhtio'
          AND toimi.oletus_vienti in ('C','F','I')
          AND toimi.toimittajanro not in ('0','')
          AND toimi.tyyppi        = ''
          {$toimittajarajaus}
          ORDER BY toimi.tunnus";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Toimittajarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  $rivi  = "{$row['maa']}-{$row['tunnus']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= pupesoft_csvstring($row['toimittajan_maa']);
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
