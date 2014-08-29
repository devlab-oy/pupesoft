<?php

require "inc/parametrit.inc";

echo "<font class='head'>" . t('Tiedostokirjasto') . "</font>";
echo "<hr>";

$tee = empty($tee) ? '' : $tee;

if ($tee == '') {
  piirra_formi();
}

function hae_toimittajat() {
  global $kukarow;

  $query = "SELECT tuotteen_toimittajat.tunnus, tuotteen_toimittajat.toim_nimitys
            FROM tuote
            INNER JOIN tuotteen_toimittajat ON (tuote.tuoteno = tuotteen_toimittajat.tuoteno)
            WHERE tuotteen_toimittajat.toim_nimitys IS NOT NULL
            AND tuotteen_toimittajat.toim_nimitys != ''
            AND tuote.yhtio = '{$kukarow['yhtio']}'
            ORDER BY tuotteen_toimittajat.toim_nimitys;";
  $result = pupe_query($query);

  $toimittajat = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($toimittajat, $rivi);
  }

  return $toimittajat;
}

function tiedostotyypit() {
  return array(
    "Ohjekirja",
    "Huoltotiedote"
  );
}

function piirra_formi() {
  $toimittajat = hae_toimittajat();

  echo "<form action='tiedostokirjasto.php' method='post'>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='toimittaja_id'>" . t("Valitse toimittaja") . "</label></td>";
  echo "<td>";
  echo '<select id="toimittaja_id" name="toimittaja">';
  foreach ($toimittajat as $toimittaja) {
    echo "<option value='{$toimittaja['tunnus']}'>{$toimittaja['toim_nimitys']}</option>";
  }
  echo '</select>';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='tyyppi_id'>" . t("Tiedoston tyyppi") . "</label></td>";
  echo "<td>";
  echo "<select id='tiedostotyyppi_id' name='tiedostotyyppi'>";
  foreach (tiedostotyypit() as $tiedostotyyppi) {
    echo "<option>{$tiedostotyyppi}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'>";
  echo "<input type='submit' value='" . t("Hae") . "'/>";
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}
