<?php

if (strpos($_SERVER['SCRIPT_NAME'], "topten.php") !== FALSE) {
  require "../inc/parametrit.inc";
  require 'validation/Validation.php';
  require "inc/connect.inc";
}

if (!isset($limitit)) $limitit = array();

if (!isset($aja_raportti)) {
  if (!isset($pvmvalinta)) $pvmvalinta = 'tkk';
  // Haetaan alku ja loppup�iv�m��r�t valinnan mukaan
  $pvmrajaus = hae_rajauksen_paivamaarat($pvmvalinta);
  foreach($pvmrajaus as $key => $value) {
     $$key = $value;
  }
}

echo "<font class='head'>", t("Top 10 Raportti"), "</font><hr>";

echo "<form method='post' action='topten.php'>";

// p�iv�m��r�rajaus
echo "<table>";
echo "<tr><th>".t('Valitse p�iv�m��r�rajaus tai sy�t� k�sin')."</th>";

echo "<td>";
echo "<select name='pvmvalinta' value='' onchange='submit();'>";

$tvv_sel = $pvmvalinta == 'tvv' ? 'selected' : '';
$tkk_sel = $pvmvalinta == 'tkk' ? 'selected' : '';
$tvvv_sel = $pvmvalinta == 'tvvv' ? 'selected' : '';

echo "<option value='tvv' $tvv_sel>".t("T�ll� viikolla")."</option>";
echo "<option value='tkk' $tkk_sel>".t("T�ss� kuussa")."</option>";
echo "<option value='tvvv' $tvvv_sel>".t("T�n� vuonna")."</option>";
echo "</select>";
echo "</td></tr>";
echo "<tr>
      <th>", t("Alkup�iv�m��r� (pp-kk-vvvv)"), "</th>
      <td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
      <td><input type='text' name='kka' value='{$kka}' size='3'></td>
      <td><input type='text' name='vva' value='{$vva}' size='5'></td>
      </tr>\n
      <tr><th>", t("Loppup�iv�m��r� (pp-kk-vvvv)"), "</th>
      <td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
      <td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
      <td><input type='text' name='vvl' value='{$vvl}' size='5'></td>
      </tr>\n";
echo "</table><br>";

$as_sel = in_array('asiakas', $limitit) ? 'checked' : '';
$tuo_sel = in_array('tuote', $limitit)  ? 'checked' : '';
$asry_sel = in_array('asiakasryhma', $limitit) ? 'checked' : '';
$asmy_sel = in_array('asiakasmyyja', $limitit) ? 'checked' : '';

echo "<table>";
echo "<tr><th>".t('N�yt� kaikki')."</th>";
echo "<td>";
echo t("Tuotteet");
echo "<input type='checkbox' name='limitit[]' value = 'tuote' $tuo_sel>";
echo t("Asiakkaat");
echo "<input type='checkbox' name='limitit[]' value = 'asiakas' $as_sel>";
echo t("Myyj�t");
echo "<input type='checkbox' name='limitit[]' value = 'asiakasmyyja' $asmy_sel>";
echo t("Asiakasryhm�t");
echo "<input type='checkbox' name='limitit[]' value = 'asiakasryhma' $asry_sel>";
echo "</td>";
echo "</tr>";
echo "</table><br>";

$monivalintalaatikot = array("ASIAKASMYYJA", "ASIAKASRYHMA", "TRY");
$monivalintalaatikot_normaali = array();

require "tilauskasittely/monivalintalaatikot.inc";
echo "<input type='submit' name='aja_raportti' value='", t("N�yt�"), "'>";
echo "</form>";

if (isset($aja_raportti) and !empty($vva) and !empty($kka) and !empty($ppa)
  and !empty($vvl) and !empty($kkl) and !empty($ppl)) {
  $alkupvm = "$vva-$kka-$ppa";
  $loppupvm = "$vvl-$kkl-$ppl";
  $rajaukset = array (
    'tuotteet' => $mul_try,
    'asiakasmyyjat' => $mul_asiakasmyyja,
    'asiakasryhmat' => $mul_asiakasryhma
  );

  echo "<table>";
  echo "<tr><td>";
  piirra_taulukko(hae_data('tuotteet', $limitit, $rajaukset));  
  echo "</td>";

  echo "<td>";
  piirra_taulukko(hae_data('asiakkaat', $limitit, $rajaukset));
  echo "</td>";

  echo "<tr><td>";
  piirra_taulukko(hae_data('asiakasmyyjat', $limitit, $rajaukset));
  echo "</td>";

  echo "<td>";
  piirra_taulukko(hae_data('asiakasryhmat', $limitit, $rajaukset));
  echo "</td></tr></table>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "topten.php") !== FALSE) {
  require "../inc/footer.inc";
}

function hae_rajauksen_paivamaarat($pvmvalinta) {
  $paivamaarat = array(
    'ppa' => '', 
    'kka' => '',
    'vva' => '',
    'ppl' => '', 
    'kkl' => '',
    'vvl' => ''
  ); 

  switch ($pvmvalinta) {
    case "tvv":
      $alku = date('d-m-Y', strtotime('last monday'));
      $loppu = date('d-m-Y', strtotime('next sunday'));
      break;
    case "tkk":
      $alku = date('d-m-Y', strtotime('first day of this month'));
      $loppu = date('d-m-Y', strtotime('last day of this month'));
      break;
    case "tvvv":
      $alku = date('d-m-Y', strtotime('Jan 1'));
      $loppu = date('d-m-Y', strtotime('Dec 31'));
      break;
  }

  $alku = explode('-', $alku);
  $loppu = explode('-', $loppu);

  $paivamaarat['ppa'] = $alku[0];
  $paivamaarat['kka'] = $alku[1];
  $paivamaarat['vva'] = $alku[2];
  $paivamaarat['ppl'] = $loppu[0];
  $paivamaarat['kkl'] = $loppu[1];
  $paivamaarat['vvl'] = $loppu[2];
  return $paivamaarat;
}

function piirra_taulukko($data) {
  global $yhtiorow;

  echo "<table>";
  echo "<tr><th>#</th><th>".t($data['otsikko'])."</th><th>".t('Laskutus')."</th></tr>";
  $jarjestys = 1;
  foreach ($data['rivit'] as $row) {
    echo "<tr>";
    echo "<td>{$jarjestys}</td>";
    echo "<td>{$row['nimi']}</td>";
    echo "<td>".hintapyoristys($row['myyntinyt'], $yhtiorow['hintapyoristys'])."</td>";
    echo "</tr>";
    $jarjestys++;
  }
  echo "<tr><th colspan ='2'>".t('Yhteens�')."</th><th>".hintapyoristys($data['yhteensa'], $yhtiorow['hintapyoristys'])."</th></tr>";
  echo "</table>";
}

function hae_data($tyyppi, $limitit, $rajaukset) {
  global $kukarow, $yhtiorow, $alkupvm, $loppupvm;

  $tuoterajaus = '';
  if (!empty($rajaukset['tuotteet']) and count($rajaukset['tuotteet']) > 0) {
    $tuoterajaus = " AND tuote.try IN (";
    foreach ($rajaukset['tuotteet'] as $value) {
      $tuoterajaus .= "'{$value}',";
    }
    $tuoterajaus = substr($tuoterajaus, 0, -1);
    $tuoterajaus .= ") ";
  }

  $ryhmarajaus = '';
  if (!empty($rajaukset['asiakasryhmat']) and count($rajaukset['asiakasryhmat']) > 0) {
    $ryhmarajaus = " AND asiakas.ryhma IN (";
    foreach ($rajaukset['asiakasryhmat'] as $value) {
      $ryhmarajaus .= "'{$value}',";
    }
    $ryhmarajaus = substr($ryhmarajaus, 0, -1);
    $ryhmarajaus .= ") ";
  }

  $myyjarajaus = '';
  if (!empty($rajaukset['asiakasmyyjat']) and count($rajaukset['asiakasmyyjat']) > 0) {
    $myyjarajaus = " AND asiakas.myyjanro IN (";
    foreach ($rajaukset['asiakasmyyjat'] as $value) {
      $myyjarajaus .= "'{$value}',";
    }
    $myyjarajaus = substr($myyjarajaus, 0, -1);
    $myyjarajaus .= ") ";
  }

  $haettu_data = array();
  $nimikentta = ' tuote.nimitys ';
  $grouppauskentta = ' tuote.tunnus ';

  switch ($tyyppi) {
    case "tuotteet":
      $haettu_data['otsikko'] = t('Tuotteet');
      $limitti = in_array('tuote', $limitit) ? '' : ' LIMIT 10 ';
      break;
    case "asiakkaat":
      $haettu_data['otsikko'] = t('Asiakkaat');
      $nimikentta = " asiakas.nimi ";
      $grouppauskentta = " asiakas.tunnus ";
      $limitti = in_array('asiakas', $limitit) ? '' : ' LIMIT 10 ';
      break;
    case "asiakasryhmat":
      $haettu_data['otsikko'] = t('Asiakasryhm�t');
      $nimikentta = " ifnull(avainsana.selitetark,'".t("Ei asiakasryhm��")."') ";
      $grouppauskentta = " asiakas.ryhma ";
      $limitti = in_array('asiakasryhma', $limitit) ? '' : ' LIMIT 10 ';
      break;
    case "asiakasmyyjat":
      $haettu_data['otsikko'] = t('Asiakasmyyj�t');
      $nimikentta = " ifnull(kuka.nimi,'".t("Ei asiakasmyyj��")."') ";
      $grouppauskentta = " asiakas.myyjanro ";
      $limitti = in_array('asiakasmyyja', $limitit) ? '' : ' LIMIT 10 ';
      break;
  }

  $query = "SELECT {$nimikentta} nimi, 
            sum(if(tilausrivi.laskutettuaika >= '{$alkupvm}' and tilausrivi.laskutettuaika <= '{$loppupvm}', tilausrivi.rivihinta, 0)) myyntinyt,
            sum(if(tilausrivi.laskutettuaika >= '{$alkupvm}' and tilausrivi.laskutettuaika <= '{$loppupvm}', tilausrivi.kpl, 0)) myykplnyt
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
            JOIN tilausrivi use index (uusiotunnus_index) ON (tilausrivi.yhtio=lasku.yhtio AND tilausrivi.uusiotunnus=lasku.tunnus AND tilausrivi.tyyppi='L' AND tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}')
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno AND tuote.myynninseuranta = '' {$tuoterajaus})
            JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.myynninseuranta = '' {$myyjarajaus} {$ryhmarajaus})
            LEFT JOIN kuka ON (kuka.myyja=asiakas.myyjanro AND kuka.yhtio=asiakas.yhtio and kuka.myyja > 0)
            LEFT JOIN avainsana ON (avainsana.yhtio = lasku.yhtio AND avainsana.laji = 'ASIAKASRYHMA' AND avainsana.selite = asiakas.ryhma)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila in ('U')
            AND lasku.alatila='X'
            AND lasku.tapvm >= '{$alkupvm}'
            AND lasku.tapvm <= '{$loppupvm}'
            GROUP BY {$grouppauskentta}
            ORDER BY myyntinyt DESC
            {$limitti}";
  $result = pupe_query($query);
  $summayhteensa = 0;

  //  Haetaan rivit
  while ($row = mysql_fetch_assoc($result)) {
    $haettu_data['rivit'][] = $row;
    $summayhteensa += $row['myyntinyt'];
  }

  $haettu_data['yhteensa'] = $summayhteensa;
  return $haettu_data;  
}
