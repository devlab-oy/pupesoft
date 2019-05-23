<?php

if (@include_once "pdflib/phppdflib.class.php") {
}
else {
  include_once "phppdflib.class.php";
}

// Haetaan asiakkaan kieli
$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
$kielresult = pupe_query($querykiel);
$kielrow = mysql_fetch_array($kielresult);

if ($kielrow['kieli'] != '') {
  $kieli = strtolower($kielrow['kieli']);
}

$iso["height"]         = 25;
$iso["font"]           = "Helvetica";

$keski["height"]         = 20;
$keski["font"]           = "Helvetica";

$rectparam["width"]    = 0.3;
$rivinkorkeus          = 15;

//muutamat funktiot...
if (!function_exists('tulosta_pullopanttitarra_pdf')) {
  function tulosta_pullopanttitarra_pdf ($laskurow) {
    global $yhtiorow, $keski, $iso;

    $sivu = 0;
    $tarrat = "";

    $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tunnus,
                sarjanumeroseuranta.sarjanumero
              FROM tilausrivi
                JOIN sarjanumeroseuranta ON (sarjanumeroseuranta.yhtio = tilausrivi.yhtio AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus)
              WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
              AND tilausrivi.otunnus = '{$laskurow['tunnus']}';";
    $tuotetiedot_res = (pupe_query($query));

    while ($tilausrivi = mysql_fetch_assoc($tuotetiedot_res)){

      $pdf_zebra_tarra = new pdffile;

      // Tehd‰‰n viivakoodi pdf:n‰, jotta siit‰ saadaan riitt‰v‰n tarkka tarratulostimelle ja k‰ytet‰‰n viivakoodin pdf:‰‰ tarran pohjana
      $filename = pdf_viivakoodi(trim($tilausrivi["sarjanumero"]), "code128", 180, 32, "");
      $pdf_zebra_tarra->enable('import');

      ob_start();

      $handle = fopen($filename, "r");
      $d = fread($handle, filesize($filename));

      fclose($handle);

      $pdf_zebra_tarra->import->append($d);
      $page_zebra_tarra = $pdf_zebra_tarra->import->get_pages();

      ob_end_clean();
  
      $pdf_zebra_tarra->set_default('margin-top',   20);
      $pdf_zebra_tarra->set_default('margin-bottom', 0);
      $pdf_zebra_tarra->set_default('margin-left',   0);
      $pdf_zebra_tarra->set_default('margin-right',  0);

      $data = file_get_contents("../pics/mustavalkologo.jpg", true);
      
      $image = $pdf_zebra_tarra->jfif_embed($data);
      $pdf_zebra_tarra->image_place($image, 140, -50, $page_zebra_tarra[$sivu], array("scale" => 0.3));

      $pdf_zebra_tarra->draw_text(-40, 120, $yhtiorow["www"], $page_zebra_tarra[$sivu], $iso);
      $posti_osoite = $yhtiorow["osoite"].", ".$yhtiorow["postino"]." ".$yhtiorow["postitp"];
      $pdf_zebra_tarra->draw_text(-40, 95, $posti_osoite, $page_zebra_tarra[$sivu], $iso);

      $pdf_zebra_tarra->draw_text(280, 205, "Tilausnro:", $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(400, 205, $laskurow["tunnus"], $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(280, 180, "Pvm:", $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(400, 180, date("d.m.Y"), $page_zebra_tarra[$sivu], $iso);

      $pdf_zebra_tarra->draw_text(200, 40, $tilausrivi["sarjanumero"], $page_zebra_tarra[$sivu], $keski);

      $pdf_zebra_tarra->draw_text(-40, 70, t("Veloitukseton palautus viimeist‰‰n:"), $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(360, 70, date("d.m.Y", strtotime("+6 months")), $page_zebra_tarra[$sivu], $iso);

      $fh = fopen($filename, "w");
      if (fwrite($fh, $pdf_zebra_tarra->generate()) === FALSE) die("PDF create error $filename");
      fclose($fh);

      $tarrat = $tarrat." ".$filename;
    }

    zebra_tarra_tulostus_ruudulle($tarrat);
  }
}

if (!function_exists('zebra_tarra_tulostus_ruudulle')) {
    function zebra_tarra_tulostus_ruudulle($tarrat) {
   
    // Keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Pullopanttitarra-".md5(uniqid(mt_rand(), true)).".pdf";

    // Kootaan tarrat yhteen pdf:‰‰n
    shell_exec("gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile={$pdffilenimi} {$tarrat}");

    echo file_get_contents($pdffilenimi);
  }
}
