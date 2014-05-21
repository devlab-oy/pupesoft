<?php

require ("inc/parametrit.inc");

if ($toim == "VAIHDAKEHAHIN") {
  echo "<font class='head'>".t("Vaihda tuotteen keskihankintahinta")."</font><hr>";
}
else {
  echo "<font class='head'>".t("Epäkurantit")."</font><hr>";
}

$tee = isset($tee) ? trim($tee) : "";
$tuoteno = isset($tuoteno) ? trim($tuoteno) : "";

if ($toim == "VAIHDAKEHAHIN" and $tee == "tiedostosta") {

  if (isset($_FILES['userfile']) and (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE)) {

    if ($_FILES['userfile']['size'] == 0) {
      echo "<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>";
      $tee = '';
    }

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $ext = strtoupper($path_parts['extension']);

    $retval = tarkasta_liite("userfile", array("XLSX","XLS","ODS","SLK","XML","GNUMERIC","CSV","TXT","DATAIMPORT"));

    if ($retval !== TRUE) {
      echo "<font class='error'><br>".t("Väärä tiedostomuoto")."!</font>";
      $tee = '';
    }
  }
  else {
    $tee = '';
  }
}

if ($toim == "VAIHDAKEHAHIN" and $tee == "tiedostosta") {

  // Tehdään kaikki tapahtumat samalle tositteelle!
  $tapahtumat_samalle_tositteelle = "kylla";

  $excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

  foreach ($excelrivit as $rivinumero => $rivi) {
    $tuoteno  = $rivi[0];
    $uusiarvo  = $rivi[1];
    $oma_selite = $rivi[2];
    $tee     = "vaihda_kehahin";

    echo t("Käsitellään riviä").":  ".($rivinumero+1).". ".t("Tuote").": $tuoteno ";

    require ("epakurantti.inc");

    echo "<br>";
  }

  $tuoteno     = "";
  $uusiarvo  = "";
  $oma_selite  = "";
  $tee     = "";
}

if ($tee != '' and $tuoteno == "") {
  $tee = "";
}

if ($tee != '') {

  // täällä tehdään epäkuranttihommat
  // tarvitaan $kukarow, $tuoteno ja jos halutaan muuttaa ni $tee jossa on paalle, puolipaalle tai pois
  require ("epakurantti.inc");

  if ($tee == 'vahvista' and isset($sarjatunnus) and $sarjatunnus > 0) {

    echo "<form method='post'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";
    echo "<input type='hidden' name='sarjanro' value='{$sarjanro}'>";
    echo "<input type='hidden' name='sarjatunnus' value='{$sarjatunnus}'>";
    echo "<input type='hidden' name = 'tee' value='sarjanro_paalle'>";

    echo "<table>";
    echo "<tr><th>".t("Tuote")                  ."</th><td>$tuoterow[tuoteno]</td></tr>";
    echo "<tr><th>".t("Sarjanumero")        ."</th><td>$sarjanro</td>";
    echo "<tr><th>".t("Varastonarvo nyt")      ."</th><td>";
    echo sprintf('%.2f', sarjanumeron_ostohinta("tunnus", $sarjatunnus));
    echo "</td></tr>";
    echo "<tr><th>".t("Uusi varastonarvo")      ."</th><td>";
    echo "<input type='text' name='uusiarvo' size='10'>";
    echo "</td></tr>";
    echo "<tr><th>".t("Selite")      ."</th><td>";
    echo "<input type='text' name='epakurantti_selite' size='35'></td></tr>";
    echo "</table><br><br>";
    echo "<input type='submit' value='".t("Muuta varastonarvoa")."'>";
    echo "</form><br>";
  }
  elseif ($tee == 'vahvista') {

    echo "<table>";
    echo "<tr><th>".t("Tuote")                  ."</th><td>$tuoterow[tuoteno]</td></tr>";
    echo "<tr><th>".t("Varastonarvo nyt")      ."</th><td>$tuoterow[saldo] * $nykyinen_keskihankintahinta = $nykyinen_varastonarvo</td></tr>";
    echo "<tr><th>".t("Korjaamaton varastonarvo")  ."</th><td>$tuoterow[saldo] * $tuoterow[kehahin] = $brutto_varastonarvo</td></tr>";
    echo "<tr><th>".t("25% epäkurantti")      ."</th><td>$tuoterow[epakurantti25pvm]</td></tr>";
    echo "<tr><th>".t("Puoliepäkurantti")      ."</th><td>$tuoterow[epakurantti50pvm]</td></tr>";
    echo "<tr><th>".t("75% epäkurantti")      ."</th><td>$tuoterow[epakurantti75pvm]</td></tr>";
    echo "<tr><th>".t("Epäkurantti")        ."</th><td>$tuoterow[epakurantti100pvm]</td></tr>";
    echo "</table><br>";

    if ($toim == "") {
      // voidaan merkata 25epäkurantiksi
      if ($tuoterow['epakurantti25pvm'] == '0000-00-00') {
        echo "<form method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
        echo "<input type='hidden' name = 'tee' value='25paalle'> ";
        echo "<input type='submit' value='".t("Merkitään 25% epäkurantiksi")."'></form> ";
      }

      // voidaan merkata puoliepäkurantiksi
      if ($tuoterow['epakurantti50pvm'] == '0000-00-00') {
        echo "<form method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
        echo "<input type='hidden' name = 'tee' value='puolipaalle'> ";
        echo "<input type='submit' value='".t("Merkitään puoliepäkurantiksi")."'></form> ";
      }

      // voidaan merkata 75epäkurantiksi
      if ($tuoterow['epakurantti75pvm'] == '0000-00-00') {
        echo "<form method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
        echo "<input type='hidden' name = 'tee' value='75paalle'> ";
        echo "<input type='submit' value='".t("Merkitään 75% epäkurantiksi")."'></form> ";
      }

      // voidaan merkata epäkurantiksi
      if ($tuoterow['epakurantti100pvm'] == '0000-00-00') {
        echo "<form method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
        echo "<input type='hidden' name = 'tee' value='paalle'>";
        echo "<input type='submit' value='".t("Merkitään epäkurantiksi")."'></form> ";
      }

      // voidaan aktivoida
      if (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
        echo "<form method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
        echo "<input type='hidden' name = 'tee' value='pois'>";
        echo "<input type='submit' value='".t("Aktivoidaan kurantiksi")."'></form>";
      }

      // voidaan aktivoida
      if (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
        echo "<form method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
        echo "<input type='hidden' name = 'tee' value='peru'>";
        echo "<input type='submit' value='".t("Aktivoidaan kurantiksi, ei nosteta keskihankintahintaa")."'></form>";
      }
    }

    // voidaan vaihtaa tuotteen kehahinta
    if ($toim == "VAIHDAKEHAHIN" and $tuoterow['epakurantti25pvm'] == '0000-00-00' and $tuoterow['epakurantti50pvm'] == '0000-00-00' and $tuoterow['epakurantti75pvm'] == '0000-00-00' and $tuoterow['epakurantti100pvm'] == '0000-00-00') {

      echo "<br><br><form method='post'><table>";

      echo "<tr><th>".t("Uusi keskihankintahinta")."</th><td>";
      echo "<input type='text' name='uusiarvo' size='10' value='$uusiarvo'>";
      echo "</td></tr>";
      echo "<tr><th>".t("Selite")."</th><td>";
      echo "<input type='text' name='oma_selite' size='35' value='$oma_selite'></td></tr>";
      echo "</table><br>";

      echo "<input type='hidden' name='toim' value='{$toim}'>";
      echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
      echo "<input type='hidden' name = 'tee' value='vaihda_kehahin'> ";
      echo "<input type='submit' value='".t("Vaihda keskihankintahinta")."'></form>";
    }
    elseif ($toim == "VAIHDAKEHAHIN") {
      echo "<font class='error'>".t("Tuote on epäkurantti. Keskihankintahintaa ei voida vaihtaa")."!</font><br>";
    }
  }
  elseif ($tee != "STOP") {
    $tee = "";
  }
}

if ($tee == '') {
  echo "<br><table><tr><th>".t("Valitse tuote")."</th><td>";
  echo "<form name='epaku' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<input type='text' name='tuoteno'>";
  echo "</td><td>";
  echo "<input type='hidden' name='tee' value='vahvista'>";
  echo "<input type='submit' value='".t("Valitse")."'>";
  echo "</form>";
  echo "</td></tr></table>";

  if ($toim == "VAIHDAKEHAHIN") {
    echo "<br><br><form method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' name='tee' value='tiedostosta'>";

    echo "<br><br>
        <font class='head'>".t("Lue tuotteet tiedostosta")."</font><hr>
        <table>
        <tr><th colspan='3'>".t("Tiedostomuoto").":</th></tr>
        <tr>";
    echo "<td>".t("Tuoteno")."</td><td>".t("Keskihankintahinta")."</td><td>".t("Selite")."</td>";
    echo "</tr>";
    echo "<tr><td class='back'><br></td></tr>";
    echo "<tr><th>".t("Valitse tiedosto").":</th>
        <td colspan='2'><input name='userfile' type='file'></td></tr>";

    echo "</table>";
    echo "<br><br><input type='submit' value='".t("Valitse")."'></form>";
  }

  // kursorinohjausta
  $formi  = "epaku";
  $kentta = "tuoteno";
}

require ("inc/footer.inc");
