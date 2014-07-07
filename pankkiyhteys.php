<?php

const SEPA_OSOITE = "https://sepa.devlab.fi/api/";
const ACCESS_TOKEN = "Bexvxb10H1XBT36x42Lv8jEEKnA6";

if (!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS']) {
  $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

  header('Location: ' . $url);

  exit;
}

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (isset($uusi_pankkiyhteys)) {
  $tilit = hae_uudet_tilit($kukarow["yhtio"]);

  echo "<form action='pankkiyhteys.php' method='post'>";
  echo "<input type='hidden' name='tee' value='generoi_tunnukset'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='tili'>" . t("Tili, jolle pankkiyhteys luodaan") . "</label></td>";
  echo "<td>";
  echo "<select name='tili' id='tili'>";

  foreach ($tilit as $tili) {
    echo "<option>{$tili["nimi"]}</option>";
  }


  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='pin'>" . t("Pankilta saatu PIN-koodi") . "</label></td>";
  echo "<td><input type='text' name='pin' id='pin'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'><input type='submit' value='" . t("Luo pankkiyhteys") . "'/></td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}
elseif (isset($tee) and $tee == "generoi_tunnukset") {

  $key_config = array(
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA
  );

  $csr_info = array(
    "countryName" => $yhtiorow["maa"],
    "localityName" => $yhtiorow["kotipaikka"],
    "organizationName" => $yhtiorow["nimi"],
    "commonName" => $yhtiorow["nimi"],
    "emailAddress" => $yhtiorow["email"]
  );


  $key = openssl_pkey_new($key_config);
  $csr = openssl_csr_new($csr_info, $key);

  openssl_csr_export($csr, $csrout);

  $parameters = array(
    "method" => "POST",
    "data" => array(
      "pin" => $pin,
      "customer_id" => "11111111",
      "environment" => "TEST",
      "csr" => base64_encode($csrout)
    ),
    "url" => "" . SEPA_OSOITE . "nordea/get_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  var_dump($vastaus);
}
elseif (isset($tee) and $tee == "lataa_sertifikaatti") {
  if ($_POST["submit"] and avaimet_ja_salasana_kunnossa()) {

    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $salattu_sertifikaatti = salaa($sertifikaatti, $salasana);

    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
    $salattu_private_key = salaa($private_key, $salasana);

    $target_id = hae_target_id($sertifikaatti, $private_key, $customer_id);

    $query = "UPDATE yriti
              SET private_key='{$salattu_private_key}', certificate='{$salattu_sertifikaatti}',
                  sepa_customer_id='{$customer_id}', sepa_target_id='{$target_id}'
              WHERE tunnus={$tili} AND yhtio='{$kukarow['yhtio']}'";

    $result = pupe_query($query);

    if ($result) {
      echo "Tunnukset lisätty";
    }
    else {
      echo "Tunnukset eivät tallentuneet tietokantaan";
    }
  }
  else {
    $tilit = hae_tilit($kukarow["yhtio"]);

    echo "<form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='lataa_sertifikaatti'/>";
    echo "<input type='hidden' name='tili' value='{$tili}'/>";
    echo "<table>";
    echo "<tbody>";

    echo "<tr>";
    echo "<td>";
    echo "<label for='private_key'>" . t('Yksityinen avain') . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<input type='file' name='private_key' id='private_key'>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<label for='certificate'>" . t('Sertifikaatti') . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<input type='file' name='certificate' id='certificate'/>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label for='customer_id'>Sertifikaattiin yhdistetty asiakastunnus</label></td>";
    echo "<td><input type='text' name='customer_id' id='customer_id'/></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<label for='salasana'>" . t("Salasana, jolla tiedot suojataan") . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<input type='password' name='salasana' id='salasana'/>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<label for='salasanan_vahvistus'>" . t("Salasanan vahvistus") . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<input type='password' name='salasanan_vahvistus' id='salasanan_vahvistus'/>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='back'>";
    echo "<input type='submit' name='submit' value='" . t('Tallenna tunnukset') . "'/>";
    echo "</td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table";
    echo "</form>";
  }
}
elseif (isset($tee) and $tee == "hae_tiliote") {
  if ($salasana) {
    $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $kukarow, $salasana);

    $viitteet = hae_viitteet("TITO", $tunnukset);

    if ($viitteet and lataa_tiedostot($viitteet, "TITO", $tunnukset)) {
      echo "Tiedostot ladattu";
    }
  }
  else {
    tarkista_salasana();

    salasana_formi();
  }
}
elseif (isset($tee) and $tee == "hae_viiteaineisto") {
  if ($salasana) {
    $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $kukarow, $salasana);

    $viitteet = hae_viitteet("KTL", $tunnukset);

    if ($viitteet and lataa_tiedostot($viitteet, "KTL", $tunnukset)) {
      echo "Tiedostot ladattu";
    }
  }
  else {
    tarkista_salasana();

    salasana_formi();
  }
}
elseif (isset($tee) and $tee == "laheta_maksuaineisto") {
  if ($salasana and $_FILES["maksuaineisto"]["tmp_name"]) {
    $maksuaineisto = file_get_contents($_FILES["maksuaineisto"]["tmp_name"]);
    $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $kukarow, $salasana);

    $vastaus = laheta_maksuaineisto($tunnukset, $maksuaineisto);

    if ($vastaus) {
      echo "<table>";
      echo "<tbody>";

      foreach ($vastaus[1] as $key => $value) {
        echo "<tr>";
        echo "<td>{$key}</td>";
        echo "<td>{$value}</td>";
        echo "</tr>";
      }

      echo "</tbody";
      echo "</table>";
    }
  }
  else {
    tarkista_salasana();
    tarkista_maksuaineisto();

    salasana_formi();
  }
}
elseif (isset($tee) and $tee == "valitse_komento") {
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tili' value='{$tili}'/>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>Mitä haluat tehdä?</td>";
  echo "<td>";
  echo "<select name='tee'>";
  echo "<option value='lataa_sertifikaatti'>" . t('Lataa sertifikaatti järjestelmään') . "</option>";
  echo "<option value='hae_tiliote'>" . t("Hae tiliote") . "</option>";
  echo "<option value='hae_viiteaineisto'>" . t("Hae viiteaineisto") . "</option>";
  echo "<option value='laheta_maksuaineisto'>" . t("Lähetä maksuaineisto") . "</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "<input type='submit' value='" . t('OK') . "'>";
  echo "</form>";
}
else {
  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio='{$kukarow["yhtio"]}' AND sepa_customer_id != ''";

  $result = pupe_query($query);

  $kaytossa_olevat_tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($kaytossa_olevat_tilit, $rivi);
  }

  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tee' value='valitse_komento'/>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>Käytössä olevat pankkiyhteydet</td>";
  echo "<td>";
  echo "<select name='tili'>";

  foreach ($kaytossa_olevat_tilit as $tili) {
    echo "<option value='{$tili["tunnus"]}'>{$tili["nimi"]}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "<input type='submit' value='" . t('Valitse tili') . "'>";
  echo "<br/><br/>";
  echo "<input type='submit' name='uusi_pankkiyhteys' value='" . t("Uusi pankkiyhteys") . "'/>";
  echo "</form>";
}

/**
 * @param $data
 * @param $salasana
 * @return string
 */
function salaa($data, $salasana)
{
  $avain = hash("SHA256", $salasana, true);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $avain, $data, MCRYPT_MODE_CBC, $iv);
  $salattu_data = $iv . $salattu_data;

  return base64_encode($salattu_data);
}

/**
 * @param $yhtio
 * @return array
 */
function hae_tilit($yhtio)
{
  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio='{$yhtio}' AND bic != '' AND bic IS NOT NULL";

  $result = pupe_query($query);

  $tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($tilit, $rivi);
  }

  return $tilit;
}

/**
 * @param $yhtio
 * @return array
 */
function hae_uudet_tilit($yhtio)
{
  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio='{$yhtio}' AND bic != '' AND bic IS NOT NULL AND sepa_customer_id = ''";

  $result = pupe_query($query);

  $tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($tilit, $rivi);
  }

  return $tilit;
}

/**
 * @param $tili
 * @param $yhtio
 * @return array
 */
function hae_avain_sertifikaatti_ja_customer_id($tili, $yhtio)
{
  $query = "SELECT private_key, certificate, sepa_customer_id
              FROM yriti
              WHERE tunnus={$tili} AND yhtio='{$yhtio}'";

  $result = pupe_query($query);
  $rivi = mysql_fetch_assoc($result);

  return $rivi;
}

/**
 * @param $salattu_data
 * @param $salasana
 * @return string
 */
function pura_salaus($salattu_data, $salasana)
{
  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = substr($salattu_data_binaari, 0, $iv_size);

  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size);

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}

/**
 * @return bool
 */
function avaimet_ja_salasana_kunnossa()
{
  $virheet_maara = 0;

  if (!$_FILES["certificate"]["tmp_name"]) {
    $virheet_maara++;
    echo "<font class='error'>Sertifikaatti täytyy antaa</font><br/>";
  }
  if (!$_FILES["private_key"]["tmp_name"]) {
    $virheet_maara++;
    echo "<font class='error'>Avain täytyy antaa</font><br/>";
  }
  if (!$_POST["customer_id"]) {
    $virheet_maara++;
    echo "<font class='error'>Asiakastunnus täytyy antaa</font><br/>";
  }
  if (empty($_POST["salasana"])) {
    $virheet_maara++;
    echo "<font class='error'>Salasana täytyy antaa</font><br/>";
  }
  if (!$_POST["salasana"] == $_POST["salasanan_vahvistus"]) {
    $virheet_maara++;
    echo "<font class='error'>Salasanan vahvistus ei vastannut salasanaa</font><br/>";
  }

  if ($virheet_maara == 0) {
    return true;
  }

  return false;
}

/**
 * @param $tiedostotyyppi
 * @param $tunnukset
 * @return array
 */
function hae_viitteet($tiedostotyyppi, $tunnukset)
{
  $parameters = array(
    "method" => "POST",
    "data" => array(
      "cert" => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type" => $tiedostotyyppi,
      "target_id" => "11111111A1"
    ),
    "url" => "" . SEPA_OSOITE . "nordea/download_file_list",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
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
 * @return bool
 */
function lataa_tiedostot($viitteet, $tiedostotyyppi, $tunnukset)
{
  $onnistuneet = 0;

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method" => "POST",
      "data" => array(
        "cert" => base64_encode($tunnukset["sertifikaatti"]),
        "private_key" => base64_encode($tunnukset["avain"]),
        "customer_id" => $tunnukset["customer_id"],
        "file_type" => $tiedostotyyppi,
        "target_id" => "11111111A1",
        "file_reference" => $viite
      ),
      "url" => "" . SEPA_OSOITE . "nordea/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token=" . ACCESS_TOKEN
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

function salasana_formi()
{

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
 * @param $kukarow
 * @param $salasana
 * @return array
 */
function hae_tunnukset_ja_pura_salaus($tili, $kukarow, $salasana)
{
  $haetut_tunnukset = hae_avain_sertifikaatti_ja_customer_id($tili, $kukarow["yhtio"]);

  $avain = pura_salaus($haetut_tunnukset["private_key"], $salasana);
  $sertifikaatti = pura_salaus($haetut_tunnukset["certificate"], $salasana);
  $customer_id = $haetut_tunnukset["sepa_customer_id"];

  return array(
    "avain" => $avain,
    "sertifikaatti" => $sertifikaatti,
    "customer_id" => $customer_id
  );
}

/**
 * @param $tunnukset
 * @param $maksuaineisto
 * @return array
 */
function laheta_maksuaineisto($tunnukset, $maksuaineisto)
{
  $parameters = array(
    "method" => "POST",
    "data" => array(
      "cert" => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type" => "NDCORPAYS",
      "target_id" => "11111111A1",
      "content" => $maksuaineisto
    ),
    "url" => "" . SEPA_OSOITE . "nordea/upload_file",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  return $vastaus;
}

function tarkista_salasana()
{
  if (isset($_POST["salasana"]) and empty($_POST["salasana"])) {
    echo "<font class='error'>Salasana täytyy antaa</font><br/>";
  }
}

function tarkista_maksuaineisto()
{
  if (isset($_FILES["maksuaineisto"]) and !$_FILES["maksuaineisto"]["tmp_name"]) {
    echo "<font class='error'>Maksuaineisto puuttuu</font><br/>";
  }
}

/**
 * @param $sertifikaatti
 * @param $private_key
 * @param $customer_id
 * @return string
 */
function hae_target_id($sertifikaatti, $private_key, $customer_id)
{
  $parameters = array(
    "method" => "POST",
    "data" => array(
      "cert" => base64_encode($sertifikaatti),
      "private_key" => base64_encode($private_key),
      "customer_id" => $customer_id
    ),
    "url" => "" . SEPA_OSOITE . "nordea/get_user_info",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  return $vastaus[1]["userFileTypes"][0]["targetId"];
}

/**
 * @param $vastaus
 * @return bool
 */
function vastaus_kunnossa($vastaus)
{
  switch ($vastaus[0]) {
    case 200:
      return true;
    case 500:
      echo "<font class='error'>Pankki ei vastaa kyselyyn, yritä myöhemmin uudestaan</font>";
      return false;
    case 0:
      echo "<font class='error'>Sepa-palvelimeen ei jostain syystä saada yhteyttä, ";
      echo "yritä myöhemmin uudestaan</font>";
      return false;
  }
}
