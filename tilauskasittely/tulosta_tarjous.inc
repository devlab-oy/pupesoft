<?php

//Fontit
$seiska["height"]    = 7;
$seiska["font"]    = "Times-Roman";

$kymppi["height"]    = 10;
$kymppi["font"]    = "Times-Roman";

$kymppi_courier["height"]  = 10;
$kymppi_courier["font"]  = "Courier";

$kasi_courier["height"]  = 8;
$kasi_courier["font"]   = "Courier";

$kymppi_helvetica["height"] = 10;
$kymppi_helvetica["font"]  = "Helvetica";

$kasi_helvetica["height"]  = 8;
$kasi_helvetica["font"]  = "Helvetica";

$rectparam["width"]   = 0.2;

//Haetaan pdflibrary
require_once 'pdflib/phppdflib.class.php';

//muutamat funktiot...
if (!function_exists('alku_tarjous')) {
  function alku_tarjous($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous) {
    global $yhtiorow, $kukarow, $pupe_root_polku, $kasi_helvetica, $kymppi_helvetica, $seiska, $rectparam, $kymppi_courier, $rectparam;

    // Haetaan pohja
    ob_start();

    $filename = "{$pupe_root_polku}/ajoneuvomyynti/tarjous.pdf";

    $handle = fopen($filename, "r");
    $d = fread($handle, filesize($filename));
    fclose($handle);

    $pdf_tarjous->import->append($d);

    $pdf_tarjous_sivu = $pdf_tarjous->import->get_pages();

    ob_end_clean();

    tulosta_logo_pdf($pdf_tarjous, $pdf_tarjous_sivu[0], "", 0, 0, 30, 200);

    if ($laskurow["muutospvm"] != "0000-00-00 00:00:00") {
      $pp = substr($laskurow["muutospvm"], 8, 2);
      $kk = substr($laskurow["muutospvm"], 5, 2);
      $vv = substr($laskurow["muutospvm"], 0, 4);
    }
    else {
      $pp = date("d");
      $kk = date("m");
      $vv = date("Y");
    }

    if ($laskurow["olmapvm"] != "0000-00-00") {
      $voimassa = tv1dateconv($laskurow["olmapvm"]);
    }
    else {
      $voimassa = date("d.m.Y", mktime(0, 0, 0, $kk, $pp+14, $vv));
    }

    $luotoaik = date("d.m.Y", mktime(0, 0, 0, $kk, $pp, $vv));

    $pdf_tarjous->draw_text(mm_pt(110),  mm_pt(280), t("TARJOUS", $kieli),         $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(160),  mm_pt(284), t("Numero", $kieli),         $pdf_tarjous_sivu[0], $kasi_helvetica);

    //top left bottom right
    $pdf_tarjous->draw_rectangle(mm_pt(287), mm_pt(158),  mm_pt(278), mm_pt(197),       $pdf_tarjous_sivu[0], $rectparam);

    $pdf_tarjous->draw_text(mm_pt(170),  mm_pt(280), $laskurow["tunnus"],         $pdf_tarjous_sivu[0]);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(275), t("Myyjä", $kieli).":",         $pdf_tarjous_sivu[0], $kasi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(270), $yhtiorow["nimi"],           $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(265), $yhtiorow["osoite"],          $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(260), $yhtiorow["postino"]." ".$yhtiorow["postitp"],   $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(255), t("Puh", $kieli).": ".$yhtiorow["puhelin"],    $pdf_tarjous_sivu[0]);

    $pdf_tarjous->draw_text(mm_pt(75),  mm_pt(275), t("Ostaja", $kieli).":",         $pdf_tarjous_sivu[0], $kasi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(75),  mm_pt(270), $laskurow["nimi"],           $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(75),  mm_pt(265), $laskurow["osoite"],          $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(75),  mm_pt(260), $laskurow["postino"]." ".$laskurow["postitp"],   $pdf_tarjous_sivu[0]);
    $pdf_tarjous->draw_text(mm_pt(75),  mm_pt(255), t("Puh", $kieli).": ".$asiakasrow["puhelin"],   $pdf_tarjous_sivu[0]);

    $pdf_tarjous->draw_text(mm_pt(160),  mm_pt(275), t("Pvm", $kieli).":",         $pdf_tarjous_sivu[0], $kasi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(160),  mm_pt(270), $luotoaik,            $pdf_tarjous_sivu[0]);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(245), t("TARJOUKSEN KOHDE", $kieli),       $pdf_tarjous_sivu[0], $kymppi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(138), t("VAIHTOKOHDE", $kieli),        $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(25),  mm_pt(93),  t("Tarjoamme teille ylläolevan tarjouksen kohteen:", $kieli),  $pdf_tarjous_sivu[0], $kymppi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(126),  mm_pt(93), t("Hintaan / Välirahaan", $kieli),         $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(22),  mm_pt(154), t("Tarjouskohteen toimitusehto", $kieli),    $pdf_tarjous_sivu[0], $seiska);
    $pdf_tarjous->draw_text(mm_pt(25),  mm_pt(150.5), $laskurow["toimitusehto"],       $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(126), mm_pt(151), t("Hinta yhteensä", $kieli),       $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(126), mm_pt(101), t("Hyvityshinta yhteensä", $kieli),      $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(22),  mm_pt(104), t("Vaihtokohteen toimitusehto", $kieli),    $pdf_tarjous_sivu[0], $seiska);
    $pdf_tarjous->draw_text(mm_pt(25),  mm_pt(100.5), $laskurow["toimitusehto2"],       $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(75), t("LISÄTIEDOT", $kieli),         $pdf_tarjous_sivu[0], $kymppi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(70), t("Viesti", $kieli).":",         $pdf_tarjous_sivu[0], $seiska);
    $pdf_tarjous->draw_paragraph(mm_pt(70),  mm_pt(10), mm_pt(60), mm_pt(70), $laskurow["viesti"],    $pdf_tarjous_sivu[0], $kasi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(60), t("Maksuehto", $kieli).":",        $pdf_tarjous_sivu[0], $seiska);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(56), $laskurow["maksuehto"],         $pdf_tarjous_sivu[0], $kasi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(50), t("Arvonlisävero", $kieli).":",       $pdf_tarjous_sivu[0], $seiska);
    $pdf_tarjous->draw_paragraph(mm_pt(50),  mm_pt(10), mm_pt(40), mm_pt(80), t("Kun tarjouksen kohde on uusi, sen hinta sisältää arvonlisäveron", $kieli)." ".((float) $laskurow["alv"])."%", $pdf_tarjous_sivu[0], $kasi_helvetica);

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(40), t("Voimassaolo", $kieli).":",       $pdf_tarjous_sivu[0], $seiska);
    $pdf_tarjous->draw_paragraph(mm_pt(40),  mm_pt(10), mm_pt(30), mm_pt(80), t("Tarjous on voimassa välimyyntivarauksin", $kieli)." ".$voimassa." ". t("saakka", $kieli).".", $pdf_tarjous_sivu[0], $kasi_helvetica);


    $query  = "	select *
						from kuka
						where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
    $myyresult = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($myyresult) == 1) {
      $myyrow = mysql_fetch_array($myyresult);
    }
    else {
      $myyrow = $kukarow;
    }

    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(25), t("TERVEISIN", $kieli),         $pdf_tarjous_sivu[0], $kymppi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(20), $myyrow["nimi"],           $pdf_tarjous_sivu[0], $kymppi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(16), $myyrow["puhno"],          $pdf_tarjous_sivu[0], $kymppi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(12), $myyrow["eposti"],          $pdf_tarjous_sivu[0], $kymppi_helvetica);
    $pdf_tarjous->draw_text(mm_pt(10),  mm_pt(8), $yhtiorow["nimi"],          $pdf_tarjous_sivu[0], $kymppi_helvetica);


    $tee = 'osamaksusoppari';
    $tulosta_osamakusoppari = "TRUE";
    $tilausnumero = $laskurow["tunnus"];
    $maksuerienlkm = 0;

    require "ajoneuvomyynti/osamaksusoppari.inc";

    if ($maksuerienlkm > 0) {
      $pdf_tarjous->draw_text(mm_pt(89), mm_pt(75), t("OSAMAKSUERIEN MAKSUSUUNNITELMA", $kieli),      $pdf_tarjous_sivu[0], $kymppi_helvetica);

      $pdf_tarjous->draw_paragraph(mm_pt(40), mm_pt(90), mm_pt(30), mm_pt(190),  t("Lyhennys on annuiteettilyhennys. Maksuerä sisältää myös koron. Sopimusajan korko on kiinteä. Kauppahinnan maksamattomalle, rahoitettavalle osalle maksetaan vuotuista korkoa", $kieli), $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_paragraph(mm_pt(30), mm_pt(90), mm_pt(20), mm_pt(190),  t("Korko on vaihtuva, jolloin koron määrästä, muutosperusteista, muutosten voimaantulosta ja muista ehdoista laaditaan erillinen sopimus", $kieli), $pdf_tarjous_sivu[0], $seiska);

      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(70),  t("Osamaksuerien lkm", $kieli),         $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(120), mm_pt(70),  t("Luottoaika kk", $kieli),          $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(70),  t("Maksuväli kk", $kieli),          $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(170), mm_pt(70),  t("Erän nro 1 maksupäivä", $kieli),        $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(63),  t("Annuiteettierä", $kieli),         $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(120), mm_pt(63),  t("Viim erä euro", $kieli),          $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(63),  t("Käsittelymaksu per erä", $kieli),       $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(170), mm_pt(63),  t("Viitekorko", $kieli),          $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(182), mm_pt(63),  t("Sopimusajan korko", $kieli),         $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(55),  t("Viim.erän maksupäivä", $kieli),        $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(55),  t("18. Luottokustannus yht (12+13+15+16+17)", $kieli),   $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(48),  t("19. KSL 7.2 §:n mukainen luottohinta (4+18)", $kieli),  $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(48),  t("20. KSL 7:11 §:n mukainen todellinen vuosikorko", $kieli), $pdf_tarjous_sivu[0], $seiska);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(41),  t("Erien viivästyessä ostaja maksaa eräpäivän jälkeiseltä osalta korkolain mukaista viivästyskorkoa.", $kieli), $pdf_tarjous_sivu[0], $seiska);

      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(65.5), $maksuerienlkm,            $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(120), mm_pt(65.5), $luottoaikakk,             $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(65.5), $maksuvali_kk,             $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(170), mm_pt(65.5), $ekaerpcm,              $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(58),   sprintf('%.2f', $A),           $pdf_tarjous_sivu[0], $kymppi_helvetica);

      if ($poikkeava_era != 0) {
        $pdf_tarjous->draw_text(mm_pt(120), mm_pt(58),   sprintf('%.2f', $poikkeava_era),       $pdf_tarjous_sivu[0], $kymppi_helvetica);
      }
      else {
        $pdf_tarjous->draw_text(mm_pt(120), mm_pt(58),   sprintf('%.2f', $A),          $pdf_tarjous_sivu[0], $kymppi_helvetica);
      }

      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(58),   sprintf('%.2f', $erankasittelymaksu),       $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(170), mm_pt(58),   $viitearray[$viitekorko],          $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(182), mm_pt(58),   $sopajankorko." %",           $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(51.5), $vikaerpcm,             $pdf_tarjous_sivu[0], $kymppi_helvetica);

      if ($lyhennystapa == 1) {
        $luottokustannus_yhteensa  = $luottokustannus_yhteensa_tl;
        $ksl_luottohinta    = $ksl_luottohinta_tl;
      }
      else {
        $luottokustannus_yhteensa  = $luottokustannus_yhteensa_te;
        $ksl_luottohinta    = $ksl_luottohinta_te;
      }

      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(51.5), sprintf('%.2f', $luottokustannus_yhteensa),     $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(90),  mm_pt(44),   sprintf('%.2f', $ksl_luottohinta),        $pdf_tarjous_sivu[0], $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(142), mm_pt(44),   $ksl_vuosikorko."%",           $pdf_tarjous_sivu[0], $kymppi_helvetica);
    }

    //Palautetan luotu sivu
    return $pdf_tarjous_sivu[0];
  }
}

if (!function_exists('rivi_tarjous')) {
  function rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier, $kasi_courier;

    $rivinkorkeus = 7.1;

    if ($sarjanutunnus != "") {
      $query = "	SELECT *
							from sarjanumeroseuranta
							where yhtio='$kukarow[yhtio]'
							and tuoteno='$row[tuoteno]'
							and $sarjanutunnus='$row[tunnus]'";
      $sarjares = mysql_query($query) or pupe_error($query);
      $sarjarow = mysql_fetch_array($sarjares);

      if (strpos($sarjarow["sarjanumero"], t("PUUTTUU", $kieli)) === FALSE and $sarjarow["sarjanumero"] != "") {
        $sarjalisa = " (".$sarjarow["sarjanumero"].")";
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

    $pdf_tarjous->draw_text(mm_pt(22), mm_pt($kala+4), $selite, $page_tarjous, $seiska);

    if (strpos($selite, 'Vaihtoveneen') === FALSE) {
      $kommlisa = " ".$row["kommentti"];
    }
    else {
      $kommlisa = "";
    }

    $kommlisa2 = "";

    if ($row["tuoteno"] == 'VAVE') {
      $query = "	SELECT
							#sarjanumeron_lisatiedot.Polttoainetankki,
							#sarjanumeron_lisatiedot.Kayttovesisailio 'Käyttövesisäiliö',
							#sarjanumeron_lisatiedot.Septitankki,
							#a9.selitetark_2 Moottorin_ohjausjärjestelmä,
							concat_ws(' ',a8.selitetark_2,sarjanumeron_lisatiedot.Koneen_malli,sarjanumeron_lisatiedot.Teho_hv) Koneistus,
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
							WHERE sarjanumeron_lisatiedot.yhtio		 = '$kukarow[yhtio]'
							and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
      $lisatietores = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($lisatietores) > 0) {
        $lisarow = mysql_fetch_assoc($lisatietores);

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

    $pdf_tarjous->draw_text(mm_pt(24), mm_pt($kala), $row["nimitys"].$sarjalisa,  $page_tarjous, $kymppi_helvetica);

    if ($row["hinta"] != 0 and $hinnat == "BR" and (strpos($selite, 'Vene') !== FALSE or strpos($selite, 'Vaihtovene') !== FALSE)) {
      $pdf_tarjous->draw_text(mm_pt(172), mm_pt($kala), sprintf('%10s', sprintf('%.2f', $row["bruttorivihinta"])),   $page_tarjous, $kymppi_courier);
    }
    elseif (($row["rivihinta"] != 0 or ($row["hinta"] != 0 and $row["ale1"] != 0)) and $hinnat != "VL") {
      if ($row["ale1"] != 0) {

        if ($yhtiorow["alv_kasittely"] != '' and $row["alv"] < 500) {
          $row["hinta"] = $row["hinta"]*(1+($row["alv"]/100));
        }

        $pdf_tarjous->draw_text(mm_pt(144), mm_pt($kala), sprintf('%10s', sprintf('%.2f', $row["hinta"])),   $page_tarjous, $kasi_courier);
        $pdf_tarjous->draw_text(mm_pt(150), mm_pt($kala), sprintf('%10s', sprintf('%.0f', $row["ale1"]))."%",  $page_tarjous, $kasi_courier);
      }
      $pdf_tarjous->draw_text(mm_pt(172), mm_pt($kala), sprintf('%10s', sprintf('%.2f', $row["rivihinta"])),   $page_tarjous, $kymppi_courier);
    }

    if (trim($kommlisa) != '') {
      $kommentit = explode("\n", trim($kommlisa));

      foreach ($kommentit as $akey => $kommentti) {

        $rpit1 = 500;
        $rpit2 = 350;

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_tarjous->strlen($kommentti, $kymppi_helvetica) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {
            if ($pdf_tarjous->strlen($lause, $kymppi_helvetica) > $rpit2) {

              if ($lask > 10) {
                loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, 0);

                $sivu++;
                $lask = 0;
                $kala = 236+$rivinkorkeus;

                $page_tarjous = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
              }

              $kala = $kala - $rivinkorkeus;
              $pdf_tarjous->draw_text(mm_pt(22), mm_pt($kala+4), t("Kommentti", $kieli),  $page_tarjous, $seiska);
              $pdf_tarjous->draw_text(mm_pt(26), mm_pt($kala) , $lause,  $page_tarjous, $kymppi_helvetica);
              $lask++;

              $lause = "";
            }

            $lause .= $sana;
          }

          $kommentti = $lause;
        }

        if (trim($kommentti) != "") {
          if ($lask > 10) {
            loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, 0);

            $sivu++;
            $lask = 0;
            $kala = 236+$rivinkorkeus;

            $page_tarjous = alku_tarjous($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
          }

          $kala = $kala - $rivinkorkeus;
          $pdf_tarjous->draw_text(mm_pt(22), mm_pt($kala+4), t("Kommentti", $kieli),  $page_tarjous, $seiska);
          $pdf_tarjous->draw_text(mm_pt(26), mm_pt($kala) , $kommentti,  $page_tarjous, $kymppi_helvetica);
          $lask++;
        }
      }
    }

    if (strpos($selite, 'Vene') !== FALSE and $kommlisa2 != "") {
      $kommentit = explode(",", trim($kommlisa2));

      foreach ($kommentit as $kommentti) {

        if ($lask > 10) {
          loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, 0);

          $sivu++;
          $lask = 0;
          $kala = 236+$rivinkorkeus;

          $page_tarjous = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
        }

        if (trim($kommentti) != '') {
          $kala = $kala - $rivinkorkeus;
          $pdf_tarjous->draw_text(mm_pt(22), mm_pt($kala+4), t("Lisävaruste"),  $page_tarjous, $seiska);
          $pdf_tarjous->draw_text(mm_pt(24), mm_pt($kala) , $kommentti,  $page_tarjous, $kymppi_helvetica);
          $lask++;
        }
      }
    }

    if (strpos($selite, 'Vaihtoveneen') !== FALSE) {

      $kommlisa2 = $row["kommentti"]. " ".$kommlisa2;

      $rectp        = array();
      $rectp['mode']      = "fill";
      $rectp['fillcolor']['red']   = 1;
      $rectp['fillcolor']['blue']  = 1;
      $rectp['fillcolor']['green']  = 1;

      $pdf_tarjous->draw_rectangle(mm_pt($kala-1.1), mm_pt(21.7), mm_pt($kala-($rivinkorkeus*3)-0.9), mm_pt(169.7),   $page_tarjous, $rectp);
      $pdf_tarjous->draw_paragraph(mm_pt($kala-1), mm_pt(24), mm_pt($kala-($rivinkorkeus*3)), mm_pt(170), $kommlisa2,  $page_tarjous, $kymppi_helvetica);
    }

    $kala = $kala - $rivinkorkeus;

    return array ($page_tarjous, $kala, $lask);
  }
}

if (!function_exists('loppu_tarjous')) {
  function loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $tots, $total_uudet=0, $total_vanhat=0) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier, $rectparam;

    if ($tots == 1) {

      $query = "	SELECT *
							FROM maksupositio
							WHERE maksupositio.yhtio = '$kukarow[yhtio]'
							and maksupositio.otunnus = '$laskurow[tunnus]'
							ORDER BY tunnus";
      $copresult = mysql_query($query) or pupe_error($query);

      $query_ale_lisa = generoi_alekentta('M');

      $query = "	SELECT sum(-1 * tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) summa
							FROM tilausrivi
							JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
							JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='ENM'
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'
							ORDER BY tilausrivi.tunnus";
      $copresult2 = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($copresult) > 0 or mysql_num_rows($copresult2) > 0) {
        $lask    = 1;
        $toimitettaessa = 0;
        $ennakot   = 0;
        $ennakko1   = 0;
        $ennakko2   = 0;
        $ennakko3   = 0;

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
          $pdf_tarjous->draw_text(mm_pt(23), mm_pt(84), $laskurow["valkoodi"],        $page_tarjous, $kymppi_helvetica);
          $pdf_tarjous->draw_text(mm_pt(30), mm_pt(84), sprintf('%10s', sprintf("%.2f", $ennakko1)),   $page_tarjous, $kymppi_courier);
        }
        if ($ennakko2 != 0) {
          $pdf_tarjous->draw_text(mm_pt(91), mm_pt(84), $laskurow["valkoodi"],        $page_tarjous, $kymppi_helvetica);
          $pdf_tarjous->draw_text(mm_pt(99), mm_pt(84), sprintf('%10s', sprintf("%.2f", $ennakko2)),   $page_tarjous, $kymppi_courier);
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

      $pdf_tarjous->draw_text(mm_pt(172), mm_pt(93), sprintf('%10s', sprintf("%01.2f", $uudet-abs($vanha))),  $page_tarjous, $kymppi_courier);
      $pdf_tarjous->draw_text(mm_pt(162), mm_pt(93), $laskurow["valkoodi"],          $page_tarjous, $kymppi_helvetica);


      $pdf_tarjous->draw_text(mm_pt(22),   mm_pt(88.5), t("Ennakkomaksu tilattaessa", $kieli),    $page_tarjous, $seiska);
      $pdf_tarjous->draw_text(mm_pt(89),   mm_pt(88.5), t("Ennakkomaksu ennen luovutusta", $kieli),   $page_tarjous, $seiska);
      $pdf_tarjous->draw_text(mm_pt(161),  mm_pt(88.5), t("Luovutettaessa yhteensä", $kieli),     $page_tarjous, $seiska);

      $pdf_tarjous->draw_rectangle(mm_pt(90.9), mm_pt(21), mm_pt(83), mm_pt(195.4),        $page_tarjous, $rectparam);

      $pdf_tarjous->draw_text(mm_pt(172), mm_pt(151), sprintf('%10s', sprintf("%.2f", $uudet)),     $page_tarjous, $kymppi_courier);
      $pdf_tarjous->draw_text(mm_pt(162), mm_pt(151), $laskurow["valkoodi"],          $page_tarjous, $kymppi_helvetica);

      $pdf_tarjous->draw_text(mm_pt(172), mm_pt(101), sprintf('%10s', sprintf("%.2f", abs($vanha))),    $page_tarjous, $kymppi_courier);
      $pdf_tarjous->draw_text(mm_pt(162), mm_pt(101), $laskurow["valkoodi"],          $page_tarjous, $kymppi_helvetica);

      $pdf_tarjous->draw_text(mm_pt(162), mm_pt(84), $laskurow["valkoodi"],          $page_tarjous, $kymppi_helvetica);
      $pdf_tarjous->draw_text(mm_pt(172), mm_pt(84), sprintf('%10s', sprintf("%01.2f", $toimitettaessa)),  $page_tarjous, $kymppi_courier);
    }
    else {
      $pdf_tarjous->draw_text(mm_pt(178), mm_pt(151), t("Jatkuu", $kieli)."...", $page_tarjous);
    }
  }
}

if (!function_exists('print_pdf_tarjous')) {
  function print_pdf_tarjous($pdf_tarjous, $laskurow, $komento, $tee = "") {
    global $yhtiorow, $kukarow;

    $oslapp='';
    $returnvalue=0;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdf_tarjousfilenimi = "/tmp/Tarjous-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdf_tarjousfilenimi, "w");
    if (fwrite($fh, $pdf_tarjous->generate()) === FALSE) die("PDF create error $pdf_tarjousfilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'RUUDULLE') {
      return $pdf_tarjousfilenimi;
    }
    elseif ($komento == 'email' or substr($komento, 0, 12) == 'asiakasemail') {
      $liite = $pdf_tarjousfilenimi;

      $kutsu = t("Tarjous", $kieli);

      if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
        $kutsu .= ", ".trim($laskurow["nimi"]);
      }

      echo t("Tarjous tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "../inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdf_tarjousfilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Tarjous tulostuu")."...<br>";
      $line = exec("$komento $pdf_tarjousfilenimi", $output, $returnvalue);
    }

    //poistetaan tmp file samantien kuleksimasta...
    system("rm -f $pdf_tarjousfilenimi");
  }
}

if (!function_exists('tulosta_tarjous')) {
  function tulosta_tarjous($otunnus, $komento, $kieli = "", $tee = "", $hinnat = "") {
    global $yhtiorow, $kukarow;

    // Haetaan laskun tiedot
    $query = "	SELECT *, lasku.laatija laatija, lasku.luontiaika luontiaika, lasku.tunnus tunnus
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

    $query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
    $result = mysql_query($query) or pupe_error($query);
    $asiakasrow = mysql_fetch_array($result);

    //korjataan yhtiön tietoja
    $query  = "	SELECT *
						from kuka
						where tunnus = '$laskurow[myyja]'
						and yhtio = '$kukarow[yhtio]'";
    $myyresult = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($myyresult) == 1) {
      $myyrow = mysql_fetch_array($myyresult);

      $kukarow["toimipaikka"] = $myyrow["toimipaikka"];
    }

    $query = "	SELECT *
						FROM yhtion_toimipaikat
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[toimipaikka]'";
    $result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

    if (mysql_num_rows($result) == 1) {
      $yhtion_toimipaikkarow = mysql_fetch_array($result);

      $yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
      $yhtiorow["nimi"]   = $yhtion_toimipaikkarow["nimi"];
      $yhtiorow["osoite"]  = $yhtion_toimipaikkarow["osoite"];
      $yhtiorow["postino"]  = $yhtion_toimipaikkarow["postino"];
      $yhtiorow["postitp"]  = $yhtion_toimipaikkarow["postitp"];
      $yhtiorow["puhelin"]  = $yhtion_toimipaikkarow["puhelin"];

    }


    function printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous, $sarjanutunnus, $osasto, $selite, $total, $hinnat) {
      global $yhtiorow, $kukarow;

      //Tehään tästä taas array tän funktion siäiseen käyttöön jotta saamme sivunvaihdot toimimaan
      $page_tarjous2[$sivu] = $page_tarjous;

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
        $hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
      }
      else {
        $hinta_riv = "tilausrivi.hinta";
      }

      $sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

      $query_ale_lisa = generoi_alekentta('M');

      $query = "	SELECT tilausrivi.*,
							round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},2) rivihinta,
							round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl),2) bruttorivihinta,
							tilausrivin_lisatiedot.positio,
							$sorttauskentta
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in  ('L','T','U')
							$ennakkolisa
							$osasto
							ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
      $riresult = mysql_query($query) or pupe_error($query);

      while ($row = mysql_fetch_array($riresult)) {

        //jos on paljon rivejä tehdään uusi otsikko...
        if ($lask > 11) {
          loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous2[$sivu], 0);

          $sivu++;
          $lask = 0;
          $kala = 236;

          $page_tarjous2[$sivu] = alku_tarjous($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
        }

        list($page_tarjous2[$sivu], $kala, $lask, $sivu) = rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous2[$sivu], $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu);

        $lask++;

        if ($row["hinta"] != 0 and $hinnat == "BR" and (strpos($selite, 'Vene') !== FALSE or strpos($selite, 'Vaihtovene') !== FALSE)) {
          $total += $row["bruttorivihinta"];
        }
        else {
          $total += $row["rivihinta"];
        }
      }
      return array ($page_tarjous2[$sivu], $lask, $kala, $sivu, $total);
    }


    //oletuksia
    $kala    = 236;
    $lask    = 0;
    $total    = 0;
    $sivu    = 1;

    //Myyntisopimuksen pohja
    $pdf_tarjous = new pdffile;

    $pdf_tarjous->set_default('margin-top', 20);
    $pdf_tarjous->set_default('margin-bottom', 0);
    $pdf_tarjous->set_default('margin-left', 0);
    $pdf_tarjous->set_default('margin-right', 0);

    $pdf_tarjous->enable('import');

    $page_tarjous[$sivu] = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);

    // Uusi vene
    list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], "myyntirivitunnus",  "HAVING tilausrivin_lisatiedot.positio like '%moottori%'", "Ajoneuvon merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);
    // Uusi moottori
    //list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], "",      "HAVING tilausrivin_lisatiedot.positio='Moottori'", "Moottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);
    // Uudet tarvikkeet
    list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], "",      "HAVING tilausrivin_lisatiedot.positio = '' and tilausrivi.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'", "Tarvike", $total, $hinnat);

    // Siirrytään vaihtolaitteiden kimppuun
    $total_uudet = $total;
    $kala = 129;
    $lask--;

    // Vaihtokamat
    list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], "ostorivitunnus",  "HAVING tilausrivin_lisatiedot.positio like '%vaihto%'", "Vaihtokohteen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);
    //list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], "ostorivitunnus",  "HAVING tilausrivin_lisatiedot.positio='Vaihtomoottori'", "Vaihtomoottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $hinnat);

    // Lasketaan vähän hintoja yhteen
    $total_vanhat = $total - $total_uudet;

    // Lopputekstit
    loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous[$sivu], 1, $total_uudet, $total_vanhat);

    // Tulostetaan sivu
    print_pdf_tarjous ($pdf_tarjous, $laskurow, $komento, $tee);

    $tee = '';
  }
}
?>
