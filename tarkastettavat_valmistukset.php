<?php
/**
* Tarkastettavat valmistukset
*/

require 'inc/parametrit.inc';
require 'valmistus.class.php';

echo "<font class='head'>".t("Tarkastettavat valmistukset")."</font>";
echo "<hr>";

/** Päivitetään valmistuksen tila */
if ($tee == 'paivita' and isset($tunnus)) {
	$valmistus = Valmistus::find($tunnus);

	// Yritetään muuttaa valmistuksen tilaa
	try {
		$valmistus->setTila(Valmistus::TARKASTETTU);
	} catch (Exception $e) {
		$errors = $e->getMessage();
	}
}

// Jos valmistus on valittu, listataan yksittäisen valmistuksen tiedot
if ($tee == 'nayta' and isset($tunnus)) {
	// Haetaan valmistus
	$valmistus = Valmistus::find($tunnus);

	echo "<table>";

	echo "<tr>";
	echo "<th>" . t("Tilaus") . "</th>";
	echo "<td>{$valmistus->tunnus()}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>" . t("Asiakas/Nimi") . "</th>";
	echo "<td>{$valmistus->nimi}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>" . t("Ytunnus") . "</th>";
	echo "<td>{$valmistus->ytunnus}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>" . t("Tila") . "</th>";
	echo "<td>{$valmistus->getTila()}</td>";
	echo "</tr>";

	echo "</table>";

	echo "<a href='tarkastettavat_valmistukset.php'>" . t("Takaisin") . "</a>";
	echo " ";
	echo "<form action='tarkastettavat_valmistukset.php' method='post'>
				<input type='hidden' name='tunnus' value='{$valmistus->tunnus()}'>
				<input name='tee' type='hidden' value='paivita'>
				<input name='submit' type='submit' value='" . t("Tarkastettu") . "'>
			</form>";
	echo "<br><br>";

	echo "<table>";
	echo "<tr>
			<th>" . t("Tunnus") . "</th>
			<th>" . t("Nimitys") . "</th>
			<th>" . t("Valmistettu") . "</th>
		</tr>";

	// Valmistuksen raaka-aineet
	foreach($valmistus->raaka_aineet() as $ra) {
		if ($ra['tyyppi'] == 'W' or $ra['tyyppi'] == 'M') {
			echo "<tr class='spec'>";
		} else {
			echo "<tr>";
		}
		echo "<td>{$valmistus->tunnus()}</td>";
		echo "<td>{$ra['nimitys']}</td>";
		echo "<td>{$ra['varattu']}</td>";
		echo "</tr>";
	}

	echo "</table>";

}

// Listataan kaikki valmistukset
else {
	// Haetaan valmistukset joiden tila on Valmis Tarkastukseen (VT)
	$valmistukset = Valmistus::find_by_tila(Valmistus::VALMIS_TARKASTUKSEEN);

	if ($valmistukset) {

		echo "<table>
				<tr>
					<th>" . t("Tila") . "</th>
					<th>" . t("tunnus") . "</th>
					<th>" . t("Valmiste") . "</th>
					<th>" . t("Ylityötunnit") . "</th>
					<th>" . t("Kommentti") . "</th>
					<th colspan=2></th>
				</tr>";

		// Listataan valmistukset
		foreach($valmistukset as $valmistus) {
			echo "<tr>";
			echo "<td>{$valmistus->getTila()}</td>";
			echo "<td>{$valmistus->tunnus()}</td>";

			echo "<td>";
			foreach($valmistus->tuotteet() as $valmiste) {
				echo $valmiste['nimitys']."<br>";
			}
			echo "</td>";

			echo "<td>{$valmistus->ylityotunnit}</td>";
			echo "<td>{$valmistus->kommentti}</td>";
			echo "<td><form method='get'>
						<input type='hidden' name='tee' value='nayta'>
						<input type='hidden' name='tunnus' value='{$valmistus->tunnus()}'>
						<input type='submit' value='" . t("Valitse") . "'>
						</form></td>";
			echo "<td><form method='post'>
						<input name='tee' type='hidden' value='paivita'>
						<input type='hidden' name='tunnus' value='{$valmistus->tunnus()}'>
						<input name='submit' type='submit' value='" . t("Tarkastettu") . "'>
					</form></td>";
			echo "</tr>";
		}

		echo "</table>";
	} else {
		echo t("Ei tarkastettavia valmistuksia");
	}
}

if (isset($errors)) echo "<font class='error'>$errors</font>";
