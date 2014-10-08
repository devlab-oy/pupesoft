<?php

(@include "inc/parametrit.inc") || (@include "parametrit.inc") || exit;

echo "<font class='head'>" . t('Tiedostokirjasto') . "</font>";
echo "<hr>";

$tee = empty($tee) ? '' : $tee;

if ($tee == 'hae_tiedostot') {
  $tiedostot = hae_tiedostot($toimittaja, $tiedostotyyppi);

  piirra_formi($toimittaja, $tiedostotyyppi);
  piirra_tiedostolista($tiedostot);
}

if ($tee == '') {
  piirra_formi($toimittaja, $tiedostotyyppi);
}

function hae_toimittajat() {
  global $kukarow;

  $query  = "SELECT DISTINCT toimi.tunnus, toimi.nimi
             FROM tuotteen_toimittajat
             INNER JOIN toimi ON (toimi.tunnus = tuotteen_toimittajat.liitostunnus)
             WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
             ORDER BY toimi.nimi";
  $result = pupe_query($query);

  $toimittajat = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($toimittajat, $rivi);
  }

  return $toimittajat;
}

function tiedostotyypit() {
  global $kukarow;

  $query  = "SELECT selite
             FROM avainsana
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND laji    = 'LITETY_TKIRJAST'";
  $result = pupe_query($query);

  $tiedostotyypit = array();

  while ($tiedostotyyppi = mysql_fetch_assoc($result)) {
    array_push($tiedostotyypit, strtolower($tiedostotyyppi['selite']));
  }

  return $tiedostotyypit;
}

function piirra_formi($valittu_toimittaja, $valittu_tiedostotyyppi) {
  $toimittajat = hae_toimittajat();

  echo "<form action='tiedostokirjasto.php' method='post'>";
  echo "<input type='hidden' name='tee' value='hae_tiedostot'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='toimittaja_id'>" . t("Toimittaja") . "</label></td>";
  echo "<td>";
  echo '<select id="toimittaja_id" name="toimittaja">';
  foreach ($toimittajat as $toimittaja) {
    $valittu = $valittu_toimittaja == $toimittaja['tunnus'] ? "selected" : "";
    echo "<option {$valittu} value='{$toimittaja['tunnus']}'>{$toimittaja['nimi']}</option>";
  }
  echo '</select>';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='tyyppi_id'>" . t("Tiedoston tyyppi") . "</label></td>";
  echo "<td>";
  echo "<select id='tiedostotyyppi_id' name='tiedostotyyppi'>";
  foreach (tiedostotyypit() as $tiedostotyyppi) {    
    $tiedostotyyppinimi = t_avainsana("LITETY", '', "and selite = '{$tiedostotyyppi}'", '', '', "selitetark");
    $valittu = $valittu_tiedostotyyppi == $tiedostotyyppi ? "selected" : "";
    echo "<option value='{$tiedostotyyppi}' {$valittu}>{$tiedostotyyppinimi}</option>";
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

function hae_tiedostot($toimittajan_tunnus, $tiedoston_tyyppi) {
  global $kukarow;

  $tiedoston_tyyppi = strtolower($tiedoston_tyyppi);

  $query  = "SELECT liitetiedostot.tunnus,
             liitetiedostot.kayttotarkoitus,
             liitetiedostot.selite
             FROM tuotteen_toimittajat
             INNER JOIN tuote ON (tuote.yhtio = '{$kukarow['yhtio']}'
               AND tuotteen_toimittajat.tuoteno    = tuote.tuoteno)
             INNER JOIN liitetiedostot ON (liitetiedostot.yhtio = '{$kukarow['yhtio']}'
               AND liitetiedostot.liitos           = 'tuote'
               AND liitetiedostot.liitostunnus     = tuote.tunnus
               AND liitetiedostot.kayttotarkoitus  = '{$tiedoston_tyyppi}')
             WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
             AND tuotteen_toimittajat.liitostunnus = '{$toimittajan_tunnus}'
             ORDER BY liitetiedostot.selite";
  $result = pupe_query($query);

  $tiedostot = array();

  while ($tiedosto = mysql_fetch_assoc($result)) {
    array_push($tiedostot, $tiedosto);
  }

  return $tiedostot;
}

function piirra_tiedostolista($tiedostot) {
  if (empty($tiedostot)) {
    echo "<font class='error'>";
    echo t("Valitulle toimittajalle ei l�ytynyt valitun tyyppisi� liitetiedostoja");
    echo "</font>";

    return;
  }

  echo "<table>";
  echo "<tbody>";

  foreach ($tiedostot as $tiedosto) {
    echo "<tr>";
    echo "<td>";
    echo "<a href='view.php?id={$tiedosto['tunnus']}'
             target='Attachment'>{$tiedosto['selite']}</a>";
    echo "</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}
