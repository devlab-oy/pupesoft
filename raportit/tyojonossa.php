<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require '../inc/parametrit.inc';

echo "<font class='head'>".t("Työjono-raportti")."</font><hr>";

// Hoidetaan parametri kuntoon
$peruste = isset($peruste) ? $peruste : "tyojono";
$sel = ($peruste == "suorittaja") ? "SELECTED" : "";

echo "<form name='vaihdaPeruste' method='POST'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Näytä työmääräykset")."</th>";
echo "<td><select name='peruste' onchange='submit()'>";
echo "<option value='tyojono'>".t("Työjonottain")."</option>";
echo "<option value='suorittaja' $sel>".t("Suorittajittain")."</option>";
echo "</select></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

echo "<br>";
echo "<table>";

if ($kukarow['kieli'] != '') {
  $kieli = $kukarow['kieli'];
}
else {
  $kieli = $yhtiorow['kieli'];
}

if ($peruste == "suorittaja") {
  $query = "SELECT
            ifnull(a3.nimi, 'N/A') tyojono1,
            ifnull(a2.selitetark, 'N/A') tyostatus1,
            count(*) maara
            FROM lasku
            JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus=lasku.tunnus and tyomaarays.tyojono != '')
            LEFT JOIN avainsana a1 ON (a1.yhtio = tyomaarays.yhtio and a1.laji = 'TYOM_TYOJONO' and a1.selite = tyomaarays.tyojono  and a1.kieli = '$kieli')
            LEFT JOIN avainsana a2 ON (a2.yhtio = tyomaarays.yhtio and a2.laji = 'TYOM_TYOSTATUS' and a2.selite = tyomaarays.tyostatus and a2.kieli = '$kieli')
            LEFT JOIN avainsana a5 ON (a5.yhtio=tyomaarays.yhtio and a5.laji='TYOM_PRIORIT' and a5.selite=tyomaarays.prioriteetti and a5.kieli = '$kieli')
            LEFT JOIN kuka a3 ON (a3.yhtio = tyomaarays.yhtio and a3.kuka = tyomaarays.suorittaja)
            WHERE lasku.yhtio  = '{$kukarow["yhtio"]}'
            AND lasku.tila     in ('A','L','N','S','C')
            AND lasku.alatila != 'X'
            GROUP BY 1,2
            ORDER BY a3.nimi, ifnull(a2.jarjestys, 9999), a2.selitetark";
}
else {
  $query = "SELECT
            a1.selitetark tyojono1,
            ifnull(a2.selitetark, 'N/A') tyostatus1,
            count(*) maara
            FROM lasku
            JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus = lasku.tunnus and tyomaarays.tyojono != '')
            LEFT JOIN avainsana a1 ON (a1.yhtio = tyomaarays.yhtio and a1.laji = 'TYOM_TYOJONO' and a1.selite = tyomaarays.tyojono and a1.kieli = '$kieli')
            LEFT JOIN avainsana a2 ON (a2.yhtio = tyomaarays.yhtio and a2.laji = 'TYOM_TYOSTATUS' and a2.selite = tyomaarays.tyostatus and a2.kieli = '$kieli')
            LEFT JOIN avainsana a5 ON (a5.yhtio=tyomaarays.yhtio and a5.laji='TYOM_PRIORIT' and a5.selite=tyomaarays.prioriteetti and a5.kieli = '$kieli')
            WHERE lasku.yhtio  = '{$kukarow["yhtio"]}'
            AND lasku.tila     in ('A','L','N','S','C')
            AND lasku.alatila != 'X'
            GROUP BY 1,2
            ORDER BY ifnull(a1.jarjestys, 9999), a1.selitetark, ifnull(a2.jarjestys, 9999), a2.selitetark";
}

$ekares = pupe_query($query);

$vaihdajono = "";
$jonosumma = 0;

echo "<tr>";
echo "<th>".t($peruste)."</th>";
echo "<th>".t("Työstatus")."</th>";
echo "<th>".t("Määrä")."</th>";
echo "</tr>";

while ($rivit = mysql_fetch_assoc($ekares)) {

  if ($vaihdajono != $rivit["tyojono1"] and $vaihdajono != "") {
    echo "<tr><td class='tumma' colspan='2'>".t("Yhteensä").":</td><td class='tumma' align='right'>$jonosumma</td></tr>";
    echo "<tr><td class='back' colspan='3'><br></td></tr>";
    $jonosumma = 0;
  }

  echo "<tr class='aktiivi'>";

  if ($vaihdajono != $rivit["tyojono1"]) {

    if ($peruste == "tyojono") {
      echo "<td><a href='{$palvelin2}tyomaarays/tyojono.php?indexvas=1&tyojono_haku={$rivit["tyojono1"]}&lopetus={$palvelin2}raportit/tyojonossa.php////peruste=$peruste//tee=K'>{$rivit["tyojono1"]}</a></td>";
    }
    else {
      $linkkihaku = urlencode($rivit["tyojono1"]);
      echo "<td><a href='{$palvelin2}tyomaarays/tyojono.php?indexvas=1&linkkihaku=$linkkihaku&lopetus={$palvelin2}raportit/tyojonossa.php////peruste=$peruste//tee=K''>{$rivit["tyojono1"]}</a></td>";
    }
  }
  else {
    echo "<td></td>";
  }

  echo "<td>{$rivit["tyostatus1"]} </td>";
  echo "<td align='right'>{$rivit["maara"]} </td>";

  echo "</tr>";

  $jonosumma += $rivit["maara"];
  $vaihdajono = $rivit["tyojono1"];
}

echo "<tr><td class='tumma' colspan='2'>".t("Yhteensä").":</td><td class='tumma' align='right'>$jonosumma</td></tr>";
echo "</table>";

require "inc/footer.inc";
