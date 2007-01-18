<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Luo siirtolista tuotepaikkojen hälytysrajojen perusteella")."</font><hr>";

	if ($tee == 'M') {
		if ($kohdevarasto != '' and $lahdevarasto != '' and $kohdevarasto != $lahdevarasto) {
			$lask = 0;

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
				$query = "select distinct tuoteno from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and toimittaja='$toimittaja'";
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

			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$kukarow['kesken'] = 0;

			$query = "SELECT * FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kohdevarasto'";
			$result = mysql_query($query) or pupe_error($query);
			$varow = mysql_fetch_array($result);
			
			//katotaan tarvetta
			$query = "SELECT tuotepaikat.*, tuotepaikat.halytysraja-saldo tarve, concat_ws('-',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka, tuote.nimitys
						FROM tuotepaikat
						JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno $lisa)
						$abcjoin
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and concat(rpad(upper('$varow[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper('$varow[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and tuotepaikat.halytysraja != 0
						and tuotepaikat.halytysraja > saldo
						order by tuotepaikat.tuoteno";
			$resultti = mysql_query($query) or pupe_error($query);
			$luku = mysql_num_rows($result);

			$tehtyriveja = 0;
			$otsikoita = 0;

			if ((int) $olliriveja == 0 or $olliriveja == '') {
				$olliriveja = 20;
			}

			while ($pairow = mysql_fetch_array($resultti)) {

				if ($tehtyriveja == 0 or $tehtyriveja == (int) $olliriveja+1) {
					$jatka		= "kala";
					if ($kukarow['kesken'] != 0) {
						$query = "	UPDATE lasku SET alatila = 'J' WHERE tunnus = '$kukarow[kesken]'";
						$delresult = mysql_query($query) or pupe_error($query);
					}


					$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
					$delresult = mysql_query($query) or pupe_error($query);

					$kukarow["kesken"] = 0;

					$tilausnumero = $kukarow["kesken"];
					$clearing 	= $kohdevarasto;
					$toimpp 	= $kerpp = date(j);
					$toimkk 	= $kerkk = date(n);
					$toimvv 	= $kervv = date(Y);
					$comments 	= $kukarow["nimi"]." Generoi hälytysrajojen perusteella";
					$viesti 	= $kukarow["nimi"]." Generoi hälytysrajojen perusteella";
					$varasto 	= $lahdevarasto;
					$toim = "SIIRTOLISTA";

					require ("otsik_siirtolista.inc");

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus = '$kukarow[kesken]'";
					$aresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($aresult) == 0) {
						echo "<font class='message'>".t("VIRHE: Tilausta ei löydy")."!<br><br></font>";
						exit;
					}
					$laskurow = mysql_fetch_array($aresult);

					$tehtyriveja = 1;
					$otsikoita ++;

				}
				
				//katotaan paljonko sinne on jo menossa
				$query = "SELECT sum(varattu) varattu
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tuoteno = '$pairow[tuoteno]'
							and varattu > 0
							and tyyppi = 'G'
							and lasku.clearing = '$kohdevarasto'";
				$vanresult = mysql_query($query) or pupe_error($query);
				$vanhatrow = mysql_fetch_array($vanresult);

				if ($pairow['tilausmaara'] > 0 and $pairow['tarve'] > 0) {
					$pairow['tarve'] = $pairow['tilausmaara'];
				}
				
				//ja vähennetään se tarpeesta
				//$pairow['tarve'] = $pairow['tarve'] - $vanhatrow['varattu'];
				
				//ei lähetetä lisää jos on jo matkalla
				if ($vanhatrow['varattu'] > 0) {
					$pairow['tarve'] = 0;
				}

				//katotaan myytävissä määrä
				$saldo_myytavissa = (float) saldo_myytavissa($pairow["tuoteno"], "KAIKKI", "$lahdevarasto");
				
				if ($saldo_myytavissa > 0 and $pairow['tarve'] > 0) {
			
					if ($pairow['tarve'] > $saldo_myytavissa) {
						$siirretaan = $saldo_myytavissa;
					}
					else {
						$siirretaan = $pairow['tarve'];
					}

					if ($siirretaan > 0) {

						$query = "	SELECT *
									FROM tuote
									WHERE tuoteno='$pairow[tuoteno]' and yhtio='$kukarow[yhtio]'";
						$rarresult = mysql_query($query) or pupe_error($query);

						if(mysql_num_rows($rarresult) == 1) {

							$trow = mysql_fetch_array($rarresult);
							$toimaika 	= $laskurow["toimaika"];
							$kerayspvm	= $laskurow["kerayspvm"];
							$tuoteno	= $pairow["tuoteno"];
							$kpl		= $siirretaan;
							//$paikka		= "$uuspairow[hyllyalue]#$uuspairow[hyllynro]#$uuspairow[hyllyvali]#$uuspairow[hyllytaso]";
							$varasto	= $lahdevarasto;
							$hinta 		= "";
							$netto 		= "";
							$ale 		= "";
							$var		= "";
							
							$korvaavakielto		= 1;
							$perhekielto		= 1;
							$orvoteikiinnosta	= "EITOD";
							
							require ('lisaarivi.inc');

							$tuoteno	= '';
							$kpl		= '';
							$hinta		= '';
							$ale		= '';
							$alv		= 'X';
							$var		= '';
							$toimaika	= '';
							$kerayspvm	= '';
							$kommentti	= '';

							$tehtyriveja ++;

						}
						else {
							echo t("VIRHE: Tuotetta ei löydy")."!<br>";
						}
						$lask++;
					}
				}
			}
			echo "</table><br>";
			//echo "lask = $lask<br><br>";
			if ($lask == 0) {
				echo "<font class='error'>".t("Yhtään riviä ei voitu toimittaa lähdevarastosta kohdevarastoon")."!!!!</font><br>";
				$query = "	SELECT tunnus FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
				$okdelresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($okdelresult) == 0 and $kukarow['kesken'] != 0) {
					$query = "	UPDATE lasku SET tila = 'D', alatila = 'G' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
					$delresult = mysql_query($query) or pupe_error($query);
				}
				elseif ($kukarow['kesken'] != 0){
					echo "<font class='error'>".t("APUAAAA tilauksella")." $kukarow[kesken] ".t("on rivejä vaikka luultiin että ei olisi!!!!!")."<br></font><br>";
				}

			}
			else {
				echo "<font class='message'>".t("Luotiin")." $otsikoita ".t("siirtolistaa")."</font><br><br><br>";
				$query = "	UPDATE lasku SET alatila = 'J' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
				$delresult = mysql_query($query) or pupe_error($query);
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
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>";

		echo "<tr><th>".t("Lähdevarasto, eli varasto josta kerätään").":</th>";
		echo "<td colspan='4'><select name='lahdevarasto'><option value=''>".t("Valitse")."</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if ($varow['tunnus']==$lahdevarasto) $sel = 'selected';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maakoodi'])) {
				$varastomaa = strtoupper($varow['maa']);
			}

			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t("Kohdevarasto, eli varasto jonne lähetetään").":</th>";
		echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if ($varow['tunnus']==$kohdevarasto) $sel = 'selected';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maakoodi'])) {
				$varastomaa = strtoupper($varow['maa']);
			}

			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t("Rivejä per tilaus (tyhjä = 20)").":</th><td><input type='text' size='8' value='$olliriveja' name='olliriveja'></td>";

		echo "<tr><th>".t("Osasto")."</th><td>";

		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
					ORDER BY selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

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
		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
					ORDER BY selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

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

		// katotaan onko abc aputaulu rakennettu
		$query  = "select count(*) from abc_aputaulu where yhtio='$kukarow[yhtio]' and tyyppi in ('TK','TR','TP')";
		$abcres = mysql_query($query) or pupe_error($query);
		$abcrow = mysql_fetch_array($abcres);

		// jos on niin näytetään tällänen vaihtoehto
		if ($abcrow[0] > 0) {
			echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td>";

			$sel = array();
			$sel[$abcrajaus] = "SELECTED";

			echo "<select name='abcrajaus'>
			<option value=''>Ei rajausta</option>
			<option $sel[0] value='0'>Luokka A-30</option>
			<option $sel[1] value='1'>Luokka B-20 ja paremmat</option>
			<option $sel[2] value='2'>Luokka C-15 ja paremmat</option>
			<option $sel[3] value='3'>Luokka D-15 ja paremmat</option>
			<option $sel[4] value='4'>Luokka E-10 ja paremmat</option>
			<option $sel[5] value='5'>Luokka F-05 ja paremmat</option>
			<option $sel[6] value='6'>Luokka G-03 ja paremmat</option>
			<option $sel[7] value='7'>Luokka H-02 ja paremmat</option>
			<option $sel[8] value='8'>Luokka I-00 ja paremmat</option>
			</select>";

			$sel = array();
			$sel[$abcrajaustapa] = "SELECTED";

			echo "<select name='abcrajaustapa'>
			<option $sel[TK] value='TK'>Myyntikate</option>
			<option $sel[TR] value='TR'>Myyntirivit</option>
			<option $sel[TP] value='TK'>Myyntikappaleet</option>
			</select>
			</td></tr>";
		}

		echo "</table><br>
		<input type = 'submit' value = '".t("Generoi siirtolista")."'>
		</form>";
	}

	require ("../inc/footer.inc");
?>