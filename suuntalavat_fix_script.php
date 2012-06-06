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

		$query = "	INSERT INTO suuntalavat_saapuminen SET
					yhtio = '{$kukarow['yhtio']}',
					suuntalava = '{$suuntalavat_fetch_row['tunnus']}',
					saapuminen = '{$suuntalavat_fetch_row['keikkatunnus']}',
					laatija = 'cron',
					luontiaika = now(),
					muutospvm = now(),
					muuttaja = 'cron'";
		echo str_replace("\t", "", $query),"\n\n";

	}