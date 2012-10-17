<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Luo siirtolista tuotepaikkojen hälytysrajojen perusteella")."</font><hr>";

	// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
	$org_rajaus = $abcrajaus;
	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

	list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

	if ($tee == 'M') {
		if ($kohdevarasto != '' and !empty($lahdevarastot) and !in_array($kohdevarasto, $lahdevarastot)) {

			$lisa = "";
			$abcjoin = "";

			if ($osasto != "") {
				$lisa .= " and tuote.osasto='$osasto' ";
			}

			if ($tuoteryhma != "") {
				$lisa .= " and tuote.try='$tuoteryhma' ";
			}

			if ($tuotemerkki != "") {
				$lisa .= " and tuote.tuotemerkki='$tuotemerkki' ";
			}

			if ($abcrajaus != "") {
				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and
							abc_aputaulu.tuoteno = tuote.tuoteno and
							abc_aputaulu.tyyppi = '$abcrajaustapa' and
							(abc_aputaulu.luokka <= '$abcrajaus' or abc_aputaulu.luokka_osasto <= '$abcrajaus' or abc_aputaulu.luokka_try <= '$abcrajaus'))";
			}

			if ($toimittaja != "") {
				$query = "	SELECT distinct tuoteno
							from tuotteen_toimittajat
							where yhtio = '$kukarow[yhtio]'
							and toimittaja = '$toimittaja'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					$lisa .= " and tuote.tuoteno in (";
					while ($toimirow = mysql_fetch_array($result)) {
						$lisa .= "'$toimirow[tuoteno]',";
					}
					$lisa = substr($lisa,0,-1).")";
				}
				else {
					echo "<font class='error'>".t("Toimittaa ei löytynyt")."! ".t("Ajetaan ajo ilman rajausta")."!</font><br><br>";
				}
			}

			$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$kukarow['kesken'] = 0;

			$query = "SELECT * FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kohdevarasto'";
			$result = mysql_query($query) or pupe_error($query);
			$varow = mysql_fetch_array($result);

			//katotaan tarvetta
			$query = "	SELECT tuotepaikat.*, tuotepaikat.halytysraja, concat_ws('-',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka, tuote.nimitys
						FROM tuotepaikat
						JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno $lisa)
						$abcjoin
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and concat(rpad(upper('$varow[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper('$varow[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and tuotepaikat.halytysraja != 0
						and tuotepaikat.halytysraja > tuotepaikat.saldo
						order by tuotepaikat.tuoteno";
			$resultti = mysql_query($query) or pupe_error($query);
			$luku = mysql_num_rows($result);

			if ((int) $olliriveja == 0 or $olliriveja == '') {
				$olliriveja = 20;
			}

			//	Otetaan luodut otsikot talteen
			$otsikot = array();

			//	Varmistetaan ettei olla missään kesken
			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$kukarow["kesken"] = 0;

			// tehdään jokaiselle valitulle lahdevarastolle erikseen
			foreach ($lahdevarastot as $lahdevarasto) {
				//	Varmistetaan että aloitetaan aina uusi otsikko uudelle varastolle
				$tehtyriveja = 0;

				// mennään aina varmasti alkuun
				mysql_data_seek($resultti, 0);

				while ($pairow = mysql_fetch_array($resultti)) {

					//katotaan paljonko sinne on jo menossa
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tuoteno = '$pairow[tuoteno]'
								and varattu > 0
								and tyyppi = 'G'
								and lasku.clearing = '$kohdevarasto'";
					$vanresult = mysql_query($query) or pupe_error($query);
					$vanhatrow = mysql_fetch_array($vanresult);

					list( , , $saldo_myytavissa_kohde) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $kohdevarasto);
					$tarve_kohdevarasto = $pairow['halytysraja']-$saldo_myytavissa_kohde;

					if ($pairow['tilausmaara'] > 0 and $tarve_kohdevarasto > 0) {
						$tarve_kohdevarasto = $pairow['tilausmaara'];
					}

					//ei lähetetä lisää jos on jo matkalla
					if ($vanhatrow['varattu'] > 0) {
						$tarve_kohdevarasto = 0;
					}

					//katotaan myytävissä määrä
					list( , , $saldo_myytavissa) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $lahdevarasto);

					$saldo_myytavissa = (float) $saldo_myytavissa;

					if ($saldo_myytavissa > 0 and $tarve_kohdevarasto > 0) {

						if ($tarve_kohdevarasto >= $saldo_myytavissa) {
							if ($saldo_myytavissa == 1) {
								$siirretaan = $saldo_myytavissa;
							}
							else {
								$siirretaan = floor($saldo_myytavissa / 2);
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
								$delresult = mysql_query($query) or pupe_error($query);

								$kukarow["kesken"] = 0;

								$tilausnumero	= $kukarow["kesken"];
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
								$aresult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($aresult) == 0) {
									echo "<font class='message'>".t("VIRHE: Tilausta ei löydy")."!<br><br></font>";
									exit;
								}

								$query = "SELECT nimitys from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus = '$lahdevarasto'";
								$varres = mysql_query($query) or pupe_error($query);
								$varrow = mysql_fetch_array($varres);

								echo "<br><font class='message'>".t("Tehtiin siirtolistalle otsikko %s lähdevarasto on %s", $kieli, $kukarow["kesken"], $varrow["nimitys"])."</font><br>";

								//	Otetaan luotu otsikko talteen
								$otsikot[] = $kukarow["kesken"];

								$laskurow = mysql_fetch_array($aresult);
							}

							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno='$pairow[tuoteno]' and yhtio='$kukarow[yhtio]'";
							$rarresult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($rarresult) == 1) {
								$trow = mysql_fetch_array($rarresult);
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

								//echo "<font class='info'>".t("Siirtolistalle lisättiin %s tuotetta %s", $kieli, $siirretaan." ".$trow["yksikko"], $trow["tuoteno"])."</font><br>";

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
				echo "<font class='error'>".t("Lähdevarastojen saldo ei riitä kohdevaraston tarpeeseen")."!</font><br>";
			}
			else {
				echo "<font class='message'>".t("Luotiin %s siirtolistaa", $kieli, count($otsikot))."</font><br><br><br>";

				if ($kesken != "X") {
					foreach ($otsikot as $ots) {
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$ots'";
						$aresult = mysql_query($query) or pupe_error($query);
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

			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$tee = "";
		}
		else {
			echo "<font class='error'>".t("Varastonvalinnassa on virhe")."<br></font>";
			$tee = '';
		}
	}

	if ($tee == '') {
		// Tällä ollaan, jos olemme syöttämässä tiedostoa ja muuta
		echo "<form name = 'valinta' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>";

		echo "<tr><th>".t("Lähdevarasto, eli varasto josta kerätään").":</th>";
		echo "<td colspan='4'>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if (is_array($lahdevarastot) && in_array($varow['tunnus'], $lahdevarastot)) $sel = 'checked';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
			}

			echo "<input type='checkbox' name='lahdevarastot[]' value='$varow[tunnus]' $sel />$varow[nimitys] $varastomaa<br />";
		}

		echo "</td></tr>";

		echo "<tr><th>".t("Kohdevarasto, eli varasto jonne lähetetään").":</th>";
		echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' AND varasto_status != 'P'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if ($varow['tunnus']==$kohdevarasto) $sel = 'selected';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = strtoupper($varow['maa']);
			}

			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t("Rivejä per tilaus (tyhjä = 20)").":</th><td><input type='text' size='8' value='$olliriveja' name='olliriveja'></td>";

		if($kesken == "X") {
			$c = "checked";
		}
		else {
			$c = "";
		}
		echo "<tr><th>".t("Jätä tilaus kesken").":</th><td><input type='checkbox' name = 'kesken' value='X' $c></td>";

		echo "<tr><th>".t("Osasto")."</th><td>";

		// tehdään avainsana query
		$sresult = t_avainsana("OSASTO");

		echo "<select name='osasto'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($osasto == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select>";


		echo "</td></tr><tr><th>".t("Tuoteryhmä")."</th><td>";

		//Tehdään osasto & tuoteryhmä pop-upit
		// tehdään avainsana query
		$sresult = t_avainsana("TRY");

		echo "<select name='tuoteryhma'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuoteryhma == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select>";


		echo "</td></tr>
				<tr><th>".t("Tuotemerkki")."</th><td>";

		//Tehdään osasto & tuoteryhmä pop-upit
		$query = "	SELECT distinct tuotemerkki
					FROM tuote
					WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
					ORDER BY tuotemerkki";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<select name='tuotemerkki'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuotemerkki == $srow["tuotemerkki"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
		}
		echo "</select>";

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
		$tresult = mysql_query($query) or pupe_error($query);
		echo "<select name='valittu_toimitustapa'>";

		while ($row = mysql_fetch_array($tresult)) {
			echo "<option value='$row[selite]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")."</option>";
		}
		echo "</select>";


		echo "</td></tr>";
		echo "</table><br>

		<input type = 'submit' value = '".t("Generoi siirtolista")."'>
		</form>";
	}

	require ("../inc/footer.inc");
?>
