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

if (isset($tee) and $tee == "lataa_sertifikaatti") {
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
      echo "Tunnukset lis‰tty";
    }
    else {
      echo "Tunnukset eiv‰t tallentuneet tietokantaan";
    }
  }
  else {
    $tilit = hae_tilit($kukarow["yhtio"]);

    echo "<form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='lataa_sertifikaatti'/>";
    echo "<table>";
    echo "<tbody>";

    echo "<tr>";
    echo "<td>";
    echo "<label for='tili'>" . t("Tili, jolle sertifikaatti on") . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<select name='tili'>";
    foreach ($tilit as $tili) {
      echo "<option value='" . $tili["tunnus"] . "'>" . $tili["nimi"] . "</option>";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>";

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

    if (lataa_tiedostot($viitteet, "TITO", $tunnukset)) {
      echo "Tiedostot ladattu";
    }
    else {
      echo "Tiedostojen lataaminen ei onnistunut";
    }
  }
  else {
    tarkista_salasana();

    tiliformi("hae_tiliote", $kukarow);
  }
}
elseif (isset($tee) and $tee == "hae_viiteaineisto") {
  if ($salasana) {
    $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $kukarow, $salasana);

    $viitteet = hae_viitteet("KTL", $tunnukset);

    if (lataa_tiedostot($viitteet, "KTL", $tunnukset)) {
      echo "Tiedostot ladattu";
    }
    else {
      echo "Tiedostojen lataaminen ei onnistunut";
    }
  }
  else {
    tarkista_salasana();

    tiliformi("hae_viiteaineisto", $kukarow);
  }
}
elseif (isset($tee) and $tee == "laheta_maksuaineisto") {
  if ($salasana and $_FILES["maksuaineisto"]["tmp_name"]) {
    $maksuaineisto = file_get_contents($_FILES["maksuaineisto"]["tmp_name"]);
    $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $kukarow, $salasana);

    $vastaus = laheta_maksuaineisto($tunnukset, $maksuaineisto);

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
  else {
    tarkista_salasana();
    tarkista_maksuaineisto();

    tiliformi("laheta_maksuaineisto", $kukarow);
  }
}
else {
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>Mit‰ haluat tehd‰?</td>";
  echo "<td>";
  echo "<select name='tee'>";
  echo "<option value='lataa_sertifikaatti'>" . t('Lataa sertifikaatti j‰rjestelm‰‰n') . "</option>";
  echo "<option value='hae_tiliote'>" . t("Hae tiliote") . "</option>";
  echo "<option value='hae_viiteaineisto'>" . t("Hae viiteaineisto") . "</option>";
  echo "<option value='laheta_maksuaineisto'>" . t("L‰het‰ maksuaineisto") . "</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "<input type='submit' value='" . t('OK') . "'>";
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
            WHERE yhtio='{$yhtio}'";

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
    echo "<font class='error'>Sertifikaatti t‰ytyy antaa</font><br/>";
  }
  if (!$_FILES["private_key"]["tmp_name"]) {
    $virheet_maara++;
    echo "<font class='error'>Avain t‰ytyy antaa</font><br/>";
  }
  if (!$_POST["customer_id"]) {
    $virheet_maara++;
    echo "<font class='error'>Asiakastunnus t‰ytyy antaa</font><br/>";
  }
  if (empty($_POST["salasana"])) {
    $virheet_maara++;
    echo "<font class='error'>Salasana t‰ytyy antaa</font><br/>";
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

/**
 * @param $komento
 * @param $kukarow
 */
function tiliformi($komento, $kukarow)
{
  $tilit = hae_tilit($kukarow["yhtio"]);

  if ($komento == "laheta_maksuaineisto") {
    $enctype = "enctype='multipart/form-data'";
  }
  else {
    $enctype = "";
  }

  echo "<form method='post' action='pankkiyhteys.php' {$enctype}>";
  echo "<input type='hidden' name='tee' value='{$komento}'/>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>";
  echo "<label for='tili'>" . t("Tili") . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<select name='tili'>";
  foreach ($tilit as $tili) {
    echo "<option value='" . $tili["tunnus"] . "'>" . $tili["nimi"] . "</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";
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
  return $vastaus;
}

function tarkista_salasana()
{
  if (isset($_POST["salasana"]) and empty($_POST["salasana"])) {
    echo "<font class='error'>Salasana t‰ytyy antaa</font><br/>";
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
