<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (strpos($_SERVER['SCRIPT_NAME'], "topten.php") !== FALSE) {
  require "../inc/parametrit.inc";
  require 'validation/Validation.php';
}
var_dump($_REQUEST );
if (!isset($aja_raportti)) {
  if (!isset($pvmvalinta)) $pvmvalinta = 'tkk';
  // Haetaan alku ja loppup‰iv‰m‰‰r‰t valinnan mukaan
  $pvmrajaus = hae_rajauksen_paivamaarat($pvmvalinta);
  foreach($pvmrajaus as $key => $value) {
     $$key = $value;
  }
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {
  require "inc/connect.inc";

  echo "<font class='head'>", t("Top 10 Raportti"), "</font><hr>";

  echo "<form method='post' action='topten.php'>";
  
  // p‰iv‰m‰‰r‰rajaus
  echo "<table>";
  echo "<tr><th>".t('Valitse p‰iv‰m‰‰r‰rajaus tai syˆt‰ k‰sin')."</th>";
  
  echo "<td>";
  echo "<select name='pvmvalinta' value='' onchange='submit();'>";

  $tvv_sel = $pvmvalinta == 'tvv' ? 'selected' : '';
  $tkk_sel = $pvmvalinta == 'tkk' ? 'selected' : '';
  $tvvv_sel = $pvmvalinta == 'tvvv' ? 'selected' : '';

  echo "<option value='tvv' $tvv_sel>".t("T‰ll‰ viikolla")."</option>";
  echo "<option value='tkk' $tkk_sel>".t("T‰ss‰ kuussa")."</option>";
  echo "<option value='tvvv' $tvvv_sel>".t("T‰n‰ vuonna")."</option>";
  echo "</select>";
  echo "</td></tr>";
  echo "<tr>
    <th>", t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)"), "</th>
    <td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
    <td><input type='text' name='kka' value='{$kka}' size='3'></td>
    <td><input type='text' name='vva' value='{$vva}' size='5'></td>
    </tr>\n
    <tr><th>", t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)"), "</th>
    <td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
    <td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
    <td><input type='text' name='vvl' value='{$vvl}' size='5'></td>
    </tr>\n";
  echo "</table><br>";

  $monivalintalaatikot = array("ASIAKASMYYJA", "ASIAKASRYHMA", "TRY");
  $monivalintalaatikot_normaali = array();

  require "tilauskasittely/monivalintalaatikot.inc";
  echo "<input type='submit' name='aja_raportti' value='", t("N‰yt‰"), "'>";
  echo "</form>";
}

if (isset($aja_raportti)) {
  $alkupvm = "$vva-$kka-$ppa";
  $loppupvm = "$vvl-$kkl-$ppl";
  #piirra_taulukko(hae_asiakasdata());
  piirra_taulukko(hae_asiakasryhmadata(' LIMIT 10 ', $mul_asiakasryhma));
  #piirra_taulukko(hae_tuotedata());
  #piirra_taulukko(hae_myyjadata());
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
echo "<tr><th colspan ='2'>".t('Yhteens‰')."</th><th>".hintapyoristys($data['yhteensa'], $yhtiorow['hintapyoristys'])."</th></tr>";
echo "</table>";

}

function hae_asiakasryhmadata($limitti, $rajaus) {
  global $kukarow, $yhtiorow, $alkupvm, $loppupvm;

  $haettu_data = array(
    'otsikko' => t('Asiakasryhm‰t')
  );

  $ryhmarajaus = '';
  if (!empty($rajaus) and count($rajaus) > 0) {
    $ryhmarajaus = " AND asiakas.ryhma IN (";
    foreach ($rajaus as $value) {
      $ryhmarajaus .= "'{$value}',";
    }
    $ryhmarajaus = substr($ryhmarajaus, 0, -1);
    $ryhmarajaus .= ") ";
  }

  $query = "SELECT asiakas.ryhma 'asiakasryhma',
            group_concat(DISTINCT asiakas.tunnus) 'asiakaslista',
            sum(if(tilausrivi.laskutettuaika >= '{$alkupvm}' and tilausrivi.laskutettuaika <= '{$loppupvm}', tilausrivi.rivihinta, 0)) myyntinyt,
            sum(if(tilausrivi.laskutettuaika >= '{$alkupvm}' and tilausrivi.laskutettuaika <= '{$loppupvm}', tilausrivi.kpl, 0)) myykplnyt
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
            JOIN tilausrivi use index (uusiotunnus_index) ON (tilausrivi.yhtio=lasku.yhtio
              AND tilausrivi.uusiotunnus=lasku.tunnus AND tilausrivi.tyyppi='L')
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio AND tuote.tuoteno=tilausrivi.tuoteno)
            JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.myynninseuranta = '' {$ryhmarajaus})
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila in ('U')
            AND lasku.alatila='X'
            AND lasku.tapvm >= '{$alkupvm}'
            AND lasku.tapvm <= '{$loppupvm}'
            AND tuote.myynninseuranta = ''
            AND tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
            GROUP BY asiakas.ryhma
            ORDER BY myyntinyt DESC
            {$limitti}";

  $result = pupe_query($query);
  $summayhteensa = 0;

  //  Haetaan rivit
  while ($row = mysql_fetch_assoc($result)) {
    $osre = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite  = '{$row['asiakasryhma']}'");
    $osrow = mysql_fetch_assoc($osre);

    if ($osrow['selitetark'] == "") {
      $osrow['selitetark'] = t("Ei asiakasryhm‰‰");
    }
    $row['nimi'] = $osrow['selitetark'];
    $haettu_data['rivit'][] = $row;
    $summayhteensa += $row['myyntinyt'];
  }

  $haettu_data['yhteensa'] = $summayhteensa;
  return $haettu_data;
}
