<?php

// Datatables p‰‰lle
$pupe_DataTables = array("raportti_valmistuksista");

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
}

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");
require('valmistuslinjat.inc');

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

// Pupe datatables koodi
pupe_DataTables(array(array($pupe_DataTables[0], 12, 12)));

// Piirret‰‰n taulu aluksi display:none, ettei selain piirr‰ sit‰ ruudulle. Toglataan display p‰‰lle kun dokumentti on ready ja datatables tehny rivityksen.
echo '<script language="javascript">
$(document).ready(function() {
  $("#raportti_valmistuksista").toggle();
});
</script>';

// Tarvittavat muuttujat
$tee = isset($tee) ? trim($tee) : "";

echo "<font class='head'>".t("Raportti avoimista ja tehdyist‰ valmistuksista")."</font><hr>";
echo "<br>";

// Ehdotetaan oletuksena edellist‰ kuukautta
if (!isset($pp1)) $pp1 = date("d", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($kk1)) $kk1 = date("m", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($vv1)) $vv1 = date("Y", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
if (!isset($pp2)) $pp2 = date("d", mktime(0, 0, 0, date("m"), 0, date("Y")));
if (!isset($kk2)) $kk2 = date("m", mktime(0, 0, 0, date("m"), 0, date("Y")));
if (!isset($vv2)) $vv2 = date("Y", mktime(0, 0, 0, date("m"), 0, date("Y")));

$valmistuksien_tilat = hae_valmistuksien_tilat('myos_valmistukset_jotka_valmistettu');
$valmistuslinjat = hae_valmistuslinjat();

if (checkdate($kk1, $pp1, $vv1) and checkdate($kk2, $pp2, $vv2)) {
  // MySQL muodossa
  $pvmalku = date("Y-m-d", mktime(0, 0, 0, $kk1, $pp1, $vv1));
  $pvmloppu = date("Y-m-d", mktime(0, 0, 0, $kk2, $pp2, $vv2));
}
else {
  echo "<font class='error'>".t("VIRHE: P‰iv‰m‰‰riss‰ ongelmia")."!</font><br><br>";
  $tee = "";
}

echo "<form method='post'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='pp1' value='$pp1' size='3'>";
echo "<input type='text' name='kk1' value='$kk1' size='3'>";
echo "<input type='text' name='vv1' value='$vv2' size='5'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='pp2' value='$pp2' size='3'>";
echo "<input type='text' name='kk2' value='$kk2' size='3'>";
echo "<input type='text' name='vv2' value='$vv2' size='5'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t('Valmistuksen tila')."</th>";
echo "<td>";
echo "<select name='valmistuksen_tila'>";
foreach ($valmistuksien_tilat as $tila) {
  $sel = "";
  if ($valmistuksen_tila == $tila['value']) {
    $sel = "SELECTED";
  }
  echo "<option value='{$tila['value']}' {$sel}>{$tila['dropdown_text']}</option>";
}
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t('Valmistuslinja')."</th>";
echo "<td>";
echo "<select name='valmistuslinja'>";
echo "<option value=''>".t('Kaikki valmistuslinjat')."</option>";
foreach ($valmistuslinjat as $_valmistuslinja) {
  $sel = "";
  if ($valmistuslinja == $_valmistuslinja['selite']) {
    $sel = "SELECTED";
  }
  echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
}
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t('Esitysmuoto')."</th>";
echo "<td>";
echo "<select name='esitysmuoto'>";
echo "<option value=''>".t('N‰ytet‰‰n tuotteittain')."</option>";
$sel = '';
if ($esitysmuoto == 'Y') {
  $sel = 'SELECTED';
}
echo "<option value='Y' {$sel}>".t('N‰ytet‰‰n vain yhteens‰rivit')."</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr><th>".t("Rajaa tuotekategorialla")."</th>";
echo "<td >";

$monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
require ("tilauskasittely/monivalintalaatikot.inc");

echo "</td>";
echo "<tr class='back'>";
echo "<td></td>";
echo "<td><input type='hidden' value='ajaraportti' name='tee'>";

echo "</tr>";
echo "</table>";

echo "<br>";
echo "<input type='submit' name ='submit_nappi' value='".t("Aja raportti")."'></td>";
echo "</form>";
echo "<br>";

if ($tee == "ajaraportti" and isset($submit_nappi)) {

  if (include('Spreadsheet/Excel/Writer.php')) {
    //keksit‰‰n failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $excelnimi = md5(uniqid(mt_rand(), true)).".xls";

    $workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
    $workbook->setVersion(8);
    $worksheet = $workbook->addWorksheet('Raportti Valmistuksista');

    $format_bold = $workbook->addFormat();
    $format_bold->setBold();

    $excelsarake = 0;
    $excelrivi = 0;
  }

  $lasku_join_ehto = "";
  $valmistuksen_tila = search_array_key_for_value_recursive($valmistuksien_tilat, 'value', $valmistuksen_tila);
  $lasku_join_ehto .= $valmistuksen_tila[0]['query_where'];

  if (isset($valmistuslinja) AND $valmistuslinja != '') {
    $lasku_join_ehto .= " AND lasku.kohde = '{$valmistuslinja}'";
  }

  $query = "  SELECT
        tuote.tuoteno,
        lasku.kohde valmistuslinja,
        tilausrivi.toimaika,
        tilausrivi.toimitettuaika,
        tilausrivi.kerattyaika,
        tilausrivi.nimitys,
        lasku.tila,
        lasku.alatila,
        tuote.try,
        tuote.osasto,
        left(lasku.kerayspvm, 10) kerayspvm,
        lasku.toimaika,
        lasku.tunnus valmistusnumero,
        ifnull(sum(tilausrivi.kpl), 0) valmistettu,
        ifnull(sum(tilausrivi.varattu), 0) valmistetaan
        FROM lasku
        JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
          AND tilausrivi.otunnus = lasku.tunnus
          AND tilausrivi.tyyppi = 'W'
          AND tilausrivi.var != 'P')
        JOIN tuote ON (tuote.yhtio = lasku.yhtio
          AND tuote.tuoteno = tilausrivi.tuoteno)
        {$lisa_parametri}
        WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
        AND lasku.tila = 'V'
        AND lasku.toimaika >= '{$pvmalku}'
        AND lasku.toimaika <= '{$pvmloppu}'
        {$lasku_join_ehto}
        {$lisa}
        GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13
        HAVING valmistettu != 0 OR valmistetaan != 0
        ORDER BY lasku.kohde, tilausrivi.tuoteno, lasku.alatila, lasku.tunnus";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    // Alustetaan array
    $yhteenveto_valmistettu = array();
    $yhteenveto_valmistetaan = array();

    if (isset($workbook)) {
      $worksheet->write($excelrivi, $excelsarake, t("tuoteno"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Tuotteen nimi"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Tuoteosasto"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm‰"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Valmistuslinja"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Valmistusnumero"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Valmistettu kpl"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Valmistetaan kpl"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Valmistuksen tila"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Ker‰ysajankohta"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Valmistusajankohta"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Toteutunut valmistusp‰iv‰"), $format_bold);
      $excelsarake++;
    }

    if ($esitysmuoto == '') {
      echo "<table class='display dataTable' style='display:none;' id='$pupe_DataTables[0]'>";
      echo "<thead>";
      echo "<tr>";
      echo "<th>".t("Tuoteno")."</th>";
      echo "<th>".t("Tuotteen nimi")."</th>";
      echo "<th>".t("Tuoteosasto")."</th>";
      echo "<th>".t("Tuoteryhm‰")."</th>";
      echo "<th>".t("Valmistuslinja")."</th>";
      echo "<th>".t("Valmistusnumero")."</th>";
      echo "<th>".t("Valmistettu kpl")."</th>";
      echo "<th>".t("Valmistetaan kpl")."</th>";
      echo "<th>".t("Valmistuksen tila")."</th>";
      echo "<th>".t("Ker‰ysajankohta")."</th>";
      echo "<th>".t("Valmistusajankohta")."</th>";
      echo "<th>".t("Toteutunut Valmistusp‰iv‰")."</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<td><input type='text' size='8' class='search_field' name='search_tuoteno_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_nimitys_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_osasto_haku'></td>";
      echo "<td><input type='text' class='search_field' name='search_try_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_valmistuslinja_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_valmistusnumero_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_valmistettu_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_valmistetaan_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_tila_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_keraysajankohta_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_valmistusajankohta_haku'></td>";
      echo "<td><input type='text' size='8' class='search_field' name='search_valmistettu_haku'></td>";
      echo "</tr>";
      echo "</thead>";
      echo "<tbody>";
    }

    while ($rivit = mysql_fetch_assoc($result)) {
      $excelsarake = 0;
      $excelrivi++;

      $laskutyyppi = $rivit["tila"];
      $alatila   = $rivit["alatila"];
      require ("inc/laskutyyppi.inc");

      // otetaan selkokieliset nimet esiin avainsanoista
      $linja   = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='$rivit[valmistuslinja]'", "", "", "selitetark");
      $osasto = t_avainsana("OSASTO", "", "and avainsana.selite='$rivit[osasto]'", "", "", "selitetark");
      $try   = t_avainsana("TRY", "", "and avainsana.selite='$rivit[try]'", "", "", "selitetark");

      if($linja == "") $linja = t("Ei m‰‰ritelty");

      if ($esitysmuoto == '') {
        echo "<tr class='aktiivi'>";
        echo "<td>{$rivit["tuoteno"]}</td>";
        echo "<td>{$rivit["nimitys"]}</td>";
        echo "<td>{$osasto}</td>";
        echo "<td>{$try}</td>";
        echo "<td>$linja</td>";
        echo "<td>{$rivit['valmistusnumero']}</td>";
        echo "<td align='right'>{$rivit["valmistettu"]}</td>";
        echo "<td align='right'>{$rivit["valmistetaan"]}</td>";
        echo "<td>{$alatila}</td>";
        echo "<td align='right'>".pupe_DataTablesEchoSort($rivit["kerayspvm"]).tv1dateconv($rivit["kerayspvm"])."</td>";
        echo "<td align='right'>".pupe_DataTablesEchoSort($rivit["toimaika"]).tv1dateconv($rivit["toimaika"])."</td>";
        echo "<td align='right'>".pupe_DataTablesEchoSort($rivit["toimitettuaika"]).tv1dateconv($rivit["toimitettuaika"])."</td>";
        echo "</tr>";
      }

      if (isset($workbook)) {
        // kirjoitetaan samat exceliin
        $worksheet->write($excelrivi, $excelsarake, $rivit["tuoteno"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["nimitys"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["osasto"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["try"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $linja);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit['valmistusnumero']);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["valmistettu"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["valmistetaan"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $alatila);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["kerayspvm"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["toimaika"]);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, $rivit["toimitettuaika"]);
        $excelsarake++;
      }

      // tehd‰‰n yhteenveto arrayt
      if (!isset($yhteenveto_valmistettu[$rivit["valmistuslinja"]])) $yhteenveto_valmistettu[$rivit["valmistuslinja"]] = 0;
      if (!isset($yhteenveto_valmistetaan[$rivit["valmistuslinja"]])) $yhteenveto_valmistetaan[$rivit["valmistuslinja"]] = 0;

      $yhteenveto_valmistettu[$rivit["valmistuslinja"]] += $rivit["valmistettu"];
      $yhteenveto_valmistetaan[$rivit["valmistuslinja"]] += $rivit["valmistetaan"];
    }

    // suljetaan excel
    $workbook->close();

    if ($esitysmuoto == '') {
      echo "</tbody>";
      echo "<tfoot>";
      echo "<tr>";
      echo "<th colspan='6'>".t("N‰kym‰ yhteens‰").":</th>";
      echo "<th valign='top' name='yhteensa' id='yhteensa_6' style='text-align: right'></th>";
      echo "<th valign='top' name='yhteensa' id='yhteensa_7' style='text-align: right'></th>";
      echo "<th colspan='4'></th>";
      echo "</tr>";
      echo "</tfoot>";
      echo "</table>";
      echo "<br>";
    }

    // tehd‰‰n summat taulu
    echo "<br>";
    echo "<font class='message'>".t("Yhteenveto valmistuslinjoittain")."</font><br><br>";
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Valmistuslinja")."</th>";
    echo "<th>".t("Valmistettu")."</th>";
    echo "<th>".t("Valmistuksessa")."</th>";
    echo "</tr>";

    foreach ($yhteenveto_valmistettu as $valmistuslinja => $value) {

      $linja_yht = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='$valmistuslinja'", "", "", "selitetark");
      if ($linja_yht == "") $linja_yht = t("Ei M‰‰ritelty");

      echo "<tr>";
      echo "<td>{$linja_yht}</td>";
      echo "<td style='text-align:right;'>$value</td>";
      echo "<td style='text-align:right;'>{$yhteenveto_valmistetaan[$valmistuslinja]}</td>";
      echo "</tr>";
    }

    echo "<tr>";
    echo "<th>".t("Yhteens‰")."</th>";
    echo "<th style='text-align:right;'>".array_sum($yhteenveto_valmistettu)."</th>";
    echo "<th style='text-align:right;'>".array_sum($yhteenveto_valmistetaan)."</th>";
    echo "</tr>";

    echo "</table>";

    echo "<br>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='raportti_valmistuksista.xls'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<table>";
    echo "<tr><th>",t("Tallenna raportti (xls)"),":</th>";
    echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
    echo "</table></form><br />";
  }
  else {
    echo "<br><font class='error'>".t("Yht‰‰n tuotetta ei lˆytynyt")."!</font>";
  }
}

require ("inc/footer.inc");
