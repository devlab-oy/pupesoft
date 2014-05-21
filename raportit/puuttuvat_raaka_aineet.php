<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require("../inc/parametrit.inc");
require('valmistuslinjat.inc');
require('validation/Validation.php');
require('inc/pupeExcel.inc');
require('inc/ProgressBar.class.php');

if (isset($tee) and $tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  else {
    echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
  }
  exit;
}

echo "<font class='head'>".t("Puuttuvat raaka-aineet")."</font><hr>";

$tee = isset($tee) ? $tee : '';
$alku_pp = isset($alku_pp) ? trim($alku_pp) : '';
$alku_kk = isset($alku_kk) ? trim($alku_kk) : '';
$alku_vv = isset($alku_vv) ? trim($alku_vv) : '';
$loppu_pp = isset($loppu_pp) ? trim($loppu_pp) : '';
$loppu_kk = isset($loppu_kk) ? trim($loppu_kk) : '';
$loppu_vv = isset($loppu_vv) ? trim($loppu_vv) : '';
$valmistuksen_tila = isset($valmistuksen_tila) ? trim($valmistuksen_tila) : '';
$valmistuslinja = isset($valmistuslinja) ? trim($valmistuslinja) : '';
$mul_osasto = isset($mul_osasto) ? $mul_osasto : array();
$mul_try = isset($mul_try) ? $mul_try : array();
$mul_tme = isset($mul_tme) ? $mul_tme : array();
$generoi_excel = isset($generoi_excel) ? trim($generoi_excel) : '';
$esitysmuoto = isset($esitysmuoto) ? trim($esitysmuoto) : 'KISSA';

$request = array(
  'tee'         => $tee,
  'alku_pp'       => $alku_pp,
  'alku_kk'       => $alku_kk,
  'alku_vv'       => $alku_vv,
  'loppu_pp'       => $loppu_pp,
  'loppu_kk'       => $loppu_kk,
  'loppu_vv'       => $loppu_vv,
  'alku_pvm'       => '',
  'loppu_pvm'       => '',
  'valmistuksen_tila'   => $valmistuksen_tila,
  'valmistuslinja'   => $valmistuslinja,
  'mul_osasto'     => $mul_osasto,
  'mul_try'       => $mul_try,
  'mul_tme'       => $mul_tme,
  'generoi_excel'     => $generoi_excel,
  'esitysmuoto'        => $esitysmuoto,
);

$request['valmistuslinjat'] = hae_valmistuslinjat();
$request['valmistuksien_tilat'] = hae_valmistuksien_tilat();

init($request);

$valid = validate($request);

echo_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";
if ($request['tee'] == 'ajaraportti') {
  if ($valid) {
    $request['valmistukset'] = hae_valmistukset_joissa_raaka_aine_ei_riita($request);

    if ($request['generoi_excel']) {
      $xls_filename = generoi_custom_excel($request['valmistukset'], $request['valmistuslinjat'], $request['esitysmuoto']);

      echo_tallennus_formi($xls_filename, t('Puuttuvat_raaka_aineet'));
    }
    else {
      echo_valmistukset_joissa_raaka_aine_ei_riita($request);
    }
  }
}

function hae_valmistukset_joissa_raaka_aine_ei_riita($request) {
  global $kukarow, $yhtiorow;

  $lasku_where = "";
  $valmistuksen_tila = search_array_key_for_value_recursive($request['valmistuksien_tilat'], 'value', $request['valmistuksen_tila']);
  $lasku_where .= $valmistuksen_tila[0]['query_where'];

  if (isset($request['valmistuslinja']) and $request['valmistuslinja'] != '') {
    $lasku_where .= "  AND lasku.kohde = '{$request['valmistuslinja']}'";
  }

  $tuote_join = "";
  if (!empty($request['mul_osasto'])) {
    $tuote_join .= "  AND tuote.osasto IN ('".implode("','", $request['mul_osasto'])."')";
  }

  if (!empty($request['mul_try'])) {
    $tuote_join .= "  AND tuote.try IN ('".implode("','", $request['mul_try'])."')";
  }

  if (!empty($request['mul_tme'])) {
    $tuote_join .= "  AND tuote.tuotemerkki IN ('".implode("','", $request['mul_tme'])."')";
  }

  //Haetaan valmisteet
  $query = "SELECT lasku.tunnus AS lasku_tunnus,
            tilausrivi.tunnus AS tilausrivi_tunnus,
            tilausrivi.tuoteno,
            tilausrivi.nimitys,
            tilausrivi.tyyppi,
            lasku.kohde as valmistuslinja,
            lasku.tila,
            lasku.alatila,
            lasku.kerayspvm,
            lasku.toimaika,
            (  SELECT toimi.nimi
              FROM tuotteen_toimittajat
              JOIN toimi
              ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
                AND toimi.tunnus               = tuotteen_toimittajat.liitostunnus )
              WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
              AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
              ORDER BY tuotteen_toimittajat.jarjestys ASC
              LIMIT 1
            ) AS toimittaja,
            sum(tilausrivi.varattu) AS valmistettava_kpl
            FROM lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus           = lasku.tunnus
              AND tilausrivi.tyyppi            IN ('W')
              AND tilausrivi.varattu           > 0)
            JOIN tuote
            ON ( tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno                = tilausrivi.tuoteno
              AND tuote.tuotetyyppi            not in ('A', 'B')
              AND tuote.ei_saldoa              = ''
              {$tuote_join} )
            WHERE lasku.yhtio                  = '{$kukarow['yhtio']}'
            AND lasku.toimaika BETWEEN '{$request['alku_pvm']}' AND '{$request['loppu_pvm']}'
            {$lasku_where}
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11
            ORDER BY lasku.tunnus ASC, tilausrivi.perheid ASC, tilausrivi.tyyppi DESC";
  $result = pupe_query($query);

  $valmistukset_joissa_raaka_aine_ei_riita = array();
  while ($valmiste_rivi = mysql_fetch_assoc($result)) {
    //Haetaan valmisteen raaka-aineet
    $query = "SELECT lasku.tunnus AS lasku_tunnus,
              tilausrivi.tunnus AS tilausrivi_tunnus,
              tilausrivi.tuoteno,
              tilausrivi.nimitys,
              tilausrivi.tyyppi,
              lasku.kohde as valmistuslinja,
              lasku.tila,
              lasku.alatila,
              lasku.kerayspvm,
              lasku.toimaika,
              (  SELECT toimi.nimi
                FROM tuotteen_toimittajat
                JOIN toimi
                ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
                  AND toimi.tunnus               = tuotteen_toimittajat.liitostunnus )
                WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
                ORDER BY tuotteen_toimittajat.jarjestys ASC
                LIMIT 1
              ) AS toimittaja,
              sum(tilausrivi.varattu) AS valmistettava_kpl
              FROM lasku
              JOIN tilausrivi
              ON ( tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus           = lasku.tunnus
                AND tilausrivi.tyyppi            IN ('V')
                AND tilausrivi.varattu           > 0
                AND tilausrivi.otunnus           = '{$valmiste_rivi['lasku_tunnus']}'
                AND tilausrivi.perheid           = '{$valmiste_rivi['tilausrivi_tunnus']}' )
              JOIN tuote
              ON ( tuote.yhtio = tilausrivi.yhtio
                AND tuote.tuoteno                = tilausrivi.tuoteno
                AND tuote.tuotetyyppi            not in ('A', 'B')
                AND tuote.ei_saldoa              = '' )
              WHERE lasku.yhtio                  = '{$kukarow['yhtio']}'
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11";
    $valmistus_result = pupe_query($query);

    while ($valmistus_rivi = mysql_fetch_assoc($valmistus_result)) {
      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($valmistus_rivi['tuoteno']);

      if ($saldo < $valmistus_rivi['valmistettava_kpl']) {

        $valmistus_rivi['tilattu'] = hae_tilattu_kpl($valmistus_rivi['tuoteno']);

        $valmistus_rivi['saldo'] = $saldo;
        $valmistus_rivi['hyllyssa'] = $hyllyssa;
        $valmistus_rivi['myytavissa'] = $myytavissa;

        if (empty($valmistukset_joissa_raaka_aine_ei_riita[$valmiste_rivi['lasku_tunnus']]['tilausrivit'][$valmiste_rivi['tilausrivi_tunnus']])) {
          $valmistukset_joissa_raaka_aine_ei_riita[$valmiste_rivi['lasku_tunnus']]['tilausrivit'][$valmiste_rivi['tilausrivi_tunnus']] = $valmiste_rivi;
        }

        $valmistukset_joissa_raaka_aine_ei_riita[$valmiste_rivi['lasku_tunnus']]['tilausrivit'][$valmiste_rivi['tilausrivi_tunnus']]['raaka_aineet'][] = $valmistus_rivi;
      }
    }
  }

  return $valmistukset_joissa_raaka_aine_ei_riita;
}

function hae_tilattu_kpl($tuoteno) {
  global $kukarow, $yhtiorow;

  $query = "SELECT ifnull(sum(varattu), 0) as tilattu
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tyyppi  = 'O'
            AND varattu > 0
            AND tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);

  $tilattu_row = mysql_fetch_assoc($result);

  return $tilattu_row['tilattu'];
}

function generoi_custom_excel($valmistukset, $valmistuslinjat, $esitysmuoto) {
  global $kukarow, $yhtiorow;

  if (count($valmistukset) == 0) {
    return false;
  }

  $xls_progress_bar = new ProgressBar(t("Tallennetaan exceliin"));
  $xls_progress_bar->initialize(count($valmistukset));

  $xls = new pupeExcel();
  $rivi = 0;
  $sarake = 0;
  $valmistus_headerit = array(
    'tuoteno'       => t('Valmisteen tuoteno'),
    'nimitys'       => t('Valmisteen nimitys'),
    'lasku_tunnus'     => t('Valmistusnumero'),
    'yksikko'       => t('Valmistuslinja'),
    'valmistettava_kpl'   => t('Valmistetaan kpl'),
    'ostohinta'       => t('Valmistuksen tila'),
    'kerayspvm'       => t('Keräyspäivä'),
    'toimaika'       => t('Valmistuspäivä'),
  );
  $raaka_aine_headerit = array(
    'tuoteno'     => t('Raaka-Aineen Tuoteno'),
    'nimitys'     => t('Raaka-Aineen Nimitys'),
    'kappalemaara'   => t('Valmistusnumero'),
    'yksikko'     => t('Saldo'),
    'paivitys_pvm'   => t('Hyllyssä'),
    'ostohinta'     => t('Myytävissä'),
    'kehahin'     => t('Tilattu'),
    'ryhman_ale'   => t('Toimittaja'),
  );

  $tulostettu = false;

  foreach ($valmistukset as $valmistus) {
    foreach ($valmistus['tilausrivit'] as $tilausrivi) {

      if ($esitysmuoto == 'A') {

        foreach ($valmistus_headerit as $valmistus_header) {
          $xls->write($rivi, $sarake, $valmistus_header, array('bold' => true));
          $sarake++;
        }
        $sarake = 0;
        $rivi++;

        $xls->write($rivi, $sarake, $tilausrivi['tuoteno']);
        $sarake++;
        $xls->write($rivi, $sarake, $tilausrivi['nimitys']);
        $sarake++;
        $xls->write($rivi, $sarake, $tilausrivi['lasku_tunnus']);
        $sarake++;

        $valmistuslinja = search_array_key_for_value_recursive($valmistuslinjat, 'selite', $tilausrivi['valmistuslinja']);

        $valmistuslinja = isset($valmistuslinja[0]['selitetark']) ? $valmistuslinja[0]['selitetark'] : '';

        if (empty($valmistuslinja)) {
          $xls->write($rivi, $sarake, t('Ei valmistuslinjaa'));
          $sarake++;
        }
        else {
          $xls->write($rivi, $sarake, $valmistuslinja['selitetark']);
          $sarake++;
        }

        $xls->writeNumber($rivi, $sarake, $tilausrivi['valmistettava_kpl']);
        $sarake++;

        $laskutyyppi = $tilausrivi['tila'];
        $alatila = $tilausrivi['alatila'];
        require('inc/laskutyyppi.inc');
        $xls->write($rivi, $sarake, $laskutyyppi.' '.$alatila);
        $sarake++;

        $xls->write($rivi, $sarake, date('d.m.Y', strtotime($tilausrivi['kerayspvm'])));
        $sarake++;
        $xls->write($rivi, $sarake, date('d.m.Y', strtotime($tilausrivi['toimaika'])));
        $sarake++;

        $rivi = $rivi + 2;
        $sarake = 0;
      }

      if (!$tulostettu and $esitysmuoto == 'B') {
        foreach ($raaka_aine_headerit as $raaka_aine_header) {
          $xls->write($rivi, $sarake, $raaka_aine_header, array('bold' => true));
          $sarake++;
        }
        $sarake = 0;
        $rivi++;
        $tulostettu = true;
      }

      foreach ($tilausrivi['raaka_aineet'] as $raaka_aine) {
        $xls->write($rivi, $sarake, $raaka_aine['tuoteno']);
        $sarake++;
        $xls->write($rivi, $sarake, $raaka_aine['nimitys']);
        $sarake++;
        $xls->write($rivi, $sarake, $raaka_aine['lasku_tunnus']);
        $sarake++;
        $xls->writeNumber($rivi, $sarake, $raaka_aine['saldo']);
        $sarake++;
        $xls->writeNumber($rivi, $sarake, $raaka_aine['hyllyssa']);
        $sarake++;
        $xls->writeNumber($rivi, $sarake, $raaka_aine['myytavissa']);
        $sarake++;
        $xls->writeNumber($rivi, $sarake, $raaka_aine['tilattu']);
        $sarake++;
        $xls->write($rivi, $sarake, $raaka_aine['toimittaja']);
        $sarake++;

        $rivi++;
        $sarake = 0;
      }

      $xls_progress_bar->increase();

      if ($esitysmuoto == 'A') {
        $rivi = $rivi + 2;
      }

      $sarake = 0;
    }
  }

  echo "<br/>";

  $xls_tiedosto = $xls->close();

  return $xls_tiedosto;
}

function echo_valmistukset_joissa_raaka_aine_ei_riita($request) {
  global $kukarow, $yhtiorow;

  $tulostettu = false;

  echo "<table>";

  foreach ($request['valmistukset'] as $valmistus) {

    foreach ($valmistus['tilausrivit'] as $tilausrivi) {

      if ($request['esitysmuoto'] == 'A') {
        echo "<thead>";
        echo "<tr>";
        echo "<th>".t('Valmisteen tuoteno')."</th>";
        echo "<th>".t('Valmisteen nimitys')."</th>";
        echo "<th>".t('Valmistusnumero')."</th>";
        echo "<th>".t('Valmistuslinja')."</th>";
        echo "<th>".t('Valmistetaan kpl')."</th>";
        echo "<th>".t('Valmistuksen tila')."</th>";
        echo "<th>".t('Keräyspäivä')."</th>";
        echo "<th>".t('Valmistuspäivä')."</th>";
        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";
        echo "<tr class='aktiivi'>";

        echo "<td>";
        echo $tilausrivi['tuoteno'];
        echo "</td>";

        echo "<td>";
        echo $tilausrivi['nimitys'];
        echo "</td>";

        echo "<td>";
        echo $tilausrivi['lasku_tunnus'];
        echo "</td>";

        echo "<td>";
        $valmistuslinja = search_array_key_for_value_recursive($request['valmistuslinjat'], 'selite', $tilausrivi['valmistuslinja']);
        $valmistuslinja = $valmistuslinja[0];
        if (empty($valmistuslinja)) {
          echo t('Ei valmistuslinjaa');
        }
        else {
          echo $valmistuslinja['selitetark'];
        }
        echo "</td>";

        echo "<td>";
        echo $tilausrivi['valmistettava_kpl'];
        echo "</td>";

        echo "<td>";
        $laskutyyppi = $tilausrivi['tila'];
        $alatila = $tilausrivi['alatila'];
        require('inc/laskutyyppi.inc');
        echo $laskutyyppi.' '.$alatila;
        echo "</td>";

        echo "<td>";
        echo date('d.m.Y', strtotime($tilausrivi['kerayspvm']));
        echo "</td>";

        echo "<td>";
        echo date('d.m.Y', strtotime($tilausrivi['toimaika']));
        echo "</td>";

        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='8'>";
        echo "&nbsp;";
        echo "</td>";
        echo "</tr>";

        echo "</tbody>";
      }

      if (!$tulostettu and $request['esitysmuoto'] == 'B') {
        echo "<thead>";
        echo "<tr>";
        echo "<th>".t('Raaka-aineen tuoteno')."</th>";
        echo "<th>".t('Raaka-aineen nimitys')."</th>";
        echo "<th>".t('Valmistusnumero')."</th>";
        echo "<th>".t('Saldo')."</th>";
        echo "<th>".t('Hyllyssä')."</th>";
        echo "<th>".t('Myytävissä')."</th>";
        echo "<th>".t('Tilattu')."</th>";
        echo "<th>".t('Toimittaja')."</th>";
        echo "</tr>";
        echo "</thead>";

        $tulostettu = true;
      }

      echo "<tbody>";
      foreach ($tilausrivi['raaka_aineet'] as $raaka_aine) {
        echo "<tr class='aktiivi'>";

        echo "<td>";
        echo $raaka_aine['tuoteno'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['nimitys'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['lasku_tunnus'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['saldo'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['hyllyssa'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['myytavissa'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['tilattu'];
        echo "</td>";

        echo "<td>";
        echo $raaka_aine['toimittaja'];
        echo "</td>";

        echo "</tr>";
      }

      if ($request['esitysmuoto'] == 'A') {
        echo "<tr>";
        echo "<td class='back' colspan='8'>";
        echo "&nbsp;";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='back' colspan='8'>";
        echo "&nbsp;";
        echo "</td>";
        echo "</tr>";
      }

      echo "</tbody>";
    }
  }
  echo "</table>";
}

function init(&$request) {
  global $kukarow, $yhtiorow;

  if (empty($request['alku_pp'])) {
    $request['alku_pp'] = date("d", mktime(0, 0, 0, date("m"), 1, date("Y")));
  }
  if (empty($request['alku_kk'])) {
    $request['alku_kk'] = date("m", mktime(0, 0, 0, date("m"), 1, date("Y")));
  }
  if (empty($request['alku_vv'])) {
    $request['alku_vv'] = date("Y", mktime(0, 0, 0, date("m"), 1, date("Y")));
  }

  if (empty($request['loppu_pp'])) {
    $request['loppu_pp'] = date("d", mktime(0, 0, 0, date("m")+2, 0, date("Y")));
  }
  if (empty($request['loppu_kk'])) {
    $request['loppu_kk'] = date("m", mktime(0, 0, 0, date("m")+2, 0, date("Y")));
  }
  if (empty($request['loppu_vv'])) {
    $request['loppu_vv'] = date("Y", mktime(0, 0, 0, date("m")+2, 0, date("Y")));
  }

  $request['alku_pvm'] = "{$request['alku_vv']}-{$request['alku_kk']}-{$request['alku_pp']}";
  $request['loppu_pvm'] = "{$request['loppu_vv']}-{$request['loppu_kk']}-{$request['loppu_pp']}";
}

function validate($request) {
  global $kukarow, $yhtiorow;

  $validations = array(
    'alku_pvm'   => 'paiva',
    'loppu_pvm'   => 'paiva',
  );

  $required = array(
    'alku_pvm',
    'loppu_pvm'
  );
  $validator = new FormValidator($validations, $required);
  $valid = $validator->validate($request);

  if ($valid and strtotime($request['alku_pvm']) > strtotime($request['loppu_pvm'])) {
    echo "<font class='error'>".t('Alkupäivämäärä on myöhemmin kuin loppupäivämäärä')."</font>";
    echo "<br/>";
    echo "<br/>";
    $valid = false;
  }

  if (!$valid) {
    echo $validator->getScript();
  }

  return $valid;
}

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form method='POST' action='' name='puuttuvat_raaka_aineet_form'>";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>";
  echo "<td>";
  echo "<input type='text' name='alku_pp' value='{$request['alku_pp']}' size='3'>";
  echo "<input type='text' name='alku_kk' value='{$request['alku_kk']}' size='3'>";
  echo "<input type='text' name='alku_vv' value='{$request['alku_vv']}' size='5'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>";
  echo "<td>";
  echo "<input type='text' name='loppu_pp' value='{$request['loppu_pp']}' size='3'>";
  echo "<input type='text' name='loppu_kk' value='{$request['loppu_kk']}' size='3'>";
  echo "<input type='text' name='loppu_vv' value='{$request['loppu_vv']}' size='5'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Valmistuksen tila')."</th>";
  echo "<td>";
  echo "<select name='valmistuksen_tila'>";
  foreach ($request['valmistuksien_tilat'] as $tila) {
    $sel = "";
    if ($request['valmistuksen_tila'] == $tila['value']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$tila['value']}' {$sel}>{$tila['dropdown_text']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Valmistuslinja')."</th>";
  echo "<td>";
  echo "<select name='valmistuslinja'>";
  echo "<option value=''>".t('Kaikki valmistuslinjat')."</option>";
  foreach ($request['valmistuslinjat'] as $_valmistuslinja) {
    $sel = "";
    if ($request['valmistuslinja'] == $_valmistuslinja['selite']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  $sel = array(
    'A' => $request['esitysmuoto'] == 'A' ? 'SELECTED' : '',
    'B' => $request['esitysmuoto'] == 'B' ? 'SELECTED' : '',
  );

  echo "<tr>";
  echo "<th>".t('Esitysmuoto')."</th>";
  echo "<td>";
  echo "<select name='esitysmuoto'>";
  echo "<option value='A' {$sel['A']}>".t('Näytä valmiste sekä raaka-aineet')."</option>";
  echo "<option value='B' {$sel['B']}>".t('Näytä vain raaka-aineet')."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tulosta exceliin")."</th>";
  echo "<td>";

  $chk = empty($request['generoi_excel']) ? '' : 'CHECKED';

  echo "<input type='checkbox' name='generoi_excel' {$chk}>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Rajaa tuotekategorialla")."</th>";
  echo "<td>";
  $noautosubmit = true;
  $monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
  $mul_osasto = $request['mul_osasto'];
  $mul_try = $request['mul_try'];
  $mul_tme = $request['mul_tme'];
  require ("tilauskasittely/monivalintalaatikot.inc");
  echo "</td>";
  echo "</tr>";

  echo "<tr class='back'>";
  echo "<td>";
  echo "</td>";
  echo "<td>";
  echo "<input type='hidden' value='ajaraportti' name='tee' />";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<br/>";
  echo "<input type='submit' name='submit_nappi' value='".t("Aja raportti")."'>";
  echo "</form>";
  echo "<br/>";
}

require ("inc/footer.inc");
