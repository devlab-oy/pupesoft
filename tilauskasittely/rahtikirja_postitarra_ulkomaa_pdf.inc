<?php

// nyt on kaikki tiedot rahtikirjaa varten haettu..
//
// arrayt:
// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
// $rakir_row:sta löytyy asiakkaan tiedot ja $rivi:stä ytunnus
//
// muuttujat:
// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kollityht, kilotyht, kuutiotyht, kirjoitin
// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
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
$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);

$rectparam["width"]     = 0.3;

$norm["height"]       = 8.5;
$norm["font"]         = "Helvetica";

$pieni["height"]       = 6;
$pieni["font"]         = "Helvetica";

$pienibold["height"]     = 7;
$pienibold["font"]       = "Helvetica-Bold";

$iso["height"]         = 9;
$iso["font"]         = "Helvetica";

$isobold["height"]       = 10.5;
$isobold["font"]       = "Helvetica-Bold";

$viivacourier["height"]   = 10;
$viivacourier["font"]     = "Courier";

$viivacourierbold["height"] = 10;
$viivacourierbold["font"]   = "Courier-Bold";

$huge["height"]       = 20;
$huge["font"]         = "Helvetica-Bold";

$query_ale_lisa = generoi_alekentta('M');


if ($otunnukset != "") {
  // Tilausken arvo
  $query = "SELECT lasku.valkoodi,
            sum(round(tilausrivi.hinta
              * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)
              * {$query_ale_lisa}, '$yhtiorow[hintapyoristys]')) rivihinta,
            lasku.toimitustapa
            FROM tilausrivi
            JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
            where tilausrivi.otunnus in ($otunnukset)
            and tilausrivi.yhtio     = '$kukarow[yhtio]'
            and tilausrivi.var       in ('','H')
            and tilausrivi.tyyppi    in ('L','G')
            GROUP BY 1";
  $riresult = pupe_query($query);
  $rirow = mysql_fetch_assoc($riresult);
}
else {
  $rirow = array();
  $rirow["valkoodi"]  = "";
  $rirow["rivihinta"] = "";
}

// defaultteja
$kala = 575;
$lask = 1;
$sivu = 1;

$tulostakolli = $tulostuskpl;

// tulostetaan niin monta lappua kun on kollejakin
for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

  if (substr($rivi["rahtikirjanro"], 0, 2) == "CE") {
    $rakirritn = explode(" ", trim($rivi["rahtikirjanro"]));

    $juokseva = $rakirritn[($tulostuskpl-1)];
  }
  else {
    // Haetaan seuraava vapaa numero
    $query = "SELECT rahtikirjanro
              FROM rahtikirjat
              WHERE yhtio       = '$kukarow[yhtio]'
              AND rahtikirjanro LIKE '%CE%FI%'
              ORDER BY tunnus desc
              LIMIT 1";
    $ranres = pupe_query($query);
    $ranrow = mysql_fetch_assoc($ranres);

    $edmax = array_pop(explode(" ", trim($ranrow["rahtikirjanro"])));

    $_koodipos = strpos($edmax, "CE") + 6;
    $juokseva = sprintf("%04d", $tulostuskpl + (int) substr($edmax, $_koodipos, 4));
  }

  if (substr($juokseva, 0, 2) != "CE" or substr($juokseva, -2, 2) != "FI") {
    // Lähetystunnuksen tarkistenumeron laskentakaava (modulus 11):
    $viiva = mod11_tarkiste($toitarow['tunhalnro'].$juokseva);
  }
  else {
    $viiva = $juokseva;
  }

  if (substr($viiva, 0, 2) != "CE" and substr($viiva, -2, 2) != "FI") {
    $viiva1   = "CE".$viiva."FI";
    $viivaedi = "CE".$viiva."FI";
    $viiva2   = "*CE".$viiva."FI*";
  }
  elseif (substr($viiva, 0, 2) != "CE") {
    $viiva1   = "CE".$viiva;
    $viivaedi = "CE".$viiva;
    $viiva2   = "*CE".$viiva."*";
  }
  elseif (substr($viiva, -2, 2) != "FI") {
    $viiva1   = $viiva."FI";
    $viivaedi = $viiva."FI";
    $viiva2   = "*".$viiva."FI*";
  }
  else {
    $viiva1   = $viiva;
    $viivaedi = $viiva;
    $viiva2   = "*".$viiva."*";
  }

  // erotellaan spacella jokainen rahtikirjanumero
  $rahtikirjanro .= "$viiva1 ";

  // tehdään pdf:n uusi sivu
  $firstpage = $pdf->new_page("a5");

  if ($keltainen == "Priority") {
    $data = file_get_contents($pupe_root_polku."/pics/Itella_priority.jpg");
    $image = $pdf->jfif_embed($data);
    $logoparam['scale'] = 0.105;
    $pdf->image_place($image, 560, 70, $firstpage, $logoparam);
  }
  else {
    $pdf->draw_text(65, 570, $keltainen, $firstpage, $norm);
  }

  if (class_exists("Image_Barcode")) {
    // Lähetystunnus
    $data = viivakoodi($viiva1, "code39", 280, 70, "");
    $image = $pdf->jfif_embed($data);
    $logoparam['scale'] = 122/282;
    $pdf->image_place($image, 270, 30, $firstpage, $logoparam);

    // Tuotetunnus
    $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

    imagejpeg(Image_Barcode::draw($tuoteviiva1, 'code128', 'jpg', false, 3, 130), $nimi);

    $fh = fopen($nimi, "r");
    $data = fread($fh, filesize($nimi));
    fclose($fh);

    $image = $pdf->jfif_embed($data);

    $logoparam['scale'] = 122/282;
    $pdf->image_place($image, 540, 255, $firstpage, $logoparam);
    system("rm -f $nimi");

    // Sopimustunnus
    $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

    imagejpeg(Image_Barcode::draw("9V".$toitarow["sopimusnro"], 'code128', 'jpg', false, 3, 130), $nimi);

    $fh = fopen($nimi, "r");
    $data = fread($fh, filesize($nimi));
    fclose($fh);

    $image = $pdf->jfif_embed($data);

    $logoparam['scale'] = 112/282;
    $pdf->image_place($image, 30, 245, $firstpage, $logoparam);
    system("rm -f $nimi");
  }

  // sitten aletaan piirtämään itse PDF sisältöä
  $pdf->draw_text(18,  550,  "Sender (name and address) / Expéditeur (nom et adresse)",           $firstpage, $pieni);
  $pdf->draw_text(18,  540, $postirow["yhtio_nimi"],             $firstpage, $norm);
  $pdf->draw_text(18,  530, $postirow["yhtio_osoite"],          $firstpage, $norm);
  $pdf->draw_text(18,  520, $postirow["yhtio_postino"]." ".$postirow["yhtio_postitp"], $firstpage, $norm);
  $pdf->draw_text(18,  510, $postirow["yhtio_maa"],             $firstpage, $norm);

  $data = file_get_contents($pupe_root_polku."/pics/Itella_puhelin.jpg");
  $image = $pdf->jfif_embed($data);
  $logoparam['scale'] = 0.12;
  $pdf->image_place($image, 493, 18, $firstpage, $logoparam);
  $pdf->draw_text(35, 495,  $yhtiorow["puhelin"],             $firstpage, $iso);

  $pdf->draw_text(115,  500,  "Sender's business ID /",    $firstpage, $pieni);
  $pdf->draw_text(115,  495,  "No de T.V.A. de l'expéditeur",    $firstpage, $pieni);
  $pdf->draw_text(185,  500,  $postirow["yhtio_maa"]."-".preg_replace("/^0037/", "", $postirow["yhtio_ovttunnus"]),    $firstpage, $norm);


  $pdf->draw_text(18,  485,  "Recipient (name and address) / Destinataire (nom et adresse)",    $firstpage, $pieni);
  $pdf->draw_text(120, 485,  $rakir_row["toim_puh"],             $firstpage, $pieni);
  $pdf->draw_text(18,  475,  $rakir_row["toim_nimi"],             $firstpage, $iso);
  $pdf->draw_text(18,  465,  $rakir_row["toim_nimitark"],         $firstpage, $iso);
  $pdf->draw_text(18,  455,  $rakir_row["toim_osoite"],           $firstpage, $iso);
  $pdf->draw_text(18,  445,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"], $firstpage, $iso);
  $pdf->draw_text(18,  435,  $rakir_row['toim_maa'],      $firstpage, $iso);

  $data = file_get_contents($pupe_root_polku."/pics/Itella_puhelin.jpg");
  $image = $pdf->jfif_embed($data);
  $logoparam['scale'] = 0.12;
  $pdf->image_place($image, 418, 18, $firstpage, $logoparam);

  $pdf->draw_text(35, 420,  $rakir_row["toim_puh"],             $firstpage, $iso);

  $pdf->draw_text(115,  425,  "VAT No /",    $firstpage, $pieni);
  $pdf->draw_text(115,  420,  "No de T.V.A.",    $firstpage, $pieni);

  if ($rakir_row["ovttunnus"] != "") {
    $pdf->draw_text(155,  420,  $rakir_row['maa']."-".preg_replace("/^0037/", "", $rakir_row["ovttunnus"]),    $firstpage, $norm);
  }

  // tuoteviivakoodin alle
  $pdf->draw_text(310, 532,  $tuoteviiva1,                 $firstpage, $viivacourier);

  // jos käytetään ediä
  if ($postiedihost != "" and $postiediuser != "" and $postiedipass != "" and $postiedipath != "") {
    $pdf->draw_text(345, 510,  "EDI",                   $firstpage, $huge);
  }

  $pdf->draw_text(250, 490,  "Date of posting / Date du dépôt", $firstpage, $pieni);
  $pdf->draw_text(250, 479,  date("d.m.Y"),                 $firstpage, $norm);

  $pdf->draw_rectangle(495, 245, 450, 245,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(476, 245, 476, 400,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(476, 322, 450, 322,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(450, 245, 450, 400,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(450, 245, 432, 245,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(432, 245, 432, 400,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(432, 245, 398, 245,                   $firstpage, $rectparam);

  $pdf->draw_rectangle(430, 247, 420, 258,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(420, 247, 410, 258,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(410, 247, 400, 258,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(430, 330, 420, 341,                   $firstpage, $rectparam);

  $pdf->draw_text(250, 469,  "Shipment weight /", $firstpage, $pieni);
  $pdf->draw_text(250, 463,  "Poids de l'envoi", $firstpage, $pieni);
  $pdf->draw_text(255, 453,  $kilotyht,                   $firstpage, $norm);
  $pdf->draw_text(312, 453,  "kg",                     $firstpage, $pieni);

  $pdf->draw_text(330, 469,  "Shipment volume /", $firstpage, $pieni);
  $pdf->draw_text(330, 463,  "Volume de l'envoi", $firstpage, $pieni);
  $pdf->draw_text(330, 453,  $kuutiotyht,                 $firstpage, $norm);
  $pdf->draw_text(390, 453,  "m3",                     $firstpage, $pieni);

  $pdf->draw_text(250, 444,  "Pieces Pièces", $firstpage, $pieni);
  $pdf->draw_text(255, 435,  $kollityht,                  $firstpage, $iso);


  $pdf->draw_text(260, 423,  "Sample/Enchatillon",   $firstpage, $pieni);
  $pdf->draw_text(260, 414,  "Gift/Cadeau",       $firstpage, $pieni);
  $pdf->draw_text(260, 405,  "Merchandise, value",   $firstpage, $pieni);
  $pdf->draw_text(260, 400,  "Marchandises, valeur",   $firstpage, $pieni);
  $pdf->draw_text(343, 423,  "Documents",       $firstpage, $pieni);
  $pdf->draw_text(249,  401,  "X",          $firstpage, $isobold);
  $pdf->draw_text(329,  401,  $rirow["valkoodi"]." ".$rirow["rivihinta"],  $firstpage, $norm);

  // asiakkaan postitoimipaikan alle
  $pdf->draw_rectangle(398, 18, 398, 400,                   $firstpage, $rectparam);
  $pdf->draw_text(18,  390,  "In case of non-delivery, please / En cas de non-livraison, veuillez",  $firstpage, $pieni);

  $pdf->draw_rectangle(385, 18, 375, 28,                   $firstpage, $rectparam);
  $pdf->draw_text(30,  380,  "treat item as abandoned",          $firstpage, $pieni);
  $pdf->draw_text(30,  375,  "traiter l'envoi comme abandonné",      $firstpage, $pieni);

  $pdf->draw_rectangle(385, 128, 375, 138,                   $firstpage, $rectparam);
  $pdf->draw_text(140,  380,  "return to the sender immediately",      $firstpage, $pieni);
  $pdf->draw_text(140,  375,  "renvoyer à l'expéditeur immédiatement",  $firstpage, $pieni);

  $pdf->draw_rectangle(385, 250, 375, 260,                   $firstpage, $rectparam);
  $pdf->draw_text(263,  380,  "by air",    $firstpage, $pieni);
  $pdf->draw_text(263,  375,  "par avion",  $firstpage, $pieni);

  $pdf->draw_rectangle(385, 300, 375, 310,                   $firstpage, $rectparam);
  $pdf->draw_text(313,  380,  "the most economical route",  $firstpage, $pieni);
  $pdf->draw_text(313,  375,  "par la route la plus économique",  $firstpage, $pieni);
  $pdf->draw_text(302,  376,  "X",  $firstpage, $isobold);

  // viiva viivakooditekstin ylä ja alapuolelle
  $pdf->draw_rectangle(260, 18, 260, 400,                   $firstpage, $rectparam);
  $pdf->draw_rectangle(370, 18, 370, 400,                   $firstpage, $rectparam);
  $pdf->draw_text(140, 262,  $viiva2,                    $firstpage, $viivacourier);
  $pdf->draw_text(140, 362,  $viiva2,                    $firstpage, $viivacourier);

  // viivakoodin alapuolella
  $pdf->draw_text(18,  250,  "Sender / Expéditeur",             $firstpage, $pieni);
  $pdf->draw_text(80,  250, $postirow["yhtio_nimi"]." ".$postirow["yhtio_osoite"]." ".$postirow["yhtio_postino"]." ".$postirow["yhtio_postitp"]." ".$postirow["yhtio_maa"], $firstpage, $norm);
  $pdf->draw_rectangle(245, 18, 245, 400,                   $firstpage, $rectparam);

  $pdf->draw_text(18,  237,  "Recipient (name and address)",   $firstpage, $pieni);
  $pdf->draw_text(18,  228,  "Destinataire(nometadresse)",   $firstpage, $pieni);
  $pdf->draw_text(110,  237,  $rakir_row["toim_nimi"]." ".$rakir_row["toim_nimitark"], $firstpage, $norm);
  $pdf->draw_text(110,  228,  $rakir_row["toim_osoite"]." ".$rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"]." ".strtoupper($rakir_row['toim_maa']), $firstpage, $norm);
  $pdf->draw_rectangle(225, 18, 225, 400,                   $firstpage, $rectparam);

  $pdf->draw_text(18,  218,  "Description of contents",   $firstpage, $pieni);
  $pdf->draw_text(18,  212,  "Description détaillée du contenu",   $firstpage, $pieni);

  $pdf->draw_text(120,  218,  "Number",   $firstpage, $pieni);
  $pdf->draw_text(120,  212,  "Nombre",   $firstpage, $pieni);

  $pdf->draw_text(150,  218,  "Net weight",   $firstpage, $pieni);
  $pdf->draw_text(150,  212,  "Poids net",   $firstpage, $pieni);

  $pdf->draw_text(200,  218,  "Country of origin",   $firstpage, $pieni);
  $pdf->draw_text(200,  212,  "Pays d'origine",   $firstpage, $pieni);

  $pdf->draw_text(255,  218,  "Customs tariff No",   $firstpage, $pieni);
  $pdf->draw_text(255,  212,  "No tarifaire",   $firstpage, $pieni);

  $pdf->draw_text(320,  218,  "Value / Currency",   $firstpage, $pieni);
  $pdf->draw_text(320,  212,  "Valeur / Monnaie",   $firstpage, $pieni);
  $pdf->draw_rectangle(208, 18, 208, 400,                   $firstpage, $rectparam);

  // Lisätiedot
  $pdf->draw_rectangle(118, 18, 118, 400,                   $firstpage, $rectparam);
  $pdf->draw_text(18, 110,  "Date",  $firstpage, $pieni);
  $pdf->draw_text(70, 110,  "Time/Heure",  $firstpage, $pieni);
  $pdf->draw_text(130, 110,  "Recipient's signature Signature du destinataire",  $firstpage, $pieni);

  $pdf->draw_text(18, 20,   t("Posti Green - Carbon neutral delivery"),          $firstpage, $iso);

  $pdf->draw_text(270, 20,  "9V".$toitarow['sopimusnro'],    $firstpage, $viivacourier);

  //lähetetään postin edisanoma
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
    //keksitään uudelle failille joku varmasti uniikki nimi:
    $pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
    fclose($fh);

    //tulostetaan PDF
    if ($kirjoitin == 'email') {
      $liite = $pdffilenimi;
      $kutsu = "Postitarra";

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
