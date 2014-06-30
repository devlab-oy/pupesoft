<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font><hr>";

$tee = $_REQUEST["tee"];

echo "Post:<br>";
echo var_dump($_POST);
echo "<br><br>Request<br>";
echo var_dump($_REQUEST);
echo "<br><br>Files<br>";
echo var_dump($_FILES["certificate"]);

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  echo "
  <form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>
    <label for='private_key'>" . t('Yksityinen avain') . " </label>
    <input type = 'file' name = 'private_key' id = 'private_key'>
    <br>
    <label for='certificate'>" . t('Sertifikaatti') . "</label>
    <input type='file' name='certificate' id='certificate'/>
    <input type='submit' name='submit' value='" . t('Lähetä') . "'>
  </form>";
}
else {
  echo "
  <form method='post' action='pankkiyhteys.php'>
    <table>
      <tbody>
        <tr>
          <td>Mita haluat tehdä?</td>
          <td>
            <select name='tee'>
              <option value='lataa_sertifikaatti'>" . t('Lataa sertifikaatti') . "</option>
            </select>
          </td>
        </tr>
      </tbody>
    </table>

    <input type='submit' value='" . t('OK') . "'>
  </form>";
}
