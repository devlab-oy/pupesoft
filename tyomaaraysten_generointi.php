<?php

$debug = true;
// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli' and !$debug) {
	die("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (trim($argv[1]) == '' and !$debug) {
	echo "Et antanut yhti�t�!\n";
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