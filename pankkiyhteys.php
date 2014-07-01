<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

$tee = $_REQUEST["tee"];

/**
 * @param $data
 * @param $salasana
 * @return string
 */
function salaa($data, $salasana)
{
  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salasana, $data, MCRYPT_MODE_ECB);
  $salattu_data = $iv . $salattu_data;
  return base64_encode($salattu_data);
}

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  if ($_FILES["certificate"] and $_FILES["private_key"] and $_POST["salasana"]) {
    $tili = $_POST["tili"];
    $salasana = $_POST["salasana"];

    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $salattu_sertifikaatti = salaa($sertifikaatti, $salasana);

    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
    $salattu_private_key = salaa($private_key, $salasana);

    $query = "UPDATE yriti
              SET private_key='{$salattu_private_key}', certificate='{$salattu_sertifikaatti}'
              WHERE tunnus={$tili}";

    $result = pupe_query($query);

    if ($result) {
      echo "Tunnukset lisätty";
    }
    else {
      echo "Tunnukset eivät tallentuneet tietokantaan";
    }
  }
  else {
    $query = "SELECT tunnus, nimi
              FROM yriti";

    $result = pupe_query($query);

    $tilit = array();

    while ($rivi = mysql_fetch_assoc($result)) {
      $tili = array();
      array_push($tili, $rivi["tunnus"]);
      array_push($tili, $rivi["nimi"]);
      array_push($tilit, $tili);
    }

    echo "<form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='lataa_sertifikaatti'/>";
    echo "<label for='tili'>" . t("Tili, jolle sertifikaatti on") . "</label>";
    echo "<select name='tili'>";
    foreach ($tilit as $tili) {
      echo "<option value='" . $tili[0] . "'>" . $tili[1] . "</option>";
    }
    echo "</select>";
    echo "<br/>";
    echo "<label for='private_key'>" . t('Yksityinen avain') . "</label>";
    echo "<input type='file' name='private_key' id='private_key'>";
    echo "<br>";
    echo "<label for='certificate'>" . t('Sertifikaatti') . "</label>";
    echo "<input type='file' name='certificate' id='certificate'/>";
    echo "<br/>";
    echo "<label for='salasana'>" . t("Salasana, jolla tiedot suojataan") . "</label>";
    echo "<input type='password' name='salasana' id='salasana'/>";
    echo "<br/>";
    echo "<input type='submit' name='submit' value='" . t('Lähetä') . "'>";
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
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "<input type='submit' value='" . t('OK') . "'>";
  echo "</form>";
}
