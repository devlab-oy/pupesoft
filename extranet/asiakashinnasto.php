<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_POST["tee_lataa"])) {
		if($_POST["tee_lataa"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (@include("../inc/parametrit.inc"));
	elseif (@include("parametrit.inc"));
	else exit;

	if (isset($tee_lataa)) {
		if ($tee_lataa == "lataa_tiedosto") {
			readfile("/tmp/".basename($tmpfilenimi));
			exit;
		}
	}
	else {

		echo "<font class='head'>".t("Hinnasto asiakashinnoin")."</font><hr>";

		$ytunnus = trim($ytunnus);

		if ($tee != '' and $ytunnus != '' and $kukarow["extranet"] == '') {

			if (isset($muutparametrit)) {
				$muutparametrit = unserialize(urldecode($muutparametrit));
				$mul_osasto 	= $muutparametrit[0];
				$mul_try 		= $muutparametrit[1];
			}

			$muutparametrit = array($mul_osasto, $mul_try);
			$muutparametrit = urlencode(serialize($muutparametrit));

			require ("inc/asiakashaku.inc");

			$asiakas = $asiakasrow["tunnus"];
			$ytunnus = $asiakasrow["ytunnus"];
		}
		elseif ($tee != '' and $kukarow["extranet"] != '') {
			//Haetaan asiakkaan tunnuksella
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				$asiakasrow = mysql_fetch_array($result);

				$ytunnus = $asiakasrow["ytunnus"];
				$asiakas = $asiakasrow["tunnus"];
			}
			else {
				echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
				exit;
			}
		}

		//Käyttöliittymä
		echo "<br>";
		echo "<table><form method='post'>";
		echo "<input type='hidden' name='tee' value='kaikki'>";

		if ($kukarow["extranet"] == '') {
			if ($asiakas > 0) {
				echo "<tr><th>".t("Asiakas").":</th><td><input type='hidden' name='ytunnus' value='$ytunnus'>$ytunnus $asiakasrow[nimi]</td></tr>";

				echo "<input type='hidden' name='asiakasid' value='$asiakas'></td></tr>";
			}
			else {
				echo "<tr><th>".t("Asiakas").":</th><td><input type='text' name='ytunnus' size='15' value='$ytunnus'></td></tr>";
			}

			echo "<tr><th>".t("Kieli").":</th><td><select name='hinkieli'>";

			$query  = "SHOW columns from sanakirja";
			$fields =  pupe_query($query);

			while ($apurow = mysql_fetch_array($fields)) {
				if (strlen($apurow[0]) == 2) {
					$sel = "";

					if ($hinkieli == $apurow[0]) {
						$sel = "SELECTED";
					}
					elseif ($asiakasrow["kieli"] == $apurow[0] and $hinkieli == "") {
						$sel = "SELECTED";
					}

					echo "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
				}
			}

			echo "</select></td></tr>";
		}
		else {
			$hinkieli = $kukarow["kieli"];
		}

		// Monivalintalaatikot (osasto, try tuotemerkki...)
		// Määritellään mitkä latikot halutaan mukaan
		$monivalintalaatikot = array("OSASTO", "TRY");

		echo "<tr><th>".t("Osasto")." / ".t("tuoteryhmä").":</th><td nowrap>";

		if (@include("tilauskasittely/monivalintalaatikot.inc"));
		elseif (@include("monivalintalaatikot.inc"));

		echo "</td></tr>";
		echo "</table><br>";
		echo "<input type='submit' name='ajahinnasto' value='".t("Aja hinnasto")."'>";
		echo "</form>";

		if ($kukarow["extranet"] == '' and $asiakas > 0) {
			echo "<form method='post'>";
			echo "<input type='submit' value='Valitse uusi asiakas'>";
			echo "</form>";
		}

		if ($tee != '' and $asiakas > 0 and isset($ajahinnasto)) {

			$kieltolisa 	= '';
			$sallitut_maat 	= $asiakasrow["toim_maa"] != '' ? $asiakasrow["toim_maa"] : $asiakasrow["maa"];

			if ($sallitut_maat != "") {
				$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$sallitut_maat%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$sallitut_maat%' ";
			}

			$query = "	SELECT kurssi
						FROM valuu
						WHERE nimi = '$asiakasrow[valkoodi]'
						and yhtio  = '$kukarow[yhtio]'";
			$asres = pupe_query($query);
			$kurssi = mysql_fetch_assoc($asres);

			$query = "	SELECT *
						FROM tuote
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.status NOT IN ('P','X')
						and tuote.tuotetyyppi NOT IN ('A', 'B')
						and tuote.hinnastoon != 'E'
						$kieltolisa
						$lisa
						ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
			$rresult = pupe_query($query);

			// KAUTTALASKUTUSKIKKARE
			if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and ($GLOBALS['koti_yhtio'] != $kukarow['yhtio'] or $asiakasrow['osasto'] != '6')) {
				$GLOBALS['eta_yhtio'] = "";
			}
			elseif (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '') {
				// haetaan etäyhtiön tiedot
				$yhtiorow_eta = $yhtiorow = hae_yhtion_parametrit($GLOBALS['eta_yhtio']);
			}

			echo "<br><br><font class='message'>".t("Asiakashinnastoa luodaan...")."</font><br>";
			flush();

			require_once ('inc/ProgressBar.class.php');
			$bar = new ProgressBar();
			$elements = mysql_num_rows($rresult); // total number of elements to process
			$bar->initialize($elements); // print the empty bar

			if (include('Spreadsheet/Excel/Writer.php')) {

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

			if (isset($workbook)) {
				$worksheet->writeString($excelrivi,  0, t("Ytunnus", $hinkieli).": $ytunnus", $format_bold);
				$excelrivi++;

				$worksheet->writeString($excelrivi,  0, t("Asiakas", $hinkieli).": $asiakasrow[nimi] $asiakasrow[nimitark]", $format_bold);
				$excelrivi++;

				$worksheet->writeString($excelrivi,  0, t("Tuotenumero", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  1, t("EAN-koodi", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  2, t("Osasto", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  3, t("Tuoteryhmä", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  4, t("Nimitys", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  5, t("Yksikkö", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  6, t("Aleryhmä", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  7, t("Veroton Myyntihinta", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi,  8, t("Verollinen Myyntihinta", $hinkieli), $format_bold);

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$worksheet->writeString($excelrivi,  9, t("Alennus{$alepostfix}", $hinkieli), $format_bold);
				}

				$worksheet->writeString($excelrivi, 10, t("Sinun veroton hinta", $hinkieli), $format_bold);
				$worksheet->writeString($excelrivi, 11, t("Sinun verollinen hinta", $hinkieli), $format_bold);
				$excelrivi++;
			}

			while ($rrow = mysql_fetch_assoc($rresult)) {

				$bar->increase();

				if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '{$GLOBALS["eta_yhtio"]}'
								AND tuoteno = '$rrow[tuoteno]'";
					$tres_eta = pupe_query($query);
					$alehinrrow = mysql_fetch_assoc($tres_eta);
					$yhtiorow = $yhtiorow_eta;
				}
				else {
					$alehinrrow = $rrow;
				}

				//haetaan asiakkaan oma hinta
				$laskurow["ytunnus"] 		= $asiakasrow["ytunnus"];
				$laskurow["liitostunnus"] 	= $asiakasrow["tunnus"];
				$laskurow["vienti"] 		= $asiakasrow["vienti"];
				$laskurow["alv"] 			= $asiakasrow["alv"];
				$laskurow["valkoodi"]		= $asiakasrow["valkoodi"];
				$laskurow["vienti_kurssi"]	= $kurssi;
				$laskurow["maa"]			= $asiakasrow["maa"];
				$laskurow['toim_ovttunnus'] = $asiakasrow["toim_ovttunnus"];

				$palautettavat_kentat = "hinta,netto,alehinta_alv,alehinta_val,hintaperuste,aleperuste";

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$palautettavat_kentat .= ",ale{$alepostfix}";
				}

				$hinnat = alehinta($laskurow, $alehinrrow, 1, '', '', '', $palautettavat_kentat, $GLOBALS['eta_yhtio']);

				// Kauttalaskutuksessa pitää otaa etäyhtiön tiedot
				if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
					$yhtiorow = $yhtiorow_eta;
				}

				// Otetaan erikoisalennus pois asiakashinnastosta
				// $hinnat['erikoisale'] = $asiakasrow["erikoisale"];
				$hinnat['erikoisale'] = 0;

				$hinta = $hinnat["hinta"];
				$netto = $hinnat["netto"];

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					${'ale'.$alepostfix} = $hinnat["ale{$alepostfix}"];
				}

				$alehinta_alv	= $hinnat["alehinta_alv"];
				$alehinta_val	= $hinnat["alehinta_val"];

				list($hinta, $lis_alv) = alv($laskurow, $rrow, $hinta, '', $alehinta_alv);

				$onko_asiakkaalla_alennuksia = FALSE;

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
						$onko_asiakkaalla_alennuksia = TRUE;
						break;
					}
				}

				// Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
				if ($rrow["hinnastoon"] == "V" and (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE)) {
					continue;
				}

				if ((float) $hinta == 0) {
					$hinta = $rrow["myyntihinta"];
				}

				if ($netto == "") {
					$alennukset = generoi_alekentta_php($hinnat, 'M', 'kerto');

					$asiakashinta = hintapyoristys($hinta * $alennukset);
				}
				else {
					$asiakashinta = $hinta;
				}

				$veroton				 = 0;
				$verollinen 			 = 0;
				$asiakashinta_veroton 	 = 0;
				$asiakashinta_verollinen = 0;

				if ($yhtiorow["alv_kasittely"] == "") {
					// Hinnat sisältävät arvonlisäveron
					$verollinen				 = $rrow["myyntihinta"];
					$veroton				 = round(($rrow["myyntihinta"]/(1+$rrow['alv']/100)), 2);
					$asiakashinta_veroton 	 = round(($asiakashinta/(1+$lis_alv/100)), 2);
					$asiakashinta_verollinen = $asiakashinta;
				}
				else {
					// Hinnat ovat nettohintoja joihin lisätään arvonlisävero
					$verollinen 			 = round(($rrow["myyntihinta"]*(1+$rrow['alv']/100)), 2);
					$veroton				 = $rrow["myyntihinta"];
					$asiakashinta_veroton 	 = $asiakashinta;
					$asiakashinta_verollinen = round(($asiakashinta*(1+$lis_alv/100)), 2);
				}

				if (isset($workbook)) {
					$worksheet->writeString($excelrivi, 0, $rrow["tuoteno"]);
					$worksheet->writeString($excelrivi, 1, $rrow["eankoodi"]);
					$worksheet->writeString($excelrivi, 2, $rrow["osasto"]);
					$worksheet->writeString($excelrivi, 3, $rrow["try"]);
					$worksheet->writeString($excelrivi, 4, t_tuotteen_avainsanat($rrow, 'nimitys', $hinkieli));
					$worksheet->writeString($excelrivi, 5, t_avainsana("Y", $hinkieli, "and avainsana.selite='$rrow[yksikko]'", "", "", "selite"));
					$worksheet->writeString($excelrivi, 6, $rrow["aleryhma"]);
					$worksheet->writeNumber($excelrivi, 7, $veroton);
					$worksheet->writeNumber($excelrivi, 8, $verollinen);

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						if ($netto != "") {
							$worksheet->writeString($excelrivi, 9, t("Netto", $hinkieli));
						}
						else {
							$worksheet->writeNumber($excelrivi, 9, sprintf('%.2f',${'ale'.$alepostfix}));
						}
					}

					$worksheet->writeNumber($excelrivi, 10, hintapyoristys($asiakashinta_veroton));
					$worksheet->writeNumber($excelrivi, 11, hintapyoristys($asiakashinta_verollinen));
					$excelrivi++;
				}
			}

			if (isset($workbook)) {

				// We need to explicitly close the workbook
				$workbook->close();

				echo "<br><br><table>";
				echo "<tr><th>".t("Tallenna hinnasto").":</th>";
				echo "<form method='post' class='multisubmit'>";
				echo "<input type='hidden' name='tee_lataa' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".t("Asiakashinnasto").".xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}

		}

		if (@include("inc/footer.inc"));
		elseif (@include("footer.inc"));
		else exit;
	}
?>