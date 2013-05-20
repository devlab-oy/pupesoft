<?php

require 'inc/parametrit.inc';

require 'valmistuslinjat.inc';
require 'valmistus.class.php';

// Yhtiönparametri pitää olla setattu jotta tätä toimminallisuutta voidaan käyttää
if ($yhtiorow['valmistuksessa_kaytetaan_tilakoodeja'] != 'K') {
	exit("Valmistuslinjojen työjonot toiminta vaatii yhtiönparametrin valmistuksessa käytetään tilakoodeja");
}

/**
 * Näytetään lomake valmistuksen tilan vaihdossa
 *
 */
if (isset($tee) and $tee == 'verify') {
	// Haetaan aina valmistus
	$valmistus = Valmistus::find($tunnus);

	// Näytetään edit formi (valmista_tarkastukseen)
	// Formilla kysytään valmistettu määrä ja kommentit
	if ($tila == Valmistus::VALMIS_TARKASTUKSEEN) {
		include '_valmistus_edit.php';
	}
	// Näytetään formi (keskeytys)
	else if ($tila == Valmistus::KESKEYTETTY) {
		include '_keskeyta_valmistus.php';
	}
	else {
		$tee = 'update';
	}
}

/**
 * Jos tullaan lommakkeelta, päivitetään valmistuksen tiedot
 */
if (isset($tee) and $tee == 'update') {

	// Haetaan aina valmistus
	$valmistus = Valmistus::find($tunnus);

	// Keskeytetään työ
	if ($tila == 'TK') {

		try {
			// Merkataan kommentti, ylityötunnit ja kaytetyttunnit talteen
			$valmistus->kommentti = $kommentti;
			$valmistus->ylityotunnit = $ylityotunnit;
			$valmistus->kaytetyttunnit = $kaytetyttunnit;
			$valmistus->keskeyta();
		} catch (Exception $e) {
			$errors = "Virhe {$e->getMessage()}";
		}

	}
	// Merkataan valmiiksi
	elseif ($tila == 'VT') {
		// Loopataan päivitettävät valmisteet läpi ja tarkistetaan syötetyt määrät
		// Splitataan tarvittaessa
		try {

			// Jos on tultu lomakkeen kautta ($tee=='verify')
			if (isset($valmisteet)) {
				$tuotteet = $valmistus->tuotteet();

				if (empty($tuotteet)) {
					throw new Exception("Valmistuksella ei ole yhtään valmistetta");
				}

				// Loopataan valmistukset läpi
				foreach ($tuotteet as $valmiste) {

					// Syötetyt arvot
					$maara = $valmisteet[$valmiste['tuoteno']]['maara'];
					$tunnit = $valmisteet[$valmiste['tuoteno']]['tunnit'];

					// Määrää pienennetään (splitataan valmistus)
					if ($maara < $valmiste['varattu']) {
						throw new Exception("Virhe valmistuksen keskeytyksessä (ei jaettu)");
					}
					// Määrä on sama (valmistus on valmistettu kokonaan)
					else if ($maara == $valmiste['varattu']) {
						#echo "määrä sama! päivitetään vaan tila ja lisätään kommentit";
					}
					// Virhe
					else {
						throw new Exception("Valmistettava määrä ei voi olla suurempi kuin tilattu määrä");
					}
				}
			}

			// päivitetään kalenterin tiedot
			$query = "UPDATE kalenteri
						SET kentta01 = '{$ylityotunnit}',
						kentta02     = '{$kommentti}',
						pvmalku      = '{$pvmalku}',
						pvmloppu     = '{$pvmloppu}'
						WHERE yhtio  = '{$kukarow['yhtio']}'
						AND otunnus  = '{$tunnus}'";
			pupe_query($query);

			$valmistus->setTila($tila);

		} catch (Exception $e) {
			$errors = "VIRHE: {$e->getMessage()}";
		}
	}
	else {
		try {
			// Päivitetään vain tila
			$valmistus->setTila($tila);
		} catch (Exception $e) {
			$errors = "VIRHE: {$e->getMessage()}";
		}
	}

	// Palataan työjono näkymään
	$tee = '';
}

if ($tee == '') {

	/* TYÖJONO TYÖNTEKIJÄ */
	echo "<font class='head'>".t("Valmistuslinjojen työjonot")."</font>";
	echo "<hr>";

	// Haetaan valmistuslinjat
	$linjat = hae_valmistuslinjat();

	if (empty($linjat)) {
		echo "Ei valmistuslinjoja";
	}

	// Valmistuksen tilat selväkielisenä
	$tilat = array(
			'OV' => 'Odottaa valmistusta',
			'VA' => 'Valmistuksessa',
			'TK' => 'Työ keskeytetty',
			'VT' => 'Valmis tarkastukseen',
			'TA' => 'Tarkastettu'
		);

	foreach($linjat as $linja) {

		echo "<table>";
		echo "<tr>";
		echo "<th colspan=5>" . t("Valmistuslinja") . ": " . $linja['selitetark']."</th>";
		echo "</tr>";
		echo "<tr>
			<th>" . t("Tila") . " </th>
			<th>" . t("Nimitys") . "</th>
			<th>" . t("Viite") . "</th>
			<th>" . t("Määrä") . "</th>
			<th></th>
			</tr>";

		// Haetaan linjan 4 uusinta kalenterimerkinnät
		$tyojono_query = "SELECT kalenteri.kuka, kalenteri.henkilo, nimitys, varattu, yksikko, pvmalku, pvmloppu, kalenteri.tunnus, lasku.valmistuksen_tila, lasku.viesti, kalenteri.otunnus
						FROM kalenteri
						JOIN tilausrivi on (tilausrivi.yhtio=kalenteri.yhtio and tilausrivi.otunnus=kalenteri.otunnus)
						JOIN lasku on (lasku.yhtio=kalenteri.yhtio and lasku.tunnus=kalenteri.otunnus)
						WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
						AND henkilo='{$linja['selite']}'
						AND tilausrivi.tyyppi='W'
						AND lasku.valmistuksen_tila != ('TA')
						ORDER BY pvmalku
						LIMIT 4";
		$tyojono_result = pupe_query($tyojono_query);

		// Jos työjono on tyhjä
		if (mysql_num_rows($tyojono_result) == 0) {
			echo "<tr>";
			echo "<td colspan=4>";
			echo t("Ei valmistuksia jonossa.");
			echo "</td>";
			echo "</tr>";
		}
		else {
			// Työjonon työt
			while($tyojono = mysql_fetch_assoc($tyojono_result)) {
				echo "<tr>";
				echo "<td>" . strtoupper($tilat[$tyojono['valmistuksen_tila']]) . "</td>";
				echo "<td>" . $tyojono['nimitys'] . "</td>";
				echo "<td>" . $tyojono['viesti'] ."</td>";
				echo "<td>" . $tyojono['varattu'] . " " . $tyojono['yksikko'] . "</td>";

				echo "<td>";
				echo "<form method='post'>";
				echo "<input type='hidden' name='tee' value='verify'>";
				echo "<input type='hidden' name='tunnus' value={$tyojono['otunnus']}>";

				echo "<select name='tila' onchange='submit()'>";
				echo "<option value=''>Valitse</option>";

				// Aloittamaton valmistus voidaan aloittaa tai jakaa
				if ($tyojono['valmistuksen_tila'] == Valmistus::ODOTTAA) {
					echo "<option value='VA'>Aloita valmistus</option>";

				}
				// Aloitettu valmistus voidaan merkata valmistetuksi tai keskeyttää
				else if ($tyojono['valmistuksen_tila'] == Valmistus::VALMISTUKSESSA) {
					echo "<option value='TK'>Keskeytä valmistus</option>";
					echo "<option value='VT'>Valmis tarkistukseen</option>";
				}
				else if ($tyojono['valmistuksen_tila'] == Valmistus::KESKEYTETTY) {
					echo "<option value='VA'>Aloita valmistus</option>";
				}

				echo "<option value='OV'>(Siirä parkkiin)</option>"; # TODO: Tätä ei tarvita täällä.
				echo "</select>";

				echo "</form>";
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
		}
	}

	// Virheet
	if (isset($errors)) echo "<font class='error'>$errors</font>";

}