<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

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

	// Tehdään kaikki tapahtumat samalle tositteelle!
	$tapahtumat_samalle_tositteelle = "kylla";
	$laskuid 		= 0;
	$php_cli 		= TRUE;
	$kaikkiepakur	= "";

	if (isset($argv[2]) and in_array($argv[2], array("25paalle", "puolipaalle", "75paalle", "paalle"))) {
		$kaikkiepakur = $argv[2];
	}

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

	while ($epakurantti_row = mysql_fetch_assoc($epakurantti_result)) {

		if ($kaikkiepakur != "") {
			$tee = $kaikkiepakur;
			$tuoteno = $epakurantti_row["tuoteno"];

			require("epakurantti.inc");

			echo "Tuotteen $epakurantti_row[tuoteno], laitetaan $tee epakurantiksi. Varastonmuutos $muutos $yhtiorow[valkoodi].\n";
		}
		else {
			// Haetaan tuotteen viimeisin tulo
			$query  = "	SELECT laadittu
						FROM tapahtuma
						WHERE yhtio = '$kukarow[yhtio]'
						AND tuoteno = '$epakurantti_row[tuoteno]'
						AND laji in ('tulo', 'valmistus')
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
						AND tuoteno = '$epakurantti_row[tuoteno]'
						AND laji = 'laskutus'
						ORDER BY laadittu DESC
						LIMIT 1;";
			$tapres = mysql_query($query) or pupe_error($query);

			if (!$laskutusrow = mysql_fetch_assoc($tapres)) {
				// Jos ei löydy laskua, laitetaan jotain vanhaa
				$laskutusrow = array("laadittu" => "1970-01-01");
			}

			list($vv1, $kk1, $pp1) = explode("-", $tulorow["laadittu"]);
			list($vv2, $kk2, $pp2) = explode("-", $laskutusrow["laadittu"]);

			$today = (int) date("U");
			$viimeinen_tulo = (int) date("U", mktime(0, 0, 0, $kk1, $pp1, $vv1));
			$viimeinen_laskutus = (int) date("U", mktime(0, 0, 0, $kk2, $pp2, $vv2));

			// Lasketaan monta päivää on kulunut viimeisestä tulosta / laskutuksesta
			$tulo = ($today - $viimeinen_tulo) / 60 / 60 / 24;
			$lasku = ($today - $viimeinen_laskutus) / 60 / 60 / 24;

			$tuoteno = $epakurantti_row["tuoteno"];
			$tee = "";

			// jos yli 30 kuukautta --> 100% epäkurantiksi
			if ($tulo > 913 and $lasku > 913 and $epakurantti_row["epakurantti100pvm"] == "0000-00-00") {
				$tee = "paalle";
				require ("epakurantti.inc");

				echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 30kk vanha, laitetaan 100% epakurantiksi. Varastonmuutos $muutos $yhtiorow[valkoodi].\n";
			}
			// jos yli 24 kuukautta --> 50% epäkurantiksi
			elseif ($tulo > 730 and $lasku > 730 and $epakurantti_row["epakurantti50pvm"] == "0000-00-00") {
				$tee = "puolipaalle";
				require ("epakurantti.inc");

				echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 24kk vanha, laitetaan 50% epakurantiksi. Varastonmuutos $muutos $yhtiorow[valkoodi].\n";
			}
			// jos yli 18 kuukautta --> 25% epäkurantiksi
			elseif ($tulo > 547 and $lasku > 547 and $epakurantti_row["epakurantti25pvm"] == "0000-00-00") {
				$tee = "25paalle";
				require ("epakurantti.inc");

				echo "Tuotteen $epakurantti_row[tuoteno] viimeinen tapahtuma on yli 18kk vanha, laitetaan 25% epakurantiksi. Varastonmuutos $muutos $yhtiorow[valkoodi].\n";
			}
		}
	}
?>