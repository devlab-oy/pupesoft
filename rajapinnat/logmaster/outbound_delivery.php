<?php

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  die ("Kerättävien tilauksien lähettäminen estetty yhtiötasolla!\n");
}

if ($yhtiorow['lahetteen_tulostustapa'] != 'K') {
  die ("Kerättävien tilauksien lähettäminen edellyttää että keräyslistojen tulostuksessa käytetään tulostusjonoa!\n");
}

if ($yhtiorow['siirtolistan_tulostustapa'] != 'K') {
  die ("Kerättävien tilauksien lähettäminen edellyttää että siirtolistojen tulostuksessa käytetään tulostusjonoa!\n");
}

# Haetaan myyntitilauksia siirtolistoja ja myyntitilejä
# Varaston täytyy käyttää ulkoista varastoa
# Varasto ei saa olla poistettu
# Maksuehto ei saa olla jälkivaatimus
# Tilausrivi ei saa olla jälkitoimitus eikä hyvitysrivejä (kappaleet miinusmerkkisiä)
# Tuote ei saa olla saldoton eikä poistettu
$query = "SELECT DISTINCT lasku.tunnus
          FROM lasku
          JOIN varastopaikat ON (
            varastopaikat.yhtio   = lasku.yhtio AND
            varastopaikat.tunnus  = lasku.varasto AND
            varastopaikat.tyyppi != 'P' AND
            varastopaikat.ulkoinen_jarjestelma IN ('L','P')
          )
          JOIN tilausrivi ON (
            tilausrivi.yhtio    = lasku.yhtio AND
            tilausrivi.otunnus  = lasku.tunnus AND
            (tilausrivi.varattu + tilausrivi.kpl) > 0 AND
            tilausrivi.tyyppi   IN ('L', 'G') AND
            tilausrivi.var     != 'J'
          )
          JOIN tuote ON (
            tuote.yhtio         = tilausrivi.yhtio AND
            tuote.tuoteno       = tilausrivi.tuoteno AND
            tuote.ei_saldoa     = ''
          )
          LEFT JOIN maksuehto ON (
            maksuehto.yhtio = lasku.yhtio AND
            maksuehto.tunnus = lasku.maksuehto
          )
          LEFT JOIN kuka ON (
            kuka.yhtio = lasku.yhtio AND
            kuka.kesken = lasku.tunnus
          )
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND (
            (lasku.tila = 'N' AND lasku.alatila = 'A') OR
            (
              lasku.tila = 'G' AND
              lasku.alatila = 'J' AND
              lasku.toimitustavan_lahto = 0
            )
          )
          AND NOW() >= DATE_ADD(lasku.h1time, INTERVAL (if(varastopaikat.ulkoinen_jarjestelma='L', 5,15)) MINUTE)
          AND (lasku.lahetetty_ulkoiseen_varastoon IS NULL or lasku.lahetetty_ulkoiseen_varastoon = 0)
          AND (maksuehto.jv IS NULL OR maksuehto.jv = '')
          AND kuka.kesken IS NULL";
$laskures = pupe_query($query);

while ($laskurow = mysql_fetch_assoc($laskures)) {
  $filename = logmaster_outbounddelivery($laskurow['tunnus']);

  if ($filename === false) {
    continue;
  }

  $palautus = logmaster_send_file($filename);

  if ($palautus == 0) {
    logmaster_sent_timestamp($laskurow['tunnus']);
    logmaster_mark_as_sent($laskurow['tunnus']);
    pupesoft_log('logmaster_outbound_delivery', "Siirretiin tilaus {$laskurow['tunnus']}.");
  }
  else {
    pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$laskurow['tunnus']} siirto epäonnistui.");
  }
}
