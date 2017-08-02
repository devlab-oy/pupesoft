<?php

echo "<br>
    <form method='POST'>
    <input type='hidden' name='tee' value='LISAA'>
    <input type='hidden' name='lopetus' value='$lopetus'>
    <input type='hidden' name='year'  value='$year'>
    <input type='hidden' name='month' value='$month'>
    <input type='hidden' name='day'   value='$day'>
    <table>
    <tr><td colspan='2' class='back'>".t("Varaa").": </td></tr>
    <tr><th>".t("Alkamisajankohta").":</th>";


if ($kello == "") {
  $alku  = "";
  $loppu = "";

  echo "<td>$thfont $day.$month.$year Klo: ";

  echo "<select name='kello'>";

  for ($i = 801; $i <= 2300; $i++) {
    $i--;
    $sel = '';

    if (substr($i, 0, 1) == "8" || substr($i, 0, 1) == "9") {
      $alku = substr($i, 0, 1);
      $loppu = substr($i, 1, 2);
      $alku = "0".$alku;
    }
    else {
      $alku = substr($i, 0, 2);
      $loppu = substr($i, 2, 2);
    }

    echo "<option value='$alku:$loppu'>$alku:$loppu</option>";

    if ($loppu == "00" && $alku <= 16) {
      $loppu = "30";
    }
    else {
      $alku++;
      $loppu = "00";
    }
    $i = $alku.$loppu;
  }
  echo "</select></td></tr>";
}
else {
  echo "<td>$thfont $day.$month.$year Klo: $kello</td></tr>";
  echo "<input type='hidden' name='kello' value='$kello'>";
}

echo "<tr>
    <th>".t("Päättymisajankohta").":</th>
    <td>
    <input type='text' size='3' name='lday'   value='$day'>
    <input type='text' size='3' name='lmonth' value='$month'>
    <input type='text' size='5' name='lyear'  value='$year'>
    ".t("Klo").": <select name='lkello'>";

$alku  = "";
$loppu = "";

for ($i = 801; $i <= 2300; $i++) {
  $i--;
  $sel = '';

  if (substr($i, 0, 1) == "8" || substr($i, 0, 1) == "9") {
    $alku = substr($i, 0, 1);
    $loppu = substr($i, 1, 2);
    $alku = "0".$alku;
  }
  else {
    $alku = substr($i, 0, 2);
    $loppu = substr($i, 2, 2);
  }

  if ($alku == substr($kello, 0, 2)) {
    $sel = "SELECTED";
  }
  echo "<option value='$alku:$loppu' $sel>$alku:$loppu</option>";

  if ($loppu == "00" && $alku <= 21) {
    $loppu = "30";
  }
  else {
    $alku++;
    $loppu = "00";
  }
  $i = $alku.$loppu;
}

echo "</select></td>
  </tr>
  <tr><th>".t("Kohde").":</th><td><input type='hidden' name='toim' value='$toim'>$toim</td></tr>
  <tr><th>".t("Yhtiö").":</th><td><input type='text' name='kentta01' size='30' value='$yhtiorow[nimi]'></td></tr>
  <tr><th>".t("Osasto").":</th><td><input type='text' name='kentta02' size='30'></td></tr>
  <tr><th>".t("Tilaisuus").":</th><td><select name='kentta03'>
  <option value='Kokous'>Kokous</option>
  <option value='Edustus'>Edustus</option>
  <option value='Koulutus'>Koulutus</option>
  <option value='Esittely/Näyttely'>Esittely/Näyttely</option>
  <option value='Muu'>Muu, mikä --></option>
  </select>&nbsp;
  <input type='text' name='kentta04' size='30'></td></tr>
  <tr><th>".t("Lisätiedot").":</th><td><textarea name='kentta05' cols='30' rows='3' wrap='hard'></textarea></td></tr>";

if ($toim != "") {
  echo "<tr><td colspan='2' class='back'><br><br>".t("Tilaisuuden tarkemmat tiedot").":</td></tr>";
  echo "<tr><th>".t("Isännät").":</th><td><textarea name='kentta06' cols='30' rows='3' wrap='hard'>$kukarow[nimi]</textarea></td></tr>";
  echo "<tr><th>".t("Vieraat").":</th><td><textarea name='kentta07' cols='30' rows='2' wrap='hard'></textarea></td></tr>";
  echo "<tr><th>".t("Vieraslukumäärä").":</th><td><input type='text' name='kentta08' size='31'></td></tr>";
  echo "<tr><th>Juomatoivomus:</th><td><textarea name='kentta10' cols='30' rows='3' wrap='hard'></textarea></td></tr>";
}
echo "</table>";

echo "<br><input type='submit' name='lis' value='Lisää'>";
echo "</form>";

$jatko = 0;
