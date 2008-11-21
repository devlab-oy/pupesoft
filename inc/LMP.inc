<?php
	$tunnus = substr($tietue, 0,3);
	$loytyi = 0;
	$tkesken = 1;
	switch ($tunnus) {
		case 'T03' :
			echo "<table>";
			echo "<tr><th>".t("asiakas")."</th><th>" . t("tilino") .  "</th></tr>";
			$pankkitilino = substr($tietue, 9, 14);
			echo "<tr><td>".substr($tietue, 147, 35)."</td><td>" . tilinumero_print($pankkitilino) . "</td></tr>";

			$query = "SELECT hyvak, nimi, oletus_rahatili, oletus_kulutili, yhtio
						FROM yriti
						WHERE tilino = '$tilino'";

			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font class='message'>".t("Tiliä")." '$pankkitilino' ".t("ei löytynyt")."!</font><br>";
				$toim='E';
			}
			else {
				$yritirow = mysql_fetch_array ($result);
				echo "<tr><td colspan='5'><font class='message'>".t("tili löytyi")." $yritirow[4] ".t("vastuussa")." '$yritirow[0]'</font><br>";

				if ((strlen($yritirow[2]) == 0) || (strlen($yritirow[2]) == 0)) { // Tarkistetaan tilit
					echo "<font class='message'>".t("Tililtä puuttuu kulu tai rahatili")."</font>";
					$toim='E';
				}
				else {
					echo "<font class='message'>".t("Tilin rahatili on")." '$yritirow[2]' ".t("ja kulutili")." '$yritirow[3]'</font><br>";
				}

				$query = "SELECT *
							FROM yhtio
							WHERE yhtio = '$yritirow[4]'";

				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) != 1) {
					echo "<font class='message'>".t("Yritystä")." '$yritirow[4]' ".t("ei löytynyt")."!</font><br>";
					$toim='E';
				}
				else {

					$yhtiorow = mysql_fetch_array ($result);

					$query = "	SELECT *
								FROM yhtion_parametrit
								WHERE yhtio='$yhtiorow[yhtio]'";
					$result = mysql_query($query)
							or die ("Kysely ei onnistu yhtio $query");

					if (mysql_num_rows($result) == 1) {
						$yhtion_parametritrow = mysql_fetch_array($result);
						// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
						foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
							$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
						}
					}

					echo "<font class='message'>".t("Yritys löytyi")." $yhtiorow[nimi] ".t("ostovelat")." '$yhtiorow[ostovelat]'</font><br>";
					$toim="K";
				}
			}
			if ($toim != 'K') exit; // Jos perustietoja ei löydy lopetetaam heti!
			echo "</td></tr></table><br>";
			echo "<table><tr><th>".t("Pvm")."</th><th>".t("Maksaja")."</th><th>".t("Maksajan tilinumero")."</th><th>".t("Summa")."</th><th></th></tr>";
			break;
		case 'T10' :
			$tiliotedataid = $tiliotedatarow['tunnus'];
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

			$etumerkki = substr($tietue, 87, 1);
			//echo "Etumerkki: '" .  $etumerkki . "'<br>";
			$maara = substr($tietue, 88, 18) / 100;
			if ($etumerkki == '-') {
				$maara *= -1;
			}
			$maksaa = substr($tietue, 108, 35);
			$vientiselite = $maksaa; // Ei ole muutakaan infoa!?
			echo "<td>$maksaa</td>";
			$tilino = substr($tietue, 144, 14);
			echo "<td>".tilinumero_print($tilino)."</td>";
			echo "<td align='right'>".sprintf("%01.2f",$maara)."</td>";
			if (($taso == '1') and ($tiliotedataka == '0000-00-00')) { // Tama on todellinen tapahtuma


				$viite = substr($tietue, 159, 20);
				while ((strlen($viite) > 0) and (substr($viite, 0, 1) == 0)) { // Etunollat pois
					$viite = substr($viite, 1);
				}
				echo "<td>";
				if ($viite > 0) {
					echo "<font class='message'>Pupesoft: ".t("maksukanditaatti viiteno")."</font><br>";
					$query = "SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
								FROM lasku
								WHERE viite = '$viite' and summa = $maara * -1 and yhtio = '$yritirow[4]' and tila = 'Q'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) != 1) {
							echo "<font class='message'>Pupesoft: ".t("Sopivaa maksua viitteella")." '$viite' ".t("ei löytynyt")."!</font><br>";
					}
					else {
						$loytyi = 1;
					}
				}
				if ($loytyi == 0) {
					echo "<font class='message'>Pupesoft: ".t("maksukanditaatti nimi & määrä")."</font><br>";
					$query = "SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
								FROM lasku
								WHERE left(nimi,35) = left('$maksaa',35) and summa = $maara * -1 and yhtio = '$yritirow[4]' and tila = 'Q'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) != 1) {
						echo "<font class='message'>Pupesoft: ".t("Sopivaa maksua")." '$maksaa' & '$maara' ".t("ei löytynyt")."</font><br>";
					}
					else {
						$loytyi = 1;
					}
				}
				if ($loytyi == 1) {
					$trow = mysql_fetch_array ($result);
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
				if ($taso != '1') echo "<font class='message'>".t("Tapahtumat yhteensä")."</font><br>";

				if ($tiliotedataka != '0000-00-00') {
					$query = "SELECT tiliointi.tilino tilino, tili.nimi nimi, concat_ws('@',korjattu, korjausaika) korjattu, ltunnus
								FROM tiliointi
								LEFT JOIN tili on tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
								WHERE tiliointi.tunnus = '$tiliotedatatu'";
					$tiliointiresult = mysql_query($query) or pupe_error($query);

					if ($tiliointirow = mysql_fetch_array($tiliointiresult)) {
						echo "<font class='message'>".t("Tapahtuma on käsitelty"). " ". tv1dateconv($tiliotedataka) ."<br>";
						echo "".t("Alkuperäinen tiliöinti on")." <a href='muutosite.php?tee=E&tunnus=$tiliointirow[ltunnus]'>$tiliointirow[tilino]/$tiliointirow[nimi]</a></font><br>";

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
