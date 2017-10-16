<?php

/**
 * Tulostaa asiakkaan kuitin lämpösiirtotulostimelle. Kuitille tulostuu logo, yhtiön tiedot, laskun
 * tilausrivit, laskun summa, verotiedot ja luottokorttitapahtumat
 *
 * @param int     $laskunro
 * @param int     $tulostimen_tunnus
 * @param array   $params
 *
 *        Esimerkki config parametreistä arvoina defaultit:
 *        array(
 *          "header_teksti"      => false,
 *          "footer_teksti"      => false,
 *          "avaa_lipas_lopuksi" => true,
 *          "kateinen"           => 0
 *        );
 *
 * @return bool
 */

function draw_center_aligned($pdf, $firstpage, $text, $font, $y) {
  $lpos = (204-$pdf->strlen($text, $font)) / 2;
  $pdf->draw_text($lpos, $y, $text, $firstpage, $font);
}

function tulosta_asiakkaan_kuitti($laskunro, $tulostimen_tunnus, $params = array()) {
  global $kukarow, $yhtiorow, $pupe_root_polku, $palvelin2;

  if ((tulostimen_tyyppi($tulostimen_tunnus) != "kuittitulostin" and empty($params['pdf_kuitti'])) or empty($laskunro)) {
    return false;
  }

  if (isset($params["footer_teksti"])) {
    $footer_teksti = $params["footer_teksti"];
  }
  elseif ($teksti_avainsanasta = t_avainsana("KUITIN_VAKTEXT", '', '', '', '', "selitetark")) {
    $footer_teksti = $teksti_avainsanasta;
  }
  else {
    $footer_teksti = false;
  }

  $header_teksti      = empty($params["header_teksti"]) ? false : $params["header_teksti"];
  $kateinen           = empty($params["kateinen"]) ? 0 : hintapyoristys($params["kateinen"]);
  $avaa_lipas_lopuksi = $params["avaa_lipas_lopuksi"] !== false;

  list($maksupaatetapahtumat, $maksupaatesumma) = hae_maksupaatetapahtumat($laskunro);
  list($tilausrivit, $tilausnumerot, $verot) = hae_tilausrivit($laskunro);

  $lasku = hae_myyntilasku($laskunro);

  list($yhteensa, $pyoristys, $loppusumma) = summat($lasku);
  $yhteensa_num        = $yhteensa;
  $maksupaatesumma_num = $maksupaatesumma;

  if ($kateinen != 0) {
    $takaisin = $kateinen - $loppusumma + $maksupaatesumma;
    $takaisin = hintapyoristys($takaisin);
  }

  // Yhtiön ja tapahtuman tiedot
  $yhtion_nimi          = $lasku['yhtio_nimi'];
  $yhtion_puhelinnumero = $yhtiorow['puhelin'];
  $yhtion_osoite        = $lasku['yhtio_osoite'];
  $yhtion_postino       = $lasku['yhtio_postino'];
  $yhtion_postitp       = $lasku['yhtio_postitp'];
  $yhtion_ytunnus       = preg_replace("/^0037/", "", $lasku["yhtio_ovttunnus"]);
  $yhtion_ytunnus       = tulosta_ytunnus($yhtion_ytunnus, $lasku['yhtio_maa']);
  $myyjan_nimi          = $lasku['myyjan_nimi'];
  $asiakkaan_kieli      = $lasku["asiakkaan_kieli"];

  // Arvojen asettelu spritfillä ja muu asettelu
  $yhteensa_left = 32 - strlen($lasku["valkoodi"]);
  $yhteensa      = sprintf("%{$yhteensa_left}.{$yhteensa_left}s", $yhteensa);

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
  elseif ($maksupaatesumma_num < $yhteensa_num) {
    $kateinen = sprintf("%34.34s", sprintf("%.2f", $yhteensa_num - $maksupaatesumma_num));
  }

  $verorivit = array();

  foreach ($verot as $alvi => $alvirivi) {
    $verorivit[$alvi]['veroprosentti'] = sprintf("%-12.12s", "{$alvi} %");
    $verorivit[$alvi]['netto']         = sprintf("%-12.12s", $alvirivi['netto']);
    $verorivit[$alvi]['vero']          = sprintf("%-9.9s", $alvirivi['vero']);
    $verorivit[$alvi]['brutto']        = sprintf("%9.9s", $alvirivi['brutto']);
  }

  $tilausnumero_teksti = count($tilausnumerot) > 1 ? "Tilausnumerot" : "Tilausnumero";

  if (!empty($params['pdf_kuitti'])) {
    require_once 'pdflib/phppdflib.class.php';

    $norm["height"] = 10;
    $norm["font"] = "Helvetica";

    $pieni["height"] = 8;
    $pieni["font"] = "Helvetica";

    $pdf = new pdffile;
    $pdf->set_default('margin-top', 0);
    $pdf->set_default('margin-bottom', 0);
    $pdf->set_default('margin-left', 0);
    $pdf->set_default('margin-right', 0);

    $korkeus = "404";

    $korkeus += count($tilausrivit) * 30;

    $firstpage = $pdf->new_page("204x$korkeus");

    tulosta_logo_pdf($pdf, $firstpage, "", $korkeus-10, 42, 40, 120);

    $kala = $korkeus-60;

    draw_center_aligned($pdf, $firstpage, "{$yhtion_nimi}", $norm, $kala);
    $kala-=10;

    draw_center_aligned($pdf, $firstpage, "{$yhtion_osoite}", $norm, $kala);
    $kala-=10;

    draw_center_aligned($pdf, $firstpage, "{$yhtion_postino} {$yhtion_postitp}", $norm, $kala);
    $kala-=10;

    draw_center_aligned($pdf, $firstpage, "Y-tunnus: {$yhtion_ytunnus}", $norm, $kala);
    $kala-=10;

    draw_center_aligned($pdf, $firstpage, "Puh. {$yhtion_puhelinnumero}", $norm, $kala);
    $kala-=10;

    if ($header_teksti) {
      draw_center_aligned($pdf, $firstpage, "{$header_teksti}", $norm, $kala);
      $kala-=10;
    }

    $kala-=30;

    foreach ($tilausrivit as $tilausrivi) {
      $pdf->draw_text(0, $kala, $tilausrivi['tuoteno'], $firstpage, $pieni);
      $kala-=10;

      $pdf->draw_text(0, $kala, $tilausrivi['nimitys'], $firstpage, $norm);
      $kala-=10;

      $pdf->draw_text(0, $kala, "{$tilausrivi['kpl']} {$tilausrivi['yksikko']}", $firstpage, $norm);

      $pdf->draw_text(60, $kala, "á ".hintapyoristys($tilausrivi["yksikkohinta"]), $firstpage, $norm);

      if ((float) $tilausrivi['aleyhteensa']) {
        $pdf->draw_text(110, $kala, "-".(float) $tilausrivi['aleyhteensa']."%", $firstpage, $norm);
      }

      $oikpos = $pdf->strlen($tilausrivi['rivihinta_verollinen'], $norm);
      $pdf->draw_text(200-$oikpos, $kala, $tilausrivi['rivihinta_verollinen'], $firstpage, $norm);
      $kala-=15;
    }

    // Hinta yhteensä
    $pdf->draw_text(0, $kala, "====================================", $firstpage, $norm);
    $kala-=10;

    $pdf->draw_text(0, $kala, "YHTEENSÄ, {$lasku["valkoodi"]}", $firstpage, $norm);

    $oikpos = $pdf->strlen(trim($yhteensa), $norm);
    $pdf->draw_text(200-$oikpos, $kala, trim($yhteensa), $firstpage, $norm);
    $kala-=10;

    if ($pyoristys) {
      $pdf->draw_text(0, $kala, "PYÖRISTYS", $firstpage, $norm);
      $oikpos = $pdf->strlen(trim($pyoristys), $norm);
      $pdf->draw_text(200-$oikpos, $kala, trim($pyoristys), $firstpage, $norm);
      $kala-=10;

      $pdf->draw_text(0, $kala, "LOPPUSUMMA", $firstpage, $norm);
      $oikpos = $pdf->strlen(trim($loppusumma), $norm);
      $pdf->draw_text(200-$oikpos, $kala, trim($loppusumma), $firstpage, $norm);
      $kala-=10;
    }

    // Maksutapaerottelu
    if ($maksupaatesumma != 0) {
      $pdf->draw_text(0, $kala, t("KORTTI"), $firstpage, $norm);
      $oikpos = $pdf->strlen(trim($maksupaatesumma), $norm);
      $pdf->draw_text(200-$oikpos, $kala, trim($maksupaatesumma), $firstpage, $norm);
      $kala-=10;
    }

    if ($kateinen != 0) {

      // maksupääteintegraatio ei käytössä, katsotaan tarkat maksutavat tiliöinneistä
      if ($maksupaatesumma == 0) {

          $maksutavat_ja_summat = hae_maksutavat_ja_summat($lasku);

          if ($maksutavat_ja_summat[0] != 0) {
            $_summa = sprintf("%34.34s", $maksutavat_ja_summat[0]);
            $pdf->draw_text(0, $kala, t("KÄTEINEN"), $firstpage, $norm);
            $oikpos = $pdf->strlen(trim($_summa), $norm);
            $pdf->draw_text(200-$oikpos, $kala, trim($_summa), $firstpage, $norm);
            $kala-=10;
          }

          if ($maksutavat_ja_summat[1] != 0) {
            $_summa = sprintf("%34.34s", $maksutavat_ja_summat[1]);
            $pdf->draw_text(0, $kala, t("LUOTTOKORTTI"), $firstpage, $norm);
            $oikpos = $pdf->strlen(trim($_summa), $norm);
            $pdf->draw_text(200-$oikpos, $kala, trim($_summa), $firstpage, $norm);
            $kala-=10;
          }

          if ($maksutavat_ja_summat[2] != 0) {
            $_summa = sprintf("%34.34s", $maksutavat_ja_summat[2]);
            $pdf->draw_text(0, $kala, t("PANKKIKORTTI"), $firstpage, $norm);
            $oikpos = $pdf->strlen(trim($_summa), $norm);
            $pdf->draw_text(200-$oikpos, $kala, trim($_summa), $firstpage, $norm);
            $kala-=10;
          }

      }
      else {
        $pdf->draw_text(0, $kala, t("KÄTEINEN"), $firstpage, $norm);
        $oikpos = $pdf->strlen(trim($kateinen), $norm);
        $pdf->draw_text(200-$oikpos, $kala, trim($kateinen), $firstpage, $norm);
        $kala-=10;
      }

      if (isset($takaisin)) {
        $pdf->draw_text(0, $kala, t("TAKAISIN"), $firstpage, $norm);
        $oikpos = $pdf->strlen(trim($takaisin), $norm);
        $pdf->draw_text(200-$oikpos, $kala, trim($takaisin), $firstpage, $norm);
        $kala-=10;
      }
    }

    $kala-=20;

    // Verotiedot
    $pdf->draw_text(0, $kala, t("ALV") . "{$takaisin}", $firstpage, $norm);
    $pdf->draw_text(60, $kala, t("NETTO") . "{$takaisin}", $firstpage, $norm);
    $pdf->draw_text(110, $kala, t("VERO") . "{$takaisin}", $firstpage, $norm);
    $pdf->draw_text(160, $kala, t("BRUTTO") . "{$takaisin}", $firstpage, $norm);
    $kala-=10;

    foreach ($verorivit as $verorivi) {
      $pdf->draw_text(0, $kala, trim($verorivi['veroprosentti']), $firstpage, $norm);
      $pdf->draw_text(60, $kala, trim($verorivi['netto']), $firstpage, $norm);
      $pdf->draw_text(110, $kala, trim($verorivi['vero']), $firstpage, $norm);

      $oikpos = $pdf->strlen(trim($verorivi['brutto']), $norm);
      $pdf->draw_text(200-$oikpos, $kala, trim($verorivi['brutto']), $firstpage, $norm);
      $kala-=10;
    }

    $kala-=20;

    // Maksupäätetapahtumat
    foreach ($maksupaatetapahtumat as $tapahtuma) {
      if ($tapahtuma['asiakkaan_kuitti']) {
        $pdf->draw_text(0, $kala, "{$tapahtuma['asiakkaan_kuitti']}", $firstpage, $norm);
        $kala-=10;
      }
    }

    // Footer
    draw_center_aligned($pdf, $firstpage, t("Kuittinumero", $asiakkaan_kieli).": {$lasku['laskunro']}", $norm, $kala);
    $kala-=15;

    draw_center_aligned($pdf, $firstpage, date("d.m.Y H:i:s"), $norm, $kala);
    $kala-=15;

    draw_center_aligned($pdf, $firstpage, "{$yhtiorow['www']}", $norm, $kala);
    $kala-=15;

    $comments = explode("\n", wordwrap($lasku["sisviesti1"], 40));

    foreach ($comments as $comment) {
      draw_center_aligned($pdf, $firstpage, $comment, $norm, $kala);
      $kala -= 10;
    }

    $kala-=10;

    if ($myyjan_nimi) {
      draw_center_aligned($pdf, $firstpage, t("Teitä palveli", $asiakkaan_kieli) . " {$myyjan_nimi}", $norm, $kala);
      $kala-=10;
    }

    if ($footer_teksti) {
      $footer_teksti = explode("\n", $footer_teksti);
      foreach ($footer_teksti as $footer_teksti_rivi) {
        $footer_teksti_rivi = explode("\n", wordwrap($footer_teksti_rivi, 40));
        foreach ($footer_teksti_rivi as $footer_teksti_subrivi) {
          draw_center_aligned($pdf, $firstpage, $footer_teksti_subrivi, $norm, $kala);
          $kala -= 10;
        }
      }
    }

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "{$pupe_root_polku}/dataout/Kuitti-{$lasku['laskunro']}.pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    if (!empty($params['pdf_kuitti_printdialog'])) {
      return "{$palvelin2}dataout/Kuitti-{$lasku['laskunro']}.pdf";
    }

    if (!empty($params['pdf_kuitti_ruudulle'])) {
      echo file_get_contents($pdffilenimi);
    }
  }
  else {

    // Escape sequencejä printterille
    $logo           = "\x1C\x70\x01\x10";
    $katkaisu       = "\x1B\x69";
    $align_left     = "\x1B\x61\x00";
    $align_center   = "\x1B\x61\x01";
    $emphasized_on  = "\x1B\x45\x01";
    $emphasized_off = "\x1B\x45\x00";
    $avaa_lipas     = "\x1B\x70\x00\n\n";

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
  }

  return true;
}

function hae_myyntilasku($laskunro) {
  global $kukarow;

  $query = "SELECT lasku.*,
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

function hae_maksutavat_ja_summat($lasku) {
  global $kukarow;

  if (empty($lasku['kassalipas'])) {
    return array(0,0,0);
  }

  $query = "SELECT kassa, luottokortti, pankkikortti
            FROM kassalipas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = {$lasku['kassalipas']}";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $query = "SELECT sum(summa) summa
            FROM tiliointi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ltunnus = {$lasku['tunnus']}
            AND tilino  = {$row['kassa']}
            AND korjattu = ''";
  $result = pupe_query($query);
  $kateis_row = mysql_fetch_assoc($result);

  $query = "SELECT sum(summa) summa
            FROM tiliointi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ltunnus = {$lasku['tunnus']}
            AND tilino  = {$row['luottokortti']}
            AND tilino not in ({$row['kassa']})
            AND korjattu = ''";
  $result = pupe_query($query);
  $luottokortti_row = mysql_fetch_assoc($result);

  $query = "SELECT sum(summa) summa
            FROM tiliointi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ltunnus = {$lasku['tunnus']}
            AND tilino  = {$row['pankkikortti']}
            AND tilino not in ({$row['kassa']}, {$row['luottokortti']})
            AND korjattu = ''";
  $result = pupe_query($query);
  $pankkikortti_row = mysql_fetch_assoc($result);

  return array($kateis_row['summa'], $luottokortti_row['summa'], $pankkikortti_row['summa']);
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
  $ale_query_select_lisa = generoi_alekentta_select('yhteen', 'M');

  $query = "SELECT tilausrivi.rivihinta_valuutassa,
            tilausrivi.hinta,
            tilausrivi.kpl,
            tilausrivi.alv,
            tilausrivi.rivihinta,
            $ale_query_select_lisa aleyhteensa,
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
            AND tunnus  = '{$tulostimen_tunnus}'";

  $result = pupe_query($query);
  $result = mysql_fetch_assoc($result);

  return $result["mediatyyppi"];
}

function receipt_dump($dumpattava) {
  $dumpattava = str_replace("\x1C\x70\x01\x10", "", $dumpattava);
  $dumpattava = str_replace("\x1B\x69", "", $dumpattava);
  $dumpattava = str_replace("\x1B\x61\x00", "", $dumpattava);
  $dumpattava = str_replace("\x1B\x61\x01", "", $dumpattava);
  $dumpattava = str_replace("\x1B\x45\x01", "", $dumpattava);
  $dumpattava = str_replace("\x1B\x45\x00", "", $dumpattava);
  $dumpattava = str_replace("\x1B\x70\x00\n\n", "", $dumpattava);

  $style = "background: white;" .
    "margin-left: auto;" .
    "margin-right: auto;" .
    "width: 333px;" .
    "padding: 20px;";

  echo "<pre style='{$style}'>" . htmlentities($dumpattava, ENT_IGNORE) . "</pre>";
}
