<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Sanakirja")."</font><hr>";

echo "<form method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Valitse k‰‰nnett‰v‰t kielet")."</th>";
echo "<td><table>";

$r = 0;

// k‰yd‰‰n l‰pi mahdolliset kielet
foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
  if ($sanakirja_kieli == "fi") continue;

  $sels = '';
  if (strtoupper($kieli[$r]) == strtoupper($sanakirja_kieli)) {
    $sels = "CHECKED";
  }

  echo "<tr><td>".t($sanakirja_kieli_nimi)."</td><td><input type='checkbox' name='kieli[$r]' value='$sanakirja_kieli' $sels></td></tr>";
  $r++;
}

echo "</table></td>";
echo "</tr><tr>";

if ($show == "all") {
  $sel1 = 'CHECKED';
}
else {
  $sel2 = 'CHECKED';
}

echo "<th>".t("N‰yt‰ kaikki lauseet")."</th>";
echo "<td><input type='radio' name='show' value='all' $sel1></td>";
echo "</tr><tr>";
echo "<th>".t("N‰yt‰ vain k‰‰nt‰m‰ttˆm‰t lauseet")."</th>";
echo "<td><input type='radio' name='show' value='empty' $sel2></td>";
echo "</tr>";

echo "<tr><td class='back'><br></td></tr>";

echo "<tr><th>".t("Mill‰ kielell‰ etsit‰‰n")."</th>";
echo "<td><select name='etsi_kieli'>";

echo "<option value=''>".t("Ei etsit‰")."</option>";

foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
  $sel = '';
  if ($sanakirja_kieli == $etsi_kieli) $sel = "SELECTED";

  echo "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
}

echo "</select></td>";
echo "</tr>";
echo "<tr><th>".t("Hakusana")."</th><td><textarea name='hakusana' rows='5' cols='25'>$hakusana</textarea></td></tr>";

if ($tarkkahaku != "") {
  $tahas = "CHECKED";
}

echo "<tr><th>".t("Tarkka haku")."</th><td><input type='checkbox' name='tarkkahaku' value='ON' $tahas></td>";

echo "<td class='back'><input name='nappi1' type='submit' value='".t("Hae")."'></td></tr>";
echo "</table><br>";

if ($maxtunnus > 0) {
  // k‰yd‰‰n l‰pi mahdolliset kielet
  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    for ($i=0; $i<=$maxtunnus; $i++) {
      // eli etsit‰‰n kielen nimisesta arraysta indexill‰ $i
      if (${$sanakirja_kieli}[$i] != "") {
        $value = addslashes(trim(${$sanakirja_kieli}[$i])); // spacella pystyy tyhjent‰m‰‰n kent‰n, mutta ei tallenneta sit‰

        $query = "UPDATE sanakirja set $sanakirja_kieli='$value', muuttaja='$kukarow[kuka]', muutospvm=now() where tunnus='$i'";
        $result = pupe_query($query);
      }
    }
  }

  echo "<font class='message'>".t("Sanakirja p‰ivitetty!")."</font><br><br>";
  $tee = "";
}

// jos meill‰ on onnistuneesti valittu kieli
if (count($kieli) > 0) {

  $query  = "select tunnus, fi ";

  foreach ($kieli as $ki) {
    $query .= ", $ki";
  }

  $query .= " from sanakirja ";


  if (($etsi_kieli != '' and isset($hakusana) and trim($hakusana) != '') or $show == 'empty') {
    $query .= " where ";
  }

  if ($etsi_kieli != '') {

    if (isset($hakusana) and $hakusana != '') {
      $sanat = explode("\n", $hakusana);

      $query .= " (";

      $sanarajaus = "";
      foreach ($sanat as $sana) {
        if (trim($sana) != '') {

          if ($tarkkahaku == "") {
            $query .= " $etsi_kieli like '%".trim($sana)."%' or ";
          }
          else {
            $query .= " $etsi_kieli = '".trim($sana)."' or ";
          }
        }
      }

      $query = substr($query, 0, -3).") ";
    }
  }

  if ($show == "empty") {

    if ($etsi_kieli != '' and (isset($hakusana) and $hakusana != '')) {
      $query .= " and ";
    }

    $query .= " (fi='' ";
    foreach ($kieli as $ki) {
      $query .= "or $ki='' ";
    }
    $query .= ")";
  }

  $query .= " ORDER BY luontiaika desc,fi ";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<table>";
    echo "<tr>";
    for ($i=1; $i<mysql_num_fields($result); $i++) echo "<th>".mysql_field_name($result, $i)."</th>";
    echo "</tr>";

    $laskkaannos = 0;

    while ($row = mysql_fetch_array($result)) {
      echo "<tr class='aktiivi'>";
      echo "<td>$row[fi]</td>";

      if ($row['tunnus'] > $maxtunnus) $maxtunnus = $row['tunnus'];

      for ($i=2; $i<mysql_num_fields($result); $i++) {
        echo "<td><input type='text' size='30' name='".mysql_field_name($result, $i)."[$row[tunnus]]' value='".trim($row[$i])."'></td>";
      }

      $laskkaannos++;

      echo "</tr>";
    }

    echo "<tr><th colspan='".mysql_num_rows($result)."'>".t("Yhteens‰")." $laskkaannos ".t("rivi‰")."</th></tr>";
    echo "</table>";
    echo "<input type='hidden' name='maxtunnus' value='$maxtunnus'>";
    echo "<br><input type='submit' name='paivita' value='".t("P‰ivit‰")."'>";
  }
  else {
    echo "<font class='message'>".t("Haulla ei lˆytynyt yht‰‰n rivi‰!")."</font>";
  }
}
else {
  echo "<br><br><font class='error'>".t("Valitse k‰‰nnett‰v‰ kieli!")."</font><br><br>";
}

require "inc/footer.inc";
