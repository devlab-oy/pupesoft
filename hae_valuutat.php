<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Valuuttakurssien päivitys<hr></font>";

	ob_start();

	$val_palautus = @readfile("http://www.suomenpankki.fi/ohi/fin/0_new/0.1_valuuttak/fix-rec.txt");

	if ($val_palautus !== FALSE) {
		$val_palautus = ob_get_contents();
	}
	else {
		unset ($val_palautus);
	}

	ob_end_clean();

	if (isset($val_palautus)) {

		// splitataan rivit rivinvaihdosta
		$rivit = explode("\n",$val_palautus);

		// käydään läpi riveittäin
		foreach ($rivit as $rivi) {

			// splitataan rivi spacesta
			$arvot = explode(" ", $rivi);

			// haetaan valuuttakoodi
			$valuutta = explode("/", $arvot[1]);

			// haetaan kurssi
			$kurssi = (float) 1 / $arvot[2];

			// varmistetaan, että oli yhtiö kurssi on sama ku tuli boffin saitilta
			if ($yhtiorow["valkoodi"] == $valutta[1]) {

				$query = "update valuu set kurssi='$kurssi' where yhtio='$kukarow[yhtio]' and nimi='$valuutta[0]'";
				$result = mysql_query($oikeuquery) or pupe_error($oikeuquery);

				if (mysql_affected_rows($result) != 0) {
					echo "<font class='message'>Päivitettiin $arvot[0] kurssi valuutalle $valuutta[0]: $kurssi</font><br>";
				}
			}

		}
	}
	else {
		echo "<font class='error'>Valuuttakurssien päivitys epäonnistui!</font><br>";
	}

	require ("inc/footer.inc");

?>