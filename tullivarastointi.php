<?php
require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

$errors = array();

if (isset($task) and $task == 'aloita_perustus') {
  $otsikko = t("Syˆt‰ tulon tiedot");
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

  if (empty($rekisterinumero) and empty($konttinumero)) {
    $errors['rekisterinumero'] = t("Rekisterinumero tai konttinumero tarvitaan");
    $errors['konttinumero'] = t("Konttinumero tai rekisterinumero tarvitaan");
  }

  if (!empty($rekisterinumero) and !empty($konttinumero)) {
    $errors['rekisterinumero'] = t("Syˆt‰ joko rekisterinumero tai konttinumero");
    $errors['konttinumero'] = t("Syˆt‰ joko konttinumero tai rekisterinumero");
  }

  if (empty($sinettinumero)) {
    $errors['sinettinumero'] = t("Sinettinumero asiakirja puuttuu");
  }

  if (!is_numeric($tuoteryhmien_maara)) {
    $errors['tuoteryhmien_maara'] = t("Tarkista m‰‰ra");
  }
  elseif (empty($tuoteryhmien_maara)) {
   $tuoteryhmien_maara = 1;
  }

  if (count($errors) > 0) {
    $otsikko = t("Syˆt‰ tulon tiedot");
    $view = 'tulotiedot';
  }
  else {
    $otsikko = t("T‰ydenn‰ tulotiedot");
    $view = "tuotetiedot";
  }
}

if (isset($task) and $task == 'siirto') {

  if ($varastokoodi == 'ROVV') {
    $uusi_koodi = 'ROTV';
    $hylly_alku = 'XB';
  }
  elseif ($varastokoodi == 'VRP') {
    $uusi_koodi = 'RP';
    $hylly_alku = 'XD';
  }

  $query = "SELECT tunnus
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND nimitark = '{$uusi_koodi}'";
  $result = pupe_query($query);
  $uusi_varastotunnus = mysql_result($result, 0);

  $query = "SELECT tuoteno
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tulotunnus}'";
  $result = pupe_query($query);

  $tuotenumerot = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    $tuotenumerot[] = $rivi['tuoteno'];
  }

  $tuotenumerot = array_unique($tuotenumerot);

  foreach ($tuotenumerot as  $tuotenumero) {

    $query = "UPDATE tuotepaikat SET
              tuoteno = REPLACE(tuoteno, '{$varastokoodi}', '{$uusi_koodi}'),
              hyllyalue = CONCAT('{$hylly_alku}', SUBSTRING(hyllyalue, 3)),
              hyllypaikka = CONCAT('{$hylly_alku}', SUBSTRING(hyllypaikka, 3)),
              varasto = '{$uusi_varastotunnus}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    pupe_query($query);

    $query = "UPDATE tuote SET
              tuoteno = REPLACE(tuoteno, '{$varastokoodi}', '{$uusi_koodi}')
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    pupe_query($query);

  }

  $query = "UPDATE lasku SET
            asiakkaan_tilausnumero = REPLACE(asiakkaan_tilausnumero, '{$varastokoodi}', '{$uusi_koodi}'),
            varasto = '{$uusi_varastotunnus}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tulotunnus}'";
  pupe_query($query);

  $query = "UPDATE tilausrivi SET
            tuoteno = REPLACE(tuoteno, '{$varastokoodi}', '{$uusi_koodi}'),
            varasto = '{$uusi_varastotunnus}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tulotunnus}'";
  pupe_query($query);

  unset($task);
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

    //if (empty($tiedot['lisatieto'])) {
    //  $errors[$key]["lisatieto"] = t("Syˆt‰ lis‰tiedot");
    //}

  }

  if (count($errors) > 0) {
    $view = 'tuotetiedot';
    $otsikko = t("T‰ydenn‰ tulotiedot");
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

    $palat = explode('.', $tulopaiva);
    $toimaika = $palat[2] . '-' . $palat[1] . '-' . $palat[0];

    $params = array(
      'liitostunnus' => $toimrow['tunnus'],
      'nimi' => $toimrow['nimi'],
      'myytil_toimaika' => $toimaika,
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
      $lisatieto = mysql_real_escape_string($tiedot['lisatieto']);

      $query = "INSERT INTO tuote
                SET yhtio = '{$kukarow['yhtio']}',
                tuoteno = '{$uusi_tuoteno}',
                nimitys = '{$nimitys}',
                malli = '{$malli}',
                yksikko = '{$yksikko}',
                muuta = '{$lisatieto}',
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

      $query = "UPDATE tilausrivin_lisatiedot SET
                kontin_mrn = '{$edeltava_asiakirja}',
                kuljetuksen_rekno = '{$rekisterinumero}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$lisatty_tun}'";
      pupe_query($query);

    }
    header("Location: tullivarastointi.php?pe=ok&tn={$tulonumero}");
  }
}

if (!isset($task)) {
  $otsikko = t("Perustetut tulonumerot");
  $view = "perus";
}

echo "<font class='head'>{$otsikko}</font><hr><br>";

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
    $query  = "SELECT asiakkaan_tilausnumero AS tulonumero
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
      $koodin_osat = explode("-", $row['tulonumero']);
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
  <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}' />
  <input type='hidden' name='varastotunnus_ja_koodi' value='{$varastotunnus_ja_koodi}' />
  <input type='hidden' name='tuoteryhmien_maara' value='{$tuoteryhmien_maara}' />
  <input type='hidden' name='varastotunnus' value='{$varastotunnus}' />
  <input type='hidden' name='rekisterinumero' value='{$rekisterinumero}' />
  <input type='hidden' name='kontti' value='{$konttinumero}' />
  <input type='hidden' name='sinettinumero' value='{$sinettinumero}' />
  <input type='hidden' name='varastokoodi' value='{$varastokoodi}' />
  <input type='hidden' name='tulopaiva' value='{$tulopaiva}' />
  <input type='hidden' name='edeltava_asiakirja' value='{$edeltava_asiakirja}' />
  <input type='hidden' name='tulonumero' value='{$tulonumero}' />";

  if (!empty($konttinumero)) {
    $rekisteri_tai_kontti_otsikko = t("Konttinumero");
    $rekisteri_tai_kontti = $konttinumero;
  }
  else {
    $rekisteri_tai_kontti_otsikko = t("Rekisterinumero");
    $rekisteri_tai_kontti = $rekisterinumero;
  }

  echo "
  <table>
  <tr>
    <th>" . t("Toimittaja") ."</th>
    <th>" . t("Tulonumero") ."</th>
    <th>" . t("Edelt‰v‰ asiakirja") ."</th>
    <th>" . $rekisteri_tai_kontti_otsikko ."</th>
    <th>" . t("Sinettinumero") ."</th>
    <th>" . t("Tulop‰iva") ."</th>
  </tr>
  <tr>
    <td>{$toimittajat[$toimittajatunnus]}</td>
    <td>{$tulonumero}</td>
    <td>{$edeltava_asiakirja}</td>
    <td>{$rekisteri_tai_kontti}</td>
    <td>{$sinettinumero}</td>
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
    $lisatieto = $tuote[$laskuri]['lisatieto'];

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
        <td>
          <input type='text' style='width:40px;' name='tuote[{$laskuri}][maara]' value='$maara' />";

          echo "&nbsp;<select name='tuote[{$laskuri}][yksikko]' style='width:70px;'>
                  <option value='KPL'>KPL - Kappaletta</option>
                  <option value='KG'>KG - Kilogrammaa</option>
                  <option value='H'>H - Tuntia</option>
                  <option value='KM'>KM - Kilometri‰</option>
                  <option value='L'>L - Litraa</option>
                  <option value='M'>M - Metri‰</option>
                  <option value='M2'>M2 - Neliˆmetri‰</option>
                  <option value='M3'>M3 - Kuutiometri‰</option>
                  <option value='PVA'>PVA - P‰iv‰‰</option>
                </select>";


      echo "</td>
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

      <tr>
        <th>" . t("Lis‰tietoja") ."</th>
        <td><textarea name='tuote[{$laskuri}][lisatieto]'>{$lisatieto}</textarea></td>
        <td class='back error'>{$errors[$laskuri]['lisatieto']}</td>
      </tr>

    </table>";

    $laskuri++;
  }

  if ($laskuri > 1) {
    echo "<br><br><input type='submit' value='". t("Perusta tulo ja tuotteet") ."' /></form>";
  }
  else {
    echo "<br><br><input type='submit' value='". t("Perusta tulo") ."' /></form>";
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



  if (!isset($tulopaiva)) {
    $tulopaiva = date("d.m.Y", time());
  }


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
    <th>" . t("Rekisterinumero") . "</th>
    <td><input type='text' name='rekisterinumero' value='{$rekisterinumero}' /></td>
    <td class='back error'>{$errors['rekisterinumero']}</td>
  </tr>

  <tr>
    <th>" . t("Konttinumero") . "</th>
    <td><input type='text' name='konttinumero' value='{$konttinumero}' /></td>
    <td class='back error'>{$errors['konttinumero']}</td>
  </tr>

  <tr>
    <th>" . t("Sinettinumero") . "</th>
    <td><input type='text' name='sinettinumero' value='{$sinettinumero}' /></td>
    <td class='back error'>{$errors['sinettinumero']}</td>
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
    <form method='post'>
    <input type='hidden' name='task' value='aloita_perustus' />
    <input type='submit' value='". t("Perusta uusi tulonumero") . "' />
    </form><br><br>";

  $query = "SELECT
            lasku.asiakkaan_tilausnumero,
            lasku.tunnus,
            tilausrivi.tilkpl as kpl,
            concat(tilausrivi.nimitys, '&nbsp;-&nbsp;', tuote.malli) as tuote,
            tuote.muuta AS lisatieto,
            tilausrivi.tuoteno,
            tilausrivi.hyllyalue,
            tilausrivi.hyllynro,
            concat(SUBSTRING(tilausrivi.hyllyalue, 3, 5),  tilausrivi.hyllynro) AS varastopaikka,
            lasku.varasto,
            varastopaikat.nimitys AS varastonimi,
            varastopaikat.nimitark AS varastokoodi,
            lasku.nimi,
            lasku.toimaika,
            tilausrivi.tunnus AS rivitunnus
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN varastopaikat
              ON varastopaikat.yhtio = lasku.yhtio
              AND varastopaikat.tunnus = lasku.varasto
            JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE lasku.yhtio = 'rplog'
            AND viesti = 'tullivarasto'
            GROUP BY lasku.tunnus, tilausrivi.tunnus
            ORDER BY lasku.tunnus DESC";
  $result = pupe_query($query);

  $tulot = array();
  $tuotteet = array();

  while ($tulo = mysql_fetch_assoc($result)) {

    if ($tulo['varastokoodi'] == 'ROVV' or $tulo['varastokoodi'] == 'VRP') {
        $varastotyyppi = 'v‰liaikainen';
    }
    else {
      $varastotyyppi = 'normaali';
    }

    $tuoteinfo = array(
      'rivitunnus' => $tulo['rivitunnus'],
      'lisatieto' => $tulo['lisatieto'],
      'tuoteno' => $tulo['tuoteno'],
      'tuote' => $tulo['tuote'],
      'kpl' => $tulo['kpl'],
      'vp' => $tulo['varastopaikka']
    );

    $tuotteet[$tulo['asiakkaan_tilausnumero']]['tuoteinfo'][] = $tuoteinfo;
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['toimittaja'] = $tulo['nimi'];
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['varastonimi'] = $tulo['varastonimi'];
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['toimaika'] = $tulo['toimaika'];
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['vt'] = $varastotyyppi;
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['tulotunnus'] = $tulo['tunnus'];
    $tuotteet[$tulo['asiakkaan_tilausnumero']]['varastokoodi'] = $tulo['varastokoodi'];
  }

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tulonumero")."</th>";
  echo "<th>".t("Toimitusp‰iv‰")."</th>";
  echo "<th>".t("Toimittaja")."</th>";
  echo "<th>".t("Varasto")."</th>";
  echo "<th>";
  echo "<div style='overflow:auto'>";
  echo "<div style='float:left; width:150px; text-align:center; border-right:1px solid white;'>";
  echo t("nimitys");
  echo '</div>';
  echo "<div style='float:left; width:60px; text-align:center; border-right:1px solid white;'>";
  echo t("Kpl.");
  echo '</div>';
  echo "<div style='float:left; width:60px; text-align:center;'>";
  echo t("Paikka");
  echo '</div>';
  echo '</div>';
  echo "</th>";
  echo "<th>".t("Status")."</th>";
  echo "<th class='back'></th>";
  echo "</tr>";


  foreach ($tuotteet as $key => $info) {

    echo "<tr>";
    echo "<td valign='top'>";
    echo $key;
    echo "</td>";

    echo "<td valign='top'>";

    $toimaika = date("d.m.Y", strtotime($info['toimaika']));

    echo $toimaika;

    if ($info['vt'] == 'v‰liaikainen') {

      echo "<br><span class='valivarastospan' style='color:red'>";

      $date1 = new DateTime($info['toimaika']);
      $date2 = new DateTime('today');
      $interval = $date1->diff($date2);

      echo (20 - $interval->days) . ' ' . t("P‰iv‰‰ j‰ljell‰");
      echo "<span>";

    }


    echo "</td>";
    echo "<td valign='top'>";
    echo $info['toimittaja'];
    echo "</td>";
    echo "<td valign='top'>";

    if ($info['vt'] == 'v‰liaikainen') {
      echo "<span class='valivarastospan' style='color:red'>";
    }

    echo $info['varastonimi'];

    if ($info['vt'] == 'v‰liaikainen') {
      echo "</span>";
    }
    echo "</td>";

    echo "<td valign='top'>";

    $statukset = array();

    $tuotemaara = count($info['tuoteinfo']);
    $ei_varastossa = 0;
    $varastossa = 0;

    foreach ($info['tuoteinfo'] as $tuote) {

      $saldot = saldo_myytavissa($tuote['tuoteno']);
      $tarjolla = $saldot[2];

      if ($tarjolla === false) {
        $statukset[$key][$tuote['rivitunnus']] = t("Ei viel‰ varastossa");
      }
      elseif ($tarjolla == 0) {
        $statukset[$key][$tuote['rivitunnus']] = t("Kaikki liitetty toimituksiin");
      }
      elseif ($tarjolla < $tuote['kpl']) {
        $statukset[$key][$tuote['rivitunnus']] = t("Osa liitetty toimituksiin");
      }
      elseif ($tarjolla == $tuote['kpl']) {
        $statukset[$key][$tuote['rivitunnus']] = t("Ei viel‰ liitetty toimituksiin");
      }
      else {
        //$statukset[$key][$tuote['rivitunnus']] = $tarjolla ." = ". $tuote['kpl'];
      }

      if (empty($tuote['vp'])) {
        $color = 'red';
        $paikka = '-';
        $ei_varastossa++;
      }
      else {
        $color = 'green';
        $paikka = $tuote['vp'];
        $varastossa++;
      }

      echo "<div style='overflow:auto; color:{$color};'>";

      echo "<div style='float:left; width:150px; text-align:center; overflow:hidden; border-right:1px solid white;'>";

      //if (!empty($tuote['lisatieto'])) {
      //  echo $tuote['lisatieto'];
      //}

      echo $tuote['tuote'];
      echo '</div>';
      echo "<div style='float:left; width:60px; text-align:center; border-right:1px solid white;'>";
      echo (int) $tuote['kpl'];
      echo '</div>';
      echo "<div style='float:left; width:60px; text-align:center;'>";
      echo $paikka;
      echo '</div>';
      echo '</div>';

    }

    echo "</td>";
    echo "<td valign='top'>";

    if ($info['vt'] == 'v‰liaikainen') {

      $kaikki_varastossa = false;

      if ($varastossa == $tuotemaara) {
        $status = t("V‰liaikaisvarastoitu");
        $kaikki_varastossa = true;
      }
      elseif ($ei_varastossa == $tuotemaara){
        $status = t("Ei viel‰ v‰liaikaisvarastoitu");
      }
      else {
        $status = t("Osittain v‰liaikaisvarastoitu");
      }

      echo $status;

    }
    else{
      foreach ($statukset as $tulo => $tuotteet) {
        foreach ($tuotteet as $tuoteno => $status) {
          echo $status . '<br>';
        }
      }
    }

    echo "</td>";
    echo "<td class='back' valign='top'>";

    if ($info['vt'] == 'v‰liaikainen' and $kaikki_varastossa) {

      echo "
        <form method='post'>
        <input type='hidden' name='task' value='siirto' />
        <input type='hidden' name='varastokoodi' value='{$info['varastokoodi']}'>
        <input type='hidden' name='tulotunnus' value='{$info['tulotunnus']}'>
        <input type='submit' value='" . t("Siirr‰ tullivarastoon") . "'/>
        </form><br><br>";
    }
    echo "</td>";
    echo "</tr>";
  }
  echo "</table>";
}

require "inc/footer.inc";
