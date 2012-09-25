<?php

	require("inc/parametrit.inc");

	enable_ajax();

	if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	$muuttujien_alustus = array(
		'tilinalku' => "string",
		'tilinloppu' => "string",
		'tilinimi' => "string",
		'tilino' => "string",
		'prosentti' => "float",
		'tee' => "string"
	);

	foreach ($muuttujien_alustus as $muuttuja => $tyyppi) {
		if (!isset(${$muuttuja})) settype(${$muuttuja}, $tyyppi);
	}

	echo "<font class='head'>",t("Sosiaalikulujen laskenta"),"</font><hr>\n";

	echo "<form method='post' action='' name='sosiaali'>";
	echo "<table>";

	echo "<tr><th>",t("Tilinumero"),"</th><td width='200' valign='top'>",livesearch_kentta("sosiaali", "TILIHAKU", "tilino", 170, $tilino, "EISUBMIT")," {$tilinimi}</td></tr>";

	echo "<tr><th>",t("Kustannuspaikka")."</th><td>";

	$monivalintalaatikot = array("KUSTP");
	$monivalintalaatikot_normaali = array("KUSTP");
	$noautosubmit = TRUE;
	$piirra_otsikot = FALSE;

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";

	echo "<tr><th>",t("Tilin alku"),"</th><td width='200' valign='top'>",livesearch_kentta("sosiaali", "TILIHAKU", "tilinalku", 170, $tilinalku, "EISUBMIT")," {$tilinimi}</td></tr>";
	echo "<tr><th>",t("Tilin loppu"),"</th><td width='200' valign='top'>",livesearch_kentta("sosiaali", "TILIHAKU", "tilinloppu", 170, $tilinloppu, "EISUBMIT")," {$tilinimi}</td></tr>";

	echo "<tr><th>",t("Laskentaprosentti"),"</th><td><input type='text' name='prosentti' value='{$prosentti}' /></td></tr>";

	echo "<tr><td class='back' colspan='2'>";
	echo "<input type='hidden' name='tee' value='laske' />";
	echo "<input type='submit' value='",t("Laske"),"' />";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	if ($tee == 'laske') {

		echo "<br /><font class='head'>",t("Hakutulos"),"</font><hr>";

		if ($tilinalku == '' and $tilinloppu == '') {
			echo "<font class='error'>",t("Pitää syöttää ainakin yksi tili"),".</font>";
		}
		elseif ($prosentti == '' or $prosentti == 0) {
			echo "<font class='error'>",t("Laskentaprosentti pitää syöttää ja se ei saa olla tyhjä tai nolla"),".</font>";
		}
		else {

			$groupby = "";

			if (trim($tilinloppu) == '') {
				$tilinloppu = $tilinalku;
			}

			if (trim($tilinalku) == '') {
				$tilinalku = $tilinloppu;
			}

			if (trim($tilinalku) > trim($tilinloppu)) {
				$swap = $tilinalku;
				$tilinalku = $tilinloppu;
				$tilinloppu = $swap;
			}

			$query = "	SELECT tiliointi.tilino, tiliointi.kustp, SUM(tiliointi.summa) tilisaldo
						FROM tiliointi
						LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tiliointi.yhtio AND kustannuspaikka.tunnus = tiliointi.kustp)
						WHERE tiliointi.yhtio  = '{$kukarow['yhtio']}'
						AND tiliointi.korjattu = ''
						AND tiliointi.tilino BETWEEN '{$tilinalku}' AND '{$tilinloppu}'
						GROUP BY tiliointi.tilino, tiliointi.kustp
						HAVING tilisaldo != 0
						ORDER BY tiliointi.tilino, kustannuspaikka.koodi+0 ASC";
			$result = pupe_query($query);

			$prosentti = (float) $prosentti;
			$yhteensa1 = $yhteensa2 = $yhteensa3 = array();

			echo "<table><tr><td class='back'>";

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Tili"),"</th>";
			echo "<th>",t("Saldo"),"</th>";
			echo "<th>",t("Kustp"),"</th>";
			echo "</tr>";

			while ($row = mysql_fetch_assoc($result)) {

				$saldo1 = $row['tilisaldo'];

				echo "<tr>";
				echo "<td>{$row['tilino']}</td>";
				echo "<td>{$saldo1}</td>";

				$tarkenne = array('koodi' => "", 'nimi' => "");

				if ($row['kustp'] != '') {

					$query2 = "	SELECT nimi, koodi
								FROM kustannuspaikka
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$row['kustp']}'";
					$result2 = pupe_query($query2);
					$tarkenne_row = mysql_fetch_assoc($result2);

					$tarkenne['koodi'] = $tarkenne_row['koodi'];
					$tarkenne['nimi'] = $tarkenne_row['nimi'];

					if ($tarkenne_row["nimi"] == '') {
						$tarkenne["nimi"] = t("Ei kustannuspaikkaa");
					}
				}

				echo "<td>{$tarkenne['koodi']} {$tarkenne['nimi']}</td>";

				if (!isset($yhteensa1[$row['tilino']])) $yhteensa1[$row['tilino']] = 0;
				$yhteensa1[$row['tilino']] += $row['tilisaldo'];

				echo "</tr>";
			}

			echo "</table>";

			echo "</td><td class='back'>";

			mysql_data_seek($result, 0);

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Kirjaustili"),"</th>";
			echo "<th>",t("Saldo"),"</th>";
			echo "<th>",t("Kustp"),"</th>";
			echo "</tr>";

			while ($row = mysql_fetch_assoc($result)) {

				echo "<tr>";

				echo "<td class='spec'>{$tilino}</td>";

				$saldo2 = round(($prosentti / 100) * $row['tilisaldo'], 2);

				echo "<td class='spec'>{$saldo2}</td>";

				$tarkenne = array('koodi' => "", 'nimi' => "");

				if ($row['kustp'] != '') {

					$query2 = "	SELECT nimi, koodi
								FROM kustannuspaikka
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$row['kustp']}'";
					$result2 = pupe_query($query2);
					$tarkenne_row = mysql_fetch_assoc($result2);

					$tarkenne['koodi'] = $tarkenne_row['koodi'];
					$tarkenne['nimi'] = $tarkenne_row['nimi'];

					if ($tarkenne_row["nimi"] == '') {
						$tarkenne["nimi"] = t("Ei kustannuspaikkaa");
					}
				}

				echo "<td class='spec'>{$tarkenne['koodi']} {$tarkenne['nimi']}</td>";

				if (!isset($yhteensa2[$tarkenne['koodi']])) $yhteensa2[$tarkenne['koodi']] = 0;
				$yhteensa2[$tarkenne['koodi']] += $saldo2;

				echo "</tr>";
			}

			echo "</table>";
			echo "</td><td class='back'>";

			mysql_data_seek($result, 0);

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Kirjaustili"),"</th>";
			echo "<th>",t("Saldo"),"</th>";
			echo "<th>",t("Kustp"),"</th>";
			echo "</tr>";

			while ($row = mysql_fetch_assoc($result)) {

				echo "<tr>";

				$saldo3 = -1 * round(($prosentti / 100) * $row['tilisaldo'], 2);

				$tarkenne = array('koodi' => "", 'nimi' => "");

				if (count($mul_kustp) > 0 and $mul_kustp[0] !='') {

					$query2 = "	SELECT nimi, koodi
								FROM kustannuspaikka
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$mul_kustp[0]}'";
					$result2 = pupe_query($query2);
					$tarkenne_row = mysql_fetch_assoc($result2);

					$tarkenne['koodi'] = $tarkenne_row['koodi'];
					$tarkenne['nimi'] = $tarkenne_row['nimi'];

					if ($tarkenne_row["nimi"] == '') {
						$tarkenne["nimi"] = t("Ei kustannuspaikkaa");
					}
				}

				echo "<td class='spec'>{$tilino}</td>";

				if (!isset($yhteensa3[$tarkenne['koodi']])) $yhteensa3[$tarkenne['koodi']] = 0;
				$yhteensa3[$tarkenne['koodi']] += $saldo3;

				echo "<td class='spec'>{$saldo3}</td>";
				echo "<td class='spec'>{$tarkenne['koodi']} {$tarkenne['nimi']}</td>";

				echo "</tr>";
			}

			echo "</table>";

			echo "</td></tr><tr><td class='back'>";

			for ($i = 1; $i < 4; $i++) {

				$summaus = 0;

				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Yhteensä"),"</th>";
				echo "<th></th>";
				echo "<th></th>";
				echo "</tr>";

				foreach (${"yhteensa{$i}"} as $koodi => $arvo) {

					echo "<tr>";

					switch ($i) {
						case '1':
							echo "<td>{$koodi}</td>";
							echo "<td>{$arvo}</td>";
							echo "<td></td>";
							break;
						case '2':
						case '3':
							echo "<td>{$tilino}</td>";
							echo "<td>{$arvo}</td>";

							$query2 = "	SELECT nimi, koodi
										FROM kustannuspaikka
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND koodi = '{$koodi}'";
							$result2 = pupe_query($query2);
							$tarkenne_row = mysql_fetch_assoc($result2);

							$tarkenne['koodi'] = $tarkenne_row['koodi'];
							$tarkenne['nimi'] = $tarkenne_row['nimi'];

							if ($tarkenne_row["nimi"] == '') {
								$tarkenne["nimi"] = t("Ei kustannuspaikkaa");
							}

							echo "<td>{$tarkenne['koodi']} {$tarkenne['nimi']}</td>";
							break;
					}

					$summaus += $arvo;

					echo "</tr>";
				}

				echo "<tr>";
				echo "<th>",t("Yhteensä"),"</th>";
				echo "<td>{$summaus}</td>";
				echo "<td></td>";
				echo "</tr>";

				echo "</table>";

				if ($i < 3) echo "</td><td class='back'>";

			}

			echo "</td></tr></table>";
		}

	}

	require('inc/footer.inc');
