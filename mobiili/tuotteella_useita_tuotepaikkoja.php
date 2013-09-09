<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();
if (!isset($viivakoodi)) $viivakoodi = "";
if (!isset($tuotenumero)) $tuotenumero = "";
if (!isset($hyllypaikka)) $hyllypaikka = "";
if (!isset($hyllyalue) $hyllyalue = "";
if (!isset($hyllynro) $hyllynro = "";, $hyllyvali, $hyllytaso

$params = array();

# Joku parametri tarvii olla setattu.
if ($hyllypaikka != '' or $tuotenumero != '' or $viivakoodi != '') {

	if (strpos($tuotenumero, "%") !== FALSE) $tuotenumero = urldecode($tuotenumero);

	if ($tuotenumero != '') $params['tuoteno'] = "tilausrivi.tuoteno = '{$tuotenumero}'";
	if ($hyllypaikka != '') $params['hyllypaikka'] = "tilausrivi.otunnus = '{$hyllypaikka}'";

	// Viivakoodi case
	if ($viivakoodi != '') {
		$tuotenumerot = hae_viivakoodilla($viivakoodi);

		if (count($tuotenumerot) > 0) {
			$params['viivakoodi'] = "tuote.tuoteno in ('" . implode($tuotenumerot, "','") . "')";
		}
		else {
			$errors[] = t("Viivakoodilla %s ei löytynyt tuotetta", '', $viivakoodi)."<br />";
			$viivakoodi = "";
		}
	}

	$query_lisa = count($params) > 0 ? " AND ".implode($params, " AND ") : "";

}
else {
	# Tänne ei pitäis päätyä, tarkistetaan jo hyllysiirrot.php:ssä
	echo t("Parametrivirhe");
	echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=hyllysiirrot.php'>";
	exit();
}

echo "viivakoodi: $viivakoodi<br>";
echo "tuoteno: $tuotenumero<br>";
echo "hyllypaikka: $hyllypaikka<br>";

### UI ###
echo "<div class='header'>
	<button onclick='window.location.href=\"hyllysiirrot.php\"' class='button left'><img src='back2.png'></button>
	<h1>",t("USEITA TUOTEPAIKKOJA"), "</h1></div>";

require('inc/footer.inc');
