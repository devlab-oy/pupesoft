<?php

	if (empty($argv)) {
	    die('<p>Tämän scriptin voi ajaa ainoastaan komentoriviltä.</p>');
	}

	if ($argv[1] == '') {
		die("Yhtiö on annettava!!");
	}
	
	$yhtio = $argv[1];

	require('/var/www/html/pupesoft/inc/connect.inc');
	require('/var/www/html/pupesoft/inc/functions.inc');

	$path = "/tmp/logisticar_siirto_$yhtio/";
	
	// Sivotaan eka vanha pois
	system("rm -rf $path");
	
	// Tehään uysi dirikka
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
	
	//Siirretään failit logisticar palvelimelle	
	siirto($path);

	function siirto ($path) {
		GLOBAL $host_logisticar, $user_logisticar, $pass_logisticar, $path_logisticar;
		
		$path_localdir 	= "/mnt/logisticar_siirto/";
		
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
		global $path_nimike, $yhtio, $where_logisticar;

		echo "Tuotteet ...";

		$query = "	SELECT 
					tuote.tuoteno        	nimiketunnus,
        			tuote.nimitys           nimitys,
			        tuote.yksikko           yksikko,
			        concat_ws(' ', tuote.try, avainsana.selitetark) tuoteryhma,
					tuote.kustp				kustannuspaikka,
					'0'						toimittajatunnus,
					'0'						varastotunnus,
					'0'						toimittajannimiketunnus,
					'1'						hintayksikko,
					tuote.ei_varastoida 	varastoimiskoodi,
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
			echo "Yhtään tuotetta ei löytynyt $query\n";
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

			// mones tämä on
			$row++;

			$query = "	SELECT 
						liitostunnus toimittajatunnus, 
						toim_tuoteno toimittajannimiketunnus
						FROM tuotteen_toimittajat
						WHERE tuoteno = '{$tuote['tuoteno']}'
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
						where tuoteno='{$tuote['tuoteno']}' 
						and oletus != '' 
						and yhtio='$yhtio' 
						limit 1";
			$res = mysql_query($query) or pupe_error($query);
			$paikka = mysql_fetch_assoc($res);

			// mikä varasto
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
		global $path_asiakas, $yhtio, $where_logisticar;

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
			echo "Yhtään asiakasta ei löytynyt\n";
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
		global $path_toimittaja, $yhtio, $where_logisticar;

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
			echo "Yhtään toimittajaa ei löytynyt\n";
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
		global $path_varasto, $lisavarattu, $yhtio, $where_logisticar;

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
			echo "Yhtään varastoa ei löytynyt\n";
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

		// tehdään otsikot
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

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
		}

		fclose($fp);
		echo "Done.\n";
	}

	function varastotapahtumat($limit = '') {
		global $path_tapahtumat, $yhtio, $where_logisticar;

		echo "Varastotapahtumat... ";
		
		if (! $fp = fopen($path_tapahtumat, 'w+')) {
			die("Ei voitu avata filea $path_tapahtumat");
		}
		
		if ($where_logisticar["paiva_ajo"] != "") {
			$pvmlisa = " and tilausrivi.laskutettuaika >= date_sub(now(), interval 30 day) ";
		}
		else {
			$pvmlisa = " and tilausrivi.laskutettuaika > '0000-00-00' ";
		}

	    $query = "	SELECT 
					tilausrivi.tuoteno 			nimiketunnus,
					lasku.liitostunnus          asiakastunnus,
					lasku.liitostunnus			toimitusasiakas,
					tilausrivi.laskutettuaika   tapahtumapaiva,
					tilausrivi.tyyppi           tapahtumalaji,
					tilausrivi.rivihinta        myyntiarvo,
					tilausrivi.rivihinta        ostoarvo,					
					tilausrivi.kate             kate,					
					tilausrivi.kpl              tapahtumamaara,
					lasku.laskunro              laskunumero,					
					kuka.kuka	                myyjatunnus,
					lasku.yhtio_toimipaikka		toimipaikka,
					varastopaikat.tunnus        varastotunnus,
					tilausrivi.tyyppi  			tapahtumatyyppi
					FROM tilausrivi
					JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.uusiotunnus and lasku.yhtio=tilausrivi.yhtio
					LEFT JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio
					LEFT JOIN varastopaikat ON
					concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
					concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))	            
					and varastopaikat.yhtio=tuotepaikat.yhtio
					LEFT JOIN kuka ON kuka.tunnus=lasku.myyja and kuka.yhtio=lasku.yhtio
					WHERE tilausrivi.tyyppi IN ('L', 'O') 
					$pvmlisa
					and tilausrivi.yhtio = '$yhtio'
					ORDER BY tilausrivi.laskutettuaika
					$limit";
	    $res = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($res);
		$row = 0;
		if ($rows == 0) {
			echo "Yhtään varastotapahtumaa ei löytynyt\n";
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

		// tehdään otsikot
		create_headers($fp, array_keys($headers));

	    while ($trow = mysql_fetch_assoc($res)) {
			$row++;

			switch(strtoupper($trow['tapahtumalaji'])) {
				// ostot
				case 'O':

					// 1 = saapuminen tai oston palautus
					$trow['tapahtumalaji'] = 1;

					// myyntiarvo on 0
					$trow['myyntiarvo'] = 0;

					// jos kpl alle 0 niin tämä on oston palautus
					// jolloin hinta myös miinus
					if ($trow['tapahtumamaara'] < 0) {
						// tapahtumamaara on aina positiivinen logisticarissa
						$trow['tapahtumamaara'] = -1 * $trow['tapahtumamaara'];
					}

			        break;

				// myynnit
				case 'L':

					// 2 = otto tai myynninpalautus
					$trow['tapahtumalaji'] = 2;

					// ostoarvo
					$trow['ostoarvo'] = $trow['myyntiarvo'] - $trow['kate'];

					// tämä on myynninpalautus eli myyntiarvo on negatiivinen
					if ($trow['tapahtumamaara'] < 0) {
						// tapahtumamaara on aina positiivinen logisticarissa
						$trow['tapahtumamaara'] = -1 * $trow['tapahtumamaara'];
					}

					break;
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
		global $path_myynti, $yhtiorow, $yhtio, $where_logisticar;

		echo "Myynnit... ";
		
		if (! $fp = fopen($path_myynti, 'w+')) {
			die("Ei voitu avata filea $path_myynti");
		}

	    $query = "	SELECT 
					tilausrivi.tuoteno nimiketunnus,
					lasku.liitostunnus asiakastunnus,
					lasku.liitostunnus toimitusasiakas,
					tilausrivi.toimaika toimituspaiva,
					tilausrivi.tyyppi tapahtumalaji,
					round(tilausrivi.hinta  / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), $yhtiorow[hintapyoristys]) myyntiarvo,
					(tilausrivi.varattu+tilausrivi.jt) * tuote.kehahin ostoarvo,
					tilausrivi.varattu tapahtumamaara,				
					lasku.tunnus tilausnro,
					kuka.kuka myyjatunnus,					
					lasku.yhtio_toimipaikka	toimipaikka,
					varastopaikat.tunnus varastotunnus										
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
					AND tilausrivi.tyyppi IN ('L','O')
					AND tilausrivi.laskutettuaika = '0000-00-00' 
					AND tilausrivi.yhtio = '$yhtio'
					ORDER BY tilausrivi.laadittu
					$limit";
		$res = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($res);
		$row = 0;
		if ($rows == 0) {
			echo "Yhtään myyntitapahtumaa ei löytynyt\n";
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

		// tehdään otsikot
		create_headers($fp, array_keys($headers));

		while ($trow = mysql_fetch_assoc($res)) {
			$row++;

			switch ($trow['tapahtumalaji']) {
				case 'L':
					$trow['tapahtumalaji']	= '4';
					break;
				case 'O':
					$trow['tapahtumalaji'] 	= '3';
					$trow['myyntiarvo'] 	= 0;
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