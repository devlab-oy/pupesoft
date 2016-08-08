<?php

$ulos = "";
$jatko = 1;   // oletetaan normaali käsittely
$tyyppi = 1;   // oletetaan rivin näkyvyys

if ($i == 0) {   //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "piiri") {
  $preq = "SELECT avainsana.selite piiri, avainsana.selitetark selitetark
           FROM avainsana
           WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='piiri'";
  $preres = pupe_query($preq);

  $ulos = "<td><select id='piiri' name='$nimi'>";
  $ulos .= "<option value = ''>".t("Ei piiriä")."</option>";

  while ($srow = mysql_fetch_array($preres)) {
    $sel = '';
    if (strtoupper($trow[$i]) == strtoupper($srow["piiri"])) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[piiri]' $sel>$srow[selitetark]</option>";
  }

  $ulos .= "</select></td>\n";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "asiakas_segmentti") {

  $preq = "SELECT CONCAT(REPEAT('&raquo;', COUNT(parent.tunnus) - 1), ' ', ifnull(node.koodi, ''), ' ', node.nimi) AS name, node.koodi koodi, node.tunnus
           FROM dynaaminen_puu AS node
           JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
           WHERE node.yhtio = '$kukarow[yhtio]'
           AND node.lft     > 0
           AND node.laji    = 'asiakas'
           GROUP BY node.tunnus
           ORDER BY node.lft";
  $preres = pupe_query($preq);

  $ulos = "<td><select id='asiakas_segmentti' name='$nimi'>";
  $ulos .= "<option value=''>".t("Ei asiakassegmenttiä")."</option>";

  while ($prerow = mysql_fetch_array($preres)) {
    $sel = '';
    if ($trow[$i] == $prerow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value='$prerow[tunnus]' $sel>$prerow[name]</option>";
  }
  $ulos .= "</select></td>\n";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ryhma") {

  $query = "SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
            FROM perusalennus
            WHERE yhtio='$kukarow[yhtio]'
            ORDER BY ryhma";
  $sresult = pupe_query($query);

  $ulos = "<td><select id='aleryhma' name='$nimi'>";
  $ulos .= "<option value = ''>".t("Ei ryhmää")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if (strtoupper($trow[$i]) == strtoupper($srow["ryhma"])) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
  }

  $ulos .= "</select>";
  $ulos .= " <a href='#' onclick=\"js_open_yllapito('aleryhma','toim=perusalennus');\"><img src='pics/lullacons/add.png'>".t("Uusi ryhmä")."</a></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "asiakas_ryhma" and $lukitse_avaimeen != "") {
  $tyyppi = 4;
}
elseif (mysql_field_name($result, $i) == "asiakas_ryhma") {

  $vresult = t_avainsana("ASIAKASRYHMA");

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = '' >".t("Ei ryhmää")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow['selite'])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "valkoodi") {
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
             ORDER BY jarjestys";
  $vresult = pupe_query($query);

  // Jos trow valkoodi on tyhjä niin näytetään oletuksena
  // yhtiön valkoodi ekana
  if ($trow[$i] == "") {
    $trow[$i] = $yhtiorow["valkoodi"];
  }

  $ulos = "<td><select name='$nimi'>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel = "";
    if ($trow[$i] == $vrow['nimi']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "laji") {

  $chkn = array();
  $chkn[$trow[$i]] = "SELECTED";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Yhtiön oletus")."</option>";
  $ulos .= "<option value='N' $chkn[N]>".t("N-Nettohinta")."</option>";
  $ulos .= "<option value='E' $chkn[E]>".t("E-Nettohinta")."</option>";
  $ulos .= "<option value='B' $chkn[B]>".t("Bruttohinta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ytunnus" and $lukitse_avaimeen != "") {
  // Jos tämä on liitetty ytunnuksella:
  if ($trow[$i] != "" and $trow[$i] != "0") {
    $ulos = "<input type='hidden' name='$nimi' value='$trow[$i]'>";
  }

  $tyyppi = 4;
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "ytunnus") {
  // Ei maxlength:iä koska silloin hakutoiminto ei välttämättä toimi
  $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size'></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "asiakas" and $lukitse_avaimeen != "") {
  // Jos tämä on liitetty tunnuksella:
  if ($trow[$i] > 0) {
    $ulos = "<input type='hidden' name='$nimi' value='$trow[$i]'>";
  }
  // Jos tää on uusi itemi
  elseif ((int) $tunnus == 0) {
    $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
  }

  $tyyppi = 4;
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "asiakas" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
}
elseif (mysql_field_name($result, $i) == "asiakas") {
  if ((int) $trow[$i] > 0) {
    $query  = "SELECT *
               FROM asiakas
               WHERE yhtio='$kukarow[yhtio]' and tunnus='$trow[$i]'";
    $sresult = pupe_query($query);
    $srow = mysql_fetch_array($sresult);
    $ssize = strlen($trow[$i])+2;

    $ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize'> <table><tr><td>$srow[nimi] $srow[nimitark]</td></tr><tr><td>$srow[toim_nimi] $srow[toim_nimitark]</td></tr></table></td>";
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

if (mysql_field_name($result, $i) == "tuoteno" and isset($alasveto[$i])) {
  $ulos = "<td>".$alasveto[$i]."</td>";
  $jatko = 0;
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
