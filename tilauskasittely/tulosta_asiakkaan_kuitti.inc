<?php

function tulosta_asiakkaan_kuitti($laskunro, $tulostimen_tunnus) {
  global $yhtiorow;

  $laskut = hae_laskut($laskunro);
  list($tilausrivit, $netto, $brutto, $vero) = hae_tilausrivit($laskunro);

  // Escape sequencejä printterille
  $logo           = "\x1C\x70\x01\x10";
  $katkaisu       = "\x1B\x69";
  $align_left     = "\x1B\x61\x00";
  $align_center   = "\x1B\x61\x01";
  $align_right    = "\x1B\x61\x02";
  $emphasized_on  = "\x1B\x45\x01";
  $emphasized_off = "\x1B\x45\x00";

  // Yhtiön tiedot
  $yhtion_nimi          = $laskut[0]['yhtio_nimi'];
  $yhtion_puhelinnumero = wordwrap($yhtiorow['puhelin'], 3, " ", true);
  $yhtion_osoite        = $laskut[0]['yhtio_osoite'];
  $yhtion_postino       = $laskut[0]['yhtio_postino'];
  $yhtion_postitp       = $laskut[0]['yhtio_postitp'];
  $yhtion_www           = sprintf("%-42.42s", $yhtiorow['www']);
  $yhtion_ytunnus       = str_replace("0037", "", $laskut[0]["yhtio_ovttunnus"]);
  $yhtion_ytunnus       = tulosta_ytunnus($yhtion_ytunnus, $laskut[0]['yhtio_maa']);
  $yhtion_aukioloajat   = "ARK. 8.30-18.00 VARD. / LA. 10-15 LÖ.";

  // Arvojen asettelu spritfillä
  $yhteensa = sprintf("%34.34s", $brutto);

  $kuitin_teksti = "{$logo}\n\n";

  $kuitin_teksti .= "{$align_center}";

  $kuitin_teksti .= "{$yhtion_nimi}\n";
  $kuitin_teksti .= "{$yhtion_osoite}\n";
  $kuitin_teksti .= "{$yhtion_postino} {$yhtion_postitp}\n";
  $kuitin_teksti .= "Y-tunnus: {$yhtion_ytunnus}\n";
  $kuitin_teksti .= "Puh. {$yhtion_puhelinnumero}\n";
  $kuitin_teksti .= "{$yhtion_aukioloajat}\n\n";

  $kuitin_teksti .= "{$align_left}";

  foreach ($tilausrivit as $tilausrivi) {
    $kappalehinta = $tilausrivi['rivihinta_valuutassa'] / $tilausrivi['kpl'];
    $kappalehinta = $kappalehinta * (1 + $tilausrivi['alv'] / 100);
    $kappalehinta = hintapyoristys($kappalehinta, 1);
    $kappalehinta = sprintf("%14.14s", "a {$kappalehinta}");
    $bruttohinta  = $tilausrivi['rivihinta'] * (1 + $tilausrivi['alv'] / 100);
    $bruttohinta  = hintapyoristys($bruttohinta, 1);
    $bruttohinta  = sprintf("%14.14s", $bruttohinta);
    $rivihinta    = hintapyoristys($tilausrivi['rivihinta']);
    $tuoteno      = sprintf("%-42.42s", $tilausrivi['tuoteno']);
    $nimitys      = sprintf("%-42.42s", $tilausrivi['nimitys']);
    $kpl          = sprintf("%-14.14s", "{$tilausrivi['kpl']} {$tilausrivi['yksikko']}");

    $kuitin_teksti .= "\n{$tuoteno}\n";
    $kuitin_teksti .= "{$nimitys}\n";
    $kuitin_teksti .= "{$kpl}";
    $kuitin_teksti .= "{$kappalehinta}";
    $kuitin_teksti .= "{$bruttohinta}\n";
  }

  // Hinta yhteensä
  $kuitin_teksti .= "==========================================\n";
  $kuitin_teksti .= "{$emphasized_on}YHTEENSÄ{$yhteensa}{$emphasized_off}\n\n";

  // Verotiedot
  $alv    = sprintf("%-12.12s", "{$laskut[0]["alv"]} %");
  $netto  = sprintf("%-12.12s", $netto);
  $vero   = sprintf("%-9.9s", $vero);
  $brutto = sprintf("%9.9s", $brutto);

  $kuitin_teksti .= "ALV         NETTO        VERO       BRUTTO\n";
  $kuitin_teksti .= "{$alv}{$netto}{$vero}{$brutto}\n\n";

  // Maksupäätetapahtumat
  foreach ($laskut as $lasku) {
    if ($lasku['asiakkaan_kuitti']) {
      $kuitin_teksti .= "{$lasku['asiakkaan_kuitti']}\n\n";
    }
  }

  $kuitin_teksti .= "\n\n\n\n";
  $kuitin_teksti .= $katkaisu;

  lpr($kuitin_teksti, $tulostimen_tunnus);
}

function hae_laskut($laskunro) {
  global $yhtiorow;
  $query  = "SELECT *
             FROM lasku
             LEFT JOIN maksupaatetapahtumat ON (lasku.tunnus = maksupaatetapahtumat.tilausnumero
               AND maksupaatetapahtumat.yhtio = '{$yhtiorow['yhtio']}')
             WHERE lasku.yhtio  = '{$yhtiorow['yhtio']}'
             AND lasku.laskunro = '{$laskunro}'";
  $result = pupe_query($query);

  $tapahtumat = array();

  while ($tapahtuma = mysql_fetch_assoc($result)) {
    array_push($tapahtumat, $tapahtuma);
  }

  return $tapahtumat;
}

function hae_tilausrivit($laskunro) {
  global $yhtiorow;

  $query  = "SELECT *
             FROM tilausrivi
             INNER JOIN lasku ON (lasku.tunnus = tilausrivi.otunnus
               AND lasku.yhtio = '{$yhtiorow['yhtio']}')
             WHERE lasku.laskunro = '{$laskunro}'
             AND tilausrivi.yhtio = '{$yhtiorow['yhtio']}'";
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
    hintapyoristys($brutto, 1),
    hintapyoristys($vero)
  );
}
