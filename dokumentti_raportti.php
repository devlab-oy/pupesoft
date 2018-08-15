<?php

$pupe_DataTables = 'laskutaulu';

require "inc/parametrit.inc";

echo "<font class='head'>".t('Hyv‰ksytyt ja hyl‰tyt dokumentit')."</font><hr><br>";

$dokkarityypit = "";

// Mit‰ dokkarityyppej‰ k‰ytt‰j‰lle saa n‰ytt‰‰
$query = "SELECT group_concat(hd.tunnus) tyypit
          FROM hyvaksyttavat_dokumenttityypit_kayttajat hdk
          JOIN hyvaksyttavat_dokumenttityypit hd ON (hdk.yhtio=hd.yhtio
            and hdk.doku_tyyppi_tunnus=hd.tunnus)
          WHERE hdk.yhtio = '$kukarow[yhtio]'
          AND hdk.kuka = '$kukarow[kuka]'";
$hvresult = pupe_query($query);
$hvrow = mysql_fetch_assoc($hvresult);

if (empty($hvrow['tyypit'])) {
  echo "<font class='error'>".t("VIRHE: Et kuulu mihink‰‰n dokumenttityyppiryhm‰‰n!")."</font>";
  exit;
}
else {
  $dokkarityypit = $hvrow['tyypit'];
}

echo "<form method='post'>";
echo "<input type='hidden' name = 'tee' value = 'raportoi'>";

echo "<table>";

echo "<tr><th>".t("Valitse dokumetin tila")."</th>
    <td>";

echo "<select name='tila'>";
$sel = array($tila => 'selected') + array_fill_keys(array('HYVAKS', 'HYVTTY', 'HYLATT', 'KAIKKI'), '');

echo "<option value='HYVAKS' $sel[HYVAKS]>".t("Hyv‰ksynn‰ss‰")."</option>";
echo "<option value='HYVTTY' $sel[HYVTTY]>".t("Hyv‰ksytty")."</option>";
echo "<option value='HYLATT' $sel[HYLATT]>".t("Hyl‰tty / Poistettu")."</option>";
echo "<option value='KAIKKI' $sel[KAIKKI]>".t("Kaikki")."</option>";
echo "</select>";
echo "</td>";
echo "<td class='back'><input type='submit' value='".t("N‰yt‰")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form><br><br>";


if ($tee == "raportoi") {

  $tilalisa = "";

  if ($tila == "HYVAKS") {
    $tilalisa = "AND hd.tila='H'";
  }
  if ($tila == "HYVTTY") {
    $tilalisa = "AND hd.tila='M'";
  }
  if ($tila == "HYLATT") {
    $tilalisa = "AND hd.tila='D'";
  }

  $query = "SELECT hd.*,
            kuka.nimi laatija,
            kuka1.nimi hyvaksyja1,
            kuka2.nimi hyvaksyja2,
            kuka3.nimi hyvaksyja3,
            kuka4.nimi hyvaksyja4,
            kuka5.nimi hyvaksyja5,
            hdt.tyyppi
            FROM hyvaksyttavat_dokumentit hd
            JOIN hyvaksyttavat_dokumenttityypit hdt ON (hd.yhtio=hdt.yhtio and hd.tiedostotyyppi=hdt.tunnus and hdt.tunnus in ({$dokkarityypit}))
            LEFT JOIN kuka as kuka ON kuka.yhtio = hd.yhtio and kuka.kuka = hd.laatija
            LEFT JOIN kuka as kuka1 ON kuka1.yhtio = hd.yhtio and kuka1.kuka = hd.hyvak1
            LEFT JOIN kuka as kuka2 ON kuka2.yhtio = hd.yhtio and kuka2.kuka = hd.hyvak2
            LEFT JOIN kuka as kuka3 ON kuka3.yhtio = hd.yhtio and kuka3.kuka = hd.hyvak3
            LEFT JOIN kuka as kuka4 ON kuka4.yhtio = hd.yhtio and kuka4.kuka = hd.hyvak4
            LEFT JOIN kuka as kuka5 ON kuka5.yhtio = hd.yhtio and kuka5.kuka = hd.hyvak5
            WHERE hd.yhtio = '$kukarow[yhtio]'
            {$tilalisa}
            ORDER BY hd.tunnus";
  $result = pupe_query($query);

  pupe_DataTables(array(array($pupe_DataTables, 7, 7)));

  echo "<table class='display dataTable' id='$pupe_DataTables'>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Tyyppi")."</th>";
  echo "<th>".t("Nimi")."</th>";
  echo "<th>".t("Kuvaus")."</th>";
  echo "<th>".t("Kommentit")."</th>";
  echo "<th>".t("Laatija")."</th>";
  echo "<th>".t("Hyv‰ksyj‰t")."</th>";
  echo "<th>".t("Tiedosto")."</th>";
  echo "<th>".t("Tila")."</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td><input type='text' class='search_field' name='search_tyyppi'></td>";
  echo "<td><input type='text' class='search_field' name='search_nimi'></td>";
  echo "<td><input type='text' class='search_field' name='search_kuvaus'></td>";
  echo "<td><input type='text' class='search_field' name='search_kommentit'></td>";
  echo "<td><input type='text' class='search_field' name='search_laatija'></td>";
  echo "<td><input type='text' class='search_field' name='search_hyvak'></td>";
  echo "<td><input type='text' class='search_field' name='search_tiedosto'></td>";
  echo "<td><input type='text' class='search_field' name='search_tila'></td>";
  echo "</tr>";
  echo "</thead>";
  echo "<tbody>";

  while ($trow = mysql_fetch_array($result)) {

    echo "<tr class='aktiivi'>";
    echo "<td>$trow[tyyppi]</td>";
    echo "<td>$trow[nimi]</td>";
    echo "<td>$trow[kuvaus]</td>";
    echo "<td>$trow[kommentit]</td>";
    echo "<td>$trow[laatija]</td>";


    echo "<td>";

    if ($trow[h1time] != '0000-00-00 00:00:00') {
      echo "$trow[hyvaksyja1] @ $trow[h1time]<br>";
    }
    if ($trow[h2time] != '0000-00-00 00:00:00') {
      echo "$trow[hyvaksyja2] @ $trow[h2time]<br>";
    }
    if ($trow[h3time] != '0000-00-00 00:00:00') {
      echo "$trow[hyvaksyja3] @ $trow[h3time]<br>";
    }
    if ($trow[h4time] != '0000-00-00 00:00:00') {
      echo "$trow[hyvaksyja4] @ $trow[h4time]<br>";
    }
    if ($trow[h5time] != '0000-00-00 00:00:00') {
      echo "$trow[hyvaksyja5] @ $trow[h5time]";
    }

    echo "</td>";


    $query = "SELECT tunnus, filename, selite
              from liitetiedostot
              where liitostunnus = '{$trow['tunnus']}'
              and liitos = 'hyvaksyttavat_dokumentit'
              and yhtio = '{$kukarow['yhtio']}'
              ORDER BY tunnus";
    $res = pupe_query($query);

    echo "<td>";

    while ($row = mysql_fetch_assoc($res)) {
      echo "<div id='div_$row[tunnus]' class='popup'>$row[filename]<br>$row[selite]</div>";
      echo js_openUrlNewWindow("{$palvelin2}view.php?id=$row[tunnus]", t('Liite').": $row[filename]", "class='tooltip' id='$row[tunnus]'")."<br>";
    }

    echo "</td>";

    $tila = "";

    if ($trow["tila"] == "H") {
      $tila = t("Hyv‰ksynn‰ss‰");
    }
    elseif ($trow["tila"] == "M") {
      $tila = t("Hyv‰ksytty");
    }
    elseif ($trow["tila"] == "D") {
      $tila = t("Poistettu / Hyl‰tty");
    }

    echo "<td>$tila</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table><br>";
}

require "inc/footer.inc";
