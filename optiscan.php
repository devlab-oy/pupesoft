#!/usr/bin/php
<?php

	/*
		T‰t‰ on tarkoitus k‰ytt‰‰ Linux XINETD socket serverin kanssa.

		1. Tehd‰‰n tiedosto /etc/xinetd.d/optiscan

		# description: Optsican socket server
		service optiscan
		{
		   port        = 15005
		   socket_type = stream
		   wait        = no
		   user        = apache
		   server      = /var/www/html/pupesoft/optiscan.php
		   disable     = no
		}

		2. Lis‰t‰‰n tiedostoon /etc/services rivi

		optiscan        15005/tcp               # Optiscan socket server

		3. Varmista, ett‰ XINETD k‰ytynnistyy bootissa.

		ntsysv

		4. Varmista, ett‰ optiscan.php:ll‰ on execute oikeus (t‰m‰ on hyv‰t‰ lis‰t‰ myˆs pupesoft p‰ivitys scriptiin!)

		chmod a+x /var/www/html/pupesoft/optiscan.php

		5. Uudelleenk‰ynnist‰ XINETD

		service xinetd restart
	*/

	if (php_sapi_name() != 'cli') {
		die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
	}

	// error_reporting(E_ALL);
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	$pupe_root_polku = dirname(__FILE__);
	chdir($pupe_root_polku);

	require('inc/connect.inc');
	require('inc/functions.inc');

	$handle = fopen("php://stdin", "r");
	$lines = array();

	while ($line = fgets($handle)) {
		if (trim($line) == "") {
			$lines[] = "#EOM";
			break;
		}
		$lines[] = trim($line);
	}

	// Jos kuoltiin timeouttiin
	if (!in_array("#EOM", $lines)) {
		echo "1, Timeout\r\n\r\n";
		exit;
	}

	// Jos saatiin laitteelta enemm‰n kuin yksi rivi
	if (count($lines) != 2) {
		echo "1, Virheellinen viesti\r\n\r\n";
		exit;
	}

	// Erotellaan sanomasta sanoman tyyppi ja sis‰ltˆ
	preg_match("/^(.*?)\((.*?)\)/", $lines[0], $matches);

	// Varmistetaan, ett‰ splitti onnistui
	if (!isset($matches[1]) or !isset($matches[2])) {
		echo "1, Virheellinen sanomamuoto\r\n\r\n";
		exit;
	}

	$sanoma = $matches[1];
	$sisalto = $matches[2];

	// Sallittuja sanomia
	$sallitut_sanomat = array(
		"SignOn",
		"GetPicks",
		"PrintContainers",
		"Picked",
		"AllPicked",
		"StopAssignment",
		"SignOff",
		"Replenishment",
		"NewContainer",
		"ChangeContainer",
	);

	// Virheellinen sanoma tai tyhj‰ sis‰ltˆ
	if (!in_array($sanoma, $sallitut_sanomat)) {
		echo "1, Virheellinen sanoma\r\n\r\n";
		exit;
	}

	// Erotellaan sanoman sis‰ltˆ arrayseen
	$sisalto = explode(",", str_replace("'", "", $sisalto));
	$response = "";

	if ($sanoma == "SignOn") {

		/**
		 * Parametrit
		 * 1. DateTime mm-dd-yyyy hh:mm:ss
		 * 2. Device Serial Number string
		 * 3. Login
		 * 4. Password
		 */

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));

		$pass = md5(trim($sisalto[3]));

		$query = "	SELECT kuka.kuka, kuka.salasana
					FROM kuka
					JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
					WHERE kuka.kuka	= '{$kukarow['kuka']}'
					AND kuka.extranet = ''
					AND kuka.yhtio = '{$kukarow['yhtio']}'
					GROUP BY 1,2";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe k‰ytt‰j‰tietoja haettaessa\r\n\r\n");
		$krow = mysql_fetch_assoc($result);

		if (trim($krow['kuka']) == '') {
			// K‰ytt‰j‰nime‰ ei lˆytynyt
			$response = "1, K‰ytt‰j‰‰ ei lˆydy\r\n\r\n";
		}
		elseif ($pass != $krow['salasana']) {
			// Salasana virheellinen
			$response = "1, Salasana virheellinen\r\n\r\n";
		}
		elseif ($pass == $krow['salasana']) {
			// Sis‰‰nkirjaantuminen onnistui
			$response = "0, Sis‰‰nkirjaantuminen onnistui\r\n\r\n";
		}
		else {
			// Sis‰‰nkirjaantuminen ep‰onnistui
			$response = "1, Sis‰‰nkirjaantuminen ep‰onnistui\r\n\r\n";
		}
	}
	elseif ($sanoma == "GetPicks") {
		// Kent‰t: "Pick slot" sek‰ "Item number" tulee olla m‰‰r‰mittaisia. Paddataan loppuun spacella.

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		// Katsotaan onko k‰ytt‰j‰ll‰ jo ker‰yser‰ ker‰yksess‰
		$query = "	SELECT tilausrivi, tunnus, pakkausnro, sscc
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laatija = '{$kukarow['kuka']}'
					AND tila = 'K'
					ORDER BY sscc, otunnus, pakkausnro";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		if (mysql_num_rows($result) > 0) {

			$kpl = mysql_num_rows($result);
			$n = 1;

			while ($row = mysql_fetch_assoc($result)) {

				$query = "	SELECT keraysvyohyke.nimitys AS ker_nimitys,
							tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
							IFNULL(vh.varmistuskoodi, '00') AS varmistuskoodi,
							tilausrivi.tuoteno, ROUND(tilausrivi.varattu, 0) AS varattu, tilausrivi.yksikko, tuote.nimitys
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
							JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tunnus = '{$row['tilausrivi']}'";
				$rivi_result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe\r\n\r\n");
				$rivi_row = mysql_fetch_assoc($rivi_result);

				$pakkauskirjain = chr((64+$row['pakkausnro']));
				$tuotteen_nimitys = str_replace(array("'", ","), "", $rivi_row['nimitys']);

				$hyllypaikka = $rivi_row['hyllyalue'];
				$hyllypaikka = trim($rivi_row['hyllynro']) != '' ? $hyllypaikka."-".$rivi_row['hyllynro'] : $hyllypaikka;
				$hyllypaikka = trim($rivi_row['hyllyvali']) != '' ? $hyllypaikka."-".$rivi_row['hyllyvali'] : $hyllypaikka;
				$hyllypaikka = trim($rivi_row['hyllytaso']) != '' ? $hyllypaikka."-".$rivi_row['hyllytaso'] : $hyllypaikka;

				$response .= "N,";
				$response .= substr($rivi_row['ker_nimitys'], 0, 255).",";
				$response .= "{$kpl} rivi‰,{$row['sscc']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},{$rivi_row['tuoteno']},{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$row['tunnus']},{$tuotteen_nimitys},Rivi {$n},0\r\n";

				$n++;
			}

			$response .= "\r\n";
		}
		else {

			$query = "	SELECT keraysvyohyke
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$kukarow['kuka']}'
						AND extranet = ''
						AND keraysvyohyke != ''";
			$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰ysvyˆhykett‰ haettaessa\r\n\r\n");
			$row = mysql_fetch_assoc($result);

			$erat = tee_keraysera($row['keraysvyohyke']);

			if (count($erat['tilaukset']) != 0) {
				$otunnukset = implode(",", $erat['tilaukset']);

				$ei_tulosteta = true;

				require('inc/tulosta_reittietiketti.inc');

				$kerayslistatunnus = trim(array_shift($erat['tilaukset'][0]));

				// tilaus on jo tilassa N A, p‰ivitet‰‰n nyt tilaus "ker‰yslista tulostettu" eli L A
				$query = "	UPDATE lasku SET
							tila = 'L',
							lahetepvm = now(),
							kerayslista = '{$kerayslistatunnus}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus in ({$otunnukset})";
				$upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilauksen p‰ivityksen yhteydess‰\r\n\r\n");

				$query = "	SELECT tilausrivi, tunnus, pakkausnro, sscc
							FROM kerayserat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laatija = '{$kukarow['kuka']}'
							AND tila = 'K'
							ORDER BY sscc, otunnus, pakkausnro";
				$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

				$kpl = mysql_num_rows($result);
				$n = 1;

				while ($row = mysql_fetch_assoc($result)) {

					$query = "	SELECT keraysvyohyke.nimitys AS ker_nimitys,
								tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
								IFNULL(vh.varmistuskoodi, '00') AS varmistuskoodi,
								tilausrivi.tuoteno, ROUND(tilausrivi.varattu, 0) AS varattu, tilausrivi.yksikko, tuote.nimitys
								FROM tilausrivi
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
								JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.tunnus = '{$row['tilausrivi']}'";
					$rivi_result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe\r\n\r\n");
					$rivi_row = mysql_fetch_assoc($rivi_result);

					$pakkauskirjain = chr((64+$row['pakkausnro']));
					$tuotteen_nimitys = str_replace(array("'", ","), "", $rivi_row['nimitys']);

					$hyllypaikka = $rivi_row['hyllyalue'];
					$hyllypaikka = trim($rivi_row['hyllynro']) != '' ? $hyllypaikka."-".$rivi_row['hyllynro'] : $hyllypaikka;
					$hyllypaikka = trim($rivi_row['hyllyvali']) != '' ? $hyllypaikka."-".$rivi_row['hyllyvali'] : $hyllypaikka;
					$hyllypaikka = trim($rivi_row['hyllytaso']) != '' ? $hyllypaikka."-".$rivi_row['hyllytaso'] : $hyllypaikka;

					$response .= "N,";
					$response .= substr($rivi_row['ker_nimitys'], 0, 255).",";
					$response .= "{$kpl} rivi‰,{$row['sscc']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},{$rivi_row['tuoteno']},{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$row['tunnus']},{$tuotteen_nimitys},Rivi {$n},0\r\n";

					$n++;
				}

				$response .= "\r\n";
			}
		}

		if ($response == '') {
			$response = "N,,,,,,,,,,,,,1,Ei yht‰‰n ker‰yser‰‰\r\n\r\n";
		}
	}
	elseif ($sanoma == "PrintContainers") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$sscc = mysql_real_escape_string(trim($sisalto[3]));
		$printer_id = (int) trim($sisalto[4]);

		$query = "	SELECT komento
					FROM kirjoittimet
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND jarjestys = '{$printer_id}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe kirjoittimen komentoa haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		$komento = $row['komento'];

		$query = "	SELECT *
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND sscc = '{$sscc}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		if (mysql_num_rows($result) == 0) {
			$response = "1, Ei printattavaa\r\n\r\n";
		}

		if (trim($response) == '') {
			$rivit = $paino = $tilavuus = 0;
			$otunnus = 0;

			while ($row = mysql_fetch_assoc($result)) {

				if ($sscc > 0 and $sscc != $row['sscc']) break;

				$query = "	SELECT round((tuote.tuotemassa * (tilausrivi.kpl+tilausrivi.varattu)), 2) as paino, round(((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys) * (tilausrivi.kpl+tilausrivi.varattu)), 4) as tilavuus, tilausrivi.otunnus
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tunnus = '{$row['tilausrivi']}'";
				$paino_res = pupe_query($query);
				$paino_row = mysql_fetch_assoc($paino_res);

				$paino += $paino_row['paino'];
				$tilavuus += $paino_row['tilavuus'];
				$rivit += 1;
				$otunnus = $row['otunnus'];

				$sscc = $row['sscc'];
			}

			$query = "SELECT toimitustapa, nimi, nimitark, osoite, postino, postitp, viesti, sisviesti2 FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$otunnus}'";
			$laskures = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausta haettaessa\r\n\r\n");
			$laskurow = mysql_fetch_assoc($laskures);

			$query = "	SELECT *
						FROM kerayserat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND sscc = '{$sscc}'
						GROUP BY pakkausnro
						ORDER BY pakkausnro";
			$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

			while ($row = mysql_fetch_assoc($result)) {

				$pakkaus_kirjain = chr((64+$row['pakkausnro']));

				$params = array(
					'tilriv' => $row['tilausrivi'],
					'pakkaus_kirjain' => $pakkaus_kirjain,
					'sscc' => $sscc,
					'toimitustapa' => $laskurow['toimitustapa'],
					'rivit' => $rivit,
					'paino' => $paino,
					'tilavuus' => $tilavuus,
					'lask_nimi' => $laskurow['nimi'],
					'lask_nimitark' => $laskurow['nimitark'],
					'lask_osoite' => $laskurow['osoite'],
					'lask_postino' => $laskurow['postino'],
					'lask_postitp' => $laskurow['postitp'],
					'lask_viite' => $laskurow['viesti'],
					'lask_merkki' => $laskurow['sisviesti2'],
					'komento_reittietiketti' => $komento,
				);

				if ($komento != 'email') {
					tulosta_reittietiketti($params);
				}
			}

			$response = "0, Tulostus onnistui\r\n\r\n";
		}
	}
	elseif ($sanoma == "Picked") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$sscc = (int) trim($sisalto[3]);
		$row_id = (int) trim($sisalto[4]);
		$qty = (int) trim($sisalto[5]);
		$package = mysql_real_escape_string(trim($sisalto[6]));

		// katsotaan m‰ts‰‰v‰tkˆ kappalem‰‰r‰t. jos ei niin pit‰‰ splitata tilausrivi ja laittaa loput puutteeksi
		$query = "	SELECT *
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND sscc = '{$sscc}'
					AND tunnus = '{$row_id}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		if ($qty < (int) $row['kpl']) {
			// ker‰‰j‰ otti v‰hemm‰n mit‰ oli tilausrivill‰, splitataan tilausrivi ja p‰ivitet‰‰n loput puutteeksi
			$query = "SELECT * FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['tilausrivi']}'";
			$tilrivires = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausrivi‰ haettaessa\r\n\r\n");
			$tilrivirow = mysql_fetch_array($tilrivires);

			mysql_data_seek($tilrivires, 0);

			$fields = "yhtio";
			$values = "'{$kukarow['yhtio']}'";

			$puutevarattu = $row['kpl'] - $qty;

			for ($i = 0; $i < mysql_num_fields($tilrivires); $i++) {

				$fieldname = mysql_field_name($tilrivires, $i);

				if ($fieldname == 'tunnus' or $fieldname == 'yhtio') continue;

				$fields .= ", ".$fieldname;

				switch ($fieldname) {
					case 'varattu':
						$values .= ", 0";
						break;
					case 'tilkpl':
						$values .= ", '{$puutevarattu}'";
						break;
					case 'var':
						$values .= ", 'P'";
						break;
					case 'laatija':
						$values .= ", '{$kukarow['kuka']}'";
						break;
					case 'laadittu':
						$values .= ", now()";
						break;
					default:
						$values .= ", '".$tilrivirow[$fieldname]."'";
				}
			}

			$query = "INSERT INTO tilausrivi ($fields) VALUES ($values)";
			$insres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausrivi‰ luodessa\r\n\r\n");

			$query = "UPDATE tilausrivi SET varattu = '{$qty}', tilkpl = '{$qty}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilrivirow['tunnus']}'";
			$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausrivi‰ p‰ivitett‰ess‰\r\n\r\n");

			$query = "UPDATE kerayserat SET kpl = '{$qty}' WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tunnus = '{$row_id}'";
			$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
		}

		$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tunnus = '{$row_id}'";
		$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");

		$response = "0x100";

	}
	elseif ($sanoma == "AllPicked") {

		$kukarow['yhtio'] = 'artr';

		$query = "SELECT * FROM kuka WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '".mysql_real_escape_string(trim($sisalto[2]))."'";
		$kukares = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe k‰ytt‰j‰‰ haettaessa\r\n\r\n");
		$kukarow = mysql_fetch_assoc($kukares);

		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$sscc = (int) trim($sisalto[3]);

		$query = "	SELECT nro
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND sscc = '{$sscc}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		$query = "	SELECT COUNT(otunnus) AS kpl, GROUP_CONCAT(otunnus) AS otunnukset
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nro = '{$row['nro']}'
					ORDER BY sscc";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		if ($row['kpl'] == 0) {
			$response = ",0,Virhe ei ker‰yser‰‰\r\n\r\n";
		}
		else {

			$query = "SELECT keraysvyohyke FROM kuka WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
			$keraysvyohyke_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰ysvyˆhykett‰ haettaessa\r\n\r\n");
			$keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res);

			$query = "SELECT * FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ({$keraysvyohyke_row['keraysvyohyke']})";
			$printteri_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe printteri‰ haettaessa\r\n\r\n");
			$printteri_row = mysql_fetch_assoc($printteri_res);

			$vain_tulostus = '';

			$query = "	UPDATE lasku SET
						tila = 'L',
						alatila = 'C'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus IN ({$row['otunnukset']})";
			$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilauksia p‰ivitett‰ess‰\r\n\r\n");

			$query = "	SELECT lasku.tunnus, lasku.vienti, lasku.tila, lasku.alatila,
						toimitustapa.rahtikirja,
						toimitustapa.tulostustapa,
						toimitustapa.nouto,
						lasku.varasto,
						lasku.toimitustapa,
						lasku.jaksotettu
						FROM lasku
						LEFT JOIN toimitustapa ON (lasku.yhtio = toimitustapa.yhtio AND lasku.toimitustapa = toimitustapa.selite AND toimitustapa.nouto = '')
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tunnus IN ({$row['otunnukset']})";
			$lasresult = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilauksia haettaessa\r\n\r\n");

			while ($laskurow = mysql_fetch_assoc($lasresult)) {
				$query = "	SELECT pakkaus.pakkaus,
							pakkaus.pakkauskuvaus,
							tuote.tuotemassa,
							(pakkaus.leveys * pakkaus.korkeus * pakkaus.syvyys) as kuutiot,
							kerayserat.kpl,
							kerayserat.pakkausnro
							FROM kerayserat
							JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
							JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND kerayserat.otunnus = '{$laskurow['tunnus']}'
							AND kerayserat.tila = 'T'
							ORDER BY pakkausnro ASC";
				$keraysera_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe pakkaustietoja haettaessa\r\n\r\n");

				$rahtikirjan_pakkaukset = array();

				while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
					if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kpl'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kpl'] = 0;
					if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'] = 0;
					if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'] = 0;
					if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkausnro'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkausnro'] = 0;

					$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kpl'] 			+= $keraysera_row['kpl'];
					$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkauskuvaus'] = $keraysera_row['pakkauskuvaus'];
					$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'] 		+= ($keraysera_row['tuotemassa'] * $keraysera_row['kpl']);
					$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'] 		+= $keraysera_row['kuutiot'];
					$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkausnro']	= $keraysera_row['pakkausnro'];
				}

				// esisyˆtet‰‰n rahtikirjan tiedot
				if (count($rahtikirjan_pakkaukset) > 0) {

					foreach ($rahtikirjan_pakkaukset as $pak => $arr) {
						// LOGY:n rahtikirjanumerot?
						$query_ker  = "	INSERT INTO rahtikirjat SET
										kollit 			= '{$arr['pakkausnro']}',
										kilot 			= '{$arr['paino']}',
										kuutiot 		= '{$arr['kuutiot']}',
										pakkauskuvaus 	= '{$arr['pakkauskuvaus']}',
										pakkaus 		= '{$pak}',
										rahtikirjanro 	= '{$laskurow['tunnus']}',
										otsikkonro 		= '{$laskurow['tunnus']}',
										tulostuspaikka 	= '{$laskurow['varasto']}',
										toimitustapa 	= '{$laskurow['toimitustapa']}',
										yhtio 			= '{$kukarow['yhtio']}'";
						$ker_res = mysql_query($query_ker) or die("1, Tietokantayhteydess‰ virhe rahtikirjaa lis‰tt‰ess‰\r\n\r\n");

						$query = "UPDATE lasku SET alatila = 'B' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['tunnus']}'";
						$alatila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe laskua p‰ivitett‰ess‰\r\n\r\n");

						// jos kyseess‰ on toimitustapa jonka rahtikirja on hetitulostus, tulostetaan myˆs rahtikirja t‰ss‰ vaiheessa
						if ($laskurow['tulostustapa'] == 'H' and $laskurow["nouto"] == "") {

							// p‰ivitet‰‰n ker‰yser‰n tila "Rahtikirja tulostettu"-tilaan
							$query = "UPDATE kerayserat SET tila = 'R' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
							$tila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");

							// rahtikirjojen tulostus vaatii seuraavat muuttujat:

							// $toimitustapa_varasto	toimitustavan selite!!!!varastopaikan tunnus
							// $tee						t‰ss‰ pit‰‰ olla teksti tulosta

							$toimitustapa_varasto = $laskurow['toimitustapa']."!!!!".$kukarow['yhtio']."!!!!".$laskurow['varasto'];
							$tee				  = "tulosta";

							$nayta_pdf = 'foo';

							// $vain_tulostus muuttujaan laitetaan rahtikirjan tulostuksessa printteri mihin tulostetaan ja sill‰ haetaan jarjestys joka echotetaan

							require ("rahtikirja-tulostus.php");
						}
						else {
							// p‰ivitet‰‰n ker‰yser‰n tila "Ker‰tty"-tilaan
							$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
							$tila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
						}
					}
				}
				else {
					// p‰ivitet‰‰n ker‰yser‰n tila "Ker‰tty"-tilaan
					$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
					$tila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
				}

				//haetaan osoitelapun tulostuskomento
				$query  = "SELECT * FROM kirjoittimet WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$printteri_row['printteri3']}'";
				$kirres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe kirjoitinta haettaessa");
				$kirrow = mysql_fetch_assoc($kirres);

				$oslapp = $kirrow['komento'];
				$oslappkpl = 1;

				$tunnus = $laskurow["tunnus"];

				$query = "SELECT osoitelappu FROM toimitustapa WHERE yhtio = '{$kukarow['yhtio']}' and selite = '{$laskurow['toimitustapa']}'";
				$oslares = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe osoitelappua haettaessa");
				$oslarow = mysql_fetch_assoc($oslares);

				if ($oslarow['osoitelappu'] == 'intrade') {
					require('tilauskasittely/osoitelappu_intrade_pdf.inc');
				}
				else {
					require ("tilauskasittely/osoitelappu_pdf.inc");
				}
			}

			if (trim($vain_tulostus) != '') {
				$vain_tulostus = mysql_real_escape_string(trim($vain_tulostus));

				$query = "SELECT jarjestys FROM kirjoittimet WHERE yhtio = '{$kukarow['yhtio']}' AND kirjoitin = '{$vain_tulostus}'";
				$kirjoitin_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe kirjoitinta haettaessa\r\n\r\n");
				$kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);
			}

			$query_ale_lisa = generoi_alekentta('M');

			$query = "SELECT * FROM kirjoittimet WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$printteri_row['printteri1']}'";
			$kirj_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe kirjoitinta haettaessa\r\n\r\n");
			$kirj_row = mysql_fetch_assoc($kirj_res);

			$komento["L‰hete"] = $kirj_row['komento'];

			foreach (explode(",", $row['otunnukset']) as $otun) {

				$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$otun}'";
				$laskures = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausta haettaessa\r\n\r\n");
				$laskurow = mysql_fetch_assoc($laskures);

				$params = array(
					'laskurow'					=> $laskurow,
					'sellahetetyyppi' 			=> "",
					'extranet_tilausvahvistus' 	=> "",
					'naytetaanko_rivihinta'		=> "",
					'tee'						=> "",
					'toim'						=> "",
					'komento' 					=> $komento,
					'lahetekpl'					=> "",
					'kieli' 					=> ""
					);

				pupesoft_tulosta_lahete($params);
			}

			$dok = (isset($kirjoitin_row['jarjestys']) and trim($kirjoitin_row['jarjestys']) != '') ? ($kirjoitin_row['jarjestys']." ja ".$kirj_row['jarjestys']) : $kirj_row['jarjestys'];

			$response = "Dokumentit tulostuu kirjoittimelta {$dok},0,\r\n\r\n";
		}
	}
	elseif ($sanoma == "StopAssignment") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$sscc = (int) trim($sisalto[3]);

		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tila = 'K'";
		$chkres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		if (mysql_num_rows($chkres) > 0) {
			$response = "1,Kaikki rivit ei ole ker‰tty\r\n\r\n";
		}
		else {
			$response = "0,\r\n\r\n";
		}
	}
	elseif ($sanoma == "SignOff") {
		$response = "0,\r\n\r\n";
	}
	elseif ($sanoma == "Replenishment") {

	}
	elseif ($sanoma == "NewContainer") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$sscc = (int) trim($sisalto[3]);
		$row_id = (int) trim($sisalto[4]);
		$printer_id = (int) trim($sisalto[5]);

		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tunnus = '{$row_id}'";
		$chkres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$chkrow = mysql_fetch_array($chkres);

		/*
		###### TEHDƒƒN UUSI SSCC-NUMERO
		// emuloidaan transactioita mysql LOCK komennolla
		$query = "LOCK TABLES avainsana WRITE";
		$res   = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe lukituksen yhteydess‰\r\n\r\n");

		$query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe avainsanaa haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		$uusi_sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

		$query = "UPDATE avainsana SET selite = '{$uusi_sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
		$update_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe avainsanaa p‰ivitett‰ess‰\r\n\r\n");

		// poistetaan lukko
		$query = "UNLOCK TABLES";
		$res   = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe lukitusta poistettaessa\r\n\r\n");
		*/

		$uusi_sscc = $sscc;

		###### TEHDƒƒN UUSI PAKKAUSKIRJAIN
		$query = "SELECT (MAX(pakkausnro) + 1) uusi_pakkauskirjain FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$chkrow['nro']}'";
		$uusi_paknro_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$uusi_paknro_row = mysql_fetch_assoc($uusi_paknro_res);

		// T‰m‰n tilausrivin tuotteet pit‰‰ laittaa uudelle alustalle (sama asiakas), tulostaa t‰st‰ uusi alustatarra automaattisesti sek‰ vaihtaa kaikki ker‰‰m‰ttˆm‰t rivit kyseiselt‰ ker‰yser‰lt‰ t‰lle uudelle.
		// Vastauksena tulee l‰hett‰‰ uuden alustan tunnus. sanomassa tulee tulostimen numero, mille laput pit‰‰ tulostaa. Vastaus OK tai error ja joku viesti joka puhutaan k‰ytt‰j‰lle.
		$query = "	UPDATE kerayserat SET
					#sscc = '{$uusi_sscc}',
					pakkausnro = '{$uusi_paknro_row['uusi_pakkauskirjain']}'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila = 'K'
					AND pakkausnro = '{$chkrow['pakkausnro']}'
					AND nro = '{$chkrow['nro']}'";
		$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰n rivej‰ p‰ivitett‰ess‰\r\n\r\n");

		$pakkaus_kirjain = chr((64+$uusi_paknro_row['uusi_pakkauskirjain']));

		$query = "	SELECT *
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND sscc = '{$uusi_sscc}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		$rivit = $paino = $tilavuus = 0;
		$otunnus = 0;

		while ($row = mysql_fetch_assoc($result)) {

			$query = "	SELECT round((tuote.tuotemassa * (tilausrivi.kpl+tilausrivi.varattu)), 2) as paino, round(((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys) * (tilausrivi.kpl+tilausrivi.varattu)), 4) as tilavuus, tilausrivi.otunnus
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.tunnus = '{$row['tilausrivi']}'";
			$paino_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe mittatietoja haettaessa\r\n\r\n");
			$paino_row = mysql_fetch_assoc($paino_res);

			$paino += $paino_row['paino'];
			$tilavuus += $paino_row['tilavuus'];
			$rivit += 1;
			$otunnus = $row['otunnus'];
		}

		$query = "	SELECT komento
					FROM kirjoittimet
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND jarjestys = '{$printer_id}'";
		$print_result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe kirjoittimen komentoa haettaessa\r\n\r\n");
		$print_row = mysql_fetch_assoc($print_result);

		$komento = $print_row['komento'];

		$query = "SELECT toimitustapa, nimi, nimitark, osoite, postino, postitp, viesti, sisviesti2 FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$otunnus}'";
		$laskures = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausta haettaessa\r\n\r\n");
		$laskurow = mysql_fetch_assoc($laskures);

		$params = array(
			'tilriv' => $chkrow['tilausrivi'],
			'pakkaus_kirjain' => $pakkaus_kirjain,
			'sscc' => $uusi_sscc,
			'toimitustapa' => $laskurow['toimitustapa'],
			'rivit' => $rivit,
			'paino' => $paino,
			'tilavuus' => $tilavuus,
			'lask_nimi' => $laskurow['nimi'],
			'lask_nimitark' => $laskurow['nimitark'],
			'lask_osoite' => $laskurow['osoite'],
			'lask_postino' => $laskurow['postino'],
			'lask_postitp' => $laskurow['postitp'],
			'lask_viite' => $laskurow['viesti'],
			'lask_merkki' => $laskurow['sisviesti2'],
			'komento_reittietiketti' => $komento,
		);

		if ($komento != 'email') {
			tulosta_reittietiketti($params);
		}

		$response = "{$pakkaus_kirjain},0,\r\n\r\n";
	}
	elseif ($sanoma == "ChangeContainer") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$sscc = (int) trim($sisalto[3]);
		$row_id = (int) trim($sisalto[4]);
		$container_id = trim($sisalto[5]);
		$all = trim($sisalto[6]);

		// haetaan ker‰tt‰v‰ ker‰ysrivi
		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tunnus = '{$row_id}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰n numeroa haettaessa\r\n\r\n");
		$orig_row = mysql_fetch_assoc($result);

		// haetaan ker‰tt‰v‰n ker‰ysrivin tilauksen tiedot
		$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$orig_row['otunnus']}'";
		$laskures = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausta haettaessa\r\n\r\n");
		$laskurow = mysql_fetch_assoc($laskures);

		// tehd‰‰n pakkauskirjaimesta numero
		$pakkaus_kirjain_chk = ord($container_id) - 64;

		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$orig_row['nro']}' AND pakkausnro = '{$pakkaus_kirjain_chk}' ORDER BY RAND()";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		$response = "1,Uusi valittu pakkaus ei k‰y\r\n\r\n";

		while ($row = mysql_fetch_assoc($result)) {

			$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['otunnus']}'";
			$chkres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausta haettaessa\r\n\r\n");
			$chkrow = mysql_fetch_assoc($chkres);

			if ($chkrow['liitostunnus'] == $laskurow['liitostunnus']) {
				$pakkaus_kirjain = chr(64+$row['pakkausnro']);

				if ($all != '') {
					if ($all == 1) {
						$query = "UPDATE kerayserat SET pakkausnro = '{$pakkaus_kirjain_chk}' WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tila = 'K' AND pakkausnro = '{$orig_row['pakkausnro']}'";
						$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
					}
					else {
						$query = "UPDATE kerayserat SET pakkausnro = '{$pakkaus_kirjain_chk}' WHERE yhtio = '{$kukarow['yhtio']}' AND sscc = '{$sscc}' AND tunnus = '{$row_id}'";
						$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
					}
				}

				$response = "{$pakkaus_kirjain},0,\r\n\r\n";
				break;
			}
		}
	}
	else {
		$response = "1, Kui s‰‰ t‰nne jouduit?\r\n\r\n";
	}

	echo $response;
