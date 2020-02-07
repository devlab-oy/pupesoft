<?php

if (php_sapi_name() != 'cli') {
  exit;
}

$pupe_root_polku = "/var/www/html/pupesoft";

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("memory_limit", "5G");

$query = "SELECT *
          FROM lasku
          WHERE yhtio    = 'artr'
          AND tila       = 'U'
          AND alatila    = 'X'
          AND laskunro   < 0
          AND laatija    in ('konversio', 'futursoft')
          AND kassalipas = 0
          AND luontiaika >= '2013-01-01'
          AND luontiaika < '2014-01-01'";
$uxlasku = mysql_query($query);

while ($myyntilasku = mysql_fetch_assoc($uxlasku)) {
  // echo "\n  $myyntilasku[laskunro]";
  $query2 = "SELECT *
             FROM lasku
             WHERE yhtio  = 'artr'
             AND laskunro = '{$myyntilasku['laskunro']}'
             AND tila     = 'L'
             AND alatila  = 'X'
             AND laatija  in ('konversio', 'futursoft')
             limit 1";
  $lxlasku = mysql_query($query2);

  if (mysql_num_rows($lxlasku) == 1) {

    $myyntiots = mysql_fetch_assoc($lxlasku);

    if ($myyntilasku['yhtio_toimipaikka'] != $myyntiots['yhtio_toimipaikka']) {
      echo "\n  $myyntiots[laskunro], $myyntilasku[tunnus], $myyntilasku[yhtio_toimipaikka], $myyntiots[yhtio_toimipaikka]";

      $query4 = "UPDATE lasku
                 SET yhtio_toimipaikka = '{$myyntiots['yhtio_toimipaikka']}'
                 WHERE yhtio = 'artr'
                 AND tunnus  = '{$myyntilasku['tunnus']}'";
      $privi = pupe_query($query4);
    }
  }
}
