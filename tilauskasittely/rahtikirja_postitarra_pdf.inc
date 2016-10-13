<?php

// nyt on kaikki tiedot rahtikirjaa varten haettu..
//
// arrayt:
// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
// $rakir_row:sta lˆytyy asiakkaan tiedot ja $rivi:st‰ ytunnus
//
// muuttujat:
// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kollityht, kilotyht, kuutiotyht, kirjoitin
// jv tapauksissa on myˆs yhteensa, summa, jvhinta, lasno ja viite muuttujat
//
// tulostetaan rahtikirja

// Setataan tarvittavat muuttujat
require_once "rahtikirja_postitarra_otsikkotiedot.inc";

require_once "pdflib/phppdflib.class.php";

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
  if (@include_once "viivakoodi/Barcode.php");
  else include_once "Barcode.php";
}

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

if (substr(trim($rivi["rahtikirjanro"]), 0, 4) == "JJFI") {
  $oma = sprintf("%09s", substr(trim($rivi["rahtikirjanro"]), -9));
}
else {
  $oma = sprintf("%09s", $rtunnus); // 9, pitk‰ numero tulee rahtikirja taulun tunnuksesta, pit‰‰ ollaa uniikki
}

// T‰‰ll‰ tehd‰‰n aina uusiksi postin seurantakoodit=viivakoodit, joten
// p‰ivitet‰‰n ne rahtikirjat-tauluun myˆs rahtikirjaa muutettaessa,
// kun lis‰t‰‰n/v‰hennet‰‰n kolleja rahtikirjalla
$rahtikirjanro = '';
$muutos = '';

// tulostetaan niin monta lappua kun on kollejakin
for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

  $kuka = sprintf("%02s", $tulostuskpl); // laitetaan tulostuskpl alkuun niin joka lapulla on uniikki viiva

  // tehd‰‰n viivakoodit
  $viiva1   = "JJFI$toitarow[sopimusnro]$kuka$oma";

  $viivaedi = "JJFI$toitarow[sopimusnro]$kuka$oma";

  $viiva2   = "JJFI $toitarow[sopimusnro] $kuka$oma";
  $v2_alku  = substr($viiva2, 0, -6);
  $v2_loppu = substr($viiva2, -6);

  // erotellaan spacella jokainen rahtikirjanumero
  $rahtikirjanro .= "$viiva2 ";

  // tehd‰‰n pdf:n uusi sivu
  $firstpage = $pdf->new_page("a5");

  if (class_exists("Image_Barcode")) {
    //tehd‰‰n JJFI viivakoodi
    $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

    imagejpeg(Image_Barcode::draw($viiva1, 'code128', 'jpg', false, 3, 170), $nimi);

    $fh = fopen($nimi, "r");
    $data = fread($fh, filesize($nimi));
    fclose($fh);

    $image = $pdf->jfif_embed($data);

    $logoparam['scale'] = 122/282;
    $pdf->image_place($image, 275, 30, $firstpage, $logoparam);
    system("rm -f $nimi");

    // postin koodiksi
    $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

    imagejpeg(Image_Barcode::draw($tuoteviiva1, 'code128', 'jpg', false, 3, 130), $nimi);

    $fh = fopen($nimi, "r");
    $data = fread($fh, filesize($nimi));
    fclose($fh);

    $image = $pdf->jfif_embed($data);

    $logoparam['scale'] = 122/282;
    $pdf->image_place($image, 540, 255, $firstpage, $logoparam);
    system("rm -f $nimi");
  }

  // sitten aletaan piirt‰m‰‰n itse PDF sis‰ltˆ‰
  $pdf->draw_text(50, 570, $keltainen, $firstpage, $isobold);
  $pdf->draw_text(50, 560, $kuljetus, $firstpage, $isobold);
  $pdf->draw_text(220, 570, $keku, $firstpage, $huge);

  $pdf->draw_text(18, 540, "L‰hett‰j‰ Avs‰ndare From", $firstpage, $pieni);
  $pdf->draw_text(18, 530, $postirow["yhtio_nimi"], $firstpage, $norm);
  $pdf->draw_text(18, 520, $postirow["yhtio_osoite"], $firstpage, $norm);
  $pdf->draw_text(18, 510, $postirow["yhtio_postino"]." ".$postirow["yhtio_postitp"], $firstpage, $norm);

  $pdf->draw_text(18, 490, "Vastaanottaja Addressat To       puh.", $firstpage, $pieni);
  $pdf->draw_text(120, 490, $rakir_row["toim_puh"], $firstpage, $norm);
  $pdf->draw_text(18, 480, $rakir_row["toim_nimi"], $firstpage, $iso);
  $pdf->draw_text(18, 470, $rakir_row["toim_nimitark"], $firstpage, $iso);
  $pdf->draw_text(18, 460, $rakir_row["toim_osoite"], $firstpage, $iso);
  $pdf->draw_text(18, 430, strtoupper($rakir_row['toim_maa']).'-'.$rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"], $firstpage, $isobold);

  // tuoteviivakoodin alle
  $pdf->draw_text(310, 532, $tuoteviiva1, $firstpage, $viivacourier);

  // jos k‰ytet‰‰n edi‰
  if ($postiedihost != "" and $postiediuser != "" and $postiedipass != "" and $postiedipath != "") {
    $pdf->draw_text(345, 510, "EDI", $firstpage, $huge);
  }

  $pdf->draw_text(250, 490, "P‰iv‰m‰‰r‰ Datum", $firstpage, $pieni);
  $pdf->draw_text(250, 478, date("d.m.Y"), $firstpage, $norm);

  $pdf->draw_rectangle(495, 245, 450, 245, $firstpage, $rectparam);
  $pdf->draw_rectangle(472, 245, 472, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(472, 322, 450, 322, $firstpage, $rectparam);
  $pdf->draw_rectangle(450, 245, 450, 400, $firstpage, $rectparam);

  $pdf->draw_text(255, 458, $kilotyht, $firstpage, $norm);
  $pdf->draw_text(312, 458, "kg", $firstpage, $pieni);
  $pdf->draw_text(330, 458, $kuutiotyht, $firstpage, $norm);
  $pdf->draw_text(390, 458, "m3", $firstpage, $pieni);

  // asiakkaan postitoimipaikan alle
  $pdf->draw_rectangle(428, 18, 428, 300, $firstpage, $rectparam);
  $pdf->draw_rectangle(400, 18, 400, 300, $firstpage, $rectparam);
  $pdf->draw_rectangle(380, 18, 380, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(428, 300, 400, 300, $firstpage, $rectparam);
  $pdf->draw_rectangle(428, 280, 400, 280, $firstpage, $rectparam);
  $pdf->draw_rectangle(428, 110, 400, 110, $firstpage, $rectparam);
  $pdf->draw_rectangle(400, 220, 380, 220, $firstpage, $rectparam);
  $pdf->draw_rectangle(365, 300, 380, 300, $firstpage, $rectparam);
  $pdf->draw_text(18, 422, "Lis‰palvelut Till‰ggstj‰nster", $firstpage, $pieni);
  $pdf->draw_text(282, 422, "Kpl St.", $firstpage, $pieni);
  $pdf->draw_text(112, 422, "Maksaja muu kuin l‰hett‰j‰ Betalaren annan ‰n avs‰ndaren", $firstpage, $pieni);
  $pdf->draw_text(112, 408, "Maksajan sopimustunnus", $firstpage, $pieni);
  $pdf->draw_text(112, 402, "Betalarens avtalskod", $firstpage, $pieni);
  $pdf->draw_text(18, 394, "PE-summa PF-belopp", $firstpage, $pieni);
  $pdf->draw_text(222, 394, "Tilinumero Kontonummer", $firstpage, $pieni);
  $pdf->draw_text(18, 374, "Pankkiviite Bank referens", $firstpage, $pieni);
  $pdf->draw_text(302, 374, "BIC", $firstpage, $pieni);

  // v‰h‰ arvoja
  $pdf->draw_text(190, 405, $rahdinmaksajan_nr, $firstpage, $isobold);
  $pdf->draw_text(18, 384, $postiennakkomaara, $firstpage, $iso);
  $pdf->draw_text(222, 384, $postiennakkotilino, $firstpage, $iso);
  $pdf->draw_text(112, 370, $postiennakkoviite, $firstpage, $iso);
  $pdf->draw_text(303, 366, $postiennakkobic, $firstpage, $iso);
  $pdf->draw_text(285, 405, $kollityht, $firstpage, $iso);

  // lis‰palvelut laatikot
  $pdf->draw_rectangle(402, 18, 420, 38, $firstpage, $rectparam);
  $pdf->draw_rectangle(402, 40, 420, 60, $firstpage, $rectparam);
  $pdf->draw_rectangle(402, 62, 420, 82, $firstpage, $rectparam);
  $pdf->draw_rectangle(402, 84, 420, 104, $firstpage, $rectparam);
  $pdf->draw_text(23, 406, $x[0], $firstpage, $iso);
  $pdf->draw_text(45, 406, $x[1], $firstpage, $iso);
  $pdf->draw_text(67, 406, $x[2], $firstpage, $iso);
  $pdf->draw_text(89, 406, $x[3], $firstpage, $iso);

  // viiva viivakooditekstin yl‰ ja alapuolelle
  $pdf->draw_rectangle(260, 18, 260, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(365, 18, 365, 400, $firstpage, $rectparam);
  $pdf->draw_text(140, 262, $v2_alku, $firstpage, $viivacourier);
  $pdf->draw_text(140, 357, $v2_alku, $firstpage, $viivacourier);

  $pdf->draw_text(247, 262, $v2_loppu, $firstpage, $viivacourierbold);
  $pdf->draw_text(247, 357, $v2_loppu, $firstpage, $viivacourierbold);

  // viivakoodin alapuolella vasen laita
  $pdf->draw_text(18, 250, "SAAPUMISILMOITUS ANKOMSTAVI", $firstpage, $pienibold);

  if (count($vakit) > 0) {
    $pdf->draw_text(18, 242, "Lis‰tietoja 0200 77000, www.posti.fi * Mera information 0200 75000, www.posti.fi.", $firstpage, $pieni);
    $pdf->draw_text(18, 234, "S‰ilytysaika on yksi (1) t‰ysi kalenteriviikko * Kvarligger en (1) hel kalendervecka.", $firstpage, $pieni);
  }
  else {
    $pdf->draw_text(18, 242, "L‰hetys on noudettavissa Postistanne. Lis‰tietoja: 0200 77000, www.posti.fi S‰ilytysaika on 2 t‰ytt‰ kalenteriviikkoa.", $firstpage, $pieni);
    $pdf->draw_text(18, 234, "Ni kan h‰mta fˆrs‰ndelsen pÂ Ert postkontor. Mera information: 0200 75000, www.posti.fi Kvarligger 2 hela kalenderveckor.", $firstpage, $pieni);
  }

  $pdf->draw_text(18, 215, "L‰hett‰j‰ Avs‰ndare From", $firstpage, $pieni);
  $pdf->draw_text(18, 206, $postirow["yhtio_nimi"], $firstpage, $norm);
  $pdf->draw_text(18, 197, $postirow["yhtio_osoite"], $firstpage, $norm);
  $pdf->draw_text(18, 188, $postirow["yhtio_postino"]." ".$postirow["yhtio_postitp"], $firstpage, $norm);

  $pdf->draw_text(18, 180, "Vastaanottaja Addressat To       puh.", $firstpage, $pieni);
  $pdf->draw_text(120, 180, $rakir_row["toim_puh"], $firstpage, $pieni);
  $pdf->draw_text(18, 170, $rakir_row["toim_nimi"], $firstpage, $iso);
  $pdf->draw_text(18, 160, $rakir_row["toim_nimitark"], $firstpage, $iso);
  $pdf->draw_text(18, 150, $rakir_row["toim_osoite"], $firstpage, $iso);
  $pdf->draw_text(18, 130, strtoupper($rakir_row['toim_maa']).'-'.$rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"], $firstpage, $isobold);

  // viivakoodin alapuolella vasen laita
  $oikpos = $pdf->strlen("$keltainen $kuljetus", $pienibold);
  $pdf->draw_text(400-$oikpos, 250, "$keltainen $kuljetus", $firstpage, $pienibold);
  $pdf->draw_text(250, 225, "Lis‰tiedot Till‰ggsuppgifter", $firstpage, $pieni);

  $apkal = 216;

  $x_koordinaatti = 250;

  $cnt = 1;

  foreach ($x as $_koodi) {

    if ($cnt == 5) break;

    $pdf->draw_text($x_koordinaatti, $apkal, $x_tekstit[$_koodi], $firstpage, $norm);

    if ($x_koordinaatti == 250) {
      $x_koordinaatti = 320;
    }
    else {
      $x_koordinaatti = 250;
      $apkal -= 9;
    }

    $cnt++;
  }

  $pdf->draw_text(250, 194, "Sis‰ltˆ InnehÂll", $firstpage, $pieni);
  $pdf->draw_text(250, 186, "Kpl St.", $firstpage, $pieni);
  $pdf->draw_text(340, 178, "kg", $firstpage, $pieni);
  $pdf->draw_text(352, 186, "Pvm Datum", $firstpage, $pieni);
  $pdf->draw_text(250, 168, "PE-summa PF-belopp", $firstpage, $pieni);
  $pdf->draw_text(340, 168, "BIC", $firstpage, $pieni);
  $pdf->draw_text(250, 150, "Tilinumero Kontonummer", $firstpage, $pieni);
  $pdf->draw_text(250, 130, "Pankkiviite Bank referens", $firstpage, $pieni);

  $pdf->draw_rectangle(192, 250, 192, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(174, 250, 174, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(156, 250, 156, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(136, 250, 136, 400, $firstpage, $rectparam);
  $pdf->draw_rectangle(192, 290, 174, 290, $firstpage, $rectparam);
  $pdf->draw_rectangle(192, 350, 174, 350, $firstpage, $rectparam);
  $pdf->draw_rectangle(156, 335, 174, 335, $firstpage, $rectparam);

  // ja v‰h‰ arvoja
  $pdf->draw_text(255, 205, "", $firstpage, $iso);  // t‰‰ on pikku lis‰tietoja kentt‰ JJFI koodin alla vasemmalla
  $pdf->draw_text(255, 178, $kollityht, $firstpage, $iso);
  $pdf->draw_text(297, 178, $kilotyht, $firstpage, $iso);
  $pdf->draw_text(357, 178, date("d.m.Y"), $firstpage, $iso);
  $pdf->draw_text(255, 160, $postiennakkomaara, $firstpage, $iso);
  $pdf->draw_text(340, 160, $postiennakkobic, $firstpage, $iso);
  $pdf->draw_text(255, 140, $postiennakkotilino, $firstpage, $iso);
  $pdf->draw_text(255, 122, $postiennakkoviite, $firstpage, $iso);

  // viiva alle
  $pdf->draw_rectangle(118, 18, 118, 400, $firstpage, $rectparam);

  // Lis‰tiedot
  $pdf->draw_text(18, 110, "Kuittaus ja nimen selvennys Kvittering och namnfˆrtydligande", $firstpage, $pieni);
  $pdf->draw_text(40, 80, ".               .                        klo kl.", $firstpage, $pieni);
  $pdf->draw_text(18, 70, "Lis‰tiedot Till‰ggsuppgifter", $firstpage, $pieni);
  $pdf->draw_text(18, 10, t("Posti Green - ilmastoyst‰v‰llinen kuljetus"), $firstpage, $iso);

  $komm = "";

  $cnt = 1;

  foreach ($x as $_koodi) {
    $komm .= $x_tekstit_koko[$_koodi]." ";
  }

  if (is_array($vakit)) {
    $komm .= "\n";

    foreach ($vakit as $vak) {
      $komm .= "$vak.\n";
    }
  }

  if ($kuljetusohjeet != "") {
    $komm .= "$kuljetusohjeet";
  }

  if ($yhtiorow["osoitelappu_lisatiedot"] == "") {
    if ($postirow['tilausyhteyshenkilo'] != '' or $postirow['asiakkaan_tilausnumero'] != '') $komm .= "\n";
    $komm .= $postirow['tilausyhteyshenkilo'] != '' ? t("Tilaaja").": {$postirow['tilausyhteyshenkilo']}. " : '';
    $komm .= $postirow['asiakkaan_tilausnumero'] != '' ? t("Tilaus").": {$postirow['asiakkaan_tilausnumero']}. " : '';
  }

  $pdf->draw_paragraph(70, 18, 15, 380, $komm, $firstpage, $isobold);

  // koko kortin ymp‰rille border
  $pdf->draw_rectangle(0, 0, 598, 418, $firstpage, $rectparam);

  //l‰hetet‰‰n postin edisanoma
  if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
    require "postiedi.inc";
  }
}

if ($tulostakolli > 0) {

  if (isset($kirjoitin) and $kirjoitin == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_postitarra_{$postirow['tunnus']}' name='tulostakopioform_postitarra_{$postirow['tunnus']}' method='post' action='{$palvelin2}rahtikirja-kopio.php' autocomplete='off'>
          <input type='hidden' name='rtunnukset[]' value='{$rahtikirjanro}'>
          <input type='hidden' name='tee' value='tulosta'>
          <input type='hidden' name='real_submit' value='joo'>
          <input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
          <input type='hidden' name='yksittainen' value='ON'>
          <input type='hidden' name='komento' value='PDF_RUUDULLE'>
          <input type='submit' value='".t("Pakettikortti").": {$postirow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_postitarra_{$postirow['tunnus']}', ''); return false;\"></form><br>";
  }
  else {

    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
    fclose($fh);

    //tulostetaan PDF
    if ($kirjoitin == 'email') {
      $liite = $pdffilenimi;
      $kutsu = "Postitarra";

      require "inc/sahkoposti.inc";
    }
    elseif ($kirjoitin == 'PDF_RUUDULLE') {
      //Tyˆnnet‰‰n tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    else {
      $line = exec("$kirjoitin $pdffilenimi");
    }
  }
}
