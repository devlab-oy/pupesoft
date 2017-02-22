<?php

if (strpos($_SERVER['SCRIPT_NAME'], "nayta_tyomaarayksen_tapahtumat.php") !== FALSE) {
  require "parametrit.inc";
}

if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

if (!isset($tyomaaraystunnus) or !is_numeric($tyomaaraystunnus)) die(t("Parametrejä puuttuu! - Ohjelmaa ei voida käyttää"));

piirra_tapahtumahistoria($tyomaaraystunnus);

function piirra_tapahtumahistoria($tyomaaraystunnus) {
  $tapahtumahistoria = hae_tapahtumahistoria($tyomaaraystunnus);

  echo "<font class='head'>".t("Huoltopyynnön %s tapahtumahistoria", '', $tyomaaraystunnus)."</font><hr>";
  echo "<table>";
  echo "<tr>";
  echo "<th>".t('Työstatus')."</th>";
  echo "<th>".t('Muutosaika')."</th>";
  foreach ($tapahtumahistoria as $tapahtuma) {
    echo "<tr><td>{$tapahtuma[0]}</td><td>{$tapahtuma[1]}</td></tr>";
  }
  echo "</tr>";
  echo "</table>";
}

function hae_tapahtumahistoria($tyomaaraystunnus) {
  global $kukarow;

  $tapahtumahistoria = array();
  $query = "SELECT tyomaarayksen_tapahtumat.*,
            ifnull(tilataulu.selitetark_5, '".t('Status ei ole nähtävissä')."') tilassa,
            ifnull(jonotaulu.selitetark, '') jonossa
            FROM tyomaarayksen_tapahtumat
            JOIN avainsana tilataulu ON tilataulu.yhtio = tyomaarayksen_tapahtumat.yhtio
              AND tilataulu.laji                           = 'TYOM_TYOSTATUS'
              AND tilataulu.selite                         = tyomaarayksen_tapahtumat.tyostatus_selite
              AND tilataulu.selitetark_5                  != ''
              AND tilataulu.kieli                          = '{$kukarow['kieli']}'
            LEFT JOIN avainsana jonotaulu ON jonotaulu.yhtio = tyomaarayksen_tapahtumat.yhtio
              AND jonotaulu.laji                           = 'TYOM_TYOJONO'
              AND jonotaulu.selite                         = tyomaarayksen_tapahtumat.tyojono_selite
            WHERE tyomaarayksen_tapahtumat.yhtio           = '{$kukarow['yhtio']}'
            AND tyomaarayksen_tapahtumat.tyomaarays_tunnus = '$tyomaaraystunnus'
            ORDER BY tyomaarayksen_tapahtumat.luontiaika desc";
  $historiares = pupe_query($query);

  if (mysql_affected_rows() > 0) {
    while ($row = mysql_fetch_assoc($historiares)) {
      $tapahtumahistoria[$row['tunnus']][] = $row['tilassa'];
      $aika = strftime("%d.%m.%y %H:%M", strtotime($row['luontiaika']));
      $tapahtumahistoria[$row['tunnus']][] = $aika;
    }
  }
  return $tapahtumahistoria;
}
