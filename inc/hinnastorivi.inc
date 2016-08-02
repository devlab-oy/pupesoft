<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

// yhtiötä ei näytetä
if ($i == 0) {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tuoteno") {
  if ($lukitse_avaimeen != "") {
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "valkoodi") {
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
             ORDER BY jarjestys";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['nimi']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "laji") {

  $chkn = $chke = $chko = "";

  if ($trow[$i] == 'N') {
    $chkn = 'SELECTED';
  }
  elseif ($trow[$i] == "E") {
    $chke = 'SELECTED';
  }
  elseif ($trow[$i] == "O") {
    $chko = 'SELECTED';
  }
  elseif ($trow[$i] == "K") {
    $chkk = 'SELECTED';
  }

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Bruttohinta Myyntihinta")."</option>";
  $ulos .= "<option value='N' $chkn>".t("N-Nettohinta Myyntihinta")."</option>";
  $ulos .= "<option value='E' $chke>".t("E-Nettohinta Myyntihinta")."</option>";
  $ulos .= "<option value='O' $chko>".t("Ostohinta")."</option>";
  $ulos .= "<option value='K' $chkk>".t("Informatiivinen hinta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "maa") {

  $query = "SELECT distinct koodi, nimi
            FROM maat
            where nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);

  $ulos =  "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Kaikki maat")."</option>";

  if ($trow[$i] == "" and $t[$i] == "") {
    $trow[$i] = $yhtiorow["maa"];
  }

  while ($row = mysql_fetch_array($vresult)) {
    $sel = '';
    if ($row[0] == $trow[$i]) {
      $sel = 'selected';
    }
    $ulos .= "<option value='$row[0]' $sel>$row[1]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;

}

if (mysql_field_name($result, $i) == "yhtion_toimipaikka_id") {
  $toimipaikat = hae_toimipaikat();

  if (!empty($toimipaikat)) {
    $ulos = "<tr><th><label for='yhtion_toimipaikka_id'>";
    $ulos .= t("Yhtion toimipaikka");
    $ulos .= "</label></th><td><select id='yhtion_toimipaikka_id' name='{$nimi}'><option>";
    $ulos .= t("Hinnasto voimassa kaikissa toimipaikoissa");
    $ulos .= "</option>";

    foreach ($toimipaikat as $toimipaikka) {
      $sel = $toimipaikka["tunnus"] == $trow[$i] ? " selected" : "";

      $ulos .= "<option value='{$toimipaikka["tunnus"]}'{$sel}>{$toimipaikka["nimi"]}</option>";
    }

    $ulos .= "</select></td></tr>";

    $jatko  = 0;
  }

  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "campaign_id") {

  $query = "SELECT campaigns.*
            FROM campaigns
            JOIN yhtio ON (campaigns.company_id = yhtio.tunnus and yhtio.yhtio = '{$kukarow['yhtio']}')
            WHERE campaigns.active = 1";
  $kresult = pupe_query($query);

  $ulos = "<td><select id='campaign_id' name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = 'NULL'>".t("Ei kampanjaa")."</option>";
  while ($krow = mysql_fetch_assoc($kresult)) {
    $sel = '';
    if (strtoupper($trow[$i]) == strtoupper($krow["id"])) {
      $sel = "selected";
    }
    $ulos .= "<option value='{$krow['id']}' $sel>{$krow['name']}</option>";
  }

  $ulos .= "</select></td>\n";
  $jatko = 0;
}