<?php

(@include "inc/parametrit.inc") || (@include "parametrit.inc") || exit;

require_once("tiedostofunkkarit.inc");

echo "<font class='head'>" . t('Tiedostojen tallennus') . "</font>";
echo "<hr>";

$tee            = empty($tee) ? "" : $tee;
$tiedosto       = $_FILES["tiedosto"];
$selite         = isset($selite) ? $selite : "";
$aihealue       = isset($aihealue) ? $aihealue : "";
$tiedostotyyppi = isset($tiedostotyyppi) ? $tiedostotyyppi : "";

if ($tee == "tallenna_tiedosto" and !empty($tiedosto["tmp_name"]) and !empty($selite)) {
  if (tallenna_liite("tiedosto", "muut_tiedostot", 0, $selite, "{$aihealue} | {$tiedostotyyppi}")) {
    echo "<font class='ok'>" . t("Tiedosto tallennettu onnistuneesti") . "</font";
  }

  $tee = "";
}
elseif (!empty($tiedostotyyppi) and empty($tiedosto["tmp_name"])) {
  echo "<font class='error'>" . t("Sinun täytyy valita tiedosto") . "</font>";

  $tee = "";
}
elseif (!empty($tiedostotyyppi) and empty($selite)) {
  echo "<font class='error'>" . t("Sinun täytyy valita tiedostolle selite") . "</font>";

  $tee = "";
}
else {
  $tee = "";
}

if ($tee == "") {
  $aihealueet = hae_aihealueet();

  if (!empty($aihealueet)) {
    piirra_formi($aihealue, $tiedostotyyppi, $aihealueet);
  }
  else {
    echo "<font class='error'>" . t("Et ole vielä lisännyt aihealueita avainsanoihin") . "</font>";
  }
}

function piirra_formi($valittu_aihealue, $valittu_tiedostotyyppi, $aihealueet) {
  echo "<form method='post' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='tallenna_tiedosto'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='aihealue'>" . t("Aihealue") . "</label></td>";
  echo "<td>";
  echo '<select id="aihealue" name="aihealue" onchange="submit()">';

  foreach ($aihealueet as $aihealue) {
    $valittu = $valittu_aihealue == $aihealue['selite'] ? "selected" : "";
    echo "<option {$valittu} value='{$aihealue['selite']}'>{$aihealue['selite']}</option>";
  }

  echo '</select>';
  echo "</td>";
  echo "</tr>";

  if (!empty($valittu_aihealue)) {
    echo "<tr>";
    echo "<td><label for='tyyppi_id'>" . t("Tiedoston tyyppi") . "</label></td>";
    echo "<td>";
    echo "<select id='tiedostotyyppi_id' name='tiedostotyyppi'>";

    foreach (tiedostotyypit($valittu_aihealue) as $tiedostotyyppi) {
      $valittu = $valittu_tiedostotyyppi == $tiedostotyyppi ? "selected" : "";
      echo "<option value='{$tiedostotyyppi["selitetark"]}' {$valittu}>{$tiedostotyyppi["selitetark"]}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>
          <td><label for='tiedosto'>" . t("Tiedosto") . "</label></td>
          <td><input type='file' name='tiedosto' id='tiedosto'></td>
          </tr>";

    echo "<tr>
          <td><label for='selite'>" . t("Selite") . "</label></td>
          <td><input type='text' name='selite' id='selite'></td>
          </tr>";
  }

  echo "<tr>";
  echo "<td class='back'>";

  $buttonin_teksti = empty($valittu_aihealue) ? "Jatka" : "Tallenna";

  echo "<input type='submit' value='" . t($buttonin_teksti) . "'/>";
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}
