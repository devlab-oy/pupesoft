<?php

require "inc/parametrit.inc";

echo "<font class='head'>" . t('Tiedostokirjasto') . "</font>";
echo "<hr>";

$tee = empty($tee) ? '' : $tee;

if ($tee == 'hae_tiedostot') {
  $tuotetunnukset = hae_tuotetunnukset($toimittaja);
  $tiedostot = hae_tiedostot($tuotetunnukset, $tiedostotyyppi);

  piirra_formi();
  piirra_tiedostolista($tiedostot);
}

if ($tee == '') {
  piirra_formi();
}

function hae_toimittajat() {
  global $kukarow;

  $query = "SELECT toimi.tunnus, toimi.nimi
            FROM toimi
            WHERE toimi.yhtio = 'Mesta'
            AND EXISTS (SELECT tuotteen_toimittajat.liitostunnus
              FROM tuotteen_toimittajat
              WHERE tuotteen_toimittajat.liitostunnus = toimi.tunnus)
            ORDER BY toimi.nimi";
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
  echo "<input type='hidden' name='tee' value='hae_tiedostot'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='toimittaja_id'>" . t("Valitse toimittaja") . "</label></td>";
  echo "<td>";
  echo '<select id="toimittaja_id" name="toimittaja">';
  foreach ($toimittajat as $toimittaja) {
    echo "<option value='{$toimittaja['tunnus']}'>{$toimittaja['nimi']}</option>";
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

function hae_tiedostot($tuotetunnukset, $tiedoston_tyyppi) {
  global $kukarow;

  $tuotetunnukset_lista = implode(',', $tuotetunnukset);

  $query = "SELECT liitetiedostot.tunnus, liitetiedostot.selite
            FROM liitetiedostot
            WHERE liitetiedostot.liitostunnus IN ({$tuotetunnukset_lista})
            AND liitetiedostot.selite = '{$tiedoston_tyyppi}'
            AND liitetiedostot.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $tiedostot = array();

  while ($tiedosto = mysql_fetch_assoc($result)) {
    array_push($tiedostot, $tiedosto);
  }

  return $tiedostot;
}

function hae_tuotetunnukset($toimittajan_tunnus) {
  global $kukarow;

  $query = "SELECT tuote.tunnus
            FROM tuote
            INNER JOIN tuotteen_toimittajat ON (tuote.tuoteno = tuotteen_toimittajat.tuoteno
              AND tuote.yhtio = tuotteen_toimittajat.yhtio)
            WHERE tuotteen_toimittajat.liitostunnus = '{$toimittajan_tunnus}'
            AND tuote.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $tunnukset = array();

  while ($tuote = mysql_fetch_assoc($result)) {
    array_push($tunnukset, $tuote['tunnus']);
  }

  return $tunnukset;
}

function piirra_tiedostolista($tiedostot) {
  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Selite") . "</th>";
  echo "<th></th>";
  echo "</tr>";

  echo "</thead>";
  echo "<tbody>";

  foreach ($tiedostot as $tiedosto) {
    echo "<tr>";
    echo "<td>{$tiedosto['selite']}</td>";
    echo "<td>";
    echo "<a href='view.php?id={$tiedosto['tunnus']}' target='Attachment'>";
    echo t("Lataa tiedosto");
    echo "</a>";
    echo "</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}
