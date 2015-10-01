<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

include '../inc/parametrit.inc';

$nimi   = (isset($_REQUEST['nimi'])) ? $_REQUEST['nimi'] : '';
$rekno  = (isset($_REQUEST['rekno'])) ? $_REQUEST['rekno'] : '';
$eid    = (isset($_REQUEST['eid'])) ? $_REQUEST['eid'] : '';
$asno   = (isset($_REQUEST['asno'])) ? $_REQUEST['asno'] : '';
$valmno = (isset($_REQUEST['valmno'])) ? $_REQUEST['valmno'] : '';

echo "<font class='head'>".t("Etsi ty�m��r�ys").":</font><hr><br>";

if ($tee == 'etsi') {
  echo "<table>";
  $hakuehdot = '';

  if ($vva != '' and $kka != '' and $ppa != '') {
    $hakuehdot .= "AND lasku.luontiaika >= '$vva-$kka-$ppa' "; 
  }
  
  if ($vvl != '' and $kkl != '' and $ppl != '') {
    $hakuehdot .= " AND lasku.luontiaika <= '$vvl-$kkl-$ppl' ";    
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
        <th>".t("Ty�m��r�ys").":</th>
        <th>".t("Nimi").":</th>
        <th>".t("Rekno").":</th>
        <th>".t("P�iv�m��r�").":</th>
        <th>".t("Ty�n kuvaus / Toimenpiteet").":</th>
        <th>".t("Muokkaa").":</th>
        <th>".t("Tulosta").":</th>
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
      }
      else {
        echo "<td></td>";
      }

      echo "<td valign='top'><form action = '../tilauskasittely/tulostakopio.php' method='post'>
          <input type='hidden' name='tee' value = 'ETSILASKU'>
          <input type='hidden' name='otunnus' value='$row[laskutunnus]'>
          <input type='hidden' name='toim' value='TYOMAARAYS'>
          <input type='submit' value = '".t("Tulosta")."'></form></td>";

      echo " </tr>";
    }
    echo "</table><br>";
  }
  else {
    echo t("Yht��n ty�m��r�yst� ei l�ytynyt annetuilla ehdoilla")."!<br>";
  }
}

echo "<form method='post'><input type='hidden' name='tee' value='etsi'>";
echo "<table><tr>";
echo "<th colspan='4'>".t("Hae ty�m��r�ykset v�lilt�").":</th>";
echo "</tr>";

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='ppa' value='$ppa' size='3'></td>";
echo "<td><input type='text' name='kka' value='$kka' size='3'></td>";
echo "<td><input type='text' name='vva' value='$vva' size='5'></td>";
echo "</tr>";

echo "<tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='ppl' value='$ppl' size='3'></td>";
echo "<td><input type='text' name='kkl' value='$kkl' size='3'></td>";
echo "<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakkaan nimi").":</th><td colspan='3'><input type='text' name='nimi' size='35' value='$nimi'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Rekno").":</th><td colspan='3'><input type='text' name='rekno' size='35' value='$rekno'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Ty�m��r�ysno").":</th><td colspan='3'><input type='text' name='eid' size='35' value='$eid'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakasnumero").":</th><td colspan='3'><input type='text' name='asno' size='35' value='$asno'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Sarjanumero").":</th><td colspan='3'><input type='text' name='valmno' size='35' value='$valmno'></td>";
echo "</tr>";

echo "</table>";
echo "<input type='submit' value='Hae'>";
echo "</form>";

require "../inc/footer.inc";
