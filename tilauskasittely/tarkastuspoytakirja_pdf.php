<?php

namespace PDF\Tarkastuspoytakirja;

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/../inc/parametrit.inc')) {
	require_once($filepath . '/../inc/parametrit.inc');
}
else {
	require_once($filepath . '/parametrit.inc');
}

function hae_tarkastuspoytakirjat($lasku_tunnukset) {
	$pdf_tiedostot = array();
	if (!empty($lasku_tunnukset)) {
		$tyomaaraykset = \PDF\Tarkastuspoytakirja\pdf_hae_tyomaaraykset($lasku_tunnukset);

		foreach ($tyomaaraykset as $tyomaarays) {
			$filepath = kirjoita_json_tiedosto($tyomaarays, "tyomaarays_{$tyomaarays['tunnus']}");

			//ajettavan tiedoston nimi on tarkastuspoytakirja_pdf.rb
			$pdf_tiedostot[] = aja_ruby($filepath, 'tarkastuspoytakirja_pdf');
		}
	}

	return $pdf_tiedostot;
}

function pdf_hae_tyomaaraykset($lasku_tunnukset) {
	global $kukarow, $yhtiorow;

	if (!empty($lasku_tunnukset)) {
		$lasku_tunnukset = explode(',', $lasku_tunnukset);
	}
	else {
		return array();
	}

	//queryyn joinataan tauluja kohteeseen saakka, koska tarkastuspöytäkirjat halutaan tulostaa per kohde mutta työmääräykset on laite per työmääräin
	$query = "	SELECT lasku.*,
				kohde.tunnus as kohde_tunnus,
				laite.tunnus as laite_tunnus
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus
					AND tilausrivi.var != 'P')
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					)
				JOIN laite
				ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
					AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
				JOIN paikka
				ON ( paikka.yhtio = laite.yhtio
					AND paikka.tunnus = laite.paikka )
				JOIN kohde
				ON ( kohde.yhtio = paikka.yhtio
					AND kohde.tunnus = paikka.kohde )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tunnus IN('".implode("','", $lasku_tunnukset)."')";

	$result = pupe_query($query);

	$tyomaaraykset = array();
	while ($tyomaarays = mysql_fetch_assoc($result)) {
		//vaikka työmääräyksen kaikki tiedot saisi yllä olevasta querystä,
		//haetaan ne silti erillisillä queryillä,
		//koska ruby:lle pässättävästä arraystä halutaan multidimensional array.
		//Tämä sen takia että pdf koodiin ei tarvitsisi koskea.
		if (isset($tyomaaraykset[$tyomaarays['kohde_tunnus']])) {
			$tyomaaraysrivit_temp = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
			foreach ($tyomaaraysrivit_temp as $rivi_temp) {
				$tyomaaraykset[$tyomaarays['kohde_tunnus']]['rivit'][] = $rivi_temp;
			}
		}
		else {
			$tyomaarays['rivit'] = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_rivit($tyomaarays['tunnus']);
			$tyomaarays['kohde'] = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_kohde($tyomaarays['laite_tunnus']);
			$tyomaarays['asiakas'] = \PDF\Tarkastuspoytakirja\hae_tyomaarayksen_asiakas($tyomaarays['liitostunnus']);
			$tyomaarays['yhtio'] = $yhtiorow;
			$tyomaarays['logo'] = base64_encode(hae_yhtion_lasku_logo());
			$tyomaaraykset[$tyomaarays['kohde_tunnus']] = $tyomaarays;
		}
	}

	return $tyomaaraykset;
}

function hae_tyomaarayksen_rivit($lasku_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT tilausrivi.*,
				tilausrivin_lisatiedot.*,
				toimenpiteen_tyyppi.selite as toimenpiteen_tyyppi,
				huoltosyklit_laitteet.huoltovali as toimenpiteen_huoltovali,
				poikkeamarivi.tunnus as poikkeamarivi_tunnus
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					AND tilausrivin_lisatiedot.asiakkaan_positio != 0 )
				LEFT JOIN tilausrivi as poikkeamarivi
				ON ( poikkeamarivi.yhtio = tilausrivin_lisatiedot.yhtio
					AND poikkeamarivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki )
				LEFT JOIN tuotteen_avainsanat as toimenpiteen_tyyppi
				ON ( toimenpiteen_tyyppi.yhtio = tilausrivi.yhtio
					AND toimenpiteen_tyyppi.tuoteno = tilausrivi.tuoteno
					AND toimenpiteen_tyyppi.laji = 'tyomaarayksen_ryhmittely' )
				JOIN laite
				ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
					AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
				JOIN paikka
				ON ( paikka.yhtio = laite.yhtio
					AND paikka.tunnus = laite.paikka )
				JOIN tuote AS laite_tuote
				ON ( laite_tuote.yhtio = laite.yhtio
					AND laite_tuote.tuoteno = laite.tuoteno )
				JOIN tuotteen_avainsanat sammutin_koko
				ON ( sammutin_koko.yhtio = laite_tuote.yhtio
					AND sammutin_koko.tuoteno = laite_tuote.tuoteno
					AND sammutin_koko.laji = 'sammutin_koko' )
				JOIN tuotteen_avainsanat sammutin_tyyppi
				ON ( sammutin_tyyppi.yhtio = laite_tuote.yhtio
					AND sammutin_tyyppi.tuoteno = laite_tuote.tuoteno
					AND sammutin_tyyppi.laji = 'sammutin_tyyppi' )
				LEFT JOIN huoltosykli
				ON ( huoltosykli.yhtio = tilausrivi.yhtio
					AND huoltosykli.toimenpide = tilausrivi.tuoteno
					AND huoltosykli.olosuhde = paikka.olosuhde
					AND huoltosykli.koko = sammutin_koko.selite
					AND huoltosykli.tyyppi = sammutin_tyyppi.selite )
				LEFT JOIN huoltosyklit_laitteet
				ON ( huoltosyklit_laitteet.yhtio = huoltosykli.yhtio
					AND huoltosyklit_laitteet.huoltosykli_tunnus = huoltosykli.tunnus
					AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$lasku_tunnus}'
				AND tilausrivi.var != 'P'";
	$result = pupe_query($query);

	$rivit = array();
	while ($rivi = mysql_fetch_assoc($result)) {
		if (!empty($rivi['poikkeamarivi_tunnus'])) {
			$rivi['poikkeus'] = 'X';
		}
		else {
			$rivi['poikkeus'] = ' ';
		}

		if ($rivi['toimenpiteen_tyyppi'] == 'koeponnistus') {
			$rivi['koeponnistus'] = date('my', strtotime($rivi['toimitettuaika']));
			$rivi['huolto'] = ' ';
			$rivi['tarkastus'] = ' ';
		}
		else if ($rivi['toimenpiteen_tyyppi'] == 'huolto') {
			$rivi['koeponnistus'] = ' ';
			$rivi['huolto'] = date('my', strtotime($rivi['toimitettuaika']));
			$rivi['tarkastus'] = ' ';
		}
		else if ($rivi['toimenpiteen_tyyppi'] == 'tarkastus') {
			$rivi['koeponnistus'] = ' ';
			$rivi['huolto'] = ' ';
			$rivi['tarkastus'] = date('my', strtotime($rivi['toimitettuaika']));
		}
		
		$rivi['laite'] = \PDF\Tarkastuspoytakirja\hae_rivin_laite($rivi['asiakkaan_positio']);
		$rivit[] = $rivi;
	}

	return $rivit;
}

function hae_rivin_laite($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT laite.*,
				concat_ws(' ', tuote.nimitys, tuote.tuoteno) as nimitys,
				sammutin_koko.selite as sammutin_koko,
				sammutin_tyyppi.selite as sammutin_tyyppi
				FROM laite
				JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = laite.tuoteno )
				JOIN tuotteen_avainsanat as sammutin_koko
				ON ( sammutin_koko.yhtio = tuote.yhtio
					AND sammutin_koko.tuoteno = tuote.tuoteno
					AND sammutin_koko.laji = 'sammutin_koko' )
				JOIN tuotteen_avainsanat as sammutin_tyyppi
				ON ( sammutin_tyyppi.yhtio = tuote.yhtio
					AND sammutin_tyyppi.tuoteno = tuote.tuoteno
					AND sammutin_tyyppi.laji = 'sammutin_tyyppi' )
				WHERE laite.yhtio = '{$kukarow['yhtio']}'
				AND laite.tunnus = '{$laite_tunnus}'";
	$result = pupe_query($query);

	$laite = mysql_fetch_assoc($result);

	$laite['viimeinen_painekoe'] = date('my', strtotime(hae_laitteen_viimeinen_koeponnistus($laite['tunnus'])));

	return $laite;
}

function hae_laitteen_viimeinen_koeponnistus($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT huoltosyklit_laitteet.viimeinen_tapahtuma
				FROM huoltosyklit_laitteet
				JOIN huoltosykli
				ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
					AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
				JOIN tuote as toimenpide_tuote
				ON ( toimenpide_tuote.yhtio = huoltosykli.yhtio
					AND toimenpide_tuote.tuoteno = huoltosykli.toimenpide )
				JOIN tuotteen_avainsanat
				ON ( tuotteen_avainsanat.yhtio = toimenpide_tuote.yhtio
					AND tuotteen_avainsanat.tuoteno = toimenpide_tuote.tuoteno
					AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely'
					AND tuotteen_avainsanat.selite = 'koeponnistus')
				WHERE huoltosyklit_laitteet.yhtio = '{$kukarow['yhtio']}'
				AND huoltosyklit_laitteet.laite_tunnus = '{$laite_tunnus}'";
	$result = pupe_query($query);

	$viimeinen_tapahtuma = mysql_fetch_assoc($result);

	return $viimeinen_tapahtuma['viimeinen_tapahtuma'];
}

function hae_tyomaarayksen_asiakas($asiakas_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$asiakas_tunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_tyomaarayksen_kohde($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT kohde.*
				FROM kohde
				JOIN paikka
				ON ( paikka.yhtio = kohde.yhtio
					AND paikka.kohde = kohde.tunnus )
				JOIN laite
				ON ( laite.yhtio = kohde.yhtio
					AND laite.paikka = paikka.tunnus
					AND laite.tunnus = '{$laite_tunnus}')
				WHERE kohde.yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}
