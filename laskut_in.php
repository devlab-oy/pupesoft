<?php

/**
 * P�ivitt�� myyntireskontran laskuja maksetuksi Excel-tiedostosta.
 *
 */


include "inc/parametrit.inc";

$errors = array();

// Formilta on saatu tiedosto
if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

  // Pilkotaan tiedostonimi osiin
  $path_parts = pathinfo($_FILES['userfile']['name']);
  $ext = strtoupper($path_parts['extension']);

  // Tarkistetaan tiedostop��te
  if ($ext != 'XLS') {
    $errors[] = "<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>";
  }

  // Tarkistetaan ett� tiedosto ei ole tyhj�
  if ($_FILES['userfile']['size'] == 0) {
    $errors[] = "<font class='error'<br>".t("Tiedosto on tyhj�")."!</font>";
  }

  if (empty($errors)) {
    // Luetaan excel tiedosto
    $tiedosto = pupeFileReader($_FILES['userfile']['tmp_name'], 'xls');

    // Loopataan excelist� tunnukset l�pi (maksamattomat laskut)
    foreach ($tiedosto as $rivi) {
      $maksamattomat_laskut[] = $rivi[0];
    }

    // Jos tunnus l�ytyy avoimista laskuista niin skipataan.
    $query = "  SELECT laskunro, tunnus
          FROM lasku
          WHERE yhtio='{$kukarow['yhtio']}'
          AND mapvm = '0000-00-00'
          AND tila = 'A'
          AND alatila = ''";
    $result = pupe_query($query);

    echo "<font class='message'>";
    echo t("Avoimia laskuja yhteens�").": ".mysql_num_rows($result)."<br>";
    echo "</font>";

    /**
     * Toimii 'k��nteisesti' ja p�ivitt� ne lasku maksetuksi joita EI l�ydy
     * sis��nluettavasta tiedostosta.
     */
    // P�ivitet��n ne laskut maksetuksi joita ei l�ydy maksamattomat_laskut-listasta
    $query = "  UPDATE lasku
          JOIN tyomaarays
          ON ( tyomaarays.yhtio = lasku.yhtio
            AND tyomaarays.otunnus = lasku.tunnus
            AND tyomaarays.takuunumero NOT IN (".implode(', ', $maksamattomat_laskut).") )
          SET lasku.mapvm = now(),
          tyomaarays.tyostatus = '5'
          WHERE lasku.yhtio='{$kukarow['yhtio']}'
          AND lasku.mapvm = '0000-00-00'
          AND lasku.tila = 'A'
          AND lasku.alatila = ''";
    $result = pupe_query($query);

    echo "<font class='message'>";
    echo mysql_affected_rows()." ".t("laskua p�ivitetty maksetuksi")."<br>";
    echo "</font>";

    // P�ivitet��n kaikki kaikki laskut jotka l�ytyv�t maksamattomat listasta.
    // T�m� on vain varokeino jos joku lasku on merkattu maksetuksi.
    $query = "  UPDATE lasku
          JOIN tyomaarays
          ON ( tyomaarays.yhtio = lasku.yhtio
            AND tyomaarays.otunnus = lasku.tunnus
            AND tyomaarays.takuunumero IN (".implode(', ', $maksamattomat_laskut)."))
          SET lasku.mapvm = '0000-00-00'
          WHERE lasku.yhtio='{$kukarow['yhtio']}'
          AND lasku.tila = 'A'
          AND lasku.alatila = ''
          AND lasku.mapvm != '0000-00-00'";
    $result = pupe_query($query);

    echo "<font class='message'>";
    echo mysql_affected_rows()." ".t("laskua korjattu maksamattomaksi")."<br>";
    echo "</font>";
  }
}
// Form
echo "<font class='head'>".t("Myyntireskontran sis��nluku")."</font><hr>";

echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
    <table>
      <tr>
        <th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='".t("L�het�")."'></td>
      <tr>
    </table>
  </form>";

// Virheet
if ($errors) {
  foreach ($errors as $error) {
    echo $error;
  }
}
