<?php

	require ("inc/parametrit.inc");

	if ($tee == "keraa") {
		if (isset($_FILES['userfile']) and (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE)) {

			if ($_FILES['userfile']['size'] == 0) {
				echo "<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>";
				$tee = '';
			}

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$ext = strtoupper($path_parts['extension']);

			$retval = tarkasta_liite("userfile", array("XLSX","XLS","ODS","SLK","XML","GNUMERIC","CSV","TXT","DATAIMPORT"));

			if ($retval !== TRUE) {
				echo "<font class='error'><br>".t("V‰‰r‰ tiedostomuoto")."!</font>";
				$tee = '';
			}
		}
	}

	if ($tee == "keraa") {
		$excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

		$kerattavat = array();

		foreach ($excelrivit as $rivinumero => $rivi) {

			// index 0, maara
			// index 1, tilaus
			// index 2, tuoteno

			$kpl 	= $rivi[0];
			$tilaus = $rivi[1];
			$tuote  = $rivi[2];

			if (!isset($kerattavat[$tilaus][$tuote])) $kerattavat[$tilaus][$tuote] = $kpl;
			else $kerattavat[$tilaus][$tuote] += $kpl;
		}

		$lask = 0;

		foreach ($kerattavat as $tilaus => $tuotteet) {

			// Nollataan muuttujat
			$maara = $kerivi = $rivin_varattu = $rivin_puhdas_tuoteno = $rivin_tuoteno = $vertaus_hylly = $keraysera_maara = $poikkeama_kasittely = array();

			$tilaus = (int) $tilaus;
			$tilauksenrivit = array();

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus	= $tilaus
						AND tila 	= 'L'
						AND alatila = 'A'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font class='error'>".t("Tilausta: %s ei voida merkata ker‰tyksi. Tilaus v‰‰r‰ss‰ tilassa", "", $tilaus)."!</font><br>";
				continue;
			}
			else {
				$lask++;
				if ($lask > 5) exit;

				$tilausrow = mysql_fetch_assoc($result);
			}

			foreach ($tuotteet as $tuote => $kpl) {
				$query = "	SELECT tunnus, varattu,
							tuoteno AS puhdas_tuoteno,
							concat_ws(' ',tuoteno, nimitys) tuoteno,
							concat_ws('###',hyllyalue, hyllynro, hyllyvali, hyllytaso) varastopaikka_rekla
							FROM tilausrivi
							WHERE yhtio  = '{$kukarow['yhtio']}'
							AND otunnus  = '{$tilaus}'
							AND tyyppi 	 = 'L'
							AND tuoteno  = '{$tuote}'
							AND keratty  = ''
							AND varattu != 0
							ORDER BY varattu desc";
				$valmis_era_chk_res = pupe_query($query);

				$rivimaara = mysql_num_rows($valmis_era_chk_res);
				$rivilask  = 1;

				while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
					$kerivi[] = $valmis_era_chk_row['tunnus'];
					$tilauksenrivit[] = $valmis_era_chk_row['tunnus'];

					if ($rivimaara == 1 and $valmis_era_chk_row['varattu'] != $kpl) {
						// Vain yksi rivi ja ker‰tty m‰‰r‰ on eri ku myyty m‰‰r‰
						$maara[$valmis_era_chk_row['tunnus']] = $kpl;
					}
					elseif ($rivimaara > 1 and $rivimaara < $rivilask and $valmis_era_chk_row['varattu'] > $kpl) {
						// Enemm‰n kuin yksi rivi ja ker‰tty m‰‰r‰ on pienempi kuin myyty m‰‰r‰
						$maara[$valmis_era_chk_row['tunnus']] = $kpl;
						$kpl = 0;
					}
					elseif ($rivimaara > 1 and $rivimaara == $rivilask and $valmis_era_chk_row['varattu'] != $kpl) {
						// Vika rivi ja ker‰tty m‰‰r‰ on eri kuin myyty m‰‰r‰
						$maara[$valmis_era_chk_row['tunnus']] = $kpl;
					}
					else {
						$maara[$valmis_era_chk_row['tunnus']] = "";
					}

					$rivin_varattu[$valmis_era_chk_row['tunnus']] 		 = $valmis_era_chk_row['varattu'];
					$rivin_puhdas_tuoteno[$valmis_era_chk_row['tunnus']] = $valmis_era_chk_row['puhdas_tuoteno'];
					$rivin_tuoteno[$valmis_era_chk_row['tunnus']] 		 = $valmis_era_chk_row['tuoteno'];
					$vertaus_hylly[$valmis_era_chk_row['tunnus']] 		 = $valmis_era_chk_row['varastopaikka_rekla'];

					if ($kpl > 0) $kpl -= $valmis_era_chk_row['varattu'];
					$rivilask++;
				}
			}

			$failinrivit = implode(",", $tilauksenrivit);

			if ($failinrivit != "") {
				$query = "	SELECT tilausrivi.tunnus, tilausrivi.varattu,
							tilausrivi.tuoteno AS puhdas_tuoteno,
							concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
							concat_ws('###',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka_rekla,
							tuote.status
							FROM tilausrivi
							JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
							WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus  = '{$tilaus}'
							AND tilausrivi.tyyppi 	= 'L'
							AND tilausrivi.keratty  = ''
							AND tilausrivi.varattu != 0
							AND tilausrivi.tunnus not in ($failinrivit)
							ORDER BY tilausrivi.varattu desc";
				$valmis_era_chk_res = pupe_query($query);

				while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
					$kerivi[] 											 = $valmis_era_chk_row['tunnus'];
					$maara[$valmis_era_chk_row['tunnus']] 				 = 0;
					$rivin_varattu[$valmis_era_chk_row['tunnus']] 		 = $valmis_era_chk_row['varattu'];
					$rivin_puhdas_tuoteno[$valmis_era_chk_row['tunnus']] = $valmis_era_chk_row['puhdas_tuoteno'];
					$rivin_tuoteno[$valmis_era_chk_row['tunnus']] 		 = $valmis_era_chk_row['tuoteno'];
					$vertaus_hylly[$valmis_era_chk_row['tunnus']] 		 = $valmis_era_chk_row['varastopaikka_rekla'];

					if ($valmis_era_chk_row["status"] == "P") {
						$poikkeama_kasittely[$valmis_era_chk_row["tunnus"]]	 = "PU";
					}
					else {
						$poikkeama_kasittely[$valmis_era_chk_row["tunnus"]]	 = "JT";
					}
				}
			}

			echo "<table>";

			foreach ($kerivi as $rivitunnus) {
				echo "<tr>";
				echo "<th>",$tilaus,"</th>";
				echo "<th>",$tilausrow['nimi'],"</th>";
				echo "<td>",$rivitunnus,"</td>";
				echo "<td>",$maara[$rivitunnus],"</td>";
				echo "<td>",$rivin_varattu[$rivitunnus],"</td>";
				echo "<td>",$rivin_puhdas_tuoteno[$rivitunnus],"</td>";
				echo "<td>",$rivin_tuoteno[$rivitunnus],"</td>";
				echo "<td>",$vertaus_hylly[$rivitunnus],"</td>";
				echo "<td>",$poikkeama_kasittely[$rivitunnus],"</td>";
				echo "</tr>";
			}

			echo "</table><br>";

			// setataan muuttujat keraa.php:ta varten
			$tee 		 = "P";
			$toim 		 = "";
			$id 		 = $tilaus;
			$keraajanro  = 0;
			$keraajalist = $kukarow["kuka"];

			$oslappkpl 	= $yhtiorow["oletus_oslappkpl"];
			$lahetekpl 	= $yhtiorow["oletus_lahetekpl"];
			$vakadrkpl	= $yhtiorow["oletus_lahetekpl"];

			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio	= '$kukarow[yhtio]'
						AND tunnus	= '$tilausrow[varasto]'
						ORDER BY alkuhyllyalue,alkuhyllynro";
			$kirre = pupe_query($query);
			$prirow = mysql_fetch_assoc($kirre);

			// vakadr-tulostin on aina sama kuin l‰hete-tulostin
			$valittu_tulostin = $vakadr_tulostin = $prirow['printteri1'];
			$valittu_oslapp_tulostin = $prirow['printteri3'];

			$lasku_yhtio = "";
			$real_submit = "Merkkaa ker‰tyksi";

			require('tilauskasittely/keraa.php');
		}
	}

	echo "<br><br><form method='post' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='tee' value='keraa'>";

	echo "<br><br>
			<font class='head'>".t("Lue ker‰tt‰v‰t tuotteet tiedostosta")."</font><hr>
			<table>
			<tr><th colspan='3'>".t("Tiedostomuoto").":</th></tr>
			<tr>";
	echo "<td>".t("Kpl")."</td><td>".t("Tilausnumero")."</td><td>".t("Tuoteno")."</td>";
	echo "</tr>";
	echo "<tr><td class='back'><br></td></tr>";
	echo "<tr><th>".t("Valitse tiedosto").":</th>
			<td colspan='2'><input name='userfile' type='file'></td></tr>";

	echo "</table>";
	echo "<br><br><input type='submit' value='".t("Valitse")."'></form>";

	require ("inc/footer.inc");