<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (!empty($_REQUEST["vva"])) {
  setcookie("etsityom_alkupvm", $_REQUEST["vva"]."-".$_REQUEST["kka"]."-".$_REQUEST["ppa"]);
}
elseif (!empty($_COOKIE["etsityom_alkupvm"])) {
  list($_REQUEST["vva"], $_REQUEST["kka"], $_REQUEST["ppa"]) = explode("-", $_COOKIE["etsityom_alkupvm"]);
}

if (!empty($_REQUEST["laajahaku"])) {
  if (count($_REQUEST["laajahaku"]) == 1) {
    $_REQUEST["laajahaku"] = "";
  }
  else {
    $_REQUEST["laajahaku"] = "on";
  }

  setcookie("etsityom_laajahaku", $_REQUEST["laajahaku"]);
}
elseif (!empty($_COOKIE["etsityom_laajahaku"])) {
  $_REQUEST["laajahaku"] = $_COOKIE["etsityom_laajahaku"];
}

require '../inc/parametrit.inc';

echo "<font class='head'>".t("Etsi työmääräys").":</font><hr><br>";

$tyom_kentat_array = array();
$tyom_kentat_array["rekno"] = t("Rekno");
$tyom_kentat_array["valmnro"] = t("Sarjanumero");
$tyom_kentat_array["komm1"] = t("Työn kuvaus");
$tyom_kentat_array["komm2"] = t("Toimenpiteet");
$laajah = "";

if (!empty($laajahaku)) {
  $tyomkentta_res = t_avainsana("TYOM_TYOKENTAT", "", "and avainsana.selitetark != '' and avainsana.selitetark_2 != 'DATE'");

  if (mysql_num_rows($tyomkentta_res)) {
    $tyom_kentat_array = array();

    while ($al_row = mysql_fetch_assoc($tyomkentta_res)) {
      $tyom_kentat_array[$al_row['selite']] = $al_row['selitetark'];
      $tyom_kentat_tyyppi[$al_row['selite']] = $al_row['selitetark_2'];
    }
  }

  $laajah = "CHECKED";
}

if ($tee == 'etsi' and !empty($hakusubmit)) {
  echo "<table>";
  $hakuehdot = '';

  if ($vva != '' and $kka != '' and $ppa != '') {
    $hakuehdot .= "AND lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' ";
  }

  if ($vvl != '' and $kkl != '' and $ppl != '') {
    $hakuehdot .= " AND lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' ";
  }

  if ($nimi != '') {
    $hakuehdot .= " AND lasku.nimi LIKE '%".$nimi."%' ";
  }

  if ($eid != '') {
    $hakuehdot .= " AND lasku.tunnus = '$eid' ";
  }

  if ($asno != '') {
    $hakuehdot .= " AND asiakas.asiakasnro = '$asno' ";
  }

  foreach ($tyom_kentat_array as $selite => $selitetark) {
    if (!empty(${$selite})) {
      if (strtoupper($tyom_kentat_tyyppi[$selite]) == "DROPDOWN") {
        $hakuehdot .= " AND tyomaarays.{$selite} = '".${$selite}."'";
      }
      else {
        $hakuehdot .= " AND tyomaarays.{$selite} LIKE '%".${$selite}."%'";
      }
    }
  }

  if ($hakuteksti != '') {
    $hakuehdot .= " AND ( lasku.tunnus LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR lasku.sisviesti2 LIKE '%".$hakuteksti."%' ";
    $hakuehdot .= " OR asiakas.asiakasnro LIKE '%".$hakuteksti."%' ";

    foreach ($tyom_kentat_array as $selite => $selitetark) {
      $hakuehdot .= " OR tyomaarays.{$selite} LIKE '%".$hakuteksti."%' ";
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
             ORDER BY lasku.tunnus desc
             LIMIT 100";
  $sresult = pupe_query($squery);

  if (mysql_num_rows($sresult) > 0) {
    echo "<tr>
        <th>".t("Työmääräys").":</th>
        <th>".t("Nimi").":</th>
        <th>{$reknokentta}:</th>
        <th>".t("Päivämäärä").":</th>
        <th>".t("Työn kuvaus / Toimenpiteet").":</th>
        <th>".t("Muokkaa").":</th>
        <th>".t("Monista").":</th>
        <th>".t("Tulosta").":</th>
        <th>".t("Uusi").":</th>
       </tr>";

    $lopelinkki = "{$palvelin2}tyomaarays/etsi.php////tee=$tee//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//hakuteksti=$hakuteksti//nimi=$nimi//rekno=$rekno//eid=$eid//asno=$asno//valmno=$valmno//komm1=$komm1//komm2=$komm2";

    while ($row = mysql_fetch_array($sresult)) {

      echo "<tr>
          <td valign='top'><a name='tyom_$row[laskutunnus]'></a>$row[laskutunnus]</td>
          <td valign='top'>$row[nimi]</td>
          <td valign='top'>$row[rekno]</td>
          <td valign='top'>".tv1dateconv(substr($row["luontiaika"], 0, 10))."</td>
          <td>".str_replace("\n", "<br>", $row["komm1"])."".str_replace("\n", "<br>", $row["komm2"])."</td>";

      if ($row["alatila"] == '' or $row["alatila"] == 'A' or $row["alatila"] == 'B' or $row["alatila"] == 'C' or $row["alatila"] == 'J') {
        echo "<td valign='top'>
            <form method='post' action='{$palvelin2}tilauskasittely/tilaus_myynti.php?lopetus=$lopelinkki///tyom_$row[laskutunnus]'>
            <input type='hidden' name='toim' value='TYOMAARAYS'>
            <input type='hidden' name='tilausnumero' value='$row[laskutunnus]'>
            <input type='submit' value = '".t("Muokkaa")."'></form></td>";
      }
      else {
        echo "<td></td>";
      }

      echo "<td valign='top'>
          <form method='post' action='{$palvelin2}monistalasku.php'>
          <input type='hidden' name='toim' value='TYOMAARAYS'>
          <input type='hidden' name='monistettavat[{$row['laskutunnus']}]' value='MONISTA'>
          <input type='hidden' name='tee' value='MONISTA'>
          <input type='hidden' name='kklkm' value='1'>
          <input type='hidden' name='asiakasid' value='{$row['liitostunnus']}'>
          <input type='hidden' name='ytunnus' value='{$row['ytunnus']}'>
          <input type='submit' value = '".t("Monista")."'></form></td>";

      echo "<td valign='top'><form action = '{$palvelin2}tilauskasittely/tulostakopio.php?lopetus=$lopelinkki///tyom_$row[laskutunnus]' method='post'>
          <input type='hidden' name='tee' value = 'ETSILASKU'>
          <input type='hidden' name='otunnus' value='$row[laskutunnus]'>
          <input type='hidden' name='toim' value='TYOMAARAYS'>
          <input type='submit' value = '".t("Tulosta")."'></form></td>";

      echo "<td valign='top'><form action = '{$palvelin2}tilauskasittely/tilaus_myynti.php' method='post'>
          <input type='hidden' name='toim' value='TYOMAARAYS'>
          <input type='hidden' name='tee' value='OTSIK'>
          <input type='hidden' name='asiakasid' value='{$row['liitostunnus']}'>
          <input type='submit' value = '".t("Uusi")."'></form></td>";

      echo " </tr>";
    }
    echo "</table><br>";
  }
  else {
    echo t("Yhtään työmääräystä ei löytynyt annetuilla ehdoilla")."!";

    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<form action = '{$palvelin2}tilauskasittely/tilaus_myynti.php' method='post'>
        <input type='hidden' name='toim' value='TYOMAARAYS'>
        <input type='hidden' name='tee' value='OTSIK'>";
    if (!empty($nimi)) {
      echo "<input type='hidden' name='nimi' value='{$nimi}'>";
    }
    echo "<input type='submit' value = '".t("Tee uusi työmääräys")."'></form>";

    echo "<br><br>";
  }
}

echo "<form method='post'><input type='hidden' name='tee' value='etsi'>";
echo "<table><tr>";
echo "<th colspan='4'>".t("Hae työmääräyksiä").": <div style='float: right;'>(".t("Laajahaku").": <input type='hidden' name='laajahaku[]' value='default'><input type='checkbox' name='laajahaku[]' onclick='submit();' $laajah>)</div></th>";
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
echo "<th>".t("Hae kaikista kentistä (Ei Asiakas)").":</th>";
echo "<td colspan='3'><input type='text' name='hakuteksti' size='35' value='{$hakuteksti}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakkaan nimi").":</th>";
echo "<td colspan='3'><input type='text' name='nimi' size='35' value='{$nimi}' ></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Työmääräysno").":</th>";
echo "<td colspan='3'><input type='text' name='eid' size='35' value='{$eid}'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Asiakasnumero").":</th>";
echo "<td colspan='3'><input type='text' name='asno' size='35' value='{$asno}'></td>";
echo "</tr>";

foreach ($tyom_kentat_array as $selite => $selitetark) {
  echo "<tr>";
  echo "<th>{$selitetark}:</th>";
  echo "<td colspan='3'><input type='text' name='$selite' size='35' value='".${$selite}."'></td>";
  echo "</tr>";
}

echo "</table>";
echo "<input type='submit' name='hakusubmit' value='".t("Hae")."'>";
echo "</form>";

require "../inc/footer.inc";
