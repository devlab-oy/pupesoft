<?php

// tehd‰‰n lock file, ettei voi kerralla ajaa kuin yks per firma
file_put_contents("/tmp/$kukarow[yhtio]-keikka.lock", "$otunnus");

$query  = "SELECT * from lasku where tunnus='$otunnus' and yhtio='$kukarow[yhtio]' and tila='K' and alatila=''";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	echo "<font style='error'>".t("Tilaus katosi!")."</font>";
	exit;
}

list(,,,$kohok,,,,$lisok,,$sarjanrook,,,,,,$varok) = tsekit($laskurow, 0);

if ($kohok == 1 and $lisok == 1 and $sarjanrook == 1 and $varok == 1) {
	if ($tee == "varma") {
		require ("varastoon.inc");

		// takaisin ihan alkuun
		$toiminto 		= "dummy";
		$ytunnus  		= "";
		$toimittajaid 	= "";
	}
	else {

		if ($tee == "eitullia") {
			//merkataan ne rivit joille ei pid‰ laskea tullia
			$query = "	UPDATE tilausrivi
						SET var = ''
						WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
			$varresult = mysql_query($query) or pupe_error($query);

			if (count($eitullia) > 0) {
				foreach($eitullia as $eitul) {
					$query = "	UPDATE tilausrivi
								SET var = 'T'
								WHERE tunnus = '$eitul' and uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
					$varresult = mysql_query($query) or pupe_error($query);
				}
			}
			$tee = "";
		}

		// katotaan onko liitettyj‰ laskuja, mutta ei vaihto-omaisuuslaskuja
		$query  = "	SELECT *
					from lasku
					where yhtio = '$kukarow[yhtio]'
					and tila = 'K'
					and alatila = ''
					and vanhatunnus <> 0
					and laskunro = '$laskurow[laskunro]'
					and vienti not in ('C','F','I','J','K','L')";
		$result = mysql_query($query) or pupe_error($query);

		$summa = 0;

		echo "<br>";

		if (mysql_num_rows($result) != 0) {
			echo "<table>";
			echo "<tr>
					<th>".t("Nimi")."</th>
					<th>".t("Summa")."</th>
					<th>".t("Veroton")."</th>
					<th>".t("Valuutta")."</th>
					<th>".t("Veroton")." $yhtiorow[valkoodi]</th>
					<th>".t("Viite")."</th>
					<th>".t("Vienti")."</th></tr>";

			while($llrow = mysql_fetch_array($result)) {

				// tsekataan viel‰
				if ($llrow["vienti_kurssi"] == 0) {
					echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");
					exit;
				}

				$apusumma = round($llrow["arvo"] * $llrow["vienti_kurssi"],2);
				$summa += $llrow["arvo"] * $llrow["vienti_kurssi"];

				echo "<tr>
						<td>$llrow[nimi]</td>
						<td align='right'>$llrow[summa]</td>
						<td align='right'>$llrow[arvo]</td>
						<td>$llrow[valkoodi]</td>
						<td align='right'>$apusumma</td>
						<td>$llrow[viite]</td>
						<td>$llrow[vienti]</td></tr>";
			}

			// pyˆristet‰‰n kahteen desimaaliin vasta lopuksi...
			$summa = round($summa,2);

			echo "</table><br>";
		}

		if ($yhtiorow["jalkilaskenta_kuluperuste"] == "") {
			echo "<font class='message'>";
			echo t("Kululaskuja yhteens‰").": ".$summa." $yhtiorow[valkoodi]";
			echo "</font><br>";

			// yht‰‰n kululaskua ei ole liitetty
			if ($summa == 0) {
				echo "<font class='error'>".t("Yht‰‰n kululaskua ei ole liitetty saapumiseen!")."<br>";
			}
		}

		// p‰ivitet‰‰n kululaskujen summa kentt‰‰n saldo_maksettu keikan otsikolle
		$query  = "	UPDATE lasku set saldo_maksettu='$summa', valkoodi='$yhtiorow[valkoodi]'
					where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 and laskunro='$laskurow[laskunro]'";
		$result = mysql_query($query) or pupe_error($query);

		// haetaan keikan otsikko laskurowhun
		$query  = "	SELECT *
					from lasku
					where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 and laskunro='$laskurow[laskunro]'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		require ("varastoon.inc");

		echo "<form method='post'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type='hidden' name='toiminto' value='kaikkiok'>";
		echo "<input type='hidden' name='tee' value='varma'>";
		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<input type='submit' value='".t("Laske virallinen varastonarvo!")."'>";
		echo "</form>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
		echo "<input type='submit' value='".t("Peruuta")."'>";
		echo "</form>";
	}
}
else {
	echo "<br><br><font class='error'>".t("Saapumisen tiedot eiv‰t t‰sm‰‰! Korjaa asia")."!</font><br>";
}

// poistetaan lock file
unlink("/tmp/$kukarow[yhtio]-keikka.lock");

?>