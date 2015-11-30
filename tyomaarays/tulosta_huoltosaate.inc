<?php

require_once "pdflib/phppdflib.class.php";

if (!function_exists('huoltosaate_alku')) {
  function huoltosaate_alku($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    // PDF parametrit
    if ($pdf === NULL) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
    }

    $thispage = $pdf->new_page("a4");

    $page[$sivu] = $thispage;

    $merkkiavainsanares = t_avainsana('TUOTEMERKKI', '', "and selite = '{$tyomaaraysrow['merkki']}'");
    $merkkiavainsanarow = mysql_fetch_assoc($merkkiavainsanares);

    $query = "SELECT *
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$merkkiavainsanarow['tunnus']}'
              and liitos = 'avainsana'";
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
        $logoparam['scale'] = $lasku_logo_koko_y  / $isizelogo[1];
      }

      $placement = $pdf->image_place($image, $lasku_logo_positio_y-($logoparam['scale']*$isizelogo[1]), $lasku_logo_positio_x, $thispage, $logoparam);
    }
    else {
      if (!empty($merkkiavainsanarow['selitetark'])) {
        $pdf->draw_text(50, 815, $merkkiavainsanarow['selitetark'], $thispage, $iso);
      }
      else {
        $pdf->draw_text(50, 815, $merkkiavainsanarow['selite'], $thispage, $iso);
      }
    }

    if (!empty($merkkiavainsanarow['selitetark_2'])) {

      $_oikea_yla_x = 815;

      foreach(explode("\n", trim($merkkiavainsanarow['selitetark_2'])) as $oikea_yla_teksti) {
        $oikpos = $pdf->strlen($oikea_yla_teksti, $norm);
        $pdf->draw_text(540-$oikpos, $_oikea_yla_x, $oikea_yla_teksti, $thispage, $norm);
        $_oikea_yla_x -= 12;
      }
    }
    else {
      $pdf->draw_text(410, 815, $yhtiorow['nimi'], $thispage, $norm);
    }

    $otsikko_len = $pdf->strlen(t("Huoltosaate", $kieli), $iso);
    $pdf->draw_text((595 / 2) - ($otsikko_len / 2), 715, t("Huoltosaate", $kieli), $thispage, $iso);

    $pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), $thispage, $pieni);
    $pdf->draw_text(50, 644, $laskurow["toim_nimi"],      $thispage, $norm);
    $pdf->draw_text(50, 634, $laskurow["toim_nimitark"],  $thispage, $norm);
    $pdf->draw_text(50, 624, $laskurow["toim_osoite"],    $thispage, $norm);
    $pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
    $pdf->draw_text(50, 604, $laskurow["toim_maa"],       $thispage, $norm);

    $pdf->draw_text(410, 656, t("Asiakkaan yhteystiedot", $kieli), $thispage, $pieni);
    $pdf->draw_text(410, 644, $laskurow["nimi"],      $thispage, $norm);
    $pdf->draw_text(410, 634, $laskurow["nimitark"],  $thispage, $norm);
    $pdf->draw_text(410, 624, t("Puh.", $kieli)." {$laskurow['puh']}", $thispage, $norm);

    $pdf->draw_text(50, 584, t("Pvm", $kieli).": ".tv1dateconv($tyomaaraysrow['luvattu']), $thispage, $norm);

    $query = "SELECT *
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$laskurow['liitostunnus']}'";
    $asiakasres = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($asiakasres);

    $pdf->draw_text(50, 574, t("Asiakasnumero", $kieli).": {$asiakasrow['asiakasnro']}", $thispage, $norm);
    $pdf->draw_text(50, 564, t("Asiakkaan s.posti", $kieli).": {$asiakasrow['email']}", $thispage, $norm);

    $pdf->draw_text(50, 544, t("Tuotekoodi", $kieli).": {$tyomaaraysrow['koodi']}", $thispage, $norm);
    $pdf->draw_text(50, 534, t("Sarjanumero", $kieli).": {$tyomaaraysrow['valmnro']}", $thispage, $norm);

    $kala = 514;

    if (!empty($tyomaaraysrow['komm1'])) {
      $otsikko = t("Työnkuvaus", $kieli);
      $pdf->draw_text(50, $kala, "{$otsikko}:", $thispage, $norm);
      $otsikko_len = $pdf->strlen($otsikko, $norm);
      $pdf->draw_rectangle($kala-2, 50, $kala-2, 50+$otsikko_len, $thispage, $rectparam);

      $kala -= 15;

      $_oikea_yla_x = $kala;

      foreach(explode("\n", trim($tyomaaraysrow['komm1'])) as $komm) {

        $komm = wordwrap($komm, 140, "\n", true);

        foreach(explode("\n", trim($komm)) as $kommentti) {
          list($ff_string, $ff_font) = pdf_fontfit($kommentti, 595, $pdf, $pieni);
          $pdf->draw_text(50, $_oikea_yla_x, $ff_string, $thispage, $ff_font);
          $_oikea_yla_x -= 12;
        }
      }

      $kala = $_oikea_yla_x - 12;
    }

    if (!empty($tyomaaraysrow['komm2'])) {
      $otsikko = t("Toimenpiteet", $kieli);
      $pdf->draw_text(50, $kala, "{$otsikko}:", $thispage, $norm);
      $otsikko_len = $pdf->strlen($otsikko, $norm);
      $pdf->draw_rectangle($kala-2, 50, $kala-2, 50+$otsikko_len, $thispage, $rectparam);

      $kala -= 15;

      $_oikea_yla_x = $kala;

      foreach(explode("\n", trim($tyomaaraysrow['komm2'])) as $komm) {

        $komm = wordwrap($komm, 140, "\n", true);

        foreach(explode("\n", trim($komm)) as $kommentti) {
          list($ff_string, $ff_font) = pdf_fontfit($kommentti, 595, $pdf, $pieni);
          $pdf->draw_text(50, $_oikea_yla_x, $ff_string, $thispage, $ff_font);
          $_oikea_yla_x -= 12;
        }
      }

      $kala = $_oikea_yla_x;
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

    $pdf->draw_text(50,  $kala, t("Koodi", $kieli),  $thispage, $norm);
    $pdf->draw_text(125, $kala, t("Työnkuvaus", $kieli),    $thispage, $norm);
    $pdf->draw_text(350, $kala, t("Kpl", $kieli),    $thispage, $norm);

    $otsikko = t("Suositushinta", $kieli)." {$laskurow['valkoodi']} ".t("sis ALV", $kieli)." {$tuotenorow['alv']}%";
    $oikpos = $pdf->strlen($otsikko, $norm);

    $pdf->draw_text(540-$oikpos, $kala, $otsikko, $thispage, $norm);
    $pdf->draw_rectangle($kala-2, 50,  $kala-2, 540, $thispage, $rectparam);

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

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 70, $pdf, $norm);
    $pdf->draw_text(50, $kala, $ff_string, $thispage, $ff_font);

    $_oikea_yla_x = $kala;

    foreach(explode("\n", trim($row["kommentti"])) as $komm) {

      $komm = wordwrap($komm, 65, "\n", true);

      foreach(explode("\n", trim($komm)) as $kommentti) {
        list($ff_string, $ff_font) = pdf_fontfit($kommentti, 220, $pdf, $norm);
        $pdf->draw_text(125, $_oikea_yla_x, $ff_string, $thispage, $ff_font);
        $_oikea_yla_x -= 12;
      }
    }

    $pdf->draw_text(350, $kala, $row["varattu"], $thispage, $norm);

    $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"], $yhtiorow['hintapyoristys']).",-", $norm);
    $pdf->draw_text(540-$oikpos, $kala, hintapyoristys($row["rivihinta"], $yhtiorow['hintapyoristys']).",-", $thispage, $norm);

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

    $pdf->draw_rectangle($kala-2, 50,  $kala-2, 540, $thispage, $rectparam);

    $pdf->draw_text(50, $kala, t("Yhteensä", $kieli)." {$laskurow['valkoodi']} ".t("sis ALV", $kieli)." {$tuotenorow['alv']}%", $thispage, $norm);

    $oikpos = $pdf->strlen("{$yhteensa},-", $norm);
    $pdf->draw_text(540-$oikpos, $kala, "{$yhteensa},-", $thispage, $norm);

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

      $kutsu = t("Työmääräys", $kieli)." $laskurow[tunnus]";

      if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
        $kutsu .= ", ".trim($laskurow["nimi"]);
      }

      echo t("Työmääräys tulostuu")."...<br>";

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
      echo t("Työmääräys tulostuu")."...<br>";
      $line = exec("$komento $pdffilenimi", $output, $returnvalue);
    }
  }
}
