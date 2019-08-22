<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$PHP_CLI = true;

if (!isset($argv[1])) {
  die ("Anna yhtio parametriksi!");
}

$pupe_root_polku = dirname(__FILE__);
date_default_timezone_set('Europe/Helsinki');

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Tehdään oletukset
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);
if (empty($yhtiorow)) {
  die("Yhtiö ei löydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);
if (empty($kukarow)) {
  die("Admin -käyttäjä ei löydy.");
}

$query = "SELECT *
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
            AND tilaustyyppi = 'P'
            AND tila = 'N'";
$hyvitystilaukset = pupe_query($query);
while ($laskurow = mysql_fetch_assoc($hyvitystilaukset)) {
  echo "Käsitellään lasku " . $laskurow['tunnus'] . "\n";

  $kukarow["kesken"] = $laskurow['tunnus'];
  require "tilauskasittely/tilaus-valmis.inc";
}

$kukarow['kesken'] = "0";
$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
pupe_query($query);
