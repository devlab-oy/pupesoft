<?php

require("inc/parametrit.inc");
require("inc/pankkiyhteys_functions.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit k‰ytt‰‰ pankkiyhteytt‰ vain salatulla yhteydell‰!");
  echo "</font>";
  exit;
}

if (!isset($sepa_pankkiyhteys_token)) {
  echo "<font class='error'>";
  echo t("SEPA-palvelua ei ole aktivoitu.");
  echo "</font>";
  exit;
}

$tee = empty($tee) ? '' : $tee;
$hae_tiliotteet = empty($hae_tiliotteet) ? '' : $hae_tiliotteet;
$hae_viitteet = empty($hae_viitteet) ? '' : $hae_viitteet;

$pankkitiedostot = array();

// Oikellisuustarkistukset
if ($tee == "laheta") {
  $komennot_count = 0;
  $virheet_count = 0;

  if ($hae_tiliotteet == "on") {
    $komennot_count++;
  }

  if ($hae_viitteet == "on") {
    $komennot_count++;
  }

  if ($komennot_count == 0) {
    virhe("Et valinnut yht‰‰n komentoa!");
    $virheet_count++;
  }

  if (empty($salasana)) {
    virhe("Salasana t‰ytyy antaa!");
    $virheet_count++;
  }
  elseif (!pankkiyhteys_salasana_kunnossa($pankkiyhteys_tunnus, $salasana)) {
    virhe("Antamasi salasana on v‰‰r‰!");
    $virheet_count++;
  }

  if ($virheet_count > 0) {
    echo "<br>";
    $tee = "";
  }
}

// Tiliotteiden haku
if ($tee == "laheta" and $hae_tiliotteet == "on") {
  $params = array(
    "tiedostotyyppi"      => "TITO",
    "pankkiyhteys_tunnus" => $pankkiyhteys_tunnus,
    "salasana"            => $salasana
  );

  $tiedostot = sepa_lataa_kaikki_uudet_tiedostot($params);

  if ($tiedostot) {
    viesti("Ladatut tiliotteet:");
    tiedostot_table($tiedostot);

    // ker‰t‰‰n t‰h‰n kaikki filet
    $pankkitiedostot = array_merge($pankkitiedostot, $tiedostot);
  }
  else {
    viesti("Ladattavia tiliotteita ei ollut saatavilla");
  }
}

// Viitteiden haku
if ($tee == "laheta" and $hae_viitteet == "on") {
  $params = array(
    "tiedostotyyppi"      => "KTL",
    "pankkiyhteys_tunnus" => $pankkiyhteys_tunnus,
    "salasana"            => $salasana
  );

  $tiedostot = sepa_lataa_kaikki_uudet_tiedostot($params);

  if ($tiedostot) {
    viesti("Ladatut viitteet:");
    tiedostot_table($tiedostot);

    // ker‰t‰‰n t‰h‰n kaikki filet
    $pankkitiedostot = array_merge($pankkitiedostot, $tiedostot);
  }
  else {
    viesti("Ladattavia viitteit‰ ei ollut saatavilla");
  }
}

// K‰sitell‰‰n haetut tiedostot
if ($tee == "laheta" and count($pankkitiedostot) > 0) {
  echo "<hr><br>";

  // K‰sitell‰‰n haetut tiedostot
  foreach ($pankkitiedostot as $aineisto) {
    // Jos aineisto ei ollut ok, ei teh‰ mit‰‰n
    if ($aineisto['status'] != "OK") {
      continue;
    }

    // Kirjotetaan tiedosto levylle
    $filenimi = tempnam("{$pupe_root_polku}/datain", "pankkiaineisto");
    $data = base64_decode($aineisto['data']);
    $status = file_put_contents($filenimi, $data);

    if ($status === false) {
      echo "<font class='error'>";
      echo t("Tiedoston kirjoitus ep‰onnistui");
      echo ": {$filenimi}";
      echo "</font>";
      echo "<br/>";
      continue;
    }

    // K‰sitell‰‰n aineisto
    $aineistotunnus = tallenna_tiliote_viite($filenimi);

    if ($aineistotunnus !== false) {
      kasittele_tiliote_viite($aineistotunnus);
      unlink($filenimi);
    }
    else {
      echo "<font class='error'>";
      echo t("Aineisto lˆytyy hakemistosta");
      echo ": {$filenimi}";
      echo "</font>";
      echo "<br/>";
    }

    echo "<br><hr><br>";
  }
}

// K‰yttˆliittym‰
$kaytossa_olevat_pankkiyhteydet = hae_pankkiyhteydet();

if ($kaytossa_olevat_pankkiyhteydet) {

  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tee' value='laheta'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<th>";
  echo t("Valitse pankki");
  echo "</th>";
  echo "<td>";
  echo "<select name='pankkiyhteys_tunnus'>";

  foreach ($kaytossa_olevat_pankkiyhteydet as $pankkiyhteys) {
    $selected = $pankkiyhteys_tunnus == $pankkiyhteys["tunnus"] ? " selected" : "";

    echo "<option value='{$pankkiyhteys["tunnus"]}'{$selected}>";
    echo "{$pankkiyhteys["pankin_nimi"]}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>";
  echo t("Valitse toiminnot");
  echo "</th>";
  echo "<td>";

  $checked = $hae_tiliotteet == "on" ? " checked" : "";

  echo "<input type='checkbox' name='hae_tiliotteet' id='hae_tiliotteet'{$checked}/>";
  echo "<label for='hae_tiliotteet'>" . t("Hae uudet tiliotteet") . "</label>";
  echo "<br>";

  $checked = $hae_viitteet == "on" ? " checked" : "";

  echo "<input type='checkbox' name='hae_viitteet' id='hae_viitteet'{$checked}/>";
  echo "<label for='hae_viitteet'>" . t("Hae uudet viitteet") . "</label>";
  echo "<br>";

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='salasana'>" . t("Salasana") . "</label></th>";
  echo "<td><input type='password' name='salasana' id='salasana'/></td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' value='" . t('L‰het‰') . "'>";

  echo "</form>";
}
else {
  viesti("Yht‰‰n pankkiyhteytt‰ ei ole viel‰ luotu.");
}

require 'inc/footer.inc';
