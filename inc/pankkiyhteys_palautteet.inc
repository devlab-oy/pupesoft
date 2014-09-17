<?php

// Oikellisuustarkistukset
if ($tee == "hae_aineistot") {
  $palaute_references = isset($palaute_references) ? $palaute_references : array();

  if (count($palaute_references) == 0) {
    virhe("Et valinnut yht‰‰n palautetta");
    $tee = "valitse";
  }
}

// Aineistojen haku
if ($tee == "hae_aineistot") {
  // Otetaa salasana + pankkiyhteyden tunnus cookiesta
  $salasana = $_COOKIE[$cookie_secret];
  $pankkiyhteys_tunnus = $_COOKIE[$cookie_tunnus];

  echo "<br>";

  if (count($palaute_references) > 0) {
    $params = array(
      "file_type"             => "C2BN",
      "viitteet"              => $palaute_references,
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    );

    $tiedostot = sepa_download_files($params);

    // Palutetaan array, josta saadaan tilinumerot ja p‰iv‰m‰‰r‰t
    $palaute_tiedostot = unserialize(base64_decode($palaute_tiedostot));

    if ($tiedostot) {
      viesti("Ladatut palaute -aineistot:");
      echo "<hr>";

      echo "<table>";
      echo "<tr>";

      echo "<th>";
      echo t("Tilinumero");
      echo "</th>";

      echo "<th>";
      echo t("P‰iv‰m‰‰r‰");
      echo "</th>";

      echo "<th>";
      echo t("Status");
      echo "</th>";

      echo "<th>";
      echo t("Selite");
      echo "</th>";

      echo "</tr>";

      // K‰sitell‰‰n haetut tiedostot
      foreach ($tiedostot as $aineisto) {
        // Haetaan tiedoston tiedot filelistasta, ett‰ saadaan p‰iv‰m‰‰r‰ ja tilinumero
        $tiedot = search_array_key_for_value_recursive($palaute_tiedostot, "fileReference", $aineisto["viite"]);

        // Kaikki pankit ei osaa n‰ytt‰‰ tilinumeroa, n‰ytet‰‰n sillo filereference
        $tili = $tiedot[0]["serviceId"] != "" ? $tiedot[0]["serviceId"] : $tiedot[0]["fileReference"];
        $date = date("d.m.Y h:i:s", strtotime($tiedot[0]["fileTimestamp"]));

        // Jos aineisto ei ollut ok
        if ($aineisto['status'] != "OK") {
          $palaute = array(
            "status" => $aineisto['status'],
            "selite" => t("Aineiston haku pankista ep‰onnistui!"),
          );
        }
        else {
          $palaute = kasittele_pain_palaute($aineisto['data']);
        }

        echo "<tr class='aktiivi'>";
        echo "<td>{$tili}</td>";
        echo "<td>{$date}</td>";
        echo "<td>{$palaute["status"]}</td>";
        echo "<td>{$palaute["selite"]}</td>";
        echo "</tr>";
      }

      echo "</table>";
    }
    else {
      viesti("Ladattavia palautteita ei ollut saatavilla");
    }
  }
}

// Valitaan palaute k‰yttˆliittym‰
if ($tee == "valitse") {
  // Otetaa salasana + pankkiyhteyden tunnus cookiesta
  $salasana = $_COOKIE[$cookie_secret];
  $pankkiyhteys_tunnus = $_COOKIE[$cookie_tunnus];

  // Haetaan palaute-lista
  $params = array(
    "file_type"             => "C2BN",
    "status"                => "ALL",
    "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
    "pankkiyhteys_salasana" => $salasana
  );

  $palaute_tiedostot = sepa_download_file_list($params);

  // Piirret‰‰n formi
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='toim' value='$toim'/>";
  echo "<input type='hidden' name='tee' value='hae_aineistot'/>";

  // V‰litet‰‰n tiliote ja viitetiedosto arrayt formissa,
  // jotta saadaan n‰ytetty‰ selkokielist‰ formia downloadin j‰lkeen
  $_p = base64_encode(serialize($palaute_tiedostot));

  echo "<input type='hidden' name='palaute_tiedostot' value='{$_p}'>";

  echo "<input type='submit' value='" . t('Hae valitut palautteet') . "'>";
  echo "<br><br>";

  echo "<table>";
  echo "<tr>";
  echo "<td class='back' style='vertical-align:top;'>";

  echo "<font class='message'>";
  echo t("Maksuaineistojen palautteet");
  echo "</font>";
  echo "<hr>";

  filelist_table($palaute_tiedostot, "palaute");

  echo "</td>";
  echo "</tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' value='" . t('Hae valitut palautteet') . "'>";

  echo "</form>";
}
