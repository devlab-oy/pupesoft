<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

// yhtiötä ei näytetä
if ($i == 0) {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "valkoodi") {
  $query = "  SELECT nimi, tunnus
                FROM valuu
               WHERE yhtio = '$kukarow[yhtio]'
                 ORDER BY jarjestys";
  $vresult = mysql_query($query) or pupe_error($query);

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
  $ulos .= "<option value='K' $chkk>".t("Informatiivinen selainhinta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "maa") {

  $query = "  SELECT distinct koodi, nimi
        FROM maat
        where nimi != ''
        ORDER BY koodi";
  $vresult = mysql_query($query) or pupe_error($query);

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
