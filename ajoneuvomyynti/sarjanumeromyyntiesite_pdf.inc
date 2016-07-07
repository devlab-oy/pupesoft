<?php

if ($kieli== '') {
  $querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
  $kielresult = mysql_query($querykiel) or pupe_error($querykiel);
  $kielnum = mysql_num_rows($kielresult);
  $kielrow = mysql_fetch_array($kielresult);
  $kieli = strtolower($kielrow['kieli']);
}

if ($kukarow["yhtio"] == "flmar") {
  $red  = 0.284;
  $green  = 0.257;
  $blue  = 0.182;

}
else {
  $red  = 0;
  $green  = 0.46;
  $blue  = 1;
}

$p["height"]     = 14;
$p["font"]      = "Times-Roman";
$p['fillcolor']['red']   = $red;
$p['fillcolor']['blue']  = $blue;
$p['fillcolor']['green']  = $green;

$pp["height"]    = 12;
$pp["font"]     = "Times-Bold";
$pp['fillcolor']['red']  = $red;
$pp['fillcolor']['blue'] = $blue;
$pp['fillcolor']['green']  = $green;

$pb["height"]    = 14;
$pb["font"]     = "Times-Bold";
$pb['fillcolor']['red']  = $red;
$pb['fillcolor']['blue'] = $blue;
$pb['fillcolor']['green']  = $green;

$pbi["height"]    = 18;
$pbi["font"]    = "Times-Bold";
$pbi['fillcolor']['red']  = $red;
$pbi['fillcolor']['blue'] = $blue;
$pbi['fillcolor']['green']  = $green;

$pi["height"]    = 24;
$pi["font"]     = "Times-Bold";
$pi['fillcolor']['red']  = $red;
$pi['fillcolor']['blue'] = $blue;
$pi['fillcolor']['green']  = $green;

$ph["height"]    = 96;
$ph["font"]     = "Times-Bold";
$ph['fillcolor']['red']  = $red;
$ph['fillcolor']['blue'] = $blue;
$ph['fillcolor']['green']  = $green;


$pm["height"]    = 72;
$pm["font"]     = "Times-Bold";
$pm['fillcolor']['red']  = $red;
$pm['fillcolor']['blue'] = $blue;
$pm['fillcolor']['green']  = $green;

$rectparam["width"] = 0.3;
$rectparam['strokecolor']['red']  = $red;
$rectparam['strokecolor']['blue'] = $blue;
$rectparam['strokecolor']['green']  = $green;

//muutamat funktiot...
if (!function_exists('alku')) {
  function alku($pdf, $hinnat) {
    global $kukarow, $yhtiorow, $rectparam, $p;

    $page = $pdf->new_page("a4");


    unset($data);

    if (file_exists("../pics/VA_Aaltoo.jpg")) {
      $filename = "../pics/VA_Aaltoo.jpg";

      $fh = fopen($filename, "r");
      $data = fread($fh, filesize($filename));
      fclose($fh);

      $isizelogo = getimagesize($filename);
    }

    if ($data) {
      $image = $pdf->jfif_embed($data);

      if (!$image) {
        echo t("Logokuvavirhe");
      }
      else {

        $logoparam = array();
        $logoparam['scale'] = 550 / $isizelogo[0];

        $placement = $pdf->image_place($image, 825-($logoparam['scale']*$isizelogo[1]), 20, $page, $logoparam);
      }
    }
    else {
      $pdf->draw_text(30, 815,  $yhtiorow["nimi"], $page);
    }

    return array($page, 770);
  }
}

if (!function_exists('rivi')) {
  function rivi($pdf, $page, $kalakorkeus, $sarjarow, $kulurow, $hinnat, $tarkkuus, $kommentit, $text_output, $kuvalisa_bin, $hankintahinta, $fnlina22) {
    global $kukarow, $yhtiorow, $rectparam, $p, $pb, $pp, $pi, $ph, $pm, $pbi, $red, $green, $blue;

    $pini["height"]    = 12;
    $pini["font"]    = "Times-Bold";

    if ($kukarow["yhtio"] == "flmar") {
      $pini['fillcolor']['red']  = $red;
      $pini['fillcolor']['blue'] = $blue;
      $pini['fillcolor']['green'] = $green;
    }
    else {
      $pini['fillcolor']['red']  = 1;
      $pini['fillcolor']['blue'] = 1;
      $pini['fillcolor']['green'] = 1;
    }

    while ($pdf->strlen($sarjarow["nimitys"], $pini) < 400) {
      $pini["height"]++;

      if ($kukarow["yhtio"] == "flmar" and $pini["height"] > 40) {
        break;
      }
      elseif ($pini["height"] > 64) {
        break;
      }
    }

    $pdf->draw_text(35, $kalakorkeus, $sarjarow["nimitys"], $page, $pini);

    if ($hinnat == "MEY") {
      $kalakorkeus = $kalakorkeus-150;

      $piso["height"]    = 72;
      $piso["font"]    = "Times-Bold";
      $piso['fillcolor']['red']  = $red;
      $piso['fillcolor']['blue'] = $blue;
      $piso['fillcolor']['green'] = $green;

      preg_match("/\nMerkki###(.*)/", $text_output, $string);

      $pdf->draw_text(35,  $kalakorkeus, "Merkki",      $page, $pbi);
      list($ff_string, $ff_font) = pdf_fontfit(trim($string[1]), 400, $pdf, $piso);
      $oikpos = $pdf->strlen($ff_string, $ff_font);
      $pdf->draw_text(530-$oikpos,  $kalakorkeus, $ff_string,     $page, $ff_font);

      $kalakorkeus = $kalakorkeus - 80;

      preg_match("/\nMalli###(.*)/", $text_output, $string);

      $pdf->draw_text(35,  $kalakorkeus, "Malli",      $page, $pbi);
      list($ff_string, $ff_font) = pdf_fontfit(trim($string[1]), 400, $pdf, $piso);
      $oikpos = $pdf->strlen($ff_string, $ff_font);
      $pdf->draw_text(530-$oikpos,  $kalakorkeus, $ff_string,     $page, $ff_font);

      $kalakorkeus = $kalakorkeus - 80;


      preg_match("/\nMoottorin Merkki###(.*)/", $text_output, $string1);
      preg_match("/\nMoottorin Malli###(.*)/", $text_output, $string2);
      preg_match("/\nMoottoreita###(.*)/", $text_output, $string3);

      if (trim($string1[1]) != "") {
        if (trim(str_replace("KPL", "x", $string3[1])) != "") $string1[1] = trim(str_replace("KPL", "x", $string3[1]))." ".$string1[1];

        $pdf->draw_text(35,  $kalakorkeus, "Mottori",          $page, $pbi);
        list($ff_string, $ff_font) = pdf_fontfit(trim($string1[1]." ".$string2[1]), 400, $pdf, $piso);
        $oikpos = $pdf->strlen($ff_string, $ff_font);
        $pdf->draw_text(530-$oikpos,  $kalakorkeus, $ff_string,       $page, $ff_font);

        $kalakorkeus = $kalakorkeus - 80;
      }

      preg_match("/\nVuosimalli###(.*)/", $text_output, $string);

      if (trim($string[1]) != "") {
        $pdf->draw_text(35,  $kalakorkeus, "Vuosimalli",      $page, $pbi);
        list($ff_string, $ff_font) = pdf_fontfit(trim($string[1]), 400, $pdf, $piso);
        $oikpos = $pdf->strlen($ff_string, $ff_font);
        $pdf->draw_text(530-$oikpos,  $kalakorkeus, $ff_string,     $page, $ff_font);

        $kalakorkeus = $kalakorkeus - 80;
      }

      if ($sarjarow["kaytetty"] == '') {
        if (function_exists("sarjanumeronlisatiedot_popup")) {
          list($null, $text_output_2, $null, $null, $null) = sarjanumeronlisatiedot_popup($sarjarow["tunnus"], '', '', 'MY', '', "");
        }

        preg_match("/Myyntihinta yht:### ### ###([0-9\.]*) /", $text_output_2, $string);

        if (trim($string[1]) != "") {
          $pdf->draw_text(35,  $kalakorkeus, "OVH varusteineen",  $page, $pbi);
          list($ff_string, $ff_font) = pdf_fontfit(trim((int) $string[1]), 400, $pdf, $piso);
          $oikpos = $pdf->strlen($ff_string, $ff_font);
          $pdf->draw_text(530-$oikpos,  $kalakorkeus, $ff_string,     $page, $ff_font);
          $pdf->draw_text(535,  $kalakorkeus, "EUR",         $page, $pbi);
          $kalakorkeus = $kalakorkeus - 80;
        }
      }

      preg_match("/\nToimituskulut###([0-9\.]*)/", $text_output, $string);

      if (trim($string[1]) != "") {
        $pdf->draw_text(35,  200, "Toimituskulut",       $page, $pbi);

        list($ff_string, $ff_font) = pdf_fontfit((int) trim($string[1]), 380, $pdf, $piso);
        $oikpos = $pdf->strlen($ff_string, $ff_font);
        $pdf->draw_text(530-$oikpos,  200, $ff_string,      $page, $ff_font);
        $pdf->draw_text(535,  200, "EUR",         $page, $pbi);
      }

      $piso["height"]    = 132;
      $piso["font"]    = "Times-Bold";
      $piso['fillcolor']['red']  = $red;
      $piso['fillcolor']['blue'] = $blue;
      $piso['fillcolor']['green'] = $green;

      preg_match("/\nHintapyyntö###([0-9\.]*)/", $text_output, $string);

      if (trim($string[1]) != "") {
        $pdf->draw_text(35,  80, "Hintapyyntö",       $page, $pbi);
        list($ff_string, $ff_font) = pdf_fontfit((int) trim($string[1]), 380, $pdf, $piso);
        $oikpos = $pdf->strlen($ff_string, $ff_font);
        $pdf->draw_text(530-$oikpos,  80, $ff_string,      $page, $ff_font);
        $pdf->draw_text(535,  80, "EUR",         $page, $pbi);
      }

      preg_match("/\nSijainti###(.*)/", $text_output, $string);
      $pdf->draw_text(35, 20,  $string[1], $page, $p);
    }
    else {
      if ($kukarow["yhtio"] == "flmar") {
        $kalakorkeus = $kalakorkeus-40;
      }
      else {
        $kalakorkeus = $kalakorkeus-120;
      }

      if (count($kuvalisa_bin) > 0) {

        $kuva = $kuvalisa_bin[0];

        // Pitää runkata jotta saadaan kuvat näkyviin!
        $nimi1 = "/tmp/".md5(uniqid(rand(), true)).".jpg";

        $fh = fopen($nimi1, "w");
        if (fwrite($fh, $kuva[0]) === FALSE) die("Kirjoitus epäonnistui $nimi1");
        fclose($fh);

        $nimi2 = "/tmp/".md5(uniqid(rand(), true)).".jpg";

        $isizelogo[0] = round($kuva[1]/2);
        $isizelogo[1] = round($kuva[2]/2);

        // Haetaan kuvan väriprofiili
        exec("nice -n 20 identify -format %[colorspace] \"$nimi1\"", $identify);

        $colorspace = "sRGB";
        if ($identify[0] != "") $colorspace = $identify[0];

        passthru("nice -n 20 convert -resize ".$isizelogo[0]."x".$isizelogo[1]." -quality 90 -colorspace $colorspace -strip \"$nimi1\" \"$nimi2\"", $palautus);

        if ($fh = @fopen($nimi2, "r")) {
          $data = fread($fh, filesize($nimi2));
          fclose($fh);
          $image = $pdf->jfif_embed($data);
        }

        //dellataan tmp filet kuleksimasta
        system("rm -f $nimi1 $nimi2");

        $logoparam = array();

        if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (mm_pt(140) / $isizelogo[0]) <= mm_pt(90)) {
          $logoparam['scale'] = mm_pt(140) / $isizelogo[0];
        }
        else {
          $logoparam['scale'] = mm_pt(90)  / $isizelogo[1];
        }

        $placement = $pdf->image_place($image, $kalakorkeus-($logoparam['scale']*$isizelogo[1]), 20, $page, $logoparam);

        for ($i=1; $i<=5; $i++) {
          $kuva = $kuvalisa_bin[$i];

          if ($kuva[0] != "") {
            // Pitää runkata jotta saadaan kuvat näkyviin!
            $nimi1 = "/tmp/".md5(uniqid(rand(), true)).".jpg";

            $fh = fopen($nimi1, "w");
            if (fwrite($fh, $kuva[0]) === FALSE) die("Kirjoitus epäonnistui $nimi1");
            fclose($fh);

            $nimi2 = "/tmp/".md5(uniqid(rand(), true)).".jpg";

            $isizelogo[0] = round($kuva[1]/3);
            $isizelogo[1] = round($kuva[2]/3);

            // Haetaan kuvan väriprofiili
            exec("nice -n 20 identify -format %[colorspace] \"$nimi1\"", $identify);

            $colorspace = "sRGB";
            if ($identify[0] != "") $colorspace = $identify[0];

            passthru("nice -n 20 convert -resize ".$isizelogo[0]."x".$isizelogo[1]." -quality 90 -colorspace $colorspace -strip \"$nimi1\" \"$nimi2\"", $palautus);

            if ($fh = @fopen($nimi2, "r")) {
              $data = fread($fh, filesize($nimi2));
              fclose($fh);
              $image = $pdf->jfif_embed($data);
            }

            //dellataan tmp filet kuleksimasta
            system("rm -f $nimi1 $nimi2");

            $logoparam = array();

            if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (mm_pt(65) / $isizelogo[0]) <= mm_pt(40)) {
              $logoparam['scale'] = mm_pt(65) / $isizelogo[0];
            }
            else {
              $logoparam['scale'] = mm_pt(40)  / $isizelogo[1];
            }

            $placement = $pdf->image_place($image, $kalakorkeus-($logoparam['scale']*$isizelogo[1]), mm_pt(135), $page, $logoparam);
            $kalakorkeus = $kalakorkeus - mm_pt(40);
          }
        }
      }

      $kalakorkeus = 340;

      $kommentit = explode("\n", trim($text_output));

      foreach ($kommentit as $kommentti) {

        $komm = explode("###", trim($kommentti));

        if (in_array($komm[0], array("Merkki", "Malli", "Vuosimalli", "Hintapyyntö", "Toimituskulut", "Väri", "Moottorin Merkki", "Moottorin Malli", "Moottorin Tyyppi", "Moottorin Teho", "Polttoaine", "Moottorin Vuosimalli", "Polttoainetankki"))) {
          $komm[1] = preg_replace("/\.00 /", " ", $komm[1]);

          if (trim($komm[1]) != "" and trim($komm[1]) != "0000") {
            $pdf->draw_text(35,  $kalakorkeus, trim($komm[0]),     $page, $p);
            list($ff_string, $ff_font) = pdf_fontfit(trim($komm[1]), 200,  $pdf, $pbi);
            $pdf->draw_text(175,  $kalakorkeus, $ff_string,     $page, $ff_font);

            $kalakorkeus = $kalakorkeus - 25;
          }
        }

        if ($komm[0] == "Sijainti") {
          $pdf->draw_text(35, 20,  $komm[1], $page, $p);
        }
      }

      list($page, $kalakorkeus) = alku($pdf, $hinnat);

      if ($kukarow["yhtio"] == "flmar") {
        $pdf->draw_text(35, 700, $sarjarow["nimitys"], $page, $pini);
      }
      else {
        $pdf->draw_text(35, 770, $sarjarow["nimitys"], $page, $pini);
      }

      $kalakorkeus = 650;

      $kommentit = explode("\n", trim($text_output));

      $lask  = 1;
      $lisaoik = 0;

      foreach ($kommentit as $kommentti) {

        if ($lask > 35) {
          $lisaoik   = 290;
          $kalakorkeus = 650;
          $lask   = 1;
        }

        $komm = explode("###", trim($kommentti));

        if (!in_array($komm[0], array("Merkki", "Malli", "Vuosimalli", "Hintapyyntö", "Toimituskulut", "Väri", "Moottorin Merkki", "Moottorin Malli", "Moottorin Tyyppi", "Moottorin Teho", "Polttoaine", "Moottorin Vuosimalli", "Polttoainetankki", "Sijainti", "Julkaisu", "Varastointiaika"))) {

          $komm[1] = preg_replace("/\.00 /", " ", $komm[1]);

          $pdf->draw_text(35+$lisaoik,  $kalakorkeus, trim($komm[0]),    $page, $pp);
          $pdf->draw_text(165+$lisaoik, $kalakorkeus, trim($komm[1]),    $page, $pp);
          $kalakorkeus = $kalakorkeus - 17;
          $lask++;
        }

        if ($komm[0] == "Sijainti") {
          $pdf->draw_text(35, 20,  $komm[1], $page, $p);
        }
      }

      // LISÄSIVU
      if ($hinnat == "MEL") {
        list($page, $kalakorkeus) = alku($pdf, $hinnat);
        $pdf->draw_text(35, $kalakorkeus, $sarjarow["nimitys"], $page, $pini);

        $kalakorkeus -= 200;

        foreach ($kommentit as $kommentti) {

          $komm = explode("###", trim($kommentti));

          if (in_array($komm[0], array("Merkki", "Vuosimalli"))) {
            $pdf->draw_text(40, $kalakorkeus, trim($komm[1]),    $page, $ph);
            $kalakorkeus -= 100;
          }
          if (in_array($komm[0], array("Malli"))) {

            $pala = $pdf->draw_paragraph($kalakorkeus+100, 40, 100, 580,  trim($komm[1]), $page, $ph); // top, left, bottom, right
            $kalakorkeus = $pala -= 100;
          }
          if (in_array($komm[0], array("Hintapyyntö", "Toimituskulut"))) {
            if ($komm[0] == "Toimituskulut") {
              $plisa = "+ ";
            }
            else {
              $plisa = "";
            }

            $oikpos = $pdf->strlen($plisa.sprintf('%.2f', (float) $komm[1])." ".strtoupper($yhtiorow["valkoodi"]), $pm);
            $pdf->draw_text(560-$oikpos, $kalakorkeus, $plisa.sprintf('%.2f', (float) $komm[1])." ".strtoupper($yhtiorow["valkoodi"]), $page, $pm);

            $kalakorkeus -= 100;
          }
        }
      }
    }

    return array($page, $kalakorkeus);
  }
}

if (!function_exists('print_pdf')) {
  function print_pdf($pdf, $kirjoitin, $luetkpl) {
    global $kukarow, $yhtiorow, $tee;

    $oslapp='';
    $returnvalue=0;

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Myyntiesite-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    $query = "SELECT *
              from kirjoittimet
              where yhtio='$kukarow[yhtio]'
              and tunnus='$kirjoitin'";
    $kirres= mysql_query($query) or pupe_error($query);
    $kirrow= mysql_fetch_array($kirres);
    $komento=$kirrow['komento'];


    // itse print komento...
    if ($komento == 'email') {
      $liite = $pdffilenimi;
      $kutsu = "Luettelo";

      echo t("Luettelo tulostuu")."...<br>";

      if ($kukarow["extranet"] == '') {
        require "../inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    elseif ($tee == 'NAYTATILAUS') {
      //Työnnetään tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    elseif ($komento != '' and $komento != 'edi') {
      echo t("Luettelo tulostuu")."...<br>";
      $line = exec("$komento $pdffilenimi", $output, $returnvalue);
    }

    //poistetaan tmp file samantien kuleksimasta...
    system("rm -f $pdffilenimi");
  }
}
