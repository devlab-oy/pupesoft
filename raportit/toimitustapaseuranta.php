<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

require('../inc/parametrit.inc');

echo "<font class='head'>",t("Toimitustapaseuranta"),"</font><hr>";

echo "<br /><table><form method='post'>";

// ehdotetaan 7 p�iv�� taaksep�in
if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if (!isset($tee)) $tee = "";

$sel = array_fill_keys(array($tee), ' selected') + array_fill_keys(array('kaikki', 'paivittain'), '');

echo "<input type='hidden' name='tee' value='kaikki'>";
echo "<tr><th>",t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)"),"</th>
    <td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
    <td><input type='text' name='kka' value='{$kka}' size='3'></td>
    <td><input type='text' name='vva' value='{$vva}' size='5'></td>
    </tr><tr><th>",t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)"),"</th>
    <td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
    <td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
    <td><input type='text' name='vvl' value='{$vvl}' size='5'></td><td class='back'></td></tr>";
echo "<tr><th>",t("Valitse seurantatapa"),"</th>";
echo "<td colspan='3'><select name='tee'>";
echo "<option value='kaikki'{$sel['kaikki']}>",t("N�yt� summattuna"),"</option>";
echo "<option value='paivittain'{$sel['paivittain']}>",t("N�yt� p�ivitt�in"),"</option>";
echo "</td></select>";
echo "<td class='back'><input type='submit' value='",t("Aja raportti"),"'></td></tr></table>";

echo "<br />";

if ($tee != '') {
  echo "<table>";

  $lisa = $orderbylisa = $groupbylisa = "";

  if ($tee == 'paivittain') {
    $lisa = ", LEFT(tilausrivi.toimitettuaika, 10) toimitettuaika";
    $groupbylisa = ",2";
    $orderbylisa = "toimitettuaika, ";
  }

  $query = "  SELECT lasku.toimitustapa
        {$lisa},
        left(SEC_TO_TIME(AVG(TIME_TO_SEC(DATE_FORMAT(toimitettuaika,'%H:%i:%s')))),5) aika,
        COUNT(DISTINCT lasku.tunnus) kpl,
        SUM(tilausrivi.rivihinta) summa,
        COUNT(DISTINCT IF(lasku.kerayslista = 0, lasku.tunnus, lasku.kerayslista)) kpl_kerayslista,
        COUNT(DISTINCT tilausrivi.tunnus) tilausriveja,
        ROUND(SUM(tilausrivi.kpl)) kpl_tilriv
        FROM tilausrivi
        JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
        WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
        AND tilausrivi.tyyppi = 'L'
        AND tilausrivi.toimitettuaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
        AND tilausrivi.toimitettuaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
        GROUP BY 1 {$groupbylisa}
        ORDER BY {$orderbylisa} aika, kpl desc, toimitustapa";
  $result = pupe_query($query);

  $otsikot =  "  <tr>
          <th>".t("Toimitustapa")."</th>
          <th>".t("Toimitusaika")."</th>";

  if ($tee == 'kaikki') {
    $otsikot .= "<th>".t("Tilauksia")."</th>";
    $otsikot .= "<th>".t("Tilauksia/P�iv�")."</th>";
  }
  else {
    $otsikot .= "<th>".t("Ker�yslistoja")."</th>";
    $otsikot .= "<th>".t("Tilauksia")."</th>";
    $otsikot .= "<th>".t("Tilausrivej�")."</th>";
    $otsikot .= "<th>".t("M��r�")."</th>";
  }

  $otsikot .= "<th>".t("Myynti")."</th></tr>";

  if ($tee == 'kaikki') echo $otsikot;

  //p�ivi� aikajaksossa
  $epa1 = (int) date('U',mktime(0,0,0,$kka,$ppa,$vva));
  $epa2 = (int) date('U',mktime(0,0,0,$kkl,$ppl,$vvl));

  //Diff in workdays (5 day week)
  $pva = abs($epa2-$epa1)/60/60/24/7*5;

  $paivamaara = "";

  $tilauksia_kaikki     = 0;
  $tilauksia         = 0;
  $kerayslistoja_kaikki = 0;
  $kerayslistoja       = 0;
  $tilausriveja_kaikki  = 0;
  $tilausriveja       = 0;
  $maara_kaikki       = 0;
  $maara           = 0;
  $kplperpva_kaikki     = 0;
  $myynti_kaikki       = 0;
  $myynti         = 0;

  while ($row = mysql_fetch_assoc($result)) {

    if ($tee == 'kaikki') $row['toimitettuaika'] = "";

    if ($tee == 'paivittain' and ($paivamaara == "" or $paivamaara != $row['toimitettuaika'])) {

      if ($paivamaara != "") {
        echo "<tr>
            <td class='spec' colspan='2'>".t("Yhteens�").":</td>
            <td class='spec'>{$kerayslistoja}</td>
            <td class='spec'>{$tilauksia}</td>
            <td class='spec'>{$tilausriveja}</td>
            <td class='spec'>{$maara}</td>
            <td class='spec' align='right'>{$myynti}</td></tr>";
        echo "<tr><td class='back' colspan='7'>&nbsp;</td></tr>";
      }

      $tilauksia      = 0;
      $kerayslistoja = 0;
      $tilausriveja  = 0;
      $maara        = 0;
      $myynti       = 0;

      echo "<tr><th colspan='7'>",tv1dateconv($row['toimitettuaika']),"</th></tr>";
      echo $otsikot;
    }

    echo "<tr class='aktiivi'>";
    echo "<td>$row[toimitustapa]</td>";
    echo "<td>$row[aika]</td>";

    if ($tee == 'paivittain') {
      echo "<td>{$row['kpl_kerayslista']}</td>";
      $kerayslistoja       += $row['kpl_kerayslista'];
      $kerayslistoja_kaikki += $row['kpl_kerayslista'];
      $tilausriveja       += $row['tilausriveja'];
      $tilausriveja_kaikki  += $row['tilausriveja'];
      $maara           += $row['kpl_tilriv'];
      $maara_kaikki       += $row['kpl_tilriv'];
      $myynti         += $row['summa'];
      $myynti_kaikki      += $row['summa'];
    }

    echo "<td>$row[kpl]</td>";

    if ($tee == 'kaikki') {
      $kplperpva = round($row["kpl"]/$pva,0);
      echo "<td>$kplperpva</td>";
      $kplperpva_kaikki += $kplperpva;
    }
    else {
      echo "<td>{$row['tilausriveja']}</td>";
      echo "<td>{$row['kpl_tilriv']}</td>";
    }

    echo "<td align='right'>",hintapyoristys($row['summa']),"</td>";
    echo "</tr>";

    $paivamaara = $row['toimitettuaika'];

    $tilauksia += $row['kpl'];
    $tilauksia_kaikki += $row['kpl'];
  }

  if ($tee == 'paivittain') {
    echo "<tr>
        <td class='spec' colspan='2'>".t("Yhteens�").":</td>
        <td class='spec'>{$kerayslistoja}</td>
        <td class='spec'>{$tilauksia}</td>
        <td class='spec'>{$tilausriveja}</td>
        <td class='spec'>{$maara}</td>
        <td class='spec' align='right'>{$myynti}</td></tr>";
    echo "<tr><td class='back' colspan='7'>&nbsp;</td></tr>";

    echo "<tr>
        <td class='spec' colspan='2'>",t("Kaikki yhteens�"),"</td>
        <td class='spec'>{$kerayslistoja_kaikki}</td>
        <td class='spec'>{$tilauksia_kaikki}</td>
        <td class='spec'>{$tilausriveja_kaikki}</td>
        <td class='spec'>{$maara_kaikki}</td>
        <td class='spec'>{$myynti_kaikki}</td></tr>";
  }
  else {
    echo "<tr>
        <td class='spec' colspan='2'>",t("Kaikki yhteens�"),"</td>
        <td class='spec'>{$tilauksia_kaikki}</td>
        <td class='spec'>{$kplperpva_kaikki}</td>
        <td class='spec'>{$myynti_kaikki}</td></tr>";
  }


  echo "</table>";
}

require ("inc/footer.inc");
