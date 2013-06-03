<?php

if (!function_exists('onko_tuotteella_oletuspaikka')) {
	/**
	 * Hakee tuotepaikat taulusta tuotenumerolla oletus = 'X' rivejä. Jos rivi löytyy niin tällöin tuotteelle on määritetty oletuspaikka.
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