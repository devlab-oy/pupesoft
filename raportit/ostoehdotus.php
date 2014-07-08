<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }

  if ($_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require '../inc/parametrit.inc';

if (isset($tee) AND $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

require 'inc/pupeExcel.inc';

$worksheet = new pupeExcel();
$format_bold = array("bold" => TRUE);
$excelrivi = 0;

// Arrayt otsikoiden käsittelyyn
// Otsikoiden kirjoitus tehdään arrayden kautta,
// koska write komento haluaa tietää sarakkeen numeron
// ja sarakkeiden määrän on helpointa vaan käsitellä loopilla.
$headerivi = array();

require_once 'inc/ProgressBar.class.php';

echo "<font class = 'head'>".t("Ostoehdotus")."</font><hr>";

$useampi_yhtio = 0;

if (is_array($valitutyhtiot)) {
  foreach ($valitutyhtiot as $yhtio) {
    $yhtiot .= "'$yhtio',";
    $useampi_yhtio++;
  }
  $yhtiot = substr($yhtiot, 0, -1);
}

if ($yhtiot == "") {
  $yhtiot = "'$kukarow[yhtio]'";
}

// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " + tilausrivi.varattu";
}
else {
  $lisavarattu = "";
}

function myynnit($myynti_varasto = '', $myynti_maa = '') {
  // Otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
  extract($GLOBALS);

  $laskuntoimmaa = "";
  $varastotapa = " JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
                       AND varastopaikat.tunnus = tilausrivi.varasto) ";

  if ($myynti_varasto != "") {
    $varastotapa .= " AND varastopaikat.tunnus = '$myynti_varasto' ";
  }
  elseif ($erikoisvarastot != "") {
    $varastotapa .= " AND varastopaikat.tyyppi = '' ";
  }
  else {
    $varastotapa = "";
  }

  if ($myynti_maa != "") {
    $laskuntoimmaa = " AND lasku.toim_maa = '$myynti_maa' ";
  }

  // Tutkaillaan myynti
  $query = "SELECT
            sum(if(tilausrivi.tyyppi = 'L'
              AND laadittu >= '$vva1-$kka1-$ppa1 00:00:00'
              AND laadittu <= '$vvl1-$kkl1-$ppl1 23:59:59'
              AND var = 'P', tilkpl, 0)) AS puutekpl1,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laadittu >= '$vva2-$kka2-$ppa2 00:00:00'
              AND laadittu <= '$vvl2-$kkl2-$ppl2 23:59:59'
              AND var = 'P', tilkpl, 0)) AS puutekpl2,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laadittu >= '$vva3-$kka3-$ppa3 00:00:00'
              AND laadittu <= '$vvl3-$kkl3-$ppl3 23:59:59'
              AND var = 'P', tilkpl, 0)) AS puutekpl3,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laadittu >= '$vva4-$kka4-$ppa4 00:00:00'
              AND laadittu <= '$vvl4-$kkl4-$ppl4 23:59:59'
              AND var = 'P', tilkpl, 0)) AS puutekpl4,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva1-$kka1-$ppa1'
              AND laskutettuaika <= '$vvl1-$kkl1-$ppl1', kpl, 0)) AS kpl1,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva2-$kka2-$ppa2'
              AND laskutettuaika <= '$vvl2-$kkl2-$ppl2', kpl, 0)) AS kpl2,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva3-$kka3-$ppa3'
              AND laskutettuaika <= '$vvl3-$kkl3-$ppl3', kpl, 0)) AS kpl3,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva4-$kka4-$ppa4'
              AND laskutettuaika <= '$vvl4-$kkl4-$ppl4', kpl, 0)) AS kpl4,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed'
              AND laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed', kpl, 0)) AS EDkpl1,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed'
              AND laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed', kpl, 0)) AS EDkpl2,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed'
              AND laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed', kpl, 0)) AS EDkpl3,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed'
              AND laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed', kpl, 0)) AS EDkpl4,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva1-$kka1-$ppa1'
              AND laskutettuaika <= '$vvl1-$kkl1-$ppl1', tilausrivi.kate, 0)) AS kate1,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva2-$kka2-$ppa2'
              AND laskutettuaika <= '$vvl2-$kkl2-$ppl2', tilausrivi.kate, 0)) AS kate2,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva3-$kka3-$ppa3'
              AND laskutettuaika <= '$vvl3-$kkl3-$ppl3', tilausrivi.kate, 0)) AS kate3,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva4-$kka4-$ppa4'
              AND laskutettuaika <= '$vvl4-$kkl4-$ppl4', tilausrivi.kate, 0)) AS kate4,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva1-$kka1-$ppa1'
              AND laskutettuaika <= '$vvl1-$kkl1-$ppl1', rivihinta, 0)) AS rivihinta1,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva2-$kka2-$ppa2'
              AND laskutettuaika <= '$vvl2-$kkl2-$ppl2', rivihinta, 0)) AS rivihinta2,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva3-$kka3-$ppa3'
              AND laskutettuaika <= '$vvl3-$kkl3-$ppl3', rivihinta, 0)) AS rivihinta3,
            sum(if(tilausrivi.tyyppi = 'L'
              AND laskutettuaika >= '$vva4-$kka4-$ppa4'
              AND laskutettuaika <= '$vvl4-$kkl4-$ppl4', rivihinta, 0)) AS rivihinta4,
            sum(if((tilausrivi.tyyppi = 'L' OR tilausrivi.tyyppi = 'V')
              AND tilausrivi.var not IN ('P', 'J', 'O', 'S'), tilausrivi.varattu, 0)) AS ennpois,
            sum(if(tilausrivi.tyyppi = 'L'
              AND tilausrivi.var IN ('J', 'S'), tilausrivi.jt $lisavarattu, 0)) AS jt,
            sum(if(tilausrivi.tyyppi = 'E'
              AND tilausrivi.var != 'O', tilausrivi.varattu, 0)) AS ennakko
            FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
            JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus $laskuntoimmaa)
            JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus = lasku.liitostunnus $lisaa3)
            $varastotapa
            WHERE tilausrivi.yhtio IN ($yhtiot)
            AND tilausrivi.tyyppi  IN ('L', 'V', 'E')
            AND tilausrivi.tuoteno = '$row[tuoteno]'
            AND ((tilausrivi.laskutettuaika >= '$apvm' AND tilausrivi.laskutettuaika <= '$lpvm')
              OR tilausrivi.laskutettuaika = '0000-00-00')";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_array($result);

  $katepros1 = 0;
  $katepros2 = 0;
  $katepros3 = 0;
  $katepros4 = 0;

  if ($laskurow['rivihinta1'] != 0) {
    $katepros1 = round($laskurow['kate1'] / $laskurow['rivihinta1'] * 100, 0);
  }

  if ($laskurow['rivihinta2'] != 0) {
    $katepros2 = round($laskurow['kate2'] / $laskurow['rivihinta2'] * 100, 0);
  }

  if ($laskurow['rivihinta3'] <> 0) {
    $katepros3 = round($laskurow['kate3'] / $laskurow['rivihinta3'] * 100, 0);
  }

  if ($laskurow['rivihinta4'] <> 0) {
    $katepros4 = round($laskurow['kate4'] / $laskurow['rivihinta4'] * 100, 0);
  }

  // Myydyt kappaleet
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kpl1']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kpl2']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kpl3']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kpl4']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['EDkpl1']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['EDkpl2']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['EDkpl3']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['EDkpl4']);

  // Kate
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kate1']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kate2']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kate3']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['kate4']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $katepros1);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $katepros2);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $katepros3);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $katepros4);

  // Puute kappaleet
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['puutekpl1']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['puutekpl2']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['puutekpl3']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['puutekpl4']);

  // Ennakkopoistot ja jt:t
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['ennpois']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['jt']);
  $worksheet->writeNumber($excelrivi, $excelsarake++, $laskurow['ennakko']);
}

function saldot($myynti_varasto = '', $myynti_maa = '') {
  // Otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
  extract($GLOBALS);

  $varastotapa = "";

  if ($myynti_varasto != "") {
    $varastotapa = " AND varastopaikat.tunnus = '$myynti_varasto' ";
  }
  elseif ($erikoisvarastot != "") {
    $varastotapa .= " AND varastopaikat.tyyppi = '' ";
  }

  if ($myynti_maa != "") {
    $varastotapa .= " AND varastopaikat.maa = '$myynti_maa' ";
  }

  // Kaikkien valittujen varastojen saldo per maa
  $query = "SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
            FROM tuotepaikat
            JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus = tuotepaikat.varasto)
            $varastotapa
            WHERE tuotepaikat.yhtio IN ($yhtiot)
            AND tuotepaikat.tuoteno = '$row[tuoteno]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    while ($varrow = mysql_fetch_array($result)) {
      $worksheet->writeNumber($excelrivi, $excelsarake++, str_replace(".",",",$varrow['saldo']));
      $worksheet->writeNumber($excelrivi, $excelsarake++, str_replace(".",",",$varrow['halytysraja']));
    }
  }
  else {
    $worksheet->writeNumber($excelrivi, $excelsarake++, "0");
    $worksheet->writeNumber($excelrivi, $excelsarake++, "0");
  }
}

function ostot($myynti_varasto = '', $myynti_maa = '') {
  // Otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
  extract($GLOBALS);

  $varastotapa = " JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
                    AND varastopaikat.tunnus = tilausrivi.varasto)";

  if ($myynti_varasto != "") {
    $varastotapa .= " AND varastopaikat.tunnus = '$myynti_varasto' ";
  }
  elseif ($erikoisvarastot != "" AND $myynti_maa == "") {
    $query = "SELECT group_concat(tunnus)
              FROM varastopaikat
              WHERE yhtio IN ($yhtiot)
              AND tyyppi = ''";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_array($result);

    if ($laskurow[0] != "") {
      $varastotapa .= " AND varastopaikat.tunnus IN ($laskurow[0]) ";
    }
  }
  elseif ($myynti_maa != "") {
    $query = "SELECT group_concat(tunnus)
              FROM varastopaikat
              WHERE yhtio IN ($yhtiot)
              AND maa = '$myynti_maa'";

    if ($erikoisvarastot != "") {
      $query .= " AND tyyppi = '' ";
    }

    $result = pupe_query($query);
    $laskurow = mysql_fetch_array($result);

    if ($laskurow[0] != "") {
      $varastotapa .= " AND varastopaikat.tunnus IN ($laskurow[0]) ";
    }
  }
  else {
    $varastotapa = "";
  }

  // Tilauksessa/siirtolistalla jt
  $query = "SELECT
            sum(if (tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)) tilattu,
            sum(if (tilausrivi.tyyppi = 'G', tilausrivi.jt $lisavarattu, 0)) siirtojt
            FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
            $varastotapa
            WHERE tilausrivi.yhtio IN ($yhtiot)
            AND tilausrivi.tyyppi  IN ('O','G')
            AND tilausrivi.tuoteno = '$row[tuoteno]'
            AND tilausrivi.varattu + tilausrivi.jt > 0";
  $result = pupe_query($query);
  $ostorow = mysql_fetch_array($result);

  // Siirtolista jt kpl
  $worksheet->writeNumber($excelrivi, $excelsarake++, str_replace(".",",",$ostorow['siirtojt']));
  // Tilattu kpl
  $worksheet->writeNumber($excelrivi, $excelsarake++, str_replace(".",",",$ostorow['tilattu']));
}

// Org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
$org_rajaus = $abcrajaus;

list($abcrajaus, $abcrajaustapa) = explode("##", $abcrajaus);

if (!isset($abcrajaustapa)) {
  $abcrajaustapa = "TK";
}

list($ryhmanimet, $ryhmaprossat,,,, ) = hae_ryhmanimet($abcrajaustapa);

// Tarvittavat päivämäärät
if (!isset($kka1)) {
  $kka1 = date("m", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y")));
}

if (!isset($vva1)) {
  $vva1 = date("Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y")));
}

if (!isset($ppa1)) {
  $ppa1 = date("d", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y")));
}

if (!isset($kkl1)) {
  $kkl1 = date("m");
}

if (!isset($vvl1)) {
  $vvl1 = date("Y");
}

if (!isset($ppl1)) {
  $ppl1 = date("d");
}

if (!isset($kka2)) {
  $kka2 = date("m", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
}

if (!isset($vva2)) {
  $vva2 = date("Y", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
}

if (!isset($ppa2)) {
  $ppa2 = date("d", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
}

if (!isset($kkl2)) {
  $kkl2 = date("m");
}

if (!isset($vvl2)) {
  $vvl2 = date("Y");
}

if (!isset($ppl2)) {
  $ppl2 = date("d");
}

if (!isset($kka3)) {
  $kka3 = date("m", mktime(0, 0, 0, date("m") - 6, date("d"), date("Y")));
}

if (!isset($vva3)) {
  $vva3 = date("Y", mktime(0, 0, 0, date("m") - 6, date("d"), date("Y")));
}

if (!isset($ppa3)) {
  $ppa3 = date("d", mktime(0, 0, 0, date("m") - 6, date("d"), date("Y")));
}

if (!isset($kkl3)) {
  $kkl3 = date("m");
}

if (!isset($vvl3)) {
  $vvl3 = date("Y");
}

if (!isset($ppl3)) {
  $ppl3 = date("d");
}

if (!isset($kka4)) {
  $kka4 = date("m", mktime(0, 0, 0, date("m") - 12, date("d"), date("Y")));
}

if (!isset($vva4)) {
  $vva4 = date("Y", mktime(0, 0, 0, date("m") - 12, date("d"), date("Y")));
}

if (!isset($ppa4)) {
  $ppa4 = date("d", mktime(0, 0, 0, date("m") - 12, date("d"), date("Y")));
}

if (!isset($kkl4)) {
  $kkl4 = date("m");
}

if (!isset($vvl4)) {
  $vvl4 = date("Y");
}

if (!isset($ppl4)) {
  $ppl4 = date("d");
}

// Edellisen vuoden vastaavat kaudet
$kka1ed = date("m", mktime(0, 0, 0, $kka1, $ppa1, $vva1 - 1));
$vva1ed = date("Y", mktime(0, 0, 0, $kka1, $ppa1, $vva1 - 1));
$ppa1ed = date("d", mktime(0, 0, 0, $kka1, $ppa1, $vva1 - 1));
$kkl1ed = date("m", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1 - 1));
$vvl1ed = date("Y", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1 - 1));
$ppl1ed = date("d", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1 - 1));

$kka2ed = date("m", mktime(0, 0, 0, $kka2, $ppa2, $vva2 - 1));
$vva2ed = date("Y", mktime(0, 0, 0, $kka2, $ppa2, $vva2 - 1));
$ppa2ed = date("d", mktime(0, 0, 0, $kka2, $ppa2, $vva2 - 1));
$kkl2ed = date("m", mktime(0, 0, 0, $kkl2, $ppl2, $vvl2 - 1));
$vvl2ed = date("Y", mktime(0, 0, 0, $kkl2, $ppl2, $vvl2 - 1));
$ppl2ed = date("d", mktime(0, 0, 0, $kkl2, $ppl2, $vvl2 - 1));

$kka3ed = date("m", mktime(0, 0, 0, $kka3, $ppa3, $vva3 - 1));
$vva3ed = date("Y", mktime(0, 0, 0, $kka3, $ppa3, $vva3 - 1));
$ppa3ed = date("d", mktime(0, 0, 0, $kka3, $ppa3, $vva3 - 1));
$kkl3ed = date("m", mktime(0, 0, 0, $kkl3, $ppl3, $vvl3 - 1));
$vvl3ed = date("Y", mktime(0, 0, 0, $kkl3, $ppl3, $vvl3 - 1));
$ppl3ed = date("d", mktime(0, 0, 0, $kkl3, $ppl3, $vvl3 - 1));

$kka4ed = date("m", mktime(0, 0, 0, $kka4, $ppa4, $vva4 - 1));
$vva4ed = date("Y", mktime(0, 0, 0, $kka4, $ppa4, $vva4 - 1));
$ppa4ed = date("d", mktime(0, 0, 0, $kka4, $ppa4, $vva4 - 1));
$kkl4ed = date("m", mktime(0, 0, 0, $kkl4, $ppl4, $vvl4 - 1));
$vvl4ed = date("Y", mktime(0, 0, 0, $kkl4, $ppl4, $vvl4 - 1));
$ppl4ed = date("d", mktime(0, 0, 0, $kkl4, $ppl4, $vvl4 - 1));

// Katotaan pienin alkupvm ja isoin loppupvm
$apaiva1 = (int) date('Ymd', mktime(0, 0, 0, $kka1, $ppa1, $vva1));
$apaiva2 = (int) date('Ymd', mktime(0, 0, 0, $kka2, $ppa2, $vva2));
$apaiva3 = (int) date('Ymd', mktime(0, 0, 0, $kka3, $ppa3, $vva3));
$apaiva4 = (int) date('Ymd', mktime(0, 0, 0, $kka4, $ppa4, $vva4));
$apaiva5 = (int) date('Ymd', mktime(0, 0, 0, $kka1ed, $ppa1ed, $vva1ed));
$apaiva6 = (int) date('Ymd', mktime(0, 0, 0, $kka2ed, $ppa2ed, $vva2ed));
$apaiva7 = (int) date('Ymd', mktime(0, 0, 0, $kka3ed, $ppa3ed, $vva3ed));
$apaiva8 = (int) date('Ymd', mktime(0, 0, 0, $kka4ed, $ppa4ed, $vva4ed));

$lpaiva1 = (int) date('Ymd', mktime(0, 0, 0, $kkl1, $ppl1, $vvl1));
$lpaiva2 = (int) date('Ymd', mktime(0, 0, 0, $kkl2, $ppl2, $vvl2));
$lpaiva3 = (int) date('Ymd', mktime(0, 0, 0, $kkl3, $ppl3, $vvl3));
$lpaiva4 = (int) date('Ymd', mktime(0, 0, 0, $kkl4, $ppl4, $vvl4));
$lpaiva5 = (int) date('Ymd', mktime(0, 0, 0, $kkl1ed, $ppl1ed, $vvl1ed));
$lpaiva6 = (int) date('Ymd', mktime(0, 0, 0, $kkl2ed, $ppl2ed, $vvl2ed));
$lpaiva7 = (int) date('Ymd', mktime(0, 0, 0, $kkl3ed, $ppl3ed, $vvl3ed));
$lpaiva8 = (int) date('Ymd', mktime(0, 0, 0, $kkl4ed, $ppl4ed, $vvl4ed));

$apienin = 99999999;
$lsuurin = 0;

if ($apaiva1 <= $apienin and $apaiva1 != 19700101) {
  $apienin = $apaiva1;
}

if ($apaiva2 <= $apienin and $apaiva2 != 19700101) {
  $apienin = $apaiva2;
}

if ($apaiva3 <= $apienin and $apaiva3 != 19700101) {
  $apienin = $apaiva3;
}

if ($apaiva4 <= $apienin and $apaiva4 != 19700101) {
  $apienin = $apaiva4;
}

if ($apaiva5 <= $apienin and $apaiva5 != 19700101) {
  $apienin = $apaiva5;
}

if ($apaiva6 <= $apienin and $apaiva6 != 19700101) {
  $apienin = $apaiva6;
}

if ($apaiva7 <= $apienin and $apaiva7 != 19700101) {
  $apienin = $apaiva7;
}

if ($apaiva8 <= $apienin and $apaiva8 != 19700101) {
  $apienin = $apaiva8;
}


if ($lpaiva1 >= $lsuurin and $lpaiva1 != 19700101) {
  $lsuurin = $lpaiva1;
}

if ($lpaiva2 >= $lsuurin and $lpaiva2 != 19700101) {
  $lsuurin = $lpaiva2;
}

if ($lpaiva3 >= $lsuurin and $lpaiva3 != 19700101) {
  $lsuurin = $lpaiva3;
}

if ($lpaiva4 >= $lsuurin and $lpaiva4 != 19700101) {
  $lsuurin = $lpaiva4;
}

if ($lpaiva5 >= $lsuurin and $lpaiva5 != 19700101) {
  $lsuurin = $lpaiva5;
}

if ($lpaiva6 >= $lsuurin and $lpaiva6 != 19700101) {
  $lsuurin = $lpaiva6;
}

if ($lpaiva7 >= $lsuurin and $lpaiva7 != 19700101) {
  $lsuurin = $lpaiva7;
}

if ($lpaiva8 >= $lsuurin and $lpaiva8 != 19700101) {
  $lsuurin = $lpaiva8;
}

if ($apienin == 99999999 and $lsuurin == 0) {
  $apienin = $lsuurin = date('Ymd'); // Jos mitään ei löydy niin NOW molempiin. :)
}

$apvm = substr($apienin, 0, 4)."-".substr($apienin, 4, 2)."-".substr($apienin, 6, 2);
$lpvm = substr($lsuurin, 0, 4)."-".substr($lsuurin, 4, 2)."-".substr($lsuurin, 6, 2);

// Katsotaan tarvitaanko mennä toimittajahakuun
if (($ytunnus != "" and $toimittajaid == "") or ($edytunnus != $ytunnus)) {
  if ($edytunnus != $ytunnus) $toimittajaid = "";
  require ("inc/kevyt_toimittajahaku.inc");
  $ytunnus = $toimittajarow["ytunnus"];
  $tee = "";
}

// Tehdään itse raportti
if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {
  // Haetaan nimitietoa
  if ($tuoryh != '') {
    // Tehdään avainsana query
    $sresult = t_avainsana("TRY", "", "AND avainsana.selite = '$tuoryh'", $yhtiot);
    $trow1 = mysql_fetch_array($sresult);
  }

  if ($osasto != '') {
    // Tehdään avainsana query
    $sresult = t_avainsana("OSASTO", "", "AND avainsana.selite = '$osasto'", $yhtiot);
    $trow2 = mysql_fetch_array($sresult);
  }

  if ($toimittajaid != '') {
    $query = "SELECT nimi
              FROM toimi
              WHERE yhtio IN ($yhtiot) AND tunnus = '$toimittajaid'";
    $sresult = pupe_query($query);
    $trow3 = mysql_fetch_array($sresult);
  }

  $lisaa = ""; // Tuote-rajauksia
  $lisaa2 = ""; // Toimittaja-rajauksia
  $lisaa3 = ""; // Asiakas-rajauksia

  if ($osasto != '') {
    $lisaa .= " AND tuote.osasto = '$osasto' ";
  }

  if ($tuoryh != '') {
    $lisaa .= " AND tuote.try = '$tuoryh' ";
  }

  if ($tuotemerkki != '') {
    $lisaa .= " AND tuote.tuotemerkki = '$tuotemerkki' ";
  }

  if ($poistetut != '') {
    $lisaa .= " AND tuote.status != 'P' ";
  }

  if ($poistuva != '') {
    $lisaa .= " AND tuote.status != 'X' ";
  }

  if ($eihinnastoon != '') {
    $lisaa .= " AND tuote.hinnastoon != 'E' ";
  }

  if ($vainuudet != '') {
    $lisaa .= " AND tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
  }

  if ($eiuusia != '') {
    $lisaa .= " AND tuote.luontiaika < date_sub(current_date, interval 12 month) ";
  }

  if ($toimittajaid != '') {
    $lisaa2 .= " JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio
                  AND tuote.tuoteno = tuotteen_toimittajat.tuoteno
                  AND liitostunnus = '$toimittajaid') ";
  }

  if ($eliminoikonserni != '') {
    $lisaa3 .= " AND asiakas.konserniyhtio = '' ";
  }

  $abcnimi = $ryhmanimet[$abcrajaus];

  $varastot_paikoittain = "";

  if (is_array($valitutvarastot) and count($valitutvarastot) > 0) {
    $varastot_paikoittain = "KYLLA";
  }

  $maa_varastot = "";
  $varastot_maittain = "";

  if (is_array($valitutmaat) and count($valitutmaat) > 0) {
    $varastot_maittain = "KYLLA";

    // Katsotaan valitut varastot
    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio IN ($yhtiot)
              ORDER BY yhtio, tyyppi, nimitys";
    $vtresult = pupe_query($query);

    while ($vrow = mysql_fetch_array($vtresult)) {
      if (in_array($vrow["maa"], $valitutmaat)) {
        $maa_varastot .= "'".$vrow["tunnus"]."',";
      }
    }
  }

  $maa_varastot = substr($maa_varastot, 0, -1);

  // Katotaan JT:ssä olevat tuotteet ABC-analyysiä varten, koska ne pitää includata aina!
  $query = "SELECT group_concat(DISTINCT concat(\"'\", tilausrivi.tuoteno, \"'\") separator ', ')
            FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
            JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno $lisaa)
            WHERE tilausrivi.yhtio IN ($yhtiot)
            AND tyyppi             IN  ('L', 'G')
            AND var = 'J'
            AND jt $lisavarattu > 0";
  $vtresult = pupe_query($query);
  $vrow = mysql_fetch_array($vtresult);

  $jt_tuotteet = "''";

  if ($vrow[0] != "") {
    $jt_tuotteet = $vrow[0];
  }

  if ($abcrajaus != "") {
    // Joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
    $abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno)
                  ON (abc_aputaulu.yhtio = tuote.yhtio
                    AND abc_aputaulu.tuoteno = tuote.tuoteno
                    AND abc_aputaulu.tyyppi = '$abcrajaustapa'
                    AND (luokka <= '$abcrajaus'
                      OR luokka_osasto <= '$abcrajaus'
                      OR luokka_try <= '$abcrajaus'
                      OR tuote_luontiaika >= date_sub(current_date, interval 12 month)
                      OR abc_aputaulu.tuoteno IN ($jt_tuotteet))) ";
  }
  else {
    $abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno)
                  ON (abc_aputaulu.yhtio = tuote.yhtio
                    AND abc_aputaulu.tuoteno = tuote.tuoteno
                    AND abc_aputaulu.tyyppi = '$abcrajaustapa') ";
  }

  // Tässä haetaan sitten listalle soveltuvat tuotteet
  $query = "SELECT
            group_concat(tuote.yhtio) yhtio,
            tuote.tuoteno,
            tuote.halytysraja,
            tuote.tahtituote,
            tuote.status,
            tuote.nimitys,
            tuote.myynti_era,
            tuote.myyntihinta,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm,
            tuote.tuotemerkki,
            tuote.osasto,
            tuote.try,
            tuote.aleryhma,
            if(tuote.epakurantti100pvm = '0000-00-00',
              if(tuote.epakurantti75pvm = '0000-00-00',
                if(tuote.epakurantti50pvm = '0000-00-00',
                  if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75),
                tuote.kehahin * 0.5),
              tuote.kehahin * 0.25),
            0) AS kehahin,
            abc_aputaulu.luokka abcluokka,
            abc_aputaulu.luokka_osasto abcluokka_osasto,
            abc_aputaulu.luokka_try abcluokka_try,
            tuote.luontiaika
            FROM tuote
            $lisaa2
            $abcjoin
            LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio
              AND tuote.tuoteno = korvaavat.tuoteno)
            WHERE
            tuote.yhtio IN ($yhtiot)
            $lisaa
            AND tuote.ei_saldoa = ''
            AND tuote.tuotetyyppi NOT IN ('A', 'B')
            AND tuote.ostoehdotus = ''
            GROUP BY tuote.tuoteno
            ORDER BY id, tuote.tuoteno, yhtio";
  $res = pupe_query($query);

  echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
  flush();

  $rivi = "";

  $bar = new ProgressBar();
  $elements = mysql_num_rows($res); // Total number of elements to process
  $bar->initialize($elements); // Print the empty bar

  // Laitetaan otsikkotiedot otsikkoarrayn sisään
  if ($useampi_yhtio > 1) {
    $headerivi[] = ucfirst(t("yhtio"));
  }

  $headerivi[] = ucfirst(t("tuoteno"));
  $headerivi[] = ucfirst(t("osasto"));
  $headerivi[] = ucfirst(t("try"));
  $headerivi[] = ucfirst(t("tuotemerkki"));
  $headerivi[] = ucfirst(t("tähtituote"));
  $headerivi[] = ucfirst(t("status"));
  $headerivi[] = ucfirst(t("abc"));
  $headerivi[] = ucfirst(t("abc osasto"));
  $headerivi[] = ucfirst(t("abc try"));
  $headerivi[] = ucfirst(t("luontiaika"));
  $headerivi[] = ucfirst(t("tuotteen hälytysraja"));
  $headerivi[] = ucfirst(t("ostoerä"));
  $headerivi[] = ucfirst(t("myyntierä"));
  $headerivi[] = ucfirst(t("toimittaja"));
  $headerivi[] = ucfirst(t("toim tuoteno"));
  $headerivi[] = ucfirst(t("nimitys"));
  $headerivi[] = ucfirst(t("toim nimitys"));
  $headerivi[] = ucfirst(t("ostohinta"));
  $headerivi[] = ucfirst(t("myyntihinta"));
  $headerivi[] = ucfirst(t("epäkurantti25%"));
  $headerivi[] = ucfirst(t("epäkurantti50%"));
  $headerivi[] = ucfirst(t("epäkurantti75%"));
  $headerivi[] = ucfirst(t("epäkurantti100%"));
  $headerivi[] = ucfirst(t("tuotekerroin"));
  $headerivi[] = ucfirst(t("aleryhmä"));
  $headerivi[] = ucfirst(t("keskihankintahinta"));

  // Rullataan läpä maittain
  if ($varastot_maittain == "KYLLA") {
    foreach ($valitutmaat as $maa) {
      // Kirjoitetaan myyntiotsikot (myyntifunktioslle)
      // Myydyt kappaleet
      $headerivi[] = ucfirst(t("$maa kpl1"));
      $headerivi[] = ucfirst(t("$maa kpl2"));
      $headerivi[] = ucfirst(t("$maa kpl3"));
      $headerivi[] = ucfirst(t("$maa kpl4"));
      $headerivi[] = ucfirst(t("$maa edkpl1"));
      $headerivi[] = ucfirst(t("$maa edkpl2"));
      $headerivi[] = ucfirst(t("$maa edkpl3"));
      $headerivi[] = ucfirst(t("$maa edkpl4"));

      // Kate
      $headerivi[] = ucfirst(t("$maa kate1"));
      $headerivi[] = ucfirst(t("$maa kate2"));
      $headerivi[] = ucfirst(t("$maa kate3"));
      $headerivi[] = ucfirst(t("$maa kate4"));
      $headerivi[] = ucfirst(t("$maa katepro1"));
      $headerivi[] = ucfirst(t("$maa katepro2"));
      $headerivi[] = ucfirst(t("$maa katepro3"));
      $headerivi[] = ucfirst(t("$maa katepro4"));

      // Puute kappaleet
      $headerivi[] = ucfirst(t("$maa puutekpl1"));
      $headerivi[] = ucfirst(t("$maa puutekpl2"));
      $headerivi[] = ucfirst(t("$maa puutekpl3"));
      $headerivi[] = ucfirst(t("$maa puutekpl4"));

      // Ennakkopoistot ja jt:t
      $headerivi[] = ucfirst(t("$maa ennpois kpl"));
      $headerivi[] = ucfirst(t("$maa jt kpl"));
      $headerivi[] = ucfirst(t("$maa ennakkotilaus kpl"));

      if ($erikoisvarastot != "") {
        $varastotapa = " AND varastopaikat.tyyppi = '' ";
      }

      $varastotapa .= " AND varastopaikat.maa = '$maa'";

      // Kirjoitetaan saldo otsikot (saldofunkitolle)
      // Kaikkien valittujen varastojen saldo per maa
      $query = "SELECT ifnull(sum(saldo), 0) saldo, ifnull(sum(halytysraja), 0) halytysraja
                FROM tuotepaikat
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  AND varastopaikat.tunnus = tuotepaikat.varasto)
                $varastotapa
                WHERE tuotepaikat.yhtio IN ($yhtiot)
                AND tuotepaikat.tuoteno = '$row[tuoteno]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        while ($varrow = mysql_fetch_array($result)) {
          $headerivi[] = ucfirst(t("$maa saldo"));
          $headerivi[] = ucfirst(t("$maa hälytysraja"));
        }
      }
      else {
        $headerivi[] = ucfirst(t("$maa saldo"));
        $headerivi[] = ucfirst(t("$maa hälytysraja"));
      }

      // Kirjoitetaan osto otsikot (ostofunktille)
      // Siirtolista jt kpl
      $headerivi[] = ucfirst(t("$maa siirtojt kpl"));
      // Tilattu kpl
      $headerivi[] = ucfirst(t("$maa tilattu kpl"));
    }
  }

  // Sitten rullataan läpi varastoittain
  if ($varastot_paikoittain == "KYLLA") {
    foreach ($valitutvarastot as $varastotunnus) {
      $query = "SELECT nimitys
                FROM varastopaikat
                WHERE yhtio IN ($yhtiot)
                AND tunnus = '$varastotunnus'";
      $varastorow = mysql_fetch_array(pupe_query($query));
      $varastonimi = $varastorow["nimitys"];

      // Kirjoitetaan myyntiotsikot (myyntifunktioslle)
      // Myydyt kappaleet
      $headerivi[] = ucfirst(t("$varastonimi kpl1"));
      $headerivi[] = ucfirst(t("$varastonimi kpl2"));
      $headerivi[] = ucfirst(t("$varastonimi kpl3"));
      $headerivi[] = ucfirst(t("$varastonimi kpl4"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl1"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl2"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl3"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl4"));

      // Kate
      $headerivi[] = ucfirst(t("$varastonimi kate1"));
      $headerivi[] = ucfirst(t("$varastonimi kate2"));
      $headerivi[] = ucfirst(t("$varastonimi kate3"));
      $headerivi[] = ucfirst(t("$varastonimi kate4"));
      $headerivi[] = ucfirst(t("$varastonimi katepro1"));
      $headerivi[] = ucfirst(t("$varastonimi katepro2"));
      $headerivi[] = ucfirst(t("$varastonimi katepro3"));
      $headerivi[] = ucfirst(t("$varastonimi katepro4"));

      // Puute kappaleet
      $headerivi[] = ucfirst(t("$varastonimi puutekpl1"));
      $headerivi[] = ucfirst(t("$varastonimi puutekpl2"));
      $headerivi[] = ucfirst(t("$varastonimi puutekpl3"));
      $headerivi[] = ucfirst(t("$varastonimi puutekpl4"));

      // Ennakkopoistot ja jt:t
      $headerivi[] = ucfirst(t("$varastonimi ennpois kpl"));
      $headerivi[] = ucfirst(t("$varastonimi jt kpl"));
      $headerivi[] = ucfirst(t("$varastonimi ennakkotilaus kpl"));

      // Kirjoitetaan saldo otsikot (saldofunkitolle)
      $varastotapa = " AND varastopaikat.tunnus = '$varastotunnus' ";

      // Kaikkien valittujen varastojen saldo
      $query = "SELECT ifnull(sum(saldo), 0) saldo, ifnull(sum(halytysraja), 0) halytysraja
                FROM tuotepaikat
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  AND varastopaikat.tunnus = tuotepaikat.varasto)
                $varastotapa
                WHERE tuotepaikat.yhtio IN ($yhtiot)
                AND tuotepaikat.tuoteno = '$row[tuoteno]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        while ($varrow = mysql_fetch_array($result)) {
          $headerivi[] = ucfirst(t("$varastotunnus saldo"));
          $headerivi[] = ucfirst(t("$varastotunnus hälytysraja"));
        }
      }
      else {
        $headerivi[] = ucfirst(t("$varastotunnus saldo"));
        $headerivi[] = ucfirst(t("$varastotunnus hälytysraja"));
      }

      // Kirjoitetaan osto otsikot (ostofunktille)
      // Siirtolista jt kpl
      $headerivi[] = ucfirst(t("$varastotunnus siirtojt kpl"));
      // Tilattu kpl
      $headerivi[] = ucfirst(t("$varastotunnus tilattu kpl"));
    }
  }

  // Sitte vielä totalit
  // Kirjoitetaan myyntiotsikot (myyntifunktiolle)
  // Myydyt kappaleet
  $headerivi[] = ucfirst(t("Total kpl1"));
  $headerivi[] = ucfirst(t("Total kpl2"));
  $headerivi[] = ucfirst(t("Total kpl3"));
  $headerivi[] = ucfirst(t("Total kpl4"));
  $headerivi[] = ucfirst(t("Total edkpl1"));
  $headerivi[] = ucfirst(t("Total edkpl2"));
  $headerivi[] = ucfirst(t("Total edkpl3"));
  $headerivi[] = ucfirst(t("Total edkpl4"));

  // Kate
  $headerivi[] = ucfirst(t("Total kate1"));
  $headerivi[] = ucfirst(t("Total kate2"));
  $headerivi[] = ucfirst(t("Total kate3"));
  $headerivi[] = ucfirst(t("Total kate4"));
  $headerivi[] = ucfirst(t("Total katepro1"));
  $headerivi[] = ucfirst(t("Total katepro2"));
  $headerivi[] = ucfirst(t("Total katepro3"));
  $headerivi[] = ucfirst(t("Total katepro4"));

  // Puute kappaleet
  $headerivi[] = ucfirst(t("Total puutekpl1"));
  $headerivi[] = ucfirst(t("Total puutekpl2"));
  $headerivi[] = ucfirst(t("Total puutekpl3"));
  $headerivi[] = ucfirst(t("Total puutekpl4"));

  // Ennakkopoistot ja jt:t
  $headerivi[] = ucfirst(t("Total ennpois kpl"));
  $headerivi[] = ucfirst(t("Total jt kpl"));
  $headerivi[] = ucfirst(t("Total ennakkotilaus kpl"));

  // Kirjoitetaan saldo otsikot (saldofunkitolle)
  if ($erikoisvarastot != "") {
    $varastotapa = " AND varastopaikat.tyyppi = '' ";
  }

  // Kaikkien valittujen varastojen saldo per maa
  $query = "SELECT ifnull(sum(saldo), 0) saldo, ifnull(sum(halytysraja), 0) AS halytysraja
            FROM tuotepaikat
            JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus = tuotepaikat.varasto)
            $varastotapa
            WHERE tuotepaikat.yhtio IN ($yhtiot)
            AND tuotepaikat.tuoteno = '$row[tuoteno]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    while ($varrow = mysql_fetch_array($result)) {
      $headerivi[] = ucfirst(t("Total saldo"));
      $headerivi[] = ucfirst(t("Total hälytysraja"));
    }
  }
  else {
    $headerivi[] = ucfirst(t("Total saldo"));
    $headerivi[] = ucfirst(t("Total hälytysraja"));
  }

  // Kirjoitetaan osto otsikot (ostofunktille)
  // Siirtolista jt kpl
  $headerivi[] = ucfirst(t("Total siirtojt kpl"));
  // Tilattu kpl
  $headerivi[] = ucfirst(t("Total tilattu kpl"));

  // Kirjoitetaan otsikot tiedostoon
  for ($i = 0; $i < count($headerivi); $i++) {
    $worksheet->write($excelrivi, $i, ucfirst(t($headerivi[$i])), $format_bold);
  }

  // Siirrytään seuraavalle riville
  $excelrivi++;

  // Loopataan tuotteet läpi
  while ($row = mysql_fetch_array($res)) {

    $toimilisa = "";

    if ($toimittajaid != '') {
      $toimilisa = " AND tuotteen_toimittajat.liitostunnus = '$toimittajaid' ";
    }

    // Haetaan tuotteen toimittajatietoa
    $query = "SELECT group_concat(toimi.ytunnus
                ORDER BY tuotteen_toimittajat.tunnus separator '/') AS toimittaja,
              group_concat(DISTINCT tuotteen_toimittajat.osto_era
                ORDER BY tuotteen_toimittajat.tunnus separator '/') AS osto_era,
              group_concat(DISTINCT tuotteen_toimittajat.toim_tuoteno
                ORDER BY tuotteen_toimittajat.tunnus separator '/') AS toim_tuoteno,
              group_concat(DISTINCT tuotteen_toimittajat.toim_nimitys
                ORDER BY tuotteen_toimittajat.tunnus separator '/') AS toim_nimitys,
              group_concat(DISTINCT tuotteen_toimittajat.ostohinta
                ORDER BY tuotteen_toimittajat.tunnus separator '/') AS ostohinta,
              group_concat(DISTINCT tuotteen_toimittajat.tuotekerroin
                ORDER BY tuotteen_toimittajat.tunnus separator '/') AS tuotekerroin
              FROM tuotteen_toimittajat
              JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio
                AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
              WHERE tuotteen_toimittajat.yhtio IN ($yhtiot)
              AND tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
              $toimilisa";
    $result = pupe_query($query);
    $toimirow = mysql_fetch_array($result);

    // Kaunistellaan kenttiä
    if ($row["luontiaika"] == "0000-00-00 00:00:00") {
      $row["luontiaika"] = "";
    }

    if ($row['epakurantti25pvm'] == '0000-00-00') {
      $row['epakurantti25pvm'] = "";
    }

    if ($row['epakurantti50pvm'] == '0000-00-00') {
      $row['epakurantti50pvm'] = "";
    }

    if ($row['epakurantti75pvm'] == '0000-00-00') {
      $row['epakurantti75pvm'] = "";
    }

    if ($row['epakurantti100pvm'] == '0000-00-00') {
      $row['epakurantti100pvm'] = "";
    }

    // Hhaetaan abc luokille nimet
    $abcnimi = $ryhmanimet[$row["abcluokka"]];
    $abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
    $abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];

    $excelsarake = 0;

    // Kirjoitetaan itse riviä;
    if ($useampi_yhtio > 1) {
      $worksheet->write($excelrivi, $excelsarake++, $row["yhtio"]);
    }

    $worksheet->write($excelrivi, $excelsarake++, $row["tuoteno"]);
    $worksheet->write($excelrivi, $excelsarake++, $row["osasto"]);
    $worksheet->write($excelrivi, $excelsarake++, $row["try"]);
    $worksheet->write($excelrivi, $excelsarake++, $row["tuotemerkki"]);
    $worksheet->write($excelrivi, $excelsarake++, $row["tahtituote"]);
    $worksheet->write($excelrivi, $excelsarake++, $row["status"]);
    $worksheet->write($excelrivi, $excelsarake++, $abcnimi);
    $worksheet->write($excelrivi, $excelsarake++, $abcnimi2);
    $worksheet->write($excelrivi, $excelsarake++, $abcnimi3);
    $worksheet->writeDate($excelrivi, $excelsarake++, $row["luontiaika"]);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $row['halytysraja']);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $toimirow["osto_era"]);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $row['myynti_era']);
    $worksheet->write($excelrivi, $excelsarake++, $toimirow["toimittaja"]);
    $worksheet->write($excelrivi, $excelsarake++, $toimirow["toim_tuoteno"]);
    $worksheet->write($excelrivi, $excelsarake++, t_tuotteen_avainsanat($row, 'nimitys'));
    $worksheet->write($excelrivi, $excelsarake++, $toimirow["toim_nimitys"]);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $toimirow['ostohinta']);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $row['myyntihinta']);
    $worksheet->writeDate($excelrivi, $excelsarake++, $row["epakurantti25pvm"]);
    $worksheet->writeDate($excelrivi, $excelsarake++, $row["epakurantti50pvm"]);
    $worksheet->writeDate($excelrivi, $excelsarake++, $row["epakurantti75pvm"]);
    $worksheet->writeDate($excelrivi, $excelsarake++, $row["epakurantti100pvm"]);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $toimirow['tuotekerroin']);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $row["aleryhma"]);
    $worksheet->writeNumber($excelrivi, $excelsarake++, $row["kehahin"]);

    // Rullataan läpä maittain
    if ($varastot_maittain == "KYLLA") {
      foreach ($valitutmaat as $maa) {
        // Haetaan tuotteen myyntitiedot
        myynnit('', $maa);
        // Haetaan tuotteen saldotiedot
        saldot('', $maa);
        // Haetaan tuotteen ostotiedot
        ostot('', $maa);
      }
    }

    // Sitten rullataan läpi varastoittain
    if ($varastot_paikoittain == "KYLLA") {
      foreach ($valitutvarastot as $varastotunnus) {
        // Haetaan tuotteen myyntitiedot
        myynnit($varastotunnus);
        // Haetaan tuotteen saldotiedot
        saldot($varastotunnus);
        // Haetaan tuotteen ostotiedot
        ostot($varastotunnus);
      }
    }

    // Sitten vielä totalit
    myynnit();
    saldot();
    ostot();

    // Siirrytään seuraavalle riville
    $excelrivi++;

    $bar->increase(); // Calls the bar with every processed element
  }

    $excelnimi = $worksheet->close();

    echo "<br><br>";
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tallenna raportti (xlsx)").":</th>";
    echo "<form method = 'post' class = 'multisubmit'>";
    echo "<input type = 'hidden' name = 'tee' value = 'lataa_tiedosto'>";
    echo "<input type = 'hidden' name = 'kaunisnimi' value = 'Ostoehdotus.xlsx'>";
    echo "<input type = 'hidden' name = 'tmpfilenimi' value = '$excelnimi'>";
    echo "<td class = 'back'><input type = 'submit' value = '".t("Tallenna")."'></td>";
    echo "</tr>";
    echo "</form>";
    echo "</table><br>";
}

// Näytetään käyttöliittymä..
if ($tee == "" or !isset($ehdotusnappi)) {

  $abcnimi = $ryhmanimet[$abcrajaus];

  echo "<form method = 'post' autocomplete = 'off'>";
  echo "<input type = 'hidden' name = 'tee' value = 'RAPORTOI'>";
  echo "<table>";

  echo "<tr><th>".t("Osasto")."</th><td colspan = '3'>";

  // Tehdään avainsana query
  $sresult = t_avainsana("OSASTO", "", "", $yhtiot);

  echo "<select name = 'osasto'>";
  echo "<option value = ''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($osasto == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value = '$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
  }
  echo "</select>";

  echo "</td></tr>
      <tr><th>".t("Tuoteryhmä")."</th><td colspan = '3'>";

  // Tehdään avainsana query
  $sresult = t_avainsana("TRY", "", "", $yhtiot);

  echo "<select name = 'tuoryh'>";
  echo "<option value = ''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($tuoryh == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value = '$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
  }
  echo "</select>";

  echo "</td></tr>
      <tr><th>".t("Tuotemerkki")."</th><td colspan = '3'>";

  // Tehdään osasto & tuoteryhmä pop-upit
  $query = "SELECT DISTINCT tuotemerkki
            FROM tuote
            WHERE yhtio IN ($yhtiot)
            AND tuotemerkki != ''
            ORDER BY tuotemerkki";
  $sresult = pupe_query($query);

  echo "<select name = 'tuotemerkki'>";
  echo "<option value = ''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($tuotemerkki == $srow["tuotemerkki"]) {
      $sel = "selected";
    }
    echo "<option value = '$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
  }
  echo "</select>";

  echo "</td></tr>";

  echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

  echo "<select name = 'abcrajaus' onchange = 'submit()'>";
  echo "<option  value = ''>".t("Valitse")."</option>";

  $teksti = "";
  for ($i = 0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) {
      $teksti = t("ja paremmat");
    }

    if ($org_rajaus == "{$i}##TM") {
      $selabc = "SELECTED";
    }

    echo "<option  value = '$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i = 0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) {
      $teksti = t("ja paremmat");
    }

    if ($org_rajaus == "{$i}##TK") {
      $selabc = "SELECTED";
    }

    echo "<option  value = '$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i = 0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) {
      $teksti = t("ja paremmat");
    }

    if ($org_rajaus == "{$i}##TR") {
      $selabc = "SELECTED";
    }

    echo "<option  value = '$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i = 0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) {
      $teksti = t("ja paremmat");
    }

    if ($org_rajaus == "{$i}##TP") {
      $selabc = "SELECTED";
    }

    echo "<option value = '$i##TP' $selabc>";
    echo t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti";
    echo "</option>";
  }

  echo "</select>";

  list($abcrajaus, $abcrajaustapa) = explode("##", $abcrajaus);

  echo "<tr>";
  echo "<th>".t("Toimittaja")."</th>";
  echo "<td colspan = '3'><input type = 'text' size = '20' name = 'ytunnus' value = '$ytunnus'></td></tr>";
  echo "<input type = 'hidden' name = 'edytunnus' value = '$ytunnus'>";
  echo "<input type = 'hidden' name = 'toimittajaid' value = '$toimittajaid'>";

  echo "</table><table><br>";

  echo "<tr>";
  echo "<th></th>";
  echo "<th colspan = '3'>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>";
  echo "<th></th><th colspan = '3'>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Kausi 1")."</th>";
  echo "<td><input type = 'text' name = 'ppa1' value = '$ppa1' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kka1' value = '$kka1' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vva1' value = '$vva1' size = '5'></td>";
  echo "<td class = 'back'>&nbsp;-&nbsp;</td>";
  echo "<td><input type = 'text' name = 'ppl1' value = '$ppl1' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kkl1' value = '$kkl1' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vvl1' value = '$vvl1' size = '5'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Kausi 2")."</th>";
  echo "<td><input type = 'text' name = 'ppa2' value = '$ppa2' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kka2' value = '$kka2' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vva2' value = '$vva2' size = '5'></td>";
  echo "<td class = 'back'>&nbsp;-&nbsp;</td>";
  echo "<td><input type = 'text' name = 'ppl2' value = '$ppl2' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kkl2' value = '$kkl2' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vvl2' value = '$vvl2' size = '5'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Kausi 3")."</th>";
  echo "<td><input type = 'text' name = 'ppa3' value = '$ppa3' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kka3' value = '$kka3' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vva3' value = '$vva3' size = '5'></td>";
  echo "<td class = 'back'>&nbsp;-&nbsp;</td>";
  echo "<td><input type = 'text' name = 'ppl3' value = '$ppl3' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kkl3' value = '$kkl3' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vvl3' value = '$vvl3' size = '5'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Kausi 4")."</th>";
  echo "<td><input type = 'text' name = 'ppa4' value = '$ppa4' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kka4' value = '$kka4' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vva4' value = '$vva4' size = '5'></td>";
  echo "<td class = 'back'>&nbsp;-&nbsp;</td>";
  echo "<td><input type = 'text' name = 'ppl4' value = '$ppl4' size = '5'></td>";
  echo "<td><input type = 'text' name = 'kkl4' value = '$kkl4' size = '5'></td>";
  echo "<td><input type = 'text' name = 'vvl4' value = '$vvl4' size = '5'></td>";
  echo "</tr>";

  echo "</table><table><br>";

  $chk = "";
  if ($eliminoi != "") {
    $chk = "checked";
  }
  echo "<tr><th>";
  echo t("Älä huomioi konsernimyyntiä");
  echo "</th><td colspan = '3'><input type = 'checkbox' name = 'eliminoi' $chk></td></tr>";

  $chk = "";
  if ($erikoisvarastot != "") {
    $chk = "checked";
  }
  echo "<tr><th>";
  echo t("Älä huomioi erikoisvarastoja");
  echo "</th><td colspan = '3'><input type = 'checkbox' name = 'erikoisvarastot' $chk></td></tr>";

  $chk = "";
  if ($poistetut != "") {
    $chk = "checked";
  }
  echo "<tr><th>";
  echo t("Älä näytä poistettuja tuotteita");
  echo "</th><td colspan = '3'><input type = 'checkbox' name = 'poistetut' $chk></td></tr>";

  $chk = "";
  if ($poistuva != "") {
    $chk = "checked";
  }
  echo "<tr><th>";
  echo t("Älä näytä poistuvia tuotteita");
  echo "</th><td colspan = '3'><input type = 'checkbox' name = 'poistuva' $chk></td></tr>";

  $chk = "";
  if ($eihinnastoon != "") {
    $chk = "checked";
  }
  echo "<tr><th>";
  echo t("Älä näytä tuotteita joita ei näytetä hinnastossa");
  echo "</th><td colspan = '3'><input type = 'checkbox' name = 'eihinnastoon' $chk></td></tr>";

  if ($abcrajaus != "") {
    echo "<tr><td class = 'back'><br></td></tr>";
    echo "<tr><th colspan = '4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

    $chk = "";
    if ($eiuusia != "") {
      $chk = "checked";
    }
    echo "<tr><th>";
    echo t("Älä listaa 12kk sisällä perustettuja tuotteita");
    echo "</th><td colspan = '3'><input type = 'checkbox' name = 'eiuusia' $chk></td></tr>";

    $chk = "";
    if ($vainuudet != "") {
      $chk = "checked";
    }
    echo "<tr><th>";
    echo t("Listaa vain 12kk sisällä perustetut tuotteet");
    echo "</th><td colspan = '3'><input type = 'checkbox' name = 'vainuudet' $chk></td></tr>";
  }

  echo "</table><table><br>";

  // Yhtiövalinnat
  $query = "SELECT DISTINCT yhtio, nimi
             FROM yhtio
             WHERE konserni = '$yhtiorow[konserni]'
             AND konserni   != ''";
  $presult = pupe_query($query);

  $useampi_yhtio = 0;

  if (mysql_num_rows($presult) > 0) {

    echo "<tr><th>".t("Huomioi yhtiön saldot, myynnit ja ostot").":</th></tr>";
    $yhtiot = "";

    while ($prow = mysql_fetch_array($presult)) {

      $chk = "";
      if (is_array($valitutyhtiot) AND in_array($prow["yhtio"], $valitutyhtiot) != '') {
        $chk = "CHECKED";
        $yhtiot .= "'$prow[yhtio]',";
        $useampi_yhtio++;
      }
      elseif ($prow["yhtio"] == $kukarow["yhtio"]) {
        $chk = "CHECKED";
      }

      echo "<tr><td>";
      echo "<input type = 'checkbox' name = 'valitutyhtiot[]'
              value = '$prow[yhtio]' $chk onClick = 'submit();'> $prow[nimi]";
      echo "</td></tr>";
    }

    $yhtiot = substr($yhtiot, 0, -1);

    if ($yhtiot == "") {
      $yhtiot = "'$kukarow[yhtio]'";
    }

    echo "</table><table><br>";
  }

  // Katsotaan onko firmalla varastoja useassa maassa
  $query = "SELECT DISTINCT maa
            FROM varastopaikat
            WHERE maa != ''
            AND yhtio  IN ($yhtiot)
            ORDER BY yhtio, maa";
  $vtresult = pupe_query($query);

  // Useampi maa löytyy, annetaan mahdollisuus tutkailla saldoja per maa
  if (mysql_num_rows($vtresult) > 1) {

    echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot per varaston maa:")."</th></tr>";

    while ($vrow = mysql_fetch_array($vtresult)) {

      $chk = "";
      if (is_array($valitutmaat) AND in_array($vrow["maa"], $valitutmaat) != '') {
        $chk = "CHECKED";
      }

      echo "<tr><td>";
      echo "<input type = 'checkbox' name = 'valitutmaat[]' value = '$vrow[maa]' $chk>$vrow[maa] - ";
      echo maa($vrow["maa"]);
      echo "</td></tr>";
    }

    echo "</table><table><br>";
  }

  // Valitaan varastot joiden saldot huomioidaan
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio IN ($yhtiot)
            ORDER BY yhtio, tyyppi, nimitys";
  $vtresult = pupe_query($query);

  $vlask = 0;

  if (mysql_num_rows($vtresult) > 0) {

    echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot varastoittain:")."</th></tr>";

    while ($vrow = mysql_fetch_array($vtresult)) {

      $chk = "";
      if (is_array($valitutvarastot) AND in_array($vrow["tunnus"], $valitutvarastot) != '') {
        $chk = "CHECKED";
      }

      echo "<tr><td><input type = 'checkbox' name = 'valitutvarastot[]' value = '$vrow[tunnus]' $chk>";

      if ($useampi_yhtio > 1) {
        $query = "SELECT nimi
                  FROM yhtio
                  WHERE yhtio = '$vrow[yhtio]'";
        $yhtres = pupe_query($query);
        $yhtrow = mysql_fetch_array($yhtres);
        echo "$yhtrow[nimi]: ";
      }

      echo "$vrow[nimitys] ";

      if ($vrow["tyyppi"] != "") {
        echo " *$vrow[tyyppi]* ";
      }

      if ($useampi_maa == 1) {
        echo "(".maa($vrow["maa"]).")";
      }

      echo "</td></tr>";
    }
  }
  else {
    echo "<font class = 'error'>".t("Yhtään varastoa ei löydy, raporttia ei voida ajaa")."!</font>";
    exit;
  }

  echo "</table>";
  echo "<br><input type = 'submit' name = 'ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";
}

require "inc/footer.inc";
