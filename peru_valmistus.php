<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Peru valmistus:</font><hr>";

	if ($id != 0) {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio	 = '$kukarow[yhtio]'
					AND tunnus	 = '$id'
					AND ((tila = 'V' and alatila  in ('V','C')) or (tila = 'L' and alatila  != 'X' and tilaustyyppi='V'))";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) != 1) {
			echo "Valmistusta ei l�ydy!";
			exit;
		}
		$laskurow = mysql_fetch_array($res);

		// Laskun tiedot
		echo t("Laskun tiedot");
		echo "<table>";
		echo "<tr><th>".t("Tunnus").":</th>	<td>$laskurow[tunnus]</td></tr>";
		echo "<tr><th>".t("Tila").":</th>	<td>$laskurow[tila]</td></tr>";
		echo "<tr><th>".t("Alatila").":</th><td>$laskurow[alatila]</td></tr>";
		echo "<tr><th>".t("Nimi").":</th>	<td>$laskurow[nimi]</td></tr>";
		echo "<tr><th>".t("Viite").":</th>	<td>$laskurow[viesti]</td></tr>";
		echo "</table><br><br>";

		// N�ytet��n tilausrivit
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and tyyppi in ('V','W','M')
					ORDER BY perheid desc, tyyppi in ('W','M','L','D','V'), tunnus";
		$res = mysql_query($query) or pupe_error($query);

		echo t("Tilausrivit, Tuote ja Tapahtumat").":<br>";
		echo "<table>";
		echo "<form method=POST'>";
		echo "<input type='hidden' name='id'  value='$laskurow[tunnus]'>";

		while ($rivirow = mysql_fetch_array($res)) {

			if ($tee != 'KORJAA' and $edtuote != $rivirow["perheid"]) {
				echo "<tr><td colspan='3' class='back'><hr></td></tr>";
			}

			$edtuote = $rivirow["perheid"];

			if ($rivirow["perheid"] == $rivirow["tunnus"]) {

				//Haetaan valmistetut tuotteet ja raaka-aineet
				$query = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta
							from tilausrivi
							JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
							where tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.perheid = '$rivirow[perheid]'
							and tilausrivi.tyyppi in ('L','D','O','V')
							order by tilausrivi.tunnus desc";
				$valm_res = mysql_query($query) or pupe_error($query);

				$sarjanumeroita = FALSE;

				// onko sarjanumerollisia ?
				while ($valm_row = mysql_fetch_array($valm_res)) {
					if ($valm_row["sarjanumeroseuranta"] != "") {
						$sarjanumeroita = TRUE;
						break;
					}
				}

				if (!$sarjanumeroita and $tee != 'KORJAA') {
					echo "<tr><th colspan='1'><input type='checkbox' name='perutaan[$rivirow[tunnus]]' value='$rivirow[tunnus]'> ".t("Peru tuotteen valmistus")."</th></tr>";
				}

				mysql_data_seek($valm_res, 0);

				//Fl�g�t��n voidaanko is� poistaa
				$isa_voidaankopoistaa = "";
				$valmkpl = 0;

				while ($valm_row = mysql_fetch_array($valm_res)) {
					if ($valm_row["tyyppi"] == "L" or $valm_row["tyyppi"] == "D" or $valm_row["tyyppi"] == "O") {
						$valmkpl += $valm_row["kpl"];
					}

					if (($valm_row["tyyppi"] == "L" or $valm_row["tyyppi"] == "O") and ($valm_row["laskutettu"] != "" or $valm_row["laskutettuaika"] != "0000-00-00")) {
						$isa_voidaankopoistaa = "EI";
					}
				}
			}
			else {
				$valmkpl = 	$rivirow["kpl"];
			}

			$query = "	SELECT *
						from tuote
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$rivirow[tuoteno]'";
			$tuoteres = mysql_query($query) or pupe_error($query);
			$tuoterow = mysql_fetch_array($tuoteres);

			if ($tee != 'KORJAA') {
				echo "<tr>";
				echo "<td class='back' valign='top'><table>";
				echo "<tr><th>".t("Tuoteno").":</th>			<td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($rivirow["tuoteno"])."'>$rivirow[tuoteno]</a></td></tr>";
				echo "<tr><th>".t("Tilattu").":</th>			<td>$rivirow[tilkpl]</td></tr>";
				echo "<tr><th>".t("Varattu").":</th>			<td>$rivirow[varattu]</td></tr>";
				echo "<tr><th>".t("Valmistettu").":</th>		<td>$valmkpl</td></tr>";
				echo "<tr><th>".t("Valmistettu").":</th>		<td>$rivirow[toimitettuaika]</td></tr>";

				if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
					echo "<tr><th>".t("Kehahinta").":</th>		<td>$tuoterow[kehahin]</td></tr>";
					echo "<tr><th>".t("Otunnus").":</th>		<td>$rivirow[otunnus]</td></tr>";
				}

				echo "</table></td>";
			}

			$query = "	SELECT count(*) kpl
						from tapahtuma
						where yhtio = '$kukarow[yhtio]'
						and laji = 'valmistus'
						and rivitunnus = '$rivirow[tunnus]'";
			$countres = mysql_query($query) or pupe_error($query);
			$countrow = mysql_fetch_array($countres);

			//Montako kertaa t�m� rivi on viety varastoon
			$vientikerrat = $countrow["kpl"];

			// T�ss� toi DESC sana ja LIMIT 1 ovat erityisen t�rkeit�
			$order = "";

			if ($tee == 'KORJAA') {
				$order = "DESC LIMIT 1";

				//T�ss� aloitetaan ykk�sest� jotta voimme poistaa vientikerran jos niit� on monta vaikka meill� on order by desc ja se joka poistetaan tulee ekana j�rjestyksess�
				$vientikerta = 1;
			}
			else {
				//Normaalisti eka vientikerta saa indeksin nolla
				$vientikerta = 0;
			}

			//Haetaan tapahtuman tiedot
			$query = "	SELECT *
						from tapahtuma
						where yhtio = '$kukarow[yhtio]'
						and laji in ('valmistus','kulutus')
						and rivitunnus = '$rivirow[tunnus]'
						order by tunnus $order";
			$tapares = mysql_query($query) or pupe_error($query);

			while($taparow = mysql_fetch_array($tapares)) {

				if ($tee != 'KORJAA') {
					echo "<td class='back' valign='top'><table>";
					echo "<tr><th>".t("Tuoteno").":</th><td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($taparow["tuoteno"])."'>$taparow[tuoteno]</a></td></tr>";
					echo "<tr><th>".t("Kpl").":</th><td>$taparow[kpl]</td></tr>";
					echo "<tr><th>".t("Laatija")." ".t("Laadittu").":</th><td>$taparow[laatija] $taparow[laadittu]</td></tr>";
					echo "<tr><th>".t("Selite").":</th><td>$taparow[selite]</td></tr>";

					if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
						echo "<tr><th>Kehahinta:</th><td>$taparow[hinta]</td></tr>";
					}
				}

				// Tutkitaan onko t�t� tuotetta runkslattu t�n tulon j�lkeen
				$query = "	SELECT count(*) kpl
							from tapahtuma
							where yhtio = '$kukarow[yhtio]'
							and laji in ('laskutus','inventointi','ep�kurantti','valmistus', 'kulutus')
							and tuoteno = '$taparow[tuoteno]'
							and tunnus > '$taparow[tunnus]'
							and rivitunnus != '$taparow[rivitunnus]'";
				$tapares2 = mysql_query($query) or pupe_error($query);
				$taparow2 = mysql_fetch_array($tapares2);

				if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
					$voidaankopoistaa = "";

					if ($isa_voidaankopoistaa == "EI") {
						$voidaankopoistaa = "Ei";
					}
					elseif ($taparow2["kpl"] > 0) {
						$voidaankopoistaa = "Ei";
					}
					elseif($taparow2["kpl"] == 0) {
						$voidaankopoistaa = "Kyll�";
					}
					else {
						$voidaankopoistaa = "Ei";
					}
				}

				if ($tee != 'KORJAA' and ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M")) {
					echo "<tr><th>".t("Voidaanko poistaa").":</th><td>$voidaankopoistaa</td></tr>";
				}

				$query = "	SELECT *
							from tapahtuma
							where yhtio = '$kukarow[yhtio]'
							and laji in ('tulo','valmistus')
							and tuoteno = '$taparow[tuoteno]'
							and tunnus < '$taparow[tunnus]'
							ORDER BY tunnus DESC
							LIMIT 1";
				$tapares3 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tapares3) > 0) {
					$taparow3 = mysql_fetch_array($tapares3);
				}
				else {
					$query = "	SELECT *
								from tapahtuma
								where yhtio = '$kukarow[yhtio]'
								and laji in ('laskutus')
								and tuoteno = '$taparow[tuoteno]'
								and tunnus < '$taparow[tunnus]'
								ORDER BY tunnus DESC
								LIMIT 1";
					$tapares3 = mysql_query($query) or pupe_error($query);
					$taparow3 = mysql_fetch_array($tapares3);
				}

				if ($tee != 'KORJAA') {

					if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
						echo "<tr><th>".t("Kehahinta")." from --> to:</th><td>$taparow[hinta] --> $taparow3[hinta]</td></tr>";
					}
					echo "</table></td>";
				}

				if ($tee == 'KORJAA' and ($voidaankopoistaa == "Kyll�" or $vakisinpoista == 'Kyll�') and $perutaan[$rivirow["perheid"]] == $rivirow["perheid"] and $perutaan[$rivirow["perheid"]] != 0) {

					//Poistetaan tapahtuma
					$query = "	DELETE from tapahtuma
								where yhtio	  = '$kukarow[yhtio]'
								and tunnus	  = '$taparow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);

					echo "$rivirow[tuoteno]: ".t("Poistetaan tapahtuma")."!<br>";

					//Haetaan valmistetut tuotteet ja poistetaan informativinen valmistusrivi
					if ($taparow["laji"] == "valmistus") {
						$query = "	SELECT *
									from tilausrivi
									where yhtio = '$kukarow[yhtio]'
									and otunnus = '$laskurow[tunnus]'
									and perheid = '$rivirow[perheid]'
									and tyyppi in ('L','D')
									order by tunnus $order";
						$valm_res = mysql_query($query) or pupe_error($query);

						while($valm_row = mysql_fetch_array($valm_res)) {
							//Poistetaan tilausrivi
							$query = "	DELETE from tilausrivi
										where yhtio	 = '$kukarow[yhtio]'
										and otunnus	 = '$laskurow[tunnus]'
										and tunnus	 = '$valm_row[tunnus]'";
							$korjres = mysql_query($query) or pupe_error($query);

							echo "$valm_row[tuoteno]: ".t("Poistetaan informatiivinen valmistusrivi")."!<br>";
						}
					}

					//Laitetaan valmistetut kappaleet takaisin valmistukseen
					if ($taparow["laji"] == "valmistus") {
						$palkpl = $taparow["kpl"];
					}
					else {
						$palkpl = $taparow["kpl"] * -1;
					}

					$query = "	UPDATE tilausrivi
								set varattu = varattu+$palkpl,
								kpl=kpl-$palkpl
								where yhtio = '$kukarow[yhtio]'
								and otunnus = '$laskurow[tunnus]'
								and tunnus  = '$rivirow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);

					echo "$rivirow[tuoteno]: ".t("Palautetaan")." $palkpl ".t("kappaletta")." ".t("takaisin valmistukseen")."!<br>";

					// Laitetaan takas saldoille
					if ($tuoterow["ei_saldoa"] == "") {
						$query = "	UPDATE tuotepaikat
									set saldo 		= saldo - $taparow[kpl],
									saldoaika   	= now()
									where yhtio	  	  = '$kukarow[yhtio]'
									and tuoteno	  	  = '$rivirow[tuoteno]'
									and hyllyalue 	  = '$rivirow[hyllyalue]'
									and hyllynro	  = '$rivirow[hyllynro]'
									and hyllyvali	  = '$rivirow[hyllyvali]'
									and hyllytaso	  = '$rivirow[hyllytaso]'";
						$korjres = mysql_query($query) or pupe_error($query);

						if (mysql_affected_rows() == 0) {
							echo "<font class='error'>".t("Varastopaikka")." $rivirow[hyllyalue]-$rivirow[hyllynro]-$rivirow[hyllytaso]-$rivirow[hyllyvali] ".t("ei l�ydy vaikka se on sy�tetty tilausriville! Koitetaan p�ivitt�� oletustapaikkaa!")."</font>";

							$query = "	UPDATE tuotepaikat
										set saldo = saldo - $taparow[kpl],
										saldoaika   = now()
										where yhtio = '$kukarow[yhtio]' and
										tuoteno     = '$rivirow[tuoteno]' and
										oletus     != ''
										limit 1";
							$korjres = mysql_query($query) or pupe_error($query);

							if (mysql_affected_rows() == 0) {

								echo "<br><font class='error'>".t("Tuotteella")." $rivirow[tuoteno] ".t("ei ole oletuspaikkaakaan")."!!!</font><br><br>";

								// haetaan firman eka varasto, t�k�t��n kama sinne ja tehd��n siit� oletuspaikka
								$query = "SELECT alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue, alkuhyllynro limit 1";
								$korjres = mysql_query($query) or pupe_error($query);
								$hyllyrow =  mysql_fetch_array($korjres);

								echo "<br><font class='error'>".t("Tehtiin oletuspaikka")." $rivirow[tuoteno]: $rivirow[alkuhyllyalue] $rivirow[alkuhyllynro] 0 0</font><br><br>";

								// lis�t��n paikka
								$query = "	INSERT INTO tuotepaikat set
											yhtio		= '$kukarow[yhtio]',
											tuoteno     = '$rivirow[tuoteno]',
											oletus      = 'X',
											saldo       = '$taparow[kpl]',
											saldoaika   = now(),
											hyllyalue   = '$hyllyrow[alkuhyllyalue]',
											hyllynro    = '$hyllyrow[alkuhyllynro]',
											hyllytaso   = '0',
											hyllyvali   = '0'";
								$korjres = mysql_query($query) or pupe_error($query);

								// tehd��n tapahtuma
								$query = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$taparow[tuoteno]',
											kpl 		= '0',
											kplhinta	= '0',
											hinta 		= '0',
											laji 		= 'uusipaikka',
											selite 		= '".t("Lis�ttiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
											laatija 	= '$kukarow[kuka]',
											hyllyalue   = '$hyllyrow[alkuhyllyalue]',
											hyllynro    = '$hyllyrow[alkuhyllynro]',
											hyllytaso   = '0',
											hyllyvali   = '0',
											laadittu 	= now()";
								$korjres = mysql_query($query) or pupe_error($query);
							}
							else {
								echo "<font class='error'>".t("Huh, se onnistui!")."</font><br>";
							}
						}

						echo "$rivirow[tuoteno]: ".t("P�ivitet��n saldo")." ($taparow[kpl]) ".t("pois tuotepaikalta")."!<br>";
					}

					if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
						//P�ivitet��n tuotteen kehahinta
						$query = "	UPDATE tuote
									SET kehahin   = '$taparow3[hinta]'
									where yhtio	  = '$kukarow[yhtio]'
									and tuoteno	  = '$rivirow[tuoteno]'";
						$korjres = mysql_query($query) or pupe_error($query);

						echo "$rivirow[tuoteno]: ".t("P�ivitet��n tuotteen keskihankintahinta")." ($taparow[hinta] --> $taparow3[hinta]) ".t("takaisin edelliseen arvoon")."!<br>";
					}

					//P�ivitet��n rivit takaisin keskener�iseksi ja siivotaan samalla hieman py�ristyseroja jotka on tapahtunut perumisprosessissa
					$query = "	UPDATE tilausrivi
								set toimitettu = '', toimitettuaika='0000-00-00 00:00:00'
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivirow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);

					echo "$rivirow[tuoteno]: ".t("P�ivitet��n tilausrivi takaisin tilaan ennen valmistusta")."<br>";

					echo "<br><br>";
				}
				$vientikerta++;
			}

			if ($tee != 'KORJAA') {
				echo "</tr>";
				echo "<tr><td colspan='2' class='back'><br></td></tr>";
			}
		}
		echo "</table><br><br>";


		if ($tee == 'KORJAA') {
			$query = "	SELECT tunnus
						FROM tilausrivi
						WHERE yhtio	= '$kukarow[yhtio]'
						and otunnus = $laskurow[tunnus]
						and toimitettuaika = '0000-00-00 00:00:00'";
			$chkresult1 = mysql_query($query) or pupe_error($query);

			//Eli tilaus on osittain valmistamaton
			if (mysql_num_rows($chkresult1) != 0) {
				//Jos kyseess� on varastovalmistus
				$query = "	UPDATE lasku
							SET tila = 'V', alatila	= 'C'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = $laskurow[tunnus]";
				$chkresult2 = mysql_query($query) or pupe_error($query);

				echo t("P�ivitet��n valmistus takaisin avoimeksi")."!<br>";
			}
		}


		if ($tee != 'KORJAA') {

			echo "<input type='hidden' name='tee' value='KORJAA'>";


			echo t("V�kisinkorjaa vaikka korjaaminen ei olisi j�rjev��").": <input type='checkbox' name='vakisinpoista' value='Kyll�'><br><br>";


			echo t("N�ytt�� hyv�lt� ja haluan korjata").":<br><br>";
			echo "<input type='submit' value='".t("Korjaa")."'><br><br>";

			echo "<font class='error'>".t("HUOM: Poistaa vain yhden valmistuksen/osavalmistuksen per tuote per korjausajo")."!</font>";

		}
		else {
			echo "<a href='$PHP_SELF?id=$id'>".t("N�yt� valmistus uudestaan")."</a>";
		}

		echo "</form>";
	}

	if ($id == '') {
		echo "<br><table>";
		echo "<tr>";
		echo "<form method = 'post'>";
		echo "<th>".t("Sy�ta valmistuksen numero")."</th>";
		echo "<td><input type='text' size='30' name='id'></td>";
		echo "<td><input type='submit' value='".t("Jatka")."'></td></form></tr>";
		echo "</table>";
	}


	require("inc/footer.inc");
?>
