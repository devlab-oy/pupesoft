<?php

if (php_sapi_name() == 'cli') {
	$pupe_root_polku = dirname(dirname(__FILE__));
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
	ini_set("display_errors", 0);

	require("inc/connect.inc");
	require("inc/functions.inc");

	$yhtio = trim($argv[1]);

	//yhtiötä ei ole annettu
	if (empty($yhtio)) {
		echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
		die;
	}

	$yhtiorow = hae_yhtion_parametrit($yhtio);

	// Haetaan käyttäjän tiedot
	$query = "	SELECT *
				FROM kuka
				WHERE yhtio = '$yhtio'
				AND kuka = 'admin'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		die("User admin not found");
	}

	// Adminin oletus
	$kukarow = mysql_fetch_assoc($result);
}
else if (php_sapi_name() != 'cli') {
	//for debug reasons
	require('inc/parametrit.inc');

	echo "<font class='head'>".t('Asiakkaan jt-rivien toimitusajan vahvistus')."</font><hr>";
}

//tarkistetaan onko yhtiolla sähköpostien lähetys parametri päällä
$yhtiokohtainen_jt_toimitusaika_email_vahvistus = tarkista_yhtion_jt_toimitusaika_email_vahvistus();

//haetaan ostotilaukset ja niiden tilausrivit joiden toimitusaika on päivittynyt viimeisein 24h aikana
$ostotilauksien_tilausrivit = hae_ostotilauksien_tilausrivit_joiden_toimitusaika_on_muuttunut_tai_vahvistettu();
if (!$yhtiokohtainen_jt_toimitusaika_email_vahvistus) {
	//haetaan asiakkaat joilla kyseinen parametri on päällä ja joilla on jt rivejä
	$myyntitilaukset = hae_myyntitilaukset_joiden_asiakkailla_jt_toimitusaika_email_vahvistus_paalla_ja_jt_riveja();
}
else {
	//haetaan yhtion kaikki myyntitilaukset joilla on jt_rivejä
	$myyntitilaukset = hae_myyntitilaukset_joilla_jt_riveja();
}

$asiakkaille_lahtevat_sahkopostit = generoi_asiakas_emailit($ostotilauksien_tilausrivit, $myyntitilaukset);

laheta_asiakas_emailit($asiakkaille_lahtevat_sahkopostit);

/**
 * Tarkistaa onko yhtiöllä parametri jt_toimitusaika_email_vahvistus päällä
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @return boolean
 */
function tarkista_yhtion_jt_toimitusaika_email_vahvistus() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT jt_toimitusaika_email_vahvistus
				FROM yhtion_parametrit
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$jt_toimitusaika_email_vahvistus = mysql_fetch_assoc($result);

	if ($jt_toimitusaika_email_vahvistus['jt_toimitusaika_email_vahvistus'] == 'K') {
		return true;
	}

	return false;
}

/**
 * Hakee ostotilaukset ja niiden rivit joiden toimaika on muuttunut viimeisen päivän aikana
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @return array
 */
function hae_ostotilauksien_tilausrivit_joiden_toimitusaika_on_muuttunut_tai_vahvistettu() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT lasku.tunnus AS lasku_tunnus,
				tilausrivi.toimaika,
				tilausrivi.tuoteno,
				tilausrivi.tilkpl as tilkpl,
				tilausrivi.tilkpl as tilkpl_jaljella
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus )
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
					AND tilausrivin_lisatiedot.toimitusaika_paivitetty >= DATE_SUB(NOW(), INTERVAL 1 DAY))
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila = 'O'
				AND lasku.alatila IN ('','A','B')
				ORDER BY lasku.toimaika ASC";
	$result = pupe_query($query);
	$ostolaskut = array();
	while ($ostolasku = mysql_fetch_assoc($result)) {
		$ostolaskut[$ostolasku['tuoteno']]['tilausrivit'][] = $ostolasku;
	}

	$ostolaskut = kasittele_ostotilaukset($ostolaskut);

	return $ostolaskut;
}

/**
 * Käsitellään ostolaskujen tilausrivit niin, että tuotteen alle saadaan kokonaistilaus määrät
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array $ostolaskut
 * @return array
 */
function kasittele_ostotilaukset($ostolaskut) {
	global $kukarow, $yhtiorow;

	$ostolasku_temp = array();
	foreach ($ostolaskut as $ostolasku_index => $ostolasku) {
		foreach ($ostolasku['tilausrivit'] as $ostolasku_tilausrivi) {
			$ostolasku_temp[$ostolasku_index]['kpl_yhteensa'] += $ostolasku_tilausrivi['tilkpl'];
			$ostolasku_temp[$ostolasku_index]['tilausrivit'][] = $ostolasku_tilausrivi;
		}
		$ostolasku_temp[$ostolasku_index]['tilkpl_jaljella'] = $ostolasku_temp[$ostolasku_index]['kpl_yhteensa'];
		$ostolasku_temp[$ostolasku_index]['tuoteno'] = $ostolasku_index;
	}

	return $ostolasku_temp;
}

/**
 * Hakee myyntitilaukset ja niiden rivit joiden asiakkailla on jt_toimitusaika_email_vahvistus päällä sekä myyntitilauksia, joissa on jt-rivejä
 * Tätä funktiota käytetään jos yhtiöparametri jt_toimitusaika_email_vahvistus ei ole päällä
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @return array
 */
function hae_myyntitilaukset_joiden_asiakkailla_jt_toimitusaika_email_vahvistus_paalla_ja_jt_riveja() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT lasku.tunnus,
				lasku.nimi,
				lasku.liitostunnus
				FROM lasku
				JOIN asiakas
				ON ( asiakas.yhtio = lasku.yhtio
					AND asiakas.tunnus = lasku.liitostunnus
					AND asiakas.jt_toimitusaika_email_vahvistus = 'K' )
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus
					AND tilausrivi.var = 'J')
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila = 'N'
				AND lasku.alatila IN ('T', 'U')
				GROUP BY lasku.tunnus
				ORDER BY lasku.luontiaika ASC";
	$result = pupe_query($query);

	$myyntitilaukset = array();
	while ($myyntitilaus = mysql_fetch_assoc($result)) {
		$myyntitilaus['tilausrivit'] = hae_myyntitilausrivit($myyntitilaus['tunnus']);
		$myyntitilaus['asiakas'] = hae_myyntitilauksen_asiakas($myyntitilaus['liitostunnus']);
		$myyntitilaukset[] = $myyntitilaus;
	}

	return $myyntitilaukset;
}

/**
 * Hakee kaikki myyntitilaukset ja niiden rivit, joissa on jt-rivejä
 * Tätä funktiota käytetään kun yhtiöparametri jt_toimitusaika_email_vahvistus on päällä
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @return array
 */
function hae_myyntitilaukset_joilla_jt_riveja() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT lasku.tunnus,
				lasku.nimi,
				lasku.liitostunnus
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus
					AND tilausrivi.var = 'J')
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila = 'N'
				AND lasku.alatila IN ('T', 'U')
				GROUP BY lasku.tunnus
				ORDER BY lasku.luontiaika ASC";
	$result = pupe_query($query);

	$myyntitilaukset = array();
	while ($myyntitilaus = mysql_fetch_assoc($result)) {
		$myyntitilaus['tilausrivit'] = hae_myyntitilausrivit($myyntitilaus['tunnus']);
		$myyntitilaus['asiakas'] = hae_myyntitilauksen_asiakas($myyntitilaus['liitostunnus']);
		$myyntitilaukset[] = $myyntitilaus;
	}

	return $myyntitilaukset;
}

/**
 * Hakee myyntitilauksen myyntitilausrivit
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param int $tilaus_tunnus
 * @return array
 */
function hae_myyntitilausrivit($tilaus_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT tuoteno,
				varattu+jt AS tilkpl,
				varattu+jt AS tilkpl_jaljella,
				nimitys,
				toimaika
				FROM tilausrivi
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND otunnus = '{$tilaus_tunnus}'
				AND var = 'J'";
	$result = pupe_query($query);

	$tilausrivit = array();
	while ($tilausrivi = mysql_fetch_assoc($result)) {
		$tilausrivit[] = $tilausrivi;
	}

	return $tilausrivit;
}

/**
 * Hakee myyntitilauksen asiakkaan
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param int $liitostunnus
 * @return array
 */
function hae_myyntitilauksen_asiakas($liitostunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$liitostunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

/**
 * Generoi ostotilausrivien sekä myyntitilauksien pohjalta asiakkaille lähetettävät emailit
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array $tuotteet_ja_niiden_ostotilausrivit
 * @param array $myyntitilaukset
 * @return array
 */
function generoi_asiakas_emailit($tuotteet_ja_niiden_ostotilausrivit, $myyntitilaukset) {
	global $kukarow, $yhtiorow;

	$asiakkaille_lahtevat_sahkopostit = array();

	foreach ($tuotteet_ja_niiden_ostotilausrivit as &$tuote_ja_sen_ostotilausrivit) {
		foreach ($myyntitilaukset as $myyntitilaus) {
			foreach ($myyntitilaus['tilausrivit'] as $myyntitilausrivi) {
				if ($tuote_ja_sen_ostotilausrivit['tuoteno'] == $myyntitilausrivi['tuoteno']) {
					//riittääkö tietyn tuotteen kaikkien ostotilausrivien kappaleet myyntitilausriville
					if ($tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] >= $myyntitilausrivi['tilkpl']) {
						foreach ($tuote_ja_sen_ostotilausrivit['tilausrivit'] as &$ostotilausrivi) {
							//riittääkö tämän kyseisen ostotilausrivin kappaleet myyntitilausriville
							if ($ostotilausrivi['tilkpl_jaljella'] >= $myyntitilausrivi['tilkpl_jaljella']) {
								populoi_asiakkaan_email_array($asiakkaille_lahtevat_sahkopostit, $myyntitilaus, $myyntitilausrivi, $ostotilausrivi);

								$ostotilausrivi['tilkpl_jaljella'] = $ostotilausrivi['tilkpl_jaljella'] - $myyntitilausrivi['tilkpl_jaljella'];
								$tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] = $tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] - $myyntitilausrivi['tilkpl_jaljella'];
								$myyntitilausrivi['tilkpl_jaljella'] = 0;

								break 1;
							}
							else {
								if ($ostotilausrivi['tilkpl_jaljella'] != 0) {
									populoi_asiakkaan_email_array($asiakkaille_lahtevat_sahkopostit, $myyntitilaus, $myyntitilausrivi, $ostotilausrivi);

									$myyntitilausrivi['tilkpl_jaljella'] = $myyntitilausrivi['tilkpl_jaljella'] - $ostotilausrivi['tilkpl_jaljella'];
									$ostotilausrivi['tilkpl_jaljella'] = 0;
								}
							}
						}
					}
					else {
						//jos ei riitä
						//viedään kyseisen tuotteen saldo nollille, jotta jälkimmäiset myyntitilaukset eivät pääse niihin käsiksi
						$tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] = 0;
						continue;
					}
				}
			}
		}
	}

	return $asiakkaille_lahtevat_sahkopostit;
}

/**
 * Populoi asiakas_email_arraytä, johon kerätään asiakkaalle lähetettäviä emaileja
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array $asiakkaille_lahtevat_sahkopostit
 * @param array $myyntitilaus
 * @param array $myyntitilausrivi
 * @param array $ostotilausrivi
 */
function populoi_asiakkaan_email_array(&$asiakkaille_lahtevat_sahkopostit, $myyntitilaus, $myyntitilausrivi, $ostotilausrivi) {
	global $kukarow, $yhtiorow;

	//jos kaikki ostotilausrivin tuotteet riittää
	if ($ostotilausrivi['tilkpl_jaljella'] >= $myyntitilausrivi['tilkpl_jaljella']) {
		$kappaleet_jotka_riittaa = $myyntitilausrivi['tilkpl_jaljella'];
	}
	else {
		$kappaleet_jotka_riittaa = $ostotilausrivi['tilkpl_jaljella'];
	}

	//jos riittää niin voidaan lisätä asiakkaan emailiin tilauksen alle tilausrivi
	if (empty($asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['tilausrivit'][$myyntitilausrivi['tuoteno']])) {
		$asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['tilausrivit'][$myyntitilausrivi['tuoteno']] .= '<td>'.$myyntitilausrivi['tuoteno'].'</td>'
				.'<td>'.$myyntitilausrivi['nimitys'].'</td>'
				.'<td>'.$myyntitilausrivi['tilkpl'].'</td>'
				.'<td>'.t("Toimitusaika").': '.$ostotilausrivi['toimaika'].' '.(number_format($kappaleet_jotka_riittaa, 0)).' '.t("kpl").' '; //HUOM JÄTETÄÄN VIIMEINEN TD PRINTTAAMATTA, jotta viimeiseen soluun voidaan appendaa lisää toimituksia. viimeinen td printataan emailin luomis vaiheessa
	}
	else {
		$asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['tilausrivit'][$myyntitilausrivi['tuoteno']] .= '<br/>'
				.t("Toimitusaika").': '.$ostotilausrivi['toimaika'].' '.(number_format($kappaleet_jotka_riittaa, 0)).' '.t("kpl").' '; //HUOM JÄTETÄÄN VIIMEINEN TD PRINTTAAMATTA, jotta viimeiseen soluun voidaan appendaa lisää toimituksia. viimeinen td printataan emailin luomis vaiheessa
	}

	$asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['email'] = $myyntitilaus['asiakas']['email'];
}

/**
 * Lähettää generoidut emailit
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array $asiakkaille_lahtevat_sahkopostit
 */
function laheta_asiakas_emailit($asiakkaille_lahtevat_sahkopostit = array()) {
	global $kukarow, $yhtiorow;

	foreach ($asiakkaille_lahtevat_sahkopostit as $asiakas_sahkoposti) {
		$body = t('Hei').',<br/><br/>';
		$body .= t("Seuraavien tuotteiden toimitusaika on muuttunut").'.<br/><br/>';
		foreach ($asiakas_sahkoposti['tilaukset'] as $tilaustunnus => $tilaus) {
			$body .= t('Tilaus').": {$tilaustunnus}"."<br/>";
			$body .= "<table border=1>";
			$body .= "<tr>";
			$body .= "<td>".t("Tuoteno")."</td>";
			$body .= "<td>".t("Nimitys")."</td>";
			$body .= "<td>".t("Tilattu kpl")."</td>";
			$body .= "<td>".t("Saapumiset")."</td>";
			$body .= "</tr>";
			foreach ($tilaus['tilausrivit'] as $tilausrivi) {
				$body .= "<tr>";
				$body .= $tilausrivi.'</td>'; //emailin generoimis vaiheessa jätetään viimeinen td printtaamatta, jotta viimeiseen soluun pystytään appendaamaan lisää toimituksia
				$body .= "</tr>";
			}
			$body .= "</table>";
			$body .= "<br/>";
			$body .= "<br/>";
			$body .= t("Ystävällisin terveisin");
			$body .= "<br/>";
			$body .= $yhtiorow['nimi'];
			$body .= "<br/>";
			$body .= "<br/>";
			$body .= t("Puh").': '.$yhtiorow['puhelin'];
			$body .= "<br/>";
			$body .= t("Mail").': '.$yhtiorow['email'];
			$body .= "<br/>";
			$body .= t("Www").': '.$yhtiorow['www'];
		}
		laheta_sahkoposti($asiakas_sahkoposti['email'], $body);
	}
}

/**
 * Lähettää yhden emailin
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param string $email
 * @param string $body
 */
function laheta_sahkoposti($email, $body) {
	global $kukarow, $yhtiorow;

	$parametrit = array(
		"to"		 => $email,
		"subject"	 => t('Tilauksenne toimitusajankohta on päivittynyt'),
		"ctype"		 => "html",
		"body"		 => $body,
	);
	pupesoft_sahkoposti($parametrit);
}
