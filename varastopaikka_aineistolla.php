<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "varastopaikka_aineistolla.php")  !== FALSE) {
		require("inc/parametrit.inc");
		echo "<font class='head'>".t("Tuotteen varastopaikkojen muutos aineistolla")."</font><hr>";
	}

	if (!isset($tee) or (isset($varasto_valinta) and $varasto_valinta == '')) $tee = "";
	if (!isset($virheviesti)) $virheviesti = "";

	if ($tee == "AJA") {
		$virhe = 0;
		$kaikki_tiedostorivit = array();
		// Tutkitaan ja hutkitaan
		// Lˆytyykˆ tiedosto?
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
			$kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];
			list ($devnull, $tyyppi) = explode(".", $_FILES['userfile']['name']);

			$tyyppi = strtoupper($tyyppi);
			if ($tyyppi == "XLSX" or $tyyppi == "TXT") {

				echo "<br><br><font class='message'>".t("Luetaan l‰hetetty tiedosto")."...<br><br></font>";

				$kaikki_tiedostorivit = pupeFileReader($kasiteltava_tiedosto_path, $tyyppi);

				// Poistetaan tyhj‰t solut arraysta
				foreach ($kaikki_tiedostorivit as &$tiedr) {
					$tiedr = array_filter($tiedr);
				}
				$kaikki_tiedostorivit = array_filter($kaikki_tiedostorivit);

				// Siivous ja validitytsekit
				foreach ($kaikki_tiedostorivit as $rowkey => &$tiedr) {
					// Indeksit:
					$tuoteno = $tiedr[0] = pupesoft_cleanstring($tiedr[0]);								// 0 - Tuotenumero
					$kpl = $tiedr[1] = str_replace( ",", ".", pupesoft_cleanstring($tiedr[1]));			// 1 - M‰‰r‰
					$lahdevarastopk = $tiedr[2] = pupesoft_cleanstring($tiedr[2]);						// 2 - L‰hdevarastopaikka
					$kohdevarastopk = $tiedr[3] = pupesoft_cleanstring($tiedr[3]);						// 3 - Kohdevarastopaikka
					$kom = $tiedr[4] = pupesoft_cleanstring($tiedr[4]);									// 4 - Kommentti
					$poistetaanko_lahde = $tiedr[5] = pupesoft_cleanstring($tiedr[5]);					// 5 - Poistetaanko l‰hdevarastopaikka
					if ($poistetaanko_lahde != 'X') $tiedr[5] = '';

					// Jos joku pakollisista tiedoista on tyhj‰ tai v‰‰r‰ poistetaan koko rivi
					if (in_array("", array($tuoteno, $kpl, $lahdevarastopk, $kohdevarastopk)) or $lahdevarastopk == $kohdevarastopk or ($kpl == 0 or (!is_numeric($kpl) and $kpl != "X"))) {
						unset($kaikki_tiedostorivit[$rowkey]);
						continue;
					}

					list($lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso) = explode(",", $lahdevarastopk);

					// Tarkistetaan onko tuote ja l‰hdevarastopaikka olemassa
					$query = "	SELECT *
								FROM tuotepaikat use index (tuote_index), tuote
								WHERE tuotepaikat.yhtio	  = '$kukarow[yhtio]'
								and tuotepaikat.tuoteno	  = '$tuoteno'
								and tuotepaikat.hyllyalue = '$lhyllyalue'
								and tuotepaikat.hyllynro  = '$lhyllynro'
								and tuotepaikat.hyllyvali = '$lhyllyvali'
								and tuotepaikat.hyllytaso = '$lhyllytaso'
								and tuote.tuoteno		  = tuotepaikat.tuoteno
								and tuote.yhtio			  = tuotepaikat.yhtio";
					$tvresult = pupe_query($query);

					if (mysql_num_rows($tvresult) == 0) {
						unset($kaikki_tiedostorivit[$rowkey]);
						continue;
					}
					else {
						$ressu = mysql_fetch_assoc($tvresult);
						$tiedr[2] = $ressu['tunnus'];
						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', $varasto_valinta, '', $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso);
						if ($kpl > $myytavissa or $kpl == "X") $tiedr[1] = $myytavissa;
					}

					list($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso) = explode(",", $kohdevarastopk);

					// Onko kohdevarastopaikka olemassa
					$query = "	SELECT *
								from tuotepaikat use index (tuote_index)
								where tuoteno = '$tuoteno'
								and yhtio	  = '$kukarow[yhtio]'
								and hyllyalue = '$ahyllyalue'
								and hyllynro  = '$ahyllynro'
								and hyllyvali = '$ahyllyvali'
								and hyllytaso = '$ahyllytaso'";
					$kvresult = pupe_query($query);

					// Jos tuotepaikkaa ei lˆydy, yritet‰‰n perustaa sellainen
					if (mysql_num_rows($kvresult) == 0) {
						$tee = "UUSIPAIKKA";
						$kutsuja = "varastopaikka_aineistolla.php";
						require("muuvarastopaikka.php");
					}

					if (isset($failure)) unset($kaikki_tiedostorivit[$rowkey]);
					else {
						// Tsekataan uusiksi ett‰ saadaan tunnus siirtoa varten
						$kvresult = pupe_query($query);
						if (mysql_num_rows($kvresult) == 0) $virhe = 1;
						else {
							$ressi = mysql_fetch_assoc($kvresult);
							$tiedr[3] = $ressi['tunnus'];
						} 
						
					}
					if (in_array('', array($tiedr[2],$tiedr[3]))) $virhe = 1;
					if ($tee = "PALATTIIN_MUUSTA") $tee = "AJA";

				}

			}
			else {
				$virheviesti .= t("Tiedostoformaattia ei tueta")."!<br>";
				$virhe = 1;
			}

		}
		else {
			$virheviesti .= t("Tiedostovalinta virheellinen")."!<br>";
			$virhe = 1;
		}

		if (count($kaikki_tiedostorivit) < 1 and $virhe == 0) {
			$virheviesti .= t("Tiedostosta ei lˆytynyt yht‰‰n validia rivi‰")."!<br>";
			$virhe = 1;
		}

		// Jos kaikki on ok ja soluja on viel‰ j‰ljell‰
		if ($virhe == 0) {

			echo "<br><br><font class='message'>".t("Siirret‰‰n %s rivi‰", "", count($kaikki_tiedostorivit))."...<br><br></font>";

			foreach ($kaikki_tiedostorivit as $tkey => $tval) {
				// Parametrit muu_varastopaikka.phplle
				// $asaldo  = siirrett‰v‰ m‰‰r‰
				// $mista   = tuotepaikan tunnus josta otetaan
				// $minne   = tuotepaikan tunnus jonne siirret‰‰n
				// $tuoteno = tuotenumero jota siirret‰‰n
				$tuoteno = $tval[0];				// 0 - Tuotenumero
				$asaldo = $tval[1];					// 1 - M‰‰r‰
				$mista = $tval[2];					// 2 - L‰hdevarastopaikka - tunnus
				$minne = $tval[3];					// 3 - Kohdevarastopaikka - tunnus
				$kom = $tval[4];					// 4 - Kommentti
				$poistetaanko_lahde = $tval[5];		// 5 - Poistetaanko l‰hdevarastopaikka

				$tee = "N";
				$kutsuja = "varastopaikka_aineistolla.php";
			}

			if ($tee == 'MEGALOMAANINEN_ONNISTUMINEN') echo "JEEE JUHLAT KAIKKI TOIMII<br>";
			$tee = "";
			$kutsuja = "";
		}
		else {
			$tee = "VALITSE_TIEDOSTO";
		}
	}

	if ($tee == "") {

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nimitys != ''
					AND tyyppi != 'P'
					ORDER BY tyyppi,nimitys";
		$vresult = pupe_query($query);

		echo "	<form name='varasto' method='post'>
				<input type='hidden' name='tee' value='VALITSE_TIEDOSTO'>
				<table>
				<tr><th>".t("Valitse kohdevarasto").":</th>
				<td><select name='varasto_valinta'><option value = ''>".t("Ei varastoa")."</option>";
		while($varasto = mysql_fetch_assoc($vresult)) {
			$sel = "";
			if ($varasto_valinta != '' and $varasto_valinta == $varasto['tunnus']) $sel = "SELECTED";
			echo "<option value='{$varasto['tunnus']}' $sel>{$varasto['nimitys']}</option>";
		}
		echo "	</select></td><td class='back'><font class='error'>{$virheviesti}</font></td></tr>
				</table>
				<br><input type = 'submit' value = '".t("Hae")."'>
				</form>";
	}

	if ($tee == 'VALITSE_TIEDOSTO' and $varasto_valinta != '') {

		$ohje_sarake_1 = t("Tuotenumero");
		$ohje_sarake_2 = t("Anna siirrett‰v‰ kappalem‰‰r‰. Siirett‰v‰ kappalem‰‰r‰ ei voi ikin‰ ylitt‰‰ tuotteen myyt‰viss‰ olevaa kappalem‰‰r‰‰. Kappalem‰‰r‰ksi voi syˆtt‰‰ avainsanan %s jolloin k‰ytet‰‰n automaattisesti myyt‰viss‰ olevaa m‰‰r‰‰.", "", "X");
		$ohje_sarake_3 = t("Varastopaikka mist‰ siirret‰‰n. Hyllyalue,hyllynumero,hyllyv‰li,hyllytaso pilkulla eroteltuna");
		$ohje_sarake_4 = t("Varastopaikka mihin siirret‰‰n. Hyllyalue,hyllynumero,hyllyv‰li,hyllytaso pilkulla eroteltuna. Jos paikkaa ei lˆydy niin sellainen luodaan annetuilla parametreill‰");
		$ohje_sarake_5 = t("Kommentti");
		$ohje_sarake_6 = t("Arvolla %s l‰hdepaikka poistetaan siirron j‰lkeen, muuten l‰hdepaikkaa ei poisteta", "", "X");

		echo "	<table>
				<tr><th colspan='6'>".t("Sarkaineroteltu tekstitiedosto tai excel-tiedosto.")."</th></tr>
				<tr><td title='{$ohje_sarake_1}'>".t("Tuotenumero")."</td>
					<td title='{$ohje_sarake_2}'>".t("M‰‰r‰")."</td>
					<td title='{$ohje_sarake_3}'>".t("L‰hdepaikka")."</td>
					<td title='{$ohje_sarake_4}'>".t("Kohdepaikka")."</td>
					<td title='{$ohje_sarake_5}'>".t("Kommentti")."</td>
					<td title='{$ohje_sarake_6}'>".t("Poistetaanko l‰hdepaikka lopuksi")."</td></tr>
				</table><br><font class='message'>".t("Lis‰tietoja saat kohdistamalla kursorin yll‰oleviin sarakkeisiin")."</font><br><br>";
		echo "	<form name='tiedosto' method='post' enctype='multipart/form-data'>
				<input type='hidden' name='varasto_valinta' value='$varasto_valinta'>
				<input type='hidden' name='tee' value='AJA'>
				<table>
				<tr><th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("L‰het‰")."'></td><td class='back'><font class='error'>{$virheviesti}</font></td></tr>
				</table></form>";
	}

	if ($kutsuja == '') {
		require "inc/footer.inc";
	}
