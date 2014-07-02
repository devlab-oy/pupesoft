<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  if (avaimet_ja_salasana_kunnossa($salasana, $salasanan_vahvistus)) {
    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $salattu_sertifikaatti = salaa($sertifikaatti, $salasana);

    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
    $salattu_private_key = salaa($private_key, $salasana);

    $query = "UPDATE yriti
              SET private_key='{$salattu_private_key}', certificate='{$salattu_sertifikaatti}'
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
    echo "<input type='submit' name='submit' value='" . t('Lähetä') . "'/>";
    echo "</td>";
    echo "</tr>";
    echo "</tbody>";
    echo "</table";
    echo "</form>";
  }
}
elseif (isset($tee) and $tee == "hae_tiliote") {
  if ($salasana) {
    $salatut_tunnukset = hae_avain_ja_sertifikaatti($tili, $kukarow["yhtio"]);

    $avain = pura_salaus($salatut_tunnukset["private_key"], $salasana);
    $sertifikaatti = pura_salaus($salatut_tunnukset["certificate"], $salasana);

    $viitteet = hae_viitteet($sertifikaatti, $avain);

    if (lataa_tiedostot($viitteet, $sertifikaatti, $avain)) {
      echo "Tiedostot ladattu";
    }
    else {
      echo "Tiedostojen lataaminen ei onnistunut";
    }
  }
  else {
    $tilit = hae_tilit($kukarow["yhtio"]);

    echo "<form method='post' action='pankkiyhteys.php'>";
    echo "<input type='hidden' name='tee' value='hae_tiliote'/>";
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
    echo "<tr>";
    echo "<td class='back'>";
    echo "<input type='submit'/>";
    echo "</td>";
    echo "</tr>";
    echo "</tbody>";
    echo "</table>";
    echo "</form>";
  }
}
else {
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>Mita haluat tehdä?</td>";
  echo "<td>";
  echo "<select name='tee'>";
  echo "<option value='lataa_sertifikaatti'>" . t('Lataa sertifikaatti') . "</option>";
  echo "<option value='hae_tiliote'>" . t("Hae tiliote") . "</option>";
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
function hae_avain_ja_sertifikaatti($tili, $yhtio)
{
  $query = "SELECT private_key, certificate
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
 * @param $salasana
 * @param $salasanan_vahvistus
 * @return bool
 */
function avaimet_ja_salasana_kunnossa($salasana, $salasanan_vahvistus)
{
  return $_FILES["certificate"]["tmp_name"] and $_FILES["private_key"]["tmp_name"] and !empty($salasana) and
  $salasana == $salasanan_vahvistus;
}

/**
 * @param $sertifikaatti
 * @param $avain
 * @return array
 */
function hae_viitteet($sertifikaatti, $avain)
{
  $parameters = array(
    "method" => "POST",
    "data" => array(
      "cert" => base64_encode($sertifikaatti),
      "private_key" => base64_encode($avain),
      "customer_id" => "11111111",
      "file_type" => "TITO",
      "target_id" => "11111111A1"
    ),
    "url" => "https://sepa.devlab.fi/api/nordea/download_file_list",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=Vl2E1xahRJz4vO4J28QSQn2mbkrM"
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
 * @param $sertifikaatti
 * @param $avain
 * @return bool
 */
function lataa_tiedostot($viitteet, $sertifikaatti, $avain)
{
  $onnistuneet = 0;

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method" => "POST",
      "data" => array(
        "cert" => base64_encode($sertifikaatti),
        "private_key" => base64_encode($avain),
        "customer_id" => "11111111",
        "file_type" => "TITO",
        "target_id" => "11111111A1",
        "file_reference" => $viite
      ),
      "url" => "https://sepa.devlab.fi/api/nordea/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token=Vl2E1xahRJz4vO4J28QSQn2mbkrM"
      )
    );

    $vastaus = pupesoft_rest($parameters);

    if ($vastaus[0] == 200) {
      $onnistuneet++;
    }

    file_put_contents("/tmp/{$viite}", base64_decode($vastaus[1]["data"]));
  }

  if (count($viitteet) == $onnistuneet) {
    return true;
  }

  return false;
}
