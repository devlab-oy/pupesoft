<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys


if (substr(mysql_field_name($result, $i), 0, 9) == 'printteri') {

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos = "<td><select name='$nimi'>";

  $query = "SELECT *
            from kirjoittimet
            where yhtio  = '$kukarow[yhtio]'
            AND komento != 'EDI'
            ORDER BY kirjoitin";
  $kires = pupe_query($query);

  $ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

  while ($kirow = mysql_fetch_array($kires)) {
    $ulos .= "<option value='$kirow[tunnus]' ";
    if (isset($sel[$kirow["tunnus"]])) $ulos .= $sel[$kirow["tunnus"]];
    $ulos .=">$kirow[kirjoitin]</option>";
  }

  $ulos .= "<option value='-88' ";
  if (isset($sel["-88"])) $ulos .= $sel["-88"];
  $ulos .=">".t("PDF Ruudulle")."</option>";
  $ulos .= "</select></td>";

  $jatko=0;
}

if (mysql_field_name($result, $i) == 'tyyppi') {
  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value=''>".t("Normaalivarasto josta kaikki myyvät")."</option>";
  $ulos .= "<option value='V' $sel[V]>".t("Erikoisvarasto josta voi myydä")."</option>";
  $ulos .= "<option value='E' $sel[E]>".t("Erikoisvarasto josta automaattisesti ei myydä")."</option>";
  $ulos .= "<option value='P' $sel[P]>".t("Poistettu varasto")."</option>";
  $ulos .= "</select>";

  $ulos .= "</select></td>";

  $jatko=0;
}

if (mysql_field_name($result, $i) == "isa_varasto") {
  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio      = '$kukarow[yhtio]'
            AND tyyppi      != 'P'
            AND isa_varasto  = 0
            AND tunnus      != '{$trow['tunnus']}'";
  $vresult =  pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  $ulos .= "<option value='0'>Ei isävarastoa</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";

    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }

    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[nimitys]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "nouto") {

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value = ''>".t("Valitse")."</option>";

  $chk = '';

  if ($trow[$i] == 1) {
    $chk = 'selected';
  }

  $ulos .= "<option value='0'>".t("Ei näytetä Extranetin Hae ja selaa-ohjelmassa noutovarastona")."</option>";
  $ulos .= "<option value='1' $chk>".t("Näytetään Extranetin Hae ja selaa-ohjelmassa noutovarastona")."</option>";

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "maa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value = '' ></option>";

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

if (mysql_field_name($result, $i) == "tunnus" or
  mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tuoteno" and $uusi != 0) {
  $tyyppi = 1;
}

if (mysql_field_name($result, $i) == "sisamaan_kuljetusmuoto" or mysql_field_name($result, $i) == "kuljetusmuoto") {

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = '' >".t("Valitse")."</option>";

  $vresult = t_avainsana("KM");

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selite']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kontti") {

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value = ''>".t("Valitse")."</option>";

  $chk1 = '';
  $chk2 = '';

  if ($trow[$i] == "1") {
    $chk1 = 'selected';
  }
  if ($trow[$i] == "0") {
    $chk2 = 'selected';
  }

  $ulos .= "<option value='1' $chk1>".t("Kyllä")."</option>";
  $ulos .= "<option value='0' $chk2>".t("Ei")."</option>";

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = '' >".t("Valitse")."</option>";

  $vresult = t_avainsana("KT");

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selite']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "poistumistoimipaikka_koodi") {

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = ''>".t("Valitse")."</option>";

  $vresult = t_avainsana("TULLI");

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selite']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "toimipaikka") {

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = ''>".t("Valitse toimipaikka")."</option>";

  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND vat_numero = ''
            ORDER BY nimi";
  $vresult = pupe_query($query);

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['tunnus']) {
      $sel = "selected";
    }

    $ulos .= "<option value='{$vrow['tunnus']}' {$sel}>{$vrow['ovtlisa']} {$vrow['nimi']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ulkoinen_jarjestelma") {
  $ulos = "<td>";

  $sel = array();
  $sel[$trow[$i]] = " selected";

  $ulos .= "<select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Ei käytössä")."</option>";
  $ulos .= "<option value='K'{$sel['K']}>".t("Kardex")."</option>";
  $ulos .= "<option value='V'{$sel['V']}>".t("Vocollect")."</option>";
  $ulos .= "<option value='U'{$sel['U']}>".t("Unikko")."</option>";
  $ulos .= "<option value='G'{$sel['G']}>".t("Geodis Wilson")."</option>";
  $ulos .= "<option value='C'{$sel['C']}>".t("Kiinalainen tehdas")."</option>";
  $ulos .= "<option value='P'{$sel['P']}>".t("PostNord")."</option>";
  $ulos .= "<option value='L'{$sel['L']}>".t("Velox")."</option>";
  $ulos .= "</select>";
  $ulos .= "</td>";
  $jatko = 0;
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
