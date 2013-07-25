<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
	}

	if (trim($argv[1]) == '') {
		die ("Et antanut lähettävää yhtiötä!\n");
	}

	if (trim($argv[2]) == '') {
		die ("Et antanut vastaanottavaa yhtiötä!\n");
	}

	date_default_timezone_set("Europe/Helsinki");

	$locktime = 5400;
	$lockfile = "/tmp/##hinnat_cron.lock";

	// jos meillä on lock-file ja se on alle 90 minuuttia vanha (90 minsaa ku backuppia odotellessa saattaa tunti vierähtää aika nopeasti)
	if (file_exists($lockfile)) {
		$locktime_calc = mktime() - filemtime($lockfile);

		if ($locktime_calc < $locktime) {
			exit("Hintojen päivitys käynnissä, odota hetki!");
		}
		else {
			exit("VIRHE: Hintojen päivitys jumissa! Ota yhteys tekniseen tukeen!!!");
		}
	}

	touch($lockfile);

	// lisätään includepathiin pupe-root
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	// otetaan tietokanta connect ja funktiot
	require("inc/connect.inc");
	require("inc/functions.inc");

	$mista_yhtio = mysql_escape_string(trim($argv[1]));
	$mihin_yhtio = mysql_escape_string(trim($argv[2]));

	$yhtiorow = hae_yhtion_parametrit($mista_yhtio);

	// Haetaan kukarow
	$query = "	SELECT *
				FROM kuka
				WHERE yhtio = '{$mista_yhtio}'
				AND kuka = 'admin'";
	$kukares = pupe_query($query);
	$kukarow = mysql_fetch_assoc($kukares);

	$mista_yhtion_toimittajan_tunnus = 27371;
	$mihin_yhtion_asiakkaan_tunnus = 101850;

	$datetime_checkpoint = date('2013-07-25 11:00:00');

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$mista_yhtio}'
				AND tunnus = $mihin_yhtion_asiakkaan_tunnus";
	$asiakasres = pupe_query($query);
	$asiakasrow = mysql_fetch_assoc($asiakasres);

	// Haetaan vain tuotteet mitkä vastaanottava yhtiö ostaa lähettävältä yhtiöltä
	$query = "	SELECT GROUP_CONCAT(DISTINCT CONCAT('\'', tuoteno, '\'')) AS tuotteet
				FROM tuotteen_toimittajat
				WHERE yhtio = '{$mihin_yhtio}'
				AND liitostunnus = {$mista_yhtion_toimittajan_tunnus}";
	$mihin_tuoteres = pupe_query($query);
	$mihin_tuoterow = mysql_fetch_assoc($mihin_tuoteres);

	$laskurow = array(
		'liitostunnus' => $mihin_yhtion_asiakkaan_tunnus,
		'valkoodi' => $asiakasrow['valkoodi'],
		'maa' => $asiakasrow['maa'],
		'ytunnus' => $asiakasrow['ytunnus'],
	);

	$tuotteet = $ryhmat = array();

	$query = "	SELECT group_concat(parent.tunnus) tunnukset
				FROM puun_alkio
				JOIN dynaaminen_puu AS node ON (puun_alkio.yhtio = node.yhtio and puun_alkio.laji = node.laji and puun_alkio.puun_tunnus = node.tunnus)
				JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 0)
				WHERE puun_alkio.yhtio = '{$mista_yhtio}'
				AND puun_alkio.laji    = 'ASIAKAS'
				AND puun_alkio.liitos  = '{$asiakasrow['tunnus']}'";
	$result2 = pupe_query($query);
	$puun_tunnukset = mysql_fetch_assoc($result2);

	$asiakkaan_puiden_tunnukset = $puun_tunnukset !== NULL ? " OR asiakas_segmentti IN ({$puun_tunnukset['tunnukset']})" : "";

	$query = "	SELECT hinta, ryhma, tuoteno, laji
				FROM asiakashinta
				WHERE yhtio = '{$mista_yhtio}'
				AND (asiakas = {$asiakasrow['tunnus']} OR ytunnus = '{$asiakasrow['ytunnus']}' OR asiakas_ryhma = '{$asiakasrow['ryhma']}' OR piiri = '{$asiakasrow['piiri']}' {$asiakkaan_puiden_tunnukset})
				AND minkpl < 2
				AND (
					(alkupvm > LEFT('{$datetime_checkpoint}', 10) AND alkupvm <= CURRENT_DATE) OR
					(loppupvm < CURRENT_DATE AND LEFT('{$datetime_checkpoint}', 10) <= loppupvm) OR
					muutospvm >= '{$datetime_checkpoint}'
					)";
	$asiakashinta_res = pupe_query($query);

	while ($asiakashinta_row = mysql_fetch_assoc($asiakashinta_res)) {
		if ($asiakashinta_row['ryhma'] != "") {
			$ryhmat[$asiakashinta_row['ryhma']] = 0;
		}
		elseif ($asiakashinta_row['tuoteno'] != "") {
			$tuotteet[$asiakashinta_row['tuoteno']] = 0;
		}
	}

	$query = "	SELECT ryhma, tuoteno
				FROM asiakasalennus
				WHERE yhtio = '{$mista_yhtio}'
				AND (asiakas = {$asiakasrow['tunnus']} OR ytunnus = '{$asiakasrow['ytunnus']}' OR asiakas_ryhma = '{$asiakasrow['ryhma']}' OR piiri = '{$asiakasrow['piiri']}' {$asiakkaan_puiden_tunnukset})
				AND minkpl < 2
				AND (
					(alkupvm > LEFT('{$datetime_checkpoint}', 10) AND alkupvm <= CURRENT_DATE) OR
					(loppupvm < CURRENT_DATE AND LEFT('{$datetime_checkpoint}', 10) <= loppupvm) OR
					muutospvm >= '{$datetime_checkpoint}'
					)";
	$asiakasalennus_res = pupe_query($query);

	while ($asiakasalennus_row = mysql_fetch_assoc($asiakasalennus_res)) {
		if ($asiakasalennus_row['ryhma'] != "") {
			$ryhmat[$asiakasalennus_row['ryhma']] = 0;
		}
		elseif ($asiakasalennus_row['tuoteno'] != "") {
			$tuotteet[$asiakasalennus_row['tuoteno']] = 0;
		}
	}

	$query = "	SELECT tuoteno
				FROM hinnasto
				WHERE yhtio = '{$mista_yhtio}'
				AND minkpl < 2
				AND tuoteno IN ({$mihin_tuoterow['tuotteet']})
				AND (
					(alkupvm > LEFT('{$datetime_checkpoint}', 10) AND alkupvm <= CURRENT_DATE) OR
					(loppupvm < CURRENT_DATE AND LEFT('{$datetime_checkpoint}', 10) <= loppupvm) OR
					muutospvm >= '{$datetime_checkpoint}'
					)";
	$hinnasto_res = pupe_query($query);

	while ($hinnasto_row = mysql_fetch_assoc($hinnasto_res)) {
		$tuotteet[$hinnasto_row['tuoteno']] = 0;
	}

	foreach ($ryhmat as $ryhma => $devnull) {

		$query = "	SELECT tuoteno
					FROM tuote
					WHERE yhtio = '{$mista_yhtio}'
					AND status != 'P'
					AND tuotetyyppi NOT in ('A','B')
					AND aleryhma = '{$ryhma}'
					AND tuoteno IN ({$mihin_tuoterow['tuotteet']})";
		$ryhmares = pupe_query($query);

		while ($ryhmarow = mysql_fetch_assoc($ryhmares)) {
			$tuotteet[$ryhmarow['tuoteno']] = 0;
		}
	}

	$query = "	SELECT tuoteno
				FROM tuote
				WHERE yhtio = '{$mista_yhtio}'
				AND status != 'P'
				AND tuotetyyppi NOT in ('A','B')
				AND muutospvm >= '{$datetime_checkpoint}'
				AND tuoteno IN ({$mihin_tuoterow['tuotteet']})
				ORDER BY muutospvm, tuoteno";
	$tuoteres = pupe_query($query);

	while ($tuoterow = mysql_fetch_assoc($tuoteres)) {
		$tuotteet[$tuoterow['tuoteno']] = 0;
	}

	foreach ($tuotteet as $tuoteno => $devnull) {

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '{$mista_yhtio}'
					AND tuoteno = '{$tuoteno}'";
		$tuoteres = pupe_query($query);
		$tuoterow = mysql_fetch_assoc($tuoteres);

		echo "tuote: {$tuoterow['tuoteno']}\n";

		$query = "	SELECT tunnus
					FROM tuotteen_toimittajat
					WHERE yhtio = '{$mihin_yhtio}'
					AND tuoteno = '{$tuoteno}'
					AND liitostunnus = {$mista_yhtion_toimittajan_tunnus}";
		$mihin_tuoteres = pupe_query($query);

		while ($mihin_tuoterow = mysql_fetch_assoc($mihin_tuoteres)) {

			// Lasketaan ja päivitetään ostohinta
			list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $tuoterow, 1, '', '', array());

			echo "Laskettu hinta: $hinta\n";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$hinta *= (1 - $ale["ale{$alepostfix}"] / 100);
			}

			echo "Alennettu hinta: $hinta\n";

			// Päivitetään ostohinta
			$query = "	UPDATE tuotteen_toimittajat SET
						ostohinta = '{$hinta}'
						WHERE yhtio = '{$mihin_yhtio}'
						AND tunnus = '{$mihin_tuoterow['tunnus']}'";
			pupe_query($query);

			echo "\n";
		}
	}

	// Haetaan saldot tuotteille, joille on tehty tietyn ajan sisällä tilausrivi tai tapahtuma
	$query =  "(SELECT DISTINCT tapahtuma.tuoteno
				FROM tapahtuma
				JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
					AND tuote.tuoteno = tapahtuma.tuoteno
					AND tuote.status != 'P'
					AND tuote.tuotetyyppi NOT in ('A','B')
					AND tuote.tuoteno != ''
					AND tuote.ei_saldoa = '')
				WHERE tapahtuma.yhtio = '{$mista_yhtio}'
				AND tapahtuma.tuoteno IN ({$mihin_tuoterow['tuotteet']})
				AND tapahtuma.laadittu >= '{$datetime_checkpoint}')

				UNION

				(SELECT DISTINCT tilausrivi.tuoteno
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
					AND tuote.tuoteno = tilausrivi.tuoteno
					AND tuote.status != 'P'
					AND tuote.tuotetyyppi NOT in ('A','B')
					AND tuote.tuoteno != ''
					AND tuote.ei_saldoa = '')
				WHERE tilausrivi.yhtio = '{$mista_yhtio}'
				AND tilausrivi.tyyppi NOT IN ('D','O')
				AND tilausrivi.tuoteno IN ({$mihin_tuoterow['tuotteet']})
				AND tilausrivi.laadittu >= '{$datetime_checkpoint}')";
	$result = pupe_query($query);

	while ($row = mysql_fetch_assoc($result)) {
		list($saldo, $hyllyssa, $myytavissa, $devnull) = saldo_myytavissa($row['tuoteno']);

		echo "tuotteen $row[tuoteno] tehdas_saldo $myytavissa\n";

		$query = "	UPDATE tuotteen_toimittajat SET
					tehdas_saldo = '{$myytavissa}'
					WHERE yhtio = '{$mihin_yhtio}'
					AND tuoteno = '{$row['tuoteno']}'
					AND liitostunnus = {$mista_yhtion_toimittajan_tunnus}";
		pupe_query($query);
	}

	unlink($lockfile);
