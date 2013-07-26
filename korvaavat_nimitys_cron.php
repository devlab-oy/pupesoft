<?php
/**
 * Päivitetään korvaavuusketjujen tuotteiden nimitys seuraavin ehdoin:
 * - Kaikille ketjun tuotteille, poislukien ketjun päätuote, joilla on järjestys 0 ja tuotteella
 *	 on saldoa, muutetaan nimitys muotoon "KORVAAVA A2". Jossa A2 on ketjussa järjestykseltään
 *	 suurempi kuin nimetty tuote.
 * - Samalla kun tuotteen nimitys muutetaan, poistetaan kyseinen tuote kaikista vastaavuusketjuista.
 */

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die("Tätä scriptiä voi ajaa vain komentoriviltä\n");
}

// Yhtiö annettava parametriksi
if (!isset($argv[1])) {
	echo "Käyttö: {$_SERVER['SCRIPT_NAME']} [yhtion_nimi]\n";
	exit;
}

require "inc/connect.inc";
require "inc/functions.inc";

$kukarow['yhtio'] = (string) $argv[1];
$kukarow['kuka']  = 'cron';
$kukarow['kieli'] = 'fi';
$kukarow['extranet'] = '';

// Laskurimuuttujat
$yhteensa = 0;
$muutettu = 0;
$poistettu = 0;

echo "Päivitetään korvaavuusketjun nimitykset ...\n\n";

// Haetaan tuoteketjut joilla on järjestys=0 tuotteita
$select = "SELECT distinct(id)
			FROM korvaavat
			WHERE yhtio='{$kukarow['yhtio']}'
			AND jarjestys=0";
$result = pupe_query($select);

// Loopataan ketjut läpi
while($ketju = mysql_fetch_assoc($result)) {

	// Haetaan ketjun tuotteet
	$tuotteet_query = "SELECT korvaavat.id, korvaavat.tuoteno, korvaavat.jarjestys
						FROM korvaavat
						WHERE korvaavat.yhtio='{$kukarow['yhtio']}'
						AND id='{$ketju['id']}'
						ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno";
	$tuotteet_result = pupe_query($tuotteet_query);

	// Poislukien päätuote
	$paa_tuote = mysql_fetch_assoc($tuotteet_result);
	$edellinen_tuote = $paa_tuote;

	// Loopataan ketjun muut tuotteet läpi
	while ($tuote = mysql_fetch_assoc($tuotteet_result)) {

		// Jos tuotteen järjestys on 0, tarkistetaan sen saldo
		if ($tuote['jarjestys'] == 0) {
			// Tuotteen saldo
			$myytavissa = saldo_myytavissa($tuote['tuoteno']);

			// Huomioidaan vain tuotteet joilla on saldoa
			if ($myytavissa[0] > 0) {

				// Muutetaan tuotteen nimitys (tuotteet joiden järjestys on 0 ja niillä on saldoa)
				echo "Muutetaan tuotteen {$tuote['tuoteno']} nimitys 'KORVAAVA $edellinen_tuote[tuoteno]'\n";

				$uusi_nimitys = "KORVAAVA " . $edellinen_tuote['tuoteno'];
				$muutos_query = "UPDATE tuote SET nimitys='$uusi_nimitys' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuote['tuoteno']}'";

				// Ajetaan päivitysquery ja poistetaan tuote vastaavuusketjuista
				if (pupe_query($muutos_query)) {
					$muutettu++;

					$poista_vastaavat_query = "DELETE FROM vastaavat
												WHERE yhtio='{$kukarow['yhtio']}'
												AND tuoteno='{$tuote['tuoteno']}'";

					if (pupe_query($poista_vastaavat_query)) {
					 	$poistettu++;
					}
				}
			}
		}

		// Laitetaan ketjun edellinen tuote talteen
		$edellinen_tuote = $tuote;
	}

	$yhteensa++;
}

echo "\nYhteensä $yhteensa sopivaa tuotetta, joista muutettiin $muutettu nimitystä\n";
echo "Vastaavat ketjuista poistettiin $poistettu tuotetta\n";