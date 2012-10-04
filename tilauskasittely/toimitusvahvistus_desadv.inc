<?php

	/**
	* Despatch Advice Messages
	* DESADV D.00A
	* Version 0.2
	* EDIT: FI0089
	**/

	// Tarvitaan $otunnukset jossa on tilauksien tunnukset
	// Tarvitaan $tunnukset jossa on rahtikirjojen tunnukset
	// Tarvitaan $rahtikirjanro jossa on rahtikirjan numero
	// Tarvitaan $toitarow jossa on laskun toimitustavan tiedot
	// Passataan $tulostauna, mikäli se on tyhjä, niin ei tulosteta uusia headereitä

	//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
	if ($desadv_host != '' and $desadv_user != '' and $desadv_pass != '' and $desadv_path != '') {
		//	Haetaan otsikkotiedot pohjalle
		$query = "	SELECT lasku.*, laskun_lisatiedot.*,
					lasku.tunnus laskutunnus,
					asiakas.nimi asiakasnimi,
					asiakas.nimitark asiakasnimitark,
					asiakas.osoite asiakasosoite,
					asiakas.postitp asiakaspostitp,
					asiakas.postino asiakaspostino,
					asiakas.maa asiakasmaa,
					asiakas.toim_nimi asiakastoim_nimi,
					asiakas.toim_nimitark asiakastoim_nimitark,
					asiakas.ovttunnus asiakasovttunnus,
					asiakas.ovttunnus asiakastoim_ovttunnus,
					asiakas.toim_osoite asiakastoim_osoite,
					asiakas.toim_postitp asiakastoim_postitp,
					asiakas.toim_postino asiakastoim_postino,
					asiakas.toim_maa asiakastoim_maa
					FROM lasku
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus in ({$otunnukset})
					ORDER BY lasku.tunnus";
		$desadv_laskures = pupe_query($query);

		$rajaus = 1;

		while ($desadv_laskurow = mysql_fetch_assoc($desadv_laskures)) {

			$lahtunniste = "";

			$query = "	SELECT *
						FROM rahtikirjat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus in ({$tunnukset})";
			$desadv_rakir_res = pupe_query($query);

			if (mysql_num_rows($desadv_rakir_res) > 0) {
				$desadv_rakir_row = mysql_fetch_assoc($desadv_rakir_res);
			}
			else {
				$desadv_rakir_row["tulostettu"] = date("Y-m-d H:i:s");
				$desadv_rakir_row["rahtikirjanro"] = 0;
				$desadv_rakir_row["viesti"] = "";
			}

			$desadv_message = '';
			$error 			= '';
			$x 				= 0; // yhteensä rivejä koko filessä

			if ($tulostauna != "") {
				$kuljetustunnus = "PS".sprintf("%012.12s",$desadv_laskurow["laskutunnus"]); // tämä voi olla yhteensä max 14 merkkiäpitkä stringjono

				// ED2 ja UNA kehykset.
				// NRQ:0 = Ei CON kuittauspyyntöä, NRQ:1 = CON kuittauspyyntö
				$desadv_message  = "'''ED2''':{$desadv_laskurow["asiakasovttunnus"]}:OVT':{$desadv_laskurow["yhtio_ovttunnus"]}:OVT'{$kuljetustunnus}'1'STX:EDIFACT'TYP:EDI'NRQ:0'''";
				$desadv_message .= "\nUNA:+.? '";

				// Valmistelupäiväys ja valmisteluaika on now(), jollei parempaa tietoa tule.
				$valpvm = date("ymd");
				$valaika = date("Hi");
				$lahtunniste = $valpvm.$valaika.$rajaus;

				// UNB ja UNOC kehys
				// Ton DESADVD00A perässä on 4 plusmerkkiä, mikäli sinne viimeiseksi merkiksi laitetaan ennen hipsua 1 niin se on testisanoma
				$desadv_message .= "UNB+UNOC:1+{$desadv_laskurow["yhtio_ovttunnus"]}:30+{$desadv_laskurow["asiakasovttunnus"]}:30+{$valpvm}:{$valaika}+{$lahtunniste}++DESADVD00A++++'";
			}
			// UNH - message header
			$desadv_message .= "UNH+{$desadv_laskurow['laskutunnus']}+DESADV:D:00A:UN:FI0089'";
			$x++;

			// BGM (beginning of message) - identification data of despatch advice
			// 351 = despatch advice
			// document identifier? onko tämä laskun tunnus myös?
			$desadv_message .= "BGM+351+{$desadv_laskurow['laskutunnus']}+9'";
			$x++;

			// DTM - date of despatch advice
			// 0000-00-00 00:00:00
			// 202   YYMMDDHHMMSS

			list($date, $time) = explode(" ", $desadv_rakir_row['tulostettu']);
			list($year, $month, $day) = explode("-", $date);
			$desadv_message .= "DTM+137:{$year}{$month}{$day}:102'";
			$x++;

			// DTM - despatch and/or delivery date/time
			// 11 = despatch date/time
			$desadv_message .= "DTM+11:{$year}{$month}{$day}:102'";
			$x++;

			// DTM - despatch and/or delivery date/time
			// 171 = tilauspäivä
			list($date, $time) = explode(" ", $desadv_laskurow['luontiaika']);
			list($year, $month, $day) = explode("-", $date);
			$desadv_message .= "DTM+171:{$year}{$month}{$day}:102'";
			$x++;

			// DTM - despatch and/or delivery date/time
			// 171 = tilauspäivä
			list($date, $time) = explode(" ", $desadv_laskurow['toimaika']);
			list($year, $month, $day) = explode("-", $date);
			$desadv_message .= "DTM+69:{$year}{$month}{$day}:102'";
			$x++;

			// RFF - customer reference number
			// CR = customer reference number
			// The format of this reference number is nnn-llllll, where nnn is Stockmann's merchandise group and llllll is the actual Stockmann's order number
			if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
				$desadv_message .= "RFF+CR:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35)."'";
				$x++;
			}
			else {
				$error .= "Asiakkaan tilausnumero puuttui!<br>";
			}

			// RFF - waybill number
			// AAM = waybill number
			$desadv_message .= "RFF+AAM:".substr($desadv_rakir_row["rahtikirjanro"], 0, 35)."'";
			$x++;

			// RFF - buyer's order number
			// CO = buyer's order number
			if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
				if (strpos($desadv_laskurow['asiakkaan_tilausnumero'], "-") !== FALSE) {
					// Stockmannille explodetaan
					list($group, $order) = explode("-", $desadv_laskurow['asiakkaan_tilausnumero']);
					$desadv_message .= "RFF+CO:".substr($order, 0, 35)."'";
				}
				else {
					$desadv_message .= "RFF+CO:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35)."'";
				}
				$x++;
			}

			// NAD - identification of the seller
			// SE = seller
			// 100 = Finnish EDI identifier
			$desadv_message .= "NAD+SE+{$yhtiorow['ovttunnus']}:100++".substr($yhtiorow["nimi"], 0, 35)."+".substr($yhtiorow["osoite"], 0, 35)."+".substr($yhtiorow["postitp"], 0, 35)."++".substr($yhtiorow["postino"], 0, 17)."+".substr($yhtiorow["maa"], 0, 3)."'";
			$x++;

			$desadv_message .= "NAD+CZ+{$yhtiorow['ovttunnus']}::92++".substr($yhtiorow["nimi"], 0, 35)."+".substr($yhtiorow["osoite"], 0, 35)."+".substr($yhtiorow["postitp"], 0, 35)."++".substr($yhtiorow["postino"], 0, 17)."+".substr($yhtiorow["maa"], 0, 3)."'";
			$x++;

			// CTA - contact information
			$query = "	SELECT *
						FROM yhteyshenkilo
						WHERE yhtio 	 = '$kukarow[yhtio]'
						AND liitostunnus = '$desadv_laskurow[liitostunnus]'
						AND nimi 		 = '$desadv_laskurow[tilausyhteyshenkilo]'
						and tyyppi	 	 = 'A'";
			$yhteyshenkiloresult = pupe_query($query);
			$yhteyshenkiloasiakas = mysql_fetch_assoc($yhteyshenkiloresult);

			$yhteyshenkilo = '';
			$yhteyshenkilo_puh = '';

			if ($yhteyshenkiloasiakas['nimi'] != '') {
				$yhteyshenkilo = $yhteyshenkiloasiakas['nimi'];
				$yhteyshenkilo_puh = $yhteyshenkiloasiakas['puh'] != '' ? $yhteyshenkiloasiakas['puh'] : ($yhteyshenkiloasiakas['gsm'] != '' ? $yhteyshenkiloasiakas['gsm'] : '');
			}
			elseif ($desadv_laskurow['yhteyshenkilo_kaupallinen'] != '') {
				$query = "	SELECT *
							FROM yhteyshenkilo
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus 	= '$desadv_laskurow[yhteyshenkilo_kaupallinen]'
							and tyyppi	= 'A'";
				$yhteyshenkilo_kaupallinen_res = pupe_query($query);
				$yhteyshenkilo_kaupallinen_row = mysql_fetch_assoc($yhteyshenkilo_kaupallinen_res);

				$yhteyshenkilo = $yhteyshenkilo_kaupallinen_row['nimi'];
				$yhteyshenkilo_puh = $yhteyshenkilo_kaupallinen_row['puh'] != '' ? $yhteyshenkilo_kaupallinen_row['puh'] : ($yhteyshenkilo_kaupallinen_row['gsm'] != '' ? $yhteyshenkilo_kaupallinen_row['gsm'] : '');
			}
			elseif ($desadv_laskurow['yhteyshenkilo_tekninen'] != '') {
				$query = "	SELECT *
							FROM yhteyshenkilo
							WHERE yhtio	= '$kukarow[yhtio]'
							AND tunnus	= '$desadv_laskurow[yhteyshenkilo_tekninen]'
							and tyyppi	= 'A'";
				$yhteyshenkilo_tekninen_res = pupe_query($query);
				$yhteyshenkilo_tekninen_row = mysql_fetch_assoc($yhteyshenkilo_tekninen_res);

				$yhteyshenkilo = $yhteyshenkilo_tekninen_row['nimi']; // max 35 merkkiä, R (riippuu maasta)
				$yhteyshenkilo_puh = $yhteyshenkilo_tekninen_row['puh'] != '' ? $yhteyshenkilo_tekninen_row['puh'] : ($yhteyshenkilo_tekninen_row['gsm'] != '' ? $yhteyshenkilo_tekninen_row['gsm'] : '');
			}

			// IC = information contact
			if ($yhteyshenkilo != '') {
				$desadv_message .= "CTA+IC+:".substr($yhteyshenkilo, 0, 35)."'";
				$x++;

				// COM - communication contact
				if ($yhteyshenkilo_puh != '') {
					$desadv_message .= "COM+".substr($yhteyshenkilo_puh, 0, 512).":TE'";
					$x++;
				}
				else {
					$yhteyshenkilo_puh = $desadv_laskurow['puhelin'] != '' ? $desadv_laskurow['puhelin'] : ($desadv_laskurow['gsm'] != '' ? $desadv_laskurow['gsm'] : ($desadv_laskurow['tyopuhelin'] != '' ? $desadv_laskurow['tyopuhelin'] : '')); // max 15 merkkiä, C

					if ($yhteyshenkilo_puh != '') {
						$desadv_message .= "COM+".substr($yhteyshenkilo_puh, 0, 512).":TE'";
						$x++;
					}
				}
			}

			// NAD - identification of buyer
			if ($desadv_laskurow['asiakasovttunnus'] != '' and $desadv_laskurow['asiakasnimi'] != '') {
				$desadv_message .= "NAD+BY+$desadv_laskurow[asiakasovttunnus]:100++".substr($desadv_laskurow["asiakasnimi"], 0, 35).":".substr($desadv_laskurow["asiakasnimitark"], 0, 35)."+".substr($desadv_laskurow["asiakasosoite"], 0, 35)."+".substr($desadv_laskurow["asiakaspostitp"], 0, 35)."++".substr($desadv_laskurow["asiakaspostino"], 0, 17)."+".substr($desadv_laskurow["asiakasmaa"], 0, 3)."'";
				$x++;
			}
			else {
				$error .= "Asiakkaan ovt-tunnus / nimi puuttui!<br>";
			}

			// NAD - identification of the delivery party
			if ($desadv_laskurow['asiakastoim_nimi'] != '' and $desadv_laskurow['asiakastoim_ovttunnus'] != '') {
				$desadv_message .= "NAD+DP+{$desadv_laskurow['asiakastoim_ovttunnus']}::92++".substr($desadv_laskurow["asiakastoim_nimi"], 0, 35).":".substr($desadv_laskurow["asiakastoim_nimitark"], 0, 35)."+".substr($desadv_laskurow["asiakastoim_osoite"], 0, 35)."+".substr($desadv_laskurow["asiakastoim_postitp"], 0, 35)."++".substr($desadv_laskurow["asiakastoim_postino"], 0, 17)."+".substr($desadv_laskurow["asiakastoim_maa"], 0, 3)."'";
				$x++;

				$desadv_message .= "LOC+11+:::".substr($desadv_laskurow["asiakastoim_postitp"], 0, 70)."'";
				$x++;
			}
			else {
				$error .= "Asiakkaan toim_ovt-tunnus / toim_nimi puuttui!<br>";
			}

			// NAD - identification of consignee
			if ($toitarow['toim_nimi'] != '' and $toitarow['toim_ovttunnus'] != '') {
				$desadv_message .= "NAD+CN+{$toitarow['toim_ovttunnus']}::92++".substr($toitarow["toim_nimi"], 0, 35).":".substr($toitarow["toim_nimitark"], 0, 35)."+".substr($toitarow["toim_osoite"], 0, 35)."+".substr($toitarow["toim_postitp"], 0, 35)."++".substr($toitarow["toim_postino"], 0, 17)."+".substr($toitarow["toim_maa"], 0, 3)."'";
				$x++;
			}
			else {
				$error .= "Toimitustavan toim_ovt-tunnus / toim_nimi puuttui!<br>";
			}

			if (trim($desadv_laskurow['toimitusehto']) != '') {
				// TOD - terms of delivery
				$desadv_message .= "TOD+6+Z2K+".substr($desadv_laskurow['toimitusehto'], 0, 3)."'";
				$x++;
			}

			// LOC - place/location identification
			// ei ole pupessa listausta UN/LOCODE
			$desadv_message .= "LOC+1'";
			$x++;

			// Katsotaan löytyykö tilaukselle keräyserä
			$query = "	SELECT sscc, pakkaus, GROUP_CONCAT(tilausrivi) AS tilausrivit
						FROM kerayserat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$desadv_laskurow['laskutunnus']}'
						GROUP BY 1,2
						ORDER BY 1,2";
			$keraysera_res = pupe_query($query);

			if (mysql_num_rows($keraysera_res) > 0) {

				while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

					// CPS - consignment packing sequence
					$desadv_message .= "CPS+{$keraysera_row['sscc']}++1'";
					$x++;

					// PAC - number and type of packages
					$desadv_message .= "PAC+1++PK'";
					$x++;

					$query = "	SELECT *
								FROM pakkaus
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$keraysera_row['pakkaus']}'";
					$pakkaus_res = pupe_query($query);
					$pakkaus_row = mysql_fetch_assoc($pakkaus_res);

					if ($pakkaus_row['korkeus'] == 0 or $pakkaus_row['leveys'] == 0 or $pakkaus_row['syvyys'] == 0) {
						$error .= "Pakkauksella {$pakkaus_row['pakkaus']} {$pakkaus_row['pakkauskuvaus']} on puutteelliset mittatiedot<br>";
					}

					// Haetaan pakkauksen tiedot
					$query = "	SELECT SUM(tuote.tuotemassa * kerayserat.kpl_keratty) kilot
								FROM tilausrivi
								JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.tunnus IN ({$keraysera_row['tilausrivit']})";
					$paino_res = pupe_query($query);
					$paino_row = mysql_fetch_assoc($paino_res);

					$paino_row['kilot'] += $pakkaus_row['oma_paino'];
					$paino_row['kilot'] = round($paino_row['kilot']);

					// Pakkauksen bruttopaino
					$desadv_message .= "MEA+AAE+AAB+KGM:{$paino_row['kilot']}'";
					$x++;

					// Pakkauksen tilavuus
					// m3 => litrat
					$pakkauksen_tilavuus_litroina = round($pakkaus_row['korkeus'] * $pakkaus_row['leveys'] * $pakkaus_row['syvyys'] * 1000);
					$desadv_message .= "MEA+AAE+ABJ+LTR:{$pakkauksen_tilavuus_litroina}'";
					$x++;

					$query = "	SELECT tuote.eankoodi,
								tilausrivi.tuoteno,
								tilausrivi.nimitys,
								tilausrivi.tilaajanrivinro,
								SUM(tilausrivi.varattu+tilausrivi.kpl) varattu
								FROM tilausrivi
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
		 						AND tilausrivi.tunnus IN ({$keraysera_row['tilausrivit']})
								AND tilausrivi.tyyppi != 'D'
								GROUP BY tuote.eankoodi, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro";
					$tuotteet_res = pupe_query($query);

					$i = 1;

					while ($tuotteet_row = mysql_fetch_assoc($tuotteet_res)) {

						if ($tuotteet_row["eankoodi"] == "") {
							$error .= "Tuotteelta {$tuotteet_row['tuoteno']} puuttuu EAN-koodi<br>";
						}

						// LIN - line item
						$desadv_message .= "LIN+{$i}++".substr($tuotteet_row["eankoodi"], 0, 35).":EN'";
						$x++;

						// PIA - additional product identification
						$tuotenumero = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", " ", $tuotteet_row['tuoteno']);
						$desadv_message .= "PIA+5+".substr($tuotenumero, 0, 35).":SA'";
						$x++;

						// IMD - additional information of the article
						$tuotteen_nimitys = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", " ", $tuotteet_row['nimitys']);
						$desadv_message .= "IMD+F+8+:::".substr($tuotteen_nimitys, 0, 256)."'";
						$x++;

						// QTY - quantity
						// PCE = piece
						// tarvitaan varmaan laittaa eri uniitteja???? UN/ECE recommendation number 20
						$desadv_message .= "QTY+12:".round($tuotteet_row['varattu'], 0).":PCE'";
						$x++;

						// RFF - buyer's order number
						// CO = buyer's order number
						if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
							if (strpos($desadv_laskurow['asiakkaan_tilausnumero'], "-") !== FALSE) {
								// Stockmannille explodetaan
								list($group, $order) = explode("-", $desadv_laskurow['asiakkaan_tilausnumero']);
								$desadv_message .= "RFF+CO:".substr($order, 0, 35).":{$i}'";
							}
							else {
								$desadv_message .= "RFF+CO:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35).":{$i}'";
							}
							$x++;
						}

						// DTM - despatch and/or delivery date/time
						// 171 = tilauspäivä
						list($date, $time) = explode(" ", $desadv_laskurow['luontiaika']);
						list($year, $month, $day) = explode("-", $date);
						$desadv_message .= "DTM+171:{$year}{$month}{$day}:102'";
						$x++;

						$i++;
					}
				}
			}
			else {

				// CPS - consignment packing sequence
				$desadv_message .= "CPS+1'";
				$x++;

				if (trim($desadv_rakir_row['viesti']) != '') {
					$desadv_message .= "FTX+GEN+++{$desadv_rakir_row['viesti']}'";
					$x++;
				}

				unset($desadv_kollitres);

				if ((int) $rahtikirjanro != 0) {
					$query = "	SELECT SUM(kollit) kollit
								FROM rahtikirjat USE INDEX (otsikko_index)
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otsikkonro = '{$desadv_laskurow['laskutunnus']}'
								AND rahtikirjanro = '{$rahtikirjanro}'";
					$desadv_kollitres = pupe_query($query);
				}

				if (isset($desadv_kollitres) and mysql_num_rows($desadv_kollitres) > 0) {
					$desadv_kollitrow = mysql_fetch_assoc($desadv_kollitres);
				}
				else {
					$desadv_kollitrow["kollit"] = 0;
				}

				// PAC - number and type of packages
				$desadv_message .= "PAC+{$desadv_kollitrow['kollit']}++PK:6'";
				$x++;

				// LIN - line item
				$query = "	SELECT tuote.eankoodi,
							tilausrivi.tuoteno,
							tilausrivi.nimitys,
							tilausrivi.tilaajanrivinro,
							SUM(tilausrivi.varattu+tilausrivi.kpl) varattu
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
	 						AND tilausrivi.otunnus = '{$desadv_laskurow['laskutunnus']}'
							AND tilausrivi.tyyppi != 'D'
							GROUP BY tuote.eankoodi, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro";
				$tuotteet_res = pupe_query($query);

				$i = 1;

				while ($tuotteet_row = mysql_fetch_assoc($tuotteet_res)) {

					if ($tuotteet_row["eankoodi"] == "") {
						$error .= "Tuotteelta {$tuotteet_row['tuoteno']} puuttuu EAN-koodi<br>";
					}

					$desadv_message .= "LIN+{$i}++".substr($tuotteet_row["eankoodi"], 0, 35).":EN'";
					$x++;

					// PIA - additional product identification
					$desadv_message .= "PIA+5+".substr(str_replace(array("+",":","'"), " ", $tuotteet_row['tuoteno']), 0, 35).":SA'";
					$x++;

					// IMD - additional information of the article
					$desadv_message .= "IMD+F+8+:::".substr(str_replace(array("+",":","'"), " ", $tuotteet_row['nimitys']), 0, 256)."'";
					$x++;

					// QTY - quantity
					// PCE = piece
					// tarvitaan varmaan laittaa eri uniitteja???? UN/ECE recommendation number 20
					$desadv_message .= "QTY+12:".round($tuotteet_row['varattu'], 0).":PCE'";
					$x++;

					$i++;
				}
			}

			// CNT - control total
			$desadv_message .= "CNT+2:{$i}'";
			$x++;

			// UNT - message trailer
			$x++;
			$desadv_message .= "UNT+{$x}+{$desadv_laskurow['laskutunnus']}'";

			if ($tulostauna != "") {
				// loppuun pakollinen UNZ ja EOF. HUOM: UNZ ja EOF pitää olla erillisillä riveillä !!!
				$desadv_message .= "\nUNZ+1+{$lahtunniste}'";
				$desadv_message .= "\n'''EOF'''0'";
			}

			$filenimi = $pupe_root_polku."/dataout/desadv_import-{$kukarow['yhtio']}-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";

			//kirjoitetaan faili levylle..
			if (file_put_contents($filenimi, $desadv_message) === FALSE) {
				echo "<br/><font class='error'>",t("tiedoston kirjoitus EPÄONNISTUI")," ({$filenimi})</font><br/>";
			}

			if ($error == '') {
				// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
				// palautetaan $palautus ja $syy
				$ftphost = $desadv_host;
				$ftpuser = $desadv_user;
				$ftppass = $desadv_pass;
				$ftppath = $desadv_path;
				$ftpfile = realpath($filenimi);

				require ("inc/ftp-send.inc");
			}
			else {
				echo "<br/><font class='error'><br><hr>",t("AINEISTOA EI VOIDA LÄHETTÄÄ"),"!<br>",t("Aineistossa virheitä"),":<br>{$error}</font><hr><br>";
			}

			$rajaus++;
		}
	}
