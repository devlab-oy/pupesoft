<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 2;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee_lataa"])) {
  if ($_POST["tee_lataa"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (@include "inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (isset($tee_lataa)) {
  if ($tee_lataa == "lataa_tiedosto") {
    readfile("/tmp/".basename($tmpfilenimi));
    exit;
  }
}
else {

  echo "<font class='head'>".t("Varastosaldot ja hinnasto asiakashinnoin")."</font><hr>";

  if ($ytunnus != '') {
    $ytunnus = trim($ytunnus);
    require_once "inc/asiakashaku.inc";
    
    $asiakas = $asiakasrow["tunnus"];
    $ytunnus = $asiakasrow["ytunnus"];
  }

  //Käyttöliittymä
  echo "<br>";
  echo "<table><form method='post'>";
  echo "<input type='hidden' name='tee' value='kaikki'>";


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
  
  if (!isset($hinkieli)) {
    $hinkieli = $yhtiorow['kieli'];
  }
  
  // Monivalintalaatikot (osasto, try tuotemerkki...)
  // Määritellään mitkä latikot halutaan mukaan
  $monivalintalaatikot = array("TRY", "TUOTEMERKKI");

  echo "<tr><th>".t("tuoteryhmät joita ei huomioida").":</th><td nowrap>";

  require_once "tilauskasittely/monivalintalaatikot.inc";
  echo "</td></tr>";
  
  $query  = "SELECT tunnus, nimitys
             FROM varastopaikat
             WHERE yhtio = '$kukarow[yhtio]'
             AND tyyppi  = ''
             ORDER BY tyyppi, nimitys";
  $vares = pupe_query($query);

  echo "<tr>
      <th valign=top>".t('Varastorajaus').":</th>
      <td>";

  $varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

  while ($varow = mysql_fetch_assoc($vares)) {
    $sel = '';
    if (in_array($varow['tunnus'], $varastot)) {
      $sel = 'checked';
    }

    echo "<input type='checkbox' name='varastot[]' class='shift' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
  }
  #echo "</table><br>";
  $valitut_varastot = implode(",", $varastot);

  $ale_kaikki_array = array();

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    $ale_kaikki_array['ale'.$alepostfix] = ${'ale'.$alepostfix};
  }
  #echo "<table>";
  echo "<tr>";
  echo "<th>".t("Valitse tuotteet").":</th>";
  echo "<td><select name='nayta'>";
  echo "<option value='normaalit' $sel1[normaalit]>".t("Normaalit tuotteet")."</option>";
  echo "<option value='zeniorparts' $sel1[zeniorparts]>".t("Zenior parts tuotteet")."</option>";
  echo "</select></td>";
  echo "</tr>";

  echo "<tr><th>".t("Käytettävä kateprosentti").":</th><td><input type='text' name='katepros' size='15' value='$katepros'></td></tr>";
  
  echo "</table><br>";
  echo "<input type='submit' name='ajatiedosto' value='".t("Aja Tiedosto")."'>";
  echo "</form>";
  
  //Pilkut pisteiksi
  $katepros = str_replace(',', '.', $katepros);
  
  if ( $asiakas > 0) {
    echo "<form method='post'>";
    echo "<input type='submit' value='Valitse uusi asiakas'>";
    echo "</form>";
  }

  if ($tee != '' and $asiakas > 0 and isset($ajatiedosto)) {

    $zeniorparts_lisa = ($nayta == "normaalit") ? "" : " JOIN tuotteen_avainsanat ON (tuote.yhtio = tuotteen_avainsanat.yhtio AND tuotteen_avainsanat.kieli = '{$yhtiorow['kieli']}' AND tuotteen_avainsanat.laji = 'zeniorparts' AND tuote.tuoteno = tuotteen_avainsanat.tuoteno)";
    $lisa = str_replace(' in ', ' not in ', $lisa);

    $query = "SELECT *
              FROM tuote
              {$zeniorparts_lisa}
              WHERE tuote.yhtio      = '{$kukarow['yhtio']}'
              and tuote.status       NOT IN ('E','P', 'T', 'X')
              and tuote.tuotetyyppi  NOT IN ('A', 'B')
              and tuote.ei_saldoa    = ''
              {$lisa}";
    $rresult = pupe_query($query);

    if (mysql_num_rows($rresult) == 0) {
      $osuma = false;
    }
    else {

      echo "<br><br><font class='message'>".t("Asiakashinnastoa luodaan...")."</font><br>";
      flush();

      require_once "inc/ProgressBar.class.php";

      $bar = new ProgressBar();
      $elements = mysql_num_rows($rresult); // total number of elements to process
      $bar->initialize($elements); // print the empty bar

      require_once "inc/pupeExcel.inc";

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake = 0;

      if (isset($worksheet)) {
        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotenumero", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotemerkki", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Nimitys", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Saldo", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Toim. tuotekoodi", $hinkieli), $format_bold);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, t("Asiakashinta", $hinkieli), $format_bold);
        $excelsarake++;
        
        $excelrivi++;
      }

      $rivit = array();

      while ($row = mysql_fetch_assoc($rresult)) {
        $bar->increase();
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row['tuoteno'],'' ,$varastot);

        $kopiorivi = $row;
        $kopiorivi['saldo'] = ($myytavissa > 4) ? 4 : $myytavissa;

        if ($myytavissa > 0) {
          $rivit[] = $kopiorivi;
        }
      }
  
      foreach ($rivit as $rrow) {

        $bar->increase();

        if ($nayta == "normaalit") {
          if ($rrow["kehahin"] > 0) {
            $hinta = round($rrow["kehahin"] * ((100 + $katepros) / 100), 2);
            
          }
        }
        else {
          $laskurow = array();
          //haetaan asiakkaan oma hinta
          $laskurow["ytunnus"]        = $ytunnus;
          $laskurow["liitostunnus"]   = $asiakasrow["tunnus"];
          $laskurow["vienti"]         = $asiakasrow["vienti"];
          $laskurow["alv"]            = $asiakasrow["alv"];
          $laskurow["valkoodi"]       = $asiakasrow["valkoodi"];
          $laskurow["vienti_kurssi"]  = $yhtiorow['kurssi'];
          $laskurow["maa"]            = $asiakasrow["maa"];
          $laskurow['toim_ovttunnus'] = $asiakasrow["toim_ovttunnus"];
          
          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $palautettavat_kentat .= ",ale{$alepostfix}";
          }

          $hinnat = alehinta($laskurow, $alehinrrow, 1, '', '', '', '', '');

          list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rrow, 1, '', '', '', '', '');
          
          $alennukset = generoi_alekentta_php($ale, 'M', 'kerto');

          $hinta = round($hinta * $alennukset, 2);
        }

        $query = "SELECT toim_tuoteno,
                          if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                          FROM tuotteen_toimittajat
                          WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
                          and tuotteen_toimittajat.tuoteno = '{$rrow['tuoteno']}'
                          ORDER BY sorttaus
                          LIMIT 1";
        $tresult = pupe_query($query);
        
        if (mysql_num_rows($tresult) != 1) {
          $toimtuote = '';
        }
        else {
          $ttrow = mysql_fetch_assoc($tresult);
          $toimtuote = $ttrow["toim_tuoteno"];
        }
        
        
        if (isset($worksheet)) {

          $excelsarake = 0;

          $worksheet->writeString($excelrivi, $excelsarake, $rrow["tuoteno"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["tuotemerkki"]);
          $excelsarake++;

          $worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($rrow, 'nimitys', $hinkieli));
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, $rrow["saldo"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $toimtuote);
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, $hinta);
          $excelsarake++;

          $excelrivi++;
        }
      }
    }

    if (isset($worksheet)) {

      $excelnimi = $worksheet->close();

      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna tiedosto").":</th>";
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
