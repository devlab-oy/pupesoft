<?php

function unifaun_tilauksen_vak_koodit($tilausnumero) {
  global $yhtiorow, $kukarow;

  $vak_koodit = array();

  if (empty($tilausnumero)) {
    return $vak_koodit;
  }

  // haetaan tilauksen vak tuotteet
  $query = "SELECT tuote.vakkoodi,
            tuote.vakmaara,
            tuote.tuoteno,
            tuote.nimitys,
            tilausrivi.varattu as kpl,
            tuote.tuotemassa AS kpl_paino
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno)
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus = '{$tilausnumero}'
            AND tilausrivi.tyyppi != 'D'
            AND tilausrivi.var not in ('P','J','O','S')
            AND tuote.vakkoodi != ''";
  $vak_chk_res = pupe_query($query);

  // ei vak tuoteita
  if (mysql_num_rows($vak_chk_res) == 0) {
    return $vak_koodit;
  }

  while ($vak_chk_row = mysql_fetch_assoc($vak_chk_res)) {
    $vak_tiedot = unifaun_vak_tiedot($vak_chk_row['vakkoodi'], $vak_chk_row['vakmaara']);

    $vak_koodit[] = array(
      'kpl'            => round($vak_chk_row['kpl']),
      'kpl_paino'      => $vak_chk_row['kpl_paino'],
      'limited_qty'    => $vak_tiedot['limited_qty'],
      'lipukkeet'      => $vak_tiedot['lipukkeet'],
      'luokituskoodi'  => $vak_tiedot['luokituskoodi'],
      'luokka'         => $vak_tiedot['luokka'],
      'nimi_ja_kuvaus' => $vak_tiedot['nimi_ja_kuvaus'],
      'paino'          => $vak_chk_row['kpl_paino'],
      'pakkausryhma'   => $vak_tiedot['pakkausryhma'],
      'tuotenimitys'   => $vak_chk_row['nimitys'],
      'tuoteno'        => $vak_chk_row['tuoteno'],
      'yk_nro'         => $vak_tiedot['yk_nro'],
      'kuljetus_kategoria' => $vak_tiedot['kuljetus_kategoria'],
    );
  }

  return $vak_koodit;
}

function unifaun_tilauksen_paino($tilausnumero) {
  global $yhtiorow, $kukarow;

  if (empty($tilausnumero)) {
    return 0;
  }

  $query = "SELECT ifnull(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS paino
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno)
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus = '{$tilausnumero}'
            AND tilausrivi.tyyppi != 'D'
            AND tilausrivi.var not in ('P','J','O','S')
            AND tuote.vakkoodi != ''";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  return $row['paino'];
}

function unifaun_limited_quantity_limit($key) {
  $limited_qty = array(
    "LQ0"   => "0",
    "LQ1"   => "120",
    "LQ2"   => "1000",
    "LQ3"   => "500",
    "LQ4"   => "3000",
    "LQ5"   => "5000",
    "LQ6"   => "5000",
    "LQ7"   => "5000",
    "LQ8"   => "3000",
    "LQ9"   => "6000",
    "LQ10"  => "500",
    "LQ11"  => "500",
    "LQ12"  => "1000",
    "LQ13"  => "1000",
    "LQ14"  => "25",
    "LQ15"  => "100",
    "LQ16"  => "125",
    "LQ17"  => "500",
    "LQ18"  => "1000",
    "LQ19"  => "5000",
    "LQ20"  => "0",
    "LQ21"  => "0",
    "LQ22"  => "1000",
    "LQ23"  => "3000",
    "LQ24"  => "6000",
    "LQ25"  => "1000",
    "LQ26"  => "500",
    "LQ27"  => "6000",
    "LQ28"  => "3000",
  );

  $qty = isset($limited_qty[$key]) ? $limited_qty[$key] : false;

  return $qty;
}

function unifaun_limited_quantity($vak_maara, $limited_qty) {
  $limited_qty = unifaun_limited_quantity_limit($limited_qty);

  if ($limited_qty === false or empty($vak_maara)) {
    return '';
  }

  // putstaan arvot
  $ltq_yks = preg_replace("/[^a-z]/", "", strtolower($vak_maara));
  $ltq_maara = (float) preg_replace("/[^0-9,\.]/", "", str_replace(",", ".", $vak_maara));

  // K‰ytet‰‰n vain millilitroja ja grammoja
  if ($ltq_yks == 'l' or $ltq_yks == 'kg') {
    $ltq_maara = $ltq_maara * 1000;
  }

  if ($ltq_maara > 0 and $ltq_maara <= $limited_qty) {
    return 'LTD QTY';
  }

  return '';
}

function unifaun_vak_tiedot($tuote_vak, $tuote_vakmaara, $type = 'vak') {
  global $kukarow, $yhtiorow;

  // otetaan sis‰‰n tuote.vak
  if (empty($tuote_vak)) {
    return false;
  }

  // type on 'vak' tai 'imdg'
  if ($type == 'vak') {
    $vak_table = 'vak';
  }
  elseif ($type == 'imdg') {
    $vak_table = 'vak_imdg';
  }
  else {
    return false;
  }

  // jos on k‰ytˆss‰ "normaali vak k‰sittely", niin tuotteen takana on suoraan vak koodi
  // eik‰ meill‰ ole muuta tietoa
  if ($yhtiorow["vak_kasittely"] == "") {
    $vak_tiedot = array(
      'limited_qty'    => '',
      'lipukkeet'      => '',
      'luokituskoodi'  => '',
      'luokka'         => '',
      'nimi_ja_kuvaus' => '',
      'pakkausryhma'   => '',
      'yk_nro'         => $tuote_vak,
      'kuljetus_kategoria' => '',
    );

    return $vak_tiedot;
  }

  // haetaan VAK tiedot
  $query = "SELECT *
            FROM {$vak_table}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tuote_vak}'";
  $vakkoodi_res = pupe_query($query);
  $vakkoodi_row = mysql_fetch_assoc($vakkoodi_res);

  $limited_qty = $vakkoodi_row["rajoitetut_maarat_ja_poikkeusmaarat_1"];
  $limited_qty_val = unifaun_limited_quantity($tuote_vakmaara, $limited_qty);

  $vak_tiedot = array(
    'limited_qty'    => $limited_qty_val,
    'lipukkeet'      => $vakkoodi_row['lipukkeet'],
    'luokituskoodi'  => $vakkoodi_row['luokituskoodi'],
    'luokka'         => $vakkoodi_row['luokka'],
    'nimi_ja_kuvaus' => $vakkoodi_row['nimi_ja_kuvaus'],
    'pakkausryhma'   => $vakkoodi_row['pakkausryhma'],
    'yk_nro'         => $vakkoodi_row['yk_nro'],
    'kuljetus_kategoria' => $vakkoodi_row['kuljetus_kategoria'],
  );

  return $vak_tiedot;
}

// palauttaa lasku-taulun kent‰n nimen, jota tulee k‰ytt‰‰ unifaun sanomassa l‰hett‰j‰n viitteen‰
function unifaun_sender_reference() {
  global $kukarow, $yhtiorow;

  $default_value = 'viesti';

  $query = "SELECT selite
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji = 'UNIFAUN_REF'
            ORDER BY tunnus
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) !== 1) {
    return $default_value;
  }

  $row = mysql_fetch_assoc($result);
  $value = $row['selite'];

  if (empty($value)) {
    return $default_value;
  }

  return $value;
}
