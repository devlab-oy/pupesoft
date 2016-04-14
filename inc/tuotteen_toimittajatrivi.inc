<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tuoteno") {
  if ($lukitse_avaimeen != "") {
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "liitostunnus") {
  if (isset($alasveto[$i])) {
    $ulos   = "<td>".$alasveto[$i]."</td>";
    $jatko = 0;
  }
  else {
    $toim_tuoteno_tunnus = $tunnus; // otetaan tää talteen

    $liitostunnus = $trow["liitostunnus"] > 0 ? $trow["liitostunnus"] : (int) $t[$i];

    $query = "SELECT *
              from toimi
              where yhtio  = '$kukarow[yhtio]'
              and tyyppi  != 'P'
              and tunnus   = '$liitostunnus'";
    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) == 1) {
      $srow = mysql_fetch_assoc($sresult);

      $ssize = strlen($trow[$i])+2;

      $ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize' maxlength='$maxsize'><br><br>$srow[ytunnus] $srow[nimi] $srow[nimitark]</td>";
      $jatko = 0;
    }
  }
}

if (mysql_field_name($result, $i) == "alkuperamaa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value=''>".t("Tuotteen toimittajan alkuperämaa")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "valuutta") {

  $ulos = "<td><select name='{$nimi}'>";

  $query = "SELECT nimi
            FROM valuu
            WHERE yhtio = '{$kukarow['yhtio']}'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);

  $ulos .= "<option value=''>".t("Tuotteen toimittajan valuutta")."</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = $trow[$i] == $vrow['nimi'] ? 'selected' : '';
    $ulos .= "<option value = '{$vrow['nimi']}' {$sel}>{$vrow['nimi']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "osto_alv") {
  $query = "SELECT distinct selite
            FROM avainsana
            WHERE yhtio = '$kukarow[yhtio]'
            and laji    = 'ALV'
            ORDER BY selite+0, laji, jarjestys, selite";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value = '-1'>".t("Tuotteen oletus")."</option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }

    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[0] %</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "jarjestys") {
  if ($trow[$i] == 0) {
    $trow[$i] = "";
  }

  $ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tehdas_saldo_paivitetty") {

  $ulos = "<td>";

  if (isset($trow[$i])) {
    $ulos .= tv1dateconv($trow[$i], "PITKA");
  }

  $ulos .= "&nbsp;";
  $ulos .= "<input type='hidden' name='{$nimi}' value='{$trow[$i]}' />";
  $ulos .= "<input type='hidden' name='paivita_tehdas_saldo_paivitetty[]' value='default' />";
  $ulos .= "<span style='float: right;'>".t("Päivitä")." <input type='checkbox' name='paivita_tehdas_saldo_paivitetty[]' /></span>";

  $ulos .= "</td>";

  $jatko = 0;
}
