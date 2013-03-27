<?php

//debuggi pitää olla jotta voidaan ajaa selaimesta ja käyttää debuggeria
$debug = false;
if (php_sapi_name() != 'cli' and !$debug) {
	die("T?t? scripti? voi ajaa vain komentorivilt?!");
}

require_once ('inc/connect.inc');
require_once ('inc/functions.inc');

/*
 * HOW TO:
 *
 * Tarvitaan 1 parametri: yhtio
 *
 */

if ((!isset($argv[1]) or $argv[1] == '') and !$debug) {
	echo "Anna yhti?!!!\n";
	die;
}

if (!$debug) {
	$yhtiorow = hae_yhtion_parametrit($argv[1]);
}
else {
	$yhtiorow = hae_yhtion_parametrit('artr');
}

$tilaukset = hae_suoritusta_odottavat_tilaukset();

kasittele_tilaukset($tilaukset);

function hae_suoritusta_odottavat_tilaukset() {
	global $yhtiorow;

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$yhtiorow['yhtio']}'
				AND tila 	= 'N'
				AND alatila = 'G'
				ORDER BY luontiaika ASC";
	$result = pupe_query($query);

	$tilaukset = array();

	while ($tilaus = mysql_fetch_assoc($result)) {
		$tilaukset[] = $tilaus;
	}

	return $tilaukset;
}

function kasittele_tilaukset($tilaukset) {
	global $kukarow, $yhtiorow;

	echo t("Otetaan").' '.count($tilaukset).' '.t("myyntitilausta käsittelyyn")."\n";

	foreach ($tilaukset as $laskurow) {

		// Parametrejä saatanat.php:lle
		$sytunnus = $laskurow['ytunnus'];
		$sliitostunnus = $laskurow['liitostunnus'];
		$eiliittymaa = "ON";
		$luottorajavirhe = "";
		$jvvirhe = "";
		$ylivito = 0;
		$trattavirhe = "";
		$laji = "MA";
		$grouppaus = ($yhtiorow["myyntitilaus_saatavat"] == "Y") ? "ytunnus" : "";

		$kukarow = hae_asiakas($laskurow['liitostunnus']);

		ob_start();

		require ("raportit/saatanat.php");

		ob_end_clean();

		if (!empty($luottorajavirhe) or $ylivito > 0) {
			echo t("Lasku").' '.$laskurow['tunnus'].' '.t("pysyy suoritusta odotus tilassa")."\n";
		}
		else {
			//jos laskut on maksettu, tilaus voidaan laittaa myyntitilaus kesken tilaan
			aseta_tilaus_kesken_tilaan_ja_aseta_uusi_lahto($laskurow);
		}
	}
}

function aseta_tilaus_kesken_tilaan_ja_aseta_uusi_lahto($laskurow) {
	global $yhtiorow, $kukarow;

	tarkista_suoratoimitus($laskurow);

	aseta_tilaus_kesken_tilaan($laskurow);
}

function hae_asiakas($liitostunnus) {
	global $yhtiorow;

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$yhtiorow['yhtio']}'
				AND tunnus = {$liitostunnus}";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function tarkista_suoratoimitus($myyntitilaus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT tilausrivin_lisatiedot.tilausrivilinkki,
				tilausrivi.otunnus
				FROM tilausrivin_lisatiedot
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
					AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki
				)
				WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivin_lisatiedot.vanha_otunnus = '{$myyntitilaus['tunnus']}'
				AND tilausrivin_lisatiedot.tilausrivilinkki != 0
				LIMIT 1";
	$suoratoimitus_result = pupe_query($query);

	if ($tilausrivin_lisatieto_row = mysql_fetch_assoc($suoratoimitus_result)) {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tilausrivin_lisatieto_row['otunnus']}'";
		$result = pupe_query($query);
		$laskurow = mysql_fetch_assoc($result);

		echo t("Naitettu ostotilaus").' '.$laskurow['tunnus'].' '.t("asetetaan kesken tilaan")."\n";

		aseta_tilaus_kesken_tilaan($laskurow);
	}
}

function aseta_tilaus_kesken_tilaan($laskurow) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE lasku
				SET tila = '{$laskurow['tila']}',
				alatila = ''
				WHERE yhtio = '{$yhtiorow['yhtio']}'
				AND tunnus = '{$laskurow['tunnus']}'
				AND tila = '{$laskurow['tila']}'
				AND alatila = 'G'";
	pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo t("Lasku").' '.$laskurow['tunnus'].' '.t("asetettiin kesken tilaan")."\n";

		if ($laskurow['tila'] == 'N') {
			//tilaus-valmis.inc hoitaa meille järkevän lähdön kun tilauksen tila ja alatila on oikein
			$kukarow['kesken'] = $laskurow['tunnus'];

			require("tilauskasittely/tilaus-valmis.inc");
		}
	}
}