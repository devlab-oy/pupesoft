<?php

$kaunisnimi = isset($_POST["kaunisnimi"]) ? $_POST["kaunisnimi"] : null;
$tee        = isset($_REQUEST["tee"]) ? $_REQUEST["tee"] : null;
$toim       = isset($_REQUEST["toim"]) ? $_REQUEST["toim"] : null;
$toim_tee   = isset($_REQUEST["toim_tee"]) ? $_REQUEST["toim_tee"] : null;

if (isset($tee)) {
  if ($tee == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($kaunisnimi != '') $kaunisnimi = str_replace("/", "", $kaunisnimi);
}

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Näissä keisseissä ei slavea
if (($toim == 'hyvaksynta' and $tee == 'T') or $toim == 'maksuvalmius' or $toim_tee == 'kululasku') {
  $useslave = 0;
}

if ($toim == 'avoimet') {
  // DataTables päälle
  $pupe_DataTables = array("avoimet0", "avoimet1");
}

if ($toim == 'hyvaksynta') {
  // DataTables päälle
  $pupe_DataTables = array("hyvaksynta");
}

if ($toim == 'dokumenttien_hyvaksynta') {
  // DataTables päälle
  $pupe_DataTables = array("dokumenttien_hyvaksynta");
}

if ($toim == 'toimittajahaku' or $toim == 'laskuhaku' or $toim == 'myyrespaakirja') {
  // DataTables päälle
  $pupe_DataTables = $toim;
}

require "inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

$cleantoim = $toim;

// Rajataanko rappari näyttämään vain Omaa KustannusPaikkaa
if (substr($toim, -4) == '_OKP') {
  // käyttäjän osasto kertoo oletuskustannuspaikan
  $vainomakustp = TRUE;

  if (empty($kukarow["osasto"])) {
    echo "<br><br>".t("Käyttäjätiedoistasi puuttuu osasto")."!<br>";
    require "inc/footer.inc";
    exit;
  }

  $mul_kustp    = array();
  $mul_kustp[]  = $kukarow["osasto"];
  $cleantoim    = substr($toim, 0, -4);
}

// Livesearch jutut
enable_ajax();

js_popup();

if (!isset($excel)) $excel = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";

if ($livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

if ($excel == "YES") {
  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi   = 0;
}

require "inc/{$cleantoim}.inc";

if (isset($worksheet) and $excelrivi > 0) {
  $excelnimi = $worksheet->close();

  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna tulos").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='".ucfirst(strtolower($toim)).".xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";
}

require "inc/footer.inc";
