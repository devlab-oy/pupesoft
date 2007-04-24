<?php

if (count($argv) != 2) die ("anna parametriksi yhtiö\n");

if (isset($argv[1]) and trim($argv[1]) != '') {

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// hmm.. jännää
	$kukarow['yhtio'] = $argv[1];
	$kieli = $argv[2];
	$kukarow['kuka']  = "crond";

	$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($yhtiores) == 1) {
		$yhtiorow = mysql_fetch_array($yhtiores);

		// haetaan yhtiön parametrit
		$query = "	SELECT *
					FROM yhtion_parametrit
					WHERE yhtio='$yhtiorow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_parametritrow = mysql_fetch_array($result);
			// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
			foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
				$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
			}
		}
	}
	else {
		die ("Yhtiö $kukarow[yhtio] ei löydy!");
	}

	// ajetaan eka läpi niin saadaan laskuttamattomat sopparit muuttujiin
	require ("yllapitosopimukset.php");

	$laskutapvm = $cron_pvm;
	$laskutatun = $cron_tun;
	$tee        = "laskuta";

	// sitte ajetaan uudestaan laskuta modessa kaikki sopparit läpi
	require ("yllapitosopimukset.php");

	// echotaan outputti
	$laskuta_message = str_replace("<br>", "\n", $laskuta_message);
	echo strip_tags($laskuta_message);

}