<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
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
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
      $ajopaiva = $argv[2];
    }
  }
  $paiva_ajo = TRUE;
}

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/supplier_update_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

$header = "product;location;varastopaikka\n";
fwrite($fp, $header);

// Otetaan mukaan vain viimeisen vuorokauden jälkeen muuttuneet
if ($paiva_ajo) {
  $tuoterajaus = "AND (
                    tuote.muutospvm >= date_sub(now(), interval 24 HOUR) or
                    tuote.luontiaika >= date_sub(now(), interval 24 HOUR)
                  )";
}
else {
  $tuoterajaus = "";
}

$query = "SELECT tuote.tuoteno, yhtio.maa
          FROM tuote
          JOIN yhtio ON (toimi.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio = '{$kukarow['yhtio']}'
          AND tuote.status != 'P'";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex tuoterivejä {$rows} kappaletta.\n";

while ($row = mysql_fetch_assoc($res)) {

  $query = "SELECT DISTINCT varasto,
            oletus,
            hyllypaikka,
            concat_ws('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka
            FROM tuotepaikat
            WHERE yhtio='{$kukarow['yhtio']}'
            AND tuoteno='{$row['tuoteno']}'
            GROUP BY 1
            ORDER BY 1,2,3 DESC";
  $tres = pupe_query($query);

  while ($trow = mysql_fetch_assoc($tres)) {
    $rivi  = pupesoft_csvstring($row['tuoteno']).";";
    $rivi .= "{$row['maa']}-{$trow['varasto']};";
    $rivi .= pupesoft_csvstring($trow['hyllypaikka']);
    $rivi .= "\n";

    fwrite($fp, $rivi);
  }
}

fclose($fp);

// Tehdään FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relex tuotepaikat valmis.\n\n";
