<?php

// Ei käytetä pakkausta
$compression = FALSE;

require "../inc/parametrit.inc";
require_once '../inc/pupeExcel.inc';
require '../inc/ProgressBar.class.php';

if ($tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "KAYTTAJAHAKU") {
  livesearch_kayttajahaku($toim);
  exit;
}

enable_ajax();

?>
<script>
  function tarkista() {
    if ($('#ppa').val() == '' || $('#kka').val() == '' || $('#vva').val() == '' || $('#ppl').val() == '' || $('#kkl').val() == '' || $('#vvl').val() == '') {
      alert($('#paivamaara_vaarin').html());
      return false;
    }

    return true;
  }
</script>
<?php

echo "<font class='head'>".t('Matkalaskuraportti')."</font><hr>";

echo "<div id='paivamaara_vaarin' style='display:none;'>".t("Antamasi päivämäärä on virheellinen")."</div>";

$request_params = array(
  "ajotapa"        => $ajotapa,
  "tuotetyypit"      => $tuotetyypit,
  "jarjestys"      => $jarjestys,
  "mul_kustp"      => $mul_kustp,
  "kenelta_kustp"    => $kenelta_kustp,
  "ruksit"        => $ruksit,
  "tuotenro"        => $tuotenro,
  "toimittajanro"    => $toimittajanro,
  "matkalaskunro"    => $matkalaskunro,
  "tuotteet_lista"    => $tuotteet_lista,
  "piilota_kappaleet"  => $piilota_kappaleet,
  "nimitykset"      => $nimitykset,
  "laskunro"        => $laskunro,
  "maksutieto"      => $maksutieto,
  "tapahtumapaiva"    => $tapahtumapaiva,
  "paivamaaravali" => $paivamaaravali,
  "ppa"          => $ppa,
  "kka"          => $kka,
  "vva"          => $vva,
  "ppl"          => $ppl,
  "kkl"          => $kkl,
  "vvl"          => $vvl,
  "tmpfilenimi"      => $tmpfilenimi,
  "kaunisnimi"      => $kaunisnimi,
  "uusi_kysely"      => $uusi_kysely,
  "hakukysely"      => $haku_kysely,
  "aja_kysely"      => $aja_kysely,
  "tallenna_muutokset" => $tallenna_muutokset,
  "poista_kysely"    => $poista_kysely,
  "debug" => 0,
);

if ($request_params['debug'] == 1) {
  echo "<pre>";
  var_dump($_REQUEST);
  echo "</pre>";
}

if ($tee == 'aja_raportti') {
  if ($request_params['uusi_kysely'] or $request_params['aja_kysely']) {
    aja_kysely();
    foreach ($request_params as $index => &$value) {
      $value = ${$index};
    }
  }

  $rivit = generoi_matkalaskuraportti_rivit($request_params);

  $naytetaanko_ruudulla = true;
  if (count($rivit) > 1000) {
    echo "<font class='message'>".t('Hakutulos oli liian suuri, ei näytetä ruudulla')."</font>";
    $naytetaanko_ruudulla = false;
  }

  if (count($rivit) > 0) {
    $header_values = array(
      'lasku_tyyppi'    => t('Laskun tyyppi'),
      'laskunro'       => t('Laskunumero'),
      'tapvm'       => t('Tapahtumapäivä'),
      'summa'       => t('Summa'),
      'nimitys'       => t('Nimitys'),
      'tuotetyyppi'     => t('Tyyppi'),
      'matkustaja_nimi'   => t('Nimi'),
      'kustp_nimi'     => t('Kustannuspaikka'),
      'tuoteno'       => t('Tuotenumero'),
      'kommentti'     => t('Kommentti'),
      'kpl'         => t('Määrä'),
      'ilmaiset_lounaat'   => t('Ilmaiset ateriat'),
      'hinta'       => t('Yksikköhinta'),
      'rivihinta'     => t('Rivihinta'),
    );
    $force_to_string = array(
      'laskunro'
    );
    $tiedosto = generoi_excel_tiedosto($rivit, $request_params, $header_values, $force_to_string);
  }

  echo_matkalaskuraportti_form($request_params);
  echo "<br/>";
  echo "<font class='message'>".t("Raportti on ajettu")."</font>";
  echo "<br/>";
  echo "<br/>";

  echo_tallennus_formi($tiedosto);

  echo "<br/>";

  if ($naytetaanko_ruudulla) {
    nayta_ruudulla($rivit, $request_params, $header_values, $force_to_string);
  }
}
else {
  echo_matkalaskuraportti_form($request_params);
}

require "../inc/footer.inc";

function generoi_matkalaskuraportti_rivit($request_params) {
  global $kukarow;

  $where           = generoi_where_ehdot($request_params);
  $select         = generoi_select($request_params);
  $group           = generoi_group_by($request_params);
  $tuote_join       = generoi_tuote_join($request_params);
  $toimi_join       = generoi_toimi_join($request_params);
  $kuka_join         = generoi_kuka_join($request_params);
  $kustannuspaikka_join   = generoi_kustannuspaikka_join($request_params);

  $query = "SELECT
            {$select}
            FROM lasku
            JOIN tilausrivi ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'M')
            JOIN tilausrivin_lisatiedot ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            JOIN tuote {$tuote_join}
            JOIN toimi {$toimi_join}
            {$kuka_join}
            LEFT JOIN kustannuspaikka {$kustannuspaikka_join}
            {$where}
            {$group}";

  if ($request_params['debug'] == 1) {
    echo "<pre>";
    var_dump($query);
    echo "</pre>";
  }

  $result = pupe_query($query);

  echo "<font class='message'>".t("Haetaan matkalaskut")."</font>";
  echo "<br/>";

  if (mysql_num_rows($result) > 0) {
    $progress_bar = new ProgressBar();
    $progress_bar->initialize(mysql_num_rows($result));
  }

  $rivit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    if (isset($rivi['tuotetyyppi'])) {
      korvaa_tuotetyyppi_selitteella($rivi);
    }
    if (isset($rivi['tila_tunnus']) and isset($rivi['alatila_tunnus'])) {
      lisaa_lasku_tyyppi($rivi);
    }
    $rivit[] = $rivi;

    if (isset($progress_bar)) {
      $progress_bar->increase();
    }
  }

  echo "<br/>";

  return $rivit;
}

function korvaa_tuotetyyppi_selitteella(&$rivi) {
  $tuotetyyppi_array = array(
    'A' => t("Päiväraha"),
    'B' => t("Muu kulu"),
  );

  if (array_key_exists($rivi['tuotetyyppi'], $tuotetyyppi_array)) {
    $rivi['tuotetyyppi'] = $tuotetyyppi_array[$rivi['tuotetyyppi']];
  }
  else {
    $rivi['tuotetyyppi'] = '';
  }
}

function lisaa_lasku_tyyppi(&$rivi) {
  if ($rivi['tila_tunnus'] == 'H' and $rivi['alatila_tunnus'] == 'M') {
    $rivi['lasku_tyyppi'] = t('Keskeneräinen');
  }
  elseif ($rivi['tila_tunnus'] == 'H' and $rivi['alatila_tunnus'] == '') {
    $rivi['lasku_tyyppi'] = t('Maksamaton');
  }
  elseif ($rivi['tila_tunnus'] == 'Y' and $rivi['alatila_tunnus'] == '') {
    $rivi['lasku_tyyppi'] = t('Maksettu');
  }
}

function generoi_where_ehdot($request_params) {
  global $kukarow;

  $where = 'WHERE ';

  if (!empty($request_params['ppa']) and !empty($request_params['kka']) and !empty($request_params['vva']) and !empty($request_params['ppl']) and !empty($request_params['kkl']) and !empty($request_params['vvl'])) {
    $where .= "lasku.yhtio = '{$kukarow['yhtio']}'
    AND (lasku.tapvm >= '{$request_params['vva']}-{$request_params['kka']}-{$request_params['ppa']}'
      AND lasku.tapvm <= '{$request_params['vvl']}-{$request_params['kkl']}-{$request_params['ppl']}') ";
  }

  if (!empty($request_params['ajotapa'])) {
    $where .= ajotapa_where($request_params);
  }

  if (!empty($request_params['matkalaskunro']) and is_numeric($request_params['matkalaskunro'])) {
    $where .= "AND lasku.laskunro IN ({$request_params['matkalaskunro']})";
  }

  return $where;
}

function ajotapa_where($request_params) {
  $where = "";
  switch ($request_params['ajotapa']) {
  case 'keskeneraiset':
    $where .= "AND lasku.tila = 'H' AND lasku.alatila = 'M'";
    break;
  case 'maksamattomat':
    $where .= "AND lasku.tila = 'H' AND lasku.alatila = ''";
    break;
  case 'maksetut':
    $where .= "AND lasku.tila = 'Y' AND lasku.alatila = ''";
    break;
  case 'keskeneraiset_maksamattomat':
    $where .= "AND (lasku.tila = 'H' AND lasku.alatila = 'M') OR (lasku.tila = 'H' AND lasku.alatila = '')";
    break;
  case 'maksamattomat_maksetut':
    $where .= "AND (lasku.tila = 'H' AND lasku.alatila = '') OR (lasku.tila = 'Y' AND lasku.alatila = '')";
    break;
  case 'kaikki':
    $where .= "AND lasku.tila IN ('H','Y','M','P','Q') AND lasku.alatila IN ('M', '')";
  }

  return $where;
}

function generoi_tuote_join($request_params) {
  $tuote_join = "ON ( tuote.yhtio = lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno ";
  if (!empty($request_params['tuotetyypit'])) {
    $tuotetyypit = implode("','", $request_params['tuotetyypit']);
    $tuote_join .= " AND tuote.tuotetyyppi IN ('{$tuotetyypit}')";
  }

  if (!empty($request_params['tuotteet_lista'])) {
    if ($tuote_lista = explode('\n', $request_params['tuotteet_lista'])) {
      $tuote_join .= " AND tuote.tuoteno IN ('".implode("', ", $tuote_lista)."')";
    }
    elseif ($tuote_lista = explode(',', $request_params['tuotteet_lista'])) {
      $tuote_join .= " AND tuote.tuoteno IN ('".implode("', ", $tuote_lista)."')";
    }
    else {
      $tuote_join .= " AND tuote.tuoteno IN ('{$request_params['tuotteet_lista']}')";
    }
  }

  if ($request_params['kenelta_kustp'] == "tuotteilta") {
    if (!empty($request_params['mul_kustp'])) {
      $tuote_join .= " AND tuote.kustp IN (".implode(',', $request_params['mul_kustp']).")";
    }
  }

  $tuote_join .= " )";
  return $tuote_join;
}

function generoi_kustannuspaikka_join($request_params) {
  $kustannuspaikka_join = "ON ( kustannuspaikka.yhtio = lasku.yhtio ";

  if ($request_params['kenelta_kustp'] == "tuotteilta") {
    $kustannuspaikka_join .= " AND kustannuspaikka.tunnus = tuote.kustp";
  }
  else {
    $kustannuspaikka_join .= "AND kustannuspaikka.tunnus = toimi.kustannuspaikka";
  }

  $kustannuspaikka_join .= " )";

  return $kustannuspaikka_join;
}

function generoi_toimi_join($request_params) {
  $toimi_join = "ON ( toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus";
  if ($request_params['kenelta_kustp'] == "toimittajilta") {
    if (!empty($request_params['mul_kustp']) and $request_params['mul_kustp'][0] != '') {
      $toimi_join .= " AND toimi.kustannuspaikka IN (".implode(',', $request_params['mul_kustp']).")";
    }
  }

  $toimi_join .= " )";

  return $toimi_join;
}

function generoi_kuka_join($request_params) {
  $kuka_join = "JOIN kuka ";
  $kuka_join .= "ON ( kuka.yhtio = lasku.yhtio AND kuka.kuka = toimi.nimi ";
  if (!empty($request_params['ruksit']['toimittajittain'])) {
    if ($request_params['toimittajanro']) {
      $kuka_join .= "AND kuka.tunnus = '{$request_params['toimittajanro']}' ";
    }
  }

  $kuka_join .= " )";

  return $kuka_join;
}

function generoi_select($request_params) {
  $mukaan_tulevat = jarjesta_prioriteetit($request_params);
  $select = "";

  if (!empty($mukaan_tulevat)) {
    //generoidaan selectit annettujen grouppauksien prioriteetin mukaan, jotta kentät ovat printtaus vaiheessa oikeassa järjestyksessä.
    foreach ($mukaan_tulevat as $group => $value) {
      $select .= select_group_byn_mukaan($request_params, $group);
    }
  }
  else {
    //kun ei haluta groupata minkään mukaan
    $select .= select_group_byn_mukaan($request_params, '');
  }

  //group: kaikki
  if (empty($request_params['piilota_kappaleet'])) {
    //jos on mikä tahansa grouppi niin tilausrivi.kpl pitää summata
    if ($request_params['ruksit']['tuotteittain']
      or $request_params['ruksit']['toimittajittain']
      or $request_params['ruksit']['matkalaskuittain']
      or $request_params['ruksit']['tuotetyypeittain']
      or $request_params['ruksit']['kustp']) {
      $select .= "sum(tilausrivi.kpl) as kpl, sum(tilausrivi.erikoisale) as ilmaiset_lounaat, avg(tilausrivi.hinta) as hinta, sum(tilausrivi.rivihinta) as rivihinta, ";
    }
    else {
      if ($request_params["paivamaaravali"]) {
        $pvm_vali_lisa =
          ",tilausrivi.kerattyaika AS Alkupvm, tilausrivi.toimitettuaika AS Loppupvm";
      }
      else {
        $pvm_vali_lisa = "";
      }

      $select .= "tilausrivi.kpl
                  {$pvm_vali_lisa},
                  tilausrivi.erikoisale AS ilmaiset_lounaat,
                  tilausrivi.hinta AS hinta,
                  tilausrivi.rivihinta AS rivihinta, ";
    }
  }

  $select = substr($select, 0, -2);

  return $select;
}

function select_group_byn_mukaan($request_params, $group) {
  $select = "";
  switch ($group) {
  case 'kustp':
    $select .= "kustannuspaikka.tunnus as kustp_tunnus, kustannuspaikka.nimi as kustp_nimi, ";
    break;
  case 'toimittajittain':
    $select .= "toimi.tunnus as matkustaja_tunnus, kuka.nimi as matkustaja_nimi, ";
    break;
  case 'tuotteittain':
    $select .= "tilausrivi.tuoteno, ";
    //tuotteiden nimityksen näytetään kun: nimitykset checked ja grouptaan tuotteittain
    if (!empty($request_params['nimitykset'])) {
      $select .= "tilausrivi.nimitys, ";
    }
    //matkalasku rivin muita tietoja, kuin kpl halutaan näyttää vain jos grouptaan tuotteittain
    $select .= "tilausrivi.var as kulu_tunnus, ";
    break;
  case 'tuotetyypeittain':
    $select .= "tuote.tuotetyyppi, ";
    break;
  case 'matkalaskuittain':
    $select .= "lasku.tunnus as lasku_tunnus, ";
    //laskunumero näytetään kun: Piilota laskunumero not checked ja groupataan laskun mukaan
    if (!empty($request_params['laskunro'])) {
      $select .= "lasku.laskunro, ";
    }
    //jos näytetään kaikki matkalaskut haetaan myös matkalaskun tyyppi
    if ($request_params['ajotapa'] == 'kaikki') {
      //tila ja alatila merkitään _tunnus, koska tällöin ne eivät printtaannu exceliin/ruudulle
      $select .= "lasku.tila as tila_tunnus, lasku.alatila as alatila_tunnus, '' as lasku_tyyppi, ";
    }
    //näytetään: kun tapahtuma päivä checked ja groupataan matkalaskuittain
    if (!empty($request_params['tapahtumapaiva'])) {
      $select .= "lasku.tapvm, ";
    }
    $select .= "lasku.summa, ";
    if (!empty($request_params['maksutieto'])) {
      $select .= "concat_ws(' ', lasku.viite, lasku.viesti) as maksutieto, ";
    }
    break;
  case 'tilausrivi_kommentti':
    $select .= "tilausrivi.tunnus as tilausrivi_tunnus, tilausrivi.kommentti, ";
    break;
  default:
    //kun ei olla groupattu millään
    $select .= "lasku.tunnus as lasku_tunnus,";
    //laskunumero näytetään kun: Piilota laskunumero not checked ja groupataan laskun mukaan
    if (!empty($request_params['laskunro'])) {
      $select .= "lasku.laskunro, ";
    }
    //jos näytetään kaikki matkalaskut haetaan myös matkalaskun tyyppi
    if ($request_params['ajotapa'] == 'kaikki') {
      //tila ja alatila merkitään _tunnus, koska tällöin ne eivät printtaannu exceliin/ruudulle
      $select .= "lasku.tila as tila_tunnus, lasku.alatila as alatila_tunnus, '' as lasku_tyyppi, ";
    }
    //näytetään: kun tapahtuma päivä checked ja groupataan matkalaskuittain
    if (!empty($request_params['tapahtumapaiva'])) {
      $select .= "lasku.tapvm, ";
    }
    $select .= "lasku.summa, ";
    if (!empty($request_params['maksutieto'])) {
      $select .= "concat_ws(' ', lasku.viite, lasku.viesti) as maksutieto, ";
    }

    $select .= "tilausrivi.tuoteno, ";
    if (!empty($request_params['nimitykset'])) {
      $select .= "tilausrivi.nimitys, ";
    }
    $select .= "tilausrivi.var as kulu_tunnus, tuote.tuotetyyppi, toimi.tunnus as matkustaja_tunnus, kuka.nimi as matkustaja_nimi, kustannuspaikka.tunnus as kustp_tunnus, kustannuspaikka.nimi as kustp_nimi, ";
    break;
  }

  return $select;
}

function generoi_group_by($request_params) {
  //selectoidaan vain valitut grouppaukset mukaan
  $group_by = "";

  $mukaan_tulevat = jarjesta_prioriteetit($request_params);

  if (!empty($mukaan_tulevat)) {
    $group_by = "GROUP BY ";
    foreach ($mukaan_tulevat as $index => $value) {
      switch ($index) {
      case 'kustp':
        $group_by .= "kustannuspaikka.tunnus, ";
        break;
      case 'toimittajittain':
        $group_by .= "toimi.tunnus, ";
        break;
      case 'tuotteittain':
        $group_by .= "tilausrivi.tuoteno, tilausrivi.var, ";
        break;
      case 'tuotetyypeittain':
        $group_by .= "tuote.tuotetyyppi, ";
        break;
      case 'matkalaskuittain':
        $group_by .= "lasku.tunnus, ";
        break;
      case 'tilausrivi_kommentti':
        $group_by .= "tilausrivi.tunnus, ";
      }
    }
    //poistetaan viimeiset 2 merkkiä ", " group by:n lopusta
    $group_by = substr($group_by, 0, -2);
  }

  return $group_by;
}

function jarjesta_prioriteetit($request_params) {
  $mukaan_tulevat = array();
  if (!empty($request_params['ruksit'])) {
    foreach ($request_params['ruksit'] as $index => $value) {
      if ($value != '') {
        $mukaan_tulevat[$index] = $request_params['jarjestys'][$index];
      }
    }
  }
  //tässä meillä on mukaan tulevat grouppaukset, nyt array pitää sortata niin, että pienin prioriteetti tulee ensimmäiseksi ja tyhjät pohjalle
  asort($mukaan_tulevat);
  //tällä saadaan tyhjät valuet arrayn pohjalle
  $mukaan_tulevat = array_diff($mukaan_tulevat, array('')) + array_intersect($mukaan_tulevat, array(''));
  /* php > $arr = array(0 => '1', 1 => '3', 2 => '2', 3 => '', 4 => '', 5 => '6'); asort($arr); $re = array_diff($arr, array('')) + array_intersect($arr, array('')); echo print_r($re);
    Array
    (
    [0] => 1
    [2] => 2
    [1] => 3
    [5] => 6
    [3] =>
    [4] =>
    )
   */

  return $mukaan_tulevat;
}

function echo_matkalaskuraportti_form($request_params) {
  global $kukarow;

  $now = date('d-m-Y');
  $last_month = date('d-m-Y', strtotime($now . '-1 month'));
  $now = explode('-', $now);
  $last_month = explode('-', $last_month);

  if ($request_params['ruksit']['tuotetyypeittain'] != '')     $ruk_tuotetyypeittain_chk  = "CHECKED";
  if ($request_params['ruksit']['tuotteittain'] != '')       $ruk_tuotteittain_chk     = "CHECKED";
  if ($request_params['ruksit']['toimittajittain'] != '')     $ruk_toimittajittain_chk     = "CHECKED";
  if ($request_params['ruksit']['matkalaskuittain'] != '')     $ruk_matkalaskuittain_chk  = "CHECKED";
  if ($request_params['ruksit']['tilausrivi_kommentti'] != '')$tilrivikommchk        = "CHECKED";
  if ($request_params['piilota_kappaleet'] != '')        $piilota_kappaleet_chk    = "CHECKED";
  if ($request_params['nimitykset'] != '')          $nimchk            = "CHECKED";
  if ($request_params['laskunro'] != '')            $laskunrochk           = "CHECKED";
  if ($request_params['maksutieto'] != '')          $maksutietochk        = "CHECKED";
  if ($request_params['tapahtumapaiva'] != '')        $tapahtumapaivachk      = "CHECKED";
  if ($request_params['paivamaaravali'] != '')        $paivamaaravalichk      = "CHECKED";

  if ($request_params['ppl'] == '')              $request_params['ppl']    = $now[0];
  if ($request_params['kkl'] == '')              $request_params['kkl']    = $now[1];
  if ($request_params['vvl'] == '')              $request_params['vvl']    = $now[2];

  if ($request_params['ppa'] == '')              $request_params['ppa']    = $last_month[0];
  if ($request_params['kka'] == '')              $request_params['kka']    = $last_month[1];
  if ($request_params['vva'] == '')              $request_params['vva']    = $last_month[2];

  if (isset($request_params['tiedosto_tyyppi'])) {
    if ($request_params['tiedosto_tyyppi'] == 'ruudulle') {
      $ruudulle = "CHECKED";
    }
    elseif ($request_params['tiedosto_tyyppi'] == 'excel') {
      $excel = "CHECKED";
    }
  }
  else {
    $ruudulle = "CHECKED";
  }

  $jarjestys['kustp'] = $request_params['jarjestys']['kustp'];
  $ruksit["kustp"] = $request_params['ruksit']['kustp'];
  //asetetaan toimittajilta default valueksi
  $kenelta_kustp = ($request_params['kenelta_kustp'] == ''? 'toimittajilta' : $request_params['kenelta_kustp']);

  $ajotavat = array(
    "kaikki" => t("Kaikki"),
    "keskeneraiset" => t("Keskeneräiset"),
    "maksamattomat" => t("Maksamattomat"),
    "maksetut" => t("Maksetut"),
    "keskeneraiset_maksamattomat" => t("Keskeneräiset ja maksamattomat"),
    "maksamattomat_maksetut" => t("Maksamattomat ja maksetut"),
  );
  $tuotetyypit = array(
    "A" => t("Päiväraha"),
    "B" => t("Muu kulu"),
  );

  echo "<form name='matkalaskuraportti' method='POST'>";
  echo "<input type='hidden' name='tee' value='aja_raportti' />";
  echo "<table id='ajotavat'>";

  echo "<tr>";
  echo "<th>".t("Valitse Ajotapa")."</th>";
  echo "<td>";
  echo "<select name='ajotapa'>";
  $sel = "";
  foreach ($ajotavat as $ajotapa_key => $ajotapa_value) {
    if ($ajotapa_key == $request_params['ajotapa']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$ajotapa_key}' $sel>{$ajotapa_value}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<br/>";

  echo "<table id='tuotetyypit'>";
  echo "<tr>";
  echo "<th>".t("Valitse tuotetyypit")."</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td>";
  echo "<select id='tuotetyypit' multiple='multiple' class='multipleselect' name='tuotetyypit[]'>";
  $sel = "";
  foreach ($tuotetyypit as $tuotetyyppi_key => $tuotetyyppi_value) {
    if (is_array($request_params['tuotetyypit']) and in_array($tuotetyyppi_key, $request_params['tuotetyypit'])) {
      $sel = "SELECTED";
    }
    echo "<option value='$tuotetyyppi_key' $sel>$tuotetyyppi_value</option>";
    $sel = "";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>";
  echo t("Prio").": <input type='text' name='jarjestys[tuotetyypeittain]' size='2' value='{$request_params['jarjestys']['tuotetyypeittain']}'> ";
  echo t("Tuotetyypeittäin")." <input type='checkbox' name='ruksit[tuotetyypeittain]' value='tuotetyypeittain' $ruk_tuotetyypeittain_chk>";
  echo "</th>";
  echo "</tr>";
  echo "</table>";

  $noautosubmit = TRUE;
  $monivalintalaatikot = array("<br>KUSTP");
  $monivalintalaatikot_normaali = array();

  require "../tilauskasittely/monivalintalaatikot.inc";

  echo "<br/><br/>";

  echo "<table id='lisarajaus'>";
  echo "<tr>
      <th>".t("Lisärajaus")."</th>
      <th>".t("Prio")."</th>
      <th> x</th>
      <th>".t("Rajaus")."</th>
    </tr>";
  echo "<tr></tr>";
  echo "<tr>
      <th>".t("Listaa tuotteittain")."</th>
      <td><input type='text' name='jarjestys[tuotteittain]' size='2' value='{$request_params['jarjestys']['tuotteittain']}'></td>
      <td><input id='tuotteittain_group' type='checkbox' name='ruksit[tuotteittain]' value='tuotteittain' {$ruk_tuotteittain_chk}></td>
      <td></td>
    </tr>";
  echo "<tr>
      <th>".t("Listaa toimittajittain")."</th>
      <td><input type='text' name='jarjestys[toimittajittain]' size='2' value='{$request_params['jarjestys']['toimittajittain']}'></td>
      <td><input type='checkbox' name='ruksit[toimittajittain]' value='toimittajittain' {$ruk_toimittajittain_chk}></td>
      <td>".livesearch_kentta("matkalaskuraportti", "KAYTTAJAHAKU", "toimittajanro", 150, '', 'EISUBMIT')."</td>
    </tr>";
  echo "<tr>
      <th>".t("Listaa matkalaskuittain")."</th>
      <td><input type='text' name='jarjestys[matkalaskuittain]' size='2' value='{$request_params['jarjestys']['matkalaskuittain']}'></td>
      <td><input type='checkbox' name='ruksit[matkalaskuittain]' value='matkalaskuittain' {$ruk_matkalaskuittain_chk}></td>
      <td><input type='text' name='matkalaskunro' value='{$request_params['matkalaskunro']}'></td>
      <td>(".t('Rajaus vain laskunumerolla').")</td>
    </tr>";
  echo "</table>";

  echo "<br/><br/>";

  echo "<table id='tuotelista'>";
  echo "<tr>
      <th valign='top'>".t("Tuotelista")."<br>(".t("Rajaa näillä tuotteilla").")</th>
      <td colspan='3'><textarea name='tuotteet_lista' rows='5' cols='35'>{$request_params['tuotteet_lista']}</textarea></td>
    </tr>";
  echo "</table>";

  echo "<br/><br/>";

  echo "<table id='naytto'>";
  echo "<tr>
      <th>".t("Piilota kappaleet")."</th>
      <td colspan='3'><input type='checkbox' name='piilota_kappaleet' {$piilota_kappaleet_chk}></td>
    </tr>";
  echo "<tr>
      <th>".t("Näytä tuotteiden nimitykset")."</th>
      <td colspan='3'><input id='nayta_tuotteiden_nimitykset'type='checkbox' name='nimitykset' {$nimchk}></td>
      <td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
    </tr>";
  echo "<tr>
      <th>".t("Näytä tilausrivin kommentti")."</th>
      <td colspan='3'><input type='checkbox' name='ruksit[tilausrivi_kommentti]' {$tilrivikommchk}></td>
      <td class='back'>".t("(Listataan kaikki rivit)")."</td>
    </tr>";
  echo "<tr>
      <th>".t("Näytä myös laskunumero")."</th>
      <td colspan='3'><input type='checkbox' name='laskunro' {$laskunrochk}></td>
      <td class='back'>".t("(Toimii vain jos listaat matkalaskuittain, tai jos et valitse mitään listausta)")."</td>
    </tr>";
  echo "<tr>
      <th>".t("Näytä myös maksuetieto")."</th>
      <td colspan='3'><input type='checkbox' name='maksutieto' {$maksutietochk}></td>
      <td class='back'>".t("(Toimii vain jos listaat matkalaskuittain, tai jos et valitse mitään listausta)")."</td>
    </tr>";
  echo "<tr>
      <th>".t("Näytä myös tapahtumapäivä")."</th>
      <td colspan='3'><input type='checkbox' name='tapahtumapaiva' {$tapahtumapaivachk}></td>
      <td class='back'>".t("(Toimii vain jos listaat matkalaskuittain, tai jos et valitse mitään listausta)")."</td>
    </tr>";
  echo "<tr>
      <th>".t("Näytä myös alku ja loppupäivämäärät")."</th>
      <td colspan='3'><input type='checkbox' name='paivamaaravali' {$paivamaaravalichk}></td>
      <td class='back'>".t("(Toimii vain jos et valitse mitään listausta)")."</td>
    </tr>";
  echo "</table>";

  echo "<br/>";

  echo "<table>";
  echo "<tr>
      <th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input id='ppa' type='text' name='ppa' value='{$request_params['ppa']}' size='3'></td>
      <td><input id='kka' type='text' name='kka' value='{$request_params['kka']}' size='3'></td>
      <td><input id='vva' type='text' name='vva' value='{$request_params['vva']}' size='5'></td>
      </tr>
      <br/>
      <tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input id='ppl' type='text' name='ppl' value='{$request_params['ppl']}' size='3'></td>
      <td><input id='kkl' type='text' name='kkl' value='{$request_params['kkl']}' size='3'></td>
      <td><input id='vvl' type='text' name='vvl' value='{$request_params['vvl']}' size='5'></td>
    </tr>
    <br/>";
  echo "</table>";
  echo "<br/>";

  echo nayta_kyselyt("matkalaskuraportti");

  echo "<br/>";
  echo "<input type='submit' name='aja_raportti' value='".t("Aja raportti")."' onclick='return tarkista();' />";
  echo "</form>";
  echo "<a name='focus_tahan' />";
  echo "<br/><br/>";
  echo "<script LANGUAGE='JavaScript'>window.location.hash=\"focus_tahan\";</script>";
}

function echo_tallennus_formi($xls_filename) {
  echo "<table>";
  echo "<tr><th>".t("Tallenna tulos").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
  echo "<input type='hidden' name='kaunisnimi' value='".t('Matkalaskuraportti').".xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='{$xls_filename}'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br/>";
}

function generoi_excel_tiedosto($rivit, $request_params, $header_values, $force_to_string) {
  $xls = new pupeExcel();
  $rivi = 0;
  $sarake = 0;

  xls_headerit($xls, $rivit, $rivi, $sarake, $header_values);

  xls_rivit($xls, $rivit, $rivi, $sarake, $force_to_string);

  $xls_tiedosto = $xls->close();

  return $xls_tiedosto;
}

function xls_headerit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake, $header_values) {
  $style = array("bold" => TRUE);
  foreach ($rivit[0] as $header_text => $value) {
    if (!stristr($header_text, 'tunnus')) {
      if (array_key_exists($header_text, $header_values)) {
        kirjoita_header_solu($xls, $header_values[$header_text], $rivi, $sarake, $style);
      }
      else {
        //fail safe
        kirjoita_header_solu($xls, $header_text, $rivi, $sarake, $style);
      }
    }
  }
  $rivi++;
  $sarake = 0;
}

function xls_rivit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake, $force_to_string) {
  echo "<br/>";
  echo "<font class='message'>".t("Generoidaan excel-tiedosto")."</font>";
  echo "<br/>";
  echo "<font class='message'>".t("Löytyi"). ' ' . count($rivit) . ' ' . t('kpl') . "</font>";
  echo "<br/>";

  if (count($rivit) > 1) {
    $xls_progress_bar = new ProgressBar();
    $xls_progress_bar->initialize(count($rivit));
  }

  foreach ($rivit as $matkalasku_rivi) {
    foreach ($matkalasku_rivi as $header => $solu) {
      if (!stristr($header, 'tunnus')) {
        kirjoita_solu($xls, $header, $solu, $rivi, $sarake, $force_to_string);
      }
    }
    $rivi++;
    $sarake = 0;

    if (isset($xls_progress_bar)) {
      $xls_progress_bar->increase();
    }
  }

  echo "<br/>";
}

function kirjoita_solu(&$xls, $key, $string, &$rivi, &$sarake, $force_to_string) {
  if (is_numeric($string) and !in_array($key, $force_to_string)) {
    $xls->writeNumber($rivi, $sarake, $string);
  }
  elseif (valid_date($string) != 0 and valid_date($string) !== false and !in_array($key, $force_to_string)) {
    $xls->writeDate($rivi, $sarake, $string);
  }
  else {
    $xls->write($rivi, $sarake, $string);
  }
  $sarake++;
}

function kirjoita_header_solu(&$xls, $string, &$rivi, &$sarake, $style = array()) {
  $xls->write($rivi, $sarake, $string, $style);
  $sarake++;
}

function valid_date($date) {
  //preg_match() returns 1 if the pattern matches given subject, 0 if it does not, or FALSE if an error occurred.
  return preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date);
}

function nayta_ruudulla($rivit, $request_params, $header_values, $force_to_string) {
  echo "<table>";
  echo_headers($rivit[0], $header_values);
  echo_rivit($rivit, $force_to_string);
  echo "</table>";
}

function echo_headers($rivi, $header_values) {
  echo "<tr>";
  foreach ($rivi as $header_text => $value) {
    if (!stristr($header_text, 'tunnus')) {
      if (array_key_exists($header_text, $header_values)) {
        echo "<th>{$header_values[$header_text]}</th>";
      }
      else {
        //fail safe
        echo "<th>{$header_text}</th>";
      }
    }
  }
  echo "</tr>";
}

function echo_rivit($rivit, $force_to_string) {
  foreach ($rivit as $rivi) {
    echo "<tr>";

    foreach ($rivi as $header => &$solu) {
      if (!stristr($header, 'tunnus')) {
        if (is_numeric($solu) and floatval($solu) and !in_array($header, $force_to_string)) {
          $solu = number_format($solu, 2, ',', ' ');
          echo "<td align='right'>{$solu}</td>";
        }
        elseif (is_numeric($solu) and $solu == 0 and !in_array($header, $force_to_string)) {
          echo "<td></td>";
        }
        else {
          echo "<td>{$solu}</td>";
        }

      }
    }
    echo "</tr>";
  }
}
