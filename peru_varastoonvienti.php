<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Peru varastoonvienti:</font><hr>";

	if ($id != 0) {
		$query = "	SELECT *
					from lasku
					where yhtio	 = '$kukarow[yhtio]'
					and tila	 = 'K'
					and alatila != 'X'
					and laskunro = '$id'
					and vanhatunnus = ''";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) != 1) {
			echo "Keikkaa ei löydy!";
			exit;
		}

		$laskurow = mysql_fetch_array($res);

		// Laskun tiedot
		echo "Laskun tiedot";
		echo "<table>";
		echo "<tr><th>Tunnus:</th>			<td>$laskurow[tunnus]</td></tr>";
		echo "<tr><th>Laskunro:</th>		<td>$laskurow[laskunro]</td></tr>";
		echo "<tr><th>Tila:</th>			<td>$laskurow[tila]</td></tr>";
		echo "<tr><th>Alatila:</th>			<td>$laskurow[alatila]</td></tr>";
		echo "<tr><th>Nimi:</th>			<td>$laskurow[nimi]</td></tr>";
		echo "<tr><th>Vienti:</th>			<td>$laskurow[vienti]</td></tr>";
		echo "</table><br><br>";

		// Näytetään tilausrivit
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and uusiotunnus = '$laskurow[tunnus]'";
		$res = mysql_query($query) or pupe_error($query);

		echo "Tilausrivit, Tuote ja Tapahtumat:<br>";
		echo "<table>";

		while ($rivirow = mysql_fetch_array($res)) {
			
			$query = "	SELECT *
						from tuote
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$rivirow[tuoteno]'";
			$tuores = mysql_query($query) or pupe_error($query);
			$tuorow = mysql_fetch_array($tuores);
			
			$voidaankosnropoistaa = "";
			
			if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
				$query = "	SELECT yhtio
							FROM sarjanumeroseuranta
							WHERE yhtio 		  = '$kukarow[yhtio]'
							and tuoteno			  = '$rivirow[tuoteno]'
							and ostorivitunnus	  = '$rivirow[tunnus]'
							and myyntirivitunnus != 0";
				$sarjares = mysql_query($query) or pupe_error($query);
			
				if (mysql_num_rows($sarjares) == 0) {
					$voidaankosnropoistaa = "OK";
				}
			}
			
			
			if ($tee != 'KORJAA') {
				echo "<tr>";

				echo "<td class='back' valign='top'><table>";
				echo "<tr><th>Tuoteno:</th>			<td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($rivirow["tuoteno"])."'>$rivirow[tuoteno]</a></td></tr>";
				echo "<tr><th>Varattu:</th>			<td>$rivirow[varattu]</td></tr>";
				echo "<tr><th>Kpl:</th>				<td>$rivirow[kpl]</td></tr>";
				echo "<tr><th>Viety varastoon:</th>	<td>$rivirow[laskutettuaika]</td></tr>";
				echo "<tr><th>Uusiotunnus:</th>		<td>$rivirow[uusiotunnus]</td></tr>";
				echo "<tr><th>Otunnus:</th>			<td>$rivirow[otunnus]</td></tr>";
				echo "</table></td>";
			}

			// Tässä toi DESC sana ja LIMIT 1 ovat erityisen tärkeitä
			$order = "";
			if ($tee == 'KORJAA') {
				$order = "DESC LIMIT 1";
				//Tässä aloitetaan ykkösestä jotta voimme poistaa vientikerran jos niitä on monta vaikka meillä on order by desc ja se joka poistetaan tulee ekana järjestyksessä
				$vientikerta = 1;
			}
			else {
				//Normaalisti eka vientikerta saa indeksin nolla
				$vientikerta = 0;
			}

			$query = "	SELECT count(*) kpl
						from tapahtuma
						where yhtio = '$kukarow[yhtio]'
						and laji = 'tulo'
						and rivitunnus = '$rivirow[tunnus]'";
			$countres = mysql_query($query) or pupe_error($query);
			$countrow = mysql_fetch_array($countres);

			//Montako kertaa tämä rivi on viety varastoon
			$vientikerrat = $countrow["kpl"];

			//Haetaan tapahtuman tiedot
			$query = "	SELECT *
						from tapahtuma
						where yhtio = '$kukarow[yhtio]'
						and laji = 'tulo'
						and rivitunnus = '$rivirow[tunnus]'
						order by tunnus $order";
			$tapares = mysql_query($query) or pupe_error($query);

			while($taparow = mysql_fetch_array($tapares)) {

				if ($tee != 'KORJAA') {
					echo "<td class='back' valign='top'><table>";
					echo "<tr><th>Tuoteno:</th><td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($taparow["tuoteno"])."'>$taparow[tuoteno]</a></td></tr>";
					echo "<tr><th>Kpl:</th><td>$taparow[kpl]</td></tr>";
					echo "<tr><th>Laatija:</th><td>$taparow[laatija]</td></tr>";
					echo "<tr><th>Laadittu:</th><td>$taparow[laadittu]</td></tr>";
					echo "<tr><th>Selite:</th><td>$taparow[selite]</td></tr>";
					echo "<tr><th>Kehahinta:</th><td>$taparow[hinta]</td></tr>";
				}

				// Tutkitaan onko tätä tuotetta runkslattu tän tulon jälkeen
				$query = "	SELECT count(*) kpl
							from tapahtuma
							where yhtio = '$kukarow[yhtio]'
							and laji in ('laskutus','inventointi','epäkurantti','valmistus')
							and tuoteno = '$taparow[tuoteno]'
							and tunnus > '$taparow[tunnus]'";
				$tapares2 = mysql_query($query) or pupe_error($query);
				$taparow2 = mysql_fetch_array($tapares2);

				$voidaankopoistaa = "";

				if ($vientikerrat == 1 and ($taparow2["kpl"] > 0 and $voidaankosnropoistaa == "")) {
					$voidaankopoistaa = "Ei";
				}
				elseif($vientikerrat == 1 and ($taparow2["kpl"] == 0 or $voidaankosnropoistaa == "OK")) {
					$voidaankopoistaa = "Kyllä";
				}
				elseif($vientikerrat > 1 and $taparow2["kpl"] >= 0 and $vientikerta > 0) {
					$voidaankopoistaa = "Kyllä";
				}
				elseif($vientikerrat > 1 and $taparow2["kpl"] == 0 and $vientikerta == 0) {
					$voidaankopoistaa = "Kyllä";
				}
				else {
					$voidaankopoistaa = "Ei";
				}

				if ($tee != 'KORJAA') {
					echo "<tr><th>Voidaankopoistaa:</th><td>$voidaankopoistaa</td></tr>";
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
				$taparow3 = mysql_fetch_array($tapares3);

				if ($tee != 'KORJAA') {
					echo "<tr><th>Kehahinta from --> to:</th><td>$taparow[hinta] --> $taparow3[hinta]</td></tr>";
					echo "</table></td>";
				}

				if ($tee == 'KORJAA' and $voidaankopoistaa == "Kyllä") {
					//Poistetaan tapahtuma
					$query = "	DELETE from tapahtuma
								where yhtio	  = '$kukarow[yhtio]'
								and tunnus	  = '$taparow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);

					echo "$rivirow[tuoteno]: Poistetaan tapahtuma!<br>";

					// onko tämä tehdaslisävaruste
					if ($rivirow["perheid2"] != 0 and $rivirow["perheid2"] != $rivirow["tunnus"]) {
						$tehdaslisavaruste_lisa1 = ", saldo_varattu = saldo_varattu - $rivirow[kpl] ";
						$tehdaslisavaruste_lisa2 = ", saldo_varattu = $rivirow[kpl] ";
					}
					else {
						$tehdaslisavaruste_lisa1 = "";
						$tehdaslisavaruste_lisa2 = "";
					}
					
					// Laitetaan takas saldoille
					$query = "	UPDATE tuotepaikat
								set saldo = saldo - $rivirow[kpl],
								saldoaika   = now()
								$tehdaslisavaruste_lisa1
								where yhtio	  	  = '$kukarow[yhtio]'
								and tuoteno	  	  = '$rivirow[tuoteno]'
								and hyllyalue 	  = '$rivirow[hyllyalue]'
								and hyllynro	  = '$rivirow[hyllynro]'
								and hyllyvali	  = '$rivirow[hyllyvali]'
								and hyllytaso	  = '$rivirow[hyllytaso]'";
					$korjres = mysql_query($query) or pupe_error($query);

					if (mysql_affected_rows() == 0) {
						echo "<font class='error'>".t("Varastopaikka")." $rivirow[hyllyalue]-$rivirow[hyllynro]-$rivirow[hyllytaso]-$rivirow[hyllyvali] ".t("ei löydy vaikka se on syötetty tilausriville! Koitetaan päivittää oletustapaikkaa!")."</font>";

						$query = "	UPDATE tuotepaikat
									set saldo = saldo - $rivirow[kpl],
									saldoaika   = now()
									$tehdaslisavaruste_lisa1
									where yhtio = '$kukarow[yhtio]' and
									tuoteno     = '$rivirow[tuoteno]' and
									oletus     != ''
									limit 1";
						$korjres = mysql_query($query) or pupe_error($query);

						if (mysql_affected_rows() == 0) {

							echo "<br><font class='error'>".t("Tuotteella")." $rivirow[tuoteno] ".t("ei ole oletuspaikkaakaaaaan")."!!!</font><br><br>";

							// haetaan firman eka varasto, tökätään kama sinne ja tehdään siitä oletuspaikka
							$query = "SELECT alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue, alkuhyllynro limit 1";
							$korjres = mysql_query($query) or pupe_error($query);
							$hyllyrow =  mysql_fetch_array($korjres);

							echo "<br><font class='error'>".t("Tehtiin oletuspaikka")." $rivirow[tuoteno]: $rivirow[alkuhyllyalue] $rivirow[alkuhyllynro] 0 0</font><br><br>";

							// lisätään paikka
							$query = "	INSERT INTO tuotepaikat set
										yhtio		= '$kukarow[yhtio]',
										tuoteno     = '$rivirow[tuoteno]',
										oletus      = 'X',
										saldo       = '$rivirow[kpl]',
										$tehdaslisavaruste_lisa2
										saldoaika   = now(),
										hyllyalue   = '$hyllyrow[alkuhyllyalue]',
										hyllynro    = '$hyllyrow[alkuhyllynro]',
										hyllytaso   = '0',
										hyllyvali   = '0'";
							$korjres = mysql_query($query) or pupe_error($query);

							// tehdään tapahtuma
							$query = "	INSERT into tapahtuma set
										yhtio 		= '$kukarow[yhtio]',
										tuoteno 	= '$rivirow[tuoteno]',
										kpl 		= '0',
										kplhinta	= '0',
										hinta 		= '0',
										laji 		= 'uusipaikka',
										hyllyalue   = '$hyllyrow[alkuhyllyalue]',
										hyllynro    = '$hyllyrow[alkuhyllynro]',
										hyllytaso   = '0',
										hyllyvali   = '0',
										selite 		= '".t("Lisättiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
										laatija 	= '$kukarow[kuka]',
										laadittu 	= now()";
							$korjres = mysql_query($query) or pupe_error($query);
						}
						else {
							echo "<font class='error'>".t("Huh, se onnistui!")."</font><br>";
						}
					}

					echo "$rivirow[tuoteno]: Päivitetään saldo ($rivirow[kpl]) pois tuotepaikalta!<br>";

					//Päivitetään tuotteen kehahinta
					$query = "	UPDATE tuote
								SET kehahin   = '$taparow3[hinta]'
								where yhtio	  = '$kukarow[yhtio]'
								and tuoteno	  = '$rivirow[tuoteno]'";
					$korjres = mysql_query($query) or pupe_error($query);

					echo "$rivirow[tuoteno]: Päivitetään tuotteen keskihankintahinta ($taparow[hinta] --> $taparow3[hinta]) takaisin edelliseen arvoon!<br>";

					// Katotaan oliko tämä ensimmäinen ja viimeine varastovienti joka nyt poistettiin
					$query = "	SELECT tunnus
								from tapahtuma
								where yhtio = '$kukarow[yhtio]'
								and laji = 'tulo'
								and rivitunnus = '$rivirow[tunnus]'
								order by tunnus";
					$korjres = mysql_query($query) or pupe_error($query);

					if(mysql_num_rows($korjres) == 0) {

						if ($yhtiorow['suuntalavat'] == 'S' and $rivirow['suuntalava'] > 0) {
							// otetaan suuntalava pois purettu-tilasta
							$query = "	UPDATE suuntalavat SET
										tila = ''
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rivirow['suuntalava']}'";
							$suuntalava_korjres = mysql_query($query) or pupe_error($query);
						}

						// Päivitetään alkuperäinen ostorivi
						$query = "	UPDATE tilausrivi
									set varattu=kpl, kpl=0, laskutettuaika='0000-00-00', suuntalava=0
									where yhtio = '$kukarow[yhtio]'
									and tunnus = '$rivirow[tunnus]'";
						$korjres = mysql_query($query) or pupe_error($query);

						echo "$rivirow[tuoteno]: Päiviteään tilausrivi takaisin tilaan ennen varastoonvientiä<br>";
					}
					echo "<br>";
				}
				$vientikerta++;
			}

			if ($tee != 'KORJAA') {
				echo "</tr>";
				echo "<tr><td colspan='2' class='back'><br></td></tr>";
			}
		}
		echo "</table><br><br>";

		if ($tee != 'KORJAA') {
			echo "<a href='$PHP_SELF?tee=KORJAA&id=$id'>".t("Näyttää hyvältä ja haluan korjata")."</a><br><br>";
			echo "<font class='error'>HUOM: Poistaa vain yhden varastoonviennin per tuote per korjausajo!</font>";

		}
		else {
			echo "<a href='$PHP_SELF?id=$id'>".t("Näytää keikka uudestaan")."</a>";
		}
	}

	if ($id == '') {
		echo "<br><table>";
		echo "<tr>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<th>".t("Syöta keikan numero")."</th>";
		echo "<td><input type='text' size='30' name='id'></td>";
		echo "<td><input type='submit' value='".t("Jatka")."'></td></form></tr>";
		echo "</table>";
	}

	require("inc/footer.inc");

?>