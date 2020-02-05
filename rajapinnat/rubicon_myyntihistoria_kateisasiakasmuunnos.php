<?php

/*
  Korjataan käteismyyntejä, päivitetään kätesimyynneille toimipaikkakohtainen käteismyyntiasiakasnumero
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

pupe_query("UPDATE lasku SET liitostunnus = '289035' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '14'");
pupe_query("UPDATE lasku SET liitostunnus = '289036' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '15'");
pupe_query("UPDATE lasku SET liitostunnus = '289037' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '16'");
pupe_query("UPDATE lasku SET liitostunnus = '289038' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '17'");
pupe_query("UPDATE lasku SET liitostunnus = '289039' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '18'");
pupe_query("UPDATE lasku SET liitostunnus = '289040' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '19'");
pupe_query("UPDATE lasku SET liitostunnus = '289041' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '20'");
pupe_query("UPDATE lasku SET liitostunnus = '289042' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '24'");
pupe_query("UPDATE lasku SET liitostunnus = '289043' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '25'");
pupe_query("UPDATE lasku SET liitostunnus = '289044' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '26'");
pupe_query("UPDATE lasku SET liitostunnus = '289046' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '27'");
pupe_query("UPDATE lasku SET liitostunnus = '289045' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '28'");
pupe_query("UPDATE lasku SET liitostunnus = '289047' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '30'");
pupe_query("UPDATE lasku SET liitostunnus = '289048' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '31'");
pupe_query("UPDATE lasku SET liitostunnus = '289049' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '32'");
pupe_query("UPDATE lasku SET liitostunnus = '289050' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '34'");
pupe_query("UPDATE lasku SET liitostunnus = '289051' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '35'");
pupe_query("UPDATE lasku SET liitostunnus = '289052' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '36'");
pupe_query("UPDATE lasku SET liitostunnus = '289052' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '37'");
pupe_query("UPDATE lasku SET liitostunnus = '289053' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '38'");
pupe_query("UPDATE lasku SET liitostunnus = '289054' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '40'");
pupe_query("UPDATE lasku SET liitostunnus = '289055' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '286460' AND yhtio_toimipaikka = '41'");

pupe_query("UPDATE lasku SET liitostunnus = '289048' WHERE yhtio = '{$yhtio}' AND tila in ('U','L') AND alatila = 'X' AND liitostunnus = '104795'  AND yhtio_toimipaikka = '31'");
