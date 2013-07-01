<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (trim($argv[1]) == '') {
		echo "Et antanut yhtiötä!\n";
		exit;
	}

	// Otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	$kukarow['yhtio'] = (string) $argv[1];
	$kukarow['kuka']  = 'cron';
	$kukarow['kieli'] = 'fi';

	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	// Kuinka pitkälle tulevaisuuteen lähtöjä generoidaan
	if (isset($argv[2]) and trim($argv[2]) != '') {
		$paivia_eteenpain = (int) trim($argv[2]);
	}
	else {
		$paivia_eteenpain = 14;
	}

	// Päiväajo?
	if (isset($argv[3]) and trim($argv[3]) != '') {
		$paivaajo = TRUE;
	}
	else {
		$paivaajo = FALSE;
	}

	// Poistetaan aktiiviset, vanhentuneet lähdöt joihin ei oo liitetty yhtään tilausta
	$query = "	SELECT tunnus
				FROM lahdot
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND aktiivi = ''
				AND pvm < CURDATE()
				order by tunnus";
	$chk_res = pupe_query($query);

	while ($chk_row = mysql_fetch_assoc($chk_res)) {

		$query = "	SELECT tunnus
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila IN ('N','L')
					AND alatila not in ('D','X')
					AND toimitustavan_lahto = $chk_row[tunnus]";
		$chk_res2 = pupe_query($query);

		if (mysql_num_rows($chk_res2) == 0) {
			$query = "	DELETE FROM lahdot
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$chk_row['tunnus']}'";
			$del_res = pupe_query($query);
		}
	}

	// Poistetaan sellaiset lähdöt, jotka on poistettu toimitustapojen_lahdoista ja joissa ei ole tilauksia
	if ($paivaajo) {
		$query = "	SELECT *
					FROM lahdot
					WHERE yhtio  = '{$kukarow['yhtio']}'
					AND aktiivi  = ''
					AND pvm    	 > CURDATE()
					AND laatija  = 'cron'
					ORDER BY tunnus";
	}
	else {
		$query = "	SELECT *
					FROM lahdot
					WHERE yhtio  = '{$kukarow['yhtio']}'
					AND aktiivi  = ''
					AND pvm     >= CURDATE()
					AND laatija  = 'cron'
					ORDER BY tunnus";
	}

	$chk_res = pupe_query($query);

	// Loopataan kaikki generoidut lähdöt
	while ($chk_row = mysql_fetch_assoc($chk_res)) {

		// Tehdään asiakasluokka-konversio toisin päin
		$wquery = "	SELECT group_concat(concat('\'',selite,'\'')) luokka
					FROM avainsana
					WHERE yhtio 	 = '$kukarow[yhtio]'
					AND laji 		 = 'ASIAKASLUOKKA'
					AND selitetark_3 = '{$chk_row['asiakasluokka']}'";
		$wtres  = pupe_query($wquery);
		$wtrow  = mysql_fetch_assoc($wtres);

		if ($wtrow['luokka'] != "") {
			$asiakasluokka = $wtrow['luokka'];
		}
		else {
			$asiakasluokka = "";
		}

		// Löytyykö vielä toimitustavan_lahdot oletus ja onko oletus voimassa?
		$query1 = "	SELECT tunnus
					FROM toimitustavan_lahdot
					WHERE yhtio 			 = '{$kukarow['yhtio']}'
					AND lahdon_viikonpvm 	 = '{$chk_row['lahdon_viikonpvm']}'
					AND lahdon_kellonaika 	 = '{$chk_row['lahdon_kellonaika']}'
					AND viimeinen_tilausaika = '{$chk_row['viimeinen_tilausaika']}'
					AND kerailyn_aloitusaika = '{$chk_row['kerailyn_aloitusaika']}'
					AND terminaalialue 		 = '{$chk_row['terminaalialue']}'
					AND asiakasluokka 		IN ({$asiakasluokka})
					AND liitostunnus 		 = '{$chk_row['liitostunnus']}'
					AND varasto 			 = '{$chk_row['varasto']}'
					AND aktiivi				!= 'E'
					AND (alkupvm = '0000-00-00' OR (alkupvm != '0000-00-00' AND alkupvm <= '{$chk_row['pvm']}'))";
		$chk_res2 = pupe_query($query1);

		// Voimassa olevaa oletusta ei löytynyt, löytyykö 'poistettu' joka on vielä lähdön päivänä voimassa?
		if (mysql_num_rows($chk_res2) == 0) {
			$query2 = "	SELECT tunnus
						FROM toimitustavan_lahdot
						WHERE yhtio 			 = '{$kukarow['yhtio']}'
						AND lahdon_viikonpvm 	 = '{$chk_row['lahdon_viikonpvm']}'
						AND lahdon_kellonaika 	 = '{$chk_row['lahdon_kellonaika']}'
						AND viimeinen_tilausaika = '{$chk_row['viimeinen_tilausaika']}'
						AND kerailyn_aloitusaika = '{$chk_row['kerailyn_aloitusaika']}'
						AND terminaalialue 		 = '{$chk_row['terminaalialue']}'
						AND asiakasluokka 		IN ({$asiakasluokka})
						AND liitostunnus 		 = '{$chk_row['liitostunnus']}'
						AND varasto 			 = '{$chk_row['varasto']}'
						AND aktiivi				 = 'E'
						AND alkupvm 			!= '0000-00-00'
						AND alkupvm 			 > '{$chk_row['pvm']}'";
			$chk_res2 = pupe_query($query2);
		}

		// Onko lähdössä tilauksia?
		$query = "	SELECT tunnus
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila IN ('N','L')
					AND alatila not in ('D','X')
					AND toimitustavan_lahto = {$chk_row['tunnus']}";
		$chk_res3 = pupe_query($query);

		if (mysql_num_rows($chk_res2) == 0 and mysql_num_rows($chk_res3) == 0) {
			$query = "	DELETE FROM lahdot
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$chk_row['tunnus']}'";
			$del_res = pupe_query($query);
		}
		elseif (mysql_num_rows($chk_res2) == 0 and mysql_num_rows($chk_res3) != 0) {
			$query = "	UPDATE lahdot
						SET aktiivi = 'T'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$chk_row['tunnus']}'";
			$del_res = pupe_query($query);
		}
	}


	// Kuinka pitkälle ollaan jo generoitu tän skriptin toimesta lähtöjä per toimitustapa
	$query = "	SELECT liitostunnus, varasto, max(pvm) maxpvm
				FROM lahdot
				WHERE yhtio = '{$kukarow['yhtio']}'
				GROUP BY 1,2";
	$chk_res = pupe_query($query);

	$max_pvm_array = array();

	while ($chk_row = mysql_fetch_assoc($chk_res)) {
		$max_pvm_array[$chk_row["liitostunnus"]][$chk_row["varasto"]] = (int) str_replace("-", "", $chk_row["maxpvm"]);
	}

	for ($i = 0; $i <= $paivia_eteenpain; $i++) {

		$time = mktime(0, 0, 0, date("m"), date("d") + $i, date("Y"));

		// Päivämäärä
		$pvm = date("Y-m-d", $time);

		// Päivämäärä numeerinen
		$pvm_int = date("Ymd", $time);

		// Viikonpäivä
		$aika_vkonpvm = date("w", $time);

		$query = "	SELECT *
					FROM toimitustavan_lahdot
					WHERE yhtio 		 = '{$kukarow['yhtio']}'
					AND lahdon_viikonpvm = '{$aika_vkonpvm}'
					AND aktiivi 		!= 'E'
					ORDER BY liitostunnus, varasto, asiakasluokka";
		$toimitustavan_lahdot_res = pupe_query($query);

		while ($t_row = mysql_fetch_assoc($toimitustavan_lahdot_res)) {

			$alkupvm = (int) str_replace("-", "", $t_row["alkupvm"]);

			// Lisätään vain jos päivälle ei oo aikaisemmin lisätty, tai jos alkupvm-muuttujalla halutaan lisätä $max_pvm_array-slotin sisälle uusia lähtöjä
			if (($pvm_int >= $alkupvm and $alkupvm > 0) or
				($alkupvm == 0 and (!isset($max_pvm_array[$t_row["liitostunnus"]][$t_row["varasto"]]) or
				(isset($max_pvm_array[$t_row["liitostunnus"]][$t_row["varasto"]]) and $pvm_int > $max_pvm_array[$t_row["liitostunnus"]][$t_row["varasto"]])))) {

				// Tehdään asiakasluokka-konversio
				$asiakasluokka = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$t_row['asiakasluokka']}'", "", "", "selitetark_3");

				if ((int) $asiakasluokka <= 0) {
					continue;
				}

				// Onko tälle päivälle jo generoitu tän skriptin toimesta lähtöjä
				$query = "	SELECT *
							FROM lahdot
							WHERE yhtio 			 = '{$kukarow['yhtio']}'
							AND pvm 				 = '{$pvm}'
							AND lahdon_viikonpvm 	 = '{$aika_vkonpvm}'
							AND lahdon_kellonaika 	 = '{$t_row['lahdon_kellonaika']}'
							AND viimeinen_tilausaika = '{$t_row['viimeinen_tilausaika']}'
							AND kerailyn_aloitusaika = '{$t_row['kerailyn_aloitusaika']}'
							AND terminaalialue 		 = '{$t_row['terminaalialue']}'
							AND asiakasluokka 		 = '{$asiakasluokka}'
							AND liitostunnus 		 = '{$t_row['liitostunnus']}'
							AND varasto 			 = '{$t_row['varasto']}'";
				$chk_res = pupe_query($query);

				if (mysql_num_rows($chk_res) == 0) {
					$query = "	INSERT INTO lahdot SET
								yhtio 				 = '{$kukarow['yhtio']}',
								pvm 				 = '{$pvm}',
								lahdon_viikonpvm 	 = '{$aika_vkonpvm}',
								lahdon_kellonaika 	 = '{$t_row['lahdon_kellonaika']}',
								viimeinen_tilausaika = '{$t_row['viimeinen_tilausaika']}',
								kerailyn_aloitusaika = '{$t_row['kerailyn_aloitusaika']}',
								terminaalialue 		 = '{$t_row['terminaalialue']}',
								asiakasluokka 		 = '{$asiakasluokka}',
								aktiivi 			 = '',
								liitostunnus 		 = '{$t_row['liitostunnus']}',
								varasto 			 = '{$t_row['varasto']}',
								laatija 			 = '{$kukarow['kuka']}',
								luontiaika 			 = now(),
								muutospvm 			 = now(),
								muuttaja 			 = '{$kukarow['kuka']}'";
					$ins_res = pupe_query($query);
				}
			}
		}
	}

	if (!$paivaajo) {
		// Nollataan väkisinkeräystäpät aina päivän päätteeksi
		$query = "	UPDATE lahdot
					SET vakisin_kerays = ''
					WHERE yhtio 		= '{$kukarow['yhtio']}'
					AND vakisin_kerays != ''";
		$upd_res = pupe_query($query);

		$query = "	UPDATE lasku
					SET vakisin_kerays = ''
					WHERE yhtio 		= '{$kukarow['yhtio']}'
					AND vakisin_kerays != ''
					AND tila 			= 'N'
					AND alatila 		= 'A'";
		$upd_res = pupe_query($query);
	}
