<?php

if ($_POST["pyytaja"] == "yhteensopivuus") {
	$_POST["pyytaja"] = "yhteensopivuus.php";
	$pyytajadir = "../arwidson/";
}
else {
	$_POST["pyytaja"] = "tuote_selaus_haku.php";
	$pyytajadir = "../tilauskasittely/";
}

if (file_exists("../inc/parametrit.inc")) {
	require ("../inc/parametrit.inc");
	$post_myynti = $pyytajadir.$pyytaja;
}
else {
	require ("parametrit.inc");
	$post_myynti = $pyytaja;
}

echo "<font class='head'>".t("Ostoskorisi")."</font><hr>";

echo "	<form method='post' action='$post_myynti'>
		<input type='hidden' name='ostoskori' value='$ostoskori'>
		<input type='submit' value='".t("Palaa selaimeen")."'>
		</form>";

if ($tee == "poistakori") {
	$query = "	select tunnus from lasku
				where yhtio = '$kukarow[yhtio]' and
				tila = 'B' and
				liitostunnus = '$kukarow[oletus_asiakas]' and
				alatila='$ostoskori'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// löyty vaan yks dellataan se
		$ostoskori = mysql_fetch_array($result);

		$query = "	delete from tilausrivi
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'B' and
					otunnus = '$ostoskori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	delete from lasku
					where yhtio = '$kukarow[yhtio]' and
					tila = 'B' and
					tunnus = '$ostoskori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>Ostoskori tyhjennetty.</font><br>";
	}
	else {
		echo "<font class='message'>Ostoskorin tyhjennys epäonnistui.</font><br>";
	}

	$tee = "";
}

if ($tee == "poistarivi") {
	$query = "	select tunnus from lasku
				where yhtio = '$kukarow[yhtio]' and
				tila = 'B' and
				liitostunnus = '$kukarow[oletus_asiakas]' and
				alatila='$ostoskori'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// löyty vaan yks dellataan siitä rivi
		$ostoskori = mysql_fetch_array($result);

		$query = "	delete from tilausrivi
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'B' and
					tunnus = '$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		echo "<font class='message'>Rivin poisto epäonnistui.</font><br>";
	}

	$tee = "";
}

if ($tee == "") {

	if (is_numeric($ostoskori)) {
		$lisa = "and alatila='$ostoskori'";
	}
	else {
		$lisa = "";
	}

	$query = "	SELECT lasku.*, count(*) rivit
				FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
				JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'B')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'B' and
				lasku.liitostunnus = '$kukarow[oletus_asiakas]'
				$lisa
				GROUP BY lasku.tunnus
				HAVING rivit > 0
				ORDER BY alatila";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		while ($ostoskori = mysql_fetch_array($result)) {

			echo "<table>";

			echo "<tr>";
			echo "<th colspan='3'>Ostoskorissa nro $ostoskori[alatila] olevat tuotteet</th>";

			echo "<form method='post' action='ostoskori.php'>";
			echo "<th colspan='3' style='text-align:right;'>";
			echo "<input type='hidden' name='tee' value='poistakori'>
					<input type='hidden' name='ostoskori' value='$ostoskori[alatila]'>
					<input type='submit' value='".t("Tyhjennä ostoskori")."'>";
			echo "</th>";
			echo "</form>";
			echo "<tr>";

			echo "<tr>";
			echo "<th>Tuoteno</th>";
			echo "<th>Nimitys</th>";
			echo "<th>Määrä</th>";
			echo "<th>Yksikköhinta</th>";
			echo "<th>Rivihinta</th>";
			echo "<th>Poista</th>";
			echo "</tr>";

			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]' and
						otunnus = '$ostoskori[tunnus]' and
						tyyppi = 'B'";
			$riviresult = mysql_query($query) or pupe_error($query);

			while ($koririvi = mysql_fetch_array($riviresult)) {

				echo "<tr>";
				echo "<td>$koririvi[tuoteno]</td>";
				echo "<td>$koririvi[nimitys]</td>";
				echo "<td>$koririvi[varattu]</td>";
				echo "<td>$koririvi[hinta]</td>";
				echo "<td>$koririvi[rivihinta]</td>";
				echo "<form method='post' action='ostoskori.php'>";
				echo "<td>";
				echo "<input type='hidden' name='tee' value='poistarivi'>
						<input type='hidden' name='ostoskori' value='$ostoskori[alatila]'>
						<input type='hidden' name='rivitunnus' value='$koririvi[tunnus]'>
						<input type='submit' value='".t("Poista")."'>";
				echo "</td>";
				echo "</form>";
				echo "</tr>";

			}

			echo "</table><br>";

		}

	}
	else {
		echo "<font class='message'>Ostoskorisi on tyhjä.</font><br>";
	}
}

?>