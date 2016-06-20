<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

// DataTables p‰‰lle
$pupe_DataTables = 'vartiltaul';

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

$vvl = date("Y");
if (!isset($nayta_vapaa_saldo)) $nayta_vapaa_saldo = "";

echo "<font class=head>".t("Varastotilasto")." $vvl</font><hr>";

if ($ytunnus != '') {

  if ($valittuytunnus != "" and $valittuytunnus != $ytunnus) $toimittajaid = "";

  require "inc/kevyt_toimittajahaku.inc";

  // Toimittaja lˆytyi
  if ($toimittajaid == 0) {
    $tee = "";
  }
}
else {
  $ytunnus = "";
  $toimittajaid = "";
}

// k‰yttis
echo "<form method='POST'>";
echo "<input type='hidden' name='tee' value='raportoi'>";

echo "<table>";

if ($toim == "KASSA" and !isset($painoinnappia)) {
  $listaustyyppi = "eimyyntia";
}

$sel = array_fill_keys(array($listaustyyppi), " SELECTED") + array_fill_keys(array('kappaleet', 'hinnat', 'kappaleet2','eimyyntia'), '');

echo "<tr>";
echo "<th>".t("Listaustyyppi")."</th>";
echo "<td>";
echo "<select name='listaustyyppi'>";
echo "<option value = 'kappaleet'{$sel['kappaleet']}>".t("Listauksessa n‰ytet‰‰n myynti kappaleina")."</option>";
echo "<option value = 'hinnat'{$sel['hinnat']}>".t("Listauksessa n‰ytet‰‰n myynti euroina")."</option>";
echo "<option value = 'kappaleet2'{$sel['kappaleet2']}>".t("Listauksessa n‰ytet‰‰n myynti ja kulutus kappaleina")."</option>";
echo "<option value = 'eimyyntia'{$sel['eimyyntia']}>".t("Listauksessa ei n‰ytet‰ myyntej‰ eik‰ kulutuksia")."</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr><th>".t("Rajaukset")."</th><td>";

$monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
require "tilauskasittely/monivalintalaatikot.inc";

echo "</td></tr>";

if (empty($oletusvarasto_chk)) {
  // Varastot
  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio = '{$kukarow["yhtio"]}'";
  $result = pupe_query($query);

  $varastot = array();

  while ($varasto = mysql_fetch_assoc($result)) {
    array_push($varastot, $varasto);
  }

  // Varaston valinta
  echo "<tr>";
  echo "<th>".t("Varasto")."</th>";
  echo "<td>";
  echo "<select name='valitut_varastot[]' multiple='multiple' class='multipleselect' size='7'>";

  if ($toim == "KASSA" and !empty($kukarow['oletus_varasto']) and !isset($painoinnappia)) {
    $valitut_varastot = array($kukarow['oletus_varasto']);
  }

  foreach ($varastot as $varasto) {
    $selected = in_array($varasto["tunnus"], $valitut_varastot) ? "selected" : "";
    echo "<option value='{$varasto["tunnus"]}' {$selected}>{$varasto["nimitys"]}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";
}

echo "<tr>";
echo "<th>".t("Toimittaja")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'> ";
echo "{$toimittajarow["nimi"]} {$toimittajarow["nimitark"]} {$toimittajarow["postitp"]}";

echo "<input type='hidden' name='valittuytunnus' value='$ytunnus'>";
echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
echo "</td>";
echo "</tr>";

echo "<tr><th>".t("Piilota nollarivit")."</th>";

if ($toim == "KASSA" and !isset($painoinnappia)) {
  $nollapiilo = "vainsaldo";
}

$sel = array_fill_keys(array($nollapiilo), " SELECTED") + array_fill_keys(array('', 'einollia', 'vainsaldo'), '');

echo "<td>";
echo "<select name='nollapiilo'>";
echo "<option value = ''>".t("N‰ytet‰‰n kaikki rivit")."</option>";
echo "<option value = 'einollia'{$sel['einollia']}>".t("Piilotetaan nollarivit")."</option>";
echo "<option value = 'vainsaldo'{$sel['vainsaldo']}>".t("N‰ytet‰‰n vain saldolliset tuotteet")."</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

// Checkbox, jolla valitaan naytentaanko vapaa saldo vai ei
echo "<tr>";
echo "<th>";
echo t("N‰yt‰ vapaa saldo");
echo "</th>";

$checked = (isset($nayta_vapaa_saldo) and $nayta_vapaa_saldo == "on") ? "checked" : "";
echo "<td><input type='checkbox' name='nayta_vapaa_saldo' {$checked}/></td>";
echo "</tr>";

echo "</table>";
echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
echo "</form>";
echo "<br><br>";

if ($toim == "" and $tee != "" and isset($painoinnappia) and $lisa == "" and $toimittajaid == "") {
  echo "<font class='error'>", t("Anna jokin rajaus"), "!</font>";
  $tee = "";
}

if ($tee != "" and isset($painoinnappia)) {

  if ($nollapiilo == "vainsaldo") {
    $saldolisa = " AND tuotepaikat.saldo != 0 ";
  }
  else {
    $saldolisa = " AND (tuote.status != 'P' OR (  SELECT sum(tuotepaikat.saldo)
                            FROM tuotepaikat
                            WHERE tuotepaikat.yhtio = tuote.yhtio
                            AND tuotepaikat.tuoteno = tuote.tuoteno
                            AND tuotepaikat.saldo   > 0) > 0) ";
  }

  if (isset($valitut_varastot)) {
    $varastot = join(",", $valitut_varastot);

    $varasto_tp_filter = " AND tuotepaikat.varasto in ({$varastot}) ";
    $varasto_tilausrivi_filter = "AND tilausrivi.varasto in ({$varastot})";
  }
  else {
    $varasto_tp_filter = "";
    $varasto_tilausrivi_filter = "";
  }

  if ($toimittajaid != "") {
    $toimittaja_join = "  JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio
                AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
                AND tuotteen_toimittajat.liitostunnus = '$toimittajaid')";
  }
  else {
    $toimittaja_join = "";
  }

  $query = "SELECT
            tuote.tuoteno,
            tuote.nimitys,
            tuote.osasto,
            tuote.try,
            tuote.myyntihinta,
            tuote.varmuus_varasto,
            tuote.kehahin,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm,
            tuote.eankoodi,
            sum(saldo) saldo
            FROM tuote
            JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno
              AND tuote.yhtio = tuotepaikat.yhtio)
            {$toimittaja_join}
            WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
            {$lisa}
            {$varasto_tp_filter}
            {$saldolisa}
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12
            ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
  $eresult = pupe_query($query);
  $total_rows = mysql_num_rows($eresult);

  if ($total_rows > 0) {

    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;

    $excelsarake = 0;
    $worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhm‰"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteno"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Myyntihinta"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("EAN-koodi"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Varastosaldo"));

    if ($nayta_vapaa_saldo == "on") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Vapaa saldo"));
      $worksheet->writeString($excelrivi, $excelsarake++, t("Varattu saldo"));
    }

    if ($toim == "") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Varastonarvo"));
      $worksheet->writeString($excelrivi, $excelsarake++, t("Varmuusvarasto"));
      $worksheet->writeString($excelrivi, $excelsarake++, t("Tilattu m‰‰r‰"));
      $worksheet->writeString($excelrivi, $excelsarake++, t("Toimitus aika"));
    }

    if ($listaustyyppi != "eimyyntia") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." $vvl");
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 12kk"));
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 6kk"));
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 3kk"));

      if ($listaustyyppi == "kappaleet2") {
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus")." $vvl");
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus 12kk"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus 6kk"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus 3kk"));
      }
    }

    $excelrivi++;

    echo "<font class='message'>", t("K‰sitell‰‰n"), " $total_rows ", t("tuotetta"), ".</font>";
    require 'inc/ProgressBar.class.php';

    $bar = new ProgressBar();
    $bar->initialize($total_rows); // print the empty bar

    $osastores = t_avainsana("OSASTO");
    $osastot = array();
    while ($osastorow = mysql_fetch_assoc($osastores)) {
      $osastot[$osastorow["selite"]] = $osastorow["selitetark"];
    }

    $tryres = t_avainsana("TRY");

    $tryt = array();
    while ($tryrow = mysql_fetch_assoc($tryres)) {
      $tryt[$tryrow["selite"]] = $tryrow["selitetark"];
    }

    $varastotilasto_table = "";

    while ($row = mysql_fetch_assoc($eresult)) {

      $bar->increase();

      if ($listaustyyppi != "eimyyntia") {
        // ostopuoli
        $query = "SELECT min(toimaika) toimaika,
                  round(sum(varattu)) tulossa
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND tuoteno = '{$row["tuoteno"]}'
                  AND tyyppi  = 'O'
                  AND varattu > 0
                  {$varasto_tilausrivi_filter}";
        $ostoresult = pupe_query($query);
        $ostorivi = mysql_fetch_assoc($ostoresult);

        $tyyppi_lisa = ($listaustyyppi == "kappaleet" or $listaustyyppi == "kappaleet2") ? "kpl" : "rivihinta";

        // myyntipuoli
        $query = "SELECT
                  round(sum(if(laskutettuaika >= '{$vvl}-01-01', $tyyppi_lisa, 0))) myyntiVA,
                  round(sum(if(laskutettuaika >= date_sub(CURDATE(), interval 12 month), $tyyppi_lisa, 0))) myynti12kk,
                  round(sum(if(laskutettuaika >= date_sub(CURDATE(), interval 6 month), $tyyppi_lisa, 0))) myynti6kk,
                  round(sum(if(laskutettuaika >= date_sub(CURDATE(), interval 3 month), $tyyppi_lisa, 0))) myynti3kk
                  FROM tilausrivi
                  WHERE yhtio         = '{$kukarow["yhtio"]}'
                  AND tuoteno         = '{$row["tuoteno"]}'
                  AND tyyppi          = 'L'
                  and laskutettuaika  >= date_sub(CURDATE(), interval 12 month)
                  AND kpl            != 0
                  {$varasto_tilausrivi_filter}";
        $myyntiresult = pupe_query($query);
        $myyntirivi = mysql_fetch_assoc($myyntiresult);

        if ($listaustyyppi == "kappaleet2") {
          // kulutukset
          $query = "SELECT
                    round(sum(if(toimitettuaika >= '{$vvl}-01-01', $tyyppi_lisa, 0))) kulutusVA,
                    round(sum(if(toimitettuaika >= date_sub(CURDATE(), interval 12 month), $tyyppi_lisa, 0))) kulutus12kk,
                    round(sum(if(toimitettuaika >= date_sub(CURDATE(), interval 6 month), $tyyppi_lisa, 0))) kulutus6kk,
                    round(sum(if(toimitettuaika >= date_sub(CURDATE(), interval 3 month), $tyyppi_lisa, 0))) kulutus3kk
                    FROM tilausrivi
                    WHERE yhtio         = '{$kukarow["yhtio"]}'
                    AND tuoteno         = '{$row["tuoteno"]}'
                    AND tyyppi          = 'V'
                    and toimitettuaika  >= date_sub(CURDATE(), interval 12 month)
                    AND kpl            != 0
                    {$varasto_tilausrivi_filter}";
          $kulutusresult = pupe_query($query);
          $kulutusrivi = mysql_fetch_assoc($kulutusresult);
        }
      }

      if ($nayta_vapaa_saldo == "on") {

        $jalkitoimituksessa = 0;

        // Jos j‰lkitoimitukset eiv‰t varaa saldoa, pit‰‰ ne ottaa mukaan
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
          $query = "SELECT ifnull(round(sum(jt)), 0) jt
                    FROM tilausrivi
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tuoteno = '{$row["tuoteno"]}'
                    AND tyyppi  = 'L'
                    AND var     = 'J'
                    AND jt      > 0
                    {$varasto_tilausrivi_filter}";
          $jt_result = pupe_query($query);
          $jt_rivi = mysql_fetch_assoc($jt_result);
          $jalkitoimituksessa = $jt_rivi["jt"];
        }

        $valitut_varastot = isset($valitut_varastot) ? $valitut_varastot : "";
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", $valitut_varastot);
        $varattu = $saldo - $myytavissa + $jalkitoimituksessa;
      }

      // Jos kaikki luvut on nollaa, niin skipataan rivi
      if ($nollapiilo == 'einollia' and (float) $saldo == 0 and (float) $ostorivi["tulossa"] == 0 and (float) $varattu == 0 and (float) $myyntirivi["myynti12kk"] == 0 and (float) $kulutusrivi["kulutus12kk"] == 0) {
        continue;
      }

      if ($osastot[$row["osasto"]] != "") $row['osasto'] = $row['osasto']." - ".$osastot[$row["osasto"]];
      if ($tryt[$row["try"]] != "") $row['try'] = $row['try']." - ".$tryt[$row["try"]];

      if ($listaustyyppi != "eimyyntia") {
        $myyntirivi["myyntiVA"] = ((int) $myyntirivi["myyntiVA"] == 0) ? "" : $myyntirivi["myyntiVA"];
        $myyntirivi["myynti12kk"] = ((int) $myyntirivi["myynti12kk"] == 0) ? "" : $myyntirivi["myynti12kk"];
        $myyntirivi["myynti6kk"] = ((int) $myyntirivi["myynti6kk"] == 0) ? "" : $myyntirivi["myynti6kk"];
        $myyntirivi["myynti3kk"] = ((int) $myyntirivi["myynti3kk"] == 0) ? "" : $myyntirivi["myynti3kk"];

        if ($listaustyyppi == "kappaleet2") {
          $kulutusrivi["kulutusVA"] = ((int) $kulutusrivi["kulutusVA"] == 0) ? "" : $kulutusrivi["kulutusVA"];
          $kulutusrivi["kulutus12kk"] = ((int) $kulutusrivi["kulutus12kk"] == 0) ? "" : $kulutusrivi["kulutus12kk"];
          $kulutusrivi["kulutus6kk"] = ((int) $kulutusrivi["kulutus6kk"] == 0) ? "" : $kulutusrivi["kulutus6kk"];
          $kulutusrivi["kulutus3kk"] = ((int) $kulutusrivi["kulutus3kk"] == 0) ? "" : $kulutusrivi["kulutus3kk"];
        }
      }

      if ($toim == "") {
        $row["varmuus_varasto"] = ((int) $row["varmuus_varasto"] == 0) ? "" : $row["varmuus_varasto"];

        if     ($row["epakurantti100pvm"] != '0000-00-00') $kehahin = 0;
        elseif ($row["epakurantti75pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.25, 6);
        elseif ($row["epakurantti50pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.5, 6);
        elseif ($row["epakurantti25pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.75, 6);
        else   $kehahin = $row["kehahin"];

        $varastonarvo = round($saldo * $kehahin, 2);
        $varastonarvo = ((float) $varastonarvo == 0) ? "" : $varastonarvo;
      }

      if ($nayta_vapaa_saldo == "on") {
        $varattu = ((float) $varattu == 0) ? "" : (float) $varattu;
        $vapaa_saldo = ((float) $myytavissa == 0) ? "" : (float) $myytavissa;
      }

      $saldo = ((float) $row['saldo'] == 0) ? "" : (float) $row['saldo'];

      if ($total_rows <= 1000) {
        $varastotilasto_table .= "<tr class='aktiivi'>";
        $varastotilasto_table .= "<td nowrap>$row[osasto]</td>";
        $varastotilasto_table .= "<td nowrap>$row[try]</td>";
        $varastotilasto_table .= "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
        $varastotilasto_table .= "<td>$row[nimitys]</td>";
        $varastotilasto_table .= "<td align='right'>".hintapyoristys($row['myyntihinta'])."</td>";
        $varastotilasto_table .= "<td align='right'>$saldo</td>";

        if ($nayta_vapaa_saldo == "on") {
          $varastotilasto_table .= "<td align='right'>{$vapaa_saldo}</td>";
          $varastotilasto_table .= "<td align='right'>{$varattu}</td>";
        }

        if ($toim == "") {
          $varastotilasto_table .= "<td align='right'>$varastonarvo</td>";
        }

        if ($toim == "") {
          $varastotilasto_table .= "<td align='right'>$row[varmuus_varasto]</td>";
          $varastotilasto_table .= "<td align='right'>$ostorivi[tulossa]</td>";
          $varastotilasto_table .= "<td align='right'>".tv1dateconv($ostorivi['toimaika'])."</td>";
        }

        if ($listaustyyppi != "eimyyntia") {
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myyntiVA]</td>";
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myynti12kk]</td>";
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myynti6kk]</td>";
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myynti3kk]</td>";

          if ($listaustyyppi == "kappaleet2") {
            $varastotilasto_table .= "<td align='right'>$kulutusrivi[kulutusVA]</td>";
            $varastotilasto_table .= "<td align='right'>$kulutusrivi[kulutus12kk]</td>";
            $varastotilasto_table .= "<td align='right'>$kulutusrivi[kulutus6kk]</td>";
            $varastotilasto_table .= "<td align='right'>$kulutusrivi[kulutus3kk]</td>";
          }
        }
        $varastotilasto_table .= "</tr>";
      }

      $excelsarake = 0;
      $worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["tuoteno"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["nimitys"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntihinta"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["eankoodi"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $saldo);

      if ($nayta_vapaa_saldo == "on") {
        $worksheet->writeNumber($excelrivi, $excelsarake++, $vapaa_saldo);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $varattu);
      }

      if ($toim == "") {
        $worksheet->writeNumber($excelrivi, $excelsarake++, $varastonarvo);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $row["varmuus_varasto"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $ostorivi["tulossa"]);
        $worksheet->writeString($excelrivi, $excelsarake++, $ostorivi["toimaika"]);
      }

      if ($listaustyyppi != "eimyyntia") {
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myyntiVA"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myynti12kk"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myynti6kk"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myynti3kk"]);

        if ($listaustyyppi == "kappaleet2") {
          $worksheet->writeNumber($excelrivi, $excelsarake++, $kulutusrivi["kulutusVA"]);
          $worksheet->writeNumber($excelrivi, $excelsarake++, $kulutusrivi["kulutus12kk"]);
          $worksheet->writeNumber($excelrivi, $excelsarake++, $kulutusrivi["kulutus6kk"]);
          $worksheet->writeNumber($excelrivi, $excelsarake++, $kulutusrivi["kulutus3kk"]);
        }
      }

      $excelrivi++;
    }

    echo "<br>";

    $excelnimi = $worksheet->close();

    echo "<table>";
    echo "<tr><th>".t("Tallenna excel").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='".t("Varastotilasto").".xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";

    if ($total_rows > 1000) {
      echo "<font class='error'>", t("Hakutulos oli liian suuri"), ". " , t("Tulos vain exceliss‰"), ".</font><br><br>";
    }
    else {
      $sarakkeet = 0;

      echo "<table class='display dataTable' id='$pupe_DataTables'>";
      echo "<thead>";
      echo "<tr>";
      echo "<th>".t("Osasto")."</th>";
      echo "<th>".t("Tuoteryhm‰")."</th>";
      echo "<th>".t("Tuoteno")."</th>";
      echo "<th>".t("Nimitys")."</th>";
      echo "<th>".t("Myyntihinta")."</th>";
      echo "<th>".t("Varastosaldo")."</th>";
      $sarakkeet += 6;

      if ($nayta_vapaa_saldo == "on") {
        echo "<th>".t("Vapaa saldo")."</th>";
        echo "<th>".t("Varattu saldo")."</th>";
        $sarakkeet += 2;
      }

      if ($toim == "") {
        echo "<th>".t("Varastonarvo")."</th>";
        echo "<th>".t("Varmuusvarasto")."</th>";
        echo "<th>".t("Tilattu m‰‰r‰")."</th>";
        echo "<th>".t("Toimitus aika")."</th>";
        $sarakkeet += 4;
      }

      if ($listaustyyppi != "eimyyntia") {
        echo "<th>".t("Myynti")."<br>$vvl</th>";
        echo "<th>".t("Myynti")."<br>12kk</th>";
        echo "<th>".t("Myynti")."<br>6kk</th>";
        echo "<th>".t("Myynti")."<br>3kk</th>";
        $sarakkeet += 4;

        if ($listaustyyppi == "kappaleet2") {
          echo "<th>".t("Kulutus")."<br>$vvl</th>";
          echo "<th>".t("Kulutus")."<br>12kk</th>";
          echo "<th>".t("Kulutus")."<br>6kk</th>";
          echo "<th>".t("Kulutus")."<br>3kk</th>";
          $sarakkeet += 4;
        }
      }

      echo "</tr>";

      echo "<tr>";
      echo "<td><input type='text' class='search_field' name='search_Osasto'></td>";
      echo "<td><input type='text' class='search_field' name='search_Tuoteryh'></td>";
      echo "<td><input type='text' class='search_field' name='search_Tuoteno'></td>";
      echo "<td><input type='text' class='search_field' name='search_Nimitys'></td>";
      echo "<td><input type='text' class='search_field' name='search_Myyntihinta'></td>";
      echo "<td><input type='text' class='search_field' name='search_Varastosaldo'></td>";

      if ($nayta_vapaa_saldo == "on") {
        echo "<td><input type='text' class='search_field' name='search_Vapaasaldo'/></td>";
        echo "<td><input type='text' class='search_field' name='search_Varattusal'></td>";
      }

      if ($toim == "") {
        echo "<td><input type='text' class='search_field' name='search_Varastonarvo'></td>";
        echo "<td><input type='text' class='search_field' name='search_Varmuusvarasto'></td>";
        echo "<td><input type='text' class='search_field' name='search_Tilattumaa'></td>";
        echo "<td><input type='text' class='search_field' name='search_Toimaika'></td>";
      }

      if ($listaustyyppi != "eimyyntia") {
        echo "<td><input type='text' class='search_field' name='search_Myyntivv'></td>";
        echo "<td><input type='text' class='search_field' name='search_Myynti12'></td>";
        echo "<td><input type='text' class='search_field' name='search_Myynti6'></td>";
        echo "<td><input type='text' class='search_field' name='search_Myynti3'></td>";

        if ($listaustyyppi == "kappaleet2") {
          echo "<td><input type='text' class='search_field' name='search_Kulutusvv'></td>";
          echo "<td><input type='text' class='search_field' name='search_Kulutus12'></td>";
          echo "<td><input type='text' class='search_field' name='search_Kulutus6'></td>";
          echo "<td><input type='text' class='search_field' name='search_Kulutus3'></td>";
        }
      }

      echo "</tr>";
      echo "</thead>";
      echo "<tbody>";

      pupe_DataTables(array(array($pupe_DataTables, $sarakkeet, $sarakkeet, false, false)));

      echo $varastotilasto_table;

      echo "</tbody>";
      echo "</table>";
    }
  }

  if ($total_rows == 0) {
    echo "<font class='message'>", t("Yht‰‰n soveltuvaa tuotetta ei lˆytynyt"), ".</font>";
  }
}

require "inc/footer.inc";
