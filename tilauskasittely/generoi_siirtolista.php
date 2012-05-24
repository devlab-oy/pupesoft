<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Luo siirtolista tuotepaikkojen hälytysrajojen perusteella")."</font><hr>";

	// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
	$org_rajaus = $abcrajaus;
	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

	list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

	// Tällä ollaan, jos olemme syöttämässä tiedostoa ja muuta
	echo "<form name = 'valinta' method='post'>
			<input type='hidden' name='tee' value='M'>
			<table>";

	echo "<tr><th>".t("Lähdevarasto, eli varasto josta kerätään").":</th>";
	echo "<td colspan='4'>";

	$query  = "	SELECT tunnus, nimitys, maa
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel='';
		if (is_array($lahdevarastot) and in_array($varow['tunnus'], $lahdevarastot)) $sel = 'checked';

		$varastomaa = '';
		if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
			$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
		}

		echo "<input type='checkbox' name='lahdevarastot[]' value='$varow[tunnus]' $sel />$varow[nimitys] $varastomaa<br />";
	}

	echo "</td></tr>";

	echo "<tr><th>".t("Kohdevarasto, eli varasto jonne lähetetään").":</th>";
	echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

	mysql_data_seek($vares, 0);

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel='';
		if ($varow['tunnus']==$kohdevarasto) $sel = 'selected';

		$varastomaa = '';
		if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
			$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
		}

		echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
	}

	echo "</select></td></tr>";

	echo "<tr><th>".t("Lisärajaukset")."</th><td>";

	$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
	$monivalintalaatikot_normaali = array();

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>
		<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='toimittaja' value='$toimittaja'></td></tr>";

	echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

	echo "<select name='abcrajaus' onchange='submit()'>";
	echo "<option  value=''>".t("Valitse")."</option>";

	$teksti = "";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

		echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
	}

	$teksti = "";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

		echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
	}

	$teksti = "";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

		echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
	}

	$teksti = "";
	for ($i=0; $i < count($ryhmaprossat); $i++) {
		$selabc = "";

		if ($i > 0) $teksti = t("ja paremmat");
		if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

		echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
	}

	echo "</select>";

	echo "<tr>";
	echo "<th>".t("Toimitustapa")."</th><td>";

	$query = "	SELECT tunnus, selite
				FROM toimitustapa
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY jarjestys, selite";
	$tresult = pupe_query($query);
	echo "<select name='valittu_toimitustapa'>";

	while ($row = mysql_fetch_assoc($tresult)) {
		echo "<option value='$row[selite]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")."</option>";
	}
	echo "</select>";


	echo "</td></tr>";

	if ($kesken == "X") {
		$c = "checked";
	}
	else {
		$c = "";
	}
	echo "<tr><th>".t("Jätä siirtolista kesken").":</th><td><input type='checkbox' name = 'kesken' value='X' $c></td>";

	echo "<tr><th>".t("Rivejä per siirtolista (tyhjä = 20)").":</th><td><input type='text' size='8' value='$olliriveja' name='olliriveja'></td>";
	echo "</table><br><input type = 'submit' value = '".t("Generoi siirtolista")."'></form>";

	if ($tee == 'M') {

		echo "<br><br>";

		$kohdevarasto = (int) $kohdevarasto;

		if ($kohdevarasto > 0 and count($lahdevarastot) > 0) {

			$abcjoin = "";

			if ($abcrajaus != "") {
				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and
							abc_aputaulu.tuoteno = tuote.tuoteno and
							abc_aputaulu.tyyppi = '$abcrajaustapa' and
							(abc_aputaulu.luokka <= '$abcrajaus' or abc_aputaulu.luokka_osasto <= '$abcrajaus' or abc_aputaulu.luokka_try <= '$abcrajaus'))";
			}

			if ($toimittaja != "") {
				$query = "	SELECT group_concat(distinct concat('\'',tuoteno,'\'')) tuotteet
							from tuotteen_toimittajat
							where yhtio = '$kukarow[yhtio]'
							and toimittaja = '$toimittaja'";
				$result = pupe_query($query);
				$toimirow = mysql_fetch_assoc($result);

				if ($toimirow["tuotteet"] != "") {
					$lisa .= " and tuote.tuoteno in ({$toimirow["tuotteet"]}) ";
				}
				else {
					echo "<font class='error'>".t("Toimittajaa ei löytynyt")."! ".t("Ajetaan ajo ilman rajausta")."!</font><br><br>";
				}
			}

			$query = "SELECT * FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kohdevarasto'";
			$result = pupe_query($query);
			$varow = mysql_fetch_assoc($result);

			$kohdepaikkalisa = "";
			$varastonsisainensiirto = FALSE;

			// Siirretäänkö varaston sisällä tai osittain varaston sisällä?
			// Tässä tapauksessa VAIN tuotteen oletuspaikka kelpaa kohdepaikaksi
			if (in_array($kohdevarasto, $lahdevarastot)) {
				$kohdepaikkalisa = " and tuotepaikat.oletus != '' ";
				$varastonsisainensiirto = TRUE;
			}

			// Katotaan kohdepaikkojen tarvetta
			$query = "	SELECT tuotepaikat.*, tuotepaikat.halytysraja, concat_ws('-',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka, tuote.nimitys
						FROM tuotepaikat
						JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno $lisa)
						$abcjoin
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and concat(rpad(upper('$varow[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper('$varow[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and tuotepaikat.halytysraja > 0
						and tuotepaikat.tilausmaara > 0
						$kohdepaikkalisa
						ORDER BY tuotepaikat.tuoteno";
			$resultti = pupe_query($query);
			$luku = mysql_num_rows($result);

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
					$query = "	SELECT sum(tilausrivi.varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.clearing = '$kohdevarasto'
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tuoteno = '$pairow[tuoteno]'
								and tilausrivi.varattu > 0
								and tilausrivi.tyyppi  = 'G'
								and tilausrivin_lisatiedot.kohde_hyllyalue = '{$pairow['hyllyalue']}'
								and tilausrivin_lisatiedot.kohde_hyllynro  = '{$pairow['hyllynro']}'
								and tilausrivin_lisatiedot.kohde_hyllyvali = '{$pairow['hyllyvali']}'
								and tilausrivin_lisatiedot.kohde_hyllytaso = '{$pairow['hyllytaso']}'";
					$vanres = pupe_query($query);
					$vanrow_paikalle = mysql_fetch_assoc($vanres);

					$menossa_paikalle = (float) $vanrow_paikalle["varattu"];

					// katotaan paljonko VARASTOON on menossa
					$query = "	SELECT sum(tilausrivi.varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.clearing = '$kohdevarasto'
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tuoteno = '$pairow[tuoteno]'
								and tilausrivi.varattu > 0
								and tilausrivi.tyyppi  = 'G'
								and tilausrivin_lisatiedot.kohde_hyllyalue = ''
								and tilausrivin_lisatiedot.kohde_hyllynro  = ''
								and tilausrivin_lisatiedot.kohde_hyllyvali = ''
								and tilausrivin_lisatiedot.kohde_hyllytaso = ''";
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

								$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
								$delresult = pupe_query($query);

								$kukarow["kesken"] = 0;

								$tilausnumero	= 0;
								$clearing 		= $kohdevarasto;
								$chn 			= 'GEN'; // tällä erotellaan "tulosta siirtolista"-kohdassa generoidut ja käsin tehdyt siirtolistat
								$toimpp 		= $kerpp = date("j");
								$toimkk 		= $kerkk = date("n");
								$toimvv 		= $kervv = date("Y");
								$comments 		= $kukarow["nimi"]." Generoi hälytysrajojen perusteella";
								$viesti 		= $kukarow["nimi"]." Generoi hälytysrajojen perusteella";
								$varasto 		= $lahdevarasto;
								$toimitustapa 	= $valittu_toimitustapa;
								$toim			= "SIIRTOLISTA";

								require ("otsik_siirtolista.inc");

								$query = "	SELECT *
											FROM lasku
											WHERE tunnus = '$kukarow[kesken]'";
								$aresult = pupe_query($query);

								if (mysql_num_rows($aresult) == 0) {
									echo "<font class='message'>".t("VIRHE: Tilausta ei löydy")."!<br><br></font>";
									exit;
								}

								$query = "	SELECT nimitys
											FROM varastopaikat
											WHERE yhtio = '$kukarow[yhtio]'
											AND tunnus  = '$lahdevarasto'";
								$varres = pupe_query($query);
								$varrow = mysql_fetch_assoc($varres);

								echo "<br><font class='message'>".t("Tehtiin siirtolistalle otsikko %s lähdevarasto on %s", $kieli, $kukarow["kesken"], $varrow["nimitys"])."</font><br>";

								//	Otetaan luotu otsikko talteen
								$otsikot[] = $kukarow["kesken"];

								$laskurow = mysql_fetch_assoc($aresult);
							}

							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno = '$pairow[tuoteno]'
										and yhtio = '$kukarow[yhtio]'";
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

								echo "<font class='info'>".t("Siirtolistalle lisättiin %s tuotetta %s", "", $siirretaan." ".$trow["yksikko"], $trow["tuoteno"])."</font><br>";

							}
							else {
								echo t("VIRHE: Tuotetta ei löydy")."!<br>";
							}
						}
					}
				}
			}

			echo "</table><br>";

			if (count($otsikot) == 0) {
				echo "<font class='error'>".t("Yhtään siirtolistaa ei luotu")."!</font><br>";
			}
			else {
				echo "<font class='message'>".t("Luotiin %s siirtolistaa", $kieli, count($otsikot))."</font><br><br><br>";

				if ($kesken != "X") {
					foreach ($otsikot as $ots) {
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ots'";
						$aresult = pupe_query($query);
						$laskurow = mysql_fetch_assoc($aresult);

						$kukarow["kesken"]	= $laskurow["tunnus"];
						$toim 				= "SIIRTOLISTA";

						require ("tilaus-valmis-siirtolista.inc");
					}
				}
				else {
					echo "<font class='message'>".t("Siirtolistat jätettiin kesken")."</font><br><br><br>";
				}
			}

			$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = pupe_query($query);
		}
		else {
			echo "<font class='error'>".t("Varastonvalinnassa on virhe")."<br></font>";
		}
	}

	require ("../inc/footer.inc");

?>