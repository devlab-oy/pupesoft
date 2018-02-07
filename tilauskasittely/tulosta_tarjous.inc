<?php

if (@include_once "pdflib/phppdflib.class.php") {
}
else {
  include_once "phppdflib.class.php";
}

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
  if (include_once "viivakoodi/Barcode.php");
  elseif (include_once "Barcode.php");
}

// Haetaan asiakkaan kieli
$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
$kielresult = pupe_query($querykiel);
$kielrow = mysql_fetch_array($kielresult);

if ($kielrow['kieli'] != '') {
  $kieli = strtolower($kielrow['kieli']);
}

$norm["height"]        = 10;
$norm["font"]          = "Times-Roman";

$pieni["height"]       = 8;
$pieni["font"]         = "Times-Roman";

$boldi["height"]       = 10;
$boldi["font"]         = "Times-Bold";

$pieni_boldi["height"] = 8;
$pieni_boldi["font"]   = "Times-Bold";

$iso["height"]         = 14;
$iso["font"]           = "Helvetica-Bold";

$rectparam["width"]    = 0.3;
$rivinkorkeus          = 15;

//muutamat funktiot...
if (!function_exists('alku_tarjous')) {
  function alku_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous, $kaanteinen_verotus, $page_tarjous, $sivu, $verolliset_verottomat_hinnat) {
    global $yhtiorow, $kukarow, $pieni, $norm, $boldi, $pieni_boldi, $iso, $rectparam, $useita;

    $page_tarjous[$sivu] = $pdf_tarjous->new_page("a4");

    tulosta_logo_pdf($pdf_tarjous, $page_tarjous[$sivu], $laskurow);

    if ($yhtiorow['tarjoustyyppi'] == 1) {
      $otsikko_len = $pdf_tarjous->strlen(t("TARJOUS", $kieli), $iso);
      $pdf_tarjous->draw_text((595 / 2) - ($otsikko_len / 2), 760, t("TARJOUS", $kieli),   $page_tarjous[$sivu], $iso);
      $pdf_tarjous->draw_text(530, 803, t("Sivu", $kieli), $page_tarjous[$sivu], $pieni);

      //Vasen sarake
      $pdf_tarjous->draw_text(50, 625, t("Asiakas", $kieli), $page_tarjous[$sivu], $boldi);

      $pdf_tarjous->draw_text(50, 612, t("Nimi", $kieli), $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 612, $laskurow["nimi"].' '.$laskurow["nimitark"], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 599, t("Osoite", $kieli), $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 599, $laskurow["osoite"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 586, $laskurow["postino"]." ".$laskurow["postitp"], $page_tarjous[$sivu], $norm);

      $query = "SELECT gsm, puhelin, ytunnus
                FROM asiakas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['liitostunnus']}'";
      $puh_res = pupe_query($query);
      $puh_row = mysql_fetch_assoc($puh_res);

      $pdf_tarjous->draw_text(50, 573, t("Puhelinnumero", $kieli), $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 573, $puh_row['puhelin'], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 560, t("GSM", $kieli), $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 560, $puh_row['gsm'], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 547, t("Y-tunnus/Sotu", $kieli), $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 547, $puh_row['ytunnus'], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(350, 625, t("Toimitusosoite", $kieli),   $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(350, 612, $laskurow["toim_nimi"].' '.$laskurow["toim_nimitark"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(350, 599, $laskurow["toim_osoite"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(350, 586, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(530, 785, t("Numero", $kieli), $page_tarjous[$sivu], $pieni);

      $valekala = 775;

      foreach (explode(",", $laskurow["tunnus"]) as $tun) {
        $pdf_tarjous->draw_text(530, $valekala, $tun, $page_tarjous[$sivu], $pieni);
        $valekala -= 7;
      }

      $pvm_len = $pdf_tarjous->strlen(tv1dateconv($laskurow["luontiaika"]), $pieni);
      $pdf_tarjous->draw_text((595 / 2) - ($pvm_len / 2), 750, tv1dateconv($laskurow["luontiaika"]), $page_tarjous[$sivu], $pieni);

      $pdf_tarjous->draw_text(50, 730, t("Myyjä", $kieli), $page_tarjous[$sivu], $boldi);

      //etsitään myyjän nimi
      $query  = "SELECT nimi, kuka, puhno, eposti
                 from kuka
                 where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
      $myyresult = pupe_query($query);
      $myyrow = mysql_fetch_array($myyresult);

      list($ff_string, $ff_font) = pdf_fontfit($myyrow["nimi"], 105, $pdf_tarjous, $boldi);
      $pdf_tarjous->draw_text(150, 730, $ff_string, $page_tarjous[$sivu], $ff_font);

      $pdf_tarjous->draw_text(50, 717, t("Y-tunnus", $kieli), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(150, 717, tulosta_ytunnus(preg_replace("/^0037/", "", $laskurow["yhtio_ovttunnus"]), $laskurow["yhtio_maa"], $laskurow["vienti"]), $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 704, t("Nimi", $kieli), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(150, 704, $yhtiorow['nimi'], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 691, t("Osoite", $kieli), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(150, 691, $yhtiorow['osoite'], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(150, 678, $yhtiorow['postino'].' '.$yhtiorow['postitp'], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 665, t("Puhelin", $kieli), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(150, 665, $myyrow['puhno'], $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_text(50, 652, t("Sähköposti", $kieli), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(150, 652, $myyrow['eposti'], $page_tarjous[$sivu], $norm);

    }
    elseif ($yhtiorow['tarjoustyyppi'] == 5) {
      $pdf_tarjous->draw_text(310, 815, t("Tarjous", $kieli),     $page_tarjous[$sivu], $iso);
      $pdf_tarjous->draw_text(310, 803, t("Sivu", $kieli),     $page_tarjous[$sivu], $norm);

      //Vasen sarake
      //$pdf_tarjous->draw_rectangle(737, 20,  674, 300, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(30, 770, t("Ostaja", $kieli),   $page_tarjous[$sivu], $pieni_boldi);
      $pdf_tarjous->draw_text(30, 758, $laskurow["nimi"],       $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 748, $laskurow["nimitark"],      $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 738, $laskurow["osoite"],       $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 728, $laskurow["postino"]." ".$laskurow["postitp"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 718, $laskurow["maa"],         $page_tarjous[$sivu], $norm);

      //$pdf_tarjous->draw_rectangle(674, 20,  611, 300, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(30, 697, t("Toimitusosoite", $kieli),   $page_tarjous[$sivu], $pieni_boldi);
      $pdf_tarjous->draw_text(30, 685, $laskurow["toim_nimi"],     $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 675, $laskurow["toim_nimitark"],   $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 665, $laskurow["toim_osoite"],     $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 655, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(30, 645, $laskurow["toim_maa"],     $page_tarjous[$sivu], $norm);

      //Oikea sarake
      $pdf_tarjous->draw_rectangle(780, 300, 654, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(780, 300, 654, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(780, 420, 758, 580, $page_tarjous[$sivu], $rectparam);

      if ($laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"]) {
        $pdf_tarjous->draw_text(310, 772, t("Tilausnumero/Tarjous", $kieli),           $page_tarjous[$sivu], $pieni);
        $pdf_tarjous->draw_text(310, 761, $laskurow["tunnusnippu"]."/".$laskurow["tunnus"],    $page_tarjous[$sivu], $boldi);
      }
      else {
        $pdf_tarjous->draw_text(310, 772, t("Tarjous", $kieli),   $page_tarjous[$sivu], $pieni);
        $pdf_tarjous->draw_text(310, 761, $laskurow["tunnus"],    $page_tarjous[$sivu], $boldi);
      }

      $pdf_tarjous->draw_text(430, 772, t("Toimitusaika", $kieli),   $page_tarjous[$sivu], $pieni);

      $useita = "EI";

      if ($laskurow["nippu"] > 0) {
        $query = "SELECT distinct(toimaika)
                  FROM tilausrivi
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and otunnus   IN ($laskurow[nippu])
                  and toimaika != '0000-00-00'";
        $toimaikares = pupe_query($query);

        if (mysql_num_rows($toimaikares) > 1) {
          $useita = "JOO";
        }
      }

      if ($laskurow['toimaika'] == '0000-00-00') {
        $naytetaanko_toimitusaika = false;
      }
      else {
        $naytetaanko_toimitusaika = true;
      }

      if ($useita == "JOO") {
        $pdf_tarjous->draw_text(430, 761, t("Useita", $kieli),   $page_tarjous[$sivu], $norm);
      }
      elseif ($laskurow["toimvko"] != '') {

        $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        if ($naytetaanko_toimitusaika == true) {
          $pdf_tarjous->draw_text(430, 761, $taika,                 $page_tarjous[$sivu], $norm);
        }
      }
      else {
        if ($naytetaanko_toimitusaika == true) {
          $pdf_tarjous->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]),   $page_tarjous[$sivu], $norm);
        }
      }

      $pdf_tarjous->draw_rectangle(758, 300, 737, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(758, 420, 737, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 750, t("Asiakkaan Y-tunnus", $kieli),                   $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(310, 740, tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]),  $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(430, 750, t("Päiväys", $kieli),                       $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(430, 740, tv1dateconv($laskurow["muutospvm"]),                 $page_tarjous[$sivu], $norm);


      $pdf_tarjous->draw_rectangle(737, 300, 716, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 729, t("Myyjä", $kieli),                         $page_tarjous[$sivu], $pieni);

      //etsitään myyjän nimi
      $query  = "SELECT nimi
                 from kuka
                 where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
      $myyresult = pupe_query($query);
      $myyrow = mysql_fetch_array($myyresult);

      $pdf_tarjous->draw_text(310, 719, $myyrow["nimi"],                     $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(716, 300, 695, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 708, t("Toimitustapa", $kieli)." / ".t("Rahtisopimus", $kieli),     $page_tarjous[$sivu], $pieni);

      //etsitään löytyykö rahtisopimusta
      $rahtirow = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"], false, $laskurow["rahtisopimus"]);
      $pdf_tarjous->draw_text(310, 698, t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)." ".$rahtirow["rahtisopimus"],     $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(695, 300, 674, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 687, t("Toimitusehto", $kieli),     $page_tarjous[$sivu], $pieni);

      $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
      if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
      else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

      $pdf_tarjous->draw_text(310, 677, $toimehto, $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(674, 300, 654, 580, $page_tarjous[$sivu], $rectparam);

      $pdf_tarjous->draw_text(310, 667, t("Maksuehto", $kieli),  $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(310, 657, $laskurow["maksuehto"],  $page_tarjous[$sivu], $norm);
    }
    else {
      $pdf_tarjous->draw_text(310, 815, t("Tarjous", $kieli),     $page_tarjous[$sivu], $iso);
      $pdf_tarjous->draw_text(310, 803, t("Sivu", $kieli),     $page_tarjous[$sivu], $norm);

      //Vasen sarake
      //$pdf_tarjous->draw_rectangle(737, 20,  674, 300, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(50, 729, t("Ostaja", $kieli),   $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(50, 717, $laskurow["nimi"],       $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 707, $laskurow["nimitark"],      $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 697, $laskurow["osoite"],       $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 677, $laskurow["maa"],         $page_tarjous[$sivu], $norm);

      //$pdf_tarjous->draw_rectangle(674, 20,  611, 300, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(50, 656, t("Toimitusosoite", $kieli),   $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(50, 644, $laskurow["toim_nimi"],     $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 634, $laskurow["toim_nimitark"],   $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 624, $laskurow["toim_osoite"],     $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(50, 604, $laskurow["toim_maa"],     $page_tarjous[$sivu], $norm);

      //Oikea sarake
      $pdf_tarjous->draw_rectangle(800, 300, 779, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(800, 420, 779, 580, $page_tarjous[$sivu], $rectparam);

      if ($laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"]) {
        $pdf_tarjous->draw_text(310, 792, t("Tilausnumero/Tarjous", $kieli),           $page_tarjous[$sivu], $pieni);
        $pdf_tarjous->draw_text(310, 782, $laskurow["tunnusnippu"]."/".$laskurow["tunnus"],    $page_tarjous[$sivu], $boldi);
      }
      else {
        $pdf_tarjous->draw_text(310, 792, t("Tarjous", $kieli),   $page_tarjous[$sivu], $pieni);
        $pdf_tarjous->draw_text(310, 782, $laskurow["tunnus"],    $page_tarjous[$sivu], $boldi);
      }

      $pdf_tarjous->draw_text(430, 792, t("Toimitusaika", $kieli),   $page_tarjous[$sivu], $pieni);

      $useita = "EI";

      if ($laskurow["nippu"] > 0) {
        $query = "SELECT distinct(toimaika)
                  FROM tilausrivi
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and otunnus   IN ($laskurow[nippu])
                  and toimaika != '0000-00-00'";
        $toimaikares = pupe_query($query);

        if (mysql_num_rows($toimaikares) > 1) {
          $useita = "JOO";
        }
      }

      if ($laskurow['toimaika'] == '0000-00-00') {
        $naytetaanko_toimitusaika = false;
      }
      else {
        $naytetaanko_toimitusaika = true;
      }

      if ($useita == "JOO") {
        $pdf_tarjous->draw_text(430, 782, t("Useita", $kieli),   $page_tarjous[$sivu], $norm);
      }
      elseif ($laskurow["toimvko"] != '') {

        $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        if ($naytetaanko_toimitusaika == true) {
          $pdf_tarjous->draw_text(430, 782, $taika,                 $page_tarjous[$sivu], $norm);
        }
      }
      else {
        if ($naytetaanko_toimitusaika == true) {
          $pdf_tarjous->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]),   $page_tarjous[$sivu], $norm);
        }
      }

      $pdf_tarjous->draw_rectangle(779, 300, 758, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(779, 420, 758, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 771, t("Asiakkaan Y-tunnus", $kieli),                   $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(310, 761, tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]),  $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(430, 771, t("Päiväys", $kieli),                       $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(430, 761, tv1dateconv($laskurow["muutospvm"]),                 $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(758, 300, 737, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 750, t("Myyjä", $kieli),                         $page_tarjous[$sivu], $pieni);

      //etsitään myyjän nimi
      $query  = "SELECT nimi
                 from kuka
                 where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
      $myyresult = pupe_query($query);
      $myyrow = mysql_fetch_array($myyresult);

      $pdf_tarjous->draw_text(310, 740, $myyrow["nimi"],                     $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(737, 300, 716, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 729, t("Toimitustapa", $kieli)." / ".t("Rahtisopimus", $kieli),     $page_tarjous[$sivu], $pieni);

      //etsitään löytyykö rahtisopimusta
      $rahtirow = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"], false, $laskurow["rahtisopimus"]);
      $pdf_tarjous->draw_text(310, 719, t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)." ".$rahtirow["rahtisopimus"],     $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(716, 300, 695, 580, $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_text(310, 708, t("Toimitusehto", $kieli),     $page_tarjous[$sivu], $pieni);

      $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
      if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
      else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

      $pdf_tarjous->draw_text(310, 698, $toimehto, $page_tarjous[$sivu], $norm);

      $pdf_tarjous->draw_rectangle(695, 300, 674, 580, $page_tarjous[$sivu], $rectparam);

      $pdf_tarjous->draw_text(310, 687, t("Maksuehto", $kieli),  $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(310, 677, $laskurow["maksuehto"],  $page_tarjous[$sivu], $norm);

    }

    $komm = "";

    if ($kaanteinen_verotus) {
      $komm .= t("AVL 8c §", $kieli).":###".wordwrap(str_replace("\n", "\n###", t_avainsana("KAANTALVVIESTI", $kieli, "", "", "", "selitetark_2")), 90, "\n###")."\n";
    }

    if ($yhtiorow['tarjoustyyppi'] == 1) {
      if (trim($laskurow['viesti']) != '') {
        $komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
      }

      if (trim($laskurow['comments']) != '') {
        $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 90, "\n###");
      }

      if (trim($laskurow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tilausyhteyshenkilö", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
      }
    }
    else {
      if (trim($laskurow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tarjousyhteyshenkilö", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
      }

      if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
        $komm .= "\n".t("Tarjouksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
      }

      if (trim($laskurow['kohde']) != '') {
        $komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
      }

      if (trim($laskurow['viesti']) != '') {
        $komm .= "\n".t("Tarjousviite", $kieli).":###".$laskurow['viesti'];
      }

      if (trim($laskurow['comments']) != '') {
        $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 90, "\n###");
      }
    }

    if ($yhtiorow["tyomaaraystiedot_tarjouksella"] == "") {
      $query = "SELECT tyomaarays.*
                FROM lasku
                JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
                WHERE lasku.yhtio = '$kukarow[yhtio]'
                and lasku.tunnus  = '$laskurow[tunnus]'";
      $tyomres = pupe_query($query);

      if (mysql_num_rows($tyomres) > 0) {

        while ($tyomrow = mysql_fetch_array($tyomres)) {

          $al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selitetark != '' and avainsana.nakyvyys in ('K','L','J')");

          while ($al_row = mysql_fetch_array($al_res)) {

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
                  $row = mysql_fetch_array($yresult);

                  $tyomrow[$kentta] = $row["nimi"];
                }
                elseif ($kentta == "merkki") {
                  $yresult = t_avainsana("SARJANUMERON_LI", $kieli, "and avainsana.selite = 'MERKKI' and avainsana.selitetark = '".$tyomrow[$kentta]."'");

                  if (mysql_num_rows($yresult) > 0) {
                    $row = mysql_fetch_array($yresult);

                    $tyomrow[$kentta] = $row["selitetark_2"];
                  }
                }

                $komm .= "\n$al_row[selitetark]:###$tyomrow[$kentta]";
              }
            }
          }
        }

        $komm .= "\n";
      }
    }

    // Tulostetaan laskun kommentti
    if (trim($komm) != '') {
      $kommentit = explode("\n", trim($komm));

      if ($yhtiorow['tarjoustyyppi'] == 1) {
        $pohja = 525;
      }
      else {
        $pohja  = 577;
      }

      $maxoik = 0;

      foreach ($kommentit as $kommentti) {
        if (strpos($kommentti, "###") !== FALSE) {
          list($o, $v) = explode("###", $kommentti);

          $oikpos = $pdf_tarjous->strlen($o, $boldi);

          if ($oikpos > $maxoik) {
            $maxoik = $oikpos;
          }
        }
      }

      foreach ($kommentit as $kommentti) {

        if ($pohja <= 130) {
          loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $verolliset_verottomat_hinnat);

          $sivu++;

          list($page_tarjous[$sivu], $pohja) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);
        }

        if (strpos($kommentti, "###") !== false) {
          list($o, $v) = explode("###", $kommentti);

          if ($yhtiorow['tarjoustyyppi'] == 1) {
            $pdf_tarjous->draw_text(50, $pohja, trim($o), $page_tarjous[$sivu], $boldi);
            $pdf_tarjous->draw_text(50+$maxoik+12, $pohja, trim($v), $page_tarjous[$sivu], $norm);
          }
          else {
            $pdf_tarjous->draw_text(35, $pohja, trim($o), $page_tarjous[$sivu], $boldi);
            $pdf_tarjous->draw_text(35+$maxoik+5, $pohja, trim($v), $page_tarjous[$sivu], $norm);
          }
        }
        else {
          $pdf_tarjous->draw_text(35, $pohja, trim($kommentti), $page_tarjous[$sivu], $norm);
        }

        $pohja = $pohja - 10;
      }
      $kala = $pohja-30;
    }
    else {
      if ($yhtiorow['tarjoustyyppi'] == 1) {
        $kala = 465;
      }
      else {
        $kala = 560;
      }
    }

    if (class_exists("Image_Barcode") and $yhtiorow['tarjoustyyppi'] == 0) {
      //viivakoodi
      $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

      imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

      $fh = fopen($nimi, "r");
      $data = fread($fh, filesize($nimi));
      fclose($fh);

      $image = $pdf_tarjous->jfif_embed($data);
      $pdf_tarjous->image_place($image, 805, 440, $page_tarjous[$sivu]);

      system("rm -f $nimi");
    }

    return array($page_tarjous, $sivu, $kala);
  }
}

if (!function_exists('uusi_sivu_tarjous')) {
  function uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous) {
    global $yhtiorow, $kukarow, $pieni, $norm, $boldi, $pieni_boldi, $iso, $rectparam, $useita;

    $page_tarjous = $pdf_tarjous->new_page("a4");

    tulosta_logo_pdf($pdf_tarjous, $page_tarjous, $laskurow);

    if ($yhtiorow['tarjoustyyppi'] == 1) {
      $otsikko_len = $pdf_tarjous->strlen(t("TARJOUS", $kieli), $iso);
      $pdf_tarjous->draw_text((595 / 2) - ($otsikko_len / 2), 760, t("TARJOUS", $kieli),   $page_tarjous, $iso);
      $pdf_tarjous->draw_text(530, 803, t("Sivu", $kieli), $page_tarjous, $pieni);

      $pdf_tarjous->draw_text(530, 785, t("Numero", $kieli), $page_tarjous, $pieni);
      $valekala = 775;

      foreach (explode(",", $laskurow["tunnus"]) as $tun) {
        $pdf_tarjous->draw_text(530, $valekala, $tun, $page_tarjous, $pieni);
        $valekala -= 7;
      }

      $pvm_len = $pdf_tarjous->strlen(tv1dateconv($laskurow["luontiaika"]), $pieni);
      $pdf_tarjous->draw_text((595 / 2) - ($pvm_len / 2), 750, tv1dateconv($laskurow["luontiaika"]), $page_tarjous, $pieni);
    }
    else {
      $pdf_tarjous->draw_text(310, 815, t("Tarjous", $kieli),     $page_tarjous, $iso);
      $pdf_tarjous->draw_text(310, 803, t("Sivu", $kieli),     $page_tarjous, $norm);

      //Oikea sarake
      $pdf_tarjous->draw_rectangle(800, 300, 779, 580, $page_tarjous, $rectparam);
      $pdf_tarjous->draw_rectangle(800, 420, 779, 580, $page_tarjous, $rectparam);

      $pdf_tarjous->draw_text(310, 792, t("Tarjous", $kieli),     $page_tarjous, $pieni);
      $pdf_tarjous->draw_text(430, 792, t("Toimitusaika", $kieli),   $page_tarjous, $pieni);
      $pdf_tarjous->draw_text(310, 782, $laskurow["tunnus"],      $page_tarjous, $boldi);

      if ($useita == "JOO") {
        $pdf_tarjous->draw_text(430, 782, t("Useita", $kieli),   $page_tarjous, $norm);
      }
      elseif ($laskurow["toimvko"] != '') {
        $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        $pdf_tarjous->draw_text(430, 782, $taika,                 $page_tarjous, $norm);
      }
      else {
        $pdf_tarjous->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]),   $page_tarjous, $norm);
      }

      $pdf_tarjous->draw_rectangle(779, 300, 758, 580, $page_tarjous, $rectparam);
      $pdf_tarjous->draw_rectangle(779, 420, 758, 580, $page_tarjous, $rectparam);
      $pdf_tarjous->draw_text(310, 771, t("Asiakkaan Y-tunnus", $kieli),           $page_tarjous, $pieni);
      $pdf_tarjous->draw_text(310, 761, tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]),  $page_tarjous, $norm);

      $pdf_tarjous->draw_text(430, 771, t("Päiväys", $kieli),             $page_tarjous, $pieni);
      $pdf_tarjous->draw_text(430, 761, tv1dateconv($laskurow["muutospvm"]),       $page_tarjous, $norm);

      //viivakoodi
      if (class_exists("Image_Barcode")) {
        $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

        imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

        $fh = fopen($nimi, "r");
        $data = fread($fh, filesize($nimi));
        fclose($fh);

        $image = $pdf_tarjous->jfif_embed($data);
        $pdf_tarjous->image_place($image, 805, 440, $page_tarjous);

        system("rm -f $nimi");
      }
    }

    $kala = 715;

    return array($page_tarjous, $kala);
  }
}

if (!function_exists('tarjous_rivi_otsikot')) {
  function tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous, $kieli, $kala, $tuotetyyppi, $verolliset_verottomat_hinnat = "", $naytetaanko_rivihinta = '', $naytetaanko_tuoteno = '') {
    global $yhtiorow, $kukarow, $norm, $rectparam, $boldi;

    $alv = "";

    if (trim($verolliset_verottomat_hinnat) == 'verolliset') {
      $alv = t("Verollinen", $kieli);
    }
    elseif (trim($verolliset_verottomat_hinnat) == 'verottomat') {
      $alv = t("Veroton", $kieli);
    }
    elseif ($yhtiorow['tarjouksen_alv_kasittely'] == '') {
      if ($yhtiorow['alv_kasittely'] != '') {
        $alv = t("Veroton", $kieli);
      }
      else {
        $alv = t("Verollinen", $kieli);
      }
    }
    else {
      if ($yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
        $alv = t("Veroton", $kieli);
      }
      else {
        $alv = t("Verollinen", $kieli);
      }
    }

    if ($yhtiorow['tarjoustyyppi'] == 1) {

      if ($naytetaanko_tuoteno == '') {
        $pdf_tarjous->draw_text(50,  $kala+15, t("Tuotenumero", $kieli),  $page_tarjous, $boldi);
        $pdf_tarjous->draw_text(150, $kala+15, t("Tuotenimi", $kieli),    $page_tarjous, $boldi);
      }
      else {
        $pdf_tarjous->draw_text(50, $kala+15, t("Tuotenimi", $kieli),    $page_tarjous, $boldi);
      }

      $pdf_tarjous->draw_text(330, $kala+15, t("Toimitus", $kieli),    $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(380, $kala+15, t("Määrä", $kieli),      $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(438, $kala+15, t("Yksikkö", $kieli),    $page_tarjous, $boldi);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf_tarjous->draw_text(498, $kala+15, t("Hinta alv.", $kieli),    $page_tarjous, $boldi);
        $pdf_tarjous->draw_text(498, $kala+5, $alv,              $page_tarjous, $boldi);
      }

    }
    elseif ($yhtiorow['tarjoustyyppi'] == 5) {
      $pdf_tarjous->draw_text(80,  $kala+15, t("Tuotenumero", $kieli),  $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(80,  $kala+5, t("Tuotenimi", $kieli),    $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(330, $kala+15, t("Määrä", $kieli),      $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(380, $kala + 15, t("Yks.hinta", $kieli), $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(450, $kala + 15, t("Alennus", $kieli), $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(498, $kala + 15, t("Alv", $kieli), $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(537, $kala + 15, t("Rivihinta", $kieli), $page_tarjous, $boldi);
      $pdf_tarjous->draw_text(537, $kala + 5, $alv, $page_tarjous, $boldi);
    }
    else {

      if ($naytetaanko_tuoteno == '') $pdf_tarjous->draw_text(25,  $kala+15, t("Tuotenumero", $kieli),  $page_tarjous, $norm);
      $pdf_tarjous->draw_text(25,  $kala+5, t("Tuotenimi", $kieli),    $page_tarjous, $norm);

      if (trim($naytetaanko_rivihinta == 'kokonaan_ilman_hintoja')) {
        $pdf_tarjous->draw_text(450, $kala+15, t("Määrä", $kieli),      $page_tarjous, $norm);
      }
      else {
        $pdf_tarjous->draw_text(330, $kala+15, t("Määrä", $kieli),      $page_tarjous, $norm);

        if ($yhtiorow["tarjoustyyppi"] == 4) {
          $pdf_tarjous->draw_text(380, $kala + 15, t("Hinta / Yksikkö", $kieli), $page_tarjous, $norm);
        }
        else {
          $pdf_tarjous->draw_text(380, $kala + 15, t("Yks.hinta", $kieli), $page_tarjous, $norm);
          $pdf_tarjous->draw_text(450, $kala + 15, t("Alennus", $kieli), $page_tarjous, $norm);
        }
      }

      if (!in_array($yhtiorow["tarjoustyyppi"], array(2, 4))) {
        $pdf_tarjous->draw_text(498, $kala + 15, t("Alv", $kieli), $page_tarjous, $norm);
      }

      if (trim($naytetaanko_rivihinta) == '') {
        if ($yhtiorow["tarjoustyyppi"] == 4) {
          $pdf_tarjous->draw_text(543, $kala + 15, t("Hinta", $kieli), $page_tarjous, $norm);
          $pdf_tarjous->draw_text(543, $kala + 5, $alv, $page_tarjous, $norm);
        }
        else {
          $pdf_tarjous->draw_text(537, $kala + 15, t("Rivihinta", $kieli), $page_tarjous, $norm);
          $pdf_tarjous->draw_text(537, $kala + 5, $alv, $page_tarjous, $norm);
        }
      }

      if ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '') {
        if ($tuotetyyppi == "2 Työt") {
          $pdf_tarjous->draw_text(25,  $kala+25, t("Työt ja palvelut", $kieli), $page_tarjous, $boldi);
        }
        else {
          $pdf_tarjous->draw_text(25,  $kala+25, t("Osat ja tarvikkeet", $kieli), $page_tarjous, $boldi);
        }
      }
    }

    $kala -= 10;

    return $kala;
  }
}

if (!function_exists('rivi_tarjous')) {
  function rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $row, $hinnat, $asiakasrow, $verolliset_verottomat_hinnat = "", $naytetaanko_rivihinta = '', $sivu = 1, $naytetaanko_tuoteno = '') {
    global $yhtiorow, $kukarow, $norm, $pieni, $summa, $useita;

    $rivinkorkeus = 15;

    //Käännetään nimitys
    $row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys', $kieli);

    // Trimmataan
    $row["kommentti"] = trim($row["kommentti"]);


    if ($yhtiorow['tarjoustyyppi'] != 1) {
      // Tilaajan rivinumero
      if ($row["tilaajanrivinro"] != 0) {
        $row["kommentti"] .= "\n".t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";
      }

      $askomquery = "SELECT *
                     from asiakaskommentti
                     where yhtio = '{$kukarow['yhtio']}'
                     and tuoteno = '{$row['tuoteno']}'
                     and ytunnus = '{$laskurow['ytunnus']}'";
      $askomresul = pupe_query($askomquery);

      if (mysql_num_rows($askomresul) > 0) {
        while ($askomrow = mysql_fetch_assoc($askomresul)) {
          $row["kommentti"] .= "\n".$askomrow["kommentti"];
        }
      }
    }

    $row["perhe_kommentti"] = "";

    // Info tuoteperheestä
    if ($row["perheid"] > 0 and $perheid != $row["perheid"]) {
      $numrows = 0;

      $query = "SELECT vanhatunnus
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$row[otunnus]'";
      $vtunres = pupe_query($query);
      $vtunrow = mysql_fetch_array($vtunres);

      if ($vtunrow["vanhatunnus"] != 0) {
        $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                  FROM lasku use index (yhtio_vanhatunnus)
                  WHERE yhtio     = '$kukarow[yhtio]'
                  and vanhatunnus = '$vtunrow[vanhatunnus]'";
        $perheresult = pupe_query($query);
        $numrows = mysql_num_rows($perheresult);
      }

      if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
        $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                  FROM lasku use index (PRIMARY)
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[otunnus]'";
        $perheresult = pupe_query($query);
      }

      if (mysql_num_rows($perheresult) > 0) {
        $perherow = mysql_fetch_array($perheresult);

        $query = "SELECT distinct tilausrivi.tuoteno, tilausrivi.nimitys, varattu
                  FROM tilausrivi
                  JOIN tuote tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                  WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                  and tilausrivi.otunnus  in ($perherow[tunnukset])
                  and tilausrivi.perheid  = '$row[perheid]'
                  and tuote.tuotetyyppi  != 'R'
                  and tilausrivi.tyyppi  != 'V'
                  ORDER BY tilausrivi.tunnus";
        $perheresult = pupe_query($query);

        if (mysql_num_rows($perheresult) > 1) {

          while ($perherow = mysql_fetch_array($perheresult)) {

            if ($row["perhe_kommentti"] == "") {
              $row["perhe_kommentti"] .= t("Tuoteperhe", $kieli).": ";

              if ($naytetaanko_tuoteno == '') {
                $row["perhe_kommentti"] .= "{$perherow["tuoteno"]} ({$row["varattu"]}) {$perherow["nimitys"]}\n".t("Sisältää tuotteet", $kieli).": ";
              }
              else {
                $row["perhe_kommentti"] .= "{$perherow["nimitys"]} ({$row["varattu"]})\n".t("Sisältää tuotteet", $kieli).": ";
              }
            }
            else {
              if ($naytetaanko_tuoteno == '') $row["perhe_kommentti"] .= strtoupper($perherow["tuoteno"]).", ";
              else $row["perhe_kommentti"] .= "{$perherow["nimitys"]}, ";
            }
          }

          $row["perhe_kommentti"] = substr($row["perhe_kommentti"], 0, -2);
        }
      }
    }

    // Trimmataan uudestaan
    $row["kommentti"] = trim($row["kommentti"]);

    // Haetaan tuotteen myyntihinta
    $query = "SELECT round(if (isnull(hinnasto.hinta) OR hinnasto.hinta = '', round(tuote.myyntihinta / if ('$laskurow[vienti_kurssi]' = 0, 1, '$laskurow[vienti_kurssi]'), '$yhtiorow[hintapyoristys]'), hinnasto.hinta) / (1 + (if ('$yhtiorow[alv_kasittely]' = '', 0, tuote.alv) / 100)), '$yhtiorow[hintapyoristys]') myyntihinta,
              tuote.aleryhma
                   FROM tuote
                   LEFT JOIN hinnasto ON (tuote.yhtio = hinnasto.yhtio
                     AND tuote.tuoteno     = hinnasto.tuoteno
                     AND hinnasto.valkoodi = '$laskurow[valkoodi]'
                     AND hinnasto.laji     = '')
                   WHERE tuote.tuoteno     = '$row[tuoteno]'
              AND tuote.yhtio              = '$kukarow[yhtio]'
              ORDER BY IFNULL(TO_DAYS(current_date) - TO_DAYS(hinnasto.alkupvm), 9999999999999), hinnasto.valkoodi DESC, hinnasto.tunnus DESC
              LIMIT 1";
    $asresult = pupe_query($query);
    $asrow1 = mysql_fetch_array($asresult);

    //saadaan lähetteen summa menemään oikein, taaksepäin yhteensopivasti
    $row["brutto_rivihinta"]   = $asrow1["myyntihinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]);
    $row["myyntihinta"]     = $asrow1["myyntihinta"];
    $row["aleryhma"]       = $asrow1["aleryhma"];

    $perheid    = $row["perheid"];
    $piilotarivi = "";

    // Alennus kuntoon
    if ($row["netto"] != 'N' and $row["erikoisale"] > 0) {
      $row["ale1"] = (1 - (1 - $row["ale1"] / 100) * (1 - $row["erikoisale"] / 100)) * 100;
    }

    if ($row["perheid"] > 0) {

      // kyseessä on isä
      if ($row["perheid"] == $row["tunnus"]) {

        $query = "SELECT vanhatunnus
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[otunnus]'";
        $vtunres = pupe_query($query);
        $vtunrow = mysql_fetch_array($vtunres);

        if ($vtunrow["vanhatunnus"] != 0) {
          $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                    FROM lasku use index (yhtio_vanhatunnus)
                    WHERE yhtio     = '$kukarow[yhtio]'
                    and vanhatunnus = '$vtunrow[vanhatunnus]'";
          $perheresult = pupe_query($query);
          $numrows = mysql_num_rows($perheresult);
        }

        if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
          $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                    FROM lasku use index (PRIMARY)
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$row[otunnus]'";
          $perheresult = pupe_query($query);
        }

        if (mysql_num_rows($perheresult) > 0) {
          $perherow = mysql_fetch_array($perheresult);

          $query_ale_lisa = generoi_alekentta('M');

          // lasketaan isätuotteen riville lapsien hinnat yhteen
          $query = "SELECT
                    round(sum(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta,
                    round(round(sum(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),'$yhtiorow[hintapyoristys]') / ($row[kpl]+$row[varattu]+$row[jt]),'$yhtiorow[hintapyoristys]') hinta,
                    round(sum(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta_verolli,
                    round(sum(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta_veroton,
                    round(round(sum(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),'$yhtiorow[hintapyoristys]') / ($row[kpl]+$row[varattu]+$row[jt]),'$yhtiorow[hintapyoristys]') hinta_verolli,
                    round(round(sum(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),'$yhtiorow[hintapyoristys]') / ($row[kpl]+$row[varattu]+$row[jt]),'$yhtiorow[hintapyoristys]') hinta_veroton
                    FROM tilausrivi
                    JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                    WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                    and tilausrivi.otunnus in ($perherow[tunnukset])
                    and tilausrivi.perheid = '$row[perheid]'";
          $riresult = pupe_query($query);
          $perherow = mysql_fetch_array($riresult);

          $row["hinta"]     = $perherow["hinta"];
          $row["rivihinta"] = $perherow["rivihinta"];
          $row["ale1"]      = round(100 * (1 - ($perherow["rivihinta"] / ($perherow["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"])))), 2);

          if ($yhtiorow['alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == 'verolliset') {
            $row["hinta"]     = $perherow["hinta_verolli"];
            $row["rivihinta"] = $perherow["rivihinta_verolli"];
          }
          elseif ($yhtiorow['alv_kasittely'] == '' and trim($verolliset_verottomat_hinnat) == 'verottomat') {
            $row["hinta"]     = $perherow["hinta_veroton"];
            $row["rivihinta"] = $perherow["rivihinta_veroton"];
          }
          elseif ($yhtiorow['tarjouksen_alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == '') {

            if ($yhtiorow['alv_kasittely'] == '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
              $row["hinta"]     = $perherow["hinta_veroton"];
              $row["rivihinta"] = $perherow["rivihinta_veroton"];
            }
            elseif ($yhtiorow['alv_kasittely'] != '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'S') {
              $row["hinta"]     = $perherow["hinta_verolli"];
              $row["rivihinta"] = $perherow["rivihinta_verolli"];
            }
          }
        }
      }
      else {
        // lapsia ei lisätä
        $piilotarivi = 1;
      }
    }

    if ($useita == "JOO") {
      $row["kommentti"] .= "\n".t("Toimitusaika", $kieli).": ".tv1dateconv($row["toimaika"]);
    }

    $row["tilkpl"]   = ($row["tilkpl"]*1);
    $row["varattu"] = ($row["kpl"]+$row["varattu"]+$row["jt"])." ".t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $row["kpl"]   = ($row["kpl"]*1);
    $kpl_rivilla   = ($row["kpl"]+$row["varattu"]+$row["jt"]);

    $sarjanutunnus  = "";

    //Haetan sarjanumeron tiedot
    if ($row["sarjanumeroseuranta"] != "") {
      if ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and $row["var2"] == "EIOST") {
        $sarjanutunnus = "ostorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and ($row['sarjanumeroseuranta'] == 'S' or $row['sarjanumeroseuranta'] == 'G')) {
        $sarjanutunnus = "myyntirivitunnus";
      }
      elseif ($row["varattu"]+$row["kpl"] < 0) {
        $sarjanutunnus = "ostorivitunnus";
      }
      else {
        $sarjanutunnus = "myyntirivitunnus";
      }

      $query = "SELECT DISTINCT sarjanumero, parasta_ennen
                FROM sarjanumeroseuranta
                WHERE yhtio      = '$kukarow[yhtio]'
                and tuoteno      = '$row[tuoteno]'
                and $sarjanutunnus='$row[tunnus]'
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

    if ($piilotarivi == "") {
      $summa += $row['rivihinta'];

      if ($yhtiorow['tarjoustyyppi'] == 1) {
        list($kala, $page_tarjous, $sivu) = tarjous_rivi_custom($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $row, $hinnat, $asiakasrow, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $sivu, $naytetaanko_tuoteno);
      }
      else {
        list($kala, $page_tarjous, $sivu) = tarjous_rivi_hinta_ale($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $row, $hinnat, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $sivu, $naytetaanko_tuoteno);
      }
    }

    return array($kala, $page_tarjous, $sivu);
  }
}

if (!function_exists('tarjous_rivi_hinta_ale')) {
  function tarjous_rivi_hinta_ale($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $row, $hinnat, $verolliset_verottomat_hinnat = "", $naytetaanko_rivihinta = '', $sivu = 1, $naytetaanko_tuoteno = '') {
    global $yhtiorow, $kukarow, $norm, $pieni, $boldi, $thumb_size_x, $thumb_size_y;

    $rivinkorkeus = 12;

    if ($yhtiorow['tarjoustyyppi'] == 5) {
      $query = "SELECT liitetiedostot.*
                FROM tuote
                JOIN liitetiedostot ON (liitetiedostot.yhtio = tuote.yhtio
                  AND liitetiedostot.liitostunnus    = tuote.tunnus
                  AND liitetiedostot.liitos          = 'tuote'
                  AND liitetiedostot.kayttotarkoitus = 'TH')
                WHERE tuote.yhtio                    = '{$kukarow['yhtio']}'
                AND tuote.tuoteno                    = '{$row['tuoteno']}'";
      $kuva_res = pupe_query($query);

      if (mysql_num_rows($kuva_res) != 0) {
        $kala -= 15;
      }
    }

    if (trim($row["perhe_kommentti"]) != '' and $yhtiorow['tarjoustyyppi'] != 5) {
      $pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],  $page_tarjous[$sivu], $norm);
      if (is_numeric($pohja)) {
        $kala = $pohja - $rivinkorkeus;
      } 
      else {
        $kala = 0;
      }
    }

    if (trim($verolliset_verottomat_hinnat) == 'verolliset') {
      $alv = $row["alv"];
    }
    elseif (trim($verolliset_verottomat_hinnat) == 'verottomat') {
      $alv = 0;
    }
    elseif ($yhtiorow['tarjouksen_alv_kasittely'] == '') {
      if ($yhtiorow['alv_kasittely'] != '') {
        $alv = 0;
      }
      else {
        $alv = $row["alv"];
      }
    }
    else {
      if ($yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
        $alv = 0;
      }
      else {
        $alv = $row["alv"];
      }
    }

    if ($naytetaanko_tuoteno == '') {
      if ($yhtiorow['tarjoustyyppi'] != 5) {
        $pdf_tarjous->draw_text(25, $kala, $row["tuoteno"], $page_tarjous[$sivu], $norm);
      }
    }

    if ($naytetaanko_rivihinta == 'kokonaan_ilman_hintoja') {
      $oikpos = $pdf_tarjous->strlen($row["varattu"], $norm);
      $pdf_tarjous->draw_text(475-$oikpos, $kala, $row["varattu"], $page_tarjous[$sivu], $norm);
    }
    else {
      $oikpos = $pdf_tarjous->strlen($row["varattu"], $norm);
      $pdf_tarjous->draw_text(355-$oikpos, $kala, $row["varattu"], $page_tarjous[$sivu], $norm);
    }

    if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja') {
      if ($yhtiorow["tarjoustyyppi"] == 4) {
        $hinta = hintapyoristys($row["rivihinta"] / $row["varattu"]);
      }
      else {
        $hinta = hintapyoristys($row["hinta"]);
      }

      $oikpos = $pdf_tarjous->strlen($hinta, $norm);
      $pdf_tarjous->draw_text(420 - $oikpos, $kala, $hinta, $page_tarjous[$sivu], $norm);
    }

    $ed_ale = array();
    $lisakala = '';

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {

      if (trim($row["ale{$alepostfix}"]) != 0) {
        if ($alepostfix > 1) {
          if (count($ed_ale) > 0) {
            $lisakala .= "+";
          }
          $lisakala .= ($row["ale{$alepostfix}"] * 1);
        }
        else {
          $lisakala .= ($row["ale{$alepostfix}"] * 1);
        }

        $ed_ale[$alepostfix] = $row["ale{$alepostfix}"];
      }
    }

    if (trim($lisakala) != '' and $yhtiorow["tarjoustyyppi"] != 4) {
      list($ff_string, $ff_font) = pdf_fontfit($lisakala, 56, $pdf_tarjous, $norm);

      $oikpos = $pdf_tarjous->strlen($ff_string, $ff_font);
      $pdf_tarjous->draw_text(482-$oikpos, $kala, $ff_string, $page_tarjous[$sivu], $norm);
    }

    if (!in_array($yhtiorow["tarjoustyyppi"], array(2, 4))) {
      if ($row["alv"] >= 600) {
        $pdf_tarjous->draw_text(500, $kala, t("K.V.", $kieli), $page_tarjous[$sivu], $norm);
      }
      elseif ($row["alv"] >= 500) {
        $pdf_tarjous->draw_text(500, $kala, t("M.V.", $kieli), $page_tarjous[$sivu], $norm);
      }
      else {
        $pdf_tarjous->draw_text(500, $kala, ($row["alv"]*1)."%", $page_tarjous[$sivu], $norm);
      }
    }

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf_tarjous->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf_tarjous->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $page_tarjous[$sivu], $norm);
    }

    if ($naytetaanko_tuoteno == '') $kala = $kala - $rivinkorkeus;

    if ($yhtiorow['tarjoustyyppi'] == 5) {
      if (mysql_num_rows($kuva_res) != 0) {
        $kuva_row = mysql_fetch_assoc($kuva_res);

        $image = $pdf_tarjous->jfif_embed($kuva_row['data']);
        $max_imagesize = 43;

        $_scale = $kuva_row['image_width'] > $kuva_row['image_height'] ? $max_imagesize / $kuva_row['image_width'] : $max_imagesize / $kuva_row['image_height'];

        $_left = 25 + (($max_imagesize - $_scale * $kuva_row['image_width']) / 2);

        $pdf_tarjous->image_place($image, $kala-15, $_left, $page_tarjous[$sivu], array('scale' => $_scale));
      }

      $pdf_tarjous->draw_text(80, $kala + 10, $row["tuoteno"], $page_tarjous[$sivu], $norm);
      $pdf_tarjous->draw_text(80, $kala, $row["nimitys"], $page_tarjous[$sivu], $norm);

      $kala -= $rivinkorkeus;

      if ($row["kommentti"] != '') {
        $pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"]+2, 80, 60, 575, $row["kommentti"], $page_tarjous[$sivu], $norm);
        if (is_numeric($pohja)) {
          $kala = $pohja - $rivinkorkeus;
        } 
        else {
          $kala = 0;
        }
      }

      if (trim($row["perhe_kommentti"]) != '') {
        $pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"], 80, 60, 575, $row["perhe_kommentti"],  $page_tarjous[$sivu], $norm);
        if (is_numeric($pohja)) {
          $kala = $pohja - $rivinkorkeus -10;
        } 
        else {
          $kala = 0;
        }      
      }
    }
    else {
      $pdf_tarjous->draw_text(25, $kala, $row["nimitys"], $page_tarjous[$sivu], $norm);
      $kala = $kala - $rivinkorkeus;
    }

    if ($row["kommentti"] != '' and $yhtiorow['tarjoustyyppi'] != 5) {
      $pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"]+2, 45, 60, 480, $row["kommentti"], $page_tarjous[$sivu], $norm);
      if (is_numeric($pohja)) {
        $kala = $pohja - $rivinkorkeus;
      } 
      else {
        $kala = 0;
      }    
    }

    $kala = $kala - 5;

    return array($kala, $page_tarjous, $sivu);
  }
}

if (!function_exists('tarjous_rivi_custom')) {
  function tarjous_rivi_custom($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $row, $hinnat, $asiakasrow, $verolliset_verottomat_hinnat = "", $naytetaanko_rivihinta = '', $sivu = 1, $naytetaanko_tuoteno = '') {
    global $yhtiorow, $kukarow, $norm, $pieni, $boldi;

    if ($laskurow['toimaika'] == '0000-00-00') {
      $naytetaanko_toimitusaika = false;
    }
    else {
      $naytetaanko_toimitusaika = true;
    }

    $rivinkorkeus = 12;

    if (trim($row["perhe_kommentti"]) != '' and $yhtiorow["tarjoustyyppi"] != 1) {
      $pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"], 150, 60, 480, $row["perhe_kommentti"],  $page_tarjous[$sivu], $norm);
      if (is_numeric($pohja)) {
        $kala = $pohja - $rivinkorkeus;
      } 
      else {
        $kala = 0;
      }    
    }

    if ($naytetaanko_tuoteno == '') {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 100, $pdf_tarjous, $boldi);
      $pdf_tarjous->draw_text(50, $kala, $ff_string, $page_tarjous[$sivu], $ff_font);
    }

    list($vv, $kk, $pp) = explode("-", $row["toimaika"]);

    $vko = date("W", mktime(0, 0, 0, $kk, $pp, $vv));

    if ($vko == date("W", mktime(0, 0, 0, 12, 31, $vv))) {
      $vko .= ' - 1';
    }
    else {
      $vko .= ' - '.(date("W", mktime(0, 0, 0, $kk, $pp, $vv)) + 1);
    }

    if ($naytetaanko_toimitusaika == true) {
      $pdf_tarjous->draw_text(330, $kala, t("Vko", $kieli)." $vko", $page_tarjous[$sivu], $norm);
    }

    $oikpos = $pdf_tarjous->strlen($row["tilkpl"], $norm);
    $pdf_tarjous->draw_text(420-$oikpos, $kala, $row["tilkpl"], $page_tarjous[$sivu], $norm);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $pdf_tarjous->draw_text(438, $kala, $omyks, $page_tarjous[$sivu], $norm);

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf_tarjous->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf_tarjous->draw_text(540-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $page_tarjous[$sivu], $norm);
    }

    list ($ff_string, $ff_font) = pdf_fontfit($row['nimitys'], 170, $pdf_tarjous, $norm);

    if ($naytetaanko_tuoteno == '') $pdf_tarjous->draw_text(150, $kala, $ff_string, $page_tarjous[$sivu], $ff_font);
    else $pdf_tarjous->draw_text(50, $kala, $ff_string, $page_tarjous[$sivu], $ff_font);

    $kala = $kala - $rivinkorkeus;

    if (is_array($page_tarjous) and trim($row['kommentti']) != '') {

      $kommentit = explode("\n", trim($row['kommentti']));

      $ed_akey = 0;
      $rpit1 = 390;
      $rpit2 = 380;
      $lause = "";

      foreach ($kommentit as $akey => $kommentti) {

        if ($akey != 0 and $akey != $ed_akey) {
          $kala -= 5;
        }

        // Jos rivikommentti on pitkä niin rivitämme sen
        if ($pdf_tarjous->strlen($kommentti, $pieni) > $rpit1) {

          $kommentti = str_replace(' ' , ' ##', $kommentti);
          $kommentti = str_replace('.' , '.##', $kommentti);
          $kommentti = str_replace(',' , ',##', $kommentti);

          $sanat = explode('##', $kommentti);
          $lause = "";

          foreach ($sanat as $sana) {

            if ($pdf_tarjous->strlen($lause, $pieni) > $rpit2) {

              if ($kala <= 110) {
                list($page_tarjous, $sivu) = loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $verolliset_verottomat_hinnat);

                $sivu++;

                list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);
                $kala = tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous[$sivu], $kieli, $kala, $tuotetyyppi, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta);
              }

              $pdf_tarjous->draw_text(150, $kala, $lause, $page_tarjous[$sivu], $pieni);
              $kala  -= 10;
              $lause = "";

            }

            $lause .= $sana;
          }

          if ($pdf_tarjous->strlen($lause, $pieni) <= $rpit2) {
            $pdf_tarjous->draw_text(150, $kala, $lause, $page_tarjous[$sivu], $pieni);
            $kala  -= 10;
          }
        }
        elseif ($pdf_tarjous->strlen($lause, $pieni) <= $rpit2) {
          if ($kala <= 110) {
            list($page_tarjous, $sivu) = loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $verolliset_verottomat_hinnat);

            $sivu++;

            list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);
            $kala = tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous[$sivu], $kieli, $kala, $tuotetyyppi, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta);
          }

          $pdf_tarjous->draw_text(150, $kala, $kommentti, $page_tarjous[$sivu], $pieni);
          $kala  -= 10;
        }

        $ed_akey = $akey;
      }
    }

    if (trim($row["perhe_kommentti"]) != '' and $yhtiorow["tarjoustyyppi"] == 1) {
      $pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"], 150, 60, 480, $row["perhe_kommentti"],  $page_tarjous[$sivu], $norm);
      if (is_numeric($pohja)) {
        $kala = $pohja - $rivinkorkeus;
      } 
      else {
        $kala = 0;
      }    
    }

    $kala = $kala - 5;

    return array($kala, $page_tarjous, $sivu);
  }
}

if (!function_exists('loppu_tarjous')) {
  function loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, $tots,
    $total_tarjous = 0, $verolliset_verottomat_hinnat = '',
    $total_brutto = 0, $kala = 0, $vikanrivin_sivu = 0, $naytetaanko_rivihinta,
    $naytetaanko_yhteissummarivi = "") {
    global $kukarow, $yhtiorow, $boldi, $pieni, $norm, $rectparam, $kaanteinen_verotus;

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
      $alhqur = "SELECT
                 puhelin,
                 fax,
                 email,
                 www,
                 vat_numero
                 FROM yhtion_toimipaikat
                 WHERE yhtio='$laskurow[yhtio]'
                 AND tunnus='$laskurow[yhtio_toimipaikka]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok  = TRUE;
        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];

        if (!empty($alhiro['vat_numero'])) {
          $y_vatnumero = $alhiro['vat_numero'];
        }
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

    if ($yhtiorow['tarjoustyyppi'] == 1) {
      $yhteystiedot_len = $pdf_tarjous->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
      $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $page_tarjous[$sivu], $pieni);

      $yhteystiedot_len = $pdf_tarjous->strlen($y_www, $pieni);
      $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $page_tarjous[$sivu], $pieni);
    }
    else {
      //Alimmat kolme laatikkoa, yhtiötietoja
      $pdf_tarjous->draw_rectangle(70, 20, 20, 580,  $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(70, 207, 20, 580,  $page_tarjous[$sivu], $rectparam);
      $pdf_tarjous->draw_rectangle(70, 394, 20, 580,  $page_tarjous[$sivu], $rectparam);

      $pdf_tarjous->draw_text(30, 55, $y_nimi,    $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(30, 45, $y_osoite,    $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(30, 35, $y_postino."  ".$y_postitp,  $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(30, 25, $y_maa,      $page_tarjous[$sivu], $pieni);

      $pdf_tarjous->draw_text(217, 55, t("Puhelin", $kieli).":",      $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(257, 55, $y_puhelin,            $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(217, 45, t("Fax", $kieli).":",      $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(257, 45, $y_fax,              $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(217, 35, t("Sähköposti", $kieli).":",    $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(257, 35, $y_email,              $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(217, 25, t("Web", $kieli).":",        $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(257, 25, $y_www,              $page_tarjous[$sivu], $pieni);

      $pdf_tarjous->draw_text(404, 55, t("Y-tunnus", $kieli).":",      $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(444, 55, tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(404, 45, t("Kotipaikka", $kieli).":",    $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(444, 45, $y_kotipaikka,            $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(404, 35, t("Alv.rek", $kieli),        $page_tarjous[$sivu], $pieni);
      $pdf_tarjous->draw_text(404, 25, $y_iban." / ".$y_swift,      $page_tarjous[$sivu], $pieni);
    }

    if ($tots == 1 and $summa != 0 and $yhtiorow['tarjoustyyppi'] == 0) {

      if ($laskurow['alv'] > 0 and $yhtiorow["alv_kasittely"] == '') {
        $pdf_tarjous->draw_text(410, 72, t("Yhteensä verollinen", $kieli).":", $page_tarjous[$sivu], $norm);
      }
      else {
        $pdf_tarjous->draw_text(410, 72, t("Yhteensä veroton", $kieli).":", $page_tarjous[$sivu], $norm);
      }

      $oikpos = $pdf_tarjous->strlen(sprintf("%.2f", $summa)." ".$laskurow["valkoodi"], $norm);
      $pdf_tarjous->draw_text(575-$oikpos, 72, sprintf("%.2f", $summa)." ".$laskurow["valkoodi"], $page_tarjous[$sivu], $norm);
    }

    if ($tots != 1) {
      $pdf_tarjous->draw_text(530, 74, t("Jatkuu", $kieli)."...", $page_tarjous[$sivu], $boldi);
    }

    if ($tots == 1) {

      if ($yhtiorow['tarjoustyyppi'] == 1) {

        if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja' and
          $naytetaanko_yhteissummarivi != "E"
        ) {
          list($page_tarjous, $sivu, $kala) = alvierittely($pdf_tarjous, $page_tarjous, $sivu, $laskurow, $total_tarjous, '', $verolliset_verottomat_hinnat, $kala);
        }

        if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja' and $kala-165 <= 60) {
          list($page_tarjous, $sivu) = loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $total_tarjous);

          $sivu++;

          $query = "SELECT * FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['liitostunnus']}'";
          $asiakas_res = pupe_query($query);
          $asiakasrow = mysql_fetch_assoc($asiakas_res);

          list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);

          $yhteystiedot_len = $pdf_tarjous->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
          $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $page_tarjous[$sivu], $pieni);

          $yhteystiedot_len = $pdf_tarjous->strlen($y_www, $pieni);
          $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $page_tarjous[$sivu], $pieni);

          $kala += 36;
        }

        if ($laskurow["olmapvm"] != "" and $laskurow["olmapvm"] != "0000-00-00") {
          $tvva = date("Y", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2), substr($laskurow["olmapvm"], 8, 2), substr($laskurow["olmapvm"], 0, 4)));
          $tkka = date("m", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2), substr($laskurow["olmapvm"], 8, 2), substr($laskurow["olmapvm"], 0, 4)));
          $tppa = date("d", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2), substr($laskurow["olmapvm"], 8, 2), substr($laskurow["olmapvm"], 0, 4)));
        }
        else {
          $tvva = date("Y", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2), substr($laskurow["muutospvm"], 8, 2)+$yhtiorow["tarjouksen_voimaika"], substr($laskurow["muutospvm"], 0, 4)));
          $tkka = date("m", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2), substr($laskurow["muutospvm"], 8, 2)+$yhtiorow["tarjouksen_voimaika"], substr($laskurow["muutospvm"], 0, 4)));
          $tppa = date("d", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2), substr($laskurow["muutospvm"], 8, 2)+$yhtiorow["tarjouksen_voimaika"], substr($laskurow["muutospvm"], 0, 4)));
        }

        $voimassa = tv1dateconv($tvva."-".$tkka."-".$tppa);

        if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja') {
          $pdf_tarjous->draw_text(50, $kala-25, t("Toimitustapa"), $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(200, $kala-25, t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli), $page_tarjous[$sivu], $norm);

          $pdf_tarjous->draw_text(50, $kala-38, t("Voimassa", $kieli), $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(200, $kala-38, t($voimassa, $kieli), $page_tarjous[$sivu], $norm);

          $pdf_tarjous->draw_text(50, $kala-51, t("Maksuehto", $kieli), $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(200, $kala-51, t($laskurow['maksuehto'], $kieli)." ".t("tai erillisen maksupostiohjelman mukaan", $kieli), $page_tarjous[$sivu], $norm);

          $pdf_tarjous->draw_text(50, $kala-64, t("Myynti- ja toimitusehdot", $kieli), $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(200, $kala-64, $yhtiorow['nimi'].t(":n yleiset kauppaehdot", $kieli)." 30.6.2014", $page_tarjous[$sivu], $norm);
          $pdf_tarjous->draw_text(200, $kala-77, $yhtiorow['nimi'].':n '.t("tilaajavastuulain mukaiset asiakirjat löydät www.tilaajavastuulaki.fi", $kieli), $page_tarjous[$sivu], $norm);

          $pdf_tarjous->draw_text(50, $kala-90, t("Salassapito", $kieli), $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(200, $kala-90, t("Tämä asiakirja liitteineen on luottamuksellinen eikä sitä saa ilman ", $kieli).$yhtiorow['nimi'].':n ', $page_tarjous[$sivu], $norm);
          $pdf_tarjous->draw_text(200, $kala-103, t("lupaa näyttää kolmannelle osapuolelle.", $kieli), $page_tarjous[$sivu], $norm);

          // Jos on paljon rivejä tehdään uusi otsikko...
          if ($kala-253 <= 230) {
            list($page_tarjous, $sivu) = loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $total_tarjous);

            $sivu++;

            $query = "SELECT * FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['liitostunnus']}'";
            $asiakas_res = pupe_query($query);
            $asiakasrow = mysql_fetch_assoc($asiakas_res);

            list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);

            $yhteystiedot_len = $pdf_tarjous->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
            $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $page_tarjous[$sivu], $pieni);

            $yhteystiedot_len = $pdf_tarjous->strlen($y_www, $pieni);
            $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $page_tarjous[$sivu], $pieni);

            $kala += 50;
          }

          $pdf_tarjous->draw_text(50, $kala-130, t("Sopimusasiakirjojen etusijajärjestys on seuraava", $kieli).':', $page_tarjous[$sivu], $pieni);
          $pdf_tarjous->draw_text(50, $kala-140, '1. '.t("Tilausvahvistus", $kieli), $page_tarjous[$sivu], $pieni);
          $pdf_tarjous->draw_text(50, $kala-150, '2. '.t("Allekirjoitettu tarjous", $kieli), $page_tarjous[$sivu], $pieni);
          $pdf_tarjous->draw_text(50, $kala-160, "3. $yhtiorow[nimi]".t(":n yleiset kauppaehdot", $kieli)." 30.6.2014", $page_tarjous[$sivu], $pieni);
          $pdf_tarjous->draw_text(50, $kala-170, "4. ".t("Rakennustuotteiden yleisiä hankinta- ja toimitusehtoja (RYHT 2000) ja betonielementtien asennusehdot", $kieli).'.', $page_tarjous[$sivu], $pieni);

          $pdf_tarjous->draw_text(50, $kala-190, t("Olen tutustunut", $kieli)." $yhtiorow[nimi]".t(":n yleisiin sopimusehtoihin ja hyväksyn ne sellaisenaan").'.', $page_tarjous[$sivu], $pieni);

          $x[0] = 50;
          $x[1] = 180;
          $y[0] = $y[1] = $kala-220;
          $pdf_tarjous->draw_line($x, $y, $page_tarjous[$sivu], $rectparam);

          //etsitään myyjän nimi
          $query  = "SELECT nimi, kuka
                     from kuka
                     where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
          $myyresult = pupe_query($query);
          $myyrow = mysql_fetch_assoc($myyresult);

          $pdf_tarjous->draw_text(50, $kala-233, $myyrow['nimi'], $page_tarjous[$sivu], $norm);
          $pdf_tarjous->draw_text(50, $kala-263, t("Myyjän allekirjoitus"), $page_tarjous[$sivu], $boldi);

          $x[0] = 280;
          $x[1] = 460;
          $y[0] = $y[1] = $kala-220;
          $pdf_tarjous->draw_line($x, $y, $page_tarjous[$sivu], $rectparam);

          $x[0] = 280;
          $x[1] = 460;
          $y[0] = $y[1] = $kala-250;
          $pdf_tarjous->draw_line($x, $y, $page_tarjous[$sivu], $rectparam);
          $pdf_tarjous->draw_text(270, $kala-263, t("Ostajan allekirjoitus ja nimenselvennys"), $page_tarjous[$sivu], $boldi);
        }

      }
      elseif ($yhtiorow['tarjoustyyppi'] == 5) {
        if ($kala > 0) {
          $kuha = $kala-35;
        }
        else {
          $kuha = 168;
        }

        $pdf_tarjous->draw_text(30,  $kuha, t("Tarjoamme teille ylläolevan tarjouksen kohteet hintaan:", $kieli),   $page_tarjous[$sivu], $boldi);

        list($page_tarjous, $sivu, $kuha) = alvierittely($pdf_tarjous, $page_tarjous, $sivu, $laskurow, $total_tarjous, '', $verolliset_verottomat_hinnat, $kuha-10);

        if ($kuha-165 <= 140) {
          list($page_tarjous, $sivu) = loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $total_tarjous);

          $sivu++;

          $query = "SELECT * FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['liitostunnus']}'";
          $asiakas_res = pupe_query($query);
          $asiakasrow = mysql_fetch_assoc($asiakas_res);

          list($page_tarjous[$sivu], $kuha) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);

          $yhteystiedot_len = $pdf_tarjous->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
          $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $page_tarjous[$sivu], $pieni);

          $yhteystiedot_len = $pdf_tarjous->strlen($y_www, $pieni);
          $pdf_tarjous->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $page_tarjous[$sivu], $pieni);

          $kala += 36;
        }

        $result = t_avainsana('TARJOUS_VAKIOTE', $kieli);
        $vakioteksti_row = mysql_fetch_assoc($result);
        $vakioteksti = $vakioteksti_row['selitetark'];

        if (empty($vakioteksti)) {
          if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja') {
            if (trim($verolliset_verottomat_hinnat) == 'verolliset') {
              $vakioteksti = t("Hinnat sisältävät arvonlisäveron.", $kieli)." ";
            }
            elseif (trim($verolliset_verottomat_hinnat) == 'verottomat') {
              $vakioteksti = t("Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $kieli)." ";
            }
            elseif ($yhtiorow['tarjouksen_alv_kasittely'] == '') {
              if ($yhtiorow['alv_kasittely'] != '') {
                $vakioteksti = t("Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $kieli)." ";
              }
              else {
                $vakioteksti = t("Hinnat sisältävät arvonlisäveron.", $kieli)." ";
              }
            }
            else {
              if ($yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
                $vakioteksti = t("Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $kieli)." ";
              }
              else {
                $vakioteksti = t("Hinnat sisältävät arvonlisäveron.", $kieli)." ";
              }
            }
          }

          if ($laskurow["rahtivapaa"] == "") {
            $vakioteksti .= t("Lisäksi veloitamme mahdollisesta toimituksesta aiheutuneet kulut.", $kieli);
          }
        }

        $_y = $kuha - 60;

        if (strlen($vakioteksti) > 110) {
          $vakioteksti = wordwrap($vakioteksti, 110, "\n");
          foreach (explode("\n", $vakioteksti) as $teksi) {
            $pdf_tarjous->draw_text(30,  $_y, $teksi, $page_tarjous[$sivu], $norm);
            $_y = $_y - 10;
          }
        }
        else {
          $pdf_tarjous->draw_text(30,  $_y, $vakioteksti, $page_tarjous[$sivu], $norm);
          $_y = $_y - 10;
        }

        $kuha = $_y;

        if ($laskurow["olmapvm"] != "" and $laskurow["olmapvm"] != "0000-00-00") {
          $tvva = date("Y", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2),
              substr($laskurow["olmapvm"], 8, 2),
              substr($laskurow["olmapvm"], 0, 4)));
          $tkka = date("m", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2),
              substr($laskurow["olmapvm"], 8, 2),
              substr($laskurow["olmapvm"], 0, 4)));
          $tppa = date("d", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2),
              substr($laskurow["olmapvm"], 8, 2),
              substr($laskurow["olmapvm"], 0, 4)));
        }
        else {
          $tvva = date("Y", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2),
              substr($laskurow["muutospvm"], 8, 2) +
              $yhtiorow["tarjouksen_voimaika"],
              substr($laskurow["muutospvm"], 0, 4)));
          $tkka = date("m", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2),
              substr($laskurow["muutospvm"], 8, 2) +
              $yhtiorow["tarjouksen_voimaika"],
              substr($laskurow["muutospvm"], 0, 4)));
          $tppa = date("d", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2),
              substr($laskurow["muutospvm"], 8, 2) +
              $yhtiorow["tarjouksen_voimaika"],
              substr($laskurow["muutospvm"], 0, 4)));
        }

        $voimassa = tv1dateconv($tvva . "-" . $tkka . "-" . $tppa);

        $pdf_tarjous->draw_text(30, $kuha,
          t("Tarjous on voimassa %s saakka", $kieli, $voimassa) . ". " .
          t("Maksuehtomme on", $kieli) . " " .
          trim($laskurow["maksuehto"]) . ".", $page_tarjous[$sivu],
          $norm);

        $kuha -= 30;

        $pdf_tarjous->draw_text(30, $kuha,
          t("Toivomme tarjouksemme kiinnostavan teitä ja johtavan " .
            "tilaukseen", $kieli) . "!", $page_tarjous[$sivu], $boldi);

        $kuha -= 30;

        $pdf_tarjous->draw_text(30, $kuha, t("Yhteistyöterveisin", $kieli) . ",",
          $page_tarjous[$sivu], $boldi);

        $query  = "SELECT *
                   from kuka
                   where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
        $myyresult = pupe_query($query);

        if (mysql_num_rows($myyresult) == 1) {
          $myyrow = mysql_fetch_array($myyresult);
        }
        else {
          $myyrow = $kukarow;
        }

        $kuha -= 10;

        $pdf_tarjous->draw_text(30, $kuha, $myyrow["nimi"], $page_tarjous[$sivu], $boldi);
        $pdf_tarjous->draw_text(217, $kuha, t("Puh", $kieli) . ": " . $myyrow['puhno'],
          $page_tarjous[$sivu], $boldi);
        $pdf_tarjous->draw_text(404, $kuha, "Email: $myyrow[eposti]", $page_tarjous[$sivu],
          $boldi);
      }
      else {

        if ($kala > 0) {
          $kuha = $kala;
        }
        else {
          $kuha = 168;
        }

        if ($vikanrivin_sivu != $sivu and $naytetaanko_rivihinta != 'kokonaan_ilman_hintoja' and
          $naytetaanko_yhteissummarivi != "E"
        ) {
          $pdf_tarjous->draw_text(30,  74, t("Tarjoamme teille ylläolevan tarjouksen kohteet hintaan:", $kieli),   $page_tarjous[$vikanrivin_sivu], $boldi);
          $oikpos = $pdf_tarjous->strlen(sprintf("%.2f", $total_tarjous)." ".$laskurow["valkoodi"], $boldi);
          $pdf_tarjous->draw_text(380-$oikpos, 74, sprintf("%.2f", $total_tarjous)." ".$laskurow["valkoodi"], $page_tarjous[$vikanrivin_sivu], $boldi);
        }

        if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja' and
          $naytetaanko_yhteissummarivi != "E"
        ) {
          $pdf_tarjous->draw_text(30,  $kuha,  t("Tarjoamme teille ylläolevan tarjouksen kohteet hintaan:", $kieli),   $page_tarjous[$sivu], $boldi);

          $oikpos = $pdf_tarjous->strlen(sprintf("%.2f", $total_tarjous)." ".$laskurow["valkoodi"], $boldi);
          $pdf_tarjous->draw_text(575-$oikpos, $kuha, sprintf("%.2f", $total_tarjous)." ".$laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);
        }

        $result = t_avainsana('TARJOUS_VAKIOTE', $kieli);
        $vakioteksti_row = mysql_fetch_assoc($result);
        $vakioteksti = $vakioteksti_row['selitetark'];

        if ($vakioteksti == "") {

          $veroteksti = "";

          if ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja') {
            if ($laskurow["vienti"] != "") {
              $veroteksti = ""; // Laitetaan tyhjänä
            }
            elseif ($kaanteinen_verotus === TRUE) {
              $veroteksti = ""; // En keksi mitään mitä tässä tapauksessa pitäisi kirjottaa :/
            }
            elseif (trim($verolliset_verottomat_hinnat) == 'verolliset') {
              $veroteksti = t("Hinnat sisältävät arvonlisäveron.", $kieli)." ";
            }
            elseif (trim($verolliset_verottomat_hinnat) == 'verottomat') {
              $veroteksti = t("Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $kieli)." ";
            }
            elseif ($yhtiorow['tarjouksen_alv_kasittely'] == '') {
              if ($yhtiorow['alv_kasittely'] != '') {
                $veroteksti = t("Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $kieli)." ";
              }
              else {
                $veroteksti = t("Hinnat sisältävät arvonlisäveron.", $kieli)." ";
              }
            }
            else {
              if ($yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
                $veroteksti = t("Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $kieli)." ";
              }
              else {
                $veroteksti = t("Hinnat sisältävät arvonlisäveron.", $kieli)." ";
              }
            }
          }

          if ($yhtiorow["tarjoustyyppi"] != 3 and $laskurow["rahtivapaa"] == "") {
            $pdf_tarjous->draw_text(30,  $kuha-24, $veroteksti.t("Lisäksi veloitamme mahdollisesta toimituksesta aiheutuneet kulut.", $kieli), $page_tarjous[$sivu], $norm);
          }
          else {
            $pdf_tarjous->draw_text(30,  $kuha-24, $veroteksti, $page_tarjous[$sivu], $norm);
          }

          if ($yhtiorow["tarjoustyyppi"] != 3) {
            $pdf_tarjous->draw_text(30, $kuha - 36,
              t("Tavarat ovat %s:n omaisuutta kunnes ne ovat " .
                "kokonaisuudessaan maksettu.", $kieli, $yhtiorow["nimi"]),
              $page_tarjous[$sivu], $norm);

            if ($laskurow["olmapvm"] != "" and $laskurow["olmapvm"] != "0000-00-00") {
              $tvva = date("Y", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2),
                  substr($laskurow["olmapvm"], 8, 2),
                  substr($laskurow["olmapvm"], 0, 4)));
              $tkka = date("m", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2),
                  substr($laskurow["olmapvm"], 8, 2),
                  substr($laskurow["olmapvm"], 0, 4)));
              $tppa = date("d", mktime(0, 0, 0, substr($laskurow["olmapvm"], 5, 2),
                  substr($laskurow["olmapvm"], 8, 2),
                  substr($laskurow["olmapvm"], 0, 4)));
            }
            else {
              $tvva = date("Y", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2),
                  substr($laskurow["muutospvm"], 8, 2) +
                  $yhtiorow["tarjouksen_voimaika"],
                  substr($laskurow["muutospvm"], 0, 4)));
              $tkka = date("m", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2),
                  substr($laskurow["muutospvm"], 8, 2) +
                  $yhtiorow["tarjouksen_voimaika"],
                  substr($laskurow["muutospvm"], 0, 4)));
              $tppa = date("d", mktime(0, 0, 0, substr($laskurow["muutospvm"], 5, 2),
                  substr($laskurow["muutospvm"], 8, 2) +
                  $yhtiorow["tarjouksen_voimaika"],
                  substr($laskurow["muutospvm"], 0, 4)));
            }

            $voimassa = tv1dateconv($tvva . "-" . $tkka . "-" . $tppa);

            $pdf_tarjous->draw_text(30, $kuha - 48,
              t("Tarjous on voimassa %s saakka", $kieli, $voimassa) . ". " .
              t("Maksuehtomme on", $kieli) . " " .
              trim($laskurow["maksuehto"]) . ".", $page_tarjous[$sivu],
              $norm);
          }
        }
        else {
          if (strlen($vakioteksti) > 110) {
            $vakioteksti = wordwrap($vakioteksti, 110, "\n");
            $_y = $kuha-24;
            foreach (explode("\n", $vakioteksti) as $teksi) {
              $pdf_tarjous->draw_text(30,  $_y, $teksi, $page_tarjous[$sivu], $norm);
              $_y = $_y - 10;
            }

            $kuha = $_y;
          }
          else {
            $pdf_tarjous->draw_text(30,  $kuha-24, $vakioteksti, $page_tarjous[$sivu], $norm);
          }
        }

        if ($yhtiorow["tarjoustyyppi"] == 3) {
          $pdf_tarjous->draw_text(30, $kuha - 50,
            t("Toivomme tarjouksemme kiinnostavan teitä ja johtavan " .
              "tilaukseen", $kieli) . "!", $page_tarjous[$sivu], $boldi);

          $pdf_tarjous->draw_text(30, $kuha - 65, t("Terveisin", $kieli) . ",",
            $page_tarjous[$sivu], $boldi);
        }
        else {
          $pdf_tarjous->draw_text(30, $kuha - 65,
            t("Toivomme tarjouksemme kiinnostavan teitä ja johtavan " .
              "tilaukseen", $kieli) . "!", $page_tarjous[$sivu], $boldi);

          $pdf_tarjous->draw_text(30, $kuha - 80, t("Yhteistyöterveisin", $kieli) . ",",
            $page_tarjous[$sivu], $boldi);
        }

        $query  = "SELECT *
                   from kuka
                   where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
        $myyresult = pupe_query($query);

        if (mysql_num_rows($myyresult) == 1) {
          $myyrow = mysql_fetch_array($myyresult);
        }
        else {
          $myyrow = $kukarow;
        }

        if ($yhtiorow["tarjoustyyppi"] == 3) {
          $pdf_tarjous->draw_text(30, $kuha - 80, $myyrow["nimi"], $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(30, $kuha - 93, t("Puh", $kieli) . ": " . $myyrow['puhno'],
            $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(30, $kuha - 106, "Email: $myyrow[eposti]", $page_tarjous[$sivu],
            $boldi);
        }
        else {
          $pdf_tarjous->draw_text(30, $kuha - 93, $myyrow["nimi"], $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(217, $kuha - 93, t("Puh", $kieli) . ": " . $myyrow['puhno'],
            $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(404, $kuha - 93, "Email: $myyrow[eposti]", $page_tarjous[$sivu],
            $boldi);
        }

        if ($yhtiorow['lomakkeiden_allekirjoitus'] == 'X') {
          $allekirjoitus_query = "SELECT nimi,
                                  asema
                                  FROM kuka
                                  WHERE yhtio = '{$kukarow['yhtio']}'
                                  AND tunnus  = '{$laskurow['allekirjoittaja']}'";
          $allekirjoitus_result = pupe_query($allekirjoitus_query);
          $allekirjoitus_row = mysql_fetch_assoc($allekirjoitus_result);

          $pdf_tarjous->draw_text(30, $kuha-133, $allekirjoitus_row['nimi'], $page_tarjous[$sivu], $boldi);
          $pdf_tarjous->draw_text(30, $kuha-143, $allekirjoitus_row['asema'], $page_tarjous[$sivu], $boldi);
        }
      }
    }

    return array($page_tarjous, $sivu);
  }
}

if (!function_exists('print_pdf_tarjous')) {
  function print_pdf_tarjous($pdf_tarjous, $laskurow, $komento, $tee, $liita_tuotetiedot = "") {
    global $yhtiorow, $kukarow, $mista;

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
        if ($liita_tuotetiedot == "Y") {
          $lisaliitteet = hae_tuotetieto_liitteet($laskurow["tunnus"]);
        }

        require "inc/sahkoposti.inc";
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
  }
}

if (!function_exists('tulosta_tarjous')) {
  function tulosta_tarjous($otunnus, $komento, $kieli = "", $tee = "", $hinnat = "",
    $verolliset_verottomat_hinnat = "", $naytetaanko_rivihinta = '',
    $naytetaanko_tuoteno = '', $liita_tuotetiedot = "", $naytetaanko_yhteissummarivi = "") {
    global $yhtiorow, $kukarow, $norm, $pieni, $kala, $mista, $kaanteinen_verotus, $palvelin2;

    if ($komento == "-88") {
      js_openFormInNewWindow();

      echo "<br><form id='tulostakopioform_tj_$otunnus' name='tulostakopioform_tj_$otunnus' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
          <input type='hidden' name='otunnus' value='$otunnus'>
          <input type='hidden' name='toim' value='TARJOUS'>
          <input type='hidden' name='kieli' value='$kieli'>
          <input type='hidden' name='hinnat' value='$hinnat'>
          <input type='hidden' name='verolliset_verottomat_hinnat' value='$verolliset_verottomat_hinnat'>
          <input type='hidden' name='naytetaanko_rivihinta' value='$naytetaanko_rivihinta'>
          <input type='hidden' name='naytetaanko_tuoteno' value='$naytetaanko_tuoteno'>
          <input type='hidden' name='liita_tuotetiedot' value='$liita_tuotetiedot'>
          <input type='hidden' name='naytetaanko_yhteissummarivi' value='$naytetaanko_yhteissummarivi'>
          <input type='hidden' name='tee' value='NAYTATILAUS'>
          <input type='submit' value='".t("Näytä tarjous").": $otunnus' onClick=\"js_openFormInNewWindow('tulostakopioform_tj_$otunnus', ''); return false;\"></form><br>";

      $GLOBALS["lopetus"] = "";
      return;
    }

    // Haetaan laskun tiedot
    $query = "SELECT *, lasku.laatija laatija, lasku.luontiaika luontiaika, lasku.muutospvm muutospvm
              FROM lasku
              LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              and lasku.tunnus  IN ($otunnus)
              ORDER BY lasku.tunnus
              LIMIT 1";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_assoc($result);

    $laskurow['tunnus'] = $otunnus;

    $querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
    $kielresult = pupe_query($querykiel);
    $kielrow = mysql_fetch_array($kielresult);
    if ($kielrow['kieli'] != '') {
      $kieli = strtolower($kielrow['kieli']);
    }

    // Vientitilauksilla AINA verottomat
    if ($laskurow["vienti"] != "") {
      $verolliset_verottomat_hinnat = "verottomat";
    }

    // haetaan maksuehdon tiedot
    $query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $result = pupe_query($query);
    $masrow = mysql_fetch_array($result);

    //maksuehto tekstinä
    $laskurow["maksuehto"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

    $query  = "SELECT *
               FROM asiakas
               WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
    $result = pupe_query($query);
    $asiakasrow = mysql_fetch_array($result);

    //oletuksia
    $total       = 0;
    $sivu       = 1;
    $page_tarjous   = array();

    $pdf_tarjous = new pdffile;

    $pdf_tarjous->set_default('margin-top',   20);
    $pdf_tarjous->set_default('margin-bottom', 0);
    $pdf_tarjous->set_default('margin-left',   0);
    $pdf_tarjous->set_default('margin-right',  0);

    // Rivit
    $sorttauskentta = generoi_sorttauskentta($yhtiorow["tarjouksen_jarjestys"]);

    if ($yhtiorow["tarjouksen_palvelutjatuottet"] == "E" and $yhtiorow['tarjoustyyppi'] != 1) $pjat_sortlisa = "tuotetyyppi,";
    else $pjat_sortlisa = "";

    $query_ale_lisa = generoi_alekentta('M');

    $query = "SELECT tilausrivi.*,
              round(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}), '$yhtiorow[hintapyoristys]') hinta,
              round(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, '$yhtiorow[hintapyoristys]') rivihinta,
              round(tilausrivi.hinta / if({$laskurow['vienti_kurssi']} = 0, 1, {$laskurow['vienti_kurssi']}) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl), '$yhtiorow[hintapyoristys]') bruttorivihinta,
              tilausrivin_lisatiedot.positio,
              $sorttauskentta,
              if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
              if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
              tuote.sarjanumeroseuranta
              FROM tilausrivi
              JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              WHERE tilausrivi.otunnus in ($laskurow[tunnus])
              and tilausrivi.yhtio     = '$kukarow[yhtio]'
              and tilausrivi.tyyppi    in  ('L','T','U')
              ORDER BY $pjat_sortlisa sorttauskentta $yhtiorow[tarjouksen_jarjestys_suunta], tilausrivi.tunnus";
    $riresult = pupe_query($query);

    $row = mysql_fetch_assoc($riresult);
    $tuotetyyppi = $row['tuotetyyppi'];
    mysql_data_seek($riresult, 0);

    $kaanteinen_verotus = FALSE;

    while ($row = mysql_fetch_assoc($riresult)) {
      if ($row["alv"] >= 600) {
        $kaanteinen_verotus = TRUE;
        break;
      }
    }
    mysql_data_seek($riresult, 0);

    list($page_tarjous, $sivu, $kala) = alku_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous, $kaanteinen_verotus, $page_tarjous, $sivu, $verolliset_verottomat_hinnat);

    $kala -= 20;
    $kala = tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous[$sivu], $kieli, $kala, $tuotetyyppi, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $naytetaanko_tuoteno);

    $edtuotetyyppi = $tuotetyyppi;

    while ($row = mysql_fetch_array($riresult)) {

      $tuotetyyppi = $row['tuotetyyppi'];

      //$yhtiorow['alv_kasittely'] == ''         - Verolliset myyntihinnat
      //$yhtiorow['alv_kasittely'] != ''         - Verottomat myyntihinnat
      //$yhtiorow['tarjouksen_alv_kasittely'] == ''  - Yhtiön oletus
      //$yhtiorow['tarjouksen_alv_kasittely'] == 'S'  - Tuotteiden myyntihinnat sisältävät arvonlisäveron
      //$yhtiorow['tarjouksen_alv_kasittely'] == 'X'  - Tuotteiden myyntihinnat ovat arvonlisäverottomia

      if ($yhtiorow['alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == 'verolliset') {
        $row["hinta"] = $row["hinta"] * (1+$row["alv"]/100);
        $row["rivihinta"] = $row["rivihinta"] * (1+$row["alv"]/100);
      }
      elseif ($yhtiorow['alv_kasittely'] == '' and trim($verolliset_verottomat_hinnat) == 'verottomat') {
        $row["hinta"] = $row["hinta"] / (1+$row["alv"]/100);
        $row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
      }
      elseif ($yhtiorow['tarjouksen_alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == '') {

        if ($yhtiorow['alv_kasittely'] == '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
          $row["hinta"] = $row["hinta"] / (1+$row["alv"]/100);
          $row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
        }
        elseif ($yhtiorow['alv_kasittely'] != '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'S') {
          $row["hinta"] = $row["hinta"] * (1+$row["alv"]/100);
          $row["rivihinta"] = $row["rivihinta"] * (1+$row["alv"]/100);
        }
      }

      // Mahtuuko rivi tälle sivulle
      $kala_nyt = $kala;

      if ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '' and $edtuotetyyppi != $tuotetyyppi and $yhtiorow['tarjoustyyppi'] != 1) {
        $kala -= 30;
      }

      $devnull = NULL;

      list($kala, $devnull_1, $devnull_2) = rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $devnull, $row, $hinnat, $asiakasrow, $verolliset_verottomat_hinnat, $sivu, $naytetaanko_tuoteno);

      // Jos on paljon rivejä tehdään uusi otsikko...
      if ($kala <= 85) {
        list($page_tarjous, $sivu) =
          loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0,
          $verolliset_verottomat_hinnat, 0, 0, 0, $naytetaanko_rivihinta,
          $naytetaanko_yhteissummarivi);

        $sivu++;

        list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);
        $kala = tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous[$sivu], $kieli, $kala, $tuotetyyppi, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $naytetaanko_tuoteno);

      }
      else {
        $kala = $kala_nyt;
      }

      if ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '' and $edtuotetyyppi != $tuotetyyppi and $yhtiorow['tarjoustyyppi'] != 1) {
        $kala -= 30;
        $kala = tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous[$sivu], $kieli, $kala, $tuotetyyppi, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $naytetaanko_tuoteno);
      }

      list($kala, $page_tarjous, $sivu) = rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $row, $hinnat, $asiakasrow, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $sivu, $naytetaanko_tuoteno);

      if ($row["hinta"] != 0 and $hinnat == "BR") {
        $total += $row["bruttorivihinta"];
      }
      else {
        $total += $row["rivihinta"];
      }

      $edtuotetyyppi = $tuotetyyppi;
    }

    // Millä sivulla vika rivi on?
    $vikanrivin_sivu = $sivu;

    $total_tarjous  = $total;

    if (($yhtiorow['tarjoustyyppi'] != 1 or ($naytetaanko_rivihinta != 'kokonaan_ilman_hintoja' and $yhtiorow['tarjoustyyppi'] == 1)) and $kala <= 170) {
      list($page_tarjous, $sivu) =
        loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, 0, 0,
        $verolliset_verottomat_hinnat, 0, 0, 0, $naytetaanko_rivihinta,
        $naytetaanko_yhteissummarivi);

      $sivu++;

      list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);
    }

    // Lopputekstit
    list($page_tarjous, $sivu) =
      loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 1, $total_tarjous,
      $verolliset_verottomat_hinnat, $total_brutto, $kala,
      $vikanrivin_sivu, $naytetaanko_rivihinta, $naytetaanko_yhteissummarivi);

    // Piirretään sivut
    if ($yhtiorow['tarjoustyyppi'] == 1) {
      for ($pp=1; $pp<=count($page_tarjous); $pp++) {
        $pdf_tarjous->draw_text(555, 803, "$pp / $sivu", $page_tarjous[$pp], $pieni);
      }
    }
    else {
      for ($pp=1; $pp<=count($page_tarjous); $pp++) {
        $pdf_tarjous->draw_text(330, 803, "$pp / $sivu", $page_tarjous[$pp], $norm);
      }
    }

    // Tulostetaan sivu
    print_pdf_tarjous ($pdf_tarjous, $laskurow, $komento, $tee, $liita_tuotetiedot);
  }
}

if (!function_exists('alvierittely')) {
  function alvierittely($pdf_tarjous, $page_tarjous, $sivu, $laskurow, $total_tarjous=0, $kassa_ale = '', $verolliset_verottomat_hinnat, $kala) {
    global $yhtiorow, $kukarow, $toim, $rectparam, $norm, $pieni, $summa, $arvo, $kieli, $boldi;

    if ($kala <= 150) {

      if ($kassa_ale == '') {
        list($page_tarjous, $sivu) = loppu_tarjous($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $sivu, 0, $total_tarjous);
      }

      $query = "SELECT * FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['liitostunnus']}'";
      $asiakas_res = pupe_query($query);
      $asiakasrow = mysql_fetch_assoc($asiakas_res);

      $sivu++;
      list($page_tarjous[$sivu], $kala) = uusi_sivu_tarjous($laskurow, $asiakasrow, $kieli, $pdf_tarjous);
    }

    $kassa_ale_lisa = '';
    $kassa_ale_lisateksti = '';

    if ($kassa_ale != '') {
      $kassa_ale = (float) $kassa_ale;
      $kassa_ale_lisa = " * (1-$kassa_ale/100) ";

      $kala -=10;
      $pdf_tarjous->draw_text(350, $kala, t("Kassa-alennuksella", $kieli), $page_tarjous[$sivu], $boldi);
    }

    if ($toim == 'PROFORMA' or in_array($yhtiorow['tarjoustyyppi'], array(1, 5))) {
      $where = " otunnus in ($laskurow[tunnus]) ";
    }
    else {
      $where = " uusiotunnus = '$laskurow[tunnus]' ";
    }

    $varakala = $kala;

    //Haetaan kaikki alvikannat riveiltä
    $alvquery = "SELECT distinct alv
                 FROM tilausrivi
                 WHERE $where
                 and yhtio  = '$kukarow[yhtio]'
                 and tyyppi IN ('L','T','U')
                 ORDER BY alv";
    $alvresult = pupe_query($alvquery);
    $kala -= 10;

    $alvrivihinta = 0;
    $veron_maara = 0;

    $query_ale_lisa = generoi_alekentta('M');

    while ($alvrow = mysql_fetch_assoc($alvresult)) {

      // Valuuttajutut kuntoon
      if ($alvrow["alv"] >= 500 and $toim != 'PROFORMA') {
        $aquery = "SELECT
                   sum(0) alvrivihinta,
                   sum(round(tilausrivi.rivihinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1),2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi IN ('L','T','U')";
      }
      elseif ($alvrow["alv"] >= 500) {
        $aquery = "SELECT
                   sum(0) alvrivihinta,
                   sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) * tilausrivi.varattu * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi IN ('L','T','U')";
      }
      elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
        $aquery = "SELECT
                   round(sum(tilausrivi.hinta $kassa_ale_lisa / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl+tilausrivi.jt) * {$query_ale_lisa} $kassa_ale_lisa * (tilausrivi.alv / 100)),2) alvrivihinta,
                   sum(round(tilausrivi.hinta $kassa_ale_lisa / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl+tilausrivi.jt) * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi IN ('L','T','U')";
      }
      elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $aquery = "SELECT
                   round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                   sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus in ($laskurow[tunnus]) and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi IN ('L','T','U')";
      }
      else {
        $aquery = "SELECT
                   round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                   sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi IN ('L','T','U')";
      }
      $aresult = pupe_query($aquery);
      $arow = mysql_fetch_assoc($aresult);

      $alvrivihinta += $arow["rivihinta"];
      $veron_maara += $arow['alvrivihinta'];

      $pdf_tarjous->draw_text(350, $kala, t("Arvonlisävero", $kieli)." (".sprintf('%.2f', $arow["rivihinta"])."): ", $page_tarjous[$sivu], $boldi);
      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $arow["alvrivihinta"]), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $arow["alvrivihinta"]), $page_tarjous[$sivu], $boldi);

      if ($alvrow["alv"] >= 600) {
        $pdf_tarjous->draw_text(550, $kala, t("K.V.", $kieli), $page_tarjous[$sivu], $boldi);
      }
      elseif ($alvrow["alv"] >= 500) {
        $pdf_tarjous->draw_text(550, $kala, t("M.V.", $kieli), $page_tarjous[$sivu], $boldi);
      }
      else {
        $pdf_tarjous->draw_text(550, $kala, ($alvrow["alv"]*1)."%", $page_tarjous[$sivu], $boldi);
      }

      $kala -= 30;
    }

    //loppusummat vain vikalle sivulle
    $pdf_tarjous->draw_text(350, $varakala, t("Veroton arvo yhteensä", $kieli).":", $page_tarjous[$sivu], $boldi);

    if ($toim == 'PROFORMA') {
      $alvrivihinta = $kassa_ale != '' ? $alvrivihinta * (1-$kassa_ale/100) : $alvrivihinta;

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $alvrivihinta), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $varakala, sprintf('%.2f', $alvrivihinta), $page_tarjous[$sivu], $boldi);
    }
    elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $arvo_valuutassa = $kassa_ale != '' ? $laskurow["arvo_valuutassa"] * (1-$kassa_ale/100) : $laskurow["arvo_valuutassa"];

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $arvo_valuutassa), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $varakala, sprintf('%.2f', $arvo_valuutassa), $page_tarjous[$sivu], $boldi);
    }
    else {
      $alvrivihinta = $kassa_ale != '' ? $alvrivihinta * (1-$kassa_ale/100) : $alvrivihinta;

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $alvrivihinta), $norm);
      $pdf_tarjous->draw_text(540-$oikpos, $varakala, sprintf('%.2f', $alvrivihinta), $page_tarjous[$sivu], $boldi);
    }

    $pdf_tarjous->draw_text(550, $varakala, $laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);

    if ($yhtiorow['tarjoustyyppi'] == 1 or $yhtiorow['tarjoustyyppi'] == 5) {

      if ($yhtiorow['tarjoustyyppi'] == 1) $kala -= 20;

      //$yhtiorow['alv_kasittely'] == ''         - Verolliset myyntihinnat
      //$yhtiorow['alv_kasittely'] != ''         - Verottomat myyntihinnat
      //$yhtiorow['tarjouksen_alv_kasittely'] == ''  - Yhtiön oletus
      //$yhtiorow['tarjouksen_alv_kasittely'] == 'S'  - Tuotteiden myyntihinnat sisältävät arvonlisäveron
      //$yhtiorow['tarjouksen_alv_kasittely'] == 'X'  - Tuotteiden myyntihinnat ovat arvonlisäverottomia

      if ($yhtiorow['alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == 'verolliset') {

      }
      elseif ($yhtiorow['alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == 'verottomat') {
        $total_tarjous += $veron_maara;
      }
      elseif ($yhtiorow['alv_kasittely'] == '' and trim($verolliset_verottomat_hinnat) == 'verottomat') {
        $total_tarjous += $veron_maara;
      }
      elseif ($yhtiorow['alv_kasittely'] == '' and trim($verolliset_verottomat_hinnat) == 'verolliset') {
      }
      elseif ($yhtiorow['tarjouksen_alv_kasittely'] != '' and trim($verolliset_verottomat_hinnat) == '') {

        if ($yhtiorow['alv_kasittely'] == '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
          $total_tarjous += $veron_maara;
        }
        elseif ($yhtiorow['alv_kasittely'] != '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'S') {
        }
        elseif ($yhtiorow['alv_kasittely'] != '' and $yhtiorow['tarjouksen_alv_kasittely'] == 'X') {
          $total_tarjous += $veron_maara;
        }

      }
      elseif ($yhtiorow['alv_kasittely'] != '') {
        $total_tarjous += $veron_maara;
      }
    }

    if ($yhtiorow['tarjoustyyppi'] == 5 or $toim == 'TARJOUS') {
      $_lasku_vai_tarjoustxt = "Tarjous";
    }
    else {
      $_lasku_vai_tarjoustxt = "Lasku";
    }

    $pdf_tarjous->draw_text(350, $kala, t("{$_lasku_vai_tarjoustxt} yhteensä", $kieli).":", $page_tarjous[$sivu], $boldi);

    if ($toim == 'PROFORMA') {
      $summa = $kassa_ale != '' ? $summa * (1-$kassa_ale/100) : $summa;

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $summa), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $summa), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);
    }
    elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $summa_valuutassa = $kassa_ale != '' ? $laskurow["summa_valuutassa"] * (1-$kassa_ale/100) : $laskurow["summa_valuutassa"];

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $summa_valuutassa), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa),  $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);

      if ($laskurow["pyoristys_valuutassa"] != 0) {

        $pyoristys_valuutassa = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys_valuutassa"];

        $kala -= 10;
        $pdf_tarjous->draw_text(350, $kala, t("Pyöristys", $kieli).":",  $page_tarjous[$sivu], $norm);
        $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $pyoristys_valuutassa * -1), $norm);
        $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $pyoristys_valuutassa * -1), $page_tarjous[$sivu], $norm);
        $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $norm);
        $kala -= 10;

        $pdf_tarjous->draw_text(350, $kala, t("Yhteensä", $kieli).":", $page_tarjous[$sivu], $norm);
        $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $norm);
        $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $page_tarjous[$sivu], $norm);
        $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $norm);
      }
    }
    else {
      if ($laskurow['summa'] > 0) {
        $total_tarjous = $kassa_ale != '' ? $laskurow["summa"] * (1-$kassa_ale/100) : $laskurow["summa"];
      }
      else {
        $total_tarjous = $kassa_ale != '' ? $total_tarjous * (1-$kassa_ale/100) : $total_tarjous;
      }

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $total_tarjous), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $total_tarjous), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);

      if ($laskurow["pyoristys"] != 0) {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];

        $kala -= 10;
        $pdf_tarjous->draw_text(350, $kala, t("Pyöristys", $kieli).":",  $page_tarjous[$sivu], $boldi);
        $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $pyoristys * -1), $boldi);
        $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $pyoristys * -1), $page_tarjous[$sivu], $boldi);
        $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);
        $kala -= 10;

        $pdf_tarjous->draw_text(350, $kala, t("Yhteensä", $kieli).":", $page_tarjous[$sivu], $boldi);
        $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $summa - $pyoristys), $boldi);
        $pdf_tarjous->draw_text(540-$oikpos, $kala, sprintf('%.2f', $summa - $pyoristys), $page_tarjous[$sivu], $boldi);
        $pdf_tarjous->draw_text(550, $kala, $laskurow["valkoodi"], $page_tarjous[$sivu], $boldi);
      }
    }

    if ($yhtiorow['tarjoustyyppi'] == 1 or $yhtiorow['tarjoustyyppi'] == 5) {
      $pdf_tarjous->draw_text(350, $kala+20, t("Verollinen arvo yhteensä", $kieli).":", $page_tarjous[$sivu], $boldi);

      $oikpos = $pdf_tarjous->strlen(sprintf('%.2f', $total_tarjous), $boldi);
      $pdf_tarjous->draw_text(540-$oikpos, $kala+20, sprintf('%.2f', $total_tarjous), $page_tarjous[$sivu], $boldi);
      $pdf_tarjous->draw_text(550, $kala+20, $laskurow['valkoodi'], $page_tarjous[$sivu], $boldi);
    }

    return array($page_tarjous, $sivu, $kala);
  }
}

function hae_tuotetieto_liitteet($laskun_tunnus) {
  global $kukarow;

  $query = "SELECT liitetiedostot.data,
            liitetiedostot.filename
            FROM lasku
            INNER JOIN tilausrivi ON (tilausrivi.otunnus = lasku.tunnus
              AND tilausrivi.yhtio               = lasku.yhtio)
            INNER JOIN tuote ON (tuote.tuoteno = tilausrivi.tuoteno
              AND tuote.yhtio                    = tilausrivi.yhtio)
            INNER JOIN liitetiedostot ON (liitetiedostot.liitostunnus = tuote.tunnus
              AND liitetiedostot.liitos          = 'tuote'
              AND liitetiedostot.kayttotarkoitus = 'tuotetiedot'
              AND liitetiedostot.yhtio           = tuote.yhtio)
            WHERE lasku.tunnus                   = {$laskun_tunnus}
            AND lasku.yhtio                      = '{$kukarow["yhtio"]}';";

  $result = pupe_query($query);

  $tuoteliitteet = array();

  while ($liitetiedosto = mysql_fetch_assoc($result)) {
    $path = "/tmp/tuotetiedot-" . md5(uniqid(mt_rand(), true));
    $tiedosto = fopen($path, "w");
    fwrite($tiedosto, $liitetiedosto["data"]);
    fclose($tiedosto);
    $tuoteliitteet[$path] = $liitetiedosto["filename"];
  }

  return $tuoteliitteet;
}
