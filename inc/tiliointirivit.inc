<?php

// Annetaan mahdollisuus tehdä uusi tiliöinti, jos siihen on oikeus

// Onko tosite liitetty saapumiseen
$query = "SELECT nimi, laskunro
          from lasku
          where yhtio     = '$kukarow[yhtio]'
          and tila        = 'K'
          and vanhatunnus = '$tunnus'";
$keikres = pupe_query($query);

if (mysql_num_rows($keikres) > 0) {
  $keikrow = mysql_fetch_assoc($keikres);

  echo "<br><br><font class='message'>".t("Lasku on liitetty saapumiseen, alv tiliöintejä ei voi muuttaa")."! ".t("Saapuminen").": $keikrow[nimi] / $keikrow[laskunro]</font>";
}

if (!isset($alv_tili))      $alv_tili = "";
if (!isset($iframe))      $iframe = "";
if (!isset($iframe_id))      $iframe_id = "";
if (!isset($jarj))        $jarj = "";
if (!isset($kk))        $kk = "";
if (!isset($laji))        $laji = "";
if (!isset($ok))        $ok = 0;
if (!isset($tiliulos))      $tiliulos = "";
if (!isset($tositenro))      $tositenro = "";
if (!isset($valkoodi))      $valkoodi = "";
if (!isset($viivatut))      $viivatut = "";
if (!isset($grouppaa))      $grouppaa = "";
if (!isset($virhe))        $virhe = "";
if (!isset($vv))        $vv = "";
if (!isset($yhtsum))      $yhtsum = "";
if (!isset($kustp))        $kustp = "";
if (!isset($kohde))        $kohde = "";
if (!isset($projekti))      $projekti = "";
if (!isset($liitos))       $liitos = "";
if (!isset($liitostunnus))     $liitostunnus = 0;
if (!isset($tapahtumatunnus))   $tapahtumatunnus = 0;
if (!isset($summa_valuutassa))  $summa_valuutassa = "";

// Otetaan yhtiön oletus jos ei olla submitattu....
if (!isset($tilirivformi))    $grouppaa = $yhtiorow["tositteen_tilioinnit"];

echo "<br>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Tili")."</th>";
echo "<th>".t("Tarkenne")."</th>";
echo "<th>".t("Summa")."</th>";
echo "<th>".t("Vero")."</th>";
echo "<th>".t("Tapvm")."</th>";
echo "<th>".t("Selite")."</th>";

if ($viivatut == 'on') {
  echo "<th>".t("Laadittu")." / ".t("Poistettu")."</th>";
}

echo "<th></th>";
echo "</tr>";

if ($oikeurow['paivitys'] == 1 and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {

  if ($ok != 1) {
    // Annetaan tyhjät tiedot, jos rivi oli virheetön
    $tili = '';
    $kustp = '';
    $kohde = '';
    $projekti = '';
    $summa = '';
    $tiliointipvm = '';
    $vero = alv_oletus();
    $selite = '';
  }

  $query = "SELECT tunnus, nimi, koodi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'K'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $ulos = "<select name = 'kustp' style='width: 140px'><option value = ' '>".t("Ei kustannuspaikkaa");

    while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
      $valittu = "";
      if ($kustannuspaikkarow["tunnus"] == $kustp) {
        $valittu = "selected";
      }
      $ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]";
    }
    $ulos .= "</select><br>";
  }

  $query = "SELECT tunnus, nimi, koodi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'O'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $ulos .= "<select name = 'kohde' style='width: 140px'><option value = ' '>".t("Ei kohdetta");

    while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
      $valittu = "";
      if ($kustannuspaikkarow["tunnus"] == $kohde) {
        $valittu = "selected";
      }
      $ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]";
    }
    $ulos .= "</select><br>";
  }

  $query = "SELECT tunnus, nimi, koodi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'P'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $ulos .= "<select name = 'projekti' style='width: 140px'><option value = ' '>".t("Ei projektia");

    while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
      $valittu = "";
      if ($kustannuspaikkarow["tunnus"] == $projekti) {
        $valittu = "selected";
      }
      $ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]";
    }
    $ulos .= "</select>";
  }

  echo "<tr>
      <td valign='top'>
      <form name = 'uusi' action = '$PHP_SELF#$tunnus' method='post'>
      <input type='hidden' name = 'tilirivformi' value = 'submitattu'>
      <input type='hidden' name = 'lopetus' value = '$lopetus'>
      <input type='hidden' name = 'tee' value = 'U'>
      <input type='hidden' name = 'tunnus' value = '$tunnus'>
      <input type='hidden' name = 'viivatut' value = '$viivatut'>
      <input type='hidden' name = 'grouppaa' value = '$grouppaa'>
      <input type='hidden' name = 'laji' value = '$laji'>
      <input type='hidden' name = 'tositenro' value = '$tositenro'>
      <input type='hidden' name = 'tapahtumatunnus' value = '$tapahtumatunnus'>
      <input type='hidden' name = 'vv' value = '$vv'>
      <input type='hidden' name = 'kk' value = '$kk'>
      <input type='hidden' name = 'iframe' value = '$iframe'>
      <input type='hidden' name = 'iframe_id' value = '$iframe_id'>
      <input type='hidden' name = 'jarj' value = '$jarj'>";

  if ($tiliulos == '') {
    echo livesearch_kentta("uusi", "TILIHAKU", "tili", 170, $tili, "EISUBMIT", "vero");
  }
  else {
    echo "$tiliulos";
  }

  echo "</td>
    <td valign='top'>$ulos</td>
    <td valign='top'>
    <input type='text' name='summa' value = '$summa' size='8'>
    <input type='hidden' name='vanhasumma' value = '$summa'>
    <input type='hidden' name='vanhasumma_valuutassa' value = '$summa_valuutassa'>
    <input type='hidden' name='vanhasumma_valkoodi' value = '$valkoodi'>
    <input type='hidden' name='alv_tili' value = '$alv_tili'>
    </td>";

  // jos ollaan liitetty lasku saapumiseen, ei anneta laittaa veroa
  if (mysql_num_rows($keikres) == 0) {
    echo "<td align='right' valign='top'>".alv_popup('vero', $vero)."</td>";
  }
  else {
    echo "<td align='right' valign='top'><input type='hidden' name='vero' value='0'>0</td>";
  }

  echo "<td valign='top'>";

  // haetaan kaikki tiliöintien tapahtumapäivät plus unionilla tämä päivä mukaan
  $query = "(SELECT distinct(tapvm) pvm
             FROM tiliointi
             WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and tapvm >= '$yhtiorow[tilikausi_alku]')
             UNION
             (SELECT CURDATE() pvm)
             UNION
             (SELECT if ('$trow[tapvm]' >= '$yhtiorow[tilikausi_alku]', '$trow[tapvm]', CURDATE()) pvm)
             UNION
             (SELECT mapvm pvm FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus' and mapvm >= '$yhtiorow[tilikausi_alku]')";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 1) {
    echo "<select name = 'tiliointipvm'>";
    while ($tiliointirow = mysql_fetch_assoc($result)) {
      $valittu = "";
      if ($tiliointirow["pvm"] == $tiliointipvm) $valittu = "selected";
      echo "<option value = '$tiliointirow[pvm]' $valittu>".tv1dateconv($tiliointirow["pvm"])."</option>";
    }
    echo "</select>";
  }
  else {
    $tiliointirow = mysql_fetch_assoc($result);
    // tapahtumapäivä on vanhalla tilikaudella, ei anneta lisätä kun tälle päivälle
    if (strtotime($tiliointirow["pvm"]) < strtotime($yhtiorow['tilikausi_alku'])) {
      $tiliointirow["pvm"] = date("Y-m-d");
    }
    echo tv1dateconv($tiliointirow["pvm"])."<input type='hidden' name='tiliointipvm' value ='$tiliointirow[pvm]'>";
  }
  echo "</td>";

  echo "<td valign='top'>";

  // Ei rikota rivinvaihtoja
  $selite = str_ireplace("<br>", "(br)", $selite);

  if ($selite == '' and $lisaselite != '') {
    $selite = $lisaselite;
  }
  echo "<input type='text' class='selite' name='selite' value = '$selite'>";

  if ($trow['tila'] == 'X') {
    echo "<div style='float:right;'><a href=\"javascript:toggleGroup('eee')\"><img src='{$palvelin2}pics/lullacons/user-option-add.png'></a></div>";

    if ($liitostunnus > 0 or $asiakastoimittajavirhe) {
      echo "<div id='eee' style='display:block'>";
    }
    else {
      echo "<div id='eee' style='display:none'>";
    }
  }

  if ($trow['tila'] == 'X' and trim($liitos) != '' and $liitostunnus > 0) {

    if ($liitos == "A") {
      $taulu = "asiakas";
    }
    elseif ($liitos == "T") {
      $taulu = "toimi";
    }
    else {
      $taulu = "SQLERROR";
    }

    $query = "SELECT nimi, nimitark
              FROM {$taulu}
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$liitostunnus}'";
    $liitos_nimi_res = pupe_query($query);
    $liitos_nimi_row = mysql_fetch_assoc($liitos_nimi_res);

    echo "<br />".t("Valittu")." ".t(ucwords($taulu)), ": {$liitos_nimi_row['nimi']} {$liitos_nimi_row['nimitark']}<br>";
  }

  if ($trow['tila'] == 'X') {
    $tiliasiakasid = $tilitoimittajaid = "";

    if ($liitos == "A" and $liitostunnus > 0) {
      $tiliasiakasid = $liitostunnus;
    }
    elseif ($liitos == "T" and $liitostunnus > 0) {
      $tilitoimittajaid = $liitostunnus;
    }

    echo "<br>";
    echo t("Asiakas").":    ".livesearch_kentta("uusi", "ASIAKASHAKU", "tiliasiakasid", 170, $tiliasiakasid, "EISUBMIT");
    echo t("Toimittaja").": ".livesearch_kentta("uusi", "TOIMITTAJAHAKU", "tilitoimittajaid", 170, $tilitoimittajaid, "EISUBMIT");
    echo "</div>";
  }

  echo "</td>";

  if ($viivatut == 'on') {
    echo "<td></td>";
  }

  if ($virhe != "") $virhe .= "<br>";
  echo "<input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />";
  echo "<td valign='top'><input type='submit' class='hae_lisaselite' value = '".t("Lisää")."'></td>";

  if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") === FALSE and strpos($_SERVER['SCRIPT_NAME'], "selaajamuuta.php") === FALSE) {
    echo "<td valign='top' class='back' nowrap><font class='error'>$virhe</font><input type='checkbox' name = 'jaksota'> ".t("Jaksota")."</td>";
  }
  else {
    echo "<td valign='top' class='back' nowrap><font class='error'>$virhe</font></td>";
  }

  echo "</tr>";
  echo "</form>";
  $formi = 'uusi';
  $kentta = 'tili';
}

$lisa = '';

if ($viivatut != 'on') {
  $lisa = " and tiliointi.korjattu = ''";
}

// tarkistetaan mikä tila otsikolla on
$query = "SELECT tila
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$tunnus}'";
$tila_chk_res = pupe_query($query);
$tila_chk_row = mysql_fetch_assoc($tila_chk_res);

if ($grouppaa != '' and $viivatut == '') {
  $summaselect = "  max(tiliointi.korjattu) korjattu,
            group_concat(DISTINCT tiliointi.selite) selite,
            group_concat(tiliointi.tunnus) tunnus,
                    sum(tiliointi.summa) summa,
                    sum(tiliointi.summa_valuutassa) summa_valuutassa,
            count(tiliointi.tunnus) tunnuksia ";

  $grouppaus = " GROUP BY 1,2,3,4,5,6,7,8,9 ";
}
else {
  $summaselect = "  tiliointi.korjattu,
            ifnull(ko.nimi, tiliointi.korjattu) korjattu_nimi,
            tiliointi.korjausaika,
            tiliointi.laatija laatija,
            tiliointi.laadittu laadittu,
            ifnull(la.nimi, tiliointi.laatija) laatija_nimi,
            tiliointi.selite selite,
            tiliointi.tunnus tunnus,
                    tiliointi.summa summa,
                    tiliointi.summa_valuutassa summa_valuutassa ";
  $grouppaus = "";
}

$query = "SELECT
          tiliointi.tilino tilino,
          tili.nimi tilinimi,
          concat_ws(' / ', a.nimi, b.nimi, c.nimi) tarkenne,
          tiliointi.vero vero,
          tiliointi.tapvm,
          tiliointi.liitos,
          tiliointi.liitostunnus,
          tiliointi.valkoodi,
          tiliointi.lukko,
          tiliointi.korjattu,
          tili.manuaali_esto,
          $summaselect
          FROM tiliointi
          LEFT JOIN tili ON tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
          LEFT JOIN kustannuspaikka a ON a.yhtio = '$kukarow[yhtio]' and a.tunnus = tiliointi.kustp and a.tyyppi = 'K'
          LEFT JOIN kustannuspaikka b ON b.yhtio = '$kukarow[yhtio]' and b.tunnus = tiliointi.kohde and b.tyyppi = 'O'
          LEFT JOIN kustannuspaikka c ON c.yhtio = '$kukarow[yhtio]' and c.tunnus = tiliointi.projekti and c.tyyppi = 'P'
          LEFT JOIN kuka la ON (la.yhtio = tiliointi.yhtio and la.kuka = tiliointi.laatija)
          LEFT JOIN kuka ko ON (ko.yhtio = tiliointi.yhtio and ko.kuka = tiliointi.korjattu)
          WHERE tiliointi.yhtio = '$kukarow[yhtio]'
          and tiliointi.ltunnus = '$tunnus'
          $lisa
          $grouppaus
          ORDER BY tiliointi.tapvm, tiliointi.tilino, tiliointi.laadittu, tiliointi.tunnus";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
  echo "<br><br><font class='message'>".t("Laskulla ei ole vielä tiliöintirivejä")."</font><br><br>";
}

$maara = mysql_num_rows($result);

while ($trow = mysql_fetch_assoc($result)) {

  $class = "";

  if ($grouppaus == "" and $trow["korjattu"] != '') {
    $class = "tumma";
  }

  echo "<tr class='aktiivi'>";
  echo "<td valign='top' class='$class'>$trow[tilino] &raquo; $trow[tilinimi]</td>";
  echo "<td valign='top' class='$class'>$trow[tarkenne]</td>";
  echo "<td valign='top' class='$class' align='right' nowrap>$trow[summa]</td>";
  echo "<td valign='top' class='$class' align='right'>". (float) $trow["vero"]. "</td>";
  echo "<td align='center' valign='top' class='$class'>".tv1dateconv($trow["tapvm"])."</td>";
  echo "<td valign='top' class='$class'>";

  if (strlen($trow["selite"]) > 255) {
    echo substr($trow["selite"], 0, 255)."...";
  }
  else {
    echo "$trow[selite]";
  }

  if ($trow["summa_valuutassa"] != 0 and $trow["valkoodi"] != "" and $trow["valkoodi"] != $yhtiorow["valkoodi"]) {
    echo " ($trow[summa_valuutassa] $trow[valkoodi])";
  }

  if ($tila_chk_row['tila'] == 'X' and $trow['liitos'] != '' and $trow['liitostunnus'] > 0) {

    if ($trow['liitos'] == "A") {
      $taulu = "asiakas";
    }
    elseif ($trow['liitos'] == "T") {
      $taulu = "toimi";
    }
    else {
      $taulu = "SQLERROR";
    }

    $query = "SELECT nimi, nimitark
              FROM {$taulu}
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$trow['liitostunnus']}'";
    $liitos_nimi_res = pupe_query($query);
    $liitos_nimi_row = mysql_fetch_assoc($liitos_nimi_res);

    echo "<br />", t(ucwords($taulu)), ": {$liitos_nimi_row['nimi']} {$liitos_nimi_row['nimitark']}";
  }

  echo "</td>";

  if ($viivatut == "on") {
    echo "<td class='$class' valign='top'>".tv1dateconv($trow["laadittu"], "pitkä")." &raquo; $trow[laatija_nimi]";
    if ($trow["korjattu"] != "") {
      echo "<br>".tv1dateconv($trow["korjausaika"], "pitkä")." &raquo; $trow[korjattu_nimi]";
    }
    echo "</td>";
  }

  $tilikausi_loppu = strtotime($yhtiorow['tilikausi_loppu']);
  $tilikausi_alku = strtotime($yhtiorow['tilikausi_alku']);
  $tiliointi_pvm = strtotime($trow['tapvm']);

  if ($grouppaus != "" and $trow["tunnuksia"] > 1) {
    echo "<td>".t("Tiivistetty")."</td>";
  }
  elseif ((mysql_num_rows($keikres) == 0 or $trow["vero"] == 0)
    and $tiliointi_pvm <= $tilikausi_loppu
    and $tiliointi_pvm >= $tilikausi_alku
    and ($trow["lukko"] == "" or $kukarow["taso"] == 3)
    and $trow["korjattu"] == ""
    and $maara > 1
    and $oikeurow["paivitys"] == 1
    and ($kukarow["taso"] == 2 or $kukarow["taso"] == 3)
    and $trow["manuaali_esto"] == '') {
    //Eli ei näytetä muuta-nappia, jos ei oikeuksia,
    //jos rivi on poistettu TAI se on tiliöinnin viimeinen TAI se on lukittu,
    //jos lasku on liitetty saapumiseen ja rivillä on veroa
    //jos sen saa poistaa niin on hankalaa löytää heittävät tositteet...
    //tai rivilla on manuaali_esto
    echo "<td>
        <form action = '$PHP_SELF#$tunnus' method='post'>
        <input type='hidden' name = 'tilirivformi' value = 'submitattu'>
        <input type='hidden' name = 'lopetus' value = '$lopetus'>
        <input type='hidden' name = 'tee' value = 'P'>
        <input type='hidden' name = 'tunnus' value = '$tunnus'>
        <input type='hidden' name = 'viivatut' value = '$viivatut'>
        <input type='hidden' name = 'grouppaa' value = '$grouppaa'>
        <input type='hidden' name = 'laji' value = '$laji'>
        <input type='hidden' name = 'vv' value = '$vv'>
        <input type='hidden' name = 'kk' value = '$kk'>
        <input type='hidden' name = 'ptunnus' value = '$trow[tunnus]'>
        <input type='hidden' name = 'iframe' value = '$iframe'>
        <input type='hidden' name = 'iframe_id' value = '$iframe_id'>
        <input type='hidden' name = 'jarj' value = '$jarj'>
        <input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />
        <input type='submit' class='hae_lisaselite' value = '".t("Muuta")."'>
        </td></tr></form>";
  }
  else {
    if ($maara > 1) {

      if (mysql_num_rows($keikres) != 0
        or $trow['lukko'] != ''
        or $oikeurow['paivitys'] != 1
        or ($kukarow["taso"] != 2 and $kukarow["taso"] != 3)
        or $tiliointi_pvm > $tilikausi_loppu
        or $tiliointi_pvm < $tilikausi_alku
        or $trow["manuaali_esto"] != '') {

        echo "<td class='$class'>";
        // Rivi on poistettu
        if ($trow['korjattu'] != '') {
          echo "<img src=pics/lullacons/mini-trash.png>";
        }
        else {
          echo "<img src=pics/lullacons/error.png>";
        }
        echo "</td></tr>";
      }
      else {
        echo "<td class='$class'><img src=pics/lullacons/mini-trash.png></td></tr>";
      }
    }
    else {
      echo "<td class='$class'>".t("Viimeinen")."</td></tr>";
    }
  }

  if ($trow['korjattu'] == '') {
    $yhtsum += $trow['summa']; //Riviä ei ole poistettu
  }
}

$yhtok = t("Tiliöinti kesken");
$class = "error";
$ok    = 0;

if (round($yhtsum, 2) == 0) {
  $yhtok = t("Tiliöinti ok");
  $class = "ok";
  $ok    = 1;
}

if ($viivatut != '') {
  $viivatut = 'checked';
  $colspan = '3';
}
else {
  $viivatut = '';
  $colspan = '2';
}

if ($grouppaa != '') {
  $grouppaa = 'checked';
}
else {
  $grouppaa = '';
}

echo "<tr>";
echo "<td colspan='2'><font class='$class'>$yhtok</font></td>";
echo "<td align='right'><font class='$class'>". sprintf("%.02f", $yhtsum) . "</font></td>";
echo "<td colspan='2'></td>";
echo "<td colspan='$colspan'>";

echo "<form action = '$PHP_SELF#$tunnus' method='post'>
    <input type='hidden' name = 'tilirivformi' value = 'submitattu'>
    <input type='hidden' name = 'laji' value = '$laji'>
    <input type='hidden' name = 'vv' value = '$vv'>
    <input type='hidden' name = 'kk' value = '$kk'>
    <input type='hidden' name = 'tee' value = 'E'>
    <input type='hidden' name = 'lopetus' value = '$lopetus'>
    <input type='hidden' name = 'tunnus' value = '$tunnus'>
    <input type='hidden' name = 'iframe' value = '$iframe'>
    <input type='hidden' name = 'iframe_id' value = '$iframe_id'>
    <input type='hidden' name = 'jarj' value = '$jarj'>";

echo "<input type='checkbox' name='viivatut' onclick='submit();' $viivatut> ".t('Näytä poistetut')."<br>";
echo "<input type='checkbox' name='grouppaa' onclick='submit();' $grouppaa> ".t('Tiivistetty näkymä');
echo "</form>";
echo "</td>";

echo "</tr>";

echo "<tr>";

$query = "SELECT liitostunnus, tilaustyyppi
          FROM lasku
          WHERE yhtio = '$kukarow[yhtio]'
          and tunnus  = '$tunnus'";
$lasres = pupe_query($query);
$mtklasrow = mysql_fetch_assoc($lasres);

//  Saadaanko muokata superina?
$query = "SELECT yhtio
          FROM oikeu
          WHERE yhtio = '$kukarow[yhtio]'
          and kuka    = '$kukarow[kuka]'
          and nimi    = 'matkalasku.php'
          and alanimi = 'SUPER'";
$oires = pupe_query($query);

if (mysql_num_rows($oires) > 0) {
  $su = "SUPER";
}
else {
  $su = "";
}

$query = "SELECT tunnus
          FROM toimi
          WHERE yhtio          = '$kukarow[yhtio]'
          AND tunnus           = '$mtklasrow[liitostunnus]'
          AND laskun_erittely != ''";
$toimires = pupe_query($query);

if (mysql_num_rows($toimires)==1) {
  echo "  <td class='back'><form action = '".$palvelin2."matkalasku.php' method='get'>
        <input type='hidden' name = 'tilausnumero' value='$tunnus'>
        <input type='hidden' name = 'tee' value='ERITTELE'>
        <input type='hidden' name = 'toim' value='$su'>";

  if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") !== FALSE) {
    echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."hyvak.php////tunnus=$tunnus'>";
  }
  else {
    echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."muutosite.php////tee=E//tunnus=$tunnus'>";
  }

  echo "<input type='submit' value='".t("Kululaskun erittely")."'></form></td>";
}

if ($mtklasrow["tilaustyyppi"] == "M") {
  echo "<td class='back'><form action = '".$palvelin2."matkalasku.php' method='post'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type='hidden' name = 'tilausnumero' value='$tunnus'>
        <input type='hidden' name = 'tee' value='MUOKKAA'>
        <input type='hidden' name = 'toim' value='$su'>";

  if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") !== FALSE) {
    echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."hyvak.php////tunnus=$tunnus'>";
  }
  else {
    echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."muutosite.php////tee=E//tunnus=$tunnus'>";
  }

  echo "<input type='submit' value='".t("Tarkastele matkalaskua")."'>
        </form></td>";
}

echo "</tr>";
echo "</table>";
