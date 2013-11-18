<?php

//Kun tehdyt_työt näkymästä tulostetaan tarkastuspyötäkirjoja tai poikkeamaraportteja,
//tulee requestin mukana toim-muuttuja.
//Koska kyseinen muuttuja ei ole tässä tiedostossa käytössä asetetaan se tyhjäksi,
//koska muuten se osuisi tämän tiedoston oikeustarkistuksiin.
if (isset($_REQUEST['toim'])) {
	$_REQUEST['toim'] = '';
}

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/inc/parametrit.inc')) {
	require_once($filepath . '/inc/parametrit.inc');
}
else {
	require_once($filepath . '/parametrit.inc');
}

if (!empty($kukarow['extranet'])) {
	pupesoft_require('inc/tyojono2_functions.inc');
	pupesoft_require('tilauskasittely/tarkastuspoytakirja_pdf.php');
	pupesoft_require('tilauskasittely/poikkeamaraportti_pdf.php');
}

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	else {
		echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
	}
	exit;
}

//Tänne tämän tiedoston ajax requestit
if ($ajax_request) {
	exit;
}

echo "<font class='head'>".t("Laitehallinta")."</font><hr>";
?>
<style>
	.paikka_tr_hidden {
		display: none;
	}

	.laitteet_table_hidden {
		display: none;
	}

	.paikka_tr_not_hidden {
	}

	.laiteet_table_not_hidden {
	}
</style>
<script src='<?php echo $palvelin2 ?>js/asiakas/asiakkaan_laite_puu.js'></script>
<script src='<?php echo $palvelin2 ?>js/tyomaarays/tyojono2.js'></script>
<script>
	$(document).ready(function() {
		$('#laite_puu_wrapper').laitePuuPlugin();
		var laite_puu_plugin = $('#laite_puu_wrapper').data('laitePuuPlugin');
		laite_puu_plugin.bind_kohde_tr_click();
		laite_puu_plugin.bind_paikka_tr_click();

		laite_puu_plugin.bind_poista_kohde_button();
		laite_puu_plugin.bind_poista_paikka_button();
		laite_puu_plugin.bind_poista_laite_button();
		laite_puu_plugin.bind_aineisto_submit_button_click();
	});
</script>
<?php

$request = array(
	'ytunnus'			 => $ytunnus,
	'asiakasid'			 => $asiakasid,
	'asiakas_tunnus'	 => $asiakas_tunnus,
	'ala_tee'			 => $ala_tee,
	'lasku_tunnukset'	 => $lasku_tunnukset,
);

$request['laitteen_tilat'] = hae_laitteen_tilat();

if (($tee == 'hae_asiakas' or ($tee == '' and !empty($valitse_asiakas))) and empty($kukarow['extranet'])) {
	$request['haettu_asiakas'] = hae_asiakas($request);
}
else {
	$request['haettu_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();
	$request['liitostunnus'] = $request['haettu_asiakas']['tunnus'];
	$request['toim'] = 'TEHDYT_TYOT';
}

if (!empty($request['haettu_asiakas'])) {
	echo "<font class='head'>{$request['haettu_asiakas']['nimi']}</font>";
	echo "<br/>";
	echo "<br/>";
}

if (!empty($request['haettu_asiakas'])) {
	$asiakkaan_kohteet = hae_asiakkaan_kohteet_joissa_laitteita($request);

	$pdf_tiedostot = array();
	if ($request['ala_tee'] == 'tulosta_kalustoraportti') {
		$asiakkaan_kohteet['yhtio'] = $yhtiorow;
		$asiakkaan_kohteet['asiakas'] = $request['haettu_asiakas'];
		$asiakkaan_kohteet['logo'] = base64_encode(hae_yhtion_lasku_logo());
		$request['pdf_filepath'] = tulosta_kalustoraportti($asiakkaan_kohteet);
	}
	else if ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' or $request['ala_tee'] == 'tulosta_poikkeamaraportti') {
		$pdf_tiedostot = ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? PDF\Tarkastuspoytakirja\hae_tarkastuspoytakirjat($request['lasku_tunnukset']) : PDF\Poikkeamaraportti\hae_poikkeamaraportit($request['lasku_tunnukset']));
		//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
		unset($request['lasku_tunnukset']);
	}

	if (!empty($kukarow['extranet'])) {
		$js = hae_tyojono2_js();
		$css = hae_tyojono2_css();

		echo $js;
		echo $css;

		$request['tyojonot'] = hae_tyojonot($request);
		$request['tyostatukset'] = hae_tyostatukset($request);

		$request['tyomaaraykset'] = hae_tyomaaraykset($request);
		$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);

		echo "<div id='tyojono_wrapper'>";
		//Tarkastuspöytäkirjan ja poikkeamaraportin tulostus logiikka suoritetaan
		//tyojono_wrapper divin sisällä, jotta työjono leiskaan liitetty js-toiminnallisuus skulaa
		foreach ($pdf_tiedostot as $pdf_tiedosto) {
			if (!empty($pdf_tiedosto)) {
				echo_tallennus_formi($pdf_tiedosto, ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? t("Tarkastuspöytakirja") : t("Poikkeamaraportti")), 'pdf');
			}
		}
		echo_tyomaaraykset_table($request);
		echo "</div>";
		echo "<br/>";
		echo "<br/>";
	}

	echo "<div id='laite_puu_wrapper'>";
	echo_kohteet_table($asiakkaan_kohteet, $request);
	echo "</div>";
}

if (empty($kukarow['extranet'])) {
	echo_kayttoliittyma($request);
}

pupesoft_require("inc/footer.inc");

function echo_kayttoliittyma($request = array()) {
	global $kukarow, $yhtiorow, $palvelin2;

	echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
	echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />";
	echo "<input type='hidden' id='oletko_varma_confirm_message' value='".t("Oletko varma")."' />";
	echo "<input type='hidden' id='poisto_epaonnistui_message' value='".t("Poisto epäonnistui")."' />";

	echo "<form method='POST' action='' name='asiakas_haku'>";

	echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
	echo "<input type='text' id='ytunnus' name='ytunnus' />";
	echo "<input type='submit' value='".t("Hae")."' />";

	echo "</form>";
}

function echo_kalustoraportti_form($haettu_asiakas) {
	global $kukarow, $yhtiorow;

	echo "<form method='POST' action='' name='tulosta_kalustoraportti'>";
	echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
	echo "<input type='hidden' id='ala_tee' name='ala_tee' value='tulosta_kalustoraportti' />";
	echo "<input type='hidden' id='asiakasid' name='asiakasid' value='{$haettu_asiakas['tunnus']}' />";
	echo "<input type='submit' value='".t("Tulosta kalustoraportti")."' />";
	echo "</form>";
}

function tulosta_kalustoraportti($kohteet) {
	global $kukarow, $yhtiorow;

	$filepath = kirjoita_json_tiedosto($kohteet, 'Kalustoraportti');
	return aja_ruby($filepath, 'kalustoraportti');
}

function echo_kohteet_table($asiakkaan_kohteet = array(), $request = array()) {
	global $palvelin2, $lopetus, $kukarow;

	$haettu_asiakas = $request['haettu_asiakas'];

	$lopetus = "{$palvelin2}asiakkaan_laite_hallinta.php////tee=hae_asiakas//asiakasid={$haettu_asiakas['tunnus']}";

	echo_kalustoraportti_form($haettu_asiakas);
	echo "<br/>";
	echo "<br/>";

	if (!empty($request['pdf_filepath'])) {
		$tiedostot = explode(' ', $request['pdf_filepath']);
		foreach ($tiedostot as $tiedosto) {
			echo_tallennus_formi($tiedosto, t("Kalustoraportti"), 'pdf');
		}
	}

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Kohteen nimi")."</th>";
	echo "<th>".t("Paikan nimi")."</th>";
	echo "<th>".t("Laitteet")."</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>";
	if (empty($kukarow['extranet'])) {
		echo "<a href='yllapito.php?toim=kohde&uusi=1&lopetus={$lopetus}&valittu_asiakas={$haettu_asiakas['tunnus']}'><button>".t("Luo uusi kohde")."</button></a>";
	}
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";

	if (!empty($asiakkaan_kohteet['kohteet'])) {
		foreach ($asiakkaan_kohteet['kohteet'] as $kohde_index => $kohde) {
			echo_kohde_tr($kohde_index, $kohde);
		}
	}

	echo "</table>";
}

function echo_kohde_tr($kohde_index, $kohde) {
	global $palvelin2, $lopetus, $kukarow;

	echo "<tr class='kohde_tr hidden'>";

	echo "<td>";
	if (empty($kukarow['extranet'])) {
		echo "<button class='poista_kohde'>".t("Poista kohde")."</button>";
	}
	echo "&nbsp";
	echo "<input type='hidden' class='kohde_tunnus' value='{$kohde_index}' />";
	if (empty($kukarow['extranet'])) {
		echo "<a href='yllapito.php?toim=kohde&lopetus={$lopetus}&tunnus={$kohde_index}'>".$kohde['kohde_nimi']."</a>";
	}
	else {
		echo $kohde['kohde_nimi'];
	}
	echo "&nbsp";
	echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
	echo "</td>";

	echo "<td>";
	//paikan nimi tyhjä
	echo "</td>";

	echo "<td>";
	//laitteet tyhjä
	echo "</td>";

	echo "</tr>";
	echo_paikka_tr($kohde_index, $kohde['paikat']);
}

function echo_paikka_tr($kohde_index, $paikat = array()) {
	global $palvelin2, $lopetus, $kukarow;

	echo "<tr class='paikka_tr_hidden paikat_{$kohde_index}'>";
	echo "<td>";
	if (empty($kukarow['extranet'])) {
		echo "<a href='yllapito.php?toim=paikka&uusi=1&&lopetus={$lopetus}&valittu_kohde={$kohde_index}'><button>".t("Luo kohteelle uusi paikka")."</button></a>";
	}
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";
	if (!empty($paikat)) {
		foreach ($paikat as $paikka_index => $paikka) {
			echo "<tr class='paikat_tr paikka_tr_hidden paikat_{$kohde_index}'>";

			echo "<td>";
			echo "<input type='hidden' class='paikka_tunnus' value='{$paikka_index}' />";
			echo "</td>";

			echo "<td>";
			if (empty($kukarow['extranet'])) {
				echo "<a href='yllapito.php?toim=paikka&lopetus={$lopetus}&tunnus={$paikka_index}'>{$paikka['paikka_nimi']}</a>";
			}
			else {
				echo $paikka['paikka_nimi'];
			}
			echo "&nbsp";
			echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
			echo "<br/>";
			if (empty($kukarow['extranet'])) {
				echo "<button class='poista_paikka'>".t("Poista paikka")."</button>";
			}
			echo "</td>";

			echo "<td>";
			if (empty($kukarow['extranet'])) {
				echo "<a href='yllapito.php?toim=laite&uusi=1&lopetus={$lopetus}&valittu_paikka={$paikka_index}'><button>".t("Luo paikkaan uusi laite")."</button></a>";
			}
			echo "<br/>";
			echo_laitteet_table($paikka['laitteet']);
			echo "</td>";

			echo "</tr>";
		}
	}
}

function echo_laitteet_table($laitteet = array()) {
	global $palvelin2, $lopetus, $kukarow;

	echo "<table class='laitteet_table_hidden'>";
	echo "<tr>";
	echo "<th>".t("Tuotenumero")."</th>";
	echo "<th>".t("Tuotteen nimi")."</th>";
	echo "<th>".t("Sijainti")."</th>";
	echo "<th>".t("Tila")."</th>";
	if (empty($kukarow['extranet'])) {
		echo "<th>".t("Kopioi")."</th>";
		echo "<th>".t("Poista")."</th>";
	}
	echo "</tr>";

	foreach ($laitteet as $laite) {
		echo "<tr>";

		echo "<td>";
		if (empty($kukarow['extranet'])) {
			echo "<a href='yllapito.php?toim=laite&asiakas_tunnus={$laite['asiakas_tunnus']}&lopetus={$lopetus}&tunnus={$laite['laite_tunnus']}'>{$laite['tuoteno']}</a>";
		}
		else {
			echo $laite['tuoteno'];
		}
		echo "</td>";

		echo "<td>";
		echo $laite['tuote_nimi'];
		echo "</td>";

		echo "<td>";
		echo $laite['sijainti'];
		echo "</td>";

		echo "<td>";
		echo $laite['tilan_selite'];
		echo "</td>";

		if (empty($kukarow['extranet'])) {
			echo "<td>";
			if (!empty($laite['laite_tunnus'])) {
				echo "<button>";
				echo "<a href='yllapito.php?toim=laite&kopioi_rivi=on&asiakas_tunnus={$laite['asiakas_tunnus']}&lopetus={$lopetus}&tunnus={$laite['laite_tunnus']}'>".t('Kopioi laite')."</a>";
				echo "</button>";
			}
			echo "</td>";
			
			echo "<td>";
			if (!empty($laite['laite_tunnus'])) {
				echo "<input type='hidden' class='laite_tunnus' value='{$laite['laite_tunnus']}' />";
				echo "<button class='poista_laite'>".t("Poista laite")."</button>";
			}
			echo "</td>";
		}

		echo "</tr>";
	}
	echo "</table>";
}

function hae_laitteen_tilat() {
	global $kukarow, $yhtiorow;

	$result = t_avainsana('LAITE_TILA');
	$tilat = array();
	while ($tila = mysql_fetch_assoc($result)) {
		$tilat[] = $tila;
	}

	return $tilat;
}

function hae_asiakas($request) {
	global $kukarow, $yhtiorow;

	if ($request['ytunnus'] != '' or $request['asiakasid'] != '') {
		$ytunnus = $request['ytunnus'];
		$asiakasid = $request['asiakasid'];
		require("inc/asiakashaku.inc");
	}

	return $asiakasrow;
}

function hae_asiakkaan_kohteet_joissa_laitteita($request) {
	global $kukarow;

	$select = "";
	$join = "";
	$group = "";
	if (!empty($request['ala_tee'])) {
		$select = "ta1.selite as sammutin_tyyppi,
					ta2.selite as sammutin_koko,
					huoltosykli.huoltovali as huoltovali,";

		$join = "	LEFT JOIN tuotteen_avainsanat ta1
					ON ( ta1.yhtio = tuote.yhtio
						AND ta1.tuoteno = tuote.tuoteno
						AND ta1.laji = 'sammutin_tyyppi' )
					LEFT JOIN tuotteen_avainsanat ta2
					ON ( ta2.yhtio = tuote.yhtio
						AND ta2.tuoteno = tuote.tuoteno
						AND ta2.laji = 'sammutin_koko' )
					LEFT JOIN huoltosykli
					ON ( huoltosykli.yhtio = laite.yhtio
						AND huoltosykli.tyyppi = ta1.selite
						AND huoltosykli.koko = ta2.selite
						AND huoltosykli.olosuhde = paikka.olosuhde )";

		//groupataan laite_tunnuksen mukaan koska laitteella voi olla monta huoltosykliä
		$group = "GROUP BY laite.tunnus";
	}

	$query = "	SELECT asiakas.tunnus as asiakas_tunnus,
				kohde.tunnus as kohde_tunnus,
				kohde.nimi as kohde_nimi,
				paikka.tunnus as paikka_tunnus,
				paikka.nimi as paikka_nimi,
				tuote.nimitys as tuote_nimi,
				laite.tunnus as laite_tunnus,
				{$select}
				laite.*
				FROM kohde
				JOIN asiakas
				ON ( asiakas.yhtio = kohde.yhtio
					AND asiakas.tunnus = kohde.asiakas )
				LEFT JOIN paikka
				ON ( paikka.yhtio = kohde.yhtio
					AND paikka.kohde = kohde.tunnus )
				LEFT JOIN laite
				ON ( laite.yhtio = paikka.yhtio
					AND laite.paikka = paikka.tunnus )
				LEFT JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = laite.tuoteno )
				{$join}
				WHERE kohde.yhtio = '{$kukarow['yhtio']}'
				AND kohde.asiakas = {$request['haettu_asiakas']['tunnus']}
				{$group}";
	$result = pupe_query($query);

	$asiakkaan_kohteet = array();
	while ($kohde = mysql_fetch_assoc($result)) {
		$laitteen_tila = search_array_key_for_value_recursive($request['laitteen_tilat'], 'selite', $kohde['tila']);
		//key:llä on tarkoitus löytyä vain yksi resultti, siksi voidaan viitata indeksillä.
		$kohde['tilan_selite'] = $laitteen_tila[0]['selitetark'];
		$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_nimi'] = $kohde['kohde_nimi'];
		$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_tunnus'] = $kohde['kohde_tunnus'];

		if (!empty($kohde['paikka_tunnus'])) {
			$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['laitteet'][] = $kohde;
			$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_nimi'] = $kohde['paikka_nimi'];
		}
	}

	return $asiakkaan_kohteet;
}
