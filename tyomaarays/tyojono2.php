<?php

require_once('../inc/parametrit.inc');
require_once('inc/tyojono2_functions.inc');
require_once('inc/laite_huolto_functions.inc');

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
	livesearch_asiakashaku();
	exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "KOHDEHAKU") {
	livesearch_kohdehaku();
	exit;
}

if (!isset($tee)) 	$tee = '';
if (!isset($ala_tee)) 	$ala_tee = '';
if (!isset($toim)) 	$toim = '';
if (!isset($lasku_tunnukset)) 	$lasku_tunnukset = '';
if (!isset($toimitusaika_haku)) 	$toimitusaika_haku = '';
if (!isset($laite_tunnus)) 	$laite_tunnus = '';
if (!isset($ajax_request)) 	$ajax_request = '';

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
require_once('tilauskasittely/laskutuspoytakirja_pdf.php');

echo "<font class='head'>".t("Laitehuoltojen työjono").":</font>";
echo "<hr/>";
echo "<br/>";
$js = hae_tyojono2_js();
$css = hae_tyojono2_css();

echo $js;
echo $css;

enable_ajax();

$request = array(
	'ala_tee'			 => $ala_tee,
	'toim'				 => $toim,
	'lasku_tunnukset'	 => $lasku_tunnukset,
	'toimitusaika_haku'	 => $toimitusaika_haku,
	'laite_tunnus'		 => $laite_tunnus,
	'asiakas_tunnus'	 => $asiakas_tunnus,
	'kohde_tunnus'		 => $kohde_tunnus,
);

$request['tyojonot'] = hae_tyojonot($request);

$request['tyostatukset'] = hae_tyostatukset($request);

echo "<div id='tyojono_wrapper'>";

if (is_string($request['lasku_tunnukset'])) {
	$request['lasku_tunnukset'] = explode(',', $lasku_tunnukset);
}

if ($toim == 'TEHDYT_TYOT') {
	if ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' or $request['ala_tee'] == 'tulosta_poikkeamaraportti') {
		$pdf_tiedostot = ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? PDF\Tarkastuspoytakirja\hae_tarkastuspoytakirjat($request['lasku_tunnukset']) : PDF\Poikkeamaraportti\hae_poikkeamaraportit($request['lasku_tunnukset']));

		foreach ($pdf_tiedostot as $pdf_tiedosto) {
			if (!empty($pdf_tiedosto)) {
				echo_tallennus_formi($pdf_tiedosto, ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? t("Tarkastuspöytakirja") : t("Poikkeamaraportti")), 'pdf');
			}
		}
	}
	if ($request['ala_tee'] == 'tulosta_laskutuspoytakirja') {
		$pdf_tiedosto = \PDF\Laskutuspoytakirja\hae_laskutuspoytakirja($request['lasku_tunnukset']);

		if (!empty($pdf_tiedosto)) {
			echo_tallennus_formi($pdf_tiedosto, 'Laskutuspoytakirja', 'pdf');
		}
		else {
			echo t("Laskutuspoytäkirjan generointi epäonnistui");
		}
	}
}
else {
	if ($request['ala_tee'] == 'merkkaa_tehdyksi') {
		merkkaa_tyomaarays_tehdyksi($request);
	}

	if ($request['ala_tee'] == 'merkkaa_kadonneeksi') {
		merkkaa_laite_kadonneeksi($request);
	}

	if ($request['ala_tee'] == 'tulosta_tyolista') {

		$multi = false;

		if( is_array($lasku_tunnukset) ){
			foreach( $lasku_tunnukset as $tunnus ){
				$tunnus = explode(',', $tunnus);
				$tunnukset[] = $tunnus;
			}
			$lasku_tunnukset = $tunnukset;
			$multi = true;
		}
		else{
			$lasku_tunnukset = explode(',', $lasku_tunnukset);
		}


		$pdf_tiedosto = \PDF\Tyolista\hae_tyolistat($lasku_tunnukset, $multi );
		if (!empty($pdf_tiedosto)) {

			if( strpos($pdf_tiedosto, '_') ){
				preg_match('~_(.*?).pdf~', $pdf_tiedosto, $osat);
				$number = '_'.$osat[1];
				$uusi_nimi = 'Tyolista';
			}
			else{
				$number = null;
				$uusi_nimi = 'Kaikki_tyolistat';
			}

			echo_tallennus_formi($pdf_tiedosto, $uusi_nimi, 'pdf', $number);
			aseta_tyomaaraysten_status($request['lasku_tunnukset'], 'T');
		}
		else {
			echo t("Työmääräysten generointi epäonnistui");
		}
	}
}

//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
unset($request['lasku_tunnukset']);

echo_tyojono_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

$request['tyomaaraykset'] = hae_tyomaaraykset($request);
$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);
echo_tyomaaraykset_table($request);

echo "</div>";

require ("inc/footer.inc");
