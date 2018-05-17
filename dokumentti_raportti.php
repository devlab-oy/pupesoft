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

  $query = "SELECT hyvaksyttavat_dokumentit.*, kuka.nimi laatija
            FROM hyvaksyttavat_dokumentit
            LEFT JOIN kuka ON kuka.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka.kuka = hyvaksyttavat_dokumentit.hyvaksyja_nyt
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
  echo "<th>".t("Tiedosto")."</th>";
  echo "<th>".t("Tila")."</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td><input type='text' class='search_field' name='search_nimi'></td>";
  echo "<td><input type='text' class='search_field' name='search_kuvaus'></td>";
  echo "<td><input type='text' class='search_field' name='search_kommentit'></td>";
  echo "<td><input type='text' class='search_field' name='search_laatija'></td>";
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
