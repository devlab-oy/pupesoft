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
require_once('HuoltosykliCSVDumper.php');
require_once('TarkastuksetKantaCSVDumper.php');
require_once('TarkastuksetCSVDumper.php');

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
	'huoltosykli'			 => t('Huoltosykli'),
	'tarkastukset_kanta'	 => t('Tarkastukset kanta'),
	'tarkastukset'			 => t('Tarkastukset'),
	'kaikki'				 => t('Kaikki'),
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

		case 'huoltosykli':
			$dumper = new HuoltosykliCSVDumper($request['kukarow']);
			break;

		case 'tarkastukset_kanta':
			$dumper = new TarkastuksetKantaCSVDumper($request['kukarow']);
			break;

		case 'tarkastukset':
			$tiedostot = lue_tiedostot('/tmp/konversio/tarkastukset/');
			foreach ($tiedostot as $tiedosto) {
				echo $tiedosto.'<br/>';
//				//passthru("php tarkastukset.php {$tiedosto}");
//                exec("php tarkastukset.php {$tiedosto}");
                
                $dumper = new TarkastuksetCSVDumper($kukarow, $tiedosto);
                
                $dumper->aja();
			}
			break;

		case 'kaikki':
			echo t('Tuote').':';
			$dumper = new TuoteCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Toimenpidetuotteiden avainsanat').':';
			$dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Tuoteryhmät').':';
			$dumper = new TuoteryhmaCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Laite tuotteiden avainsanat').':';
			$dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Asiakkaat').':';
			$dumper = new AsiakasCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Yhteyshenkilöt').':';
			$dumper = new YhteyshenkiloCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Asiakasalennukset').':';
			$dumper = new AsiakasalennusCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Kohteet').':';
			$dumper = new KohdeCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Paikat').':';
			$dumper = new PaikkaCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Laitteet').':';
			$dumper = new LaiteCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Huoltosyklit').':';
//			$dumper = new HuoltosykliCSVDumper($request['kukarow']);
//			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Tarkastukset').':';
//			$dumper = new TarkastuksetCSVDumper($request['kukarow']);
//			$dumper->aja();
			break;

		default:
			die('Ei onnistu tämä');
			break;
	}

	if ($request['konversio_tyyppi'] != 'kaikki' and isset($dumper)) {
		$dumper->aja();
	}

	if ($request['konversio_tyyppi'] == 'tuote') {
		$dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
		$dumper->aja();

		$dumper = new TuoteryhmaCSVDumper($request['kukarow']);
		$dumper->aja();
	}
}
else if ($request['action'] == 'poista_konversio_aineisto_kannasta') {
	$query_array = array(
//		'DELETE FROM asiakas WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM yhteyshenkilo WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM tuote WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM kohde WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM paikka WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM laite WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM asiakasalennus WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM tuotteen_avainsanat WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM avainsana WHERE yhtio = "'.$kukarow['yhtio'].'" AND laji = "TRY"',
//		'DELETE FROM huoltosykli WHERE yhtio = "'.$kukarow['yhtio'].'"',
//		'DELETE FROM huoltosyklit_laitteet WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tyomaarays WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM lasku WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM laskun_lisatiedot WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tilausrivi WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tilausrivin_lisatiedot WHERE yhtio = "'.$kukarow['yhtio'].'"',
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

function lue_tiedostot($polku) {
	$tiedostot = array();
	$handle = opendir($polku);
	if ($handle) {
		while (false !== ($tiedosto = readdir($handle))) {
			if ($tiedosto != "." && $tiedosto != ".." && $tiedosto != '.DS_Store') {
				if (is_file($polku.$tiedosto)) {
					$tiedostot[] = $polku.$tiedosto;
				}
			}
		}
		closedir($handle);
	}

	return $tiedostot;
}