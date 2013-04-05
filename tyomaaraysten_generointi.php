<?php

ini_set("memory_limit", "5G");

$debug = true;
if (php_sapi_name() != 'cli' and !$debug) {
	die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

require ("inc/connect.inc");
require ("inc/functions.inc");
require ("tilauskasittely/luo_myyntitilausotsikko.inc");

if (trim(empty($argv[1])) and !$debug) {
	echo "Et antanut yhtiötä!\n";
	exit;
}
else {
	$yhtio = "lpk";
}

if (!$debug) {
	// Parametrit
	$yhtio = pupesoft_cleanstring($argv[1]);
}

// Haetaan yhtiön tiedot
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

// Adminin oletus, mutta kuka konversio
$kukarow = mysql_fetch_assoc($result);

$request = array();

$laitteet = hae_laitteet_joiden_huolto_lahestyy($request);

//TODO kts. funktion TODO
//$laitteet = keraa_laitteittain_asiakkaan_alle($laitteet);

generoi_tyomaaraykset_huoltosykleista($laitteet);

//TODO: tee tämä funkkari niin että hakee vain yhden / useamman laitteen. tämä mahdollistaa sen, että näitä samoja funkkareita ja logiikkaa voidaan käyttää uuden laitteen työmääräysten generoimisessa
function hae_laitteet_joiden_huolto_lahestyy($request = array()) {
	global $kukarow;

	if (!empty($request['laitteet'])) {
		$laitteet_where = "AND laite.tuoteno IN (".implode(',', $request['laitteet']).")";
	}
	else {
		$laitteet_where = "";
	}
	$query = "	SELECT laite.tuoteno,
				laite.viimeinen_tapahtuma,
				laite.tunnus as laite_tunnus,
				ta.laji,
				ta.selite,
				paikka.nimi,
				paikka.olosuhde,
				huoltosykli.tyyppi,
				huoltosykli.koko,
				huoltosykli.olosuhde,
				huoltosykli.toimenpide as toimenpide,
				huoltosykli.huoltovali,
				tuote.*,
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
				AND laite.viimeinen_tapahtuma < Date_sub(CURRENT_DATE, INTERVAL (huoltosykli.huoltovali-30) DAY)
				{$laitteet_where}";
	$result = pupe_query($query);

	$laitteet = array();
	while ($laite = mysql_fetch_assoc($result)) {
		$laitteet[] = $laite;
	}

	return $laitteet;
}

//TODO 04-03-2013: tällä hetkellä työmääräys otsikoita luodaan niin monta kuin huollettavia laitteita on. tulevaisuudessa halutaan ehkä laittaa yhden asiakkaan tai yhden asiakkaan kohteen laitteet samalle työmääräykselle. tämä funktio on sitä varten. koodaa loppuun
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
		echo "TYÖMÄÄRÄYKSIÄ PITÄISI TULLA ".count($laitteet).' kappaletta';
		echo "<br/>";
	}
	foreach ($laitteet as $laite) {
		$onko_tyomaarays_jo_luotu_talle_laitteelle = tarkista_loytyyko_tyomaarays($laite);
		if ($onko_tyomaarays_jo_luotu_talle_laitteelle) {
			if($debug) {
				echo "Tälle laitteelle " . $laite['laite_tunnus'] . " on jo luotu työmääräys";
			}
			continue;
		}

		$tyomaarays_tunnus = luo_myyntitilausotsikko("TYOMAARAYS", $laite['asiakas_tunnus']);

		if ($debug and !empty($tyomaarays_tunnus)) {
			echo "Tyomääräys".$tyomaarays_tunnus." luotu";
			echo "<br/>";
		}

		if (empty($tyomaarays_tunnus)) {
			echo t("Joku meni pieleen");
			continue;
		}
		else {

			$laskurow = hae_tyomaarays($tyomaarays_tunnus);

			//lisätään sammuttimen toimenpiteen palvelurivi
			$trow = hae_tuote($laite['toimenpide']);
			$parametrit = array(
				'trow'		 => $trow,
				'laskurow'	 => $laskurow,
				'kpl'		 => 1,
				'tuoteno'	 => $trow['tuoteno'],
				'hinta'		 => $trow['hinta'],
			);
			$rivit = lisaa_rivi($parametrit);

			paivita_laite_tunnus_toimenpiteen_tilausriville($laite, $rivit);

			if ($debug) {
				echo "Lisätään palvelutuoterivi";
				echo "<br/>";
				var_dump($rivit);
				echo "<br/>";
				echo "<br/>";
			}
		}
	}
}

function paivita_laite_tunnus_toimenpiteen_tilausriville($laite, $tilausrivit) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tilausrivin_lisatiedot
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
					AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					AND tilausrivi.tunnus = '{$tilausrivit['lisatyt_rivit1'][0]}')
				SET asiakkaan_positio = {$laite['laite_tunnus']}
				WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'";
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

function hae_tyomaarays($tyomaarays_tunnus) {
	global $kukarow;

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tyomaarays_tunnus}'
				AND tila != 'D'";
	//TODO KORJAA WHERET
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

	$query = "		SELECT *
					FROM paikka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$paikka_tunnus}'";
	$paikka_result = pupe_query($query);
	return mysql_fetch_assoc($paikka_result);
}

function hae_kohde($kohde_tunnus) {
	global $kukarow;

	$query = "		SELECT *
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
