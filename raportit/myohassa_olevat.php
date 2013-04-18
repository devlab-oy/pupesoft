<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;
	$usemastertoo = 1;

	if (isset($_POST["supertee"])) {
		if($_POST["supertee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("../inc/parametrit.inc");

	if (isset($supertee)) {
		if ($supertee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}

	echo "<font class='head'>".t("Myöhässä olevat myyntitilaukset")."</font><hr>";

	if ($ytunnus != '') {
		require ("inc/kevyt_toimittajahaku.inc");
	}

	if ($myovv == '') {
		$myopp = date("j");
		$myokk = date("n");
		$myovv = date("Y");
	}

	echo "<form name=asiakas method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value = 'HAE'>";
	echo "<table><tr>";
	echo "<th>".t("Anna toimituspäivä")."</th>";
	echo "<td><input type='text' name='myopp' value='$myopp' size='3'>";
	echo "<input type='text' name='myokk' value='$myokk' size='3'>";
	echo "<input type='text' name='myovv' value='$myovv' size='6'></td>";
	echo "</tr>";

	$kayta_ostotilausta_check = isset($kayta_ostotilausta) ? " checked='checked'" : '';

	echo "<tr><th>",t("Vertaa ostotilauksen toimituspäivämäärään"),"</th><td><input type='checkbox' name='kayta_ostotilausta'{$kayta_ostotilausta_check}></td></tr>";

	if (!isset($ytunnus)) {
		$ytunnus = '';
	}

	echo "<tr><th>".t("Toimittajan nimi")."</th><td><input type='text' size='10' name='ytunnus' value='$ytunnus'></td></tr>";

	echo "<tr><th>".t("Valitse tuoteryhmä")."</th>";

	$sresult = t_avainsana("TRY");

	echo "<td>";

	echo "<select name='mul_tuoteryhma[]' multiple='TRUE' size='10' style='width:100%;'>";

	$mul_check = '';
	if ($mul_tuoteryhma!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_tuoteryhma)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteryhmää")."</option>";

	while ($rivi = mysql_fetch_array($sresult)) {
		$mul_check = '';
		if ($mul_tuoteryhma!="") {
			if (in_array($rivi["selite"],$mul_tuoteryhma)) {
				$mul_check = 'SELECTED';
			}
		}

		echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>".t("Valitse kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'K'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='mul_kustannuspaikka[]' multiple='TRUE' size='10' style='width:100%;'>";

	$mul_check = '';
	if ($mul_kustannuspaikka!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_kustannuspaikka)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei kustannuspaikkaa")."</option>";

	while ($rivi = mysql_fetch_array($vresult)) {
		$mul_check = '';
		if ($mul_kustannuspaikka!="") {
			if (in_array($rivi[0],$mul_kustannuspaikka)) {
				$mul_check = 'SELECTED';
			}
		}

		echo "<option value='$rivi[0]' $mul_check>$rivi[1]</option>";
	}

	$vain_excelchk = "";
	if ($vain_excel != '') {
		$vain_excelchk = "CHECKED";
	}

	echo "</select></td></tr>";
	echo "<tr><th>".t("Raportti Exceliin")."</th>";
	echo "<td><input type='checkbox' name='vain_excel' $vain_excelchk></td><tr>";
	echo "<tr><td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";
	echo "</form></table><br>";

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
		require ("naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "HAE";
		$mul_tuoteryhma = unserialize(base64_decode($se_tuoteryhma));
		$mul_kustannuspaikka = unserialize(base64_decode($se_kustannuspaikka));
	}

	if ($tee == 'JARJESTA') {
		if ($suunta == '' or $suunta == "DESC") {
			$suunta = "ASC";
		}
		else {
			$suunta = "DESC";
		}

		$tee = "HAE";
		$mul_tuoteryhma = unserialize(base64_decode($se_tuoteryhma));
		$mul_kustannuspaikka = unserialize(base64_decode($se_kustannuspaikka));
	}

	if ($tee == "HAE") {

		if (is_array($mul_tuoteryhma) and count($mul_tuoteryhma) > 0) {
			$sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_tuoteryhma))."')";
			$lisa .= " and tuote.try in $sel_tuoteryhma ";
		}

		if (is_array($mul_kustannuspaikka) and count($mul_kustannuspaikka) > 0) {
			$sel_kustannuspaikka = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_kustannuspaikka))."')";
			$lisa .= " and (asiakas.kustannuspaikka in $sel_kustannuspaikka or tuote.kustp in $sel_kustannuspaikka) ";
		}

		$toimjoin = '';

		if ($toimittajaid != '') {
			$toimittajaid = (int) $toimittajaid;
			$toimjoin = " JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = lasku.yhtio AND tuotteen_toimittajat.liitostunnus = '$toimittajaid' and tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno) ";
			$mul_tuoteryhma = unserialize(base64_decode($mul_tuoteryhma));
			$mul_kustannuspaikka = unserialize(base64_decode($mul_kustannuspaikka));
		}

		$se_tuoteryhma = base64_encode(serialize($mul_tuoteryhma));
		$se_kustannuspaikka = base64_encode(serialize($mul_kustannuspaikka));


		echo "<table><tr>";

		if (isset($kayta_ostotilausta) and $kayta_ostotilausta != '') {
			echo "<th>".t("Tuoteno")."</th>";
			echo "<th>".t("Myynti Toimitusaika")."</th>";
			echo "<th>".t("Myydyt")."</th>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>",t("Osto Toimitusaika"),"</th>";
			echo "<th>",t("Tilattu"),"</th>";
		}
		else {
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Postitp")."</th>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Tuoteno")."</th>";
			echo "<th>".t("Nimike")."</th>";
			echo "<th>".t("Määrä")."</th>";
			echo "<th>".t("Yksikkö")."</th>";
			echo "<th>".t("Arvo")."</th>";
			echo "<th>".t("Myytävissä")."</th>";
			echo "<th><a href='?tee=JARJESTA&haku=toimaika&suunta=$suunta&tunnus=$tunnus&myovv=$myovv&myokk=$myokk&myopp=$myopp&se_tuoteryhma=$se_tuoteryhma&se_kustannuspaikka=$se_kustannuspaikka'>".t("Toimitusaika")."</a></th>";
			echo "<th>".t("Tila")."</th>";
		}
		echo "</tr>";

		if ($vain_excel != '') {

			include('inc/pupeExcel.inc');

			$worksheet 	 = new pupeExcel();
			$format_bold = array("bold" => TRUE);
			$excelrivi 	 = 0;

			if(isset($worksheet)) {
				$excelsarake = 0;

				if (isset($kayta_ostotilausta) and $kayta_ostotilausta != '') {
					$worksheet->write($excelrivi, $excelsarake, t("Tuoteno"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Myynti Toimitusaika"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Myydyt"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tilaus"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Asiakas"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Osto Toimitusaika"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tilattu"), $format_bold);
				}
				else {
					$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Asiakas"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Postitp"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tilaus"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tuoteno"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Nimike"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Määrä"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Yksikkö"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Arvo"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Myytävissä"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Toimitusaika"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tila"), $format_bold);
				}

				$excelsarake = 0;
				$excelrivi++;
			}
		}

		if (($myovv == "" or !is_numeric($myovv)) or ($myokk == "" or !is_numeric($myokk)) or ($myopp == "" or !is_numeric($myopp))) {
			$myovv = "0000";
			$myokk = "00";
			$myopp = "00";
		}

		$selectlisa = '';

		if ($kayta_ostotilausta == '') {
			$selectlisa = ", lasku.ytunnus,
			lasku.nimi,
			lasku.postitp,
			lasku.tunnus,
			tilausrivi.nimitys,
			sum(tilausrivi.varattu+tilausrivi.jt) myydyt,
			tilausrivi.yksikko,
			sum(tilausrivi.tilkpl * tilausrivi.hinta) arvo,
			lasku.tila,
			lasku.alatila";
		}
		else {
			$selectlisa = ", group_concat(tilausrivi.tunnus) tunnukset, sum(tilausrivi.varattu+tilausrivi.jt) myydyt";
		}

		$query = "	SELECT lasku.toimaika,
					tilausrivi.tuoteno
					$selectlisa
					FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
					JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tila IN ('L','N') and lasku.toimaika <= '$myovv-$myokk-$myopp')
					JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
					$toimjoin
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi != 'D'
					and tilausrivi.laskutettuaika = '0000-00-00'
					and tilausrivi.toimitettuaika = '0000-00-00'
					and tilausrivi.var != 'P'
					$lisa
					group by lasku.toimaika, tilausrivi.tuoteno
					ORDER BY lasku.toimaika $suunta";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<tr><td class='back'><font class='message'>",t("Yhtään tilausta ei löytynyt"),"!</font></td></tr>";
		}

		while ($tulrow = mysql_fetch_array($result)) {

			list(,, $myytavissa) = saldo_myytavissa($tulrow["tuoteno"], '', '', '', '', '', '', '', '', '');

			if ($yhtiorow['saldo_kasittely'] != '') {
				list(,, $myytavissa_tul) = saldo_myytavissa($tulrow["tuoteno"], '', '', '', '', '', '', '', '', $myovv."-".$myokk."-".$myopp);
			}

			if (isset($kayta_ostotilausta) and $kayta_ostotilausta != '') {

				if ($myytavissa > $tulrow['myydyt']) {
					continue;
				}

				$ostotilaus_varattu_kpl = 0;

				$query = "	SELECT *
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tuoteno = '{$tulrow['tuoteno']}'
							AND kpl = 0
							AND varattu != 0
							AND tyyppi = 'O'
							AND toimaika <= '{$tulrow['toimaika']}'
							ORDER BY toimaika $suunta";
				$ostotilausres = mysql_query($query) or pupe_error($query);

				while ($ostotilausrow = mysql_fetch_assoc($ostotilausres)) {
					$ostotilaus_varattu_kpl += $ostotilausrow['varattu'];
				}

				if ($ostotilaus_varattu_kpl > $ostotilausrow['varattu']) {
					continue;
				}

				$kpl_pvm = array();

				if ($ostotilaus_varattu_kpl == 0 or $tulrow['myydyt'] > $ostotilaus_varattu_kpl) {
					$query = "	SELECT *
								FROM tilausrivi
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$tulrow['tuoteno']}'
								AND kpl = 0
								AND varattu != 0
								AND tyyppi = 'O'
								AND toimaika >= '{$tulrow['toimaika']}'
								ORDER BY toimaika $suunta";
					$ostotilausres = mysql_query($query) or pupe_error($query);

					while ($ostotilausrow = mysql_fetch_assoc($ostotilausres)) {
						$ostotilaus_varattu_kpl += $ostotilausrow['varattu'];
						$kpl_pvm[$tulrow['tuoteno']][$ostotilausrow['toimaika']] += $ostotilausrow['varattu'];
					}
				}

				if ($ostotilaus_varattu_kpl == 0) {
					continue;
				}

				$query = "	SELECT *, varattu+jt varattu
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tunnus in ($tulrow[tunnukset])
							ORDER BY tilausrivi.toimaika $suunta";
				$myohastyneet_res = mysql_query($query) or pupe_error($query);

				while ($myohastyneet_row = mysql_fetch_assoc($myohastyneet_res)) {
					echo "<tr class='aktiivi'>";
					echo "<td>$tulrow[tuoteno]</td>";
					echo "<td>".tv1dateconv($tulrow["toimaika"])."</td>";
					echo "<td align='right'>$myohastyneet_row[varattu]</td>";
					echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$myohastyneet_row[tunnus]&myovv=$myovv&myokk=$myokk&myopp=$myopp&se_tuoteryhma=$se_tuoteryhma&se_kustannuspaikka=$se_kustannuspaikka&kayta_ostotilausta=$kayta_ostotilausta'>$myohastyneet_row[tunnus]</td></td>";
					echo "<td>$myohastyneet_row[ytunnus]</td>";
					echo "<td>$myohastyneet_row[nimi]</td>";

					if (isset($worksheet)) {
						$excelsarake = 0;

						$worksheet->write($excelrivi, $excelsarake, $tulrow["tuoteno"], $format_bold);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, tv1dateconv($tulrow["toimaika"]), $format_bold);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $myohastyneet_row["varattu"], $format_bold);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $myohastyneet_row["tunnus"], $format_bold);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $myohastyneet_row["ytunnus"], $format_bold);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $myohastyneet_row["nimi"], $format_bold);
						$excelsarake++;
					}

					$i = 0;
					foreach ($kpl_pvm[$myohastyneet_row['tuoteno']] as $ostotoimitusaika => $ostovarattu) {
						if ($i > 0) {
							if (isset($worksheet)) {
								$excelrivi++;
								$excelsarake = 0;

								$worksheet->write($excelrivi, $excelsarake, '', $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, '', $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, '', $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, '', $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, '', $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, '', $format_bold);
								$excelsarake++;

							}

							echo "<tr class='aktiivi'><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
						}

						echo "<td>".tv1dateconv($ostotoimitusaika)."</td>";
						echo "<td align='right'>$ostovarattu</td>";
						echo "</tr>";

						if (isset($worksheet)) {
							$worksheet->write($excelrivi, $excelsarake, tv1dateconv($ostotoimitusaika), $format_bold);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $ostovarattu, $format_bold);
						}

						$i++;
					}

					if (isset($worksheet)) {
						$excelrivi++;
					}

					echo "</tr>";
				}
			}
			else {

				if ($tulrow["alatila"] == 'X') {
					$laskutyyppi = t("Jälkitoimitus");
					$alatila	 = "";
				}
				else {
					$laskutyyppi = $tulrow["tila"];
					$alatila	 = $tulrow["alatila"];
					require ("inc/laskutyyppi.inc");
				}

				echo "<tr class='aktiivi'>";
				echo "<td>$tulrow[ytunnus]</td>";
				echo "<td>$tulrow[nimi]</td>";
				echo "<td>$tulrow[postitp]</td>";
				echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&myovv=$myovv&myokk=$myokk&myopp=$myopp&se_tuoteryhma=$se_tuoteryhma&se_kustannuspaikka=$se_kustannuspaikka&kayta_ostotilausta=$kayta_ostotilausta'>$tulrow[tunnus]</a></td>";
				echo "<td>$tulrow[tuoteno]</td>";
				echo "<td>$tulrow[nimitys]</td>";
				echo "<td align='right'>$tulrow[myydyt]</td>";
				echo "<td>".t_avainsana("Y", "", "and avainsana.selite='$tulrow[yksikko]'", "", "", "selite")."</td>";
				echo "<td align='right'>".hintapyoristys($tulrow["arvo"])."</td>";
				if ($yhtiorow['saldo_kasittely'] != '') {
					echo "<td align='right'>$myytavissa ($myytavissa_tul)</td>";
				}
				else {
					echo "<td align='right'>$myytavissa</td>";
				}
				echo "<td>".tv1dateconv($tulrow["toimaika"])."</td>";

				if ($tulrow['tila'] == "L" and $tulrow['alatila'] == "D") {
					echo "<td><font class='OK'>".t($laskutyyppi)."<br>".t($alatila)."</font></td>";
				}
				else {
					echo "<td>".t($laskutyyppi)."<br>".t($alatila)."</td>";
				}

				echo "</tr>";

				if(isset($worksheet)) {
					$excelsarake = 0;

					$worksheet->write($excelrivi, $excelsarake, $tulrow["ytunnus"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $tulrow["nimi"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $tulrow["postitp"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $tulrow["tunnus"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $tulrow["tuoteno"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $tulrow["nimitys"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, $tulrow["myydyt"], $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t_avainsana("Y", "", "and avainsana.selite='$tulrow[yksikko]'", "", "", "selite"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, hintapyoristys($tulrow["arvo"]), $format_bold);
					$excelsarake++;
					if ($yhtiorow['saldo_kasittely'] != '') {
						$worksheet->write($excelrivi, $excelsarake, $myytavissa ."(".$myytavissa_tul.")", $format_bold);
						$excelsarake++;
					}
					else {
						$worksheet->write($excelrivi, $excelsarake, $myytavissa, $format_bold);
						$excelsarake++;
					}

					$worksheet->write($excelrivi, $excelsarake, tv1dateconv($tulrow["toimaika"]), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t($laskutyyppi)."\n".t($alatila), $format_bold);

					$excelsarake = 0;
					$excelrivi++;
				}
			}
		}

		echo "</table>";

		if(isset($worksheet)) {

			// We need to explicitly close the worksheet
			$excelnimi = $worksheet->close();

			echo "<br><table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='supertee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Myohassa_olevat.xlsx'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td valign='top' class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}

	require ("inc/footer.inc");

?>