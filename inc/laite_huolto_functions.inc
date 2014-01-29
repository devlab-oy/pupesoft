<?php

$filepath = dirname(__FILE__);
require_once("{$filepath}/../tilauskasittely/luo_myyntitilausotsikko.inc");

function hae_laitteelle_mahdolliset_huoltosyklit($laite_tyyppi = '', $laite_koko = '', $paikan_olosuhde = '') {
	global $kukarow, $yhtiorow;

	$huoltosykli_where = "";
	if ($laite_tyyppi != '') {
		$huoltosykli_where .= "	AND huoltosykli.tyyppi = '{$laite_tyyppi}'";
	}
	if ($laite_koko != '') {
		$huoltosykli_where .= "	AND huoltosykli.koko = '{$laite_koko}'";
	}
	if ($paikan_olosuhde != '') {
		$huoltosykli_where .= "	AND huoltosykli.olosuhde = '{$paikan_olosuhde}'";
	}

	$query = "	SELECT huoltosykli.tunnus,
				concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko,
					(
						SELECT selitetark
						FROM avainsana
						WHERE avainsana.yhtio = huoltosykli.yhtio
						AND avainsana.selite = huoltosykli.olosuhde
						AND avainsana.laji = 'OLOSUHDE'
					)
				) as dropdown_text,
				huoltosykli.huoltovali,
				huoltosykli.pakollisuus
				FROM huoltosykli
				JOIN tuote
				ON ( tuote.yhtio = huoltosykli.yhtio
					AND tuote.tuoteno = huoltosykli.toimenpide )
				WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
				{$huoltosykli_where}";
	$huoltosykli_result = pupe_query($query);
	$huoltosyklit = array();
	while ($huoltosykli = mysql_fetch_assoc($huoltosykli_result)) {
		$huoltosyklit[] = $huoltosykli;
	}

	return $huoltosyklit;
}

function hae_laitteet_ja_niiden_huoltosyklit_joiden_huolto_lahestyy() {
	global $kukarow;

	$query = "	SELECT laite.tuoteno,
				huoltosyklit_laitteet.viimeinen_tapahtuma,
				laite.tunnus AS laite_tunnus,
				huoltosyklit_laitteet.huoltosykli_tunnus,
				huoltosyklit_laitteet.huoltovali AS huoltovali,
				huoltosykli.toimenpide,
				huoltosykli.huoltovali AS toimenpide_oletus_huoltovali,
				asiakas.tunnus AS asiakas_tunnus,
				kohde.nimi AS kohde_nimi,
				paikka.nimi AS paikka_nimi,
				tuotteen_avainsanat.selite AS toimenpide_tuotteen_tyyppi,
				tuotteen_avainsanat.selitetark AS toimenpide_tuotteen_prioriteetti
				FROM   laite
				JOIN huoltosyklit_laitteet
				ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
					AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
				JOIN huoltosykli
				ON ( huoltosykli.yhtio = laite.yhtio
					AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
				JOIN paikka
				ON ( paikka.yhtio = laite.yhtio
					AND paikka.tunnus = laite.paikka )
				JOIN kohde
				ON ( kohde.yhtio = paikka.yhtio
					AND kohde.tunnus = paikka.kohde )
				JOIN asiakas
				ON ( asiakas.yhtio = kohde.yhtio
					AND asiakas.tunnus = kohde.asiakas )
				JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = huoltosykli.toimenpide )
				LEFT JOIN tuotteen_avainsanat
				ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
					AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
					AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely' )
				WHERE  laite.yhtio = '{$kukarow['yhtio']}'
				AND laite.tila IN ('N', 'V', 'K')/*Normaali, varalaite, kateissa*/
				AND ( laite.omistaja = '' OR laite.omistaja IS NULL )
				HAVING IFNULL(huoltosyklit_laitteet.viimeinen_tapahtuma, '0000-00-00') < Date_sub(CURRENT_DATE, INTERVAL (huoltosyklit_laitteet.huoltovali - 30) DAY)
				ORDER BY laite_tunnus ASC,
				tuotteen_avainsanat.jarjestys ASC";
	$result = pupe_query($query);

	$laitteet = array();
	while ($laite = mysql_fetch_assoc($result)) {
		//@TODO suorittajan määrittämiseen pitää tehdä algoritmi jne jne.
		$laite['tyojono'] = 'joonas';
		$laitteet[$laite['laite_tunnus']][] = $laite;
	}

	return $laitteet;
}

function paata_mitka_huollot_tehdaan($laitteiden_huoltosyklirivit) {
	global $kukarow, $yhtiorow;

	$huollettavien_laitteiden_huoltosyklirivit = array();
	$laitteiden_huoltosyklirivit_joita_ei_huolleta = array();
	foreach ($laitteiden_huoltosyklirivit as $laite_tunnus => $laitteen_huoltosyklirivit) {
		$huollettavien_laitteiden_huoltosyklirivit[] = $laitteen_huoltosyklirivit[0];
		$i = 0;
		foreach ($laitteen_huoltosyklirivit as $laitteen_huoltosyklirivi) {
			//Laitteelle ehdotettavat huollot tulevat toimenpide_tuotteen prioriteetin mukaan järjestettynä.
			//Tällöin korkein prioriteetti on siis ensimmäisenä $laitteen_huoltosyklirivit muuttujassa
			if ($i == 0) {
				$i++;
				continue;
			}
			$laitteiden_huoltosyklirivit_joita_ei_huolleta[$laite_tunnus][] = $laitteen_huoltosyklirivi;
			$i++;
		}
	}

	return array($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta);
}

function generoi_tyomaaraykset_huoltosykleista($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta) {
	global $kukarow, $yhtiorow, $debug;

	if ($debug) {
		echo "Työmääräyksiä pitäisi tulla ".count($huollettavien_laitteiden_huoltosyklirivit).' kappaletta';
		echo "<br/>";
		echo "<br/>";

		foreach ($huollettavien_laitteiden_huoltosyklirivit as $huollettavien_laitteiden_huoltosyklirivi) {
			if (!empty($huollettavien_laitteiden_huoltosyklirivi['toimenpide_tuotteen_tyyppi'])) {
				echo "Laitteelle: {$huollettavien_laitteiden_huoltosyklirivi['laite_tunnus']} generoidaan: {$huollettavien_laitteiden_huoltosyklirivi['toimenpide_tuotteen_tyyppi']}";
				echo "<br/>";
			}
			else {
				echo "<font class='error'>HUOM!! Laitteen: {$huollettavien_laitteiden_huoltosyklirivi['laite_tunnus']} huoltosyklin toimenpide tuotteen: {$huollettavien_laitteiden_huoltosyklirivi['toimenpide']} tuotteen avainsana (ryhmittely) on päivittämättä </font>";
				echo "<br/>";
			}
		}

		foreach ($laitteiden_huoltosyklirivit_joita_ei_huolleta as $laite_tunnus => $laitteen_huoltosyklirivit_joita_ei_huolleta) {
			foreach ($laitteen_huoltosyklirivit_joita_ei_huolleta as $laitteen_huoltosyklirivi_jota_ei_huolleta) {
				echo "Laitteelle: {$laite_tunnus} EI generoida huoltoa: {$laitteen_huoltosyklirivi_jota_ei_huolleta['toimenpide_tuotteen_tyyppi']}";
				echo "<br/>";
			}
		}
		echo "<br/>";
		echo "<br/>";
	}

	foreach ($huollettavien_laitteiden_huoltosyklirivit as $huollettavan_laitteen_huoltosyklirivi) {
		if ($huollettavan_laitteen_huoltosyklirivi['toimenpide_tuotteen_tyyppi'] == 'koeponnistus' and !empty($huollettavan_laitteen_huoltosyklirivi['viimeinen_tapahtuma'])) {
			$sekunttia_edellisesta_koeponnistuksesta = strtotime(date('Y-m-d')) - strtotime($huollettavan_laitteen_huoltosyklirivi['viimeinen_tapahtuma']);
			$paivaa_edellisesta_koeponnistuksesta = $sekunttia_edellisesta_koeponnistuksesta / 86400;
			$paivaa_seuraavaan_koeponnistukseen = $huollettavan_laitteen_huoltosyklirivi['huoltovali'] - $paivaa_edellisesta_koeponnistuksesta;
			//jos seuraavaan koeponnistukseen on enemmän kuin 2kk aikaa, ei generoida työmääräystä.
			if ($paivaa_seuraavaan_koeponnistukseen > 60) {
				continue;
			}
		}

		$onko_tyomaarays_jo_luotu_talle_laitteelle = tarkista_loytyyko_tyomaarays($huollettavan_laitteen_huoltosyklirivi);
		if ($onko_tyomaarays_jo_luotu_talle_laitteelle) {
			if ($debug) {
				echo "Tälle laitteelle ".$huollettavan_laitteen_huoltosyklirivi['laite_tunnus']." on jo luotu työmääräys";
				echo "<br/>";
			}
			continue;
		}

		//laitteen toimenpidetuote pitää olla saldoton
		$onko_toimenpide_tuote_saldoton = tarkista_toimenpide_saldo($huollettavan_laitteen_huoltosyklirivi);
		if (!$onko_toimenpide_tuote_saldoton) {
			if ($debug) {
				echo "Toimenpide tuote ".$huollettavan_laitteen_huoltosyklirivi['laite_tunnus']." pitää olla saldoton! Työmääräystä tälle tuotteelle ei lisätty";
				echo "<br/>";
			}
			continue;
		}

		//laitteen toimenpide tuotteen tuotteen_avainsana: tyomaarayksen ryhmittely pitää olla asetettu, myös prioriteetti (selitetark) pitää olla asetettu
		if (empty($huollettavan_laitteen_huoltosyklirivi['toimenpide_tuotteen_tyyppi']) or $huollettavan_laitteen_huoltosyklirivi['toimenpide_tuotteen_prioriteetti'] == null) {
			continue;
		}

		generoi_tyomaarays($huollettavan_laitteen_huoltosyklirivi, $laitteiden_huoltosyklirivit_joita_ei_huolleta);
	}
}

/**
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @global boolean $debug
 * @param array $huollettavan_laitteen_huoltosyklirivi
 * @param array $laitteiden_huoltosyklirivit_joita_ei_huolleta
 * @param string $poikkeus_paiva Jos halutaan generoida työmääräyksiä jollekkin muulle päivälle kuin CURRENT_DATE (konversio)
 *
 * param tarvittavia tietoja:
 * asiakas_tunnus
 * toimenpide_tuotteen_tyyppi
 * toimenpide
 * laite_tunnus
 * huoltosykli_tunnus
 * tuoteno (laitteen tuoteno)
 * kohde_nimi
 * paikka_nimi
 * tyojono
 * viimeinen_tapahtuma (optional)
 */
function generoi_tyomaarays($huollettavan_laitteen_huoltosyklirivi, $laitteiden_huoltosyklirivit_joita_ei_huolleta = array(
), $poikkeus_paiva = '') {
	global $kukarow, $yhtiorow, $debug;

	$kukarow['kesken'] = 0;

	$edellinen_tyomaarays = hae_laitteen_edellinen_tyomaarays($huollettavan_laitteen_huoltosyklirivi['laite_tunnus']);

	$tyomaarays_tunnus = luo_myyntitilausotsikko("TYOMAARAYS", $huollettavan_laitteen_huoltosyklirivi['asiakas_tunnus']);

	//jos uusi laite, niin laitetaan työmääräyksen toimitusajankohta NOW
	//HUOM jos huoltosyklirivin toimenpide_tuotteen_tyyppi = koeponnistus niin viimeinen_tapahtuma ei ole ikinä tyhjä. Luonti vaiheessa sinne asetetaan laite.valm_pvm
	if (empty($huollettavan_laitteen_huoltosyklirivi['viimeinen_tapahtuma']) and $huollettavan_laitteen_huoltosyklirivi['toimenpide_tuotteen_tyyppi'] != 'koeponnistus') {
		aseta_tyomaarayksen_toimitusajankohta($tyomaarays_tunnus, date("Y-m-d", strtotime('now')));
	}
	else {
		//poikkeus_paiva annetaan importissa. Normi generoinnissa tähän iffiin mennään.
		if ($poikkeus_paiva == '') {
			//merkataan toimitusajankohdaksi viimeinen_tapahtuma + huoltovali
			aseta_tyomaarayksen_toimitusajankohta($tyomaarays_tunnus, date("Y-m-d", strtotime($huollettavan_laitteen_huoltosyklirivi['viimeinen_tapahtuma']." + {$huollettavan_laitteen_huoltosyklirivi['huoltovali']} days")));
		}
	}

	if ($poikkeus_paiva != '') {
		aseta_tyomaarayksen_toimitusajankohta($tyomaarays_tunnus, date("Y-m-d", strtotime($poikkeus_paiva)));
	}

	if ($debug and !empty($tyomaarays_tunnus)) {
		echo "Tyomääräys ".$tyomaarays_tunnus." luotu";
		echo "<br/>";
	}

	if (empty($tyomaarays_tunnus)) {
		echo t("Joku meni pieleen");
		return false;
	}

	$laskurow = hae_tyomaarays($tyomaarays_tunnus);

	$kukarow['kesken'] = $laskurow['tunnus'];

	//lisätään sammuttimen toimenpiteen palvelurivi
	$trow = hae_tuote($huollettavan_laitteen_huoltosyklirivi['toimenpide']);

	if (isset($huollettavan_laitteen_huoltosyklirivi['tilausrivi_hinta']) and $huollettavan_laitteen_huoltosyklirivi['tilausrivi_hinta'] != '') {
		$hinta = $huollettavan_laitteen_huoltosyklirivi['tilausrivi_hinta'];
	}
	else {
		$hinta = $trow['hinta'];
	}

	if (isset($huollettavan_laitteen_huoltosyklirivi['ale']) and $huollettavan_laitteen_huoltosyklirivi['ale'] != '') {
		$ale = $huollettavan_laitteen_huoltosyklirivi['ale'];
	}
	else {
		$ale = 0;
	}

	$parametrit = array(
		'trow'		 => $trow,
		'laskurow'	 => $laskurow,
		'kpl'		 => 1,
		'tuoteno'	 => $trow['tuoteno'],
		'hinta'		 => $hinta,
		'netto'		 => '',
		'ale1'		 => $ale,
	);
	$rivit = lisaa_rivi($parametrit);

	paivita_laite_tunnus_toimenpiteen_tilausriville($huollettavan_laitteen_huoltosyklirivi, $rivit);
	paivita_tyojono_ja_tyostatus_tyomaaraykselle($tyomaarays_tunnus, $huollettavan_laitteen_huoltosyklirivi);
	paivita_viimenen_tapahtuma_laitteen_huoltosyklille($huollettavan_laitteen_huoltosyklirivi['laite_tunnus'], $huollettavan_laitteen_huoltosyklirivi['huoltosykli_tunnus']);

	if (!empty($edellinen_tyomaarays) and $edellinen_tyomaarays['tyostatus'] == 'K') {
		$kommentti = t('Edellisellä kerralla')." ({$edellinen_tyomaarays['toimitettuaika']}) ".t('laitteelle ei voitu suorittaa toimenpidettä')." {$edellinen_tyomaarays['toimenpidetuote_nimitys']} ".t('koska laitetta ei löytynyt');
		paivita_tilausrivin_kommentti($rivit[0][0], $kommentti);
	}

	if (!empty($laitteiden_huoltosyklirivit_joita_ei_huolleta)) {
		paivita_laitteen_muiden_jonossa_olevien_huoltosyklien_viimeinen_tapahtumapaiva($laitteiden_huoltosyklirivit_joita_ei_huolleta, $huollettavan_laitteen_huoltosyklirivi['laite_tunnus']);
	}

	if ($debug) {
		echo "Lisätään toimenpidetuote rivit";
		echo "<br/>";
		var_dump($rivit);
		echo "<br/>";
		echo "<br/>";
	}

	return $tyomaarays_tunnus;
}

function paivita_laite_tunnus_toimenpiteen_tilausriville($laite, $tilausrivit) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tilausrivin_lisatiedot
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
					AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					AND tilausrivi.tunnus = '{$tilausrivit[0][0]}')
				SET asiakkaan_positio = {$laite['laite_tunnus']}
				WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'";
	pupe_query($query);
}

function paivita_tyojono_ja_tyostatus_tyomaaraykselle($tyomaarays_tunnus, $laite) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tyomaarays
				SET tyojono = '{$laite['tyojono']}',
				tyostatus = 'A'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND otunnus = '{$tyomaarays_tunnus}'";
	pupe_query($query);
}

function paivita_viimenen_tapahtuma_laitteen_huoltosyklille($laite_tunnus, $huoltosykli_tunnus, $viimeinen_tapahtuma = '') {
	global $kukarow, $yhtiorow;

	//Viimeisen tapahtuman manual settiä käytetään konversio-ajossa.
	if (empty($viimeinen_tapahtuma)) {
		$huoltosyklit_laitteet_update = "viimeinen_tapahtuma = CURRENT_DATE";
	}
	else {
		$huoltosyklit_laitteet_update = "viimeinen_tapahtuma = '{$viimeinen_tapahtuma}'";
	}

	$query = "	UPDATE huoltosyklit_laitteet
				SET {$huoltosyklit_laitteet_update},
				muutospvm = NOW(),
				muuttaja = '{$kukarow['kuka']}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laite_tunnus = '{$laite_tunnus}'
				AND huoltosykli_tunnus = '{$huoltosykli_tunnus}'";
	pupe_query($query);
}

function paivita_laitteen_muiden_jonossa_olevien_huoltosyklien_viimeinen_tapahtumapaiva(&$laitteiden_huoltosyklirivit_joita_ei_huolleta, $laite_tunnus) {
	global $kukarow, $yhtiorow;

	//Haetaan $laitteiden_huoltosyklirivit_joita_ei_huolleta resultista laite_tunnuksella huoltosyklirivit, joille päivitetään viimeiseksi_tapahtumapäiväksi tämä päivä,
	//koska esim. kun suoritetaan koeponnistus, niin se sisältää huollon ja tarkastuksen, jolloin näille huoltosykliriveille pitää päivittää tämä päivä.

	$huoltosyklirivit = search_array_key_for_value_recursive($laitteiden_huoltosyklirivit_joita_ei_huolleta, 'laite_tunnus', $laite_tunnus);

	foreach ($huoltosyklirivit as $huoltosyklirivi) {
		paivita_viimenen_tapahtuma_laitteen_huoltosyklille($laite_tunnus, $huoltosyklirivi['huoltosykli_tunnus']);
	}
}

function paivita_tilausrivin_kommentti($tilausrivi_tunnus, $kommentti) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT kommentti
				FROM tilausrivi
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tilausrivi_tunnus}'";
	$result = pupe_query($query);
	$tilausrivi = mysql_fetch_assoc($result);

	$kommentti = $tilausrivi['kommentti'].' '.$kommentti;
	$query = "	UPDATE tilausrivi
				SET kommentti = '{$kommentti}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tilausrivi_tunnus}'";
	pupe_query($query);
}

function tarkista_loytyyko_tyomaarays($laite) {
	global $kukarow, $yhtiorow;

	//tarkistetaan löytyykö tälle laitteelle kesken tilassa / ei valmis oleva työmääräys

	$query = "	SELECT *
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus
					AND tilausrivi.tuoteno = '{$laite['toimenpide']}')
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					AND tilausrivin_lisatiedot.asiakkaan_positio = '{$laite['laite_tunnus']}')
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila = 'A'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		return false;
	}

	return true;
}

function tarkista_toimenpide_saldo($laite) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT ei_saldoa
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = '{$laite['toimenpide']}'
				AND ei_saldoa = 'o'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		return true;
	}

	return false;
}

function aseta_tyomaarayksen_toimitusajankohta($tyomaarays_tunnus, $ajankohta) {
	global $kukarow;

	$query = "	UPDATE lasku
				SET toimaika = '{$ajankohta}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tyomaarays_tunnus}'";
	pupe_query($query);
}

function aseta_tyomaaraysten_status($lasku_tunnukset, $status) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tyomaarays
				SET tyostatus = '{$status}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyomaarays.otunnus IN ('".implode("','", $lasku_tunnukset)."')";
	pupe_query($query);
}

function aseta_laitteen_tila($laite_tunnus, $tila) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE laite
				SET tila = '{$tila}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$laite_tunnus}'";
	pupe_query($query);
}

function hae_laitteen_edellinen_tyomaarays($laite_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT tyomaarays.*,
				tilausrivin_lisatiedot.asiakkaan_positio AS laite_tunnus,
				tilausrivi.nimitys AS toimenpidetuote_nimitys,
				tilausrivi.toimitettuaika
				FROM tyomaarays
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = tyomaarays.yhtio
					AND tilausrivi.otunnus = tyomaarays.otunnus )
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					AND tilausrivin_lisatiedot.asiakkaan_positio = '{$laite_tunnus}')
				WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
				ORDER BY tilausrivi.toimitettuaika DESC
				LIMIT 1";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function merkkaa_tyomaarays_tehdyksi($request) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE lasku
				SET tila = 'L',
				alatila = 'D'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus IN ('".implode("','", $request['lasku_tunnukset'])."')";
	pupe_query($query);

	//Toimitettuaika manual settiä käytetään konversio-ajossa
	if (empty($request['toimitettuaika'])) {
		$toimitettuaika_update = "toimitettuaika = NOW()";
	}
	else {
		$toimitettuaika_update = "toimitettuaika = '{$request['toimitettuaika']}'";
	}

	$query = "	UPDATE tilausrivi
				SET toimitettu = '{$kukarow['kuka']}',
				{$toimitettuaika_update}
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND otunnus IN ('".implode("','", $request['lasku_tunnukset'])."')";
	pupe_query($query);

	foreach ($request['lasku_tunnukset'] as $lasku_tunnus) {
		$query = "	SELECT tyostatus
					FROM tyomaarays
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND otunnus = '{$lasku_tunnus}'";
		$result = pupe_query($query);
		$tyomaarays = mysql_fetch_assoc($result);

		//Jos työmääräyksen status on K = Kateissa niin ei merkitä työmääräystä tehdyksi
		//koska laitetta ei ole tällöin oikeasti huollettu vaan se on kadonnut
		if ($tyomaarays['tyostatus'] != 'K') {
			aseta_tyomaarays_status('X', '', $lasku_tunnus);
		}
	}
}

function hae_tyomaarays($tyomaarays_tunnus) {
	global $kukarow;

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tyomaarays_tunnus}'
				AND tila != 'D'";
	$result = pupe_query($query);
	return mysql_fetch_assoc($result);
}

function hae_kohde($kohde_tunnus) {
	global $kukarow;

	$query = "	SELECT *
				FROM kohde
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kohde_tunnus}'";
	$kohde_result = pupe_query($query);
	return mysql_fetch_assoc($kohde_result);
}

function onko_huoltosyklilla_laitteita($huoltosykli_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM huoltosyklit_laitteet
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND huoltosykli_tunnus = '{$huoltosykli_tunnus}'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		return true;
	}

	return false;
}

function aseta_tyomaarays_var($tilausrivi_tunnus, $var) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tilausrivi
				SET var = '{$var}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tilausrivi_tunnus}'";
	pupe_query($query);
}

function aseta_tyomaarays_status($status, $tilausrivi_tunnus = '', $lasku_tunnus = '') {
	global $kukarow, $yhtiorow;

	if (empty($tilausrivi_tunnus) and empty($lasku_tunnus)) {
		throw new Exception("Tilausrivi tunnus ja lasku tunnus eivät molemmat voi olla tyhjiä");
	}

	$join = "";
	$where = "";
	if (!empty($tilausrivi_tunnus)) {
		$join = "	JOIN lasku
					ON ( lasku.yhtio = tyomaarays.yhtio
						AND lasku.tunnus = tyomaarays.otunnus )
					JOIN tilausrivi
					ON ( tilausrivi.yhtio = lasku.yhtio
						AND tilausrivi.otunnus = lasku.tunnus
						AND tilausrivi.tunnus = '{$tilausrivi_tunnus}')";
	}
	else if (!empty($lasku_tunnus)) {
		$where = "	AND tyomaarays.otunnus = '{$lasku_tunnus}'";
	}

	$query = "	UPDATE tyomaarays
				{$join}
				SET tyomaarays.tyostatus = '{$status}'
				WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
				{$where}";
	pupe_query($query);
}

//luodaan huoltosykli huoltosyklityypin perusteella
function huoltosykli_rivi($tyyppi) {
	global $tunnus;

	$laitteen_huoltosyklit = hae_laitteen_huoltosyklit($tunnus, $tyyppi);

	$pakollisuus_chk = "";

	$huoltosyklit = hae_huoltosyklit($tunnus, $tyyppi);

	if ($laitteen_huoltosyklit[0]['pakollisuus'] == 1) {
		$pakollisuus_chk = "CHECKED";
	}

	echo "<tr>";
	echo "<th>".t('Huoltosykli')." <br /> {$tyyppi}";
	echo "<input type='hidden' name='huoltosyklit[{$tyyppi}][huoltosykli_laite_tunnus]' value='{$laitteen_huoltosyklit[0]['hl_tunnus']}' />";
	echo"</th>";
	echo "<td>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t('Huoltosykli')."</th>";
	echo "<td>";
	echo "<select name='huoltosyklit[{$tyyppi}][huoltosykli_tunnus]'>";
	$sel = "";
	echo "<option value='0'>Ei huoltosykliä</option>";

	foreach ($huoltosyklit as $huoltosykli) {
		if ($laitteen_huoltosyklit[0]['tunnus'] == $huoltosykli['tunnus']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$huoltosykli['tunnus']}' {$sel}>{$huoltosykli['dropdown_text']}";
		$sel = "";
	}

	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Huoltovali')."</th>";
	echo "<td>";

	echo "<select name='huoltosyklit[{$tyyppi}][huoltovali]'>";

	$huoltovali_options = huoltovali_options($huoltosykli['tunnus']);

	if ($val == $laitteen_huoltosyklit[0]['huoltovali']) {
		$sel = "SELECTED";
	}

	foreach ($huoltovali_options as $key => $val) {
		echo "<option value='".$val."'>".$key."</option>";
		$sel = "";
	}
	echo "</select>";

	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>";
	echo "</tr>";
}

//haetaan laitteeseen yhdistetyt huoltosyklit
function hae_laitteen_huoltosyklit($laitetunnus, $tyyppi) {
	global $kukarow;

	$query = "	SELECT huoltosykli.tunnus,
				huoltosyklit_laitteet.huoltovali,
				huoltosyklit_laitteet.pakollisuus,
				huoltosyklit_laitteet.tunnus AS hl_tunnus
				FROM huoltosyklit_laitteet
				JOIN huoltosykli
				ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
					AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
				JOIN tuote
				ON ( tuote.yhtio = huoltosykli.yhtio
					AND tuote.tuoteno = huoltosykli.toimenpide )
				WHERE huoltosyklit_laitteet.yhtio = '{$kukarow['yhtio']}'
				AND laite_tunnus = '{$laitetunnus}'
				AND tuote.nimitys LIKE '%{$tyyppi}%'";

	$result = pupe_query($query);

	$laitteen_huoltosyklit = array();

	while ($huoltosykli = mysql_fetch_assoc($result)) {
		$laitteen_huoltosyklit[] = $huoltosykli;
	}

	if (count($laitteen_huoltosyklit) < 1) {
		$laitteen_huoltosyklit[] = array(
			'tunnus'		 => 0,
			'huoltovali'	 => 0,
			'pakollisuus'	 => 0,
			'hl_tunnus'		 => 0
		);
	}

	return $laitteen_huoltosyklit;
}

//haetaan sallitut huoltosyklit huoltosyklityypin ja laitenumeron perusteella
function hae_huoltosyklit($tunnus, $tyyppi) {
	global $kukarow;

	if ($tunnus != '') {
		$tuoteno_query = "	SELECT tuoteno
							FROM laite
							WHERE tunnus = $tunnus
							AND yhtio = '{$kukarow['yhtio']}'";
		$tuoteno_result = pupe_query($tuoteno_query);
		$tuoteno = mysql_result($tuoteno_result, 0);

		$sammutin_koko_query = "SELECT selite
								FROM tuotteen_avainsanat
								WHERE tuoteno = '{$tuoteno}'
								AND laji = 'sammutin_koko'
								AND yhtio = '{$kukarow['yhtio']}'";
		$sammutin_koko_result = pupe_query($sammutin_koko_query);
		$sammutin_koko = mysql_result($sammutin_koko_result, 0);

		$sammutin_tyyppi_query = "	SELECT selite
									FROM tuotteen_avainsanat
									WHERE tuoteno = '{$tuoteno}'
									AND laji = 'sammutin_tyyppi'
									AND yhtio = '{$kukarow['yhtio']}'";
		$sammutin_tyyppi_result = pupe_query($sammutin_tyyppi_query);
		$sammutin_tyyppi = mysql_result($sammutin_tyyppi_result, 0);
	}

	$query = "	SELECT huoltosykli.tunnus,
				concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko,
					(
						SELECT selitetark
						FROM avainsana
						WHERE avainsana.yhtio = huoltosykli.yhtio
						AND avainsana.selite = huoltosykli.olosuhde
						AND avainsana.laji = 'OLOSUHDE'
					)
				) as dropdown_text,
				huoltosykli.huoltovali,
				huoltosykli.pakollisuus
				FROM huoltosykli
				JOIN tuote
				ON ( tuote.yhtio = huoltosykli.yhtio
					AND tuote.tuoteno = huoltosykli.toimenpide )
				JOIN tuotteen_avainsanat
				ON ( tuotteen_avainsanat.yhtio = huoltosykli.yhtio
					AND tuotteen_avainsanat.tuoteno = huoltosykli.toimenpide )
				WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
				AND tuotteen_avainsanat.selite LIKE '%{$tyyppi}%'";

	if ($tunnus != '') {
		$query .= " AND huoltosykli.tyyppi = '{$sammutin_tyyppi}' AND huoltosykli.koko = $sammutin_koko";
	}

	$huoltosykli_result = pupe_query($query);

	$huoltosyklit = array();

	while ($huoltosykli = mysql_fetch_assoc($huoltosykli_result)) {
		$huoltosyklit[] = $huoltosykli;
	}

	return $huoltosyklit;
}
