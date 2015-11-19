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

    $merkkiavainsanatunnus = t_avainsana('TUOTEMERKKI', '', "and selite = '{$tyomaaraysrow['merkki']}'", '', '', "tunnus");

    $query = "SELECT *
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$merkkiavainsanatunnus}'
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
      #return $lasku_logo_positio_y-($logoparam['scale']*$isizelogo[1]);
    }

    $pdf->draw_text(410, 815, $yhtiorow['nimi'], $thispage, $norm);

    $pdf->draw_text(250, 715, t("Huoltosaate", $kieli), $thispage, $norm);

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

    $pdf->draw_text(50, 604, t("Pvm", $kieli).": {$tyomaaraysrow['luvattu']}", $thispage, $norm);

    $query = "SELECT *
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$laskurow['liitostunnus']}'";
    $asiakasres = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($asiakasres);

    $pdf->draw_text(50, 594, t("Asiakasnumero", $kieli).": {$asiakasrow['asiakasnro']}", $thispage, $norm);
    $pdf->draw_text(50, 584, t("Asiakkaan s.posti", $kieli).": {$asiakasrow['email']}", $thispage, $norm);

    $pdf->draw_text(50, 564, t("Tuotekoodi", $kieli).": {$tuotenorow['tuoteno']}", $thispage, $norm);
    $pdf->draw_text(50, 544, t("Sarjanumero", $kieli).": {$tuotenorow['sarjanumero']}", $thispage, $norm);

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('tyomaarays_rivi_otsikot')) {
  function tyomaarays_rivi_otsikot($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    $kala -= 20;

    $otsikko = t("Suoritetut toimentpiteet", $kieli);
    $pdf->draw_text(50,  $kala, $otsikko, $thispage, $norm);
    $otsikko_len = $pdf->strlen($otsikko, $iso);

    $pdf->draw_rectangle($kala, 50,  $kala, 40+$otsikko_len, $thispage, $rectparam);

    $kala -= 20;

    $pdf->draw_text(50,  $kala, t("Koodi", $kieli),  $thispage, $norm);
    $pdf->draw_text(125, $kala, t("Työnkuvaus", $kieli),    $thispage, $norm);
    $pdf->draw_text(250, $kala, t("Kpl", $kieli),    $thispage, $norm);
    $pdf->draw_text(325, $kala, t("Suositushinta", $kieli)." {$laskurow['valkoodi']} ".t("sis ALV", $kieli)." {$tuotenorow['alv']}%", $thispage, $norm);
    $pdf->draw_rectangle($kala, 50,  $kala, 350, $thispage, $rectparam);

    $kala -= 20;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}
