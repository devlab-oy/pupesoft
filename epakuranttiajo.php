<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {

		require ("inc/parametrit.inc");

		echo "<font class='head'>".t("Epäkuranttiajo")."</font><hr>";

		echo "<br><form id='valinta' name = 'valinta' method='post'>";
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
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
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

		// Haetaan kaikki saldolliset tuotteet
		$query  = "	SELECT tuote.tuoteno,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					sum(tuotepaikat.saldo) saldo
					FROM tuote
					JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					AND tuote.ei_saldoa = ''
					AND tuote.epakurantti100pvm = '0000-00-00'
					AND tuote.sarjanumeroseuranta NOT IN ('S','U','G')
					GROUP BY tuoteno, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm
					HAVING saldo > 0";
		$epakurantti_result = mysql_query($query) or pupe_error($query);

		if (!$php_cli) {
			echo "<br><table>";
			echo "<tr>";
			echo "<th>".t("Tuote")."</th>";
			echo "<th>".t("Viimeisin saapuminen")."</th>";
			echo "<th>".t("Viimeisin laskutus")."</th>";
			echo "<th>".t("Aika viimeisimmästä tapahtumasta")."</th>";
			echo "<th>".t("Epäkurattitaso")."</th>";

			if (isset($tee) and $ajo_tee == "EPAKURANTOI") {
				echo "<th></th>";
			}

			echo "</tr>";
		}

		while ($epakurantti_row = mysql_fetch_assoc($epakurantti_result)) {

			if ($php_cli and $kaikkiepakur != "") {
				$tee 	 = $kaikkiepakur;
				$tuoteno = $epakurantti_row["tuoteno"];

				require("epakurantti.inc");

				echo "Tuotteen $epakurantti_row[tuoteno], laitetaan $tee epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
			}
			else {
				// Haetaan tuotteen viimeisin tulo
				$query  = "	SELECT laadittu
							FROM tapahtuma
							WHERE yhtio = '$kukarow[yhtio]'
							AND laji in ('tulo', 'valmistus')
							AND tuoteno = '$epakurantti_row[tuoteno]'
							ORDER BY laadittu DESC
							LIMIT 1;";
				$tapres = mysql_query($query) or pupe_error($query);

				if (!$tulorow = mysql_fetch_assoc($tapres)) {
					// Jos ei löydy tuloa, laitetaan jotain vanhaa
					$tulorow = array("laadittu" => "1970-01-01");
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

					if ($php_cli or (isset($tee) and $ajo_tee == "EPAKURANTOI")) {
						$tee = "paalle";
						require ("epakurantti.inc");

						if ($php_cli) echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 30kk vanha, laitetaan 100% epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
					}

					$mikataso = 100;
				}
				// jos yli 24 kuukautta --> 50% epäkurantiksi
				elseif ($tulo > 730 and $lasku > 730 and $epakurantti_row["epakurantti50pvm"] == "0000-00-00") {

					if ($php_cli or (isset($tee) and $ajo_tee == "EPAKURANTOI")) {
						$tee = "puolipaalle";
						require ("epakurantti.inc");

						if ($php_cli) echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 24kk vanha, laitetaan 50% epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
					}

					$mikataso = 50;
				}
				// jos yli 18 kuukautta --> 25% epäkurantiksi
				elseif ($tulo > 547 and $lasku > 547 and $epakurantti_row["epakurantti25pvm"] == "0000-00-00") {

					if ($php_cli or (isset($tee) and $ajo_tee == "EPAKURANTOI")) {
						$tee = "25paalle";
						require ("epakurantti.inc");

						if ($php_cli) echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 18kk vanha, laitetaan 25% epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
					}

					$mikataso = 25;
				}

				if ($mikataso > 0) {
					echo "<tr>";
					echo "<td>{$epakurantti_row['tuoteno']}</td>";

					if ($tulorow['laadittu'] == "1970-01-01") echo "<td></td>";
					else echo "<td>{$tulorow['laadittu']}</td>";

					if ($laskutusrow['laadittu'] == "1970-01-01") echo "<td></td>";
					else echo "<td>{$laskutusrow['laadittu']}</td>";

					if ($mikataso == 100) echo "<td>".t("Viimeinen tapahtuma on yli 30kk vanha")."</td>";
					elseif ($mikataso == 50) echo "<td>".t("Viimeinen tapahtuma on yli 24kk vanha")."</td>";
					elseif ($mikataso == 25) echo "<td>".t("Viimeinen tapahtuma on yli 18kk vanha")."</td>";

					echo "<td>{$mikataso}%</td>";

					if (isset($tee) and $ajo_tee == "EPAKURANTOI") {
						echo "<td><font class='error'>".t("Päivitetty")."!</font></td>";
					}

					echo "</tr>";
				}
			}
		}

		if (!$php_cli) {
			echo "</table>";

			echo "<br><form name = 'valinta' method='post'>";
			echo "<input type = 'hidden' name = 'ajo_tee' value = 'EPAKURANTOI'>";
			echo "<input type = 'submit' value = '".t("Tee epäkuranttiuspäivitykset")."'>";
			echo "</form><br>";

			require ("inc/footer.inc");
		}
	}
?>