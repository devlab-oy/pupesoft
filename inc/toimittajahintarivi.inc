<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tuoteno" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "tuoteno" and isset($lukitse_avaimeen)) {
  $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "valkoodi") {
  $query = "SELECT nimi, tunnus
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

if (mysql_field_name($result, $i) == "ryhma") {

  $query = "SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
            FROM perusalennus
            WHERE yhtio='$kukarow[yhtio]'
            ORDER BY ryhma";
  $sresult = mysql_query($query) or pupe_error($query);

  $ulos = "<td><select id='ryhma' name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = ''>".t("Ei ryhmää")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if (strtoupper($trow[$i]) == strtoupper($srow["ryhma"])) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
  }

  $ulos .= "</select>\n";
  $ulos .= " <a href='#' onclick=\"js_open_yllapito('ryhma','toim=perusalennus');\"><img src='pics/lullacons/add.png'>".t("Uusi ryhmä")."</a></td>\n";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "ytunnus") {
  // Ei maxlength:iä koska silloin hakutoiminto ei välttämättä toimi
  $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size'></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "toimittaja" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "toimittaja") {
  if ((int) $trow[$i] > 0) {
    $query  = "SELECT *
               FROM toimi
               WHERE yhtio='$kukarow[yhtio]' and tunnus='$trow[$i]'";
    $sresult = mysql_query($query) or pupe_error($query);
    $srow = mysql_fetch_array($sresult);
    $ssize = strlen($trow[$i])+2;

    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize' maxlength='$maxsize'> <table><tr><td>$srow[nimi] $srow[nimitark]</td></tr><tr><td>$srow[toim_nimi] $srow[toim_nimitark]</td></tr></table></td>";
    $jatko = 0;
  }
  else {
    if ($trow[$i] == 0) $trow[$i] = "";

    // Ei maxlength:iä koska silloin hakutoiminto ei välttämättä toimi
    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size'></td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "alkupvm" or mysql_field_name($result, $i) == "loppupvm") {
  $tyyppi = 1.5;
}

if (mysql_field_name($result, $i) == "laji") {

  $chkn = array();
  $chkn[$trow[$i]] = "SELECTED";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='N' $chkn[N]>".t("N-Nettohinta")."</option>";
  $ulos .= "<option value='E' $chkn[E]>".t("E-Nettohinta")."</option>";
  $ulos .= "<option value='B' $chkn[B]>".t("Bruttohinta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}
