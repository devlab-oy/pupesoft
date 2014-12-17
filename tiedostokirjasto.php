<?php

if (@include "inc/parametrit.inc") {
}
elseif (@include "parametrit.inc") {
}
else {
  exit;
}

require_once("tiedostofunkkarit.inc");

$toim           = isset($toim) ? strtoupper($toim) : "";
$aihealue       = isset($aihealue) ? $aihealue : "";
$tiedostotyyppi = isset($tiedostotyyppi) ? $tiedostotyyppi : "";
$toimittaja     = isset($toimittaja) ? $toimittaja : "";
$tee            = isset($tee) ? $tee : "";

if ($toim == "LAATU") {
  $otsikko     = "Laatu-asiakirjat";
  $ylaotsikko  = "Aihealueet";
  $aihealueet  = hae_aihealueet();
  $toimittajat = "";
}
else {
  $otsikko     = "Tiedostokirjasto";
  $ylaotsikko  = "Toimittajat";
  $toimittajat = hae_toimittajat_selectiin();
  $aihealueet  = "";
}

echo "<font class='head'>" . t($otsikko) . "</font>";
echo "<hr>";

$params = array(
  "aihealue"               => $aihealue,
  "tiedoston_tyyppi"       => $tiedostotyyppi,
  "valittu_toimittaja"     => $toimittaja,
  "ylaotsikko"             => $ylaotsikko,
  "toimittajat"            => $toimittajat,
  "aihealueet"             => $aihealueet,
  "valittu_aihealue"       => $aihealue,
  "valittu_tiedostotyyppi" => $tiedostotyyppi
);

if ($tee == 'hae_tiedostot' and !empty($tiedostotyyppi)) {
  $tiedostot = hae_tiedostot($params);

  piirra_formi($params);
  piirra_tiedostolista($tiedostot);
}
else {
  $tee = "";
}

if ($tee == "") {
  if ($toim == "LAATU" and empty($aihealueet)) {
    echo "<font class='error'>" . t("Aihealueita ei ole vielä lisätty") . "</font>";
  }
  else {
    piirra_formi($params);
  }
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

function piirra_formi($params) {
  global $toim;

  $ylaotsikko             = isset($params["ylaotsikko"]) ? $params["ylaotsikko"] : "";
  $toimittajat            = isset($params["toimittajat"]) ? $params["toimittajat"] : "";
  $aihealueet             = isset($params["aihealueet"]) ? $params["aihealueet"] : "";
  $valittu_aihealue       = isset($params["valittu_aihealue"]) ? $params["valittu_aihealue"] : "";
  $valittu_tiedostotyyppi =
    isset($params["valittu_tiedostotyyppi"]) ? $params["valittu_tiedostotyyppi"] : "";
  $valittu_toimittaja     =
    isset($params["valittu_toimittaja"]) ? $params["valittu_toimittaja"] : "";
  $tiedostotyypit         = tiedostotyypit($valittu_aihealue);

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='hae_tiedostot'>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='ylaotsikko'>" . t($ylaotsikko) . "</label></td>";
  echo "<td>";

  if (!empty($toimittajat)) {
    echo '<select id="ylaotsikko" name="toimittaja" onchange="submit();">';

    foreach ($toimittajat as $toimittaja) {
      $valittu = $valittu_toimittaja == $toimittaja['tunnus'] ? "selected" : "";
      echo "<option {$valittu} value='{$toimittaja['tunnus']}'>{$toimittaja['nimi']}</option>";
    }
  }
  elseif ($aihealueet) {
    echo '<select id="ylaotsikko" name="aihealue" onchange="submit();">';
    foreach ($aihealueet as $aihealue) {
      $valittu = $valittu_aihealue == $aihealue['selite'] ? "selected" : "";
      echo "<option {$valittu} value='{$aihealue['selite']}'>{$aihealue['selite']}</option>";
    }
  }

  echo '</select>';
  echo "</td>";
  echo "</tr>";

  if ($valittu_aihealue and $tiedostotyypit) {
    echo "<tr>";
    echo "<td><label for='tiedostotyyppi'>" . t("Tiedoston tyyppi") . "</label></td>";
    echo "<td>";
    echo "<select id='tiedostotyyppi' name='tiedostotyyppi' onchange='submit()'>";

    foreach ($tiedostotyypit as $tiedostotyyppi) {
      $valittu = $valittu_tiedostotyyppi == $tiedostotyyppi['selitetark'] ? "selected" : "";
      echo "<option value='{$tiedostotyyppi["selitetark"]}'
                    {$valittu}>{$tiedostotyyppi["selitetark"]}
            </option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }
  elseif ($tiedostotyypit) {
    echo "<tr>";
    echo "<td><label for='tiedostotyyppi'>" . t("Tiedoston tyyppi") . "</label></td>";
    echo "<td>";
    echo "<select id='tiedostotyyppi' name='tiedostotyyppi' onchange='submit();'>";

    foreach ($tiedostotyypit as $tiedostotyyppi) {
      $tiedostotyyppinimi =
        t_avainsana("LITETY", '', "and selite = '{$tiedostotyyppi}'", '', '', "selitetark");
      $valittu            = $valittu_tiedostotyyppi == $tiedostotyyppi ? "selected" : "";
      echo "<option value='{$tiedostotyyppi}'
                    {$valittu}>{$tiedostotyyppinimi}
            </option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }
  elseif ($valittu_aihealue) {
    echo
      "<tr>
         <td colspan='2'>
           <font class='error'>" .
      t("Tälle aihealueelle ei ole vielä lisätty tiedostotyyppejä") .
      "</font>
         </td>
       </tr>";
  }

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
             INNER JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio
               AND tuotteen_toimittajat.tuoteno    = tuote.tuoteno)
             INNER JOIN liitetiedostot ON (liitetiedostot.yhtio = tuotteen_toimittajat.yhtio
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
    echo t("Valitulle toimittajalle ei löytynyt valitun tyyppisiä liitetiedostoja");
    echo "</font>";

    return;
  }

  echo "<br>";
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
