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
    "height" => 3,
    "font"   => "Times-Roman"
  );

  $text_bold = array(
    "height" => 3,
    "font"   => "Times-Bold"
  );

  foreach ($tuotteet as $tuote) {
    $page = $pdf->new_page('49x30mm');
    $nimitys = explode("\n", wordwrap($tuote['nimitys'], 22));
    $hinta = hintapyoristys($tuote['myyntihinta']);
    $viivakoodi = viivakoodi($tuote['tuoteno'], "code128", 120, 30, $tuote['tuoteno']);
    $viivakoodin_asetukset = array(
      "scale" => array(
        "x" => 0.1,
        "y" => 0.1
      )
    );

    $pdf->draw_text(0, 27, $nimitys[0], $page, $text_normal);
    $pdf->draw_text(0, 24, $nimitys[1], $page, $text_normal);
    $pdf->draw_text(40, 18, $hinta, $page, $text_bold);

    $kuva = $pdf->jfif_embed($viivakoodi);
    $pdf->image_place($kuva, 2, 6, $page, $viivakoodin_asetukset);
  }

  $pdf_tiedosto = $pdf->generate();

  file_put_contents('/tmp/testing.pdf', $pdf_tiedosto);
}
