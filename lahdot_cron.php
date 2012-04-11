<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (trim($argv[1]) == '') {
		echo "Et antanut yhtiötä!\n";
		exit;
	}

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

	/*
	// Poistetaan aktiiviset, vanhentuneet lähdöt joihin ei oo liitetty yhtään tilausta
	$query = "	SELECT lahdot.tunnus
				FROM lahdot
				LEFT JOIN lasku ON (lasku.yhtio = lahdot.yhtio AND lasku.toimitustavan_lahto = lahdot.tunnus AND lasku.tila  IN ('N','L'))
				WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
				AND lahdot.aktiivi = ''
				AND lahdot.pvm < CURDATE()
				AND lasku.tunnus IS NULL";
	$chk_res = pupe_query($query);

	while ($chk_row = mysql_fetch_assoc($chk_res)) {
		$query = "	DELETE FROM lahdot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$chk_row['tunnus']}'";
		$del_res = pupe_query($query);
	}
	*/

	// Kuinka pitkälle ollaan jo generoitu tän skriptin toimesta lähtöjä per toimitustapa
	$query = "	SELECT liitostunnus, max(pvm) maxpvm
				FROM lahdot
				WHERE yhtio = '{$kukarow['yhtio']}'
				GROUP BY 1";
	$chk_res = pupe_query($query);

	$max_pvm_array = array();

	while ($chk_row = mysql_fetch_assoc($chk_res)) {
		$max_pvm_array[$chk_row["liitostunnus"]] = (int) str_replace("-", "", $chk_row["maxpvm"]);
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
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND lahdon_viikonpvm = '{$aika_vkonpvm}'
					ORDER BY liitostunnus, asiakasluokka";
		$toimitustavan_lahdot_res = pupe_query($query);

		while ($t_row = mysql_fetch_assoc($toimitustavan_lahdot_res)) {

			// Lisätään vain jos päivälle ei oo aikaisemmin lisätty
			if (!isset($max_pvm_array[$t_row["liitostunnus"]]) or (isset($max_pvm_array[$t_row["liitostunnus"]]) and $pvm_int > $max_pvm_array[$t_row["liitostunnus"]])) {

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