<?php
	require ("inc/parametrit.inc");
	require ("inc/tilinumero.inc");

	js_popup();
	echo "<font class='head'>".t("Laskujen maksatus")."</font><hr>";

	if (count($_POST) == 0) {
		// Tarkistetaan laskujen oletusmaksupvm, eli poistetaan vanhentuneet kassa-alet. tehd‰‰n ta‰m‰ aina kun aloitetaan maksatus
		$query = "	UPDATE lasku use index (yhtio_tila_mapvm)
					SET olmapvm = if(kapvm < now() or kapvm > erpcm, erpcm, kapvm)
					WHERE yhtio = '$kukarow[yhtio]'
					and tila in ('H', 'M')
					and mapvm = '0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);
	}

	// P‰ivitet‰‰n oletustili
    if ($tee == 'O') {
		$query = "	UPDATE kuka set
					oletustili = '$oltili'
					WHERE kuka = '$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "V"; // n‰ytet‰‰n k‰ytt‰j‰lle valikko
	}

	// Etsit‰‰n aluksi yrityksen oletustili
	$query = "	SELECT oletustili, yriti.tunnus
				FROM kuka
				JOIN yriti ON (yriti.yhtio = kuka.yhtio and yriti.tunnus = kuka.oletustili and yriti.kaytossa = '')
				WHERE kuka.yhtio = '$kukarow[yhtio]' and
				kuka.kuka = '$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
	$oltilrow = mysql_fetch_array($result);

	if (mysql_num_rows($result) == 0 or strlen($oltilrow[0]) == 0) {
		echo "<br/><font class='error'>".t("Maksutili‰ ei ole valittu")."!</font><br><br>";
		$tee = 'W';
	}

	// Poistamme k‰yttyj‰n oletustilin
	if ($tee == 'X') {
		$query = "	UPDATE kuka set
					oletustili = ''
					WHERE kuka ='$kukarow[kuka]' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = 'W';
	}

	// Lasku merkit‰‰n maksettavaksi ja v‰hennet‰‰n limiitti‰ tai tehd‰‰n vain tarkistukset p‰itt‰invientiin.
	if ($tee == 'H' or $tee == 'G') {
		$tili = $oltilrow[1];

		// maksetaan kassa-alennuksella
		if ($kaale == 'on') {
			$maksettava = "(lasku.summa - lasku.kasumma)";
			$alatila = ", alatila = 'K'";
		}
		else {
			$maksettava = "lasku.summa";
			$alatila = '';
		}

		$query = "	SELECT valuu.kurssi, round($maksettava * valuu.kurssi,2) summa, maksuaika, olmapvm, tilinumero, maa, kapvm, erpcm,
					ultilno, swift, pankki1, pankki2, pankki3, pankki4, sisviesti1, valkoodi
					FROM lasku
					JOIN valuu ON (valuu.yhtio = lasku.yhtio and valuu.nimi = lasku.valkoodi)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<b>".t("Haulla ei lˆytynyt yht‰ laskua")."</b>";

			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_array($result);

		// Tukitaan mahdollinen aikaikkuna
		if ($trow["maksuaika"] == "0000-00-00 00:00:00") {
			$trow["maksuaika"] = "";
		}

		// Se oli jo maksettu
		if (strlen($trow["maksuaika"]) > 0) {
			echo "<font class='error'>".t("Laskun ehti jo joku muu maksaa! Ohitetaan")."! $trow[maksuaika]</font><br>";
			require ("inc/footer.inc");
			exit;
		}

		// virhetilanne, ett‰ kapvm on suurempi kuin ercpm!
		if ($trow['kapvm'] > $trow['erpcm']) $trow['kapvm'] = $trow['erpcm'];

		if ($poikkeus == 'on') 						$trow['olmapvm'] = date("Y-m-d");
		elseif (date("Y-m-d") <= $trow['kapvm']) 	$trow['olmapvm'] = $trow['kapvm'];
		elseif(date("Y-m-d") <= $trow['erpcm']) 	$trow['olmapvm'] = $trow['erpcm'];
		else  										$trow['olmapvm'] = date("Y-m-d");

		//Kotimainen hyvityslasku --> vastaava m‰‰r‰ rahaa on oltava veloituspuolella
		if ($trow['summa'] < 0 and $eipankkiin == '')  {

			if (strtoupper($trow['maa']) == 'FI') {
				$query = "	SELECT sum(if(alatila = 'K' and summa > 0, summa - kasumma, summa)) summa
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tila = 'P'
							and olmapvm = '$trow[olmapvm]'
							and maksaja = '$kukarow[kuka]'
							and maksu_tili = '$tili'
							and maa = 'FI'
							and tilinumero = '$trow[tilinumero]'";
			}
			else {
				$query = "	SELECT sum(if(alatila='K' and summa > 0, summa - kasumma, summa)) summa
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tila = 'P'
							and olmapvm = '$trow[olmapvm]'
							and maksaja = '$kukarow[kuka]'
							and maksu_tili = '$tili'
							and maa <> 'FI'
							and valkoodi = '$trow[valkoodi]'
							and ultilno = '$trow[ultilno]'
							and swift = '$trow[swift]'
							and pankki1 = '$trow[pankki1]'
							and pankki2 = '$trow[pankki2]'
							and pankki3 = '$trow[pankki3]'
							and pankki4 = '$trow[pankki4]'
							and sisviesti1 = '$trow[sisviesti1]'
							and maksaja = '$kukarow[kuka]'";
			}
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<b>".t("Hyvityshaulla ei lˆytynyt mit‰‰n")."</b>$query";
				require ("inc/footer.inc");
				exit;
			}

			$veloitusrow = mysql_fetch_array($result);

			if (strtoupper($trow['maa']) == 'FI') {

				if ($veloitusrow['summa'] + $trow['summa'] < 0) {
					echo "<font class='error'>".t("Hyvityslaskua vastaavaa m‰‰r‰‰ veloituksia ei ole valittuna.")."<br>".t("Valitse samalle asiakkaalle lis‰‰ veloituksia, jos haluat valita t‰m‰n hyvityslaskun maksatukseen")."</font><br><br>";
						$tee = 'S';
				}

				if (abs($veloitusrow['summa'] + $trow['summa']) < 0.01) {

					// Ei ole valittu mit‰ tehd‰‰n
					if ($valinta == '') {

						echo "<font class='message'>".t("Hyvityslasku ja veloituslasku(t) n‰ytt‰v‰t menev‰n p‰itt‰in")."<br>".t("Haluatko, ett‰ ne suoritetaan heti ja j‰tet‰‰n l‰hett‰m‰tt‰ pankkiin")."?</font><br><br>";
						echo "<form action = 'maksa.php' method='post'>
						<input type='hidden' name = 'tee' value='G'>
						<input type='hidden' name = 'valuu' value='$valuu'>
						<input type='hidden' name = 'erapvm' value='$erapvm'>
						<input type='hidden' name = 'kaikki' value='$kaikki'>
						<input type='hidden' name = 'tapa' value='$tapa'>
						<input type='hidden' name = 'tunnus' value='$tunnus'>
						<input type='hidden' name = 'kaale' value='$kaale'>
						<input type='hidden' name = 'poikkeus' value='$poikkeus'>
						<input type='radio' name = 'valinta' value='K' checked> ".t("Kyll‰")."
						<input type='radio' name = 'valinta' value='E'> ".t("Ei")."
						<input type='submit' name = 'valitse' value='".t("Valitse")."'>";

						require ("inc/footer.inc");
						exit;
					}
					if ($valinta == 'E') {
						echo "<font class='error'>".t("Valitut veloitukset ja hyvitykset menev‰t tasan p‰itt‰in (summa 0,-). Pankkiin ei kuitenkaan voi l‰hett‰‰ nolla-summaisia maksuja. Jos haluat l‰hett‰‰ n‰m‰ p‰itt‰in menev‰t veloitukset ja hyvitykset pankkiin, pit‰‰ sinun valita lis‰‰ veloituksia. Yhteissumman pit‰‰ olla suurempi kuin 0.")."</font><br><br>";
						$tee = 'S';
					}
				}
			}
			else {
				if ($veloitusrow['summa'] + $trow['summa'] < 0.01) {
					echo "<font class='error'>".t("Hyvityslaskua vastaavaa m‰‰r‰‰ veloituksia ei ole valittuna.")."<br>".t("Valitse samalle asiakkaalle lis‰‰ veloituksia, jos haluat valita t‰m‰n hyvityslaskun maksatukseen")."</font><br><br>";
					$tee = 'S';
				}
			}
		}
	}

	// Suoritetaan p‰itt‰in (vain kotimaa)
	if ($tee == 'G') {

		//Maksetaan hyvityslasku niin k‰sittely helpottuu
		if ($poikkeus=='on') 						$maksupvm = date("Y-m-d");
		elseif (date("Y-m-d") <= $trow['kapvm']) 	$maksupvm = $trow['kapvm'];
		elseif(date("Y-m-d") <= $trow['erpcm']) 	$maksupvm = $trow['erpcm'];
		else  										$maksupvm = date("Y-m-d");

		$query = "	UPDATE lasku set
					maksaja = '$kukarow[kuka]',
					maksuaika = now(),
					maksu_kurssi = '$trow[0]',
					maksu_tili = '$tili',
					tila = 'P',
					olmapvm = '$maksupvm'
					$alatila
					WHERE tunnus='$tunnus'
					and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$kurssi = 1;
		$query = "	SELECT ytunnus, nimi, postitp,
					round(summa * " . $kurssi . ", 2) 'maksusumma',
					round(kasumma * " . $kurssi . ", 2) 'maksukasumma',
					round(summa * vienti_kurssi, 2) 'vietysumma',
					round(kasumma * vienti_kurssi, 2) 'vietykasumma',
					concat(summa, ' ', valkoodi) 'summa',
					ebid, tunnus, alatila, vienti_kurssi, tapvm
					FROM lasku
					WHERE yhtio 	= '$kukarow[yhtio]'
					and tila		= 'P'
					and olmapvm 	= '$maksupvm'
					and maksu_tili 	= '$tili'
					and maa 	= 'fi'
					and tilinumero	= '$trow[tilinumero]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) < 2) {
			echo "<font class='error'>".t("Laskuja katosi")."</font><br>";

			require ("inc/footer.inc");
			exit;
		}

		while ($laskurow=mysql_fetch_array($result)) {
			// Oletustiliˆinnit
			$summa =  $trow['summa'];
			$selite = "Suoritettu p‰itt‰in $laskurow[nimi]";

			// Kassa-ale
			if ($laskurow['alatila'] != 'K') $laskurow['maksukasumma'] = 0;


			if ($laskurow['maksukasumma'] != 0) {
				// Kassa-alessa on huomioitava alv, joka voi olla useita vientej‰ (uhhhh tai iiikkkkk)
				$totkasumma = 0;

				$query = "	SELECT *
							from tiliointi
							WHERE ltunnus='$tunnus'
							and yhtio = '$kukarow[yhtio]'
							and tapvm = '$laskurow[tapvm]'
							and tilino<>$yhtiorow[ostovelat]
							and tilino<>$yhtiorow[alv]
							and korjattu = ''";
				$yresult = mysql_query($query) or pupe_error($query);

				while ($tiliointirow=mysql_fetch_array ($yresult)) {
					// Kuinka paljon on t‰m‰n viennin osuus
					$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma'] * $laskurow['maksukasumma'],2);

					if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
						$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
						$summa -= $alv;
					}

					$totkasumma += $summa + $alv;

					$query = "	INSERT into tiliointi set
								yhtio ='$kukarow[yhtio]',
								ltunnus = '$laskurow[tunnus]',
								tilino = '$yhtiorow[kassaale]',
								tapvm = '$maksupvm',
								summa = $summa * -1,
								vero = '$tiliointirow[vero]',
								selite = '$selite',
								lukko = '',
								laatija = '$kukarow[kuka]',
								laadittu = now()";
					$xresult = mysql_query($query) or pupe_error($query);
					$isa = mysql_insert_id ($link); // N‰in lˆyd‰mme t‰h‰n liittyv‰t alvit....

					if ($tiliointirow['vero'] != 0) {
						$query = "	INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$laskurow[tunnus]',
									tilino = '$yhtiorow[alv]',
									tapvm = '$maksupvm',
									summa = $alv * -1,
									vero = 0,
									selite = '$selite',
									lukko = '1',
									laatija = '$kukarow[kuka]',
									laadittu = now(),
									aputunnus = $isa";
						$xresult = mysql_query($query) or pupe_error($query);
					}
				}
				//Hoidetaan mahdolliset pyˆristykset
				if ($totkasumma != $laskurow["maksukasumma"]) {
					$query = "UPDATE tiliointi SET summa = summa - $totkasumma + $laskurow[maksukasumma]
							WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
					$xresult = mysql_query($query) or pupe_error($query);
				}
			}

			/* Valuutta-ero (toistaiseksi vain EUROja)
			if ($trow[13] != $kurssi) {
				$summa = $laskurow[maksusumma] - $laskurow[vietysumma];
				if (($laskurow[alatili] == 'K')  && ($laskurow[maksukasumma] != 0)) {
					$summa = $summa - ($laskurow[maksukasumma] - $laskurow[vietykasumma]);
				}
				if (round($summa,2) != 0) {
					$query = "INSERT into tiliointi set
								yhtio ='$kukarow[yhtio]',
								ltunnus = '$laskurow[tunnus]',
								tilino = '$yhtiorow[1]',
								tapvm = '$mav-$mak-$map',
								summa = $summa,
								vero = 0,
								lukko = '',
								laatija = '$kukarow[kuka]',
								laadittu = now()";

					$xresult = mysql_query($query) or pupe_error($query);
				}
			}
			*/

			// Ostovelat
			$query = "	INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus = '$laskurow[tunnus]',
						tilino = '$yhtiorow[ostovelat]',
						tapvm = '$maksupvm',
						summa = '$laskurow[vietysumma]',
						vero = 0,
						selite = '$selite',
						lukko = '',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
			$xresult = mysql_query($query) or pupe_error($query);

			// Rahatili = selvittely
			$query = "	INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus = '$laskurow[tunnus]',
						tilino = '$yhtiorow[selvittelytili]',
						tapvm = '$maksupvm',
						summa = -1 * ($laskurow[maksusumma] - $laskurow[maksukasumma]),
						vero = 0,
						selite = '$selite',
						lukko = '',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
			$xresult = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE lasku set
						tila = 'Y',
						mapvm = '$maksupvm',
						maksu_kurssi = $kurssi
						WHERE tunnus='$laskurow[tunnus]'";
			$xresult = mysql_query($query) or pupe_error($query);
		}
		$tee = 'S';
		echo t("Laskut merkitty suoritetuksi!")."<br><br>";
	}

	// Poimitaan lasku
	if ($tee == 'H') {

		if ($poikkeus == 'on') {
			$muutamaksupaiva = ", olmapvm = now()";
		}
		else {
			$muutamaksupaiva = ", olmapvm = if(now()<=kapvm and kapvm < erpcm, kapvm, if(now()<=erpcm, erpcm, now()))";
		}

		if ($eipankkiin == 'on') {
			$tila	 = 'Q';
			$poppari = ", maksaja = 'Ohitettu', popvm = '".date("Y-m-d")." 12:00:00' ";
		}
		else {
			$tila	 = 'P';
			$poppari = ", maksaja = '$kukarow[kuka]' ";
		}

		$query = "	UPDATE lasku set
					maksuaika = now(),
					maksu_kurssi = '$trow[0]',
					maksu_tili = '$tili',
					tila = '$tila'
					$poppari
					$muutamaksupaiva
					$alatila
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]' and tila='M'";
		$result = mysql_query($query) or pupe_error($query);

 		// Jotain meni pieleen
		if (mysql_affected_rows() != 1) {
			echo "System error Debug --> $query<br>";
			require ("inc/footer.inc");
			exit;
		}

		$query = "	UPDATE yriti set
					maksulimitti = maksulimitti - $trow[summa]
					WHERE tunnus = '$oltilrow[tunnus]' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = 'S';
	}

	// Perutaan maksuun meno
	if ($tee == 'DP') {
		$query = "	SELECT *, if(alatila='K' and summa > 0, summa - kasumma, summa) usumma
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$lasku'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t('Lasku katosi tai se ehdittiin juuri siirt‰‰ pankkiin')."</font><br>";
		}
		else {
			$trow = mysql_fetch_array ($result);

			//Hyvityslasku --> vastaava m‰‰r‰ rahaa on oltava veloituspuolella
			if ($trow['usumma'] > 0) {
				if (strtoupper($trow['maa']) == 'FI') {
					$query = "	SELECT sum(if(alatila='K' and summa > 0, summa - kasumma, summa)) summa
								FROM lasku
								WHERE yhtio='$kukarow[yhtio]'
								and tila='P'
								and olmapvm = '$trow[olmapvm]'
								and maksu_tili = '$trow[maksu_tili]'
								and maa = 'fi'
								and tilinumero='$trow[tilinumero]'
								and maksaja = '$kukarow[kuka]'
								and tunnus != '$lasku'";
				}
				else {
					$query = "	SELECT sum(if(alatila='K' and summa > 0, summa - kasumma, summa)) summa
								FROM lasku
								WHERE yhtio='$kukarow[yhtio]'
								and tila='P'
								and olmapvm = '$trow[olmapvm]'
								and maksu_tili = '$trow[maksu_tili]'
								and maa <> 'fi'
								and valkoodi = '$trow[valkoodi]'
								and ultilno = '$trow[ultilno]'
								and swift = '$trow[swift]'
								and pankki1 = '$trow[pankki1]'
								and pankki2 = '$trow[pankki2]'
								and pankki3 = '$trow[pankki3]'
								and pankki4 = '$trow[pankki4]'
								and sisviesti1 = '$trow[sisviesti1]'
								and maksaja = '$kukarow[kuka]'
								and tunnus != '$lasku'";
				}

				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) != 1) {
					echo "<b>".t("Hyvityshaulla ei lˆytynyt mit‰‰n")."</b>$query";
					require ("inc/footer.inc");
					exit;
				}

				$veloitusrow=mysql_fetch_array ($result);

				if ($veloitusrow['summa'] < 0) {
					echo "<font class='error'>".t("Jos poistat t‰m‰n laskun maksatuksesta, on asiakkaalle valittu liikaa hyvityksi‰.")." ($veloitusrow[summa])</font><br><br>";
					$tee = 'DM';
				}
			}
			if ($tee == 'DP') {
				$query = "	UPDATE lasku set
							maksaja = '',
							maksuaika = '0000-00-00',
							maksu_kurssi = '0',
							maksu_tili = '',
							tila = 'M',
							alatila = '',
							olmapvm = if(kapvm < now(), erpcm, kapvm)
							WHERE tunnus='$lasku' and yhtio = '$kukarow[yhtio]' and tila='P'";
				$updresult = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() != 1) { // Jotain meni pieleen
					echo "System error Debug --> $query<br>";

					require ("inc/footer.inc");
					exit;
				}

				$query = "	UPDATE yriti set
							maksulimitti = maksulimitti + $trow[usumma]
							WHERE tunnus = '$trow[maksu_tili]'
							and yhtio = '$kukarow[yhtio]'";
				$updresult = mysql_query($query) or pupe_error($query);
				$tee = 'DM';
			}
		}
	}

	// Maksetaan nipussa
	if ($tee == "NK" or $tee == "NT" or $tee == "NV") {
		if ($oltilrow['tunnus'] == 0) {
			echo "<br/><font class='error'>",t("Maksutili on kateissa"),"! ",t("Systeemivirhe"),"!</font><br/><br/>";
			require ("inc/footer.inc");
			exit;
		}

		$lisa = "";

		if ($tee == "NT") {
			$lisa = " and lasku.olmapvm = now() ";
		}
		elseif ($tee == 'NK') {
			$lisa = " and lasku.olmapvm <= now() ";
		}
		else {

			if ($valuu != '') {
				$lisa .= " and valkoodi = '" . $valuu ."'";
			}

			if ($erapvm != '') {
				if ($kaikki == 'on') {
					$lisa .= " and olmapvm <= '" . $erapvm ."'";
				}
				else {
					$lisa .= " and olmapvm = '" . $erapvm ."'";
				}
			}

			if ($nimihaku != '') {
				$lisa .= " and lasku.nimi like '%" . $nimihaku ."%'";
			}
		}

		$query = "	SELECT valuu.kurssi, round(if(kapvm>=now(),summa-kasumma,summa) * valuu.kurssi,2) summa, lasku.nimi, lasku.tunnus, lasku.liitostunnus
					FROM lasku
					JOIN valuu ON (valuu.yhtio = lasku.yhtio AND valuu.nimi = lasku.valkoodi)
					JOIN toimi ON (toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus AND toimi.maksukielto = '')
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.summa > 0
					and lasku.tila = 'M'
					$lisa
					ORDER BY lasku.olmapvm, lasku.summa desc";
		$result = mysql_query($query) or pupe_error($query);

		while ($tiliointirow = mysql_fetch_assoc($result)) {

			$query = "SELECT maksulimitti FROM yriti WHERE yhtio='$kukarow[yhtio]' and tunnus='$oltilrow[tunnus]' and yriti.kaytossa = ''";
			$yrires = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yrires) != 1) {
				echo "<br/><font class='error'>",t("Maksutili katosi"),"! ",t("Systeemivirhe"),"!</font><br/><br/>";

				require ("inc/footer.inc");
				exit;
			}

			$mayritirow=mysql_fetch_array ($yrires);

			if ($mayritirow['maksulimitti'] < $tiliointirow['summa']) {
				echo "<br><font class='error'>".t("Maksutilin limitti ylittyi! Laskujen maksu keskeytettiin")."</font><br><br>";
				break;
			}

			$query = "	UPDATE lasku set
						maksaja = '$kukarow[kuka]',
						maksuaika = now(),
						maksu_kurssi = '$tiliointirow[kurssi]',
						maksu_tili = '$oltilrow[tunnus]',
						tila = 'P',
						alatila = if(kapvm>=now(),'K',''),
						olmapvm = if(now()<=kapvm,kapvm,if(now()<=erpcm,erpcm,now()))
						WHERE tunnus='$tiliointirow[tunnus]' and yhtio = '$kukarow[yhtio]' and tila='M'";
			$updresult = mysql_query($query) or pupe_error($query);

			if (mysql_affected_rows() != 1) { // Jotain meni pieleen
				echo "System error Debug --> $query<br>";

				require ("inc/footer.inc");
				exit;
			}

			$query = "	UPDATE yriti set
						maksulimitti = maksulimitti - $tiliointirow[summa]
						WHERE tunnus = '$oltilrow[0]' and yhtio = '$kukarow[yhtio]'";
			$updresult = mysql_query($query) or pupe_error($query);
		}

		$tee = 'DM';
	}

	// Poistetaan lasku
	if ($tee == 'D' and $oikeurow['paivitys'] == '1') {

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tunnus'
					and h1time = '0000-00-00 00:00:00'
					and h2time = '0000-00-00 00:00:00'
					and h3time = '0000-00-00 00:00:00'
					and h4time = '0000-00-00 00:00:00'
					and h5time = '0000-00-00 00:00:00'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_array ($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Poisti laskun")."<br>" . $trow['comments'];

		// Ylikirjoitetaan tiliˆinnit
		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE ltunnus = '$tunnus' and
					yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		// Merkataan lasku poistetuksi
		$query = "	UPDATE lasku SET
					alatila = 'H',
					tila = 'D',
					comments = '$komm'
					WHERE tunnus = '$tunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='error'>".sprintf(t('Poistit %s:n laskun tunnuksella %d.'), $trow['nimi'],$tunnus)."</font><br><br>";

		$tunnus = '';
		$tee 	= 'S';
	}

	// Ei oletustili‰, joten annetaan k‰ytt‰j‰n valita
	if ($tee == 'W') {

		echo "<font class='message'>".t("Valitse maksutili")."</font><hr>";

		$query = "	SELECT tunnus, concat(nimi, ' (', tilino, ')') tili, maksulimitti, valkoodi
	               	FROM yriti
					WHERE yriti.yhtio = '$kukarow[yhtio]'
					and yriti.maksulimitti > 0
					and yriti.factoring = ''
					and yriti.kaytossa = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='head'>".t("Sinulla ei ole yht‰‰n tili‰, jolla olisi limiitti‰")."!<p>".t("K‰y p‰ivitt‰m‰ss‰ limiitit yrityksen pankkitileille")."!</font><hr>";

			require ("inc/footer.inc");
			exit;
		}

		echo "<form action='maksa.php' method='post'>";
		echo "<input type='hidden' name='tee' value='O'>";
		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Tili")."</th>";
		echo "<th colspan='3'>".t("Maksulimitti")."</th>";
		echo "</tr>";

		while ($yritirow = mysql_fetch_array ($result)) {
			echo "<tr>";
			echo "<td>$yritirow[tili]</td>";
			echo "<td align='right'>$yritirow[maksulimitti]</td>";
			echo "<td>$yritirow[valkoodi]</td>";
			echo "<td><input type='radio' name='oltili' value='$yritirow[tunnus]'></td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br>";
		echo "<input type='submit' value='".t("Valitse maksutili")."'>";
		echo "</form>";

		$tee = "";
	}
	else {
		// eli n‰ytet‰‰n tili jolta maksetaan ja sen saldo

		$query = "	SELECT yriti.tunnus, yriti.nimi, yriti.maksulimitti, yriti.valkoodi, round(yriti.maksulimitti * valuu.kurssi, 2) maksulimitti_koti
				 	FROM yriti
					JOIN valuu ON (valuu.yhtio = yriti.yhtio and valuu.nimi = yriti.valkoodi)
				 	WHERE yriti.yhtio = '$kukarow[yhtio]'
					and yriti.tunnus = '$oltilrow[tunnus]'
					and yriti.factoring = ''
					and yriti.kaytossa = ''";
		$result = mysql_query($query) or pupe_error($query);
		$yritirow = mysql_fetch_array($result);

		if (mysql_num_rows($result) != 1) {
			echo t("Etsin tili‰")." '$oltilrow[0]', ".t("mutta sit‰ ei lˆytynyt")."!";
			require ("inc/footer.inc");
			exit;
		}

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tili")."</th>";
		echo "<th>".t("Maksulimitti")."</th>";
		echo "<th></th>";
		echo "<th>".t("Kaikki er‰‰ntyneet")."</th>";
		echo "<th>".t("T‰n‰‰n er‰‰ntyv‰t")."</th>";
		echo "</tr>";

		$maksulimiittikoti = "";
		if ($yritirow["maksulimitti_koti"] != 0 and $yritirow["maksulimitti_koti"] != $yritirow["maksulimitti"]) {
			$maksulimiittikoti = "<br>$yritirow[maksulimitti_koti] $yhtiorow[valkoodi]";
		}

		echo "<tr>";
		echo "<td valign='top'>$yritirow[nimi]</td>";
		echo "<td valign='top' align='right'>$yritirow[maksulimitti] $yritirow[valkoodi]$maksulimiittikoti</td>";
		echo "<td valign='top'>";
		echo "<form action = 'maksa.php' method='post'>";
		echo "<input type='hidden' name = 'tee' value='X'>";
		echo "<input type='Submit' value='".t("Vaihda maksutili‰")."'>";
		echo "</form>";
		echo "</td>";

		// Lis‰t‰‰n t‰h‰n viel‰ mahdollisuus maksaa kaikki er‰‰ntyneet laskut tai t‰n‰‰n er‰‰ntyv‰t
		$query = "	SELECT ifnull(sum(round(if(kapvm >= now(), summa-kasumma, summa) * kurssi, 2)), 0),
					ifnull(sum(round(if(olmapvm = now(), if(kapvm>=now(), summa-kasumma, summa), 0) * kurssi, 2)), 0),
					ifnull(count(*), 0),
					ifnull(sum(if(olmapvm = now(), 1, 0)), 0)
					FROM lasku
					JOIN valuu ON (valuu.yhtio = lasku.yhtio and valuu.nimi = lasku.valkoodi)
					WHERE lasku.yhtio = '$yhtiorow[yhtio]'
					and summa > 0
					and tila = 'M'
					and olmapvm <= now()";
		$result = mysql_query($query) or pupe_error($query);
		$sumrow = mysql_fetch_array($result);

		echo "<td valign='top' align='right'>$sumrow[0] $yhtiorow[valkoodi] ($sumrow[2])";

		if ($sumrow[0] > 0 and $yritirow['maksulimitti'] >= $sumrow[0]) {
			echo "<form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='NK'>
				<input type='hidden' name = 'tili' value='$oltilrow[0]'>
				<input type='Submit' value='".t('Poimi kaikki er‰‰ntyneet')."'>
				</form>";
		}
		echo "</td>";
		echo "<td valign='top' align='right'>$sumrow[1] $yhtiorow[valkoodi] ($sumrow[3])";

		if ($sumrow[1] > 0 and $yritirow['maksulimitti'] >= $sumrow[1]) {
			echo "<form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='NT'>
				<input type='hidden' name = 'tili' value='$oltilrow[0]'>
				<input type='Submit' value='".t('Poimi kaikki t‰n‰‰n er‰‰ntyv‰t')."'>
				</form>";
		}
		echo "</td>";
		echo "</tr></table>";

		if ($tee == '') {
			$tee = "V";
		}
	}

	// N‰ytet‰‰n kaikki omat maksatukseen valitut
	if ($tee == 'DM') {
		$query = "	SELECT lasku.nimi, lasku.kapvm, lasku.erpcm, lasku.valkoodi,
					lasku.summa - lasku.kasumma kasumma,
					lasku.summa,
					round((lasku.summa - lasku.kasumma) * valuu.kurssi,2) ykasumma,
					round(lasku.summa * valuu.kurssi,2) ysumma,
					lasku.ebid, lasku.tunnus, lasku.olmapvm,
					if(lasku.maa='$yhtiorow[maa]', lasku.tilinumero, lasku.ultilno) tilinumero,
					if(alatila = 'K' and summa > 0, summa - kasumma, summa) maksettava_summa,
					if(alatila = 'K' and summa > 0, round(lasku.summa * valuu.kurssi,2) - kasumma, round(lasku.summa * valuu.kurssi,2)) maksettava_ysumma,
					h1time,
					h2time,
					h3time,
					h4time,
					h5time,
					if(alatila='k','*','') kale,
					lasku.tunnus peru,
					yriti.tilino,
					yriti.nimi tilinimi,
					lasku.liitostunnus, lasku.ytunnus, lasku.ovttunnus, lasku.viite, lasku.viesti, lasku.vanhatunnus, lasku.arvo, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro
					FROM lasku, valuu, yriti
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and valuu.yhtio = lasku.yhtio
					and valuu.yhtio = yriti.yhtio
					and yriti.kaytossa = ''
					and lasku.maksu_tili = yriti.tunnus
					and lasku.tila = 'P'
					and lasku.valkoodi = valuu.nimi
					and lasku.maksaja = '$kukarow[kuka]'
					ORDER BY olmapvm, ykasumma desc";
		$result = mysql_query($query) or pupe_error($query);

		echo "<br><font class='message'>".t("Maksuaineistoon poimitut laskut")."</font><hr>";

		if (mysql_num_rows($result) == 0) {
		 	echo "<font class='error'>".t("Ei yht‰‰n poimittua laskua")."!</font><br>";
		}
		else {
			// N‰ytet‰‰n valitut laskut
			echo "<table><tr>";
			echo "<th valign='top'>".t("Nimi")."</th>";
			echo "<th valign='top'>".t("Tilinumero")."</th>";
			echo "<th valign='top'>".t("Er‰pvm")." / ".t("Maksupvm")."</th>";
			echo "<th valign='top' nowrap>".t("Kassa-ale")."</th>";
			echo "<th valign='top'>".t("Summa")."</th>";
			echo "<th valign='top'>".t("Laskunro")."</th>";
			echo "<th valign='top'>".t("Maksutili")."</th>";
			echo "<th valign='top'>".t("Viite")." / ".t("Viesti")."</th>";
			echo "<th valign='top'>".t("Ebid")."</th>";
			echo "<th valign='top'>".t("Maksatus")."</th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
		        echo "<tr class='aktiivi'>";

				$query = "	SELECT count(*), group_concat(concat(lasku.summa, ' ', lasku.valkoodi) separator '<br>')
							from lasku use index (yhtio_tila_summa)
							WHERE yhtio = '$kukarow[yhtio]'
							and tila = 'M'
							and summa < 0
							and if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) = '$trow[tilinumero]'";
				$hyvitysresult = mysql_query($query) or pupe_error($query);
				$hyvitysrow = mysql_fetch_array ($hyvitysresult);

				echo "<td valign='top'>";
				echo "<a name='$trow[tunnus]' href='muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////tee=DM//valuu=$valuu//erapvm=$erapvm//nimihaku=$nimihaku///$tunnus'>$trow[nimi]</a>";

				// jos toimittajalle on maksuvalmiita hyvityksi‰, niin tehd‰‰n alertti!
				if ($hyvitysrow[0] > 0 and $trow["summa"] > 0) {
					echo "<div id='div_$trow[tunnus]' class='popup'>";
					printf(t("Toimittajalle on %s maksuvalmista hyvityst‰!"), $hyvitysrow[0]);
					echo "<br>";
					echo "$hyvitysrow[1]";
					echo "</div>";
					echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/info.png'></a>";
				}

				echo "</td>";

				echo "<td valign='top'>".tilinumero_print($trow["tilinumero"])."</td>";

				echo "<td valign='top'>".tv1dateconv($trow['erpcm'])."<br>".tv1dateconv($trow['olmapvm'])."</td>";

				if ($trow['kapvm'] != '0000-00-00') {
					echo "<td valign='top' align='right' nowrap>";

					if ($trow["kale"] != "") {
						echo t("K‰ytet‰‰n")."<br>";
					}

					echo tv1dateconv($trow["kapvm"])."<br>";
					echo "$trow[ykasumma] $yhtiorow[valkoodi]<br>";

					if (strtoupper($trow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
						echo "$trow[summa] $trow[valkoodi]";
					}

					echo "</td>";
				}
				else {
					echo "<td valign='top' align='right' nowrap></td>";
				}

				echo "<td valign='top' align='right' nowrap>$trow[ysumma] $yhtiorow[valkoodi]<br>";

				$summa += $trow["maksettava_ysumma"];

				if (strtoupper($trow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
					echo "$trow[summa] $trow[valkoodi]";

					$valsumma[$trow["valkoodi"]] += $trow["maksettava_summa"];
				}
				else {
					$valsumma[$trow["valkoodi"]] += $trow["maksettava_summa"];
				}

				echo "</td>";
				echo "<td valign='top'>$trow[laskunro]</td>";
				echo "<td valign='top'>$trow[tilinimi]<br>".tilinumero_print($trow["tilino"])."</td>";
				echo "<td valign='top'>$trow[viite] $trow[viesti]";

				if ($trow["vanhatunnus"] != 0) {
					$query = "	SELECT summa, valkoodi
								from lasku
								where yhtio = '$kukarow[yhtio]'
								and tila in ('H','Y','M','P','Q')
								and vanhatunnus = '$trow[vanhatunnus]'";
					$jaetutres = mysql_query($query) or pupe_error($query);

					echo "<div id='div_$trow[tunnus]' class='popup'>";
					printf(t("Lasku on jaettu %s osaan!"), mysql_num_rows($jaetutres));
					echo "<br>".t("Alkuper‰inen summa")." $trow[arvo] $trow[valkoodi]<br>";
					$osa = 1;
					while ($jaetutrow = mysql_fetch_array ($jaetutres)) {
						echo "$osa: $jaetutrow[summa] $jaetutrow[valkoodi]<br>";
						$osa++;
					}
					echo "</div>";
					echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/alert.png'></a>";
				}

				echo "</td>";

				// tehd‰‰n lasku linkki
				echo "<td nowrap valign='top'>";
				$lasku_urlit = ebid($trow['tunnus'], true);
				if (count($lasku_urlit) == 0) {
					echo t("Paperilasku");
				}
				foreach ($lasku_urlit as $lasku_url) {
					echo "<a href='$lasku_url' target='Attachment'>",t("N‰yt‰ liite"),"</a><br>";
				}
				echo "</td>";

				echo "<td valign='top'>
						<form action = 'maksa.php' method='post'>
						<input type='hidden' name = 'tee' value='DP'>
						<input type='hidden' name = 'lasku' value='$trow[peru]'>
						<input type='Submit' value='".t('Poista aineistosta')."'>
						</form></td>";

				echo "</tr>";
			}

			echo "</table>";

			echo "<br><font class='message'>".t("Poimitut laskut yhteens‰")."</font><hr>";

			echo "<table>";

			foreach($valsumma as $val => $sum) {
				echo "<tr><th colspan='3'>$val ".t("laskut")." ".t("yhteens‰")."</th><td valign='top' align='right'>".sprintf('%.2f', $sum)." $val</td></tr>";
			}

			echo "<tr><th colspan='3'>".t("Kaikki")." ".t("laskut")." ".t("yhteens‰")."</th><td valign='top' align='right'>".sprintf('%.2f', $summa)." $yhtiorow[valkoodi]</td></tr>";

			echo "</table>";
		}

		$tee='V';
	}

	// N‰ytet‰‰n maksuvalmiit laskut
	if ($tee == 'S') {

		$lisa = "";

		if ($valuu != '') {
			$lisa .= " and valkoodi = '" . $valuu ."'";
		}

		if ($erapvm != '') {
			if ($kaikki == 'on') {
				$lisa .= " and olmapvm <= '" . $erapvm ."'";
			}
			else {
				$lisa .= " and olmapvm = '" . $erapvm ."'";
			}
		}

		if ($nimihaku != '') {
			$lisa .= " and lasku.nimi like '%" . $nimihaku ."%'";
		}

		$query = "	SELECT lasku.nimi, lasku.kapvm, lasku.erpcm, lasku.valkoodi,
					lasku.summa - lasku.kasumma kasumma,
					lasku.summa,
					round((lasku.summa - lasku.kasumma) * valuu.kurssi,2) ykasumma,
					round(lasku.summa * valuu.kurssi,2) ysumma,
					lasku.ebid, lasku.tunnus, lasku.olmapvm,
					if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) tilinumero,
					h1time,
					h2time,
					h3time,
					h4time,
					h5time,
					lasku.liitostunnus, lasku.ytunnus, lasku.ovttunnus, lasku.viesti, lasku.comments, lasku.viite, lasku.vanhatunnus, lasku.arvo, lasku.maa, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro
					FROM lasku use index (yhtio_tila_mapvm)
					JOIN valuu ON lasku.yhtio=valuu.yhtio and lasku.valkoodi = valuu.nimi
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'M'
					and lasku.valkoodi = valuu.nimi
					and lasku.mapvm = '0000-00-00'
					$lisa
					ORDER BY olmapvm, ykasumma  desc";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<br><font class='error'>".t("Haulla ei lˆytynyt yht‰‰n laskua")."</font><br>";
		}
		else {
			// N‰ytet‰‰n valitut laskut
			echo "<br><font class='message'>".t("Maksuvalmiit laskut")."</font><hr>";

			echo "<table><tr>";
			echo "<th valign='top'>".t("Nimi")."</th>";
			echo "<th valign='top'>".t("Tilinumero")."</th>";
			echo "<th valign='top'>".t("Er‰pvm")."</th>";
			echo "<th valign='top' nowrap>".t("Kassa-ale")."</th>";
			echo "<th valign='top'>".t("Summa")."</th>";
			echo "<th valign='top'>".t("Laskunro")."</th>";
			echo "<th valign='top'>".t("Viite")." / ".t("Viesti")."</th>";
			echo "<th valign='top'>".t("Ebid")."</th>";
			echo "<th valign='top'>".t("Maksatus")."</th>";
			echo "<th valign='top'>".t("Lis‰tieto")."</th>";
			echo "</tr>";

			$dataseek = 0;

			while ($trow = mysql_fetch_array ($result)) {

		        echo "<tr class='aktiivi'>";

				$query = "	SELECT count(*), group_concat(concat(lasku.summa, ' ', lasku.valkoodi) separator '<br>')
							from lasku use index (yhtio_tila_summa)
							WHERE yhtio = '$kukarow[yhtio]'
							and tila = 'M'
							and summa < 0
							and if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) = '$trow[tilinumero]'";
				$hyvitysresult = mysql_query($query) or pupe_error($query);
				$hyvitysrow = mysql_fetch_array ($hyvitysresult);

				echo "<td valign='top'>";
				echo "<a name='$trow[tunnus]' href='muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////tee=S//valuu=$valuu//erapvm=$erapvm//nimihaku=$nimihaku///$tunnus'>$trow[nimi]</a>";

				// jos toimittajalle on maksuvalmiita hyvityksi‰, niin tehd‰‰n alertti!
				if ($hyvitysrow[0] > 0 and $trow["summa"] > 0) {
					echo "<div id='div_$trow[tunnus]' class='popup'>";
					printf(t("Toimittajalle on %s maksuvalmista hyvityst‰!"), $hyvitysrow[0]);
					echo "<br>";
					echo "$hyvitysrow[1]";
					echo "</div>";
					echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/info.png'></a>";
				}

				echo "</td>";
				echo "<td valign='top'>".tilinumero_print($trow["tilinumero"])."</td>";

				echo "<td valign='top'>";

				// er‰p‰iv‰ punasella jos se on er‰‰ntynyt
				if ($trow['olmapvm'] < date("Y-m-d")) {
					echo "<font class='error'>".tv1dateconv($trow['erpcm'])."</font>";
				}
				else {
					echo tv1dateconv($trow['erpcm']);
				}

				if ($trow['kapvm'] != '0000-00-00') {
					echo "<td valign='top' align='right' nowrap>";
					echo tv1dateconv($trow['kapvm'])."<br>";
					echo "$trow[ykasumma] $yhtiorow[valkoodi]<br>";
					if (strtoupper($trow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
						echo "$trow[summa] $trow[valkoodi]";
					}
					echo "</td>";
				}
				else {
					echo "<td valign='top' align='right' nowrap></td>";
				}

				echo "<td valign='top' align='right' nowrap>$trow[ysumma] $yhtiorow[valkoodi]<br>";

				$summa += $trow["ysumma"];

				if (strtoupper($trow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
					echo "$trow[summa] $trow[valkoodi]";
					$valsumma[$trow["valkoodi"]] += $trow["summa"];
				}
				else {
					$valsumma[$trow["valkoodi"]] += $trow["summa"];
				}

				echo "</td>";

				echo "<td valign='top'>$trow[laskunro]</td>";
				echo "<td valign='top'>$trow[viite] $trow[viesti]";

				if ($trow["vanhatunnus"] != 0) {
					$query = "	SELECT summa, valkoodi
								from lasku
								where yhtio = '$kukarow[yhtio]'
								and tila in ('H','Y','M','P','Q')
								and vanhatunnus = '$trow[vanhatunnus]'";
					$jaetutres = mysql_query($query) or pupe_error($query);

					echo "<div id='div_$trow[tunnus]' class='popup'>";
					printf(t("Lasku on jaettu %s osaan!"), mysql_num_rows($jaetutres));
					echo "<br>".t("Alkuper‰inen summa")." $trow[arvo] $trow[valkoodi]<br>";
					$osa = 1;
					while ($jaetutrow = mysql_fetch_array ($jaetutres)) {
						echo "$osa: $jaetutrow[summa] $jaetutrow[valkoodi]<br>";
						$osa++;
					}
					echo "</div>";
					echo " <a class='tooltip' id='$trow[tunnus]'><img src='$palvelin2/pics/lullacons/alert.png'></a>";
				}

				echo "</td>";

				// tehd‰‰n lasku linkki
				echo "<td nowrap valign='top'>";
				$lasku_urlit = ebid($trow['tunnus'], true);
				if (count($lasku_urlit) == 0) {
					echo t("Paperilasku");
				}
				foreach ($lasku_urlit as $lasku_url) {
					echo "<a href='$lasku_url' target='Attachment'>N‰yt‰ liite</a><br>";
				}
				echo "</td>";

				// Ok, mutta onko meill‰ varaa makssa kyseinen lasku???
				if ($trow["ysumma"] <= $yritirow[2]) {

					echo "<td valign='top' nowrap>";

					//Kikkaillaan jotta saadda seuraavan laskun tunnus
					if ($dataseek < mysql_num_rows($result)-1) {
						$kikkarow = mysql_fetch_array($result);
						mysql_data_seek($result, $dataseek+1);
						$kikkalisa = "#$kikkarow[tunnus]";
					}
					else {
						$kikkalisa = "";
					}

					echo "<form action = 'maksa.php$kikkalisa' method='post'>";

					$query = "	SELECT maksukielto
								FROM toimi
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$trow[liitostunnus]'";
					$maksukielto_res = mysql_query($query) or pupe_error($query);
					$maksukielto_row = mysql_fetch_assoc($maksukielto_res);

					if ($maksukielto_row['maksukielto'] == 'K') {
						echo "<font class='error'>",t("Maksukiellossa"),"</font>";
					}
					else {
						echo "	<input type='hidden' name = 'tee' value='H'>
								<input type='hidden' name = 'tunnus' value='$trow[tunnus]'>
								<input type='hidden' name = 'valuu' value='$valuu'>
								<input type='hidden' name = 'erapvm' value='$erapvm'>
								<input type='hidden' name = 'kaikki' value='$kaikki'>
								<input type='hidden' name = 'nimihaku' value='$nimihaku'>
								<input type='hidden' name = 'tapa' value='$tapa'>
								<input type='Submit' value='".t("Poimi lasku")."'>";
					}

					echo "</td>";

					echo "<td valign='top' nowrap>";

					if ($trow["ysumma"] != $trow["ykasumma"] and $trow['ysumma'] > 0) {
						$ruksi='checked';
						if ($trow['kapvm'] < date("Y-m-d")) {
							$ruksi = ''; // Ooh, maksamme myˆh‰ss‰
						}
						echo "<input type='checkbox' name='kaale' $ruksi> ";
						echo t("K‰yt‰ kassa-ale");
						echo "<br>";
					}

					if ($trow['olmapvm'] >= date("Y-m-d") and strtoupper($trow['maa']) == 'FI') {
						echo "<input type='checkbox' name='poikkeus'> ";
						echo t("Maksetaan heti");
						echo "<br>";
					}
					elseif (strtoupper($trow['maa']) != 'FI') {
						echo "<input type='checkbox' DISABLED CHECKED> ";
						echo "<input type='hidden' name='poikkeus' value='on'>";
						echo t("Maksetaan heti");
						echo "<br>";
					}

					if ($trow['summa'] < 0) { //Hyvitykset voi hoitaa ilman pankkiinl‰hetyst‰
						echo "<input type='checkbox' name='eipankkiin'> ";
						echo t("ƒl‰ l‰het‰ pankkiin");
						echo "<br>";
					}
					echo "</form>";
					echo "</td>";


					//Tutkitaan voidaanko lasku poistaa
					$query = "	SELECT tunnus
								from lasku use index (yhtio_vanhatunnus)
								where yhtio		= '$kukarow[yhtio]'
								and tila		= 'K'
								and vanhatunnus	= '$trow[tunnus]'";
					$delres2 = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($delres2) == 0 and $trow['h1time'] == '0000-00-00 00:00:00' and $trow['h2time'] == '0000-00-00 00:00:00' and $trow['h3time'] == '0000-00-00 00:00:00' and $trow['h4time'] == '0000-00-00 00:00:00' and $trow['h5time'] == '0000-00-00 00:00:00') {
						echo "	<td class='back'>
								<form action = 'maksa.php' method='post' onSubmit = 'return confirm(\"".t("Haluatko todella poistaa t‰m‰n laskun ja sen kaikki tiliˆinnit? T‰m‰ voi olla kirjanpitorikos!")."\");'>
								<input type='hidden' name = 'tee' value='D'>
								<input type='hidden' name = 'tunnus' value='$trow[tunnus]'>
								<input type='hidden' name = 'valuu' value='$valuu'>
								<input type='hidden' name = 'tapa' value='$tapa'>
								<input type='hidden' name = 'erapvm' value='$erapvm'>
								<input type='hidden' name = 'kaikki' value='$kaikki'>
								<input type='hidden' name = 'nimihaku' value='$nimihaku'>
								<input type='hidden' name = 'tapa' value='$tapa'>
								<input type='Submit' value='".t("Poista lasku")."'>
								</form>
								</td>";
					}

				}
				else {
					// ei ollutkaan varaa!!
					echo "<td colspan='2' valign='top'><font class='error'>".t("Tilin limitti ei riit‰")."!</td>";
				}
				echo "</tr>";

				$dataseek++;
			}
			echo "</table>";

			echo "<br><font class='message'>".t("Haetut yhteens‰")."</font><hr>";

			echo "<table>";

			foreach($valsumma as $val => $sum) {
				echo "<tr><th colspan='3'>$val ".t("laskut")." ".t("yhteens‰")."</th><td valign='top' align='right'>".sprintf('%.2f', $sum)." $val</td></tr>";
			}

			echo "<tr><th colspan='3'>".t("Kaikki")." ".t("laskut")." ".t("yhteens‰")."</th><td valign='top' align='right'>".sprintf('%.2f', $summa)." $yhtiorow[valkoodi]</td></tr>";

			echo "</table>";

			// jos limiitti riitt‰‰ niin annetaan mahdollisuus poimia kaikki
			if ($yritirow['maksulimitti_koti'] >= $summa) {
				echo "<br><form action = '$PHP_SELF' method='post'>
					<input type='hidden' name = 'tili' value='$tili'>
					<input type='hidden' name = 'tee' value='NV'>
					<input type='hidden' name = 'kaikki' value='$kaikki'>
					<input type='hidden' name = 'valuu' value='$valuu'>
					<input type='hidden' name = 'erapvm' value='$erapvm'>
					<input type='hidden' name = 'nimihaku' value='$nimihaku'>
					<input type='Submit' value='".t('Poimi kaikki haetut veloituslaskut')."'>
					</form><br>";
			}

		}
		$tee = "V";
	}

	// Tehd‰‰n hakuk‰yttˆliittym‰
	if ($tee == 'V') {

		echo "<br><font class='message'>".t("Etsi maksuvalmiita laskuja")."</font><hr>";
		// T‰ll‰ ollaan, jos valitaan maksujen selailutapoja

		echo "	<form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='S'>";
		echo "	<table>";

		// Valuutoittain
		echo "<tr>";
		echo "<th>".t("Valuutta")."</th>";
		echo "<td>";

		$query = "	SELECT valkoodi, count(*)
			  		FROM lasku
			  		WHERE yhtio = '$kukarow[yhtio]' and tila = 'M'
			  		GROUP BY valkoodi
			  		ORDER BY valkoodi";
		$result = mysql_query($query) or pupe_error($query);

		echo "<select name='valuu'>";

		if (mysql_num_rows($result) > 0) {
			$kaikaval = 0;
			while ($valuurow = mysql_fetch_array ($result)) {
				$kaikaval += $valuurow[1];
			}
		}

		echo "<option value=''>".t("Kaikki valuutat")." ($kaikaval)";

		if (mysql_num_rows($result) > 0) {
			mysql_data_seek($result, 0);

			while ($valuurow = mysql_fetch_array ($result)) {
				if ($valuurow[0] == $valuu) {
					$sel = "SELECTED";
				}
				else{
					$sel = "";
				}

				echo "<option value='$valuurow[0]' $sel>$valuurow[0] ($valuurow[1])";
			}
		}

		echo "</select></td>";
		echo "<td></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Er‰p‰iv‰")."</th>";

		$query = "	SELECT olmapvm, count(*)
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila = 'M'
					GROUP BY olmapvm
					ORDER BY olmapvm";
		$result = mysql_query($query) or pupe_error($query);

		echo "<td><select name='erapvm'>";

		if (mysql_num_rows($result) > 0) {
			$kaikaval = 0;
			while ($laskurow = mysql_fetch_array ($result)) {
				$kaikaval += $laskurow[1];
			}
		}

		echo "<option value=''>".t("Kaikki er‰p‰iv‰t")." ($kaikaval)";

		if (mysql_num_rows($result) > 0) {
			mysql_data_seek($result, 0);

			while ($laskurow = mysql_fetch_array ($result)) {
				if ($laskurow[0] == $erapvm) {
					$sel = "SELECTED";
				}
				else{
					$sel = "";
				}

				echo "<option value = '$laskurow[0]' $sel>".tv1dateconv($laskurow[0])." ($laskurow[1])";
			}
		}

		echo "</select></td>";

		if ($kaikki != "") {
			$sel = "CHECKED";
		}
		else{
			$sel = "";
		}

		echo "<td>".t("N‰yt‰ myˆs vanhemmat")." <input type='Checkbox' name='kaikki' $sel></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Nimi")."</th><td><input type='text' name='nimihaku' size='15' value='$nimihaku'></td><td></td>";
		echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></tr>";
		echo "</table>";
		echo "</form>";

		echo "<br><font class='message'>".t("Poimitut laskut")."</font><hr>";
		echo "<table>";
		$query = "	SELECT lasku.tunnus
					FROM lasku, valuu, yriti
					WHERE lasku.yhtio 		= '$kukarow[yhtio]'
					and valuu.yhtio 		= lasku.yhtio
					and valuu.yhtio 		= yriti.yhtio
					and lasku.maksu_tili 	= yriti.tunnus
					and yriti.kaytossa		= ''
					and lasku.tila	 		= 'P'
					and lasku.valkoodi 		= valuu.nimi
					and lasku.maksaja 		= '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "	<tr><th>".t("Poimitut laskut")."</th>
				<td> ".mysql_num_rows($result)." ".t("laskua poimittu")."</td>
				<td>
				<form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='DM'>
				<input type='Submit' value='".t('N‰yt‰ jo poimitut laskut')."'>
				</form>
				</td></td>";

		echo "<table>";

	}

	require ("inc/footer.inc");

?>