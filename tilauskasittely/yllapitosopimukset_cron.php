<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (!isset($argv[1]) or $argv[1] == '') {
		echo "Anna yhtiö!!!\n";
		die;
	}

	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// hmm.. jännää
	$kukarow['yhtio'] 		= $argv[1];
	$kieli 					= $argv[2];
	$kukarow['kuka']  		= "crond";
	$kukarow["kirjoitin"] 	= "";

	// Haetaan yhtion tiedot (virhetsekki funktiossa....)
	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	$cron_pvm = array();
	$cron_tun = array();

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
