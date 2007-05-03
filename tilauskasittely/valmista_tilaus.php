<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Valmista tilaus").":</font><hr>";

	if ($tee == 'NAYTATILAUS') {
		require ("../raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}

	//HUOMHUOM!!
	$query = "SET SESSION group_concat_max_len = 100000";
	$result = mysql_query($query) or pupe_error($query);

	if ($tee=='alakorjaa') {
		//P‰ivitet‰‰n lasku niin, ett‰ se on takaisin tilassa valmistettu
		$query = "	SELECT
					GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') laskut
					FROM tilausrivi, lasku
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and	tilausrivi.tunnus in ($valmistettavat)
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.yhtio=tilausrivi.yhtio";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);

		$query = "	UPDATE lasku
					SET alatila	= 'V'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus in ($row[laskut])
					and tila	= 'V'
					and alatila = 'K'";
		$chkresult4 = mysql_query($query) or pupe_error($query);

		$valmistettavat = "";
		$tee = "";
	}

	if ($tee=='TEEVALMISTUS') {
		//K‰yd‰‰n l‰pi rivien kappalem‰‰r‰t ja tehd‰‰n samalla pieni tsekki, ett‰ onko rivi jo valmistettu
		foreach ($tilkpllat as $rivitunnus => $tilkpl) {

			$tilkpl = str_replace(',','.',$tilkpl);

			// Tarkistetaan ettei tilaus ole jo toimitettu/laskutettu
			if ($toim == "KORJAA") {
				$alatilat = "K";
			}
			else {
				$alatilat = "C";
			}

			$query = "	SELECT distinct lasku.tunnus
						FROM tilausrivi, lasku
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus = '$rivitunnus'
						and lasku.tunnus = tilausrivi.otunnus
						and lasku.tila = 'V'
						and alatila = '$alatilat'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				//Haluaako k‰ytt‰j‰ muuttaa rivill‰ olevaa m‰‰r‰‰
				if($edtilkpllat[$rivitunnus] != $tilkpl) {
					$laskurow = mysql_fetch_array($result);

					$query = "	UPDATE tilausrivi
								SET varattu = '$tilkpl'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$rivitunnus'";
					$updresult = mysql_query($query) or pupe_error($query);

					$tee = "VALMISTA";
					$virhe[$rivitunnus] = "<font class='message'>".t("Valmistettava m‰‰r‰ p‰ivitettiin")."!</font>";
				}
			}
			else {
				echo "<font class='error'>".t("Valmistus on jo korjattu")."!</font><br><br>";
				$tee = "XXX";
				break;
			}
		}

		// Jatketaan valmistusta
		if ($tee == "TEEVALMISTUS") {
			if ($toim == "KORJAA") {
				foreach ($valmkpllat as $rivitunnus => $valmkpl) {

					$valmkpl = str_replace(',','.',$valmkpl);

					$query = "	SELECT *, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivitunnus'
								and tyyppi in ('W','M')";
					$roxresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($roxresult) == 1) {
						$tilrivirow = mysql_fetch_array($roxresult);

						$tuoteno 		= $tilrivirow["tuoteno"];
						$tee			= "UV";
						$varastopaikka  = $tilrivirow["paikka"];

						require ("korjaa_valmistus.inc");
					}
				}
				//P‰ivitet‰‰n lasku niin, ett‰ se on tilassa valmistettu
				$query = "	UPDATE lasku
							SET alatila	= 'V'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$tilrivirow[otunnus]'
							and tila	= 'V'
							and alatila = 'K'";
				$chkresult4 = mysql_query($query) or pupe_error($query);

				echo "<br><br><font class='message'>Valmistus korjattu!</font><br>";
			}
			else {
				foreach($valmkpllat as $rivitunnus => $valmkpl) {

					$valmkpl = str_replace(',','.',$valmkpl);

					//Haetaan tilausrivi
					$query = "	SELECT *, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivitunnus'
								and tyyppi in ('W','M')
								and toimitettuaika = '0000-00-00 00:00:00'";
					$roxresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($roxresult) == 1) {
						$tilrivirow = mysql_fetch_array($roxresult);

						// Otetaan tiedot alkuper‰iselt‰ tilaukselta
						if ($tilrivirow["uusiotunnus"] != 0) {
							$slisa = " and lasku.tunnus = tilausrivi.uusiotunnus";
						}
						else {
							$slisa = " and lasku.tunnus = tilausrivi.otunnus ";
						}

						$query = "	SELECT lasku.*
									FROM tilausrivi, lasku
									WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.tunnus = '$rivitunnus'
									$slisa
									and lasku.tila = 'V'";
						$result = mysql_query($query) or pupe_error($query);
						$laskurow = mysql_fetch_array($result);

						if (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

							if ($valmkpl > 0) {
								$atil = round($valmkpl, 2);
							}
							elseif($valmkpl == "") {
								$atil =  $tilrivirow["varattu"];
							}

							if ($kokopros > 0) {
								$atil = round($kokopros / 100 * $tilrivirow["varattu"],2);
							}


							$akerroin = $atil / $tilrivirow["varattu"];

							$valmistetaan 	= "TILAUKSELTA";
							$tuoteno 		= $tilrivirow["tuoteno"];
							$tee			= "UV";
							$varastopaikka  = $tilrivirow["paikka"];

							require ("../valmistatuotteita.inc");

							//jos valmistus meni ok, niin palautuu $tee == UV
							if (round($jaljella,2) == 0 and $tee == "UV") {
								//p‰ivitet‰‰n t‰m‰ perhe valmistetuksi
								$query = "	UPDATE tilausrivi
											SET toimitettu = '$kukarow[kuka]',
											toimitettuaika = now(),
											varattu = 0
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus = '$tilrivirow[otunnus]'
											and tyyppi in ('W','V','M')
											and perheid = '$tilrivirow[perheid]'";
								$updresult = mysql_query($query) or pupe_error($query);
							}

							if ($tee != "UV") {
								$virheitaoli = "JOO";
								$valmkpllat2[$rivitunnus] = $valmkpl;								
							}
						}
						elseif ($valmkpl == 0 or $valmkpl == "") {
							$virhe[$rivitunnus] = "<font class='message'>Kappalem‰‰r‰ ei syˆtetty!</font>";
							$tee = "VALMISTA";
							$valmkpllat2[$rivitunnus] = $valmkpl;							
						}
						else {
							$virhe[$rivitunnus] = "<font class='error'>VIRHE: Syˆtit liian ison kappalem‰‰r‰n!</font>";
							$tee = "VALMISTA";
							$valmkpllat2[$rivitunnus] = $valmkpl;
						}
					}
				}


				//katotaan onko en‰‰ mit‰‰n valmistettavaa
				foreach ($valmkpllat as $rivitunnus => $valmkpl) {
					//Haetaan tilausrivi
					$query = "	SELECT otunnus, uusiotunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = $rivitunnus
								and tyyppi in ('W','M')";
					$roxresult = mysql_query($query) or pupe_error($query);
					$tilrivirow = mysql_fetch_array($roxresult);

					//Katsotaan onko yht‰‰n valmistamatonta rivi‰ t‰l‰ tilauksella/jobilla
					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio	= '$kukarow[yhtio]'
								and otunnus = $tilrivirow[otunnus]
								and tyyppi	in ('W','M')
								and tunnus	= perheid
								and toimitettuaika = '0000-00-00 00:00:00'";
					$chkresult1 = mysql_query($query) or pupe_error($query);

					//eli tilaus on kokonaan valmistettu
					if (mysql_num_rows($chkresult1) == 0) {
						//Jos kyseess‰ on varastovalmistus
						$query = "	UPDATE lasku
									SET alatila	= 'V'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = $tilrivirow[otunnus]
									and tila	= 'V'
									and alatila = 'C'
									and tilaustyyppi = 'W'";
						$chkresult2 = mysql_query($query) or pupe_error($query);

						//Jos kyseess‰ on asiakaalle valmistus
						$query = "	UPDATE lasku
									SET tila	= 'L',
									alatila 	= 'C'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = $tilrivirow[otunnus]
									and tila	= 'V'
									and alatila = 'C'
									and tilaustyyppi = 'V'";
						$chkresult2 = mysql_query($query) or pupe_error($query);

						$tee	 		= "";
						$valmistettavat = "";
					}
					else {
						$tee = "VALMISTA";
					}

					//jos rivit oli siirretty toiselta otsikolta niin siirret‰‰n ne nyt takaisin
					if ($tilrivirow["uusiotunnus"] != 0 and mysql_num_rows($chkresult1) == 0) {
						$query = "	UPDATE tilausrivi
									SET otunnus = uusiotunnus,
									uusiotunnus = 0
									WHERE yhtio = '$kukarow[yhtio]'
									and otunnus = $tilrivirow[otunnus]
									and uusiotunnus = $tilrivirow[uusiotunnus]
									and uusiotunnus != 0";
						$chkresult3 = mysql_query($query) or pupe_error($query);

						//tutkitaan pit‰‰kˆ alkuper‰isen otsikon tilat p‰ivitt‰‰
						$query = "	SELECT tunnus
									FROM tilausrivi
									WHERE yhtio	= '$kukarow[yhtio]'
									and (otunnus = $tilrivirow[uusiotunnus] or uusiotunnus = $tilrivirow[uusiotunnus])
									and tyyppi	in ('W','M')
									and tunnus	= perheid
									and toimitettuaika = '0000-00-00 00:00:00'";
						$selres = mysql_query($query) or pupe_error($query);

						//eli alkuper‰inen tilaus on kokonaan valmistettu
						if (mysql_num_rows($selres) == 0) {
							//Jos kyseess‰ on varastovalmistus
							$query = "	UPDATE lasku
										SET alatila	= 'V'
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus  = '$tilrivirow[uusiotunnus]'
										and tila	= 'V'
										and alatila in ('C','Y')
										and tilaustyyppi = 'W'";
							$chkresult4 = mysql_query($query) or pupe_error($query);

							//Jos kyseess‰ on asiakaalle valmistus
							$query = "	UPDATE lasku
										SET tila	= 'L',
										alatila 	= 'C'
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus  = '$tilrivirow[uusiotunnus]'
										and tila	= 'V'
										and alatila in ('C','Y')
										and tilaustyyppi = 'V'";
							$chkresult4 = mysql_query($query) or pupe_error($query);
						}
					}
				}
			}
		}
	}

	if ($tee == "VALMISTA") {
		//Haetaan otsikoiden tiedot
		$query = "	SELECT
					GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') 'Tilaus',
					GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR ', ') 'Asiakas/Nimi',
					GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR ', ') 'Ytunnus'
					FROM tilausrivi, lasku
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and	tilausrivi.tunnus in ($valmistettavat)
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.yhtio=tilausrivi.yhtio";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);

		//P‰ivitet‰‰n lasku niin, ett‰ se on tilassa korjataan
		if ($toim == "KORJAA") {
			$query = "	UPDATE lasku
						SET alatila	= 'K'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus in ($row[Tilaus])
						and tila	= 'V'
						and alatila = 'V'";
			$chkresult4 = mysql_query($query) or pupe_error($query);
		}

		//Jos valmistetaan per tuote niin valinnasta/hausta tulee vain valmisteiden tunnukset, tarvitsemme kuitenkin myˆs raaka-aineiden tunnukset joten haetaan ne t‰ss‰
		if ($toim == "TUOTE" and $tulin == "VALINNASTA") {
			$query = "	SELECT
						GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus in ($row[Tilaus])
						and	tilausrivi.perheid in ($valmistettavat)";
			$ktres = mysql_query($query) or pupe_error($query);
			$ktrow    = mysql_fetch_array($ktres);

			$valmistettavat = $ktrow["valmistettavat"];
		}

		echo "<table>";
		for ($i=0; $i < mysql_num_fields($result); $i++) {
			echo "<tr><th align='left'>" . t(mysql_field_name($result,$i)) ."</th><td>$row[$i]</td></tr>";
		}
		echo "</table><br>";

		//Haetaan valmistettavat valmisteet ja k‰ytett‰v‰t raaka-aineet
		$query = "	SELECT tilausrivi.nimitys,
					tilausrivi.tuoteno,
					tilkpl tilattu,
					if(tyyppi!='L', varattu, 0) valmistetaan,
					if(tyyppi='L' or tyyppi='D', varattu, 0) valmistettu,
					if(toimitettu!='', if(varattu!=0, varattu, kpl), 0) korjataan,
					if(toimitettu!='', kpl, 0) valmistettu_valmiiksi,
					if(tyyppi!='L', kpl, 0) kaytetty,
					toimaika,
					kerayspvm,
					tilausrivi.tunnus tunnus,
					tilausrivi.perheid,
					tilausrivi.tyyppi,
					tilausrivi.toimitettuaika,
					tilausrivi.otunnus otunnus
					FROM tilausrivi, tuote
					WHERE
					tilausrivi.otunnus in ($row[Tilaus])
					and tilausrivi.tunnus in ($valmistettavat)
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tuote.yhtio=tilausrivi.yhtio
					and tuote.tuoteno=tilausrivi.tuoteno
					and tyyppi in ('V','W','M','L','D')
					ORDER BY perheid desc, tyyppi in ('W','M','L','D','V'), tunnus";
		$presult = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($presult);

		echo "<table border='0' cellspacing='1' cellpadding='2'><tr>";
		$miinus = 3;

		echo "<th>#</th>";

		echo "<th>".t("Nimitys")."</a></th>";
		echo "<th>".t("Tuoteno")."</a></th>";
		echo "<th>".t("Tilattu")."</a></th>";
		echo "<th>".t("Valmistetaan")."</a></th>";
		echo "<th>".t("Valmistettu")."</a></th>";
		echo "<th>".t("Toimaka")."</a></th>";
		echo "<th>".t("Ker‰ysaika")."</a></th>";

		if ($toim != 'KORJAA') {
			echo "<th>".t("Valmista")."</th>";
		}

		echo "</tr>";

		$rivkpl = mysql_num_rows($presult);
		$voikokorjata = 0;

		$vanhaid = "KALA";

		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='TEEVALMISTUS'>
				<input type='hidden' name='valmistettavat' value='$valmistettavat'>
				<input type='hidden' name='toim'  value='$toim'>";

		while ($prow = mysql_fetch_array ($presult)) {

			$class = '';

			if ($vanhaid != $prow["perheid"] and $vanhaid != 'KALA') {
				echo "<tr><td class='back' colspan='7'><br></td></tr>";

				if($prow["tunnus"] == $prow["perheid"]) {
					$class = "spec";
				}
			}
			elseif ($vanhaid == 'KALA' and $prow["tunnus"] == $prow["perheid"]) {
				$class = "spec";
			}

			if ($prow["tyyppi"] == 'L' or $prow["tyyppi"] == 'D') {
				$class = "green";
			}

			echo "<tr>";

			echo "<td class='$class'>$rivkpl</td>";
			$rivkpl--;

			echo "<td class='$class' align='right'>".asana('nimitys_',$prow['tuoteno'],$prow['nimitys'])."</td>";
			echo "<td class='$class'><a href='../tuote.php?tee=Z&tuoteno=$prow[tuoteno]'>$prow[tuoteno]</a></td>";
			echo "<td class='$class' align='right'>$prow[tilattu]</td>";


			if ($toim == "KORJAA" and  $prow["tyyppi"] == 'V') {
				echo "<td class='$class' align='right'>
					<input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[korjataan]'>
					<input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[korjataan]'>
					</td>";
				echo "<td class='$class'>$prow[valmistettu_valmiiksi]</td>";
			}
			elseif ($prow["tyyppi"] == 'L' or $prow["tyyppi"] == 'D' or $prow["perheid"] == 0) {
				echo "<td class='$class' align='right'></td>";
				echo "<td class='$class' align='right'>$prow[valmistettu]</td>";
			}
			elseif ($prow["toimitettuaika"] == "0000-00-00 00:00:00") {
				echo "<td class='$class' align='right'>
						<input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[valmistetaan]'>
						<input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'>
						</td>";
				echo "<td class='$class'></td>";
			}
			elseif ($prow["tyyppi"] == 'V') {
				echo "<td class='$class'></td>";
				echo "<td class='$class' align='right'>$prow[kaytetty]</td>";
			}
			else {
				echo "<td class='$class'></td>";
				echo "<td class='$class'></td>";
			}

			echo "<td class='$class' align='right'>".tv1dateconv($prow["toimaika"])."</td>";
			echo "<td class='$class' align='right'>".tv1dateconv($prow["kerayspvm"])."</td>";

			if ($prow["tyyppi"] == "V") {
				echo "<td class='back'>".$virhe[$prow["tunnus"]]."</td>";
			}

			if ($prow["tunnus"] == $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] == "0000-00-00 00:00:00") {
				echo "<td align='center'><input type='text' name='valmkpllat[$prow[tunnus]]' value='".$valmkpllat2[$prow["tunnus"]]."' size='5'></td><td class='back'>".$virhe[$prow["tunnus"]]."</td>";
			}
			elseif($prow["tunnus"] == $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] != "0000-00-00 00:00:00" and $toim == "KORJAA") {
				//tutkitaan kuinka paljon t‰t‰ nyt oli valmistettu
				$query = "	SELECT sum(kpl) valmistetut
							FROM tilausrivi
							WHERE yhtio	= '$kukarow[yhtio]'
							and otunnus = '$prow[otunnus]'
							and perheid = '$prow[perheid]'
							and tyyppi	= 'D'
							and toimitettuaika = '0000-00-00 00:00:00'";
				$sumres = mysql_query($query) or pupe_error($query);
				$sumrow = mysql_fetch_array($sumres);

				if ($sumrow["valmistetut"] != 0) {
					echo "<td class='back'><input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$sumrow[valmistetut]'></td><td class='back'>".$virhe[$prow["tunnus"]]."</td>";
					$voikokorjata++;
				}
				else {
					echo "<td class='back'><font class='error'>Rivi‰ ei voida korjata!</font></td>";
				}
			}

			echo "</tr>";

			$vanhaid = $prow["perheid"];

		}

		echo "<tr><td colspan='9' class='back'><br></td></tr>";

		if ($toim != 'KORJAA') {
			echo "<tr><td colspan='8'>Valmista syˆtetyt kappaleet:</td><td><input type='submit' name='osavalmistus' value='".t("Valmista")."'></td></tr>";
			echo "<tr><td colspan='4'>Valmista prosentti koko tilauksesta:</td><td colspan='4' align='right'><input type='text' name='kokopros' size='5'> % </td><td><input type='submit' name='osavalmistus' value='".t("Valmista")."'></td></tr>";
			echo "<tr><td colspan='8'>Valmista koko tilaus:</td><td><input type='submit' name='kokovalmistus' value='".t("Valmista")."'></td>";
		}
		elseif($toim == 'KORJAA' and $voikokorjata > 0) {
			echo "<tr><td colspan='8'>Korjaa koko valmistus:</td><td class='back'><input type='submit' name='' value='".t("Korjaa")."'></td>";
		}

		if ($virheitaoli == "JOO") {
			echo "<td>".t("V‰kisinvalmista vaikka raaka-aineiden saldo ei riit‰").". <input type='checkbox' name='vakisinhyvaksy' value='OK'></td>";
		}

		echo "</tr>";
		echo "</form>";
		echo "</table><br><br>";

		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "<input type='hidden' name='toim'  value='$toim'>";
		if ($toim != 'KORJAA') {
			echo "<input type='hidden' name='tee' value=''>";
			echo "<input type='submit' name='' value='".t("Valmis")."'>";
		}
		else {
			echo "<input type='hidden' name='tee' value='alakorjaa'>";
			echo "<input type='hidden' name='valmistettavat' value='$valmistettavat'>";
			echo "<input type='submit' name='' value='".t("ƒl‰ korjaa")."'>";
		}
		echo "</form>";
	}

	// meill‰ ei ole valittua tilausta
	if ($tee == "") {
		$formi="find";
		$kentta="etsi";

		// tehd‰‰n etsi valinta
		echo "<br><form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim'  value='$toim'>";

		if ($toim == "TUOTE") {
			echo t("Etsi valmistetta/raaka-ainetta").": ";
		}
		else {
			echo t("Etsi asiakasta/valmistusta").": ";
		}

		echo "<input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';

		if ($toim == "TUOTE" and $etsi != "") {
			$haku = " and tilausrivi.tuoteno = '$etsi' ";
		}
		else {
			if (is_string($etsi))  $haku = " and lasku.nimi LIKE '%$etsi%' ";
			if (is_numeric($etsi)) $haku = " and lasku.tunnus = '$etsi' ";
		}


		if ($toim == "TUOTE") {
			$query 		= "	SELECT tilausrivi.tuoteno,
							sum(tilausrivi.varattu) varattu,
							GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus,
							GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus,
							GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY tilausrivi.tuoteno";
			$orderby 	= " tuoteno, lasku.tunnus, varattu";
			$alatilat 	= " 'C','B' ";
			$lisa 		= " and tilausrivi.tyyppi in ('W','M')
							and tilausrivi.toimitettu = ''
							and tilausrivi.varattu != 0";
		}
		elseif ($toim == "KORJAA") {
			$query	 	= "	SELECT lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
							lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
							lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY lasku.tunnus";
			$alatilat 	= " 'V','K' ";
			$orderby 	= " lasku.tunnus";
			$lisa 		= " ";
		}
		else {
			$query	 	= "	SELECT lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
							lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
							lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY lasku.tunnus";
			$alatilat 	= " 'C','B' ";
			$orderby 	= " lasku.tunnus";
			$lisa 		= " ";
			$limit 		= " LIMIT 100 ";
		}

		$query .= "	from tilausrivi, lasku
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and lasku.yhtio = tilausrivi.yhtio
					and lasku.tunnus = tilausrivi.otunnus
					and lasku.tila 	= 'V'
					and lasku.alatila  in ($alatilat)
					$lisa
					$haku
					$grouppi
					$limit";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) > 0) {
			echo "<table>";

			if ($toim == "TUOTE") {
				echo "<tr><th>".t("Valmistus")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Valmiste")."</th><th>".t("Kpl")."</th><tr>";
			}
			else {
				echo "<tr><th>".t("Valmistus")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Viesti")."</th><tr>";
			}

			while ($tilrow = mysql_fetch_array($tilre)) {

				echo "<tr><td valign='top'>$tilrow[tunnus]</td><td valign='top'>$tilrow[nimi] $tilrow[nimitark]</td><td valign='top'>$tilrow[ytunnus]</td>";


				if ($toim == "TUOTE") {
					echo "<td valign='top'>$tilrow[tuoteno]</td><td valign='top'>$tilrow[varattu]</td>";
				}
				else {
					echo "<td valign='top'>$tilrow[viesti]</td>";
				}

				echo "	<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='tee' value='VALMISTA'>
						<input type='hidden' name='tulin' value='VALINNASTA'>
						<input type='hidden' name='toim'  value='$toim'>
						<input type='hidden' name='valmistettavat' value='$tilrow[valmistettavat]'>
						<input type='submit' value='".t("Valitse")."'></td></tr></form>";
			}
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yht‰‰n valmistettavaa tilausta/tuotetta ei lˆytynyt")."...</font>";
		}
	}

	require "../inc/footer.inc";
?>
