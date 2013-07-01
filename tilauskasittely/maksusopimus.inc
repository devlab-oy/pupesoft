<?php

	echo "<font class='head'>".t("Tilauksen maksusopimus")."</font><hr><br>";

	// Jotta osataan hakea oikeat tilaukset laitetaan tilat talteen
	if ($toim == "TARJOUS") {
		$sallitut_tilat = "'T'";
	}
	else {
		$sallitut_tilat = "'L','G','E','V','W','N','A'";
	}

	$query_ale_lisa = generoi_alekentta('M');

	if ($tila == "lisaa_kaikki_jaksotukseen") {

		if ($laskurow["jaksotettu"] == 0) {
			echo "<font class='error'>".t("Lasku ei osa maksusopimusta")."</font>";
		}
		else {
			//	Varmistetaan että kaikki otsikot on nipussa
			$query = "	UPDATE lasku
						SET jaksotettu=(if (tunnusnippu=0,tunnus,tunnusnippu))
						WHERE yhtio = '$kukarow[yhtio]'
						and if (tunnusnippu = 0,tunnus=$laskurow[tunnus], tunnusnippu=$laskurow[tunnusnippu])
						and jaksotettu = 0
						and tila in ($sallitut_tilat)
						and alatila != 'X'";
			$result = mysql_query($query) or pupe_error($query);

			//	Varmistetaan, että kaikki vanhatunnukset on sopparilla
			if ($laskurow["vanhatunnus"] > 0) {
				$query = "	UPDATE lasku
							SET jaksotettu = '$laskurow[jaksotettu]'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and vanhatunnus = '$laskurow[vanhatunnus]'
							and tila IN ($sallitut_tilat)
							and alatila != 'X'";
				$result = mysql_query($query) or pupe_error($query);
			}

			//	Haetaan kaikki tunnukset
			$query = "	SELECT group_concat(distinct tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu  = '$laskurow[jaksotettu]'
						and tila IN ($sallitut_tilat)
						and alatila != 'X'";
			$result = mysql_query($query) or pupe_error($query);
			$posrow = mysql_fetch_array($result);

			$ltunnukset	= $posrow["tunnukset"];

			$query = "	UPDATE tilausrivi
						SET jaksotettu = '$laskurow[jaksotettu]'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus in ($ltunnukset)";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	if ($tila == "uusi") {
		if ($laskurow["jaksotettu"] == 0) {
			$query = "	UPDATE lasku
						SET jaksotettu=(if(tunnusnippu=0,tunnus,tunnusnippu))
						WHERE yhtio = '$kukarow[yhtio]'
						and if(tunnusnippu = 0,tunnus=$laskurow[tunnus],tunnusnippu=$laskurow[tunnusnippu])
						and tila in ($sallitut_tilat)
						and alatila != 'X'";
			$result = mysql_query($query) or pupe_error($query);

			// Tässä meidän on hieman fiilattava tätä muuttujaa
			if ($laskurow["tunnusnippu"] > 0) {
				$laskurow["jaksotettu"] = $laskurow["tunnusnippu"];
			}
			else {
				$laskurow["jaksotettu"] = $laskurow["tunnus"];
			}

			// Jos tilaus on slittaantunut useaksi osaksi laitetaan maksusoppari koskemaan kaikkia splittejä jotka periytyvät samalta tilaukselta
			if ($laskurow["vanhatunnus"] > 0) {
				$query = "	UPDATE lasku
							SET jaksotettu = '$laskurow[jaksotettu]'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and vanhatunnus = '$laskurow[vanhatunnus]'
							and tila IN ($sallitut_tilat)
							and alatila != 'X'";
				$result = mysql_query($query) or pupe_error($query);

				$query = "	SELECT group_concat(distinct tunnus) tunnukset
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and jaksotettu  = '$laskurow[jaksotettu]'
							and tila IN ($sallitut_tilat)
							and alatila != 'X'";
				$result = mysql_query($query) or pupe_error($query);
				$posrow = mysql_fetch_array($result);

				$ltunnukset	= $posrow["tunnukset"];
			}
			else {
				$ltunnukset = $laskurow["tunnus"];
			}

			$query = "	UPDATE tilausrivi
						SET jaksotettu = '$laskurow[jaksotettu]'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus in ($ltunnukset)";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "	INSERT INTO maksupositio
					SET yhtio 	= '$kukarow[yhtio]',
					otunnus 	= '$laskurow[jaksotettu]',
					luotu		= now(),
					luonut		= '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tila == "tallenna") {

		$query = "	SELECT
					sum(if (tilausrivi.jaksotettu='$laskurow[jaksotettu]', round(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2), 0)) jaksotettavaa_veroton,
					sum(if (tilausrivi.jaksotettu='$laskurow[jaksotettu]', round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2), 0)) jaksotettavaa_verolli
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.jaksotettu='$laskurow[jaksotettu]'
					WHERE lasku.yhtio 		= '$kukarow[yhtio]'
					and lasku.tila 			IN ($sallitut_tilat)
					and lasku.jaksotettu 	= '$laskurow[jaksotettu]'";
		$result = mysql_query($query) or pupe_error($query);
		$rivrow = mysql_fetch_array($result);

		$query = "	SELECT *
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$maksupositio'";
		$result = mysql_query($query) or pupe_error($query);
		$posrow = mysql_fetch_array($result);

		$summa = str_replace(",", ".", $summa);
		$osuus = str_replace(",", ".", $osuus);

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
		$result = mysql_query($query) or pupe_error($query);
		$tuoterow = mysql_fetch_array($result);

		if ($summa != 0 and $yhtiorow["alv_kasittely"] != "" and $kukarow["alv_kasittely"] == "A" and $tuoterow["alv"] != 0) {
			$summa = sprintf('%.2f', $summa / (1 + $tuoterow["alv"] / 100));
		}

		if ($yhtiorow["alv_kasittely"] == '') {
			$laskunsumma = $rivrow["jaksotettavaa_verolli"];
		}
		else {
			$laskunsumma = $rivrow["jaksotettavaa_veroton"];
		}

		if ($posrow["summa"] != $summa and $summa > 0) {
			// Lasketaan prossa annetun summan perusteella
			$osuus = round($summa / $laskunsumma * 100, 4);
		}
		elseif ($posrow["osuus"] != $osuus and $osuus > 0) {
			// Lasketaan summa annetun prossan perusteella
			$summa = round($osuus / 100 * $laskunsumma, 2);
		}

		if ($mehto != "") {
			// haetaan maksuehdoen tiedot tarkastuksia varten
			$apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$mehto'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$meapurow = mysql_fetch_array($meapu);
		}

		if (($erpcmvv != "" and $erpcmkk != "" and $erpcmpp != "") or $meapurow["erapvmkasin"] != '') {

			$erpquery = "SELECT * from maksuehto where yhtio = '$kukarow[yhtio]' and erapvmkasin != '' order by tunnus limit 1";
			$erpres = mysql_query($erpquery) or pupe_error($erpquery);

			$erpcmvv = (int) $erpcmvv;
			$erpcmkk = (int) $erpcmkk;
			$erpcmpp = (int) $erpcmpp;

			if (!checkdate($erpcmkk, $erpcmpp, $erpcmvv) or mysql_num_rows($erpres) == 0) {
				if (mysql_num_rows($erpres) == 0) {
					echo "<font class='error'>".t("VIRHE: Yritykseltä ei löydy sopivaa maksuehtoa eräpäivän käsinsyöttöön")."!</font><br>";
				}
				else {
					echo "<font class='error'>".t("VIRHE: Syötetty eräpäivä on virheellinen")."!</font><br>";
				}
				$erpcmvv = 0;
				$erpcmkk = 0;
				$erpcmpp = 0;
			}
		}

		$lisatiedot = pupesoft_cleanstring($lisatiedot);
		$ohje = pupesoft_cleanstring($ohje);

		$query = "	UPDATE maksupositio SET
					osuus 		= '$osuus',
					summa  		= '$summa',
					kuvaus 		= '$kuvaus',
					erpcm		= '$erpcmvv-$erpcmkk-$erpcmpp',
					maksuehto 	= '$mehto',
					lisatiedot 	= '$lisatiedot',
					ohje 		= '$ohje'
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[jaksotettu]'
					and tunnus  = '$maksupositio'";
		$result = mysql_query($query) or pupe_error($query);

		$erpcmpp = "";
		$erpcmkk = "";
		$erpcmvv = "";
	}

	if ($tila == "poista") {

		$query = "	DELETE
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$maksupositio'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	SELECT *
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[jaksotettu]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM lasku USE INDEX (yhtio_jaksotettu)
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu = '$laskurow[jaksotettu]'";
			$result = mysql_query($query) or pupe_error($query);
			$jaksorow = mysql_fetch_array($result);

			$query = "	UPDATE lasku USE INDEX (yhtio_jaksotettu)
						SET jaksotettu = 0
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu = '$laskurow[jaksotettu]'";
			$result = mysql_query($query) or pupe_error($query);

			$laskurow["jaksotettu"] = 0;

			$query = "	UPDATE tilausrivi USE INDEX (yhtio_otunnus)
						SET jaksotettu = 0
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu = '$laskurow[jaksotettu]'
						and otunnus in ($jaksorow[tunnukset])";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	// Maksopositiot
	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus  = '$laskurow[maksuehto]'
				and jaksotettu != ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {

		echo "<form method='post' name='maksupositiot'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='$tee'>
				<input type='hidden' name='tila' value='uusi'>
				<input type='submit' name='uusi_maksupositio' value='".t("Uusi positio")."'>
				</form>";

		if ($laskurow["jaksotettu"] > 0) {
			echo "<form method='post' name='maksupositiot'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='$tee'>
					<input type='hidden' name='tila' value='lisaa_kaikki_jaksotukseen'>
					<input type='submit' name='uusi_maksupositio' value='".t("Lisää kaikki rivit sopimukseen")."'>
					</form>";
		}

		echo "<form method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form><br><br>";

		$tot = 0;

		$query	= "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$laskurow[liitostunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakasrow = mysql_fetch_array($result);
		}

		// Haetaan laskun summat
		$query = "	SELECT
					group_concat(distinct lasku.tunnus) tilaukset,
					sum(if (tilausrivi.jaksotettu='$laskurow[jaksotettu]', round(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2), 0)) jaksotettavaa_veroton,
					sum(if (tilausrivi.jaksotettu='$laskurow[jaksotettu]', round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2), 0)) jaksotettavaa_verolli,
					round(sum(if (tilausrivi.jaksotettu=0 , tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)),2) loput_veroton,
					round(sum(if (tilausrivi.jaksotettu=0 , tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)),2) loput_verolli
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D'
					WHERE lasku.yhtio		= '$kukarow[yhtio]'
					and lasku.jaksotettu	= '$laskurow[jaksotettu]'
					and lasku.tila 			IN ($sallitut_tilat)
					and lasku.jaksotettu 	> 0
					GROUP by lasku.jaksotettu";
		$result = mysql_query($query) or pupe_error($query);
		$rivrow = mysql_fetch_array($result);

		$jaksotettavaa_verolli 	= $rivrow["jaksotettavaa_verolli"];
		$loput_verolli 			= $rivrow["loput_verolli"];

		$jaksotettavaa_veroton 	= $rivrow["jaksotettavaa_veroton"];
		$loput_veroton 			= $rivrow["loput_veroton"];

		if ($yhtiorow["alv_kasittely"] == '') {
			$jaksotettavaa = $rivrow["jaksotettavaa_verolli"];
		}
		else {
			$jaksotettavaa = $rivrow["jaksotettavaa_veroton"];
		}

		// Haetaan kaikki tämän tilauksen laskutuspositiot (HUOM: Positioiden summ on veroton tai verollinen riippuen yhtiön myyntihinnoista)
		$query = "	SELECT *
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[jaksotettu]'
					ORDER BY tunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr><th>".t("Maksusopimukseen kuuluvat tilaukset").":</th><td> $rivrow[tilaukset] </td></tr>";
			echo "</table><br>";

			$kuvares = t_avainsana("LASKUKUVAUS");

			$query = "	SELECT *
						FROM maksuehto
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu = ''
						ORDER BY jarjestys";
			$mresult = mysql_query($query) or pupe_error($query);

			echo "<table>
					<tr>
					<th>#</th>
					<th>".t("Osuus %")."</th>
					<th>".t("Maksuehto / Kuvaus")."</th>
					<th>".t("Lisätiedot / Ohje")."</th>
					<th>".t("Summa")."</th>
					</tr>";

			$osuus	= 0;
			$i		= 0;

			while ($row = mysql_fetch_array($result)) {

				$i++;

				$todellinen_osuus = round($row["summa"] / $jaksotettavaa * 100, 4);

				if ($row["osuus"] != $todellinen_osuus) {
					$maposupdate_query = "	UPDATE maksupositio SET
											osuus = '$todellinen_osuus'
											WHERE yhtio = '$kukarow[yhtio]'
											AND tunnus = '$row[tunnus]'";
					$maposupdate = mysql_query($maposupdate_query) or pupe_error($maposupdate_query);

					$row["osuus"] = $todellinen_osuus;
				}

				if ($row["uusiotunnus"] == 0) {
					echo "<form method='post' name='maksupositiot'>";
					echo "<tr>";

					echo "<td>$i</td>";
					echo "<td><input type='text' size ='7' name='osuus' value='$row[osuus]'></td>";
					echo "<td><select name='mehto'>";

					while ($prow = mysql_fetch_array($mresult)) {
						if ($row["maksuehto"] == $prow["tunnus"]) $sel = "SELECTED";
						elseif ($asiakasrow["maksuehto"] == $prow["tunnus"] and $row["maksuehto"] == "") $sel = "SELECTED";
						else $sel = "";

						echo "<option value='$prow[tunnus]' $sel>".t_tunnus_avainsanat($prow, "teksti", "MAKSUEHTOKV")."</option>";
					}
					echo "</select><br>";

					// otetaan erpcm kannasta jos mitään ei ole syötetty ruudulle
					if ($row["erpcm"] != "" and (int) $erpcmpp == 0 and (int) $ercpmkk == 0 and (int) $erpcmvv == 0 and $erpcm_virhe == "") {
						list($erpcmvv, $erpcmkk, $erpcmpp) = explode('-', $row["erpcm"]);
					}

					// jos päivämäärä on nollaa laitetaan tyhjää
					if ((int) $erpcmpp == 0 and (int) $erpcmkk == 0 and (int) $erpcmvv == 0) {
						$erpcmpp = "";
						$erpcmkk = "";
						$erpcmvv = "";
					}

					echo "	<input type='text' name='erpcmpp' value='$erpcmpp' size='3'>
							<input type='text' name='erpcmkk' value='$erpcmkk' size='3'>
							<input type='text' name='erpcmvv' value='$erpcmvv' size='6'>";
					echo "</td>";

					echo "<td><input type='text' size='31' name='lisatiedot' value='$row[lisatiedot]'></td>";

					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);
					$tuoterow = mysql_fetch_array($tuoteresult);

					if ($yhtiorow["alv_kasittely"] != "" and $kukarow["alv_kasittely"] == "A") {
						$row["summa"] = round($row["summa"] * (1+($tuoterow["alv"]/100)),2);
					}
					else {
						$row["summa"] = round($row["summa"],2);
					}

					echo "<td align ='right'><input style='text-align:right' type='text' size='10'  name='summa' value='".sprintf('%.2f', $row["summa"])."'></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td colspan='2'></td><td valign='top'><select name='kuvaus'>";

					while($prow= mysql_fetch_array($kuvares)) {
						if ($row["kuvaus"] == $prow["selitetark"]) $sel = "SELECTED";
						else $sel = "";

						echo "<option value='$prow[selitetark]' $sel>$prow[selitetark]</option>";
					}
					echo "</select></td>";

					echo "<td><textarea rows='2' cols='30' name='ohje'>$row[ohje]</textarea></td>";
					echo "<td></td>";

					echo "	<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='tee' value='$tee'>
							<input type='hidden' name='tila' value='tallenna'>
							<input type='hidden' name='maksupositio' value='$row[tunnus]'>
							<td class='back' valign='top'><input type='submit' value='".t("Tallenna")."'></td></form>";

					echo "<form method='post' name='maksupositiot'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='tee' value='$tee'>
							<input type='hidden' name='tila' value='poista'>
							<input type='hidden' name='maksupositio' value='$row[tunnus]'>
							<td class='back' valign='top'><input type='submit' value='".t("Poista")."'></td></form>";

					echo "</tr>";
					echo "<tr><td class='back'></td></tr>";

				}
				else {

					echo "<tr><td>$i</td>";
					echo "<td>$row[osuus]</td>";

					echo "<td>";

					while($prow = mysql_fetch_array($mresult)) {
						if ($row["maksuehto"] == $prow["tunnus"]) echo t_tunnus_avainsanat($prow, "teksti", "MAKSUEHTOKV");
					}

					echo "<br>$row[kuvaus]</td>";
					echo "<td>$row[lisatiedot]<br>$row[ohje]</td>";
					echo "<td align ='right'><a target='_blank' href = '{$palvelin2}tilauskasittely/tulostakopio.php?toim=LASKU&tee=NAYTATILAUS&otunnus=$row[uusiotunnus]'>$row[summa]</a></td></tr>";
				}

				$osuus += $row["osuus"];
				$tot   += $row["summa"];

				$erpcmpp = "";
				$erpcmkk = "";
				$erpcmvv = "";

				//alkuun tämä resultti
				mysql_data_seek($kuvares,0);
				mysql_data_seek($mresult,0);
			}

			$ihakaikki_verolli  = $loput_verolli + $jaksotettavaa_verolli;
			$ihakaikki_veroton  = $loput_veroton + $jaksotettavaa_veroton;

			//Käsin syötetty summa johon lasku pyöristetään
			if ($laskurow["hinta"] <> 0 and abs($laskurow["hinta"]-$ihakaikki_verolli) <= 0.5) {
				$ihakaikki_verolli = sprintf("%.2f",$laskurow["hinta"]);
			}
			//Käsin syötetty summa johon lasku pyöristetään
			if ($laskurow["hinta"] <> 0 and abs($laskurow["hinta"]-$loput_verolli-$jaksotettavaa_verolli) <= 0.5) {
				$jaksotettavaa_verolli = $laskurow["hinta"]-$loput_verolli;
			}

			echo "<tr>";
			echo "<th style='text-align:right' colspan='2'>$osuus %</th>";
			echo "<th style='text-align:right' colspan='2'>".t("Maksupositiot yhteensä")."</th>";
			echo "<th style='text-align:right'>".sprintf('%.2f', $tot)."</th>";
			echo "</tr>";

			echo "</table>";

			if (round($tot, 2) != $jaksotettavaa) {
				$osuusvippaus 	= round(($osuus - 100) * -1,2);
				$summavippaus 	= round($jaksotettavaa-$tot,2);

				echo "<br>";
				echo "<font class='error'>".t("Positiot eivät täsmää, muuta jotain positiota")." $osuusvippaus% $summavippaus $yhtiorow[valkoodi]</font>";
				echo "<br>";
			}


			if (round($tot, 2) == $jaksotettavaa) {

				// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
				js_openFormInNewWindow();

				echo "<br>";
				echo "<form method='post' id='tulosta_maksusopimus' name='tulosta_maksusopimus'>";
				echo "<input type='hidden' name='toim' value='{$toim}'>";
				echo "<input type='hidden' name='tee' value='NAYTATILAUS'>";
				echo "<input type='hidden' name='tilausnumero' value='{$tilausnumero}'>";
				echo "<input type='hidden' name='tulosta_maksusopimus' value='{$laskurow['jaksotettu']}'>";
				echo "<input type='hidden' name='tunnus' value='{$laskurow['tunnus']}'>";
				echo "<tr><th colspan='5'><input type='submit' value='",t("Positiot PDF-muotoon"),"' onClick=\"js_openFormInNewWindow('tulosta_maksusopimus', ''); return false;\"></th></tr>";
				echo "</form>";
				echo "<br>";

			}

			echo "<br>";
			echo "<table>";

			echo "<tr>";
			echo "<td class='back'></td>";
			echo "<th>".t("Veroton")."</th>";
			echo "<th>".t("Verollinen")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Jaksotettavaa yhteensä")."</th>";
			echo "<td align='right'>".sprintf('%.2f', $jaksotettavaa_veroton)."</td>";
			echo "<td align='right'>".sprintf('%.2f', $jaksotettavaa_verolli)."</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Ei jaksotettavaa yhteensä")."</th>";
			echo "<td align='right'>".sprintf('%.2f', $loput_veroton)."</td>";
			echo "<td align='right'>".sprintf('%.2f', $loput_verolli)."</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Tilaus yhteensä")."</th>";
			echo "<td align='right'>".sprintf('%.2f', $ihakaikki_veroton)."</td>";
			echo "<td align='right'>".sprintf('%.2f', $ihakaikki_verolli)."</td>";
			echo "</tr>";

			echo "</table>";
		}
		else {
			echo "<font class='error'>".t("Tilauksella ei ole maksupositioita!")."</font><br><br>";
		}
	}

?>