<?php

if (!function_exists('hae_paikka')) {
	function hae_paikka($paikka_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM paikka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$paikka_tunnus}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}