<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	if ($php_cli) {

		date_default_timezone_set('Europe/Helsinki');

		if (!isset($argv[1]) or $argv[1] == '') {
			echo "Anna yhtiö!!!\n";
			die;
		}

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");

		// hmm.. jännää
		$kukarow['yhtio'] = $argv[1];

		//Pupeasennuksen root
		$pupe_root_polku = dirname(dirname(__FILE__));

		$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

		if ($yhtiorow["yhtio"] == "") {
			die ("Yhtiö $kukarow[yhtio] ei löydy!");
		}

		if (!isset($e3_params[$yhtiorow["yhtio"]]["ekansio"]) or !is_dir($e3_params[$yhtiorow["yhtio"]]["ekansio"])) {
			echo "VIRHE: 'e3_ehdotuskansio' puuttuu!";
			exit;
		}
		else {
			$e3_ehdotuskansio = $e3_params[$yhtiorow["yhtio"]]["ekansio"];
		}

		$filet = array();

		// Avataan kansio
		if ($handle = opendir($e3_ehdotuskansio)) {
		    while (false !== ($file = readdir($handle))) {
				// Napataan headerfilet arrayseen
				if (substr($file, 0, 1) == "h") {
					$filet[] = $file;
				}
		    }

		    closedir($handle);
		}

		if (count($filet) > 0) {
			$tee = "aja";
		}

	}
	else {
		require('../inc/parametrit.inc');

		echo "<font class='head'>".t("E3-ostoehdotuksen sisäänluku")."</font><hr>";
	}

	// jos meillä on lock-file ja se on alle 5 minuuttia vanha
	if (file_exists("/tmp/##e3ehdotukset.lock") and mktime()-filemtime("/tmp/##e3ehdotukset.lock") < 900) {
		#echo "E3-ostoehdotuksen sisäänluku käynnissä, odota hetki!";
	}
	elseif (file_exists("/tmp/##e3ehdotukset.lock") and mktime()-filemtime("/tmp/##e3ehdotukset.lock") >= 900) {
		echo "VIRHE: E3-ostoehdotuksen sisäänluku jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: E3-ostoehdotuksen sisäänluku jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##e3ehdotukset.lock");

		function datansisalto_e3($e3_ehdotuskansio, $dfile, $otunnus, $toimituspaiva) {

			global $yhtiorow, $kukarow;

			$laskuquery = " SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$otunnus'";
			$lasku_result = mysql_query($laskuquery) or pupe_error($laskuquery);
			$laskurow = mysql_fetch_array($lasku_result);

			$lines = file($e3_ehdotuskansio."/".$dfile);

			foreach ($lines as $line) {

				$tuoteno 		= pupesoft_cleanstring(substr($line, 12, 17));
				$varasto	 	= pupesoft_cleanstring(substr($line, 30, 3));
				$tuotenimi	 	= pupesoft_cleanstring(substr($line, 33, 34));
				$kpl 			= pupesoft_cleannumber(substr($line, 68, 7));
				//$hinta 		= pupesoft_cleannumber(substr($line, 91, 13));
				//$hinta 		= $hinta / 10000;

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

					list($hinta,$netto,$ale,) = alehinta_osto($laskurow, $tuote_row, $kpl);

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
										netto		= '$netto',
										ale1		= '$ale[ale1]',
										ale2		= '$ale[ale2]',
										ale3		= '$ale[ale3]',
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

			// Siirretään done kansioon
			rename($e3_ehdotuskansio."/".$dfile, $e3_ehdotuskansio."/done/".$dfile);
		}

		if (isset($tee) and trim($tee) == 'aja') {
			foreach ($filet as $filu) {

				$toimittajaytunnus 			= "";
				$e3ostotilausnumero 		= "";
				$tuoteno					= "";
				$varasto 					= "";
				$kpl 						= "";
				$toimituspaiva 				= "";
				$toivotttutoimituspaiva 	= "";
				$myyjannumero 				= "";

				$hfile = "h".sprintf("%07.7s", preg_replace("/[^0-9]/", "", $filu));
				$dfile = "d".sprintf("%07.7s", preg_replace("/[^0-9]/", "", $filu));

				if (file_exists($e3_ehdotuskansio."/".$hfile) and file_exists($e3_ehdotuskansio."/".$dfile)) {

					$lines = file($e3_ehdotuskansio."/".$hfile);

					foreach ($lines as $line) {

						$toimittajaytunnus 		= pupesoft_cleanstring(substr($line, 0, 7));
						//$toimittajaytunnus	= '06806400'; 										// <<== POISTA TOI TOSTA ETTEI MENE SITTEN SISÄREISILLE
						$varasto 				= pupesoft_cleanstring(substr($line, 12, 3));
						$e3ostotilausnumero 	= pupesoft_cleanstring(substr($line, 15, 7));
						$toimituspaiva 			= pupesoft_cleanstring(substr($line, 31, 8));
						$toimituspaiva 			= pupesoft_cleanstring(substr($toimituspaiva, 0, 4)."-".substr($toimituspaiva, 4, 2)."-".substr($toimituspaiva, 6, 2));
						$toivottutoimituspaiva	= pupesoft_cleanstring(substr($line, 512, 8));
						$toivottutoimituspaiva	= pupesoft_cleanstring(substr($toivottutoimituspaiva, 0, 4)."-".substr($toivottutoimituspaiva, 4, 2)."-".substr($toivottutoimituspaiva, 6, 2));
						$myyjannumero 			= pupesoft_cleanstring(substr($line, 557, 4));
					}

					$kquery = "	SELECT tunnus
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]'
								and myyja = '$myyjannumero'
								ORDER BY tunnus desc
								LIMIT 1";
					$kresult = mysql_query($kquery) or pupe_error($kquery);

					if (mysql_num_rows($kresult) == 1) {
						$myyja = mysql_fetch_array($kresult);
						$myyjannumero = $myyja['tunnus'];
					}
					else {
						$myyjannumero = '';
					}

					if ($toivottutoimituspaiva != '0000-00-00') {
						$toimituspaiva = $toivottutoimituspaiva;
					}

					$query = "	SELECT *
								FROM toimi
								WHERE yhtio = '$kukarow[yhtio]'
								AND toimittajanro = '$toimittajaytunnus'
								AND toimittajanro != ''
								AND toimittajanro != '0'
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
										comments		= '$e3ostotilausnumero',
										laatija			= 'E3',
										luontiaika		= now()";
						$otsikkoinsert = mysql_query($insquery) or pupe_error($insquery);
						$id = mysql_insert_id();

						// Luetaan tilauksen rivit
						datansisalto_e3($e3_ehdotuskansio, $dfile, $id, $toimituspaiva);
					}

					// Siirretään valmis tilaustiedosto VALMIS-kansioon talteen.
					rename($e3_ehdotuskansio."/".$hfile, $e3_ehdotuskansio."/done/".$hfile);
				}
				else {
					echo "<br>";
					echo "<font class='error'>".t("Ostoehdotus %s ei löydy palvelimelta tai tilausrivitiedostoa %s ei löydy palvelimelta", "", $filu, $dfile)."</font>";
					echo "<br><br>";
		 		}
			}
		}

		unlink("/tmp/##e3ehdotukset.lock");
	}

	if (!$php_cli) {
		echo "<form action='e3ostoehdotukset.php' method='POST'>";
		echo "<input type='hidden' name='tee' value='aja'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>Anna E3-ostoehdotuksen numero</th>";
		echo "<td><input type='text' name='filet[]' autocomplete='off' /></td>";
		echo "<td class='back'><input type='submit' value='".t("Sisäänlue")."' /></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}

?>