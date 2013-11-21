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
require_once('tilauskasittely/tyolista_pdf.php');

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
		$pdf_tiedosto = \PDF\Tyolista\hae_tyolistat($request['lasku_tunnukset']);
		
		if (!empty($pdf_tiedosto)) {
			echo_tallennus_formi($pdf_tiedosto, t("Työlista"), 'pdf');

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
