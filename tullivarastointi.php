<?php
require "inc/parametrit.inc";

$errors = array();

if (isset($task) and $task == 'perusta_saapuminen') {

  print_r($tuote);

  foreach ($tuote as $key => $tiedot) {

    if (empty($tiedot['nimitys'])) {
      $errors[$key]["nimitys"] = t("Syˆt‰ nimitys");
    }

    if (empty($tiedot['malli'])) {
      $errors[$key]["malli"] = t("Syˆt‰ malli");
    }

    if (empty($tiedot['maara'])) {
      $errors[$key]["maara"] = t("Syˆt‰ m‰‰ra");
    }

    if (!is_numeric($tiedot['maara'])) {
      $errors[$key]["maara"] = t("Tarkista m‰‰ra");
    }

    if (empty($tiedot['nettopaino'])) {
      $errors[$key]["nettopaino"] = t("Syˆt‰ nettopaino");
    }

    if (!is_numeric($tiedot['nettopaino'])) {
      $errors[$key]["nettopaino"] = t("Tarkista nettopaino");
    }

    if (empty($tiedot['bruttopaino'])) {
      $errors[$key]["bruttopaino"] = t("Syˆt‰ bruttopaino");
    }

    if (!is_numeric($tiedot['bruttopaino'])) {
      $errors[$key]["bruttopaino"] = t("Tarkista bruttopaino");
    }

    if (empty($tiedot['tilavuus'])) {
      $errors[$key]["tilavuus"] = t("Syˆt‰ tilavuus");
    }

    if (!is_numeric($tiedot['tilavuus'])) {
      $errors[$key]["tilavuus"] = t("Tarkista tilavuus");
    }

  }

  if (count($errors) > 0) {
    $task = 'uusi_saapuminen';
  }
  else {

    $query  = "SELECT *
               FROM toimi
               WHERE yhtio = '{$kukarow['yhtio']}'
               AND tunnus = '{$toimittaja}'";
    $result = pupe_query($query);

    $toimittajarow = mysql_fetch_assoc($result);

    $saapumistunnus = uusi_saapuminen($toimittajarow);

    $vuosi = date("y");
    $varastokoodi = "RP";

    // haetaan seuraava vapaa juokseva numero
    $query  = "SELECT asiakkaan_tilausnumero AS saapumiskoodi
               FROM lasku
               WHERE yhtio = '{$kukarow['yhtio']}'
               AND tila = 'K'
               AND viesti = 'tullivarasto'
               AND asiakkaan_tilausnumero LIKE '%/{$vuosi}'
               ORDER BY tunnus DESC
               LIMIT 1";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    if ($row) {
      $koodin_osat = explode("/", $row['juoksu']);
      $juoksunumero = (int) $kodin_osat[1];
      $saapumiskoodi = $varastokoodi . '/' . $juoksunumero + 1 . "/" . $vuosi;
    }
    else {
      $saapumiskoodi = $varastokoodi . '/1/' . $vuosi;
    }

    $query = "UPDATE lasku SET
              asiakkaan_tilausnumero = '{$saapumiskoodi}'
              viesti = 'tullivarasto',
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$saapumistunnus}'";
    pupe_query($query);

    foreach ($tuote as $key => $tiedot) {

      $uusi_tuoteno = $saapumistunnus . "-" . $key;

      $nimitys = mysql_real_escape_string($tiedot['nimitys']);
      $tuotemassa = number_format($tiedot['paino'], 6);

      $query = "INSERT INTO tuote
                SET yhtio = '{$kukarow['yhtio']}',
                tuoteno = '{$uusi_tuoteno}',
                nimitys = '{$nimitys}',
                malli = '{$malli}',
                tuotemassa = '{$tuotemassa}'";
      pupe_query($query);

      $query = "INSERT INTO tuotteen_toimittajat
                SET yhtio = '{$kukarow['yhtio']}',
                tuoteno = '{$uusi_tuoteno}',
                liitostunnus = '{$toimittaja}'";
      pupe_query($query);

      require "../tilauskasittely/lisaarivi.inc";

    }
  }
}


if (!isset($task) or $task == 'uusi_saapuminen') {

  //haetaan toimittajat valmiiksi
  $query = "SELECT nimi, tunnus
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $toimittajat = array();
  while ($toimittaja = mysql_fetch_assoc($result)) {
    $toimittajat[$toimittaja['tunnus']] = $toimittaja['nimi'];
  }

}

echo "<font class='head'>".t("Saapumisen perustaminen")."</font><hr><br>";

if (isset($task) and $task == "uusi_saapuminen") {

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='perusta_saapuminen' />
  <input type='hidden' name='toimittajatunnus' value='$toimittajatunnus' />
  <input type='hidden' name='tuoteryhmien_maara' value='$tuoteryhmien_maara' />
  <table>
  <tr>
    <th>" . t("Toimittaja") ."</th>
    <td>{$toimittajat[$toimittajatunnus]}</td>
    <td class='back error'>{$errors['konttinumero']}</td>
  </tr>
  </table>";

  $laskuri = 1;
  while ($laskuri <= $tuoteryhmien_maara) {

    $nimitys = $tuote[$laskuri]['nimitys'];
    $malli = $tuote[$laskuri]['malli'];
    $maara = $tuote[$laskuri]['maara'];
    $nettopaino = $tuote[$laskuri]['nettopaino'];
    $bruttopaino = $tuote[$laskuri]['bruttopaino'];
    $tilavuus = $tuote[$laskuri]['tilavuus'];

    echo "
    <table style='display:inline-block; margin:10px 10px 0 0;'>

      <tr>
        <th colspan='2'>" . t("Tuote") ." {$laskuri}</th>
        <td class='back error'></td>
      </tr>

      <tr>
        <th>" . t("Nimitys") ."</th>
        <td><input type='text' name='tuote[{$laskuri}][nimitys]' value='{$nimitys}' /></td>
        <td class='back error'>{$errors[$laskuri]['nimitys']}</td>
      </tr>

      <tr>
        <th>" . t("Malli") ."</th>
        <td><input type='text' name='tuote[{$laskuri}][malli]' value='{$malli}' /></td>
        <td class='back error'>{$errors[$laskuri]['malli']}</td>
      </tr>

      <tr>
        <th>" . t("M‰‰r‰") ."</th>
        <td><input type='text' name='tuote[{$laskuri}][maara]' value='$maara' /></td>
        <td class='back error'>{$errors[$laskuri]['maara']}</td>
      </tr>

      <tr>
        <th>" . t("Nettopaino") ."</th>
        <td><input type='text' name='tuote[{$laskuri}][nettopaino]' value='{$nettopaino}' /></td>
        <td class='back error'>{$errors[$laskuri]['nettopaino']}</td>
      </tr>

      <tr>
        <th>" . t("Bruttopaino") ."</th>
        <td><input type='text' name='tuote[{$laskuri}][bruttopaino]' value='{$bruttopaino}' /></td>
        <td class='back error'>{$errors[$laskuri]['bruttopaino']}</td>
      </tr>

      <tr>
        <th>" . t("Tilavuus") ."</th>
        <td><input type='text' name='tuote[{$laskuri}][tilauvuus]' value='{$tilavuus}' /></td>
        <td class='back error'>{$errors[$laskuri]['tilavuus']}</td>
      </tr>
    </table>";

    $laskuri++;

  }

  echo "<br><br><input type='submit' value='". t("Perusta saapuminen ja tuotteet") ."' /></form>";

}

if (!isset($task)) {

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='uusi_saapuminen' />
  <input type='hidden' name='' value='' />
  <table>
  <tr>
    <th>" . t("Toimittaja") ."</th>
    <td>
    <select name='toimittajatunnus'>
      <option>" . t("Valitse toimittaja") ."</option>";

      foreach ($toimittajat as $tunnus => $nimi) {
        echo "<option value='{$tunnus}'>{$nimi}</option>";
      }

  echo "</select></td>
    <td class='back error'></td>
  </tr>

  <tr>
    <th>" . t("Tuoteryhmien m‰‰r‰") . "</th>
    <td><input type='text' name='tuoteryhmien_maara' value='{$tuoteryhmien_maara}' /></td>
    <td class='back error'>{$errors['maara']}</td>
  </tr>

  <tr>
    <th>" . t("Varasto") . "</th>
    <td>
      <select name='toimittajatunnus'>
        <option>" . t("Valitse varasto") ."</option>";

        // dummy-array...
        $varastot = array(
          '100' => 'Rompintie (v‰liaikainen)',
          '101' => 'Rompintie (tullivarasto)',
          '102' => 'Hanskinmaantie (v‰liaikainen)',
          '103' => 'Hanskinmaantie (tullivarasto)',
          '104' => 'Yhteisˆtavara'
          );

        foreach ($varastot as $tunnus => $nimi) {
          echo "<option value='{$tunnus}'>{$nimi}</option>";
        }

    echo "</select></td>
  </tr>

  <tr>
    <th></th>
    <td align='right'><input type='submit' value='". t("Jatka") ."' /></td>
    <td class='back'></td>
  </tr>
  </table>
  </form>";

}


require "inc/footer.inc";






