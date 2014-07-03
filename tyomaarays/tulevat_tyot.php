<?php

require_once('../inc/parametrit.inc');
require_once("inc/laite_huolto_functions.inc");
require_once('tilauskasittely/tarkastuspoytakirja_pdf.php');
require_once('tilauskasittely/poikkeamaraportti_pdf.php');
require_once('tilauskasittely/tyolista_pdf.php');

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

enable_ajax();

if (!isset($ala_tee)) {
  $ala_tee = '';
}
if (!isset($valittu_asiakas)) {
  $valittu_asiakas = '';
}
if (!isset($ppa)) {
  $ppa = '01';
}
if (!isset($kka)) {
  $kka = '01';
}
if (!isset($vva)) {
  $vva = date('Y');
}
if (!isset($ppl)) {
  $ppl = '01';
}
if (!isset($kkl)) {
  $kkl = '01';
}
if (!isset($vvl)) {
  $vvl = date('Y') + 1;
}

if (isset($tee) and $tee == 'lataa_tiedosto') {
  $filepath = "/tmp/" . $tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  exit;
}

//AJAX requestit tänne
if (isset($ajax_request)) {
  exit;
}

$request = array(
    'ala_tee'         => $ala_tee,
    'valittu_asiakas' => $valittu_asiakas,
    'ppa'             => $ppa,
    'kka'             => $kka,
    'vva'             => $vva,
    'ppl'             => $ppl,
    'kkl'             => $kkl,
    'vvl'             => $vvl,
);

echo "<font class='head'>" . t("Tulevat työt") . ":</font>";
echo "<hr/>";
echo "<br/>";

$js = hae_tyojono2_js();
$css = hae_tyojono2_css();

echo $js;
echo $css;

echo_kust_tulevat_tyot_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if ($ala_tee == 'hae_tyomaaraykset') {
  pupe_query("TRUNCATE TABLE lasku_temp");
  pupe_query("TRUNCATE TABLE laskun_lisatiedot_temp");
  pupe_query("TRUNCATE TABLE tyomaarays_temp");
  pupe_query("TRUNCATE TABLE tilausrivi_temp");
  pupe_query("TRUNCATE TABLE tilausrivin_lisatiedot_temp");

  $temp = 1;

  $start = date('Y-m-d', strtotime("{$vva}-{$kka}-{$ppa}"));
  $end = date('Y-m-d', strtotime("{$vvl}-{$kkl}-{$ppl}"));

  $laitteiden_huoltosyklirivit = hae_laitteet_ja_niiden_huoltosyklit_ajalta($start, $end, $valittu_asiakas);

  foreach ($laitteiden_huoltosyklirivit as $laitteen_tietyn_paivan_huollot) {
    foreach ($laitteen_tietyn_paivan_huollot as $laitteen_huoltosykli) {
      generoi_tyomaarays_temp($laitteen_huoltosykli);
    }
  }

  $request['tyojonot'] = hae_tyojonot($request);
  $request['tyostatukset'] = hae_tyostatukset($request);
  $request['tyomaaraykset'] = hae_tyomaaraykset($request);

  $request['tyomaaraykset'] = kasittele_tyomaaraykset($request);

  echo "<div id='tyojono_wrapper'>";
  echo_tyomaaraykset_table($request);
  echo "</div>";
}

require ("inc/footer.inc");
