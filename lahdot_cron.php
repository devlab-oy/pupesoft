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

	$kukarow['yhtio'] = (string) $argv[1];
	$kukarow['kuka'] = 'cron';
	$kukarow['kieli'] = 'fi';

	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	// $pvmnimet[1] = t("Maanantai");
	// $pvmnimet[2] = t("Tiistai");
	// $pvmnimet[3] = t("Keskiviikko");
	// $pvmnimet[4] = t("Torstai");
	// $pvmnimet[5] = t("Perjantai");
	// $pvmnimet[6] = t("Lauantai");
	// $pvmnimet[0] = t("Sunnuntai");

	$paivia_eteenpain = 14;

	for ($i = 0; $i <= $paivia_eteenpain; $i++) {

		$time = mktime(0, 0, 0, date("m"), date("d") + $i, date("Y"));

		$pvm = date("Y-m-d", $time);

		// haetaan ajan viikonpäivä
		$aika_vkonpvm = date("w", $time);

		$query = "SELECT * FROM toimitustavan_lahdot WHERE yhtio = '{$kukarow['yhtio']}' AND lahdon_viikonpvm = '{$aika_vkonpvm}' ORDER BY liitostunnus, asiakasluokka";
		$toimitustavan_lahdot_res = pupe_query($query);

		while ($t_row = mysql_fetch_assoc($toimitustavan_lahdot_res)) {

			$asiakasluokka = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$t_row['asiakasluokka']}'", "", "", "selitetark_3");

			$query = "	SELECT * 
						FROM lahdot 
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND pvm = '{$pvm}'
						AND lahdon_viikonpvm = '{$aika_vkonpvm}'
						AND lahdon_kellonaika = '{$t_row['lahdon_kellonaika']}'
						AND viimeinen_tilausaika = '{$t_row['viimeinen_tilausaika']}'
						AND kerailyn_aloitusaika = '{$t_row['kerailyn_aloitusaika']}'
						AND terminaalialue = '{$t_row['terminaalialue']}'
						AND asiakasluokka = '{$asiakasluokka}'
						AND liitostunnus = '{$t_row['liitostunnus']}'
						AND varasto = '{$t_row['varasto']}'";
			$chk_res = pupe_query($query);

			if (mysql_num_rows($chk_res) == 0) {

				$query = "	INSERT INTO lahdot SET
							yhtio = '{$kukarow['yhtio']}',
							pvm = '{$pvm}',
							lahdon_viikonpvm = '{$aika_vkonpvm}',
							lahdon_kellonaika = '{$t_row['lahdon_kellonaika']}',
							viimeinen_tilausaika = '{$t_row['viimeinen_tilausaika']}',
							kerailyn_aloitusaika = '{$t_row['kerailyn_aloitusaika']}',
							terminaalialue = '{$t_row['terminaalialue']}',
							asiakasluokka = '{$asiakasluokka}',
							aktiivi = '',
							liitostunnus = '{$t_row['liitostunnus']}',
							varasto = '{$t_row['varasto']}',
							laatija = '{$kukarow['kuka']}',
							luontiaika = now(),
							muutospvm = now(),
							muuttaja = '{$kukarow['kuka']}'";
				$ins_res = pupe_query($query);
				// echo str_replace("\t", "", str_replace(array("\r\n", "\n"), " ", $query))."\n";
			}
		}

	}

	$query = "UPDATE lahdot SET vakisin_kerays = '' WHERE yhtio = '{$kukarow['yhtio']}' AND vakisin_kerays != ''";
	$upd_res = pupe_query($query);

	$query = "	UPDATE lasku SET 
				vakisin_kerays = '' 
				WHERE yhtio = '{$kukarow['yhtio']}' 
				AND vakisin_kerays != ''
				AND lasku.tila = 'N' 
				AND lasku.alatila = 'A'";
	$upd_res = pupe_query($query);