<?php

function tulosta_asiakkaan_kuitti($yhtio, $laskunro, $tulostimen_tunnus) {
  $tapahtumat = hae_tapahtumat($yhtio, $laskunro);
  list($tilausrivit, $netto, $brutto, $vero) = hae_tilausrivit($yhtio, $laskunro);

  // Escape sequencejä printterille
  $logo         = "\x1C\x70\x01\x10";
  $katkaisu     = "\x1B\x69";
  $align_left   = "\x1B\x61\x00";
  $align_center = "\x1B\x61\x01";
  $align_right  = "\x1B\x61\x02";

  $kuitin_teksti = "{$logo}\n";

  foreach ($tilausrivit as $tilausrivi) {
    $kuitin_teksti .= "{$align_left}{$tilausrivi['tuoteno']}";
    $kuitin_teksti .= " {$tilausrivi['nimitys']}\n";
    $kuitin_teksti .= "{$align_right}{$tilausrivi['kpl']} {$tilausrivi['yksikko']}";
    $kuitin_teksti .= " a " . hintapyoristys($tilausrivi['hinta']);
    $kuitin_teksti .= "    " . hintapyoristys($tilausrivi['rivihinta']) . "\n";
  }

  $kuitin_teksti .= "{$align_left}\n\n";

  $kuitin_teksti .= "ALV       NETTO       VERO       BRUTTO\n";
  $kuitin_teksti .= "{$tapahtumat[0]['alv']}     {$netto}       {$vero}       {$brutto}\n\n";

  foreach ($tapahtumat as $tapahtuma) {
    $kuitin_teksti .= "TILAUSNUMERO: {$tapahtuma['tilausnumero']}\n";
    $kuitin_teksti .= "{$tapahtuma['asiakkaan_kuitti']}\n\n";
  }

  $kuitin_teksti .= "\n\n\n\n\n\n";
  $kuitin_teksti .= $katkaisu;

  lpr($kuitin_teksti, $tulostimen_tunnus);
}

function hae_tapahtumat($yhtio, $laskunro) {
  $query  = "SELECT *
            FROM lasku
            INNER JOIN maksupaatetapahtumat ON (lasku.tunnus = maksupaatetapahtumat.tilausnumero
              AND maksupaatetapahtumat.yhtio = '{$yhtio}')
            WHERE lasku.yhtio = '{$yhtio}'
            AND lasku.laskunro = '{$laskunro}'";
  $result = pupe_query($query);

  $tapahtumat = array();

  while ($tapahtuma = mysql_fetch_assoc($result)) {
    array_push($tapahtumat, $tapahtuma);
  }

  return $tapahtumat;
}

function hae_tilausrivit($yhtio, $laskunro) {
  $query  = "SELECT *
            FROM tilausrivi
            INNER JOIN lasku ON (lasku.tunnus = tilausrivi.otunnus
              AND lasku.yhtio = tilausrivi.yhtio)
            WHERE lasku.laskunro = '{$laskunro}'
            AND tilausrivi.yhtio = '{$yhtio}'";
  $result = pupe_query($query);

  $rivit  = array();
  $netto  = 0;
  $brutto = 0;
  $vero   = 0;

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($rivit, $rivi);
    $netto += $rivi['rivihinta'];
    $vero += $rivi['rivihinta'] * ($rivi['alv'] / 100);
    $brutto += $rivi['rivihinta'] * (1 + ($rivi['alv'] / 100));
  }

  return array(
    $rivit,
    hintapyoristys($netto),
    hintapyoristys($brutto),
    hintapyoristys($vero)
  );
}
