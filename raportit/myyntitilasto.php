<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Myyntitilasto")."</font><hr>";

	if (!isset($kka))
		$kka = '01';
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

	$monivalintalaatikot = array('OSASTO','TRY');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";

	if (!function_exists("tuoteryhman_varastonarvo")) {
		function tuoteryhman_varastonarvo($parametrit) {
			global $kukarow, $yhtiorow;

			$osasto = $parametrit['osasto'];
			$try 	= $parametrit['try'];
			$pvm1 	= $parametrit['pvm1'];
			$pvm2 	= $parametrit['pvm2'];

			if ($pvm1 == "" or $osasto == "" or $try == "") {
				return false;
			}

			// saldo nyt
			$query = "	SELECT sum(saldo) saldo_nyt
						FROM tuote
						JOIN tuotepaikat on (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
						WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
						AND tuote.osasto = '{$osasto}'
						AND tuote.try = '{$try}'";
			$result = pupe_query($query);
			$arvo = mysql_fetch_assoc($result);
						
			$saldo_nyt = $arvo['saldo_nyt'];

			// varastonmuutos
 			$query = "	SELECT
						sum(if(tapahtuma.laadittu >= '$pvm1 00:00:00', tapahtuma.kpl, 0)) muutoskpl1,
						sum(if(tapahtuma.laadittu >= '$pvm2 00:00:00', tapahtuma.kpl, 0)) muutoskpl2
			 			FROM tuote
						JOIN tapahtuma on (tuote.yhtio = tapahtuma.yhtio AND tuote.tuoteno = tapahtuma.tuoteno and tapahtuma.laadittu >= '$pvm2 00:00:00' and tapahtuma.laji != 'Ep‰kurantti')
						WHERE tuote.yhtio 	= '$kukarow[yhtio]'
			 			AND tuote.osasto 	= '{$osasto}'
						AND tuote.try 		= '{$try}'
						ORDER BY tapahtuma.laadittu desc, tapahtuma.tunnus desc";
			$muutosres = pupe_query($query);
			$row = mysql_fetch_assoc($muutosres);
						
			$arvo1 = $saldo_nyt + $row['muutoskpl1'];
			$arvo2 = $saldo_nyt + $row['muutoskpl2'];
						
			return array($arvo1, $arvo2);
		}
	}

	if ($tee != '' and isset($painoinnappia)) {

		$edellisvuosi = $vvl-1;

		// Korjataan hieman monivalintalaatikon paluttamaa muuttujaa, koska t‰ss‰ tiedot luetaan laskulta ja tilausrivilt‰
		$lisa = str_ireplace("asiakas.", "lasku.", $lisa);
		$lisa = str_ireplace("tuote.", "tilausrivi.", $lisa);

		echo "<table>
				<tr>
				<th>",t("Valittu aikav‰li"),"</th>
				<td>{$ppa}</td>
				<td>{$kka}</td>
				<td>{$vva}</td>
				<th>-</th>
				<td>{$ppl}</td>
				<td>{$kkl}</td>
				<td>{$vvl}</td>
				</tr>
			</table><br><br>";

		echo "<table>";
		echo "<th nowrap>".t("Osasto")."</th>";
		echo "<th nowrap>".t("Tuoteryhm‰")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("aikav‰lill‰")."<br>$vvl</th>";
		echo "<th nowrap>".t("Kate")."<br>".t("aikav‰lill‰")."<br>$vvl</th>";
		echo "<th nowrap>".t("Kate %")."<br>".t("aikav‰lill‰")."<br>$vvl</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("aikav‰lill‰")."<br>$edellisvuosi</th>";
		echo "<th nowrap>".t("Kate")."<br>".t("aikav‰lill‰")."<br>$edellisvuosi</th>";
		echo "<th nowrap>".t("Kate %")."<br>".t("aikav‰lill‰")."<br>$edellisvuosi</th>";
		echo "<th nowrap>".t("Myynti")."<br>$edellisvuosi</th>";
		echo "<th nowrap>".t("Kate")."<br>$edellisvuosi</th>";
		echo "<th nowrap>".t("Kate %")."<br>$edellisvuosi</th>";
		echo "<th nowrap>".t("Myynti")."<br>12kk</th>";
		echo "<th nowrap>".t("Kate")."<br>12kk</th>";
		echo "<th nowrap>".t("Kate %")."<br>12kk</th>";
		echo "<th nowrap>".t("Myyntiind")."</th>";
		echo "<th nowrap>".t("Kateind")."</th>";
		echo "<th nowrap>".t("Varasto")."<br>{$vvl}-{$kkl}-{$ppl}</th>";
		echo "<th nowrap>".t("Varasto")."<br>{$edellisvuosi}-{$kkl}-{$ppl}</th>";

		$query = "	SELECT
					tilausrivi.osasto,
					tilausrivi.try,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0))) myyntiVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate, 0))) kateVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0))) myyntiEDVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-{$kkl}-{$ppl}', tilausrivi.kate, 0))) kateEDVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.rivihinta, 0))) myyntiED,
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.kate, 0))) kateED,
					round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vvl}-{$kkl}-{$ppl}', interval 12 month), tilausrivi.rivihinta, 0))) myynti12,
					round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vvl}-{$kkl}-{$ppl}', interval 12 month), tilausrivi.kate, 0))) kate12
					FROM lasku
					JOIN tilausrivi on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
					WHERE lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila		= 'U'
					and lasku.alatila 	= 'X'
					$lisa
					and	lasku.tapvm 	>= '{$edellisvuosi}-01-01'
					and	lasku.tapvm 	<= '{$vvl}-{$kkl}-{$ppl}'
					GROUP BY tilausrivi.osasto, tilausrivi.try
					ORDER BY tilausrivi.osasto, tilausrivi.try";
		$eresult = pupe_query($query);

		while ($row = mysql_fetch_assoc($eresult)) {

			$parametrit 			= array();
			$parametrit['osasto'] 	= $row['osasto'];
			$parametrit['try'] 		= $row['try'];
			$parametrit['pvm1'] 	= "{$vvl}-{$kkl}-{$ppl}";
			$parametrit['pvm2'] 	= "{$edellisvuosi}-{$kkl}-{$ppl}";

			list($arvo_hetkella_1, $arvo_hetkella_2) = tuoteryhman_varastonarvo($parametrit);

			$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
			$osastorow = mysql_fetch_assoc($osastores);

			if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

			$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
			$tryrow = mysql_fetch_assoc($tryres);

			if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

			echo "<tr>";
			echo "<td>$row[osasto]</td>";
			echo "<td>$row[try] </td>";
			echo "<td align='right'>$row[myyntiVA]</td>";
			echo "<td align='right'>$row[kateVA]</td>";
			echo "<td align='right'>".round($row['kateVA'] / $row['myyntiVA'] * 100, 1)."</td>";
			echo "<td align='right'>$row[myyntiEDVA]</td>";
			echo "<td align='right'>$row[kateEDVA]</td>";
			echo "<td align='right'>".round($row['myyntiEDVA'] / $row['kateEDVA'] * 100, 1)."</td>";
			echo "<td align='right'>$row[myyntiED]</td>";
			echo "<td align='right'>$row[kateED]</td>";
			echo "<td align='right'>".round($row['kateED'] / $row['myyntiED'] * 100, 1)."</td>";
			echo "<td align='right'>$row[myynti12]</td>";
			echo "<td align='right'>$row[kate12]</td>";
			echo "<td align='right'>".round($row['kate12'] / $row['myynti12'] * 100, 1)."</td>";
			echo "<td align='right'></td>";
			echo "<td align='right'></td>";
			echo "<td align='right'>$arvo_hetkella_1</td>";
			echo "<td align='right'>$arvo_hetkella_2</td>";
			echo "</tr>";
		}

		echo "</table>";
	}

	require ("inc/footer.inc");
?>