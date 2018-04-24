<?php

if (@!include_once 'pdflib/phppdflib.class.php') {
  include_once 'phppdflib.class.php';
}

require_once "barcode/barcode.php";
require_once "barcode/c128cobject.php";

if (!function_exists('lasku_fontit')) {
  function lasku_fontit($laskutyyppi) {
    $rectparam["width"] = 0.3;

    if ($laskutyyppi == 21 or $laskutyyppi == 22) {
      $normikoko = 9;
    }
    else {
      $normikoko = 10;
    }

    $norm["height"] = $normikoko;
    $norm["font"] = "Times-Roman";

    $norm_italic["height"] = $normikoko;
    $norm_italic["font"] = "Times-Italic";

    $pieni["height"] = 8;
    $pieni["font"] = "Times-Roman";

    $boldi["height"] = $normikoko;
    $boldi["font"] = "Times-Bold";

    $pieni_boldi["height"] = 8;
    $pieni_boldi["font"] = "Times-Bold";

    $iso["height"] = 14;
    $iso["font"] = "Helvetica-Bold";

    return array("rectparam" => $rectparam,
      "norm" => $norm,
      "norm_italic" => $norm_italic,
      "pieni" => $pieni,
      "boldi" => $boldi,
      "pieni_boldi" => $pieni_boldi,
      "iso" => $iso);
  }
}

if (!function_exists('alku')) {
  function alku($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu) {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    $page_lasku[$sivu] = $pdf_lasku->new_page("a4");

    tulosta_logo_pdf($pdf_lasku, $page_lasku[$sivu], $laskurow);

    if (!empty($laskurow['laskupohjateksti'])) {
      $redboldi["height"] = 8;
      $redboldi["font"] = "Times-Bold";
      $redboldi["fillcolor"] = $pdf_lasku->get_color("red");

      $pdf_lasku->draw_text(30, 832, $laskurow['laskupohjateksti'], $page_lasku[$sivu], $redboldi);
    }

    //Otsikko
    if ($laskurow["vienti"] == "") {
      if ($laskurow["summa"] >= 0) {
        if ($toim == 'PROFORMA') {
          $pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $page_lasku[$sivu], $iso);
        }
        elseif (kyseessa_eramaksu($laskurow)) {
          $pdf_lasku->draw_text(310, 815, t("Maksutosite", $kieli), $page_lasku[$sivu], $iso);
        }
        elseif ($laskurow["kateistyyppi"] != '') {
          $pdf_lasku->draw_text(310, 815, t("Kuitti", $kieli), $page_lasku[$sivu], $iso);
        }
        else {
          $pdf_lasku->draw_text(310, 815, t("Lasku", $kieli), $page_lasku[$sivu], $iso);
        }
      }
      elseif ($laskurow["kateistyyppi"] != '') {
        $pdf_lasku->draw_text(310, 815, t("Käteishyvitys", $kieli), $page_lasku[$sivu], $iso);
      }
      else {
        $pdf_lasku->draw_text(310, 815, t("Hyvityslasku", $kieli), $page_lasku[$sivu], $iso);
      }
    }
    else {
      if ($toim == 'PROFORMA') {
        $pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $page_lasku[$sivu], $iso);
      }
      elseif (kyseessa_eramaksu($laskurow)) {
        $pdf_lasku->draw_text(310, 815, t("Maksutosite", $kieli), $page_lasku[$sivu], $iso);
      }
      elseif ($laskurow["kateistyyppi"] != '') {
        $pdf_lasku->draw_text(310, 815, t("Kuitti", $kieli), $page_lasku[$sivu], $iso);
      }
      elseif ($laskurow["maa"] == "IN") {
        // Jos lasku menee Intiaan, niin laiitetaan hieman eri otsikko
        $pdf_lasku->draw_text(310, 815, "Commercial Invoice", $page_lasku[$sivu], $iso);
      }
      elseif ($laskurow["summa"] >= 0) {
        $pdf_lasku->draw_text(310, 815, t("Vientilasku", $kieli), $page_lasku[$sivu], $iso);
      }
      else {
        $pdf_lasku->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $page_lasku[$sivu], $iso);
      }
    }

    //Vasen sarake
    //$pdf_lasku->draw_rectangle(737, 20,  674, 300, $page_lasku[$sivu], $rectparam);
    $query = "SELECT laskutus_nimi,
              laskutus_nimitark,
              laskutus_osoite,
              laskutus_postino,
              laskutus_postitp,
              laskutus_maa
              FROM laskun_lisatiedot
              WHERE yhtio        = '{$kukarow['yhtio']}'
              AND laskutus_nimi != ''
              AND CONCAT(laskutus_nimi, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa) != '$laskurow[nimi]$laskurow[osoite]$laskurow[postino]$laskurow[postitp]$laskurow[maa]'
              AND otunnus        = '{$laskurow['tunnus']}'";
    $laskutusosoite_result = pupe_query($query);

    $osoitekenttia = 2;

    if (mysql_num_rows($laskutusosoite_result) > 0) {
      $laskutusosoite_row = mysql_fetch_assoc($laskutusosoite_result);

      $pdf_lasku->draw_text(50, 729, t("Laskutusosoite", $kieli), $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(50, 717, $laskutusosoite_row["laskutus_nimi"], $page_lasku[$sivu], $norm);
      $pdf_lasku->draw_text(50, 707, $laskutusosoite_row["laskutus_nimitark"], $page_lasku[$sivu], $norm);
      $pdf_lasku->draw_text(50, 697, $laskutusosoite_row["laskutus_osoite"], $page_lasku[$sivu], $norm);
      $pdf_lasku->draw_text(50, 687, $laskutusosoite_row["laskutus_postino"]." ".$laskutusosoite_row["laskutus_postitp"], $page_lasku[$sivu], $norm);

      if ($yhtiorow['maa'] != $laskutusosoite_row['laskutus_maa']) {
        $maan_tiedot = hae_maa(array('maakoodi' => $laskutusosoite_row['laskutus_maa']));
        $pdf_lasku->draw_text(50, 677, $maan_tiedot['nimi'], $page_lasku[$sivu], $norm);
      }

      $ostaja_kala = 644;
      $toim_kala = 583;
      $pohja = 515;
      $osoitekenttia = 3;

      $pdf_lasku->draw_text(50, 656, t("Ostaja", $kieli), $page_lasku[$sivu], $pieni);
    }
    else {

      if (in_array($laskutyyppi, array('21', '22'))) {
        $ostaja_kala = 768;
        $toim_kala = 710;
        $pohja = 577;

        $txt = t("Ostaja/Laskutusosoite", $kieli).":";
        $length = $pdf_lasku->strlen($txt, $pieni);

        $pdf_lasku->draw_text(50, $ostaja_kala+12, $txt, $page_lasku[$sivu], $pieni);
        $pdf_lasku->draw_rectangle($ostaja_kala+10, 50, $ostaja_kala+10, 50+$length, $page_lasku[$sivu], $rectparam);
      }
      else {
        $ostaja_kala = 717;
        $toim_kala = 656;
        $pohja = 577;

        $pdf_lasku->draw_text(50, 729, t("Ostaja/Laskutusosoite", $kieli), $page_lasku[$sivu], $pieni);
      }
    }

    $pdf_lasku->draw_text(50, $ostaja_kala, $laskurow["nimi"], $page_lasku[$sivu], ($laskutyyppi == 17 ? $boldi : $norm));
    $pdf_lasku->draw_text(50, $ostaja_kala-10, $laskurow["nimitark"], $page_lasku[$sivu], ($laskutyyppi == 17 ? $boldi : $norm));
    $pdf_lasku->draw_text(50, $ostaja_kala-20, $laskurow["osoite"], $page_lasku[$sivu], $norm);
    $pdf_lasku->draw_text(50, $ostaja_kala-30, $laskurow["postino"]." ".$laskurow["postitp"], $page_lasku[$sivu], $norm);

    if ($yhtiorow['maa'] != $laskurow['maa']) {
      $maan_tiedot = hae_maa(array('maakoodi' => $laskurow['maa']));
      $pdf_lasku->draw_text(50, $ostaja_kala-40, $maan_tiedot["nimi"], $page_lasku[$sivu], $norm);
    }

    //$pdf_lasku->draw_rectangle(674, 20,  611, 300, $page_lasku[$sivu], $rectparam);

    if (in_array($laskutyyppi, array('21', '22'))) {
      $txt = t("Toimitusosoite", $kieli).":";
      $length = $pdf_lasku->strlen($txt, $pieni);

      $pdf_lasku->draw_text(50, $toim_kala, $txt, $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_rectangle($toim_kala-2, 50, $toim_kala-2, 50+$length, $page_lasku[$sivu], $rectparam);
    }
    else {
      $pdf_lasku->draw_text(50, $toim_kala, t("Toimitusosoite", $kieli), $page_lasku[$sivu], $pieni);
    }

    $pdf_lasku->draw_text(50, $toim_kala-12, $laskurow["toim_nimi"], $page_lasku[$sivu], $norm);
    $pdf_lasku->draw_text(50, $toim_kala-22, $laskurow["toim_nimitark"], $page_lasku[$sivu], $norm);
    $pdf_lasku->draw_text(50, $toim_kala-32, $laskurow["toim_osoite"], $page_lasku[$sivu], $norm);
    $pdf_lasku->draw_text(50, $toim_kala-42, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $page_lasku[$sivu], $norm);

    if ($yhtiorow['maa'] != $laskurow['toim_maa']) {
      $maan_tiedot = hae_maa(array('maakoodi' => $laskurow['toim_maa']));
      $pdf_lasku->draw_text(50, $toim_kala-52, $maan_tiedot["nimi"], $page_lasku[$sivu], $norm);
    }

    //Oikea sarake
    $otsik[1] = 792;
    $arvo[1] = 782;

    $otsik[2] = 771;
    $arvo[2] = 761;

    $otsik[3] = 750;
    $arvo[3] = 740;

    $otsik[4] = 729;
    $arvo[4] = 719;

    $otsik[5] = 708;
    $arvo[5] = 698;

    $otsik[6] = 687;
    $arvo[6] = 677;

    $otsik[7] = 666;
    $arvo[7] = 656;

    $otsik[8] = 645;
    $arvo[8] = 635;

    $otsik[9] = 624;
    $arvo[9] = 614;

    // Oikean laidan ruudukko
    $pdf_lasku->draw_rectangle(800, 300, 779, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(800, 420, 779, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(779, 300, 758, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(779, 420, 758, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(758, 300, 737, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(758, 420, 737, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(737, 300, 716, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(737, 420, 716, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(716, 300, 695, 580, $page_lasku[$sivu], $rectparam);
    $pdf_lasku->draw_rectangle(716, 420, 695, 580, $page_lasku[$sivu], $rectparam);

    if ($laskutyyppi != 8) {
      $pdf_lasku->draw_rectangle(695, 300, 674, 580, $page_lasku[$sivu], $rectparam);
      $pdf_lasku->draw_rectangle(695, 420, 674, 580, $page_lasku[$sivu], $rectparam);
      $pdf_lasku->draw_rectangle(674, 300, 653, 580, $page_lasku[$sivu], $rectparam);
      $pdf_lasku->draw_rectangle(674, 420, 653, 580, $page_lasku[$sivu], $rectparam);
      $pdf_lasku->draw_rectangle(653, 300, 632, 580, $page_lasku[$sivu], $rectparam);
      $pdf_lasku->draw_rectangle(632, 300, 611, 580, $page_lasku[$sivu], $rectparam);
    }
    else {
      $pdf_lasku->draw_rectangle(695, 300, 674, 580, $page_lasku[$sivu], $rectparam);
      $pdf_lasku->draw_rectangle(674, 300, 653, 580, $page_lasku[$sivu], $rectparam);
    }

    if ($laskutyyppi == 20) {
      $pdf_lasku->draw_rectangle(632, 420, 611, 580, $page_lasku[$sivu], $rectparam);
    }


    // Oikean laidan ruudukko, vasen sarake
    // Riviindeksi
    $rivi_ind = 1;

    $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Laskun pvm", $kieli), $page_lasku[$sivu], $pieni);
    $pdf_lasku->draw_text(310, $arvo[$rivi_ind], tv1dateconv($laskurow["tapvm"]), $page_lasku[$sivu], ($laskutyyppi == 17 ? $boldi : $norm));
    $rivi_ind++;

    if ($laskutyyppi != 8) {
      $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Viitenumero", $kieli), $page_lasku[$sivu], $pieni);

      // Näytetään tilausnumero viitteenä kun kyseessä on ulkomaille lähetettävä proformalasku
      if ($toim == 'PROFORMA' and strtoupper($laskurow["maa"] )!= strtoupper($yhtiorow["maa"]) and $laskurow["viite"] == "") {
        $pdf_lasku->draw_text(310, $arvo[$rivi_ind], $laskurow["tunnus"], $page_lasku[$sivu], $boldi);
      }
      else {
        $pdf_lasku->draw_text(310, $arvo[$rivi_ind], $laskurow["viite"], $page_lasku[$sivu], $boldi);
      }
      $rivi_ind++;
    }

    $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Maksuehto", $kieli), $page_lasku[$sivu], $pieni);

    if (kyseessa_eramaksu($laskurow)) {
      $pdf_lasku->draw_text(310, $arvo[$rivi_ind],
        pdf_substr(t("Erämaksu"), 110, $pdf_lasku, $norm), $page_lasku[$sivu],
        $norm);
    }
    else {
      for ($s = strlen($laskurow["maksuehto"]); $s > 0; $s--) {
        if ($pdf_lasku->strlen($laskurow["maksuehto"], $norm) > 110) {
          $laskurow["maksuehto"] = substr($laskurow["maksuehto"], 0, $s);
        }
        else {
          break;
        }
      }

      $pdf_lasku->draw_text(310, $arvo[$rivi_ind],
        pdf_substr($laskurow["maksuehto"], 110, $pdf_lasku, $norm),
        $page_lasku[$sivu], $norm);
    }

    $rivi_ind++;

    if (!kyseessa_eramaksu($laskurow) and empty($laskurow["kateistyyppi"])) {
      $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Viivästyskorko", $kieli), $page_lasku[$sivu],
        $pieni);
      $pdf_lasku->draw_text(310, $arvo[$rivi_ind], ($laskurow["viikorkopros"]*1)." %", $page_lasku[$sivu],
        $norm);
    }

    $rivi_ind++;

    $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Valuutta", $kieli), $page_lasku[$sivu], $pieni);
    $pdf_lasku->draw_text(310, $arvo[$rivi_ind], $laskurow["valkoodi"], $page_lasku[$sivu], $norm);
    $rivi_ind++;

    if ($laskutyyppi != 8) {
      $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Asiakkaan Y-tunnus", $kieli), $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(310, $arvo[$rivi_ind], tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]), $page_lasku[$sivu], $norm);
      $rivi_ind++;
    }

    $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Toimitustapa", $kieli), $page_lasku[$sivu], $pieni);
    $pdf_lasku->draw_text(310, $arvo[$rivi_ind], pdf_substr(t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli), 110, $pdf_lasku, $norm), $page_lasku[$sivu], $norm);

    // Oikean laidan ruudukko, oikea sarake
    // Riviindeksi
    $rivi_ind = $laskutyyppi == 17 ? 2 : 1;

    if (!kyseessa_eramaksu($laskurow)) {
      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Eräpäivä", $kieli), $page_lasku[$sivu],
        $pieni);

      if ($laskurow["kateistyyppi"] != '') {
        $erapaiva = t('MAKSETTU', $kieli);
      }
      else {
        $erapaiva = tv1dateconv($laskurow["erpcm"]);
      }
      $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $erapaiva, $page_lasku[$sivu], $norm);
    }

    $laskutyyppi == 17 ? $rivi_ind-- : $rivi_ind++;

    if ($toim == 'PROFORMA' and $laskutyyppi == 20) {
      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Proformalasku", $kieli), $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["tunnus"], $page_lasku[$sivu], $boldi);
    }
    else {
      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Laskun numero", $kieli), $page_lasku[$sivu], $pieni);
      if ($laskurow["laskunro"] != 0) $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["laskunro"], $page_lasku[$sivu], $boldi);
    }

    $rivi_ind = $laskutyyppi == 17 ? $rivi_ind + 2 : $rivi_ind + 1;

    if (!in_array($laskutyyppi, array(8, 20)) and !kyseessa_eramaksu($laskurow)) {
      if ($laskurow["kasumma"] != 0.00) {
        $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Kassa-alennus", $kieli)." ".$laskurow["valkoodi"], $page_lasku[$sivu], $pieni);

        if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
          $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["kasumma_valuutassa"], $page_lasku[$sivu], $norm);
        }
        else {
          $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["kasumma"], $page_lasku[$sivu], $norm);
        }
      }

      $rivi_ind++;

      if ($laskurow["kapvm"] != '0000-00-00') {
        $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Kassa-alennus pvm", $kieli), $page_lasku[$sivu], $pieni);
        $pdf_lasku->draw_text(430, $arvo[$rivi_ind], tv1dateconv($laskurow["kapvm"]), $page_lasku[$sivu], $norm);
      }

      $rivi_ind++;
    }
    elseif (kyseessa_eramaksu($laskurow)) {
      $rivi_ind += 2;
    }
    elseif ($laskutyyppi == 20) {
      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Aiotut rajatoimipaikat (ja maat)", $kieli),
        $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(430,
        $arvo[$rivi_ind],
        t_avainsana("RAJATOIMIPAIKAT",
          $kieli,
          "AND avainsana.selite = '{$laskurow["aiotut_rajatoimipaikat"]}'",
          "",
          "",
          "selitetark"),
        $page_lasku[$sivu],
        $norm);

      $rivi_ind++;

      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Määrätoimipaikka (ja maa)", $kieli),
        $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(430,
        $arvo[$rivi_ind],
        t_avainsana("MAARATOIMPAIKKA",
          $kieli,
          "AND avainsana.selite = '{$laskurow["maaratoimipaikka"]}'",
          "",
          "",
          "selitetark"),
        $page_lasku[$sivu],
        $norm);

      $rivi_ind++;
    }

    $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Asiaa hoitaa", $kieli), $page_lasku[$sivu], $pieni);

    //etsitään myyjän nimi
    $query = "SELECT nimi
              from kuka
              where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
    $myyresult = pupe_query($query);
    $myyrow = mysql_fetch_assoc($myyresult);

    $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $myyrow["nimi"], $page_lasku[$sivu], $norm);
    $rivi_ind++;

    $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Asiakasnumero", $kieli), $page_lasku[$sivu], $pieni);

    if (trim($asiakasrow["asiakasnro"]) != "") {
      $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $asiakasrow["asiakasnro"], $page_lasku[$sivu], $norm);
    }
    else {
      $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["ytunnus"], $page_lasku[$sivu], $norm);
    }
    $rivi_ind++;

    if ($laskutyyppi == 8) {
      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Asiakkaan Y-tunnus", $kieli), $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(430, $arvo[$rivi_ind], tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]), $page_lasku[$sivu], $norm);
      $rivi_ind++;
    }

    if (!in_array($laskutyyppi, array('6', '8'))) {
      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Toimituspvm", $kieli), $page_lasku[$sivu], $pieni);

      //  Onko meillä monta toimituspäivää vai ei?
      if ($laskurow["tila"] == 'U') {
        $where = " uusiotunnus='$laskurow[tunnus]' ";
      }
      else {
        $where = " otunnus='$laskurow[tunnus]' ";
      }

      if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K') {
        $toimitettuaikak = " if (keratty = 'saldoton' and toimaika != '0000-00-00', toimaika, toimitettuaika) ";
      }
      elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
        $toimitettuaikak = " if (toimaika != '0000-00-00', toimaika, toimitettuaika) ";
      }
      else {
        $toimitettuaikak = " toimitettuaika ";
      }

      $query = "SELECT date_format($toimitettuaikak, '%Y-%m-%d') toimitettupaiva
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and $where
                and toimitettuaika!='0000-00-00'
                GROUP BY toimitettupaiva";
      $toimaikares = pupe_query($query);

      if (mysql_num_rows($toimaikares) > 1) {
        $pdf_lasku->draw_text(430, $arvo[$rivi_ind], t("Useita", $kieli), $page_lasku[$sivu], $norm);
      }
      else {
        $toimaikarow = mysql_fetch_assoc($toimaikares);
        $pdf_lasku->draw_text(430, $arvo[$rivi_ind], tv1dateconv($toimaikarow["toimitettupaiva"]), $page_lasku[$sivu], $norm);
      }
    }

    if ($laskutyyppi != '8') {
      $rivi_ind++;
    }

    // Leveämmät laatikot
    $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
    if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
    else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

    if (!empty($toimehto)) {
      $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Toimitusehto", $kieli), $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(310, $arvo[$rivi_ind], pdf_substr($toimehto, 270, $pdf_lasku, $norm), $page_lasku[$sivu], $norm);
      $rivi_ind++;
    }

    if ($laskutyyppi == 20) {
      $lahetysmaa = hae_maa(array("maakoodi" => $laskurow["maa_lahetys"]));
      $lahetysmaa = $lahetysmaa["nimi"];

      $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Lähetys-/Vientimaa", $kieli),
        $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(310, $arvo[$rivi_ind], $lahetysmaa, $page_lasku[$sivu], $norm);

      $pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Poistumistoimipaikka", $kieli),
        $page_lasku[$sivu], $pieni);
      $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["poistumistoimipaikka"],
        $page_lasku[$sivu], $norm);
    }
    elseif (!empty($laskurow["viesti"])) {
      $pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Tilausviite", $kieli), $page_lasku[$sivu], $pieni);

      $pdf_lasku->draw_text(310, $arvo[$rivi_ind],
        pdf_substr($laskurow["viesti"], 270, $pdf_lasku, $norm),
        $page_lasku[$sivu], $norm);
    }

    $pdf_lasku->draw_text(310, 595, t("Mahdolliset huomautukset tehtävä 8 päivän kuluessa", $kieli), $page_lasku[$sivu], $pieni);

    if ($laskutyyppi != 21 and $laskutyyppi != 22) {
      list($page_lasku, $sivu, $kala) = laskun_kommentit($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu, $pohja);
    }
    else {
      $kala = $pohja;
    }

    list($page_lasku[$sivu], $kala) = rivi_otsikot($laskurow, $asiakasrow, $kieli, $pdf_lasku, $page_lasku[$sivu], $kala, $laskutyyppi);

    return array($page_lasku, $sivu, $kala);
  }
}

if (!function_exists('laskun_kommentit')) {
  function laskun_kommentit($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu, $pohja) {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    // Kommentit
    $komm = "";

    if ($kaanteinen_verotus) {
      $komm .= t("AVL 8c §", $kieli).":###".wordwrap(str_replace("\n", "\n###", t_avainsana("KAANTALVVIESTI", $kieli, "", "", "", "selitetark")), 90, "\n###")."\n";
    }

    if (trim($laskurow['tilausyhteyshenkilo']) != '' and $laskutyyppi != 18) {
      $komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
    }

    if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
      $komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
    }

    if (trim($laskurow['kohde']) != '') {
      $komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
    }

    if (trim($laskurow['viesti']) != '' and $pdf_lasku->strlen($laskurow["viesti"], $norm) > 270) {
      $komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
    }

    if (trim($laskurow['sisviesti1']) != '') {
      $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti1']), 90, "\n###");
    }

    // Lisätään kommenttiin sopimus_numero se on asetettu ja laiterekisteri käytössä
    if ($yhtiorow['laiterekisteri_kaytossa'] != '' and $laskurow['sopimus_numero'] != '') {
      $komm .= "\n".t('Sopimus').": {$laskurow['sopimus_numero']}";
    }

    // Tutkitaan onko tämä työmääräys
    if ($laskurow["tila"] == 'U') {
      $where = " lasku.laskunro = '$laskurow[laskunro]' ";
    }
    else {
      $where = " lasku.tunnus = '$laskurow[tunnus]' ";
    }

    $query = "SELECT tyomaarays.*
              FROM lasku
              JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
              WHERE lasku.yhtio      = '$kukarow[yhtio]'
              and lasku.tilaustyyppi = 'A'
              and $where";
    $tyomres = pupe_query($query);

    if (mysql_num_rows($tyomres) > 0) {

      while ($tyomrow = mysql_fetch_assoc($tyomres)) {

        $komm .= "\n".t("Työmääräys", $kieli). ":###$tyomrow[otunnus]";

        $al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selitetark != '' and avainsana.nakyvyys in ('K','L','J')");

        while ($al_row = mysql_fetch_assoc($al_res)) {

          $kentta = $al_row["selite"];

          if (((!is_numeric($tyomrow[$kentta]) and trim($tyomrow[$kentta]) != '') or (is_numeric($tyomrow[$kentta]) and $tyomrow[$kentta] != 0)) and trim($tyomrow[$kentta]) != '0000-00-00') {
            if (strtoupper($al_row["selitetark_2"]) == "TEXT") {
              $komm .= "\n$al_row[selitetark]:###".wordwrap(str_replace("\n", "\n###", $tyomrow[$kentta]), 90, "\n###");
            }
            else {
              if (strtoupper($al_row["selitetark_2"]) == "DATE") {
                $tyomrow[$kentta] = tv1dateconv($tyomrow[$kentta]);
              }
              elseif ($kentta == "suorittaja") {
                $query = "SELECT nimi
                          FROM kuka
                          WHERE yhtio = '$kukarow[yhtio]'
                          and kuka    = '".$tyomrow[$kentta]."'";
                $yresult = pupe_query($query);
                $tmrow = mysql_fetch_assoc($yresult);

                $tyomrow[$kentta] = $tmrow["nimi"];
              }
              elseif ($kentta == "merkki") {
                $yresult = t_avainsana("SARJANUMERON_LI", $kieli, "and avainsana.selite = 'MERKKI' and avainsana.selitetark = '".$tyomrow[$kentta]."'");

                if (mysql_num_rows($yresult) > 0) {
                  $tmrow = mysql_fetch_assoc($yresult);

                  $tyomrow[$kentta] = $tmrow["selitetark_2"];
                }
              }

              $komm .= "\n$al_row[selitetark]:###$tyomrow[$kentta]";
            }
          }
        }
      }

      $komm .= "\n";
    }

    if (in_array($laskutyyppi, array(1, 4, 8, 21, 22))) {
      $query = "SELECT ifnull(group_concat(distinct tunnus SEPARATOR ', '), 0) tilnot
                FROM lasku
                WHERE yhtio   = '$kukarow[yhtio]'
                and laskunro  = '$laskurow[laskunro]'
                and tila      = 'L'
                and laskunro != 0";
      $result = pupe_query($query);
      $apulaskuro = mysql_fetch_assoc($result);

      if ($apulaskuro["tilnot"] != "0") {
        $komm .= "\n".t("Tilausnumero(t)", $kieli). ":###".wordwrap(str_replace("\n", "\n###", $apulaskuro["tilnot"]), 90, "\n###");
      }
    }

    // Tulostetaan laskun kommentti
    if (trim($komm) != '') {
      $kommentit = explode("\n", trim($komm));

      $maxoik = 0;

      foreach ($kommentit as $kommentti) {
        if (strpos($kommentti, "###") !== FALSE) {
          list($o, $v) = explode("###", $kommentti);

          $oikpos = $pdf_lasku->strlen($o, $boldi);

          if ($oikpos > $maxoik) {
            $maxoik = $oikpos;
          }
        }
      }

      foreach ($kommentit as $kommentti) {

        if ($pohja <= 130) {
          loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

          $sivu++;

          list($page_lasku[$sivu], $pohja) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, 'X');
        }

        if (strpos($kommentti, "###") !== false) {
          list($o, $v) = explode("###", $kommentti);

          $pdf_lasku->draw_text(35, $pohja, trim($o), $page_lasku[$sivu], $boldi);
          $pdf_lasku->draw_text(35+$maxoik+5, $pohja, trim($v), $page_lasku[$sivu], ($laskutyyppi == 17 ? $norm_italic : $norm));
        }
        else {
          $pdf_lasku->draw_text(35, $pohja, trim($kommentti), $page_lasku[$sivu], ($laskutyyppi == 17 ? $norm_italic : $norm));
        }

        $pohja = $pohja - 10;
      }

      $kala = $pohja-10;

    }
    else {
      $kala = $pohja;
    }

    return array($page_lasku, $sivu, $kala);
  }
}

if (!function_exists('uusi_sivu')) {
  function uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $alvierittely = '') {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    $thispage = $pdf_lasku->new_page("a4");

    tulosta_logo_pdf($pdf_lasku, $thispage, $laskurow);

    //Otsikko
    if ($laskurow["vienti"] == "") {
      if ($laskurow["summa"] >= 0) {
        if ($toim == 'PROFORMA') {
          $pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $thispage);
        }
        elseif (kyseessa_eramaksu($laskurow)) {
          $pdf_lasku->draw_text(310, 815, t("Maksutosite", $kieli), $thispage, $iso);
        }
        elseif ($laskurow["kateistyyppi"] != '') {
          $pdf_lasku->draw_text(310, 815, t("Kuitti", $kieli), $thispage, $iso);
        }
        else {
          $pdf_lasku->draw_text(310, 815, t("Lasku", $kieli), $thispage);
        }
      }
      elseif ($laskurow["kateistyyppi"] != '') {
        $pdf_lasku->draw_text(310, 815, t("Käteishyvitys", $kieli), $thispage, $iso);
      }
      else {
        $pdf_lasku->draw_text(310, 815, t("Hyvityslasku", $kieli), $thispage);
      }
    }
    else {
      if ($toim == 'PROFORMA') {
        $pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $thispage);
      }
      elseif (kyseessa_eramaksu($laskurow)) {
        $pdf_lasku->draw_text(310, 815, t("Maksutosite", $kieli), $thispage);
      }
      elseif ($laskurow["kateistyyppi"] != '') {
        $pdf_lasku->draw_text(310, 815, t("Kuitti", $kieli), $thispage);
      }
      elseif ($laskurow["summa"] >= 0) {
        $pdf_lasku->draw_text(310, 815, t("Vientilasku", $kieli), $thispage);
      }
      else {
        $pdf_lasku->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $thispage);
      }
    }

    //Oikea sarake
    $pdf_lasku->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
    $pdf_lasku->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);
    $pdf_lasku->draw_text(310, 792, t("Laskun pvm", $kieli), $thispage, $pieni);
    $pdf_lasku->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]), $thispage, $norm);
    $pdf_lasku->draw_text(430, 792, t("Eräpäivä", $kieli), $thispage, $pieni);

    if (kyseessa_eramaksu($laskurow)) {
      $erapaiva = tv1dateconv($laskurow["erpcm"]);
    }
    elseif ($laskurow["kateistyyppi"] != '') {
      $erapaiva = t('MAKSETTU', $kieli);
    }
    else {
      $erapaiva = tv1dateconv($laskurow["erpcm"]);
    }

    $pdf_lasku->draw_text(430, 782, $erapaiva, $thispage, $norm);

    $pdf_lasku->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
    $pdf_lasku->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);

    if ($laskutyyppi != 8) {
      $pdf_lasku->draw_text(310, 771, t("Viitenumero", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(310, 761, $laskurow["viite"], $thispage, $boldi);
    }

    $pdf_lasku->draw_text(430, 771, t("Laskun numero", $kieli), $thispage, $pieni);
    $pdf_lasku->draw_text(430, 761, $laskurow["laskunro"], $thispage, $boldi);

    $kala = 735;

    if ($alvierittely == '') {
      list($thispage, $kala) = rivi_otsikot($laskurow, $asiakasrow, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi);
    }

    return array($thispage, $kala);
  }
}

if (!function_exists('rivi_lasku')) {
  function rivi_lasku($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo) {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    if ($laskutyyppi == 10 or $laskutyyppi == 12) {
      $row["hinta"] = 0;
      $row["rivihinta"] = 0;
      $row["rivihinta_valuutassa"] = 0;
      $row["rivihinta_verollinen"] = 0;
      $row["rivihinta_valuutassa_verollinen"] = 0;

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $row["ale{$alepostfix}"] = 0;
      }
    }

    $row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys', $kieli);

    if ($row["vienti_kurssi"] > 0) {
      $kurssi = $row["vienti_kurssi"];
    }
    else {
      $kurssi = 1;
    }

    // Laskupohjissa 13 ja 14 ei näytetä aleja
    if (($yhtiorow['myynnin_alekentat'] > 1 or $row['erikoisale'] > 0) and $laskutyyppi != 13 and $laskutyyppi != 14 and $laskutyyppi != 15 and $laskutyyppi != 16) {

      $alekomm = "";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        if (trim($row["ale{$alepostfix}"]) > 0) {
          $alekomm .= t("Ale", $kieli)."{$alepostfix} ".($row["ale{$alepostfix}"]*1)." %\n";
        }
      }

      if ($row['erikoisale'] > 0) {
        $alekomm .= t("Erikoisale", $kieli)." ".($row["erikoisale"]*1)." %\n";
      }

      $row["kommentti"] = $alekomm.$row["kommentti"];
    }

    if ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"]
      and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"]
      and trim($row['laskuviesti']) != ''
      and strpos($row["kommentti"], "$row[laskuviesti]") === FALSE
      and !in_array($laskutyyppi, array(6, 21, 22))
    ) {
      $row["kommentti"] = trim($row["laskuviesti"])."\n".$row["kommentti"];
    }

    if ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and trim($row['asiakkaan_tilausnumero']) != '' and strpos($row["kommentti"], "$row[asiakkaan_tilausnumero]") === FALSE and $laskutyyppi != 6) {
      $row["kommentti"] = trim($row["asiakkaan_tilausnumero"])."\n".$row["kommentti"];
    }

    $_ennakkolaskun_tyyppi_check = ($yhtiorow['ennakkolaskun_tyyppi'] == 'K');
    $_ennakkolaskun_tyyppi_check = ($_ennakkolaskun_tyyppi_check and $laskurow['clearing'] == 'ENNAKKOLASKU');

    if ($_ennakkolaskun_tyyppi_check) {

      // Ennakkomaksu_tuotenumerolle tehdään tuotenumero ja nimitys kuin olisi myyty normaalisti
      if (preg_match('/^[^ ]*/', $row['nimitys'], $matches)) {
        $row['tuoteno'] = $matches[0];
        $row["nimitys"] = str_replace($row['tuoteno'].' - ', '', $row['nimitys']);
      }

      if (strpos($row["kommentti"], "100%") !== false) {
        $_ennakkoteksti = t("Ennakkolasku");
        $row["kommentti"] = preg_replace("/{$_ennakkoteksti}.*100\%/", '', $row["kommentti"]);
      }
    }

    $row["kommentti"] = trim($row["kommentti"]);

    // Kommentissa voi olla jotain sanoja jotka pitää käääntää:
    if ($kieli != $yhtiorow["kieli"]) {
      $row["kommentti"] = str_replace("Ennakkotilaus:", t("Ennakkotilaus", $kieli).":", $row["kommentti"]);

      if ($laskutyyppi != 18) {
        $row["kommentti"] = str_replace("Tilaus:", t("Tilaus", $kieli).":", $row["kommentti"]);
      }

      $row["kommentti"] = str_replace("Rahtikirja:", t("Rahtikirja", $kieli).":", $row["kommentti"]);
    }

    // Jos meillä on marginaalimyyntiä/käänteinen alv
    if ($row["alv"] >= 600) {
      $row["kommentti"] .= "\n".t("Ei lisättyä arvonlisäveroa, ostajan käännetty verovelvollisuus", $kieli).".";
    }
    elseif ($row["alv"] >= 500) {
      $row["kommentti"] .= "\n".t("Ei sisällä vähennettävää veroa", $kieli).".";
    }

    //Haetan sarjanumeron tiedot
    if ($row["sarjanumeroseuranta"] != "") {
      if ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["kpl"] < 0 and $row["var2"] == "EIOST") {
        $sarjanutunnus = "ostorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["kpl"] < 0 and ($row['sarjanumeroseuranta'] == 'S' or $row['sarjanumeroseuranta'] == 'G')) {
        $sarjanutunnus = "myyntirivitunnus";
      }
      elseif ($row["kpl"] < 0) {
        $sarjanutunnus = "ostorivitunnus";
      }
      else {
        $sarjanutunnus = "myyntirivitunnus";
      }

      $query = "SELECT DISTINCT sarjanumero, parasta_ennen
                FROM sarjanumeroseuranta
                WHERE yhtio      = '$kukarow[yhtio]'
                and tuoteno      = '$row[tuoteno]'
                and $sarjanutunnus  in ($row[rivitunnukset])
                and sarjanumero != ''
                ORDER BY sarjanumero";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) > 0) {
        if ($row["kommentti"] != '') {
          $row["kommentti"] .= "\n";
        }

        if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
          $row["kommentti"] .= t("E:nro", $kieli).": ";
        }
        else {
          $row["kommentti"] .= t("S:nro", $kieli).": ";
        }

        while ($sarjarow = mysql_fetch_assoc($sarjares)) {
          if ($row["sarjanumeroseuranta"] == "F") {
            $row["kommentti"] .= $sarjarow["sarjanumero"]." ".t("Parasta ennen", $kieli).": ".tv1dateconv($sarjarow["parasta_ennen"]).", ";
          }
          else {
            $row["kommentti"] .= $sarjarow["sarjanumero"].", ";
          }
        }

        $row["kommentti"] = substr($row["kommentti"], 0, -2);
      }
    }

    if ($laskutyyppi == 7) {

      if ($row["eankoodi"] != "") {
        $row["kommentti"] = "EAN: $row[eankoodi]\n$row[kommentti]";
      }

      $query = "SELECT kommentti
                FROM asiakaskommentti
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$row['tuoteno']}'
                AND ytunnus = '{$laskurow['ytunnus']}'
                AND tyyppi  = ''
                ORDER BY tunnus";
      $asiakaskommentti_res = pupe_query($query);

      if (mysql_num_rows($asiakaskommentti_res) > 0) {
        while ($asiakaskommentti_row = mysql_fetch_assoc($asiakaskommentti_res)) {
          $row["kommentti"] .= "\n".$asiakaskommentti_row['kommentti'];
        }
      }
    }

    if ($laskutyyppi == 1) {
      list($thispage, $kala, $summa, $arvo) = rivi_plain($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
    }
    elseif ($laskutyyppi == 2 or $laskutyyppi == 12 or $laskutyyppi == 14 or $laskutyyppi == 16 or $laskutyyppi == 19) {
      list($thispage, $kala, $summa, $arvo) = rivi_perhe($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
    }
    elseif ($laskutyyppi == 4 or $laskutyyppi == 8) {
      list($thispage, $kala, $summa, $arvo) = rivi_plain_2($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
    }
    elseif ($laskutyyppi == 13 or $laskutyyppi == 15) {
      list($thispage, $kala, $summa, $arvo) = rivi_viro($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
    }
    else {
      list($thispage, $kala, $summa, $arvo) = rivi_original($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
    }

    return array($thispage, $kala, $summa, $arvo);
  }
}

if (!function_exists('rivi_otsikot')) {
  function rivi_otsikot($laskurow, $asiakasrow, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi) {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    //Laskurivien otsikkotiedot
    if ($laskutyyppi == 1 or $laskutyyppi == 4 or $laskutyyppi == 8) {
      //eka rivi
      $pdf_lasku->draw_text(20, $kala, t("Tuotenimi", $kieli), $thispage, $pieni);

      if ($laskutyyppi == 1) {
        $pdf_lasku->draw_text(210, $kala, t("Tuotenumero", $kieli), $thispage, $pieni);
      }
      else {
        $pdf_lasku->draw_text(250, $kala, t("Tuotenumero", $kieli), $thispage, $pieni);
      }
      $pdf_lasku->draw_text(360, $kala, t("Määrä", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(430, $kala, t("A-hinta", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(475, $kala, t("Alennus", $kieli)."-%", $thispage, $pieni);
      $pdf_lasku->draw_text(530, $kala, t("Veroton arvo", $kieli), $thispage, $pieni);

      //toka rivi
      $pdf_lasku->draw_text(20, $kala-9, t("Lisätietoja", $kieli), $thispage, $pieni);
    }
    elseif ($laskutyyppi == 13 or $laskutyyppi == 14) {
      //eka rivi
      $pdf_lasku->draw_text(35, $kala, t("Tuotenimi", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(220, $kala, t("Tuotenumero", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(390, $kala, t("Määrä", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(440, $kala, t("A-hinta", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(510, $kala, t("Veroton arvo", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(550, $kala, t("Alv", $kieli)."-%", $thispage, $pieni);

      //toka rivi
      $pdf_lasku->draw_text(35, $kala-9, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(390, $kala-9, t("Yksikkö", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(440, $kala-9, t("Toimituspvm", $kieli), $thispage, $pieni);
    }
    elseif ($laskutyyppi == 15 or $laskutyyppi == 16) {
      //eka rivi
      $pdf_lasku->draw_text(35, $kala, t("Tuotenimi", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(220, $kala, t("Tuotenumero", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(350, $kala, t("Määrä", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(390, $kala, t("SVH", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(440, $kala, t("A-hinta", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(495, $kala, t("Veroton arvo", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(550, $kala, t("Alv", $kieli)."-%", $thispage, $pieni);

      //toka rivi
      $pdf_lasku->draw_text(35, $kala-9, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(350, $kala-9, t("Yksikkö", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(440, $kala-9, t("Toimituspvm", $kieli), $thispage, $pieni);
    }
    elseif ($laskutyyppi == 18) {
      //eka rivi
      $pdf_lasku->draw_text(35, $kala, t("Tuotenimi", $kieli), $thispage, $pieni);

      $pdf_lasku->draw_text(260, $kala, t("Tuotenumero", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(340, $kala, t("Määrä", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(390, $kala, t("A-hinta", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(450, $kala, t("Alennus", $kieli)."-%", $thispage, $pieni);
      $pdf_lasku->draw_text(490, $kala, t("Veroton arvo", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(550, $kala, t("Alv", $kieli)."-%", $thispage, $pieni);

      //toka rivi
      $pdf_lasku->draw_text(35, $kala-9, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(340, $kala-9, t("Yksikkö", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(390, $kala-9, t("Toimituspvm", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(490, $kala-9, t("Verollinen arvo", $kieli), $thispage, $pieni);
    }
    else {
      //eka rivi
      if ($laskutyyppi == 21 or $laskutyyppi == 22) {
        $pdf_lasku->draw_text(35, $kala, t("Tuotenumero", $kieli).":", $thispage, $pieni);
        $pdf_lasku->draw_text(150, $kala, t("Tuotenimi", $kieli).":", $thispage, $pieni);
      }
      else {
        $pdf_lasku->draw_text(35, $kala, t("Tuotenimi", $kieli), $thispage, $pieni);
        $pdf_lasku->draw_text(190, $kala, t("Tuotenumero", $kieli), $thispage, $pieni);
      }

      if ($laskutyyppi == 21 or $laskutyyppi == 22) {

        //Määrä
        $oikpos = $pdf_lasku->strlen(t("Määrä", $kieli).":", $pieni);
        $pdf_lasku->draw_text(365-$oikpos, $kala, t("Määrä", $kieli).":", $thispage, $pieni);

        $oikpos = $pdf_lasku->strlen(t("A-hinta", $kieli).":", $pieni);
        $pdf_lasku->draw_text(435-$oikpos, $kala, t("A-hinta", $kieli).":", $thispage, $pieni);
      }
      else {
        $pdf_lasku->draw_text(340, $kala, t("Määrä", $kieli), $thispage, $pieni);
        $pdf_lasku->draw_text(390, $kala, t("A-hinta", $kieli), $thispage, $pieni);
      }

      if ($laskutyyppi != 21 and $laskutyyppi != 22 and $laskurow['_onko_alennuksia']) {
        $pdf_lasku->draw_text(450, $kala, t("Alennus", $kieli)."-%", $thispage, $pieni);
      }

      if (in_array($laskutyyppi, array('21', '22'))) {
        list($ff_string, $ff_font) = pdf_fontfit(t("Yhteensä", $kieli).":", 50, $pdf_lasku, $pieni);

        $oikpos = $pdf_lasku->strlen($ff_string, $ff_font);
        $pdf_lasku->draw_text(540-$oikpos, $kala, $ff_string, $thispage, $ff_font);
      }
      else {
        $pdf_lasku->draw_text(490, $kala, t("Veroton arvo", $kieli), $thispage, $pieni);
      }

      if ($laskutyyppi != 21 and $laskutyyppi != 22) {
        $pdf_lasku->draw_text(550, $kala, t("Alv", $kieli)."-%", $thispage, $pieni);
      }

      //toka rivi
      if ($laskutyyppi != 6 and $laskutyyppi != 21 and $laskutyyppi != 22) {
        $pdf_lasku->draw_text(35, $kala-9, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli), $thispage, $pieni);
      }

      if (!in_array($laskutyyppi, array('21', '22'))) {
        $pdf_lasku->draw_text(340, $kala-9, t("Yksikkö", $kieli), $thispage, $pieni);
      }

      if (!in_array($laskutyyppi, array('6', '21', '22', '24'))) {
        $pdf_lasku->draw_text(390, $kala-9, t("Toimituspvm", $kieli), $thispage, $pieni);
      }

      if ($laskutyyppi == 24) {
        $pdf_lasku->draw_text(390, $kala-9, t("A-hinta, verollinen", $kieli), $thispage, $pieni);
      }

      if ($laskutyyppi != 5 and !(($laskutyyppi == 21 or $laskutyyppi == 22) and $laskurow['vienti'] != "")) {
        $pdf_lasku->draw_text(490, $kala-9, t("Verollinen arvo", $kieli), $thispage, $pieni);
      }
    }

    if ($laskutyyppi == 21 or $laskutyyppi == 22) {
      if ($laskurow['vienti'] == "") {
        $viivakala = $kala-15;
      }
      else {
        $viivakala = $kala-6;
      }

      $pdf_lasku->draw_line(array(35, 580), array($viivakala, $viivakala), $thispage, $lineparams);
      $kala -= 5;
    }

    $kala -= 25;

    return array($thispage, $kala);
  }
}

if (!function_exists('lisaa_tullinimike_ja_alkuperamaa')) {
  function lisaa_tullinimike_ja_alkuperamaa($tullinimike_ja_alkuperamaa, $row, $pdf_lasku, $thispage, $kala, $kieli) {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    $kala = $kala - 10;

    // Haetaan tullinimike ja alkuperämaa
    $query = "SELECT alkuperamaa,
              if (jarjestys = 0, 9999, jarjestys) sorttaus
              FROM tuotteen_toimittajat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$row['tuoteno']}'
              ORDER BY sorttaus
              LIMIT 1";
    $alkuperamaa_res = pupe_query($query);
    $alkuperamaa_row = mysql_fetch_assoc($alkuperamaa_res);

    $tullinimike_ja_alkuperamaa_text = t("Tullinimike", $kieli).": {$row["tullinimike"]}";

    if ($tullinimike_ja_alkuperamaa == "A") {
      // Tsekataan onko tämä valmiste
      $query = "SELECT tunnus
                FROM tuoteperhe
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND isatuoteno = '{$row['tuoteno']}'
                LIMIT 1";
      $alkuperamaa_res = pupe_query($query);

      if (mysql_num_rows($alkuperamaa_res)) {
        // Valmisteet ovat kotimaan tuotantoa
        $tullinimike_ja_alkuperamaa_text .= " ".t("Alkuperämaa", $kieli).":  {$yhtiorow['maa']}";
      }
      elseif ($alkuperamaa_row['alkuperamaa'] != '') {
        $tullinimike_ja_alkuperamaa_text .= " ".t("Alkuperämaa", $kieli).":  {$alkuperamaa_row['alkuperamaa']}";
      }
    }
    elseif ($tullinimike_ja_alkuperamaa == "C") {
      // Kaikki tuotteet ovat kotimaan tuotantoa
      $tullinimike_ja_alkuperamaa_text .= " ".t("Alkuperämaa", $kieli).":  {$yhtiorow['maa']}";
    }

    $pdf_lasku->draw_text(35, $kala, $tullinimike_ja_alkuperamaa_text, $thispage, $pieni);

    return $kala;
  }
}

if (!function_exists('rivi_original')) {
  function rivi_original($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
    global $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    $kuinka_alhaalla_kaytiin_nimitys = $kuinka_alhaalla_kaytiin_tuoteno = $kala;

    if ($laskutyyppi == 18) {
      $loop_kala = $kala;
      $loop_i = -10;

      //Nimitys
      foreach (str_split($row['nimitys'], 40) as $nimitys_split) {
        $loop_i += 10;

        $pdf_lasku->draw_text(35, $kala - $loop_i, $nimitys_split, $thispage, $norm);
      }

      $kuinka_alhaalla_kaytiin_nimitys = $kala - $loop_i;

      $loop_i = -10;

      //Tuoteno
      foreach (str_split($row['tuoteno'], 10) as $tuoteno_split) {

        $loop_i += 10;

        list($ff_string, $ff_font) = pdf_fontfit($tuoteno_split, 90, $pdf_lasku, $norm);
        $pdf_lasku->draw_text(260, $kala - $loop_i, $ff_string, $thispage, $ff_font);
      }

      $kuinka_alhaalla_kaytiin_tuoteno = $kala - $loop_i;
    }
    elseif ($laskutyyppi == 21 or $laskutyyppi == 22) {
      //Tuoteno
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 125, $pdf_lasku, $norm);
      $pdf_lasku->draw_text(35, $kala, $ff_string, $thispage, $ff_font);

      //Nimitys
      list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 150, $pdf_lasku, ($laskutyyppi == 17 ? $boldi : $norm));
      $pdf_lasku->draw_text(150, $kala, $ff_string, $thispage, $ff_font);
    }
    else {
      //Nimitys
      list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 150, $pdf_lasku, ($laskutyyppi == 17 ? $boldi : $norm));
      $pdf_lasku->draw_text(35, $kala, $ff_string, $thispage, $ff_font);

      //Tuoteno
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 145, $pdf_lasku, $norm);
      $pdf_lasku->draw_text(190, $kala, $ff_string, $thispage, $ff_font);
    }

    //Määrä
    $oikpos = $pdf_lasku->strlen($row["kpl"], $norm);
    $pdf_lasku->draw_text(365-$oikpos, $kala, $row["kpl"], $thispage, $norm);

    // yksikköhinta pitää olla veroton
    if ($yhtiorow["alv_kasittely"] == '') {

      // Onko marginaaliverotusta
      $laskalv = $row["alv"];

      // Jos meillä on marginaalimyyntiä/käänteinen alv
      if ($row["alv"] >= 500) {
        $laskalv = 0;
      }

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $veroton_hinta = $row["hinta"] / (1 + $laskalv / 100);
      }
      else {
        $veroton_hinta = round($row["hinta"] / (1 + $laskalv / 100), 2);
      }
    }
    else {
      $veroton_hinta = $row["hinta"];
    }

    if (($laskutyyppi == 19 and (empty($row['perheid']) or $row['tunnus'] == $row['perheid'])) or $laskutyyppi != 19) {
      // A-hinta
      $oikpos = $pdf_lasku->strlen(hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(435-$oikpos, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $thispage, $norm);

      //Alennus
      $alennukset_yhteensa = generoi_alekentta_php($row, 'M', 'plain');

      if ($alennukset_yhteensa != 0 and !in_array($laskutyyppi, array(21, 22))) {
        $pdf_lasku->draw_text(450, $kala, $alennukset_yhteensa, $thispage, $norm);
      }

      if ($kurssi != 1) {
        //Veroton Rivihinta Valuutassa
        $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $thispage, $norm);
      }
      else {
        // Veroton Rivihinta
        $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $thispage, $norm);
      }

      if (!in_array($laskutyyppi, array(21, 22))) {
        //Arvonlisävero
        if ($row["alv"] >= 600) {
          $pdf_lasku->draw_text(550, $kala, t("K.V.", $kieli), $thispage, $norm);
        }
        elseif ($row["alv"] >= 500) {
          $pdf_lasku->draw_text(550, $kala, t("M.V.", $kieli), $thispage, $norm);
        }
        else {
          $pdf_lasku->draw_text(550, $kala, ($row["alv"]*1)."%", $thispage, $norm);
        }
      }
    }

    if ($row["myyntihinta_maara"] > 1) {
      $kala = $kala - 7;
      $pdf_lasku->draw_text(390, $kala, hintapyoristys($veroton_hinta*$row["myyntihinta_maara"], 0, $laskurow['_loppunollat'])." / $row[myyntihinta_maara] $row[yksikko]", $thispage, $pieni);
      $kalakorjaus = 7;
    }
    else {
      $kalakorjaus = 0;
    }

    // Ensimmäinen rivi piirretty
    $kala = $kala - 10;

    if (!in_array($laskutyyppi, array('21', '22'))) {
      //Yksikkö
      $_yks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selitetark");

      $oikpos = $pdf_lasku->strlen($_yks, $pieni);
      $pdf_lasku->draw_text(365-$oikpos, $kala, $_yks, $thispage, $pieni);
    }

    // Jos on
    if ($laskutyyppi == 24) {
      // yksikköhinta pitää olla verollinen tässä
      if ($yhtiorow["alv_kasittely"] == '') {
        $verollinen_hinta = $row["hinta"];
      }
      else {
        // Onko marginaaliverotusta
        $laskalv = $row["alv"];

        // Jos meillä on marginaalimyyntiä/käänteinen alv
        if ($row["alv"] >= 500) {
          $laskalv = 0;
        }

        if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
          $verollinen_hinta = $row["hinta"] * (1 + $laskalv / 100);
        }
        else {
          $verollinen_hinta = round($row["hinta"] * (1 + $laskalv / 100), 2);
        }
      }

      // A-hinta verollisena
      $oikpos = $pdf_lasku->strlen(hintapyoristys(laskuval($verollinen_hinta, $kurssi), 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(435-$oikpos, $kala, hintapyoristys(laskuval($verollinen_hinta, $kurssi), 0, $laskurow['_loppunollat']), $thispage, $norm);

    } else {
      //Toimitusaika
      if (substr($row["toimitettuaika"], 0, 10) != "0000-00-00" and !in_array($laskutyyppi, array(6, 21, 22))) {
        $oikpos = $pdf_lasku->strlen(tv1dateconv(substr($row["toimitettuaika"], 0, 10)), $pieni);
        $pdf_lasku->draw_text(435-$oikpos, $kala, tv1dateconv(substr($row["toimitettuaika"], 0, 10)), $thispage, $pieni);
      }
    }

    // Näytetäänkö verollinen rivihinta
    $_pii_verolli = (!in_array($laskutyyppi, array(5, 19, 21, 22)));

    if (in_array($laskutyyppi, array(21, 22))) {
      $_pii_verolli = (empty($laskurow['vienti']));
    }

    if ($laskutyyppi == 19) {
      $_pii_verolli = (empty($row['perheid']) or $row['tunnus'] == $row['perheid']);
    }

    $_pii_verolli = ($_pii_verolli and $row["alv"] < 500);

    // Verollinen rivihinta
    if ($_pii_verolli) {
      if ($kurssi != 1) {
        $verollrivi = $row["rivihinta_valuutassa_verollinen"];
      }
      else {
        $verollrivi = $row["rivihinta_verollinen"];
      }

      $oikpos = $pdf_lasku->strlen(hintapyoristys($verollrivi, 0, $laskurow['_loppunollat']), $pieni);
      $pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($verollrivi, 0, $laskurow['_loppunollat']), $thispage, $pieni);
    }

    if ($laskutyyppi == 18 and $kala >= $kuinka_alhaalla_kaytiin_nimitys) {
      $kala = $kuinka_alhaalla_kaytiin_nimitys - 10;
    }

    // Kommentti
    if (trim($row["kommentti"]) != '') {

      $kommentit = explode("\n", trim($row["kommentti"]));

      if ($laskutyyppi != 18 and $laskutyyppi != 6 and $laskutyyppi != 21 and $laskutyyppi != 22 and $row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and !preg_match("/".t("Tilaus", $kieli).":? ".$row["otunnus"]."/", $row["kommentti"])) {
        $pdf_lasku->draw_text(35, $kala+$kalakorjaus, t("Tilaus", $kieli).": ".$row["otunnus"], $thispage, $pieni);
        $kala = $kala - 10;
      }

      foreach ($kommentit as $akey => $kommentti) {

        if ($laskutyyppi == 18 and ($kala >= $kuinka_alhaalla_kaytiin_tuoteno)) {
          $rpit1 = 200;
          $rpit2 = 190;
        }
        elseif ($akey == 0) {
          $rpit1 = 300;
          $rpit2 = 250;
        }
        else {
          $rpit1 = 500;
          $rpit2 = 450;
        }

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {
            if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
              $pdf_lasku->draw_text(35, $kala+$kalakorjaus, trim($lause), $thispage, $pieni);
              $kala = $kala - 10;
              $lause = "";
            }

            $lause .= $sana;
          }

          $kommentti = $lause;
        }

        if (trim($kommentti) != "") {
          $pdf_lasku->draw_text(35, $kala+$kalakorjaus, trim($kommentti), $thispage, $pieni);
          $kala = $kala - 10;
        }
      }
      $kala += 10;
    }
    elseif ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and !in_array($laskutyyppi, array(6, 18, 21, 22))) {
      $pdf_lasku->draw_text(35, $kala+$kalakorjaus, t("Tilaus", $kieli).": ".$row["otunnus"], $thispage, $pieni);
    }

    if ($tullinimike_ja_alkuperamaa != '' and $row['tullinimike'] != '') {
      $kala = lisaa_tullinimike_ja_alkuperamaa($tullinimike_ja_alkuperamaa, $row, $pdf_lasku, $thispage, $kala, $kieli);
    }

    if ($laskutyyppi == 18 and $kala >= $kuinka_alhaalla_kaytiin_tuoteno) {
      $kala = $kuinka_alhaalla_kaytiin_tuoteno - 5;
    }

    $kala = $kala - 15;

    if ($kurssi != 1) {
      $arvo += hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_valuutassa_verollinen"], 0, $laskurow['_loppunollat']);
    }
    else {
      $arvo += hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_verollinen"], 0, $laskurow['_loppunollat']);
    }

    return array($thispage, $kala, $summa, $arvo);
  }
}

if (!function_exists('rivi_plain')) {
  function rivi_plain($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
    global $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    //Nimitys
    list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 180, $pdf_lasku, $norm);
    $pdf_lasku->draw_text(20, $kala, $ff_string, $thispage, $ff_font);

    //Tuoteno
    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 145, $pdf_lasku, $norm);
    $pdf_lasku->draw_text(210, $kala, $ff_string, $thispage, $ff_font);

    $oikpos = $pdf_lasku->strlen($row["kpl"], $norm);
    $pdf_lasku->draw_text(380-$oikpos, $kala, $row["kpl"], $thispage, $norm);

    // yksikköhinta pitää olla veroton
    if ($yhtiorow["alv_kasittely"] == '') {

      // Jos meillä on marginaalimyyntiä/käänteinen alv
      $laskalv = $row["alv"];

      if ($row["alv"] >= 500) {
        $laskalv = 0;
      }

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $veroton_hinta = $row["hinta"] / (1 + $laskalv / 100);
      }
      else {
        $veroton_hinta = round($row["hinta"] / (1 + $laskalv / 100), 2);
      }
    }
    else {
      $veroton_hinta = $row["hinta"];
    }

    // A-hinta
    $oikpos = $pdf_lasku->strlen(hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $norm);
    $pdf_lasku->draw_text(455-$oikpos, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $thispage, $norm);

    //Alennus
    $alennukset_yhteensa = generoi_alekentta_php($row, 'M', 'plain');
    if ($alennukset_yhteensa != 0) {
      $oikpos = $pdf_lasku->strlen(($alennukset_yhteensa*1), $norm);
      $pdf_lasku->draw_text(510-$oikpos, $kala, ($alennukset_yhteensa*1), $thispage, $norm);
    }

    if ($kurssi != 1) {
      //Veroton Rivihinta Valuutassa
      $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $thispage, $norm);
    }
    else {
      // Veroton Rivihinta
      $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $thispage, $norm);
    }

    if (trim($row["kommentti"]) != '') {
      $kommentit = explode("\n", trim($row["kommentti"]));
      $kala -= 10;

      foreach ($kommentit as $akey => $kommentti) {

        if ($akey == 0) {
          $rpit1 = 300;
          $rpit2 = 250;
        }
        else {
          $rpit1 = 500;
          $rpit2 = 450;
        }

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {
            if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
              $pdf_lasku->draw_text(20, $kala, trim($lause), $thispage, $pieni);
              $kala = $kala - 10;
              $lause = "";
            }

            $lause .= $sana;
          }

          $kommentti = $lause;
        }

        if (trim($kommentti) != "") {
          $pdf_lasku->draw_text(20, $kala, trim($kommentti), $thispage, $pieni);
          $kala = $kala - 10;
        }
      }
      $kala += 10;
    }

    if ($tullinimike_ja_alkuperamaa != '' and $row['tullinimike'] != '') {
      $kala = lisaa_tullinimike_ja_alkuperamaa($tullinimike_ja_alkuperamaa, $row, $pdf_lasku, $thispage, $kala, $kieli);
    }

    $kala = $kala - 15;

    if ($kurssi != 1) {
      $arvo += hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_valuutassa_verollinen"], 0, $laskurow['_loppunollat']);
    }
    else {
      $arvo += hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_verollinen"], 0, $laskurow['_loppunollat']);
    }

    return array($thispage, $kala, $summa, $arvo);
  }
}

if (!function_exists('rivi_plain_2')) {
  function rivi_plain_2($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
    global $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    //Tuoteno
    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 100, $pdf_lasku, $norm);
    $pdf_lasku->draw_text(250, $kala, $ff_string, $thispage, $ff_font);

    $oikpos = $pdf_lasku->strlen($row["kpl"], $norm);
    $pdf_lasku->draw_text(380-$oikpos, $kala, $row["kpl"], $thispage, $norm);

    // yksikköhinta pitää olla veroton
    if ($yhtiorow["alv_kasittely"] == '') {

      // Jos meillä on marginaalimyyntiä/käänteinen alv
      $laskalv = $row["alv"];

      if ($row["alv"] >= 500) {
        $laskalv = 0;
      }

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $veroton_hinta = $row["hinta"] / (1 + $laskalv / 100);
      }
      else {
        $veroton_hinta = round($row["hinta"] / (1 + $laskalv / 100), 2);
      }
    }
    else {
      $veroton_hinta = $row["hinta"];
    }

    // A-hinta
    $oikpos = $pdf_lasku->strlen(hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $norm);
    $pdf_lasku->draw_text(455-$oikpos, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $thispage, $norm);

    //Alennus
    $alennukset_yhteensa = generoi_alekentta_php($row, 'M', 'plain');
    if ($alennukset_yhteensa != 0) {
      $oikpos = $pdf_lasku->strlen(($alennukset_yhteensa*1), $norm);
      $pdf_lasku->draw_text(510-$oikpos, $kala, ($alennukset_yhteensa*1), $thispage, $norm);
    }

    if ($kurssi != 1) {
      //Veroton Rivihinta Valuutassa
      $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $thispage, $norm);
    }
    else {
      // Veroton Rivihinta
      $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $thispage, $norm);
    }

    // rivitetään tuotenimitys jos se ei mahdu lokeroonsa
    // Nimitys
    if ($pdf_lasku->strlen($row['nimitys'], $norm) > 200) {
      $pohja = $pdf_lasku->draw_paragraph($kala+$norm["height"], 20, 60, 200, $row['nimitys'], $thispage, $norm);
      $kala = $pohja - $rivinkorkeus;
    }
    else {
      // lyhyt nimitys
      $pdf_lasku->draw_text(20, $kala, $row['nimitys'], $thispage, $norm);
    }

    if (trim($row["kommentti"]) != '') {
      $kommentit = explode("\n", trim($row["kommentti"]));
      $kala -= 10;

      foreach ($kommentit as $akey => $kommentti) {

        if ($akey == 0) {
          $rpit1 = 300;
          $rpit2 = 250;
        }
        else {
          $rpit1 = 500;
          $rpit2 = 450;
        }

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {
            if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
              $pdf_lasku->draw_text(20, $kala, trim($lause), $thispage, $pieni);
              $kala = $kala - 10;
              $lause = "";
            }

            $lause .= $sana;
          }

          $kommentti = $lause;
        }

        if (trim($kommentti) != "") {
          $pdf_lasku->draw_text(20, $kala, trim($kommentti), $thispage, $pieni);
          $kala = $kala - 10;
        }
      }
      $kala += 10;
    }

    if ($tullinimike_ja_alkuperamaa != '' and $row['tullinimike'] != '') {
      $kala = lisaa_tullinimike_ja_alkuperamaa($tullinimike_ja_alkuperamaa, $row, $pdf_lasku, $thispage, $kala, $kieli);
    }

    $kala = $kala - 15;

    if ($kurssi != 1) {
      $arvo += hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_valuutassa_verollinen"], 0, $laskurow['_loppunollat']);
    }
    else {
      $arvo += hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_verollinen"], 0, $laskurow['_loppunollat']);
    }

    return array($thispage, $kala, $summa, $arvo);
  }
}

if (!function_exists('rivi_viro')) {
  function rivi_viro($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
    global $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    // Nimitys
    list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 170, $pdf_lasku, $norm);
    $pdf_lasku->draw_text(35, $kala, $ff_string, $thispage, $ff_font);

    if ($laskutyyppi == 13 or $laskutyyppi == 14) {
      // Tuoteno
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 165, $pdf_lasku, $norm);
      $pdf_lasku->draw_text(220, $kala, $ff_string, $thispage, $ff_font);

      // Määrä
      $pdf_lasku->draw_text(390, $kala, (float) $row["kpl"], $thispage, $norm);
    }
    else {
      // Tuoteno
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 125, $pdf_lasku, $norm);
      $pdf_lasku->draw_text(220, $kala, $ff_string, $thispage, $ff_font);

      // Määrä
      $pdf_lasku->draw_text(350, $kala, (float) $row["kpl"], $thispage, $norm);
    }

    // SVH-hinta
    if (($laskutyyppi == 15 or $laskutyyppi == 16) and $row["myyntihinta"] != 0) {
      $pdf_lasku->draw_text(390, $kala, hintapyoristys(laskuval($row["myyntihinta"], $kurssi), 0, $laskurow['_loppunollat']), $thispage, $norm);
    }

    // yksikköhinta pitää olla veroton
    if ($kurssi != 1) {
      //Veroton yksikköhinta Valuutassa
      $veroton_hinta = hintapyoristys($row["rivihinta_valuutassa"]/$row["kpl"], 0, $laskurow['_loppunollat']);
    }
    else {
      // Veroton yksikköhinta
      $veroton_hinta = hintapyoristys($row["rivihinta"]/$row["kpl"], 0, $laskurow['_loppunollat']);
    }

    // A-hinta
    $pdf_lasku->draw_text(440, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi), 0, $laskurow['_loppunollat']), $thispage, $norm);

    if ($kurssi != 1) {
      //Veroton Rivihinta Valuutassa
      $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']), $thispage, $norm);
    }
    else {
      // Veroton Rivihinta
      $oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $norm);
      $pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']), $thispage, $norm);
    }

    //Arvonlisävero
    if ($row["alv"] >= 600) {
      $pdf_lasku->draw_text(550, $kala, t("K.V.", $kieli), $thispage, $norm);
    }
    elseif ($row["alv"] >= 500) {
      $pdf_lasku->draw_text(550, $kala, t("M.V.", $kieli), $thispage, $norm);
    }
    else {
      $pdf_lasku->draw_text(550, $kala, ($row["alv"]*1)."%", $thispage, $norm);
    }

    if ($row["myyntihinta_maara"] > 1) {
      $kala = $kala - 7;
      $pdf_lasku->draw_text(440, $kala, hintapyoristys($veroton_hinta*$row["myyntihinta_maara"], 0, $laskurow['_loppunollat'])." / $row[myyntihinta_maara] $row[yksikko]", $thispage, $pieni);
      $kalakorjaus = 7;
    }
    else {
      $kalakorjaus = 0;
    }

    // Ensimmäinen rivi piirretty
    $kala = $kala - 10;

    // Yksikkö
    if ($laskutyyppi == 13 or $laskutyyppi == 14) {
      $pdf_lasku->draw_text(390, $kala, t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selitetark"), $thispage, $pieni);
    }
    else {
      $pdf_lasku->draw_text(350, $kala, t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selitetark"), $thispage, $pieni);
    }

    //Toimitusaika
    if (substr($row["toimitettuaika"], 0, 10) != "0000-00-00" and $laskutyyppi != 6) {
      $pdf_lasku->draw_text(440, $kala, tv1dateconv(substr($row["toimitettuaika"], 0, 10)), $thispage, $pieni);
    }

    // Kommentti
    if (trim($row["kommentti"]) != '') {

      $kommentit = explode("\n", trim($row["kommentti"]));

      if ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and !preg_match("/".t("Tilaus", $kieli).":? ".$row["otunnus"]."/", $row["kommentti"])) {
        $pdf_lasku->draw_text(35, $kala+$kalakorjaus, t("Tilaus", $kieli).": ".$row["otunnus"], $thispage, $pieni);
        $kala = $kala - 10;
      }

      foreach ($kommentit as $akey => $kommentti) {

        if ($akey == 0) {
          $rpit1 = 300;
          $rpit2 = 250;
        }
        else {
          $rpit1 = 500;
          $rpit2 = 450;
        }

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {
            if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
              $pdf_lasku->draw_text(35, $kala+$kalakorjaus, trim($lause), $thispage, $pieni);
              $kala = $kala - 10;
              $lause = "";
            }

            $lause .= $sana;
          }

          $kommentti = $lause;
        }

        if (trim($kommentti) != "") {
          $pdf_lasku->draw_text(35, $kala+$kalakorjaus, trim($kommentti), $thispage, $pieni);
          $kala = $kala - 10;
        }
      }
      $kala += 10;
    }
    elseif ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and $laskutyyppi != 6) {
      $pdf_lasku->draw_text(35, $kala+$kalakorjaus, t("Tilaus", $kieli).": ".$row["otunnus"], $thispage, $pieni);
    }

    if ($tullinimike_ja_alkuperamaa != '' and $row['tullinimike'] != '') {
      $kala = lisaa_tullinimike_ja_alkuperamaa($tullinimike_ja_alkuperamaa, $row, $pdf_lasku, $thispage, $kala, $kieli);
    }

    $kala = $kala - 15;

    if ($kurssi != 1) {
      $arvo += hintapyoristys($row["rivihinta_valuutassa"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_valuutassa_verollinen"], 0, $laskurow['_loppunollat']);
    }
    else {
      $arvo += hintapyoristys($row["rivihinta"], 0, $laskurow['_loppunollat']);
      $summa += hintapyoristys($row["rivihinta_verollinen"], 0, $laskurow['_loppunollat']);
    }

    return array($thispage, $kala, $summa, $arvo);
  }
}

if (!function_exists('rivi_perhe')) {
  function rivi_perhe($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
    global $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    $lisataa = 0;

    if ($row["perheid"] > 0) {
      // kyseessä on isä
      if ($row["perheid"] == $row["tunnus"]) {

        $query_ale_lisa = generoi_alekentta('M');

        if ($toim == 'PROFORMA') {
          $lisa = " sum(tilausrivi.hinta
                      / if ('$yhtiorow[alv_kasittely]' = '' AND tilausrivi.alv < 500,
                          (1 + tilausrivi.alv / 100),
                          1)
                        * (tilausrivi.varattu + tilausrivi.kpl)
                        * {$query_ale_lisa}) rivihinta,
                    round(sum(tilausrivi.hinta
                        * (tilausrivi.varattu + tilausrivi.kpl)
                        * {$query_ale_lisa})
                      / $row[kpl], '$yhtiorow[hintapyoristys]') hinta,";
        }
        else {
          $lisa = " sum(tilausrivi.rivihinta) rivihinta,
                    round(sum(tilausrivi.hinta
                        * (tilausrivi.varattu + tilausrivi.kpl)
                        * {$query_ale_lisa})
                      / $row[kpl], '$yhtiorow[hintapyoristys]') hinta,";
        }

        // lasketaan isätuotteen riville lapsien hinnat yhteen
        $query = "SELECT
                  $lisa
                  round(sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500, (1 + tilausrivi.alv / 100), 1) * (tilausrivi.varattu + tilausrivi.kpl) * {$query_ale_lisa}), $yhtiorow[hintapyoristys]) rivihinta_verollinen,
                  sum((tilausrivi.hinta / lasku.vienti_kurssi) / if ('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500, (1 + tilausrivi.alv / 100), 1) * (tilausrivi.varattu + tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa,
                  sum((tilausrivi.hinta / lasku.vienti_kurssi) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1 + tilausrivi.alv / 100), 1) * (tilausrivi.varattu + tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa_verollinen
                  FROM tilausrivi
                  JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                  WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
                  AND tilausrivi.uusiotunnus = '$row[uusiotunnus]'
                  AND tilausrivi.perheid     IN ($row[perheideet])
                  AND tilausrivi.perheid     > 0";
        $riresult = pupe_query($query);
        $perherow = mysql_fetch_assoc($riresult);

        $row["hinta"] = $perherow["hinta"];
        $row["rivihinta"] = $perherow["rivihinta"];
        $row["rivihinta_verollinen"] = $perherow["rivihinta_verollinen"];
        $row["rivihinta_valuutassa"] = $perherow["rivihinta_valuutassa"];
        $row["rivihinta_valuutassa_verollinen"] = $perherow["rivihinta_valuutassa_verollinen"];

        // Nollataan alet, koska hinta lasketaan rivihinnasta jossa alet on jo huomioitu
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $row["ale{$alepostfix}"] = "";
        }

        $row["erikoisale"] = "";
      }
      else {
        if ($laskutyyppi == 19) {
          //lapset lisätään ilman hintoja
          $lisataa = 0;
          //asetetaan alkioihin string null, jotta hintapyöristys osaa palauttaa: ''
          $row["hinta"] = 'null';
          $row["rivihinta"] = 'null';
          $row["rivihinta_verollinen"] = 'null';
          $row["rivihinta_valuutassa"] = 'null';
          $row["rivihinta_valuutassa_verollinen"] = 'null';
          $row['toimitettuaika'] = "";

          for ($alepostfix = 0; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $row['ale'.$alepostfix] = '';
          }
        }
        else {
          // lapsia ei lisätä
          $lisataa = 1;
        }
      }
    }

    if ($lisataa == 0) {
      if ($laskutyyppi == 14 or $laskutyyppi == 16) {
        list($thispage, $kala, $summa, $arvo) = rivi_viro($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
      }
      else {
        list($thispage, $kala, $summa, $arvo) = rivi_original($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
      }
    }

    return array($thispage, $kala, $summa, $arvo);
  }
}

if (!function_exists('loppu')) {
  function loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $thispage, $masrow, $sivu, $summa, $arvo, $page, $vientikala, $laskutyyppi, $vikasivu = "") {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    if ($yhtiorow["viivakoodi_laskulle"] == "K" and $laskurow["maa"] == "FI" and $laskurow["summa"] > 0 and $vikasivu != "" and function_exists("imagecreate") and $laskurow["vienti"] == "" and strtoupper($laskurow["maa"]) == strtoupper($yhtiorow["maa"])) {
      $piirra_viivakoodi = true;
      $barcode_korkeus = 50;
    }
    else {
      $piirra_viivakoodi = false;
      $barcode_korkeus = 0;
    }

    $mainostxt_lasku_korkeus = 0;

    if ($laskurow['mainosteksti'] != '') {
      $viestikala = $laskutyyppi == 20 ? 190 : 150;

      $mainostekstit = explode("\n", wordwrap($laskurow['mainosteksti'], 115));

      foreach ($mainostekstit as $mainostxt) {

        if ($mainostxt_lasku_korkeus == 30) break;

        list($ff_string, $ff_font) = pdf_fontfit($mainostxt, 380, $pdf_lasku, $pieni);

        $oikpos = $pdf_lasku->strlen($ff_string, $ff_font);

        if ($laskutyyppi == 20) {
          $pdf_lasku->draw_text(210, $viestikala + $vientikala + $barcode_korkeus, $ff_string, $thispage, $ff_font);
        }
        else {
          $pdf_lasku->draw_text(210, $viestikala + $vientikala + $barcode_korkeus, $ff_string, $thispage, $ff_font);
        }

        $viestikala -= 10;
        $mainostxt_lasku_korkeus += 10;
      }
    }

    if ($vikasivu != "") {
      for ($pp=1; $pp<=$sivu; $pp++) {
        $pdf_lasku->draw_text(500, 815, t("Sivu", $kieli).": $pp / $sivu", $page[$pp], $norm);
      }
    }

    if ($yhtiorow['lomakkeiden_allekirjoitus'] == 'X') {
      $allekirjoitus_query = "SELECT nimi,
                              asema
                              FROM kuka
                              WHERE yhtio = '{$kukarow['yhtio']}'
                              AND tunnus  = '{$laskurow['allekirjoittaja']}'";
      $allekirjoitus_result = pupe_query($allekirjoitus_query);
      $allekirjoitus_row = mysql_fetch_assoc($allekirjoitus_result);

      $pdf_lasku->draw_text(30, 142+$vientikala+$barcode_korkeus, $allekirjoitus_row['nimi'], $thispage, $norm);
      $pdf_lasku->draw_text(30, 132+$vientikala+$barcode_korkeus, $allekirjoitus_row['asema'], $thispage, $norm);
    }

    if ($laskutyyppi == 20) {
      $comments = explode("\n", wordwrap($laskurow["comments"], 85));

      $pdf_lasku->draw_rectangle(150 + $vientikala + $barcode_korkeus, 207,
        110 + $vientikala + $barcode_korkeus, 580, $thispage, $rectparam);
      $pdf_lasku->draw_text(217, 142 + $vientikala + $barcode_korkeus, t("Lisätietoja", $kieli),
        $thispage, $pieni);

      $kommenttikala = 130;

      foreach ($comments as $comment) {
        if ($kommenttikala < 120) break;

        $pdf_lasku->draw_text(217, $kommenttikala + $vientikala + $barcode_korkeus,
          pdf_substr($comment, 363, $pdf_lasku, $norm), $thispage,
          $norm);

        $kommenttikala -= 10;
      }
    }

    if (!kyseessa_eramaksu($laskurow)) {
      //yhteensärivi
      $pdf_lasku->draw_rectangle(110 + $vientikala + $barcode_korkeus, 20,
        90 + $vientikala + $barcode_korkeus, 580, $thispage, $rectparam);
      $pdf_lasku->draw_rectangle(110 + $vientikala + $barcode_korkeus, 207,
        90 + $vientikala + $barcode_korkeus, 580, $thispage, $rectparam);
      $pdf_lasku->draw_rectangle(110 + $vientikala + $barcode_korkeus, 394,
        90 + $vientikala + $barcode_korkeus, 580, $thispage, $rectparam);
      $pdf_lasku->draw_rectangle(110 + $vientikala + $barcode_korkeus, 540,
        90 + $vientikala + $barcode_korkeus, 580, $thispage, $rectparam);

      $pdf_lasku->draw_text(30, 102 + $vientikala + $barcode_korkeus, t("Eräpäivä", $kieli),
        $thispage, $pieni);

      if (kyseessa_eramaksu($laskurow)) {
        $erapaiva = tv1dateconv($laskurow["erpcm"]);
      }
      elseif ($laskurow["kateistyyppi"] != '') {
        $erapaiva = t('MAKSETTU', $kieli);
      }
      else {
        $erapaiva = tv1dateconv($laskurow["erpcm"]);
      }

      $pdf_lasku->draw_text(30, 92 + $vientikala + $barcode_korkeus, $erapaiva, $thispage,
        ($laskutyyppi == 17 ? $boldi : $norm));

      if ($laskutyyppi != 8) {
        $pdf_lasku->draw_text(217, 102 + $vientikala + $barcode_korkeus, t("Viitenumero", $kieli),
          $thispage, $pieni);
        $pdf_lasku->draw_text(217, 92 + $vientikala + $barcode_korkeus, $laskurow["viite"],
          $thispage, $boldi);
      }

      if ($vikasivu != "") {
        $pdf_lasku->draw_text(404, 92 + $vientikala + $barcode_korkeus, t("Yhteensä", $kieli) . ":",
          $thispage, $norm);

        if ($toim == 'PROFORMA') {
          if ($yhtiorow["laskunsummapyoristys"] != '' or $asiakasrow["laskunsummapyoristys"] != ''
          ) {
            $summa = round($summa, 0);
          }

          $pdf_lasku->draw_text(490, 92 + $vientikala + $barcode_korkeus, sprintf('%.2f', $summa),
            $thispage, $boldi);
        }
        elseif ($laskurow["valkoodi"] != '' and
          trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))
        ) {
          $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $laskurow["summa_valuutassa"]), $boldi);
          $pdf_lasku->draw_text(535 - $oikpos, 92 + $vientikala + $barcode_korkeus,
            sprintf('%.2f', $laskurow["summa_valuutassa"]), $thispage, $boldi);
        }
        else {
          $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $laskurow["summa"]), $boldi);
          $pdf_lasku->draw_text(535 - $oikpos, 92 + $vientikala + $barcode_korkeus,
            sprintf('%.2f', $laskurow["summa"]), $thispage, $boldi);
        }

        $pdf_lasku->draw_text(550, 92 + $vientikala + $barcode_korkeus, $laskurow["valkoodi"],
          $thispage, $boldi);
      }
      else {
        $pdf_lasku->draw_text(470, 92 + $vientikala + $barcode_korkeus, t("Jatkuu", $kieli) . "...",
          $thispage, $boldi);
      }

      //Haetaan factoringsopimuksen tiedot
      if (isset($masrow["factoring_id"])) {
        $query = "SELECT *
                  FROM factoring
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and tunnus   = '$masrow[factoring_id]'
                  and valkoodi = '$laskurow[valkoodi]'";
        $fres = pupe_query($query);
        $frow = mysql_fetch_assoc($fres);
      }
      else {
        unset($frow);
      }

      $pankkitiedot = array();

      //Laitetaan pankkiyhteystiedot kuntoon
      if (isset($masrow["factoring_id"])) {
        $pankkitiedot["pankkinimi1"] = $frow["pankkinimi1"];
        $pankkitiedot["pankkitili1"] = $frow["pankkitili1"];
        $pankkitiedot["pankkiiban1"] = $frow["pankkiiban1"];
        $pankkitiedot["pankkiswift1"] = $frow["pankkiswift1"];
        $pankkitiedot["pankkinimi2"] = $frow["pankkinimi2"];
        $pankkitiedot["pankkitili2"] = $frow["pankkitili2"];
        $pankkitiedot["pankkiiban2"] = $frow["pankkiiban2"];
        $pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
        $pankkitiedot["pankkinimi3"] = "";
        $pankkitiedot["pankkitili3"] = "";
        $pankkitiedot["pankkiiban3"] = "";
        $pankkitiedot["pankkiswift3"] = "";
      }
      elseif ($masrow["pankkinimi1"] != "") {
        $pankkitiedot["pankkinimi1"] = $masrow["pankkinimi1"];
        $pankkitiedot["pankkitili1"] = $masrow["pankkitili1"];
        $pankkitiedot["pankkiiban1"] = $masrow["pankkiiban1"];
        $pankkitiedot["pankkiswift1"] = $masrow["pankkiswift1"];
        $pankkitiedot["pankkinimi2"] = $masrow["pankkinimi2"];
        $pankkitiedot["pankkitili2"] = $masrow["pankkitili2"];
        $pankkitiedot["pankkiiban2"] = $masrow["pankkiiban2"];
        $pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
        $pankkitiedot["pankkinimi3"] = $masrow["pankkinimi3"];
        $pankkitiedot["pankkitili3"] = $masrow["pankkitili3"];
        $pankkitiedot["pankkiiban3"] = $masrow["pankkiiban3"];
        $pankkitiedot["pankkiswift3"] = $masrow["pankkiswift3"];
      }
      else {
        $pankkitiedot["pankkinimi1"] = $yhtiorow["pankkinimi1"];
        $pankkitiedot["pankkitili1"] = $yhtiorow["pankkitili1"];
        $pankkitiedot["pankkiiban1"] = $yhtiorow["pankkiiban1"];
        $pankkitiedot["pankkiswift1"] = $yhtiorow["pankkiswift1"];
        $pankkitiedot["pankkinimi2"] = $yhtiorow["pankkinimi2"];
        $pankkitiedot["pankkitili2"] = $yhtiorow["pankkitili2"];
        $pankkitiedot["pankkiiban2"] = $yhtiorow["pankkiiban2"];
        $pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
        $pankkitiedot["pankkinimi3"] = $yhtiorow["pankkinimi3"];
        $pankkitiedot["pankkitili3"] = $yhtiorow["pankkitili3"];
        $pankkitiedot["pankkiiban3"] = $yhtiorow["pankkiiban3"];
        $pankkitiedot["pankkiswift3"] = $yhtiorow["pankkiswift3"];
      }

      if ($vientikala == 0) {
        $tileja = -20;
      }
      else {
        $tileja = 0;
      }

      // ei factoroida niin normitiedot
      $pdf_lasku->draw_text(30, 102 + $tileja + $barcode_korkeus, t("Pankkiyhteys", $kieli),
        $thispage, $pieni);

      $pdf_lasku->draw_text(30, 92 + $tileja + $barcode_korkeus,
        $pankkitiedot["pankkinimi1"] . "  " . $pankkitiedot["pankkitili1"],
        $thispage, $norm);

      if ($pankkitiedot["pankkiiban1"]) {
        $pdf_lasku->draw_text(217, 92 + $tileja + $barcode_korkeus,
          "IBAN: " . $pankkitiedot["pankkiiban1"], $thispage, $norm);
      }
      if ($pankkitiedot["pankkiswift1"]) {
        $pdf_lasku->draw_text(404, 92 + $tileja + $barcode_korkeus,
          "SWIFT: " . $pankkitiedot["pankkiswift1"], $thispage, $norm);
      }

      $pdf_lasku->draw_text(30, 82 + $tileja + $barcode_korkeus,
        $pankkitiedot["pankkinimi2"] . "  " . $pankkitiedot["pankkitili2"],
        $thispage, $norm);

      if ($pankkitiedot["pankkiiban2"]) {
        $pdf_lasku->draw_text(217, 82 + $tileja + $barcode_korkeus,
          "IBAN: " . $pankkitiedot["pankkiiban2"], $thispage, $norm);
      }
      if ($pankkitiedot["pankkiswift2"]) {
        $pdf_lasku->draw_text(404, 82 + $tileja + $barcode_korkeus,
          "SWIFT: " . $pankkitiedot["pankkiswift2"], $thispage, $norm);
      }

      $pdf_lasku->draw_text(30, 72 + $tileja + $barcode_korkeus,
        $pankkitiedot["pankkinimi3"] . "  " . $pankkitiedot["pankkitili3"],
        $thispage, $norm);

      if ($pankkitiedot["pankkiiban3"]) {
        $pdf_lasku->draw_text(217, 72 + $tileja + $barcode_korkeus,
          "IBAN: " . $pankkitiedot["pankkiiban3"], $thispage, $norm);
      }
      if ($pankkitiedot["pankkiswift3"]) {
        $pdf_lasku->draw_text(404, 72 + $tileja + $barcode_korkeus,
          "SWIFT: " . $pankkitiedot["pankkiswift3"], $thispage, $norm);
      }

      if ($pankkitiedot["pankkinimi2"] . $pankkitiedot["pankkitili2"] .
        $pankkitiedot["pankkiiban2"] != '' and $vientikala == 0
      ) {
        $tileja -= 10;
      }

      if ($pankkitiedot["pankkinimi3"] . $pankkitiedot["pankkitili3"] .
        $pankkitiedot["pankkitili3"] != '' and $vientikala == 0
      ) {
        $tileja -= 10;
      }

      if ($vientikala == 0) {
        $tileja += 20;
      }

      //Pankkiyhteystiedot
      $pdf_lasku->draw_rectangle(90 + $vientikala + $barcode_korkeus, 20,
        20 + $tileja + $barcode_korkeus, 580, $thispage, $rectparam);
    }

    if (!empty($laskurow["yhtio_toimipaikka"])) {
      $query = "SELECT vat_numero
                FROM yhtion_toimipaikat
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus  = '{$laskurow["yhtio_toimipaikka"]}'";

      $toimipaikka_result = pupe_query($query);

      $toimipaikkarow = mysql_fetch_assoc($toimipaikka_result);
    }

    // yhtiötiedot
    $y_tiedot_ok = FALSE;
    $y_nimi = $laskurow["yhtio_nimi"];
    $y_osoite = $laskurow["yhtio_osoite"];
    $y_postino = $laskurow["yhtio_postino"];
    $y_postitp = $laskurow["yhtio_postitp"];
    $y_maa = $laskurow["yhtio_maa"];
    $y_kotipaikka = $laskurow["yhtio_kotipaikka"];

    if ($laskurow['yhtio_maa'] == 'EE') {
      $y_vatnumero = $yhtiorow["ytunnus"];
    }
    elseif (!empty($toimipaikkarow["vat_numero"])) {
      $y_vatnumero = preg_replace("/^0037/", "", $toimipaikkarow["vat_numero"]);
    }
    else {
      $y_vatnumero = preg_replace("/^0037/", "", $laskurow["yhtio_ovttunnus"]);
    }
    $y_iban = $yhtiorow['pankkiiban1'];
    $y_swift = $yhtiorow['pankkiswift1'];

    if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and tunnus='$laskurow[yhtio_toimipaikka]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok = TRUE;
        $y_puhelin = $alhiro['puhelin'];
        $y_fax = $alhiro['fax'];
        $y_email = $alhiro['email'];
        $y_www = $alhiro['www'];
      }
    }

    // jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
    if ($laskurow["yhtio_ovttunnus"] != "" and $laskurow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok = TRUE;
        $y_puhelin = $alhiro['puhelin'];
        $y_fax = $alhiro['fax'];
        $y_email = $alhiro['email'];
        $y_www = $alhiro['www'];
      }
    }

    // jos ei olla haettu muualta, niin haetaan tiedot yhtiöltä
    if (!$y_tiedot_ok) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$laskurow[yhtio]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_puhelin = $alhiro['puhelin'];
        $y_fax = $alhiro['fax'];
        $y_email = $alhiro['email'];
        $y_www = $alhiro['www'];
      }
    }

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf_lasku->draw_rectangle(70 + $tileja + $barcode_korkeus, 20, 20 + $tileja + $barcode_korkeus, 580, $thispage, $rectparam);
    $pdf_lasku->draw_rectangle(70 + $tileja + $barcode_korkeus, 207, 20 + $tileja + $barcode_korkeus, 580, $thispage, $rectparam);
    $pdf_lasku->draw_rectangle(70 + $tileja + $barcode_korkeus, 394, 20 + $tileja + $barcode_korkeus, 580, $thispage, $rectparam);

    $pdf_lasku->draw_text(30, 55 + $tileja + $barcode_korkeus, $y_nimi, $thispage, $pieni);
    $pdf_lasku->draw_text(30, 45 + $tileja + $barcode_korkeus, $y_osoite, $thispage, $pieni);
    $pdf_lasku->draw_text(30, 35 + $tileja + $barcode_korkeus, $y_postino."  ".$y_postitp, $thispage, $pieni);

    $maan_tiedot = hae_maa(array('maakoodi' => $y_maa));
    $pdf_lasku->draw_text(30, 25 + $tileja + $barcode_korkeus, $maan_tiedot['nimi'], $thispage, $pieni);

    $pdf_lasku->draw_text(217, 55 + $tileja + $barcode_korkeus, t("Puhelin", $kieli).": $y_puhelin", $thispage, $pieni);
    $pdf_lasku->draw_text(217, 45 + $tileja + $barcode_korkeus, t("Sähköposti", $kieli).": $y_email", $thispage, $pieni);
    $pdf_lasku->draw_text(217, 35 + $tileja + $barcode_korkeus, t("Web", $kieli).": $y_www", $thispage, $pieni);
    if (!empty($y_fax)) $pdf_lasku->draw_text(217, 25 + $tileja + $barcode_korkeus, t("Fax", $kieli).": $y_fax", $thispage, $pieni);

    $pdf_lasku->draw_text(404, 55 + $tileja + $barcode_korkeus, t("Y-tunnus", $kieli).": ".tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $thispage, $pieni);
    $pdf_lasku->draw_text(404, 45 + $tileja + $barcode_korkeus, t("Kotipaikka", $kieli).": $y_kotipaikka", $thispage, $pieni);

    if (strtoupper($y_maa) == 'FI') {
      $pdf_lasku->draw_text(404, 35 + $tileja + $barcode_korkeus, t("Enn.per.rek", $kieli), $thispage, $pieni);
      $pdf_lasku->draw_text(404, 25 + $tileja + $barcode_korkeus, t("Alv.rek", $kieli), $thispage, $pieni);
    }
    else {
      $pdf_lasku->draw_text(404, 35 + $tileja + $barcode_korkeus, t("Ovt-tunnus", $kieli).": ".$laskurow["yhtio_ovttunnus"], $thispage, $pieni);
    }

    // Viivakoodin piirtaminen
    if ($piirra_viivakoodi) {

      $width = 340;
      $height = $barcode_korkeus-1 * 1.2;
      $style = BCS_IMAGE_JPEG;

      $onko_tili1_iban = tarkista_iban($pankkitiedot["pankkitili1"]);
      $onko_iban1_iban = tarkista_iban($pankkitiedot["pankkiiban1"]);

      if ($onko_tili1_iban != "") {
        $code = PalautaPankkiviivakoodi($onko_tili1_iban, $laskurow['summa'], $laskurow['erpcm'], $laskurow["viite"], 4);
      }
      elseif ($onko_iban1_iban != "") {
        $code = PalautaPankkiviivakoodi($onko_iban1_iban, $laskurow['summa'], $laskurow['erpcm'], $laskurow["viite"], 4);
      }
      else {
        $code = PalautaPankkiviivakoodi($pankkitiedot["pankkitili1"], $laskurow['summa'], $laskurow['erpcm'], $laskurow["viite"], 2);
      }

      $font = 5;
      $xres = 1;

      $obj = new C128CObject($width, $height, $style, $code);
      $filename = "/tmp/".md5(uniqid(rand(), true)).".jpg";

      if ($obj) {
        $obj->SetFont($font);
        $obj->DrawObject($xres);
        $obj->SaveObject($filename);
        $obj->DestroyObject();
        unset($obj);
      }
      $fh = fopen($filename, "r");
      $data = fread($fh, filesize($filename));
      fclose($fh);

      if ($data) {
        $image = $pdf_lasku->jfif_embed($data);

        if ($image) {
          $logoparam = array();
          $logoparam['scale'] = 0.9;
          $placement = $pdf_lasku->image_place($image, 3, 160, $thispage, $logoparam);
        }
      }
      else {
        $pdf_lasku->draw_text(270, 10, "Virhe viivakoodin luomisessa", $thispage, $boldi);
      }

      system("rm -f $filename");
    }
  }
}

if (!function_exists('alvierittely')) {
  function alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $thispage, $toim, $kala, $summa, $arvo, $laskutyyppi, $kassa_ale = '', $yhteensamaara=0) {
    global $yhtiorow, $kukarow;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    $kassa_ale_lisa = '';
    $kassa_ale_lisateksti = '';

    if ($kassa_ale != '') {
      $kassa_ale = (float) $kassa_ale;
      $kassa_ale_lisa = " * (1-$kassa_ale/100) ";

      $kala -=10;
      $pdf_lasku->draw_text(360, $kala, t("Kassa-alennuksella", $kieli), $thispage, $norm);
      $kala -= 10;
    }

    if ($yhtiorow["naytayhteensamaarat"] == "K" and $kassa_ale == '') {
      $pdf_lasku->draw_text(190, $kala, t("Tuotteita yhteensä", $kieli).":", $thispage, $norm);
      $pdf_lasku->draw_text(340, $kala, sprintf('%.2f', $yhteensamaara), $thispage, $norm);
      $kala -=10;
    }

    //loppusummat vain vikalle sivulle

    if ($toim == 'PROFORMA') {
      $where = " tilausrivi.otunnus='$laskurow[tunnus]' and tilausrivi.tyyppi in ('L','W','E') and ((tilausrivi.varattu+tilausrivi.jt != 0 and tilausrivi.laskutettuaika = '0000-00-00') or (tilausrivi.kpl != 0 and tilausrivi.laskutettuaika != '0000-00-00'))";
    }
    else {
      $where = " tilausrivi.uusiotunnus = '$laskurow[tunnus]' and tilausrivi.tyyppi='L' ";
    }

    //Haetaan kaikki alvikannat riveiltä
    $alvquery = "SELECT distinct alv
                 FROM tilausrivi
                 WHERE $where
                 and yhtio = '$kukarow[yhtio]'
                 ORDER BY alv";
    $alvresult = pupe_query($alvquery);

    // Haluataanko näytätä yhteensäsumma ilman alennuksia
    // 23 = "Normaali laskupohja (Näytetään myös yhteensä ilman alennuksia)",
    if ($laskutyyppi == '23') {
      $alequery = "SELECT sum(kpl * hinta) as hinta_ilman_alennuksia
                   FROM tilausrivi
                   WHERE {$where}
                   and yhtio = '{$kukarow['yhtio']}'";
      $ilman_ale_result = pupe_query($alequery);
      $ilman_ale_row = mysql_fetch_assoc($ilman_ale_result);
      $yhteensa_ilman_aleja = $ilman_ale_row['hinta_ilman_alennuksia'];

      $pdf_lasku->draw_text(360, $kala, t("Yhteensä ilman alennuksia", $kieli).":", $thispage, $norm);
      $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $yhteensa_ilman_aleja), $norm);
      $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $yhteensa_ilman_aleja), $thispage, $norm);
      $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);

      $kala -= 10;
    }

    if (!(($laskutyyppi == 21 or $laskutyyppi == 22) and $laskurow['vienti'] != "")) {
      $pdf_lasku->draw_text(360, $kala, t("Veroton arvo yhteensä", $kieli).":", $thispage, $norm);

      if ($toim == 'PROFORMA') {
        $arvo = $kassa_ale != '' ? $arvo * (1-$kassa_ale/100) : $arvo;

        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arvo), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo), $thispage, $norm);
      }
      elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $arvo_valuutassa = $kassa_ale != '' ? $laskurow["arvo_valuutassa"] * (1-$kassa_ale/100) : $laskurow["arvo_valuutassa"];

        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arvo_valuutassa), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo_valuutassa), $thispage, $norm);
      }
      else {
        $arvo = $kassa_ale != '' ? $laskurow["arvo"] * (1-$kassa_ale/100) : $laskurow["arvo"];

        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arvo), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo), $thispage, $norm);
      }

      $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);

      $kala -= 10;

      $query_ale_lisa = generoi_alekentta('M');

      while ($alvrow = mysql_fetch_assoc($alvresult)) {

        // Valuuttajutut kuntoon
        if ($alvrow["alv"] >= 500 and $toim != 'PROFORMA') {
          $aquery = "SELECT
                     sum(0) alvrivihinta,
                     sum(round(tilausrivi.rivihinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1),2)) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and $where";
        }
        elseif ($alvrow["alv"] >= 500) {
          $aquery = "SELECT
                     sum(0) alvrivihinta,
                     sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) * tilausrivi.varattu * {$query_ale_lisa},2)) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and $where";
        }
        elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
          $aquery = "SELECT
                     round(sum(tilausrivi.rivihinta $kassa_ale_lisa * (tilausrivi.alv / 100)),2) alvrivihinta,
                     sum(tilausrivi.rivihinta $kassa_ale_lisa) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and $where";
        }
        elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
          $aquery = "SELECT
                     round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                     sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa},2)) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and $where";
        }
        else {
          $aquery = "SELECT
                     round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                     sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},2)) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and $where";
        }
        $aresult = pupe_query($aquery);
        $arow = mysql_fetch_assoc($aresult);

        $pdf_lasku->draw_text(360, $kala, t("Arvonlisävero", $kieli)." (".sprintf('%.2f', $arow["rivihinta"])."): ", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arow["alvrivihinta"]), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arow["alvrivihinta"]), $thispage, $norm);

        if ($alvrow["alv"] >= 600) {
          $pdf_lasku->draw_text(550, $kala, t("K.V.", $kieli), $thispage, $norm);
        }
        elseif ($alvrow["alv"] >= 500) {
          $pdf_lasku->draw_text(550, $kala, t("M.V.", $kieli), $thispage, $norm);
        }
        else {
          $pdf_lasku->draw_text(550, $kala, ($alvrow["alv"]*1)."%", $thispage, $norm);
        }

        $kala -= 10;
      }
    }

    $yhteensa_teksti = kyseessa_eramaksu($laskurow) ? "Yhteensä" : "Lasku Yhteensä";

    if (in_array($laskutyyppi, array(21, 22))) {
      $kala -= 30;
      $pdf_lasku->draw_text(360, $kala, t($yhteensa_teksti, $kieli).":", $thispage, $boldi);
    }
    else {
      $pdf_lasku->draw_text(360, $kala, t($yhteensa_teksti, $kieli).":", $thispage, $norm);
    }

    if ($toim == 'PROFORMA') {
      $summa = $kassa_ale != '' ? $summa * (1-$kassa_ale/100) : $summa;

      $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa), $norm);
      $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), $thispage, $norm);
      $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
    }
    elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $summa_valuutassa = $kassa_ale != '' ? ($laskurow["summa_valuutassa"]+$laskurow["pyoristys_valuutassa"]) * (1-$kassa_ale/100) : $laskurow["summa_valuutassa"]+$laskurow["pyoristys_valuutassa"];

      if (in_array($laskutyyppi, array(21, 22))) {
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa_valuutassa), $boldi);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa), $thispage, $boldi);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $boldi);
      }
      else {
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa_valuutassa), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }

      if ($laskurow["pyoristys_valuutassa"] != 0) {

        $pyoristys_valuutassa = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys_valuutassa"];

        $kala -= 10;
        $pdf_lasku->draw_text(360, $kala, t("Pyöristys", $kieli).":", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $pyoristys_valuutassa * -1), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $pyoristys_valuutassa * -1), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
        $kala -= 10;

        $pdf_lasku->draw_text(360, $kala, t("Yhteensä", $kieli).":", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }
    }
    else {
      $summa = $kassa_ale != '' ? ($laskurow["summa"]+$laskurow["pyoristys"]) * (1-$kassa_ale/100) : $laskurow["summa"]+$laskurow["pyoristys"];

      if (in_array($laskutyyppi, array(21, 22))) {
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa), $boldi);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), $thispage, $boldi);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $boldi);
      }
      else {
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }

      if ($laskurow["pyoristys"] != 0) {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];

        $kala -= 10;
        $pdf_lasku->draw_text(360, $kala, t("Pyöristys", $kieli).":", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $pyoristys * -1), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $pyoristys * -1), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
        $kala -= 10;

        $pdf_lasku->draw_text(360, $kala, t("Yhteensä", $kieli).":", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa - $pyoristys), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa - $pyoristys), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }
    }

    $kala -= 10;

    // Käteismaksutavat ja osasuoritukset - erittely
    if (kyseessa_eramaksu($laskurow) and $toim != 'PROFORMA') {
      $kala -= 10;

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys_valuutassa"];
        $pdf_lasku->draw_text(360, $kala, t("Osasuoritukset yhteensä", $kieli).":", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $laskurow['saldo_maksettu_valuutassa']), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $laskurow['saldo_maksettu_valuutassa'] - $pyoristys * -1), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }
      else {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];
        $pdf_lasku->draw_text(360, $kala, t("Osasuoritukset yhteensä", $kieli).":", $thispage, $norm);
        $oikpos = $pdf_lasku->strlen(sprintf('%.2f', $laskurow['saldo_maksettu']), $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $laskurow['saldo_maksettu'] - $pyoristys * -1), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }

      $kala -= 10;

      $tilinolisa = '';

      // Etsitään kassalippaan tilinumerot
      $query = "SELECT kassa, pankkikortti, luottokortti
                FROM kassalipas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['kassalipas']}'";
      $keijo = pupe_query($query);
      $ressukka = mysql_fetch_assoc($keijo);

      if (!empty($ressukka['kassa']))        $tilinolisa .= "'{$ressukka['kassa']}',";
      if (!empty($ressukka['pankkikortti'])) $tilinolisa .= "'{$ressukka['pankkikortti']}',";
      if (!empty($ressukka['luottokortti'])) $tilinolisa .= "'{$ressukka['luottokortti']}',";

      $tilinolisa = substr($tilinolisa, 0, -1);

      if ($tilinolisa != "") {
        $query = "SELECT *
                  FROM tiliointi USE INDEX (tositerivit_index)
                  WHERE yhtio   = '$kukarow[yhtio]'
                  AND ltunnus   = '$laskurow[tunnus]'
                  AND tilino    in ({$tilinolisa})
                  AND korjattu  = ''
                  AND tilino   != ''";
        $lasktilitre = pupe_query($query);

        while ($lasktilitro = mysql_fetch_assoc($lasktilitre)) {
          $ueloes = '';
          $paivvaa = tv1dateconv($lasktilitro["tapvm"]);

          $osasuoritustapanimet = array();
          $osasuoritustapanimet[] = t("Käteisellä", $kieli);
          $osasuoritustapanimet[] = t("Pankkikortilla", $kieli);
          $osasuoritustapanimet[] = t("Luottokortilla", $kieli);

          // Haetaan pisin osasuoritustavan nimitys
          $olen_pena = 0;
          foreach ($osasuoritustapanimet as $arggi) {
            $pitkulainen = $pdf_lasku->strlen($arggi, $norm);
            if ($pitkulainen > $olen_pena) $olen_pena = $pitkulainen;
          }

          if ($lasktilitro['tilino'] == $ressukka['kassa'])            $ueloes = t("Käteisellä", $kieli);
          elseif ($lasktilitro['tilino'] == $ressukka['pankkikortti']) $ueloes = t("Pankkikortilla", $kieli);
          elseif ($lasktilitro['tilino'] == $ressukka['luottokortti']) $ueloes = t("Luottokortilla", $kieli);

          if ($lasktilitro["summa_valuutassa"] != 0 and $lasktilitro["valkoodi"] != $yhtiorow["valkoodi"] and $lasktilitro["valkoodi"] != "") {
            $ueloesrivi = sprintf('%.2f', $lasktilitro['summa_valuutassa'] - $pyoristys * -1);
            $pdf_lasku->draw_text(360, $kala, " - ".$ueloes, $thispage, $norm);

            $oikpos = $pdf_lasku->strlen($ueloesrivi, $norm);
            $pdf_lasku->draw_text(535-$oikpos, $kala, $ueloesrivi , $thispage, $norm);
            $pdf_lasku->draw_text(550, $kala, $lasktilitro["valkoodi"], $thispage, $norm);
          }
          else {
            $ueloesrivi = sprintf('%.2f', $lasktilitro['summa'] - $pyoristys * -1);
            $pdf_lasku->draw_text(360, $kala, " - ".$ueloes, $thispage, $norm);
            $oikpos = $pdf_lasku->strlen($paivvaa, $norm);
            $pdf_lasku->draw_text(370+$olen_pena , $kala, $paivvaa, $thispage, $norm);
            $oikpos = $pdf_lasku->strlen($ueloesrivi, $norm);
            $pdf_lasku->draw_text(535-$oikpos, $kala, $ueloesrivi, $thispage, $norm);
            $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
          }

          $kala -= 10;
        }
      }

      $kala -= 10;

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys_valuutassa"];
        $pdf_lasku->draw_text(360, $kala, t("Erämaksulla", $kieli).":", $thispage, $norm);
        $summeli = sprintf('%.2f', $laskurow["summa_valuutassa"]-$laskurow["saldo_maksettu_valuutassa"]);
        $oikpos = $pdf_lasku->strlen($summeli, $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summeli - $pyoristys * -1), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }
      else {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];
        $pdf_lasku->draw_text(360, $kala, t("Erämaksulla", $kieli).":", $thispage, $norm);
        $summeli = sprintf('%.2f', $laskurow["summa"]-$laskurow["saldo_maksettu"]);
        $oikpos = $pdf_lasku->strlen($summeli, $norm);
        $pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summeli - $pyoristys * -1), $thispage, $norm);
        $pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], $thispage, $norm);
      }

      $kala -= 40;

      $isoparams = array_merge($norm, array("height" => 12));
      $luottoteksti = "Luoton myöntää Lindorff Invest Oy, jolta saatte laskun kotiinne lähipäivinä";
      $pdf_lasku->draw_text(200, $kala, t($luottoteksti) . ".", $thispage, $isoparams);

      $kala -= 50;

      $lineparams = array("width" => 0.1);
      $pdf_lasku->draw_line(array(200, 350), array($kala, $kala), $thispage, $lineparams);
      $pdf_lasku->draw_line(array(400, 550), array($kala, $kala), $thispage, $lineparams);

      $kala -= 10;

      $pdf_lasku->draw_text(200, $kala, t("Paikka ja aika"), $thispage, $norm);
      $pdf_lasku->draw_text(400, $kala, t("Allekirjoitus"), $thispage, $norm);

      $kala -= 30;

      $pdf_lasku->draw_line(array(400, 550), array($kala, $kala), $thispage, $lineparams);

      $kala -= 10;

      $pdf_lasku->draw_text(400, $kala, t("Nimenselvennys"), $thispage, $norm);
    }

    if (($yhtiorow['maksupaate_kassamyynti'] == 'K' and $kukarow["maksupaate_kassamyynti"] == "") or
      $kukarow["maksupaate_kassamyynti"] == "K"
    ) {
      // Jos maksupääte on enabloitu, tarvitaan myös maksupäätetapahtumien kuitit
      $query = "SELECT ifnull(group_concat(distinct tunnus SEPARATOR ', '), 0) tilnot
                FROM lasku
                WHERE yhtio   = '$kukarow[yhtio]'
                and laskunro  = '$laskurow[laskunro]'
                and tila      = 'L'
                and laskunro != 0";
      $result = pupe_query($query);
      $tilnorivi = mysql_fetch_assoc($result);
      $tilausnumerot = $tilnorivi['tilnot'];

      $query = "SELECT
                *
                FROM maksupaatetapahtumat
                WHERE yhtio ='{$yhtiorow['yhtio']}'
                AND tilausnumero      IN ($tilausnumerot)
                AND maksutapa        != ''
                AND tila             != ''
                AND asiakkaan_kuitti != ''
                AND tilausnumero     != 0";
      $result = pupe_query($query);

      if (mysql_affected_rows() > 0) {
        $pdf_lasku->draw_text(20, $kala, t("Maksupäätetapahtumat"), $thispage, $norm);
        $kala -= 10;
      }
      // TODO sivunvaihto kun ylitetään tietty pituus (jos tapahtumia on tarpeeksi niin ei mahdu sivulle)
      while ($maksupaatetapahtuma = mysql_fetch_assoc($result)) {
        $tapahtuman_kaikki_rivit = explode("\n", $maksupaatetapahtuma['asiakkaan_kuitti']);
        foreach ($tapahtuman_kaikki_rivit as $yksittainen_tapahtumarivi) {
          $pdf_lasku->draw_text(20, $kala, $yksittainen_tapahtumarivi, $thispage, $pieni);
          $kala -= 7;
        }
      }

      $kala -= 10;
    }
    return $kala;
  }
}

if (!function_exists('PalautaPankkiviivakoodi')) {
  function PalautaPankkiviivakoodi($tilinumero, $laskun_summa = 0, $erapv = "00.00.00", $viitenumero = 0, $versio = 2) {

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    if ($versio == '2') {
      $pankkitili = $tilinumero;
      include "inc/pankkitilinoikeellisuus.php";
    }
    else {
      // tarkista_iban ei osaa tarkistaa kuin Suomalaisia tilinumeroita
      $pankkitili = substr(tarkista_iban($tilinumero), 2); // Maatunnus pois
    }

    if ($pankkitili == "") {
      return;
    }

    $konekielinen_tilinumero = $pankkitili;

    // Laskun summa
    $konekielinen_summa = round(($laskun_summa) * 100);

    if ($konekielinen_summa > 99999999) {
      // Jos ylittaa kentan maksimipituuden
      $konekielinen_summa = 0;
    }

    $konekielinen_summa = sprintf("%08d", $konekielinen_summa);

    // Viitenumero
    if ($versio == '2' or $versio == '4' ) {
      $konekielinen_viitenumero = sprintf("%020d", $viitenumero);
    }
    elseif ($versio == '5') {
      // Annettu viite pitaisi olla muodossa RFxxyy...yy, jossa xx on tarkistenumero
      $tarkiste = substr($viitenumero, 2, 2);
      $viitenumero = substr($viitenumero, 4);
      $konekielinen_viitenumero = $tarkiste.sprintf("%021d", $viitenumero);
    }

    // Erapaiva
    $pp = substr($erapv, 8, 2);
    $kk = substr($erapv, 5, 2);
    $vv = substr($erapv, 2, 2);

    $konekielinen_erapv = $vv.$kk.$pp;

    // Varalla
    if ($versio=='2') {
      $vara = "0000";
    }
    elseif ($versio=='4') {
      $vara = "000";
    }
    else $vara = '';

    // Koonti
    $pankkiviivakoodi = $versio.$konekielinen_tilinumero.$konekielinen_summa;

    if ($versio=='2') {
      $pankkiviivakoodi .= $konekielinen_viitenumero.$konekielinen_erapv.$vara;
    }
    else {
      $pankkiviivakoodi .= $vara.$konekielinen_viitenumero.$konekielinen_erapv;
    }

    // Tarkistusnumero
    $viite = $pankkiviivakoodi;

    // Vain versio kakkoseen tarkiste 1
    if ($versio == '2') {
      // Menee tasan samalla systeemilla kuin viitenumeron tarkistusnumeron laskeminen..
      include "inc/generoiviite.inc";
    }

    return $viite;
  }
}

if (!function_exists("tulosta_lasku")) {
  function tulosta_lasku($lasku, $kieli = "", $tee = "", $toim = "", $valittu_tulostin = "", $valittu_kopio_tulostin = "", $saatekirje = "", $sellaskutyyppi = "") {
    global $kukarow, $yhtiorow, $tulosta_lasku_kpl;

    // Fonttimuuttujat
    extract(lasku_fontit($laskutyyppi));

    if (substr($lasku, 0, 6) == "LASKU:") {
      $lasku = substr($lasku, 6);

      $query = "SELECT *
                FROM lasku
                WHERE yhtio  = '$kukarow[yhtio]'
                and tila     = 'U'
                and alatila  = 'X'
                and laskunro = '$lasku'";
    }
    else {
      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$lasku'";
    }

    $laresult = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laresult);

    if ($yhtiorow['laiterekisteri_kaytossa'] != '') {
      $query = "SELECT *
                FROM laskun_lisatiedot
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$laskurow['tunnus']}'";
      $lisresult = pupe_query($query);
      $lisarow = mysql_fetch_assoc($lisresult);
      $laskurow['sopimus_numero'] = $lisarow['sopimus_numero'];
    }

    if ($toim == 'PROFORMA') {
      $asiakas_apu_query = "SELECT *
                            FROM asiakas
                            WHERE yhtio = '$kukarow[yhtio]'
                            and tunnus  = '{$laskurow["liitostunnus"]}'";
    }
    else {
      $asiakas_apu_query = "SELECT max(asiakas.kieli) kieli,
                            max(asiakas.laskutyyppi) laskutyyppi,
                            max(asiakas.laskunsummapyoristys) laskunsummapyoristys,
                            max(asiakas.laskun_jarjestys_suunta) laskun_jarjestys_suunta,
                            max(asiakas.laskun_jarjestys) laskun_jarjestys,
                            group_concat(DISTINCT asiakas.asiakasnro SEPARATOR ', ') asiakasnro
                            FROM lasku
                            JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
                            AND asiakas.tunnus = lasku.liitostunnus)
                            WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
                            AND lasku.tila     = 'L'
                            AND lasku.alatila  = 'X'
                            AND lasku.laskunro = '{$laskurow['laskunro']}'
                            AND lasku.tapvm    = '{$laskurow["tapvm"]}'";
    }

    $asiakas_apu_res = pupe_query($asiakas_apu_query);
    $asiakasrow = mysql_fetch_assoc($asiakas_apu_res);

    if ($asiakasrow['asiakasnro'] == NULL) {
      $asiakasrow = array();
    }

    if ($kieli != "") {
      $kieli = trim(strtoupper($kieli));
    }
    elseif (trim($asiakasrow["kieli"]) != "") {
      $kieli = trim(strtoupper($asiakasrow["kieli"]));
    }
    elseif (trim($kukarow["kieli"]) != "") {
      $kieli = trim(strtoupper($kukarow["kieli"]));
    }
    else {
      $kieli = trim(strtoupper($yhtiorow["kieli"]));
    }

    // haetaan maksuehdon tiedot
    $query = "SELECT pankkiyhteystiedot.*, maksuehto.*
              FROM maksuehto
              LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio = maksuehto.yhtio and pankkiyhteystiedot.tunnus = maksuehto.pankkiyhteystiedot)
              WHERE maksuehto.yhtio = '$kukarow[yhtio]'
              and maksuehto.tunnus  = '{$laskurow["maksuehto"]}'";
    $result = pupe_query($query);
    $masrow = mysql_fetch_assoc($result);

    // Maksuehto tekstinä
    $laskurow["maksuehto"] = $masrow["teksti"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);
    $laskurow["kateistyyppi"] = $masrow["kateinen"];

    if (kyseessa_eramaksu($laskurow) and !$tulosta_lasku_kpl) {
      $tulosta_lasku_kpl = 2;
    }

    //PDF parametrit
    $pdf_lasku = new pdffile;
    $pdf_lasku->set_default('margin-top',    0);
    $pdf_lasku->set_default('margin-bottom', 0);
    $pdf_lasku->set_default('margin-left',    0);
    $pdf_lasku->set_default('margin-right',  0);

    if ($laskurow["tila"] == 'U') {
      $where = " and tilausrivi.uusiotunnus='$laskurow[tunnus]' and tilausrivi.tyyppi = 'L' ";
    }
    else {
      $where = " and tilausrivi.otunnus='$laskurow[tunnus]' and tilausrivi.tyyppi in ('L','W','E') and ((tilausrivi.varattu+tilausrivi.jt != 0 and tilausrivi.laskutettuaika = '0000-00-00') or (tilausrivi.kpl != 0 and tilausrivi.laskutettuaika != '0000-00-00'))";
    }

    if (!empty($sellaskutyyppi)) {
      $laskutyyppi = $sellaskutyyppi;

      if (strpos($sellaskutyyppi, "##") !== FALSE) {
        list($laskutyyppi, $laskurow['laskupohjateksti']) = explode("##", $laskutyyppi);
      }
    }
    elseif ($laskurow['laskutyyppi'] == -9 or $laskurow['laskutyyppi'] == 0) {
      //jos laskulta löytyvät laskutyyppi on Oletus käytetään asiakkaan tai yhtiön oletus laskutyyppiä
      if (isset($asiakasrow['laskutyyppi']) and $asiakasrow['laskutyyppi'] != -9) {
        $laskutyyppi = $asiakasrow['laskutyyppi'];
      }
      else {
        $laskutyyppi = $yhtiorow['laskutyyppi'];
      }
    }
    else {
      $laskutyyppi = $laskurow['laskutyyppi'];
    }

    // katotaan miten halutaan sortattavan
    $order_sorttaus = '';

    if (isset($asiakasrow["laskun_jarjestys"])) {
      $sorttauskentta = generoi_sorttauskentta($asiakasrow["laskun_jarjestys"] != "" ? $asiakasrow["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
      $order_sorttaus = $asiakasrow["laskun_jarjestys_suunta"] != "" ? $asiakasrow["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
    }
    else {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
      $order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
    }

    if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
    else $pjat_sortlisa = "";

    $query_ale_lisa = generoi_alekentta('M');
    $ale_query_select_lisa = generoi_alekentta_select('yhteen', 'M');

    if ($toim == 'PROFORMA') {
      $laskurow["tapvm"] = date("Y-m-d");

      $lisa = " sum(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta,
                sum(tilausrivi.varattu+tilausrivi.kpl) kpl ";
    }
    else {
      $lisa = " sum(tilausrivi.rivihinta) rivihinta,
                sum(tilausrivi.kpl) kpl ";
    }

    $sivu = 1;
    $summa = 0;
    $arvo = 0;
    $varasto = 0;
    $vientikala = 0;
    $page_lasku = array();

    if ($yhtiorow["viivakoodi_laskulle"] == "K" and $laskurow["maa"] == "FI" and $laskurow["summa"] > 0) {
      $barcode_korkeus = 50;
    }

    if ($laskurow["vienti"] != "" or strtoupper($laskurow["maa"]) != strtoupper($yhtiorow["maa"])) {
      $vientikala = 20;
    }

    if ($laskutyyppi == 21 or $laskutyyppi == 22) {
      $laskurow['_loppunollat'] = "NONE";
    }

    // haetaan tilauksen kaikki rivit
    $query = "SELECT
              if ((tilausrivi.nimitys='Kuljetusvakuutus' OR '{$yhtiorow['yhdistetaan_identtiset_laskulla']}' = 'k'), tilausrivin_lisatiedot.vanha_otunnus, ifnull((SELECT vanha_otunnus from tilausrivin_lisatiedot t_lisa where t_lisa.yhtio=tilausrivi.yhtio and t_lisa.tilausrivitunnus=tilausrivi.perheid and t_lisa.omalle_tilaukselle != ''), tilausrivi.tunnus)) rivigroup,
              tilausrivi.ale1,
              tilausrivi.ale2,
              tilausrivi.ale3,
              $ale_query_select_lisa aleyhteensa,
              tilausrivi.alv,
              tuote.eankoodi,
              tuote.ei_saldoa,
              tilausrivi.erikoisale,
              tilausrivi.nimitys,
              tilausrivin_lisatiedot.osto_vai_hyvitys,
              tuote.sarjanumeroseuranta,
              tilausrivi.tuoteno,
              tilausrivi.uusiotunnus,
              tilausrivi.yksikko,
              tilausrivi.hinta,
              tilausrivi.netto,
              lasku.vienti_kurssi,
              lasku.viesti laskuviesti,
              lasku.asiakkaan_tilausnumero,
              lasku.luontiaika tilauspaiva,
              CONCAT(tuote.tullinimike1, IF(tuote.tullinimike2 NOT IN ('', '00', '0'), tuote.tullinimike2, '')) AS tullinimike,
              if (tuote.tuotetyyppi = 'K','2 Työt','1 Muut') tuotetyyppi,
              if (tilausrivi.var2 = 'EIOST', 'EIOST', '') var2,
              if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
              tuote.myyntihinta,
              $sorttauskentta,
              lasku.tilausyhteyshenkilo,
              min(tilausrivi.hyllyalue) hyllyalue,
              min(tilausrivi.hyllynro) hyllynro,
              min(tilausrivi.keratty) keratty,
              min(if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika)) toimaika,
              min(if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', if(date_format(tilausrivi.laskutettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.laskutettuaika, '%Y-%m-%d')), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d'))) toimitettuaika,
              min(tilausrivi.otunnus) otunnus,
              min(tilausrivi.perheid) perheid,
              min(tilausrivi.tunnus) tunnus,
              min(tilausrivi.kommentti) kommentti,
              min(tilausrivi.tilaajanrivinro) tilaajanrivinro,
              min(tilausrivi.laadittu) laadittu,
              min(tilausrivin_lisatiedot.tiliointirivitunnus) tiliointirivitunnus,
              sum(tilausrivi.tilkpl) tilkpl,
              sum(round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys])) rivihinta_verollinen,
              sum((tilausrivi.hinta / lasku.vienti_kurssi) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa,
              sum((tilausrivi.hinta / lasku.vienti_kurssi) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa_verollinen,
              group_concat(tilausrivi.tunnus) rivitunnukset,
              group_concat(distinct tilausrivi.perheid) perheideet,
              count(*) rivigroup_maara,
              $lisa
              FROM tilausrivi
              JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
              JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
              and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
              and tilausrivi.varattu+tilausrivi.kpl != 0
              $where
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28
              ORDER BY otunnus, if(tilausrivi.tuoteno in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]'), 2, 1), $pjat_sortlisa sorttauskentta $order_sorttaus, tunnus";
    $result = pupe_query($query);

    $kaanteinen_verotus = FALSE;
    $laskurow['_onko_alennuksia'] = FALSE;
    $laskurows = array();

    // tsekataan pitääkö tuote korvata osaluettelolla
    while ($row = mysql_fetch_assoc($result)) {

      if ($row["alv"] >= 600) {
        $kaanteinen_verotus = TRUE;
      }

      if ($row["aleyhteensa"] != 0) {
        $laskurow['_onko_alennuksia'] = TRUE;
      }

      if ($laskutyyppi == 21 or $laskutyyppi == 22) {
        $row['kpl'] = $row['kpl'] * 1;
        $row['tilkpl'] = $row['tilkpl'] * 1;
      }

      if ($yhtiorow["pura_osaluettelot"] != "") {
        // Korvataanko tilauksella oleva rivi osaluettelolla
        $laskurows = array_merge($laskurows, pura_osaluettelot($laskurow, $row, $laskutyyppi));
      }
      else {
        $laskurows[] = $row;
      }
    }

    // aloitellaan laskun teko
    list($page_lasku, $sivu, $kala) = alku($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu);

    $rivigrouppaus = FALSE;
    $yhteensamaara = 0;

    $lasrivilask = 0;
    $lasrivimaara = count($laskurows);

    foreach ($laskurows as $row) {
      $lasrivilask++;

      // Rivin toimitusaika
      if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $row["keratty"] == "saldoton") {
        $row["toimitettuaika"] = $row["toimaika"];
      }
      elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
        $row["toimitettuaika"] = $row["toimaika"];
      }
      else {
        $row["toimitettuaika"] = $row["toimitettuaika"];
      }

      if ($row["rivigroup_maara"] > 1 and !$rivigrouppaus) {
        $rivigrouppaus = TRUE;
      }

      if (($laskutyyppi != 2 and $laskutyyppi != 12) or (($laskutyyppi == 2 or $laskutyyppi == 12) and $row["perheid"] > 0 and $row["perheid"] == $row["tunnus"])) {
        $yhteensamaara += $row["kpl"];
      }

      $yhteensa_rivikommentti_lisatty = 0;

      // Otetaan yhteensäkommentti pois jos summataan rivejä
      if ($rivigrouppaus) {
        // Trimmataan ja otetaan "yhteensäkommentti" pois
        $row["kommentti"] = trim(poista_rivin_yhteensakommentti($row["kommentti"]));
      }
      else {
        // Jos laskutyyppi on 18 (Normaali laskupohja (nimitykset rivitettynä))
        // ja tilaus vaihtuu
        if ($laskutyyppi == 18 and ($lasrivilask == $lasrivimaara or strpos($row['kommentti'], "yhteensä") !== FALSE or strpos($row['kommentti'], t("yhteensä", $kieli)) !== FALSE or strpos($row['kommentti'], t("yhteensä", $asiakasrow["kieli"])) !== FALSE or strpos($row['kommentti'], t("yhteensä")) !== FALSE)) {

          $row["kommentti"] = trim(t("Tilaus", $kieli).": ".$row['otunnus'])."\n".$row["kommentti"];

          $yhteensa_rivikommentti_lisatty = 10;

          if (trim($row['tilausyhteyshenkilo']) != '') {
            $row["kommentti"] = trim(t("Tilaaja", $kieli).": ".$row['tilausyhteyshenkilo'])."\n".$row["kommentti"];
            $yhteensa_rivikommentti_lisatty += 10;
          }
        }
      }

      if ($kala <= 130) {
        loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

        $sivu++;

        list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
      }

      // Mahtuuko rivi tälle sivulle
      $devnull = "";

      list($devnull, $devnullkala, $devnullsumma, $devnullarvo) = rivi_lasku($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $devnull, $kala, $laskutyyppi, $toim, $summa, $arvo, '');

      // Jos on paljon rivejä tehdään uusi otsikko...
      if ($devnullkala <= 130) {
        loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

        $sivu++;

        list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
      }

      list($page_lasku[$sivu], $kala, $summa, $arvo) = rivi_lasku($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $page_lasku[$sivu], $kala, $laskutyyppi, $toim, $summa, $arvo);

      $kala = $yhteensa_rivikommentti_lisatty != 0 ? ($kala - $yhteensa_rivikommentti_lisatty) : $kala;

      // Tutkitaan mihin printteriin tämän laskun voisi tulostaa
      if ($valittu_tulostin == "AUTOMAAGINEN_VALINTA" and $varasto == 0 and $row["ei_saldoa"] == "") {
        //otetaan varastopaikat talteen
        $varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"], "");
      }
    }

    // Mahtuuko erittely samalle sivulle?
    $devnullkala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $devnull, $toim, $kala, $summa, $arvo, $laskutyyppi, "", "");

    if ($devnullkala <= 110+$vientikala+$barcode_korkeus) {
      loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

      $sivu++;

      list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
    }

    $kala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $page_lasku[$sivu], $toim, $kala, $summa, $arvo, $laskutyyppi, "", $yhteensamaara);

    if ($masrow['kassa_alepros'] > 0) {

      // Mahtuuko erittely samalle sivulle?
      $devnullkala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $devnull, $toim, $kala, $summa, $arvo, $laskutyyppi, $masrow['kassa_alepros'], $yhteensamaara);

      if ($devnullkala <= 110+$vientikala+$barcode_korkeus) {
        loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

        $sivu++;

        list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
      }

      $kala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $page_lasku[$sivu], $toim, $kala, $summa, $arvo, $laskutyyppi, $masrow['kassa_alepros'], $yhteensamaara);
    }

    if ($laskutyyppi == 21 or $laskutyyppi == 22) {
      $kala -= 10;
      list($page_lasku, $sivu, $kala) = laskun_kommentit($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu, $kala);
    }

    // Vikan sivun loppulaatikko
    loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, 1);

    // Palautetaan PDF sellaisenaan
    if ($tee == 'VERKKOLASKU_MAVENTA') {
      return $pdf_lasku->generate();
    }

    // keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdf_laskufilenimi = "/tmp/Lasku-".md5(uniqid(mt_rand(), true)).".pdf";

    // kirjoitetaan pdf faili levylle..
    $fh = fopen($pdf_laskufilenimi, "w");
    if (fwrite($fh, $pdf_lasku->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdf_laskufilenimi");
    fclose($fh);

    $laskuvaikuitti = t("Lasku", $kieli);

    if (!empty($laskurow["kateistyyppi"])) {
      $laskuvaikuitti = t("Kuitti", $kieli);
    }

    // jos tämä haluttiin suoraan asiakkaalle sähköpostiin
    if ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdf_laskufilenimi);
    }
    elseif ($tee == 'VERKKOLASKU_APIX' or $tee == 'VERKKOLASKU_PPG' or $tee == 'VERKKOLASKU_TALENOM' or $tee == 'VERKKOLASKU_ARVATO') {
      // Palautetaan laskunumero ja $pdf_laskufilenimi jotta apixkoodi saa sen zipattua jne.
      return $pdf_laskufilenimi;
    }
    elseif ($tee == 'VERKKOLASKU' and ($yhtiorow['lasku_tulostin'] == -99 or $valittu_tulostin == -99)) {
      // Lasku lähtee asiakkaalle sähköpostilla. Generoidaan laskun saatekirje, joka haetaan avainsanoista
      include_once "inc/generoi_laskun_saate.inc";

      list($komento, $content_subject, $content_body) = generoi_laskun_saate($laskurow, $saatekirje, $kieli);

      // Jos asiakkaalla on laskutussähköposti tai jos $yhtiorow['lasku_tulostin'] != -99 niin silloin lasku menee kukarow[email]:iin jos $komento == ""
      if ($komento != "" or $yhtiorow['lasku_tulostin'] != -99) {

        // otetaan tämä talteen jotta saadaan vientierittely
        $GLOBALS['vientierittelymail'] = $komento;

        // lähetetään meili
        $kutsu = $laskuvaikuitti." ".$laskurow["laskunro"];
        $liite = $pdf_laskufilenimi;

        // lähetetään cc aina talhal/postittaja osoitteeseen
        if ($yhtiorow["sahkopostilasku_cc_email"] != "") {
          $sahkoposti_cc = $yhtiorow["sahkopostilasku_cc_email"];
        }
        else {
          $sahkoposti_cc = "";
        }

        include "inc/sahkoposti.inc"; // sanotaan include eikä require niin ei kuolla
      }
    }
    else {
      // Setataan muuttuja
      $komento = "";

      //haetaan varaston tiedot
      if ($valittu_tulostin != "" and !is_numeric($valittu_tulostin) and $valittu_tulostin == "AUTOMAAGINEN_VALINTA") {
        if ($varasto != 0) {
          $query = "SELECT *
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$varasto'
                    ORDER BY alkuhyllyalue,alkuhyllynro";
        }
        else {
          $query = "SELECT *
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
                    ORDER BY alkuhyllyalue,alkuhyllynro
                    LIMIT 1";
        }
        $prires = pupe_query($query);
        $prirow = mysql_fetch_assoc($prires);

        $valittu_tulostin = $prirow["printteri5"];

        if ($GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Lasku tulostuu varastoon").": $prirow[nimitys]<br>\n";
      }
      elseif ($valittu_tulostin != "" and !is_numeric($valittu_tulostin) and $valittu_tulostin != "AUTOMAAGINEN_VALINTA") {
        // Tulostakopio päässää suoraan printterikomennon
        $komento = $valittu_tulostin;
      }

       if ($komento == "" and (int) $valittu_tulostin > 0 and $tee != 'TULOSTA_EMAIL_LASKUT') {
        $querykieli = "SELECT *
                       from kirjoittimet
                       where yhtio = '$kukarow[yhtio]'
                       and tunnus  = '$valittu_tulostin'";
        $kires = pupe_query($querykieli);
        $kirow = mysql_fetch_assoc($kires);

        $komento = $kirow["komento"];

        if ($GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Lasku tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";
      }

      // otetaan tämä talteen jotta saadaan vientierittely
      $GLOBALS['vientierittelykomento'] = $komento;

      // Etsitään kassalippaan email
      $query = "SELECT email
                FROM kassalipas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['kassalipas']}'";
      $result = pupe_query($query);
      $trow = mysql_fetch_assoc($result);

      if (($komento == "email" and $kukarow["eposti"] != '') or substr($komento, 0, 12) == 'asiakasemail') {
        // lähetetään meili
        $kutsu = $laskuvaikuitti." ".$laskurow["laskunro"];

        if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
          $kutsu .= ", ".trim($laskurow["nimi"]);
        }

        if (substr($komento, 0, 12) == 'asiakasemail') {
          $asiakasemail = substr($komento, 12);
        }

        $_attachments = array(0 => array(
            "filename"        => $pdf_laskufilenimi,
            "newfilename"     => $kutsu.".pdf",
            "ctype"           => "pdf")
        );

        // Halutaan lähettää käteismyynnin kuitti sähköpostilla oletuksena kassalippaan sähköpostiin
        // jos sellainen löytyy, muuten käyttäjän sähköpostiin.
        // Asiakasraportti (myyntilaskut_asiakasraportti.php) käyttää tätä samaa koodia ja se pitäisi
        // taas laitaa laskukopiot _aina_ asiakkaan sähköpostiin.
        if (!empty($trow["email"])) {
          $vmail = $trow['email'];
        }
        elseif (!empty($asiakasemail)) {
          $vmail = $asiakasemail;
        }
        else {
          $vmail = $kukarow['eposti'];
        }

        if (!empty($vmail)) {
          // Sähköpostin lähetykseen parametrit
          $parametri = array(
            "to"           => $vmail,
            "cc"           => $sahkoposti_cc,
            "subject"      => $yhtiorow['nimi']." - ".$kutsu,
            "ctype"        => $content_body_ctype,
            "body"         => $ulos,
            "attachements" => $_attachments,
          );

          pupesoft_sahkoposti($parametri);
        }
      }
      elseif ($komento != "" and $komento != "email") {
        // itse print komento...
        if (!empty($tulosta_lasku_kpl) and strpos($komento, "-#") === FALSE) {
          $komento .= " -#{$tulosta_lasku_kpl}";
        }

        $line = exec("$komento $pdf_laskufilenimi");
      }

      // Kuittikopio
      if (isset($valittu_kopio_tulostin) and $valittu_kopio_tulostin != '') {

        $asiakasemail_kaytossa = substr($valittu_kopio_tulostin, 0, 12) == 'asiakasemail' ? true : false;

        if (!$asiakasemail_kaytossa) {
          $querykieli = "SELECT *
                         from kirjoittimet
                         where yhtio = '$kukarow[yhtio]'
                         and tunnus  = '$valittu_kopio_tulostin'";
          $kires = pupe_query($querykieli);
          $kirow = mysql_fetch_assoc($kires);
        }
        else {
          $kirow = array('komento' => '');
        }

        if ($asiakasemail_kaytossa and $GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Kuittikopio lähetetään sähköpostiin").": ".str_replace("asiakasemail", "", $valittu_kopio_tulostin)."<br>\n";
        elseif ($GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Kuittikopio tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

        if ($asiakasemail_kaytossa or ($kirow["komento"] == "email" and $kukarow["eposti"] != '')) {
          // lähetetään meili
          $komento = $asiakasemail_kaytossa ? $valittu_kopio_tulostin : "";

          $kutsu = $laskuvaikuitti." ".$laskurow["laskunro"];

          if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
            $kutsu .= ", ".trim($laskurow["nimi"]);
          }

          $liite = $pdf_laskufilenimi;
          $sahkoposti_cc = "";
          $content_subject = "";
          $content_body = "";

          if (!empty($saatekirje)) {
            include_once "inc/generoi_laskun_saate.inc";
            list($devnull, $content_subject, $content_body) = generoi_laskun_saate($laskurow, $saatekirje, $kieli, "");
          }

          include "inc/sahkoposti.inc";
        }
        elseif ($kirow["komento"] != "" and $kirow["komento"] != "email") {
          // itse print komento...
          $line = exec("$kirow[komento] $pdf_laskufilenimi");
        }
      }
    }

    unset($pdf_lasku);
    unset($page_lasku);
  }
}

function kyseessa_eramaksu($laskurow) {
  return $laskurow["kateistyyppi"] != '' and $laskurow['mapvm'] == "0000-00-00";
}
