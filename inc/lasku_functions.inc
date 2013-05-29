<?php

if (!function_exists('hae_lasku')) {

	function hae_lasku($tilausnumero) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tilausnumero}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}

if (!function_exists('hae_laskun_tilausrivit')) {
	function hae_laskun_tilausrivit($tilausnumero) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM tilausrivi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND otunnus = '{$tilausnumero}'
					AND tyyppi != 'D'";
		$result = pupe_query($query);

		$tilausrivit = array();
		while($tilausrivi = mysql_fetch_assoc($result)) {
			$tilausrivit[] = $tilausrivi;
		}

		return $tilausrivit;
	}
}

if (!function_exists('poista_lasku')) {
	function poista_lasku($tilausnumero) {
		global $kukarow, $yhtiorow;

		$query = "	UPDATE lasku
					SET tila = 'D'
					WHERE yhtio = 'artr'
					AND tunnus = '{$tilausnumero}'";
		pupe_query($query);

		$query = "	UPDATE tilausrivi
					SET tyyppi = 'D'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND otunnus = '{$tilausnumero}'";
		pupe_query($query);
	}
}