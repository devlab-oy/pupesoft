<?php

if (isset($_POST['toimenpide']) and $_POST['toimenpide'] == 'pakkalista') {
  $no_head = "yes";
}

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (isset($task) and $task == 'lastausraportti_pdf') {

  $pdf_data = unserialize(base64_decode($pdf_data));

  $logo_info = pdf_logo();
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = purkuraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  header("Content-Disposition:attachment;filename='lastausraportti_{$toimitustunnus}.pdf'");

  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($task) and $task == 'suorita_toimenpide') {
  $task = $toimenpide;
}

if (isset($task) and $task == 'lastausraportti') {
  $otsikko = t("Lastausraportin lataus");
  $view = 'lastausraportin_lataus';

  $lastausraportti_parametrit = purkuraportti_parametrit($toimitustunnus);
  extract($lastausraportti_parametrit);
  $pdf_data = serialize($lastausraportti_parametrit);
  $pdf_data = base64_encode($pdf_data);

}

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
  header("Content-Disposition:attachment;filename='pakkalista_{$konttinumero}.pdf'");
  echo file_get_contents($pdf_tiedosto);
  die;
}

$errors = array();

if (isset($task) and $task == 'lisaa_nimike') {

  $uusi_nimike = false;

  if (isset($delete)) {
    $tunnus = key($delete);
    pupe_query("DELETE FROM tilausrivi WHERE tunnus = '{$tunnus}'");
  }
  elseif (isset($lisaa_nimike_submit)) {
    $uusi_nimike = true;
  }
  elseif (isset($vahvista_nimike_submit)) {

    $laskuquery = "SELECT *
                   FROM lasku
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$toimitustunnus}'";
    $laskuresult = pupe_query($laskuquery);
    $laskurow = mysql_fetch_assoc($laskuresult);

    $kukarow['kesken'] = $laskurow['tunnus'];

    // haetaan tuotteen tiedot
    $tuotequery = "SELECT *
                   FROM tuote
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$lisattava_nimike}'";
    $tuoteresult = pupe_query($tuotequery);
    $trow = mysql_fetch_assoc($tuoteresult);

    $kpl = $lisattava_kpl;

    require "tilauskasittely/lisaarivi.inc";
  }

  $query = "SELECT lasku.nimi AS asiakas,
            tilausrivi.tilkpl AS kpl,
            tilausrivi.nimitys,
            tilausrivi.hinta,
            tilausrivi.tunnus,
            tuote.tuotemassa AS paino,
            tuote.yksikko,
            tuote.mallitarkenne
            FROM lasku
            LEFT JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            LEFT JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus = '{$toimitustunnus}'";
  $result = pupe_query($query);

  $nimikkeet = array();
  $nimikenimet = array();
  $tonnit = 0;

  while ($rivi = mysql_fetch_assoc($result)) {

    if ($rivi['kpl'] > 0 and $rivi['mallitarkenne'] == 'varastointinimike') {

      $rivi['info'] = $rivi['hinta'].' X '.$rivi['kpl'].' = '.$rivi['hinta'] * $rivi['kpl'].' €';

      $nimikkeet[] = $rivi;
      $nimikenimet[] = $rivi['nimitys'];
    }
    else {
      $tonnit = $tonnit + ($rivi['kpl'] * $rivi['paino']);
    }

    $asiakas = $rivi['asiakas'];
  }

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND mallitarkenne = 'varastointinimike'";
  $result = pupe_query($query);

  $lisattavat_nimikkeet = array();

  while ($nimike = mysql_fetch_assoc($result)) {
   if (!in_array($nimike['nimitys'], $nimikenimet)) {
      $lisattavat_nimikkeet[] = $nimike;
    }
  }

  $otsikko = t("Työnimikkeiden lisays");
  $view = t("nimikelisays");
}

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

    $query = "UPDATE tilausrivi SET
              toimitettu = '{$kukarow['kuka']}',
              toimitettuaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$toimitustunnus}'";
    pupe_query($query);

    $query = "UPDATE lasku SET
              alatila = 'D',
              toimaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$toimitustunnus}'";
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

    $query = "SELECT tunnus, asiakasnro
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND ytunnus = '{$ytunnus}'";
    $result = pupe_query($query);
    $asiakas_info = mysql_fetch_assoc($result);

    $asiakas_id = $asiakas_info['tunnus'];
    $toimittaja_id = $asiakas_info['asiakasnro'];

    $kukarow['kesken'] = 0;

    require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

    $toimitustunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $query = "UPDATE lasku SET
              viesti = 'tullivarastotoimitus',
              liitostunnus = '{$toimittaja_id}',
              varasto = '{$varastotunnus}'
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
  $view = t("perus");
}

if (isset($task) and $task == 'konttitiedot') {
  $otsikko = t("Syötä kontin tiedot");
  $view = t("konttitiedot");
}

if (isset($task) and $task == 'perusta') {
  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Valitse toimittaja");
  $view = t("kasittely");
}

if (isset($task) and $task == 'valitse_varasto') {

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimittajatunnus}'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_assoc($result);

  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Valitse varasto");
  $view = t("kasittely");
}

if (isset($task) and $task == 'hae_toimitusrivit') {

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimittajatunnus}'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_assoc($result);

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$varastotunnus}'";
  $result = pupe_query($query);
  $varasto = mysql_fetch_assoc($result);

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
            AND lasku.alatila NOT IN ('D','A', 'C')
            AND lasku.ytunnus = '{$toimittaja['ytunnus']}'
            AND lasku.nimi = '{$toimittaja['nimi']}'
            AND lasku.varasto = '{$varastotunnus}'
            GROUP BY toimitustunnus, nimitys, malli";
  $result = pupe_query($query);

  $toimitukset = array();

  while ($rivi = mysql_fetch_assoc($result)) {
  $toimitukset[$rivi['toimitustunnus']][] = $rivi;
  }

  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Valitse toimitus");
  $view = t("kasittely");
}

if (isset($task) and $task == 'hae_tulorivit') {

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimittajatunnus}'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_assoc($result);

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$varastotunnus}'";
  $result = pupe_query($query);
  $varasto = mysql_fetch_assoc($result);

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
    $haku = true;
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
            AND lasku.varasto = '{$varastotunnus}'
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

  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Lisää tavaraa");
  $view = t("kasittely");
}

echo "<font class='head'>{$otsikko}</font><hr><br>";

if (isset($view) and $view == 'kasittely') {

  if (empty($toimittajatunnus)) {

    if ($toimittajat = toimittajat(true)) {

      echo "<form method='post'>";
      echo "<input type='hidden' name='task' value='valitse_varasto' />";
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
  elseif (empty($varastotunnus)) {

    echo "<form method='post'>";
    echo "<input type='hidden' name='task' value='hae_toimitusrivit' />";
    echo "<input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}' />";
    echo "<table><tr>";
    echo "<th>" .t("Toimittaja"). "</th>";
    echo "<td>";
    echo $toimittaja['nimi'];
    echo "</td><td class='back'>";
    echo "<tr>";
    echo "<th>" .t("Valitse Varasto"). "</th>";
    echo "<td>";

    echo "<select name='varastotunnus' onchange='submit();'>
        <option selected disabled>" . t("Valitse varasto") ."</option>";
    echo "<option value='107'>Hanski</option>";
    echo "<option value='108'>Romppi</option>";
    echo "</select>";

    echo "</td><td class='back'></td></tr>";

    echo "</table>";
    echo "</form>";

  }
  else {

    echo "<form method='post'>";
    echo "<input type='hidden' name='task' value='lisaa' />";
    echo "<input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}' />";
    echo "<input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' />";
    echo "<input type='hidden' name='varastotunnus' value='{$varastotunnus}' />";
    echo "<table>";
    echo "<tr>";
    echo "<th>" .t("Toimittaja"). "</th>";
    echo "<td>";
    echo $toimittaja['nimi'];
    echo "</td><td class='back'>";

    if (isset($toimitukset) and count($toimitukset) > 0) {
      echo "
      <input type='submit' name='toimitus_vaihtosubmit' value='" . t("Vaihda") . "' />";
    }

    echo "</td></tr>";

    echo "<tr>";
    echo "<th>" .t("Varasto"). "</th>";
    echo "<td>";
    echo $varasto['nimitys'];
    echo "</td><td class='back'>";

    if (isset($toimitukset) and count($toimitukset) > 0) {
      echo "
      <input type='submit' name='varasto_vaihtosubmit' value='" . t("Vaihda") . "' />";
    }

    echo "</td></tr>";


    if ((isset($tulorivit) and count($tulorivit) > 4) or $haku) {

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
      echo "<td class='back'><input type='submit' name='etsisubmit' value='" . t("Etsi") . "'></td>";
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
          <input type='hidden' name='varastotunnus' value='{$varastotunnus}'>
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
      <input type='hidden' name='varastotunnus' value='{$varastotunnus}' />
      <input type='hidden' name='ytunnus' value='{$toimittaja['ytunnus']}'>
      <input type='submit' value='" . t("Perusta uusi toimitus") . "'/>
      </form>";

      echo "</td><td class='back'></td></tr>";


    echo "</table>";
  }
  elseif (isset($toimittaja) and isset($varasto) and !isset($toimitustunnus)) {

    echo "<br><font class='message'>".t("Ei keskeneräisiä toimituksia.")."</font><br><br>";

    echo "
      <form method='post'>
      <input type='hidden' name='task' value='lisaa' />
      <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}'>
      <input type='hidden' name='varastotunnus' value='{$varastotunnus}' />
      <input type='hidden' name='ytunnus' value='{$toimittaja['ytunnus']}'>
      <input type='hidden' name='tulonumero' value='{$tulonumero}'>
      <input type='submit' value='" . t("Perusta uusi toimitus") . "'/>
      </form>";

  }

  if (isset($tulorivit) and count($tulorivit) > 0) {

    echo "<br>
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
            <input type='hidden' name='varastotunnus' value='{$varastotunnus}'>
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
    echo "<br><font class='message'>". t("Ei lisättävää tavaraa valitulta toimittajalta valitusta varastosta") . "</font><br>";
  }

  if ($laskurow and isset($tulorivit)) {

    $query = "SELECT
              SUBSTRING_INDEX(tilausrivi.tuoteno,'-',3) AS tulonumero,
              tilausrivi.nimitys,
              sum(tilausrivi.tilkpl) AS kpl,
              CONCAT(SUBSTRING(tilausrivi.hyllyalue, 2), tilausrivi.hyllynro) AS varastopaikka,
              SUBSTRING(tilausrivi.hyllyalue, 1, 1) AS varastokoodi,
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

      echo "<br>
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
                AND SUBSTRING(alkuhyllyalue, 1,1) = '{$perustettava_rivi['varastokoodi']}'";
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
    elseif (isset($tulorivit)) {
      echo "<br><font class='message'>". t("Lisää tavaraa toimitukseen") . "</font><br>";
    }
  }
}


if (isset($view) and $view == 'nimikelisays') {

  echo "
  <form method='post' id=''>
  <input type='hidden' name='task' value='lisaa_nimike' />
  <input type='hidden' id='hidden_tonnit' name='tonnit' value='{$tonnit}' />
  <input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' />
  <table>
  <tr><th>" . t("Asiakas") ."</th><td align='right' style='width:350px;'>{$asiakas}</td><td class='back'></td></tr>
  <tr><th>" . t("Toimitusnumero") ."</th><td align='right'>{$toimitustunnus}</td><td class='back'></td></tr>";


  /*
  foreach ($tuotteet as $tuote) {
   echo "<tr><th>" . t("Tuote") ."</th><td align='right'>{$konttiviite}</td><td class='back'></td></tr>";
  }
  */

  if (count($nimikkeet) == 0) {
    echo "<tr><th style='text-align:center' colspan='2'>" . t("Ei lisättyjä työnimikkeitä") ."</th><td class='back'></td></tr>";
  }
  else {
    echo "<tr><th style='text-align:center' colspan='2'>" . t("Lisätyt työnimikkeet") ."</th><td class='back'></td></tr>";

    foreach ($nimikkeet as $nimike) {

      echo "<tr><th>{$nimike['nimitys']}</th>";
      echo "<td align='right'>{$nimike['info']} €</td>";
      echo "</td><td class='back'>";
      echo "<input type='submit' name='delete[{$nimike['tunnus']}]' value='" . t("Poista") . "' />";
      echo "</td></tr>";

    }
  }


  if ($uusi_nimike) {
    echo "
    <tr><th>" . t("Lisää nimike") ."</th><td align='right'><select name='lisattava_nimike' id='nimikevalinta' style='width:190px;'>";

    echo "<option value='0'>Valitse nimike</option>";

    foreach ($lisattavat_nimikkeet as $nimike) {

      switch ($nimike['yksikko']) {
        case 'KPL':
          $txt = $nimike['nimitys'] . " " . "(kpl.)";
          break;

        case 'TON':
          $txt = $nimike['nimitys'] . " " . "(t.)";
          break;

        case 'H':
          $txt = $nimike['nimitys'] . " " . "(h.)";
          break;

        case 'MET':
          $txt = $nimike['nimitys'] . " " . "(m.)";
          break;

        default:
          # code...
          break;
      }

      echo "<option value='{$nimike['tunnus']}'>{$txt}</option>";
    }

    echo "<select />&nbsp;&nbsp;&nbsp;";
    echo "<input style='visibility:hidden; width:50px;' id='kplvalinta' type='text' name='lisattava_kpl' /> <div style='display:inline-block;width:40px; text-align:center;' id='nimikeyksikko'></div>";
    echo "<span id='nimikelisaysnappi' style='visibility:hidden'><input type='submit' name='vahvista_nimike_submit' value='". t("Lisää") ."' /></span></td><td class='back'></td></tr>";
  }
  elseif (count($lisattavat_nimikkeet) > 0) {

    echo "
    <tr><th>" . t("Nimikkeen lisäys") ."</th><td align='right'><input type='submit' name='lisaa_nimike_submit' value='". t("Lisää nimike") ."' /></td><td class='back'></td></tr>";
  }




  echo "</table>";

  echo "
  <script type='text/javascript'>

    $('#nimikevalinta').bind('change',function(){

      var txt = $('#nimikevalinta option:selected').text();

      if (txt.indexOf('(t.)') >= 0) {

        var value = $('#hidden_tonnit').val();

        $('#kplvalinta').prop('readonly', true);
        $('#kplvalinta').val(value);
        $('#nimikeyksikko').text('t.');
        $('#kplvalinta').css('visibility', 'visible');
        $('#nimikelisaysnappi').css('visibility', 'visible');
      }
      else if (txt.indexOf('(kpl.)') >= 0) {

        $('#kplvalinta').prop('readonly', false);
        $('#kplvalinta').val('');
        $('#nimikeyksikko').text('kpl.');
        $('#kplvalinta').css('visibility', 'visible');
        $('#nimikelisaysnappi').css('visibility', 'visible');
      }
      else if (txt.indexOf('(h.)') >= 0) {

        $('#kplvalinta').prop('readonly', false);
        $('#kplvalinta').val('');
        $('#nimikeyksikko').text('h.');
        $('#kplvalinta').css('visibility', 'visible');
        $('#nimikelisaysnappi').css('visibility', 'visible');
      }
      else if (txt.indexOf('(m.)') >= 0) {

        $('#kplvalinta').prop('readonly', false);
        $('#kplvalinta').val('');
        $('#nimikeyksikko').text('m.');
        $('#kplvalinta').css('visibility', 'visible');
        $('#nimikelisaysnappi').css('visibility', 'visible');
      }
      else {

        $('#nimikeyksikko').text('');
        $('#kplvalinta').css('visibility', 'hidden');
        $('#nimikelisaysnappi').css('visibility', 'hidden');
      }

    });

  </script>";

}

if (isset($view) and $view == 'konttitiedot') {

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

//////////////////////////
// lastausraportin-lataus-näkymä alkaa
//////////////////////////

if (isset($view) and $view == "lastausraportin_lataus") {

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Asiakas")."</th>";
  echo "<th>".t("Toimitusnumero")."</th>";
  echo "<th>".t("Lastauspäivä")."</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td>{$toimittaja}</td>";
  echo "<td>{$toimitustunnus}</td>";
  echo "<td>{$saapumispaiva}</td>";
  echo "</tr>";
  echo "</table>";

  echo '<br>';

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Nimitys")."</th>";
  echo "<th>".t("Malli")."</th>";
  echo "<th>".t("kpl.")."</th>";
  echo "<th>".t("Lastausaika")."</th>";
  echo "<th class='back'></th>";
  echo "</tr>";

  foreach ($puretut_tuotteet as $tuote) {

    echo "<tr>";
    echo "<td>";
    echo $tuote['nimitys'];
    echo "</td>";
    echo "<td>";
    echo $tuote['malli'];
    echo "</td>";
    echo "<td>";
    echo $tuote['kpl'];
    echo "</td>";
    echo "<td>";
    echo $tuote['purkuaika'];
    echo "</td>";
    echo "</tr>";

  }

  echo "</table>";
  echo '<br>';

  echo "
    <form method='post' class='multisubmit' action='tullivarastointi_toimitus.php'>
    <input type='hidden' name='task' value='lastausraportti_pdf' />
    <input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' />
    <input type='hidden' name='pdf_data' value='{$pdf_data}' />
    <input type='submit' value='" . t("Lataa PDF") . "' />
    </form>";

}


if (isset($view) and $view == 'perus') {

  $toimitukset = toimitukset();

  echo "
    <form method='post'>
    <input type='hidden' name='task' value='perusta' />
    <input type='submit' value='" . t("Perusta uusi toimitus") . "'/>
    </form><br><br>";

  if ($toimitukset) {

    echo "<table>";
    echo "
    <tr>
      <th>" . t("Asiakas") ."</th>
      <th>" . t("Toimitus#") ."</th>
      <th>" . t("Tuotetiedot") ."</th>
      <th>" . t("Status") ."</th>
      <th>" . t("Toimenpiteet") ."</th>
      <th class='back'></th>
    </tr>";



    foreach ($toimitukset as $tunnus => $toimitustiedot) {

      echo "
        <tr>
        <td valign='top'>" . $toimitustiedot['asiakas'] ."</td>
        <td valign='top' align='center'>" . $tunnus ."</td>
        <td valign='top'>" . $toimitustiedot['tuotetiedot'] ."</td>
        <td valign='top'>" . $toimitustiedot['status'] ."</td>";

        echo "<td valign='top'>";


        if ($toimenpiteet = toimitustoimenpiteet($tunnus, $toimitustiedot['statuskoodi'])) {

          echo "<form action='tullivarastointi_toimitus.php' class='multisubmit' method='post'>";
          echo "<input type='hidden' name='task' value='suorita_toimenpide' />";
          echo "<input type='hidden' name='asiakas' value='{$toimitustiedot['asiakas']}' />";
          echo "<input type='hidden' name='toimittajatunnus' value='{$toimitustiedot['toimittajatunnus']}' />";
          echo "<input type='hidden' name='toimitustunnus' value='{$tunnus}' />";
          echo "<select name='toimenpide' id='{$tunnus}' class='tpselect' style='width:100px;'>";

          echo "<option value='.' selected disabled>". t("Valitse") ."</option>";

          foreach ($toimenpiteet as $koodi => $teksti) {
            echo "<option value='{$koodi}'>{$teksti}</option>";
          }

          echo "</select>";
          echo "&nbsp;";
          echo "<input id='{$tunnus}_nappi' class='nappi' disabled type='submit' value='" . t("Suorita") . "'/>";
          echo "</form>";
        }
        else {

        }

        echo "
        </td>
        </tr>";
    }
    echo "</table>";

    echo "
    <script type='text/javascript'>

      $('.tpselect').change(function() {
        var tunnus = $(this).attr('id');
        var valittu = $(this).val();
        var nappitunnus = tunnus+'_nappi';
        $('.nappi').prop('disabled', true);
        $('.tpselect').val('.');
        $(this).val(valittu);
        $('#'+nappitunnus).prop('disabled', false);
      });

    </script>";

    echo "</td>
    <td  valign='top' class='back'>";
  }
  else {

    echo "<font class='message'>" . t("Ei perustettuja toimituksia") . "</font><br>";
  }
}


require "inc/footer.inc";
