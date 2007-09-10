<?php

// käytetään slavea
// $useslave = 1;

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Varastojen hälytysrajat")."</font><hr>";

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

//katotaan pienin alkupvm ja isoin loppupvm
$apaiva1 = (int) date('Ymd',mktime(0,0,0,$kka1,$ppa1,$vva1));
$apaiva2 = (int) date('Ymd',mktime(0,0,0,$kka2,$ppa2,$vva2));
$apaiva3 = (int) date('Ymd',mktime(0,0,0,$kka3,$ppa3,$vva3));
$apaiva4 = (int) date('Ymd',mktime(0,0,0,$kka4,$ppa4,$vva4));

$lpaiva1 = (int) date('Ymd',mktime(0,0,0,$kkl1,$ppl1,$vvl1));
$lpaiva2 = (int) date('Ymd',mktime(0,0,0,$kkl2,$ppl2,$vvl2));
$lpaiva3 = (int) date('Ymd',mktime(0,0,0,$kkl3,$ppl3,$vvl3));
$lpaiva4 = (int) date('Ymd',mktime(0,0,0,$kkl4,$ppl4,$vvl4));

$apienin = 99999999;
$lsuurin = 0;

if ($apaiva1 <= $apienin and $apaiva1 != 19700101) $apienin = $apaiva1;
if ($apaiva2 <= $apienin and $apaiva2 != 19700101) $apienin = $apaiva2;
if ($apaiva3 <= $apienin and $apaiva3 != 19700101) $apienin = $apaiva3;
if ($apaiva4 <= $apienin and $apaiva4 != 19700101) $apienin = $apaiva4;

if ($lpaiva1 >= $lsuurin and $lpaiva1 != 19700101) $lsuurin = $lpaiva1;
if ($lpaiva2 >= $lsuurin and $lpaiva2 != 19700101) $lsuurin = $lpaiva2;
if ($lpaiva3 >= $lsuurin and $lpaiva3 != 19700101) $lsuurin = $lpaiva3;
if ($lpaiva4 >= $lsuurin and $lpaiva4 != 19700101) $lsuurin = $lpaiva4;

if ($apienin == 99999999 and $lsuurin == 0) {
	$apienin = $lsuurin = date('Ymd'); // jos mitään ei löydy niin NOW molempiin. :)
}

$apvm = substr($apienin,0,4)."-".substr($apienin,4,2)."-".substr($apienin,6,2);
$lpvm = substr($lsuurin,0,4)."-".substr($lsuurin,4,2)."-".substr($lsuurin,6,2);

if (isset($tuoteno) and $tuoteno != '') {
	require 'inc/tuotehaku.inc';
	
	if (empty($trow)) {
		$tee = '';
	}
}

if ((isset($_POST['muutparametrit']) and $_POST['muutparametrit'] != '') and ! isset($tuoteno)) {
	$tuoteno = $muutparametrit;
}

// katsotaan tarvitaanko mennä toimittajahakuun
if ($ytunnus_haku != "") {
	$ytunnus = $ytunnus_haku;
	if (isset($tuoteno)) {
		$muutparametrit = $tuoteno;
	}
	
	$toimittajaid = "";
	require ("inc/kevyt_toimittajahaku.inc");
	$toimittajaid  = $toimittajarow["tunnus"];
	$tee = "";
	$ytunnus = '';
}

if ($tee == "paivita") {

	$laskuri = 0;

	foreach ($halytysraja as $tunnus => $haly) {

		$tilm = $tilausmaara[$tunnus];

		$query = "	UPDATE tuotepaikat SET
		 			halytysraja = '$haly',
					tilausmaara = '$tilm'
					WHERE yhtio='$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$laskuri++;

	}

	echo "Päivitetiin $laskuri tuotetta.<br><br>";
	$tee = "";

}

if ($tee == "selaa" and isset($ehdotusnappi)) {

	// scripti balloonien tekemiseen
	js_popup();

	$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
	$vtresult = mysql_query($query) or pupe_error($query);
	$vrow = mysql_fetch_array($vtresult);

	echo "<table><tr><td class='back' valign='top'>";
	echo "<table><tr><th>Varasto</th><td>$vrow[nimitys]</td></tr>";
	echo "<tr><th>Hälytysrajan laskenta</th><td>$tarve pv tarve</td></tr>";

	$lisaa  = ""; // tuote-rajauksia
	$lisaa2 = ""; // toimittaja-rajauksia
	$divit  = "";

	if ($osasto != '') {
		echo "<tr><th>".t("Osasto")."</th><td>$osasto</td></tr>";
		$lisaa .= " and tuote.osasto = '$osasto' ";
	}
	if ($tuoryh != '') {
		echo "<tr><th>".t("Try")."</th><td>$tuoryh</td></tr>";
		$lisaa .= " and tuote.try = '$tuoryh' ";
	}
	if ($tuotemerkki != '') {
		echo "<tr><th>".t("Tuotemerkki")."</th><td>$tuotemerkki</td></tr>";
		$lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
	}
	if ($poistetut != '') {
		$lisaa .= " and tuote.status != 'P' ";
	}
	if ($poistuva != '') {
		$lisaa .= " and tuote.status != 'X' ";
	}
	if ($tuoteno != '') {
		echo "<tr><th>".t("Tuoteno")."</th><td>$tuoteno</td></tr>";
		$lisaa .= " and tuote.tuoteno = '$tuoteno' ";
	}
	if ($eihinnastoon != '') {
		$lisaa .= " and tuote.hinnastoon != 'E' ";
	}
	if ($uudettuotteet == "vainuudet") {
		$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval $uusienika month) ";
	}
	if ($uudettuotteet == "eiuusia") {
		$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval $uusienika month) ";
	}
	if ($toimittajaid != '') {
		$lisaa2 .= " JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid') ";
	}
	if ($abcrajaus != "") {
		echo "<tr><th>".t("Abc")."</th><td>$ryhmanimet[$abcrajaus] +</td></tr>";

		// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
		$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
					and abc_aputaulu.tuoteno = tuote.tuoteno
					and abc_aputaulu.tyyppi = '$abcrajaustapa'
					and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus')) ";
	}
	else {
		$abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
	}

	echo "</table></td><td class='back' valign='top'><table>";
	echo "<tr><th>".t("Kausi 1")."</th><td>$vva1-$kka1-$ppa1 - $vvl1-$kkl1-$ppl1</td><td class='back'> <-- ".t("käytetään hälytysrajaehdotuksen laskentaan")."</td></tr>";
	echo "<tr><th>".t("Kausi 2")."</th><td>$vva2-$kka2-$ppa2 - $vvl2-$kkl2-$ppl2</td></tr>";
	echo "<tr><th>".t("Kausi 3")."</th><td>$vva3-$kka3-$ppa3 - $vvl3-$kkl3-$ppl3</td></tr>";
	echo "<tr><th>".t("Kausi 4")."</th><td>$vva4-$kka4-$ppa4 - $vvl4-$kkl4-$ppl4</td></tr>";
	echo "</table>";
	echo "</td></tr></table>";

	// hehe, näin on helpompi verrata päivämääriä
	$query  = "SELECT TO_DAYS('$vvl1-$kkl1-$ppl1')-TO_DAYS('$vva1-$kka1-$ppa1') ero";
	$result = mysql_query($query) or pupe_error($query);
	$erorow = mysql_fetch_array($result);

	// tässä on itse query
	$query = "	select
				tuote.tuoteno,
				tuote.nimitys,
				tuote.status,
				ifnull(abc_aputaulu.luokka, 8) abcluokka,
				ifnull(abc_aputaulu.luokka_osasto, 8) abcluokka_osasto,
				ifnull(abc_aputaulu.luokka_try, 8) abcluokka_try,
				varastopaikat.alkuhyllyalue,
				varastopaikat.alkuhyllynro,
				varastopaikat.loppuhyllyalue,
				varastopaikat.loppuhyllynro,
				tuotepaikat.tilausmaara,
				tuotepaikat.halytysraja,
				tuotepaikat.tunnus paikkatunnus
				FROM tuote
				$lisaa2
				$abcjoin
				JOIN tuotepaikat ON (tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno)
				JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
				and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
				and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
				and varastopaikat.tunnus = '$varasto')
				WHERE
				tuote.yhtio = '$kukarow[yhtio]'
				$lisaa
				and tuote.ei_saldoa = ''
				group by tuote.tuoteno
				ORDER BY tuote.tuoteno";
	$res = mysql_query($query) or pupe_error($query);

	echo "<form action='$PHP_SELF' method='post' autocomplete='off'>
		<input type='hidden' name='tee' value='paivita'>";

	echo "\n<table>";

	echo "<tr>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("S")."</th>";
	if ($abcpaalla == "kylla") {
		echo "<th>".t("Abc")."</th>";
	}
	echo "<th>".t("Puute")."</th>";
	echo "<th>".t("Kok.Saldo")."</th>";
	echo "<th>".t("Kok.Myynti")."</th>";
	echo "<th>".t("Var.Saldo")."</th>";
	echo "<th>".t("Var.Myynti")."</th>";
	echo "<th>".t("Hälytysraja")."</th>";
	echo "<th>".t("Tilausmäärä")."</th>";
	echo "<th>".t("Hälyehdotus")."</th>";

	echo "</tr>\n";

	while ($row = mysql_fetch_array($res)) {

		$a = $row["abcluokka"];
		$b = $row["abcluokka_osasto"];
		$c = $row["abcluokka_try"];

		if ($row["luontiaika"] == '0000-00-00') $row["luontiaika"] = "";

		echo "<tr>";
		echo "<td>$row[tuoteno]</td>";
		echo "<td>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";
		echo "<td>$row[status]</td>";

		if ($abcpaalla == "kylla") {
			echo "<td>$ryhmanimet[$a] $ryhmanimet[$b] $ryhmanimet[$c]</td>";
		}

		// tutkaillaan myynti
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
					sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' and
							concat(rpad(upper('$row[alkuhyllyalue]')  ,5,'0'),lpad(upper('$row[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')) and
							concat(rpad(upper('$row[loppuhyllyalue]') ,5,'0'),lpad(upper('$row[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')), kpl, 0)) varastonkpl1,
					sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' and
							concat(rpad(upper('$row[alkuhyllyalue]')  ,5,'0'),lpad(upper('$row[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')) and
							concat(rpad(upper('$row[loppuhyllyalue]') ,5,'0'),lpad(upper('$row[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')), kpl, 0)) varastonkpl2,
					sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' and
							concat(rpad(upper('$row[alkuhyllyalue]')  ,5,'0'),lpad(upper('$row[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')) and
							concat(rpad(upper('$row[loppuhyllyalue]') ,5,'0'),lpad(upper('$row[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')), kpl, 0)) varastonkpl3,
					sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' and
							concat(rpad(upper('$row[alkuhyllyalue]')  ,5,'0'),lpad(upper('$row[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')) and
							concat(rpad(upper('$row[loppuhyllyalue]') ,5,'0'),lpad(upper('$row[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(hyllyalue) ,5,'0'),lpad(upper(hyllynro) ,5,'0')), kpl, 0)) varastonkpl4
   					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
   					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
   					and tyyppi = 'L'
   					and tuoteno = '$row[tuoteno]'
   					and ((laskutettuaika >= '$apvm' and laskutettuaika <= '$lpvm') or laskutettuaika = '0000-00-00')";
		$result   = mysql_query($query) or pupe_error($query);
		$summarow = mysql_fetch_array($result);

		echo "<td align='right'>$summarow[puutekpl1]</td>";

		// saldo myytävissa kaikki varastot
		list(, , $saldo) = saldo_myytavissa($row["tuoteno"], "KAIKKI");
		echo "<td align='right'>".sprintf("%.2f",$saldo)."</td>";

		echo "<td align='right'><a class='menu' onmouseout=\"popUp(event,'$row[paikkatunnus]')\" onmouseover=\"popUp(event,'$row[paikkatunnus]')\">$summarow[kpl1]</a></td>";

		// saldo myytävissa tämä varasto
		list(, , $saldo) = saldo_myytavissa($row["tuoteno"], "KAIKKI", $varasto);
		echo "<td align='right'>".sprintf("%.2f",$saldo)."</td>";

		echo "<td align='right'><a class='menu' onmouseout=\"popUp(event,'$row[paikkatunnus]')\" onmouseover=\"popUp(event,'$row[paikkatunnus]')\">$summarow[varastonkpl1]</a></td>";

		// tässä lasketaan ehdotettava hälytysraja: lasketaan päivän myynti ja kerrotaan haluituilla päivillä
		$halyehdotus = ceil($summarow["varastonkpl1"] / $erorow["ero"] * $tarve);

		// jos käytössä on abc analyysi ja hälyehdotus on nolla ja tuote kuuluu A,B,C,D luokkaan niin laitetaan aina yksi
		if ($abcpaalla == "kylla" and $halyehdotus == 0 and $row["abcluokka"] < 4) {
			$halyehdotus = 1;
		}

		echo "<td align='right'>$row[halytysraja]</td>";

		// sitten input kentät
		echo "<td align='right'><input type='text' name='tilausmaara[$row[paikkatunnus]]' value='$row[tilausmaara]' size='5'></td>";
		echo "<td align='right'><input type='text' name='halytysraja[$row[paikkatunnus]]' value='$halyehdotus' size='5'></td>";

		echo "</tr>\n";

		// tehään popup divi myynneistä
		$divit .= "<div id='$row[paikkatunnus]' class='popup' style='width:750px;'>";
		$divit .= "<table style='width:750px;'><tr>";
		$divit .= "<th nowrap>".t("Kok.Myynti 1")."</th>";
		$divit .= "<th nowrap>".t("Var.Myynti 1")."</th>";
		$divit .= "<th nowrap>".t("Kok.Myynti 2")."</th>";
		$divit .= "<th nowrap>".t("Var.Myynti 2")."</th>";
		$divit .= "<th nowrap>".t("Kok.Myynti 3")."</th>";
		$divit .= "<th nowrap>".t("Var.Myynti 3")."</th>";
		$divit .= "<th nowrap>".t("Kok.Myynti 4")."</th>";
		$divit .= "<th nowrap>".t("Var.Myynti 5")."</th>";
		$divit .= "<th><a href='#' onclick=\"popUp(event,'$row[paikkatunnus]')\">X</a></th>";
		$divit .= "</tr><tr>";
		$divit .= "<td align='right'>$summarow[kpl1]</td>";
		$divit .= "<td align='right'>$summarow[varastonkpl1]</td>";
		$divit .= "<td align='right'>$summarow[kpl2]</td>";
		$divit .= "<td align='right'>$summarow[varastonkpl2]</td>";
		$divit .= "<td align='right'>$summarow[kpl3]</td>";
		$divit .= "<td align='right'>$summarow[varastonkpl3]</td>";
		$divit .= "<td align='right'>$summarow[kpl4]</td>";
		$divit .= "<td align='right'>$summarow[varastonkpl4]</td>";
		$divit .= "<td class='back'></td>";
		$divit .= "</tr></table>";
		$divit .= "</div>\n";

	}

	echo "</table>\n";

	echo "<br><input type='submit' value = '".t("Päivitä")."'>
		</form>";

	// echotaan divit filen loppuun
	echo $divit;
}

// näytetään käyttöliittymä..
if ($tee == "" or !isset($ehdotusnappi)) {

	echo "<form action='$PHP_SELF' method='post' autocomplete='off'>
		<input type='hidden' name='tee' value='selaa'>";

	echo "<table>\n";
	
	echo "<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus_haku' value=''> $toimittajarow[nimi]";
	if ($toimittajaid != '') {
		echo t('Valittu toimittaja') . " $toimittajaid";
	}
	
	echo "</td></tr>";
	//echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	
	if (! empty($varaosavirhe)) {
		$varaosavirhe .= "<br />";
	}
	
	echo "<tr><th>".t("Tuotenumero (haku)")."</th><td>$varaosavirhe";
	if (isset($tuoteno) and trim($ulos) != '') {
		echo $ulos;
	} else {
		echo "<input type='text' size='20' name='tuoteno' value='$tuoteno'>";
	}
	
	echo "</td></tr></table>";
	
	echo "<table>";
	
	//Valitaan varastot
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Käsiteltävä varasto")."</th>\n";
	echo "<td><select name='varasto'>\n";

	while ($vrow = mysql_fetch_array($vtresult)) {
		$sel = '';
		if ($varasto == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value='$vrow[tunnus]' $sel>$vrow[nimitys]</option>\n";
	}

	echo "</select></td></tr>\n";

	echo "<tr><th>".t("Hälytysrajan laskenta")."</th>\n";
	echo "<td><select name='tarve'>\n";
	echo "<option value='7'>".t("7 pv tarve")."</option>";
	echo "<option value='14' selected>".t("14 pv tarve")."</option>";
	echo "<option value='21'>".t("21 pv tarve")."</option>";
	echo "<option value='30'>".t("30 pv tarve")."</option>";
	echo "<option value='45'>".t("45 pv tarve")."</option>";
	echo "<option value='60'>".t("60 pv tarve")."</option>";
	echo "<option value='90'>".t("90 pv tarve")."</option>";
	echo "</select></td></tr>\n";

	echo "<tr><th>".t("Osasto")."</th><td>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','OSASTO_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
				ORDER BY avainsana.selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='osasto'>\n";
	echo "<option value=''>".t("Näytä kaikki")."</option>\n";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($osasto == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>\n";
	}
	echo "</select>\n";

	echo "</td></tr>\n";

	echo "<tr><th>".t("Tuoteryhmä")."</th><td>";

	//Tehdään osasto & tuoteryhmä pop-upit
	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'
				ORDER BY avainsana.selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuoryh'>\n";
	echo "<option value=''>".t("Näytä kaikki")."</option>\n";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuoryh == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>\n";
	}
	echo "</select>";

	echo "</td></tr>";
	echo "<tr><th>".t("Tuotemerkki")."</th><td>";

	//Tehdään osasto & tuoteryhmä pop-upit
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuotemerkki'>\n";
	echo "<option value=''>".t("Näytä kaikki")."</option>\n";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuotemerkki == $srow["tuotemerkki"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>\n";
	}
	echo "</select>";

	echo "</td></tr>";

	// katotaan onko abc aputaulu rakennettu
	$query  = "select count(*) from abc_aputaulu where yhtio='$kukarow[yhtio]' and tyyppi in ('TK','TR','TP')";
	$abcres = mysql_query($query) or pupe_error($query);
	$abcrow = mysql_fetch_array($abcres);

	// jos on niin näytetään tällänen vaihtoehto
	if ($abcrow[0] > 0) {
		echo "<input type='hidden' name='abcpaalla' value='kylla'>";
		echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td>";

		$sel = array();
		$sel[$abcrajaus] = "SELECTED";

		echo "<select name='abcrajaus'>
		<option value=''>".t("Ei rajausta")."</option>
		<option $sel[0] value='0'>".t("Luokka")." A-30</option>
		<option $sel[1] value='1'>".t("Luokka")." B-20 ".t("ja paremmat")."</option>
		<option $sel[2] value='2'>".t("Luokka")." C-15 ".t("ja paremmat")."</option>
		<option $sel[3] value='3'>".t("Luokka")." D-15 ".t("ja paremmat")."</option>
		<option $sel[4] value='4'>".t("Luokka")." E-10 ".t("ja paremmat")."</option>
		<option $sel[5] value='5'>".t("Luokka")." F-05 ".t("ja paremmat")."</option>
		<option $sel[6] value='6'>".t("Luokka")." G-03 ".t("ja paremmat")."</option>
		<option $sel[7] value='7'>".t("Luokka")." H-02 ".t("ja paremmat")."</option>
		<option $sel[8] value='8'>".t("Luokka")." I-00 ".t("ja paremmat")."</option>
		</select>";

		$sel = array();
		$sel[$abcrajaustapa] = "SELECTED";

		echo "<select name='abcrajaustapa'>
		<option $sel[TK] value='TK'>".t("Myyntikate")."</option>
		<option $sel[TR] value='TR'>".t("Myyntirivit")."</option>
		<option $sel[TP] value='TK'>".t("Myyntikappaleet")."</option>
		</select>
		</td></tr>";
	}

	echo "<tr><th>Uusi tuote on</th>";
	echo "<td><select name='uusienika'>";
	echo "<option value='12'>".t("alle 12 kk vanha")."</option>";
	echo "<option value='6' selected>".t("alle 6 kk vanha")."</option>";
	echo "<option value='3'>".t("alle 3 kk vanha")."</option>";
	echo "<option value='1'>".t("alle 1 kk vanha")."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>Uusien tuotteiden näkyvyys</th>";
	echo "<td><select name='uudettuotteet'>";
	echo "<option value='eiuusia'>".t("Älä listaa uusia tuotteita")."</option>";
	echo "<option value='vainuudet'>".t("Listaa vain uudet tuotteet")."</option>";
	echo "</select>";
	echo "</td></tr>";

	//	Oletetaan että käyttäjä ei halyua/saa ostaa poistuvia tai poistettuja tuotteita!
	if(!isset($poistetut)) $poistetut = "checked";
	if(!isset($poistuva)) $poistuva = "checked";

	$chk = "";
	if ($poistetut != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

	$chk = "";
	if ($poistuva != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistuva' $chk></td></tr>";

	$chk = "";
	if ($eihinnastoon != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

	$chk = "";
	if ($ehdotettavat != "") $chk = "checked";
	echo "<tr><th>".t("Näytä vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='ehdotettavat' $chk></td></tr>";


	echo "</table>";

	echo "<br><table>";
	echo "	<tr>
			<td class='back'></td><th colspan='3'>".t("Alku (pp-kk-vvvv)")."</th>
			<th></th><th colspan='3'>".t("Loppu (pp-kk-vvvv)")."</th>
			</tr>";

	echo "	<tr><th>".t("Kausi 1")."</th>
			<td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
			<td><input type='text' name='kka1' value='$kka1' size='5'></td>
			<td><input type='text' name='vva1' value='$vva1' size='5'></td>
			<td> - </td>
			<td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
			<td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
			<td><input type='text' name='vvl1' value='$vvl1' size='5'></td><td class='back'> <-- ".t("käytetään hälytysrajaehdotuksen laskentaan")."</td>
			</tr>";

	echo "	<tr><th>".t("Kausi 2")."</th>
			<td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
			<td><input type='text' name='kka2' value='$kka2' size='5'></td>
			<td><input type='text' name='vva2' value='$vva2' size='5'></td>
			<td> - </td>
			<td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
			<td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
			<td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
			</tr>";

	echo "	<tr><th>".t("Kausi 3")."</th>
			<td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
			<td><input type='text' name='kka3' value='$kka3' size='5'></td>
			<td><input type='text' name='vva3' value='$vva3' size='5'></td>
			<td> - </td>
			<td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
			<td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
			<td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
			</tr>";

	echo "	<tr><th>".t("Kausi 4")."</th>
			<td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
			<td><input type='text' name='kka4' value='$kka4' size='5'></td>
			<td><input type='text' name='vva4' value='$vva4' size='5'></td>
			<td> - </td>
			<td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
			<td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
			<td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
			</tr>";

	echo "</table>";

	echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Jatka")."'>
		</form>";

}

require ("inc/footer.inc");


?>
