<?php

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if ($tee == "lataa_tiedosto") {
  readfile("/tmp/" . $tmpfilenimi);
  exit;
}

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

  require "raportit/naytatilaus.inc";

  echo "<br><br>";
  exit;
}

echo "<font class='head'>".t("Toimitetut rivit")."</font><hr><br>";
echo "<form method='post' action=''>";
echo "<input type='hidden' name='tee' value='aja'>";
echo "<table>";

if (!isset($kka))
  $kka = date("m");
if (!isset($vva))
  $vva = date("Y");
if (!isset($ppa))
  $ppa = date("d");

echo "<tr><th>".t("P‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    <td class='back'></td>
    </tr>";
echo "</table>";
echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form><br><br>";

if ($tee == 'aja') {
  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.luontiaika, lasku.tapvm, asiakas.asiakasnro,
            concat_ws(' ', lasku.nimi, lasku.nimitark) nimi, if(lasku.clearing = 'HYVITYS', lasku.viesti,'') viesti,
            lasku.tila, lasku.alatila, lasku.sisviesti3,
            from_unixtime(avg(unix_timestamp(tilausrivi.toimitettuaika))) toimitettuaika,
            round(sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta_verolli,
            round(sum(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta_veroton
            FROM tilausrivi
            JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
            LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
            WHERE tilausrivi.yhtio        = '{$kukarow['yhtio']}'
            AND tilausrivi.tyyppi         = 'L'
            AND tilausrivi.var            not in ('P','J','O','S')
            AND tilausrivi.varattu+tilausrivi.kpl != 0
            AND tilausrivi.toimitettuaika > '0000-00-00 00:00:00'
            AND tilausrivi.toimitettuaika <= '$vva-$kka-$ppa 23:59:59'
            AND (tilausrivi.laskutettuaika > '$vva-$kka-$ppa' or tilausrivi.laskutettuaika = '0000-00-00')
            GROUP BY 1,2,3,4,5,6,7,8,9,10
            ORDER BY toimitettuaika";
  $result = pupe_query($query);

  echo "<font class='head'>Toimitettu, mutta ei laskutettu per: $ppa.$kka.$vva</font><hr><br>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tilausnumero")."</th>";
  echo "<th>".t("Laskunumero")."</th>";
  echo "<th>".t("Luotu")."</th>";
  echo "<th>".t("Toimitettu")."</th>";
  echo "<th>".t("Laskutettu")."</th>";
  echo "<th>".t("Summa")."</th>";
  echo "<th>".t("Arvo")."</th>";
  echo "<th>".t("Asiakasnro")."</th>";
  echo "<th>".t("Hyvitys")."</th>";
  echo "<th>".t("Sis‰inen viesti")."</th>";
  echo "<th>".t("Tila")."</th>";
  echo "<th>".t("N‰yt‰")."</th>";
  echo "</tr>";

  include 'inc/pupeExcel.inc';

  $worksheet   = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi   = 0;

  $worksheet->write($excelrivi, 0, t("Tilausnumero"), $format_bold);
  $worksheet->write($excelrivi, 1, t("Laskunumero"), $format_bold);
  $worksheet->write($excelrivi, 2, t("Luotu"), $format_bold);
  $worksheet->write($excelrivi, 3, t("Toimitettu"), $format_bold);
  $worksheet->write($excelrivi, 4, t("Laskutettu"), $format_bold);
  $worksheet->write($excelrivi, 5, t("Summa"), $format_bold);
  $worksheet->write($excelrivi, 6, t("Arvo"), $format_bold);
  $worksheet->write($excelrivi, 7, t("Asiakasnro"), $format_bold);
  $worksheet->write($excelrivi, 8, t("Hyvitys"), $format_bold);
  $worksheet->write($excelrivi, 9, t("Sis‰inen viesti"), $format_bold);
  $worksheet->write($excelrivi, 10, t("Tila"), $format_bold);
  $excelrivi++;

  $summat = 0;
  $arvot = 0;

  while ($row = mysql_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['tunnus']}</td>";
    echo "<td>{$row['laskunro']}</td>";
    echo "<td>".tv1dateconv($row['luontiaika'])."</td>";
    echo "<td>".tv1dateconv($row['toimitettuaika'])."</td>";
    echo "<td>".tv1dateconv($row['tapvm'])."</td>";
    echo "<td style='text-align: right;'>{$row['rivihinta_verolli']}</td>";
    echo "<td style='text-align: right;'>{$row['rivihinta_veroton']}</td>";
    echo "<td>{$row['asiakasnro']}</td>";
    echo "<td>{$row['viesti']}</td>";
    echo "<td>{$row['sisviesti3']}</td>";

    $laskutyyppi = $row["tila"];
    $alatila = $row["alatila"];

    //tehd‰‰n selv‰kielinen tila/alatila
    require "inc/laskutyyppi.inc";

    echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
    echo "<td>";
    echo "<a href='' onclick=\"window.open('{$palvelin2}raportit/toimitetut_rivit.php?tee=NAYTATILAUS&tunnus=$row[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1000,height=600'); return false;\">".t("N‰yt‰ tilaus")."</a>";
    echo "</td>";
    echo "</tr>";

    $worksheet->writeString($excelrivi, 0, $row['tunnus']);
    $worksheet->writeString($excelrivi, 1, $row['laskunro']);
    $worksheet->writeDate($excelrivi, 2, substr($row['luontiaika'], 0, 10));
    $worksheet->writeDate($excelrivi, 3, substr($row['toimitettuaika'], 0, 10));
    $worksheet->writeDate($excelrivi, 4, substr($row['tapvm'], 0, 10));
    $worksheet->writeNumber($excelrivi, 5, $row['rivihinta_verolli']);
    $worksheet->writeNumber($excelrivi, 6, $row['rivihinta_veroton']);
    $worksheet->writeString($excelrivi, 7, $row['asiakasnro']);
    $worksheet->writeString($excelrivi, 8, $row['viesti']);
    $worksheet->writeString($excelrivi, 9, $row['sisviesti3']);
    $worksheet->writeString($excelrivi, 10, t("$laskutyyppi")." ".t("$alatila"));
    $excelrivi++;

    $summat += $row['rivihinta_verolli'];
    $arvot += $row['rivihinta_veroton'];
  }

  echo "<tr class='spec'>";
  echo "<td colspan='5'>".t("Yhteens‰").":</td>";
  echo "<td style='text-align: right;'>{$summat}</td>";
  echo "<td style='text-align: right;'>{$arvot}</td>";
  echo "<td></td>";
  echo "<td></td>";
  echo "</tr>";
  echo "</table>";

  $excelnimi = $worksheet->close();

  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna Excel").":</th><td class='back'>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Toimitetut_rivit.xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<input type='submit' value='".t("Tallenna")."'></form></td></tr>";
  echo "</table><br>";
}

require "inc/footer.inc";
