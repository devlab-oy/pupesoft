<?php

/**
 * Tulostaa asiakkaan kuitin lämpösiirtotulostimelle. Kuitille tulostuu logo, yhtiön tiedot, laskun
 * tilausrivit, laskun summa, verotiedot ja luottokorttitapahtumat
 *
 * @param int     $laskunro
 * @param int     $tulostimen_tunnus
 * @param array   $params
 *  Esimerkki config parametreistä arvoina defaultit:
 *    array(
 *      "header_teksti"      => false,
 *      "footer_teksti"      => false,
 *      "avaa_lipas_lopuksi" => true,
 *      "kateinen"           => false
 *    );
 *
 * @return bool
 */


function tulosta_asiakkaan_kuitti($laskunro, $tulostimen_tunnus, $params = array()) {
  global $yhtiorow;

  if (tulostimen_tyyppi($tulostimen_tunnus) != "kuittitulostin" or empty($laskunro)) {
    return false;
  }

  $footer_teksti      = empty($params["footer_teksti"]) ? false : $params["footer_teksti"];
  $header_teksti      = empty($params["header_teksti"]) ? false : $params["header_teksti"];
  $kateinen           = empty($params["kateinen"]) ? false : hintapyoristys($params["kateinen"]);
  $avaa_lipas_lopuksi = empty($params["avaa_lipas_lopuksi"]) ? true : $params["avaa_lipas_lopuksi"];

  list($maksupaatetapahtumat, $maksupaatesumma) = hae_maksupaatetapahtumat($laskunro);
  list($tilausrivit, $tilausnumerot, $verot) = hae_tilausrivit($laskunro);
  $lasku = hae_myyntilasku($laskunro);
  list($yhteensa, $pyoristys, $loppusumma) = summat($lasku);

  if ($kateinen) {
    $takaisin = $kateinen - $loppusumma + $maksupaatesumma;
    $takaisin = hintapyoristys($takaisin);
  }

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
  $asiakkaan_kieli      = $lasku["asiakkaan_kieli"];

  // Arvojen asettelu spritfillä ja muu asettelu
  $yhteensa_left = 32 - strlen($lasku["valkoodi"]);
  $yhteensa = sprintf("%{$yhteensa_left}.{$yhteensa_left}s", $yhteensa);

  if ($pyoristys) {
    $pyoristys  = sprintf("%33.33s", $pyoristys);
    $loppusumma = sprintf("%32.32s", $loppusumma);
  }

  if ($maksupaatesumma != 0) {
    $maksupaatesumma = sprintf("%36.36s", $maksupaatesumma);
  }

  if ($kateinen != 0) {
    $kateinen = sprintf("%34.34s", $kateinen);

    if (isset($takaisin)) {
      $takaisin = sprintf("%34.34s", $takaisin);
    }
  }

  $verorivit = array();

  foreach ($verot as $alvi => $alvirivi) {
    $verorivit[$alvi]['veroprosentti'] = sprintf("%-12.12s", "{$alvi} %");
    $verorivit[$alvi]['netto']         = sprintf("%-12.12s", $alvirivi['netto']);
    $verorivit[$alvi]['vero']          = sprintf("%-9.9s", $alvirivi['vero']);
    $verorivit[$alvi]['brutto']        = sprintf("%9.9s", $alvirivi['brutto']);
  }

  $tilausnumero_teksti = count($tilausnumerot) > 1 ? "Tilausnumerot" : "Tilausnumero";

  // Printattavan stringin luominen
  $kuitin_teksti = "";

  if ($avaa_lipas_lopuksi) {
    $kuitin_teksti .= $avaa_lipas;
  }

  $kuitin_teksti .= "{$logo}\n\n";

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
    $kappalehinta = hintapyoristys($tilausrivi["yksikkohinta"]);
    $kappalehinta = sprintf("%14.14s", "a {$kappalehinta}");
    $bruttohinta  = $tilausrivi['rivihinta_verollinen'];
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
  $kuitin_teksti .= "{$emphasized_on}YHTEENSÄ, {$lasku["valkoodi"]}{$yhteensa}{$emphasized_off}\n";

  if ($pyoristys) {
    $kuitin_teksti .= "{$emphasized_on}PYÖRISTYS{$pyoristys}{$emphasized_off}\n";
    $kuitin_teksti .= "{$emphasized_on}LOPPUSUMMA{$loppusumma}{$emphasized_off}\n";
  }

  // Maksutapaerottelu
  if ($maksupaatesumma != 0) {
    $kuitin_teksti .= t("KORTTI") . "{$maksupaatesumma}\n";
  }

  if ($kateinen != 0) {
    $kuitin_teksti .= t("KÄTEINEN") . "{$kateinen}\n";

    if (isset($takaisin)) {
      $kuitin_teksti .= t("TAKAISIN") . "{$takaisin}\n";
    }
  }

  // Verotiedot
  $kuitin_teksti .= "\nALV         NETTO        VERO       BRUTTO\n";

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
  $kuitin_teksti .= t("Kuittinumero", $asiakkaan_kieli) . ": {$lasku['laskunro']}\n\n";

  $kuitin_teksti .= t($tilausnumero_teksti, $asiakkaan_kieli) .
    ": " .
    implode(", ", $tilausnumerot) .
    "\n\n";

  $kuitin_teksti .= date("d.m.Y H:i:s");

  $kuitin_teksti .= "\n\n\n";

  $kuitin_teksti .= "{$emphasized_on}";
  $kuitin_teksti .= "{$yhtiorow['www']}\n\n";
  $kuitin_teksti .= "{$emphasized_off}";

  if ($footer_teksti) {
    $kuitin_teksti .= $footer_teksti;
  }

  $kuitin_teksti .= "\n\n";

  if ($myyjan_nimi) {
    $kuitin_teksti .= t("Teitä palveli", $asiakkaan_kieli) . " {$myyjan_nimi}";
  }

  $kuitin_teksti .= "\n\n\n\n\n\n";
  $kuitin_teksti .= $katkaisu;

  lpr($kuitin_teksti, $tulostimen_tunnus);

  return true;
}

function hae_myyntilasku($laskunro) {
  global $kukarow;

  $query = "SELECT lasku.yhtio_nimi,
            lasku.yhtio_osoite,
            lasku.yhtio_postino,
            lasku.yhtio_postitp,
            lasku.yhtio_ovttunnus,
            lasku.yhtio_maa,
            lasku.laskunro,
            lasku.valkoodi,
            lasku.summa_valuutassa,
            lasku.pyoristys_valuutassa,
            lasku.pyoristys,
            kuka.nimi AS myyjan_nimi,
            asiakas.kieli AS asiakkaan_kieli
            FROM lasku
            LEFT JOIN kuka ON (kuka.tunnus = lasku.myyja
              AND kuka.yhtio     = lasku.yhtio)
            LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus = lasku.liitostunnus)
            WHERE lasku.yhtio    = '{$kukarow['yhtio']}'
            AND lasku.tila       = 'U'
            AND lasku.alatila    = 'X'
            AND lasku.laskunro   = '{$laskunro}'";

  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_maksupaatetapahtumat($laskunro) {
  global $kukarow;

  $query = "SELECT maksupaatetapahtumat.*
            FROM lasku
            INNER JOIN maksupaatetapahtumat ON (maksupaatetapahtumat.yhtio = lasku.yhtio
              AND maksupaatetapahtumat.tilausnumero = lasku.tunnus)
            WHERE lasku.yhtio                       = '{$kukarow['yhtio']}'
            AND lasku.laskunro                      = '{$laskunro}'";

  $result = pupe_query($query);

  $tapahtumat = array();
  $summa      = 0;

  while ($tapahtuma = mysql_fetch_assoc($result)) {
    array_push($tapahtumat, $tapahtuma);
    $summa += $tapahtuma["summa_valuutassa"];
  }

  return array($tapahtumat, hintapyoristys($summa));
}

function hae_tilausrivit($laskunro) {
  global $kukarow, $yhtiorow;

  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT tilausrivi.rivihinta_valuutassa,
            tilausrivi.hinta,
            tilausrivi.kpl,
            tilausrivi.alv,
            tilausrivi.rivihinta,
            round(tilausrivi.hinta * if ('{$yhtiorow['alv_kasittely']}' != '' and
              tilausrivi.alv         < 500,(1+tilausrivi.alv/100), 1) *
              (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},{$yhtiorow['hintapyoristys']})
              AS rivihinta_verollinen,
            tilausrivi.tuoteno,
            tilausrivi.nimitys,
            tilausrivi.yksikko,
            tilausrivi.otunnus
            FROM lasku
            INNER JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus)
            WHERE lasku.yhtio        = '{$kukarow['yhtio']}'
            AND lasku.tila           = 'L'
            AND lasku.alatila        = 'X'
            AND lasku.laskunro       = '{$laskunro}'";

  $result = pupe_query($query);

  $tilausrivit       = array();
  $tilausnumerot     = array();
  $alvirivit         = array();
  $pyoristetyt_verot = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    $rivi["yksikkohinta"] = laske_yksikkohinta($rivi);

    array_push($tilausrivit, $rivi);
    array_push($tilausnumerot, $rivi['otunnus']);

    $alvirivit[$rivi['alv']]['netto'] += $rivi['rivihinta'];
    $alvirivit[$rivi['alv']]['vero'] += $rivi['rivihinta'] * ($rivi['alv'] / 100);
    $alvirivit[$rivi['alv']]['brutto'] += $rivi['rivihinta_verollinen'];
  }

  foreach ($alvirivit as $alvi => $alvirivi) {
    if ($alvirivi['netto'] != 0) {
      $pyoristetyt_verot[$alvi]['netto']  = hintapyoristys($alvirivi['netto']);
      $pyoristetyt_verot[$alvi]['vero']   = hintapyoristys($alvirivi['vero']);
      $pyoristetyt_verot[$alvi]['brutto'] = hintapyoristys($alvirivi['brutto']);
    }
  }

  return array(
    $tilausrivit,
    array_unique($tilausnumerot),
    $pyoristetyt_verot,
  );
}

function summat($lasku) {
  $summa_valuutassa     = $lasku["summa_valuutassa"];
  $pyoristys_valuutassa = $lasku["pyoristys_valuutassa"];
  $pyoristys            = $lasku["pyoristys"];
  $valkoodi             = $lasku["valkoodi"];
  $summa                = $lasku["summa"];

  $ehto1 = $valkoodi != '';
  $ehto2 = trim(strtoupper($valkoodi)) != trim(strtoupper($yhtiorow["valkoodi"]));

  if ($ehto1 and $ehto2) {
    if ($kassa_ale != '') {
      $yhteensa = ($summa_valuutassa + $pyoristys_valuutassa) * (1 - $kassa_ale / 100);
    }
    else {
      $yhteensa = $summa_valuutassa + $pyoristys_valuutassa;
    }

    $yhteensa = hintapyoristys($yhteensa);

    if ($pyoristys_valuutassa != 0) {

      if ($kassa_ale != '') {
        $lopullinen_pyoristys = $pyoristys_valuutassa * (1 - $kassa_ale / 100);
      }
      else {
        $lopullinen_pyoristys = $pyoristys_valuutassa;
      }

      $lopullinen_pyoristys *= -1;
      $lopullinen_pyoristys = hintapyoristys($lopullinen_pyoristys);
    }
  }
  else {
    if ($kassa_ale != '') {
      $yhteensa = ($summa + $pyoristys) * (1 - $kassa_ale / 100);
    }
    else {
      $yhteensa = $summa + $pyoristys;
    }

    $yhteensa = hintapyoristys($yhteensa);

    if ($pyoristys != 0) {
      $lopullinen_pyoristys = $kassa_ale != '' ? $pyoristys * (1 - $kassa_ale / 100) : $pyoristys;
      $lopullinen_pyoristys = hintapyoristys($lopullinen_pyoristys);
    }
  }

  if ($valkoodi != '' and trim(strtoupper($valkoodi)) != trim(strtoupper($yhtiorow["valkoodi"]))) {
    $loppusumma = $summa_valuutassa;
  }
  else {
    $loppusumma = $summa;
  }

  return array($yhteensa, $lopullinen_pyoristys, $loppusumma);
}

function laske_yksikkohinta($tilausrivi) {
  return $tilausrivi["hinta"];
}

function tulosta_kauppiaan_kuitti($teksti, $tulostimen_tunnus) {
  lpr("{$teksti}\n\n\n\n\n\n\x1B\x69", $tulostimen_tunnus);
}

function tulostimen_tyyppi($tulostimen_tunnus) {
  global $kukarow;

  $query = "SELECT mediatyyppi
            FROM kirjoittimet
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tulostimen_tunnus}'";

  $result = pupe_query($query);
  $result = mysql_fetch_assoc($result);

  return $result["mediatyyppi"];
}
