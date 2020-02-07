<?php

/*
parametrit
------------
reksuodatus
mallino
merkkino
autoid
rekno
tyyppi (pc/cv)
*/

function ysr_autoidt($type = "") {
  global $kukarow, $yhtiorow;

  $query = "SELECT DISTINCT autoid
            FROM yhteensopivuus_rekisteri
            WHERE yhtio = '{$kukarow['yhtio']}'
            and autoid  > 0";
  $ysr_res = pupe_query($query);

  if (mysql_num_rows($ysr_res) == 0) {
    $regadd = "";
  }
  else {
    $regadd = "";

    while ($ysr_row = mysql_fetch_assoc($ysr_res)) {
      $regadd .= $ysr_row["autoid"].",";
    }

    if ($type == 'cv') {
      $regadd = "ntypnr in (".substr($regadd, 0, -1).") ";
    }
    else {
      $regadd = "ktypnr in (".substr($regadd, 0, -1).") ";
    }
  }

  return $regadd;
}

function td_getbrands($params = array()) {
  /* reksuodatus, tyyppi, merkkino, merkkinimi */
  global $kukarow, $yhtiorow;

  /* jos tyyppi = pc niin näytä VAIN pc -valmistajat. Reksuodatuksen kanssa ei pakko käyttää. */
  $type   = isset($params["tyyppi"]) ? $params["tyyppi"] : '';
  $regadd = "";

  if (isset($params["reksuodatus"]) and $params['reksuodatus']) {
    $regadd = ysr_autoidt($type);
    if ($regadd == "") return;
    else $regadd = "WHERE t1.{$regadd}";
  }

  if ($type == 'cv') {
    $qu = "SELECT td_manu.hernr manuid, td_manu.name
           FROM td_cv t1
           JOIN td_manu ON (td_manu.hernr = t1.hernr AND td_manu.nkw = 1)
           {$regadd}
           GROUP BY 1,2
           ORDER BY td_manu.name";
  }
  elseif ($type == 'pc') {
    $qu = "SELECT td_manu.hernr manuid, td_manu.name
           FROM td_pc t1
           JOIN td_manu ON (td_manu.hernr = t1.hernr AND td_manu.pkw = 1)
           {$regadd}
           GROUP BY 1,2
           ORDER BY td_manu.name";
  }
  else {
    return false;
  }

  return pupe_query($qu);
}

function td_getmodels($params = array()) {
  /* input: tyyppi, merkkino, reksuodatus */

  global $kukarow, $yhtiorow;

  $type   = isset($params["tyyppi"]) ? $params["tyyppi"] : '';
  $manu   = isset($params["merkkino"]) ? $params["merkkino"] : '';
  $regadd = "";

  if (isset($params["reksuodatus"]) and $params['reksuodatus']) {
    $regadd = ysr_autoidt($type);
    if ($regadd == "") return;
    else $regadd = "AND t1.{$regadd}";
  }

  if ($manu != '' and $type != '') {
    if ($type == "pc") {
      $qu = "SELECT DISTINCT t1.kmodnr 'modelno', t3.description 'modelname', t3.bjvon vma, t3.bjbis vml
             FROM td_pc t1
             JOIN td_model t3 ON t1.kmodnr = t3.kmodnr
             WHERE t1.hernr = {$manu}
             {$regadd}
             ORDER BY 2,3,4";
    }
    elseif ($type == 'cv') {
      $qu = "SELECT DISTINCT t1.kmodnr 'modelno', t3.description 'modelname', t3.bjvon vma, t3.bjbis vml
             FROM td_cv t1
             JOIN td_model t3 ON t1.kmodnr = t3.kmodnr
             WHERE t1.hernr = {$manu}
             {$regadd}
             ORDER BY 2,3,4";
    }
    else {
      return false;
    }

    return pupe_query($qu);
  }
}

function td_getversion($params = array()) {
  /* input: mallino, merkkino, tyyppi, reksuodatus, autoid (yksi tai useampi arrayna) */
  global $kukarow, $yhtiorow;

  $type    = isset($params["tyyppi"]) ? $params["tyyppi"] : '';
  $kmodnr  = isset($params["mallino"]) ? $params["mallino"] : '';
  $hernr   = isset($params["merkkino"]) ? $params["merkkino"] : '';
  $ktypnr  = isset($params["autoid"]) ? $params["autoid"] : '';
  $orderby = isset($params["orderby"]) ? "ORDER BY ".$params["orderby"] : 'ORDER BY version, vma'; // vaihtoehtoinen ja default order
  $qryadd  = "";
  $regadd  = "";

  /* tuunataan query sen mukaan haetaanko mallinumerolla, merkkinumerolla, autoid:lla vai kaikilla */
  if ($kmodnr != '') {
    $qryadd = " t1.kmodnr = {$kmodnr}";
  }

  if ($hernr != '') {
    if ($qryadd != '') { $qryadd = $qryadd." AND "; }
    $qryadd = $qryadd."t1.hernr = {$hernr}";
  }

  if ((is_array($ktypnr) and count($ktypnr) > 0) or (!is_array($ktypnr) and (int) $ktypnr != 0)) {
    if ($type == 'cv') $tpref = 'n';
    elseif ($type == 'pc') $tpref = 'k';

    if ($qryadd != '') {
      $qryadd = $qryadd." AND ";
    }

    if (is_array($ktypnr)) {
      $qryadd = $qryadd." t1.".$tpref."typnr IN ('".implode("','", $ktypnr)."')";
    }
    else {
      $qryadd = $qryadd."t1.".$tpref."typnr = {$ktypnr}";
    }
  }

  if (isset($params["reksuodatus"]) and $params['reksuodatus']) {
    $regadd = ysr_autoidt($type);
    if ($regadd == "") return;
    else $regadd = "AND t1.{$regadd}";
  }

  // Jonkinlainen systeemivirhe?
  if ($qryadd == "") {
    return;
  }

  if ($type == 'pc') {
    $qu = "SELECT
           t1.ktypnr autoid,
           t1.hernr manuid,
           t1.kmodnr modelno,
           manu.name manu,
           t3.description model,
           t1.description version,
           t1.bjvon vma,
           t1.bjbis vml,
           t1.kw kw,
           t1.ps hp,
           t1.ccmtech cc,
           t1.zyl cyl,
           t1.valves,
           round(t1.lit / 100, 1) capltr,
           t1.tueren doors,
           t1.tankinhalt fuelcap,
           t1.spannung volt,
           t1.abs,
           t1.asr,
           t1.enginetype,
           t1.fuelmixture,
           t1.drivetype,
           t1.fueltype,
           t1.braketype,
           t1.brakesystem,
           t1.catalyst,
           t1.transmission,
           t1.bodytype,
           (SELECT count(*) rekmaara FROM yhteensopivuus_rekisteri WHERE yhteensopivuus_rekisteri.yhtio = '{$kukarow['yhtio']}' and yhteensopivuus_rekisteri.autoid = t1.ktypnr) rekmaara,
           group_concat(distinct t5.mcode SEPARATOR ', ') mcodes
           FROM td_pc t1
           JOIN td_model t3 ON t3.kmodnr = t1.kmodnr
           JOIN td_manu manu ON t1.hernr = manu.hernr
           LEFT JOIN td_pc_eng t4 ON t4.ktypnr = t1.ktypnr
           LEFT JOIN td_eng t5 ON t5.motnr = t4.motnr
           WHERE
           {$qryadd}
           {$regadd}
           GROUP BY 1
           {$orderby}";
  }
  elseif ($type == 'cv') {
    $qu = "SELECT
           t1.ntypnr autoid,
           t1.hernr manuid,
           t1.kmodnr modelno,
           manu.name manu,
           t3.description model,
           t1.descr version,
           t1.bjvon vma,
           t1.bjbis vml,
           t1.bodytype,
           t1.enginetype,
           t1.kwvon kwa,
           t1.kwbis kwl,
           t1.psvon hpa,
           t1.psbis hpl,
           t1.ccmtech cc,
           t1.tonnage tons,
           t1.axleconf axles,
           round(t1.ccmtech / 1000, 1) capltr,
           (SELECT count(*) rekmaara FROM yhteensopivuus_rekisteri WHERE yhteensopivuus_rekisteri.yhtio = '{$kukarow['yhtio']}' and yhteensopivuus_rekisteri.autoid = t1.ntypnr) rekmaara,
           group_concat(distinct t5.mcode SEPARATOR ', ') mcodes
           FROM td_cv t1
           JOIN td_model t3 ON t3.kmodnr = t1.kmodnr
           JOIN td_manu manu ON t1.hernr = manu.hernr
           LEFT JOIN td_cv_eng t4 ON t4.ntypnr = t1.ntypnr
           LEFT JOIN td_eng t5 ON t5.motnr = t4.motnr
           WHERE
           {$qryadd}
           {$regadd}
           GROUP BY 1
           {$orderby}";
  }
  else return;

  $re = pupe_query($qu);
  return $re;
}

function td_regsearch($params = array()) {
  global $kukarow, $yhtiorow;

  $regno  = isset($params["rekno"]) ? $params["rekno"] : '';
  $params["tyyppi"] = isset($params["tyyppi"]) ? $params["tyyppi"] : 'pc'; // jos ei tarjota tyyppia niin default=pc

  $qu = "SELECT t1.autoid, t1.ajoneuvolaji
         FROM yhteensopivuus_rekisteri t1
         WHERE yhtio  = '{$kukarow['yhtio']}'
         AND t1.rekno = '{$regno}'";
  $re = pupe_query($qu);

  if (mysql_num_rows($re) == 1) {
    $regrow = mysql_fetch_assoc($re);

    $params["autoid"] = $regrow['autoid'];

    return td_getversion($params);

  }
  else {
    /* useampi tulos? */
    return;
  }
}

function td_cleanyear($vm) {
  if ($vm == 0) return "";
  $vm = (string)$vm;
  return (int)substr($vm, 4, 2)."/".substr($vm, 0, 4);
}

function td_niceyear($vma, $vml) {
  if ($vma > 0) {
    $vma = td_cleanyear($vma);
  }
  else $vma = '';

  if ($vml > 0) {
    $vml = td_cleanyear($vml);
  }
  else $vml = '';

  if ($vma != '' && $vml == '') {
    $delim = "&rarr;";
  }
  else $delim = "-";
  return $vma." ".$delim." ".$vml;
}

function td_regcarsum($tuote, $type = "HA") {
  global $kukarow, $yhtiorow;

  $query = "SELECT group_concat(yhteensopivuus_tuote.atunnus) atunnarit
            FROM yhteensopivuus_tuote
            WHERE yhteensopivuus_tuote.yhtio = '{$kukarow['yhtio']}'
            AND yhteensopivuus_tuote.tuoteno = '{$tuote}'
            AND yhteensopivuus_tuote.tyyppi  = 'HA'";
  $ytres = pupe_query($query);
  $ytrow = mysql_fetch_assoc($ytres);

  $rekmaara = 0;

  if ($ytrow['atunnarit'] != "") {
    $re = td_getversion(array('tyyppi' => 'pc', 'autoid' => explode(",", $ytrow['atunnarit'])));


    while ($row = mysql_fetch_assoc($re)) {
      $rekmaara += $row['rekmaara'];
    }
  }

  return $rekmaara;
}
