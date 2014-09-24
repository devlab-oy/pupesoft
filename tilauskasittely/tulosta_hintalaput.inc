<?php

require_once "../pdflib/phppdflib.class.php";
require_once "../barcode/barcode.php";
require_once "../barcode/c128cobject.php";

function tulosta_hintalaput($tuotteet) {
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

  foreach ($tuotteet as $tuote) {
    $nimitys = explode("\n", wordwrap($tuote['nimitys'], 28));
    $hinta = hintapyoristys($tuote['myyntihinta']);
    $viivakoodi = viivakoodi($tuote['tuoteno'], "code128", 360, 90, $tuote['tuoteno']);
    $viivakoodin_asetukset = array(
      "scale" => array(
        "x" => 0.1,
        "y" => 0.1
      )
    );
    $kuva = $pdf->jfif_embed($viivakoodi);

    $montako_tulostetaan = $tuote['tarrakpl'] * $tuote['tarrakerroin'];

    for ($i = 0; $i < $montako_tulostetaan; $i++) {
      $page = $pdf->new_page('4.9x3cm');

      // Kuva ensin, jotta ei mene tekstin päälle
      $pdf->image_place($kuva, 5, 17, $page, $viivakoodin_asetukset);
      $pdf->draw_text(0, 77, $nimitys[0], $page, $text_normal);
      $pdf->draw_text(0, 68, $nimitys[1], $page, $text_normal);
      $pdf->draw_text(80, 50, str_replace(".", ",", $hinta) . " EUR", $page, $text_bold);
      $pdf->draw_text(115, 40, $tuote['yksikko'], $page, $text_normal);
    }
  }

  $pdf_tiedosto = $pdf->generate();

  $tiedostonimi = "hintalaput.pdf";
  file_put_contents("/tmp/{$tiedostonimi}", $pdf_tiedosto);

  return $tiedostonimi;
}

function hae_tuotteet_hintalappuja_varten($otunnus, $kukarow) {
  $query = "SELECT tuote.tuoteno,
            tuote.myyntihinta,
            tuote.yksikko,
            tuote.nimitys,
            if(tuote.tarrakpl > 0, tuote.tarrakpl, 1)         AS tarrakpl,
            if(tuote.tarrakerroin > 0, tuote.tarrakerroin, 1) AS tarrakerroin
            FROM tilausrivi
            INNER JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio
              AND tilausrivi.tuoteno = tuote.tuoteno)
            WHERE tilausrivi.uusiotunnus = '{$otunnus}'
            AND tilausrivi.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $tuotteet = array();

  while ($tuote = mysql_fetch_assoc($result)) {
    array_push($tuotteet, $tuote);
  }

  return $tuotteet;
}
