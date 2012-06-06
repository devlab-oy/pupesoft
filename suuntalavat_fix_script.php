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

	echo "\n";

	$query = "	SELECT *
				FROM suuntalavat
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$suuntalavat_fetch_res = pupe_query($query);

	while ($suuntalavat_fetch_row = mysql_fetch_assoc($suuntalavat_fetch_res)) {

		$arr = strpos($suuntalavat_fetch_row['keikkatunnus'], ",") !== FALSE ? explode(",", $suuntalavat_fetch_row['keikkatunnus']) : array($suuntalavat_fetch_row['keikkatunnus']);

		foreach($arr as $saapuminen) {
			$query = "	INSERT INTO suuntalavat_saapuminen SET
						yhtio = '{$kukarow['yhtio']}',
						suuntalava = '{$suuntalavat_fetch_row['tunnus']}',
						saapuminen = '{$saapuminen}',
						laatija = 'cron',
						luontiaika = now(),
						muutospvm = now(),
						muuttaja = 'cron'";
			echo str_replace("\t", "", $query),"\n\n";
			// $insert_res = pupe_query($query);
		}
	}