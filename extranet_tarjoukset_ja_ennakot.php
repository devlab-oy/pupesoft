<?php

require ("parametrit.inc");
require ("Validation.php");
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
			ok = confirm($('#hyvaksy_tarjous').val());
		}
		else if (type == "hylkaa" && toim == "EXTTARJOUS") {
			ok = confirm($('#hylkaa_tarjous_message').val());
		}
		else if (type == "hyvaksy" && toim == "EXTENNAKKO") {
			ok = confirm($('#hyvaksy_ennakko').val());
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
);

$request['kayttajaan_liitetty_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();

if ($tee == "LISAARIVI") {

	$query = "	SELECT *
				FROM tuote
				WHERE tuoteno='$tuoteno' AND yhtio='$kukarow[yhtio]'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		//Tuote löytyi
		$trow = mysql_fetch_assoc($result);

		$kukarow["kesken"] = $otunnus;

		$laskurow = hae_lasku($otunnus);
		// Nollataan hinta kun kyseessä on asiakkaan ext-ennakkotilaukseen lisäämä rivi
		if ($toim == 'EXTENNAKKO') {
			$query = "  SELECT selite AS ennakko_pros_a
						FROM tuotteen_avainsanat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$tuoteno}'
						AND laji = 'parametri_ennakkoale_a'
						AND selite != ''";
			$result = pupe_query($query);
			$tuotteen_hinta = mysql_fetch_assoc($result);
			$hinta = $trow['myyntihinta'] * (1 - ($tuotteen_hinta['ennakko_pros_a'] / 100));
			$laskurow["tila"] = 'N';
		}
		else {
			$laskurow["tila"] = 'T';
		}
		$perhekielto = '';
		$perheid = 0;
		$trow = hae_tuote($tuoteno);
		$parametrit = array(
			'trow'			 => $trow,
			'laskurow'		 => $laskurow,
			'kpl'			 => ($kpl),
			'netto'			 => $netto,
			'hinta'			 => $hinta,
			'perhekielto'	 => $perhekielto,
			'perheid'		 => $perheid,
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
	else {
		echo t("Tuotetta ei löydy")."!<br>";
	}

	$tee = "VALMISTA";
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

if ($request['action'] == 'nayta_tarjous') {
	
	nayta_tarjous($request['valittu_tarjous_tunnus'], $request['toim']);
}
elseif ($request['action'] == 'hyvaksy_tai_hylkaa') {

	if (isset($request['hyvaksy'])) {
		if ($toim == 'EXTTARJOUS') {
			$onnistuiko_toiminto = hyvaksy_tarjous($request['valittu_tarjous_tunnus'], $syotetyt_lisatiedot);
		}
		else {
			$onnistuiko_toiminto = hyvaksy_ennakko($request['valittu_tarjous_tunnus'], $syotetyt_lisatiedot, $kappalemaarat);
		}
	}
	else {
		$onnistuiko_toiminto = hylkaa($request['valittu_tarjous_tunnus']);
	}

	if (!$onnistuiko_toiminto) {
		echo "<font class='error'>".t("Toiminto epäonnistui")."</font>";
	}

	echo "<br>";
	echo "<br>";

	echo "	<script>
			setTimeout(\"parent.location.href='$palvelin2'\", 2000);
			</script>";
	exit;
}
else {
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
}

function hyvaksy_ennakko($valittu_tarjous_tunnus, $syotetyt_lisatiedot, $kappalemaarat) {
	global $kukarow, $yhtiorow;

	foreach ($kappalemaarat as &$kappalemaara_temp) {
		$kappalemaara_temp = round(str_replace(",", ".", pupesoft_cleanstring($kappalemaara_temp)), 2);
	}


	$validations = array(
		'syotetyt_lisatiedot' => 'kirjain_numero',
	);

	$validator = new FormValidator($validations);

	if ($validator->validate(array('syotetyt_lisatiedot' => $syotetyt_lisatiedot))) {

		foreach ($kappalemaarat as $kappalemaara) {
			if (!FormValidator::validateItem($kappalemaara, "2digitopt")) {
				return false;
			}
		}
		// Haetaan kannasta tilausrivit
		$muokkaamaton_ennakko = hae_tarjous($valittu_tarjous_tunnus);

		foreach ($kappalemaarat as $key => $value) {
			// Etsitään tilausrivitunnuksen perusteella tuotteen kannassa oleva kappalemäärä
			$loytynyt_tilausrivi = search_array_key_for_value_recursive($muokkaamaton_ennakko['tilausrivit'], 'tunnus', $key);
			// Tarkistetaan löytyikö tilausrivi
			if (!empty($loytynyt_tilausrivi[0])) {
				// Muutetaan kappalemäärä oikeaan muotoon
				$kplmaara = round(str_replace(",", ".", pupesoft_cleanstring($loytynyt_tilausrivi[0]['kpl'])), 2);
				// Tarkistetaan onko kappalemäärä säilynyt muuttamattomana, jos ei muutoksia niin toimenpiteitä riville ei vaadita
				if ($kplmaara != $value) {
					// Onko tuote tuoteperheen isätuote tai normaali tilausrivi JA uusi syötetty kappalemäärä 0
					if (($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus'] or $loytynyt_tilausrivi[0]['perheid_tunnus'] == 0) and $value == 0.00) {
						// Tuoteperheen kappalemäärän nollaus tilauksesta
						if ($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus']) {
							$andy = "   AND perheid = '{$loytynyt_tilausrivi[0]['tunnus']}'";
						}
						// Normaalituotteen kappalemäärän nollaus tilauksesta
						else {
							$andy = "   AND tunnus = '{$loytynyt_tilausrivi[0]['tunnus']}'";
						}

						$query = "  UPDATE tilausrivi
										SET tilkpl = 0,
										kpl = 0,
										jt = 0,
										varattu = 0
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND otunnus = '{$valittu_tarjous_tunnus}'
										{$andy}
										";
						pupe_query($query);
					}
					// Kun kappalemäärää muutetaan muuksi kuin nollaksi, poistetaan tilausrivi tilauksesta ja lisätään uudestaan uudella määrällä
					else {
						$kukarow['kesken'] = $valittu_tarjous_tunnus;
						// Tuoteperheen tapauksessa rivin poistoa varten
						if ($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus']) {
							$andy = "   AND perheid = '{$loytynyt_tilausrivi[0]['tunnus']}'";
						}
						// Normaalituotteen tapauksessa rivin poistoa varten
						else {
							$andy = "   AND tunnus = '{$loytynyt_tilausrivi[0]['tunnus']}'";
						}
						// Rivin poisto
						$query = "  DELETE
									FROM tilausrivi
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$valittu_tarjous_tunnus}'
									{$andy}
									";

						pupe_query($query);


						// Rivin uudelleenlisäys
						$trow = hae_tuote($loytynyt_tilausrivi[0]['tuoteno']);
						$laskurow = $muokkaamaton_ennakko;
						$parametrit = array(
							'trow'		 => $trow,
							'laskurow'	 => $laskurow,
							'kpl'		 => $value,
							'netto'		 => "N",
							'rivitunnus' => $loytynyt_tilausrivi[0]['tunnus'],
							'tuoteno'	 => $loytynyt_tilausrivi[0]['tuoteno'],
							'hinta'		 => 0,
						);
						lisaa_rivi($parametrit);
					}
				}
			}
		}
		// Päivitetään käyttäjän lisäämät kommentit ja vaihdetaan tila/alatila Ennakko/Lepäämässä
		$query = "	UPDATE lasku
					SET sisviesti1='{$syotetyt_lisatiedot}',
					tila='E',
					alatila='A'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$valittu_tarjous_tunnus}'";
		pupe_query($query);
		return true;
	}
	else {
		return false;
	}
}

function hyvaksy_tarjous($valittu_tarjous_tunnus, $syotetyt_lisatiedot) {
	global $kukarow, $yhtiorow;
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

function hylkaa($valittu_tarjous_tunnus) {
	global $kukarow, $yhtiorow;

	$kukarow['kesken'] = $valittu_tarjous_tunnus;
	$laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus);

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

	echo "<font class='message'>".t("Tarjous")." $kukarow[kesken] ".t("hylätty")."!</font><br><br>";

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

	echo_tarjouksen_otsikko($tarjous, $toim);

	if ($toim == 'EXTENNAKKO') {
		echo "	<form method='post' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='LISAARIVI'>
						<input type='hidden' name='toim'  value='$toim'>
						<input type='hidden' name='otunnus'  value='$valittu_tarjous_tunnus'>";
		echo "<font class='message'>".t("Tuotteiden lisäys")."</font>";

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
				tilausrivi.tilkpl as kpl,
				IF(tilausrivi.alv != 0, ( (1 + ( tilausrivi.alv / 100)	 ) * (tilausrivi.tilkpl * tilausrivi.hinta ) ), tilausrivi.tilkpl * tilausrivi.hinta) AS rivihinta,
				tilausrivi.alv,
				tuote.tunnus as tuote_tunnus
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$valittu_tarjous_tunnus}'";
	$result = pupe_query($query);

	$tilausrivit = array();

	while ($tilausrivi = mysql_fetch_assoc($result)) {
		$tilausrivit[] = $tilausrivi;
	}

	return $tilausrivit;
}

function echo_tarjouksen_otsikko($tarjous, $toim) {
	global $kukarow, $yhtiorow;

	echo "<input type='hidden' id='hylkaa_tarjous_message' value='".t("Oletko varma, että haluat hylätä tarjouksen?")."'/>";
	echo "<input type='hidden' id='hyvaksy_ennakko' value='".t("Oletko varma, että haluat hyväksyä ennakon?")."'/>";
	echo "<input type='hidden' id='hyvaksy_tarjous' value='".t("Oletko varma, että haluat hyväksyä tarjouksen?")."'/>";

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

	echo "<form method='post' action=''>";
	echo "<input type='hidden' name='action' value='hyvaksy_tai_hylkaa' />";
	echo "<input type='hidden' name='toim' value='{$toim}' />";
	echo "<input type='hidden' name='valittu_tarjous_tunnus' value='{$tunnus}'/ >";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Numero")."</th>";
	echo "<th>".t("Kuva")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Kpl")."</th>";
	echo "<th>".t("Rivihinta")."</th>";
	echo "<th>".t("Alv")."</th>";
	echo "</tr>";

	foreach ($tarjous as $rivi) {

		$liitteet = liite_popup("TH", $rivi['tuote_tunnus']);

		// Katsotaan onko tämä tuoteperheen isä tai normituote
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
			echo "<input type='text' size='4' name='kappalemaarat[{$rivi['tunnus']}]' value='{$rivi["kpl"]}' />";
		}
		else {
			echo "{$rivi["kpl"]}";
		}
		echo "</td>";

		echo "<td class='{$class}' style='text-align: right;'>".hintapyoristys($rivi["rivihinta"], $yhtiorow['hintapyoristys'])."</td>";
		echo "<td class='{$class}' style='text-align: right;'>{$rivi["alv"]}</td>";
		echo "</tr>";
	}

	echo "</table>";

	echo "<br>";
	echo "<textarea rows='5' cols='90' maxlength='1000' name='syotetyt_lisatiedot' placeholder='".t("Lisätietoja")."'>";
	echo "</textarea>";
	echo "<br>";
	echo "<br>";

	echo "<input type='submit' name='hyvaksy' value='".t("Hyväksy")."' onclick='return tarkista(\"hyvaksy\", \"$toim\");'/>";
	if ($toim == "EXTTARJOUS") {
		echo "<input type='submit' name='hylkaa' value='".t("Hylkää")."' onclick='return tarkista(\"hylkaa\", \"$toim\");'/>";
	}
	echo "</form>";
}
require ("footer.inc");
