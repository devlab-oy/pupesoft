<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

$tee = $_REQUEST["tee"];

echo "Post:<br>";
echo var_dump($_POST);
echo "<br><br>Request<br>";
echo var_dump($_REQUEST);
echo "<br><br>Files<br>";
echo var_dump($_FILES["certificate"]["tmp_name"]);

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  if ($_FILES["certificate"] and $_FILES["private_key"]) {
    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);

    $query = "UPDATE yriti
              SET private_key='{$private_key}', certificate='{$sertifikaatti}'
              WHERE tunnus=65";

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
