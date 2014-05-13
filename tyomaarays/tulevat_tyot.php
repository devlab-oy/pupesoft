<?php

require_once('../inc/parametrit.inc');
require_once("inc/laite_huolto_functions.inc");

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

if (!isset($ala_tee)) {
  $ala_tee = '';
}
if (!isset($valittu_asiakas)) {
  $valittu_asiakas = '';
}

if (isset($tee) and $tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
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

require_once('tilauskasittely/tarkastuspoytakirja_pdf.php');
require_once('tilauskasittely/poikkeamaraportti_pdf.php');
require_once('tilauskasittely/tyolista_pdf.php');

echo "<font class='head'>".t("Tulevat työt").":</font>";
echo "<hr/>";
echo "<br/>";

$js = hae_tyojono2_js();
$css = hae_tyojono2_css();

echo $js;
echo $css;

if ($ala_tee != 'hae_tyomaaraykset') {

  enable_ajax();

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

  echo "<div>";

  echo "<form name='tulevat_tyot' method = 'post'>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<input type='hidden' name='ala_tee' value='hae_tyomaaraykset'>";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Asiakas")."</th>";
  echo "<td colspan='3'>";

  echo livesearch_kentta("asiakashinnasto_haku_form", "ASIAKASHAKU", "valittu_asiakas", 315, $valittu_asiakas, 'EISUBMIT', '', 'valittu_asiakas', 'ei_break_all');

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Alku pvm. Muodossa pp-kk-vvvv")."</th>";
  echo "<td><input type='text' name='ppa' value='".$ppa."' size='3' /></td>";
  echo "<td><input type='text' name='kka' value='".$kka."' size='3' /></td>";
  echo "<td><input type='text' name='vva' value='".$vva."' size='5' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Loppu pvm. Muodossa pp-kk-vvvv")."</th>";
  echo "<td><input type='text' name='ppl' value='".$ppl."' size='3' /></td>";
  echo "<td><input type='text' name='kkl' value='".$kkl."' size='3' /></td>";
  echo "<td><input type='text' name='vvl' value='".$vvl."' size='5' /></td>";
  echo "</tr>";

  echo "</table>";

  echo "<br />";
  echo "<input type='submit' value='".t("Hae")."'>";
  echo "</form>";

  echo "</div>";
}
else {

  pupe_query("TRUNCATE TABLE lasku_temp");
  pupe_query("TRUNCATE TABLE laskun_lisatiedot_temp");
  pupe_query("TRUNCATE TABLE tyomaarays_temp");
  pupe_query("TRUNCATE TABLE tilausrivi_temp");
  pupe_query("TRUNCATE TABLE tilausrivin_lisatiedot_temp");

  $temp = 1;

  $start = date('Y-m-d', strtotime($vva.'-'.$kka.'-'.$ppa));
  $end = date('Y-m-d', strtotime($vvl.'-'.$kkl.'-'.$ppl));

  $laitteiden_huoltosyklirivit = hae_laitteet_ja_niiden_huoltosyklit_ajalta($start, $end, $valittu_asiakas);

  list($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta) = paata_mitka_huollot_tehdaan($laitteiden_huoltosyklirivit);

  foreach ($huollettavien_laitteiden_huoltosyklirivit as $huollettavan_laitteen_huoltosyklirivi) {
    generoi_tyomaarays_temp($huollettavan_laitteen_huoltosyklirivi);
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
