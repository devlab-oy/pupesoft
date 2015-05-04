<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

$yhtio = mysql_escape_string(trim($argv[1]));

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("memory_limit", "2G");

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Haetaan kukarow
$query = "SELECT *
          FROM kuka
          WHERE yhtio = '{$yhtio}'
          AND kuka    = 'admin'";
$kukares = pupe_query($query);

if (mysql_num_rows($kukares) != 1) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

$kukarow = mysql_fetch_assoc($kukares);

$query = "SELECT *
          FROM tuotepaikat
          WHERE yhtio = '{$yhtio}'
          AND inventointilista != 0";
$res = pupe_query($query);

while ($row = mysql_fetch_assoc($res)) {

  $query = "SELECT *
            FROM inventointilista
            WHERE yhtio = '{$yhtio}'
            AND tunnus = '{$row['inventointilista']}'";
  $_res = pupe_query($query);

  if (mysql_num_rows($_res) == 0) {
    $query = "INSERT INTO inventointilista SET
              yhtio = '{$yhtio}',
              aika = '{$row['inventointilista_aika']}',
              naytamaara = '{$row['inventointilista_naytamaara']}',
              muuttaja = '{$row['muuttaja']}',
              laatija = '{$row['laatija']}',
              luontiaika = '{$row['luontiaika']}',
              muutospvm = '{$row['muutospvm']}'";
    $_id = mysql_insert_id();
  }
  else {
    $_id = $row['inventointilista'];
  }

  $query = "SELECT *
            FROM inventointilistarivi
            WHERE yhtio = '{$yhtio}'
            AND tuoteno = '{$row['tuoteno']}'
            AND otunnus = '{$_id}'
            AND hyllyalue = '{$row['hyllyalue']}'
            AND hyllynro = '{$row['hyllynro']}'
            AND hyllyvali = '{$row['hyllyvali']}'
            AND hyllytaso = '{$hyllytaso}'";
  $_row_res = pupe_query($query);

  if (mysql_num_rows($_row_res) == 0) {
    $query = "INSERT INTO inventointilistarivi SET
              yhtio = '{$yhtio}',
              otunnus = '{$_id}',
              tuoteno = '{$row['tuoteno']}',
              hyllyalue = '{$row['hyllyalue']}',
              hyllynro = '{$row['hyllynro']}',
              hyllyvali = '{$row['hyllyvali']}',
              hyllytaso = '{$row['hyllytaso']}',
              muuttaja = '{$row['muuttaja']}',
              laatija = '{$row['laatija']}',
              luontiaika = '{$row['luontiaika']}',
              muutospvm = '{$row['muutospvm']}'";
  }
}

// $query = "ALTER TABLE tuotepaikat DROP COLUMN inventointilista, DROP COLUMN inventointilista_aika, DROP COLUMN inventointilista_naytamaara";
// $_drop_res = pupe_query($query);
