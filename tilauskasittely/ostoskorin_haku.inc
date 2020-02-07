<?php

if ($tee == 'hae_orumnetin_ostoskori') {

  $orig_yhtio = $kukarow['yhtio'];

  // Merca - Örum keissi
  $kukarow['yhtio'] = $kukarow['yhtio'] == 'atarv' ? 'artr' : $kukarow['yhtio'];

  $query = "SELECT kesken
            FROM kuka
            WHERE kuka.yhtio = '{$kukarow['yhtio']}'
            AND kuka.kuka    = '{$extranet_kayttaja}'";
  $result = pupe_query($query);
  $kuka_kesken_row = mysql_fetch_assoc($result);

  //haetaan extranet käyttäjällä kesken oleva myyntitilaus ja sen myyntitilausrivit
  $myyntitilaus = hae_lasku($kuka_kesken_row['kesken']);
  $myyntitilaus['tilausrivit'] = hae_laskun_tilausrivit($kuka_kesken_row['kesken']);

  if (!empty($myyntitilaus['tilausrivit']) and !empty($kuka_kesken_row['kesken'])) {

    poista_lasku($myyntitilaus['tunnus']);

    // Merca - Örum keissi
    $kukarow['yhtio'] = $orig_yhtio;

    $uusi_myyntitilaus = hae_lasku($kukarow['kesken']);

    foreach ($myyntitilaus['tilausrivit'] as $tilausrivi) {

      // ei lisätä lapsituotetta, koska lisaa_rivi() osaa lisätä ne automaagisesti
      if ($tilausrivi['perheid'] != 0 and $tilausrivi['perheid'] != $tilausrivi['tunnus']) continue;

      $haettu_tuote = hae_tuote($tilausrivi['tuoteno']);

      $parametrit = array(
        'trow'     => $haettu_tuote,
        'laskurow'   => $uusi_myyntitilaus,
        'kpl'     => $tilausrivi['tilkpl'],
        'hinta'     => '',
        'ale1'     => '',
        'ale2'     => '',
        'ale3'     => '',
        'netto'     => '',
        'tuoteno'   => $tilausrivi['tuoteno'],
        'rekisterinumero' => $tilausrivi['rekisterinumero'],
        'toim' => $toim,
      );

      lisaa_rivi($parametrit);
    }
  }

  // Merca - Örum keissi
  $kukarow['yhtio'] = $orig_yhtio;

  unset($tee);
  unset($hinta);
  unset($netto);
  unset($ale);
  unset($alehinta_alv);
  unset($alehinta_val);
  unset($myyntitilaus);
}
