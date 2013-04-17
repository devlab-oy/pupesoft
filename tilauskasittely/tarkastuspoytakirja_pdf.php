<?php

require("../inc/parametrit.inc");

$lasku_tunnus = 234;
$tyomaarays = hae_tyomaarays($lasku_tunnus);

$filepath = kirjoita_json_tiedosto($tyomaarays);

aja_ruby($filepath);

function hae_tyomaarays($lasku_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM lasku
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tunnus = '{$lasku_tunnus}'";
	$result = pupe_query($query);

	$tyomaarays = mysql_fetch_assoc($result);
	$tyomaarays['rivit'] = hae_tyomaarayksen_rivit($lasku_tunnus);
	$laite_tunnus = $tyomaarays['rivit'][0]['laite']['tunnus'];
	$tyomaarays['kohde'] = hae_tyomaarayksen_kohde($laite_tunnus);
	$tyomaarays['asiakas'] = hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
	$tyomaarays['yhtio'] = $yhtiorow;

	return $tyomaarays;
}

function hae_tyomaarayksen_rivit($lasku_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$lasku_tunnus}'";
	$result = pupe_query($query);

	$rivit = array();
	while ($rivi = mysql_fetch_assoc($result)) {
		$rivi['laite'] = hae_rivin_laite($rivi['asiakkaan_positio']);
		$rivit[] = $rivi;
	}

	return $rivit;
}

function hae_rivin_laite($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM laite
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$laite_tunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_tyomaarayksen_asiakas($asiakas_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$asiakas_tunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function kirjoita_json_tiedosto($tyomaarays) {
	$filename = "tyomaarays_{$tyomaarays['tunnus']}.json";
	$filepath = "/tmp/{$filename}";

	array_walk_recursive($tyomaarays, 'array_utf8_encode');

	file_put_contents($filepath, json_encode($tyomaarays));

	return $filepath;
}

function hae_tyomaarayksen_kohde($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT kohde.*
				FROM kohde
				JOIN paikka
				ON ( paikka.yhtio = kohde.yhtio
					AND paikka.kohde = kohde.tunnus )
				JOIN laite
				ON ( laite.yhtio = kohde.yhtio
					AND laite.paikka = paikka.tunnus
					AND laite.tunnus = '{$laite_tunnus}')
				WHERE kohde.yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function array_utf8_encode(&$item, $key) {
	$item = utf8_encode($item);
}

function aja_ruby($filepath) {
	//@TODO pupe_root kö
	$lol = system("ruby /Users/joonas/Dropbox/Sites/pupesoft/pdfs/ruby/tarkastuspoytakirja_pdf.rb {$filepath}");
	echo "<html>";
	echo $lol;
	echo "<pre>";
	echo $lol;
	echo "</pre>";
	echo "</html>";
}

?>
