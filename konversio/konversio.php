<?php

ini_set("memory_limit", "5G");

require("../inc/parametrit.inc");
require_once('TuoteCSVDumper.php');
require_once('AsiakasCSVDumper.php');
require_once('AsiakasalennusCSVDumper.php');
require_once('YhteyshenkiloCSVDumper.php');
require_once('KohdeCSVDumper.php');
require_once('PaikkaCSVDumper.php');
require_once('LaiteCSVDumper.php');
require_once('TuotteenavainsanaLaiteCSVDumper.php');
require_once('TuotteenavainsanaToimenpideCSVDumper.php');
require_once('TuoteryhmaCSVDumper.php');

$request = array(
	'action'			 => $action,
	'konversio_tyyppi'	 => $konversio_tyyppi,
	'kukarow'			 => $kukarow
);

$request['konversio_tyypit'] = array(
	'tuote'					 => t('Tuote'),
	'tuotteen_avainsanat'	 => t('Tuotteen avainsanat'),
	'asiakas'				 => t('Asiakas'),
	'kohde'					 => t('Kohde'),
	'paikka'				 => t('Paikka'),
	'laite'					 => t('Laite'),
	'yhteyshenkilo'			 => t('Yhteyshenkilö'),
	'asiakasalennus'		 => t('Asiakasalennus'),
);

if ($request['action'] == 'aja_konversio') {
	echo_kayttoliittyma($request);
	echo "<br/>";

	switch ($request['konversio_tyyppi']) {
		case 'tuote':
			$dumper = new TuoteCSVDumper($request['kukarow']);
			break;

		case 'tuotteen_avainsanat':
			$dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
			break;

		case 'asiakas':
			$dumper = new AsiakasCSVDumper($request['kukarow']);
			break;

		case 'yhteyshenkilo':
			$dumper = new YhteyshenkiloCSVDumper($request['kukarow']);
			break;

		case 'kohde':
			$dumper = new KohdeCSVDumper($request['kukarow']);
			break;

		case 'asiakasalennus':
			$dumper = new AsiakasalennusCSVDumper($request['kukarow']);
			break;

		case 'paikka':
			$dumper = new PaikkaCSVDumper($request['kukarow']);
			break;

		case 'laite':
			$dumper = new LaiteCSVDumper($request['kukarow']);
			break;

		default:
			die('Ei onnistu tämä');
			break;
	}

	$dumper->aja();

	if ($request['konversio_tyyppi'] == 'tuote') {
		$dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
		$dumper->aja();

		$dumper = new TuoteryhmaCSVDumper($request['kukarow']);
		$dumper->aja();
	}
}
else if ($request['action'] == 'poista_konversio_aineisto_kannasta') {
	$query_array = array(
		'DELETE FROM asiakas',
		'DELETE FROM yhteyshenkilo',
		'DELETE FROM tuote',
		'DELETE FROM kohde',
		'DELETE FROM paikka',
		'DELETE FROM laite',
		'DELETE FROM asiakasalennus',
		'DELETE FROM tuotteen_avainsanat',
		'DELETE FROM avainsana WHERE yhtio = "'.$kukarow['yhtio'].'" AND laji = "TRY"',
	);
	foreach ($query_array as $query) {
		pupe_query($query);
	}

	echo t('Poistettu');
	echo "<br/>";

	echo_kayttoliittyma($request);
}
else {
	echo_kayttoliittyma($request);
}

require('inc/footer.inc');

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='action' value='aja_konversio' />";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Tiedosto')."</th>";
	echo "<td>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Konversio tyyppi')."</th>";
	echo "<td>";
	echo "<select name='konversio_tyyppi'>";
	foreach ($request['konversio_tyypit'] as $konversio_tyyppi => $selitys) {
		$sel = "";
		if ($request['konversio_tyyppi'] == $konversio_tyyppi) {
			$sel = "SELECTED";
		}
		echo "<option value='{$konversio_tyyppi}' {$sel}>{$selitys}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t('Lähetä')."' />";
	echo "</form>";

	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='action' value='poista_konversio_aineisto_kannasta' />";
	echo "<input type='submit' value='".t('Poista koko konversio aineisto')."' />";
	echo "</form>";
}