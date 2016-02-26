<?php

// puun_alkiorivi.inc

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (!isset($apu)) {
  $apu = array();
}

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "laji") {

  $apu['laji'] = $laji;

  $ulos .= "<td>".ucwords($laji)."<input type='hidden' name='$nimi' value='$laji'></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "liitos") {

  if ($lukitse_avaimeen != "") {
    $apu['liitos'] = $lukitse_avaimeen;
    $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
  }
  elseif (isset($alasveto[$i])) {
    $apu['liitos'] = $alasveto[$i];
    $ulos = "<td>".$alasveto[$i]."</td>";
  }
  else {
    $apu['liitos'] = $trow[$i];
    $ulos = "<td>".$trow[$i]."</td>";
  }
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kutsuja") {

  if (isset($alasveto[$i])) {
    $apu['kutsuja'] = $alasveto[$i];
    $ulos = "<td>".$alasveto[$i]."</td>";
  }
  else {
    $apu['kutsuja'] = $trow[$i];
    $ulos = "<td>".$trow[$i]."</td>";
  }

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "puun_tunnus") {

  if ($apu['laji'] != '') {

    $alkio_nimi = '';

    if (trim($trow[$i]) != '') {
      $query = "SELECT nimi
                FROM dynaaminen_puu
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND laji    = '{$apu['laji']}'
                AND tunnus  = '{$trow[$i]}'";
      $vres = pupe_query($query);
      $nimirow = mysql_fetch_assoc($vres);
      $alkio_nimi = $nimirow['nimi'];
    }

    $query = "SELECT group_concat(distinct puun_tunnus) nodet
              FROM puun_alkio
              WHERE yhtio = '{$kukarow["yhtio"]}'
              and liitos  = '{$apu["liitos"]}'
              and kieli   = '{$apu["kieli"]}'
              and laji    = '{$apu["laji"]}'";
    $vres = pupe_query($query);
    $vrow = mysql_fetch_array($vres);

    $mista = !empty($mista) ? $mista : $apu['laji'];

    $ulos = "<td><a href='{$palvelin2}dynaaminen_puu.php?toim={$apu['laji']}&tee=valitsesegmentti&mista={$mista}&liitos=".urlencode($apu['liitos'])."&puun_tunnus={$vrow["nodet"]}&kieli={$apu['kieli']}&lopetus=$lopetus_muut' target='mainframe'>".t('Valitse segmentit')."</a><br />\n";
    $ulos .= "<br /><input type='hidden' name='$nimi' value='{$trow[$i]}' /> {$trow[$i]} {$alkio_nimi}</td>\n";
  }

  $jatko = 0;
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
