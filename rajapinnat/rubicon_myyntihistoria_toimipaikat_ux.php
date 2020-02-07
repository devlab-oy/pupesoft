<?php

/*
  Korjataan käteismyyntejä: päivitetään ux_lle toimipaikka, jos se puuttuu
*/

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  //$pupe_root_polku = "/Users/satu/Sites/pupesoft";

  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL);
  ini_set("display_errors", 1);

  ini_set("memory_limit", "5G");

  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio     = trim($argv[1]);

  //yhtiötä ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
    die;
  }
}
else {
  die("Konversion voi ajaa vain komentoriviltä");
}


// Nämä korjaavat updatet pitää ajaa eka
pupe_query("update lasku set yhtio_toimipaikka = 27 where yhtio='{$yhtio}' and kassalipas=23 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 20 where yhtio='{$yhtio}' and kassalipas=25 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 24 where yhtio='{$yhtio}' and kassalipas=26 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 19 where yhtio='{$yhtio}' and kassalipas=27 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 31 where yhtio='{$yhtio}' and kassalipas=28 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 36 where yhtio='{$yhtio}' and kassalipas=29 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 17 where yhtio='{$yhtio}' and kassalipas=30 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 18 where yhtio='{$yhtio}' and kassalipas=31 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 38 where yhtio='{$yhtio}' and kassalipas=32 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 32 where yhtio='{$yhtio}' and kassalipas=33 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 16 where yhtio='{$yhtio}' and kassalipas=34 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 22 where yhtio='{$yhtio}' and kassalipas=35 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 26 where yhtio='{$yhtio}' and kassalipas=36 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 21 where yhtio='{$yhtio}' and kassalipas=37 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 25 where yhtio='{$yhtio}' and kassalipas=38 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 27 where yhtio='{$yhtio}' and kassalipas=39 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 33 where yhtio='{$yhtio}' and kassalipas=40 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 35 where yhtio='{$yhtio}' and kassalipas=41 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 41 where yhtio='{$yhtio}' and kassalipas=42 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 34 where yhtio='{$yhtio}' and kassalipas=43 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 28 where yhtio='{$yhtio}' and kassalipas=44 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 40 where yhtio='{$yhtio}' and kassalipas=45 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 15 where yhtio='{$yhtio}' and kassalipas=46 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 30 where yhtio='{$yhtio}' and kassalipas=47 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");
pupe_query("update lasku set yhtio_toimipaikka = 14 where yhtio='{$yhtio}' and kassalipas=48 and yhtio_toimipaikka=0 and tila='U' and laskunro like '-1000013%'");

pupe_query("update lasku set liitostunnus=286460 where yhtio='{$yhtio}' and tila='U' and laskunro like '-1000013%' and liitostunnus=0 and maksuehto='1572'");
pupe_query("update lasku set liitostunnus=286460 where yhtio='{$yhtio}' and tila='U' and laskunro like '-1000013%' and liitostunnus=0 and maksuehto='1551'");

pupe_query("update lasku set yhtio_toimipaikka = 27 where yhtio='{$yhtio}' and yhtio_toimipaikka=23  and tila in ('U', 'L')");
