<?php

$koontirahtikirja = in_array($toitarow['tulostustapa'], array('K', 'L'));

//  Jos tämä on osa "kokonaistoimitusta", merkataan alkuperäinen tilausnumero alkuun
if ($rakir_row["tunnusnippu"]>0) {
  foreach ($lotsikot as &$o) {
    if ($o <> $rakir_row["tunnusnippu"]) {
      $o = "$rakir_row[tunnusnippu]/$o";
    }
  }
}

// onko tämä tyhjä rahtikirja (rahtikirja_custom.php), jos EI niin haetaan infot tilaukselta
if (!isset($GLOBALS['tyhja'])) {
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  in ($otunnukset)
            ORDER BY tunnus
            LIMIT 1";
  $tempr = pupe_query($query);
  $postirow = mysql_fetch_array($tempr);

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
    $postirow['yhtio_nimitark'] = $postirow_varasto["nimitark"];
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

  // lähettäjän viite otetaan laskulta
  $viitelah = $lotsikot[0];
}
elseif ($osoitelappurow["toim_nimi"] != '') {
  // tänne tullaan, jos ollaan oltu rahtikirja_custom.phpssä
  $postirow = $osoitelappurow;
  $rakir_row = $osoitelappurow;

  $viitelah = $osoitelappurow['viitelah'];
  $viitevas = $osoitelappurow['viitevas'];
}
else {
  $query = "SELECT asiakas.*, rahtikirjat.merahti, rahtikirjat.rahtisopimus
            FROM asiakas
            JOIN rahtikirjat on rahtikirjat.yhtio = asiakas.yhtio and rahtikirjat.otsikkonro = '".($otsikkonro * -1)."'
            WHERE asiakas.yhtio = '$kukarow[yhtio]'
            and asiakas.tunnus  = '$GLOBALS[asiakasid]'
            LIMIT 1";
  $tempr = pupe_query($query);
  $rakir_row = mysql_fetch_array($tempr);

  if (empty($rakir_row['toim_postitp'])) {
    $rakir_row['toim_postitp'] = $rakir_row['postitp'];
    $rakir_row['toim_postino'] = $rakir_row['postino'];
    $rakir_row['toim_osoite']  = $rakir_row['osoite'];
    $rakir_row['toim_nimi']    = $rakir_row['nimi'];
  }
}

if ($toitarow['logy_rahtikirjanumerot'] != "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

  $logyq = "SELECT min(rahtikirjanro) rahtikirjanro
            FROM rahtikirjanumero
            WHERE yhtio      = '$kukarow[yhtio]'
            and kaytettyaika = '0000-00-00 00:00:00'";
  $logyres = pupe_query($logyq);
  $logyrow = mysql_fetch_assoc($logyres);

  $rahtikirjanro = $logyrow["rahtikirjanro"];

  $logyq = "UPDATE rahtikirjanumero
            SET kayttaja = '$kukarow[kuka]',
            kaytettyaika      = now()
            WHERE yhtio       = '$kukarow[yhtio]'
            and rahtikirjanro = '$logyrow[rahtikirjanro]'";
  $logyres = pupe_query($logyq);
}
elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
  $rahtikirjanro = $lotsikot[0];
}
else {
  $rahtikirjanro = $rivi["rahtikirjanro"];
}

//tsekataan kuka maksaa rahdin ja laitetaan oikeat tiedot rahdinmaksajan ruutuun
if ($rakir_row['muumaksaja'] != '') {
  $rahdinmaksaja = $rakir_row['muumaksaja'];
}

if ($rahdinmaksaja != 'Lähettäjä') {
  $rahdinmaksajan_nr   = $rakir_row['rahtisopimus'];
}
else {
  $rahdinmaksajan_nr   = $toitarow['sopimusnro'];
}

// postiennakko
if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
  $postiennakkomaara  = "$yhteensa $postirow[valkoodi]";
  if ($yhtiorow["pankkiiban1"] != '') {
    $postiennakkotilino = "$yhtiorow[pankkiiban1]";
  }
  else {
    $postiennakkotilino = "$yhtiorow[pankkitili1]";
  }
  $postiennakkobic  = "$yhtiorow[pankkiswift1]";
  $postiennakkoviite  = "$viite";
}
else {
  $postiennakkomaara  = "";
  $postiennakkotilino = "$yhtiorow[pankkitili1]";
  $postiennakkoviite  = "";
}

if (isset($kirjoitin) and $kirjoitin == "-88" and $toitarow['rahtikirjakopio_email'] == '') {
  js_openFormInNewWindow();

  echo "<br><form id='tulostakopioform_rahtikirja_{$postirow['tunnus']}' name='tulostakopioform_rahtikirja_{$postirow['tunnus']}' method='post' action='{$palvelin2}rahtikirja-kopio.php' autocomplete='off'>
        <input type='hidden' name='rtunnukset[]' value='{$rahtikirjanro}'>
        <input type='hidden' name='tee' value='tulosta'>
        <input type='hidden' name='real_submit' value='joo'>
        <input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
        <input type='hidden' name='yksittainen' value='ON'>
        <input type='hidden' name='komento' value='PDF_RUUDULLE'>
        <input type='submit' value='".t("Rahtikirja").": {$postirow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_rahtikirja_{$postirow['tunnus']}', ''); return false;\"></form><br>";
}
else {

  // PDF:n luonti ja defaultit
  define(__TRACE_ENABLED__, false);
  define(__DEBUG_ENABLED__, false);

  if (in_array("gd", get_loaded_extensions())) {
    require_once "barcode/barcode.php";
    require_once "barcode/debug.php";
    require_once "barcode/c39object.php";
  }
  require_once "pdflib/phppdflib.class.php";

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);
  $rectparam["width"] = 0.3;

  //fontit
  $norm["height"] = 6;
  $norm["font"] = "Times-Roman";

  $kirj["height"] = 8;
  $kirj["font"] = "Times-Bold";

  $iso["height"] = 12;
  $iso["font"] = "Helvetica-Bold";

  $huge["height"] = 22;
  $huge["font"] = "Helvetica-Bold";

  // Tulostetaan oikea sivumäärä
  if ($yhtiorow["rahtikirjan_kopiomaara"] == 4) {
    $tulostakolli = 4;
  }
  else {
    $tulostakolli = 3;
  }

  for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

    // tehdään pdf:n uusi sivu
    $firstpage = $pdf->new_page("a4");

    if ($toitarow['sopimusnro'] != '') {
      $toitarow['sopimusnro'] = sprintf("%06s", $toitarow['sopimusnro']);  // sopimunumeron tulee olla kuus pitkä
    }

    //tehdään viivakoodi joka 9 merkkiä pitkä.
    $viiva   = $rahtikirjanro;

    //Duusataan viivakoodia
    $output   = "png";
    $width    = "180";
    $height   = "35";
    $xres     = "1";
    $font     = "2";
    $drawtext = "off";
    $border   = "on";
    $stretchtext = "off";
    $style    = BCS_ALIGN_CENTER;
    $style   |= ($output     == "png" ) ? BCS_IMAGE_PNG      : 0;
    $style   |= ($output     == "jpeg") ? BCS_IMAGE_JPEG     : 0;
    $style   |= ($border     == "on"  ) ? BCS_BORDER         : 0;
    $style   |= ($drawtext   == "on"  ) ? BCS_DRAW_TEXT      : 0;
    $style   |= ($stretchtext== "on"  ) ? BCS_STRETCH_TEXT   : 0;
    $style   |= ($negative   == "on"  ) ? BCS_REVERSE_COLOR  : 0;

    if (class_exists("c39object")) {
      //luodaan viivakoodiolio
      $obj = "";
      $obj = new c39object($width, $height, $style, $viiva);

      if ($obj) {
        $obj->SetFont($font);
        $obj->DrawObject($xres);

        //flushataan barcode ja saadaam filenimi johon se tallennettiin
        $nimi1 = $obj->FlushObject();

        //keksitään uudelle failille joku varmasti uniikki nimi:
        $nimi2 = "/tmp/".md5(uniqid(rand(), true)).".png";

        passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);

        if ($fh = @fopen($nimi2, "r")) {
          $data = fread($fh, filesize($nimi2));
          fclose($fh);
          $image = $pdf->png_embed($data);

          if ($image) {
            // piirretään viivakoodi paprulle
            $pdf->image_place($image, 631, 300, $firstpage); // Y, X
          }
        }

        $pdf->draw_text(390, 625,  $viiva, $firstpage, $norm);

        unset($obj);

        //dellataan tmp filet kuleksimasta
        system("rm -f $nimi1 $nimi2");
      }
    }

    // sitten aletaan piirtämään itse PDF sisältöä
    $pdf->draw_text(302,  812, t("RAHTIKIRJA FRAKTSEDEL"), $firstpage, $iso);

    $apu_yhtiorow = array();

    // varmistetaan, että kopiossakin tulee oikean toimipaikan logo
    if ((int) $postirow["yhtio_toimipaikka"] != 0) {
      $query = "SELECT *
                FROM yhtion_toimipaikat
                WHERE yhtio     = '$kukarow[yhtio]'
                and tunnus      = '$postirow[yhtio_toimipaikka]'
                and lasku_logo != ''";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $yhtion_toimipaikkarow = mysql_fetch_array($result);
        $apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
      }
    }
    else {
      $query = "SELECT *
                FROM yhtion_parametrit
                WHERE yhtio = '$postirow[yhtio]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $yhtion_parametritrow = mysql_fetch_array($result);
        $apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
      }
    }

    unset($data);

    if ((int) $apu_yhtiorow["lasku_logo"] > 0) {
      $liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
      $data = $liite["data"];
      $isizelogo[0] = $liite["image_width"];
      $isizelogo[1] = $liite["image_height"];
      unset($liite);
    }
    elseif (file_exists($apu_yhtiorow["lasku_logo"])) {
      $filename = $apu_yhtiorow["lasku_logo"];

      $fh = fopen($filename, "r");
      $data = fread($fh, filesize($filename));
      fclose($fh);

      $isizelogo = getimagesize($apu_yhtiorow["lasku_logo"]);
    }

    if (isset($data) !== FALSE and $yhtiorow["oslapp_rakir_logo"] == '') {
      $image = $pdf->jfif_embed($data);

      if ($image) {
        $logoparam = array();

        if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
          $logoparam['scale'] = 240 / $isizelogo[0];
        }
        else {
          $logoparam['scale'] = 50  / $isizelogo[1];
        }

        $placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $firstpage, $logoparam);
      }

      //osoitetiedot yms
      //toimittaja
      $pdf->draw_rectangle(769, 60, 752, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(62,  763,  t("Lähettäjä Avsändare"),              $firstpage, $norm);
      $pdf->draw_text(240, 763,  t("Asiakasnro Kundnr"),                $firstpage, $norm);
      $pdf->draw_text(65,  755,  $postirow["yhtio_nimi"],                $firstpage, $kirj);
      $pdf->draw_text(240, 755,  $toitarow['sopimusnro'],                $firstpage, $kirj);
      $pdf->draw_rectangle(752, 60, 740, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65,  743,  $postirow["yhtio_osoite"],              $firstpage, $kirj);
      $pdf->draw_rectangle(740, 60, 728, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65,  731,  $postirow["yhtio_postino"]."  ".$postirow["yhtio_postitp"]."  ".$postirow["yhtio_maa"]."    ".$yhtiorow["puhelin"],    $firstpage, $kirj);

      //vastaanottaja

      $vastaanottaja = array(
        'nimi'    => $koontirahtikirja ? $rakir_row['toim_nimi']    : $rakir_row['nimi'],
        'osoite'  => $koontirahtikirja ? $rakir_row['toim_osoite']  : $rakir_row['osoite'],
        'postino' => $koontirahtikirja ? $rakir_row['toim_postino'] : $rakir_row['postino'],
        'postitp' => $koontirahtikirja ? $rakir_row['toim_postitp'] : $rakir_row['postitp'],
        'maa'     => $koontirahtikirja ? $rakir_row['toim_maa']     : $rakir_row['maa'],
      );

      $pdf->draw_rectangle(713, 60, 696, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(62,  707,  t("Vastaanottaja Mottagare"),            $firstpage, $norm);
      $pdf->draw_text(240, 707,  t("Asiakasnro Kundnr"),                $firstpage, $norm);
      $pdf->draw_text(65, 699, $vastaanottaja["nimi"], $firstpage, $kirj);
      $pdf->draw_text(240, 699,  $rakir_row['rahtisopimus'],              $firstpage, $kirj);
      $pdf->draw_rectangle(696, 60, 684, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65, 687, $vastaanottaja["osoite"], $firstpage, $kirj);
      $pdf->draw_rectangle(684, 60, 672, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65, 675, $vastaanottaja["postino"] . "  " . $vastaanottaja["postitp"] . "  " . $vastaanottaja["maa"] . "    " . $rakir_row["puhelin"],  $firstpage, $kirj);

      //toimitusosoite
      $pdf->draw_rectangle(657, 60, 640, 300,                        $firstpage, $rectparam);
      $pdf->draw_rectangle(640, 60, 628, 300,                        $firstpage, $rectparam);

      $pdf->draw_text(62,  651, t("Tavaran toimitusosoite").":",              $firstpage, $norm);
      $pdf->draw_text(65,  643,  $rakir_row['toim_nimi']." ".$rakir_row['toim_nimitark'],  $firstpage, $kirj);
      $pdf->draw_text(65,  631,  $rakir_row['toim_osoite'],                $firstpage, $kirj);

    }
    else {
      //osoitetiedot yms
      //toimittaja
      $pdf->draw_rectangle(810, 60, 777, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(62,  803,  t("Lähettäjä Avsändare"),              $firstpage, $norm);
      $pdf->draw_text(240, 803,  t("Asiakasnro Kundnr"),                $firstpage, $norm);
      $pdf->draw_text(65,  782,  $postirow["yhtio_nimi"],                $firstpage, $kirj);
      $pdf->draw_text(240, 782,  $toitarow['sopimusnro'],                $firstpage, $kirj);
      $pdf->draw_rectangle(777, 60, 755, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65,  758,  $postirow["yhtio_osoite"],              $firstpage, $kirj);
      $pdf->draw_rectangle(755, 60, 733, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65,  736,  $postirow["yhtio_postino"]."  ".$postirow["yhtio_postitp"]."  ".$postirow["yhtio_maa"]."    ".$yhtiorow["puhelin"],    $firstpage, $kirj);

      //vastaanottaja
      $pdf->draw_rectangle(733, 60, 711, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(62,  726,  t("Vastaanottaja Mottagare"),            $firstpage, $norm);
      $pdf->draw_text(240, 726,  t("Asiakasnro Kundnr"),                $firstpage, $norm);
      $pdf->draw_text(65,  714,  $rakir_row["nimi"],                  $firstpage, $kirj);
      $pdf->draw_text(240, 714,  $rakir_row['rahtisopimus'],              $firstpage, $kirj);
      $pdf->draw_rectangle(711, 60, 689, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65,  692,  $rakir_row["osoite"],                $firstpage, $kirj);
      $pdf->draw_rectangle(689, 60, 667, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(65,  670,  $rakir_row["postino"]."  ".$rakir_row["postitp"]."  ".$rakir_row["maa"]."    ".$rakir_row["toim_puh"],  $firstpage, $kirj);

      //toimitusosoite
      $pdf->draw_rectangle(667, 60, 645, 300,                      $firstpage, $rectparam);
      $pdf->draw_rectangle(645, 60, 623, 300,                      $firstpage, $rectparam);
      $pdf->draw_text(62,  661, t("Tavaran toimitusosoite"),              $firstpage, $norm);
      $pdf->draw_text(65,  648,  $rakir_row['toim_nimi']." ".$rakir_row['toim_nimitark'],  $firstpage, $kirj);
      $pdf->draw_text(65,  626,  $rakir_row['toim_osoite'],              $firstpage, $kirj);
    }

    //oikeeta
    $pdf->draw_text(302,  803,  t("Lähetyspäivämäärä Avsändningsdatum"),      $firstpage, $norm);
    $pdf->draw_text(302,  793,  date("d.m.Y"),                    $firstpage, $kirj);
    $pdf->draw_text(302,  782,  t("Lähettäjän viite Avsändarens referens"),      $firstpage, $norm);
    $pdf->draw_text(302,  772,  $viitelah,                      $firstpage, $kirj);
    $pdf->draw_text(302,  761,  t("Vastaanottajan viite Mottagarens referens"),    $firstpage, $norm);

    if (!isset($GLOBALS['tyhja'])) {
      $query = "SELECT group_concat(trim(viesti) SEPARATOR ', ') viesti
                from lasku
                where yhtio = '$kukarow[yhtio]'
                and tunnus  in ($otunnukset)
                order by tunnus ";
      $viestires = pupe_query($query);
      $viestirow = mysql_fetch_array($viestires);
    }
    else {
      $viestirow = array(
        'viesti' => $viitevas,
      );
    }

    // Jos rivikommentti on pitkä niin rivitämme sen
    if ($pdf->strlen($viestirow["viesti"], $kirj) > 240) {
      $vie1 = pdf_substr($viestirow["viesti"], 240, $pdf, $kirj);
      $vie2 = pdf_substr(substr($viestirow["viesti"], strlen($vie1)), 240, $pdf, $kirj);

      $pdf->draw_text(302,  751,  $vie1, $firstpage, $kirj);
      $pdf->draw_text(302,  741,  $vie2, $firstpage, $kirj);
    }
    else {
      $pdf->draw_text(302,  751,  $viestirow["viesti"], $firstpage, $kirj);
    }

    //oikee
    if ($toitarow['rahdinkuljettaja'] == '') {
      $toitarow['rahdinkuljettaja'] = $toitarow['selite'];
    }

    $pdf->draw_rectangle(733, 300, 667, 545,                        $firstpage, $rectparam);
    $pdf->draw_text(302,  726, t("Rahdinkuljettaja ja / tai huolitsijaTransportföretag och / eller Speditör"), $firstpage, $norm);
    $pdf->draw_text(302, 670,  $toitarow['rahdinkuljettaja'],              $firstpage, $kirj);

    //oikee
    //tähän viivakoodia
    $pdf->draw_rectangle(667, 300, 623, 545,                      $firstpage, $rectparam);

    //Lastauspaikka
    $pdf->draw_rectangle(623, 60, 535, 300,                      $firstpage, $rectparam);
    $pdf->draw_text(62,  617, t("Lähtö- ja lastauspaikka"),              $firstpage, $norm);
    $pdf->draw_text(65,  593,  $postirow["yhtio_postino"]."   ".strtoupper($postirow["yhtio_postitp"])."   ".strtoupper($postirow["yhtio_maa"]),    $firstpage, $kirj);
    $pdf->draw_text(62,  573, t("Määräpaikan postinumero ja -toimipaikka"),      $firstpage, $norm);
    $pdf->draw_text(65,  550,  $rakir_row["toim_postino"]." ".strtoupper($rakir_row["toim_postitp"])."   ".strtoupper($rakir_row["toim_maa"]), $firstpage, $iso);

    //oikeeta laitaa
    $pdf->draw_text(302,  617, t("Toimituslauseke Leveransklausul"),        $firstpage, $norm);
    $pdf->draw_text(302,  582, $postirow["toimitusehto"],              $firstpage, $kirj);
    $pdf->draw_rectangle(579, 300, 579, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(302,  573, t("Rahdinmaksaja Fraktbetalare"),          $firstpage, $norm);
    $pdf->draw_text(302,  559, $rahdinmaksaja,                    $firstpage, $kirj);
    $pdf->draw_text(450,  573, t("Asiakasnro Kundnr"),                $firstpage, $norm);
    $pdf->draw_text(450,  559, $rahdinmaksajan_nr,                  $firstpage, $kirj);

    $pdf->draw_rectangle(557, 300, 557, 545,                      $firstpage, $rectparam);
    $pdf->draw_rectangle(557, 300, 535, 420,                      $firstpage, $rectparam);
    $pdf->draw_rectangle(535, 300, 535, 545,                      $firstpage, $rectparam);

    //kollitietot
    //ylärivi
    $pdf->draw_text(62,  529, t("Merkki / nro"),                  $firstpage, $norm);
    $pdf->draw_text(62,  523, t("Märke / nr"),                    $firstpage, $norm);
    $pdf->draw_rectangle(535, 141, 520, 141,                      $firstpage, $rectparam);
    $pdf->draw_text(143,  529, t("Kolliluku ja -laji"),                $firstpage, $norm);
    $pdf->draw_text(143,  523, t("Kolliantal och -slag"),              $firstpage, $norm);
    $pdf->draw_rectangle(535, 202, 520, 202,                      $firstpage, $rectparam);
    $pdf->draw_text(206,  529, t("Sisältö, ulkomitat ja VAK-merkinnät"),      $firstpage, $norm);
    $pdf->draw_text(206,  523, t("Innehåll, yttermått och ADR-anmärkningar"),    $firstpage, $norm);
    $pdf->draw_rectangle(535, 420, 520, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(422,  529, t("Brutto kg"),                    $firstpage, $norm);
    $pdf->draw_rectangle(535, 484, 520, 484,                      $firstpage, $rectparam);
    $pdf->draw_text(486,  529, t("Tilavuus, m3"),                  $firstpage, $norm);
    $pdf->draw_text(486,  523, t("Tilavuus, m3"),                  $firstpage, $norm);

    foreach ($kilot as &$arvoround) {
      $arvoround = round($arvoround, 2);
    }

    foreach ($kollit as &$arvoround) {
      $arvoround = round($arvoround);
    }

    foreach ($kuutiot as &$arvoround) {
      $arvoround = round($arvoround, 4);
    }

    $rakir_i_kala = 510;

    //kollit
    for ($rakir_i = 0; $rakir_i < 100; $rakir_i++) {

      $pdf->draw_text(62, $rakir_i_kala, $lotsikot[$rakir_i], $firstpage, $kirj);

      $pdf->draw_text(143, $rakir_i_kala, $kollit[$rakir_i]."  ". $pakkaus[$rakir_i], $firstpage, $kirj);
      $pdf->draw_text(206, $rakir_i_kala, trim($pakkauskuvaus[$rakir_i]." ".$pakkauskuvaustark[$rakir_i]), $firstpage, $kirj);

      $pdf->draw_text(422, $rakir_i_kala, $kilot[$rakir_i], $firstpage, $kirj);
      $pdf->draw_text(486, $rakir_i_kala, $kuutiot[$rakir_i], $firstpage, $kirj);

      if ($astilnrot[$rakir_i] != '') {
        $rakir_i_kala-= 10;
        $pdf->draw_text(62, $rakir_i_kala, t("Astilnro").": ".$astilnrot[$rakir_i], $firstpage, $kirj);
      }

      $alempiteksti = trim($vakit[$rakir_i]." ".$lisatiedot[$rakir_i]);

      if ($alempiteksti != "") {

        if ($astilnrot[$rakir_i] == '') {
          $rakir_i_kala-= 10;
        }

        list($ff_string, $ff_font) = pdf_fontfit($alempiteksti, 360, $pdf, $kirj);
        $pdf->draw_text(206, $rakir_i_kala, $ff_string, $firstpage, $ff_font);
      }

      $rakir_i_kala-= 10;

      if ($rakir_i_kala < 410) {
        break;
      }
    }

    //alarivi
    $pdf->draw_text(183, 404 , t("Kollit yht. Kolliantal tot."),          $firstpage, $norm);
    $pdf->draw_text(183, 390 , $kollityht,                      $firstpage, $kirj);
    $pdf->draw_rectangle(410, 181, 388, 242,                      $firstpage, $rectparam);
    $pdf->draw_text(365, 404 , t("Lavametrit Flakmeter"),              $firstpage, $norm);
    $pdf->draw_text(365, 390 , $lavametri[0],                    $firstpage, $norm);
    $pdf->draw_rectangle(410, 363, 388, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 404 , t("Brutto yht. total., kg Rahditysp.  Fraktvikt"),  $firstpage, $norm);
    $pdf->draw_text(422, 390 , $kilotyht,                      $firstpage, $kirj);
    //vähä vedetää viivaa
    $pdf->draw_rectangle(410, 60, 410, 545,                      $firstpage, $rectparam);
    $pdf->draw_rectangle(388, 60, 388, 545,                      $firstpage, $rectparam);

    //kuljetusohjeita
    $pdf->draw_text(62, 382 , t("Kuljetusohjeet Transportinstruktioner"),      $firstpage, $norm);

    if ($postirow['tilausyhteyshenkilo'] != "") {
      $pdf->draw_text(62, 372 , t("Tilausyhteyshenkilö").":",            $firstpage, $kirj);
      $pdf->draw_text(135, 372 , $postirow['tilausyhteyshenkilo'],        $firstpage, $kirj);
    }

    $pdf->draw_paragraph(368, 62, 325, 410, $kuljetusohjeet,            $firstpage, $kirj);

    $pdf->draw_text(62, 311 , t("Muut tiedot Tillägsuppgifter"),          $firstpage, $norm);

    if (is_resource($lisatiedres)) {
      $ahven = 311;

      while ($lisatiedot = mysql_fetch_array($lisatiedres)) {
        if ($lisatiedot["nimi"] != "") {
          $ahven -= 10;
          $pdf->draw_text(62, $ahven , trim($lisatiedot["nimi"]." ".$lisatiedot["puh"]),                    $firstpage, $norm);
        }
      }
    }

    //laatikot
    $pdf->draw_text(305, 325 , t("Lisät eriteltynä").":",              $firstpage, $norm);
    $pdf->draw_rectangle(278, 303, 256, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(305, 272 , t("Muu palvelu"),                  $firstpage, $norm);
    $pdf->draw_rectangle(300, 303, 278, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(305, 294 , t("Jakelu"),                      $firstpage, $norm);
    $pdf->draw_rectangle(322, 303, 300, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(305, 316 , t("Nouto"),                      $firstpage, $norm);

    //oikee eli jäkivaatimus
    $pdf->draw_rectangle(388, 420, 322, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 382 , t("Jälkivaatimus, maksuviite Bet.ref. För efterkrav"), $firstpage, $norm);
    $pdf->draw_text(422, 369 , $postiennakkoviite,                  $firstpage, $kirj);
    $pdf->draw_rectangle(366, 420, 366, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 360 , t("Jälkivaatimus, tilinro Kontonr för efterkrav"),   $firstpage, $norm);
    $pdf->draw_text(422, 347 , $postiennakkotilino,                  $firstpage, $kirj);
    $pdf->draw_rectangle(344, 420, 344, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 338 , t("Jälkivaat. Efterkrav"),                          $firstpage, $norm);
    $pdf->draw_text(422, 325 , $postiennakkomaara,                  $firstpage, $kirj);
    $pdf->draw_rectangle(322, 420, 322, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 316 , t("Rahti Frakt"),                         $firstpage, $norm);
    $pdf->draw_rectangle(300, 420, 300, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 294 , t("Lisät yht. Extra avgift tot."),               $firstpage, $norm);
    $pdf->draw_rectangle(278, 420, 278, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 272 , "+ ".t("Alv. Moms"),                  $firstpage, $norm);
    //poikkiviiva
    $pdf->draw_rectangle(256, 60, 256, 545,                      $firstpage, $rectparam);
    //SFS
    $pdf->draw_text(25, 272 , t("SFS"),                        $firstpage, $kirj);
    $pdf->draw_text(25, 262 , t("5865"),                      $firstpage, $kirj);


    //varaumat
    $pdf->draw_text(62, 250 , t("Varaumat Förbehåll"),                $firstpage, $norm);

    //alarivi
    $pdf->draw_rectangle(179, 60, 179, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(62, 173 , t("12 Nouto"),                    $firstpage, $norm);
    $pdf->draw_rectangle(179, 112, 146, 164,                      $firstpage, $rectparam);
    $pdf->draw_text(114, 173 , t("14 Jakelu"),                    $firstpage, $norm);
    $pdf->draw_text(166, 173 , t("27 LJP"),                      $firstpage, $norm);
    $pdf->draw_rectangle(179, 216, 146, 268,                      $firstpage, $rectparam);
    $pdf->draw_text(218, 173 , t("26 VAK"),                      $firstpage, $norm);
    $pdf->draw_text(270, 173 , t("23 Yks"),                      $firstpage, $norm);
    $pdf->draw_rectangle(179, 320, 146, 372,                      $firstpage, $rectparam);
    $pdf->draw_text(322, 173 , t("40 Yks lkm"),                    $firstpage, $norm);
    $pdf->draw_text(374, 173 , t("69 Lisämaksu"),                  $firstpage, $norm);

    //oikee laita
    $pdf->draw_text(422, 250 , t("Käteinen yht. Kontant tot."),            $firstpage, $norm);
    $pdf->draw_rectangle(234, 420, 234, 545,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 228 , t("Käteismaksu, pvm,"),                $firstpage, $norm);
    $pdf->draw_text(422, 222 , t("paikka ja kuittaus"),                $firstpage, $norm);
    $pdf->draw_text(422, 173 , t("Krnro, kotip. HRnr, hemort/LY-tunnus AS-signum"),  $firstpage, $norm);


    //poikki ja pystyviivaa
    $pdf->draw_rectangle(146, 60, 146, 545,                      $firstpage, $rectparam);
    $pdf->draw_rectangle(256, 420, 146, 420,                       $firstpage, $rectparam);


    //allekirjoitukset
    $pdf->draw_text(62, 140 , t("Kuljettajan luovutuskuittaus ja pvm. Chaufförens underskrift och dat"), $firstpage, $norm);
    $pdf->draw_text(62, 134 , t("Vastaanottajan allekirjoitus. Mottagarens underskift."), $firstpage, $norm);
    $pdf->draw_rectangle(146, 242, 55, 242,                      $firstpage, $rectparam);
    $pdf->draw_text(244, 140 , t("Otettu kuljetettavaksi pvm ja kuljettajan vastaanottokuittaus"),  $firstpage, $norm);
    $pdf->draw_text(244, 134 , t("Chaufför, datum och underskrift"),        $firstpage, $norm);
    $pdf->draw_rectangle(146, 420, 55, 420,                      $firstpage, $rectparam);
    $pdf->draw_text(422, 140 , t("Lähettäjä, pvm ja allekirjoitus"),        $firstpage, $norm);
    $pdf->draw_text(422, 134 , t("Avsändare, datum och underskrift"),        $firstpage, $norm);

    $pdf->draw_text(244, 42 , t("Nimenselvennykset Namnförtydliganden"),      $firstpage, $kirj);

    //noita ulkokehän viivoja ja vähä muuta
    $pdf->draw_rectangle(535, 60, 55, 60,                        $firstpage, $rectparam);
    $pdf->draw_text(25, 70 , $tulostuskpl,                      $firstpage, $huge);
    if  ($tulostuskpl == 1) $pdf->draw_text(25, 30 , t("Kuljetusliike"),      $firstpage, $kirj);
    if  ($tulostuskpl == 2) $pdf->draw_text(25, 30 , t("Kuljetusliike"),      $firstpage, $kirj);
    if  ($tulostuskpl == 3) $pdf->draw_text(25, 30 , t("Vastaanottaja"),      $firstpage, $kirj);
    if  ($tulostuskpl == 4) $pdf->draw_text(25, 30 , t("Lähettäjä"),        $firstpage, $kirj);

  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(), true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die(t("PDF kirjoitus epäonnistui %s", "", $pdffilenimi));
  fclose($fh);

  if ($kirjoitin == "email") {
    $liite = $pdffilenimi;
    $kutsu = "PDF-rahtikirja";
    require "inc/sahkoposti.inc";

  }
  elseif ($kirjoitin == 'PDF_RUUDULLE') {
    //Työnnetään tuo pdf vaan putkeen!
    echo file_get_contents($pdffilenimi);
  }
  elseif (isset($kirjoitin) and $kirjoitin == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_rahtikirja_{$postirow['tunnus']}' name='tulostakopioform_rahtikirja_{$postirow['tunnus']}' method='post' action='{$palvelin2}rahtikirja-kopio.php' autocomplete='off'>
          <input type='hidden' name='rtunnukset[]' value='{$rahtikirjanro}'>
          <input type='hidden' name='tee' value='tulosta'>
          <input type='hidden' name='real_submit' value='joo'>
          <input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
          <input type='hidden' name='yksittainen' value='ON'>
          <input type='hidden' name='komento' value='PDF_RUUDULLE'>
          <input type='submit' value='".t("Rahtikirja").": {$postirow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_rahtikirja_{$postirow['tunnus']}', ''); return false;\"></form><br>";
  }
  else {
    //itse print komento...
    $line = exec("$kirjoitin $pdffilenimi");
  }

  if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {

    // Lähetetään rahtikirja myös kuljetusfirman sähköpostiin jos se löytyy
    if ($toitarow['rahtikirjakopio_email'] != '') {

      $polkuinffo = pathinfo($pdffilenimi);
      echo t("Lähetetään rahtikirjakopio").": {$toitarow['rahtikirjakopio_email']}<br>";
      $params = array(
        "to" => $toitarow['rahtikirjakopio_email'],
        "subject" => $toitarow['selite']." ".t("Rahtikirjakopio"),
        "ctype" => "text",
        "attachements"  => array(0   => array(
            "filename"    => $pdffilenimi,
            "newfilename"  => "",
            "ctype"      => $polkuinffo['extension'],
          )
        )
      );

      pupesoft_sahkoposti($params);
    }
  }
}
