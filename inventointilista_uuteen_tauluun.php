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

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("memory_limit", "2G");

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// tarkistetaan onko tietyt kentät jo dropattu
// jos on niin ei ajeta tätä skriptiä
$query = "DESC tuotepaikat";
$res = pupe_query($query);

$loyty = false;

while ($row = mysql_fetch_assoc($res)) {
  if (in_array($row['Field'],
      array('inventointilista', 'inventointilista_aika', 'inventointilista_naytamaara'))) {
    $loyty = true;
    break;
  }
}

if (!$loyty) {
  echo "Konversioajo on jo tehty!";
  exit;
}

$query = "SELECT yhtio
          FROM yhtio";
$yhtiores = pupe_query($query);

while ($yhtiorow = mysql_fetch_assoc($yhtiores)) {

  $yhtio = $yhtiorow['yhtio'];

  $query = "SELECT group_concat(distinct inventointilista) inventointilistat
            FROM tuotepaikat
            WHERE yhtio                = '{$yhtio}'
            AND inventointilista      != 0
            AND inventointilista_aika != '0000-00-00 00:00:00'";
  $res = pupe_query($query);
  $listat_row = mysql_fetch_assoc($res);

  if ($listat_row['inventointilistat'] == "") continue;

  $query = "SELECT *
            FROM tuotepaikat
            WHERE yhtio          = '{$yhtio}'
            AND inventointilista in ({$listat_row['inventointilistat']})";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {

    $query = "SELECT *
              FROM inventointilista
              WHERE yhtio = '{$yhtio}'
              AND tunnus  = '{$row['inventointilista']}'";
    $_res = pupe_query($query);

    if (mysql_num_rows($_res) == 0) {
      $query = "INSERT INTO inventointilista SET
                yhtio      = '{$yhtio}',
                naytamaara = '{$row['inventointilista_naytamaara']}',
                muuttaja   = '{$row['muuttaja']}',
                laatija    = '{$row['laatija']}',
                luontiaika = '{$row['luontiaika']}',
                muutospvm  = '{$row['muutospvm']}',
                tunnus     = '{$row['inventointilista']}'";
      pupe_query($query);
    }

    $_id = $row['inventointilista'];

    $query = "SELECT *
              FROM inventointilistarivi
              WHERE yhtio           = '{$yhtio}'
              AND tuoteno           = '{$row['tuoteno']}'
              AND otunnus           = '{$_id}'
              AND hyllyalue         = '{$row['hyllyalue']}'
              AND hyllynro          = '{$row['hyllynro']}'
              AND hyllyvali         = '{$row['hyllyvali']}'
              AND hyllytaso         = '{$row['hyllytaso']}'
              AND tuotepaikkatunnus = '{$row['tunnus']}'";
    $_row_res = pupe_query($query);

    if (mysql_num_rows($_row_res) == 0) {

      if ($row['inventointilista_aika'] == '0000-00-00 00:00:00') {
        $_tila = "I";
        $row['luontiaika'] = $row['inventointiaika'];
        $row['muutospvm'] = $row['inventointiaika'];
        $row['inventointilista_aika'] = "'".$row['inventointiaika']."'";
      }
      else {
        $_tila = "A";
        $row['luontiaika'] = $row['inventointilista_aika'];
        $row['muutospvm'] = $row['inventointilista_aika'];
        $row['inventointilista_aika'] = "null";
      }

      $query = "INSERT INTO inventointilistarivi SET
                yhtio             = '{$yhtio}',
                otunnus           = '{$_id}',
                tila              = '{$_tila}',
                aika              = {$row['inventointilista_aika']},
                tuoteno           = '{$row['tuoteno']}',
                hyllyalue         = '{$row['hyllyalue']}',
                hyllynro          = '{$row['hyllynro']}',
                hyllyvali         = '{$row['hyllyvali']}',
                hyllytaso         = '{$row['hyllytaso']}',
                tuotepaikkatunnus = '{$row['tunnus']}',
                muuttaja          = '{$row['muuttaja']}',
                laatija           = '{$row['laatija']}',
                luontiaika        = '{$row['luontiaika']}',
                muutospvm         = '{$row['muutospvm']}'";
      pupe_query($query);
    }
  }
}

$query = "ALTER TABLE tuotepaikat
          DROP inventointilista,
          DROP inventointilista_aika,
          DROP inventointilista_naytamaara,
          DROP INDEX yhtio_inventointilista_aika,
          DROP INDEX yhtio_inventointilista";
pupe_query($query);

echo "Done!";
