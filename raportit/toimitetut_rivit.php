<?php

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;

require "../inc/parametrit.inc";

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

  require "raportit/naytatilaus.inc";

  echo "<br><br>";
  exit;
}

echo "<font class='head'>".t("Toimitetut rivit")."</font><hr><br>";

js_openFormInNewWindow();

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
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tyyppi = 'L'
            AND tilausrivi.var not in ('P','J','O','S')
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

    echo "<form id='tulostakopioform_$row[tunnus]' name='tulostakopioform_$row[tunnus]' method='post' action='".$palvelin2."raportit/toimitetut_rivit.php'>
        <input type='hidden' name='tunnus' value='$row[tunnus]'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='submit' value='".t("N‰yt‰ tilaus")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$row[tunnus]', ''); return false;\">
        </form>";

    echo "</td>";
    echo "</tr>";

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
}

require "inc/footer.inc";
