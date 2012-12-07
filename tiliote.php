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
		$palvelin2  = "";
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Tiliotteen, LMP:n, kurssien, verkkolaskujen ja viitemaksujen käsittely</font><hr><br>\n<br>\n";

		echo "<form enctype='multipart/form-data' name='sendfile' method='post'>";
		echo "<table>";
		echo "	<tr>
					<th>".t("Pankin aineisto").":</th>
					<td><input type='file' name='userfile'></td>
					<td class='back'><input type='submit' value='".t("Käsittele tiedosto")."'></td>
				</tr>";
		echo "</table>";
		echo "</form><br>\n<br>\n";

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
				-->
				</script>";

	}

	$forceta = FALSE;

	// katotaan onko faili uploadattu
	if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$userfile	= $_FILES['userfile']['name'];
		$filenimi	= $_FILES['userfile']['tmp_name'];
		$ok			= 1;
	}
	elseif (isset($virhe_file) and file_exists("/tmp/".basename($virhe_file))) {
		$userfile	= "/tmp/".basename($virhe_file);
		$filenimi	= "/tmp/".basename($virhe_file);
		$ok			= 1;
		$forceta 	= TRUE;
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
			$query= "LOCK TABLE tiliotedata WRITE, yriti READ, yhtio READ";
			$tiliotedataresult = pupe_query($query);

			// Etsitään aineistonumero
			$query = "SELECT max(aineisto)+1 aineisto FROM tiliotedata";
			$aineistores = pupe_query($query);
			$aineistorow = mysql_fetch_assoc($aineistores);

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
					$yritiresult = pupe_query($query);

					if (mysql_num_rows($yritiresult) != 1) {
						// Kokeillaan 14 vikalla merkillä (Virossa esim on 18 merkkisiä tilinumeroita)
						$query = "	SELECT *
									FROM yriti
									WHERE right(tilino,14) = '$tilino'
									and kaytossa = ''";
						$yritiresult = pupe_query($query);
					}

					if (mysql_num_rows($yritiresult) != 1) {
						echo "<font class='error'>".t("Tiliä %s ei löytynyt", "", $tilino)."!</font><br>\n";
						$xtyyppi = 0;
						$virhe++;
					}
					else {
						$yritirow = mysql_fetch_assoc($yritiresult);

						// Korjataan vielä tämä jos ne jostain syystä on eri
						$tilino = $yritirow['tilino'];

						// Setataan kukarow-yhtiö
						$kukarow["yhtio"] = $yritirow["yhtio"];
					}

					$query = "	SELECT myyntireskontrakausi_alku, myyntireskontrakausi_loppu, ostoreskontrakausi_alku, ostoreskontrakausi_loppu, tilikausi_alku, tilikausi_loppu
								FROM yhtio
								WHERE yhtio = '{$yritirow['yhtio']}'";
					$yhtio_tsekres = pupe_query($query);
					$yhtio_tsekrow = mysql_fetch_assoc($yhtio_tsekres);

					//vertaillaan tilikauteen
					list($vv1,$kk1,$pp1) = explode("-", $yhtio_tsekrow["myyntireskontrakausi_alku"]);
					list($vv2,$kk2,$pp2) = explode("-", $yhtio_tsekrow["myyntireskontrakausi_loppu"]);

					$myrealku  = (int) date('Ymd', mktime(0,0,0,$kk1,$pp1,$vv1));
					$myreloppu = (int) date('Ymd', mktime(0,0,0,$kk2,$pp2,$vv2));

					list($vv1,$kk1,$pp1) = explode("-", $yhtio_tsekrow["ostoreskontrakausi_alku"]);
					list($vv2,$kk2,$pp2) = explode("-", $yhtio_tsekrow["ostoreskontrakausi_loppu"]);

					$oresalku  = (int) date('Ymd', mktime(0,0,0,$kk1,$pp1,$vv1));
					$oresloppu = (int) date('Ymd', mktime(0,0,0,$kk2,$pp2,$vv2));

					list($vv1,$kk1,$pp1) = explode("-", $yhtio_tsekrow["tilikausi_alku"]);
					list($vv2,$kk2,$pp2) = explode("-", $yhtio_tsekrow["tilikausi_loppu"]);

					$tikaalku  = (int) date('Ymd', mktime(0,0,0,$kk1,$pp1,$vv1));
					$tikaloppu = (int) date('Ymd', mktime(0,0,0,$kk2,$pp2,$vv2));

					// Onko tämä aineisto jo ajettu?
					$query = "	SELECT *
								FROM tiliotedata
								WHERE tilino = '$tilino'
								and alku 	 = '$alkupvm'
								and loppu 	 = '$loppupvm'
								and tyyppi 	 = $xtyyppi";
					$tiliotedatares = pupe_query($query);

					if (mysql_num_rows($tiliotedatares) > 0) {
						$tiliotedatarow = mysql_fetch_assoc($tiliotedatares);

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
							$tiliotedatares = pupe_query($query);
							$tiliotedatarow = mysql_fetch_assoc($tiliotedatares);

							// Otetaan kaikki failissa olevat LMP:t arrayseen. (Nollaindeksissä tyhjää...)
							$distinct_aineistot = explode("T03", str_replace($serc, $repl, $kokoaineisto));

							if (trim($tiliotedatarow["kantaaineisto"]) != "") {
								for ($xlmpmaa_i = $xlmpmaa; $xlmpmaa_i < count($distinct_aineistot); $xlmpmaa_i++) {
									if (trim($distinct_aineistot[$xlmpmaa_i]) != "" and strpos(trim($tiliotedatarow["kantaaineisto"]), trim($distinct_aineistot[$xlmpmaa_i])) !== FALSE) {
										echo "<font class='error'>VIRHE: Tämä aineisto on jo aiemmin käsitelty!<br>\n<br>\nTili: $tilino<br>\nAjalta: $alkupvm - $loppupvm<br>\nYritys: $yritirow[yhtio]</font><br>\n<br>\n";

										$xtyyppi=0;
										$virhe++;
									}
								}
							}

							// Tutkitaan, ettei sama aineisto ole montaa kertaa tässä failissa
							if (count($distinct_aineistot) != count(array_unique($distinct_aineistot))) {
								echo "<font class='error'>VIRHE: Aineisto esiintyy tiedostossa moneen kertaan.<br>\nTiedosto viallinen, ei voida jatkaa, ota yhteyttä helpdeskiin! (Tili: $tilino / Pvm: $alkupvm / Yhtiö: $yritirow[yhtio])</font><br>\n<br>\n";

								$xtyyppi=0;
								$virhe++;
							}
						}
						else {
							if ($tiliotedatarow["aineisto"] == $aineistorow["aineisto"]) {
								echo "<font class='error'>VIRHE: Aineisto esiintyy tiedostossa moneen kertaan.<br>\nTiedosto viallinen, ei voida jatkaa, ota yhteyttä helpdeskiin! (Tili: $tilino / Pvm: $alkupvm / Yhtiö: $yritirow[yhtio])</font><br>\n";

								$xtyyppi=0;
								$virhe++;
							}
							elseif (!$forceta)  {
								echo "<font class='error'>VIRHE: Tämä aineisto on jo aiemmin käsitelty! (Tili: $tilino / Pvm: $alkupvm / Yhtiö: $yritirow[yhtio])</font><br>\n<br>\n";

								if (!$php_cli) {
									list($usec, $sec) = explode(' ', microtime());
									mt_srand((float) $sec + ((float) $usec * 100000));
									$tmpfile = md5(uniqid(mt_rand(), true)).".txt";

									file_put_contents("/tmp/".$tmpfile, $kokoaineisto);

									echo "<form method='post' onSubmit='return verify();'>";
									echo "<input type='hidden' name='virhe_file' value='$tmpfile'>
											<input type='submit' value='".t("Käsittele aineisto vaikka kyseisen päivän/tilin aineisto on jo käsitelty")."'>";
									echo "</form><br>\n<br>\n";
								}

								$xtyyppi=0;
								$virhe++;
							}
						}
					}
				}

				// Tsekataan tiliotteen duplikaatit ja tsekataan ettei kirjata suljetuille kausille
				if ($xtyyppi == 1 and substr($tietue, 0, 3) == "T10") {

					// VVKKPP
					$tsekpvm = (int) "20".substr($tietue, 30, 6);

					if ($tsekpvm < $oresalku or $tsekpvm > $oresloppu or $tsekpvm < $myrealku or $tsekpvm > $myreloppu or $tsekpvm < $tikaalku or $tsekpvm > $tikaloppu) {
						echo "<font class='error'>VIRHE: Aineistossa on tapahtuma suljetulle kaudelle! (Tili: $tilino / Pvm: $tsekpvm / Yhtiö: $yritirow[yhtio])</font><br>\n";

						$xtyyppi=0;
						$virhe++;
					}

					$arkistotunnari = substr($tietue, 12, 18);
					$taso = substr($tietue, 187, 1);

					if (($taso == ' ') and ((!is_numeric($arkistotunnari) and trim($arkistotunnari) != "") or (is_numeric($arkistotunnari) and (int) $arkistotunnari != 0))) {
						// Katsotaan löytyykö tällä tunnuksella suoritus
						$query = "	SELECT alku
									FROM tiliotedata
									WHERE yhtio	= '$yritirow[yhtio]'
									and tilino 	= '$tilino'
									and tyyppi 	= $xtyyppi
									and tieto	= '$tietue'
									and substring(tieto, 13, 18) = '$arkistotunnari'";
						$vchkres = pupe_query($query);

						if (mysql_num_rows($vchkres) > 0) {
							$vchkrow = mysql_fetch_assoc($vchkres);

							echo "<font class='error'>VIRHE: Tiliotetapahtuma arkistointitunnuksella: '$arkistotunnari' löytyy jo järjestelmästä! (Tili: $tilino / Pvm: $vchkrow[alku] / Yhtiö: $yritirow[yhtio])</font><br>\n";

							$xtyyppi=0;
							$virhe++;
						}
					}
				}

				// Tsekataan LMP-aineiston duplikaatit ja tsekataan ettei kirjata suljetuille kausille
				if ($xtyyppi == 2 and substr($tietue, 0, 3) == "T10") {
					// VVKKPP
					$taso = substr($tietue, 187, 1);
					$tsekpvm = substr($tietue, 42, 6);

					if ($taso == '0') $turvapvm = $tsekpvm; // Osuuspankki ei lähetä päiväystä kuin täällä
					if ($pvm == '000000') $tsekpvm = $turvapvm;

					if ($tsekpvm < $oresalku or $tsekpvm > $oresloppu) {
						echo "<font class='error'>VIRHE: Aineistossa on tapahtuma suljetulle kaudelle! (Tili: $tilino / Pvm: $tsekpvm / Yhtiö: $yritirow[yhtio])</font><br>\n";

						$xtyyppi=0;
						$virhe++;
					}
				}

				// Tsekataan, ettei mene duplikaattiviitesuortiuksia ja tsekataan ettei kirjata suljetuille kausille
				if ($xtyyppi == 3 and substr($tietue, 0, 1) == "3") {

					// VVKKPP
					$tsekpvm = (int) "20".substr($tietue, 15, 6);

					if ($tsekpvm < $myrealku or $tsekpvm > $myreloppu) {
						echo "<font class='error'>VIRHE: Aineistossa on viitesuoritus suljetulle kaudelle! (Tili: $tilino / Pvm: $tsekpvm / Yhtiö: $yritirow[yhtio])</font><br>\n";

						$xtyyppi=0;
						$virhe++;
					}

					$arkistotunnari = substr($tietue, 27, 16);

					if ((!is_numeric($arkistotunnari) and trim($arkistotunnari) != "") or (is_numeric($arkistotunnari) and (int) $arkistotunnari != 0)) {
						// Katsotaan löytyykö tällä tunnuksella suoritus
						$query = "	SELECT alku
									FROM tiliotedata
									WHERE yhtio	= '$yritirow[yhtio]'
									and tilino 	= '$tilino'
									and tyyppi 	= $xtyyppi
									and tieto	= '$tietue'
									and substring(tieto, 28, 16) = '$arkistotunnari'";
						$vchkres = pupe_query($query);

						if (mysql_num_rows($vchkres) > 0) {
							$vchkrow = mysql_fetch_assoc($vchkres);

							echo "<font class='error'>VIRHE: Viitesuoritus arkistointitunnuksella: '$arkistotunnari' löytyy jo järjestelmästä! (Tili: $tilino / Pvm: $vchkrow[alku] / Yhtiö: $yritirow[yhtio])</font><br>\n";

							$xtyyppi=0;
							$virhe++;
						}
					}
				}

				if ($xtyyppi > 0 and $xtyyppi <= 3) {
					// Kirjoitetaan tiedosto kantaan
					$query = "INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tieto) values ('$yritirow[yhtio]', '$aineistorow[aineisto]', '$tilino', '$alkupvm', '$loppupvm', '$xtyyppi', '$tietue')";
					$tiliotedataresult = pupe_query($query);
					$tiliote_id = mysql_insert_id();

					// Päivitetään perheid
					if (substr($tietue, 0, 3) != "T11" and substr($tietue, 0, 3) != "T81") {
						$td_perheid = $tiliote_id;
					}

					if ($td_perheid > 0) {
						$query = "	UPDATE tiliotedata
									SET perheid = $td_perheid
									WHERE tunnus = $tiliote_id";
						$updateperheid = pupe_query($query);
					}
				}

				$tietue = fgets($fd);
			}

			fclose($fd);

			//Jos meillä tuli virheitä
			if ($virhe > 0) {
				echo "<br>\n<font class='error'>".t("Aineistoa ei tallennettu järjestelmään").".</font>";

				//Poistetaan aineistot tiliotedatasta
				$query = "DELETE FROM tiliotedata WHERE aineisto ='$aineistorow[aineisto]'";
				$tiliotedataresult = pupe_query($query);

				$query = "UNLOCK TABLES";
				$tiliotedataresult = pupe_query($query);

				require("inc/footer.inc");
				exit;
			}

			$query = "UNLOCK TABLES";
			$tiliotedataresult = pupe_query($query);

			// Käsitellään uudet tietueet
			$query = "	SELECT *
						FROM tiliotedata
						WHERE aineisto = '$aineistorow[aineisto]'
						ORDER BY tunnus";
			$tiliotedataresult = pupe_query($query);

			$tilioterivilaskuri = 1;
			$tilioterivimaara	= mysql_num_rows($tiliotedataresult);

			while ($tiliotedatarow = mysql_fetch_assoc($tiliotedataresult)) {
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
				$updatekasitelty = pupe_query($query);

				$tilioterivilaskuri++;
			}

			if ($xtyyppi == 1) {
				$tkesken = 0;
				$maara = $vastavienti;
				$kohdm = $vastavienti_valuutassa;

				echo "<tr><td colspan = '6'>";
				require("inc/teeselvittely.inc");
				echo "</td></tr>";
				echo "</table><br>\n<br>\n";
			}

			if ($xtyyppi == 2) {
				$tkesken = 0;
				$maara = $vastavienti;
				$kohdm = $vastavienti_valuutassa;

				require("inc/teeselvittely.inc");
				echo "</table><br>\n<br>\n";
			}

			if ($xtyyppi == 3) {

				# Tässä tarvitaan kukarow[yhtio], joten ajetaan tämä kaikille firmoille
				$query    = "SELECT yhtio from yhtio";
				$yhtiores = pupe_query($query);

				while ($yhtiorow = mysql_fetch_assoc($yhtiores)) {

					// Setataan yhtiorow
					$yhtiorow = hae_yhtion_parametrit($yhtiorow['yhtio']);

					// Setataan kukarow-yhtiö
					$kukarow["yhtio"] = $yhtiorow["yhtio"];

					require("inc/viitemaksut_kohdistus.inc");
					require("myyntires/suoritus_asiakaskohdistus_kaikki.php");
				}

				echo "<br>\n<br>\n";
			}
		}
	}

	if (!$php_cli) {
		require("inc/footer.inc");
	}

?>