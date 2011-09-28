<?php

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	if (trim($argv[1]) == '') {
		echo "Et antanut yhti�t�!\n";
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
						alv = '{$trow['alv']}',
						rivihinta = 0,
						rivihinta_valuutassa = 0,
						{$query_insert_lisa}
						kate = 0,
						kommentti = '',
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

			// merkataan pantti k�ytetyksi
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