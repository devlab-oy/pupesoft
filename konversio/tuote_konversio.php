<?php

ini_set("memory_limit", "5G");

$debug = true;
if (php_sapi_name() != 'cli' and !$debug) {
	die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

require ("../inc/parametrit.inc");

if (trim(empty($argv[1])) and !$debug) {
	echo "Et antanut yhtiötä!\n";
	exit;
}
else {
	$yhtio = "lpk";
}

if (!$debug) {
	// Parametrit
	$yhtio = pupesoft_cleanstring($argv[1]);
}

// Haetaan yhtiön tiedot
$yhtiorow = hae_yhtion_parametrit($yhtio);

// Haetaan käyttäjän tiedot
$query = "	SELECT *
			FROM kuka
			WHERE yhtio = '$yhtio'
			AND kuka = 'admin'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
	die("User admin not found");
}

// Adminin oletus, mutta kuka konversio
$kukarow = mysql_fetch_assoc($result);
$filepath = "/tmp/turvanasi_tuote.csv";
$separator = ';';
$konversio_array = array(
	'tuoteno'		 => 'KOODI',
	'nimitys'		 => 'NIMI',
	'try'			 => 'LUOKKA',
	'aleryhma'		 => 'LUOKKA',
	'myyntihinta'	 => 'HINTA',
	'tuotetyyppi'	 => 'RYHMA',
	'ei_saldoa'		 => 'RYHMA',
);

$required_fields = array(
	'tuoteno',
	'nimitys',
);
$columns_to_be_utf8_decoded = array(
	'nimitys',
);
$table = 'tuote';

$rivit = lue_csv_tiedosto($filepath, $separator);

list($rivit, $errors) = konvertoi_rivit($rivit, $konversio_array, $required_fields, $columns_to_be_utf8_decoded);

if (empty($errors)) {
	echo t('Kaikki ok ajetaan data kantaan');
	echo "<br/>";
	dump_data($rivit, $table);
}
else {
	foreach ($errors as $rivinumero => $row_errors) {
		echo t('Rivillä')." {$rivinumero} ".t('oli seuraavat virheet').":";
		echo "<br/>";
		foreach ($row_errors as $row_error) {
			echo $row_error;
			echo "<br/>";
		}
	}
}

require('inc/footer.inc');

function dump_data($rivit, $table) {
	global $kukarow, $yhtiorow;

	foreach ($rivit as $rivi) {
		$query = "	INSERT INTO {$table}
					(".implode(", ", array_keys($rivi)).")
					VALUES
					('".implode("', '", array_values($rivi))."')";

		pupe_query($query);
	}
}

function konvertoi_rivit($rivit, $konversio_array, $required_fields, $columns_to_be_utf8_decoded) {
	global $kukarow;

	$errors = array();
	foreach ($rivit as $index => &$rivi) {
		$rivi = konvertoi_rivi($rivi, $konversio_array);
		$rivi = decode_to_utf8($rivi, $columns_to_be_utf8_decoded);
		$rivi = lisaa_pakolliset_kentat($rivi);

		validoi_rivi($rivi, $required_fields, $errors, $index + 2);
	}

	return array($rivit, $errors);
}

function konvertoi_rivi($rivi, $konversio_array) {
	$rivi_temp = array();

	foreach ($konversio_array as $konvertoitu_header => $csv_header) {
		if (array_key_exists($csv_header, $rivi)) {
			if ($konvertoitu_header == 'try' or $konvertoitu_header == 'aleryhma') {
				$rivi_temp[$konvertoitu_header] = substr($rivi[$csv_header], 0, 2);
			}
			else if ($konvertoitu_header == 'tuotetyyppi') {
				if (trim(strtolower($rivi[$csv_header])) == 'tuote') {
					$rivi_temp[$konvertoitu_header] = 'R';
				}
				else if (trim(strtolower($rivi[$csv_header])) == 'palvelutuote') {
					$rivi_temp[$konvertoitu_header] = 'K';
				}
				else {
					$rivi_temp[$konvertoitu_header] = '';
				}
			}
			else if ($konvertoitu_header == 'ei_saldoa') {
				if (trim(strtolower($rivi[$csv_header])) == 'palvelutuote') {
					$rivi_temp[$konvertoitu_header] = 'o';
				}
				else {
					$rivi_temp[$konvertoitu_header] = '';
				}
			}
			else if ($konvertoitu_header == 'nimitys') {
				$rivi_temp[$konvertoitu_header] = ucfirst($rivi[$csv_header]);
			}
			else {
				$rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
			}
		}
	}

	return $rivi_temp;
}

function decode_to_utf8($rivi, $columns_to_be_utf8_decoded) {
	foreach ($rivi as $header => &$value) {
		if (in_array($header, $columns_to_be_utf8_decoded)) {
			$value = utf8_decode($value);
		}
	}

	return $rivi;
}

function validoi_rivi($rivi, $required_fields, &$errors, $index) {
	global $kukarow;

	foreach ($required_fields as $required_field) {
		if ($rivi[$required_field] == '') {
			$errors[$index][] = t('Pakollinen kenttä')." $required_field ".t('puuttuu');
		}
	}
}

function lisaa_pakolliset_kentat($rivi) {
	global $kukarow;
	$pakolliset_kentat = array(
		'yhtio'		 => $kukarow['yhtio'],
		'laatija'	 => 'import',
		'luontiaika' => 'now()',
	);

	foreach ($pakolliset_kentat as $header => $pakollinen_kentta) {
		$rivi[$header] = $pakollinen_kentta;
	}

	return $rivi;
}

function lue_csv_tiedosto($filepath, $separator) {

	$csv_headerit = lue_csv_tiedoston_otsikot($filepath, $separator);
	$file = fopen($filepath, "r") or die("Ei aukea!\n");

	$rivit = array();
	$i = 1;
	while ($rivi = fgets($file)) {
		if ($i == 1) {
			$i++;
			continue;
		}

		$rivi = explode($separator, $rivi);
		$rivi = to_assoc($rivi, $csv_headerit);

		$rivit[] = $rivi;

		$i++;
	}

	fclose($file);

	return $rivit;
}

function to_assoc($rivi, $csv_headerit) {

	$rivi_temp = array();
	foreach ($rivi as $index => $value) {
		$rivi_temp[strtoupper($csv_headerit[$index])] = $value;
	}

	return $rivi_temp;
}

function lue_csv_tiedoston_otsikot($filepath, $separator) {

	$file = fopen($filepath, "r") or die("Ei aukea!\n");
	$header_rivi = fgets($file);
	$header_rivi = explode($separator, $header_rivi);
	fclose($file);

	return $header_rivi;
}