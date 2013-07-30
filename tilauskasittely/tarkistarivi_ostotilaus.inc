<?php

	$riviok = 0;
	$saako_hyvaksya = 0;
	$varaosavirhe = "";

	$query = "	SELECT *
				FROM tuote
				LEFT JOIN tuotteen_toimittajat ON tuote.yhtio	= tuotteen_toimittajat.yhtio and tuote.tuoteno	= tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
				LEFT JOIN toimi ON tuote.yhtio = toimi.yhtio and toimi.tunnus = '$laskurow[liitostunnus]'
				WHERE tuote.tuoteno	= '$prow[tuoteno]'
				and tuote.yhtio		= '$kukarow[yhtio]'";
	$rarresult = pupe_query($query);
	$trow = mysql_fetch_array($rarresult);

	if (mysql_num_rows($rarresult) == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei löydy")."!<br>";
		$riviok++;
		$tilausok++;
	}

	if ($trow["toimittaja"] == "" and $toim_tarkistus!= "EI" and $riviok == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei voi tilata tältä toimittajalta")."!<br>";
		$riviok++;
		$tilausok++;
	}

	// katsotaan, ettei tilata epäkuranttia tuotetta
	if (($trow["epakurantti25pvm"] != '0000-00-00' or $trow["epakurantti50pvm"] != '0000-00-00' or $trow["epakurantti75pvm"] != '0000-00-00' or $trow["epakurantti100pvm"] != '0000-00-00') and $riviok == 0) {
		$varaosavirhe .= t("VIRHE: Tuote on epäkurantti, sitä ei voi tilata!")."<br>1/4: $trow[epakurantti25pvm]<br>1/2: $trow[epakurantti50pvm]<br>3/4: $trow[epakurantti75pvm]<br>1/1: $trow[epakurantti100pvm]";
		$riviok++;
		$tilausok++;
	}

	//poistotuotekäsittely
	if (in_array($trow["status"], array('P','X')) and $riviok == 0) {
		$varaosavirhe .= t("VIRHE: Tämä on poistotuote, sitä ei voi tilata")."!<br>";
		$riviok++;
		$tilausok++;
	}

	if ($trow['ei_saldoa'] != '' and $riviok == 0) {
		$varaosavirhe .= t("HUOM: Tuote ei ole varastoitava, sitä ei voida saavuttaa varastoon")."!<br>";
		$riviok++;
	}

	if ($yhtiorow['ostoera_pyoristys'] == 'K' and isset($ostoerahuom) and is_array($ostoerahuom) and in_array($prow['tunnus'], $ostoerahuom) and $kpl_st != $org_kpl) {
		$varaosavirhe .= "<font class='message'>".t("HUOM: Rivin määrä on pyöristetty täyteen ostoerään!")."<br></font>";
	}

	if ((float) $prow["hinta"] == 0 and (float) $trow['ostohinta'] == 0 and $riviok == 0 and $prow["var2"] != "OK") {
		// $varaosavirhe .= t("HUOM: Tuotteelta puuttuu ostohinta")."!<br>";
		// $riviok++;
		// $tilausok++;
		// $saako_hyvaksya++;
	}

	if ($trow["myyntihinta_maara"] != 0 and $prow["hinta"] != 0) {
		$prow["hinta"] = round($prow["hinta"] * $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
	}

	if ((float) $prow["hinta"] != (float) $trow['ostohinta'] and ($prow["var"] == "" or $toiminto == 'kohdista') and $trow["toimittaja"] != "" and $riviok == 0) {

		$paiv_hinta = (float) $paiv_hinta;

		if ($paivitaostohinta == 'ON' and ($ALPA != '' or $JOPA != '') and $prow["tuoteno"] == $paiv_tuoteno and $prow["tunnus"] == $paiv_rivitunnus) {
			$paok = '';
			if ($toiminto != 'kohdista') {
				$oshinq = "	UPDATE tilausrivi
							set var		= 'O'
							where yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$prow[tunnus]'
							and otunnus = '$kukarow[kesken]'";
				$ishinr = pupe_query($oshinq);
			}
			else {
				$paok = 'ok';
			}

			$ALPA = '';

			if ($JOPA == '') {
				$paivitaostohinta = '';
			}
		}

		if ($paivitaostohinta == 'ON' and $JOPA != '' and $prow["tuoteno"] == $paiv_tuoteno and ($prow["tunnus"] == $paiv_rivitunnus or $toiminto == 'kohdista')) {
			$paok ='';
			$oshinq = "	UPDATE tuotteen_toimittajat
						SET ostohinta		= '$paiv_hinta'
						WHERE yhtio			= '$kukarow[yhtio]'
						AND tuoteno			= '$prow[tuoteno]'
						AND liitostunnus	= '$laskurow[liitostunnus]'";
			$ishinr = pupe_query($oshinq);

			$paok = 'ok';

			$JOPA = '';
			$paivitaostohinta = '';

			if ($toiminto == 'kohdista') {
				echo "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("ostohinta").": $trow[ostohinta] -> $paiv_hinta</font><br>";
			}
			else {
				$varaosavirhe .= "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("ostohinta").": $trow[ostohinta] -> $paiv_hinta</font>";
			}

		}

		$varquery = "SELECT var FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tunnus	= '$prow[tunnus]'";
		$varresult = pupe_query($varquery);
		$varrow  = mysql_fetch_array($varresult);

		if (($toiminto != 'kohdista' and ($varrow['var'] != 'O' or $prow['var'] != 'O') and ($prow["tunnus"] != $paiv_rivitunnus or $paiv_rivitunnus == '')) or (($toiminto == 'kohdista' and $paok == ''))) {
			$varaosavirhe .= "	<form name='tilaus' action='$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
								<input type='hidden' name='toim' 				value = '$toim'>
								<input type='hidden' name='lopetus' 			value = '$lopetus'>
								<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
								<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
								<input type='hidden' name='toim_tuoteno'		value = '$toim_tuoteno'>
								<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
								<input type='hidden' name='toiminto' 			value = '$toiminto'>
								<input type='hidden' name='tee' 				value = 'Y'>
								<input type='hidden' name='muut_siirrettavat' 	value = '$muut_siirrettavat'>
								<input type='hidden' name='rivinotunnus' 		value = '$rivinotunnus'>
								<input type='hidden' name='paivitaostohinta' 	value = 'ON'>
								<input type='hidden' name='paiv_hinta' 			value = '$prow[hinta]'>
								<input type='hidden' name='paiv_tuoteno' 		value = '$prow[tuoteno]'>
								<input type='hidden' name='paiv_rivitunnus' 	value = '$prow[tunnus]'>";

			if ($toiminto == "kohdista") {
				$varaosavirhe .= "<input type='hidden' name='tuoteno' 	value = '$tuoteno'>";
				$varaosavirhe .= "<input type='hidden' name='kpl' 		value = '$kpl'>";
				$varaosavirhe .= "<input type='hidden' name='var' 		value = '$var'>";
				$varaosavirhe .= "<input type='hidden' name='hinta' 	value = '$hinta'>";
				$varaosavirhe .= "<input type='hidden' name='toimaika' 	value = '$toimaika'>";
				$varaosavirhe .= "<input type='hidden' name='alv' 		value = '$alv'>";
				$varaosavirhe .= "<input type='hidden' name='kommentti' value = '$kommentti'>";
			}

			$varaosavirhe .= "	<font class='message'>".t("Päivitä tuotteen ostohinta").":<br> ".hintapyoristys($trow["ostohinta"])." $trow[valuutta] -> ".hintapyoristys($prow["hinta"])." $trow[valuutta]</font><br>
								<input type='submit' name='JOPA' value='".t("Päivitä")."'>
								<input type='submit' name='ALPA' value='".t("Älä päivitä")."'>
								</form>";
		}
	}

	//toimitusajan päivitys toimittajan toimitusaikaan
	if ((($trow["toimitusaika"] != 0 and $trow["toimitusaika"] != '') or ($trow["oletus_toimaika"] != 0 and $trow["oletus_toimaika"] != '')) and $riviok == 0 and strpos($_SERVER['SCRIPT_NAME'], "tilaus_osto.php") !== FALSE and ($yhtiorow["ostotilaukseen_toimittajan_toimaika"] == '1' or $yhtiorow["ostotilaukseen_toimittajan_toimaika"] == '2')) {

		if ($trow["toimitusaika"] != 0 and $trow["toimitusaika"] != '') {
			$ehdotus_pvm = date('Y-m-d',time() + $trow["toimitusaika"] * 24 * 60 * 60);
		}
		elseif ($trow["oletus_toimaika"] != 0 and $trow["oletus_toimaika"] != '') {
			$ehdotus_pvm = date('Y-m-d',time() + $trow["oletus_toimaika"] * 24 * 60 * 60);
		}

		if ($paivitatoimaika == "ON" and $prow["tunnus"] == $paiv_rivitunnus and $JOPATO != '') {

			if ($JOPATO != '') {
				$paivita_toimaika = "	UPDATE tilausrivi
										SET toimaika = '$paiv_toimaika'
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus	= '$paiv_rivitunnus'
										and otunnus = '$kukarow[kesken]'";
				$paivita_res = pupe_query($paivita_toimaika);


			}

			$varaosavirhe .= "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("toimitusaika").": ".tv1dateconv($prow["toimaika"])." -> ".tv1dateconv($ehdotus_pvm)."</font>";
			$paivitetty_ok = "YES";
			$JOPATO = "";
		}
		elseif ($ehdotus_pvm == $prow["toimaika"]) {
			$paivitetty_ok = "SAMA";
		}
		else {
			$paivitetty_ok = "";
		}

		if ($ehdotus_pvm != $prow["toimaika"] and $paivitetty_ok == "") {

			if ($varaosavirhe != "") {
				$varaosavirhe .= "<br>";
			}

			$varaosavirhe .= "	<form name='tilaus' action='$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
								<input type='hidden' name='toim' 				value = '$toim'>
								<input type='hidden' name='lopetus' 			value = '$lopetus'>
								<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
								<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
								<input type='hidden' name='toim_tuoteno'		value = '$toim_tuoteno'>
								<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
								<input type='hidden' name='toiminto' 			value = '$toiminto'>
								<input type='hidden' name='tee' 				value = 'Y'>
								<input type='hidden' name='muut_siirrettavat' 	value = '$muut_siirrettavat'>
								<input type='hidden' name='rivinotunnus' 		value = '$rivinotunnus'>
								<input type='hidden' name='paivitatoimaika' 	value = 'ON'>
								<input type='hidden' name='paiv_toimaika' 		value = '$ehdotus_pvm'>
								<input type='hidden' name='paiv_rivitunnus' 	value = '$prow[tunnus]'>
								<font class='message'>".t("Haluatko muuttaa toimitusajan")." ".tv1dateconv($prow["toimaika"])." ".t("tuotteen toimittajan toimitusaikaan")." ".tv1dateconv($ehdotus_pvm)."!</font><br>
								<input type='submit' name='JOPATO' value='".t("Päivitä")."'>
								</form>";
		}

	}

	$query = "	SELECT tuotteen_toimittajat.tehdas_saldo,
				toimi.tehdas_saldo_tarkistus
				FROM tuotteen_toimittajat use index (yhtio_tuoteno)
				JOIN toimi
				ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
					AND toimi.tunnus = tuotteen_toimittajat.liitostunnus )
				WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
				AND tuotteen_toimittajat.tuoteno = '{$prow['tuoteno']}'
				ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)";
	$tehtaan_saldo_result = pupe_query($query);

	$tehtaan_saldo_row = mysql_fetch_assoc($tehtaan_saldo_result);
	if (!empty($tehtaan_saldo_row['tehdas_saldo_tarkistus']) and $tehtaan_saldo_row['tehdas_saldo'] < $prow['varattukpl']) {
		$varaosavirhe .= "<font class='error'>".t("HUOM: Toimittajan tehtaan saldo on %d", "", $tehtaan_saldo_row['tehdas_saldo']).".<br></font>";
	}

	//@TODO tämä iffii kuntoon!
	if ($prow['tuoteno'] != '') {

		// Haetaan vastaavuusketjut korvaavat ketjun päätuotteella
		require_once 'vastaavat.class.php';

		$vastaavat = new Vastaavat($prow["tuoteno"]);

		if ($vastaavat->onkovastaavia()) {

			// Loopataan kaikki tuotteen vastaavuusketjut
			foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

				$vastaavat_table = "";

				// Haetaan tuotteet ketjukohtaisesti
				$_tuotteet = $vastaavat->tuotteet($ketju);

				foreach ($_tuotteet as $_tuote) {

					$vaihtoehtoinen = ($_tuote['vaihtoehtoinen'] == 'K') ? "<font color=#F00>".t("Vaihtoehtoinen, tarkista sopivuus")."!</font>" : "";

					if (strtoupper($_tuote['tuoteno']) != strtoupper($prow["tuoteno"])) {

						$tvrow = hae_tuote($_tuote['tuoteno']);

						list($V_hinta, $V_netto, $V_ale, $V_alehinta_alv, $V_alehinta_val) = alehinta($laskurow, $tvrow, $prow['varattukpl'], '', '', '');

						$tuotteen_toimittaja_query = "	SELECT tuotteen_toimittajat.tehdas_saldo,
														tuotteen_toimittajat.toim_tuoteno,
														tuotteen_toimittajat.ostohinta,
														toimi.nimi as toimittajan_nimi,
														toimi.tunnus as toimi_tunnus
														FROM tuotteen_toimittajat
														JOIN toimi
														ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
															AND toimi.ytunnus = tuotteen_toimittajat.toimittaja )
														WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
														AND tuotteen_toimittajat.tuoteno = '{$_tuote['tuoteno']}'";
						$tuotteen_toimittaja_result = pupe_query($tuotteen_toimittaja_query);
						$tuotteen_toimittaja_row = mysql_fetch_assoc($tuotteen_toimittaja_result);

						if ($tuotteen_toimittaja_row['tehdas_saldo'] < $prow['varattukpl']) {
							$tuotteen_toimittaja_row['tehdas_saldo'] = "<font class='error'>".sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo'])."</font>";
						}
						else {
							$tuotteen_toimittaja_row['tehdas_saldo'] = sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo']);
						}

						 $vastaavat_table .= "<tr class='aktiivi'>"
						 			."<td>{$_tuote["tuoteno"]} $vaihtoehtoinen</td>"
									."<td>{$tuotteen_toimittaja_row['toim_tuoteno']}</td>"
									."<td>{$tvrow["nimitys"]}</td>"
									."<td>{$tuotteen_toimittaja_row['toimittajan_nimi']}</td>"
									."<td>".sprintf("%.2f", $tuotteen_toimittaja_row['ostohinta'])."</td>"
									."<td>hinta</td>"
						 			."<td align='right'>".$tuotteen_toimittaja_row['tehdas_saldo']."</td>"
						 			."<td align='left'>"
						 			." <form method='post' name='jalkitoimita'>
						 			   	<input type='hidden' name='toim' 			value = '$toim'>
						 			   	<input type='hidden' name='lopetus' 		value = '$lopetus'>
						 			   	<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
						 			   	<input type='hidden' name='rivitunnus' 		value = '$prow[tunnus]'>
						 			   	<input type='hidden' name='vastaavatuoteno' value = '$_tuote[tuoteno]'>
										<input type='hidden' name='toimi_tunnus'	value = '{$tuotteen_toimittaja_row['toimi_tunnus']}'>
						 			   	<input type='hidden' name='tee' 			value = 'PV'>
						 			   	<input type='hidden' name='tapa' 			value = 'VAIHDARIVI'>
						 			   	<input type='Submit' value='".t("Vaihda tähän tuotteeseen")."' onclick='return confirm(\"".t('Oletko varma että haluat vaihtaa tuotteen')."\")'>
						 			   	</form> "
						 			."</td></tr>";
					}
				}
				echo "	<td valign='top' class='back' nowrap>
									<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>
									<input type='hidden' name='toim' 				value = '$toim'>
									<input type='hidden' name='lopetus' 			value = '$lopetus'>
									<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
									<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
									<input type='hidden' name='toim_tuoteno'		value = '$toim_tuoteno'>
									<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
									<input type='hidden' name='rivitunnus' 			value = '$prow[tunnus]'>
									<input type='hidden' name='tee' 				value = 'POISTA_RIVI'>
									<input type='Submit' value='".t("Poista")."'>
									</td></form>";

				if ($vastaavat_table != "") {
					if ($vastaavattuotteet == 0) {
						$vastaavat_html .= "<table width='100%'>";
					}

					$vastaavat_html .= "<tr><th>".t("Vastaava")." ".t("Tuoteno")."</th><th>".t("Toimittajan tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Toimittaja")."</th><th>".t("Ostohinta")."</th><th>".t("Hinta")."</th><th>".t("Tehtaan saldo")."</th>";
					$vastaavat_html .= "<th>";

					if ($vastaavattuotteet == 0) {
						$vastaavat_html .= "<form method='post' name='hyvaksy'>
											<input type='hidden' name='toim' 			value = '$toim'>
											<input type='hidden' name='lopetus' 		value = '$lopetus'>
											<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
											<input type='hidden' name='projektilla' 	value = '$projektilla'>
											<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
											<input type='hidden' name='mista' 			value = '$mista'>
											<input type='hidden' name='rivitunnus' 		value = '$prow[tunnus]'>
											<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
											<input type='hidden' name='menutila' 		value = '$menutila'>
											<input type='hidden' name='tila' 			value = 'OOKOOAA'>
											<input type='hidden' name='vastaavienkasittely' value = 'kylla'>
											<input type='Submit' value='".t("Piilota vastaavat tuotteet")."'>
											</form>";
					}

					$vastaavat_html .= "</th></tr>$vastaavat_table";
					$vastaavattuotteet = 1;
				}
			}

			if ($vastaavat_html != "") {
				$vastaavat_html .= "</table>";
			}
		}

		require_once 'korvaavat.class.php';
		$korvaavat = new Korvaavat($prow['tuoteno']);

		if (count($korvaavat->tuotteet()) > 0) {
			$korvaavat_tuotteet = $korvaavat->tuotteet();

			$korvaavat_table = "";

			foreach ($korvaavat_tuotteet as $korvaava_tuote) {
				$tuotteen_toimittaja_query = "	SELECT tuotteen_toimittajat.tehdas_saldo,
												tuotteen_toimittajat.toim_tuoteno,
												tuotteen_toimittajat.ostohinta,
												toimi.nimi as toimittajan_nimi,
												toimi.tunnus as toimi_tunnus
												FROM tuotteen_toimittajat
												JOIN toimi
												ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
													AND toimi.ytunnus = tuotteen_toimittajat.toimittaja )
												WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
												AND tuotteen_toimittajat.tuoteno = '{$korvaava_tuote['tuoteno']}'";
				$tuotteen_toimittaja_result = pupe_query($tuotteen_toimittaja_query);
				$tuotteen_toimittaja_row = mysql_fetch_assoc($tuotteen_toimittaja_result);

				if ($tuotteen_toimittaja_row['tehdas_saldo'] < $prow['varattukpl']) {
					$tuotteen_toimittaja_row['tehdas_saldo'] = "<font class='error'>".sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo'])."</font>";
				}
				else {
					$tuotteen_toimittaja_row['tehdas_saldo'] = sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo']);
				}

				 $korvaavat_table .= "<tr class='aktiivi'>"
							."<td>{$_tuote["tuoteno"]}</td>"
							."<td>{$tuotteen_toimittaja_row['toim_tuoteno']}</td>"
							."<td>{$tvrow["nimitys"]}</td>"
							."<td>{$tuotteen_toimittaja_row['toimittajan_nimi']}</td>"
							."<td>".sprintf("%.2f", $tuotteen_toimittaja_row['ostohinta'])."</td>"
							."<td>hinta</td>"
							."<td align='right'>".$tuotteen_toimittaja_row['tehdas_saldo']."</td>"
							."<td align='left'>"
							." <form method='post' name='jalkitoimita'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' 		value = '$prow[tunnus]'>
								<input type='hidden' name='vastaavatuoteno' value = '$korvaava_tuote[tuoteno]'>
								<input type='hidden' name='toimi_tunnus'	value = '{$tuotteen_toimittaja_row['toimi_tunnus']}'>
								<input type='hidden' name='tee' 			value = 'PV'>
								<input type='hidden' name='tapa' 			value = 'VAIHDARIVI'>
								<input type='Submit' value='".t("Vaihda tähän tuotteeseen")."' onclick='return confirm(\"".t('Oletko varma että haluat vaihtaa tuotteen')."\")'>
								</form> "
							."</td></tr>";
			}

			$vastaavat_html .= "<br/><br/>";
			$vastaavat_html .= "<table width='100%'>";

			$vastaavat_html .= "<tr><th>".t("Korvaava")." ".t("Tuoteno")."</th><th>".t("Toimittajan tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Toimittaja")."</th><th>".t("Ostohinta")."</th><th>".t("Hinta")."</th><th>".t("Tehtaan saldo")."</th>";
			$vastaavat_html .= "<th>";

			$vastaavat_html .= "<form method='post' name='hyvaksy'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$prow[tunnus]'>
								<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'OOKOOAA'>
								<input type='hidden' name='vastaavienkasittely' value = 'kylla'>
								<input type='Submit' value='".t("Piilota vastaavat tuotteet")."'>
								</form>";

			$vastaavat_html .= "</th></tr>$korvaavat_table";

			$vastaavat_html .= "</table>";
		}
	}
?>