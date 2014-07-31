<?php

$ulos = "";
$jatko = 1; // oletetaan normaali k‰sittely
$tyyppi = 1; // oletetaan rivin n‰kyvyys

if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
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

if (mysql_field_name($result, $i) == "ryhma") {

  $query = "SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
            FROM perusalennus
            WHERE yhtio='$kukarow[yhtio]'
            ORDER BY ryhma";
  $sresult = mysql_query($query) or pupe_error($query);

  $ulos = "<td><select id='ryhma' name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = ''>".t("Ei ryhm‰‰")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if (strtoupper($trow[$i]) == strtoupper($srow["ryhma"])) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
  }

  $ulos .= "</select>\n";
  $ulos .= " <a href='#' onclick=\"js_open_yllapito('ryhma','toim=perusalennus');\"><img src='pics/lullacons/add.png'>".t("Uusi ryhm‰")."</a></td>\n";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "ytunnus") {
  // Ei maxlength:i‰ koska silloin hakutoiminto ei v‰ltt‰m‰tt‰ toimi
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

    // Ei maxlength:i‰ koska silloin hakutoiminto ei v‰ltt‰m‰tt‰ toimi
    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size'></td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "alkupvm" or mysql_field_name($result, $i) == "loppupvm") {
  $tyyppi = 1.5;
}

if (mysql_field_name($result, $i) == "monikerta") {
  $sel = "";
  if ($trow[$i] != "") $sel = "SELECTED";

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = ''>".t("Ei. Alennus on voimassa kun tilataan v‰hintaan minkpl verran")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("Kyll‰. Alennus on voimassa kun tilataan minkpl verran, tai mink‰ tahansa minkpl monikerran verran")."</option>";
  $ulos .= "</select></td>\n";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "alennuslaji") {
  $sel = array_fill_keys(array($trow[$i]), " selected") + array_fill(1, $yhtiorow['oston_alekentat'], '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
  }

  $ulos .= "</select></td>\n";
  $jatko = 0;
}
