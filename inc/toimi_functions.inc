<?php

if (!function_exists('hae_toimittajat')) {
	/**
	 * Hakee kaikki toimittajat tyypin mukaan
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param array $tyyppi
	 * @return array
	 */
	function hae_toimittajat($tyyppi = array()) {
		global $kukarow, $yhtiorow;

		if (!empty($tyyppi)) {
			$toimi_tyyppi_where = "AND tyyppi IN ('".implode("','", $tyyppi)."')";
		}
		$query = "	SELECT *
					FROM toimi
					WHERE yhtio = '{$kukarow['yhtio']}'
					{$toimi_tyyppi_where}";
		$result = pupe_query($query);

		$toimittajat = array();
		while($toimittaja = mysql_fetch_assoc($result)) {
			$toimittajat[] = $toimittaja;
		}

		return $toimittajat;
	}
}

if (!function_exists('hae_toimittaja')) {
	function hae_toimittaja($tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM toimi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tunnus}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}