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
  echo "kissa";
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
  $salattu_sertifikaatti = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salasana, $data, MCRYPT_MODE_ECB);
  var_dump($salattu_sertifikaatti);
  $salattu_sertifikaatti = $iv . $salattu_sertifikaatti;
  return base64_encode($salattu_sertifikaatti);
}

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  if ($_FILES["certificate"] and $_FILES["private_key"]) {
    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);

    $query = "UPDATE yriti
              SET private_key='{$private_key}', certificate='{$sertifikaatti}'
              WHERE tunnus=66";

    pupe_query($query);
  }
  else {
    echo "<form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='lataa_sertifikaatti'/>";
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
