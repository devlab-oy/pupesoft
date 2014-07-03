<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require_once('../inc/parametrit.inc');

if (isset($tee) and $tee == 'lataa_tiedosto') {
  $filepath = "/tmp/" . $tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  exit;
}

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

require_once('tilauskasittely/kustannusarvio_pdf.php');
require_once("inc/laite_huolto_functions.inc");

echo "<font class='head'>" . t("Kustannusarvio") . ":</font>";
echo "<hr/>";

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

echo_kust_tulevat_tyot_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if ($ala_tee == 'hae_tyomaaraykset' and $valittu_asiakas != '') {
  if (is_numeric($valittu_asiakas)) {
    if (hae_asiakas($valittu_asiakas)) {
      if (checkdate($kka, $ppa, $vva) and checkdate($kkl, $ppl, $vvl)) {
        $start = date('Y-m-d', strtotime("{$vva}-{$kka}-{$ppa}"));
        $end = date('Y-m-d', strtotime("{$vvl}-{$kkl}-{$ppl}"));
        $pdf_tiedosto = \PDF\Kustannusarvio\hae_kustannusarvio($valittu_asiakas, $start, $end);
      }
      else {
        $pdf_tiedosto = false;
        $error = "Tarkista päivämäärät";
      }
    }
    else {
      $pdf_tiedosto = false;
      $error = "Asiakasta ei löytynyt";
    }
  }
  else {
    $pdf_tiedosto = false;
    $error = "Asiakasta ei löytynyt";
  }
  if (!empty($pdf_tiedosto)) {
    echo_tallennus_formi($pdf_tiedosto, t("Kustannusarvio"), 'pdf');
  }
  else {
    if (!isset($error)) {
      $error = "Ei löytynyt toimenpiteitä";
    }
    echo "<font class='error'>";
    echo t("Kustannusarvion generointi epäonnistui");
    echo ' - ';
    echo t($error);
    echo "</font>";
  }
}

require ('inc/footer.inc');
