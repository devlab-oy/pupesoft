<?php

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l�hett�v�� yhti�t�!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/smarten/smarten-functions.php";

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin k�ytt�j� ei l�ydy!\n");
}

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  die ("Ker�tt�vien tilauksien l�hett�minen estetty yhti�tasolla!\n");
}

if ($yhtiorow['lahetteen_tulostustapa'] != 'K') {
  die ("Ker�tt�vien tilauksien l�hett�minen edellytt�� ett� ker�yslistojen tulostuksessa k�ytet��n tulostusjonoa!\n");
}

if ($yhtiorow['siirtolistan_tulostustapa'] != 'K') {
  die ("Ker�tt�vien tilauksien l�hett�minen edellytt�� ett� siirtolistojen tulostuksessa k�ytet��n tulostusjonoa!\n");
}

# Haetaan myyntitilauksia siirtolistoja ja myyntitilej�
# Varaston t�ytyy k�ytt�� ulkoista varastoa
# Varasto ei saa olla poistettu
# Maksuehto ei saa olla j�lkivaatimus
# Tilausrivi ei saa olla j�lkitoimitus eik� hyvitysrivej� (kappaleet miinusmerkkisi�)
# Tuote ei saa olla saldoton eik� poistettu
$query = "SELECT DISTINCT lasku.tunnus
          FROM lasku
          JOIN varastopaikat ON (
            varastopaikat.yhtio   = lasku.yhtio AND
            varastopaikat.tunnus  = lasku.varasto AND
            varastopaikat.tyyppi != 'P' AND
            varastopaikat.ulkoinen_jarjestelma = 'S'
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
          #AND NOW() >= DATE_ADD(lasku.h1time, INTERVAL 5 MINUTE)
          #AND lasku.tunnus=292821
          AND (lasku.lahetetty_ulkoiseen_varastoon IS NULL or lasku.lahetetty_ulkoiseen_varastoon = 0)
          AND (maksuehto.jv IS NULL OR maksuehto.jv = '')
          AND kuka.kesken IS NULL";
$laskures = pupe_query($query);

while ($laskurow = mysql_fetch_assoc($laskures)) {
  $filename = smarten_outbounddelivery($laskurow['tunnus']);

  if ($filename === false) {
    continue;
  }

  // Siirret��n my�s tilauksen liitetiedostot
  smarten_outbounddelivery_attachments($laskurow['tunnus']);

  $palautus = smarten_send_file($filename);

  if ($palautus == 0) {
    //smarten_sent_timestamp($laskurow['tunnus']);
    //smarten_mark_as_sent($laskurow['tunnus']);
    pupesoft_log('smarten_outbound_delivery', "Siirretiin tilaus {$laskurow['tunnus']}.");

    // Siirret��n my�s tilauksen liitetiedostot
    smarten_outbounddelivery_attachments($laskurow['tunnus']);

  }
  else {
    pupesoft_log('smarten_outbound_delivery', "Tilauksen {$laskurow['tunnus']} siirto ep�onnistui.");
  }
}
