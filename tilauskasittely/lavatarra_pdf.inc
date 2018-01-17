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

if (!function_exists('sivu_lavatarra')) {
  function sivu_lavatarra($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    //PDF parametrit
    if ($pdf === NULL) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top', 0);
      $pdf->set_default('margin-bottom', 0);
      $pdf->set_default('margin-left', 0);
      $pdf->set_default('margin-right', 0);
    }

    $firstpage = $pdf->new_page("a5");

    // sitten aletaan piirtämään itse PDF sisältöä
    $pdf->draw_text(30, 550, $toimitustapa, $firstpage, $iso_boldi);

    $pdf->draw_text(30, 530, "Pallet: $lavanumero", $firstpage, $iso);

    $pdf->draw_text(50, 512, "Order", $firstpage, $boldi);
    $pdf->draw_text(130, 512, "Pack", $firstpage, $boldi);

    $kala = 500;
    $yhteensa = 0;

    foreach($tilaukset as $tilaus => $myyntierat) {
      if ($myyntierat == 0) {
        continue;
      }

      $query = "SELECT asiakkaan_tilausnumero
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = $tilaus";
      $lasres = pupe_query($query);
      $lasrow = mysql_fetch_assoc($lasres);

      if (!empty($lasrow['asiakkaan_tilausnumero'])) {
        $tilaus = $lasrow['asiakkaan_tilausnumero'];
      }

      $pdf->draw_text(50, $kala, $tilaus, $firstpage, $norm);

      $oikpos = $pdf->strlen($myyntierat, $norm);
      $pdf->draw_text(150-$oikpos, $kala, $myyntierat,  $firstpage, $norm);

      $yhteensa+= $myyntierat;
      $kala-=10;
    }

    $pdf->draw_text(200, $kala+10, $yhteensa,  $firstpage, $norm);

    $pdf->draw_rectangle(580, 15, 15, 400,  $firstpage, $rectparam);

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('print_pdf_lavatarra')) {
  function print_pdf_lavatarra($params) {
    global $kukarow, $yhtiorow, $palvelin2;

    // Luodaan muuttujat
    extract($params);

    $returnvalue = 0;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Lavatarra-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    // itse print komento...
    if ($komento == 'email') {
      $liite = $pdffilenimi;

      $kutsu = t("Lavatarra", $kieli);
      echo t("Lavatarra tulostuu")."...<br>";

      require "inc/sahkoposti.inc";
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Lavatarra tulostuu")."...<br>";
      $line = exec("$komento $pdffilenimi", $output, $returnvalue);
    }
  }
}
