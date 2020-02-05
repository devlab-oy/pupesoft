<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Kauttalaskutus")."</font><hr>";

echo "Kauttalaskutus ei enää käytössä...";
exit;

$pvmrajaus = "2010-12-15";

$query = "SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.toim_nimi, lasku.summa, lasku.tapvm
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN asiakas ON lasku.yhtio=asiakas.yhtio and lasku.liitostunnus=asiakas.tunnus and asiakas.osasto='6'
          WHERE lasku.yhtio  = 'artr'
          and lasku.tila     = 'U'
          and lasku.alatila  = 'X'
          and lasku.tapvm    >= '$pvmrajaus'
          and lasku.sisainen = ''
          and lasku.chn      = '112'
          ORDER BY lasku.laskunro desc";
$kaulasres1 = pupe_query($query);

echo "<br><br><br><table>";

echo "<tr>";
echo "<th>Örum laskunro<br>Örum pvm</th>";
echo "<th>Örum asiakas</th>";
echo "<th>Örum summa</th>";
echo "<th>Örum rivejä</th>";
echo "<th>Merca ostolasku<br>Merca Pvm</th>";
echo "<th>Merca ostosumma</th>";
echo "<th>Merca saapuminen</th>";
echo "<th>Merca myyntilasku<br>Merca pvm</th>";
echo "<th>Merca myyntisumma</th>";
echo "<th>Merca myyntirivejä</th>";
echo "</tr>";

while ($kaulasrow1 = mysql_fetch_assoc($kaulasres1)) {

  echo "<tr><td>$kaulasrow1[laskunro]<br>".tv1dateconv($kaulasrow1["tapvm"])."</td>";
  echo "<td>$kaulasrow1[nimi]<br>$kaulasrow1[toim_nimi]</td>";
  echo "<td align='right'>$kaulasrow1[summa]</td>";

  $query    = "SELECT tunnus
               FROM tilausrivi use index (uusiotunnus_index)
               WHERE yhtio      = 'artr'
               and uusiotunnus  = $kaulasrow1[tunnus]
               and tyyppi       = 'L'
               and kpl         != 0";
  $kaulasres2 = pupe_query($query);

  $alkupriveja = mysql_num_rows($kaulasres2);

  echo "<td align='center'>$alkupriveja</td>";
  echo "<td>";

  // Etsitään lasku mercan ostoreskontrasta
  $query    = "SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.toim_nimi, lasku.summa, lasku.tapvm
               FROM lasku use index (yhtio_tila_tapvm)
               WHERE yhtio  = 'atarv'
               and tila     in ('H','Y','M','P','Q')
               and tapvm    >= '$pvmrajaus'
               and laskunro = '$kaulasrow1[laskunro]'";
  $kaulasres3 = pupe_query($query);

  if (mysql_num_rows($kaulasres3) == 1) {
    $class = "ok";
  }
  else {
    $class = "error";
  }

  while ($kaulasrow3 = mysql_fetch_assoc($kaulasres3)) {

    if ($kaulasrow1["tapvm"] != $kaulasrow3["tapvm"]) {
      $pvmclass = "error";
    }
    else {
      $pvmclass = "ok";
    }

    echo "<font class='$class'>$kaulasrow3[laskunro]</font><br><font class='$pvmclass'>".tv1dateconv($kaulasrow3["tapvm"])."</font><br>";
  }

  echo "</td><td align='right'>";

  mysql_data_seek($kaulasres3, 0);

  while ($kaulasrow3 = mysql_fetch_assoc($kaulasres3)) {

    if ($kaulasrow3["summa"] == $kaulasrow1["summa"]) {
      $class = "ok";
    }
    else {
      $class = "error";
    }

    echo "<font class='$class'>$kaulasrow3[summa]</font><br>";
  }

  echo "</td><td>";

  mysql_data_seek($kaulasres3, 0);

  while ($kaulasrow3 = mysql_fetch_assoc($kaulasres3)) {
    // Keikka
    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.toim_nimi, lasku.summa, lasku.tapvm
              FROM lasku use index (yhtio_vanhatunnus)
              WHERE yhtio     = 'atarv'
              and vanhatunnus = '$kaulasrow3[tunnus]'
              and tila        = 'K'";
    $kaulasres4 = pupe_query($query);
    $kaulasrow4 = mysql_fetch_assoc($kaulasres4);

    echo "$kaulasrow4[laskunro]<br>".tv1dateconv($kaulasrow4["tapvm"])."<br>";
  }

  echo "</td>";

  // Etsitään lasku mercan myyntipuolelta
  $query    = "SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.toim_nimi, lasku.summa, lasku.tapvm
               FROM lasku use index (yhtio_tila_luontiaika)
               JOIN liitetiedostot ON (liitetiedostot.yhtio = lasku.yhtio and liitetiedostot.liitos = 'lasku' AND liitetiedostot.liitostunnus = lasku.tunnus AND liitetiedostot.laatija = 'verkkolas')
               where lasku.yhtio       = 'atarv'
               and lasku.tila          in ('L','N')
               and lasku.luontiaika    >= '$pvmrajaus'
               and liitetiedostot.data like '%".mysql_real_escape_string("<InvoiceNumber>$kaulasrow1[laskunro]</InvoiceNumber>")."%'";
  $kaulasres5 = pupe_query($query);

  if (mysql_num_rows($kaulasres5) > 0) {

    echo "<td>";
    while ($kaulasrow5 = mysql_fetch_assoc($kaulasres5)) echo "$kaulasrow5[laskunro]<br>";
    mysql_data_seek($kaulasres5, 0);
    echo "</td>";
    echo "<td align='right'>";
    while ($kaulasrow5 = mysql_fetch_assoc($kaulasres5)) echo "$kaulasrow5[summa]<br>";
    mysql_data_seek($kaulasres5, 0);
    echo "</td>";

    echo "<td align='center'>";

    while ($kaulasrow5 = mysql_fetch_assoc($kaulasres5)) {
      $query = "SELECT *
                FROM tilausrivi use index (yhtio_otunnus)
                WHERE yhtio = 'atarv'
                and otunnus = $kaulasrow5[tunnus]
                and tyyppi  = 'L'
                and varattu+kpl != 0
                ORDER BY tunnus";
      $tres = pupe_query($query);
      $kauttariveja = mysql_num_rows($tres);

      if ($kauttariveja != $alkupriveja and $kaulasrow5["laskunro"] > 0) {
        $class = "error";
      }
      else {
        $class = "ok";
      }

      echo "<font class='$class'>$kauttariveja</font>";
    }

    echo "</td>";
  }
  else {
    echo "<td colspan='3'><font class='error'>MYYNTITILAUSTA EI LÖYDY</font></td>";
  }

  echo "</tr>";
}

echo "</table>";

require "inc/footer.inc";
