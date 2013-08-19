<?php

require_once('../inc/parametrit.inc');
require_once('inc/tyojono2_functions.inc');
require_once('validation/Validation.php');

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	exit;
}

//AJAX requestit tänne
if ($ajax_request) {
	exit;
}

require_once('tilauskasittely/tarkastuspoytakirja_pdf.php');
require_once('tilauskasittely/poikkeamaraportti_pdf.php');

echo "<font class='head'>".t("Työjono2").":</font>";
echo "<hr/>";
echo "<br/>";
$js = hae_tyojono2_js();
$css = hae_tyojono2_css();

echo $js;
echo $css;

$request = array(
	'ala_tee'			 => $ala_tee,
	'toim'				 => $toim,
	'lasku_tunnukset'	 => $lasku_tunnukset,
	'toimitusaika_haku'	 => $toimitusaika_haku,
);

$validations = array(
	'ala_tee'			 => 'kirjain_numero',
	'toim'				 => 'kirjain_numero',
	'toimitusaika_haku'	 => 'numero',
);
$validator = new FormValidator($validations);

if (!$validator->validate($request)) {
	//jos validationit ei mene läpi niin todennäköisesti joku yrittää hakkeroida
	exit;
}

$request['tyojonot'] = hae_tyojonot($request);

$request['tyostatukset'] = hae_tyostatukset($request);

echo "<div id='tyojono_wrapper'>";

if ($toim == 'TEHDYT_TYOT') {
	if ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' or $request['ala_tee'] == 'tulosta_poikkeamaraportti') {
		$pdf_tiedostot = ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? PDF\Tarkastuspoytakirja\hae_tarkastuspoytakirjat($request['lasku_tunnukset']) : PDF\Poikkeamaraportti\hae_poikkeamaraportit($request['lasku_tunnukset']));

		foreach ($pdf_tiedostot as $pdf_tiedosto) {
			if (!empty($pdf_tiedosto)) {
				echo_tallennus_formi($pdf_tiedosto, ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? t("Tarkastuspöytakirja") : t("Poikkeamaraportti")), 'pdf');
			}
		}
		//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
		unset($request['lasku_tunnukset']);
	}

	$request['tyomaaraykset'] = hae_tyomaaraykset($request);
	$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);
	echo_tyomaaraykset_table($request);
}
else {
	if ($request['ala_tee'] == 'merkkaa_tehdyksi') {
		merkkaa_tyomaarays_tehdyksi($request);
		//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
		unset($request['lasku_tunnukset']);
	}

	if ($request['ala_tee'] == 'tulosta_tyolista') {
		$request['lasku_tunnukset'] = explode(',', $lasku_tunnukset);
		$tyomaaraykset = hae_tyomaaraykset($request);

		$header_values = array(
			'kohde_nimi'		 => array(
				'header' => t('Kohteen nimi'),
				'order'	 => 1
			),
			'paikka_nimi'		 => array(
				'header' => t('Paikan nimi'),
				'order'	 => 10
			),
			'paikka_olosuhde'	 => array(
				'header' => t('Olosuhde'),
				'order'	 => 20
			),
			'tuoteno'			 => array(
				'header' => t('Tuotenumero'),
				'order'	 => 9
			),
			'oma_numero'		 => array(
				'header' => t('Oma numero'),
				'order'	 => 0
			),
			'laite_sijainti'	 => array(
				'header' => t('Laitteen tarkempi sijainti'),
				'order'	 => 11
			),
			'toimaika'			 => array(
				'header' => t('Huoltoaika'),
				'order'	 => 30
			),
			'tyojono'			 => array(
				'header' => t('Työjono'),
				'order'	 => 40
			),
			'tyostatus'			 => array(
				'header' => t('Työstatus'),
				'order'	 => 50
			),
		);

		$force_to_string = array(
			'tuoteno'
		);

		$sulje_pois = array(
			'lasku_tunnus',
			'asiakas_ytunnus',
			'asiakas_nimi',
			'tyojonokoodi',
			'tyostatusvari',
			'tyostatus_koodi',
		);

		if (!empty($tyomaaraykset)) {
			$excel_filepath = generoi_excel_tiedosto($tyomaaraykset, $header_values, $force_to_string, $sulje_pois);
			echo_tallennus_formi($excel_filepath, t("Työlista"), 'xlsx');

			aseta_tyomaaraysten_status($request['lasku_tunnukset'], 'T');
			unset($request['lasku_tunnukset']);
		}
		else {
			echo t("Työmääräysten generointi epäonnistui");
		}
	}

	$request['tyomaaraykset'] = hae_tyomaaraykset($request);

	$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);

	echo_tyomaaraykset_table($request);
}

echo "</div>";

require ("inc/footer.inc");
