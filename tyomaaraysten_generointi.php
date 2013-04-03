<?php

$debug = true;
// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli' and !$debug) {
	die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (trim($argv[1]) == '' and !$debug) {
	echo "Et antanut yhtiötä!\n";
	exit;
}
else {
	$yhtio = "lpk";
}

require ("inc/connect.inc");
require ("inc/functions.inc");

hae_laitteet();

function hae_laitteet($request = array()) {
	global $kukarow;

	$query = "	SELECT *
				FROM laite
				WHERE laite.yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$laitteet = array();
	while($laite = mysql_fetch_assoc($result)) {
		$laitteet[] = $laite;
	}

	return $laite;
}