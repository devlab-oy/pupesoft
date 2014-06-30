<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t("SEPA-pankkiyhteys") . "</font><hr>";

$tee = $_REQUEST["tee"];

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  echo "Sertifikaatin lataaminen";
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

    <input type='submit' value='" . t("OK") . "'>
  </form>";
}
