<?php

function unifaun_tilauksen_vak_koodit($tilausnumero) {
  global $yhtiorow, $kukarow;

  $vak_koodit = array();

  if (empty($tilausnumero)) {
    return $vak_koodit;
  }

  // haetaan tilauksen vak tuotteet
  $query = "SELECT tuote.vakkoodi
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno)
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus = '{$tilausnumero}'
            AND tuote.vakkoodi != ''";
  $vak_chk_res = pupe_query($query);

  // ei vak tuoteita
  if (mysql_num_rows($vak_chk_res) == 0) {
    return $vak_koodit;
  }

  // haetaan tilauksen paino
  $tilauksen_paino = unifaun_tilauksen_paino($tilausnumero);

  while ($vak_chk_row = mysql_fetch_assoc($vak_chk_res)) {
    $vak_tiedot = unifaun_vak_tiedot($vak_chk_row['vakkoodi']);

    $vak_koodit[] = array(
      'kpl'            => $vak_tiedot['kpl'],
      'kpl_paino'      => $vak_tiedot['kpl_paino'],
      'limited_qty'    => $vak_tiedot['limited_qty'],
      'lipukkeet'      => $vak_tiedot['lipukkeet'],
      'luokituskoodi'  => $vak_tiedot['luokituskoodi'],
      'luokka'         => $vak_tiedot['luokka'],
      'nimi_ja_kuvaus' => $vak_tiedot['nimi_ja_kuvaus'],
      'paino'          => $tilauksen_paino,
      'pakkausryhma'   => $vak_tiedot['pakkausryhma'],
      'tuotenimitys'   => $vak_tiedot['tuotenimitys'],
      'tuoteno'        => $vak_tiedot['tuoteno'],
      'yk_nro'         => $vak_tiedot['yk_nro'],
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

  // Käytetään vain millilitroja ja grammoja
  if ($ltq_yks == 'l' or $ltq_yks == 'kg') {
    $ltq_maara = $ltq_maara * 1000;
  }

  if ($ltq_maara > 0 and $ltq_maara <= $limited_qty) {
    return 'LTD QTY';
  }

  return '';
}

function unifaun_vak_tiedot($tuote_vak, $type = 'vak') {
  global $kukarow, $yhtiorow;

  // otetaan sisään tuote.vak
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

  // jos on käytössä "normaali vak käsittely", niin tuotteen takana on suoraan vak koodi
  // eikä meillä ole muuta tietoa
  if ($yhtiorow["vak_kasittely"] == "") {
    $vak_tiedot = array(
      'kpl'            => '',
      'kpl_paino'      => '',
      'limited_qty'    => '',
      'lipukkeet'      => '',
      'luokituskoodi'  => '',
      'luokka'         => '',
      'nimi_ja_kuvaus' => '',
      'pakkausryhma'   => '',
      'tuotenimitys'   => '',
      'tuoteno'        => '',
      'yk_nro'         => $tuote_vak,
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

  $vakmaara = $vakkoodi_row["vakmaara"];
  $limited_qty = $vakkoodi_row["rajoitetut_maarat_ja_poikkeusmaarat_1"];
  $limited_qty_val = unifaun_limited_quantity($vakmaara, $limited_qty);

  $vak_tiedot = array(
    'kpl'            => $vakkoodi_row['kpl'],
    'kpl_paino'      => $vakkoodi_row['kpl_paino'],
    'limited_qty'    => $limited_qty_val,
    'lipukkeet'      => $vakkoodi_row['lipukkeet'],
    'luokituskoodi'  => $vakkoodi_row['luokituskoodi'],
    'luokka'         => $vakkoodi_row['luokka'],
    'nimi_ja_kuvaus' => $vakkoodi_row['nimi_ja_kuvaus'],
    'pakkausryhma'   => $vakkoodi_row['pakkausryhma'],
    'tuotenimitys'   => $vakkoodi_row['nimitys'],
    'tuoteno'        => $vakkoodi_row['tuoteno'],
    'yk_nro'         => $vakkoodi_row['yk_nro'],
  );

  return $vak_tiedot;
}
