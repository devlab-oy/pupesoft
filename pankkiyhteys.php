<?php

require("inc/parametrit.inc");
require("inc/pankkiyhteys_functions.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit käyttää pankkiyhteyttä vain salatulla yhteydellä!");
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

// Oikellisuustarkistukset
if ($tee == "laheta" and !formi_kunnossa()) {
  $tee = "";
}

// Tiliotteiden haku
if ($tee == "laheta" and $hae_tiliotteet == "on") {
  $params = array(
    "tiedostotyyppi"      => "TITO",
    "pankkiyhteys_tunnus" => $pankkiyhteys_tunnus,
    "salasana"            => $salasana
  );

  $tiedostot = lataa_kaikki($params);

  if ($tiedostot) {
    viesti("Ladatut tiliotteet:");
    tiedostot_table($tiedostot);
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

  $tiedostot = lataa_kaikki($params);

  if ($tiedostot) {
    viesti("Ladatut viitteet:");
    tiedostot_table($tiedostot);
  }
  else {
    viesti("Ladattavia viitteitä ei ollut saatavilla");
  }
}

// Maksuaineiston oikeellisuustarkistus
if ($tee == "laheta" and $laheta_maksuaineisto == "on") {
  $maksuaineisto = file_get_contents($_FILES["maksuaineisto"]["tmp_name"]);

  if (!$maksuaineisto) {
    virhe("Valitsit maksuaineiston lähetyksen, mutta et valinnut maksuaineistoa");
    $tee = "";
  }
}

// Maksuaineiston lähetys
if ($tee == "laheta" and $laheta_maksuaineisto == "on") {
  $tunnukset = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana);

  $params = array(
    "tunnukset"     => $tunnukset,
    "maksuaineisto" => $maksuaineisto,
  );

  $vastaus = sepa_upload_file($params);

  if ($vastaus) {
    viesti("Maksuaineisto lähetetty, vastaus pankista:");

    echo "<br/>";

    echo "<table>";
    echo "<tbody>";

    foreach ($vastaus[1] as $key => $value) {
      echo "<tr>";
      echo "<td>{$key}</td>";
      echo "<td>{$value}</td>";
      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "<br/><br/>";
  }

  $tee = "";
}

// Käyttöliittymä
formi();
