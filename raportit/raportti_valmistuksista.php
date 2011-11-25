<?php

	// Datatables p‰‰lle
	$pupe_DataTables = array("raportti_valmistuksista");

	if (isset($_REQUEST["tee"])) {
		if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
	}
	
	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;
	
	require ("../inc/parametrit.inc");
	
	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Raportti avoimista ja tehdyist‰ valmistuksista")."</font><hr>";
		echo "<br>";

		echo "<table>";
		echo "<form method='post' action='$PHP_SELF'>";

		if (!isset($kk1)) {
			$kk1 = date("m")-1;
			$kk2 = date("m");
		}
		if (!isset($vv1)) {
			$vv1 = date("Y");
			$vv2 = date("Y");
		}
		if (!isset($pp1)){
			$pp1 = date("d");
			$pp2 = date("d");
		}
		echo "<tr>";
		echo "<th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
		echo "<td><input type='text' name='pp1' value='$pp1' size='3'>";
		echo "<input type='text' name='kk1' value='$kk1' size='3'>";
		echo "<input type='text' name='vv1' value='$vv2' size='5'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
		echo "<td><input type='text' name='pp2' value='$pp2' size='3'>";
		echo "<input type='text' name='kk2' value='$kk2' size='3'>";
		echo "<input type='text' name='vv2' value='$vv2' size='5'></td>";

		echo "<tr><th>".t("Rajaa tuotekategorialla")."</th>";
		echo "<td >";

		$monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
		require ("tilauskasittely/monivalintalaatikot.inc");

		echo "</td>";
		echo "<tr class='back'>";
		echo "<td></td>";
		echo "<td><input type='hidden' value='ajaraportti' name='tee'>";

		echo "<input type='submit' name ='submit_nappi' value='".t("Aja raportti")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		echo "<br>";

		if (include('Spreadsheet/Excel/Writer.php')) {
			//keksit‰‰n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet = $workbook->addWorksheet('Raportti Valmistuksista');

			$format_bold = $workbook->addFormat();
			$format_bold->setBold();

			$excelsarake = 0;
			$excelrivi = 0;
		}

		if ($tee == "ajaraportti" and isset($submit_nappi)) {
			
			pupe_DataTables(array(array($pupe_DataTables[0], 9, 9)));

			if (checkdate($kk1, $pp1, $vv1) and checkdate($kk2, $pp2, $vv2)) {
				$pvmalku	= $vv1."-".sprintf("%02.2s",$kk1)."-".sprintf("%02.2s",$pp1);
				$pvmloppu	= $vv2."-".sprintf("%02.2s",$kk2)."-".sprintf("%02.2s",$pp2);
			}
			else {
				echo "<p class='error'>".t("Virhe: P‰iv‰m‰‰riss‰ ongelmia")."</p><br>";
				die();
			}

			$query = "	(SELECT
						sum(tilausrivi.kpl) valmistettu,
						sum(tilausrivi.tilkpl) tarvitaan,
						sum(tilausrivi.varattu) valmistetaan ,
						tuote.tuoteno,
						tuote.valmistuslinja,
						tilausrivi.toimaika,
						tilausrivi.toimitettuaika,
						lasku.tila,
						lasku.alatila, tuote.try, tuote.osasto
						FROM tuote
						JOIN tilausrivi on (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tilausrivi.tyyppi in ('W','M')
											and tilausrivi.toimaika between '{$pvmalku}' and '{$pvmloppu}'
											and tilausrivi.toimitettuaika = '0000-00-00 00:00:00')
						JOIN lasku on (lasku.yhtio = tuote.yhtio and lasku.tunnus = tilausrivi.otunnus)
						WHERE tuote.yhtio = 'mast'
						AND tuote.status !='P'
						{$lisa}
						group by 4,5,6,7,8,9
						)
						UNION
						(SELECT
						sum(tilausrivi.kpl) valmistettu,
						sum(tilausrivi.tilkpl) tarvitaan,
						sum(tilausrivi.varattu) valmistetaan ,
						tuote.tuoteno,
						tuote.valmistuslinja,
						tilausrivi.toimaika,
						tilausrivi.toimitettuaika,
						lasku.tila,
						lasku.alatila, tuote.try, tuote.osasto
						FROM tuote
						JOIN tilausrivi on (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tilausrivi.tyyppi in ('W','M')
											and tilausrivi.toimitettuaika between '{$pvmalku} 00:00:00' and '{$pvmloppu} 23:59:59')
						JOIN lasku on (lasku.yhtio = tuote.yhtio and lasku.tunnus = tilausrivi.otunnus)
						WHERE tuote.yhtio = 'mast'
						AND tuote.status !='P'
						{$lisa}
						group by 4,5,6,7,8,9
						)
						order by valmistuslinja, tuoteno,  alatila";

			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {

				// Alustetaan array
				$yhteenveto = array();

				if (isset($workbook)) {
					$worksheet->write($excelrivi, $excelsarake, t("tuoteno"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tuoteosasto"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm‰"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Valmistuslinja"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Valmistettu kpl"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Valmistetaan kpl"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Valmistuksen tila"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Suunniteltu valmistusp‰iv‰"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Toteutunut valmistusp‰iv‰"), $format_bold);
					$excelsarake++;
				}
				echo "<table class='display dataTable' id='$pupe_DataTables[0]'>";
				echo "<thead>";
				echo "<tr>";
				echo "<th>".t("Tuoteno")."</th>";
				echo "<th>".t("Tuoteosasto")."</th>";
				echo "<th>".t("Tuoteryhm‰")."</th>";
				echo "<th>".t("Valmistuslinja")."</th>";
				echo "<th>".t("Valmistettu kpl")."</th>";
				echo "<th>".t("Valmistetaan kpl")."</th>";
				echo "<th>".t("Valmistuksen tila")."</th>";
				echo "<th>".t("Suunniteltu Valmistusp‰iv‰")."</th>";
				echo "<th>".t("Toteutunut Valmistusp‰iv‰")."</th>";
				echo "</tr>";

				echo "<tr>";
				echo "<td><input type='text' size='8' class='search_field' name='search_tuoteno_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_osasto_haku'></td>";
				echo "<td><input type='text' class='search_field' name='search_try_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_valmistuslinja_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_valmistettu_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_valmistetaan_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_tila_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_valmistuspaiva_haku'></td>";
				echo "<td><input type='text' size='8' class='search_field' name='search_valmistettu_haku'></td>";
				echo "</tr>";
				echo "</thead>";
				echo "<tbody>";

				while ($rivit = mysql_fetch_assoc($result)) {
					$excelsarake = 0;
					$excelrivi++;

					$laskutyyppi = $rivit["tila"];
					$alatila	 = $rivit["alatila"];
					require ("inc/laskutyyppi.inc");

					// otetaan selkokieliset nimet esiin avainsanoista
					$linja 	= t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='$rivit[valmistuslinja]'", "", "", "selitetark");
					$osasto = t_avainsana("OSASTO", "", "and avainsana.selite='$rivit[osasto]'", "", "", "selitetark");
					$try 	= t_avainsana("TRY", "", "and avainsana.selite='$rivit[try]'", "", "", "selitetark");

					if($linja == "") $linja = t("Ei m‰‰ritelty");

					echo "<tr>";
					echo "<td>{$rivit["tuoteno"]}</td>";
					echo "<td>{$osasto}</td>";
					echo "<td>{$try}</td>";
					echo "<td>$linja</td>";
					echo "<td align='right'>{$rivit["valmistettu"]}</td>";
					echo "<td align='right'>{$rivit["valmistetaan"]}</td>";
					echo "<td>{$alatila}</td>";
					echo "<td align='right'>".tv1dateconv($rivit["toimaika"])."</td>";
					echo "<td align='right'>".tv1dateconv($rivit["toimitettuaika"])."</td>";
					echo "</tr>";

					if (isset($workbook)) {
						// kirjoitetaan samat exceliin
						$worksheet->write($excelrivi, $excelsarake, $rivit["tuoteno"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $rivit["osasto"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $rivit["try"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $linja);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $rivit["valmistettu"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $rivit["valmistetaan"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $alatila);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $rivit["toimaika"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $rivit["toimitettuaika"]);
						$excelsarake++;
					}
					
					// tehd‰‰n array johon tallennetaan valmistuslinja, valmistettu, valmistetaan
					$yhteenveto[$rivit["valmistuslinja"]]["valmistettu"] += $rivit["valmistettu"];
					$yhteenveto[$rivit["valmistuslinja"]]["valmistetaan"] += $rivit["valmistetaan"];

				}
				echo "</tbody>";
				// suljetaan excel
				$workbook->close();

				echo "<tfoot>";
				echo "<tr>";
				echo "<th colspan='4'>".t("Yhteens‰").":</th>";
				echo "<td valign='top' class='tumma' name='yhteensa' id='yhteensa_4' align='right'></td>";
				echo "<td valign='top' class='tumma' name='yhteensa' id='yhteensa_5' align='right'></td>";
				echo "</tr>";
				echo "</tfoot>";
				echo "</table>";
				echo "<br>";

				// tehd‰‰n summat taulu
				echo "<br>";
				echo "<table>";
				echo "<tr><th colspan='3'>".t("Summat Valmistuslinjoittain")."</th></tr>";
				echo "<tr>";
				echo "<th>".t("Valmistuslinja")."</th>";
				echo "<th>".t("Valmistettu")."</th>";
				echo "<th>".t("Valmistuksessa")."</th>";
				echo "</tr>";

				foreach ($yhteenveto as $valmistuslinja => $value) {
					$linja_yht = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='$valmistuslinja'", "", "", "selitetark");
					if ($linja_yht == "") $linja_yht = t("Ei M‰‰ritelty");
					echo "<tr>";
					echo "<td>{$linja_yht}</td>";
					echo "<td name='' id=''>{$value["valmistettu"]}</td>";
					echo "<td>{$value["valmistetaan"]}</td>";
					echo "</tr>";
				}
				echo "</table>";

				echo "<br>";
				echo "<form method='post'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='raportti_valmistuksista.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<table>";
				echo "<tr><th>",t("Tallenna raportti (xls)"),":</th>";
				echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
				echo "</table></form><br />";
			}
			else {
				echo "<p class='error'>".t("Virhe").": ".t("Valinnoilla ei lˆytynyt osumia")."</p>";
			}
		}
	}
	
	require ("inc/footer.inc");
?>