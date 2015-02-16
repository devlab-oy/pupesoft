<?php

function hae_toimittajat_selectiin() {
  global $kukarow;

  $result    = t_avainsana('TOIMIT_TKIRJAST');
  $tunnukset = array();

  while ($toimittaja = mysql_fetch_assoc($result)) {
    array_push($tunnukset, $toimittaja["selite"]);
  }

  if (!empty($tunnukset)) {
    $tunnukset = implode(",", $tunnukset);
    $query     = "SELECT tunnus, nimi
                  FROM toimi
                        WHERE yhtio = '{$kukarow["yhtio"]}'
                        AND tunnus  IN ({$tunnukset})
                  ORDER BY toimi.nimi";

    $result = pupe_query($query);
    $toimittajat = array();

    while ($toimittaja = mysql_fetch_assoc($result)) {
      array_push($toimittajat, $toimittaja);
    }

    return $toimittajat;
  }

  return false;
}

function tiedostotyypit($aihealue = "") {
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
    $query = "SELECT selite
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
  $valittu_toimittaja = isset($params["valittu_toimittaja"]) ? $params["valittu_toimittaja"] : "";
  $aihealue           = isset($params["aihealue"]) ? $params["aihealue"] : "";

  global $kukarow;

  $tiedoston_tyyppi = strtolower($tiedoston_tyyppi);

  if ($valittu_toimittaja) {
    $query = "SELECT liitetiedostot.tunnus,
              liitetiedostot.kayttotarkoitus,
              liitetiedostot.selite,
              tuotteen_toimittajat.tuoteno,
              tuote.tunnus
              FROM tuotteen_toimittajat
              INNER JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio
                AND tuotteen_toimittajat.tuoteno    = tuote.tuoteno)
              INNER JOIN liitetiedostot ON (liitetiedostot.yhtio = tuotteen_toimittajat.yhtio
                AND liitetiedostot.liitos           = 'tuote'
                AND liitetiedostot.liitostunnus     = tuote.tunnus
                AND liitetiedostot.kayttotarkoitus  = '{$tiedoston_tyyppi}')
              WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
              AND tuotteen_toimittajat.liitostunnus = '{$valittu_toimittaja}'
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
