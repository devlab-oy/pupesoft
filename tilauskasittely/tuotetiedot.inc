<?php

$keskihinta  = isset($keskihinta)  ? $keskihinta  : null;
$ostohinta   = isset($ostohinta)   ? $ostohinta   : null;
$paikka      = isset($paikka)      ? $paikka      : null;
$tyyppi      = isset($tyyppi)      ? $tyyppi      : "O";
$tuoteno     = isset($tuoteno)     ? $tuoteno     : null;
$valuutta    = isset($valuutta)    ? $valuutta    : null;
$vanhatunnus = isset($vanhatunnus) ? $vanhatunnus : 0;
$varasto     = isset($varasto)     ? $varasto     : null;
$yksikko     = isset($yksikko)     ? $yksikko     : null;
$myyntihinta = isset($myyntihinta) ? $myyntihinta : null;
$myymalahinta = isset($myymalahinta) && $myymalahinta > 0 ? $myymalahinta : null;


$_varastot = array($varasto);

if ($vanhatunnus != 0) {
  $query = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
            FROM varastopaikat
            WHERE yhtio      = '{$kukarow['yhtio']}'
            AND tyyppi      != 'P'
            AND toimipaikka  = '{$vanhatunnus}'";
  $vares = pupe_query($query);
  $varow = mysql_fetch_assoc($vares);

  $saldo = $hyllyssa = $myytavissa = 0;

  if (!empty($varow['tunnukset'])) {
    $_varastot_tmp = explode(",", $varow['tunnukset']);
    $_varastot     = array_merge($_varastot, $_varastot_tmp);
  }
}

list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', $_varastot);

$pop_yks = t_avainsana("Y", "", "and avainsana.selite='{$yksikko}'", "", "", "selite");

$tilattu = tilattu($tuoteno, "O");
$varattu = tilattu($tuoteno, "L");

echo "<ul>";
echo "<li>" . t("Saldo") . ": {$saldo} {$pop_yks}</li>
      <li>" . t("Hyllyssä") . ": {$hyllyssa} {$pop_yks}</li>
      <li>" . t("Myytävissä") . ": {$myytavissa} {$pop_yks}</li>";
echo "<li>" . t("Tilattu") . ": {$tilattu} {$pop_yks}</li>
      <li>" . t("Varattu") . ": {$varattu} {$pop_yks}</li>";

if ($paikka !== null) {
  $halytysraja = hae_halytysraja($tuoteno, $paikka);

  echo "<li>", t("Hälytysraja"), ": {$halytysraja} {$pop_yks}</li>";
}

echo "<li>" . t("Keskihinta") . ": {$keskihinta} {$valuutta}</li>";

if ($ostohinta) {
  echo "<li>" . t("Ostohinta") . ": {$ostohinta} {$valuutta}</li>";
}

if ($myyntihinta) {
  echo "<li>" . t("Myyntihinta") . ": {$myyntihinta} {$valuutta}</li>";
}

if ($myymalahinta) {
  echo "<li>" . t("Myymälähinta") . ": {$myymalahinta} {$valuutta}</li>";
}

echo "</ul>";


$lisatiedot = tuotteen_lisatiedot($tuoteno);

if (count($lisatiedot) > 0) {
  echo "<h3>" . t('Tuotteen lisätiedot') . "</h3>";

  echo "<ul>";

  foreach ($lisatiedot as $lisatieto) {
    echo "<li>{$lisatieto["kentta"]} &raquo; {$lisatieto["selite"]}</li>";
  }

  echo "</ul>";
}

function tilattu($tuoteno, $tyyppi) {
  global $kukarow;

  $query = "SELECT sum(tilausrivi.varattu + tilausrivi.jt) AS tilattu
            FROM tilausrivi
            WHERE tilausrivi.yhtio         = '{$kukarow["yhtio"]}'
            AND tilausrivi.tyyppi          = '{$tyyppi}'
            AND tilausrivi.tuoteno         = '{$tuoteno}'
            AND tilausrivi.laskutettuaika  = '0000-00-00'
            AND tilausrivi.var            != 'P'";
  $result = pupe_query($query);
  $tilausrivirow = mysql_fetch_assoc($result);

  $tilausrivirow["tilattu"] = $tilausrivirow["tilattu"] == null ? 0 : $tilausrivirow["tilattu"];

  return $tilausrivirow["tilattu"];
}

function hae_halytysraja($tuoteno, $paikka) {
  list($_hyllyalue, $_hyllynro, $_hyllyvali, $_hyllytaso) = explode(" ", $paikka);

  global $kukarow;

  $query = "SELECT halytysraja
            FROM tuotepaikat
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND tuoteno   = '{$tuoteno}'
            AND hyllyalue = '{$_hyllyalue}'
            AND hyllynro  = '{$_hyllynro}'
            AND hyllyvali = '{$_hyllyvali}'
            AND hyllytaso = '{$_hyllytaso}'";
  $result = pupe_query($query);
  $tuotepaikatrow = mysql_fetch_assoc($result);

  if ($tuotepaikatrow["halytysraja"] != 0) return $tuotepaikatrow["halytysraja"];

  $query = "SELECT halytysraja
            FROM tuote
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);
  $tuoterow = mysql_fetch_assoc($result);

  return $tuoterow["halytysraja"];
}
