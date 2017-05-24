<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada lähettäjän tiedot

if ($toim == 'EXTRANET_REKLAMAATIO' and $tunnus == '') {
  $tunnus = $kukarow['kesken'];
}

if ($phpnimi != "rahtikirja_custom.php") {
  //Tulostetaan standardi kolliosoitelappu
  $query = "SELECT lasku.*, if(yhteyshenkilo.nimi != '', concat_ws(' / ', yhteyshenkilo.nimi, if(yhteyshenkilo.gsm != '', yhteyshenkilo.gsm, if(yhteyshenkilo.puh != '', yhteyshenkilo.puh, NULL))), lasku.tilausyhteyshenkilo) yhteyshenkilo
            FROM lasku
            LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
            LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tunnus  = '$tunnus'";
  $tempr = pupe_query($query);
  $laskurow = mysql_fetch_array($tempr);

  // Käytetäänkö VAK-tietokantaa
  if ($yhtiorow["vak_kasittely"] != "") {
    $vakselect = "concat_ws(' ', concat('UN',yk_nro), nimi_ja_kuvaus, lipukkeet, pakkausryhma)";
    $vakjoin   = "JOIN vak ON tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus";
  }
  else {
    $vakselect = "tuote.vakkoodi";
    $vakjoin   = "";
  }

  // Haetaan kaikki vakkoodit arrayseen
  $query = "SELECT group_concat(distinct $vakselect SEPARATOR ', ') vakkoodi
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
            $vakjoin
            where tilausrivi.otunnus = '$laskurow[tunnus]'
            and tilausrivi.yhtio     = '$kukarow[yhtio]'
            and tilausrivi.var       in ('','H')
            and tilausrivi.tyyppi    in ('L','G')
            ORDER BY tuote.vakkoodi";
  $vres = pupe_query($query);

  if (mysql_num_rows($vres) > 0) {
    $vak = mysql_fetch_assoc($vres);
    $vakit = $vak["vakkoodi"];
  }

  $query = "SELECT *
            from asiakas
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$laskurow[liitostunnus]'";
  $tempr = pupe_query($query);
  $asiakasrow = mysql_fetch_array($tempr);
}
else {
  $laskurow = $osoitelappurow;
}

if (isset($oslapp) and $oslapp == "-88") {
  js_openFormInNewWindow();

  echo "<br><form id='tulostakopioform_osoitelappu_{$laskurow['tunnus']}' name='tulostakopioform_osoitelappu_{$laskurow['tunnus']}' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>
        <input type='hidden' name='toim' value='OSOITELAPPU'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='submit' value='".t("Osoitelappu").": {$laskurow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_osoitelappu_{$laskurow['tunnus']}', ''); return false;\"></form><br>";
}
else {

  if ($toim == 'EXTRANET_REKLAMAATIO' and $yhtiorow['reklamaation_vastaanottovarasto'] != 0) {
    if ($yhtiorow['reklamaation_vastaanottovarasto'] !='') {
      $laskurow['varasto'] = $yhtiorow['reklamaation_vastaanottovarasto'];
    }
  }

  // haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
  $query = "SELECT nimi, nimitark, osoite, postino, postitp, maa
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$laskurow[varasto]'";
  $tempr = pupe_query($query);
  $postirow_varasto = mysql_fetch_array($tempr);

  // jos varastolle on annettu joku osoite, käytetään sitä
  if ($postirow_varasto["nimi"] != "") {
    $laskurow["yhtio_nimi"]     = $postirow_varasto["nimi"];
    $laskurow['yhtio_nimitark']  = $postirow_varasto["nimitark"];
    $laskurow["yhtio_osoite"]   = $postirow_varasto["osoite"];
    $laskurow["yhtio_postino"]  = $postirow_varasto["postino"];
    $laskurow["yhtio_postitp"]  = $postirow_varasto["postitp"];
    $laskurow["yhtio_maa"]      = $postirow_varasto["maa"];
  }

  if ($toim == 'EXTRANET_REKLAMAATIO' and $yhtiorow['reklamaation_vastaanottovarasto'] == 0) {
    $query = "SELECT nimi, osoite, postino, postitp, maa
              FROM yhtio
              WHERE yhtio = '$yhtiorow[yhtio]'";
    $tempr = pupe_query($query);
    $postirow_varasto = mysql_fetch_array($tempr);
  }


  // haetaan printterin osoitetiedot
  $query = "SELECT *
            FROM kirjoittimet
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND TRIM(komento) LIKE CONCAT(TRIM('{$oslapp}'),'%')
            AND nimi    != ''";
  $pres  = pupe_query($query);
  $print = mysql_fetch_assoc($pres);

  // jos meillä on printterin takana joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
  if ($print["nimi"] != "") {
    $laskurow["yhtio_nimi"]    = $print["nimi"];
    $laskurow["yhtio_osoite"]  = $print["osoite"];
    $laskurow["yhtio_postino"] = $print["postino"];
    $laskurow["yhtio_postitp"] = $print["postitp"];
  }

  //PDF:n luonti ja defaultit
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
  $rectparam["width"] = 0.3;

  //fontit
  $otsik["height"] = 10;
  $otsik["font"] = "Helvetica";

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

      $oslappkpl = count($uniq_pakkaukset) > 0 ? count($uniq_pakkaukset) : $oslappkpl;
    }
  }

  $kuljetusliike_sscc = false;
  $expansion_code = "";

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

  $kirjainlisa = '';

  for ($tulostuskpl = 1; $tulostuskpl <= $oslappkpl; $tulostuskpl++) {

    if (count($uniq_pakkaukset) == 0) {
      $uniq_merkki = $tulostuskpl;
    }
    else {
      $uniq_merkki = $uniq_pakkaukset[$tulostuskpl];
      $kirjainlisa = " ".chr(64+$uniq_merkki);
    }

    if (isset($uniq_pakkaukset_sscc[$tulostuskpl]) and $uniq_pakkaukset_sscc[$tulostuskpl] != "") {
      $sscc = $uniq_pakkaukset_sscc[$tulostuskpl];
    }
    elseif ($kuljetusliike_sscc) {
      $sscc = gs1_sscc($expansioncode, $laskurow['tunnus'], $tulostuskpl);
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

    if (class_exists("Image_Barcode")) {
      //luodaan viivakoodiolio kuljetusohjeelle = postino
      $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

      if ($toim == 'EXTRANET_REKLAMAATIO') {
        imagejpeg(Image_Barcode::draw($postirow_varasto['postino'], 'code128', 'jpg', false, 3, 170), $nimi);
      }
      else {
        imagejpeg(Image_Barcode::draw($laskurow['toim_postino'], 'code128', 'jpg', false, 3, 170), $nimi);
      }

      $fh = fopen($nimi, "r");
      $data = fread($fh, filesize($nimi));
      fclose($fh);

      $image = $pdf->jfif_embed($data);

      $logoparam['scale'] = 90/282;
      $pdf->image_place($image, mm_pt(80), mm_pt(5), $firstpage, $logoparam);
      system("rm -f $nimi");

      $nimi = "/tmp/".md5(uniqid(rand(), true)).".jpg";

      imagejpeg(Image_Barcode::draw($sscc, 'code128', 'jpg', false, 3, 170), $nimi);

      $fh = fopen($nimi, "r");
      $data = fread($fh, filesize($nimi));
      fclose($fh);

      $image = $pdf->jfif_embed($data);

      $logoparam['scale'] = 90/282;
      $pdf->image_place($image, mm_pt(9), mm_pt(5), $firstpage, $logoparam);
      system("rm -f $nimi");
    }

    if ($toim == 'EXTRANET_REKLAMAATIO') {
      $pdf->draw_text(mm_pt(5), mm_pt(75), "Postinro - Postnr - Postal Code: $postirow_varasto[postino]",  $firstpage, $otsik);
    }
    else {
      $pdf->draw_text(mm_pt(5), mm_pt(75), "Postinro - Postnr - Postal Code: $laskurow[toim_postino]",  $firstpage, $otsik);
    }

    //tehää tästä vielä kaunis kattella
    $sscc = substr($sscc, 0, 4)." ".substr($sscc, 4, 1)." ".substr($sscc, 5, 8)." ".substr($sscc, 13, 8)." ".substr($sscc, 21, 1);

    $pdf->draw_text(mm_pt(5), mm_pt(5), "Kolli - Item ID.: $sscc", $firstpage, $otsik);

    $apu_yhtiorow = array();

    // varmistetaan, että kopiossakin tulee oikean toimipaikan logo
    if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
      $query = "SELECT *
                FROM yhtion_toimipaikat
                WHERE yhtio     = '$kukarow[yhtio]'
                and tunnus      = '$laskurow[yhtio_toimipaikka]'
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
                WHERE yhtio = '$laskurow[yhtio]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $yhtion_parametritrow = mysql_fetch_array($result);
        $apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
      }
    }

    unset($data);
    if ( (int) $apu_yhtiorow["lasku_logo"] > 0) {
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

      if (!$image) {
        echo t("Logokuvavirhe");
      }
      else {

        $logoparam = array();

        if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (200 / $isizelogo[0]) <= 40) {
          $logoparam['scale'] = 200 / $isizelogo[0];
        }
        else {
          $logoparam['scale'] = 40  / $isizelogo[1];
        }

        $placement = $pdf->image_place($image, 580-($logoparam['scale']*$isizelogo[1]), 410-($logoparam['scale']*$isizelogo[0]), $firstpage, $logoparam);
      }
    }

    //Mistä
    if ($toim == 'EXTRANET_REKLAMAATIO') {

      $pdf->draw_text(mm_pt(5), mm_pt(200), "Mistä - Från - From: ",              $firstpage, $otsik);
      $pdf->draw_text(mm_pt(5), mm_pt(195), $laskurow['nimi'],              $firstpage, $kirj);
      $pdf->draw_text(mm_pt(5), mm_pt(190), $laskurow['nimitark'],            $firstpage, $kirj);
      $pdf->draw_text(mm_pt(5), mm_pt(185), $laskurow['osoite'],            $firstpage, $kirj);
      $pdf->draw_text(mm_pt(5), mm_pt(180), $laskurow['postino']." ".strtoupper($laskurow['postitp']),  $firstpage, $kirj);
    }
    else {
      $pdf->draw_text(mm_pt(5), mm_pt(200), "Mistä - Från - From: ",              $firstpage, $otsik);
      $pdf->draw_text(mm_pt(5), mm_pt(195), $laskurow['yhtio_nimi'],              $firstpage, $kirj);
      $pdf->draw_text(mm_pt(5), mm_pt(190), $laskurow['yhtio_nimitark'],            $firstpage, $kirj);
      $pdf->draw_text(mm_pt(5), mm_pt(185), $laskurow['yhtio_osoite'],            $firstpage, $kirj);
      $pdf->draw_text(mm_pt(5), mm_pt(180), $laskurow['yhtio_postino']." ".strtoupper($laskurow['yhtio_postitp']),  $firstpage, $kirj);
    }

    //kolliluku
    if ($rahtikirja_tulostus == "yep" and $toimitustaparow['osoitelappu'] == 'tiivistetty') {
      $pdf->draw_text(mm_pt(70), mm_pt(180), t("Kollimäärä").": ".$s." (".$kollityht.") ",                $firstpage, $kirj);
    }
    elseif ($toimitustaparow['osoitelappu'] == 'tiivistetty') {
      $query = "SELECT sum(kollit) kollit
                FROM rahtikirjat
                WHERE yhtio='$kukarow[yhtio]' and otsikkonro = '$laskurow[tunnus]'";
      $pakka = pupe_query($query);
      $pak = mysql_fetch_array($pakka);

      if ((int) $pak["kollit"] > 0) {
        $pdf->draw_text(mm_pt(70), mm_pt(180), t("Kollimäärä").": ".$pak["kollit"],                $firstpage, $kirj);
      }
    }

    $pdf->draw_text(mm_pt(5), mm_pt(175), "Puh -Tel - Tel: ",                $firstpage, $otsik);
    if ($toim == 'EXTRANET_REKLAMAATIO') {
      $pdf->draw_text(mm_pt(5), mm_pt(170), $asiakasrow['puhelin'],                $firstpage, $kirj);
      $pdf->draw_text(mm_pt(70), mm_pt(175), "Läh.pvm. - Avs.dat. -  Desp.Date: ",      $firstpage, $otsik);
    }
    else {
      $pdf->draw_text(mm_pt(5), mm_pt(170), $yhtiorow['puhelin'],                $firstpage, $kirj);
      $pdf->draw_text(mm_pt(70), mm_pt(175), "Läh.pvm. - Avs.dat. -  Desp.Date: ",      $firstpage, $otsik);
      $pdf->draw_text(mm_pt(70), mm_pt(170), date("d.m.Y") ,                  $firstpage, $kirj);

    }

    if ($_asiakkaantilausnumero) {
      $_otsikko = "Ostotilausnro: ";
      $_mm_pt_y = "163";
      $_mm_pt_y2 = "163";
      $_mm_pt_x = "28";
    }
    else {
      $_otsikko = "Lähettäjä - Avsändare - Sender: ";
      $_mm_pt_y = "165";
      $_mm_pt_y2 = "160";
      $_mm_pt_x = "5";
    }

    $pdf->draw_text(mm_pt(5), mm_pt($_mm_pt_y), $_otsikko, $firstpage, $otsik);
    $pdf->draw_text(mm_pt($_mm_pt_x), mm_pt($_mm_pt_y2), $_asiakkaantilausnumero ? $laskurow["asiakkaan_tilausnumero"] : $kukarow["nimi"], $firstpage, $kirj);

    if ($_ohjausmerkki) {
      $_otsikko = "Asiakkaan merkki: ";
      $_mm_pt_y = "163";
      $_mm_pt_y2 = "158";
      $_mm_pt_x = "70";
    }
    else {
      $_otsikko = "Viite - Referens - Reference: ";
      $_mm_pt_y = "165";
      $_mm_pt_y2 = "160";
      $_mm_pt_x = "70";
    }

    $pdf->draw_text(mm_pt($_mm_pt_x), mm_pt($_mm_pt_y), $_otsikko,        $firstpage, $otsik);

    if ($rahtikirja_tulostus == "yep") {
      $pdf->draw_text(mm_pt($_mm_pt_x), mm_pt($_mm_pt_y2), $_ohjausmerkki ? $laskurow["ohjausmerkki"] : $viestirarrow["viesti"] ,          $firstpage, $kirj);
    }
    else {
      $pdf->draw_text(mm_pt($_mm_pt_x), mm_pt($_mm_pt_y2), $_ohjausmerkki ? $laskurow["ohjausmerkki"] : $laskurow["viesti"] ,              $firstpage, $kirj);
    }

    if ($_tilausviite) {
      $_otsikko = "Kuljetusnumero: ";
      // tilausviitteessä on myös asiakkaan_tilausnumero, siivotaan se tässä
      $_kuljetusnumero = trim(str_replace($laskurow["asiakkaan_tilausnumero"], "", $laskurow["viesti"]));
      $_mm_pt_y = "158";
      $_mm_pt_x = "32";
    }
    else {
      $_otsikko = "Tilaukset - Order - Orders: ";
      $_mm_pt_y = "155";
      $_mm_pt_x = "50";
    }

    $pdf->draw_text(mm_pt(5), mm_pt($_mm_pt_y), $_otsikko,          $firstpage, $otsik);
    if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];

    if ($rahtikirja_tulostus == "yep") {
      $pdf->draw_text(mm_pt($_mm_pt_x), mm_pt($_mm_pt_y), $_tilausviite ? $_kuljetusnumero : $kaikki_lotsikot, $firstpage, $kirj);
    }
    else {
      $pdf->draw_text(mm_pt($_mm_pt_x), mm_pt($_mm_pt_y), $_tilausviite ? $_kuljetusnumero : $tilausnumeroita, $firstpage, $kirj);
    }


    //Minne
    $pdf->draw_rectangle(mm_pt(150), mm_pt(5), mm_pt(150), mm_pt(10),              $firstpage, $rectparam);
    $pdf->draw_rectangle(mm_pt(150), mm_pt(5), mm_pt(145), mm_pt(5),              $firstpage, $rectparam);

    $pdf->draw_rectangle(mm_pt(150), mm_pt(138), mm_pt(150), mm_pt(143),            $firstpage, $rectparam);
    $pdf->draw_rectangle(mm_pt(150), mm_pt(143), mm_pt(145), mm_pt(143),            $firstpage, $rectparam);

    if ($tiedot == "toimitusta") {
      $laskurow["toim_nimi"]       = $toimitustaparow["toim_nimi"];
      $laskurow["toim_nimitark"]     = $toimitustaparow["toim_nimitark"];
      $laskurow["toim_osoite"]     = $toimitustaparow["toim_osoite"];
      $laskurow["toim_maa"]       = $toimitustaparow["toim_maa"];
      $laskurow["toim_postino"]     = $toimitustaparow["toim_postino"];
      $laskurow["toim_postitp"]     = $toimitustaparow["toim_postitp"];
    }

    $pdf->draw_text(mm_pt(15),  mm_pt(145), "Minne - Till - To: " ,              $firstpage, $otsik);

    if ($toim == 'EXTRANET_REKLAMAATIO') {
      $pdf->draw_text(mm_pt(15),  mm_pt(140), $postirow_varasto['nimi'],          $firstpage, $iso);
      $pdf->draw_text(mm_pt(15),  mm_pt(133), $postirow_varasto['nimitark'],        $firstpage, $iso);
      $pdf->draw_text(mm_pt(15),  mm_pt(126), $postirow_varasto['osoite'],        $firstpage, $iso);
      $pdf->draw_text(mm_pt(15),  mm_pt(119), $postirow_varasto['maa']."-".$postirow_varasto['postino']." ".$postirow_varasto['postitp'],  $firstpage, $huge);
    }
    else {
      $pdf->draw_text(mm_pt(15),  mm_pt(140), $laskurow['toim_nimi'],            $firstpage, $iso);
      $pdf->draw_text(mm_pt(15),  mm_pt(133), $laskurow['toim_nimitark'],          $firstpage, $iso);
      $pdf->draw_text(mm_pt(15),  mm_pt(126), $laskurow['toim_osoite'],          $firstpage, $iso);
      $pdf->draw_text(mm_pt(15),  mm_pt(119), $laskurow['toim_maa']."-".$laskurow['toim_postino']." ".$laskurow['toim_postitp'],  $firstpage, $huge);
    }

    $pdf->draw_text(mm_pt(15),  mm_pt(112),  "Puh -Tel - Tel: ",    $firstpage, $otsik);

    if ($toim == 'EXTRANET_REKLAMAATIO') {
      $pdf->draw_text(mm_pt(38),  mm_pt(112), substr($yhtiorow['puhelin'], 0, 15),    $firstpage, $kirj);
      $pdf->draw_text(mm_pt(75), mm_pt(112), "Toimitustapa:",              $firstpage, $otsik);
    }
    else {
      $pdf->draw_text(mm_pt(38),  mm_pt(112), substr($laskurow['toim_puh'], 0, 15),    $firstpage, $kirj);
      $pdf->draw_text(mm_pt(75), mm_pt(112), "Toimitustapa:",        $firstpage, $otsik);
      $pdf->draw_text(mm_pt(97), mm_pt(112), pdf_substr($laskurow["toimitustapa"], mm_pt(65), $pdf, $kirj),  $firstpage, $kirj);
    }

    $pdf->draw_rectangle(mm_pt(110), mm_pt(5), mm_pt(110), mm_pt(10),    $firstpage, $rectparam);
    $pdf->draw_rectangle(mm_pt(110), mm_pt(5), mm_pt(115), mm_pt(5),    $firstpage, $rectparam);

    $pdf->draw_rectangle(mm_pt(110), mm_pt(138), mm_pt(110), mm_pt(143),  $firstpage, $rectparam);
    $pdf->draw_rectangle(mm_pt(110), mm_pt(143), mm_pt(115), mm_pt(143),  $firstpage, $rectparam);

    // Kuljetusohjeet = viivakoodi tulee tähän
    $pdf->draw_text(mm_pt(5), mm_pt(70), "Kuljetusohjeet - Transportinstruktioner - Transport Instructions:",  $firstpage, $otsik);

    // Onko tilaus jälkivaatimus
    $apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $meapu = pupe_query($apuqu);
    $meapurow = mysql_fetch_array($meapu);

    if ($meapurow["jv"] != "") {
      $jvlisays =  " - ".t("Jälkivaatimus");
    }
    else {
      $jvlisays =  "";
    }

    if ($toim != 'EXTRANET_REKLAMAATIO') {
      $pdf->draw_text(mm_pt(5), mm_pt(65), $laskurow["toimitustapa"].$jvlisays,    $firstpage, $kirj);
    }

    $salakalapala = 65;

    if ($laskurow["viesti"] != "" or $laskurow['yhteyshenkilo'] != "" or $viestirarrow["viesti"] != "" or $laskurow['asiakkaan_tilausnumero'] != "") {

      $lisatiedot = "";

      if ($yhtiorow["osoitelappu_lisatiedot"] == "") {
        if ($laskurow['yhteyshenkilo'] != "")       $lisatiedot .= $laskurow['yhteyshenkilo'].". ";
        if ($laskurow['asiakkaan_tilausnumero'] != "" and !$_asiakkaantilausnumero) {
          $lisatiedot .= $laskurow['asiakkaan_tilausnumero'].". ";
        }
      }

      if ($rahtikirja_tulostus == "yep") {
        if ($viestirarrow['viesti'] != "")  $lisatiedot .= $viestirarrow['viesti'].". ";

        $salakalapala -= 5;
        $pdf->draw_text(mm_pt(5), mm_pt($salakalapala), trim($lisatiedot),  $firstpage, $kirj);
      }
      else {
        if ($laskurow['viesti'] != "")  $lisatiedot .= $laskurow['viesti'].". ";

        $salakalapala -= 5;
        $pdf->draw_text(mm_pt(5), mm_pt($salakalapala), trim($lisatiedot),    $firstpage, $kirj);
      }
    }

    if (isset($vakit) and $vakit != "") {
      $salakalapala -= 5;
      list($ff_string, $ff_font) = pdf_fontfit("VAK: $vakit", 400, $pdf, $otsik);
      $pdf->draw_text(mm_pt(5), mm_pt($salakalapala), $ff_string, $firstpage, $ff_font);
    }

    //kolliluku
    if ($rahtikirja_tulostus == "yep") {
      $salakalapala -= 5;
      $pdf->draw_text(mm_pt(5), mm_pt($salakalapala), t("Kollimäärä").": ".$s." (".round($kollityht).") ", $firstpage, $kirj);
    }
    else {
      $query = "SELECT sum(kollit) kollit
                FROM rahtikirjat
                WHERE yhtio='$kukarow[yhtio]' and otsikkonro = '$laskurow[tunnus]'";
      $pakka = pupe_query($query);
      $pak = mysql_fetch_array($pakka);

      if ((int) $pak["kollit"] > 0) {
        $salakalapala -= 5;
        $pdf->draw_text(mm_pt(5), mm_pt($salakalapala), t("Kollimäärä").": ".round($pak["kollit"]), $firstpage, $kirj);
      }
    }

    //tänne ohjeisiin tulee lopullisen asiakkaan tiedot jos kyseessä on koontikuljetus
    $query = "SELECT tulostustapa
              from toimitustapa
              where yhtio = '$kukarow[yhtio]'
              and selite  = '$laskurow[toimitustapa]'";
    $hetir = pupe_query($query);
    $hetirow = mysql_fetch_array($hetir);

    if ($hetirow['tulostustapa'] == 'K') {
      $salakalapala -= 5;
      $pdf->draw_paragraph(mm_pt($salakalapala), mm_pt(5), mm_pt(25), mm_pt(140), $laskurow['sisviesti1'], $firstpage, $kirj);
    }

    // ylhäällä on "oikee" tapa tehdä, mutta me ei tiedetä tässä vaiheessa kollien määrää eikä painoa
    $pdf->draw_text(mm_pt(5), mm_pt(30), "Tilaukset - Order - Orders: ",  $firstpage, $otsik);
    if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];

    if ($rahtikirja_tulostus == "yep") {
      $pdf->draw_text(mm_pt(50), mm_pt(30), $kaikki_lotsikot.$kirjainlisa, $firstpage, $kirj);
    }
    else {
      $pdf->draw_text(mm_pt(50), mm_pt(30), $tilausnumeroita.$kirjainlisa, $firstpage, $kirj);
    }
  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/kolliosoitelappu-".md5(uniqid(rand(), true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  if ($oslapp == 'email') {
    $liite = $pdffilenimi;
    $kutsu = "Osoitelappu";

    if ($toim == 'EXTRANET_REKLAMAATIO') {
      include_once "generoi_laskun_saate.inc";
      list($komento, $content_subject, $content_body) = generoi_laskun_saate($laskurow, $saatekirje, $kieli, $toim);
      $content_subject .= " ".$laskurow['tunnus'];
      require "sahkoposti.inc";
    }
    else {
      require "inc/sahkoposti.inc";
    }
  }
  elseif ($tee == 'NAYTATILAUS') {
    //Työnnetään tuo pdf vaan putkeen!
    echo file_get_contents($pdffilenimi);
  }
  elseif ($oslapp != '' and $oslapp != 'edi') {
    $line = exec("$oslapp $pdffilenimi");
    echo t("Osoitelappu tulostuu")."...<br>";
  }
}

$pdffilenimi='';
$tilausnumeroita="";
