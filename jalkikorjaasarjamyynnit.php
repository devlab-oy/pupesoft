<?php

	require('inc/parametrit.inc');

	echo "<font class='head'>".t("Korjaa sarjanumeromyyyntej�").":</font><hr><br>";

	if ($tee == "PAIVITA" and (checkdate(substr($paivamaara,5,2), substr($paivamaara,8,2), substr($paivamaara,0,4)) or $sarjanumero != "")) {

		if ($sarjanumero != "") {
			$lisa = " and sarjanumeroseuranta.sarjanumero = '$sarjanumero' ";
		}
		else {
			$lisa = " and tilausrivi.laskutettuaika >= '$paivamaara' ";
		}

		$query  = "	SELECT DISTINCT
					lasku.tunnus keikka,
					tilausrivi.tunnus rivitun,
					tilausrivi.tuoteno,
					tilausrivi.laskutettuaika,
					sarjanumeroseuranta.sarjanumero,
					sarjanumeroseuranta.ostorivitunnus,
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) r2
					FROM tilausrivi
					JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
					JOIN lasku on tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and lasku.alatila='X'
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.kpl != 0
					and tilausrivi.tyyppi in ('L','O')
					$lisa
					and tilausrivi.laskutettuaika >= '$yhtiorow[tilikausi_alku]'
					and tilausrivi.laskutettuaika <= '$yhtiorow[tilikausi_loppu]'
					and tilausrivi.var2 != 'EIOST'
					ORDER BY tilausrivi.laskutettuaika";
		$korjaaresult = mysql_query($query) or pupe_error($query);

		echo "<table>";

		while ($korjaarow = mysql_fetch_array($korjaaresult)) {

			// Tarvitaan
			// $tuoteno 	= korjattava tuote
			// $pvm 		= mihin p�iv��n asti korjataan
			// $uusihinta 	= mik� on tuon pvm:n oikea ostohinta
			// $rivitunnus 	= mik� on tapahtuman tehneen rivin tunnus

			$tuoteno		= $korjaarow["tuoteno"];
			$uusihinta 		= jalkilaskentafunktiolle_ostohinta($korjaarow["keikka"], $korjaarow["rivitun"]);

			if ($uusihinta) {
				$pvm			= $korjaarow["laskutettuaika"]; // koska t�m� tuote oli viety varastoon
				$rivitunnus 	= $korjaarow["rivitun"]; 		// rivin tunnus, t�ll� l�ydet��n varmasti oikea tapahtuma

				$sarjahin 		= sarjanumeron_ostohinta("ostorivitunnus", $korjaarow["ostorivitunnus"], "EIKULULASKUJA");

				if ($uusihinta != $sarjahin) {
					$uusikehahin	= jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus);
					echo "<tr><td>1</td><td>$korjaarow[tuoteno]</td><td>$korjaarow[sarjanumero]</td><td>$korjaarow[keikka]</td><td>$korjaarow[r2]</td><td>$uusihinta/$sarjahin</td><td>$uusikehahin</td></tr>";
				}
			}
		}

		$query  = "	SELECT DISTINCT
					ostorivi.uusiotunnus keikka,
					ostorivi.tunnus rivitun,
					ostorivi.laskutettuaika,
					tilausrivi.tunnus myyrivitun,
					tilausrivi.tuoteno,
					tilausrivi.kate,
					tilausrivi.rivihinta,
					tilausrivi.kpl,
					sarjanumeroseuranta.sarjanumero,
					sarjanumeroseuranta.ostorivitunnus,
					round(ostorivi.rivihinta/ostorivi.kpl,2) r2
					FROM tilausrivi
					JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus)
					JOIN tilausrivi ostorivi ON (sarjanumeroseuranta.yhtio=ostorivi.yhtio and sarjanumeroseuranta.ostorivitunnus=ostorivi.tunnus and ostorivi.laskutettuaika != '0000-00-00' and ostorivi.var2 != 'EIOST')
					JOIN lasku on tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and lasku.alatila='X'
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.kpl != 0
					and tilausrivi.tyyppi = 'L'
					$lisa
					and tilausrivi.laskutettuaika >= '$yhtiorow[tilikausi_alku]'
					and tilausrivi.laskutettuaika <= '$yhtiorow[tilikausi_loppu]'
					ORDER BY tilausrivi.laskutettuaika";
		$korjaaresult = mysql_query($query) or pupe_error($query);

		while ($korjaarow = mysql_fetch_array($korjaaresult)) {

			// Tarvitaan
			// $tuoteno 	= korjattava tuote
			// $pvm 		= mihin p�iv��n asti korjataan
			// $uusihinta 	= mik� on tuon pvm:n oikea ostohinta
			// $rivitunnus 	= mik� on tapahtuman tehneen rivin tunnus

			$tuoteno		= $korjaarow["tuoteno"];
			$uusihinta 		= jalkilaskentafunktiolle_ostohinta($korjaarow["keikka"], $korjaarow["rivitun"]);

			if ($uusihinta) {
				$pvm			= $korjaarow["laskutettuaika"]; // koska t�m� tuote oli viety varastoon
				$rivitunnus 	= $korjaarow["rivitun"]; 		// rivin tunnus, t�ll� l�ydet��n varmasti oikea tapahtuma

				$sarjahin 		= sarjanumeron_ostohinta("ostorivitunnus", $korjaarow["ostorivitunnus"]);

				$mrivin_ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $korjaarow["myyrivitun"]);
				$mriviero = abs($korjaarow["kate"] - ($korjaarow["rivihinta"] - ($korjaarow["kpl"]*$mrivin_ostohinta)));

				if ($mriviero > 1) {
					$uusikehahin	= jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus);
					echo "<tr><td>2</td><td>$korjaarow[tuoteno]</td><td>$korjaarow[sarjanumero]</td><td>$korjaarow[keikka]</td><td>$korjaarow[r2]</td><td>$uusihinta/$sarjahin</td><td>$uusikehahin</td></tr>";
				}
			}
		}

		echo "</table>";
		$tee = "";
	}

	if ($tee == "") {

		echo "<br><br>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";

		echo t("Sy�t� p�iv�m��r� josta korjataan").":<br>";
		echo "<input type='text' name='paivamaara' size='15'><br><br>";


		echo t("Sy�t� sarjanumero joka korjataan").":<br>";
		echo "<input type='text' name='sarjanumero' size='15'>";
		echo "<br><br><input type='submit' value='Korjaa'>";
		echo "</form>";
	}

	require ("inc/footer.inc");

?>
