<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	echo "<font class=head>".t("Piiritilasto")."</font><hr>";

	// käyttis
	echo "<form method='POST'>";
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
	echo "<option value='PITIBU' $sel[PITIBU]>".t("Piiritilasto/tavoitevertailu")."</option>";
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
	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";


	if ($tee != '' and isset($painoinnappia)) {

		$q_yhtio = "";

		if ($konserni != "") {
			// Haetaan konsernin kaikki yhtiot ja tehdään mysql lauseke muuttujaan yhtiot
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

		$myyntinyt = 0;
		$myyntied  = 0;
		$katenyt   = 0;
		$kateed    = 0;
		$edpiiri   = "";
		$q_osatry  = "";
		$osatry    = FALSE;

		// Korjataan hieman monivalintalaatikon paluttamaa muuttujaa, koska tässä tiedot luetaan laskulta ja tilausriviltä
		$lisa = str_ireplace("asiakas.", "lasku.", $lisa);
		$lisa = str_ireplace("tuote.", "tilausrivi.", $lisa);

		// Näytetäänkö osasto ja try
		if ($rappari == "PITIBU" or stripos($lisa, "tilausrivi.osasto") !== FALSE or stripos($lisa, "tilausrivi.try") !== FALSE) {
			$osatry = TRUE;
		}

		if (@include('Spreadsheet/Excel/Writer.php')) {
			// Keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Piiritilasto');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi = 0;
			$excelsarake = 0;
		}

		if ($rappari == "PITIBU") {
			// Piiritilasto ja budjetinvertailu
			$edellisvuosi = $vvl-1;
			$toissavuosi  = $vvl-2;

			$query = "	SELECT
						$q_yhtio
						lasku.piiri,
						tilausrivi.osasto,
						tilausrivi.try,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)), 2) aikavalilla,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate, 0)), 2) kate_aikavalilla,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vvl}-01-01' and tilausrivi.laskutettuaika <= '{$vvl}-12-31', tilausrivi.rivihinta, 0)), 2) summaVA,
						round(sum(if(tilausrivi.laskutettuaika >= '{$vvl}-01-01' and tilausrivi.laskutettuaika <= '{$vvl}-12-31', tilausrivi.kate, 0)), 2) kateVA,
						round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.rivihinta, 0)), 2) edvuodenmyynti,
						round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.kate, 0)), 2) edvuodenkate,
						round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.rivihinta, 0)), 2) toissavuodenmyynti,
						round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.kate, 0)), 2) toissavuodenkate";

			$q_lisa = "	GROUP BY $q_yhtio lasku.piiri+0,tilausrivi.osasto,tilausrivi.try
						ORDER BY $q_yhtio lasku.piiri+0,tilausrivi.osasto,tilausrivi.try";

			$q_alku  = "$toissavuosi-01-01";
			$q_loppu = "$vvl-$kkl-$ppl";
		}
		elseif ($rappari == "PIMY") {
			// Piirimynnit
			$vvaa = $vva - 1;
			$vvll = $vvl - 1;

			if ($osatry) {
				$q_osatry = ",tilausrivi.osasto,tilausrivi.try";
			}

			$query = "	SELECT
						$q_yhtio
						lasku.piiri,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)),2) myyntinyt,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)),2) myyntied,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)),2) katenyt,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)),2) kateed
						$q_osatry";

			$q_lisa = "	GROUP BY lasku.piiri+0 $q_osatry
						ORDER BY lasku.piiri+0 $q_osatry";

			$q_alku  = "$vvaa-$kka-$ppa";
			$q_loppu = "$vvl-$kkl-$ppl";
		}
		elseif ($rappari == "PIMYAS") {
			// Piirimyynnit asiakkaittain
			$vvaa = $vva - 1;
			$vvll = $vvl - 1;

			if ($osatry) {
				$q_osatry = ",tilausrivi.osasto,tilausrivi.try";
			}

			$query = "	SELECT
						$q_yhtio
						lasku.ytunnus,
						lasku.nimi,
						lasku.nimitark,
						lasku.piiri,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate,0)),2) kateed,
						round(sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.rivihinta,0)),2) myyntied,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',  tilausrivi.kate,0)),2) katenyt,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',  tilausrivi.rivihinta,0)),2) myyntinyt
						$q_osatry";

			$q_lisa = "	GROUP BY $q_yhtio lasku.piiri+0 $q_osatry,lasku.ytunnus,lasku.nimi,lasku.nimitark
						ORDER BY $q_yhtio lasku.piiri+0 $q_osatry,lasku.ytunnus,lasku.nimi,lasku.nimitark";

			$q_alku  = "$vvaa-$kka-$ppa";
			$q_loppu = "$vvl-$kkl-$ppl";
		}
		else {
			echo "VIRHE:";
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

		$rivimaara   = mysql_num_rows($eresult);
		$rivilimitti = 1000;

		echo "<table><tr>
			<th>",t("Valittu aikaväli"),"</th>
			<td>{$ppa}</td>
			<td>{$kka}</td>
			<td>{$vva}</td>
			<th>-</th>
			<td>{$ppl}</td>
			<td>{$kkl}</td>
			<td>{$vvl}</td>
			</tr></table><br><br>\n";

		if ($rivimaara > $rivilimitti) {
			echo "<br><font class='error'>",t("Hakutulos oli liian suuri"),"!</font><br>";
			echo "<font class='error'>",t("Tallenna/avaa tulos excelissä"),"!</font><br><br>";
		}
		else {

			echo "<table>";
			echo "<tr>";

			if ($konserni != "") {
				echo "<th>".t("Yhtiö")."</th>";
			}

			if ($rappari == "PITIBU") {
				echo "<th>".t("Piiri")."</th>";
				echo "<th>".t("Osasto")."</th>";
				echo "<th>".t("Tuoteryhmä")."</th>";
				echo "<th>".t("Myynti")."<br>$toissavuosi</th>";
				echo "<th>".t("Kate")."<br>$toissavuosi</th>";
				echo "<th>".t("Myynti")."<br>$edellisvuosi</th>";
				echo "<th>".t("Kate")."<br>$edellisvuosi</th>";
				echo "<th>".t("Myynti")."<br>".t("aikavälillä")."</th>";
				echo "<th>".t("Kate")."<br>".t("aikavälillä")."</th>";
				echo "<th>".t("Myynti")."<br>$vvl</th>";
				echo "<th>".t("Kate")."<br>$vvl</th>";
			}
			elseif ($rappari == "PIMY") {
				if ($osatry) {
					echo "<th>".t("Osasto")."</th>";
					echo "<th>".t("Tuoteryhmä")."</th>";
				}
				echo "<th>".t("Piiri")."</th>";
				echo "<th>".t("Myyntinyt")."</th>";
				echo "<th>".t("Myyntied")."</th>";
				echo "<th>".t("Myyntiind")."</th>";
				echo "<th>".t("Katenyt")."</th>";
				echo "<th>".t("Kateed")."</th>";
				echo "<th>".t("Kateind")."</th>";
			}
			elseif ($rappari == "PIMYAS") {
				if ($osatry) {
					echo "<th>".t("Osasto")."</th>";
					echo "<th>".t("Tuoteryhmä")."</th>";
				}
				echo "<th>".t("Piiri")."</th>";
				echo "<th>".t("Ytunnus")."</th>";
				echo "<th>".t("Nimi")."</th>";
				echo "<th>".t("Nimitark")."</th>";
				echo "<th>".t("Myyntinyt")."</th>";
				echo "<th>".t("Myyntied")."</th>";
				echo "<th>".t("Myyntiind")."</th>";
				echo "<th>".t("Katenyt")."</th>";
				echo "<th>".t("Kateed")."</th>";
				echo "<th>".t("Kateind")."</th>";
			}
		}

		if (isset($workbook)) {
			if ($konserni != "") {
				$worksheet->writeString($excelrivi, $excelsarake++, t("Yhtiö"));
			}

			if ($rappari == "PITIBU") {
				$worksheet->writeString($excelrivi, $excelsarake++, t("Piiri"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhmä"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." $toissavuosi");
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kate")." $toissavuosi");
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." $edellisvuosi");
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kate")." $edellisvuosi");
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." ".t("aikavälillä"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kate")." ".t("aikavälillä"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." $vvl");
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kate")." $vvl");

				$excelrivi++;
			}
			elseif ($rappari == "PIMY") {
				if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
				if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhmä"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Piiri"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntinyt"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntied"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntiind"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Katenyt"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kateed"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kateind"));

				$excelrivi++;
			}
			elseif ($rappari == "PIMYAS") {
				if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
				if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhmä"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Piiri"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Ytunnus"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Nimi"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Nimitark"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntinyt"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntied"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntiind"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Katenyt"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kateed"));
				$worksheet->writeString($excelrivi, $excelsarake++, t("Kateind"));

				$excelrivi++;
			}
		}

		while ($row = mysql_fetch_assoc($eresult)) {
			// Tarvitaanko osaston ja tuoteryhmän nimet
			if ($osatry) {
				$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
				$osastorow = mysql_fetch_assoc($osastores);

				if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

				$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
				$tryrow = mysql_fetch_assoc($tryres);

				if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];
			}

			// Yhteensäsumma per piiri
			if ($rivimaara <= $rivilimitti and !$osatry and $rappari == "PIMYAS" and $row["piiri"] != $edpiiri and $edpiiri != "") {

				$myyntiind = 0;
				if ($myyntied != 0) $myyntiind = round($myyntinyt / $myyntied, 1);

				$kateind = 0;
				if ($kateed != 0) $kateind = round($katenyt / $kateed, 1);

				echo "<tr>";
				if ($konserni != "") echo "<td class='spec'></td>";
				if ($osatry) echo "<td class='spec'></td>";
				if ($osatry) echo "<td class='spec'></td>";
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

			if ($rivimaara <= $rivilimitti) echo "<tr>";

			$excelsarake = 0;

			if ($konserni != "") {
				if ($rivimaara <= $rivilimitti) echo "<td>$row[yhtio]</td>";
				$worksheet->writeString($excelrivi, $excelsarake++, $row["yhtio"]);
			}

			if ($rappari == "PITIBU") {
				if ($rivimaara <= $rivilimitti) {
					echo "<td>$row[piiri]</td>";
					echo "<td>$row[osasto]</td>";
					echo "<td>$row[try] </td>";
					echo "<td align='right'>$row[toissavuodenmyynti]</td>";
					echo "<td align='right'>$row[toissavuodenkate]</td>";
					echo "<td align='right'>$row[edvuodenmyynti]</td>";
					echo "<td align='right'>$row[edvuodenkate]</td>";
					echo "<td align='right'>$row[aikavalilla]</td>";
					echo "<td align='right'>$row[kate_aikavalilla]</td>";
					echo "<td align='right'>$row[summaVA]</td>";
					echo "<td align='right'>$row[kateVA]</td>";
					echo "</tr>";
				}

				if (isset($workbook)) {
					$worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["piiri"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["toissavuodenmyynti"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["toissavuodenkate"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["edvuodenmyynti"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["edvuodenkate"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["aikavalilla"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["kate_aikavalilla"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["summaVA"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["kateVA"]);

					$excelrivi++;
				}
			}
			elseif ($rappari == "PIMY") {
				$myyntiind = 0;
				if ($row["myyntied"] != 0) $myyntiind = round($row["myyntinyt"] / $row["myyntied"], 1);

				$kateind = 0;
				if ($row["kateed"] != 0) $kateind = round($row["katenyt"] / $row["kateed"], 1);


				if ($rivimaara <= $rivilimitti) {
					if ($osatry) echo "<td>$row[osasto]</td>";
					if ($osatry) echo "<td>$row[try]</td>";
					echo "<td>$row[piiri]</td>";
					echo "<td align='right'>$row[myyntinyt]</td>";
					echo "<td align='right'>$row[myyntied]</td>";
					echo "<td align='right'>$myyntiind</td>";
					echo "<td align='right'>$row[katenyt]</td>";
					echo "<td align='right'>$row[kateed]</td>";
					echo "<td align='right'>$kateind</td>";
				}

				if (isset($workbook)) {
					if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
					if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["piiri"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntinyt"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntied"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $myyntiind);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["katenyt"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["kateed"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $kateind);

					$excelrivi++;
				}
			}
			elseif ($rappari == "PIMYAS") {
				$myyntiind = 0;
				if ($row["myyntied"] != 0) $myyntiind = round($row["myyntinyt"] / $row["myyntied"], 1);

				$kateind = 0;
				if ($row["kateed"] != 0) $kateind = round($row["katenyt"] / $row["kateed"], 1);

				if ($rivimaara <= $rivilimitti) {
					if ($osatry) echo "<td>$row[osasto]</td>";
					if ($osatry) echo "<td>$row[try]</td>";
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

				if (isset($workbook)) {
					if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
					if ($osatry) $worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["piiri"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["ytunnus"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["nimi"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["nimitark"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntinyt"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntied"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $myyntiind);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["katenyt"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["kateed"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $kateind);

					$excelrivi++;
				}
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

		// vikan rivin kaikkiyhteensä
		if ($rivimaara <= $rivilimitti and $rappari == "PIMYAS" or $rappari == "PIMY") {
			$myyntiind = 0;
			if ($myyntied != 0) $myyntiind = round($kaikki_myyntinyt / $kaikki_myyntied, 1);

			$kateind = 0;
			if ($kateed != 0) $kateind = round($kaikki_katenyt / $kaikki_kateed, 1);

			echo "<tr>";
			if ($osatry) echo "<td class='spec'></td>";
			if ($osatry) echo "<td class='spec'></td>";
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

		if ($rivimaara <= $rivilimitti) {
			echo "</table>";
		}

		echo "<br>";

		if (isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna excel").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Piiritilasto.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}

	require ("inc/footer.inc");
?>