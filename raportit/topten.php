<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (strpos($_SERVER['SCRIPT_NAME'], "topten.php") !== FALSE) {
  require "../inc/parametrit.inc";
  require 'validation/Validation.php';
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {
  require "inc/connect.inc";

  echo "<font class='head'>", t("Top 10 Raportti"), "</font><hr>";

  echo "<form method='post' action='topten.php'>";
  // päivämäärärajaus
  echo "<table>";
  echo "<tr>
    <th>", t("Syötä alkupäivämäärä (pp-kk-vvvv)"), "</th>
    <td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
    <td><input type='text' name='kka' value='{$kka}' size='3'></td>
    <td><input type='text' name='vva' value='{$vva}' size='5'></td>
    </tr>\n
    <tr><th>", t("Syötä loppupäivämäärä (pp-kk-vvvv)"), "</th>
    <td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
    <td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
    <td><input type='text' name='vvl' value='{$vvl}' size='5'></td>
    </tr>\n";
  echo "</table><br>";

  $monivalintalaatikot = array("ASIAKASMYYJA", "ASIAKASRYHMA", "TRY");
  $monivalintalaatikot_normaali = array();

  require "tilauskasittely/monivalintalaatikot.inc";
  echo "<input type='submit' name='aja_raportti' value='", t("Näytä"), "'>";
  echo "</form>";
}

if (isset($aja_raportti)) {
  var_dump($_REQUEST);
}