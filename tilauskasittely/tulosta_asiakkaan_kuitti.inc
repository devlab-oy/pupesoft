<?php

function tulosta_asiakkaan_kuitti($yhtio, $laskunro, $tulostimen_tunnus) {
  global $yhtiorow;

  $tapahtumat = hae_tapahtumat($yhtio, $laskunro);
  list($tilausrivit, $netto, $brutto, $vero) = hae_tilausrivit($yhtio, $laskunro);

  // Escape sequencejä printterille
  $logo           = "\x1C\x70\x01\x10";
  $katkaisu       = "\x1B\x69";
  $align_left     = "\x1B\x61\x00";
  $align_center   = "\x1B\x61\x01";
  $align_right    = "\x1B\x61\x02";
  $emphasized_on  = "\x1B\x45\x01";
  $emphasized_off = "\x1B\x45\x00";

  // Yhtiön tiedot
  $yhtion_nimi            = sprintf("%-21.21s", $tapahtumat[0]['yhtio_nimi']);
  $yhtion_puhelinnumero   = sprintf("%21.21s", "Puh. {$yhtiorow['puhelin']}");
  $yhtion_osoite          = sprintf("%-42.42s", $tapahtumat[0]['yhtio_osoite']);
  $yhtion_email           = sprintf("%-42.42s", $yhtiorow['email']);
  $yhtion_postino         = $tapahtumat[0]['yhtio_postino'];
  $yhtion_postitp         = $tapahtumat[0]['yhtio_postitp'];
  $yhtion_postino_postitp = sprintf("%-21.21s", "{$yhtion_postino} {$yhtion_postitp}");
  $yhtion_maa             = hae_maa(array("maakoodi" => $tapahtumat[0]['yhtio_maa']));
  $yhtion_maa             = sprintf("%-42.42s", $yhtion_maa['nimi']);
  $yhtion_www             = sprintf("%-42.42s", $yhtiorow['www']);
  $yhtion_ytunnus         = str_replace("0037", "", $tapahtumat[0]["yhtio_ovttunnus"]);
  $yhtion_ytunnus         = tulosta_ytunnus($yhtion_ytunnus, $tapahtumat[0]['yhtio_maa']);
  $yhtion_ytunnus         = sprintf("%21.21s", "Y-tunnus: {$yhtion_ytunnus}");

  // Arvojen asettelu spritfillä
  $yhteensa = sprintf("%34.34s", $brutto);

  $kuitin_teksti = "{$logo}\n\n";
  $kuitin_teksti .= "{$yhtion_nimi}{$yhtion_ytunnus}\n";
  $kuitin_teksti .= "{$yhtion_osoite}\n";
  $kuitin_teksti .= "{$yhtion_postino_postitp}{$yhtion_puhelinnumero}\n";
  $kuitin_teksti .= "{$yhtion_maa}\n";
  $kuitin_teksti .= "{$yhtion_email}\n";
  $kuitin_teksti .= "{$yhtion_www}\n\n\n";

  foreach ($tilausrivit as $tilausrivi) {
    $kappalehinta = $tilausrivi['rivihinta'] / $tilausrivi['kpl'];
    $kappalehinta = hintapyoristys($kappalehinta);
    $bruttohinta  = $tilausrivi['rivihinta'] * (1 + $tilausrivi['alv'] / 100);
    $bruttohinta  = hintapyoristys($bruttohinta);
    $rivihinta    = hintapyoristys($tilausrivi['rivihinta']);
    $tuoteno      = sprintf("%-11.11s", $tilausrivi['tuoteno']);
    $nimitys      = sprintf("%-31.31s", $tilausrivi['nimitys']);

    $kuitin_teksti .= "{$align_left}{$tuoteno}{$nimitys}\n";
    $kuitin_teksti .= "{$align_right}{$tilausrivi['kpl']} {$tilausrivi['yksikko']}";
    $kuitin_teksti .= " a {$kappalehinta}";
    $kuitin_teksti .= "    Veroton arvo: {$rivihinta}\n";
    $kuitin_teksti .= "Alv-%: {$tilausrivi['alv']}\n";
    $kuitin_teksti .= "Verollinen arvo: {$bruttohinta}\n";
    $kuitin_teksti .= "Tilausnumero: {$tilausrivi['otunnus']}\n";
  }

  // Hinta yhteensä
  $kuitin_teksti .= "{$align_left}";
  $kuitin_teksti .= "==========================================\n";
  $kuitin_teksti .= "{$emphasized_on}YHTEENSÄ{$yhteensa}{$emphasized_off}\n\n";

  // Verotiedot
  $alv    = sprintf("%-12.12s", "{$tapahtumat[0]["alv"]} %");
  $netto  = sprintf("%-12.12s", $netto);
  $vero   = sprintf("%-9.9s", $vero);
  $brutto = sprintf("%9.9s", $brutto);

  $kuitin_teksti .= "ALV         NETTO        VERO       BRUTTO\n";
  $kuitin_teksti .= "{$alv}{$netto}{$vero}{$brutto}\n\n";

  // Maksupäätetapahtumat
  foreach ($tapahtumat as $tapahtuma) {
    if ($tapahtuma['asiakkaan_kuitti']) {
      $kuitin_teksti .= "{$tapahtuma['asiakkaan_kuitti']}\n\n";
    }
  }

  $kuitin_teksti .= "\n\n\n\n";
  $kuitin_teksti .= $katkaisu;

  lpr($kuitin_teksti, $tulostimen_tunnus);
}

function hae_tapahtumat($yhtio, $laskunro) {
  $query  = "SELECT *
            FROM lasku
            LEFT JOIN maksupaatetapahtumat ON (lasku.tunnus = maksupaatetapahtumat.tilausnumero
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
