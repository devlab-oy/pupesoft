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
					continue 2;
				}
			}

			//loopataan kampanjan palkinnot l‰pi koska muuten tulee yksi ylim‰‰r‰inen array lisattavat_tuotteet muuttujaan
			foreach ($kampanja['kampanja_palkinnot'] as $kampanja_palkinto) {
				$lisattavat_tuotteet[] = $kampanja_palkinto;
			}
		}

		foreach ($lisattavat_tuotteet as $lisattava_tuote) {
			$haettu_tuote = hae_tuote($lisattava_tuote['tuoteno']);
			$parametrit = array(
				'trow'		 => $haettu_tuote,
				'laskurow'	 => hae_lasku($tilausnumero),
				'kpl'		 => $lisattava_tuote['kpl'],
				'hinta'		 => $haettu_tuote['myyntihinta'],
				'ale1'		 => 100,
				'tuoteno'	 => $lisattava_tuote['tuoteno'],
			);
			lisaa_rivi($parametrit);
		}
	}
}

if (!function_exists('tarkista_kampanja_ehto')) {

	/**
	 * Tarkistaa osuuko tilaus kampanjan yhteen ehtoon
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param int $tilausnumero
	 * @param array $kampanja_ehto
	 * @return boolean
	 */
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
				$asiakas_ehto = " AND asiakas.ytunnus {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']}'";
				$ehto_rajaus = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus {$asiakas_ehto})";
				break;

			case 'asiakaskategoria':
				$query = "	SELECT liitostunnus
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$tilausnumero}'";
				$result = pupe_query($query);

				while ($lasku = mysql_fetch_assoc($result)) {

					$query = "	SELECT group_concat(parent.tunnus) tunnukset
								FROM puun_alkio
								JOIN dynaaminen_puu AS node ON (puun_alkio.yhtio = node.yhtio and puun_alkio.laji = node.laji and puun_alkio.puun_tunnus = node.tunnus)
								JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 0)
								WHERE puun_alkio.yhtio = '$kukarow[yhtio]'
								AND puun_alkio.laji    = 'ASIAKAS'
								AND puun_alkio.liitos  = '{$lasku['liitostunnus']}'";
					$result2 = pupe_query($query);

					$puun_tunnukset = mysql_fetch_assoc($result2);
					$puun_tunnukset = explode(',', $puun_tunnukset['tunnukset']);

					if (in_array($kampanja_ehto['arvo'], $puun_tunnukset)) {
						//tilauksen asiakas on kampanjan ehdon asiakaskategoriassa
						return true;
					}
				}
				return false;
				break;

			case 'tuote':
				$having_ehdot = populoi_aliehto_having($kampanja_ehto['aliehdot']);
				$tuote_ehto .= " AND tilausrivi.tuoteno {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']}'";
				break;

			case 'tuotekategoria':

				$query = "	SELECT tuoteno
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$tilausnumero}'";
				$result = pupe_query($query);

				while ($tilausrivi = mysql_fetch_assoc($result)) {

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
						//tarkistetaan osuuko aliehdot
						if (!empty($kampanja_ehto['aliehdot'])) {
							$having_ehdot = populoi_aliehto_having($kampanja_ehto['aliehdot']);
							$query = "	SELECT lasku.tunnus,
										sum(tilausrivi.varattu + tilausrivi.jt) kpl,
										sum(tilausrivi.hinta) arvo
										FROM lasku
										JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus )
										WHERE lasku.yhtio = '{$kukarow['yhtio']}'
										AND lasku.tunnus = {$tilausnumero}
										AND lasku.tila = 'N'
										AND lasku.alatila = ''
										GROUP BY lasku.tunnus
										{$having_ehdot}";
							$result = pupe_query($query);
							$row = mysql_fetch_assoc($result);

							if ($row['tunnus'] == NULL) {
								return false;
							}
						}
						return true;
					}
				}
				return false;

				break;

			case 'tuoteosasto':
				$having_ehdot = populoi_aliehto_having($kampanja_ehto['aliehdot']);
				$ehto_rajaus = "JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.osasto {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']}')";
				break;

			case 'tuoteryhma':
				$having_ehdot = populoi_aliehto_having($kampanja_ehto['aliehdot']);
				$ehto_rajaus = "JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.try {$kampanja_ehto['rajoitin']} '{$kampanja_ehto['arvo']}')";
				break;

			case 'kappaleet':
				$having_ehdot = "HAVING kpl {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				break;

			case 'arvo':
				$having_ehdot = "HAVING arvo {$kampanja_ehto['rajoitin']} {$kampanja_ehto['arvo']}";
				break;

			default:
				echo "Rikki meni";
				return false;
				break;
		}

		$query = "	SELECT lasku.tunnus,
					sum(tilausrivi.varattu + tilausrivi.jt) AS kpl,
					sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt)) AS arvo
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus {$tuote_ehto})
					{$ehto_rajaus}
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus = {$tilausnumero}
					AND lasku.tila = 'N'
					AND lasku.alatila = ''
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

if (!function_exists('populoi_aliehto_having')) {

	/**
	 * Palauttaa tilauksen mappaamisessa kampanjoihin k‰ytett‰v‰n aliehto HAVING:in
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param array $aliehdot
	 * @return string
	 */
	function populoi_aliehto_having($aliehdot) {
		global $kukarow, $yhtiorow;

		$having_ehdot = "";
		if (!empty($aliehdot)) {
			$having_ehdot = "HAVING";

			foreach ($aliehdot as $aliehto) {
				$aliehto['rajoitin'] = konvertoi_rajoitin($aliehto['rajoitin']);

				if ($aliehto['kohde'] == 'arvo') {
					$having_ehdot .= " arvo {$aliehto['rajoitin']} {$aliehto['arvo']}";
				}
				elseif ($aliehto['kohde'] == 'kappaleet') {
					$having_ehdot .= " kpl {$aliehto['rajoitin']} {$aliehto['arvo']}";
				}
				else {
					echo "Rikki meni";
					return '';
				}

				$having_ehdot .= " AND";
			}
			$having_ehdot = substr($having_ehdot, 0, -3);
		}

		return $having_ehdot;
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
					FROM kampanjat
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
	 * Hakee kampanjan ehdot, aliehdot sek‰ ehtojen kohde resurssit
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param int $kampanja_tunnus
	 * @return array $kampanja_ehdot
	 */
	function hae_kampanjan_ehdot($kampanja_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM kampanja_ehdot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kampanja = '{$kampanja_tunnus}'";
		$result = pupe_query($query);

		$kampanja_ehdot = array();

		while ($kampanja_ehto = mysql_fetch_assoc($result)) {
			$kampanja_ehto['resurssi'] = hae_ehdon_tai_aliehdon_kohteen_resurssi($kampanja_ehto);
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
					FROM kampanja_ehdot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND isatunnus = '{$kampanja_ehto_tunnus}'";
		$result = pupe_query($query);

		$kampanja_aliehdot = array();

		while ($kampanja_aliehto = mysql_fetch_assoc($result)) {
			$kampanja_aliehto['resurssi'] = hae_ehdon_tai_aliehdon_kohteen_resurssi($kampanja_aliehto);
			$kampanja_aliehdot[] = $kampanja_aliehto;
		}

		return $kampanja_aliehdot;
	}
}

if (!function_exists('hae_ehdon_tai_aliehdon_kohteen_resurssi')) {

	/**
	 * Hakee ehdon tai aliehdon kohteen resurssin. Eli jos kohde on tuote niin funktio palauttaa kohteen arvon m‰‰r‰‰m‰n tuotteen.
	 * Resurssia k‰ytet‰‰n kampanjan muokkaus n‰kym‰ss‰. Inputin yl‰puolella selkokielinen teksti.
	 *
	 * @global array $kukarow
	 * @global array $yhtiorow
	 * @param array $ehto
	 * @return array
	 */
	function hae_ehdon_tai_aliehdon_kohteen_resurssi($ehto) {
		global $kukarow, $yhtiorow;

		//Jokaiselle resurssille annetaan info sarake, jotta kampanjan muokkaus n‰kym‰ss‰ voidaan n‰ytt‰‰ inputin yl‰puolella selkokielisen‰ valittu arvo
		switch ($ehto['kohde']) {
			case 'asiakas':
				$query = "	SELECT asiakas.*,
							concat_ws(' - ', asiakas.nimi, asiakas.ytunnus, asiakas.asiakasnro) as info
							FROM asiakas
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$ehto['arvo']}'";
				break;
			case 'asiakas_ytunnus':
				$query = "	SELECT asiakas.*,
							concat_ws(' - ', asiakas.nimi, asiakas.ytunnus, asiakas.asiakasnro) as info
							FROM asiakas
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND ytunnus = '{$ehto['arvo']}'";
				break;
			case 'asiakaskategoria':
				$query = "	SELECT dynaaminen_puu.*,
							dynaaminen_puu.nimi as info
							FROM dynaaminen_puu
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = 'Asiakas'
							AND tunnus = '{$ehto['arvo']}'";
				break;
			case 'tuote':
				$query = "	SELECT tuote.*,
							tuote.nimitys as info
							FROM tuote
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tuoteno = '{$ehto['arvo']}'";
				break;
			case 'tuotekategoria':
				$query = "	SELECT dynaaminen_puu.*,
							dynaaminen_puu.nimi as info
							FROM dynaaminen_puu
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = 'Tuote'
							AND tunnus = '{$ehto['arvo']}'";
				break;
			case 'tuoteosasto':
				$query = "	SELECT avainsana.*,
							avainsana.selitetark as info
							FROM avainsana
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = 'OSASTO'
							AND selite = '{$ehto['arvo']}'";
				break;
			case 'tuoteryhma':
				$query = "	SELECT avainsana.*,
							avainsana.selitetark as info
							FROM avainsana
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = 'TRY'
							AND selite = '{$ehto['arvo']}'";
				break;
			case 'kappaleet':
				return false;
				break;
			case 'arvo':
				return false;
				break;
			default:
				return false;
				break;
		}

		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
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
					FROM kampanja_palkinnot
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
		switch ($rajoitin) {
			case 'on':
				return "=";
			case 'ei_ole':
				return "!=";
			case 'suurempi_kuin':
				return ">";
			case 'pienempi_kuin':
				return "<";
			default:
				return "=";
		}
	}
}
