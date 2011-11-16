<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class=head>".t("Tuoteryhm‰tilasto")."</font><hr>";

	if (!isset($kka))
		$kka = date("m");
	if (!isset($vva))
		$vva = date("Y");
	if (!isset($ppa))
		$ppa = '01';

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	// k‰yttis
	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tee' value='raportoi'>";

	echo "<table>";
	echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td></tr>";

	echo "<tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>".t("Rajaukset")."</th><td colspan='3'>";

	$monivalintalaatikot = array('ASIAKASPIIRI','<br>OSASTO','TRY');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee != '' and isset($painoinnappia)) {

		$edellisvuosi = $vvl-1;
		$toissavuosi  = $vvl-2;

		// Korjataan hieman monivalintalaatikon paluttamaa muuttujaa, koska t‰ss‰ tiedot luetaan laskulta ja tilausrivilt‰
		$lisa = str_ireplace("asiakas.", "lasku.", $lisa);
		$lisa = str_ireplace("tuote.", "tilausrivi.", $lisa);

		echo "<table><tr>
			<th>",t("Valittu aikav‰li"),"</th>
			<td>{$ppa}</td>
			<td>{$kka}</td>
			<td>{$vva}</td>
			<th>-</th>
			<td>{$ppl}</td>
			<td>{$kkl}</td>
			<td>{$vvl}</td>
			</tr></table><br><br>\n";

		echo "<table>";
		echo "<th>".t("Piiri")."</th>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Tuoteryhm‰")."</th>";
		echo "<th>".t("Myynti")."<br>$toissavuosi</th>";
		echo "<th>".t("Myynti")."<br>$edellisvuosi</th>";
		echo "<th>".t("Myyntiind")."</th>";
		echo "<th>".t("Myynti")."<br>$vvl</th>";
		echo "<th>".t("Myynti")."<br>".t("aikav‰lill‰")."</th>";

		$query = "	SELECT
					lasku.piiri,
					tilausrivi.osasto,
					tilausrivi.try,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)), 2) aikavalilla,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vvl}-01-01' and tilausrivi.laskutettuaika <= '{$vvl}-12-31', tilausrivi.rivihinta, 0)), 2) myyntiVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.rivihinta, 0)), 2) edvuodenmyynti,
					round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.rivihinta, 0)), 2) toissavuodenmyynti
					FROM lasku
					JOIN tilausrivi on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
					WHERE lasku.yhtio   = '$kukarow[yhtio]'
					and lasku.tila	 	= 'U'
					and lasku.alatila 	= 'X'
					$lisa
					and	lasku.tapvm 	>= '{$toissavuosi}-{01}-{01}'
					and	lasku.tapvm 	<= '{$vvl}-{$kkl}-{$ppl}'
					GROUP BY lasku.piiri, tilausrivi.osasto, tilausrivi.try
					ORDER BY lasku.piiri, tilausrivi.osasto, tilausrivi.try";
		$eresult = pupe_query($query);

		while ($row = mysql_fetch_assoc($eresult)) {

			$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
			$osastorow = mysql_fetch_assoc($osastores);

			if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

			$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
			$tryrow = mysql_fetch_assoc($tryres);

			if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

			$myyntiind = 0;
			if ($row["toissavuodenmyynti"] != 0) $myyntiind = round($row["edvuodenmyynti"] / $row["toissavuodenmyynti"], 1);

			echo "<tr>";
			echo "<td>$row[piiri]</td>";
			echo "<td>$row[osasto]</td>";
			echo "<td>$row[try]</td>";
			echo "<td align='right'>$row[toissavuodenmyynti]</td>";
			echo "<td align='right'>$row[edvuodenmyynti]</td>";
			echo "<td align='right'>$myyntiind</td>";
			echo "<td align='right'>$row[myyntiVA]</td>";
			echo "<td align='right'>$row[aikavalilla]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	require ("inc/footer.inc");
?>