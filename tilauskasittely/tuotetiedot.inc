<?php

$tuoteno      = isset($tuoteno)      ? $tuoteno                    : null;
$varasto      = isset($varasto)      ? $varasto                    : null;
$yksikko      = isset($yksikko)      ? $yksikko                    : null;
$tilattu      = isset($tilattu)      ? $tilattu                    : null;
$varattu      = isset($varattu)      ? $varattu                    : null;
$paikka       = isset($paikka)       ? $paikka                     : null;
$keskihinta   = isset($keskihinta)   ? $keskihinta                 : null;
$valuutta     = isset($valuutta)     ? $valuutta                   : null;
$ostohinta    = isset($ostohinta)    ? $ostohinta                  : null;
$vanhatunnus  = isset($vanhatunnus)  ? $vanhatunnus                : 0;

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

echo "<ul>";
echo "<li>" . t("Saldo") . ": {$saldo} {$pop_yks}</li>
      <li>" . t("Hyllyssä") . ": {$hyllyssa} {$pop_yks}</li>
      <li>" . t("Myytävissä") . ": {$myytavissa} {$pop_yks}</li>";
echo "<li>" . t("Tilattu") . ": {$tilattu} {$pop_yks}</li>
      <li>" . t("Varattu") . ": {$varattu} {$pop_yks}</li>";

if ($paikka !== null) {

  list($_hyllyalue, $_hyllynro, $_hyllyvali, $_hyllytaso) = explode(" ", $paikka);

  $query = "SELECT halytysraja
            FROM tuotepaikat
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND tuoteno   = '{$tuoteno}'
            AND hyllyalue = '{$_hyllyalue}'
            AND hyllynro  = '{$_hyllynro}'
            AND hyllyvali = '{$_hyllyvali}'
            AND hyllytaso = '{$_hyllytaso}'";
  $halyraja_chk_res = pupe_query($query);
  $halyraja_chk_row = mysql_fetch_assoc($halyraja_chk_res);

  echo "<li>", t("Hälytysraja"), ": {$halyraja_chk_row['halytysraja']} {$pop_yks}</li>";
}

echo "<li>" . t("Keskihinta") . ": {$keskihinta} {$valuutta}</li>";

if ($ostohinta) {
  echo "<li>" . t("Ostohinta") . ": {$ostohinta} {$valuutta}</li>";
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
