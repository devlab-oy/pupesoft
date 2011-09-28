<?php

	$tunnus = substr($tietue, 0, 3);
	$loytyi = 0;
	$tkesken = 1;

	switch ($tunnus) {

		case 'T03' :

			$pankkitilino = substr($tietue, 9, 14);

			if (!isset($aineistojafailissa)) $aineistojafailissa = 0;
			$aineistojafailissa++;
			if ($aineistojafailissa > 1) echo "</table><br><br>";

			echo "<table>";
			echo "<tr><th>".t("asiakas")."</th><th>" . t("tilino") .  "</th></tr>";
			echo "<tr><td>".substr($tietue, 147, 35)."</td><td>" . tilinumero_print($pankkitilino) . "</td></tr>";

			$query = "	SELECT hyvak, nimi, oletus_rahatili, oletus_kulutili, yhtio
						FROM yriti
						WHERE tilino = '$pankkitilino'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font class='message'>".t("Tiliä")." '$pankkitilino' ".t("ei löytynyt")."!</font><br>";
				$toim = 'E';
			}
			else {
				$yritirow = mysql_fetch_assoc($result);

				echo "<tr><td colspan='5'><font class='message'>".t("tili löytyi")." $yritirow[yhtio] ".t("vastuussa")." '$yritirow[hyvak]'</font><br>";

				if (strlen($yritirow["oletus_rahatili"]) == 0) { // Tarkistetaan tilit
					echo "<font class='message'>".t("Tililtä puuttuu kulu tai rahatili")."</font>";
					$toim = 'E';
				}
				else {
					echo "<font class='message'>".t("Tilin rahatili on")." '$yritirow[oletus_rahatili]' ".t("ja kulutili")." '$yritirow[oletus_kulutili]'</font><br>";
				}

				$query = "	SELECT *
							FROM yhtio
							WHERE yhtio = '$yritirow[yhtio]'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) != 1) {
					echo "<font class='message'>".t("Yritystä")." '$yritirow[yhtio]' ".t("ei löytynyt")."!</font><br>";
					$toim = 'E';
				}
				else {
					$yhtiorow = mysql_fetch_assoc ($result);

					$query = "	SELECT *
								FROM yhtion_parametrit
								WHERE yhtio='$yhtiorow[yhtio]'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 1) {
						$yhtion_parametritrow = mysql_fetch_assoc($result);

						// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
						foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
							$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
						}
					}

					echo "<font class='message'>".t("Yritys löytyi")." $yhtiorow[nimi] ".t("ostovelat")." '$yhtiorow[ostovelat]'</font><br>";
					$toim = "K";
				}
			}

			// Jos perustietoja ei löydy lopetetaan heti!
			if ($toim != 'K') {
				exit;
			}

			echo "</td></tr></table><br>";
			echo "<table><tr><th>".t("Pvm")."</th><th>".t("Maksaja")."</th><th>".t("Maksajan tilinumero")."</th><th>".t("Summa")."</th><th></th></tr>";
			break;

		case 'T10' :

			$tiliotedataid = $tiliotedatarow['tunnus'];
			$tiliotedatape = $tiliotedatarow['perheid'];
			$tiliotedataka = $tiliotedatarow['kasitelty'];
			$tiliotedatatu = $tiliotedatarow['tiliointitunnus'];

			echo "<tr>";
			$taso = substr($tietue, 187, 1);
			//echo "Taso: '" . $taso . "'<br>";
			$pvm = substr($tietue, 42, 6);

			if ($taso == '0') $turvapvm = $pvm; // Osuuspankki ei lähetä päiväystä kuin täällä

			if ($pvm == '000000') {
				$pvm = $turvapvm;
				echo "<td>".pankkipvm($pvm)."*</td>";
			}
			else {
				echo "<td>".pankkipvm($pvm)."</td>";
			}

			//echo "Etumerkki: '" .  $etumerkki . "'<br>";
			$etumerkki = substr($tietue, 87, 1);
			$maara = substr($tietue, 88, 18) / 100;
			$kohdm = 0; // määrä valuutassa

			if ($etumerkki == '-') {
				$maara *= -1;
			}

			$maksaa = substr($tietue, 108, 35);
			$vientiselite = $maksaa; // Ei ole muutakaan infoa!?
			$tilino = substr($tietue, 144, 14);

			echo "<td>$maksaa</td>";
			echo "<td>".tilinumero_print($tilino)."</td>";
			echo "<td align='right'>".sprintf("%01.2f",$maara)."</td>";

			// Tama on todellinen tapahtuma
			if ($taso == '1' and $tiliotedataka == '0000-00-00') {

				$viite = substr($tietue, 159, 20);
				while (strlen($viite) > 0 and substr($viite, 0, 1) == 0) { // Etunollat pois
					$viite = substr($viite, 1);
				}

				echo "<td>";

				if ($viite > 0) {
					echo "<font class='message'>Pupesoft: ".t("Maksukandidaatti viiteno")."</font><br>";

					$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
								FROM lasku
								WHERE viite = '$viite'
								and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))
								and yhtio = '$yritirow[yhtio]'
								and tila = 'Q'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) != 1) {
							echo "<font class='message'>Pupesoft: ".t("Sopivaa maksua viitteella")." '$viite' ".t("ei löytynyt")."!</font><br>";
					}
					else {
						$loytyi = 1;
					}
				}

				if ($loytyi == 0) {
					echo "<font class='message'>Pupesoft: ".t("Maksukandidaatti nimi & määrä")."</font><br>";

					// tarkka 35 merkkiä <-> 35 merkkiä nimihaku
					$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
								FROM lasku
								WHERE left(nimi, 35) = left('$maksaa', 35)
								and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))
								and yhtio 	= '$yritirow[yhtio]'
								and tila 	= 'Q'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) != 1) {
						// tarkka 35 merkkiä <-> 35 merkkiä NIMI ja NIMITARK haku
						$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
									FROM lasku
									WHERE left(concat_ws(' ', nimi, nimitark), 35) = left('$maksaa', 35)
									and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))
									and yhtio 	= '$yritirow[yhtio]'
									and tila 	= 'Q'";
						$result = pupe_query($query);
					}

					if (mysql_num_rows($result) != 1) {
						// kokeillaan samalla kaavalla kun lum2 rivissä
						$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
									FROM lasku
									WHERE replace(left(concat_ws(' ', if(pankki_haltija != '', pankki_haltija, concat_ws(' ', nimi, if(nimitark!='', nimitark, NULL))), osoite, postitp), 30), '&', ' ') = left('$maksaa', 30)
									and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))
									and yhtio	= '$yritirow[yhtio]'
									and tila 	= 'Q'";
						$result = pupe_query($query);
					}

					if (mysql_num_rows($result) != 1) {
						// poistetaan oy:t ja ab:t
						$maksaa = trim(preg_replace('/\b(OY|AB)\b/i', '', strtoupper($maksaa)));

						$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
									FROM lasku
									WHERE left(nimi,35) LIKE '%$maksaa%'
									and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))
									and yhtio	= '$yritirow[yhtio]'
									and tila 	= 'Q'";
						$result = pupe_query($query);
					}

					if (mysql_num_rows($result) != 1) {
						// konvertoidaan ääkköset
						$maksaa = str_replace ("å", "a", $maksaa);
						$maksaa = str_replace ("ä", "a", $maksaa);
						$maksaa = str_replace ("ö", "o", $maksaa);
						$maksaa = str_replace ("Å", "A", $maksaa);
						$maksaa = str_replace ("Ä", "A", $maksaa);
						$maksaa = str_replace ("Ö", "O", $maksaa);

						$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
									FROM lasku
									WHERE replace(replace(replace(replace(replace(replace(left(nimi,35),'å','a'), 'ä','a'), 'ö','o'), 'Å','A'), 'Ä','A'), 'Ö','O') LIKE '%$maksaa%'
									and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))
									and yhtio 	= '$yritirow[yhtio]'
									and tila 	= 'Q'";
						$result = pupe_query($query);
					}

					if (mysql_num_rows($result) != 1) {
						echo "<font class='message'>Pupesoft: ".t("Sopivaa maksua")." '$maksaa' & '$maara' ".t("ei löytynyt")."</font><br>";
					}
					else {
						$loytyi = 1;
					}
				}

				if ($loytyi == 1) {
					$trow = mysql_fetch_assoc ($result);
					echo "<font class='message'>Pupesoft: ".t("maksu löytyi")." '$trow[tunnus]' '$trow[nimi]'</font><br>";
					require "inc/teemaksutosite.inc";
				}
				else {
					echo "<font class='message'>Pupesoft: ".t("maksu ei löydy")."!</font><br>";
					require "inc/teeselvittely.inc";
				}
				echo "</td></tr>";
			}
			else {
				echo "<td>";

				if ($taso != '1') {
					echo "<font class='message'>".t("Tapahtumat yhteensä")."</font><br>";
				}

				if ($tiliotedataka != '0000-00-00') {
					$query = "	SELECT tiliointi.tilino tilino, tili.nimi nimi, concat_ws('@',korjattu, korjausaika) korjattu, ltunnus
								FROM tiliointi
								LEFT JOIN tili on tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
								WHERE tiliointi.tunnus = '$tiliotedatatu'";
					$tiliointiresult = pupe_query($query);

					if ($tiliointirow = mysql_fetch_assoc($tiliointiresult)) {
						echo "<font class='message'>".t("Tapahtuma on käsitelty"). " ". tv1dateconv($tiliotedataka) ."<br>";
						echo t("Alkuperäinen tiliöinti on")." <a href='muutosite.php?tee=E&tunnus=$tiliointirow[ltunnus]'>$tiliointirow[tilino]/$tiliointirow[nimi]</a></font><br>";

						if ($tiliointirow['korjattu'] != "0000-00-00 00:00:00" and $tiliointirow['korjattu'] != "@0000-00-00 00:00:00") {
							echo "<font class='message'>".t("Tiliöinti korjattu")." $tiliointirow[korjattu]</font><br>";
						}
					}

				}
				echo "</td></tr>";
			}
		break;
	}
?>