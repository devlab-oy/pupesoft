<?php

//PDF parametrit
$Xpdf = new pdffile;

$Xpdf->set_default('margin-top',   0);
$Xpdf->set_default('margin-bottom', 0);
$Xpdf->set_default('margin-left',   0);
$Xpdf->set_default('margin-right',   0);
$Xrectparam["width"] = 0.3;

$Xnorm["height"] = 10;
$Xnorm["font"] = "Times-Roman";

$Xpieni["height"] = 8;
$Xpieni["font"] = "Times-Roman";

// defaultteja
$Xkala = 540;
$Xlask = 1;
$Xsivu = 1;

// hetaan kaikki otunnukset, jotka löytyvät tän uusiotunnuksen alta
$Xquery = "SELECT group_concat(distinct tilausrivi.otunnus) as tilausnumerot
           FROM tilausrivi
           WHERE tilausrivi.uusiotunnus = '{$uusiotunnus}'
           AND tilausrivi.yhtio = '{$kukarow['yhtio']}'";
$Xuresult = pupe_query($Xquery);
$Xurow = mysql_fetch_array($Xuresult);
$Xtunnukset2 = $Xurow['tilausnumerot'];

// varmistetaan vielä, että löyty tilauksia
if ($Xtunnukset2 != "") {
  $Xkilot = 0;

  // Jos vientierittelyn painot perustuu rahtikikirjan painoihin
  if ($yhtiorow['vientierittelyn_painot'] == '') {
    // haetaan kollimäärä ja bruttopaino
    $Xquery = "SELECT sum(kilot) as kilot
               FROM rahtikirjat
               WHERE otsikkonro in ({$Xtunnukset2})
               AND yhtio = '{$kukarow['yhtio']}'";
    $Xrahtiresult = pupe_query($Xquery);
    $Xrahtirow = mysql_fetch_array($Xrahtiresult);

    if ($Xrahtirow["kilot"] > 0) {
      $Xkilot = $Xrahtirow["kilot"];
    }
  }

  $group = 'tuote.tuoteno,
            tullinimike.su,
            tuote.tullinimike1,
            tuote.tullikohtelu,
            alkuperamaa';

  $select = "tuote.tuoteno,
             tullinimike.su,
             tuote.tullinimike1,
             tuote.tullikohtelu,
             (SELECT tuotteen_toimittajat.alkuperamaa from tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno ORDER BY if (tuotteen_toimittajat.alkuperamaa='$yhtiorow[maa]', concat('2', tuotteen_toimittajat.alkuperamaa), concat('1', tuotteen_toimittajat.alkuperamaa)) LIMIT 1) alkuperamaa,
             tuote.vakkoodi,
             tuote.vak_imdg_koodi,";

  if (in_array($yhtiorow['vienti_erittelyn_tulostus'], array('T', 'P'))) {
    $group = 'tuote.tullinimike1,
              tullinimike.su,
              tuote.tullikohtelu,
              tuote.vakkoodi,
              tuote.vak_imdg_koodi';

    $select = 'tuote.tullinimike1,
               tullinimike.su,
               tuote.tullikohtelu,
               tuote.vakkoodi,
               tuote.vak_imdg_koodi,
               max(tuote.tuoteno) tuoteno,';

    if (in_array($yhtiorow['vienti_erittelyn_tulostus'], array('T'))) {
      $select .= "(SELECT tuotteen_toimittajat.alkuperamaa from tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno ORDER BY if (tuotteen_toimittajat.alkuperamaa='$yhtiorow[maa]', concat('2', tuotteen_toimittajat.alkuperamaa), concat('1', tuotteen_toimittajat.alkuperamaa)) LIMIT 1) alkuperamaa,";
    }
  }

  $Xquery = "SELECT
             {$select}
             sum(round(tilausrivi.rivihinta / if ($laskurow[vienti_kurssi] > 0, $laskurow[vienti_kurssi], 1),2)) summa,
             sum(tilausrivi.kpl) kpl,
             round(sum((tilausrivi.rivihinta / $laskurow[summa]) * $Xkilot), 0) as rahtikirja_paino,
             round(sum(tilausrivi.kpl * tuote.tuotemassa), 0) as tuote_paino
             FROM tilausrivi
             JOIN tuote ON tuote.yhtio=tilausrivi.yhtio
               and tuote.tuoteno=tilausrivi.tuoteno
               and tuote.ei_saldoa        = ''
             LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn
               and tullinimike.kieli      = '$yhtiorow[kieli]'
             WHERE tilausrivi.uusiotunnus = '$uusiotunnus'
             and tilausrivi.yhtio         = '$kukarow[yhtio]'
             and tilausrivi.tyyppi        = 'L'
             and tilausrivi.kpl<>0
             GROUP BY {$group}";
  $Xriviresult = pupe_query($Xquery);

  if (mysql_num_rows($Xriviresult) < 1) {
    echo t("Yhtään tilausriviä ei löytynyt")."!<br>";
  }

  // listan functiot...
  if (!function_exists('erittely_alku')) {
    function erittely_alku() {
      global $Xpdf, $laskurow, $yhtiorow, $kukarow, $Xsivu, $Xrectparam, $Xnorm, $Xpieni, $kieli, $vakkeja;

      if (!is_object($Xpdf)) {
        return;
      }

      $Xfirstpage = $Xpdf->new_page("a4");
      $Xpdf->enable('template');
      $Xtid = $Xpdf->template->create();
      $Xpdf->template->size($Xtid, 600, 830);

      //Otsikko
      //$Xpdf->draw_rectangle(830, 20,  800, 580, $Xfirstpage, $Xrectparam);

      tulosta_logo_pdf($Xpdf, $Xfirstpage, $laskurow);
      $Xpdf->draw_text(310, 815, t("Vientierittely", $kieli), $Xfirstpage);

      $Xpdf->draw_text(430, 815, t("Sivu", $kieli)." ".$Xsivu, $Xfirstpage, $Xnorm);

      $Xpdf->draw_text(50, 729, t("Ostaja", $kieli),   $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(50, 717, $laskurow["nimi"],       $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(50, 707, $laskurow["osoite"],       $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(50, 697, $laskurow["postino"]." ".$laskurow["postitp"], $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(50, 687, $laskurow["maa"],       $Xfirstpage, $Xnorm);

      $Xpdf->draw_text(50, 666, t("Toimitusosoite", $kieli),   $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(50, 654, $laskurow["toim_nimi"],     $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(50, 644, $laskurow["toim_osoite"],   $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(50, 634, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(50, 624, $laskurow["toim_maa"],     $Xfirstpage, $Xnorm);

      //Oikea sarake
      $Xpdf->draw_rectangle(800, 300, 779, 580, $Xfirstpage,   $Xrectparam);
      $Xpdf->draw_rectangle(800, 420, 779, 580, $Xfirstpage,   $Xrectparam);
      $Xpdf->draw_text(310, 792, t("Laskun pvm", $kieli),   $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]),  $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(430, 792, t("Laskun numero", $kieli),   $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(430, 782, $laskurow["laskunro"],     $Xfirstpage, $Xnorm);

      $Xpdf->draw_rectangle(779, 300, 758, 580, $Xfirstpage,   $Xrectparam);
      $Xpdf->draw_rectangle(779, 420, 758, 580, $Xfirstpage,   $Xrectparam);
      $Xpdf->draw_text(310, 771, t("Viitteenne", $kieli),   $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(310, 761, substr($laskurow["viesti"], 0, 15), $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(430, 771, t("Y-tunnus", $kieli),     $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(430, 761, $laskurow["ytunnus"],     $Xfirstpage, $Xnorm);

      $Xpdf->draw_rectangle(758, 300, 737, 580,         $Xfirstpage, $Xrectparam);
      $Xpdf->draw_rectangle(758, 420, 737, 580,         $Xfirstpage, $Xrectparam);
      $Xpdf->draw_text(310, 750, t("Toimitusehto", $kieli),   $Xfirstpage, $Xpieni);

      list($ff_string, $ff_font) = pdf_fontfit($laskurow["toimitusehto"], 110, $Xpdf, $Xnorm);
      $Xpdf->draw_text(310, 740, $laskurow["toimitusehto"],   $Xfirstpage, $ff_font);

      $Xpdf->draw_text(430, 750, t("Toimituspvm", $kieli),   $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(430, 740, tv1dateconv($laskurow["toimaika"]),     $Xfirstpage, $Xnorm);

      $Xpdf->draw_rectangle(737, 300, 716, 580,         $Xfirstpage, $Xrectparam);
      $Xpdf->draw_text(310, 729, t("Toimitustapa", $kieli),  $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(310, 719, t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli),   $Xfirstpage, $Xnorm);

      $Xpdf->draw_rectangle(716, 300, 695, 580,         $Xfirstpage, $Xrectparam);
      $Xpdf->draw_text(310, 708, t("Valuutta", $kieli),     $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(310, 698, $laskurow["valkoodi"],     $Xfirstpage, $Xnorm);

      $Xpdf->draw_rectangle(695, 300, 674, 580,         $Xfirstpage, $Xrectparam);
      $Xpdf->draw_text(310, 687, t("Kommentti", $kieli),     $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(310, 677, $laskurow["viesti"],     $Xfirstpage, $Xnorm);

      //rivien otsikkotiedot
      $Xpdf->draw_text(30,  572, t("Nimike", $kieli),      $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(100, 572, t("Kohtelu", $kieli),    $Xfirstpage, $Xpieni);

      if (!in_array($yhtiorow['vienti_erittelyn_tulostus'], array('T', 'P'))) {
        $Xpdf->draw_text(150, 572, t("Tuote", $kieli),      $Xfirstpage, $Xpieni);
      }

      if ($vakkeja) $Xpdf->draw_text(310, 572, t("VAK/UN", $kieli), $Xfirstpage, $Xpieni);

      $Xpdf->draw_text(370, 572, t("Määrä", $kieli),      $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(430, 572, t("Hinta", $kieli),      $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(470, 572, t("Paino", $kieli),      $Xfirstpage, $Xpieni);

      if (!in_array($yhtiorow['vienti_erittelyn_tulostus'], array('I', 'P'))) {
        $Xpdf->draw_text(520, 572, t("Alkuperämaa", $kieli),  $Xfirstpage, $Xpieni);
      }

      return $Xfirstpage;
    }
  }

  if (!function_exists('erittely_rivi')) {
    function erittely_rivi($Xfirstpage) {
      global $Xfirstpage, $yhtiorow, $Xpdf, $Xrow, $Xkala, $Xsivu, $Xlask, $Xrectparam, $Xnorm, $Xpieni, $kieli, $vakkeja;

      if (!is_object($Xpdf)) {
        return;
      }

      if ($Xlask == 36) {
        $Xsivu++;
        erittely_loppu($Xfirstpage);
        $Xfirstpage = erittely_alku();
        $Xkala = 540;
        $Xlask = 1;
      }


      $Xpdf->draw_text(30, $Xkala, $Xrow["tullinimike1"], $Xfirstpage, $Xnorm);
      $Xpdf->draw_text(100, $Xkala, $Xrow["tullikohtelu"], $Xfirstpage, $Xnorm);

      if ($vakkeja) {

        if (!in_array($yhtiorow['vienti_erittelyn_tulostus'], array('T', 'P'))) {
          list($ff_string, $ff_font) = pdf_fontfit($Xrow["tuoteno"], 155, $Xpdf, $Xnorm);
          $Xpdf->draw_text(150, $Xkala, $ff_string, $Xfirstpage, $ff_font);
        }

        $_vak_un = palauta_tuotteen_vak_koodi($Xrow["tuoteno"]);

        if ($_vak_un != "") {
          $oikpos = $Xpdf->strlen($_vak_un, $Xnorm);
          $Xpdf->draw_text(340 - $oikpos, $Xkala, $_vak_un, $Xfirstpage, $Xnorm);
        }
      }
      elseif (!in_array($yhtiorow['vienti_erittelyn_tulostus'], array('T', 'P'))) {
        list($ff_string, $ff_font) = pdf_fontfit($Xrow["tuoteno"], 200, $Xpdf, $Xnorm);
        $Xpdf->draw_text(150, $Xkala, $ff_string, $Xfirstpage, $ff_font);
      }

      $oikpos = $Xpdf->strlen($Xrow["kpl"], $Xnorm);
      $Xpdf->draw_text(390-$oikpos, $Xkala, $Xrow["kpl"],   $Xfirstpage, $Xnorm);

      $Xrow["summa"] = sprintf("%.2f", $Xrow["summa"]);

      $oikpos = $Xpdf->strlen($Xrow["summa"], $Xnorm);
      $Xpdf->draw_text(450-$oikpos, $Xkala, $Xrow["summa"],   $Xfirstpage, $Xnorm);

      // Vientierittelyn painot tuotteelta
      if ($yhtiorow['vientierittelyn_painot'] == 'T') {
        $_paino = $Xrow["tuote_paino"];
      }
      else {
        $_paino = $Xrow["rahtikirja_paino"];
      }

      $Xpdf->draw_text(470, $Xkala, $_paino, $Xfirstpage, $Xnorm);

      if ($yhtiorow["vienti_erittelyn_tulostus"] != "I") {
        $Xpdf->draw_text(520, $Xkala, $Xrow["alkuperamaa"],  $Xfirstpage, $Xnorm);
      }

      $Xkala = $Xkala - 13;
      $Xlask++;
    }
  }

  if (!function_exists('erittely_loppu')) {
    function erittely_loppu($Xfirstpage) {
      global $Xpdf, $yhtiorow, $Xsivu, $Xrectparam, $Xnorm, $Xpieni, $kieli;

      if (!is_object($Xpdf)) {
        return;
      }

      //Alimmat kolme laatikkoa, yhtiötietoja
      $Xpdf->draw_rectangle(55, 20, 20, 580,              $Xfirstpage, $Xrectparam);
      $Xpdf->draw_rectangle(55, 207, 20, 580,              $Xfirstpage, $Xrectparam);
      $Xpdf->draw_rectangle(55, 394, 20, 580,              $Xfirstpage, $Xrectparam);

      $Xpdf->draw_text(30, 45, $yhtiorow["nimi"],            $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(30, 35, $yhtiorow["osoite"],          $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(30, 25, $yhtiorow["postino"]."  ".$yhtiorow["postitp"]."  ".$yhtiorow["maa"],  $Xfirstpage, $Xpieni);

      $Xpdf->draw_text(217, 45, t("Puhelin", $kieli).":",        $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(257, 45, $yhtiorow["puhelin"],          $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(217, 35, t("Fax", $kieli).":",        $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(247, 35, $yhtiorow["fax"],            $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(217, 25, t("Sähköposti", $kieli).": ".$yhtiorow["email"], $Xfirstpage, $Xpieni);

      $Xpdf->draw_text(404, 45, t("Y-tunnus", $kieli).":",      $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(454, 45, $yhtiorow["maa"]."-".$yhtiorow["ytunnus"],  $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(404, 35, t("Kotipaikka", $kieli).":",      $Xfirstpage, $Xpieni);
      $Xpdf->draw_text(444, 35, $yhtiorow["kotipaikka"],        $Xfirstpage, $Xpieni);
    }
  }

  // Onko vakkeja?
  $vakkeja = FALSE;

  while ($Xrow = mysql_fetch_array($Xriviresult)) {
    if (!empty($Xrow['vakkoodi']) or !empty($Xrow['vak_imdg_koodi'])) {
      $vakkeja = TRUE;
      break;
    }
  }

  mysql_data_seek($Xriviresult, 0);

  ///* Tehdään lista *///
  $Xfirstpage = erittely_alku();

  while ($Xrow = mysql_fetch_array($Xriviresult)) {
    erittely_rivi($Xfirstpage);
  }

  erittely_loppu($Xfirstpage);
}
else {
  echo "<font class='error'>".t("Tilausta ei löytynyt")."!<br>$Xquery<br></font>";
}
