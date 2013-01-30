<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	if ($tee == "") {
		echo "<font class='head'>".t("Maksuaineistot")."</font><hr>";

		$query = "	SELECT lasku.tunnus
					FROM lasku, valuu, yriti
					WHERE lasku.yhtio 		= '$kukarow[yhtio]'
					and valuu.yhtio 		= lasku.yhtio
					and valuu.yhtio 		= yriti.yhtio
					and lasku.maksu_tili 	= yriti.tunnus
					and yriti.kaytossa		= ''
					and lasku.tila	 		= 'P'
					and lasku.valkoodi 		= valuu.nimi
					and lasku.maksaja 		= '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<br>";
		echo "<font class='message'>".t("Sinulla on")." ".mysql_num_rows($result)." ".t("laskua poimittuna").".</font>";
		echo "<br><br>";

		if (mysql_num_rows($result) > 0) {
			echo "<form name = 'valinta' method='post'>";
			echo "<input type = 'hidden' name = 'tee' value = 'KIRJOITA'>";
			echo "<input type = 'submit' value = '".t("Tee maksuaineistot")."'>";
			echo "</form>";
		}

	}

	// Onko maksuaineistoille annettu salasanat.php:ss‰ oma polku jonne tallennetaan
	if (isset($pankkitiedostot_polku) and trim($pankkitiedostot_polku) != "") {
		$pankkitiedostot_polku = trim($pankkitiedostot_polku);
		if (substr($pankkitiedostot_polku, -1) != "/") {
			$pankkitiedostot_polku .= "/";
		}
	}
	else {
		$pankkitiedostot_polku = $pupe_root_polku."/dataout/";
	}

	if (!is_dir($pankkitiedostot_polku) or !is_writable($pankkitiedostot_polku)) {
		echo "<br><br>".t("Kansioissa ongelmia").": $pankkitiedostot_polku<br>";
		exit;
	}

	if ($tee == "lataa_tiedosto") {
		readfile($pankkitiedostot_polku.basename($pankkifilenimi));
		exit;
	}

	if ($tee == "KIRJOITA") {

		$popvm_nyt = date("Y-m-d H:i:s");

		if (strtoupper($yhtiorow['maa']) == 'FI') {
			$kotimaa = "FI";
		}
		elseif (strtoupper($yhtiorow['maa']) == 'SE') {
			$kotimaa = "SE";
		}

		if (strtoupper($yhtiorow['maa']) != 'FI' and strtoupper($yhtiorow['maa']) != 'SE') {
			echo "<font class='error'>".t("Yrityksen maa ei ole sallittu")." (FI, SE) '$yhtiorow[maa]'</font><br>";
			exit;
		}

		if ($kotimaa == "FI") {
			echo "<font class='head'>LM03-maksuaineisto</font><hr>";
		}
		else {
			echo "<font class='head'>Betalningsuppdrag via Bankgirot - Inrikesbetalningar</font><hr>";
		}

		if ($kotimaa == "FI") {

			// Tarkistetaan yrityksen pankkitilien oikeellisuudet
			$query = "	SELECT tilino, nimi, tunnus, asiakastunnus
					  	FROM yriti
					  	WHERE yhtio = '$kukarow[yhtio]'
					  	and tilino != ''
						and yriti.kaytossa = ''";
			$result = mysql_query($query) or pupe_error($query);

			//Haetaan funktio joka tuo pankin tietoja
			require_once ("inc/pankkitiedot.inc");
			$pankkitiedot = array();

			while ($row = mysql_fetch_array($result)) {
				$pankkitili = $row["tilino"];

				if (is_numeric(substr($row["tilino"], 0, 1))) {
					require("inc/pankkitilinoikeellisuus.php");
				}

				if ($pankkitili == "") {
					echo "<font class='error'>Pankkitili $row[nimi], '$row[tilino]' on virheellinen</font><br>";
					exit;
				}
				elseif ($row["tilino"] != $pankkitili) {
					$query = "UPDATE yriti SET tilino = '$pankkitili' WHERE tunnus = $row[tunnus]";
					$xresult = mysql_query($query) or pupe_error($query);
					echo "P‰ivitin tilin $row[nimi]<br><br>";
				}

				//Haetaan tilinumeron perusteella pankin tiedot
				$pankkitiedot[$pankkitili] = pankkitiedot($pankkitili, $row["asiakastunnus"]);
			}
		}

		// --- LM03/Eli kotimaan maksujen aineisto
		// Tutkitaan onko kotimaan aineistossa monta maksup‰iv‰‰?
		$query = "	SELECT distinct(olmapvm)
					FROM lasku
					WHERE yhtio	= '$kukarow[yhtio]'
					and tila	= 'P'
					and maa		= '$kotimaa'
					and maksaja	= '$kukarow[kuka]'
					ORDER BY 1";
		$pvmresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($pvmresult) == 0) {
			echo "<font class='message'>".t("Sopivia laskuja ei lˆydy")."</font>";
		}
		else {
			echo "<table>";
		}

		$generaatio = 0;
		$totkpl 	= 0;
		$totsumma	= 0;

		while ($pvmrow = mysql_fetch_array($pvmresult)) {

			$makskpl 	 = 0;
			$makssumma 	 = 0;
			$tilinoarray = array();

			//Tutkitaan onko kotimaan aineistossa hyvityslaskuja
			$query = "	SELECT maksu_tili, tilinumero, nimi
						FROM lasku
						WHERE yhtio  = '$kukarow[yhtio]'
						and tila 	 = 'P'
						and maa		 = '$kotimaa'
						and summa 	 < 0
						and maksaja  = '$kukarow[kuka]'
						and olmapvm  = '$pvmrow[olmapvm]'
						GROUP BY maksu_tili, tilinumero";
			$result = mysql_query($query) or pupe_error($query);

			//Lˆytyykˆ hyvityksi‰?
			while ($laskurow = mysql_fetch_array ($result)) {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tila 		= 'P'
							and maa		 	= '$kotimaa'
							and maksaja 	= '$kukarow[kuka]'
							and tilinumero	= '$laskurow[tilinumero]'
							and maksu_tili	= '$laskurow[maksu_tili]'
							and olmapvm 	= '$pvmrow[olmapvm]'";
				$xresult = mysql_query($query) or pupe_error($query);

				$hyvityssumma = 0;

				// Tarkistetaan meneekˆ tilitys plussalle??
				while ($hyvitysrow = mysql_fetch_array($xresult)) {
					// maksetaan k‰teisalennuksella
					if ($hyvitysrow['alatila'] == 'K') {
						$hyvityssumma += $hyvitysrow['summa'] - $hyvitysrow['kasumma'];
					}
					else {
						$hyvityssumma += $hyvitysrow['summa'];
					}
				}

				$hyvityssumma = round($hyvityssumma, 2);
				$summaarray[$laskurow['maksu_tili']][$laskurow['tilinumero']] = $hyvityssumma;
				$tilinoarray[$laskurow['maksu_tili']][$laskurow['tilinumero']] = $laskurow['tilinumero'];

				if ($hyvityssumma < 0.01) {
					echo "<tr><th>".t("Virhe hyvityslaskuissa")."</th><td><font class='error'>$laskurow[nimi] ($laskurow[tilinumero]) ".t("tililt‰")." $laskurow[maksu_tili] ".t("hyvitykset suuremmat kuin veloitukset. Koko aineisto hyl‰t‰‰n")."!</font></td></tr>";
					echo "</table>";
					require ("inc/footer.inc");
					exit;
				}
			}

			// --- LM03 AINEISTO MAKSUTILIT
			$query = "	SELECT yriti.tunnus, yriti.tilino, yriti.nimi nimi
						FROM lasku, yriti
						WHERE lasku.yhtio 	= '$kukarow[yhtio]'
						and tila 			= 'P'
						and maa		 		= '$kotimaa'
						and yriti.tunnus 	= maksu_tili
						and yriti.yhtio 	= lasku.yhtio
						and yriti.kaytossa  = ''
						and maksaja 		= '$kukarow[kuka]'
						and olmapvm 		= '$pvmrow[olmapvm]'
						GROUP BY yriti.tilino";
			$yritiresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yritiresult) != 0) {

				while ($yritirow = mysql_fetch_array ($yritiresult)) {

					$yritystilino 	= $yritirow['tilino'];
					$yrityytunnus 	= $yhtiorow['ytunnus'];
					$maksupvm 		= $pvmrow['olmapvm'];
					$yritysnimi 	= $yhtiorow['nimi'];

					if (!is_resource($toot)) {

						$generaatio++;

						if ($kotimaa == "FI") {
							$kaunisnimi = "lm03-$kukarow[yhtio]-".date("d.m.y.H.i.s")."-".$generaatio.".txt";
						}
						else {
							$kaunisnimi = "bg-$kukarow[yhtio]-".date("d.m.y.H.i.s")."-".$generaatio.".txt";
						}

						$toot = fopen($pankkitiedostot_polku.$kaunisnimi, "w+");

						if (!$toot) {
							echo t("En saanut tiedostoa auki! Tarkista polku")." $pankkitiedostot_polku$kaunisnimi !";
							exit;
						}

						echo "<tr><td class='back'><br></td></tr>";
						echo "<tr><th>".t("Tiedosto")."</th><td>$kaunisnimi</td></tr>";
					}

					if ($kotimaa == "FI") {

						// haetaan t‰m‰n tilin tiedot
						if (isset($pankkitiedot[$yritystilino])) {
							foreach($pankkitiedot[$yritystilino] as $key => $value) {
								${$key} = $value;
							}
						}
						else {
							die(t("Kadotin t‰m‰n pankin maksuaineistotiedot!"));
						}

						require("inc/lm03otsik.inc");

						// k‰sitell‰‰n hyvityksien netotus
						require("inc/lm03hyvitykset.inc");
					}
					else {
						require("inc/bginotsik.inc");
					}

					// Yrit‰mme nyt v‰litt‰‰ maksupointterin $laskusis1:ss‰ --> $laskurow[9] --> lasku.tunnus
					$query = "	SELECT maksu_tili,
								lasku.nimi, lasku.nimitark, lasku.pankki_haltija,
								left(concat_ws(' ', osoite, osoitetark),20) osoite,
								left(concat_ws(' ', postino, postitp),20) postitp,
								summa, lasku.valkoodi, viite, viesti,
								tilinumero, lasku.tunnus, sisviesti2,
								yriti.tilino ytilino, alatila, kasumma, laskunro
								FROM lasku, yriti
								WHERE lasku.yhtio 	= '$kukarow[yhtio]'
								and tila 			= 'P'
								and maa		 		= '$kotimaa'
								and yriti.tunnus 	= maksu_tili
								and yriti.yhtio 	= lasku.yhtio
								and yriti.kaytossa  = ''
								and maksaja 		= '$kukarow[kuka]'
								and maksu_tili 		= $yritirow[tunnus]
								and olmapvm 		= '$pvmrow[olmapvm]'
								ORDER BY tilinumero, summa desc";
					$result = mysql_query($query) or pupe_error($query);

					while ($laskurow = mysql_fetch_array ($result)) {

						$laskutapahtuma	= '10';
						$yritystilino 	= $laskurow["ytilino"];

						// jos pankkihaltijan nimi on syˆtetty, laitetaan se nimen tilalle
						if (trim($laskurow['pankki_haltija']) != '') {
							$laskunimi1 = trim($laskurow["pankki_haltija"]);
						}
						else {
							$laskunimi1 = $laskurow["nimi"];
							if ($laskurow["nimitark"] != "") {
								$laskunimi1 = $laskurow["nimi"]." ".$laskurow['nimitark'];
							}
						}

						$laskunimi2 	= $laskurow["osoite"];
						$laskunimi3 	= $laskurow["postitp"];

						if ($laskurow["alatila"] == 'K') { // maksetaan k‰teisalennuksella
							$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
						}
						else {
							$laskusumma = $laskurow["summa"];
						}

						$laskutilno 	= $laskurow["tilinumero"];
						$laskusis1  	= $laskurow["tunnus"];
						$laskusis2  	= $laskurow["sisviesti2"];
				  	 	$laskutyyppi	= 5;
						$laskuviesti	= $laskurow['viesti'];

						if ($laskurow['laskunro'] != 0 and $laskurow['laskunro'] != $laskurow['viesti']) {
			                $laskuviesti = (trim($laskuviesti) == "") ? $laskurow['laskunro'] : $laskuviesti." ".$laskurow['laskunro'];
			            }

						//Sampo/Danske haluaa viestiin ainakin yhden merkin
						if (substr($laskurow['ytilino'], 0, 1) == '8' and strlen(trim($laskuviesti)) == 0) $laskuviesti = ';';

						if (strlen($laskurow["viite"]) > 0) {
							$laskuviesti = sprintf ('%020s', $laskurow["viite"]); //Etunollat‰yttˆ
				  	 		$laskutyyppi = 1;
				  	 	}

						if ($kotimaa == "FI") {
							require("inc/lm03rivi.inc");
						}
						else {
							require("inc/bginrivi.inc");
						}

						$makskpl += 1;
						$makssumma += $laskusumma;

						$totkpl += 1;
						$totsumma += $laskusumma;
					}

					if ($kotimaa == "FI") {
						require("inc/lm03summa.inc");
					}
					else{
						require("inc/bginsumma.inc");
					}

					echo "<tr><td class='back'><br></td></tr>";
					echo "<tr><th>".t("Maksup‰iv‰")."</th><td>".tv1dateconv($pvmrow["olmapvm"])."</td></tr>";
					echo "<tr><th>".t("Maksutili")."</th><td>$yritirow[nimi] $yritirow[tilino]</td></tr>";
					echo "<tr><th>".t("Summa")."</th><td>".sprintf('%.2f', $makssumma)."</td></tr>";
					echo "<tr><th>".t("Tapahtumia")."</td><td>$makskpl ".t("kpl")."</td></tr>";

					// Jokainen pankki ja p‰iv‰ omaan tiedostoon
					if ($yhtiorow['pankkitiedostot'] == 'E') {
						if (is_resource($toot)) {
							fclose($toot);

							if ($tiedostonimi == "") {
								$tiedostonimi = $kaunisnimi;
							}

							echo "<tr><th>".t("Tallenna aineisto")."</th>";
							echo "<form method='post' class='multisubmit'>";
							echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
							echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
							echo "<input type='hidden' name='pankkifilenimi' value='$kaunisnimi'>";
							echo "<td><input type='submit' value='".t("Tallenna")."'></form></td>";
							echo "</tr>";
						}
					}

					$query = "	UPDATE lasku SET
								tila = 'Q',
								popvm = '$popvm_nyt'
					          	WHERE yhtio 	= '$kukarow[yhtio]'
					          	and tila 		= 'P'
								and maa		 	= '$kotimaa'
					          	and maksaja 	= '$kukarow[kuka]'
					          	and maksu_tili	= '$yritirow[tunnus]'
					          	and olmapvm 	= '$pvmrow[olmapvm]'
					          	ORDER BY yhtio, tila";
					$result = mysql_query($query) or pupe_error($query);

					$makskpl 	= 0;
					$makssumma 	= 0;
				}

				$makssumma = 0;
			}
			else {
				echo "<font class='message'>".t("Sopivia laskuja ei lˆydy")."!</font>";
			}
		}

		if ($totkpl > 0) {
			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><th>".t("Aineiston kokonaissumma")."</th><td>".sprintf('%.2f',$totsumma)."</td></tr>";
			echo "<tr><th>".t("Tapahtumia")."</th><td>$totkpl ".t("kpl")."</td></tr>";
		}

		// Pankit ja p‰iv‰t yhdistet‰‰n
		if ($yhtiorow['pankkitiedostot'] == '' or $yhtiorow['pankkitiedostot'] == 'F') {
			if (is_resource($toot)) {
				fclose($toot);

				if ($tiedostonimi == "") {
					$tiedostonimi = $kaunisnimi;
				}

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th>".t("Tallenna aineisto")."</th>";
				echo "<form method='post' class='multisubmit'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
				echo "<input type='hidden' name='pankkifilenimi' value='$kaunisnimi'>";
				echo "<td><input type='submit' value='".t("Tallenna")."'></form></td>";
				echo "</tr>";
			}
		}

		if (mysql_num_rows($pvmresult) != 0) {
			echo "</table>";
		}

		//----------- LUM2 AINEISTO --------------------------

		if ($kotimaa == "FI") {
			echo "<br><br><br><font class='head'>LUM2-maksuaineisto</font><hr>";
		}
		else {
			echo "<br><br><br><font class='head'>Betalningsuppdrag via Bankgirot - Utlandsbetalningar</font><hr>";
		}

		$makskpl 	= 0;
		$makssumma 	= 0;
		$maksulk 	= 0;
		$totkpl 	= 0;
		$totsumma 	= 0;
		$generaatio = 1;

		//Etsit‰‰n aineistot
		$query = "	SELECT maksu_tili, lasku.valkoodi, yriti.tilino ytilino, yriti.nimi tilinimi
					FROM lasku, yriti
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and tila = 'P'
					and maa <> '$kotimaa'
					and maksaja = '$kukarow[kuka]'
					and yriti.tunnus = maksu_tili
					and yriti.yhtio = lasku.yhtio
					and yriti.kaytossa = ''
					GROUP BY maksu_tili, lasku.valkoodi";
		$pvmresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($pvmresult) != 0) {

			echo "<table>";

			while ($pvmrow = mysql_fetch_array($pvmresult)) {

				if (!is_resource($toot)) {
					if ($kotimaa == "FI") {
						$kaunisnimi = "lum2-$kukarow[yhtio]-".date("d.m.y.H.i.s")."-".$generaatio.".txt";
					}
					else {
						$kaunisnimi = "bgut-$kukarow[yhtio]-".date("d.m.y.H.i.s")."-".$generaatio.".txt";
					}

					$generaatio++;
					$toot = fopen($pankkitiedostot_polku.$kaunisnimi,"w+");
				}

				unset($edmaksu_tili);

				if (!$toot) {
					echo t("En saanut tiedostoa auki! Tarkista polku")." $pankkitiedostot_polku$kaunisnimi !";
					exit;
				}

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th>".t("Tiedosto")."</th><td>$kaunisnimi</td></tr>";
				echo "<tr><td class='back'><br></td></tr>";

				echo "<tr><th>".t("Maksutili")."<td>$pvmrow[tilinimi] $pvmrow[ytilino]</td></tr>";
				echo "<tr><th>".t("Valuutta")."</th><td>$pvmrow[valkoodi]</td></tr>";

				//Maksetaan hyvityslaskut alta pois, jos niit‰ on
				$query = "	SELECT maksu_tili, valkoodi, olmapvm, ultilno, swift, pankki1, pankki2, pankki3, pankki4, sisviesti1, sum(if(alatila='K', summa-kasumma, summa)) summa
							FROM lasku
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and tila = 'P'
							and maa <> '$kotimaa'
							and maksaja = '$kukarow[kuka]'
							and summa < 0
							and maksu_tili = '$pvmrow[maksu_tili]'
							and valkoodi = '$pvmrow[valkoodi]'
							GROUP BY maksu_tili, valkoodi, olmapvm, ultilno, swift, pankki1, pankki2, pankki3, pankki4, sisviesti1";
				$hyvitysresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hyvitysresult) > 0 ) {

					while ($hyvitysrow = mysql_fetch_array($hyvitysresult)) {
						$query = "	SELECT maksu_tili,
									lasku.nimi, lasku.nimitark, lasku.pankki_haltija,
									left(concat_ws(' ', osoite, osoitetark),45) osoite,
									left(concat_ws(' ', postino, postitp),45) postitp,
									sum(if(alatila='K', summa-kasumma, summa)) summa, lasku.valkoodi,
									group_concat(distinct viite) viite,
									group_concat(distinct viesti) viesti,
									group_concat(distinct laskunro) laskunro,
									ultilno, group_concat(lasku.tunnus) tunnus,
									yriti.tilino ytilino, yriti.nimi tilinimi,
									maa, pankki1, pankki2, pankki3, pankki4,
									swift, ytunnus, yriti.valkoodi yritivalkoodi, lasku.olmapvm
									FROM lasku, yriti, valuu
									WHERE lasku.yhtio = '$kukarow[yhtio]'
									and tila = 'P'
									and maa <> '$kotimaa'
									and yriti.tunnus = maksu_tili
									and yriti.yhtio = lasku.yhtio
									and valuu.nimi = lasku.valkoodi
									and valuu.yhtio = lasku.yhtio
									and maksaja = '$kukarow[kuka]'
									and maksu_tili = '$pvmrow[maksu_tili]'
									and lasku.valkoodi = '$pvmrow[valkoodi]'
									and olmapvm = '$hyvitysrow[olmapvm]'
									and maksu_tili = '$hyvitysrow[maksu_tili]'
									and ultilno = '$hyvitysrow[ultilno]'
									and swift = '$hyvitysrow[swift]'
									and pankki1 = '$hyvitysrow[pankki1]'
									and pankki2 = '$hyvitysrow[pankki2]'
									and pankki3 = '$hyvitysrow[pankki3]'
									and pankki4 = '$hyvitysrow[pankki4]'
									and sisviesti1 = '$hyvitysrow[sisviesti1]'
									GROUP BY maksu_tili, lasku.valkoodi, olmapvm, ultilno, swift, pankki1, pankki2, pankki3, pankki4, sisviesti1";
						$maksuresult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($maksuresult) > 0 ) {

							while ($laskurow = mysql_fetch_array($maksuresult)) {

								if (!isset($edmaksu_tili)) {
									$yritystilino = $laskurow["ytilino"];
									$yrityytunnus = $yhtiorow['ytunnus'];

									if ($laskurow["olmapvm"] >= date("Y-m-d")) {
										$maksupvm	= date("Y-m-d");
									}
									else {
										$maksupvm	= $laskurow["olmapvm"];
									}

									if ($kotimaa == "FI") {
										//haetaan t‰m‰n tilin tiedot
										if(isset($pankkitiedot[$yritystilino])) {
											foreach($pankkitiedot[$yritystilino] as $key => $value) {
												${$key} = $value;
											}
										}
										else {
											die(t("Kadotin t‰m‰n pankin maksuaineistotiedot!"));
										}
										require("inc/lum2otsik.inc");
									}
									else {
										require("inc/bgutotsik.inc");
									}

									$edmaksu_tili 		= $laskurow["maksu_tili"];
									$edvalkoodi 		= $laskurow["valkoodi"];
									$edyritystilino 	= $yritystilino;
									$edyritystilinimi	= $laskurow["tilinimi"];
								}

								$yritysnimi		= strtoupper($yhtiorow["nimi"]);
								$yritysosoite	= strtoupper($yhtiorow["osoite"]);
								$yritystilino	= $laskurow["ytilino"];

								// jos pankkihaltijan nimi on syˆtetty, laitetaan se nimen tilalle
								if ($laskurow['pankki_haltija'] != '') {
									$laskunimi1 = $laskurow["pankki_haltija"];
								}
								else {
									$laskunimi1 = $laskurow["nimi"];
									if ($laskurow["nimitark"] != "") {
										$laskunimi1 = $laskurow["nimi"]." ".$laskurow['nimitark'];
									}
								}

								$laskunimi2 		= $laskurow["osoite"];
								$laskunimi3 		= $laskurow["postitp"];
								$laskusumma 		= $laskurow["summa"];
								$laskuvaluutta 		= $laskurow["valkoodi"];
								$laskutilino 		= $laskurow["ultilno"];
								$laskumaakoodi 		= $laskurow["maa"];
								$laskupankki1  		= $laskurow["pankki1"];
								$laskupankki2  		= $laskurow["pankki2"];
								$laskupankki3  		= $laskurow["pankki3"];
								$laskupankki4  		= $laskurow["pankki4"];
								$laskuswift 		= $laskurow["swift"];
								$laskuyritivaluutta	= $laskurow["yritivalkoodi"];
								$ohjeitapankille 	= $laskurow["sisviesti1"];
								$laskuaihe			= $laskurow['viesti'];

								if ($laskurow['laskunro'] != 0 and $laskurow['laskunro'] != $laskurow['viesti']) {
					                $laskuaihe = (trim($laskuaihe) == "") ? $laskurow['laskunro'] : $laskuaihe." ".$laskurow['laskunro'];
					            }

								if ($kotimaa == "FI") {
									require("inc/lum2rivi.inc");
								}
								else {
									require("inc/bgutrivi.inc");
								}

								$makskpl += 1;
								$makssumma += $laskusumma;
								$maksulk += $ulklaskusumma;	//viritet‰‰n bgutrivi.inc-failissa
							}

							$query = "	UPDATE lasku SET
										tila = 'Q',
										popvm = '$popvm_nyt'
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and tila = 'P'
										and maa <> '$kotimaa'
										and maksaja = '$kukarow[kuka]'
										and olmapvm = '$hyvitysrow[olmapvm]'
										and maksu_tili = '$hyvitysrow[maksu_tili]'
										and ultilno = '$hyvitysrow[ultilno]'
										and swift = '$hyvitysrow[swift]'
										and pankki1 = '$hyvitysrow[pankki1]'
										and pankki2 = '$hyvitysrow[pankki2]'
										and pankki3 = '$hyvitysrow[pankki3]'
										and pankki4 = '$hyvitysrow[pankki4]'
										and sisviesti1 = '$hyvitysrow[sisviesti1]'
									    ORDER BY yhtio, tila";
							$result = mysql_query($query) or pupe_error($query);
						}
						else {
							echo "Meill‰ oli hyvityksi‰, mutta ne kaikki katosivat yhdistelyss‰!";
							exit;
						}
					}
				}

				// Yrit‰mme nyt v‰litt‰‰ maksupointterin $laskusis1:ss‰ --> $laskurow[9] --> tunnus
				$query = "	SELECT maksu_tili,
							lasku.nimi, lasku.nimitark, lasku.pankki_haltija,
							left(concat_ws(' ', osoite, osoitetark),45) osoite,
							left(concat_ws(' ', postino, postitp),45) postitp,
							summa, lasku.valkoodi, viite, viesti, laskunro,
							ultilno, lasku.tunnus, sisviesti2, sisviesti1,
							yriti.tilino ytilino, yriti.nimi tilinimi,
							maa, pankki1, pankki2, pankki3, pankki4,
							swift, alatila, kasumma, kurssi, ytunnus, yriti.valkoodi yritivalkoodi, lasku.olmapvm
							FROM lasku, yriti, valuu
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and tila = 'P'
							and maa <> '$kotimaa'
							and yriti.tunnus = maksu_tili
							and yriti.yhtio = lasku.yhtio
							and yriti.kaytossa = ''
							and valuu.nimi = lasku.valkoodi
							and valuu.yhtio = lasku.yhtio
							and maksaja = '$kukarow[kuka]'
							and maksu_tili = '$pvmrow[maksu_tili]'
							and lasku.valkoodi = '$pvmrow[valkoodi]'
							ORDER BY summa";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {

					while ($laskurow = mysql_fetch_array ($result)) {

						$yritysnimi 	= strtoupper($yhtiorow["nimi"]);
						$yritysosoite 	= strtoupper($yhtiorow["osoite"]);
						$yritystilino 	= $laskurow["ytilino"];

						// jos pankkihaltijan nimi on syˆtetty, laitetaan se nimen tilalle
						if ($laskurow['pankki_haltija'] != '') {
							$laskunimi1 = $laskurow["pankki_haltija"];
						}
						else {
							$laskunimi1 = $laskurow["nimi"];
							if ($laskurow["nimitark"] != "") {
								$laskunimi1 = $laskurow["nimi"]." ".$laskurow['nimitark'];
							}

						}

						$laskunimi2 	= $laskurow["osoite"];
						$laskunimi3 	= $laskurow["postitp"];

						if ($laskurow["olmapvm"] >= date("Y-m-d")) {
							$maksupvm	= date("Y-m-d");
						}
						else {
							$maksupvm	= $laskurow["olmapvm"];
						}

						if ($laskurow["alatila"] == 'K') { // maksetaan k‰teisalennuksella
							$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
						}
						else {
							$laskusumma = $laskurow["summa"];
						}

						$laskuvaluutta 		= $laskurow["valkoodi"];
						$laskutilino 		= $laskurow["ultilno"];
						$laskumaakoodi 		= $laskurow["maa"];
						$laskupankki1  		= $laskurow["pankki1"];
						$laskupankki2  		= $laskurow["pankki2"];
						$laskupankki3  		= $laskurow["pankki3"];
						$laskupankki4  		= $laskurow["pankki4"];
						$laskuswift 		= $laskurow["swift"];
						$laskuyritivaluutta	= $laskurow["yritivalkoodi"];
						$ohjeitapankille 	= $laskurow["sisviesti1"];
						$laskuaihe			= $laskurow['viesti'];

						if ($laskurow['laskunro'] != 0 and $laskurow['laskunro'] != $laskurow['viesti']) {
			                $laskuaihe = (trim($laskuaihe) == "") ? $laskurow['laskunro'] : $laskuaihe." ".$laskurow['laskunro'];
			            }

						//haetaan t‰m‰n tilin tiedot
						if ($kotimaa == "FI") {
							if(isset($pankkitiedot[$yritystilino])) {
								foreach($pankkitiedot[$yritystilino] as $key => $value) {
									${$key} = $value;
								}
							}
							else {
								die(t("Kadotin t‰m‰n pankin maksuaineistotiedot!"));
							}

							// haetaan automaagisesti EU maksu jos ehdot t‰yttyv‰t, muuten normaalilla maksum‰‰r‰kysen‰
							// T‰m‰ siis siksi ett‰ myˆs OP osaa ottaa laskut maksuun...
							if ($lum_eumaksu != "") {
								//onko t‰m‰ laskun eurom‰‰r‰ alle 50 000 eur?
								if ($laskusumma < 50000 and strtoupper($laskuvaluutta) == 'EUR') {
									// t‰sm‰‰kˆ maatunnukset
									$tinoalut = $laskutilino{0}.$laskutilino{1};
									$swiftmaa = $laskuswift{4}.$laskuswift{5};

									if ($tinoalut == $swiftmaa) {
										//onko EU maksun saaja EU alueella?
										$query = "	SELECT koodi
													FROM maat
													WHERE koodi = '$laskumaakoodi'
													and eu != ''
													and ryhma_tunnus = ''";
										$aburesult = mysql_query($query) or pupe_error($query);

										if (mysql_num_rows($aburesult) == 1) {
											// meill‰ on siis EU maksukelpoinen lasku
											// t‰m‰ vaatii seuraavat tiedot pois
											$laskupankki1 ='';
											$laskupankki2 ='';
											$laskupankki3 ='';
											$laskupankki4 ='';

											// eli t‰m‰ on kelpo eumaksu joten se me myˆs tehd‰‰n
											$lum_maksutapa = $lum_eumaksu;

											//echo "$laskunimi oli EU-maksu<br>";
										}
										else {
											//echo "$laskunimi ei EU-maksu, maa ei EU alueella. Tehtiin maksum‰‰r‰ys.<br>";
										}
									}
									else {
										//echo "$laskunimi ei EU-maksu SWIFT maatunnus ($swiftmaa) ja IBAN maatunnus ($tinoalut) eiv‰t t‰sm‰‰. Tehtiin maksum‰‰r‰ys.<br>";
									}
								}
								else {
									//echo "$laskunimi ei EU-maksu laskusumma on yli 50 000 eur tai valuutta ei ole EUR. Tehtiin maksum‰‰r‰ys.<br>";
								}
							}
						}

						if (!isset($edmaksu_tili)) {
							$yritystilino =  $laskurow["ytilino"];
							$yrityytunnus =  $yhtiorow['ytunnus'];

							if ($kotimaa == "FI") {
								require("inc/lum2otsik.inc");
							}
							else {
								require("inc/bgutotsik.inc");
							}

							$edmaksu_tili 		= $laskurow["maksu_tili"];
							$edvalkoodi 		= $laskurow["valkoodi"];
							$edyritystilino 	= $yritystilino;
							$edyritystilinimi 	= $laskurow["tilinimi"];
						}

						if ($kotimaa == "FI") {
							require("inc/lum2rivi.inc");
						}
						else {
							require("inc/bgutrivi.inc");
						}

						$makskpl += 1;
						$makssumma += $laskusumma;
						$maksulk += $ulklaskusumma;	//viritet‰‰n bgutrivi.inc-failissa
					}
				}

				if (isset($edmaksu_tili)) {

					if ($kotimaa == "FI") {
						require("inc/lum2summa.inc");
					}
					else {
						require("inc/bgutsumma.inc");
					}

					echo "<tr><th>".t("Summa")."</th><td>".sprintf('%.2f',$makssumma)." $edvalkoodi</td>";
					echo "<tr><th>".t("Tapahtumia")."</th><td>$makskpl ".t("kpl")."</td></tr>";

					$totkpl += $makskpl;
					$totsumma += $makssumma;
					$makskpl = 0;
					$makssumma = 0;
					unset($edmaksu_tili);

					// Jokainen pankki ja p‰iv‰ omaan tiedostoon
					if ($yhtiorow['pankkitiedostot'] == 'E') {
						if (is_resource($toot)) {
							fclose($toot);

							if ($tiedostonimilum2 == "") {
								$tiedostonimilum2 = $kaunisnimi;
							}

							echo "<tr><td class='back'><br></td></tr>";
							echo "<tr><th>".t("Tallenna aineisto")."</th>";
							echo "<form method='post' class='multisubmit'>";
							echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
							echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimilum2'>";
							echo "<input type='hidden' name='pankkifilenimi' value='$kaunisnimi'>";
							echo "<td><input type='submit' value='".t("Tallenna")."'></form></td>";
							echo "</tr>";
						}
					}

					$query = "	UPDATE lasku SET
								tila = 'Q',
								popvm = '$popvm_nyt'
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and tila = 'P'
								and maa <> '$kotimaa'
								and maksaja = '$kukarow[kuka]'
								and maksu_tili = '$pvmrow[maksu_tili]'
								and valkoodi = '$pvmrow[valkoodi]'
							    ORDER BY yhtio, tila";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><th>".t("Aineiston kokonaissumma")."</th><td>".sprintf('%.2f',$totsumma)."</td></tr>";
			echo "<tr><th>".t("Tapahtumia")."</th><td>$totkpl ".t("kpl")."</td></tr>";

			// Pankit ja p‰iv‰t yhdistet‰‰n
			if ($yhtiorow['pankkitiedostot'] == '' or $yhtiorow['pankkitiedostot'] == 'F') {
				if (is_resource($toot)) {
					fclose($toot);

					if ($tiedostonimilum2 == "") {
						$tiedostonimilum2 = $kaunisnimi;
					}

					echo "<tr><td class='back'><br></td></tr>";
					echo "<tr><th>".t("Tallenna aineisto")."</th>";
					echo "<form method='post' class='multisubmit'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimilum2'>";
					echo "<input type='hidden' name='pankkifilenimi' value='$kaunisnimi'>";
					echo "<td><input type='submit' value='".t("Tallenna")."'></form></td>";
					echo "</tr>";
				}
			}

			echo "</table>";

		}
		else {
			echo "<font class='message'>".t("Sopivia laskuja ei lˆydy")."</font>";
		}

	}

	require ("inc/footer.inc");

?>
