<?php

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
      viesti("Ladatut tiliote -aineistot:");

      $_t = unserialize(base64_decode($tiliote_tiedostot));
      tiedostot_table($tiedostot, $_t);

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
      viesti("Ladatut viite -aineistot:");

      $_v = unserialize(base64_decode($viite_tiedostot));
      tiedostot_table($tiedostot, $_v);

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

    $aineistotunnus = false;

    // Jos aineistot pit‰‰ k‰sitell‰
    if ($kasittele_aineistot != "ei") {
      // K‰sitell‰‰n aineisto
      $aineistotunnus = tallenna_tiliote_viite($filenimi);
    }
    else {
      echo "<font class='error'>";
      echo t("Aineistoa ei k‰sitelty.");
      echo "</font>";
      echo "<br/>";
    }

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

  // V‰litet‰‰n tiliote ja viitetiedosto arrayt formissa,
  // jotta saadaan n‰ytetty‰ selkokielist‰ formia downloadin j‰lkeen
  $_t = base64_encode(serialize($tiliote_tiedostot));
  $_v = base64_encode(serialize($viite_tiedostot));

  echo "<input type='hidden' name='tiliote_tiedostot' value='{$_t}'>";
  echo "<input type='hidden' name='viite_tiedostot' value='{$_v}'>";

  echo "<table>";
  echo "<tr>";
  echo "<td class='back' style='vertical-align:top;'>";

  echo "<font class='message'>";
  echo t("Tiliotteet");
  echo "</font>";
  echo "<hr>";

  filelist_table($tiliote_tiedostot, "tiliote");

  echo "</td>";
  echo "<td class='back' style='vertical-align:top;'>";

  echo "<font class='message'>";
  echo t("Viitteet");
  echo "</font>";
  echo "<hr>";

  filelist_table($viite_tiedostot, "viite");

  echo "</td>";
  echo "</tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' value='" . t('Hae valitut aineistot') . "'>";

  // Jos meill‰ on oikkarit pankkiyhteysadminiin, ni voidaan hakea filet ilman, ett‰ k‰sitell‰‰n
  if (tarkista_oikeus("pankkiyhteysadmin.php")) {
    echo "<br><br>";
    echo "<label for='kasittele_aineistot'>" . t("ƒl‰ k‰sittele haettuja aineistoja") . "</label>";
    echo "<input type='checkbox' id='kasittele_aineistot' name='kasittele_aineistot' value='ei'>";
  }
  else {
    echo "<input type='hidden' name='kasittele_aineistot' value='kylla'>";
  }

  echo "</form>";
}
