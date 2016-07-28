<?php

// Tällä ohjelmalla voidaan generoida myyntitilauksia annetulle ajanjaksolle

require '../../inc/parametrit.inc';
require 'rajapinnat/yrityspeli/yrityspeli_functions.php';

if (empty($kauppakeskus_myyra)) $kauppakeskus_myyra = '003732419754';
if (empty($kokonaiskustannus))  $kokonaiskustannus = 1000;
if (empty($tee))                $tee = '';
if (empty($tilausmaara))        $tilausmaara = 3;
if (empty($response))           $response = array();
if (empty($alkuaika))           $alkuaika = date("Y-m-d", strtotime('monday this week'));
if (empty($loppuaika))          $loppuaika  = date("Y-m-d", strtotime('sunday this week'));

if ($tee == 'GENEROI') {
  $params = array(
    "asiakkaat" => $valitut,
    "kokonaiskustannus" => $kokonaiskustannus,
    "tilausmaara" => $tilausmaara,
  );

  $response = generoi_ostotilauksia($params);
}

$params = array(
  "alkuaika" => $alkuaika,
  "kokonaiskustannus" => $kokonaiskustannus,
  "loppuaika" => $loppuaika,
  "messages" => $response,
  "tilauksettomat_yhtiot" => hae_tilauksettomat_yhtiot($alkuaika, $loppuaika),
  "tilausmaara" => $tilausmaara,
);

echo_yrityspeli_kayttoliittyma($params);

require "inc/footer.inc";
