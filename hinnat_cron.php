<?php

	// Nämä pitää setata kuntoon
	$locktime = 5400;
	$lockfile = "/tmp/##hinnat_cron.lock";
	$mista_yhtion_toimittajan_tunnus = 27371;
	$mihin_yhtion_asiakkaan_tunnus = 101850;

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

	// jos meillä on lock-file ja se on alle 90 minuuttia vanha (90 minsaa ku backuppia odotellessa saattaa tunti vierähtää aika nopeasti)
	if (file_exists($lockfile)) {
		$locktime_calc = mktime() - filemtime($lockfile);

		if ($locktime_calc < $locktime) {
			exit("Hintojen päivitys käynnissä, odota hetki!")."\n";
		}
		else {
			exit("VIRHE: Hintojen päivitys jumissa! Ota yhteys tekniseen tukeen!!!")."\n";
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

	if (mysql_num_rows($kukares) != 1) {
		unlink($lockfile);
		exit("VIRHE: Admin käyttäjä ei löydy!\n");
	}

	$kukarow = mysql_fetch_assoc($kukares);

	// Haetaan timestamp
	$datetime_checkpoint_res = t_avainsana("HINNAT_CRON");

	if (mysql_num_rows($datetime_checkpoint_res) != 1) {
		unlink($lockfile);
		exit("VIRHE: Timestamp ei löydy avainsanoista!\n");
	}

	$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
	$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mikä tilanne on jo käsitelty
	$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$mista_yhtio}'
				AND tunnus = $mihin_yhtion_asiakkaan_tunnus";
	$asiakasres = pupe_query($query);

	if (mysql_num_rows($asiakasres) != 1) {
		unlink($lockfile);
		exit("VIRHE: Asiakas ei löydy!\n");
	}

	$asiakasrow = mysql_fetch_assoc($asiakasres);

	// Haetaan vain tuotteet mitkä vastaanottava yhtiö ostaa lähettävältä yhtiöltä
	$query = "	SELECT GROUP_CONCAT(DISTINCT CONCAT('\'', tuoteno, '\'')) AS tuotteet
				FROM tuotteen_toimittajat
				WHERE yhtio = '{$mihin_yhtio}'
				AND liitostunnus = {$mista_yhtion_toimittajan_tunnus}";
	$mihin_tuoteres = pupe_query($query);
	$mihin_tuoterow = mysql_fetch_assoc($mihin_tuoteres);

	if ($mihin_tuoterow['tuotteet'] === NULL) {
		unlink($lockfile);
		exit("VIRHE: Toimittajan tuotteita ei löydy!\n");
	}

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

	// Haetaan muuttuneet asiakashinnat
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

	// Haetaan muuttuneet asiakasalennukset
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

	// Haetaan muuttuneet hinnastohinnat
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

	// Käydään läpi kaikki muuttuneet alennusryhmät, lisätään niiden tuotteet tuote arrayseen
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

	// Vapautetaan muistia
	unset($ryhmat);

	// Haetaan kaikki muuttuneet tuotteet ja lisätään ne arrayseen
	$query = "	SELECT *
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

	// Loopataan läpi kaikki muuttuneet tuotteet
	foreach ($tuotteet as $tuoteno => $devnull) {

		// Haetaan tuotteen tiedot $mista_yhtio
		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '{$mista_yhtio}'
					AND tuoteno = '{$tuoteno}'";
		$tuoteres = pupe_query($query);
		$tuoterow = mysql_fetch_assoc($tuoteres);

		// Päivitetään myyntihinta $mihin_yhtio
		$query = "	UPDATE tuote SET
					myyntihinta = '{$tuoterow['myyntihinta']}'
					WHERE yhtio = '{$mihin_yhtio}'
					AND tuoteno = '{$tuoteno}'";
		pupe_query($query);

		// Haetaan tuotteen toimittajan liitos $mihin_yhtio
		$query = "	SELECT tunnus
					FROM tuotteen_toimittajat
					WHERE yhtio = '{$mihin_yhtio}'
					AND tuoteno = '{$tuoteno}'
					AND liitostunnus = {$mista_yhtion_toimittajan_tunnus}";
		$mihin_tuoteres = pupe_query($query);

		while ($mihin_tuoterow = mysql_fetch_assoc($mihin_tuoteres)) {

			// Lasketaan ja päivitetään ostohinta
			list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $tuoterow, 1, '', '', array());

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$hinta *= (1 - $ale["ale{$alepostfix}"] / 100);
			}

			// Päivitetään ostohinta
			$query = "	UPDATE tuotteen_toimittajat SET
						ostohinta = '{$hinta}'
						WHERE yhtio = '{$mihin_yhtio}'
						AND tunnus = '{$mihin_tuoterow['tunnus']}'";
			pupe_query($query);
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

		$query = "	UPDATE tuotteen_toimittajat SET
					tehdas_saldo = '{$myytavissa}'
					WHERE yhtio = '{$mihin_yhtio}'
					AND tuoteno = '{$row['tuoteno']}'
					AND liitostunnus = {$mista_yhtion_toimittajan_tunnus}";
		pupe_query($query);
	}

	// Kun kaikki onnistui, päivitetään lopuksi timestamppi talteen
	$query = "	UPDATE avainsana SET
				selite = '{$datetime_checkpoint_uusi}'
				WHERE yhtio = '{$mista_yhtio}'
				AND laji = 'HINNAT_CRON'";
	pupe_query($query);
	
	if (mysql_affected_rows() != 1) {
		echo "Timestamp päivitys epäonnistui!\n";
	}

	unlink($lockfile);
