<?php

// T‰ll‰ ohjelmalla voidaan generoida myyntitilauksia kuluvalle viikolle yrityksille

require '../../inc/parametrit.inc';
require 'tilauskasittely/luo_myyntitilausotsikko.inc';
require 'rajapinnat/yrityspeli/yrityspeli_functions.php';

if (!isset($tee)) $tee = '';
if (!isset($kokonaiskustannus)) $kokonaiskustannus = 1000;
if (!isset($tilausmaara)) $tilausmaara = 3;

$kauppakeskus_myyra = '003732419754';

echo "<font class='head'>", t("Generoi myyntitilauksia yrityksille"), "</font><hr>";

if ($tee == 'GENEROI') {
  if (empty($valitut)) {
    echo "<font class='error'>Et valinnut yht‰‰n yrityst‰</font><br><br>";
  }
  else {
    $tilaukset = generoi_myyntitilauksia($valitut, $kokonaiskustannus, $tilausmaara, $kauppakeskus_myyra);
  }

  $tee = '';
}

if (empty($tee)) {
  echo_yrityspeli_kayttoliittyma($kokonaiskustannus, $tilausmaara);
}

require "inc/footer.inc";
