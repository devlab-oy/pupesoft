<?php

if (trim($argv[1]) != '') {

	if ($argc == 0) die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");

	// otetaan tietokanta connect
	require ("inc/connect.inc");
	require ("inc/functions.inc");

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
	require ("inc/parametrit.inc");
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
		$abcchar = "AK";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "AM";
	}

	//siivotaan ensin aputaulu tyhj‰ksi
	$query = "	DELETE from abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$res = mysql_query($query) or pupe_error($query);

	///* sitten l‰het‰‰n rakentamaan uutta aputaulua *///
	//haetaan ensin koko kauden yhteisnmyynti ja ostot
	$query = "	SELECT
				sum(if(tilausrivi.tyyppi='L', 1, 0))					yhtrivia,
				sum(if(tilausrivi.tyyppi='L', tilausrivi.rivihinta, 0))	yhtmyynti,
				sum(if(tilausrivi.tyyppi='L', tilausrivi.kate, 0)) 		yhtkate
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi = 'L'
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'";
	$res = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($res);

	//kokokauden kokonaismyynti
	$kaudenmyynyht 		= $row["yhtmyynti"];
	$kaudenkateyht 		= $row["yhtkate"];
	$kaudenmyyriviyht 	= $row["yhtrivia"];

	// t‰ss‰ on kayttajan syottamat kustannukset per vuosi, jyvitet‰‰n ne per p‰iv‰ ja sit katotaan p‰iv‰m‰‰r‰v‰liin kuuluvien p‰ivien lukum‰‰r‰‰
	$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$paivat = abs($row["ero"]);

	$myynninkustapaiva = $myynninkustavuosi / 365;
	$myynninkustayht = $myynninkustapaiva * $ero;
	$kustapermyyrivi = $myynninkustayht / $kaudenmyyriviyht;

	// rakennetaan perus ABC-luokat
	$query = "	SELECT
				lasku.liitostunnus,
				ifnull(asiakas.osasto,'#') osasto,
				ifnull(asiakas.ryhma,'#') ryhma,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='H' or tilausrivi.var=''), 1, 0))						rivia,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='H' or tilausrivi.var=''), tilausrivi.kpl, 0))			kpl,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='H' or tilausrivi.var=''), tilausrivi.rivihinta, 0))	summa,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='H' or tilausrivi.var=''), tilausrivi.kate, 0))		kate,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='P'), tilausrivi.tilkpl, 0))							puutekpl,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='P'), 1, 0))											puuterivia
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				JOIN lasku use index (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
				LEFT JOIN asiakas use index (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi = 'L'
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				GROUP BY 1,2,3
				HAVING kpl > 0
	   			ORDER BY $abcwhat desc";
	$res = mysql_query($query) or pupe_error($query);

	$i					= 0;
	$ryhmaprossa		= 0;

	$ryhmanimet   = array('A-50','B-30','C-20');
	$ryhmaprossat = array(50.00,30.00,20.00);

	while ($row = mysql_fetch_array($res)) {

		if ($abctyyppi == "kate") {
			//tuotteen suhteellinen kate totaalikatteesta
			if ($kaudenkateyht != 0) $tuoteprossa = ($row["kate"] / $kaudenkateyht) * 100;
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

		if ($row["rivia"] != 0)	$myyntieranarvo = round($row["summa"] / $row["rivia"],2);
		else $myyntieranarvo = 0;

		if ($row["rivia"] != 0)	$myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
		else $myyntieranakpl = 0;

		if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
		else $palvelutaso = 0;

		//rivin kustannus
		$kustamyy = round($kustapermyyrivi * $row["rivia"],2);
		$kustayht = $kustamyy;

		$query = "	INSERT INTO abc_aputaulu
					SET yhtio			= '$kukarow[yhtio]',
					tyyppi				= '$abcchar',
					luokka				= '$i',
					osto_rivia			= '$row[myyja]',
					tuoteno				= '$row[liitostunnus]',
					osasto				= '$row[osasto]',
					try					= '$row[ryhma]',
					summa				= '$row[summa]',
					kate				= '$row[kate]',
					katepros			= '$kateprosentti',
					myyntierankpl 		= '$myyntieranakpl',
					myyntieranarvo 		= '$myyntieranarvo',
					rivia				= '$row[rivia]',
					kpl					= '$row[kpl]',
					puutekpl			= '$row[puutekpl]',
					puuterivia			= '$row[puuterivia]',
					palvelutaso 		= '$palvelutaso',
					kustannus_yht		= '$kustayht'";
		$insres = mysql_query($query) or pupe_error($query);

		//luokka vaihtuu
		if ($ryhmaprossa >= $ryhmaprossat[$i]) {
			$ryhmaprossa = 0;
			$i++;

			if ($i == 3) {
				$i = 2;
			}
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
	$query = "SELECT distinct try FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
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
					tunnus
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto = '$a'
					ORDER BY $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {

			//haetaan luokan myynti yhteens‰
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kate) yhtkate
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and osasto = '$a'";
			$resi 	= mysql_query($query) or pupe_error($query);
			$yhtrow = mysql_fetch_array($resi);

			$i			 = 0;
			$ryhmaprossa = 0;

			$ryhmanimet   = array('A-50','B-30','C-20');
			$ryhmaprossat = array(50.00,30.00,20.00);

			while($row = mysql_fetch_array($res)) {

				if ($abctyyppi == "kate") {
					//tuotteen suhteellinen kate totaalikatteesta
					if ($kaudenkateyht != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
					else $tuoteprossa = 0;
				}
				else {
					//tuotteen suhteellinen myynti totaalimyynnist‰
					if ($kaudenmyynyht != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
					else $tuoteprossa = 0;
				}

				//muodostetaan ABC-luokka rym‰prossan mukaan
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

					if ($i == 3) {
						$i = 2;
					}
				}
			}
		}
	}


	//rakennetaan ryhm‰kohtainen luokka
	foreach ($aputryt as $a) {
		$query = "	SELECT tunnus
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and try	   = '$a'";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {
			//rakennetaan aliluokat
			$query = "	SELECT
						summa,
						kate,
						tunnus
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and try		= '$a'
						ORDER BY $abcwhat desc";
			$res = mysql_query($query) or pupe_error($query);


			//haetaan luokan myynti yhteens‰
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kate) yhtkate
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and try		= '$a'";
			$resi 	= mysql_query($query) or pupe_error($query);
			$yhtrow = mysql_fetch_array($resi);

			$i			 = 0;
			$ryhmaprossa = 0;

			$ryhmanimet   = array('A-50','B-30','C-20');
			$ryhmaprossat = array(50.00,30.00,20.00);

			while ($row = mysql_fetch_array($res)) {

				if ($abctyyppi == "kate") {
					//tuotteen suhteellinen kate totaalikatteesta
					if ($kaudenkateyht != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
					else $tuoteprossa = 0;
				}
				else {
					//tuotteen suhteellinen myynti totaalimyynnist‰
					if ($kaudenmyynyht != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
					else $tuoteprossa = 0;
				}

				//muodostetaan ABC-luokka rym‰prossan mukaan
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

					if ($i == 3) {
						$i = 2;
					}
				}
			}
		}
	}

	//rakennetaan tuoteryhm‰grouppikohtainen abc-ryhm‰
	$query = "	SELECT
				osasto,
				try,
				sum(summa) summa,
				sum(kate) kate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				GROUP BY osasto, try
				ORDER BY summa desc";
	$res = mysql_query($query) or pupe_error($query);

	//haetaan myynti yhteens‰
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kate) yhtkate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$resi 	= mysql_query($query) or pupe_error($query);
	$yhtrow = mysql_fetch_array($resi);

	$i			 = 0;
	$ryhmaprossa = 0;

	$ryhmanimet   = array('A-50','B-30','C-20');
	$ryhmaprossat = array(50.00,30.00,20.00);

	while ($row = mysql_fetch_array($res)) {

		if ($abctyyppi == "kate") {
			//tuotteen suhteellinen kate totaalikatteesta
			if ($kaudenkateyht != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
			else $tuoteprossa = 0;
		}
		else {
			//tuotteen suhteellinen myynti totaalimyynnist‰
			if ($kaudenmyynyht != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
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

			if ($i == 3) {
				$i = 2;
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
	echo "<th colspan='4'>".t("Kustannukset per vuosi").":</th>";

	echo "<tr><th colspan='3'>".t("Myynnin kustannukset").":</th>
			<td><input type='text' name='myynninkustayht' value='$myynninkustavuosi' size='10'></td></tr>";

	echo "</table>";
	echo "<br><input type='submit' value='".t("Rakenna")."'>";
	echo "</form><br><br><br>";

}

if (trim($argv[1]) == '') {
	require ("inc/footer.inc");
}

?>