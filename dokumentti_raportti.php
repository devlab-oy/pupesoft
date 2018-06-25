<?php

$pupe_DataTables = 'laskutaulu';

require "inc/parametrit.inc";

echo "<font class='head'>".t('Hyväksytyt ja hylätyt dokumentit')."</font><hr><br>";

echo "<form method='post'>";
echo "<input type='hidden' name = 'tee' value = 'raportoi'>";

echo "<table>";

echo "<tr><th>".t("Valitse dokumetin tila")."</th>
    <td>";

echo "<select name='tila'>";
$sel = array($tila => 'selected') + array_fill_keys(array('HYVAKS', 'HYVTTY', 'HYLATT', 'KAIKKI'), '');

echo "<option value='HYVAKS' $sel[HYVAKS]>".t("Hyväksynnässä")."</option>";
echo "<option value='HYVTTY' $sel[HYVTTY]>".t("Hyväksytty")."</option>";
echo "<option value='HYLATT' $sel[HYLATT]>".t("Hylätty / Poistettu")."</option>";
echo "<option value='KAIKKI' $sel[KAIKKI]>".t("Kaikki")."</option>";
echo "</select>";
echo "</td>";
echo "<td class='back'><input type='submit' value='".t("Näytä")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form><br><br>";


if ($tee == "raportoi") {

  $tilalisa = "";

  if ($tila == "HYVAKS") {
    $tilalisa = "AND hyvaksyttavat_dokumentit.tila='H'";
  }
  if ($tila == "HYVTTY") {
    $tilalisa = "AND hyvaksyttavat_dokumentit.tila='M'";
  }
  if ($tila == "HYLATT") {
    $tilalisa = "AND hyvaksyttavat_dokumentit.tila='D'";
  }

  $query = "SELECT hyvaksyttavat_dokumentit.*,
            kuka.nimi laatija,
            kuka1.nimi hyvaksyja1,
            kuka2.nimi hyvaksyja2,
            kuka3.nimi hyvaksyja3,
            kuka4.nimi hyvaksyja4,
            kuka5.nimi hyvaksyja5
            FROM hyvaksyttavat_dokumentit
            LEFT JOIN kuka as kuka ON kuka.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka.kuka = hyvaksyttavat_dokumentit.laatija
            LEFT JOIN kuka as kuka1 ON kuka1.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka1.kuka = hyvaksyttavat_dokumentit.hyvak1
            LEFT JOIN kuka as kuka2 ON kuka2.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka2.kuka = hyvaksyttavat_dokumentit.hyvak2
            LEFT JOIN kuka as kuka3 ON kuka3.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka3.kuka = hyvaksyttavat_dokumentit.hyvak3
            LEFT JOIN kuka as kuka4 ON kuka4.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka4.kuka = hyvaksyttavat_dokumentit.hyvak4
            LEFT JOIN kuka as kuka5 ON kuka5.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka5.kuka = hyvaksyttavat_dokumentit.hyvak5
            WHERE hyvaksyttavat_dokumentit.yhtio = '$kukarow[yhtio]'
            {$tilalisa}
            ORDER BY hyvaksyttavat_dokumentit.tunnus";
  $result = pupe_query($query);

  pupe_DataTables(array(array($pupe_DataTables, 6, 6)));

  echo "<table class='display dataTable' id='$pupe_DataTables'>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Nimi")."</th>";
  echo "<th>".t("Kuvaus")."</th>";
  echo "<th>".t("Kommentit")."</th>";
  echo "<th>".t("Laatija")."</th>";
  echo "<th>".t("Hyväksyjät")."</th>";
  echo "<th>".t("Tiedosto")."</th>";
  echo "<th>".t("Tila")."</th>";
  echo "</tr>";
  echo "<tr>";
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
      $tila = t("Hyväksynnässä");
    }
    elseif ($trow["tila"] == "M") {
      $tila = t("Hyväksytty");
    }
    elseif ($trow["tila"] == "D") {
      $tila = t("Poistettu / Hylätty");
    }

    echo "<td>$tila</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table><br>";
}

require "inc/footer.inc";
