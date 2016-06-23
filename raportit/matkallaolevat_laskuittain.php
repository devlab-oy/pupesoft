<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if (!empty($_POST["kaunisnimi"])) {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require "../inc/parametrit.inc";

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Saapuminen")." $saapuminen:</font><hr>";

  require "raportit/naytatilaus.inc";

  echo "<br><br>";
  exit;
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>".t("Matkallaolevat laskuittain")."</font><hr>";

if (!isset($vv) or !isset($lvv)) {
  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio         = '$kukarow[yhtio]'
            and tilikausi_alku  <= current_date
            and tilikausi_loppu >= current_date";
  $result = pupe_query($query);
  $tilikausirow = mysql_fetch_assoc($result);

  if (!isset($vv)) {
    $vv = substr($tilikausirow['tilikausi_alku'], 0, 4);
    $kk = substr($tilikausirow['tilikausi_alku'], 5, 2);
    $pp = substr($tilikausirow['tilikausi_alku'], 8, 2);
  }

  if (!isset($lvv)) {
    $lvv = substr($tilikausirow['tilikausi_loppu'], 0, 4);
    $lkk = substr($tilikausirow['tilikausi_loppu'], 5, 2);
    $lpp = substr($tilikausirow['tilikausi_loppu'], 8, 2);
  }
}

$llisa = "";
$alisa = "";

if (isset($tkausi) and $tkausi > 0) {
  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tkausi'";
  $vresult = pupe_query($query);
  $tilikaudetrow = mysql_fetch_array($vresult);

  $alisa = $tilikaudetrow["tilikausi_alku"];
  $llisa = $tilikaudetrow["tilikausi_loppu"];
}
else {
  if ($vv != "") {
    if (!checkdate($kk, $pp, $vv)) {
      echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br><br>";
    }
    else {
      $alisa = "$vv-$kk-$pp";
    }
  }

  if ($lvv != "") {
    if (!checkdate($lkk, $lpp, $lvv)) {
      echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br><br>";
    }
    else {
      $llisa = "$lvv-$lkk-$lpp";
    }
  }
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='aja'>";
echo "<table>";

$query = "SELECT *
          FROM tilikaudet
          WHERE yhtio = '{$kukarow["yhtio"]}'
          ORDER BY tilikausi_alku DESC";
$vresult = pupe_query($query);

echo "<tr>";
echo "<th>", t("Tilikausi"), "</th>";
echo "<td><select name='tkausi'>";
echo "<option value = ''>".t("Valitse")."</option>";

while ($vrow = mysql_fetch_assoc($vresult)) {
  $sel = $tkausi == $vrow['tunnus'] ? ' selected' : '';
  echo "<option value = '$vrow[tunnus]'$sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' class='back'> ".t("tai")." </td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Syötä alkupäivä")."</th>";
echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Syötä loppupäivä")."</th>";
echo "<td><input type='text' name='lpp' size='5' value='$lpp'><input type='text' name='lkk' size='5' value='$lkk'><input type='text' name='lvv' size='7' value='$lvv'></td>";
echo "</tr>";

$chk = array(
  "none" => "",
  "vain" => "",
  "muut" => "",
);

$key = empty($vo_laskurajaus) ? 'none' : $vo_laskurajaus;

$chk[$key] = 'CHECKED';

echo "<tr>";
echo "<th>".t("Tapahtumatyyppirajaus")."</th>";
echo "<td>";
echo "<input type='radio' name='vo_laskurajaus' value='none' {$chk['none']}> ".t("Näytä kaikki")."<br>";
echo "<input type='radio' name='vo_laskurajaus' value='vain' {$chk['vain']}> ".t("Näytä vain vaihto-omaisuuslaskuja")."<br>";
echo "<input type='radio' name='vo_laskurajaus' value='muut' {$chk['muut']}> ".t("Älä näytä vaihto-omaisuuslaskuja")."<br>";
echo "</td>";
echo "</tr>";

$chk = empty($rajaa_myos_lasku_tapvm) ? '' : 'CHECKED';

echo "<tr>";
echo "<th>".t("Rajaa myös laskun tapahtumapäivällä")."</th>";
echo "<td>";
echo "<input type='checkbox' name='rajaa_myos_lasku_tapvm' value='YES' $chk>";
echo "</td>";
echo "</tr>";

$chk = "";
if (isset($excel) and $excel != "") {
  $chk = "CHECKED";
}

echo "<tr><th>".t("Tee Excel")."</th>
    <td><input type = 'checkbox' name = 'excel'  value = 'YES' $chk></td></tr>";

echo "</table>";
echo "<br><input type='submit' value='".t("Näytä")."'>";

echo "</form>";
echo "<br><br>";

$create_excel = (isset($excel) and $excel != "");

if ($tee == "aja" and $alisa != "" and $llisa != "") {
  switch ($vo_laskurajaus) {
  case 'vain':
    // halutaan listata vain vaihto-omaisuuslaskuja
    $vo_laskurajaus_lisa = "AND lasku.vienti in ('C','F','I')";
    break;
  case 'muut':
    // ei haluta listata vaihto-omaisuuslaskuja
    $vo_laskurajaus_lisa = "AND lasku.vienti not in ('C','F','I')";
    break;
  default:
    $vo_laskurajaus_lisa = '';
  }

  if ($rajaa_myos_lasku_tapvm == 'YES') {
    $tapvm_laskurajaus_lisa = " AND lasku.tapvm >= '{$alisa}' AND lasku.tapvm <= '{$llisa}'
      AND (lasku.tila in ('H', 'Y', 'M', 'P', 'Q') or (lasku.tila = 'X' and lasku.alatila != 'A'))";
  }
  else {
    $tapvm_laskurajaus_lisa = "";
  }

  $query = "SELECT lasku.alatila,
            lasku.nimi,
            lasku.summa,
            lasku.tapvm,
            lasku.tila,
            lasku.tunnus,
            lasku.valkoodi,
            sum(tiliointi.summa) AS matkalla
            FROM tiliointi
            JOIN lasku on (lasku.tunnus = tiliointi.ltunnus)
            WHERE tiliointi.yhtio  = '{$kukarow['yhtio']}'
            AND tiliointi.tilino   = '{$yhtiorow['matkalla_olevat']}'
            AND tiliointi.tapvm    >= '{$alisa}'
            AND tiliointi.tapvm    <= '{$llisa}'
            AND tiliointi.korjattu = ''
            {$vo_laskurajaus_lisa}
            {$tapvm_laskurajaus_lisa}
            GROUP BY lasku.alatila,
            lasku.nimi,
            lasku.summa,
            lasku.tapvm,
            lasku.tila,
            lasku.tunnus,
            lasku.valkoodi
            HAVING matkalla != 0
            ORDER BY lasku.nimi, lasku.tapvm, lasku.summa";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Nimi")."</th>";
    echo "<th>".t("Tapvm")."</th>";
    echo "<th>".t("Summa")."</th>";
    echo "<th>".t("Valuutta")."</th>";
    echo "<th>".t("Matkalla")."</th>";
    echo "<th>".t("Valuutta")."</th>";
    echo "<th>".t("Saapuminen")."</th>";
    echo "<th>".t("Saapuminen suljettu")."</th>";
    echo "<th>".t("Varastoonvientipäivä")."</th>";
    echo "<th>".t("Tuotteita varastossa").": ".tv1dateconv($llisa)."</th>";
    echo "<th>".t("Toimitusehto")."</th>";
    echo "</tr>";

    if ($create_excel) {
      include 'inc/pupeExcel.inc';

      $worksheet   = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi   = 0;
      $excelsarake = 0;

      $worksheet->write($excelrivi, $excelsarake++, t("Nimi"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Tapvm"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Summa"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Valuutta"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Matkalla"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Valuutta"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Saapuminen"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Saapuminen suljettu"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Varastoonvientipäivä"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Tuotteita varastossa").": ".tv1dateconv($llisa), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Toimitusehto"), $format_bold);

      $excelrivi++;
      $excelsarake = 0;
    }

    $summa = 0;
    $alvsumma = array();

    while ($row = mysql_fetch_assoc($result)) {
      // skipataan avaava tase tosite
      if ($row['tila'] == 'X' and $row['alatila'] == 'A') {
        continue;
      }

      $liotsrow = array();
      $keikrow = array();
      $rivirow = array();
      $toimirow = array();
      $varivirow = array();

      // Onko lasku liitetty saapumiseen?
      $query = "SELECT DISTINCT laskunro
                FROM lasku
                WHERE yhtio     = '$kukarow[yhtio]'
                AND tila        = 'K'
                AND vanhatunnus = $row[tunnus]";
      $liotsres = pupe_query($query);

      if (mysql_num_rows($liotsres) == 1) {
        $liotsrow = mysql_fetch_assoc($liotsres);

        // Virallinen varastoonvientipäivä
        $query = "SELECT laskunro, tunnus, mapvm, liitostunnus
                  FROM lasku
                  WHERE yhtio     = '$kukarow[yhtio]'
                  AND tila        = 'K'
                  AND vanhatunnus = 0
                  AND laskunro    = '{$liotsrow['laskunro']}'";
        $keikres = pupe_query($query);
        $keikrow = mysql_fetch_assoc($keikres);

        // Milloin rivit on viety saldoille keskimäärin
        $query = "SELECT DATE_FORMAT(FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(laskutettuaika))),'%Y-%m-%d') AS laskutettuaika
                  FROM tilausrivi
                  WHERE yhtio         = '{$kukarow['yhtio']}'
                  AND uusiotunnus     = {$keikrow['tunnus']}
                  AND tyyppi          = 'O'
                  AND laskutettuaika != '0000-00-00'";
        $rivires = pupe_query($query);
        $rivirow = mysql_fetch_assoc($rivires);

        // Milloin rivit on viety saldoille keskimäärin
        $query = "SELECT
                  round(sum(rivihinta), 2) va_arvo
                  FROM tilausrivi
                  WHERE yhtio        = '{$kukarow['yhtio']}'
                  AND uusiotunnus    = {$keikrow['tunnus']}
                  AND tyyppi         = 'O'
                  AND laskutettuaika <= '$llisa'";
        $varivires = pupe_query($query);
        $varivirow = mysql_fetch_assoc($varivires);

        // Toimittajan toimitusehto
        $query = "SELECT toimitusehto
                  FROM toimi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = {$keikrow['liitostunnus']}";
        $toimires = pupe_query($query);
        $toimirow = mysql_fetch_assoc($toimires);
      }
      elseif (mysql_num_rows($liotsres) > 1) {
        echo "Lasku {$row['tunnus']} useassa saapumisessa<br>";
      }

      if ($row['tila'] == 'X' and empty($row['nimi'])) {
        $row['nimi'] = t("Tosite");
      }

      echo "<tr class='aktiivi'>";
      echo "<td>$row[nimi]</td>";
      echo "<td>".tv1dateconv($row["tapvm"])."</td>";
      echo "<td align='right'>$row[summa]</td>";
      echo "<td align='right'>$row[valkoodi]</td>";
      echo "<td align='right'><a href='' onclick=\"window.open('{$palvelin2}muutosite.php?tee=E&tunnus=$row[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1200,height=800'); return false;\">$row[matkalla]</a></td>";
      echo "<td align='right'>$yhtiorow[valkoodi]</td>";
      echo "<td>";

      if (!empty($keikrow["laskunro"])) {
        echo "<a href='' onclick=\"window.open('{$palvelin2}raportit/matkallaolevat_laskuittain.php?tee=NAYTATILAUS&tunnus=$keikrow[tunnus]&saapuminen=$keikrow[laskunro]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1200,height=800'); return false;\">{$keikrow["laskunro"]}</a>";
      }

      echo "</td>";
      echo "<td>".tv1dateconv($keikrow["mapvm"])."</td>";
      echo "<td>".tv1dateconv($rivirow["laskutettuaika"])."</td>";
      echo "<td align='right'>{$varivirow['va_arvo']}</td>";
      echo "<td>$toimirow[toimitusehto]</td>";
      echo "</tr>";

      if ($create_excel) {
        $worksheet->writeString($excelrivi, $excelsarake++, $row["nimi"], $format_bold);
        $worksheet->writeDate($excelrivi,   $excelsarake++, $row["tapvm"], $format_bold);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $row["summa"], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $row["valkoodi"], $format_bold);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $row["matkalla"], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $row["valkoodi"], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $keikrow["laskunro"], $format_bold);
        $worksheet->writeDate($excelrivi,   $excelsarake++, $keikrow["mapvm"], $format_bold);
        $worksheet->writeDate($excelrivi,   $excelsarake++, $rivirow["laskutettuaika"], $format_bold);
        $worksheet->writeNumber($excelrivi, $excelsarake++, $varivirow['va_arvo'], $format_bold);
        $worksheet->writeString($excelrivi, $excelsarake++, $toimirow["toimitusehto"], $format_bold);

        $excelrivi++;
        $excelsarake = 0;
      }

      $summa += $row["matkalla"];
    }

    echo "<tr>";
    echo "<th colspan='4'>".t("Yhteensä")."</th>";
    echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
    echo "<th colspan='6'></th>";
    echo "</tr>";
    echo "</table>";

    if (isset($excel) and $excel != "") {
      $excelnimi = $worksheet->close();

      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna tulos").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".t("Matkallaolevat").".xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";
    }
  }
}

require "inc/footer.inc";
