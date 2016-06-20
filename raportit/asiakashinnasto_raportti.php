<?php

// Ei käytetä pakkausta
$compression = FALSE;

$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require "../inc/parametrit.inc";
require "inc/pupeExcel.inc";
require 'inc/ProgressBar.class.php';

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

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

enable_ajax();

echo "<font class='head'>".t("Asiakashinnasto raportti")."</font><hr>";
?>
<style>

</style>
<script>

</script>
<?php

$request = array(
  'valittu_asiakas'     => isset($valittu_asiakas) ? $valittu_asiakas : '',
  'valittu_asiakasryhma'   => isset($valittu_asiakasryhma) ? $valittu_asiakasryhma : '',
  'mitka_tuotteet'     => isset($mitka_tuotteet) ? $mitka_tuotteet : '',
  'action'         => isset($action) ? $action : '',
  'nayta_poistetut'     => isset($nayta_poistetut) ? $nayta_poistetut : '',
);

$request['asiakasryhmat'] = hae_asiakasryhmat();
$request['aleryhmat'] = hae_aleryhmat();

$valid = true;
if (!empty($request['valittu_asiakas']) and !is_numeric($request['valittu_asiakas'])) {
  echo "<font class='error'>".t('Käytä livesearch toiminnallisuutta')."</font>";
  echo "<br/>";
  $valid = false;
}

if ($request['action'] == 'aja_raportti' and $valid) {
  echo "<font class='message'>".t("Raporttia ajetaan")."</font>";
  echo "<br/>";

  $html = ob_get_clean();
  echo $html;

  $request['tuotteet'] = hae_tuotteet_joilla_on_asiakashinta_tai_hae_kaikki_tuotteet($request);

  $tuotteet = hae_asiakasalet($request);

  $xls_tiedosto = generoi_custom_excel($tuotteet);

  if (!empty($xls_tiedosto)) {
    echo_tallennus_formi($xls_tiedosto, t('Asiakashinnasto_raportti'));
    echo "<br/>";
  }
  else {
    echo t('Asiakashinnaston tuotteita ei löytynyt');
    echo "<br/>";
  }
}

echo_kayttoliittyma($request);

require "inc/footer.inc";

function echo_kayttoliittyma($request = array()) {
  global $kukarow, $yhtiorow;

  echo "<form action='' method='POST' name='asiakashinnasto_haku_form'>";

  echo "<input type='hidden' name='action' value='aja_raportti' />";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Asiakas').":</th>";
  echo "<td>";
  echo livesearch_kentta("asiakashinnasto_haku_form", "ASIAKASHAKU", "valittu_asiakas", 315, $request['valittu_asiakas'], 'EISUBMIT', '', 'valittu_asiakas', 'ei_break_all');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Asiakasryhmä').":</th>";
  echo "<td>";
  echo "<select id='valittu_asiakasryhma' name='valittu_asiakasryhma'>";
  foreach ($request['asiakasryhmat'] as $asiakasryhma) {
    $sel = "";
    //absoluuttinen vertaus tarvitaan, koska asiakasryhmän tunnuksessa voi olla leading zero 005023 == 5023
    if ($request['valittu_asiakasryhma'] === $asiakasryhma['selite']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$asiakasryhma['selite']}' {$sel}>{$asiakasryhma['selite']} - {$asiakasryhma['selitetark']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";

  $sel = array(
    'kaikki'             => $request['mitka_tuotteet'] == 'kaikki' ? 'CHECKED' : '',
    'tuotteet_joilla_asiakashinta'   => $request['mitka_tuotteet'] == 'tuotteet_joilla_asiakashinta' ? 'CHECKED' : '',
  );
  if (empty($request['mitka_tuotteet'])) {
    $sel['tuotteet_joilla_asiakashinta'] = "CHECKED";
  }
  echo "<th>".t('Tuotteet').":</th>";

  echo "<td>";
  echo "<input type='radio' {$sel['kaikki']} name='mitka_tuotteet' value='kaikki' />";
  echo t('Kaikki tuotteet');
  echo "<br/>";
  echo "<br/>";
  echo "<input type='radio' {$sel['tuotteet_joilla_asiakashinta']} name='mitka_tuotteet' value='tuotteet_joilla_asiakashinta' />";
  echo t('Tuotteet joilla on asiakashinta');
  echo "</td>";

  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Listaa myös poistetut tuotteet')."</th>";
  echo "<td>";
  $chk = "";
  if (!empty($request['nayta_poistetut'])) {
    $chk = "CHECKED";
  }
  echo "<input type='checkbox' name='nayta_poistetut' {$chk}/>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='".t('Hae')."' />";

  echo "</form>";
}

function hae_asiakasryhmat() {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji    = 'ASIAKASRYHMA'";
  $result = pupe_query($query);

  $asiakasryhmat = array();
  while ($asiakasryhma = mysql_fetch_assoc($result)) {
    $asiakasryhmat[] = $asiakasryhma;
  }

  $ei_valintaa_array = array(
    'selite'   => '',
    'selitetark' => t('Ei valintaa'),
  );
  array_unshift($asiakasryhmat, $ei_valintaa_array);
  return $asiakasryhmat;
}

function hae_aleryhmat() {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM perusalennus
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $aleryhmat = array();
  while ($aleryhma = mysql_fetch_assoc($result)) {
    $aleryhmat[] = $aleryhma;
  }

  return $aleryhmat;
}

function hae_tuotteet_joilla_on_asiakashinta_tai_hae_kaikki_tuotteet(&$request) {
  global $kukarow, $yhtiorow;

  $tuotteet = array();

  if ($request['valittu_asiakas']) {
    $request['asiakas'] = hae_asiakas($request['valittu_asiakas']);
  }
  else {
    $request['asiakas']['ryhma'] = $request['valittu_asiakasryhma'];
  }

  $tuote_where = "AND status NOT IN ('P','X')";
  $poistuvat = '';
  if (!empty($request['nayta_poistetut'])) {
    $tuote_where = "";
    $poistuvat = 'kaikki';
  }

  if ($request['mitka_tuotteet'] == 'kaikki') {
    $query = "SELECT aleryhma, tuoteno
              FROM tuote
              WHERE yhtio   = '{$kukarow['yhtio']}'
              {$tuote_where}
              AND aleryhma != ''";
    $result = pupe_query($query);

    while ($tuote = mysql_fetch_assoc($result)) {
      $tuotteet[$tuote['tuoteno']] = 0;
    }
  }
  else {
    $query = "SELECT group_concat(parent.tunnus) tunnukset
              FROM puun_alkio
              JOIN dynaaminen_puu AS node ON (puun_alkio.yhtio = node.yhtio and puun_alkio.laji = node.laji and puun_alkio.puun_tunnus = node.tunnus)
              JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 0)
              WHERE puun_alkio.yhtio = '{$kukarow['yhtio']}'
              AND puun_alkio.laji    = 'ASIAKAS'
              AND puun_alkio.liitos  = '{$request['valittu_asiakas']}'";
    $result = pupe_query($query);
    $puun_tunnukset = mysql_fetch_assoc($result);


    $tuotteet_joilla_asiakashinta = hae_asiakashinnat($request['asiakas'], $puun_tunnukset, $kukarow['yhtio'], $poistuvat);

    foreach ($tuotteet_joilla_asiakashinta as $tuote) {
      $tuotteet[$tuote['tuoteno']] = 0;
    }
  }

  return $tuotteet;
}

function hae_asiakasalet($request) {
  global $kukarow, $yhtiorow;

  $tuotenumerot = array_keys($request['tuotteet']);

  $tuote_where = "AND status NOT IN ('P','X')";
  if (!empty($request['nayta_poistetut'])) {
    $tuote_where = "";
  }

  $query = "SELECT *
            FROM tuote
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND tuoteno   IN ('".implode("','", $tuotenumerot)."')
            {$tuote_where}
            AND aleryhma != ''
            ORDER BY tuote.aleryhma ASC, tuote.nimitys ASC";
  $result = pupe_query($query);

  $tuotteet = array();
  $palautettavat_kentat = "hinta,netto,ale,hintaperuste";

  while ($tuote = mysql_fetch_assoc($result)) {
    if (!empty($request['valittu_asiakas'])) {
      $laskurow = array();
      //haetaan asiakkaan oma hinta
      $laskurow["ytunnus"] = $request['asiakas']["ytunnus"];
      $laskurow["liitostunnus"] = $request['asiakas']["tunnus"];
      $laskurow["vienti"] = $request['asiakas']["vienti"];
      $laskurow["alv"] = $request['asiakas']["alv"];
      $laskurow["valkoodi"] = $request['asiakas']["valkoodi"];
      $laskurow["maa"] = $request['asiakas']["maa"];
      $laskurow['toim_ovttunnus'] = $request['asiakas']["toim_ovttunnus"];
      $laskurow['liitostunnus'] = $request['valittu_asiakas'];
      $laskurow['yhtio_toimipaikka'] = $request['asiakas']['toimipaikka'];

      $alehinnat = alehinta($laskurow, $tuote, 1, '', '', '', $palautettavat_kentat, '', '');
    }
    else {
      $alehinnat = alehinta(array(), $tuote, 1, '', '', '', $palautettavat_kentat, '', '', $request['valittu_asiakasryhma']);
    }

    $query = "SELECT *
              FROM tuotteen_toimittajat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuote['tuoteno']}'
              ORDER BY jarjestys ASC
              LIMIT 1";
    $tuotteen_toimittaja_result = pupe_query($query);
    $tuotteen_toimittaja_row = mysql_fetch_assoc($tuotteen_toimittaja_result);

    $alennettu_hinta = ( 1 - ( $alehinnat['ale']['ale1'] / 100 ) ) * $tuote['myyntihinta'];

    $alennusryhma = search_array_key_for_value_recursive($request['aleryhmat'], 'ryhma', $tuote['aleryhma']);

    if ($alennettu_hinta == 0) {
      $kateprosentti = number_format(0, 2);
    }
    else {
      $kateprosentti = number_format((1 - ($tuote['kehahin'] / $alehinnat['hinta'])) * 100, 2);
    }

    $status_array = array(
      'A' => t('Aktiivi'),
      'P' => t('Poistettu'),
      'T' => t('Tilaustuote'),
    );

    $tuote_temp = array(
      'aleryhma'       => $alennusryhma[0],
      'tuoteno'       => $tuote['tuoteno'],
      'tuote_nimi'     => $tuote['nimitys'],
      'kappalemaara'     => 1,
      'yksikko'       => $tuote['yksikko'],
      'paivitys_pvm'     => $tuote['muutospvm'],
      'ostohinta'       => number_format($tuotteen_toimittaja_row['ostohinta'], 2),
      'kehahin'       => number_format($tuote['kehahin'], 2),
      'ovh_hinta'       => number_format($tuote['myyntihinta'], 2),
      'ryhman_ale'     => number_format($alennettu_hinta, 2),
      'hinnasto_hinta'   => (($alehinnat['hintaperuste'] == 2 or $alehinnat['hintaperuste'] == 5) ? number_format($alehinnat['hinta'], 2) : ''),
      'status'       => $status_array[$tuote['status']],
      'ale_prosentti'     => '',
      'tarjous_hinta'     => '',
      'alennus_prosentti'   => '',
      'kate_prosentti'   => $kateprosentti,
    );

    $tuotteet[] = $tuote_temp;
  }

  return $tuotteet;
}

function generoi_custom_excel($tuotteet) {
  global $kukarow, $yhtiorow;

  if (count($tuotteet) == 0) {
    return false;
  }

  $xls_progress_bar = new ProgressBar(t("Tallennetaan exceliin"));
  $xls_progress_bar->initialize(count($tuotteet));

  $xls = new pupeExcel();
  $rivi = 0;
  $sarake = 0;
  $edellinen_ryhma = null;
  $headerit = array(
    'tuoteno'       => t('Tuoteno'),
    'tuote_nimi'     => t('Tuotteen nimi'),
    'kappalemaara'     => t('Kappalemaara'),
    'yksikko'       => t('Yksikkö'),
    'paivitys_pvm'     => t('Päivitys päivämäärä'),
    'ostohinta'       => t('Ostohinta'),
    'kehahin'       => t('Keskihankintahinta'),
    'ovh_hinta'       => t('Ovh').'-'.t('Hinta'),
    'ryhman_ale'     => t('Ryhmän ale'),
    'hinnasto_hinta'   => t('Hinnasto hinta'),
    'status'       => t('Status'),
    'ale_prosentti'     => t('Ale prosentti'),
    'tarjous_hinta'     => t('Alennettu hinta'),
    'alennus_prosentti'   => t('Alennus prosentti'),
    'kate_prosentti'   => t('Kate prosentti'),
  );

  foreach ($headerit as $header) {
    $xls->write($rivi, $sarake, $header, array('bold' => true));
    $sarake++;
  }
  $sarake = 0;
  $rivi++;

  foreach ($tuotteet as $tuote) {
    if ($tuote['aleryhma']['ryhma'] != $edellinen_ryhma) {
      $xls->write($rivi, $sarake, t('Ryhmä'), array('bold' => true));
      $sarake++;
      $xls->write($rivi, $sarake, $tuote['aleryhma']['selite'], array('bold' => true));

      $rivi++;
      $sarake = 0;
    }

    $xls->write($rivi, $sarake, $tuote['tuoteno']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['tuote_nimi']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['kappalemaara']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['yksikko']);
    $sarake++;
    $xls->write($rivi, $sarake, date('d.m.Y', strtotime($tuote['paivitys_pvm'])));
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['ostohinta']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['kehahin']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['ovh_hinta']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['ryhman_ale']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['hinnasto_hinta']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['status']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['ale_prosentti']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['tarjous_hinta']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['alennus_prosentti']);
    $sarake++;
    $xls->write($rivi, $sarake, $tuote['kate_prosentti']);
    $sarake++;

    $xls_progress_bar->increase();

    $edellinen_ryhma = $tuote['aleryhma']['ryhma'];
    $sarake = 0;
    $rivi++;
  }

  echo "<br/>";

  $xls_tiedosto = $xls->close();

  return $xls_tiedosto;
}
