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
require "talknpick-functions.php";

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

$query = "SELECT tuote.*, ta.selite AS synkronointi, ta.tunnus AS ta_tunnus
          FROM tuote
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
          WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
          #AND tuote.ei_saldoa = ''
          AND tuote.tuotetyyppi NOT IN ('A', 'B')
          HAVING (ta.tunnus IS NOT NULL AND ta.selite = '') OR
                  # jos avainsanaa ei ole olemassa ja status P niin ei haluta näitä tuotteita jatkossakaan
                 (ta.tunnus IS NULL AND tuote.status != 'P')";
$res = pupe_query($query);

while ($tuoterow = mysql_fetch_assoc($res)) {
  list($code, $response) = talknpick_create_product($tuoterow);

  $productID = (int) $response["productID"];

  if ($code != 200 or empty($productID)) {
    pupesoft_log('talknpick_create_product', "Tuotteen {$tuoterow['tuoteno']} perustus epäonnistui: {$code} / {$response}.");
  }
  else {
    pupesoft_log('talknpick_create_product', "Tuotteen {$tuoterow['tuoteno']} perustus onnistui, productID: {$productID}.");
  }
}
