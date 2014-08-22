<?php

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
}

require('../inc/parametrit.inc');

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

function count_workdays($date1,$date2){ 
  $firstdate = strtotime($date1); 
  $lastdate = strtotime($date2); 
  $firstday = date(w,$firstdate); 
  $lastday = date(w,$lastdate); 
  $totaldays = intval(($lastdate-$firstdate)/86400)+1; 

  //check for one week only 
  if ($totaldays<=7 && $firstday<=$lastday){ 
    $workdays = $lastday-$firstday+1; 
    //check for weekend 
    if ($firstday==0){ 
      $workdays = $workdays-1; 
    } 
    if ($lastday==6){ 
      $workdays = $workdays-1; 
    } 
      
  }
  else { 
  //more than one week

    //workdays of first week 
    if ($firstday==0){ 
      //so we don't count weekend 
      $firstweek = 5;  
    }
    else { 
      $firstweek = 6-$firstday; 
    } 
    $totalfw = 7-$firstday; 
    
    //workdays of last week 
    if ($lastday==6){ 
      //so we don't count sat, sun=0 so it won't be counted anyway 
      $lastweek = 5; 
    }
    else { 
      $lastweek = $lastday; 
    } 
    $totallw = $lastday+1; 
        
    //check for any mid-weeks  
    if (($totalfw+$totallw)>=$totaldays){ 
      $midweeks = 0; 
    } 
    else { //count midweeks 
      $midweeks = (($totaldays-$totalfw-$totallw)/7)*5; 
    } 
    
    //total num of workdays 
    $workdays = $firstweek+$midweeks+$lastweek; 
  }

  return $workdays;
}

echo "<font class='head'>".t("Työmääräysraportti").":</font><hr>";

if ($raptee == "AJA") {

  include('inc/pupeExcel.inc');

  $worksheet = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi = 0;
  $i = 0;

  $valmistajalisa = !empty($valmistajarajaus) ? " and tm.merkki like '%{$valmistajarajaus}%' " : "" ;
  $aloituspaiva = "{$aloitusvv}-{$aloituskk}-{$aloituspp}";
  $lopetuspaiva = "{$lopetusvv}-{$lopetuskk}-{$lopetuspp}";

  $query = "SELECT
            concat(lasku.toim_nimi,'<br>',
            lasku.toim_osoite,'<br>',
            lasku.toim_postitp) asiakastiedot,
            tm.merkki valmistaja,
            tt1.tyomaarays_tunnus,
            min(tt1.luontiaika) alkupvm,
            a1.selitetark alku_nimitys,
            max(tt2.luontiaika) loppupvm,
            a2.selitetark loppu_nimitys
            FROM tyomaarayksen_tapahtumat tt1
            JOIN tyomaarays tm ON (tm.yhtio = tt1.yhtio
              AND tm.otunnus = tt1.tyomaarays_tunnus 
              {$valmistajalisa})
            LEFT JOIN avainsana a1 ON (a1.yhtio = tt1.yhtio
              AND a1.laji = 'tyom_tyostatus' 
              AND a1.selite = tt1.tyostatus_selite)
            LEFT JOIN tyomaarayksen_tapahtumat tt2 ON (tt2.yhtio = tt1.yhtio 
              AND tt2.tyomaarays_tunnus = tt1.tyomaarays_tunnus)
            LEFT JOIN avainsana a2 ON (a2.yhtio = tt1.yhtio
              AND a2.laji = 'tyom_tyostatus'
              AND a2.selite = tt2.tyostatus_selite)
            LEFT JOIN lasku ON (lasku.yhtio = tm.yhtio 
              AND lasku.tunnus = tm.otunnus)
            WHERE tt1.yhtio = '{$kukarow['yhtio']}'
            AND tt1.tyostatus_selite = '{$aloitustila}'
            AND tt2.tyostatus_selite = '{$lopetustila}'
            GROUP BY tt1.tyomaarays_tunnus
            HAVING alkupvm >= '{$aloituspaiva}'
            AND loppupvm <= '{$lopetuspaiva}'";
  $result = pupe_query($query);

  $worksheet->write($excelrivi, $i, t('Työmääräysnumero'), $format_bold);
  $i++;
  $worksheet->write($excelrivi, $i, t('Asiakastiedot'), $format_bold);
  $i++;
  if (!empty($valmistajarajaus)) {
    $worksheet->write($excelrivi, $i, t('Tuotemerkki'), $format_bold);
    $i++;
  }
  $worksheet->write($excelrivi, $i, t('Aloitustapahtuma'), $format_bold);
  $i++;
  $worksheet->write($excelrivi, $i, t('Aloitusaika'), $format_bold);
  $i++;
  $worksheet->write($excelrivi, $i, t('Lopetustapahtuma'), $format_bold);
  $i++;
  $worksheet->write($excelrivi, $i, t('Lopetusaika'), $format_bold);
  $i++;
  $worksheet->write($excelrivi, $i, t('Kesto'), $format_bold);



  $i=0;
  $excelrivi++;

  while ($rivi = mysql_fetch_array($result)) {
    $worksheet->writeString($excelrivi, $i, $rivi['tyomaarays_tunnus']);
    $i++;
    $worksheet->writeString($excelrivi, $i, $rivi['asiakastiedot']);
    $i++;
    if (!empty($valmistajarajaus)) {
      $worksheet->writeString($excelrivi, $i, $rivi['valmistaja']);
      $i++;
    }
    $worksheet->writeString($excelrivi, $i, $rivi['alku_nimitys']);
    $i++;
    $worksheet->writeString($excelrivi, $i, $rivi['alkupvm']);
    $i++;
    $worksheet->writeString($excelrivi, $i, $rivi['loppu_nimitys']);
    $i++;
    $worksheet->writeString($excelrivi, $i, $rivi['loppupvm']);
    $i++;
    
    // Lasketaan business days ensimmäisen ja viimeisen tapahtuman välillä
    $businessdays = count_workdays($rivi['alkupvm'],$rivi['loppupvm']);
    $worksheet->writeString($excelrivi, $i, $businessdays);
    $i=0;
    $excelrivi++;
  }

  $excelnimi = $worksheet->close();

  echo "<br><br>";
  echo "<font class='message'>".t("Tallenna excel").": </font>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='".t("tyomaaraysraportti").".xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<input type='submit' value='".t("Tallenna")."'>";
  echo "</form>";
  echo "<br><br>";
}

echo "<br><form method='post'>";
echo "<input type='hidden' name='raptee' value='AJA'>";

$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");

echo "<table>";
echo "<tr>
  <th>".t("Aloituspäivä (pp-kk-vvvv)")."</th>
  <td>
  <input type='text' size='2' maxlength='2' name='aloituspp' value='$aloituspp'>
  <input type='text' size='2' maxlength='2' name='aloituskk' value='$aloituskk'>
  <input type='text' size='4' maxlength='4' name='aloitusvv' value='$aloitusvv'>
  </td>
  <td><select name='aloitustila'>";
  echo "<option value=''>Ei valintaa</option>";
  while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
    $sel = $aloitustila == $tyostatus_row['selite'] ? " SELECTED " : '';
    echo "<option value='$tyostatus_row[selite]' $sel>$tyostatus_row[selitetark]</option>";
  }
  mysql_data_seek($tyostatus_result, 0);
  echo "</select></td>";
  echo "</tr>";

echo "<tr>
  <th>".t("Lopetuspäivä (pp-kk-vvvv)")."</th>
  <td>
  <input type='text' size='2' maxlength='2' name='lopetuspp' value='$lopetuspp'>
  <input type='text' size='2' maxlength='2' name='lopetuskk' value='$lopetuskk'>
  <input type='text' size='4' maxlength='4' name='lopetusvv' value='$lopetusvv'>
  </td>
  <td><select name='lopetustila'>";
  echo "<option value=''>Ei valintaa</option>";
  while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
    $sel = $lopetustila == $tyostatus_row['selite'] ? " SELECTED " : '';
    echo "<option value='$tyostatus_row[selite]' $sel>$tyostatus_row[selitetark]</option>";
  }
  echo "</select></td>";
  echo "</tr>";
  
echo "<tr>
  <th>".t("Rajaa tuotemerkillä")."</th>
  <td><input type='text' name='valmistajarajaus' value='$valmistajarajaus'></td>
  </tr>";

echo "</table><br>";
echo "<br><input type='submit' value='".t("Aja työmääräysraportti")."'>";
echo "</form>";

require("../inc/footer.inc");
