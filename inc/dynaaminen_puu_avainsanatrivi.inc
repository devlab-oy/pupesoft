<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "liitostunnus") {
  if ($lukitse_avaimeen != "") {
    $tyyppi = 4;
    $ulos = "<input type='hidden' name='{$nimi}' value='{$lukitse_avaimeen}'>";
    $jatko = 0;
  }
  else {
    $tyyppi = 2;
  }
}

if (mysql_field_name($result, $i) == "laji") {
  $vresult = t_avainsana("DPAVAINSANALAJI");

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";
  $ulos .= "<option value=''>".t("Oletus")."</option>";

  while ($row = mysql_fetch_assoc($vresult)) {
    $sel = "";

    if ($row["selite"] == $trow[$i]) {
      $sel = 'selected';
      $dpavainsanalaji = $row['selite'];
    }

    $ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) {
      $sel = "selected";
    }
    else {
      $sel = "";
    }

    $ulos .= "<option value='{$sanakirja_kieli}' {$sel}>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
