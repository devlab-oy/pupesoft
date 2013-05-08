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