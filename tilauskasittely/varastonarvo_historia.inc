<?php

//Kukarow kesken p‰ivitet‰‰n t‰ss‰, jotta kukaan ei voi samalla vied‰ varaston ja tehd‰ kohdistuksia
if ($kukarow["kesken"] == 0 or $kukarow["kesken"] != $otunnus) {
	$query = "update kuka set kesken='$otunnus' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
}

$query  = "select * from lasku where tunnus='$otunnus' and yhtio='$kukarow[yhtio]' and tila='K' and alatila=''";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	echo "<font style='error'>".t("Tilaus katosi!")."</font>";
	exit;
}

if ($tee == "varma") {
	require ("varastoon.inc");
	// takaisin ihan alkuun
	$toiminto = "dummy";
	$ytunnus  = "";
}
else {

	if ($tee == "eitullia") {
		//merkataan ne rivit joille ei pid‰ laskea tullia
		$query = "	UPDATE tilausrivi
					SET var=''
					WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'";
		$varresult = mysql_query($query) or pupe_error($query);

		if (count($eitullia) > 0) {
			foreach($eitullia as $eitul) {
				$query = "	UPDATE tilausrivi
							SET var='T'
							WHERE tunnus='$eitul' and uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'";
				$varresult = mysql_query($query) or pupe_error($query);
			}
		}
		$tee = "";
	}
	else {
		// katotaan onko liitettyj‰ laskuja, mutta ei vaihto-omaisuuslaskuja
		$query  = "select * from lasku where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus<>0 and laskunro='$laskurow[laskunro]' and vienti not in ('C','F','I','J','K','L')";
		$result = mysql_query($query) or pupe_error($query);

		$summa = 0;

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

				// arvotaan illan 7 oikein, eli kurssi
				if ($llrow["maksu_kurssi"] == 0) {	//t‰m‰ olisi huonompi juttu, mut ei mik‰‰n stopperi
					$llrow["maksu_kurssi"] = $llrow["vienti_kurssi"];
				}

				// tsekataan viel‰
				if ($llrow["maksu_kurssi"] == 0) {
					echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");
					exit;
				}

				$apusumma = round($llrow["arvo"] * $llrow["maksu_kurssi"],2);
				$summa += $llrow["arvo"] * $llrow["maksu_kurssi"];

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

		echo "<font class='message'>";
		echo t("Kululaskuja yhteens‰").": ".$summa." $yhtiorow[valkoodi]";
		echo "</font><br>";

		// yht‰‰n kululaskua ei ole liitetty, kokeillaan keksi‰ jotain kuluja! :)
		if ($summa == 0) {

			echo "<font class='error'>".t("Yht‰‰n kululaskua ei ole liitetty keikkaan!")." ";

			if ($laskurow["rahti_huolinta"] != 0) {
				echo t("K‰ytet‰‰n keikalle syˆtetty‰ kulusummaa.");
				$summa = $laskurow["rahti_huolinta"];
			}
			elseif ($laskurow['rahti'] != 0) {
				if ($laskurow["maksu_kurssi"] == 0) {	//t‰m‰ olisi huonompi juttu, mut ei mik‰‰n stopperi
					$laskurow["maksu_kurssi"] = $laskurow["vienti_kurssi"];
				}
				echo t("K‰ytet‰‰n keikalle syˆtetty‰ kuluprosenttia")." ".$laskurow['rahti']."%";
				$summa = round($laskurow['summa'] * $laskurow['maksu_kurssi'] * ($laskurow['rahti'] / 100), 2);
			}
			else {
				echo t("Keikalle ei lˆytynyt yht‰‰n kuluja!");
			}

			echo "</font><br>";
		}

		// p‰ivitet‰‰n kululaskujen summa kentt‰‰n saldo_maksettu keikan otsikolle
		$query  = "update lasku set saldo_maksettu='$summa', valkoodi='$yhtiorow[valkoodi]'
					where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 and laskunro='$laskurow[laskunro]'";
		$result = mysql_query($query) or pupe_error($query);

		// haetaan keikan otsikko laskurowhun
		$query  = "select * from lasku
					where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 and laskunro='$laskurow[laskunro]'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);
	}

	require ("varastoon_ruudulle.inc");

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
	echo "<input type='hidden' name='toiminto' value='kaikkiok'>";
	echo "<input type='hidden' name='tee' value='varma'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='submit' value='".t("Laske virallinen varastonarvo!")."'>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
	echo "<input type='submit' value='".t("Peruuta")."'>";
	echo "</form>";
}

//Kukarow kesken p‰ivitet‰‰n t‰ss‰ takas
$query = "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
$result = mysql_query($query) or pupe_error($query);

?>