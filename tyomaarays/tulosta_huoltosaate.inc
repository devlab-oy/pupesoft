<?php

require_once "pdflib/phppdflib.class.php";

$_hsnorm["height"] = 10;
$_hsnorm["font"] = "Times-Roman";

$_hspieni["height"] = 8;
$_hspieni["font"] = "Times-Roman";

$_hsboldi["height"] = 10;
$_hsboldi["font"] = "Times-Bold";

$_hsiso["height"] = 14;
$_hsiso["font"] = "Helvetica-Bold";

$_hsrectparam["width"] = 0.3;

if (!function_exists('huoltosaate_alku')) {
  function huoltosaate_alku($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    // PDF parametrit
    if ($pdf === NULL) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top', 0);
      $pdf->set_default('margin-bottom', 0);
      $pdf->set_default('margin-left', 0);
      $pdf->set_default('margin-right', 0);
    }

    $thispage = $pdf->new_page("a4");

    $page[$sivu] = $thispage;

    $merkkiavainsanares = t_avainsana('TUOTEMERKKI', '', "and selite = '{$laskurow['merkki']}'");
    $merkkiavainsanarow = mysql_fetch_assoc($merkkiavainsanares);

    $query = "SELECT *
              FROM liitetiedostot
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$merkkiavainsanarow['tunnus']}'
              and liitos       = 'avainsana'";
    $liiteres = pupe_query($query);
    $liite = mysql_fetch_assoc($liiteres);

    $data = $liite["data"];
    $isizelogo[0] = $liite["image_width"];
    $isizelogo[1] = $liite["image_height"];
    unset($liite);

    if (isset($data) and $data) {
      $image = $pdf->jfif_embed($data);

      $logoparam = array();

      if ($lasku_logo_koko_y == 0 and $lasku_logo_koko_x == 0) {
        $lasku_logo_koko_y = 50;
        $lasku_logo_koko_x = 240;

        if ((int) $yhtiorow["lasku_logo_koko"] > 0) {
          $lasku_logo_koko_y = (int) $yhtiorow["lasku_logo_koko"];
        }
      }

      if ($lasku_logo_positio_y == 0 and $lasku_logo_positio_x == 0) {
        $lasku_logo_positio_y = 830;
        $lasku_logo_positio_x = 20;

        if ($yhtiorow["lasku_logo_positio"] != "") {
          if (strpos($yhtiorow["lasku_logo_positio"], "x") !== FALSE) {
            list($llp_y, $llp_x) = explode("x", trim($yhtiorow["lasku_logo_positio"]));
          }
          else {
            $llp_y = trim($yhtiorow["lasku_logo_positio"]);
            $llp_x = 0;
          }

          if ((int) $llp_y > 0) $lasku_logo_positio_y = (int) $llp_y;
          if ((int) $llp_x > 0) $lasku_logo_positio_x = (int) $llp_x;
        }
      }

      if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * ($lasku_logo_koko_x / $isizelogo[0]) <= $lasku_logo_koko_y) {
        $logoparam['scale'] = $lasku_logo_koko_x / $isizelogo[0];
      }
      else {
        $logoparam['scale'] = $lasku_logo_koko_y / $isizelogo[1];
      }

      $placement = $pdf->image_place($image, $lasku_logo_positio_y-($logoparam['scale']*$isizelogo[1]), $lasku_logo_positio_x, $thispage, $logoparam);
    }
    else {
      if (!empty($merkkiavainsanarow['selitetark'])) {
        $pdf->draw_text(50, 815, $merkkiavainsanarow['selitetark'], $thispage, $_hsiso);
      }
      else {
        $pdf->draw_text(50, 815, $merkkiavainsanarow['selite'], $thispage, $_hsiso);
      }
    }

    if (!empty($merkkiavainsanarow['selitetark_2'])) {

      $_oikea_yla_x = 815;

      foreach (explode("\n", trim($merkkiavainsanarow['selitetark_2'])) as $oikea_yla_teksti) {
        $oikpos = $pdf->strlen($oikea_yla_teksti, $_hsnorm);
        $pdf->draw_text(540-$oikpos, $_oikea_yla_x, $oikea_yla_teksti, $thispage, $_hsnorm);
        $_oikea_yla_x -= 12;
      }
    }
    else {
      $pdf->draw_text(410, 815, $yhtiorow['nimi'], $thispage, $_hsnorm);
    }

    $otsikko_len = $pdf->strlen(t("Huoltosaate", $kieli), $_hsiso);
    $pdf->draw_text((595 / 2) - ($otsikko_len / 2), 715, t("Huoltosaate", $kieli), $thispage, $_hsiso);

    $pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), $thispage, $_hspieni);
    $pdf->draw_text(50, 644, $laskurow["toim_nimi"], $thispage, $_hsnorm);
    $pdf->draw_text(50, 634, $laskurow["toim_nimitark"], $thispage, $_hsnorm);
    $pdf->draw_text(50, 624, $laskurow["toim_osoite"], $thispage, $_hsnorm);
    $pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $_hsnorm);
    $pdf->draw_text(50, 604, $laskurow["toim_maa"], $thispage, $_hsnorm);

    $pdf->draw_text(410, 656, t("Asiakkaan yhteystiedot", $kieli), $thispage, $_hspieni);
    $pdf->draw_text(410, 644, $laskurow["nimi"], $thispage, $_hsnorm);
    $pdf->draw_text(410, 634, $laskurow["nimitark"], $thispage, $_hsnorm);
    $pdf->draw_text(410, 624, t("Puh.", $kieli)." {$laskurow['puh']}", $thispage, $_hsnorm);

    $pdf->draw_text(50, 584, t("Pvm", $kieli).": ".tv1dateconv($laskurow['luvattu']), $thispage, $_hsnorm);

    $query = "SELECT *
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$laskurow['liitostunnus']}'";
    $asiakasres = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($asiakasres);

    $pdf->draw_text(50, 574, t("Asiakasnumero", $kieli).": {$asiakasrow['asiakasnro']}", $thispage, $_hsnorm);
    $pdf->draw_text(50, 564, t("Asiakkaan s.posti", $kieli).": {$asiakasrow['email']}", $thispage, $_hsnorm);

    $kala = 544;

    $al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selite not in ('komm1','komm2') and avainsana.selitetark != '' and avainsana.nakyvyys in ('H','I','J')");

    while ($al_row = mysql_fetch_array($al_res)) {
      if (!empty($laskurow[$al_row['selite']])) {
        $pdf->draw_text(50, $kala, t($al_row['selitetark'], $kieli).": {$laskurow[$al_row['selite']]}", $thispage, $_hsnorm);
        $kala-=15;
      }
    }

    $kala-=15;

    $al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selite in ('komm1','komm2') and avainsana.selitetark != '' and avainsana.nakyvyys in ('H','I','J')");

    while ($al_row = mysql_fetch_array($al_res)) {
      if (!empty($laskurow[$al_row['selite']])) {
        $otsikko = t($al_row['selitetark'], $kieli);
        $pdf->draw_text(50, $kala, "{$otsikko}:", $thispage, $_hsnorm);
        $otsikko_len = $pdf->strlen($otsikko, $_hsnorm);
        $pdf->draw_rectangle($kala-2, 50, $kala-2, 50+$otsikko_len, $thispage, $_hsrectparam);

        $kala -= 15;

        $_oikea_yla_x = $kala;

        foreach (explode("\n", trim($laskurow[$al_row['selite']])) as $komm) {

          $komm = wordwrap($komm, 140, "\n", true);

          foreach (explode("\n", trim($komm)) as $kommentti) {
            list($ff_string, $ff_font) = pdf_fontfit($kommentti, 595, $pdf, $_hspieni);
            $pdf->draw_text(50, $_oikea_yla_x, $ff_string, $thispage, $ff_font);
            $_oikea_yla_x -= 12;
          }
        }

        $kala = $_oikea_yla_x - 12;
      }
    }

    $kala -= 10;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('huoltosaate_rivi_otsikot')) {
  function huoltosaate_rivi_otsikot($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    $kala -= 20;

    $pdf->draw_text(50, $kala, t("Koodi", $kieli), $thispage, $_hsnorm);
    $pdf->draw_text(125, $kala, t("Työnkuvaus", $kieli), $thispage, $_hsnorm);
    $pdf->draw_text(350, $kala, t("Kpl", $kieli), $thispage, $_hsnorm);

    $otsikko = t("Suositushinta", $kieli)." {$laskurow['valkoodi']} ".t("sis ALV", $kieli)." ".(int) $laskurow['alv']."%";
    $oikpos = $pdf->strlen($otsikko, $_hsnorm);

    $pdf->draw_text(540-$oikpos, $kala, $otsikko, $thispage, $_hsnorm);
    $pdf->draw_rectangle($kala-2, 50, $kala-2, 540, $thispage, $_hsrectparam);

    $kala -= 20;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('huoltosaate_rivi')) {
  function huoltosaate_rivi($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    // jos ollaan liian pitkällä tehdään uusi otsikko...
    if ($kala < 95) {
      $sivu++;
      $uudehko = "ON";

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = huoltosaate_loppu($return);
      $params = huoltosaate_alku($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;
    }
    else {
      $uudehko = "";
    }

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 70, $pdf, $_hsnorm);
    $pdf->draw_text(50, $kala, $ff_string, $thispage, $ff_font);

    $_oikea_yla_x = $kala;

    foreach (explode("\n", trim($row["kommentti"])) as $komm) {

      $komm = wordwrap($komm, 65, "\n", true);

      foreach (explode("\n", trim($komm)) as $kommentti) {
        list($ff_string, $ff_font) = pdf_fontfit($kommentti, 220, $pdf, $_hsnorm);
        $pdf->draw_text(125, $_oikea_yla_x, $ff_string, $thispage, $ff_font);
        $_oikea_yla_x -= 12;
      }
    }

    $pdf->draw_text(350, $kala, $row["varattu"], $thispage, $_hsnorm);

    $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta_verollinen"], $yhtiorow['hintapyoristys']).",-", $_hsnorm);
    $pdf->draw_text(540-$oikpos, $kala, hintapyoristys($row["rivihinta_verollinen"], $yhtiorow['hintapyoristys']).",-", $thispage, $_hsnorm);

    $kala = $_oikea_yla_x;
    $kala -= 20;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('huoltosaate_loppu')) {
  function huoltosaate_loppu($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    $pdf->draw_rectangle($kala-2, 50, $kala-2, 540, $thispage, $_hsrectparam);

    $pdf->draw_text(50, $kala, t("Yhteensä", $kieli)." {$laskurow['valkoodi']} ".t("sis ALV", $kieli)." ".(int) $laskurow['alv']."%", $thispage, $_hsnorm);

    $oikpos = $pdf->strlen(sprintf('%.2f', $yhteensa).",-", $_hsnorm);
    $pdf->draw_text(540-$oikpos, $kala, sprintf('%.2f', $yhteensa).",-", $thispage, $_hsnorm);

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('huoltosaate_print_pdf')) {
  function huoltosaate_print_pdf($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Huoltosaate-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'email') {
      $liite = $pdffilenimi;

      $kutsu = t("Huoltosaate", $kieli)." $laskurow[tunnus]";

      if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
        $kutsu .= ", ".trim($laskurow["nimi"]);
      }

      echo t("Huoltosaate tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS' or $komento == "PDF_RUUDULLE") {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Huoltosaate tulostuu")."...<br>";
      $line = exec("$komento $pdffilenimi", $output, $returnvalue);
    }
  }
}
