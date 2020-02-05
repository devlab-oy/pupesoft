<?php

/* ***************************************** **
  Tecdoc luokka
  Käyttö:
    constructille passataan tyyppi pc/cv ja voidaan overridettää rekisterisuodatuksen käyttö true/false
      oletukset  pc=rekisterisuodatus
            cv=ei rekisterisuodatusta


    $td = new tecdoc('pc', true);
    $brands = $td->getBrands();
    $models = $td->getModels($brands['manuid']);
    $versions = $td->getVersions($models['modelno']);
    $version = $td->getVersion($autoid);

/* ***************************************** */

class tecdoc {

  // suodatus rekisteriliitosten avulla
  private $type = 'pc';

  // pc=ktypnr, cv=ntypnr
  private $typnrPrefix = 'k';

  // oletuksena suodatetaan
  private $regfilter = true;

  public function __construct($car_type = 'pc', $register_filter = null) {
    // pc ja cv ainoat sallitut tyypit
    if ($car_type == 'pc' or $car_type == 'cv') {
      $this->type = $car_type;
    }
    else {
      die("tecdoc.class: type '$car_type' not allowed");
    }

    // cv:llä aina regfilter pois
    if ($this->type == 'cv') {
      $this->regfilter = false;
    }
    elseif ($register_filter !== null) {
      // muutoin mennään sen mukaan mitä tarjoillaan muuttujiin
      $this->regfilter = $register_filter;
    }

    if ($this->type == 'cv') {
      $this->typnrPrefix = 'n';
    }
  }


  public function getBrands() {
    global $kukarow;

    if ($this->type == 'cv') {
      // commercial vehicle, raskas mallit
      $qu = "SELECT td_manu.hernr manuid, td_manu.name
             FROM td_cv
             JOIN td_manu ON (td_manu.hernr = td_cv.hernr AND td_manu.nkw = 1)
             GROUP BY 1,2
             ORDER BY td_manu.name";
    }
    elseif ($this->type == 'pc' and $this->regfilter) {
      // personal car, kevyen ajoneuvot

      $qu = "SELECT distinct td_pc.hernr manuid, td_manu.name
             FROM yhteensopivuus_rekisteri
             JOIN td_pc ON (td_pc.ktypnr = yhteensopivuus_rekisteri.autoid)
             JOIN td_manu ON (td_manu.hernr = td_pc.hernr)
             WHERE yhteensopivuus_rekisteri.yhtio = '{$kukarow['yhtio']}'
             ORDER BY 2,1";
    }
    elseif ($this->type == 'pc') {
      $qu = "SELECT td_manu.hernr manuid, td_manu.name
             FROM td_pc
             JOIN td_manu ON (td_manu.hernr = td_pc.hernr AND td_manu.pkw = 1)
             GROUP BY 1,2
             ORDER BY td_manu.name";
    }
    else {
      return false;
    }

    $re = pupe_query($qu);

    $data = array();

    while ($row = mysql_fetch_assoc($re)) {
      $data[] = $row;
    }

    return $data;
  }


  public function getModels($manufacturer_id = null) {
    global $kukarow;

    $manufacturer_id = (int) $manufacturer_id;

    if ($manufacturer_id == 0) {
      return false;
    }

    if ($this->regfilter) {
      $regadd = "JOIN yhteensopivuus_rekisteri t2 ON t2.yhtio = '{$kukarow['yhtio']}' AND t1.{$this->typnrPrefix}typnr = t2.autoid";
    }
    else {
      $regadd = "";
    }

    if ($this->type == "pc") {
      $qu = "SELECT DISTINCT t1.kmodnr 'modelno', t3.description 'modelname', t3.bjvon vma, t3.bjbis vml
             FROM td_pc t1
             {$regadd}
             JOIN td_model t3 ON t1.kmodnr = t3.kmodnr
             WHERE t1.hernr = {$manufacturer_id}
             ORDER BY 2,3,4";
    }
    elseif ($this->type == 'cv') {
      $qu = "SELECT DISTINCT t1.kmodnr 'modelno', t3.description 'modelname', t3.bjvon vma, t3.bjbis vml
             FROM td_cv t1
             {$regadd}
             JOIN td_model t3 ON t1.kmodnr = t3.kmodnr
             WHERE t1.hernr = {$manufacturer_id}
             ORDER BY 2,3,4";
    }

    $re = pupe_query($qu);

    $data = array();

    while ($row = mysql_fetch_assoc($re)) {
      $row['year_txt'] = $this->niceyear($row['vma'], $row['vml']);
      $data[] = $row;
    }

    return $data;
  }


  public function getVersion($ktypnr) {
    $data = $this->getVersions('', array($ktypnr));

    return $data[0];
  }


  public function getVersions($kmodnr, $ktypnr = array()) {
    /* input: mallino, merkkino, tyyppi, reksuodatus, autoid (yksi tai useampi arrayna) */
    global $kukarow;

    $qryadd = $regadd1 = $regadd2 = "";

    if (!(is_array($ktypnr))) {
      die("tecdoc.class: ktypnr type is not array! $this->type");
    }

    if ($this->regfilter) {
      $regadd1 = ", REPLACE(FORMAT(count(autoid) / count(distinct t5.mcode), 0),',','') rekmaara";

      $regadd2 = "JOIN yhteensopivuus_rekisteri t2 ON (t2.yhtio = '".$kukarow['yhtio']."' AND t1.".$this->typnrPrefix."typnr = t2.autoid)";
    }

    /* tuunataan query sen mukaan haetaanko mallinumerolla, merkkinumerolla, autoid:lla vai kaikilla */
    if ($kmodnr != '') {
      $kmodnr = (int)$kmodnr;

      if ($kmodnr > 0) {
        $qryadd = " t1.kmodnr = '{$kmodnr}'";
      }
      else return false;
    }

    if (is_array($ktypnr) and count($ktypnr) > 0) {
      if ($qryadd != '') { $qryadd = $qryadd." AND "; }
      $qryadd = $qryadd." t1.".$this->typnrPrefix."typnr IN ('".implode(",", $ktypnr)."')";
    }

    if (trim($qryadd) == '') {
      return false;
    }

    if ($this->type == 'pc') {
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
             group_concat(distinct t5.mcode SEPARATOR ', ') mcodes
             {$regadd1}
             FROM td_pc t1
             JOIN td_model t3 ON (t3.kmodnr = t1.kmodnr)
             JOIN td_manu manu ON (t1.hernr = manu.hernr)
             LEFT JOIN td_pc_eng t4 ON (t4.ktypnr = t1.ktypnr)
             LEFT JOIN td_eng t5 ON (t5.motnr = t4.motnr)
             {$regadd2}
             WHERE
             {$qryadd}
             GROUP BY 1
             ORDER BY version, vma";
    }
    elseif ($this->type == 'cv') {
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
             group_concat(distinct t5.mcode SEPARATOR ', ') mcodes
             {$regadd1}
             FROM td_cv t1
             JOIN td_model t3 ON t3.kmodnr = t1.kmodnr
             JOIN td_manu manu ON t1.hernr = manu.hernr
             LEFT JOIN td_cv_eng t4 ON t4.ntypnr = t1.ntypnr
             LEFT JOIN td_eng t5 ON t5.motnr = t4.motnr
             {$regadd2}
             WHERE
             {$qryadd}
             GROUP BY 1
             ORDER BY version, vma";
    }
    else return;

    $re = pupe_query($qu);

    $data = array();

    while ($row = mysql_fetch_assoc($re)) {
      $row['year_txt'] = $this->niceYear($row['vma'], $row['vml']);
      $data[] = $row;
    }

    return $data;

  }


  public function cleanYear($vm) {
    if ($vm == 0) return '';
    $vm = (string)$vm;
    return (int)substr($vm, 4, 2).'/'.substr($vm, 0, 4);
  }


  public function niceYear($vma, $vml) {
    if ($vma > 0) {
      $vma = $this->cleanYear($vma);
    }
    else $vma = '';

    if ($vml > 0) {
      $vml = $this->cleanYear($vml);
    }
    else $vml = '';

    if ($vma == '' && $vml == '') {
      return '';
    }

    if ($vma != '' && $vml == '') {
      $delim = '&rarr;';
    }
    else $delim = '-';
    return $vma.' '.$delim.' '.$vml;
  }


  public function getRegSumForProduct($tuote) {
    global $kukarow;
    
    $query = "SELECT count(*) rekmaara
              FROM yhteensopivuus_tuote
              JOIN yhteensopivuus_rekisteri ON (
                yhteensopivuus_rekisteri.yhtio = yhteensopivuus_tuote.yhtio
                AND yhteensopivuus_rekisteri.autoid = yhteensopivuus_tuote.atunnus)
              WHERE yhteensopivuus_tuote.yhtio = '{$kukarow['yhtio']}'
              AND yhteensopivuus_tuote.tuoteno = '{$tuote}'
              AND yhteensopivuus_tuote.tyyppi  = 'HA'";
    $ytres = pupe_query($query);
    $ytrow = mysql_fetch_assoc($ytres);
    
    return $ytrow['rekmaara'];
  }
  

}
