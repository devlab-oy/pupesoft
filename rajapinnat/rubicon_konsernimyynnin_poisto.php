<?php

if (php_sapi_name() != 'cli') {
  echo "clionly!";
  exit(1);
}

if (empty($argv[1])) {
  echo "Anna Puperoot!";
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
// cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

// Haetaan $yhtiorow, $kukarow
$yhtiorow = hae_yhtion_parametrit('artr');
$kukarow = hae_kukarow('admin', 'artr');

// Hardcoodataan nämä
$autoasi_ytunnus = "FI06453510";
$autoasi_ovttunnus = "003706453510";
$orum_ovttunnus = "003720428100";
$takaraja = '2012-07-01';
$konversio_paiva = "2014-05-25";

$query = "LOCK TABLES
          lasku WRITE,
          laskun_lisatiedot WRITE,
          tapahtuma WRITE,
          tilausrivi WRITE,
          tilausrivin_lisatiedot WRITE,
          tiliointi WRITE";
pupe_query($query);

echo "Haetaan Örumin tilaukset + laskut Autoasille\n";

// Haetaan kaikki myynti, joka Örum on myynyt Autoasille halutulla välillä
$query = "SELECT lasku.tunnus,
          lasku.tila,
          ifnull(group_concat(tilausrivi.tunnus), '') AS tilausrivi_tunnukset
          FROM lasku
          LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.uusiotunnus = lasku.tunnus)
          WHERE lasku.yhtio            = '{$kukarow['yhtio']}'
          AND lasku.tila               IN ('U', 'L')
          AND lasku.ytunnus            = '{$autoasi_ytunnus}'
          AND lasku.yhtio_ovttunnus    = '{$orum_ovttunnus}'
          AND lasku.tapvm BETWEEN '{$takaraja}' AND '{$konversio_paiva}'
          GROUP BY lasku.tunnus,
          lasku.tila";
$result = pupe_query($query);

$total_items = mysql_num_rows($result);
$current_item = 0;

echo "Poistetaan tilaukset + laskut + tilausrivit\n";

while ($row = mysql_fetch_assoc($result)) {
  $current_item++;
  progress_bar($current_item, $total_items);

  $lasku_tunnus = $row['tunnus'];
  $lasku_tila = $row['tila'];
  $tilausrivi_tunnukset = $row['tilausrivi_tunnukset'];

  $query = "DELETE FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = {$lasku_tunnus}";
  pupe_query($query);

  // Poistetaan laskun lisätiedot
  $query = "DELETE FROM laskun_lisatiedot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = {$lasku_tunnus}";
  pupe_query($query);

  if ($lasku_tila == 'U') {
    // Poistetaan tilausrivit
    $query = "DELETE FROM tilausrivi
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND uusiotunnus = {$lasku_tunnus}";
    pupe_query($query);

    // Poistetaan tiliöinnit
    $query = "DELETE FROM tiliointi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND ltunnus = {$lasku_tunnus}";
    pupe_query($query);
  }

  if ($tilausrivi_tunnukset != "") {
    // Poistetaan tapahtumat
    $query = "DELETE FROM tapahtuma
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND laji       = 'laskutus'
              AND rivitunnus IN ({$tilausrivi_tunnukset})";
    pupe_query($query);

    // Poistetaan tilausrivin lisätiedot
    $query = "DELETE FROM tilausrivin_lisatiedot
              WHERE yhtio          = '{$kukarow['yhtio']}'
              AND tilausrivitunnus IN ({$tilausrivi_tunnukset})";
    pupe_query($query);
  }
}

echo "\n\n\n\n\n\n";

echo "Korjataan puuttuvat ovttunnukset otsikolle\n";

// Jos laskuttajan ovttunnus on tyhjää ja nimessä on autoasi, päivitetään että on autoasi
$query = "UPDATE lasku
          SET yhtio_ovttunnus = '{$autoasi_ovttunnus}'
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND lasku.tila = 'U'
          AND lasku.tapvm BETWEEN '{$takaraja}' AND '{$konversio_paiva}'
          AND lasku.yhtio_nimi LIKE '%autoasi%'
          AND lasku.yhtio_ovttunnus = ''";
$result = pupe_query($query);

echo "Haetaan Autosin tekemät laskut\n";

// Haetaan kaikki myyntirivit samalta ajalta, jotka on Autoasin tekemiä
$query = "SELECT tilausrivi.tunnus,
          tilausrivi.laskutettuaika,
          tilausrivi.tuoteno,
          tilausrivi.kpl,
          tilausrivi.rivihinta,
          tilausrivi.kate,
          lasku.kate AS laskun_kate,
          tilausrivi.otunnus,
          tilausrivi.uusiotunnus
          FROM lasku
          INNER JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.uusiotunnus = lasku.tunnus
            AND tilausrivi.tyyppi      = 'L')
          WHERE lasku.yhtio            = '{$kukarow['yhtio']}'
          AND lasku.tila               = 'U'
          AND lasku.yhtio_ovttunnus    = '{$autoasi_ovttunnus}'
          AND lasku.tapvm BETWEEN '{$takaraja}' AND '{$konversio_paiva}'";
$result = pupe_query($query);

$total_items = mysql_num_rows($result);
$current_item = 0;
progress_bar(0, 0);

echo "Päivitetään tilausrivien katteet\n";

while ($row = mysql_fetch_assoc($result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $kpl = $row['kpl'];
  $laskutettu = $row['laskutettuaika'];
  $rivihinta = $row['rivihinta'];
  $tilausrivi_tunnus = $row['tunnus'];
  $tuoteno = $row['tuoteno'];
  $tilausrivin_kate = $row['kate'];
  $laskun_kate = $row['laskun_kate'];
  $laskun_tunnus = $row['uusiotunnus'];
  $tilauksen_tunnus = $row['otunnus'];

  // Katsotaan löytyykö Örumilta keskihankintahinta tälle tuotteelle
  // Rivitunnus > 0, että ei osuta konvertoituihin atarv -tapahtumiin
  // Laatija != fuusil, että ei ostua fuusiotapahtumiin
  $query = "SELECT hinta
            FROM tapahtuma
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND tuoteno     = '{$tuoteno}'
            AND laadittu    < '{$laskutettu}'
            AND laji        NOT IN ('poistettupaikka', 'uusipaikka')
            AND rivitunnus  > 0
            AND laatija    != 'fuusio'
            ORDER BY laadittu DESC
            LIMIT 1";
  $tapahtuma_result = pupe_query($query);

  // Ei löytynyt, ei päivitetä tätä tuotetta
  if (mysql_num_rows($tapahtuma_result) != 1) {
    continue;
  }

  $tapahtuma_row = mysql_fetch_assoc($tapahtuma_result);
  $keskihankintahinta = $tapahtuma_row['hinta'];

  // Lasketaan rivin uusi kate
  $kate = round($rivihinta - ($kpl * $keskihankintahinta), 6);

  // Jos kate on muuttunut
  if ($kate != $tilausrivin_kate) {
    // Päivitetään tilausrivi
    $query = "UPDATE tilausrivi SET
              kate        = $kate,
              laatija     = 'muutos-2014'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = {$tilausrivi_tunnus}";
    pupe_query($query);

    // Päivitetään tuotteen tapahtuma (rivitunnus negatiivinen)
    $query = "UPDATE tapahtuma SET
              hinta = $keskihankintahinta,
              laatija = 'muutos-2014'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'
              AND laji = 'laskutus'
              AND rivitunnus = -{$tilausrivi_tunnus}";
    pupe_query($query);
  }

  // Lasketaan katteen muutos
  $kate_erotus = $tilausrivin_kate - $kate;

  if ($kate_erotus != 0) {
    // Päivitetään laskun ja tilauksen kate
    $query = "UPDATE lasku SET
              kate        = round(kate - $kate_erotus, 2),
              muuttaja    = 'muutos-2014'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  IN ({$laskun_tunnus}, {$tilauksen_tunnus})";
    pupe_query($query);
  }
}

$query = "UNLOCK TABLES";
pupe_query($query);

echo "\n\n\n\n\n\n";
echo "Valmis\n";
