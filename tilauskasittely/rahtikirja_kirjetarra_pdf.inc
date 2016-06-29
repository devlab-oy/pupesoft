<?php

if ($phpnimi == "rahtikirja_custom.php") {
  $postirow = $osoitelappurow;
  $rakir_row = $osoitelappurow;
}
else {
  $query = "SELECT * from lasku where yhtio = '$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
  $tempr = pupe_query($query);
  $postirow = mysql_fetch_array($tempr);
}

// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
$query = "SELECT nimi, nimitark, osoite, postino, postitp, maa
          FROM varastopaikat
          WHERE yhtio = '$kukarow[yhtio]'
          AND tunnus  = '$postirow[varasto]'";
$tempr = pupe_query($query);
$postirow_varasto = mysql_fetch_array($tempr);

// jos varastolle on annettu joku osoite, käytetään sitä
if ($postirow_varasto["nimi"] != "") {
  $postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
  $postirow['yhtio_nimitark']  = $postirow_varasto["nimitark"];
  $postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
  $postirow["yhtio_postino"]  = $postirow_varasto["postino"];
  $postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
  $postirow["yhtio_maa"]      = $postirow_varasto["maa"];
}

// jos meillä on printterin takana joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
if ($print["nimi"] != "") {
  $postirow["yhtio_nimi"]    = $print["nimi"];
  $postirow["yhtio_osoite"]  = $print["osoite"];
  $postirow["yhtio_postino"] = $print["postino"];
  $postirow["yhtio_postitp"] = $print["postitp"];
}

if ($postirow['rahtisopimus']=='') $postirow['rahtisopimus'] = "000000";
if ($toitarow['sopimusnro']=='')   $toitarow['sopimusnro']   = "000000";

// Ulkomaan lähetykissä tarvitaan myös nelinumeroinen, asiakaskohtainen tunnushallintanumero
if (strpos($toitarow['sopimusnro'], "@") !== FALSE) {
  list($toitarow['sopimusnro'], $toitarow['tunhalnro']) = explode("@", $toitarow['sopimusnro']);
}

$toitarow['sopimusnro'] = sprintf("%06s", $toitarow['sopimusnro']);  // sopimunumeron tulee olla kuus pitkä

require_once "pdflib/phppdflib.class.php";

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 0);
$pdf->set_default('margin-bottom', 0);
$pdf->set_default('margin-left', 0);
$pdf->set_default('margin-right', 0);

$rectparam["width"] = 0.3;

$norm["height"] = 8.5;
$norm["font"] = "Helvetica";

$pieni["height"] = 6;
$pieni["font"] = "Helvetica";

$pienibold["height"] = 7;
$pienibold["font"] = "Helvetica-Bold";

$iso["height"] = 9;
$iso["font"] = "Helvetica";

$isobold["height"] = 10.5;
$isobold["font"] = "Helvetica-Bold";

$isompi["height"] = 13;
$isompi["font"] = "Helvetica";

$viivacourier["height"] = 10;
$viivacourier["font"] = "Courier";

$viivacourierbold["height"] = 10;
$viivacourierbold["font"] = "Courier-Bold";

$huge["height"] = 20;
$huge["font"] = "Helvetica-Bold";

// defaultteja
$kala = 575;
$lask = 1;
$sivu = 1;

$tulostakolli = $tulostuskpl;

// tulostetaan niin monta lappua kun on kollejakin
for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

  // tehdään pdf:n uusi sivu
  $firstpage = $pdf->new_page('10.5x14.8cm');

  // koko kortin ympärille border
  $pdf->draw_rectangle(8, 8, 410, 290, $firstpage, $rectparam);

  $pdf->draw_rectangle(405, 230, 350, 285, $firstpage, $rectparam);
  $pdf->draw_text(238, 390, 'Port Payé', $firstpage, $iso);
  $pdf->draw_text(241, 380, 'Finlande', $firstpage, $iso);
  $pdf->draw_text(243, 370, $toitarow['sopimusnro'], $firstpage, $iso);
  $pdf->draw_text(241, 360, 'Posti Oy', $firstpage, $iso);

  // Jaetaan kirje osiin vaakaviivoilla
  $pdf->draw_rectangle(280, 8, 280, 290, $firstpage, $rectparam);
  $pdf->draw_rectangle(100, 8, 100, 290, $firstpage, $rectparam);

  $pdf->draw_text(13, 330, 'Lähettäjä / Avsend / Sender:', $firstpage, $iso);
  $pdf->draw_text(13, 317, $postirow["yhtio_nimi"], $firstpage, $iso);
  $pdf->draw_text(13, 306, $postirow["yhtio_osoite"], $firstpage, $iso);
  $pdf->draw_text(13, 296, "{$postirow['yhtio_postino']} {$postirow['yhtio_postitp']}", $firstpage, $iso);
  $pdf->draw_text(13, 285, 'Puh: '.$yhtiorow["puhelin"], $firstpage, $iso);

  $pdf->draw_text(13, 265, 'Vastaanottaja / Mottag / Consing:', $firstpage, $iso);
  $pdf->draw_text(13, 252, $rakir_row["toim_nimi"], $firstpage, $iso);
  $pdf->draw_text(13, 241, $rakir_row["toim_osoite"], $firstpage, $iso);
  $pdf->draw_text(13, 230, "{$rakir_row['toim_postino']} {$rakir_row['toim_postitp']}", $firstpage, $iso);

  $pdf->draw_text(13, 117, 'Viite:', $firstpage, $iso);

  if ($postirow['asiakkaan_tilausnumero'] != '') {
    $pdf->draw_text(40, 117, $postirow['asiakkaan_tilausnumero'], $firstpage, $iso);
    $pdf->draw_text(40, 105, $postirow['tilausyhteyshenkilo'], $firstpage, $iso);
  }
  else {
    $pdf->draw_text(40, 117, $postirow['tilausyhteyshenkilo'], $firstpage, $iso);
  }
}

if ($tulostakolli > 0) {

  if (isset($kirjoitin) and $kirjoitin == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_kirjetarra_{$postirow['tunnus']}' name='tulostakopioform_kirjetarra_{$postirow['tunnus']}' method='post' action='{$palvelin2}rahtikirja-kopio.php' autocomplete='off'>
          <input type='hidden' name='rtunnukset[]' value='{$rahtikirjanro}'>
          <input type='hidden' name='tee' value='tulosta'>
          <input type='hidden' name='real_submit' value='joo'>
          <input type='hidden' name='lasku_yhtio' value='{$kukarow['yhtio']}'>
          <input type='hidden' name='yksittainen' value='ON'>
          <input type='hidden' name='komento' value='PDF_RUUDULLE'>
          <input type='submit' value='".t("Kirjetarra").": {$postirow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_kirjetarra_{$postirow['tunnus']}', ''); return false;\"></form><br>";
  }
  else {

    // keksitään uudelle failille joku varmasti uniikki nimi:
    $pdffilenimi = "/tmp/rahtikirja-kirjetarra-".md5(uniqid(rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
    fclose($fh);

    //tulostetaan PDF
    if ($kirjoitin == 'email') {
      $liite = $pdffilenimi;
      $kutsu = "Kirjetarra";

      require "inc/sahkoposti.inc";
    }
    elseif ($kirjoitin == 'PDF_RUUDULLE') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    else {
      $line = exec("$kirjoitin $pdffilenimi");
    }
  }
}
