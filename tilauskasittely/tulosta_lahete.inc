<?php

if (@include_once "pdflib/phppdflib.class.php");
else include_once "phppdflib.class.php";

if ($kieli == '') {
  $querykiel = "SELECT kieli
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[liitostunnus]'";
  $kielresult = pupe_query($querykiel);
  $kielrow = mysql_fetch_assoc($kielresult);

  $kieli = strtolower($kielrow['kieli']);
}

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

$boldi["height"] = 10;
$boldi["font"] = "Times-Bold";

$pieni_boldi["height"] = 8;
$pieni_boldi["font"] = "Times-Bold";

$iso["height"] = 14;
$iso["font"] = "Helvetica-Bold";

$rectparam["width"] = 0.3;
$rivinkorkeus = 15;

//muutamat funktiot...
if (!function_exists('alku_lahete')) {
  function alku_lahete($params) {
    global $kukarow, $yhtiorow;

    if (skippa_lahete_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    $lahetetyyppi_avainsana = t_avainsana("LAHETETYYPPI", "", " and selite='$lahetetyyppi'", "", "", "selitetark_2");

    //PDF parametrit
    if ($pdf === NULL) {
      $pdf = new pdffile;
      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
    }

    $thispage = $pdf->new_page("a4");

    $page[$sivu] = $thispage;

    // KAUTTALASKUTUSKIKKARE
    if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
      $query = "SELECT nimi, nimitark, osoite, postino, postitp, maa, osasto
                FROM asiakas
                WHERE yhtio         = '{$GLOBALS['eta_yhtio']}'
                AND laji           != 'P'
                AND ytunnus         = '{$laskurow['ytunnus']}'
                AND toim_ovttunnus  = '{$laskurow['toim_ovttunnus']}'";
      $asiakas_tunnus_res = pupe_query($query);
      $asiakas_tunnus_row = mysql_fetch_assoc($asiakas_tunnus_res);

      if ($asiakas_tunnus_row['osasto'] != '6') {
        $GLOBALS['eta_yhtio'] = "";
        $lahetetyyppi = "tulosta_lahete_eiale_eihinta.inc";
      }
      else {
        $laskurow["eta_yhtio"]     = $GLOBALS['eta_yhtio'];
        $laskurow["yhtio_nimi"]   = $GLOBALS['eta_yhtio_nimi'];
        $laskurow["yhtio_osoite"]   = $GLOBALS['eta_yhtio_osoite'];
        $laskurow["yhtio_postino"]   = $GLOBALS['eta_yhtio_postino'];
        $laskurow["yhtio_postitp"]   = $GLOBALS['eta_yhtio_postitp'];
        $laskurow["yhtio_maa"]     = $GLOBALS['eta_yhtio_maa'];
      }
    }

    tulosta_logo_pdf($pdf, $thispage, $laskurow);

    if (!empty($laskurow['lahetepohjateksti'])) {
      $redboldi["height"] = 8;
      $redboldi["font"]   = "Times-Bold";
      $redboldi["fillcolor"]  = $pdf->get_color("red");

      $pdf->draw_text(30, 832, $laskurow['lahetepohjateksti'], $thispage, $redboldi);
    }

    if ($tyyppi == "TILAUSVAHVISTUS") {
      $kerarow["kerattyaika"] = date("Y-m-d");
    }
    elseif ($tyyppi == "YLLAPITOSOPIMUS") {
      $kerarow["kerattyaika"] = date("Y-m-d");
    }
    else {
      // haetaan max(kerattyaika)
      $query  = "SELECT max(kerattyaika) kerattyaika
                 from tilausrivi
                 where yhtio  = '$kukarow[yhtio]'
                 and otunnus  = '$laskurow[tunnus]'
                 AND tyyppi  != 'D'
                 and var     != 'O'";
      $keraresult = pupe_query($query);
      $kerarow = mysql_fetch_assoc($keraresult);

      if ($kerarow["kerattyaika"] == "0000-00-00 00:00:00") {
        $kerarow["kerattyaika"] = date("Y-m-d H:i:s");
      }
    }

    if ($tyyppi == "TILAUSVAHVISTUS") {
      if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        $otsikko_len = $pdf->strlen(t("TILAUSVAHVISTUS", $kieli), $iso);
        $pdf->draw_text((595 / 2) - ($otsikko_len / 2), 760, t("TILAUSVAHVISTUS", $kieli),     $thispage, $iso);
      }
      else {
        $pdf->draw_text(310, 815, t("Tilausvahvistus", $kieli),     $thispage, $iso);
      }
    }
    elseif ($tyyppi == "YLLAPITOSOPIMUS") {
      $pdf->draw_text(310, 815, t("Yll‰pitosopimus", $kieli),     $thispage, $iso);
    }
    elseif ($tyyppi == "SIIRTOLISTA") {
      $pdf->draw_text(310, 815, t("L‰hete/Siirtolista", $kieli),   $thispage, $iso);
    }
    else {
      $pdf->draw_text(310, 815, t("L‰hete", $kieli),         $thispage, $iso);
    }

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text(530, 803, t("Sivu", $kieli),           $thispage, $pieni);
    }
    else {
      $pdf->draw_text(310, 803, t("Sivu", $kieli),           $thispage, $norm);
    }

    $query = "SELECT gsm, puhelin, ytunnus, verkkotunnus, chn
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$laskurow['liitostunnus']}'";
    $asiakas_res = pupe_query($query);
    $asiakas_row = mysql_fetch_assoc($asiakas_res);

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {

      //Vasen sarake
      $pdf->draw_text(50, 625, t("Asiakas", $kieli),   $thispage, $boldi);

      $pdf->draw_text(50, 612, t("Nimi", $kieli), $thispage, $norm);
      $pdf->draw_text(150, 612, $laskurow["nimi"].' '.$laskurow["nimitark"], $thispage, $norm);

      $pdf->draw_text(50, 599, t("Osoite", $kieli), $thispage, $norm);
      $pdf->draw_text(150, 599, $laskurow["osoite"], $thispage, $norm);
      $pdf->draw_text(150, 586, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);

      $pdf->draw_text(50, 573, t("Puhelinnumero", $kieli), $thispage, $norm);
      $pdf->draw_text(150, 573, $asiakas_row['puhelin'], $thispage, $norm);

      $pdf->draw_text(50, 560, t("GSM", $kieli), $thispage, $norm);
      $pdf->draw_text(150, 560, $asiakas_row['gsm'], $thispage, $norm);

      $pdf->draw_text(50, 547, t("Y-tunnus/Sotu", $kieli), $thispage, $norm);
      $pdf->draw_text(150, 547, $asiakas_row['ytunnus'], $thispage, $norm);

      $pdf->draw_text(350, 625, t("Toimitusosoite", $kieli),   $thispage, $boldi);
      $pdf->draw_text(350, 612, $laskurow["toim_nimi"].' '.$laskurow["toim_nimitark"], $thispage, $norm);
      $pdf->draw_text(350, 599, $laskurow["toim_osoite"], $thispage, $norm);
      $pdf->draw_text(350, 586, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
    }
    else {
      //Vasen sarake
      $pdf->draw_text(50, 729, t("Ostaja", $kieli),   $thispage, $pieni);

      if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
        $pdf->draw_text(50, 717, $asiakas_tunnus_row["nimi"],         $thispage, $norm);
        $pdf->draw_text(50, 707, $asiakas_tunnus_row["nimitark"],      $thispage, $norm);
        $pdf->draw_text(50, 697, $asiakas_tunnus_row["osoite"],       $thispage, $norm);
        $pdf->draw_text(50, 687, $asiakas_tunnus_row["postino"]." ".$asiakas_tunnus_row["postitp"], $thispage, $norm);

        if ($yhtiorow['maa'] != $asiakas_tunnus_row['maa']) {
          $maan_tiedot = hae_maa(array('maakoodi' => $asiakas_tunnus_row['maa']));
          $pdf->draw_text(50, 677, $maan_tiedot["nimi"],         $thispage, $norm);
        }
      }
      else {
        $pdf->draw_text(50, 717, $laskurow["nimi"],       $thispage, $norm);
        $pdf->draw_text(50, 707, $laskurow["nimitark"],      $thispage, $norm);
        $pdf->draw_text(50, 697, $laskurow["osoite"],       $thispage, $norm);
        $pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);

        if ($yhtiorow['maa'] != $laskurow['maa']) {
          $maan_tiedot = hae_maa(array('maakoodi' => $laskurow['maa']));
          $pdf->draw_text(50, 677, $maan_tiedot["nimi"],         $thispage, $norm);
        }
      }

      $pdf->draw_text(50, 656, t("Toimitusosoite", $kieli),   $thispage, $pieni);
      $pdf->draw_text(50, 644, $laskurow["toim_nimi"],     $thispage, $boldi);
      $pdf->draw_text(50, 634, $laskurow["toim_nimitark"],   $thispage, $boldi);
      $pdf->draw_text(50, 624, $laskurow["toim_osoite"],     $thispage, $boldi);
      $pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $boldi);

      if ($yhtiorow['maa'] != $laskurow['toim_maa']) {
        $maan_tiedot = hae_maa(array('maakoodi' => $laskurow['toim_maa']));
        $pdf->draw_text(50, 604, $maan_tiedot["nimi"],     $thispage, $boldi);
      }
    }

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text(530, 785, t("Numero", $kieli), $thispage, $pieni);
      $pdf->draw_text(530, 775, $laskurow["tunnus"], $thispage, $pieni);

      $pvm_len = $pdf->strlen(tv1dateconv($laskurow["luontiaika"]), $pieni);
      $pdf->draw_text((595 / 2) - ($pvm_len / 2), 750, tv1dateconv($laskurow["luontiaika"]),     $thispage, $pieni);
    }
    else {
      //Oikea sarake
      $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);

      if (isset($koontilahete) and $koontilahete != 0) {
        $pdf->draw_text(310, 792, t("Tilausnumero", $kieli),            $thispage, $pieni);
        $pdf->draw_text(310, 782, array_shift(explode(",", $koontilahete)),  $thispage, $boldi);
      }
      elseif ($laskurow["tunnusnippu"] > 0) {
        if ($tyyppi == "YLLAPITOSOPIMUS" or $laskurow["nippu"] > 0) {
          $pdf->draw_text(310, 792, t("Tilausnumero", $kieli),   $thispage, $pieni);
          $pdf->draw_text(310, 782, $laskurow["tunnusnippu"],    $thispage, $boldi);
        }
        else {
          $pdf->draw_text(310, 792, t("Tilausnumero/toimitus", $kieli),             $thispage, $pieni);
          $pdf->draw_text(310, 782, $laskurow["tunnusnippu"]." / ".$laskurow["tunnus"],  $thispage, $boldi);
        }
      }
      elseif ($laskurow["vanhatunnus"] > 0 and $laskurow["vanhatunnus"] != $laskurow["tunnus"]) {
        $pdf->draw_text(310, 792, t("Tilausnumero/toimitus", $kieli),             $thispage, $pieni);
        $pdf->draw_text(310, 782, $laskurow["vanhatunnus"]." / ".$laskurow["tunnus"],  $thispage, $boldi);
      }
      else {
        $pdf->draw_text(310, 792, t("Tilausnumero", $kieli),   $thispage, $pieni);
        $pdf->draw_text(310, 782, $laskurow["tunnus"],      $thispage, $boldi);
      }

      $pdf->draw_text(430, 792, t("Toimitusaika", $kieli),   $thispage, $pieni);

      $useita = "EI";

      if (isset($laskurow["nippu"]) and $laskurow["nippu"] > 0) {
        $query = "SELECT distinct(toimaika)
                  FROM tilausrivi
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and otunnus   IN ($laskurow[nippu])
                  AND tyyppi   != 'D'
                  and var      != 'O'
                  and toimaika != '0000-00-00'";
        $toimaikares = pupe_query($query);

        if (mysql_num_rows($toimaikares) > 1) {
          $useita = "JOO";
        }
      }

      if ($useita == "JOO") {
        $pdf->draw_text(430, 782, t("Useita", $kieli),   $thispage, $norm);
      }
      elseif ($laskurow["toimvko"] != '') {
        $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        $pdf->draw_text(430, 782, $taika,                 $thispage, $norm);
      }
      else {
        if ($tyyppi != "YLLAPITOSOPIMUS" and $tyyppi != 'SIIRTOLISTA' and $laskurow['toimaika'] == '0000-00-00') {
          $pdf->draw_text(430, 782, t("Avoin", $kieli),   $thispage, $norm);
        }
        else {

          if ($yhtiorow['kerayserat'] == 'K' and !empty($laskurow['toimitustavan_lahto'])) {

            $query = "SELECT *
                      FROM lahdot
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$laskurow['toimitustavan_lahto']}'";
            $lahto_res = pupe_query($query);
            $lahto_row = mysql_fetch_assoc($lahto_res);

            $_aika = tv1dateconv("{$lahto_row['pvm']} {$lahto_row['lahdon_kellonaika']}", "PITKƒ");

            $pdf->draw_text(430, 782, $_aika, $thispage, $norm);
          }
          else {
            $pdf->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]),   $thispage, $norm);
          }
        }
      }

      $asrow["ytunnus"] = tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]);
      $pdf->draw_rectangle(779, 300, 758, 580, $thispage,             $rectparam);
      $pdf->draw_rectangle(779, 420, 758, 580, $thispage,             $rectparam);

      if ($asrow["asiakasnro_aito"] == "" or $asrow["asiakasnro_aito"] == "0") {
        $pdf->draw_text(310, 771, t("Ytunnus", $kieli),             $thispage, $pieni);
        $pdf->draw_text(310, 761, $asrow["ytunnus"],               $thispage, $norm);
      }
      else {
        $pdf->draw_text(310, 771, t("Ytunnus / Asiakasnumero", $kieli),             $thispage, $pieni);
        $pdf->draw_text(310, 761, $asrow["ytunnus"] ." / ". $asrow["asiakasnro_aito"],       $thispage, $norm);
      }
      $pdf->draw_text(430, 771, t("Vastaanotettu", $kieli),            $thispage, $pieni);

      if ($lahetetyyppi_avainsana == 1) { // Ei kellonaikaa
        $pdf->draw_text(430, 761, tv1dateconv($laskurow["luontiaika"]), $thispage, $norm);
      }
      else {
        $pdf->draw_text(430, 761, tv1dateconv($laskurow["luontiaika"], "pitka"), $thispage, $norm);
      }
    }

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text(50, 730, t("Myyj‰", $kieli),                 $thispage, $boldi);
    }
    else {
      $pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 750, t("Myyj‰", $kieli),                 $thispage, $pieni);
    }

    //etsit‰‰n myyj‰n nimi
    $query  = "SELECT nimi, kuka, puhno, eposti
               from kuka
               where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
    $myyresult = pupe_query($query);
    $myyrow = mysql_fetch_assoc($myyresult);

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      list($ff_string, $ff_font) = pdf_fontfit($myyrow["nimi"], 105, $pdf, $boldi);
      $pdf->draw_text(150, 730, $ff_string, $thispage, $ff_font);

      $pdf->draw_text(50, 717, t("Y-tunnus", $kieli), $thispage, $boldi);

      if ($yhtiorow['maa'] == 'EE') {
        $pdf->draw_text(150, 717, tulosta_ytunnus(preg_replace("/^0037/", "", $yhtiorow["ytunnus"]), $laskurow["yhtio_maa"], $laskurow["vienti"]), $thispage, $norm);
      }
      else {
        $pdf->draw_text(150, 717, tulosta_ytunnus(preg_replace("/^0037/", "", $laskurow["yhtio_ovttunnus"]), $laskurow["yhtio_maa"], $laskurow["vienti"]), $thispage, $norm);
      }

      $pdf->draw_text(50, 704, t("Nimi", $kieli), $thispage, $boldi);
      $pdf->draw_text(150, 704, $yhtiorow["nimi"], $thispage, $norm);

      $pdf->draw_text(50, 691, t("Osoite", $kieli), $thispage, $boldi);
      $pdf->draw_text(150, 691, $yhtiorow['osoite'], $thispage, $norm);
      $pdf->draw_text(150, 678, $yhtiorow['postino'].' '.$yhtiorow['postitp'], $thispage, $norm);

      $pdf->draw_text(50, 665, t("Puhelin", $kieli), $thispage, $boldi);
      $pdf->draw_text(150, 665, $myyrow['puhno'], $thispage, $norm);

      $pdf->draw_text(50, 652, t("S‰hkˆposti", $kieli), $thispage, $boldi);
      $pdf->draw_text(150, 652, $myyrow['eposti'], $thispage, $norm);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($myyrow["nimi"], 105, $pdf, $norm);
      $pdf->draw_text(310, 740, $ff_string,                     $thispage, $ff_font);

      $pdf->draw_text(430, 750, t("K‰sittelyss‰", $kieli),             $thispage, $pieni);

      if ($lahetetyyppi_avainsana == 1) { // Ei kellonaikaa
        $pdf->draw_text(430, 740, tv1dateconv($laskurow['h1time']), $thispage, $norm);
      }
      else {
        $pdf->draw_text(430, 740, tv1dateconv($laskurow['h1time'], "pitka"), $thispage, $norm);
      }
      $pdf->draw_rectangle(737, 300, 716, 420, $thispage, $rectparam);
      $pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);

      if ($myyrow['kuka'] != $laskurow['laatija']) {
        //etsit‰‰n laatijan nimi
        $query  = "SELECT nimi
                   from kuka
                   where kuka='$laskurow[laatija]' and yhtio='$kukarow[yhtio]'";
        $laaresult = pupe_query($query);
        $laarow = mysql_fetch_assoc($laaresult);

        $pdf->draw_text(310, 729, t("Laatija", $kieli), $thispage, $pieni);

        list($ff_string, $ff_font) = pdf_fontfit($laarow["nimi"], 105, $pdf, $norm);
        $pdf->draw_text(310, 719, $ff_string,                   $thispage, $ff_font);
      }

      $pdf->draw_text(430, 729, t("P‰iv‰ys", $kieli),               $thispage, $pieni);

      if ($lahetetyyppi_avainsana == 1) { // Ei kellonaikaa
        $pdf->draw_text(430, 719, tv1dateconv($kerarow["kerattyaika"]), $thispage, $norm);
      }
      else {
        $pdf->draw_text(430, 719, tv1dateconv($kerarow["kerattyaika"], "pitka"), $thispage, $norm);
      }
      $pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);

      $rahtivapaa = "";

      // Jos rahtivapaa
      if ($laskurow['rahtivapaa'] == 'o' and onko_rahtituote_syotetty($laskurow) == 0) {
        $rahtivapaa = "Rahtivapaa";
      }
      
      if ($tyyppi == "TILAUSVAHVISTUS") {
        if ($laskurow["osatoimitus"] == 'o') {
          $kokonaistoimitus = '/ Kokonaistoimitus';
        }
        else  {
          $kokonaistoimitus = '';
        } 
      }
  
      if ($extranet_tilausvahvistus != 1) {
        if ($laskurow["kohdistettu"] == "K" or $lahetetyyppi_avainsana == 1) {
          // K‰ytet‰‰n LƒHETTƒJƒN rahtisopimusta, EI n‰ytet‰ l‰hett‰j‰n sopparinumeroa.
          $pdf->draw_text(310, 708, t("Toimitustapa", $kieli), $thispage, $pieni);
          $toimitustapateksti = t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)." ".t($rahtivapaa, $kieli)." ".t($kokonaistoimitus, $kieli);
          $pdf->draw_text(310, 698, $toimitustapateksti, $thispage, $boldi);

        }
        else {
          // K‰ytet‰‰n VASTAANOTTAJAN rahtisopimusta, n‰ytet‰‰n sopparinumero.
          $rahtirow = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"], false, $laskurow["rahtisopimus"]);

          $pdf->draw_text(310, 708, t("Toimitustapa", $kieli)." / ".t("Rahtisopimus", $kieli), $thispage, $pieni);
          $toimitustapateksti = t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)." ".t($rahtivapaa, $kieli);
          $pdf->draw_text(310, 698, $toimitustapateksti." ".$rahtirow["rahtisopimus"]." ".t($kokonaistoimitus, $kieli),   $thispage, $boldi);

        }
      }

      $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
      if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
      else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

      if ($lahetetyyppi_avainsana == 1) {
        // Rahdinmaksaja
        $pdf->draw_rectangle(695, 300, 674, 420, $thispage, $rectparam);
        $pdf->draw_text(310, 687, t("Rahdinmaksaja", $kieli), $thispage, $pieni);

        if ($laskurow['kohdistettu'] == 'K') {
          $pdf->draw_text(310, 677, "L‰hett‰j‰", $thispage, $norm);
        } else $pdf->draw_text(310, 677, "Vastaanottaja", $thispage, $norm);

        // Rahtisopimusnumero
        if ($laskurow['kohdistettu'] == 'K') {
          $rahtirow = hae_rahtisopimusnumero($laskurow["toimitustapa"]);
        }
        else {
          $rahtirow = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"], false, $laskurow["rahtisopimus"]);
        }

        $pdf->draw_rectangle(695, 420, 674, 580, $thispage, $rectparam);
        $pdf->draw_text(430, 687, t("Rahtisopimusnumero", $kieli), $thispage, $pieni);
        $pdf->draw_text(430, 677, $rahtirow["rahtisopimus"], $thispage, $norm);

        // Haetaaan ker‰‰j‰
        $keraaja_query = "SELECT distinct kuka.nimi
                          FROM tilausrivi
                          JOIN kuka on (kuka.yhtio = tilausrivi.yhtio
                          AND kuka.kuka = tilausrivi.keratty)
                          WHERE tilausrivi.otunnus='$laskurow[tunnus]'
                          and tilausrivi.yhtio='$kukarow[yhtio]'";
        $keraaja_result = pupe_query($keraaja_query);
        $keraaja = mysql_fetch_assoc($keraaja_result);

        $pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
        $pdf->draw_text(310, 666, t("Ker‰‰j‰", $kieli), $thispage, $pieni);
        $pdf->draw_text(310, 656, ucfirst($keraaja["nimi"]), $thispage, $norm);
      }
      else {
        $pdf->draw_text(310, 687, t("Toimitusehto", $kieli),     $thispage, $pieni);
        $pdf->draw_text(310, 677, $toimehto,             $thispage, $norm);

        $pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
      }

      // maksuehto tekstin‰ jos on jokin muu kuin hinnaton maksuehto
      // extranet tilausvahvistuksessa ei n‰ytet‰ maksuehtoa (jos halutaan n‰ytt‰‰ niin setataan muuttuja nollaksi)
      if (($base_lahetetyyppi != "tulosta_lahete_eiale_eihinta.inc" and $laskurow['lahetetyyppi'] != 'perhe_tulosta_lahete_viivakoodi_eipuute.inc') and
      $extranet_tilausvahvistus != 1 and $lahetetyyppi_avainsana != 1) {
        // haetaan maksuehdon tiedot
        $query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
        $masresult = pupe_query($query);
        $masrow = mysql_fetch_assoc($masresult);

        $pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
        $pdf->draw_text(310, 666, t("Maksuehto", $kieli),   $thispage, $pieni);

        $maksuehtoteksti = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

        if (!in_array($asiakas_row['chn'], array('100', '667')) and $kukarow['extranet'] == "" and $yhtiorow['konserni'] == "indu") {
          $maksuehtoteksti .= " / ".hae_chn_teksti($asiakas_row['chn'], $kieli);
          if ($asiakas_row['verkkotunnus'] != '') $maksuehtoteksti .= " / {$asiakas_row['verkkotunnus']}";
        }

        list($ff_string, $ff_font) = pdf_fontfit($maksuehtoteksti, 265, $pdf, $norm);
        $pdf->draw_text(310, 656, $ff_string, $thispage, $ff_font);
      }

      if (  ($base_lahetetyyppi == "tulosta_lahete.inc"
          or $base_lahetetyyppi == "tulosta_lahete_ean.inc"
          or $base_lahetetyyppi == "tulosta_lahete_ean_eiale.inc"
          or $base_lahetetyyppi == "tulosta_lahete_eialehintoja.inc"
          or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc"
          or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ykshinta_normaali.inc"
          or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc")
        or
        (   isset($GLOBALS['eta_yhtio'])
          and $GLOBALS['eta_yhtio'] != ''
          and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc")) {

        if (($laskurow['alv'] > 0 and $yhtiorow['alv_kasittely'] == '') or strpos($lahetetyyppi, "_verollinen") !== FALSE) {
          $pdf->draw_text(310, 640, t("Hinnat sis‰lt‰v‰t arvonlis‰veron", $kieli).".", $thispage, $boldi);
        }
        else {
          $pdf->draw_text(310, 640, t("Hinnat eiv‰t sis‰ll‰ arvonlis‰veroa", $kieli).".",  $thispage, $boldi);
        }
      }
    }

    $komm = "";

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      if (trim($laskurow['viesti']) != '') {
        $komm .= "\n".t("Tilausviite", $kieli)."###".$laskurow['viesti'];
      }
      if (trim($laskurow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tilausyhteyshenkilˆ", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
      }
      if (trim($laskurow['comments']) != '') {
        $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 90, "\n###");
      }
    }
    else {
      if (trim($laskurow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
      }

      if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
        $komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
      }

      if (trim($laskurow['kohde']) != '') {
        $komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
      }

      if (trim($laskurow['viesti']) != '') {
        $komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
      }

      if (trim($laskurow['comments']) != '') {
        $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 90, "\n###");
      }
      if (trim($laskurow['ohjausmerkki']) != '') {
        $komm .= "\n".t("Ohjausmerkki", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['ohjausmerkki']), 90, "\n###");
      }
    }

    // Haetaan avainsanoista vakioviesti
    $query = "SELECT *
              FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND kieli   = '{$kieli}'
              AND laji    = 'VAKIOVIESTI_TIL'
              ORDER BY jarjestys
              LIMIT 1";
    $vakioviesti_result = pupe_query($query);

    if (mysql_num_rows($vakioviesti_result) == 1) {
      $vakioviesti_row = mysql_fetch_assoc($vakioviesti_result);

      $komm .= "\n".t("Viesti", $kieli).":###".wordwrap(str_replace("\n", "\n###", trim($vakioviesti_row['selite']." ".$vakioviesti_row['selitetark'])), 90, "\n###");
    }

    if ($yhtiorow["vak_kasittely"] != "" and $tyyppi != "TILAUSVAHVISTUS") {

      $vaktiedot = "";

      list($vakrakirtiedot, $vaktuotetiedot) = palauta_vak_tiedot($laskurow['tunnus'], $kieli);

      foreach ($vakrakirtiedot as $vakrakirrivi) {
        $vaktiedot .= "###".$vakrakirrivi."\n";
      }

      foreach ($vaktuotetiedot as $vaktuoterivi) {
        $vaktiedot .= "###".$vaktuoterivi."\n";
      }

      if ($vaktiedot != "") {
        $komm .= "\n\n".t("VAK-tiedot", $kieli).":".$vaktiedot;
      }
    }

    // Tulostetaan laskun kommentti
    if (trim($komm) != '') {
      $kommentit = explode("\n", trim($komm));

      if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        $pohja  = 525;
      }
      else {
        $pohja  = 577;
      }

      $maxoik = 0;

      foreach ($kommentit as $kommentti) {
        if (strpos($kommentti, "###") !== FALSE) {
          list($o, $v) = explode("###", $kommentti);

          $oikpos = $pdf->strlen($o, $boldi);

          if ($oikpos > $maxoik) {
            $maxoik = $oikpos;
          }
        }
      }

      foreach ($kommentit as $kommentti) {

        if (strpos($kommentti, "###") !== false) {
          list($o, $v) = explode("###", $kommentti);

          if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
            $pdf->draw_text(50, $pohja, trim($o), $thispage, $boldi);
            $pdf->draw_text(50+$maxoik+12, $pohja, trim($v), $thispage, $norm);

          }
          else {
            $pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
            $pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
          }

          if ($base_lahetetyyppi == "tulosta_lahete_asiakviivakoodi.inc" and substr($o, 0, -1) == t("Tilausviite", $kieli)) {
            // viivakoodi
            $oikpos = 35+$maxoik+20+$pdf->strlen($v, $norm);

            $data = viivakoodi(trim($v), "code128", 100, 10, "");
            $image = $pdf->jfif_embed($data);
            $pdf->image_place($image, $pohja-15, $oikpos, $thispage, array("scale" => 0.5));

            $kala  = $kala - 10;
            $pohja = $pohja - 10;
          }
        }
        else {
          if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
            $pdf->draw_text(50, $pohja, trim($kommentti), $thispage, $norm);
          }
          else {
            $pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
          }
        }

        $pohja = $pohja - 10;
      }
      $kala = $pohja-30;
    }
    else {
      if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        $kala = 465;
      }
      else {
        $kala = 560;
      }
    }

    if ($base_lahetetyyppi != "tulosta_lahete_custom.inc") {
      // viivakoodi
      $data = viivakoodi($laskurow["tunnus"], "code128", 150, 30, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, 805, 440, $thispage, array("scale" => 0.3));

      $pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
    }

    // M‰‰ritet‰‰n dynaamisesti tuoteno ja nimitys-sarakkeiden leveydet
    $tuotenopituus  = 0;
    $nimityspituus  = 0;

    // Generoidaan rivinumerot
    $rivinumerot = array();

    mysql_data_seek($riviresult, 0);

    while ($row = mysql_fetch_assoc($riviresult)) {
      $rivinumerot[$row["tunnus"]] = $row["tunnus"];

      $tuotenopituus += $pdf->strlen($row["tuoteno"], $norm);
      $nimityspituus += $pdf->strlen($row["nimitys"], $norm);
    }

    sort($rivinumerot);

    $kal = 1;

    foreach ($rivinumerot as $rivino) {
      $rivinumerot[$rivino] = $kal;
      $kal++;
    }

    // Jos tuotenumerot ja nimitykset ovat hyvin pitk‰t
    if (($tuotenopituus / mysql_num_rows($riviresult)) + ($nimityspituus / mysql_num_rows($riviresult)) > 280) {
      $pitkattuotteet = TRUE;
    }

    $tuotenopituus = round($tuotenopituus/($tuotenopituus+$nimityspituus), 2);

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    if (!isset($koontilahete) or $koontilahete == 0 or ($koontilahete != 0 and !$onko_kerayserat_kaytossa)) {
      // Koontil‰hetteelle ei t‰ss‰ vaiheessa oteta otsikoita
      $return = rivi_otsikot_lahete($return);
    }
    else {
      // Koontil‰hetteelle siirret‰‰n pointteria otsikkolaatikoiden verran ylˆsp‰in
      $return["kala"]+=20;
    }

    return $return;
  }
}

if (!function_exists('uusi_sivu_lahete')) {
  function uusi_sivu_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    $lahetetyyppi_avainsana = t_avainsana("LAHETETYYPPI", "", " and selite='$lahetetyyppi'", "", "", "selitetark_2");

    $thispage = $pdf->new_page("a4");

    // KAUTTALASKUTUSKIKKARE
    if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
      $query = "SELECT nimi, nimitark, osoite, postino, postitp, maa, osasto
                FROM asiakas
                WHERE yhtio         = '{$GLOBALS['eta_yhtio']}'
                AND laji           != 'P'
                AND ytunnus         = '{$laskurow['ytunnus']}'
                AND toim_ovttunnus  = '{$laskurow['toim_ovttunnus']}'";
      $asiakas_tunnus_res = pupe_query($query);
      $asiakas_tunnus_row = mysql_fetch_assoc($asiakas_tunnus_res);

      if ($asiakas_tunnus_row['osasto'] != '6') {
        $GLOBALS['eta_yhtio'] = "";
        $lahetetyyppi = "tulosta_lahete_eiale_eihinta.inc";
      }
      else {
        $laskurow["eta_yhtio"]     = $GLOBALS['eta_yhtio'];
        $laskurow["yhtio_nimi"]   = $GLOBALS['eta_yhtio_nimi'];
        $laskurow["yhtio_osoite"]   = $GLOBALS['eta_yhtio_osoite'];
        $laskurow["yhtio_postino"]   = $GLOBALS['eta_yhtio_postino'];
        $laskurow["yhtio_postitp"]   = $GLOBALS['eta_yhtio_postitp'];
        $laskurow["yhtio_maa"]     = $GLOBALS['eta_yhtio_maa'];
      }
    }

    tulosta_logo_pdf($pdf, $thispage, $laskurow);

    if ($tyyppi == "TILAUSVAHVISTUS") {
      $kerarow["kerattyaika"] = date("Y-m-d");
    }
    elseif ($tyyppi == "YLLAPITOSOPIMUS") {
      $kerarow["kerattyaika"] = "";
    }
    else {
      // haetaan max(kerattyaika)
      $query  = "SELECT max(kerattyaika) as kerattyaika
                 from tilausrivi
                 where yhtio  = '$kukarow[yhtio]'
                 and otunnus  = '$laskurow[tunnus]'
                 and var     != 'O'
                 AND tyyppi  != 'D'";
      $keraresult = pupe_query($query);
      $kerarow = mysql_fetch_assoc($keraresult);

      if ($kerarow["kerattyaika"] == "0000-00-00 00:00:00") {
        $kerarow["kerattyaika"] = date("Y-m-d H:i:s");
      }
    }

    if ($tyyppi == "TILAUSVAHVISTUS") {

      if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        $otsikko_len = $pdf->strlen(t("TILAUSVAHVISTUS", $kieli), $iso);
        $pdf->draw_text((595 / 2) - ($otsikko_len / 2), 760, t("TILAUSVAHVISTUS", $kieli), $thispage, $iso);
      }
      else {
        $pdf->draw_text(310, 815, t("Tilausvahvistus", $kieli),     $thispage, $iso);
      }
    }
    elseif ($tyyppi == "YLLAPITOSOPIMUS") {
      $pdf->draw_text(310, 815, t("Yll‰pitosopimus", $kieli),     $thispage, $iso);
    }
    elseif ($tyyppi == "SIIRTOLISTA") {
      $pdf->draw_text(310, 815, t("L‰hete/Siirtolista", $kieli),   $thispage, $iso);
    }
    else {
      $pdf->draw_text(310, 815, t("L‰hete", $kieli),         $thispage, $iso);
    }

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text(530, 803, t("Sivu", $kieli), $thispage, $pieni);
    }
    else {
      $pdf->draw_text(310, 803, t("Sivu", $kieli),           $thispage, $norm);
    }

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text(530, 785, t("Numero", $kieli), $thispage, $pieni);
      $pdf->draw_text(530, 775, $laskurow["tunnus"], $thispage, $pieni);

      $pvm_len = $pdf->strlen(tv1dateconv($laskurow["luontiaika"]), $pieni);
      $pdf->draw_text((595 / 2) - ($pvm_len / 2), 750, tv1dateconv($laskurow["luontiaika"]),     $thispage, $pieni);
    }
    else {
      //Oikea sarake
      $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);

      $pdf->draw_text(310, 792, t("Tilausnumero", $kieli),   $thispage, $pieni);
      $pdf->draw_text(430, 792, t("Toimitusaika", $kieli),   $thispage, $pieni);
      $pdf->draw_text(310, 782, $laskurow["tunnus"],      $thispage, $boldi);

      if ($useita == "JOO") {
        $pdf->draw_text(430, 782, t("Useita", $kieli),   $thispage, $norm);
      }
      elseif ($laskurow["toimvko"] != '') {
        $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        $pdf->draw_text(430, 782, $taika,                 $thispage, $norm);
      }
      else {
        $pdf->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]),   $thispage, $norm);
      }

      if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];

      $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);

      if ($asrow["asiakasnro_aito"] == "" or $asrow["asiakasnro_aito"] == "0") {
        $pdf->draw_text(310, 771, t("Ytunnus", $kieli),             $thispage, $pieni);
        $pdf->draw_text(310, 761, $asrow["ytunnus"],               $thispage, $norm);
      }
      else {
        $pdf->draw_text(310, 771, t("Ytunnus / Asiakasnumero", $kieli),             $thispage, $pieni);
        $pdf->draw_text(310, 761, $asrow["ytunnus"] ." / ". $asrow["asiakasnro_aito"],       $thispage, $norm);
      }
      // $pdf->draw_text(310, 771, t("Asiakasnumero", $kieli),             $thispage, $pieni);
      // $pdf->draw_text(310, 761, $asrow["asiakasnro"],               $thispage, $norm);
      $pdf->draw_text(430, 771, t("P‰iv‰ys", $kieli),               $thispage, $pieni);

      if ($lahetetyyppi_avainsana == 1) { // Ei kellonaikaa
        $pdf->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"], "pitka"),   $thispage, $norm);
      }
      else {
        $pdf->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"]),   $thispage, $norm);
      }

      // viivakoodi
      $data = viivakoodi($laskurow["tunnus"], "code128", 70, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, 805, 440, $thispage, array("scale" => 0.3));
    }

    $kala = 715;

    if ($base_lahetetyyppi != "tulosta_lahete_custom.inc") {
      $pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    if (trim($ei_otsikoita) == '') {
      $return = rivi_otsikot_lahete($return);
    }

    return $return;
  }
}

if (!function_exists('rivi_otsikot_lahete')) {
  function rivi_otsikot_lahete($params, $kollitiedot=null) {
    global $kukarow, $yhtiorow;

    if (skippa_lahete_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    if ($base_lahetetyyppi == "tulosta_lahete.inc" or $base_lahetetyyppi == "tulosta_lahete_ean.inc" or $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc' or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc") {
      // tulosta_lahete.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);

      //tuoteno ja nimitys sopivan leveiksi
      $nimityskohta = (220 * $tuotenopituus + 90);
      $nimitysleveys = 310 - $nimityskohta;

      if ($pitkattuotteet) {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, 320,   $thispage, $rectparam);
      }
      else {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, $nimityskohta-5,   $thispage, $rectparam);
        $pdf->draw_rectangle($kala+30, $nimityskohta-5, $kala+10, 320,   $thispage, $rectparam);
      }

      $pdf->draw_rectangle($kala+30, 320, $kala+10, 370,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 370, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",          $thispage, $norm);

      if ($pitkattuotteet) {
        $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli)." / ".t("Tuotenimi", $kieli),  $thispage, $norm);
      }
      else {
        $pdf->draw_text(45, $kala+15, t("Tuotenumero", $kieli),        $thispage, $norm);
        $pdf->draw_text($nimityskohta, $kala+15, t("Tuotenimi", $kieli),  $thispage, $norm);
      }

      $pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),    $thispage, $norm);
      $pdf->draw_text(480, $kala+15, t("Ale", $kieli),      $thispage, $norm);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),  $thispage, $norm);
      }
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_ean_eiale.inc") {
      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);

      //tuoteno ja nimitys sopivan leveiksi
      $nimityskohta = (220 * $tuotenopituus + 90);
      $nimitysleveys = 310 - $nimityskohta;

      if ($pitkattuotteet) {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, 320,   $thispage, $rectparam);
      }
      else {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, $nimityskohta-5,   $thispage, $rectparam);
        $pdf->draw_rectangle($kala+30, $nimityskohta-5, $kala+10, 320,   $thispage, $rectparam);
      }

      $pdf->draw_rectangle($kala+30, 320, $kala+10, 370,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 370, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 580,   $thispage, $rectparam);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_rectangle($kala+30, 490, $kala+10, 580,   $thispage, $rectparam);
      }

      $pdf->draw_text(25,  $kala+15, "#",          $thispage, $norm);

      if ($pitkattuotteet) {
        $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli)." / ".t("Tuotenimi", $kieli),  $thispage, $norm);
      }
      else {
        $pdf->draw_text(45, $kala+15, t("Tuotenumero", $kieli),        $thispage, $norm);
        $pdf->draw_text($nimityskohta, $kala+15, t("Tuotenimi", $kieli),  $thispage, $norm);
      }

      $pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),    $thispage, $norm);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_text(495, $kala+15, t("Rivihinta", $kieli),  $thispage, $norm);
      }
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_brutto.inc") {
      // tulosta_lahete_brutto.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 140,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 140, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text(145, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(475, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(525, $kala+15, t("Ovh/Kpl", $kieli),    $thispage, $norm);
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_eialeja.inc") {

      //tulosta_lahete_eialeja.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);

      //tuoteno ja nimitys sopivan leveiksi
      $nimityskohta = (220 * $tuotenopituus + 90);
      $nimitysleveys = 310 - $nimityskohta;

      if ($pitkattuotteet) {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, 320,   $thispage, $rectparam);
      }
      else {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, $nimityskohta-5,   $thispage, $rectparam);
        $pdf->draw_rectangle($kala+30, $nimityskohta-5, $kala+10, 320,   $thispage, $rectparam);
      }

      $pdf->draw_rectangle($kala+30, 320, $kala+10, 370,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 370, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);

      if ($pitkattuotteet) {
        $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli)." / ".t("Tuotenimi", $kieli),  $thispage, $norm);
      }
      else {
        $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),      $thispage, $norm);
        $pdf->draw_text($nimityskohta, $kala+15, t("Tuotenimi", $kieli),  $thispage, $norm);
      }

      $pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),    $thispage, $norm);
      if ($extranet_tilausvahvistus != 1) {
        $pdf->draw_text(473, $kala+15, t("Alekoodi", $kieli),    $thispage, $norm);
      }

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),    $thispage, $norm);
      }

    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi.inc" or $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc') {
      // tulosta_lahete_viivakoodi.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 180, $kala+10, 480,   $thispage, $rectparam);

      if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc') {
        $pdf->draw_rectangle($kala+30, 430, $kala+10, 480,   $thispage, $rectparam);
      }

      $pdf->draw_rectangle($kala+30, 480, $kala+10, 530,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 530, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);

      if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc') {
        $pdf->draw_text(435, $kala+15, t("Yks.hinta", $kieli),    $thispage, $norm);
      }

      $pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_osh.inc") {
      // tulosta_lahete_viivakoodi.inc
      if ($yhtiorow['alv_kasittely'] == "o" and strpos($lahetetyyppi, "_verollinen") === FALSE) {
        $tuotealv = t("Veroton", $kieli);
      }
      else {
        $tuotealv = t("Verollinen", $kieli);
      }

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 180, $kala+10, 360,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 360, $kala+10, 400,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 400, $kala+10, 450,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 450, $kala+10, 510,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 510, $kala+10, 580,   $thispage, $rectparam);

      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);
      $pdf->draw_text(370, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(410, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(460, $kala+22, t("OSH-hinta", $kieli),    $thispage, $pieni);
      $pdf->draw_text(460, $kala+13, $tuotealv,           $thispage, $pieni);
      $pdf->draw_text(515, $kala+22, t("Yks. nettohinta", $kieli), $thispage, $pieni);
      $pdf->draw_text(515, $kala+13, $tuotealv,          $thispage, $pieni);
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_asiakviivakoodi.inc") {
      // tulosta_lahete_asiakviivakoodi.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 180, $kala+10, 480,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 480, $kala+10, 530,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 530, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);
      $pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_ean13.inc") {
      // tulosta_lahete_viivakoodi_ean13.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 180, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 480,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 480, $kala+10, 530,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 530, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("As.hinta(kpl)", $kieli),    $thispage, $norm);
      $pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_eialehintoja.inc") {
      //tulosta_lahete_eialehintoja.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);

      //tuoteno ja nimitys sopivan leveiksi
      $nimityskohta = (270 * $tuotenopituus + 90);
      $nimitysleveys = 360 - $nimityskohta;

      if ($pitkattuotteet) {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, 370,   $thispage, $rectparam);
      }
      else {
        $pdf->draw_rectangle($kala+30, 40,  $kala+10, $nimityskohta-5,   $thispage, $rectparam);
        $pdf->draw_rectangle($kala+30, $nimityskohta-5, $kala+10, 370,   $thispage, $rectparam);
      }

      $pdf->draw_rectangle($kala+30, 370, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",          $thispage, $norm);

      if ($pitkattuotteet) {
        $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli)." / ".t("Tuotenimi", $kieli),  $thispage, $norm);
      }
      else {
        $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),      $thispage, $norm);
        $pdf->draw_text($nimityskohta, $kala+15, t("Tuotenimi", $kieli),  $thispage, $norm);
      }

      $pdf->draw_text(373, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(423, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(473, $kala+15, t("Yks.hinta", $kieli),    $thispage, $norm);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_text(523, $kala+15, t("Rivihinta", $kieli),    $thispage, $norm);
      }
    }
    elseif (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
      //tulosta_lahete_hae_hinnat.inc

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 180, $kala+10, 320,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 320, $kala+10, 370,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 370, $kala+10, 420,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);
      $pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),    $thispage, $norm);
      $pdf->draw_text(500, $kala+15, t("Ale", $kieli), $thispage, $norm);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),    $thispage, $norm);
      }
    }
    elseif ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text(50,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $boldi);
      $pdf->draw_text(150, $kala+15, t("Tuotenimi", $kieli),    $thispage, $boldi);
      $pdf->draw_text(330, $kala+15, t("Toimitus", $kieli),    $thispage, $boldi);
      $pdf->draw_text(380, $kala+15, t("M‰‰r‰", $kieli),      $thispage, $boldi);
      $pdf->draw_text(425, $kala+15, t("Yksikkˆ", $kieli),    $thispage, $boldi);

      if (trim($naytetaanko_rivihinta) == '') {
        $pdf->draw_text(473, $kala+15, t("Hinta alv.", $kieli), $thispage, $boldi);

        if ($yhtiorow['alv_kasittely'] == '' or strpos($lahetetyyppi, "_verollinen") !== FALSE) {
          $pdf->draw_text(473, $kala+5, t("Verollinen", $kieli), $thispage, $boldi);
        }
        else {
          $pdf->draw_text(473, $kala+5, t("Veroton", $kieli),  $thispage, $boldi);
        }
      }

      $kala -= 10;
    }
    else {
      //tulosta_lahete_eiale_eihinta.inc

      //tuoteno ja nimitys sopivan leveiksi
      $nimityskohta = (380 * $tuotenopituus + 90);
      $nimitysleveys = 470 - $nimityskohta;

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,    $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, $nimityskohta-5,  $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, $nimityskohta-5, $kala+10, 480,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 480, $kala+10, 530,   $thispage, $rectparam);
      $pdf->draw_rectangle($kala+30, 530, $kala+10, 580,   $thispage, $rectparam);
      $pdf->draw_text(25,  $kala+15, "#",              $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),  $thispage, $norm);
      $pdf->draw_text($nimityskohta, $kala+15, t("Tuotenimi", $kieli),    $thispage, $norm);
      $pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),    $thispage, $norm);
      $pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),    $thispage, $norm);
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('kollirivi_lahete')) {
  function kollirivi_lahete($params) {
    global $kukarow, $yhtiorow;

    if (skippa_lahete_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    // Mahtuuko rivi t‰lle sivulle
    $kalatest = $kala;

    // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
    if ($kala <= 70) {
      $sivu++;

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = loppu_lahete($return);
      $params = uusi_sivu_lahete($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;
    }

    $kala -= 5;

    $teksti = t("Kolli", $kieli).": ".$row['pakkaus']." - ".$row['sscc_ulkoinen']." ";

    list($vakrakirtiedot, $vaktuotetiedot) = palauta_vak_tiedot($row['otunnus'], $kieli);

    if ($vakrakirtiedot[$kierros] != "") {
      $vakpaino = preg_match('/[\d]+[.][\d]+ kg/', $vakrakirtiedot[$kierros], $matches);
      $vakpaino = $matches[0];

      $teksti .= t("VAK-tiedot", $kieli).": ".t("Bruttopaino", $kieli)." $paino     ";
    }

    $teksti .= t("sis‰lt‰en").":";

    $pdf->draw_text(25, $kala, $teksti, $thispage, $font);

    $kala -= 30;

    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_lahete')) {
  function rivi_lahete($params) {
    global $kukarow, $yhtiorow;

    if (skippa_lahete_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
    if ($kala <= 70) {
      $sivu++;

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = loppu_lahete($return);
      $params = uusi_sivu_lahete($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;
    }

    // JT-rivit pikkasen alasp‰in omalla otsikolla (ei tehd‰ JT-otsikkoa, jos tilausta ei osatoimiteta)
    if ((!isset($jtid) or $jtid == 0) and $row["jtsort"] == 1 and $laskurow['osatoimitus'] == '') {

      if (isset($jtid)) $kala = $kala-10;

      $xkoord[0] = 20;
      $xkoord[1] = 580;
      $ykoord[0] = $ykoord[1] = $kala - 4;
      $pdf->draw_line($xkoord, $ykoord, $page[$sivu], $rectparam);

      $pdf->draw_text(25,  $kala, "#",                  $thispage, $norm);
      $pdf->draw_text(45,  $kala, t("J‰lkitoimitukseen", $kieli).":",    $thispage, $norm);
      $kala = $kala-$rivinkorkeus;
    }

    // Rahtituote pikkasen alasp‰in omalla otsikolla
    if (isset($jtid) and $jtid != 2 and $row["jtsort"] == 2) {

      $kala = $kala-5;

      $xkoord[0] = 20;
      $xkoord[1] = 580;
      $ykoord[0] = $ykoord[1] = $kala - 4;
      $pdf->draw_line($xkoord, $ykoord, $page[$sivu], $rectparam);

      $pdf->draw_text(25,  $kala, "#",                $thispage, $norm);
      $pdf->draw_text(45,  $kala, t("Toimituskulut", $kieli).":",    $thispage, $norm);
      $kala = $kala-$rivinkorkeus;
    }

    //K‰‰nnet‰‰n nimitys
    $row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys', $kieli);

    // Trimmataan ja otetaan "yhteens‰kommentti" pois
    $row["kommentti"] = trim(poista_rivin_yhteensakommentti($row["kommentti"]));

    // Kommentissa voi olla jotain sanoja jotka pit‰‰ k‰‰‰nt‰‰:
    if ($kieli != $yhtiorow["kieli"]) {
      $row["kommentti"] = str_replace("Ennakkotilaus:", t("Ennakkotilaus", $kieli).":", $row["kommentti"]);
      $row["kommentti"] = str_replace("Tilaus:", t("Tilaus", $kieli).":", $row["kommentti"]);
    }

    // Tilaajan rivinumero
    if ($row["tilaajanrivinro"] != 0) {
      $row["kommentti"] .= "\n".t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";
    }

    // Tutkitaan onko tuotteelle asiakaskommentti
    if ($base_lahetetyyppi != "tulosta_lahete_asiakviivakoodi.inc") {
      $askomquery = "SELECT *
                     from asiakaskommentti
                     where yhtio = '$kukarow[yhtio]'
                     and tuoteno = '$row[tuoteno]'
                     and ytunnus = '$laskurow[ytunnus]'
                     and tyyppi  = ''";
      $askomresul = pupe_query($askomquery);

      if (mysql_num_rows($askomresul) > 0) {
        while ($askomrow = mysql_fetch_assoc($askomresul)) {
          $row["kommentti"] .= "\n".$askomrow["kommentti"];
        }
      }
    }

    $row["perhe_kommentti1"] = "";
    $row["perhe_kommentti2"] = "";

    // Info tuoteperheest‰
    if ($yhtiorow["tuoteperheinfo_lahetteella"] == "" and $row["perheid"] > 0 and $perheid != $row["perheid"]) {
      $numrows = 0;

      $query = "SELECT vanhatunnus
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$row[otunnus]'";
      $vtunres = pupe_query($query);
      $vtunrow = mysql_fetch_assoc($vtunres);

      if ($vtunrow["vanhatunnus"] != 0) {
        $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                  FROM lasku use index (yhtio_vanhatunnus)
                  WHERE yhtio     = '$kukarow[yhtio]'
                  and vanhatunnus = '$vtunrow[vanhatunnus]'";
        $perheresult = pupe_query($query);
        $numrows = mysql_num_rows($perheresult);
      }

      if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
        $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                  FROM lasku use index (PRIMARY)
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[otunnus]'";
        $perheresult = pupe_query($query);
      }

      if (mysql_num_rows($perheresult) > 0) {
        $perherow = mysql_fetch_assoc($perheresult);

        $query = "SELECT distinct tilausrivi.tuoteno, tilausrivi.nimitys, varattu
                  FROM tilausrivi
                  JOIN tuote tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                  WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                  and tilausrivi.otunnus  in ($perherow[tunnukset])
                  and tilausrivi.perheid  = '$row[perheid]'
                  and tuote.tuotetyyppi  != 'R'
                  and tilausrivi.tyyppi  != 'V'
                  AND tilausrivi.tyyppi  != 'D'
                  and tilausrivi.var     != 'O'
                  ORDER BY tilausrivi.tunnus";
        $perheresult = pupe_query($query);

        if (mysql_num_rows($perheresult) > 1) {
          $perherow = mysql_fetch_assoc($perheresult);

          $row["perhe_kommentti1"] = $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"];

          while ($perherow = mysql_fetch_assoc($perheresult)) {
            $row["perhe_kommentti2"] .= strtoupper($perherow["tuoteno"]).", ";
          }

          $row["perhe_kommentti2"] = substr($row["perhe_kommentti2"], 0, -2);
        }
      }
    }

    // Koontil‰hetteelle mik‰ alkuperˆinen toimitus on ollut kyseess‰
    if (isset($koontilahete) and $koontilahete != 0) {
      $row["kommentti"] = t("Toimitus", $kieli).": $row[otunnus]\n".trim($row["kommentti"]);
    }

    // Trimmataan uudestaan
    $row["kommentti"] = trim($row["kommentti"]);

    // Haetaan tuotteen myyntihinta
    $query = "SELECT *
              FROM tuote
              WHERE tuoteno = '$row[tuoteno]'
              and yhtio     = '$kukarow[yhtio]'";
    $asresult = pupe_query($query);
    $asrow1 = mysql_fetch_assoc($asresult);

    //saadaan l‰hetteen summa menem‰‰n oikein, taaksep‰in yhteensopivasti
    $row["myyntihinta"]      = tuotteen_myyntihinta($laskurow, $asrow1, ($row["kpl"]+$row["varattu"]+$row["jt"]), "EINETTOA");
    $row["brutto_rivihinta"]   = $row["myyntihinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]);
    $row["aleryhma"]       = $asrow1["aleryhma"];

    if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {

      // Lasketaan alet yhteen
      $alennukset = generoi_alekentta_php($row, 'M', 'kerto');

      $row["rivihinta"] = $row["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]) * $alennukset;

      $row["rivihinta"] = hintapyoristys(laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]));
      $row["hinta"]     = hintapyoristys(laskuval($row["hinta"], $laskurow["vienti_kurssi"]));
      $row["ovhhinta"]  = hintapyoristys(laskuval($row["ovhhinta"], $laskurow["vienti_kurssi"]));
    }

    $perheid    = $row["perheid"];
    $jtid     = $row["jtsort"];
    $piilotarivi = "";

    // Summataan erkioisale mukaan ale1-kentt‰‰n
    if ($row["netto"] != 'N' and $laskurow["erikoisale"] > 0) {
      $row["ale1"] = round((1-(1-($row["ale1"]+$row["erikoisale"]-($row["ale1"]*$row["erikoisale"]/100))/100))*100, 2);
    }

    if ($row["perheid"] > 0 and substr($lahetetyyppi, 0, 6) == "perhe_") {

      // kyseess‰ on is‰
      if ($row["perheid"] == $row["tunnus"]) {

        $query = "SELECT vanhatunnus
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[otunnus]'";
        $vtunres = pupe_query($query);
        $vtunrow = mysql_fetch_assoc($vtunres);

        if ($vtunrow["vanhatunnus"] != 0) {
          $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                    FROM lasku use index (yhtio_vanhatunnus)
                    WHERE yhtio     = '$kukarow[yhtio]'
                    and vanhatunnus = '$vtunrow[vanhatunnus]'";
          $perheresult = pupe_query($query);
          $numrows = mysql_num_rows($perheresult);
        }

        if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
          $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                    FROM lasku use index (PRIMARY)
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$row[otunnus]'";
          $perheresult = pupe_query($query);
        }

        if (mysql_num_rows($perheresult) > 0) {
          $perherow = mysql_fetch_assoc($perheresult);

          $isahintarivikpl = (float) $row["kpl"]+$row["varattu"]+$row["jt"];

          $query_ale_lisa = generoi_alekentta('M');

          // lasketaan is‰tuotteen riville lapsien hinnat yhteen
          $query = "SELECT
                    round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi=0, 1, lasku.vienti_kurssi)) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta,
                    round(round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi=0, 1, lasku.vienti_kurssi)) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),'$yhtiorow[hintapyoristys]') / $isahintarivikpl,'$yhtiorow[hintapyoristys]') hinta,
                    GROUP_CONCAT(CONCAT(tilausrivi.tuoteno, '°!°!', tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)) perheen_tuotteet
                    FROM tilausrivi
                    JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                    WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                    and tilausrivi.otunnus  in ($perherow[tunnukset])
                    AND tilausrivi.tyyppi  != 'D'
                    and tilausrivi.var     != 'O'
                    and tilausrivi.perheid  = '$row[perheid]'";
          $riresult = pupe_query($query);
          $perherow = mysql_fetch_assoc($riresult);

          $row["hinta"]            = $perherow["hinta"];
          $row["rivihinta"]        = $perherow["rivihinta"];
          $row['myyntihinta']      = 0;
          $row['brutto_rivihinta'] = 0;
          $myyntihinta_temp        = 0;

          foreach (explode(",", $perherow['perheen_tuotteet']) as $perhetuote) {

            list($perhetuoteno, $perhekpl) = explode('°!°!', $perhetuote);

            $query = "SELECT *
                      FROM tuote
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tuoteno = '$perhetuoteno'";
            $lapsi_res = pupe_query($query);
            $lapsi_row = mysql_fetch_assoc($lapsi_res);

            $myyntihinta_temp = tuotteen_myyntihinta($laskurow, $lapsi_row, $perhekpl, "EINETTOA");
            $row['myyntihinta'] += $myyntihinta_temp;
            $row['brutto_rivihinta'] += $myyntihinta_temp * $perhekpl;
          }

          $row["ale1"] = round(100 * (1 - ($perherow["rivihinta"] / ($perherow["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"])))), 2);

          for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $row['ale'.$alepostfix] = 0;
          }
        }
      }
      else {
        if (strpos($lahetetyyppi, 'hinnat_isalla')) {
          $piilotarivi = "";
          //asetetaan alkioihin string null, jotta hintapyˆristys osaa palauttaa: ''
          $row["hinta"] = 'null';
          $row["rivihinta"] = 'null';

          $row["myyntihinta"] = 'null';
          $row["brutto_rivihinta"] = 'null';
          $myyntihinta_temp = 'null';
          for ($alepostfix = 0; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $row['ale'.$alepostfix] = '';
          }
        }
        else {
          // lapsia ei lis‰t‰
          $piilotarivi = 1;
        }
      }
    }

    if ($useita == "JOO" and $row["toimaika"] != "0000-00-00") {
      $row["kommentti"] .= "\n".t("Toimitusaika", $kieli).": ".tv1dateconv($row["toimaika"]);
    }

    $row["tilkpl"]   = ($row["tilkpl"]*1);
    $row["varattu"] = ($row["varattu"]*1);
    $row["kpl"]   = ($row["kpl"]*1);
    $kpl_rivilla   = (($row["kpl"]+$row["varattu"]+$row["jt"])*1);

    if ($piilotarivi != 1) $yhteensamaara += $kpl_rivilla;

    $row['varattu_orig'] = $kpl_rivilla;

    // Erikseen toimitetuille riveille ei n‰ytet‰ toimitettu-saraketta
    if (!isset($row["d_erikseen"]) or $row["d_erikseen"] == "") {
      if ($row["keratty"] != '' and !in_array($row["var"], array('J', 'P'))) {
        $row['varattu'] = (($row['varattu']+$row['kpl'])*1)." ".t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
        $row['varattu_viivakoodi'] = $kpl_rivilla*1;
      }
      else {
        if ($row["var"] == 'J' and $laskurow['osatoimitus'] == '' and $base_lahetetyyppi != 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc' and $base_lahetetyyppi != 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc') { // j‰lkitoimitus
          $row['varattu'] = t("**JT**", $kieli);
          $row['varattu_viivakoodi'] = 0;
        }
        elseif ($row["var"] == 'P') { // puute
          $row['varattu'] = t("PUUTE", $kieli);
          $row['varattu_viivakoodi'] = 0;
        }
        elseif ($yhtiorow["lahetteen_tulostustapa"] == "L") {
          // Jos l‰hetett‰ tulostetaan ker‰yslistan kanssa yht‰aikaa, niin silloin pistet‰‰n aina toimitettu sarakkeeseen tilattum‰‰r‰
          $row["varattu"] = $row["tilkpl"];
          $row["kpl"]   = "";
          $row['varattu_viivakoodi'] = 0;
        }
        else {
          $row["varattu"] = "";
          $row["kpl"]   = "";
          $row['varattu_viivakoodi'] = 0;
        }
      }
    }
    else {
      $row["varattu"] = "";
    }

    // Jos meill‰ on marginaalimyynti‰/k‰‰nteinen alv
    if ($row["alv"] >= 600) {
      $row["kommentti"] .= "\n".t("Ei lis‰tty‰ arvonlis‰veroa, ostajan k‰‰nnetty verovelvollisuus", $kieli).".";
    }
    elseif ($row["alv"] >= 500) {
      $row["kommentti"] .= "\n".t("Ei sis‰ll‰ v‰hennett‰v‰‰ veroa", $kieli).".";
    }

    // Haetaan sarjanumeron tiedot
    if ($row["sarjanumeroseuranta"] != "") {
      if ($tyyppi == "SIIRTOLISTA") {
        $sarjanutunnus = "siirtorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and $row["var2"] == "EIOST") {
        $sarjanutunnus = "ostorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and ($row['sarjanumeroseuranta'] == 'S' or $row['sarjanumeroseuranta'] == 'G')) {
        $sarjanutunnus = "myyntirivitunnus";
      }
      elseif ($row["varattu"]+$row["kpl"] < 0) {
        $sarjanutunnus = "ostorivitunnus";
      }
      else {
        $sarjanutunnus = "myyntirivitunnus";
      }

      $query = "SELECT DISTINCT sarjanumero, parasta_ennen
                FROM sarjanumeroseuranta
                WHERE yhtio      = '$kukarow[yhtio]'
                and tuoteno      = '$row[tuoteno]'
                and $sarjanutunnus='$row[tunnus]'
                and sarjanumero != ''
                ORDER BY sarjanumero";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) > 0) {
        if ($row["kommentti"] != '') {
          $row["kommentti"] .= "\n";
        }

        if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
          $row["kommentti"] .= t("E:nro", $kieli).": ";
        }
        else {
          $row["kommentti"] .= t("S:nro", $kieli).": ";
        }

        while ($sarjarow = mysql_fetch_assoc($sarjares)) {
          if ($row["sarjanumeroseuranta"] == "F") {
            $row["kommentti"] .= $sarjarow["sarjanumero"]." ".t("Parasta ennen", $kieli).": ".tv1dateconv($sarjarow["parasta_ennen"]).", ";
          }
          else {
            $row["kommentti"] .= $sarjarow["sarjanumero"].", ";
          }
        }

        $row["kommentti"] = substr($row["kommentti"], 0, -2);
      }
    }

    // Lis‰t‰‰n mahdollinen saapumisaika tuoterivin alle, jos kyseess‰ on jt-rivi
    $saapumisaika = jt_saapumisaika_tilausvahvistus($row, $asrow1["status"], $kieli);

    if (!empty($row["kommentti"]) and !empty($saapumisaika)) {
      $row["kommentti"] .= "\n{$saapumisaika}";
    }
    elseif (!empty($saapumisaika)) {
      $row["kommentti"] = $saapumisaika;
    }

    // Mahtuuko rivi t‰lle sivulle
    $kalatest = $kala;

    if (substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {

      // Luodaan palautettavat
      $returntest = compact(array_keys($params));

      //Fuulataan t‰m‰, jotta voidaan kokeilla mahtuuko rivi sivulle
      $returntest["thispage"] = "TESTISIVU";

      if ($base_lahetetyyppi == "tulosta_lahete.inc" or $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc') {
        $returntest = rivi_hinta_ale_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_ean.inc" or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc") {
        $returntest = rivi_hinta_ale_lahete_ean($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_ean_eiale.inc") {
        $returntest = rivi_hinta_eiale_lahete_ean($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_brutto.inc") {
        $returntest = rivi_brutto_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_eialeja.inc") {
        $returntest = rivi_eialeja_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi.inc" or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc") {
        $returntest = rivi_viivakoodi_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_osh.inc") {
        $returntest = rivi_viivakoodi_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_asiakviivakoodi.inc") {
        $returntest = rivi_asiakviivakoodi_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_ean13.inc") {
        $returntest = rivi_viivakoodi_lahete_ean13($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_eialehintoja.inc") {
        $returntest = rivi_eialehintoja_lahete($returntest);
      }
      elseif (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
        $returntest = rivi_hae_hinnat_lahete($returntest);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        $returntest = rivi_custom_lahete($returntest);
      }
      else {
        $returntest = rivi_ei_ale_ei_hinta_lahete($returntest);
      }

      // Otetaan vain kalamuuttuja t‰st‰
      $kalatest = $returntest["kala"];
    }

    // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
    if ($kalatest <= 70 or $kalatest > $kala) {
      $sivu++;

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = loppu_lahete($return);
      $params = uusi_sivu_lahete($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    if (substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
      if ($base_lahetetyyppi == "tulosta_lahete.inc" or $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc') {
        // tulosta_lahete.inc
        if ((($row["var"] != 'J' or ($row['var'] == 'J' and $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc')) or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {

          // Myyntihinnat sis‰lt‰v‰t arvonlis‰veron
          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          // Myyntihinnat ovat arvonlis‰verottomia
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_hinta_ale_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_ean.inc" or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc") {
        // tulosta_lahete.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {

          if ($lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc") {
            $maara = $yhtiorow['myynnin_alekentat'];
            for ($alenumero = $maara; $alenumero > 1; $alenumero--) {
              $row["rivihinta"] = $row["rivihinta"] / (1 - $row["ale{$maara}"] / 100);
            }
          }

          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_hinta_ale_lahete_ean($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_ean_eiale.inc") {

        $return['row']['rivihinta'] = $row['rivihinta'] = hintapyoristys($row['hinta'] * $row['tilkpl']);

        // tulosta_lahete.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));

          }
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_hinta_eiale_lahete_ean($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_brutto.inc") {
        // tulosta_lahete_brutto.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
          $return['row']['rivihinta'] = $row['rivihinta'] = round($row['ovhhinta'] * $kpl_rivilla, 2);

          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['ovhhinta']   = hintapyoristys($row['ovhhinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_brutto_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_eialeja.inc") {
        //tulosta_lahete_eialeja.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['brutto_rivihinta'];
            $return["arvo"]  += ($row['brutto_rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          else {
            $return["summa"] += ($row['brutto_rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['brutto_rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['myyntihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['brutto_rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['brutto_rivihinta'] = $row['brutto_rivihinta'] = 0;
        }

        $return = rivi_eialeja_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi.inc" or $base_lahetetyyppi == "tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc") {

        // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit hintaan
        if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc' and strpos($lahetetyyppi, "_verollinen") !== FALSE) {
          $return['row']['hinta']  = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
        }

        // tulosta_lahete_viivakoodi.inc
        $return = rivi_viivakoodi_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_osh.inc") {
        // tulosta_lahete_viivakoodi.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_viivakoodi_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_asiakviivakoodi.inc") {
        // tulosta_lahete_asiakviivakoodi.inc
        $return = rivi_asiakviivakoodi_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_ean13.inc") {
        // rivi_viivakoodi_lahete_ean13.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_viivakoodi_lahete_ean13($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_eialehintoja.inc") {

        $return['row']['rivihinta'] = $row['rivihinta'] = hintapyoristys($row['hinta'] * $row['tilkpl']);

        // tulosta_lahete_eialehintoja.inc
        if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
          if ($yhtiorow['alv_kasittely'] == '') {
            $return["summa"] += $row['rivihinta'];
            $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
          else {
            $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return["arvo"]  += $row['rivihinta'];

            // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
            if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
              $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
              $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            }
          }
        }
        else {
          $return['row']['rivihinta'] = $row['rivihinta'] = 0;
        }

        $return = rivi_eialehintoja_lahete($return);
      }
      elseif (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
        $return = rivi_hae_hinnat_lahete($return);
      }
      elseif ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        //rivi_custom_lahete.inc
        if ($yhtiorow['alv_kasittely'] == '') {
          $return["summa"] += $row['rivihinta'];
          $return["arvo"]  += ($row['rivihinta'] / (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
        }
        else {
          $return["summa"] += ($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          $return["arvo"]  += $row['rivihinta'];

          // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
          if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
            $return['row']['hinta']   = hintapyoristys($row['hinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
            $return['row']['rivihinta'] = hintapyoristys($row['rivihinta'] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
          }
        }

        $return = rivi_custom_lahete($return);
      }
      else {
        //tulosta_lahete_eiale_eihinta.inc
        //tulosta_lahete_eiale_eihinta_iso.inc
        $return = rivi_ei_ale_ei_hinta_lahete($return);
      }
    }

    // Rivin loppuviiva
    if ($base_lahetetyyppi != "tulosta_lahete_custom.inc") {
      $xkoord[0] = 20;
      $xkoord[1] = 580;
      $ykoord[0] = $ykoord[1] = $return["kala"] + $return["rivinkorkeus"] - 5;
      $pdf->draw_line($xkoord, $ykoord, $return["thispage"], $return["rectparam"]);
    }
    return $return;
  }
}

if (!function_exists('rivi_hinta_ale_lahete')) {
  function rivi_hinta_ale_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    // Rivin eka rivi
    $rivi_kala = $kala;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],  $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    if ($pitkattuotteet) {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 270, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }

    $oikpos = $pdf->strlen($row["tilkpl"], $norm);
    $pdf->draw_text(353-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $pdf->draw_text(355, $kala, $omyks, $thispage, $pieni);

    if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc' and $yhtiorow['lahetteen_tulostustapa'] == 'I') {
      $oikpos = $pdf->strlen($row["tilkpl"], $norm);
      $pdf->draw_text(415-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);
    }
    else {
      $oikpos = $pdf->strlen($row["varattu"], $norm);
      $pdf->draw_text(415-$oikpos, $kala, $row["varattu"], $thispage, $norm);
    }

    $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
    $pdf->draw_text(465-$oikpos, $kala, hintapyoristys($row["hinta"]), $thispage, $norm);

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
    }

    $lisakala = '';
    $ed_ale = array();

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {

      if (trim($row["ale{$alepostfix}"]) != 0) {
        if ($alepostfix > 1) {
          if (count($ed_ale) > 0) {
            $lisakala .= "+";
          }
          $lisakala .= ($row["ale{$alepostfix}"] * 1);
        }
        else {
          $lisakala .= ($row["ale{$alepostfix}"] * 1);
        }

        $ed_ale[$alepostfix] = $row["ale{$alepostfix}"];
      }
    }

    list($ff_string, $ff_font) = pdf_fontfit($lisakala, 56, $pdf, $norm);

    $oikpos = $pdf->strlen($ff_string, $ff_font);
    $pdf->draw_text(518-$oikpos, $kala, $ff_string, $thispage, $ff_font);

    if ($pitkattuotteet) {
      $kala = $kala - $rivinkorkeus+5;
      $pdf->draw_text(45, $kala, $row["nimitys"], $thispage, $norm);
    }
    else {
      if ($pdf->strlen($row["nimitys"], $norm) >= $nimitysleveys) {
        $kala = $kala - $rivinkorkeus;
      }

      $pdf->draw_text($nimityskohta, $kala, $row["nimitys"], $thispage, $norm);
    }

    $kala = $kala - $rivinkorkeus;

    if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc') {
      $alakala = $kala;

      // tarkista_ean13 on uusi, jos syˆtett‰v‰ EAN-koodi on viallinen, niin ei tehd‰ viivakoodi tulostusta PDF:lle
      if ($row["eankoodi"] != "" and $row["eankoodi"] != "0" and strlen($row["eankoodi"]) == "13" and is_numeric($row["eankoodi"]) and tarkista_ean13($row["eankoodi"]) !== FALSE) {
        //viivakoodi
        $data = viivakoodi($row["eankoodi"], "ean", 100, 10, "");
        $image = $pdf->jfif_embed($data);
        $pdf->image_place($image, $kala-15, 410, $thispage, array("scale" => 0.5));
        $pdf->draw_text(425, $kala-17, $row["eankoodi"], $thispage, $pieni);

        $alakala = $kala-17-$rivinkorkeus;
      }
    }

    if ($row["kommentti"] != '') {

      $kommentin_pituus = $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc' ? 85 : 90;

      $row["kommentti"] = wordwrap($row["kommentti"], $kommentin_pituus, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($row["myyntihinta_maara"] > 1) {

      if ($kala == ($rivi_kala - $rivinkorkeus)) {
        $kala = $kala - $rivinkorkeus + 5;
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $pieni);
      $pdf->draw_text(465-$oikpos, ($rivi_kala-$rivinkorkeus+5), hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $thispage, $pieni);
      $pdf->draw_text(465, ($rivi_kala-$rivinkorkeus+5), " / $row[myyntihinta_maara] $omyks", $thispage, $pieni);
    }

    if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali.inc' and $kala > $alakala) $kala = $alakala;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_hinta_ale_lahete_ean')) {
  function rivi_hinta_ale_lahete_ean($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Rivin eka rivi
    $rivi_kala = $kala;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],  $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    if ($pitkattuotteet) {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 270, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }

    $oikpos = $pdf->strlen($row["tilkpl"], $norm);
    $pdf->draw_text(353-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $pdf->draw_text(355, $kala, $omyks, $thispage, $pieni);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(415-$oikpos, $kala, $row["varattu"], $thispage, $norm);

    $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
    $pdf->draw_text(465-$oikpos, $kala, hintapyoristys($row["hinta"]), $thispage, $norm);

    if (trim($naytetaanko_rivihinta) == '') {

      if ($lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc") {
        $maara = $yhtiorow['myynnin_alekentat'];
        for ($alenumero = $maara; $alenumero > 1; $alenumero--) {
          $row["rivihinta"] = $row["rivihinta"] / (1 - $row["ale{$maara}"] / 100);
        }
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
    }

    if ($lahetetyyppi == "tulosta_lahete_viivakoodi_ale1.inc") {
      $lisakala = ($row["ale1"] * 1);
    }
    else {
      $lisakala = '';
      $ed_ale = array();

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {

        if (trim($row["ale{$alepostfix}"]) != 0) {
          if ($alepostfix > 1) {
            if (count($ed_ale) > 0) {
              $lisakala .= "+";
            }
            $lisakala .= ($row["ale{$alepostfix}"] * 1);
          }
          else {
            $lisakala .= ($row["ale{$alepostfix}"] * 1);
          }

          $ed_ale[$alepostfix] = $row["ale{$alepostfix}"];
        }
      }
    }

    list($ff_string, $ff_font) = pdf_fontfit($lisakala, 56, $pdf, $norm);

    $oikpos = $pdf->strlen($ff_string, $ff_font);
    $pdf->draw_text(518-$oikpos, $kala, $ff_string, $thispage, $ff_font);

    if ($pitkattuotteet) {
      $kala = $kala - $rivinkorkeus+5;
      $pdf->draw_text(45, $kala, $row["nimitys"], $thispage, $norm);
    }
    else {
      if ($pdf->strlen($row["nimitys"], $norm) >= $nimitysleveys) {
        $kala = $kala - $rivinkorkeus;
      }

      $pdf->draw_text($nimityskohta, $kala, $row["nimitys"], $thispage, $norm);
    }

    $kala = $kala - $rivinkorkeus;
    $alakala = $kala;

    // tarkista_ean13 on uusi, jos syˆtett‰v‰ EAN-koodi on viallinen, niin ei tehd‰ viivakoodi tulostusta PDF:lle
    if ($row["eankoodi"] != "" and $row["eankoodi"] != "0" and strlen($row["eankoodi"]) == "13" and is_numeric($row["eankoodi"]) and tarkista_ean13($row["eankoodi"]) !== FALSE) {
      //viivakoodi
      $data = viivakoodi($row["eankoodi"], "ean", 100, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, $kala-15, 410, $thispage, array("scale" => 0.5));
      $pdf->draw_text(425, $kala-17, $row["eankoodi"], $thispage, $pieni);

      $alakala = $kala-17-$rivinkorkeus;
    }

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($row["myyntihinta_maara"] > 1) {

      if ($kala == ($rivi_kala - $rivinkorkeus)) {
        $kala = $kala - $rivinkorkeus + 5;
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $pieni);
      $pdf->draw_text(465-$oikpos, ($rivi_kala-$rivinkorkeus+5), hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $thispage, $pieni);
      $pdf->draw_text(465, ($rivi_kala-$rivinkorkeus+5), " / $row[myyntihinta_maara] $omyks", $thispage, $pieni);
    }

    if ($kala > $alakala) $kala = $alakala;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_hinta_eiale_lahete_ean')) {
  function rivi_hinta_eiale_lahete_ean($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Rivin eka rivi
    $rivi_kala = $kala;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],  $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    if ($pitkattuotteet) {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 270, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }

    $oikpos = $pdf->strlen($row["tilkpl"], $norm);
    $pdf->draw_text(353-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $pdf->draw_text(355, $kala, $omyks, $thispage, $pieni);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(415-$oikpos, $kala, $row["varattu"], $thispage, $norm);

    $pdf->draw_text(425, $kala, hintapyoristys($row["hinta"]), $thispage, $norm);

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf->draw_text(518-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
    }

    if ($pitkattuotteet) {
      $kala = $kala - $rivinkorkeus+5;
      $pdf->draw_text(45, $kala, $row["nimitys"], $thispage, $norm);
    }
    else {
      if ($pdf->strlen($row["nimitys"], $norm) >= $nimitysleveys) {
        $kala = $kala - $rivinkorkeus;
      }

      $pdf->draw_text($nimityskohta, $kala, $row["nimitys"], $thispage, $norm);
    }

    $kala = $kala - $rivinkorkeus;
    $alakala = $kala;

    // tarkista_ean13 on uusi, jos syˆtett‰v‰ EAN-koodi on viallinen, niin ei tehd‰ viivakoodi tulostusta PDF:lle
    if ($row["eankoodi"] != "" and $row["eankoodi"] != "0" and strlen($row["eankoodi"]) == "13" and is_numeric($row["eankoodi"]) and tarkista_ean13($row["eankoodi"]) !== FALSE) {
      //viivakoodi
      $data = viivakoodi($row["eankoodi"], "ean", 100, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, $kala-15, 410, $thispage, array("scale" => 0.5));
      $pdf->draw_text(425, $kala-17, $row["eankoodi"], $thispage, $pieni);

      $alakala = $kala-17-$rivinkorkeus;
    }

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
      $lisarivi++;
    }

    if ($row["myyntihinta_maara"] > 1) {

      if ($kala == ($rivi_kala - $rivinkorkeus)) {
        $kala = $kala - $rivinkorkeus + 5;
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $pieni);
      $pdf->draw_text(465-$oikpos, ($rivi_kala-$rivinkorkeus+5), hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $thispage, $pieni);
      $pdf->draw_text(465, ($rivi_kala-$rivinkorkeus+5), " / $row[myyntihinta_maara] $omyks", $thispage, $pieni);
    }

    if ($kala > $alakala) $kala = $alakala;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_custom_lahete')) {
  function rivi_custom_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $boldi);
    $pdf->draw_text(50, $kala, $ff_string, $thispage, $ff_font);

    list($vv, $kk, $pp) = explode("-", $row["toimaika"]);
    $vko = date("W", mktime(0, 0, 0, $kk, $pp, $vv));

    if ($vko == date("W", mktime(0, 0, 0, 12, 31, $vv))) {
      $vko .= ' - 1';
    }
    else {
      $vko .= ' - '.(date("W", mktime(0, 0, 0, $kk, $pp, $vv)) + 1);
    }

    $pdf->draw_text(330, $kala, t("Vko", $kieli)." $vko", $thispage, $norm);

    $oikpos = $pdf->strlen($row["tilkpl"], $norm);
    $pdf->draw_text(410-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $pdf->draw_text(425, $kala, $omyks, $thispage, $norm);

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf->draw_text(513-$oikpos, $kala, hintapyoristys($row["rivihinta"]),       $thispage, $norm);
    }

    list ($ff_string, $ff_font) = pdf_fontfit($row['nimitys'], 170, $pdf, $norm);
    $pdf->draw_text(150, $kala, $ff_string, $thispage, $ff_font);

    $kala = $kala - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(150, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 90) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_brutto_lahete')) {
  function rivi_brutto_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Rivin eka rivi
    $rivi_kala = $kala;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],              $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 100, $pdf, $norm);
    $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
    $pdf->draw_text(465-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(515-$oikpos, $kala, $row["varattu"],                 $thispage, $norm);

    $oikpos = $pdf->strlen(hintapyoristys($row["ovhhinta"]), $norm);
    $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["ovhhinta"]),         $thispage, $norm);

    if ($pdf->strlen($row["nimitys"], $norm) >= 270) {
      $kala = $kala - $rivinkorkeus;
    }
    $pdf->draw_text(145, $kala, $row["nimitys"],                     $thispage, $norm);

    $kala = $kala - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($row["myyntihinta_maara"] > 1) {

      if ($kala == ($rivi_kala - $rivinkorkeus)) {
        $kala = $kala - $rivinkorkeus + 5;
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["ovhhinta"]*$row["myyntihinta_maara"]), $pieni);
      $pdf->draw_text(539-$oikpos, ($rivi_kala-$rivinkorkeus+5), hintapyoristys($row["ovhhinta"]*$row["myyntihinta_maara"]), $thispage, $pieni);
      $pdf->draw_text(539, ($rivi_kala-$rivinkorkeus+5), " / $row[myyntihinta_maara] $omyks", $thispage, $pieni);
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_ei_ale_ei_hinta_lahete')) {
  function rivi_ei_ale_ei_hinta_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    $lisarivi = 0;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]], $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $lisarivi++;
    }

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], $nimityskohta-65, $pdf, $norm);
    $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);


    list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 480-$nimityskohta, $pdf, $norm);
    $pdf->draw_text($nimityskohta, $kala, $ff_string, $thispage, $ff_font);


    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
    $pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(575-$oikpos, $kala, $row["varattu"],                 $thispage, $norm);

    $kala = $kala - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
      $lisarivi++;
    }

    // jos halutaan isommat rivit niin tehd‰‰n yks tyhj‰ (eillei oo jo kommenttia tai pitk‰‰ nimityst‰)
    if ($base_lahetetyyppi == "tulosta_lahete_eiale_eihinta_iso.inc" and $lisarivi == 0) {
      $kala = $kala - $rivinkorkeus;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_eialeja_lahete')) {
  function rivi_eialeja_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Rivin eka rivi
    $rivi_kala = $kala;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]], $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    if ($pitkattuotteet) {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 270, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
    $pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(415-$oikpos, $kala, $row["varattu"],                 $thispage, $norm);


    $oikpos = $pdf->strlen(hintapyoristys($row["myyntihinta"]), $norm);
    $pdf->draw_text(465-$oikpos, $kala, hintapyoristys($row["myyntihinta"]),               $thispage, $norm);

    if ($extranet_tilausvahvistus != 1) {
      list($ff_string, $ff_font) = pdf_fontfit($row["aleryhma"], 50, $pdf, $norm);
      $pdf->draw_text(475, $kala, $ff_string, $thispage, $ff_font);
    }

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf->strlen(hintapyoristys($row["brutto_rivihinta"]), $norm);
      $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["brutto_rivihinta"]),     $thispage, $norm);
    }

    if ($pitkattuotteet) {
      $kala = $kala - $rivinkorkeus+5;
      $pdf->draw_text(45, $kala, $row["nimitys"], $thispage, $norm);
    }
    else {

      if ($pdf->strlen($row["nimitys"], $norm) >= $nimitysleveys) {
        $kala = $kala - $rivinkorkeus;
      }

      $pdf->draw_text($nimityskohta, $kala, $row["nimitys"], $thispage, $norm);
    }

    $kala = $kala - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($row["myyntihinta_maara"] > 1) {

      if ($kala == ($rivi_kala - $rivinkorkeus)) {
        $kala = $kala - $rivinkorkeus + 5;
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $pieni);
      $pdf->draw_text(465-$oikpos, ($rivi_kala-$rivinkorkeus+5), hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $thispage, $pieni);
      $pdf->draw_text(465, ($rivi_kala-$rivinkorkeus+5), " / $row[myyntihinta_maara] $omyks", $thispage, $pieni);
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_eialehintoja_lahete')) {
  function rivi_eialehintoja_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Rivin eka rivi
    $rivi_kala = $kala;

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]], $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    if ($pitkattuotteet) {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 270, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
      $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);
    }

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"], $norm);
    $pdf->draw_text(403-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);
    $pdf->draw_text(405, $kala, $omyks, $thispage, $pieni);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(465-$oikpos, $kala, $row["varattu"],                 $thispage, $norm);

    $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
    $pdf->draw_text(515-$oikpos, $kala, hintapyoristys($row["hinta"]),          $thispage, $norm);

    if (trim($naytetaanko_rivihinta) == '') {
      $oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
      $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]),       $thispage, $norm);
    }

    if ($pitkattuotteet) {
      $kala = $kala - $rivinkorkeus+5;
      $pdf->draw_text(45, $kala, $row["nimitys"], $thispage, $norm);
    }
    else {
      if ($pdf->strlen($row["nimitys"], $norm) >= $nimitysleveys) {
        $kala = $kala - $rivinkorkeus;
      }

      $pdf->draw_text($nimityskohta, $kala, $row["nimitys"], $thispage, $norm);
    }

    $kala = $kala - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($row["myyntihinta_maara"] > 1) {

      if ($kala == ($rivi_kala - $rivinkorkeus)) {
        $kala = $kala - $rivinkorkeus + 5;
      }

      $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $pieni);
      $pdf->draw_text(515-$oikpos, ($rivi_kala-$rivinkorkeus+5), hintapyoristys($row["hinta"]*$row["myyntihinta_maara"]), $thispage, $pieni);
      $pdf->draw_text(515, ($rivi_kala-$rivinkorkeus+5), " / $row[myyntihinta_maara] $omyks", $thispage, $pieni);
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_hae_hinnat_lahete')) {
  function rivi_hae_hinnat_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],              $thispage, $pieni);

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
    $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
    $pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(415-$oikpos, $kala, $row["varattu"],                 $thispage, $norm);

    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
              AND tuoteno = '{$row['tuoteno']}'";
    $tres = pupe_query($query);
    $trow = mysql_fetch_assoc($tres);

    list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $row["varattu"], '', '', '', '', $GLOBALS['eta_yhtio']);

    $oikpos = $pdf->strlen(hintapyoristys($lis_hinta), $norm);
    $pdf->draw_text(465-$oikpos, $kala, hintapyoristys($lis_hinta),         $thispage, $norm);

    $lisakala = '';

    $iiii = 1;
    $ed_val = array();

    foreach ($lis_ale_kaikki as $val) {
      if (trim($val) != 0) {
        if ($iiii > 1) {
          if (count($ed_val) > 0) {
            $lisakala .= "+";
          }
          $lisakala .= ($val * 1);
        }
        else {
          $lisakala .= ($val*1);
        }

        $ed_val[$val] = $val;
      }

      $iiii++;
    }

    list($ff_string, $ff_font) = pdf_fontfit($lisakala, 56, $pdf, $norm);
    $oikpos = $pdf->strlen($ff_string, $ff_font);
    $pdf->draw_text(522-$oikpos, $kala, $ff_string, $thispage, $ff_font);

    if (trim($naytetaanko_rivihinta) == '') {
      $rivihinta = $lis_hinta;

      foreach ($lis_ale_kaikki as $val_ale) {
        $rivihinta *= (1-($val_ale/100));
      }

      $rivihinta *= $row['varattu_orig'];

      $oikpos = $pdf->strlen(hintapyoristys($rivihinta), $norm);
      $pdf->draw_text(575-$oikpos, $kala, hintapyoristys($rivihinta),       $thispage, $norm);
    }

    if ($pdf->strlen($row["nimitys"], $norm) >= 130) {
      $kala = $kala - $rivinkorkeus;
    }

    $pdf->draw_text(190, $kala, $row["nimitys"],                     $thispage, $norm);

    $kala = $kala - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_viivakoodi_lahete')) {
  function rivi_viivakoodi_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]], $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
    $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    if ($base_lahetetyyppi == "tulosta_lahete_viivakoodi_osh.inc") {
      $pohja = $pdf->draw_paragraph($kala+$norm["height"]+1, 185, 70, 360, $row["nimitys"], $thispage, $norm);

      $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
      $pdf->draw_text(395-$oikpos, $kala, $row["tilkpl"]." ".$omyks,   $thispage, $norm);

      $oikpos = $pdf->strlen($row["varattu"], $norm);
      $pdf->draw_text(445-$oikpos, $kala, $row["varattu"], $thispage, $norm);

      // Haetaan tuotteen myyntihinta
      $query = "SELECT *
                FROM tuote
                WHERE tuoteno = '$row[tuoteno]'
                and yhtio     = '$kukarow[yhtio]'";
      $asresult = pupe_query($query);
      $asrow1 = mysql_fetch_assoc($asresult);

      //saadaan l‰hetteen summa menem‰‰n oikein, taaksep‰in yhteensopivasti
      $row["myyntihinta"] = hintapyoristys(tuotteen_myyntihinta($laskurow, $asrow1, ($row["kpl"]+$row["varattu"]+$row["jt"]), "EINETTOA"));

      // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
      if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
        $row["myyntihinta"] = hintapyoristys($row["myyntihinta"] * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
      }

      $oikpos = $pdf->strlen($row["myyntihinta"]  , $norm);
      $pdf->draw_text(500-$oikpos, $kala, $row["myyntihinta"], $thispage, $norm);

      $alennukset = generoi_alekentta_php($row, 'M', 'kerto');
      $nettohinta = hintapyoristys($row["hinta"] * $alennukset);

      // Verollinen l‰hetetyyppi, lis‰t‰‰n alvit rivihintoihin
      if (strpos($lahetetyyppi, "_verollinen") !== FALSE) {
        $nettohinta = hintapyoristys($nettohinta * (1 + (($row['alv'] >= 500) ? 0 : $row['alv']) / 100));
      }

      $oikpos = $pdf->strlen($nettohinta, $norm);
      $pdf->draw_text(570-$oikpos, $kala, $nettohinta, $thispage, $norm);
    }
    else {
      $pohja = $pdf->draw_paragraph($kala+$norm["height"]+1, 185, 70, 460, $row["nimitys"], $thispage, $norm);

      if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc') {
        $oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
        $pdf->draw_text(475-$oikpos, $kala, hintapyoristys($row["hinta"]), $thispage, $norm);
      }

      $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
      $pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks,   $thispage, $norm);

      if ($base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc' and $yhtiorow['lahetteen_tulostustapa'] == 'I') {
        $oikpos = $pdf->strlen($row["tilkpl"], $norm);
        $pdf->draw_text(575-$oikpos, $kala, $row["tilkpl"], $thispage, $norm);
      }
      else {
        $oikpos = $pdf->strlen($row["varattu"], $norm);
        $pdf->draw_text(575-$oikpos, $kala, $row["varattu"], $thispage, $norm);
      }
    }

    $kala = $pohja - $rivinkorkeus;
    $alakala = $kala;

    if (trim($row["eankoodi"]) != "" and trim($row["eankoodi"]) != '0') {
      // viivakoodi
      $data = viivakoodi($row["eankoodi"], "ean", 100, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, $kala-15, 410, $thispage, array("scale" => 0.5));
      $pdf->draw_text(420, $kala-17, $row["eankoodi"], $thispage, $pieni);
      $alakala = $kala-17-$rivinkorkeus;
    }

    if ($row["kommentti"] != '') {

      $kommentin_pituus = $base_lahetetyyppi == 'tulosta_lahete_viivakoodi_ykshinta_normaali_eiale.inc' ? 85 : 90;

      $row["kommentti"] = wordwrap($row["kommentti"], $kommentin_pituus, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($kala > $alakala) $kala = $alakala;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_asiakviivakoodi_lahete')) {
  function rivi_asiakviivakoodi_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],                $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
    $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
    $pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks,   $thispage, $norm);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(575-$oikpos, $kala, $row["varattu"],                   $thispage, $norm);

    $pohja = $pdf->draw_paragraph($kala+$norm["height"]+1, 185, 70, 460, $row["nimitys"],  $thispage, $norm);

    $kala = $pohja - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    // tutkitaan onko tuotteelle kommentti
    $eanquery = "SELECT kommentti
                 from asiakaskommentti
                 where yhtio = '$kukarow[yhtio]'
                 and ytunnus = '$laskurow[ytunnus]'
                 and tuoteno = '$row[tuoteno]'
                 and tyyppi  = ''";
    $eanres = pupe_query($eanquery);
    $komrow = mysql_fetch_assoc($eanres);

    if ($komrow["kommentti"] != "") {
      //viivakoodi
      $data = viivakoodi($komrow["kommentti"], "code128", 100, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, $kala-13, 45, $thispage, array("scale" => 0.5));
      $pdf->draw_text(55, $kala-15, $komrow["kommentti"], $thispage, $pieni);

      //viivakoodi
      $data = viivakoodi(sprintf("%08.2f", $row['varattu_viivakoodi']), "code128", 100, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, $kala-13, 410, $thispage, array("scale" => 0.5));
      $pdf->draw_text(420, $kala-15, $row['varattu_viivakoodi'], $thispage, $pieni);

      $kala = $kala - 30;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_viivakoodi_lahete_ean13')) {
  function rivi_viivakoodi_lahete_ean13($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],                $thispage, $pieni);

    if (trim($row["perhe_kommentti1"]) != '') {
      $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;

      $plen = $pdf->strlen(t("Sis‰lt‰‰ tuotteet", $kieli).": ", $norm);
      $pdf->draw_text(45, $kala, t("Sis‰lt‰‰ tuotteet", $kieli).": ", $thispage, $norm);
      list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 485, $pdf, $norm);
      $pdf->draw_text($plen+45, $kala, $ff_string, $thispage, $ff_font);
      $kala -= $rivinkorkeus;
    }

    list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
    $pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

    $as_hinta = $row["hinta"] * generoi_alekentta_php($row, 'M', 'kerto');

    $oikpos = $pdf->strlen(hintapyoristys($as_hinta), $norm);
    $pdf->draw_text(475-$oikpos, $kala, hintapyoristys($as_hinta),       $thispage, $norm);

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

    $oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
    $pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks,   $thispage, $norm);

    $oikpos = $pdf->strlen($row["varattu"], $norm);
    $pdf->draw_text(575-$oikpos, $kala, $row["varattu"],                   $thispage, $norm);

    $alakala = $kala;

    // tarkista_ean13 on uusi, jos syˆtett‰v‰ EAN-koodi on viallinen, niin ei tehd‰ viivakoodi tulostusta PDF:lle
    if ($row["eankoodi"] != "" and $row["eankoodi"] != "0" and strlen($row["eankoodi"]) == "13" and is_numeric($row["eankoodi"]) and tarkista_ean13($row["eankoodi"]) !== FALSE) {
      //viivakoodi
      $data = viivakoodi($row["eankoodi"], "ean", 100, 10, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, $kala-25, 410, $thispage, array("scale" => 0.5));
      $pdf->draw_text(425, $kala-27, $row["eankoodi"], $thispage, $pieni);

      $alakala = $kala-27-$rivinkorkeus;
    }

    $pohja = $pdf->draw_paragraph($kala+$norm["height"]+1, 185, 70, 400, $row["nimitys"],  $thispage, $norm);

    $kala = $pohja - $rivinkorkeus;

    if ($row["kommentti"] != '') {

      $row["kommentti"] = wordwrap($row["kommentti"], 90, "\n");

      foreach (explode("\n", $row["kommentti"]) as $komm) {
        $pdf->draw_text(45, $kala, $komm, $thispage, $norm);
        $kala = $kala - $rivinkorkeus + 4;

        // Kun kokeillaan mahtuuko rivi sivulle, niin otetaan vain kommentin eka rivi
        if ($thispage == "TESTISIVU") break;

        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala <= 70) {
          $sivu++;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;
        }
      }

      $kala = $kala - 5;
    }

    if ($kala > $alakala) $kala = $alakala;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('loppu_lahete')) {
  function loppu_lahete($params) {
    global $kukarow, $yhtiorow;

    if (skippa_lahete_pdf($params)) {
      // Luodaan palautettavat
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $base_lahetetyyppi == "tulosta_lahete_hae_hinnat.inc") {
      // et‰yhtiˆn yhtiˆtiedot
      $y_puhelin = $GLOBALS['eta_yhtio_puhelin'];
      $y_fax = $GLOBALS['eta_yhtio_fax'];
      $y_email = $GLOBALS['eta_yhtio_email'];
      $y_www = $GLOBALS['eta_yhtio_email'];
      $y_nimi = $GLOBALS['eta_yhtio_nimi'];
      $y_osoite = $GLOBALS['eta_yhtio_osoite'];
      $y_postino = $GLOBALS['eta_yhtio_postino'];
      $y_postitp = $GLOBALS['eta_yhtio_postitp'];
      $y_maa = $GLOBALS['eta_yhtio_maa'];
      $y_kotipaikka = $GLOBALS['eta_yhtio_kotipaikka'];
      $y_vatnumero = preg_replace("/^0037/", "", $GLOBALS['eta_yhtio_ovttunnus']);
      $y_kotipaikka = $GLOBALS['eta_yhtio_kotipaikka'];
      $y_iban = "";
      $y_swift = "";
    }
    else {
      // yhtiˆtiedot
      $y_tiedot_ok = FALSE;
      $y_nimi = $laskurow["yhtio_nimi"];
      $y_osoite = $laskurow["yhtio_osoite"];
      $y_postino = $laskurow["yhtio_postino"];
      $y_postitp = $laskurow["yhtio_postitp"];
      $y_maa = $laskurow["yhtio_maa"];
      $y_kotipaikka = $laskurow["yhtio_kotipaikka"];
      $y_vatnumero = preg_replace("/^0037/", "", $laskurow["yhtio_ovttunnus"]);
      $y_iban = $yhtiorow['pankkiiban1'];
      $y_swift = $yhtiorow['pankkiswift1'];

      if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT
                   puhelin,
                   fax,
                   email,
                   www,
                   vat_numero
                   FROM yhtion_toimipaikat
                   WHERE yhtio='$laskurow[yhtio]'
                   AND tunnus='$laskurow[yhtio_toimipaikka]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_tiedot_ok = TRUE;
          $y_puhelin = $alhiro['puhelin'];
          $y_fax = $alhiro['fax'];
          $y_email = $alhiro['email'];
          $y_www = $alhiro['www'];

          if (!empty($alhiro['vat_numero'])) {
            $y_vatnumero = $alhiro['vat_numero'];
          }
        }
      }

      // jos meill‰ on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
      if ($laskurow["yhtio_ovttunnus"] != "" and $laskurow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_tiedot_ok = TRUE;
          $y_puhelin = $alhiro['puhelin'];
          $y_fax = $alhiro['fax'];
          $y_email = $alhiro['email'];
          $y_www = $alhiro['www'];
        }
      }

      // jos ei olla haettu muualta, niin haetaan tiedot yhtiˆlt‰
      if (!$y_tiedot_ok) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$laskurow[yhtio]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_puhelin = $alhiro['puhelin'];
          $y_fax = $alhiro['fax'];
          $y_email = $alhiro['email'];
          $y_www = $alhiro['www'];
        }
      }
    }

    if ($yhtiorow['lomakkeiden_allekirjoitus'] == 'X') {
      $allekirjoitus_query = "SELECT nimi,
                              asema
                              FROM kuka
                              WHERE yhtio = '{$kukarow['yhtio']}'
                              AND tunnus  = '{$laskurow['allekirjoittaja']}'";
      $allekirjoitus_result = pupe_query($allekirjoitus_query);
      $allekirjoitus_row = mysql_fetch_assoc($allekirjoitus_result);

      $pdf->draw_text(40, 95, $allekirjoitus_row['nimi'], $thispage, $norm);
      $pdf->draw_text(40, 85, $allekirjoitus_row['asema'], $thispage, $norm);
    }

    $maan_tiedot = hae_maa(array('maakoodi' => $y_maa));
    $yhteystiedot_teksti = "{$y_nimi}, {$y_osoite}, {$y_postino} {$y_postitp}, {$maan_tiedot['nimi']}";
    $yhteystiedot_len = $pdf->strlen($yhteystiedot_teksti, $pieni);
    $yhteystiedot_len_www = $pdf->strlen($y_www, $pieni);


    $query  = "SELECT pankkiyhteystiedot.*, maksuehto.*
               FROM maksuehto
               LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio = maksuehto.yhtio and pankkiyhteystiedot.tunnus = maksuehto.pankkiyhteystiedot)
               WHERE maksuehto.yhtio = '$kukarow[yhtio]'
               and maksuehto.tunnus  = '{$laskurow["maksuehto"]}'";
    $result = pupe_query($query);
    $masrow = mysql_fetch_assoc($result);

    if ($masrow["pankkinimi1"] != "") {
      $y_iban  = $masrow["pankkiiban1"];
      $y_swift = $masrow["pankkiswift1"];
    }

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, $yhteystiedot_teksti, $thispage, $pieni);
      $pdf->draw_text((595 / 2) - ($yhteystiedot_len_www / 2), 42, $y_www, $thispage, $pieni);
    }
    else {
      //Alimmat kolme laatikkoa, yhtiˆtietoja
      $pdf->draw_rectangle(70, 20, 20, 580,  $thispage, $rectparam);
      $pdf->draw_rectangle(70, 207, 20, 580,  $thispage, $rectparam);
      $pdf->draw_rectangle(70, 394, 20, 580,  $thispage, $rectparam);

      $pdf->draw_text(30, 55, $y_nimi, $thispage, $pieni);
      $pdf->draw_text(30, 45, $y_osoite, $thispage, $pieni);
      $pdf->draw_text(30, 35, "{$y_postino}  {$y_postitp}", $thispage, $pieni);
      $pdf->draw_text(30, 25, $maan_tiedot['nimi'], $thispage, $pieni);

      $pdf->draw_text(217, 55, t("Puhelin", $kieli).":", $thispage, $pieni);
      $pdf->draw_text(257, 55, $y_puhelin, $thispage, $pieni);
      $pdf->draw_text(217, 45, t("S‰hkˆposti", $kieli).":", $thispage, $pieni);
      $pdf->draw_text(257, 45, $y_email, $thispage, $pieni);
      $pdf->draw_text(217, 35, t("Web", $kieli).":", $thispage, $pieni);
      $pdf->draw_text(257, 35, $y_www, $thispage, $pieni);

      $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":", $thispage, $pieni);
      if ($yhtiorow["maa"] == 'EE') {
        $pdf->draw_text(444, 55, tulosta_ytunnus($yhtiorow["ytunnus"], $yhtiorow["maa"], $laskurow["vienti"]), $thispage, $pieni);
      }
      else {
        $pdf->draw_text(444, 55, tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $thispage, $pieni);
      }
      $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":", $thispage, $pieni);
      $pdf->draw_text(444, 45, $y_kotipaikka, $thispage, $pieni);
      $pdf->draw_text(404, 35, t("Alv.rek", $kieli), $thispage, $pieni);
    }

    if ($tots == 1 and $summa != 0) {

      if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {

        // Haetaan maksuehdon tiedot
        $query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
        $masresult = pupe_query($query);
        $masrow = mysql_fetch_assoc($masresult);

        $kala -= 10;
        $ed_sivu = $sivu;

        // Luodaan palautettavat
        $return = compact(array_keys($params));

        $params = alvierittely_lahete($return);

        // Luodaan muuttujat
        extract($params);

        // Alvierittely kassa-alennuksella
        if ($masrow['kassa_alepros'] > 0) {

          $kassa_ale = $masrow['kassa_alepros'];
          $kala -= 10;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $params = alvierittely_lahete($return);

          // Luodaan muuttujat
          extract($params);
        }

        if ($sivu != $ed_sivu) {
          $pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, $yhteystiedot_teksti, $thispage, $pieni);
          $pdf->draw_text((595 / 2) - ($yhteystiedot_len_www / 2), 42, $y_www, $thispage, $pieni);
        }

        if ($kala-90 <= 100) {

          $sivu++;

          $ei_otsikoita = "EI";
          $tots = 0;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;

          $ei_otsikoita = "";
          $tots = 1;

          $pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, $yhteystiedot_teksti, $thispage, $pieni);
          $pdf->draw_text((595 / 2) - ($yhteystiedot_len_www / 2), 42, $y_www, $thispage, $pieni);

          $kala += 36;
        }

        $pdf->draw_text(50, $kala-25, t("Toimitustapa", $kieli), $thispage, $boldi);
        $pdf->draw_text(200, $kala-25, t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli), $thispage, $norm);

        $pdf->draw_text(50,  $kala-38, t("Maksuehto", $kieli), $thispage, $boldi);
        $pdf->draw_text(200, $kala-38, t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli)." ".t("tai erillisen maksupostiohjelman mukaan", $kieli), $thispage, $norm);

        $pdf->draw_text(50,  $kala-51, t("Myynti- ja toimitusehdot", $kieli), $thispage, $boldi);
        $pdf->draw_text(200, $kala-51, $yhtiorow['nimi'].t(":n yleiset kauppaehdot", $kieli)." 30.6.2014", $thispage, $norm);
        $pdf->draw_text(200, $kala-61, $yhtiorow['nimi'].':n '.t("tilaajavastuulain mukaiset asiakirjat lˆyd‰t www.tilaajavastuulaki.fi", $kieli), $thispage, $norm);

        $pdf->draw_text(50, $kala-71, t("Salassapito", $kieli), $thispage, $boldi);
        $pdf->draw_text(200, $kala-71, t("T‰m‰ asiakirja liitteineen on luottamuksellinen eik‰ sit‰ saa ilman ", $kieli).$yhtiorow['nimi'].':n ', $thispage, $norm);
        $pdf->draw_text(200, $kala-81, t("lupaa n‰ytt‰‰ kolmannelle osapuolelle.", $kieli), $thispage, $norm);
        // Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
        if ($kala-253 <= 58) {
          $sivu++;

          $ei_otsikoita = "EI";
          $tots = 0;

          // Luodaan palautettavat
          $return = compact(array_keys($params));

          $return = loppu_lahete($return);
          $params = uusi_sivu_lahete($return);

          // Luodaan muuttujat
          extract($params);

          $page[$sivu] = $thispage;

          $ei_otsikoita = "";
          $tots = 1;

          $pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, $yhteystiedot_teksti, $thispage, $pieni);
          $pdf->draw_text((595 / 2) - ($yhteystiedot_len_www / 2), 42, $y_www, $thispage, $pieni);

          $kala += 90;
        }

        $pdf->draw_text(50, $kala-120, t("Sopimusasiakirjojen etusijaj‰rjestys on seuraava", $kieli).':', $thispage, $pieni);
        $pdf->draw_text(50, $kala-130, '1. '.t("Tilausvahvistus", $kieli), $thispage, $pieni);
        $pdf->draw_text(50, $kala-140, '2. '.t("Allekirjoitettu tarjous", $kieli), $thispage, $pieni);
        $pdf->draw_text(50, $kala-150, "3. $yhtiorow[nimi]".t(":n yleiset kauppaehdot", $kieli)." 30.6.2014", $thispage, $pieni);
        $pdf->draw_text(50, $kala-160, "4. ".t("Rakennustuotteiden yleisi‰ hankinta- ja toimitusehtoja (RYHT 2000) ja betonielementtien asennusehdot", $kieli).'.', $thispage, $pieni);

        $pdf->draw_text(50, $kala-180, t("Olen tutustunut", $kieli)." $yhtiorow[nimi]".t(":n yleisiin sopimusehtoihin ja hyv‰ksyn ne sellaisenaan", $kieli).'.', $thispage, $pieni);

        $xkoord[0] = 50;
        $xkoord[1] = 180;
        $ykoord[0] = $ykoord[1] = $kala-210;
        $pdf->draw_line($xkoord, $ykoord, $thispage, $rectparam);

        //etsit‰‰n myyj‰n nimi
        $query  = "SELECT nimi, kuka
                   from kuka
                   where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
        $myyresult = pupe_query($query);
        $myyrow = mysql_fetch_assoc($myyresult);

        $pdf->draw_text(50, $kala-223, $myyrow['nimi'], $thispage, $norm);
        $pdf->draw_text(50, $kala-243, t("Myyj‰n allekirjoitus", $kieli), $thispage, $boldi);

        $xkoord[0] = 330;
        $xkoord[1] = 550;
        $ykoord[0] = $ykoord[1] = $kala-210;
        $pdf->draw_line($xkoord, $ykoord, $thispage, $rectparam);

        $xkoord[0] = 330;
        $xkoord[1] = 550;
        $ykoord[0] = $ykoord[1] = $kala-230;
        $pdf->draw_line($xkoord, $ykoord, $thispage, $rectparam);
        $pdf->draw_text(330, $kala-243, t("Ostajan allekirjoitus ja nimenselvennys", $kieli), $thispage, $boldi);
      }
      elseif ($tyyppi == 'TILAUSVAHVISTUS' and strpos($laskurow['tilausvahvistus'], 'A') !== FALSE) {
        $kala -= 10;
        $ed_sivu = $sivu;

        // Luodaan palautettavat
        $return = compact(array_keys($params));

        $params = alvierittely_lahete($return);

        // Luodaan muuttujat
        extract($params);
      }
      else {
        // Verolliset hinnat yhtiˆnparametreist‰ tai l‰hetetyypist‰
        if (($laskurow['alv'] > 0 and $yhtiorow["alv_kasittely"] == '') or strpos($lahetetyyppi, "_verollinen")) {
          $pdf->draw_text(410, 72, t("Yhteens‰ verollinen", $kieli).":", $thispage, $norm);
          $oikpos = $pdf->strlen(sprintf("%.2f", $summa)." ".$laskurow["valkoodi"], $norm);
          $pdf->draw_text(575-$oikpos, 72, sprintf("%.2f", $summa)." ".$laskurow["valkoodi"], $thispage, $norm);
        }
        // Verottomat hinnat
        else {
          $pdf->draw_text(410, 72, t("Yhteens‰ veroton", $kieli).":", $thispage, $norm);
          $oikpos = $pdf->strlen(sprintf("%.2f", $arvo)." ".$laskurow["valkoodi"], $norm);
          $pdf->draw_text(575-$oikpos, 72, sprintf("%.2f", $arvo)." ".$laskurow["valkoodi"], $thispage, $norm);
        }
      }
    }

    if ($tots == 1 and $yhtiorow["naytayhteensamaarat"] == "K") {
      $pdf->draw_text(250, 72, t("Tuotteita yhteens‰", $kieli).": ".(float) sprintf("%.2f", $yhteensamaara), $thispage, $norm);
    }

    if ($tots != 1) {
      $pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", $thispage, $boldi);
    }

    if ($tots == 1) {
      if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
        for ($pp=1; $pp<=$sivu; $pp++) {
          $pdf->draw_text(555, 803, "$pp / $sivu", $page[$pp], $pieni);
        }
      }
      else {
        for ($pp=1; $pp<=$sivu; $pp++) {
          $pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
        }
      }
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('alvierittely_lahete')) {
  function alvierittely_lahete($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    $sivu_vaihtui = FALSE;

    if ($kala <= 150) {
      $sivu++;

      $ei_otsikoita = "EI";
      $tots = 0;

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      $return = loppu_lahete($return);
      $params = uusi_sivu_lahete($return);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;

      $ei_otsikoita = "";
      $sivu_vaihtui = TRUE;
      $tots = 1;
    }


    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $vasen1 = 150;
      $vasen2 = 470;
    }
    else {
      $vasen1 = 365;
      $vasen2 = 555;
    }

    $kassa_ale_lisa = '';
    $kassa_ale_lisateksti = '';

    if ($kassa_ale != '') {
      $kassa_ale = (float) $kassa_ale;
      $kassa_ale_lisa = " * (1-($kassa_ale/100)) ";

      $kala-=10;
      $pdf->draw_text($vasen1, $kala, t("Kassa-alennuksella", $kieli),       $thispage, $boldi);
      $kala-=10;
    }

    // loppusummat vain vikalle sivulle
    $pdf->draw_text($vasen1, $kala, t("Veroton arvo yhteens‰", $kieli).":",     $thispage, $boldi);

    if ($laskurow["arvo"] != 0) {
      if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $arvo = $kassa_ale != '' ? $laskurow["arvo_valuutassa"] * (1-$kassa_ale/100) : $laskurow["arvo_valuutassa"];
      }
      else {
        $arvo = $kassa_ale != '' ? $laskurow["arvo"] * (1-$kassa_ale/100) : $laskurow["arvo"];
      }
    }
    else {
      // T‰m‰ on aina valuutassa
      $arvo = $kassa_ale != '' ? $arvo * (1-$kassa_ale/100) : $arvo;
    }

    $oikpos = $pdf->strlen(sprintf('%.2f', $arvo), $norm);
    $pdf->draw_text($vasen2-$oikpos, $kala, sprintf('%.2f', $arvo), $thispage, $boldi);
    $pdf->draw_text($vasen2+3, $kala, $laskurow["valkoodi"], $thispage, $boldi);

    $jtlisa = "";

    if ($base_lahetetyyppi == "tulosta_lahete_custom.inc") {
      $jtlisa = "+tilausrivi.jt";
      $tyyppilisa = " and tilausrivi.tyyppi in ('L','T') ";
    }
    else {
      if ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE) {
        $tyyppilisa = " and tilausrivi.tyyppi in ('L','T') and tilausrivi.var not in ('P','O') ";
      }
      elseif ($tyyppi == "TILAUSVAHVISTUS") {
        $tyyppilisa = " and tilausrivi.tyyppi in ('L','T') and tilausrivi.var not in ('P','J','O','S') ";
      }
      else {
        $tyyppilisa = " and tilausrivi.tyyppi = 'L' and tilausrivi.var not in ('P','J','O','S') ";
      }
    }

    // Haetaan kaikki alvikannat riveilt‰
    $alvquery = "SELECT distinct alv
                 FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 and yhtio     = '$kukarow[yhtio]'
                 $tyyppilisa
                 ORDER BY alv";
    $alvresult = pupe_query($alvquery);
    $kala -= 10;

    $veron_maara = 0;

    $query_ale_lisa = generoi_alekentta('M');

    while ($alvrow = mysql_fetch_assoc($alvresult)) {

      // Valuuttajutut kuntoon
      if ($alvrow["alv"] >= 500 and $laskurow["arvo"] != 0) {
        $aquery = "SELECT
                   sum(0) alvrivihinta,
                   sum(round(tilausrivi.rivihinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1),2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' $tyyppilisa";
      }
      elseif ($alvrow["alv"] >= 500) {
        $aquery = "SELECT
                   sum(0) alvrivihinta,
                   sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) * tilausrivi.varattu$jtlisa * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' $tyyppilisa";
      }
      elseif ($alvrow["alv"] < 500 and $laskurow["arvo"] != 0 and trim(strtoupper($laskurow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
        $aquery = "SELECT
                   round(sum(tilausrivi.rivihinta $kassa_ale_lisa * (tilausrivi.alv / 100)),2) alvrivihinta,
                   sum(tilausrivi.rivihinta $kassa_ale_lisa) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' $tyyppilisa";
      }
      elseif ($alvrow["alv"] < 500 and $laskurow["arvo"] != 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $aquery = "SELECT
                   round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                   sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' $tyyppilisa";
      }
      else {
        $aquery = "SELECT
                   round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu$jtlisa+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                   sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu$jtlisa+tilausrivi.kpl) * {$query_ale_lisa},2)) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' $tyyppilisa";
      }
      $aresult = pupe_query($aquery);
      $arow = mysql_fetch_assoc($aresult);

      $veron_maara += $arow['alvrivihinta'];

      $pdf->draw_text($vasen1, $kala, t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f', $arow["rivihinta"])."): ",  $thispage, $boldi);
      $oikpos = $pdf->strlen(sprintf('%.2f', $arow["alvrivihinta"]), $boldi);
      $pdf->draw_text($vasen2-$oikpos, $kala, sprintf('%.2f', $arow["alvrivihinta"]),     $thispage, $boldi);

      if ($alvrow["alv"] >= 600) {
        $pdf->draw_text($vasen2+3, $kala, t("K.V.", $kieli),                   $thispage, $boldi);
      }
      elseif ($alvrow["alv"] >= 500) {
        $pdf->draw_text($vasen2+3, $kala, t("M.V.", $kieli),                   $thispage, $boldi);
      }
      else {
        $pdf->draw_text($vasen2+3, $kala, ($alvrow["alv"]*1)."%",               $thispage, $boldi);
      }

      $kala -= 10;
    }

    if ($laskurow["arvo"] != 0) {
      if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $arvo = $kassa_ale != '' ? $laskurow["summa_valuutassa"] * (1-$kassa_ale/100) : $laskurow["summa_valuutassa"];
      }
      else {
        $summa = $kassa_ale != '' ? $laskurow["summa"] * (1-$kassa_ale/100) : $laskurow["summa"];
      }
    }
    else {
      // T‰m‰ on aina valuutassa
      $summa = $kassa_ale != '' ? $summa * (1-$kassa_ale/100) : $summa;
    }

    $pdf->draw_text($vasen1, $kala, t("Verollinen arvo yhteens‰", $kieli).":", $thispage, $boldi);

    $oikpos = $pdf->strlen(sprintf('%.2f', $summa), $boldi);
    $pdf->draw_text($vasen2-$oikpos, $kala, sprintf('%.2f', $summa), $thispage, $boldi);
    $pdf->draw_text($vasen2+3, $kala, $laskurow['valkoodi'], $thispage, $boldi);

    $kala -= 15;

    $pdf->draw_text($vasen1, $kala, t("Yhteens‰", $kieli).":", $thispage, $boldi);

    $oikpos = $pdf->strlen(sprintf('%.2f', $summa), $boldi);
    $pdf->draw_text($vasen2-$oikpos, $kala, sprintf('%.2f', $summa),             $thispage, $boldi);
    $pdf->draw_text($vasen2+3, $kala, $laskurow["valkoodi"],                   $thispage, $boldi);

    if ($laskurow["pyoristys"] != 0) {

      if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];
      }
      else {
        $pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];
      }

      $kala -= 10;
      $pdf->draw_text($vasen1, $kala, t("Pyˆristys", $kieli).":",              $thispage, $boldi);
      $oikpos = $pdf->strlen(sprintf('%.2f', $pyoristys * -1), $boldi);
      $pdf->draw_text($vasen2-$oikpos, $kala, sprintf('%.2f', $pyoristys * -1),       $thispage, $boldi);
      $pdf->draw_text($vasen2+3, $kala, $laskurow["valkoodi"],                 $thispage, $boldi);
      $kala -= 10;

      $pdf->draw_text($vasen1, $kala, t("Yhteens‰", $kieli).":",              $thispage, $boldi);
      $oikpos = $pdf->strlen(sprintf('%.2f', $summa - $pyoristys), $boldi);
      $pdf->draw_text($vasen2-$oikpos, $kala, sprintf('%.2f', $summa - $pyoristys),     $thispage, $boldi);
      $pdf->draw_text($vasen2+3, $kala, $laskurow["valkoodi"],                 $thispage, $boldi);
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('kuittaus_lahete')) {
  function kuittaus_lahete($params) {
    global $kukarow, $yhtiorow;

    if (skippa_lahete_pdf($params)) {
      // Luodaan palautettavat
      return $params;
    }

    //katotaan onko laskutus nouto
    $query = "SELECT toimitustapa.nouto, maksuehto.kateinen
              FROM lasku
              JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
              JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
              WHERE lasku.yhtio       = '$kukarow[yhtio]' and lasku.tunnus = '{$params['laskurow']['tunnus']}'
              and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
    $kures = pupe_query($query);

    //toimitustapa on nouto ja lahete_allekirjoitus Tulostetaan erillinen allekirjoitussivu vain kun toimitustapa on nouto
    if (mysql_num_rows($kures) > 0 and $yhtiorow['lahete_allekirjoitus'] == 'K') {
      $params["sivu"]++;
      $params["ei_otsikoita"] = "EI";

      $params = alku_lahete($params);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;

      $pdf->draw_text(50, 400, t("Allekirjoitus", $kieli).": _______________________________",   $thispage, $norm);
      $pdf->draw_text(300, 400, t("Nimenselvennys", $kieli).": _______________________________",   $thispage, $norm);

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      return $return;
    }
    //toimitustapa on nouto ja lahete_allekirjoitus Tulostetaan allekirjoitus samalle sivulle vain kun toimitustapa on nouto
    elseif (mysql_num_rows($kures) > 0 and $yhtiorow['lahete_allekirjoitus'] == 'A') {
      // Luodaan muuttujat
      extract($params);

      if ($kala < 90 ) {
        // Luodaan palautettavat
        $return = compact(array_keys($params));

        $params = uusi_sivu_lahete($return);

        $params = loppu_lahete($params);

        // Luodaan muuttujat
        extract($params);
      }

      $pdf->draw_text(50, 90, t("Allekirjoitus", $kieli).": _______________________________",   $thispage, $norm);
      $pdf->draw_text(300, 90, t("Nimenselvennys", $kieli).": _______________________________",   $thispage, $norm);

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      return $return;
    }
    //toimitustavalla ei v‰li‰ lahete_allekirjoitus Tulostetaan erillinen allekirjoitussivu kaikilla toimitustavoilla
    elseif ($yhtiorow['lahete_allekirjoitus'] == 'E') {
      $params["sivu"]++;
      $params["ei_otsikoita"] = "EI";

      $params = alku_lahete($params);

      // Luodaan muuttujat
      extract($params);

      $page[$sivu] = $thispage;

      $pdf->draw_text(50, 400, t("Allekirjoitus", $kieli).": _______________________________",   $thispage, $norm);
      $pdf->draw_text(300, 400, t("Nimenselvennys", $kieli).": _______________________________",   $thispage, $norm);

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      return $return;
    }
    //toimitustavalla ei v‰li‰ lahete_allekirjoitus Tulostetaan allekirjoitussivu samalle sivulle kaikilla toimitustavoilla
    elseif ($yhtiorow['lahete_allekirjoitus'] == 'S') {
      // Luodaan muuttujat
      extract($params);

      if ($kala < 90 ) {
        // Luodaan palautettavat
        $return = compact(array_keys($params));

        $params = uusi_sivu_lahete($return);

        $params = loppu_lahete($params);

        // Luodaan muuttujat
        extract($params);
      }

      $pdf->draw_text(50, 90, t("Allekirjoitus", $kieli).": _______________________________",   $thispage, $norm);
      $pdf->draw_text(300, 90, t("Nimenselvennys", $kieli).": _______________________________",   $thispage, $norm);

      // Luodaan palautettavat
      $return = compact(array_keys($params));

      return $return;
    }
    else {
      return $params;
    }
  }
}

if (!function_exists('print_pdf_lahete')) {
  function print_pdf_lahete($params) {
    global $kukarow, $yhtiorow, $palvelin2;

    // Luodaan muuttujat
    extract($params);

    $base_lahetetyyppi = hae_base_lahetetyyppi($lahetetyyppi);

    $oslapp = '';
    $returnvalue = 0;

    if (!skippa_lahete_pdf($params)) {
      //keksit‰‰n uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdffilenimi, "w");
      if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
      fclose($fh);
    }

    if ($tee == 'NAYTATILAUS') {
      //Tyˆnnet‰‰n tuo pdf vaan putkeen!
      echo file_get_contents($pdffilenimi);
    }
    else {
      if (!is_array($komento)) {
        $komentoulos = array($komento);
      }
      else {
        $komentoulos = $komento;
      }

      foreach ($komentoulos as $komento) {
        // itse print komento...
        if ($komento == "-88") {
          js_openFormInNewWindow();

          echo "<br><form id='tulostakopioform_lahete_{$laskurow['tunnus']}' name='tulostakopioform_lahete_{$laskurow['tunnus']}' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
                <input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>
                <input type='hidden' name='toim' value='LAHETE'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='submit' value='".t("L‰hete").": {$laskurow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_lahete_{$laskurow['tunnus']}', ''); return false;\"></form><br>";
        }
        elseif ($komento == 'email' or substr($komento, 0, 12) == 'asiakasemail') {
          $liite = $pdffilenimi;
          $content_body = "";

          if (substr($komento, 0, 12) == 'asiakasemail') {
            $sahkoposti_to = substr($komento, 12);
          }
          else {
            $sahkoposti_to = $kukarow["eposti"];
          }

          if ($tyyppi == "TILAUSVAHVISTUS") {

            if (php_sapi_name() != 'cli') {
              echo t("Tilausvahvistus tulostuu")."...<br>";
            }

            switch ($yhtiorow["tilausvahvistus_tilausnumero"]) {
            case 1:
              if (empty($laskurow["asiakkaan_tilausnumero"])) {
                $tilausnumero = $laskurow["tunnus"];
              }
              else {
                $tilausnumero = $laskurow["asiakkaan_tilausnumero"];
              }
              break;
            default:
              $tilausnumero = $laskurow["tunnus"];
            }

            if ($laskurow["tunnusnippu"] > 0) {
              if ($tyyppi == "YLLAPITOSOPIMUS" or $laskurow["nippu"] > 0) {
                $subjnumero = $laskurow["tunnusnippu"];
              }
              else {
                $subjnumero = $laskurow["tunnusnippu"] . "/" . $tilausnumero;
              }
            }
            else {
              $subjnumero = $tilausnumero;
            }

            $kutsu = t("Tilausvahvistus", $kieli)." ".$subjnumero;

            $content_body = t("Kiitos tilauksestanne", $kieli).",\n\n";
            $content_body .= t("Tilausnumeronne on", $kieli)." $subjnumero\n\n";
            $content_body .= t("T‰m‰ on automaattinen vahvistus tilauksestanne, t‰h‰n s‰hkˆpostiin ei tarvitse vastata.", $kieli)."\n\n";
            $content_body .= t("Yst‰v‰llisin terveisin", $kieli).",\n";
            $content_body .= $yhtiorow["nimi"]."\n";
          }
          elseif ($tyyppi == "YLLAPITOSOPIMUS") {
            echo t("Yll‰pitosopimus tulostuu")."...<br>";

            $kutsu = t("Yll‰pitosopimus", $kieli)." ".$laskurow["tunnus"];
          }
          else {
            echo t("L‰hete tulostuu")."...<br>";

            $kutsu = t("L‰hete", $kieli)." ".$laskurow["tunnus"];

            if (isset($koontilahete) and $koontilahete != 0) {
              $content_body .= t("Tilausnumero", $kieli).": ";
              $content_body .= array_shift(explode(",", $koontilahete))."\n\n";
            }
            elseif ($laskurow["tunnusnippu"] > 0) {
              $content_body .= t("Tilausnumero/toimitus", $kieli).": ";
              $content_body .= $laskurow["tunnusnippu"]." / ".$laskurow["tunnus"].".\n\n";
            }
            elseif ($laskurow["vanhatunnus"] > 0 and $laskurow["vanhatunnus"] != $laskurow["tunnus"]) {
              $content_body .= t("Tilausnumero/toimitus", $kieli).": ";
              $content_body .= $laskurow["vanhatunnus"]." / ".$laskurow["tunnus"].".\n\n";
            }
            else {
              $content_body .= t("Tilausnumero", $kieli).": ";
              $content_body .= $laskurow["tunnus"].".\n\n";
            }

            $content_body .= t("Yst‰v‰llisin terveisin", $kieli).",\n";
            $content_body .= $yhtiorow["nimi"]."\n";
          }

          $newfilename = str_replace(" ", "_", $kutsu);

          if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
            $kutsu .= ", ".trim($laskurow["nimi"]);
          }

          // Jos tilausvahvistuksessa on R, halutaan rekkarinumeroita otsikkoon
          /*
          Tsekataan rekisteritieto kentist‰ distinct rekkarit ja kirjoitetaan muutama niist‰ emailin subjektiin ihan loppuun sulkujen sis‰‰n.
          Muutama = 2, ja jos on enemm‰nkin rekkareita niin sitten niist‰ info tyyliin n‰in: (ABC-123, EFG-456, ..). Ja jos esim yksi niin: (ABC-123).
          */
          $kutsu2 = "";

          if (strpos($laskurow['tilausvahvistus'], 'R') !== FALSE) {

            $rekkarit_subject = array_slice(array_keys(hae_tilauksen_rekisterinumerot($laskurow['tunnus'], true)), 0, 3);

            if (count($rekkarit_subject) > 0) {

              if (isset($rekkarit_subject[2])) $rekkarit_subject[2] = '...';

              $kutsu2 = "(".implode(", ", $rekkarit_subject).") ";
            }
          }

          $kutsu = "$kutsu2{$yhtiorow["nimi"]} - $kutsu";

          // S‰hkˆpostin l‰hetykseen parametrit
          $parametri = array( "to"       => $sahkoposti_to,
            "cc"       => "",
            "subject"    => $kutsu,
            "ctype"      => "text",
            "body"      => $content_body,
            "attachements"  => array(0   => array(
                "filename"    => $liite,
                "newfilename"  => $newfilename.".pdf",
                "ctype"      => "pdf"),
            )
          );

          pupesoft_sahkoposti($parametri);
        }
        elseif ($komento != '' and $komento != 'edi') {
          echo t("L‰hete tulostuu")."...<br>";

          if ($lahetekpl > 1) {
            $komento .= " -#$lahetekpl ";
          }

          $line = exec("$komento $pdffilenimi", $output, $returnvalue);
        }
      }
    }
  }
}

if (!function_exists('hae_base_lahetetyyppi')) {
  function hae_base_lahetetyyppi($lahetetyyppi) {
    $base_lahetetyyppi = str_replace('perhe_', '', $lahetetyyppi);
    $base_lahetetyyppi = str_replace('hinnat_isalla_', '', $base_lahetetyyppi);
    $base_lahetetyyppi = str_replace('_verollinen', '', $base_lahetetyyppi);
    $base_lahetetyyppi = str_replace('_eipuute', '', $base_lahetetyyppi);

    return $base_lahetetyyppi;
  }
}

if (!function_exists('skippa_lahete_pdf')) {
  function skippa_lahete_pdf($params) {

    extract($params);

    if (!is_array($komento)) {
      $komentoulos = array($komento);
    }
    else {
      $komentoulos = $komento;
    }

    if (count($komentoulos) == 1 and $komentoulos[0] == "-88") {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
