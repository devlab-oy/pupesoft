<?php

/**
 * Tulostaa asiakkaan kuitin lämpösiirtotulostimelle. Kuitille tulostuu logo, yhtiön tiedot, laskun
 * tilausrivit, laskun summa, verotiedot ja luottokorttitapahtumat
 *
 * @param  int  $laskunro
 * @param  int  $tulostimen_tunnus
 * @param array $params
 *  Esimerkki config parametreistä arvoina defaultit:
 *    array(
 *      "header_teksti"     => false,
 *      "footer_teksti"     => false,
 *      "avaa_lipas_lopuksi => false
 *    );
 */
function tulosta_asiakkaan_kuitti($laskunro, $tulostimen_tunnus, $params = array()) {
  global $yhtiorow;

  $footer_teksti      = empty($params["footer_teksti"]) ? false : $params["footer_teksti"];
  $header_teksti      = empty($params["header_teksti"]) ? false : $params["header_teksti"];
  $avaa_lipas_lopuksi = $params["avaa_lipas_lopuksi"];

  $lasku                = hae_myyntilasku($laskunro);
  $maksupaatetapahtumat = hae_maksupaatetapahtumat($laskunro);
  list($tilausrivit, $tilausnumerot, $verot, $yhteensa) = hae_tilausrivit($laskunro);

  // Escape sequencejä printterille
  $logo           = "\x1C\x70\x01\x10";
  $katkaisu       = "\x1B\x69";
  $align_left     = "\x1B\x61\x00";
  $align_center   = "\x1B\x61\x01";
  $emphasized_on  = "\x1B\x45\x01";
  $emphasized_off = "\x1B\x45\x00";
  $avaa_lipas     = "\x1B\x70\x00\n\n";

  // Yhtiön ja tapahtuman tiedot
  $yhtion_nimi          = $lasku['yhtio_nimi'];
  $yhtion_puhelinnumero = $yhtiorow['puhelin'];
  $yhtion_osoite        = $lasku['yhtio_osoite'];
  $yhtion_postino       = $lasku['yhtio_postino'];
  $yhtion_postitp       = $lasku['yhtio_postitp'];
  $yhtion_ytunnus       = str_replace("0037", "", $lasku["yhtio_ovttunnus"]);
  $yhtion_ytunnus       = tulosta_ytunnus($yhtion_ytunnus, $lasku['yhtio_maa']);
  $myyjan_nimi          = $lasku['myyjan_nimi'];

  // Arvojen asettelu spritfillä ja muu asettelu
  $yhteensa = sprintf("%34.34s", $yhteensa);

  $verorivit = array();

  foreach ($verot as $alvi => $alvirivi) {
    $verorivit[$alvi]['veroprosentti'] = sprintf("%-12.12s", "{$alvi} %");
    $verorivit[$alvi]['netto']         = sprintf("%-12.12s", $alvirivi['netto']);
    $verorivit[$alvi]['vero']          = sprintf("%-9.9s", $alvirivi['vero']);
    $verorivit[$alvi]['brutto']        = sprintf("%9.9s", $alvirivi['brutto']);
  }

  $tilausnumero_teksti = count($tilausnumerot) > 1 ? "Tilausnumerot" : "Tilausnumero";

  // Printattavan stringin luominen
  $kuitin_teksti = "{$logo}\n\n";

  $kuitin_teksti .= "{$align_center}";

  $kuitin_teksti .= "{$yhtion_nimi}\n";
  $kuitin_teksti .= "{$yhtion_osoite}\n";
  $kuitin_teksti .= "{$yhtion_postino} {$yhtion_postitp}\n";
  $kuitin_teksti .= "Y-tunnus: {$yhtion_ytunnus}\n";
  $kuitin_teksti .= "Puh. {$yhtion_puhelinnumero}";

  if ($header_teksti) {
    $kuitin_teksti .= "\n{$header_teksti}";
  }

  $kuitin_teksti .= "\n\n{$align_left}";

  foreach ($tilausrivit as $tilausrivi) {
    $kappalehinta = $tilausrivi['rivihinta_valuutassa'] / $tilausrivi['kpl'];
    $kappalehinta = $kappalehinta * (1 + $tilausrivi['alv'] / 100);
    $kappalehinta = hintapyoristys($kappalehinta, 1);
    $kappalehinta = sprintf("%14.14s", "a {$kappalehinta}");
    $bruttohinta  = $tilausrivi['rivihinta'] * (1 + $tilausrivi['alv'] / 100);
    $bruttohinta  = hintapyoristys($bruttohinta, 1);
    $bruttohinta  = sprintf("%14.14s", $bruttohinta);
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
  $kuitin_teksti .= "ALV         NETTO        VERO       BRUTTO\n";

  foreach ($verorivit as $verorivi) {
    $kuitin_teksti .= "{$verorivi['veroprosentti']}" .
      "{$verorivi['netto']}" .
      "{$verorivi['vero']}" .
      "{$verorivi['brutto']}\n";
  }

  $kuitin_teksti .= "\n";

  // Maksupäätetapahtumat
  foreach ($maksupaatetapahtumat as $tapahtuma) {
    if ($tapahtuma['asiakkaan_kuitti']) {
      $kuitin_teksti .= "{$tapahtuma['asiakkaan_kuitti']}\n";
    }
  }

  // Footer
  $kuitin_teksti .= "{$align_center}";
  $kuitin_teksti .= t("Kuittinumero") . ": {$lasku['laskunro']}\n\n";

  $kuitin_teksti .= t($tilausnumero_teksti) . ": " . implode(", ", $tilausnumerot) . "\n\n\n";

  $kuitin_teksti .= "{$emphasized_on}";
  $kuitin_teksti .= "{$yhtiorow['www']}\n\n";
  $kuitin_teksti .= "{$emphasized_off}";

  if ($footer_teksti) {
    $kuitin_teksti .= $footer_teksti;
  }

  $kuitin_teksti .= "\n\n";

  if ($myyjan_nimi) {
    $kuitin_teksti .= t("Teitä palveli") . " {$myyjan_nimi}";
  }

  $kuitin_teksti .= "\n\n\n\n\n\n";
  $kuitin_teksti .= $katkaisu;

  if ($avaa_lipas_lopuksi) {
    $kuitin_teksti .= $avaa_lipas;
  }

  lpr($kuitin_teksti, $tulostimen_tunnus);
}

function hae_myyntilasku($laskunro) {
  global $yhtiorow;

  $query = "SELECT lasku.yhtio_nimi,
            lasku.yhtio_osoite,
            lasku.yhtio_postino,
            lasku.yhtio_postitp,
            lasku.yhtio_ovttunnus,
            lasku.yhtio_maa,
            lasku.laskunro,
            kuka.nimi AS myyjan_nimi
            FROM lasku
            LEFT JOIN kuka ON (lasku.myyja = kuka.tunnus
              AND kuka.yhtio = '{$yhtiorow['yhtio']}')
            WHERE lasku.yhtio  = '{$yhtiorow['yhtio']}'
            AND lasku.tila     = 'U'
            AND lasku.alatila  = 'X'
            AND lasku.laskunro = '{$laskunro}'";

  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_maksupaatetapahtumat($laskunro) {
  global $kukarow;

  $query = "SELECT maksupaatetapahtumat.*
            FROM lasku
            INNER JOIN maksupaatetapahtumat ON (maksupaatetapahtumat.yhtio = lasku.yhtio
              AND maksupaatetapahtumat.tilausnumero = lasku.tunnus)
            WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
            AND lasku.laskunro = '{$laskunro}'";

  $result = pupe_query($query);

  $tapahtumat = array();

  while ($tapahtuma = mysql_fetch_assoc($result)) {
    array_push($tapahtumat, $tapahtuma);
  }

  return $tapahtumat;
}

function hae_tilausrivit($laskunro) {
  global $kukarow;

  $query = "SELECT tilausrivi.rivihinta_valuutassa,
            tilausrivi.kpl,
            tilausrivi.alv,
            tilausrivi.rivihinta,
            tilausrivi.tuoteno,
            tilausrivi.nimitys,
            tilausrivi.yksikko,
            tilausrivi.otunnus
            FROM lasku
            INNER JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus)
            WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
            AND lasku.tila     = 'L'
            AND lasku.alatila  = 'X'
            AND lasku.laskunro = '{$laskunro}'";

  $result = pupe_query($query);

  $tilausrivit       = array();
  $tilausnumerot     = array();
  $alvirivit         = array();
  $pyoristetyt_verot = array();
  $hinta_yhteensa    = 0;

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($tilausrivit, $rivi);
    array_push($tilausnumerot, $rivi['otunnus']);

    $alvirivit[$rivi['alv']]['netto'] += $rivi['rivihinta'];
    $alvirivit[$rivi['alv']]['vero'] += $rivi['rivihinta'] * ($rivi['alv'] / 100);
    $alvirivit[$rivi['alv']]['brutto'] += $rivi['rivihinta'] * (1 + $rivi['alv'] / 100);

    $hinta_yhteensa += $rivi['rivihinta'] * (1 + $rivi['alv'] / 100);
  }

  foreach ($alvirivit as $alvi => $alvirivi) {
    if ($alvirivi['netto'] != 0) {
      $pyoristetyt_verot[$alvi]['netto']  = hintapyoristys($alvirivi['netto']);
      $pyoristetyt_verot[$alvi]['vero']   = hintapyoristys($alvirivi['vero']);
      $pyoristetyt_verot[$alvi]['brutto'] = hintapyoristys($alvirivi['brutto'], 1);
    }
  }

  return array(
    $tilausrivit,
    array_unique($tilausnumerot),
    $pyoristetyt_verot,
    hintapyoristys($hinta_yhteensa, 1)
  );
}
