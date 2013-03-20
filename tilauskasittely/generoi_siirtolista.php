<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>",t("Luo siirtolista tuotepaikkojen hälytysrajojen perusteella"),"</font><hr /><br />";

	// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
	$org_rajaus = $abcrajaus;
	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";
	if (!isset($keraysvyohyke)) $keraysvyohyke = array();

	list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

	// Tällä ollaan, jos olemme syöttämässä tiedostoa ja muuta
	echo "<form name = 'valinta' method='post'>
			<input type='hidden' name='tee' value='M'>
			<table>";

	echo "<tr><th>",t("Lähdevarasto, eli varasto josta kerätään"),":</th>";
	echo "<td colspan='4'>";

	$query  = "	SELECT tunnus, nimitys, maa
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel = '';
		if (is_array($lahdevarastot) and in_array($varow['tunnus'], $lahdevarastot)) $sel = 'checked';

		$varastomaa = '';
		if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
			$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
		}

		echo "<input type='checkbox' name='lahdevarastot[]' value='{$varow['tunnus']}' {$sel} />{$varow['nimitys']} {$varastomaa}<br />";
	}

	echo "</td></tr>";

	echo "<tr><th>",t("Kohdevarasto, eli varasto jonne lähetetään"),":</th>";
	echo "<td colspan='4'><select name='kohdevarasto'><option value=''>",t("Valitse"),"</option>";

	mysql_data_seek($vares, 0);

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel = '';
		if ($varow['tunnus'] == $kohdevarasto) $sel = 'selected';

		$varastomaa = '';
		if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
			$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
		}

		echo "<option value='{$varow['tunnus']}' {$sel}>{$varastomaa} {$varow['nimitys']}</option>";
	}

	echo "</select></td></tr>";

	echo "<tr><th>",t("Lisärajaukset"),"</th><td>";

	$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
	$monivalintalaatikot_normaali = array();

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";

	if ($yhtiorow['kerayserat'] == 'K') {

		$query = "	SELECT nimitys, tunnus
					FROM keraysvyohyke
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$keraysvyohyke_res = pupe_query($query);

		if (mysql_num_rows($keraysvyohyke_res) > 0) {

			echo "<tr><th>",t("Keräysvyöhyke"),"</th>";
			echo "<td>";

			echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";

			while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {

				$chk = in_array($keraysvyohyke_row['tunnus'], $keraysvyohyke) ? " checked" : "";

				echo "<input type='checkbox' name='keraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$chk} /> {$keraysvyohyke_row['nimitys']}<br />";
			}

			echo "</td></tr>";
		}
	}

	echo "<tr><th>",t("Toimittaja"),"</th><td><input type='text' size='20' name='toimittaja' value='{$toimittaja}'></td></tr>";

	echo "<tr><th>",t("ABC-luokkarajaus ja rajausperuste"),"</th><td>";

	echo "<select name='abcrajaus' onchange='submit()'>";
	echo "<option  value=''>",t("Valitse"),"</option>";

	$teksti = "";
	for ($i = 0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

		echo "<option  value='{$i}##TM' {$selabc}>",t("Myynti"),": {$ryhmanimet[$i]} {$teksti}</option>";
	}

	$teksti = "";
	for ($i = 0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

		echo "<option  value='{$i}##TK' {$selabc}>",t("Myyntikate"),": {$ryhmanimet[$i]} {$teksti}</option>";
	}

	$teksti = "";
	for ($i = 0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

		echo "<option  value='{$i}##TR' {$selabc}>",t("Myyntirivit"),": {$ryhmanimet[$i]} {$teksti}</option>";
	}

	$teksti = "";
	for ($i = 0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

		echo "<option  value='{$i}##TP' {$selabc}>",t("Myyntikappaleet"),": {$ryhmanimet[$i]} {$teksti}</option>";
	}

	echo "</select>";

	echo "<tr>";
	echo "<th>",t("Toimitustapa"),"</th><td>";

	$query = "	SELECT tunnus, selite
				FROM toimitustapa
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY jarjestys, selite";
	$tresult = pupe_query($query);
	echo "<select name='valittu_toimitustapa'>";

	while ($row = mysql_fetch_assoc($tresult)) {
		echo "<option value='{$row['selite']}' {$sel}>",t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV"),"</option>";
	}
	echo "</select>";


	echo "</td></tr>";

	if ($kesken == "X") {
		$c = "checked";
	}
	else {
		$c = "";
	}

	echo "<tr><th>",t("Jätä siirtolista kesken"),":</th><td><input type='checkbox' name = 'kesken' value='X' {$c}></td>";
	echo "<tr><th>",t("Rivejä per siirtolista (tyhjä = 20)"),":</th><td><input type='text' size='8' value='{$olliriveja}' name='olliriveja'></td>";
	echo "</table><br><input type = 'submit' name = 'generoi' value = '",t("Generoi siirtolista"),"'></form>";

	if ($tee == 'M' and isset($generoi)) {

		echo "<br /><br />";

		$kohdevarasto = (int) $kohdevarasto;

		if ($kohdevarasto > 0 and count($lahdevarastot) > 0) {

			$abcjoin = "";

			if ($abcrajaus != "") {
				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio AND
							abc_aputaulu.tuoteno = tuote.tuoteno AND
							abc_aputaulu.tyyppi = '{$abcrajaustapa}' AND
							(abc_aputaulu.luokka <= '{$abcrajaus}' OR abc_aputaulu.luokka_osasto <= '{$abcrajaus}' OR abc_aputaulu.luokka_try <= '{$abcrajaus}'))";
			}

			if (count($keraysvyohyke) > 1) {

				// ensimmäinen alkio on 'default' ja se otetaan pois
				array_shift($keraysvyohyke);

				$keraysvyohykelisa = "	JOIN varaston_hyllypaikat AS vh ON (
											vh.yhtio = tuotepaikat.yhtio AND
											vh.hyllyalue = tuotepaikat.hyllyalue AND
											vh.hyllynro = tuotepaikat.hyllynro AND
											vh.hyllytaso = tuotepaikat.hyllytaso AND
											vh.hyllyvali = tuotepaikat.hyllyvali AND
											vh.keraysvyohyke IN (".implode(",", $keraysvyohyke)."))
										JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)";
			}

			if ($toimittaja != "") {
				$query = "	SELECT GROUP_CONCAT(DISTINCT CONCAT('\'',tuoteno,'\'')) tuotteet
							FROM tuotteen_toimittajat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND toimittaja = '{$toimittaja}'";
				$result = pupe_query($query);
				$toimirow = mysql_fetch_assoc($result);

				if ($toimirow["tuotteet"] != "") {
					$lisa .= " AND tuote.tuoteno IN ({$toimirow["tuotteet"]}) ";
				}
				else {
					echo "<font class='error'>",t("Toimittajaa ei löytynyt"),"! ",t("Ajetaan ajo ilman rajausta"),"!</font><br><br>";
				}
			}

			$query = "SELECT * FROM varastopaikat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kohdevarasto}'";
			$result = pupe_query($query);
			$varow = mysql_fetch_assoc($result);

			$kohdepaikkalisa = "";
			$varastonsisainensiirto = FALSE;

			// Siirretäänkö varaston sisällä tai osittain varaston sisällä?
			// Tässä tapauksessa VAIN tuotteen oletuspaikka kelpaa kohdepaikaksi
			if (in_array($kohdevarasto, $lahdevarastot)) {
				$kohdepaikkalisa = " AND tuotepaikat.oletus != '' ";
				$varastonsisainensiirto = TRUE;
			}

			// Katotaan kohdepaikkojen tarvetta
			$query = "	SELECT tuotepaikat.*,
						tuotepaikat.halytysraja,
						if (tuotepaikat.tilausmaara = 0, 1, tuotepaikat.tilausmaara) tilausmaara,
						CONCAT_WS('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) hyllypaikka,
						tuote.nimitys
						FROM tuotepaikat
						JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno {$lisa})
						{$abcjoin}
						{$keraysvyohykelisa}
						WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
						AND CONCAT(RPAD(UPPER('{$varow['alkuhyllyalue']}'),  5, '0'),LPAD(UPPER('{$varow['alkuhyllynro']}'),  5, '0')) <= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
						AND CONCAT(RPAD(UPPER('{$varow['loppuhyllyalue']}'), 5, '0'),LPAD(UPPER('{$varow['loppuhyllynro']}'), 5, '0')) >= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
						AND tuotepaikat.halytysraja > 0
						{$kohdepaikkalisa}
						ORDER BY tuotepaikat.tuoteno";
			$resultti = pupe_query($query);

			if ((int) $olliriveja == 0 or $olliriveja == '') {
				$olliriveja = 20;
			}

			//	Otetaan luodut otsikot talteen
			$otsikot = array();

			// tehdään jokaiselle valitulle lahdevarastolle erikseen
			foreach ($lahdevarastot as $lahdevarasto) {
				//	Varmistetaan että aloitetaan aina uusi otsikko uudelle varastolle
				$tehtyriveja = 0;

				// mennään aina varmasti alkuun
				mysql_data_seek($resultti, 0);

				while ($pairow = mysql_fetch_assoc($resultti)) {

					// katotaan paljonko tälle PAIKALLE on menossa
					$query = "	SELECT SUM(tilausrivi.varattu) varattu
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
								JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND lasku.clearing = '{$kohdevarasto}')
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.tuoteno = '{$pairow['tuoteno']}'
								AND tilausrivi.varattu > 0
								AND tilausrivi.tyyppi  = 'G'
								AND tilausrivin_lisatiedot.kohde_hyllyalue = '{$pairow['hyllyalue']}'
								AND tilausrivin_lisatiedot.kohde_hyllynro  = '{$pairow['hyllynro']}'
								AND tilausrivin_lisatiedot.kohde_hyllyvali = '{$pairow['hyllyvali']}'
								AND tilausrivin_lisatiedot.kohde_hyllytaso = '{$pairow['hyllytaso']}'";
					$vanres = pupe_query($query);
					$vanrow_paikalle = mysql_fetch_assoc($vanres);

					$menossa_paikalle = (float) $vanrow_paikalle["varattu"];

					// katotaan paljonko VARASTOON on menossa
					$query = "	SELECT SUM(tilausrivi.varattu) varattu
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
								JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND lasku.clearing = '$kohdevarasto')
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.tuoteno = '{$pairow['tuoteno']}'
								AND tilausrivi.varattu > 0
								AND tilausrivi.tyyppi  = 'G'
								AND tilausrivin_lisatiedot.kohde_hyllyalue = ''
								AND tilausrivin_lisatiedot.kohde_hyllynro  = ''
								AND tilausrivin_lisatiedot.kohde_hyllyvali = ''
								AND tilausrivin_lisatiedot.kohde_hyllytaso = ''";
					$vanres = pupe_query($query);
					$vanrow_varastoon = mysql_fetch_assoc($vanres);

					$menossa_varastoon = (float) $vanrow_varastoon["varattu"];

					// Kohdepaikan myytävissämäärä
					list( , , $saldo_myytavissa_kohde) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $kohdevarasto, '', $pairow["hyllyalue"], $pairow["hyllynro"], $pairow["hyllyvali"], $pairow["hyllytaso"]);

					$tarve_kohdevarasto = $pairow['halytysraja']-$saldo_myytavissa_kohde-$menossa_paikalle-$menossa_varastoon;

					if ($tarve_kohdevarasto > 0) {

						if ($tarve_kohdevarasto <= $pairow['tilausmaara']) {
							$tarve_kohdevarasto = $pairow['tilausmaara'];
						}
						else {
							$kokonaisluku = round($tarve_kohdevarasto / $pairow['tilausmaara']);

							$test = $kokonaisluku * $pairow['tilausmaara'];

							if ($tarve_kohdevarasto > $test) {
								$test = ($kokonaisluku + 1) * $pairow['tilausmaara'];
							}

							$tarve_kohdevarasto = (float) $test;
						}
					}

					if ($tarve_kohdevarasto <= 0) {
						continue;
					}

					// Lähdevaraston myytävissämäärä
					list( , , $saldo_myytavissa_lahde) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $lahdevarasto);

					// jos lähdevarasto on sama kuin kohdevarasto, niin silloin kohdepaikka on aina oletupaikka, joten poistetaan sen myytävissämäärä lähdepuolelta
					if ($kohdevarasto == $lahdevarasto) {
						$saldo_myytavissa_lahde = (float) $saldo_myytavissa_lahde - $saldo_myytavissa_kohde;
					}
					else {
						$saldo_myytavissa_lahde = (float) $saldo_myytavissa_lahde;
					}

					#echo "TUOTENO: $kala $pairow[tuoteno]<br>";
					#echo "MENOSSA_PAIKALLE: $menossa_paikalle<br>";
					#echo "MENOSSA_VARASTOON: $menossa_varastoon<br>";
					#echo "MYYTAVISSÄ_KOHDE: $saldo_myytavissa_kohde<br>";
					#echo "HÄLYRAJA_KOHDE: $pairow[halytysraja]<br>";
					#echo "TILAUSMÄÄRÄ_KOHDE: $pairow[tilausmaara]<br>";
					#echo "TARVE: $tarve_kohdevarasto<br>";
					#echo "MYYTAVISSÄ_LÄHDE: $saldo_myytavissa_lahde<br><br>";

					if ($saldo_myytavissa_lahde > 0 and $tarve_kohdevarasto > 0) {

						// Jos tarve on suurempi kuin saatavilla oleva määrä
						if ($tarve_kohdevarasto >= $saldo_myytavissa_lahde) {
							if ($saldo_myytavissa_lahde == 1) {
								$siirretaan = $saldo_myytavissa_lahde;
							}
							else {
								$siirretaan = floor($saldo_myytavissa_lahde / 2);
							}
						}
						else {
							$siirretaan = $tarve_kohdevarasto;
						}

						if ($siirretaan > 0) {

							//	Onko meillä jo otsikko vai pitääkö tehdä uusi?
							if ($tehtyriveja == 0 or $tehtyriveja == (int) $olliriveja+1) {

								// Nollataan kun tehdään uusi otsikko
								$tehtyriveja = 0;

								$jatka = "kala";

								$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
								$delresult = pupe_query($query);

								$kukarow["kesken"] = 0;

								$tilausnumero	= 0;
								$clearing 		= $kohdevarasto;
								$chn 			= 'GEN'; // tällä erotellaan "tulosta siirtolista"-kohdassa generoidut ja käsin tehdyt siirtolistat
								$toimpp 		= $kerpp = date("j");
								$toimkk 		= $kerkk = date("n");
								$toimvv 		= $kervv = date("Y");
								$comments 		= $kukarow["nimi"]." ".t("Generoi hälytysrajojen perusteella");
								$viesti 		= $kukarow["nimi"]." ".t("Generoi hälytysrajojen perusteella");
								$varasto 		= $lahdevarasto;
								$toimitustapa 	= $valittu_toimitustapa;
								$toim			= "SIIRTOLISTA";

								require ("otsik_siirtolista.inc");

								$query = "	SELECT *
											FROM lasku
											WHERE tunnus = '{$kukarow['kesken']}'";
								$aresult = pupe_query($query);

								if (mysql_num_rows($aresult) == 0) {
									echo "<font class='message'>",t("VIRHE: Tilausta ei löydy"),"!<br /><br /></font>";
									exit;
								}

								$query = "	SELECT nimitys
											FROM varastopaikat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus  = '{$lahdevarasto}'";
								$varres = pupe_query($query);
								$varrow = mysql_fetch_assoc($varres);

								echo "<br /><font class='message'>",t("Tehtiin siirtolistalle otsikko %s lähdevarasto on %s", $kieli, $kukarow["kesken"], $varrow["nimitys"]),"</font><br />";

								//	Otetaan luotu otsikko talteen
								$otsikot[] = $kukarow["kesken"];

								$laskurow = mysql_fetch_assoc($aresult);
							}

							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno = '{$pairow['tuoteno']}'
										AND yhtio = '{$kukarow['yhtio']}'";
							$rarresult = pupe_query($query);

							if (mysql_num_rows($rarresult) == 1) {
								$trow = mysql_fetch_assoc($rarresult);
								$toimaika 			= $laskurow["toimaika"];
								$kerayspvm			= $laskurow["kerayspvm"];
								$tuoteno			= $pairow["tuoteno"];
								$kpl				= $siirretaan;
								$jtkielto 			= $laskurow['jtkielto'];
								$varasto			= $lahdevarasto;
								$hinta 				= "";
								$netto 				= "";
								$var				= "";
								$korvaavakielto		= 1;
								$perhekielto		= 1;
								$orvoteikiinnosta	= "EITOD";

								// Tallennetaan riville minne se on menossa
								$kohde_alue 	= $pairow["hyllyalue"];
								$kohde_nro 		= $pairow["hyllynro"];
								$kohde_vali 	= $pairow["hyllyvali"];
								$kohde_taso 	= $pairow["hyllytaso"];

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									${'ale'.$alepostfix} = "";
								}

								require ('lisaarivi.inc');

								$tuoteno	= '';
								$kpl		= '';
								$hinta		= '';
								$alv		= 'X';
								$var		= '';
								$toimaika	= '';
								$kerayspvm	= '';
								$kommentti	= '';

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									${'ale'.$alepostfix} = '';
								}

								$tehtyriveja++;

								echo "<font class='info'>",t("Siirtolistalle lisättiin %s tuotetta %s", "", $siirretaan." ".$trow["yksikko"], $trow["tuoteno"]),"</font><br />";

							}
							else {
								echo t("VIRHE: Tuotetta ei löydy"),"!<br />";
							}
						}
					}
				}
			}

			echo "</table><br />";

			if (count($otsikot) == 0) {
				echo "<font class='error'>",t("Yhtään siirtolistaa ei luotu"),"!</font><br />";
			}
			else {
				echo "<font class='message'>",t("Luotiin %s siirtolistaa", $kieli, count($otsikot)),"</font><br /><br /><br />";

				if ($kesken != "X") {
					foreach ($otsikot as $ots) {
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$ots}'";
						$aresult = pupe_query($query);
						$laskurow = mysql_fetch_assoc($aresult);

						$kukarow["kesken"]	= $laskurow["tunnus"];
						$toim 				= "SIIRTOLISTA";

						require ("tilaus-valmis-siirtolista.inc");
					}
				}
				else {
					echo "<font class='message'>",t("Siirtolistat jätettiin kesken"),"</font><br /><br /><br />";
				}
			}

			$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
			$delresult = pupe_query($query);
		}
		else {
			echo "<font class='error'>",t("Varastonvalinnassa on virhe"),"</font><br />";
		}
	}

	require ("inc/footer.inc");
