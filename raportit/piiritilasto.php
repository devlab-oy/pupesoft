<?php
	require ("../inc/parametrit.inc");

	echo "<font class=head>".t("Piiriraportointi")."</font><hr>";

	// käyttis
	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<table>";

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

	echo "<input type='hidden' name='tee' value='raportoi'>";

	$sel = array();
	$sel[$rappari] = "SELECTED";

	echo "<tr><th>".t("Raporttityyppi")."</th>";
	echo "<td colspan='3'><select name='rappari'>";
	echo "<option value='PITIBU' $sel[PITIBU]>".t("Piiritilasto/budjettivertailu")."</option>";
	echo "<option value='PIMY' $sel[PIMY]>".t("Piirimyynnit")."</option>";
	echo "<option value='PIMYAS' $sel[PIMYAS]>".t("Piirimyynnit asiakkaittain")."</option>";
	echo "</select></td></tr>";

	echo "<tr><th>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td></tr>";

	echo "<tr><th>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>".t("Rajaukset")."</th><td colspan='3'>";

	$monivalintalaatikot = array('ASIAKASPIIRI','<br>OSASTO','TRY');

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";

	if ($yhtiorow["konserni"] != "") {
		$chk = "";
		if ($konserni != "") $chk = "CHECKED";

		echo "<tr><th>".t("Näytä kaikki konserniyhtiöt")."</th><td colspan='3'><input type='checkbox' name='konserni' $chk></td></tr>";
	}

	echo "</table>";
	echo "<br><br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";

	echo "<br>";


	if ($tee != '' and isset($painoinnappia)) {

		$q_yhtio = "";

		if ($konserni != "") {
			// haetaan konsernin kaikki yhtiot ja tehdään mysql lauseke muuttujaan yhtiot
			$query = "	SELECT group_concat(concat('\'',yhtio,'\'')) yhtiot
						from yhtio
						where konserni = '$yhtiorow[konserni]'
						and konserni != ''";
			$result = mysql_query($query) or pupe_error($query);
			$rivi = mysql_fetch_assoc($result);

			if ($rivi["yhtiot"] != "") {
				$yhtiot = $rivi["yhtiot"];
			}
			else {
				$yhtiot = $kukarow["yhtio"];
			}

			$q_yhtio = "lasku.yhtio,";

		}
		else {
			$yhtiot = "'{$kukarow["yhtio"]}'";
		}

		// korjataan hieman monivalintalaatikon paluttamaa muuttujaa, koska tässä tiedot luetaan laskulta ja tilausriviltä
		$lisa = str_ireplace("asiakas.", "lasku.", $lisa);
		$lisa = str_ireplace("tuote.", "tilausrivi.", $lisa);

		echo "<table>";
		echo "<tr>";

		if ($konserni != "") echo "<th>".t("Yhtiö")."</th>";

		if ($rappari == "PITIBU") {

			$edellisvuosi = $vva-1;
			$toissavuosi  = $vva-2;

			// Piiritilasto
			echo "<th>".t("Osasto")."</th>";
			echo "<th>".t("Tuoteryhmä")."</th>";
			echo "<th>".t("Piiri")."</th>";
			echo "<th>".t("Myynti")."<br>$toissavuosi</th>";
			echo "<th>".t("Kate")."<br>$toissavuosi</th>";
			echo "<th>".t("Myynti")."<br>$edellisvuosi</th>";
			echo "<th>".t("Kate")."<br>$edellisvuosi</th>";
			echo "<th>".t("Myynti")."<br>".t("aikavälillä")."</th>";
			echo "<th>".t("Kate")."<br>".t("aikavälillä")."</th>";
			echo "<th>".t("Myynti")."<br>$vva</th>";
			echo "<th>".t("Kate")."<br>$vva</th>";
			echo "<th>Budjetti</th>";
			echo "<th>Budjetti</th>";
			echo "<th>Budjetti</th>";
			echo "<th>Budjetti</th>";

			$query = "	SELECT
						$q_yhtio
						tilausrivi.osasto,
						tilausrivi.try,
						lasku.piiri,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)), 2) aikavalilla,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate, 0)), 2) kate_aikavalilla,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-01-01' and tilausrivi.laskutettuaika <= '{$vva}-12-31', tilausrivi.rivihinta, 0)), 2) summaVA,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-01-01' and tilausrivi.laskutettuaika <= '{$vva}-12-31', tilausrivi.kate, 0)), 2) kateVA,
						round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.rivihinta, 0)), 2) edvuodenmyynti,
						round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.kate, 0)), 2) edvuodenkate,
						round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.rivihinta, 0)), 2) toissavuodenmyynti,
						round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.kate, 0)), 2) toissavuodenkate";

			$q_lisa = "	GROUP BY $q_yhtio tilausrivi.osasto,tilausrivi.try,lasku.piiri+0
						ORDER BY $q_yhtio tilausrivi.osasto,tilausrivi.try,lasku.piiri+0";


			$q_alku  = "$toissavuosi-1-1";
			$q_loppu = "$vvl-$kkl-$ppl";
		}
		elseif ($rappari == "PIMY") {

			// Edellinen vuosi
			$vvaa = $vva - 1;
			$vvll = $vvl - 1;

			// Piirimyynnit piireittäin
			if (stripos($lisa, "tilausrivi.osasto") !== FALSE or stripos($lisa, "tilausrivi.try") !== FALSE) {
				echo "<th>".t("Osasto")."</th>";
				echo "<th>".t("Tuoteryhmä")."</th>";

				$q_yhtio .= "tilausrivi.osasto,tilausrivi.try,";
			}
			echo "<th>".t("Piiri")."</th>";
			echo "<th>".t("Myyntinyt")."</th>";
			echo "<th>".t("Myyntied")."</th>";
			echo "<th>".t("Myyntiind")."</th>";
			echo "<th>".t("Katenyt")."</th>";
			echo "<th>".t("Kateed")."</th>";
			echo "<th>".t("Kateind")."</th>";

			$query = "	SELECT
						$q_yhtio
						lasku.piiri,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)),2) myyntinyt,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)),2) myyntied,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)),2) katenyt,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)),2) kateed";

			$q_lisa = "	GROUP BY $q_yhtio lasku.piiri+0
						ORDER BY $q_yhtio lasku.piiri+0";

			$q_alku  = "$vvaa-$kka-$ppa";
			$q_loppu = "$vvl-$kkl-$ppl";
		}
		elseif ($rappari == "PIMYAS") {

			// Edellinen vuosi
			$vvaa = $vva - 1;
			$vvll = $vvl - 1;

			// Piirimyynnit asiakkaittain
			if (stripos($lisa, "tilausrivi.osasto") !== FALSE or stripos($lisa, "tilausrivi.try") !== FALSE) {
				echo "<th>".t("Osasto")."</th>";
				echo "<th>".t("Tuoteryhmä")."</th>";

				$q_yhtio .= "tilausrivi.osasto,tilausrivi.try,";
			}
			echo "<th>".t("Piiri")."</th>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Nimitark")."</th>";
			echo "<th>".t("Myynti nyt")."</th>";
			echo "<th>".t("Myynti ed")."</th>";
			echo "<th>".t("Indeksi Myynti")."</th>";
			echo "<th>".t("Kate nyt")."</th>";
			echo "<th>".t("Kate ed")."</th>";
			echo "<th>".t("Indeksi Kate")."</th>";

			$query = "	SELECT
						$q_yhtio
						lasku.ytunnus,
						lasku.nimi,
						lasku.nimitark,
						lasku.piiri,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate,0)),2) kateed,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.rivihinta,0)),2) myyntied,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',  tilausrivi.kate,0)),2) katenyt,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',  tilausrivi.rivihinta,0)),2) myyntinyt";

			$q_lisa = "	GROUP BY $q_yhtio lasku.piiri+0,lasku.ytunnus,lasku.nimi,lasku.nimitark
						ORDER BY $q_yhtio lasku.piiri+0,lasku.ytunnus,lasku.nimi,lasku.nimitark";

			$q_alku  = "$vvaa-$kka-$ppa";
			$q_loppu = "$vvl-$kkl-$ppl";
		}
		else {
			echo "VIRHE!";
			require ("inc/footer.inc");
			exit;
		}

		echo "</tr>";

		$query .= "	FROM lasku
					JOIN tilausrivi on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
					WHERE lasku.yhtio  in ($yhtiot)
					and lasku.tila	 	= 'U'
					and lasku.alatila 	= 'X'
					$lisa
					and	lasku.tapvm 	>= '$q_alku'
					and	lasku.tapvm 	<= '$q_loppu'
					$q_lisa";
		$eresult = pupe_query($query);

		$myyntinyt = 0;
		$myyntied  = 0;
		$katenyt   = 0;
		$kateed    = 0;
		$edpiiri   = "";
		$osatry 	= FALSE;

		if ($rappari == "PITIBU" or stripos($lisa, "tilausrivi.osasto") !== FALSE or stripos($lisa, "tilausrivi.try") !== FALSE) {
			$osatry = TRUE;
		}

		while ($row = mysql_fetch_assoc($eresult)) {
			// tarvitaanko osaston ja tuoteryhmän nimet
			if ($osatry) {
				$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
				$osastorow = mysql_fetch_assoc($osastores);

				if ($osastorow['selitetark'] != "") $row['osasto'] = $osastorow['selitetark'];

				$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
				$tryrow = mysql_fetch_assoc($tryres);

				if ($tryrow['selitetark'] != "") $row['try'] = $tryrow['selitetark'];
			}


			if (!$osatry and $rappari == "PIMYAS" and $row["piiri"] != $edpiiri and $edpiiri != "") {

				$myyntiind = 0;

				if ($myyntied != 0) {
					$myyntiind = round($myyntinyt / $myyntied, 1);
				}

				$kateind = 0;

				if ($kateed != 0) {
					$kateind = round($katenyt / $kateed, 1);
				}

				///* Edellinen piiri yhteensä *///
				echo "<tr>";
				if ($konserni != "") echo "<td class='spec'></td>";
				echo "<td class='spec'>$edpiiri</td>";
				echo "<td class='spec'></td>";
				echo "<td class='spec'></td>";
				echo "<td class='spec'></td>";
				echo "<td class='spec' align='right'>$myyntinyt</td>";
				echo "<td class='spec' align='right'>$myyntied</td>";
				echo "<td class='spec' align='right'>$myyntiind</td>";
				echo "<td class='spec' align='right'>$katenyt</td>";
				echo "<td class='spec' align='right'>$kateed</td>";
				echo "<td class='spec' align='right'>$kateind</td>";
				echo "</tr>";

				$myyntinyt = 0;
				$myyntied  = 0;
				$katenyt   = 0;
				$kateed    = 0;
			}

			echo "<tr>";

			if ($konserni != "") echo "<td>$row[yhtio]</td>";

			if ($rappari == "PITIBU") {
				// riviotsikoita
				echo "<td>$osastorow[selitetark]</td>";
				echo "<td>$tryrow[selitetark] </td>";
				echo "<td>$row[piiri]</td>";
				echo "<td align='right'>$row[toissavuodenmyynti]</td>";
				echo "<td align='right'>$row[toissavuodenkate]</td>";
				echo "<td align='right'>$row[edvuodenmyynti]</td>";
				echo "<td align='right'>$row[edvuodenkate]</td>";
				echo "<td align='right'>$row[aikavalilla]</td>";
				echo "<td align='right'>$row[kate_aikavalilla]</td>";
				echo "<td align='right'>$row[summaVA]</td>";
				echo "<td align='right'>$row[kateVA]</td>";
				echo "<td align='right'>tulossa</td>";
				echo "<td align='right'>tulossa</td>";
				echo "<td align='right'>tulossa</td>";
				echo "<td align='right'>tulossa</td>";
				echo "</tr>";
			}
			elseif ($rappari == "PIMY") {

				$myyntiind = 0;
				if ($row["myyntied"] != 0) $myyntiind = round($row["myyntinyt"] / $row["myyntied"], 1);

				$kateind = 0;
				if ($row["kateed"] != 0) $kateind = round($row["katenyt"] / $row["kateed"], 1);

				if ($osatry) {
					echo "<td>$row[osasto]</td>";
					echo "<td>$row[try]</td>";
				}
				echo "<td>$row[piiri]</td>";
				echo "<td align='right'>$row[myyntinyt]</td>";
				echo "<td align='right'>$row[myyntied]</td>";
				echo "<td align='right'>$myyntiind</td>";
				echo "<td align='right'>$row[katenyt]</td>";
				echo "<td align='right'>$row[kateed]</td>";
				echo "<td align='right'>$kateind</td>";
			}
			elseif ($rappari == "PIMYAS") {
				$myyntiind = 0;
				if ($row["myyntied"] != 0) $myyntiind = round($row["myyntinyt"] / $row["myyntied"], 1);

				$kateind = 0;
				if ($row["kateed"] != 0) $kateind = round($row["katenyt"] / $row["kateed"], 1);

				if ($osatry) {
					echo "<td>$row[osasto]</td>";
					echo "<td>$row[try]</td>";
				}
				echo "<td>$row[piiri]</td>";
				echo "<td>$row[ytunnus]</td>";
				echo "<td>$row[nimi]</td>";
				echo "<td>$row[nimitark]</td>";
				echo "<td align='right'>$row[myyntinyt]</td>";
				echo "<td align='right'>$row[myyntied]</td>";
				echo "<td align='right'>$myyntiind</td>";
				echo "<td align='right'>$row[katenyt]</td>";
				echo "<td align='right'>$row[kateed]</td>";
				echo "<td align='right'>$kateind</td>";
			}

			//Summat
			$edpiiri = $row["piiri"];

			$myyntinyt += $row['myyntinyt'];
			$myyntied  += $row['myyntied'];
			$katenyt   += $row['katenyt'];
			$kateed    += $row['kateed'];

			$kaikki_myyntinyt += $row['myyntinyt'];
			$kaikki_myyntied  += $row['myyntied'];
			$kaikki_katenyt   += $row['katenyt'];
			$kaikki_kateed    += $row['kateed'];

		}

		if ($rappari == "PIMYAS" or $rappari == "PIMY") {

			$myyntiind = 0;

			if ($myyntied != 0) {
				$myyntiind = round($kaikki_myyntinyt / $kaikki_myyntied, 1);
			}

			$kateind = 0;

			if ($kateed != 0) {
				$kateind = round($kaikki_katenyt / $kaikki_kateed, 1);
			}

			///* Yhteensä *///
			echo "<tr>";
			if ($osatry) {
				echo "<td class='spec'></td>";
				echo "<td class='spec'></td>";
			}
			if ($konserni != "") echo "<td class='spec'></td>";
			echo "<td class='spec'>".t("Yht").":</td>";
			if ($rappari == "PIMYAS") echo "<td class='spec'>$row[ytunnus] </td>";
			if ($rappari == "PIMYAS") echo "<td class='spec'>$row[nimi]</td>";
			if ($rappari == "PIMYAS") echo "<td class='spec'>$row[nimitark]</td>";
			echo "<td class='spec' align='right'>$kaikki_myyntinyt</td>";
			echo "<td class='spec' align='right'>$kaikki_myyntied</td>";
			echo "<td class='spec' align='right'>$myyntiind</td>";
			echo "<td class='spec' align='right'>$kaikki_katenyt</td>";
			echo "<td class='spec' align='right'>$kaikki_kateed</td>";
			echo "<td class='spec' align='right'>$kateind</td>";

			echo "</tr>";
		}


		echo "</table>";
	}

	require ("inc/footer.inc");
?>