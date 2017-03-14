<?php

if (@require_once "pdflib/phppdflib.class.php");
else require_once "phppdflib.class.php";

$norm["height"]       = 10;
$norm["font"]         = "Times-Roman";

$pieni["height"]       = 8;
$pieni["font"]         = "Times-Roman";

$boldi["height"]       = 10;
$boldi["font"]         = "Times-Bold";

$pieni_boldi["height"]     = 8;
$pieni_boldi["font"]     = "Times-Bold";

$keraaja_font["height"]    = 12;
$keraaja_font["font"]     = "Helvetica";

$iso["height"]         = 14;
$iso["font"]         = "Helvetica-Bold";

$iso_boldi["height"]     = 24;
$iso_boldi["font"]       = "Helvetica-Bold";

$rectparam["width"]     = 0.3;
$rivinkorkeus        = 17;

if (!function_exists('vakadr_otsikko')) {
  function vakadr_otsikko($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    //PDF parametrit
    if ($pdf === NULL) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
    }

    $thispage = $pdf->new_page("a4");

    $page[$sivu] = $thispage;

    tulosta_logo_pdf($pdf, $thispage, $laskurow);

    $pdf->draw_text(290, 815, t("Kuljetuksen VAK/ADR-erittely", $kieli), $thispage, $iso);

    // VASEN PUOLI
    // Myyjän tiedot
    $pdf->draw_text(30, 717, t("Lähettäjä", $kieli), $thispage, $boldi);
    $pdf->draw_text(30, 707, $yhtiorow["nimi"],  $thispage, $norm);
    $pdf->draw_text(30, 697, $yhtiorow["osoite"],  $thispage, $norm);
    $pdf->draw_text(30, 687, $yhtiorow["postino"]." ".$yhtiorow["postitp"], $thispage, $norm);
    $pdf->draw_text(30, 677, $yhtiorow["maa"],  $thispage, $norm);

    // Tilaajantiedot
    $pdf->draw_text(30, 656, t("Tilaaja", $kieli),   $thispage, $boldi);
    $pdf->draw_text(30, 644, $laskurow["nimi"],  $thispage, $norm);
    $pdf->draw_text(30, 634, $laskurow["nimitark"],  $thispage, $norm);
    $pdf->draw_text(30, 624, $laskurow["osoite"],  $thispage, $norm);
    $pdf->draw_text(30, 614, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
    $pdf->draw_text(30, 604, $laskurow["maa"],  $thispage, $norm);
    // Toimitustosoite
    $pdf->draw_text(290, 656, t("Toimitustosoite", $kieli),   $thispage, $boldi);
    $pdf->draw_text(290, 644, $laskurow["toim_nimi"],  $thispage, $norm);
    $pdf->draw_text(290, 634, $laskurow["toim_nimitark"],  $thispage, $norm);
    $pdf->draw_text(290, 624, $laskurow["toim_osoite"],  $thispage, $norm);
    $pdf->draw_text(290, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
    $pdf->draw_text(290, 604, $laskurow["toim_maa"], $thispage, $norm);

    // OIKEA PUOLI
    $pdf->draw_text(290, 792, t("Toimitustapa", $kieli), $thispage, $boldi);
    $pdf->draw_text(370, 792, $laskurow["toimitustapa"], $thispage, $boldi);

    $pdf->draw_text(290, 780, t("Tilausnumero", $kieli), $thispage, $norm);
    $pdf->draw_text(370, 780, $laskurow["tunnus"], $thispage, $norm);

    $pdf->draw_text(290, 770, t("Päivämäärä", $kieli), $thispage, $norm);
    $pdf->draw_text(370, 770, date('d.m.Y H:i'), $thispage, $norm);

    if (trim($laskurow['ohjausmerkki']) != '') {
      $pdf->draw_text(290, 740, t("Ohjausmerkki", $kieli), $thispage, $norm);
      $pdf->draw_text(370, 740, $laskurow['ohjausmerkki'], $thispage, $iso);
    }

    // Otsikon viimeisin rivi on pikselissä 673
    // 665 on laskettu ottaen huomioon että jokaisen SSCC:n alussa tiputetaan $kala-15 (650), joka on oikeakohta aloittaa piirtäminen.

    $params["kala"] = 595;
    $kala      = 595;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('vakadr_rivi')) {
  function vakadr_rivi($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Jos on paljon rivejä tehdään uusi sivu...
    if ($kala <= 100) {
      $sivu++;

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = vakadr_loppu($return);
      $params = vakadr_otsikko($return);

      // Luodaan muuttujat
      extract($params);
      $page[$sivu] = $thispage;
    }

    if (isset($sscc)) {

      $kala = $kala-15; // oikeasta kohtaan ja että SSCC väliin tulee väliä

      if ((is_numeric($sscc_ulkoinen) and (int) $sscc_ulkoinen > 0) or (!is_numeric($sscc_ulkoinen) and (string) $sscc_ulkoinen != "")) {
        $pdf->draw_text(150, $kala, $sscc_ulkoinen, $thispage, $boldi); // ULKOINEN_SSCC tähän jos löytyy
      }
      else {
        $pdf->draw_text(150, $kala, $sscc, $thispage, $boldi); // SSCC tähän
      }

      // tää on se ongelma.
      // tehdään kollikohtaiset otsikot.
      $pdf->draw_text(30,  $kala, t("Kollitunniste (SSCC)", $kieli), $thispage, $boldi);
      $pdf->draw_text(290, $kala, t("Tilaus", $kieli), $thispage, $boldi);
      $pdf->draw_text(330, $kala, $laskurow["tunnus"], $thispage, $norm);
      $pdf->draw_text(520, $kala, t("Määrä kg", $kieli), $thispage, $boldi);
      $kala = $kala-12;

    }

    if (isset($vakkoodi) and isset($massa)) {

      if (strlen($vakkoodi) >= 80) {
        list($ff_string, $ff_font) = pdf_fontfit($vakkoodi, 500, $pdf, $norm);
        $pdf->draw_text(30, $kala, $ff_string, $thispage, $ff_font);
      }
      else {
        $pdf->draw_text(30, $kala, $vakkoodi, $thispage, $norm);
      }

      $pdf->draw_text(520, $kala, sprintf("%.2f", $massa), $thispage, $boldi);
      $kala = $kala-11;
    }

    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('vakadr_loppu')) {
  function vakadr_loppu($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // yhtiötiedot
    $y_tiedot_ok  = FALSE;
    $y_nimi     = $laskurow["yhtio_nimi"];
    $y_osoite     = $laskurow["yhtio_osoite"];
    $y_postino     = $laskurow["yhtio_postino"];
    $y_postitp     = $laskurow["yhtio_postitp"];
    $y_maa       = $laskurow["yhtio_maa"];
    $y_kotipaikka  = $laskurow["yhtio_kotipaikka"];
    $y_vatnumero  = preg_replace("/^0037/", "", $laskurow["yhtio_ovttunnus"]);
    $y_iban     = $yhtiorow['pankkiiban1'];
    $y_swift     = $yhtiorow['pankkiswift1'];

    if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and tunnus='$laskurow[yhtio_toimipaikka]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok  = TRUE;
        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];
      }
    }

    // jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
    if ($laskurow["yhtio_ovttunnus"] != "" and $laskurow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok  = TRUE;
        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];
      }
    }

    // jos ei olla haettu muualta, niin haetaan tiedot yhtiöltä
    if (!$y_tiedot_ok) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$laskurow[yhtio]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];
      }
    }


    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(70, 20, 20, 580,  $thispage, $rectparam);
    $pdf->draw_rectangle(70, 207, 20, 580,  $thispage, $rectparam);
    $pdf->draw_rectangle(70, 394, 20, 580,  $thispage, $rectparam);

    $pdf->draw_text(30, 55, $y_nimi,    $thispage, $pieni);
    $pdf->draw_text(30, 45, $y_osoite,    $thispage, $pieni);
    $pdf->draw_text(30, 35, $y_postino."  ".$y_postitp,  $thispage, $pieni);
    $pdf->draw_text(30, 25, $y_maa,      $thispage, $pieni);

    $pdf->draw_text(217, 55, t("Puhelin", $kieli).":",      $thispage, $pieni);
    $pdf->draw_text(247, 55, $y_puhelin,            $thispage, $pieni);
    $pdf->draw_text(217, 45, t("Fax", $kieli).":",      $thispage, $pieni);
    $pdf->draw_text(247, 45, $y_fax,              $thispage, $pieni);
    $pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",    $thispage, $pieni);
    $pdf->draw_text(257, 35, $y_email,              $thispage, $pieni);
    $pdf->draw_text(217, 25, t("Web", $kieli).":",        $thispage, $pieni);
    $pdf->draw_text(247, 25, $y_www,              $thispage, $pieni);

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",      $thispage, $pieni);
    $pdf->draw_text(444, 55, tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $thispage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",    $thispage, $pieni);
    $pdf->draw_text(444, 45, $y_kotipaikka,            $thispage, $pieni);
    $pdf->draw_text(404, 35, t("Alv.rek", $kieli),        $thispage, $pieni);
    $pdf->draw_text(404, 25, $y_iban." / ".$y_swift,      $thispage, $pieni);

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('print_pdf_vakadr')) {
  function print_pdf_vakadr($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);
    $returnvalue = 0;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    $pdffilenimi = tempnam("/tmp", "VakAdr").".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    if ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    else {
      if (!is_array($komento)) {
        $komentoulos = array($komento);
      }
      else {
        $komentoulos = $komento;
      }

      foreach ($komentoulos as $komento) {
        // itse print komento...
        if ($komento == 'email' or substr($komento, 0, 12) == 'asiakasemail') {
          $liite = $pdffilenimi;
          $content_body = "";

          echo t("VAK/ADR-erittely tulostuu")."...<br>";

          $kutsu = t("VAK/ADR-erittely %s", $kieli, $laskurow["tunnus"]);

          if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
            $kutsu .= ", ".trim($laskurow["nimi"]);
          }

          if (@include "inc/sahkoposti.inc");
          else include "sahkoposti.inc";
        }
        elseif ($komento != '' and $komento != 'edi') {
          echo t("VAK/ADR-erittely tulostuu")."...<br>";
          $line = exec("$komento $pdffilenimi", $output, $returnvalue);
        }
      }
    }
  }
}
