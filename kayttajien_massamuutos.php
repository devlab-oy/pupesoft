<?php

require ("inc/parametrit.inc");

function massamuuttaja ($taulu, $sarake, $korvattava, $korvaava) {
  global $yhtiorow, $kukarow;

  $paivityslause  = "UPDATE $taulu SET
                     $sarake = '$korvaava',
                     muuttaja    = '$kukarow[kuka]',
                     muutospvm   = now()
                     WHERE yhtio = '$kukarow[yhtio]'
                     AND $sarake = '$korvattava'";
  $resultpaivitys  = mysql_query($paivityslause) or pupe_error($paivityslause);

  return mysql_affected_rows();
}

echo "<font class='head'>".t("Käyttäjien massamuutos").":</font><hr>";

$totalupdate = 0;

if ($MassaMuutos != '') {

  $tarkistus = "SELECT DISTINCT kuka.kuka, kuka.myyja, kuka.yhtio, kuka.nimi
                FROM kuka
                WHERE kuka.yhtio = '$kukarow[yhtio]'
                AND kuka.kuka    = '$tokuka'";
  $resulttarkista1 = mysql_query($tarkistus) or pupe_error($tarkistus);
  $tarkistusrow1 = mysql_fetch_assoc($resulttarkista1);

  $tarkistus = "SELECT DISTINCT kuka.kuka, kuka.myyja, kuka.yhtio, kuka.nimi
                FROM kuka
                WHERE kuka.yhtio = '$kukarow[yhtio]'
                AND kuka.kuka    = '$fromkuka'";
  $resulttarkista2 = mysql_query($tarkistus) or pupe_error($tarkistus);
  $tarkistusrow2 = mysql_fetch_assoc($resulttarkista2);

  if (($tarkistusrow1['myyja'] == 0 or $tarkistusrow2['myyja'] == 0) and ($tuote_myyja == 1 or $tuote_ostaja == 1 or $asiakas_myyja == 1)) {
    if ($tarkistusrow1['myyja'] == 0) {
      echo "<font class='error'> ".t("Käyttäjällä ei %s ole myyjänumeroa, valittuja päivityksiä ei voida tehdä!", '', $tarkistusrow1['nimi'])." </font><br>";
    }

    if ($tarkistusrow2['myyja'] == 0) {
      echo "<font class='error'> ".t("Käyttäjällä ei %s ole myyjänumeroa, valittuja päivityksiä ei voida tehdä!", '', $tarkistusrow2['nimi'])." </font><br>";
    }

    echo "<br>";
  }
  else {

    echo "<font class='message'>".t("Korvataan käyttäjä ")." $tarkistusrow2[nimi] ".t("käyttäjällä")." $tarkistusrow1[nimi].</font><br><br>";

    if ($tiliointi == 1) {
      // käyttämällä massamuuttaja-funktiota voidaan massana ajaa muutoksia haluttuun tauluihin.
      $laskuri = 0;
      $laskuri += massamuuttaja("tiliointisaanto", "hyvak1", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("tiliointisaanto", "hyvak2", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("tiliointisaanto", "hyvak3", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("tiliointisaanto", "hyvak4", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("tiliointisaanto", "hyvak5", $fromkuka, $tokuka);

      if ($laskuri > 0) {
        echo "<font class='message'>".t("Päivitettiin %s tiliöintisääntöä", '', $laskuri).".</font><br>";
        $totalupdate++;
      }
    }

    if ($toimittajan_oletus == 1) {
      $laskuri = 0;
      $laskuri += massamuuttaja("toimi", "oletus_hyvak1", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("toimi", "oletus_hyvak2", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("toimi", "oletus_hyvak3", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("toimi", "oletus_hyvak4", $fromkuka, $tokuka);
      $laskuri += massamuuttaja("toimi", "oletus_hyvak5", $fromkuka, $tokuka);

      if ($laskuri > 0) {
        echo "<font class='message'>".t("Päivitettiin %s toimittajan oletushyväksyjää", '', $laskuri).".</font><br>";
        $totalupdate++;
      }
    }

    if ($tuote_myyja == 1) {
      $laskuri = massamuuttaja("tuote", "myyjanro", $tarkistusrow2['myyja'], $tarkistusrow1['myyja']);

      if ($laskuri > 0) {
        echo "<font class='message'>".t("Päivitettiin %s tuotteen myyjää", '', $laskuri).".</font><br>";
        $totalupdate++;
      }
    }

    if ($tuote_ostaja == 1) {
      $laskuri = massamuuttaja("tuote", "ostajanro", $tarkistusrow2['myyja'], $tarkistusrow1['myyja']);

      if ($laskuri > 0) {
        echo "<font class='message'>".t("Päivitettiin %s tuotteen ostajaa", '', $laskuri).".</font><br>";
        $totalupdate++;
      }
    }

    if ($asiakas_myyja == 1)  {
      $laskuri = massamuuttaja("asiakas", "myyjanro", $tarkistusrow2['myyja'], $tarkistusrow1['myyja']);

      if ($laskuri > 0) {
        echo "<font class='message'>".t("Päivitettiin %s asiakkaan myyjää", '', $laskuri).".</font><br>";
        $totalupdate++;
      }
    }

    if ($totalupdate == 0) {
      echo "<font class='message'>".t("Ei löytynyt mitään päivitetävää")."!</font><br>";
    }

    echo "<br>";
  }
}

echo "<form method='post'>";
echo "<input type='hidden' name='tila' value='massaus'>";

echo "<font class='message'>".t("Kuka korvataan ").":</font>";

// tehdään käyttäjälistaukset

$query = "SELECT distinct kuka.nimi, kuka.kuka
          FROM kuka
          WHERE kuka.extranet = ''
          AND kuka.yhtio      = '$kukarow[yhtio]'
          ORDER BY kuka.nimi";
$kukar = mysql_query($query) or pupe_error($query);

echo "<table><tr><th align='left'>".t("Käyttäjä").":</th><td>
<select name='fromkuka' onchange='submit()'>
<option value=''>".t("Valitse käyttäjä")."</option>";

while ($kurow = mysql_fetch_array($kukar)) {
  if ($fromkuka == $kurow[1]) $select = 'selected';
  else $select = '';
  echo "<option $select value='$kurow[1]'>$kurow[0] ($kurow[1])</option>";
}

echo "</select>";
echo "</td></tr>";

echo "</table>";

echo "<br><br><font class='message'>".t("Kenellä korvataan").":</font>";

// tehdään käyttäjälistaukset
$query = "SELECT distinct kuka.nimi, kuka.kuka
          FROM kuka
          WHERE kuka.extranet = ''
          AND kuka.yhtio      = '$kukarow[yhtio]'
          ORDER BY kuka.nimi";
$kukar = mysql_query($query) or pupe_error($query);

echo "<table><tr><th align='left'>".t("Käyttäjä").":</th><td>
<select name='tokuka' onchange='submit()'>
<option value=''>".t("Valitse käyttäjä")."</option>";

while ($kurow = mysql_fetch_array($kukar)) {
  if ($tokuka == $kurow[1]) $select = 'selected';
  else $select = '';
  echo "<option $select value='$kurow[1]'>$kurow[0] ($kurow[1])</option>";
}

echo "</select></td></tr>";

echo "</table><br>";

if (isset($tiliointi)) {
  $check1 = "checked='yes'";
}
if (isset($toimittajan_oletus)) {
  $check2 = "checked='yes'";
}
if (isset($tuote_myyja)) {
  $check3 = "checked='yes'";
}
if (isset($tuote_ostaja)) {
  $check4 = "checked='yes'";
}
if (isset($asiakas_myyja)) {
  $check5 = "checked='yes'";
}

echo "<table>";
echo "<tr>";
echo "<td><input type='checkbox' name='tiliointi' value='1' $check1 onchange='submit()'/>1. ".t("Tiliöintisääntöjen hyväksyjät")."</td>";
echo "<td><input type='checkbox' name='toimittajan_oletus' value='1' $check2 onchange='submit()'/>2. ".t("Toimittajien oletushyväksyjät")."</td>";
echo "</tr>";

echo "<tr>";
echo "<td><input type='checkbox' name='tuote_myyja' value='1' $check3 onchange='submit()'/>3. ".t("Tuotteen vastuumyyjä")."</td>";
echo "<td><input type='checkbox' name='tuote_ostaja' value='1' $check4 onchange='submit()'/>4. ".t("Tuotteen vastuuostaja")."</td>";
echo "</tr>";

echo "<tr>";
echo "<td><input type='checkbox' name='asiakas_myyja' value='1' $check5 onchange='submit()'/>5. ".t("Asiakkaan vastuumyyjä")."</td>";
echo "<td>&nbsp;</td>";
echo "</tr>";

echo "</table>";

if ($tokuka != '' and $fromkuka != '' and ($tiliointi == 1 or $toimittajan_oletus == 1 or $tuote_myyja == 1 or $tuote_ostaja == 1 or $asiakas_myyja == 1)) {
  echo "<br><br>";
  echo "<input type='submit' name='MassaMuutos' value='".t("Korvaa")." $fromkuka --> $tokuka'>";
}

echo "</form>";

require("inc/footer.inc");
