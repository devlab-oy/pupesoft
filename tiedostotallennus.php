<?php

(@include "inc/parametrit.inc") || (@include "parametrit.inc") || exit;

require_once("tiedostofunkkarit.inc");

echo "<font class='head'>" . t('Tiedostojen tallennus') . "</font>";
echo "<hr>";

$tee                  = empty($tee) ? "" : $tee;
$tiedosto             = $_FILES["tiedosto"];
$selite               = isset($selite) ? $selite : "";
$aihealue             = isset($aihealue) ? $aihealue : "";
$tiedostotyyppi       = isset($tiedostotyyppi) ? $tiedostotyyppi : "";
$listaa_tiedostot     = isset($listaa_tiedostot) ? $listaa_tiedostot : "";
$poistettava_tiedosto = isset($poistettava_tiedosto) ? $poistettava_tiedosto : "";

if ($tee == "poista") {
  poista_liitetiedosto($poistettava_tiedosto);
  $listaa_tiedostot = true;
}
if ($tee == "tallenna_tiedosto" and
    !empty($tiedosto["tmp_name"]) and
    !empty($selite) and
    !$listaa_tiedostot
) {
  if (tallenna_liite("tiedosto", "muut_tiedostot", 0, $selite, "{$aihealue} | {$tiedostotyyppi}")) {
    echo "<font class='ok'>" . t("Tiedosto tallennettu onnistuneesti") . "</font>";
  }

  $tee = "";
}
elseif (!empty($tiedostotyyppi) and
        empty($tiedosto["tmp_name"]) and
        !$listaa_tiedostot and
        $tee != ""
) {
  echo "<font class='error'>" . t("Sinun täytyy valita tiedosto") . "</font>";

  $tee = "";
}
elseif (!empty($tiedostotyyppi) and empty($selite) and !$listaa_tiedostot and $tee != "") {
  echo "<font class='error'>" . t("Sinun täytyy valita tiedostolle selite") . "</font>";

  $tee = "";
}
elseif ($listaa_tiedostot) {
  $params     = array("tiedoston_tyyppi" => $tiedostotyyppi, "aihealue" => $aihealue);
  $tiedostot  = hae_tiedostot($params);
  $aihealueet = hae_aihealueet();

  if (!empty($aihealueet)) {
    piirra_formi($aihealue, $tiedostotyyppi, $aihealueet);
  }
  else {
    echo "<font class='error'>" . t("Et ole vielä lisännyt aihealueita avainsanoihin") . "</font>";
  }

  piirra_tiedostolista($tiedostot, $aihealue, $tiedostotyyppi);
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

  if (!empty($valittu_aihealue) and $tiedostotyypit = tiedostotyypit($valittu_aihealue)) {
    echo "<tr>";
    echo "<td><label for='tyyppi_id'>" . t("Tiedoston tyyppi") . "</label></td>";
    echo "<td>";
    echo "<select id='tiedostotyyppi_id' name='tiedostotyyppi'>";

    foreach ($tiedostotyypit as $tiedostotyyppi) {
      $valittu = $valittu_tiedostotyyppi == $tiedostotyyppi["selitetark"] ? "selected" : "";
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
  elseif (!empty($valittu_aihealue)) {
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

  $buttonin_teksti = (empty($valittu_aihealue) or empty($tiedostotyypit)) ? "Jatka" : "Tallenna";

  echo "<input type='submit' value='" . t($buttonin_teksti) . "'/>";

  if (!empty($valittu_aihealue) and $tiedostotyypit = tiedostotyypit($valittu_aihealue)) {
    echo "<input name='listaa_tiedostot' type='submit' value='Listaa aihealueen tiedostot'>";
  }

  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}

function piirra_tiedostolista($tiedostot, $aihealue, $tiedostotyyppi) {
  if (empty($tiedostot)) {
    echo "<font class='error'>";
    echo t("Tiedostoja ei löytynyt");
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
    echo "<td>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='poista'>";
    echo "<input type='hidden' name='aihealue' value='{$aihealue}'>";
    echo "<input type='hidden' name='tiedostotyyppi' value='{$tiedostotyyppi}'>";
    echo "<input type='hidden' name='poistettava_tiedosto' value='{$tiedosto["tunnus"]}'>";
    echo "<input type='submit' value='Poista'></td>";
    echo "</form>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

function poista_liitetiedosto($tunnus) {
  if ($tunnus) {
    $query =
      "DELETE
       FROM liitetiedostot
       WHERE tunnus = {$tunnus}";

    return pupe_query($query);
  }
  else {
    return false;
  }
}
