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
    $nimitys = explode("\n", wordwrap($tuote['nimitys'], 22));
    $hinta = hintapyoristys($tuote['myyntihinta']);
    $viivakoodi = viivakoodi($tuote['tuoteno'], "code128", 120, 30, $tuote['tuoteno']);
    $viivakoodin_asetukset = array(
      "scale" => array(
        "x" => 0.1,
        "y" => 0.1
      )
    );
    $kuva = $pdf->jfif_embed($viivakoodi);

    $montako_tulostetaan = $tuote['tarrakpl'] * $tuote['tarrakerroin'];

    for ($i = 0; $i < $montako_tulostetaan; $i++) {
      $page = $pdf->new_page('49x30mm');

      $pdf->draw_text(0, 27, $nimitys[0], $page, $text_normal);
      $pdf->draw_text(0, 24, $nimitys[1], $page, $text_normal);
      $pdf->draw_text(40, 18, $hinta, $page, $text_bold);
      $pdf->image_place($kuva, 2, 6, $page, $viivakoodin_asetukset);
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
            tuote.nimitys,
            if (tuote.tarrakpl > 0, tuote.tarrakpl, 1) AS tarrakpl,
            if (tuote.tarrakerroin > 0, tuote.tarrakerroin, 1) AS tarrakerroin
            FROM tilausrivi
            INNER JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio
              AND tilausrivi.tuoteno = tuote.tuoteno)
            WHERE tilausrivi.uusiotunnus = '{$otunnus}'
            AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $tuotteet = array();

  while ($tuote = mysql_fetch_assoc($result)) {
    array_push($tuotteet, $tuote);
  }

  return $tuotteet;
}
