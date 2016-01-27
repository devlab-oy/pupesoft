<?php

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
  if (@include_once "viivakoodi/Barcode.php");
  else include_once "Barcode.php";
}

//PDF:n luonti ja defaultit
require_once "pdflib/phppdflib.class.php";

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top',    0);
$pdf->set_default('margin-bottom', 0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',  0);
$rectparam["width"] = 0.3;

//fontit
$norm["height"] = 10;
$norm["font"] = "Helvetica";

$otsik["height"] = 10;
$otsik["font"] = "Helvetica-Bold";

$kirj["height"] = 12;
$kirj["font"] = "Helvetica-Bold";

$iso["height"] = 16;
$iso["font"] = "Helvetica-Bold";

$huge["height"] = 20;
$huge["font"] = "Helvetica-Bold";

$uniq_pakkaukset = array();
$uniq_pakkaukset_sscc = array();

if ($yhtiorow['kerayserat'] == 'P' or $yhtiorow['kerayserat'] == 'A') {

  $kaikki_ok = true;

  if ($yhtiorow['kerayserat'] == 'A') {
    $query = "SELECT kerayserat
              FROM asiakas
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND tunnus     = '{$laskurow['liitostunnus']}'
              AND kerayserat = 'A'";
    $asiakas_chk_res = pupe_query($query);

    if (mysql_num_rows($asiakas_chk_res) == 0) {
      $kaikki_ok = false;
    }
  }

  if ($kaikki_ok) {
    $query = "SELECT DISTINCT pakkausnro, sscc, sscc_ulkoinen, pakkaus
              FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$laskurow['tunnus']}'
              ORDER BY pakkausnro";
    $pakkaus_chk_res = pupe_query($query);

    $pi = 1;

    while ($pakkaus_chk_row = mysql_fetch_assoc($pakkaus_chk_res)) {
      $uniq_pakkaukset[$pi] = $pakkaus_chk_row['pakkausnro'];
      $uniq_pakkaukset_sscc[$pi] = $pakkaus_chk_row['sscc_ulkoinen'];
      $pi++;
    }

    $lavaetikettikpl = count($uniq_pakkaukset) > 0 ? count($uniq_pakkaukset) : $lavaetikettikpl;
  }
}

$kuljetusliike_sscc = false;

if (!empty($yhtiorow['ean'])) {
  $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = '{$laskurow['toimitustapa']}'", "", "", "selitetark");

  if ($_selitetark == '') {
    $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = 'kaikki'", "", "", "selitetark");
  }

  if ($_selitetark != '') {
    $expansioncode = $_selitetark;
    $kuljetusliike_sscc = true;
  }
}

$query = "SELECT kerayserat.nro,
          kerayserat.otunnus,
          kerayserat.sscc,
          kerayserat.sscc_ulkoinen,
          kerayserat.tilausrivi,
          kerayserat.kpl
          FROM kerayserat
          WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
          AND kerayserat.otunnus = '5401537'
          GROUP BY 1,2,3,4,5,6
          ORDER BY 1,2,3,4,5,6";
$result = pupe_query($query);

$cnt = 1;

while ($row = mysql_fetch_assoc($result)) {

  if ($kuljetusliike_sscc) {
    $sscc = gs1_sscc($expansioncode, $laskurow['tunnus'], $cnt);
  }
  else {
    // tehdään SSCC :
    // (00)
    // 1
    // ean (8)
    // lähetenro (6) viimeistä
    // tulostuskpl (2)
    // tarkiste (1)
    $sscc  = 1;
    $sscc .= sprintf("%08.8s", $yhtiorow["ytunnus"]);
    $sscc .= sprintf('%06.6s', substr($laskurow["tunnus"], -6));
    $sscc .= sprintf('%02.2s', $uniq_merkki);
    $loppu = tarkiste($sscc);
    $sscc  = $sscc.$loppu;
  }

  // tehdään pdf:n uusi sivu
  $firstpage = $pdf->new_page("a5");

  $query = "SELECT tilausrivi.nimitys, tilausrivi.yksikko, tuote.sarjanumeroseuranta, tilausrivi.tuoteno
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tunnus = '{$row['tilausrivi']}'";
  $rivires = pupe_query($query);
  $rivirow = mysql_fetch_assoc($rivires);

  $pdf->draw_text(mm_pt(5), mm_pt(200), $laskurow['toim_nimi'], $firstpage, $norm);

  if (!empty($laskurow['toim_nimitark'])) {
    $pdf->draw_text(mm_pt(5), mm_pt(195), $laskurow['toim_nimitark'], $firstpage, $norm);
    $pdf->draw_text(mm_pt(5), mm_pt(190), $laskurow['toim_osoite'].", ".$laskurow['toim_postino']." ".strtoupper($laskurow['toim_postitp']), $firstpage, $norm);
  }
  else {
    $pdf->draw_text(mm_pt(5), mm_pt(195), $laskurow['toim_osoite'].", ".$laskurow['toim_postino']." ".strtoupper($laskurow['toim_postitp']), $firstpage, $norm);
  }

  $pdf->draw_text(mm_pt(5), mm_pt(180), $rivirow['nimitys'], $firstpage, $norm);
  $pdf->draw_text(mm_pt(5), mm_pt(175), $row['kpl']." ".$rivirow['yksikko'], $firstpage, $norm);

  $pdf->draw_rectangle(mm_pt(170), mm_pt(0), mm_pt(170), mm_pt(150), $firstpage, $rectparam);

  $pdf->draw_text(mm_pt(5), mm_pt(165), t("SSCC").":", $firstpage, $norm);
  $pdf->draw_text(mm_pt(5), mm_pt(160), $sscc, $firstpage, $otsik);

  if ($rivirow['sarjanumeroseuranta'] == 'E') {

    $query = "SELECT sarjanumero, parasta_ennen
              FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND myyntirivitunnus = '{$row['tilausrivi']}'
              AND tuoteno = '{$rivirow['tuoteno']}'";
    $sarjanumerores = pupe_query($query);
    $sarjanumerorow = mysql_fetch_assoc($sarjanumerores);

    $eranumero = $sarjanumerorow['sarjanumero'];
    $parasta_ennen = $sarjanumerorow['parasta_ennen'];

    # VVKKPP muoto
    $parastaennen_code = substr($parasta_ennen, 2, 2).substr($parasta_ennen, 5, 2).substr($parasta_ennen, 8, 2);
  }
  else {
    $eranumero = $row['nro'];
    $parasta_ennen = '';
    $parastaennen_code = '';
  }

  $oikpos = $pdf->strlen(t("Parasta ennen")." (DD.MM.YYYY):", $norm);
  $pdf->draw_text(mm_pt(144)-$oikpos, mm_pt(165), t("Parasta ennen")." (DD.MM.YYYY):", $firstpage, $norm);

  $oikpos = $pdf->strlen(tv1dateconv($parasta_ennen), $otsik);
  $pdf->draw_text(mm_pt(144)-$oikpos, mm_pt(160), tv1dateconv($parasta_ennen), $firstpage, $otsik);

  $pdf->draw_text(mm_pt(5), mm_pt(150), t("Eränumero").":", $firstpage, $norm);
  $pdf->draw_text(mm_pt(5), mm_pt(145), $eranumero, $firstpage, $otsik);

  $pdf->draw_rectangle(mm_pt(140), mm_pt(0), mm_pt(140), mm_pt(150), $firstpage, $rectparam);

  # AI (10) tarkoittaa eränumeroa
  $code1_text = "(10) {$eranumero}";
  $code1 = "10{$eranumero}";

  if (!empty($parastaennen_code)) {
    # AI (15) tarkoittaa parasta ennen päivämäärää
    $code2_text = "(15) {$parastaennen_code}";
    $code2 = "15{$parastaennen_code}";
  }
  else {
    $code2 = '';
  }

  $barcode1 = $code1.$code2;
  $barcode1_text = "{$code1_text} {$code2_text}";
  $barcode2_text = gs1_sscc_barcode_text($expansioncode, $laskurow['tunnus'], $cnt);

  if (class_exists("Image_Barcode")) {
    //luodaan viivakoodiolio kuljetusohjeelle = postino
    $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

    imagejpeg(Image_Barcode::draw($barcode1, 'code128', 'jpg', false, 3, 170), $nimi);

    $fh = fopen($nimi, "r");
    $data = fread($fh, filesize($nimi));
    fclose($fh);

    $image = $pdf->jfif_embed($data);

    $logoparam['scale'] = array(
      'x' => mm_pt(165),
      'y' => mm_pt(400),
    );

    $pdf->image_place($image, mm_pt(100), mm_pt(40), $firstpage, $logoparam);
    $pdf->draw_text(mm_pt(50), mm_pt(95), $barcode1_text, $firstpage, $norm);
    system("rm -f $nimi");

    $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

    imagejpeg(Image_Barcode::draw($sscc, 'code128', 'jpg', false, 3, 170), $nimi);

    $fh = fopen($nimi, "r");
    $data = fread($fh, filesize($nimi));
    fclose($fh);

    $image = $pdf->jfif_embed($data);

    $logoparam['scale'] = array(
      'x' => mm_pt(165),
      'y' => mm_pt(400),
    );

    $pdf->image_place($image, mm_pt(55), mm_pt(30), $firstpage, $logoparam);
    $pdf->draw_text(mm_pt(50), mm_pt(50), $barcode2_text, $firstpage, $norm);
    system("rm -f $nimi");
  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/lavaetiketti-".md5(uniqid(rand(), true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui {$pdffilenimi}");
  fclose($fh);

  if ($lavaetiketti == 'email') {
    $liite = $pdffilenimi;
    $kutsu = "Lavaetiketti";

    require "inc/sahkoposti.inc";
  }
  elseif ($tee == 'NAYTATILAUS') {
    //Työnnetään tuo pdf vaan putkeen!
    echo file_get_contents($pdffilenimi);
  }
  elseif ($lavaetiketti != '' and $lavaetiketti != 'edi') {
    $line = exec("$lavaetiketti $pdffilenimi");
    echo t("Lavaetiketti tulostuu")."...<br>";
  }

  $pdffilenimi = '';
  $tilausnumeroita = "";

  $cnt++;
  exit;
}
