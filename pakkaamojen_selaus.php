<?php
require ("inc/parametrit.inc");

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
  require ("raportit/naytatilaus.inc");
}

$logistiikka_yhtio = '';
$logistiikka_yhtiolisa = '';

if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
  $logistiikka_yhtio = $konsernivarasto_yhtiot;
  $logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";
}
else {
  $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
}

echo "<font class='head'>".t("Pakkaamojen selaus")."</font><hr>";

if ($tee == "nollaa") {
  if (isset($checktunnukset) and is_array($checktunnukset)) {
    $tunnukset = implode(',', $checktunnukset);
  }

  if ($tunnukset != "") {
    $query = "   UPDATE lasku
          SET pakkaamo = 0
          WHERE $logistiikka_yhtiolisa
          AND tunnus in($tunnukset)";
    $nollausres = mysql_query($query) or pupe_error($query);

    echo t("Lokerot nollattiin tilauksista")." : ".$tunnukset;
  }
  else {
    echo "<font class='error'>".t("VIRHE: Et valinnut yhtään tilausta")."!</font>";
  }

}

echo "<table>";
echo "<form name='find' method='post'>";

echo "<tr><td>".t("Valitse pakkaamo:")."</td><td><select name='tupakkaamo' onchange='submit()'>";

$query = "  SELECT distinct nimi
      FROM pakkaamo
      WHERE $logistiikka_yhtiolisa
      ORDER BY nimi";
$result = mysql_query($query) or pupe_error($query);

echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

while($row = mysql_fetch_array($result)){
  $sel = '';
  if ($tupakkaamo == '') {
    if($row['nimi'] == $kukarow['oletus_pakkaamo']) {
      $sel = 'selected';
      $tupakkaamo = $row['nimi'];
    }
  }
  else {
    if($row['nimi'] == $tupakkaamo) {
      $sel = 'selected';
      $tupakkaamo = $row['nimi'];
    }
  }

  echo "<option value='$row[nimi]' $sel>".$row["nimi"]."</option>";
}

echo "</select></td></tr>";
echo "</form></table>";

if ($tupakkaamo == '' and $kukarow['oletus_pakkaamo'] != '') {
  $query = "  SELECT group_concat(tunnus SEPARATOR ',') tunnukset
          FROM pakkaamo
        WHERE $logistiikka_yhtiolisa
        AND nimi = '$kukarow[oletus_pakkaamo]'";
  $etsire = mysql_query($query) or pupe_error($query);
  $etsirow = mysql_fetch_array($etsire);

  $haku .= " and lasku.pakkaamo in($etsirow[tunnukset])";

}
elseif ($tupakkaamo != '' and $tupakkaamo != 'KAIKKI') {
  $query = "  SELECT group_concat(tunnus SEPARATOR ',') tunnukset
          FROM pakkaamo
        WHERE $logistiikka_yhtiolisa
        AND nimi = '$tupakkaamo'";
  $etsire = mysql_query($query) or pupe_error($query);
  $etsirow = mysql_fetch_array($etsire);

  $haku .= " and lasku.pakkaamo in($etsirow[tunnukset])";
}

$query = "  SELECT   pakkaamo.nimi pakkaamo,
           pakkaamo.lokero,
          lasku.ytunnus,
          lasku.tunnus,
          lasku.lahetepvm,
          lasku.yhtio,
          lasku.yhtio_nimi,
          if(lasku.tila = 'L', concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark), lasku.nimi) asnimi
          FROM lasku
          JOIN pakkaamo ON (pakkaamo.yhtio = lasku.yhtio and pakkaamo.tunnus = lasku.pakkaamo)
          WHERE lasku.$logistiikka_yhtiolisa
          AND lasku.tila in ('L','G')
          AND lasku.alatila in ('A','C')
          AND lasku.pakkaamo > 0
          $haku
          GROUP BY pakkaamo.nimi, pakkaamo.lokero, lasku.ytunnus, lasku.tunnus, lasku.lahetepvm, lasku.yhtio, lasku.yhtio_nimi, asnimi
          ORDER BY pakkaamo.nimi, pakkaamo.lokero, lasku.ytunnus, lasku.lahetepvm, kerayspvm";
$pakkaamore = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($pakkaamore) != 0) {

  $pakkaamo = $lokero = "";


  //echo "<table><tr><td>";
  echo "<table>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='nollaa'>";
  echo "<input type='hidden' name='tupakkaamo' value='$tupakkaamo'>";
  while ($row = mysql_fetch_array($pakkaamore)) {

    if ($pakkaamo == "" or $pakkaamo != $row['pakkaamo']) {
      echo "<tr><td colspan ='7' class='back'><br><font class='head'>".$row['pakkaamo']."</font></td></tr>";
    }

    if ($lokero == "" or $lokero != $row['lokero']) {

      echo "<tr>";
      if ($yhtiorow['konsernivarasto'] != '') {
        echo "<th valign='top'>".t("Yhtiö")."</th>";
      }
      echo "<th valign='top'>".t("Lokero")."</th>";
      echo "<th valign='top'>".t("Asiakas")."</th>";
      echo "<th valign='top'>".t("Nimi")."</th>";
      echo "<th valign='top'>".t("Tilaus")."</th>";
      echo "<th valign='top'>".t("Tulostettu")."</th>";
      echo "<th valign='top'>".t("Kerätty")."</th>";
      echo "<th valign='top'><input type='submit' value='".t("Nollaa")."'></th>";

      echo "<tr class='aktiivi'>";
      if ($yhtiorow['konsernivarasto'] != '') {
        echo "<td>$row[yhtio_nimi]</td>";
      }
      echo "<td>".$row['lokero']."</td>";
    }
    else {
      echo "<tr class='aktiivi'>";
      echo "<td colspan='";
      if ($yhtiorow['konsernivarasto'] != '') {
        echo "2'>$row[yhtio_nimi]</td>";
      }
      else {
        echo "1'></td>";
      }

    }

    $query = "  SELECT min(tilausrivi.kerattyaika) kerayspvm
          FROM tilausrivi USE INDEX (yhtio_otunnus)
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')
          WHERE tilausrivi.$logistiikka_yhtiolisa
          and tilausrivi.otunnus = '$row[tunnus]'
          and tilausrivi.kerattyaika != '0000-00-00 00:00:00'
          and tilausrivi.var not in ('P','J','O','S')";
    $kerayspvmres = mysql_query($query) or pupe_error($query);
    $kerayspvmrow = mysql_fetch_array($kerayspvmres);

    echo "<td>".$row['ytunnus']."</td>";
    echo "<td>".$row['asnimi']."</td>";
    echo "<td><a href='?tupakkaamo=$tupakkaamo&tee=NAYTATILAUS&tunnus=$row[tunnus]'>".$row['tunnus']."</a></td>";
    echo "<td>".tv1dateconv($row['lahetepvm'],"P","LYHYT")."</td>";
    echo "<td>".tv1dateconv($kerayspvmrow['kerayspvm'],"P","LYHYT")."</td>";
    echo "<td><input type='checkbox' name='checktunnukset[]' value='$row[tunnus]'></td></tr>";

    $pakkaamo = $row['pakkaamo'];
    $lokero = $row['lokero'];
  }
  echo "</form></table>";

}

require ("inc/footer.inc");
