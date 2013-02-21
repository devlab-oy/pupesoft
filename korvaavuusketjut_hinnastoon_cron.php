<?php
/** päivitetään korvaavuusketjun päätuotteen tarvittaessa hinnastoon kylläksi
 * - jos löytyy tuote jonka myytävissä on nolla
 * - jos tuotteen hinnastoon='K' hinnastoon='' ja hinnastoon='W'
 * - tuote on korvaavuusketjussa
 * - tuote ei ole korvaavuusketjun päätuote
 * - korvaavuusketjun päätuotteella on saldoa
 * - korvaavuusketjun päätuottella on hinnastoon ei
 */

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

// Yhtiö annettava parametriksi
if (trim($argv[1]) == '') {
	echo "Käyttö: {$_SERVER['SCRIPT_NAME']} [yhtion_nimi]\n";
	exit;
}

require "inc/connect.inc";
require "inc/functions.inc";

$kukarow['yhtio'] = (string) $argv[1];
$kukarow['kuka']  = 'cron';
$kukarow['kieli'] = 'fi';
$kukarow['extranet'] = '';

echo "Päivitetään korvaavuusketjun päätuotteet hinnastoon...\n\n";

// Haetaan kaikki korvaavuusketjujen id:t
$select = "SELECT DISTINCT(id) FROM korvaavat WHERE yhtio='{$kukarow['yhtio']}'";
$result = pupe_query($select);

// Laskurimuuttujat
$yhteensa = 0;
$muutettu = 0;

// Loopataan kaikki ketjut läpi ja etsitään päivitettäviä tuotteita
while ($ketju = mysql_fetch_assoc($result)) {

	// Haetaan ketjun tuotteet
	$tuotteet_query = "SELECT korvaavat.id, korvaavat.tuoteno, korvaavat.jarjestys, tuote.hinnastoon
						FROM korvaavat
						JOIN tuote on (tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno)
						WHERE korvaavat.yhtio='{$kukarow['yhtio']}'
						AND id='{$ketju['id']}'
						ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno;";
	$tuotteet_result = pupe_query($tuotteet_query);

	// Eka tuote on AINA ketjun päätuote
	$paa_tuote = mysql_fetch_assoc($tuotteet_result);

	// Jos päätuotteen tuote.hinnastoon on 'E'
	if ($paa_tuote['hinnastoon'] == 'E') {

		$myytavissa = saldo_myytavissa($paa_tuote['tuoteno']);

		// Jos päätuotteella on saldoa
		if ($myytavissa[0] > 0) {
			$yhteensa += 1;

			$paivitetaanko = false;

			// Loopataan ketjun tuotteet
			while ($ketjun_tuote = mysql_fetch_assoc($tuotteet_result)) {
				$tuote_myytavissa = saldo_myytavissa($ketjun_tuote['tuoteno']);

				// Päivitetään päätuote hinnastoon jos ketjussa on tuotteita joiden myytavissa on 0,
				// ja hinnastoon KYLLÄ
				if ($tuote_myytavissa[2] == 0 and $ketjun_tuote['hinnastoon'] != 'E') {
					$paivitetaanko = true;
				}
			}

			// Jos hinnastoon on setattu, niin ketjun päätuote pitää päivittää
			if ($paivitetaanko) {

				// Päivitetään tuote.hinnastoon
				$query = "UPDATE tuote SET hinnastoon='' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$paa_tuote['tuoteno']}'";
				if ($result = pupe_query($query)) {
					$muutettu += 1;
				}

				echo "Ketjun $paa_tuote[id] päätuote $paa_tuote[tuoteno] päivitetty hinnastoon kylläksi.\n";
			}
		}
	}
}
echo "\nYhteensä $yhteensa sopivaa ketjua, joista muutettiin $muutettu tuotetta hinnastoon.\n";