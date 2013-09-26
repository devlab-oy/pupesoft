<?php

require ("parametrit.inc");
require ("Validation.php");
require_once('luo_myyntitilausotsikko.inc');
enable_ajax();

?>

<style>
	.tr_border_top {
		border-top: 1px solid;
	}
	.text_align_right {
		text-align: right;
	}
</style>
<script>

function tarkista(type, toim) {
	if (type == "hyvaksy" && toim == "EXTTARJOUS") {
		ok = confirm($('#hyvaksy_tarjous_message').val());
	}
	else if (type == "hylkaa" && toim == "EXTTARJOUS") {
		ok = confirm($('#hylkaa_tarjous_message').val());
	}
	else {
		ok = true;
	}

	if (ok) {
		return true;
	}
	else {
		return false;
	}
}

$(function() {

	function confirmation(question) {
	    var defer = $.Deferred();
	    $('<div></div>')
	        .html(question)
	        .dialog({
	            autoOpen: true,
	            modal: true,
	            title: 'Vahvistus',
	            buttons: {
	                "L�het�": function () {
	                    defer.resolve(true);
						$(this).dialog("close");
	                },
	                "Peruuta": function () {
	                    defer.resolve(false);
	                    $(this).dialog("close");
	                }
	            }
	        });
	    return defer.promise();
	};

	$('#hyvaksyennakko').on('click', function() {
	    var question = "Kiitos ennakkotilauksestasi";
	    confirmation(question).then(function (answer) {
	        if(answer){
				$('#hyvaksy_hylkaa_formi').submit();
	        } else {
	        }
	    });
	});
});
	
</script>

<?php

if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
	liite_popup("AK", $tuotetunnus, $width, $height);
}
else {
	liite_popup("JS");
}

if (function_exists("js_popup")) {
	echo js_popup(-100);
}
if ($toim == 'EXTTARJOUS') {
	echo "<font class='head'>".t("Tarjoukset")."</font><hr>";
}
elseif ($toim == 'EXTENNAKKO') {
	echo "<font class='head'>".t("Ennakkomyynnit")."</font><hr>";
	//Nollataan kesken ettei mene ennakkotilaukset sekaisin normaalien myyntitilausten kanssa kun surffaillaan villisti vasemmasta valikosta
	$kukarow['kesken'] = 0;
}
else {
	echo "<font class='error'>".t("Virheellinen toiminto")."!</font><hr>";
	exit;
}

$request = array(
	"toim"							 => $toim,
	"kayttajaan_liitetty_asiakas"	 => $kayttajaan_liitetty_asiakas,
	"asiakkaan_tarjoukset"			 => $asiakkaan_tarjoukset,
	"action"						 => $action,
	"valittu_tarjous_tunnus"		 => $valittu_tarjous_tunnus,
	"hyvaksy"						 => $hyvaksy,
	"hylkaa"						 => $hylkaa,
	"paivita"						 => $paivita,
);

$request['kayttajaan_liitetty_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();

if ($tee == "LISAARIVI") {
	$parametrit = array("lasku_tunnus" => $otunnus,
						"tuoteno" => $tuoteno,
						"kpl" => $kpl,
						"toim" => $request['toim']);
	lisaa_ennakkorivi($parametrit);
}

$action = $request['action'];

// Halutaan listata kaikki ennakot/tarjoukset
if ($action == "") {
	$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset($request['kayttajaan_liitetty_asiakas']['tunnus'], $request['toim']);

	if (count($request['asiakkaan_tarjoukset']) == 0) {
		if ($request['toim'] == "EXTENNAKKO") {
			echo t("Sinulle ei ole voimassaolevia ennakkotilauksia.");
		}
		else {
			echo t("Sinulle ei ole voimassaolevia tarjouksia.");
		}
	}
	else {
		$params = array("data"	 => $request['asiakkaan_tarjoukset'],
			"toim"	 => $request['toim']);
		piirra_tarjoukset($params);
	}

	if ($toim == "EXTENNAKKO") {
		// Uuden ennakon luonti asiakkaan n�kym�st�
		echo "<br>";
		echo "<br>";
		echo "<form method='post' action=''>";
		echo "<input type='hidden' name='action' value='luo_uusi_ennakko'>";
		echo "<input type='submit' id='luo_uusi_ennakko' value='".t("Luo uusi ennakkotilaus")."'>";
		echo "</form>";
	}
}
// N�ytet��n yksitt�inen ennakko/tarjous
if ($action == 'nayta_tarjous') {
	nayta_tarjous($request['valittu_tarjous_tunnus'], $request['toim']);
}

// Hyv�ksyt��n/hyl�t��n/p�ivitet��n yksi ennakko/tarjous
if ($action == 'hyvaksy_hylkaa_paivita') {
	if (isset($request['paivita']) and $toim == 'EXTENNAKKO') {
		unset($request['paivita']);
		$params = array("valittu_tarjous_tunnus" => $valittu_tarjous_tunnus,
		 				"syotetyt_lisatiedot" => $syotetyt_lisatiedot,
						"kappalemaarat" => $kappalemaarat,
						"toim" => $request['toim']);
		$onnistuiko_toiminto = paivita_ennakko($params);

		if ($onnistuiko_toiminto) {
			nayta_tarjous($request['valittu_tarjous_tunnus'], $request['toim']);
		}
		exit;
	}
	elseif (isset($request['hyvaksy'])) {
		if ($toim == 'EXTTARJOUS') {
			$onnistuiko_toiminto = hyvaksy_tarjous($request['valittu_tarjous_tunnus'], $syotetyt_lisatiedot);
		}
		else {
			$parametrit = array("valittu_tarjous_tunnus" => $valittu_tarjous_tunnus,
								"syotetyt_lisatiedot" => $syotetyt_lisatiedot,
								"kappalemaarat" => $kappalemaarat,
								"toim" => $toim);
			$onnistuiko_toiminto = hyvaksy_ennakko($parametrit);
		}
	}
	elseif($toim == "EXTTARJOUS"){
		$onnistuiko_toiminto = hylkaa($request['valittu_tarjous_tunnus']);
	}
	else {
		//T�nne ei pit�isi ikin� menn�
		echo "<font class='error'>".t("K�sittelyss� tapahtui virhe")."</font>";
		exit;
	}

	if (!$onnistuiko_toiminto) {
		echo "<font class='error'>".t("Toiminto ep�onnistui")."</font>";
	}

	echo "<br>";
	echo "<br>";

	echo "	<script>
			setTimeout(\"parent.location.href='$palvelin2'\", 2000);
			</script>";
	exit;
}

if ($action == 'luo_uusi_ennakko') {
	$ennakko_asiakas = hae_extranet_kayttajaan_liitetty_asiakas();

	$uusi_tilausnumero = luo_myyntitilausotsikko($toim, $ennakko_asiakas['tunnus'], '', '', '', '', '', '');

	$uusi_saate_teksti = "T�m� on Extranet-asiakkaan luoma ennakkotilaus";

	$tilaustyyppi = 'E';

	$viimeinen_voimassaolo_pvm = date('Y-m-d', strtotime('now + 30 day'));

	$query = "	UPDATE lasku
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
				SET lasku.olmapvm = '{$viimeinen_voimassaolo_pvm}',
				laskun_lisatiedot.saate = '{$uusi_saate_teksti}',
				lasku.clearing = '{$toim}',
				lasku.tilaustyyppi = '{$tilaustyyppi}'
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tunnus = '{$uusi_tilausnumero}'";
	pupe_query($query);
	
	$query = "  SELECT *
				FROM lasku
				JOIN laskun_lisatiedot
				ON ( laskun_lisatiedot.yhtio = lasku.yhtio
					AND laskun_lisatiedot.otunnus = lasku.tunnus )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tunnus = '{$uusi_tilausnumero}'";
	$result = pupe_query($query);

	$laskurow = mysql_fetch_assoc($result);
	
	$request['valittu_tarjous_tunnus'] = $uusi_tilausnumero;

	$kukarow['kesken'] = $uusi_tilausnumero;

	nayta_tarjous($uusi_tilausnumero, $toim);
}

if (!empty($request['action'])) {
	$laskurow = hae_extranet_tarjous($request['valittu_tarjous_tunnus'], $toim);

	if (empty($laskurow)) {
		if ($toim == 'EXTTARJOUS') {
			echo "<font class='error'>".t("Tarjous kadonnut")."</font><hr>";
		}
		elseif ($toim == 'EXTENNAKKO') {
			echo "<font class='error'>".t("Ennakko kadonnut")."</font><hr>";
		}
		exit;
	}
}

function hyvaksy_ennakko($parametrit) {
	global $kukarow, $yhtiorow;
	
	$valittu_tarjous_tunnus = $parametrit['valittu_tarjous_tunnus'];
	$toim = $parametrit['toim'];
	$kukarow['kesken'] = $valittu_tarjous_tunnus;
	$onnistuiko_toiminto = paivita_ennakko($parametrit);

	// Vaihdetaan tila/alatila Ennakko/Lep��m�ss�
	if ($onnistuiko_toiminto) {

		$laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus, $toim);

		require_once('tilaus-valmis.inc');

		$kukarow['kesken'] = '';
		$valittu_tarjous_tunnus = '';

		return true;
	}
	else {
		return false;
	}
}

function hyvaksy_tarjous($valittu_tarjous_tunnus, $syotetyt_lisatiedot) {
	global $kukarow, $yhtiorow;

	$kukarow['kesken'] = $valittu_tarjous_tunnus;
	$validations = array(
		'syotetyt_lisatiedot' => 'kirjain_numero',
	);

	$validator = new FormValidator($validations);

	if ($validator->validate(array('syotetyt_lisatiedot' => $syotetyt_lisatiedot))) {

		//asetetaan myyntitilaus Myyntitilaus kesken Tulostusjonossa
		$query = "	UPDATE lasku
					SET sisviesti1='{$syotetyt_lisatiedot}'
					WHERE yhtio='$kukarow[yhtio]'
					AND tunnus='$valittu_tarjous_tunnus'";
		pupe_query($query);

		// Kopsataan valitut rivit uudelle myyntitilaukselle
		require("tilauksesta_myyntitilaus.inc");
		$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($valittu_tarjous_tunnus, '', '', '');

		if ($tilauksesta_myyntitilaus != '') {
			echo "$tilauksesta_myyntitilaus<br><br>";

			$query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$valittu_tarjous_tunnus'";
			pupe_query($query);
		}

		$aika = date("d.m.y @ G:i:s", time());
		echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

		$tee = '';
		$tilausnumero = '';
		$laskurow = '';
		$kukarow['kesken'] = '';
		return true;
	}
	return false;
}

function paivita_ennakko($params) {
	global $kukarow, $yhtiorow;

	$syotetyt_lisatiedot = $params['syotetyt_lisatiedot'];
	$valittu_tarjous_tunnus = $params['valittu_tarjous_tunnus'];
	$kappalemaarat = $params['kappalemaarat'];
	$toim = $params['toim'];

	// Siisti��n parametrit tietokantaqueryj� varten
	$syotetyt_lisatiedot = pupesoft_cleanstring($syotetyt_lisatiedot);
	$valittu_tarjous_tunnus = pupesoft_cleanstring($valittu_tarjous_tunnus);

	// Haetaan kannasta tilausrivit
	$muokkaamaton_ennakko = hae_tarjous($valittu_tarjous_tunnus);

	foreach ($kappalemaarat as $key => $value) {

		// Kappalem��r� k�ytt�liittym�st�, pilkut pisteiksi ja round 2
		$value = round(str_replace(",", ".", pupesoft_cleanstring($value)), 2);
		
		// Etsit��n tilausrivitunnuksen perusteella tuotteen kannassa oleva kappalem��r�
		$loytynyt_tilausrivi = search_array_key_for_value_recursive($muokkaamaton_ennakko['tilausrivit'], 'tunnus', $key);

		// Tarkistetaan l�ytyik� tilausrivi, jos ei l�ydy, ei tehd� mit��n
		if (empty($loytynyt_tilausrivi[0])) {
			continue;
		}

		// Kappalem��r� kannasta
		$kplmaara = $loytynyt_tilausrivi[0]['kpl'];

		// jos Optio-rivi niin katsotaan onko sy�tetty arvo muutettu tyhj�st� joksikin muuksi, jos ei niin toimenpiteit� riville ei vaadita
		if ($loytynyt_tilausrivi[0]['var'] == "O" and $value == '') {
			continue;
		}
		// Tarkistetaan onko kappalem��r�� muutettu tai onko kplm��r� tyhj�, jos ei muutoksia niin toimenpiteit� riville ei vaadita
		elseif ($kplmaara == $value and $loytynyt_tilausrivi[0]['var'] != "O") {
			continue;
		}

		// Tuoteperheen tapauksessa p�ivitet��n/poistetaan kaikki tuoteperheen rivit, muuten vain ko. rivi
		if ($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus']) {
			$andy = "AND perheid = '{$loytynyt_tilausrivi[0]['tunnus']}'";
		}		
		else {
			$andy = "AND tunnus = '{$loytynyt_tilausrivi[0]['tunnus']}'";
		}

		// Jos ollaan nollattu kappaleet, p�ivitet��n rivi tilaan Optio
		if ($value == 0) {
			$query = "  UPDATE tilausrivi
						SET var = 'O'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$valittu_tarjous_tunnus}'
						{$andy}";
			pupe_query($query);
		}
		else {
			// Rivin poisto
			$query = "  UPDATE tilausrivi
						SET tyyppi = 'D'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$valittu_tarjous_tunnus}'
						{$andy}";
			pupe_query($query);

			$parametrit = array("lasku_tunnus" => $valittu_tarjous_tunnus,
								"tuoteno" => $loytynyt_tilausrivi[0]['tuoteno'],
								"kpl" => $value,
								"toim" => $toim,
								"syotettyhinta" => $loytynyt_tilausrivi[0]['hinta']);

			lisaa_ennakkorivi($parametrit);
		}
	}

	// P�ivitet��n k�ytt�j�n lis��m�t kommentit
	$query = "	UPDATE lasku
				SET sisviesti1 = '{$syotetyt_lisatiedot}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$valittu_tarjous_tunnus}'";
	pupe_query($query);

	return true;
}

function hylkaa($valittu_tarjous_tunnus) {
	global $kukarow, $yhtiorow;

	$kukarow['kesken'] = $valittu_tarjous_tunnus;
	$laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus, $toim);

	$query = "	UPDATE lasku
				SET alatila = 'X'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kukarow['kesken']}'";
	$result = pupe_query($query);

	$query = "	UPDATE tilausrivi
				SET tyyppi = 'D'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND otunnus = '{$kukarow['kesken']}'";
	$result = pupe_query($query);

	$query = "	UPDATE kuka
				SET kesken = '0'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kuka = '{$kukarow['kuka']}'";
	$result = pupe_query($query);

	echo "<font class='message'>".t("Tarjous")." $kukarow[kesken] ".t("hyl�tty")."!</font><br><br>";

	$tee = '';
	$tilausnumero = '';
	$laskurow = '';
	$kukarow['kesken'] = '';
	return true;
}

function hae_extranet_tarjoukset($asiakasid, $toim) {
	global $kukarow, $yhtiorow;

	if ($toim == 'EXTTARJOUS') {
		$where = "  AND lasku.clearing = 'EXTTARJOUS' AND lasku.tila = 'T'";
	}
	else {
		$where = "  AND lasku.clearing = 'EXTENNAKKO' AND lasku.tila = 'N'";
	}

	$query = "  SELECT laskun_lisatiedot.saate,
				lasku.nimi,
				lasku.hinta,
				lasku.olmapvm,
				lasku.tunnus
				FROM lasku
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.liitostunnus = '{$asiakasid}'
				AND lasku.alatila = ''
				{$where}";
	$result = pupe_query($query);

	$haetut_tarjoukset = array();

	while ($haettu_tarjous = mysql_fetch_assoc($result)) {
		$tilrivit = hae_tarjouksen_tilausrivit($haettu_tarjous['tunnus']);
		$haettu_tarjous['hinta'] = 0;
		foreach ($tilrivit as $tilrivi) {
			$haettu_tarjous['hinta'] += (float)$tilrivi['rivihinta'];
		}
		$haetut_tarjoukset[] = $haettu_tarjous;
	}
	return $haetut_tarjoukset;
}

function hae_extranet_tarjous($tunnus, $toim) {
	global $kukarow, $yhtiorow;

	if ($toim == 'EXTTARJOUS') {
		$where = "	AND tila = 'T' AND alatila = ''";
	}
	else if ($toim == 'EXTENNAKKO') {
		$where = "	AND tila = 'N' AND alatila = ''";
	}
	else {
		return false;
	}
	$query = "  SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tunnus}'
				{$where}";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_extranet_kayttajaan_liitetty_asiakas() {
	global $kukarow, $yhtiorow;

	$query = "  SELECT asiakas.*
				FROM asiakas
				JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' AND kuka.kuka = '{$kukarow['kuka']}' )
				WHERE asiakas.yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function nayta_tarjous($valittu_tarjous_tunnus, $toim) {
	global $kukarow, $yhtiorow;

	$tarjous = hae_tarjous($valittu_tarjous_tunnus);
	$kukarow['kesken'] = $valittu_tarjous_tunnus;
	$laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus, $toim);

	if ($toim == "EXTENNAKKO") {
		echo "<br>
			<form action='tuote_selaus_haku.php' method='post'>
			<input type='hidden' name='toim_kutsu' value='$toim'>
			<input type='hidden' name='tilausnumero' value='$valittu_tarjous_tunnus'>
			<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
			<input type='submit' value='".t("Selaa tuotteita")."'>
			</form><br>
			<form action='yhteensopivuus.php' method='post'>
			<input type='hidden' name='toim' value='MP'>
			<input type='hidden' name='toim_kutsu' value='$toim'>
			<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
			<input type='submit' value='".t("MP-Selain")."'>
			</form>
			<form action='yhteensopivuus.php' method='post'>
			<input type='hidden' name='toim' value='MO'>
			<input type='hidden' name='toim_kutsu' value='$toim'>
			<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
			<input type='submit' value='".t("Moposelain")."'>
			</form>
			<form action='yhteensopivuus.php' method='post'>
			<input type='hidden' name='toim' value='MK'>
			<input type='hidden' name='toim_kutsu' value='$toim'>
			<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
			<input type='submit' value='".t("Kelkkaselain")."'>
			</form>
			<form action='yhteensopivuus.php' method='post'>
			<input type='hidden' name='toim' value='MX'>
			<input type='hidden' name='toim_kutsu' value='$toim'>
			<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
			<input type='submit' value='".t("Crossiselain")."'>
			</form>
			<form action='yhteensopivuus.php' method='post'>
			<input type='hidden' name='toim' value='AT'>
			<input type='hidden' name='toim_kutsu' value='$toim'>
			<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
			<input type='submit' value='".t("ATV-Selain")."'>
			</form><br><br>";
	}
	
	echo_tarjouksen_otsikko($tarjous, $toim);

	if ($toim == 'EXTENNAKKO') {
		echo "<font class='message'>".t("Tuotteiden lis�ys")."</font>";

		echo "	<form method='post' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='LISAARIVI'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='otunnus' value='$valittu_tarjous_tunnus'>
				<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
				<input type='hidden' name='action' value='nayta_tarjous'>
				";

		require('syotarivi.inc');
		echo "<br>";
	}

	$params = array("data"			 => $tarjous['tilausrivit'],
		"tarjous_tunnus" => $valittu_tarjous_tunnus,
		"toim"			 => $toim);
	piirra_tarjouksen_tilausrivit($params);
}

function hae_tarjous($valittu_tarjous_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "  SELECT *
				FROM lasku
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$valittu_tarjous_tunnus}'";
	$result = pupe_query($query);

	$tarjous = mysql_fetch_assoc($result);
	$tarjous['tilausrivit'] = hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus);

	return $tarjous;
}

function hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "  SELECT '' as nro,
				'' as kuva,
				tilausrivi.tunnus,
				tilausrivi.perheid as perheid_tunnus,
				tilausrivi.tuoteno,
				tilausrivi.nimitys,
				tilausrivi.var,
				tuote.myyntihinta,
				tilausrivi.varattu as kpl,
				round(tilausrivi.hinta * (1 - ale1 / 100) * (1 - ale2 / 100) * (1 - ale3 / 100), 2) hinta,
				round(tilausrivi.hinta * tilausrivi.varattu * (1 - ale1 / 100) * (1 - ale2 / 100) * (1 - ale3 / 100), 2) rivihinta,
				tilausrivi.alv,
				tuote.tunnus as tuote_tunnus
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
				JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$valittu_tarjous_tunnus}'
				AND tilausrivi.tyyppi in ('L','E','T')
				ORDER BY tilausrivi.perheid, tilausrivi.tunnus";
	$result = pupe_query($query);

	$tilausrivit = array();

	while ($tilausrivi = mysql_fetch_assoc($result)) {
		$query2 = " SELECT selite AS ennakko_pros_a
					FROM tuotteen_avainsanat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tuoteno = '{$tilausrivi['tuoteno']}'
					AND laji = 'parametri_ennakkoale_a'
					AND selite != ''
					ORDER BY ennakko_pros_a DESC
					LIMIT 1";
		$result2 = pupe_query($query2);
		$selite = mysql_fetch_assoc($result2);
		$tilausrivi['parametri_ennakkoale_a'] = $selite['ennakko_pros_a'];
		$tilausrivit[] = $tilausrivi;
	}

	return $tilausrivit;
}

function echo_tarjouksen_otsikko($tarjous, $toim) {
	global $kukarow, $yhtiorow;

	echo "<input type='hidden' id='hylkaa_tarjous_message' value='".t("Oletko varma, ett� haluat hyl�t� tarjouksen?")."'/>";
	echo "<input type='hidden' id='hyvaksy_tarjous_message' value='".t("Oletko varma, ett� haluat hyv�ksy� tarjouksen?")."'/>";

	echo "<a href=$_SERVER[PHP_SELF]?toim={$toim}>".t("Palaa takaisin")."</a>";
	echo "<br>";
	echo "<br>";

	if ($toim == 'EXTTARJOUS') {
		echo "<font class='message'>".t("Tarjouksen tiedot")."</font>";
	}
	else {
		echo "<font class='message'>".t("Ennakon tiedot")."</font>";
	}

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Voimassa")."</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>{$tarjous['nimi']}</td>";
	echo "<td>".tv1dateconv($tarjous['olmapvm'])."</td>";
	echo "</tr>";

	echo "</table>";
	echo "<br>";
}

function piirra_tarjoukset($params) {
	global $kukarow, $yhtiorow;

	$data = $params['data'];
	$toim = $params['toim'];

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Numero")."</th>";
	echo "<th>".t("Saate")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Hinta")."</th>";
	echo "<th>".t("Voimassa")."</th>";
	echo "</tr>";

	foreach ($data as $rivi) {
		echo "<tr>";
		echo "<td><a href='$_SERVER[PHP_SELF]?action=nayta_tarjous&valittu_tarjous_tunnus={$rivi["tunnus"]}&toim={$toim}'>{$rivi["tunnus"]}</a>";
		echo "<td>{$rivi["saate"]}</td>";
		echo "<td>{$rivi["nimi"]}</td>";
		echo "<td style='text-align: right;'>{$rivi["hinta"]}</td>";
		echo "<td>".tv1dateconv($rivi["olmapvm"])."</td>";
		echo "</tr>";
	}

	echo "</table>";
}

function piirra_tarjouksen_tilausrivit($params) {
	global $kukarow, $yhtiorow;

	$tarjous = $params['data'];
	$tunnus = $params['tarjous_tunnus'];
	$toim = $params['toim'];
	$nro = 0;

	echo "<font class='message'>".t("Tilausrivit")."</font>";

	echo "<form id='hyvaksy_hylkaa_formi' method='post' action=''>";
	echo "<input type='hidden' name='action' value='hyvaksy_hylkaa_paivita' />";
	echo "<input type='hidden' name='toim' value='{$toim}' />";
	echo "<input type='hidden' name='valittu_tarjous_tunnus' value='{$tunnus}'/ >";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Numero")."</th>";
	echo "<th>".t("Kuva")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Kpl")."</th>";	
	echo "<th>".t("Yksikk�hinta")."</th>";
	if ($toim == "EXTENNAKKO") {
		echo "<th>".t("Osh")."</th>";	
		echo "<th>".t("Ale %")."</th>";
	}
	echo "<th>".t("Rivihinta")."</th>";
	echo "<th>".t("Alv")."</th>";
	echo "</tr>";

	foreach ($tarjous as $rivi) {

		$liitteet = liite_popup("TH", $rivi['tuote_tunnus']);

		// Katsotaan onko t�m� tuoteperheen is� tai normituote
		if ($rivi['tunnus'] == $rivi['perheid_tunnus'] or $rivi['perheid_tunnus'] == 0) {
			$nro++;
			$class = "tr_border_top";
			$rivinumero = $nro;
		}
		else {
			$class = "";
			$rivinumero = "";
		}

		echo "<tr class='aktiivi'>";
		echo "<td class='{$class}'>{$rivinumero}</a>";
		echo "<td class='{$class}' style='vertical-align: top;'>{$liitteet}</td>";
		echo "<td class='{$class}'>{$rivi["tuoteno"]}</td>";
		echo "<td class='{$class}'>{$rivi["nimitys"]}</td>";
		echo "<td class='{$class}'>";

		if ($toim == "EXTENNAKKO" and $rivinumero != "") {
			if ($rivi['var'] == "O") {
				$kpl = '';
			}
			else {
				$kpl = $rivi['kpl'];
			}
			echo "<input type='text' size='4' name='kappalemaarat[{$rivi['tunnus']}]' value='{$kpl}' />";
		}
		else {
			echo "{$rivi["kpl"]}";
		}
		echo "</td>";

		echo "<td class='{$class}' style='text-align: right;'>".hintapyoristys($rivi["hinta"], $yhtiorow['hintapyoristys'])."</td>";
		if ($toim == "EXTENNAKKO") {
			echo "<td class='{$class}' style='text-align: right;'>".hintapyoristys($rivi["myyntihinta"], $yhtiorow['hintapyoristys'])."</td>";
			echo "<td class='{$class}' style='text-align: right;'>{$rivi["parametri_ennakkoale_a"]}</td>";
		}
		echo "<td class='{$class}' style='text-align: right;'>".hintapyoristys($rivi["rivihinta"], $yhtiorow['hintapyoristys'])."</td>";
		echo "<td class='{$class}' style='text-align: right;'>{$rivi["alv"]}</td>";
		echo "</tr>";
	}

	echo "</table>";
	echo "<br>";
	
	if ($toim == "EXTENNAKKO") {
		echo "<input type='submit' name='paivita' value='".t("P�ivit� rivit")."' />";
		echo "<br>";
		echo "<br>";
	}
	
	echo "<br>";
	echo "<textarea rows='5' cols='90' maxlength='1000' name='syotetyt_lisatiedot' placeholder='".t("Lis�tietoja")."'>";
	echo "</textarea>";
	echo "<br>";
	echo "<br>";
	if ($toim == "EXTENNAKKO") {
		echo "<input type='hidden' name='hyvaksy' value='JOO' />";
		echo "<button type='button' id='hyvaksyennakko'>".t("Hyv�ksy")."</button>";
	}
	else {
		echo "<input type='submit' name='hyvaksy' value='".t("Hyv�ksy")."' onclick='return tarkista(\"hyvaksy\", \"$toim\");'/>";
		echo "<input type='submit' name='hylkaa' value='".t("Hylk��")."' onclick='return tarkista(\"hylkaa\", \"$toim\");'/>";
	}
	echo "</form>";
}

function lisaa_ennakkorivi($params) {

	global $kukarow, $yhtiorow;
	
	$tuoteno = $params['tuoteno'];
	$kpl     = $params['kpl'];
	$otunnus = $params['lasku_tunnus'];
	$toim    = $params['toim'];
	$var     = $params['var'];

	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = '{$tuoteno}'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Tuotetta ei l�ydy")."!<br>";
		return;
	}

	// Tuote l�ytyi
	$trow = mysql_fetch_assoc($result);
	$kukarow["kesken"] = $otunnus;
	$laskurow = hae_lasku($otunnus);
	$laskurow["tila"] = 'N';

	if ($toim == 'EXTENNAKKO' and !empty($params['syotettyhinta'])) {
		$hinta = $params['syotettyhinta'];
		$alennus = 0;
		$netto = 'N';
	}

	$perhekielto = '';
	$perheid = 0;

	$parametrit = array(
		'trow'			 => $trow,
		'laskurow'		 => $laskurow,
		'kpl'			 => $kpl,
		'ale1'           => $alennus,
		'hinta'			 => $hinta,
		'perhekielto'	 => $perhekielto,
		'perheid'		 => $perheid,
		'netto'			 => $netto,
		'var'			 => $var,
		'toim'			 => $toim
	);
	lisaa_rivi($parametrit);

	$lisatyt_rivit = array_merge($lisatyt_rivit1, $lisatyt_rivit2);

	if ($lisatyt_rivit[0] > 0) {
		$valmistettavat .= ",".$lisatyt_rivit[0];

		$query = "	UPDATE tilausrivi
					SET toimitettu	= '$kukarow[kuka]',
					toimitettuaika	= now(),
					keratty			= '$kukarow[kuka]',
					kerattyaika		= now()
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus	= '$lisatyt_rivit[0]'";
		$result = pupe_query($query);
	}
}

require ("footer.inc");
