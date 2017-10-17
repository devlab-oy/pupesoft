<?php

echo "<font class=head>".t("Avoimet laskut")."</font><hr>";

// scripti balloonien tekemiseen
js_popup();

if (!isset($asiakasid))            $asiakasid = "";
if (!isset($asitoimittain))        $asitoimittain = "";
if (!isset($karhuja))              $karhuja = "";
if (!isset($karhutieto))           $karhutieto = isset($_COOKIE["karhutieto"]) ? $_COOKIE["karhutieto"] : "";
if (!isset($laji))                 $laji = "";
if (!isset($luottovakuutettu))     $luottovakuutettu = "";
if (!isset($maa))                  $maa = "";
if (!isset($maksuehto))            $maksuehto = "";
if (!isset($muutparametrit))       $muutparametrit = "";
if (!isset($naytetaankokommentit)) $naytetaankokommentit = "";
if (!isset($savalkoodi))           $savalkoodi = "";
if (!isset($tee))                  $tee = "";
if (!isset($toimipaikka))          $toimipaikka = null;
if (!isset($toimittajaid))         $toimittajaid = "";
if (!isset($valuutassako))         $valuutassako = "";
if (!isset($ytunnus))              $ytunnus = "";

if ($muutparametrit != '') list($tee, $vv, $kk, $pp, $laji, $naytetaankokommentit, $savalkoodi, $valuutassako, $maa, $maksuehto, $excel) = explode("/", $muutparametrit);

// Tarkistetaan pvm
if ($tee != '') {
  // muutetaan numeroiksi
  $kk += 0;
  $pp += 0;
  $vv += 0;
  $pvm = sprintf("%04d", $vv) . "-" . sprintf("%02d", $kk) . "-" . sprintf("%02d", $pp);

  if (!checkdate($kk, $pp, $vv)) {
    echo "<font class='error'>".t("Virheellinen pvm")."</font><br>";
    $tee = '';
  }

  $paivamaaralisa = "";

  if ($pp2 !='' and $kk2 !='' and $vv2 !='') {

    if (!checkdate($kk2, $pp2, $vv2)) {
      echo "<font class='error'>".t("Virheellinen alkupäivämäärä")."</font><br>";
      $tee = '';
    }
    else {
      $pvm2 = sprintf("%04d", $vv2) . "-" . sprintf("%02d", $kk2) . "-" . sprintf("%02d", $pp2);
      $paivamaaralisa = " AND lasku.tapvm >= '$pvm2' ";
    }
  }

  // katotaan onko annettu tämä päivä (että voidaanko näyttää kirjanpitovertailuja
  $onkotanaan = "";
  if ($pvm != date("Y-m-d")) {
    $onkotanaan = "NO";
  }

  // Löytyykö oikea tilikausi?
  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '$kukarow[yhtio]'
            and '$pvm' >= tilikausi_alku
            and '$pvm' <= tilikausi_loppu";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo "<font class='error'>".t("Päivämäärälle ei löydy tilikautta")."</font><br>";
    $tee = '';
  }
  else {
    $tilikausirow = mysql_fetch_assoc($result);
  }
}

// Valitaan asiakas, jos sitä ei tiedetä
if ($tee != '' and $ytunnus != '') {

  $ytunnus = mysql_real_escape_string($ytunnus);
  $muutparametrit = $tee."/".$vv."/".$kk."/".$pp."/".$laji."/".$naytetaankokommentit."/".$savalkoodi."/".$valuutassako."/".$maa."/".$maksuehto."/".$excel;

  if (substr($laji, 0, 1) == 'M') {
    include "asiakashaku.inc";
    if ($asiakasid == '') {
      $tee = '';
      echo "<br>";
    }
    else $ajaraportti = 'JOO';
  }

  if (substr($laji, 0, 1) == 'O') {
    include "kevyt_toimittajahaku.inc";
    if ($toimittajaid == '') {
      $tee = '';
      echo "<br>";
    }
    else $ajaraportti = 'JOO';
  }
}

// mikä kuu/vuosi nyt on
$year = date("Y");
$kuu  = date("n");

if (!isset($vv)) $vv = date("Y");
if (!isset($kk)) $kk = date("n");
if (!isset($pp)) $pp = date("j");

$sel = array_fill_keys(array($laji), " selected") + array_fill_keys(array('M', 'MF', 'MK', 'MMK', 'MA', 'O', 'OK', 'OOK'), '');

echo "  <form name = 'valinta' action = 'raportit.php' method='post'>
    <input type = 'hidden' name = 'toim' value = 'avoimet'>
    <input type = 'hidden' name = 'tee' value = 'X'>
    <table>
    <tr>
    <th>".t("Anna päivämäärä, jonka tilanteen haluat:")."</th>
    <td><input type = 'text' name = 'pp' value='$pp' size=2></td>
    <td><input type = 'text' name = 'kk' value='$kk' size=2></td>
    <td><input type = 'text' name = 'vv' value='$vv' size=4></td>
    </tr>";

echo "  <tr>
    <th>".t("Anna alkupäivämäärä, jota vanhempia laskuja ei näytetä:")."</th>
    <td><input type = 'text' name = 'pp2' value='$pp2' size=2></td>
    <td><input type = 'text' name = 'kk2' value='$kk2' size=2></td>
    <td><input type = 'text' name = 'vv2' value='$vv2' size=4></td>
    </tr>";

echo "  <tr>
    <th>".t("Mitkä laskut listataan:")."</th>
    <td colspan = '3'><select name='laji' onchange='submit();'>
    <option value='M'   $sel[M]>".t("myyntisaamiset")."</option>
    <option value='MF'  $sel[MF]>".t("factoringmyyntisaamiset")."</option>
    <option value='MK'  $sel[MK]>".t("konsernimyyntisaamiset")."</option>
    <option value='MMK' $sel[MMK]>".t("myyntisaamiset + konsernimyyntisaamiset")."</option>
    <option value='MA'  $sel[MA]>".t("myyntisaamiset + factoringmyyntisaamiset + konsernimyyntisaamiset")."</option>
    <option value='O'   $sel[O]>".t("ostovelat")."</option>
    <option value='OK'  $sel[OK]>".t("konserniostovelat")."</option>
    <option value='OOK' $sel[OOK]>".t("ostovelat + konserniostovelat")."</option>
    </select></td>
    </tr>";

echo "  <tr>
    <th>".t("Vain toimittaja/asiakas").":</th>
    <td colspan='3'><input type = 'text' name = 'ytunnus' value='".tarkistahetu($ytunnus)."'>";

if ($asiakasid > 0) {
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  = '$asiakasid'";
  $result = pupe_query($query);
  $asiakasrow = mysql_fetch_assoc($result);

  echo "$asiakasrow[nimi] $asiakasrow[nimitark] $asiakasrow[toim_nimi] $asiakasrow[toim_nimitark]";
}

echo "</td>
    </tr>";

echo "<tr><th>".t("Rajauksia")."</th><td colspan='3'>";

if (substr($laji, 0, 1) != 'O') $monivalintalaatikot = array("KUSTP", "ASIAKASOSASTO", "ASIAKASMYYJA");
else $monivalintalaatikot = array("KUSTP");
$noautosubmit = TRUE;
require "tilauskasittely/monivalintalaatikot.inc";
echo "</td></tr>";

$toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio']);

if (mysql_num_rows($toimipaikkares) > 0) {

  echo "<tr>";
  echo "<th>", t("Toimipaikka"), "</th>";

  $sel = (isset($toimipaikka) and $toimipaikka === "kaikki") ? 'selected' : '';

  echo "<td colspan='3'><select name='toimipaikka'>";
  echo "<option value='kaikki' {$sel}>", t("Kaikki toimipaikat"), "</option>";

  $sel = (isset($toimipaikka) and empty($toimipaikka)) ? 'selected' : '';

  echo "<option value='0' {$sel}>".t('Ei toimipaikkaa')."</option>";

  $sel = "";

  while ($toimipaikkarow = mysql_fetch_assoc($toimipaikkares)) {
    if (!isset($toimipaikka) and $kukarow['toimipaikka'] == $toimipaikkarow['tunnus']) {
      $sel = 'selected';
      $toimipaikka = $kukarow['toimipaikka'];
    }
    elseif (isset($toimipaikka) and $toimipaikka == $toimipaikkarow['tunnus']) {
      $sel = 'selected';
    }
    else {
      $sel = '';
    }

    echo "<option value='{$toimipaikkarow['tunnus']}' {$sel}>{$toimipaikkarow['nimi']}</option>";
  }

  echo "</select></td>";
  echo "</tr>";
}

if ($naytetaankokommentit != "") $komsel = "CHECKED";
else $komsel = "";

echo "  <tr>
    <th>".t("Älä näytä laskujen kommentteja").":</th>
    <td colspan='3'><input type = 'checkbox' name = 'naytetaankokommentit' $komsel></td>
    </tr>";

$query = "SELECT nimi, tunnus
          FROM valuu
          WHERE yhtio = '$kukarow[yhtio]'
          ORDER BY jarjestys";
$vresult = pupe_query($query);

echo "<tr><th>".t('Näytä vain valuutta') .":</th><td colspan=3><select name='savalkoodi'>";
echo "<option value = ''>".t("Kaikki")."</option>";

while ($vrow = mysql_fetch_assoc($vresult)) {
  $sel="";
  if (strtoupper($vrow['nimi']) == strtoupper($savalkoodi)) {
    $sel = "selected";
  }
  echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
}

echo "</select></td>";
echo "</tr>";

$sel1 = '';

if ($valuutassako == 'V') {
  $sel1 = "SELECTED";
}

echo "<tr><th>".t("Summat valuutassa").":</th>";
echo "<td colspan=3><select name='valuutassako'>";
echo "<option value = ''>".t("Yrityksen valuutassa")."</option>";
echo "<option value = 'V' $sel1>".t("Laskun valuutassa")."</option>";
echo "</select></td></tr>";

$sel1 = '';

if ($asitoimittain == 'AT') {
  $sel1 = "SELECTED";
}

echo "<tr><th>".t("Summaustaso").":</th>";
echo "<td colspan=3><select name='asitoimittain'>";
echo "<option value = ''>".t("Lasku")."</option>";
echo "<option value = 'AT' $sel1>".t("Asiakas/Toimittaja")."</option>";
echo "</select></td></tr>";

echo "<tr><th>".t('Maa').":</th><td colspan=3><select name='maa'>";

$query = "SELECT distinct maa
          FROM asiakas
          WHERE yhtio  = '$kukarow[yhtio]'
          AND maa     != ''";
$res = pupe_query($query);

echo "<option value=''>".t('Kaikki')."</option>";

while ($rowmaa = mysql_fetch_assoc($res)) {
  if ($maa == $rowmaa['maa']) {
    $sel = "SELECTED";
  }
  else {
    $sel = "";
  }

  echo "<option value='$rowmaa[maa]' $sel>".maa($rowmaa['maa'])."</option>";
}

echo "</td></tr>";

if (substr($laji, 0, 1) != 'O') {
  echo "<tr><th>".t("Maksuehto").":</th>";
  echo "<td colspan='3'><select name='maksuehto'>";
  echo "<option value=''>".t("Ei valintaa");

  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys, teksti, tunnus";
  $mresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($mresult)) {
    if ($row["tunnus"] == $maksuehto) {
      $sel = 'selected';
    }
    else {
      $sel = "";
    }

    echo "<option value='$row[tunnus]' $sel>".t_tunnus_avainsanat($row, "teksti", "MAKSUEHTOKV")."</option>";
  }

  echo "</select></td></tr>";

  echo "<tr>";
  echo "<th>".t("Näytä laskut, joista on lähetetty maksukehotus ainakin")."</th>";
  echo "<td colspan='3'>";
  echo "<input type='text' size='5' name='karhuja' value='$karhuja'> ".t("kertaa");
  echo "</td>";
  echo "</tr>";

  $chk = empty($karhutieto) ? '' : 'checked';

  echo "<tr>";
  echo "<th>".t("Näytä maksukehotuksien tietoja listalla")."</th>";
  echo "<td colspan='3'>";
  echo "<input type='checkbox' name='karhutieto' {$chk}>";
  echo "<input type='hidden' name='tallenna_keksiin' value='joo'>";
  echo "</td>";
  echo "</tr>";

  // luottovakuutettu
  echo "<tr>";
  echo "<th>".t("Näytä vain luottovakuutetut asiakkaat")."</th>";

  $luottochecked = ($luottovakuutettu == "K") ? "CHECKED" : "";

  echo "<td colspan='3'>";
  echo "<input type='checkbox' name='luottovakuutettu' value='K' $luottochecked>";
  echo "</td></tr>";
}

if ($excel != "") $exsel = "CHECKED";
else $exsel = "";

echo "<tr><th>".t("Tee Excel")."</th><td colspan='3'><input type='checkbox' name='excel' value = 'YES' $exsel></td></tr>";

echo "</table><br>";
echo "<input type = 'submit' name = 'ajaraportti' value = '".t("Aja raportti")."'>";
echo "</form><br><br>";

$formi = 'valinta';
$kentta = 'pp';

if ($tee != '' and isset($ajaraportti)) {
 switch ($laji) {
  case 'M' :
    $lista = t("myyntisaamiset");
    break;
  case 'MF' :
    $lista = t("factoringmyyntisaamiset");
    break;
  case 'MK' :
    $lista = t("konsernimyyntisaamiset");
    break;
  case 'MMK' :
    $lista = t("myyntisaamiset + konsernimyyntisaamiset");
    break;
  case 'MA' :
    $lista = t("myyntisaamiset + factoringmyyntisaamiset + konsernimyyntisaamiset");
    break;
  case 'O' :
    $lista = t("ostovelat");
    break;
  case 'OK' :
    $lista = t("konserniostovelat");
    break;
  case 'OOK' :
    $lista = t("ostovelat + konserniostovelat");
    break;
  }

  $valkoodi  = "";
  $valkoodi2 = "";

  if (isset($savalkoodi) and $savalkoodi != '') {
    $valkoodi1 = $valkoodi = " and lasku.valkoodi = '$savalkoodi'";
    $valkoodi2 = " and suoritus.valkoodi = '$savalkoodi'";
  }

  $tiliointilisa = "";

  if (isset($lisa) and strpos($lisa, "tiliointi.") !== FALSE) {
    preg_match("/and tiliointi.kustp in \([',0-9]*?\)/", $lisa, $kustpeet);
    $tiliointilisa = $kustpeet[0];
  }

  $maalisa = $maalisa2 = '';

  if (isset($maa) and $maa != '') {
    $maalisa  = " and lasku.maa = '$maa'";
    $maalisa2 = " and asiakas.maa = '$maa'";
  }

  if (substr($laji, 0, 1) == 'O') {

    $av_lisa = $toimipaikkalisa = '';

    if ($toimittajaid != '') {
      $av_lisa = " and lasku.liitostunnus='$toimittajaid'";
      $indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
    }
    else {
      $indeksi = " use index (yhtio_tila_tapvm)";
    }

    if (isset($toimipaikka) and $toimipaikka !== "kaikki") {
      $toimipaikkalisa = " and lasku.yhtio_toimipaikka = {$toimipaikka} ";
    }

    if ($laji == 'OOK')   $tili = "'".$yhtiorow['ostovelat']."','".$yhtiorow['konserniostovelat']."'";
    elseif ($laji == 'OK')   $tili = "'".$yhtiorow['konserniostovelat']."'";
    else           $tili = "'".$yhtiorow['ostovelat']."'";

    if ($asitoimittain == "AT") {
      $query = "SELECT
                lasku.liitostunnus,
                lasku.ytunnus,
                lasku.maa,
                lasku.valkoodi,
                group_concat(lasku.comments) comments,
                concat_ws(' ', min(lasku.erpcm), max(lasku.erpcm)) erpcm,
                concat_ws(' ', min(lasku.mapvm), max(lasku.mapvm)) mapvm,
                max(lasku.nimi) nimi,
                concat_ws(' ', min(lasku.olmapvm), max(lasku.olmapvm)) olmapvm,
                concat_ws(' ', min(lasku.laskunro), max(lasku.laskunro)) laskunro,
                concat_ws(' ', min(lasku.tapvm), max(lasku.tapvm)) tapvm,
                max(lasku.tunnus) tunnus,
                concat_ws(' ', min(concat_ws(' ', viite, viesti)), max(concat_ws(' ', viite, viesti))) maksutieto,
                sum(tiliointi.summa * -1) avoinsaldo,
                sum(round(lasku.summa * lasku.vienti_kurssi, 2)) laskun_summa,
                sum(lasku.summa) summa";

      $groupby = "GROUP BY 1,2,3,4";
    }
    else {
      $query = "SELECT
                lasku.ytunnus,
                lasku.maa,
                lasku.valkoodi,
                lasku.comments,
                lasku.erpcm,
                lasku.mapvm,
                lasku.nimi,
                lasku.olmapvm,
                if(lasku.laskunro = 0, '', laskunro) laskunro,
                lasku.tapvm,
                lasku.tunnus,
                concat_ws(' ', viite, viesti) maksutieto,
                tiliointi.summa * -1 avoinsaldo,
                round(lasku.summa * lasku.vienti_kurssi, 2) laskun_summa,
                lasku.summa";

      $groupby = "";
    }

    $query .= "  FROM lasku use index (yhtio_tila_tapvm)
          JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio=tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tapvm = tiliointi.tapvm and tiliointi.tilino IN ($tili) and tiliointi.korjattu = '' {$tiliointilisa})
          WHERE lasku.yhtio = '$kukarow[yhtio]'
          and (mapvm > '$pvm' or mapvm = '0000-00-00')
          $paivamaaralisa
          and lasku.tapvm <= '$pvm'
          and lasku.tapvm > '0000-00-00'
          and tila in ('H','Y','M','P','Q')
          {$toimipaikkalisa}
          $maalisa
          $av_lisa
          $valkoodi1
          $groupby
          ORDER BY lasku.ytunnus";
    $result = pupe_query($query);

    // poimitaan kuluva päivä, raportin timestampille
    $today = date("Y-m-d");

    echo "<font class='message'>".t("Avoimet")." $lista</font><br><font class='message'>".t("Raportti otettu")." ".tv1dateconv($today)."</font><br>";

    if ($toimittajaid == '') {
      echo "<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." ".tv1dateconv($pvm)." ".t("seuraavat avoimet")." $lista:</font><br>";
    }

    pupe_DataTables(array(array($pupe_DataTables[0], 11, 12)));

    echo "<table class='display dataTable' id='$pupe_DataTables[0]'>";

    echo "<thead>";
    echo "<tr>
        <th>".t("Ytunnus")."</th>
        <th>".t("Nimi")."</th>
        <th>".t("Maa")."</th>
        <th>".t("Maksutieto")."</th>
        <th>".t("Laskunro")."</th>
        <th>".t("Pvm")."</th>
        <th>".t("Maksupäivä")."</th>
        <th>".t("Oletusmaksupäivä")."</th>
        <th>".t("Eräpäivä")."</th>
        <th>".t("Avoin saldo")."</th>
        <th>".t("Val")."</th>
        <th class='back' style='display:none;'></th>
        </tr>
        <tr>
        <td><input type='text' class='search_field' name='search_ytunnus'></td>
        <td><input type='text' class='search_field' name='search_nimi'></td>
        <td><input type='text' class='search_field' name='search_maa'></td>
        <td><input type='text' class='search_field' name='search_maksutieto'></td>
        <td><input type='text' class='search_field' name='search_laskunro'></td>
        <td><input type='text' class='search_field' name='search_pvm'></td>
        <td><input type='text' class='search_field' name='search_maksupaiva'></td>
        <td><input type='text' class='search_field' name='search_olmaksupaiva'></td>
        <td><input type='text' class='search_field' name='search_erapaiva'></td>
        <td><input type='text' class='search_field' name='search_avoinsaldo'></td>
        <td><input type='text' class='search_field' name='search_val'></td>
        <td class='back' style='display:none;'></td>
      </tr>";

    echo "</thead>";
    echo "<tbody>";

    if (isset($worksheet)) {
      $worksheet->write($excelrivi, 0, t("Ytunnus"), $format_bold);
      $worksheet->write($excelrivi, 1, t("Nimi"), $format_bold);
      $worksheet->write($excelrivi, 2, t("Maa"), $format_bold);
      $worksheet->write($excelrivi, 3, t("Maksutieto"), $format_bold);
      $worksheet->write($excelrivi, 4, t("Laskunro"), $format_bold);
      $worksheet->write($excelrivi, 5, t("Pvm"), $format_bold);
      $worksheet->write($excelrivi, 6, t("Maksupäivä"), $format_bold);
      $worksheet->write($excelrivi, 7, t("Oletusmaksupäivä"), $format_bold);
      $worksheet->write($excelrivi, 8, t("Eräpäivä"), $format_bold);
      $worksheet->write($excelrivi, 9, t("Avoin saldo"), $format_bold);
      $worksheet->write($excelrivi, 10, t("Val"), $format_bold);
      $excelrivi++;
    }

    $summa  = array();
    $summa2 = 0;

    while ($trow = mysql_fetch_assoc($result)) {
      if ($trow['avoinsaldo'] != 0) {

        // Laskun valuutassa
        $summa[$trow["valkoodi"]] += $trow['summa'];

        // Yrityksen valuutassa
        $summa2+= $trow['avoinsaldo'];

        echo "<tr class='aktiivi'>";
        echo "<td><a name='$trow[tunnus]' href='".$palvelin2."raportit.php?toim=toimittajahaku&tee=A&ytunnus=".tarkistahetu($trow["ytunnus"])."&alku=0&laji=M&lopetus=".$palvelin2."raportit.php////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=".tarkistahetu($ytunnus)."//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///$trow[tunnus]'>".tarkistahetu($trow["ytunnus"])."</a></td>";
        echo "<td>$trow[nimi]</td>";
        echo "<td>$trow[maa]</td>";
        echo "<td>$trow[maksutieto]";

        if ($trow["comments"] != "" and $naytetaankokommentit == "") {
          echo " <a class='tooltip' id='$trow[tunnus]'><img src='{$palvelin2}pics/lullacons/info.png'></a>";
          echo "<div id='div_$trow[tunnus]' class='popup' style='width:500px;'>";
          echo $trow["comments"];
          echo "</div>";
        }

        echo "</td>";

        if ($asitoimittain == "") {
          echo "<td><a name='$trow[tunnus]' href='".$palvelin2."muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=".$palvelin2."raportit.php////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=".tarkistahetu($ytunnus)."//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///$trow[tunnus]'>$trow[laskunro]</a></td>";
        }
        else {
          echo "<td>$trow[laskunro]</td>";
        }

        if ($trow["tapvm"] == "0000-00-00" or $trow["tapvm"] == "0000-00-00 0000-00-00") {
          $trow["tapvm"] = "";
          echo "<td align='right'>$trow[tapvm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['tapvm']).tv1dateconv($trow["tapvm"])."</td>";
        }
        if ($trow["mapvm"] == "0000-00-00" or $trow["mapvm"] == "0000-00-00 0000-00-00") {
          $trow["mapvm"] = "";
          echo "<td>$trow[mapvm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['mapvm']).tv1dateconv($trow["mapvm"])."</td>";
        }
        if ($trow["olmapvm"] == "0000-00-00" or $trow["olmapvm"] == "0000-00-00 0000-00-00") {
          $trow["olmapvm"] = "";
          echo "<td>$trow[olmapvm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['olmapvm']).tv1dateconv($trow["olmapvm"])."</td>";
        }
        if ($trow["erpcm"] == "0000-00-00" or $trow["erpcm"] == "0000-00-00 0000-00-00") {
          $trow["erpcm"] = "";
          echo "<td>$trow[erpcm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['erpcm']).tv1dateconv($trow["erpcm"])."</td>";
        }

        if ($valuutassako == "V") {
          // Laskun valuutassa
          echo "<td align='right'>$trow[summa]</td>";
          echo "<td align='right'>$trow[valkoodi]</td>";
        }
        else {
          // Yrityksen valuutassa
          echo "<td align='right'>$trow[avoinsaldo]</td>";
          echo "<td align='right'>$yhtiorow[valkoodi]</td>";
        }

        // jos ollaan ajettu tälle päivälle laskuittain voidaan echota errori avoimesta saldosta
        if ($asitoimittain == "" and $onkotanaan == "" and abs($trow["avoinsaldo"] - $trow["laskun_summa"]) >= 0.02) {
          echo "<td class='back' nowrap><font class='error'>VIRHE: Lasku / Kirjanpito heittää!</font> $trow[avoinsaldo] / $trow[laskun_summa]</td>";
        }
        else {
          echo "<td class='back'></td>";
        }

        echo "</tr>";

        if (isset($worksheet)) {
          $worksheet->writeString($excelrivi, 0, tarkistahetu($trow["ytunnus"]));
          $worksheet->writeString($excelrivi, 1, $trow["nimi"]);
          $worksheet->writeString($excelrivi, 2, $trow["maa"]);
          $worksheet->write($excelrivi, 3, $trow["maksutieto"]);
          $worksheet->write($excelrivi, 4, $trow["laskunro"]);
          $worksheet->write($excelrivi, 5, tv1dateconv($trow["tapvm"]));

          if ($trow["mapvm"] != '0000-00-00') $worksheet->write($excelrivi, 6, $trow["mapvm"]);
          else $worksheet->write($excelrivi, 6, "");

          $worksheet->write($excelrivi, 7, $trow["olmapvm"]);
          $worksheet->write($excelrivi, 8, $trow["erpcm"]);

          if ($valuutassako == "V") {
            $worksheet->writeNumber($excelrivi, 9, (float) $trow["summa"]);
            $worksheet->write($excelrivi, 10, $trow["valkoodi"]);
          }
          else {
            $worksheet->writeNumber($excelrivi, 9, (float) $trow["avoinsaldo"]);
            $worksheet->write($excelrivi, 10, $yhtiorow["valkoodi"]);
          }

          $excelrivi++;
        }
      }
    }

    echo "</tbody>";
    echo "<tfoot>";

    foreach ($summa as $val => $sum) {
      echo "<tr>";
      echo "<th colspan='9'>".t("Avoimet yhteensä").":</th>";
      echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_yhteensa_$val'>".sprintf("%.2f", $sum)."</td>";
      echo "<td class='tumma' align='right' nowrap>$val</td>";
      echo "</tr>";

      if (!in_array($val, $kaikkival)) $kaikkival[] = $val;
    }

    $query = "SELECT sum(summa) saldo
              FROM tiliointi
              WHERE yhtio  = '$kukarow[yhtio]'
              and korjattu = ''
              and tapvm    >= '$tilikausirow[tilikausi_alku]'
              and tapvm    <= '$pvm'
              and tilino   IN ($tili)
               {$tiliointilisa}";
    $result = pupe_query($query);
    $trow = mysql_fetch_assoc($result);

    $erotus = $trow['saldo']+$summa2;

    echo "<tr><td class='back' colspan='11'><br></td></tr>";
    echo "<tr><th colspan='9'>".t("Avoimet yhteensä").":</th><td class='tumma' align='right' nowrap>".sprintf("%01.2f", $summa2)."</td><th>$yhtiorow[valkoodi]</th></tr>";
    echo "<tr><th colspan='9'>".t("Tili %s yhteensä", "", $tili).":</th><td class='tumma' align='right' nowrap>".sprintf("%01.2f", $trow['saldo'])."</td><th>$yhtiorow[valkoodi]</th></tr>";
    echo "<tr><th colspan='9'>".t("Ero").":</th><td class='tumma' align='right' nowrap>".sprintf("%01.2f", $erotus)."</td><th>$yhtiorow[valkoodi]</th></tr>";

    echo "</tfoot>";
  }
  elseif (substr($laji, 0, 1) == 'M') {

    $av_lisa1 = ""; // lasku rajauksia
    $av_lisa2 = ""; // suoritus rajauksia
    $av_lisa3 = ""; // asiakas rajauksia
    $toimipaikkalisa = '';

    if ($asiakasid != '') {
      $av_lisa1 .= " and lasku.liitostunnus='$asiakasid' ";
      $av_lisa2 .= " and asiakas_tunnus='$asiakasid' ";
      $indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
    }
    else {
      $indeksi = " use index (yhtio_tila_mapvm) ";
    }

    if ($maksuehto != '') {
      $av_lisa1 .= " and lasku.maksuehto = '$maksuehto' ";
    }

    if (isset($toimipaikka) and $toimipaikka !== "kaikki") {
      $toimipaikkalisa = " and lasku.yhtio_toimipaikka = {$toimipaikka} ";
    }

    $tili = 0;

    if ($laji == 'MK')     $tili = "'$yhtiorow[konsernimyyntisaamiset]'";
    elseif ($laji == 'MF')   $tili = "'$yhtiorow[factoringsaamiset]'";
    elseif ($laji == 'MA')   $tili = "'$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]'";
    elseif ($laji == 'MMK') $tili = "'$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]'";
    else           $tili = "'$yhtiorow[myyntisaamiset]'";

    $tunnukset = '';
    $tunnuslisa = '';
    $karhuja = (int) $karhuja;

    // Halutaanko vain luottovakuutetut asiakkaat
    $luottovakuutuslisa = ($luottovakuutettu == "K") ? " and asiakas.luottovakuutettu = 'K' " : "";

    if ($karhuja > 0) {

      $query = "SELECT count(karhu_lasku.ltunnus) as karhukerrat,
                group_concat(karhu_lasku.ltunnus) as karhutunnukset
                FROM lasku
                JOIN karhu_lasku ON (lasku.tunnus = karhu_lasku.ltunnus)
                JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = '')
                WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
                AND lasku.tila    = 'U'
                AND lasku.alatila = 'X'
                AND lasku.mapvm   = '0000-00-00'
                GROUP BY karhu_lasku.ltunnus
                HAVING karhukerrat >= $karhuja";
      $tunnusres = pupe_query($query);

      if (mysql_num_rows($tunnusres) > 0) {

        while ($tunnusrivi = mysql_fetch_assoc($tunnusres)) {
          $tunnukset .= $tunnusrivi['karhutunnukset'].",";
        }

        $tunnukset = substr($tunnukset, 0, -1);
        $tunnuslisa = "AND lasku.tunnus in ($tunnukset)";
      }
    }

    if ($asitoimittain == "AT") {
      $query = "SELECT
                lasku.liitostunnus,
                lasku.ytunnus,
                if(asiakas.asiakasnro = 0 or asiakas.asiakasnro = asiakas.ytunnus, '', asiakas.asiakasnro) asiakasnro,
                lasku.maa,
                lasku.valkoodi,
                max(lasku.tunnus) tunnus,
                concat_ws(' ', min(lasku.erpcm), max(lasku.erpcm)) erpcm,
                max(lasku.liitostunnus) liitostunnus,
                concat_ws(' ', min(lasku.mapvm), max(lasku.mapvm)) mapvm,
                max(concat_ws('<br>', concat(lasku.nimi,' ',lasku.nimitark), concat(lasku.toim_nimi,' ',lasku.toim_nimitark))) nimi,
                concat_ws(' ', min(lasku.tapvm), max(lasku.tapvm)) tapvm,
                concat_ws(' ', min(lasku.laskunro), max(lasku.laskunro)) maksutieto,
                sum(lasku.summa-lasku.saldo_maksettu) laskuavoinsaldo,
                sum(lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa) laskuavoinsaldo_valuutassa,
                sum(lasku.summa) laskusumma,
                sum(lasku.summa_valuutassa) laskusumma_valuutassa,
                group_concat(lasku.tunnus) tunnukset,";

      $groupby = "GROUP BY 1,2,3,4,5";
    }
    else {
      $query = "SELECT
                lasku.ytunnus,
                if(asiakas.asiakasnro = 0 or asiakas.asiakasnro = asiakas.ytunnus, '', asiakas.asiakasnro) asiakasnro,
                lasku.maa,
                lasku.valkoodi,
                lasku.tunnus,
                lasku.erpcm,
                lasku.liitostunnus,
                lasku.mapvm,
                lasku.nimi,
                lasku.tapvm,
                lasku.laskunro maksutieto,
                lasku.summa-lasku.saldo_maksettu laskuavoinsaldo,
                lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa laskuavoinsaldo_valuutassa,
                lasku.summa laskusumma,
                lasku.summa_valuutassa laskusumma_valuutassa,";

      $groupby = "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15";
    }

    $query .= "  sum(tiliointi.summa) tiliointiavoinsaldo,
          sum(tiliointi.summa_valuutassa) tiliointiavoinsaldo_valuutassa,
          sum(if(tiliointi.tapvm = lasku.tapvm, tiliointi.summa, 0))-lasku.pyoristys tiliointisumma,
          sum(if(tiliointi.tapvm = lasku.tapvm, tiliointi.summa_valuutassa, 0))-lasku.pyoristys_valuutassa tiliointisumma_valuutassa,
          group_concat(lasku.tunnus) tunnukset
          FROM lasku $indeksi
          JOIN asiakas on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus {$luottovakuutuslisa})
          JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tilino in ($tili) and tiliointi.korjattu = '' and tiliointi.tapvm <= '$pvm')
          WHERE lasku.yhtio    = '$kukarow[yhtio]'
          and (lasku.mapvm    > '$pvm' or lasku.mapvm = '0000-00-00')
          $paivamaaralisa
          and lasku.tapvm      <= '$pvm'
          and lasku.tapvm     > '0000-00-00'
          and lasku.tila      = 'U'
          and lasku.alatila    = 'X'
          {$toimipaikkalisa}
          $lisa
          $av_lisa1
          $valkoodi
          $maalisa
          $tunnuslisa
          $groupby
          ORDER BY lasku.ytunnus, lasku.laskunro";
    $result = pupe_query($query);

    // poimitaan kuluva päivä, raportin timestampille
    $today = date("Y-m-d");

    echo "<font class='message'>".t("Avoimet")." $lista</font><br><font class='message'>".t("Raportti otettu")." ".tv1dateconv($today)."</font><br>";

    if ($toimittajaid == '') {
      echo "<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." ".tv1dateconv($pvm)." ".t("seuraavat avoimet")." $lista:</font><br>";
    }

    if (!empty($karhutieto)) {
      pupe_DataTables(array(array($pupe_DataTables[1], 13, 14)));
    }
    else {
      pupe_DataTables(array(array($pupe_DataTables[1], 11, 12)));
    }

    echo "<table class='display dataTable' id='$pupe_DataTables[1]'>";

    echo "<thead>";
    echo "<tr>
        <th>".t("Ytunnus")."</th>
        <th>".t("Asiakasnro")."</th>
        <th>".t("Nimi")."</th>
        <th>".t("Maa")."</th>
        <th>".t("Laskunro")."</th>
        <th>".t("Pvm")."</th>
        <th>".t("Maksupäivä")."</th>
        <th>".t("Eräpäivä")."</th>
        <th>".t("Laskun summa")."</th>
        <th>".t("Avoin saldo")."</th>
        <th>".t("Val")."</th>";

    if (!empty($karhutieto)) {
      echo "<th>".t("Maksukehotuksia")."</th>";
      echo "<th>".t("Viimeisin maksukehotus")."</th>";
    }

    echo "<th style='visibility:hidden;'></th>
        </tr>
        <tr>
        <td><input type='text' class='search_field' name='search_ytunnus'></td>
        <td><input type='text' class='search_field' name='search_asiakasnro'></td>
        <td><input type='text' class='search_field' name='search_nimi'></td>
        <td><input type='text' class='search_field' name='search_maa'></td>
        <td><input type='text' class='search_field' name='search_laskunro'></td>
        <td><input type='text' class='search_field' name='search_pvm'></td>
        <td><input type='text' class='search_field' name='search_maksupaiva'></td>
        <td><input type='text' class='search_field' name='search_erapvm'></td>
        <td><input type='text' class='search_field' name='search_summa'></td>
        <td><input type='text' class='search_field' name='search_avoinsaldo'></td>
        <td><input type='text' class='search_field' name='search_val'></td>";

    if (!empty($karhutieto)) {
      echo "<td><input type='text' class='search_field' name='search_maksukehotuksia'></td>";
      echo "<td><input type='text' class='search_field' name='search_viim_maksukehotus'></td>";
    }

    echo "<td style='visibility:hidden;'></td>
      </tr>";

    echo "</thead>";
    echo "<tbody>";

    if (isset($worksheet)) {
      $worksheet->write($excelrivi,  0, t("Ytunnus"), $format_bold);
      $worksheet->write($excelrivi,  1, t("Asiakasnro"), $format_bold);
      $worksheet->write($excelrivi,  2, t("Nimi"), $format_bold);
      $worksheet->write($excelrivi,  3, t("Maa"), $format_bold);
      $worksheet->write($excelrivi,  4, t("Laskunro"), $format_bold);
      $worksheet->write($excelrivi,  5, t("Pvm"), $format_bold);
      $worksheet->write($excelrivi,  6, t("Maksupäivä"), $format_bold);
      $worksheet->write($excelrivi,  7, t("Eräpäivä"), $format_bold);
      $worksheet->write($excelrivi,  8, t("Laskun summa"), $format_bold);
      $worksheet->write($excelrivi,  9, t("Avoin saldo"), $format_bold);
      $worksheet->write($excelrivi, 10, t("Val"), $format_bold);

      if (!empty($karhutieto)) {
        $worksheet->write($excelrivi, 11, t("Maksukehotuksia"), $format_bold);
        $worksheet->write($excelrivi, 12, t("Viimeisin maksukehotus"), $format_bold);
      }

      $excelrivi++;
    }

    $summa = array(); // tässä kirjanpidosta osasuoritukset poistettuna
    $summatotal = 0;  // ässä koko höskä kotivaluutassa

    while ($trow = mysql_fetch_assoc($result)) {

      $karhutieto_array = array();

      if ($trow['tiliointiavoinsaldo'] != 0) {

        echo "<tr class='aktiivi'>";
        echo "<td><a name='$trow[tunnus]' href='".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php?ytunnus=".tarkistahetu($trow["ytunnus"])."&asiakasid=$trow[liitostunnus]&alatila=Y&tila=tee_raportti&lopetus=$PHP_SELF////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=".tarkistahetu($ytunnus)."//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///$trow[tunnus]'>".tarkistahetu($trow["ytunnus"])."</a></td>";
        echo "<td>$trow[asiakasnro]</td>";
        echo "<td>$trow[nimi]</td>";
        echo "<td>$trow[maa]</td>";
        echo "<td>$trow[maksutieto]</td>";

        if ($trow["tapvm"] == "0000-00-00" or $trow["tapvm"] == "0000-00-00 0000-00-00") {
          $trow["tapvm"] = "";
          echo "<td>$trow[tapvm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['tapvm']).tv1dateconv($trow["tapvm"])."</td>";
        }
        if ($trow["mapvm"] == "0000-00-00" or $trow["mapvm"] == "0000-00-00 0000-00-00") {
          $trow["mapvm"] = "";
          echo "<td>$trow[mapvm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['mapvm']).tv1dateconv($trow["mapvm"])."</td>";
        }
        if ($trow["erpcm"] == "0000-00-00" or $trow["erpcm"] == "0000-00-00 0000-00-00") {
          $trow["erpcm"] = "";
          echo "<td>$trow[erpcm]</td>";
        }
        else {
          echo "<td>".pupe_DataTablesEchoSort($trow['erpcm']).tv1dateconv($trow["erpcm"])."</td>";
        }

        $summatotal += $trow['tiliointiavoinsaldo'];

        if ($valuutassako == 'V') {
          echo "<td align='right'>$trow[tiliointisumma_valuutassa]</td>";
          echo "<td align='right' class='avoin'>$trow[tiliointiavoinsaldo_valuutassa]</td>";
          echo "<td align='right'>$trow[valkoodi]</td>";

          if (!isset($summa[$trow["valkoodi"]])) $summa[$trow["valkoodi"]] = $trow["tiliointiavoinsaldo_valuutassa"];  // paljo kirjanpidossa avoimena valuutassa
          else $summa[$trow["valkoodi"]] += $trow["tiliointiavoinsaldo_valuutassa"];  // paljo kirjanpidossa avoimena valuutassa
        }
        else {
          echo "<td align='right'>$trow[tiliointisumma]</td>";
          echo "<td align='right' class='avoin'>$trow[tiliointiavoinsaldo]</td>";
          echo "<td align='right'>$yhtiorow[valkoodi]</td>";

          if (!isset($summa[$yhtiorow["valkoodi"]])) $summa[$yhtiorow["valkoodi"]] = $trow['tiliointiavoinsaldo'];  // paljo kirjanpidossa avoimena
          else $summa[$yhtiorow["valkoodi"]] += $trow['tiliointiavoinsaldo'];  // paljo kirjanpidossa avoimena
        }

        if (!empty($karhutieto) and $trow['tunnukset'] != '') {

          $query = "SELECT max(karhukierros.pvm) as karhupvm,
                    count(karhu_lasku.ltunnus) as karhukerrat
                    FROM lasku
                    JOIN karhu_lasku ON (lasku.tunnus = karhu_lasku.ltunnus)
                    JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = '')
                    WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
                    AND lasku.tila    = 'U'
                    AND lasku.alatila = 'X'
                    AND lasku.mapvm   = '0000-00-00'
                    AND lasku.tunnus IN ({$trow['tunnukset']})";
          $karhures = pupe_query($query);
          $karhurow = mysql_fetch_assoc($karhures);

          echo "<td>{$karhurow['karhukerrat']}</td>";
          echo "<td>".pupe_DataTablesEchoSort($karhurow['karhupvm']).tv1dateconv($karhurow['karhupvm'])."</td>";

          $karhutieto_array = $karhurow;
        }

        // jos ollaan ajettu tälle päivälle laskuittain voidaan echota errori avoimesta saldosta
        if ($asitoimittain == "" and $onkotanaan == "" and abs($trow["tiliointiavoinsaldo"] - $trow["laskuavoinsaldo"]) >= 0.05) {
          echo "<td class='back' nowrap><font class='error'>VIRHE: Lasku / Kirjanpito heittää!</font> $trow[laskuavoinsaldo] / <a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>$trow[tiliointiavoinsaldo]</a></td>";
        }
        else {
          echo "<td class='back'></td>";
        }

        echo "</tr>";

        if (isset($worksheet)) {
          $worksheet->writeString($excelrivi, 0, tarkistahetu($trow["ytunnus"]));
          $worksheet->writeString($excelrivi, 1, $trow["asiakasnro"]);
          $worksheet->writeString($excelrivi, 2, $trow["nimi"]);
          $worksheet->writeString($excelrivi, 3, $trow["maa"]);
          $worksheet->write($excelrivi, 4, $trow["maksutieto"]);
          $worksheet->write($excelrivi, 5, $trow["tapvm"]);

          if ($trow["mapvm"] != '0000-00-00') $worksheet->write($excelrivi, 6, $trow["mapvm"]);
          else $worksheet->write($excelrivi, 6, "");

          $worksheet->write($excelrivi, 7, $trow["erpcm"]);

          if ($valuutassako == "V") {
            $worksheet->writeNumber($excelrivi, 8, (float) $trow["tiliointisumma_valuutassa"]);
            $worksheet->writeNumber($excelrivi, 9, (float) $trow["tiliointiavoinsaldo_valuutassa"]);
            $worksheet->write($excelrivi, 10, $trow["valkoodi"]);
          }
          else {
            $worksheet->writeNumber($excelrivi, 8, (float) $trow["tiliointisumma"]);
            $worksheet->writeNumber($excelrivi, 9, (float) $trow["tiliointiavoinsaldo"]);
            $worksheet->write($excelrivi, 10, $yhtiorow["valkoodi"]);
          }

          if (!empty($karhutieto) and !empty($karhutieto_array)) {
            $worksheet->writeNumber($excelrivi, 11, (int) $karhutieto_array["karhukerrat"]);
            $worksheet->write($excelrivi, 12, $karhutieto_array["karhupvm"]);
          }

          $excelrivi++;
        }
      }
    }

    $suoritussumma = array();
    $suoritussummatotal = 0; // tässä koko höskä kotivaluutassa

    // Ei listata kohdistamattomia mikäli ollaan haettu karhuttuja laskuja
    if (trim($karhuja) == '' or $karhuja == 0) {
      //Listataan vielä kohdistamattomat
      $query = "SELECT asiakas.tunnus,
                asiakas.ytunnus,
                if(asiakas.asiakasnro = 0 or asiakas.asiakasnro = asiakas.ytunnus, '', asiakas.asiakasnro) asiakasnro,
                if(asiakas.nimi is not null, asiakas.nimi, suoritus.nimi_maksaja) nimi_maksaja,
                asiakas.maa,
                suoritus.viite,
                suoritus.kirjpvm,
                tiliointi.summa tiliointisumma,
                tiliointi.summa_valuutassa tiliointisumma_valuutassa,
                round(suoritus.summa * if(suoritus.kurssi = 0, 1, suoritus.kurssi), 2) * -1 suoritussumma,
                tiliointi.ltunnus,
                suoritus.summa * -1 summa,
                suoritus.valkoodi
                  FROM suoritus
                JOIN tiliointi ON (suoritus.yhtio = tiliointi.yhtio and tiliointi.tunnus = suoritus.ltunnus and tiliointi.korjattu = '' and tiliointi.tilino in ($tili) {$tiliointilisa})
                  LEFT JOIN asiakas ON (suoritus.yhtio = asiakas.yhtio and suoritus.asiakas_tunnus = asiakas.tunnus $av_lisa3)
                  WHERE suoritus.yhtio = '$kukarow[yhtio]'
                and (suoritus.kohdpvm = '0000-00-00' or suoritus.kohdpvm > '$pvm')
                and suoritus.ltunnus   > 0
                and suoritus.kirjpvm   <= '$pvm'
                $av_lisa2
                $valkoodi2
                $maalisa2
                  ORDER BY asiakas.ytunnus";
      $result = pupe_query($query);

      while ($trow = mysql_fetch_assoc($result)) {
        echo "<tr class='aktiivi'>";

        $suoritussummatotal += $trow["suoritussumma"];

        if ($trow["tunnus"] != NULL) {
          echo "<td><a name='a_$trow[tunnus]' href='".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php?ytunnus=".tarkistahetu($trow["ytunnus"])."&asiakasid=$trow[tunnus]&alatila=Y&tila=tee_raportti&lopetus=$PHP_SELF////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=".tarkistahetu($ytunnus)."//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///a_$trow[tunnus]'>".tarkistahetu($trow["ytunnus"])."</a></td>";
          echo "<td>$trow[asiakasnro]</td>";
        }
        else {
          echo "<td>*".t("ei asiakasta")."*</td>";
          echo "<td></td>";
        }
        echo "<td>$trow[nimi_maksaja]</td>";
        echo "<td>$trow[maa]</td>";
        echo "<td>$trow[viite]</td>";
        echo "<td>".pupe_DataTablesEchoSort($trow['kirjpvm']).tv1dateconv($trow["kirjpvm"])."</td>";
        echo "<td></td>";
        echo "<td></td>";

        $trow["summa"] = sprintf("%01.2f", $trow["summa"]);
        $trow["suoritussumma"] = sprintf("%01.2f", $trow["suoritussumma"]);

        if ($valuutassako == 'V') {
          echo "<td></td>";
          echo "<td align='right'>$trow[summa]</td>";
          echo "<td align='right'>$trow[valkoodi]</td>";

          $suoritussumma[$trow["valkoodi"]] += $trow["summa"];
        }
        else {
          echo "<td></td>";
          echo "<td align='right'>$trow[suoritussumma]</td>";
          echo "<td align='right'>$yhtiorow[valkoodi]</td>";

          $suoritussumma[$yhtiorow["valkoodi"]] += $trow["suoritussumma"];
        }

        if (!empty($karhutieto)) {
          echo "<td></td>";
          echo "<td></td>";
        }

        echo "<td class='back' nowrap>";

        if ($trow["suoritussumma"] != $trow["tiliointisumma"] and $trow["summa"] != $trow["tiliointisumma_valuutassa"] and $trow["tiliointisumma"] != NULL and $trow["tiliointisumma_valuutassa"] != NULL) {
          echo "<font class='error'>VIRHE: Kirjanpito / Suoritus summat heittävät!</font> (<a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tiliointisumma] / $trow[suoritussumma]</a>)";
        }

        if ($trow["tiliointisumma"] == NULL) {
          echo "<br><font class='error'>VIRHE: Suoritukselle ei löydy tiliöintejä!</font>";
        }

        echo "</td>";
        echo "</tr>";

        if (isset($worksheet)) {
          if ($trow["tunnus"] != NULL) $worksheet->write($excelrivi, 0, tarkistahetu($trow["ytunnus"]));
          else $worksheet->write($excelrivi, 0, "*".t("ei asiakasta")."*");
          $worksheet->write($excelrivi, 1, $trow["asiakasnro"]);
          $worksheet->write($excelrivi, 2, $trow["nimi_maksaja"]);
          $worksheet->write($excelrivi, 3, $trow["maa"]);
          $worksheet->write($excelrivi, 4, $trow["viite"]);
          $worksheet->write($excelrivi, 5, $trow["kirjpvm"]);
          $worksheet->write($excelrivi, 6, "");
          $worksheet->write($excelrivi, 7, "");

          if ($valuutassako == "V") {
            $worksheet->write($excelrivi, 8, "");
            $worksheet->writeNumber($excelrivi, 9, (float) $trow["summa"]);
            $worksheet->write($excelrivi, 10, $trow["valkoodi"]);
          }
          else {
            $worksheet->write($excelrivi, 8, "");
            $worksheet->writeNumber($excelrivi, 9, (float) $trow["suoritussumma"]);
            $worksheet->write($excelrivi, 10, $yhtiorow["valkoodi"]);
          }

          $excelrivi++;
        }
      }
    }
    $kaikkival = array();

    echo "</tbody>";
    echo "<tfoot>";

    foreach ($summa as $val => $sum) {
      echo "<tr>";
      echo "<th colspan='9'>".t("Avoimet yhteensä").":</th>";
      echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_yhteensa_$val'>".sprintf("%.2f", $sum)."</td>";        // tässä kirjanpidosta osasuoritukset poistettuna
      echo "<td class='tumma' align='right' nowrap>$val</td>";

      if (!empty($karhutieto)) {
        echo "<th colspan='2'></th>";
      }

      echo "</tr>";

      if (!in_array($val, $kaikkival)) $kaikkival[] = $val;
    }

    foreach ($suoritussumma as $val => $sum) {
      echo "<tr>";
      echo "<th colspan='9'>".t("Kohdistamattomat suoritukset yhteensä").":</th>";
      echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_suoriyhteensa_$val'>".sprintf("%.2f", $sum)."</td>";
      echo "<td class='tumma' align='right' nowrap>$val</td>";

      if (!empty($karhutieto)) {
        echo "<th colspan='2'></th>";
      }

      echo "</tr>";

      if (!in_array($val, $kaikkival)) $kaikkival[] = $val;
    }

    foreach ($kaikkival as $val) {
      echo "<tr>";
      echo "<th colspan='9'>".t("Erotus avoimet - kohdistamattomat").":</th>";
      echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_yseroyhteensa_$val'>".sprintf("%.2f", ($summa[$val] + $suoritussumma[$val]))."</td>"; // tässä kirjanpidosta osasuoritukset poistettuna
      echo "<td class='tumma' align='right' nowrap>$val</td>";

      if (!empty($karhutieto)) {
        echo "<th colspan='2'></th>";
      }

      echo "</tr>";
    }

    // ei rajauksia, voidaan näyttää erotus kirjanpitoon
    if ($av_lisa1 == "" and $av_lisa3 == "" and $valkoodi == "" and $maa == "" and $karhuja == 0
      and $toimipaikkalisa == "") {

      $query = "SELECT sum(summa) saldo
                FROM tiliointi
                WHERE yhtio  = '$kukarow[yhtio]'
                and korjattu = ''
                and tapvm    >= '$tilikausirow[tilikausi_alku]'
                and tapvm    <= '$pvm'
                and tilino   in ($tili)
                {$tiliointilisa}";
      $result = pupe_query($query);
      $trow = mysql_fetch_assoc($result);

      $trow["saldo"]   = sprintf("%.2f", $trow["saldo"]);
      $erotus     = $summatotal + $suoritussummatotal - $trow["saldo"];
      $erotus     = sprintf("%.2f", $erotus);

      $colspan = empty($karhutieto) ? 11 : 13;

      echo "<tr><td class='back' colspan='{$colspan}'><br></td></tr>";

      echo "<tr>";
      echo "<th colspan='9'>".t("Erotus avoimet - kohdistamattomat", "", $tili).":</th>";
      echo "<td class='tumma' align='right' nowrap>".sprintf("%.2f", $summatotal + $suoritussummatotal)."</td>";
      echo "<td class='tumma' align='right' nowrap>$yhtiorow[valkoodi]</td>";

      if (!empty($karhutieto)) {
        echo "<th colspan='2'></th>";
      }

      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='9'>".t("Kirjanpidon tili %s yhteensä", "", $tili).":</th>";
      echo "<td class='tumma' align='right' nowrap>".sprintf("%.2f", $trow["saldo"])."</td>";
      echo "<td class='tumma' align='right' nowrap>$yhtiorow[valkoodi]</td>";

      if (!empty($karhutieto)) {
        echo "<th colspan='2'></th>";
      }

      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='9'>".t("Erotus avoimet - kohdistamattomat - kirjanpito").":</th>";
      echo "<td class='tumma' align='right' nowrap>".sprintf("%.2f", $erotus)."</td>";
      echo "<td class='tumma' align='right' nowrap>$yhtiorow[valkoodi]</td>";

      if (!empty($karhutieto)) {
        echo "<th colspan='2'></th>";
      }

      echo "</tr>";
    }

    echo "</tfoot>";
  }

  echo "</table>";
}
