<?php

(@include "inc/parametrit.inc") || (@include "parametrit.inc") || exit;

require_once("tiedostofunkkarit.inc");

echo "<font class='head'>" . t('Tiedostojen tallennus') . "</font>";
echo "<hr>";

$tee                  = isset($tee) ? $tee : "";
$selite               = isset($selite) ? $selite : "";
$aihealue             = isset($aihealue) ? $aihealue : "";
$tiedostotyyppi       = isset($tiedostotyyppi) ? $tiedostotyyppi : "";
$listaa_tiedostot     = isset($listaa_tiedostot) ? $listaa_tiedostot : "";
$poistettava_tiedosto = isset($poistettava_tiedosto) ? $poistettava_tiedosto : "";
$tallenna_nappi       = isset($tallenna_nappi) ? $tallenna_nappi : "";
$tiedosto             = isset($_FILES["tiedosto"]) ? $_FILES["tiedosto"] : "";

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

  $listaa_tiedostot = true;
}

if (!empty($tiedostotyyppi) and
    empty($tiedosto["tmp_name"]) and
    !$listaa_tiedostot and
    $tee != "" and
    $tallenna_nappi
) {
  echo "<font class='error'>" . t("Sinun täytyy valita tiedosto") . "</font>";

  $tee = "";
}

if (!empty($tiedostotyyppi) and
    empty($selite) and
    !$listaa_tiedostot and
    $tee != "" and
    $tallenna_nappi
) {
  echo "<font class='error'>" . t("Sinun täytyy valita tiedostolle selite") . "</font>";

  $tee = "";
}

if ($listaa_tiedostot) {
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
  $params     = array("tiedoston_tyyppi" => $tiedostotyyppi, "aihealue" => $aihealue);
  $tiedostot  = hae_tiedostot($params);

  if (!empty($aihealueet)) {
    piirra_formi($aihealue, $tiedostotyyppi, $aihealueet);
    if ($tiedostot) {
      piirra_tiedostolista($tiedostot, $aihealue, $tiedostotyyppi);
    }
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
    echo "<select id='tiedostotyyppi_id' name='tiedostotyyppi' onclick='submit();'>";

    foreach ($tiedostotyypit as $tiedostotyyppi) {
      $valittu = $valittu_tiedostotyyppi == $tiedostotyyppi["selitetark"] ? "selected" : "";
      echo "<option value='{$tiedostotyyppi["selitetark"]}'
                    {$valittu}>{$tiedostotyyppi["selitetark"]}</option>";
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

  echo "<input name='tallenna_nappi' type='submit' value='" . t($buttonin_teksti) . "'/>";

  if (!empty($valittu_aihealue) and $tiedostotyypit = tiedostotyypit($valittu_aihealue)) {
    echo "<input name='listaa_tiedostot' type='submit' value='Listaa aihealueen valitun tyyppiset tiedostot'>";
  }

  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}

function piirra_tiedostolista($tiedostot, $aihealue, $tiedostotyyppi) {
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
    echo "<input type='submit'
                 value='Poista'
                 onclick='return confirm(\"Oletko varma, että haluat poistaa tämän tiedoston\");'>
          </td>";
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
