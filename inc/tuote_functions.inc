<?php

if (!function_exists('onko_tuotteella_oletuspaikka')) {
	/**
	 * Hakee tuotepaikat taulusta tuotenumerolla oletus = 'X' rivej?. Jos rivi l?ytyy niin t?ll?in tuotteelle on m??ritetty oletuspaikka.
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param string $tuoteno
	 * @return boolean
	 */
	function onko_tuotteella_oletuspaikka($tuoteno) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT oletus
					FROM tuotepaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND oletus = 'X'
					AND tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			return true;
		}

		return false;
	}
}

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