<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	// Kutsutaanko CLI:st‰
	if (php_sapi_name() != 'cli') {
		die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
	}

	if (!isset($argv[1]) or $argv[1] == '') {
		die("Yhtiˆ on annettava!!");
	}

	$yhtio = $argv[1];

	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	require('inc/connect.inc');
	require('inc/functions.inc');

	$path = "/tmp/logisticar_siirto_$yhtio/";

	// Sivotaan eka vanha pois
	system("rm -rf $path");

	// Teh‰‰n uysi dirikka
	system("mkdir $path");

	$path_nimike     = $path . 'ITEM.txt';
	$path_asiakas    = $path . 'CUSTOMER.txt';
	$path_toimittaja = $path . 'VENDOR.txt';
	$path_varasto    = $path . 'BALANCE.txt';
	$path_tapahtumat = $path . 'TRANSACTION.txt';
	$path_myynti     = $path . 'ORDER.txt';

	$query = "SELECT * from yhtio where yhtio='$yhtio'";
	$res = mysql_query($query) or pupe_error($query);
	$yhtiorow = mysql_fetch_assoc($res);

	$query = "SELECT * from yhtion_parametrit where yhtio='$yhtio'";
	$res = mysql_query($query) or pupe_error($query);
	$params = mysql_fetch_assoc($res);

	$yhtiorow = array_merge($yhtiorow, $params);

	// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
	if ($yhtiorow["varaako_jt_saldoa"] != "") {
		$lisavarattu = " not in ('P') ";
	}
	else {
		$lisavarattu = " not in ('P','J','S') ";
	}

	echo "Logisticar siirto: $yhtio\n";

	//testausta varten limit
	//$limit = "limit 200";

	// Ajetaan kaikki operaatiot
	nimike($limit);
	asiakas($limit);
	toimittaja($limit);
	varasto($limit);
	varastotapahtumat($limit);
	myynti($limit);

	//Siirret‰‰n failit logisticar palvelimelle
	siirto($path);

	function siirto ($path) {
		GLOBAL $logisticar, $yhtio;

		$path_localdir 	 = "/mnt/logisticar_siirto/";
		$user_logisticar = $logisticar[$yhtio]["user"];
		$pass_logisticar = $logisticar[$yhtio]["pass"];
		$host_logisticar = $logisticar[$yhtio]["host"];
		$path_logisticar = $logisticar[$yhtio]["path"];

		unset($retval);
		system("mount -t cifs -o username=$user_logisticar,password=$pass_logisticar //$host_logisticar/$path_logisticar $path_localdir", $retval);

		if ($retval != 0) {
			echo "Mount failed! $retval\n";
		}
		else {

			unset($retval);
			system("cp -f $path/* $path_localdir", $retval);

			if ($retval != 0) {
				echo "Copy failed! $retval\n";
			}

			unset($retval);
			system("umount $path_localdir", $retval);

			if ($retval != 0) {
				echo "Unmount failed! $retval\n";
			}
		}
	}

	function nimike($limit = '') {
		global $path_nimike, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Tuotteet ...";

		$query = "	SELECT
					tuote.tuoteno        	nimiketunnus,
        			tuote.nimitys           nimitys,
			        tuote.yksikko           yksikko,
			        tuote.try				tuoteryhma,
					avainsana.selitetark	tuoteryhma_nimi,
					tuote.kustp				kustannuspaikka,
					''						toimittajatunnus,
					'0'						varastotunnus,
					'0'						toimittajannimiketunnus,
					'1'						hintayksikko,
					if(tuote.status = 'T', 'T', '') varastoimiskoodi,
			        tuote.tuotetyyppi    	nimikelaji,
			        kuka.kuka      			ostaja,
			        tuote.tuotemassa     	paino
			        FROM tuote
			        LEFT JOIN avainsana ON avainsana.selite=tuote.try and avainsana.yhtio=tuote.yhtio
					LEFT JOIN kuka ON kuka.myyja=tuote.ostajanro and kuka.yhtio=tuote.yhtio and kuka.myyja > 0
					WHERE tuote.yhtio='$yhtio'
					$limit";
		$rest = mysql_query($query) or pupe_error($query);
		$rows = mysql_num_rows($rest);

		if ($rows == 0) {
			echo "Yht‰‰n tuotetta ei lˆytynyt $query\n";
			die();
		}

		$fp = fopen($path_nimike, 'w+');

		$row = 0;

		$headers = array(
			'nimiketunnus'     			=> null,
			'nimitys'          			=> null,
			'yksikko'          			=> null,
			'tuoteryhma'       			=> null,
			'kustannuspaikka'  			=> null,
			'toimittajatunnus' 			=> null,
			'varastotunnus'    			=> null,
			'toimittajannimiketunnus' 	=> null,
			'hintayksikko'    	 		=> null,
			'varastoimiskoodi' 			=> null,
			'nimikelaji'       			=> null,
			'ostaja'           			=> null,
			'paino'            			=> null
		);

		create_headers($fp, array_keys($headers));

		while ($tuote = mysql_fetch_assoc($rest)) {

			// mones t‰m‰ on
			$row++;

			$query = "	SELECT
						liitostunnus toimittajatunnus,
						toim_tuoteno toimittajannimiketunnus
						FROM tuotteen_toimittajat
						WHERE tuoteno = '{$tuote['nimiketunnus']}'
						AND yhtio = '$yhtio'
						LIMIT 1";
			$tuot_toim_res = mysql_query($query) or pupe_error($query);
			$tuot_toim_row = mysql_fetch_assoc($tuot_toim_res);

			if (trim($tuote['varastoimiskoodi']) != '') {
				// tuotetta ei varastoida
				$tuote['varastoimiskoodi'] = '0';
			}
			else {
				$tuote['varastoimiskoodi'] = '1';
			}

			$query = "	SELECT hyllyalue, hyllynro
						from tuotepaikat
						where tuoteno = '{$tuote['tuoteno']}'
						and oletus != ''
						and yhtio='$yhtio'
						limit 1";
			$res = mysql_query($query) or pupe_error($query);
			$paikka = mysql_fetch_assoc($res);

			// mik‰ varasto
			$tuote['varastotunnus'] 			= kuuluukovarastoon($paikka['hyllyalue'], $paikka['hyllynro']);
			$tuote['toimittajatunnus'] 			= $tuot_toim_row['toimittajatunnus'];
			$tuote['toimittajannimiketunnus'] 	= $tuot_toim_row['toimittajannimiketunnus'];

			$data = array_merge($headers, $tuote);
			$data = implode("\t", $data);

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
		}

		fclose($fp);
		echo "Done.\n";
	}

	function asiakas($limit = '') {
		global $path_asiakas, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Asiakkaat...";

		$query = "	SELECT
					asiakas.tunnus		asiakastunnus,
					concat_ws(' ', asiakas.nimi, asiakas.nimitark)	asiakkaannimi,
					asiakas.ryhma		asiakasryhma,
					kuka.kuka 			myyjatunnus
					FROM asiakas
					LEFT JOIN kuka ON kuka.myyja=asiakas.myyjanro and kuka.yhtio=asiakas.yhtio and kuka.myyja > 0
					where asiakas.yhtio='$yhtio'
					$limit";
		$rest = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($rest);
		$row = 0;

		if ($rows == 0) {
			echo "Yht‰‰n asiakasta ei lˆytynyt\n";
			die();
		}

		$fp = fopen($path_asiakas, 'w+');

		$headers = array(
			'asiakastunnus'  => null,
			'asiakkaannimi'  => null,
			'asiakasryhma'   => null,
			'myyjatunnus'    => null
		);

		create_headers($fp, array_keys($headers));

		while ($asiakas = mysql_fetch_assoc($rest)) {
			$row++;

			$data = array_merge($headers, $asiakas);
			$data = implode("\t", $data);

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
		}

		fclose($fp);
		echo "Done.\n";
	}

	function toimittaja($limit = '') {
		global $path_toimittaja, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Toimittajat...";

		$query = "	SELECT
					tunnus							toimittajatunnus,
					concat_ws(' ', nimi, nimitark)	toimittajannimi
					from toimi
					where yhtio='$yhtio'
					$limit";
		$rest = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($rest);
		$row = 0;
		if ($rows == 0) {
			echo "Yht‰‰n toimittajaa ei lˆytynyt\n";
			die();
		}

		$fp = fopen($path_toimittaja, 'w+');

		$headers = array(
			'toimittajatunnus'  => null,
			'toimittajannimi'   => null
		);

		create_headers($fp, array_keys($headers));

		while ($toimittaja = mysql_fetch_assoc($rest)) {
			$row++;

			$data = array_merge($headers, $toimittaja);
			$data = implode("\t", $data);

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
		}

		fclose($fp);
		echo "Done.\n";
	}

	function varasto($limit = '') {
		global $path_varasto, $lisavarattu, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Varasto... ";

		$fp = fopen($path_varasto, 'w+');

		$query = "	SELECT
					tuotepaikat.tuoteno nimiketunnus,
					sum(tuotepaikat.saldo) saldo,
					tuote.kehahin keskihinta,
					'0' tilattu,
					'0' varattu,
					varastopaikat.tunnus varastotunnus,
					(SELECT tuotteen_toimittajat.toimitusaika FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = '$yhtio' AND tuotteen_toimittajat.tuoteno = tuotepaikat.tuoteno AND tuotteen_toimittajat.toimitusaika != '' LIMIT 1) toimitusaika
					FROM tuotepaikat
					LEFT JOIN varastopaikat ON
					concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
					concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					and varastopaikat.yhtio=tuotepaikat.yhtio
					JOIN tuote ON tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio
					WHERE tuote.ei_saldoa = ''
					AND tuotepaikat.yhtio = '$yhtio'
					$where_logisticar[varasto_1]
					GROUP BY 1,3,4,5,6,7
					ORDER BY 1
					$limit";
		$res = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($res);
		$row = 0;
		if ($rows == 0) {
			echo "Yht‰‰n varastoa ei lˆytynyt\n";
			die();
		}

		$headers = array(
			'nimiketunnus',
			'saldo',
			'keskihinta',
			'tilattu',
			'varattu',
			'varastotunnus',
			'toimitusaika'
		);

		// tehd‰‰n otsikot
		create_headers($fp, $headers);

		while ($trow = mysql_fetch_assoc($res)) {
			$row++;

			$query = "	SELECT
						sum(if(tilausrivi.tyyppi='O', tilausrivi.varattu, 0)) tilattu,
						sum(if((tilausrivi.tyyppi='L' or tilausrivi.tyyppi='V') and tilausrivi.var $lisavarattu, tilausrivi.varattu, 0)) varattu
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio = '$yhtio'
	 					and tyyppi in ('L','V','O','G')
						and tuoteno = '{$trow['nimiketunnus']}'
						and laskutettuaika = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);
			$ennp = mysql_fetch_assoc($result);

			$trow['tilattu'] = $ennp['tilattu'];
			$trow['varattu'] = $ennp['varattu'];

			$data = array_merge($headers, $trow);
			$data = implode("\t", $trow);

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}
		}

		fclose($fp);
		echo "Done.\n";
	}

	function varastotapahtumat($limit = '') {
		global $path_tapahtumat, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Varastotapahtumat... ";

		if (! $fp = fopen($path_tapahtumat, 'w+')) {
			die("Ei voitu avata filea $path_tapahtumat");
		}

		if ($where_logisticar["paiva_ajo"] != "") {
			$pvmlisa = " and date_format(tapahtuma.laadittu, '%Y-%m-%d') >= date_sub(now(), interval 30 day) ";
		}
		else {
			$pvmlisa = " and date_format(tapahtuma.laadittu, '%Y-%m-%d') > '0000-00-00 00:00:00' ";
		}

		$query = "	SELECT
					tapahtuma.tuoteno 			nimiketunnus,
					if(tapahtuma.laji = 'siirto', lasku.clearing, lasku.liitostunnus) asiakastunnus,
					if(tapahtuma.laji = 'siirto', lasku.clearing, lasku.liitostunnus) toimitusasiakas,
					date_format(tapahtuma.laadittu, '%Y-%m-%d') tapahtumapaiva,
					tapahtuma.laji           	tapahtumalaji,
					(tapahtuma.kplhinta * tapahtuma.kpl * -1) myyntiarvo,
					(tapahtuma.kplhinta * tapahtuma.kpl * -1) ostoarvo,
					(tapahtuma.kpl * (tapahtuma.kplhinta - tapahtuma.hinta) * -1) kate,
					tapahtuma.kpl 				tapahtumamaara,
					lasku.laskunro              laskunumero,
					kuka.kuka	                myyjatunnus,
					lasku.yhtio_toimipaikka		toimipaikka,
					varastopaikat.tunnus        varastotunnus,
					tapahtuma.laji  			tapahtumatyyppi
					FROM tapahtuma
					LEFT JOIN tilausrivi USE INDEX (PRIMARY) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
					LEFT JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
					LEFT JOIN tuotepaikat USE INDEX (yhtio_tuoteno_paikka) ON (	tuotepaikat.yhtio		= tapahtuma.yhtio and
																				tuotepaikat.tuoteno 	= tapahtuma.tuoteno and
																				tuotepaikat.hyllyvali 	= tapahtuma.hyllyvali and
																				tuotepaikat.hyllytaso 	= tapahtuma.hyllytaso and
																				tuotepaikat.hyllyalue 	= tapahtuma.hyllyalue and
																				tuotepaikat.hyllynro 	= tapahtuma.hyllynro)
					LEFT JOIN varastopaikat ON
					concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
					concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					and varastopaikat.yhtio = tuotepaikat.yhtio
					LEFT JOIN kuka ON kuka.tunnus=lasku.myyja and kuka.yhtio=lasku.yhtio
					WHERE tapahtuma.laji in ('tulo', 'laskutus', 'siirto', 'valmistus', 'kulutus')
					and tapahtuma.yhtio = '$yhtio'
					$pvmlisa
					ORDER BY tapahtumapaiva, nimiketunnus ASC
					$limit";
	    $res = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($res);
		$row = 0;
		if ($rows == 0) {
			echo "Yht‰‰n varastotapahtumaa ei lˆytynyt\n";
			die();
		}

		$headers = array(
			'nimiketunnus'    => null,
			'asiakastunnus'   => null,
			'toimitusasiakas' => null,
			'tapahtumapaiva'  => null,
			'tapahtumalaji'   => null,
			'myyntiarvo'      => null,
			'ostoarvo'        => null,
			'tapahtumamaara'  => null,
			'laskunumero'     => null,
			'myyjatunnus'     => null,
			'toimipaikka'	  => null,
			'varastotunnus'   => null,
			'tapahtumatyyppi' => null
		);

		// tehd‰‰n otsikot
		create_headers($fp, array_keys($headers));

	    while ($trow = mysql_fetch_assoc($res)) {
			$row++;

			switch(strtolower($trow['tapahtumalaji'])) {
				// ostot
				case 'tulo':

					// 1 = saapuminen tai oston palautus
					$trow['tapahtumalaji'] = 1;
					$trow['tapahtumatyyppi'] = 'O';

					// myyntiarvo on 0
					$trow['myyntiarvo'] = 0;
					$trow['ostoarvo'] = (-1 * $trow['ostoarvo']);

					// jos kpl alle 0 niin t‰m‰ on oston palautus
					// jolloin hinta myˆs miinus
					if ($trow['tapahtumamaara'] < 0) {
						// tapahtumamaara on aina positiivinen logisticarissa
						$trow['tapahtumamaara'] = (-1 * $trow['tapahtumamaara']);
					}

			        break;

				// myynnit
				case 'laskutus':

					// 2 = otto tai myynninpalautus
					$trow['tapahtumalaji'] = 2;
					$trow['tapahtumatyyppi'] = 'L';

					// ostoarvo
					$trow['ostoarvo'] = $trow['myyntiarvo'] - $trow['kate'];

					// t‰m‰ on myynninpalautus eli myyntiarvo on negatiivinen
					if ($trow['tapahtumamaara'] < 0) {
						// tapahtumamaara on aina positiivinen logisticarissa
						$trow['tapahtumamaara'] = (-1 * $trow['tapahtumamaara']);
					}

					break;

				// varastosiirrot
				case 'siirto':

					if ($trow['tapahtumamaara'] < 0) {
						$trow['tapahtumalaji'] = 2;
						$trow['tapahtumatyyppi'] = 'S';
						$trow['tapahtumamaara'] = (-1 * $trow['tapahtumamaara']);
					}
					else {
						$trow['tapahtumalaji'] = 1;
						$trow['tapahtumatyyppi'] = 'S';

						$trow['toimitusasiakas'] = $trow['varastotunnus'];
						$trow['varastotunnus'] = $trow['asiakastunnus'];
					}


			}

			unset($trow['kate']);

			$data = array_merge($headers, $trow);
			$data = implode("\t", $data);

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
	    }

		fclose($fp);
		echo "Done.\n";
	}

	function myynti($limit = '') {
		global $path_myynti, $yhtiorow, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Myynnit... ";

		if (! $fp = fopen($path_myynti, 'w+')) {
			die("Ei voitu avata filea $path_myynti");
		}

		$query_ale_lisa = generoi_alekentta('M');

	    $query = "	SELECT
					tilausrivi.tuoteno nimiketunnus,
					lasku.liitostunnus asiakastunnus,
					lasku.liitostunnus toimitusasiakas,
					if(tilausrivi.tyyppi = 'G', lasku.clearing, lasku.liitostunnus) asiakastunnus,
					if(tilausrivi.tyyppi = 'G', lasku.clearing, lasku.liitostunnus) toimitusasiakas,
					tilausrivi.toimaika toimituspaiva,
					tilausrivi.tyyppi tapahtumalaji,
					round(tilausrivi.hinta  / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) myyntiarvo,
					(tilausrivi.varattu+tilausrivi.jt) * tuote.kehahin ostoarvo,
					tilausrivi.varattu tapahtumamaara,
					lasku.tunnus tilausnro,
					kuka.kuka myyjatunnus,
					lasku.yhtio_toimipaikka	toimipaikka,
					varastopaikat.tunnus varastotunnus,
					tilausrivi.toimitettu
					FROM tilausrivi
					JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.otunnus and lasku.yhtio=tilausrivi.yhtio
					JOIN tuote ON tuote.tuoteno = tilausrivi.tuoteno and tuote.yhtio = tilausrivi.yhtio
					JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio
					JOIN varastopaikat ON
					concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
					concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					and varastopaikat.yhtio=tuotepaikat.yhtio
					LEFT JOIN kuka ON kuka.tunnus=lasku.myyja and kuka.yhtio=lasku.yhtio
					WHERE tilausrivi.varattu != 0
					AND tilausrivi.tyyppi IN ('L','O','G')
					AND tilausrivi.laskutettuaika = '0000-00-00'
					AND tilausrivi.yhtio = '$yhtio'
					ORDER BY tilausrivi.laadittu
					$limit";
		$res = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($res);
		$row = 0;
		if ($rows == 0) {
			echo "Yht‰‰n myyntitapahtumaa ei lˆytynyt\n";
			die();
		}

		$headers = array(
			'nimiketunnus'    => null,
			'asiakastunnus'   => null,
			'toimitusasiakas' => null,
			'toimituspaiva'   => null,
			'tapahtumalaji'   => null,
			'myyntiarvo'      => null,
			'ostoarvo'        => null,
			'tapahtumamaara'  => null,
			'tilausnro'       => null,
			'myyjatunnus'     => null,
			'toimipaikka'	  => null,
			'varastotunnus'   => null
		);

		// tehd‰‰n otsikot
		create_headers($fp, array_keys($headers));

		while ($trow = mysql_fetch_assoc($res)) {
			$row++;

			switch (strtoupper($trow['tapahtumalaji'])) {
				case 'G':
					// kirjoitetaan fileen vain avoimia siirtolistoja eli toimitettu pit‰‰ olla tyhj‰‰
					if ($trow['toimitettu'] != '') {
						continue;
					}

					// laji 3 = saapuva tilaus
					$trow['tapahtumalaji'] 	= '3';
					$trow['myyntiarvo'] 	= 0;

					$trow['toimitusasiakas'] = $trow['varastotunnus'];
					$trow['varastotunnus'] = $trow['asiakastunnus'];

					// ei haluta toimitettu-saraketta mukaan
					unset($trow['toimitettu']);

					$data = array_merge($headers, $trow);
					$data = implode("\t", $data);

					if (! fwrite($fp, $data . "\n")) {
						echo "Failed writing row.\n";
						die();
					}

					// laji 4 = varattu
					$trow['tapahtumalaji'] = '4';

					$trow['varastotunnus'] = $trow['toimitusasiakas'];
					$trow['toimitusasiakas'] = $trow['asiakastunnus'];

					break;
				case 'L':
					$trow['tapahtumalaji']	= '4';

					// ei haluta toimitettu-saraketta mukaan
					unset($trow['toimitettu']);
					break;
				case 'O':
					$trow['tapahtumalaji'] 	= '3';
					$trow['myyntiarvo'] 	= 0;

					// ei haluta toimitettu-saraketta mukaan
					unset($trow['toimitettu']);
					break;
			}

			$data = array_merge($headers, $trow);
			$data = implode("\t", $data);

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
	    }

		fclose($fp);
		echo "Done.\n";
	}

	function create_headers($fp, array $cols) {
		$data = implode("\t", $cols) . "\n";
		fwrite($fp, $data);
	}
?>
