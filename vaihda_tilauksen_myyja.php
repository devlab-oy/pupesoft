<?php

require ("inc/parametrit.inc");

enable_ajax();

if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if (!isset($sopparit))   $sopparit = "";
if (!isset($riveittain)) $riveittain = "";
if (!isset($tee))      $tee = "";

if ($tee == 'PAIVITAMYYJA' and (int) $tunnus > 0 and (int) $myyja > 0) {

  $query = "  UPDATE lasku
        SET myyja = '$myyja'
        WHERE yhtio = '{$kukarow['yhtio']}'
        and tunnus  = '$tunnus'
        and tila   = 'L'
        and alatila = 'X'";
  pupe_query($query);

  die("Myyj� p�ivitetty!");
}

if ($tee == 'PAIVITARIVIMYYJA' and (int) $rivitunnus > 0 and $myyja != "") {

  $query = "  UPDATE tilausrivin_lisatiedot
        SET positio = '$myyja'
        WHERE yhtio = '{$kukarow['yhtio']}'
        and tilausrivitunnus = '$rivitunnus'";
  pupe_query($query);

  die("Myyj� p�ivitetty!");
}

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

  require ("raportit/naytatilaus.inc");
  echo "<hr>";
  exit;
}

echo "<font class='head'>".t("Vaihda tilauksen myyj�").":</font><hr>";
echo "<form method='post'>";
echo "<table>";

echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr>";
echo "<tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>";
echo "</tr>";

$rukchk = "";
if ($sopparit  != '') $rukchk = "CHECKED";

echo "<tr><th>".t("Piilota yll�pitosopimukset")."</th>
    <td colspan='3'><input type='checkbox' name='sopparit' value='YLLARI' $rukchk></td>";
echo "</tr>";

$rukchk = "";
if ($riveittain  != '') $rukchk = "CHECKED";

echo "<tr><th>".t("Riveitt�in")."</th>
    <td colspan='3'><input type='checkbox' name='riveittain' value='RIVI' $rukchk></td>";
echo "</tr>";

$rukchk = "";
if ($puuttuvat  != '') $rukchk = "CHECKED";

echo "<tr><th>".t("N�yt� vain ne josta myyj� puuttuu")."</th>
    <td colspan='3'><input type='checkbox' name='puuttuvat' value='VAIN' $rukchk></td>";
echo "</tr>";


$query = "  SELECT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
      FROM kuka
      WHERE kuka.yhtio = '$kukarow[yhtio]'
      ORDER BY kuka.nimi";
$myyjares = pupe_query($query);

echo "<tr><th>".t("N�yt� vain myyj�n tilaukset")."</th><td colspan='3'><select name='laskumyyja'>";
echo "<option value=''>".t("Valitse")."</option>";

while ($myyjarow = mysql_fetch_assoc($myyjares)) {
  $sel = "";

  if ($myyjarow['tunnus'] == $laskumyyja) {
    $sel = 'selected';
  }

  echo "<option value='$myyjarow[tunnus]' $sel>$myyjarow[nimi]</option>";
}

echo "</select></td></tr>";
echo "</table><br>";

$soplisa = "";

if ($sopparit != "") {
  $soplisa = " AND lasku.clearing != 'sopimus' ";
}

$rivilisa     = "";
$rivijoin     = "";
$puuttuvatlisa  = "";

if ($riveittain != "") {
  $rivilisa = " tilausrivi.rivihinta, tilausrivi.kommentti rivikommetti, tilausrivi.tunnus rivitunnus, tilausrivin_lisatiedot.positio, ";
  $rivijoin = " JOIN tilausrivi ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'L')
          JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus) ";

  if ($puuttuvat != "") {
    $puuttuvatlisa = " AND tilausrivin_lisatiedot.positio = '' ";
  }
}
elseif ($puuttuvat != "") {
  $puuttuvatlisa = " AND lasku.myyja = 0 ";
}

$lasmylis = "";

if ($laskumyyja != "") {
  $lasmylis = " AND lasku.myyja = $laskumyyja ";
}

$query = "  SELECT
      lasku.tunnus,
      lasku.laskunro,
      concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas,
      lasku.summa,
      lasku.viesti tilausviite,
      $rivilisa
      lasku.myyja,
      kuka.nimi
      FROM lasku
      $rivijoin
      LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
      WHERE lasku.yhtio = '{$kukarow['yhtio']}'
      and lasku.tila     = 'L'
      and lasku.alatila = 'X'
      and lasku.tapvm  >= '$vva-$kka-$ppa'
      and lasku.tapvm  <= '$vvl-$kkl-$ppl'
      {$soplisa}
      {$puuttuvatlisa}
      {$lasmylis}
      ORDER BY 1";
$result = pupe_query($query);

echo "<input type='submit' value='".t("N�yt� tilaukset")."'>";
echo "</form><br><br>";

if (mysql_num_rows($result) > 0) {

  if ($riveittain != "") {
    $query = "  SELECT selite, selitetark
          FROM avainsana
          WHERE yhtio = '$kukarow[yhtio]'
          AND laji    = 'TRIVITYYPPI'
           ORDER BY selitetark";
    $myyjares = pupe_query($query);
  }
  else {
    $query = "  SELECT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
          FROM kuka
          WHERE kuka.yhtio = '$kukarow[yhtio]'
          ORDER BY kuka.nimi";
    $myyjares = pupe_query($query);
  }

  echo "<br>";
  echo "<table>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Tilausnumero")."</th>";
  echo "<th>".t("Laskunro")."</th>";
  echo "<th>".t("Asiakas")."</th>";
  echo "<th>".t("Summa")."</th>";
  echo "<th>".t("Tilausviite")."</th>";
  echo "<th>".t("Myyj�")."</th>";

  if ($riveittain != "") {
    echo "<th>".t("Rivimyyj�")."</th>";
  }

  echo "</tr>";
  echo "</thead>";
  echo "<tbody>";

  while ($row = mysql_fetch_assoc($result)) {

    echo "<tr>";

    $class = "";
    if (isset($tunnus) and $tunnus == $row['tunnus']) {
      $class = " class='tumma' ";
    }

    echo "<td valign='top' $class>{$row['tunnus']}</td>";
    echo "<td valign='top' $class>{$row['laskunro']}</td>";
    echo "<td valign='top' $class>{$row['asiakas']}</td>";

    if ($riveittain != "") {
      echo "<td valign='top' align='right' $class>".sprintf('%.2f', $row['rivihinta'])."</td>";
    }
    else {
      echo "<td valign='top' align='right' $class>{$row['summa']}</td>";
    }

    echo "<td valign='top' $class>{$row['tilausviite']}";

    if ($riveittain != "") {
      if ($row['tilausviite'] != "") echo "<br>";
      echo "{$row['rivikommetti']}";
    }
    else {
      $query = "  SELECT DISTINCT kommentti
            FROM tilausrivi
            WHERE yhtio = '$kukarow[yhtio]'
            AND otunnus = '$row[tunnus]'
            and tyyppi  = 'L'
            AND kommentti != ''";
      $kommres = pupe_query($query);

      while ($kommrow = mysql_fetch_assoc($kommres)) {
        echo "<br>{$kommrow['kommentti']}";
      }
    }

    echo "</td>";

    if ($riveittain != "") {
      echo "<td valign='top' $class>$row[nimi]</td>";
      echo "<td valign='top' $class><a name='$row[tunnus]'>";
      echo "<form name='myyjaformi_$row[rivitunnus]' id='myyjaformi_$row[rivitunnus]' action=\"javascript:ajaxPost('myyjaformi_$row[rivitunnus]', '{$palvelin2}vaihda_tilauksen_myyja.php?', 'div_$row[rivitunnus]', '', '', '', 'post');\" method='POST'>
          <input type='hidden' name='tee'   value = 'PAIVITARIVIMYYJA'>
          <input type='hidden' name='rivitunnus'   value = '$row[rivitunnus]'>";

      echo "<select name='myyja' onchange='submit();'>";
      echo "<option value=''>".t("Valitse")."</option>";

      mysql_data_seek($myyjares, 0);

      while ($myyjarow = mysql_fetch_assoc($myyjares)) {
        $sel = "";

        if ($myyjarow['selite'] == $row['positio']) {
          $sel = 'selected';
        }

        echo "<option value='$myyjarow[selite]' $sel>$myyjarow[selitetark]</option>";
      }

      echo "</select></form><div id='div_$row[rivitunnus]'></div></td>";
    }
    else {
      echo "<td valign='top' $class><a name='$row[tunnus]'>";
      echo "<form name='myyjaformi_$row[tunnus]' id='myyjaformi_$row[tunnus]' action=\"javascript:ajaxPost('myyjaformi_$row[tunnus]', '{$palvelin2}vaihda_tilauksen_myyja.php?', 'div_$row[tunnus]', '', '', '', 'post');\" method='POST'>
          <input type='hidden' name='tee'   value = 'PAIVITAMYYJA'>
          <input type='hidden' name='tunnus'   value = '$row[tunnus]'>";

      echo "<select name='myyja' onchange='submit();'>";

      mysql_data_seek($myyjares, 0);

      while ($myyjarow = mysql_fetch_assoc($myyjares)) {
        $sel = "";

        if ($myyjarow['tunnus'] == $row['myyja']) {
          $sel = 'selected';
        }

        echo "<option value='$myyjarow[tunnus]' $sel>$myyjarow[nimi]</option>";
      }

      echo "</select></form><div id='div_$row[tunnus]'></div></td>";
    }

    echo "<td class='back' valign='top'><a href='' onclick=\"window.open('{$palvelin2}vaihda_tilauksen_myyja.php?tee=NAYTATILAUS&tunnus=$row[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1000,height=600'); return false;\">".t("N�yt� tilaus")."</a></td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}
else {
  echo t("Ei tilauksia")."...<br><br>";
}

require ("inc/footer.inc");
