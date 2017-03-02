<?php


//viivakoodien tulostamista varten
define(__TRACE_ENABLED__, false);
define(__DEBUG_ENABLED__, false);

require_once "barcode/barcode.php";
require_once "barcode/c39object.php";
require_once "pdflib/phppdflib.class.php";

if ($kieli== '') {
  $querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
  $kielresult = pupe_query($querykiel);
  $kielnum = mysql_num_rows($kielresult);
  $kielrow = mysql_fetch_array($kielresult);
  $kieli = strtolower($kielrow['kieli']);
}

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

$pdf = new pdffile;
$pdf->set_default('margin', 0);
$rectparam["width"] = 0.3;

if (!function_exists('alku')) {
  function alku() {
    global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kieli, $kala;

    //PDF parametrit
    if (!isset($pdf)) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
    }

    $thispage = $pdf->new_page("a4");

    //oletukset viivakoodeille
    $output   = "png";
    $width    = "140";
    $height   = "20";
    $xres     = "1";
    $font     = "2";
    $drawtext = "off";

    $style  = BCS_ALIGN_CENTER;
    $style |= ($output  == "png" ) ? BCS_IMAGE_PNG  : 0;
    $style |= ($output  == "jpeg") ? BCS_IMAGE_JPEG : 0;
    $style |= ($border  == "on"  ) ? BCS_BORDER     : 0;
    $style |= ($drawtext== "on"  ) ? BCS_DRAW_TEXT  : 0;
    $style |= ($stretchtext== "on" ) ? BCS_STRETCH_TEXT  : 0;
    $style |= ($negative== "on"  ) ? BCS_REVERSE_COLOR  : 0;

    unset($data);
    if ( (int) $yhtiorow["lasku_logo"] > 0) {
      $liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
      $data = $liite["data"];
      $isizelogo[0] = $liite["image_width"];
      $isizelogo[1] = $liite["image_height"];
      unset($liite);
    }
    elseif (file_exists($yhtiorow["lasku_logo"])) {
      $filename = $yhtiorow["lasku_logo"];

      $fh = fopen($filename, "r");
      $data = fread($fh, filesize($filename));
      fclose($fh);

      $isizelogo = getimagesize($yhtiorow["lasku_logo"]);
    }

    if ($data) {
      $image = $pdf->jfif_embed($data);

      if (!$image) {
        echo t("Logokuvavirhe");
      }
      else {

        $logoparam = array();

        if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
          $logoparam['scale'] = 240 / $isizelogo[0];
        }
        else {
          $logoparam['scale'] = 50  / $isizelogo[1];
        }

        $placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $thispage, $logoparam);
      }
    }
    else {
      $pdf->draw_text(30, 815, $yhtiorow["nimi"],   $thispage, $norm);
      $pdf->draw_text(30, 805, $yhtiorow["osoite"],   $thispage, $norm);
      $pdf->draw_text(30, 795, $yhtiorow["postino"]." ".$yhtiorow["postitp"],   $thispage, $norm);
      $pdf->draw_text(30, 785, $yhtiorow["maa"],     $thispage, $norm);
    }

    $pdf->draw_text(310, 815, t("Vahvistamattomat ostotilausrivit", $kieli),       $thispage, $iso);
    $pdf->draw_text(310, 803, t("Sivu", $kieli),         $thispage, $norm);

    //Vasen sarake
    $pdf->draw_text(50, 729, t("Toimittaja", $kieli),   $thispage, $pieni);
    $pdf->draw_text(50, 717, $laskurow["nimi"],       $thispage, $norm);
    $pdf->draw_text(50, 707, $laskurow["nimitark"],      $thispage, $norm);
    $pdf->draw_text(50, 697, $laskurow["osoite"],       $thispage, $norm);
    $pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
    $pdf->draw_text(50, 677, $laskurow["maa"],         $thispage, $norm);

    $query = "SELECT *
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$laskurow[liitostunnus]'
              LIMIT 1";
    $rarres = pupe_query($query);
    $toimirow = mysql_fetch_array($rarres);

    //Oikea sarake
    $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 792, t("Tilausnumero", $kieli),   $thispage, $pieni);
    $pdf->draw_text(430, 792, t("Toimittajan fax", $kieli), $thispage, $pieni);
    $pdf->draw_text(430, 782, $toimirow["fax"],        $thispage, $norm);

    $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 771, t("Y-tunnus", $kieli),     $thispage, $pieni);

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

    $pdf->draw_text(310, 761, $uytunnus,                 $thispage, $norm);
    $pdf->draw_text(430, 771, t("Päiväys", $kieli),           $thispage, $pieni);
    $pdf->draw_text(430, 761, tv1dateconv(date("Y-m-d")),         $thispage, $norm);

    $pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 750, t("Asiaa hoitaa", $kieli),         $thispage, $pieni);
    $pdf->draw_text(310, 740, $kukarow["nimi"],             $thispage, $norm);

    $pdf->draw_text(430, 750, t("Toimitusaika", $kieli),        $thispage, $pieni);

    $pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);

    $pdf->draw_text(310, 729, t("Yhteyshenkilö", $kieli),        $thispage, $pieni);

    $pdf->draw_text(430, 729, t("Kuljetus", $kieli),           $thispage, $pieni);

    $pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 708, t("Toimitusehto", $kieli),         $thispage, $pieni);


    $pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 687, t("Huolitsija", $kieli),           $thispage, $pieni);


    $pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 666, t("Maksuehto", $kieli),           $thispage, $pieni);


    $pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 645, t("Jakelu", $kieli),             $thispage, $pieni);


    $pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 624, t("Tilausviite", $kieli),         $thispage, $pieni);

    $kala = 565;

    $pdf->draw_rectangle($kala+30, 20, 70, 580, $thispage, $rectparam);

    $pdf->draw_rectangle($kala+30, 20,  $kala+10, 95,    $thispage, $rectparam);
    $pdf->draw_rectangle($kala+30, 95,  $kala+10, 290,   $thispage, $rectparam);
    $pdf->draw_rectangle($kala+30, 290, $kala+10, 350,   $thispage, $rectparam);
    $pdf->draw_rectangle($kala+30, 350, $kala+10, 420,   $thispage, $rectparam);
    $pdf->draw_rectangle($kala+30, 420, $kala+10, 480,   $thispage, $rectparam);
    $pdf->draw_rectangle($kala+30, 480, $kala+10, 520,   $thispage, $rectparam);
    $pdf->draw_rectangle($kala+30, 520, $kala+10, 580,   $thispage, $rectparam);
    $pdf->draw_text(25,  $kala+15, t("Tilausnumero", $kieli),  $thispage, $norm);
    $pdf->draw_text(100,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
    $pdf->draw_text(292, $kala+15, t("Toimitusaika", $kieli),  $thispage, $norm);
    $pdf->draw_text(352, $kala+15, t("Määrä", $kieli),      $thispage, $norm);
    $pdf->draw_text(422, $kala+15, t("Hinta", $kieli),      $thispage, $norm);
    $pdf->draw_text(482, $kala+15, t("Ale", $kieli),      $thispage, $norm);
    $pdf->draw_text(522, $kala+15, t("Yhteensä", $kieli),    $thispage, $norm);

    return $thispage;
  }
}

if (!function_exists('rivi')) {
  function rivi($thispage) {
    global $pdf, $laskurow, $row, $yhtiorow, $kukarow, $sivu, $page, $rectparam, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kieli, $kala, $nimitykset, $rivinumerot, $toim;

    // viivat rivien väliin...
    $x[0] = 20;
    $x[1] = 580;
    $y[0] = $y[1] = $kala + 15 - 5;

    $pdf->draw_line($x, $y, $thispage, $rectparam);

    // Jos on paljon rivejä tehdään uusi otsikko...
    if ($kala <= 100) {
      $sivu++;

      loppu($thispage, 0, 0);

      $page[$sivu] = alku();
      $thispage    = $page[$sivu];
    }

    $pdf->draw_text(30,  $kala, $row["otunnus"],                  $thispage, $norm);

    $pdf->draw_text(105,  $kala, $row["toim_tuoteno"],                            $thispage, $norm);
    $pdf->draw_text(205, $kala, "(".$row["tuoteno"].")",                          $thispage, $norm);

    $pdf->draw_text(295, $kala, tv1dateconv($row["toimaika"]),                        $thispage, $norm);

    $oikpos = $pdf->strlen((float) $row["tilkpl"]."/".(float) $row["ulkkpl"]. ' ' .$row['omyks'] . '/'. $row['toyks'], $norm);
    $pdf->draw_text(415-$oikpos, $kala, (float) $row["tilkpl"]."/".(float) $row["ulkkpl"].' ' . $row['omyks'] . '/' . $row['toyks'],   $thispage, $norm);

    $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
    $pdf->draw_text(480-$oikpos, $kala, hintapyoristys($row["hinta"]),     $thispage, $norm);

    if ($row["ale1"] != 0) {
      $oikpos = $pdf->strlen(($row["ale1"]*1)."%", $norm);
      $pdf->draw_text(515-$oikpos, $kala, ($row["ale1"]*1)."%",                       $thispage, $norm);
    }

    $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
    $pdf->draw_text(580-$oikpos, $kala, hintapyoristys($row["rivihinta"]),   $thispage, $norm);

    $kala = $kala - 15;
  }
}

if (!function_exists('loppu')) {
  function loppu($thispage, $total, $laitetaanko_yhteensarivi) {
    global $pdf, $laskurow, $yhtiorow, $kukarow, $page, $sivu, $rectparam, $kieli, $kala, $norm, $pieni;

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

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",                     $thispage, $pieni);
    $pdf->draw_text(454, 55, $uytunnus,                             $thispage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",                   $thispage, $pieni);
    $pdf->draw_text(454, 45, $yhtiorow["kotipaikka"],                  $thispage, $pieni);
    $pdf->draw_text(404, 35,  t("Pankkiyhteys", $kieli).":",              $thispage, $pieni);
    $pdf->draw_text(454, 35,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],  $thispage, $pieni);

    if ($laitetaanko_yhteensarivi == 1 and $total != 0) {
      $pdf->draw_text(430, 82, t("Yhteensä", $kieli).":",               $thispage, $norm);

      $oikpos = $pdf->strlen(sprintf('%.2f', $total)." ".$laskurow["valkoodi"], $norm);
      $pdf->draw_text(555-$oikpos, 82, sprintf('%.2f', $total)." ".$laskurow["valkoodi"], $thispage, $norm);
    }

    if ($laitetaanko_yhteensarivi != 1) {
      $pdf->draw_text(510, 82, t("Jatkuu", $kieli)."...",               $thispage, $norm);
    }

    if ($laitetaanko_yhteensarivi == 1) {
      // viivat rivien väliin...
      $x[0] = 20;
      $x[1] = 580;
      $y[0] = $y[1] = $kala + 15 - 4;

      $pdf->draw_line($x, $y, $thispage, $rectparam);

      for ($pp=1; $pp<=$sivu; $pp++) {
        $pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
      }
    }
  }
}

if (!function_exists('print_pdf')) {
  function print_pdf() {
    global $pdf, $kukarow, $yhtiorow, $komento, $kieli, $tee;

    if ($komento["Ostotilaus"] != '' or $tee == 'NAYTATILAUS') {
      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/Vahvistamattomat-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdffilenimi, "w+");
      if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
      fclose($fh);

      // itse print komento...
      if ($komento["Ostotilaus"] == 'email') {
        $liite = $pdffilenimi;
        $kutsu = "Ostotilaus";

        require "inc/sahkoposti.inc";
        echo t("Vahvistamattomat rivit lähetetty sähköpostiin")."...<br><br>";
      }

      $tee = "";
    }

    return $pdffilenimi;
  }
}

unset($pdf);
unset($page);

$sivu  = 1;
$total = 0;

// Aloitellaan lähetteen teko
$page[$sivu] = alku();

foreach ($vahvistamattomat_rivit as $row) {
  //count koska array:ssa on myös total amount jonka ei haluta printtaantuvan
  if (count($row) > 2) {
    rivi($page[$sivu]);
  }
}
$total = $vahvistamattomat_rivit['total_rivihinta'];

//Viimeiselle sivulle myös lopputekstit
loppu($page[$sivu], $total, 1);

//kutsutaan lopuksi vielä print_pdf funktiota
$pdffilenimi = print_pdf();

system("rm -f $pdffilenimi");
