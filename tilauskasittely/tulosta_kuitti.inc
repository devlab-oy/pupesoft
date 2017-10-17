<?php

require_once "pdflib/phppdflib.class.php";

// tarvitaan $yhtiorow, $asiakasrow, $selite ja $summa

//PDF parametrit
$pdf = new pdffile;

$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);
$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

// defaultteja
$kala = 540;
$lask = 1;
$sivu = 1;

if ($kieli == '') {
  $kieli = $asiakasrow['kieli'];
}

// laskun alku ja loppu functiot...
if (!function_exists('alku')) {
  function alku() {
    global $pdf, $asiakasrow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kutsuja, $kieli;

    $firstpage = $pdf->new_page("a4");
    $pdf->enable('template');
    $tid = $pdf->template->create();
    $pdf->template->size($tid, 600, 830);

    //Otsikko
    $pdf->draw_text(30, 815, $yhtiorow["nimi"], $firstpage);

    $pdf->draw_text(310, 815, t("Käteiskuitti", $kieli),  $firstpage);
    $pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, $firstpage, $norm);

    $pdf->draw_text(50, 729, t("Asiakas", $kieli),       $firstpage, $pieni);
    $pdf->draw_text(50, 717, $asiakasrow["nimi"],       $firstpage, $norm);
    $pdf->draw_text(50, 707, $asiakasrow["osoite"],     $firstpage, $norm);
    $pdf->draw_text(50, 697, $asiakasrow["postino"]." ".$asiakasrow["postitp"], $firstpage, $norm);
    $pdf->draw_text(50, 687, $asiakasrow["maa"],       $firstpage, $norm);

    //Oikea sarake
    $pdf->draw_rectangle(758, 300, 737, 580,         $firstpage, $rectparam);
    $pdf->draw_rectangle(758, 420, 737, 580,         $firstpage, $rectparam);
    $pdf->draw_text(310, 750, t("Maksupäivä", $kieli),     $firstpage, $pieni);
    $pdf->draw_text(310, 740, date('Y-m-d'),         $firstpage, $norm);
    $pdf->draw_text(430, 750, t("Valuutta", $kieli),     $firstpage, $pieni);
    $pdf->draw_text(430, 740, $yhtiorow["valkoodi"],     $firstpage, $norm);

    $pdf->draw_rectangle(737, 300, 716, 580,         $firstpage, $rectparam);
    $pdf->draw_rectangle(737, 420, 716, 580,         $firstpage, $rectparam);
    $pdf->draw_text(310, 729, t("Laatija", $kieli),     $firstpage, $pieni);
    $pdf->draw_text(310, 719, $kukarow["nimi"],       $firstpage, $norm);
    $pdf->draw_text(430, 729, t("Asiakasnumero", $kieli),  $firstpage, $pieni);
    $pdf->draw_text(430, 719, $asiakasrow["ytunnus"],     $firstpage, $norm);


    //rivien otsikkotiedot
    //eka rivi
    $pdf->draw_text(30,  572, t("Selite", $kieli),      $firstpage, $pieni);
    $pdf->draw_text(470, 572, t("Summa", $kieli),      $firstpage, $pieni);

    return $firstpage;
  }
}
if (!function_exists('rivi')) {
  function rivi($firstpage) {
    global $firstpage, $pdf, $row, $kala, $sivu, $kala, $lask, $rectparam, $norm, $pieni, $kutsuja, $summa, $arvo, $laskurow, $kieli, $kukarow, $selite;

    $pdf->draw_text(30,  $kala, $selite,  $firstpage, $norm);
    $pdf->draw_text(470, $kala, sprintf('%-1.2f', $summa),   $firstpage, $norm);

    $kala = $kala - 13;
    $lask++;
  }
}

if (!function_exists('loppu')) {
  function loppu($firstpage) {
    global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $summa, $arvo, $kutsuja, $kieli;

    //yhteensärivi
    $pdf->draw_rectangle(110, 394, 90, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(110, 540, 90, 580,  $firstpage, $rectparam);

    $pdf->draw_text(404, 92, t("Yhteensä", $kieli).":",  $firstpage, $norm);
    $pdf->draw_text(464, 92, sprintf('%-1.2f', $summa),  $firstpage, $norm);
    $pdf->draw_text(550, 92, $yhtiorow["valkoodi"],  $firstpage, $norm);

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(55, 20, 20, 580,              $firstpage, $rectparam);
    $pdf->draw_rectangle(55, 207, 20, 580,              $firstpage, $rectparam);
    $pdf->draw_rectangle(55, 394, 20, 580,              $firstpage, $rectparam);

    $pdf->draw_text(30, 45, $yhtiorow["nimi"],            $firstpage, $pieni);
    $pdf->draw_text(30, 35, $yhtiorow["osoite"],          $firstpage, $pieni);
    $pdf->draw_text(30, 25, $yhtiorow["postino"]."  ".$yhtiorow["postitp"]."  ".$yhtiorow["maa"],  $firstpage, $pieni);

    $pdf->draw_text(217, 45, t("Puhelin", $kieli).":",        $firstpage, $pieni);
    $pdf->draw_text(247, 45, $yhtiorow["puhelin"],          $firstpage, $pieni);
    $pdf->draw_text(217, 35, t("Fax", $kieli).":",        $firstpage, $pieni);
    $pdf->draw_text(247, 35, $yhtiorow["fax"],            $firstpage, $pieni);
    $pdf->draw_text(217, 25, t("Sähköposti", $kieli).":",      $firstpage, $pieni);
    $pdf->draw_text(247, 25, $yhtiorow["email"],          $firstpage, $pieni);

    $pdf->draw_text(404, 45, t("Y-tunnus", $kieli).":",        $firstpage, $pieni);
    $pdf->draw_text(444, 45, $yhtiorow["maa"]."-".$yhtiorow["ytunnus"],  $firstpage, $pieni);
    $pdf->draw_text(404, 35, t("Kotipaikka", $kieli).":",      $firstpage, $pieni);
    $pdf->draw_text(444, 35, $yhtiorow["kotipaikka"],        $firstpage, $pieni);
  }
}
