<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require ("../inc/parametrit.inc");

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
echo "<table>";

$query = "SELECT *
          FROM tilikaudet
          WHERE yhtio = '{$kukarow["yhtio"]}'
          ORDER BY tilikausi_alku DESC";
$vresult = pupe_query($query);

echo "<tr>";
echo "<th>",t("Tilikausi"),"</th>";
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

if ($alisa != "" and $llisa != "") {

  $query = "SELECT lasku.tunnus, if(lasku.tila = 'X', '".t("Tosite")."', lasku.nimi) nimi, lasku.summa, lasku.valkoodi, lasku.tapvm, sum(tiliointi.summa) matkalla
            FROM lasku
            JOIN tiliointi on (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = '$yhtiorow[matkalla_olevat]' AND tiliointi.tapvm >= '$alisa' AND tiliointi.tapvm <= '$llisa' AND tiliointi.korjattu = '')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND (lasku.tila in ('H', 'Y', 'M', 'P', 'Q') or (lasku.tila = 'X' and lasku.alatila != 'A'))
            AND lasku.tapvm   >= '$alisa'
            AND lasku.tapvm   <= '$llisa'
            GROUP BY lasku.tunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm
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
    echo "<th>".t("Toimitusehto")."</th>";
    echo "</tr>";

    if (isset($excel) and $excel != "") {
      include('inc/pupeExcel.inc');

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake = 0;

      $worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Tapvm"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Summa"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Valuutta"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Matkalla"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Valuutta"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Saapuminen"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Saapuminen suljettu"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Varastoonvientipäivä"), $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Toimitusehto"), $format_bold);

      $excelrivi++;
      $excelsarake = 0;
    }

    $summa = 0;
    $alvsumma = array();

    while ($row = mysql_fetch_array($result)) {

      $liotsrow = array();
      $keikrow  = array();
      $rivirow  = array();
      $toimirow = array();

      // Onko lasku liitetty saapumiseen?
      $query = "SELECT laskunro
                FROM lasku
                WHERE yhtio     = '$kukarow[yhtio]'
                AND tila        = 'K'
                AND vanhatunnus = $row[tunnus]";
      $liotsres = pupe_query($query);

      while ($liotsrow = mysql_fetch_assoc($liotsres)) {
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
        $query = "SELECT
                  DATE_FORMAT(FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(laskutettuaika))),'%Y-%m-%d') AS laskutettuaika
                  FROM tilausrivi
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND uusiotunnus = {$keikrow['tunnus']}
                  AND tyyppi      = 'O'";
        $rivires = pupe_query($query);
        $rivirow = mysql_fetch_assoc($rivires);

        // Toimittajan toimitusehto
        $query = "SELECT toimitusehto
                  FROM toimi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = {$keikrow['liitostunnus']}";
        $toimires = pupe_query($query);
        $toimirow = mysql_fetch_assoc($toimires);
      }

      echo "<tr class='aktiivi'>";
      echo "<td>$row[nimi]</td>";
      echo "<td>".tv1dateconv($row["tapvm"])."</td>";
      echo "<td align='right'>$row[summa]</td>";
      echo "<td align='right'>$row[valkoodi]</td>";
      echo "<td align='right'><a href='$palvelin2","muutosite.php?tee=E&tunnus=$row[tunnus]&lopetus=$palvelin2","raportit/matkallaolevat_laskuittain.php'>$row[matkalla]</a></td>";
      echo "<td align='right'>$yhtiorow[valkoodi]</td>";
      echo "<td>{$keikrow["laskunro"]}</td>";
      echo "<td>".tv1dateconv($keikrow["mapvm"])."</td>";
      echo "<td>".tv1dateconv($rivirow["laskutettuaika"])."</td>";
      echo "<td>$toimirow[toimitusehto]</td>";
      echo "</tr>";

      if (isset($excel) and $excel != "") {
        $worksheet->write($excelrivi, $excelsarake,   $row["nimi"], $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, tv1dateconv($row["tapvm"]), $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, $row["summa"], $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, $row["valkoodi"], $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, $row["matkalla"], $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, $row["valkoodi"], $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, $keikrow["laskunro"], $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, tv1dateconv($keikrow["mapvm"]), $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, tv1dateconv(tv3dateconv($rivirow["laskutettuaika"], TRUE)), $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, $toimirow["toimitusehto"], $format_bold);

        $excelrivi++;
        $excelsarake = 0;
      }

      $summa += $row["matkalla"];
    }

    echo "<tr>";
    echo "<th colspan='4'>".t("Yhteensä")."</th>";
    echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
    echo "<th colspan='5'></th>";
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

require ("inc/footer.inc");
