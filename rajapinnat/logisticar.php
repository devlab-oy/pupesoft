<?php
/**
 * 
 * 
 */

if (empty($argv)) {
    die('<p>Tämän scriptin voi ajaa ainoastaan komentoriviltä.</p>');
}

require 'inc/connect.inc';
require 'inc/functions.inc';

// kaikki virheilmotukset
ini_set('error_reporting', E_ALL | E_STRICT);

$path            = '/tmp/';
$path_nimike     = $path . 'NIMIKE.txt';
$path_asiakas    = $path . 'ASIAKAS.txt';
$path_toimittaja = $path . 'TOIMITTAJA.txt';
$path_varasto    = $path . 'VARASTO.txt';
$path_tapahtumat = $path . 'TAPAHTUMAT.txt';
$path_myynti     = $path . 'MYYNTI.txt';

$kukarow = array(
	'kuka'  => 'anttih',
	'yhtio' => 'artr',
);

$query = "SELECT * from yhtio where yhtio='{$kukarow['yhtio']}'";
$res = mysql_query($query) or pupe_error($query);
$yhtiorow = mysql_fetch_assoc($res);


$query = "SELECT * from yhtion_parametrit where yhtio='{$kukarow['yhtio']}'";
$res = mysql_query($query) or pupe_error($query);
$params = mysql_fetch_assoc($res);


$yhtiorow = array_merge($yhtiorow, $params);

// ajetaan kaikki operaatiot
//nimike();
asiakas();
toimittaja();
varasto();
varastotapahtumat();
myynti();

function nimike()
{
	global $kukarow, $path_nimike;
	
	$query = "SELECT tuoteno from tuote where yhtio='{$kukarow['yhtio']}'";
	$rest = mysql_query($query) or pupe_error($query);
	
	$rows = mysql_num_rows($rest);
	if ($rows == 0) {
		echo "Yhtään tuotetta ei löytynyt\n";
		die(); 
	}
	
	$fp = fopen($path_nimike, 'w+');
	
	$row = 0;
	while ($tuoteno = mysql_fetch_assoc($rest)) {
		
		// mones tämä on
		$row++;
		
		$query = "SELECT
		          tuote.tuoteno        nimiketunnus,
		          nimitys              nimitys,
		          yksikko              yksikko,
		          avainsana.selitetark tuoteryhma,
		          tuotteen_toimittajat.liitostunnus toimittajatunnus,
		          #varastotunnus,
		          #osasto,
		          ei_saldoa      varastoimiskoodi,
		          tuotetyyppi    nimikelaji,
		          kuka.kuka      ostaja,
		          tuotemassa     paino
		          #lava,
		          #lavakerros
		          from tuote
		          JOIN avainsana ON avainsana.selite=tuote.try and avainsana.yhtio=tuote.yhtio
		          JOIN tuotteen_toimittajat ON tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.yhtio=tuotteen_toimittajat.yhtio
				  JOIN kuka ON kuka.myyja=tuote.ostajanro
				  WHERE tuote.yhtio='{$kukarow['yhtio']}'
				  and tuote.tuoteno='{$tuoteno['tuoteno']}'";
		
		$res = mysql_query($query) or pupe_error($query);

		$data = array(
			'nimiketunnus'     => null,
			'nimitys'          => null,
			'yksikko'          => null,
			'tuoteryhma'       => null,
			'toimittajatunnus' => null,
			'varastotunnus'    => null,
			'hintayksikko'     => null,
			'varastoimiskoodi' => null,
			'nimikelaji'       => null,
			'ostaja'           => null,
			'paino'            => null,
		);
		
		create_headers($fp, array_keys($data));
		
		while ($tuote = mysql_fetch_assoc($res)) {
			
			if (trim($tuote['varastoimiskoodi']) != '') {
				// tuotetta ei varastoida
				$tuote['varastoimiskoodi'] = '0';
			} else {
				$tuote['varastoimiskoodi'] = '1';
			}

			// hintayksikko aina 1
			$tuote['hintayksikko'] = '1';
			
			$query = "SELECT hyllyalue, hyllynro from tuotepaikat where tuoteno='{$tuoteno['tuoteno']}' and oletus != '' and yhtio='{$kukarow['yhtio']}'";
			$res = mysql_query($query) or pupe_error($query);
			$paikka = mysql_fetch_assoc($res);
			
			// mikä varasto
			$tuote['varastotunnus'] = kuuluukovarastoon($paikka['hyllyalue'], $paikka['hyllynro']);
			
			$data = array_merge($data, $tuote);
			
			$data = implode("\t", $data);
			//echo '.';
			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}
		
		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");
		
		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}
		
		echo sprintf("%s  |%-40s|\r", $str, $hash);
	}
	
	fclose($fp);
	echo "\nDone.\n";
}

function asiakas()
{
	global $path_asiakas, $kukarow;
	
	echo "Asiakkaat...";
	
	$query = "SELECT tunnus, nimi, nimitark, ryhma, myyjanro from asiakas where yhtio='{$kukarow['yhtio']}'";
	$rest = mysql_query($query) or pupe_error($query);
	
	$rows = mysql_num_rows($rest);
	if ($rows == 0) {
		echo "Yhtään asiakasta ei löytynyt\n";
		die(); 
	}
	
	$fp = fopen($path_asiakas, 'w+');
	
	$data = array(
		'asiakastunnus'  => null,
		'asiakkaan nimi' => null,
		'asiakasryhma'   => null,
		'myyjatunnus'    => null,
		'laskutustunnus' => null,
	);
	
	create_headers($fp, array_keys($data));
	
	while ($asiakas = mysql_fetch_array($rest)) {
		$data = array(
			'asiakastunnus'  => $asiakas['tunnus'],
			'asiakkaan nimi' => $asiakas['nimi'] . ' ' . $asiakas['nimitark'],
			'asiakasryhma'   => $asiakas['ryhma'],
			'myyjatunnus'    => $asiakas['myyjanro'],
			'laskutustunnus' => $asiakas['tunnus'],
		);
		
		$data = implode("\t", $data);
		
		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}
	}
	
	fclose($fp);
	echo "Done.\n";
}

function toimittaja()
{
	global $path_toimittaja, $kukarow;
	
	echo "Toimittajat...";
	$query = "SELECT tunnus, nimi, nimitark, yhteyshenkilo from toimi where yhtio='{$kukarow['yhtio']}'";
	$rest = mysql_query($query) or pupe_error($query);
	
	$rows = mysql_num_rows($rest);
	if ($rows == 0) {
		echo "Yhtään toimittajaa ei löytynyt\n";
		die(); 
	}
	
	$fp = fopen($path_toimittaja, 'w+');

	$data = array(
		'toimittajatunnus'  => null,
		'toimittajan nimi'  => null,
		'ostajatunnus'      => null,
	);
	
	create_headers($fp, array_keys($data));
	
	while ($asiakas = mysql_fetch_array($rest)) {
		$data = array(
			'toimittajatunnus'  => $asiakas['tunnus'],
			'toimittajan nimi'  => $asiakas['nimi'] . ' ' . $asiakas['nimitark'],
			'ostajatunnus'      => $asiakas['yhteyshenkilo'],
		);
		
		$data = implode("\t", $data);
		
		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}
	}
	
	fclose($fp);
	echo "Done.\n";
}

function varasto()
{
	global $path_varasto, $kukarow;
	
	echo "Varasto... ";
	$fp = fopen($path_varasto, 'w+');
	
	$query = "SELECT tuotepaikat.tuoteno nimiketunnus, sum(tuotepaikat.saldo) saldo, tuote.kehahin keskihinta, varastopaikat.tunnus varastotunnus
			FROM tuotepaikat
			JOIN varastopaikat ON
			concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
			concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
			and varastopaikat.yhtio=tuotepaikat.yhtio
			JOIN tuote ON tuote.tuoteno=tuotepaikat.tuoteno and tuote.yhtio=tuotepaikat.yhtio
			WHERE tuote.ei_saldoa = '' and tuotepaikat.yhtio='{$kukarow['yhtio']}'
			GROUP BY tuotepaikat.tuoteno, varastopaikat.tunnus
			ORDER BY tuotepaikat.tuoteno";
	
	$res = mysql_query($query) or pupe_error($query);
	
	$rows = mysql_num_rows($res);
	if ($rows == 0) {
		echo "Yhtään tuotetta ei löytynyt\n";
		die(); 
	}
	
	$headers = array(
		'nimiketunnus',
		'saldo',
		'keskihinta',
		'varastotunnus',
	);
	
	// tehdään otsikot
	create_headers($fp, $headers);
	
	while ($trow = mysql_fetch_assoc($res)) {
		
		$data = implode("\t", $trow);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}
	}
	
	fclose($fp);
	echo "Done.\n";
}

function varastotapahtumat()
{
	global $path_tapahtumat, $kukarow;
	
	echo "Varastotapahtumat... ";
	if (! $fp = fopen($path_tapahtumat, 'w+')) {
		die("Ei voitu avata filea $path_tapahtumat");
	}
	
	$date = date('Y-m-d', mktime(0, 0, 0, date('m')-2, date('d'), date('Y')));
	
    $query = "SELECT tilausrivi.tuoteno nimiketunnus,
			tilausrivi.laskutettuaika   tapahtumapaiva,
			tilausrivi.tyyppi           tapahtumalaji,
			tilausrivi.rivihinta        hinta, # rivin veroton arvo
			tilausrivi.kate             kate,
			tilausrivi.kpl              tapahtumamaara,
			lasku.laskunro              laskunumero,
			lasku.liitostunnus          asiakastunnus,
			lasku.myyja                 myyjatunnus,
			varastopaikat.tunnus        varastotunnus
			FROM tilausrivi
			JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.uusiotunnus and lasku.yhtio=tilausrivi.yhtio
			JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio 
			JOIN varastopaikat ON
			concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
			concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
            WHERE tilausrivi.tyyppi IN('L', 'O') and tilausrivi.laskutettuaika >= '$date' and tilausrivi.yhtio='{$kukarow['yhtio']}'
			ORDER BY tilausrivi.laskutettuaika";
    
    $res = mysql_query($query) or pupe_error($query);
    
	$headers = array(
		'nimiketunnus'   => null,
		'asiakastunnus'  => null,
		'tapahtumapaiva' => null,
		'tapahtumalaji'  => null,
		'myyntiarvo'     => null,
		'ostoarvo'       => null,
		'tapahtumamaara' => null,
		'laskunumero'    => null,
		'myyjatunnus'    => null,
		'varastotunnus'  => null,
	);
	
	// tehdään otsikot
	create_headers($fp, array_keys($headers));
	
    while ($trow = mysql_fetch_assoc($res)) {
		
		switch($trow['tapahtumalaji']) {
			// ostot
			case 'O':
				
				// 1 = saapuminen tai oston palautus
				$trow['tapahtumalaji'] = 1;
				
				// ostoarvo
				$trow['ostoarvo'] = $trow['hinta'];
				
				// myyntiarvo on 0
				$trow['myyntiarvo'] = 0;
				
				// jos kpl alle 0 niin tämä on oston palautus
				// jolloin hinta myös miinus
				if ($trow['tapahtumamaara'] < 0) {
					// tapahtumamaara on aina positiivinen logisticarissa
					$trow['tapahtumamaara'] = -1 * $trow['tapahtumamaara'];
				}
				
		        break;
			
			// myynnit
			case 'L':
				
				// 2 = otto tai myynninpalautus
				$trow['tapahtumalaji'] = 2;
				
				$trow['myyntiarvo'] = $trow['hinta'];
				
				// ostoarvo
				$trow['ostoarvo'] = $trow['hinta'] - $trow['kate'];
				
				// tämä on myynninpalautus eli myyntiarvo on negatiivinen
				if ($trow['tapahtumamaara'] < 0) {
					// tapahtumamaara on aina positiivinen logisticarissa
					$trow['tapahtumamaara'] = -1 * $trow['tapahtumamaara'];
				}
				
				break;
		}
		
		unset($trow['hinta']);
		unset($trow['kate']);
		
		$data = array_merge($headers, $trow);
		
		$data = implode("\t", $data);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}
    }
	
	fclose($fp);
	echo "Done.\n";
}


function myynti()
{
	global $path_myynti, $kukarow, $yhtiorow;
	
	echo "Myynnit... ";
	if (! $fp = fopen($path_myynti, 'w+')) {
		die("Ei voitu avata filea $path_myynti");
	}
	
	$date = date('Y-m-d', mktime(0, 0, 0, date('m')-2, date('d'), date('Y')));
	
    $query = "SELECT tilausrivi.tuoteno nimiketunnus,
			tilausrivi.toimaika toimituspaiva,
			tilausrivi.tyyppi tapahtumalaji,
			tilausrivi.hinta  / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)) rivihinta,
			tilausrivi.varattu tapahtumamaara,
			lasku.liitostunnus asiakastunnus,
			lasku.myyja myyjatunnus,
			lasku.tunnus tilausnro,
			varastopaikat.tunnus varastotunnus
			FROM tilausrivi
			JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.uusiotunnus and lasku.yhtio=tilausrivi.yhtio
			JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio 
			JOIN varastopaikat ON
			concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
			concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
			WHERE
			tilausrivi.varattu != 0
			AND tilausrivi.tyyppi IN('L', 'O')
			AND tilausrivi.laskutettuaika = '0000-00-00' AND tilausrivi.laadittu >= '$date 00:00:00'
			AND tilausrivi.yhtio='{$kukarow['yhtio']}'
			ORDER BY tilausrivi.laskutettuaika";
	
	$res = mysql_query($query) or pupe_error($query);

	$headers = array(
		'nimiketunnus'   => null,
		'asiakastunnus'  => null,
		'toimituspaiva'  => null,
		'tapahtumalaji'  => null,
		'myyntiarvo'     => null,
		'ostoarvo'       => null,
		'tapahtumamaara' => null,
		'tilausnro'      => null,
		'myyjatunnus'    => null,
		'varastotunnus'  => null,
	);

	// tehdään otsikot
	create_headers($fp, array_keys($headers));
	
	while ($trow = mysql_fetch_assoc($res)) {
		
		$trow['myyntiarvo'] = $trow['rivihinta'];
		$trow['ostoarvo']   = $trow['rivihinta'];
		
		unset($trow['rivihinta']);
		
		switch ($trow['tapahtumalaji']) {
			case 'L':
				$trow['tapahtumalaji'] = '4';
				break;
			case 'O':
				$trow['tapahtumalaji'] = '3';
				break;
		}
		$data = array_merge($headers, $trow);

		$data = implode("\t", $data);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}
    }

	fclose($fp);
	echo "Done.\n";
}

function create_headers($fp, array $cols)
{
	$data = implode("\t", $cols) . "\n";
	fwrite($fp, $data);
}
?>