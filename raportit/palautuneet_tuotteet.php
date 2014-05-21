<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST['tee'])) {
  if ($_POST['tee'] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST['kaunisnimi']) and $_POST['kaunisnimi'] != '') {
    $_POST['kaunisnimi'] = str_replace('/', '', $_POST['kaunisnimi']);
  }
}

require('../inc/parametrit.inc');
require('validation/Validation.php');

if ($tee == 'lataa_tiedosto') {
  $filepath = '/tmp/'.$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  else {
    echo '<font class="error">'.t('Tiedostoa ei ole olemassa').'</font>';
  }
  exit;
}

//tänne tämän tiedoston ajax_requestit
if ($ajax_request) {

}

echo '<font class="head">'.t('Palautuneet tuotteet').'</font><hr>';
?>
<style>
  .digit2_aika_input {
    width: 25px;
  }
  .digit4_aika_input {
    width: 50px;
  }
</style>
<script>
  $(document).ready(function() {
    tallenna_tiedosto_form_click();
  });

  function tallenna_tiedosto_form_click() {
    $('#tallennus_form').submit(function() {
      $('#excelin_generointi').remove();
      $('#tallennus_form').remove();
    });
  }
</script>

<?php

$alku_pvm = $vva.'-'.$kka.'-'.$ppa;
$loppu_pvm = $vvl.'-'.$kkl.'-'.$ppl;
//lisätään loppu_pvm:n +1d koska queryssä käytetään between
$loppu_pvm = date('Y-m-d', strtotime($loppu_pvm.' + 1 day'));
$request = array(
  'ppa'           => $ppa,
  'kka'           => $kka,
  'vva'           => $vva,
  'ppl'           => $ppl,
  'kkl'           => $kkl,
  'vvl'           => $vvl,
  'alku_pvm'         => $alku_pvm,
  'loppu_pvm'         => $loppu_pvm,
  'tee'           => $tee,
  'osumien_maara_rajaus'   => $osumien_maara_rajaus,
  'tallenna_exceliin'     => $tallenna_exceliin,
);

init($request);

if ($request['tee'] == 'aja_raportti') {
  $validations = array(
    'alku_pvm'         => 'paiva',
    'loppu_pvm'         => 'paiva',
    'osumien_maara_rajaus'   => 'numero',
  );

  $required = array('alku_pvm', 'loppu_pvm');
  $validator = new FormValidator($validations, $required);

  if ($validator->validate($request)) {
    $palautuneet_tuotteet = hae_palautuneet_tuotteet($request);
  }
  else {
    echo $validator->getScript();
  }

  echo_kayttoliittyma($request);

  echo "<br/>";
  echo "<br/>";

  if (!empty($palautuneet_tuotteet)) {
    $header_values = array(
      'tuoteno'           => array(
        'header' => t('Tuoteno'),
        'order'   => 0
      ),
      'nimitys'           => array(
        'header' => t('Tuotteen nimi'),
        'order'   => 10
      ),
      'palautettu_kpl'       => array(
        'header' => t('Palautetut kappaleet'),
        'order'   => 20
      ),
      'palautettu_hinta'       => array(
        'header' => t('Palautettu hinta'),
        'order'   => 30
      ),
      'ajoneuvosoveltuvuus_kpl'   => array(
        'header' => t('Ajoneuvosoveltuvuus kpl'),
        'order'   => 40
      ),
    );
    $force_to_string = array(
      'tuoteno',
    );

    if ($request['tallenna_exceliin']) {
      echo "<div id='excelin_generointi'>";
      $excel_tiedosto = generoi_excel_tiedosto($palautuneet_tuotteet, $header_values, $force_to_string);
      echo "</div>";
      if ($excel_tiedosto != '') {
        echo_tallennus_formi($excel_tiedosto, t('Palautuneet_tuotteet'));
      }
    }

    echo_rows_in_table($palautuneet_tuotteet, $header_values, $force_to_string, 'right_align_numbers');
  }
}
else {
  echo_kayttoliittyma($request);
}

require('inc/footer.inc');

function init(&$request) {
  if (empty($request['ppa']) and empty($request['kka']) and empty($request['vva'])) {
    $request['ppa'] = date('d', strtotime('now - 365 day'));
    $request['kka'] = date('m', strtotime('now - 365 day'));
    $request['vva'] = date('Y', strtotime('now - 365 day'));

    $request['alku_pvm'] = $request['vva'].'-'.$request['kka'].'-'.$request['ppa'];
  }

  if (empty($request['ppl']) and empty($request['kkl']) and empty($request['vvl'])) {
    $request['ppl'] = date('d', strtotime('now'));
    $request['kkl'] = date('m', strtotime('now'));
    $request['vvl'] = date('Y', strtotime('now'));

    $request['loppu_pvm'] = $request['vvl'].'-'.$request['kkl'].'-'.$request['ppl'];
  }
}

function hae_palautuneet_tuotteet($request) {
  global $kukarow, $yhtiorow, $palvelin2;

  $query_limit = "";
  if (!empty($request['osumien_maara_rajaus'])) {
    $query_limit = "LIMIT {$request['osumien_maara_rajaus']}";
  }

  $query = "SELECT tilausrivi.tuoteno,
            tilausrivi.nimitys,
            sum(tilausrivi.tilkpl) as palautettu_kpl,
            sum((tilausrivi.rivihinta)) as palautettu_hinta
            FROM   lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus )
            WHERE  lasku.yhtio       = '{$kukarow['yhtio']}'
            AND lasku.tila           = 'L'
            AND lasku.alatila        = 'X'
            AND lasku.tilaustyyppi   = 'R'
            AND lasku.tapvm BETWEEN '{$request['alku_pvm']}' AND '{$request['loppu_pvm']}'
            GROUP BY tilausrivi.tuoteno, tilausrivi.nimitys
            ORDER BY palautettu_kpl ASC
            {$query_limit}";
  $result = pupe_query($query);

  $palautuneet_tuotteet = array();
  $onkoyhteensop       = table_exists("yhteensopivuus_tuote");

  while ($palautunut_tuote = mysql_fetch_assoc($result)) {
    if ($onkoyhteensop) $palautunut_tuote['ajoneuvosoveltuvuus_kpl'] = hae_ajoneuvosoveltuvuus_kpl($palautunut_tuote['tuoteno']);
    $palautunut_tuote['tuoteno'] = "<a href='#' onclick=\"window.open('{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($palautunut_tuote['tuoteno'])."', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1000,height=800'); return false;\">".$palautunut_tuote['tuoteno']."</a>";
    $palautuneet_tuotteet[] = $palautunut_tuote;
  }

  return $palautuneet_tuotteet;
}

function hae_ajoneuvosoveltuvuus_kpl($tuoteno) {
  global $kukarow, $yhtiorow;

  $query = "SELECT count(*) as kpl
            FROM yhteensopivuus_tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);

  $yhteensopivuuksien_lkm = mysql_fetch_assoc($result);
  return $yhteensopivuuksien_lkm['kpl'];
}

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form name='palautuneet_tuotteet_form' method='POST' action=''>";
  echo "<input type='hidden' name='tee' value='aja_raportti' />";
  echo '<table>';

  echo '<tr>';
  echo '<th>'.t('Aikaväli').'</th>';
  echo '<td>';
  echo "<input type='text' class='digit2_aika_input' name='ppa' value='{$request['ppa']}' /> ";
  echo "<input type='text' class='digit2_aika_input' name='kka' value='{$request['kka']}' /> ";
  echo "<input type='text' class='digit4_aika_input' name='vva' value='{$request['vva']}' />";
  echo ' - ';
  echo "<input type='text' class='digit2_aika_input' name='ppl' value='{$request['ppl']}' /> ";
  echo "<input type='text' class='digit2_aika_input' name='kkl' value='{$request['kkl']}' /> ";
  echo "<input type='text' class='digit4_aika_input' name='vvl' value='{$request['vvl']}' />";
  echo '</td>';
  echo '</tr>';

  echo "<tr>";
  echo "<th>".t("Osumien määrän rajaus")."</th>";
  echo "<td>";
  echo "<input type='text' name='osumien_maara_rajaus' value='{$request['osumien_maara_rajaus']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tallenna exceliin")."</th>";
  echo "<td>";
  echo "<input type='checkbox' name='tallenna_exceliin' ".(!empty($request['tallenna_exceliin']) ? 'CHECKED' : '')." />";
  echo "</td>";
  echo "</tr>";

  echo '</table>';

  echo "<br/>";
  echo "<input type='submit' value='".t('Hae')."' />";
  echo "</form>";
}

//callback function table td:lle
function right_align_numbers($header, $solu, $force_to_string) {
    if (!stristr($header, 'tunnus')) {
        if (is_numeric($solu) and !in_array($header, $force_to_string)) {
      if (!ctype_digit($solu)) {
        echo "<td align='right'>".number_format($solu, 2)."</td>";
      }
      else {
        echo "<td align='right'>$solu</td>";
      }
        }
        else {
      echo "<td>{$solu}</td>";
        }
  }
}
