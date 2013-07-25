<?php

require_once('tilauskasittely/luo_myyntitilausotsikko.inc');

$query = "  SELECT laskun_lisatiedot.sopimus_lisatietoja,
			laskun_lisatiedot.saate,
			lasku.olmapvm,
			lasku.clearing,
			tilausrivi.*,
			lasku.varasto,
			lasku.tilaustyyppi
			FROM laskun_lisatiedot
			JOIN tilausrivi
			ON ( tilausrivi.yhtio = laskun_lisatiedot.yhtio
				AND tilausrivi.otunnus = laskun_lisatiedot.otunnus )
			JOIN lasku
			ON ( lasku.yhtio = laskun_lisatiedot.yhtio
				AND lasku.tunnus = laskun_lisatiedot.otunnus)
			WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
			AND laskun_lisatiedot.otunnus = '{$tilausnumero}'";
$result = pupe_query($query);

$tilausrivit = array();

while ($tilausrivi = mysql_fetch_assoc($result)) {
	$tilausrivit[] = $tilausrivi;
	//setataan muuttujat monta kertaa
	$tarjouksen_asiakkaat = $tilausrivi['sopimus_lisatietoja'];
	$clearing = $tilausrivi['clearing'];
	$alkuperainen_varasto = $tilausrivi['varasto'];
	$viimeinen_voimassaolo_pvm = $tilausrivi['olmapvm'];
	$saate_teksti = $tilausrivi['saate'];
	$tilaustyyppi = $tilausrivi['tilaustyyppi'];
}

$tarjouksen_asiakkaat = explode(',', $tarjouksen_asiakkaat);

$dummy_tarjouksen_tilausnumero = $tilausnumero;

foreach ($tarjouksen_asiakkaat as $tarjous_asiakas) {
	$kukarow['kesken'] = 0;

	if ($toim == "EXTTARJOUS") {
		$tilausnumero = luo_myyntitilausotsikko('TARJOUS', $tarjous_asiakas, '', '', '', '', '', '', $clearing);
	}
	else {
		$tilausnumero = luo_myyntitilausotsikko('ENNAKKO', $tarjous_asiakas, '', '', '', '', '', '', $clearing);
	}

	//Halutaan pitää asiakkaille kopioitavissa tarjouksissa myyjän valitsema varasto
	$query = "	UPDATE lasku
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
				SET lasku.varasto = '{$alkuperainen_varasto}',
				lasku.olmapvm = '{$viimeinen_voimassaolo_pvm}',
				laskun_lisatiedot.saate = '{$saate_teksti}',
				lasku.clearing = '{$clearing}',
				lasku.tilaustyyppi = '{$tilaustyyppi}'
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tunnus = '{$tilausnumero}'";
	pupe_query($query);

	$query = "  SELECT *
				FROM lasku
				JOIN laskun_lisatiedot
				ON ( laskun_lisatiedot.yhtio = lasku.yhtio
					AND laskun_lisatiedot.otunnus = lasku.tunnus )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tunnus = '{$tilausnumero}'";
	$result = pupe_query($query);

	$laskurow = mysql_fetch_assoc($result);

	$kukarow['kesken'] = $tilausnumero;

	foreach ($tilausrivit as $tilausrivi) {
		if ($tilausrivi['tunnus'] == $tilausrivi['perheid'] or $tilausrivi['perheid'] == 0) {
			$trow = hae_tuote($tilausrivi['tuoteno']);
			if ($toim == "EXTTARJOUS") {
				$netto = "N";
				$hinta = $tilausrivi['hinta'];
				$alv = 0.01;
			}
			else {
				$netto = '';
				$query = "  SELECT selite AS ennakko_pros_a
							FROM tuotteen_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tuoteno = '{$tilausrivi['tuoteno']}'
							AND laji = 'parametri_ennakkoale_a'
							AND selite != ''";
				$result = pupe_query($query);
				$tuotteen_hinta = mysql_fetch_assoc($result);
				if ($tuotteen_hinta === false) {
					$hinta = 0;
				}
				else {
					$hinta = $tilausrivi['hinta'] * (1 - ($tuotteen_hinta['ennakko_pros_a'] / 100));
				}
			}
			$parametrit = array(
				'trow'				 => $trow,
				'tuoteno'			 => $trow['tuoteno'],
				'laskurow'			 => $laskurow,
				'kpl'				 => ($tilausrivi['varattu'] + $tilausrivi['jt']),
				'netto'				 => $netto,
				'hinta'				 => $hinta,
				'alv'				 => $alv,
				'varataan_saldoa'	 => 'EI',
			);
			lisaa_rivi($parametrit);
		}
	}

	require_once('tilaus-valmis.inc');
}

$query = "  UPDATE lasku
			SET tila = 'D'
			WHERE yhtio = '{$kukarow['yhtio']}'
			AND tunnus = '{$dummy_tarjouksen_tilausnumero}'";
pupe_query($query);
