<?php

// Oikellisuustarkistukset
if ($tee == "hae_aineistot") {
  $viite_references = isset($viite_references) ? $viite_references : array();
  $tiliote_references = isset($tiliote_references) ? $tiliote_references : array();
  $factoring_tiliote_references = isset($factoring_tiliote_references) ? $factoring_tiliote_references : array();
  $factoring_viite_references = isset($factoring_viite_references) ? $factoring_viite_references : array();
  $finvoice_references = isset($finvoice_references) ? $finvoice_references : array();

  if (count($tiliote_references) +
    count($viite_references) +
    count($factoring_tiliote_references) +
    count($factoring_viite_references) +
    count($finvoice_references) == 0) {
    virhe("Et valinnut yht‰‰n aineistoa");
    $tee = "valitse";
  }
}

// Aineistojen haku
if ($tee == "hae_aineistot") {
  // Otetaa salasana + pankkiyhteyden tunnus cookiesta
  $salasana = $_COOKIE[$cookie_secret];
  $pankkiyhteys_tunnus = $_COOKIE[$cookie_tunnus];

  $pankkiyhteysrow = hae_pankkiyhteys($pankkiyhteys_tunnus);

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

  if (count($factoring_tiliote_references) > 0) {
    $params = array(
      "file_type"             => "FINRACSTL",
      "viitteet"              => $factoring_tiliote_references,
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    );

    $tiedostot = sepa_download_files($params);

    if ($tiedostot) {
      viesti("Ladatut factoring-tiliotteet:");

      $_f_t = unserialize(base64_decode($factoring_tiliote_tiedostot));
      tiedostot_table($tiedostot, $_f_t);

      // ker‰t‰‰n t‰h‰n kaikki filet
      $pankkitiedostot = array_merge($pankkitiedostot, $tiedostot);
    }
    else {
      viesti("Ladattavia factoring-tiliotteita ei ollut saatavilla");
    }
  }

  if (count($factoring_viite_references) > 0) {

    if ($pankkiyhteysrow['pankki'] == 'DABAFIHH') {
      $params = array(
        "file_type"             => "VIPN",
        "viitteet"              => $factoring_viite_references,
        "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
        "pankkiyhteys_salasana" => $salasana
      );
    }
    else {
      $params = array(
        "file_type"             => "FINRREFPL",
        "viitteet"              => $factoring_viite_references,
        "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
        "pankkiyhteys_salasana" => $salasana
      );
    }

    $tiedostot = sepa_download_files($params);

    if ($tiedostot) {
      viesti("Ladatut factoring-viiteaineistot:");

      $_f_v = unserialize(base64_decode($factoring_viite_tiedostot));
      tiedostot_table($tiedostot, $_f_v);

      // ker‰t‰‰n t‰h‰n kaikki filet
      $pankkitiedostot = array_merge($pankkitiedostot, $tiedostot);
    }
    else {
      viesti("Ladattavia factoring-viiteaineistoja ei ollut saatavilla");
    }
  }

  if (count($finvoice_references) > 0) {
    $params = array(
      "file_type"             => "HAELASKUT",
      "viitteet"              => $finvoice_references,
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana,
    );

    $tiedostot = sepa_download_files($params);

    if ($tiedostot) {
      viesti("Ladatut laskut:");

      $_l = unserialize(base64_decode($finvoice_tiedostot));
      tiedostot_table($tiedostot, $_l);

      // ker‰t‰‰n t‰h‰n kaikki filet
      $pankkitiedostot = array_merge($pankkitiedostot, $tiedostot);
    }
    else {
      viesti("Ladattavia laskuja ei ollut saatavilla");
    }
  }
}

// K‰sitell‰‰n haetut tiedostot
if ($tee == "hae_aineistot" and count($pankkitiedostot) > 0) {
  $tuetut_pankit     = tuetut_pankit();
  $pankin_lyhyt_nimi = $tuetut_pankit[$pankkiyhteysrow['pankki']]['lyhyt_nimi'];

  echo "<hr><br>";

  // Pankkitiedostot on laskevassa p‰iv‰m‰‰r‰j‰rjestyksess‰
  // Reversataan array, ett‰ ajetaan aineistot aina vanhimmasta uusimpaan
  $pankkitiedostot = array_reverse($pankkitiedostot);

  // K‰sitell‰‰n haetut tiedostot
  $oli_laskuja = false;
  foreach ($pankkitiedostot as $aineisto) {

    // Jos aineisto ei ollut ok, ei teh‰ mit‰‰n
    if ($aineisto['status'] != "OK") {
      continue;
    }

    if ($aineisto['file_type'] == sepa_file_type_check($pankin_lyhyt_nimi, 'HAELASKUT')) {
      $oli_laskuja = true;

      // Kirjotetaan tiedosto levylle
      $filenimi = tempnam($verkkolaskut_in, "finvoice");
      $data     = base64_decode($aineisto['data']);
      $status   = file_put_contents($filenimi, $data);

      if ($status === false) {
        echo "<font class='error'>";
        echo t("Tiedoston kirjoitus ep‰onnistui");
        echo ": {$filenimi}";
        echo "</font>";
        echo "<br/>";
        continue;
      }
    }
    else {
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

  if ($oli_laskuja) {
    require 'verkkolasku-in.php';
  }
}

// Pankkiyhteyden k‰yttˆliittym‰
if ($tee == "valitse") {

  // Otetaa salasana + pankkiyhteyden tunnus cookiesta
  $salasana = $_COOKIE[$cookie_secret];
  $pankkiyhteys_tunnus = $_COOKIE[$cookie_tunnus];

  // Haetaan pankkiyhteysrivi
  $query = "SELECT *
            FROM pankkiyhteys
            WHERE tunnus = '{$pankkiyhteys_tunnus}'
            LIMIT 1";
  $result = pupe_query($query);
  $pankkiyhteysrow = mysql_fetch_assoc($result);

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

  if ($pankkiyhteysrow["hae_factoring"] == 1) {
    // Haetaan factoring-tiliote-lista
    $params = array(
      "file_type"             => "FINRACSTL",
      "status"                => "ALL",
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    );

    $factoring_tiliote_tiedostot = sepa_download_file_list($params);

    if ($pankkiyhteysrow['pankki'] == 'DABAFIHH') {
      // Haetaan factoring-viite-lista
      $params = array(
        "file_type"             => "VIPN",
        "status"                => "ALL",
        "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
        "pankkiyhteys_salasana" => $salasana
      );
    }
    else {
      // Haetaan factoring-viite-lista
      $params = array(
        "file_type"             => "FINRREFPL",
        "status"                => "ALL",
        "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
        "pankkiyhteys_salasana" => $salasana
      );
    }

    $factoring_viite_tiedostot = sepa_download_file_list($params);
  }

  if ($pankkiyhteysrow["hae_laskut"] == 1) {
    // Haetaan finvoice-lista
    $params = array(
      "file_type"             => "HAELASKUT",
      "status"                => "ALL",
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    );

    $finvoice_tiedostot = sepa_download_file_list($params);
  }

  // Piirret‰‰n formi
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='toim' value='$toim'/>";
  echo "<input type='hidden' name='tee' value='hae_aineistot'/>";

  // V‰litet‰‰n tiliote, viitetiedosto ja factoring arrayt formissa,
  // jotta saadaan n‰ytetty‰ selkokielist‰ formia downloadin j‰lkeen
  $_t = base64_encode(serialize($tiliote_tiedostot));
  $_v = base64_encode(serialize($viite_tiedostot));

  if ($pankkiyhteysrow["hae_factoring"] == 1) {
    $_f_t = base64_encode(serialize($factoring_tiliote_tiedostot));
    $_f_v = base64_encode(serialize($factoring_viite_tiedostot));
  }

  if ($pankkiyhteysrow["hae_laskut"] == 1) {
    $_l = base64_encode(serialize($finvoice_tiedostot));
  }

  echo "<input type='hidden' name='tiliote_tiedostot' value='{$_t}'>";
  echo "<input type='hidden' name='viite_tiedostot' value='{$_v}'>";

  if ($pankkiyhteysrow["hae_factoring"] == 1) {
    echo "<input type='hidden' name='factoring_tiliote_tiedostot' value='{$_f_t}'>";
    echo "<input type='hidden' name='factoring_viite_tiedostot' value='{$_f_v}'>";
  }

  if ($pankkiyhteysrow["hae_laskut"] == 1) {
    echo "<input type='hidden' name='finvoice_tiedostot' value='{$_l}'>";
  }

  echo "<input type='submit' value='" . t('Hae valitut aineistot') . "'>";
  echo "<br><br>";

  $saldo = hae_tilin_saldo(array(
      "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
      "pankkiyhteys_salasana" => $salasana
    ));

  if ($saldo) {
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>";
    echo t("Tilin saldo");
    echo "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    echo "<tr>";
    echo "<td>";
    echo "{$saldo['saldo']} {$saldo["valuutta"]}";
    echo "</td>";
    echo "</tr>";
    echo "</tbody>";
    echo "</table>";
  }

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

  if ($pankkiyhteysrow["hae_factoring"] == 1) {
    echo "<tr>";

    echo "<td class='back' style='vertical-align:top;'>";

    echo "<font class='message'>";
    echo t("Factoring-tiliotteet");
    echo "</font>";
    echo "<hr>";

    filelist_table($factoring_tiliote_tiedostot, "factoring_tiliote");

    echo "</td>";
    echo "<td class='back' style='vertical-align:top;'>";

    echo "<font class='message'>";
    echo t("Factoring-viiteaineistot");
    echo "</font>";
    echo "<hr>";

    filelist_table($factoring_viite_tiedostot, "factoring_viite");

    echo "</td>";
    echo "</tr>";
  }

  if ($pankkiyhteysrow["hae_laskut"] == 1) {
    echo "<tr>";
    echo "<td class='back' style='vertical-align:top;'>";

    echo "<font class='message'>";
    echo t("Laskut");
    echo "</font>";
    echo "<hr>";

    filelist_table($finvoice_tiedostot, "finvoice");

    echo "</td>";
    echo "</tr>";
  }

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
