<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "filename" or
  mysql_field_name($result, $i) == "filesize" or
  mysql_field_name($result, $i) == "filetype" or
  mysql_field_name($result, $i) == "image_width" or
  mysql_field_name($result, $i) == "image_height" or
  mysql_field_name($result, $i) == "image_bits" or
  mysql_field_name($result, $i) == "image_channels") {
  $tyyppi = 2;
}

if (mysql_field_name($result, $i) == "liitos") {
  $tyyppi = 4;

  if ($lukitse_laji != "") {
    $ulos = "<input type='hidden' name='$nimi' value='$lukitse_laji'>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "liitostunnus") {
  $tyyppi = 4;

  if ($lukitse_avaimeen != "") {
    $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
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

if (mysql_field_name($result, $i) == "kayttotarkoitus") {

  $ulos = "<td><select name='$nimi'>";
  $sel  = "";

  if (in_array($lukitse_laji, array("tuote", "asiakas"))) {
    $kires = t_avainsana("LITETY");
  }
  elseif ($lukitse_laji == "lasku" and !in_array($lasrow['tila'], array("H", "Y", "M", "P", "Q", "X"))) {
    $liitemuok_ok = true;

    $kires = t_avainsana("TIL-LITETY", "", "ORDER BY selitetark_2 DESC, jarjestys, selite");
  }

  $natsasko = FALSE;

  if (is_resource($kires)) {
    while ($kirow = mysql_fetch_array($kires)) {
      if ($kirow["selite"] == $trow[$i]) {
        $select = 'SELECTED';
        $natsasko = TRUE;
      }
      else $select = '';

      if ($kirow["selitetark_2"] == "PAKOLLINEN") {
        $paklisa = "*";
      }
      else {
        $paklisa = "";
      }

      $ulos .= "<option value='$kirow[selite]' $select>$paklisa $kirow[selitetark]</option>";
    }

    $ulos .= "<option value='MU'>".t("Yleinen")."</option>";
  }
  else {
    $ulos .= "<option value='MU'>".t("Yleinen")."</option>";
  }

  // Jos LITETY-avainsanaa ei ollut mutta kannassa on kuitenkin jokin tieto jo niin pidetään se selectöitynä
  if (!$natsasko and $trow[$i] != "") {
    $ulos .= "<option value= '$trow[$i]' SELECTED>$trow[$i]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "filesize" and $tunnus != "") {
  $ulos = "<td>".size_readable($trow[$i])."</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "data") {

  $path_parts = pathinfo($trow['filename']);
  $ext = $path_parts['extension'];

  if (strtoupper($ext) == "JPEG" or strtoupper($ext) == "JPG" or strtoupper($ext) == "GIF" or strtoupper($ext) == "PNG") {
    $ulos = "<td><a href='view.php?id=$trow[tunnus]' target='Attachment'><img src='view.php?id=$trow[tunnus]' height='80px'></a>";
  }
  else {
    $ulos = "<td><a href='view.php?id=$trow[tunnus]' target='Attachment'>$trow[filename]</a>";
  }

  if ($uusilukko == "" and ($poistolukko == "" or $tunnus == "")) {
    $ulos .= "<input type = 'file' name = 'liite_data'></td>";
  }

  $jatko = 0;
}
