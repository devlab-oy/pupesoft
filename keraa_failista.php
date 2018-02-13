<?php

require "inc/parametrit.inc";

if ($tee == "keraa") {
  if (isset($_FILES['userfile']) and (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE)) {

    if ($_FILES['userfile']['size'] == 0) {
      echo "<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>";
      $tee = '';
    }

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $ext = strtoupper($path_parts['extension']);

    $retval = tarkasta_liite("userfile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT", "DATAIMPORT"));

    if ($retval !== TRUE) {
      echo "<font class='error'><br>".t("V‰‰r‰ tiedostomuoto")."!</font>";
      $tee = '';
    }
  }
}

if ($tee == "keraa") {
  $excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

  $kerattavat = array();

  foreach ($excelrivit as $rivinumero => $rivi) {

    // index 0, maara
    // index 1, tilaus
    // index 2, tuoteno

    $kpl   = $rivi[0];
    $tilaus = $rivi[1];
    $tuote  = $rivi[2];

    if (!isset($kerattavat[$tilaus][$tuote])) $kerattavat[$tilaus][$tuote] = $kpl;
    else $kerattavat[$tilaus][$tuote] += $kpl;
  }

  merkkaa_keratyksi($kerattavat);
}

echo "<br><br><form method='post' enctype='multipart/form-data'>";
echo "<input type='hidden' name='tee' value='keraa'>";

echo "<br><br>
    <font class='head'>".t("Lue ker‰tt‰v‰t tuotteet tiedostosta")."</font><hr>
    <table>
    <tr><th colspan='3'>".t("Tiedostomuoto").":</th></tr>
    <tr>";
echo "<td>".t("Kpl")."</td><td>".t("Tilausnumero")."</td><td>".t("Tuoteno")."</td>";
echo "</tr>";
echo "<tr><td class='back'><br></td></tr>";
echo "<tr><th>".t("Valitse tiedosto").":</th>
    <td colspan='2'><input name='userfile' type='file'></td></tr>";

echo "</table>";
echo "<br><br><input type='submit' value='".t("Valitse")."'></form>";

require "inc/footer.inc";
