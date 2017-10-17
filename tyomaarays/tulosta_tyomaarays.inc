<?php

require_once "pdflib/phppdflib.class.php";

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

$boldi["height"] = 10;
$boldi["font"] = "Times-Bold";

$pieni_boldi["height"] = 8;
$pieni_boldi["font"] = "Times-Bold";

$iso["height"] = 14;
$iso["font"] = "Helvetica-Bold";

$rectparam["width"] = 0.3;
$lineparam["width"] = 1;
$rivinkorkeus = 17;

if (!function_exists('tyomaarays_alku')) {
  function tyomaarays_alku($params) {
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

    //oletukset viivakoodeille
    $output = "png";
    $width = "140";
    $height = "20";
    $xres = "1";
    $font = "2";
    $drawtext = "off";
    $border = "off";
    $stretchtext = "off";
    $negative = "off";

    $style = BCS_ALIGN_CENTER;
    $style |= ($output == "png" ) ? BCS_IMAGE_PNG : 0;
    $style |= ($output == "jpeg") ? BCS_IMAGE_JPEG : 0;
    $style |= ($border == "on"  ) ? BCS_BORDER : 0;
    $style |= ($drawtext== "on"  ) ? BCS_DRAW_TEXT : 0;
    $style |= ($stretchtext== "on" ) ? BCS_STRETCH_TEXT : 0;
    $style |= ($negative== "on"  ) ? BCS_REVERSE_COLOR : 0;

    $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

    tulosta_logo_pdf($pdf, $thispage, $laskurow);

    // haetaan maksuehdon tiedot
    $query = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $masresult = pupe_query($query);
    $masrow = mysql_fetch_array($masresult);

    //maksuehto tekstinä
    $maksuehto = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

    $pdf->draw_text(410, 815, $laskurow["hyvaksynnanmuutos"], $thispage, $iso);

    if ($tyyppi == "SISAINEN") {
      $pdf->draw_text(300, 815, t("Sisäinen työmääräys", $kieli), $thispage, $iso);
    }
    else {
      $pdf->draw_text(310, 815, t("Työmääräys", $kieli), $thispage, $iso);
    }

    $pdf->draw_text(310, 803, t("Sivu", $kieli), $thispage, $norm);

    //Vasen sarake
    //$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
    $pdf->draw_text(50, 729, t("Ostaja", $kieli), $thispage, $pieni);
    $pdf->draw_text(50, 717, $laskurow["nimi"], $thispage, $norm);
    $pdf->draw_text(50, 707, $laskurow["nimitark"], $thispage, $norm);
    $pdf->draw_text(50, 697, $laskurow["osoite"], $thispage, $norm);
    $pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
    $pdf->draw_text(50, 677, $laskurow["maa"], $thispage, $norm);


    //$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
    $pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), $thispage, $pieni);
    $pdf->draw_text(50, 644, $laskurow["toim_nimi"], $thispage, $norm);
    $pdf->draw_text(50, 634, $laskurow["toim_nimitark"], $thispage, $norm);
    $pdf->draw_text(50, 624, $laskurow["toim_osoite"], $thispage, $norm);
    $pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
    $pdf->draw_text(50, 604, $laskurow["toim_maa"], $thispage, $norm);


    //Oikea sarake
    $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli), $thispage, $pieni);
    $pdf->draw_text(310, 782, $laskurow["tunnus"], $thispage, $boldi);

    $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 771, t("Keräysaika", $kieli), $thispage, $pieni);

    if ($laskurow["keraysvko"] != '') {
      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["kerayspvm"]));

      if ($laskurow['keraysvko'] != '7') {
        $taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
      }

      $pdf->draw_text(310, 761, $taika, $thispage, $boldi);
    }
    else {
      $pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]), $thispage, $boldi);
    }

    $pdf->draw_text(430, 771, t("Toimitusaika", $kieli), $thispage, $pieni);

    if ($laskurow["toimvko"] != '') {
      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

      if ($laskurow['toimvko'] != '7') {
        $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
      }

      $pdf->draw_text(430, 761, $taika, $thispage, $boldi);
    }
    else {
      $pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]), $thispage, $boldi);
    }

    if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];

    $pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 750, t("Asiakasnumero", $kieli), $thispage, $pieni);
    $pdf->draw_text(310, 740, $asrow["asiakasnro"], $thispage, $norm);
    $pdf->draw_text(430, 750, t("Päiväys", $kieli), $thispage, $pieni);
    $pdf->draw_text(430, 740, tv1dateconv(date("Y-m-d")), $thispage, $norm);


    $pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 729, t("Myyjä", $kieli), $thispage, $pieni);

    //etsitään myyjän nimi
    $query = "SELECT nimi
              FROM kuka
              WHERE tunnus = '$laskurow[myyja]'
              AND yhtio    = '$kukarow[yhtio]'";
    $myyresult = pupe_query($query);
    $myyrow = mysql_fetch_array($myyresult);

    $pdf->draw_text(310, 719, $myyrow["nimi"], $thispage, $norm);

    $pdf->draw_text(430, 729, t("Tilauspvm", $kieli), $thispage, $pieni);
    $pdf->draw_text(430, 719, tv1dateconv($laskurow["luontiaika"], "pitka"), $thispage, $norm);

    $pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 708, t("Toimitustapa", $kieli), $thispage, $pieni);
    $pdf->draw_text(310, 698, t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli), $thispage, $boldi);

    $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
    if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
    else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

    $pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 687, t("Toimitusehto", $kieli), $thispage, $pieni);
    $pdf->draw_text(310, 677, $toimehto, $thispage, $norm);

    $pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 666, t("Maksuehto", $kieli), $thispage, $pieni);
    $pdf->draw_text(310, 656, $maksuehto, $thispage, $norm);

    $pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 645, t("Vastaanottajan kuittaus", $kieli), $thispage, $pieni);
    $pdf->draw_text(520, 635, "/", $thispage, $norm);
    $pdf->draw_text(540, 635, "/", $thispage, $norm);
    $pdf->draw_text(550, 635, date("Y"), $thispage, $norm);

    $pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 624, t("Hyväksyn allamainitut työt suoritettavaksi", $kieli), $thispage, $pieni);
    $pdf->draw_text(520, 614, "/", $thispage, $norm);
    $pdf->draw_text(540, 614, "/", $thispage, $norm);
    $pdf->draw_text(550, 614, date("Y"), $thispage, $norm);

    if ($tyyppi == "Q") {
      $pdf->draw_text(310, 600, t("Työmääräyksen hinnat sisältävät arvonlisäveron"), $thispage, $pieni);
    }
    elseif ($tyyppi == "A" or $tyyppi == "") {

      if ($yhtiorow['alv_kasittely'] != '') {
        $pdf->draw_text(310, 600, t("Työmääräyksen hinnat ovat nettohintoja joihin lisätään arvonlisävero."), $thispage, $pieni);
      }
      else {
        $pdf->draw_text(310, 600, t("Työmääräyksen hinnat sisältävät arvonlisäveron"), $thispage, $pieni);
      }
    }

    $kala = 575;

    //Täysin dynaaminen osio
    $al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selitetark != '' and avainsana.nakyvyys in ('','K','I','J')");

    $al_lask = 35;
    $al_fask = 0;
    $al_komm = "";

    while ($al_row = mysql_fetch_array($al_res)) {

      if ($al_lask > 310) {
        $kala -= 10;
        $al_lask = 35;
      }

      $kentta = $al_row["selite"];

      if (((!is_numeric($laskurow[$kentta]) and trim($laskurow[$kentta]) != '') or (is_numeric($laskurow[$kentta]) and $laskurow[$kentta] != 0)) and trim($laskurow[$kentta]) != '0000-00-00') {
        if (strtoupper($al_row["selitetark_2"]) == "TEXT") {
          $al_komm .= "\n$al_row[selitetark]:###".wordwrap(str_replace("\n", "\n###", $laskurow[$kentta]), 90, "\n###");
        }
        else {
          if (strtoupper($al_row["selitetark_2"]) == "DATE") {
            $laskurow[$kentta] = tv1dateconv($laskurow[$kentta]);
          }
          elseif ($kentta == "suorittaja") {
            $query = "SELECT nimi
                      FROM kuka
                      WHERE yhtio = '$kukarow[yhtio]'
                      and kuka    = '".$laskurow[$kentta]."'";
            $yresult = pupe_query($query);
            $row = mysql_fetch_array($yresult);

            $laskurow[$kentta] = $row["nimi"];
          }
          elseif ($kentta == "merkki") {
            $yresult = t_avainsana("SARJANUMERON_LI", $kieli, "and avainsana.selite = 'MERKKI' and avainsana.selitetark = '".$laskurow[$kentta]."'");

            if (mysql_num_rows($yresult) > 0) {
              $row = mysql_fetch_array($yresult);

              $laskurow[$kentta] = $row["selitetark_2"];
            }
          }

          $pdf->draw_text($al_lask, $kala, $al_row["selitetark"].":", $thispage, $norm);
          $pdf->draw_text($al_lask+70, $kala, $laskurow[$kentta], $thispage, $norm);
          $al_fask++;
          $al_lask += 275;
        }
      }
    }
    if ($al_fask > 0) {
      $kala -= 15;
    }

    $komm = "";

    if (trim($laskurow['tilausyhteyshenkilo']) != '') {
      $komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
    }

    if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
      $komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
    }

    if (trim($laskurow['kohde']) != '') {
      $komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
    }

    if (trim($laskurow['viesti']) != '') {
      $komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
    }

    if (trim($laskurow['comments']) != '') {
      $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 90, "\n###");
    }

    if (trim($al_komm) != '') {
      $komm .= $al_komm;
    }

    //Tulostetaan laskun kommentti
    if (trim($komm) != '') {
      $kommentit = explode("\n", trim($komm));

      $pohja = $kala;
      $maxoik = 0;

      foreach ($kommentit as $kommentti) {
        if (strpos($kommentti, "###") !== FALSE) {
          list($o, $v) = explode("###", $kommentti);

          $oikpos = $pdf->strlen($o, $boldi);

          if ($oikpos > $maxoik) {
            $maxoik = $oikpos;
          }
        }
      }

      foreach ($kommentit as $kommentti) {

        if ($pohja <= 130) {

          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = tyomaarays_loppu($return);
          $params = tyomaarays_uusi_sivu($return);

          // Luodaan muuttujat
          extract($params);

          $pohja = $kala;
          $page[$sivu] = $thispage;
        }

        if (strpos($kommentti, "###") !== false) {
          list($o, $v) = explode("###", $kommentti);

          $pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
          $pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
        }
        else {
          $pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
        }

        $pohja = $pohja - 12;
      }

      $kala = $pohja-10;
    }

    //viivakoodin arvo
    $data = viivakoodi($laskurow["tunnus"], "code128", 150, 30, "");
    $image = $pdf->jfif_embed($data);
    $pdf->image_place($image, 805, 440, $thispage, array("scale" => 0.3));

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('tyomaarays_uusi_sivu')) {
  function tyomaarays_uusi_sivu($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    $thispage = $pdf->new_page("a4");

    //oletukset viivakoodeille
    $output = "png";
    $width = "140";
    $height = "20";
    $xres = "1";
    $font = "2";
    $drawtext = "off";
    $border = "off";
    $stretchtext = "off";
    $negative = "off";

    $style = BCS_ALIGN_CENTER;
    $style |= ($output == "png" ) ? BCS_IMAGE_PNG : 0;
    $style |= ($output == "jpeg") ? BCS_IMAGE_JPEG : 0;
    $style |= ($border == "on"  ) ? BCS_BORDER : 0;
    $style |= ($drawtext== "on"  ) ? BCS_DRAW_TEXT : 0;
    $style |= ($stretchtext== "on" ) ? BCS_STRETCH_TEXT : 0;
    $style |= ($negative== "on"  ) ? BCS_REVERSE_COLOR : 0;

    $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

    tulosta_logo_pdf($pdf, $thispage, $laskurow);

    $pdf->draw_text(410, 815, $laskurow["hyvaksynnanmuutos"], $thispage, $iso);

    if ($tyyppi == "SISAINEN") {
      $pdf->draw_text(300, 815, t("Sisäinen työmääräys", $kieli), $thispage, $iso);
    }
    else {
      $pdf->draw_text(310, 815, t("Työmääräys", $kieli), $thispage, $iso);
    }

    $pdf->draw_text(310, 803, t("Sivu", $kieli), $thispage, $norm);

    //Oikea sarake
    $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli), $thispage, $pieni);
    $pdf->draw_text(310, 782, $laskurow["tunnus"], $thispage, $boldi);

    $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 771, t("Keräysaika", $kieli), $thispage, $pieni);

    if ($laskurow["keraysvko"] != '') {
      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["kerayspvm"]));

      if ($laskurow['keraysvko'] != '7') {
        $taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
      }

      $pdf->draw_text(310, 761, $taika, $thispage, $boldi);
    }
    else {
      $pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]), $thispage, $boldi);
    }

    $pdf->draw_text(430, 771, t("Toimitusaika", $kieli), $thispage, $pieni);

    if ($laskurow["toimvko"] != '') {
      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

      if ($laskurow['toimvko'] != '7') {
        $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
      }

      $pdf->draw_text(430, 761, $taika, $thispage, $boldi);
    }
    else {
      $pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]), $thispage, $boldi);
    }

    //viivakoodin arvo
    $data = viivakoodi($laskurow["tunnus"], "code128", 150, 30, "");
    $image = $pdf->jfif_embed($data);
    $pdf->image_place($image, 805, 440, $thispage, array("scale" => 0.3));

    $kala = 710;

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

    $pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);

    if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") {
      if ($tuotetyyppi == "2 Työt") {
        $pdf->draw_text(25, $kala+35, t("Työt ja palvelut", $kieli), $thispage, $boldi);
      }
      else {
        $pdf->draw_text(25, $kala+35, t("Osat ja tarvikkeet", $kieli), $thispage, $boldi);
      }
    }

    if ($tyyppi == "" or $tyyppi == "Q") {
      $pdf->draw_rectangle($kala+30, 20, $kala+10, 40, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40, $kala+10, 320, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 320, $kala+10, 370, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 370, $kala+10, 420, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage, $rectparam);
      $pdf->draw_text(25, $kala+15, "#", $thispage, $norm);
      $pdf->draw_text(45, $kala+15, t("Tuotenumero/Tuotenimi", $kieli), $thispage, $norm);
      $pdf->draw_text(330, $kala+15, t("Tilattu", $kieli), $thispage, $norm);
      $pdf->draw_text(375, $kala+15, t("Toimitus", $kieli), $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli), $thispage, $norm);
      $pdf->draw_text(478, $kala+15, t("Alennus", $kieli), $thispage, $norm);
      $pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli), $thispage, $norm);
    }
    elseif ($tyyppi == "P" or $tyyppi == "SISAINEN") {

      $pdf->draw_rectangle($kala+30, 20, $kala+10, 40, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40, $kala+10, 120, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 120, $kala+10, 520, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage, $rectparam);

      $pdf->draw_text(25, $kala+15, "#", $thispage, $norm);
      $pdf->draw_text(45, $kala+15, t("Paikka", $kieli), $thispage, $norm);
      $pdf->draw_text(125, $kala+15, t("Tuotenumero/Tuotenimi", $kieli), $thispage, $norm);
      $pdf->draw_text(530, $kala+15, t("Tilattu", $kieli), $thispage, $norm);
    }
    else {

      $pdf->draw_rectangle($kala+30, 20, $kala+10, 40, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40, $kala+10, 180, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 180, $kala+10, 520, $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage, $rectparam);

      $pdf->draw_text(25, $kala+15, "#", $thispage, $norm);
      $pdf->draw_text(45, $kala+15, t("Tuotenumero", $kieli), $thispage, $norm);
      $pdf->draw_text(185, $kala+15, t("Tuotenimi", $kieli), $thispage, $norm);
      $pdf->draw_text(530, $kala+15, t("Tilattu", $kieli), $thispage, $norm);
    }

    $kala -= 3;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('tyomaarays_rivi')) {
  function tyomaarays_rivi($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    // viivat rivien väliin...
    $x[0] = 20;
    $x[1] = 580;
    $y[0] = $y[1] = $kala + $rivinkorkeus - 4;

    if (($perheid == 0 or $perheid != $row["perheid"]) and ($perheid2 == 0 or $perheid2 != $row["perheid2"]) and isset($perheid) and isset($perheid2)) {
      $pdf->draw_line($x, $y, $thispage, $rectparam);
    }

    // jos ollaan liian pitkällä tehdään uusi otsikko...
    if ($kala < 95) {
      $sivu++;
      $uudehko = "ON";

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = tyomaarays_loppu($return);
      $params = tyomaarays_uusi_sivu($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;
    }
    else {
      $uudehko = "";
    }

    if (($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E" and $row["tuotetyyppi"] != $edtuotetyyppi) or $uudehko == "ON") {
      $kala -= $rivinkorkeus*3;

      if ($kala < 95) {
        $sivu++;
        $uudehko = "ON";

        // Luodaan palautettavat
        $return = compact(array_keys($params));

        $return = tyomaarays_loppu($return);
        $params = tyomaarays_uusi_sivu($return);

        // Luodaan muuttujat
        extract($params);

        $page[$sivu] = $thispage;
      }

      // Luodaan palautettavat
      $return = compact(array_keys($params));
      $return["tuotetyyppi"] = $row["tuotetyyppi"];

      $params = tyomaarays_rivi_otsikot($return);

      // Luodaan muuttujat
      extract($params);
    }

    $edtuotetyyppi = $row["tuotetyyppi"];

    $varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];

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
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.otunnus in ($perherow[tunnukset])
                  and tilausrivi.perheid = '$row[perheid]'
                  ORDER BY tilausrivi.tunnus";
        $perheresult = pupe_query($query);

        if (mysql_num_rows($perheresult) > 1) {
          while ($perherow = mysql_fetch_array($perheresult)) {
            if ($row["perhe_kommentti"] == "") {
              $row["perhe_kommentti"] .= t("Tuoteperhe", $kieli).": ";
              $row["perhe_kommentti"] .= $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"].".\n".t("Sisältää tuotteet", $kieli).": ";
            }
            else {
              $row["perhe_kommentti"] .= strtoupper($perherow["tuoteno"]).", ";
            }
          }
          $row["perhe_kommentti"] = substr($row["perhe_kommentti"], 0, -2);
        }
      }
    }

    if (trim($row["perhe_kommentti"]) != '') {
      $pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"], $thispage, $norm);
      $kala = $pohja - $rivinkorkeus;
    }

    $pdf->draw_text(23, $kala, $rivinumerot[$row["tunnus"]], $thispage, $pieni);

    if ($row["keratty"] != '' and !in_array($row["var"], array('J', 'P'))) {
      $row['varattu'] = ($row['varattu']+$row['kpl'])." ".t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    }
    else {
      if ($row["var"] == 'J') { // jälkitoimitus
        $row['varattu'] = t("**JT**", $kieli);
      }
      elseif ($row["var"] == 'P') { // puute
        $row['varattu'] = t("PUUTE", $kieli);
      }
      else {
        $row["varattu"] = "";
        $row["kpl"] = "";
      }
    }

    if ($tyyppi == "" or $tyyppi == "Q") {
      $pdf->draw_text(45, $kala, $row["tuoteno"], $thispage, $norm);

      $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

      $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
      $pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

      $oikpos = $pdf->strlen($row["varattu"], $norm);
      $pdf->draw_text(415-$oikpos, $kala, $row["varattu"], $thispage, $norm);

      if ($tyyppi == 'Q') {

        if ($yhtiorow["alv_kasittely"] != '' and $row["alv"] < 500) {
          $hinta = hintapyoristys($row["hinta"] * (1 + $row["alv"] / 100));
        }
        else {
          $hinta = hintapyoristys($row["hinta"]);
        }

        $oikpos = $pdf->strlen($hinta, $norm);
        $pdf->draw_text(465-$oikpos, $kala, $hinta, $thispage, $norm);
      }
      else {
        $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
        $pdf->draw_text(465-$oikpos, $kala, hintapyoristys($row["hinta"]), $thispage, $norm);
      }

      $ed_ale = array();
      $lisakala = '';

      // Alennus kuntoon
      if ($row["netto"] != 'N' and $row["erikoisale"] > 0) {
        $row["ale1"] = (1 - (1 - $row["ale1"] / 100) * (1 - $row["erikoisale"] / 100)) * 100;
      }

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

      if (trim($lisakala) != '') {
        list($ff_string, $ff_font) = pdf_fontfit($lisakala, 56, $pdf, $norm);

        $oikpos = $pdf->strlen($ff_string, $ff_font);
        $pdf->draw_text(515-$oikpos, $kala, $ff_string, $thispage, $norm);
      }

      if ($tyyppi == 'Q') {

        if ($yhtiorow["alv_kasittely"] != '' and $row["alv"] < 500) {
          $rivihinta = hintapyoristys($row["rivihinta"] * (1 + $row["alv"] / 100));
        }
        else {
          $rivihinta = hintapyoristys($row["rivihinta"]);
        }
        $oikpos = $pdf->strlen($rivihinta, $norm);
        $pdf->draw_text(575-$oikpos, $kala, $rivihinta, $thispage, $norm);
      }
      else {
        $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
        $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
      }

      $kala = $kala - $rivinkorkeus + 5;
      $pdf->draw_text(45, $kala, $row["nimitys"], $thispage, $norm);
    }
    elseif ($tyyppi == "P" or $tyyppi == "SISAINEN") {
      $pdf->draw_text(42, $kala, $varastopaikka, $thispage, $norm);
      $pdf->draw_text(125, $kala, $row["tuoteno"], $thispage, $norm);

      $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

      $oikpos = $pdf->strlen($row['tilkpl']." ".$omyks, $norm);
      $pdf->draw_text(575-$oikpos, $kala, $row['tilkpl']." ".$omyks, $thispage, $norm);

      $kala = $kala - $rivinkorkeus + 5;

      $pdf->draw_text(125, $kala, $row["nimitys"], $thispage, $norm);
    }
    else {

      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 130, $pdf, $norm);

      $pdf->draw_text(42, $kala, $ff_string, $thispage, $ff_font);

      list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 330, $pdf, $norm);

      $pdf->draw_text(185, $kala, $ff_string, $thispage, $ff_font);

      $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

      $oikpos = $pdf->strlen($row['tilkpl']." ".$omyks, $norm);
      $pdf->draw_text(575-$oikpos, $kala, $row['tilkpl']." ".$omyks, $thispage, $norm);
    }

    $kala = $kala - $rivinkorkeus;

    //Haetan sarjanumeron tiedot
    if ($row["sarjanumeroseuranta"] != "") {
      if ($tyyppi == "SISAINEN") {
        $sarjanutunnus = "siirtorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and $row["var2"] == "EIOST") {
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

    if (trim($row["kommentti"]) != "") {
      $row["kommentti"] = wordwrap(trim($row["kommentti"]), 90, "\n");

      $kommentit = explode("\n", $row["kommentti"]);

      foreach ($kommentit as $kommentti) {

        if ($kala < 95) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = tyomaarays_loppu($return);
          $return = tyomaarays_uusi_sivu($return);

          $params = tyomaarays_rivi_otsikot($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }


        if (($tyyppi == "" or $tyyppi == "O") and $row["kommentti"] != '') {
          $pdf->draw_text(45, $kala, trim($kommentti), $thispage, $norm);
        }
        elseif ($row["kommentti"] != '') {
          $pdf->draw_text(125, $kala, trim($kommentti), $thispage, $norm);
        }

        $kala = $kala - 10;
      }

      $kala = $kala - 10;
    }

    $perheid = $row["perheid"];
    $perheid2 = $row["perheid2"];

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('tyomaarays_loppu')) {
  function tyomaarays_loppu($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(70, 20, 20, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(70, 207, 20, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(70, 394, 20, 580, $thispage, $rectparam);

    $pdf->draw_text(30, 55, $yhtiorow["nimi"], $thispage, $pieni);
    $pdf->draw_text(30, 45, $yhtiorow["osoite"], $thispage, $pieni);
    $pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"], $thispage, $pieni);
    $pdf->draw_text(30, 25, $yhtiorow["maa"], $thispage, $pieni);

    $pdf->draw_text(217, 55, t("Puhelin", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(257, 55, $yhtiorow["puhelin"], $thispage, $pieni);
    $pdf->draw_text(217, 45, t("Fax", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(257, 45, $yhtiorow["fax"], $thispage, $pieni);
    $pdf->draw_text(217, 35, t("Sähköposti", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(257, 35, $yhtiorow["email"], $thispage, $pieni);

    //jos on suomalainen yritys tehdään ytunnus nätiks
    if (strtoupper($yhtiorow["maa"])== 'FI') {
      //muutetaan ytunnus takas oikean näköseks
      $ytunpit = 8-strlen($yhtiorow["ytunnus"]);

      if ($ytunpit > 0) {
        $uytunnus = $yhtiorow["ytunnus"];
        while ($ytunpit > 0) {
          $uytunnus = "0".$uytunnus; $ytunpit--;
        }
      }
      else {
        $uytunnus = $yhtiorow["ytunnus"];
      }

      $uytunnus = substr($uytunnus, 0, 7)."-".substr($uytunnus, 7, 1);
    }
    else {
      $uytunnus = $yhtiorow["ytunnus"];
    }

    if ($laskurow['yhtio_ovttunnus'] != $yhtiorow['ovttunnus'] and $laskurow['yhtio_ovttunnus'] != '') {
      $uytunnus = $laskurow['yhtio_ovttunnus'];
    }
    else {
      $uytunnus = $yhtiorow["maa"]."-".$uytunnus;
    }

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(444, 55, $uytunnus, $thispage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(444, 45, $yhtiorow["kotipaikka"], $thispage, $pieni);

    if ($tots != 1) {
      $pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", $thispage, $norm);
    }

    if ($tots == 1) {
      // viivat rivien väliin...
      $x[0] = 20;
      $x[1] = 580;
      $y[0] = $y[1] = $kala + $rivinkorkeus - 4;

      $pdf->draw_line($x, $y, $thispage, $rectparam);

      for ($pp=1; $pp<=$sivu; $pp++) {
        $pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
      }
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('tyomaarays_loppu_lisarivit')) {
  function tyomaarays_loppu_lisarivit($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(70, 20, 20, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(70, 207, 20, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(70, 394, 20, 580, $thispage, $rectparam);

    $pdf->draw_text(30, 55, $yhtiorow["nimi"], $thispage, $pieni);
    $pdf->draw_text(30, 45, $yhtiorow["osoite"], $thispage, $pieni);
    $pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"], $thispage, $pieni);
    $pdf->draw_text(30, 25, $yhtiorow["maa"], $thispage, $pieni);

    $pdf->draw_text(217, 55, t("Puhelin", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(257, 55, $yhtiorow["puhelin"], $thispage, $pieni);
    $pdf->draw_text(217, 45, t("Fax", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(257, 45, $yhtiorow["fax"], $thispage, $pieni);
    $pdf->draw_text(217, 35, t("Sähköposti", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(257, 35, $yhtiorow["email"], $thispage, $pieni);

    //jos on suomalainen yritys tehdään ytunnus nätiks
    if (strtoupper($yhtiorow["maa"])== 'FI') {
      //muutetaan ytunnus takas oikean näköseks
      $ytunpit = 8-strlen($yhtiorow["ytunnus"]);

      if ($ytunpit > 0) {
        $uytunnus = $yhtiorow["ytunnus"];
        while ($ytunpit > 0) {
          $uytunnus = "0".$uytunnus; $ytunpit--;
        }
      }
      else {
        $uytunnus = $yhtiorow["ytunnus"];
      }

      $uytunnus = substr($uytunnus, 0, 7)."-".substr($uytunnus, 7, 1);
    }
    else {
      $uytunnus = $yhtiorow["ytunnus"];
    }

    if ($laskurow['yhtio_ovttunnus'] != $yhtiorow['ovttunnus'] and $laskurow['yhtio_ovttunnus'] != '') {
      $uytunnus = $laskurow['yhtio_ovttunnus'];
    }
    else {
      $uytunnus = $yhtiorow["maa"]."-".$uytunnus;
    }

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(444, 55, $uytunnus, $thispage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":", $thispage, $pieni);
    $pdf->draw_text(444, 45, $yhtiorow["kotipaikka"], $thispage, $pieni);

    if ($tots != 1) {
      $pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", $thispage, $norm);
    }

    if ($tots == 1) {
      // viivat rivien väliin...
      $x[0] = 20;
      $x[1] = 580;
      $y[0] = $y[1] = $kala + $rivinkorkeus - 4;

      $pdf->draw_line($x, $y, $thispage, $rectparam);

      for ($pp=1; $pp<=$sivu; $pp++) {
        $pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
      }
    }

    if ($kala >= 308) {
      for ($i = $kala-20, $xxx = 0; $i > 60 and $xxx < 10; $i-=20) {
        $x[0] = 50;
        $x[1] = 540;
        $y[0] = $y[1] = $i;

        $pdf->draw_line($x, $y, $thispage, $lineparam);

        $xxx++;
        $kala -= 20;
      }

      $kala -= 20;

      $pdf->draw_rectangle($kala, 30, $kala-10, 40, $thispage, $rectparam);
      $pdf->draw_text(45, $kala-10, t("Toiminnan kokeilu", $kieli), $thispage, $norm);

      $x[0] = 125;
      $x[1] = 215; // 220
      $y[0] = $y[1] = $kala-10;

      $pdf->draw_line($x, $y, $thispage, $lineparam);

      $pdf->draw_rectangle($kala, 225, $kala-10, 235, $thispage, $rectparam);
      $pdf->draw_text(240, $kala-10, t("Käytönopastus", $kieli), $thispage, $norm);

      $x[0] = 305;
      $x[1] = 385;
      $y[0] = $y[1] = $kala-10;

      $pdf->draw_line($x, $y, $thispage, $lineparam);

      $pdf->draw_rectangle($kala, 395, $kala-10, 405, $thispage, $rectparam);
      $pdf->draw_text(410, $kala-10, t("Tuotevarmennus", $kieli), $thispage, $norm);

      $x[0] = 480;
      $x[1] = 560;
      $y[0] = $y[1] = $kala-10;

      $pdf->draw_line($x, $y, $thispage, $lineparam);
    }
    else {

      $sivu++;
      $uudehko = "ON";

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = tyomaarays_loppu($return);
      $return = tyomaarays_uusi_sivu($return);
      $params = tyomaarays_loppu_lisarivit($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('tyomaarays_print_pdf')) {
  function tyomaarays_print_pdf($params) {
    global $yhtiorow, $kukarow;

    // Luodaan muuttujat
    extract($params);

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Kerayslista-".md5(uniqid(mt_rand(), true)).".pdf";

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

if (!function_exists('tulosta_tyomaaraystarra_zebra')) {
  function tulosta_tyomaaraystarra_zebra($laskurow, $komento, $tkpl) {

    global $kukarow;

    //Kun tullaan tänne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin
    //ja tarvitaan myös $laskurow ja $tkpl

    $nimitys = trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']);
    $nimitys2 = "";

    // tässä tehdään aineisto jota Zebra tulostin tajuaa
    /* Esimerkki
    D15
    N
    B125,0,0,3,2,5,45,B,"HC3310CPT40H "
    A560,0,0,2,1,2,N,
    A120,75,0,3,1,2,N,"PALL PROFILE STAR ""  ""         0"
    A120,115,0,3,1,2,N,"SUODATINPANOS "
    P1
    */

    // Toimii ja suunniteltu mallille GX420t
    // Tarrakoko 76 mm x 25 mm
    $pituus = 50; // voidaan määrittää mistä kohdasta katkaistaan teksti.

    if (strlen($nimitys) > $pituus ) {
      if (strpos($nimitys, " ")) {
        $nimipalat = explode(' ', $nimitys);

        $merkkimaara = 0;
        $nimitys = "";

        foreach ($nimipalat as $nimipala) {
          if (strlen($nimitys)+strlen($nimipala) <= $pituus or $merkkimaara == 0) {
            $nimitys .= $nimipala." ";
          }
          else {
            $nimitys2 .= $nimipala." ";
          }
          $merkkimaara += strlen($nimipala);
        }
      }

      $nimitys = substr($nimitys, 0, $pituus);
      $nimitys2 = $nimitys2 != "" ? substr($nimitys2, 0, $pituus) : "";
    }

    $sivu = "^XA\n";    // vakio alku, pakollinen
    $sivu .= "^LH50\n";  // offset vasemmasta
    $sivu .= "^LT000\n";  // offset ylhäältä
    $sivu .= "^FO85,20\n^ASN,20,12\n^FDTyömääräin: {$laskurow['tunnus']}\n^FS";
    $sivu .= "^FO85,65\n^AQN,18,8\n^FD$nimitys\n^FS";
    $sivu .= "^FO85,88\n^AQN,18,8\n^FD$nimitys2\n^FS";
    $sivu .= "^MD10";                        // TUMMUUS, vakio on 8 mutta se ei riitä viivakoodille.
    $sivu .= "^PQ$tkpl";                    // Tulostettavien lukumäärä
    $sivu .= "^FO80,120\n^BCN,40,Y,N,N\n^FD{$laskurow['tunnus']}\n^FS";    // viivakoodi CODE-128

    $sivu .= "\n^XZ";  // pakollinen lopetus

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $filenimi = "/tmp/Zebra-tyomaaraystarra-".md5(uniqid(mt_rand(), true)).".txt";
    $fh = file_put_contents($filenimi, $sivu);

    $line = exec("$komento $filenimi");
    system("rm -f $filenimi");
  }
}
