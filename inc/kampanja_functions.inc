<?php

if (!function_exists('tarkista_osuuko_myyntitilaus_kampanjaan')) {

	/**
	 * Tarkistaa osuuko myyntitilauksen tilausrivit mihink‰‰n kampanjaan ja jos osuu niin lis‰‰ kampanjan palkitorivit myyntitilaukselle
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param int $tilausnumero
	 */
	function tarkista_osuuko_myyntitilaus_kampanjaan($tilausnumero) {
		global $kukarow, $yhtiorow;

		require('luo_myyntitilausotsikko.inc');
		$kampanjat = hae_kampanjat();

		if (empty($kampanjat)) {
			return false;
		}

		$lisattavat_tuotteet = array();

		foreach ($kampanjat as $kampanja) {
			foreach ($kampanja['kampanja_ehdot'] as $kampanja_ehto) {
				$onko_kampanja_ok = tarkista_kampanja_ehto($tilausnumero, $kampanja_ehto);

				if ($onko_kampanja_ok === false) {
					break 2;
				}
			}

			//loopataan kampanjan palkinnot l‰pi koska muuten tulee yksi ylim‰‰r‰inen array lisattavat_tuotteet muuttujaan
			foreach ($kampanja['kampanja_palkinnot'] as $kampanja_palkinto) {
				$lisattavat_tuotteet[] = $kampanja_palkinto;
			}
		}

		foreach ($lisattavat_tuotteet as $lisattava_tuote) {
			$parametrit = array(
				'trow' => hae_tuote($lisattava_tuote['tuoteno']),
				'laskurow' => hae_lasku($tilausnumero),
				'kpl' => $lisattava_tuote['kpl'],
				'tuoteno' => $lisattava_tuote['tuoteno'],
			);
			lisaa_rivi($parametrit);
		}
	}
}

if (!function_exists('tarkista_kampanja_ehto')) {

	function tarkista_kampanja_ehto($tilausnumero, $kampanja_ehto) {
		global $kukarow, $yhtiorow;

		//kampanjoiden ehtojen ja aliehtojen rajoittimet pit‰‰ konvertoida, jotta niit‰ voidaan k‰ytt‰‰ suoraan queryiss‰
		$kampanja_ehto['rajoitin'] = konvertoi_rajoitin($kampanja_ehto['rajoitin']);

		switch ($kampanja_ehto['kohde']) {
			case 'asiakas':
				$asiakas_ehto = " AND asiakas.tunnus {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				$ehto_rajaus = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus {$asiakas_ehto})";
				break;
			case 'asiakas_ytunnus':
				$asiakas_ehto = " AND asiakas.ytunnus {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				$ehto_rajaus = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus {$asiakas_ehto})";
				break;
			case 'asiakaskategoria':
				$asiakas_ehto = " AND asiakas.kategoria {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				$ehto_rajaus = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus {$asiakas_ehto})";
				break;
			case 'tuote':
				//jos tuote pit‰‰ loopata laskun tilausrivit l‰pi ja katsoa t‰sm‰‰kˆ mik‰‰n tuote
				if (!empty($kampanja_ehto['aliehdot'])) {
					$having_ehdot = "HAVING";
					foreach ($kampanja_ehto['aliehdot'] as $aliehto) {
						$aliehto['rajoitin'] = konvertoi_rajoitin($aliehto['rajoitin']);

						if ($aliehto['kohde'] == 'arvo') {
							$having_ehdot .= " arvo {$aliehto['rajoitin']} {$aliehto['arvo']}";
						}
						elseif ($aliehto['kohde'] == 'kappaleet') {
							$having_ehdot .= " kpl {$aliehto['rajoitin']} {$aliehto['arvo']}";
						}
						else {
							echo "Rikki meni";
							return false;
						}
						$having_ehdot .= " AND";
					}
					$having_ehdot = substr($having_ehdot, 0, -3);
				}

				$tuote_ehto .= " AND tilausrivi.tuoteno {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']}'";

				break;
			case 'tuotekategoria':
				$query = "	SELECT tuoteno
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$tilausnumero}'";
				$result = pupe_query($query);
				while($tilausrivi = mysql_fetch_assoc($result)) {
					$query = "	SELECT group_concat(parent.tunnus) tunnukset
								FROM puun_alkio
								JOIN dynaaminen_puu AS node ON (puun_alkio.yhtio = node.yhtio and puun_alkio.laji = node.laji and puun_alkio.puun_tunnus = node.tunnus)
								JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 0)
								WHERE puun_alkio.yhtio = '$kukarow[yhtio]'
								AND puun_alkio.laji    = 'Tuote'
								AND puun_alkio.liitos  = '{$tilausrivi['tuoteno']}'";
					$result2 = pupe_query($query);
					$puun_tunnukset = mysql_fetch_assoc($result2);
					$puun_tunnukset = explode(',', $puun_tunnukset['tunnukset']);
					if (in_array($kampanja_ehto['arvo'], $puun_tunnukset)) {
						//tilausrivin tuote on kampanjan ehdon tuotekategoriassa
						return true;
					}
				}
				return false;
				
				break;
			case 'tuoteosasto':
				$ehto_rajaus = "JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.osasto {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']})";
				break;
			case 'tuoteryhma':
				$ehto_rajaus = "JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.try {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']})";
				break;
			case 'kappaleet':
				$having_ehdot = "HAVING kpl {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				break;
			case 'arvo':
				$having_ehdot = "HAVING arvo {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				break;
			default:
				break;
		}

		$query = "	SELECT lasku.tunnus,
					sum(tilausrivi.varattu+tilausrivi.jt) kpl,
					sum(tilausrivi.hinta) arvo
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus {$tuote_ehto})
					{$ehto_rajaus}
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus = {$tilausnumero}
					AND lasku.tila = 'N'
					#AND lasku.alatila = ''
					GROUP BY lasku.tunnus
					{$having_ehdot}";

		$result = pupe_query($query);

		$row = mysql_fetch_assoc($result);

		if ($row['tunnus'] == NULL) {
			return false;
		}

		return true;
	}
}

if (!function_exists('hae_kampanjat')) {

	/**
	 * 	Hakee kaikki kampanjat ja niiden ehdot, aliehdot sek‰ palkintorivit
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @return array $kampanjat
	 */
	function hae_kampanjat() {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM kampanja
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		$kampanjat = array();
		while ($kampanja = mysql_fetch_assoc($result)) {
			$kampanja['kampanja_ehdot'] = hae_kampanjan_ehdot($kampanja['tunnus']);
			$kampanja['kampanja_palkinnot'] = hae_kampanjan_palkinnot($kampanja['tunnus']);
			$kampanjat[] = $kampanja;
		}

		return $kampanjat;
	}
}

if (!function_exists('hae_kampanjan_ehdot')) {

	/**
	 * Hakee kampanjan ehdot sek‰ aliehdot
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param int $kampanja_tunnus
	 * @return array $kampanja_ehdot
	 */
	function hae_kampanjan_ehdot($kampanja_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM kampanja_ehto
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kampanja = '{$kampanja_tunnus}'";
		$result = pupe_query($query);

		$kampanja_ehdot = array();
		while ($kampanja_ehto = mysql_fetch_assoc($result)) {
			$kampanja_ehto['aliehdot'] = hae_kampanja_ehdon_aliehdot($kampanja_ehto['tunnus']);
			$kampanja_ehdot[] = $kampanja_ehto;
		}

		return $kampanja_ehdot;
	}
}


if (!function_exists('hae_kampanja_ehdon_aliehdot')) {

	/**
	 * Hakee kampanjan ehdon aliehdot
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param int $kampanja_ehto_tunnus
	 * @return array $kampanja_aliehdot
	 */
	function hae_kampanja_ehdon_aliehdot($kampanja_ehto_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM kampanja_ehto
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND isatunnus = '{$kampanja_ehto_tunnus}'";
		$result = pupe_query($query);

		$kampanja_aliehdot = array();
		while ($kampanja_aliehto = mysql_fetch_assoc($result)) {
			$kampanja_aliehdot[] = $kampanja_aliehto;
		}

		return $kampanja_aliehdot;
	}
}


if (!function_exists('hae_kampanjan_palkinnot')) {

	/**
	 * Hakee kampanjan palkintorivit
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param int $kampanja_tunnus
	 * @return array $kampanja_palkinnot
	 */
	function hae_kampanjan_palkinnot($kampanja_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM kampanja_palkinto
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kampanja = '{$kampanja_tunnus}'";
		$result = pupe_query($query);

		$kampanja_palkinnot = array();
		while ($kampanja_palkinto = mysql_fetch_assoc($result)) {
			$kampanja_palkinnot[] = $kampanja_palkinto;
		}

		return $kampanja_palkinnot;
	}
}

if (!function_exists('konvertoi_rajoitin')) {

	function konvertoi_rajoitin($rajoitin) {
		$return_rajoitin = "";
		switch ($rajoitin) {
			case 'on':
				$return_rajoitin = "=";
				break;
			case 'ei_ole':
				$return_rajoitin = "!=";
				break;
			case 'suurempi_kuin':
				$return_rajoitin = ">";
				break;
			case 'pienempi_kuin':
				$return_rajoitin = "<";
				break;
			default:
				$return_rajoitin = "=";
				break;
		}

		return $return_rajoitin;
	}
}