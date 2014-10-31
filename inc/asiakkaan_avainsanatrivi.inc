<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi  = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "liitostunnus") {
  if ($lukitse_avaimeen != "") {
    $tyyppi = 4;
    if ($lukitse_avaimeen != "") {
      $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
      $jatko = 0;
    }
  }
  else {
    $tyyppi = 2;
  }
}

if (mysql_field_name($result, $i) == "laji") {
  $vresult = t_avainsana("ASAVAINSANA");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";
  $ulos .= "<option value=''>".t("Oletus")."</option>";

  while ($row = mysql_fetch_assoc($vresult)) {
    $sel = "";

    if ($row["selite"] == $trow[$i]) {
      $sel = 'selected';
      $asavainsanatyyppi = $row["selitetark_2"];
      $asavainsanalaji = $row['selite'];
    }

    $ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "avainsana") {

  if (stripos($asavainsanatyyppi, "DATE") !== FALSE) {
    $tyyppi = 1.5;
  }
  elseif (stripos($asavainsanalaji, "OHJAUSMERKKI") !== FALSE) {

    $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

    $query = "SELECT selite
              FROM toimitustapa
              WHERE yhtio = '{$kukarow['yhtio']}'
              ORDER BY 1";
    $toimitustapares = pupe_query($query);

    $ulos .= "<option value='OLETUS'>".t("Oletus")."</option>";

    while ($toimitustaparow = mysql_fetch_assoc($toimitustapares)) {
      $sel = $trow[$i] == $toimitustaparow['selite'] ? " selected" : "";

      $ulos .= "<option value='{$toimitustaparow['selite']}'{$sel}>{$toimitustaparow['selite']}</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
  elseif (stripos($asavainsanalaji, "oletusmyyntivarasto") !== FALSE) {

    $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

    $varasto_query = "SELECT tunnus, nimitys
                      FROM varastopaikat
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      AND tyyppi  != 'P'";

    $varasto_res = pupe_query($varasto_query);

    while ($varasto_row = mysql_fetch_assoc($varasto_res)) {
      $sel = $trow[$i] == $varasto_row['tunnus'] ? " selected" : "";
      $ulos .= "<option value='{$varasto_row['tunnus']}'{$sel}>{$varasto_row['nimitys']}</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
  elseif (stripos($asavainsanatyyppi, "TOIMITUSTAPA") !== FALSE) {
    $query = "SELECT *
              FROM toimitustapa
              WHERE yhtio = '$kukarow[yhtio]'
              ORDER BY jarjestys, selite";
    $vresult = pupe_query($query);
    $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

    while ($vrow=mysql_fetch_assoc($vresult)) {
      $sel="";
      if ($trow[$i] == $vrow['selite']) {
        $sel = "selected";
      }
      $ulos .= "<option value = '$vrow[selite]' $sel>".t_tunnus_avainsanat($vrow, "selite", "TOIMTAPAKV")."</option>";
    }
    $ulos .= "</select></td>";
    $jatko = 0;
  }
  elseif (stripos($asavainsanatyyppi, "EXTRANET_TEEMA") !== FALSE) {
    $vresult = t_avainsana("EXTRANET_TEEMA");

    $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value=''>".t("Oletus")."</option>";

    while ($row = mysql_fetch_assoc($vresult)) {
      $sel = "";

      if ($row["selite"] == $trow[$i]) {
        $sel = 'selected';
        $asavainsanatyyppi = $row["selitetark_2"];
      }

      $ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
  elseif (strpos($asavainsanatyyppi, "/") !== FALSE) {
    $sel = array();
    $sel[$trow[$i]] = "SELECTED";

    $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

    foreach (explode("/", $asavainsanatyyppi) as $astyy) {
      $ulos .= "<option value = '$astyy' ".$sel[$astyy].">$astyy</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
  elseif (stripos($asavainsanatyyppi, "PAIKALLISVARAST") !== FALSE) {
    $query = "SELECT *
              FROM avainsana
              WHERE yhtio = '$kukarow[yhtio]'
              AND laji    = 'PAIKALLISVARAST'
              ORDER BY jarjestys, selite";
    $varresult = pupe_query($query);
    $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

    while ($paikallisrow = mysql_fetch_assoc($varresult)) {

      $sel = $trow[$i] == $paikallisrow['tunnus'] ? " selected" : "";

      $ulos .= "<option value='{$paikallisrow['tunnus']}'{$sel}>{$paikallisrow['selitetark']}</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) {
      $sel = "selected";
    }

    $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
