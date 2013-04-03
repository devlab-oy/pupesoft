<?php

	if (isset($_REQUEST["komento"]) and in_array("PDF_RUUDULLE", $_REQUEST["komento"])) {
		$nayta_pdf = 1; //Generoidaan .pdf-file
	}
	else {
		unset($nayta_pdf);
	}

	if (isset($_REQUEST["tee"])) {
		if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if(isset($_REQUEST["kaunisnimi"]) and $_REQUEST["kaunisnimi"] != '') {
			$_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
		}
	}

	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("$pupe_root_polku/dataout/".basename($filenimi));
		exit;
	}

	if (!isset($nayta_pdf)) {
		// scripti balloonien tekemiseen
		js_popup();
	}

	if (!isset($nayta_pdf) and $livesearch_tee == "TUOTEHAKU") {
		livesearch_tuotehaku();
		exit;
	}

	if (!isset($nayta_pdf) and $yhtiorow["livetuotehaku_tilauksella"] == "K") {
		enable_ajax();
	}

	// jos ei olla postattu mit‰‰n, niin halutaan varmaan tehd‰ kokonaan uusi tilaus..
	if (count($_POST) == 0 and $from == "") {
		$tila				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow["kesken"]	= '';

		//varmistellaan ettei vanhat kummittele...
		$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = pupe_query($query);
	}

	if ($from == "LASKUTATILAUS") {
		$tee = "AKTIVOI";
	}

	// Katostaan, ett‰ tilaus on viel‰ samassa tilassa jossa se oli kun se klikattiin auku muokkaatilaus-ohjelmassa
	if ($tee == 'AKTIVOI' and $mista == "muokkaatilaus") {
		$query = "	SELECT tila, alatila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus  = '$tilausnumero'
					AND tila 	= '$orig_tila'
					AND alatila = '$orig_alatila'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
				echo "<font class='error'>".t("Tilauksen tila on vaihtunut. Ole hyv‰ avaa tilaus uudestaan").".</font><br>";

				// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
				$query = "UPDATE kuka SET kesken='' WHERE yhtio='$kukarow[yhtio]' AND kuka='$kukarow[kuka]'";
				$result = pupe_query($query);
				exit;
		}
	}

	if ($tee == 'AKTIVOI') {
		// katsotaan onko muilla aktiivisena
		$query = "SELECT * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
		$result = pupe_query($query);

		unset($row);

		if (mysql_num_rows($result) != 0) {
			$row = mysql_fetch_array($result);
		}

		if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
			echo "<font class='error'>".t("Tilaus on aktiivisena k‰ytt‰j‰ll‰")." $row[nimi]. ".t("Tilausta ei voi t‰ll‰ hetkell‰ muokata").".</font><br>";

			// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
			$query = "UPDATE kuka SET kesken='' WHERE yhtio='$kukarow[yhtio]' AND kuka='$kukarow[kuka]'";
			$result = pupe_query($query);
			exit;
		}
		else {
			$query = "	UPDATE kuka
						SET kesken = '$tilausnumero'
						WHERE yhtio = '$kukarow[yhtio]' AND
						kuka = '$kukarow[kuka]' AND
						session = '$session'";
			$result = pupe_query($query);

			$kukarow['kesken'] 	 = $tilausnumero;
			$tee = "Y";
		}
	}

	if ($tee != "") {
		//katsotaan ett‰ kukarow kesken ja $kukarow[kesken] stemmaavat kesken‰‰n
		if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
			echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("K‰y aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
			exit;
		}
		if ($kukarow['kesken'] != '0') {
			$tilausnumero=$kukarow['kesken'];
		}
	}

	// Setataan lopetuslinkki, jotta p‰‰semme takaisin tilaukselle jos k‰yd‰‰n jossain muualla
	$tilost_lopetus = "{$palvelin2}tilauskasittely/tilaus_osto.php////toim=$toim//tilausnumero=$tilausnumero//toim_nimitykset=$toim_nimitykset//naytetaankolukitut=$naytetaankolukitut";

	if ($lopetus != "") {
		// Lis‰t‰‰n t‰m‰ lopetuslinkkiin
		$tilost_lopetus = $lopetus."/SPLIT/".$tilost_lopetus;
	}

	if ((int) $kukarow['kesken'] == 0 or $tee == "MUUOTAOSTIKKOA") {
		require("otsik_ostotilaus.inc");
	}

	if ($tee != "" and $tee != "MUUOTAOSTIKKOA") {

		// Hateaan tilauksen tiedot
		$query = "	SELECT *
					FROM lasku
					WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]' and tila = 'O'";
		$aresult = pupe_query($query);

		if (mysql_num_rows($aresult) == 0) {
			echo "<font class='message'>".t("VIRHE: Tilausta ei lˆydy")."!<br><br></font>";
			exit;
		}

		$laskurow = mysql_fetch_array($aresult);

		if ($tee == "vahvista") {
			$query = "	UPDATE tilausrivi
						SET jaksotettu = 1
						WHERE yhtio     = '$kukarow[yhtio]'
						and otunnus     = '$kukarow[kesken]'
						and tyyppi      = 'O'
						and uusiotunnus = 0";
			$result = pupe_query($query);

			if (mysql_affected_rows() > 0) {
				echo "<font class='message'>".t("Toimitus vahvistettu")."</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Toimituksella ei ollut vahvistettavia rivej‰")."</font><br><br>";
			}

			$tee = "Y";
		}

		if ($tee == 'poista') {
			// poistetaan tilausrivit
			$query  = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
			$result = pupe_query($query);

			// Nollataan sarjanumerolinkit
			$query = "	SELECT tilausrivi.tunnus,
						tuote.sarjanumeroseuranta
						FROM tilausrivi
						JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$kukarow[kesken]'";
			$sres = pupe_query($query);

			while ($srow = mysql_fetch_array($sres)) {
				if ($srow['sarjanumeroseuranta'] != '') {
					$query = "	UPDATE sarjanumeroseuranta
								SET ostorivitunnus = 0
								WHERE yhtio = '$kukarow[yhtio]'
								AND ostorivitunnus = '$srow[tunnus]'";
					$sarjares = pupe_query($query);
				}

				tarkista_myynti_osto_liitos_ja_poista($srow['tunnus'], false);
			}

			$query = "UPDATE lasku SET tila='D', alatila='$laskurow[tila]', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mit‰tˆi tilauksen")." ohjelmassa tilaus_osto.php ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = pupe_query($query);

			$query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
			$result = pupe_query($query);

			$tee = "";
			$kukarow["kesken"] = 0; // Ei en‰‰ kesken
		}

		if ($tee == 'poista_kohdistamattomat') {
			// poistetaan kohdistamattomat ostotilausrivit
			$query = "	UPDATE tilausrivi
						SET tyyppi = 'D'
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$kukarow[kesken]'
						AND uusiotunnus = 0";
			$result = pupe_query($query);

			echo "<font class='message'>".t("Kohdistamattomat tilausrivit poistettu")."!<br><br></font>";

			$query = "	SELECT tilausrivi.tunnus
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$kukarow['kesken']}'
						AND uusiotunnus = 0";
			$result = pupe_query($query);

			while($ostotilausrivi = mysql_fetch_assoc($result)) {
				tarkista_myynti_osto_liitos_ja_poista($ostotilausrivi['tunnus'], false);
			}

			$tee = "Y";
		}

		if ($tee =='valmis') {

			//tulostetaan tilaus kun se on valmis
			$otunnus = $kukarow["kesken"];

			if (count($komento) == 0) {
				// p‰ivitet‰‰n t‰ss‰ tilaus valmiiksi
				$query = "UPDATE lasku SET alatila = 'A' WHERE tunnus='$kukarow[kesken]'";
				$result = pupe_query($query);

				if ($toim == "HAAMU") {
					echo "<font class='head'>".t("Tyˆ/tarvikeosto").":</font><hr><br>";
				}
				else {
					echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";
				}

				$tulostimet[0] = "Ostotilaus";
				require("../inc/valitse_tulostin.inc");
			}

			// luodaan varastopaikat jos tilaus on optimoitu varastoon...
			$query = "SELECT * from lasku WHERE tunnus='$kukarow[kesken]'";
			$result = pupe_query($query);
			$laskurow = mysql_fetch_array($result);

			if ($laskurow['tila'] != 'O') {
				echo t("Kesken oleva tilaus ei ole ostotilaus");
				exit;
			}

			// katotaan ollaanko haluttu optimoida johonki varastoon
			if ($laskurow["varasto"] != 0) {

				$query = "SELECT * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]' and tyyppi='O'";
				$result = pupe_query($query);

				// k‰yd‰‰n l‰pi kaikki tilausrivit
				while ($ostotilausrivit = mysql_fetch_array($result)) {

					// k‰yd‰‰n l‰pi kaikki tuotteen varastopaikat
					$query = "	SELECT *, concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								hyllyalue, hyllynro, hyllytaso, hyllyvali
					 			from tuotepaikat
								where yhtio='$kukarow[yhtio]' and tuoteno='$ostotilausrivit[tuoteno]'
								order by sorttauskentta";
					$tuopaires = pupe_query($query);

					// apulaskuri
					$kuuluu = 0;

					while ($tuopairow = mysql_fetch_array($tuopaires)) {
						// katotaan kuuluuko tuotepaikka haluttuun varastoon
						if (kuuluukovarastoon($tuopairow["hyllyalue"], $tuopairow["hyllynro"], $laskurow["varasto"]) != 0) {

							// jos kuului niin p‰ivitet‰‰n info tilausriville
							$query = "	UPDATE tilausrivi set
										hyllyalue = '$tuopairow[hyllyalue]',
										hyllynro  = '$tuopairow[hyllynro]',
										hyllytaso = '$tuopairow[hyllytaso]',
										hyllyvali = '$tuopairow[hyllyvali]'
										where yhtio = '$kukarow[yhtio]' and
										tunnus = '$ostotilausrivit[tunnus]'";
							$tuopaiupd = pupe_query($query);

							$kuuluu++;
							break; // breakataan niin ei looppailla en‰‰ turhaa
						}
					} // end while tuopairow

					// tuotteella ei ollut varastopaikkaa halutussa varastossa, tehd‰‰n sellainen
					if ($kuuluu == 0) {

						// haetaan halutun varaston tiedot
						$query = "SELECT alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'";
						$hyllyres = pupe_query($query);
						$hyllyrow =  mysql_fetch_array($hyllyres);

						// katotaan lˆytykˆ yht‰‰n tuotepaikkaa, jos ei niin teh‰‰n oletus
						if (mysql_num_rows($tuopaires) == 0) {
							$oletus = 'X';
						}
						else {
							$oletus = '';
						}

			   			if (!isset($nayta_pdf)) echo "<font class='error'>".t("Tehtiin uusi varastopaikka")." $ostotilausrivit[tuoteno]: $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0</font><br>";

						// lis‰t‰‰n paikka
						$query = "	INSERT INTO tuotepaikat set
			 						yhtio		= '$kukarow[yhtio]',
						 			tuoteno     = '$ostotilausrivit[tuoteno]',
						 			oletus      = '$oletus',
				   		 			saldo       = '0',
				   		 			saldoaika   = now(),
									hyllyalue 	= '$hyllyrow[alkuhyllyalue]',
									hyllynro 	= '$hyllyrow[alkuhyllynro]',
									hyllytaso 	= '0',
									hyllyvali 	= '0'";
						$updres = pupe_query($query);

						// tehd‰‰n tapahtuma
						$query = "	INSERT into tapahtuma set
									yhtio 		= '$kukarow[yhtio]',
									tuoteno 	= '$ostotilausrivit[tuoteno]',
									kpl 		= '0',
									kplhinta	= '0',
									hinta 		= '0',
									laji 		= 'uusipaikka',
									hyllyalue 	= '$hyllyrow[alkuhyllyalue]',
									hyllynro 	= '$hyllyrow[alkuhyllynro]',
									hyllytaso 	= '0',
									hyllyvali 	= '0',
									selite 		= '".t("Lis‰ttiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
									laatija 	= '$kukarow[kuka]',
									laadittu 	= now()";
						$updres = pupe_query($query);

						// p‰ivitet‰‰n tilausrivi
						$query = "	UPDATE tilausrivi set
									hyllyalue 	= '$hyllyrow[alkuhyllyalue]',
									hyllynro 	= '$hyllyrow[alkuhyllynro]',
									hyllytaso 	= '0',
									hyllyvali 	= '0'
									where yhtio = '$kukarow[yhtio]' and
									tunnus = '$ostotilausrivit[tunnus]'";
						$updres = pupe_query($query);

					}
				} // end while ostotilausrivit
			} // end if varasto != 0

			if (isset($nayta_pdf)) $tee = "NAYTATILAUS";

			require('tulosta_ostotilaus.inc');

			// p‰ivitet‰‰n t‰ss‰ tilaus tulostetuksi
			$query = "UPDATE lasku SET lahetepvm = now() WHERE tunnus='$kukarow[kesken]'";
			$result = pupe_query($query);

			if ($toim == "HAAMU") {
				$query = "UPDATE lasku SET tila='D', tilaustyyppi = 'O' WHERE tunnus='$kukarow[kesken]'";
				$result = pupe_query($query);

				$query = "UPDATE tilausrivi SET tyyppi = 'D' WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
				$result = pupe_query($query);
			}

			$query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
			$result = pupe_query($query);

			$kukarow["kesken"] 	= '';
			$tilausnumero 		= 0;
			$tee 				= '';

			if ($lopetus != '') {
				lopetus($lopetus, "META");
			}
		}

		//Kuitataan OK-var riville
		if ($tee == "OOKOOAA") {
			$query = "	UPDATE tilausrivi
						SET var2 = 'OK'
						WHERE tunnus = '$rivitunnus'";
			$result = pupe_query($query);

			$tee 		= "Y";
			$rivitunnus = "";
		}

		// Olemassaolevaa rivi‰ muutetaan, joten poistetaan se ja annetaan perustettavaksi
		if ($tee == 'PV') {
			$query = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta, tuote.myyntihinta_maara
						FROM tilausrivi use index (PRIMARY)
						LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						WHERE tilausrivi.tunnus = '$rivitunnus'
						and tilausrivi.yhtio	= '$kukarow[yhtio]'
						and tilausrivi.otunnus	= '$kukarow[kesken]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo t("Tilausrivi ei en‰‰ lˆydy")."! $query";
				exit;
			}
			$tilausrivirow = mysql_fetch_array($result);

			$query = "	DELETE
						FROM tilausrivi
						WHERE tunnus = '$rivitunnus'";
			$result = pupe_query($query);

			tarkista_myynti_osto_liitos_ja_poista($rivitunnus, false);

			// Tehd‰‰n pari juttua jos tuote on sarjanumeroseurannassa
			if ($tilausrivirow["sarjanumeroseuranta"] != '') {
				//Nollataan sarjanumero
				$query = "SELECT tunnus FROM sarjanumeroseuranta WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]' and ostorivitunnus='$tilausrivirow[tunnus]'";
				$sarjares = pupe_query($query);
				$sarjarow = mysql_fetch_array($sarjares);

				//Pidet‰‰n sarjatunnus muistissa
				$osto_sarjatunnus = $sarjarow["tunnus"];

				$query = "UPDATE sarjanumeroseuranta SET ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' AND tuoteno='$tilausrivirow[tuoteno]' AND ostorivitunnus='$tilausrivirow[tunnus]'";
				$sarjares = pupe_query($query);
			}


			$hinta 			= $tilausrivirow["hinta"];
			$tuoteno 		= $tilausrivirow["tuoteno"];
			$tuotenimitys	= $tilausrivirow["nimitys"];
			$kpl 			= $tilausrivirow["tilkpl"];

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
				${'ale'.$alepostfix} = $tilausrivirow["ale{$alepostfix}"];
			}

			$toimaika 		= $tilausrivirow["toimaika"];
			$kerayspvm 		= $tilausrivirow["kerayspvm"];
			$alv 			= $tilausrivirow["alv"];
			$kommentti 		= $tilausrivirow["kommentti"];
			$perheid2 		= $tilausrivirow["perheid2"];
			$rivitunnus 	= $tilausrivirow["tunnus"];
			$automatiikka 	= "ON";
			$tee 			= "Y";

			if ($tilausrivirow["myyntihinta_maara"] != 0 and $hinta != 0) {
				$hinta = hintapyoristys($hinta * $tilausrivirow["myyntihinta_maara"]);
			}
		}

		if ($tee == 'POISTA_RIVI') {
			tarkista_myynti_osto_liitos_ja_poista($rivitunnus, true);

			$automatiikka = "ON";
			$tee = "Y";
		}

		// Tyhjennet‰‰n tilausrivikent‰t n‰ytˆll‰
		if ($tee == 'TI' and isset($tyhjenna)) {

			$tee = "Y";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
				unset(${'ale'.$alepostfix});
				unset(${'ale_array'.$alepostfix});
				unset(${'kayttajan_ale'.$alepostfix});
			}

			unset($alv);
			unset($alv_array);
			unset($hinta);
			unset($hinta_array);
			unset($kayttajan_alv);
			unset($kayttajan_hinta);
			unset($kayttajan_kpl);
			unset($kayttajan_netto);
			unset($kayttajan_var);
			unset($kerayspvm);
			unset($kommentti);
			unset($kpl);
			unset($kpl_array);
			unset($netto);
			unset($netto_array);
			unset($paikat);
			unset($paikka);
			unset($paikka_array);
			unset($perheid);
			unset($perheid2);
			unset($rivinumero);
			unset($rivitunnus);
			unset($toimaika);
			unset($tuotenimitys);
			unset($tuoteno);
			unset($var);
			unset($variaatio_tuoteno);
			unset($var_array);
		}

		if ($tee == "LISLISAV") {
			//P‰ivitet‰‰n is‰n perheid jotta voidaan lis‰t‰ lis‰‰ lis‰varusteita
			$query = "	UPDATE tilausrivi use index (primary)
						set perheid2 = -1
						where yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$rivitunnus'";
			$updres = pupe_query($query);
			$tee = "Y";
		}

		// Rivi on lisataan tietokantaan
		if ($tee == 'TI' and $tuoteno != "") {
			if ($toim != "HAAMU") {
				$multi = "TRUE";
				require("inc/tuotehaku.inc");
			}
		}

		if ($tee == 'TI' and ((trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array))) and ($variaatio_tuoteno == "" or (is_array($kpl_array) and array_sum($kpl_array) != 0))) {
			if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
				$tuoteno_array[] = $tuoteno;
			}

			// K‰ytt‰j‰n syˆtt‰m‰ hinta ja ale ja netto, pit‰‰ s‰ilˆ‰ jotta tuotehaussakin voidaan syˆtt‰‰ n‰m‰
			$kayttajan_hinta	= $hinta;
			$kayttajan_netto 	= $netto;
			$kayttajan_var		= $var;
			$kayttajan_kpl		= $kpl;
			$kayttajan_alv		= $alv;

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
				${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
			}

			foreach ($tuoteno_array as $tuoteno) {

				$query	= "	SELECT *
							FROM tuote
							WHERE tuoteno = '$tuoteno'
							and yhtio = '$kukarow[yhtio]'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					//Tuote lˆytyi
					$trow = mysql_fetch_array($result);
				}
				else {
					//Tuotetta ei lˆydy, arvataan muutamia muuttujia
					$trow["alv"] = $laskurow["alv"];
				}

				if (checkdate($toimkka,$toimppa,$toimvva)) {
					$toimaika = $toimvva."-".$toimkka."-".$toimppa;
				}
				if (checkdate($kerayskka,$keraysppa,$keraysvva)) {
					$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
				}
				if ($toimaika == "" or $toimaika == "0000-00-00") {
					$toimaika = $laskurow["toimaika"];
				}
				if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
					$kerayspvm = $laskurow["kerayspvm"];
				}

				$varasto = $laskurow["varasto"];

				//Tehd‰‰n muuttujaswitchit
				if (is_array($hinta_array)) {
					$hinta = $hinta_array[$tuoteno];
				}
				else {
					$hinta = $kayttajan_hinta;
				}

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					if (is_array(${'ale_array'.$alepostfix})) {
						${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
					}
					else {
						${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
					}
				}

				if (is_array($netto_array)) {
					$netto = $netto_array[$tuoteno];
				}
				else {
					$netto = $kayttajan_netto;
				}

				if (is_array($var_array)) {
					$var = $var_array[$tuoteno];
				}
				else {
					$var = $kayttajan_var;
				}

				if (is_array($kpl_array)) {
					$kpl = $kpl_array[$tuoteno];
				}
				else {
					$kpl = $kayttajan_kpl;
				}

				if (is_array($alv_array)) {
					$alv = $alv_array[$tuoteno];
				}
				else {
					$alv = $kayttajan_alv;
				}

				//rivitunnus nollataan lisaarivissa
				$rivitunnus_temp = $rivitunnus;

				if ($kpl != "") {
					require ('lisaarivi.inc');
				}

				if (!empty($lisatyt_rivit2)) {
					foreach ($lisatyt_rivit2 as $rivitunnus_temp) {

						// Haetaan myyntirivi
						$query = "	SELECT tilausrivin_lisatiedot.tilausrivitunnus
									FROM tilausrivin_lisatiedot
									JOIN tilausrivi ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
									WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivin_lisatiedot.tilausrivilinkki = '{$rivitunnus_temp}'";
						$result = pupe_query($query);
						$tilausrivin_lisatiedot_row = mysql_fetch_assoc($result);

						//jos ostorivi on naitettu myyntiriviin
						if (!empty($tilausrivin_lisatiedot_row)) {
							// p‰ivitet‰‰n myyntitilausrivi, jos se on liitetty ostotilausriviin (nollarivej‰ ei elvytet‰...)
							$query = "	UPDATE tilausrivi
										SET tilausrivi.tyyppi  = 'L'
										WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
										AND tilausrivi.tunnus  = '{$tilausrivin_lisatiedot_row['tilausrivitunnus']}'
										AND tilausrivi.tyyppi  = 'D'
										AND tilausrivi.varattu+tilausrivi.jt != 0
										AND tilausrivi.kerattyaika    = '0000-00-00 00:00:00'
										AND tilausrivi.toimitettuaika = '0000-00-00 00:00:00'
										AND tilausrivi.laskutettuaika = '0000-00-00'";
							pupe_query($query);

							if (mysql_affected_rows() > 0) {
								echo "<font class='error'>".t("Myyntitilausriville p‰ivitettiin m‰‰r‰t")."</font><br/><br/>";
							}
							else {
								echo "<font class='error'>".t("Myyntitilausrivi on ker‰tty, toimitettu tai laskutettu, joten sit‰ ei p‰ivitetty")."</font><br/><br/>";
							}
						}
					}
				}

				$hinta 	= '';
				$netto 	= '';
				$var 	= '';
				$kpl 	= '';
				$alv 	= '';
				$paikka	= '';

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = '';
				}
			}

			if ($lisavarusteita == "ON" and $perheid2 > 0) {
				//P‰ivitet‰‰n is‰lle perheid jotta tiedet‰‰n, ett‰ lis‰varusteet on nyt lis‰tty
				$query = "	UPDATE tilausrivi set
							perheid2	= '$perheid2'
							where yhtio = '$kukarow[yhtio]'
							and tunnus 	= '$perheid2'";
				$updres = pupe_query($query);
			}

			$tee = "Y";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
				unset(${'ale'.$alepostfix});
				unset(${'ale_array'.$alepostfix});
				unset(${'kayttajan_ale'.$alepostfix});
			}

			unset($alv);
			unset($alv_array);
			unset($hinta);
			unset($hinta_array);
			unset($kayttajan_alv);
			unset($kayttajan_hinta);
			unset($kayttajan_kpl);
			unset($kayttajan_netto);
			unset($kayttajan_var);
			unset($kerayspvm);
			unset($kommentti);
			unset($kpl);
			unset($kpl_array);
			unset($netto);
			unset($netto_array);
			unset($paikat);
			unset($paikka);
			unset($paikka_array);
			unset($perheid);
			unset($perheid2);
			unset($rivinumero);
			unset($rivitunnus);
			unset($toimaika);
			unset($tuotenimitys);
			unset($tuoteno);
			unset($var);
			unset($variaatio_tuoteno);
			unset($var_array);
		}
		elseif ($tee == 'TI') {
			$tee = "Y";
		}

		//lis‰t‰‰n rivej‰ tiedostosta
		if ($tee == 'mikrotila' or $tee == 'file') {
			require('mikrotilaus_ostotilaus.inc');
		}

		// Jee meill‰ on otsikko!
		if ($tee == 'Y') {

			// ekotetaan javascripti‰ jotta saadaan pdf:‰t uuteen ikkunaan
			js_openFormInNewWindow();

			if ($toim == "HAAMU") {
				echo "<font class='head'>".t("Tyˆ/tarvikeosto").":</font><hr><br>";
			}
			else {
				echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";
			}

			echo "<table>";
			echo "<tr>";
			echo "<td class='back'>
					<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
					<input type='hidden' name='toim' 				value = '$toim'>
					<input type='hidden' name='lopetus' 			value = '$lopetus'>
					<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
					<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
					<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
					<input type='hidden' name='tee' 				value = 'MUUOTAOSTIKKOA'>
					<input type='hidden' name='tila' 				value = 'Muuta'>
					<input type='Submit' value='".t("Muuta otsikkoa")."'>
					</form>
					</td>";

			echo "<td class='back'>
					<form action='tuote_selaus_haku.php' method='post'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='submit' value='".t("Selaa tuotteita")."'>
					</form>
					</td>";

			echo "<td class='back'>
					<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
					<input type='hidden' name='toim' 				value = '$toim'>
					<input type='hidden' name='lopetus' 			value = '$lopetus'>
					<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
					<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
					<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
					<input type='hidden' name='tee' 				value = 'mikrotila'>
					<input type='Submit' value='".t("Lue tilausrivit tiedostosta")."'>
					</form>
					</td>";

			if (tarkista_oikeus('yllapito.php', 'liitetiedostot')) {

				if ($laskurow["tunnusnippu"] > 0) {
					$id = $laskurow["tunnusnippu"];
				}
				else {
					$id = $laskurow["tunnus"];
				}

				echo "<td class='back'>
						<form method='POST' action='{$palvelin2}yllapito.php?toim=liitetiedostot&from=tilausmyynti&ohje=off&haku[7]=@lasku&haku[8]=@$id&lukitse_avaimeen=$id&lukitse_laji=lasku'>
						<input type='hidden' name='lopetus' value='{$tilost_lopetus}//from=LASKUTATILAUS'>
						<input type='hidden' name='toim_kutsu' value='{$toim}'>
						<input type='submit' value='",t('Tilauksen liitetiedostot'),"'>
						</form>
						</td>";
			}

			$query = "	SELECT a.fakta, l.ytunnus, l.tila, l.alatila
						FROM toimi a, lasku l
						WHERE l.tunnus='$kukarow[kesken]' and l.yhtio='$kukarow[yhtio]' and a.yhtio = l.yhtio and a.tunnus = l.liitostunnus";
			$faktaresult = pupe_query($query);
			$faktarow = mysql_fetch_array($faktaresult);

			if (tarkista_oikeus('yllapito.php', 'tuote')) {

				echo "<td class='back'>";
				echo "<form method='POST' action='{$palvelin2}yllapito.php?toim=tuote&from=tilausmyynti&ohje=off&uusi=1'>
						<input type='hidden' name='lopetus' value='{$tilost_lopetus}//from=LASKUTATILAUS//mista=muokkaatilaus//tilausnumero={$tilausnumero}//orig_tila={$faktarow['tila']}//orig_alatila={$faktarow['alatila']}'>
						<input type='hidden' name='toim_kutsu' value='{$toim}'>
						<input type='hidden' name='liitostunnus' value='{$laskurow['liitostunnus']}' />
						<input type='hidden' name='tee_myos_tuotteen_toimittaja_liitos' value='JOO' />
						<input type='submit' value='",t('Uusi tuote'),"'>
						</form>";
				echo "</td>";

			}

			echo "</tr>";
			echo "</table><br>";

			echo "<table>";

			echo "<tr><th>".t("Ytunnus")."</th><th colspan='3'>".t("Toimittaja")."</th></tr>";
			echo "<tr><td>".tarkistahetu($laskurow["ytunnus"])."</td><td colspan='3'>$laskurow[nimi] $laskurow[nimitark]<br>$laskurow[osoite] $laskurow[postino] $laskurow[postitp]</td></tr>";

			echo "<tr><th colspan='4'>".t("Toimitusosoite")."</th></tr>";
			echo "<tr><td colspan='4'>$laskurow[toim_nimi] $laskurow[toim_nimitark]<br>$laskurow[toim_osoite] $laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>";

			echo "<tr><th>".t("Tilausnumero")."</th><th>".t("Laadittu")."</th><th>".t("Toimaika")."</th><th>".t("Valuutta")."</th><td class='back'></td></tr>";
			echo "<tr><td>$laskurow[tunnus]</td><td>".tv1dateconv($laskurow["luontiaika"])."</td><td>".tv1dateconv($laskurow["toimaika"])."</td><td>{$laskurow["valkoodi"]}</td></tr>";

			if ($faktarow["fakta"] != "") {
				echo "<tr><th>".t("Fakta")."</th><td colspan='3'>$faktarow[fakta]&nbsp;</td></tr>";
			}

			echo "</table><br>";

			echo "<font class='head'>".t("Lis‰‰ rivi").": </font><hr>";

			require('syotarivi_ostotilaus.inc');

			if ($huomio != '') {
				echo "<font class='message'>$huomio</font><br>";
				$huomio = '';
			}

			echo "<font class='head'>".t("Tilausrivit").": </font><hr>";

			if (!isset($toim_nimitykset)) {
				$toim_nimitykset = "ME";
			}

			$sel = array();
			$sel[$toim_nimitykset] = "CHECKED";

			echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
					<input type='hidden' name='toim' 				value = '$toim'>
					<input type='hidden' name='lopetus' 			value = '$lopetus'>
					<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
					<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
					<input type='hidden' name='tee' 				value = 'Y'>";

			echo "<font class='info'>";
			echo t("Nimitykset").": <input onclick='submit();' type='radio' name='toim_nimitykset' value='ME' {$sel["ME"]}> ".t("Omat")." <input onclick='submit();' type='radio' name='toim_nimitykset' value='HE' {$sel["HE"]}> ".t("Toimittajan")."";
			echo "</font>";
			echo "</form>";

			// katotaan onko joku rivi jo liitetty johonkin saapumiseen ja jos on niin annetaan mahdollisuus piilottaa lukitut rivit
			$query = "SELECT * from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$laskurow[tunnus]' and uusiotunnus != 0";
			$kaunisres = pupe_query($query);

			if (mysql_num_rows($kaunisres) != 0) {

				if ($naytetaankolukitut == "EI") {
					$sel_ky = "";
					$sel_ei = "CHECKED";
				}
				else {
					$sel_ky = "CHECKED";
					$sel_ei = "";
				}
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

				echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
						<input type='hidden' name='toim' 				value = '$toim'>
						<input type='hidden' name='lopetus' 			value = '$lopetus'>
						<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
						<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
						<input type='hidden' name='tee' 				value = 'Y'>";

				echo "<font class='info'>".t("N‰ytet‰‰nkˆ lukitut rivit").": <input onclick='submit();' type='radio' name='naytetaankolukitut' value='kylla' $sel_ky> ".t("Kyll‰")." <input onclick='submit();' type='radio' name='naytetaankolukitut' value='EI' $sel_ei> ".t("Ei");
				echo "</font>";
				echo "</form>";
			}

			// katotaan miten halutaan sortattavan
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

			//"ei_erikoisale" koska rivill‰ ei haluta v‰hent‰‰ erikoisalea hinnasta, vaan se n‰ytet‰‰n erikseen yhteenvedossa
			$query_ale_lisa = generoi_alekentta("O", '', 'ei_erikoisale');

			$ale_query_select_lisa = generoi_alekentta_select('erikseen', 'O');

			//Listataan tilauksessa olevat tuotteet
			$query = "	SELECT tilausrivi.nimitys,
						concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
						tilausrivi.tuoteno,
						tuotteen_toimittajat.toim_tuoteno,
						tuotteen_toimittajat.toim_nimitys,
						tuotteen_toimittajat.valuutta,
						tilausrivi.tilkpl tilattu,
						round(tilausrivi.tilkpl*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) tilattu_ulk,
						round((tilausrivi.varattu+tilausrivi.jt)*tilausrivi.hinta*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
						tilausrivi.alv, tilausrivi.toimaika, tilausrivi.kerayspvm, tilausrivi.uusiotunnus, tilausrivi.tunnus, tilausrivi.perheid2, tilausrivi.hinta, {$ale_query_select_lisa} tilausrivi.varattu varattukpl, tilausrivi.kommentti,
						$sorttauskentta,
						tilausrivi.var,
						tilausrivi.var2,
						tilausrivi.jaksotettu,
						tilausrivi.yksikko,
						tuote.tuotemassa,
						tuote.kehahin keskihinta,
						tuotteen_toimittajat.ostohinta,
						tuotteen_toimittajat.valuutta,
						tilausrivi.erikoisale,
						tilausrivi.ale1,
						tilausrivi.ale2,
						tilausrivi.ale3
						FROM tilausrivi
						LEFT JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
						WHERE otunnus = '$kukarow[kesken]'
						and tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'O'
						ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
			$presult = pupe_query($query);

			$rivienmaara = mysql_num_rows($presult);

			if ($rivienmaara > 0) {

				echo "<table><tr>";
				echo "<th>#</th>";
				echo "<th align='left'>".t("Nimitys")."</th>";
				echo "<th align='left'>".t("Paikka")."</th>";
				echo "<th align='left'>".t("Tuote")."</th>";
				echo "<th align='left'>".t("Toim Tuote")."</th>";
				echo "<th align='left'>".t("M‰‰r‰")."<br>".t("M‰‰r‰/Ulk")."</th>";
				echo "<th align='left'>".t("Hinta")."</th>";

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					echo "<th align='left'>".t("Ale")."{$alepostfix}</th>";
				}

				echo "<th align='left'>".t("Alv")."</th>";
				echo "<th align='left'>".t("Rivihinta")."</th>";
				echo "<th align='left'>".t("Valuutta")."</th>";
				echo "</tr>";

				$yhteensa 		= 0;
				$paino_yhteensa = 0;
				$nettoyhteensa 	= 0;
				$eimitatoi 		= '';
				$lask 			= mysql_num_rows($presult);
				$tilausok 		= 0;
				$divnolla		= 0;
				$erikoisale_summa = 0;

				while ($prow = mysql_fetch_array ($presult)) {
					$divnolla++;
					$erikoisale_summa += (($prow['rivihinta'] * ($laskurow['erikoisale'] / 100)) * -1);
					$yhteensa 		  += $prow["rivihinta"];
					$paino_yhteensa   += ($prow["tilattu"]*$prow["tuotemassa"]);

					$class = "";

					if ($prow["uusiotunnus"] == 0 or $naytetaankolukitut != "EI") {

						echo "<tr>";

						// Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
						if ($prow["perheid"] == $prow["tunnus"] or ($prow["perheid2"] == $prow["tunnus"] and $prow["perheid"] == 0) or ($prow["perheid2"] == -1)) {

							if ($prow["perheid2"] == 0 or $prow["perheid2"] == -1) {
								$pklisa = " and (perheid = '$prow[tunnus]' or perheid2 = '$prow[tunnus]')";
							}
							elseif ($prow["perheid"] == 0) {
								$pklisa = " and perheid2 = '$prow[perheid2]'";
							}
							else {
								$pklisa = " and (perheid = '$prow[perheid]' or perheid2 = '$prow[perheid]')";
							}

							$query = "	SELECT count(*), count(*)
										FROM tilausrivi use index (yhtio_otunnus)
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus = '$kukarow[kesken]'
										$pklisa
										and tyyppi != 'D'";
							$pkres = pupe_query($query);
							$pkrow = mysql_fetch_array($pkres);

							if ($prow["perheid2"] == 0 or $prow["perheid2"] == -1) {
								$query  = "	SELECT tuoteperhe.tunnus
											FROM tuoteperhe
											WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
											and tuoteperhe.isatuoteno 	= '$prow[tuoteno]'
											and tuoteperhe.tyyppi 		= 'L'";
								$lisaresult = pupe_query($query);

								$lisays = mysql_num_rows($lisaresult)+1;
							}
							else {
								$lisays = 0;
							}

							$pkrow[1] += $lisays;

							if ($prow["perheid2"] == -1) {
								$pkrow[1]++;
							}

							$pknum = $pkrow[0] + $pkrow[1];
							$borderlask = $pkrow[1];


							echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$lask</td>";
						}
						elseif ($prow["perheid"] == 0 and $prow["perheid2"] == 0) {
							echo "<td rowspan = '2' valign='top'>$lask</td>";

							$borderlask		= 0;
							$pknum			= 0;
						}

						$lask--;
						$classlisa = "";

						if ($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
							$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
							$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

							$borderlask--;
						}
						elseif ($borderlask == $pkrow[1] and $pkrow[1] > 0) {
							$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
							$class    .= " style='border-top: 1px solid;' ";

							$borderlask--;
						}
						elseif ($borderlask == 1) {
							$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
							$class    .= " style='font-style:italic; ' ";

							$borderlask--;
						}
						elseif ($borderlask > 0 and $borderlask < $pknum) {
							$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
							$class    .= " style='font-style:italic;' ";
							$borderlask--;
						}

						if ($toim != "HAAMU") {
							if ($toim_nimitykset == "HE") {
								echo "<td valign='top' $class>{$prow["toim_tuoteno"]} {$prow["toim_nimitys"]}</td>";
							}
							else {
								echo "<td valign='top' $class>".t_tuotteen_avainsanat($prow, 'nimitys')."</td>";
							}
						}
						else {
							echo "<td valign='top' $class>{$prow["kommentti"]}</td>";
						}

						echo "<td valign='top' $class>$prow[paikka]</td>";

						$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$prow[tuoteno]'";
						$sarjares = pupe_query($query);
						$sarjarow = mysql_fetch_array($sarjares);

						// tehd‰‰n pop-up divi jos keikalla on kommentti...
						if ($prow["tunnus"] != "") {
							list ($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($prow["tuoteno"]);
							$pop_yks = t_avainsana("Y", "", "and avainsana.selite='$prow[yksikko]'", "", "", "selite");

							echo "<div id='div_$prow[tunnus]' class='popup' style='width: 400px;'>";
							echo "<ul>";
							echo "<li>".t("Saldo").": $saldo $pop_yks</li><li>".t("Hyllyss‰").": $hyllyssa $pop_yks</li><li>".t("Myyt‰viss‰").": $myytavissa $pop_yks</li>";
							echo "<li>".t("Tilattu").": $prow[tilattu] $pop_yks</li><li>".t("Varattu").": $prow[varattukpl] $pop_yks</li>";
							echo "<li>".t("Keskihinta").": $prow[keskihinta] $prow[valuutta]</li><li>".t("Ostohinta").": $prow[ostohinta] $prow[valuutta]</li>";
							echo "</ul>";
							echo "</div>";

							if ($toim != "HAAMU") {
								echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&toim_kutsu=RIVISYOTTO&lopetus=$tilost_lopetus//from=LASKUTATILAUS' class='tooltip' id='$prow[tunnus]'>$prow[tuoteno]</a>";
							}
							else {
								echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&lopetus=$tilost_lopetus//from=LASKUTATILAUS' class='tooltip' id='$prow[tunnus]'>$prow[tuoteno]</a>";
							}

						}
						else {
							if ($toim != "HAAMU") {
								echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&toim_kutsu=RIVISYOTTO&lopetus=$tilost_lopetus//from=LASKUTATILAUS'>$prow[tuoteno]</a>";
							}
							else {
								echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&lopetus=$tilost_lopetus//from=LASKUTATILAUS'>$prow[tuoteno]</a>";
							}
						}

						if ($sarjarow["sarjanumeroseuranta"] != "") {
							$query = "	SELECT count(*) kpl
										from sarjanumeroseuranta
										where yhtio='$kukarow[yhtio]' and tuoteno='$prow[tuoteno]' and ostorivitunnus='$prow[tunnus]'";
							$sarjares = pupe_query($query);
							$sarjarow = mysql_fetch_array($sarjares);

							if ($sarjarow["kpl"] == abs($prow["varattukpl"])) {
								echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&ostorivitunnus=$prow[tunnus]&from=riviosto&lopetus=$tilost_lopetus//from=LASKUTATILAUS' style='color:#00FF00;'>".t("S:nro ok")."</font></a>)";
							}
							else {
								echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&ostorivitunnus=$prow[tunnus]&from=riviosto&lopetus=$tilost_lopetus//from=LASKUTATILAUS'>".t("S:nro")."</a>)";
							}
						}

						if ($yhtiorow["ostoera_pyoristys"] == "K") {
							// haetaan tuotteen toimittajan takaata pakkauskoko tai jotain
							// Otetaan $trow laajemmin, tuotteen_toimittaja tiedot ja 2 avainsanaa
							$query = "	SELECT tuote.tunnus, tt.toim_tuoteno, tt.osto_era, ta1.selite p2,ta1.selitetark p2s, ta2.selite p3,ta2.selitetark p3s
										FROM tuote
										JOIN tuotteen_toimittajat as tt on (tt.yhtio = tuote.yhtio and tuote.tuoteno = tt.tuoteno and tt.liitostunnus = '$laskurow[liitostunnus]')
										LEFT JOIN tuotteen_avainsanat as ta1 on (ta1.yhtio=tuote.yhtio and ta1.tuoteno=tuote.tuoteno and ta1.laji='pakkauskoko2' )
										LEFT JOIN tuotteen_avainsanat as ta2 on (ta2.yhtio=tuote.yhtio and ta2.tuoteno=tuote.tuoteno and ta2.laji='pakkauskoko3')
										WHERE tuote.yhtio='{$kukarow["yhtio"]}'
										AND tuote.tuoteno = '{$prow["tuoteno"]}'";
							$ttresult = pupe_query($query);
							$ttrow = mysql_fetch_assoc($ttresult);
						}

						echo "</td>";

						echo "<td valign='top' class='tooltip' id='$divnolla'>$prow[toim_tuoteno]";

						if ($ttrow["p2"] !="" or $ttrow["p3"] !="") {
							echo "<br><img src='$palvelin2/pics/lullacons/info.png'>";
							echo "<div id='div_$divnolla' class='popup' style='width: 600px;'>";
							// t‰h‰n pakkauskoot..
							echo "<ul><li>".t("Oletuskoko").": {$ttrow["osto_era"]}</li>";
							if ($ttrow["p2"] !="") {
								echo "<li>".t("pakkaus2").": {$ttrow["p2"]} {$ttrow["p2s"]}</li>";
							}
							if ($ttrow["p3"] !="") {
								echo "<li>".t("pakkaus3").": {$ttrow["p3"]} {$ttrow["p3s"]}</li>";
							}
							echo "</ul></div>";
						}
						echo "</td>";
						echo "<td valign='top' $class align='right'>".($prow["tilattu"]*1)."<br>".($prow["tilattu_ulk"]*1)."</td>";
						echo "<td valign='top' $class align='right'>".hintapyoristys($prow["hinta"])."</td>";

						$alespan = 8;
						$backspan1 = 1;
						$backspan2 = 5;

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							echo "<td valign='top' $class align='right'>".((float) $prow["ale{$alepostfix}"])."</td>";
							$alespan++;
							$backspan1++;
							$backspan2++;
						}

						echo "<td valign='top' $class align='right'>".((float) $prow["alv"])."</td>";
						echo "<td valign='top' $class align='right'>".hintapyoristys($prow["rivihinta"])."</td>";
						echo "<td valign='top' $classlisa align='right'>$prow[valuutta]</td>";

						if ($prow["uusiotunnus"] == 0) {

							// Tarkistetaan tilausrivi
							if ($toim != "HAAMU") {
								require("tarkistarivi_ostotilaus.inc");
							}


							echo "	<td valign='top' class='back' nowrap>
									<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
									<input type='hidden' name='toim' 				value = '$toim'>
									<input type='hidden' name='lopetus' 			value = '$lopetus'>
									<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
									<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
									<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
									<input type='hidden' name='rivitunnus' 			value = '$prow[tunnus]'>
									<input type='hidden' name='tee' 				value = 'PV'>
									<input type='Submit' value='".t("Muuta")."'>
									</td></form>";

							echo "	<td valign='top' class='back' nowrap>
									<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
									<input type='hidden' name='toim' 				value = '$toim'>
									<input type='hidden' name='lopetus' 			value = '$lopetus'>
									<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
									<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
									<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
									<input type='hidden' name='rivitunnus' 			value = '$prow[tunnus]'>
									<input type='hidden' name='tee' 				value = 'POISTA_RIVI'>
									<input type='Submit' value='".t("Poista")."'>
									</td></form>";

							if ($saako_hyvaksya > 0) {
								echo "<td valign='top' class='back'>
										<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
										<input type='hidden' name='toim' 				value = '$toim'>
										<input type='hidden' name='lopetus' 			value = '$lopetus'>
										<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
										<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
										<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
										<input type='hidden' name='rivitunnus' 			value = '$prow[tunnus]'>
										<input type='hidden' name='tee' 				value = 'OOKOOAA'>
										<input type='Submit' value='".t("Hyv‰ksy")."'>
										</form></td> ";
							}

							if ($varaosavirhe != '') {
								echo "<td valign='top' class='back'>$varaosavirhe</td>";
							}

							if ($varaosavirhe == "") {
								//Tutkitaan tuotteiden lis‰varusteita
								$query  = "	SELECT *
											FROM tuoteperhe
											JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
											WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
											and tuoteperhe.isatuoteno 	= '$prow[tuoteno]'
											and tuoteperhe.tyyppi 		= 'L'
											order by tuoteperhe.tuoteno";
								$lisaresult = pupe_query($query);

								if (mysql_num_rows($lisaresult) > 0 and $prow["perheid2"] == -1) {

									echo "</tr>";

									echo "	<form name='tilaus' method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php' autocomplete='off'>
											<input type='hidden' name='toim' 			value = '$toim'>
											<input type='hidden' name='lopetus' 		value = '$lopetus'>
											<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
											<input type='hidden' name='toim_nimitykset' value = '$toim_nimitykset'>
											<input type='hidden' name='tee' 			value = 'TI'>
											<input type='hidden' name='lisavarusteita' 	value = 'ON'>
											<input type='hidden' name='perheid2'	 	value = '$prow[tunnus]'>";

									if ($alv=='') $alv=$laskurow['alv'];
									$lask = 0;
									$borderlask--;

									while ($xprow = mysql_fetch_array($lisaresult)) {
										echo "<tr><td class='spec'>".t_tuotteen_avainsanat($xprow, 'nimitys')."</td><td></td>";
										echo "<td><input type='hidden' name='tuoteno_array[$xprow[tuoteno]]' value='$xprow[tuoteno]'>$xprow[tuoteno]</td>";
										echo "<td></td>";
										echo "<td><input type='text' name='kpl_array[$xprow[tuoteno]]' size='5' maxlength='5'></td>
												<td><input type='text' name='hinta_array[$xprow[tuoteno]]' size='5' maxlength='12'></td>";

										for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
											echo "<td><input type='text' name='ale_array{$alepostfix}[$xprow[tuoteno]]' size='5' maxlength='6'></td>";
										}

										echo "<td>".alv_popup_oletus('alv',$alv)."</td>
												<td style='border-right: 1px solid;'></td>";
										$lask++;
										$borderlask--;

										if ($lask == mysql_num_rows($lisaresult)) {
											echo "<td class='back'><input type='submit' value='".t("Lis‰‰")."'></td>";
											echo "</form>";
										}
										echo "</tr>";
									}
								}
								elseif (mysql_num_rows($lisaresult) > 0 and $prow["perheid2"] != -1) {
									echo "	<td class='back'>
											<form name='tilaus' method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php' autocomplete='off'>
											<input type='hidden' name='toim' 				value = '$toim'>
											<input type='hidden' name='lopetus' 			value = '$lopetus'>
											<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
											<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
											<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
											<input type='hidden' name='tee' 				value = 'LISLISAV'>
											<input type='hidden' name='rivitunnus' 			value = '$prow[tunnus]'>
											<input type='submit' value='".t("Lis‰‰ lis‰varusteita tuotteelle")."'>
											</form>
											</td>";
									echo "</tr>";
								}
							}
							else {
								echo "</tr>";
							}
						}
						else {
							echo "<td class='back'>".t("Lukittu")."</td>";
							$eimitatoi = "EISAA";
							echo "</tr>";
						}

						echo "<tr>";

						if ($borderlask == 0 and $pknum > 1) {
							$kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
							$kommclass2 = " style='border-bottom: 1px solid;'";
						}
						elseif ($pknum > 0) {
							$kommclass1 = " style='border-right: 1px solid;'";
							$kommclass2 = "";
						}
						else {
							$kommclass1 = "";
							$kommclass2 = "";
						}

						if ($prow["jaksotettu"] == 1) {
							echo "<td $kommclass2><font style = 'color: #00FF00;'>".t("Vahvistettu toimitusaika").": ".tv1dateconv($prow["toimaika"])."</font></td>";
						}
						else {

							if ($paivitetty_ok == "YES") {
								echo "<td $kommclass2>".t("Toimitusaika").": ".tv1dateconv($ehdotus_pvm)."</td>";
							}
							else {
								echo "<td $kommclass2>".t("Toimitusaika").": ".tv1dateconv($prow["toimaika"])."</td>";
							}
						}

						echo "<td colspan='$alespan' $kommclass1>";
						if (trim($prow["kommentti"]) != "") echo t("Kommentti").": $prow[kommentti]";
						echo "</td></tr>";
					}
				}

				if ($toim == "HAAMU") {
					$kopiotoim = "HAAMU";
				}
				else {
					$kopiotoim = "OSTO";
				}

				echo "	<tr>
						<th colspan='2' nowrap>".t("N‰yt‰ ostotilaus").":</th>
						<td colspan='2' nowrap>
						<form name='valmis' action='tulostakopio.php' method='post' name='tulostaform_tosto' id='tulostaform_tosto' class='multisubmit'>
						<input type='hidden' name='otunnus' 		value = '$tilausnumero'>
						<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
						<input type='hidden' name='toim_nimitykset' value = '$toim_nimitykset'>
						<input type='hidden' name='toimittajaid' 	value = '$laskurow[liitostunnus]'>
						<input type='hidden' name='toim' 			value = '$kopiotoim'>
						<input type='hidden' name='tee' 			value = 'TULOSTA'>
						<input type='hidden' name='lopetus' 		value = '$tilost_lopetus//from=LASKUTATILAUS'>
						<input type='submit' value='".t("N‰yt‰")."' onClick=\"js_openFormInNewWindow('tulostaform_tosto', 'tulosta_osto'); return false;\">
						<input type='submit' value='".t("Tulosta")."' onClick=\"js_openFormInNewWindow('tulostaform_tosto', 'samewindow'); return false;\">
						</form>
						</td>";

				if ($laskurow['erikoisale'] > 0) {
					$_colspan = $backspan1 - 1;
					echo "<td>
						<td class='back' colspan='$_colspan'></td>
						<td colspan='3' class='spec'>".t('Tilauksen arvo yhteens‰')."</td>
						<td align='right' class='spec'>".sprintf("%.2f", $yhteensa)."</td>
						<td class='spec'>$laskurow[valkoodi]</td>
						</td>
						</tr>";
					$_colspan = $backspan1 + 4;
					echo "<tr>
						<td class='back' colspan='$_colspan'></td>
						<td colspan='3' class='spec'>".t('Erikoisalennus')." ".$laskurow['erikoisale']."%</td>
						<td align='right' class='spec'>".sprintf("%.2f", $erikoisale_summa)."</td>
						<td class='spec'>$laskurow[valkoodi]</td>
						</tr>";
					echo "<tr>
						<td class='back' colspan='$_colspan'></td>
						<td colspan='3' class='spec'>".t("Tilauksen arvo").":</td>
						<td align='right' class='spec'>".sprintf("%.2f", $yhteensa + $erikoisale_summa)."</td>
						<td class='spec'>$laskurow[valkoodi]</td>
						</tr>";
				}
				else {
					echo "<td class='back' colspan='$backspan1'></td>
						<td colspan='3' class='spec'>".t("Tilauksen arvo").":</td>
						<td align='right' class='spec'>".sprintf("%.2f", $yhteensa)."</td>
						<td class='spec'>$laskurow[valkoodi]</td>
						</tr>";
				}

				echo "	<tr>
						<td class='back' colspan='$backspan2'></td>
						<td colspan='3' class='spec'>".t("Tilauksen paino").":</td>
						<td align='right' class='spec'>".sprintf("%.2f", $paino_yhteensa)."</td>
						<td class='spec'>kg</td>
						</tr>";

				echo "</table>";
			}

			// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
			if ($yhteensa != '' and abs($yhteensa) > 0) {
				if (abs($yhteensa) > 9999999999.99) {
					echo "<font class='error'>".t("VIRHE: liian iso loppusumma")."!</font><br>";
					$tilausok++;
				}
			}

			echo "<br><br><table width='100%'><tr>";

			if ($rivienmaara > 0 and $laskurow["liitostunnus"] != '' and $tilausok == 0) {
					echo "	<td class='back'>
							<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
							<input type='hidden' name='toim' 				value = '$toim'>
							<input type='hidden' name='lopetus' 			value = '$lopetus'>
							<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
							<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
							<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
							<input type='hidden' name='toimittajaid' 		value = '$laskurow[liitostunnus]'>
							<input type='hidden' name='tee' 				value = 'valmis'>
							<input type='Submit' value='".t("Tilaus valmis")."'>
							</form>
							</td>";

				if ($toim != "HAAMU") {
					echo "	<td class='back''>
							<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
							<input type='hidden' name='toim' 				value = '$toim'>
							<input type='hidden' name='lopetus' 			value = '$lopetus'>
							<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
							<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
							<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
							<input type='hidden' name='tee' 				value = 'vahvista'>
							<input type='Submit' value='".t("Vahvista toimitus")."'>
							</form>
							</td>";
				}

			}

			if ($eimitatoi != "EISAA" and $kukarow["mitatoi_tilauksia"] == "") {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
							function verify(){
								msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';

								if (confirm(msg)) {
									return true;
								}
								else {
									skippaa_tama_submitti = true;
									return false;
								}
							}
					</SCRIPT>";
				echo "	<td class='back' align='right'>
						<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php' onSubmit = 'return verify()'>
						<input type='hidden' name='toim' 				value = '$toim'>
						<input type='hidden' name='lopetus' 			value = '$lopetus'>
						<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
						<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
						<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
						<input type='hidden' name='tee' 				value = 'poista'>
						<input type='Submit' value='*".t("Mit‰tˆi koko tilaus")."*'>
						</form>
						</td>";

			}
			elseif ($laskurow["tila"] == 'O') {
				echo "	<td class='back' align='right'>
						<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
						<input type='hidden' name='toim' 				value = '$toim'>
						<input type='hidden' name='lopetus' 			value = '$lopetus'>
						<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
						<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
						<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
						<input type='hidden' name='tee' 				value = 'poista_kohdistamattomat'>
						<input type='Submit' value='*".t("Mit‰tˆi kohdistamattomat rivit")."*'>
						</form>
						</td>";

			}

			echo "</tr></table>";
		}

		if (!isset($nayta_pdf) and $tee == "") {
			require("otsik_ostotilaus.inc");
		}
	}

	require("../inc/footer.inc");
