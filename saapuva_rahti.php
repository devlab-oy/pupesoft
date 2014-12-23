<?php

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (!isset($errors)) $errors = array();

if (isset($tnum)) {
  $lisa = " AND trlt.asiakkaan_tilausnumero = '{$tnum}' ";
}
else {
  $lisa = '';
}

$query = "SELECT
          trlt.rahtikirja_id AS rahtikirja,
          group_concat(DISTINCT trlt.asiakkaan_tilausnumero SEPARATOR '<br>') AS tilaukset,
          group_concat(DISTINCT trlt.asiakkaan_rivinumero) AS rivit,
          count(tr.tunnus) AS rullia,
          sum(ss.massa) AS paino,
          lasku.luontiaika,
          tr.toimitettuaika,
          group_concat(DISTINCT ss.varasto) AS varastot,
          sum(if(ss.varasto IS NULL, 0, 1)) AS varastossa,
          sum(if(ss.lisatieto = 'Hyl�tty', 1, 0)) AS hylatyt,
          sum(if(ss.lisatieto = 'Hyl�t��n', 1, 0)) AS hylattavat,
          sum(if(ss.lisatieto = 'Lusattava', 1, 0)) AS lusattavat,
          sum(if(ss.lisatieto = 'Toimitettu', 1, 0)) AS toimitettu,
          trlt.kuljetuksen_rekno
          FROM lasku
          JOIN tilausrivi AS tr
            ON tr.yhtio = lasku.yhtio
            AND tr.otunnus = lasku.tunnus
          JOIN tilausrivin_lisatiedot AS trlt
            ON trlt.yhtio = lasku.yhtio
            AND trlt.tilausrivitunnus = tr.tunnus
          JOIN sarjanumeroseuranta AS ss
            ON ss.yhtio = lasku.yhtio
            AND ss.ostorivitunnus = tr.tunnus
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          {$lisa}
          GROUP BY lasku.tunnus
          ORDER BY trlt.rahtikirja_id";
$result = pupe_query($query);

echo "<font class='head'>".t("Saapuva rahti")."</font><hr><br>";

if (mysql_num_rows($result) == 0) {
  echo "Ei rahteja...";
}
else {

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Rahtikirja#")."</th>";
  echo "<th>".t("Tilausnumerot")."</th>";
  echo "<th>".t("Tilausrivit")."</th>";
  echo "<th>".t("Rullien m��r�")."</th>";
  echo "<th>".t("Rahdin paino")."</th>";
  echo "<th>".t("Rekisterinumero")."</th>";
  echo "<th>".t("Sanoma vastaanotettu")."</th>";
  echo "<th>".t("Rahti vastaanotettu")."</th>";
  echo "<th>".t("Varastoon viety")."</th>";
  echo "<th>".t("Toimitettu")."</th>";
  echo "<th class='back'></th>";
  echo "</tr>";

  while ($rahti = mysql_fetch_assoc($result)) {

    echo "<tr>";

    $liite_query = "SELECT tunnus
                    FROM liitetiedostot
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND kayttotarkoitus = 'rahtikirjasanoma'
                    AND selite = '{$rahti['rahtikirja']}'";
    $liite_result = pupe_query($liite_query);
    $liite = mysql_result($liite_result, 0);

    echo "<td valign='top' align='center'>";
    echo "<a href='view.php?id={$liite}' target='_blank'>" . $rahti['rahtikirja'] . "</a>";
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo $rahti['tilaukset'];
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo $rahti['rivit'];
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo $rahti['rullia'] . " kpl";

    $poikkeukset = array(
      'odottaa hylk�yst�' => $rahti['hylattavat'],
      'hylatty' => $rahti['hylatyt'],
      'odottaa lusausta' => $rahti['lusattavat']
      );

    foreach ($poikkeukset as $poikkeus => $kpl) {
      if ($kpl > 0) {
        echo "<br>&bull; " . $kpl . " " . $poikkeus;
      }
    }

    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo (int) $rahti['paino'];
    echo " kg </td>";

    echo "<td valign='top' align='center'>";
    echo $rahti['kuljetuksen_rekno'];
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo date("j.n.Y H:i", strtotime($rahti['luontiaika']));
    echo "</td>";

    if ($rahti['toimitettuaika'] == '0000-00-00 00:00:00') {
      $vastaanotettu = t("Ei viel�");
    }
    else {
      $vastaanotettu = date("j.m.Y H:i", strtotime($rahti['toimitettuaika']));
    }

    echo "<td valign='top' align='center'>";
    echo $vastaanotettu;
    echo "</td>";

    echo "<td valign='top' align='center'>";
    echo $rahti['varastossa'];
    echo " kpl</td>";

    echo "<td valign='top' align='center'>";
    echo $rahti['toimitettu'];
    echo " kpl</td>";

    echo "</tr>";
  }
  echo "</table>";

}

require "inc/footer.inc";
