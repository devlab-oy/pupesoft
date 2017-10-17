<?php

$query = "SELECT *
          FROM tuote
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tuoteno = '$tuoteno'";
$eankoodires = pupe_query($query);
$eankoodirow = mysql_fetch_assoc($eankoodires);

$rectparam["width"] = 0.3;

//fontit
$norm["height"] = 8;
$norm["font"] = "Helvetica";

// tehdään pdf:n uusi sivu
if ($malli == 'PDFBRO') {
  $firstpage = $pdf ->new_page('6.2x2.9cm');
  $norm["height"] = 12;
}
elseif ($malli != 'PDF') {
  $firstpage = $pdf->new_page("a4");
}
else {
  $firstpage = $pdf->new_page('4.9x3cm');
}

// A4 pointteina 595x842
if ($malli == 'PDFBRO') {
  $x = 1;
  $riville = 3;
  $leveys = 500;
  $kappalemaara = 10;
  $mm_pt = 300;
  $y = 25;
}
elseif ($malli == 'PDF40') {
  $x = 10;
  $riville = 4;
  $leveys = 315;
  $kappalemaara = 41;
  $mm_pt = 35;
  $y = 292;
}
elseif ($malli == 'PDF') {
  $x = 1;
  $riville = 1;
  $leveys = 456;
  $kappalemaara = 2;
  $mm_pt = 48;
  $y = 25;
}
else {
  $x = 15;
  $riville = 3;
  $leveys = 450;
  $kappalemaara = 25;
  $mm_pt = 50;
  $y = 285;
}

if ($toim == 'YKS') {
  $koodi = 'tuoteno';
}
elseif ($toim == "SARJA") {
  $koodi = 'sarjanumero';
}
else {
  $koodi = 'eankoodi';
}

for ($i = 1; $i < $kappalemaara; $i++) {

  $nimitys1 = $nimitys2 = '';

  if ($toim == "SARJA") {
    $pdf->draw_text(mm_pt($x), mm_pt($y), t("Tuotenumero") . ": $eankoodirow[tuoteno]", $firstpage, $norm);
  }
  elseif ($pdf->strlen($eankoodirow['nimitys'], $norm) >= mm_pt($mm_pt)) {
    list($ff_string, $ff_font) = pdf_fontfit($eankoodirow['nimitys'], mm_pt($mm_pt), $pdf, $norm);

    $nimitys1 = substr($eankoodirow['nimitys'], 0, strlen($ff_string));
    $nimitys2 = substr($eankoodirow['nimitys'], strlen($ff_string));

    if ($malli == 'PDF40' and trim($nimitys2) == '' and ($i == 1 or $i == 2 or $i == 3 or $i == 4 or $i == 37 or $i == 38 or $i == 39 or $i == 40) ) {
      if ($i == 37) {
        $y += 3;
      }

      $pdf->draw_text(mm_pt($x), mm_pt($y - 4), $nimitys1, $firstpage, $ff_font);
    }
    else {
      $pdf->draw_text(mm_pt($x), mm_pt($y), $nimitys1, $firstpage, $ff_font);
    }

    if ($pdf->strlen($nimitys2, $norm) >= mm_pt($mm_pt)) {
      list($ff_string2, $ff_font2) = pdf_fontfit($nimitys2, mm_pt($mm_pt), $pdf, $norm);
      $nimitys2 = $ff_string2;
    }

    $pdf->draw_text(mm_pt($x), mm_pt($y - 4), $nimitys2, $firstpage, $ff_font);
    $pdf->draw_text(mm_pt($x), mm_pt($y - 8), t("Tuotenumero")." $eankoodirow[tuoteno]", $firstpage, $norm);
  }
  else {
    list($ff_string, $ff_font) = pdf_fontfit($eankoodirow['nimitys'], mm_pt($mm_pt), $pdf, $norm);
    $pdf->draw_text(mm_pt($x), mm_pt($y), $ff_string, $firstpage, $ff_font);
    $pdf->draw_text(mm_pt($x), mm_pt($y - 4), t("Tuotenumero")." $eankoodirow[tuoteno]", $firstpage, $norm);
  }

  if (isset($viivakoodityyppi) and $viivakoodityyppi == "viivakoodi") {
    if ($malli == 'PDFBRO') {
      $tieto = empty($eankoodirow["eankoodi"]) ? $eankoodirow['tuoteno'] : $eankoodirow['eankoodi'];
      $data = viivakoodi($tieto, "codabar", $leveys, 100, "");
    }
    elseif ($koodi == 'eankoodi' and tarkista_ean13($eankoodirow['eankoodi']) !== false) {
      $data = viivakoodi($eankoodirow['eankoodi'], "ean", $leveys, 100, "zz");
    }
    elseif ($koodi == "sarjanumero") {
      $data = viivakoodi($sarjarow["sarjanumero"], "codabar", $leveys, 100, "zz");
    }
    else {
      $data = viivakoodi($eankoodirow["$koodi"], "codabar", $leveys, 100, "zz");
    }
  }
  else {
    $qr_params = array(
      "format" => "jpg",
      "size" => 10,
      "height" => 400
    );

    if ($koodi == 'eankoodi' and tarkista_ean13($eankoodirow['eankoodi']) !== false) {
      $data = qr_koodi($eankoodirow['eankoodi'], $qr_params);
    }
    else {
      $data = qr_koodi($eankoodirow[$koodi], $qr_params);
    }
  }

  $image = $pdf->jfif_embed($data);

  if ($image) {
    $logoparam['scale'] = 0.1;

    // piirretään viivakoodi paprulle
    $pdf->image_place($image, mm_pt($y - 22.5), mm_pt($x), $firstpage, $logoparam); // Y, X
  }

  if ($malli == 'PDF40') {
    $x += 53;
  }
  else {
    $x += 68;
  }

  if ($i % $riville == 0) {
    if ($malli == 'PDF40') {
      $x = 10;
      $y -= 29.5;
    }
    else {
      $x = 15;
      $y -= 37;
    }
  }
}
