<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 2;

require '../inc/parametrit.inc';

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if (!isset($nayta)) $nayta = '';
if (!isset($tee)) $tee = '';
if (!isset($rivityyppi)) $rivityyppi = '';
if (!isset($varasto)) $varasto = 0;

echo "<font class='head'>", t("Keräyspoikkeamat"), ":</font><hr>";

if ($tee != '') {

  if ($rivien_aika == 'laskutettuaika') {
    $aikalisa = " and tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}'
               and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}'";
  }
  else {
    $aikalisa = " and tilausrivi.kerattyaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
               and tilausrivi.kerattyaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'";
  }

  if ($rivityyppi == 'M') {
    if ($rivien_aika == 'laskutettuaika') {
      $rivityyppilisa = " and tilausrivi.tyyppi = 'L' ";
    }
    else {
      $rivityyppilisa = " and tilausrivi.tyyppi IN ('L','D') ";
    }
  }
  elseif ($rivityyppi == 'S') {
    if ($rivien_aika == 'laskutettuaika') {
      $rivityyppilisa = " and tilausrivi.tyyppi = 'G' ";
    }
    else {
      $rivityyppilisa = " and tilausrivi.tyyppi IN ('G','D') ";
    }
  }
  else {
    if ($rivien_aika == 'laskutettuaika') {
      $rivityyppilisa = " and tilausrivi.tyyppi IN ('L','G') ";
    }
    else {
      $rivityyppilisa = " and tilausrivi.tyyppi IN ('L','D','G') ";
    }
  }

  if (!empty($varasto)) {
    $varasto     = (int) $varasto;
    $varastolisa = "and tilausrivi.varasto = {$varasto}";
  }
  else {
    $varastolisa = "";
  }

  $query = "SELECT lasku.nimi asiakas,
            tilausrivi.tuoteno,
            tilausrivi.nimitys,
            tilausrivi.tilkpl,
            tilausrivi.kpl,
            tilausrivi.keratty,
            concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) tuotepaikka,
            tilausrivi.nimitys,
            tilausrivi.yksikko,
            tilausrivi.hyllyalue,
            tilausrivi.hyllynro,
            tilausrivi.hyllytaso,
            tilausrivi.hyllyvali,
            tilausrivi.tyyppi,
            concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
            FROM tilausrivi
            JOIN lasku ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            {$aikalisa}
            {$varastolisa}
            and tilausrivi.var     not in ('P','J','O','S')
            and tilausrivi.tilkpl  <> tilausrivi.kpl
            ORDER BY sorttauskentta, tuoteno";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0 ) {
    echo "<table><tr>";
    echo "<th>",t("Varastopaikka"),"</th>";
    echo "<th>",t("Tuoteno"),"</th>";
    echo "<th>",t("Nimitys"),"</th>";
    echo "<th>",t("Asiakas"),"</th>";

    if (empty($rivityyppi)) {
      echo "<th>",t("Rivityyppi"),"</th>";
    }

    echo "<th>",t("Tilattu"),"</th>";
    echo "<th>",t("Toimitettu"),"</th>";
    echo "<th>",t("Tilauksessa"),"</th>";
    echo "<th>",t("Ensimmäinen toimitus"),"</th>";
    echo "<th>",t("Hyllyssä"),"</th>";
    echo "<th>",t("Saldo"),"</th>";
    echo "<th>",t("Kerääjä"),"</th>";
    echo "</tr>";

    while ($row = mysql_fetch_array($result)) {

      if ($nayta == 'ei_ylijaamia' and $row['tilkpl'] < $row['kpl']) continue;

      if (!empty($varasto)) {
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $row["hyllyalue"], $row["hyllynro"], $row["hyllyvali"], $row["hyllytaso"]);
      }
      else {
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);
      }

      //saldolaskentaa tulevaisuuteen
      $query = "SELECT sum(varattu) varattu,
                min(toimaika) toimaika
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$row[tuoteno]'
                and tyyppi='O'
                and varattu > 0";
      $result2 = pupe_query($query);
      $prow = mysql_fetch_array($result2);

      echo "<tr>";
      echo "<td>{$row['tuotepaikka']}</td>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>",t_tuotteen_avainsanat($row, 'nimitys'),"</td>";
      echo "<td>{$row['asiakas']}</td>";

      if (empty($rivityyppi)) {
        echo "<td>";

        if ($row['tyyppi'] == 'G') {
          echo t("Siirto");
        }
        else {
          echo t("Myynti");
        }

        echo "</td>";
      }

      echo "<td align='right'>{$row['tilkpl']}</td>";
      echo "<td align='right'>{$row['kpl']}</td>";
      echo "<td align='right'>{$prow['varattu']}</td>";
      echo "<td>{$prow['toimaika']}</td>";
      echo "<td align='right'>{$hyllyssa}</td>";
      echo "<td align='right'>{$saldo}</td>";
      echo "<td>{$row['keratty']}</td>";
      echo "</tr>";
    }

    echo "</table>";

  }
  else {
    echo "<br>".t("Ei tuotteita väärillä saldoilla")."!<br>";
  }
}

//Käyttöliittymä
echo "<br>";
echo "<table><form method='post'>";
echo "<input type='hidden' name='tee' value='kaikki'>";

echo "<tr>";
echo "<th>", t("Näytä"), "</th>";
echo "<td><select name='nayta'>";
echo "<option value=''>", t("Kaikki poikkeamat"), "</option>";

$sel = $nayta == 'ei_ylijaamia' ? ' selected' : '';

echo "<option value='ei_ylijaamia'{$sel}>", t("Kaikki paitsi rivit jossa kerääjä on kerännyt tilattua enemmän"), "</option>";
echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Hae rivit ajan mukaan"), "</th>";
echo "<td><select name='rivien_aika'>";
echo "<option value=''>", t("Kerättyaika"), "</option>";

$sel = $rivien_aika == 'laskutettuaika' ? ' selected' : '';

echo "<option value='laskutettuaika'{$sel}>", t("Laskutettuaika"), "</option>";
echo "</select></td>";
echo "</tr>";

echo "<tr><th>", t("Rajaa varastolla"), "</th>";

echo "<td><select name='varasto'>";

$sel = "";

$query = "SELECT *
          FROM varastopaikat
          WHERE yhtio  = '{$kukarow['yhtio']}'
          AND tyyppi  != 'P'
          ORDER BY nimitys, tyyppi";
$varastopaikat_result = pupe_query($query);

echo "<option value='0'>", t("Kaikki varastot"), "</option>";

while ($_varasto = mysql_fetch_assoc($varastopaikat_result)) {

  if (!empty($varasto) and $varasto == $_varasto['tunnus']) {
    $sel = "selected";
  }
  elseif ($sel == "" and $kukarow['oletus_varasto'] == $_varasto['tunnus']) {
    $sel = "selected";
  }
  else {
    $sel = "";
  }

  echo "<option value='{$_varasto['tunnus']}' {$sel}>{$_varasto['nimitys']}</option>";
}

echo "</select></td></tr>";

$sel = array($rivityyppi => 'selected') + array('M' => '', 'S' => '');

echo "<tr>";
echo "<th>",t("Rajaa rivityypillä"),"</th>";
echo "<td>";
echo "<select name='rivityyppi'>";
echo "<option value=''>",t("Myynti- ja siirtorivit"),"</option>";
echo "<option value='M' {$sel['M']}>",t("Myyntirivit"),"</option>";
echo "<option value='S' {$sel['S']}>",t("Siirtorivit"),"</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr><th>", t("Syötä alkupäivämäärä (pp-kk-vvvv)"), "</th>
    <td><input type='text' name='ppa' value='{$ppa}' size='3'>
    <input type='text' name='kka' value='{$kka}' size='3'>
    <input type='text' name='vva' value='{$vva}' size='5'></td>
    </tr><tr><th>", t("Syötä loppupäivämäärä (pp-kk-vvvv)"), "</th>
    <td><input type='text' name='ppl' value='{$ppl}' size='3'>
    <input type='text' name='kkl' value='{$kkl}' size='3'>
    <input type='text' name='vvl' value='{$vvl}' size='5'>";
echo "<td class='back'><input type='submit' value='", t("Aja raportti"), "'></td></tr></table>";

require "inc/footer.inc";
