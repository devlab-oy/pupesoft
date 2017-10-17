<?php

require_once "pdflib/phppdflib.class.php";
require_once "barcode/barcode.php";
require_once "barcode/c128cobject.php";

/**
 *
 * @param array   $tuotteet Array tuotteista, joiden hintalaput tulostetaan
 * @param array   $params   Configuraation array. Esimerkki:
 *   array(
 *     "kpl"  => 1,
 *     "koko" => '4.9x3cm',
 *   );
 *
 * @return array Tiedostonimi ja kaunisnimi
 */


function tulosta_hintalaput($tuotteet, $params = array()) {
  global $yhtiorow;

  $kpl           = isset($params["kpl"])             ? $params["kpl"]           : false;
  $koko          = isset($params["koko"])            ? $params["koko"]          : '4.9x3cm';
  $barcode_field = isset($params["barcode_field"])   ? $params["barcode_field"] : 'tuoteno';
  $default_tax   = empty($yhtiorow['alv_kasittely']) ? 'verollinen'             : 'veroton';
  $tax_field     = isset($params["tax_field"])       ? $params["tax_field"]     : $default_tax;

  $barcode_map = array(
    'tuoteno'  => 'code128',
    'eankoodi' => 'ean',
  );

  $pdf = new pdffile();

  $pdf->set_default('margin-top', 0);
  $pdf->set_default('margin-right', 0);
  $pdf->set_default('margin-bottom', 0);
  $pdf->set_default('margin-left', 0);

  $text_normal = array(
    "height" => 8,
    "font"   => "Helvetica"
  );

  $text_bold = array(
    "height" => 10,
    "font"   => "Helvetica-Bold"
  );

  switch ($koko) {
  case '6.2x2.9cm':
    if ($barcode_field == 'tuoteno') {
      $viivakoodi_vasen        = 2;
      $viivakoodi_teksti_vasen = 2;
    }
    else {
      $viivakoodi_vasen        = -5;
      $viivakoodi_teksti_vasen = 60;
    }

    $viivakoodi_leveys     = 600;
    $viivakoodin_asetukset = array("scale" => array("x" => 0.1, "y" => 0.1));
    $kompensaatio          = -2;
    $hinta_x               = 140;
    $yksikko_x             = 168;
    break;
  default:
    $viivakoodi_leveys       = 360;
    $viivakoodi_vasen        = 17;
    $viivakoodi_teksti_vasen = 50;
    $viivakoodin_asetukset   = array("scale" => array("x" => 0.1, "y" => 0.1));
    $kompensaatio            = 0;
    $hinta_x                 = 100;
    $yksikko_x               = 129;
  }

  foreach ($tuotteet as $tuote) {
    $nimitys            = explode("\n", wordwrap($tuote['nimitys'], 28));
    $hinta              = hintapyoristys($tuote['myyntihinta']);
    $viivakoodi         = viivakoodi($tuote[$barcode_field], $barcode_map[$barcode_field], $viivakoodi_leveys, 60);
    $viivakoodin_teksti = $tuote[$barcode_field];
    $kuva               = $pdf->jfif_embed($viivakoodi);
    $alv_prosentti      = 1 + ($tuote['alv'] / 100);

    // Lasketaan hinta uusiksi, jos pyydetty eri kun default
    if ($default_tax == 'verollinen' and $tax_field == 'veroton') {
      $hinta = hintapyoristys($tuote['myyntihinta'] / $alv_prosentti);
    }
    elseif ($default_tax == 'veroton' and $tax_field == 'verollinen') {
      $hinta = hintapyoristys($tuote['myyntihinta'] * $alv_prosentti);
    }

    if ($kpl) {
      $montako_tulostetaan = $kpl;
    }
    elseif ($tuote["kpl"]) {
      $montako_tulostetaan = ($tuote['kpl'] * $tuote['tarrakerroin']);
    }
    else {
      $montako_tulostetaan = ($tuote['tarrakpl'] * $tuote['tarrakerroin']);
    }

    for ($i = 0; $i < $montako_tulostetaan; $i++) {
      $page = $pdf->new_page($koko);

      // Kuva ensin, jotta ei mene tekstin päälle
      $pdf->image_place($kuva, 15, $viivakoodi_vasen, $page, $viivakoodin_asetukset);
      $pdf->draw_text(0, 77 + $kompensaatio, $nimitys[0], $page, $text_normal);
      $pdf->draw_text(0, 68 + $kompensaatio, $nimitys[1], $page, $text_normal);
      $pdf->draw_text(0, 59 + $kompensaatio, $nimitys[2], $page, $text_normal);

      $hinta         = str_replace(".", ",", $hinta);
      $hinnan_leveys = $pdf->strlen($hinta, $text_normal);
      $pdf->draw_text($hinta_x - $hinnan_leveys, 50, $hinta . " EUR", $page, $text_bold);

      $yksikon_leveys = $pdf->strlen($tuote['yksikko'], $text_normal);
      $pdf->draw_text($yksikko_x - $yksikon_leveys, 40, $tuote['yksikko'], $page, $text_normal);
      $pdf->draw_text($viivakoodi_teksti_vasen, 8, $viivakoodin_teksti, $page, $text_normal);
    }
  }

  $pdf_tiedosto = $pdf->generate();

  $kaunisnimi   = "hintalaput.pdf";
  $tiedostonimi = "hintalaput-" . md5(uniqid(rand())) . ".pdf";
  file_put_contents("/tmp/{$tiedostonimi}", $pdf_tiedosto);

  return array($tiedostonimi, $kaunisnimi);
}

function hae_tuotteet_hintalappuja_varten($otunnus, $kukarow) {
  if ($otunnus <= 0) {
    return false;
  }

  $query = "SELECT tuote.tuoteno,
            tuote.eankoodi,
            tuote.myyntihinta,
            tuote.yksikko,
            tuote.nimitys,
            if(tuote.tarrakpl > 0, tuote.tarrakpl, 1) AS tarrakpl,
            if(tuote.tarrakerroin > 0, tuote.tarrakerroin, 1) AS tarrakerroin,
            tilausrivi.kpl
            FROM tilausrivi
            INNER JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
            AND tilausrivi.tuoteno       = tuote.tuoteno)
            WHERE tilausrivi.uusiotunnus = '{$otunnus}'
            AND tilausrivi.yhtio         = '{$kukarow['yhtio']}'
            AND tilausrivi.tyyppi        = 'O'";

  $result = pupe_query($query);

  $tuotteet = array();

  while ($tuote = mysql_fetch_assoc($result)) {
    array_push($tuotteet, $tuote);
  }

  return $tuotteet;
}
