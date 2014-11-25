<?php

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (!isset($errors)) $errors = array();

$query = "SELECT ss.*,
          IF(ss.lisatieto IS NULL, 'Normaali', ss.lisatieto) AS status,
          lasku.asiakkaan_tilausnumero
          FROM sarjanumeroseuranta AS ss
          JOIN tilausrivi
            ON tilausrivi.yhtio = ss.yhtio
            AND tilausrivi.tunnus = ss.myyntirivitunnus
          JOIN lasku
            ON lasku.yhtio = ss.yhtio
            AND lasku.tunnus = tilausrivi.otunnus
          JOIN laskun_lisatiedot
            ON laskun_lisatiedot.yhtio = ss.yhtio
            AND laskun_lisatiedot.otunnus = lasku.tunnus
          WHERE ss.yhtio = '{$kukarow['yhtio']}'
          AND (ss.lisatieto != 'Toimitettu' OR ss.lisatieto IS NULL)";
$result = pupe_query($query);

echo "<font class='head'>".t("Varastotilanne")."</font><hr><br>";

if (mysql_num_rows($result) == 0) {
  echo "Ei rullia varastossa...";
}
else {

  $varastot = array();
  $painot = array();

  while ($rulla = mysql_fetch_assoc($result)) {

    $varastopaikka = $rulla['hyllyalue'] . "-" . $rulla['hyllynro'];

    $varastot[$varastopaikka][] = $rulla;
    $painot[$varastopaikka] = $painot[$varastopaikka] + $rulla['massa'];
    $statukset[$varastopaikka][] = $rulla['status'];

  }

  foreach ($statukset as $vp => $status) {
    $statukset[$vp] = array_count_values($status);
  }

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Varastopaikka")."</th>";
  echo "<th>".t("Rullien m‰‰r‰")."</th>";
  echo "<th>".t("Tilausnumerot")."</th>";
  echo "<th>".t("Statukset")."</th>";
  echo "<th>".t("Yhteispaino")."</th>";
  echo "</tr>";

  foreach ($varastot as $vp => $rullat) {

    echo "<tr>";

    echo "<td valign='top' align='center'>";
    echo $vp;
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo count($rullat);
    echo " kpl</td>";

    echo "<td valign='top' align='center'>";
    $tilausnumerot = array();
    foreach ($rullat as $rulla) {
      if (!in_array($rulla['asiakkaan_tilausnumero'], $tilausnumerot)) {
        $tilausnumerot[] = $rulla['asiakkaan_tilausnumero'];
        echo $rulla['asiakkaan_tilausnumero'], '<br>';
      }
    }
    echo "</td>";

    echo "<td valign='top' align='center'>";
    foreach ($statukset[$vp] as $status => $kpl) {
      echo $status, ' ', $kpl, ' kpl<br>';
    }
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo $painot[$vp];
    echo " kg</td>";

    echo "</tr>";
  }
  echo "</table>";

}

require "inc/footer.inc";
