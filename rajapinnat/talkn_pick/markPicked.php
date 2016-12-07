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
require "rajapinnat/talknpick/talknpick-functions.php";

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

// Tarkistetaan kaikki keräyksessä olevien tilausten statukset
$query = "SELECT lasku.tunnus, laskun_lisatiedot.ulkoinen_tarkenne
          FROM lasku
          JOIN varastopaikat ON (varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto and varastopaikat.ulkoinen_jarjestelma = 'D')
          JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus AND laskun_lisatiedot.ulkoinen_tarkenne != '')
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND lasku.tila    IN ('L', 'V', 'G', 'S')
          AND lasku.alatila IN ('A', 'E')";
$res = pupe_query($query);

while ($taskrow = mysql_fetch_assoc($res)) {
  list($code, $response) = talknpick_task_status($taskrow["ulkoinen_tarkenne"]);

  if ($code != 200) {
    pupesoft_log('talknpick_mark_picked', "Tilauksen {$taskrow['tunnus']} haku epäonnistui: {$code} / {$response}.");
  }
  else {
    $task = json_decode($response);

    // Merkataan kerätyksi
    if ($task["status"] == "completed") {

      $kerattavat = array();

      foreach ($task["taskLines"] as $taskline) {
        $tilausrivin_tunnus = $taskline["orderLineNumber"];
        $kerattymaara = $taskline["taskAmount"];

        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tilausrivin_tunnus}'";
        $rivires = pupe_query($query);
        $rivirow = mysql_fetch_assoc($rivires);

        if (!isset($kerattavat[$taskrow['tunnus']][$rivirow['tuoteno']])) $kerattavat[$tilaus][$tuote] = $kerattymaara;
        else $kerattavat[$taskrow['tunnus']][$rivirow['tuoteno']] += $kerattymaara;
      }

      merkkaa_keratyksi($kerattavat);
    }
  }
}
