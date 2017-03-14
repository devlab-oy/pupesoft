<?php

if (@include_once 'PEAR.php') {
  require_once "viivakoodi/Barcode.php";
}

require_once "pdflib/phppdflib.class.php";

$norm["height"]     = 10;
$norm["font"]       = "Times-Roman";

$pieni["height"]     = 8;
$pieni["font"]       = "Times-Roman";

$boldi["height"]     = 10;
$boldi["font"]       = "Times-Bold";

$pieni_boldi["height"]   = 8;
$pieni_boldi["font"]   = "Times-Bold";

$iso["height"]       = 14;
$iso["font"]       = "Helvetica-Bold";

$rectparam["width"]   = 0.3;
$rivinkorkeus      = 17;

if (!function_exists('alku_valm')) {
  function alku_valm() {
    global $pdf, $sivu, $laskurow, $yhtiorow, $viite, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kala, $rectparam, $kukarow, $kieli, $asrow, $tilausnumeroita;

    //PDF parametrit
    if (!isset($pdf)) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
    }

    if ($laskurow["tilaustyyppi"] == "W") {
      return uusi_sivu_valm();
    }
    else {

      $thispage = $pdf->new_page("a4");

      $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

      tulosta_logo_pdf($pdf, $thispage, $laskurow);

      // haetaan maksuehdon tiedot
      $query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
      $masresult = pupe_query($query);
      $masrow = mysql_fetch_array($masresult);

      //maksuehto tekstinä
      $maksuehto = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

      $pdf->draw_text(410, 815, $laskurow["hyvaksynnanmuutos"],   $thispage, $iso);

      $pdf->draw_text(310, 815, t("Valmistuslista", $kieli),     $thispage, $iso);

      $pdf->draw_text(310, 803, t("Sivu", $kieli),         $thispage, $norm);

      //Vasen sarake
      //$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
      $pdf->draw_text(50, 729, t("Ostaja", $kieli),   $thispage, $pieni);
      $pdf->draw_text(50, 717, $laskurow["nimi"],       $thispage, $norm);
      $pdf->draw_text(50, 707, $laskurow["nimitark"],      $thispage, $norm);
      $pdf->draw_text(50, 697, $laskurow["osoite"],       $thispage, $norm);
      $pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
      $pdf->draw_text(50, 677, $laskurow["maa"],         $thispage, $norm);


      //$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
      $pdf->draw_text(50, 656, t("Toimitusosoite", $kieli),   $thispage, $pieni);
      $pdf->draw_text(50, 644, $laskurow["toim_nimi"],     $thispage, $norm);
      $pdf->draw_text(50, 634, $laskurow["toim_nimitark"],   $thispage, $norm);
      $pdf->draw_text(50, 624, $laskurow["toim_osoite"],     $thispage, $norm);
      $pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
      $pdf->draw_text(50, 604, $laskurow["toim_maa"],     $thispage, $norm);


      //Oikea sarake
      $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli),       $thispage, $pieni);
      $pdf->draw_text(310, 782, $tilausnumeroita,              $thispage, $boldi);

      $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 771, t("Keräysaika", $kieli),           $thispage, $pieni);

      if ($laskurow["keraysvko"] != '') {
        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["kerayspvm"]));

        if ($laskurow['keraysvko'] != '7') {
          $taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
        }

        $pdf->draw_text(310, 761, $taika,                 $thispage, $boldi);
      }
      else {
        $pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]),   $thispage, $boldi);
      }

      $pdf->draw_text(430, 771, t("Toimitusaika", $kieli),         $thispage, $pieni);

      if ($laskurow["toimvko"] != '') {
        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        $pdf->draw_text(430, 761, $taika,                 $thispage, $boldi);
      }
      else {
        $pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]),   $thispage, $boldi);
      }

      if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];

      $pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 750, t("Asiakasnumero", $kieli),     $thispage, $pieni);
      $pdf->draw_text(310, 740, $asrow["asiakasnro"],       $thispage, $norm);
      $pdf->draw_text(430, 750, t("Päiväys", $kieli),        $thispage, $pieni);
      $pdf->draw_text(430, 740, tv1dateconv(date("Y-m-d")),     $thispage, $norm);

      $pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 729, t("Myyjä", $kieli),     $thispage, $pieni);

      //etsitään myyjän nimi
      $query  = "SELECT nimi
                 FROM kuka
                 WHERE tunnus = '$laskurow[myyja]'
                 AND yhtio    = '$kukarow[yhtio]'";
      $myyresult = pupe_query($query);
      $myyrow = mysql_fetch_array($myyresult);

      $pdf->draw_text(310, 719, $myyrow["nimi"],                     $thispage, $norm);

      $pdf->draw_text(430, 729, t("Tilauspvm", $kieli),                 $thispage, $pieni);
      $pdf->draw_text(430, 719, tv1dateconv($laskurow["luontiaika"], "pitka"),     $thispage, $norm);

      $ttapae = $laskurow["toimitustapa"];

      //etsitään löytyykö rahtisopimusta
      $rahtirow = hae_rahtisopimusnumero($ttapae, $laskurow["ytunnus"], $laskurow["liitostunnus"], false, $laskurow["rahtisopimus"]);

      if ($rahtirow["rahtisopimus"] != "") {
        $ttapae .= " / ".$rahtirow["rahtisopimus"];
      }
      if ($laskurow["rahtivapaa"] == 'o' and onko_rahtituote_syotetty($laskurow) == 0) {
        $ttapae .= " / ". t("Rahtivapaa", $kieli);
      }

      $pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 708, t("Toimitustapa", $kieli)." / ".t("Rahtisopimus", $kieli), $thispage, $pieni);
      $pdf->draw_text(310, 698, $ttapae, $thispage, $boldi);

      $pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 687, t("Toimitusehto", $kieli),   $thispage, $pieni);
      $pdf->draw_text(310, 677, $laskurow["toimitusehto"],   $thispage, $norm);

      $pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 666, t("Maksuehto", $kieli),     $thispage, $pieni);
      $pdf->draw_text(310, 656, $maksuehto,           $thispage, $norm);

      $pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 645, "", $thispage, $pieni);
      $pdf->draw_text(310, 635, "", $thispage, $norm);

      $pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 624, "",   $thispage, $pieni);
      $pdf->draw_text(310, 614, "",   $thispage, $norm);

      $komm = "";

      if (trim($laskurow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
      }

      if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
        $komm .= "\n".t("Asiakkaan tilausnumero", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
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

      if (trim($laskurow['sisviesti2']) != '') {
        $komm .= "\n".t("Kommentti", $kieli)." 2:###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti2']), 90, "\n###");
      }

      //Tulostetaan laskun kommentti
      if (trim($komm) != '') {
        $kommentit = explode("\n", trim($komm));

        $pohja  = 577;
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

          if (strpos($kommentti, "###") !== false) {
            list($o, $v) = explode("###", $kommentti);

            $pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
            $pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
          }
          else {
            $pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
          }

          $pohja = $pohja - 10;
        }
        $kala = $pohja-30;
      }
      else {
        $kala = 560;
      }

      if (class_exists("Image_Barcode")) {
        //viivakoodi
        $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

        imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

        $fh = fopen($nimi, "r");
        $data = fread($fh, filesize($nimi));
        fclose($fh);

        $image = $pdf->jfif_embed($data);
        $pdf->image_place($image, 805, 440, $thispage);

        system("rm -f $nimi");
      }

      rivi_otsikot_valm($thispage);

      return $thispage;
    }
  }
}

if (!function_exists('uusi_sivu_valm')) {
  function uusi_sivu_valm() {
    global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $iso, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala, $tilausnumeroita;

    $thispage = $pdf->new_page("a4");

    $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

    $logoloppuy = tulosta_logo_pdf($pdf, $thispage, $laskurow);
    $logoloppuy -= 10;

    //Vasen sarake
    $pdf->draw_text(35, $logoloppuy, $laskurow["nimi"],     $thispage, $norm);
    $pdf->draw_text(35, $logoloppuy-10, $laskurow["nimitark"],  $thispage, $norm);
    $pdf->draw_text(35, $logoloppuy-20, $laskurow["osoite"],   $thispage, $norm);
    $pdf->draw_text(35, $logoloppuy-30, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
    $pdf->draw_text(35, $logoloppuy-40, $laskurow["maa"],     $thispage, $norm);

    $logoloppuy -= 55;

    // haetaan maksuehdon tiedot
    $query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $masresult = pupe_query($query);
    $masrow = mysql_fetch_array($masresult);

    //maksuehto tekstinä
    $maksuehto = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

    $pdf->draw_text(410, 815, $laskurow["hyvaksynnanmuutos"],       $thispage, $iso);

    $pdf->draw_text(310, 815, t("Valmistuslista", $kieli),         $thispage, $iso);

    $pdf->draw_text(310, 803, t("Sivu", $kieli),             $thispage, $norm);

    //Oikea sarake
    $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli),       $thispage, $pieni);
    $pdf->draw_text(310, 782, $tilausnumeroita,              $thispage, $boldi);

    $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 771, t("Keräysaika", $kieli),           $thispage, $pieni);

    if ($laskurow["keraysvko"] != '') {
      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["kerayspvm"]));

      if ($laskurow['keraysvko'] != '7') {
        $taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
      }

      $pdf->draw_text(310, 761, $taika,                 $thispage, $boldi);
    }
    else {
      $pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]),   $thispage, $boldi);
    }

    $pdf->draw_text(430, 771, t("Toimitusaika", $kieli),         $thispage, $pieni);

    if ($laskurow["toimvko"] != '') {
      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

      if ($laskurow['toimvko'] != '7') {
        $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
      }

      $pdf->draw_text(430, 761, $taika,                 $thispage, $boldi);
    }
    else {
      $pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]),   $thispage, $boldi);
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

    if (trim($laskurow['sisviesti2']) != '') {
      $komm .= "\n".t("Kommentti", $kieli)." 2:###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti2']), 90, "\n###");
    }

    //Tulostetaan laskun kommentti
    if (trim($komm) != '') {
      $kommentit = explode("\n", trim($komm));

      $pohja  = $logoloppuy;
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

        if (strpos($kommentti, "###") !== false) {
          list($o, $v) = explode("###", $kommentti);

          $pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
          $pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
        }
        else {
          $pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
        }

        $pohja = $pohja - 10;
      }
      $kala = $pohja-30;
    }
    else {
      $pohja  = $logoloppuy;
    }


    if (class_exists("Image_Barcode")) {
      //viivakoodi
      $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

      imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

      $fh = fopen($nimi, "r");
      $data = fread($fh, filesize($nimi));
      fclose($fh);

      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, 805, 440, $thispage);

      system("rm -f $nimi");
    }

    $kala = $pohja-30;

    rivi_otsikot_valm($thispage);

    return $thispage;
  }
}

if (!function_exists('rivi_otsikot_valm')) {
  function rivi_otsikot_valm($thispage) {
    global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;

    $pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);

    $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  $thispage,   $rectparam);
    $pdf->draw_rectangle($kala+30, 40,  $kala+10, 120, $thispage,   $rectparam);
    $pdf->draw_rectangle($kala+30, 120, $kala+10, 400, $thispage,   $rectparam);
    $pdf->draw_rectangle($kala+30, 400, $kala+10, 460, $thispage,   $rectparam);
    $pdf->draw_rectangle($kala+30, 460, $kala+10, 520, $thispage,   $rectparam);
    $pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage,   $rectparam);

    $pdf->draw_text(25,  $kala+15, "#",                $thispage, $norm);
    $pdf->draw_text(45,  $kala+15, t("Paikka", $kieli),        $thispage, $norm);
    $pdf->draw_text(125, $kala+15, t("Tuotenumero/Tuotenimi", $kieli),  $thispage, $norm);

    $pdf->draw_text(405, $kala+15, t("Kerroin", $kieli),    $thispage, $norm);
    $pdf->draw_text(465, $kala+15, t("Valmistetaan", $kieli),  $thispage, $norm);
    $pdf->draw_text(525, $kala+15, t("Valmistettu", $kieli),  $thispage, $norm);

    $kala -= 3;
  }
}

if (!function_exists('rivi_valm')) {
  function rivi_valm($thispage) {
    global $pdf, $page, $sivu, $row, $kala, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $yhtiorow, $perheid, $perheid2, $norm, $pieni, $rivinkorkeus, $paino, $uusisivu;

    static $tuoteperhe_omallesivulle = "";

    // viivat rivien väliin...
    $x[0] = 20;
    $x[1] = 580;
    $y[0] = $y[1] = $kala + $rivinkorkeus - 4;
    $uusisivu = "";

    if ($row["tunnus"] == $row["perheid"] or $row["tunnus"] == $row["perheid2"]) {
      //  Tulostetaanko aina uusi sivu?
      $query = "SELECT tunnus FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'R' and isatuoteno = '$row[tuoteno]' and omasivu != ''";
      $pres = pupe_query($query);

      if (mysql_num_rows($pres) > 0) {
        //  Uusi sivu
        if (isset($perheid) and isset($perheid2)) {
          $page[$sivu]   = uusi_sivu_valm();
          $thispage      = $page[$sivu];
        }

        $tuoteperhe_omallesivulle = "KYLLA";
        $uusisivu = "KOHTA";
      }
      else {
        $tuoteperhe_omallesivulle = "";
      }
    }

    if (isset($perheid) and isset($perheid2) and (($perheid == 0 and $perheid2 == 0) or $perheid != $row["perheid"] or $perheid2 != $row["perheid2"])) {
      if ($uusisivu == "JOO") {
        //  Uusi sivu
        $page[$sivu]  = uusi_sivu_valm();
        $thispage      = $page[$sivu];
        $uusisivu     = "";
      }
      elseif ($uusisivu == "KOHTA") {
        $uusisivu     = "JOO";
      }
      else {
        $pdf->draw_line($x, $y, $thispage, $rectparam);
      }
    }
    elseif ($tuoteperhe_omallesivulle == "KYLLA") {
      $pdf->draw_line($x, $y, $thispage, $rectparam);
    }

    //Haetan sarjanumeron tiedot
    if ($row["sarjanumeroseuranta"] != "") {

      if ($row["tyyppi"] == "V") {
        $tunken = "myyntirivitunnus";
      }
      else {
        $tunken = "ostorivitunnus";
      }

      $query = "SELECT DISTINCT sarjanumero, parasta_ennen
                FROM sarjanumeroseuranta
                WHERE yhtio      = '$kukarow[yhtio]'
                and tuoteno      = '$row[tuoteno]'
                and $tunken      = '$row[tunnus]'
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

    if ($row["tunnus"] == $row["perheid"] and $laskurow['asiakkaan_tilausnumero'] != "") {
      $row["kommentti"] .= "\n".t("Asiakkaan tilausnumero", $kieli).": ".$laskurow['asiakkaan_tilausnumero'];
    }

    if ($yhtiorow["pura_osaluettelot"] != "") {
      // Näytetäänkö osaluettelo
      $query = "SELECT tuote.tuoteno, tuote.yksikko, tuote.nimitys,
                tuoteperhe.kerroin
                FROM tuoteperhe
                JOIN tuote ON (tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tuoteno=tuote.tuoteno)
                WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                AND tuoteperhe.isatuoteno = '$row[tuoteno]'
                AND tuoteperhe.tyyppi     = 'O'
                ORDER BY tuote.tuoteno";
      $tuoteperhe_result = pupe_query($query);

      if (mysql_num_rows($tuoteperhe_result) > 0) {
        while ($tuoteperhe_row = mysql_fetch_assoc($tuoteperhe_result)) {
          $row["kommentti"] .= ((float) $tuoteperhe_row["kerroin"])." x $tuoteperhe_row[tuoteno] - $tuoteperhe_row[nimitys]\n";
        }
      }
    }

    // Mahtuuko rivi tälle sivulle
    $kala_nyt = $kala;

    rivi_valmistus($devnull);

    // Jos on paljon rivejä tehdään uusi otsikko...
    if ($kala <= 65) {
      $sivu++;

      loppu_valm($thispage, 0);

      $page[$sivu] = uusi_sivu_valm();
      $thispage    = $page[$sivu];
    }
    else {
      $kala = $kala_nyt;
    }

    $rivinkorkeus  = 15;

    rivi_valmistus($thispage);
  }
}

if (!function_exists('rivi_valmistus')) {
  function rivi_valmistus($thispage) {
    global $pdf, $page, $sivu, $row, $kala, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $yhtiorow, $perheid, $perheid2, $norm, $pieni, $rivinkorkeus, $paino, $isakpl;

    $p["height"]  = 10;
    $p["font"]    = "Times-Roman";

    $pp["height"]  = 8;
    $pp["font"]    = "Times-Roman";

    $varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];

    if ($row['tyyppi'] == 'W' or $row['tyyppi'] == 'M') {
      $isakpl  = $row["tilkpl"];
      $lapskpl = ' ';

      $pb["height"]  = 10;
      $pb["font"]    = "Times-Bold";
    }
    else {
      $pb["height"]  = 10;
      $pb["font"]    = "Times-Roman";

      if ((float) $isakpl == 0) {
        $lapskpl = $row["tilkpl"];
      }
      else {
        $lapskpl = round($row["tilkpl"]/$isakpl, 8);
      }
    }

    if ($row['tyyppi'] == 'V' and !empty($row['ed_tyyppi']) and $row['ed_tyyppi'] != $row['tyyppi']) {
      $pdf->draw_text(35,  $kala, "Raaka-aineet:",  $thispage, $p);
      $kala -= $rivinkorkeus;
    }

    $pdf->draw_text(25,  $kala, $rivinumerot[$row["tunnus"]],                $thispage, $pp);
    $pdf->draw_text(45,  $kala, $varastopaikka,                        $thispage, $pb);

    $pdf->draw_text(125, $kala, $row["tuoteno"],                      $thispage, $pb);
    $pdf->draw_text(405, $kala, $lapskpl,                           $thispage, $pb);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $pb);
    $pdf->draw_text(515-$oikpos, $kala, $row["tilkpl"]." ".$omyks,     $thispage, $pb);

    if ($row["toimitettu"] != '') {
      $oikpos = $pdf->strlen($row['varattu'], $pb);
      $pdf->draw_text(575-$oikpos, $kala, $row['varattu'],       $thispage, $pb);
    }

    $kala = $kala - $rivinkorkeus+3;
    $lask_valm++;

    $pdf->draw_text(125, $kala, t_tuotteen_avainsanat($row, 'nimitys'),  $thispage, $pb);
    $kala -= $rivinkorkeus;

    // Ei piirretä kommenttia, kun testataan mahtuuko, koska pitkä komentti osaa vaihtaa sivua ihan itsekin
    if (!empty($thispage) and trim($row["kommentti"]) != '') {

      $komm = t("Kommentti", $kieli).":\n".trim($row["kommentti"]);

      foreach (explode("\n", wordwrap(trim($komm), 90, "\n")) as $kommentti) {

        if ($kala <= 85) {
          $sivu++;

          loppu_valm($thispage, 0);

          $page[$sivu] = uusi_sivu_valm();
          $thispage    = $page[$sivu];
        }

        $pdf->draw_text(125, $kala, $kommentti, $thispage, $p);
        $kala -= $rivinkorkeus;
        $lask_valm++;
      }
    }

    if ($row["tunnus"] == $row["perheid"]) {
      $query = "SELECT fakta
                FROM tuoteperhe
                WHERE yhtio    = '$kukarow[yhtio]'
                and tyyppi     = 'R'
                and isatuoteno = '$row[tuoteno]'
                and trim(fakta) != ''
                ORDER BY LENGTH(fakta) desc
                LIMIT 1";
      $ressu = pupe_query($query);

      if (mysql_num_rows($ressu) == 1) {
        $faktarow = mysql_fetch_array($ressu);

        $faktarow["fakta"] = t("Työohje", $kieli).":\n".trim($faktarow["fakta"]);

        if ($row['valmistuslinja'] > 0) {
          $tuotantolinja = $row['valmistuslinja']. " - ". t_avainsana('valmistuslinja', $kieli, "and avainsana.selite='$row[valmistuslinja]'", "", "", "selitetark");
          $faktarow["fakta"] = t("Tuotantolinja", $kieli).": ".$tuotantolinja."\n".$faktarow["fakta"];
        }

        foreach (explode("\n", wordwrap($faktarow["fakta"], 90, "\n")) as $kommentti) {
          $pdf->draw_text(125,  $kala, $kommentti, $thispage, $p);
          $kala -= $rivinkorkeus;
          $lask_valm++;
        }
      }
      else {
        $lask_valm++;
      }
    }

    $perheid  = $row["perheid"];
    $perheid2 = $row["perheid2"];
  }
}

if (!function_exists('loppu_valm')) {
  function loppu_valm($thispage, $tots) {
    global $pdf, $sivu, $laskurow, $viite, $pieni, $norm, $rectparam, $kieli, $yhtiorow, $kala, $rivinkorkeus, $page, $paino;

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(70, 20, 20, 580,  $thispage, $rectparam);
    $pdf->draw_rectangle(70, 207, 20, 580,  $thispage, $rectparam);
    $pdf->draw_rectangle(70, 394, 20, 580,  $thispage, $rectparam);

    $pdf->draw_text(30, 55, $yhtiorow["nimi"],    $thispage, $pieni);
    $pdf->draw_text(30, 45, $yhtiorow["osoite"],  $thispage, $pieni);
    $pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],  $thispage, $pieni);
    $pdf->draw_text(30, 25, $yhtiorow["maa"],    $thispage, $pieni);

    $pdf->draw_text(217, 55, t("Puhelin", $kieli).":",      $thispage, $pieni);
    $pdf->draw_text(257, 55, $yhtiorow["puhelin"],        $thispage, $pieni);
    $pdf->draw_text(217, 45, t("Fax", $kieli).":",      $thispage, $pieni);
    $pdf->draw_text(257, 45, $yhtiorow["fax"],          $thispage, $pieni);
    $pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",    $thispage, $pieni);
    $pdf->draw_text(257, 35, $yhtiorow["email"],        $thispage, $pieni);

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

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",      $thispage, $pieni);
    $pdf->draw_text(444, 55, $uytunnus,              $thispage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",    $thispage, $pieni);
    $pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],      $thispage, $pieni);

    if ($tots != 1) {
      $pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...",   $thispage, $norm);
    }

    if ($tots == 1) {
      if ($paino != 0) {
        $pdf->draw_text(440, 25, t("Tilauksen paino", $kieli).":",  $thispage, $norm);
        $oikpos = $pdf->strlen($paino." kg", $boldi);
        $pdf->draw_text(575-$oikpos, 25, $paino." kg",        $thispage, $boldi);
      }

      // viivat rivien väliin...
      $x[0] = 20;
      $x[1] = 580;
      $y[0] = $y[1] = $kala + $rivinkorkeus - 4;

      $pdf->draw_line($x, $y, $thispage, $rectparam);

      for ($pp=1; $pp<=$sivu; $pp++) {
        $pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
      }
    }
  }
}

if (!function_exists('print_pdf_valm')) {
  function print_pdf_valm($komento) {
    global $pdf, $kukarow, $yhtiorow, $kateinen, $returnvalue, $kieli, $tee;

    $returnvalue=0;

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
      $kutsu = t("Valmistuslista", $kieli);
      echo t("Valmistuslista tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Valmistuslista tulostuu")."...<br>";
      $line = exec("$komento $pdffilenimi", $output, $returnvalue);
    }
  }
}
