<?php

function hae_toimittajat_selectiin() {
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

function tiedostotyypit($aihealue) {
  global $kukarow;

  if ($aihealue) {
    $avainsanat = t_avainsana("LITETY_LAATUAS", "", "AND selite = '{$aihealue}'");

    $tiedostotyypit = array();

    while ($tiedostotyyppi = mysql_fetch_assoc($avainsanat)) {
      array_push($tiedostotyypit, $tiedostotyyppi);
    }

    return $tiedostotyypit;
  }
  else {
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
}

function hae_tiedostot($params) {
  $tiedoston_tyyppi   = isset($params["tiedoston_tyyppi"]) ? $params["tiedoston_tyyppi"] : "";
  $toimittajan_tunnus = isset($params["toimittajan_tunnus"]) ? $params["toimittajan_tunnus"] : "";
  $aihealue           = isset($params["aihealue"]) ? $params["aihealue"] : "";

  global $kukarow;

  $tiedoston_tyyppi = strtolower($tiedoston_tyyppi);

  if ($toimittajan_tunnus) {
    $query = "SELECT liitetiedostot.tunnus,
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
  }
  elseif ($aihealue and $tiedoston_tyyppi) {
    $query = "SELECT liitetiedostot.tunnus,
               liitetiedostot.kayttotarkoitus,
               liitetiedostot.selite
               FROM liitetiedostot
               WHERE liitetiedostot.liitos        = 'muut_tiedostot'
               AND liitetiedostot.liitostunnus    = 0
               AND liitetiedostot.kayttotarkoitus = '{$aihealue} | {$tiedoston_tyyppi}'
               AND liitetiedostot.yhtio           = '{$kukarow["yhtio"]}'
               ORDER BY liitetiedostot.selite";
  }

  if (empty($query)) {
    return false;
  }

  $result = pupe_query($query);

  $tiedostot = array();

  while ($tiedosto = mysql_fetch_assoc($result)) {
    array_push($tiedostot, $tiedosto);
  }

  return $tiedostot;
}

function hae_aihealueet() {
  $avainsanat = t_avainsana("AIHEAL_LAATUAS");

  $aihealueet = array();

  while ($avainsana = mysql_fetch_assoc($avainsanat)) {
    array_push($aihealueet, $avainsana);
  }

  return $aihealueet;
}

function piirra_tiedostolista($tiedostot) {
  if (empty($tiedostot)) {
    echo "<font class='error'>";
    echo t("Valitulle toimittajalle ei löytynyt valitun tyyppisiä liitetiedostoja");
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
