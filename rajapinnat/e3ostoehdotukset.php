<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("E3-ostoehdotuksen sisäänluku")."</font><hr>";

	$kansio 					= "/Users/jamppa/Desktop/e3/";			//Pitää vaihtaa !!
	$kansio_valmis				= "/Users/jamppa/Desktop/e3/valmis/";	//Pitää vaihtaa !!
	$toimittajaytunnus 			= "";
	$e3ostotilausnumero 		= "";
	$tuoteno					= "";
	$varasto 					= "";
	$kpl 						= '';
	$toimituspaiva 				= "";
	$toivotttutoimituspaiva 	= "";
	$myyjannumero 				= "";

	function datansisalto_e3($kansio, $kansio_valmis, $filunloppu, $otunnus, $toimituspaiva) {

		global $yhtiorow, $kukarow;

		$laskuquery = " SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$otunnus'";
		$lasku_result = mysql_query($laskuquery) or pupe_error($laskuquery);
		$laskurow = mysql_fetch_array($lasku_result);

		$file = "d".$filunloppu;
		$lines = file($kansio.$file);

		foreach ($lines as $line) {

			$tuoteno 		= pupesoft_cleanstring(substr($line, 12, 17));
			$varasto	 	= pupesoft_cleanstring(substr($line, 30, 3));
			$tuotenimi	 	= pupesoft_cleanstring(substr($line, 33, 34));
			$kpl 			= pupesoft_cleannumber(substr($line, 68, 7));
			//$hinta 			= pupesoft_cleannumber(substr($line, 91, 13));
			//$hinta 			= $hinta / 10000;

			$tuote_query = "	SELECT tuote.try,
								tuote.osasto,
								tuote.tuoteno,
								tuote.nimitys,
								tuote.yksikko,
								tuotepaikat.hyllyalue,
								tuotepaikat.hyllynro,
								tuotepaikat.hyllytaso,
								tuotepaikat.hyllyvali
								FROM tuote
								LEFT JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								AND tuote.tuoteno = '$tuoteno'
								ORDER BY tuotepaikat.oletus DESC
								LIMIT 1";
			$tuote_result = mysql_query($tuote_query) or pupe_error($tuote_query);
			$tuote_row = mysql_fetch_array($tuote_result);

			if ($tuote_row['tuoteno'] == '') {
				echo "<br>";
				echo "<font class='error'>".t("Tiedostosta %s tuotetietoja tuotteelle %s ei löydy tuotehallinnasta. Tuotetta ei lisätty ostoehdotukseen.", "", $file,$tuoteno)."</font>";
				echo "<br>";
			}
			else {
				$hinta = tuotteen_ostohinta ($laskurow, $tuote_row, $kpl);
				$insert_query = "	INSERT INTO tilausrivi SET
									yhtio 		= '$kukarow[yhtio]',
									tyyppi 		= 'O',
									toimaika	= '$toimituspaiva',
									otunnus		= '$otunnus',
									tuoteno 	= '$tuoteno',
									try			= '$tuote_row[try]',
									osasto		= '$tuote_row[osasto]',
									nimitys 	= '$tuote_row[nimitys]',
									tilkpl		= '$kpl',
									yksikko		= '$tuote_row[yksikko]',
									varattu		= '$kpl',
									hinta		= '$hinta',
									laadittu	= now(),
									hyllyalue	= '$tuote_row[hyllyalue]',
									hyllynro	= '$tuote_row[hyllynro]',
									hyllytaso	= '$tuote_row[hyllytaso]',
									hyllyvali	= '$tuote_row[hyllyvali]'";
				$insertdata = mysql_query($insert_query) or pupe_error($insert_query);
			}
		}

		echo "<br>";
		echo "<font class='message'>".t("Ostoehdotus %s siirretty ostotilaukseksi %s.", "", $filunloppu, $otunnus)."</font>";
		echo "<br><br>";

		// Siirretään valmis tilaisrivit kansioon VALMIS talteen.
		//rename($kansio.$file, $kansio_valmis."DONE_".$file);
	}

	if (isset($tee) and trim($tee) == 'aja') {

		$file = "h".sprintf("%07.7s", $filu);
		$dfile = "d".sprintf("%07.7s", $filu);

		if (file_exists($kansio.$file) and file_exists($kansio.$dfile)) {

			$tiedostonimi = substr($file, 1, strlen($file)-1);
			$lines = file($kansio.$file);

			foreach ($lines as $line) {
				
				//$toimittajaytunnus 		= pupesoft_cleanstring(substr($line, 0, 7));
				$toimittajaytunnus		= '06806400'; 										// <<== POISTA TOI TOSTA ETTEI MENE SITTEN SISÄREISILLE 
				$varasto 				= pupesoft_cleanstring(substr($line, 12, 3));
				$e3ostotilausnumero 	= pupesoft_cleanstring(substr($line, 15, 7));
				$toimituspaiva 			= pupesoft_cleanstring(substr($line, 31, 8));
				$toimituspaiva 			= pupesoft_cleanstring(substr($toimituspaiva, 0, 4)."-".substr($toimituspaiva, 4, 2)."-".substr($toimituspaiva, 6, 2));
				$toivottutoimituspaiva	= pupesoft_cleanstring(substr($line, 512, 8));
				$toivottutoimituspaiva	= pupesoft_cleanstring(substr($toivottutoimituspaiva, 0, 4)."-".substr($toivottutoimituspaiva, 4, 2)."-".substr($toivottutoimituspaiva, 6, 2));
				$myyjannumero 			= pupesoft_cleanstring(substr($line, 557, 4));
			}

			if ($toivottutoimituspaiva != '0000-00-00') {
				$toimituspaiva = $toivottutoimituspaiva;
			}

			$query = "	SELECT *
						FROM toimi
						WHERE yhtio = '$kukarow[yhtio]'
						AND ytunnus = '$toimittajaytunnus'
						AND ytunnus != ''
						AND tyyppi != 'P'
						ORDER BY tunnus
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<br>";
				echo "<font class='error'>".t("Tiedostosta %s oleva toimittajan Y-tunnus on väärä tai puuttuu", "", $filu)."</font>";
				echo "<br><br>";
			}
			else {
			 	$trow = mysql_fetch_array($result);

				$vquery = "	SELECT nimi, kurssi, tunnus
							FROM valuu
							WHERE yhtio = '$kukarow[yhtio]'
							and nimi = '$trow[oletus_valkoodi]'";
				$vresult = mysql_query($vquery) or pupe_error($vquery);
				$vrow = mysql_fetch_array($vresult);

				$insquery = "	INSERT into lasku SET
								yhtio			= '$kukarow[yhtio]',
								nimi			= '$trow[nimi]',
								nimitark		= '$trow[nimitark]',
								osoite			= '$trow[osoite]',
								osoitetark		= '$trow[osoitetark]',
								postino			= '$trow[postino]',
								postitp			= '$trow[postitp]',
								maa				= '$trow[maa]',
								ytunnus			= '$trow[ytunnus]',
								ovttunnus		= '$trow[ovttunnus]',
								toimitusehto	= '$trow[toimitusehto]',
								liitostunnus	= '$trow[tunnus]',
								valkoodi		= '$trow[oletus_valkoodi]',
								vienti_kurssi	= '$vrow[kurssi]',
								toim_nimi		= '$yhtiorow[nimi]',
								toim_osoite		= '$yhtiorow[osoite]',
								toim_postino	= '$yhtiorow[postino]',
								toim_postitp	= '$yhtiorow[postitp]',
								toim_maa		= '$yhtiorow[maa]',
								toimaika		= '$toimituspaiva',
								myyja			= '$myyjannumero',
								tila			= 'O',
								viesti			= '$e3ostotilausnumero',
								laatija			= '$kukarow[kuka]',
								luontiaika		= now()";
				$otsikkoinsert = mysql_query($insquery) or pupe_error($insquery);
				$id = mysql_insert_id();

				// Luetaan tilauksen rivit
				datansisalto_e3($kansio, $kansio_valmis, $tiedostonimi, $id, $toimituspaiva);
			}

			// Siirretään valmis tilaustiedosto VALMIS-kansioon talteen.
			//	rename($kansio.$file, $kansio_valmis."DONE_".$file);
		}
		else {
			echo "<br>";
			echo "<font class='error'>".t("Ostoehdotus %s ei löydy palvelimelta tai tilausrivitiedostoa %s ei löydy palvelimelta", "", $filu, $dfile)."</font>";
			echo "<br><br>";
 		}

	}

	echo "<form action='e3ostoehdotukset.php' method='POST'>";
	echo "<input type='hidden' name='tee' value='aja'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>Anna E3-ostoehdotuksen numero</th>";
	echo "<td><input type='text' name='filu' autocomplete='off' /></td>";
	echo "<td class='back'><input type='submit' value='".t("Sisäänlue")."' /></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

?>