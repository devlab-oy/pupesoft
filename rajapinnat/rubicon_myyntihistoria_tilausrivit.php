<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}

if (!isset($argv[1])) {
  echo "Anna Puperoot\n";
  exit(1);
}

$pupesoft_root = $argv[1];

if (!is_dir($pupesoft_root) or !is_file("{$pupesoft_root}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root);

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

// PKS:n avoimien tilauksien toimitustavat kuntoon
$query = "UPDATE lasku SET
          toimitustapa           = 'Ma-Si PKS'
          WHERE yhtio            = 'atarv'
          AND tila               in ('L','N')
          AND alatila           != 'X'
          AND toimitustapa       IN ('Oma kuljetus 1','Oma kuljetus 10','Oma kuljetus 11','Oma kuljetus 12','Oma kuljetus 2','Oma kuljetus 3','Oma kuljetus 4','Oma kuljetus 5','Oma kuljetus 6','Oma kuljetus 7','Oma kuljetus 8','Oma kuljetus 9')
          AND yhtio_toimipaikka  = 27";
$result = mysql_query($query) or pupe_error($query);

// päivitetään lasku ja laskun_lisatiedot taulut atarv -> artr
// myyntitilaus laskutettu
$query = "UPDATE lasku
          JOIN laskun_lisatiedot ON (lasku.yhtio = laskun_lisatiedot.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
          SET lasku.yhtio = 'artr', laskun_lisatiedot.yhtio = 'artr'
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'L' AND lasku.alatila = 'X'";
$result = mysql_query($query) or pupe_error($query);

// myyntilasku (avoimet maksetuksi tälle päivälle)
// HUOM, avoimet menee nyt maksetuksi, ja erillinen lisaa_avoimetmyyntilaskut.php hoitaa myöhemmin avoimet laskut takaisin ei-avoimeksi.
$query = "UPDATE lasku
          JOIN laskun_lisatiedot ON (lasku.yhtio = laskun_lisatiedot.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
          SET lasku.yhtio = 'artr', laskun_lisatiedot.yhtio = 'artr', lasku.mapvm = if(lasku.mapvm = '0000-00-00', '2014-05-23', lasku.mapvm)
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'U' AND lasku.alatila = 'X'";
$result = mysql_query($query) or pupe_error($query);

$query = "UPDATE lasku
          SET lasku.yhtio = 'artr', lasku.mapvm = if(lasku.mapvm = '0000-00-00', '2014-05-23', lasku.mapvm)
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'U' AND lasku.alatila = 'X'";
$result = mysql_query($query) or pupe_error($query);

// myyntitilaus kesken + tehdaspalautukset muille kuin örumille
$query = "UPDATE lasku
          JOIN laskun_lisatiedot ON (lasku.yhtio = laskun_lisatiedot.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
          SET lasku.yhtio = 'artr', laskun_lisatiedot.yhtio = 'artr'
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'N' AND (lasku.alatila = '' OR lasku.alatila = 'U') AND (lasku.tilaustyyppi != '9' OR (lasku.tilaustyyppi = '9' AND lasku.liitostunnus not in ('281057', '287770')))";
$result = mysql_query($query) or pupe_error($query);

// rekkulat ja tarjoukset kesken + jonossa olevat
$query = "UPDATE lasku
          JOIN laskun_lisatiedot ON (lasku.yhtio = laskun_lisatiedot.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
          SET lasku.yhtio = 'artr', laskun_lisatiedot.yhtio = 'artr'
          WHERE lasku.yhtio = 'atarv' AND lasku.tila in ('C', 'T') AND lasku.alatila in ('', 'A')";
$result = mysql_query($query) or pupe_error($query);

// Laskuihin, laskutettuihin tilauksiin ja muihin avoimiin tilauksiin liitetyt tilausrivit.
// Lasku-taulun jutut on jo artr, joten tsekataan sieltä ne. Rivit vielä atarv.
// HUOM RIVIT TEHDÄÄN rubicon_konversio.sql lopussa! ei täs loopissa.
$query = "(SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON (lasku.yhtio = 'artr' AND tilausrivi.uusiotunnus = lasku.tunnus AND lasku.tila = 'U' AND lasku.alatila = 'X')
           WHERE tilausrivi.yhtio     = 'atarv'
           AND tilausrivi.tyyppi      = 'L'
           AND tilausrivi.var         not in ('P'))
           UNION
           (SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON (lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila = 'L' AND lasku.alatila = 'X')
           WHERE tilausrivi.yhtio     = 'atarv'
           AND tilausrivi.tyyppi      = 'L'
           AND tilausrivi.uusiotunnus = 0
           AND tilausrivi.var         not in ('P'))
           UNION
           (SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON
           (lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila = 'N' AND (lasku.alatila = '' OR lasku.alatila = 'U') AND (lasku.tilaustyyppi != '9' OR (lasku.tilaustyyppi = '9' AND lasku.liitostunnus not in ('281057', '287770'))))
           WHERE tilausrivi.yhtio     = 'atarv'
           AND tilausrivi.tyyppi      = 'L'
           AND tilausrivi.var         not in ('P'))
           UNION
           (SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON
           (lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila in ('C', 'T') AND lasku.alatila in ('', 'A'))
           WHERE tilausrivi.yhtio     = 'atarv'
           AND tilausrivi.tyyppi      = 'L'
           AND tilausrivi.var         not in ('P'))";
$query_result = pupe_query($query);
/*
$query = "  SELECT tuoteno FROM tuote WHERE yhtio = 'artr'";
$query_result2 = pupe_query($query);
$kaikkituotteet = array();
while ($query_row = mysql_fetch_assoc($query_result2)) {
  $tuoteno =  strtoupper($query_row['tuoteno']);
  $kaikkituotteet[$tuoteno] = '';
}
*/
$total_items = mysql_num_rows($query_result);
$current_item = 0;

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  // Tsekataa onko tuote jo artr:ssä
  $query = "SELECT tuoteno
            FROM tuote
            WHERE yhtio = 'artr'
            AND tuoteno = '$query_row[tuoteno]'";
  $query_result2 = pupe_query($query);

  if (mysql_num_rows($query_result2) == 0) {
    $qu = "UPDATE tuote SET
           yhtio       = 'artr',
           status      = 'P',
           try         = '999997',
           osasto      = '999997',
           lyhytkuvaus = 'Fuusiossa siirretty tuote myyntihistorian vuoksi'
           WHERE yhtio = 'atarv'
           AND tuoteno = '$query_row[tuoteno]'";
    pupe_query($qu);
  }

  // $query = "UPDATE tilausrivi
  //       SET tilausrivi.yhtio = 'artr'
  //       WHERE tilausrivi.yhtio = 'atarv' AND tilausrivi.tunnus = '{$query_row['tunnus']}'";
  // pupe_query($query);
  //
  // $query = "UPDATE tilausrivin_lisatiedot
  //       SET tilausrivin_lisatiedot.yhtio = 'artr'
  //       WHERE tilausrivin_lisatiedot.yhtio = 'atarv' AND tilausrivin_lisatiedot.tilausrivitunnus = '{$query_row['tunnus']}'";
  // pupe_query($query);
}

echo "\n\n\n\n\n\n";
