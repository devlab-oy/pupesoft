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
				$kaikki_tiedostorivit = pupeFileReader($kasiteltava_tiedosto_path, $tyyppi);
				echo "<br><br><font class='message'>".t("Luetaan l‰hetetty tiedosto")."...<br><br></font>";
			}
			else {
				$virheviesti .= t("Tiedostoformaattia ei tueta")."!<br>";
				$virhe = 1;
			}

			echo "<pre>";
			var_dump($kaikki_tiedostorivit);
			echo "</pre>";

			/*if ($tyyppi == "text/plain") {
				echo "PLAINTEKSTIFILE<br>";
					$filehandle = fopen($kasiteltava_tiedosto_path, "r");

					while ($tietue = fgets($filehandle)) {
						// Tyhj‰t rivit skipataan
						if (trim($tietue) == "") continue;
						$params = array();
						list($params['tuotenumero'], $params['kplmaara'], $params['varastopaikka_lahde'], $params['varastopaikka_kohde'], $params['kommentti'], $params['poistetaanko']) = explode("\t", $tietue);
						foreach ($params as &$parami) {
							$parami = pupesoft_cleanstring($parami);
						}
						echo "<pre>";
						var_dump($params);
						echo "</pre>";
						// Jos OK niin lis‰t‰‰n arskaan
						if ($virhe == 0) $all_params[]  = $params;
					}
					echo "<pre>";
					var_dump($all_params);
					echo "</pre>";
			}
			elseif($tyyppi == "application/vnd.ms-excel") {
				echo "KEKKONENEXCELFILE<br>";
			}
			else {
				$virheviesti .= t("Tuntematon tiedostoformaatti")."!<br>";
				$virhe = 1;
			}*/

		}
		else {
			$virheviesti .= t("Tiedostovalinta virheellinen")."!<br>";
			$tee = "VALITSE_TIEDOSTO";
			$virhe = 1;
		}

		// Lˆytyykˆ l‰hdepaikka valitusta varastosta (ja kohdepaikka samasta varastosta jos se on setattu)
		// Luodaan uusi varastopaikka jos kohdepaikkaa ei ole
		// M‰‰r‰ kentt‰ ok? - Onko m‰‰r‰ kent‰ss‰ "KAIKKI" - onko m‰‰r‰ <= myyt‰viss‰(jos ei niin fail)
		// Kommentti (optional)
		// Poistetaanko_lahdevarasto?

		if ($virhe == 0) {
			// jos kaikki on ok aletaan looppaa
			
			//$kutsuja = "varastopaikka_aineistolla.php";
			echo "<br> KAIKKI OK <br>";
			$tee = "";
		}
		else {
			$tee = "VALITSE_TIEDOSTO";
		}
	}

	if ($tee == "") {
		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '{$yhtiorow['yhtio']}'
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
		$ohje_sarake_2 = t("Anna siirrett‰v‰ kappalem‰‰r‰. Siirett‰v‰ kappalem‰‰r‰ ei voi ikin‰ ylitt‰‰ tuotteen myyt‰viss‰ olevaa kappalem‰‰r‰‰. Kappalem‰‰r‰ksi voi syˆtt‰‰ avainsanan %s jolloin k‰ytet‰‰n automaattisesti myyt‰viss‰ olevaa m‰‰r‰‰.", "", "KAIKKI");
		$ohje_sarake_3 = t("Varastopaikka mist‰ siirret‰‰n. Hyllyalue,hyllynumero,hyllyrivi,hyllytaso pilkulla eroteltuna");
		$ohje_sarake_4 = t("Varastopaikka mihin siirret‰‰n. Hyllyalue,hyllynumero,hyllyrivi,hyllytaso pilkulla eroteltuna. Jos paikkaa ei lˆydy niin sellainen luodaan annetuilla parametreill‰");
		$ohje_sarake_5 = t("Kommentti");
		$ohje_sarake_6 = t("Arvolla %s l‰hdepaikka poistetaan siirron j‰lkeen, arvolla %s l‰hdepaikkaa ei poisteta", "", "K", "E");

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
	
	if ($tee == 'AJA' and $userfile != '') {
		
		echo "nyt kutsuttiin ajoa";
	}

	if ($kutsuja == '') {
		require "inc/footer.inc";
	}