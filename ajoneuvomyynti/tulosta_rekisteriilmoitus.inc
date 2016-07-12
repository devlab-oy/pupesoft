<?php

  //Fontit
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
  require_once('pdflib/phppdflib.class.php');

  //muutamat funktiot...
  if (!function_exists('alku')) {
    function alku_rekisteriilmoitus ($laskurow, $sivu, $kieli, $pdf_rekisteriilmoitus = "") {
      global $yhtiorow, $kukarow, $pupe_root_polku, $kasi_helvetica, $kymppi_helvetica, $seiska, $rectparam;

      if ($pdf_rekisteriilmoitus == "") {
        $pdf_rekisteriilmoitus = new pdffile;

        $pdf_rekisteriilmoitus->set_default('margin-top', 20);
        $pdf_rekisteriilmoitus->set_default('margin-bottom', 0);
        $pdf_rekisteriilmoitus->set_default('margin-left', 0);
        $pdf_rekisteriilmoitus->set_default('margin-right', 0);
        $pdf_rekisteriilmoitus->enable('import');
      }

      // Haetaan pohja
      ob_start();

      $filename = "{$pupe_root_polku}/ajoneuvomyynti/rekisteriilmoitus.pdf";

      $handle = fopen($filename, "r");
      $d = fread($handle, filesize($filename));
      fclose($handle);

      $pdf_rekisteriilmoitus->import->append($d);

      $pdf_rekisteriilmoitus_sivu = $pdf_rekisteriilmoitus->import->get_pages();

      ob_end_clean();

      tulosta_logo_pdf($pdf_rekisteriilmoitus, $pdf_rekisteriilmoitus_sivu[0], "", 785, 435, 20, 150);

      $query = "  SELECT *
            FROM laskun_lisatiedot
            WHERE yhtio  = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'";
      $lisres = mysql_query($query) or pupe_error($query);
      $lisrow = mysql_fetch_array($lisres);

      $query = "  SELECT *
            FROM asiakas
            WHERE yhtio  = '$kukarow[yhtio]'
            and tunnus = '$laskurow[liitostunnus]'";
      $lisres = mysql_query($query) or pupe_error($query);
      $asiakasrow = mysql_fetch_array($lisres);

      $pp = substr($laskurow["luontiaika"],8,2);
      $kk = substr($laskurow["luontiaika"],5,2);
      $vv = substr($laskurow["luontiaika"],0,4);

      $voimassa = date("d.m.Y",mktime(0, 0, 0, $kk, $pp+14, $vv));
      $luotoaik = date("d.m.Y",mktime(0, 0, 0, $kk, $pp, $vv));

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(280), t("ILMOITUS VESIKULKUNEUVOREKISTERIIN"),      $pdf_rekisteriilmoitus_sivu[0]);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(269), t("Rekisteritunnus").":",              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(45), mm_pt(269), $lisrow["rekisteilmo_rekisterinumero"],       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);


      $pdf_rekisteriilmoitus->draw_text(mm_pt(20),  mm_pt(264), t("Ensirekisteröinti"),              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(50),  mm_pt(264), t("Muutos"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(68),  mm_pt(264), t("Rek.todistuksen kaksoiskappale"),        $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(124), mm_pt(264), t("Poisto"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(140), mm_pt(264), t("Tietojen tarkistus"),              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(172), mm_pt(264), t("Koetunnus"),                  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      if ($lisrow["rekisteilmo_tyyppi"] == "UUSEREKISTERÖINTI" or $lisrow["rekisteilmo_tyyppi"] == "") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(264), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      elseif ($lisrow["rekisteilmo_tyyppi"] == "MUUTOSILMOITUS") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(46),  mm_pt(264), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      elseif ($lisrow["rekisteilmo_tyyppi"] == "KAKSOISKPL") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(64),  mm_pt(264), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      elseif ($lisrow["rekisteilmo_tyyppi"] == "POISTO") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(120),  mm_pt(264), "X",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      elseif ($lisrow["rekisteilmo_tyyppi"] == "TARKISTUS") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(135),  mm_pt(264), "X",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(168),  mm_pt(264), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(70),  mm_pt(259), t("Laminoidaan (maksullinen palvelu, ei kaikissa maistraateissa)"),        $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(175), mm_pt(259), t("Kyllä"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(192), mm_pt(259), t("Ei"),                      $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      if ($lisrow["rekisteilmo_laminointi"] == "K" or $lisrow["rekisteilmo_laminointi"] == "") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(171),  mm_pt(259), "X",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(188),  mm_pt(259), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(251), t("Kieli"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(33), mm_pt(251), "1. ".t("Suomi"),                  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(57), mm_pt(251), "2. ".t("Ruotsi"),                  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      if ($lisrow["rekisteilmo_kieli"] == "SUOMI" or $lisrow["rekisteilmo_kieli"] == "") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(30),  mm_pt(251), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(54),  mm_pt(251), "X",                       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107),  mm_pt(254), t("Kunta, jossa vesikulkuneuvoa pääasiallisesti käytetään"),      $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107),  mm_pt(250), $lisrow["rekisteilmo_paakayttokunta"],       $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(20), mm_pt(73.5), t("Tietoja ei saa luovuttaa markkinointikäyttöön"),  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      if ($lisrow["rekisteilmo_suoramarkkinointi"] != "K") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(16),  mm_pt(73.5), "X",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(95), mm_pt(73.5), t("Tietoja ei saa luovuttaa osoitepalveluun"),  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(140), t("Tiedot omistajista ja haltijoista"),      $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(136.5), t("Omistajan tai haltijan täydellinen nimi"),  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(136.5), t("Henkilö- tai Y-tunnus"),            $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(136.5), t("Kotikunta"),                  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(129),   t("Lähiosoite"),                $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(129),   t("Postinumero ja -toimipaikka"),        $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(129),   t("Omistaja"),                  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(126),   t("Haltija"),                  $pdf_rekisteriilmoitus_sivu[0], $seiska);


      $omsitajakala = 0;

      if ($lisrow["rekisteilmo_omistaja"] == "NORDEA") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "Nordea Rahoitus Suomi Oy",         $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "0112305-3",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "Nihtisillantie 3 G",             $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "ESPOO 00020",                $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);


        $omsitajakala = 1;
      }
      elseif($lisrow["rekisteilmo_omistaja"] == "GE") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "GE Money Oy",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "1465259-5",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "Kuortaneenkatu 2",             $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "HELSINKI 00510",              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

        $omsitajakala = 1;
      }
      elseif($lisrow["rekisteilmo_omistaja"] == "OKO") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "Pohjola Pankki Oyj",             $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "0199920-7",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "PL 308",                   $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "POHJOLA 00013",              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

        $omsitajakala = 1;
      }
      elseif($lisrow["rekisteilmo_omistaja"] == "POHJOLA") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "Pohjola Pankki Oyj",             $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "0199920-7",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "PL 308",                   $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "POHJOLA 00013",              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

        $omsitajakala = 1;
      }
      elseif($lisrow["rekisteilmo_omistaja"] == "SAMPO") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "Sampo Rahoitus",               $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "173074-7",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "PL 1274",                   $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "SAMPO 00075",                $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

        $omsitajakala = 1;
      }
      elseif($lisrow["rekisteilmo_omistaja"] == "SANTAN") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "Santander",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "2076455-0",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "Hermannin Rantatie 10",           $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "HELSINKI 00580",              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

        $omsitajakala = 1;
      }
      elseif($lisrow["rekisteilmo_omistaja"] == "HANDEL") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), "Handelsbanken Rahoitus Oyj ",         $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), "0112308-8",                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), "",                     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   "Aleksanterinkatu 11",             $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   "HELSINKI 00100",              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

        $omsitajakala = 1;
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(132.5), $laskurow["nimi"]." ".$laskurow["nimitark"],   $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(132.5), $laskurow["ovttunnus"],             $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(132.5), $lisrow["rekisteilmo_omistajankotikunta"],     $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(125),   $laskurow["osoite"],               $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(125),   $laskurow["postino"]." ".$laskurow["postitp"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(129),   "X",                        $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(121), "2. ".t("Omistajan tai haltijan täydellinen nimi"),  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(121), t("Henkilö- tai Y-tunnus"),              $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(121), t("Kotikunta"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(114), t("Lähiosoite"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(114), t("Postinumero ja -toimipaikka"),            $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(114),   t("Omistaja"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(111),   t("Haltija"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);

      if ($omsitajakala == 1) {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(117.5), $laskurow["nimi"]." ".$laskurow["nimitark"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(117.5), $laskurow["ovttunnus"],              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(117.5), $lisrow["rekisteilmo_omistajankotikunta"],    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(110), $laskurow["osoite"],                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(110), $laskurow["postino"]." ".$laskurow["postitp"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(110),   "X",                      $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }
      elseif($lisrow["kolm_nimi"] != "") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(117.5), $lisrow["kolm_nimi"]." ".$lisrow["kolm_nimitark"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(117.5), $lisrow["kolm_ovttunnus"],              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(117.5), $lisrow["kolm_postitp"],              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(110), $lisrow["kolm_osoite"],                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(110), $lisrow["kolm_postino"]." ".$lisrow["kolm_postitp"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(114),   "X",                        $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(106), "3. ".t("Omistajan tai haltijan täydellinen nimi"),  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(106), t("Henkilö- tai Y-tunnus"),              $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(106), t("Kotikunta"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(99),  t("Lähiosoite"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(99),  t("Postinumero ja -toimipaikka"),            $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(99),   t("Omistaja"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(96),   t("Haltija"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);

      if ($omsitajakala == 1) {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(102.5), $lisrow["kolm_nimi"]." ".$lisrow["kolm_nimitark"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(102.5), $lisrow["kolm_ovttunnus"],              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(102.5), $lisrow["kolm_postitp"],              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(95),  $lisrow["kolm_osoite"],                 $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(95),  $lisrow["kolm_postino"]." ".$lisrow["kolm_postitp"],  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
        $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(95),   "X",                          $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(91), "4. ".t("Omistajan tai haltijan täydellinen nimi"),  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(91), t("Henkilö- tai Y-tunnus"),              $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(91), t("Kotikunta"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(84),  t("Lähiosoite"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(84),  t("Postinumero ja -toimipaikka"),            $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(84),   t("Omistaja"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(156), mm_pt(81),   t("Haltija"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),    mm_pt(67), t("Poiston syy"),                  $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(19),    mm_pt(62.5), t("Myynti"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(33.5),  mm_pt(62.5), t("Anastus"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(50),    mm_pt(62.5), t("Rikkoutuminen"),                $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(76.5),  mm_pt(62.5), t("Vienti ulkomaille"),              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(105),   mm_pt(62.5), t("Hävitys"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(120.5), mm_pt(62.5), t("Vaihto"),                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(137),   mm_pt(62.5), t("Muu"),                      $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(57), t("Lisätietoja"),                    $pdf_rekisteriilmoitus_sivu[0], $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(47), t("Ilmoittajan nimi"),                  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(77),  mm_pt(47), t("Puhelinnumero"),                  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(137), mm_pt(47), t("Sähköpostiosoite"),                  $pdf_rekisteriilmoitus_sivu[0], $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(42), $yhtiorow["nimi"],                    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);


      if ($kukarow["puhno"] != "") $pdf_rekisteriilmoitus->draw_text(mm_pt(77),  mm_pt(42), $kukarow["puhno"],    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      else $pdf_rekisteriilmoitus->draw_text(mm_pt(77),  mm_pt(42), $yhtiorow["puhelin"],                $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      if ($kukarow["eposti"] != "") $pdf_rekisteriilmoitus->draw_text(mm_pt(137),  mm_pt(42), $kukarow["eposti"],    $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);
      else $pdf_rekisteriilmoitus->draw_text(mm_pt(137), mm_pt(42), $yhtiorow["email"],                $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(37), t("Paikka ja aika"),                        $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(37), t("Omistajan allekirjoitus ja nimen selvennys"),          $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(35), t("(huoltajien allekirjoitukset, jos omistaja alle 18-vuotias)"),  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(30), $yhtiorow["postitp"]." ".date("d.m.Y"),              $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);


      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(201.5), t("Vesikulkuneuvolle annettu (kutsuma) nimi"),  $pdf_rekisteriilmoitus_sivu[0], $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(197.5), $lisrow["rekisteilmo_veneen_nimi"],        $pdf_rekisteriilmoitus_sivu[0], $kymppi_helvetica);

      //Palautetan luotu pdf ja sivu
      return array ($pdf_rekisteriilmoitus, $pdf_rekisteriilmoitus_sivu[0]);
    }
  }

  if (!function_exists('venerivi_rekisteriilmoitus')) {
    function venerivi_rekisteriilmoitus($pdf_rekisteriilmoitus, $laskurow, $kieli, $page_rekisteriilmoitus, $row) {
      global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

      $query = "  SELECT *
            from sarjanumeroseuranta
            where yhtio='$kukarow[yhtio]'
            and tuoteno='$row[tuoteno]'
            and myyntirivitunnus='$row[tunnus]'";
      $sarjares = mysql_query($query) or pupe_error($query);
      $sarjarow = mysql_fetch_array($sarjares);

      $query = "  SELECT sarjanumeron_lisatiedot.*,
            a1.selitetark_2 Ankkurivinssi,
            a2.selitetark_2 Jaakaappi,
            a3.selitetark_2 Koneistus,
            a4.selitetark_2 Kuvatyyppi,
            a5.selitetark_2 Laatuluokitus,
            a6.selitetark_2 Materiaali,
            a7.selitetark_2 Merkki,
            a8.selitetark_2 Koneen_merkki,
            a9.selitetark_2 Moottorin_ohjausjarjestelma,
            a10.selitetark_2 Pilssipumppu,
            a11.selitetark_2 Sijainti,
            a12.selitetark_2 Tiikkisarja,
            a13.selitetark_2 Toimituskulut,
            a14.selitetark_2 Tyynysarja,
            a15.selitetark_2 Tyyppi,
            a16.selitetark_2 Uuni,
            a17.selitetark_2 Valonheitin,
            a18.selitetark_2 Varirunko,
            a19.selitetark_2 WC_ja_suihku
            FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
            LEFT JOIN avainsana a1 use index (yhtio_laji_selitetark) ON a1.yhtio=sarjanumeron_lisatiedot.yhtio and a1.laji='sarjanumeron_li' and a1.selitetark=sarjanumeron_lisatiedot.Ankkurivinssi and a1.selite='ANKKURIVINSSI'
            LEFT JOIN avainsana a2 use index (yhtio_laji_selitetark) ON a2.yhtio=sarjanumeron_lisatiedot.yhtio and a2.laji='sarjanumeron_li' and a2.selitetark=sarjanumeron_lisatiedot.Jaakaappi and a2.selite='JAAKAAPPI'
            LEFT JOIN avainsana a3 use index (yhtio_laji_selitetark) ON a3.yhtio=sarjanumeron_lisatiedot.yhtio and a3.laji='sarjanumeron_li' and a3.selitetark=sarjanumeron_lisatiedot.Koneistus and a3.selite='KONEISTUS'
            LEFT JOIN avainsana a4 use index (yhtio_laji_selitetark) ON a4.yhtio=sarjanumeron_lisatiedot.yhtio and a4.laji='sarjanumeron_li' and a4.selitetark=sarjanumeron_lisatiedot.Kuvatyyppi and a4.selite='KUVATYYPPI'
            LEFT JOIN avainsana a5 use index (yhtio_laji_selitetark) ON a5.yhtio=sarjanumeron_lisatiedot.yhtio and a5.laji='sarjanumeron_li' and a5.selitetark=sarjanumeron_lisatiedot.Laatuluokitus and a5.selite='LAATU'
            LEFT JOIN avainsana a6 use index (yhtio_laji_selitetark) ON a6.yhtio=sarjanumeron_lisatiedot.yhtio and a6.laji='sarjanumeron_li' and a6.selitetark=sarjanumeron_lisatiedot.Materiaali and a6.selite='MATERIAALI'
            LEFT JOIN avainsana a7 use index (yhtio_laji_selitetark) ON a7.yhtio=sarjanumeron_lisatiedot.yhtio and a7.laji='sarjanumeron_li' and a7.selitetark=sarjanumeron_lisatiedot.Merkki and a7.selite='MERKKI'
            LEFT JOIN avainsana a8 use index (yhtio_laji_selitetark) ON a8.yhtio=sarjanumeron_lisatiedot.yhtio and a8.laji='sarjanumeron_li' and a8.selitetark=sarjanumeron_lisatiedot.Koneen_merkki and a8.selite='MOOTTORINMERKKI'
            LEFT JOIN avainsana a9 use index (yhtio_laji_selitetark) ON a9.yhtio=sarjanumeron_lisatiedot.yhtio and a9.laji='sarjanumeron_li' and a9.selitetark=sarjanumeron_lisatiedot.Moottorin_ohjausjarjestelma and a9.selite='MOOTTORINOHJAUS'
            LEFT JOIN avainsana a10 use index (yhtio_laji_selitetark) ON a10.yhtio=sarjanumeron_lisatiedot.yhtio and a10.laji='sarjanumeron_li' and a10.selitetark=sarjanumeron_lisatiedot.Pilssipumppu and a10.selite='PILSSIPUMPPU'
            LEFT JOIN avainsana a11 use index (yhtio_laji_selitetark) ON a11.yhtio=sarjanumeron_lisatiedot.yhtio and a11.laji='sarjanumeron_li' and a11.selitetark=sarjanumeron_lisatiedot.Sijainti and a11.selite='SIJAINTI'
            LEFT JOIN avainsana a12 use index (yhtio_laji_selitetark) ON a12.yhtio=sarjanumeron_lisatiedot.yhtio and a12.laji='sarjanumeron_li' and a12.selitetark=sarjanumeron_lisatiedot.Tiikkisarja and a12.selite='TIIKKISARJA'
            LEFT JOIN avainsana a13 use index (yhtio_laji_selitetark) ON a13.yhtio=sarjanumeron_lisatiedot.yhtio and a13.laji='sarjanumeron_li' and a13.selitetark=sarjanumeron_lisatiedot.Toimituskulut and a13.selite='TOIMITUSKULUT'
            LEFT JOIN avainsana a14 use index (yhtio_laji_selitetark) ON a14.yhtio=sarjanumeron_lisatiedot.yhtio and a14.laji='sarjanumeron_li' and a14.selitetark=sarjanumeron_lisatiedot.Tyynysarja and a14.selite='TYYNYSARJA'
            LEFT JOIN avainsana a15 use index (yhtio_laji_selitetark) ON a15.yhtio=sarjanumeron_lisatiedot.yhtio and a15.laji='sarjanumeron_li' and a15.selitetark=sarjanumeron_lisatiedot.Tyyppi and a15.selite='TYYPPI'
            LEFT JOIN avainsana a16 use index (yhtio_laji_selitetark) ON a16.yhtio=sarjanumeron_lisatiedot.yhtio and a16.laji='sarjanumeron_li' and a16.selitetark=sarjanumeron_lisatiedot.Uuni and a16.selite='UUNI'
            LEFT JOIN avainsana a17 use index (yhtio_laji_selitetark) ON a17.yhtio=sarjanumeron_lisatiedot.yhtio and a17.laji='sarjanumeron_li' and a17.selitetark=sarjanumeron_lisatiedot.Valonheitin and a17.selite='VALONHEITIN'
            LEFT JOIN avainsana a18 use index (yhtio_laji_selitetark) ON a18.yhtio=sarjanumeron_lisatiedot.yhtio and a18.laji='sarjanumeron_li' and a18.selitetark=sarjanumeron_lisatiedot.Varirunko and a18.selite='VARIRUNKO'
            LEFT JOIN avainsana a19 use index (yhtio_laji_selitetark) ON a19.yhtio=sarjanumeron_lisatiedot.yhtio and a19.laji='sarjanumeron_li' and a19.selitetark=sarjanumeron_lisatiedot.WC_ja_suihku and a19.selite='VESSA'
            WHERE sarjanumeron_lisatiedot.yhtio     = '$kukarow[yhtio]'
            and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
      $lisatietores = mysql_query($query) or pupe_error($query);
      $lisarow = mysql_fetch_array($lisatietores);

      $query = "  SELECT *
            FROM laskun_lisatiedot
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'";
      $lisares = mysql_query($query) or pupe_error($query);
      $laslisarow = mysql_fetch_array($lisares);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(243), t("Tiedot vesikulkuneuvosta"),        $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(239), t("Merkki"),                  $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107),  mm_pt(235), $lisarow["Merkki"],              $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(239), t("Malli"),                  $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(152), mm_pt(235), substr($lisarow["Malli"],0,20),        $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(231.5), t("Runkonumero"),              $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(108), mm_pt(228), $sarjarow["sarjanumero"],            $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(201.5), t("Veneen väri"),                $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(198), $lisarow["Varirunko"],            $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(62),  mm_pt(201.5), t("Lisäväri"),                $page_rekisteriilmoitus, $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(210), t("Rakennusvuosi"),              $page_rekisteriilmoitus, $seiska);
      if ($lisarow["Vuosimalli"] != "0000") $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(206), $lisarow["Vuosimalli"],  $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(62),  mm_pt(210), t("Käyttöönottovuosi"),            $page_rekisteriilmoitus, $seiska);
      if ($lisarow["Kayttoonottovuosi"] != "0000") $pdf_rekisteriilmoitus->draw_text(mm_pt(62),  mm_pt(206), $lisarow["Kayttoonottovuosi"],  $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(239), t("Tyyppi"),                  $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(235), $lisarow["Tyyppi"],              $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(231.5), t("Kansirakenne"),              $page_rekisteriilmoitus, $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(20), mm_pt(228.5), t("Avoin"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(35), mm_pt(228.5), t("Osittain katettu"),            $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(65), mm_pt(228.5), t("Katettu"),                $page_rekisteriilmoitus, $kymppi_helvetica);

      if($lisarow["Runkotyyppi"] == "Avoin") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(228.5), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif($lisarow["Runkotyyppi"] == "Katettu") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(60), mm_pt(228.5), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(31), mm_pt(228.5), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(216.5), t("Rungon rakennusmateriaali"),        $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(20),  mm_pt(213), t("Lasikuitu"),                $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(42),  mm_pt(213), t("Kestomuovi"),                $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(70),  mm_pt(213), t("Alumiini"),                $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(91),  mm_pt(213), t("Teräs"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(109), mm_pt(213), t("Puu"),                    $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(124), mm_pt(213), t("Kumi"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(141), mm_pt(213), t("Rauta"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(160), mm_pt(213), t("Betoni"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(180), mm_pt(213), t("Muu"),                    $page_rekisteriilmoitus, $kymppi_helvetica);

      if($lisarow["Materiaali"] == "Lasikuitu") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(213), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif($lisarow["Materiaali"] == "ABS kestomuovi") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(38), mm_pt(213), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif($lisarow["Materiaali"] == "Teräs") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(88), mm_pt(213), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif($lisarow["Materiaali"] == "Alumiini") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(66), mm_pt(213), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif($lisarow["Materiaali"] == "Puu") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(106), mm_pt(213), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(175), mm_pt(213), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107),  mm_pt(210), t("Suurin nopeus"),              $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107),  mm_pt(206), $lisarow["Suurin_nopeus"],          $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(162),  mm_pt(210), t("Suurin henkilöluku"),            $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(162),  mm_pt(206), $lisarow["Suurin_henkiloluku"],        $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(224.5), t("Suurin pituus"),              $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(220.5), $lisarow["Pituus"],              $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(62), mm_pt(224.5), t("Suurin leveys"),              $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(62), mm_pt(220.5), $lisarow["Leveys"],              $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(224.5), t("Purjetunnus"),                $page_rekisteriilmoitus, $seiska);

      return $page_rekisteriilmoitus;
    }
  }

  if (!function_exists('moottoririvi_rekisteriilmoitus')) {
    function moottoririvi_rekisteriilmoitus($pdf_rekisteriilmoitus, $laskurow, $kieli, $page_rekisteriilmoitus, $row, $venerow) {
      global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

      // Löytyykö moottorille omaa sarjanumeroa, vai onko se sisämoottori jolloin pitää hakea veneen sarjanumerolla
      $query = "  SELECT *
            from sarjanumeroseuranta
            where yhtio       = '$kukarow[yhtio]'
            and tuoteno       = '$row[tuoteno]'
            and myyntirivitunnus = '$row[tunnus]'";
      $sarjares = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($sarjares) == 0) {
        $query = "  SELECT *
              from sarjanumeroseuranta
              where yhtio       = '$kukarow[yhtio]'
              and tuoteno       = '$venerow[tuoteno]'
              and myyntirivitunnus = '$venerow[tunnus]'";
        $sarjares = mysql_query($query) or pupe_error($query);

        $onkomoottori = "VENE";
      }
      else {
        $onkomoottori = "MOOT";

        //Löytyykö toista moottoria
        $query = "  SELECT tilausrivi.*,
              tilausrivin_lisatiedot.positio
              FROM tilausrivi
              JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
              and tilausrivi.yhtio = '$kukarow[yhtio]'
              and tilausrivi.tyyppi in  ('L','T','U')
              and tilausrivi.tunnus != '$row[tunnus]'
              HAVING tilausrivin_lisatiedot.positio='Moottori'";
        $riresult2 = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($riresult2) > 0) {
          $moottorirow2 = mysql_fetch_array($riresult2);

          $query = "  SELECT *
                from sarjanumeroseuranta
                where yhtio       = '$kukarow[yhtio]'
                and tuoteno       = '$moottorirow2[tuoteno]'
                and myyntirivitunnus = '$moottorirow2[tunnus]'";
          $sarjares2 = mysql_query($query) or pupe_error($query);
          $sarjarow2 = mysql_fetch_array($sarjares2);

          $onko2moottori = "MOOT";
        }
      }

      $sarjarow = mysql_fetch_array($sarjares);


      $query = "  SELECT sarjanumeron_lisatiedot.*,
            a1.selitetark_2 Ankkurivinssi,
            a2.selitetark_2 Jaakaappi,
            a3.selitetark_2 Koneistus,
            a4.selitetark_2 Kuvatyyppi,
            a5.selitetark_2 Laatuluokitus,
            a6.selitetark_2 Materiaali,
            a7.selitetark_2 Merkki,
            a8.selitetark_2 Koneen_merkki,
            a9.selitetark_2 Moottorin_ohjausjarjestelma,
            a10.selitetark_2 Pilssipumppu,
            a11.selitetark_2 Sijainti,
            a12.selitetark_2 Tiikkisarja,
            a13.selitetark_2 Toimituskulut,
            a14.selitetark_2 Tyynysarja,
            a15.selitetark_2 Tyyppi,
            a16.selitetark_2 Uuni,
            a17.selitetark_2 Valonheitin,
            a18.selitetark_2 Varirunko,
            a19.selitetark_2 WC_ja_suihku
            FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
            LEFT JOIN avainsana a1 use index (yhtio_laji_selitetark) ON a1.yhtio=sarjanumeron_lisatiedot.yhtio and a1.laji='sarjanumeron_li' and a1.selitetark=sarjanumeron_lisatiedot.Ankkurivinssi and a1.selite='ANKKURIVINSSI'
            LEFT JOIN avainsana a2 use index (yhtio_laji_selitetark) ON a2.yhtio=sarjanumeron_lisatiedot.yhtio and a2.laji='sarjanumeron_li' and a2.selitetark=sarjanumeron_lisatiedot.Jaakaappi and a2.selite='JAAKAAPPI'
            LEFT JOIN avainsana a3 use index (yhtio_laji_selitetark) ON a3.yhtio=sarjanumeron_lisatiedot.yhtio and a3.laji='sarjanumeron_li' and a3.selitetark=sarjanumeron_lisatiedot.Koneistus and a3.selite='KONEISTUS'
            LEFT JOIN avainsana a4 use index (yhtio_laji_selitetark) ON a4.yhtio=sarjanumeron_lisatiedot.yhtio and a4.laji='sarjanumeron_li' and a4.selitetark=sarjanumeron_lisatiedot.Kuvatyyppi and a4.selite='KUVATYYPPI'
            LEFT JOIN avainsana a5 use index (yhtio_laji_selitetark) ON a5.yhtio=sarjanumeron_lisatiedot.yhtio and a5.laji='sarjanumeron_li' and a5.selitetark=sarjanumeron_lisatiedot.Laatuluokitus and a5.selite='LAATU'
            LEFT JOIN avainsana a6 use index (yhtio_laji_selitetark) ON a6.yhtio=sarjanumeron_lisatiedot.yhtio and a6.laji='sarjanumeron_li' and a6.selitetark=sarjanumeron_lisatiedot.Materiaali and a6.selite='MATERIAALI'
            LEFT JOIN avainsana a7 use index (yhtio_laji_selitetark) ON a7.yhtio=sarjanumeron_lisatiedot.yhtio and a7.laji='sarjanumeron_li' and a7.selitetark=sarjanumeron_lisatiedot.Merkki and a7.selite='MERKKI'
            LEFT JOIN avainsana a8 use index (yhtio_laji_selitetark) ON a8.yhtio=sarjanumeron_lisatiedot.yhtio and a8.laji='sarjanumeron_li' and a8.selitetark=sarjanumeron_lisatiedot.Koneen_merkki and a8.selite='MOOTTORINMERKKI'
            LEFT JOIN avainsana a9 use index (yhtio_laji_selitetark) ON a9.yhtio=sarjanumeron_lisatiedot.yhtio and a9.laji='sarjanumeron_li' and a9.selitetark=sarjanumeron_lisatiedot.Moottorin_ohjausjarjestelma and a9.selite='MOOTTORINOHJAUS'
            LEFT JOIN avainsana a10 use index (yhtio_laji_selitetark) ON a10.yhtio=sarjanumeron_lisatiedot.yhtio and a10.laji='sarjanumeron_li' and a10.selitetark=sarjanumeron_lisatiedot.Pilssipumppu and a10.selite='PILSSIPUMPPU'
            LEFT JOIN avainsana a11 use index (yhtio_laji_selitetark) ON a11.yhtio=sarjanumeron_lisatiedot.yhtio and a11.laji='sarjanumeron_li' and a11.selitetark=sarjanumeron_lisatiedot.Sijainti and a11.selite='SIJAINTI'
            LEFT JOIN avainsana a12 use index (yhtio_laji_selitetark) ON a12.yhtio=sarjanumeron_lisatiedot.yhtio and a12.laji='sarjanumeron_li' and a12.selitetark=sarjanumeron_lisatiedot.Tiikkisarja and a12.selite='TIIKKISARJA'
            LEFT JOIN avainsana a13 use index (yhtio_laji_selitetark) ON a13.yhtio=sarjanumeron_lisatiedot.yhtio and a13.laji='sarjanumeron_li' and a13.selitetark=sarjanumeron_lisatiedot.Toimituskulut and a13.selite='TOIMITUSKULUT'
            LEFT JOIN avainsana a14 use index (yhtio_laji_selitetark) ON a14.yhtio=sarjanumeron_lisatiedot.yhtio and a14.laji='sarjanumeron_li' and a14.selitetark=sarjanumeron_lisatiedot.Tyynysarja and a14.selite='TYYNYSARJA'
            LEFT JOIN avainsana a15 use index (yhtio_laji_selitetark) ON a15.yhtio=sarjanumeron_lisatiedot.yhtio and a15.laji='sarjanumeron_li' and a15.selitetark=sarjanumeron_lisatiedot.Tyyppi and a15.selite='TYYPPI'
            LEFT JOIN avainsana a16 use index (yhtio_laji_selitetark) ON a16.yhtio=sarjanumeron_lisatiedot.yhtio and a16.laji='sarjanumeron_li' and a16.selitetark=sarjanumeron_lisatiedot.Uuni and a16.selite='UUNI'
            LEFT JOIN avainsana a17 use index (yhtio_laji_selitetark) ON a17.yhtio=sarjanumeron_lisatiedot.yhtio and a17.laji='sarjanumeron_li' and a17.selitetark=sarjanumeron_lisatiedot.Valonheitin and a17.selite='VALONHEITIN'
            LEFT JOIN avainsana a18 use index (yhtio_laji_selitetark) ON a18.yhtio=sarjanumeron_lisatiedot.yhtio and a18.laji='sarjanumeron_li' and a18.selitetark=sarjanumeron_lisatiedot.Varirunko and a18.selite='VARIRUNKO'
            LEFT JOIN avainsana a19 use index (yhtio_laji_selitetark) ON a19.yhtio=sarjanumeron_lisatiedot.yhtio and a19.laji='sarjanumeron_li' and a19.selitetark=sarjanumeron_lisatiedot.WC_ja_suihku and a19.selite='VESSA'
            WHERE sarjanumeron_lisatiedot.yhtio     = '$kukarow[yhtio]'
            and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
      $lisatietores = mysql_query($query) or pupe_error($query);
      $lisarow = mysql_fetch_array($lisatietores);

      if ($lisarow["Koneiden_lkm"] > 1) {
        $onko2moottori = "MOOT";

        list($lisarow["Koneen_sarjanumero"], $sarjarow2["sarjanumero"]) = explode(",", $lisarow["Koneen_sarjanumero"]);
      }

      $query = "  SELECT *
            FROM laskun_lisatiedot
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'";
      $lisares = mysql_query($query) or pupe_error($query);
      $laslisarow = mysql_fetch_array($lisares);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(192), t("Tiedot moottoreista"),              $page_rekisteriilmoitus, $kymppi_helvetica);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(180), "1. ".t("Moottorin merkki ja malli"),        $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(173), "2. ".t("Moottorin merkki ja malli "),      $page_rekisteriilmoitus, $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(176), $lisarow["Koneen_merkki"]." ".$lisarow["Koneen_malli"],  $page_rekisteriilmoitus, $kymppi_helvetica);

      if ($onko2moottori == "MOOT") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(169), $lisarow["Koneen_merkki"]." ".$lisarow["Koneen_malli"],  $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(180), t("Vuosimalli"),                  $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(173), t("Vuosimalli"),                  $page_rekisteriilmoitus, $seiska);

      if ($lisarow["Koneen_vuosimalli"] != "0000") $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(176), $lisarow["Koneen_vuosimalli"],  $page_rekisteriilmoitus, $kymppi_helvetica);

      if ($onko2moottori == "MOOT") {
        if ($lisarow["Koneen_vuosimalli"] != "0000") $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(169), $lisarow["Koneen_vuosimalli"],        $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(137), mm_pt(180), t("Käyttöönottovuosi"),              $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(137), mm_pt(173), t("Käyttöönottovuosi"),              $page_rekisteriilmoitus, $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(137), mm_pt(176), $lisarow["Koneen_kayttonottovuosi"],        $page_rekisteriilmoitus, $kymppi_helvetica);

      if ($onko2moottori == "MOOT") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(137), mm_pt(169), $lisarow["Koneen_kayttonottovuosi"],      $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(165), t("Polttoaine"),                  $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(20),  mm_pt(161.5), t("Bensiini"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(45),  mm_pt(161.5), t("Diesel"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(70),  mm_pt(161.5), t("Kaasuöljy/Kevyt polttoöljy"),        $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(120),  mm_pt(161.5), t("Sähkö"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(140),  mm_pt(161.5), t("Muu"),                    $page_rekisteriilmoitus, $kymppi_helvetica);

      if ($lisarow["Polttoaine"] == "Bensiini") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(161.5), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(41),  mm_pt(161.5), "X",                    $page_rekisteriilmoitus, $kymppi_helvetica);
      }


      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(188), t("Moottorin tyyppi"),                $page_rekisteriilmoitus, $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(19),  mm_pt(184), t("Perämoottori"),                $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(50),  mm_pt(184), t("Sisäperämoottori"),              $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(87),  mm_pt(184), t("Sisämoottori"),                $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(119), mm_pt(184), t("Vesisuihku"),                  $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(145), mm_pt(184), t("Hydrokopteri"),                $page_rekisteriilmoitus, $kymppi_helvetica);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(175), mm_pt(184), t("Muu"),                      $page_rekisteriilmoitus, $kymppi_helvetica);

      if ($lisarow["Koneistus"] == "Perämoottori") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15),  mm_pt(184), "X",                      $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif ($lisarow["Koneistus"] == "Sisäperämoottori") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(46),  mm_pt(184), "X",                      $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif ($lisarow["Koneistus"] == "Sisämoottori") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(84),  mm_pt(184), "X",                      $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      elseif ($lisarow["Koneistus"] == "Vesisuihku") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(115),  mm_pt(184), "X",                      $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(170),  mm_pt(184), "X",                      $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(157.5), "1. ".t("moottorin teho"),            $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(150), "2. ".t("moottorin teho"),              $page_rekisteriilmoitus, $seiska);

      $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(153.5),   $lisarow["Teho_kw"]." kW    tai     ".$lisarow["Teho_hv"]." hv",  $page_rekisteriilmoitus, $kymppi_helvetica);

      if ($onko2moottori == "MOOT") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(15), mm_pt(146),   $lisarow["Teho_kw"]." kW    tai     ".$lisarow["Teho_hv"]." hv",  $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(157.5), "1. ".t("moottorin tunnistenumero"),      $page_rekisteriilmoitus, $seiska);
      $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(150), "2. ".t("moottorin tunnistenumero"),        $page_rekisteriilmoitus, $seiska);

      if ($onkomoottori == "MOOT") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(153.5),   $sarjarow["sarjanumero"],        $page_rekisteriilmoitus, $kymppi_helvetica);
      }
      else {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(153.5),   $lisarow["Koneen_sarjanumero"],      $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      if ($onko2moottori == "MOOT") {
        $pdf_rekisteriilmoitus->draw_text(mm_pt(107), mm_pt(146),   $sarjarow2["sarjanumero"],          $page_rekisteriilmoitus, $kymppi_helvetica);
      }

      return $page_rekisteriilmoitus;
    }
  }

  if (!function_exists('print_pdf_rekisteriilmoitus')) {
    function print_pdf_rekisteriilmoitus ($pdf_rekisteriilmoitus, $laskurow, $komento, $tee = "") {
      global $yhtiorow, $kukarow;

      $oslapp='';
      $returnvalue=0;

      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdf_rekisteriilmoitusfilenimi = "/tmp/Tarjous-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdf_rekisteriilmoitusfilenimi, "w");
      if (fwrite($fh, $pdf_rekisteriilmoitus->generate()) === FALSE) die("PDF create error $pdf_rekisteriilmoitusfilenimi");
      fclose($fh);

      // itse print komento...
      if ($komento == 'RUUDULLE') {
        return $pdf_rekisteriilmoitusfilenimi;
      }
      elseif ($komento == 'email') {
        $liite = $pdf_rekisteriilmoitusfilenimi;

        $kutsu = t("Rekisteröinti-ilmoitus", $kieli);

        if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
          $kutsu .= ", ".trim($laskurow["nimi"]);
        }

        echo t("Rekisteröinti-ilmoitus tulostuu")."...<br>";

        if ($kukarow["extranet"] == '') {
          require("../inc/sahkoposti.inc");
        }
        else {
          require("sahkoposti.inc");
        }
      }
      elseif ($tee == 'NAYTATILAUS') {
        //Työnnetään tuo pdf vaan putkeen!
        echo file_get_contents($pdf_rekisteriilmoitusfilenimi);
      }
      elseif ($komento != '' and $komento != 'edi') {
        echo t("Rekisteröinti-ilmoitus tulostuu")."...<br>";
        $line = exec("$komento $pdf_rekisteriilmoitusfilenimi", $output, $returnvalue);
      }

      //poistetaan tmp file samantien kuleksimasta...
      system("rm -f $pdf_rekisteriilmoitusfilenimi");
    }
  }

  if (!function_exists('tulosta_rekisteriilmoitus')) {
    function tulosta_rekisteriilmoitus ($otunnus, $komento, $kieli = "", $tee = "") {
      global $yhtiorow, $kukarow;

      // Haetaan laskun tiedot
      $query = "  SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$otunnus'";
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
      $query  = "SELECT * from maksuehto where tunnus='$laskurow[maksuehto]'";
      $result = mysql_query($query) or pupe_error($query);
      $masrow = mysql_fetch_array($result);

      //maksuehto tekstinä
      $laskurow["maksuehto"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

      //korjataan yhtiön tietoja
      $query  = "  SELECT *
            from kuka
            where tunnus = '$laskurow[myyja]'
            and yhtio = '$kukarow[yhtio]'";
      $myyresult = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($myyresult) == 1) {
        $myyrow = mysql_fetch_array($myyresult);

        $kukarow["toimipaikka"] = $myyrow["toimipaikka"];
      }

      $query = "  SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[toimipaikka]'";
      $result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

      if (mysql_num_rows($result) == 1) {
        $yhtion_toimipaikkarow = mysql_fetch_array($result);

        $yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
        $yhtiorow["nimi"]     = $yhtion_toimipaikkarow["nimi"];
        $yhtiorow["osoite"]   = $yhtion_toimipaikkarow["osoite"];
        $yhtiorow["postino"]   = $yhtion_toimipaikkarow["postino"];
        $yhtiorow["postitp"]   = $yhtion_toimipaikkarow["postitp"];
        $yhtiorow["puhelin"]   = $yhtion_toimipaikkarow["puhelin"];

      }

      $sivu = 1;

      // Pohja
      list ($pdf_rekisteriilmoitus, $page_rekisteriilmoitus[$sivu]) = alku_rekisteriilmoitus ($laskurow, $sivu, $kieli, "", "");

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
        $hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
      }
      else {
        $hinta_riv = "tilausrivi.hinta";
      }

      $query_ale_lisa = generoi_alekentta('M');

      $query = "  SELECT tilausrivi.*,
            round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2) rivihinta,
            tilausrivin_lisatiedot.positio
            FROM tilausrivi
            JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
            LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
            WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
            and tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.tyyppi in  ('L','T','U')";


      $query1 = $query." HAVING tilausrivin_lisatiedot.positio='Vene'";
      $riresult = mysql_query($query1) or pupe_error($query1);
      $venerow = mysql_fetch_array($riresult);
      $page_rekisteriilmoitus[$sivu] = venerivi_rekisteriilmoitus($pdf_rekisteriilmoitus, $laskurow, $kieli, $page_rekisteriilmoitus[$sivu], $venerow);

      $query1 = $query." HAVING tilausrivin_lisatiedot.positio='Moottori'";
      $riresult = mysql_query($query1) or pupe_error($query1);
      $moottorirow = mysql_fetch_array($riresult);

      $page_rekisteriilmoitus[$sivu] = moottoririvi_rekisteriilmoitus($pdf_rekisteriilmoitus, $laskurow, $kieli, $page_rekisteriilmoitus[$sivu], $moottorirow, $venerow);

      // Tulostetaan sivu
      print_pdf_rekisteriilmoitus ($pdf_rekisteriilmoitus, $laskurow, $komento, $tee);

      $tee = '';
    }
  }
