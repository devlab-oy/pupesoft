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
require_once 'pdflib/phppdflib.class.php';

//muutamat funktiot...
if (!function_exists('alku')) {
  function alku_vakuutushakemus($laskurow, $sivu, $kieli, $pdf_vakuutushakemus = "") {
    global $yhtiorow, $kukarow, $pupe_root_polku, $kasi_helvetica, $kymppi_helvetica, $seiska, $rectparam;

    if ($pdf_vakuutushakemus == "") {
      $pdf_vakuutushakemus = new pdffile;

      $pdf_vakuutushakemus->set_default('margin-top', 20);
      $pdf_vakuutushakemus->set_default('margin-bottom', 0);
      $pdf_vakuutushakemus->set_default('margin-left', 0);
      $pdf_vakuutushakemus->set_default('margin-right', 0);
      $pdf_vakuutushakemus->enable('import');
    }

    // Haetaan pohja
    ob_start();

    $filename = "{$pupe_root_polku}/ajoneuvomyynti/vakuutushakemus.pdf";

    $handle = fopen($filename, "r");
    $d = fread($handle, filesize($filename));
    fclose($handle);

    $pdf_vakuutushakemus->import->append($d);

    $pdf_vakuutushakemus_sivu = $pdf_vakuutushakemus->import->get_pages();

    ob_end_clean();

    $apu_yhtiorow = array();

    // varmistetaan, että kopiossakin tulee oikean toimipaikan logo
    if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
      $query = "SELECT *
                  FROM yhtion_toimipaikat
                  WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[yhtio_toimipaikka]' and lasku_logo != ''";
      $result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

      if (mysql_num_rows($result) == 1) {
        $yhtion_toimipaikkarow = mysql_fetch_array($result);
        $apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
      }
    }
    else {
      $query = "SELECT *
                  FROM yhtion_parametrit
                  WHERE yhtio = '$laskurow[yhtio]'";
      $result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

      if (mysql_num_rows($result) == 1) {
        $yhtion_parametritrow = mysql_fetch_array($result);
        $apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
      }
    }

    unset($data);

    if ( (int) $apu_yhtiorow["lasku_logo"] > 0) {
      $liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
      $data = $liite["data"];
      $isizelogo[0] = $liite["image_width"];
      $isizelogo[1] = $liite["image_height"];
      unset($liite);
    }
    elseif (file_exists($apu_yhtiorow["lasku_logo"])) {
      $filename = $apu_yhtiorow["lasku_logo"];

      $fh = fopen($filename, "r");
      $data = fread($fh, filesize($filename));
      fclose($fh);

      $isizelogo = getimagesize($apu_yhtiorow["lasku_logo"]);
    }

    if ($data) {
      $image = $pdf_vakuutushakemus->jfif_embed($data);

      if (!$image) {
        echo t("Logokuvavirhe");
      }
      else {

        $logoparam = array();

        $lasku_logo_koko = 50;
        $lasku_logo_positio = 830;

        if ((int) $yhtiorow["lasku_logo_koko"] > 0) {
          $lasku_logo_koko = (int) $yhtiorow["lasku_logo_koko"];
        }
        if ((int) $yhtiorow["lasku_logo_positio"] > 0) {
          $lasku_logo_positio = (int) $yhtiorow["lasku_logo_positio"];
        }

        if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (180 / $isizelogo[0]) <= $lasku_logo_koko) {
          $logoparam['scale'] = 180 / $isizelogo[0];
        }
        else {
          $logoparam['scale'] = $lasku_logo_koko  / $isizelogo[1];
        }

        $placement = $pdf_vakuutushakemus->image_place($image, $lasku_logo_positio-($logoparam['scale']*$isizelogo[1]), 20, $pdf_vakuutushakemus_sivu[0], $logoparam);
      }
    }
    else {
      $pdf_vakuutushakemus->draw_text(30, 815,  $laskurow["yhtio_nimi"], $pdf_vakuutushakemus_sivu[0]);
    }

    $pp = substr($laskurow["luontiaika"], 8, 2);
    $kk = substr($laskurow["luontiaika"], 5, 2);
    $vv = substr($laskurow["luontiaika"], 0, 4);

    $voimassa = date("d.m.Y", mktime(0, 0, 0, $kk, $pp+14, $vv));
    $luotoaik = date("d.m.Y", mktime(0, 0, 0, $kk, $pp, $vv));

    $query = "SELECT *
                FROM laskun_lisatiedot
                WHERE yhtio = '$kukarow[yhtio]'
                and otunnus = '$laskurow[tunnus]'";
    $lisres = mysql_query($query) or pupe_error($query);
    $lisrow = mysql_fetch_array($lisres);

    $query = "SELECT *
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[liitostunnus]'";
    $lisres = mysql_query($query) or pupe_error($query);
    $asiakasrow = mysql_fetch_array($lisres);


    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(272), t("VENEVAKUUTUSHAKEMUS"),            $pdf_vakuutushakemus_sivu[0]);
    $pdf_vakuutushakemus->draw_text(mm_pt(134), mm_pt(284), t("Vakuutusyhtiö täyttää"),            $pdf_vakuutushakemus_sivu[0], $seiska);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(265), t("VAKUUTUKSEN OTTAJA"),            $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(261), t("Sukunimi ja puhutteluetunimi"),        $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(88),  mm_pt(261), t("Muut etunimet"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(261), t("Arvo tai ammatti"),              $pdf_vakuutushakemus_sivu[0], $seiska);

    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(256), $laskurow["nimi"],                 $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(256), $laskurow["nimitark"],               $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(256), $asiakasrow["ammatti"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(252), t("Lähiosoite"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(252), t("Postinumero ja -toimipaikka"),        $pdf_vakuutushakemus_sivu[0], $seiska);

    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(248), $laskurow["osoite"],               $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(248), $laskurow["postino"]." ".$laskurow["postitp"],  $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(244), t("Kotipuhelin"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(49),  mm_pt(244), t("Työpuhelin"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(88),  mm_pt(244), t("Matkapuhelin"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(244), t("Henkilö- tai Y-tunnus"),            $pdf_vakuutushakemus_sivu[0], $seiska);

    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(240), $asiakasrow["puhelin"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(240), $asiakasrow["tyopuhelin"],            $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(240), $asiakasrow["gsm"],               $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(240), $laskurow["ovttunnus"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(235), t("Pankkiyhteys"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(235), t("Kieli"),                    $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(231), $laskurow["pankkiyhteys"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(231), strtoupper($asiakasrow["kieli"]),        $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(223), t("OMISTAJA (EDUNSAAJA)"),            $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(219), t("Sukunimi ja puhutteluetunimi"),        $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(88),  mm_pt(219), t("Muut etunimet"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(219), t("Arvo tai ammatti"),              $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(210), t("Lähiosoite"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(210), t("Postinumero ja -toimipaikka"),        $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(202), t("Kotipuhelin"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(49),  mm_pt(202), t("Työpuhelin"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(88),  mm_pt(202), t("Matkapuhelin"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(128), mm_pt(202), t("Henkilö- tai Y-tunnus"),            $pdf_vakuutushakemus_sivu[0], $seiska);

    if ($lisrow["rekisteilmo_omistaja"] == "NORDEA") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "Nordea Rahoitus Suomi Oy", $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "Nihtisillantie 3 G",     $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "ESPOO 00020",        $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    elseif ($lisrow["rekisteilmo_omistaja"] == "GE") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "GE Money Oy",         $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "Kuortaneenkatu 2",     $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "HELSINKI 00510",      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    elseif ($lisrow["rekisteilmo_omistaja"] == "OKO") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "Pohjola Pankki Oyj",     $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "PL 308",           $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "POHJOLA 00013",      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    elseif ($lisrow["rekisteilmo_omistaja"] == "POHJOLA") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "Pohjola Pankki Oyj",     $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "PL 308",           $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "POHJOLA 00013",      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    elseif ($lisrow["rekisteilmo_omistaja"] == "SAMPO") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "Sampo Rahoitus",       $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "PL 1274",           $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "SAMPO 00075",        $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    elseif ($lisrow["rekisteilmo_omistaja"] == "SANTAN") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "Santander",         $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "Hermannin Rantatie 10",   $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "HELSINKI 00580",      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    elseif ($lisrow["rekisteilmo_omistaja"] == "HANDEL") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), "Handelsbanken Rahoitus Oyj ", $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), "Aleksanterinkatu 11",     $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), "HELSINKI 00100",      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), "",             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }
    else {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(215), $laskurow["nimi"],                 $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(215), $laskurow["nimitark"],               $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(215), $asiakasrow["ammatti"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(206), $laskurow["osoite"],               $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(206), $laskurow["postino"]." ".$laskurow["postitp"],  $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(197), $asiakasrow["puhelin"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(50),  mm_pt(197), $asiakasrow["tyopuhelin"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(89),  mm_pt(197), $asiakasrow["gsm"],               $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
      $pdf_vakuutushakemus->draw_text(mm_pt(129), mm_pt(197), $laskurow["ovttunnus"],             $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    }



    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(190), t("TIEDOT VENEESTÄ"),              $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(149), t("TIEDOT MOOTTORISTA"),            $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(124), t("VENEEN KÄYTTÖ"),                $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);


    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(108), t("OTETTAVA VAKUUTUS, OMAVASTUU JA KÄYVÄT ARVOT"), $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(105), t("Kaskolaji"),                  $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(105), t("Omavastuu"),                  $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(105), t("Runko, takila, purjeet"),          $pdf_vakuutushakemus_sivu[0], $seiska);

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

    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(100), $vakuutushak_vakuutusyhtio_array[$lisrow["vakuutushak_vakuutusyhtio"]]." ".$vakuutushak_kaskolaji_array[$lisrow["vakuutushak_kaskolaji"]],   $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(100), $lisrow["vakuutushak_perusomavastuu"],       $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(100), $lisrow["vakuutushak_runko_takila_purjeet"],   $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);


    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(96), t("Moottori"),                  $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(62),  mm_pt(96), t("Varusteet"),                  $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(96), t("Yhteensä"),                  $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(96), t("Maksetaan"),                  $pdf_vakuutushakemus_sivu[0], $seiska);

    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(91), $lisrow["vakuutushak_moottori"],         $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(63),  mm_pt(91), $lisrow["vakuutushak_varusteet"],         $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(91), $lisrow["vakuutushak_yhteensa"],         $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(91), $lisrow["vakuutushak_maksuerat"]." ".t("Erässä"), $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(83), t("LISÄTIETOJA"),                $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(69), t("MYYJÄTIEDOT"),                $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(65), t("Myyjä"),                    $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(106), mm_pt(65), t("Myyjätunnus"),                $pdf_vakuutushakemus_sivu[0], $seiska);

    $query  = "  select *
            from kuka
            where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
    $myyresult = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($myyresult) == 1) {
      $myyrow = mysql_fetch_array($myyresult);
    }
    else {
      $myyrow = $kukarow;
    }

    $pdf_vakuutushakemus->draw_text(mm_pt(12), mm_pt(60), $myyrow["nimi"]."   ".$yhtiorow["nimi"],      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(53), t("ALLEKIRJOITUKSET"),              $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(49), t("Paikka ja aika"),                $pdf_vakuutushakemus_sivu[0], $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(45), $yhtiorow["postitp"]." ".date("d.m.Y"),      $pdf_vakuutushakemus_sivu[0], $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(87),  mm_pt(49), t("Vakuutuksen ottajan allekirjoitus"),      $pdf_vakuutushakemus_sivu[0], $kasi_helvetica);
    $pdf_vakuutushakemus->draw_text(mm_pt(87),  mm_pt(30), $laskurow["nimi"],                $pdf_vakuutushakemus_sivu[0], $seiska);


    $pdf_vakuutushakemus->draw_text(mm_pt(150), mm_pt(49), t("Vakuutusyhtiön edustajan allekirjoitus"),    $pdf_vakuutushakemus_sivu[0], $kasi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(10),  mm_pt(25), t("Olen vastaanottanut haettua vakuutusta koskevan materiaalin. Vakuutan antamani tiedot oikeiksi"),  $pdf_vakuutushakemus_sivu[0], $kasi_helvetica);

    //Palautetan luotu pdf ja sivu
    return array ($pdf_vakuutushakemus, $pdf_vakuutushakemus_sivu[0]);
  }
}


if (!function_exists('venerivi_vakuutushakemus')) {
  function venerivi_vakuutushakemus($pdf_vakuutushakemus, $laskurow, $kieli, $page_vakuutushakemus, $row) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

    $query = "  select *
            from sarjanumeroseuranta
            where yhtio       = '$kukarow[yhtio]'
            and tuoteno       = '$row[tuoteno]'
            and myyntirivitunnus = '$row[tunnus]'";
    $sarjares = mysql_query($query) or pupe_error($query);
    $sarjarow = mysql_fetch_array($sarjares);

    $query = "SELECT sarjanumeron_lisatiedot.*,
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
                WHERE sarjanumeron_lisatiedot.yhtio      = '$kukarow[yhtio]'
                and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
    $lisatietores = mysql_query($query) or pupe_error($query);
    $lisarow = mysql_fetch_array($lisatietores);

    $query = "SELECT *
                FROM laskun_lisatiedot
                WHERE yhtio = '$kukarow[yhtio]'
                and otunnus = '$laskurow[tunnus]'";
    $lisares = mysql_query($query) or pupe_error($query);
    $laslisarow = mysql_fetch_array($lisares);


    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(186), t("Veneen merkki, malli ja runkonumero"),    $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(181), $row["nimitys"]."  ".$sarjarow["sarjanumero"],  $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(186), t("Valmistaja"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(181), $lisarow["Merkki"],                $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(186), t("Venelaji"),                  $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(181), $lisarow["Tyyppi"],                $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(177), t("Veneen väri"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(172), $lisarow["Varirunko"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(62),  mm_pt(177), t("Rakennusvuosi"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(63),  mm_pt(172), $lisarow["Vuosimalli"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(177), t("Tyyppi"),                  $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(172), $lisarow["Runkotyyppi"],            $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(177), t("Rungon rakennusaine"),            $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(172), $lisarow["Materiaali"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(168.5), t("Arvioitu suurin nopeus"),          $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(163.5), $lisarow["Suurin_nopeus"],          $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(62),  mm_pt(168.5), t("Suurin henkilöluku"),            $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(63),  mm_pt(163.5), $lisarow["Suurin_henkiloluku"],        $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(168.5), t("Suurin pituus"),              $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(163.5), $lisarow["Pituus"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(168.5), t("Suurin leveys"),              $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(163.5), $lisarow["Leveys"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(160), t("CE- tai MKH-kilpi"),              $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(155), $lisarow["Kilpi"],                $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(62),  mm_pt(160), t("Katsastettu"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(63),  mm_pt(155), $lisarow["Katsastuspvm"],            $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(85),  mm_pt(160), t("Luokkaan"),                  $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(86),  mm_pt(155), $lisarow["Katsastusluokka"],          $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(106), mm_pt(160), t("Käsisprinkler"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(155), $lisarow["Sprinkleri"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(131), mm_pt(160), t("Automaattisprinkler"),            $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(132), mm_pt(155), "",                        $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(160), t("Rekisterinumero"),              $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(155), $laslisarow["rekisteilmo_rekisterinumero"],    $page_vakuutushakemus, $kymppi_helvetica);


    return $page_vakuutushakemus;
  }
}

if (!function_exists('moottoririvi_vakuutushakemus')) {
  function moottoririvi_vakuutushakemus($pdf_vakuutushakemus, $laskurow, $kieli, $page_vakuutushakemus, $row, $venerow) {
    global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

    // Löytyykö moottorille omaa sarjanumeroa, vai onko se sisämoottori jolloin pitää hakea veneen sarjanumerolla
    $query = "  select *
            from sarjanumeroseuranta
            where yhtio       = '$kukarow[yhtio]'
            and tuoteno       = '$row[tuoteno]'
            and myyntirivitunnus = '$row[tunnus]'";
    $sarjares = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($sarjares) == 0) {
      $query = "  select *
              from sarjanumeroseuranta
              where yhtio       = '$kukarow[yhtio]'
              and tuoteno       = '$venerow[tuoteno]'
              and myyntirivitunnus = '$venerow[tunnus]'";
      $sarjares = mysql_query($query) or pupe_error($query);

      $onkomoottori = "VENE";
    }
    else {
      $onkomoottori = "MOOT";
    }
    $sarjarow = mysql_fetch_array($sarjares);


    $query = "SELECT sarjanumeron_lisatiedot.*,
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
                WHERE sarjanumeron_lisatiedot.yhtio      = '$kukarow[yhtio]'
                and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
    $lisatietores = mysql_query($query) or pupe_error($query);
    $lisarow = mysql_fetch_array($lisatietores);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(144), t("Moottorin merkki, malli ja valmistenumero"),  $page_vakuutushakemus, $seiska);

    if ($onkomoottori == "MOOT") {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(139), $row["nimitys"]."  ".$sarjarow["sarjanumero"],      $page_vakuutushakemus, $kymppi_helvetica);
    }
    else {
      $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(139), $lisarow["Koneen_merkki"]." ".$lisarow["Koneen_malli"],  $page_vakuutushakemus, $kymppi_helvetica);
    }

    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(144), t("Vuosimalli"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(139), $lisarow["Koneen_vuosimalli"],          $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(144), t("Tyyppi"),                  $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(139), $lisarow["Koneistus"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(11),  mm_pt(135), t("Polttoaine"),                $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(12),  mm_pt(130), $lisarow[""],                  $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(107), mm_pt(135), t("Teho [hv]"),                  $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(108), mm_pt(130), $lisarow["Teho_hv"],              $page_vakuutushakemus, $kymppi_helvetica);

    $pdf_vakuutushakemus->draw_text(mm_pt(157), mm_pt(135), t("Teho [kW]"),                  $page_vakuutushakemus, $seiska);
    $pdf_vakuutushakemus->draw_text(mm_pt(158), mm_pt(130), $lisarow["Teho_kw"],              $page_vakuutushakemus, $kymppi_helvetica);


    return $page_vakuutushakemus;
  }
}


if (!function_exists('print_pdf_vakuutushakemus')) {
  function print_pdf_vakuutushakemus($pdf_vakuutushakemus, $laskurow, $komento, $tee = "") {
    global $yhtiorow, $kukarow;

    $oslapp='';
    $returnvalue=0;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdf_vakuutushakemusfilenimi = "/tmp/Tarjous-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdf_vakuutushakemusfilenimi, "w");
    if (fwrite($fh, $pdf_vakuutushakemus->generate()) === FALSE) die("PDF create error $pdf_vakuutushakemusfilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'RUUDULLE') {
      return $pdf_vakuutushakemusfilenimi;
    }
    elseif ($komento == 'email') {
      $liite = $pdf_vakuutushakemusfilenimi;

      $kutsu = t("Vakuutushakemus", $kieli);

      if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
        $kutsu .= ", ".trim($laskurow["nimi"]);
      }

      echo t("Vakuutushakemus tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "../inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdf_vakuutushakemusfilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Vakuutushakemus tulostuu")."...<br>";
      $line = exec("$komento $pdf_vakuutushakemusfilenimi", $output, $returnvalue);
    }

    //poistetaan tmp file samantien kuleksimasta...
    system("rm -f $pdf_vakuutushakemusfilenimi");
  }
}

if (!function_exists('tulosta_vakuutushakemus')) {
  function tulosta_vakuutushakemus($otunnus, $komento, $kieli = "", $tee = "") {
    global $yhtiorow, $kukarow;

    // Haetaan laskun tiedot
    $query = "SELECT *
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

    $sivu = 1;

    // Pohja
    list ($pdf_vakuutushakemus, $page_vakuutushakemus[$sivu]) = alku_vakuutushakemus ($laskurow, $sivu, $kieli, "", "");

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
                and tilausrivi.tyyppi    in  ('L','T','U')";

    $query1 = $query." HAVING tilausrivin_lisatiedot.positio='Vene'";
    $riresult = mysql_query($query1) or pupe_error($query1);
    $venerow = mysql_fetch_array($riresult);
    $page_vakuutushakemus[$sivu] = venerivi_vakuutushakemus($pdf_vakuutushakemus, $laskurow, $kieli, $page_vakuutushakemus[$sivu], $venerow);

    $query1 = $query." HAVING tilausrivin_lisatiedot.positio='Moottori'";
    $riresult = mysql_query($query1) or pupe_error($query1);
    $moottorirow = mysql_fetch_array($riresult);
    $page_vakuutushakemus[$sivu] = moottoririvi_vakuutushakemus($pdf_vakuutushakemus, $laskurow, $kieli, $page_vakuutushakemus[$sivu], $moottorirow, $venerow);

    // Tulostetaan sivu
    print_pdf_vakuutushakemus ($pdf_vakuutushakemus, $laskurow, $komento, $tee);

    $tee = '';
  }
}
