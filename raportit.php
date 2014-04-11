<?php


if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Näissä keisseissä ei slavea
if (($_REQUEST["toim"] == 'hyvaksynta' and $_REQUEST["tee"] == 'T') or $_REQUEST["toim"] == 'maksuvalmius' or $_REQUEST["toim_tee"] == 'kululasku') {
  $useslave = 0;
}

if ($_REQUEST["toim"] == 'avoimet') {
  // DataTables päälle
  $pupe_DataTables = array("avoimet0", "avoimet1");
}

if ($_REQUEST["toim"] == 'toimittajahaku' or $_REQUEST["toim"] == 'laskuhaku' or $_REQUEST["toim"] == 'myyrespaakirja') {
  // DataTables päälle
  $pupe_DataTables = $_REQUEST["toim"];
}

require ("inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

// Livesearch jutut
enable_ajax();

if (!isset($excel))      $excel = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";

if ($livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

if ($excel == "YES") {
  include('inc/pupeExcel.inc');

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi   = 0;
}

require ("inc/".$toim.".inc");

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

require ("inc/footer.inc");
