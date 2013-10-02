<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
	$php_cli = TRUE;
}

if ($php_cli) {

	if (!isset($argv[1]) or $argv[1] == '') {
		echo "Anna yhtiˆ!!!\n";
		die;
	}
	
	date_default_timezone_set('Europe/Helsinki');

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	$kukarow['yhtio'] = trim($argv[1]);

	$abctyyppi = "";
	$saldottomatmukaan = "";
	$kustannuksetyht = "";

	if (isset($argv[2]) and trim($argv[2]) != "") {
		$abctyyppi = trim($argv[2]);
	}

	if (isset($argv[3]) and trim($argv[3]) != "") {
		$saldottomatmukaan = trim($argv[3]);
	}

	$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = pupe_query($query);

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
	elseif ($abctyyppi == "rivia") {
		$abcwhat = "rivia";
		$abcchar = "TR";
	}
	elseif ($abctyyppi == "kulutus") {
		$abcwhat = "kpl";
		$abcchar = "TV";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "TM";
	}

	if ($abctyyppi == "kulutus") {
		$kpltyyppi = " tilausrivi.tyyppi='V' ";
		$summasql  = " sum(if(tilausrivi.tyyppi='V', (SELECT sum(-1*kpl*hinta) from tapahtuma where tapahtuma.yhtio=tilausrivi.yhtio and tapahtuma.laji='kulutus' and tapahtuma.rivitunnus=tilausrivi.tunnus), 0)) ";
		$katesql   = " 0 ";

		$riviwhere = " (tilausrivi.tyyppi = 'V' and tilausrivi.toimitettuaika >= '$vva-$kka-$ppa 00:00:00' and tilausrivi.toimitettuaika <= '$vvl-$kkl-$ppl 23:59:59') ";
	}
	else {
		$kpltyyppi = " tilausrivi.tyyppi='L' ";
		$summasql  = " sum(if(tilausrivi.tyyppi='L', tilausrivi.rivihinta, 0)) ";
		$katesql   = " sum(if(tilausrivi.tyyppi='L', tilausrivi.kate, 0)) ";

		$riviwhere = " (tilausrivi.tyyppi in ('L','O') and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl') or
					   (tilausrivi.tyyppi = 'L' and tilausrivi.var = 'P' and tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00' and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59') ";
	}

	// Haetaan abc-parametrit
	list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcchar);

	$i_luokka = count($ryhmaprossat)-1;

	// siivotaan ensin aputaulu tyhj‰ksi
	$query = "	DELETE from abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$res = pupe_query($query);

	// katotaan halutaanko saldottomia mukaan.. default on ett‰ EI haluta
	if ($saldottomatmukaan == "") {
		$tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '') ";
	}
	else {
		$tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno) ";
	}

	// otetaan isot queryt slavelta
	$useslave = 1;
	require ("../inc/connect.inc");

	// Haetaan ensin koko kauden yhteismyynti ja ostot
	$query = "	SELECT
				tilausrivi.tuoteno						tuoteno,
				sum(if(tilausrivi.tyyppi='O', 1, 0))	rivia_osto,
				sum(if($kpltyyppi, 1, 0))				rivia,
				sum(if($kpltyyppi, tilausrivi.kpl, 0)) 	kpl,
				$summasql								summa,
				$katesql 								kate
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				$tuotejoin
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and ($riviwhere)
				GROUP BY 1";
	$res = pupe_query($query);

	//kokokauden kokonaismyynti
	$kaudenmyyriviyht 	= 0;
	$kaudenostriviyht 	= 0;

	// kokokauden kokonaismyynti
	$kausiyhteensa = 0;

	// joudutaan summaamaan loopissa, koska kokonaismyyntiin ei saa vaikuttaa tuotteet joiden kauden myynti/kate/kappaleet on alle nolla
	while ($row = mysql_fetch_array($res)) {

		// onko enemm‰n ku nolla
		if ($row[$abcwhat] > 0) {
			$kausiyhteensa += $row[$abcwhat];
		}

		$kaudenostriviyht += $row["rivia_osto"];
		$kaudenmyyriviyht += $row["rivia"];
	}

	// t‰‰ on nyt hardcoodattu, eli milt‰ kirjanpidon tasolta otetaan kulut
	$sisainen_taso = "34";

	if ($kustannuksetyht == "" and $sisainen_taso != "") {
		// etsit‰‰n kirjanpidosta mitk‰ on meid‰n kulut samalta ajanjaksolta
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

	// rakennetaan tuotekohtaiset ABC-luokat.. haetaan kaikki tilausrivit ajanjaksolta
	$query = "	SELECT
				tilausrivi.tuoteno 					tuoteno,
				ifnull(tuote.try,'#') 				try,
				ifnull(tuote.osasto,'#') 			osasto,
				ifnull(tuote.tuotemerkki,'#') 		tuotemerkki,
				ifnull(tuote.nimitys,'#') 			tuotenimitys,
				ifnull(tuote.luontiaika,'#') 		luontiaika,
				ifnull(tuote.myyjanro,'0') 			myyjanro,
				ifnull(tuote.ostajanro,'0') 		ostajanro,
				ifnull(tuote.malli,'#') 			malli,
				ifnull(tuote.mallitarkenne,'#') 	mallitarkenne,
				ifnull(tuote.vihapvm,'0000-00-00') 	saapumispvm,
				ifnull(tuote.status,'#')			status,
				$summasql							summa,
				$katesql							kate,
				sum(if($kpltyyppi and tilausrivi.var in ('H',''), 1, 0))						rivia,
				sum(if($kpltyyppi and tilausrivi.var in ('H',''), tilausrivi.kpl, 0)) 			kpl,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='P'), tilausrivi.tilkpl, 0))	puutekpl,
				sum(if(tilausrivi.tyyppi='L' and (tilausrivi.var='P'), 1, 0))					puuterivia,
				sum(if(tilausrivi.tyyppi='O', 1, 0))											osto_rivia,
				sum(if(tilausrivi.tyyppi='O', tilausrivi.kpl, 0))								osto_kpl,
				sum(if(tilausrivi.tyyppi='O', tilausrivi.rivihinta, 0))							osto_summa,
				count(distinct if(tilausrivi.tyyppi='O', tilausrivi.otunnus, 0))-1 				osto_kerrat,
				count(distinct if($kpltyyppi, tilausrivi.otunnus, 0))-1 						kerrat,
				(SELECT ifnull(sum(tuotepaikat.saldo) * if(tuote.epakurantti100pvm = '0000-00-00',if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25), 0), 0) from tuote, tuotepaikat where tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) vararvo
				FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
				$tuotejoin
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and ($riviwhere)
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12
	   			ORDER BY $abcwhat desc";
	$res = pupe_query($query);

	$i				= 0;
	$ryhmaprossa	= 0;

	// otetaan takasin master yhteys
	$useslave = 0;
	require ("../inc/connect.inc");

	while ($row = mysql_fetch_array($res)) {

		// ensimm‰inen tulo
		$query = "	SELECT ifnull(left(min(laadittu),10), 0) tulopvm
					FROM tapahtuma USE INDEX (yhtio_laji_tuoteno)
					WHERE yhtio = '$kukarow[yhtio]' and
					tuoteno = '$row[tuoteno]' and
					laji = 'tulo'";
		$insres = pupe_query($query);
		$tulorow = mysql_fetch_array($insres);

		// saldo nyt
		$query = " 	SELECT sum(saldo) saldo
					FROM tuotepaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND	tuoteno = '$row[tuoteno]'";
		$saldores = pupe_query($query);
		$saldorow = mysql_fetch_array($saldores);

		// katotaan onko kelvollinen tuote, elikk‰ luokitteluperuste pit‰‰ olla > 0
		if ($row["${abcwhat}"] > 0) {

			// laitetaan oikeeseen luokkaan
			$luokka = $i;

			// tuotteen osuus yhteissummasta
			if ($kausiyhteensa != 0) $tuoteprossa = ($row["${abcwhat}"] / $kausiyhteensa) * 100;
			else $tuoteprossa = 0;

			//muodostetaan ABC-luokka ryhm‰prossan mukaan
			$ryhmaprossa += $tuoteprossa;
		}
		else {
			// ei ole kelvollinen tuote laitetaan I-luokkaan
			$luokka = $i_luokka;
		}

		if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
		else $kateprosentti = 0;

		if ($row["vararvo"] != 0) $kiertonopeus  = round(($row["summa"] - $row["kate"]) / $row["vararvo"],2);
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

		$query = "	INSERT INTO abc_aputaulu
					SET yhtio			= '$kukarow[yhtio]',
					tyyppi				= '$abcchar',
					luokka				= '$luokka',
					tuoteno				= '$row[tuoteno]',
					nimitys				= '$row[tuotenimitys]',
					osasto				= '$row[osasto]',
					tuotemerkki			= '$row[tuotemerkki]',
					try					= '$row[try]',
					tulopvm				= '$tulorow[tulopvm]',
					summa				= '$row[summa]',
					kate				= '$row[kate]',
					katepros			= '$kateprosentti',
					vararvo				= '$row[vararvo]',
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
					osto_kerrat			= '$row[osto_kerrat]',
					kerrat				= '$row[kerrat]',
					tuote_luontiaika	= '$row[luontiaika]',
					myyjanro			= '$row[myyjanro]',
					ostajanro			= '$row[ostajanro]',
					malli				= '$row[malli]',
					mallitarkenne		= '$row[mallitarkenne]',
					saapumispvm			= '$row[saapumispvm]',
					saldo				= '$saldorow[saldo]',
					status				= '$row[status]'";
		$insres = pupe_query($query);

		// luokka vaihtuu
		if ($ryhmaprossa >= $ryhmaprossat[$i]) {
			$ryhmaprossa = 0;
			$i++;

			// ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
			if ($i == $i_luokka) {
				$i = $i_luokka-1;
			}
		}
	}

	// nyt pit‰‰ viel‰ k‰yd‰ l‰pi kaikki tuotteet joilla on saldoa mutta ei lˆydy viel‰ abc_aputaulusta.. ne kuuluu myˆs I-luokkaan
	$query = "	SELECT
				tuote.tuoteno,
				tuote.try,
				tuote.osasto,
				tuote.luontiaika,
				tuote.tuotemerkki,
				tuote.nimitys,
				tuote.myyjanro,
				tuote.ostajanro,
				tuote.malli,
				tuote.mallitarkenne,
				tuote.vihapvm saapumispvm,
				tuote.status,
				abc_aputaulu.luokka,
				sum(tuotepaikat.saldo) saldo,
				sum(tuotepaikat.saldo) * if(epakurantti100pvm = '0000-00-00',if(epakurantti75pvm='0000-00-00', if(epakurantti50pvm='0000-00-00', if(epakurantti25pvm='0000-00-00', kehahin, kehahin*0.75), kehahin*0.5), kehahin*0.25), 0) vararvo
				FROM tuotepaikat USE INDEX (tuote_index)
				JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
				LEFT JOIN abc_aputaulu USE INDEX (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuotepaikat.yhtio and abc_aputaulu.tuoteno = tuotepaikat.tuoteno and abc_aputaulu.tyyppi = '$abcchar')
				WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13
				HAVING saldo > 0 and luokka is null";
	$tuores = pupe_query($query);

	// ja k‰yd‰‰n kaikki ne tuotteet l‰pi ja lis‰t‰‰n abc_aputauluun
	while ($row = mysql_fetch_array($tuores)) {

		// ensimm‰inen tulo
		$query = "	SELECT ifnull(left(min(laadittu),10), 0) tulopvm
					FROM tapahtuma USE INDEX (yhtio_laji_tuoteno)
					WHERE yhtio = '$kukarow[yhtio]' and
					tuoteno = '$row[tuoteno]' and
					laji = 'tulo'";
		$insres = pupe_query($query);
		$tulorow = mysql_fetch_array($insres);

		$query = "	INSERT INTO abc_aputaulu
					SET yhtio			= '$kukarow[yhtio]',
					tyyppi				= '$abcchar',
					luokka				= '$i_luokka',
					tuoteno				= '$row[tuoteno]',
					nimitys				= '$row[nimitys]',
					osasto				= '$row[osasto]',
					try					= '$row[try]',
					tuotemerkki			= '$row[tuotemerkki]',
					tulopvm				= '$tulorow[tulopvm]',
					vararvo				= '$row[vararvo]',
					tuote_luontiaika	= '$row[luontiaika]',
					myyjanro			= '$row[myyjanro]',
					ostajanro			= '$row[ostajanro]',
					malli				= '$row[malli]',
					mallitarkenne		= '$row[mallitarkenne]',
					saapumispvm			= '$row[saapumispvm]',
					saldo				= '$row[saldo]',
					status				= '$row[status]'";
		$insres = pupe_query($query);

	}

	// p‰ivitet‰‰n kaikille riveille kustannukset
	$query = "	UPDATE abc_aputaulu SET
				kustannus		= round(rivia * '$kustapermyyrivi', 2),
				kustannus_osto	= round(osto_rivia * '$kustaperostrivi', 2),
				kustannus_yht	= kustannus + kustannus_osto
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$ires = pupe_query($query);

	// p‰ivitet‰‰n ensiks kaikki osastot, tuoteryhm‰t ja tuotemerkit I-luokkaan ja k‰yd‰‰n sitten p‰ivitt‰m‰ss‰ niit‰ oikeisiin luokkiin
	$query = "	UPDATE abc_aputaulu SET
				luokka_osasto = '$i_luokka',
				luokka_try = '$i_luokka',
				luokka_tuotemerkki = '$i_luokka'
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$ires = pupe_query($query);

	// haetaan kaikki osastot
	$query = "	SELECT distinct osasto FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by osasto";
	$kaikres = pupe_query($query);

	// tehd‰‰n osastokohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens‰
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

			// tuotteen osuus yhteissummasta
			if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
			else $tuoteprossa = 0;

			//muodostetaan ABC-luokka ryhm‰prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_osasto = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = pupe_query($query);

			//luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				// ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
				if ($i == $i_luokka) {
					$i = $i_luokka-1;
				}
			}
		}

	}

	// haetaan kaikki tryt
	$query = "	SELECT distinct try FROM abc_aputaulu use index (yhtio_tyyppi_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by try";
	$kaikres = pupe_query($query);

	// tehd‰‰n try kohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens‰
		$query = "	SELECT
					sum(rivia) rivia,
					sum(summa) summa,
					sum(kpl)   kpl,
					sum(kate)  kate
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

			// tuotteen osuus yhteissummasta
			if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
			else $tuoteprossa = 0;

			//muodostetaan ABC-luokka ryhm‰prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_try = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = pupe_query($query);

			//luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				// ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
				if ($i == $i_luokka) {
					$i = $i_luokka-1;
				}
			}
		}

	}

	// haetaan kaikki tuotemerkit
	$query = "	SELECT DISTINCT tuotemerkki FROM abc_aputaulu USE INDEX (yhtio_tyyppi_tuotemerkki)
				WHERE yhtio = '$kukarow[yhtio]'
				AND tyyppi = '$abcchar'
				ORDER BY tuotemerkki";
	$kaikres = pupe_query($query);

	// tehd‰‰n try kohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens‰
		$query = "	SELECT
					sum(rivia) rivia,
					sum(summa) summa,
					sum(kpl)   kpl,
					sum(kate)  kate
					FROM abc_aputaulu use index (yhtio_tyyppi_tuotemerkki)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and tuotemerkki = '$arow[tuotemerkki]'
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
					FROM abc_aputaulu use index (yhtio_tyyppi_tuotemerkki)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and tuotemerkki = '$arow[tuotemerkki]'
					and $abcwhat > 0
					ORDER BY $abcwhat desc";
		$res = pupe_query($query);

		$i			 = 0;
		$ryhmaprossa = 0;

		while ($row = mysql_fetch_array($res)) {

			// tuotteen osuus yhteissummasta
			if ($yhtrow["${abcwhat}"] != 0) $tuoteprossa = ($row["${abcwhat}"] / $yhtrow["${abcwhat}"]) * 100;
			else $tuoteprossa = 0;

			//muodostetaan ABC-luokka ryhm‰prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_tuotemerkki = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = pupe_query($query);

			//luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				// ei menn‰ ikin‰ tokavikaa-luokkaa pidemm‰lle
				if ($i == $i_luokka) {
					$i = $i_luokka-1;
				}
			}
		}
	}

	$query = "OPTIMIZE table abc_aputaulu";
	$optir = pupe_query($query);

	if (!$php_cli) {
		echo t("ABC-aputaulu rakennettu")."!<br><br>";
	}
}

if (!$php_cli) {

	// piirrell‰‰n formi
	echo "<form method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<table>";

	echo "<th colspan='4'>".t("Valitse kausi").":</th>";

	echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>";
	echo "<tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>".t("ABC-luokkien laskentatapa")."</th>";
	echo "<td colspan='3'><select name='abctyyppi'>";
	echo "<option value='kate'>".t("Katteen mukaan")."</option>";
	echo "<option value='myynti'>".t("Myynnin mukaan")."</option>";
	echo "<option value='kpl'>".t("Kappaleiden mukaan")."</option>";
	echo "<option value='rivia'>".t("Rivim‰‰r‰n mukaan")."</option>";
	echo "<option value='kulutus'>".t("Kulutuksen mukaan")."</option>";
	echo "</select></td></tr>";

	echo "<tr><td colspan='4' class='back'><br></td></tr>";
	echo "<tr><th colspan='1'>".t("Kustannukset valitulla kaudella")."</th>";
	echo "<td colspan='3'><input type='text' name='kustannuksetyht' value='$kustannuksetyht' size='15'></td></tr>";
	echo "<tr><th colspan='1'>".t("Huomioi laskennassa myˆs saldottomat tuotteet")."</th>";
	echo "<td colspan='3'><input type='checkbox' name='saldottomatmukaan' value='kylla'></td></tr>";

	echo "</table>";
	echo "<br><input type='submit' value='".t("Rakenna")."'>";
	echo "</form><br><br><br>";

}

if (trim($argv[1]) == '') {
	require ("inc/footer.inc");
}

?>