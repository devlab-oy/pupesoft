<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 2;

// Ei käytetä pakkausta
$compression = FALSE;

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

$onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikkares) > 0) ? TRUE : FALSE;

echo "<font class='head'>".t("Hälytysraportti")."</font><hr>";

$org_rajaus = $abcrajaus;
list($abcrajaus, $abcrajaustapa) = explode("##", $abcrajaus);

if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " + tilausrivi.varattu";
}
else {
  $lisavarattu = "";
}

// Tarvittavat päivämäärät
if (!isset($kka1)) $kka1 = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva1)) $vva1 = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa1)) $ppa1 = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl1)) $kkl1 = date("m");
if (!isset($vvl1)) $vvl1 = date("Y");
if (!isset($ppl1)) $ppl1 = date("d");

if (!isset($kka2)) $kka2 = date("m", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($vva2)) $vva2 = date("Y", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($ppa2)) $ppa2 = date("d", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($kkl2)) $kkl2 = date("m");
if (!isset($vvl2)) $vvl2 = date("Y");
if (!isset($ppl2)) $ppl2 = date("d");

if (!isset($kka3)) $kka3 = date("m", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($vva3)) $vva3 = date("Y", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($ppa3)) $ppa3 = date("d", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($kkl3)) $kkl3 = date("m");
if (!isset($vvl3)) $vvl3 = date("Y");
if (!isset($ppl3)) $ppl3 = date("d");

if (!isset($kka4)) $kka4 = date("m", mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($vva4)) $vva4 = date("Y", mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($ppa4)) $ppa4 = date("d", mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($kkl4)) $kkl4 = date("m");
if (!isset($vvl4)) $vvl4 = date("Y");
if (!isset($ppl4)) $ppl4 = date("d");

// Edellisen vuoden vastaavat kaudet
$kka1ed = date("m", mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$vva1ed = date("Y", mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$ppa1ed = date("d", mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$kkl1ed = date("m", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$vvl1ed = date("Y", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$ppl1ed = date("d", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));

$kka2ed = date("m", mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$vva2ed = date("Y", mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$ppa2ed = date("d", mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$kkl2ed = date("m", mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
$vvl2ed = date("Y", mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
$ppl2ed = date("d", mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));

$kka3ed = date("m", mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$vva3ed = date("Y", mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$ppa3ed = date("d", mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$kkl3ed = date("m", mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
$vvl3ed = date("Y", mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
$ppl3ed = date("d", mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));

$kka4ed = date("m", mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$vva4ed = date("Y", mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$ppa4ed = date("d", mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$kkl4ed = date("m", mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
$vvl4ed = date("Y", mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
$ppl4ed = date("d", mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));

//katotaan pienin alkupvm ja isoin loppupvm
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

if ($apaiva1 <= $apienin and $apaiva1 != 19700101) $apienin = $apaiva1;
if ($apaiva2 <= $apienin and $apaiva2 != 19700101) $apienin = $apaiva2;
if ($apaiva3 <= $apienin and $apaiva3 != 19700101) $apienin = $apaiva3;
if ($apaiva4 <= $apienin and $apaiva4 != 19700101) $apienin = $apaiva4;
if ($apaiva5 <= $apienin and $apaiva5 != 19700101) $apienin = $apaiva5;
if ($apaiva6 <= $apienin and $apaiva6 != 19700101) $apienin = $apaiva6;
if ($apaiva7 <= $apienin and $apaiva7 != 19700101) $apienin = $apaiva7;
if ($apaiva8 <= $apienin and $apaiva8 != 19700101) $apienin = $apaiva8;

if ($lpaiva1 >= $lsuurin and $lpaiva1 != 19700101) $lsuurin = $lpaiva1;
if ($lpaiva2 >= $lsuurin and $lpaiva2 != 19700101) $lsuurin = $lpaiva2;
if ($lpaiva3 >= $lsuurin and $lpaiva3 != 19700101) $lsuurin = $lpaiva3;
if ($lpaiva4 >= $lsuurin and $lpaiva4 != 19700101) $lsuurin = $lpaiva4;
if ($lpaiva5 >= $lsuurin and $lpaiva5 != 19700101) $lsuurin = $lpaiva5;
if ($lpaiva6 >= $lsuurin and $lpaiva6 != 19700101) $lsuurin = $lpaiva6;
if ($lpaiva7 >= $lsuurin and $lpaiva7 != 19700101) $lsuurin = $lpaiva7;
if ($lpaiva8 >= $lsuurin and $lpaiva8 != 19700101) $lsuurin = $lpaiva8;

if ($apienin == 99999999 and $lsuurin == 0) {
  $apienin = $lsuurin = date('Ymd'); // jos mitään ei löydy niin NOW molempiin. :)
}

$apvm = substr($apienin, 0, 4)."-".substr($apienin, 4, 2)."-".substr($apienin, 6, 2);
$lpvm = substr($lsuurin, 0, 4)."-".substr($lsuurin, 4, 2)."-".substr($lsuurin, 6, 2);

// Tulostettavat sarakkeet
$sarakkeet = array();

if (!isset($valitut))       $valitut = array();
if (!isset($rappari))       $rappari = "";
if (!isset($osasto))        $osasto = "";
if (!isset($tuoryh))        $tuoryh = "";
if (!isset($tuotemerkki))   $tuotemerkki = "";
if (!isset($asiakasosasto)) $asiakasosasto = "";
if (!isset($toimipaikka))   $toimipaikka = "";

//Voidaan tarvita jotain muuttujaa täältä
if (isset($muutparametrit)) {
  list($temp_osasto, $temp_tuoryh, $temp_ytunnus, $temp_tuotemerkki, $temp_asiakasosasto, $temp_asiakasno, $temp_toimittaja) = explode('#', $muutparametrit);
}

$sarakkeet["SARAKE1"]   = t("osasto")."\t";
$sarakkeet["SARAKE2"]   = t("tuoteryhma")."\t";
$sarakkeet["SARAKE3"]   = t("tuotemerkki")."\t";
$sarakkeet["SARAKE3B"]  = t("malli")."\t";
$sarakkeet["SARAKE3C"]  = t("mallitarkenne")."\t";
$sarakkeet["SARAKE4"]   = t("tahtituote")."\t";
$sarakkeet["SARAKE4B"]  = t("status")."\t";
$sarakkeet["SARAKE4C"]  = t("abc")."\t";
$sarakkeet["SARAKE4CA"] = t("abc osasto")."\t";
$sarakkeet["SARAKE4CB"] = t("abc try")."\t";
$sarakkeet["SARAKE4D"]  = t("luontiaika")."\t";
$sarakkeet["SARAKE5"]   = t("saldo")."\t";
$sarakkeet["SARAKE6"]   = t("halytysraja")."\t";
$sarakkeet["SARAKE6B"]  = t("tilausmaara")."\t";
$sarakkeet["SARAKE7"]   = t("tilauksessa")."\t";
$sarakkeet["SARAKE7B"]  = t("valmistuksessa")."\t";
$sarakkeet["SARAKE8"]   = t("ennpois")."\t";
$sarakkeet["SARAKE9"]   = t("jt")."\t";

$ehd_kausi_o1 = "1";
$ehd_kausi_o2 = "3";
$ehd_kausi_o3 = "4";

if ($valitut["KAUSI1"] != '') {
  list($al, $lo) = explode("##", $valitut["KAUSI1"]);

  $ehd_kausi_o1  = $lo;
}
if ($valitut["KAUSI2"] != '') {
  list($al, $lo) = explode("##", $valitut["KAUSI2"]);

  $ehd_kausi_o2  = $lo;
}
if ($valitut["KAUSI3"] != '') {
  list($al, $lo) = explode("##", $valitut["KAUSI3"]);

  $ehd_kausi_o3  = $lo;
}

$sarakkeet["SARAKE10"]  = t("Ostoehdotus A:")." $ehd_kausi_o1\t";
$sarakkeet["SARAKE11"]  = t("Ostoehdotus B:")." $ehd_kausi_o2\t";
$sarakkeet["SARAKE12"]  = t("Ostoehdotus C:")." $ehd_kausi_o3\t";

$sarakkeet["SARAKE12B"] = t("Ostoehdotus status")."\t";
$sarakkeet["SARAKE12C"] = t("Viimeinen hankintapäivä")."\t";
$sarakkeet["SARAKE12D"] = t("Viimeinen myyntipäivä")."\t";

$sarakkeet["SARAKE13"]  = t("ostettava haly")."\t";
$sarakkeet["SARAKE13B"] = t("ostettava tilausmaara")."\t";
$sarakkeet["SARAKE14"]  = t("osto_era")."\t";
$sarakkeet["SARAKE15"]  = t("myynti_era")."\t";
$sarakkeet["SARAKE16"]  = t("toimittaja")."\t";
$sarakkeet["SARAKE17"]  = t("toim_tuoteno")."\t";
$sarakkeet["SARAKE18"]  = t("nimitys")."\t";
$sarakkeet["SARAKE18B"] = t("toim_nimitys")."\t";
$sarakkeet["SARAKE18C"] = t("kuvaus")."\t";

$sarakkeet["SARAKE18D"] = t("lyhytkuvaus")."\t";
$sarakkeet["SARAKE18E"] = t("tuotekorkeus")."\t";
$sarakkeet["SARAKE18F"] = t("tuoteleveys")."\t";
$sarakkeet["SARAKE18G"] = t("tuotesyvyys")."\t";
$sarakkeet["SARAKE18H"] = t("tuotemassa")."\t";
$sarakkeet["SARAKE18I"] = t("hinnastoon")."\t";
$sarakkeet["SARAKE18J"]  = t("EANkoodi")."\t";

$sarakkeet["SARAKE19"]  = t("ostohinta")."\t";
$sarakkeet["SARAKE20"]  = t("myyntihinta")."\t";
$sarakkeet["SARAKE20Z"] = t("epakurantti25pvm")."\t";
$sarakkeet["SARAKE21"]  = t("epakurantti50pvm")."\t";
$sarakkeet["SARAKE21B"] = t("epakurantti75pvm")."\t";
$sarakkeet["SARAKE22"]  = t("epakurantti100pvm")."\t";
$sarakkeet["SARAKE23"]  = t("oletussaldo")."\t";
$sarakkeet["SARAKE24"]  = t("hyllypaikka")."\t";

if ($tee == "RAPORTOI" and isset($RAPORTOI)) {
  $kausi1 = "($ppa1.$kka1.$vva1-$ppl1.$kkl1.$vvl1)";
  $kausi2 = "($ppa2.$kka2.$vva2-$ppl2.$kkl2.$vvl2)";
  $kausi3 = "($ppa3.$kka3.$vva3-$ppl3.$kkl3.$vvl3)";
  $kausi4 = "($ppa4.$kka4.$vva4-$ppl4.$kkl4.$vvl4)";

  $kausied1 = "($ppa1ed.$kka1ed.$vva1ed-$ppl1ed.$kkl1ed.$vvl1ed)";
  $kausied2 = "($ppa2ed.$kka2ed.$vva2ed-$ppl2ed.$kkl2ed.$vvl2ed)";
  $kausied3 = "($ppa3ed.$kka3ed.$vva3ed-$ppl3ed.$kkl3ed.$vvl3ed)";
  $kausied4 = "($ppa4ed.$kka4ed.$vva4ed-$ppl4ed.$kkl4ed.$vvl4ed)";
}
else {
  $kausi1 = t("Kausi 1");
  $kausi2 = t("Kausi 2");
  $kausi3 = t("Kausi 3");
  $kausi4 = t("Kausi 4");

  $kausied1 = t("Ed. Kausi 1");
  $kausied2 = t("Ed. Kausi 2");
  $kausied3 = t("Ed. Kausi 3");
  $kausied4 = t("Ed. Kausi 4");
}

$sarakkeet["SARAKE25"] = t("puutteet")." $kausi1\t";
$sarakkeet["SARAKE26"] = t("puutteet")." $kausi2\t";
$sarakkeet["SARAKE27"] = t("puutteet")." $kausi3\t";
$sarakkeet["SARAKE28"] = t("puutteet")." $kausi4\t";

//Myydyt kappaleet
$sarakkeet["SARAKE29"] = t("myynti")." $kausi1\t";
$sarakkeet["SARAKE30"] = t("myynti")." $kausi2\t";
$sarakkeet["SARAKE31"] = t("myynti")." $kausi3\t";
$sarakkeet["SARAKE32"] = t("myynti")." $kausi4\t";

$sarakkeet["SARAKE33"] = t("myynti")." $kausied1\t";
$sarakkeet["SARAKE34"] = t("myynti")." $kausied2\t";
$sarakkeet["SARAKE35"] = t("myynti")." $kausied3\t";
$sarakkeet["SARAKE36"] = t("myynti")." $kausied4\t";

//Kulutetut kappaleet
$sarakkeet["SARAKE29K"] = t("kulutus")." $kausi1\t";
$sarakkeet["SARAKE30K"] = t("kulutus")." $kausi2\t";
$sarakkeet["SARAKE31K"] = t("kulutus")." $kausi3\t";
$sarakkeet["SARAKE32K"] = t("kulutus")." $kausi4\t";
$sarakkeet["SARAKE33K"] = t("kulutus")." $kausied1\t";
$sarakkeet["SARAKE34K"] = t("kulutus")." $kausied2\t";
$sarakkeet["SARAKE35K"] = t("kulutus")." $kausied3\t";
$sarakkeet["SARAKE36K"] = t("kulutus")." $kausied4\t";

$sarakkeet["SARAKE37"] = t("Kate")." $yhtiorow[valkoodi] $kausi1\t";
$sarakkeet["SARAKE38"] = t("Kate")." $yhtiorow[valkoodi] $kausi2\t";
$sarakkeet["SARAKE39"] = t("Kate")." $yhtiorow[valkoodi] $kausi3\t";
$sarakkeet["SARAKE40"] = t("Kate")." $yhtiorow[valkoodi] $kausi4\t";
$sarakkeet["SARAKE41"] = t("Kate %")." $kausi1\t";
$sarakkeet["SARAKE42"] = t("Kate %")." $kausi2\t";
$sarakkeet["SARAKE43"] = t("Kate %")." $kausi3\t";
$sarakkeet["SARAKE44"] = t("Kate %")." $kausi4\t";

$sarakkeet["SARAKE45"]   = t("tuotekerroin")."\t";
$sarakkeet["SARAKE46"]   = t("ennakkotilauksessa")."\t";
$sarakkeet["SARAKE47"]   = t("aleryhmä")."\t";
$sarakkeet["SARAKE47B"] = t("kehahin")."\t";

if ($temp_asiakasosasto != '' or $asiakasosasto != '') {
  $sarakkeet["SARAKE48"] = t("myynti asiakasosasto")." $asiakasosasto $kausi1\t";
  $sarakkeet["SARAKE49"] = t("myynti asiakasosasto")." $asiakasosasto $kausi2\t";
  $sarakkeet["SARAKE50"] = t("myynti asiakasosasto")." $asiakasosasto $kausi3\t";
  $sarakkeet["SARAKE51"] = t("myynti asiakasosasto")." $asiakasosasto $kausi4\t";
}
if ($temp_asiakasno != '' or $asiakasno != '') {
  $sarakkeet["SARAKE52"] = t("myynti asiakas")." $asiakasno $kausi1\t";
  $sarakkeet["SARAKE53"] = t("myynti asiakas")." $asiakasno $kausi2\t";
  $sarakkeet["SARAKE54"] = t("myynti asiakas")." $asiakasno $kausi3\t";
  $sarakkeet["SARAKE55"] = t("myynti asiakas")." $asiakasno $kausi4\t";
}

$sarakkeet["SARAKE70"] = t("kampanjatuotteet")." $kausi1\t";
$sarakkeet["SARAKE71"] = t("kampanjatuotteet")." $kausi2\t";
$sarakkeet["SARAKE72"] = t("kampanjatuotteet")." $kausi3\t";
$sarakkeet["SARAKE73"] = t("kampanjatuotteet")." $kausi4\t";

$sarakkeet["SARAKE74"] = t("sampletuotteet")." $kausi1\t";
$sarakkeet["SARAKE75"] = t("sampletuotteet")." $kausi2\t";
$sarakkeet["SARAKE76"] = t("sampletuotteet")." $kausi3\t";
$sarakkeet["SARAKE77"] = t("sampletuotteet")." $kausi4\t";

if (table_exists("yhteensopivuus_rekisteri")) {
  $query = "SELECT count(*) kpl
            FROM yhteensopivuus_rekisteri
            WHERE yhtio = '$kukarow[yhtio]'";
  $res = pupe_query($query);
  $row = mysql_fetch_assoc($res);

  if ($row["kpl"] > 0) {
    $sarakkeet["SARAKE64"] = "Rekisteröidyt kpl\t";
  }
}

//  Haetaan kaikki varastot ja luodaan kysely paljonko ko. varastoon on tilattu tavaraa..
$varastolisa = "";

if ($valitut["OSTOTVARASTOITTAIN"] != "") {

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY tyyppi, nimitys";
  $osvres = pupe_query($query);

  $abuArray=array();

  while ($vrow = mysql_fetch_assoc($osvres)) {
    $varastolisa .= ", sum(if (tyyppi='O' and
              concat(rpad(upper('$vrow[alkuhyllyalue]'),  5, '0'),lpad(upper('$vrow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0')) and
              concat(rpad(upper('$vrow[loppuhyllyalue]'), 5, '0'),lpad(upper('$vrow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
            , varattu, 0)) tilattu_$vrow[tunnus] ";

    $sarakkeet["SARAKE65#".$vrow["tunnus"]] = t("tilattu kpl - $vrow[nimitys]")."\t";
    $abuArray["SARAKE65#".$vrow["tunnus"]] = "SARAKE65#".$vrow["tunnus"];
  }

  // Liitetään oletus jotta summat voisi täsmätä..
  $varastolisa .= ", sum(if (tilausrivi.tyyppi='O' and tilausrivi.hyllyalue = '' , tilausrivi.varattu, 0)) tilattu_oletus ";

  $sarakkeet["SARAKE65#oletus"] = t("tilattu kpl - varastoa ei annettu")."\t";
  $abuArray["SARAKE65#oletus"] = "SARAKE65#oletus";

  //  karseeta haetaan offset valitut arrayksi jotta osataan siirtää nämä tiedot oikeaan paikkaan..
  $i = 0;

  foreach ($valitut as $key => $value) {
    if (in_array($key, array("SARAKE56", "SARAKE57", "SARAKE58", "SARAKE59", "SARAKE60", "SARAKE61", "SARAKE62", "SARAKE63"))) {
      $offset = $i;
      echo "löydettiin offset ($offset)<br>";
      break;
    }
    $i++;
  }
  array_splice($valitut, $offset, 0, $abuArray);
}

$sarakkeet["SARAKE56"] = t("Korvaavat Tuoteno")."\t";
$sarakkeet["SARAKE57"] = t("Korvaavat Saldo")."\t";
$sarakkeet["SARAKE58"] = t("Korvaavat Ennpois")."\t";
$sarakkeet["SARAKE59"] = t("Korvaavat Tilauksessa")."\t";
$sarakkeet["SARAKE60"] = t("Korvaavat Myyty")." $kausi1\t";
$sarakkeet["SARAKE61"] = t("Korvaavat Myyty")." $kausi2\t";
$sarakkeet["SARAKE62"] = t("Korvaavat Myyty")." $kausi3\t";
$sarakkeet["SARAKE63"] = t("Korvaavat Myyty")." $kausi4\t";

//Jos halutaan tallentaa päivämäärät profiilin taakse
if ($valitut["TALLENNAPAIVAM"] == "TALLENNAPAIVAM") {
  //Tehdään päivämääristä tallennettavia
  $paivamaarat = array('ppa1', 'kka1', 'vva1', 'ppl1', 'kkl1', 'vvl1', 'ppa2', 'kka2', 'vva2', 'ppl2', 'kkl2', 'vvl2', 'ppa3', 'kka3', 'vva3', 'ppl3', 'kkl3', 'vvl3', 'ppa4', 'kka4', 'vva4', 'ppl4', 'kkl4', 'vvl4');
  foreach ($paivamaarat as $paiva) {
    $valitut[] = "PAIVAM##".$paiva."##".${$paiva};
  }
}

// Tässä luodaan uusi raporttiprofiili
if ($tee == "RAPORTOI" and $uusirappari != '') {

  $rappari = $kukarow["kuka"]."##".$uusirappari;

  foreach ($valitut as $val) {
    $query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='HALYRAP', selite='$rappari', selitetark='$val'";
    $res = pupe_query($query, $masterlink);
  }
}

//Ajetaan itse raportti
if ($tee == "RAPORTOI" and isset($RAPORTOI)) {

  if ($rappari != '') {
    $query = "DELETE FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='HALYRAP' and selite='$rappari'";
    $res = pupe_query($query, $masterlink);

    foreach ($valitut as $val) {
      $query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='HALYRAP', selite='$rappari', selitetark='$val'";
      $res = pupe_query($query, $GLOBALS["masterlink"]);
    }
  }

  if ($tuoryh != '') {
    $sresult = t_avainsana("TRY", "", "and avainsana.selite  = '$tuoryh'");
    $srow = mysql_fetch_assoc($sresult);
  }
  if ($osasto != '') {
    $sresult = t_avainsana("OSASTO", "", "and avainsana.selite  = '$osasto'");
    $trow = mysql_fetch_assoc($sresult);
  }
  if ($toimittajaid != '') {
    $query = "SELECT nimi
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]' and tunnus='$toimittajaid'";
    $sresult = pupe_query($query);
    $trow1 = mysql_fetch_assoc($sresult);
  }
  if ($asiakasid != '') {
    $query = "SELECT nimi
              FROM asiakas
              WHERE yhtio = '$kukarow[yhtio]' and tunnus='$asiakasid'";
    $sresult = pupe_query($query);
    $trow2 = mysql_fetch_assoc($sresult);
  }

  $abcnimi = $ryhmanimet[$abcrajaus];

  echo "  <table>
      <tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
      <tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
      <tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
      <tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
      <tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
      <tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
      <tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>
      <tr><th>".t("JT")."</th><td colspan='3'>$KAIKKIJT</td></tr>";

  if ($onkolaajattoimipaikat) {

    if ("{$toimipaikka}" == "kaikki") {
      $toimipaikka_nimi = t("Kaikki toimipaikat");
    }
    elseif ($toimipaikka > 0) {
      $toimipaikka_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $toimipaikka);
      $toimipaikka_row = mysql_fetch_assoc($toimipaikka_res);

      $toimipaikka_nimi = $toimipaikka_row['nimi'];
    }
    else {
      $toimipaikka_nimi = t("Ei toimipaikkaa");
    }

    echo "<tr><th>", t("Toimipaikka"), "</th><td colspan='3'>{$toimipaikka_nimi}</td></tr>";
  }

  echo "  </table><br>";
  flush();

  $lisaa  = ""; // tuote-rajauksia
  $lisaa2 = ""; // toimittaja-rajauksia

  if ($osasto != '') {
    $lisaa .= " and tuote.osasto = '$osasto' ";
  }
  if ($tuoryh != '') {
    $lisaa .= " and tuote.try = '$tuoryh' ";
  }
  if ($tuotemerkki != '') {
    $lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
  }
  if ($valitut["poistetut"] != '') {
    $lisaa .= " and tuote.status != 'P' ";
  }
  if ($valitut["poistuvat"] != '') {
    $lisaa .= " and tuote.status != 'X' ";
  }
  if ($valitut["ehdokas"] != '') {
    $lisaa .= " and tuote.status != 'E' ";
  }
  if ($valitut["ei_ostoehd"] != '') {
    $lisaa .= " and tuote.ostoehdotus != 'E' ";
  }
  if ($valitut["EIHINNASTOON"] != '') {
    $lisaa .= " and tuote.hinnastoon != 'E' ";
  }
  if ($valitut["EIVARASTOITAVA"] != '') {
    $lisaa .= " and tuote.status != 'T' ";
  }
  // Listaa vain äskettäin perustetut tuotteet:
  if ($valitut["VAINUUDETTUOTTEET"] != '') {
    $lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
  }
  // Älä listaa äskettäin perustettuja tuotteita:
  if ($valitut["UUDETTUOTTEET"] != '') {
    $lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
  }

  if (isset($nayta_vain_ykkostoimittaja)) {
    $toimittajaidlisa = $toimittajaid != '' ? " and paatoimittaja.liitostunnus = '{$toimittajaid}' " : "";

    $lisaa2 .= " JOIN tuotteen_toimittajat paatoimittaja ON paatoimittaja.yhtio=tuote.yhtio and paatoimittaja.tuoteno=tuote.tuoteno and paatoimittaja.liitostunnus = (select liitostunnus from tuotteen_toimittajat where yhtio = tuote.yhtio and tuoteno = tuote.tuoteno {$toimittajaidlisa} ORDER BY if (jarjestys = 0, 9999, jarjestys) LIMIT 1)";
  }
  elseif ($toimittajaid != '') {
    $lisaa2 .= " JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$toimittajaid'";
  }

  //Yhtiövalinnat
  $query  = "SELECT distinct yhtio, nimi
             from yhtio
             where konserni = '$yhtiorow[konserni]' and konserni != ''";
  $presult = pupe_query($query);

  $yhtiot   = "";
  $konsyhtiot = "";

  if (mysql_num_rows($presult) > 0) {
    while ($prow = mysql_fetch_assoc($presult)) {
      if ($valitut["YHTIO##$prow[yhtio]"] == "YHTIO##".$prow["yhtio"]) {
        $yhtiot .= "'".$prow["yhtio"]."',";
      }
      $konsyhtiot .= "'".$prow["yhtio"]."',";
    }

    $yhtiot = substr($yhtiot, 0, -1);
    $yhtiot = " yhtio in ($yhtiot) ";

    $konsyhtiot = substr($konsyhtiot, 0, -1);
    $konsyhtiot = " yhtio in ($konsyhtiot) ";
  }
  else {
    $yhtiot = "'".$kukarow["yhtio"]."'";
    $yhtiot = " yhtio in ($yhtiot) ";

    $konsyhtiot = "'".$kukarow["yhtio"]."'";
    $konsyhtiot = " yhtio in ($konsyhtiot) ";
  }

  //Katsotaan valitut varastot
  $query = "SELECT *
            FROM varastopaikat
            WHERE $konsyhtiot
            ORDER BY yhtio, tyyppi, nimitys";
  $vtresult = pupe_query($query);

  $varastot       = "";
  $varastot_yhtiot   = "";

  while ($vrow = mysql_fetch_assoc($vtresult)) {
    if ($valitut["VARASTO##$vrow[tunnus]"] == "VARASTO##".$vrow["tunnus"]) {
      $varastot .= "'".$vrow["tunnus"]."',";
      $varastot_yhtiot .= "'".$vrow["yhtio"]."',";
    }
  }

  $varastot      = substr($varastot, 0, -1);
  $varastot_yhtiot = substr($varastot_yhtiot, 0, -1);

  $paikoittain = $valitut["paikoittain"];

  if ($varastot == "" and $paikoittain != "") {
    echo "<font class='error'>".t("VIRHE: Ajat hälytysraportin varastopaikoittain, mutta et valinnut yhtään varastoa.")."</font>";
    exit;
  }

  if ($varastot == "") {
    echo "<font class='error'>".t("VIRHE: Ajat hälytysraportin, mutta et valinnut yhtään varastoa.")."</font>";
    exit;
  }

  $varastowherelisa = $tuotepaikatjoinlisa = "";

  if ($valitut['VARASTOHUOMIO'] != '') {
    $varastowherelisa = "and tilausrivi.varasto in ({$varastot})";
    $tuotepaikatjoinlisa = " and tuotepaikat.varasto in ({$varastot}) ";
  }

  if ($valitut['SALDOLLISET'] != '') {
    $tuotepaikatjoinlisa .= " and tuotepaikat.saldo != 0 ";
  }

  if ($abcrajaus != "") {

    // katotaan JT:ssä olevat tuotteet
    $query = "SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',') tuotteet
              FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
              JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
              WHERE tilausrivi.$yhtiot
              {$varastowherelisa}
              and tyyppi = 'L'
              and var    = 'J'
              and jt $lisavarattu > 0";
    $vtresult = pupe_query($query);
    $vrow = mysql_fetch_assoc($vtresult);

    $jt_tuotteet = "''";

    if ($vrow["tuotteet"] != "") {
      $jt_tuotteet = $vrow["tuotteet"];
    }

    // joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
    $abcjoin = "   JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (
            abc_aputaulu.yhtio = tuote.yhtio
            and abc_aputaulu.tuoteno = tuote.tuoteno
            and abc_aputaulu.tyyppi = '$abcrajaustapa'
            and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
  }
  else {
    $abcjoin = "  LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (
            abc_aputaulu.yhtio = tuote.yhtio
            and abc_aputaulu.tuoteno = tuote.tuoteno
            and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
  }


  if ($KAIKKIJT == "KAIKKIJT") {
    // katotaan JT:ssä olevat tuotteet
    $query = "SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',') tuotteet
              FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
              JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
              WHERE tilausrivi.$yhtiot
              {$varastowherelisa}
              and tyyppi = 'L'
              and var    = 'J'
              and jt $lisavarattu > 0";
    $vtresult = pupe_query($query);
    $vrow = mysql_fetch_assoc($vtresult);

    if ($vrow["tuotteet"] != "") {
      $lisaa .= " and tuote.tuoteno in ($vrow[tuotteet]) ";
    }
  }

  $varastot      = " HAVING varastopaikat.tunnus in ($varastot) or varastopaikat.tunnus is null ";
  $varastot_yhtiot = " yhtio in ($varastot_yhtiot) ";

  //Tuotekannassa voi olla tuotteen mitat kahdella eri tavalla
  // leveys x korkeus x syvyys
  // leveys x korkeus x pituus
  $query = "  SHOW columns
        FROM tuote
        LIKE 'tuotepituus'";
  $spres = pupe_query($query);

  if (mysql_num_rows($spres) == 1) {
    $splisa = "tuote.tuotepituus tuotesyvyys";
  }
  else {
    $splisa = "tuote.tuotesyvyys";
  }

  //Ajetaan raportti tuotteittain
  if ($paikoittain == '') {
    $query = "SELECT
              tuote.yhtio,
              tuote.tuoteno,
              tuote.halytysraja,
              tuote.tilausmaara,
              tuote.tahtituote,
              tuote.status,
              tuote.nimitys,
              tuote.kuvaus,
              tuote.myynti_era,
              tuote.myyntihinta,
              tuote.epakurantti25pvm,
              tuote.epakurantti50pvm,
              tuote.epakurantti75pvm,
              tuote.epakurantti100pvm,
              tuote.tuotemerkki,
              tuote.malli,
              tuote.mallitarkenne,
              tuote.osasto,
              tuote.try,
              tuote.aleryhma,
              tuote.kehahin,
              abc_aputaulu.luokka abcluokka,
              abc_aputaulu.luokka_osasto abcluokka_osasto,
              abc_aputaulu.luokka_try abcluokka_try,
              tuote.luontiaika,
              tuote.sarjanumeroseuranta,
              tuote.tuotekorkeus,
              tuote.tuoteleveys,
              tuote.tuotemassa,
              $splisa,
              tuote.lyhytkuvaus,
              tuote.hinnastoon,
              tuote.ostoehdotus,
              tuote.vihapvm,
              tuote.eankoodi
              FROM tuote
              LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
              $lisaa2
              $abcjoin
              WHERE tuote.$yhtiot
              $lisaa
              and tuote.ei_saldoa   = ''
              and tuote.tuotetyyppi NOT IN ('A', 'B')
              ORDER BY id, tuote.tuoteno";
  }
  //Ajetaan raportti tuotteittain, varastopaikoittain
  else {
    $query = "SELECT
              tuote.yhtio,
              tuote.tuoteno,
              tuotepaikat.halytysraja,
              tuote.tilausmaara,
              tuote.tahtituote,
              tuote.status,
              tuote.nimitys,
              tuote.kuvaus,
              tuote.myynti_era,
              tuote.myyntihinta,
              tuote.epakurantti25pvm,
              tuote.epakurantti50pvm,
              tuote.epakurantti75pvm,
              tuote.epakurantti100pvm,
              tuote.tuotemerkki,
              tuote.malli,
              tuote.mallitarkenne,
              tuote.osasto,
              tuote.try,
              tuote.aleryhma,
              tuote.kehahin,
              abc_aputaulu.luokka abcluokka,
              abc_aputaulu.luokka_osasto abcluokka_osasto,
              abc_aputaulu.luokka_try abcluokka_try,
              tuote.luontiaika,
              tuote.sarjanumeroseuranta,
              tuote.tuotekorkeus,
              tuote.tuoteleveys,
              tuote.tuotemassa,
              $splisa,
              tuote.lyhytkuvaus,
              tuote.hinnastoon,
              concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso) varastopaikka,
              varastopaikat.tunnus,
              tuote.ostoehdotus,
              tuote.vihapvm,
              tuote.eankoodi
              FROM tuote
              $lisaa2
              $abcjoin
              JOIN tuotepaikat ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno {$tuotepaikatjoinlisa})
              LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                AND varastopaikat.tunnus = tuotepaikat.varasto)
              LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
              WHERE tuote.$yhtiot
              $lisaa
              and tuote.ei_saldoa        = ''
              and tuote.tuotetyyppi      NOT IN ('A', 'B')
              $varastot
              ORDER BY id, tuote.tuoteno, varastopaikka";
  }
  $res = pupe_query($query);

  if (isset($valitut["poistetut"]) and $valitut["poistetut"] != '' and isset($valitut["poistuvat"]) and $valitut["poistuvat"] != '') {
    echo "<font class='message'>".t("Vain aktiiviset tuotteet").".<br>";
  }
  if (isset($valitut["poistetut"]) and $valitut["poistetut"] != '' and !isset($valitut["poistuvat"])) {
    echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistuvat näytetään").".<br>";
  }
  if (!isset($valitut["poistetut"]) and isset($valitut["poistuvat"]) and $valitut["poistuvat"] != '') {
    echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistetut näytetään").".<br>";
  }

  if (isset($valitut["ei_ostoehd"]) and $valitut["ei_ostoehd"] != '') {
    echo "<font class='message'>".t("Vain ostoehdotettavat tuotteet").".<br>";
  }
  else {
    echo "<font class='message'>".t("Ostoehdotettavat tuotteet ja ostoehdotukseen kuulumattomat näytetään").".<br>";
  }

  if (isset($valitut["OSTOTVARASTOITTAIN"]) and $valitut["OSTOTVARASTOITTAIN"] != '') {
    echo "<font class='message'>".t("Tilatut eritellään varastoittain").".<br>";
  }

  if (isset($valitut["VAINUUDETTUOTTEET"]) and $valitut["VAINUUDETTUOTTEET"] != '') {
    echo "<font class='message'>".t("Listaa vain 12kk sisällä perustetut tuotteet").".<br>";
  }

  if (isset($valitut["UUDETTUOTTEET"]) and $valitut["UUDETTUOTTEET"] != '') {
    echo "<font class='message'>".t("Ei listata 12kk sisällä perustettuja tuotteita").".<br>";
  }

  if ($abcrajaus != "") {

    echo "<font class='message'>".t("ABC-luokka tai ABC-osastoluokka tai ABC-tuoteryhmäluokka")." >= $ryhmanimet[$abcrajaus] ".t("tai sitä on jälkitoimituksessa");

    if ($valitut["VAINUUDETTUOTTEET"] == '' and $valitut["UUDETTUOTTEET"] == '') {
      echo " ".t("tai tuote on perustettu viimeisen 12kk sisällä").".<br>";
    }
    else {
      echo ".<br>";
    }
  }

  echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>";

  if ($valitut["EHDOTETTAVAT"] != '') {
    echo "<font class='message'>".t("Joista jätetään pois ne tuotteet joita ei ehdoteta ostettavaksi").".<br>";
  }

  flush();

  if (mysql_num_rows($res) > 0) {

    require 'inc/ProgressBar.class.php';

    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);

    $rivi      = "";
    $excelrivi    = 0;
    $excelsarake = 0;

    $rivi .= t("tuoteno")."\t";

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuoteno")), $format_bold);
    $excelsarake++;

    if ($paikoittain != '') {
      $rivi .= t("Varastopaikka")."\t";

      $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Varastopaikka")), $format_bold);
      $excelsarake++;
    }

    foreach ($valitut as $val) {
      $rivi .= $sarakkeet[$val];

      if ($sarakkeet[$val] != '') {
        $worksheet->writeString($excelrivi, $excelsarake, ucfirst(trim($sarakkeet[$val])), $format_bold);
        $excelsarake++;
      }
    }

    $rivi .= "\r\n";
    $excelrivi++;
    $excelsarake = 0;

    $bar = new ProgressBar();
    $bar->initialize(mysql_num_rows($res));

    while ($row = mysql_fetch_assoc($res)) {
      $bar->increase();

      $lisa = "";

      if ($paikoittain != '') {
        $lisa = " and concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)='$row[varastopaikka]' ";
      }

      if ($paikoittain == '') {
        // Kaikkien valittujen varastojen paikkojen saldo yhteensä, mukaan tulee myös aina ne saldot jotka ei kuulu mihinkään varastoalueeseen
        $query = "SELECT sum(saldo) saldo, varastopaikat.tunnus
                  FROM tuotepaikat
                  LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                    AND varastopaikat.tunnus = tuotepaikat.varasto)
                  WHERE tuotepaikat.$varastot_yhtiot
                  and tuotepaikat.tuoteno    = '$row[tuoteno]'
                  GROUP BY varastopaikat.tunnus
                  $varastot";
        $result = pupe_query($query);

        $sumsaldo = 0;

        while ($saldo = mysql_fetch_assoc($result)) {
          $sumsaldo += $saldo["saldo"];
        }

        if ($valitut['SALDOLLISET'] != '' and $sumsaldo == 0) {
          continue;
        }

        $saldo["saldo"] = $sumsaldo;
      }
      else {

        $lisa_tuotepaikat = str_replace("tilausrivi.", "", $lisa);

        // Ajetaan varastopaikoittain eli tässä on just tän paikan saldo
        $query = "SELECT saldo
                  from tuotepaikat
                  where yhtio='$row[yhtio]'
                  and tuoteno='$row[tuoteno]'
                  {$lisa_tuotepaikat}";
        $result = pupe_query($query);
        $saldo = mysql_fetch_assoc($result);
      }

      //toimittajatiedot
      if ($toimittajaid == '') {
        if (isset($nayta_vain_ykkostoimittaja)) {
          $query = "SELECT
                    toimi.ytunnus toimittaja,
                    tuotteen_toimittajat.osto_era,
                    tuotteen_toimittajat.toim_tuoteno,
                    tuotteen_toimittajat.toim_nimitys,
                    tuotteen_toimittajat.ostohinta,
                    tuotteen_toimittajat.tuotekerroin,
                    tuotteen_toimittajat.viivakoodi
                    FROM tuotteen_toimittajat
                    JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
                    WHERE tuotteen_toimittajat.yhtio = '$row[yhtio]'
                    and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
                    ORDER BY IF (jarjestys = 0, 9999, jarjestys) LIMIT 1";
        }
        else {
          $query = "SELECT
                    group_concat(toimi.ytunnus                 order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
                    group_concat(distinct tuotteen_toimittajat.osto_era   order by tuotteen_toimittajat.tunnus separator '/') osto_era,
                    group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
                    group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
                    group_concat(distinct tuotteen_toimittajat.ostohinta   order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
                    group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin,
                    group_concat(distinct tuotteen_toimittajat.viivakoodi order by tuotteen_toimittajat.tunnus separator '/') viivakoodi
                    FROM tuotteen_toimittajat
                    JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
                    WHERE tuotteen_toimittajat.yhtio = '$row[yhtio]'
                    and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'";
        }
      }
      else {
        $ykkostoim_orderilisa = isset($nayta_vain_ykkostoimittaja) ? " AND tuotteen_toimittajat.liitostunnus = (SELECT liitostunnus FROM tuotteen_toimittajat WHERE yhtio = '{$row['yhtio']}' AND tuoteno = '{$row['tuoteno']}' ORDER BY IF (jarjestys = 0, 9999, jarjestys) LIMIT 1)": "";

        $query = "SELECT toimi.ytunnus toimittaja,
                  tuotteen_toimittajat.osto_era,
                  tuotteen_toimittajat.toim_tuoteno,
                  tuotteen_toimittajat.toim_nimitys,
                  tuotteen_toimittajat.ostohinta,
                  tuotteen_toimittajat.tuotekerroin,
                  tuotteen_toimittajat.viivakoodi
                  FROM tuotteen_toimittajat
                  JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
                  WHERE tuotteen_toimittajat.yhtio      = '$row[yhtio]'
                  and tuotteen_toimittajat.tuoteno      = '$row[tuoteno]'
                  and tuotteen_toimittajat.liitostunnus = '$toimittajaid'
                  {$ykkostoim_orderilisa}";
      }

      $result   = pupe_query($query);
      $toimirow = mysql_fetch_assoc($result);

      $row['toimittaja']     = $toimirow['toimittaja'];
      $row['osto_era']     = $toimirow['osto_era'];
      $row['toim_tuoteno']   = $toimirow['toim_tuoteno'];
      $row['toim_nimitys']   = $toimirow['toim_nimitys'];
      $row['ostohinta']     = $toimirow['ostohinta'];
      $row['tuotekerroin']   = $toimirow['tuotekerroin'];
      
      if ($row['eankoodi'] == '') {
        $row['eankoodi']  = $toimirow['viivakoodi'];
      } 

      ///* Myydyt kappaleet *///
      $query = "SELECT
                sum(if (tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kpl,0)) kpl1,
                sum(if (tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2' and tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kpl,0)) kpl2,
                sum(if (tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3' and tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kpl,0)) kpl3,
                sum(if (tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kpl,0)) kpl4,
                sum(if (tilausrivi.laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and tilausrivi.laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,tilausrivi.kpl,0)) EDkpl1,
                sum(if (tilausrivi.laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and tilausrivi.laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,tilausrivi.kpl,0)) EDkpl2,
                sum(if (tilausrivi.laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and tilausrivi.laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,tilausrivi.kpl,0)) EDkpl3,
                sum(if (tilausrivi.laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and tilausrivi.laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,tilausrivi.kpl,0)) EDkpl4,
                sum(if (tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) kate1,
                sum(if (tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2' and tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kate,0)) kate2,
                sum(if (tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3' and tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kate,0)) kate3,
                sum(if (tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kate,0)) kate4,
                sum(if (tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.rivihinta,0)) rivihinta1,
                sum(if (tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2' and tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.rivihinta,0)) rivihinta2,
                sum(if (tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3' and tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.rivihinta,0)) rivihinta3,
                sum(if (tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.rivihinta,0)) rivihinta4,
                sum(if (tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' and tilausrivi.campaign_id IS NOT NULL ,tilausrivi.kpl,0)) kampanja1,
                sum(if (tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2' and tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2' and tilausrivi.campaign_id IS NOT NULL ,tilausrivi.kpl,0)) kampanja2,
                sum(if (tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3' and tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3' and tilausrivi.campaign_id IS NOT NULL ,tilausrivi.kpl,0)) kampanja3,
                sum(if (tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4' and tilausrivi.campaign_id IS NOT NULL ,tilausrivi.kpl,0)) kampanja4,
                sum(if (tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' and tilausrivin_lisatiedot.korvamerkinta = 'Sample' ,tilausrivi.kpl,0)) sample1,
                sum(if (tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2' and tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2' and tilausrivin_lisatiedot.korvamerkinta = 'Sample' ,tilausrivi.kpl,0)) sample2,
                sum(if (tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3' and tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3' and tilausrivin_lisatiedot.korvamerkinta = 'Sample' ,tilausrivi.kpl,0)) sample3,
                sum(if (tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4' and tilausrivin_lisatiedot.korvamerkinta = 'Sample' ,tilausrivi.kpl,0)) sample4,
                max(tilausrivi.laskutettuaika) myyntipvm
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                JOIN tilausrivin_lisatiedot use index (tilausrivitunnus) ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus 
                WHERE tilausrivi.yhtio        = '$row[yhtio]'
                {$varastowherelisa}
                and tilausrivi.tyyppi         = 'L'
                and tilausrivi.tuoteno        = '$row[tuoteno]'
                and tilausrivi.laskutettuaika >= '$apvm'
                and tilausrivi.laskutettuaika <= '$lpvm'
                $lisa";
      $result   = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      $query = "SELECT
                sum(if (tilausrivi.laadittu >= '$vva1-$kka1-$ppa1 00:00:00' and tilausrivi.laadittu <= '$vvl1-$kkl1-$ppl1 23:59:59' and tilausrivi.var='P', tilausrivi.tilkpl,0)) puutekpl1,
                sum(if (tilausrivi.laadittu >= '$vva2-$kka2-$ppa2 00:00:00' and tilausrivi.laadittu <= '$vvl2-$kkl2-$ppl2 23:59:59' and tilausrivi.var='P', tilausrivi.tilkpl,0)) puutekpl2,
                sum(if (tilausrivi.laadittu >= '$vva3-$kka3-$ppa3 00:00:00' and tilausrivi.laadittu <= '$vvl3-$kkl3-$ppl3 23:59:59' and tilausrivi.var='P', tilausrivi.tilkpl,0)) puutekpl3,
                sum(if (tilausrivi.laadittu >= '$vva4-$kka4-$ppa4 00:00:00' and tilausrivi.laadittu <= '$vvl4-$kkl4-$ppl4 23:59:59' and tilausrivi.var='P', tilausrivi.tilkpl,0)) puutekpl4
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
                WHERE tilausrivi.yhtio  = '$row[yhtio]'
                {$varastowherelisa}
                and tilausrivi.tyyppi   = 'L'
                and tilausrivi.tuoteno  = '$row[tuoteno]'
                and tilausrivi.laadittu >= '$apvm 00:00:00'
                and tilausrivi.laadittu <= '$lpvm 23:59:59'
                $lisa";
      $result   = pupe_query($query);
      $puuterow = mysql_fetch_assoc($result);

      if ($laskurow['rivihinta1'] <> 0) {
        $katepros1 = round($laskurow['kate1'] / $laskurow['rivihinta1'] * 100, 0);
      }
      else ($katepros1 = 0);

      if ($laskurow['rivihinta2'] <> 0) {
        $katepros2 = round($laskurow['kate2'] / $laskurow['rivihinta2'] * 100, 0);
      }
      else ($katepros2 = 0);

      if ($laskurow['rivihinta3'] <> 0) {
        $katepros3 = round($laskurow['kate3'] / $laskurow['rivihinta3'] * 100, 0);
      }
      else ($katepros3 = 0);

      if ($laskurow['rivihinta4'] <> 0) {
        $katepros4 = round($laskurow['kate4'] / $laskurow['rivihinta4'] * 100, 0);
      }
      else ($katepros4 = 0);

      ///* Kulutetut kappaleet *///
      $query = "SELECT
                sum(if (tilausrivi.toimitettuaika >= '$vva1-$kka1-$ppa1 00:00:00' and tilausrivi.toimitettuaika <= '$vvl1-$kkl1-$ppl1 23:59:59' ,tilausrivi.kpl,0)) kpl1,
                sum(if (tilausrivi.toimitettuaika >= '$vva2-$kka2-$ppa2 00:00:00' and tilausrivi.toimitettuaika <= '$vvl2-$kkl2-$ppl2 23:59:59' ,tilausrivi.kpl,0)) kpl2,
                sum(if (tilausrivi.toimitettuaika >= '$vva3-$kka3-$ppa3 00:00:00' and tilausrivi.toimitettuaika <= '$vvl3-$kkl3-$ppl3 23:59:59' ,tilausrivi.kpl,0)) kpl3,
                sum(if (tilausrivi.toimitettuaika >= '$vva4-$kka4-$ppa4 00:00:00' and tilausrivi.toimitettuaika <= '$vvl4-$kkl4-$ppl4 23:59:59' ,tilausrivi.kpl,0)) kpl4,
                sum(if (tilausrivi.toimitettuaika >= '$vva1ed-$kka1ed-$ppa1ed 00:00:00' and tilausrivi.toimitettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed 23:59:59' ,tilausrivi.kpl,0)) EDkpl1,
                sum(if (tilausrivi.toimitettuaika >= '$vva2ed-$kka2ed-$ppa2ed 00:00:00' and tilausrivi.toimitettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed 23:59:59' ,tilausrivi.kpl,0)) EDkpl2,
                sum(if (tilausrivi.toimitettuaika >= '$vva3ed-$kka3ed-$ppa3ed 00:00:00' and tilausrivi.toimitettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed 23:59:59' ,tilausrivi.kpl,0)) EDkpl3,
                sum(if (tilausrivi.toimitettuaika >= '$vva4ed-$kka4ed-$ppa4ed 00:00:00' and tilausrivi.toimitettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed 23:59:59' ,tilausrivi.kpl,0)) EDkpl4
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
                WHERE tilausrivi.yhtio = '$row[yhtio]'
                {$varastowherelisa}
                and tilausrivi.tyyppi  = 'V'
                and tilausrivi.tuoteno = '$row[tuoteno]'
                and ((tilausrivi.toimitettuaika >= '$apvm 00:00:00' and tilausrivi.toimitettuaika <= '$lpvm 23:59:59') or tilausrivi.toimitettuaika = '0000-00-00 00:00:00')
                $lisa";
      $result   = pupe_query($query);
      $kulutrow = mysql_fetch_assoc($result);

      //tilauksessa, ennakkopoistot ja jt  HUOM: varastolisa määritelty jo aiemmin!
      $query = "SELECT
                sum(if(tilausrivi.tyyppi in ('W','M'), tilausrivi.varattu, 0)) valmistuksessa,
                sum(if(tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)) tilattu,
                sum(if(tilausrivi.tyyppi = 'E' and tilausrivi.var != 'O', tilausrivi.varattu, 0)) ennakot, # toimittamattomat ennakot
                sum(if(tilausrivi.tyyppi in ('L','V') and tilausrivi.var not in ('P','J','O','S'), tilausrivi.varattu, 0)) ennpois,
                sum(if(tilausrivi.tyyppi in ('L','G') and tilausrivi.var = 'J', jt $lisavarattu, 0)) jt
                $varastolisa
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                WHERE tilausrivi.yhtio        = '$row[yhtio]'
                {$varastowherelisa}
                and tilausrivi.tyyppi         in ('L','V','O','G','E','W','M')
                and tilausrivi.tuoteno        = '$row[tuoteno]'
                and tilausrivi.laskutettuaika = '0000-00-00'
                and (tilausrivi.varattu + tilausrivi.jt > 0)";
      $result = pupe_query($query);
      $ennp   = mysql_fetch_assoc($result);

      if (isset($nayta_vain_ykkostoimittaja)) {
        $query = "SELECT sum(if(tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)) tilattu
                  FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                  JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus = (select liitostunnus from tuotteen_toimittajat where yhtio='$row[yhtio]' and tuoteno='$row[tuoteno]' ORDER BY if (jarjestys = 0, 9999, jarjestys) LIMIT 1)
                  WHERE tilausrivi.yhtio        = '$row[yhtio]'
                  {$varastowherelisa}
                   and tilausrivi.tyyppi        = 'O'
                  and tilausrivi.tuoteno        = '$row[tuoteno]'
                  and tilausrivi.laskutettuaika = '0000-00-00'";
        $result = pupe_query($query);
        $ykkostoimittajarajattuna   = mysql_fetch_assoc($result);

        $ennp['tilattu'] = empty($ykkostoimittajarajattuna['tilattu']) ? 0 : $ykkostoimittajarajattuna['tilattu'];
      }

      // oletuspaikan saldo ja hyllypaikka
      $query = "SELECT sum(saldo) osaldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
                from tuotepaikat
                where yhtio='$row[yhtio]'
                and tuoteno='$row[tuoteno]'
                and oletus='X'
                group by hyllyalue, hyllynro, hyllyvali, hyllytaso";
      $result = pupe_query($query);
      $osaldo = mysql_fetch_assoc($result);

      if ($row['osto_era']==0) $row['osto_era']=1;

      ///* lasketaan ehdotelma, mitä kannattaisi tilata *///
      //minkä kauden mukaan ostoehdotukset lasketaan
      $indeksi    = "kpl".$ostoehdotus;

      //Kausien ero sekunneissa
      $ero = strtotime(${"vvl".$ostoehdotus}."-".${"kkl".$ostoehdotus}."-".${"ppl".$ostoehdotus}) - strtotime(${"vva".$ostoehdotus}."-".${"kka".$ostoehdotus}."-".${"ppa".$ostoehdotus});

      //Kausien ero kuukausissa, tässä approximoidaan, että kuukausissa on keskimäärin 365/12 päivää
      $ero = $ero/60/60/24/(365/12);

      $ehd_kausi1  = "1";
      $ehd_kausi2 = "3";
      $ehd_kausi3 = "4";

      if ($valitut["KAUSI1"] != '') {
        list($al, $lo) = explode("##", $valitut["KAUSI1"]);

        $ehd_kausi1  = $lo;
      }
      if ($valitut["KAUSI2"] != '') {
        list($al, $lo) = explode("##", $valitut["KAUSI2"]);

        $ehd_kausi2  = $lo;
      }
      if ($valitut["KAUSI3"] != '') {
        list($al, $lo) = explode("##", $valitut["KAUSI3"]);

        $ehd_kausi3  = $lo;
      }

      // Laskentakaava:
      // (((myynti + kulutus) / valitun_kauden_pituus_kuukauksissa * varastointitarve_kuukauksissa) - (saldo + tilattu + valmistuksessa - varatut - jt)) / osto_era
      // echo "$row[tuoteno]: ((({$laskurow[$indeksi]} + {$kulutrow[$indeksi]}) / $ero * $ehd_kausi1) - ({$saldo['saldo']} + {$ennp['tilattu']} + {$ennp['valmistuksessa']} - {$ennp['ennpois']} - {$ennp['jt']})) / {$row['osto_era']}<br>";

      $ostettava1kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi1) - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];
      $ostettava3kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi2) - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];
      $ostettava4kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi3) - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];

      $ostettavahaly = ($row['halytysraja'] - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];

      // Ostetaan kamaa jos tilausmäärä on > 0 ja halyraja on alitettu tai hälyraja on nolla
      if (($ostettavahaly > 0 or ($ostettavahaly == 0 and $row['halytysraja'] == 0)) and $row['tilausmaara'] > 0) {
        $ostettavahalytilausmaara = ceil($ostettavahaly/$row['tilausmaara']) * $row['tilausmaara'];
      }
      elseif ($ostettavahaly > 0 and $row['tilausmaara'] == 0) {
        $ostettavahalytilausmaara = $ostettavahaly;
      }
      else {
        $ostettavahalytilausmaara = 0;
      }

      // jos tuotteella on joku ostoerä pyöristellään ylospäin, että tilataan aina toimittajan haluama määrä
      if ($ostettava1kk > 0)  $ostettava1kk = ceil($ostettava1kk) * $row['osto_era'];
      else           $ostettava1kk = 0;

      if ($ostettava3kk > 0)  $ostettava3kk = ceil($ostettava3kk) * $row['osto_era'];
      else           $ostettava3kk = 0;

      if ($ostettava4kk > 0)  $ostettava4kk = ceil($ostettava4kk) * $row['osto_era'];
      else           $ostettava4kk = 0;

      if ($ostettavahaly > 0)  $ostettavahaly = ceil($ostettavahaly) * $row['osto_era'];
      else           $ostettavahaly = 0;

      $vva1ehto = "tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1'
                    AND tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1'";
      $vva2ehto = "tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2'
                    AND tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2'";
      $vva3ehto = "tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3'
                    AND tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3'";
      $vva4ehto = "tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4'
                    AND tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4'";

      //asiakkaan ostot
      if ($asiakasosasto != '') {
        $query  = "SELECT
                   sum(if ({$vva1ehto}, tilausrivi.kpl, 0)) kpl1,
                   sum(if ({$vva2ehto}, tilausrivi.kpl, 0)) kpl2,
                   sum(if ({$vva3ehto}, tilausrivi.kpl, 0)) kpl3,
                   sum(if ({$vva4ehto}, tilausrivi.kpl, 0)) kpl4
                   FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                   JOIN lasku use index(PRIMARY)
                   JOIN asiakas use index (ytunnus_index)
                   WHERE tilausrivi.yhtio        = '$row[yhtio]'
                   {$varastowherelisa}
                   AND tilausrivi.tyyppi         = 'L'
                   AND tilausrivi.tuoteno        = '$row[tuoteno]'
                   AND tilausrivi.laskutettuaika >= '$apvm'
                   AND tilausrivi.laskutettuaika <= '$lpvm'
                   AND lasku.yhtio               = tilausrivi.yhtio
                   AND lasku.tunnus              = tilausrivi.uusiotunnus
                   AND asiakas.ytunnus           = lasku.ytunnus
                   AND asiakas.yhtio             = lasku.yhtio
                   AND asiakas.osasto            = '$asiakasosasto'";
        $asosresult = pupe_query($query);
        $asosrow = mysql_fetch_assoc($asosresult);
      }

      if ($asiakasid != '') {
        $query  = "SELECT
                   sum(if ({$vva1ehto}, tilausrivi.kpl, 0)) kpl1,
                   sum(if ({$vva2ehto}, tilausrivi.kpl, 0)) kpl2,
                   sum(if ({$vva3ehto}, tilausrivi.kpl, 0)) kpl3,
                   sum(if ({$vva4ehto}, tilausrivi.kpl, 0)) kpl4
                   FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                   JOIN lasku use index(PRIMARY)
                   WHERE tilausrivi.yhtio        = '$row[yhtio]'
                   {$varastowherelisa}
                   AND tilausrivi.tyyppi         = 'L'
                   AND tilausrivi.tuoteno        = '$row[tuoteno]'
                   AND tilausrivi.laskutettuaika >= '$apvm'
                   AND tilausrivi.laskutettuaika <= '$lpvm'
                   AND lasku.yhtio               = tilausrivi.yhtio
                   AND lasku.tunnus              = tilausrivi.otunnus
                   AND lasku.liitostunnus        = '$asiakasid'";
        $asresult = pupe_query($query);
        $asrow = mysql_fetch_assoc($asresult);
      }

      if ((!isset($valitut['EHDOTETTAVAT']) or $valitut['EHDOTETTAVAT'] == '') or $ostettavahalytilausmaara > 0 or $ostettavahaly > 0 or $ostettava4kk > 0) {

        // kirjotettaan rivi
        $rivi .= "\"$row[tuoteno]\"\t";

        $worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"], $format_bold);
        $excelsarake++;

        if ($paikoittain != '') {
          $rivi .= "\"$row[varastopaikka]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["varastopaikka"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE1"] != '') {
          $rivi .= "\"$row[osasto]\"\t";

          $worksheet->write($excelrivi, $excelsarake, $row["osasto"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE2"] != '') {
          $rivi .= "\"$row[try]\"\t";

          $worksheet->write($excelrivi, $excelsarake, $row["try"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE3"] != '') {
          $rivi .= "\"$row[tuotemerkki]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["tuotemerkki"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE3B"] != '') {
          $rivi .= "\"$row[malli]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["malli"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE3C"] != '') {
          $rivi .= "\"$row[mallitarkenne]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["mallitarkenne"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE4"] != '') {
          $rivi .= "\"$row[tahtituote]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["tahtituote"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE4B"] != '') {
          $rivi .= "\"$row[status]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["status"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE4C"] != '') {
          $rivi .= "\"".$ryhmanimet[$row["abcluokka"]]."\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka"]]);
          $excelsarake++;
        }

        if ($valitut["SARAKE4CA"] != '') {
          $rivi .= "\"".$ryhmanimet[$row["abcluokka_osasto"]]."\"\t";

          $worksheet->write($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka_osasto"]]);
          $excelsarake++;
        }

        if ($valitut["SARAKE4CB"] != '') {
          $rivi .= "\"".$ryhmanimet[$row["abcluokka_try"]]."\"\t";

          $worksheet->write($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka_try"]]);
          $excelsarake++;
        }

        if ($valitut["SARAKE4D"] != '') {
          if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";

          $rivi .= "\"$row[luontiaika]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["luontiaika"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE5"] != '') {
          $rivi .= str_replace(".", ",", $saldo['saldo'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $saldo["saldo"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE6"] != '') {
          $rivi .= str_replace(".", ",", $row['halytysraja'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["halytysraja"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE6B"] != '') {
          $rivi .= str_replace(".", ",", $row['tilausmaara'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["tilausmaara"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE7"] != '') {
          $rivi .= str_replace(".", ",", $ennp['tilattu'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ennp["tilattu"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE7B"] != '') {
          $rivi .= str_replace(".", ",", $ennp['valmistuksessa'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ennp["valmistuksessa"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE8"] != '') {
          $rivi .= str_replace(".", ",", $ennp['ennpois'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ennp["ennpois"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE9"] != '') {
          $rivi .= str_replace(".", ",", $ennp['jt'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ennp["jt"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE10"] != '') {
          $rivi .= "$ostettava1kk\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ostettava1kk, $format_bold);
          $excelsarake++;
        }

        if ($valitut["SARAKE11"] != '') {
          $rivi .= "$ostettava3kk\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ostettava3kk, $format_bold);
          $excelsarake++;
        }

        if ($valitut["SARAKE12"] != '') {
          $rivi .= "$ostettava4kk\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ostettava4kk, $format_bold);
          $excelsarake++;
        }

        if ($valitut["SARAKE12B"] != '') {
          $ostoehdotus_status = $row['ostoehdotus'] == 'E' ? t("Ei") : t("Kyllä");
          $rivi .= $ostoehdotus_status."\t";

          $worksheet->writeString($excelrivi, $excelsarake, $ostoehdotus_status);
          $excelsarake++;
        }

        if ($valitut["SARAKE12C"] != '') {
          $rivi .= $row['vihapvm']."\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["vihapvm"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE12D"] != '') {
          $rivi .= $laskurow['myyntipvm']."\t";

          $worksheet->writeString($excelrivi, $excelsarake, $laskurow['myyntipvm']);
          $excelsarake++;
        }

        if ($valitut["SARAKE13"] != '') {
          $rivi .= "$ostettavahaly\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ostettavahaly);
          $excelsarake++;
        }

        if ($valitut["SARAKE13B"] != '') {
          $rivi .= "$ostettavahalytilausmaara\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ostettavahalytilausmaara);
          $excelsarake++;
        }

        if ($valitut["SARAKE14"] != '') {
          $rivi .= str_replace(".", ",", $row['osto_era'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["osto_era"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE15"] != '') {
          $rivi .= str_replace(".", ",", $row['myynti_era'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["myynti_era"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE16"] != '') {
          $rivi .= "\"$row[toimittaja]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["toimittaja"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE17"] != '') {
          $rivi .= "\"$row[toim_tuoteno]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["toim_tuoteno"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18"] != '') {
          $rivi .= "\"".t_tuotteen_avainsanat($row, 'nimitys')."\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
          $excelsarake++;
        }

        if ($valitut["SARAKE18B"] != '') {
          $rivi .= "\"$row[toim_nimitys]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["toim_nimitys"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18C"] != '') {
          $rivi .= "\"$row[kuvaus]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["kuvaus"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18D"] != '') {
          $rivi .= "\"$row[lyhytkuvaus]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["lyhytkuvaus"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18E"] != '') {
          $rivi .= "\"$row[tuotekorkeus]\"\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotekorkeus"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18F"] != '') {
          $rivi .= "\"$row[tuoteleveys]\"\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["tuoteleveys"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18G"] != '') {
          $rivi .= "\"$row[tuotesyvyys]\"\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotesyvyys"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18H"] != '') {
          $rivi .= "\"$row[tuotemassa]\"\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotemassa"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18I"] != '') {
          $rivi .= "\"$row[hinnastoon]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["hinnastoon"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE18J"] != '') {
          $rivi .= "\"$row[eankoodi]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["eankoodi"]);
          $excelsarake++;
        }
        
        if ($valitut["SARAKE19"] != '') {
          $rivi .= str_replace(".", ",", $row['ostohinta'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["ostohinta"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE20"] != '') {
          $rivi .= str_replace(".", ",", $row['myyntihinta'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["myyntihinta"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE20Z"] != '') {
          $epakur = "";
          if ($row['epakurantti25pvm'] != '0000-00-00') $epakur = $row['epakurantti25pvm'];

          $rivi .= "$epakur\t";

          $worksheet->writeString($excelrivi, $excelsarake, $epakur);
          $excelsarake++;
        }

        if ($valitut["SARAKE21"] != '') {
          $epakur = "";
          if ($row['epakurantti50pvm'] != '0000-00-00') $epakur = $row['epakurantti50pvm'];

          $rivi .= "$epakur\t";

          $worksheet->writeString($excelrivi, $excelsarake, $epakur);
          $excelsarake++;
        }

        if ($valitut["SARAKE21B"] != '') {
          $epakur = "";
          if ($row['epakurantti75pvm'] != '0000-00-00') $epakur = $row['epakurantti75pvm'];

          $rivi .= "$epakur\t";

          $worksheet->writeString($excelrivi, $excelsarake, $epakur);
          $excelsarake++;
        }

        if ($valitut["SARAKE22"] != '') {
          $epakur = "";
          if ($row['epakurantti100pvm'] != '0000-00-00') $epakur = $row['epakurantti100pvm'];

          $rivi .= "$epakur\t";

          $worksheet->writeString($excelrivi, $excelsarake, $epakur);
          $excelsarake++;
        }

        if ($valitut["SARAKE23"] != '') {
          $rivi .= str_replace(".", ",", $osaldo['osaldo'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $osaldo["osaldo"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE24"] != '') {
          $rivi .= "\"$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, "$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]");
          $excelsarake++;
        }

        if ($valitut["SARAKE25"] != '') {
          $rivi .= str_replace(".", ",", $puuterow['puutekpl1'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl1"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE26"] != '') {
          $rivi .= str_replace(".", ",", $puuterow['puutekpl2'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl2"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE27"] != '') {
          $rivi .= str_replace(".", ",", $puuterow['puutekpl3'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl3"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE28"] != '') {
          $rivi .= str_replace(".", ",", $puuterow['puutekpl4'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl4"]);
          $excelsarake++;
        }

        //Myydyt kappaleet
        if ($valitut["SARAKE29"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kpl1'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl1"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE30"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kpl2'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl2"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE31"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kpl3'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl3"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE32"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kpl4'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl4"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE33"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['EDkpl1'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl1"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE34"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['EDkpl2'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl2"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE35"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['EDkpl3'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl3"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE36"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['EDkpl4'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl4"]);
          $excelsarake++;
        }

        //Kulutetut kappaleet
        if ($valitut["SARAKE29K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['kpl1'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl1"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE30K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['kpl2'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl2"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE31K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['kpl3'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl3"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE32K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['kpl4'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl4"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE33K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['EDkpl1'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl1"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE34K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['EDkpl2'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl2"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE35K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['EDkpl3'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl3"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE36K"] != '') {
          $rivi .= str_replace(".", ",", $kulutrow['EDkpl4'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl4"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE37"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kate1'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate1"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE38"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kate2'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate2"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE39"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kate3'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate3"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE40"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kate4'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate4"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE41"] != '') {
          $rivi .= str_replace(".", ",", $katepros1)."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $katepros1);
          $excelsarake++;
        }

        if ($valitut["SARAKE42"] != '') {
          $rivi .= str_replace(".", ",", $katepros2)."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $katepros2);
          $excelsarake++;
        }

        if ($valitut["SARAKE43"] != '') {
          $rivi .= str_replace(".", ",", $katepros3)."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $katepros3);
          $excelsarake++;
        }

        if ($valitut["SARAKE44"] != '') {
          $rivi .= str_replace(".", ",", $katepros4)."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $katepros4);
          $excelsarake++;
        }

        if ($valitut["SARAKE45"] != '') {
          $rivi .= str_replace(".", ",", $row['tuotekerroin'])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotekerroin"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE46"] != '') {
          $rivi .= str_replace(".", ",", $ennp["ennakot"])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ennp["ennakot"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE47"] != '') {
          $rivi .= "\"$row[aleryhma]\"\t";

          $worksheet->writeString($excelrivi, $excelsarake, $row["aleryhma"]);
          $excelsarake++;
        }

        if ($valitut["SARAKE47B"] != '') {
          $kehahin = 0;

          //Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu))
          if ($row["sarjanumeroseuranta"] == "S") {
            $query  = "SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
                       FROM sarjanumeroseuranta
                       LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                       LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                       WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                       and sarjanumeroseuranta.tuoteno           = '$row[tuoteno]'
                       and sarjanumeroseuranta.myyntirivitunnus != -1
                       and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                       and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
            $sarjares = pupe_query($query);
            $sarjarow = mysql_fetch_assoc($sarjares);

            $kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
          }
          else {
            $kehahin = sprintf('%.2f', $row["kehahin"]);
          }

          if     ($row['epakurantti100pvm'] != '0000-00-00') $kehahin = 0;
          elseif   ($row['epakurantti75pvm'] != '0000-00-00')  $kehahin = round($kehahin * 0.25, 6);
          elseif   ($row['epakurantti50pvm'] != '0000-00-00')  $kehahin = round($kehahin * 0.5,  6);
          elseif   ($row['epakurantti25pvm'] != '0000-00-00')  $kehahin = round($kehahin * 0.75, 6);

          $rivi .= str_replace(".", ",", $kehahin)."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kehahin);
          $excelsarake++;
        }

        if ($asiakasosasto != '') {
          if ($valitut["SARAKE48"] != '') {
            $rivi .= str_replace(".", ",", $asosrow['kpl1'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl1']);
            $excelsarake++;
          }

          if ($valitut["SARAKE49"] != '') {
            $rivi .= str_replace(".", ",", $asosrow['kpl2'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl2']);
            $excelsarake++;
          }

          if ($valitut["SARAKE50"] != '') {
            $rivi .= str_replace(".", ",", $asosrow['kpl3'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl3']);
            $excelsarake++;
          }

          if ($valitut["SARAKE51"] != '') {
            $rivi .= str_replace(".", ",", $asosrow['kpl4'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl4']);
            $excelsarake++;

          }
        }

        if ($asiakasno != '') {
          if ($valitut["SARAKE52"] != '') {
            $rivi .= str_replace(".", ",", $asrow['kpl1'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl1']);
            $excelsarake++;
          }

          if ($valitut["SARAKE53"] != '') {
            $rivi .= str_replace(".", ",", $asrow['kpl2'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl2']);
            $excelsarake++;
          }

          if ($valitut["SARAKE54"] != '') {
            $rivi .= str_replace(".", ",", $asrow['kpl3'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl3']);
            $excelsarake++;
          }

          if ($valitut["SARAKE55"] != '') {
            $rivi .= str_replace(".", ",", $asrow['kpl4'])."\t";

            $worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl4']);
            $excelsarake++;
          }
        }

        unset($korvaresult1);
        unset($korvaresult2);
        $korvaavat_tunrot = "";

        //korvaavat tuotteet
        $query  = "SELECT id
                   FROM korvaavat
                   WHERE tuoteno = '$row[tuoteno]'
                   and yhtio     = '$row[yhtio]'";
        $korvaresult1 = pupe_query($query);

        if (mysql_num_rows($korvaresult1) > 0) {
          $korvarow = mysql_fetch_assoc($korvaresult1);

          $query  = "SELECT tuoteno
                     FROM korvaavat
                     WHERE tuoteno != '$row[tuoteno]'
                     and id         = '$korvarow[id]'
                     and yhtio      = '$row[yhtio]'";
          $korvaresult2 = pupe_query($query);

          //tulostetaan korvaavat
          while ($korvarow = mysql_fetch_assoc($korvaresult2)) {
            $korvaavat_tunrot .= ",'$korvarow[tuoteno]'";
          }
        }

        if ($valitut["SARAKE64"] != '') {
          $query = "SELECT count(*) kpl
                    FROM yhteensopivuus_tuote
                    JOIN yhteensopivuus_rekisteri on (yhteensopivuus_rekisteri.yhtio = yhteensopivuus_tuote.yhtio and yhteensopivuus_rekisteri.autoid = yhteensopivuus_tuote.atunnus)
                    WHERE yhteensopivuus_tuote.yhtio='$kukarow[yhtio]'
                    and yhteensopivuus_tuote.tuoteno in ('$row[tuoteno]' $korvaavat_tunrot)
                     and yhteensopivuus_tuote.tyyppi='HA'";
          $asresult = pupe_query($query);
          $kasrow = mysql_fetch_assoc($asresult);

          $rivi .= $kasrow['kpl']."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $kasrow['kpl']);
          $excelsarake++;
        }
        
        if ($valitut["SARAKE70"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kampanja1'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['kampanja1']);
          $excelsarake++;
        }

        if ($valitut["SARAKE71"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kampanja2'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['kampanja2']);
          $excelsarake++;
        }

        if ($valitut["SARAKE72"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kampanja3'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['kampanja3']);
          $excelsarake++;
        }

        if ($valitut["SARAKE73"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['kampanja4'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['kampanja4']);
          $excelsarake++;
        }

        if ($valitut["SARAKE74"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['sample1'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['sample1']);
          $excelsarake++;
        }

        if ($valitut["SARAKE75"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['sample2'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['sample2']);
          $excelsarake++;
        }

        if ($valitut["SARAKE76"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['sample3'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['sample3']);
          $excelsarake++;
        }

        if ($valitut["SARAKE77"] != '') {
          $rivi .= str_replace(".", ",", $laskurow['sample4'])."\t";
          
          $worksheet->writeNumber($excelrivi, $excelsarake, $laskurow['sample4']);
          $excelsarake++;
        }

        //Liitetäänkö myös tilauttu by varasto
        if (isset($osvres) and is_resource($osvres)) {
          mysql_data_seek($osvres, 0);

          while ($vrow = mysql_fetch_assoc($osvres)) {
            $rivi .= str_replace(".", ",", $ennp["tilattu_".$vrow["tunnus"]])."\t";

            $worksheet->write($excelrivi, $excelsarake, $ennp["tilattu_".$vrow["tunnus"]]);
            $excelsarake++;
          }

          $rivi .= str_replace(".", ",", $ennp["tilattu_oletus"])."\t";

          $worksheet->writeNumber($excelrivi, $excelsarake, $ennp["tilattu_oletus"]);
          $excelsarake++;
        }


        if (isset($korvaresult2) and is_resource($korvaresult2) and mysql_num_rows($korvaresult2) > 0) {

          mysql_data_seek($korvaresult2, 0);

          //tulostetaan korvaavat
          while ($korvarow = mysql_fetch_assoc($korvaresult2)) {
            // Korvaavien paikkojen valittujen varastojen paikkojen saldo yhteensä, mukaan tulee myös aina ne saldot jotka ei kuulu mihinkään varastoalueeseen
            $query = "SELECT sum(saldo) saldo, varastopaikat.tunnus
                      FROM tuotepaikat
                      LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus = tuotepaikat.varasto)
                      WHERE tuotepaikat.$varastot_yhtiot
                      and tuotepaikat.tuoteno='$korvarow[tuoteno]'
                      GROUP BY varastopaikat.tunnus
                      $varastot";
            $korvasaldoresult = pupe_query($query);

            $korva_sumsaldo = 0;

            while ($korvasaldorow = mysql_fetch_assoc($korvasaldoresult)) {
              $korva_sumsaldo += $korvasaldorow["saldo"];
            }

            $korvasaldorow["saldo"] = $korva_sumsaldo;

            // Saldolaskentaa tulevaisuuteen
            $query = "SELECT
                      sum(if(tilausrivi.tyyppi in ('O','W','M'), tilausrivi.varattu, 0)) tilattu,
                      sum(if(tilausrivi.tyyppi in ('L','V'), tilausrivi.varattu, 0)) varattu
                      FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
                      WHERE tilausrivi.yhtio = '$row[yhtio]'
                      {$varastowherelisa}
                      and tilausrivi.tyyppi  in ('L','V','O','W','M')
                      and tilausrivi.tuoteno = '$korvarow[tuoteno]'
                      and tilausrivi.varattu > 0";
            $presult = pupe_query($query);
            $prow = mysql_fetch_assoc($presult);

            //Korvaavien myynnnit
            $query  = "SELECT
                       sum(if (tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kpl,0)) kpl1,
                       sum(if (tilausrivi.laskutettuaika >= '$vva2-$kka2-$ppa2' and tilausrivi.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kpl,0)) kpl2,
                       sum(if (tilausrivi.laskutettuaika >= '$vva3-$kka3-$ppa3' and tilausrivi.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kpl,0)) kpl3,
                       sum(if (tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kpl,0)) kpl4
                       FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                       WHERE tilausrivi.yhtio        = '$row[yhtio]'
                       {$varastowherelisa}
                       and tilausrivi.tyyppi         = 'L'
                       and tilausrivi.tuoteno        = '$korvarow[tuoteno]'
                       and tilausrivi.laskutettuaika >= '$apvm'
                       and tilausrivi.laskutettuaika <= '$lpvm'";
            $asresult = pupe_query($query);
            $kasrow = mysql_fetch_assoc($asresult);

            if ($valitut["SARAKE56"] != '') {
              $rivi .= "\"$korvarow[tuoteno]\"\t";

              $worksheet->writeString($excelrivi, $excelsarake, $korvarow["tuoteno"], $format_bold);
              $excelsarake++;
            }

            if ($valitut["SARAKE57"] != '') {
              $rivi .= str_replace(".", ",", $korvasaldorow['saldo'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $korvasaldorow["saldo"]);
              $excelsarake++;
            }

            if ($valitut["SARAKE58"] != '') {
              $rivi .= str_replace(".", ",", $prow['varattu'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $prow["varattu"]);
              $excelsarake++;
            }

            if ($valitut["SARAKE59"] != '') {
              $rivi .= str_replace(".", ",", $prow['tilattu'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $prow["tilattu"]);
              $excelsarake++;
            }

            if ($valitut["SARAKE60"] != '') {
              $rivi .= str_replace(".", ",", $kasrow['kpl1'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl1"]);
              $excelsarake++;
            }

            if ($valitut["SARAKE61"] != '') {
              $rivi .= str_replace(".", ",", $kasrow['kpl2'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl2"]);
              $excelsarake++;
            }

            if ($valitut["SARAKE62"] != '') {
              $rivi .= str_replace(".", ",", $kasrow['kpl3'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl3"]);
              $excelsarake++;
            }

            if ($valitut["SARAKE63"] != '') {
              $rivi .= str_replace(".", ",", $kasrow['kpl4'])."\t";

              $worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl4"]);
              $excelsarake++;
            }

          }

        }

        $rivi .= "\r\n";
        $excelrivi++;
        $excelsarake = 0;
      }
    }

    flush();

    echo "<br>";

    $excelnimi = $worksheet->close();

    echo "<table>";
    echo "<tr><th>".t("Tallenna raportti (xlsx)").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Hälytysraportti.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";

    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $txtnimi = md5(uniqid(mt_rand(), true)).".txt";

    file_put_contents("/tmp/$txtnimi", $rivi);

    echo "<table>";
    echo "<tr><th>".t("Tallenna raportti (txt)").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Hälytysraportti.txt'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";

    echo "<table>";
    echo "<tr><th>".t("Tallenna raportti (csv)").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Hälytysraportti.csv'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";
  }

  // Nää muuttujat voi olla aika isoja joten unsetataan ne
  unset($rivi);

  $osasto     = '';
  $tuoryh     = '';
  $ytunnus   = '';
  $tuotemerkki = '';
  $tee     = 'X';
}

if ($tee == "" or $tee == "JATKA") {
  if (isset($muutparametrit)) {
    list($osasto, $tuoryh, $ytunnus, $tuotemerkki, $asiakasosasto, $asiakasno, $toimittaja) = explode('#', $muutparametrit);
  }

  $muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

  if ($tuoryh !='' or $osasto != '' or $ytunnus != '' or $tuotemerkki != '' or $KAIKKIJT != '' or $toimipaikka != "") {
    if ($ytunnus != '' and !isset($ylatila)) {

      require "../inc/kevyt_toimittajahaku.inc";

      if ($ytunnus != '') {
        $tee = "JATKA";
      }
    }
    elseif ($ytunnus != '' and isset($ylatila)) {
      $tee = "JATKA";
    }
    elseif (($tuoryh !='' or $osasto != '' or $tuotemerkki != '' or $KAIKKIJT != '') and "{$toimipaikka}" == "kaikki") {
      $tee = "JATKA";
    }
    elseif ($tuoryh !='' or $osasto != '' or $tuotemerkki != '' or $KAIKKIJT != '' or is_numeric($toimipaikka)) {
      $tee = "JATKA";
    }
    else {
      $tee = "";
    }
  }

  $muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

  if ($asiakasno != '' and $tee == "JATKA") {
    $muutparametrit .= $ytunnus;

    if ($asiakasid == "") {
      $ytunnus = $asiakasno;
    }

    require "inc/asiakashaku.inc";

    if ($ytunnus != '') {
      $tee = "JATKA";
      $asiakasno = $ytunnus;
      $ytunnus = $toimittaja;
    }
    else {
      $asiakasno = $ytunnus;
      $ytunnus = $toimittaja;

      $tee = "";
    }
  }
}

if ($tee == "") {

  echo "  <form method='post' autocomplete='off'>
      <br>".t("Valitse vähintään yksi seuraavista:")."
      <table>
      <tr><th>".t("Osasto")."</th><td>";

  // tehdään avainsana query
  $sresult = t_avainsana("OSASTO");

  echo "<select name='osasto'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_assoc($sresult)) {
    $sel = '';
    if ($osasto == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
  }
  echo "</select>";


  echo "</td></tr>
      <tr><th>".t("Tuoteryhmä")."</th><td>";

  //Tehdään osasto & tuoteryhmä pop-upit
  // tehdään avainsana query
  $sresult = t_avainsana("TRY");

  echo "<select name='tuoryh'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_assoc($sresult)) {
    $sel = '';
    if ($tuoryh == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
  }
  echo "</select>";


  echo "</td></tr>
      <tr><th>".t("Tuotemerkki")."</th><td>";

  //Tehdään osasto & tuoteryhmä pop-upit
  $sresult = t_avainsana("TUOTEMERKKI");

  echo "<select name='tuotemerkki'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_assoc($sresult)) {
    $sel = '';
    if ($tuotemerkki == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite]</option>";
  }
  echo "</select>";


  echo "</td></tr>";
  echo "<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";

  echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

  echo "<select name='abcrajaus'>";
  echo "<option  value=''>".t("Valitse")."</option>";

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

    echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

    echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

    echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

    echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
  }

  echo "</select></tr>";

  if ($onkolaajattoimipaikat) {

    echo "<tr>";

    echo "<th>", t("Toimipaikka"), "</th>";

    echo "<td><select name='toimipaikka'>";
    echo "<option value='kaikki'>", t("Kaikki"), "</option>";

    $sel = "";

    if (is_numeric($toimipaikka) and $toimipaikka == 0) {
      $sel = 'selected';
    }

    echo "<option value='0' {$sel}>".t('Ei toimipaikkaa')."</option>";

    $sel = "";

    while ($toimipaikkarow = mysql_fetch_assoc($toimipaikkares)) {

      if (!isset($toimipaikka) and $kukarow['toimipaikka'] == $toimipaikkarow['tunnus']) {
        $sel = ' selected';
      }
      elseif (isset($toimipaikka) and $toimipaikka == $toimipaikkarow['tunnus']) {
        $sel = ' selected';
      }
      else {
        $sel = '';
      }

      echo "<option value='{$toimipaikkarow['tunnus']}'{$sel}>{$toimipaikkarow['nimi']}</option>";
    }

    echo "</select></td>";
  }

  echo "<tr><td colspan='2' class='back'><br></td></tr>";
  echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa asiakaan myynnit").":</td></tr>";

  echo "<tr><th>".t("Asiakasosasto")."</th><td>";

  $query = "SELECT distinct osasto
            FROM asiakas
            WHERE yhtio='$kukarow[yhtio]' and osasto!=''
            order by osasto+0";
  $sresult = pupe_query($query);

  echo "<select name='asiakasosasto'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_assoc($sresult)) {
    $sel = '';
    if ($asiakasosasto == $srow["osasto"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[osasto]' $sel>$srow[osasto]</option>";
  }
  echo "</select>";


  echo "  </td></tr>
      <tr><th>".t("Asiakas")."</th><td><input type='text' size='20' name='asiakasno' value='$asiakasno'></td></tr>";


  // Maanteräspecial
  echo "<tr><td colspan='2' class='back'><br></td></tr>";
  echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa kaikki JT-rivit").":</td></tr>";
  echo "<tr><th>".t("Näytä kaikki JT rivit")."</th><td><input type='checkbox' name='KAIKKIJT' value='KAIKKIJT'></td></tr>";

  echo "  </table><br>
      <input type='submit' name='jatka' value = '".t("Jatka")."'>
      </form>";

}

if ($tee == "JATKA" or $tee == "RAPORTOI") {
  if ($ostoehdotus == "1")
    $kl = "CHECKED";
  if ($ostoehdotus == "2")
    $k2 = "CHECKED";
  if ($ostoehdotus == "3")
    $k3 = "CHECKED";
  if ($ostoehdotus == "4")
    $k4 = "CHECKED";

  if (!isset($ostoehdotus))
    $k3 = "CHECKED";

  if ($tuoryh != '') {
    // tehdään avainsana query
    $sresult = t_avainsana("TRY", "", "and avainsana.selite ='$tuoryh'");
    $srow = mysql_fetch_assoc($sresult);
  }
  if ($osasto != '') {
    // tehdään avainsana query
    $sresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$osasto'");
    $trow = mysql_fetch_assoc($sresult);
  }
  if ($toimittajaid != '') {
    $query = "SELECT nimi
              FROM toimi
              WHERE yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
    $sresult = pupe_query($query);
    $trow1 = mysql_fetch_assoc($sresult);
  }
  if ($asiakasid != '') {
    $query = "SELECT nimi
              FROM asiakas
              WHERE yhtio='$kukarow[yhtio]' and tunnus='$asiakasid'";
    $sresult = pupe_query($query);
    $trow2 = mysql_fetch_assoc($sresult);
  }

  if ($rappari != $edrappari) {
    unset($valitut);
    $tee = "JATKA";
  }

  if (!isset($edrappari) or ($rappari == "" and $edrappari != "")) {
    $defaultit = "PÄÄLLE";
  }

  $abcnimi = $ryhmanimet[$abcrajaus];

  echo "  <form method='post' autocomplete='off'>
      <input type='hidden' name='tee' value='RAPORTOI'>
      <input type='hidden' name='osasto' value='$osasto'>
      <input type='hidden' name='tuoryh' value='$tuoryh'>
      <input type='hidden' name='ytunnus' value='$ytunnus'>
      <input type='hidden' name='edrappari' value='$rappari'>
      <input type='hidden' name='toimittajaid' value='$toimittajaid'>
      <input type='hidden' name='asiakasid' value='$asiakasid'>
      <input type='hidden' name='tuotemerkki' value='$tuotemerkki'>
      <input type='hidden' name='asiakasno' value='$asiakasno'>
      <input type='hidden' name='asiakasosasto' value='$asiakasosasto'>
      <input type='hidden' name='abcrajaus' value='$abcrajaus'>
      <input type='hidden' name='abcrajaustapa' value='$abcrajaustapa'>
      <input type='hidden' name='KAIKKIJT' value='$KAIKKIJT'>";

  echo "<table>
      <tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
      <tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
      <tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
      <tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
      <tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
      <tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
      <tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>
      <tr><th>".t("JT")."</th><td colspan='3'>$KAIKKIJT</td></tr>";

  if ($onkolaajattoimipaikat) {

    if ("{$toimipaikka}" == "kaikki") {
      $toimipaikka_nimi = t("Kaikki toimipaikat");
    }
    elseif ($toimipaikka > 0) {
      $toimipaikka_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $toimipaikka);
      $toimipaikka_row = mysql_fetch_assoc($toimipaikka_res);

      $toimipaikka_nimi = $toimipaikka_row['nimi'];
    }
    else {
      $toimipaikka_nimi = t("Ei toimipaikkaa");
    }

    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<tr><th>", t("Toimipaikka"), "</th><td colspan='3'>{$toimipaikka_nimi}</td></tr>";
  }


  echo "  <tr><td class='back'><br></td></tr>";

  echo "  <tr>
      <td class='back'></td><th colspan='3'>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
      <td class='back'></td><th colspan='3'>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>
      <th>".t("Ostoehdotus")."</th></tr>";

  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark like 'PAIVAM##%'";
  $sresult = pupe_query($query);

  while ($srow = mysql_fetch_assoc($sresult)) {
    list($etuliite, $nimi, $paivamaara) = explode('##', $srow["selitetark"]);

    ${$nimi} = $paivamaara;
  }

  echo "  <tr><th>".t("Kausi 1")."</th>
      <td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
      <td><input type='text' name='kka1' value='$kka1' size='5'></td>
      <td><input type='text' name='vva1' value='$vva1' size='5'></td>
      <td class='back'> - </td>
      <td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
      <td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
      <td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
      <td><input type='radio' name='ostoehdotus' value='1' $k1></td></tr>";

  echo "  <tr><th>".t("Kausi 2")."</th>
      <td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
      <td><input type='text' name='kka2' value='$kka2' size='5'></td>
      <td><input type='text' name='vva2' value='$vva2' size='5'></td>
      <td class='back'> - </td>
      <td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
      <td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
      <td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
      <td><input type='radio' name='ostoehdotus' value='2' $k2></td></tr>";

  echo "  <tr><th>".t("Kausi 3")."</th>
      <td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
      <td><input type='text' name='kka3' value='$kka3' size='5'></td>
      <td><input type='text' name='vva3' value='$vva3' size='5'></td>
      <td class='back'> - </td>
      <td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
      <td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
      <td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
      <td><input type='radio' name='ostoehdotus' value='3' $k3></td></tr>";

  echo "  <tr><th>".t("Kausi 4")."</th>
      <td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
      <td><input type='text' name='kka4' value='$kka4' size='5'></td>
      <td><input type='text' name='vva4' value='$vva4' size='5'></td>
      <td class='back'> - </td>
      <td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
      <td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
      <td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
      <td><input type='radio' name='ostoehdotus' value='4' $k4></td></tr>";


  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'TALLENNAPAIVAM'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);


  $chk = "";
  if (($srow["selitetark"] == "TALLENNAPAIVAM" and $tee == "JATKA") or $valitut["TALLENNAPAIVAM"] != '') {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Tallenna päivämäärät:")."</th><td colspan='9'><input type='checkbox' name='valitut[TALLENNAPAIVAM]' value='TALLENNAPAIVAM' $chk></td></tr>";
  echo "  <tr><td class='back'><br></td></tr>";

  //Ostokausivalinnat
  $kaudet_oletus = array("A" => 1, "B" => 3, "C" => 4);
  $kaudet_kaikki = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 24);

  $kaudet = array();
  $kaulas = 1;

  foreach ($kaudet_oletus as $kaunimi => $kausi1) {

    echo "<tr><th>".t("Ostoehdotus")." $kaunimi:</th><td colspan='3'><select name='valitut[KAUSI$kaulas]'>";

    foreach ($kaudet_kaikki as $kausi2) {
      $query = "SELECT selitetark
                FROM avainsana
                WHERE yhtio    = '$kukarow[yhtio]'
                and laji       = 'HALYRAP'
                and selite     = '$rappari'
                and selitetark = 'KAUSI$kaulas##$kausi2'";
      $sresult = pupe_query($query);
      $srow = mysql_fetch_assoc($sresult);

      $chk = "";

      if (("KAUSI".$kaulas."##".$kausi2 == $srow["selitetark"] and $tee == "JATKA") or $valitut["KAUSI$kaulas##$kausi2"] != '' or ($kausi1 == $kausi2 and $srow["selitetark"] == "")) {
        $chk = "SELECTED";
      }

      echo "<option value='KAUSI$kaulas##$kausi2' $chk onchange='submit();'>$kausi2</option>";
    }

    echo "</select> ".t("kuukauden tarve")."</td></tr>";

    $kaulas++;
  }

  echo "  <tr><td class='back'><br></td></tr>";

  //Yhtiövalinnat
  $query  = "SELECT distinct yhtio, nimi
             from yhtio
             where konserni = '$yhtiorow[konserni]' and konserni != ''";
  $presult = pupe_query($query);

  $yhtiot   = "";
  $konsyhtiot = "";
  $vlask     = 0;

  if (mysql_num_rows($presult) > 0) {
    while ($prow = mysql_fetch_assoc($presult)) {

      $query = "SELECT selitetark
                FROM avainsana
                WHERE yhtio    = '$kukarow[yhtio]'
                and laji       = 'HALYRAP'
                and selite     = '$rappari'
                and selitetark = 'YHTIO##$prow[yhtio]'";
      $sresult = pupe_query($query);
      $srow = mysql_fetch_assoc($sresult);

      $chk = "";
      if (("YHTIO##".$prow["yhtio"] == $srow["selitetark"] and $tee == "JATKA") or $valitut["YHTIO##$prow[yhtio]"] != '' or $prow["yhtio"] == $kukarow["yhtio"]) {
        $chk = "CHECKED";
        $yhtiot .= "'".$prow["yhtio"]."',";
      }

      if ($vlask == 0) {
        echo "<tr><th rowspan='".mysql_num_rows($presult)."'>Huomioi yhtiön myynnit:</th>";
      }
      else {
        echo "<tr>";
      }

      echo "<td colspan='3'><input type='checkbox' name='valitut[YHTIO##$prow[yhtio]]' value='YHTIO##$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";

      $konsyhtiot .= "'".$prow["yhtio"]."',";
      $vlask++;
    }

    $yhtiot = substr($yhtiot, 0, -1);
    $konsyhtiot = substr($konsyhtiot, 0, -1);

    echo "  <tr><td class='back'><br></td></tr>";
  }
  else {
    $yhtiot = "'".$kukarow['yhtio']."'";
    $konsyhtiot = "'".$kukarow['yhtio']."'";
  }

  //Ajetaanko varastopaikoittain
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'PAIKOITTAIN'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "PAIKOITTAIN" and $tee == "JATKA") or $valitut["paikoittain"] != '') {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Aja raportti varastopaikoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[paikoittain]' value='PAIKOITTAIN' $chk></td></tr>";


  //Näytetäänkö poistetut tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'POISTETUT'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "POISTETUT" and $tee == "JATKA") or $valitut["poistetut"] != '' or $defaultit == "PÄÄLLE") {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistetut]' value='POISTETUT' $chk></td></tr>";

  //Näytetäänkö poistetut tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'POISTUVAT'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "POISTUVAT" and $tee == "JATKA") or $valitut["poistuvat"] != '' or $defaultit == "PÄÄLLE") {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Älä näytä poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistuvat]' value='POISTUVAT' $chk></td></tr>";

  //Näytetäänkö ehdokas-tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'EHDOKAS'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "EHDOKAS" and $tee == "JATKA") or $valitut["ehdokas"] != '') {
    $chk = "CHECKED";
  }

  echo "<tr><th>", t("Älä näytä ehdokas-tuotteita"), "</th><td colspan='3'><input type='checkbox' name='valitut[ehdokas]' value='EHDOKAS' {$chk}></td></tr>";

  //Näytetäänkö ostoehdottamattomat tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'EI_OSTOEHD'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "EI_OSTOEHD" and $tee == "JATKA") or $valitut["ei_ostoehd"] != '' or $defaultit == "PÄÄLLE") {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Älä näytä ostoehdotukseen kuulumattomia tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[ei_ostoehd]' value='EI_OSTOEHD' $chk></td></tr>";

  //Näytetäänkö ei hinnastoon tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'EIHINNASTOON'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "EIHINNASTOON" and $tee == "JATKA") or $valitut["EIHINNASTOON"] != '' or $defaultit == "PÄÄLLE") {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='3'><input type='checkbox' name='valitut[EIHINNASTOON]' value='EIHINNASTOON' $chk></td></tr>";

  //Näytetäänkö ei varastoitavat tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'EIVARASTOITAVA'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "EIVARASTOITAVA" and $tee == "JATKA") or $valitut["EIVARASTOITAVA"] != '') {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Älä näytä tuotteita joita ei varastoida")."</th><td colspan='3'><input type='checkbox' name='valitut[EIVARASTOITAVA]' value='EIVARASTOITAVA' $chk></td></tr>";

  //Näytetäänkö poistuvat tuotteet
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'EHDOTETTAVAT'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if ((mysql_num_rows($sresult) > 0 and $srow["selitetark"] == "EHDOTETTAVAT" and $tee == "JATKA") or $valitut["EHDOTETTAVAT"] != '') {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Näytä vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='valitut[EHDOTETTAVAT]' value='EHDOTETTAVAT' $chk></td></tr>";


  //Näytetäänkö ostot varastoittain
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'OSTOTVARASTOITTAIN'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "OSTOTVARASTOITTAIN" and $tee == "JATKA") or $valitut["OSTOTVARASTOITTAIN"] != '') {
    $chk = "CHECKED";
  }
  echo "<tr><th>".t("Näytä tilatut varastoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[OSTOTVARASTOITTAIN]' $chk></td></tr>";

  if ($abcrajaus != "") {

    echo "<tr><td class='back'><br></td></tr>";
    echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

    //näytetäänkö uudet tuotteet
    $query = "SELECT selitetark
              FROM avainsana
              WHERE yhtio    = '$kukarow[yhtio]'
              and laji       = 'HALYRAP'
              and selite     = '$rappari'
              and selitetark = 'UUDETTUOTTEET'";
    $sresult = pupe_query($query);
    $srow = mysql_fetch_assoc($sresult);

    $chk = "";
    if (($srow["selitetark"] == "UUDETTUOTTEET" and $tee == "JATKA") or $valitut["UUDETTUOTTEET"] != '') {
      $chk = "CHECKED";
    }

    echo "<tr><th>".t("Älä listaa 12kk sisällä perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[UUDETTUOTTEET]' value='UUDETTUOTTEET' $chk></td></tr>";

    //näytetäänkö uudet tuotteet
    $query = "SELECT selitetark
              FROM avainsana
              WHERE yhtio    = '$kukarow[yhtio]'
              and laji       = 'HALYRAP'
              and selite     = '$rappari'
              and selitetark = 'VAINUUDETTUOTTEET'";
    $sresult = pupe_query($query);
    $srow = mysql_fetch_assoc($sresult);

    $chk = "";
    if (($srow["selitetark"] == "VAINUUDETTUOTTEET" and $tee == "JATKA") or $valitut["VAINUUDETTUOTTEET"] != '') {
      $chk = "CHECKED";
    }

    echo "<tr><th>".t("Listaa vain 12kk sisällä perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='valitut[VAINUUDETTUOTTEET]' value='VAINUUDETTUOTTEET' $chk></td></tr>";
  }

  //Näytetäänkö ostot varastoittain
  $query = "SELECT selitetark
            FROM avainsana
            WHERE yhtio    = '$kukarow[yhtio]'
            and laji       = 'HALYRAP'
            and selite     = '$rappari'
            and selitetark = 'SALDOLLISET'";
  $sresult = pupe_query($query);
  $srow = mysql_fetch_assoc($sresult);

  $chk = "";
  if (($srow["selitetark"] == "SALDOLLISET" and $tee == "JATKA") or $valitut["SALDOLLISET"] != '') {
    $chk = "CHECKED";
  }

  echo "<tr>";
  echo "<th>", t("Näytä vain tuotteet joilla on saldoa"), "</th>";
  echo "<td colspan='3'><input type='checkbox' name='valitut[SALDOLLISET]' {$chk}></td>";
  echo "<td colspan='5' class='back'></td>";
  echo "</tr>";

  echo "<tr><th>".t("Päätoimittajarajaus")."</th><td colspan='3'><input type='checkbox' name='nayta_vain_ykkostoimittaja' value='JOO'/></tr></td>";
  echo "<tr><td class='back'><br></td></tr>";


  //Valitaan varastot joiden saldot huomioidaan
  //Tutkitaan onko käyttäjä klikannut useampaa yhtiötä
  if ($konsyhtiot  != '') {
    $konsyhtiot = " yhtio in (".$konsyhtiot.") ";
  }
  else {
    $konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
  }

  if ($onkolaajattoimipaikat and isset($toimipaikka) and "{$toimipaikka}" != "kaikki") {
    $toimipaikkalisa = "AND toimipaikka = '{$toimipaikka}'";
  }
  else {
    $toimipaikkalisa = "";
  }

  $query = "SELECT *
            FROM varastopaikat
            WHERE {$konsyhtiot}
            {$toimipaikkalisa}
            ORDER BY yhtio, tyyppi, nimitys";
  $vtresult = pupe_query($query);

  if ($onkolaajattoimipaikat and isset($toimipaikka) and mysql_num_rows($vtresult) == 0) {

    $query = "SELECT *
              FROM varastopaikat
              WHERE {$konsyhtiot}
              AND toimipaikka = 0
              ORDER BY yhtio, tyyppi, nimitys";
    $vtresult = pupe_query($query);
  }

  $vlask = 0;

  while ($vrow = mysql_fetch_assoc($vtresult)) {
    $query = "SELECT selitetark
              FROM avainsana
              WHERE yhtio    = '$kukarow[yhtio]'
              and laji       = 'HALYRAP'
              and selite     = '$rappari'
              and selitetark = 'VARASTO##$vrow[tunnus]'";
    $sresult = pupe_query($query);
    $srow = mysql_fetch_assoc($sresult);

    $chk = "";
    if (("VARASTO##".$vrow["tunnus"] == $srow["selitetark"]  and $tee == "JATKA") or $valitut["VARASTO##$vrow[tunnus]"] != '' or ($defaultit == "PÄÄLLE" and $vrow["yhtio"] == $kukarow["yhtio"])) {
      $chk = "CHECKED";
    }

    if ($vlask == 0) {

      $query = "SELECT selitetark
                FROM avainsana
                WHERE yhtio    = '{$kukarow['yhtio']}'
                and laji       = 'HALYRAP'
                and selite     = '{$rappari}'
                and selitetark = 'VARASTOHUOMIO'";
      $sresult = pupe_query($query);
      $srow = mysql_fetch_assoc($sresult);

      $_chk = "";

      if (($srow["selitetark"] == "VARASTOHUOMIO" and $tee == "JATKA") or $valitut["VARASTOHUOMIO"] != '') {
        $_chk = "CHECKED";
      }

      echo "<tr><th>", t("Varastovalinta huomioidaan kaikissa laskennoissa"), "</th>";
      echo "<td colspan='3'><input type='checkbox' name='valitut[VARASTOHUOMIO]' value='VARASTOHUOMIO' {$_chk} />";

      echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot varastossa:")."</th>";
    }
    else {
      echo "<tr>";
    }

    echo "<td colspan='3'><input type='checkbox' name='valitut[VARASTO##$vrow[tunnus]]' value='VARASTO##$vrow[tunnus]' $chk> $vrow[nimitys] ($vrow[yhtio])</td></tr>";

    $vlask++;
  }

  echo "</table><br><br>";
  echo "<table><tr>";
  echo "<th colspan='4'>";
  echo t("Omat hälytysraportit");

  echo "<span style='float: right;'>";
  echo t("Ruksaa kaikki"), " ";
  echo "<input type='checkbox' class='valitut_checkbox_kaikki' />";
  echo "</span>";

  echo "</th></tr>";

  if (isset($POISTA) and isset($rappari) and $rappari != "") {
    $query = "DELETE FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              and laji    = 'HALYRAP'
              AND selite  = '$rappari'";
    pupe_query($query, $masterlink);

    $rappari = "";

    echo "<font class='error'>".t("Raporttipohja poistettu")."! </font><br>";
  }

  echo "<tr><th>".t("Luo uusi oma raportti").":</th><td colspan='3'><input type='text' size='40' name='uusirappari' value=''></td></tr>";
  echo "<tr><th>".t("Valitse raportti").":</th><td colspan='3'>";

  // Haetaan tallennetut hälyrapit
  $query = "SELECT distinct selite, concat('(',replace(selite, '##',') ')) nimi
            FROM avainsana
            WHERE yhtio = '$kukarow[yhtio]'
            and laji    = 'HALYRAP'
            ORDER BY selite";
  $sresult = pupe_query($query);

  echo "<select name='rappari' onchange='submit()'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_assoc($sresult)) {

    $sel = '';
    if ($rappari == $srow["selite"]) {
      $sel = "selected";
    }

    echo "<option value='$srow[selite]' $sel>$srow[nimi]</option>";
  }

  echo "</select>";

  echo "<input type='submit' name='POISTA' value = '".t("Poista valittu raporttipohja")."'>";

  echo "</td></tr>";

  echo "<script type='text/javascript'>
          $(function() {
            $('input.valitut_checkbox_kaikki').on('click', function() {
              if ($(this).is(':checked')) {
                $('input.valitut_checkbox').attr('checked', true);
              }
              else {
                $('input.valitut_checkbox').attr('checked', false);
              }
            });
          });
        </script>";

  $lask = 0;
  echo "<tr>";

  foreach ($sarakkeet as $key => $sarake) {

    $query = "SELECT selitetark
              FROM avainsana
              WHERE yhtio    = '$kukarow[yhtio]'
              and laji       = 'HALYRAP'
              and selite     = '$rappari'
              and selitetark = '$key'";
    $sresult = pupe_query($query);

    $sel = "";
    if (mysql_num_rows($sresult) == 1 or $rappari == "") {
      $sel = "CHECKED";
    }

    if ($lask % 4 == 0 and $lask != 0) {
      echo "</tr><tr>";
    }

    echo "<td><input type='checkbox' class='valitut_checkbox' name='valitut[$key]' value='$key' $sel>".ucfirst($sarake)."</td>";
    $lask++;
  }

  echo "</tr>";
  echo "</table>";
  echo "<br>";

  echo "<input type='submit' name='RAPORTOI' value = '".t("Aja hälytysraportti")."'>
  </form>";
}

require "inc/footer.inc";
