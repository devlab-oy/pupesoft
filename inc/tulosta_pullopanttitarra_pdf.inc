<?php

if (@include_once "pdflib/phppdflib.class.php") {
}
else {
  include_once "phppdflib.class.php";
}

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
  if (include_once "viivakoodi/Barcode.php");
  elseif (include_once "Barcode.php");
}

// Haetaan asiakkaan kieli
$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
$kielresult = pupe_query($querykiel);
$kielrow = mysql_fetch_array($kielresult);

if ($kielrow['kieli'] != '') {
  $kieli = strtolower($kielrow['kieli']);
}

// $iso["height"]         = 25;
// $iso["font"]           = "Helvetica-Bold";

// $keski["height"]         = 20;
// $keski["font"]           = "Helvetica-Bold";

$iso["height"]         = 45;
$iso["font"]           = "Helvetica-Bold";

$keski["height"]         = 35;
$keski["font"]           = "Helvetica-Bold";

$rectparam["width"]    = 0.3;
$rivinkorkeus          = 15;

//muutamat funktiot...
if (!function_exists('tulosta_pullopanttitarra_pdf')) {
  function tulosta_pullopanttitarra_pdf ($laskurow) {
    global $yhtiorow, $keski, $iso;

    $pdf_zebra_tarra = new pdffile;
    $sivu = 1;

    $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tunnus,
                sarjanumeroseuranta.sarjanumero
              FROM tilausrivi
                JOIN sarjanumeroseuranta ON (sarjanumeroseuranta.yhtio = tilausrivi.yhtio AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus)
              WHERE tilausrivi.yhtio = 'refai'
              AND tilausrivi.otunnus = '{$laskurow['tunnus']}';";
    $tuotetiedot_res = (pupe_query($query));

    while ($tilausrivi = mysql_fetch_assoc($tuotetiedot_res)){
  
      $pdf_zebra_tarra->set_default('margin-top',   20);
      $pdf_zebra_tarra->set_default('margin-bottom', 0);
      $pdf_zebra_tarra->set_default('margin-left',   0);
      $pdf_zebra_tarra->set_default('margin-right',  0);

      # vanha
      // $page_zebra_tarra[$sivu] = $pdf_zebra_tarra->new_page("zebra_tarra");

      // tulosta_logo_pdf($pdf_zebra_tarra, $page_zebra_tarra[$sivu], $laskurow, 300, 5);
      // $pdf_zebra_tarra->draw_text(5, 225, $laskurow["nimi"], $page_zebra_tarra[$sivu], $iso);
      // $pdf_zebra_tarra->draw_text(5, 202, $tilausrivi["nimitys"], $page_zebra_tarra[$sivu], $iso);

      // $pdf_zebra_tarra->draw_text(550, 280, $laskurow["tunnus"], $page_zebra_tarra[$sivu], $iso);
      // $pdf_zebra_tarra->draw_text(495, 257, date("d.m.Y"), $page_zebra_tarra[$sivu], $iso);


      // $data = viivakoodi(trim($tilausrivi["sarjanumero"]), "code128", 180, 32, "");

      // error_log(print_r($data, true));

      // $image = $pdf_zebra_tarra->jfif_embed($data);
      // $pdf_zebra_tarra->image_place($image, 40, 37, $page_zebra_tarra[$sivu]);

      // $pdf_zebra_tarra->draw_text(250, 32, $tilausrivi["sarjanumero"], $page_zebra_tarra[$sivu], $keski);

      // $pdf_zebra_tarra->draw_text(5, 10, t("Palautettava viimeistään:"), $page_zebra_tarra[$sivu], $iso);
      // $pdf_zebra_tarra->draw_text(305, 10, date("d.m.Y", strtotime("+6 months")), $page_zebra_tarra[$sivu], $iso);

      #uusi
      $page_zebra_tarra[$sivu] = $pdf_zebra_tarra->new_page("zebra_tarra");

      $query = "SELECT lasku_logo
                FROM yhtion_parametrit
                WHERE yhtio = '$laskurow[yhtio]'";
      $apu_yhtiorow = mysql_fetch_array(pupe_query($query));

      #tulosta_logo_pdf($pdf_zebra_tarra, $page_zebra_tarra[$sivu], $laskurow, 595, 5);
      $liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
      $data = $liite["data"];
      $image = $pdf_zebra_tarra->jfif_embed($data);
      $pdf_zebra_tarra->image_place($image, 495, 5, $page_zebra_tarra[$sivu], array("scale" => 0.5));

      $pdf_zebra_tarra->draw_text(5, 505, $laskurow["nimi"], $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(5, 465, $tilausrivi["nimitys"], $page_zebra_tarra[$sivu], $iso);

      $pdf_zebra_tarra->draw_text(1000, 565, $laskurow["tunnus"], $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(1000, 525, date("d.m.Y"), $page_zebra_tarra[$sivu], $iso);

      $data = viivakoodi(trim($tilausrivi["sarjanumero"]), "code128", 280, 74, "");

      error_log(print_r($data, true));

      $image = $pdf_zebra_tarra->jfif_embed($data);
      $pdf_zebra_tarra->image_place($image, 100, 37, $page_zebra_tarra[$sivu]);

      $pdf_zebra_tarra->draw_text(600, 70, $tilausrivi["sarjanumero"], $page_zebra_tarra[$sivu], $keski);

      $pdf_zebra_tarra->draw_text(5, 10, t("Palautettava viimeistään:"), $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(555, 10, date("d.m.Y", strtotime("+6 months")), $page_zebra_tarra[$sivu], $iso);

      $sivu = $sivu + 1;
    }

    zebra_tarra_tulostus_ruudulle($pdf_zebra_tarra);
  }
}

if (!function_exists('zebra_tarra_tulostus_ruudulle')) {
    function zebra_tarra_tulostus_ruudulle($pdf_zebra_tarra) {
    
    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Pullopanttitarra-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf_zebra_tarra->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    echo file_get_contents($pdffilenimi);
  }
}
