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
	error_reporting(0);
	ini_set("display_errors", 0);
	ini_set("log_errors", 1);

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
		$kukarow['kuka']  = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		// Katsotaan onko k‰ytt‰j‰ll‰ jo ker‰yser‰ ker‰yksess‰
		$query = "	SELECT GROUP_CONCAT(tilausrivi) AS tilausrivit
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laatija = '{$kukarow['kuka']}'
					AND tila    = 'K'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		$row = mysql_fetch_assoc($result);

		if (trim($row['tilausrivit']) != '') {

			$kpl_arr = explode(",", $row['tilausrivit']);
			$kpl = count($kpl_arr);
			$n = 1;

			$query = "	SELECT keraysvyohyke
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$kukarow['kuka']}'
						AND extranet = ''
						AND keraysvyohyke != ''";
			$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰ysvyˆhykett‰ haettaessa\r\n\r\n");
			$ker_row = mysql_fetch_assoc($result);

			// haetaan ker‰ysvyˆhykkeen takaa ker‰ysj‰rjestys
			$query = "	SELECT keraysjarjestys
						FROM keraysvyohyke
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ker_row['keraysvyohyke']}'";
			$keraysjarjestys_res = pupe_query($query);
			$keraysjarjestys_row = mysql_fetch_assoc($keraysjarjestys_res);

			$orderby_select = $keraysjarjestys_row['keraysjarjestys'] == "V" ? ",".generoi_sorttauskentta("3") : "";
			$orderby = $keraysjarjestys_row['keraysjarjestys'] == 'P' ? "kokonaismassa" : ($keraysjarjestys_row['keraysjarjestys'] == "V" ? "sorttauskentta" : "vh.indeksi");

			$query = "	SELECT keraysvyohyke.nimitys AS ker_nimitys,
						tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
						IFNULL(vh.varmistuskoodi, '00') AS varmistuskoodi,
						tilausrivi.tuoteno, ROUND(kerayserat.kpl, 0) AS varattu, tilausrivi.yksikko, tuote.nimitys,
						kerayserat.pakkausnro, kerayserat.sscc, kerayserat.tunnus AS kerayseran_tunnus,
						(tuote.tuotemassa * ROUND(kerayserat.kpl, 0)) AS kokonaismassa,
						kerayserat.nro, tuote.kerayskommentti
						{$orderby_select}
						FROM tilausrivi
						JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.tunnus IN ({$row['tilausrivit']})
						ORDER BY {$orderby}";
			$rivi_result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe\r\n\r\n");

			while ($rivi_row = mysql_fetch_assoc($rivi_result)) {

				$rivi_row['kerayskommentti'] = str_replace(array("'", ","), "", $rivi_row['kerayskommentti']);

				$pakkauskirjain = strtoupper(chr((64+$rivi_row['pakkausnro'])));
				$tuotteen_nimitys = str_replace(array("'", ","), "", $rivi_row['nimitys']);

				$hyllypaikka = $rivi_row['hyllyalue'];
				$hyllypaikka = trim($rivi_row['hyllynro'])  != '' ? $hyllypaikka." ".$rivi_row['hyllynro']  : $hyllypaikka;
				$hyllypaikka = trim($rivi_row['hyllyvali']) != '' ? $hyllypaikka." ".$rivi_row['hyllyvali'] : $hyllypaikka;
				$hyllypaikka = trim($rivi_row['hyllytaso']) != '' ? $hyllypaikka." ".$rivi_row['hyllytaso'] : $hyllypaikka;

				$hyllypaikka = implode(" ", str_split(strtoupper(trim($hyllypaikka))));

				// W7 12 80 => W71280 => w71280 => w 7 128 0
				$rivi_row['tuoteno'] = strtoupper(str_replace(" ", "", $rivi_row['tuoteno']));

				$_tmp = str_split($rivi_row['tuoteno']);
				$_cnt = count($_tmp);
				$_arr = array();

				for ($i = 0; $i < $_cnt; $i++) {
					if ($i < $_cnt-1 and !is_numeric($_tmp[$i])) {
						array_push($_arr, $_tmp[$i], " ");
					}
					elseif ($i < $_cnt-1 and is_numeric($_tmp[$i]) and isset($_tmp[$i+1]) and !is_numeric($_tmp[$i+1])) {
						array_push($_arr, $_tmp[$i], " ");
					}
					else {
						array_push($_arr, $_tmp[$i]);
					}
				}

				$rivi_row['tuoteno'] = implode("", $_arr);

				$rivi_row['tuoteno'] = implode(" ,,, ", str_split($rivi_row['tuoteno'], 3));

				$rivi_row['yksikko'] = t_avainsana("Y", "", "and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite");

				$response .= "N,";
				$response .= substr($rivi_row['ker_nimitys'], 0, 255).",";
				// $response .= "{$kpl} rivi‰,{$rivi_row['sscc']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},{$rivi_row['tuoteno']},{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$rivi_row['kerayseran_tunnus']},{$tuotteen_nimitys},{$n},0\r\n";
				$response .= "{$kpl} rivi‰,{$rivi_row['nro']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},\"{$rivi_row['tuoteno']}\",{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$rivi_row['kerayseran_tunnus']},{$tuotteen_nimitys},{$rivi_row['kerayskommentti']},0\r\n";

				$n++;
			}
		}
		else {

			$query = "	SELECT keraysvyohyke, oletus_varasto
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$kukarow['kuka']}'
						AND extranet = ''
						AND keraysvyohyke != ''
						AND oletus_varasto != ''";
			$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰ysvyˆhykett‰ haettaessa\r\n\r\n");
			$row = mysql_fetch_assoc($result);
			
			$query = "	LOCK TABLES lasku WRITE,
						lasku AS lasku1 WRITE,
						lasku AS lasku2 WRITE,
						laskun_lisatiedot WRITE,
						asiakas AS asiakas1 READ,
						asiakas AS asiakas2 READ,
						tilausrivi WRITE,
						tilausrivi AS tilausrivi1 READ,
						tilausrivi AS tilausrivi2 READ,
						tilausrivin_lisatiedot WRITE,
						tilausrivin_lisatiedot AS tilrivlis1 READ,
						tilausrivin_lisatiedot AS tilrivlis2 READ,
						messenger WRITE,
						varaston_hyllypaikat AS vh READ,
						varaston_hyllypaikat AS vh1 READ,
						varaston_hyllypaikat AS vh2 READ,
						tuote READ,
						tuote AS tuote1 READ,
						tuote AS tuote2 READ,
						keraysvyohyke AS keraysvyohyke1 READ,
						keraysvyohyke AS keraysvyohyke2 READ,
						toimitustapa AS toimitustapa1 READ,
						toimitustapa AS toimitustapa2 READ,
						lahdot READ,
						lahdot AS lahdot1 WRITE,
						lahdot AS lahdot2 WRITE,
						tuoteperhe READ,
						kerayserat WRITE,
						tuotteen_toimittajat READ,
						pakkaus READ,
						avainsana WRITE,
						keraysvyohyke READ,
						asiakas READ,
						liitetiedostot READ,
						tilausrivi AS t2 READ,
						tilausrivin_lisatiedot AS tlt2 READ,
						tilausrivi AS tilausrivi_osto READ,
						tilausrivi AS tilausrivi_myynti READ,
						sarjanumeroseuranta READ,
						tilausrivi AS t3 READ,
						sanakirja WRITE,
						avainsana as a READ,
						avainsana as b READ,
						varaston_tulostimet READ,
						tuotepaikat READ,
						maksuehto READ,
						varastopaikat READ,
						kirjoittimet READ,
						kuka WRITE,
						asiakaskommentti READ,
						tuotteen_avainsanat READ,
						pankkiyhteystiedot READ,
						toimitustapa READ,
						yhtion_toimipaikat READ,
						yhtion_parametrit READ,
						tuotteen_alv READ,
						maat READ,
						rahtisopimukset READ,
						rahtisopimukset AS rahtisopimukset2 READ,
						pakkaamo WRITE,
						avainsana as avainsana_kieli READ,
						lasku as vanha_lasku READ,
						varaston_tulostimet as vanha_varaston_tulostimet READ,
						yhtio READ";
			$result = pupe_query($query);

			$lukotetaan = FALSE;

			$erat = tee_keraysera($row['keraysvyohyke'], $row['oletus_varasto'], FALSE);

			if (isset($erat['tilaukset']) and count($erat['tilaukset']) != 0) {
				$otunnukset = implode(",", $erat['tilaukset']);
				
				require('inc/tallenna_keraysera.inc');

				$kerayslistatunnus = trim(array_shift($erat['tilaukset']));

				// tilaus on jo tilassa N A, p‰ivitet‰‰n nyt tilaus "ker‰yslista tulostettu" eli L A
				$query = "	UPDATE lasku SET
							tila = 'L',
							lahetepvm = now(),
							kerayslista = '{$kerayslistatunnus}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus in ({$otunnukset})";
				$upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilauksen p‰ivityksen yhteydess‰\r\n\r\n");

				if ($lukotetaan) {
					// lukitaan tableja
			 		$query = "UNLOCK TABLES";
					$result = pupe_query($query);
				}

				$query = "	SELECT GROUP_CONCAT(tilausrivi) AS tilausrivit
							FROM kerayserat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laatija = '{$kukarow['kuka']}'
							AND tila = 'K'";
				$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

				$row = mysql_fetch_assoc($result);

				$kpl_arr = explode(",", $row['tilausrivit']);
				$kpl = count($kpl_arr);
				$n = 1;

				// haetaan ker‰ysvyˆhykkeen takaa ker‰ysj‰rjestys
				$query = "	SELECT keraysjarjestys
							FROM keraysvyohyke
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$row['keraysvyohyke']}'";
				$keraysjarjestys_res = pupe_query($query);
				$keraysjarjestys_row = mysql_fetch_assoc($keraysjarjestys_res);

				$orderby_select = $keraysjarjestys_row['keraysjarjestys'] == "V" ? ",".generoi_sorttauskentta("3") : "";
				$orderby = $keraysjarjestys_row['keraysjarjestys'] == 'P' ? "kokonaismassa" : ($keraysjarjestys_row['keraysjarjestys'] == "V" ? "sorttauskentta" : "vh.indeksi");

					$query = "	SELECT keraysvyohyke.nimitys AS ker_nimitys,
								tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
								IFNULL(vh.varmistuskoodi, '00') AS varmistuskoodi,
							tilausrivi.tuoteno, ROUND(kerayserat.kpl, 0) AS varattu, tilausrivi.yksikko, tuote.nimitys,
							kerayserat.pakkausnro, kerayserat.sscc, kerayserat.tunnus AS kerayseran_tunnus,
							(tuote.tuotemassa * ROUND(kerayserat.kpl, 0)) AS kokonaismassa,
							kerayserat.nro, tuote.kerayskommentti
							{$orderby_select}
								FROM tilausrivi
							JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tunnus IN ({$row['tilausrivit']})
							ORDER BY {$orderby}";
					$rivi_result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe\r\n\r\n");

				while ($rivi_row = mysql_fetch_assoc($rivi_result)) {

					$rivi_row['kerayskommentti'] = str_replace(array("'", ","), "", $rivi_row['kerayskommentti']);

					$pakkauskirjain = strtoupper(chr((64+$rivi_row['pakkausnro'])));
					$tuotteen_nimitys = str_replace(array("'", ","), "", $rivi_row['nimitys']);

					$hyllypaikka = $rivi_row['hyllyalue'];
					$hyllypaikka = trim($rivi_row['hyllynro']) != '' ? $hyllypaikka." ".$rivi_row['hyllynro'] : $hyllypaikka;
					$hyllypaikka = trim($rivi_row['hyllyvali']) != '' ? $hyllypaikka." ".$rivi_row['hyllyvali'] : $hyllypaikka;
					$hyllypaikka = trim($rivi_row['hyllytaso']) != '' ? $hyllypaikka." ".$rivi_row['hyllytaso'] : $hyllypaikka;

					$hyllypaikka = implode(" ", str_split(strtoupper(trim($hyllypaikka))));

					// W7 12 80 => W71280 => w71280 => w 7 128 0
					$rivi_row['tuoteno'] = strtoupper(str_replace(" ", "", $rivi_row['tuoteno']));

					$_tmp = str_split($rivi_row['tuoteno']);
					$_cnt = count($_tmp);
					$_arr = array();

					for ($i = 0; $i < $_cnt; $i++) {
						if ($i < $_cnt-1 and !is_numeric($_tmp[$i])) {
							array_push($_arr, $_tmp[$i], " ");
						}
						elseif ($i < $_cnt-1 and is_numeric($_tmp[$i]) and isset($_tmp[$i+1]) and !is_numeric($_tmp[$i+1])) {
							array_push($_arr, $_tmp[$i], " ");
						}
						else {
							array_push($_arr, $_tmp[$i]);
						}
					}

					$rivi_row['tuoteno'] = implode("", $_arr);

					$rivi_row['tuoteno'] = implode(" ,,, ", str_split($rivi_row['tuoteno'], 3));

					$rivi_row['yksikko'] = t_avainsana("Y", "", "and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite");

					$response .= "N,";
					$response .= substr($rivi_row['ker_nimitys'], 0, 255).",";
					// $response .= "{$kpl} rivi‰,{$rivi_row['sscc']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},{$rivi_row['tuoteno']},{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$rivi_row['kerayseran_tunnus']},{$tuotteen_nimitys},{$n},0\r\n";
					$response .= "{$kpl} rivi‰,{$rivi_row['nro']},{$hyllypaikka},{$rivi_row['varmistuskoodi']},\"{$rivi_row['tuoteno']}\",{$rivi_row['varattu']},{$rivi_row['yksikko']},{$pakkauskirjain},{$rivi_row['kerayseran_tunnus']},{$tuotteen_nimitys},{$rivi_row['kerayskommentti']},0\r\n";

					$n++;
				}
			}

			// poistetaan lukko
			$query = "UNLOCK TABLES";
			$res   = pupe_query($query);

			}

		if ($response == '') {
			$response = "N,,,,,,,,,,,,,1,Ei yht‰‰n ker‰yser‰‰\r\n\r\n";
		}
		else {
			$response .= "\r\n";
		}
	}
	elseif ($sanoma == "PrintContainers") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka']  = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$nro = mysql_real_escape_string(trim($sisalto[3]));
		$printer_id = (int) trim($sisalto[4]);

		$query = "	SELECT tunnus
					FROM kirjoittimet
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND jarjestys = '{$printer_id}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe kirjoittimen komentoa haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		$komento['reittietiketti'] = $row['tunnus'];

		$query = "	SELECT *
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nro = '{$nro}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		if (mysql_num_rows($result) == 0) {
			$response = "1, Ei printattavaa\r\n\r\n";
		}

		if (trim($response) == '') {
			// Tulostetaan ker‰yser‰
			$kerayseran_numero = $nro;
			
			require("inc/tulosta_reittietiketti.inc");			

			$response = "0, Tulostus onnistui\r\n\r\n";
		}
	}
	elseif ($sanoma == "Picked") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$nro = (int) trim($sisalto[3]);
		$row_id = (int) trim($sisalto[4]);
		$qty = (int) trim($sisalto[5]);
		$package = mysql_real_escape_string(trim($sisalto[6]));
		$splitlineflag = (int) trim($sisalto[7]);

		$pitaako_splitata = false;

		$query = "	SELECT *
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nro = '{$nro}'
					AND tunnus = '{$row_id}'";
		$result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$row = mysql_fetch_assoc($result);

		// splitataan rivi, splittauksen ensimm‰inen rivi
		if ($splitlineflag == 1) {

			$query = "UPDATE kerayserat SET tila = 'T', kpl = '{$qty}', kpl_keratty = '{$qty}' WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
			$upd_res = pupe_query($query);

		}
		// 2 = 2 ... n splitattu rivi
		// 3 = viimeinen splitattu rivi
		elseif ($splitlineflag == 2 or $splitlineflag == 3) {

			$fields = "yhtio";
			$values = "'{$kukarow['yhtio']}'";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {

				$fieldname = mysql_field_name($result, $i);

				if ($fieldname == 'tunnus' or $fieldname == 'yhtio') continue;

				$fields .= ", ".$fieldname;

				switch ($fieldname) {
					case 'sscc':
						$query = "LOCK TABLES avainsana WRITE";
						$lock_res   = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe lukituksen yhteydess‰\r\n\r\n");

						$query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
						$selite_result = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe avainsanaa haettaessa\r\n\r\n");
						$selite_row = mysql_fetch_assoc($selite_result);

						$uusi_sscc = is_numeric($selite_row['selite']) ? (int) $selite_row['selite'] + 1 : 1;

						$query = "UPDATE avainsana SET selite = '{$uusi_sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
						$update_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe avainsanaa p‰ivitett‰ess‰\r\n\r\n");

						// poistetaan lukko
						$query = "UNLOCK TABLES";
						$unlock_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe lukitusta poistettaessa\r\n\r\n");

						$values .= ", '{$uusi_sscc}'";
						break;
					case 'tila':
						$values .= ", 'T'";
						break;
					case 'pakkausnro':
						$values .= ", '".(ord($package) - 64)."'";
						break;
					case 'kpl':
					case 'kpl_keratty':
						$values .= ", '{$qty}'";
						break;
					default:
						$values .= ", '".$row[$fieldname]."'";
				}
			}

			$query = "INSERT INTO kerayserat ({$fields}) VALUES ({$values})";
			$insres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰n rivi‰ luodessa\r\n\r\n");

			if ($splitlineflag == 3) {

				$query = "SELECT varattu FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['tilausrivi']}'";
				$chk_res = pupe_query($query);
				$chk_row = mysql_fetch_assoc($chk_res);

				$query = "SELECT SUM(kpl_keratty) kappaleet FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tilausrivi = '{$row['tilausrivi']}'";
				$sum_res = pupe_query($query);
				$sum_row = mysql_fetch_assoc($sum_res);

				if ($chk_row['varattu'] > $sum_row['kappaleet']) {
					$pitaako_splitata = true;
					$qty = $sum_row['kappaleet'];
				}
				else {
					$query = "UPDATE tilausrivi SET varattu = '{$sum_row['kappaleet']}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['tilausrivi']}'";
					$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausrivi‰ p‰ivitett‰ess‰\r\n\r\n");
				}
			}

		}
		// ei splitata rivi‰ eli normaali rivi, $splitlineflag == 0
		else {

		}

		// katsotaan m‰ts‰‰v‰tkˆ kappalem‰‰r‰t. jos ei niin pit‰‰ splitata tilausrivi ja laittaa loput puutteeksi

		if (($splitlineflag == 0 and $qty < (int) $row['kpl']) or $pitaako_splitata) {
			// ker‰‰j‰ otti v‰hemm‰n mit‰ oli tilausrivill‰, splitataan tilausrivi ja p‰ivitet‰‰n loput puutteeksi
			$query = "SELECT * FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['tilausrivi']}'";
			$tilrivires = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausrivi‰ haettaessa\r\n\r\n");
			$tilrivirow = mysql_fetch_array($tilrivires);

			mysql_data_seek($tilrivires, 0);

			$fields = "yhtio";
			$values = "'{$kukarow['yhtio']}'";

			$puutevarattu = $pitaako_splitata ? $tilrivirow['varattu'] - $qty : $row['kpl'] - $qty;

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

			$query = "UPDATE kerayserat SET kpl = '{$qty}', kpl_keratty = '{$qty}' WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
			$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
		}

		if ($splitlineflag == 0 or $splitlineflag == 3) {

			if ($splitlineflag == 0) {
				$query = "UPDATE kerayserat SET tila = 'T', kpl_keratty = '{$qty}' WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
		$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
			}

			$query = "UPDATE tilausrivi SET keratty = '{$kukarow['kuka']}', kerattyaika = now() WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['tilausrivi']}'";
			$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausrivi‰ p‰ivitett‰ess‰\r\n\r\n");

		}

		$response = "0x100";

	}
	elseif ($sanoma == "AllPicked") {

		$kukarow['yhtio'] = 'artr';

		$query = "SELECT * FROM kuka WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '".mysql_real_escape_string(trim($sisalto[2]))."'";
		$kukares = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe k‰ytt‰j‰‰ haettaessa\r\n\r\n");
		$kukarow = mysql_fetch_assoc($kukares);

		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$nro = (int) trim($sisalto[3]);

		$query = "	SELECT COUNT(otunnus) AS kpl, GROUP_CONCAT(otunnus) AS otunnukset
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nro = '{$nro}'
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

			$query = "	SELECT pakkaus.pakkaus, GROUP_CONCAT(DISTINCT kerayserat.otunnus) tunnukset,
						ROUND(SUM(tuote.tuotemassa * kerayserat.kpl_keratty) + pakkaus.oma_paino, 1) tuotemassa,
						ROUND(SUM((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl_keratty) * IF(pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1)), 2) as kuutiot,
						COUNT(DISTINCT CONCAT(kerayserat.nro,kerayserat.pakkaus,kerayserat.pakkausnro)) AS maara
							FROM kerayserat
							JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
							JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.otunnus IN ({$row['otunnukset']})
						AND kerayserat.tila = 'K'
						GROUP BY 1
							ORDER BY pakkausnro ASC";
			$keraysera_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe painotietoja rahtikirjalle haettaessa\r\n\r\n");

				$rahtikirjan_pakkaukset = array();

				while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
					if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'] = 0;
					if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'] = 0;
				if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['maara'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['maara'] = 0;

				$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']][$keraysera_row['tunnukset']]['paino'] 	= $keraysera_row['tuotemassa'];
				$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']][$keraysera_row['tunnukset']]['kuutiot'] 	= $keraysera_row['kuutiot'];
				$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']][$keraysera_row['tunnukset']]['maara']	= $keraysera_row['maara'];
				}

			$query = "	SELECT 'MUU KOLLI' AS pakkaus, GROUP_CONCAT(DISTINCT kerayserat.otunnus) tunnukset,
						ROUND(SUM(tuote.tuotemassa * kerayserat.kpl_keratty), 1) tuotemassa,
						ROUND(SUM(tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl_keratty), 2) as kuutiot,
						COUNT(DISTINCT CONCAT(kerayserat.nro,kerayserat.pakkaus,kerayserat.pakkausnro)) AS maara
						FROM kerayserat
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.otunnus IN ({$row['otunnukset']})
						AND kerayserat.tila = 'K'
						AND kerayserat.pakkaus = '999'
						GROUP BY 1
						ORDER BY pakkausnro ASC";
			$keraysera_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe painotietoja rahtikirjalle haettaessa\r\n\r\n");

			while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
				if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'] = 0;
				if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'] = 0;
				if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['maara'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['maara'] = 0;

				$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']][$keraysera_row['tunnukset']]['paino'] 	= $keraysera_row['tuotemassa'];
				$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']][$keraysera_row['tunnukset']]['kuutiot'] 	= $keraysera_row['kuutiot'];
				$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']][$keraysera_row['tunnukset']]['maara']	= $keraysera_row['maara'];
			}

			// p‰ivitet‰‰n ker‰yser‰n tila "Ker‰tty"-tilaan
			$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus IN ({$row['otunnukset']})";
			$tila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");

				// esisyˆtet‰‰n rahtikirjan tiedot
				if (count($rahtikirjan_pakkaukset) > 0) {

				foreach ($rahtikirjan_pakkaukset as $pak => $otun_arr) {

					$maara_x = 0;
					$paino_x = 0;
					$kuutiot_x = 0;
					$otsikkonro = 0;

					foreach ($otun_arr as $otun_x => $arr) {

						foreach (explode(",", $otun_x) as $otun) {

							if ($maara_x == 0 and $paino_x == 0 and $kuutiot_x == 0 and $otsikkonro == 0) {
								$maara_x = $arr['maara'];
								$paino_x = $arr['paino'];
								$kuutiot_x = $arr['kuutiot'];
							}
							else {
								$maara_x = 0;
								$paino_x = 0;
								$kuutiot_x = 0;
							}

							if ($otsikkonro == 0) $otsikkonro = $otun;

							$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$otun}'";
							$laskures = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilauksen tietoja haettaessa\r\n\r\n");
							$laskurow = mysql_fetch_assoc($laskures);

						$query_ker  = "	INSERT INTO rahtikirjat SET
											kollit = '{$maara_x}',
											kilot = '{$paino_x}',
											kuutiot = '{$kuutiot_x}',
											pakkauskuvaus = '',
										pakkaus 		= '{$pak}',
										rahtikirjanro 	= '{$laskurow['tunnus']}',
											otsikkonro = '{$otsikkonro}',
										tulostuspaikka 	= '{$laskurow['varasto']}',
										toimitustapa 	= '{$laskurow['toimitustapa']}',
										yhtio 			= '{$kukarow['yhtio']}'";
							$ker_res = mysql_query($query_ker) or die("1, Tietokantayhteydess‰ virhe rahtikirjan tietoja tallentaessa\r\n\r\n");

						// jos kyseess‰ on toimitustapa jonka rahtikirja on hetitulostus, tulostetaan myˆs rahtikirja t‰ss‰ vaiheessa
						if ($laskurow['tulostustapa'] == 'H' and $laskurow["nouto"] == "") {

								$query = "UPDATE lasku SET alatila = 'B' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['tunnus']}'";
								$alatila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilauksen tilaa p‰ivitett‰ess‰\r\n\r\n");

							// p‰ivitet‰‰n ker‰yser‰n tila "Rahtikirja tulostettu"-tilaan
							$query = "UPDATE kerayserat SET tila = 'R' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
								$tila_upd_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰n tilaa p‰ivitett‰ess‰\r\n\r\n");

							// rahtikirjojen tulostus vaatii seuraavat muuttujat:

							// $toimitustapa_varasto	toimitustavan selite!!!!varastopaikan tunnus
							// $tee						t‰ss‰ pit‰‰ olla teksti tulosta

							$toimitustapa_varasto = $laskurow['toimitustapa']."!!!!".$kukarow['yhtio']."!!!!".$laskurow['varasto'];
							$tee				  = "tulosta";

							$nayta_pdf = 'foo';

							require ("rahtikirja-tulostus.php");
						}
						}
					}
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

			$ei_echoa = 'eini';

			foreach (explode(",", $row['otunnukset']) as $otun) {

				$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$otun}'";
				$laskures = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe tilausta haettaessa\r\n\r\n");
				$laskurow = mysql_fetch_assoc($laskures);

				$params = array(
					'laskurow' => $laskurow,
					'sellahetetyyppi' => '',
					'extranet_tilausvahvistus' => '',
					'naytetaanko_rivihinta' => '',
					'tee' => '',
					'toim' => '',
					'query_ale_lisa' => $query_ale_lisa,
					'komento' => $komento,
					'ei_echoa' => $ei_echoa
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

		$nro = (int) trim($sisalto[3]);

		// $query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tila = 'K'";
		// $chkres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		// if (mysql_num_rows($chkres) > 0) {
		// 	$response = "1,Kaikki rivit ei ole ker‰tty\r\n\r\n";
		// }
		// else {
			$response = "0,\r\n\r\n";
		// }
	}
	elseif ($sanoma == "SignOff") {

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));

		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND laatija = '{$kukarow['kuka']}' AND tila = 'K'";
		$chkres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");

		if (mysql_num_rows($chkres) > 0) {
			$response = "1,Kaikki rivit ei ole ker‰tty\r\n\r\n";
		}
		else {
			$response = "0,\r\n\r\n";
		}
	}
	elseif ($sanoma == "Replenishment") {

	}
	elseif ($sanoma == "NewContainer") {

		/**
		 * Case1 (Normaali):
		 * Kaikki edelliset rivit jotka ker‰tty A:han s‰ilyv‰t kyseisess‰ alustassa, mutta nykyinen/tulevat rivit menev‰t B:hen.
		 *
		 * Case2 (Jaa rivi):
		 * Edelliset rivit + se m‰‰r‰ jaetusta rivist‰ ovat alustassa A. Seuraavat rivit + jaetun rivin loput menev‰t B:hen.
		 */

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$nro = (int) trim($sisalto[3]);
		$row_id = (int) trim($sisalto[4]);
		$printer_id = (int) trim($sisalto[5]);
		$splitlineflag = (int) trim($sisalto[6]);

		###### TEHDƒƒN UUSI PAKKAUSKIRJAIN
		$query = "SELECT (MAX(pakkausnro) + 1) uusi_pakkauskirjain FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}'";
		$uusi_paknro_res = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$uusi_paknro_row = mysql_fetch_assoc($uusi_paknro_res);

		$pakkaus_kirjain = chr((64+$uusi_paknro_row['uusi_pakkauskirjain']));

		if ($splitlineflag == 0) {
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
		}

		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
		$chkres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ haettaessa\r\n\r\n");
		$chkrow = mysql_fetch_array($chkres);

		$sscclisa = $splitlineflag == 0 ? " sscc = '{$uusi_sscc}', " : "";

		$query = "	UPDATE kerayserat SET
					{$sscclisa}
					pakkausnro = '{$uusi_paknro_row['uusi_pakkauskirjain']}'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila = 'K'
					AND pakkausnro = '{$chkrow['pakkausnro']}'
					AND nro = '{$nro}'";
		$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰n rivej‰ p‰ivitett‰ess‰\r\n\r\n");

		$query = "	SELECT *
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nro = '{$nro}'
					AND pakkausnro = '{$uusi_paknro_row['uusi_pakkauskirjain']}'";
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

		/**
		 * Case1 (Normaali):
		 * Jos pyydet‰‰n ker‰yksen yhteydess‰ "Vaihda alusta", niin WMS palauttaa Vocollectille pakkauskirjaimen, joka on sallittu (sama asiakas) ja siirt‰‰ sinne joko kyseisen rivin tai kaikki loput ker‰yksess‰ olevat rivit.
		 *
		 * Case2 (Jaa rivi):
		 * Ensimm‰iseen laatikkoon laitetaan 5 kpl, jonka j‰lkeen halutaan "vaihda alusta",
		 * niin WMS ei p‰ivit‰ A-kirjainta, vaan palauttaa Vocollectille pakkauskirjaimen, joka on sallittu (sama asiakas).
		 * Toisen jaetun rivin kuittauksen kohdalla p‰ivitt‰‰ uudelle ker‰ysriville pakkauskirjaimeksi aiemmin valitun pakkauskirjaimen.
		 * Ei tulosteta SSCC-koodia.
		 */

		$kukarow['yhtio'] = 'artr';
		$kukarow['kuka'] = mysql_real_escape_string(trim($sisalto[2]));
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$nro = (int) trim($sisalto[3]);
		$row_id = (int) trim($sisalto[4]);
		$container_id = trim($sisalto[5]);
		$all = trim($sisalto[6]);

		// haetaan ker‰tt‰v‰ ker‰ysrivi
		$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
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
						$query = "UPDATE kerayserat SET pakkausnro = '{$pakkaus_kirjain_chk}' WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tila = 'K' AND pakkausnro = '{$orig_row['pakkausnro']}' AND sscc = '{$orig_row['sscc']}'";
						$updres = mysql_query($query) or die("1, Tietokantayhteydess‰ virhe ker‰yser‰‰ p‰ivitett‰ess‰\r\n\r\n");
					}
					else {
						$query = "UPDATE kerayserat SET pakkausnro = '{$pakkaus_kirjain_chk}' WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}' AND tunnus = '{$row_id}'";
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
