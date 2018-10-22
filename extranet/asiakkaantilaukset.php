<?php

require "parametrit.inc";

$lisa = '';

//Haetaan asiakkaan tunnuksella
$query  = "SELECT *
           FROM asiakas
           WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $asiakas = mysql_fetch_array($result);
  $ytunnus = $asiakas["ytunnus"];
  $asiakastunnus = $asiakas["tunnus"];
}
else {
  echo t("VIRHE: K‰ytt‰j‰tiedoissasi on virhe! Ota yhteys j‰rjestelm‰n yll‰pit‰j‰‰n.")."<br><br>";
  exit;
}

echo "<font class='head'>".t("Tilaushistoria").":</font><hr>";


if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

  require "naytatilaus.inc";

  echo "<hr>";
  $tee = "TULOSTA";
}

if ($otunnus > 0) {
  $query = "SELECT laskunro, ytunnus
            FROM lasku
            WHERE tunnus     = '$otunnus'
            and liitostunnus = '$asiakastunnus'
            and yhtio        = '$kukarow[yhtio]' ";
  $result = pupe_query($query);
  $row = mysql_fetch_array($result);

  if ($row["laskunro"] > 0) {
    $laskunro = $row["laskunro"];
  }
}
elseif ($laskunro > 0) {
  $query = "SELECT laskunro, ytunnus
            FROM lasku
            WHERE laskunro='$laskunro'
            and liitostunnus = '$asiakastunnus'
            and yhtio        = '$kukarow[yhtio]' ";
  $result = pupe_query($query);
  $row = mysql_fetch_array($result);

  $laskunro = $row["laskunro"];
}


echo "<form method='post' autocomplete='off'>
  <input type='hidden' name='tee' value='TULOSTA'>";

echo "<table>";


echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='10' name='otunnus' value='$otunnus'></td></tr>";
echo "<tr><th>".t("Laskunumero")."</th><td colspan='3'><input type='text' size='10' name='laskunro' value='$laskunro'></td></tr>";


if (!isset($kka))
  $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva))
  $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa))
  $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if (!isset($kkl))
  $kkl = date("m");
if (!isset($vvl))
  $vvl = date("Y");
if (!isset($ppl))
  $ppl = date("d");

echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr><tr><th>".t("Loppup‰iv‰m‰‰r‰")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>";
echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></form></table>";

if ($jarj != '') {
  $jarj = "ORDER BY $jarj";
}
else {
  $jarj = "ORDER BY lasku.laskunro desc";
}

if ($toim == "EXT") {
    $lisa = " and ohjelma_moduli='EXTRANET' ";
}

if ($otunnus > 0 or $laskunro > 0) {
  $query = "SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
            FROM lasku
            WHERE yhtio      = '$kukarow[yhtio]'
            and liitostunnus = '$asiakastunnus'
            and tila         in ('L','N')
            $lisa";

  if ($laskunro > 0) {
    $query .= "and lasku.laskunro='$laskunro'";
  }
  else {
    $query .= "and lasku.tunnus='$otunnus'";
  }

  $query .=  "$jarj";
}
else {
  $query = "SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
            FROM lasku use index (yhtio_tila_luontiaika)
            WHERE yhtio      = '$kukarow[yhtio]'
            and liitostunnus = '$asiakastunnus'
            and tila         in ('L','N')
            $lisa
            and luontiaika >='$vva-$kka-$ppa 00:00:00'
            and luontiaika <='$vvl-$kkl-$ppl 23:59:59'
            $jarj , tila";
}
$result = pupe_query($query);

if (mysql_num_rows($result)!=0) {

  echo "<br><table>";
  echo "<tr>";
  for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
    echo "<th align='left'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&jarj=".mysql_field_name($result, $i)."'>".t(mysql_field_name($result, $i))."</a></th>";
  }
  echo "<th align='left'>".t("Tyyppi")."</th>";
  echo "</tr>";

  while ($row = mysql_fetch_array($result)) {

    $ero="td";
    if ($tunnus==$row['tilaus']) $ero="th";

    echo "<tr>";
    for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
      echo "<$ero>$row[$i]</$ero>";
    }

    $laskutyyppi=$row["tila"];
    $alatila=$row["alatila"];

    //tehd‰‰n selv‰kielinen tila/alatila
    require "laskutyyppi.inc";

    echo "<$ero>".t($laskutyyppi)." ".t($alatila)."</$ero>";

    echo "<form method='post'><td class='back'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='tunnus' value='$row[tilaus]'>
        <input type='hidden' name='ppa' value='$ppa'>
        <input type='hidden' name='kka' value='$kka'>
        <input type='hidden' name='vva' value='$vva'>
        <input type='hidden' name='ppl' value='$ppl'>
        <input type='hidden' name='kkl' value='$kkl'>
        <input type='hidden' name='vvl' value='$vvl'>
        <input type='submit' value='".t("N‰yt‰ tilaus")."'></td></form>";

    echo "</tr>";
  }
  echo "</table>";
}
else {
  echo t("Yht‰‰n tilausta ei lˆytynyt")."...<br><br>";
}

require "footer.inc";
