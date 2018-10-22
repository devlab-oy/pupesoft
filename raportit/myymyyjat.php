<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require '../inc/parametrit.inc';
require 'inc/myymyyjat.inc';

if ($toim == "TARKKA") {
  echo "<script src='myymyyjat.js'></script>";
}

echo "<font class='head'>".t("Myyjien myynnit").":</font><hr>";

// Käyttöliittymä
if ($toim == "TARKKA") {
  $muuttujat = muistista("myymyyjat", "oletus");

  if (!isset($kuluprosentti) and $muuttujat["kuluprosentti"]) {
    $kuluprosentti = $muuttujat["kuluprosentti"];
  }

  if (!isset($mul_laskumyyja) and $muuttujat["mul_laskumyyja"]) {
    $mul_laskumyyja = $muuttujat["mul_laskumyyja"];
  }

  if (!isset($mul_osasto) and $muuttujat["mul_osasto"]) {
    $mul_osasto = $muuttujat["mul_osasto"];
  }

  $kvartaali = paata_kvartaali();
  $kvartaali_alku = date_parse($kvartaali["start_date"]);
  $kvartaali_loppu = date_parse($kvartaali["end_date"]);

  if (!isset($alkukk)) {
    $alkukk = $kvartaali_alku["month"];
  }
  if (!isset($alkuvv)) {
    $alkuvv = $kvartaali_alku["year"];
  }

  if (!isset($loppukk)) {
    $loppukk = $kvartaali_loppu["month"];
  }
  if (!isset($loppuvv)) {
    $loppuvv = $kvartaali_loppu["year"];
  }
}
else {
  if (!isset($alkukk)) $alkukk = date("m", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
  if (!isset($alkuvv)) $alkuvv = date("Y", mktime(0, 0, 0, date("m"), 1, date("Y")-1));
  if (!isset($loppukk)) $loppukk = date("m", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
  if (!isset($loppuvv)) $loppuvv = date("Y", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
}
$tee = isset($tee) ? trim($tee) : "";

if (checkdate($alkukk, 1, $alkuvv) and checkdate($loppukk, 1, $loppuvv)) {
  // MySQL muodossa
  $pvmalku = date("Y-m-d", mktime(0, 0, 0, $alkukk, 1, $alkuvv));
  $pvmloppu = date("Y-m-d", mktime(0, 0, 0, $loppukk+1, 0, $loppuvv));
}
else {
  echo "<font class='error'>".t("Päivämäärävirhe")."!</font>";
  $tee = "";
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' id='myymyyjatTee' value='kaikki'>";

if ($toim == "TARKKA") {
  $classes = 'hidden';
  $noautosubmit = true;
  $monivalintalaatikot = array('laskumyyja', 'osasto');
  $osastojen_nimet = hae_osastojen_nimet();
}
else {
  $classes = '';
  $lisa = "";
}

echo "<div id='valinnat' class='{$classes}'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Anna alkukausi (kk-vuosi)")."</th>";
echo "  <td>
    <input type='text' name='alkukk' value='$alkukk' size='2'>-
    <input type='text' name='alkuvv' value='$alkuvv' size='5'>
    </td>";
echo "</tr>";

echo "<th>".t("Anna loppukausi (kk-vuosi)")."</th>";
echo "  <td>
    <input type='text' name='loppukk' value='$loppukk' size='2'>-
    <input type='text' name='loppuvv' value='$loppuvv' size='5'>
    </td>";
echo "</tr>";

if ($toim == "TARKKA") {
  foreach ($osastojen_nimet as $osasto => $osaston_nimi) {
    echo "<tr>";
    echo "<th>";
    echo "<label for='kuluprosentti_{$osasto}'>" . t("Kuluprosentti osastolle") .
      " {$osaston_nimi}</label>";
    echo "</th>";
    echo "<td>";
    echo "<input type='number' id='kuluprosentti_{$osasto}' name='kuluprosentti[{$osasto}]' min='0'
                 max='100' value='{$kuluprosentti[$osasto]}'>";
    echo "</td>";
    echo "</tr>";
  }
  $konserni = '';
}
else {
  if ($yhtiorow["konserni"] != "") {
    $chk = "";
    if ($konserni != "") $chk = "CHECKED";
  
    echo "<tr><th>".t("Näytä kaikki konserniyhtiöt")."</th><td colspan='3'><input type='checkbox' name='konserni' $chk></td></tr>";
  }
}

echo "</table>";

if ($toim == "TARKKA") {
  require_once "tilauskasittely/monivalintalaatikot.inc";

  echo "<input type='button' id='tallennaNappi' value='" . t("Tallenna haku oletukseksi") . "'>";
}

echo "</div>";

echo "<table>";
echo "<tbody>";
echo "<tr>";
echo "<td class='back'><input type='submit' value='" . t("Aja raportti") . "'>";

if ($toim == "TARKKA") {
  echo "<input id='naytaValinnat' type='button' value='" . t("Näytä valinnat") . "'>";
}

echo "</td>";
echo "</tr>";
echo "</tbody>";
echo "</table>";

echo "<br>";

if ($tee == "tallenna_haku") {
  $muistettavat = array(
    "kuluprosentti"  => $kuluprosentti,
    "mul_laskumyyja" => $mul_laskumyyja,
    "mul_osasto"     => $mul_osasto
  );

  muistiin("myymyyjat", "oletus", $muistettavat);
}

if ($tee != '') {
  piirra_myyjien_myynnit($lisa, $pvmalku, $pvmloppu, $toim, $kuluprosentti, $osastojen_nimet, $konserni);
}

require "inc/footer.inc";
