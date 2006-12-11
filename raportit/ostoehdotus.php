<?php

// Tämä skripti käyttää slave-tietokantapalvelinta
$useslave = 1;

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
	if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require("../inc/parametrit.inc");

if (isset($tee)) {
	if ($tee == "lataa_tiedosto") {
		echo $file;
		exit;
	}
}

echo "<font class='head'>".t("Ostoehdotus")."</font><hr>";

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


// katsotaan tarvitaanko mennä toimittajahakuun
if (($ytunnus != "" and $toimittajaid == "") or ($edytunnus != $ytunnus)) {
	if ($edytunnus != $ytunnus) $toimittajaid = "";
	require ("inc/kevyt_toimittajahaku.inc");
	$ytunnus = $toimittajarow["ytunnus"];
	$tee = "";
}


// tehdään itse raportti
if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {

	// haetaan nimitietoa
	if ($tuoryh != '') {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='TRY'
					and selite  = '$tuoryh'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow1 = mysql_fetch_array($sresult);
	}
	if ($osasto != '') {
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='OSASTO'
					and selite  = '$osasto'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow2 = mysql_fetch_array($sresult);
	}
	if ($toimittajaid != '') {
		$query = "	SELECT nimi
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$toimittajaid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow3 = mysql_fetch_array($sresult);
	}

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
	if ($poistetut != '') {
		$lisaa .= " and tuote.status != 'P' ";
	}
	if ($eihinnastoon != '') {
		$lisaa .= " and tuote.hinnastoon != 'E' ";
	}
	if ($vainuudet != '') {
		$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
	}
	if ($eiuusia != '') {
		$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
	}
	if ($toimittajaid != '') {
		$lisaa2 .= " JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid') ";
	}

	$abcnimi = $ryhmanimet[$abcrajaus];

	// tutkitaan yhtiövalinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]' and konserni != ''";
	$presult = mysql_query($query) or pupe_error($query);

	$yhtiot = "";

	if (mysql_num_rows($presult) > 0 and is_array($valitutyhtiot)) {
		while ($prow = mysql_fetch_array($presult)) {
			if (in_array($prow["yhtio"], $valitutyhtiot) != '') {
				$yhtiot .= "'$prow[yhtio]',";
			}
		}
		$yhtiot = substr($yhtiot,0,-1);
	}
	else {
		$yhtiot = "'".$kukarow["yhtio"]."'";
	}


	// katsotaan valitut varastot
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio in ($yhtiot)";
	$vtresult = mysql_query($query) or pupe_error($query);

	$varastot 			  = "";
	$varastot_paikoittain = "";

	if (is_array($valitutvarastot)) {
		while ($vrow = mysql_fetch_array($vtresult)) {
			if (in_array($vrow["tunnus"], $valitutvarastot)) {
				$varastot .= "'".$vrow["tunnus"]."',";
				$varastot_paikoittain = "KYLLA";
			}
		}
	}

	$varastot 		 = substr($varastot,0,-1);
	$varastot_yhtiot = substr($varastot_yhtiot,0,-1);

	$maa_varastot 			= "";
	$varastot_maittain		= "";

	mysql_data_seek($vtresult,0);

	if (is_array($valitutmaat)) {
		while ($vrow = mysql_fetch_array($vtresult)) {
			if (in_array("$vrow[yhtio]#$vrow[maa]", $valitutmaat)) {
				$maa_varastot .= "'".$vrow["tunnus"]."',";
				$varastot_maittain = "KYLLA";
			}
		}
	}

	$maa_varastot 		 = substr($maa_varastot,0,-1);
	$maa_varastot_yhtiot = substr($maa_varastot_yhtiot,0,-1);

	if ($varastot == "" and $maa_varastot == "" and ($varastot_maittain == "KYLLA" or $varastot_paikoittain == "KYLLA")) {
		echo "<font class='error'>".t("VIRHE: Ajat hälytysraportin varastopaikoittain, mutta et valinnut yhtään varastoa.")."</font>";
		exit;
	}

	// katotaan JT:ssä olevat tuotteet
	$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
				FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
				JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
				WHERE tilausrivi.yhtio in ($yhtiot)
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


	// tässä on itse query
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
				LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
				WHERE
				tuote.yhtio in ($yhtiot)
				$lisaa
				and tuote.ei_saldoa = ''
				group by tuote.tuoteno
				ORDER BY id, tuote.tuoteno";
	$res = mysql_query($query) or pupe_error($query);

	echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>";
	flush();

	$rivi = "";

	// arvioidaan kestoa
	$ajat      = array();
	$arvio     = array();
	$timeparts = explode(" ",microtime());
	$alkuaika  = $timeparts[1].substr($timeparts[0],1);
	$joukko    = 100; //kuinka monta riviä otetaan keskiarvoon

	while ($row = mysql_fetch_array($res)) {

		// otetaan kellonaika arviota varten
		$timeparts = explode(" ",microtime());
		$alku      = $timeparts[1].substr($timeparts[0],1);

		$toimilisa = "";
		if ($toimittajaid != '') $toimilisa = " and liitostunnus = '$toimittajaid' ";

		// haetaan tuotteen toimittajatietoa
		$query = "	SELECT group_concat(tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '/') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
					group_concat(distinct tuotteen_toimittajat.ostohinta order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin
					FROM tuotteen_toimittajat
					WHERE yhtio in ($yhtiot)
					and tuoteno = '$row[tuoteno]'
					$toimilisa";
		$result   = mysql_query($query) or pupe_error($query);
		$toimirow = mysql_fetch_array($result);

		// kaunistellaan kenttiä
		if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";
		if ($row['epakurantti1pvm'] == '0000-00-00')     $row['epakurantti1pvm'] = "";
		if ($row['epakurantti2pvm'] == '0000-00-00')     $row['epakurantti1pvm'] = "";

		// haetaan abc luokille nimet
		$abcnimi = $ryhmanimet[$row["abcluokka"]];
		$abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
		$abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];

		// kirjoitetaan itse riviä
		$headerivi  = "tuoteno\t";
		$tuoterivi  = "\"$row[tuoteno]\"\t";
		$headerivi .= "osasto\t";
		$tuoterivi .= "\"$row[osasto]\"\t";
		$headerivi .= "try\t";
		$tuoterivi .= "\"$row[try]\"\t";
		$headerivi .= "tuotemerkki\t";
		$tuoterivi .= "\"$row[tuotemerkki]\"\t";
		$headerivi .= "tähtituote\t";
		$tuoterivi .= "\"$row[tahtituote]\"\t";
		$headerivi .= "status\t";
		$tuoterivi .= "\"$row[status]\"\t";
		$headerivi .= "abc\t";
		$tuoterivi .= "\"$abcnimi\"\t";
		$headerivi .= "abc osasto\t";
		$tuoterivi .= "\"$abcnimi2\"\t";
		$headerivi .= "abc try\t";
		$tuoterivi .= "\"$abcnimi3\"\t";
		$headerivi .= "luontiaika\t";
		$tuoterivi .= "\"$row[luontiaika]\"\t";
		$headerivi .= "hälytysraja\t";
		$tuoterivi .= str_replace(".",",",$row['halytysraja'])."\t";
		$headerivi .= "ostoerä\t";
		$tuoterivi .= str_replace(".",",",$row['osto_era'])."\t";
		$headerivi .= "myyntierä\t";
		$tuoterivi .= str_replace(".",",",$row['myynti_era'])."\t";
		$headerivi .= "toimittaja\t";
		$tuoterivi .= "\"$toimirow[toimittaja]\"\t";
		$headerivi .= "toim tuoteno\t";
		$tuoterivi .= "\"$toimirow[toim_tuoteno]\"\t";
		$headerivi .= "nimitys\t";
		$tuoterivi .= "\"$row[nimitys]\"\t";
		$headerivi .= "toim nimitys\t";
		$tuoterivi .= "\"$toimirow[toim_nimitys]\"\t";
		$headerivi .= "ostohinta\t";
		$tuoterivi .= str_replace(".",",",$toimirow['ostohinta'])."\t";
		$headerivi .= "myyntihinta\t";
		$tuoterivi .= str_replace(".",",",$row['myyntihinta'])."\t";
		$headerivi .= "epäkurantti1\t";
		$tuoterivi .= "$row[epakurantti1pvm]\t";
		$headerivi .= "epäkurantti2\t";
		$tuoterivi .= "$row[epakurantti2pvm]\t";
		$headerivi .= "tuotekerroin\t";
		$tuoterivi .= str_replace(".",",",$toimirow['tuotekerroin'])."\t";
		$headerivi .= "aleryhmä\t";
		$tuoterivi .= "\"$row[aleryhma]\"\t";
		$headerivi .= "keskihankintahinta\t";
		$tuoterivi .= str_replace(".",",",$row["kehahin"])."\t";

		// sitten lasketaan myynnit, kulutukset jne per valittu yhtiö
		foreach ($valitutyhtiot as $valittuyhtio) {

			// tutkaillaan myynti
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
	   					sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) kate1,
	   					sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kate,0)) kate2,
	   					sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kate,0)) kate3,
	   					sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kate,0)) kate4,
	   					sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta1,
	   					sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,rivihinta,0)) rivihinta2,
	   					sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,rivihinta,0)) rivihinta3,
	   					sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,rivihinta,0)) rivihinta4
	   					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
						JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and konserniyhtio = '')
	   					WHERE tilausrivi.yhtio = '$valittuyhtio'
	   					and tyyppi='L'
	   					and tuoteno = '$row[tuoteno]'
	   					and ((laskutettuaika >= '$apvm' and laskutettuaika <= '$lpvm') or laskutettuaika = '0000-00-00')";
			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			$katepros1 = 0;
			$katepros2 = 0;
			$katepros3 = 0;
			$katepros4 = 0;

			if ($laskurow['rivihinta1'] <> 0) $katepros1 = round($laskurow['kate1'] / $laskurow['rivihinta1'] * 100,0);
			if ($laskurow['rivihinta2'] <> 0) $katepros2 = round($laskurow['kate2'] / $laskurow['rivihinta2'] * 100,0);
			if ($laskurow['rivihinta3'] <> 0) $katepros3 = round($laskurow['kate3'] / $laskurow['rivihinta3'] * 100,0);
			if ($laskurow['rivihinta4'] <> 0) $katepros4 = round($laskurow['kate4'] / $laskurow['rivihinta4'] * 100,0);

			// Myydyt kappaleet
			$headerivi .= "$valittuyhtio kpl1\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kpl1'])."\t";
			$headerivi .= "$valittuyhtio kpl2\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kpl2'])."\t";
			$headerivi .= "$valittuyhtio kpl3\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kpl3'])."\t";
			$headerivi .= "$valittuyhtio kpl4\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kpl4'])."\t";
			$headerivi .= "$valittuyhtio edkpl1\t";
			$tuoterivi .= str_replace(".",",",$laskurow['EDkpl1'])."\t";
			$headerivi .= "$valittuyhtio edkpl2\t";
			$tuoterivi .= str_replace(".",",",$laskurow['EDkpl2'])."\t";
			$headerivi .= "$valittuyhtio edkpl3\t";
			$tuoterivi .= str_replace(".",",",$laskurow['EDkpl3'])."\t";
			$headerivi .= "$valittuyhtio edkpl4\t";
			$tuoterivi .= str_replace(".",",",$laskurow['EDkpl4'])."\t";

			// Kate
			$headerivi .= "$valittuyhtio kate1\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kate1'])."\t";
			$headerivi .= "$valittuyhtio kate2\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kate2'])."\t";
			$headerivi .= "$valittuyhtio kate3\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kate3'])."\t";
			$headerivi .= "$valittuyhtio kate4\t";
			$tuoterivi .= str_replace(".",",",$laskurow['kate4'])."\t";
			$headerivi .= "$valittuyhtio katepro1\t";
			$tuoterivi .= str_replace(".",",",$katepros1)."\t";
			$headerivi .= "$valittuyhtio katepro2\t";
			$tuoterivi .= str_replace(".",",",$katepros2)."\t";
			$headerivi .= "$valittuyhtio katepro3\t";
			$tuoterivi .= str_replace(".",",",$katepros3)."\t";
			$headerivi .= "$valittuyhtio katepro4\t";
			$tuoterivi .= str_replace(".",",",$katepros4)."\t";

			// Puute kappaleet
			$headerivi .= "$valittuyhtio puutekpl1\t";
			$tuoterivi .= str_replace(".",",",$laskurow['puutekpl1'])."\t";
			$headerivi .= "$valittuyhtio puutekpl2\t";
			$tuoterivi .= str_replace(".",",",$laskurow['puutekpl2'])."\t";
			$headerivi .= "$valittuyhtio puutekpl3\t";
			$tuoterivi .= str_replace(".",",",$laskurow['puutekpl3'])."\t";
			$headerivi .= "$valittuyhtio puutekpl4\t";
			$tuoterivi .= str_replace(".",",",$laskurow['puutekpl4'])."\t";

/*
			// kommentoidaan kuluts nyt näin aluksi turhana
			// lasketaan tuotteen kulutus
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
						WHERE yhtio = '$valittuyhtio'
						and tyyppi = 'V'
						and tuoteno = '$row[tuoteno]'
						and ((toimitettuaika >= '$apvm' and toimitettuaika <= '$lpvm') or toimitettuaika = '0000-00-00')";
			$result   = mysql_query($query) or pupe_error($query);
			$kulutrow = mysql_fetch_array($result);

			// kulutetut kappaleet
			$headerivi .= "$valittuyhtio kulu kpl1\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['kpl1'])."\t";
			$headerivi .= "$valittuyhtio kulu kpl2\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['kpl2'])."\t";
			$headerivi .= "$valittuyhtio kulu kpl3\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['kpl3'])."\t";
			$headerivi .= "$valittuyhtio kulu kpl4\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['kpl4'])."\t";
			$headerivi .= "$valittuyhtio kulu edkpl1\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['EDkpl1'])."\t";
			$headerivi .= "$valittuyhtio kulu edkpl2\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['EDkpl2'])."\t";
			$headerivi .= "$valittuyhtio kulu edkpl3\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['EDkpl3'])."\t";
			$headerivi .= "$valittuyhtio kulu edkpl4\t";
			$tuoterivi .= str_replace(".",",",$kulutrow['EDkpl4'])."\t";
*/
			//tilauksessa, ennakkopoistot ja jt
			$query = "	SELECT tilausrivi.yhtio,
						sum(if(tyyppi='O', varattu, 0)) tilattu,
						sum(if(tyyppi='L' or tyyppi='V', varattu, 0)) ennpois,
						sum(if(tyyppi='L' or tyyppi='G', jt, 0)) jt
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
						JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and konserniyhtio = '')
						WHERE tilausrivi.yhtio = '$valittuyhtio'
	 					and tyyppi in ('L','V','O','G')
						and tuoteno = '$row[tuoteno]'
						and laskutettuaika = '0000-00-00'
						and (varattu>0 or jt>0)
						GROUP BY yhtio
						ORDER BY yhtio";
			$result = mysql_query($query) or pupe_error($query);
			$ennp = mysql_fetch_array($result);

			// ennakkapoisot, tilattu ja jt
			$headerivi .= "$valittuyhtio tilattu kpl\t";
			$tuoterivi .= str_replace(".",",",$ennp['tilattu'])."\t";
			$headerivi .= "$valittuyhtio ennpois kpl\t";
			$tuoterivi .= str_replace(".",",",$ennp['ennpois'])."\t";
			$headerivi .= "$valittuyhtio jt kpl\t";
			$tuoterivi .= str_replace(".",",",$ennp['jt'])."\t";

			if ($varastot_maittain == "KYLLA") {

				foreach ($valitutmaat as $maarivi) {

					list($yhtio, $maa) = split("#",$maarivi);

					if ($yhtio == $valittuyhtio) {
						// Kaikkien valittujen varastojen saldo per maa
						$query = "	SELECT ifnull(sum(saldo),0) saldo
									FROM tuotepaikat
									JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
									and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and varastopaikat.maa = '$maa'
									WHERE tuotepaikat.yhtio = '$valittuyhtio'
									and tuotepaikat.tuoteno = '$row[tuoteno]'";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) > 0) {
							while ($varrow = mysql_fetch_array($result)) {
								$headerivi .= "$valittuyhtio $maa saldo\t";
								$tuoterivi .= str_replace(".",",",$varrow['saldo'])."\t";
							}
						}
						else {
							$headerivi .= "$valittuyhtio $maa saldo\t";
							$tuoterivi .= "0\t";
						}
					}
				}
			}

		}

		if ($varastot_paikoittain == "KYLLA") {

			foreach ($valitutvarastot as $varastotunnus) {

				// saldot per varasto
				$query = "	SELECT nimitys, ifnull(sum(saldo),0) saldo
							FROM varastopaikat
							JOIN tuotepaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and tuotepaikat.tuoteno = '$row[tuoteno]'
							WHERE varastopaikat.tunnus = '$varastotunnus'
							GROUP BY 1";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					$varrow = mysql_fetch_array($result);
					$headerivi .= "$varrow[nimitys] saldo\t";
					$tuoterivi .= str_replace(".",",",$varrow['saldo'])."\t";
				}
				else {
					$headerivi .= "$varrow[nimitys] saldo\t";
					$tuoterivi .= "0\t";
				}
			}

		}

		// lisätään tämän tuotteen rivi outputtiin
		$rivi .= $tuoterivi."\n";

		// tehdään arvio kauan tämä kestää.. wau! :)
		if (count($arvio)<=$joukko) {
			$timeparts = explode(" ",microtime());
			$endtime   = $timeparts[1].substr($timeparts[0],1);
			$arvio[]   = round($endtime-$alkuaika,4);

			if (count($arvio) == $joukko) {
				$ka   = array_sum($arvio) / count($arvio);
				$aika = round(mysql_num_rows($res) * $ka, 0);
				echo t("Arvioitu ajon kesto")." $aika sec.<br>";
				flush();
			}
			else {
				$timeparts = explode(" ",microtime());
				$alkuaika  = $timeparts[1].substr($timeparts[0],1);
			}
		}

		$timeparts = explode(" ",microtime());
		$loppu     = $timeparts[1].substr($timeparts[0],1);
		$ajat[]    = round($loppu-$alku,4);

	}

	$timeparts = explode(" ",microtime());
	$loppu     = $timeparts[1].substr($timeparts[0],1);
	$aika      = round($loppu-$alkuaika,4);

	echo t("Toteutunut ajon kesto")." $aika sec.<br><br>";

	$file = "$headerivi\n$rivi";

	echo "<form method='post' action='$PHP_SELF'>";

	echo "<table>";
	echo "<tr><th>".t("Tallenna tulos")."</th>";
	echo "<td>";
	echo "<input type='radio' name='kaunisnimi' value='ostoehdotus.xls' checked> Excel-muodossa<br>";
	echo "<input type='radio' name='kaunisnimi' value='ostoehdotus.csv'> OpenOffice-muodossa<br>";
	echo "<input type='radio' name='kaunisnimi' value='ostoehdotus.txt'> Tekstitiedostona";
	echo "</td>";
	echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
	echo "<input type='hidden' name='file' value='$file'>";
	echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
	echo "</table>";
	echo "</form>";
}


// näytetään käyttöliittymä..
if ($tee == "" or !isset($ehdotusnappi)) {

	if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tee' value='RAPORTOI'>

			<table>
			<tr><th>".t("Osasto")."</th><td colspan='3'>";

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
			<tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>";

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
			<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>";

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

	echo "</td></tr>";

	$sel = array();
	$sel[$abcrajaus] = "SELECTED";

	// katotaan onko abc aputaulu rakennettu
	$query  = "select count(*) from abc_aputaulu where yhtio='$kukarow[yhtio]' and tyyppi = 'TK'";
	$abcres = mysql_query($query) or pupe_error($query);
	$abcrow = mysql_fetch_array($abcres);

	// jos on niin näytetään tällänen vaihtoehto
	if ($abcrow[0] > 0) {
		echo "<tr><th>".t("ABC-luokkarajaus")."</th><td colspan='3'>
		<select name='abcrajaus' onchange='submit();'>
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

	echo "<tr><th>".t("Toimittaja")."</th><td colspan='3'><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";
	echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

	echo "	<tr><td class='back'><br></td></tr>";

	if ($ostoehdotus == "") $ostoehdotus = 1;
	$oseh = array();
	$oseh[$ostoehdotus] = "CHECKED";

	echo "	<tr>
			<td class='back'></td><th colspan='3'>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td class='back'></td><th colspan='3'>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>
			<th>".t("Ostoehdotus")."</th></tr>";

	echo "	<tr><th>".t("Kausi 1")."</th>
			<td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
			<td><input type='text' name='kka1' value='$kka1' size='5'></td>
			<td><input type='text' name='vva1' value='$vva1' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
			<td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
			<td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='1' $oseh[1]></td></tr>";

	echo "	<tr><th>".t("Kausi 2")."</th>
			<td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
			<td><input type='text' name='kka2' value='$kka2' size='5'></td>
			<td><input type='text' name='vva2' value='$vva2' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
			<td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
			<td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='2' $oseh[2]></td></tr>";

	echo "	<tr><th>".t("Kausi 3")."</th>
			<td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
			<td><input type='text' name='kka3' value='$kka3' size='5'></td>
			<td><input type='text' name='vva3' value='$vva3' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
			<td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
			<td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='3' $oseh[3]></td></tr>";

	echo "	<tr><th>".t("Kausi 4")."</th>
			<td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
			<td><input type='text' name='kka4' value='$kka4' size='5'></td>
			<td><input type='text' name='vva4' value='$vva4' size='5'></td>
			<td class='back'> - </td>
			<td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
			<td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
			<td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
			<td><input type='radio' name='ostoehdotus' value='4' $oseh[4]></td></tr>";

	echo "	<tr><td class='back'><br></td></tr>";

	//Ostokausivalinnat
	$kaudet_oletus = array(1,3,4);
	$kaudet_kaikki = array(1,2,3,4,5,6,7,8,9,10,11,12,24);

	$kaudet = array();
	$kaulas = 1;

	foreach ($kaudet_oletus as $kausi1) {

		echo "<tr><th>Ostoehdotus $kaulas:</th><td colspan='3'><select name='varastotarve[]'>";

		foreach ($kaudet_kaikki as $kausi2) {

			$chk = "";
			if (is_array($varastotarve)) {
				if (in_array("$kausi1#$kausi2", $varastotarve)) {
					$chk = "selected";
				}
			}
			echo "<option value='$kausi1#$kausi2' $chk>$kausi2</option>";
		}

		echo "</select> kk varastotarve</td></tr>";

		$kaulas++;
	}
	echo "<tr><td class='back'><br></td></tr>";

	$chk = "";
	if ($poistetut != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

	$chk = "";
	if ($eihinnastoon != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

	$chk = "";
	if ($ehdotettavat != "") $chk = "checked";
	echo "<tr><th>".t("Näytä vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='ehdotettavat' $chk></td></tr>";

	if ($abcrajaus != "") {
		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

		$chk = "";
		if ($eiuusia != "") $chk = "checked";
		echo "<tr><th>".t("Älä listaa 12kk sisällä perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='eiuusia' $chk></td></tr>";

		$chk = "";
		if ($vainuudet != "") $chk = "checked";
		echo "<tr><th>".t("Listaa vain 12kk sisällä perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='vainuudet' $chk></td></tr>";
	}

	echo "<tr><td class='back'><br></td></tr>";

	// yhtiövalinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]' and konserni != ''";
	$presult = mysql_query($query) or pupe_error($query);

	$vlask 		= 0;

	if (mysql_num_rows($presult) > 0) {

		$yhtiot = "";

		while ($prow = mysql_fetch_array($presult)) {

			$chk = "";
			if (is_array($valitutyhtiot)) {
				if (in_array($prow["yhtio"], $valitutyhtiot) != '') {
					$chk = "CHECKED";
					$yhtiot .= "'$prow[yhtio]',";
				}
			}
			elseif ($prow["yhtio"] == $kukarow["yhtio"]) {
				$chk = "CHECKED";
			}

			if ($vlask == 0) {
				echo "<tr><th rowspan='".mysql_num_rows($presult)."'>Huomioi yhtiön tiedot/myynnit:</th>";
			}
			else {
				echo "<tr>";
			}

			echo "<td colspan='3'><input type='checkbox' name='valitutyhtiot[]' value='$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";

			$vlask++;
		}

		$yhtiot = substr($yhtiot,0,-1);

		if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

		echo "<tr><td class='back'><br></td></tr>";
	}

	// katsotaan onko firmalla varastoja useassa maassa
	$query = "select distinct maa from varastopaikat where maa != '' and yhtio in ($yhtiot) order by yhtio, maa";
	$vtresult = mysql_query($query) or pupe_error($query);

	// useampi maa löytyy, annetaan mahdollisuus tutkailla saldoja per maa
	if (mysql_num_rows($vtresult) > 1) {

		// katsotaan onko firmalla varastoja useassa maassa
		$query = "select distinct yhtio, maa from varastopaikat where maa != '' and yhtio in ($yhtiot) order by yhtio, maa";
		$vtresult = mysql_query($query) or pupe_error($query);
		$vlask = 0;

		while ($vrow = mysql_fetch_array($vtresult)) {

			$chk = "";
			if (is_array($valitutmaat)) {
				if (in_array("$vrow[yhtio]#$vrow[maa]", $valitutmaat) != '') {
					$chk = "CHECKED";
				}
			}

			if ($vlask == 0) {
				echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot maittain:")."</th>";
			}
			else {
				echo "<tr>";
			}

			echo "<td colspan='3'><input type='checkbox' name='valitutmaat[]' value='$vrow[yhtio]#$vrow[maa]' $chk> $vrow[maa] ($vrow[yhtio])</td></tr>";

			$vlask++;
		}

		echo "<tr><td class='back'><br></td></tr>";

	}

	//Valitaan varastot joiden saldot huomioidaan
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio in ($yhtiot)
				ORDER BY yhtio, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	$vlask = 0;

	while ($vrow = mysql_fetch_array($vtresult)) {

		$chk = "";
		if (is_array($valitutvarastot)) {
			if (in_array($vrow["tunnus"], $valitutvarastot) != '') {
				$chk = "CHECKED";
			}
		}

		if ($vlask == 0) {
			echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot varastoittain:")."</th>";
		}
		else {
			echo "<tr>";
		}

		echo "<td colspan='3'><input type='checkbox' name='valitutvarastot[]' value='$vrow[tunnus]' $chk> $vrow[nimitys] ($vrow[yhtio])</td></tr>";

		$vlask++;
	}

	echo "</table>";

	echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'>
		</form>";

}

require ("../inc/footer.inc");

?>
