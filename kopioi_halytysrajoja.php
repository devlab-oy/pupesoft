<?php

// k�ytet��n slavea
// $useslave = 1;

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Kopioi varastojen h�lytysrajat")."</font><hr>";

// ABC luokkanimet
$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

// Tarvittavat p�iv�m��r�t
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
	$apienin = $lsuurin = date('Ymd'); // jos mit��n ei l�ydy niin NOW molempiin. :)
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

// katsotaan tarvitaanko menn� toimittajahakuun
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

	//p�ivitet��n kohde varaston tuotteen h�lyraja
	$edtuote = "";

	if (isset($kopioitavaraja) && isset($kohde)) {
		foreach ($kopioitavaraja as $tunnus => $haly) {
			if ($edtuote == "" or $edtuote != $kopioitavatuoteno[$tunnus]) {
				foreach ($kohde as $paikkatunnus => $tuotetunnus) {
					if ($kopioitavatuoteno[$tunnus] == $tuotetunnus) {
						$query = "	UPDATE tuotepaikat SET
					 				halytysraja = '$kohdehaly[$paikkatunnus]'
									WHERE yhtio = '$kukarow[yhtio]' and
									tunnus = '$paikkatunnus'";
						$result = mysql_query($query) or pupe_error($query);

						$laskuri++;
					}
				}

				$edtuote = $kopioitavatuoteno[$tunnus];
			}
		}
	}


	echo "P�ivitetiin $laskuri tuotetta.<br><br>";
	$tee = "";

}

if ($tee == "selaa" and isset($ehdotusnappi)) {

	// scripti balloonien tekemiseen
	js_popup();


	//varastot queryyn
	if (!empty($kopioitavavarasto) && !empty($kohdevarasto)) {
		$lisa_varastot = " and varastopaikat.tunnus IN ($kopioitavavarasto, $kohdevarasto)";

		if ($kopioitavavarasto >= $kohdevarasto) {
			$jarjestys = "ORDER BY tuote.tuoteno, varastopaikat.tunnus desc, tuotepaikat.tunnus";
		}
		else {
			$jarjestys = "ORDER BY tuote.tuoteno, varastopaikat.tunnus asc, tuotepaikat.tunnus";
		}
    }

	echo "<table><tr><td class='back' valign='top'>";
	echo "<tr><th>H�lytysrajan laskenta</th><td>$tarve pv tarve</td></tr>";

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

	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]' and (tunnus = '$kopioitavavarasto' or tunnus = '$kohdevarasto')
				ORDER BY nimitys";
	$result = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Varastosta varastoon")."</th><td>";

	while ($ressu = mysql_fetch_array($result)) {
		if ($ressu['tunnus'] == $kopioitavavarasto) {
			$kopvarasto = $ressu["nimitys"];
		}
		else {
			$kohvarasto = $ressu["nimitys"];
		}

	}

	echo "$kopvarasto -> $kohvarasto</td></tr>";

	echo "</table></td><td class='back' valign='top'><table>";
	echo "<tr><th>".t("Kausi 1")."</th><td>$vva1-$kka1-$ppa1 - $vvl1-$kkl1-$ppl1</td><td class='back'> <-- ".t("k�ytet��n h�lytysrajaehdotuksen laskentaan")."</td></tr>";
	echo "<tr><th>".t("Kausi 2")."</th><td>$vva2-$kka2-$ppa2 - $vvl2-$kkl2-$ppl2</td></tr>";
	echo "<tr><th>".t("Kausi 3")."</th><td>$vva3-$kka3-$ppa3 - $vvl3-$kkl3-$ppl3</td></tr>";
	echo "<tr><th>".t("Kausi 4")."</th><td>$vva4-$kka4-$ppa4 - $vvl4-$kkl4-$ppl4</td></tr>";
	echo "</table>";
	echo "</td></tr></table>";

	// hehe, n�in on helpompi verrata p�iv�m��ri�
	$query  = "SELECT TO_DAYS('$vvl1-$kkl1-$ppl1')-TO_DAYS('$vva1-$kka1-$ppa1') ero";
	$result = mysql_query($query) or pupe_error($query);
	$erorow = mysql_fetch_array($result);

	// t�ss� on itse query

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
				$jarjestys";
	$res = mysql_query($query) or pupe_error($query);


	echo "<form action='$PHP_SELF' method='post' autocomplete='off'>
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
	echo "<th>".t("H�lytysraja")."</th>";



	echo "</tr>\n";


	$edtuoteno = "";
	$edkohdetuoteno = "";
	$summa = 0;

	while ($row = mysql_fetch_array($res)) {

		$a = $row["abcluokka"];
		$b = $row["abcluokka_osasto"];
		$c = $row["abcluokka_try"];

		if ($row["luontiaika"] == '0000-00-00') $row["luontiaika"] = "";

		echo "<tr>";
		echo "<td>$row[tuoteno]</td>";

		if ($kopioitavavarasto == $row["tunnus"]) {
			echo "<td>$row[varastonnimi]</td>";
			echo "<input type='hidden' name='kopioitavaraja[$row[paikkatunnus]]' value='$row[halytysraja]'>";
			echo "<input type='hidden' name='kopioitavatuoteno[$row[paikkatunnus]]' value='$row[tuoteno]'>";

			if ($edtuoteno != $row["tuoteno"]) {
				$summa = 0;
			}

			if ($edtuote == "" or $edtuote == $row["tuoteno"]) {
				$summa += $row["halytysraja"];
				$edtuoteno = $row["tuoteno"];
			}

		}
		elseif ($kohdevarasto == $row["tunnus"]) {
			echo "<th>$row[varastonnimi]</th>";

			echo "<input type='hidden' name='kohde[$row[paikkatunnus]]' value='$row[tuoteno]'>";

			if ($edtuoteno != "") {
				$edkohdetuoteno = $edtuoteno;
			}


			if ($edkohdetuoteno == $row["tuoteno"]){
				echo "<input type='hidden' name='kohdehaly[$row[paikkatunnus]]' value='$summa'>";

			}
			else {
				echo "<input type='hidden' name='kohdehaly[$row[paikkatunnus]]' value='$row[halytysraja]'>";
				$edkohdetuoteno = "";
			}


			$summa = 0;
			$edtuoteno = "";
		}


		echo "<td>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
		echo "<td>$row[status]</td>";

		if ($abcpaalla == "kylla") {
			echo "<td>$ryhmanimet[$a] $ryhmanimet[$b] $ryhmanimet[$c]</td>";
		}

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

		// saldo myyt�vissa kaikki varastot
		list(, , $saldo) = saldo_myytavissa($row["tuoteno"], "KAIKKI");
		echo "<td align='right'>".sprintf("%.2f",$saldo)."<br>";

		// saldo myyt�vissa t�m� varasto
		list(, , $saldo) = saldo_myytavissa($row["tuoteno"], "KAIKKI", $row["tunnus"]);
		echo sprintf("%.2f",$saldo)."</td>";


		echo "<td align='right' class='tooltip' id='$row[paikkatunnus]'>$summarow[kpl1]<br>$summarow[varastonkpl1]</td>";

		echo "<td align='right'>$row[halytysraja]</td>";

		echo "</tr>\n";

		// teh��n popup divi myynneist�
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

	echo "<br><input type='submit' value = '".t("P�ivit�")."'>
		</form>";

	// echotaan divit filen loppuun
	echo $divit;
}

// n�ytet��n k�ytt�liittym�..
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
	if (isset($tuoteno)  and trim($ulos) != '') {
		echo $ulos;
	}
	else {
		echo "<input type='text' size='20' name='tuoteno' value='$tuoteno'></td>";
	}

	echo "</td></tr></table>";

	echo "<table>";

	//Tehd��n varastot
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);


	echo "<tr><th>Kopioitava varasto</th>\n";
	echo "<td><select name='kopioitavavarasto'>\n";

	while ($varow = mysql_fetch_array($vtresult)) {
		$sel = '';
		if ($kopioitavavarasto == $varow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>\n";
	}
	echo "</select>\n";
	echo "</td></tr>\n";


	echo "<tr><th>Kohde varasto</th>\n";
	echo "<td><select name='kohdevarasto'>\n";

	mysql_data_seek($vtresult, 0);

	while ($varow = mysql_fetch_array($vtresult)) {
		$sel = '';
		if ($kohdevarasto == $varow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>\n";
	}
	echo "</select>\n";

	echo "</td></tr>\n";

	//echo "</select></td></tr>\n";

	echo "<tr><th>".t("H�lytysrajan laskenta")."</th>\n";
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

	// tehd��n avainsana query
	$sresult = t_avainsana("OSASTO");

	echo "<select name='osasto'>\n";
	echo "<option value=''>".t("N�yt� kaikki")."</option>\n";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($osasto == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>\n";
	}
	echo "</select>\n";

	echo "</td></tr>\n";

	echo "<tr><th>".t("Tuoteryhm�")."</th><td>";

	//Tehd��n osasto & tuoteryhm� pop-upit

	// tehd��n avainsana query
	$sresult = t_avainsana("TRY");

	echo "<select name='tuoryh'>\n";
	echo "<option value=''>".t("N�yt� kaikki")."</option>\n";

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

	//Tehd��n osasto & tuoteryhm� pop-upit
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuotemerkki'>\n";
	echo "<option value=''>".t("N�yt� kaikki")."</option>\n";

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

	// jos on niin n�ytet��n t�ll�nen vaihtoehto
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

	echo "<tr><th>".t("Uusi tuote")." on</th>";
	echo "<td><select name='uusienika'>";
	echo "<option value='12'>".t("alle 12 kk vanha")."</option>";
	echo "<option value='6' selected>".t("alle 6 kk vanha")."</option>";
	echo "<option value='3'>".t("alle 3 kk vanha")."</option>";
	echo "<option value='1'>".t("alle 1 kk vanha")."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>".t("Uusien tuotteiden n�kyvyys")."</th>";
	echo "<td><select name='uudettuotteet'>";
	echo "<option value='eiuusia'>".t("�l� listaa uusia tuotteita")."</option>";
	echo "<option value='vainuudet'>".t("Listaa vain uudet tuotteet")."</option>";
	echo "<option value='kaikki'>".t("Listaa kaikki tuotteet")."</option>";
	echo "</select>";
	echo "</td></tr>";

	//	Oletetaan ett� k�ytt�j� ei halyua/saa ostaa poistuvia tai poistettuja tuotteita!
	if(!isset($poistetut)) $poistetut = "checked";
	if(!isset($poistuva)) $poistuva = "checked";

	$chk = "";
	if ($poistetut != "") $chk = "checked";
	echo "<tr><th>".t("�l� n�yt� poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

	$chk = "";
	if ($poistuva != "") $chk = "checked";
	echo "<tr><th>".t("�l� n�yt� poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistuva' $chk></td></tr>";

	$chk = "";
	if ($eihinnastoon != "") $chk = "checked";
	echo "<tr><th>".t("�l� n�yt� tuotteita joita ei n�ytet� hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

	$chk = "";
	if ($ehdotettavat != "") $chk = "checked";
	echo "<tr><th>".t("N�yt� vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='ehdotettavat' $chk></td></tr>";


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
			<td><input type='text' name='vvl1' value='$vvl1' size='5'></td><td class='back'> <-- ".t("k�ytet��n h�lytysrajaehdotuksen laskentaan")."</td>
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