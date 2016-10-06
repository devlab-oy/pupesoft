<?php

// Tällä ohjelmalla voidaan generoida myyntitilauksia annetulle ajanjaksolle

require '../../inc/parametrit.inc';
require 'rajapinnat/yrityspeli/yrityspeli_functions.php';

if (empty($alkuaika))          $alkuaika = date("Y-m-d", strtotime('monday this week'));
if (empty($kokonaiskustannus)) $kokonaiskustannus = 1000;
if (empty($loppuaika))         $loppuaika = date("Y-m-d", strtotime('sunday this week'));
if (empty($response))          $response = array();
if (empty($tee))               $tee = '';
if (empty($tilausmaara))       $tilausmaara = 3;
if (empty($valitut_tryt))      $valitut_tryt = array();
if (empty($toimipaikat))       $toimipaikat = array();

if ($tee == 'GENEROI') {
  $params = array(
    "asiakkaat"         => $valitut,
    "kokonaiskustannus" => $kokonaiskustannus,
    "tilausmaara"       => $tilausmaara,
    "valitut_tryt"      => $valitut_tryt,
    "toimipaikat"       => (array) $toimipaikat,
  );

  $response = yrityspeli_generoi_ostotilauksia($params);
}

$params = array(
  "alkuaika"              => $alkuaika,
  "kokonaiskustannus"     => $kokonaiskustannus,
  "loppuaika"             => $loppuaika,
  "messages"              => $response,
  "tilauksettomat_yhtiot" => yrityspeli_hae_tilauksettomat_yhtiot($alkuaika, $loppuaika),
  "tilausmaara"           => $tilausmaara,
  "valitut_tryt"          => $valitut_tryt,
  "toimipaikat"           => $toimipaikat,
);

yrityspeli_kayttoliittyma($params);

require "inc/footer.inc";
