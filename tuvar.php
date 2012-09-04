<?php

	require("inc/parametrit.inc");

	if ($livesearch_tee == "TUOTEHAKU") {
		livesearch_tuotehaku();
		exit;
	}

	// Liitetiedostot popup
	if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
		liite_popup("AK", $tuotetunnus, $width, $height);
	}
	else {
		liite_popup("JS");
	}

	if (function_exists("js_popup")) {
		echo js_popup(-100);
	}

	// Enaboidaan ajax kikkare
	enable_ajax();

	if ($tee == 'N' or $tee == 'E') {

		if ($tee == 'N') {
			$oper='>';
			$suun='';
		}
		else {
			$oper='<';
			$suun='desc';
		}

		$query = "	SELECT tuote.tuoteno
					FROM tuote use index (tuoteno_index)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno $oper '$tuoteno'
					and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
					ORDER BY tuote.tuoteno $suun
					LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$trow = mysql_fetch_assoc ($result);
			$tuoteno = $trow['tuoteno'];
			$tee='Z';
		}
		else {
			$varaosavirhe = t("Yht��n tuotetta ei l�ytynyt")."!";
			$tuoteno = '';
			$tee='Y';
		}
	}


	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

	if (($tee == 'Z') and ($tyyppi == '')) {
		require "inc/tuotehaku.inc";
	}
	if (($tee == 'Z') and ($tyyppi != '')) {

		if ($tyyppi == 'TOIMTUOTENO') {

			$query = "	SELECT tuotteen_toimittajat.tuoteno, sum(saldo) saldo, status
						FROM tuotteen_toimittajat
						JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.status NOT IN ('P','X')
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.toim_tuoteno = '$tuoteno'
						GROUP BY tuotteen_toimittajat.tuoteno
						HAVING status NOT IN ('P','X') or saldo > 0
						ORDER BY tuote.tuoteno";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				$varaosavirhe = t("VIRHE: Tiedolla ei l�ytynyt tuotetta")."!";
				$tee = 'Y';
			}
			elseif (mysql_num_rows($result) > 1) {
				$varaosavirhe = t("VIRHE: Tiedolla l�ytyi useita tuotteita")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_assoc($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
		elseif ($tyyppi != '') {
			$query = "	SELECT tuoteno
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and laji='$tyyppi'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) != 1) {
				$varaosavirhe = t("VIRHE: Tiedolla ei l�ytynyt tuotetta")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_assoc($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
	}

	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";

	echo "<br>";
	echo "<table>";

	echo "<tr>";
	echo "<form method='post' name='formi' autocomplete='off'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='tee' value='Z'>";
	echo "<th style='vertical-align:middle;'>".t("Tuotehaku")."</th>";
	echo "<td>".livesearch_kentta("formi", "TUOTEHAKU", "tuoteno", 300)."</td>";
	echo "<td class='back'>";
	echo "<input type='Submit' value='".t("Hae")."'></form></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<form method='post' name='formi2' autocomplete='off'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='tee' value='Z'>";

	echo "<th style='vertical-align:middle;'>";
	echo "<input type='hidden' name='tyyppi' value='TOIMTUOTENO'>";
	echo t("Toimittajan tuotenumero");
	echo "</th>";

	echo "<td>";
	echo "<input type='text' name='tuoteno' value='' style='width:300px;'>";
	echo "</td>";

	echo "<td class='back'>";
	echo "<input type='Submit' value='".t("Hae")."'>";
	echo "</form>";
	echo "</td>";

	//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
	if ($ulos == '' and $tee == 'Z') {
		echo "<form method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value=''>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Edellinen")."'>";
		echo "</td>";
		echo "</form>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='tyyppi' value=''>";
		echo "<input type='hidden' name='tee' value='N'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Seuraava")."'>";
		echo "</td>";
		echo "</form>";
	}

	echo "</tr></table><br>";

	//tuotteen varastostatus
	if ($tee == 'Z') {
		$query = "	SELECT tuote.*, date_format(tuote.muutospvm, '%Y-%m-%d') muutos, date_format(tuote.luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin
					FROM tuote
					LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$tuoteno'
					GROUP BY tuote.tuoteno";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$tuoterow = mysql_fetch_assoc($result);

			//korvaavat tuotteet
			$query  = "SELECT * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = pupe_query($query);

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";
			echo "<tr><th>".t("Tuoteno")."</th><th colspan='5'>".t("Nimitys")."</th>";
			echo "<tr><td>$tuoterow[tuoteno]</td><td colspan='5'>".t_tuotteen_avainsanat($tuoterow, 'nimitys')."</td></tr>";

			echo "<tr><th>".t("Osasto/Try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhm�")."</th><th>".t("T�hti")."</th><th colspan='2'>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto] - ".t_avainsana("OSASTO", "", "and avainsana.selite='$tuoterow[osasto]'", "", "", "selitetark")."<br>$tuoterow[try] - ".t_avainsana("TRY", "", "and avainsana.selite='$tuoterow[try]'", "", "", "selitetark")."</td>";

			if ($yhtiorow["vak_kasittely"] != "" and $tuoterow["vakkoodi"] != "" and $tuoterow["vakkoodi"] != "0") {
				$query = "	SELECT tunnus, concat_ws(' / ', concat('UN',yk_nro), nimi_ja_kuvaus, luokka, luokituskoodi, pakkausryhma, lipukkeet, rajoitetut_maarat_ja_poikkeusmaarat_1) vakkoodi
							FROM vak
							WHERE yhtio = '{$kukarow['yhtio']}'
							and tunnus  = '{$tuoterow['vakkoodi']}'";
				$vak_res = pupe_query($query);
				$vak_row = mysql_fetch_assoc($vak_res);

				$tuoterow["vakkoodi"] = $vak_row["vakkoodi"];
			}

			echo "<td>$tuoterow[toimittaja]</td><td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td colspan='2'>$tuoterow[vakkoodi]</td></tr>";

			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Nettohinta")."</th><th colspan='3'>".t("Viimeksi tullut")."</th>";
			echo "<tr><td>$tuoterow[toim_tuoteno]</td><td>";

			if ($kukarow['hinnat'] >= 0) echo $tuoterow["myyntihinta"];

			echo "</td><td>";

			if ($kukarow['hinnat'] >= 0 and $tuoterow["nettohinta"] != 0) echo $tuoterow["nettohinta"];

			echo "</td><td colspan='3'>".tv1dateconv($tuoterow["vihapvm"])."</td></tr>";

			echo "<tr><th>".t("H�lyraja")."</th><th>".t("Tiler�")."</th><th>".t("Toier�")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td>$tuoterow[halytysraja]</td><td>$tuoterow[osto_era]</td><td>$tuoterow[myynti_era]</td><td>$tuoterow[tuotekerroin]</td><td>$tuoterow[tarrakerroin]</td><td>$tuoterow[tarrakpl]</td></tr>";
			echo "</table><br>";

			// Onko liitetiedostoja
			$liitteet = liite_popup("TN", $tuoterow["tunnus"]);

			if ($liitteet != "") {
				echo "<font class='message'>".t("Liitetiedostot")."</font><hr>";
				echo "$liitteet<br><br>";
			}

			// Varastosaldot ja paikat
			echo "<table><tr><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'>";

			if ($tuoterow["ei_saldoa"] == '') {

				$yhtiot = array();
				$yhtiot[] = $kukarow["yhtio"];

				// Halutaanko saldot koko konsernista?
				if ($yhtiorow["haejaselaa_konsernisaldot"] == "S") {
					$query = "	SELECT *
								FROM yhtio
								WHERE konserni = '$yhtiorow[konserni]'
								AND konserni != ''
								AND yhtio != '$kukarow[yhtio]'";
					$result = pupe_query($query);

					while ($row = mysql_fetch_assoc($result)) {
						$yhtiot[] = $row["yhtio"];
					}
				}

				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Varastopaikat")."</font><hr>";

				// Saldot
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Varasto")."</th>";
				echo "<th>".t("Varastopaikka")."</th>";
				echo "<th>".t("Saldo")."</th>";
				echo "<th>".t("Hyllyss�")."</th>";
				echo "</tr>";

				$kokonaissaldo = 0;
				$kokonaishyllyssa = 0;
				$kokonaissaldo_tapahtumalle = 0;

				//saldot per varastopaikka
				if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F" or $tuoterow["sarjanumeroseuranta"] == "G") {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
								and tuote.tuoteno = '$tuoteno'
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				else {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
								and tuote.tuoteno = '$tuoteno'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}

				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_assoc ($sresult)) {

						list($saldo, $hyllyssa,) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $saldoaikalisa, $saldorow["era"]);

						//summataan kokonaissaldoa ja vain oman firman saldoa
						$kokonaissaldo += $saldo;
						$kokonaishyllyssa += $hyllyssa;

						if ($saldorow["yhtio"] == $kukarow["yhtio"]) {
							$kokonaissaldo_tapahtumalle += $saldo;
						}

						echo "<tr>";
						echo "<td>$saldorow[nimitys] $saldorow[tyyppi] $saldorow[era]</td>";

						if ($saldorow["hyllyalue"] == "!!M") {
							$asiakkaan_tunnus = (int) $saldorow["hyllynro"].$saldorow["hyllyvali"].$saldorow["hyllytaso"];

							$query = "	SELECT nimi, toim_nimi
										FROM asiakas
										WHERE yhtio = '{$kukarow["yhtio"]}'
										AND tunnus = '$asiakkaan_tunnus'";
							$asiakasresult = pupe_query($query);
							$asiakasrow = mysql_fetch_assoc($asiakasresult);
							echo "<td>{$asiakasrow["nimi"]}</td>";
						}
						else {
							echo "<td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
						}

						echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>
								<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
								</tr>";
					}
				}

				list($saldo, $hyllyssa,) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);

				if ($saldo != 0) {
					echo "<tr>";
					echo "<td>".t("Tuntematon")."</td>";
					echo "<td>?</td>";
					echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>";
					echo "<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>";
					echo "</tr>";

					//summataan kokonaissaldoa ja vain oman firman saldoa.
					$kokonaissaldo += $saldo;
					$kokonaishyllyssa += $hyllyssa;
				}

				echo "<tr>
						<th colspan='2'>".t("Yhteens�")."</th>
						<th style='text-align:right;'>".sprintf("%.2f", $kokonaissaldo)."</th>
						<th style='text-align:right;'>".sprintf("%.2f", $kokonaishyllyssa)."</th>
						</tr>";

				echo "</table>";
			}

			echo "</td><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'v>";

			//korvaavat tuotteet
			$query  = "SELECT * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = pupe_query($query);

			if (mysql_num_rows($korvaresult) > 0) {
				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Korvaavat tuotteet")."</font><hr>";

				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Tuotenumero")."</th>";
				echo "<th>".t("Saldo")."</th>";
				echo "</tr>";

				// tuote l�ytyi, joten haetaan sen id...
				$row    = mysql_fetch_assoc($korvaresult);
				$id		= $row['id'];

				$query = "SELECT * FROM korvaavat WHERE id='$id' AND tuoteno<>'$tuoteno' AND yhtio='$kukarow[yhtio]' ORDER BY jarjestys, tuoteno";
				$korva2result = pupe_query($query);

				while ($row = mysql_fetch_assoc($korva2result)) {
					list($saldo, $hyllyssa,) = saldo_myytavissa($row["tuoteno"], '', '', '', '', '', '', '', '', $saldoaikalisa);

					echo "<tr>";
					echo "<td><a href='$PHP_SELF?toim=$toim&tee=Z&tuoteno=".urlencode($row["tuoteno"])."&lopetus=$lopetus'>$row[tuoteno]</a></td>";
					echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>";
					echo "</tr>";
				}

				echo "</table>";
			}

			echo "</td><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'>";

			//vastaavat tuotteet
			$query  = "SELECT * FROM vastaavat WHERE tuoteno='$tuoteno' AND yhtio='$kukarow[yhtio]'";
			$vastaresult = pupe_query($query);

			if (mysql_num_rows($vastaresult) > 0) {
				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Vastaavat tuotteet")."</font><hr>";

				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Tuotenumero")."</th>";
				echo "<th>".t("Saldo")."</th>";
				echo "</tr>";

				// tuote l�ytyi, joten haetaan sen id...
				$row    = mysql_fetch_assoc($vastaresult);
				$id		= $row['id'];

				$query = "SELECT * FROM vastaavat WHERE id='$id' AND tuoteno<>'$tuoteno' AND yhtio='$kukarow[yhtio]' ORDER BY jarjestys, tuoteno";
				$vasta2result = pupe_query($query);

				while ($row = mysql_fetch_assoc($vasta2result)) {
					list($saldo, $hyllyssa,) = saldo_myytavissa($row["tuoteno"], '', '', '', '', '', '', '', '', $saldoaikalisa);

					echo "<tr>";
					echo "<td><a href='$PHP_SELF?toim=$toim&tee=Z&tuoteno=".urlencode($row["tuoteno"])."&lopetus=$lopetus'>$row[tuoteno]</a></td>";
					echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>";
					echo "</tr>";
				}

				echo "</table>";
			}

			echo "</td></tr></table><br>";

			$ale_query_lisa = generoi_alekentta('M');

			$tyyppilisa = ($toim == "EDUSTAJA") ? "	and tilausrivi.tyyppi in ('L','E','O','G','V','W','M') " : " and tilausrivi.tyyppi = 'G' ";

			// Tilausrivit t�lle tuotteelle
			$query = "	SELECT if (asiakas.ryhma != '', concat(lasku.nimi,' (',asiakas.ryhma,')'), lasku.nimi) nimi, lasku.tunnus, (tilausrivi.varattu+tilausrivi.jt) kpl,
						if (tilausrivi.tyyppi!='O' and tilausrivi.tyyppi!='W', tilausrivi.kerayspvm, tilausrivi.toimaika) pvm, tilausrivi.laadittu,
						varastopaikat.nimitys varasto, tilausrivi.tyyppi, lasku.laskunro, lasku.tila laskutila, lasku.tilaustyyppi, tilausrivi.var, lasku2.laskunro as keikkanro, tilausrivi.jaksotettu, tilausrivin_lisatiedot.osto_vai_hyvitys
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
						LEFT JOIN lasku as lasku2 ON lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.uusiotunnus
						LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						{$tyyppilisa}
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and tilausrivi.varattu + tilausrivi.jt != 0
						and tilausrivi.var != 'P'
						ORDER BY pvm, tunnus";
			$jtresult = pupe_query($query);

			if (mysql_num_rows($jtresult) != 0) {

				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Tuotteen tilaukset")."</font><hr>";

				$myyta = $kokonaismyytavissa;

				// Avoimet rivit
				echo "<table>";

				echo "<tr>
						<th>".t("Asiakas/Toimittaja")."</th>
						<th>".t("Tilaus/Saapuminen")."</th>
						<th>".t("Tyyppi")."</th>
						<th>".t("Luontiaika")."</th>
						<th>".t("Toim.aika")."</th>
						<th>".t("M��r�")."</th>
						<th>".t("Myyt�viss�")."</th>
						</tr>";

				$yhteensa 	= array();
				$ekotettiin = 0;

				while ($jtrow = mysql_fetch_assoc($jtresult)) {

					$tyyppi 	 = "";
					$vahvistettu = "";
					$merkki 	 = "";
					$keikka 	 = "";

					if ($jtrow["tyyppi"] == "O") {
						$tyyppi = t("Ostotilaus");
						$merkki = "+";
					}
					elseif ($jtrow["tyyppi"] == "E") {
						$tyyppi = t("Ennakkotilaus");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "G" and $jtrow["tilaustyyppi"] == "S") {
						$tyyppi = t("Sis�inen ty�m��r�ys");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "G") {
						$tyyppi = t("Varastosiirto");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "V") {
						$tyyppi = t("Kulutus");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
						$tyyppi = t("J�lkitoimitus");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0 and $jtrow["osto_vai_hyvitys"] == "H") {
						// Marginaalioston hyvitys
						$tyyppi = t("K�ytetyn tavaran hyvitys");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0) {
						// Normimyynti
						$tyyppi = t("Myynti");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0 and $jtrow["osto_vai_hyvitys"] != "O") {
						// Normihyvitys
						$tyyppi = t("Hyvitys");
						$merkki = "+";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0 and $jtrow["osto_vai_hyvitys"] == "O") {
						// Marginaaliosto
						$tyyppi = t("K�ytetyn tavaran osto");
						$merkki = "+";
					}
					elseif (($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "W") {
						$tyyppi = t("Valmistus");
						$merkki = "+";
					}
					elseif (($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "V") {
						$tyyppi = t("Asiakkaallevalmistus");
						$merkki = "+";
					}

					$yhteensa[$tyyppi] += $jtrow["kpl"];

					if ($jtrow["varasto"] != "") {
						$tyyppi = $tyyppi." - ".$jtrow["varasto"];
					}

					if ((int) str_replace("-", "", $jtrow["pvm"]) > (int) date("Ymd") and $ekotettiin == 0) {
						echo "<tr>
								<td colspan='6' align='right' class='spec'>".t("Myyt�viss� nyt").":</td>
								<td align='right' class='spec'>".sprintf('%.2f', $myyta)."</td>
								</tr>";
						$ekotettiin = 1;
					}

					list(, , $myyta) = saldo_myytavissa($tuoteno, "KAIKKI", '', '', '', '', '', '', '', $jtrow["pvm"]);

					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td>$jtrow[tunnus]</td>
							<td>$tyyppi</td>
							<td>".tv1dateconv($jtrow["laadittu"])."</td>
							<td>".tv1dateconv($jtrow["pvm"])."$vahvistettu</td>
							<td align='right'>$merkki".abs($jtrow["kpl"])."</td>
							<td align='right'>".sprintf('%.2f', $myyta)."</td>
							</tr>";
				}

				foreach ($yhteensa as $type => $kappale) {
					echo "<tr>";
					echo "<th colspan='5'>$type ".t("yhteens�")."</th>";
					echo "<th style='text-align:right;'>$kappale</th>";
					echo "<th></th>";
					echo "</tr>";
				}

				echo "</table><br>";
			}

			echo "</td></tr><tr><td class='back' valign='top'><br>";
			echo "<table>";
			echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";

			if ($historia == "") $historia=1;
			$chk[$historia] = "SELECTED";
			
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

			echo "<a href='#' name='Tapahtumat'>";

			echo "<tr>";
			echo "<th colspan='2'>".t("N�yt� tapahtumat").": ";
			echo "<select name='historia' onchange='submit();'>'";
			echo "<option value='1' $chk[1]> ".t("20 viimeisint�")."</option>";
			echo "<option value='2' $chk[2]> ".t("Tilivuoden alusta")."</option>";
			echo "<option value='3' $chk[3]> ".t("L�hes kaikki")."</option>";
			echo "</select>";
			echo "</th>";
			echo "<th colspan='2'></th></tr>";

			echo "<tr>";
			echo "<th>".t("K�ytt�j�@Pvm")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("M��r�")."</th>";
			echo "<th>".t("Selite")."";

			echo "</th></form>";
			echo "</tr>";


			//tapahtumat
			if ($historia == '1' or $historia == '') {
				$maara = "LIMIT 20";
				$ehto = ' and tapahtuma.laadittu >= date_sub(now(), interval 6 month)';
			}
			if ($historia == '2') {
				$maara = "";
				$ehto = " and tapahtuma.laadittu > '$yhtiorow[tilikausi_alku]'";
			}
			if ($historia == '3') {
				$maara = "LIMIT 2500";
				$ehto = "";
			}
			
			$lajilisa = ($toim == "EDUSTAJA") ? "" : " and tapahtuma.laji = 'siirto' ";
			
			$query = "	SELECT concat_ws('@', tapahtuma.laatija, tapahtuma.laadittu) kuka, tapahtuma.laji, tapahtuma.kpl, tapahtuma.kplhinta, tapahtuma.hinta,
						if(tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta, tapahtuma.hinta)*tapahtuma.kpl arvo, tapahtuma.selite, lasku.tunnus laskutunnus
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						LEFT JOIN tilausrivi use index (primary) ON tilausrivi.yhtio=tapahtuma.yhtio and tilausrivi.tunnus=tapahtuma.rivitunnus
						LEFT JOIN lasku use index (primary) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						and tapahtuma.tuoteno = '$tuoteno'
						and tapahtuma.laadittu > '0000-00-00 00:00:00'
						{$lajilisa}
						{$ehto}
						ORDER BY tapahtuma.laadittu desc $maara";
			$qresult = pupe_query($query);

			while ($prow = mysql_fetch_assoc ($qresult)) {
				echo "<tr>";
				echo "<td nowrap>$prow[kuka]</td>";
				echo "<td nowrap>";
				echo t("$prow[laji]");
				echo "</td>";
				echo "<td nowrap align='right'>$prow[kpl]</td>";
				
				$selite = preg_replace("/(\([0-9\.]*\)|\[[0-9\.]*\])/", "", $prow["selite"]);
				
				echo "<td>$selite</td>";
				echo "</tr>";
			}
			
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yht��n tuotetta ei l�ytynyt")."!<br></font>";
		}
		$tee = '';
	}

	if ($tee == "Y") {
			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse tuotenumero").":</th>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");

?>