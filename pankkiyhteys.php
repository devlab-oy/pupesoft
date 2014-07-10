<?php

require("inc/parametrit.inc");

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

if ($tee == "laheta" and !formi_kunnossa()) {
  $tee = "";
}

if ($tee == "laheta" and $hae_tiliotteet == "on") {
  if (!lataa_kaikki("TITO")) {
    $tee = "";
  }
}

if ($tee == "laheta" and $hae_viitteet == "on") {
  if (!lataa_kaikki("KTL")) {
    $tee = "";
  }
}

if ($tee == "laheta" and $laheta_maksuaineisto == "on") {
  $maksuaineisto = file_get_contents($_FILES["maksuaineisto"]["tmp_name"]);

  if (!$maksuaineisto) {
    virhe("Valitsit maksuaineiston lähetyksen, mutta et valinnut maksuaineistoa");
    $tee = "";
  }
}

if ($tee == "laheta" and $laheta_maksuaineisto == "on") {
  $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $salasana);
  $vastaus = laheta_maksuaineisto($tunnukset, $maksuaineisto);

  if ($vastaus) {
    ok("Maksuaineisto lähetetty, vastaus pankista:");
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

if ($tee == "") {
  formi();
}

/**
 * @param $tili
 *
 * @return array
 */
function hae_avain_sertifikaatti_ja_customer_id($tili) {
  global $kukarow;

  $query = "SELECT private_key, certificate, sepa_customer_id
            FROM yriti
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus = {$tili}";
  $result = pupe_query($query);

  $rivi = mysql_fetch_assoc($result);

  return $rivi;
}

/**
 * @param $salattu_data
 * @param $salasana
 *
 * @return string
 */
function pura_salaus($salattu_data, $salasana) {
  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = substr($salattu_data_binaari, 0, $iv_size);

  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size);

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}

/**
 * @param $tiedostotyyppi
 * @param $tunnukset
 *
 * @return array
 */
function download_file_list($tiedostotyyppi, $tunnukset) {
  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type"   => $tiedostotyyppi,
      "target_id"   => "11111111A1"
    ),
    "url"     => "https://sepa.devlab.fi/api/nordea/download_file_list",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_url}"
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  $tiedostot = $vastaus[1]["files"];
  $viitteet = array();

  foreach ($tiedostot as $tiedosto) {
    array_push($viitteet, $tiedosto["fileReference"]);
  }

  return $viitteet;
}

/**
 * @param $viitteet
 * @param $tiedostotyyppi
 * @param $tunnukset
 *
 * @return bool
 */
function download_file($viitteet, $tiedostotyyppi, $tunnukset) {
  $onnistuneet = 0;

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "cert"           => base64_encode($tunnukset["sertifikaatti"]),
        "private_key"    => base64_encode($tunnukset["avain"]),
        "customer_id"    => $tunnukset["customer_id"],
        "file_type"      => $tiedostotyyppi,
        "target_id"      => "11111111A1",
        "file_reference" => $viite
      ),
      "url"     => "https://sepa.devlab.fi/api/nordea/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token={$sepa_pankkiyhteys_url}"
      )
    );

    $vastaus = pupesoft_rest($parameters);

    if ($vastaus[0] == 200) {
      $onnistuneet++;
    }

    if (!is_dir("/tmp/{$tiedostotyyppi}")) {
      mkdir("/tmp/{$tiedostotyyppi}");
    }

    file_put_contents("/tmp/{$tiedostotyyppi}/{$viite}", base64_decode($vastaus[1]["data"]));
  }

  if (count($viitteet) == $onnistuneet) {
    return true;
  }

  return false;
}

function salasana_formi() {
  $komento = $_POST["tee"];

  if ($komento == "laheta_maksuaineisto") {
    $enctype = "enctype='multipart/form-data'";
  }
  else {
    $enctype = "";
  }

  echo "<form method='post' action='pankkiyhteys.php' {$enctype}>";
  echo "<input type='hidden' name='tee' value='{$komento}'/>";
  echo "<input type='hidden' name='tili' value='{$_POST["tili"]}'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td>";
  echo "<label for='salasana'>" . t("Salasana, jolla salasit tunnukset") . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<input type='password' name='salasana' id='salasana'/>";
  echo "</td>";
  echo "</tr>";

  if ($komento == "laheta_maksuaineisto") {
    echo "<tr>";
    echo "<td>";
    echo "<label for='maksuainesto'>" . t("Maksuaineisto") . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<input type='file' name='maksuaineisto' id='maksuaineisto'/>";
    echo "</td>";
    echo "</tr>";
  }
  echo "<tr>";
  echo "<td class='back'>";
  echo "<input type='submit' value='" . t("Hae") . "'/>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}

/**
 * @param $tili
 * @param $salasana
 *
 * @return array
 */
function hae_tunnukset_ja_pura_salaus($tili, $salasana) {
  $haetut_tunnukset = hae_avain_sertifikaatti_ja_customer_id($tili);

  $avain = pura_salaus($haetut_tunnukset["private_key"], $salasana);

  if (!openssl_pkey_get_private($avain)) {
    virhe("Annoit väärän salasanan");

    return false;
  }

  $sertifikaatti = pura_salaus($haetut_tunnukset["certificate"], $salasana);
  $customer_id = $haetut_tunnukset["sepa_customer_id"];

  return array(
    "avain"         => $avain,
    "sertifikaatti" => $sertifikaatti,
    "customer_id"   => $customer_id
  );
}

/**
 * @param $tunnukset
 * @param $maksuaineisto
 *
 * @return array
 */
function laheta_maksuaineisto($tunnukset, $maksuaineisto) {
  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type"   => "NDCORPAYS",
      "target_id"   => "11111111A1",
      "content"     => $maksuaineisto
    ),
    "url"     => "https://sepa.devlab.fi/api/nordea/upload_file",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_url}"
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  return $vastaus;
}

function maksuaineisto_kunnossa() {
  if (isset($_FILES["maksuaineisto"]) and !$_FILES["maksuaineisto"]["tmp_name"]) {
    virhe("Maksuaineisto puuttuu");
    return false;
  }

  return true;
}

/**
 * @param $vastaus
 *
 * @return bool
 */
function vastaus_kunnossa($vastaus) {
  switch ($vastaus[0]) {
    case 200:
      return true;
    case 500:
      virhe("Pankki ei vastaa kyselyyn, yritä myöhemmin uudestaan");
      return false;
    case 503:
      virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yritä myöhemmin uudestaan");
      return false;
    case 0:
      virhe("Sepa-palvelimeen ei jostain syystä saada yhteyttä, yritä myöhemmin uudestaan");
      return false;
  }

  return false;
}

function virhe($viesti) {
  echo "<font class='error'>{$viesti}</font><br/>";
}

function ok($viesti) {
  echo "<font class='ok'>{$viesti}</font><br/>";
}

function viesti($viesti) {
  echo "<font class='message'>{$viesti}</font><br/>";
}


/**
 * @param $tiedostotyyppi
 *
 * @return bool
 */
function lataa_kaikki($tiedostotyyppi) {
  $tunnukset = hae_tunnukset_ja_pura_salaus($_POST["tili"], $_POST["salasana"]);

  if (!$tunnukset) {
    return false;
  }

  $viitteet = download_file_list($tiedostotyyppi, $tunnukset);

  if ($viitteet and download_file($viitteet, $tiedostotyyppi, $tunnukset)) {
    if ($tiedostotyyppi == "TITO") {
      ok("Tiliotteet ladattu");
    }
    elseif ($tiedostotyyppi == "KTL") {
      ok("Viitteet ladattu");
    }
  }

  return true;
}

/**
 * @return array
 */
function hae_kaytossa_olevat_tilit() {
  global $kukarow;

  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND sepa_customer_id != ''";
  $result = pupe_query($query);

  $kaytossa_olevat_tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($kaytossa_olevat_tilit, $rivi);
  }

  return $kaytossa_olevat_tilit;
}

function formi() {
  $kaytossa_olevat_tilit = hae_kaytossa_olevat_tilit();

  if ($kaytossa_olevat_tilit) {

    echo "<form method='post' action='pankkiyhteys.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='laheta'/>";
    echo "<table>";
    echo "<tbody>";

    echo "<tr>";
    echo "<td>Valitse tili</td>";
    echo "<td>";
    echo "<select name='tili'>";

    foreach ($kaytossa_olevat_tilit as $tili) {
      echo "<option value='{$tili["tunnus"]}'>{$tili["nimi"]}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label>";
    echo t("Mitä haluat tehdä?");
    echo "</label></td>";
    echo "<td>";
    echo "<label for='hae_tiliotteet'>" . t("Hae tiliotteet") . "</label>";
    echo "<input type='checkbox' name='hae_tiliotteet' id='hae_tiliotteet'/>";
    echo "<label for='hae_viitteet'>" . t("Hae viitteet") . "</label>";
    echo "<input type='checkbox' name='hae_viitteet' id='hae_viitteet'/>";
    echo "<label for='laheta_maksuaineisto'>" . t("Lähetä maksuaineisto") . "</label>";
    echo "<input type='checkbox' name='laheta_maksuaineisto' id='laheta_maksuaineisto'/>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label for='maksuaineisto'>" . t("Maksuaineisto") . "</label></td>";
    echo "<td><input type='file' name='maksuaineisto' id='maksuaineisto'/></td>";
    echo "<td class='back'>" . t("Täytä vain, jos aiot lähettää maksuaineiston") . "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label for='salasana'>" . t("Salasana") . "</label></td>";
    echo "<td><input type='password' name='salasana' id='salasana'/></td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";
    echo "<input type='submit' value='" . t('Lähetä') . "'>";

    echo "</form>";
  }
  else {
    viesti("Yhtään pankkiyhteyttä ei ole vielä luotu.");
  }
}

function formi_kunnossa() {
  $komennot_count = 0;
  $virheet_count = 0;

  if ($_POST["hae_tiliotteet"] == "on") {
    $komennot_count++;
  }

  elseif ($_POST["hae_viitteet"] == "on") {
    $komennot_count++;
  }

  elseif ($_POST["laheta_maksuaineisto"] == "on") {
    $komennot_count++;
  }

  if ($komennot_count == 0) {
    virhe("Et valinnut yhtään komentoa");
    $virheet_count++;
  }

  if (empty($_POST["salasana"])) {
    virhe("Salasana täytyy antaa");
    $virheet_count++;
  }

  if ($virheet_count == 0) {
    return true;
  }

  return false;
}
