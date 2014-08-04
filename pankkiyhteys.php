<?php

require "inc/parametrit.inc";
require "inc/pankkiyhteys_functions.inc";

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

// Varmistetaan, ett‰ sepa pankkiyhteys on kunnossa. Funkio kuolee, jos ei ole.
sepa_pankkiyhteys_kunnossa();

toggle_all("viite_toggler", "viite_boxes");
toggle_all("tiliote_toggler", "tiliote_boxes");

$tee = empty($tee) ? '' : $tee;
$hae_tiliotteet = empty($hae_tiliotteet) ? '' : $hae_tiliotteet;
$hae_viitteet = empty($hae_viitteet) ? '' : $hae_viitteet;

$pankkitiedostot = array();
$virheet_count = 0;
$cookie_secret = "pupesoft_pankkiyhteys_secret";
$cookie_tunnus = "pupesoft_pankkiyhteys_tunnus";

// Jos meill‰ on viel‰ cookie voimassa, menn‰‰n suoraan valintaan
if ($tee == "" and isset($_COOKIE[$cookie_secret])) {
  $tee = "valitse";
}

// Jos meill‰ on cookie, tehd‰‰n uloskirjautumisnappi
if (isset($_COOKIE[$cookie_secret])) {
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tee' value='kirjaudu_ulos'/>";
  echo "<input type='submit' value='" . t('Kirjaudu ulos') . "'>";
  echo "</form>";
  echo "<br>";
}

// Kirjaudutaan pankkiin
if ($tee == "kirjaudu") {
  if (empty($salasana)) {
    virhe("Salasana t‰ytyy antaa!");
    $virheet_count++;
  }
  elseif (!hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana)) {
    virhe("Antamasi salasana on v‰‰r‰!");
    $virheet_count++;
  }

  if ($virheet_count > 0) {
    $tee = "kirjaudu_ulos";
  }
  else {
    // Setataan SECURE cookiet, HTTP only
    setcookie($cookie_secret, $salasana, time() + 300, '/', false, true, true);
    setcookie($cookie_tunnus, $pankkiyhteys_tunnus, time() + 300, '/', false, true, true);

    // Laitetaan samantien myˆs globaaliin
    $_COOKIE[$cookie_secret] = $salasana;
    $_COOKIE[$cookie_tunnus] = $pankkiyhteys_tunnus;

    $tee = "valitse";
  }
}

// Kirjaudutaan ulos pankista
if ($tee == "kirjaudu_ulos") {
  // Unsetataan cookiet
  setcookie($cookie_secret, "", time() - 300, "/", false, true, true);
  setcookie($cookie_tunnus, "", time() - 300, "/", false, true, true);

  // Poistetaan myˆs globaalista
  unset($_COOKIE[$cookie_secret]);
  unset($_COOKIE[$cookie_tunnus]);

  $tee = "";
}

// Oikellisuustarkistukset
if ($tee == "hae_aineistot") {
  $viite_references = isset($viite_references) ? $viite_references : array();
  $tiliote_references = isset($tiliote_references) ? $tiliote_references : array();

  if (count($tiliote_references) + count($viite_references) == 0) {
    virhe("Et valinnut yht‰‰n aineistoa");
    $tee = "valitse";
  }
}

// Aineistojen haku
if ($tee == "hae_aineistot") {
  // Otetaa salasana + pankkiyhteyden tunnus cookiesta
  $salasana = $_COOKIE[$cookie_secret];
  $pankkiyhteys_tunnus = $_COOKIE[$cookie_tunnus];

  echo "<br>";

  if (count($tiliote_references) > 0) {
    $params = array(
      "file_type"             => "TITO",
      "viitteet"              => $tiliote_references,
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    );

    $tiedostot = sepa_download_files($params);

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

  if (count($viite_references) > 0) {
    $params = array(
      "file_type"             => "KTL",
      "viitteet"              => $viite_references,
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    );

    $tiedostot = sepa_download_files($params);

    if ($tiedostot) {
      viesti("Ladatut tiliotteet:");
      tiedostot_table($tiedostot);

      // ker‰t‰‰n t‰h‰n kaikki filet
      $pankkitiedostot = array_merge($pankkitiedostot, $tiedostot);
    }
    else {
      viesti("Ladattavia viitteit‰ ei ollut saatavilla");
    }
  }
}

// K‰sitell‰‰n haetut tiedostot
if ($tee == "hae_aineistot" and count($pankkitiedostot) > 0) {
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

// Pankkiyhteyden k‰yttˆliittym‰
if ($tee == "valitse") {

  // Otetaa salasana + pankkiyhteyden tunnus cookiesta
  $salasana = $_COOKIE[$cookie_secret];
  $pankkiyhteys_tunnus = $_COOKIE[$cookie_tunnus];

  // Haetaan tiliote-lista
  $params = array(
    "file_type"             => "TITO",
    "status"                => "ALL",
    "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
    "pankkiyhteys_salasana" => $salasana
  );

  $tiliote_tiedostot = sepa_download_file_list($params);

  // Haetaan viite-lista
  $params = array(
    "file_type"             => "KTL",
    "status"                => "ALL",
    "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
    "pankkiyhteys_salasana" => $salasana
  );

  $viite_tiedostot = sepa_download_file_list($params);

  // Piirret‰‰n formi
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tee' value='hae_aineistot'/>";

  echo "<br>";
  echo "<font class='message'>";
  echo t("Tiliotteet");
  echo "</font>";
  echo "<hr>";

  filelist_table($tiliote_tiedostot, "tiliote");

  echo "<br>";
  echo "<font class='message'>";
  echo t("Viitteet");
  echo "</font>";
  echo "<hr>";

  filelist_table($viite_tiedostot, "viite");

  echo "<br>";
  echo "<input type='submit' value='" . t('Hae valitut aineistot') . "'>";

  echo "</form>";
}

// Sis‰‰nkirjautumisen k‰yttˆliittym‰
if ($tee == "") {
  $kaytossa_olevat_pankkiyhteydet = hae_pankkiyhteydet();

  if ($kaytossa_olevat_pankkiyhteydet) {

    echo "<form method='post' action='pankkiyhteys.php'>";
    echo "<input type='hidden' name='tee' value='kirjaudu'/>";
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
    echo "<th><label for='salasana'>" . t("Salasana") . "</label></th>";
    echo "<td><input type='password' name='salasana' id='salasana'/></td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";

    echo "<br>";
    echo "<input type='submit' value='" . t('Kirjaudu') . "'>";

    echo "</form>";
  }
  else {
    viesti("Yht‰‰n pankkiyhteytt‰ ei ole viel‰ luotu.");
  }
}

require 'inc/footer.inc';
