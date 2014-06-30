<?php

require("inc/parametrit.inc");
?>
  <font class='head'><?= t("SEPA-pankkiyhteys") ?></font>
  <hr>
<?php

$tee = $_REQUEST["tee"];

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  ?>
  <form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>
    <label for='private_key'><?= t("Yksityinen avain") ?></label>
    <input type='file' name='private_key' id='private_key'>
    <br>
    <input type='submit' name='submit' value='Submit'>
  </form>
<?php
}
else {
  ?>
  <form method='post' action='pankkiyhteys.php'>
    <table>
      <tbody>
      <tr>
        <td>Mita haluat tehdä?</td>
        <td>
          <select name='tee'>
            <option value='lataa_sertifikaatti'><?= t('Lataa sertifikaatti') ?></option>
          </select>
        </td>
      </tr>
      </tbody>
    </table>

    <input type='submit' value='<?= t("OK") ?>'>
  </form>
<?php
}
