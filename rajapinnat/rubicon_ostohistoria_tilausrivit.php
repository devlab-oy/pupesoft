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

// päivitetään lasku taulut atarv -> artr
// kaikki keskeneräiset ostotilaukset paitsi Örumin + kaikki ostotilaukset jotka ei ole kesken sekä loppuun viedyt saapumiset
// ostotilauksille lisäksi konvertoidaan tilaustyyppi 1 = 2
$query = "UPDATE lasku
          SET lasku.yhtio = 'artr', lasku.tilaustyyppi = if(lasku.tilaustyyppi = '1', '2', lasku.tilaustyyppi)
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'O' AND lasku.alatila = '' AND liitostunnus != 27371";
$result = mysql_query($query) or pupe_error($query);

$query = "UPDATE lasku
          SET lasku.yhtio = 'artr', lasku.tilaustyyppi = if(lasku.tilaustyyppi = '1', '2', lasku.tilaustyyppi)
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'O' AND lasku.alatila != ''";
$result = mysql_query($query) or pupe_error($query);

$query = "UPDATE lasku
          SET lasku.yhtio = 'artr', lasku.tapvm = if(lasku.tapvm = '0000-00-00', now(), lasku.tapvm), lasku.mapvm = if(lasku.mapvm = '0000-00-00', now(), lasku.mapvm), lasku.alatila = 'X'
          WHERE lasku.yhtio = 'atarv' AND lasku.tila = 'K' AND lasku.alatila in ('X', '') AND vanhatunnus = 0";
$result = mysql_query($query) or pupe_error($query);

// todo, laitetaanko valmiiden saapumisten lisäksi vaihees olevia saapumisia? --> JOO! ja rivit + saapumiset menee KX tilaan vaik onkin Masil auki. Historiaksi vain.
// todo, siirretäänkö liitosotsikot saapumisten lisäksi --> ei.
// todo, siirretäänkö ostolaskut (avoimet / ei avoimet) myös? --> ei.

// Laskuihin, laskutettuihin tilauksiin ja muihin avoimiin tilauksiin liitetyt tilausrivit.
// Lasku-taulun jutut on jo artr, joten tsekataan sieltä ne. Rivit vielä atarv.
$query = "(SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.otunnus AND lasku.tila = 'O' AND lasku.alatila = '' AND liitostunnus != 27371)
           WHERE tilausrivi.yhtio         = 'atarv'
           AND tilausrivi.tyyppi          = 'O'
           AND tilausrivi.uusiotunnus     = 0)
           UNION
           (SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.otunnus AND lasku.tila = 'O' AND lasku.alatila != '' AND liitostunnus != 27371)
           WHERE tilausrivi.yhtio         = 'atarv'
           AND tilausrivi.tyyppi          = 'O'
           AND tilausrivi.uusiotunnus     = 0)
           UNION
           (SELECT tilausrivi.tunnus, tilausrivi.tuoteno
           FROM tilausrivi
           JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.uusiotunnus AND lasku.tila = 'K' AND lasku.alatila in ('X', '') AND vanhatunnus = 0)
           WHERE tilausrivi.yhtio         = 'atarv'
           AND tilausrivi.tyyppi          = 'O'
           AND tilausrivi.uusiotunnus    != 0
           AND tilausrivi.laskutettuaika != '0000-00-00')";
$query_result = pupe_query($query);

/*
$query = "  SELECT tuoteno FROM tuote WHERE yhtio = 'artr'";
$query_result2 = pupe_query($query);
$kaikkituotteet = array();
while ($query_row = mysql_fetch_assoc($query_result2)) {
  $kaikkituotteet[] = strtoupper($query_row['tuoteno']);
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
           lyhytkuvaus = 'Fuusiossa siirretty tuote ostohistorian vuoksi'
           WHERE yhtio = 'atarv'
           AND tuoteno = '$query_row[tuoteno]'";
    pupe_query($qu);
  }

  $query = "UPDATE tilausrivi
            #JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
            SET tilausrivi.yhtio = 'artr'#, tilausrivin_lisatiedot.yhtio = 'artr'
            WHERE tilausrivi.yhtio = 'atarv' AND tilausrivi.tunnus = '{$query_row['tunnus']}'";
  //pupe_query($query);
}

echo "\n\n\n\n\n\n";
