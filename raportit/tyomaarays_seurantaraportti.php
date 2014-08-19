<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Ei käytetä pakkausta
$compression = FALSE;

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
         }else { 
         $firstweek = 6-$firstday; 
         } 
    $totalfw = 7-$firstday; 
    
    //workdays of last week 
    if ($lastday==6){ 
         //so we don't count sat, sun=0 so it won't be counted anyway 
         $lastweek = 5; 
         }else { 
         $lastweek = $lastday; 
         } 
    $totallw = $lastday+1; 
        
    //check for any mid-weeks  
    if (($totalfw+$totallw)>=$totaldays){ 
         $midweeks = 0; 
         } else { //count midweeks 
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

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $i        = 0;

  $valmistajahakulisa = isset($valmistajahaku) ? " and tyomaarays.merkki = '{$valmistajahaku}' " : "" ;
  $aloituspaiva = "{$aloitusvv}-{$aloituskk}-{$aloituspp}";
  $lopetuspaiva = "{$lopetusvv}-{$lopetuskk}-{$lopetuspp}";

  $query = "SELECT 
            tyomaarays_tunnus,
            min(tyomaarayksen_tapahtumat.luontiaika) alkupvm,
            a1.selitetark alkustatus,
            a2.selitetark alkujono
            FROM tyomaarayksen_tapahtumat 
            LEFT JOIN avainsana a1 ON (a1.yhtio = tyomaarayksen_tapahtumat.yhtio 
              AND a1.laji = 'tyom_tyostatus' 
              AND a1.selite = tyomaarayksen_tapahtumat.tyostatus_selite)
            LEFT JOIN avainsana a2 ON (a2.yhtio = tyomaarayksen_tapahtumat.yhtio 
              AND a2.laji = 'tyom_tyojono' 
              AND a2.selite = tyomaarayksen_tapahtumat.tyojono_selite)
            LEFT JOIN tyomaarays ON (tyomaarays.yhtio = tyomaarayksen_tapahtumat.yhtio 
              AND tyomaarays.otunnus = tyomaarayksen_tapahtumat.tyomaarays_tunnus)
            WHERE tyomaarayksen_tapahtumat.yhtio = '{$kukarow['yhtio']}'
            AND tyomaarayksen_tapahtumat.luontiaika > '{$aloituspaiva}'
            {$valmistajahakulisa}
            GROUP BY tyomaarays_tunnus";
  $result = pupe_query($query);

  $worksheet->write($excelrivi, $i, t('Työmääräysnumero'), $format_bold);
  $i++;
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
    $worksheet->writeString($excelrivi, $i, $rivi['alkustatus']." / ".$rivi['alkujono']);
    $i++;
    $worksheet->writeString($excelrivi, $i, $rivi['alkupvm']);
    $i++;

    $subuquery = "SELECT 
                  max(tyomaarayksen_tapahtumat.luontiaika) loppupvm,
                  a1.selitetark loppustatus,
                  a2.selitetark loppujono
                  FROM tyomaarayksen_tapahtumat
                  LEFT JOIN avainsana a1 ON (a1.yhtio = tyomaarayksen_tapahtumat.yhtio
                    AND a1.laji = 'tyom_tyostatus'
                    AND a1.selite = tyomaarayksen_tapahtumat.tyostatus_selite)
                  LEFT JOIN avainsana a2 ON (a2.yhtio = tyomaarayksen_tapahtumat.yhtio
                    AND a2.laji = 'tyom_tyojono'
                    AND a2.selite = tyomaarayksen_tapahtumat.tyojono_selite)
                  LEFT JOIN tyomaarays ON (tyomaarays.yhtio = tyomaarayksen_tapahtumat.yhtio
                    AND tyomaarays.otunnus = tyomaarayksen_tapahtumat.tyomaarays_tunnus)
                  WHERE tyomaarayksen_tapahtumat.yhtio = '{$kukarow['yhtio']}'
                  AND tyomaarayksen_tapahtumat.luontiaika < '{$lopetuspaiva} 23:59:59'
                  AND tyomaarayksen_tapahtumat.tyomaarays_tunnus = '{$rivi['tyomaarays_tunnus']}'";
    $suburesult = pupe_query($subuquery);

    $suburivi = mysql_fetch_assoc($suburesult);

    $worksheet->writeString($excelrivi, $i, $suburivi['loppustatus']." / ".$suburivi['loppujono']);
    $i++;
    $worksheet->writeString($excelrivi, $i, $suburivi['loppupvm']);
    $i++;
    
    // Lasketaan business days ensimmäisen ja viimeisen tapahtuman välillä
    $businessdays = count_workdays($rivi['alkupvm'],$suburivi['loppupvm']);
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
echo "<table>";

$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");

$optiot = '';
while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
  $optiot .= "<option value='$tyostatus_row[selite]'>$tyostatus_row[selitetark]</option>";
}

echo "<tr>
  <th>".t("Aloituspäivä (pp-kk-vvvv)")."</th>
  <td>
  <input type='text' size='2' maxlength='2' name='aloituspp' value='$aloituspp'>
  <input type='text' size='2' maxlength='2' name='aloituskk' value='$aloituskk'>
  <input type='text' size='4' maxlength='4' name='aloitusvv' value='$aloitusvv'>
  </td>
  <td><select name='aloitustila'>";
  echo $optiot;
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
  echo $optiot;
  echo "</select></td>";
  echo "</tr>";
  
echo "<tr>
  <th>".t("Rajaa valmistajalla")."</th>
  <td><input type='text' name='valmistajarajaus' value='$valmistajarajaus'></td>
  </tr>";

echo "</table><br>";
echo "<br><input type='submit' value='".t("Aja työmääräysraportti")."'>";
echo "</form>";

require("../inc/footer.inc");
