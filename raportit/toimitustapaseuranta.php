<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Toimitustapaseuranta")."</font><hr>";

	if ($tee != '') {
		echo "<table>";

		$query = "	SELECT lasku.toimitustapa, sec_to_time(avg(time_to_sec(date_format(toimitettuaika,'%H:%i:%s')))) aika, count(distinct lasku.tunnus) kpl, sum(tilausrivi.rivihinta) summa
					FROM lasku, tilausrivi
					WHERE lasku.yhtio='$kukarow[yhtio]'
					and lasku.tila='L'
					and lasku.alatila='X'
					and lasku.tapvm >='$vva-$kka-$ppa'
					and lasku.tapvm <='$vvl-$kkl-$ppl'
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.yhtio=tilausrivi.yhtio
					GROUP BY lasku.toimitustapa
					ORDER BY kpl desc, toimitustapa, aika";
		$result = mysql_query($query) or pupe_error($query);

		echo "<tr>
				<th>".t("Toimitustapa")."</th>
				<th>".t("Toimitusaika")."</th>
				<th>".t("Tilauksia")."</th>
				<th>".t("Tilauksia/Päivä")."</th>
				<th>".t("Myynti")."</th>";
		echo "</tr>";

		//päiviä aikajaksossa
		$epa1 = (int) date('U',mktime(0,0,0,$kka,$ppa,$vva));
		$epa2 = (int) date('U',mktime(0,0,0,$kkl,$ppl,$vvl));

		//Diff in workdays (5 day week)
		$pva = abs($epa2-$epa1)/60/60/24/7*5;

		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$row[toimitustapa]</td>";
			echo "<td>$row[aika]</td>";
			echo "<td>$row[kpl]</td>";

			$kplperpva = round($row["kpl"]/$pva,0);
			echo "<td>$kplperpva</td>";

			echo "<td>$row[summa]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}


	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	// ehdotetaan 7 päivää taaksepäin
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");

?>