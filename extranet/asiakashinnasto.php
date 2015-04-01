<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 2;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee_lataa"])) {
  if ($_POST["tee_lataa"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (isset($tee_lataa)) {
  if ($tee_lataa == "lataa_tiedosto") {
    readfile("/tmp/".basename($tmpfilenimi));
    exit;
  }
}
else {

  echo "<font class='head'>".t("Hinnasto asiakashinnoin")."</font><hr>";

  $ytunnus = trim($ytunnus);

  if ($tee != '' and $ytunnus != '' and $kukarow["extranet"] == '') {

    if (isset($muutparametrit)) {
      $muutparametrit = unserialize(urldecode($muutparametrit));
      $mul_osasto   = $muutparametrit[0];
      $mul_try     = $muutparametrit[1];
    }

    $muutparametrit = array($mul_osasto, $mul_try);
    $muutparametrit = urlencode(serialize($muutparametrit));

    require "inc/asiakashaku.inc";

    $asiakas = $asiakasrow["tunnus"];
    $ytunnus = $asiakasrow["ytunnus"];
  }
  elseif ($tee != '' and $kukarow["extranet"] != '') {
    //Haetaan asiakkaan tunnuksella
    $query  = "SELECT *
               FROM asiakas
               WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $asiakasrow = mysql_fetch_array($result);

      $ytunnus = $asiakasrow["ytunnus"];
      $asiakas = $asiakasrow["tunnus"];
    }
    else {
      echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
      exit;
    }
  }

  //Käyttöliittymä
  echo "<br>";
  echo "<table><form method='post'>";
  echo "<input type='hidden' name='tee' value='kaikki'>";

  if ($kukarow["extranet"] == '') {
    if ($asiakas > 0) {
      echo "<tr><th>".t("Asiakas").":</th><td><input type='hidden' name='ytunnus' value='$ytunnus'>$ytunnus $asiakasrow[nimi]</td></tr>";

      echo "<input type='hidden' name='asiakasid' value='$asiakas'></td></tr>";
    }
    else {
      echo "<tr><th>".t("Asiakas").":</th><td><input type='text' name='ytunnus' size='15' value='$ytunnus'></td></tr>";
    }

    echo "<tr><th>".t("Kieli").":</th><td><select name='hinkieli'>";

    foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
      if (strlen($sanakirja_kieli) == 2) {
        $sel = "";

        if ($hinkieli == $sanakirja_kieli) {
          $sel = "SELECTED";
        }
        elseif ($asiakasrow["kieli"] == $sanakirja_kieli and $hinkieli == "") {
          $sel = "SELECTED";
        }

        echo "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
      }
    }

    echo "</select></td></tr>";

    if (!isset($ryhmittely)) {
      $ryhmittely = 1;
    }

    if ($ryhmittely == 1) {
      $sel1 = "SELECTED";
      $sel2 = "";
      $tuoteryhmaosasto = false;
      $ryhmittelylisa = $selectlisa = "";
    }

    if (!isset($hinkieli)) {
      $hinkieli = $yhtiorow['kieli'];
    }

    if ($ryhmittely == 2) {
      $sel2 = "SELECTED";
      $sel1 = "";
      $tuoteryhmaosasto = true;
      $ryhmittelylisa = " JOIN tuotteen_avainsanat
                            ON tuotteen_avainsanat.yhtio = tuote.yhtio
                            AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                            AND tuotteen_avainsanat.laji = 'hinnastoryhmittely'
                          JOIN avainsana
                            ON avainsana.yhtio = tuote.yhtio
                            AND avainsana.kieli = '{$hinkieli}'
                            AND LOCATE(avainsana.selite, tuotteen_avainsanat.selite) > 0
                            AND avainsana.laji = 'THR'  ";
      $selectlisa = ", tuotteen_avainsanat.selite AS ryhmittely ";
    }

    // näytetään vain jos tuoteryhmittelyjä ja ryhmiä on perustettu
    $tarkistu1 = "SELECT count(tunnus)
                  FROM tuotteen_avainsanat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji    = 'hinnastoryhmittely'";
    $tarkistu1 = pupe_query($tarkistu1);
    $tarkistu1 = mysql_result($tarkistu1, 0);

    $tarkistu2 = "SELECT count(tunnus)
                  FROM avainsana
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji    = 'THR'";
    $tarkistu2 = pupe_query($tarkistu2);
    $tarkistu2 = mysql_result($tarkistu2, 0);

    if ($tarkistu1 > 0 and $tarkistu2 > 0) {
      echo "<tr><th>".t("Esitystapa").":</th><td><select name='ryhmittely'>";
      echo "<option value='1' $sel1>".t("Normaali")."</option>";
      echo "<option value='2' $sel2>".t("Tuotehinnastoryhmittäin")."</option>";
      echo "</select></td></tr>";
    }
  }
  else {
    $hinkieli = $kukarow["kieli"];
  }

  // Monivalintalaatikot (osasto, try tuotemerkki...)
  // Määritellään mitkä latikot halutaan mukaan
  $monivalintalaatikot = array("OSASTO", "TRY");

  echo "<tr><th>".t("Osasto")." / ".t("tuoteryhmä").":</th><td nowrap>";

  if (@include "tilauskasittely/monivalintalaatikot.inc");
  elseif (@include "monivalintalaatikot.inc");

  echo "</td></tr>";
  echo "</table><br>";
  echo "<input type='submit' name='ajahinnasto' value='".t("Aja hinnasto")."'>";
  echo "</form>";

  if ($kukarow["extranet"] == '' and $asiakas > 0) {
    echo "<form method='post'>";
    echo "<input type='submit' value='Valitse uusi asiakas'>";
    echo "</form>";
  }

  if ($tee != '' and $asiakas > 0 and isset($ajahinnasto)) {

    $kieltolisa   = '';
    $sallitut_maat   = $asiakasrow["toim_maa"] != '' ? $asiakasrow["toim_maa"] : $asiakasrow["maa"];

    if ($sallitut_maat != "") {
      $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$sallitut_maat%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$sallitut_maat%' ";
    }

    $query = "SELECT kurssi
              FROM valuu
              WHERE nimi = '$asiakasrow[valkoodi]'
              and yhtio  = '$kukarow[yhtio]'";
    $asres = pupe_query($query);
    $kurssi = mysql_fetch_assoc($asres);

    $query = "SELECT tuote.*{$selectlisa}
              FROM tuote
              {$ryhmittelylisa}
              WHERE tuote.yhtio      = '{$kukarow['yhtio']}'
              and tuote.status       NOT IN ('P','X')
              and tuote.tuotetyyppi  NOT IN ('A', 'B')
              and tuote.hinnastoon  != 'E'
              {$kieltolisa}
              {$lisa}
              GROUP BY tuote.tunnus";
    $rresult = pupe_query($query);

    if (mysql_num_rows($rresult) == 0) {

      $osuma = false;

    }
    else {

      // KAUTTALASKUTUSKIKKARE
      if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and ($GLOBALS['koti_yhtio'] != $kukarow['yhtio'] or $asiakasrow['osasto'] != '6')) {
        $GLOBALS['eta_yhtio'] = "";
      }
      elseif (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '') {
        // haetaan etäyhtiön tiedot
        $yhtiorow_eta = $yhtiorow = hae_yhtion_parametrit($GLOBALS['eta_yhtio']);
      }

      echo "<br><br><font class='message'>".t("Asiakashinnastoa luodaan...")."</font><br>";
      flush();

      require_once 'inc/ProgressBar.class.php';
      $bar = new ProgressBar();
      $elements = mysql_num_rows($rresult); // total number of elements to process
      $bar->initialize($elements); // print the empty bar

      include 'inc/pupeExcel.inc';

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake = 0;

      if (isset($worksheet)) {
        $worksheet->writeString($excelrivi,  0, t("Ytunnus", $hinkieli).": $ytunnus", $format_bold);
        $excelrivi++;

        $worksheet->writeString($excelrivi,  0, t("Asiakas", $hinkieli).": $asiakasrow[nimi] $asiakasrow[nimitark]", $format_bold);
        $excelrivi++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotenumero", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("EAN-koodi", $hinkieli), $format_bold);
        $excelsarake++;

        if (!$tuoteryhmaosasto) {
          $worksheet->writeString($excelrivi, $excelsarake, t("Osasto", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Tuoteryhmä", $hinkieli), $format_bold);
          $excelsarake++;
        }

        $worksheet->writeString($excelrivi, $excelsarake, t("Nimitys", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierä", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Yksikkö", $hinkieli), $format_bold);
        $excelsarake++;

        if (!$tuoteryhmaosasto) {
          $worksheet->writeString($excelrivi, $excelsarake, t("Status", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Aleryhmä", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Veroton Myyntihinta", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Verollinen Myyntihinta", $hinkieli), $format_bold);
          $excelsarake++;
        }
        else {
          $worksheet->writeString($excelrivi, $excelsarake, t("Veroton Myyntihinta", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Verollinen Myyntihinta", $hinkieli), $format_bold);
          $excelsarake++;
        }

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $worksheet->writeString($excelrivi, $excelsarake, t("Alennus{$alepostfix}", $hinkieli), $format_bold);
          $excelsarake++;
        }

        if (!$tuoteryhmaosasto) {
          $worksheet->writeString($excelrivi, $excelsarake, t("Sinun verollinen hinta", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Sinun veroton hinta", $hinkieli), $format_bold);
          $excelsarake++;
        }
        else {
          $worksheet->writeString($excelrivi, $excelsarake, t("Verollinen asiakashinta", $hinkieli), $format_bold);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t("Veroton asiakashinta ", $hinkieli), $format_bold);
          $excelsarake++;
        }
        $excelrivi++;
      }

      if ($tuoteryhmaosasto) {
        $tro = '';
      }

      $rivit = array();

      while ($rrow = mysql_fetch_assoc($rresult)) {

        if ($tuoteryhmaosasto) {

          $trot = explode(",", $rrow['ryhmittely']);

          foreach ($trot as $perhe) {

            $jq = "SELECT jarjestys, selitetark
                   FROM avainsana
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND laji    = 'THR'
                   AND selite  = '{$perhe}'
                   AND kieli   = '{$hinkieli}'";
            $jr = pupe_query($jq);

            if (mysql_num_rows($jr) != 0) {

              $info = mysql_fetch_assoc($jr);

              $jarjestys = $info['jarjestys'];
              $ryhma = $info['selitetark'];

              $kopiorivi = $rrow;
              $kopiorivi['tro'] = $ryhma;
              $kopiorivi['jar'] = $jarjestys;
              $rivit[] = $kopiorivi;
            }
          }
        }
        else {

          $rivit[] = $rrow;
        }
      }

      if ($tuoteryhmaosasto) {

        $sort = array();
        foreach ($rivit as $key => $rivi) {
          $sort1[$key] = $rivi['jar'];
          $sort2[$key] = $rivi['tro'];
        }
        array_multisort($sort1, SORT_ASC, $sort2, SORT_ASC, $rivit);
      }

      foreach ($rivit as $rrow) {

        $bar->increase();

        if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '{$GLOBALS["eta_yhtio"]}'
                    AND tuoteno = '$rrow[tuoteno]'";
          $tres_eta = pupe_query($query);
          $alehinrrow = mysql_fetch_assoc($tres_eta);
          $yhtiorow = $yhtiorow_eta;
        }
        else {
          $alehinrrow = $rrow;
        }

        //haetaan asiakkaan oma hinta
        $laskurow["ytunnus"]     = $asiakasrow["ytunnus"];
        $laskurow["liitostunnus"]   = $asiakasrow["tunnus"];
        $laskurow["vienti"]     = $asiakasrow["vienti"];
        $laskurow["alv"]       = $asiakasrow["alv"];
        $laskurow["valkoodi"]    = $asiakasrow["valkoodi"];
        $laskurow["vienti_kurssi"]  = $kurssi;
        $laskurow["maa"]      = $asiakasrow["maa"];
        $laskurow['toim_ovttunnus'] = $asiakasrow["toim_ovttunnus"];

        $palautettavat_kentat = "hinta,netto,alehinta_alv,alehinta_val,hintaperuste,aleperuste";

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $palautettavat_kentat .= ",ale{$alepostfix}";
        }

        $hinnat = alehinta($laskurow, $alehinrrow, 1, '', '', '', $palautettavat_kentat, $GLOBALS['eta_yhtio']);

        // Kauttalaskutuksessa pitää otaa etäyhtiön tiedot
        if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
          $yhtiorow = $yhtiorow_eta;
        }

        // Otetaan erikoisalennus pois asiakashinnastosta
        // $hinnat['erikoisale'] = $asiakasrow["erikoisale"];
        $hinnat['erikoisale'] = 0;

        $hinta = $hinnat["hinta"];
        $netto = $hinnat["netto"];

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = $hinnat["ale{$alepostfix}"];
        }

        $alehinta_alv  = $hinnat["alehinta_alv"];
        $alehinta_val  = $hinnat["alehinta_val"];

        list($hinta, $lis_alv) = alv($laskurow, $rrow, $hinta, '', $alehinta_alv);

        $onko_asiakkaalla_alennuksia = FALSE;

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
            $onko_asiakkaalla_alennuksia = TRUE;
            break;
          }
        }

        // Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
        if ($rrow["hinnastoon"] == "V" and (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE)) {
          continue;
        }
        else {
          $osuma = true;
        }

        if ((float) $hinta == 0) {
          $hinta = $rrow["myyntihinta"];
        }

        if ($netto == "") {
          $alennukset = generoi_alekentta_php($hinnat, 'M', 'kerto');

          $asiakashinta = hintapyoristys($hinta * $alennukset);
        }
        else {
          $asiakashinta = $hinta;
        }

        $veroton         = 0;
        $verollinen        = 0;
        $asiakashinta_veroton    = 0;
        $asiakashinta_verollinen = 0;

        if ($yhtiorow["alv_kasittely"] == "") {
          // Hinnat sisältävät arvonlisäveron
          $verollinen         = $rrow["myyntihinta"];
          $veroton         = round(($rrow["myyntihinta"]/(1+$rrow['alv']/100)), 2);
          $asiakashinta_veroton    = round(($asiakashinta/(1+$lis_alv/100)), 2);
          $asiakashinta_verollinen = $asiakashinta;
        }
        else {
          // Hinnat ovat nettohintoja joihin lisätään arvonlisävero
          $verollinen        = round(($rrow["myyntihinta"]*(1+$rrow['alv']/100)), 2);
          $veroton         = $rrow["myyntihinta"];
          $asiakashinta_veroton    = $asiakashinta;
          $asiakashinta_verollinen = round(($asiakashinta*(1+$lis_alv/100)), 2);
        }

        if (isset($worksheet)) {

          $excelsarake = 0;

          if (isset($tro) and $tro != $rrow['tro']) {
            $excelrivi++;
            $worksheet->writeString($excelrivi, 0, $rrow["tro"], $format_bold);
            $excelrivi++;
          }

          $worksheet->writeString($excelrivi, $excelsarake, $rrow["tuoteno"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["eankoodi"]);
          $excelsarake++;

          if (!$tuoteryhmaosasto) {
            $worksheet->writeString($excelrivi, $excelsarake, $rrow["osasto"]);
            $excelsarake++;
            $worksheet->writeString($excelrivi, $excelsarake, $rrow["try"]);
            $excelsarake++;
          }

          $worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($rrow, 'nimitys', $hinkieli));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["myynti_era"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t_avainsana("Y", $hinkieli, "and avainsana.selite='$rrow[yksikko]'", "", "", "selite"));
          $excelsarake++;

          if (!$tuoteryhmaosasto) {
            $worksheet->writeString($excelrivi, $excelsarake, $rrow["status"]);
            $excelsarake++;
            $worksheet->writeString($excelrivi, $excelsarake, $rrow["aleryhma"]);
            $excelsarake++;
            $worksheet->writeNumber($excelrivi, $excelsarake, $veroton);
            $excelsarake++;
            $worksheet->writeNumber($excelrivi, $excelsarake, $verollinen);
            $excelsarake++;
          }
          else {
            $worksheet->writeNumber($excelrivi, $excelsarake, $veroton);
            $excelsarake++;
            $worksheet->writeNumber($excelrivi, $excelsarake, $verollinen);
            $excelsarake++;
          }

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            if ($netto != "") {
              $worksheet->writeString($excelrivi, $excelsarake, t("Netto", $hinkieli));
              $excelsarake++;
            }
            else {
              $worksheet->writeNumber($excelrivi, $excelsarake, sprintf('%.2f', ${'ale'.$alepostfix}));
              $excelsarake++;
            }
          }

          $worksheet->writeNumber($excelrivi, $excelsarake, hintapyoristys($asiakashinta_verollinen));
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, hintapyoristys($asiakashinta_veroton));
          $excelsarake++;
          $excelrivi++;
        }

        if ($tuoteryhmaosasto) {
          $tro = $rrow['tro'];
        }
      }

    }


    if ($osuma == false) {

      echo "<br><br><font class='error'>".t("Valitulla rajauksella ei löydy tuotteita!")."</font><br>";

    }
    elseif (isset($worksheet)) {

      $excelnimi = $worksheet->close();

      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna hinnasto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee_lataa' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".t("Asiakashinnasto").".xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";
    }

  }

  if (@include "inc/footer.inc");
  elseif (@include "footer.inc");
  else exit;
}
