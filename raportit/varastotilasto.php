<?php

if (isset($_POST["tee"])) {
  if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

// DataTables p‰‰lle
$pupe_DataTables = 'vartiltaul';

require ("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

$vvl = date("Y");

echo "<font class=head>".t("Varastotilasto")." $vvl</font><hr>";

if ($ytunnus != '') {

  if ($valittuytunnus != "" and $valittuytunnus != $ytunnus) $toimittajaid = "";

  require ("inc/kevyt_toimittajahaku.inc");

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

$sel = array_fill_keys(array($listaustyyppi), " SELECTED") + array_fill_keys(array('kappaleet', 'hinnat', 'kappaleet2'), '');

echo "<tr>";
echo "<th>".t("Listaustyyppi")."</th>";
echo "<td>";
echo "<select name='listaustyyppi'>";
echo "<option value = 'kappaleet'{$sel['kappaleet']}>".t("Listauksessa n‰ytet‰‰n myynti kappaleina")."</option>";
echo "<option value = 'hinnat'{$sel['hinnat']}>".t("Listauksessa n‰ytet‰‰n myynti euroina")."</option>";
echo "<option value = 'kappaleet2'{$sel['kappaleet2']}>".t("Listauksessa n‰ytet‰‰n myynti ja kulutus kappaleina")."</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr><th>".t("Rajaukset")."</th><td>";

$monivalintalaatikot = array('OSASTO', 'TRY', '<br>TUOTEMERKKI');
require ("tilauskasittely/monivalintalaatikot.inc");

echo "</td></tr>";

echo "<tr>";
echo "<th>".t("Toimittaja")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'> ";
echo "{$toimittajarow["nimi"]} {$toimittajarow["nimitark"]} {$toimittajarow["postitp"]}";

echo "<input type='hidden' name='valittuytunnus' value='$ytunnus'>";
echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
echo "</td>";
echo "</tr>";

$nollapiilochk = "";
if (isset($nollapiilo) and $nollapiilo != '') $nollapiilochk  = "CHECKED";
echo "<tr><th>".t("Piilota nollarivit")."</th><td><input type='checkbox' name='nollapiilo' $nollapiilochk></td></tr>";

echo "</table>";
echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
echo "</form>";
echo "<br><br>";

if ($tee != "" and isset($painoinnappia) and $lisa == "" and $toimittajaid == "") {
  echo "<font class='error'>", t("Anna jokin rajaus"), "!</font>";
  $tee = "";
}

if ($tee != "" and isset($painoinnappia)) {

  if ($toimittajaid != "") {
    $toimittaja_join = "  JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio
                AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
                AND tuotteen_toimittajat.liitostunnus = '$toimittajaid')";
  }
  else {
    $toimittaja_join = "";
  }

  $query = "  SELECT tuote.tuoteno,
        tuote.nimitys,
        tuote.osasto,
        tuote.try,
        tuote.myyntihinta,
        tuote.varmuus_varasto,
        tuote.kehahin,
        tuote.epakurantti25pvm,
        tuote.epakurantti50pvm,
        tuote.epakurantti75pvm,
        tuote.epakurantti100pvm
        FROM tuote
        {$toimittaja_join}
        WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
        {$lisa}
        AND (tuote.status != 'P' OR (  SELECT sum(tuotepaikat.saldo)
                        FROM tuotepaikat
                        WHERE tuotepaikat.yhtio = tuote.yhtio
                        AND tuotepaikat.tuoteno = tuote.tuoteno
                        AND tuotepaikat.saldo > 0) > 0)
        ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
  $eresult = pupe_query($query);
  $total_rows = mysql_num_rows($eresult);

  if ($total_rows > 0) {

    include('inc/pupeExcel.inc');

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;

    if ($total_rows <= 1000) {
      $varastotilasto_table = "<table class='display dataTable' id='$pupe_DataTables'>";
      $varastotilasto_table .= "<thead>";
      $varastotilasto_table .= "<tr>";
      $varastotilasto_table .= "<th>".t("Osasto")."</th>";
      $varastotilasto_table .= "<th>".t("Tuoteryhm‰")."</th>";
      $varastotilasto_table .= "<th>".t("Tuoteno")."</th>";
      $varastotilasto_table .= "<th>".t("Nimitys")."</th>";
      $varastotilasto_table .= "<th>".t("Varastosaldo")."</th>";
      $varastotilasto_table .= "<th>".t("Varastonarvo")."</th>";
      $varastotilasto_table .= "<th>".t("Myyntihinta")."</th>";
      $varastotilasto_table .= "<th>".t("Varmuusvarasto")."</th>";
      $varastotilasto_table .= "<th>".t("Tilattu m‰‰r‰")."</th>";
      $varastotilasto_table .= "<th>".t("Toimitus aika")."</th>";
      $varastotilasto_table .= "<th>".t("Varattu saldo")."</th>";
      $varastotilasto_table .= "<th>".t("Myynti")."<br>$vvl</th>";
      $varastotilasto_table .= "<th>".t("Myynti")."<br>12kk</th>";
      $varastotilasto_table .= "<th>".t("Myynti")."<br>6kk</th>";
      $varastotilasto_table .= "<th>".t("Myynti")."<br>3kk</th>";

      if ($listaustyyppi == "kappaleet2") {
        $varastotilasto_table .= "<th>".t("Kulutus")."<br>$vvl</th>";
        $varastotilasto_table .= "<th>".t("Kulutus")."<br>12kk</th>";
        $varastotilasto_table .= "<th>".t("Kulutus")."<br>6kk</th>";
        $varastotilasto_table .= "<th>".t("Kulutus")."<br>3kk</th>";
      }

      $varastotilasto_table .= "</tr>";

      $varastotilasto_table .= "<tr>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Osasto'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Tuoteryh'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Tuoteno'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Nimitys'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Varastosaldo'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Varastonarvo'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Myyntihinta'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Varmuusvarasto'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Tilattumaa'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Toimaika'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Varattusal'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Myyntivv'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Myynti12'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Myynti6'></td>";
      $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Myynti3'></td>";

      if ($listaustyyppi == "kappaleet2") {
        $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Kulutusvv'></td>";
        $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Kulutus12'></td>";
        $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Kulutus6'></td>";
        $varastotilasto_table .= "<td><input type='text' class='search_field' name='search_Kulutus3'></td>";
      }

      $varastotilasto_table .= "</tr>";
      $varastotilasto_table .= "</thead>";
      $varastotilasto_table .= "<tbody>";
    }

    $excelsarake = 0;
    $worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhm‰"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteno"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Varastosaldo"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Varastonarvo"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Myyntihinta"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Varmuusvarasto"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Tilattu m‰‰r‰"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Toimitus aika"));
    $worksheet->writeString($excelrivi, $excelsarake++, t("Varattu saldo"));
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

    $excelrivi++;

    echo "<font class='message'>", t("K‰sitell‰‰n"), " $total_rows ", t("tuotetta"), ".</font>";
    require('inc/ProgressBar.class.php');

    $bar = new ProgressBar();
    $bar->initialize($total_rows); // print the empty bar

    while ($row = mysql_fetch_assoc($eresult)) {

      $bar->increase();

      // ostopuoli
      $query = "  SELECT min(toimaika) toimaika,
            round(sum(varattu)) tulossa
            FROM tilausrivi
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tuoteno = '{$row["tuoteno"]}'
            AND tyyppi   = 'O'
            AND varattu > 0";
      $ostoresult = pupe_query($query);
      $ostorivi = mysql_fetch_assoc($ostoresult);

      $jalkitoimituksessa = 0;

      // Jos j‰lkitoimitukset eiv‰t varaa saldoa, pit‰‰ ne ottaa mukaan
      if ($yhtiorow["varaako_jt_saldoa"] == "") {
        $query = "  SELECT ifnull(round(sum(jt)), 0) jt
              FROM tilausrivi
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tuoteno = '{$row["tuoteno"]}'
              AND tyyppi  = 'L'
              AND var     = 'J'
              AND jt      > 0";
        $jt_result = pupe_query($query);
        $jt_rivi = mysql_fetch_assoc($jt_result);
        $jalkitoimituksessa = $jt_rivi["jt"];
      }

      $tyyppi_lisa = ($listaustyyppi == "kappaleet" or $listaustyyppi == "kappaleet2") ? "kpl" : "rivihinta";

      // myyntipuoli
      $query = "  SELECT
            round(sum(if(laskutettuaika >= '{$vvl}-01-01', $tyyppi_lisa, 0))) myyntiVA,
            round(sum(if(laskutettuaika >= date_sub(CURDATE(), interval 12 month), $tyyppi_lisa, 0))) myynti12kk,
            round(sum(if(laskutettuaika >= date_sub(CURDATE(), interval 6 month), $tyyppi_lisa, 0))) myynti6kk,
            round(sum(if(laskutettuaika >= date_sub(CURDATE(), interval 3 month), $tyyppi_lisa, 0))) myynti3kk
            FROM tilausrivi
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tuoteno = '{$row["tuoteno"]}'
            AND tyyppi = 'L'
            and laskutettuaika >= date_sub(CURDATE(), interval 12 month)
            AND kpl != 0";
      $myyntiresult = pupe_query($query);
      $myyntirivi = mysql_fetch_assoc($myyntiresult);

      if ($listaustyyppi == "kappaleet2") {
        // kulutukset
        $query = "  SELECT
              round(sum(if(toimitettuaika >= '{$vvl}-01-01', $tyyppi_lisa, 0))) kulutusVA,
              round(sum(if(toimitettuaika >= date_sub(CURDATE(), interval 12 month), $tyyppi_lisa, 0))) kulutus12kk,
              round(sum(if(toimitettuaika >= date_sub(CURDATE(), interval 6 month), $tyyppi_lisa, 0))) kulutus6kk,
              round(sum(if(toimitettuaika >= date_sub(CURDATE(), interval 3 month), $tyyppi_lisa, 0))) kulutus3kk
              FROM tilausrivi
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tuoteno = '{$row["tuoteno"]}'
              AND tyyppi = 'V'
              and toimitettuaika >= date_sub(CURDATE(), interval 12 month)
              AND kpl != 0";
        $kulutusresult = pupe_query($query);
        $kulutusrivi = mysql_fetch_assoc($kulutusresult);
      }

      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);
      $varattu = $saldo - $myytavissa + $jalkitoimituksessa;

      // Jos kaikki luvut on nollaa, niin skipataan rivi
      if ($nollapiilo != '' and (float) $saldo == 0 and (float) $ostorivi["tulossa"] == 0 and (float) $varattu == 0 and (float) $myyntirivi["myynti12kk"] == 0 and (float) $kulutusrivi["kulutus12kk"] == 0) {
        continue;
      }

      $osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
      $osastorow = mysql_fetch_assoc($osastores);

      if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

      $tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
      $tryrow = mysql_fetch_assoc($tryres);

      if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

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

      $row["varmuus_varasto"] = ((int) $row["varmuus_varasto"] == 0) ? "" : $row["varmuus_varasto"];
      if     ($row["epakurantti100pvm"] != '0000-00-00') $kehahin = 0;
      elseif   ($row["epakurantti75pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.25, 6);
      elseif   ($row["epakurantti50pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.5, 6);
      elseif  ($row["epakurantti25pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.75, 6);
      else $kehahin = $row["kehahin"];
      $varastonarvo = round($saldo * $kehahin, 2);
      $varastonarvo = ((float) $varastonarvo == 0) ? "" : $varastonarvo;
      $varattu = ((int) $varattu == 0) ? "" : $varattu;
      $saldo = ((int) $saldo == 0) ? "" : $saldo;

      if ($total_rows <= 1000) {
        $varastotilasto_table .= "<tr class='aktiivi'>";
        $varastotilasto_table .= "<td nowrap>$row[osasto]</td>";
        $varastotilasto_table .= "<td nowrap>$row[try]</td>";
        $varastotilasto_table .= "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
        $varastotilasto_table .= "<td>$row[nimitys]</td>";
        $varastotilasto_table .= "<td align='right'>$saldo</td>";
        $varastotilasto_table .= "<td align='right'>$varastonarvo</td>";
        $varastotilasto_table .= "<td align='right'>".hintapyoristys($row['myyntihinta'])."</td>";
        $varastotilasto_table .= "<td align='right'>$row[varmuus_varasto]</td>";
        $varastotilasto_table .= "<td align='right'>$ostorivi[tulossa]</td>";
        $varastotilasto_table .= "<td align='right'>".tv1dateconv($ostorivi['toimaika'])."</td>";
        $varastotilasto_table .= "<td align='right'>$varattu</td>";
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

        $varastotilasto_table .= "</tr>";
      }

      $excelsarake = 0;
      $worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["tuoteno"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["nimitys"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $saldo);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $varastonarvo);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntihinta"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $row["varmuus_varasto"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $ostorivi["tulossa"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $ostorivi["toimaika"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $varattu);
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

      $excelrivi++;
    }

    if ($total_rows <= 1000) {
      $varastotilasto_table .= "</tbody>";
      $varastotilasto_table .= "</table>";
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
      echo "<font class='error'>", t("Hakutulos oli liian suuri"), ". " ,t("Tulos vain exceliss‰"), ".</font><br><br>";
    }
    else {
      if ($listaustyyppi == "kappaleet2"){
        pupe_DataTables(array(array($pupe_DataTables, 19, 19, false, false)));
      }
      else {
        pupe_DataTables(array(array($pupe_DataTables, 15, 15, false, false)));
      }
      echo "<br>", $varastotilasto_table;
    }
  }

  if ($total_rows == 0) {
    echo "<font class='message'>", t("Yht‰‰n soveltuvaa tuotetta ei lˆytynyt"), ".</font>";
  }
}

require("inc/footer.inc");
