<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {

		require ("inc/parametrit.inc");

		echo "<font class='head'>".t("Epäkuranttiajo")."</font><hr>";

		echo "<br><form method='post'>";
		echo "<input type = 'hidden' name = 'ajo_tee' value = 'NAYTA'>";
		echo "<input type = 'submit' value = '".t("Näytä epäkurantoitavat tuotteet")."'>";
		echo "</form><br>";

		$php_cli 		= FALSE;
		$kaikkiepakur	= "";
	}
	else {
		if (!isset($argv[1])) {
			die ("Anna yhtio parametriksi!");
		}

		// Otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// Otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");

		// Tehdään oletukset
		$kukarow['yhtio'] = $argv[1];
		$kukarow['kuka'] = "crond";
		$yhtiorow = hae_yhtion_parametrit($argv[1]);

		$php_cli 		= TRUE;
		$kaikkiepakur	= "";

		if (isset($argv[2]) and in_array($argv[2], array("25paalle", "puolipaalle", "75paalle", "paalle"))) {
			$kaikkiepakur = $argv[2];
		}
	}

	if ($php_cli or (isset($ajo_tee) and ($ajo_tee == "NAYTA" or $ajo_tee == "EPAKURANTOI"))) {

		// Tehdään kaikki tapahtumat samalle tositteelle!
		$tapahtumat_samalle_tositteelle = "kylla";
		$laskuid = 0;
		$epa_tuotemaara = 0;

		// Haetaan kaikki saldolliset tuotteet
		$query  = "	SELECT tuote.tuoteno,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) kehahin,
					tuote.kehahin bruttokehahin,
					tuote.luontiaika,
					sum(tuotepaikat.saldo) saldo
					FROM tuote
					JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					AND tuote.ei_saldoa = ''
					AND tuote.epakurantti100pvm = '0000-00-00'
					AND tuote.sarjanumeroseuranta NOT IN ('S','U','G')
					GROUP BY 1,2,3,4,5,6,7,8
					HAVING saldo > 0
					ORDER BY tuoteno";
		$epakurantti_result = mysql_query($query) or pupe_error($query);

		if (!$php_cli) {
			echo "<br><table>";
			echo "<tr>";
			echo "<th>".t("Tuote")."</th>";
			echo "<th>".t("Viimeisin saapuminen")."</th>";
			echo "<th>".t("Viimeisin laskutus")."</th>";
			echo "<th>".t("Viim. tapahtuma")."</th>";
			echo "<th>".t("Epäkurattitaso")."</th>";
			echo "<th>".t("Saldo")."</th>";
			echo "<th>".t("Kehahin")."</th>";
			echo "<th>".t("Varastonarvo")."</th>";
			echo "<th>".t("Uusi varastonarvo")."</th>";

			if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
				echo "<th></th>";
			}

			echo "</tr>";
		}

		$vararvot_nyt = 0;
		$vararvot_sit = 0;

		while ($epakurantti_row = mysql_fetch_assoc($epakurantti_result)) {

			if ($php_cli and $kaikkiepakur != "") {
				$tee 	 = $kaikkiepakur;
				$tuoteno = $epakurantti_row["tuoteno"];

				require("epakurantti.inc");

				echo "Tuotteen $epakurantti_row[tuoteno], laitetaan $tee epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
			}
			else {

				if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
					ob_start();
				}

				// Haetaan tuotteen viimeisin tulo
				$query  = "	SELECT laadittu
							FROM tapahtuma
							WHERE yhtio = '$kukarow[yhtio]'
							AND laji in ('tulo', 'valmistus')
							AND tuoteno = '$epakurantti_row[tuoteno]'
							AND selite not like '%alkusaldo%'
							ORDER BY laadittu DESC
							LIMIT 1;";
				$tapres = mysql_query($query) or pupe_error($query);

				if (!$tulorow = mysql_fetch_assoc($tapres)) {

					if ($epakurantti_row["luontiaika"] != "0000-00-00 00:00:00") {
						// Jos ei löydy tuloa, laitetaan tuotteen luontiaika
						$tulorow = array("laadittu" => substr($epakurantti_row["luontiaika"], 0, 10));
					}
					else {
						// Jos ei löydy tuloa eikä tuotteen luontiaikaa, niin laitetaan jotain vanhaa
						$tulorow = array("laadittu" => "1970-01-01");
					}
				}

				// Haetaan tuotteen viimeisin laskutus
				$query  = "	SELECT laadittu
							FROM tapahtuma
							WHERE yhtio = '$kukarow[yhtio]'
							AND laji 	= 'laskutus'
							AND tuoteno = '$epakurantti_row[tuoteno]'
							ORDER BY laadittu DESC
							LIMIT 1;";
				$tapres = mysql_query($query) or pupe_error($query);

				if (!$laskutusrow = mysql_fetch_assoc($tapres)) {
					// Jos ei löydy laskua, laitetaan jotain vanhaa
					$laskutusrow = array("laadittu" => "1970-01-01");
				}

				list($vv1, $kk1, $pp1) = explode("-", substr($tulorow["laadittu"], 0, 10));
				list($vv2, $kk2, $pp2) = explode("-", substr($laskutusrow["laadittu"], 0, 10));

				$today = (int) date("U");

				$viimeinen_tulo = (int) date("U", mktime(0, 0, 0, $kk1, $pp1, $vv1));
				$viimeinen_laskutus = (int) date("U", mktime(0, 0, 0, $kk2, $pp2, $vv2));

				// Lasketaan monta päivää on kulunut viimeisestä tulosta / laskutuksesta
				$tulo = ($today - $viimeinen_tulo) / 60 / 60 / 24;
				$lasku = ($today - $viimeinen_laskutus) / 60 / 60 / 24;

				$tuoteno 	= $epakurantti_row["tuoteno"];
				$tee 		= "";
				$mikataso 	= 0;

				// jos yli 30 kuukautta --> 100% epäkurantiksi
				if ($tulo > 913 and $lasku > 913 and $epakurantti_row["epakurantti100pvm"] == "0000-00-00") {

					if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
						$tee = "paalle";
						require ("epakurantti.inc");

						if ($php_cli) echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 30kk vanha, laitetaan 100% epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
					}

					$mikataso = 100;
				}
				// jos yli 24 kuukautta --> 50% epäkurantiksi
				elseif ($tulo > 730 and $lasku > 730 and $epakurantti_row["epakurantti50pvm"] == "0000-00-00") {

					if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
						$tee = "puolipaalle";
						require ("epakurantti.inc");

						if ($php_cli) echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 24kk vanha, laitetaan 50% epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
					}

					$mikataso = 50;
				}
				// jos yli 18 kuukautta --> 25% epäkurantiksi
				elseif ($tulo > 547 and $lasku > 547 and $epakurantti_row["epakurantti25pvm"] == "0000-00-00") {

					if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
						$tee = "25paalle";
						require ("epakurantti.inc");

						if ($php_cli) echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 18kk vanha, laitetaan 25% epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
					}

					$mikataso = 25;
				}

				if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
					$viesti = ob_get_contents();
					ob_end_clean();
				}

				if ($mikataso > 0) {
					echo "<tr>";
					echo "<td><a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($epakurantti_row['tuoteno'])."'>{$epakurantti_row['tuoteno']}</a></td>";

					if ($tulorow['laadittu'] == "1970-01-01") echo "<td></td>";
					else echo "<td>".tv1dateconv($tulorow['laadittu'])."</td>";

					if ($laskutusrow['laadittu'] == "1970-01-01") echo "<td></td>";
					else echo "<td>".tv1dateconv($laskutusrow['laadittu'])."</td>";

					if ($mikataso == 100) echo "<td>".t("Yli 30kk sitten")."</td>";
					elseif ($mikataso == 50) echo "<td>".t("Yli 24kk sitten")."</td>";
					elseif ($mikataso == 25) echo "<td>".t("Yli 18kk sitten")."</td>";

					echo "<td align='right'>{$mikataso}%</td>";

					echo "<td align='right'>{$epakurantti_row['saldo']}</td>";
					echo "<td align='right'>".round($epakurantti_row['kehahin'],2)."</td>";

					$vararvo_nyt = round($epakurantti_row['kehahin']*$epakurantti_row['saldo'], 2);

					echo "<td align='right'>{$vararvo_nyt}</td>";

					if ($mikataso == 100) {
						$vararvo_sit = 0;
					}
					elseif ($mikataso == 50) {
						$vararvo_sit = round($epakurantti_row['bruttokehahin']*0.5*$epakurantti_row['saldo'], 2);
					}
					elseif ($mikataso == 25) {
						$vararvo_sit = round($epakurantti_row['bruttokehahin']*0.75*$epakurantti_row['saldo'], 2);
					}

					echo "<td align='right'>{$vararvo_sit}</td>";

					if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
						echo "<td>$viesti</td>";
					}

					echo "</tr>";

					$vararvot_nyt += $vararvo_nyt;
					$vararvot_sit += $vararvo_sit;

					$epa_tuotemaara++;
				}
			}
		}

		if (!$php_cli and $epa_tuotemaara > 0) {

				echo "<tr>";
				echo "<td class='tumma' colspan='7'>".t("Yhteensä").":</td>";
				echo "<td class='tumma' align='right'>$vararvot_nyt</td>";
				echo "<td class='tumma' align='right'>$vararvot_sit</td>";

				if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
					echo "<td class='tumma'></td>";
				}

				echo "</tr>";

				echo "<tr>";
				echo "<td class='tumma' colspan='8'>".t("Epäkuranttimuutos yhteensä").":</td>";
				echo "<td class='tumma' align='right'>",($vararvot_sit-$vararvot_nyt),"</td>";

				if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
					echo "<td class='tumma'></td>";
				}

				echo "</tr>";

			echo "</table>";

			echo "<br><form name = 'valinta' method='post'>";
			echo "<input type = 'hidden' name = 'ajo_tee' value = 'EPAKURANTOI'>";
			echo "<input type = 'submit' value = '".t("Tee epäkuranttiuspäivitykset")."'>";
			echo "</form><br>";

			require ("inc/footer.inc");
		}
	}
?>