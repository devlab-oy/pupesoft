<?php
require "inc/parametrit.inc";

$errors = array();

if (!isset($task)) {
  $otsikko = t("Perustetut tulonumerot");
  $view = "perus";
}

if (isset($task) and $task == 'aloita_perustus') {
  $otsikko = t("Syˆt‰ saapumisen tiedot");
  $view = "tulotiedot";
}

if (isset($task) and $task == 'anna_tulotiedot') {

  if ($varastotunnus_ja_koodi == 'X') {
    $errors['varastotunnus_ja_koodi'] = t("Valitse varasto");
  }

  if ($toimittajatunnus == 'X') {
    $errors['toimittajatunnus'] = t("Valitse toimittaja");
  }

  if (empty($edeltava_asiakirja)) {
    $errors['edeltava_asiakirja'] = t("Edelt‰v‰ asiakirja puuttuu");
  }

  if (!is_numeric($tuoteryhmien_maara)) {
    $errors['tuoteryhmien_maara'] = t("Tarkista m‰‰ra");
  }
  elseif (empty($tuoteryhmien_maara)) {
   $tuoteryhmien_maara = 1;
  }

  if (count($errors) > 0) {
    $otsikko = t("Saapuvan rahdin perustaminen");
    $view = 'tulotiedot';
  }
  else {
    $otsikko = t("T‰ydenn‰ saapumisen tiedot");
    $view = "tuotetiedot";
  }

}


if (isset($task) and $task == 'perusta') {

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
    $view = 'tuotetiedot';
  }
  else {

    require_once "inc/luo_ostotilausotsikko.inc";

    // haetaan toimittajan tiedot
    $query = "SELECT *
              FROM toimi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$toimittajatunnus}'";
    $toimres = pupe_query($query);
    $toimrow = mysql_fetch_assoc($toimres);

    $params = array(
      'liitostunnus' => $toimrow['tunnus'],
      'nimi' => $toimrow['nimi'],
      'myytil_toimaika' => $data['toimitusaika'],
      'varasto' => $varastotunnus,
      'osoite' => $toimrow['osoite'],
      'postino' => $toimrow['postino'],
      'postitp' => $toimrow['postitp'],
      'maa' => $toimrow['maa'],
      'uusi_ostotilaus' => 'JOO'
    );

    $laskurow = luo_ostotilausotsikko($params);

    $query = "UPDATE lasku SET
              asiakkaan_tilausnumero = '{$tulonumero}',
              viesti = 'tullivarasto'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$laskurow['tunnus']}'";
    pupe_query($query);

    foreach ($tuote as $key => $tiedot) {

      $uusi_tuoteno = $tulonumero . "-" . $key;

      $nimitys = mysql_real_escape_string($tiedot['nimitys']);
      $tuotemassa = number_format($tiedot['bruttopaino'], 6);
      $malli = mysql_real_escape_string($tiedot['malli']);

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
                liitostunnus = '{$toimittajatunnus}'";
      pupe_query($query);

      // haetaan tuotteen tiedot
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$uusi_tuoteno}'";
      $tuoteres = pupe_query($query);

      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = $tiedot['maara'];
      $kerayspvm = $toimaika = date();
      $toim = '';
      $hinta = 0;
      $var = '';
      $kutsuja = '';
      $kpl2 = 0;
      $varasto = $varastotunnus;
      $toimittajan_tunnus = $toimrow['tunnus'];

      $kukarow['kesken'] = $laskurow['tunnus'];

      require "tilauskasittely/lisaarivi.inc";
    }
    header("Location: tullivarastointi.php?pe=ok&tn={$tulonumero}");
  }
}






echo "<font class='head'>{$otsikko} {$task}</font><hr><br>";

if (isset($pe) and $pe == "ok") {
  echo "<p class='green'>", t("Tulonumero: "), $tn, ' ', t("perustettu"), "</p>";
  echo "<br>";
}

if (isset($view) and $view == "tuotetiedot") {

  if(empty($tulonumero)) {

    $_varastotunnus_ja_koodi = explode("#", $varastotunnus_ja_koodi);
    $varastotunnus = $_varastotunnus_ja_koodi[0];
    $varastokoodi = $_varastotunnus_ja_koodi[1];

    $vuosi = date("y");

    // haetaan seuraava vapaa juokseva numero
    $query  = "SELECT asiakkaan_tilausnumero AS saapumiskoodi
               FROM lasku
               WHERE yhtio = '{$kukarow['yhtio']}'
               AND tila = 'O'
               AND viesti = 'tullivarasto'
               AND asiakkaan_tilausnumero LIKE '%-{$vuosi}'
               ORDER BY tunnus DESC
               LIMIT 1";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    if ($row) {
      $koodin_osat = explode("-", $row['saapumiskoodi']);
      $juoksunumero = $koodin_osat[1] + 1;
      $tulonumero = $varastokoodi . "-" . $juoksunumero . "-" . $vuosi;
    }
    else {
      $tulonumero = $varastokoodi . "-1-" . $vuosi;
    }
  }

  $toimittajat = toimittajat();

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='perusta' />
  <input type='hidden' name='toimittajatunnus' value='$toimittajatunnus' />
  <input type='hidden' name='varastotunnus_ja_koodi' value='$varastotunnus_ja_koodi' />
  <input type='hidden' name='tuoteryhmien_maara' value='$tuoteryhmien_maara' />
  <input type='hidden' name='varastotunnus' value='$varastotunnus' />
  <input type='hidden' name='varastokoodi' value='$varastokoodi' />
  <input type='hidden' name='edeltava_asiakirja' value='$edeltava_asiakirja' />
  <input type='hidden' name='tulonumero' value='$tulonumero' />";

  echo "
  <table>
  <tr>

    <th>" . t("Toimittaja") ."</th>
    <th>" . t("Tulonumero") ."</th>
    <th>" . t("Edelt‰v‰ asiakirja") ."</th>
    <th>" . t("Rekisterinumero") ."</th>
    <th>" . t("Tulop‰iva") ."</th>
  </tr>
  <tr>
    <td>{$toimittajat[$toimittajatunnus]}</td>
    <td>{$tulonumero}</td>
    <td>{$edeltava_asiakirja}</td>
    <td>{$rekisterinumero}</td>
    <td>{$tulopaiva}</td>
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
        <td><input type='text' name='tuote[{$laskuri}][tilavuus]' value='{$tilavuus}' /></td>
        <td class='back error'>{$errors[$laskuri]['tilavuus']}</td>
      </tr>
    </table>";

    $laskuri++;
  }

  if ($laskuri > 1) {
    echo "<br><br><input type='submit' value='". t("Perusta saapuminen ja tuotteet") ."' /></form>";
  }
  else {
    echo "<br><br><input type='submit' value='". t("Perusta saapuminen") ."' /></form>";
  }
}







if (isset($view) and $view == 'tulotiedot') {

  $toimittajat = toimittajat();

  if (!isset($tuoteryhmien_maara)) {
    $tuoteryhmien_maara = 1;
  }

  echo "
    <script>
      $(function($){
         $.datepicker.regional['fi'] = {
                     closeText: 'Sulje',
                     prevText: '&laquo;Edellinen',
                     nextText: 'Seuraava&raquo;',
                     currentText: 'T&auml;n&auml;&auml;n',
             monthNames: ['Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kes&auml;kuu',
              'Hein&auml;kuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu'],
              monthNamesShort: ['Tammi','Helmi','Maalis','Huhti','Touko','Kes&auml;',
              'Hein&auml;','Elo','Syys','Loka','Marras','Joulu'],
                      dayNamesShort: ['Su','Ma','Ti','Ke','To','Pe','Su'],
                      dayNames: ['Sunnuntai','Maanantai','Tiistai','Keskiviikko','Torstai','Perjantai','Lauantai'],
                      dayNamesMin: ['Su','Ma','Ti','Ke','To','Pe','La'],
                      weekHeader: 'Vk',
              dateFormat: 'dd.mm.yy',
                      firstDay: 1,
                      isRTL: false,
                      showMonthAfterYear: false,
                      yearSuffix: ''};
          $.datepicker.setDefaults($.datepicker.regional['fi']);
      });

      $(function() {
        $('#tulopaiva').datepicker();
      });
      </script>
  ";

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='anna_tulotiedot' />
  <input type='hidden' name='' value='' />
  <table>
  <tr>
    <th>" . t("Toimittaja") ."</th>
    <td>
    <select name='toimittajatunnus'>
      <option value='X'>" . t("Valitse toimittaja") ."</option>";

      foreach ($toimittajat as $tunnus => $nimi) {

        if ($tunnus == $toimittajatunnus) {
          $selected = 'selected';
        }
        else {
          $selected = '';
        }

        echo "<option value='{$tunnus}' {$selected}>{$nimi}</option>";
      }

  echo "</select></td>
    <td class='back error'>{$errors['toimittajatunnus']}</td>
  </tr>

  <tr>
    <th>" . t("Tuoteryhmien m‰‰r‰") . "</th>
    <td><input type='text' name='tuoteryhmien_maara' value='{$tuoteryhmien_maara}' /></td>
    <td class='back error'>{$errors['tuoteryhmien_maara']}</td>
  </tr>

  <tr>
    <th>" . t("Varasto") . "</th>
    <td>
      <select name='varastotunnus_ja_koodi'>
        <option value='X'>" . t("Valitse varasto") ."</option>";

        $varastot = varastot();

        foreach ($varastot as $varasto) {

          if ($varastotunnus_ja_koodi == $varasto['varastokoodi']) {
            $selected = 'selected';
          }
          else {
            $selected = '';
          }

          echo "<option value='{$varasto['varastokoodi']}' {$selected}>{$varasto['nimitys']}</option>";
        }


    echo "</select></td><td class='back error'>{$errors['varastotunnus_ja_koodi']}</td>
  </tr>


  <tr>
    <th>" . t("Edelt‰v‰ asiakirja") . "</th>
    <td><input type='text' name='edeltava_asiakirja' value='{$edeltava_asiakirja}' /></td>
    <td class='back error'>{$errors['edeltava_asiakirja']}</td>
  </tr>

  <tr>
    <th>" . t("Auton rekisterinumero") . "</th>
    <td><input type='text' name='rekisterinumero' value='{$rekisterinumero}' /></td>
    <td class='back error'>{$errors['rekisterinumero']}</td>
  </tr>

  <tr>
    <th>" . t("Tulop‰iv‰") . "</th>
    <td><input type='text' name='tulopaiva' id='tulopaiva' value='{$tulopaiva}' /></td>
    <td class='back error'>{$errors['tulopaiva']}</td>
  </tr>


  <tr>
    <th></th>
    <td align='right'><input type='submit' value='". t("Jatka") ."' /></td>
    <td class='back'></td>
  </tr>
  </table>
  </form>";

}




// perusn‰kym‰
if (isset($view) and $view == "perus") {

  echo "
    <form>
    <input type='hidden' name='task' value='aloita_perustus' />
    <input type='submit' value='". t("Perusta uusi saapuminen") . "' />
    </form><br><br>";

  $query = "SELECT lasku.asiakkaan_tilausnumero,
            tilausrivi.tilkpl as kpl,
            tilausrivi.nimitys,
            lasku.varasto,
            lasku.nimi
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            WHERE lasku.yhtio = 'rplog'
            AND viesti = 'tullivarasto'
            GROUP BY concat(lasku.tunnus, tilausrivi.tunnus)";
  $result = pupe_query($query);

  $tulot = array();
  $tuotteet = array();

  while ($tulo = mysql_fetch_assoc($result)) {

    $tuoteinfo = array('nimitys' => $tulo['nimitys'], 'kpl' => $tulo['kpl']);

    $tuotteet[$tulo['asiakkaan_tilausnumero']]['tuoteinfo'][] = $tuoteinfo;
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['toimittaja'] = $tulo['nimi'];
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['varasto'] = $tulo['varasto'];

  }

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tulonumero")."</th>";
  echo "<th>".t("Toimittaja")."</th>";
  echo "<th>".t("Kohdevarasto")."</th>";
  echo "<th>".t("Tuotteet")."</th>";
  echo "<th class='back'></th>";
  echo "</tr>";

  foreach ($tuotteet as $key => $info) {

    echo "<tr>";

    echo "<td valign='top'>";
    echo $key;
    echo "</td>";

    echo "<td valign='top'>";
    echo $info['toimittaja'];
    echo "</td>";

    echo "<td valign='top'>";
    echo $info['varasto'];
    echo "</td>";

    echo "<td valign='top'>";

    foreach ($info['tuoteinfo'] as $tuote) {
      echo $tuote['nimitys'], ' - ',  (int) $tuote['kpl'], " kpl<br>";
    }

    echo "</td>";
    echo "</tr>";

  }
  echo "</table>";

}


require "inc/footer.inc";







function toimittajat() {
  global $kukarow;

  $query = "SELECT nimi, tunnus
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $toimittajat = array();
  while ($toimittaja = mysql_fetch_assoc($result)) {
    $toimittajat[$toimittaja['tunnus']] = $toimittaja['nimi'];
  }

  return $toimittajat;
}


function varastot() {
  global $kukarow;

  $query = "SELECT
            concat(tunnus, '#', nimitark) AS varastokoodi,
            nimitys
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND nimitark != ''";
  $result = pupe_query($query);

  $varastot = array();
  while ($varasto = mysql_fetch_assoc($result)) {
    $varastot[] = $varasto;
  }

  return $varastot;
}




