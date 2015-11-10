<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

include '../inc/parametrit.inc';

echo "<font class='head'>".t("Etsi työmääräys").":</font><hr><br>";

if ($tee == 'etsi') {
  echo "<table>";
  $hakuehdot = '';

  if ($vva != '' and $kka != '' and $ppa != '') {
    $hakuehdot .= "AND lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' ";
  }

  if ($vvl != '' and $kkl != '' and $ppl != '') {
    $hakuehdot .= " AND lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' ";
  }

  if ($nimi != '') {
    $hakuehdot .= " AND lasku.nimi LIKE '%".$nimi."%'";
  }

  if ($rekno != '') {
    $hakuehdot .= " AND tyomaarays.rekno LIKE '%".$rekno."%'";
  }

  if ($eid != '') {
    $hakuehdot .= " AND lasku.tunnus = '$eid'";
  }

  if ($asno != '') {
    $hakuehdot .= " AND asiakas.asiakasnro = '$asno'";
  }

  if ($valmno != '') {
    $hakuehdot .= " AND tyomaarays.valmnro LIKE '%".$valmno."%'";
  }

  if ($komm1 != '') {
    $hakuehdot .= " AND tyomaarays.komm1 LIKE '%".$komm1."%' ";
  }

  if ($komm2 != '') {
    $hakuehdot .= " AND tyomaarays.komm2 LIKE '%".$komm2."%' ";
  }

  if ($hakuteksti != '') {
    $hakuehdot .= " AND ( lasku.nimi LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR tyomaarays.rekno LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR lasku.tunnus LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR asiakas.asiakasnro LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR tyomaarays.valmnro LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR tyomaarays.komm1 LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR tyomaarays.komm2 LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " ) ";
  }

  $squery = "SELECT lasku.*, tyomaarays.*, lasku.tunnus laskutunnus
             FROM lasku
             JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
             JOIN asiakas ON asiakas.yhtio = tyomaarays.yhtio and asiakas.tunnus = lasku.liitostunnus
             WHERE lasku.yhtio      = '$kukarow[yhtio]'
             and lasku.tila         in ('A','L','N')
             and lasku.tilaustyyppi = 'A'
             $hakuehdot
             ORDER BY lasku.tunnus desc";
  $sresult = pupe_query($squery);

  if (mysql_num_rows($sresult) > 0) {
    echo "<tr>
        <th>".t("Työmääräys").":</th>
        <th>".t("Nimi").":</th>
        <th>".t("Rekno").":</th>
        <th>".t("Päivämäärä").":</th>
        <th>".t("Työn kuvaus / Toimenpiteet").":</th>
        <th>".t("Muokkaa").":</th>
        <th>".t("Monista").":</th>
        <th>".t("Tulosta").":</th>
        <th>".t("Uusi").":</th>
       </tr>";

    while ($row = mysql_fetch_array($sresult)) {

      echo "<tr>
          <td valign='top'>$row[laskutunnus]</td>
          <td valign='top'>$row[nimi]</td>
          <td valign='top'>$row[rekno]</td>
          <td valign='top'>".tv1dateconv(substr($row["luontiaika"], 0, 10))."</td>
          <td>".str_replace("\n", "<br>", $row["komm1"])."".str_replace("\n", "<br>", $row["komm2"])."</td>";

      if ($row["alatila"] == '' or $row["alatila"] == 'A' or $row["alatila"] == 'B' or $row["alatila"] == 'C' or $row["alatila"] == 'J') {
        echo "<td valign='top'>
            <form method='post' action='../tilauskasittely/tilaus_myynti.php'>
            <input type='hidden' name='toim' value='TYOMAARAYS'>
            <input type='hidden' name='tilausnumero' value='$row[laskutunnus]'>
            <input type='submit' value = '".t("Muokkaa")."'></form></td>";
        echo "<td valign='top'>
            <form method='post' action='../monistalasku.php'>
            <input type='hidden' name='toim' value='TYOMAARAYS'>
            <input type='hidden' name='monistettavat[{$row['laskutunnus']}]' value='MONISTA'>
            <input type='hidden' name='tee' value='MONISTA'>
            <input type='hidden' name='kklkm' value='1'>
            <input type='hidden' name='asiakasid' value='{$row['liitostunnus']}'>
            <input type='hidden' name='ytunnus' value='{$row['ytunnus']}'>
            <input type='submit' value = '".t("Monista")."'></form></td>";
      }
      else {
        echo "<td></td>";
        echo "<td></td>";
      }

      echo "<td valign='top'><form action = '../tilauskasittely/tulostakopio.php' method='post'>
          <input type='hidden' name='tee' value = 'ETSILASKU'>
          <input type='hidden' name='otunnus' value='$row[laskutunnus]'>
          <input type='hidden' name='toim' value='TYOMAARAYS'>
          <input type='submit' value = '".t("Tulosta")."'></form></td>";

      echo "<td valign='top'><form action = '../tilauskasittely/tilaus_myynti.php' method='post'>
          <input type='hidden' name='toim' value='TYOMAARAYS'>
          <input type='hidden' name='tee' value='OTSIK'>
          <input type='hidden' name='nimi' value='{$row['nimi']}'>
          <input type='submit' value = '".t("Uusi")."'></form></td>";

      echo " </tr>";
    }
    echo "</table><br>";
  }
  else {
    echo t("Yhtään työmääräystä ei löytynyt annetuilla ehdoilla")."!<br>";
  }
}

echo "<form method='post'><input type='hidden' name='tee' value='etsi'>";
echo "<table><tr>";
echo "<th colspan='4'>".t("Hae työmääräykset väliltä").":</th>";
echo "</tr>";

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='ppa' value='$ppa' size='3'></td>";
echo "<td><input type='text' name='kka' value='$kka' size='3'></td>";
echo "<td><input type='text' name='vva' value='$vva' size='5'></td>";
echo "</tr>";

echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='ppl' value='$ppl' size='3'></td>";
echo "<td><input type='text' name='kkl' value='$kkl' size='3'></td>";
echo "<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Hae kaikista kentistä").":</th>";
echo "<td colspan='3'><input type='text' name='hakuteksti' size='35' value='{$hakuteksti}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakkaan nimi").":</th>";
echo "<td colspan='3'><input type='text' name='nimi' size='35' value='{$nimi}' ></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Rekno").":</th>";
echo "<td colspan='3'><input type='text' name='rekno' size='35' value='{$rekno}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Työmääräysno").":</th>";
echo "<td colspan='3'><input type='text' name='eid' size='35' value='{$eid}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakasnumero").":</th>";
echo "<td colspan='3'><input type='text' name='asno' size='35' value='{$asno}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Sarjanumero").":</th>";
echo "<td colspan='3'><input type='text' name='valmno' size='35' value='{$valmno}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Työn kuvaus").":</th>";
echo "<td colspan='3'><input type='text' name='komm1' size='35' value='{$komm1}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Toimenpiteet").":</th>";
echo "<td colspan='3'><input type='text' name='komm2' size='35' value='{$komm2}'></td>";
echo "</tr>";

echo "</table>";
echo "<input type='submit' value='Hae'>";
echo "</form>";

require "../inc/footer.inc";
