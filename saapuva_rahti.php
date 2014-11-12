<?php

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (!isset($errors)) $errors = array();

$query = "SELECT
          trlt.rahtikirja_id AS rahtikirja,
          group_concat(DISTINCT trlt.asiakkaan_tilausnumero) AS tilaukset,
          group_concat(DISTINCT trlt.asiakkaan_rivinumero) AS rivit,
          count(tr.tunnus) AS rullia,
          sum(ss.massa) AS paino,
          lasku.luontiaika,
          group_concat(DISTINCT tr.toimitettuaika) AS ajat,
          group_concat(DISTINCT ss.varasto) AS varastot,
          sum(if(ss.varasto IS NULL, 0, 1)) AS varastossa,
          sum(if(ss.lisatieto = 'Hyl‰tty', 1, 0)) AS hylatyt,
          sum(if(ss.lisatieto = 'Hyl‰t‰‰n', 1, 0)) AS hylattavat,
          sum(if(ss.lisatieto = 'Lusattava', 1, 0)) AS lusattavat
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
          WHERE lasku.yhtio = 'demo'
          AND tila = 'O'
          GROUP BY lasku.tunnus
          ORDER BY trlt.rahtikirja_id";

echo $query;
echo '<hr>';

$result = pupe_query($query);

echo "<font class='head'>".t("Saapuva rahti")."</font><hr><br>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Rahtikirja#")."</th>";
echo "<th>".t("Tilausnumerot")."</th>";
echo "<th>".t("Tilausrivit")."</th>";
echo "<th>".t("Rullien m‰‰r‰")."</th>";
echo "<th>".t("Rahdin paino")."</th>";
echo "<th>".t("Sanoma vastaanotettu")."</th>";
echo "<th>".t("Rahti vastaanotettu")."</th>";
echo "<th>".t("Varastoon viety")."</th>";
echo "<th class='back'></th>";
echo "</tr>";

while ($rahti = mysql_fetch_assoc($result)) {

  echo "<tr>";

  echo "<td valign='top'>";
  echo $rahti['rahtikirja'];
  echo "</td>";

  echo "<td valign='top'>";
  echo $rahti['tilaukset'];
  echo "</td>";

  echo "<td valign='top' align='center'>";
  echo $rahti['rivit'];
  echo "</td>";

  echo "<td valign='top' align='center'>";
  echo $rahti['rullia'] . " kpl";

  $poikkeukset = array(
    'odottaa hylk‰yst‰' => $rahti['hylattavat'],
    'hylatty' => $rahti['hylatyt'],
    'odottaa lusausta' => $rahti['lusattavat']
    );

  foreach ($poikkeukset as $poikkeus => $kpl) {
    if ($kpl > 0) {
      echo "<br>&bull; " . $kpl . " " . $poikkeus;
    }

  }

  echo "</td>";

  echo "<td valign='top'>";
  echo $rahti['paino'];
  echo "</td>";

  echo "<td valign='top'>";
  echo $rahti['luontiaika'];
  echo "</td>";

  echo "<td valign='top'>";
  echo $rahti['ajat'];
  echo "</td>";

  echo "<td valign='top' align='center'>";
  echo $rahti['varastossa'];
  echo " kpl</td>";

  echo "</tr>";
}
echo "</table>";

require "inc/footer.inc";
