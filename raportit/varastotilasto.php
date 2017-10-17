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

echo "<font class='head'>".t("Varastotilasto")." $vvl</font><hr>";

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

$sel = array_fill_keys(array($listaustyyppi), " SELECTED") + array_fill_keys(array('kappaleet', 'hinnat', 'kappaleet2', 'kappaleet3', 'eimyyntia', 'osto', 'ostoryhma'), '');

echo "<tr>";
echo "<th>".t("Listaustyyppi")."</th>";
echo "<td>";
echo "<select name='listaustyyppi' onchange='submit();'>";
echo "<option value = 'kappaleet'{$sel['kappaleet']}>".t("Listauksessa n‰ytet‰‰n myynti kappaleina")."</option>";
echo "<option value = 'kappaleet3'{$sel['kappaleet3']}>".t("Listauksessa n‰ytet‰‰n myynti kappaleina, rajattu")."</option>";
echo "<option value = 'hinnat'{$sel['hinnat']}>".t("Listauksessa n‰ytet‰‰n myynti euroina")."</option>";
echo "<option value = 'kappaleet2'{$sel['kappaleet2']}>".t("Listauksessa n‰ytet‰‰n myynti ja kulutus kappaleina")."</option>";
echo "<option value = 'eimyyntia'{$sel['eimyyntia']}>".t("Listauksessa ei n‰ytet‰ myyntej‰ eik‰ kulutuksia")."</option>";

if ($toim == "") {
  echo "<option value = 'osto'{$sel['osto']}>".t("Ostoraportti")."</option>";
  echo "<option value = 'ostoryhma'{$sel['ostoryhma']}>".t("Ostoraportti ryhmitt‰in")."</option>";
}

echo "</select>";
echo "</td>";
echo "</tr>";

if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {

  if (!isset($kka)) $kka = date("n", mktime(0, 0, 0, 1, 1, date("Y")));
  if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, 1, 1, date("Y")));
  if (!isset($ppa)) $ppa = date("j", mktime(0, 0, 0, 1, 1, date("Y")));

  if (!isset($kkl)) $kkl = date("n");
  if (!isset($vvl)) $vvl = date("Y");
  if (!isset($ppl)) $ppl = date("j");

  echo "<tr>";
  echo "<th>", t("Alkup‰iv‰m‰‰r‰"), " (", t("pp-kk-vvvv"), ")</th>";
  echo "<td><input type='text' name='ppa' id='ppa' value='{$ppa}' size='3'>";
  echo "<input type='text' name='kka' id='kka' value='{$kka}' size='3'>";
  echo "<input type='text' name='vva' id='vva' value='{$vva}' size='5'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Loppup‰iv‰m‰‰r‰"), " (", t("pp-kk-vvvv"), ")</th>";
  echo "<td><input type='text' name='ppl' id='ppl' value='{$ppl}' size='3'>";
  echo "<input type='text' name='kkl' id='kkl' value='{$kkl}' size='3'>";
  echo "<input type='text' name='vvl' id='vvl' value='{$vvl}' size='5'></td>";
  echo "</tr>";
}

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

if ($listaustyyppi == "kappaleet3" and !isset($painoinnappia)) {
  $nayta_vapaa_saldo = "on";
}

$checked = (isset($nayta_vapaa_saldo) and $nayta_vapaa_saldo == "on") ? "checked" : "";

echo "<td><input type='checkbox' name='nayta_vapaa_saldo' {$checked}/></td>";
echo "</tr>";

echo "</table>";
echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
echo "</form>";
echo "<br><br>";

if ($tee != "" and isset($painoinnappia) and empty($lisa) and empty($toimittajaid) and empty($valitut_varastot)) {
  echo "<font class='error'>", t("Anna jokin rajaus"), "!</font>";
  $tee = "";
}

if (($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") and (
  (isset($ppa) and (int) $ppa == 0) or
  (isset($kka) and (int) $kka == 0) or
  (isset($vva) and (int) $vva == 0) or
  (isset($ppl) and (int) $ppl == 0) or
  (isset($kkl) and (int) $kkl == 0) or
  (isset($vvl) and (int) $vvl == 0))
) {
  echo "<font class='error'>", t("P‰iv‰m‰‰r‰ss‰ on virhe"), "!</font>";
  unset($painoinnappia);
}

if ($tee != "" and isset($painoinnappia)) {

  $left = $listaustyyppi == "kappaleet3" ? "LEFT" : "";

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
    $varasto_tapahtuma_filter = "AND tapahtuma.varasto in ({$varastot})";
  }
  else {
    $varasto_tp_filter = "";
    $varasto_tilausrivi_filter = "";
    $varasto_tapahtuma_filter = "";
  }

  if ($toimittajaid != "") {
    $toimittaja_join = "  JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio
                AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
                AND tuotteen_toimittajat.liitostunnus = '$toimittajaid')";
  }
  else {
    $toimittaja_join = "";
  }

  $unionlisa = "";

  if ($listaustyyppi == "ostoryhma") {
    $unionlisa = "UNION
                  SELECT
                  'XXX' tuoteno,
                  'XXX' nimitys,
                  'XXX' osasto,
                  'XXX' try,
                  'XXX' malli,
                  'XXX' myyntihinta,
                  'XXX' varmuus_varasto,
                  'XXX' kehahin,
                  'XXX' epakurantti25pvm,
                  'XXX' epakurantti50pvm,
                  'XXX' epakurantti75pvm,
                  'XXX' epakurantti100pvm,
                  'XXX' eankoodi,
                  'XXX' saldo ";
  }

  $_orderby = "";

  if ($listaustyyppi == "kappaleet3") {
    $_orderby = "tuoteno";
  }
  else {
    $_orderby = "osasto, try, malli, tuoteno";
  }

  $query = "SELECT
            tuote.tuoteno,
            tuote.nimitys,
            tuote.osasto,
            tuote.try,
            tuote.malli,
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
            {$left} JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno AND tuote.yhtio = tuotepaikat.yhtio)
            {$toimittaja_join}
            WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
            {$lisa}
            {$varasto_tp_filter}
            {$saldolisa}
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12
            {$unionlisa}
            ORDER BY {$_orderby}";
  $eresult = pupe_query($query);
  $total_rows = mysql_num_rows($eresult);

  if ($total_rows > 0) {

    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;

    $excelsarake = 0;

    if ($listaustyyppi != "kappaleet3") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhm‰"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Malli"), $format_bold);
    }
    $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteno"), $format_bold);
    $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"), $format_bold);
    if ($listaustyyppi != "kappaleet3") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("EAN-koodi"), $format_bold);
    }
    $worksheet->writeString($excelrivi, $excelsarake++, t("Myyntihinta"), $format_bold);
    $worksheet->writeString($excelrivi, $excelsarake++, t("Saldo"), $format_bold);

    if ($nayta_vapaa_saldo == "on") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Vapaa saldo"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Varattu saldo"), $format_bold);

      if ($yhtiorow["varaako_jt_saldoa"] == "") {
         $worksheet->writeString($excelrivi, $excelsarake++, t("J‰lkitoimituksessa"), $format_bold);
      }
    }

    if ($toim == "") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Varastonarvo"), $format_bold);

      if ($listaustyyppi != "osto" and $listaustyyppi != "ostoryhma") {
        if ($listaustyyppi != "kappaleet3") {
          $worksheet->writeString($excelrivi, $excelsarake++, t("Varmuusvarasto"), $format_bold);
        }
        $worksheet->writeString($excelrivi, $excelsarake++, t("Tilattu m‰‰r‰"), $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, t("Toimitus aika"), $format_bold);
      }
    }

    if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Ostot"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Ostot")." ".$yhtiorow['valkoodi'], $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." ".$yhtiorow['valkoodi'], $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Kate")." ".$yhtiorow['valkoodi'], $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Siirrot")." ".$yhtiorow['valkoodi'], $format_bold);
    }
    elseif ($listaustyyppi != "eimyyntia") {
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." $vvl", $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 12kk"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 6kk"), $format_bold);
      $worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 3kk"), $format_bold);

      if ($listaustyyppi == "kappaleet2") {
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus")." $vvl", $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus 12kk"), $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus 6kk"), $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kulutus 3kk"), $format_bold);
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

    $mallires = t_avainsana("MALLI");

    $mallit = array();
    while ($mallirow = mysql_fetch_assoc($mallires)) {
      $mallit[$mallirow["selite"]] = $mallirow["selitetark"];
    }

    $varastotilasto_table = "";
    $varastonarvo_tot = 0;
    $mallisummarivi = 2;
    $mallisummat = array();
    $trysummat = array();
    $osastosummat = array();

    while ($row = mysql_fetch_assoc($eresult)) {

      $bar->increase();

      if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {
        // myyntipuoli
        $query = "SELECT
                  round(sum(rivihinta)) myyntiVA,
                  round(sum(kpl)) myyntikplVA,
                  round(sum(kate)) myyntikateVA
                  FROM tilausrivi
                  WHERE yhtio         = '{$kukarow["yhtio"]}'
                  AND tuoteno         = '{$row["tuoteno"]}'
                  AND tyyppi          = 'L'
                  AND laskutettuaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
                  AND laskutettuaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
                  AND kpl            != 0
                  {$varasto_tilausrivi_filter}";
        $myyntiresult = pupe_query($query);
        $myyntirivi = mysql_fetch_assoc($myyntiresult);

        // tuloutukset ja siirrot
        $query = "SELECT
                  round(sum(if(laji = 'tulo', kpl*kplhinta, 0))) tulotVA,
                  round(sum(if(laji = 'tulo', kpl, 0))) tulotkplVA,
                  round(sum(if(laji = 'siirto', kpl*hinta, 0))) siirrotVA
                  FROM tapahtuma
                  WHERE yhtio  = '{$kukarow["yhtio"]}'
                  AND tuoteno  = '{$row["tuoteno"]}'
                  AND laji in ('tulo', 'siirto')
                  AND laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
                  AND laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
                  {$varasto_tapahtuma_filter}";
        $tuloresult = pupe_query($query);
        $tapahtumarivi = mysql_fetch_assoc($tuloresult);
      }
      elseif ($listaustyyppi != "eimyyntia") {
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

        $tyyppi_lisa = ($listaustyyppi == "kappaleet" or $listaustyyppi == "kappaleet2" or $listaustyyppi == "kappaleet3") ? "kpl" : "rivihinta";

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

        // Jos j‰lkitoimitukset varaavat saldoa, lasketaan ne varattu lukuun
        // muuten j‰lkitoimituksessa -lukuun
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

        $valitut_varastot = isset($valitut_varastot) ? $valitut_varastot : "";
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", $valitut_varastot);
        if ($yhtiorow["varaako_jt_saldoa"] != "") {
          $varattu = $saldo - $myytavissa + $jalkitoimituksessa;
        }
        else {
          $varattu = $saldo - $myytavissa;
        }
      }

      if ($osastot[$row["osasto"]] != "") {
        $row['osasto'] = $row['osasto']." - ".$osastot[$row["osasto"]];
      }

      if ($tryt[$row["try"]] != "") {
        $row['try'] = $row['try']." - ".$tryt[$row["try"]];
      }

      if ($mallit[$row["malli"]] != "") {
        $row['malli'] = $row['malli']." - ".$mallit[$row["malli"]];
      }

      $myyntirivi["myyntiVA"]   = empty($myyntirivi["myyntiVA"]) ? "" : $myyntirivi["myyntiVA"];
      $myyntirivi["myynti12kk"] = empty($myyntirivi["myynti12kk"]) ? "" : $myyntirivi["myynti12kk"];
      $myyntirivi["myynti6kk"]  = empty($myyntirivi["myynti6kk"]) ? "" : $myyntirivi["myynti6kk"];
      $myyntirivi["myynti3kk"]  = empty($myyntirivi["myynti3kk"]) ? "" : $myyntirivi["myynti3kk"];

      $kulutusrivi["kulutusVA"]   = empty($kulutusrivi["kulutusVA"]) ? "" : $kulutusrivi["kulutusVA"];
      $kulutusrivi["kulutus12kk"] = empty($kulutusrivi["kulutus12kk"]) ? "" : $kulutusrivi["kulutus12kk"];
      $kulutusrivi["kulutus6kk"]  = empty($kulutusrivi["kulutus6kk"]) ? "" : $kulutusrivi["kulutus6kk"];
      $kulutusrivi["kulutus3kk"]  = empty($kulutusrivi["kulutus3kk"]) ? "" : $kulutusrivi["kulutus3kk"];

      $tapahtumarivi["tulotVA"] = empty($tapahtumarivi["tulotVA"]) ? "" : $tapahtumarivi["tulotVA"];
      $tapahtumarivi["tulotkplVA"] = empty($tapahtumarivi["tulotkplVA"]) ? "" : $tapahtumarivi["tulotkplVA"];
      $tapahtumarivi["siirrotVA"] = empty($tapahtumarivi["siirrotVA"]) ? "" : $tapahtumarivi["siirrotVA"];

      $varattu = empty($varattu) ? "" : (float) $varattu;
      $vapaa_saldo = empty($myytavissa) ? "" : (float) $myytavissa;
      $saldo = empty($row['saldo']) ? "" : (float) $row['saldo'];
      $row["varmuus_varasto"] = empty($row["varmuus_varasto"]) ? "" : $row["varmuus_varasto"];
      $jalkitoimituksessa = empty($jalkitoimituksessa) ? "" : (float) $jalkitoimituksessa;

      if ($toim == "") {
        if     ($row["epakurantti100pvm"] != '0000-00-00') $kehahin = 0;
        elseif ($row["epakurantti75pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.25, 6);
        elseif ($row["epakurantti50pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.5, 6);
        elseif ($row["epakurantti25pvm"]  != '0000-00-00') $kehahin = round($row["kehahin"] * 0.75, 6);
        else   $kehahin = $row["kehahin"];

        $varastonarvo = round($saldo * $kehahin, 2);
        $varastonarvo_tot += $varastonarvo;

        $varastonarvo = ((float) $varastonarvo == 0) ? "" : $varastonarvo;
      }

      if ($listaustyyppi == "ostoryhma" and !empty($edrow) and $row['malli'] != $edrow["malli"] and $excelrivi >= $mallisummarivi) {
        $excelsarake = 0;
        $worksheet->writeString($excelrivi, $excelsarake++, $edrow["osasto"], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $edrow["try"], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $edrow["malli"], $format_bold);
        $excelsarake += 4;

        $mallisummat[] = $excelrivi+1;

        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(H$mallisummarivi:H$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(I$mallisummarivi:I$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(J$mallisummarivi:J$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(K$mallisummarivi:K$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(L$mallisummarivi:L$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(M$mallisummarivi:M$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(N$mallisummarivi:N$excelrivi)", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(O$mallisummarivi:O$excelrivi)", $format_bold);
        $excelrivi++;
        $excelrivi++;

        $mallisummarivi = $excelrivi+1;
      }

      if ($listaustyyppi == "ostoryhma" and !empty($edrow) and $row['try'] != $edrow["try"] and count($mallisummat) > 0) {
        $excelsarake = 0;
        $worksheet->writeString($excelrivi, $excelsarake++, $edrow["osasto"], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $edrow["try"], $format_bold);
        $excelsarake += 5;

        $trysummat[] = $excelrivi+1;

        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(H".implode(", H", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(I".implode(", I", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(J".implode(", J", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(K".implode(", K", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(L".implode(", L", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(M".implode(", M", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(N".implode(", N", $mallisummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(O".implode(", O", $mallisummat).")", $format_bold);

        $excelrivi++;
        $excelrivi++;
        $mallisummarivi = $excelrivi+1;
        $mallisummat = array();
      }

      if ($listaustyyppi == "ostoryhma" and !empty($edrow) and $row['osasto'] != $edrow["osasto"] and count($trysummat) > 0) {

        $excelsarake = 0;
        $worksheet->writeString($excelrivi, $excelsarake++, $edrow["osasto"], $format_bold);
        $excelsarake += 6;

        $osastosummat[] = $excelrivi+1;

        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(H".implode(", H", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(I".implode(", I", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(J".implode(", J", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(K".implode(", K", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(L".implode(", L", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(M".implode(", M", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(N".implode(", N", $trysummat).")", $format_bold);
        $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(O".implode(", O", $trysummat).")", $format_bold);

        $excelrivi++;
        $excelrivi++;
        $mallisummarivi = $excelrivi+1;
        $mallisummat = array();
        $trysummat = array();
      }

      $edrow = $row;

      // Jos kaikki luvut on nollaa, niin skipataan rivi
      if (($nollapiilo == 'einollia'
        and (float) $varastonarvo == 0
        and (float) $saldo == 0
        and (float) $ostorivi["tulossa"] == 0
        and (float) $varattu == 0
        and (float) $myyntirivi["myynti12kk"] == 0
        and (float) $kulutusrivi["kulutus12kk"] == 0
        and (float) $tapahtumarivi["tulotVA"] == 0
        and (float) $tapahtumarivi["tulotkplVA"] == 0
        and (float) $tapahtumarivi["siirrotVA"] == 0)
        or (
          $listaustyyppi == "ostoryhma"
          and $row["osasto"] == "XXX"
          and $row["try"] == "XXX"
          and $row["tuoteno"] == "XXX"
        )
      ) {
        continue;
      }

      if ($total_rows <= 1000) {
        $varastotilasto_table .= "<tr class='aktiivi'>";

        if ($listaustyyppi != "kappaleet3") {
          $varastotilasto_table .= "<td nowrap>$row[osasto]</td>";
          $varastotilasto_table .= "<td nowrap>$row[try]</td>";
          $varastotilasto_table .= "<td nowrap>$row[malli]</td>";
        }

        $varastotilasto_table .= "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
        $varastotilasto_table .= "<td>$row[nimitys]</td>";
        $varastotilasto_table .= "<td align='right'>".hintapyoristys($row['myyntihinta'])."</td>";
        $varastotilasto_table .= "<td align='right'>$saldo</td>";

        if ($nayta_vapaa_saldo == "on") {
          $varastotilasto_table .= "<td align='right'>{$vapaa_saldo}</td>";
          $varastotilasto_table .= "<td align='right'>{$varattu}</td>";
          if ($yhtiorow["varaako_jt_saldoa"] == "") {
            $varastotilasto_table .= "<td align='right'>{$jalkitoimituksessa}</td>";
          }
        }

        if ($toim == "") {
          $varastotilasto_table .= "<td align='right'>$varastonarvo</td>";

          if ($listaustyyppi != "osto" and $listaustyyppi != "ostoryhma") {
            if ($listaustyyppi != "kappaleet3") {
              $varastotilasto_table .= "<td align='right'>$row[varmuus_varasto]</td>";
            }

            $varastotilasto_table .= "<td align='right'>$ostorivi[tulossa]</td>";
            $varastotilasto_table .= "<td align='right'>".tv1dateconv($ostorivi['toimaika'])."</td>";
          }
        }

        if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {
          $varastotilasto_table .= "<td align='right'>$tapahtumarivi[tulotkplVA]</td>";
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myyntikplVA]</td>";
          $varastotilasto_table .= "<td align='right'>$tapahtumarivi[tulotVA]</td>";
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myyntiVA]</td>";
          $varastotilasto_table .= "<td align='right'>$myyntirivi[myyntikateVA]</td>";
          $varastotilasto_table .= "<td align='right'>$tapahtumarivi[siirrotVA]</td>";
        }
        elseif ($listaustyyppi != "eimyyntia") {
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
      if ($listaustyyppi != "kappaleet3") {
        $worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
        $worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
        $worksheet->writeString($excelrivi, $excelsarake++, $row["malli"]);
      }
      $worksheet->writeString($excelrivi, $excelsarake++, $row["tuoteno"]);
      $worksheet->writeString($excelrivi, $excelsarake++, $row["nimitys"]);
      if ($listaustyyppi != "kappaleet3") {
        $worksheet->writeString($excelrivi, $excelsarake++, $row["eankoodi"]);
      }
      $worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntihinta"]);
      $worksheet->writeNumber($excelrivi, $excelsarake++, $saldo);

      if ($nayta_vapaa_saldo == "on") {
        $worksheet->writeNumber($excelrivi, $excelsarake++, $vapaa_saldo);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $varattu);
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
            $worksheet->writeNumber($excelrivi, $excelsarake++, $jalkitoimituksessa);
        }
      }

      if ($toim == "") {
        $worksheet->writeNumber($excelrivi, $excelsarake++, $varastonarvo);

        if ($listaustyyppi != "osto" and $listaustyyppi != "ostoryhma") {
          if ($listaustyyppi != "kappaleet3") {
            $worksheet->writeNumber($excelrivi, $excelsarake++, $row["varmuus_varasto"]);
          }
          $worksheet->writeNumber($excelrivi, $excelsarake++, $ostorivi["tulossa"]);
          $worksheet->writeString($excelrivi, $excelsarake++, $ostorivi["toimaika"]);
        }
      }

      if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {
        $worksheet->writeNumber($excelrivi, $excelsarake++, $tapahtumarivi["tulotkplVA"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myyntikplVA"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $tapahtumarivi["tulotVA"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myyntiVA"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myyntikateVA"]);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $tapahtumarivi["siirrotVA"]);
      }
      elseif ($listaustyyppi != "eimyyntia") {
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

    if ($listaustyyppi == "ostoryhma" and !empty($edrow) and count($osastosummat) > 0) {
      $excelsarake = 0;
      $worksheet->writeString($excelrivi, $excelsarake++, "Kaikki yhteens‰", $format_bold);
      $excelsarake += 6;

      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(H".implode(", H", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(I".implode(", I", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(J".implode(", J", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(K".implode(", K", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(L".implode(", L", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(M".implode(", M", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(N".implode(", N", $osastosummat).")", $format_bold);
      $worksheet->writeFormula($excelrivi, $excelsarake++, "=SUM(O".implode(", O", $osastosummat).")", $format_bold);
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

      if ($listaustyyppi != "kappaleet3") {
        echo "<th>".t("Osasto")."</th>";
        echo "<th>".t("Tuoteryhm‰")."</th>";
        echo "<th>".t("Malli")."</th>";
        $sarakkeet += 3;
      }

      echo "<th>".t("Tuoteno")."</th>";
      echo "<th>".t("Nimitys")."</th>";
      echo "<th>".t("Myyntihinta")."</th>";
      echo "<th>".t("Saldo")."</th>";
      $sarakkeet += 4;

      if ($nayta_vapaa_saldo == "on") {
        echo "<th>".t("Vapaa saldo")."</th>";
        echo "<th>".t("Varattu saldo")."</th>";
        $sarakkeet += 2;
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
          echo "<th>".t("J‰lkitoimituksessa")."</th>";
          $sarakkeet += 1;
        }
      }

      if ($toim == "") {
        echo "<th>".t("Varastonarvo")."</th>";
        $sarakkeet++;

        if ($listaustyyppi != "osto" and $listaustyyppi != "ostoryhma") {
          if ($listaustyyppi != "kappaleet3") {
            echo "<th>".t("Varmuusvarasto")."</th>";
            $sarakkeet++;
          }
          echo "<th>".t("Tilattu m‰‰r‰")."</th>";
          echo "<th>".t("Toimitus aika")."</th>";
          $sarakkeet += 2;
        }
      }

      if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {
        echo "<th>".t("Ostot")."</th>";
        echo "<th>".t("Myynti")."</th>";
        echo "<th>".t("Ostot")." $yhtiorow[valkoodi]</th>";
        echo "<th>".t("Myynti")." $yhtiorow[valkoodi]</th>";
        echo "<th>".t("Kate")." $yhtiorow[valkoodi]</th>";
        echo "<th>".t("Siirrot")."$yhtiorow[valkoodi]</th>";
        $sarakkeet += 6;
      }
      elseif ($listaustyyppi != "eimyyntia") {
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
      if ($listaustyyppi != "kappaleet3") {
        echo "<td><input type='text' class='search_field' name='search_Osasto'></td>";
        echo "<td><input type='text' class='search_field' name='search_Tuoteryh'></td>";
        echo "<td><input type='text' class='search_field' name='search_Tuotemalli'></td>";
      }
      echo "<td><input type='text' class='search_field' name='search_Tuoteno'></td>";
      echo "<td><input type='text' class='search_field' name='search_Nimitys'></td>";
      echo "<td><input type='text' class='search_field' name='search_Myyntihinta'></td>";
      echo "<td><input type='text' class='search_field' name='search_Varastosaldo'></td>";

      if ($nayta_vapaa_saldo == "on") {
        echo "<td><input type='text' class='search_field' name='search_Vapaasaldo'/></td>";
        echo "<td><input type='text' class='search_field' name='search_Varattusal'></td>";
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
          echo "<td><input type='text' class='search_field' name='search_Jalkkari'></td>";
        }
      }

      if ($toim == "") {
        echo "<td><input type='text' class='search_field' name='search_Varastonarvo'></td>";

        if ($listaustyyppi != "osto" and $listaustyyppi != "ostoryhma") {
          if ($listaustyyppi != "kappaleet3") {
            echo "<td><input type='text' class='search_field' name='search_Varmuusvarasto'></td>";
          }
          echo "<td><input type='text' class='search_field' name='search_Tilattumaa'></td>";
          echo "<td><input type='text' class='search_field' name='search_Toimaika'></td>";
        }
      }

      if ($listaustyyppi == "osto" or $listaustyyppi == "ostoryhma") {
        echo "<td><input type='text' class='search_field' name='search_Ostotkpl'></td>";
        echo "<td><input type='text' class='search_field' name='search_Myyntikpl'></td>";
        echo "<td><input type='text' class='search_field' name='search_Ostot'></td>";
        echo "<td><input type='text' class='search_field' name='search_Myynti'></td>";
        echo "<td><input type='text' class='search_field' name='search_Kate'></td>";
        echo "<td><input type='text' class='search_field' name='search_Siirrot'></td>";
      }
      elseif ($listaustyyppi != "eimyyntia") {
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

    echo "<br><br><table><tr><th>".t("Varastonarvo yhteens‰").":</th><td>$varastonarvo_tot</td></tr></table>";
  }

  if ($total_rows == 0) {
    echo "<font class='message'>", t("Yht‰‰n soveltuvaa tuotetta ei lˆytynyt"), ".</font>";
  }
}

require "inc/footer.inc";
