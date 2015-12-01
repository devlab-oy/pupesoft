<?php

$tuoteno      = isset($tuoteno)      ? $tuoteno                    : null;
$varastot     = isset($varastot)     ? explode(",", $varastot)     : 0;
$yksikko      = isset($yksikko)      ? $yksikko                    : null;
$tilattu      = isset($tilattu)      ? $tilattu                    : null;
$varattu      = isset($varattu)      ? $varattu                    : null;
$paikka       = isset($paikka)       ? $paikka                     : null;
$keskihinta   = isset($keskihinta)   ? $keskihinta                 : null;
$valuutta     = isset($valuutta)     ? $valuutta                   : null;
$ostohinta    = isset($ostohinta)    ? $ostohinta                  : null;

list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', $varastot);

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
    echo "<li>{$lisatiedot["kentta"]} &raquo; {$lisatiedot["selite"]}</li>";
  }

  echo "</ul>";
}

