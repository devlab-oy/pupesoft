<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Varastojen hälytysrajat")."</font><hr>";

list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);


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
	//tarvitaan multiple select boksin tekoon
	$kutsuja = "varastojen_halytysraja.php";
	$multi = "multiple";

	require 'inc/tuotehaku.inc';

	if ($tee == 'Y') {
		$tee = '';
	}
}


if ((isset($_POST['muutparametrit']) and $_POST['muutparametrit'] != '') and !isset($tuoteno) and !isset($tuoteno_array)) {
	$tuoteno = $muutparametrit;
}

if (isset($tuoteno_array)) {

	$muutparametrit = "";
	foreach ($tuoteno_array as $tarray => $tuotevalue) {
		$muutparametrit .= "'$tuotevalue',";
	}
	$muutparametrit = substr($muutparametrit,0,-1); // vika pilkku pois

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
		$result = pupe_query($query);

		$laskuri++;

	}

	echo "Päivitetiin $laskuri tuotetta.<br><br>";
	$tee = "";

}

if ($tee == "selaa" and isset($ehdotusnappi)) {

	// scripti balloonien tekemiseen
	js_popup();


	//varastot queryyn
	if (!empty($varastot)) {
		$lisa_varastot = " and varastopaikat.tunnus IN (";
		foreach ($varastot as $key => $value) {
			$lisa_varastot .= "'$value',";
		}
		$lisa_varastot = substr($lisa_varastot,0,-1); // vika pilkku pois

		$lisa_varastot .= ")";
    }

	echo "<table><tr><td class='back' valign='top'>";
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
	//usea tuote
	if (isset($tuoteno_array)) {
		echo "<tr><th>".t("Tuoteno")."</th><td>$tuoteno</td></tr>";
		$lisaa .= " and tuote.tuoteno in ($muutparametrit) ";
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
	$result = pupe_query($query);
	$erorow = mysql_fetch_array($result);

	// tässä on itse query

	$query = "	SELECT
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
				varastopaikat.tunnus,
				varastopaikat.nimitys varastonnimi,
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
				$lisa_varastot)
				WHERE
				tuote.yhtio = '$kukarow[yhtio]'
				$lisaa
				and tuote.ei_saldoa = ''
				group by tuote.tuoteno, varastopaikat.tunnus, tuotepaikat.tunnus
				ORDER BY tuote.tuoteno, varastopaikat.tunnus, tuotepaikat.tunnus";
	$res = pupe_query($query);

	echo "<form method='post' autocomplete='off'>
		<input type='hidden' name='tee' value='paivita'>";

	echo "\n<table>";

	echo "<tr>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Varasto")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("S")."</th>";
	if ($abcpaalla == "kylla") {
		echo "<th>".t("Abc")."</th>";
	}
	echo "<th>".t("Puute")."</th>";
	echo "<th>".t("Kok.Saldo")."<br>".t("Var.Saldo")."</th>";
	echo "<th>".t("Kok.Myynti")."<br>".t("Var.Myynti")."</th>";
	if ($maittain != "") {
		echo "<th>".t("Myynti")."<br>".t("maittain")."</th>";
	}
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
		echo "<td>$row[varastonnimi]</td>";
		echo "<td>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
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
		$result   = pupe_query($query);
		$summarow = mysql_fetch_array($result);

		echo "<td align='right'>$summarow[puutekpl1]</td>";

		// saldo myytävissa kaikki varastot
		list(, , $saldo) = saldo_myytavissa($row["tuoteno"], "KAIKKI");
		echo "<td align='right'>".sprintf("%.2f",$saldo)."<br>";

		// saldo myytävissa tämä varasto
		list(, , $saldo) = saldo_myytavissa($row["tuoteno"], "KAIKKI", $row[tunnus]);
		echo sprintf("%.2f",$saldo)."</td>";

		echo "<td align='right' class='tooltip' id='$row[paikkatunnus]'>$summarow[kpl1]<br/>$summarow[varastonkpl1]</td>";

		//Haetaan mihin maahan tuote on toimitettu
		if ($maittain != "") {
			$query = "  SELECT lasku.toim_maa, sum(if(tilausrivi.laskutettuaika >= '$vva1-$kka1-$ppa1' and tilausrivi.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kpl,0)) maakpl
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
   						and tilausrivi.tyyppi = 'L'
   						and tilausrivi.tuoteno = '$row[tuoteno]'
						and ((tilausrivi.laskutettuaika >= '$apvm' and tilausrivi.laskutettuaika <= '$lpvm') or tilausrivi.laskutettuaika = '0000-00-00')
						group by lasku.toim_maa";
			$result   = pupe_query($query);

			echo "<td align='right'>";
			while ($maarow = mysql_fetch_array($result)) {
				if ($maarow[toim_maa] != '' and $maarow[maakpl] != 0) {
					echo "$maarow[toim_maa] $maarow[maakpl]<br>";
				}

			}
			echo "</td>";
		}

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
		$divit .= "<div id='div_$row[paikkatunnus]' class='popup' style='width:250px;'>";
		$divit .= "<table style='width:250px;'>";
		$divit .= "<tr><th nowrap>".t("Kok.Myynti 1")."</th>";
		$divit .= "<td align='right'>$summarow[kpl1]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Var.Myynti 1")."</th>";
		$divit .= "<td align='right'>$summarow[varastonkpl1]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Kok.Myynti 2")."</th>";
		$divit .= "<td align='right'>$summarow[kpl2]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Var.Myynti 2")."</th>";
		$divit .= "<td align='right'>$summarow[varastonkpl2]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Kok.Myynti 3")."</th>";
		$divit .= "<td align='right'>$summarow[kpl3]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Var.Myynti 3")."</th>";
		$divit .= "<td align='right'>$summarow[varastonkpl3]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Kok.Myynti 4")."</th>";
		$divit .= "<td align='right'>$summarow[kpl4]</td></tr>";
		$divit .= "<tr><th nowrap>".t("Var.Myynti 4")."</th>";
		$divit .= "<td align='right'>$summarow[varastonkpl4]</td></tr>";
		$divit .= "</table>";
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

	echo "<form method='post' autocomplete='off'>
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
	if (isset($tuoteno)  and trim($ulos) != '') {
		echo $ulos;
	}
	else {
		echo "<input type='text' size='20' name='tuoteno' value='$tuoteno'></td>";
	}

	echo "</td></tr></table>";

	echo "<table>";

	//Valitaan varastot
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vtresult = pupe_query($query);


	echo "<tr><th valign=top>" . t('Varastot') . "<br /><br /><span style='font-size: 0.8em;'>"
		. t('Saat kaikki varastot jos et valitse yhtään')
		. "</span></th>
	    <td>";

	$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

	while ($varow = mysql_fetch_array($vtresult)) {
		$sel = '';
		if (in_array($varow['tunnus'], $varastot)) {
			$sel = 'checked';
		}

		echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
	}

	echo "</select></td></tr>\n";

	echo "<tr><th>".t("Hälytysrajan laskenta")."</th>\n";
	echo "<td><select name='tarve'>\n";
	echo "<option value='3'>".t("3 pv tarve")."</option>";
	echo "<option value='7'>".t("7 pv tarve")."</option>";
	echo "<option value='14' selected>".t("14 pv tarve")."</option>";
	echo "<option value='21'>".t("21 pv tarve")."</option>";
	echo "<option value='30'>".t("30 pv tarve")."</option>";
	echo "<option value='45'>".t("45 pv tarve")."</option>";
	echo "<option value='60'>".t("60 pv tarve")."</option>";
	echo "<option value='90'>".t("90 pv tarve")."</option>";
	echo "</select></td></tr>\n";

	echo "<tr><th>".t("Osasto")."</th><td>";

	// tehdään avainsana query
	$sresult = t_avainsana("OSASTO");

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
	// tehdään avainsana query
	$sresult = t_avainsana("TRY");

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
	$sresult = pupe_query($query);

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

	echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

	echo "<select name='abcrajaus'>";
	echo "<option  value=''>".t("Valitse")."</option>";

	$teksti="";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		if ($i != 0) $teksti = t("ja paremmat");
		echo "<option  value='$i##TM'>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
	}

	$teksti="";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		if ($i != 0) $teksti = t("ja paremmat");
		echo "<option  value='$i##TK'>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
	}

	$teksti="";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		if ($i !=0) $teksti = t("ja paremmat");
		echo "<option  value='$i##TR'>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
	}

	$teksti="";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		if ($i !=0) $teksti = t("ja paremmat");
		echo "<option  value='$i##TP'>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
	}

	echo "</select>";

	echo "<tr><th>".t("Uusi tuote")." on</th>";
	echo "<td><select name='uusienika'>";
	echo "<option value='12'>".t("alle 12 kk vanha")."</option>";
	echo "<option value='6' selected>".t("alle 6 kk vanha")."</option>";
	echo "<option value='3'>".t("alle 3 kk vanha")."</option>";
	echo "<option value='1'>".t("alle 1 kk vanha")."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>".t("Uusien tuotteiden näkyvyys")."</th>";
	echo "<td><select name='uudettuotteet'>";
	echo "<option value='eiuusia'>".t("Älä listaa uusia tuotteita")."</option>";
	echo "<option value='vainuudet'>".t("Listaa vain uudet tuotteet")."</option>";
	echo "<option value='kaikki'>".t("Listaa kaikki tuotteet")."</option>";
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

	$chk = "";
	if ($maittain != "") $chk = "checked";
	echo "<tr><th>".t("Näytä myynti maittain")."</th><td colspan='3'><input type='checkbox' name='maittain' $chk></td></tr>";
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
