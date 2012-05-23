<?php

// Kutsutaanko CLI:st�
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
	$php_cli = TRUE;
}

if ($php_cli) {

	if (!isset($argv[1]) or $argv[1] == '') {
		echo "Anna yhti�!!!\n";
		die;
	}

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	$kukarow['yhtio'] = trim($argv[1]);

	$abctyyppi 			= "";
	$saldottomatmukaan 	= "";
	$kustannuksetyht 	= "";

	if (isset($argv[2]) and trim($argv[2]) != "") {
		$abctyyppi = trim($argv[2]);
	}

	if (isset($argv[3]) and trim($argv[3]) != "") {
		$saldottomatmukaan = trim($argv[3]);
	}

	$query    = "SELECT * FROM yhtio WHERE yhtio='$kukarow[yhtio]'";
	$yhtiores = pupe_query($query);

	if (mysql_num_rows($yhtiores) == 1) {
		$yhtiorow = mysql_fetch_array($yhtiores);
	}
	else {
		die ("Yhti� $kukarow[yhtio] ei l�ydy!");
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
		$abcchar = "AK";
	}
	elseif ($abctyyppi == "kpl") {
		$abcwhat = "kpl";
		$abcchar = "AP";
	}
	elseif ($abctyyppi == "rivia") {
		$abcwhat = "rivia";
		$abcchar = "AR";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "AM";
	}

	// Haetaan abc-parametrit
	list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcchar);

	//siivotaan ensin aputaulu tyhj�ksi
	$query = "	DELETE from abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$res = pupe_query($query);

	///* sitten l�het��n rakentamaan uutta aputaulua *///

	// katotaan halutaanko saldottomia mukaan.. default on ettei haluta
	if ($saldottomatmukaan == "") {
		$tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '') ";
	}
	else {
		$tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno) ";
	}

	// otetaan isot queryt slavelta
	$useslave = 1;
	require ("../inc/connect.inc");

	//haetaan ensin koko kauden yhteisnmyynti ja ostot
	$query = "	SELECT
				lasku.liitostunnus,
				sum(if(tyyppi='L', 1, 0))						rivia,
				sum(if(tyyppi='L', tilausrivi.kpl, 0))			kpl,
				sum(if(tyyppi='L', tilausrivi.rivihinta, 0))	summa,
				sum(if(tyyppi='L', tilausrivi.kate, 0)) 		kate
				FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
				$tuotejoin
				JOIN lasku USE INDEX (primary) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi = 'L'
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				GROUP BY liitostunnus";
	$res = pupe_query($query);

	//kokokauden kokonaismyynti
	$kaudenmyyriviyht 	= 0;
	$kausiyhteensa = 0;

	// joudutaan summaamaan loopissa, koska kokonaismyyntiin ei saa vaikuttaa tuotteet joiden kauden myynti/kate/kappaleet on alle nolla
	while ($row = mysql_fetch_array($res)) {

		// onko enemm�n ku nolla
		if ($row["${abcwhat}"] > 0) {
			$kausiyhteensa += $row["${abcwhat}"];
		}

		$kaudenmyyriviyht += $row["rivia"];
	}

 	// t�� on nyt hardcoodattu, eli milt� kirjanpidon tasolta otetaan kulut
	$sisainen_taso = "34";

	if ($kustannuksetyht == "" and $sisainen_taso != "") {
		// etsit��n kirjanpidosta mitk� on meid�n kulut samalta ajanjaksolta
		$query  = "	SELECT sum(summa) summa
					FROM tiliointi use index (yhtio_tapvm_tilino)
					join tili use index (tili_index) on (tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino and sisainen_taso like '$sisainen_taso%')
					where tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.tapvm >= '$vva-$kka-$ppa' and
					tiliointi.tapvm <= '$vvl-$kkl-$ppl' and
					tiliointi.korjattu = ''";
		$result = pupe_query($query);
		$kprow  = mysql_fetch_array($result);

		$kustannuksetyht = $kprow["summa"];
	}

	// sitten lasketaan yhden myyntirivin kulu
	if ($kaudenmyyriviyht != 0) {
		$kustapermyyrivi = $kustannuksetyht / $kaudenmyyriviyht;
	}
	else {
		$kustapermyyrivi = 0;
	}

	// rakennetaan perus ABC-luokat
	$query = "	SELECT
				lasku.liitostunnus,
				ifnull(asiakas.osasto,'#') osasto,
				ifnull(asiakas.ryhma,'#') ryhma,
				ifnull(asiakas.myyjanro,'#') myyjanro,
				sum(if(tilausrivi.tyyppi='L' and tilausrivi.var in ('H',''), 1, 0))						rivia,
				sum(if(tilausrivi.tyyppi='L' and tilausrivi.var in ('H',''), tilausrivi.kpl, 0))		kpl,
				sum(if(tilausrivi.tyyppi='L' and tilausrivi.var in ('H',''), tilausrivi.rivihinta, 0))	summa,
				sum(if(tilausrivi.tyyppi='L' and tilausrivi.var in ('H',''), tilausrivi.kate, 0))		kate,
				sum(if(tilausrivi.tyyppi='L' and tilausrivi.var = 'P', tilausrivi.tilkpl, 0))			puutekpl,
				sum(if(tilausrivi.tyyppi='L' and tilausrivi.var = 'P', 1, 0))							puuterivia
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
				$tuotejoin
				LEFT JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and ((tilausrivi.tyyppi = 'L' and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl')
				or (tilausrivi.tyyppi = 'L' and tilausrivi.var = 'P' and tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00' and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59'))
				GROUP BY 1,2,3,4
	   			ORDER BY $abcwhat desc";
	$res = pupe_query($query);

	$i					= 0;
	$ryhmaprossa		= 0;

	// otetaan takasin master yhteys
	$useslave = 0;
	require ("../inc/connect.inc");

	while ($row = mysql_fetch_array($res)) {

		// katotaan onko kelvollinen tuote, elikk� luokitteluperuste pit�� olla > 0
		if ($row["${abcwhat}"] > 0) {

			// laitetaan oikeeseen luokkaan
			$luokka = $i;

			// tuotteen osuus yhteissummasta
			if ($kausiyhteensa != 0) $tuoteprossa = ($row["${abcwhat}"] / $kausiyhteensa) * 100;
			else $tuoteprossa = 0;

			//muodostetaan ABC-luokka ryhm�prossan mukaan
			$ryhmaprossa += $tuoteprossa;
		}
		else {
			// ei ole kelvollinen tuote laitetaan I-luokkaan
			$luokka = 3;
		}

		if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
		else $kateprosentti = 0;

		if ($row["rivia"] != 0)	$myyntieranarvo = round($row["summa"] / $row["rivia"],2);
		else $myyntieranarvo = 0;

		if ($row["rivia"] != 0)	$myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
		else $myyntieranakpl = 0;

		if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
		else $palvelutaso = 0;

		//rivin kustannus
		$kustamyy = round($kustapermyyrivi * $row["rivia"], 2);
		$kustayht = $kustamyy;

		$query = "	INSERT INTO abc_aputaulu
					SET yhtio			= '$kukarow[yhtio]',
					tyyppi				= '$abcchar',
					luokka				= '$i',
					osto_rivia			= '$row[myyjanro]',
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
		$insres = pupe_query($query);

		//luokka vaihtuu
		if ($ryhmaprossa >= $ryhmaprossat[$i]) {
			$ryhmaprossa = 0;
			$i++;

			// ei menn� ikin� I-luokkaan asti
			if ($i == 3) {
				$i = 2;
			}
		}
	}

	// p�ivitet��n ensiks kaikki osastot ja tuoteryhm�t I-luokkaan ja k�yd��n sitten p�ivitt�m�ss� niit� oikeisiin luokkiin
	$query = "	UPDATE abc_aputaulu SET
				luokka_osasto = '3',
				luokka_try = '3'
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$ires = pupe_query($query);

	// haetaan kaikki osastot
	$query = "	SELECT distinct osasto FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by osasto";
	$kaikres = pupe_query($query);

	// tehd��n osastokohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens�
		$query = "	SELECT
					sum(rivia) rivia,
					sum(summa) summa,
					sum(kpl)   kpl,
					sum(kate)  kate
					FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto = '$arow[osasto]'
					and $abcwhat > 0";
		$resi 	= pupe_query($query);
		$yhtrow = mysql_fetch_array($resi);

		//rakennetaan aliluokat
		$query = "	SELECT
					rivia,
					summa,
					kate,
					kpl,
					tunnus
					FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto = '$arow[osasto]'
					and $abcwhat > 0
					ORDER BY $abcwhat desc";
		$res = pupe_query($query);

		$i			 = 0;
		$ryhmaprossa = 0;

		while ($row = mysql_fetch_array($res)) {

			// asiakkaan osuus yhteissummasta
			if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
			else $tuoteprossa = 0;

			// muodostetaan ABC-luokka ryhm�prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_osasto = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = pupe_query($query);

			// luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				if ($i == 3) {
					$i = 2;
				}
			}
		}

	}

	// haetaan kaikki tryt
	$query = "SELECT distinct try FROM abc_aputaulu use index (yhtio_tyyppi_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by try";
	$kaikres = pupe_query($query);

	// tehd��n try kohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens�
		$query = "	SELECT
					sum(rivia) rivia,
					sum(summa) summa,
					sum(kpl) kpl,
					sum(kate) kate
					FROM abc_aputaulu use index (yhtio_tyyppi_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and try = '$arow[try]'
					and $abcwhat > 0";
		$resi 	= pupe_query($query);
		$yhtrow = mysql_fetch_array($resi);

		//rakennetaan aliluokat
		$query = "	SELECT
					rivia,
					summa,
					kate,
					kpl,
					tunnus
					FROM abc_aputaulu use index (yhtio_tyyppi_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and try = '$arow[try]'
					and $abcwhat > 0
					ORDER BY $abcwhat desc";
		$res = pupe_query($query);

		$i			 = 0;
		$ryhmaprossa = 0;

		while ($row = mysql_fetch_array($res)) {

			// asiakkaan osuus yhteissummasta
			if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
			else $tuoteprossa = 0;

			// muodostetaan ABC-luokka ryhm�prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_try = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = pupe_query($query);

			// luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				if ($i == 3) {
					$i = 2;
				}
			}
		}

	}

	$query = "OPTIMIZE table abc_aputaulu";
	$optir = pupe_query($query);

	if ($php_cli == FALSE) {
		$tee = "";
	}

}

if ($tee == "") {

	// piirrell��n formi
	echo "<form method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<table>";

	echo "<th colspan='4'>".t("Valitse kausi").":</th>";

	echo "<tr><th>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>";
	echo "<tr><th>".t("Loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>".t("ABC-luokkien laskentatapa")."</th>";
	echo "<td colspan='3'>";
	echo "<select name='abctyyppi'>";
	echo "<option value='kate'>".t("Katteen mukaan")."</option>";
	echo "<option value='myynti'>".t("Myynnin mukaan")."</option>";
	echo "<option value='kpl'>".t("Kappaleiden mukaan")."</option>";
	echo "<option value='rivia'>".t("Rivim��r�n mukaan")."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><td colspan='4' class='back'><br></td></tr>";
	echo "<tr><th colspan='1'>".t("Kustannukset valitulla kaudella")."</th>";
	echo "<td colspan='3'><input type='text' name='kustannuksetyht' value='$kustannuksetyht' size='15'></td></tr>";
	echo "<tr><th colspan='1'>".t("Huomioi laskennassa my�s saldottomat tuotteet")."</th>";
	echo "<td colspan='3'><input type='checkbox' name='saldottomatmukaan' value='kylla'></td></tr>";

	echo "</table>";
	echo "<br><input type='submit' value='".t("Rakenna")."'>";
	echo "</form><br><br><br>";

}

if (!$php_cli) {
	require ("../inc/footer.inc");
}

?>