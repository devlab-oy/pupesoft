<?php

$pdf = alku($pdf, $row);

$pdf->write($h, 1, 0, 75, $yhtiorow[nimi], "BIG", "L", "B");
$pdf->write($h, 1, 80, 92,  tv1dateconv($aikarow["laskutettuaika"]), "norm", "R", "");

$h-=$pdf->ln(2);

$pdf->write($h, 1, 0, 20, "Tuote", "norm", "L", "B");
$pdf->write($h, 1, 20, 0, "$row[tuoteno] - $row[nimitys]", "norm", "L", "");
$h-=$pdf->ln(1.4);

if(in_array($row["sarjanumeroseuranta"], array("E", "F", "G"))) {

  $pdf->write($h, 1, 0, 15,   "Tilaus", "norm", "L", "B");
  $pdf->write($h, 1, 20, 45,   "Erä", "norm", "L", "B");
  $pdf->write($h, 1, 50, 68,   "Eräkoko", "norm", "L", "B");
  if($lisarow["pituus"]>0) {
    $pdf->write($h, 1, 73, 93,   "Pituus", "norm", "L", "B");
  }
  $h-=$pdf->ln(1.25);

  $pdf->write($h, 1, 0, 15,   $row["otunnus"], "norm", "L", "");
  $pdf->write($h, 1, 20, 45,  $row["sarjanumero"], "norm", "L", "");
  $pdf->write($h, 1, 50, 68,  $row["kpl"].strtolower($row["yksikko"]), "norm", "L", "");
  if($lisarow["pituus"]>0) {
    $pdf->write($h, 1, 73, 93,  $lisarow["pituus"]."mm", "norm", "L", "");
  }
  $h-=$pdf->ln(1.5);

  $pdf->write($h, 1, 0, 15,   "Lisätiedot", "norm", "L", "B");
  $h-=$pdf->ln(1.25);
  $pdf->write($h, 1, 0, 0,  "$row[lisatieto]", "norm", "L", "");
}
elseif(in_array($row["sarjanumeroseuranta"], array("S", "T", "U", "V"))) {
  $pdf->write($h, 1, 0, 15,   "Tilaus", "norm", "L", "B");
  $pdf->write($h, 1, 20, 45,   "S:nro", "norm", "L", "B");
  if($lisarow["pituus"]>0) {
    $pdf->write($h, 1, 73, 93,   "Pituus", "norm", "L", "B");
  }
  $h-=$pdf->ln(1.25);

  $pdf->write($h, 1, 0, 15,   $row["otunnus"], "norm", "L", "");
  $pdf->write($h, 1, 20, 45,  $row["sarjanumero"], "norm", "L", "");
  if($lisarow["pituus"]>0) {
    $pdf->write($h, 1, 73, 93,  $lisarow["pituus"], "norm", "L", "");
  }
  $h-=$pdf->ln(1.5);

  $pdf->write($h, 1, 0, 15,   "Lisätiedot", "norm", "L", "B");
  $h-=$pdf->ln(1.25);
  $pdf->write($h, 1, 0, 0,  "$row[lisatieto]", "norm", "L", "");
}
