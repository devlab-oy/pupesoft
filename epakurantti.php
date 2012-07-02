<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Epäkurantit")."</font><hr>";

	$tee = isset($tee) ? trim($tee) : "";

	if ($tee != '') {

		// täällä tehdään epäkuranttihommat
		// tarvitaan $kukarow, $tuoteno ja jos halutaan muuttaa ni $tee jossa on paalle, puolipaalle tai pois
		require ("epakurantti.inc");

		if ($tee == 'vahvista' and isset($sarjatunnus) and $sarjatunnus > 0) {

			echo "<form method='post'>";
			echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";
			echo "<input type='hidden' name='sarjanro' value='{$sarjanro}'>";
			echo "<input type='hidden' name='sarjatunnus' value='{$sarjatunnus}'>";
			echo "<input type='hidden' name = 'tee' value='sarjanro_paalle'>";

			echo "<table>";
			echo "<tr><th>".t("Tuote")            			."</th><td>$tuoterow[tuoteno]</td></tr>";
			echo "<tr><th>".t("Sarjanumero")				."</th><td>$sarjanro</td>";
			echo "<tr><th>".t("Varastonarvo nyt")			."</th><td>";
			echo sprintf('%.2f', sarjanumeron_ostohinta("tunnus", $sarjatunnus));
			echo "</td></tr>";
			echo "<tr><th>".t("Uusi varastonarvo")			."</th><td>";
			echo "<input type='text' name='uusiarvo' size='10'>";
			echo "</td></tr>";
			echo "<tr><th>".t("Selite")			."</th><td>";
			echo "<input type='text' name='epakurantti_selite' size='35'></td></tr>";
			echo "</table><br><br>";
			echo "<input type='submit' value='".t("Muuta varastonarvoa")."'>";
			echo "</form><br>";

		}
		elseif ($tee == 'vahvista') {

			echo "<table>";
			echo "<tr><th>".t("Tuote")            			."</th><td>$tuoterow[tuoteno]</td></tr>";
			echo "<tr><th>".t("Varastonarvo nyt")			."</th><td>$tuoterow[saldo] * $nykyinen_keskihankintahinta = $nykyinen_varastonarvo</td></tr>";
			echo "<tr><th>".t("Korjaamaton varastonarvo")	."</th><td>$tuoterow[saldo] * $tuoterow[kehahin] = $brutto_varastonarvo</td></tr>";
			echo "<tr><th>".t("25% epäkurantti")			."</th><td>$tuoterow[epakurantti25pvm]</td></tr>";
			echo "<tr><th>".t("Puoliepäkurantti")			."</th><td>$tuoterow[epakurantti50pvm]</td></tr>";
			echo "<tr><th>".t("75% epäkurantti")			."</th><td>$tuoterow[epakurantti75pvm]</td></tr>";
			echo "<tr><th>".t("Epäkurantti")				."</th><td>$tuoterow[epakurantti100pvm]</td></tr>";
			echo "</table><br>";

			// voidaan merkata 25epäkurantiksi
			if ($tuoterow['epakurantti25pvm'] == '0000-00-00') {
				echo "<form method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='25paalle'> ";
				echo "<input type='submit' value='".t("Merkitään 25% epäkurantiksi")."'></form> ";
			}

			// voidaan merkata puoliepäkurantiksi
			if ($tuoterow['epakurantti50pvm'] == '0000-00-00') {
				echo "<form method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='puolipaalle'> ";
				echo "<input type='submit' value='".t("Merkitään puoliepäkurantiksi")."'></form> ";
			}

			// voidaan merkata 75epäkurantiksi
			if ($tuoterow['epakurantti75pvm'] == '0000-00-00') {
				echo "<form method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='75paalle'> ";
				echo "<input type='submit' value='".t("Merkitään 75% epäkurantiksi")."'></form> ";
			}

			// voidaan merkata epäkurantiksi
			if ($tuoterow['epakurantti100pvm'] == '0000-00-00') {
				echo "<form method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='paalle'>";
				echo "<input type='submit' value='".t("Merkitään epäkurantiksi")."'></form> ";
			}

			// voidaan aktivoida
			if (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
				echo "<form method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='pois'>";
				echo "<input type='submit' value='".t("Aktivoidaan kurantiksi")."'></form>";
			}

			// voidaan aktivoida
			if (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
				echo "<form method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='peru'>";
				echo "<input type='submit' value='".t("Aktivoidaan kurantiksi, ei nosteta keskihankintahintaa")."'></form>";
			}

		}
		elseif ($tee != "STOP") {
			$tee = "";
		}
	}

	if ($tee == '') {
		echo "<table><tr><th>".t("Valitse tuote")."</th><td>";
		echo "<form name='epaku' method='post' autocomplete='off'>";
		echo "<input type='text' name='tuoteno'>";
		echo "</td><td>";
		echo "<input type='hidden' name='tee' value='vahvista'>";
		echo "<input type='submit' value='".t("Valitse")."'>";
		echo "</form>";
		echo "</td></tr></table>";

		// kursorinohjausta
		$formi  = "epaku";
		$kentta = "tuoteno";
	}

	require ("inc/footer.inc");

?>