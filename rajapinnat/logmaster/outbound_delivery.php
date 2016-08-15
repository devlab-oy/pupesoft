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

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  die ("Saapumisen lähettäminen estetty yhtiötasolla!\n");
}

$query = "SELECT *
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND (
            (tila = 'N' AND alatila = 'A') OR
            (tila = 'G' AND alatila = 'J')
          )
          AND CURTIME() >= DATE_ADD(h1time, INTERVAL 15 MINUTE)
          AND lahetetty_ulkoiseen_varastoon = NULL";
$laskures = pupe_query($query);

while ($laskurow = mysql_fetch_assoc($laskures)) {
  $filename = logmaster_outbounddelivery($laskurow['tunnus']);

  if ($filename === false) {
    continue;
  }

  $palautus = logmaster_send_file($filename);

  if ($palautus == 0) {
    logmaster_sent_timestamp($laskurow['tunnus']);
    pupesoft_log('logmaster_outbound_delivery', "Siirretiin tilaus {$laskurow['tunnus']}.");
  }
  else {
    pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$laskurow['tunnus']} siirto epäonnistui.");
  }
}
