<?php

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	if (!isset($argv[1]) or $argv[1] == '') {
		echo "Anna yhti�!!!\n";
		die;
	}

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// hmm.. j�nn��
	$kukarow['yhtio'] = $argv[1];
	$kieli = $argv[2];
	$kukarow['kuka']  = "crond";

	$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($yhtiores) == 1) {
		$yhtiorow = mysql_fetch_array($yhtiores);

		// haetaan yhti�n parametrit
		$query = "	SELECT *
					FROM yhtion_parametrit
					WHERE yhtio='$yhtiorow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_parametritrow = mysql_fetch_array($result);
			// lis�t��n kaikki yhtiorow arrayseen, niin ollaan taaksep�inyhteensopivia
			foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
				$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
			}
		}
	}
	else {
		die ("Yhti� $kukarow[yhtio] ei l�ydy!");
	}

	// ajetaan eka l�pi niin saadaan laskuttamattomat sopparit muuttujiin
	require ("yllapitosopimukset.php");

	$laskutapvm = $cron_pvm;
	$laskutatun = $cron_tun;
	$tee        = "laskuta";

	// sitte ajetaan uudestaan laskuta modessa kaikki sopparit l�pi
	require ("yllapitosopimukset.php");

	// echotaan outputti
	$laskuta_message = str_replace("<br>", "\n", $laskuta_message);
	echo strip_tags($laskuta_message);

?>