<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Ep‰kurantit")."</font><hr>";

	if ($tee != '') {

		// t‰‰ll‰ tehd‰‰n ep‰kuranttihommat
		// tarvitaan $kukarow, $tuoteno ja jos halutaan muuttaa ni $tee jossa on paalle, puolipaalle tai pois
		require ("epakurantti.inc");

		if ($tee == 'vahvista') {
		
			echo "<table>";
			echo "<tr><th>".t("Tuote")            ."</th><td>$tuoterow[tuoteno]</td></tr>";
			echo "<tr><th>".t("Varastonarvo")     ."</th><td>$apu</td></tr>";
			echo "<tr><th>".t("Korjaamaton varastonarvo")     ."</th><td>$tuoterow[saldo] * $tuoterow[kehahin] = $apu2</td></tr>";
			echo "<tr><th>".t("Puoliep‰kurantti") ."</th><td>$tuoterow[epakurantti1pvm]</td></tr>";
			echo "<tr><th>".t("Ep‰kurantti")      ."</th><td>$tuoterow[epakurantti2pvm]</td></tr>";
			echo "</table><br>";

			// voidaan merkata puoliep‰kurantiksi
			if ($tuoterow['epakurantti1pvm'] == '0000-00-00') {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='puolipaalle'> ";
				echo "<input type='submit' value='".t("Merkit‰‰n puoliep‰kurantiksi")."'></form> ";
			}
			
			// voidaan merkata ep‰kurantiksi
			if ($tuoterow['epakurantti2pvm'] == '0000-00-00') {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='paalle'>";
				echo "<input type='submit' value='".t("Merkit‰‰n ep‰kurantiksi")."'></form> ";
			}

			// voidaan aktivoida
			if (($tuoterow['epakurantti1pvm'] != '0000-00-00') or ($tuoterow['epakurantti2pvm'] != '0000-00-00')) {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<input type='hidden' name = 'tuoteno' value='$tuoterow[tuoteno]'>";
				echo "<input type='hidden' name = 'tee' value='pois'>";
				echo "<input type='submit' value='".t("Aktivoidaan kurantiksi")."'></form>";
			}
		}
	}

	if ($tee == '') {
		echo "<table><tr><th>".t("Valitse tuote")."</th><td>";
		echo "<form name='epaku' action='$PHP_SELF' method='post' autocomplete='off'>";
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