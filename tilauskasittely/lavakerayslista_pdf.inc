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

$iso_boldi["height"] = 24;
$iso_boldi["font"] = "Helvetica-Bold";

$rectparam["width"] = 0.3;

if (!function_exists('otsikot_lavakerayslista')) {
  function otsikot_lavakerayslista($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // sitten aletaan piirt�m��n itse PDF sis�lt��
    $pdf->draw_text(30, 550, "Lavaker�yslista", $firstpage, $iso_boldi);
    $pdf->draw_text(50, 512, "Tilausnumero", $firstpage, $boldi);
    $pdf->draw_text(150, 512, "Asiakkaan tilausnumero", $firstpage, $boldi);

    tulosta_logo_pdf($pdf, $firstpage, "");

    return compact(array_keys($params));
  }
}

if (!function_exists('sivunvaihto')) {
  function sivunvaihto($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    if ($kala < $sivunvaihto) {
      $kala = 500;
      $pdf->draw_rectangle(580, 15, 15, 400,  $firstpage, $rectparam);
      $firstpage = $pdf->new_page("a5");
      $params = otsikot_lavakerayslista(compact(array_keys($params)));
      extract($params);
    }

    return compact(array_keys($params));
  }
}


if (!function_exists('sivu_lavakerayslista')) {
  function sivu_lavakerayslista($params) {
    global $kukarow, $yhtiorow;

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

    $firstpage = $pdf->new_page("a5");

    // sitten aletaan piirt�m��n itse PDF sis�lt��
    $params = otsikot_lavakerayslista(compact(array_keys($params)));
    extract($params);

    $kala = 500;
    $yhteensa = 0;

    foreach($tilausnumerot as $tilaus) {
      $query = "SELECT asiakkaan_tilausnumero
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = $tilaus";
      $lasres = pupe_query($query);
      $lasrow = mysql_fetch_assoc($lasres);

      $pdf->draw_text(50, $kala, $tilaus, $firstpage, $norm);
      $pdf->draw_text(150, $kala, $lasrow['asiakkaan_tilausnumero'],  $firstpage, $norm);

      $kala-=10;

      $params = sivunvaihto(compact(array_keys($params)));
      extract($params);
    }

    $sivunvaihto = 50;
    $kala-=50;
    $params = sivunvaihto(compact(array_keys($params)));
    extract($params);

    foreach($lavat as $lava => $lavantiedot) {
      $pdf->draw_text(50, $kala, "Lava ".$lava.":", $firstpage, $norm);
      $kala-=50;

      $params = sivunvaihto(compact(array_keys($params)));
      extract($params);
    }

    $pdf->draw_rectangle(580, 15, 15, 400,  $firstpage, $rectparam);

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('print_pdf_lavakerayslista')) {
  function print_pdf_lavakerayslista($params) {
    global $kukarow, $yhtiorow, $palvelin2;

    // Luodaan muuttujat
    extract($params);

    $returnvalue = 0;

    //keksit��n uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Lavakerayslista-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'email') {
      $liite = $pdffilenimi;

      $kutsu = t("Lavakerayslista", $kieli);
      echo t("Lavakerayslista tulostuu")."...<br>";

      require "inc/sahkoposti.inc";
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Ty�nnet��n tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Lavakerayslista tulostuu")."...<br>";
      $line = exec("$komento $pdffilenimi", $output, $returnvalue);
    }
  }
}