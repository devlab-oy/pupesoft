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

  $ytunnus = trim($ytunnus);
   require "inc/asiakashaku.inc";

  $asiakas = $asiakasrow["tunnus"];
  $ytunnus = $asiakasrow["ytunnus"];

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
  
  $ale_kaikki_array = array();

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    $ale_kaikki_array['ale'.$alepostfix] = ${'ale'.$alepostfix};
  }
  echo "<table";
  echo "<tr>";
  echo "<th>".t("Valitse tuotteet").":</th>";
  echo "<td><select name='nayta'>";
  echo "<option value='normaalit' $sel1[normaalit]>".t("Normaalit tuotteet")."</option>";
  echo "<option value='zeniorparts' $sel1[zeniorparts]>".t("Zenior parts tuotteet")."</option>";
  echo "</select></td>";
  echo "</tr>";

  echo "</td></tr>";
  echo "</table><br>";
  echo "<input type='submit' name='ajatiedosto' value='".t("Aja Tiedosto")."'>";
  echo "</form>";

  if ( $asiakas > 0) {
    echo "<form method='post'>";
    echo "<input type='submit' value='Valitse uusi asiakas'>";
    echo "</form>";
  }

  if ($tee != '' and $asiakas > 0 and isset($ajatiedosto)) {

      $aquery = "SELECT avainsana.selitetark selitetark
               FROM avainsana
               WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='ASCSV_EIMERKKI'
               and avainsana.kieli = '$yhtiorow[kieli]'
               LIMIT 1";
      $aresult = pupe_query($aquery);
      
      if (mysql_num_rows($aresult) != 1) {
        $ohitettavat_brandit = '';
      }
      else {
        $arow = mysql_fetch_assoc($aresult);
        $ohita_brandit = explode(",", $arow["selitetark"]);
        $ohitettavat_brandit = " and tuote.tuotemerkki not in ('".implode("','", $ohita_brandit)."')";
      }

    $ohitettavat_brandit = " and tuote.tuotemerkki not in ('NF Parts','OE','OEM')";
    $ohitettavat_tryt = " and (tuote.try not in ('0','999','999999','1000002','1000003','400001','400002')
    and tuote.try < '200101' and tuote.try < '201112')";
    $zeniorparts_lisa = ($nayta == "normaalit") ? "" : " JOIN tuotteen_avainsanat ON (tuote.yhtio = tuotteen_avainsanat.yhtio AND tuotteen_avainsanat.kieli = '{$yhtiorow['kieli']}' AND tuote.tuoteno = tuotteen_avainsanat.tuoteno AND tuotteen_avainsanat.laji = 'zeniorparts')";

    $query = "SELECT *
              FROM tuote
              {$zeniorparts_lisa}
              WHERE tuote.yhtio      = '{$kukarow['yhtio']}'
              and tuote.status       NOT IN ('P','X')
              and tuote.tuotetyyppi  NOT IN ('A', 'B')
              {$ohitettavat_brandit}
              {$ohitettavat_tryt}";
    $rresult = pupe_query($query);

    if (mysql_num_rows($rresult) == 0) {
      $osuma = false;
    }
    else {

      echo "<br><br><font class='message'>".t("Asiakashinnastoa luodaan...")."</font><br>";
      flush();

      if (@require_once "inc/ProgressBar.class.php");
      elseif (@require_once "ProgressBar.class.php");

      $bar = new ProgressBar();
      $elements = mysql_num_rows($rresult); // total number of elements to process
      $bar->initialize($elements); // print the empty bar

      if (@include "inc/pupeExcel.inc");
      elseif (@include "pupeExcel.inc");

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

      while ($rrow = mysql_fetch_assoc($rresult)) {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($rrow['tuoteno']);

        $kopiorivi = $rrow;
        $kopiorivi['saldo'] = ($myytavissa > 4) ? 4 : $myytavissa;

        $rivit[] = $kopiorivi;

      }
  
      foreach ($rivit as $rrow) {

        $bar->increase();

        if ($nayta == "normaalit") {
          if ($rrow["kehahin"] > 0) {
            $hinta = round($rrow["kehahin"] * 1.17, 2);
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
