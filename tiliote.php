<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	$ok = 0;

	// tehdään tällänen häkkyrä niin voidaan scriptiä kutsua vaikka perlistä..
	if ($php_cli) {

		if (!isset($argv[1]) or $argv[1] != 'perl') {
			echo "Parametri väärin!!!\n";
			die;
		}

		if (!isset($argv[2]) or $argv[2] == '') {
			echo "Anna tiedosto!!!\n";
			die;
		}

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		require ("inc/connect.inc");
		require ("inc/functions.inc");

		$userfile	= trim($argv[2]);
		$filenimi	= $userfile;
		$ok 		= 1;
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Tiliotteen, LMP:n, kurssien, verkkolaskujen ja viitemaksujen käsittely</font><hr><br><br>";
	}

	// katotaan onko faili uploadattu
	if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$userfile	= $_FILES['userfile']['name'];
		$filenimi	= $_FILES['userfile']['tmp_name'];
		$ok			= 1;
	}

	if ($ok == 1) {

		// Otetaan koko aineisto muuttujaan jotta voidaan vertailla onko tämä jo koneella (koska joskus voi tulla esim kaksi LMP aineistoa samalle päivälle mutta eri sisällöllä)
		$kokoaineisto = file_get_contents($filenimi);

		$fd = fopen($filenimi, "r");

		if (!($fd)) {
			echo "<font class='message'>Tiedosto '$filenimi' ei auennut!</font>";
			exit;
		}

		$tietue = fgets($fd);

		if (substr($tietue, 0, 9) == "<SOAP-ENV" or substr($tietue, 0, 5) == "<?xml") {
			// Finvoice verkkolasku
			fclose($fd);

			require("verkkolasku-in.php");
		}
		elseif (substr($tietue, 5, 12) == "Tilivaluutan") {
			// luetaanko kursseja

			lue_kurssit($filenimi, $fd);
			fclose($fd);
		}
		elseif (substr($tietue, 0, 7) == "VK01000") {
			// luetaanko kursseja? tyyppi kaks

			lue_kurssit($filenimi, $fd, 2);
			fclose($fd);
		}
		else {
			// Tämä oli tiliote tai viiteaineisto
			require ("inc/tilinumero.inc");

			$query= "LOCK TABLE tiliotedata WRITE, yriti READ";
			$tiliotedataresult = mysql_query($query) or pupe_error($query);

			// Etsitään aineistonumero
			$query = "SELECT max(aineisto)+1 aineisto FROM tiliotedata";
			$aineistores = mysql_query($query) or pupe_error($query);
			$aineistorow = mysql_fetch_array($aineistores);

			$xlmpmaa 	= 0;
			$xtyyppi 	= 0;
			$virhe	 	= 0;
			$td_perheid = 0;
			$serc	 	= array("{", "|", "}", "[", "\\", "]", "'");
			$repl	 	= array("ä", "ö", "å", "Ä", "Ö" , "Å", " ");

			while (!feof($fd)) {
				$tietue = str_replace($serc, $repl, $tietue);

				if (substr($tietue,0,3) == 'T00' or substr($tietue,0,3) == 'T03' or substr($tietue,0,1) == '0') {

					// Konekielinen tiliote
					if (substr($tietue,0,3) == 'T00') {
						$xtyyppi 	= 1;
						$alkupvm 	= dateconv(substr($tietue,26,6));
						$loppupvm 	= dateconv(substr($tietue,32,6));
						$tilino 	= substr($tietue, 9, 14);
					}

					// Laskujen maksupalvelu LMP
					if (substr($tietue,0,3) == 'T03') {
						$xtyyppi 	= 2;
						$alkupvm	= substr($tietue, 38, 6);
						$loppupvm 	= $alkupvm;
						$tilino 	= substr($tietue, 9, 14);

						// LMP aineistojen määrä tiedostossa
						$xlmpmaa++;
					}

					// Saapuvat viitemaksut
					if (substr($tietue,0,1) == '0') {
						$xtyyppi 	= 3;
						$alkupvm	= "20".dateconv(substr($tietue,1,6));
						$loppupvm 	= $alkupvm;

						//Luetaan tilinumero seuraavalta riviltä ja siirretään pointteri takaisin nykypaikkaan
						$pointterin_paikka = ftell($fd);
						$tilino 	= fgets($fd);
						$tilino 	= substr($tilino,1,14);
						fseek($fd, $pointterin_paikka);
					}

					$query = "	SELECT *
								FROM yriti
								WHERE tilino = '$tilino'
								and kaytossa = ''";
					$yritiresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($yritiresult) != 1) {
						echo "<font class='error'> Tiliä '$tilino' ei löytynyt!</font><br>";
						$xtyyppi = 0;
						$virhe++;
					}
					else {
						$yritirow = mysql_fetch_array ($yritiresult);
					}

					// Onko tämä aineisto jo ajettu?
					$query = "	SELECT *
								FROM tiliotedata
								WHERE tilino = '$tilino'
								and alku 	 = '$alkupvm'
								and loppu 	 = '$loppupvm'
								and tyyppi 	 = $xtyyppi";
					$tiliotedatares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($tiliotedatares) > 0) {
						$tiliotedatarow = mysql_fetch_array($tiliotedatares);

						// OP-pankki toimittaa useita LMP aineistoja per päivä, eli tarkistetaan onko juuri tämä aineisto ajettu koneelle
						// Koodataan tässä vaiheessa vain LMP:lle, mutta mikään ei estä etteikö saman tsekin vois tehdä myös vaikka tiliotteille jos joku pankki toimittaisi yhden päivän otteet useassa osassa.
						if ($xtyyppi == 2) {
							// Group concatissa tulee kaikki tän päivän LMP:t
							$query = "	SELECT group_concat(tieto SEPARATOR '') kantaaineisto
										FROM tiliotedata
										WHERE tilino = '$tilino'
										and alku 	 = '$alkupvm'
										and loppu 	 = '$loppupvm'
										and tyyppi 	 = $xtyyppi";
							$tiliotedatares = mysql_query($query) or pupe_error($query);
							$tiliotedatarow = mysql_fetch_array($tiliotedatares);

							// Otetaan kaikki failissa olevat LMP:t arrayseen. (Nollaindeksissä tyhjää...)
							$distinct_aineistot = explode("T03", str_replace($serc, $repl, $kokoaineisto));

							if (trim($tiliotedatarow["kantaaineisto"]) != "") {
								for ($xlmpmaa_i = $xlmpmaa; $xlmpmaa_i < count($distinct_aineistot); $xlmpmaa_i++) {
									if (trim($distinct_aineistot[$xlmpmaa_i]) != "" and strpos(trim($tiliotedatarow["kantaaineisto"]), trim($distinct_aineistot[$xlmpmaa_i])) !== FALSE) {
										echo "<font class='error'>Tämä aineisto on jo aiemmin käsitelty!<br><br>Tili: $tilino<br>Ajalta: $alkupvm - $loppupvm<br>Yritys: $yritirow[yhtio]</font><br><br>";

										$xtyyppi=0;
										$virhe++;
									}
								}
							}

							// Tutkitaan, ettei sama aineisto ole montaa kertaa tässä failissa
							if (count($distinct_aineistot) != count(array_unique($distinct_aineistot))) {
								echo "<font class='error'>Aineisto esiintyy tiedostossa moneen kertaan.<br>Tiedosto viallinen, ei voida jatkaa, ota yhteyttä helpdeskiin!<br><br>Tili: $tilino<br>Ajalta: $alkupvm - $loppupvm<br>Yritys: $yritirow[yhtio]</font><br><br>";

								$xtyyppi=0;
								$virhe++;
							}
						}
						else {
							if ($tiliotedatarow["aineisto"] == $aineistorow["aineisto"]) {
								echo "<font class='error'>Aineisto esiintyy tiedostossa moneen kertaan.<br>Tiedosto viallinen, ei voida jatkaa, ota yhteyttä helpdeskiin!<br><br>Tili: $tilino<br>Ajalta: $alkupvm - $loppupvm<br>Yritys: $yritirow[yhtio]</font><br><br>";

								$xtyyppi=0;
								$virhe++;
							}
							else {
								echo "<font class='error'>Tämä aineisto on jo aiemmin käsitelty!<br><br>Tili: $tilino<br>Ajalta: $alkupvm - $loppupvm<br>Yritys: $yritirow[yhtio]</font><br><br>";

								$xtyyppi=0;
								$virhe++;
							}
						}
					}
				}

				if ($xtyyppi > 0 and $xtyyppi <= 3) {
					// Kirjoitetaan tiedosto kantaan
					$query = "INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tieto) values ('$yritirow[yhtio]', '$aineistorow[aineisto]', '$tilino', '$alkupvm', '$loppupvm', '$xtyyppi', '$tietue')";
					$tiliotedataresult = mysql_query($query) or pupe_error($query);
					$tiliote_id = mysql_insert_id();

					// Päivitetään perheid
					if (substr($tietue, 0, 3) != "T11" and substr($tietue, 0, 3) != "T81") {
						$td_perheid = $tiliote_id;
					}

					if ($td_perheid > 0) {
						$query = "	UPDATE tiliotedata
									SET perheid = $td_perheid
									WHERE tunnus = $tiliote_id";
						$updateperheid = mysql_query($query) or pupe_error($query);
					}
				}

				$tietue = fgets($fd);
			}

			fclose($fd);

			//Jos meillä tuli virheitä
			if ($virhe > 0) {
				echo "<font class='error'>Aineisto oli virheellinen. Sitä ei voitu tallentaa järjestelmään.</font>";

				//Poistetaan aineistot tiliotedatasta
				$query = "DELETE FROM tiliotedata WHERE aineisto ='$aineistorow[aineisto]'";
				$tiliotedataresult = mysql_query($query) or pupe_error($query);

				$query = "UNLOCK TABLES";
				$tiliotedataresult = mysql_query($query) or pupe_error($query);

				require("inc/footer.inc");
				exit;
			}

			$query = "UNLOCK TABLES";
			$tiliotedataresult = mysql_query($query) or pupe_error($query);

			// Käsitellään uudet tietueet
			$query = "	SELECT *
						FROM tiliotedata
						WHERE aineisto = '$aineistorow[aineisto]'
						ORDER BY tunnus";
			$tiliotedataresult = mysql_query($query) or pupe_error($query);

			$tilioterivilaskuri = 1;
			$tilioterivimaara	= mysql_num_rows($tiliotedataresult);

			while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {
				$tietue = $tiliotedatarow['tieto'];

				if ($tiliotedatarow['tyyppi'] == 1) {
					require("inc/tiliote.inc");
				}
				if ($tiliotedatarow['tyyppi'] == 2) {
					require("inc/LMP.inc");
				}
				if ($tiliotedatarow['tyyppi'] == 3) {
					require("inc/viitemaksut.inc");
				}

				// merkataan tämä tiliotedatarivi käsitellyksi
				$query = "	UPDATE tiliotedata
							SET kasitelty = now()
							WHERE tunnus = '$tiliotedatarow[tunnus]'";
				$updatekasitelty = mysql_query($query) or pupe_error($query);

				$tilioterivilaskuri++;
			}

			if ($xtyyppi == 1) {
				$tkesken = 0;
				$maara = $vastavienti;
				$kohdm = $vastavienti_valuutassa;

				echo "<tr><td colspan = '6'>";
				require("inc/teeselvittely.inc");
				echo "</td></tr>";
				echo "</table><br><br>";
			}

			if ($xtyyppi == 2) {
				$tkesken = 0;
				$maara = $vastavienti;
				$kohdm = $vastavienti_valuutassa;

				require("inc/teeselvittely.inc");
				echo "</table><br><br>";
			}

			if ($xtyyppi == 3) {
				require("inc/viitemaksut_kohdistus.inc");
				require("myyntires/suoritus_asiakaskohdistus_kaikki.php");
				echo "<br><br>";
			}
		}
	}

	if (!$php_cli) {
		echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>";
		echo "<table>";
		echo "	<tr>
					<th>Pankin aineisto:</th>
					<td><input type='file' name='userfile'></td>
					<td class='back'><input type='submit' value='Käsittele tiedosto'></td>
				</tr>";
		echo "</table>";

		echo "</form>";

		require("inc/footer.inc");
	}

?>