<?php

if (isset($_POST['task']) and $_POST['task'] == 'pakkalista') {
  $no_head = "yes";
}

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (isset($task) and $task == 'pakkalista') {

  $query = "SELECT
            tuote.nimitys,
            tuote.malli,
            tuote.tuotemassa,
            tilausrivi.tilkpl,
            tilausrivin_lisatiedot.kontin_taarapaino,
            tilausrivin_lisatiedot.konttinumero,
            tilausrivin_lisatiedot.sinettinumero
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = lasku.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus = '{$toimitustunnus}'";
  $result = pupe_query($query);

  $pakkalista = array();
  $paino = 0;
  $kpl = 0;

  while ($rivi = mysql_fetch_assoc($result)) {

    $rivipaino = $rivi['tilkpl'] * $rivi['tuotemassa'];

    $pakkalista[] = $rivi['nimitys'] . " - " . $rivi['malli'] . " - " . $rivipaino . ' kg';

    $konttinumero = $rivi['konttinumero'];
    $sinettinumero = $rivi['sinettinumero'];

    $paino += $rivipaino;
    $kpl += $rivi['tilkpl'];
    $taara = $rivi['kontin_taarapaino'];
  }

  $pdf_data = array(
    'pakkalista' => $pakkalista,
    'taara' => $taara,
    'kpl' => $kpl,
    'paino' => $paino,
    'konttinumero' => $konttinumero,
    'sinettinumero' => $sinettinumero
    );

  $logo_info = pdf_logo();
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = pakkalista_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

$errors = array();

if (isset($task) and $task == 'toimitus_valmis') {

  $query = "UPDATE lasku SET
            tila = 'L',
            alatila = 'A'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimitustunnus}'";
  pupe_query($query);

unset($task);
}

if (isset($task) and $task == 'sinetoi') {

  if (empty($konttinumero)) {
    $errors['konttinumero'] = t("Syötä konttinumero");
  }

  if (empty($sinettinumero)) {
    $errors['sinettinumero'] = t("Syötä sinettinumero");
  }

  if(count($errors) == 0) {

    $query = "UPDATE lasku SET
              alatila = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$toimitustunnus}'";
    pupe_query($query);

    $query = "UPDATE tilausrivi SET
              toimitettu = '{$kukarow['kuka']}',
              toimitettuaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$toimitustunnus}'";
    pupe_query($query);

    $query = "SELECT group_concat(tunnus)
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$toimitustunnus}'";
    $result = pupe_query($query);
    $tunnukset = mysql_result($result, 0);

    $query = "UPDATE tilausrivin_lisatiedot SET
              konttinumero = '{$konttinumero}',
              sinettinumero = '{$sinettinumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tilausrivitunnus IN ({$tunnukset})";
    pupe_query($query);

    unset($task);
  }
  else{
    $task = 'konttitiedot';
  }
}

if (isset($task) and $task == 'lisaa') {

  if ($kpl >= $vapaana) {
    $kpl = $vapaana;
  }
  else  {
    $kpl = $kpl;
  }

  if ($toimitustunnus == 0) {

    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND ytunnus = '{$ytunnus}'";
    $result = pupe_query($query);
    $asiakas_id = mysql_result($result, 0);

    $kukarow['kesken'] = 0;

    require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

    $toimitustunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $query = "UPDATE lasku SET
              viesti = 'tullivarastotoimitus'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$toimitustunnus}'";
    pupe_query($query);
  }

  if (isset($tuoteno)) {

    $laskuquery = "SELECT *
                   FROM lasku
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$toimitustunnus}'";
    $laskuresult = pupe_query($laskuquery);
    $laskurow = mysql_fetch_assoc($laskuresult);

    // haetaan tuotteen tiedot
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'";
    $tuoteres = pupe_query($query);

    $trow = mysql_fetch_assoc($tuoteres);
    $toim = '';
    $hinta = 0;
    $kutsuja = '';
    $var2 = 'OK';
    $varataan_saldoa = "JOO";

    $kukarow['kesken'] = $laskurow['tunnus'];

    require "tilauskasittely/lisaarivi.inc";
  }

  $task = 'hae_tulorivit';
}


if (!isset($task)) {
  $otsikko = t("Toimitukset");
}

if (isset($task) and $task == 'konttitiedot') {
  $otsikko = t("Syötä kontin tiedot");
}

if (isset($task) and $task == 'hae_toimitusrivit') {
  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Valitse toimitus");
}

if (isset($task) and $task == 'hae_tulorivit') {
  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Lisää tavaraa");
}

if (isset($task) and $task == 'perusta') {
  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Valitse toimittaja");
}


if (isset($task) and $task == 'hae_toimitusrivit') {

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimittajatunnus}'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_assoc($result);

  $query = "SELECT
            tilausrivi.nimitys,
            tuote.malli,
            SUM(tilausrivi.tilkpl) AS kpl,
            lasku.tunnus AS toimitustunnus
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.viesti = 'tullivarastotoimitus'
            AND lasku.tila != 'D'
            AND lasku.alatila NOT IN ('D','A')
            AND lasku.ytunnus = '{$toimittaja['ytunnus']}'
            AND lasku.nimi = '{$toimittaja['nimi']}'
            GROUP BY toimitustunnus, nimitys, malli";
  $result = pupe_query($query);

  $toimitukset = array();

  while ($rivi = mysql_fetch_assoc($result)) {
  $toimitukset[$rivi['toimitustunnus']][] = $rivi;
  }
}

if (isset($task) and $task == 'hae_tulorivit') {

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimittajatunnus}'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_assoc($result);

  if (!isset($hakusana_tulonumero)) {
    $hakusana_tulonumero = '';
  }

  if (!isset($hakusana_nimitys)) {
    $hakusana_nimitys = '';
  }

  if (!isset($hakusana_malli)) {
    $hakusana_malli = '';
  }

  $hakulisa = '';

  if (!empty($hakusana_tulonumero)) {
    $hakusana_tulonumero = mysql_real_escape_string($hakusana_tulonumero);
    $hakulisa = "lasku.asiakkaan_tilausnumero LIKE '%{$hakusana_tulonumero}%'";
  }

  if (!empty($hakusana_nimitys)) {
    $hakusana_nimitys = mysql_real_escape_string($hakusana_nimitys);
    if (!empty($hakulisa)) {
      $hakulisa .= " OR ";
    }
    $hakulisa .= "tilausrivi.nimitys LIKE '%{$hakusana_nimitys}%'";
  }

  if (!empty($hakusana_malli)) {
    $hakusana_malli = mysql_real_escape_string($hakusana_malli);
    if (!empty($hakulisa)) {
      $hakulisa .= " OR ";
    }
    $hakulisa .= "tuote.malli LIKE '%{$hakusana_malli}%'";
  }

  if (!empty($hakulisa)) {
    $hakulisa = "AND (". $hakulisa .")";
  }



  $query = "SELECT tilausrivi.*,
            CONCAT(SUBSTRING(tilausrivi.hyllyalue, 2), tilausrivi.hyllynro) AS varastopaikka,
            lasku.asiakkaan_tilausnumero AS tulonumero,
            varastopaikat.nimitys AS varastonimi,
            tuote.malli,
            lasku.nimi AS toimittajanimi,
            lasku.ytunnus,
            tilausrivi.hyllyalue,
            tilausrivi.hyllynro,
            tilausrivi.hyllyvali,
            tilausrivi.hyllytaso,
            varastopaikat.tunnus AS varastotunnus
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
            AND lasku.liitostunnus = '{$toimittajatunnus}'
            AND tilausrivi.hyllyalue != ''
            AND tilausrivi.otunnus != 0
            {$hakulisa}";
  $result = pupe_query($query);

  $tulorivit = array();

  while ($rivi = mysql_fetch_assoc($result)) {

    $saldot = saldo_myytavissa($rivi['tuoteno'], '', $rivi['varastotunnus'], '', $rivi['hyllyalue'], $rivi['hyllynro'], $rivi['hyllyvali'], $rivi['hyllytaso']);

    $rivi['vapaana'] = $saldot[2];

    if ($rivi['vapaana'] > 0) {
      $tulorivit[] = $rivi;
    }
    else {

    }
  }

  $laskuquery = "SELECT *
                 FROM lasku
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND tunnus = '{$toimitustunnus}'";
  $laskuresult = pupe_query($laskuquery);
  $laskurow = mysql_fetch_assoc($laskuresult);

}

echo "<font class='head'>{$otsikko}</font><hr><br>";


if (isset($task) and ($task == 'hae_toimitusrivit' or $task == 'perusta'or $task == 'hae_tulorivit')) {

  if (empty($toimittajatunnus)) {

    if ($toimittajat = toimittajat(true)) {

      echo "<form method='post'>";
      echo "<input type='hidden' name='task' value='hae_toimitusrivit' />";
      echo "<table><tr>";
      echo "<th>" .t("Valitse toimittaja"). "</th>";
      echo "<td>";

      echo "<select name='toimittajatunnus' onchange='submit();'>
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

          echo "</select>";


      echo "</td><td class='back'></td></tr>";

      echo "</table>";
      echo "</form>";
    }
    else {

      echo "<font class='message'>";
      echo t("Ei löytynyt mitään toimitettavaa!");
      echo "</font><br>";
    }
  }
  else {

    echo "<form method='post'>";
    echo "<table><tr>";
    echo "<th>" .t("Toimittaja"). "</th>";
    echo "<td>";
    echo $toimittaja['nimi'];
    echo "</td><td class='back'>";

    if (isset($toimitukset) and count($toimitukset) > 0) {
      echo "<form method='post'>
      <input type='hidden' name='task' value='perusta' />
      <input type='submit' value='" . t("Vaihda") . "' /></form>";
    }



    echo "</td></tr>";

    if (isset($tulorivit) and count($tulorivit) > 4) {

      echo "<tr>";
      echo "<th>" .t("Tulonumero"). "</th>";
      echo "<td><input type='text' name='hakusana_tulonumero' value='{$hakusana_tulonumero}'></td>";
      echo "<td class='back'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>" .t("Nimitys"). "</th>";
      echo "<td><input type='text' name='hakusana_nimitys' value='{$hakusana_nimitys}'></td>";
      echo "<td class='back'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>" .t("Malli"). "</th>";
      echo "<td><input type='text' name='hakusana_malli' value='{$hakusana_malli}'></td>";
      echo "<td class='back'><input type='submit' value='" . t("Etsi") . "'></td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "</form>";
  }


  if (isset($toimitukset) and count($toimitukset) > 0) {

    echo "<br><font class='message'>";
    echo t("Valitse keskeneräinen toimitus tai perusta uusi.");
    echo "</font><br><br>";

    echo "<table>";
    echo "
    <tr>
      <th>" . t("Toimitusnumero") ."</th>
      <th>" . t("Tuotteet") ."</th>
      <th>" . t("Mallit") ."</th>
      <th>" . t("kpl") ."</th>
      <th class='back'></th>
    </tr>";

    foreach ($toimitukset as $tunnus => $toimitusrivit) {

      $nimitykset = '';
      $mallit = '';
      $kappaleet = '';

      foreach ($toimitusrivit as $toimitusrivi) {
        $nimitykset .= $toimitusrivi['nimitys'] . "<br>";
        $mallit .= $toimitusrivi['nimitys'] . "<br>";
        $kappaleet .= $toimitusrivi['kpl'] . "<br>";
      }

      echo "
        <tr>
        <td valign='top' align='center'>" . $tunnus ."</td>
        <td valign='top' align='center'>" . $nimitykset ."</td>
        <td valign='top' align='center'>" . $mallit ."</td>
        <td valign='top' align='center'>" . $kappaleet ."</td>
        <td  valign='bottom' class='back'>";

        echo "
          <form method='post'>
          <input type='hidden' name='task' value='lisaa' />
          <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}'>
          <input type='hidden' name='toimitustunnus' value='{$toimitusrivi['toimitustunnus']}'>
          <input type='submit' value='" . t("Valitse") . "'/>
          </form>";

        echo "
        </td>
        </tr>";
    }
    echo "<tr><td style='padding:15px;' align='center' colspan='4'>";

    echo "
      <form method='post'>
      <input type='hidden' name='task' value='lisaa' />
      <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}'>
      <input type='hidden' name='ytunnus' value='{$toimittaja['ytunnus']}'>
      <input type='hidden' name='toimitustunnus' value='0'>
      <input type='submit' value='" . t("Perusta uusi toimitus") . "'/>
      </form>";

      echo "</td><td class='back'></td></tr>";


    echo "</table>";
  }
  elseif (isset($toimittaja) and !isset($toimitustunnus)) {

    echo "<br><font class='message'>".t("Ei keskeneräisiä toimituksia.")."</font><br><br>";

    echo "
      <form method='post'>
      <input type='hidden' name='task' value='lisaa' />
      <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}'>
      <input type='hidden' name='ytunnus' value='{$toimittaja['ytunnus']}'>
      <input type='hidden' name='tulonumero' value='{$tulonumero}'>
      <input type='hidden' name='toimitustunnus' value='0'>
      <input type='submit' value='" . t("Perusta uusi toimitus") . "'/>
      </form>";

  }


  if (isset($tulorivit) and count($tulorivit) > 0) {

    echo "<hr>
    <font class='message'>". t("Tuotteet varastossa") . "</font><br>";

    echo "<table>
    <tr>
      <th>" . t("Nimitys") ."</th>
      <th>" . t("Malli") ."</th>
      <th>" . t("Tulonumero") ."</th>
      <th>" . t("Varastosaldo") ."</th>
      <th>" . t("Vapaana") ."</th>
      <th>" . t("Varasto") ."</th>
      <th>" . t("Varastopaikka") ."</th>
      <th>" . t("Tomitukseen lisääminen") ."</th>
    </tr>";

    foreach ($tulorivit as $tulorivi) {

      $nimitys = $tulorivi['nimitys'];
      $tulonumero = $tulorivi['tulonumero'];
      $varastopaikka = $tulorivi['varastopaikka'];
      $malli = $tulorivi['malli'];
      $tilkpl = (int) $tulorivi['tilkpl'];
      $ytunnus = $tulorivi['ytunnus'];
      $vapaana = $tulorivi['vapaana'];

      $tulonumero = preg_replace("/".preg_quote($hakusana_tulonumero, "/")."/i", "<span style='background: white;'>$0</span>", $tulonumero);
      $nimitys = preg_replace("/".preg_quote($hakusana_nimitys, "/")."/i", "<span style='background: white;'>$0</span>", $nimitys);
      $malli = preg_replace("/".preg_quote($hakusana_malli, "/")."/i", "<span style='background: white;'>$0</span>", $malli);

      echo "
        <tr>
        <td>" . $nimitys . "</td>
        <td>" . $malli ."</td>
        <td>" . $tulonumero ."</td>
        <td align='center'>" . $tilkpl ."</td>
        <td align='center'>" . $vapaana ."</td>
        <td>" . $tulorivi['varastonimi'] ."</td>
        <td align='center'>" . $varastopaikka ."</td>
        <td align='right'>";

        if (!empty($tulorivi['varastopaikka'])) {

          echo "
            <form method='post'>
            <input type='hidden' name='task' value='lisaa' />
            <input type='text' size='4' name='kpl' value='{$vapaana}' />&nbsp;kpl&nbsp;
            <input type='hidden' name='hakusana_nimitys' value='{$hakusana_nimitys}'>
            <input type='hidden' name='kaikki' value='{$tilkpl}'>
            <input type='hidden' name='hyllyalue' value='{$tulorivi['hyllyalue']}'>
            <input type='hidden' name='hyllynro' value='{$tulorivi['hyllynro']}'>
            <input type='hidden' name='tulonumero' value='{$tulorivi['tulonumero']}'>
            <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}'>
            <input type='hidden' name='toimitustunnus' value='{$toimitustunnus}'>
            <input type='hidden' name='ytunnus' value='{$ytunnus}'>
            <input type='hidden' name='vapaana' value='{$vapaana}'>
            <input type='hidden' name='tuoteno' value='{$tulorivi['tuoteno']}'>
            <input type='hidden' name='hakusana_tulonumero' value='{$hakusana_tulonumero}'>
            <input type='hidden' name='hakusana_malli' value='{$hakusana_malli}'>
            <input type='hidden' name='toimittaja' value='$toimittaja' />
            <input type='submit' value='" . t("Lisää") . "'/>
            </form>";

        }
      echo "</td></tr>";
    }
  echo "</table>";
  }
  elseif (isset($toimittaja) and isset($toimitustunnus)) {
    echo "<br><font class='message'>". t("Ei lisättävää tavaraa valitulta toimittajalta") . "</font><br>";
  }

  if ($laskurow and isset($tulorivit)) {

    $query = "SELECT
              SUBSTRING_INDEX(tilausrivi.tuoteno,'-',3) AS tulonumero,
              tilausrivi.nimitys,
              sum(tilausrivi.tilkpl) AS kpl,
              CONCAT(SUBSTRING(tilausrivi.hyllyalue, 3, 4), tilausrivi.hyllynro) AS varastopaikka,
              SUBSTRING(tilausrivi.hyllyalue, 1, 2) AS varastokoodi,
              tuote.malli,
              lasku.nimi AS toimittajanimi,
              lasku.ytunnus
              FROM tilausrivi
              JOIN lasku
                ON lasku.yhtio = tilausrivi.yhtio
                AND lasku.tunnus = tilausrivi.otunnus
              JOIN tuote
                ON tuote.yhtio = lasku.yhtio
                AND tuote.tuoteno = tilausrivi.tuoteno
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tunnus = '{$laskurow['tunnus']}'
              GROUP BY tilausrivi.nimitys, tuote.malli, varastopaikka, tilausrivi.tuoteno";
    $result = pupe_query($query);

    $perustettavat_rivit = array();

    while ($rivi = mysql_fetch_assoc($result)) {
      $perustettavat_rivit[] = $rivi;
    }

    if (count($perustettavat_rivit) > 0) {

      echo "<hr>
      <font class='message'>". t("Toimituksen sisältö") . "</font><br>";

      echo "
      <table>
      <tr>
        <th>" . t("Nimitys") ."</th>
        <th>" . t("Malli") ."</th>
        <th>" . t("Tulonumero") ."</th>
        <th>" . t("Kpl") ."</th>
        <th>" . t("Varasto") ."</th>
        <th>" . t("Varastopaikka") ."</th>
      </tr>";

      foreach ($perustettavat_rivit as  $perustettava_rivi) {

        $nimitys = $perustettava_rivi['nimitys'];
        $varastopaikka = $perustettava_rivi['varastopaikka'];
        $malli = $perustettava_rivi['malli'];
        $kpl = (int) $perustettava_rivi['kpl'];
        $ytunnus = $perustettava_rivi['ytunnus'];
        $tulonumero = $perustettava_rivi['tulonumero'];

        $qry = "SELECT nimitys
                FROM varastopaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND SUBSTRING(alkuhyllyalue, 1, 2) = '{$perustettava_rivi['varastokoodi']}'";
        $res = pupe_query($qry);
        $varastonimi = mysql_result($res, 0);

        echo "
        <tr>
          <td>" . $nimitys . "</td>
          <td>" . $malli ."</td>
          <td>" . $tulonumero ."</td>
          <td align='center'>" . $kpl ."</td>
          <td>" . $varastonimi ."</td>
          <td align='center'>" . $varastopaikka ."</td>
        </tr>";
      }
      echo "</table>";


      echo "<br>
        <form method='post'>
        <input type='hidden' name='task' value='toimitus_valmis' />
        <input type='hidden' name='toimitustunnus' value='{$laskurow['tunnus']}' />
        <input type='submit' value='" . t("Toimitus valmis") . "'/>
        </form>";


    }
    else {
      echo "<hr><font class='message'>". t("Lisää tavaraa toimitukseen") . "</font><br>";
    }
  }
}


if (isset($task) and $task == 'konttitiedot') {

  echo "
    <form method='post'>
    <table>
    <tr>
      <th>" . t("Asiakas") ."</th>
      <td>{$asiakas}</td>
      <td class='back error'></td>
    </tr>

    <tr>
      <th>" . t("Toimitus#") ."</th>
      <td>{$toimitustunnus}</td>
      <td class='back error'></td>
    </tr>

    <tr>
      <th>" . t("Konttinumero") ."</th>
      <td><input type='text' name='konttinumero' value='{$konttinumero}' /></td>
      <td class='back error'>{$errors['konttinumero']}</td>
    </tr>
    <tr>
      <th>" . t("Sinettinumero") ."</th>
      <td><input type='text' name='sinettinumero' value='{$sinettinumero}' /></td>
      <td class='back error'>{$errors['sinettinumero']}</td>
    </tr>";

/* ei ehkä tarvita näitä

    <tr>
      <th>" . t("Taarapaino") ." (kg)</th>
      <td><input type='text' name='taara' value='{$taara}' /></td>
      <td class='back error'>{$errors['taara']}</td>
    </tr>
    <tr>
      <th>" . t("ISO-koodi") ."</th>
      <td><input type='text' name='isokoodi' value='{$isokoodi}' /></td>
      <td class='back error'>{$errors['isokoodi']}</td>
    </tr>
    <tr>
*/


  echo "<th></th>
      <td align='right'>
      <input type='hidden' name='task' value='sinetoi' />
      <input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' />
      <input type='hidden' name='asiakas' value='{$asiakas}' />
      <input type='submit' value='". t("Sinetöi") ."' /></td>
      <td class='back'></td>
    </tr>
    </table>
    </form>";


}



if (!isset($task)) {

  $query = "SELECT
            tilausrivi.nimitys,
            tuote.malli,
            SUM(tilausrivi.tilkpl) AS kpl,
            lasku.tunnus AS toimitustunnus,
            lasku.nimi,
            lasku.liitostunnus,
            concat(lasku.tila, lasku.alatila) AS status
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.viesti = 'tullivarastotoimitus'
            AND lasku.tila != 'D'
            GROUP BY toimitustunnus, nimitys, malli
            ORDER BY toimitustunnus DESC";
  $result = pupe_query($query);

  $kaikki_toimitukset = array();
  $asiakkaat = array();
  $statukset = array();
  $toimittajat = array();

  while ($rivi = mysql_fetch_assoc($result)) {

    $kaikki_toimitukset[$rivi['toimitustunnus']][] = $rivi;
    $asiakkaat[$rivi['toimitustunnus']] = $rivi['nimi'];
    $toimittajat[$rivi['toimitustunnus']] = $rivi['liitostunnus'];

    if ($rivi['status'] == 'N') {
      $status = t('Kokoaminen kesken');
    }
    elseif ($rivi['status'] == 'LA') {
      $status = t('Valmis kerättäväksi');
    }
    elseif ($rivi['status'] == 'LC') {
      $status = t('Kerätty');
    }
    elseif ($rivi['status'] == 'LD') {
      $status = t('Toimitettu');
    }

    $statuskoodit[$rivi['toimitustunnus']] = $rivi['status'];
    $statukset[$rivi['toimitustunnus']] = $status;

  }

  echo "
    <form method='post'>
    <input type='hidden' name='task' value='perusta' />
    <input type='hidden' name='toimitustunnus' value='0'>
    <input type='submit' value='" . t("Perusta uusi toimitus") . "'/>
    </form><br><br>";



  echo "<table>";
  echo "
  <tr>
    <th>" . t("Asiakas") ."</th>
    <th>" . t("Toimitus#") ."</th>
    <th>" . t("Tuotteet") ."</th>
    <th>" . t("Mallit") ."</th>
    <th>" . t("kpl") ."</th>
    <th>" . t("Status") ."</th>
    <th class='back'></th>
  </tr>";

  foreach ($kaikki_toimitukset as $tunnus => $toimitusrivit) {

    $nimitykset = '';
    $mallit = '';
    $kappaleet = '';

    foreach ($toimitusrivit as $toimitusrivi) {
      $nimitykset .= $toimitusrivi['nimitys'] . "<br>";
      $mallit .= $toimitusrivi['malli'] . "<br>";
      $kappaleet .= $toimitusrivi['kpl'] . "<br>";
    }

    echo "
      <tr>
      <td valign='top' align='left'>" . $asiakkaat[$tunnus] ."</td>
      <td valign='top' align='center'>" . $tunnus ."</td>
      <td valign='top' align='center'>" . $nimitykset ."</td>
      <td valign='top' align='center'>" . $mallit ."</td>
      <td valign='top' align='center'>" . $kappaleet ."</td>
      <td valign='top' align='center'>" . $statukset[$tunnus] ."</td>
      <td  valign='top' class='back'>";

      if ($statuskoodit[$tunnus] == 'LC') {
        echo "
          <form method='post'>
          <input type='hidden' name='task' value='konttitiedot' />
          <input type='hidden' name='asiakas' value='{$asiakkaat[$tunnus]}'>
          <input type='hidden' name='toimitustunnus' value='{$toimitusrivi['toimitustunnus']}'>
          <input type='submit' value='" . t("Syötä konttitiedot") . "'/>
          </form>";
      }
      elseif ($statuskoodit[$tunnus] == 'N') {
        echo "
          <form method='post'>
          <input type='hidden' name='task' value='hae_tulorivit' />
          <input type='hidden' name='toimittajatunnus' value='{$toimittajat[$tunnus]}'>
          <input type='hidden' name='toimitustunnus' value='{$toimitusrivi['toimitustunnus']}'>
          <input type='submit' value='" . t("Muokkaa") . "'/>
          </form>";
      }
      elseif ($statuskoodit[$tunnus] == 'LD') {

        js_openFormInNewWindow();

        echo "<form method='post' id='hae_pakkalista{$toimitusrivi['toimitustunnus']}'>";
        echo "<input type='hidden' name='task' value='pakkalista' />";
        echo "<input type='hidden' name='tee' value='XXX' />";
        echo "<input type='hidden' name='toimitustunnus' value='{$toimitusrivi['toimitustunnus']}' />";
        echo "</form>";
        echo "<button onClick=\"js_openFormInNewWindow('hae_pakkalista{$toimitusrivi['toimitustunnus']}', 'Pakkalista'); return false;\" />";
        echo t("Pakkalista");
        echo "</button>";


      }



      echo "
      </td>
      </tr>";
  }
  echo "</table>";
}


require "inc/footer.inc";
