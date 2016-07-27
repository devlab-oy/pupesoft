<?php

// Tällä ohjelmalla voidaan generoida myyntitilauksia annetulle ajanjaksolle

require '../../inc/parametrit.inc';
require 'rajapinnat/yrityspeli/yrityspeli_functions.php';
require 'tilauskasittely/luo_myyntitilausotsikko.inc';

if (empty($kauppakeskus_myyra)) $kauppakeskus_myyra = '003732419754';
if (empty($kokonaiskustannus))  $kokonaiskustannus = 1000;
if (empty($tee))                $tee = '';
if (empty($tilausmaara))        $tilausmaara = 3;

if ($tee == 'GENEROI') {
  generoi_myyntitilauksia($valitut, $kokonaiskustannus, $tilausmaara, $kauppakeskus_myyra);
}

echo_yrityspeli_kayttoliittyma($kokonaiskustannus, $tilausmaara);

require "inc/footer.inc";
