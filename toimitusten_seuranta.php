<?php

require "inc/parametrit.inc";
require 'mobiili/generoi_edifact.inc';

if (isset($task) and $task == 'hae_parametrit') {
  $parametrit = satamavahvistus_parametrit($konttiviite);

  if ($parametrit) {
    $sanoma = laadi_edifact_sanoma($parametrit);
  }
  else{
    echo 'virhe<br>';
  }

  if (laheta_sanoma($sanoma)) {
    echo "Sanoma lähetetty";
    echo "<hr>";
    echo $sanoma;
    echo "<hr>";
  }
  else{
    echo "Lähetys epäonnistui!";
  }

  echo '<hr>';
}

echo "<font class='head'>".t("Toimitusten seuranta")."</font><br><br>";


// kesken.....
$query = "SELECT lasku.asiakkaan_tilausnumero,
          laskun_lisatiedot.matkakoodi,
          laskun_lisatiedot.konttiviite
          FROM tilausrivi
          JOIN lasku
            ON lasku.yhtio = tilausrivi.yhtio
            AND lasku.tunnus = tilausrivi.otunnus
          JOIN laskun_lisatiedot
            ON laskun_lisatiedot.yhtio = lasku.yhtio
            AND laskun_lisatiedot.otunnus = lasku.tunnus
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND lasku.tilaustyyppi = 'N'
          AND laskun_lisatiedot.konttiviite != ''
          GROUP BY lasku.asiakkaan_tilausnumero";
$result = pupe_query($query);

$toimitukset = array();

while ($rivi = mysql_fetch_assoc($result)) {

  //if (!isset($toimitukset[$rivi['konttiviite']])) {

    $toimitukset[$rivi['konttiviite']][] = $rivi;

  //}

}



echo "<table>";
echo "<tr>";
/*
echo "<th>".t("Tilauskoodi")."</th>";
echo "<th>".t("Matkakoodi")."</th>";
*/
echo "<th>".t("Konttiviite")."</th>";
echo "<th class='back'></th>";
echo "</tr>";

//while ($tilaus = mysql_fetch_assoc($result)) {

foreach ($toimitukset as $key => $value) {

  echo "<tr>";
/*
  echo "<td>";
  echo $tilaus['asiakkaan_tilausnumero'];
  echo "</td>";

  echo "<td>";
  echo $tilaus['matkakoodi'];
  echo "</td>";
*/

  echo "<td>";
  echo $key;
  echo "</td>";

  echo "<td class='back'>";

  echo "
    <form method='post'>
    <input type='hidden' name='konttiviite' value='{$key}' />
    <input type='hidden' name='task' value='hae_parametrit' />
    <input type='submit' value='". t("Lähetä satamavahvistus") ."' />
    </form>";

  echo "</td>";

  echo "</tr>";

}

echo "</table>";



require "inc/footer.inc";
