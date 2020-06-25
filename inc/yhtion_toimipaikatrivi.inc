<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "maa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "varastotoimipaikka") {
    $sela = $selb = '';
    if ($trow[$i] != '') {
      $selb = "SELECTED";
    }
    else {
      $sela = "SELECTED";
    }

    $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value = '' $sela>".t("Ei")."</option>";
    $ulos .= "<option value = 'o' $selb>".t("Kyllä")."</option>";
    $ulos .= "</select></td>";
    $jatko = 0;
}

if ((mysql_field_name($result, $i) == "tilino") or
  (mysql_field_name($result, $i) == "tilino_eu") or
  (mysql_field_name($result, $i) == "tilino_ei_eu") or
  (mysql_field_name($result, $i) == "tilino_marginaali") or
  (mysql_field_name($result, $i) == "tilino_kaanteinen") or
  (mysql_field_name($result, $i) == "tilino_osto_marginaali") or
  (mysql_field_name($result, $i) == "toim_alv") or
  (mysql_field_name($result, $i) == "tilino_triang")) {

  if ($ulosarray[$i] != '') {
    $ulos = $ulosarray[$i];
    $jatko = 0;
  }
  else {
    $ulos = "<td nowrap valign='top'>";

    if ($trow[$i] != "" and (!isset($virhe[$i]) or $virhe[$i] == "")) {
      $ulos .= "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>$trow[$i] - ";
    }
    else {
      $ulos .= "<input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
    }

    $query = "SELECT nimi
              FROM tili
              WHERE yhtio='$kukarow[yhtio]' and tilino = '$trow[$i]'";
    $vresult = pupe_query($query);

    if (mysql_num_rows($vresult) == 1) {
      $vrow = mysql_fetch_array($vresult);
      $ulos .= " $vrow[nimi]";
    }

    $ulos .= "</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "projekti" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "kustp") {

  if (mysql_field_name($result, $i) == "projekti") $kptyyppi = "P";
  if (mysql_field_name($result, $i) == "kohde")   $kptyyppi = "O";
  if (mysql_field_name($result, $i) == "kustp")   $kptyyppi = "K";

  $tpque = "SELECT tunnus, nimi, koodi
            from kustannuspaikka
            where yhtio   = '$kukarow[yhtio]'
            and tyyppi    = '$kptyyppi'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($tpque);

  $sel="";
  if ($trow[$i] == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "toim_automaattinen_jtraportti") {

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos = "<td><select name='$nimi'><option value=''>".t("Ei")."</option>";
  $ulos .= "<option value = 'pv' $sel[pv]>".t("Päivittäin")."</option>";
  $ulos .= "<option value = 'vk' $sel[vk]>".t("Viikottain")."</option>";
  $ulos .= "<option value = '2vk' {$sel["2vk"]}>".t("Joka toinen viikko")."</option>";
  $ulos .= "<option value = 'kk' $sel[kk]>".t("Kuukausittain")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ostotilauksen_kasittely") {

  $myynnista_osto_avainsanat = t_avainsana("MYYNNISTA_OSTO");

  if (mysql_num_rows($myynnista_osto_avainsanat) > 0) {

    $ulos  = "<td><select name='{$nimi}'>";
    $ulos .= "<option value=''>".t("Ei valintaa")."</option>";

    while ($myynnista_osto_avainsanat_row = mysql_fetch_assoc($myynnista_osto_avainsanat)) {

      $sel = $myynnista_osto_avainsanat_row['selite'] == $trow[$i] ? " selected" : "";

      $ulos .= "<option value='{$myynnista_osto_avainsanat_row['selite']}'{$sel}>{$myynnista_osto_avainsanat_row['selitetark']}</option>";
    }

    $ulos .= "</select></td>";
  }
  else {
    $ulos .= "<td></td>";
  }

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "sahkoinen_automaattituloutus") {

  $sel = array($trow[$i] => 'selected') + array('K' => '', 'J' => '');

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Sähköisen laskun sisäänluvun yhteydessä ei oletuksena tulouteta tuotteita varastoon")."</option>";
  $ulos .= "<option value='K'{$sel["K"]}>".t("Tuotteet tuloutetaan varastoon automaattisesti sähköisen laskun sisäänluvun yhteydessä")."</option>";
  $ulos .= "<option value='J'{$sel["J"]}>".t("Tuotteet tuloutetaan varastoon ja jälkilasketaan automaattisesti sähköisen laskun sisäänluvun yhteydessä")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
