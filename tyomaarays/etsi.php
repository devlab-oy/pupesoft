<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

include '../inc/parametrit.inc';

$nimi   = (isset($_REQUEST['nimi'])) ? $_REQUEST['nimi'] : '';
$rekno  = (isset($_REQUEST['rekno'])) ? $_REQUEST['rekno'] : '';
$eid    = (isset($_REQUEST['eid'])) ? $_REQUEST['eid'] : '';
$asno   = (isset($_REQUEST['asno'])) ? $_REQUEST['asno'] : '';
$valmno = (isset($_REQUEST['valmno'])) ? $_REQUEST['valmno'] : '';

if (isset($_REQUEST['selected'])) {
  $nimiselect   = (array_search('nimisearch', $_REQUEST['selected']) !== false) ? 'CHECKED' : '';
  $reknoselect  = (array_search('reknosearch', $_REQUEST['selected']) !== false) ? 'CHECKED' : '';
  $eidselect    = (array_search('eidsearch', $_REQUEST['selected']) !== false) ? 'CHECKED' : '';
  $asnoselect   = (array_search('asnosearch', $_REQUEST['selected']) !== false) ? 'CHECKED' : '';
  $valmnoselect = (array_search('valmnosearch', $_REQUEST['selected']) !== false) ? 'CHECKED' : '';
}

echo "<font class='head'>".t("Etsi työmääräys").":</font><hr><br>";

if ($tee == 'etsi') {
  echo "<table>";
  $hakuehdot = '';

  if ($vva != '' and $kka != '' and $ppa != '') {
    $hakuehdot .= "AND lasku.luontiaika >= '$vva-$kka-$ppa' "; 
  }
  
  if ($vvl != '' and $kkl != '' and $ppl != '') {
    $hakuehdot .= " AND lasku.luontiaika <= '$vvl-$kkl-$ppl' ";    
  }

  // Näissä hakuehdoissa haetaan samalla tiedolla mahdollisesti useasta sarakkeesta
  if ($hakuteksti != '' and ($nimiselect != '' or $reknoselect != '' or $eidselect != ''
    or $asnoselect != '' or $valmnoselect != '')) {
    
    $hakuehdot .= " AND ( 'konditionaaliset_hakuehdot' ";

    if ($nimiselect != '') {
      $hakuehdot .= " OR lasku.nimi LIKE '%".$hakuteksti."%' ";
    }
    
    if ($reknoselect != '') {
      $hakuehdot .= " OR tyomaarays.rekno LIKE '%".$hakuteksti."%' ";
    }
    
    if ($eidselect != '') {
      $hakuehdot .= " OR lasku.tunnus LIKE '%".$hakuteksti."%' ";
    }
    
    if ($asnoselect != '') {
      $hakuehdot .= " OR asiakas.asiakasnro LIKE '%".$hakuteksti."%' ";
    }
    
    if ($valmnoselect != '') {
      $hakuehdot .= " OR tyomaarays.valmnro LIKE '%".$hakuteksti."%' ";
    }

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
echo "<th>".t("Hakuteksti").":</th>";
echo "<td colspan='3'><input type='text' name='hakuteksti' size='35' value='$hakuteksti'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakkaan nimi").":</th><td colspan='3'><input type='checkbox' name='selected[]' value='nimisearch' $nimiselect></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Rekno").":</th><td colspan='3'><input type='checkbox' name='selected[]' value='reknosearch' $reknoselect></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Työmääräysno").":</th><td colspan='3'><input type='checkbox' name='selected[]' value='eidsearch' $eidselect></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakasnumero").":</th><td colspan='3'><input type='checkbox' name='selected[]' value='asnosearch' $asnoselect></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Sarjanumero").":</th><td colspan='3'><input type='checkbox' name='selected[]' value='valmnosearch' $valmnoselect></td>";
echo "</tr>";

echo "</table>";
echo "<input type='submit' value='Hae'>";
echo "</form>";

require "../inc/footer.inc";
