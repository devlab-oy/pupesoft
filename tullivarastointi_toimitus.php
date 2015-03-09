<?php
require "inc/parametrit.inc";

$errors = array();

if (!isset($task)) {
  $otsikko = t("Hae tuotteita");
  $view = "perus";
}

if (isset($task) and $task == 'etsi') {

  $hakusana = mysql_real_escape_string($hakusana);

  $query = "SELECT tilausrivi.*,
            CONCAT(SUBSTRING(tilausrivi.hyllyalue, 3, 4), tilausrivi.hyllynro) AS varastopaikka,
            lasku.asiakkaan_tilausnumero AS saapumiskoodi,
            varastopaikat.nimitys AS varastonimi,
            tuote.malli
            FROM tilausrivi
            JOIN lasku
             ON lasku.yhtio = tilausrivi.yhtio
             AND lasku.tunnus = tilausrivi.otunnus
            JOIN varastopaikat
            ON varastopaikat.yhtio = lasku.yhtio
            AND varastopaikat.tunnus = lasku.varasto

            JOIN tuote
            ON tuote.yhtio = lasku.yhtio
            AND tuote.tuoteno = tilausrivi.tuoteno

            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND lasku.viesti = 'tullivarasto'
            AND tilausrivi.otunnus != 0";

  $lisa = " AND lasku.asiakkaan_tilausnumero = '{$hakusana}'";
  $result = pupe_query($query.$lisa);

  if (mysql_num_rows($result) == 0) {
    $lisa = " AND tilausrivi.nimitys LIKE '%{$hakusana}%'";
    $result = pupe_query($query.$lisa);
  }
  elseif (mysql_num_rows($result) == 0) {
    $errors[] = t("Ei osumia");
  }

  if (count($errors) < 1) {

   $rivit = array();
    while ($rivi = mysql_fetch_assoc($result)) {
      $rivit[] = $rivi;
    }
  }
  $view = 'perus';
  $otsikko = t("Toimituksen kokoaminen");
}


echo "<font class='head'>{$otsikko}</font><hr><br>";

if ($view == 'perus') {

  echo "<form method='post'>";
  echo "<input type='hidden' name='task' value='etsi' />";
  echo "<table><tr><th>Etsi tuotteita</th><td><input type='text' name='hakusana'></td><td class='back'><input type='submit' class='hae_btn' value='Etsi'></td></tr></table>";
  echo "</form><br>";

  if (isset($rivit) and count($rivit) > 0) {

    echo "<table>";

    echo "
    <tr>
      <th>" . t("Saapumiskoodi") ."</th>
      <th>" . t("Nimitys") ."</th>
      <th>" . t("Malli") ."</th>
      <th>" . t("Kpl") ."</th>
      <th>" . t("Varasto") ."</th>
      <th>" . t("Varastopaikka") ."</th>
      <th class='back'></th>
    </tr>";

    foreach ($rivit as $rivi) {

      if (empty($rivi['varastopaikka'])) {
        $varastopaikka = t("Ei varastoitu!");
      }
      else {
        $varastopaikka = $rivi['varastopaikka'];
      }

      echo "
      <tr>
        <td>" . $rivi['saapumiskoodi'] ."</td>
        <td>" . $rivi['nimitys'] ."</td>
        <td>" . $rivi['malli'] ."</td>
        <td>" . (int) $rivi['tilkpl'] ."</td>
        <td>" . $rivi['varastonimi'] ."</td>
        <td align='center'>" . $varastopaikka ."</td>
        <td class='back'>";

        if (!empty($rivi['varastopaikka'])) {

          echo "
            <form method='post'>
            <input type='submit' value='" . t("Lis&auml;&auml; toimitukseen") . "'/>
            </form>";

        }

      echo "
        </td>
      </tr>";

    }
  echo "</table>";
  }

  if (count($errors) > 0) {
    echo "<div class='error' style='text-align:center'>";
    foreach ($errors as $error) {
      echo $error."<br>";
    }
    echo "</div>";
  }

}







require "inc/footer.inc";




