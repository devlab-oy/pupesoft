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
  $filepath = "/tmp/".$tmpfilenimi;
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

echo "<font class='head'>".t("Kustannusarvio").":</font>";
echo "<hr/>";

if (isset($ala_tee) and $ala_tee == 'tulosta_kustannusarvio' and $valittu_asiakas != '') {
  if (is_numeric($valittu_asiakas)) {
    if (hae_asiakas($valittu_asiakas)) {
      if (checkdate($kka, $ppa, $vva) and checkdate($kkl, $ppl, $vvl)) {
        $alku       = date('Y-m-d', strtotime($vva.'-'.$kka.'-'.$ppa));
        $loppu       = date('Y-m-d', strtotime($vvl.'-'.$kkl.'-'.$ppl));
        $pdf_tiedosto   = \PDF\Kustannusarvio\hae_kustannusarvio($valittu_asiakas, $alku, $loppu);
      }
      else {
        $pdf_tiedosto   = false;
        $error       = "Tarkista päivämäärät";
      }
    }
    else {
      $pdf_tiedosto   = false;
      $error       = "Asiakasta ei löytynyt";
    }
  }
  else {
    $pdf_tiedosto   = false;
    $error       = "Asiakasta ei löytynyt";
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

echo "<form name='kustannusarvio_pdf_form' method = 'post'>";
echo "<input type='hidden' name='toim' value='{$toim}'>";
echo "<input type='hidden' name='ala_tee' value='tulosta_kustannusarvio'>";

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

require ('inc/footer.inc');
