<?php

namespace PDF\Tarkastuspoytakirja;

require("../inc/parametrit.inc");

function hae_tarkastuspoytakirjat($lasku_tunnukset) {
	$pdf_tiedostot = array();
	if (!empty($lasku_tunnukset)) {
		$tyomaaraykset = \PDF\Tarkastuspoytakirja\pdf_hae_tyomaaraykset($lasku_tunnukset);

		foreach ($tyomaaraykset as $tyomaarays) {
			$filepath = \PDF\Tarkastuspoytakirja\kirjoita_json_tiedosto($tyomaarays);

			$pdf_tiedostot[] = \PDF\Tarkastuspoytakirja\aja_ruby($filepath);
		}
	}

	return $pdf_tiedostot;
}

function pdf_hae_tyomaaraykset($lasku_tunnukset) {
	global $kukarow, $yhtiorow;

	//queryyn joinataan tauluja kohteeseen saakka, koska tarkastusp�yt�kirjat halutaan tulostaa per kohde mutta ty�m��r�ykset on laite per ty�m��r�in
	$query = "	SELECT lasku.*,
				kohde.tunnus as kohde_tunnus,
				laite.tunnus as laite_tunnus
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus )
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					AND tilausrivin_lisatiedot.tilausrivilinkki != 0 )
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
		//vaikka ty�m��r�yksen kaikki tiedot saisi yll� olevasta queryst�,
		//haetaan ne silti erillisill� queryill�,
		//koska ruby:lle p�ss�tt�v�st� arrayst� halutaan multidimensional array.
		//T�m� sen takia ett� pdf koodiin ei tarvitsisi koskea.
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
			$tyomaaraykset[$tyomaarays['kohde_tunnus']] = $tyomaarays;
		}
	}

	return $tyomaaraykset;
}

function hae_tyomaarayksen_rivit($lasku_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					AND tilausrivin_lisatiedot.asiakkaan_positio != 0 )
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$lasku_tunnus}'
				AND tilausrivi.var != 'P'";
	$result = pupe_query($query);

	$rivit = array();
	while ($rivi = mysql_fetch_assoc($result)) {
		$rivi['laite'] = \PDF\Tarkastuspoytakirja\hae_rivin_laite($rivi['asiakkaan_positio']);
		$rivit[] = $rivi;
	}

	return $rivit;
}

function hae_rivin_laite($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM laite
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$laite_tunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
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

function kirjoita_json_tiedosto($tyomaarays) {
	$filename = "tyomaarays_{$tyomaarays['tunnus']}.json";
	$filepath = "/tmp/{$filename}";

	array_walk_recursive($tyomaarays, '\PDF\Tarkastuspoytakirja\array_utf8_encode');

	file_put_contents($filepath, json_encode($tyomaarays));

	return $filepath;
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

function array_utf8_encode(&$item, $key) {
	$item = utf8_encode($item);
}

function aja_ruby($filepath) {
	global $pupe_root_polku;

	$cmd = "ruby {$pupe_root_polku}/pdfs/ruby/tarkastuspoytakirja_pdf.rb {$filepath}";
	$return = exec($cmd, $output, $return_code);

	//poistetaan json tiedosto
	unlink($filepath);

	// Palautetaan ensimm�inen rivi outputista, siin� on filenimet
	return $output[0];
}
?>
