<?php

if (!function_exists("uusi_karhukierros")) {
  function uusi_karhukierros($yhtio) {
    $query = "SELECT tunnus
              FROM karhukierros
              where pvm  = current_date
              and yhtio  = '$yhtio'
              and tyyppi = ''";
    $result = pupe_query($query);

    if (!mysql_num_rows($result)) {
      $query = "INSERT INTO karhukierros (pvm,yhtio) values (current_date,'$yhtio')";
      $result = pupe_query($query);
      $uusid = mysql_insert_id($GLOBALS["masterlink"]);
    }
    else {
      $row = mysql_fetch_assoc($result);
      $uusid = $row["tunnus"];
    }

    return $uusid;
  }
}

if (!function_exists("liita_lasku")) {
  function liita_lasku($ktunnus, $ltunnus) {
    $query = "INSERT INTO karhu_lasku (ktunnus,ltunnus) values ($ktunnus,$ltunnus)";
    $result = pupe_query($query);
  }
}

if (!function_exists("alku")) {
  function alku($viesti = null, $karhukierros_tunnus = '') {
    global $pdf, $asiakastiedot, $yhteyshenkilo, $yhteyshenkiloteksti, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $boldi, $kaatosumma, $kieli, $_POST, $iso;

    $firstpage = $pdf->new_page("a4");

    if ($yhteyshenkilo == "") {
      $yhteyshenkilo = $kukarow["tunnus"];
    }

    //Haetaan yhteyshenkilon tiedot
    $apuqu = "SELECT *
              from kuka
              where yhtio='$kukarow[yhtio]' and tunnus='$yhteyshenkilo'";
    $yres = pupe_query($apuqu);
    $yrow = mysql_fetch_assoc($yres);

    //Otsikko
    $pdf->draw_text(310, 815, t("Maksukehotus", $kieli), $firstpage, $iso);
    $pdf->draw_text(490, 815, t("Sivu", $kieli)." ".$sivu,   $firstpage, $norm);

    tulosta_logo_pdf($pdf, $firstpage, "");

    if (isset($_POST['ekirje_laheta']) === false) {
      //Vasen sarake
      //$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
      $pdf->draw_text(50, 729, t("Laskutusosoite", $kieli),   $firstpage, $pieni);

      if ($asiakastiedot["laskutus_nimi"] != "" and (
        ($asiakastiedot["maksukehotuksen_osoitetiedot"] == "B" or ($yhtiorow["maksukehotuksen_osoitetiedot"] == "K" and $asiakastiedot["maksukehotuksen_osoitetiedot"] == "")) or
        ($asiakastiedot["maksukehotuksen_osoitetiedot"] == "C" or ($yhtiorow["maksukehotuksen_osoitetiedot"] == "C" and $asiakastiedot["maksukehotuksen_osoitetiedot"] == ""))
        )) {

        $pdf->draw_text(50, 717, $asiakastiedot["laskutus_nimi"],     $firstpage, $norm);
        $pdf->draw_text(50, 707, $asiakastiedot["laskutus_nimitark"],   $firstpage, $norm);
        $pdf->draw_text(50, 697, $asiakastiedot["laskutus_osoite"],   $firstpage, $norm);
        $pdf->draw_text(50, 687, $asiakastiedot["laskutus_postino"]." ".$asiakastiedot["laskutus_postitp"], $firstpage, $norm);
        $pdf->draw_text(50, 677, $asiakastiedot["laskutus_maa"],     $firstpage, $norm);

        if (($asiakastiedot["maksukehotuksen_osoitetiedot"] == "C" or ($yhtiorow["maksukehotuksen_osoitetiedot"] == "C" and $asiakastiedot["maksukehotuksen_osoitetiedot"] == "")) and
          $asiakastiedot["laskutus_nimi"].$asiakastiedot["laskutus_osoite"].$asiakastiedot["laskutus_postino"].$asiakastiedot["laskutus_postitp"].$asiakastiedot["laskutus_maa"] != $asiakastiedot["nimi"].$asiakastiedot["osoite"].$asiakastiedot["postino"].$asiakastiedot["postitp"].$asiakastiedot["maa"]
          ) {

          $pdf->draw_text(50, 654, t("Ostaja", $kieli), $firstpage, $pieni);
          $pdf->draw_text(50, 644, $asiakastiedot["nimi"],     $firstpage, $norm);
          $pdf->draw_text(50, 634, $asiakastiedot["nimitark"],   $firstpage, $norm);
          $pdf->draw_text(50, 624, $asiakastiedot["osoite"],     $firstpage, $norm);
          $pdf->draw_text(50, 614, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
          $pdf->draw_text(50, 604, $asiakastiedot["maa"],     $firstpage, $norm);
        }
      }
      else {
        $pdf->draw_text(50, 717, $asiakastiedot["nimi"],     $firstpage, $norm);
        $pdf->draw_text(50, 707, $asiakastiedot["nimitark"],   $firstpage, $norm);
        $pdf->draw_text(50, 697, $asiakastiedot["osoite"],     $firstpage, $norm);
        $pdf->draw_text(50, 687, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
        $pdf->draw_text(50, 677, $asiakastiedot["maa"],     $firstpage, $norm);
      }
    }
    else {
      // lähettäjä
      $iiso = array('height' => 11, 'font' => 'Times-Roman');
      $pdf->draw_text(mm_pt(22), mm_pt(268), strtoupper($yhtiorow["nimi"]),     $firstpage, $iiso);
      $pdf->draw_text(mm_pt(22), mm_pt(264), strtoupper($yhtiorow["nimitark"]),  $firstpage, $iiso);
      $pdf->draw_text(mm_pt(22), mm_pt(260), strtoupper($yhtiorow["osoite"]),   $firstpage, $iiso);
      $pdf->draw_text(mm_pt(22), mm_pt(256), strtoupper($yhtiorow["postino"]." ".$yhtiorow["postitp"]), $firstpage, $iiso);

      // vastaanottaja
      if ($asiakastiedot["laskutus_nimi"] != "" and ($asiakastiedot["maksukehotuksen_osoitetiedot"] == "B" or ($yhtiorow["maksukehotuksen_osoitetiedot"] == "K" and $asiakastiedot["maksukehotuksen_osoitetiedot"] == ""))) {
        $pdf->draw_text(mm_pt(22), mm_pt(234), strtoupper($asiakastiedot["laskutus_nimi"]),   $firstpage, $iiso);
        $pdf->draw_text(mm_pt(22), mm_pt(230), strtoupper($asiakastiedot["laskutus_nimitark"]), $firstpage, $iiso);
        $pdf->draw_text(mm_pt(22), mm_pt(226), strtoupper($asiakastiedot["laskutus_osoite"]),   $firstpage, $iiso);
        $pdf->draw_text(mm_pt(22), mm_pt(222), strtoupper($asiakastiedot["laskutus_postino"]." ".$asiakastiedot["laskutus_postitp"]), $firstpage, $iiso);
        // Laitetaan laskutus_maa asiakas_maaksi niin saadaan ilman ehtomuuttujia kyselystä oikea lopputulos.
        $asiakastiedot['maa'] = $asiakastiedot["laskutus_maa"];
      }
      else {
        $pdf->draw_text(mm_pt(22), mm_pt(234), strtoupper($asiakastiedot["nimi"]),     $firstpage, $iiso);
        $pdf->draw_text(mm_pt(22), mm_pt(230), strtoupper($asiakastiedot["nimitark"]),   $firstpage, $iiso);
        $pdf->draw_text(mm_pt(22), mm_pt(226), strtoupper($asiakastiedot["osoite"]),   $firstpage, $iiso);
        $pdf->draw_text(mm_pt(22), mm_pt(222), strtoupper($asiakastiedot["postino"]." ".$asiakastiedot["postitp"]), $firstpage, $iiso);
      }


      // jos vastaanottaja on eri maassa kuin yhtio niin lisätään maan nimi
      if ($yhtiorow['maa'] != $asiakastiedot['maa']) {
        $query = sprintf(
          "SELECT nimi from maat where koodi='%s' AND ryhma_tunnus = ''",
          mysql_real_escape_string($asiakastiedot['maa'])
        );

        $maa_result = pupe_query($query);
        $maa_nimi = mysql_fetch_assoc($maa_result);
        $pdf->draw_text(mm_pt(22), mm_pt(218), $maa_nimi['nimi'],                         $firstpage, $iiso);
      }
    }

    //Oikea sarake
    $pdf->draw_rectangle(760, 320, 739, 575,         $firstpage, $rectparam);
    $pdf->draw_rectangle(760, 420, 739, 575,         $firstpage, $rectparam);
    $pdf->draw_text(330, 752, t("Päivämäärä", $kieli),     $firstpage, $pieni);

    if ($karhukierros_tunnus != "") {
      $query = "SELECT pvm
                FROM karhukierros
                WHERE tunnus = '$karhukierros_tunnus'
                LIMIT 1";
      $pvm_result = pupe_query($query);
      $pvm_row = mysql_fetch_assoc($pvm_result);
      $paiva = substr($pvm_row["pvm"], 8, 2);
      $kuu   = substr($pvm_row["pvm"], 5, 2);
      $year  = substr($pvm_row["pvm"], 0, 4);
    }
    else {
      $pvm_row = array();
      $pvm_row["pvm"] = date("Y-m-d");
      $paiva = date("j");
      $kuu   = date("n");
      $year  = date("Y");
    }

    $pdf->draw_text(330, 742, tv1dateconv($pvm_row["pvm"]),  $firstpage, $norm);
    $pdf->draw_text(430, 752, t("Asiaa hoitaa", $kieli),   $firstpage, $pieni);
    $pdf->draw_text(430, 742, $yrow["nimi"],         $firstpage, $norm);

    $pdf->draw_rectangle(739, 320, 718, 575, $firstpage, $rectparam);
    $pdf->draw_rectangle(739, 420, 718, 575, $firstpage, $rectparam);
    $pdf->draw_text(330, 731, t("Eräpäivä", $kieli), $firstpage, $pieni);

    if ($yhtiorow['karhuerapvm'] > 0) {
      $seurday   = date("d", mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'],  $year));
      $seurmonth = date("m", mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'],  $year));
      $seuryear  = date("Y", mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'],  $year));

      $pdf->draw_text(330, 721, tv1dateconv($seuryear."-".$seurmonth."-".$seurday), $firstpage, $norm);
    }
    else {
      $pdf->draw_text(330, 721, t("HETI", $kieli), $firstpage, $norm);
    }

    $pdf->draw_text(430, 731, t("Puhelin", $kieli), $firstpage, $pieni);
    $pdf->draw_text(430, 721, $yrow["puhno"], $firstpage, $norm);

    $pdf->draw_rectangle(718, 320, 697, 575, $firstpage, $rectparam);
    $pdf->draw_rectangle(718, 420, 697, 575, $firstpage, $rectparam);
    $pdf->draw_text(330, 710, t("Viivästykorko", $kieli),       $firstpage, $pieni);
    $pdf->draw_text(330, 700, ($yhtiorow["viivastyskorko"]*1)."%",   $firstpage, $norm);
    $pdf->draw_text(430, 710, t("Sähköposti", $kieli),         $firstpage, $pieni);
    $pdf->draw_text(430, 700, $yrow["eposti"],             $firstpage, $norm);

    $pdf->draw_rectangle(697, 320, 676, 575, $firstpage, $rectparam);
    $pdf->draw_text(330, 689, t("Ytunnus/Asiakasnumero", $kieli),   $firstpage, $pieni);
    $pdf->draw_text(330, 679, $asiakastiedot["ytunnus"],       $firstpage, $norm);

    //Rivit alkaa täsä kohtaa
    $kala = 540;

    // lisätään karhuviesti kirjeeseen
    if ($sivu == 1) {

      //otsikko
      if ($yhtiorow['maksukehotus_kentat'] == 'J' or $yhtiorow['maksukehotus_kentat'] == 'L') {
        $pdf->draw_text(30, $kala+30, t("Avoimet laskut", $kieli), $firstpage, $bold);
      }

      // tehdään riveistä max 90 merkkiä
      $viesti = wordwrap($viesti, 90, "\n");

      $i = 0;
      $rivit = explode("\n", $viesti);

      $yhteyshenkiloteksti = t("Yhteyshenkilömme", $kieli) . ": $yrow[nimi] / $yrow[eposti] / $yrow[puhno]";

      $rivit[] = '';
      $rivit[] = $yhteyshenkiloteksti;

      foreach ($rivit as $rivi) {
        // laitetaan
        $pdf->draw_text(80, $kala, $rivi, $firstpage, $norm);

        // seuraava rivi tulee 10 pistettä alemmas kuin tämä rivi
        $kala -= 10;
        $i++;
      }
    }

    $kala -= 10;

    //Laskurivien otsikkotiedot
    //eka rivi
    $pdf->draw_text(30,  $kala, t("Laskun numero", $kieli)." / ".t("Viite", $kieli),    $firstpage, $pieni);

    if ($yhtiorow['maksukehotus_kentat'] == 'J' or $yhtiorow['maksukehotus_kentat'] == 'L') {
      //eka rivi lisäkentillä
      $pdf->draw_text(130, $kala, t("Laskun pvm", $kieli),                  $firstpage, $pieni);
      $pdf->draw_text(190, $kala, t("Eräpäivä", $kieli),                    $firstpage, $pieni);
      $pdf->draw_text(245, $kala, t("Myöhässä pv", $kieli),                  $firstpage, $pieni);
      $pdf->draw_text(410, $kala, t("Laskun summa", $kieli),                  $firstpage, $pieni);
      $pdf->draw_text(495, $kala, t("Korko", $kieli),                      $firstpage, $pieni);
      $pdf->draw_text(545, $kala, t("Yhteensä", $kieli),                    $firstpage, $pieni);

      if ($yhtiorow['maksukehotus_kentat'] == 'J') {
        $pdf->draw_text(295, $kala, t("Viimeisin muistutuspvm", $kieli),            $firstpage, $pieni);
        $pdf->draw_text(365, $kala, t("Perintäkerta", $kieli),                  $firstpage, $pieni);
      }
    }
    else {
      //eka rivi ilman lisäkenttiä
      $pdf->draw_text(180, $kala, t("Laskun pvm", $kieli),                  $firstpage, $pieni);
      $pdf->draw_text(240, $kala, t("Eräpäivä", $kieli),                    $firstpage, $pieni);
      $pdf->draw_text(295, $kala, t("Myöhässä pv", $kieli),                  $firstpage, $pieni);
      $pdf->draw_text(455, $kala, t("Laskun summa", $kieli),                  $firstpage, $pieni);

      if ($yhtiorow["maksukehotus_kentat"] == "") {
        $pdf->draw_text(360, $kala, t("Viimeisin muistutuspvm", $kieli),          $firstpage, $pieni);
        $pdf->draw_text(525, $kala, t("Perintäkerta", $kieli),                $firstpage, $pieni);
      }
    }

    $kala -= 15;

    //toka rivi
    if ($kaatosumma != 0 and $sivu == 1) {
      $pdf->draw_text(30,  $kala, t("Kohdistamattomia suorituksia", $kieli),  $firstpage, $norm);

      if ($yhtiorow['maksukehotus_kentat'] == 'J' or $yhtiorow['maksukehotus_kentat'] == 'L') {
        $oikpos = $pdf->strlen(sprintf("%.2f", $kaatosumma), $norm);
        $pdf->draw_text(565-$oikpos, $kala, sprintf("%.2f", $kaatosumma),    $firstpage, $norm);
      }
      else {
        $oikpos = $pdf->strlen(sprintf("%.2f", $kaatosumma), $norm);
        $pdf->draw_text(500-$oikpos, $kala, sprintf("%.2f", $kaatosumma),    $firstpage, $norm);
      }


      $kala -= 13;
    }

    return $firstpage;
  }
}

if (!function_exists("rivi")) {
  function rivi($firstpage, $summa, $korko) {
    global $firstpage, $pdf, $yhtiorow, $kukarow, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli, $karhutunnus, $karhukertanro;

    // siirrytäänkö uudelle sivulle?
    if ($kala < 153) {
      $sivu++;
      loppu($firstpage, '', '');
      $firstpage = alku();
      $lask = 1;
    }
    // ei anneta negatiivisia korkoja
    $row['korko'] = ($row['summa'] >= 0) ? $row['korko'] : 0.0;

    $pdf->draw_text(30,  $kala, $row["laskunro"]." / ".$row["viite"],  $firstpage, $norm);

    if (!empty($karhutunnus)) {
      $query = "SELECT count(distinct ktunnus) ktun
                FROM karhu_lasku
                JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus AND karhukierros.tyyppi = '')
                WHERE ltunnus = {$row['tunnus']}
                AND ktunnus   <= $karhutunnus";
      $karhukertares = pupe_query($query);
      $karhukertarow = mysql_fetch_assoc($karhukertares);

      $karhukertanro = $karhukertarow["ktun"];

      $query = "SELECT
                max(karhukierros.pvm) as kpvm
                FROM karhu_lasku
                JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus AND karhukierros.tyyppi = '')
                WHERE ltunnus = {$row['tunnus']}
                AND ktunnus   < $karhutunnus";
      $karhukertares = pupe_query($query);
      $karhukertarow = mysql_fetch_assoc($karhukertares);

      $karhuedpvm = $karhukertarow["kpvm"];
    }
    else {
      $karhukertanro = $row["karhuttu"] + 1;
      $karhuedpvm = $row["kpvm"];
    }

    if ($yhtiorow['maksukehotus_kentat'] == 'J' or $yhtiorow['maksukehotus_kentat'] == 'L') {

      $pdf->draw_text(130, $kala, tv1dateconv($row["tapvm"]),       $firstpage, $norm);
      $pdf->draw_text(190, $kala, tv1dateconv($row["erpcm"]),       $firstpage, $norm);

      $oikpos = $pdf->strlen($row["ika"], $norm);
      $pdf->draw_text(270-$oikpos, $kala, $row["ika"],           $firstpage, $norm);

      if ($row["valkoodi"] != $yhtiorow["valkoodi"]) {
        $oikpos = $pdf->strlen($row["summa_valuutassa"], $norm);
        $pdf->draw_text(460-$oikpos, $kala, $row["summa_valuutassa"],   $firstpage, $norm);
      }
      else {
        $oikpos = $pdf->strlen($row["summa"], $norm);
        $pdf->draw_text(460-$oikpos, $kala, $row["summa"],        $firstpage, $norm);
      }
      if ($yhtiorow["maksukehotus_kentat"] == "" or $yhtiorow["maksukehotus_kentat"] == "J") {
        $pdf->draw_text(295, $kala, tv1dateconv($karhuedpvm),       $firstpage, $norm);
        $oikpos = $pdf->strlen($karhukertanro, $norm);
        $pdf->draw_text(385-$oikpos, $kala, $karhukertanro,       $firstpage, $norm);
      }

      $oikpos = $pdf->strlen(sprintf('%.2f', $row["korko"]), $norm);
      $pdf->draw_text(515-$oikpos, $kala, $row["korko"],                   $firstpage, $norm);
      $oikpos = $pdf->strlen(sprintf('%.2f', $row["summa"] + $row["korko"]), $norm);
      $pdf->draw_text(565-$oikpos, $kala, sprintf('%.2f', $row["summa"] + $row["korko"]),  $firstpage, $norm);
    }
    else {

      $pdf->draw_text(180, $kala, tv1dateconv($row["tapvm"]),              $firstpage, $norm);
      $pdf->draw_text(240, $kala, tv1dateconv($row["erpcm"]),              $firstpage, $norm);

      $oikpos = $pdf->strlen($row["ika"], $norm);
      $pdf->draw_text(338-$oikpos, $kala, $row["ika"],                $firstpage, $norm);

      if ($row["valkoodi"] != $yhtiorow["valkoodi"]) {
        $oikpos = $pdf->strlen($row["summa_valuutassa"], $norm);
        $pdf->draw_text(500-$oikpos, $kala, $row["summa_valuutassa"]." ".$row["valkoodi"],  $firstpage, $norm);
      }
      else {
        $oikpos = $pdf->strlen($row["summa"], $norm);
        $pdf->draw_text(500-$oikpos, $kala, $row["summa"]." ".$row["valkoodi"],        $firstpage, $norm);
      }

      if ($yhtiorow["maksukehotus_kentat"] == "" or $yhtiorow["maksukehotus_kentat"] == "J") {
        $pdf->draw_text(365, $kala, tv1dateconv($karhuedpvm),   $firstpage, $norm);
        $oikpos = $pdf->strlen($karhukertanro, $norm);
        $pdf->draw_text(560-$oikpos, $kala, $karhukertanro,   $firstpage, $norm);
      }
    }

    $kala = $kala - 13;

    $lask++;

    if ($row["valkoodi"] != $yhtiorow["valkoodi"]) {
      $summa += $row["summa_valuutassa"];
    }
    else {
      $summa += $row["summa"];
    }

    $korko += $row["korko"];

    $palautus = array(
      "korko"   => $korko,
      "summa"   => $summa,
    );
    return $palautus;
  }
}

if (!function_exists("loppu")) {
  function loppu($firstpage, $summa, $korko) {

    global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $ktunnus, $maksuehtotiedot, $toimipaikkarow, $laskutiedot, $karhut_samalle_laskulle, $karhukertanro;

    /*
    //yhteensärivi
    $pdf->draw_rectangle(110, 20, 90, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(110, 207, 90, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(110, 394, 90, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(110, 540, 90, 580,  $firstpage, $rectparam);
    */

    if (($karhut_samalle_laskulle == 1 or $karhukertanro != "") and ($summa != '' and $korko != '')) {

      if ($yhtiorow['maksukehotus_kentat'] == 'J' or $yhtiorow['maksukehotus_kentat'] == 'L') {

        //Kokonaissummalaatikko + valuuttalaatikko
        $pdf->draw_rectangle(115, 364, 148, 580,  $firstpage, $rectparam);
        $pdf->draw_rectangle(115, 540, 148, 580,  $firstpage, $rectparam);

        $pdf->draw_text(370, 138,  t("YHTEENSÄ", $kieli).":",  $firstpage, $norm);
        $pdf->draw_text(370, 128,  t("KORKO", $kieli).":",  $firstpage, $norm);
        $pdf->draw_text(370, 118,  t("YHTEENSÄ + KORKO", $kieli).":",  $firstpage, $norm);

        $kokonaissumma = $korko+$summa;

        $oikpos = $pdf->strlen(sprintf("%.2f", $summa), $norm);
        $pdf->draw_text(535-$oikpos, 138, sprintf("%.2f", $summa),  $firstpage, $norm);
        $oikpos = $pdf->strlen(sprintf("%.2f", $korko), $norm);
        $pdf->draw_text(535-$oikpos, 128, sprintf("%.2f", $korko),  $firstpage, $norm);
        $oikpos = $pdf->strlen(sprintf("%.2f", $kokonaissumma), $norm);
        $pdf->draw_text(535-$oikpos, 118, sprintf("%.2f", $kokonaissumma),  $firstpage, $norm);

        $oikpos = $pdf->strlen($laskutiedot["valkoodi"], $norm);
        $pdf->draw_text(575-$oikpos, 138, $laskutiedot["valkoodi"],  $firstpage, $norm);
        $pdf->draw_text(575-$oikpos, 128, $laskutiedot["valkoodi"],  $firstpage, $norm);
        $pdf->draw_text(575-$oikpos, 118, $laskutiedot["valkoodi"],  $firstpage, $norm);
      }
      else {
        $pdf->draw_text(380, 118,  t("YHTEENSÄ", $kieli).":",        $firstpage, $norm);

        $oikpos = $pdf->strlen(sprintf("%.2f", $summa), $norm);
        $pdf->draw_text(500-$oikpos, 118, sprintf("%.2f", $summa)." ".$laskutiedot["valkoodi"],                $firstpage, $norm);
      }
    }

    $pankkitiedot = array();

    //Laitetaan pankkiyhteystiedot kuntoon
    if (isset($maksuehtotiedot["factoring_id"])) {
      $query = "SELECT *
                FROM factoring
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$maksuehtotiedot[factoring_id]'";
      $fac_result = pupe_query($query);
      $factoringrow = mysql_fetch_assoc($fac_result);

      $pankkitiedot["pankkinimi1"]  =  $factoringrow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $factoringrow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $factoringrow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $factoringrow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $factoringrow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $factoringrow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $factoringrow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $factoringrow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  "";
      $pankkitiedot["pankkitili3"]  =  "";
      $pankkitiedot["pankkiiban3"]  =  "";
      $pankkitiedot["pankkiswift3"] =  "";
    }
    elseif ($maksuehtotiedot["pankkinimi1"] != "") {
      $pankkitiedot["pankkinimi1"]  =  $maksuehtotiedot["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $maksuehtotiedot["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $maksuehtotiedot["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $maksuehtotiedot["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $maksuehtotiedot["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $maksuehtotiedot["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $maksuehtotiedot["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $maksuehtotiedot["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  $maksuehtotiedot["pankkinimi3"];
      $pankkitiedot["pankkitili3"]  =  $maksuehtotiedot["pankkitili3"];
      $pankkitiedot["pankkiiban3"]  =  $maksuehtotiedot["pankkiiban3"];
      $pankkitiedot["pankkiswift3"] =  $maksuehtotiedot["pankkiswift3"];
    }
    else {
      $pankkitiedot["pankkinimi1"]  =  $yhtiorow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $yhtiorow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $yhtiorow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $yhtiorow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $yhtiorow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $yhtiorow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $yhtiorow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  $yhtiorow["pankkinimi3"];
      $pankkitiedot["pankkitili3"]  =  $yhtiorow["pankkitili3"];
      $pankkitiedot["pankkiiban3"]  =  $yhtiorow["pankkiiban3"];
      $pankkitiedot["pankkiswift3"] =  $yhtiorow["pankkiswift3"];
    }

    //Pankkiyhteystiedot
    $pdf->draw_rectangle(115, 20, 68, 580,  $firstpage, $rectparam);

    $pdf->draw_text(30, 106,  t("Pankkiyhteys", $kieli),  $firstpage, $pieni);

    $pdf->draw_text(30,  94, $pankkitiedot["pankkinimi1"]." ".$pankkitiedot["pankkitili1"],  $firstpage, $norm);
    $pdf->draw_text(217, 94, $pankkitiedot["pankkinimi2"]." ".$pankkitiedot["pankkitili2"],  $firstpage, $norm);
    $pdf->draw_text(404, 94, $pankkitiedot["pankkinimi3"]." ".$pankkitiedot["pankkitili3"],  $firstpage, $norm);

    if ($pankkitiedot["pankkiiban1"] != "") {
      $pdf->draw_text(30,  83, "IBAN: ".$pankkitiedot["pankkiiban1"],  $firstpage, $pieni);
    }
    if ($pankkitiedot["pankkiiban2"] != "") {
      $pdf->draw_text(217, 83, "IBAN: ".$pankkitiedot["pankkiiban2"],  $firstpage, $pieni);
    }
    if ($pankkitiedot["pankkiiban3"] != "") {
      $pdf->draw_text(404, 83, "IBAN: ".$pankkitiedot["pankkiiban3"],  $firstpage, $pieni);
    }
    if ($pankkitiedot["pankkiswift1"] != "") {
      $pdf->draw_text(30,  72, "SWIFT: ".$pankkitiedot["pankkiswift1"],  $firstpage, $pieni);
    }
    if ($pankkitiedot["pankkiswift2"] != "") {
      $pdf->draw_text(217, 72, "SWIFT: ".$pankkitiedot["pankkiswift2"],  $firstpage, $pieni);
    }
    if ($pankkitiedot["pankkiswift3"] != "") {
      $pdf->draw_text(404, 72, "SWIFT: ".$pankkitiedot["pankkiswift3"],  $firstpage, $pieni);
    }

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(65, 20,  20, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(65, 207, 20, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(65, 394, 20, 580,  $firstpage, $rectparam);

    $pdf->draw_text(30, 55, $toimipaikkarow["nimi"],          $firstpage, $pieni);
    $pdf->draw_text(30, 45, $toimipaikkarow["osoite"],          $firstpage, $pieni);
    $pdf->draw_text(30, 35, $toimipaikkarow["postino"]."  ".$toimipaikkarow["postitp"],  $firstpage, $pieni);
    $pdf->draw_text(30, 25, $toimipaikkarow["maa"],            $firstpage, $pieni);

    $pdf->draw_text(217, 55, t("Puhelin", $kieli).":",          $firstpage, $pieni);
    $pdf->draw_text(247, 55, $toimipaikkarow["puhelin"],        $firstpage, $pieni);
    $pdf->draw_text(217, 45, t("Fax", $kieli).":",            $firstpage, $pieni);
    $pdf->draw_text(247, 45, $toimipaikkarow["fax"],          $firstpage, $pieni);
    $pdf->draw_text(217, 35, t("Email", $kieli).":",          $firstpage, $pieni);
    $pdf->draw_text(247, 35, $toimipaikkarow["email"],          $firstpage, $pieni);

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",          $firstpage, $pieni);
    $pdf->draw_text(444, 55, $toimipaikkarow["vat_numero"],        $firstpage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",        $firstpage, $pieni);
    $pdf->draw_text(444, 45, $toimipaikkarow["kotipaikka"],        $firstpage, $pieni);
    $pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),          $firstpage, $pieni);
    $pdf->draw_text(404, 25, t("Alv.rek", $kieli),            $firstpage, $pieni);

  }
}

require_once 'pdflib/phppdflib.class.php';

flush();

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);
$rectparam["width"] = 0.3;

$norm["height"]   = 10;
$norm["font"]     = "Times-Roman";

$boldi["height"]   = 10;
$boldi["font"]     = "Times-Bold";

$pieni["height"]   = 8;
$pieni["font"]     = "Times-Roman";

$iso["height"]     = 14;
$iso["font"]     = "Helvetica-Bold";

// defaultteja
$lask = 1;
$sivu = 1;

// aloitellaan laskun teko
if (is_array($lasku_tunnus)) {
  $ltunnukset = implode(",", $lasku_tunnus);
}
else {
  $ltunnukset = $lasku_tunnus;
}

if ($nayta_pdf == 1 and $karhutunnus != '') {
  $karhutunnus = mysql_real_escape_string($karhutunnus);
  $kjoinlisa   = " and kl.ktunnus = '$karhutunnus' ";
  $ikalaskenta = " TO_DAYS(kk.pvm) - TO_DAYS(l.erpcm) as ika, ";
}
else {
  $kjoinlisa   = "";
  $ikalaskenta = " TO_DAYS(now()) - TO_DAYS(l.erpcm) as ika, ";
}

$query = "SELECT l.tunnus,
          l.tapvm,
          l.liitostunnus,
          l.summa-l.saldo_maksettu summa,
          l.summa_valuutassa-l.saldo_maksettu_valuutassa summa_valuutassa,
          l.erpcm, l.laskunro, l.viite,
          l.yhtio_toimipaikka, l.valkoodi, l.maksuehto, l.maa,
          round((l.viikorkopros * (TO_DAYS(if(mapvm!='0000-00-00', mapvm, now())) - TO_DAYS(erpcm)) * summa / 36500),2) as korko,
          $ikalaskenta
          max(kk.pvm) as kpvm,
          count(distinct kl.ktunnus) as karhuttu,
          l.laskunro laskunro,
          l.myyja myyja
          FROM lasku l
          LEFT JOIN karhu_lasku kl on (l.tunnus = kl.ltunnus $kjoinlisa)
          LEFT JOIN karhukierros kk on (kk.tunnus = kl.ktunnus AND kk.tyyppi = '')
          WHERE l.tunnus in ($ltunnukset)
          and l.yhtio    = '$kukarow[yhtio]'
          and l.tila     = 'U'
          GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13
          ORDER BY l.erpcm, l.laskunro";
$result = pupe_query($query);

//otetaan maksuehto- ja asiakastiedot ekalta laskulta
$laskutiedot = mysql_fetch_assoc($result);

$query = "SELECT *
          FROM maksuehto
          LEFT JOIN pankkiyhteystiedot ON (pankkiyhteystiedot.yhtio = maksuehto.yhtio AND pankkiyhteystiedot.tunnus = maksuehto.pankkiyhteystiedot)
          WHERE maksuehto.yhtio = '$kukarow[yhtio]'
          AND maksuehto.tunnus  = '$laskutiedot[maksuehto]'";
$maksuehtoresult = pupe_query($query);
$maksuehtotiedot = mysql_fetch_assoc($maksuehtoresult);

$query = "SELECT *
          FROM asiakas
          WHERE yhtio = '$kukarow[yhtio]'
          AND tunnus  = '$laskutiedot[liitostunnus]'";
$asiakasresult = pupe_query($query);
$asiakastiedot = mysql_fetch_assoc($asiakasresult);

//Otetaan tässä asiakkaan kieli talteen
$kieli = $asiakastiedot["kieli"];

//ja kelataan akuun
mysql_data_seek($result, 0);

$query = "SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
          FROM lasku
          WHERE lasku.yhtio = '$kukarow[yhtio]'
          and lasku.tunnus  in ($ltunnukset)";
$lires = pupe_query($query);
$lirow = mysql_fetch_assoc($lires);

// Karhuvaiheessa tämä on tyhjä
if ($laskutiedot["kpvm"] == "") {
  $laskutiedot["kpvm"] = date("Y-m-d");
}

if ($lirow["liitokset"] != "") {
  $query = "SELECT sum(summa) summa
            FROM suoritus
            WHERE yhtio        = '$kukarow[yhtio]'
            and (kohdpvm = '0000-00-00' or kohdpvm > '$laskutiedot[kpvm]')
            and ltunnus        > 0
            and kirjpvm        <= '$laskutiedot[kpvm]'
            and asiakas_tunnus in ($lirow[liitokset])";
  $summaresult = pupe_query($query);
  $kaato = mysql_fetch_assoc($summaresult);
}
else {
  $kaato = array();
  $kaato["summa"] = 0;
}

// haetaan yhtiön toimipaikkojen yhteystiedot
if ($laskutiedot["yhtio_toimipaikka"] != '' and $laskutiedot["yhtio_toimipaikka"] != 0) {
  $toimipaikkaquery = "SELECT *
                       FROM yhtion_toimipaikat
                       WHERE yhtio = '$kukarow[yhtio]'
                       AND tunnus  = '$laskutiedot[yhtio_toimipaikka]'";
  $toimipaikkares = pupe_query($toimipaikkaquery);
  $toimipaikkarow = mysql_fetch_assoc($toimipaikkares);
}
else {
  $toimipaikkarow["nimi"]     = $yhtiorow["nimi"];
  $toimipaikkarow["osoite"]     = $yhtiorow["osoite"];
  $toimipaikkarow["postino"]     = $yhtiorow["postino"];
  $toimipaikkarow["postitp"]     = $yhtiorow["postitp"];
  $toimipaikkarow["maa"]       = $yhtiorow["maa"];
  $toimipaikkarow["puhelin"]     = $yhtiorow["puhelin"];
  $toimipaikkarow["fax"]       = $yhtiorow["fax"];
  $toimipaikkarow["email"]     = $yhtiorow["email"];
  $toimipaikkarow["vat_numero"]   = $yhtiorow["ytunnus"];
  $toimipaikkarow["kotipaikka"]   = $yhtiorow["kotipaikka"];
}

$kaatosumma = $kaato["summa"] * -1;
if (!$kaatosumma) $kaatosumma = '0.00';

//  Arvotaan oikea karhuviesti
if (!isset($karhuviesti)) {

  //  Lasketaan kuinka vanhoja laskuja tässä karhutaan
  $query = "SELECT count(*) kpl
            FROM karhu_lasku
            WHERE ltunnus IN ($ltunnukset)
            GROUP BY ltunnus";
  $res = pupe_query($query);
  $r = 0;

  while ($a = mysql_fetch_assoc($res)) {
    $r += $a["kpl"];
  }

  //  Tämä on mikä on karhujen keskimääräinen kierroskerta
  $avg = floor(($r/mysql_num_rows($res))+1);

  if ($tee_pdf == 'tulosta_karhu') {
    $avg--;
  }

  // Etsitään asiakkaan kielellä:
  $query = "SELECT tunnus
            FROM avainsana
            WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys = '$avg' and kieli = '$kieli'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 0) {

    $query = "SELECT tunnus
              FROM avainsana
              WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys < '$avg' and kieli = '$kieli'
              ORDER BY jarjestys DESC
              LIMIT 1";
    $res = pupe_query($query);

    if (mysql_num_rows($res) == 0) {

      $query = "SELECT tunnus
                FROM avainsana
                WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys > '$avg' and kieli = '$kieli'
                ORDER BY jarjestys ASC
                LIMIT 1";
      $res = pupe_query($query);
    }
  }

  // Etsitään yhtiön kielellä:
  if (mysql_num_rows($res) == 0) {
    $query = "SELECT tunnus
              FROM avainsana
              WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys = '$avg' and kieli = '$yhtiorow[kieli]'";
    $res = pupe_query($query);

    if (mysql_num_rows($res) == 0) {

      $query = "SELECT tunnus
                FROM avainsana
                WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys < '$avg' and kieli = '$yhtiorow[kieli]'
                ORDER BY jarjestys DESC
                LIMIT 1";
      $res = pupe_query($query);

      if (mysql_num_rows($res) == 0) {

        $query = "SELECT tunnus
                  FROM avainsana
                  WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys > '$avg' and kieli = '$yhtiorow[kieli]'
                  ORDER BY jarjestys ASC
                  LIMIT 1";
        $res = pupe_query($query);
      }
    }
  }

  $kv = mysql_fetch_assoc($res);
  $karhuviesti = $kv["tunnus"];
}

$query = "SELECT selitetark
          FROM avainsana
          WHERE tunnus = '$karhuviesti'
          AND laji     = 'KARHUVIESTI'
          AND yhtio    = '{$yhtiorow['yhtio']}'";
$res = pupe_query($query);
$viestit = mysql_fetch_assoc($res);

$karhuviesti = $viestit["selitetark"];

$firstpage = alku($karhuviesti, $karhutunnus);

$summa = 0.0;
$korko = 0.0;
$rivit = array();

while ($row = mysql_fetch_assoc($result)) {

  if ($tee_pdf == 'tulosta_karhu') {
    // huomioidaan osasuoritukset jos tulostetaan kopsu jälkikäteen
    $query = "SELECT sum(summa) osasuor_summa
              FROM tiliointi USE INDEX (tositerivit_index)
              WHERE yhtio  = '$kukarow[yhtio]'
              and ltunnus  = '$row[tunnus]'
              and tilino   in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]')
              and tapvm    >= '$laskutiedot[kpvm]'
              and korjattu = ''";
    $lasktilitre = pupe_query($query);
    $lasktilitro = mysql_fetch_assoc($lasktilitre);

    if ($lasktilitro["osasuor_summa"] != 0) {
      $row["summa"] += $lasktilitro["osasuor_summa"];
    }
  }

  $rivit[] = $row;
  $palautus = rivi($firstpage, $summa, $korko);
  $summa = $palautus['summa'];
  $korko = $palautus['korko'];
}

// loppusumma
$loppusumma = sprintf('%.2f', $summa+$kaatosumma);

// viimenen sivu
loppu($firstpage, $loppusumma,  $korko);

//keksitään uudelle failille joku varmasti uniikki nimi:
$pdffilenimi = "/tmp/karhu_$kukarow[yhtio]_".date("Ymd")."_".$laskutiedot['laskunro'].".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
fclose($fh);

if ($nayta_pdf == 1) {
  echo file_get_contents($pdffilenimi);
}

if ($yhtiorow["verkkolasku_lah"] == "maventa" and $_REQUEST['maventa_laheta'] == 'Lähetä Maventaan') {

  if (!function_exists("vlas_dateconv")) {
    function vlas_dateconv($date) {
      //kääntää mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
      return substr($date, 0, 4).substr($date, 5, 2).substr($date, 8, 2);
    }
  }

  if (!function_exists("pp")) {
    function pp($muuttuja, $round = "", $rmax = "", $rmin = "") {
      if (strlen($round) > 0) {
        if (strlen($rmax) > 0 and $rmax < $round) {
          $round = $rmax;
        }
        if (strlen($rmin) > 0 and $rmin > $round) {
          $round = $rmin;
        }
        return $muuttuja = number_format($muuttuja, $round, ",", "");
      }
      else {
        return $muuttuja   = str_replace(".", ",", $muuttuja);
      }
    }
  }

  if (!function_exists("spyconv")) {
    function spyconv($spy) {
      return $spy = sprintf("%020.020s", $spy);
    }
  }

  // Täytetään api_keys, näillä kirjaudutaan Maventaan
  $api_keys = array();
  $api_keys["user_api_key"]   = $yhtiorow['maventa_api_avain'];
  $api_keys["vendor_api_key"]   = $yhtiorow['maventa_ohjelmisto_api_avain'];

  // Vaihtoehtoinen company_uuid
  if ($yhtiorow['maventa_yrityksen_uuid'] != "") {
    $api_keys["company_uuid"] = $yhtiorow['maventa_yrityksen_uuid'];
  }

  // Testaus
  //$client = new SoapClient('https://testing.maventa.com/apis/bravo/wsdl');
  // Tuotanto
  $client = new SoapClient('https://secure.maventa.com/apis/bravo/wsdl/');

  if ($yhtiorow["finvoice_versio"] == "2") {
    require "tilauskasittely/verkkolasku_finvoice_201.inc";
  }
  else {
    require "tilauskasittely/verkkolasku_finvoice.inc";
  }

  $finvoice_file_path = "$pupe_root_polku/dataout/karhu_$kukarow[yhtio]_".date("Ymd")."_".$laskutiedot['laskunro'].".xml";
  $tootfinvoice    = fopen($finvoice_file_path, 'w');

  $pankkitiedot   = $yhtiorow;
  $tyyppi      = '';
  $silent      = '';
  $toimaikarow['mint'] = date("Y-m-d");
  $toimaikarow['maxt'] = date("Y-m-d");

  // Otetaan ekan laskun tiedot
  $query = "SELECT *
            FROM lasku
            WHERE tunnus in ($ltunnukset)
            and yhtio    = '$kukarow[yhtio]'
            and tila     = 'U'
            order by laskunro desc
            LIMIT 1";
  $result_temp = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result_temp);

  $laskurow["chn"] = "100";
  $laskurow["verkkotunnus"] = "PRINT";
  $laskurow["arvo"]  = 0;
  $laskurow["summa"] = 0;
  $laskurow["tapvm"] = date("Y-m-d");
  $laskurow["erpcm"] = date("Y-m-d");
  $laskurow["kapvm"] = date("Y-m-d");

  if ($laskurow["toim_nimi"] == '') {
    $laskurow["toim_nimi"]    = $laskurow["nimi"];
    $laskurow["toim_osoite"]  = $laskurow["osoite"];
    $laskurow["toim_postitp"] = $laskurow["postitp"];
    $laskurow["toim_postino"] = $laskurow["postino"];
  }

  $alvrow = array(
    'rivihinta'     => 0,
    'alv'       => alv_oletus(),
    'alvrivihinta'   => 0
  );

  $masrow = array(
    'teksti' => 'Heti',
    'kassa_alepros' => 0
  );

  $myyrow = array(
    'nimi' => ''
  );

  finvoice_otsik($tootfinvoice, $laskurow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, "", $silent);
  finvoice_alvierittely($tootfinvoice, $laskurow, $alvrow);
  finvoice_otsikko_loput($tootfinvoice, $laskurow, $masrow, $pankkitiedot);

  $tilrow = array(
    'tuoteno'      => 1,
    'nimitys'      => 'Tyhjä',
    'kpl'        => 0,
    'tilkpl'      => 0,
    'hinta'      => 0,
    'hintapyoristys' => 0,
    'otunnus'      => 0,
    'toimitettuaika' => date("Y-m-d"),
    'tilauspaiva'    => date("Y-m-d"),
    'alv'        => 0,
    'rivihinta'    => 0,
    'rivihinta_verollinen' => 0,
  );

  $vatamount = 0;

  finvoice_rivi($tootfinvoice, $tilrow, $laskurow, $vatamount);

  finvoice_lasku_loppu($tootfinvoice, $laskurow, $pankkitiedot, $masrow);

  fclose($tootfinvoice);

  $files_out['files'] = array();
  $files_out['filenames'] = array();

  //Finvoice
  $files_out['files'][0]     = base64_encode(file_get_contents($finvoice_file_path));
  $files_out['filenames'][0]   = "Maksukehotus_".date("Ymd")."_".$laskutiedot['laskunro'].".xml";
  //PDFä
  $files_out['files'][1]     = base64_encode(file_get_contents($pdffilenimi));
  $files_out['filenames'][1]   = "Maksukehotus_".date("Ymd")."_".$laskutiedot['laskunro'].".pdf";

  // Tehdään validaatio Application Requestille
  $axml = new DomDocument('1.0');
  $axml->encoding = 'UTF-8';
  $axml->loadXML(file_get_contents($finvoice_file_path));

  $return_value = $client->invoice_put_finvoice($api_keys, $files_out);

  if (stristr($return_value->status, 'OK')) {
    echo t("Maksukehotus lähetettiin Maventaan")."\n<br>";
  }
  else {
    echo '<font class="error">'.t("Maksukehotuksen lähetys maventaan epäonnistui")." ({$return_value->status})</font>\n<br>";
    throw new Exception("Maventa Error.");
  }
}

// jos halutaan eKirje sekä configuraatio on olemassa niin
// lähetetään eKirje
if (isset($_POST['ekirje_laheta']) === true and (isset($ekirje_config) and is_array($ekirje_config))) {

  // ---------------------------------------------------------------------
  // tähän ekirjeen lähetys

  // pdfekirje luokka
  require_once 'inc/ekirje.inc';

  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float) $sec + ((float) $usec * 100000));

  $ekirje_tunnus = md5(uniqid(mt_rand(), true));

  $info = array(
    'tunniste'              => $ekirje_tunnus,       // asiakkaan oma kirjeen tunniste
    'kirjeluokka'           => '1',                      // 1 = priority, 2 = economy
    'osasto'                => $kukarow['yhtio'],       // osastokohtainen erittely = mikä yritys
    'file_id'               => $ekirje_tunnus,          // lähettäjän tunniste tiedostolle
    'kirje_id'              => $ekirje_tunnus,          // kirjeen id
    'contact_name'          => $kukarow['nimi'],
    'contact_email'         => $kukarow['eposti'],
    'contact_phone'         => $kukarow['puhno'],
    'yritys_nimi'           => trim($yhtiorow['nimi'] . ' ' . $yhtiorow['nimitark']),
    'yritys_osoite'         => $yhtiorow['osoite'],
    'yritys_postino'        => $yhtiorow['postino'],
    'yritys_postitp'        => $yhtiorow['postitp'],
    'yritys_maa'            => $yhtiorow['maa'],
    'sivumaara'             => $sivu,
  );

  if ($asiakastiedot["laskutus_nimi"] != "" and ($asiakastiedot["maksukehotuksen_osoitetiedot"] == "B" or ($yhtiorow["maksukehotuksen_osoitetiedot"] == "K" and $asiakastiedot["maksukehotuksen_osoitetiedot"] == ""))) {
    $info['vastaanottaja_osoite']   = trim($asiakastiedot['laskutus_nimi'] . ' ' . $asiakastiedot['laskutus_nimitark']);
    $info['vastaanottaja_osoite']   = $asiakastiedot["laskutus_osoite"];
    $info['vastaanottaja_postino']  = $asiakastiedot["laskutus_postino"];
    $info['vastaanottaja_postitp']  = $asiakastiedot["laskutus_postitp"];
    $info['vastaanottaja_maa']    = $asiakastiedot["laskutus_maa"];
  }
  else {
    $info['vastaanottaja_osoite']   = trim($asiakastiedot['nimi'] . ' ' . $asiakastiedot['nimitark']);
    $info['vastaanottaja_osoite']   = $asiakastiedot["osoite"];
    $info['vastaanottaja_postino']  = $asiakastiedot["postino"];
    $info['vastaanottaja_postitp']  = $asiakastiedot["postitp"];
    $info['vastaanottaja_maa']    = $asiakastiedot["maa"];
  }

  // otetaan configuraatio filestä salasanat ja muut
  $info = array_merge($info, (array) $ekirje_config);

  $ekirje = new Pupe_Pdfekirje($info);

  //koitetaan lähettää eKirje
  $ekirje->send($pdffilenimi);

  // poistetaan filet omalta koneelta
  $ekirje->clean();
}

// ------------------------------------------------------------------------
//
// nyt kirjoitetaan tiedot vasta kantaan kun tiedetään että kirje
// on lähtenyt Itellaan tai tulostetaan kirje ainoastaan

if ($tee_pdf != 'tulosta_karhu') {
  $karhukierros = uusi_karhukierros($kukarow['yhtio']);

  foreach ($rivit as $row) {
    liita_lasku($karhukierros, $row['tunnus']);
  }
}

// tulostetaan jos ei lähetetä ekirjettä eikä maventaan
if (isset($_POST['ekirje_laheta']) === false and $tee_pdf != 'tulosta_karhu' and $_REQUEST['maventa_laheta'] != 'Lähetä Maventaan') {
  if (function_exists("pupesoft_sahkoposti") and !empty($laheta_karhuemail_myyjalle)) {

    $pathinfo = pathinfo($pdffilenimi);
    $params = array(
      "to"     => $laheta_karhuemail_myyjalle,
      "subject"  => t("Maksukehotuskopio"),
      "ctype"    => "text",
      "attachements" => array(0   => array(
          "filename"    => $pdffilenimi,
          "newfilename"  => "",
          "ctype"      => $pathinfo['extension'],
        )
      )
    );

    if (pupesoft_sahkoposti($params)) echo t("Maksukehotuskopio lähetettiin myyjälle").": {$laheta_karhuemail_myyjalle}...\n<br>";
  }

  if (isset($_REQUEST['email_laheta']) and $_REQUEST['karhu_email'] != "") {

    if (!empty($yhtiorow['maksukehotus_cc_email'])) {
      $asiakkaan_nimi = trim($asiakastiedot['nimi']." ".$asiakastiedot['nimitark']);
      $asiakkaan_nimi = poista_osakeyhtio_lyhenne($asiakkaan_nimi);
      $asiakkaan_nimi = trim(pupesoft_csvstring($asiakkaan_nimi));
      $newfilename = t("Maksukehotus")." - ".date("Ymd")." - ".$laskutiedot['laskunro']." - ".$asiakkaan_nimi.".pdf";
      $sahkoposti_cc = $yhtiorow['maksukehotus_cc_email'];
    }
    else {
      $newfilename = $pdffilenimi;
      $sahkoposti_cc = "";
    }

    $pathinfo = pathinfo($pdffilenimi);
    $params = array(
      "to"     => $_REQUEST['karhu_email'],
      "cc" => $sahkoposti_cc,
      "subject"  => t("Maksukehotus", $kieli),
      "ctype"    => "text",
      "body" => $karhuviesti."\n\n".$yhteyshenkiloteksti."\n\n\n",
      "attachements" => array(0   => array(
          "filename"    => $pdffilenimi,
          "newfilename"  => $newfilename,
          "ctype"      => $pathinfo['extension'],
        )
      )
    );

    if (pupesoft_sahkoposti($params)) {
      echo t("Maksukehotus lähetetään osoitteeseen").": {$_REQUEST['karhu_email']}...\n<br>";

      if (!empty($sahkoposti_cc)) {
        echo t("Maksukehotuskopio lähetetään myös osoitteeseen").": {$sahkoposti_cc}...\n<br>";
      }
    }
  }
  else {
    $kirjoitin = $kirjoitin == 0 ? $kukarow['kirjoitin'] : $kirjoitin;

    // itse print komento...
    $query = "SELECT komento
              from kirjoittimet
              where yhtio = '{$kukarow['yhtio']}'
              and tunnus  = '{$kirjoitin}'";
    $kires = pupe_query($query);

    error_log($kirjoitin);

    if (mysql_num_rows($kires) == 1) {
      $kirow = mysql_fetch_assoc($kires);

      if ($kirow["komento"] == "email") {
        $liite = $pdffilenimi;
        $kutsu = t("Maksukehotus", $kieli)." ".$asiakastiedot["ytunnus"];
        echo t("Maksukehotus lähetetään osoitteeseen").": $kukarow[eposti]...\n<br>";

        require "inc/sahkoposti.inc";
      }
      else {
        $line = exec("{$kirow['komento']} $pdffilenimi");
      }
    }
  }
}
