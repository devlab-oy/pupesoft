<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys
$_kentta = mysql_field_name($result, $i);

if ($_kentta == "yhtio") {
  $tyyppi = 0;
}

if ($_kentta == "toim_tuoteno_tunnus") {

  if ($lukitse_avaimeen != "") {
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
    $jatko = 0;
  }

}

if ($_kentta == "yksikko") {

  $res = t_avainsana("Y");

  $ulos = "<td>";
  $ulos .= "<select name='$nimi'>";
  
  while ($_yksikko = mysql_fetch_assoc($res)) {
    
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($_yksikko["selite"]) and $_yksikko["selite"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '{$_yksikko['selite']}' $sel>{$_yksikko['selitetark']}</option>";
  }
  
  $ulos .= "</select>";
  $ulos .= "</td>";
  $jatko = 0;

}