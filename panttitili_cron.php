<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (trim($argv[1]) == '') {
		echo "Et antanut yhtiötä!\n";
		exit;
	}

	require ("inc/salasanat.php");
	require ("inc/connect.inc");
	require ("inc/functions.inc");
	require ("tilauskasittely/luo_myyntitilausotsikko.inc");

	$kukarow['yhtio'] = (string) $argv[1];
	$kukarow['kuka'] = 'cron';

	$query = "	SELECT asiakas, GROUP_CONCAT(tunnus) tunnukset
				FROM panttitili
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND status = ''
				AND myyntipvm <= DATE_SUB(now(), INTERVAL 6 MONTH)
				GROUP BY asiakas";
	$panttitili_res = pupe_query($query);

	while ($panttitili_row = mysql_fetch_assoc($panttitili_res)) {

		$tilausnumero = luo_myyntitilausotsikko('RIVISYOTTO', $panttitili_row['asiakas']);

		$query = "	SELECT tuoteno, hinta, alv, erikoisale, SUM(kpl) AS kpl
					FROM panttitili
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus IN ({$panttitili_row['tunnukset']})
					GROUP BY 1,2,3,4
					ORDER BY myyntipvm ASC";
		$pantti_res = pupe_query($query);

		while ($pantti_row = mysql_fetch_assoc($pantti_res)) {

			$query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$pantti_row['tuoteno']}'";
			$tres = pupe_query($query);
			$trow = mysql_fetch_assoc($tres);

			$query_insert_lisa = '';

			// nollataan ale2 ja ale3 kentät ja laitetaan INSERT ale1 100%
			for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$query_insert_lisa .= " ale{$postfix} = 0, ";
			}

			$pantti_row['kpl'] *= -1;

			$query = "	INSERT INTO tilausrivi SET
						yhtio = '{$kukarow['yhtio']}',
						tyyppi = 'L',
						toimaika = now(),
						kerayspvm = now(),
						otunnus = '{$tilausnumero}',
						tuoteno = '{$pantti_row['tuoteno']}',
						try = '{$trow['try']}',
						osasto = '{$trow['osasto']}',
						nimitys = '{$trow['nimitys']}',
						kpl = 0,
						kpl2 = 0,
						tilkpl = '{$pantti_row['kpl']}',
						varattu = '{$pantti_row['kpl']}',
						yksikko = '{$trow['yksikko']}',
						jt = 0,
						hinta = '{$pantti_row['hinta']}',
						hinta_valuutassa = 0,
						alv = '{$pantti_row['alv']}',
						rivihinta = 0,
						rivihinta_valuutassa = 0,
						erikoisale = '{$pantti_row['erikoisale']}',
						ale1 = 100,
						{$query_insert_lisa}
						kate = 0,
						kommentti = '".("Panttituotteen hyvitys palauttamattomista panteista")."',
						laatija = 'cron',
						laadittu = now(),
						keratty = 'cron',
						kerattyaika = now(),
						toimitettu = 'cron',
						toimitettuaika = now(),
						laskutettu = '',
						laskutettuaika = '0000-00-00',
						var = '',
						var2 = 'PANT',
						netto = '',
						perheid = 0,
						perheid2 = 0,
						hyllyalue = '',
						hyllynro = '',
						hyllytaso = '',
						hyllyvali = '',
						suuntalava = 0,
						tilaajanrivinro = 0,
						jaksotettu = 0,
						uusiotunnus = 0";
			$insert_res = pupe_query($query);
		}

		$query = "	SELECT *
					FROM panttitili
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus IN ({$panttitili_row['tunnukset']})
					ORDER BY myyntipvm ASC";
		$pantti_res = pupe_query($query);

		while ($pantti_row = mysql_fetch_assoc($pantti_res)) {

			$query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$pantti_row['tuoteno']}'";
			$tres = pupe_query($query);
			$trow = mysql_fetch_assoc($tres);

			$query_insert_lisa = '';

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$query_insert_lisa .= " ale{$postfix} = '{$pantti_row['ale{$postfix}']}', ";
			}

			$query = "	INSERT INTO tilausrivi SET
						yhtio = '{$kukarow['yhtio']}',
						tyyppi = 'L',
						toimaika = now(),
						kerayspvm = now(),
						otunnus = '{$tilausnumero}',
						tuoteno = '{$pantti_row['tuoteno']}',
						try = '{$trow['try']}',
						osasto = '{$trow['osasto']}',
						nimitys = '{$trow['nimitys']}',
						kpl = 0,
						kpl2 = 0,
						tilkpl = '{$pantti_row['kpl']}',
						varattu = '{$pantti_row['kpl']}',
						yksikko = '{$trow['yksikko']}',
						jt = 0,
						hinta = '{$pantti_row['hinta']}',
						hinta_valuutassa = 0,
						alv = '{$pantti_row['alv']}',
						rivihinta = 0,
						rivihinta_valuutassa = 0,
						erikoisale = '{$pantti_row['erikoisale']}',
						{$query_insert_lisa}
						kate = 0,
						kommentti = '".("Panttituotteen veloitus palauttamattomista panteista")."',
						laatija = 'cron',
						laadittu = now(),
						keratty = 'cron',
						kerattyaika = now(),
						toimitettu = 'cron',
						toimitettuaika = now(),
						laskutettu = '',
						laskutettuaika = '0000-00-00',
						var = '',
						var2 = 'PANT',
						netto = '',
						perheid = 0,
						perheid2 = 0,
						hyllyalue = '',
						hyllynro = '',
						hyllytaso = '',
						hyllyvali = '',
						suuntalava = 0,
						tilaajanrivinro = 0,
						jaksotettu = 0,
						uusiotunnus = 0";
			$insert_res = pupe_query($query);

			// merkataan pantti käytetyksi
			$query = "	UPDATE panttitili SET
						status = 'X',
						kaytettypvm = now(),
						kaytettytilausnro = '{$tilausnumero}'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$pantti_row['tunnus']}'";
			$upd_res = pupe_query($query);
		}

		$query = "	UPDATE lasku SET
					tila = 'L',
					alatila = 'D'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tilausnumero}'";
		$upd_res = pupe_query($query);
	}
