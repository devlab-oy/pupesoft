<?php

ini_set("memory_limit", "5G");

$debug = true;
if (php_sapi_name() != 'cli' and !$debug) {
	die("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

require ("inc/connect.inc");
require ("inc/functions.inc");
require ("tilauskasittely/luo_myyntitilausotsikko.inc");

if (trim(empty($argv[1])) and !$debug) {
	echo "Et antanut yhtiˆt‰!\n";
	exit;
}
else {
	$yhtio = "lpk";
}

if (!$debug) {
	// Parametrit
	$yhtio = pupesoft_cleanstring($argv[1]);
}

// Haetaan yhtiˆn tiedot
$yhtiorow = hae_yhtion_parametrit($yhtio);

// Haetaan k‰ytt‰j‰n tiedot
$query = "	SELECT *
			FROM kuka
			WHERE yhtio = '$yhtio'
			AND kuka = 'admin'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
	die("User admin not found");
}

// Adminin oletus, mutta kuka konversio
$kukarow = mysql_fetch_assoc($result);

//generoidaan debug moodissa vain testi tuotteen tyˆm‰‰r‰yksi‰
if ($debug) {
	$laitteet = array(
		'TESTI',
		'TESTI2',
		'TESTI3',
	);
}

$request = array(
	'laitteet' => $laitteet,
);

$laitteet = hae_laitteet_joiden_huolto_lahestyy($request);

//TODO kts. funktion TODO
//$laitteet = keraa_laitteittain_asiakkaan_alle($laitteet);

generoi_tyomaaraykset_huoltosykleista($laitteet);

//TODO: tee t‰m‰ funkkari niin ett‰ hakee vain yhden / useamman laitteen. t‰m‰ mahdollistaa sen, ett‰ n‰it‰ samoja funkkareita ja logiikkaa voidaan k‰ytt‰‰ uuden laitteen tyˆm‰‰r‰ysten generoimisessa
function hae_laitteet_joiden_huolto_lahestyy($request = array()) {
	global $kukarow;

	if (!empty($request['laitteet'])) {
		$laitteet_where = "AND laite.tuoteno IN ('".implode("', '", $request['laitteet'])."')";
	}
	else {
		$laitteet_where = "";
	}
	$query = "	SELECT laite.tuoteno,
				laite.viimeinen_tapahtuma,
				laite.tunnus as laite_tunnus,
				ta.laji,
				ta.selite,
				paikka.nimi as paikka_nimi,
				paikka.olosuhde,
				huoltosykli.tyyppi,
				huoltosykli.koko,
				huoltosykli.olosuhde,
				huoltosykli.toimenpide as toimenpide,
				huoltosykli.huoltovali,
				tuote.*,
				kohde.nimi as kohde_nimi,
				kohde.*,
				asiakas.tunnus as asiakas_tunnus,
				asiakas.*
				FROM   laite
				JOIN tuotteen_avainsanat ta
				ON ( ta.yhtio = laite.yhtio
					AND ta.tuoteno = laite.tuoteno
					AND ta.laji = 'sammutin_tyyppi' )
				JOIN tuotteen_avainsanat ta2
				ON ( ta2.yhtio = laite.yhtio
					AND ta2.tuoteno = laite.tuoteno
					AND ta2.laji = 'sammutin_koko' )
				JOIN paikka
				ON ( paikka.yhtio = laite.yhtio
					AND paikka.tunnus = laite.paikka )
				JOIN huoltosykli
				ON ( huoltosykli.yhtio = laite.yhtio
					AND huoltosykli.tyyppi = ta.selite
					AND huoltosykli.koko = ta2.selite
					AND huoltosykli.olosuhde = paikka.olosuhde )
				JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = laite.tuoteno)
				JOIN kohde
				ON ( kohde.yhtio = paikka.yhtio
					AND kohde.tunnus = paikka.kohde )
				JOIN asiakas
				ON ( asiakas.yhtio = kohde.yhtio
					AND asiakas.tunnus = kohde.asiakas)
				WHERE  laite.yhtio = '{$kukarow['yhtio']}'
				/*haetaan laitteet, joiden viimenen tapahtuma on huoltovali - kuukausi sitten tehty. esim 365 - 30 = 11kk*/
				AND IFNULL(laite.viimeinen_tapahtuma, '0000-00-00') < Date_sub(CURRENT_DATE, INTERVAL (huoltosykli.huoltovali-30) DAY)
				{$laitteet_where}";
	$result = pupe_query($query);

	$laitteet = array();
	while ($laite = mysql_fetch_assoc($result)) {
		//@TODO suorittajan m‰‰ritt‰miseen pit‰‰ tehd‰ algoritmi jne jne.
		$laite['tyojono'] = 'joonas';
		$laitteet[] = $laite;
	}

	return $laitteet;
}

//TODO 04-03-2013: t‰ll‰ hetkell‰ tyˆm‰‰r‰ys otsikoita luodaan niin monta kuin huollettavia laitteita on. tulevaisuudessa halutaan ehk‰ laittaa yhden asiakkaan tai yhden asiakkaan kohteen laitteet samalle tyˆm‰‰r‰ykselle. t‰m‰ funktio on sit‰ varten. koodaa loppuun
function keraa_laitteittain_asiakkaan_alle($laitteet) {
	$laitteet_temp = array();

	foreach ($laitteet as $laite) {
		$laitteet_temp[$laite['asiakas_tunnus']][] = $laite;
	}

	return $laitteet_temp;
}

function generoi_tyomaaraykset_huoltosykleista($laitteet) {
	global $kukarow, $yhtiorow, $debug;

	if ($debug) {
		echo "TY÷MƒƒRƒYKSIƒ PITƒISI TULLA ".count($laitteet).' kappaletta';
		echo "<br/>";
	}
	foreach ($laitteet as $laite) {
		$onko_tyomaarays_jo_luotu_talle_laitteelle = tarkista_loytyyko_tyomaarays($laite);
		if ($onko_tyomaarays_jo_luotu_talle_laitteelle) {
			if ($debug) {
				echo "T‰lle laitteelle ".$laite['laite_tunnus']." on jo luotu tyˆm‰‰r‰ys";
				echo "<br/>";
			}
			continue;
		}

		//laitteen toimenpidetuote pit‰‰ olla saldoton
		$onko_toimenpide_tuote_saldoton = tarkista_toimenpide_saldo($laite);
		if (!$onko_toimenpide_tuote_saldoton) {
			if ($debug) {
				echo "Toimenpide tuote ".$laite['laite_tunnus']." pit‰‰ olla saldoton! Tyˆm‰‰r‰yst‰ t‰lle tuotteelle ei lis‰tty";
				echo "<br/>";
			}
			continue;
		}

		$kukarow['kesken'] = 0;

		$tyomaarays_tunnus = luo_myyntitilausotsikko("TYOMAARAYS", $laite['asiakas_tunnus']);

		//jos uusi laite, niin laitetaan tyˆm‰‰r‰yksen toimitusajankohta NOW
		if (empty($laite['viimeinen_tapahtuma'])) {
			aseta_tyomaarayksen_toimitusajankohta($tyomaarays_tunnus, date('Y-m-d'));
		}

		if ($debug and !empty($tyomaarays_tunnus)) {
			echo "Tyom‰‰r‰ys ".$tyomaarays_tunnus." luotu";
			echo "<br/>";
		}

		if (empty($tyomaarays_tunnus)) {
			echo t("Joku meni pieleen");
			continue;
		}
		else {

			$laskurow = hae_tyomaarays($tyomaarays_tunnus);

			//lis‰t‰‰n sammuttimen toimenpiteen palvelurivi
			$trow = hae_tuote($laite['toimenpide']);
			$parametrit = array(
				'trow'		 => $trow,
				'laskurow'	 => $laskurow,
				'kpl'		 => 1,
				'tuoteno'	 => $trow['tuoteno'],
				'hinta'		 => $trow['hinta'],
				'netto'		 => '',
			);
			$rivit = lisaa_rivi($parametrit);

			paivita_laite_tunnus_ja_kohteen_tiedot_toimenpiteen_tilausriville($laite, $rivit);
			paivita_tyojono_ja_tyostatus_tyomaaraykselle($tyomaarays_tunnus, $laite);
			paivita_viimenen_tapahtuma_laitteelle($laite);

			if ($debug) {
				echo "Lis‰t‰‰n palvelutuoterivi";
				echo "<br/>";
				var_dump($rivit);
				echo "<br/>";
				echo "<br/>";
			}
		}
	}
}

function paivita_laite_tunnus_ja_kohteen_tiedot_toimenpiteen_tilausriville($laite, $tilausrivit) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tilausrivin_lisatiedot
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
					AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					AND tilausrivi.tunnus = '{$tilausrivit['lisatyt_rivit1'][0]}')
				SET asiakkaan_positio = {$laite['laite_tunnus']}
				WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'";
	pupe_query($query);

	$laiteen_kohde_ja_paikka_tiedot = t("Laite")." ".$laite['tuoteno']." ".t("kohteessa").": {$laite['kohde_nimi']} ".t("paikalla").": {$laite['paikka_nimi']}";
	$query = "	UPDATE tilausrivi
				SET kommentti = '{$laiteen_kohde_ja_paikka_tiedot}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tilausrivit['lisatyt_rivit1'][0]}'";
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

function paivita_viimenen_tapahtuma_laitteelle($laite) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE laite
				SET viimeinen_tapahtuma = CURRENT_DATE
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$laite['laite_tunnus']}'";
	pupe_query($query);
}

function tarkista_loytyyko_tyomaarays($laite) {
	global $kukarow, $yhtiorow;

	//tarkistetaan lˆytyykˆ t‰lle laitteelle kesken tilassa / ei valmis oleva tyˆm‰‰r‰ys

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

function hae_tuote($tuoteno) {
	global $kukarow;

	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = '{$tuoteno}'";
	$result = pupe_query($query);
	return mysql_fetch_assoc($result);
}

function hae_paikka($paikka_tunnus) {
	global $kukarow;

	$query = "	SELECT *
				FROM paikka
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$paikka_tunnus}'";
	$paikka_result = pupe_query($query);
	return mysql_fetch_assoc($paikka_result);
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

function hae_asiakas($asiakas_tunnus) {
	global $kukarow;

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$asiakas_tunnus}'";
	$asiakas_result = pupe_query($query);
	return mysql_fetch_assoc($asiakas_result);
}
