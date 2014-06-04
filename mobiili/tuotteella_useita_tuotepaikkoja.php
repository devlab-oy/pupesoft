<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();
if (!isset($viivakoodi)) $viivakoodi = "";
if (!isset($tuotenumero)) $tuotenumero = "";
if (!isset($hyllypaikka)) $hyllypaikka = "";
if (!isset($hyllyalue)) $hyllyalue = "";
if (!isset($hyllynro)) $hyllynro = "";
if (!isset($hyllyvali)) $hyllyvali = "";
if (!isset($hyllytaso)) $hyllytaso = "";

$hae_hyllypaikalla = $hae_tuotenumerolla = $hae_viivakoodilla = false;
$params = array();

# Joku parametri tarvii olla setattu.
if (($hyllyalue != '' and $hyllynro != '' and $hyllyvali != '' and $hyllytaso != '') or $tuotenumero != '' or $viivakoodi != '') {

  if (strpos($tuotenumero, "%") !== FALSE) $tuotenumero = urldecode($tuotenumero);

  if ($tuotenumero != '') {
    $params['tuoteno'] = "tuote.tuoteno = '{$tuotenumero}'";
    $hae_tuotenumerolla = true;
  }

  if ($hyllyalue != '' and $hyllynro != '' and $hyllyvali != '' and $hyllytaso != '') {
    $params['hyllyalue'] = "tuotepaikat.hyllyalue = '{$hyllyalue}'";
    $params['hyllynro'] = "tuotepaikat.hyllynro = '{$hyllynro}'";
    $params['hyllyvali'] = "tuotepaikat.hyllyvali = '{$hyllyvali}'";
    $params['hyllytaso'] = "tuotepaikat.hyllytaso = '{$hyllytaso}'";
    $hae_hyllypaikalla = true;
  }

  // Viivakoodi case
  if ($viivakoodi != '') {
    $tuotenumerot = hae_viivakoodilla($viivakoodi);

    if (count($tuotenumerot) > 0) {

      $param_viivakoodi = array();

      foreach ($tuotenumerot as $_tuoteno => $_arr) {
        array_push($param_viivakoodi, $_tuoteno);
      }

      $params['viivakoodi'] = "tuote.tuoteno in ('" . implode($param_viivakoodi, "','") . "')";
      $hae_viivakoodilla = true;
    }
    else {
      $errors[] = t("Viivakoodilla %s ei lˆytynyt tuotetta", '', $viivakoodi)."<br />";
      $viivakoodi = "";
    }
  }

  $query_lisa = count($params) > 0 ? " AND ".implode($params, " AND ") : "";

}
else {
  # T‰nne ei pit‰is p‰‰ty‰, tarkistetaan jo hyllysiirrot.php:ss‰
  echo t("Parametrivirhe");
  echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=hyllysiirrot.php'>";
  exit();
}

$orderby = "1";
$ascdesc = "desc";

if ($hae_hyllypaikalla and !$hae_tuotenumerolla and !$hae_viivakoodilla) {
  $query = "SELECT tuotepaikat.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuotepaikat.tunnus
            FROM tuotepaikat
            WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
            {$query_lisa}
            ORDER BY {$orderby} {$ascdesc}";
}
else {
  $query = "SELECT tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuotepaikat.tunnus
            FROM tuote
            JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno)
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$query_lisa}
            ORDER BY {$orderby} {$ascdesc}";
}

$result = pupe_query($query);
$tilausten_lukumaara = mysql_num_rows($result);

$url_lisa_arr = array();

### UI ###
echo "<div class='header'>
  <button onclick='window.location.href=\"hyllysiirrot.php\"' class='button left'><img src='back2.png'></button>
  <h1>",t("USEITA HYLLYPAIKKOJA"), "</h1></div>";

echo "<form name='form1' method='post' action=''>";
echo "<table>";
echo "<tr>";
echo "<th>",t("Tuoteno"),"</th>";
echo "<th>",t("Hyllypaikka"),"</th>";
echo "</tr>";

$_cnt = 0;

# Loopataan ostotilaukset
while($row = mysql_fetch_assoc($result)) {

  list($siirrettava_yht, $siirrettavat_rivit) = laske_siirrettava_maara($row);

  $_cnt++;

  echo "<tr>";
  echo "<td><a href='tuotteen_hyllypaikan_muutos.php?tuotepaikan_tunnus={$row['tunnus']}'>{$row['tuoteno']}</td>";
  echo "<td>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>";
  echo "<tr>";
}

echo "</table></div>";

if ($_cnt == 0) {
  echo "<span class='error'>",t("Yht‰‰n siirre‰tt‰v‰‰ tuotetta / tuotepaikkaa ei ole"),"</span>";
}

echo "<div class='controls'></div>";
echo "</form>";

require('inc/footer.inc');
