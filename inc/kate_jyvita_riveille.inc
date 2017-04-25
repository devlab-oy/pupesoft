<?php

//T‰nne tarvitaan:
//$jysum joka on kokonaiskate joka tilaukselle halutaan
//$tilausnumero TAI $valitut_tilausrivi_tunnukset

if ((!isset($valitut_rivit_jyvitys) and empty($valitut_tilausrivi_tunnukset))) {
  laske_tilausriveille_uudet_hinnat($jysum, $tilausnumero);
}
else {
  laske_tilausriveille_uudet_hinnat($jysum, 0, $valitut_tilausrivi_tunnukset);
}

function laske_tilausriveille_uudet_hinnat($uusi_kate, $tilausnumero = 0, $tilausrivi_tunnukset = array()) {
  global $kukarow, $yhtiorow;

  if (!empty($tilausnumero)) {
    $tilausrivi_where = "  AND tilausrivi.otunnus = '{$tilausnumero}'";
  }
  else {
    $tilausrivi_where = "  AND tilausrivi.tunnus IN ('".implode("','", $tilausrivi_tunnukset)."')";
  }

  $query = "SELECT tilausrivi.*,
                   if (tuote.epakurantti100pvm = '0000-00-00',
                     if (tuote.epakurantti75pvm = '0000-00-00',
                       if (tuote.epakurantti50pvm = '0000-00-00',
                         if (tuote.epakurantti25pvm = '0000-00-00',
                          tuote.kehahin,
                         tuote.kehahin * 0.75),
                       tuote.kehahin * 0.5),
                     tuote.kehahin * 0.25),
                   0) AS kehahin
            FROM tilausrivi
            JOIN tuote
            USING (yhtio, tuoteno)
            WHERE yhtio = '{$kukarow['yhtio']}'
            {$tilausrivi_where}";
  $result = pupe_query($query);

  $tilausrivit = array();
  while ($tilausrivi = mysql_fetch_assoc($result)) {
    $tilausrivit[] = $tilausrivi;
  }

  $summa = 0;
  $otunnus = 0;
  foreach ($tilausrivit as $tilausrivi) {
    $otunnus = $tilausrivi['otunnus'];

    if ((float)$uusi_kate > 99.99) {
      $uusi_kate = 99.99;
    }

    $rivin_alennus = generoi_alekentta_php($tilausrivi, 'M', 'kerto');

    //Jos rivill‰ on alennuksia, niin kahahinnan p‰‰lle pit‰‰ laskea rivin alennukset, jotta uuden katteen laskenta menee oikein.
    $kehahin_plus_alennus = $tilausrivi['kehahin'] / $rivin_alennus;
    $uusi_hinta = $kehahin_plus_alennus / ( 1 - ( $uusi_kate / 100 ));

    //Jos alv_kasittely on p‰‰ll‰, tarkoittaa se,
    //ett‰ myyntihinnoissa on alv laskettuna myyntihinnan sis‰lle.
    //Verrattuna verottomaan myyntihintaan,
    //hinnan pit‰‰ siis olla tilausrivi.alv verran suurempi, jotta katteen lasku on oikein.
    if ($yhtiorow['alv_kasittely'] == '') {
      $uusi_hinta = $uusi_hinta * (1 + ($tilausrivi['alv'] / 100));
    }

    $uusi_hinta = hintapyoristys($uusi_hinta);

    $summa += $uusi_hinta;

    $query = "UPDATE tilausrivi
              SET hinta = '{$uusi_hinta}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tilausrivi['tunnus']}'";
    pupe_query($query);
  }

  $query = "UPDATE lasku
            SET hinta = '{$summa}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$otunnus}'";
  pupe_query($query);
}
