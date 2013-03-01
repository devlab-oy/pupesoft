<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
		require("inc/parametrit.inc");
	}

	if (!isset($toim)) $toim = '';

	if ($toim == "VASTAANOTA_REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] != 'U') {
		echo "<font class='error'>".t("HUOM: Ohjelma on käytössä vain kun käytetään laajaa reklamaatioprosessia")."!</font>";
		exit;
	}

	if (isset($tee) and $tee == 'MITATOI_TARJOUS') {
		unset($tee);
	}

	if ($toim == 'TARJOUS' and $tee == 'MITATOI_TARJOUS_KAIKKI' and $tunnukset != "") {

		$query = "	UPDATE lasku
					SET tila = 'D',
					alatila  = 'T',
					comments = CONCAT(comments, ' $kukarow[nimi] ($kukarow[kuka]) mitätöi tilauksen ohjelmassa muokkaatilaus.php 1')
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila    = 'T'
					AND tunnus IN {$tunnukset}";
		pupe_query($query);

		$query = "	UPDATE tilausrivi
					SET tyyppi = 'D'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tyyppi  = 'T'
					AND otunnus IN {$tunnukset}";
		pupe_query($query);
	}

	if (isset($tee) and $tee == 'TOIMITA_ENNAKKO' and $yhtiorow["ennakkotilausten_toimitus"] == "M") {

		$toimita_ennakko = explode(",", $toimita_ennakko);

		foreach ($toimita_ennakko as $tilausnro) {
			$query  = "	SELECT *
						FROM lasku
						WHERE yhtio 	 = '$kukarow[yhtio]'
						AND tunnus 		 = '$tilausnro'
						AND tila 		 = 'E'
						AND tilaustyyppi = 'E'";
			$jtrest = pupe_query($query);

			while ($laskurow = mysql_fetch_assoc($jtrest)) {

				$query  = "	UPDATE lasku
							SET tila 	 = 'N',
							alatila 	 = '',
							clearing	 = 'ENNAKKOTILAUS',
							tilaustyyppi = ''
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$laskurow[tunnus]'";
				$apure  = pupe_query($query);

				$laskurow["tila"] 			= "N";
				$laskurow["alatila"] 		= "A";
				$laskurow["clearing"] 		= "ENNAKKOTILAUS";
				$laskurow["tilaustyyppi"] 	= "";

				// Päivitetään rivit
				$query  = "	SELECT tunnus, tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$laskurow[tunnus]'
							and tyyppi  = 'E'";
				$apure  = pupe_query($query);

				while ($rivirow = mysql_fetch_assoc($apure)) {

					$varastorotunnus = kuuluukovarastoon($rivirow["hyllyalue"], $rivirow["hyllynro"]);

					if ($laskurow["varasto"] > 0 and $varastorotunnus != $laskurow["varasto"]) {
						// Katotaan, että rivit myydään halutusta varastosta
						$query = "	SELECT tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllytaso, tuotepaikat.hyllyvali
									FROM tuotepaikat
									JOIN varastopaikat on (varastopaikat.yhtio=tuotepaikat.yhtio
						 			and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						 			and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')))
									WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
									AND tuotepaikat.tuoteno = '{$rivirow['tuoteno']}'
									and varastopaikat.tunnus = '{$laskurow["varasto"]}'
									ORDER BY saldo desc
									LIMIT 1";
						$tuotepaikka_result = pupe_query($query);

						if (mysql_num_rows($tuotepaikka_result) == 1) {
							$tuotepaikka_row = mysql_fetch_assoc($tuotepaikka_result);

							$rivirow["hyllyalue"] = $tuotepaikka_row["hyllyalue"];
							$rivirow["hyllynro"]  = $tuotepaikka_row["hyllynro"];
							$rivirow["hyllyvali"] = $tuotepaikka_row["hyllyvali"];
							$rivirow["hyllytaso"] = $tuotepaikka_row["hyllytaso"];
						}
					}
					elseif ($varastorotunnus == 0) {
						// Rivillä ei ollut mitään viksua paikkaa
						$query = "	SELECT tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllytaso, tuotepaikat.hyllyvali
									FROM tuotepaikat
									WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
									AND tuotepaikat.tuoteno = '{$rivirow['tuoteno']}'
									AND tuotepaikat.oletus != ''";
						$tuotepaikka_result = pupe_query($query);
						$tuotepaikka_row = mysql_fetch_assoc($tuotepaikka_result);

						$rivirow["hyllyalue"] = $tuotepaikka_row["hyllyalue"];
						$rivirow["hyllynro"]  = $tuotepaikka_row["hyllynro"];
						$rivirow["hyllyvali"] = $tuotepaikka_row["hyllyvali"];
						$rivirow["hyllytaso"] = $tuotepaikka_row["hyllytaso"];
					}

					$query  = "	UPDATE tilausrivi
								SET tyyppi 	= 'L',
								hyllyalue 	= '{$rivirow["hyllyalue"]}',
								hyllynro 	= '{$rivirow["hyllynro"]}',
								hyllyvali 	= '{$rivirow["hyllyvali"]}',
								hyllytaso 	= '{$rivirow["hyllytaso"]}'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivirow[tunnus]'
								and tyyppi  = 'E'";
					$updapure  = pupe_query($query);
				}

				// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
				$kukarow["kesken"] = $laskurow["tunnus"];

				$kateisohitus = "X";
				$mista = "jtselaus";

				require ("tilauskasittely/tilaus-valmis.inc");
			}
		}

		unset($tee);
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		if (!isset($asiakastiedot)) $asiakastiedot = '';
		if (!isset($limit)) $limit = '';
		if (!isset($etsi)) $etsi = '';

		// scripti balloonien tekemiseen
		js_popup();
		enable_ajax();

		// Saako poistaa tarjouksia
		$deletarjous = FALSE;

		if ($toim == "TARJOUS" or $toim == "TARJOUSSUPER" or $toim == "HYPER") {
			//Saako poistaa tarjouksia
			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio		= '$kukarow[yhtio]'
						and kuka		= '$kukarow[kuka]'
						and nimi		= 'tilauskasittely/tilaus_myynti.php'
						and alanimi 	= 'TARJOUS'
						and paivitys	= '1'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				$deletarjous = TRUE;
			}
		}

		echo "	<script type='text/javascript' language='JavaScript'>
				<!--
					function verify() {
						msg = '".t("Oletko varma?")."';

						if (confirm(msg)) {
							return true;
						}
						else {
							skippaa_tama_submitti = true;
							return false;
						}
					}

					function tarkista_mitatointi(count) {
						msg = '".t("Oletko varma, että haluat mitätöidä ")."' + count + '".t(" tarjousta?")."';

						if (confirm(msg)) {
							return true;
						}
						else {
							skippaa_tama_submitti = true;
							return false;
						}
					}
				-->
				</script>";

		$toim = strtoupper($toim);

		if ($toim == "" or $toim == "SUPER" or $toim == "KESKEN") {
			$otsikko = t("myyntitilausta");
		}
		elseif ($toim == "ENNAKKO") {
			$otsikko = t("ennakkotilausta");
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
			$otsikko = t("työmääräystä");
		}
		elseif ($toim == "REKLAMAATIO" or $toim == "REKLAMAATIOSUPER") {
			$otsikko = t("reklamaatiota");
		}
		elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
			$otsikko = t("reklamaatio");
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$otsikko = t("sisäistä työmääräystä");
		}
		elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
			$otsikko = t("valmistusta");
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER") {
			$otsikko = t("varastosiirtoa");
		}
		elseif ($toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
			$otsikko = t("myyntitiliä");
		}
		elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
			$otsikko = t("tarjousta");
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$otsikko = t("laskutuskieltoa");
		}
		elseif ($toim == "EXTRANET") {
			$otsikko = t("extranet-tilausta");
		}
		elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
			$otsikko = t("osto-tilausta");
		}
		elseif ($toim == "HAAMU") {
			$otsikko = t("työ/tarvikeostoa");
		}
		elseif ($toim == "YLLAPITO") {
			$otsikko = t("ylläpitosopimusta");
		}
		elseif ($toim == "PROJEKTI") {
			$otsikko = t("tilauksia");
		}
		elseif ($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") {
			$otsikko = t("tilauksia ja valmistuksia");
		}
		elseif ($toim == "JTTOIMITA") {
			$otsikko = t("JT-tilausta");
		}
		elseif ($toim == "HYPER") {
			$otsikko = t("tilauksia");
		}
		else {
			$otsikko = t("myyntitilausta");
			$toim = "";
		}

		if (($toim == "TARJOUS" or $toim == "TARJOUSSUPER") and $tee == '' and $kukarow["kesken"] != 0 and $tilausnumero != "") {
			$query_tarjous = "	UPDATE lasku
								SET alatila = tila,
								tila = 'D',
								muutospvm = now(),
								comments = CONCAT(comments, ' $kukarow[nimi] ($kukarow[kuka]) mitätöi tilauksen ohjelmassa muokkaatilaus.php 2')
								WHERE	yhtio = '$kukarow[yhtio]'
								AND		tunnus = $tilausnumero";
			$result_tarjous = pupe_query($query_tarjous);

			echo "<font class='message'>".t("Mitätöitiin lasku")." $tilausnumero</font><br><br>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {

			if ($toim == "VASTAANOTA_REKLAMAATIO") {
				$otsikkoteksti = t("Vastaanota");
			}
			else {
				$otsikkoteksti = t("Muokkaa");
			}

			echo "<font class='head'>".$otsikkoteksti." ".$otsikko."<hr></font>";

			// Tehdään popup käyttäjän lepäämässä olevista tilauksista
			if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'G'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'S'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='A' and alatila = '' and tilaustyyppi='A'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and tila = 'C' and alatila in ('A','B') and tilaustyyppi='R'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "REKLAMAATIO" or $toim == "REKLAMAATIOSUPER") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila = 'C' and alatila = '' and tilaustyyppi = 'R'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='T' and alatila in ('','A') and tilaustyyppi='T'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "OSTO") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi != 'O' and alatila = ''";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "OSTOSUPER") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi != 'O' and alatila in ('A','')";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "HAAMU") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi = 'O' and alatila = ''";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "ENNAKKO") {
				$query = "	SELECT lasku.*
							FROM lasku use index (tila_index)
							LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and (lasku.laatija = '$kukarow[kuka]' or lasku.tunnus = '$kukarow[kesken]')
							and lasku.tila in ('E', 'N')
							and lasku.alatila in ('','A','J')
							and lasku.tilaustyyppi = 'E'
							GROUP BY lasku.tunnus";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'V'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "" or $toim == "SUPER" or $toim == "PROJEKTI" or $toim == "KESKEN") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and alatila='' and tila in ('N','E')";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "LASKUTUSKIELTO") {
				$query = "	SELECT lasku.*
							FROM lasku use index (tila_index)
							JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
							WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila in ('N','L') and alatila != 'X'";
				$eresult = pupe_query($query);
			}
			elseif ($toim == "YLLAPITO") {
				$query = "	(SELECT lasku.*
							FROM lasku use index (tila_index)
							WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
							AND (lasku.laatija = '{$kukarow["kuka"]}' or lasku.tunnus = '{$kukarow["kesken"]}')
							AND tila = '0'
							AND alatila not in ('V','D'))

							UNION

							(SELECT lasku.*
							FROM lasku use index (tila_index)
							WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
							AND (lasku.laatija = '{$kukarow["kuka"]}' or lasku.tunnus = '{$kukarow["kesken"]}')
							AND tila = 'N'
							AND alatila = ''
							AND tilaustyyppi = 0)

							ORDER BY tunnus DESC";
				$eresult = pupe_query($query);
			}

			if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET" and $toim != "VALMISTUSMYYNTI" and $toim != "VALMISTUSMYYNTISUPER") {
				if (isset($eresult) and  mysql_num_rows($eresult) > 0) {
					// tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
					if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO" or $toim == "KESKEN") {
						$aputoim1 = "RIVISYOTTO";
						$aputoim2 = "PIKATILAUS";

						$lisa1 = t("Rivisyöttöön");
						$lisa2 = t("Pikatilaukseen");
					}
					elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
						$aputoim1 = "VALMISTAASIAKKAALLE";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "MYYNTITILISUPER") {
						$aputoim1 = "MYYNTITILI";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "SIIRTOLISTASUPER") {
						$aputoim1 = "SIIRTOLISTA";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "TARJOUSSUPER") {
						$aputoim1 = "TARJOUS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "TYOMAARAYSSUPER") {
						$aputoim1 = "TYOMAARAYS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
						$aputoim1 = "";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "HAAMU") {
						$aputoim1 = "HAAMU";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					else {
						$aputoim1 = $toim;
						$aputoim2 = "";

						$lisa1 = t("Muokkaa");
						$lisa2 = "";
					}

					if ($toim == "OSTO" or $toim == "OSTOSUPER") {
						echo "<form method='post' action='tilauskasittely/tilaus_osto.php'>";
					}
					else {
						echo "<form method='post' action='tilauskasittely/tilaus_myynti.php'>";
					}

					echo "	<input type='hidden' name='toim' value='$aputoim1'>
							<input type='hidden' name='tee' value='AKTIVOI'>";

					echo "<br><table>
							<tr>
							<th>".t("Kesken olevat").":</th>
							<td><select name='tilausnumero'>";

					while ($row = mysql_fetch_assoc($eresult)) {
						$select = "";

						//valitaan keskenoleva oletukseksi..
						if ($row['tunnus'] == $kukarow["kesken"]) {
							$select="SELECTED";
						}
						echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
					}

					echo "</select></td>";

					if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO" or $toim == "KESKEN") {
						echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
					}

					echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
					echo "</tr></table></form>";
				}
				else {
					echo t("Sinulla ei ole aktiivisia eikä kesken olevia tilauksia").".<br>";
				}
			}
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
			// Näytetään muuten vaan sopivia tilauksia
			echo "<br><br>";
			echo "<form method='post' name='hakuformi'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='asiakastiedot' value='$asiakastiedot'>";
			echo "<input type='hidden' name='limit' value='$limit'>";
			echo "<font class='head'>".t("Etsi")." $otsikko<hr></font>";
			if ($toim == "YLLAPITO") {
				echo t("Syötä tilausnumeron, asiakkaan tilausnumeron, nimen, laatijan tai sopimuksen lisätiedon osa");
			}
			else if ($toim == "MYYNTITILITOIMITA") {
				echo t("Syötä tuotenumeron, tilausnumeron, nimen tai laatijan osa");
			}
			else {
				echo t("Syötä tilausnumeron, nimen tai laatijan osa");
			}
			echo "<input type='text' name='etsi'>";
			echo "<input type='Submit' value = '".t("Etsi")."'>";
			echo "</form>";
			echo "<br>";

			// kursorinohjausta
			$formi  = "hakuformi";
			$kentta = "etsi";

			// pvm 30 pv taaksepäin
			$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
			$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
			$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

			$haku = "";
			$myyntitili_haku = "";

			if ($toim == "MYYNTITILITOIMITA") {
				$myyntitili_haku = " or tilausrivi.tuoteno like '%$etsi%' ";
			}

			if ($kukarow["yhtio"] == "savt") {
				$myyntitili_haku .= " or lasku.viesti like '%$etsi%' ";
			}

			if (is_string($etsi))  {
				$haku = " and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%' or kuka1.nimi like '%$etsi%' or kuka2.nimi like '%$etsi%' $myyntitili_haku) ";
			}
			if (is_numeric($etsi)) {
				$haku = " and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%' $myyntitili_haku) ";
			}

			if ($toim == 'YLLAPITO' and $etsi != "" and $haku != "") {
				$haku = substr($haku, 0, -2); // Poistetaan vika sulku $hausta
				$haku .= " or tilausrivin_lisatiedot.sopimuksen_lisatieto1 like '%$etsi%' or tilausrivin_lisatiedot.sopimuksen_lisatieto2 like '%$etsi%' or lasku.asiakkaan_tilausnumero like '%$etsi%') ";
			}

			$seuranta = "";
			$seurantalisa = "";

			$kohde = "";
			$kohdelisa = "";

			$toimaikalisa = "";

			if ($kukarow['resoluutio'] == 'I' and $toim != "SIIRTOLISTA" and $toim != "SIIRTOLISTASUPER" and $toim != "MYYNTITILI" and $toim != "MYYNTITILISUPER" and $toim != "EXTRANET" and $toim != "TARJOUS") {
				$toimaikalisa = ' lasku.toimaika, ';
			}

			if ($limit == "") {
				$rajaus = "LIMIT 50";
			}
			else {
				$rajaus	= "";
			}
		}

		if ($asiakastiedot == "KAIKKI") {
			$asiakasstring = " concat_ws('<br>', lasku.ytunnus, concat_ws(' ',lasku.nimi, lasku.nimitark), if(lasku.nimi!=lasku.toim_nimi, concat_ws(' ',lasku.toim_nimi, lasku.toim_nimitark), NULL), if(lasku.postitp!=lasku.toim_postitp, lasku.toim_postitp, NULL)) ";
			$assel1 = "";
			$assel2 = "CHECKED";
		}
		else {
			$asiakasstring = " concat_ws('<br>', lasku.ytunnus, lasku.nimi, lasku.nimitark) ";
			$assel1 = "CHECKED";
			$assel2 = "";
		}

		echo "	<script language=javascript>
				function lahetys_verify(pitaako_varmistaa) {
					msg = pitaako_varmistaa;

					if (confirm(msg)) {
						return true;
					}
					else {
						skippaa_tama_submitti = true;
						return false;
					}
				}
			</script>";

		echo "<br><form method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='limit' value='$limit'>
				".t("Näytä vain laskutustiedot")." <input type='radio' name='asiakastiedot' value='NORMI' onclick='submit();' $assel1>
				".t("Näytä myös toimitusasiakkaan tiedot")." <input type='radio' name='asiakastiedot' value='KAIKKI' onclick='submit();' $assel2>
				</form>";

		$query_ale_lisa = generoi_alekentta('M');

		// Etsitään muutettavaa tilausta
		if ($toim == 'HYPER') {

			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

			if ($kukarow['hinnat'] == 0) $query .= " round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo, round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa, ";

			$query .= "	$toimaikalisa alatila, tila, lasku.tunnus, lasku.mapvm, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						WHERE lasku.yhtio = '$kukarow[yhtio]' and
						(((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
						or (lasku.tila = '0' and lasku.alatila NOT in ('D'))
						or (lasku.tila = 'N' and lasku.alatila = 'F')
						or (lasku.tila = 'V' and lasku.alatila in ('','A','B','C','J'))
						or (lasku.tila = 'V' and lasku.alatila in ('','A','B','J'))
						or (lasku.tila = 'T' and lasku.tilaustyyppi = 'T' and lasku.alatila in ('','A'))
						or (lasku.tila = 'T' and lasku.tilaustyyppi = 'T' and lasku.alatila in ('','A','X'))
						or (lasku.tila in ('A','L','N') and lasku.tilaustyyppi = 'A' and lasku.alatila != 'X')
						or (lasku.tila in ('L','N') and lasku.alatila != 'X')
						or (lasku.tila in ('L','N') and lasku.alatila in ('A',''))
						or (lasku.tila in ('L','N','C') and tilaustyyppi = 'R' and alatila in ('','A','B','C','J','D'))
						or (lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999')
						or (lasku.tila in ('R','L','N','A') and alatila NOT in ('X') and lasku.tilaustyyppi != '9')
						or (lasku.tila = 'E' and tilausrivi.tyyppi = 'E')
						or (lasku.tila = 'G' and lasku.alatila in ('','A','B','C','D','J','T'))
						or (lasku.tila = 'G' and lasku.alatila in ('','A','J'))
						or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J'))
						or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J'))
						or (lasku.tila = 'N' and lasku.alatila = 'U')
						or (lasku.tila = 'S' and lasku.alatila in ('','A','B','J','C'))
						or (lasku.tila in ('L','N','V') and lasku.alatila NOT in ('X','V'))
						or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'))
						$haku
						GROUP BY lasku.tunnus
						ORDER BY lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) arvo,
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) summa,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) jt_arvo,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) jt_summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == 'SUPER') {

			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

			if ($kukarow['hinnat'] == 0) $query .= " round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo, round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa, ";

			$query .= "	$toimaikalisa alatila, tila, lasku.tunnus, lasku.mapvm, lasku.tilaustyyppi, lasku.label
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'
						$haku
						GROUP BY lasku.tunnus
						ORDER BY lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) arvo,
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) summa,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) jt_arvo,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})),2) jt_summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 6;
		}
		elseif ($toim == 'ENNAKKO') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, viesti tilausviite, $toimaikalisa alatila, tila, lasku.tunnus, tilausrivi.tyyppi trivityyppi, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('E','N')
						and lasku.tilaustyyppi = 'E'
						$haku
						GROUP BY lasku.tunnus
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi = 'E')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'E'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "SIIRTOLISTA") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring varasto, lasku.luontiaika, lasku.toimaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == "SIIRTOLISTASUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring varasto, lasku.luontiaika, lasku.toimaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','B','C','D','J','T')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == "MYYNTITILI") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}
			$miinus = 4;
		}
		elseif ($toim == "MYYNTITILISUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "MYYNTITILITOIMITA") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'
						$haku
						GROUP BY lasku.tunnus
						order by lasku.luontiaika desc
						$rajaus";

			 // haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
				 				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
				 				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
				 				count(distinct lasku.tunnus) kpl
				 				FROM lasku use index (tila_index)
				 				JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
				 				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'";
				 $sumresult = pupe_query($sumquery);
				 $sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "JTTOIMITA") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'N'
						and lasku.alatila in ('U','T')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='N' and lasku.alatila='U'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == 'VALMISTUS') {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi varastoon, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'V'
						and lasku.alatila in ('','A','B','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tila = 'V'
								and lasku.alatila in ('','A','B','J')
								and lasku.tilaustyyppi != 'W'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "VALMISTUSSUPER") {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi varastoon, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'V'
						and lasku.alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tila = 'V'
								and lasku.alatila in ('','A','B','C','J')
								and lasku.tilaustyyppi != 'W'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "VALMISTUSMYYNTI") {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta $asiakasstring asiakas, $kohde lasku.viesti, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka.extranet extra, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
								and tilaustyyppi != 'W'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "VALMISTUSMYYNTISUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta $asiakasstring asiakas, $kohde lasku.viesti, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka.extranet extra, tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and tila in ('L','N','V')
						and alatila not in ('X','V')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and tila in ('L','N','V')
								and alatila not in ('X','V')
								and tilaustyyppi != 'W'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {

			if ($toim == "TYOMAARAYSSUPER") {
				$tyomalatlat = " and lasku.alatila != 'X' ";
			}
			else {
				$tyomalatlat = " and lasku.alatila in ('','A','B','C','J') ";
			}

			$query = "	SELECT lasku.tunnus tilaus,
						concat_ws('<br>', lasku.ytunnus, lasku.nimi, if (lasku.tilausyhteyshenkilo='', NULL, lasku.tilausyhteyshenkilo), if (lasku.viesti='', NULL, lasku.viesti), concat_ws(' ', ifnull((SELECT selitetark_2 FROM avainsana WHERE avainsana.yhtio=tyomaarays.yhtio and avainsana.laji = 'sarjanumeron_li' and avainsana.selite = 'MERKKI' and avainsana.selitetark=tyomaarays.merkki LIMIT 1), tyomaarays.merkki), tyomaarays.mallivari)) asiakas, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

			if ($kukarow['hinnat'] == 0) $query .= " round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo, round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa, ";

			$query .= "	$toimaikalisa alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' $tyomalatlat
						$haku
						GROUP BY lasku.tunnus
						ORDER BY lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
		    					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
		    					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
		    					count(distinct lasku.tunnus) kpl
		    					FROM lasku use index (tila_index)
		    					LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
		    					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' $tyomalatlat";
		    	$sumresult = pupe_query($sumquery);
		    	$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "REKLAMAATIO" or $toim == "VASTAANOTA_REKLAMAATIO" or $toim == "REKLAMAATIOSUPER") {

			if ($toim == "REKLAMAATIOSUPER") {
				$rekla_tila = " and lasku.tila in ('N','C','L') and lasku.alatila in ('','A','B','C','J','D') ";
			}
			elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
				$rekla_tila = " and lasku.tila = 'C' and lasku.alatila in ('A','B') ";
			}
			else {
				if ($yhtiorow['reklamaation_kasittely'] == 'U') {
					$rekla_tila = " and lasku.tila = 'C' and lasku.alatila = '' ";
				}
				else {
					$rekla_tila = " and tila in ('L','N','C') and alatila in ('','A') ";
				}
			}

			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tilaustyyppi = 'R'
						$rekla_tila
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
				   				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
						    	round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
						    	count(distinct lasku.tunnus) kpl
						    	FROM lasku use index (tila_index)
						    	JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
						    	WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tilaustyyppi = 'R'
								$rekla_tila";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$query = "	SELECT lasku.tunnus tilaus,
						concat_ws('<br>',lasku.nimi,lasku.tilausyhteyshenkilo,lasku.viesti, concat_ws(' ', ifnull((SELECT selitetark_2 FROM avainsana WHERE avainsana.yhtio=tyomaarays.yhtio and avainsana.laji = 'sarjanumeron_li' and avainsana.selite = 'MERKKI' and avainsana.selitetark=tyomaarays.merkki LIMIT 1), tyomaarays.merkki), tyomaarays.mallivari)) asiakas,
						lasku.ytunnus, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila in ('','A','B','J','C')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == "TARJOUS") {
			$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
						if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">Voimassa</font>', '<font class=\"red\">Erääntynyt</font>') voimassa,
						DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu, lasku.liitostunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A')
						$haku
						ORDER BY lasku.tunnus desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A')";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "TARJOUSSUPER") {
			$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
						if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">Voimassa</font>', '<font class=\"red\">Erääntynyt</font>') voimassa,
						DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A','X')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan kaikkien avoimien tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A')";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "EXTRANET") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.mapvm, lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

		   // haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
		   						round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
				   				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
				   				count(distinct lasku.tunnus) kpl
				   				FROM lasku use index (tila_index)
				   				JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
				   				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999'";
				   $sumresult = pupe_query($sumquery);
				   $sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == 'OSTO') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, if(kuka1.extranet is null, 0, if(kuka1.extranet != '', 1, 0)) kuka_ext,
						sum(if(tilausrivi.kpl is not null and tilausrivi.kpl != 0, 1, 0)) varastokpl,
						sum(if(tilausrivi.jaksotettu is not null and tilausrivi.jaksotettu != 0, 1, 0)) vahvistettukpl
						FROM lasku use index (tila_index)
						LEFT JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'O'
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio 		= '$kukarow[yhtio]'
						and lasku.tila		 	= 'O'
						and lasku.alatila		= ''
						and lasku.tilaustyyppi != 'O'
						$haku
						GROUP BY 1,2,3,4,5,6,7,8
						ORDER by kuka_ext, lasku.luontiaika desc
						$rajaus";
			$miinus = 6;
		}
		elseif ($toim == 'OSTOSUPER') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, if(kuka1.extranet is null, 0, if(kuka1.extranet != '', 1, 0)) kuka_ext,
						sum(if(tilausrivi.kpl is not null and tilausrivi.kpl != 0, 1, 0)) varastokpl,
						sum(if(tilausrivi.jaksotettu is not null and tilausrivi.jaksotettu != 0, 1, 0)) vahvistettukpl
						FROM lasku use index (tila_index)
						LEFT JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'O'
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio 		= '$kukarow[yhtio]'
						and lasku.tila 			= 'O'
						and lasku.alatila in ('A','')
						and lasku.tilaustyyppi != 'O'
						$haku
						GROUP BY 1,2,3,4,5,6,7,8
						ORDER by kuka_ext, lasku.luontiaika desc
						$rajaus";
			$miinus = 6;
		}
		elseif ($toim == 'HAAMU') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila			= 'O'
						and lasku.alatila		= ''
						and lasku.tilaustyyppi	= 'O'
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 4;
		}
		elseif ($toim == 'PROJEKTI') {
			$query = "	SELECT if(lasku.tunnusnippu > 0 and lasku.tunnusnippu!=lasku.tunnus, concat(lasku.tunnus,',',lasku.tunnusnippu), lasku.tunnus) tilaus, $seuranta lasku.nimi asiakas, $kohde lasku.ytunnus, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tunnusnippu, lasku.liitostunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ('R','L','N','A') and alatila NOT IN ('X') and lasku.tilaustyyppi!='9'
						$haku
						ORDER by lasku.tunnusnippu desc, tunnus asc
						$rajaus";
			$miinus = 5;
		}
		elseif ($toim == 'YLLAPITO') {
			$query = "  SELECT lasku.tunnus tilaus,
						lasku.asiakkaan_tilausnumero 'asiak. tilno',
						$asiakasstring asiakas,
						lasku.luontiaika,
						if(kuka1.kuka != kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi) laatija,
						concat_ws('###', sopimus_alkupvm, sopimus_loppupvm) sopimuspvm,
						group_concat(distinct tilausrivin_lisatiedot.sopimuksen_lisatieto1 separator '<br>') sarjanumero,
						group_concat(distinct tilausrivin_lisatiedot.sopimuksen_lisatieto2 separator '<br>') vasteaika,
						lasku.alatila,
						lasku.tila,
						lasku.tunnus,
						tunnusnippu,
						sopimus_loppupvm
						FROM lasku use index (tila_index)
						JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
						JOIN tilausrivin_lisatiedot on (tilausrivin_lisatiedot.yhtio = lasku.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus)
						WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
						AND tila = '0'
						AND alatila NOT IN ('D')
						$haku
						GROUP BY 1,2,3,4,5,6
						ORDER by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus and (laskun_lisatiedot.sopimus_loppupvm >= now() or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('0') and lasku.alatila != 'D'";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == 'KESKEN') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'N'
						and lasku.alatila in ('A','','T','U')
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila in ('A','','T','U')";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 7;
		}
		else {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('L','N')
						and lasku.alatila in ('A','','T','U')
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L','N') and lasku.alatila in ('A','','T','U')";
				$sumresult = pupe_query($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 7;
		}
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0) {

			if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
				if(@include('Spreadsheet/Excel/Writer.php')) {

					//keksitään failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

					$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
					$workbook->setVersion(8);
					$worksheet = $workbook->addWorksheet('Sheet 1');

					$format_bold = $workbook->addFormat();
					$format_bold->setBold();

					$excelrivi = 0;
				}
			}

			if ($toim == 'OSTO') {
				$ext_chk = '';
				$temp_row = mysql_fetch_assoc($result);

				if ($temp_row['kuka_ext'] != '' and $temp_row['kuka_ext'] == 0) {
					echo "<br/><br/><font class='head'>",t("Myyjien ostotilaukset"),"</font><br/>";
					$ext_chk = $temp_row['kuka_ext'];
				}
				elseif ($temp_row['kuka_ext'] != '' and $temp_row['kuka_ext'] == 1) {
					echo "<br/><br/><font class='head'>",t("Extranet-käyttäjien ostotilaukset"),"</font><br/>";
					$ext_chk = $temp_row['kuka_ext'];
				}

				unset($temp_row);
				mysql_data_seek($result, 0);
			}

			echo "<table>";
			echo "<tr>";

			$ii = 0;
			for ($i = 0; $i < mysql_num_fields($result)-$miinus; $i++) {
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";

				if (isset($workbook)) {

					if (mysql_field_name($result,$i) == "asiakas") {
						$worksheet->write($excelrivi, $ii, t("Ytunnus"), $format_bold);
						$ii++;
						$worksheet->write($excelrivi, $ii, t("Asiakas"), $format_bold);
						$ii++;
					}
					else {
						$worksheet->write($excelrivi, $ii, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
						$ii++;
					}
				}
			}
			$excelrivi++;

			echo "<th align='left'>".t("tyyppi")."</th><th class='back'></th></tr>";

			$lisattu_tunnusnippu  = array();
			$toimitettavat_ennakot  = array();
			$nakyman_tunnukset = array();

			while ($row = mysql_fetch_assoc($result)) {

				if ($toim == 'OSTO' and $row['kuka_ext'] != '' and $ext_chk != '' and (int) $ext_chk != (int) $row['kuka_ext']) {
					echo "</table>";
					echo "<br/><br/><font class='head'>";

					if ((int) $row['kuka_ext'] == 1) {
						echo t("Extranet-käyttäjien ostotilaukset");
					}
					else {
						echo t("Myyjien ostotilaukset");
					}

					$ext_chk = '';
					echo "</font><br/>";

					echo "<table>";

					for ($i=0; $i < mysql_num_fields($result)-$miinus; $i++) {
						echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
					}

					echo "<th align='left'>".t("tyyppi")."</th><td class='back'></td></tr>";
				}

				if ($toim == 'HYPER') {

					if ($row["tila"] == 'E' and $row["trivityyppi"] == 'E') {
						$whiletoim = 'ENNAKKO';
					}
					elseif ($row["tila"] == 'N' and $row["alatila"] == 'U') {
						$whiletoim = "JTTOIMITA";
					}
					elseif ($row["tila"] == 'N' and $row["alatila"] == 'F') {
						$whiletoim = "EXTRANET";
					}
					elseif (in_array($row["tila"], array('N','L')) and $row["alatila"] != 'X' and $row["chn"] == '999') {
						$whiletoim = "LASKUTUSKIELTO";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','B','J')) and $row["tilaustyyppi"] == 'M') {
						$whiletoim = "MYYNTITILI";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','B','C','J')) and $row["tilaustyyppi"] == 'M') {
						$whiletoim = "MYYNTITILISUPER";
					}
					elseif ($row["tila"] == 'G' and $row["alatila"] == 'V' and $row["tilaustyyppi"] == 'M') {
						$whiletoim = "MYYNTITILITOIMITA";
					}
					elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('','A'))) {
						$whiletoim = "TARJOUS";
					}
					elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('','A','X'))) {
						$whiletoim = "TARJOUSSUPER";
					}
					elseif (in_array($row["tila"], array('A','L','N')) and $row["tilaustyyppi"] == 'A' and in_array($row["alatila"], array('','A','B','C','J'))) {
						$whiletoim = "TYOMAARAYS";
					}
					elseif (in_array($row["tila"], array('A','L','N')) and $row["tilaustyyppi"] == 'A' and $row["alatila"] != 'X') {
						$whiletoim = "TYOMAARAYSSUPER";
					}
					elseif (in_array($row["tila"], array('L','N','C')) and $row["tilaustyyppi"] == 'R' and in_array($row["alatila"], array('','A','B','C','J','D'))) {
						$whiletoim = "REKLAMAATIO";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','J'))) {
						$whiletoim = "SIIRTOLISTA";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','B','C','D','J','T'))) {
						$whiletoim = "SIIRTOLISTASUPER";
					}
					elseif ($row["tila"] == 'V' and in_array($row["alatila"], array('','A','B','J'))) {
						$whiletoim = 'VALMISTUS';
					}
					elseif ($row["tila"] == 'V' and in_array($row["alatila"], array('','A','B','C','J'))) {
						$whiletoim = "VALMISTUSSUPER";
					}
					elseif (($row["tila"] == 'V' and in_array($row["alatila"], array('','A','B','J'))) or (in_array($row["tila"], array('L','N')) and in_array($row["alatila"], array('A','')))) {
						$whiletoim = "VALMISTUSMYYNTI";
					}
					elseif (in_array($row["tila"], array('L','N','V')) and !in_array($row["alatila"], array('X','V'))) {
						$whiletoim == "VALMISTUSMYYNTISUPER";
					}
					elseif ($row["tila"] == 'S' and in_array($row["alatila"], array('','A','B','J','C'))) {
						$whiletoim = "SIIRTOTYOMAARAYS";
					}
					elseif ($row["tila"] == '0' and $row["alatila"] != 'D') {
						$whiletoim = 'YLLAPITO';
					}
					elseif (in_array($row["tila"], array('L','N')) and in_array($row["alatila"], array('A',''))) {
						$whiletoim = '';
					}
					if (in_array($row["tila"], array('L','N')) and $row["alatila"] != 'X') {
						$whiletoim = 'SUPER';
					}
					elseif (in_array($row["tila"], array('R','L','N','A')) and $row["alatila"] != 'X' and $row["tilaustyyppi"] != '9') {
						$whiletoim = 'PROJEKTI';
					}
				}
				elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
					$whiletoim = "REKLAMAATIO";
				}
				else {
					$whiletoim = $toim;
				}

				$piilotarivi = "";
				$pitaako_varmistaa = "";

				// jos kyseessä on "odottaa JT tuotteita rivi"
				if ($row["tila"] == "N" and $row["alatila"] == "T") {
					$query = "SELECT tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
					$countres = pupe_query($query);

					// ja sillä ei ole yhtään riviä
					if (mysql_num_rows($countres) == 0) {
						$piilotarivi = "kylla";
					}
				}

				//	Nipuista vain se viimeisin jos niin halutaan
				if (isset($row["tunnusnippu"]) and $row["tunnusnippu"] > 0 and ($whiletoim == "PROJEKTI" or $whiletoim == "TARJOUS")) {

					//	Tunnusnipuista näytetään vaan se eka!
					// ja sillä ei ole yhtään riviä
					if (array_search($row["tunnusnippu"], $lisattu_tunnusnippu) !== false) {
						$piilotarivi = "kylla";
					}
					else {
						$lisattu_tunnusnippu[] = $row["tunnusnippu"];
					}
				}

				if ($piilotarivi == "") {

					// jos kyseessä on "odottaa JT tuotteita rivi ja kyseessa on toim=JTTOIMITA"
					if ($row["tila"] == "N" and $row["alatila"] == "U") {

						if ($yhtiorow["varaako_jt_saldoa"] != "") {
							$lisavarattu = " + tilausrivi.varattu";
						}
						else {
							$lisavarattu = "";
						}

						$query = "	SELECT tilausrivi.tuoteno, tilausrivi.jt $lisavarattu jt
									from tilausrivi
									where tilausrivi.yhtio	= '$kukarow[yhtio]'
									and tilausrivi.tyyppi	= 'L'
									and tilausrivi.otunnus	= '$row[tilaus]'";
						$countres = pupe_query($query);

						$jtok = 0;

						while ($countrow = mysql_fetch_assoc($countres)) {
							list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "JTSPEC", 0, "");

							if ($jtapu_myytavissa < $countrow["jt"]) {
								$jtok--;
							}
						}
					}

					$label_color = "";

					if (isset($row['label']) and $row['label'] != '') {
						$label_query = "	SELECT selite
											FROM avainsana
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus = {$row['label']}
											AND laji = 'label'";
						$label_result = pupe_query($label_query);

						if (mysql_num_rows($label_result) == 1) {
							$label_row = mysql_fetch_assoc($label_result);
							$label_color = "style = 'background-color: {$label_row['selite']};'";
						}
					}

					echo "<tr class='aktiivi' {$label_color}>";

					$zendesk_viesti = FALSE;
					$ii = 0;

					for ($i = 0; $i < mysql_num_fields($result)-$miinus; $i++) {

						$fieldname = mysql_field_name($result,$i);

						if ($whiletoim == "YLLAPITO" and $row["sopimus_loppupvm"] < date("Y-m-d") and $row["sopimus_loppupvm"] != '0000-00-00') {
							$class = 'tumma';
						}
						else {
							$class = '';
						}

						if ($fieldname == 'luontiaika' or $fieldname == 'toimaika') {
							echo "<td class='$class' valign='top' align='right'>".tv1dateconv($row[$fieldname], "PITKA", "LYHYT")."</td>";
						}
						elseif ($fieldname == 'sopimuspvm') {

							list($sopalk, $soplop) = explode("###", $row[$fieldname]);

							if ($soplop == "0000-00-00") {
								$soplop = t("Toistaiseksi");
							}
							else {
								$soplop = tv1dateconv($soplop);
							}

							echo "<td class='$class' valign='top' align='right'>".tv1dateconv($sopalk)." - $soplop</td>";
						}
						elseif ($fieldname == 'Pvm') {
							list($aa, $bb) = explode('<br>', $row[$fieldname]);

							echo "<td class='$class' valign='top'>".tv1dateconv($aa, "PITKA", "LYHYT")."<br>".tv1dateconv($bb, "PITKA", "LYHYT")."</td>";
						}
						elseif ($fieldname == "tilaus") {

							$query_comments = "	SELECT group_concat(concat_ws('<br>', comments, sisviesti2) SEPARATOR '<br><br>') comments
												FROM lasku use index (primary)
												WHERE yhtio = '$kukarow[yhtio]'
												AND tunnus in (".$row[$fieldname].")
												AND (comments != '' OR sisviesti2 != '')";
							$result_comments = pupe_query($query_comments);
							$row_comments = mysql_fetch_assoc($result_comments);

							if (trim($row_comments["comments"]) != "") {
								echo "<td class='$class' align='right' valign='top'>";
								echo "<div id='div_kommentti".$row[$fieldname]."' class='popup' style='width: 500px;'>";
								echo $row_comments["comments"];
								echo "</div>";
								echo "<a class='tooltip' id='kommentti".$row[$fieldname]."'>".str_replace(",", "<br>*", $row[$fieldname])."</a>";
							}
							else {
								echo "<td class='$class' align='right' valign='top'>".str_replace(",", "<br>*", $row[$fieldname]);
							}

							if ($kukarow["yhtio"] == "savt") {
								$query_comments = "	SELECT viesti
													FROM lasku use index (primary)
													WHERE yhtio = '$kukarow[yhtio]'
													AND tunnus in ({$row[$fieldname]})
													AND viesti != ''
													LIMIT 1";
								$result_comments = pupe_query($query_comments);
								$row_comments = mysql_fetch_assoc($result_comments);

								$row_comments["viesti"] = preg_replace("/[^0-9]/", "", $row_comments["viesti"]);

								if ($row_comments["viesti"] != "") {
									echo "<br><a target='_blank' href='https://devlab.zendesk.com/tickets/{$row_comments["viesti"]}'>{$row_comments["viesti"]}</a>";
									$zendesk_viesti = TRUE;
								}
							}
							echo "</td>";
						}
						elseif ($fieldname == "asiakas" and $kukarow["yhtio"] == "savt" and $zendesk_auth != "" and $zendesk_viesti) {

							echo "<td class='$class' valign='top'>".$row[$fieldname];

							list($ticket, $statukset, $priot) = zendesk_curl("https://devlab.zendesk.com/tickets/{$row_comments["viesti"]}.xml");

							if ($xml = simplexml_load_string($ticket)) {

								list($requester, $null, $null) = zendesk_curl("https://devlab.zendesk.com/users/".$xml->{"requester-id"}.".xml");
								list($assignee, $null, $null) = zendesk_curl("https://devlab.zendesk.com/users/".$xml->{"assignee-id"}.".xml");

								$requester = simplexml_load_string($requester);
								$assignee = simplexml_load_string($assignee);

								echo "<br><br><table><tr><th>Requester</th><td>".utf8_decode($requester->{"name"})."</td></tr>";
								echo "<tr><th>Subject</th><td>".utf8_decode($xml->{"subject"})."</td></tr>";
								echo "<tr><th>Status</th><td>".$statukset[(int) $xml->{"status-id"}]."</td></tr>";
								echo "<tr><th>Assignee</th><td>".utf8_decode($assignee->{"name"})."</td></tr></table>";
							}

							echo "</td>";
						}
						elseif ($fieldname == "seuranta") {

							$img = "mini-comment.png";
							$linkkilisa = "";
							$query_comments = "	SELECT group_concat(tunnus) tunnukset
												FROM lasku
												WHERE yhtio = '$kukarow[yhtio]'
												AND lasku.tila != 'S'
												AND tunnusnippu = '$row[tunnusnippu]' and tunnusnippu>0";
							$ares = pupe_query($query_comments);

							if (mysql_num_rows($ares) > 0) {
								$arow = mysql_fetch_assoc($ares);

								if ($arow["tunnukset"] != "") {
									//	Olisiko meillä kalenterissa kommentteja?
									$query_comments = "	SELECT tunnus
														FROM kalenteri
														WHERE yhtio = '$kukarow[yhtio]'
														AND tyyppi = 'Memo'
														AND otunnus IN ($arow[tunnukset])";
									$result_comments = pupe_query($query_comments);

									$nums="";
									if (mysql_num_rows($result_comments) > 0) {
										$img = "info.png";
										$linkkilisa = "onmouseover=\"popUp(event, 'asiakasmemo_".$row[$fieldname]."', '0', '0', '{$palvelin2}crm/asiakasmemo.php?tee=NAYTAMUISTIOT&liitostunnus=$row[liitostunnus]&tunnusnippu=$row[tunnusnippu]', false, true); return false;\" onmouseout=\"popUp(event, 'asiakasmemo_".$row[$fieldname]."'); return false;\"";
									}
								}
							}

							echo "<td class='$class' valign='top' NOWRAP>".$row[$fieldname]." <div style='float: right;'><img src='pics/lullacons/$img' class='info' $linkkilisa onclick=\"window.open('{$palvelin2}crm/asiakasmemo.php?tee=NAYTA&liitostunnus=$row[liitostunnus]&tunnusnippu=$row[tunnusnippu]&from=muokkaatilaus.php');\"> $nums</div></td>";
						}
						elseif (is_numeric($row[$fieldname])) {
							echo "<td class='$class' align='right' valign='top'>".$row[$fieldname]."</td>";
						}
						else {
							echo "<td class='$class' valign='top'>".$row[$fieldname]."</td>";
						}

						if (isset($workbook)) {

							if ($fieldname == "asiakas") {
								$nimiosat = explode("<br>", $row[$fieldname]);

								$ytunnari = trim(array_shift($nimiosat));
								$lopnimit = trim(implode("\n", $nimiosat));

								$worksheet->writeString($excelrivi, $ii, $ytunnari);
								$ii++;
								$worksheet->writeString($excelrivi, $ii, $lopnimit);
								$ii++;

							}
							elseif (mysql_field_type($result,$i) == 'real') {
								$worksheet->writeNumber($excelrivi, $ii, sprintf("%.02f", $row[$fieldname]));
								$ii++;
							}
							else {
								$worksheet->writeString($excelrivi, $ii, $row[$fieldname]);
								$ii++;
							}
						}
					}

					if ($row["tila"] == "N" and $row["alatila"] == "U") {
						if ($jtok == 0) {
							echo "<td class='$class' valign='top'><font style='color:#00FF00;'>".t("Voidaan toimittaa")."</font></td>";

							if (isset($workbook)) {
								$worksheet->writeString($excelrivi, $ii, "Voidaan toimittaa");
								$ii++;
							}
						}
						else {
							echo "<td class='$class' valign='top'><font style='color:#FF0000;'>".t("Ei voida toimittaa")."</font></td>";

							if (isset($workbook)) {
								$worksheet->writeString($excelrivi, $ii, t("Ei voida toimittaa"));
								$ii++;
							}
						}
					}
					else {

						$laskutyyppi = $row["tila"];
						$alatila	 = $row["alatila"];

						//tehdään selväkielinen tila/alatila
						require "inc/laskutyyppi.inc";

						$tarkenne = " ";

						if ($row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
							$tarkenne = " (".t("Asiakkaalle").") ";
						}
						elseif ($row["tila"] == "V" and  $row["tilaustyyppi"] == "W") {
							$tarkenne = " (".t("Varastoon").") ";
						}
						elseif (($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "R") {
							$tarkenne = " (".t("Reklamaatio").") ";
						}
						elseif (($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "A") {
							$laskutyyppi = "Työmääräys";
						}
						elseif ($row["tila"] == "N" and $row["tilaustyyppi"] == "E") {
							$laskutyyppi = "Ennakkotilaus kesken";
						}
						elseif ($row["tila"] == "G" and $row["tilaustyyppi"] == "M") {
							$laskutyyppi = "Myyntitili";
						}

						if ($row["tila"] == "G" and $row["tilaustyyppi"] == "M" and $row["alatila"] == "V") {
							$alatila = "Toimitettu asiakkaalle";
						}

						$varastotila = "";

						if (isset($row["varastokpl"]) and $row["varastokpl"] > 0) {
							$varastotila .= "<font class='info'><br>".t("Viety osittain varastoon")."</font>";
						}

						if (isset($row["vahvistettukpl"]) and $row["vahvistettukpl"] > 0) {
							$varastotila .= "<font class='info'><br>".t("Toimitusajat vahvistettu")."</font>";
						}

						echo "<td class='$class' valign='top'>".t("$laskutyyppi")."$tarkenne".t("$alatila")." $varastotila</td>";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $ii, t("$laskutyyppi")."$tarkenne".t("$alatila")." $varastotila");
							$ii++;
						}
					}

					$excelrivi++;

					// tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
					if ($whiletoim == "" or $whiletoim == "SUPER" or $whiletoim == "KESKEN" or $whiletoim == "EXTRANET" or $whiletoim == "ENNAKKO" or $whiletoim == "JTTOIMITA" or $whiletoim == "LASKUTUSKIELTO"or (($whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {
						$aputoim1 = "RIVISYOTTO";
						$aputoim2 = "PIKATILAUS";

						$lisa1 = t("Rivisyöttöön");
						$lisa2 = t("Pikatilaukseen");
					}
					elseif (($whiletoim == "VALMISTUS" or $whiletoim == "VALMISTUSSUPER" or $whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
						$aputoim1 = "VALMISTAASIAKKAALLE";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif (($whiletoim == "VALMISTUS" or $whiletoim == "VALMISTUSSUPER" or $whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V" and $row["tilaustyyppi"] != "V") {
						$aputoim1 = "VALMISTAVARASTOON";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "MYYNTITILISUPER" or $whiletoim == "MYYNTITILITOIMITA") {
						$aputoim1 = "MYYNTITILI";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "SIIRTOLISTASUPER") {
						$aputoim1 = "SIIRTOLISTA";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "TARJOUSSUPER") {
						$aputoim1 = "TARJOUS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "TYOMAARAYSSUPER") {
						$aputoim1 = "TYOMAARAYS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "OSTO" or $whiletoim == "OSTOSUPER") {
						$aputoim1 = "";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "PROJEKTI") {
						if ($row["tila"] == "A") {
							$aputoim1 = "TYOMAARAYS";
						}
						elseif($row["tila"] == "R") {
							$aputoim1 = "PROJEKTI";
						}
						else {
							$aputoim1 = "RIVISYOTTO";
						}

						$lisa1 = t("Rivisyöttöön");
					}
					elseif ($whiletoim == "REKLAMAATIO" or $whiletoim == "REKLAMAATIOSUPER") {
						$aputoim1 = "REKLAMAATIO";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
						$aputoim1 = $whiletoim;
						$lisa1 = t("Vastaanota");

						$aputoim2 = "";
						$lisa2 = "";
					}
					else {
						$aputoim1 = $whiletoim;
						$aputoim2 = "";

						$lisa1 = t("Muokkaa");
						$lisa2 = "";
					}

					// tehdään alertteja
					if ($row["tila"] == "L" and $row["alatila"] == "A") {
						$pitaako_varmistaa = t("Keräyslista on jo tulostettu! Oletko varma, että haluat vielä muokata tilausta?");
					}

					if ($row["tila"] == "G" and $row["alatila"] == "A") {
						$pitaako_varmistaa = t("Siirtolista on jo tulostettu! Oletko varma, että haluat vielä muokata siirtolistaa?");
					}

					$button_disabled = "";

					if (($row["tila"] == "L" or $row["tila"] == "N") and isset($row["mapvm"]) and $row["mapvm"] != '0000-00-00' and $row["mapvm"] != '') {
						$button_disabled = "disabled";
					}

					// tehdään alertti jos sellanen ollaan määritelty
					$javalisa = "";

					if ($pitaako_varmistaa != "") {
						$javalisa = "onSubmit = \"return lahetys_verify('$pitaako_varmistaa')\"";
					}

					echo "<td class='back' nowrap>";

					if ($whiletoim == "OSTO" or $whiletoim == "OSTOSUPER" or $whiletoim == "HAAMU") {
						echo "<form method='post' action='tilauskasittely/tilaus_osto.php' $javalisa>";
					}
					else {
						echo "<form method='post' action='tilauskasittely/tilaus_myynti.php' $javalisa>";
					}

					//	Projektilla hypätään aina pääotsikolle..
					if ($whiletoim == "PROJEKTI") {
						echo "	<input type='hidden' name='projektilla' value='$row[tunnusnippu]'>";
					}

					echo "	<input type='hidden' name='lopetus' 	 value='{$palvelin2}muokkaatilaus.php////toim=$toim//asiakastiedot=$asiakastiedot//limit=$limit//etsi=$etsi'>
							<input type='hidden' name='mista'		 value='muokkaatilaus'>
							<input type='hidden' name='toim'		 value='$aputoim1'>
							<input type='hidden' name='tee'			 value='AKTIVOI'>
							<input type='hidden' name='orig_tila'	 value='{$row["tila"]}'>
							<input type='hidden' name='orig_alatila' value='{$row["alatila"]}'>
							<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";

					if ($toim == "VASTAANOTA_REKLAMAATIO") {
						echo "	<input type='hidden' name='mista' value='vastaanota'>";
					}

					if ($whiletoim == "" or $whiletoim == "SUPER" or $whiletoim == "KESKEN" or $whiletoim == "EXTRANET" or $whiletoim == "ENNAKKO" or $whiletoim == "JTTOIMITA" or $whiletoim == "LASKUTUSKIELTO" or (($whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {
						echo "<input type='submit' name='$aputoim2' value='$lisa2' $button_disabled>";
					}

					echo "<input type='submit' name='$aputoim1' value='$lisa1' $button_disabled>";
					echo "</form></td>";

					if (($whiletoim == "TARJOUS" or $whiletoim == "TARJOUSSUPER") and $deletarjous and $kukarow["mitatoi_tilauksia"] == "") {
						echo "<td class='back'><form method='post' action='muokkaatilaus.php' onSubmit='return tarkista_mitatointi(1);'>";
						echo "<input type='hidden' name='toim' value='$whiletoim'>";
						echo "<input type='hidden' name='tee' value='MITATOI_TARJOUS'>";
						echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
						echo "<input type='submit' name='$aputoim1' value='".t("Mitätöi")."'>";
						echo "</form></td>";
					}

					//laitetaan tunnukset talteen mitatoi_tarjous_kaikki toiminnallisuutta varten
					$nakyman_tunnukset[] = $row['tunnus'];

					if ($whiletoim == "ENNAKKO" and $yhtiorow["ennakkotilausten_toimitus"] == "M") {

						$toimitettavat_ennakot[] = $row["tunnus"];

						echo "<td class='back'><form method='post' action='muokkaatilaus.php' onSubmit='return verify();'>";
						echo "<input type='hidden' name='toim' value='$whiletoim'>";
						echo "<input type='hidden' name='tee' value='TOIMITA_ENNAKKO'>";
						echo "<input type='hidden' name='toimita_ennakko' value='$row[tunnus]'>";
						echo "<input type='submit' name='$aputoim1' value='".t("Toimita ennakkotilaus")."'>";
						echo "</form></td>";
					}

					echo "</tr>";
				}
			}

			echo "</table>";

			if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
				if (is_array($sumrow)) {
					echo "<br><table>";
					echo "<tr><th>".t("Arvo yhteensä")." ($sumrow[kpl] ".t("kpl")."): </th><td align='right'>$sumrow[arvo] $yhtiorow[valkoodi]</td></tr>";

					if (isset($sumrow["jt_arvo"]) and $sumrow["jt_arvo"] != 0) {
						echo "<tr><th>".t("Muu tilauskanta").":</th><td align='right'>$sumrow[jt_arvo] $yhtiorow[valkoodi]</td></tr>";


						echo "<tr><th>".t("Yhteensä")."</th><td align='right'>".sprintf('%.2f', $sumrow["jt_arvo"]+$sumrow["arvo"])." $yhtiorow[valkoodi]</td></tr>";

						echo "<tr><td class='back'><br></td></tr>";
					}

					echo "<tr><th>".t("Summa yhteensä").": </th><td align='right'>$sumrow[summa] $yhtiorow[valkoodi]</td></tr>";

					if (isset($sumrow["jt_summa"]) and $sumrow["jt_summa"] != 0) {
						echo "<tr><th>".t("Muu tilauskanta").":</th><td align='right'>$sumrow[jt_summa] $yhtiorow[valkoodi]</td></tr>";

						echo "<tr><th>".t("Yhteensä")."</th><td align='right'>".sprintf('%.2f', $sumrow["jt_summa"]+$sumrow["summa"])." $yhtiorow[valkoodi]</td></tr>";
					}

					echo "</table>";
				}

				if (mysql_num_rows($result) == 50) {
					// Näytetään muuten vaan sopivia tilauksia
					echo "<br>
							<form method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='etsi' value='$etsi'>
							<input type='hidden' name='asiakastiedot' value='$asiakastiedot'>
							<input type='hidden' name='limit' value='NO'>
							<table>
							<tr><th>".t("Listauksessa näkyy 50 ensimmäistä")." $otsikko.</th>
							<td class='back'><input type='Submit' value = '".t("Näytä kaikki")."'></td></tr>
							</table>
							</form>";
				}

				if (isset($workbook)) {

					// We need to explicitly close the workbook
					$workbook->close();

					echo "<form method='post' class='multisubmit'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='Tilauslista.xls'>";
					echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
					echo "<br><table>";
					echo "<tr><th>".t("Tallenna lista").":</th>";
					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
					echo "</table></form><br>";
				}

				if ($toim == 'TARJOUS' and tarkista_oikeus('tilaus_myynti.php', 'TARJOUS', 1)) {
					$tunnukset = implode(',', $nakyman_tunnukset);

					echo "<form method='POST' name='mitatoi_kaikki_formi' action='muokkaatilaus.php' onSubmit='return tarkista_mitatointi(".count($nakyman_tunnukset).");'>";
					echo "<input type='hidden' name='toim' value='$toim' />";
					echo "<input type='hidden' name='tee' value='MITATOI_TARJOUS_KAIKKI' />";
					echo "<input type='hidden' name='tunnukset' value='($tunnukset)' />";
					echo "<input type='submit' value='".t("Mitätöi kaikki näkymän tarjoukset")."'/>";
					echo "</form>";
				}

				if ($whiletoim == "ENNAKKO" and $yhtiorow["ennakkotilausten_toimitus"] == "M" and count($toimitettavat_ennakot) > 0) {
					echo "<br><form method='post' action='muokkaatilaus.php' onSubmit='return verify();'>";
					echo "<input type='hidden' name='toim' value='$whiletoim'>";
					echo "<input type='hidden' name='tee' value='TOIMITA_ENNAKKO'>";
					echo "<input type='hidden' name='toimita_ennakko' value='".implode(",", $toimitettavat_ennakot)."'>";
					echo "<table><tr><th>".t("Toimita")."</th>";
					echo "<td><input type='submit' name='$aputoim1' value='".t("Toimita kaikki yllälistatut ennakkotilaukset")."'></td>";
					echo "</table></form>";
				}
			}
		}
		else {
			echo t("Ei tilauksia")."...<br>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
			require ("inc/footer.inc");
		}
	}
?>
