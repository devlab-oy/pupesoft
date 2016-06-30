<?php

//Fontit
$seiska["height"]       = 7;
$seiska["font"]       = "Times-Roman";

$kymppi["height"]       = 10;
$kymppi["font"]       = "Times-Roman";

$kymppi_courier["height"]   = 10;
$kymppi_courier["font"]   = "Courier";

$kasi_courier["height"]   = 8;
$kasi_courier["font"]     = "Courier";

$kymppi_helvetica["height"] = 10;
$kymppi_helvetica["font"]   = "Helvetica";

$kasi_helvetica["height"]   = 8;
$kasi_helvetica["font"]   = "Helvetica";

$rectparam["width"]      = 0.2;

//Haetaan pdflibrary
require_once 'pdflib/phppdflib.class.php';

//muutamat funktiot...
if (!function_exists('alku_myyntisopimus')) {
  function alku_myyntisopimus($laskurow, $asiakasrow, $sivu, $kieli, $pdf_myyntisopimus) {
    global $yhtiorow, $kukarow, $pupe_root_polku, $kasi_helvetica, $kymppi_helvetica, $seiska, $rectparam;

    // Haetaan pohja
    ob_start();

    $filename = "{$pupe_root_polku}/ajoneuvomyynti/myyntisopimus.pdf";

    $handle = fopen($filename, "r");
    $d = fread($handle, filesize($filename));
    fclose($handle);

    $pdf_myyntisopimus->import->append($d);

    $pdf_myyntisopimus_sivu = $pdf_myyntisopimus->import->get_pages();

    ob_end_clean();

    tulosta_logo_pdf($pdf_myyntisopimus, $pdf_myyntisopimus_sivu[0], "", 0, 0, 30, 200);

    $pp = substr($laskurow["luontiaika"], 8, 2);
    $kk = substr($laskurow["luontiaika"], 5, 2);
    $vv = substr($laskurow["luontiaika"], 0, 4);

    $voimassa = date("d.m.Y", mktime(0, 0, 0, $kk, $pp+14, $vv));
    $luotoaik = date("d.m.Y", mktime(0, 0, 0, $kk, $pp, $vv));

    $pdf_myyntisopimus->draw_text(mm_pt(110),  mm_pt(280), t("MYYNTISOPIMUS", $kieli),              $pdf_myyntisopimus_sivu[0]);
    $pdf_myyntisopimus->draw_text(mm_pt(75),   mm_pt(275), t("Pvm", $kieli).": $luotoaik",            $pdf_myyntisopimus_sivu[0], $kasi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(160),  mm_pt(284), t("Numero", $kieli),                  $pdf_myyntisopimus_sivu[0], $kasi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(170),  mm_pt(280), $laskurow["tunnus"],                  $pdf_myyntisopimus_sivu[0]);
    $pdf_myyntisopimus->draw_text(mm_pt(110),  mm_pt(275), t("Tämän sopimuksen ehdot on annettu ostajalle erillisenä liitteenä", $kieli),  $pdf_myyntisopimus_sivu[0], $seiska);


    $pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(275), t("MYYJÄ", $kieli),                  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(272), t("Nimi", $kieli),                  $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(77),   mm_pt(272), t("Y-tunnus", $kieli),                $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(272), t("Puhelin", $kieli),                $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(272), t("Pankkiyhteys", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(267.5), $yhtiorow["nimi"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(78),   mm_pt(267.5), $yhtiorow["ytunnus"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(267.5), $yhtiorow["puhelin"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(267.5), $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(264), t("Lähiosoite", $kieli),                $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(77),   mm_pt(264), t("Postinumero", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(264), t("Postitoimipaikka", $kieli),            $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(264), t("Henkilön nimi", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(260.5), $yhtiorow["osoite"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(78),   mm_pt(260.5), $yhtiorow["postino"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(260.5), $yhtiorow["postitp"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $query  = "SELECT *
                 from kuka
                 where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
    $myyresult = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($myyresult) == 1) {
      $myyrow = mysql_fetch_array($myyresult);
    }
    else {
      $myyrow = $kukarow;
    }

    $pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(260.5), $myyrow["nimi"],                  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(254), t("VÄLITTÄJÄ", $kieli),                $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(250), t("Nimi", $kieli),                  $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(79),   mm_pt(250), t("Lähiosoite", $kieli),                $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(147),  mm_pt(250), t("Puhelin", $kieli),                $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(246), $yhtiorow[""],                    $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(79),   mm_pt(246), $yhtiorow[""],                     $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(147),  mm_pt(246), $yhtiorow[""],                     $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(241.5), t("OSTAJA (Rinnakkaisostajan tiedot merkitään vain jos yhteisvastuullinen)", $kieli),  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(239), t("Sukunimi ja puhutteluetunimi", $kieli),      $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(61),   mm_pt(239), t("Muut etunimet", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(239), t("Tarkastettu henkilötunnus", $kieli),        $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(239), t("Arvo tai ammatti", $kieli),            $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(234.5), $laskurow["nimi"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(62),   mm_pt(234.5), $laskurow["nimitark"],               $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(234.5), $laskurow["ovttunnus"],              $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(234.5), $laskurow[""],                    $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(231.5), t("Lähiosoite", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(61),   mm_pt(231.5), t("Postinumero", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(231.5), t("Postitoimipaikka", $kieli),            $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(231.5), t("Pankkiyhteys", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(227.5), $laskurow["osoite"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(62),   mm_pt(227.5), $laskurow["postino"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(227.5), $laskurow["postitp"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(227.5), $laskurow[""],                    $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(224.5), t("Kotipuhelin", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(41),   mm_pt(224.5), t("Matkapuhelin", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(75),   mm_pt(224.5), t("Työnantaja", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(224.5), t("Sähköposti", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(220.5), $asiakasrow["puhelin"],               $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(42),   mm_pt(220.5), $asiakasrow["gsm"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(76),   mm_pt(220.5), $asiakasrow["tyonantaja"],             $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(220.5), $asiakasrow["email"],                $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(10.5), mm_pt(217), t("Rinnakkaisostajan sukunimi ja puhutteluetunimi", $kieli),  $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(61),   mm_pt(217), t("Muut etunimet", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(217), t("Tarkastettu henkilötunnus", $kieli),        $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(217), t("Arvo tai ammatti", $kieli),            $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(213), $laskurow["kolm_nimi"],                 $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(62),   mm_pt(213), $laskurow["kolm_nimitark"],               $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(213), $laskurow["kolm_ovttunnus"],             $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(213), $laskurow[""],                    $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(27),   mm_pt(122), t("Toimitusehto", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(27),   mm_pt(85),  t("Vaihtokohteen toimitusehto", $kieli),        $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(28),   mm_pt(118), $laskurow["toimitusehto"],              $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(28),   mm_pt(81),  $laskurow["toimitusehto2"],              $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(206), t("KAUPAN", $kieli),                  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(202), t("KOHDE", $kieli),                  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(112), t("VAIHTO", $kieli),                  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(108), t("KOHDE", $kieli),                  $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(129),  mm_pt(118.5), t("Hinta yhteensä", $kieli),            $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(129),  mm_pt(81), t("Hyvityshinta yhteensä", $kieli),          $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(129),  mm_pt(73), t("Ostajan maksettavaksi jää", $kieli),        $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);


    $pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(70), t("MAKSUN SUORITUS", $kieli),              $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(66), t("Ennakkomaksu tilattaessa", $kieli),        $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(71),   mm_pt(66), t("Ennakkomaksu ennen luovutusta", $kieli),      $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(130),  mm_pt(66), t("Luovutettaessa suoritus", $kieli),          $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(130),  mm_pt(62), t("Luovutettaessa rahoitus", $kieli),          $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(130),  mm_pt(57.5), t("Luovutettaessa yhteensä", $kieli),        $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(51.5), t("ALLEKIRJOITUKSET", $kieli),            $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(48.5), t("Myyjä myy ja luovuttaa ostajalle yllämainitut tavarat tähän sopimukseen perustuvin ehdoin.", $kieli), $pdf_myyntisopimus_sivu[0], $seiska);

    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(46), t("Ostaja vakuuttaa, että hänen vaihtokohteestaan antamat tiedot ovat paikkansapitäviä, että se on täysin maksettu ja, että se on kokonaan hänen omaisuuttaan.", $kieli), $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(42.5), t("Allekirjoituksellaan ostaja kuittaa vastaanottaneensa liitteenä tämän sopimuksen ehdot,", $kieli), $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(11),  mm_pt(39), t("tutustuneensa niihin ja hyväksyvänsä sopimuksen täysin.", $kieli), $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(11),  mm_pt(35), t("Paikka ja aika", $kieli),              $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(12),  mm_pt(31.2), $yhtiorow["postitp"]." ".date("d.m.Y"),    $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(28), t("Ostajan allekirjoitus", $kieli),          $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(71),   mm_pt(28), t("Rinnakkaisostajan allekirjoitus", $kieli),      $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(131),  mm_pt(35), t("Myyjän nimi ja allekirjoitus", $kieli),      $pdf_myyntisopimus_sivu[0], $seiska);

    $pdf_myyntisopimus->draw_text(mm_pt(133), mm_pt(20), $myyrow["nimi"]."   ".$yhtiorow["nimi"],    $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(13),  mm_pt(20), $laskurow["nimi"],                $pdf_myyntisopimus_sivu[0], $seiska);
    $pdf_myyntisopimus->draw_text(mm_pt(75),  mm_pt(20), $laskurow["kolm_nimi"],            $pdf_myyntisopimus_sivu[0], $seiska);

    $rectp = array();
    $rectp['mode']           = "fill";
    $rectp['fillcolor']['red']     = 1;
    $rectp['fillcolor']['blue']    = 1;
    $rectp['fillcolor']['green']   = 1;

    $pdf_myyntisopimus->draw_rectangle(mm_pt(19), mm_pt(10),  mm_pt(5), mm_pt(200),          $pdf_myyntisopimus_sivu[0], $rectp);

    $x[0] = mm_pt(10);
    $x[1] = mm_pt(199);
    $y[0] = mm_pt(19);
    $y[1] = mm_pt(19);
    $pdf_myyntisopimus->draw_line($x, $y, $pdf_myyntisopimus_sivu[0], $rectparam);

    $pdf_myyntisopimus->draw_paragraph(mm_pt(18), mm_pt(10), mm_pt(10), mm_pt(190), t("Omistusoikeus kaupan kohteeseen ei siirry ostajalle ennen kuin kauppahinta kokonaisuudessaan on maksettu ja muut tämän sopimuksen tarkoittamat, kaupan kohdetta koskevat ostajan maksuvelvoitteet on täysin täytetty.", $kieli), $pdf_myyntisopimus_sivu[0], $seiska);

    //Palautetan luotu sivu
    return $pdf_myyntisopimus_sivu[0];
  }
}

if (!function_exists('rivi_myyntisopimus')) {
  function rivi_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $kieli, $page_myyntisopimus, $sarjanutunnus, $selite, $row, $hinnat, $lask) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier, $kasi_courier;

    $rivinkorkeus  = 7.1;

    if ($sarjanutunnus != "") {
      $query = "SELECT *
                  from sarjanumeroseuranta
                  where yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$row[tuoteno]'
                  and $sarjanutunnus = '$row[tunnus]'";
      $sarjares = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($sarjares) > 0) {
        $sarjarow = mysql_fetch_array($sarjares);

        if (strpos($sarjarow["sarjanumero"], t("PUUTTUU", $kieli)) === FALSE and $sarjarow["sarjanumero"] != "") {
          $sarjalisa = " (".$sarjarow["sarjanumero"].")";
        }
      }
    }
    else {
      $sarjalisa = "";
    }

    if ($row["rivihinta"] < 0) {
      $row["rivihinta"] = abs($row["rivihinta"]);
    }

    if ($row["bruttorivihinta"] < 0) {
      $row["bruttorivihinta"] = abs($row["bruttorivihinta"]);
    }

    if ($selite == "") {
      $selite = $row["positio"];
    }
    if ($selite == "") {
      $selite = t("Muuta", $kieli);
    }

    $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala+4), t($selite, $kieli),  $page_myyntisopimus, $seiska);

    if (stripos($selite, 'Vaihto') === FALSE) {
      $kommlisa = " ".$row["kommentti"];
    }
    else {
      $kommlisa = "";
    }

    $kommlisa2 = "";

    if ($row["tuoteno"] == 'VAVE') {
      $query = "SELECT
                  #sarjanumeron_lisatiedot.Polttoainetankki,
                  #sarjanumeron_lisatiedot.Kayttovesisailio 'Käyttövesisäiliö',
                  #sarjanumeron_lisatiedot.Septitankki,
                  #a9.selitetark_2 Moottorin_ohjausjärjestelmä,
                  #concat_ws(' ',a8.selitetark_2,sarjanumeron_lisatiedot.Koneen_malli,sarjanumeron_lisatiedot.Teho_hv) Koneistus,
                  sarjanumeron_lisatiedot.WC_lukumaara 'WC_lukumäärä',
                  a19.selitetark_2 WC_ja_suihku,
                  sarjanumeron_lisatiedot.Liesin_Ceran_pinnalla,
                  sarjanumeron_lisatiedot.Nopeusmittari,
                  sarjanumeron_lisatiedot.Kompassi,
                  sarjanumeron_lisatiedot.Defroster,
                  sarjanumeron_lisatiedot.Mikroaaltouuni,
                  sarjanumeron_lisatiedot.Pakastin,
                  sarjanumeron_lisatiedot.TV_antenni,
                  sarjanumeron_lisatiedot.GPS,
                  sarjanumeron_lisatiedot.Satamapeite,
                  sarjanumeron_lisatiedot.Ajokuomu,
                  sarjanumeron_lisatiedot.Maasahko 'Maasähkö',
                  sarjanumeron_lisatiedot.Generaattori,
                  sarjanumeron_lisatiedot.Ankkuri,
                  sarjanumeron_lisatiedot.Keulapotkuri,
                  sarjanumeron_lisatiedot.Lamminvesijarjestelma 'Lämminvesijärjestelmä',
                  sarjanumeron_lisatiedot.Trimmitasot,
                  sarjanumeron_lisatiedot.Makeavesijarjestelma 'Makeavesijärjestelmä',
                  sarjanumeron_lisatiedot.Suihku_WCssa 'Suihku_WCssä',
                  sarjanumeron_lisatiedot.Suihku_uimatasolla,
                  sarjanumeron_lisatiedot.Sailytyspukki,
                  sarjanumeron_lisatiedot.Raitatiikkilattia,
                  sarjanumeron_lisatiedot.Myrkkymaalaus,
                  sarjanumeron_lisatiedot.Runkovalonheittimet,
                  sarjanumeron_lisatiedot.Vesi_WC,
                  sarjanumeron_lisatiedot.Runkoikkunat,
                  sarjanumeron_lisatiedot.Avotilan_poyta,
                  sarjanumeron_lisatiedot.Kaksoisakkujarjestelma 'Kaksoisakkujärjestelmä',
                  sarjanumeron_lisatiedot.Sumutorvi,
                  sarjanumeron_lisatiedot.Keulaluukku,
                  sarjanumeron_lisatiedot.Avotilan_penkki,
                  sarjanumeron_lisatiedot.Liesituuletin,
                  sarjanumeron_lisatiedot.Tuulilasin_peite,
                  sarjanumeron_lisatiedot.Aurinkokatos,
                  sarjanumeron_lisatiedot.Avotilan_kuomu,
                  sarjanumeron_lisatiedot.Jaapalakone 'Jääpalakone',
                  sarjanumeron_lisatiedot.Liesi,
                  sarjanumeron_lisatiedot.Kaiku,
                  concat_ws(' ',sarjanumeron_lisatiedot.Jaakaappi_kpl,a2.selitetark_2) 'Jääkääppi',
                  sarjanumeron_lisatiedot.Tutka,
                  sarjanumeron_lisatiedot.Karttaplotteri,
                  a17.selitetark_2 Valonheitin,
                  a1.selitetark_2 Ankkurivinssi,
                  sarjanumeron_lisatiedot.Lammitys 'Lämmitys',
                  sarjanumeron_lisatiedot.CD_ja_Radio_soitin,
                  sarjanumeron_lisatiedot.Autopilotti,
                  a16.selitetark_2 Uuni,
                  sarjanumeron_lisatiedot.Televisio,
                  a14.selitetark_2 Tyynysarja,
                  a12.selitetark_2 Tiikkisarja,
                  a10.selitetark_2 Pilssipumppu,
                  sarjanumeron_lisatiedot.Sprinkleri
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
                  WHERE sarjanumeron_lisatiedot.yhtio      = '$kukarow[yhtio]'
                  and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
      $lisatietores = mysql_query($query) or pupe_error($query);
      $lisarow = mysql_fetch_assoc($lisatietores);

      if (is_array($lisarow) and count($lisarow) > 0) {
        foreach ($lisarow as $ind => $val) {
          if ((!is_numeric($val) and $val != '') or (is_numeric($val) and $val != 0) and $val != '0000-00-00') {
            if ($val == "o") {
              $kommlisa2 .= str_replace('_', ' ', $ind).", ";
            }
            else {
              $kommlisa2 .= str_replace('_', ' ', $ind).": ".$val.", ";
            }
          }
        }
        $kommlisa2 = substr($kommlisa2, 0, -2);
      }
    }

    $pdf_myyntisopimus->draw_text(mm_pt(28), mm_pt($kala), $row["nimitys"].$sarjalisa,             $page_myyntisopimus, $kymppi_helvetica);

    if ($row["hinta"] != 0 and $hinnat == "BR" and (stripos($selite, 'Vene') !== FALSE or stripos($selite, 'Vaihtovene') !== FALSE)) {
      $pdf_myyntisopimus->draw_text(mm_pt(172), mm_pt($kala), sprintf('%10s', sprintf('%.2f', $row["bruttorivihinta"])),     $page_myyntisopimus, $kymppi_courier);
    }
    elseif (($row["rivihinta"] != 0 or ($row["hinta"] != 0 and $row["ale1"] != 0)) and $hinnat != "VL") {
      if ($row["ale1"] != 0) {

        if ($yhtiorow["alv_kasittely"] != '' and $row["alv"] < 500) {
          $row["hinta"] = $row["hinta"]*(1+($row["alv"]/100));
        }

        $pdf_myyntisopimus->draw_text(mm_pt(147), mm_pt($kala), sprintf('%10s', sprintf('%.2f', $row["hinta"])),     $page_myyntisopimus, $kasi_courier);
        $pdf_myyntisopimus->draw_text(mm_pt(153), mm_pt($kala), sprintf('%10s', sprintf('%.0f', $row["ale1"]))."%",     $page_myyntisopimus, $kasi_courier);
      }

      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt($kala), sprintf('%10s', sprintf("%.2f", $row["rivihinta"])),    $page_myyntisopimus, $kymppi_courier);
    }

    $lask++;

    if (trim($kommlisa) != '') {
      $kommentit = explode("\n", trim($kommlisa));

      foreach ($kommentit as $akey => $kommentti) {

        $rpit1 = 500;
        $rpit2 = 350;

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_myyntisopimus->strlen($kommentti, $kymppi_helvetica) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {
            if ($pdf_myyntisopimus->strlen($lause, $kymppi_helvetica) > $rpit2) {
              if ($lask > 11) {
                loppu_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, 0);

                $sivu++;
                $lask = 0;
                $kala = 203.5+$rivinkorkeus;

                $page_myyntisopimus = alku_myyntisopimus($laskurow, $asiakasrow, $sivu, $kieli, $pdf_myyntisopimus);
              }

              $kala = $kala - $rivinkorkeus;
              $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala+4), t("Kommentti", $kieli),   $page_myyntisopimus, $seiska);
              $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala) , $lause, $page_myyntisopimus, $kymppi_helvetica);
              $lask++;

              $lause = "";
            }

            $lause .= $sana;
          }

          $kommentti = $lause;
        }

        if (trim($kommentti) != "") {
          if ($lask > 11) {
            loppu_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, 0);

            $sivu++;
            $lask = 0;
            $kala = 203.5+$rivinkorkeus;

            $page_myyntisopimus = alku_myyntisopimus($laskurow, $asiakasrow, $sivu, $kieli, $pdf_myyntisopimus);
          }

          $kala = $kala - $rivinkorkeus;
          $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala+4), t("Kommentti", $kieli),   $page_myyntisopimus, $seiska);
          $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala) , $kommentti,   $page_myyntisopimus, $kymppi_helvetica);
          $lask++;
        }
      }
    }

    if (stripos($selite, 'Vene') !== FALSE and $kommlisa2 != "") {
      $kommentit = explode(",", trim($kommlisa2));

      foreach ($kommentit as $kommentti) {

        if ($lask > 11) {
          loppu_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, 0);

          $sivu++;
          $lask = 0;
          $kala = 203.5+$rivinkorkeus;

          $page_myyntisopimus = alku_myyntisopimus($laskurow, $asiakasrow, $sivu, $kieli, $pdf_myyntisopimus);
        }

        if (trim($kommentti) != '') {
          $kala = $kala - $rivinkorkeus;
          $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala+4), t("Lisävaruste", $kieli),   $page_myyntisopimus, $seiska);
          $pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala) , $kommentti,   $page_myyntisopimus, $kymppi_helvetica);
          $lask++;
        }
      }
    }

    if (stripos($selite, 'Vaihto') !== FALSE) {
      $kala = $kala-$rivinkorkeus;

      $pdf_myyntisopimus->draw_text(mm_pt(28), mm_pt($kala), $row["kommentti"],              $page_myyntisopimus, $kymppi_helvetica);

      $rectp = array();
      $rectp['mode']           = "fill";
      $rectp['fillcolor']['red']     = 1;
      $rectp['fillcolor']['blue']    = 1;
      $rectp['fillcolor']['green']   = 1;

      $pdf_myyntisopimus->draw_rectangle(mm_pt($varakala-1.1), mm_pt(25), mm_pt($varakala-($rivinkorkeus*2)-0.1), mm_pt(172.8),     $page_myyntisopimus, $rectp);
      $pdf_myyntisopimus->draw_paragraph(mm_pt($varakala-1), mm_pt(26), mm_pt($varakala-($rivinkorkeus*2)), mm_pt(162), $kommlisa2,   $page_myyntisopimus, $kymppi_helvetica);
    }

    $kala = $kala - $rivinkorkeus;

    return array ($page_myyntisopimus, $kala, $lask);
  }
}

if (!function_exists('loppu_myyntisopimus')) {
  function loppu_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, $tots, $total_uudet, $total_vanhat) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

    if ($tots == 1) {

      $query = "SELECT *
                  FROM maksupositio
                  WHERE maksupositio.yhtio = '$kukarow[yhtio]'
                  and maksupositio.otunnus = '$laskurow[tunnus]'
                  ORDER BY tunnus";
      $copresult = mysql_query($query) or pupe_error($query);

      $query_ale_lisa = generoi_alekentta('M');

      $query = "SELECT sum(-1 * tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) summa
                  FROM tilausrivi
                  JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
                  JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='ENM')
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.otunnus = '$laskurow[tunnus]'
                  and tilausrivi.tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'
                  ORDER BY tilausrivi.tunnus";
      $copresult2 = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($copresult) > 0 or mysql_num_rows($copresult2) > 0) {
        $lask       = 1;
        $toimitettaessa = 0;
        $ennakot     = 0;
        $ennakko1     = 0;
        $ennakko2     = 0;
        $ennakko3     = 0;

        while ($coprivirow = mysql_fetch_array($copresult)) {
          if ($yhtiorow["alv_kasittely"] != '') {
            $coprivirow["summa"] = round($coprivirow["summa"] * (1+($laskurow["alv"]/100)), 2);
            $coprivirow["summa"] = round(round($coprivirow["summa"] / 0.05) * 0.05, 2);
          }

          if ($lask == 1 and mysql_num_rows($copresult) > 1) {
            $ennakko1 += $coprivirow["summa"];
          }
          elseif ($lask == 2 and mysql_num_rows($copresult) > 2) {
            $ennakko2 += $coprivirow["summa"];
          }

          $lask++;
        }

        $coprivirow2 = mysql_fetch_array($copresult2);

        if ($coprivirow2["summa"] != 0) {
          if ($yhtiorow["alv_kasittely"] != '') {
            $coprivirow2["summa"] = round($coprivirow["summa"] * (1+($laskurow["alv"]/100)), 2);
            $coprivirow2["summa"] = round(round($coprivirow["summa"] / 0.05) * 0.05, 2);
          }

          $ennakko1 += $coprivirow2["summa"];
          $ennakko3 += $coprivirow2["summa"];
        }

        if ($ennakko1 != 0) {
          $pdf_myyntisopimus->draw_text(mm_pt(12), mm_pt(58), $laskurow["valkoodi"], $page_myyntisopimus, $kymppi_helvetica);
          $pdf_myyntisopimus->draw_text(mm_pt(20), mm_pt(58), sprintf('%10s', sprintf("%.2f", $ennakko1)), $page_myyntisopimus, $kymppi_courier);
        }
        if ($ennakko2 != 0) {
          $pdf_myyntisopimus->draw_text(mm_pt(72), mm_pt(58), $laskurow["valkoodi"], $page_myyntisopimus, $kymppi_helvetica);
          $pdf_myyntisopimus->draw_text(mm_pt(80), mm_pt(58), sprintf('%10s', sprintf("%.2f", $ennakko2)), $page_myyntisopimus, $kymppi_courier);
        }

        $ennakot = round($ennakko1+$ennakko2, 2);
      }

      $uudet = $total_uudet;
      $vanha = $total_vanhat;
      $arvo  = $uudet + $vanha;

      //Käsin syötetty summa johon lasku pyöristetään
      if ($laskurow["hinta"] <> 0 and abs($laskurow["hinta"]-$arvo) <= 0.5) {
        $arvo = sprintf("%.2f", $laskurow["hinta"]);
      }

      //Käsin syötetty summa johon lasku pyöristetään
      if ($laskurow["hinta"] <> 0 and abs($laskurow["hinta"]-$ennakot-$toimitettaessa) <= 0.5) {
        $toimitettaessa = sprintf("%.2f", ($laskurow["hinta"]-$ennakot));
      }
      else {
        $toimitettaessa = sprintf("%.2f", ($arvo-$ennakot));
      }

      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(118.5), sprintf('%10s', sprintf("%01.2f", $uudet)),     $page_myyntisopimus, $kymppi_courier);
      $pdf_myyntisopimus->draw_text(mm_pt(165), mm_pt(118.5), $laskurow["valkoodi"],                 $page_myyntisopimus, $kymppi_helvetica);

      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(81), sprintf('%10s', sprintf("%01.2f", abs($vanha))),     $page_myyntisopimus, $kymppi_courier);
      $pdf_myyntisopimus->draw_text(mm_pt(165), mm_pt(81), $laskurow["valkoodi"],                 $page_myyntisopimus, $kymppi_helvetica);

      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(73), sprintf('%10s', sprintf("%.2f", $arvo)),         $page_myyntisopimus, $kymppi_courier);

      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(66), sprintf('%10s', sprintf("%01.2f", $toimitettaessa)),   $page_myyntisopimus, $kymppi_courier);
      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(57.5), sprintf('%10s', sprintf("%01.2f", $toimitettaessa)), $page_myyntisopimus, $kymppi_courier);
    }
    else {
      $pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(119), t("Jatkuu", $kieli)."...", $page_myyntisopimus);
    }
  }
}

if (!function_exists('ehdot_myyntisopimus')) {
  function ehdot_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska;


    $pdf_myyntisopimus_sivu[0] = $pdf_myyntisopimus->new_page("a4");
    $pdf_myyntisopimus->draw_text(mm_pt(110),  mm_pt(280), t("MYYNTIEHDOT", $kieli),  $pdf_myyntisopimus_sivu[0]);

    tulosta_logo_pdf($pdf_myyntisopimus, $pdf_myyntisopimus_sivu[0], "", 0, 0, 30, 200);

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

    $pdf_myyntisopimus->draw_text(mm_pt(120), mm_pt(271), t("Myyjä", $kieli).": ".$yhtiorow["nimi"],            $pdf_myyntisopimus_sivu[0], $kasi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(10),  mm_pt(271), t("Ostaja", $kieli).": ".$laskurow["nimi"],            $pdf_myyntisopimus_sivu[0], $kasi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(120), mm_pt(264.5), t("Myyntisopimuksen numero", $kieli).": ".$laskurow["tunnus"],  $pdf_myyntisopimus_sivu[0], $kasi_helvetica);

    if (strpos($lisrow["sarjanumero"], t("PUUTTUU", $kieli)) === FALSE and $lisrow["sarjanumero"] != "") {
      $sarjalisa = $lisrow["sarjanumero"];
    }
    else {
      $sarjalisa = "";
    }

    $pdf_myyntisopimus->draw_text(mm_pt(10), mm_pt(264.5), t("Kaupan kohde", $kieli).": ".$lisrow["nimitys"],        $pdf_myyntisopimus_sivu[0], $kasi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(10), mm_pt(258), t("S:nro", $kieli).": ".$sarjalisa,                $pdf_myyntisopimus_sivu[0], $kasi_helvetica);
    $pdf_myyntisopimus->draw_text(mm_pt(120),  mm_pt(258), t("Päivämäärä", $kieli).": ".tv1dateconv(substr($laskurow["luontiaika"], 0, 10))." ".substr($laskurow["luontiaika"], 10), $pdf_myyntisopimus_sivu[0], $kasi_helvetica);

    $pdf_myyntisopimus->draw_text(mm_pt(10), mm_pt(251.5), t("Viesti", $kieli).": ".$laskurow["viesti"],          $pdf_myyntisopimus_sivu[0], $kasi_helvetica);

    $pdf_myyntisopimus->draw_paragraph(mm_pt(250), mm_pt(10), mm_pt(220), mm_pt(190), $laskurow["comments"],         $pdf_myyntisopimus_sivu[0], $kasi_helvetica);

    if (strtoupper($kieli) == "SE") {
      $pdf_myyntisopimus->draw_paragraph(mm_pt(230), mm_pt(10), mm_pt(10), mm_pt(190), "

        1. Äganderätten till varorna i detta avtal tillhör säljaren tills köpeskillingen har betalts till fullo
        och andra betalningsskyldigheter för varorna som ankommer på köparen har fullgjorts i sin
        helhet. Säljaren får kräva att varan återlämnas ifall köpeskillingen inte erläggs i enlighet med
        avtalet.
        \r\n
        2. Köparen skall kontrollera varan vid mottagandet.
        \r\n
        3. Om parterna har kommit överens om kontant betalning kvitteras köpeskillingen eller en del
        därav inte betald genom undertecknande av detta avtal utan skilt kvitto över erlagd betalning
        skall uppsättas.
        \r\n
        4. Gäller detta avtal försäljning av en ny serietillverkad båt som försetts med skrovnummer,
        tillämpas vid köpet Båtbranschens Centralförbund Finnboat rf:s garantivillkor eller andra
        särskilt överenskomna garantivillkor.
        \r\n
        5. En begagnad vara säljs i det skick som den har vid överlåtelsepunkten.
        \r\n
        6. Om ett fel som avses i 5. kap. Konsumentskyddslagen yppar sig i varan, har köparen rätt att
        kräva att säljaren avhjälper felet. Är detta inte möjligt, har köparen rätt att kräva ett prisavdrag
        som motsvarar felet. Är säljarens avtalsbrott väsentligt, får köparen häva köpet och kräva
        skadestånd. Säljaren har rätt att avhjälpa felet, om han erbjuder sig att göra den på sin egen
        bekostnad. Köparen skall underrätta säljaren om felet inom en skälig tid, i normala fall två
        veckor efter att han har upptäckt felet eller borde ha upptäckt det. En garanti eller förbindelse
        av säljaren begränsar inte köparens lagstadgande rätt att åberopa fel i köpeobjektet.
        \r\n
        7. Om köparen utan skäl som grundar sig på lag häver kontraktet eller annulerar beställningen
        innan varans överlåtelse har säljaren rätt till skadestånd för den skada som åsamkats honom
        enligt gällande konsumentlagstiftning. Om köpet hävs på grund av orsaker som inte beror på
        säljarens agerande har säljaren enligt detta avtal rätt till ersättning som är minst 10 %
        av köpesumman. Om säljaren häver kontraktet har köparen rätt till skadestånd för den skada
        som åsamkats honom enligt gällande konsumentlagstiftning. I konsumentköp kan skadeståndet jämkas
        i fall hävningen av kontraktet beror på att köparen råkat i betalningssvårigheter genom sjukdom,
        arbetslöshet eller annan särskild orsak, huvudsakligen utan eget förvållande. I konsumentköp har
        köparen likaså motsvarande rätt till skadestånd om säljaren utan skäl som grundar sig på lag häver
        avtalet innan varan har överlåtits eller inte överlåter varan inom fjorton (14) dygn från den
        överenskomna överlåtelsetidpunkten.
        \r\n
        8. Inom konsumentköp stiger eller sjunker köpeskillingen av en ny vara från det i kontraktet
        antecknade priset ifall en finsk eller en utländsk myndighet efter beställningen före den
        avtalsenliga överlåtelsetiden fattar beslut om skatter, tullar och övriga skatteartade avgifter som
        påverkar priset på den beställda varan eller ifall valutakurserna fluktuerar och importören av
        varan av dessa skäl förändrar priset på dylika varor som är föremål för köpet. Försäljaren är
        skyldig före överlåtelsen av varan att skriftligt meddela köparen om förhöjningen av
        köpeskillingen. Ifall förhöjningen av köpeskillingen är minst fyra (4) procent, har köparen rätt
        att häva avtalet genom att meddela därom försäljaren inom en (1) vecka från det köparen fått
        kännedom om säljarens meddelande om förhöjningen.
        \r\n
        9. Köparen är berättigad att hänskjuta meningsskiljaktigheter beträffande detta avtal till
        konsumentklagonämndens behandling. Om meningsskiljaktigheterna anhängiggörs vid domstol,
        avgörs målet i underrätten på köparens hemort.", $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    }
    else {

      $pdf_myyntisopimus->draw_paragraph(mm_pt(230), mm_pt(10), mm_pt(10), mm_pt(190), "

        1. Omistusoikeus tässä sopimuksessa mainittuihin tavaroihin kuuluu myyjälle kunnes kauppahinta on
        kokonaisuudessaan maksettu ja muut tämän sopimuksen tarkoittamat tavaroita koskevat ostajan
        maksuvelvoitteet on täysin täytetty. Myyjä saa vaatia tavaran takaisin, mikäli kauppahintaa ei
        suoriteta sopimuksen mukaisesti.
        \r\n
        2. Ostajaa kehotetaan tarkastamaan ostamansa tavara heti sitä vastaanotettaessa.
        \r\n
        3. Käteisellä maksettavaksi sovittua kauppahintaa tai sen osaa ei kuitata maksetuksi tämän
        sopimuksen allekirjoituksin, vaan maksun suorittamisesta on laadittava erillinen kuitti.
        \r\n
        4. Mikäli tämä sopimus koskee uuden runkonumerolla varustetun sarjavalmisteisen veneen myyntiä
        sovelletaan kauppaan Venealan Keskusliitto Finnboat ry:n laatimia takuuehtoja tai erikseen sovittuja
        muita takuuehtoja.
        \r\n
        5. Käytetty tavara myydään siinä kunnossa kuin se on luovutushetkellä.
        \r\n
        6. Jos luovutetussa tavarassa ilmenee kuluttajansuojalain 5 luvussa tarkoitettu virhe, ostajalla on oikeus
        vaatia, että myyjä korjaa virheen. Jollei tämä ole mahdollista, ostaja voi vaatia virhettä vastaavaa
        hinnanalennusta. Jos myyjän sopimusrikkomus on olennainen, ostaja saa purkaa kaupan ja vaatia
        vahingonkorvausta. Myyjällä on oikeus korjata virhe, jos hän tarjoutuu tekemään sen omalla
        kustannuksellaan. Ostajan pitää ilmoittaa virheestä myyjälle kohtuullisen ajan, normaalisti kahden
        viikon kuluessa siitä, kun hän oli havainnut virheen tai hänen olisi pitänyt havaita se. Takuu tai
        myyjän antama sitoumus ei rajoita ostajan lakiin perustuvaa oikeutta vedota kaupan kohteessa
        olevaan virheeseen.
        \r\n
        7. Jos ostaja ilman lakiin perustuvaa syytä purkaa sopimuksen tai peruuttaa tavaran tilauksen ennen
        tavaran luovutusta, myyjällä on oikeus korvaukseen hänelle aiheutuvasta vahingosta voimassa olevan
        kuluttajansuojalain mukaisesti. Tämän sopimuksen mukaan myyjällä on kuitenkin oikeus vähintään 10%
        korvaukseen sovitusta kauppahinnasta kun kauppa purkautuu myyjästä riippumattomasta syystä.
        Jos myyjä purkaa sopimuksen on ostajalla oikeus korvaukseen hänelle aiheutuvasta vahingosta
        voimassa olevan kuluttajansuojalain mukaisesti. Kuluttajakaupan vahingon-korvausta voidaan sovitella,
        jos sopimuksen purkaminen johtuu maksuvaikeuksista, joihin ostaja on joutunut sairauden,
        työttömyyden tai muun erityisen seikan vuoksi pääasiassa omaa syyttään.
        Vastaava oikeus vahingonkorvaukseen on kuluttajakaupassa myös ostajalla, jos myyjä ennen tavaran
        luovutusta purkaa sopimuksen ilman lakiin perustuvaa syytä tai jos myyjä ei neljäntoista (14)
        vuorokauden kuluessa sovitusta luovutusajankohdasta ole luovuttanut tavaraa.
        \r\n
        8. Kuluttajakaupoissa uuden tavaran kauppahinta nousee tai laskee sopimukseen merkitystä hinnasta,
        jos suomalainen tai ulkomainen viranomainen tekee tilauksen jälkeen ennen sopimuksenmukaista
        luovutusaikaa tilatun tavaran hintaan vaikuttavia veroja, tulleja ja muita veroluonteisia maksuja
        koskevan päätöksen tai jos valuuttakursseissa tapahtuu muutoksia, ja tavaran maahantuoja näistä
        syistä muuttaa kaupan kohdetta vastaavien tavaroiden hintaa. Myyjän on ennen tavaran luovutusta
        ilmoitettava ostajalle kirjallisesti kauppahinnan korotuksesta. Jos kauppahinnan korotus on vähintään
        neljä (4) prosenttia, ostaja saa purkaa sopimuksen ilmoittamalla siitä myyjälle yhden (1) viikon
        kuluessa saatuaan tietää myyjän korotusilmoituksesta.
        \r\n
        9. Ostaja on oikeutettu saattamaan tätä sopimusta koskevat erimielisyydet kuluttajavalituslautakunnan
        käsiteltäviksi. Mikäli erimielisyydet saatetaan tuomioistuimen ratkaistaviksi, ne ratkaistaan ostajan
        kotipaikan alioikeudessa.", $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
    }
  }
}

if (!function_exists('print_pdf_myyntisopimus')) {
  function print_pdf_myyntisopimus($pdf_myyntisopimus, $laskurow, $komento, $tee = "") {
    global $yhtiorow, $kukarow;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdf_myyntisopimusfilenimi = "/tmp/Myyntisopimus-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdf_myyntisopimusfilenimi, "w");
    if (fwrite($fh, $pdf_myyntisopimus->generate()) === FALSE) die("PDF create error $pdf_myyntisopimusfilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'RUUDULLE') {
      return $pdf_myyntisopimusfilenimi;
    }
    elseif ($komento == 'email') {
      $liite = $pdf_myyntisopimusfilenimi;

      $kutsu = t("Myyntisopimus", $kieli);

      if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
        $kutsu .= ", ".trim($laskurow["nimi"]);
      }

      echo t("Myyntisopimus tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "../inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdf_myyntisopimusfilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Myyntisopimus tulostuu")."...<br>";
      $line = exec("$komento $pdf_myyntisopimusfilenimi", $output, $returnvalue);
    }

    //poistetaan tmp file samantien kuleksimasta...
    system("rm -f $pdf_myyntisopimusfilenimi");
  }
}

if (!function_exists('tulosta_myyntisopimus')) {
  function tulosta_myyntisopimus($otunnus, $komento, $kieli = "", $tee = "", $hinnat = "") {
    global $yhtiorow, $kukarow;

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

    //korjataan yhtiön tietoja
    $query  = "SELECT *
                 from kuka
                 where tunnus = '$laskurow[myyja]'
                 and yhtio    = '$kukarow[yhtio]'";
    $myyresult = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($myyresult) == 1) {
      $myyrow = mysql_fetch_array($myyresult);

      $kukarow["toimipaikka"] = $myyrow["toimipaikka"];
    }

    $query = "SELECT *
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

    function printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus, $sarjanutunnus, $osasto, $selite, $total, $hinnat) {
      global $yhtiorow, $kukarow;

      //Tehään tästä taas array tän funktion siäiseen käyttöön jotta saamme sivunvaihdot toimimaan
      $page_myyntisopimus2[$sivu] = $page_myyntisopimus;

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
        $hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
      }
      else {
        $hinta_riv = "tilausrivi.hinta";
      }

      $sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

      $query_ale_lisa = generoi_alekentta('M');

      $query = "SELECT tilausrivi.*,
                  round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},2) rivihinta,
                  round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl),2) bruttorivihinta,
                  tilausrivin_lisatiedot.positio,
                  $sorttauskentta
                  FROM tilausrivi
                  JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                  LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                  WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
                  and tilausrivi.yhtio     = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi    in  ('L','T','U')
                  $osasto
                  ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
      $riresult = mysql_query($query) or pupe_error($query);

      while ($row = mysql_fetch_array($riresult)) {

        //jos on paljon rivejä tehdään uusi otsikko...
        if ($lask > 11) {
          loppu_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus2[$sivu], 0);

          $sivu++;
          $lask = 0;
          $kala = 203.5;

          $page_myyntisopimus2[$sivu] = alku_myyntisopimus($laskurow, $asiakasrow, $sivu, $kieli, $pdf_myyntisopimus);
        }

        list($page_myyntisopimus2[$sivu], $kala, $lask) = rivi_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $kieli, $page_myyntisopimus2[$sivu], $sarjanutunnus, $selite, $row, $hinnat, $lask);

        if ($row["hinta"] != 0 and $hinnat == "BR" and (stripos($selite, 'Vene') !== FALSE or stripos($selite, 'Vaihtovene') !== FALSE)) {
          $total += $row["bruttorivihinta"];
        }
        else {
          $total += $row["rivihinta"];
        }
      }
      return array ($page_myyntisopimus2[$sivu], $lask, $kala, $total);
    }

    //Oletuksia
    $sivu       = 1;
    $kala       = 203.5;
    $lask       = 0;

    $pdf_myyntisopimus = new pdffile;

    $pdf_myyntisopimus->set_default('margin-top', 20);
    $pdf_myyntisopimus->set_default('margin-bottom', 0);
    $pdf_myyntisopimus->set_default('margin-left', 0);
    $pdf_myyntisopimus->set_default('margin-right', 0);

    $pdf_myyntisopimus->enable('import');

    //Myyntisopimuksen pohja
    $page_myyntisopimus[$sivu] = alku_myyntisopimus($laskurow, $asiakasrow, $sivu, $kieli, $pdf_myyntisopimus);

    // Uusi vene
    list ($page_myyntisopimus[$sivu], $lask, $kala, $total) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus[$sivu], "myyntirivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Vene'", "Veneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);
    // Uusi moottori
    list ($page_myyntisopimus[$sivu], $lask, $kala, $total) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus[$sivu], "myyntirivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Moottori'", "Moottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);
    // Uudet tarvikkeet
    list ($page_myyntisopimus[$sivu], $lask, $kala, $total) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus[$sivu], "myyntirivitunnus",   "HAVING (tilausrivin_lisatiedot.positio not like '%vene%' and tilausrivin_lisatiedot.positio not like '%moottori%') and tilausrivi.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'", "", $total, $hinnat);

    // Siirrytään vaihtolaitteiden kimppuun
    $total_uudet = $total;
    $kala = 109.5;
    $lask--;

    list ($page_myyntisopimus[$sivu], $lask, $kala, $total) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus[$sivu], "ostorivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Vaihtovene'", "Vaihtoveneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);
    list ($page_myyntisopimus[$sivu], $lask, $kala, $total) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus[$sivu], "ostorivitunnus",   "HAVING tilausrivin_lisatiedot.positio='Vaihtomoottori'", "Vaihtomoottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);

    // Lasketaan vähän hintoja yhteen
    $total_vanhat = $total - $total_uudet;

    // Lopputekstit
    loppu_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus[$sivu], 1, $total_uudet, $total_vanhat);

    // Sopimusehdot
    ehdot_myyntisopimus($pdf_myyntisopimus, $laskurow, $kieli);

    // Tulostetaan sivu
    print_pdf_myyntisopimus($pdf_myyntisopimus, $laskurow, $komento, $tee);

    $tee = '';
  }
}
