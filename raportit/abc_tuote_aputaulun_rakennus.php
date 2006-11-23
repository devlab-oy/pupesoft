<?php

if (trim($argv[1]) != '') {

	if ($argc == 0) die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	$kukarow['yhtio'] = trim($argv[1]);

	if (trim($argv[2]) != "") {
		$abctyyppi = trim($argv[2]);
	}

	$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($yhtiores) == 1) {
		$yhtiorow = mysql_fetch_array($yhtiores);
	}
	else {
		die ("Yhtiˆ $kukarow[yhtio] ei lˆydy!");
	}

	$tee = "YHTEENVETO";
}
else {
	require ("../inc/parametrit.inc");
	echo "<font class='head'>".t("ABC-Aputaulun rakennus")."<hr></font>";
}

if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));

if (!isset($kkl)) $kkl = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vvl)) $vvl = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppl)) $ppl = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($abctyyppi)) $abctyyppi = "kate";

// rakennetaan tiedot
if ($tee == 'YHTEENVETO') {

	if ($abctyyppi == "kate") {
		$abcwhat = "kate";
		$abcchar = "TK";
	}
	elseif ($abctyyppi == "kpl") {
		$abcwhat = "kpl";
		$abcchar = "TP";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "TM";
	}

	//siivotaan ensin aputaulu tyhj‰ksi
	$query = "	DELETE from abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$res = mysql_query($query) or pupe_error($query);

	///* sitten l‰het‰‰n rakentamaan uutta aputaulua *///

	//haetaan ensin koko kauden yhteisnmyynti ja ostot
	$query = "	SELECT
				sum(if(tyyppi='O', 1, 0))			yhtriviaosto,
				sum(if(tyyppi='L', 1, 0))			yhtrivia,
				sum(if(tyyppi='L', rivihinta, 0))	yhtmyynti,
				sum(if(tyyppi='L', kate, 0)) 		yhtkate
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi in ('L','O')
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'";
	$res = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($res);

	//kokokauden kokonaismyynti
	$kaudenmyynyht 		= $row["yhtmyynti"];
	$kaudenkateyht 		= $row["yhtkate"];
	$kaudenmyyriviyht 	= $row["yhtrivia"];
	$kaudenostriviyht 	= $row["yhtriviaosto"];

	if ($kustannuksetyht == "") {
		// etsit‰‰n kirjanpidosta mitk‰ on meid‰n kulut samalta ajanjaksolta
		$query  = "select sum(summa) summa
					from tiliointi use index (tapvm_index)
					join tili use index (tili_index) on (tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino and sisainen_taso like '34%')
					where tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.tapvm >= '$vva-$kka-$ppa' and
					tiliointi.tapvm <= '$vvl-$kkl-$ppl' and
					tiliointi.korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);
		$kprow  = mysql_fetch_array($result);
		$kustannuksetyht = $kprow["summa"];
	}

	// paljonko on rivej‰ kaikenkaikkiaan
	$rivityht = $kaudenmyyriviyht + $kaudenostriviyht;

	if ($rivityht != 0) {
		// lasketaan myynti- ja ostorivien osuus kokonaisriveist‰
		$myynti_osuus = $kaudenmyyriviyht / $rivityht;
		$osto_osuus   = $kaudenostriviyht / $rivityht;
	}
	else {
		$myynti_osuus = 0;
		$osto_osuus   = 0;
	}

	// lasketaan myynnin ja oston kustannusten osuus kokonaiskustannuksista
	$myynninkustayht = $kustannuksetyht * $myynti_osuus;
	$ostojenkustayht = $kustannuksetyht * $osto_osuus;

	// sitten lasketaan viel‰ yhden myyntirivin kulu
	if ($kaudenmyyriviyht != 0) {
		$kustapermyyrivi = $myynninkustayht / $kaudenmyyriviyht;
	}
	else {
		$kustapermyyrivi = 0;
	}

	// ja lasketaan yhden ostorivin kulu
	if ($kaudenostriviyht != 0) {
		$kustaperostrivi = $ostojenkustayht / $kaudenostriviyht;
	}
	else {
		$kustaperostrivi = 0;
	}

	// rakennetaan tuotekohtaiset ABC-luokat
	$query = "	SELECT
				tilausrivi.tuoteno,
				ifnull(tuote.try,'#') try,
				ifnull(tuote.osasto,'#') osasto,
				sum(if(tyyppi='L' and (var='H' or var=''), 1, 0))			rivia,
				sum(if(tyyppi='L' and (var='H' or var=''), kpl, 0))			kpl,
				sum(if(tyyppi='L' and (var='H' or var=''), rivihinta, 0))	summa,
				sum(if(tyyppi='L' and (var='H' or var=''), kate, 0))		kate,
				sum(if(tyyppi='L' and (var='P'), tilkpl, 0))				puutekpl,
				sum(if(tyyppi='L' and (var='P'), 1, 0))						puuterivia,
				sum(if(tyyppi='O', 1, 0))									osto_rivia,
				sum(if(tyyppi='O', kpl, 0))									osto_kpl,
				sum(if(tyyppi='O', rivihinta, 0))							osto_summa
				FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
				LEFT JOIN tuote USE INDEX (tuoteno_index) USING (yhtio, tuoteno)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi in ('L','O')
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				GROUP BY 1,2,3
				HAVING kpl > 0
	   			ORDER BY $abcwhat desc";
	$res = mysql_query($query) or pupe_error($query);

	$i					= 0;
	$ryhmaprossa		= 0;

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00);

	while ($row = mysql_fetch_array($res)) {

		//varastonarvot
		$query  = "	select round(sum(saldo)*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2),2) vararvo
					from tuotepaikat use index (tuote_index), tuote use index (tuoteno_index)
					where tuotepaikat.yhtio		= '$kukarow[yhtio]'
					and tuotepaikat.tuoteno		= '$row[tuoteno]'
					and tuote.yhtio				= tuotepaikat.yhtio
					and tuote.tuoteno			= tuotepaikat.tuoteno
					and tuote.ei_saldoa			= ''
					and tuote.epakurantti2pvm	= '0000-00-00'";
		$tuores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tuores) > 0) {
			//tuotteen ja tuotepaikan kaikki tiedot
			$tuorow = mysql_fetch_array($tuores);

			if ($abctyyppi == "kate") {
				//tuotteen suhteellinen kate totaalikatteesta
				if ($kaudenkateyht != 0) $tuoteprossa = ($row["kate"] / $kaudenkateyht) * 100;
				else $tuoteprossa = 0;
			}
			elseif ($abctyyppi == "kpl") {
				//tuotteen suhteellinen kpl totaalikappaleista
				if ($kaudenmyyriviyht != 0) $tuoteprossa = ($row["kpl"] / $kaudenmyyriviyht) * 100;
				else $tuoteprossa = 0;
			}
			else {
				//tuotteen suhteellinen myynti totaalimyynnist‰
				if ($kaudenmyynyht != 0) $tuoteprossa = ($row["summa"] / $kaudenmyynyht) * 100;
				else $tuoteprossa = 0;
			}

			//muodostetaan ABC-luokka rym‰prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
			else $kateprosentti = 0;

			if ($tuorow["vararvo"] != 0) $kiertonopeus  = round(($row["summa"] - $row["kate"]) / $tuorow["vararvo"],2);
			else $kiertonopeus = 0;

			if ($row["rivia"] != 0) $myyntieranarvo = round($row["summa"] / $row["rivia"],2);
			else $myyntieranarvo = 0;

			if ($row["rivia"] != 0) $myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
			else $myyntieranakpl = 0;

			if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
			else $palvelutaso = 0;

			if ($row["osto_rivia"] != 0) $ostoeranarvo = round ($row["osto_summa"] / $row["osto_rivia"],2);
			else $ostoeranarvo = 0;

			if ($row["osto_rivia"] != 0) $ostoeranakpl = round ($row["osto_kpl"] / $row["osto_rivia"],2);
			else $ostoeranakpl = 0;

			//rivin kustannus
			$kustamyy = round($kustapermyyrivi * $row["rivia"],2);
			$kustaost = round($kustaperostrivi * $row["osto_rivia"],2);
			$kustayht = $kustamyy + $kustaost;

			$query = "	INSERT INTO abc_aputaulu
						SET yhtio			= '$kukarow[yhtio]',
						tyyppi				= '$abcchar',
						luokka				= '$i',
						tuoteno				= '$row[tuoteno]',
						osasto				= '$row[osasto]',
						try					= '$row[try]',
						summa				= '$row[summa]',
						kate				= '$row[kate]',
						katepros			= '$kateprosentti',
						vararvo				= '$tuorow[vararvo]',
						varaston_kiertonop 	= '$kiertonopeus',
						myyntierankpl 		= '$myyntieranakpl',
						myyntieranarvo 		= '$myyntieranarvo',
						rivia				= '$row[rivia]',
						kpl					= '$row[kpl]',
						puutekpl			= '$row[puutekpl]',
						puuterivia			= '$row[puuterivia]',
						palvelutaso 		= '$palvelutaso',
						osto_rivia			= '$row[osto_rivia]',
						osto_kpl			= '$row[osto_kpl]',
						ostoerankpl 		= '$ostoeranakpl',
						ostoeranarvo 		= '$ostoeranarvo',
						osto_summa			= '$row[osto_summa]',
						kustannus			= '$kustamyy',
						kustannus_osto		= '$kustaost',
						kustannus_yht		= '$kustayht'";
			$insres = mysql_query($query) or pupe_error($query);

			//luokka vaihtuu
			if ($ryhmaprossa >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				if ($i == 8) {
					$i = 7;
				}
			}
		}
	}

	// luodaan I-ryhm‰, eli tuotteet joilla ei ole ollut myynti‰/katetta kaudella, mutta joilla on varastonarvoa

	// etsit‰‰n kaikki tuotteet joilla on varastonarvoa
	$query  = "	SELECT tuote.tuoteno, tuote.osasto, tuote.try, ifnull(round(sum(saldo)*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2),2),0) vararvo
				FROM tuotepaikat use index (tuote_index), tuote use index (tuoteno_index)
				WHERE tuote.yhtio			= '$kukarow[yhtio]'
				and tuote.ei_saldoa			= ''
				and tuote.epakurantti2pvm	= '0000-00-00'
				and tuotepaikat.tuoteno		= tuote.tuoteno
				and tuotepaikat.yhtio		= tuote.yhtio
				group by 1,2,3
				HAVING vararvo > 0";
	$result = mysql_query($query) or pupe_error($query);

	while ($tuoterow = mysql_fetch_array($result)) {

		// tutkitaan onko tuotetta myyty
		$query = "	SELECT
					tilausrivi.tuoteno,
					ifnull(tuote.try,'#') try,
					ifnull(tuote.osasto,'#') osasto,
					sum(if(tyyppi='L' and (var='H' or var=''), 1, 0))			rivia,
					sum(if(tyyppi='L' and (var='H' or var=''), kpl, 0))			kpl,
					sum(if(tyyppi='L' and (var='H' or var=''), rivihinta, 0))	summa,
					sum(if(tyyppi='L' and (var='H' or var=''), kate, 0))		kate,
					sum(if(tyyppi='L' and (var='P'), tilkpl, 0))				puutekpl,
					sum(if(tyyppi='L' and (var='P'), 1, 0))						puuterivia,
					sum(if(tyyppi='O', 1, 0))									osto_rivia,
					sum(if(tyyppi='O', kpl, 0))									osto_kpl,
					sum(if(tyyppi='O', rivihinta, 0))							osto_summa
					FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
					LEFT JOIN tuote USE INDEX (tuoteno_index) USING (yhtio, tuoteno)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi in ('L','O')
					and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
					and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					and tilausrivi.tuoteno = '$tuoterow[tuoteno]'
					GROUP BY 1,2,3
					HAVING kpl is null or kpl <= 0";
		$res = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($res)) {

			if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
			else $kateprosentti = 0;

			if ($tuoterow["vararvo"] != 0) $kiertonopeus  = round(($row["summa"] - $row["kate"]) / $tuoterow["vararvo"],2);
			else $kiertonopeus = 0;

			if ($row["rivia"] != 0) $myyntieranarvo = round($row["summa"] / $row["rivia"],2);
			else $myyntieranarvo = 0;

			if ($row["rivia"] != 0)	$myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
			else $myyntieranakpl = 0;

			if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
			else $palvelutaso = 0;

			if ($row["osto_rivia"] != 0) $ostoeranarvo = round ($row["osto_summa"] / $row["osto_rivia"],2);
			else $ostoeranarvo = 0;

			if ($row["osto_rivia"] != 0) $ostoeranakpl = round ($row["osto_kpl"] / $row["osto_rivia"],2);
			else $ostoeranakpl = 0;

			//rivin kustannus
			$kustamyy = round($kustapermyyrivi * $row["rivia"],2);
			$kustaost = round($kustaperostrivi * $row["osto_rivia"],2);
			$kustayht = $kustamyy + $kustaost;

			$query = "	INSERT INTO abc_aputaulu
						SET yhtio			= '$kukarow[yhtio]',
						tyyppi				= '$abcchar',
						luokka				= '8',
						tuoteno				= '$row[tuoteno]',
						osasto				= '$row[osasto]',
						try					= '$row[try]',
						summa				= '$row[summa]',
						kate				= '$row[kate]',
						katepros			= '$kateprosentti',
						vararvo				= '$tuoterow[vararvo]',
						varaston_kiertonop 	= '$kiertonopeus',
						myyntierankpl 		= '$myyntieranakpl',
						myyntieranarvo 		= '$myyntieranarvo',
						rivia				= '$row[rivia]',
						kpl					= '$row[kpl]',
						puutekpl			= '$row[puutekpl]',
						puuterivia			= '$row[puuterivia]',
						palvelutaso 		= '$palvelutaso',
						osto_rivia			= '$row[osto_rivia]',
						osto_kpl			= '$row[osto_kpl]',
						ostoerankpl 		= '$ostoeranakpl',
						ostoeranarvo 		= '$ostoeranarvo',
						osto_summa			= '$row[osto_summa]',
						kustannus			= '$kustamyy',
						kustannus_osto		= '$kustaost',
						kustannus_yht		= '$kustayht'";
			$insres = mysql_query($query) or pupe_error($query);
		}
	}

	// haetaan kaikki osastot
	$query = "SELECT distinct osasto FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by osasto";
	$res = mysql_query($query) or pupe_error($query);

	$apuosastot = array();
	while ($apurivi = mysql_fetch_array($res)) {
		$apuosastot[] = $apurivi["osasto"];
	}

	// haetaan kaikki tryt
	$query = "SELECT distinct try FROM abc_aputaulu use index (yhtio_tyyppi_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by try";
	$res = mysql_query($query) or pupe_error($query);

	$aputryt = array();
	while ($apurivi = mysql_fetch_array($res)) {
		$aputryt[] = $apurivi["try"];
	}

	//rakennetaan osastokohtainen luokka
	foreach ($apuosastot as $a) {

		//rakennetaan aliluokat
		$query = "	SELECT
					summa,
					kate,
					kpl,
					tunnus
					FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto = '$a'
					ORDER BY $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {

			//haetaan luokan myynti yhteens‰
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kpl) yhtkpl,
						sum(kate) yhtkate
						FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and osasto = '$a'";
			$resi 	= mysql_query($query) or pupe_error($query);
			$yhtrow = mysql_fetch_array($resi);

			$i			 = 0;
			$ryhmaprossa = 0;

			$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
			$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

			while ($row = mysql_fetch_array($res)) {

				if ($abctyyppi == "kate") {
					//tuotteen suhteellinen kate totaalikatteesta
					if ($yhtrow["yhtkate"] != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
					else $tuoteprossa = 0;
				}
				elseif ($abctyyppi == "kpl") {
					//tuotteen suhteellinen kpl totaalikappaleista
					if ($yhtrow["yhtkpl"] != 0) $tuoteprossa = ($row["kpl"] / $yhtrow["yhtkpl"]) * 100;
					else $tuoteprossa = 0;
				}
				else {
					//tuotteen suhteellinen myynti totaalimyynnist‰
					if ($yhtrow["yhtmyynti"] != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
					else $tuoteprossa = 0;
				}

				//muodostetaan ABC-luokka ryhm‰prossan mukaan
				$ryhmaprossa += $tuoteprossa;

				$query = "	UPDATE abc_aputaulu
							SET luokka_osasto = '$i'
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = '$abcchar'
							and tunnus  = '$row[tunnus]'";
				$insres = mysql_query($query) or pupe_error($query);

				//luokka vaihtuu
				if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
					$ryhmaprossa = 0;
					$i++;

					if ($i == 8) {
						$i = 7;
					}
				}
			}
		}
	}

	//rakennetaan tuoteryhm‰kohtainen luokka
	foreach ($aputryt as $b) {

		//rakennetaan aliluokat
		$query = "	SELECT
					summa,
					kate,
					kpl,
					tunnus
					FROM abc_aputaulu use index (yhtio_tyyppi_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and try = '$b'
					ORDER BY $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {

			//haetaan luokan myynti yhteens‰
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kpl) yktkpl,
						sum(kate) yhtkate
						FROM abc_aputaulu use index (yhtio_tyyppi_try)
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and try = '$b'";
			$resi 	= mysql_query($query) or pupe_error($query);
			$yhtrow = mysql_fetch_array($resi);

			$i			 = 0;
			$ryhmaprossa = 0;

			$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
			$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

			while ($row = mysql_fetch_array($res)) {

				if ($abctyyppi == "kate") {
					//tuotteen suhteellinen kate totaalikatteesta
					if ($yhtrow["yhtkate"] != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
					else $tuoteprossa = 0;
				}
				elseif ($abctyyppi == "kpl") {
					//tuotteen suhteellinen kpl totaalikappaleista
					if ($yhtrow["yhtkpl"] != 0) $tuoteprossa = ($row["kpl"] / $yhtrow["yhtkpl"]) * 100;
					else $tuoteprossa = 0;
				}
				else {
					//tuotteen suhteellinen myynti totaalimyynnist‰
					if ($yhtrow["yhtmyynti"] != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
					else $tuoteprossa = 0;
				}

				//muodostetaan ABC-luokka ryhm‰prossan mukaan
				$ryhmaprossa += $tuoteprossa;

				$query = "	UPDATE abc_aputaulu
							SET luokka_try = '$i'
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = '$abcchar'
							and tunnus  = '$row[tunnus]'";
				$insres = mysql_query($query) or pupe_error($query);

				//luokka vaihtuu
				if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
					$ryhmaprossa = 0;
					$i++;

					if ($i == 8) {
						$i = 7;
					}
				}
			}
		}
	}

	// rakennetaan osasto/tuoteryhm‰ groupin mukaan combo abc-ryhm‰
	$query = "	SELECT
				osasto,
				try,
				sum(summa) summa,
				sum(kpl) kpl,
				sum(kate) kate
				FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				GROUP BY osasto, try
				ORDER BY $abcwhat desc";
	$res = mysql_query($query) or pupe_error($query);

	//haetaan myynti yhteens‰
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kpl) yhtkpl,
				sum(kate) yhtkate
				FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$resi 	= mysql_query($query) or pupe_error($query);
	$yhtrow = mysql_fetch_array($resi);

	$i			 = 0;
	$ryhmaprossa = 0;

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	while ($row = mysql_fetch_array($res)) {

		if ($abctyyppi == "kate") {
			//tuotteen suhteellinen kate totaalikatteesta
			if ($yhtrow["yhtkate"] != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
			else $tuoteprossa = 0;
		}
		elseif ($abctyyppi == "kpl") {
			//tuotteen suhteellinen kpl totaalikappaleista
			if ($yhtrow["yhtkpl"] != 0) $tuoteprossa = ($row["kpl"] / $yhtrow["yhtkpl"]) * 100;
			else $tuoteprossa = 0;
		}
		else {
			//tuotteen suhteellinen myynti totaalimyynnist‰
			if ($yhtrow["yhtmyynti"] != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
			else $tuoteprossa = 0;
		}

		//muodostetaan ABC-luokka rym‰prossan mukaan
		$ryhmaprossa += $tuoteprossa;

		$query = "	UPDATE abc_aputaulu
					SET luokka_trygroup = '$i'
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto  = '$row[osasto]'
					and try 	= '$row[try]'";
		$insres = mysql_query($query) or pupe_error($query);

		//luokka vaihtuu
		if ($ryhmaprossa >= $ryhmaprossat[$i]) {
			$ryhmaprossa = 0;
			$i++;

			if ($i == 8) {
				$i = 7;
			}
		}
	}

	$query = "OPTIMIZE table abc_aputaulu";
	$optir = mysql_query($query) or pupe_error($query);
}

if ($tee == "") {

	// piirrell‰‰n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<table>";

	echo "<th colspan='4'>".t("Valitse kausi").":</th>";

	echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>ABC-luokkien laskentatapa</th>";
	echo "<td colspan='3'><select name='abctyyppi'>";
	echo "<option value='kate'>Katteen mukaan</option>";
	echo "<option value='myynti'>Myynnin mukaan</option>";
	echo "</select></td></tr>";


	echo "<tr><td colspan='4' class='back'><br></td></tr>";
	echo "<tr><th colspan='1'>".t("Kustannukset valitulla kaudella").":</th>
			<td colspan='3'><input type='text' name='kustannuksetyht' value='$kustannuksetyht' size='15'></td></tr>";

	echo "</table>";
	echo "<br><input type='submit' value='".t("Rakenna")."'>";
	echo "</form><br><br><br>";

}

if (trim($argv[1]) == '') {
	require ("../inc/footer.inc");
}

?>