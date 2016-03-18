<?php

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

require_once "pdflib/phppdflib.class.php";

//PDF parametrit
$pdf = new pdffile;

$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);

$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] = "Helvetica";

$normbold["height"] = 10;
$normbold["font"] = "Helvetica-Bold";

$pieni["height"] = 8;
$pieni["font"] = "Helvetica";

$iso["height"] = 15;
$iso["font"] = "Helvetica-Bold";

$firstpage = $pdf->new_page("a4");

//Otsikko
$pdf->draw_text(30, 750, t("KUITTI MAKSAJALLE"), $firstpage, $iso);
$pdf->draw_text(460, 750, t("Tulostettu %s", '', date('d.m.Y')), $firstpage, $norm);

$pdf->draw_text(30, 710, t("Maksajan nimi"), $firstpage, $normbold);
$pdf->draw_text(150, 710, $yhtiorow['nimi'], $firstpage, $norm);

$pdf->draw_text(30, 695, t("Maksajan tilinumero"), $firstpage, $normbold);
$pdf->draw_text(150, 695, $yhtiorow['pankkinimi1'].' '.$yhtiorow['pankkitili1'], $firstpage, $norm);

$pdf->draw_text(30, 665, t("Maksupäivä"), $firstpage, $normbold);
$pdf->draw_text(150, 665, $maksupvm, $firstpage, $norm);

$pdf->draw_text(30, 650, t("Arvopäivä"), $firstpage, $normbold);
$pdf->draw_text(150, 650, $arvopvm, $firstpage, $norm);

$pdf->draw_text(30, 620, t("Saajan nimi"), $firstpage, $normbold);
$pdf->draw_text(150, 620, $saajan_nimi, $firstpage, $norm);

$pdf->draw_text(30, 605, t("Saajan tilinumero"), $firstpage, $normbold);
$pdf->draw_text(150, 605, $saajan_tilinumero, $firstpage, $norm);

$pdf->draw_text(30, 575, t("Viite"), $firstpage, $normbold);
$pdf->draw_text(150, 575, $viite, $firstpage, $norm);

$pdf->draw_text(30, 545, t("Arkistointitunnus"), $firstpage, $normbold);
$pdf->draw_text(150, 545, $arkistointitunnus, $firstpage, $norm);

$pdf->draw_text(30, 515, t("Kirjausselite"), $firstpage, $normbold);
$pdf->draw_text(150, 515, $kirjausselite, $firstpage, $norm);

$pdf->draw_text(150, 490, $sisainen_viite, $firstpage, $norm);

$pdf->draw_text(30, 450, t("Rahamäärä"), $firstpage, $normbold);
$pdf->draw_text(150, 450, 'EUR', $firstpage, $norm);
$pdf->draw_text(300, 450, $rahamaara, $firstpage, $norm);

$pdf->draw_text(30, 150, t("Päiväys ja allekirjoitus"), $firstpage, $normbold);
$pdf->draw_rectangle(100, 30, 100, 560, $firstpage , $rectparam);

$pdffilenimi = "/tmp/tiliotekuitti-".md5(uniqid(mt_rand(), true)).".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui {$pdffilenimi}");
fclose($fh);

echo file_get_contents($pdffilenimi);

//poistetaan tmp file samantien kuleksimasta...
unlink($pdffilenimi);
