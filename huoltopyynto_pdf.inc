<?php

// tarvitaan työmääräyksen tunnus muuttujassa $tyomaaraystunnus -> voidaan tätä kautta hakea myös laitteen tiedot
// $kukarow array jostain saadaan yhtio sekä huoltopyynnön luonut henkilö sekä oletusasiakas
// $yhtiorow array josta saada lähettäjän tiedot

//PDF:n luonti ja defaultit
require_once "pdflib/phppdflib.class.php";

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);
$rectparam["width"] = 0.3;

//fontit
$otsik["height"] = 9;
$otsik["font"] = "Helvetica";

$pien["height"] = 10;
$pien["font"] = "Helvetica";

$norm["height"] = 10;
$norm["font"] = "Helvetica-Bold";

$piennorm["height"] = 9;
$piennorm["font"] = "Helvetica-Bold";

$kirj["height"] = 12;
$kirj["font"] = "Helvetica-Bold";

$iso["height"] = 16;
$iso["font"] = "Helvetica-Bold";

$huge["height"] = 20;
$huge["font"] = "Helvetica-Bold";

// Tehdään uusi sivu
$firstpage = $pdf->new_page("a4");

tulosta_logo_pdf($pdf, $firstpage, "");

// Otsikko
$pdf->draw_text(310, 815, t("Huoltopyyntö", $kieli), $firstpage, $iso);

// Huoltopyynnön tiedot
$pdf->draw_text(50, 729, t("Huoltopyyntönumero", $kieli),   $firstpage, $iso);
$pdf->draw_text(50, 717, 'huoltopyynnönnumero',     $firstpage, $iso);

$pdf->draw_text(50, 729, t("Asiakas", $kieli),   $firstpage, $norm);
$pdf->draw_text(50, 717, 'huoltopyynnönasiakas',     $firstpage, $norm);

$pdf->draw_text(50, 729, t("Huoltopyynnön luonut", $kieli),   $firstpage, $norm);
$pdf->draw_text(50, 717, 'huoltopyynnönluoja',     $firstpage, $norm);

$pdf->draw_text(50, 729, t("Luontiaika", $kieli),   $firstpage, $norm);
$pdf->draw_text(50, 717, 'huoltopyynnönluontiaika',     $firstpage, $norm);

// Laitteen tiedot
$pdf->draw_rectangle(737, 300, 716, 580, $firstpage, $rectparam);
$pdf->draw_text(310, 729, t("Huollettava laite", $kieli),   $firstpage, $iso);

$pdf->draw_text(310, 729, t("Valmistaja", $kieli),   $firstpage, $pieni);
$pdf->draw_text(310, 718, 'valmistajatieto',       $firstpage, $norm);

$pdf->draw_text(310, 729, t("Malli", $kieli),   $firstpage, $pieni);
$pdf->draw_text(310, 718, 'mallitieto',       $firstpage, $norm);

$pdf->draw_text(310, 729, t("Sarjanumero", $kieli),   $firstpage, $pieni);
$pdf->draw_text(310, 718, 'sarjanumerotieto',       $firstpage, $norm);

$pdf->draw_text(310, 729, t("Viankuvaus", $kieli),   $firstpage, $pieni);
$pdf->draw_text(310, 718, 'viankuvaustieto',       $firstpage, $norm);

// iffattu rivi
$pdf->draw_text(310, 729, t("Laitetta ei löytynyt laiterekisteristä", $kieli),   $firstpage, $pieni);

// Yhtiön osoitetiedot
$pdf->draw_text(510, 792, t("Vioittuneen laitteen lähetysosoite", $kieli),     $firstpage, $norm);
$pdf->draw_text(510, 792, $yhtiorow["nimi"],    $firstpage, $pieni);
$pdf->draw_text(510, 792, $yhtiorow["osoite"],  $firstpage, $pieni);
$pdf->draw_text(510, 792, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],  $firstpage, $pieni);
$pdf->draw_text(510, 792, $yhtiorow["maa"],    $firstpage, $pieni);
$pdf->draw_text(510, 792, 'inffo',    $firstpage, $pieni);

// Toimitusosoite
$pdf->draw_text(510, 792, t("Palautusosoite", $kieli),     $firstpage, $norm);
$pdf->draw_text(510, 792, $yhtiorow["nimi"],    $firstpage, $pieni);
$pdf->draw_text(510, 792, $yhtiorow["osoite"],  $firstpage, $pieni);
$pdf->draw_text(510, 792, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],  $firstpage, $pieni);
$pdf->draw_text(510, 792, $yhtiorow["maa"],    $firstpage, $pieni);
                                                                                                   
//keksitään uudelle failille joku varmasti uniikki nimi:
list($usec, $sec) = explode(' ', microtime());
mt_srand((float) $sec + ((float) $usec * 100000));
$pdffilenimi = "/tmp/huoltopyynto-{$tyomaaraystunnus}-".md5(uniqid(mt_rand(), true)).".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
fclose($fh);

if ($nayta_pdf == 1) {
  echo file_get_contents($pdffilenimi);
}
