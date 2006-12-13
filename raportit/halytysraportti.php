<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Hälytysraportti")."</font><hr>";

// ABC luokkanimet
$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

// Tarvittavat päivämäärät
if (!isset($kka1)) $kka1 = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva1)) $vva1 = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa1)) $ppa1 = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl1)) $kkl1 = date("m");
if (!isset($vvl1)) $vvl1 = date("Y");
if (!isset($ppl1)) $ppl1 = date("d");

if (!isset($kka2)) $kka2 = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($vva2)) $vva2 = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($ppa2)) $ppa2 = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($kkl2)) $kkl2 = date("m");
if (!isset($vvl2)) $vvl2 = date("Y");
if (!isset($ppl2)) $ppl2 = date("d");

if (!isset($kka3)) $kka3 = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($vva3)) $vva3 = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($ppa3)) $ppa3 = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($kkl3)) $kkl3 = date("m");
if (!isset($vvl3)) $vvl3 = date("Y");
if (!isset($ppl3)) $ppl3 = date("d");

if (!isset($kka4)) $kka4 = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($vva4)) $vva4 = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($ppa4)) $ppa4 = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($kkl4)) $kkl4 = date("m");
if (!isset($vvl4)) $vvl4 = date("Y");
if (!isset($ppl4)) $ppl4 = date("d");

//Edellisen vuoden vastaavat kaudet
$kka1ed = date("m",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$vva1ed = date("Y",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$ppa1ed = date("d",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$kkl1ed = date("m",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$vvl1ed = date("Y",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$ppl1ed = date("d",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));

$kka2ed = date("m",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$vva2ed = date("Y",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$ppa2ed = date("d",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$kkl2ed = date("m",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
$vvl2ed = date("Y",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
$ppl2ed = date("d",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));

$kka3ed = date("m",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$vva3ed = date("Y",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$ppa3ed = date("d",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$kkl3ed = date("m",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
$vvl3ed = date("Y",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
$ppl3ed = date("d",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));

$kka4ed = date("m",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$vva4ed = date("Y",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$ppa4ed = date("d",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$kkl4ed = date("m",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
$vvl4ed = date("Y",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
$ppl4ed = date("d",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));


//katotaan pienin alkupvm ja isoin loppupvm
$apaiva1 = (int) date('Ymd',mktime(0,0,0,$kka1,$ppa1,$vva1));
$apaiva2 = (int) date('Ymd',mktime(0,0,0,$kka2,$ppa2,$vva2));
$apaiva3 = (int) date('Ymd',mktime(0,0,0,$kka3,$ppa3,$vva3));
$apaiva4 = (int) date('Ymd',mktime(0,0,0,$kka4,$ppa4,$vva4));
$apaiva5 = (int) date('Ymd',mktime(0,0,0,$kka1ed,$ppa1ed,$vva1ed));
$apaiva6 = (int) date('Ymd',mktime(0,0,0,$kka2ed,$ppa2ed,$vva2ed));
$apaiva7 = (int) date('Ymd',mktime(0,0,0,$kka3ed,$ppa3ed,$vva3ed));
$apaiva8 = (int) date('Ymd',mktime(0,0,0,$kka4ed,$ppa4ed,$vva4ed));

$lpaiva1 = (int) date('Ymd',mktime(0,0,0,$kkl1,$ppl1,$vvl1));
$lpaiva2 = (int) date('Ymd',mktime(0,0,0,$kkl2,$ppl2,$vvl2));
$lpaiva3 = (int) date('Ymd',mktime(0,0,0,$kkl3,$ppl3,$vvl3));
$lpaiva4 = (int) date('Ymd',mktime(0,0,0,$kkl4,$ppl4,$vvl4));
$lpaiva5 = (int) date('Ymd',mktime(0,0,0,$kkl1ed,$ppl1ed,$vvl1ed));
$lpaiva6 = (int) date('Ymd',mktime(0,0,0,$kkl2ed,$ppl2ed,$vvl2ed));
$lpaiva7 = (int) date('Ymd',mktime(0,0,0,$kkl3ed,$ppl3ed,$vvl3ed));
$lpaiva8 = (int) date('Ymd',mktime(0,0,0,$kkl4ed,$ppl4ed,$vvl4ed));


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

$apvm = substr($apienin,0,4)."-".substr($apienin,4,2)."-".substr($apienin,6,2);
$lpvm = substr($lsuurin,0,4)."-".substr($lsuurin,4,2)."-".substr($lsuurin,6,2);

// Tulostettavat sarakkeet
$sarakkeet = array();

//Voidaan tarvita jotain muuttujaa täältä
if (isset($muutparametrit)) {
	list($temp_osasto,$temp_tuoryh,$temp_ytunnus,$temp_tuotemerkki,$temp_asiakasosasto,$temp_asiakasno,$temp_toimittaja) = explode('#', $muutparametrit);
}

$sarakkeet["SARAKE1"] = t("osasto")."\t";
$sarakkeet["SARAKE2"] = t("tuoteryhma")."\t";
$sarakkeet["SARAKE3"] = t("tuotemerkki")."\t";
$sarakkeet["SARAKE4"] = t("tahtituote")."\t";
$sarakkeet["SARAKE4B"] = t("status")."\t";
$sarakkeet["SARAKE4C"] = t("abc")."\t";
$sarakkeet["SARAKE4CA"] = t("abc osasto")."\t";
$sarakkeet["SARAKE4CB"] = t("abc try")."\t";
$sarakkeet["SARAKE4D"] = t("luontiaika")."\t";
$sarakkeet["SARAKE5"] = t("saldo")."\t";
$sarakkeet["SARAKE6"] = t("halytysraja")."\t";
$sarakkeet["SARAKE7"] = t("tilauksessa")."\t";
$sarakkeet["SARAKE8"] = t("ennpois")."\t";
$sarakkeet["SARAKE9"] = t("jt")."\t";

$ehd_kausi_o1 = "1";
$ehd_kausi_o2 = "3";
$ehd_kausi_o3 = "4";

if($valitut["KAUSI1"] != '') {
	list($al, $lo) = explode("##", $valitut["KAUSI1"]);

	$ehd_kausi_o1	= $lo;
}
if($valitut["KAUSI2"] != '') {
	list($al, $lo) = explode("##", $valitut["KAUSI2"]);

	$ehd_kausi_o2	= $lo;
}
if($valitut["KAUSI3"] != '') {
	list($al, $lo) = explode("##", $valitut["KAUSI3"]);

	$ehd_kausi_o3	= $lo;
}

$sarakkeet["SARAKE10"] = t("ostettava $ehd_kausi_o1 kk")."\t";
$sarakkeet["SARAKE11"] = t("ostettava $ehd_kausi_o2 kk")."\t";
$sarakkeet["SARAKE12"] = t("ostettava $ehd_kausi_o3 kk")."\t";

$sarakkeet["SARAKE13"] = t("ostettava haly")."\t";
$sarakkeet["SARAKE14"] = t("osto_era")."\t";
$sarakkeet["SARAKE15"] = t("myynti_era")."\t";
$sarakkeet["SARAKE16"] = t("toimittaja")."\t";
$sarakkeet["SARAKE17"] = t("toim_tuoteno")."\t";
$sarakkeet["SARAKE18"] = t("nimitys")."\t";
$sarakkeet["SARAKE18B"] = t("toim_nimitys")."\t";
$sarakkeet["SARAKE19"] = t("ostohinta")."\t";
$sarakkeet["SARAKE20"] = t("myyntihinta")."\t";
$sarakkeet["SARAKE21"] = t("epakurantti1pvm")."\t";
$sarakkeet["SARAKE22"] = t("epakurantti2pvm")."\t";
$sarakkeet["SARAKE23"] = t("oletussaldo")."\t";
$sarakkeet["SARAKE24"] = t("hyllypaikka")."\t";

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
$sarakkeet["SARAKE45"] = t("tuotekerroin")."\t";
$sarakkeet["SARAKE46"] = t("ennakkotilauksessa")."\t";
$sarakkeet["SARAKE47"] = t("aleryhmä")."\t";

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

$sarakkeet["SARAKE56"] = t("Korvaavat Tuoteno")."\t";
$sarakkeet["SARAKE57"] = t("Korvaavat Saldo")."\t";
$sarakkeet["SARAKE58"] = t("Korvaavat Ennpois")."\t";
$sarakkeet["SARAKE59"] = t("Korvaavat Tilauksessa")."\t";
$sarakkeet["SARAKE60"] = t("Korvaavat Myyty")." $kausi1\t";
$sarakkeet["SARAKE61"] = t("Korvaavat Myyty")." $kausi2\t";
$sarakkeet["SARAKE62"] = t("Korvaavat Myyty")." $kausi3\t";
$sarakkeet["SARAKE63"] = t("Korvaavat Myyty")." $kausi4\t";

// aika karseeta, mutta katotaan voidaanko tällästä optiota näyttää yks tosi firma specific juttu
$query = "describe yhteensopivuus_rekisteri";
$res = mysql_query($query);

if (mysql_error() == "") {
	$query = "select count(*) kpl from yhteensopivuus_rekisteri where yhtio='$kukarow[yhtio]'";
	$res = mysql_query($query);
	$row = mysql_fetch_array($res);
	if ($row["kpl"] > 0) {
		$sarakkeet["SARAKE64"] = "Rekisteröidyt kpl\t";
	}
}

//Jos halutaan tallentaa päivämäärät profiilin taakse
if ($valitut["TALLENNAPAIVAM"] == "TALLENNAPAIVAM") {
	//Tehdään päivämääristä tallennettavia
	$paivamaarat = array('ppa1','kka1','vva1','ppl1','kkl1','vvl1','ppa2','kka2','vva2','ppl2','kkl2','vvl2','ppa3','kka3','vva3','ppl3','kkl3','vvl3','ppa4','kka4','vva4','ppl4','kkl4','vvl4');
	foreach($paivamaarat as $paiva) {
		$valitut[] = "PAIVAM##".$paiva."##".${$paiva};
	}
}


// Tässä luodaan uusi raporttiprofiili
if ($tee == "RAPORTOI" and $uusirappari != '') {

	$rappari = $kukarow["kuka"]."##".$uusirappari;

	foreach($valitut as $val) {
		$query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='HALYRAP', selite='$rappari', selitetark='$val'";
		$res = mysql_query($query) or pupe_error($query);
	}
}


//Ajetaan itse raportti
if ($tee == "RAPORTOI" and isset($RAPORTOI)) {

	if ($rappari != '') {
		$query = "DELETE FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='HALYRAP' and selite='$rappari'";
		$res = mysql_query($query) or pupe_error($query);

		foreach($valitut as $val) {
			$query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='HALYRAP', selite='$rappari', selitetark='$val'";
			$res = mysql_query($query) or pupe_error($query);
		}
	}

	if ($tuoryh != '') {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='TRY'
					and selite  = '$tuoryh'";
		$sresult = mysql_query($query) or pupe_error($query);
		$srow = mysql_fetch_array($sresult);
	}
	if ($osasto != '') {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='OSASTO'
					and selite  = '$osasto'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($sresult);
	}
	if ($toimittajaid != '') {
		$query = "	SELECT nimi
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$toimittajaid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow1 = mysql_fetch_array($sresult);
	}
	if ($asiakasid != '') {
		$query = "	SELECT nimi
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$asiakasid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow2 = mysql_fetch_array($sresult);
	}

	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<table>
			<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
			<tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
			<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
			<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
			<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
			<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
			<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>";

	echo "	</table><br>";
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
	if ($valitut["EIHINNASTOON"] != '') {
		$lisaa .= " and tuote.hinnastoon != 'E' ";
	}
	// Listaa vain äskettäin perustetut tuotteet:
	if ($valitut["VAINUUDETTUOTTEET"] != '') {
		$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
	}
	// Älä listaa äskettäin perustettuja tuotteita:
	if ($valitut["UUDETTUOTTEET"] != '') {
		$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
	}

	if ($toimittajaid != '') {
		$lisaa2 .= " JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid' ";
	}

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	//Eli haetaan connect.inc uudestaan tässä
	require("../inc/connect.inc");

	//Yhtiövalinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]' and konserni != ''";
	$presult = mysql_query($query) or pupe_error($query);

	$yhtiot 	= "";
	$konsyhtiot = "";

	if (mysql_num_rows($presult) > 0) {
		while ($prow = mysql_fetch_array($presult)) {
			if ($valitut["YHTIO##$prow[yhtio]"] == "YHTIO##".$prow["yhtio"]) {
				$yhtiot .= "'".$prow["yhtio"]."',";
			}
			$konsyhtiot .= "'".$prow["yhtio"]."',";
		}

		$yhtiot = substr($yhtiot,0,-1);
		$yhtiot = " yhtio in ($yhtiot) ";

		$konsyhtiot = substr($konsyhtiot,0,-1);
		$konsyhtiot = " yhtio in ($konsyhtiot) ";
	}
	else {
		$yhtiot = "'".$kukarow["yhtio"]."'";
		$yhtiot = " yhtio in ($yhtiot) ";

		$konsyhtiot = "'".$kukarow["yhtio"]."'";
		$konsyhtiot = " yhtio in ($konsyhtiot) ";
	}

	//Katsotaan valitut varastot
	$query = "	SELECT *
				FROM varastopaikat
				WHERE $konsyhtiot";
	$vtresult = mysql_query($query) or pupe_error($query);

	$varastot 			= "";
	$varastot_yhtiot 	= "";

	while ($vrow = mysql_fetch_array($vtresult)) {
		if ($valitut["VARASTO##$vrow[tunnus]"] == "VARASTO##".$vrow["tunnus"]) {
			$varastot .= "'".$vrow["tunnus"]."',";
			$varastot_yhtiot .= "'".$vrow["yhtio"]."',";
		}
	}

	$varastot 		 = substr($varastot,0,-1);
	$varastot_yhtiot = substr($varastot_yhtiot,0,-1);

	$paikoittain = $valitut["paikoittain"];

	if ($varastot == "" and $paikoittain != "") {
		echo "<font class='error'>".t("VIRHE: Ajat hälytysraportin varastopaikoittain, mutta et valinnut yhtään varastoa.")."</font>";
		exit;
	}

	// katotaan JT:ssä olevat tuotteet
	$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
				FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
				JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
				WHERE tilausrivi.$yhtiot
				and tyyppi = 'L'
				and var = 'J'
				and jt > 0";
	$vtresult = mysql_query($query) or pupe_error($query);
	$vrow = mysql_fetch_array($vtresult);

	$jt_tuotteet = "''";
	if ($vrow[0] != "") {
		$jt_tuotteet = $vrow[0];
	}

	if ($abcrajaus != "") {
		// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
		$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
					and abc_aputaulu.tuoteno = tuote.tuoteno
					and abc_aputaulu.tyyppi = 'TK'
					and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
	}
	else {
		$abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = 'TK') ";
	}

	$varastot 		 = " HAVING varastopaikat.tunnus in ($varastot) or varastopaikat.tunnus is null ";
	$varastot_yhtiot = " yhtio in ($varastot_yhtiot) ";

	//Ajetaan raportti tuotteittain
	if ($paikoittain == '') {
		$query = "	select
					tuote.yhtio,
					tuote.tuoteno,
					tuote.halytysraja,
					tuote.tahtituote,
					tuote.status,
					tuote.nimitys,
					tuote.myynti_era,
					tuote.myyntihinta,
					tuote.epakurantti1pvm,
					tuote.epakurantti2pvm,
					tuote.tuotemerkki,
					tuote.osasto,
					tuote.try,
					tuote.aleryhma,
					tuote.kehahin,
					abc_aputaulu.luokka abcluokka,
					abc_aputaulu.luokka_osasto abcluokka_osasto,
					abc_aputaulu.luokka_try abcluokka_try,
					tuote.luontiaika
					FROM tuote
					$lisaa2
					$abcjoin
					LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
					WHERE
					tuote.$yhtiot
					$lisaa
					and tuote.ei_saldoa = ''
					ORDER BY id, tuote.tuoteno";
	}
	//Ajetaan raportti tuotteittain, varastopaikoittain
	else {

		$query = "	select
					tuote.yhtio,
					tuote.tuoteno,
					concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso) varastopaikka,
					tuotepaikat.halytysraja,
					tuote.tahtituote,
					tuote.status,
					tuote.nimitys,
					tuote.myynti_era,
					tuote.myyntihinta,
					tuote.epakurantti1pvm,
					tuote.epakurantti2pvm,
					tuote.tuotemerkki,
					tuote.osasto,
					tuote.try,
					tuote.aleryhma,
					tuote.kehahin,
					varastopaikat.tunnus,
					abc_aputaulu.luokka abcluokka,
					abc_aputaulu.luokka_osasto abcluokka_osasto,
					abc_aputaulu.luokka_try abcluokka_try,
					tuote.luontiaika
					FROM tuote
					$lisaa2
					$abcjoin
					JOIN tuotepaikat ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno
					LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
					WHERE
					tuote.$yhtiot
					$lisaa
					and tuote.ei_saldoa = ''
					$varastot
					order by id, tuote.tuoteno, varastopaikka";
	}
	$res = mysql_query($query) or pupe_error($query);

	if ($valitut["poistetut"] != '') {
		echo "<font class='message'>".t("Vain aktiiviset tuotteet").".<br>";
	}
	if ($valitut["VAINUUDETTUOTTEET"] != '') {
		echo "<font class='message'>".t("Listaa vain 12kk sisällä perustetut tuotteet").".<br>";
	}
	if ($valitut["UUDETTUOTTEET"] != '') {
		echo "<font class='message'>".t("Ei listata 12kk sisällä perustettuja tuotteita").".<br>";
	}

	if ($abcrajaus != "") {

		echo "<font class='message'>".t("ABC-luokka tai ABC-osastoluokka tai ABC-tuoteryhmäluokka >=")." $ryhmanimet[$abcrajaus] ".t("tai sitä on jälkitoimituksessa");

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

	$rivi = "";
	$rivi .= t("tuoteno")."\t";

	if ($paikoittain != '') {
		$rivi .= t("Varastopaikka")."\t";
	}

	foreach ($valitut as $val) {
		$rivi .= $sarakkeet[$val];
	}

	$rivi .= "\r\n";

	// arvioidaan kestoa
	$ajat      = array();
	$arvio     = array();
	$timeparts = explode(" ",microtime());
	$alkuaika  = $timeparts[1].substr($timeparts[0],1);
	$joukko    = 100; //kuinka monta riviä otetaan keskiarvoon

	while ($row = mysql_fetch_array($res)) {

		$timeparts = explode(" ",microtime());
		$alku      = $timeparts[1].substr($timeparts[0],1);

		$lisa = "";

		if ($paikoittain != '') {
			$lisa = " and concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso)='$row[varastopaikka]' ";
		}

		//toimittajatiedot
		if ($toimittajaid == '') {
			$query = "	SELECT group_concat(tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
						group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '/') osto_era,
						group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
						group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
						group_concat(distinct tuotteen_toimittajat.ostohinta order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
						group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin
						FROM tuotteen_toimittajat
						WHERE yhtio = '$row[yhtio]'
						and tuoteno = '$row[tuoteno]'";
		}
		else {
			$query = "	SELECT tuotteen_toimittajat.toimittaja,
						tuotteen_toimittajat.osto_era,
						tuotteen_toimittajat.toim_tuoteno,
						tuotteen_toimittajat.toim_nimitys,
						tuotteen_toimittajat.ostohinta,
						tuotteen_toimittajat.tuotekerroin
						FROM tuotteen_toimittajat
						WHERE yhtio = '$row[yhtio]'
						and tuoteno = '$row[tuoteno]'
						and liitostunnus = '$toimittajaid'";
		}
		$result   = mysql_query($query) or pupe_error($query);
		$toimirow = mysql_fetch_array($result);


		$row['toimittaja'] 		= $toimirow['toimittaja'];
		$row['osto_era'] 		= $toimirow['osto_era'];
		$row['toim_tuoteno'] 	= $toimirow['toim_tuoteno'];
		$row['toim_nimitys'] 	= $toimirow['toim_nimitys'];
		$row['ostohinta'] 		= $toimirow['ostohinta'];
		$row['tuotekerroin'] 	= $toimirow['tuotekerroin'];

		///* Myydyt kappaleet *///
		$query = "	SELECT
					sum(if (laadittu >= '$vva1-$kka1-$ppa1' and laadittu <= '$vvl1-$kkl1-$ppl1' and var='P', tilkpl,0)) puutekpl1,
					sum(if (laadittu >= '$vva2-$kka2-$ppa2' and laadittu <= '$vvl2-$kkl2-$ppl2' and var='P', tilkpl,0)) puutekpl2,
					sum(if (laadittu >= '$vva3-$kka3-$ppa3' and laadittu <= '$vvl3-$kkl3-$ppl3' and var='P', tilkpl,0)) puutekpl3,
					sum(if (laadittu >= '$vva4-$kka4-$ppa4' and laadittu <= '$vvl4-$kkl4-$ppl4' and var='P', tilkpl,0)) puutekpl4,
					sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
					sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
					sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
					sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
					sum(if (laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl1,
					sum(if (laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) EDkpl2,
					sum(if (laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) EDkpl3,
					sum(if (laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) EDkpl4,
					sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kate,0)) kate1,
					sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kate,0)) kate2,
					sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kate,0)) kate3,
					sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kate,0)) kate4,
					sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta1,
					sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,rivihinta,0)) rivihinta2,
					sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,rivihinta,0)) rivihinta3,
					sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,rivihinta,0)) rivihinta4
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					WHERE yhtio = '$row[yhtio]'
					and tyyppi='L'
					and tuoteno = '$row[tuoteno]'
					and ((laskutettuaika >= '$apvm' and laskutettuaika <= '$lpvm') or laskutettuaika = '0000-00-00')
					$lisa";
		$result   = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		if ($laskurow['rivihinta1'] <> 0){
			$katepros1 = round($laskurow['kate1'] / $laskurow['rivihinta1'] * 100,0);
		}
		else ($katepros1 = 0);

		if ($laskurow['rivihinta2'] <> 0){
			$katepros2 = round($laskurow['kate2'] / $laskurow['rivihinta2'] * 100,0);
		}
		else ($katepros2 = 0);

		if ($laskurow['rivihinta3'] <> 0){
			$katepros3 = round($laskurow['kate3'] / $laskurow['rivihinta3'] * 100,0);
		}
		else ($katepros3 = 0);

		if ($laskurow['rivihinta4'] <> 0){
				$katepros4 = round($laskurow['kate4'] / $laskurow['rivihinta4'] * 100,0);
		}
		else ($katepros4 = 0);

		///* Kulutetut kappaleet *///
		$query = "	SELECT
					sum(if (toimitettuaika >= '$vva1-$kka1-$ppa1' and toimitettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
					sum(if (toimitettuaika >= '$vva2-$kka2-$ppa2' and toimitettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
					sum(if (toimitettuaika >= '$vva3-$kka3-$ppa3' and toimitettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
					sum(if (toimitettuaika >= '$vva4-$kka4-$ppa4' and toimitettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
					sum(if (toimitettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and toimitettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl1,
					sum(if (toimitettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and toimitettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) EDkpl2,
					sum(if (toimitettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and toimitettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) EDkpl3,
					sum(if (toimitettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and toimitettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) EDkpl4
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
					WHERE yhtio = '$row[yhtio]'
					and tyyppi='V'
					and tuoteno = '$row[tuoteno]'
					and ((toimitettuaika >= '$apvm' and toimitettuaika <= '$lpvm') or toimitettuaika = '0000-00-00')
					$lisa";
		$result   = mysql_query($query) or pupe_error($query);
		$kulutrow = mysql_fetch_array($result);

		//tilauksessa, ennakkopoistot ja jt
		$query = "	SELECT
					sum(if(tyyppi='O', varattu, 0)) tilattu,
					sum(if(tyyppi='L' or tyyppi='V', varattu, 0)) ennpois,
					sum(if(tyyppi='L' or tyyppi='G', jt, 0)) jt
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					WHERE yhtio = '$row[yhtio]'
 					and tyyppi in ('L','V','O','G')
					and tuoteno = '$row[tuoteno]'
					and laskutettuaika = '0000-00-00'
					and (varattu>0 or jt>0)";
		$result = mysql_query($query) or pupe_error($query);
		$ennp   = mysql_fetch_array($result);


		if ($paikoittain == '') {
			// Kaikkien valittujen varastojen paikkojen saldo yhteensä, mukaan tulee myös aina ne saldot jotka ei kuulu mihinkään varastoalueeseen
			$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
						FROM tuotepaikat
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
						and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
						WHERE tuotepaikat.$varastot_yhtiot
						and tuotepaikat.tuoteno='$row[tuoteno]'
						GROUP BY varastopaikat.tunnus
						$varastot";
			$result = mysql_query($query) or pupe_error($query);

			$sumsaldo = 0;

			while($saldo = mysql_fetch_array($result)) {
				$sumsaldo += $saldo["saldo"];
			}

			$saldo["saldo"] = $sumsaldo;
		}
		else {
			// Ajetaan varastopaikoittain eli tässä on just tän paikan saldo
			$query = "	select saldo
						from tuotepaikat
						where yhtio='$row[yhtio]'
						and tuoteno='$row[tuoteno]'
						$lisa";
			$result = mysql_query($query) or pupe_error($query);
			$saldo = mysql_fetch_array($result);
		}

		// oletuspaikan saldo ja hyllypaikka
		$query = "	select sum(saldo) osaldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
					from tuotepaikat
					where yhtio='$row[yhtio]'
					and tuoteno='$row[tuoteno]'
					and oletus='X'
					group by hyllyalue, hyllynro, hyllyvali, hyllytaso";
		$result = mysql_query($query) or pupe_error($query);
		$osaldo = mysql_fetch_array($result);

		if ($row['osto_era']==0) $row['osto_era']=1;

		///* lasketaan ehdotelma, mitä kannattaisi tilata *///
		//minkä kauden mukaan ostoehdotukset lasketaan
		$indeksi    = "kpl".$ostoehdotus;

		//Kausien ero sekunneissa
		$ero = strtotime(${vvl.$ostoehdotus}."-".${kkl.$ostoehdotus}."-".${ppl.$ostoehdotus}) - strtotime(${vva.$ostoehdotus}."-".${kka.$ostoehdotus}."-".${ppa.$ostoehdotus});

		//Kausien ero kuukausissa, tässä approximoidaan, että kuukausissa on keskimäärin 365/12 päivää
		$ero = $ero/60/60/24/(365/12);

		$ehd_kausi1	= "1";
		$ehd_kausi2 = "3";
		$ehd_kausi3 = "4";

		if($valitut["KAUSI1"] != '') {
			list($al, $lo) = explode("##", $valitut["KAUSI1"]);

			$ehd_kausi1	= $lo;
		}
		if($valitut["KAUSI2"] != '') {
			list($al, $lo) = explode("##", $valitut["KAUSI2"]);

			$ehd_kausi2	= $lo;
		}
		if($valitut["KAUSI3"] != '') {
			list($al, $lo) = explode("##", $valitut["KAUSI3"]);

			$ehd_kausi3	= $lo;
		}

		$ostettava1kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi1) - ($saldo['saldo'] + $ennp['tilattu'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];
		$ostettava3kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi2) - ($saldo['saldo'] + $ennp['tilattu'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];
		$ostettava4kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi3) - ($saldo['saldo'] + $ennp['tilattu'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];

		$ostettavahaly = ($row['halytysraja'] - ($saldo['saldo'] + $ennp['tilattu'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];

		// jos tuotteella on joku ostoerä pyöristellään ylospäin, että tilataan aina toimittajan haluama määrä
		if ($ostettava1kk > 0)	$ostettava1kk = ceil($ostettava1kk) * $row['osto_era'];
		else 					$ostettava1kk = 0;

		if ($ostettava3kk > 0)	$ostettava3kk = ceil($ostettava3kk) * $row['osto_era'];
		else 					$ostettava3kk = 0;

		if ($ostettava4kk > 0)	$ostettava4kk = ceil($ostettava4kk) * $row['osto_era'];
		else 					$ostettava4kk = 0;

		if ($ostettavahaly > 0)	$ostettavahaly = ceil($ostettavahaly) * $row['osto_era'];
		else 					$ostettavahaly = 0;


		//ennakkotilauksessa
		$query  = "	SELECT sum(tilkpl) tilkpl
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					WHERE yhtio='$row[yhtio]'
					and tyyppi='E'
					and tuoteno='$row[tuoteno]'
					and laskutettuaika = '0000-00-00'";
		$ennaresult = mysql_query($query) or pupe_error($query);
		$ennarow = mysql_fetch_array($ennaresult);

		//asiakkaan ostot
		if ($asiakasosasto != '') {
			$query  = "	SELECT
						sum(if(t.laskutettuaika >= '$vva1-$kka1-$ppa1' and t.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,t.kpl,0)) kpl1,
						sum(if(t.laskutettuaika >= '$vva2-$kka2-$ppa2' and t.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,t.kpl,0)) kpl2,
						sum(if(t.laskutettuaika >= '$vva3-$kka3-$ppa3' and t.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,t.kpl,0)) kpl3,
						sum(if(t.laskutettuaika >= '$vva4-$kka4-$ppa4' and t.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,t.kpl,0)) kpl4
						FROM tilausrivi t use index (yhtio_tyyppi_tuoteno_laskutettuaika),
						lasku l use index(PRIMARY),
						asiakas a use index (ytunnus_index)
						WHERE t.yhtio = '$row[yhtio]'
						and t.tyyppi = 'L'
						and t.tuoteno = '$row[tuoteno]'
						and t.laskutettuaika >= '$apvm'
						and t.laskutettuaika <= '$lpvm'
						and l.yhtio = t.yhtio
						and l.tunnus = t.uusiotunnus
						and a.ytunnus = l.ytunnus
						and a.yhtio = l.yhtio
						and a.osasto = '$asiakasosasto'";
			$asosresult = mysql_query($query) or pupe_error($query);
			$asosrow = mysql_fetch_array($asosresult);
		}
		if ($asiakasid != '') {
			$query  = "	SELECT sum(if(t.laskutettuaika >= '$vva1-$kka1-$ppa1' and t.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,t.kpl,0)) kpl1,
						sum(if(t.laskutettuaika >= '$vva2-$kka2-$ppa2' and t.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,t.kpl,0)) kpl2,
						sum(if(t.laskutettuaika >= '$vva3-$kka3-$ppa3' and t.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,t.kpl,0)) kpl3,
						sum(if(t.laskutettuaika >= '$vva4-$kka4-$ppa4' and t.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,t.kpl,0)) kpl4
						FROM tilausrivi t use index (yhtio_tyyppi_tuoteno_laskutettuaika),
						lasku l use index(PRIMARY)
						WHERE t.yhtio = '$row[yhtio]'
						and t.tyyppi = 'L'
						and t.tuoteno = '$row[tuoteno]'
						and t.laskutettuaika >= '$apvm'
						and t.laskutettuaika <= '$lpvm'
						and l.yhtio = t.yhtio
						and l.tunnus = t.otunnus
						and l.liitostunnus 	= '$asiakasid'";
			$asresult = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($asresult);
		}

		if ($valitut['EHDOTETTAVAT'] != '') {
			$rivinaytetaan = 0;
		}
		else {
			$rivinaytetaan = 1;
		}

		$apurivi = '';

		// kirjotettaan rivi
		$apurivi .= "\"$row[tuoteno]\"\t";

		if ($paikoittain != '') {
			$apurivi .= "\"$row[varastopaikka]\"\t";
		}

		if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";

		$abcnimi = $ryhmanimet[$row["abcluokka"]];
		$abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
		$abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];

		if($valitut["SARAKE1"] != '') $apurivi .= "\"$row[osasto]\"\t";
		if($valitut["SARAKE2"] != '') $apurivi .= "\"$row[try]\"\t";
		if($valitut["SARAKE3"] != '') $apurivi .= "\"$row[tuotemerkki]\"\t";
		if($valitut["SARAKE4"] != '') $apurivi .= "\"$row[tahtituote]\"\t";
		if($valitut["SARAKE4B"] != '')$apurivi .= "\"$row[status]\"\t";
		if($valitut["SARAKE4C"] != '')$apurivi .= "\"$abcnimi\"\t";
		if($valitut["SARAKE4CA"] != '')$apurivi .= "\"$abcnimi2\"\t";
		if($valitut["SARAKE4CB"] != '')$apurivi .= "\"$abcnimi3\"\t";
		if($valitut["SARAKE4D"] != '')$apurivi .= "\"$row[luontiaika]\"\t";
		if($valitut["SARAKE5"] != '') $apurivi .= str_replace(".",",",$saldo['saldo'])."\t";
		if($valitut["SARAKE6"] != '') $apurivi .= str_replace(".",",",$row['halytysraja'])."\t";
		if($valitut["SARAKE7"] != '') $apurivi .= str_replace(".",",",$ennp['tilattu'])."\t";
		if($valitut["SARAKE8"] != '') $apurivi .= str_replace(".",",",$ennp['ennpois'])."\t";
		if($valitut["SARAKE9"] != '') $apurivi .= str_replace(".",",",$ennp['jt'])."\t";
		if($valitut["SARAKE10"] != '') $apurivi .= "$ostettava1kk\t";
		if($valitut["SARAKE11"] != '') $apurivi .= "$ostettava3kk\t";
		if($valitut["SARAKE12"] != '') $apurivi .= "$ostettava4kk\t";
		if($valitut["SARAKE13"] != '') $apurivi .= "$ostettavahaly\t";
		if($valitut["SARAKE14"] != '') $apurivi .= str_replace(".",",",$row['osto_era'])."\t";
		if($valitut["SARAKE15"] != '') $apurivi .= str_replace(".",",",$row['myynti_era'])."\t";
		if($valitut["SARAKE16"] != '') $apurivi .= "\"$row[toimittaja]\"\t";
		if($valitut["SARAKE17"] != '') $apurivi .= "\"$row[toim_tuoteno]\"\t";
		if($valitut["SARAKE18"] != '') $apurivi .= "\"$row[nimitys]\"\t";
		if($valitut["SARAKE18B"] != '') $apurivi .= "\"$row[toim_nimitys]\"\t";
		if($valitut["SARAKE19"] != '') $apurivi .= str_replace(".",",",$row['ostohinta'])."\t";
		if($valitut["SARAKE20"] != '') $apurivi .= str_replace(".",",",$row['myyntihinta'])."\t";

		if ($ostettavahaly > 0 or $ostettava4kk > 0) {
			$rivinaytetaan++;
		}

		if ($row['epakurantti1pvm']!='0000-00-00') {
			if($valitut["SARAKE21"] != '') $apurivi .= "$row[epakurantti1pvm]\t";
		}
		else {
			if($valitut["SARAKE21"] != '') $apurivi .= "\t";
		}

		if ($row['epakurantti2pvm']!='0000-00-00') {
			if($valitut["SARAKE22"] != '') $apurivi .= "$row[epakurantti2pvm]\t";
		}
		else {
			if($valitut["SARAKE22"] != '') $apurivi .= "\t";
		}

		if($valitut["SARAKE23"] != '') $apurivi .= str_replace(".",",",$osaldo['osaldo'])."\t";
		if($valitut["SARAKE24"] != '') $apurivi .= "\"$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]\"\t";
		if($valitut["SARAKE25"] != '') $apurivi .= str_replace(".",",",$laskurow['puutekpl1'])."\t";
		if($valitut["SARAKE26"] != '') $apurivi .= str_replace(".",",",$laskurow['puutekpl2'])."\t";
		if($valitut["SARAKE27"] != '') $apurivi .= str_replace(".",",",$laskurow['puutekpl3'])."\t";
		if($valitut["SARAKE28"] != '') $apurivi .= str_replace(".",",",$laskurow['puutekpl4'])."\t";

		//Myydyt kappaleet
		if($valitut["SARAKE29"] != '') $apurivi .= str_replace(".",",",$laskurow['kpl1'])."\t";
		if($valitut["SARAKE30"] != '') $apurivi .= str_replace(".",",",$laskurow['kpl2'])."\t";
		if($valitut["SARAKE31"] != '') $apurivi .= str_replace(".",",",$laskurow['kpl3'])."\t";
		if($valitut["SARAKE32"] != '') $apurivi .= str_replace(".",",",$laskurow['kpl4'])."\t";
		if($valitut["SARAKE33"] != '') $apurivi .= str_replace(".",",",$laskurow['EDkpl1'])."\t";
		if($valitut["SARAKE34"] != '') $apurivi .= str_replace(".",",",$laskurow['EDkpl2'])."\t";
		if($valitut["SARAKE35"] != '') $apurivi .= str_replace(".",",",$laskurow['EDkpl3'])."\t";
		if($valitut["SARAKE36"] != '') $apurivi .= str_replace(".",",",$laskurow['EDkpl4'])."\t";

		//Kulutetut kappaleet
		if($valitut["SARAKE29K"] != '') $apurivi .= str_replace(".",",",$kulutrow['kpl1'])."\t";
		if($valitut["SARAKE30K"] != '') $apurivi .= str_replace(".",",",$kulutrow['kpl2'])."\t";
		if($valitut["SARAKE31K"] != '') $apurivi .= str_replace(".",",",$kulutrow['kpl3'])."\t";
		if($valitut["SARAKE32K"] != '') $apurivi .= str_replace(".",",",$kulutrow['kpl4'])."\t";
		if($valitut["SARAKE33K"] != '') $apurivi .= str_replace(".",",",$kulutrow['EDkpl1'])."\t";
		if($valitut["SARAKE34K"] != '') $apurivi .= str_replace(".",",",$kulutrow['EDkpl2'])."\t";
		if($valitut["SARAKE35K"] != '') $apurivi .= str_replace(".",",",$kulutrow['EDkpl3'])."\t";
		if($valitut["SARAKE36K"] != '') $apurivi .= str_replace(".",",",$kulutrow['EDkpl4'])."\t";

		if($valitut["SARAKE37"] != '') $apurivi .= str_replace(".",",",$laskurow['kate1'])."\t";
		if($valitut["SARAKE38"] != '') $apurivi .= str_replace(".",",",$laskurow['kate2'])."\t";
		if($valitut["SARAKE39"] != '') $apurivi .= str_replace(".",",",$laskurow['kate3'])."\t";
		if($valitut["SARAKE40"] != '') $apurivi .= str_replace(".",",",$laskurow['kate4'])."\t";
		if($valitut["SARAKE41"] != '') $apurivi .= str_replace(".",",",$katepros1)."\t";
		if($valitut["SARAKE42"] != '') $apurivi .= str_replace(".",",",$katepros2)."\t";
		if($valitut["SARAKE43"] != '') $apurivi .= str_replace(".",",",$katepros3)."\t";
		if($valitut["SARAKE44"] != '') $apurivi .= str_replace(".",",",$katepros4)."\t";
		if($valitut["SARAKE45"] != '') $apurivi .= str_replace(".",",",$row['tuotekerroin'])."\t";
		if($valitut["SARAKE46"] != '') $apurivi .= str_replace(".",",",$ennarow['tilkpl'])."\t";
		if($valitut["SARAKE47"] != '') $apurivi .= "\"$row[aleryhma]\"\t";

		if($valitut["SARAKE47B"] != '') $apurivi .= str_replace(".",",",$row["kehahin"])."\t";

		if ($asiakasosasto != '') {
			if($valitut["SARAKE48"] != '') $apurivi .= str_replace(".",",",$asosrow['kpl1'])."\t";
			if($valitut["SARAKE49"] != '') $apurivi .= str_replace(".",",",$asosrow['kpl2'])."\t";
			if($valitut["SARAKE50"] != '') $apurivi .= str_replace(".",",",$asosrow['kpl3'])."\t";
			if($valitut["SARAKE51"] != '') $apurivi .= str_replace(".",",",$asosrow['kpl4'])."\t";
		}
		if ($asiakasno != '') {
			if($valitut["SARAKE52"] != '') $apurivi .= str_replace(".",",",$asrow['kpl1'])."\t";
			if($valitut["SARAKE53"] != '') $apurivi .= str_replace(".",",",$asrow['kpl2'])."\t";
			if($valitut["SARAKE54"] != '') $apurivi .= str_replace(".",",",$asrow['kpl3'])."\t";
			if($valitut["SARAKE55"] != '') $apurivi .= str_replace(".",",",$asrow['kpl4'])."\t";
		}

		//korvaavat tuotteet
		$query  = "	SELECT id
					FROM korvaavat
					WHERE tuoteno	= '$row[tuoteno]'
					and yhtio		= '$row[yhtio]'";
		$korvaresult = mysql_query($query) or pupe_error($query);
		$korvarow = mysql_fetch_array($korvaresult);

		$query  = "	SELECT tuoteno
					FROM korvaavat
					WHERE tuoteno  != '$row[tuoteno]'
					and id			= '$korvarow[id]'
					and yhtio		= '$row[yhtio]'";
		$korvaresult = mysql_query($query) or pupe_error($query);

		//tulostetaan korvaavat
		while ($korvarow = mysql_fetch_array($korvaresult)) {
			// Korvaavien paikkojen valittujen varastojen paikkojen saldo yhteensä, mukaan tulee myös aina ne saldot jotka ei kuulu mihinkään varastoalueeseen
			$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
						FROM tuotepaikat
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
						and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
						WHERE tuotepaikat.$varastot_yhtiot
						and tuotepaikat.tuoteno='$korvarow[tuoteno]'
						GROUP BY varastopaikat.tunnus
						$varastot";
			$korvasaldoresult = mysql_query($query) or pupe_error($query);

			$korva_sumsaldo = 0;

			while($korvasaldorow = mysql_fetch_array($korvasaldoresult)) {
				$korva_sumsaldo += $korvasaldorow["saldo"];
			}

			$korvasaldorow["saldo"] = $korva_sumsaldo;

			// Saldolaskentaa tulevaisuuteen
			$query = "	SELECT
						sum(if(tyyppi='O', varattu, 0)) tilattu,
						sum(if(tyyppi='L' or tyyppi='V', varattu, 0)) varattu
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
						WHERE yhtio='$row[yhtio]'
						and tyyppi in ('O','L','V')
						and tuoteno='$korvarow[tuoteno]'
						and varattu>0";
			$presult = mysql_query($query) or pupe_error($query);
			$prow = mysql_fetch_array($presult);

			//Korvaavien myynnnit
			$query  = "	SELECT
						sum(if(laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
						sum(if(laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
						sum(if(laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
						sum(if(laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio = '$row[yhtio]'
						and tyyppi = 'L'
						and tuoteno = '$korvarow[tuoteno]'
						and laskutettuaika >= '$apvm'
						and laskutettuaika <= '$lpvm'";
			$asresult = mysql_query($query) or pupe_error($query);
			$kasrow = mysql_fetch_array($asresult);

			if($valitut["SARAKE56"] != '') $apurivi .= "\"$korvarow[tuoteno]\"\t";
			if($valitut["SARAKE57"] != '') $apurivi .= str_replace(".",",",$korvasaldorow['saldo'])."\t";
			if($valitut["SARAKE58"] != '') $apurivi .= str_replace(".",",",$prow['varattu'])."\t";
		    if($valitut["SARAKE59"] != '') $apurivi .= str_replace(".",",",$prow['tilattu'])."\t";

			if($valitut["SARAKE60"] != '') $apurivi .= str_replace(".",",",$kasrow['kpl1'])."\t";
			if($valitut["SARAKE61"] != '') $apurivi .= str_replace(".",",",$kasrow['kpl2'])."\t";
			if($valitut["SARAKE62"] != '') $apurivi .= str_replace(".",",",$kasrow['kpl3'])."\t";
			if($valitut["SARAKE63"] != '') $apurivi .= str_replace(".",",",$kasrow['kpl4'])."\t";

			if($valitut["SARAKE64"] != '') {
				$query = "	select count(*) kpl
							from yhteensopivuus_tuote
							join yhteensopivuus_rekisteri on (yhteensopivuus_rekisteri.yhtio = yhteensopivuus_tuote.yhtio and yhteensopivuus_rekisteri.autoid = yhteensopivuus_tuote.atunnus)
							where yhteensopivuus_tuote.yhtio='$kukarow[yhtio]' and yhteensopivuus_tuote.tuoteno='$row[tuoteno]'";
				$asresult = mysql_query($query) or pupe_error($query);
				$kasrow = mysql_fetch_array($asresult);
				$apurivi .= $kasrow['kpl']."\t";
			}

		}

		$apurivi .= "\r\n";

		if ($rivinaytetaan > 0) {
			$rivi .= $apurivi;
		}


		// tehdään arvio kauan tämä kestää.. wau! :)
		if (count($arvio)<=$joukko) {
			$timeparts = explode(" ",microtime());
			$endtime   = $timeparts[1].substr($timeparts[0],1);
			$arvio[]   = round($endtime-$alkuaika,4);

			if (count($arvio)==$joukko)
			{
				$ka   = array_sum($arvio) / count($arvio);
				$aika = round(mysql_num_rows($res) * $ka, 0);
				echo t("Arvioitu ajon kesto")." $aika sec.<br>";
				flush();
			}
			else
			{
				$timeparts = explode(" ",microtime());
				$alkuaika  = $timeparts[1].substr($timeparts[0],1);
			}
		}

		$timeparts = explode(" ",microtime());
		$loppu     = $timeparts[1].substr($timeparts[0],1);
		$ajat[]    = round($loppu-$alku,4);
	}

	echo t("Lähetetään sähköposti")." ";
	flush();

	$timeparts = explode(" ",microtime());
	$endtime   = $timeparts[1].substr($timeparts[0],1);
	$total     = round($endtime-$starttime,0);


	$bound = uniqid(time()."_") ;

	$header  = "From: <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	$content = "--$bound\n";

	$content .= "Content-Type: application/vnd.ms-excel; name=\"".t("Excel-halyrap")."-$osasto-$tuoryh-$kukarow[yhtio].xls\"\n" ;
	$content .= "Content-Transfer-Encoding: base64\n" ;
	$content .= "Content-Disposition: attachment; filename=\"".t("Excel-halyrap")."-$osasto-$tuoryh-$kukarow[yhtio].xls\"\n\n";


	$content .= chunk_split(base64_encode($rivi));
	$content .= "\n" ;

	$content .= "--$bound\n";

	$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("OpenOffice-halyrap")."-$osasto-$tuoryh-$kukarow[yhtio].csv\"\n" ;
	$content .= "Content-Transfer-Encoding: base64\n" ;
	$content .= "Content-Disposition: attachment; filename=\"".t("OpenOffice-halyrap")."-$osasto-$tuoryh-$kukarow[yhtio].csv\"\n\n";


	$content .= chunk_split(base64_encode($rivi));
	$content .= "\n" ;

	$content .= "--$bound\n";

	$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("TXT-halyrap")."-$osasto-$tuoryh-$kukarow[yhtio].txt\"\n" ;
	$content .= "Content-Transfer-Encoding: base64\n" ;
	$content .= "Content-Disposition: attachment; filename=\"".t("TXT-halyrap")."-$osasto-$tuoryh-$kukarow[yhtio].txt\"\n\n";


	$content .= chunk_split(base64_encode($rivi));
	$content .= "\n" ;

	$content .= "--$bound\n";

	$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Halytysraportti osasto").": $osasto ".t("tuoteryhma").": $tuoryh", $content, $header);

	if ($boob===FALSE) echo " - ".t("Email lähetys epäonnistui!")." $yhtiorow[postittaja_email] --> $kukarow[eposti]<br>";
	else echo " $kukarow[eposti].<br>";

	//Nää muuttujat voi olla aika isoja joten unsetataan ne
	unset($rivi);

	foreach ($ajat as $aika) {
		$yht+=$aika;
	}

	$yht=round($yht/count($ajat),5);

	echo t("Ajo kesti")." $total sec.</font> <font class='info'>($yht ".t("sec/tuoterivi").")</font><br><br>";

	$osasto		= '';
	$tuoryh		= '';
	$ytunnus	= '';
	$tuotemerkki= '';
	$tee		= 'X';
}

if ($tee == "" or $tee == "JATKA") {
	if (isset($muutparametrit)) {
		list($osasto,$tuoryh,$ytunnus,$tuotemerkki,$asiakasosasto,$asiakasno,$toimittaja) = explode('#', $muutparametrit);
	}

	$muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

	if ($tuoryh !='' or $osasto != '' or $ytunnus != '' or $tuotemerkki != '') {
		if ($ytunnus != '' and !isset($ylatila)) {

			require("../inc/kevyt_toimittajahaku.inc");

			if ($ytunnus != '') {
				$tee = "JATKA";
			}
		}
		elseif($ytunnus != '' and isset($ylatila)) {
			$tee = "JATKA";
		}
		elseif($tuoryh !='' or $osasto != '' or $tuotemerkki != '') {
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

		require("../inc/asiakashaku.inc");

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

	echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
			<br>".t("Valitse vähintään yksi seuraavista:")."
			<table>
			<tr><th>".t("Osasto")."</th><td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='osasto'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
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
	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuoryh'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
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
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuotemerkki'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuotemerkki == $srow["tuotemerkki"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
	}
	echo "</select>";


	echo "</td></tr>
			<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";

			$sel = array();
			$sel[$abcrajaus] = "SELECTED";

			// katotaan onko abc aputaulu rakennettu
			$query  = "select count(*) from abc_aputaulu where yhtio='$kukarow[yhtio]' and tyyppi = 'TK'";
			$abcres = mysql_query($query) or pupe_error($query);
			$abcrow = mysql_fetch_array($abcres);

			// jos on niin näytetään tällänen vaihtoehto
			if ($abcrow[0] > 0) {
				echo "<tr><th>".t("ABC-luokkarajaus")."</th><td>
				<select name='abcrajaus'>
				<option value=''>Ei rajausta</option>
				<option $sel[0] value='0'>Luokka A-30</option>
				<option $sel[1] value='1'>Luokka B-20 ja paremmat</option>
				<option $sel[2] value='2'>Luokka C-15 ja paremmat</option>
				<option $sel[3] value='3'>Luokka D-15 ja paremmat</option>
				<option $sel[4] value='4'>Luokka E-10 ja paremmat</option>
				<option $sel[5] value='5'>Luokka F-05 ja paremmat</option>
				<option $sel[6] value='6'>Luokka G-03 ja paremmat</option>
				<option $sel[7] value='7'>Luokka H-02 ja paremmat</option>
				<option $sel[8] value='8'>Luokka I-00 ja paremmat</option>
				</select>
				</td></tr>";
			}

	echo "<tr><td colspan='2' class='back'><br></td></tr>";
	echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa asiakaan myynnit").":</td></tr>";

	echo "<tr><th>".t("Asiakasosasto")."</th><td>";

	$query = "	SELECT distinct osasto
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and osasto!=''
				order by osasto+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='asiakasosasto'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($asiakasosasto == $srow[0]) {
			$sel = "selected";
		}
		echo "<option value='$srow[osasto]' $sel>$srow[osasto]</option>";
	}
	echo "</select>";


	echo "	</td></tr>
			<tr><th>".t("Asiakas")."</th><td><input type='text' size='20' name='asiakasno' value='$asiakasno'></td></tr>

			</table><br>
			<input type='Submit' value = '".t("Jatka")."'>
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
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
					and selite='$tuoryh'";
		$sresult = mysql_query($query) or pupe_error($query);
		$srow = mysql_fetch_array($sresult);
	}
	if ($osasto != '') {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
					and selite='$osasto'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($sresult);
	}
	if ($toimittajaid != '') {
		$query = "	SELECT nimi
					FROM toimi
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow1 = mysql_fetch_array($sresult);
	}
	if ($asiakasid != '') {
		$query = "	SELECT nimi
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$asiakasid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow2 = mysql_fetch_array($sresult);
	}

	if ($rappari != $edrappari) {
		unset($valitut);
		$tee = "JATKA";
	}

	if (!isset($edrappari) or ($rappari == "" and $edrappari != "")) {
		$defaultit = "PÄÄLLE";
	}

	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
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

			<table>
			<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
			<tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
			<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
			<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
			<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
			<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
			<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>";

	echo "	<tr><td class='back'><br></td></tr>";

	echo "	<tr>
			<td class='back'></td><th colspan='3'>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td class='back'></td><th colspan='3'>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>
			<th>".t("Ostoehdotus")."</th></tr>";

	$query = "	SELECT selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji	= 'HALYRAP'
				and selite	= '$rappari'
				and selitetark like 'PAIVAM##%'";
	$sresult = mysql_query($query) or pupe_error($query);

	while($srow = mysql_fetch_array($sresult)) {
		list($etuliite, $nimi, $paivamaara) = explode('##',$srow["selitetark"]);

		${$nimi} = $paivamaara;
	}

	echo "	<tr><th>".t("Kausi 1")."</th>
			<td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
			<td><input type='text' name='kka1' value='$kka1' size='5'></td>
			<td><input type='text' name='vva1' value='$vva1' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
			<td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
			<td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='1' $k1></td></tr>";

	echo "	<tr><th>".t("Kausi 2")."</th>
			<td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
			<td><input type='text' name='kka2' value='$kka2' size='5'></td>
			<td><input type='text' name='vva2' value='$vva2' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
			<td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
			<td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='2' $k2></td></tr>";

	echo "	<tr><th>".t("Kausi 3")."</th>
			<td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
			<td><input type='text' name='kka3' value='$kka3' size='5'></td>
			<td><input type='text' name='vva3' value='$vva3' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
			<td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
			<td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='3' $k3></td></tr>";

	echo "	<tr><th>".t("Kausi 4")."</th>
			<td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
			<td><input type='text' name='kka4' value='$kka4' size='5'></td>
			<td><input type='text' name='vva4' value='$vva4' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
			<td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
			<td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='4' $k4></td></tr>";


	$query = "	SELECT selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji	= 'HALYRAP'
				and selite	= '$rappari'
				and selitetark = 'TALLENNAPAIVAM'";
	$sresult = mysql_query($query) or pupe_error($query);
	$srow = mysql_fetch_array($sresult);


	$chk = "";
	if (($srow["selitetark"] == "TALLENNAPAIVAM" and $tee == "JATKA") or $valitut["TALLENNAPAIVAM"] != '') {
		$chk = "CHECKED";
	}

	echo "<tr><th>".t("Tallenna päivämäärät:")."</th><td colspan='9'><input type='checkbox' name='valitut[TALLENNAPAIVAM]' value='TALLENNAPAIVAM' $chk></td></tr>";
	echo "	<tr><td class='back'><br></td></tr>";

	//Ostokausivalinnat
	$kaudet_oletus = array(1,3,4);
	$kaudet_kaikki = array(1,2,3,4,5,6,7,8,9,10,11,12,24);

	$kaudet = array();
	$kaulas = 1;

	foreach ($kaudet_oletus as $kausi1) {

		echo "<tr><th>Ostoehdotus $kausi1:</th><td colspan='3'><select name='valitut[KAUSI$kaulas]'>";

		foreach ($kaudet_kaikki as $kausi2) {
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'KAUSI$kaulas##$kausi2'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";

			if (("KAUSI".$kaulas."##".$kausi2 == $srow["selitetark"] and $tee == "JATKA") or $valitut["KAUSI$kaulas##$kausi2"] != '' or ($kausi1 == $kausi2 and $srow["selitetark"] == "")) {
				$chk = "SELECTED";
			}

			echo "<option value='KAUSI$kaulas##$kausi2' $chk onchange='submit();'>$kausi2</option>";
		}

		echo "</select></td></tr>";

		$kaulas++;
	}

	echo "	<tr><td class='back'><br></td></tr>";



	//Yhtiövalinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]' and konserni != ''";
	$presult = mysql_query($query) or pupe_error($query);

	$yhtiot 	= "";
	$konsyhtiot = "";
	$vlask 		= 0;

	if (mysql_num_rows($presult) > 0) {
		while ($prow = mysql_fetch_array($presult)) {

			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'YHTIO##$prow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

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

		$yhtiot = substr($yhtiot,0,-1);
		$konsyhtiot = substr($konsyhtiot,0,-1);

		echo "	<tr><td class='back'><br></td></tr>";
	}
	else {
		$yhtiot = "'".$kukarow['yhtio']."'";
		$konsyhtiot = "'".$kukarow['yhtio']."'";
	}

	//Ajetaanko varastopaikoittain
	$query = "	SELECT selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji = 'HALYRAP'
				and selite	= '$rappari'
				and selitetark = 'PAIKOITTAIN'";
	$sresult = mysql_query($query) or pupe_error($query);
	$srow = mysql_fetch_array($sresult);

	$chk = "";
	if (($srow["selitetark"] == "PAIKOITTAIN" and $tee == "JATKA") or $valitut["paikoittain"] != '') {
		$chk = "CHECKED";
	}

	echo "<tr><th>".t("Aja raportti varastopaikoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[paikoittain]' value='PAIKOITTAIN' $chk></td></tr>";


	//Näytetäänkö poistetut tuotteet
	$query = "	SELECT selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji = 'HALYRAP'
				and selite	= '$rappari'
				and selitetark = 'POISTETUT'";
	$sresult = mysql_query($query) or pupe_error($query);
	$srow = mysql_fetch_array($sresult);

	$chk = "";
	if (($srow["selitetark"] == "POISTETUT" and $tee == "JATKA") or $valitut["poistetut"] != '' or $defaultit == "PÄÄLLE") {
		$chk = "CHECKED";
	}

	echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistetut]' value='POISTETUT' $chk></td></tr>";


	//Näytetäänkö poistetut tuotteet
	$query = "	SELECT selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji = 'HALYRAP'
				and selite	= '$rappari'
				and selitetark = 'EIHINNASTOON'";
	$sresult = mysql_query($query) or pupe_error($query);
	$srow = mysql_fetch_array($sresult);

	$chk = "";
	if (($srow["selitetark"] == "EIHINNASTOON" and $tee == "JATKA") or $valitut["EIHINNASTOON"] != '' or $defaultit == "PÄÄLLE") {
		$chk = "CHECKED";
	}

	echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='3'><input type='checkbox' name='valitut[EIHINNASTOON]' value='EIHINNASTOON' $chk></td></tr>";


	//Näytetäänkö poistetut tuotteet
	$query = "	SELECT selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji = 'HALYRAP'
				and selite	= '$rappari'
				and selitetark = 'EHDOTETTAVAT'";
	$sresult = mysql_query($query) or pupe_error($query);
	$srow = mysql_fetch_array($sresult);

	$chk = "";
	if (($srow["selitetark"] == "EHDOTETTAVAT" and $tee == "JATKA") or $valitut["EHDOTETTAVAT"] != '') {
		$chk = "CHECKED";
	}

	echo "<tr><th>".t("Näytä vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='valitut[EHDOTETTAVAT]' value='EHDOTETTAVAT' $chk></td></tr>";

	if ($abcrajaus != "") {

		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

		//näytetäänkö uudet tuotteet
		$query = "	SELECT selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and laji = 'HALYRAP'
					and selite	= '$rappari'
					and selitetark = 'UUDETTUOTTEET'";
		$sresult = mysql_query($query) or pupe_error($query);
		$srow = mysql_fetch_array($sresult);

		$chk = "";
		if (($srow["selitetark"] == "UUDETTUOTTEET" and $tee == "JATKA") or $valitut["UUDETTUOTTEET"] != '') {
			$chk = "CHECKED";
		}

		echo "<tr><th>".t("Älä listaa 12kk sisällä perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[UUDETTUOTTEET]' value='UUDETTUOTTEET' $chk></td></tr>";

		//näytetäänkö uudet tuotteet
		$query = "	SELECT selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and laji = 'HALYRAP'
					and selite	= '$rappari'
					and selitetark = 'VAINUUDETTUOTTEET'";
		$sresult = mysql_query($query) or pupe_error($query);
		$srow = mysql_fetch_array($sresult);

		$chk = "";
		if (($srow["selitetark"] == "VAINUUDETTUOTTEET" and $tee == "JATKA") or $valitut["VAINUUDETTUOTTEET"] != '') {
			$chk = "CHECKED";
		}

		echo "<tr><th>".t("Listaa vain 12kk sisällä perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='valitut[VAINUUDETTUOTTEET]' value='VAINUUDETTUOTTEET' $chk></td></tr>";
	}

	echo "<tr><td class='back'><br></td></tr>";


	//Valitaan varastot joiden saldot huomioidaan
	//Tutkitaan onko käyttäjä klikannut useampaa yhtiötä
	if ($konsyhtiot  != '') {
		$konsyhtiot = " yhtio in (".$konsyhtiot.") ";
	}
	else {
		$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
	}

	$query = "	SELECT *
				FROM varastopaikat
				WHERE $konsyhtiot
				ORDER BY yhtio, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	$vlask = 0;

	while ($vrow = mysql_fetch_array($vtresult)) {
		$query = "	SELECT selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and laji = 'HALYRAP'
					and selite	= '$rappari'
					and selitetark = 'VARASTO##$vrow[tunnus]'";
		$sresult = mysql_query($query) or pupe_error($query);
		$srow = mysql_fetch_array($sresult);

		$chk = "";
		if (("VARASTO##".$vrow["tunnus"] == $srow["selitetark"]  and $tee == "JATKA") or $valitut["VARASTO##$vrow[tunnus]"] != '' or ($defaultit == "PÄÄLLE" and $vrow["yhtio"] == $kukarow["yhtio"])) {
			$chk = "CHECKED";
		}

		if ($vlask == 0) {
			echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot varastossa:")."</th>";
		}
		else {
			echo "<tr>";
		}

		echo "<td colspan='3'><input type='checkbox' name='valitut[VARASTO##$vrow[tunnus]]' value='VARASTO##$vrow[tunnus]' $chk> $vrow[nimitys] ($vrow[yhtio])</td></tr>";

		$vlask++;
	}


	echo "</table><br><br>";
	echo "<table>";
	echo "<tr><th colspan='4'>".t("Omat hälytysraportit")."</th></tr>";
	echo "<tr><th>".t("Luo uusi oma raportti").":</th><td colspan='3'><input type='text' size='40' name='uusirappari' value=''></td></tr>";
	echo "<tr><th>".t("Valitse raportti").":</th><td colspan='3'>";

	//Haetaan tallennetut hälyrapit
	$query = "	SELECT distinct selite, concat('(',replace(selite, '##',') ')) nimi
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji = 'HALYRAP'
				ORDER BY selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='rappari' onchange='submit()'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {

		$sel = '';
		if ($rappari == $srow["selite"]) {
			$sel = "selected";
		}

		echo "<option value='$srow[selite]' $sel>$srow[nimi]</option>";
	}
	echo "</select>";

	echo "</td></tr>";

	$lask = 0;
	echo "<tr>";

	foreach($sarakkeet as $key => $sarake) {

		$query = "	SELECT selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and laji	= 'HALYRAP'
					and selite	= '$rappari'
					and selitetark = '$key'";
		$sresult = mysql_query($query) or pupe_error($query);

		$sel = "";
		if (mysql_num_rows($sresult) == 1 or $rappari == "") {
			$sel = "CHECKED";
		}

		if ($lask % 4 == 0 and $lask != 0) {
			echo "</tr><tr>";
		}

		echo "<td><input type='checkbox' name='valitut[$key]' value='$key' $sel>".ucfirst($sarake)."</td>";
		$lask++;
	}

	echo "</tr>";
	echo "</table>";
	echo "<br>
		<input type='Submit' name='RAPORTOI' value = '".t("Aja hälytysraportti")."'>
		</form>";
}
require ("../inc/footer.inc");

?>
