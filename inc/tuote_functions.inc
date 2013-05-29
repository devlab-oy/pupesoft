<?php

if (!function_exists('hae_tuote')) {

	/**
	 * Hakee tuotteen
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param string $tuoteno
	 * @return array
	 */
	function hae_tuote($tuoteno) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}
