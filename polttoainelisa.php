<?php

require("inc/parametrit.inc");

echo "<font class='head'>",t("Rahtimaksujen polttoainelisä"),"</font><hr>";

if (isset($lisaa) and isset($polttoainelisa) and isset($toimitustapa)) {

  $polttoainelisa = (float) str_replace(",", ".", trim($polttoainelisa));
  $toimitustapa = mysql_real_escape_string($toimitustapa);

  if ($polttoainelisa == 0) {
    echo "<font class='error'>",t("Polttoainelisän hintakerroin on syötettävä"),"!</font><br/><br/>";
  }
  else {
    $query = "  SELECT *
          FROM rahtimaksut
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND rahtihinta != 0
          AND toimitustapa = '$toimitustapa'";
    $rahtimaksut_res = mysql_query($query) or pupe_error($query);

    while ($rahtimaksut_row = mysql_fetch_assoc($rahtimaksut_res)) {

      $rahtimaksu = round($rahtimaksut_row['rahtihinta'] * $polttoainelisa, 2);

      $query = "  UPDATE rahtimaksut SET
            rahtihinta = '$rahtimaksu',
            muutospvm = now(),
            muuttaja = '{$kukarow['kuka']}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$rahtimaksut_row['tunnus']}'";
      $update_res = mysql_query($query) or pupe_error($query);
    }

    echo "<font class='message'>",t("Toimitustavan")," $toimitustapa ",t("rahtihinnat kerrottiin kertoimella")," $polttoainelisa.</font><br/><br/>";
  }
}

$query = "  SELECT DISTINCT rahtimaksut.toimitustapa
      FROM rahtimaksut
      JOIN toimitustapa ON (toimitustapa.yhtio = rahtimaksut.yhtio AND toimitustapa.selite = rahtimaksut.toimitustapa)
      WHERE rahtimaksut.yhtio = '{$kukarow['yhtio']}'
      AND rahtimaksut.rahtihinta != ''
      ORDER BY rahtimaksut.toimitustapa ASC";
$toimitustapa_res = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($toimitustapa_res) == 0) {
  echo "<font class='error'>",t("Yhdelläkään toimitustavalla ei ole rahtimaksuja"),"!</font>";
}
else {

  echo "<form method='post'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>",t("Toimitustapa"),":</th>";
  echo "<td><select name='toimitustapa' id='toimitustapa'>";

  while ($toimitustapa_row = mysql_fetch_array($toimitustapa_res)) {
    echo "<option value='{$toimitustapa_row["toimitustapa"]}'>{$toimitustapa_row["toimitustapa"]}</option>";
  }

  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Polttoainelisän hintakerroin"),":</th>";
  echo "<td><input type='text' name='polttoainelisa' id='polttoainelisa' value='' size='5'></td>";
  echo "<td class='back'><input type='submit' name='lisaa' id='lisaa' value='",t("Lisää"),"'></td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";
}

require("inc/footer.inc");
