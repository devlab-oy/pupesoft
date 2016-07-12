<?php

//Fontit
$mini["height"]       = 5;
$mini["font"]         = "Times-Roman";

$seiska["height"]       = 7;
$seiska["font"]       = "Times-Roman";

$kymppi["height"]       = 10;
$kymppi["font"]       = "Times-Roman";

$kymppi_courier["height"]   = 10;
$kymppi_courier["font"]   = "Courier";

$kymppi_helvetica["height"] = 10;
$kymppi_helvetica["font"]   = "Helvetica";

$kasi_helvetica["height"]   = 8;
$kasi_helvetica["font"]   = "Helvetica";

$rectparam["width"]      = 0.2;

//Haetaan pdflibrary
require_once 'pdflib/phppdflib.class.php';

//muutamat funktiot...
if (!function_exists('alku_osamaksusoppari')) {
  function alku_osamaksusoppari($laskurow, $asiakasrow, $sivu, $kieli, $pdf_osamaksusoppari) {
    global $yhtiorow, $kukarow, $pupe_root_polku, $kasi_helvetica, $kymppi_helvetica, $seiska, $rectparam, $kymppi_courier, $vkorko;

    // Haetaan pohja
    ob_start();

    $filename = "{$pupe_root_polku}/ajoneuvomyynti/osamaksusoppari.pdf";

    $handle = fopen($filename, "r");
    $d = fread($handle, filesize($filename));
    fclose($handle);

    $pdf_osamaksusoppari->import->append($d);
    $pdf_osamaksusoppari_sivu = $pdf_osamaksusoppari->import->get_pages();

    ob_end_clean();

    tulosta_logo_pdf($pdf_osamaksusoppari, $pdf_osamaksusoppari_sivu[0], "", 0, 0, 30, 200);

    $rectp = array();
    $rectp['mode']           = "fill";
    $rectp['fillcolor']['red']     = 1;
    $rectp['fillcolor']['blue']    = 1;
    $rectp['fillcolor']['green']   = 1;

    $pdf_osamaksusoppari->draw_text(mm_pt(110),  mm_pt(283), t("OSAMAKSUSOPIMUS"),            $pdf_osamaksusoppari_sivu[0]);
    $pdf_osamaksusoppari->draw_text(mm_pt(77),   mm_pt(278), t("Pvm").": ".tv1dateconv($laskurow["luontiaika"]), $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(287), t("Numero"),                $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    //top left bottom right
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(290), mm_pt(158),  mm_pt(281), mm_pt(197),        $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_text(mm_pt(170),  mm_pt(283), $laskurow["tunnus"],            $pdf_osamaksusoppari_sivu[0]);
    $pdf_osamaksusoppari->draw_text(mm_pt(110),  mm_pt(278), t("Tämän sopimuksen ehdot on annettu ostajalle erillisenä liitteenä"),  $pdf_osamaksusoppari_sivu[0], $seiska);

    // Ylipiirretään ruudukko
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(278), mm_pt(8),  mm_pt(185), mm_pt(200),          $pdf_osamaksusoppari_sivu[0], $rectp);

    $pdf_osamaksusoppari->draw_rectangle(mm_pt(277), mm_pt(10),  mm_pt(263.5), mm_pt(200),        $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(277), mm_pt(10),  mm_pt(270.5), mm_pt(200),        $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(277), mm_pt(10),  mm_pt(263.5), mm_pt(152),        $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(277), mm_pt(10),  mm_pt(263.5), mm_pt(97),        $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(277), mm_pt(10),  mm_pt(263.5), mm_pt(76),        $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_text(mm_pt(10),   mm_pt(278), t("MYYJÄ"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(275), t("Nimi"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(77),   mm_pt(275), t("Y-tunnus"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(98),   mm_pt(275), t("Puhelin"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(122),  mm_pt(275), t("Kotipaikka"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(153),  mm_pt(275), t("Pankkiyhteys"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(271.5), $yhtiorow["nimi"],             $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(78),   mm_pt(271.5), $yhtiorow["ytunnus"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(99),   mm_pt(271.5), $yhtiorow["puhelin"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(123),  mm_pt(271.5), $yhtiorow["kotipaikka"],         $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(154),  mm_pt(271.5), $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],  $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(268), t("Lähiosoite"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(77),   mm_pt(268), t("Postinumero"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(98),  mm_pt(268), t("Postitoimipaikka"),            $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(153),  mm_pt(268), t("Henkilön nimi"),            $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(264.5), $yhtiorow["osoite"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(78),   mm_pt(264.5), $yhtiorow["postino"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(99),  mm_pt(264.5), $yhtiorow["postitp"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(154),  mm_pt(264.5), $kukarow["nimi"],            $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);


    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(217.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(252.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(245.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(238.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(231.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(224.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);


    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(217.5), mm_pt(159),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(217.5), mm_pt(105),      $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(259.5), mm_pt(10),  mm_pt(217.5), mm_pt(64),        $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_text(mm_pt(10),   mm_pt(260.5), t("OSTAJA"),  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(25),   mm_pt(260.5), t("(Rinnakkaisostajan tiedot merkitään vain jos yhteisvastuullinen)"),  $pdf_osamaksusoppari_sivu[0], $seiska);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(257), t("Nimi"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(65),   mm_pt(257), t("Henkilötunnus / Y-tunnus"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(253.5), $laskurow["nimi"]." ".$laskurow["nimitark"], $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(66),   mm_pt(253.5), $laskurow["ovttunnus"],          $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(106),  mm_pt(257), t("Nimi"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(257), t("Henkilötunnus / Y-tunnus"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(107),  mm_pt(253.5), $laskurow["kolm_nimi"]." ".$laskurow["kolm_nimitark"], $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(161),  mm_pt(253.5), $laskurow["kolm_ovttunnus"],        $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    if ($asiakasrow["gsm"] != "") {
      $puh = $asiakasrow["gsm"];
    }
    else {
      $puh = $asiakasrow["puhelin"];
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(250), t("Lähiosoite"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(65),   mm_pt(250), t("Puhelin"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(246.5), $laskurow["osoite"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(66),   mm_pt(246.5), $puh,                   $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(106),  mm_pt(250), t("Lähiosoite"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(250), t("Puhelin"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(107),  mm_pt(246.5), $laskurow["kolm_osoite"],         $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(161),  mm_pt(246.5), "",                     $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(243), t("Postinumero"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(65),   mm_pt(243), t("Postitoimipaikka"),            $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(239.5), $laskurow["postino"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(66),   mm_pt(239.5), $laskurow["postitp"],           $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(106),  mm_pt(243), t("Postinumero"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(243), t("Postitoimipaikka"),            $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(107),  mm_pt(239.5), $laskurow["kolm_postino"],         $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(161),  mm_pt(239.5), $laskurow["kolm_postitp"],         $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(236), t("Henkilötiedot tarkistettu"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(65),   mm_pt(236), t("(Ks. yleiset ehdot 15 §)"),        $pdf_osamaksusoppari_sivu[0], $seiska);

    $ht = "";

    if ($laskurow["rahlaskelma_hetu_tarkistus"] != "") {
      if ($laskurow["rahlaskelma_hetu_tarkistus"] == "A")   $ht = t("Ajokortti");
      elseif ($laskurow["rahlaskelma_hetu_tarkistus"] == "H") $ht = t("Henkilökortti");
      elseif ($laskurow["rahlaskelma_hetu_tarkistus"] == "P") $ht = t("Passi");
      elseif ($laskurow["rahlaskelma_hetu_tarkistus"] == "K") $ht = t("Kuvallinen pankkikortti");
      elseif ($laskurow["rahlaskelma_hetu_tarkistus"] == "U") $ht = t("Kuvallinen Kela-kortti");
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(232.5), $ht,                   $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(66),   mm_pt(232.5), "PEP / Ei",                 $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(106),  mm_pt(236), t("Henkilötiedot tarkistettu"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(236), t("(Ks. yleiset ehdot 15 §)"),        $pdf_osamaksusoppari_sivu[0], $seiska);

    $ht = "";

    if ($laskurow["rahlaskelma_hetu_kolm_tarkistus"] != "") {
      if ($laskurow["rahlaskelma_hetu_kolm_tarkistus"] == "A")    $ht = t("Ajokortti");
      elseif ($laskurow["rahlaskelma_hetu_kolm_tarkistus"] == "H") $ht = t("Henkilökortti");
      elseif ($laskurow["rahlaskelma_hetu_kolm_tarkistus"] == "P") $ht = t("Passi");
      elseif ($laskurow["rahlaskelma_hetu_kolm_tarkistus"] == "K") $ht = t("Kuvallinen pankkikortti");
      elseif ($laskurow["rahlaskelma_hetu_kolm_tarkistus"] == "U") $ht = t("Kuvallinen Kela-kortti");
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(107),  mm_pt(232.5), $ht,                   $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(161),  mm_pt(232.5), "PEP / Ei",                 $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(229), t("Asiakirjan nro / pvm"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(65),   mm_pt(229), t("Asiakirjan myöntäja"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(225.5), $laskurow["rahlaskelma_hetu_asiakirjanro"],     $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(66),   mm_pt(225.5), $laskurow["rahlaskelma_hetu_asiakirjamyontaja"], $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(106),  mm_pt(229), t("Asiakirjan nro / pvm"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(229), t("Asiakirjan myöntäja"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(107),  mm_pt(225.5), $laskurow["rahlaskelma_hetu_kolm_asiakirjanro"],     $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(161),  mm_pt(225.5), $laskurow["rahlaskelma_hetu_kolm_asiakirjamyontaja"],  $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),   mm_pt(222), t("Päivämäärä/tunnistajan nimi"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(65),   mm_pt(222), t("Kansalaisuus"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12),   mm_pt(218.5), $laskurow["rahlaskelma_hetu_tarkastaja"],  $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(66),   mm_pt(218.5), $asiakasrow["kansalaisuus"],       $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(106),  mm_pt(222), t("Päivämäärä/tunnistajan nimi"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(160),  mm_pt(222), t("Kansalaisuus"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(107),  mm_pt(218.5), $laskurow["rahlaskelma_hetu_kolm_tarkastaja"],  $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(161),  mm_pt(218.5), $laskurow[""],               $pdf_osamaksusoppari_sivu[0], $kasi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(214.5), t("KAUPAN KOHDE"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(213.5), mm_pt(10),  mm_pt(199.5), mm_pt(200),      $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(196.5), t("VAIHTOKOHDE"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(195.5), mm_pt(10),  mm_pt(188), mm_pt(200),        $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_rectangle(mm_pt(185), mm_pt(8),  mm_pt(77), mm_pt(60),          $pdf_osamaksusoppari_sivu[0], $rectp);

    $x[0] = $x[1] = mm_pt(60);
    $y[0] = mm_pt(183.5);
    $y[1] = mm_pt(80.20);
    $pdf_osamaksusoppari->draw_line($x, $y, $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_text(mm_pt(10),   mm_pt(184.5), t("RAHOITETTAVA OSA"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(181.5), t("1. Käteishinta kaupan kohteesta vakiovarustein"),    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(175.5), t("2. Lisävarusteet yhteensä"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(169.5), t("3. Toimituskulut"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(163.5), t("4. Käteishinta kaupan kohteesta (1+2+3)"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(157.5), t("5. Hyvityshinta vaihtokohteesta"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(151.5), t("6. Jäännösvelka vaihtokohteesta"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(145.5), t("7. Hyvitysosuus vaihtokohteesta (5+6)"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(139.5), t("8. Etumaksu tilattaessa"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(133.5), t("9. Muut maksut ennen toimitusta"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(127.5), t("10. Käteismaksuosuus yhteensä (7+8+9)"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(121.0), t("11. Velkaosuus käteishinnasta (4-10)"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(115.0), t("12. Luottosuhteen perustamiskustannus"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(109.0), t("13. Muut luottokustannukset"),            $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(102.5), t("14. Rahoitettava osuus yhteensä (11+12+13)"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(96.5),  t("15. Korko sopimusajalta eräpäiviä noudatettaessa"),  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(90.5),  t("16. Tilinavausmaksu"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(10.5), mm_pt(84.5),  t("17. Käsittelymaksu yhteensä"),            $pdf_osamaksusoppari_sivu[0], $seiska);

    $tee = 'osamaksusoppari';
    $tulosta_osamakusoppari = "TRUE";
    $tilausnumero = $laskurow["tunnus"];

    //Otetaan laskurow talteen
    $resquelaskurow = $laskurow;

    require "osamaksusoppari.inc";

    $vkorko = $viitearray[$viitekorko];
    $laskurow = $resquelaskurow;

    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(178), sprintf('%10.2f', $uusivene),                $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(172), sprintf('%10.2f', $uusitarvike),                $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(166), sprintf('%10.2f', $toimkulu),                $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(160), sprintf('%10.2f', $kateishinta_kaupan_kohteesta),      $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(154), sprintf('%10.2f', $hyvitys_vaihtoveneesta),          $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(148), sprintf('%10.2f', $jaannosvelka_vaihtokohteesta),      $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(142), sprintf('%10.2f', $hyvitysosuus_vaihtokohteesta),      $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(136), sprintf('%10.2f', $ennakkomaksu),              $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(130), sprintf('%10.2f', $muut_maksut_ennen_toimitusta),      $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(124), sprintf('%10.2f', $kateismaksuosuus_yhteensa),        $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(118), sprintf('%10.2f', $velkaosuus_kateishinnasta),        $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(112), sprintf('%10.2f', $perustamiskustannus),            $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(106), sprintf('%10.2f', $muutluottokustannukset),          $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(99.5), sprintf('%10.2f', $rahoitettavaa_yhteensa),          $pdf_osamaksusoppari_sivu[0], $kymppi_courier);

    if ($lyhennystapa == 1) {
      $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(93.5), sprintf('%10.2f', $koron_osuus_tl),            $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    }
    else {
      $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(93.5), sprintf('%10.2f', $koron_osuus_te),            $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(87.5), sprintf('%10.2f', $tilinavausmaksu),              $pdf_osamaksusoppari_sivu[0], $kymppi_courier);
    $pdf_osamaksusoppari->draw_text(mm_pt(62), mm_pt(81),  sprintf('%10.2f', $kasittelymaksut_yhteensa),        $pdf_osamaksusoppari_sivu[0], $kymppi_courier);

    $pdf_osamaksusoppari->draw_text(mm_pt(89), mm_pt(184.5), t("OSAMAKSUERIEN MAKSUSUUNNITELMA"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(183), mm_pt(145),  mm_pt(178), mm_pt(155),                $pdf_osamaksusoppari_sivu[0], $rectp);

    if ($viitearray[$viitekorko] == "Kiinteä") {
      $pdf_osamaksusoppari->draw_paragraph(mm_pt(184), mm_pt(90), mm_pt(150), mm_pt(190),  t("Lyhennys on annuiteettilyhennys. Maksuerä sisältää myös koron. Sopimusajan korko on kiinteä. Kauppahinnan maksamattomalle, rahoitettavalle osalle maksetaan vuotuista korkoa"),  $pdf_osamaksusoppari_sivu[0], $seiska);

      $pdf_osamaksusoppari->draw_text(mm_pt(185), mm_pt(175), $sopajankorko." %",                    $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    }
    else {
      $pdf_osamaksusoppari->draw_paragraph(mm_pt(184), mm_pt(90), mm_pt(150), mm_pt(190),  t("Korko on vaihtuva, jolloin koron määrästä, muutosperusteista, muutosten voimaantulosta ja muista ehdoista laaditaan erillinen sopimus"),  $pdf_osamaksusoppari_sivu[0], $seiska);
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(168),  t("Osamaksuerien lkm"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(120), mm_pt(168),  t("Luottoaika kk"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(168),  t("Maksuväli kk"),                      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(170), mm_pt(168),  t("Erän nro 1 maksupäivä"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(161),  t("Annuiteettierä"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(120), mm_pt(161),  t("Viim erä euro"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(161),  t("Käsittelymaksu per erä"),                $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(170), mm_pt(161),  t("Viitekorko"),                      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(153),  t("Viim.erän maksupäivä"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(153),  t("18. Luottokustannus yht (12+13+15+16+17)"),        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(146),  t("19. KSL 7.2 §:n mukainen luottohinta (4+18)"),      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(146),  t("20. KSL 7:11 §:n mukainen todellinen vuosikorko"),    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(139),  t("Erien viivästyessä ostaja maksaa eräpäivän jälkeiseltä osalta korkolain mukaista viivästyskorkoa."), $pdf_osamaksusoppari_sivu[0], $seiska);


    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(163.5), $maksuerienlkm,                    $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(120), mm_pt(163.5), $luottoaikakk,                    $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(163.5), $maksuvali_kk,                    $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(170), mm_pt(163.5), $ekaerpcm,                      $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(156),   sprintf('%.2f', $A),                  $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);

    if ($poikkeava_era != 0) {
      $pdf_osamaksusoppari->draw_text(mm_pt(120), mm_pt(156),   sprintf('%.2f', $poikkeava_era),          $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    }
    else {
      $pdf_osamaksusoppari->draw_text(mm_pt(120), mm_pt(156),   sprintf('%.2f', $A),                $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(156),   sprintf('%.2f', $erankasittelymaksu),          $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(170), mm_pt(156),   $viitearray[$viitekorko],                $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(149.5), $vikaerpcm,                      $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);

    if ($lyhennystapa == 1) {
      $luottokustannus_yhteensa   = $luottokustannus_yhteensa_tl;
      $ksl_luottohinta       = $ksl_luottohinta_tl;
    }
    else {
      $luottokustannus_yhteensa   = $luottokustannus_yhteensa_te;
      $ksl_luottohinta       = $ksl_luottohinta_te;
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(149.5), sprintf('%.2f', $luottokustannus_yhteensa),      $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(142),   sprintf('%.2f', $ksl_luottohinta),          $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(142),   $ksl_vuosikorko."%",                  $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(89),  mm_pt(134),   t("VAKUUTUS"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(131.5), t("Vakuutusyhtiö"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(131.5), t("Laajuus"),                      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(90),  mm_pt(124.5), t("Alkamispäivä"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(142), mm_pt(124.5), t("Perusomavastuu"),                  $pdf_osamaksusoppari_sivu[0], $seiska);

    $vakuutushak_vakuutusyhtio_array = array("03" => "Lähivakuutus",
      "04" => "A-vakuutus",
      "13" => "Pohjantähti",
      "15" => "Volvia",
      "20" => "Nordea Vahinkovakuutus",
      "22" => "Tapiola",
      "36" => "Pohjola",
      "41" => "if / Sampo",
      "50" => "Veritas",
      "56" => "Turva",
      "65" => "Y-Fennia",
      "66" => "Ålandia");

    $vakuutushak_kaskolaji_array = array("1" => "Laajin",
      "2" => "Laaja",
      "3" => "Suppea",
      "4" => "Suppein");

    $pdf_osamaksusoppari->draw_text(mm_pt(91),  mm_pt(127), $vakuutushak_vakuutusyhtio_array[$laskurow["vakuutushak_vakuutusyhtio"]], $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(143), mm_pt(127), $vakuutushak_kaskolaji_array[$laskurow["vakuutushak_kaskolaji"]],           $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(91),  mm_pt(120), tv1dateconv($laskurow["vakuutushak_alkamispaiva"]),   $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(143), mm_pt(120), $laskurow["vakuutushak_perusomavastuu"],         $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(89),  mm_pt(115), t("TOIMITUSEHDOT"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(91),  mm_pt(112), t("Toimitusaika"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(110), mm_pt(112), t("Toimitustapa"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(91),  mm_pt(107), tv1dateconv($laskurow["toimaika"]),           $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(111), mm_pt(107), t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli),  $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_rectangle(mm_pt(114), mm_pt(150),  mm_pt(103.5), mm_pt(190),              $pdf_osamaksusoppari_sivu[0], $rectp);

    $pdf_osamaksusoppari->draw_text(mm_pt(150),  mm_pt(112.4), t("Ostajalle on annettu:"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(111.5),  mm_pt(150),  mm_pt(109.25), mm_pt(153),            $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(109.25), mm_pt(150),  mm_pt(106.75), mm_pt(153),            $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(106.75), mm_pt(150),  mm_pt(104.25),  mm_pt(153),          $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_rectangle(mm_pt(109.25), mm_pt(176),  mm_pt(106.75), mm_pt(179),            $pdf_osamaksusoppari_sivu[0], $rectparam);
    $pdf_osamaksusoppari->draw_rectangle(mm_pt(106.75), mm_pt(176),  mm_pt(104.25),  mm_pt(179),          $pdf_osamaksusoppari_sivu[0], $rectparam);

    $pdf_osamaksusoppari->draw_text(mm_pt(153.5),  mm_pt(109.35), t("Suullinen käytön opastus"),          $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(153.5),  mm_pt(106.85), t("Huoltokirja"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(153.5),  mm_pt(104.35), t("Takuukirja"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(179.5),  mm_pt(106.85), t("Kuntotestitodistus"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(179.5),  mm_pt(104.35), t("Käyttöohjeet"),                $pdf_osamaksusoppari_sivu[0], $seiska);

    if ($laskurow['rahlaskelma_opastus'] != "") $pdf_osamaksusoppari->draw_text(mm_pt(151),  mm_pt(109.35), "X", $pdf_osamaksusoppari_sivu[0], $seiska);
    if ($laskurow['rahlaskelma_huoltokirja'] != "")  $pdf_osamaksusoppari->draw_text(mm_pt(151),  mm_pt(106.85), "X", $pdf_osamaksusoppari_sivu[0], $seiska);
    if ($laskurow['rahlaskelma_takuukirja'] != "") $pdf_osamaksusoppari->draw_text(mm_pt(151),  mm_pt(104.35), "X", $pdf_osamaksusoppari_sivu[0], $seiska);
    if ($laskurow['rahlaskelma_kuntotestitodistus'] != "") $pdf_osamaksusoppari->draw_text(mm_pt(177),  mm_pt(106.85), "X", $pdf_osamaksusoppari_sivu[0], $seiska);
    if ($laskurow['rahlaskelma_kayttoohjeet'] != "") $pdf_osamaksusoppari->draw_text(mm_pt(177),  mm_pt(104.35), "X", $pdf_osamaksusoppari_sivu[0], $seiska);

    $pdf_osamaksusoppari->draw_text(mm_pt(89),  mm_pt(100.5),   t("LISÄTIEDOT JA -EHDOT"),              $pdf_osamaksusoppari_sivu[0], $seiska);

    $pdf_osamaksusoppari->draw_paragraph(mm_pt(100), mm_pt(90), mm_pt(80), mm_pt(190), t("Ostaja on hyväksynyt kaupan kohteen tämän sopimuksen allekirjoituksella. Ostaja on tutustunut tähän sopimukseen sekä sopimukseen kuuluviin yleisehtoihin ja sopimuksen liitteisiin ennen sopimuksen allekirjoitusta. Tämä sopimus tulee voimaan, kun sekä ostaja että myyjä ovat sopimuksen allekirjoittaneet. Sopijapuolet sitoutuvat noudattamaan tätä sopimusta. Tätä sopimusta on tehty kaksi samansisältöistä kappaletta, toinen myyjälle ja toinen ostajalle. MYYJÄLLÄ ON OSAMAKSUEHTOJEN 3. KOHDAN NOJALLA OIKEUS SIIRTÄÄ TÄHÄN SOPIMUKSEEN PERUSTUVAT OIKEUDET KOLMANNELLE, JOLLA ON OIKEUS SIIRTÄÄ NE EDELLEEN."), $pdf_osamaksusoppari_sivu[0], $seiska);

    $pdf_osamaksusoppari->draw_text(mm_pt(10),  mm_pt(76), t("ALLEKIRJOITUKSET"),                  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(75), mm_pt(11), mm_pt(67), mm_pt(80), t("Allekirjoituksellaan ostaja vahvistaa tutustuneensa sopimusehtoihin ja hyväksyvänsä sopimuksen täysin."), $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(131), mm_pt(73), t("Myyjän nimi ja allekirjoitus"),            $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(86),  mm_pt(73), t("Paikka ja aika"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(87),  mm_pt(69), $yhtiorow["postitp"]." ".date("d.m.Y"),          $pdf_osamaksusoppari_sivu[0], $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(11),  mm_pt(65.5), t("Ostajan allekirjoitus"),              $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(73),  mm_pt(65.5), t("Toisen ostajan allekirjoitus"),            $pdf_osamaksusoppari_sivu[0], $seiska);

    $pdf_osamaksusoppari->draw_text(mm_pt(133), mm_pt(55), $kukarow["nimi"]."   ".$yhtiorow["nimi"],        $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(13),  mm_pt(55), $laskurow["nimi"],                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(75),  mm_pt(55), $laskurow["kolm_nimi"],                  $pdf_osamaksusoppari_sivu[0], $seiska);

    $pdf_osamaksusoppari->draw_text(mm_pt(10),  mm_pt(50), t("SIIRROT"),                      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(11),  mm_pt(47), t("Tähän sopimukseen perustuvat oikeudet siirrämme"),  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(104), mm_pt(47), t("Tähän sopimukseen perustuvat oikeudet siirrämme"),  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(11),  mm_pt(39), t("Paikka ja aika"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(104), mm_pt(39), t("Paikka ja aika"),                    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(11),  mm_pt(32), t("Myyjäliikkeen allekirjoitus (myös painokirjaimin)"),  $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(104), mm_pt(32), t("Siirtäjän allekirjoitus (myös painokirjaimin)"),    $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(11),  mm_pt(23), t("Todistavat"),                      $pdf_osamaksusoppari_sivu[0], $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(104), mm_pt(23), t("Todistavat"),                      $pdf_osamaksusoppari_sivu[0], $seiska);


    $pdf_osamaksusoppari->draw_paragraph(mm_pt(19), mm_pt(10), mm_pt(10), mm_pt(190), t("Omistusoikeus kaupan kohteeseen ei siirry ostajalle ennen kuin kauppahinta kokonaisuudessaan on maksettu ja muut tämän sopimuksen tarkoittamat, kaupan kohdetta koskevat ostajan maksuvelvoitteet on täysin täytetty."), $pdf_osamaksusoppari_sivu[0], $seiska);

    //Palautetan luotu sivu
    return $pdf_osamaksusoppari_sivu[0];
  }
}

if (!function_exists('rivi_osamaksusoppari')) {
  function rivi_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kala, $kieli, $page_osamaksusoppari, $sarjanutunnus, $selite, $row) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

    $rivinkorkeus  = 7.1;

    if ($sarjanutunnus != "") {
      $query = "SELECT * from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and $sarjanutunnus='$row[tunnus]'";
      $sarjares = mysql_query($query) or pupe_error($query);
      $sarjarow = mysql_fetch_array($sarjares);

      if ($sarjarow["sarjanumero"] != "") {
        $sarjalisa = " (".$sarjarow["sarjanumero"].")";
      }
    }
    else {
      $sarjalisa = "";
    }

    if ($row["rivihinta"] < 0) {
      $row["rivihinta"] = abs($row["rivihinta"]);
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(11), mm_pt($kala+4), $selite,                           $page_osamaksusoppari, $seiska);
    $pdf_osamaksusoppari->draw_text(mm_pt(12), mm_pt($kala), $row["nimitys"]."  ".$sarjalisa,                 $page_osamaksusoppari, $kasi_helvetica);

    $kala = $kala - $rivinkorkeus;

    return array($page_osamaksusoppari, $kala);
  }
}

if (!function_exists('ehdot_osamaksusoppari')) {
  function ehdot_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kieli) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $mini;

    $firstpage = $pdf_osamaksusoppari->new_page("a4");

    tulosta_logo_pdf($pdf_osamaksusoppari, $firstpage, "", 0, 0, 30, 200);

    $pdf_osamaksusoppari->draw_text(mm_pt(110),  mm_pt(283), t("YLEISET SOPIMUSEHDOT"),  $firstpage);

    $rahoitusyhtio = '';

    if ($laskurow["rekisteilmo_omistaja"] == "NORDEA") {
      $rahoitusyhtio = "Nordea Rahoitus Suomi Oy";
    }
    elseif ($laskurow["rekisteilmo_omistaja"] == "GE") {
      $rahoitusyhtio = "GE Money Oy";
    }
    elseif ($laskurow["rekisteilmo_omistaja"] == "OKO") {
      $rahoitusyhtio = "Pohjola Pankki Oyj";
    }
    elseif ($laskurow["rekisteilmo_omistaja"] == "POHJOLA") {
      $rahoitusyhtio = "Pohjola Pankki Oyj";
    }
    elseif ($laskurow["rekisteilmo_omistaja"] == "SAMPO") {
      $rahoitusyhtio = "Sampo Rahoitus / Sampo Pankki Oyj";
    }
    elseif ($laskurow["rekisteilmo_omistaja"] == "SANTAN") {
      $rahoitusyhtio = "Santander";
    }
    elseif ($laskurow["rekisteilmo_omistaja"] == "HANDEL") {
      $rahoitusyhtio = "Handelsbanken Rahoitus Oyj";
    }

    $pdf_osamaksusoppari->draw_paragraph(mm_pt(280), mm_pt(10), mm_pt(10), mm_pt(90), "
1 § Osapuolet\r\n\r\n
Ostaja ja myyjä ovat sopineet tässä sopimuksessa mainitun kaupan tehdyksi erityisissä sopimusehdoissa mainituin sekä seuraavin ehdoin. Kaupan kohteesta käytetään jäljempänä nimitystä laite. Sopimus on euromääräinen.
\r\n
2 § Sovellettava laki\r\n\r\n
Tähän sopimukseen sovelletaan kuluttajakaupassa kuluttajansuojalain (38/78) 7 luvun säännöksiä ja muussa kaupassa kulloinkin voimassa olevia osamaksukauppalain (91/66) säännöksiä.
\r\n
3 § Sopimuksen siirto\r\n\r\n
Myyjällä on oikeus siirtää tähän sopimukseen perustuvat oikeutensa, mukaan lukien omistusoikeuden laitteeseen, kolmannelle ilman ostajan suostumusta.
Allekirjoittamalla tämän sopimuksen myyjä siirtää kaikki tähän sopimukseen perustuvat oikeutensa, mukaan lukien omistusoikeuden sopimuksen kohteena olevaan laitteeseen {$rahoitusyhtio}:lle (jäljempänä Rahoitusyhtiö). Siirron jälkeen kuuluvat kaikki tämän sopimuksen mukaiset myyjän oikeudet Rahoitusyhtiölle. Ostaja on saanut tiedon siirrosta ja voi suorittaa osamaksuerät pätevästi ainoastaan Rahoitusyhtiölle. Siirron jälkeen sopimusehtoja ei voida muuttaa ilman Rahoitusyhtiön suostumusta.
Ostajalla ei ole oikeutta siirtää tähän sopimukseen perustuvia oikeuksiaan tai velvollisuuksiaan kolmannelle.
\r\n
4 § Kaupan kohde ja myyjän ja luotonantajan vastuu virhetilanteessa\r\n\r\n
Sopimuksen siirrosta huolimatta erityisissä sopimusehdoissa mainitulla myyjällä on kaupanvastuu kaupan kohteena olevasta laitteesta. Ostaja vakuuttaa tutustuneensa huolellisesti laitteeseen ja myyjän siitä antamiin tietoihin sekä hyväksyvänsä laitteen. Mahdolliset reklamaatiot laitteesta on tehtävä myyjälle välittömästi virheen havaitsemisesta. Myyjällä on oikeus korjata virhe tai toimittaa ostajalle uusi laite. Mikäli myyjä ja ostaja eivät pääse sopimukseen asiassa, tulee ostajan ottaa yhteyttä Rahoitusyhtiöön. Kuluttajaostajalla on kuluttajansuojalain 7 luvun 39 §:ssä tarkoitettu oikeus vaatia myyjän sopimusrikkomuksen johdosta hyvitystä myös Rahoitusyhtiöltä, jolle sopimus on siirretty. Rahoitusyhtiö ei kuitenkaan ole velvollinen maksamaan ostajalle enempää kuin on saanut tältä maksuina. Kuluttajaostajalla on asiasta Rahoitusyhtiölle ilmoitettuaan oikeus pidättäytyä sovittujen maksuerien maksamisesta virhettä vastaavalla määrällä. Ostaja vastaa perusteettomasta maksusta pidättäytymisestä aiheutuneista viivästys- ja muista seuraamuksista.
\r\n
5 § Omistusoikeus\r\n\r\n
Rahoitusyhtiö pidättää omistusoikeuden laitteeseen kaikkine lisävarusteineen, kunnes kauppahinta ja muut tähän sopimukseen perustuvat laitetta koskevat maksuvelvoitteet, kuten vakuutus- ja rekisteröintimaksut, on kokonaisuudessaan suoritettu. Rahoitusyhtiön omistusoikeus ulottuu myös laitteeseen kiinnitettyihin laitteisiin, joita ei voida irroittaa vahingoittamatta laitetta.
Myyjä vakuuttaa, ettei laite ole minkään välipuheen perusteella kenenkään velkojen vakuutena tai kiinnityksen kohteena.
Ostaja vakuuttaa, että hänen omistamansa, tämän kaupan yhteydessä käteisosuutena luovuttama laite, on täysin maksettu ja kiinnityksistä vapaa. Lisäksi ostaja vakuuttaa, että vaihtolaitteeseen kohdistuvat kaikki verot ja vakuutusmaksut on maksettu.
Käteisellä maksettavaksi sovittua kauppahinnan osaa ei katsota maksetuksi tämän sopimuksen allekirjoituksella, vaan maksusta tulee laatia erillinen kuitti.
Myyjä ja ostaja vakuuttavat, että maksueristä ei ole annettu tunnusteita.
Ostaja ei saa ilman Rahoitusyhtiön kirjallista suostumusta rekisteröidä laitetta omiin nimiinsä taikka myydä, vuokrata tai pantata laitetta kolmannelle tai viedä sitä Suomen rajojen ulkopuolelle. Ostaja ei saa muutoinkaan luovuttaa laitetta edelleen ilman Rahoitusyhtiön kirjallista suostumusta muutoin kuin tilapäisesti.
Ostajan on sijoitettava laite siten, että se säilyttää irtaimen omaisuuden luonteensa, ts. laitetta ei saa sijoittaa siten, että se muuttuu kiinteistön osaksi tai tarpeistoksi.
Ostaja on velvollinen viipymättä ilmoittamaan Rahoitusyhtiölle, a) jos laite aiotaan ulosmitata, takavarikoida tai panna hukkaamiskiellon alaiseksi ostajan sitoumusten johdosta tai laitetta uhkaa elinkeinonharjoittajan myyntioikeuslain (688/88) nojalla tapahtuva myynti taikka b) jos ostaja asetetaan konkurssiin tai hänen taloudellinen asemansa muuten heikkenee niin, että Rahoitusyhtiön etujen voidaan katsoa vaarantuvan.
Mikäli laite aiotaan ulosmitata, takavarikoida tai panna hukkaamiskiellon alaiseksi, ostaja on velvollinen esittämään tämän sopimuksen toimeenpanevalle viranomaiselle.
\r\n
6 § Oikeus peruuttaa sopimuksen mukainen luotto ja liitännäissopimuksen raukeaminen\r\n\r\n
Kuluttajaostajalla on kuluttajansuojalain 7 luvun 20 §:n mukainen oikeus peruuttaa osamaksusopimuksen mukainen luotto ilmoittamalla siitä Rahoitusyhtiölle kirjallisesti 14 päivän kuluessa sopimuksen tekemisestä tai siitä myöhäisemmästä ajankohdasta, jona ostaja on saanut kuluttajansuojalain 7 luvun 17 §:n mukaiset tiedot sisältävän kappaleen osamaksusopimuksesta kirjallisesti tai sähköisesti. Peruuttamisilmoituksen olisi hyvä olla yksilöity ja sisältää ainakin ostajan nimi, henkilötunnus ja laitteen tunnistetiedot sekä ostajan allekirjoitus.
Jos kuluttajaostaja peruuttaa osamaksusopimuksen mukaisen luoton, Rahoitusyhtiö perii korvauksena luoton koron siltä ajalta, jonka luotto on ollut ostajan käytettävissä. Mikäli Rahoitusyhtiö on maksanut viranomaisille sopimuksen tekemisen johdosta maksuja eikä tällaisia maksuja peruuttamistapauksessa palauteta, Rahoitusyhtiöllä on oikeus saada korvaus ostajalta myös tällaisista maksuista.
Kuluttajaostajan on viivytyksettä ja viimeistään 30 päivän kuluttua peruuttamisilmoituk sen lähettämisestä maksettava Rahoitusyhtiölle tämän sopimuksen perusteella rahoitettu osuus käteishinnasta sekä suoritettava edellä mainitut korvaukset uhalla, että peruuttaminen muutoin raukeaa. Sopimuksen mukaisen luoton peruuttaminen ei vapauta ostajaa laitetta koskevan kauppasopimuksen mukaisista velvoitteista.
Osamaksusopimukseen liittyvä muu sopimus ei sido kuluttajaostajaa tämän peruuttaessa luottosopimuksen, jos liitännäispalvelun tarjoaa Rahoitusyhtiö tai kolmas tämän ja Rahoitusyhtiön välisen sopimuksen tai muun järjestelyn perusteella. Jos kuluttajaostaja haluaa pitää liitännäissopimuksen voimassa luottosopimuksen peruuttamisesta huolimatta, hänen on ilmoitettava tästä Rahoitusyhtiölle 30 päivän kuluessa peruuttamisilmoituksen lähettämisestä.
\r\n
7 § Osamaksuerät ja muut maksut\r\n\r\n
Ostaja maksaa osamaksusopimuksen mukaisen luottonsa erityisiin sopimusehtoihin merkityn maksuohjelman mukaisesti siten, että maksut sisältävät pääoman lyhennyksen, koron sekä sopimuksen etusivulle merkityt palkkiot ja maksut.
Ostajalla on oikeus määrätä, mitä useammista Rahoitusyhtiön ostajalta olevista saatavista hänen suorituksellaan lyhennetään.
Ostaja vastaa dieselveron, pysäköintivirhemaksun, ajoneuvoveron sekä muiden vastaavien verojen ja maksujen maksamisesta sen jälkeen, kun laite on hänelle luovutettu. Mikäli tämän sopimuksen mukaiselle luotolle määrätään leima- tai muu vastaava vero tai maksu, vastaa ostaja aiheutuneesta kustannuksesta. Rahoitusyhtiöllä on oikeus periä ostajalta sopimusehtojen muutoskulut sekä muut sopimuksen hoitokulut kulloinkin voimassa olevan palveluhinnaston mukaan. Rahoitusyhtiön pitää ilmoittaa hinnaston muutoksesta kuluttajaostajalle pysyvällä tavalla ennen muutoksen voimaantuloa.
Ostaja on lisäksi velvollinen korvaamaan saatavan perinnästä aiheutuvat kohtuulliset perimiskulut saatavien perinnästä annetun lain (513/99) mukaisesti.
\r\n
8 § Korko ja viivästyskorko\r\n\r\n
Ostaja on velvollinen maksamaan luotolle erityisten sopimusehtojen 6. kohdassa mainittua korkoa. Kiinteä- korkoisen luoton korko pysyy samana koko luottoajan. Muissa kuin kuluttajakaupoissa Rahoitusyhtiöllä on kuitenkin oikeus tarkistaa kiinteän koron määrää osamaksusopimuksen rahoituspäivänä, mikäli sopimuksen laatimispäivän ja rahoituspäivän välisenä aikana yleisessä korkotasossa on tapahtunut olennaisia muutoksia. Rahoituspäivällä tarkoitetaan päivää, jona Rahoitusyhtiö maksaa rahoitettavan määrän myyjälle.
Viivästyskorko erääntyneistä ja maksamattomista eristä ja muista saatavista määräytyy Korkolain (633/82) mukaisesti. Viivästyskorko on 7 prosenttiyksikköä korkeampi kuin korkolaissa tarkoitettu viitekorko. Mikäli Rahoitusyhtiön ennen luoton erääntymistä perimä korko on edellä mainittua korkolain mukaista viivästyskorkoa suurempi, on Rahoitusyhtiöllä oikeus periä viivästyskorkona tätä korkoa 180 vuorokauden ajan siitä, kun luotto on kokonaisuudessaan erääntynyt, kuitenkin enintään luottoa koskevan tuomioistuimen tuomion antamiseen saakka. Tämän jälkeen peritään korkolain mukaista viivästyskorkoa.
\r\n
9 § Vakuutukset\r\n\r\n
Ostaja on velvollinen kustannuksellaan vakuuttamaan laitteen siten, että {$rahoitusyhtio} merkitään vakuutuksen edunsaajaksi. Ostajan tulee ottaa laitteelle lakiin perustuvat vakuutukset sekä muut Rahoitusyhtiön sopimusta tehtäessä edellyttämät vakuutukset ja pitämään ne voimassa siihen saakka, kunnes laitteen omistusoikeus siirtyy ostajalle. Mikäli ostaja laiminlyö vakuuttamisvelvollisuutensa tai irtisanoo vakuutuksen sopimuksen ehtojen vastaisesti, on Rahoitusyhtiöllä oikeus ottaa laitteelle vakuutus ostajan kustannuksella. Ostaja vastaa vakuutuksen omavastuuosuudesta.
Vakuutusyhtiöllä on oikeus ilmoittaa Rahoitusyhtiölle, mikäli ostaja on irtisanonut vakuutuksen ennen laitteen omistusoikeuden siirtymistä ostajalle taikka mikäli vakuutusyhtiö on irtisanonut vakuutuksen ostajan maksulaiminlyönnin johdosta.
Vakuutustapahtuman sattuessa ostajan tulee tehdä vakuutusyhtiölle vakuutusehdoissa edellytetty vahinkoilmoitus ja ilmoittaa asiasta Rahoitusyhtiölle.
Laitteen vahingoittuminen tai vaurioituminen ei vapauta ostajaa tämän sopimuksen mukaisista velvollisuuksista.
Mikäli laite vahingoittuu, ei Rahoitusyhtiö ole velvollinen kunnostamaan, korjaamaan tai vaihtamaan laitetta. Ostaja on velvollinen toimittamaan laitteen korjattavaksi. Vahingoittuneiden osien sijaan tulleisiin korvaaviin osiin sovelletaan tätä sopimusta muuttumattomana.
Mikäli laite tuhoutuu käyttökelvottomaksi eikä sitä kunnosteta, erääntyy maksamatta oleva osa osamaksuvelasta välittömästi ostajan maksettavaksi.",  $firstpage, $mini);

    $pdf_osamaksusoppari->draw_paragraph(mm_pt(280), mm_pt(100), mm_pt(10), mm_pt(180), "
10 § Rekisteröinti\r\n\r\n
Rekisteröitävän laitteen omistajaksi laitetyyppiä koskevaan rekisteriin (esim. ajoneuvorekisteri, venerekisteri) merkitään {$rahoitusyhtio} ja haltijaksi ostaja, ellei erikseen ole toisin sovittu. Rekisteröinti Liikennevakuutus kustannuksista vastaa ostaja. Sen jälkeen kun laitteen omistusoikeus on siirtynyt ostajalle, on ostaja kustannuksellaan velvollinen tekemään omistusoikeuden siirtymistä koskevan ilmoituksen asianomaiseen rekisteriin.
\r\n
11 § Laitteen huolto, käyttö ja kunnossapito\r\n\r\n
Ostaja on velvollinen omalla kustannuksellaan pitämään kaupan kohteena olevan laitteen hyvässä kunnossa ja huolehtimaan sen vaatimista korjauksista ja huolloista sekä noudattamaan niistä annettuja määräyksiä ja myyjän huolto- ja käyttöohjeita sekä takuuehtoja. Ostaja ei saa muuttaa laitteen rakennetta tai verotuksellista asemaa. Ostaja vastaa tekemistään muutoksista aiheutuvista veroista ja maksuista. Ostaja kustantaa kaikki tarvittavat varaosat ja vastaa kustannuksellaan mahdollisista korvausinvestoinneista luottoaikana. Vanhojen osien tilalle tulevat korvaavat osat tulevat tämän sopimuksen kohteeksi.
Ostaja ei saa antaa laitetta korjattavaksi muulle kuin myyjälle taikka myyjän hyväksymälle korjaamolle. Rahoitusyhtiöllä on oikeus tarkastaa laite ja sen varusteet kuitenkin niin, että siitä ei aiheudu ostajalle kohtuutonta haittaa.
Ostaja on velvollinen huolehtimaan laitteen hyväksyttämisestä, rekisteröinnistä sekä hankkimaan sen käyttöä varten tarvittavat käyttöoikeudet, luvat ja suostumukset sekä tekemään tarvittavat ilmoitukset viranomaisille.
Ostaja ei saa käyttää eikä sallia laitteen käyttöä voimassa olevien lakien tai asetusten vastaisesti.
Rahoitusyhtiö ei vastaa vahingosta, jonka laitteen käyttö voi aiheuttaa ihmisille, eläimille taikka kiinteälle tai irtaimelle omaisuudelle.
\r\n
12 § Ennenaikainen maksu, maksuohjelman muuttaminen ja lyhennystaulukko\r\n\r\n
Ostaja saa halutessaan maksaa koko luoton ennakolta Rahoitusyhtiölle. Kuluttajaostajalla on lisäks i oikeus maksaa osa luotosta ennakolta Rahoitusyhtiölle. Tällöin kuluttajaostajan on kuitenkin etukäteen ilmoitettava Rahoitusyhtiölle pysyvällä tavalla halustaan tehdä sovitusta maksusuunnitelmasta poikkeava ylimääräinen lyhennys, muutoin Rahoitusyhtiö kohdistaa sopimuksen mukaisen maksuerän ylittävän määrän Rahoitusyhtiön etukäteen kuluttajaostajalle pysyvällä tavalla ilmoittamalla tavalla. Rahoitusyhtiön saatava lasketaan kuluttajakaupassa kuluttajansuojalain 7 luvun 27 §:ssä ja muussa osamaksukaupassa osamaksukauppalain 5 §:ssä tarkoitetulla tavalla. Rahoitusyhtiö saa kuitenkin periä kokonaisuudessaan osamaksusopimuksessa yksilöidyt, luoton perustamiseen liittyvistä toimista aiheutuneet kulut.
Rahoitusyhtiö saa periä kuluttajaostajalta korvauksen luoton tai sen osan ennenaikaisesta maksamisesta silloin, kun luotto on sovittu kiinteäkorkoiseksi ja luottoa on maksettu takaisin ennenaikaisesti viimeksi kuluneen 12 kuukauden aikana yli 10 000 euroa. Korvauksen määrä on enintään yksi (1) prosentti takaisinmaksetun luoton määrästä tai, jos ennenaikaisen takaisinmaksun hetkellä sopimuksen päättymiseen on vähemmän kuin vuosi, puoli (0,5) prosenttia takaisinmaksetun luoton määrästä. Korvaus erääntyy maksettavaksi luoton maksun yhteydessä. Muissa kuin kuluttajakaupoissa Rahoitusyhtiöllä on aina oikeus periä ostajalta ennenaikaisesta takaisinmaksusta aiheutuneet jälleenrahoituksen purkukustannukset.
Ostaja ja Rahoitusyhtiö voivat sopia maksuohjelman tai muun sopimusehdon muuttamisesta sopimuskauden aikana. Luotonantajalla on oikeus periä tehdystä muutoksesta kulloinkin voimassa olevan hinnastonsa mukainen palkkio. Kuluttajaostajalla on oikeus saada pyynnöstä maksutta lyhennystaulukko milloin tahansa sopimussuhteen kestäessä.
\r\n
13 § Takaisinotto ja tilitys\r\n\r\n
Rahoitusyhtiöllä on oikeus vaatia koko jäljellä oleva luotto heti maksettavaksi ja laitteen luovuttamista takaisin Rahoitusyhtiölle tilitystä varten, mikäli ostaja on laiminlyönyt osamaksusopimuksen mukaisen maksuvelvollisuutensa siten, että maksamaton maksuerä on ollut maksamatta kuluttajakaupoissa vähintään kuukauden ja muissa kaupoissa vähintään 14 päivää eräpäivästä lukien ja kysymyksessä on erä, joka on vähintään kymmenen (10) prosenttia luottohinnasta tai erä, joka on yhdessä aikaisemmin erääntyneen tai erääntyneiden, maksamatta olevien erien kanssa vähintään viisi (5) prosenttia luottohinnasta taikka koko jäännössaatava. Rahoitusyhtiöllä on oikeus saattaa edellä määrätty toimenpide viivästyneen maksun määrästä riippumatta voimaan, jos maksu on viivästynyt vähintään kuusi (6) kuukautta ja se on olennaisessa määrin edelleen suorittamatta. Rahoitusyhtiöllä on sama oikeus, jos ostaja muutoin olennaisesti rikkoo tämän sopimuksen ehtoja.
Tilitys toimitetaan kuluttajakaupoissa kuluttajansuojalain 7 luvussa säädetyllä tavalla ja muissa kaupoissa osamaksulaissa säädetyllä tavalla.
Mikäli ostaja ja Rahoitusyhtiö eivät pääse tilityksestä yksimielisyyteen, voi Rahoitusyhtiö pyytää ostajan kotipaikan tai kohteen sijaintipaikan ulosotonhaltijalta virka-apua kohteen takaisinottamiseksi ja tilityksen suorittamiseksi. Tällöin ulosottoviranomainen laskee Rahoitusyhtiön saatavan ja määrää laitteen takaisinottoarvon. Muissa kuin kuluttajakaupoissa Rahoitusyhtiöllä on oikeus ottaa saatavan määrässä huomioon myös sopimuksen ennenaikaisesta päättymisestä aiheutuvat jälleenrahoituksen purkukustannukset. Ulosottoviranomaisen päätöksestä voidaan valittaa osamaksukauppalaissa (18.2.66/91) säädetyllä tavalla. Rahoitusyhtiöllä ei kuitenkaan ole oikeutta vaatia edellä mainittua toimenpidettä, mikäli maksun viivästyminen johtuu ostajan sairaudesta, työttömyydestä tai muusta siihen verrattavasta ostajasta riippumattomasta seikasta, paitsi milloin tämä viivästyksen kesto ja muut olosuhteet huomioon ottaen olisi Rahoitusyhtiölle ilmeisen kohtuutonta.
Kuluttajakaupoissa erääntyminen tulee voimaan neljän (4) viikon, tai jos ostajalle oli jo aiemmin ilmoitettu viivästyksestä tai muusta sopimusrikkomuksesta, kahden (2) viikon kuluttua siitä, kun erääntymistä koskeva ilmoitus on jätetty tai lähetetty ostajalle. Jos ostaja tänä aikana maksaa viivästyneen määrän tai oikaisee muun sopimusrikkomuksen, erääntyminen raukeaa.
Rahoitusyhtiöllä on myös oikeus ottaa laite takaisin, vaikka ostajan laiminlyönti ei ole sellainen k uin mitä edellä on edellytetty, jos on tarjolla vaara, että ostaja turmelee tai hävittää laitteen tai laitteen arvo kulumisen tai huonon hoidon johdosta alenee poikkeuksellisen nopeasti. Rahoitusyhtiöllä on oikeus laitteen takaisin ottamiseen ja tilityksen toimittamiseen myös silloin, kun ostaja on jättänyt ottamatta tai irtisanonut sovitut vakuutukset taikka jos vakuutusyhtiö on irtisanonut vakuutukset ostajan maksulaiminlyöntien johdosta.
Rahoitusyhtiö ei luovu omistusoikeudestaan laitteeseen eikä oikeudestaan ottaa laite takaisin, vaikka Rahoitusyhtiö ryhtyisikin oikeudellisin keinoin perimään maksettavaksi erääntyneitä tähän sopimukseen perustuvia saataviaan ostajalta.
\r\n
14 § Maksun laiminlyönnin seuraukset\r\n\r\n
Maksun laiminlyönti saadaan ilmoittaa rekisteröitäväksi Suomen Asiakastieto Oy:n luottotietorekisteriin tai muuhun osapuolten erikseen sopimaan vastaavaan rekisteriin henkilörekisteriasetuksessa tarkoitetulla tavalla sen jälkeen kun maksu on viivästynyt enemmän kuin kolme (3) viikkoa Rahoitusyhtiön eräpäivän jälkeen lähettämästä maksukehotuksesta ja viivästys on tuolloin kestänyt vähintään 60 päivää, eikä viivästys ole johtunut ostajan sairaudesta, työttömyydestä tai muusta erityisestä syystä taikka Rahoitusyhtiön virheestä, ja jos ostaja ei tätä ennen ole ottanut yhteyttä Rahoitusyhtiöön maksuviivästyksen syiden selvittämiseksi. Maksuhäiriömerkintä saattaa vaikeuttaa tulevaa luotonsaantia. Luotonantaja antaa ostajalle maksuviivästystilanteissa tietoa ja neuvoja maksuvaikeuksien syntymisen ja syvenemisen estämiseksi ja maksukyvyttömyystilanteiden hoitamiseksi.
\r\n
15 § Ostajan yhteys- ja luottotiedot\r\n\r\n
Kaikki Rahoitusyhtiön toimesta ostajalle tehtävät ilmoitukset katsotaan tulleen ostajan tietoon, mikäli ne on tehty kirjallisesti ostajan sopimuksessa mainittuun osoitteeseen tai hänen kirjallisesti Rahoitusyhtiölle ilmoittamaansa muuttuneeseen osoitteeseen.
Jos ostajan osoite muuttuu, tulee hänen viipymättä ilmoittaa siitä kirjallisesti Rahoitusyhtiölle. Myyjällä ja Rahoitusyhtiöllä on oikeus tarkastaa ostajan henkilö- tai yhteisötiedot sekä hankkia tarpeelliset luottotiedot luottotietolaissa (527/07) tarkoitetulla tavalla ja tunnistaa ostaja luottolaitostoiminnasta annetun lain (121/07) 135 §:ssä ja rahanpesun ja terrorismin rahoittamisen estämisestä ja selvittämisestä annetussa laissa (503/08) tarkoitetulla tavalla. Poliittisesti vaikutusvaltaisena henkilönä (Politically Exposed Person eli PEP) pidetään edellä mainitun lain 20 §:ssä tarkoitettua henkilöä tai tällaiseen henkilöön läheisessä suhteessa olevaa henkilöä, joka toimii tai on viimeisen 12 kuukauden aikana toiminut merkittävässä julkisessa tehtävässä toisessa valtiossa.
\r\n
16 § Tietojenanto-oikeus\r\n\r\n
Rahoitusyhtiöllä on oikeus luovuttaa ostajaa, tätä osamaksusopimusta ja ostajan taloudellista tilaa koskevia tietoja emo-, tytär- ja osakkuusyhtiöilleen sekä näiden emo-, tytär- ja osakkuusyhteisölle ja hankkia näiltä vastaavia tietoja, jolloin näillä on oikeus ilmoittaa salassapitovelvollisuudenkin alaisia tietoja. Lisäksi Rahoitusyhtiöllä on oikeus luovuttaa tätä sopimusta koskevia tietoja sopimukseen merkitylle kohteen myyjälle ja tämän sijaan tulleelle.
\r\n
17 § Erimielisyyksien ratkaisu\r\n\r\n
Jos erimielisyyksiä ei voida ratkaista osapuolten välisillä neuvotteluilla, kuluttajaostaja voi saada tietoa ja apua myös kuluttajaneuvonnasta (www.kuluttajavirasto.fi). Kuluttajaostaja voi myös saattaa asian kirjallisesti käsiteltäväksi Kuluttajariitalautakuntaan, joka voi antaa erimielisyyden ratkaisemiseksi suosituksen. Kuluttajaostaja tai pienyritys voi kääntyä myös Vakuutus- ja rahoitusneuvonnan (Fine) tai sen yhteydessä toimivan Pankkilautakunnan puoleen (www.fine.fi). Jos erimielisyydet saatetaan tuomioistuimen ratkaistavaksi, kanne on nostettava ostajan asuinpaikan yleisessä alioikeudessa, ellei ostaja halua nostaa kannetta Rahoitusyhtiön kotipaikan yleisessä alioikeudessa. Muissa kaupoissa erimielisyydet ratkaistaan Helsingin käräjäoikeudessa.
\r\n
18 § Valvontaviranomainen\r\n\r\n
Kuluttajansuojalain 7 luvun säännösten noudattamista valvovat kuluttaja-asiamies ja Kuluttajavirasto (www.kuluttajavirasto.fi) ja sen alaisina piirihallintoviranomaisina aluehallintoviranomaiset sekä myös Finanssivalvonta (www.finanssivalvonta.fi) silloin, kun luotonantajana on Finanssivalvonnan valvottava.
\r\n
19 § Sopimusmuutokset\r\n\r\n
Kaikki tähän sopimukseen tehtävät muutokset on tehtävä yhdessä kirjallisesti.",  $firstpage, $mini);


  }
}

if (!function_exists('print_pdf_osamaksusoppari')) {
  function print_pdf_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $komento, $tee = "") {
    global $yhtiorow, $kukarow;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdf_osamaksusopparifilenimi = "/tmp/Osamaksusopimus-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdf_osamaksusopparifilenimi, "w");
    if (fwrite($fh, $pdf_osamaksusoppari->generate()) === FALSE) die("PDF create error $pdf_osamaksusopparifilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'RUUDULLE') {
      return $pdf_osamaksusopparifilenimi;
    }
    elseif ($komento == 'email') {
      $liite = $pdf_osamaksusopparifilenimi;

      $kutsu = t("Osamaksusopimus", $kieli);

      if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
        $kutsu .= ", ".trim($laskurow["nimi"]);
      }

      echo t("Osamaksusopimus tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "../inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdf_osamaksusopparifilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Osamaksusopimus tulostuu")."...<br>";
      $line = exec("$komento $pdf_osamaksusopparifilenimi", $output, $returnvalue);
    }

    //poistetaan tmp file samantien kuleksimasta...
    system("rm -f $pdf_osamaksusopparifilenimi");
  }
}

if (!function_exists('liite_osamaksusoppari')) {
  function liite_osamaksusoppari($pdf_osamaksusoppari, $vkorko, $laskurow, $kieli) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska;

    $firstpage = $pdf_osamaksusoppari->new_page("a4");

    $query = "SELECT sarjanumeroseuranta.sarjanumero, tilausrivi.nimitys
                FROM tilausrivi
                JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
                JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='VENE'
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.otunnus = '$laskurow[tunnus]'
                LIMIT 1";
    $venvelisres = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($venvelisres) > 0) {
      $lisrow = mysql_fetch_array($venvelisres);
    }
    else {
      $query = "SELECT sarjanumeroseuranta.sarjanumero, tilausrivi.nimitys
                  FROM tilausrivi
                  JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
                  JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='MOOTTORI'
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.otunnus = '$laskurow[tunnus]'
                  LIMIT 1";
      $mootlisres = mysql_query($query) or pupe_error($query);

      $lisrow = mysql_fetch_array($mootlisres);
    }


    $pdf_osamaksusoppari->draw_text(mm_pt(150), mm_pt(290), "LIITE OSAMAKSUSOPIMUKSEEN",           $firstpage, $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(285), "OSAMAKSUSOPIMUKSEN KORKOKANTA",         $firstpage, $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(270), "Ostaja:",                     $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(265), "Myyjä:",                     $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(260), "Kaupan kohde:",                 $firstpage, $kymppi_helvetica);


    $pdf_osamaksusoppari->draw_text(mm_pt(40), mm_pt(270), $laskurow["nimi"],                 $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(40), mm_pt(265), $yhtiorow["nimi"],                 $firstpage, $kymppi_helvetica);

    if (strpos($lisrow["sarjanumero"], t("PUUTTUU")) === FALSE and $lisrow["sarjanumero"] != "") {
      $sarjalisa = " (".$lisrow["sarjanumero"].")";
    }
    else {
      $sarjalisa = "";
    }

    $pdf_osamaksusoppari->draw_text(mm_pt(40), mm_pt(260), $lisrow["nimitys"]." $sarjalisa",   $firstpage, $kymppi_helvetica);


    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(250), "Allekirjoituspäivä:",               $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(245), "Rahoituspäivä:",                 $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(240), "Rahoituspäivän noteeraus viitekorolle:",     $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(235), "Marginaali:",                   $firstpage, $kymppi_helvetica);

    $pdf_osamaksusoppari->draw_paragraph(mm_pt(223), mm_pt(10), mm_pt(210), mm_pt(195), "Myyjä on siirtänyt yllä yksilöidyn osamaksusopimuksen kaikkine siihen liittyvine myyjän oikeuksineen Nordea Rahoitus Suomi Oy:lle (rahoittaja).",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(210), mm_pt(10), mm_pt(190), mm_pt(195), "Ostaja sitoutuu maksamaan kulloinkin maksamatta olevalle pääomalle korkoa, joka on sidottu $vkorko -korkoon lisättynä sovitulla marginaalilla. Korkokanta kullakin korkokaudella muodostuu korkokannan vaihtuvasta osasta ($vkorko -korko) lisättynä korkokannan kiinteällä osalla eli marginaalilla. Korko lasketaan todellisten päivien mukaan käyttäen jakajana lukua 360.",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(190), mm_pt(10), mm_pt(170), mm_pt(195), "Euriborkorkoon sidottu osamaksusopimuksen korkokanta ensimmäisellä korkokaudella määräytyy rahoituspäivänä noteeratun viitekoron mukaan. Rahoituspäivä on se päivä, jona rahoittaja maksaa sopimuksen mukaisen suorituksen myyjälle. Noteerauspäivällä tarkoitetaan päivää, jolloin euriborkoron arvo määrätään kulloinkin voimassa olevan kansainvälisen käytännön mukaan.",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(170), mm_pt(10), mm_pt(150), mm_pt(195), "Rahoituspäivänä alkaa myös ensimmäinen koronmääräytymisjakso. Kukin uusi koronmääräytymisjakso alkaa edellisen koronmääräytymisjakson päättymispäivänä. Korkokanta kullekin ensimmäistä korkokautta seuraavalle korkokaudelle määräytyy kaksi noteerauspäivää ennen uuden koronmääräytymisjakson alkamista noteeratun viitekoron mukaan. Korkokausi on 3 kuukautta.",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(150), mm_pt(10), mm_pt(125), mm_pt(195), "Mikäli $vkorko -koron noteeraus lakkaa tai se menettää merkityksensä markkinakorkona, ostaja ja rahoittaja sopivat erikseen käytössä olleen viitekoron korvaavasta uudesta viitekorosta, ellei uudesta viitekorosta ole annettu viranomaisohjetta. Edellä mainitun sopimuksen voimaantuloon saakka sovelletaan viitekoron viimeisintä noteerausta. Mikäli ostaja ja rahoittaja eivät 21 vrk:n kuluessa noteerauksen päättymisestä pääse sopimukseen käytettävästä viitekorosta, rahoittajalla on oikeus sitoa korkokannan vaihtuva osa käytettyä viitekorkoa vastaavaan julkisesti noteerattuun korkoon.",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(122), mm_pt(10), mm_pt(105), mm_pt(195), "Rahoittajalla on oikeus muuttaa korkokannan kiinteää osaa, mikäli rahoittajalle määrätään lainsäädännössä tai viranomaismääräyksissä myös tätä rahoitusta koskeva vero tai muu maksu, joka nostaa rahoittajan jälleenrahoituskustannuksia.",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(105), mm_pt(10), mm_pt(90),  mm_pt(195), "Osamaksusopimuksessa oleva todellinen vuosikorko on laskettu rahoitushetkellä noteerattuun viitekorkoon perustuvalla korkokannalla.",  $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_paragraph(mm_pt(90), mm_pt(10), mm_pt(80),   mm_pt(195), "Olen perehtynyt ylläolevaan ja hyväksyn sen kaikin puolin.",  $firstpage, $kymppi_helvetica);


    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(70), "Paikka ja aika:",                 $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(60), "_______________ __/__ ____",           $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(50), "_________________________",             $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(40), "______________________",             $firstpage, $kymppi_helvetica);
    $pdf_osamaksusoppari->draw_text(mm_pt(10), mm_pt(35), "nimen selvennys",                 $firstpage, $kymppi_helvetica);

  }
}

if (!function_exists('tulosta_osamaksusoppari')) {
  function tulosta_osamaksusoppari($otunnus, $komento, $kieli = "", $tee = "") {
    global $yhtiorow, $kukarow, $vkorko;

    // Haetaan laskun tiedot
    $query = "SELECT laskun_lisatiedot.*, lasku.*, lasku.laatija laatija, lasku.luontiaika luontiaika, lasku.tunnus tunnus
                FROM lasku
                LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
                WHERE lasku.yhtio = '$kukarow[yhtio]'
                and lasku.tunnus  = '$otunnus'";
    $result = mysql_query($query) or pupe_error($query);
    $laskurow = mysql_fetch_array($result);

    if ($kieli== '') {
      $querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
      $kielresult = mysql_query($querykiel) or pupe_error($querykiel);
      $kielnum = mysql_num_rows($kielresult);
      $kielrow = mysql_fetch_array($kielresult);
      $kieli = strtolower($kielrow['kieli']);
    }

    // haetaan maksuehdon tiedot
    $query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $result = mysql_query($query) or pupe_error($query);
    $masrow = mysql_fetch_array($result);

    //maksuehto tekstinä
    $laskurow["maksuehto"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);


    $query  = "SELECT *
                 FROM asiakas
                 WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
    $result = mysql_query($query) or pupe_error($query);
    $asiakasrow = mysql_fetch_array($result);


    function printtaa_rivit_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kala, $lask, $kieli, $sivu, $page_osamaksusoppari, $sarjanutunnus, $osasto, $selite) {
      global $yhtiorow, $kukarow;

      //Tehään tästä taas array tän funktion siäiseen käyttöön jotta saamme sivunvaihdot toimimaan
      $page_osamaksusoppari2[$sivu] = $page_osamaksusoppari;

      $ennakkolisa = "";
      if ($yhtiorow["ennakkomaksu_tuotenumero"] != '') {
        $ennakkolisa = " and tilausrivi.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' ";
      }

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
        $hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
      }
      else {
        $hinta_riv = "tilausrivi.hinta";
      }

      $query_ale_lisa = generoi_alekentta('M');

      $query = "SELECT tilausrivi.*,
                  round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},2) rivihinta,
                  tilausrivin_lisatiedot.positio
                  FROM tilausrivi
                  JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                  LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                  WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
                  and tilausrivi.yhtio     = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi    in  ('L','T','U')
                  $ennakkolisa
                  $osasto
                  LIMIT 1";
      $riresult = mysql_query($query) or pupe_error($query);

      while ($row = mysql_fetch_array($riresult)) {
        list($page_osamaksusoppari2[$sivu], $kala) = rivi_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kala, $kieli, $page_osamaksusoppari2[$sivu], $sarjanutunnus, $selite, $row);

        $lask++;
      }
      return array($page_osamaksusoppari2[$sivu], $lask, $kala);
    }

    //Oletuksia
    $sivu       = 1;
    $lask       = 0;

    $pdf_osamaksusoppari = new pdffile;

    $pdf_osamaksusoppari->set_default('margin-top', 20);
    $pdf_osamaksusoppari->set_default('margin-bottom', 0);
    $pdf_osamaksusoppari->set_default('margin-left', 0);
    $pdf_osamaksusoppari->set_default('margin-right', 0);

    $pdf_osamaksusoppari->enable('import');

    //Sopimuksen pohja
    $page_osamaksusoppari[$sivu] = alku_osamaksusoppari ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_osamaksusoppari);

    $kala = 207;

    // Uusi vene
    list ($page_osamaksusoppari[$sivu], $lask, $kala) = printtaa_rivit_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kala, $lask, $kieli, $sivu, $page_osamaksusoppari[$sivu], "myyntirivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Vene'", "Veneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)");

    $kala = 200.5;

    // Uusi moottori
    list ($page_osamaksusoppari[$sivu], $lask, $kala) = printtaa_rivit_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kala, $lask, $kieli, $sivu, $page_osamaksusoppari[$sivu], "myyntirivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Moottori'", "Moottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)");


    $kala = 189;

    //Vaihtovene
    list ($page_osamaksusoppari[$sivu], $lask, $kala) = printtaa_rivit_osamaksusoppari($pdf_osamaksusoppari, $laskurow, $kala, $lask, $kieli, $sivu, $page_osamaksusoppari[$sivu], "ostorivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Vaihtovene'", "Vaihtoveneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)");

    // Sopimusehdot
    ehdot_osamaksusoppari ($pdf_osamaksusoppari, $laskurow, $kieli);

    if ($vkorko != "Kiinteä") {
      // Ei tarvita enää?
      //liite_osamaksusoppari ($pdf_osamaksusoppari, $vkorko, $laskurow, $kieli);
    }

    // Tulostetaan sivu
    print_pdf_osamaksusoppari ($pdf_osamaksusoppari, $laskurow, $komento, $tee);

    $tee = '';
  }
}
