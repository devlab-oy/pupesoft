<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Raaka-aineiden ostoraportti")."</font><hr>";

	// Tarvittavat p‰iv‰m‰‰r‰t
	if (!isset($kka1)) $kka1 = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva1)) $vva1 = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa1)) $ppa1 = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($kkl1)) $kkl1 = date("m");
	if (!isset($vvl1)) $vvl1 = date("Y");
	if (!isset($ppl1)) $ppl1 = date("d");

	// Edellisen vuoden vastaavat kaudet
	$edellinen_alku  = date("Y-m-d", mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
	$edellinen_loppu = date("Y-m-d", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));

	// P‰iv‰m‰‰r‰tarkistus
	if (!checkdate($kka1, $ppa1, $vva1)) {
		echo "<font class='error'>".t("Virheellinen alkup‰iv‰!")."</font><br>";
		$tee = "";
	}

	if (!checkdate($kkl1, $ppl1, $vvl1)) {
		echo "<font class='error'>".t("Virheellinen alkup‰iv‰!")."</font><br>";
		$tee = "";
	}

	// T‰m‰ palauttaa yhden tuotteen ostosuosituksen tiedot
	function teerivi($tuoteno, $isatuoteno) {

		// Kukarow k‰yttˆˆn
		global $kukarow;

		// Haetaan is‰tuotteiden varastosaldo
		$query = "	SELECT ifnull(sum(saldo),0) saldo
					FROM tuotepaikat
					JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio)
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
					AND concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					AND concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					AND tuotepaikat.tuoteno = '$isatuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		// Lasketaan kaikilla varastopaikoilla olevat tuotteet yhteen
		if (mysql_num_rows($result) > 0) {
			$isanvarastosaldo = 0;
			while ($varrow = mysql_fetch_array($result)) {
				$isanvarastosaldo += floatval($varrow['saldo']);
			}
		}

		// Haetaan is‰tuotteiden myynti
		$query = "	SELECT
					sum(if (tilausrivi.tyyppi = 'O' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.varattu,0)) tilattu,
					sum(if ((tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V') AND tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0)) ennpois,
					sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' AND laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl+tilkpl,0)) EDkpl
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi in ('L','V','E')
					AND ((tilausrivi.laskutettuaika >= '$apvm' AND tilausrivi.laskutettuaika <= '$lpvm') or tilausrivi.laskutettuaika = '0000-00-00')
					AND tilausrivi.tuoteno = '$isatuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		$isarow = mysql_fetch_array($result);

		// Haetaan budjetti-indeksi, jos sellainen on m‰‰ritetty
		// TODO: haetaan budjetti-indeksi
		if (1==2) {
		}
		else {
			$budjetti_indeksi = 1;
		}

		// Haetaan lapsituotteen varastosaldo
		$query = "	SELECT ifnull(sum(saldo),0) saldo
					FROM tuotepaikat
					JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio)
					WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
					AND concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					AND concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					AND tuotepaikat.tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		// Lasketaan kaikilla varastopaikoilla olevat tuotteet yhteen
		if (mysql_num_rows($result) > 0) {
			$lapsenvarastosaldo = 0;
			while ($varrow = mysql_fetch_array($result)) {
				$lapsenvarastosaldo += floatval($varrow['saldo']);
			}
		}

		// Haetaan lapsituotteen myynti
		$query = "	SELECT
					sum(if (tilausrivi.tyyppi = 'O' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.varattu,0)) tilattu,
					sum(if ((tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V') AND tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0)) ennpois,
					sum(if(tilausrivi.tyyppi in ('L','V','W') AND toimitettuaika >= '$vva1-01-01' AND toimitettuaika <= '$vvl1-12-31', kpl, 0)) vuosikulutus
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi in ('L','V','E')
					AND ((tilausrivi.laskutettuaika >= '$apvm' AND tilausrivi.laskutettuaika <= '$lpvm') OR tilausrivi.laskutettuaika = '0000-00-00')
					AND tilausrivi.tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query); // TODO: vuosikulutuksen haku pit‰‰ tarkistaa
		$lapsirow = mysql_fetch_array($result);

		// Haetaan lapsituotteen toimittajatiedot
		$query = "	SELECT if (tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, 0) toimitusaika,
					pakkauskoko,
					toimittaja,
					toimi.nimi,
					toimi.tunnus
					FROM tuotteen_toimittajat
					JOIN toimi ON (tuotteen_toimittajat.yhtio = toimi.yhtio AND tuotteen_toimittajat.toimittaja = toimi.ytunnus)
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
					AND tuoteno = '$tuoteno'
					LIMIT 1"; // TODO: tarvisiko t‰t‰ sortata jotenkin?
		$result = mysql_query($query) or pupe_error($query);
		$toimittajarow = mysql_fetch_array($result);
		
		// Is‰tuotteen myyntiennuste
		$isanreaalisaldo = $isanvarastosaldo - $isarow['tilattu'] - $isarow['ennpois'];
		$isanmyyntiennuste = $isarow['EDkpl'] * $budjetti_indeksi - $isanreaalisaldo;
	
		// Lasketaan lapsituotteen ostosuositus
		$lapsenreaalisaldo = $lapsenvarastosaldo - $lapsirow['tilattu'] - $lapsirow['ennpois'];
		$toimitusaika = $toimittajarow['toimitusaika'];
		$vuosikulutus = $lapsirow['vuosikulutus'];
		$vuodenpaivat = 365 + date('L', mktime(0,0,0,0,0,$vvl1));
		$paivakulutus = ($vuosikulutus > 0 ? round($vuosikulutus / $vuodenpaivat) : 0);

		$kulutusennuste = $isanmyyntiennuste * $kerroin - $lapsenreaalisaldo;
		$maaraennuste = $kulutusennuste + ($paivakulutus * $toimitusaika);

		if ($paivakulutus > 0) {
			$riittopv = (floor($lapsenreaalisaldo / $paivakulutus) > 0 ? floor($lapsenreaalisaldo / $paivakulutus) : 0); // N‰ytet‰‰n negatiivinen riitto nollana
		}
		else {
			$riittopv = t('Ei tiedossa'); // Jos aiempaa kulutusta ei ole
		}

		$ostosuositus = (($maaraennuste-$lapsenreaalisaldo) > 0 ? ceil($maaraennuste-$lapsenreaalisaldo) : 0); // N‰ytet‰‰n negatiivinen suositus nollana

		$tuoterivi['kulutusennuste'] 		= $kulutusennuste;
		$tuoterivi['maaraennuste']			= $maaraennuste;
		$tuoterivi['riittopv'] 				= $riittopv;
		$tuoterivi['reaalisaldo'] 			= $lapsenreaalisaldo;
		$tuoterivi['ostosuositus'] 			= $ostosuositus;
		$tuoterivi['toimittajan_tunnus'] 	= $toimittajarow['tunnus'];
		$tuoterivi['toimittajan_ytunnus'] 	= ( !empty($toimittajarow['toimittaja']) ? $toimittajarow['toimittaja'] : t("Ei toimittajaa") );
		$tuoterivi['toimittajan_nimi'] 		= $toimittajarow['nimi'];

		// Jos ei ole pakkauskokoa, oletetaan 1
		$toimittajan_pakkauskoko = ((int) $toimittajarow['pakkauskoko'] == 0) ? 1 : $toimittajarow['pakkauskoko'];

		$tuoterivi['ostoeramaara'] = ceil($ostosuositus / $toimittajan_pakkauskoko) * $toimittajarow['pakkauskoko'] ;

		return $tuoterivi;
	}

	// Tehd‰‰n ostotilaukset
	if ($tee == "TEE_OSTOTILAUKSET") {

		// Ker‰t‰‰n tilattavat tuotteet
		$tilattavat = "(";
		$tilattavatrow = array();

		foreach ($_POST["ostettava_tuote"] as $key => $value) {
			if ($value != 0) {
				$tuoteno = @mysql_real_escape_string($key);
				$tilattavat .= "'$tuoteno',";
				$tilattavatrow[$tuoteno] = @mysql_real_escape_string($value);
			}
		}
		$tilattavat = substr($tilattavat,0,-1).")";

		// Haetaan tuotteiden tiedot
		$query = "	SELECT tuotteen_toimittajat.tuoteno,
					tuotteen_toimittajat.toimittaja,
					tuotteen_toimittajat.toimitusaika,
					tuotteen_toimittajat.tunnus,
					tuotteen_toimittajat.ostohinta,
					tuotteen_toimittajat.valuutta,
					toimi.nimi,
					toimi.osoite,
					toimi.postino,
					toimi.postitp,
					toimi.maa,
					toimi.oletus_valkoodi,
					toimi.ytunnus,
					toimi.ovttunnus,
					tuote.osasto,
					tuote.try,
					tuote.yksikko,
					tuote.nimitys
			FROM tuotteen_toimittajat
			JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.ytunnus = tuotteen_toimittajat.toimittaja)
			JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
			WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
			AND tuotteen_toimittajat.tuoteno in $tilattavat
			ORDER BY tuotteen_toimittajat.toimittaja";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$rows = 0;
			// Laskun headeri
			$laskuheader = "INSERT INTO lasku (
			yhtio,
			yhtio_nimi,
			yhtio_osoite,
			yhtio_postino,
			yhtio_postitp,
			yhtio_maa,
			toim_nimi,
			toim_osoite,
			toim_postino,
			toim_postitp,
			toim_maa,
			nimi,
			osoite,
			postino,
			postitp,
			maa,
			valkoodi,
			toimaika,
			laatija,
			luontiaika,
			tila,
			maksuteksti,
			toimitusehto,
			liitostunnus,
			ytunnus,
			ovttunnus,
			vienti_kurssi,
			viikorkopros,
			tilausyhteyshenkilo
			) VALUES ";

			$riviheader = "INSERT INTO tilausrivi (
			yhtio,
			tyyppi,
			toimaika,
			kerayspvm,
			otunnus,
			tuoteno,
			try,
			osasto,
			nimitys,
			tilkpl,
			yksikko,
			varattu,
			hinta,
			laatija,
			laadittu
			) VALUES ";

			$EDtoimittaja = "";

			while ($tilausrow = mysql_fetch_assoc($result)) {

				if ($EDtoimittaja != $tilausrow['ytunnus']) {

					$EDtoimittaja = $tilausrow['ytunnus'];

					// Tehd‰‰n uusi header
					$headervalues = "('$kukarow[yhtio]',
					'$yhtiorow[nimi]',
					'$yhtiorow[osoite]',
					'$yhtiorow[postino]',
					'$yhtiorow[postitp]',
					'$yhtiorow[maa]',
					'$yhtiorow[nimi]',
					'$yhtiorow[osoite]',
					'$yhtiorow[postino]',
					'$yhtiorow[postitp]',
					'$yhtiorow[maa]',
					'$tilausrow[nimi]',
					'$tilausrow[osoite]',
					'$tilausrow[postino]',
					'$tilausrow[postitp]',
					'$tilausrow[maa]',
					'$tilausrow[valuutta]',
					now(),
					'$kukarow[kuka]',
					now(),
					'O',
					'TOP',
					'$kukarow[oletus_toimituehto]',
					'$tilausrow[tunnus]',
					'$tilausrow[ytunnus]',
					'$tilausrow[ovttunnus]',
					0,
					'$kukarow[viivastyskorko]',
					'$kukarow[tilauksen_yhteyshenkilot]')";
					// TODO: maksuteksti, vienti_kurssi

					$query = $laskuheader.$headervalues;
					mysql_query($query) or pupe_error($query);
					$otunnus = @mysql_insert_id();
					$rows++;
				}

				// Tehd‰‰n rivi
				$tilkpl = $tilattavatrow[$tilausrow['tuoteno']];

				$rivivalues = "('$kukarow[yhtio]',
				'O',
				now(),
				now(),
				'$otunnus',
				'$tilausrow[tuoteno]',
				'$tilausrow[try]',
				'$tilausrow[osasto]',
				'$tilausrow[nimitys]',
				'$tilkpl',
				'$tilausrow[yksikko]',
				'$tilkpl',
				'$tilausrow[ostohinta]',
				'$kukarow[kuka]',
				now())";

				$query = $riviheader.$rivivalues;
				mysql_query($query) or pupe_error($query);
			}

		}

		echo $rows." ".t('ostotilausta muodostettu.');
	}
	
	// Tehd‰‰n raportti
	if ($tee == "RAPORTOI") {

		$tuote_where       = ""; // tuote-rajauksia
		$toimittaja_join   = ""; // toimittaja-rajauksia
		$toimittaja_select = ""; // toimittaja-rajauksia
		$abc_join          = ""; // abc-rajauksia

		if ($osasto != '') {
			$tuote_where .= " and tuote.osasto = '$osasto'";
		}

		if ($tuoryh != '') {
			$tuote_where .= " and tuote.try = '$tuoryh'";
		}

		if ($tuotemerkki != '') {
			$tuote_where .= " and tuote.tuotemerkki = '$tuotemerkki'";
		}

		if ($toimittajaid != '') {
			// Jos ollaan rajattu toimittaja, niin otetaan vain sen toimittajan tuotteet ja laitetaan mukaan selectiin
			$toimittaja_join = "JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid')";
			$toimittaja_select = "tuotteen_toimittajat.toimittaja";
		}
		else {
			// Jos toimittajaa ei olla rajattu, haetaan tuotteen oletustoimittaja subqueryll‰
			$toimittaja_select = "(SELECT toimittaja FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno ORDER BY if(jarjestys = 0, 9999, jarjestys), toimittaja LIMIT 1) toimittaja";
		}

		if ($abcrajaus != "") {
			// katotaan JT:ss‰ olevat tuotteet ABC-analyysi‰ varten, koska ne pit‰‰ includata aina!
			$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
						FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tyyppi IN  ('L','G')
						and var = 'J'
						and jt $lisavarattu > 0";
			$vtresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vtresult);

			$jt_tuotteet = "''";

			if ($vrow[0] != "") {
				$jt_tuotteet = $vrow[0];
			}

			// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
			$abc_join = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
						AND abc_aputaulu.tuoteno = tuote.tuoteno
						AND abc_aputaulu.tyyppi = '$abcrajaustapa'
						AND (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
		}

		// Haetaan raaka-aineet, jotka osuvat hakuehtoihin
		$query = "	SELECT tuote.tuoteno,
					tuote.nimitys,
					tuoteperhe.isatuoteno,
					tuoteperhe.kerroin,
					$toimittaja_select
					FROM tuote
					JOIN tuoteperhe ON (tuote.tuoteno = tuoteperhe.tuoteno AND tuote.yhtio = tuoteperhe.yhtio AND tuoteperhe.tyyppi = 'R')
					$toimittaja_join
					$abc_join
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.ei_saldoa = ''
					$tuote_where
					GROUP BY tuote.tuoteno
					ORDER BY toimittaja, tuote.try, tuote.tuoteno";
		$res = mysql_query($query) or pupe_error($query);

		echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
		echo t("N‰ytet‰‰n ostoraportti aikav‰lille").": $ppa1.$kka1.$vva1 - $ppl1.$kkl1.$vvl1";

		if (mysql_num_rows($res) > 0) {

			// Kootaan raportti
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<table>";

			echo "<tr>";
			echo "<th>".t("Tuotenumero")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			echo "<th>".t("Kulutusennuste")."</th>";
			echo "<th>".t("M‰‰r‰ennuste")."</th>";
			echo "<th>".t("Riitto Pv")."</th>";
			echo "<th>".t("Reaalisaldo")."</th>";
			echo "<th>".t("Ostosuositus")."</th>";
			echo "<th>".t("Ostoer‰m‰‰r‰")."</th>";
			echo "</tr>";

			$EDtoimittaja = false;
			// loopataan tuotteet l‰pi
			while ($row = mysql_fetch_assoc($res)) {

				// Haetaan tuotteen tiedot
				$tuoterivi = teerivi($row["tuoteno"], $row["isatuoteno"], $row["kerroin"]); // Annetaan funktiolle myˆs is‰tuoteno ja kerroin, ettei niit‰ tarvitse hakea erikseen

				// Toimittaja vaihtuu
				if ($tuoterivi['toimittajan_tunnus'] != $EDtoimittaja or $EDtoimittaja === false) {
					$EDtoimittaja_ytunnus = $tuoterivi['toimittajan_ytunnus'];
					$EDtoimittaja_nimi = $tuoterivi['toimittajan_nimi'];
					
					echo "<tr>";
					echo "<th colspan='2'>$EDtoimittaja_ytunnus / $EDtoimittaja_nimi</th>";
					echo "</tr>";
				}

				$EDtoimittaja = $tuoterivi['toimittajan_tunnus'];

				echo "<tr>";
				echo "<td>{$row["tuoteno"]}</td>";
				echo "<td>{$row["nimitys"]}</td>";
				echo "<td>{$tuoterivi["kulutusennuste"]}</td>";
				echo "<td>{$tuoterivi["maaraennuste"]}</td>";
				echo "<td>{$tuoterivi["riittopv"]}</td>";
				echo "<td>{$tuoterivi["reaalisaldo"]}</td>";
				echo "<td>{$tuoterivi["ostosuositus"]}</td>";
				if(empty($EDtoimittaja)) echo "<td>{$tuoterivi["ostoeramaara"]}</td>"; // Tuotteella ei ole toimittajaa
				else echo "<td><input type='text' name='ostettava_tuote[{$row["tuoteno"]}]' value='{$tuoterivi["ostoeramaara"]}'</td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "<br>";

			echo "<input type='hidden' name='tee' value='TEE_OSTOTILAUKSET' />";
			echo "<input type='submit' name='muodosta_ostotilaukset' value='".t('Muodosta ostotilaukset')."' />";
			echo "</form>";
		}
	}

	// N‰ytet‰‰n k‰yttˆliittym‰	
	if ($tee == "") {

		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='RAPORTOI'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Osasto")."</th>";
		echo "<td>";
		echo "<select name='osasto'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";

		// tehd‰‰n avainsana query
		$sresult = t_avainsana("OSASTO", "", "");

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($osasto == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteryhm‰")."</th>";
		echo "<td>";
		echo "<select name='tuoryh'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";

		// tehd‰‰n avainsana query
		$sresult = t_avainsana("TRY", "", "");

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuoryh == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
		}
		echo "</select>";

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuotemerkki")."</th>";
		echo "<td>";

		//Tehd‰‰n osasto & tuoteryhm‰ pop-upit
		$query = "	SELECT distinct tuotemerkki
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					AND tuotemerkki != ''
					ORDER BY tuotemerkki";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<select name='tuotemerkki'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuotemerkki == $srow["tuotemerkki"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
		}
		echo "</select>";

		echo "</td>";
		echo "</tr>";

		// katotaan onko abc aputaulu rakennettu
		$query  = "SELECT count(*) from abc_aputaulu where yhtio = '$kukarow[yhtio]' and tyyppi in ('TK','TR','TP')";
		$abcres = mysql_query($query) or pupe_error($query);
		$abcrow = mysql_fetch_array($abcres);

		// jos on niin n‰ytet‰‰n t‰ll‰nen vaihtoehto
		if ($abcrow[0] > 0) {
			echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td>";

			$sel = array();
			$sel[$abcrajaus] = "SELECTED";

			echo "<select name='abcrajaus'>
			<option value=''>".t("Ei rajausta")."</option>
			<option $sel[0] value='0'>".t("Luokka A-30")."</option>
			<option $sel[1] value='1'>".t("Luokka B-20 ja paremmat")."</option>
			<option $sel[2] value='2'>".t("Luokka C-15 ja paremmat")."</option>
			<option $sel[3] value='3'>".t("Luokka D-15 ja paremmat")."</option>
			<option $sel[4] value='4'>".t("Luokka E-10 ja paremmat")."</option>
			<option $sel[5] value='5'>".t("Luokka F-05 ja paremmat")."</option>
			<option $sel[6] value='6'>".t("Luokka G-03 ja paremmat")."</option>
			<option $sel[7] value='7'>".t("Luokka H-02 ja paremmat")."</option>
			<option $sel[8] value='8'>".t("Luokka I-00 ja paremmat")."</option>
			</select>";

			$sel = array();
			$sel[$abcrajaustapa] = "SELECTED";

			echo "<select name='abcrajaustapa'>
			<option $sel[TK] value='TK'>".t("Myyntikate")."</option>
			<option $sel[TR] value='TR'>".t("Myyntirivit")."</option>
			<option $sel[TP] value='TP'>".t("Myyntikappaleet")."</option>
			</select>
			</td></tr>";
		}

		echo "<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";
		echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

		echo "<tr>";
		echo "<th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
		echo "<td>";
		echo "<input type='text' name='ppa1' value='$ppa1' size='5'>";
		echo "<input type='text' name='kka1' value='$kka1' size='5'>";
		echo "<input type='text' name='vva1' value='$vva1' size='5'>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
		echo "<td>";
		echo "<input type='text' name='ppl1' value='$ppl1' size='5'>";
		echo "<input type='text' name='kkl1' value='$kkl1' size='5'>";
		echo "<input type='text' name='vvl1' value='$vvl1' size='5'>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";
	}

	require ("inc/footer.inc");

?>