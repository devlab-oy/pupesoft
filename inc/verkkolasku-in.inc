<?php

	// otetaan sisään $file jossa on luettavan filen nimi polkuineen
	// palautetaan virheviesti jos tapahtuu jotain, muuten palautetaan tyhjää

	if (!function_exists("verkkolasku_in")) {
		function verkkolasku_in ($file, $luolasku = TRUE) {
			global $kukarow, $yhtiorow, $toim_asiakkaantiedot, $xmlstr, $liitetaanko_editilaus_laskulle_hakemisto;

			if (!is_file($file)) {
				echo ("Tiedostoa $file ei löydy!\n");
				return ("Tiedostoa $file ei löydy!");
			}

			// luetaan file muuttujaan
			$xmlstr = file_get_contents($file);

			if ($xmlstr === FALSE) {
				echo ("Tiedosto $file luku epäonnistui!\n");
				return ("Tiedosto $file luku epäonnistui!");
			}

			// luetaan sisään xml
			$xml = simplexml_load_string($xmlstr);

			if ($xml === FALSE) {
				echo ("Tiedosto $file ei ole validi XML!\n");
				return ("Tiedosto $file ei ole validi XML!");
			}

			require_once("inc/functions.inc");

			// Katsotaan mitä aineistoa käpistellään
			if (strpos($file, "finvoice-") !== false) {
				require("inc/verkkolasku-in-finvoice.inc");
			}
			else {
				require("inc/verkkolasku-in-pupevoice.inc");
			}


			// Nämä muuttujat kuuluisi olla setattuna:
			/*
			echo "\n\nLASKUNTIEDOT:\n";
			echo "01: ".$yhtio."\n";
			echo "02: ".$verkkotunnus_vas."\n";
			echo "03: ".$laskun_tyyppi."\n";
			echo "04: ".$laskun_numero."\n";
			echo "05: ".$laskun_ebid."\n";
			echo "06: ".$laskun_paiva."\n";
			echo "07: ".$laskun_erapaiva."\n";
			echo "08: ".$laskuttajan_ovt."\n";
			echo "09: ".$laskuttajan_nimi."\n";
			echo "10: ".$laskuttajan_vat."\n";
			echo "11: ".$laskun_pankkiviite."\n";
			echo "12: ".$laskun_asiakastunnus."\n";
			echo "13: ".$laskun_summa_eur."\n";
			echo "14: ".$laskun_tilausviite."\n";
			echo "15: ".$kauttalaskutus."\n";
			echo "16: ".$toim_asiakkaantiedot["toim_ovttunnus"]."\n";
			echo "17: ".$toim_asiakkaantiedot["ytunnus"]."\n";
			echo "18: ".$toim_asiakkaantiedot["nimi"]."\n";
			echo "19: ".$toim_asiakkaantiedot["osoite"]."\n";
			echo "20: ".$toim_asiakkaantiedot["postino"]."\n";
			echo "21: ".$toim_asiakkaantiedot["postitp"]."\n";
			echo "22: ".$ostaja_asiakkaantiedot["toim_ovttunnus"]."\n";
			echo "23: ".$ostaja_asiakkaantiedot["ytunnus"]."\n";
			echo "24: ".$ostaja_asiakkaantiedot["nimi"]."\n";
			echo "25: ".$ostaja_asiakkaantiedot["osoite"]."\n";
			echo "26: ".$ostaja_asiakkaantiedot["postino"]."\n";
			echo "27: ".$ostaja_asiakkaantiedot["postitp"]."\n";
			*/

			// generic error messagen alku
			$return  = "Tiedosto: $file\n";
			$return .= "Lähettäjä: $laskuttajan_nimi\n";
			$return .= "Laskunro: $laskun_numero\n";
			$return .= "Päivä: $laskun_paiva\n";
			$return .= "Summa: $laskun_summa_eur\n";
			$return .= "Viite: $laskun_pankkiviite\n";
			$komm = "";

			// $yhtio:ssa on nyt laskun VASTAANOTTAJAN ovt-tunnus ja $verkkotunnus_vas:ssa on VASTAANOTTAJAN verkkolaskutunnus
			$verkkotunnus_vas 	= trim($verkkotunnus_vas);
			$yhtio	 			= trim($yhtio);

			// Itella lehettää joskus verkkotunnusta todella kummallisessa muodossa.. kokeillaan arvailla oikein
			$pos = strpos($verkkotunnus_vas,"@");

			if ($pos !== FALSE) {
				$verkkotunnus_vas = substr($verkkotunnus_vas, 0, $pos); // otetaan info ekaan ättämerkkiin asti
			}

			if ($verkkotunnus_vas != "") {
				// 1 kokeillaan löytää yritys verkkotunnuksella
				$query = "SELECT * FROM yhtion_parametrit WHERE verkkotunnus_vas = '$verkkotunnus_vas'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$paramrow = mysql_fetch_array($result);

					// löydettiin parametreistä oikea yhtiö haetaan yhtiorow
					$query = "SELECT * FROM yhtio WHERE yhtio = '$paramrow[yhtio]'";
					$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

					if (mysql_num_rows($result) == 1) {
						$yhtiorow = mysql_fetch_array($result);
					}
				}
			}

			if (!isset($yhtiorow) and $verkkotunnus_vas != "") {
				// 2 etsitään vastaanottavaa yritystä ovt-tunnuksella..
				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$verkkotunnus_vas'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $verkkotunnus_vas != "") {
				// 3 etsitään vastaanottavaa yritystä ovt-tunnuksella ilman tarkenteita
				$oyhtio1 = substr($verkkotunnus_vas, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$oyhtio1'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $verkkotunnus_vas != 0) {
				// 4 etsitään vastaanottavaa yritystä ytunnuksella..
				$yyhtio1 = substr(str_replace("0037", "", $verkkotunnus_vas), 0, 8); // mahdollisella etunollalla
				$yyhtio2 = (int) $yyhtio1; // ilman etunollaa

				$query = "SELECT * FROM yhtio WHERE ytunnus in ('$yyhtio1', '$yyhtio2')";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 5 etsitään vastaanottavaa yritystä ovt-tunnuksella..
				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$yhtio'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "") {
				// 6 etsitään vastaanottavaa yritystä IBAN-tilinumerolla
				$query = "SELECT * FROM yriti WHERE kaytossa != 'E' and iban = '$yhtio'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$ibanrow = mysql_fetch_array($result);

					$query = "SELECT * FROM yhtio WHERE yhtio = '$ibanrow[yhtio]'";
					$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

					if (mysql_num_rows($result) == 1) {
						$yhtiorow = mysql_fetch_array($result);
					}
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 7 etsitään vastaanottavaa yritystä ovt-tunnuksella ilman tarkenteita
				$oyhtio2 = substr($yhtio, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$oyhtio2'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 8 etsitään vastaanottavaa yritystä ytunnuksella..
				$yyhtio3 = substr(str_replace("0037", "", $yhtio), 0, 8); // mahdollisella etunollalla
				$yyhtio4 = (int) $yyhtio3; // ilman etunollaa

				$query = "SELECT * FROM yhtio WHERE ytunnus in ('$yyhtio3', '$yyhtio4')";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 9 viimeinen hätäkeino, katotaan onko joku lisätieto ongelma
				$query = "SELECT * FROM yhtio WHERE ovttunnus like '$yhtio%'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_array($result);
				}
			}

			if (!isset($yhtiorow) and $luolasku === TRUE) {
				// ei löydetty VASTAANOTTAVAA yhtiötä, lähetetään meili kaikille tän serverin admineille!
				$query = "SELECT distinct alert_email, postittaja_email FROM yhtion_parametrit WHERE alert_email != '' and postittaja_email != ''";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				$return .= "\nEpäonnistumisen syy: Laskun vastaanottajaa ei löytynyt järjestelmästä $_SERVER[SERVER_ADDR]!\n\n";
				$return .= "Etsittiin tiedoilla:\n";
				$return .= "Verkkotunnus: $verkkotunnus_vas\n";
				$return .= "Ovt: $yhtio $oyhtio1 $oyhtio2\n";
				$return .= "Ytunnus: $yyhtio1 $yyhtio2 $yyhtio3 $yyhtio4\n\n";

				while ($yhtiorow = mysql_fetch_array($result)) {
					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
				}

				return ($return);
			}
			elseif(isset($yhtiorow)) {

				$return .= "Lasku yhtiölle: $yhtiorow[nimi] ($yhtiorow[yhtio])\n";

				// Haetaan yhtiön parametrit
				$query = "	SELECT *
							FROM yhtion_parametrit
							WHERE yhtio='$yhtiorow[yhtio]'";
				$result = mysql_query($query)
						or die ("Kysely ei onnistu yhtio $query");

				if (mysql_num_rows($result) == 1) {
					$yhtion_parametritrow = mysql_fetch_array($result);
					// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
					foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
						$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
					}
				}
			}

			// $yhtiossa on nyt yhtio.yhtio
			$yhtio = $yhtiorow['yhtio'];

			// Perusta lasku
			$summa		= $laskun_summa_eur;
			$valkoodi	= 'EUR';

			if ($laskun_tyyppi == "381" and $summa > 0) {
				$summa = $summa * -1;
			}

			$tpp = (int) substr($laskun_paiva,6,2);
			$tpk = (int) substr($laskun_paiva,4,2);
			$tpv = (int) substr($laskun_paiva,0,4);
			$erp = (int) substr($laskun_erapaiva,6,2);
			$erk = (int) substr($laskun_erapaiva,4,2);
			$erv = (int) substr($laskun_erapaiva,0,4);

			$viite  = $laskun_pankkiviite;
			$ebid   = $laskun_ebid;
			$selite = t("Laskunumero").": ".$laskun_numero;

			unset($trow);

			// $laskuttajan_ovt --> voi olla ovttunnus, ytunnus, tai vatnumero
			// $laskuttajan_vat --> voisi olla vatnumero
			// $laskuttajan_nimi --> voisi olla laskuttajan nimi
			$laskuttajan_ovt  = trim($laskuttajan_ovt);
			$laskuttajan_vat  = trim($laskuttajan_vat);
			$laskuttajan_nimi = trim($laskuttajan_nimi);

			if (!isset($trow) and stripos($laskuttajan_nimi, "rum Oy Ab / Arwidson Automotive") !== FALSE) {
				// 0 eleganttia
				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '$yhtio'
							and nimi = 'Örum Oy Ab / Arwidson Automotive'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if ($laskuttajan_ovt != "") {
				// 1 etsitään toimittaja ovttunnuksella
				$query  = "	SELECT *
							FROM toimi
							WHERE ovttunnus = '$laskuttajan_ovt'
							and ovttunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if (!isset($trow) and $laskuttajan_ovt != "") {
				// 2 etsitään toimittaja ovt-tunnuksella ilman tarkenteita
				$yovt = substr($laskuttajan_ovt, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

				$query  = "	SELECT *
							FROM toimi
							WHERE ovttunnus = '$yovt'
							and ovttunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if (!isset($trow) and $laskuttajan_vat != "") {
				// 3 etsitään toimittaja vat-numerolla
				$query  = "	SELECT *
							FROM toimi
							WHERE ovttunnus = '$laskuttajan_vat'
							and ovttunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if (!isset($trow) and $laskuttajan_vat != "") {
				// 4 etsitään toimittaja vat-numerolla
				$query  = "	SELECT *
							FROM toimi
							WHERE ytunnus = '$laskuttajan_vat'
							and ytunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if (!isset($trow) and $laskuttajan_ovt != "") {
				// 5 etsitään toimittaja ytunnuksella
				$yovt1 = substr(str_replace("0037", "", $laskuttajan_ovt), 0, 8); // mahdollisella etunollalla
				$yovt2 = (int) $yovt1; // ilman etunollaa

				$query  = "	SELECT *
							FROM toimi
							WHERE ytunnus in ('$yovt1', '$yovt2')
							and ytunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if (!isset($trow) and $laskuttajan_vat != "") {
				$intvat = preg_replace("/[^0-9]/", "", $laskuttajan_vat);
				$intvat2 = (int) $intvat; // ilman etunollaa

				// 6 etsitään toimittaja vat-numerolla ilman FI-etuliitettä
				$query  = "	SELECT *
							FROM toimi
							WHERE ytunnus in ('$intvat', '$intvat2')
							and ytunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			if (!isset($trow) and $laskuttajan_nimi != "") {
				// 7 etsitään toimittaja nimellä
				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '$yhtio'
							and nimi = '$laskuttajan_nimi'
							and nimi not in ('','0')
							and tyyppi != 'P'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_array($result);
				}
			}

			// Tätä voi ajaa myös verkkolaskuvirheetohjelmasta silloin $luolasku on FALSE ja lopetetaan tässä vaiheessa
			if ($luolasku === TRUE) {
				if (!isset($trow)) {
					$return .= "\nEpäonnistumisen syy: Emme löytäneet toimittajaa yhtiöltä!\n\n";
					$return .= "Etsittiin tiedoilla:\n";
					$return .= "Nimi: $laskuttajan_nimi\n";
					$return .= "Ovt: $laskuttajan_ovt $yovt $yovt1 $yovt2\n";
					$return .= "Vat: $laskuttajan_vat $intvat $intvat2\n\n";

					$urlmain = "/view/ebs-2.0/$yhtiorow[verkkotunnus_vas]/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=".gmdate("YmdHis")."Z&VERSION=ebs-2.0";
					$digest	 = md5($urlmain . "&" . $yhtiorow['verkkosala_vas']);
					$url	 = "http://www.verkkolasku.net".$urlmain."&DIGEST=$digest";

					$return .= "Laskun kuva: $url\n";

					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

					return ($return);
				}
				else {
					$return .= "Toimittaja: $trow[ytunnus]/$trow[ovttunnus] - $trow[nimi]\nEpäonnistumisen syy: ";
				}

				// katotaan pitääks vaihtaa oletus_vientiä
				if ($trow['oletus_vienti'] != 'C' and $trow['oletus_vienti'] != 'F' and $trow['oletus_vienti'] != 'I') {

					// katotaan löydettäskö tälle vähän ostorivejä
					$chkriviok = 0;

					for ($i=0; $i<count($rtuoteno); $i++) {

						$ostotunnus = (int) $rtuoteno[$i]["tilaajanrivinro"];

						// ei tehdä turhaa hakua jos meillä ei ole tullu rivitunnusta
						if ($ostotunnus != 0) {
							$chkquery = "	SELECT *
											FROM tilausrivi
											where yhtio = '$yhtio' and
											tunnus = '$ostotunnus' and
											tyyppi = 'O'";
							$checkostot = mysql_query($chkquery) or die ("$chkquery<br><br>".mysql_error());

							if (mysql_num_rows($checkostot) == 1) {
								$chkriviok++;
							}
						}
					}

					// löydettiin mätchi ostoriveiltä, laitetaan vaihto-omaisuus täppi päälle
					if ($chkriviok != 0 ) {
						if (strtoupper($trow["maa"]) == strtoupper($yhtiorow["maa"])) {
							// kyseessä kotimaa
							$trow['oletus_vienti'] = 'C';
						}
						else {
							$chkquery = "SELECT * from maat where koodi = '$trow[maa]' and eu != ''";
							$checkostot = mysql_query($chkquery) or die ("$chkquery<br><br>".mysql_error());

							if (mysql_num_rows($checkostot) != 0) {
								// kyseessä EU
								$trow['oletus_vienti'] = 'F';
							}
							else {
								// kyseessä EI-EU
								$trow['oletus_vienti'] = 'I';
							}
						}
					}
				}

				$ytunnus = $trow["ytunnus"];
				$verkapunimi = $trow["nimi"];

				$hyvak[1] = $trow['oletus_hyvak1'];
				$hyvak[2] = $trow['oletus_hyvak2'];
				$hyvak[3] = $trow['oletus_hyvak3'];
				$hyvak[4] = $trow['oletus_hyvak4'];
				$hyvak[5] = $trow['oletus_hyvak5'];

				$oletustili     = $trow['tilino'];
				$oletuskust     = $trow['kustannuspaikka'];
				$oletuskohde    = $trow['kohde'];
				$oletusprojekti = $trow['projekti'];
				$selite         = $trow['nimi'] . " " . $trow['nimitark'] . " Lasno: " . $laskun_numero;
				$laskun_numero	= preg_replace("/[^0-9]/", "", $laskun_numero);

				// Onko tälle toimitusasiakkaalle erityinen kustannuspaikka?
				$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5
							FROM tiliointisaanto
							WHERE ttunnus 	= '$trow[tunnus]'
							and yhtio 		= '$yhtio'
							and tyyppi		= 'o'";

				if ($laskuformaatti == "INVOIC.D.96A") {
					$query .= "	and SUBSTRING(kuvaus,   1, 30) = '$toim_asiakkaantiedot[nimi]'
								and SUBSTRING(kuvaus2,  1, 30) = '$toim_asiakkaantiedot[osoite]'
								and SUBSTRING(mintuote, 1, 30) = '$toim_asiakkaantiedot[postino]'
								and SUBSTRING(maxtuote, 1, 30) = '$toim_asiakkaantiedot[postitp]'";
				}
				else {
					$query .= " and kuvaus   = '$toim_asiakkaantiedot[nimi]'
								and kuvaus2  = '$toim_asiakkaantiedot[osoite]'
								and mintuote = '$toim_asiakkaantiedot[postino]'
								and maxtuote = '$toim_asiakkaantiedot[postitp]'";
				}

				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) != 0) {
					$tiliointirow = mysql_fetch_array($result);
					$oletuskust = $tiliointirow['kustp'];

					if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
						$hyvak[1] = "";
						$hyvak[2] = "";
						$hyvak[3] = "";
						$hyvak[4] = "";
						$hyvak[5] = "";
					}
					elseif (trim($tiliointirow['hyvak1']) != '') {
						$hyvak[1] = $tiliointirow['hyvak1'];
						$hyvak[2] = $tiliointirow['hyvak2'];
						$hyvak[3] = $tiliointirow['hyvak3'];
						$hyvak[4] = $tiliointirow['hyvak4'];
						$hyvak[5] = $tiliointirow['hyvak5'];
					}
				}

				// Onko tälle ostajatiedoille erityinen kustannuspaikka?
				$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5
							FROM tiliointisaanto
							WHERE ttunnus 	= '$trow[tunnus]'
							and yhtio 		= '$yhtio'
							and tyyppi		= 'b'";

				if ($laskuformaatti == "INVOIC.D.96A") {
					$query .= "	and SUBSTRING(kuvaus,   1, 30) = '$ostaja_asiakkaantiedot[nimi]'
								and SUBSTRING(kuvaus2,  1, 30) = '$ostaja_asiakkaantiedot[osoite]'
								and SUBSTRING(mintuote, 1, 30) = '$ostaja_asiakkaantiedot[postino]'
								and SUBSTRING(maxtuote, 1, 30) = '$ostaja_asiakkaantiedot[postitp]'";
				}
				else {
					$query .= " and kuvaus   = '$ostaja_asiakkaantiedot[nimi]'
								and kuvaus2  = '$ostaja_asiakkaantiedot[osoite]'
								and mintuote = '$ostaja_asiakkaantiedot[postino]'
								and maxtuote = '$ostaja_asiakkaantiedot[postitp]'";
				}

				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) != 0) {
					$tiliointirow = mysql_fetch_array($result);
					$oletuskust = $tiliointirow['kustp'];

					if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
						$hyvak[1] = "";
						$hyvak[2] = "";
						$hyvak[3] = "";
						$hyvak[4] = "";
						$hyvak[5] = "";
					}
					elseif (trim($tiliointirow['hyvak1']) != '') {
						$hyvak[1] = $tiliointirow['hyvak1'];
						$hyvak[2] = $tiliointirow['hyvak2'];
						$hyvak[3] = $tiliointirow['hyvak3'];
						$hyvak[4] = $tiliointirow['hyvak4'];
						$hyvak[5] = $tiliointirow['hyvak5'];
					}
				}

				if (mysql_num_rows($result) == 0) {
					// Onko tälle asiakastunnukselle erityinen kustannuspaikka?
					$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5
								FROM tiliointisaanto
								WHERE ttunnus 	= '$trow[tunnus]'
								and tyyppi 		= 'a'
								and yhtio 		= '$yhtio'
								and tilino 		= 0
								and kuvaus 		= '$laskun_asiakastunnus'";
					$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

					if (mysql_num_rows($result) != 0) {
						$tiliointirow = mysql_fetch_array($result);
						$oletuskust = $tiliointirow['kustp'];

						if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
							$hyvak[1] = "";
							$hyvak[2] = "";
							$hyvak[3] = "";
							$hyvak[4] = "";
							$hyvak[5] = "";
						}
						elseif (trim($tiliointirow['hyvak1']) != '') {
							$hyvak[1] = $tiliointirow['hyvak1'];
							$hyvak[2] = $tiliointirow['hyvak2'];
							$hyvak[3] = $tiliointirow['hyvak3'];
							$hyvak[4] = $tiliointirow['hyvak4'];
							$hyvak[5] = $tiliointirow['hyvak5'];
						}
					}
				}

				if (mysql_num_rows($result) == 0) {
					// Onko tämä kauttalaskutusta?
					$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5
								FROM tiliointisaanto
								WHERE ttunnus 	= '$trow[tunnus]'
								and tyyppi 		= 'k'
								and yhtio 		= '$yhtio'
								and tilino 		= 0
								and kuvaus 		= '$kauttalaskutus'";
					$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

					if (mysql_num_rows($result) != 0) {
						$tiliointirow = mysql_fetch_array($result);
						$oletuskust = $tiliointirow['kustp'];

						if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
							$hyvak[1] = "";
							$hyvak[2] = "";
							$hyvak[3] = "";
							$hyvak[4] = "";
							$hyvak[5] = "";
						}
						elseif (trim($tiliointirow['hyvak1']) != '') {
							$hyvak[1] = $tiliointirow['hyvak1'];
							$hyvak[2] = $tiliointirow['hyvak2'];
							$hyvak[3] = $tiliointirow['hyvak3'];
							$hyvak[4] = $tiliointirow['hyvak4'];
							$hyvak[5] = $tiliointirow['hyvak5'];
						}
					}
				}

				// errorcheckit...
				$val = checkdate($tpk, $tpp, $tpv);

				if (!$val) {
					// Laitetaan sitten ajopäivä
					list($tpv,$tpk,$tpp) = explode("-",strftime("%Y-%m-%d", mktime(0,0,0,date("m"),date("d"),date("Y"))));
					$komm = "(verkkolas@" . date('Y-m-d') .") Tiedoista puuttui päiväys. Tarkista asia laskulta!<br>" . $komm;
				}

				// Otetaan yhtiön tiedoista ostoreskontran sallittu tilikauden ajankohta
				$tilalk = explode("-", $yhtiorow["ostoreskontrakausi_alku"]);
				$tillop = explode("-", $yhtiorow["ostoreskontrakausi_loppu"]);

				// verrataan vähän päivämääriä. onpa vittumaista PHP:ssä!
				$ostoresktilalk = (int) date('Ymd',mktime(0,0,0,$tilalk[1],$tilalk[2],$tilalk[0]));
				$ostoresktillop = (int) date('Ymd',mktime(0,0,0,$tillop[1],$tillop[2],$tillop[0]));
				$syotettypvm = (int) date('Ymd',mktime(0,0,0,$tpk,$tpp,$tpv));

				if ($syotettypvm > $ostoresktillop) {
					$tpp = $tillop[2];
					$tpk = $tillop[1];
					$tpv = $tillop[0];
					$komm = "(verkkolas@" . date('Y-m-d') .") Laskulla oli päiväys liian pitkälle tulevaisuuteen. Tarkista asia laskulta!<br>" . $komm;
				}

				if ($syotettypvm < $ostoresktilalk) {
					$tpp = $tilalk[2];
					$tpk = $tilalk[1];
					$tpv = $tilalk[0];
					$komm = "(verkkolas@" . date('Y-m-d') .") Laskulla oli liian vanha päiväys. Tarkista asia laskulta!<br>" . $komm;
				}

				// oletus alv nolla paitsi jos toimittajalla on oletuksena jotain kotimaahan liittyvää ni haetaan oletus avainsanoista...
				$oletusalvi = 0;

				if ($trow['oletus_vienti'] == "A" or $trow['oletus_vienti'] == "B" or $trow['oletus_vienti'] == "C" or $trow['oletus_vienti'] == "J") {

					$query = "	SELECT selite
								FROM avainsana
								WHERE yhtio = '$yhtio' and laji = 'alv' and selitetark != ''
								ORDER BY jarjestys,selite";
					$avainresult = mysql_query($query) or die ("$query".mysql_error());

					if (mysql_num_rows($avainresult) != 1) {
						$return .= "Yrityksen $yhtio oletus ALV% puuttuu tai niitä on monta!\n";
						mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
						return ($return);
					}
					else {
						$avainrow = mysql_fetch_array($avainresult);
						$oletusalvi = $avainrow['selite'];
					}
				}

				// jos eräpäivää ei tule laskulta, otetaan toimittajan oletus
				if ($erp == 0 and $erk == 0 and $erv == 0) {
					$err = $trow["oletus_erapvm"];
					// jos oletustakaan ei ole, laitetaan lasku erääntymään huomenna...
					if ($err == 0) {
						list($erv,$erk,$erp) = explode("-",strftime("%Y-%m-%d", mktime(0,0,0,date("m"),date("d")+1,date("Y"))));
					}
					$komm = "(verkkolas@" . date('Y-m-d') .") Tiedoista puuttui eräpvm. Tarkista asia laskulta!<br>" . $komm;
				}

				if (trim($laskun_numero) != '' and trim($trow['tunnus']) != '') {
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '{$yhtio}'
								AND tila IN ('H','M','P','Q','Y')
								AND laskunro = '{$laskun_numero}'
								AND tapvm >= date_sub(now(), INTERVAL 12 MONTH)
								AND liitostunnus = '{$trow['tunnus']}'";
					$duplikaattilasku_check_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($duplikaattilasku_check_res) > 0) {
						$komm = "(verkkolas@" . date('Y-m-d') .") Mahdollisesti duplikaattilasku!<br>".$komm;
					}
				}

				if ($err > 0) {
					$newer = strftime("%Y-%m-%d", mktime(0,0,0,$tpk,$tpp+$err,$tpv));
					$erp = (int) substr($newer, 8, 2);
					$erk = (int) substr($newer, 5, 2);
					$erv = (int) substr($newer, 0, 4);
					$err = 0;
				}

				$kar   = $trow['oletus_kapvm'];
				$kapro = $trow['oletus_kapro'];

				$kap = 0;
				$kak = 0;
				$kav = 0;

				if ($kar > 0) {
					$newer = strftime("%Y-%m-%d", mktime(0,0,0,$tpk,$tpp+$kar,$tpv));
					$kap = (int) substr($newer, 8, 2);
					$kak = (int) substr($newer, 5, 2);
					$kav = (int) substr($newer, 0, 4);
					$kar = 0;
				}

				$val = checkdate($erk, $erp, $erv);
				if (!$val) {
					$return .= "Virheellinen eräpvm $erv-$erk-$erp\n";
					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				$kassaale = 0;
				if ($kapro != 0 and (float) $summa > 0) {
					$kassaale = $summa * $kapro / 100;
					$kapro = 0;
				}
				$kassaale = round($kassaale,2);

				if ($kak > 0 and $kap > 0 and $kav > 0 and $kassaale > 0) {

					$val = checkdate($kak, $kap, $kav);
					if (!$val) {
						$return .= "Virheellinen kassaeräpvm '$kav-$kak-$kap' kassaale '$kassaale'\n";
						mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
						return ($return);
					}
					else {
						if ($kassaale == 0) {
							$return .= "Kassapvm on, mutta kassa-ale puuttu\n";
							mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
							return ($return);
						}
					}
				}

				$summa = round($summa,2);

				if ($summa == 0.0) {
					$return .= "Laskulta puuttuu summa!\n";
					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				if (strlen($viite) > 0 and tarkista_viite($viite) === FALSE) {
					$return .= "Viite on väärin!\n";
					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				if (strlen($viite) > 0 and strlen($viesti) > 0) {
					$return .= "Viitettä ja viestiä ei voi antaa yhtaikaa!\n";
					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				$query = "SELECT kurssi FROM valuu WHERE nimi = '$valkoodi' and yhtio = '$yhtio'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

				if (mysql_num_rows($result) != 1) {
					$return .= "Valuuttaa $valkoodi ei löytynytkään!\n";
					mail($yhtiorow['alert_email'], "Verkkolaskun vastaanotto epäonnistui", $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				$vrow = mysql_fetch_array ($result);

				$hyvaksyja_nyt 	= '';
				$tila 			= "M";

				$hyvak[5] = trim($hyvak[5]);
				$hyvak[4] = trim($hyvak[4]);
				$hyvak[3] = trim($hyvak[3]);
				$hyvak[2] = trim($hyvak[2]);
				$hyvak[1] = trim($hyvak[1]);

				if (strlen($hyvak[5]) > 0) {
					$hyvaksyja_nyt=$hyvak[5];
					$tila = "H";
				}
				if (strlen($hyvak[4]) > 0) {
					$hyvaksyja_nyt=$hyvak[4];
					$tila = "H";
				}
				if (strlen($hyvak[3]) > 0) {
					$hyvaksyja_nyt=$hyvak[3];
					$tila = "H";
				}
				if (strlen($hyvak[2]) > 0) {
					$hyvaksyja_nyt=$hyvak[2];
					$tila = "H";
				}
				if (strlen($hyvak[1]) > 0) {
					$hyvaksyja_nyt=$hyvak[1];
					$tila = "H";
				}

				$olmapvm = $erv."-".$erk."-".$erp;

				if ($kap != 0) {
					$olmapvm = $kav."-".$kak."-".$kap;
				}

				// Kirjoitetaan lasku
				$query = "	INSERT into lasku set
							yhtio 				= '$yhtio',
							summa 				= '$summa',
							kasumma 			= '$kassaale',
							erpcm 				= '$erv-$erk-$erp',
							kapvm 				= '$kav-$kak-$kap',
							olmapvm 			= '$olmapvm',
							valkoodi 			= '$valkoodi',
							hyvak1 				= '$hyvak[1]',
							hyvak2 				= '$hyvak[2]',
							hyvak3 				= '$hyvak[3]',
							hyvak4 				= '$hyvak[4]',
							hyvak5 				= '$hyvak[5]',
							hyvaksyja_nyt 		= '$hyvaksyja_nyt',
							ytunnus 			= '$ytunnus',
							tilinumero 			= '$trow[tilinumero]',
							nimi 				= '$trow[nimi]',
							nimitark 			= '$trow[nimitark]',
							osoite 				= '$trow[osoite]',
							osoitetark 			= '$trow[osoitetark]',
							postino 			= '$trow[postino]',
							postitp 			= '$trow[postitp]',
							maa 				= '$trow[maa]',
							viite 				= '$viite',
							viesti 				= '$viesti',
							sisviesti1 			= '$laskun_tilausviite',
							sisviesti2 			= '$sis2',
							tapvm 				= '$tpv-$tpk-$tpp',
							vienti 				= '$trow[oletus_vienti]',
							ebid 				= '$ebid',
							tila 				= '$tila',
							vienti_kurssi 		= '$vrow[kurssi]',
							laatija 			= 'verkkolas',
							liitostunnus 		= '$trow[tunnus]',
							luontiaika 			= now(),
							pankki1				= '$trow[pankki1]',
							pankki2				= '$trow[pankki2]',
							pankki3				= '$trow[pankki3]',
							pankki4				= '$trow[pankki4]',
							ultilno				= '$trow[ultilno]',
							swift				= '$trow[swift]',
							suoraveloitus		= '$trow[oletus_suoraveloitus]',
							hyvaksynnanmuutos	= '$trow[oletus_hyvaksynnanmuutos]',
							comments 			= '$komm',
							laskunro 			= '$laskun_numero',
							asiakkaan_tilausnumero = '$laskun_asiakkaan_tilausnumero'";
				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
				$tunnus = mysql_insert_id();
				$omasumma = round($summa * $vrow['kurssi'], 2);

				// Tehdään oletustiliöinnit, ostovelat
				$vassumma = -1 * $omasumma;

				//Tutkitaan otsovelkatiliä
				if ($trow["konserniyhtio"] != '') {
					$ostovelat = $yhtiorow["konserniostovelat"];
				}
				else {
					$ostovelat = $yhtiorow["ostovelat"];
				}

				if ($yhtiorow["kirjanpidon_tarkenteet"] == "K") {
					$ostovelat_kustp = $oletuskust;
					$ostovelat_kohde = $oletuskohde;
					$ostovelat_projekti = $oletusprojekti;
				}
				else {
					$ostovelat_kustp = 0;
					$ostovelat_kohde = 0;
					$ostovelat_projekti = 0;
				}

				$query = "	INSERT into tiliointi set
							yhtio 		= '$yhtio',
							ltunnus 	= '$tunnus',
							tilino 		= '$ostovelat',
							kustp		= '$ostovelat_kustp',
							kohde		= '$ostovelat_kohde',
							projekti	= '$ostovelat_projekti',
							tapvm 		= '$tpv-$tpk-$tpp',
							summa 		= '$vassumma',
							selite 		= '$selite',
							vero 		= 0,
							lukko 		= '1',
							laatija 	= 'verkkolas',
							laadittu 	= now()";
				$result = mysql_query($query) or die ("$query\n\n".mysql_error());

				// Onko rivikohtaisia tiliöintisääntöjä
				$query = "	SELECT *
							FROM tiliointisaanto
							WHERE ttunnus	= '$trow[tunnus]'
							and yhtio 		= '$yhtio'
							and tyyppi 		= 't'
							and tilino	   != 0";
				$result = mysql_query($query) or die ("$query\n\n".mysql_error());

				// Onko laskuriveillä useita alv-verokantoja?
				if (count($ealvi) > 0) {
					$ealvi = array_unique($ealvi);
				}
				else {
					//Katsotaan alv laskuerittelystä
					$ealvi[0] = $lisavat[0];
				}

	 			// Tehdän pelkät oletustiliöinnit
				if (mysql_num_rows($result) == 0 and count($ealvi) == 1) {

					if ($oletustili > 0) {
						$tili = $oletustili;
					}
					else {
						$tili = $yhtiorow['muutkulut'];
					}

					$vero = $oletusalvi;

					if ($ealvi[0] != '') {
						$vero = $ealvi[0]; //Tuliko jotain verkkolaskulta
					}

					$kukarow['yhtio']   = $yhtio;
					$kukarow['kuka']    = 'verkkolas';

					$verkkolaskuveroton = round($omasumma / (1 + ($vero / 100)),2);
					$summa              = $omasumma;
					$kustp              = $oletuskust;
					$kohde              = $oletuskohde;
					$projekti           = $oletusprojekti;

					require ("inc/teetiliointi.inc");

					// jos kyseessä on jonkin rahti/huolintakuluja, tiliöidään varastonarvoon
					if ($trow['oletus_vienti'] != 'A' and $trow['oletus_vienti'] != 'D' and $trow['oletus_vienti'] != 'G' and $trow['oletus_vienti'] != '') {

						$varastotili = $yhtiorow['varasto'];

						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') {
								$varastotili = $yhtiorow['matkalla_olevat'];
						}

						$query = "	INSERT into tiliointi set
									yhtio = '$kukarow[yhtio]',
									ltunnus = '$tunnus',
									tilino = '$varastotili',
									kustp = 0,
									tapvm = '$tpv-$tpk-$tpp',
									summa = '$verkkolaskuveroton',
									selite = '$selite',
									vero = 0,
									lukko = '',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());

						$query = "	INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$tunnus',
									tilino = '$yhtiorow[varastonmuutos]',
									kustp = '$kustp',
									kohde = '$kohde',
									projekti = '$projekti',
									tapvm = '$tpv-$tpk-$tpp',
									summa = $verkkolaskuveroton*-1,
									selite = '$selite',
									vero = 0,
									lukko = '',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());

						// Jos tämä lasku menee varastoon, tehdään valmiiksi keikka
						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') {
							verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
						}
					}
				}
				else {
					// Tehdään rivikohtaiset tiliöinnit
					$i					= 0;
					$totsumma 			= 0;
					$verkkolaskuveroton = 0;
					$vtontot 			= 0;

					for ($i=0; $i < count($rtuoteno); $i++) {

						if ((float) $rtuoteno[$i]["rivihinta"] != 0) {

							$kustp = $oletuskust;

							$selite = utf8_decode(str_replace ("'", " ", $rtuoteno[$i]["riviinfo"])); // Poistaa SQL-virheen mahdollisuuden
							$tuote  = utf8_decode(str_replace ("'", " ", $rtuoteno[$i]["tuoteno"]));  // Poistaa SQL-virheen mahdollisuuden

							$query = "	SELECT tilino, kustp
										FROM tiliointisaanto
										WHERE ttunnus 	= '$trow[tunnus]'
										and tyyppi 		= 't'
										and yhtio 		= '$yhtio'
										and mintuote   <= '$tuote'
										and maxtuote   >= '$tuote'
										and tilino 	   != 0";
							$result = mysql_query($query) or die ("$query\n\n".mysql_error());

	 						// Sopiva sääntöä ei löytynyt
							if (mysql_num_rows($result) == 0) {

								$query = "	SELECT tilino, kustp
											FROM tiliointisaanto
											WHERE ttunnus 	= '$trow[tunnus]'
											and tyyppi 		= 't'
											and yhtio 		= '$yhtio'
											and kuvaus like '%$selite%'
											and tilino     != 0";
								$result = mysql_query($query) or die ("$query\n\n".mysql_error());

	 							// Hmm, mikään sääntö ei kelpaa
								if (mysql_num_rows($result) == 0) {

	 								// Toimittajan oletustili
									if ($oletustili > 0) {
										$tili = $oletustili;
									}
									else {
										 // Yleinen kulutili
										$tili = $yhtiorow['muutkulut'];
									}
								}
								else {
									$tiliointirow = mysql_fetch_array($result);
									$tili = $tiliointirow['tilino'];
									if ($tiliointirow['kustp'] > 0) $kustp = $tiliointirow['kustp'];
								}
							}
							else {
								$tiliointirow = mysql_fetch_array($result);
								$tili = $tiliointirow['tilino'];
								if ($tiliointirow['kustp'] > 0) $kustp = $tiliointirow['kustp'];
							}

							$summa = (float) $rtuoteno[$i]["rivihinta"];

	 						// Hyvityslasku tai summa alle nolla
							// FINVOICE-laskuilla rivihinta on aina valmiiksi oikeinpäin
							if (($laskun_tyyppi == "381" or $omasumma < 0) and $laskuformaatti != "FINVOICE") {
								$summa = $summa * -1;
							}

							$vero = (float) $rtuoteno[$i]["alv"];

							$verkkolaskuveroton += $summa;
							$vtonsumma 			 = $summa;
							$vtontot 		    += $summa;
							$summa 			 	 = round($summa * (1+($vero/100)),2);
							$totsumma 		    += $summa;
							$selite				 = $selite." ".$trow['nimi']." ".$trow['nimitark']." Lasno: ".$laskun_numero;
							$kukarow['yhtio'] 	 = $yhtio;
							$kukarow['kuka'] 	 = 'verkkolas';

							require ("inc/teetiliointi.inc");

							// jos kyseessä on jonkin rahti/huolintakuluja, tiliöidään varastonmuutokseen
							if ($trow['oletus_vienti'] != 'A' and $trow['oletus_vienti'] != 'D' and $trow['oletus_vienti'] != 'G' and $trow['oletus_vienti'] != '') {
								$query = "	INSERT into tiliointi set
											yhtio 		= '$kukarow[yhtio]',
											ltunnus 	= '$tunnus',
											tilino 		= '$yhtiorow[varastonmuutos]',
											kustp 		= '$kustp',
											kohde 		= '$kohde',
											projekti 	= '$projekti',
											tapvm 		= '$tpv-$tpk-$tpp',
											summa 		= $vtonsumma *-1,
											selite 		= '$selite',
											vero 		= 0,
											lukko 		= '',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$result = mysql_query($query) or die ("$query\n\n".mysql_error());
							}
						}
						$selite = $trow['nimi']." ".$trow['nimitark']." Lasno: ".$laskun_numero;
					}

	 				// Tuli pyöristyseroja
					if (round(abs($totsumma - $omasumma),2) >= 0.01) {
						$query = "	INSERT into tiliointi set
									yhtio 		= '$yhtio',
									ltunnus 	= '$tunnus',
									tilino 		= '$yhtiorow[pyoristys]',
									kustp 		= 0,
									tapvm 		= '$tpv-$tpk-$tpp',
									summa 		= $omasumma - $totsumma,
									selite 		= '$selite',
									vero 		= 0,
									lukko 		= '',
									laatija 	= 'verkkolas',
									laadittu 	= now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());
					}

					// jos kyseessä on jonkin rahti/huolintakuluja, tiliöidään varastonarvoon
					if ($trow['oletus_vienti'] != 'A' and $trow['oletus_vienti'] != 'D' and $trow['oletus_vienti'] != 'G' and $trow['oletus_vienti'] != '') {

						$varastotili = $yhtiorow['varasto'];
						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') {
								$varastotili = $yhtiorow['matkalla_olevat'];
						}

						$query = "	INSERT into tiliointi set
									yhtio 		= '$kukarow[yhtio]',
									ltunnus 	= '$tunnus',
									tilino 		= '$varastotili',
									kustp 		= 0,
									tapvm 		= '$tpv-$tpk-$tpp',
									summa 		= '$vtontot',
									selite 		= '$selite',
									vero 		= 0,
									lukko 		= '',
									laatija		= '$kukarow[kuka]',
									laadittu	= now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());

						// Jos tämä lasku menee varastoon, tehdään valmiiksi keikka
						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') {
							verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
						}
					}
				}

				// Jos meillä on suoraveloitus
				if ($trow['oletus_suoraveloitus'] != '') {

					if ($trow['oletus_suoravel_pankki'] > 0) {
						 //Toimittajalla on pankkitili, teemme eräpäivälle suorituksen valmiiksi
						// Oletustiliöinnit
						// Ostovelat
						$query = "	INSERT into tiliointi set
									yhtio 		= '$kukarow[yhtio]',
									ltunnus 	= '$tunnus',
									tilino		= '$ostovelat',
									tapvm 		= '$erv-$erk-$erp',
									summa 		= '$omasumma',
									vero 		= 0,
									lukko 		= '',
									laatija 	= '$kukarow[kuka]',
									laadittu	= now()";
						$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());

						// Rahatili
						$query = "	INSERT into tiliointi set
									yhtio 		= '$kukarow[yhtio]',
									ltunnus 	= '$tunnus',
									tilino 		= '$yhtiorow[selvittelytili]',
									tapvm 		= '$erv-$erk-$erp',
									summa 		= $vassumma,
									vero 		= 0,
									lukko 		= '',
									laatija 	= '$kukarow[kuka]',
									laadittu 	= now()";
						$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());

						if ($tila == 'M') {
							$query = "	UPDATE lasku set
										tila 	= 'Y',
										mapvm 	= '$erv-$erk-$erp',
										maksu_kurssi = 1
										WHERE tunnus = '$tunnus'";
							$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
							//echo "Lasku merkittiin suoraan maksetuksi";
						}
					}
					else {
					 	// Tämä koskee vain suoraveloitusta
						if ($tila == 'M') {
							$query = "	UPDATE lasku set
										tila = 'Q'
										WHERE tunnus='$tunnus'";
							$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
							//echo "Lasku merkittiin odottamaan suoritusta";
						}
					}
				}

				$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
				$kuvalaskures = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($kuvalaskures) > 0) {

					$kuvalaskurow = mysql_fetch_array($kuvalaskures);

					$ebid 	= $kuvalaskurow['ebid'];
					$selite = mysql_real_escape_string("$kuvalaskurow[nimi] $kuvalaskurow[summa] $kuvalaskurow[valkoodi]");

					// Tallennetaan laskun XML liitetiedostoksi
					$filesize = strlen($xmlstr);
					$filename = array_pop(explode("/", $file));
					$xmlstr   = mysql_real_escape_string($xmlstr);

					if ($ebid == "FINVOICEXML") {
						$kayttotarkoitus = 'FINVOICE';
					}
					else {
						$kayttotarkoitus = 'INVOIC.D.96A';
					}

					$query = "	INSERT INTO liitetiedostot SET
								yhtio    			= '$kukarow[yhtio]',
								liitos   			= 'lasku',
								liitostunnus 		= '$tunnus',
								data     			= '$xmlstr',
								selite   			= '$selite',
								kieli				= '$yhtiorow[kieli]',
								filename 			= '$filename',
								filesize 			= '$filesize',
								filetype 			= 'text/xml',
								image_width			= '',
								image_height		= '',
								image_bits			= '',
								image_channels		= '',
								kayttotarkoitus		= '$kayttotarkoitus',
								jarjestys			= '1',
								laatija				= '$kukarow[kuka]',
								luontiaika			= now()";
					$Xresult = mysql_query($query) or die ("$query\n\n".mysql_error());

					// Haetaan verkkolaskun visualisoitu versio
					if ($ebid != "FINVOICEXML") {
						$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
						$salasana		   = $yhtiorow['verkkosala_vas'];

						$timestamppi = gmdate("YmdHis")."Z";

						$urlhead = "http://www.verkkolasku.net";
						$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

						$digest	 = md5($urlmain . "&" . $salasana);
						$url	 = $urlhead.$urlmain."&DIGEST=$digest";

						$sisalto = file_get_contents($url);

						if (substr($sisalto,0,4) == "%PDF") {
							// tallennetaan kuva
							$filename = $ebid.".pdf";

							//filesize functio ei toimi koska ei ole oikea file.
							$filesize = strlen($sisalto);
							$data  = mysql_real_escape_string($sisalto);

							// lisätään kuva
							$query = "	INSERT INTO liitetiedostot SET
										yhtio    			= '$kukarow[yhtio]',
										liitos   			= 'lasku',
										liitostunnus 		= '$tunnus',
										data     			= '$data',
										selite   			= '$selite',
										kieli				= '$yhtiorow[kieli]',
										filename 			= '$file',
										filesize 			= '$filesize',
										filetype 			= 'application/pdf',
										image_width			= '',
										image_height		= '',
										image_bits			= '',
										image_channels		= '',
										kayttotarkoitus		= 'VERKKOLASKU',
										jarjestys			= '1',
										laatija				= '$kukarow[kuka]',
										luontiaika			= now()";
							$Xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
						}
					}

					if (trim($liitetaanko_editilaus_laskulle_hakemisto) != '' and trim($laskun_asiakkaan_tilausnumero) != '') {

						// fopen dirikka $liitetaanko_editilaus_laskulle_hakemisto eli purchaseorderit
						$files_editilaus_laskulle = listdir($liitetaanko_editilaus_laskulle_hakemisto);

						// loopataan se läpi
						foreach ($files_editilaus_laskulle as $files_editilaus_laskulle_x) {

							$path_parts = pathinfo($files_editilaus_laskulle_x);
							$editilaus_laskulle_polku = substr($liitetaanko_editilaus_laskulle_hakemisto, -1) !== '/' ? $liitetaanko_editilaus_laskulle_hakemisto.'/' : $liitetaanko_editilaus_laskulle_hakemisto;

							if ($editilaus_laskulle_data = file_get_contents($editilaus_laskulle_polku.$path_parts['basename'])) {

								if (strpos($editilaus_laskulle_data, "OSTOTIL.OT_NRO:".$laskun_asiakkaan_tilausnumero) !== FALSE) {

									$editilaus_laskulle_filesize = filesize($editilaus_laskulle_polku.$path_parts['basename']);
									$data  = mysql_real_escape_string($editilaus_laskulle_data);

									$query = "	INSERT INTO liitetiedostot SET
												yhtio    			= '$kukarow[yhtio]',
												liitos   			= 'lasku',
												liitostunnus 		= '$tunnus',
												data     			= '$data',
												selite   			= 'Futursoft-tilaus $laskun_asiakkaan_tilausnumero',
												kieli				= '$yhtiorow[kieli]',
												filename 			= '$path_parts[basename]',
												filesize 			= '$editilaus_laskulle_filesize',
												filetype 			= 'text',
												image_width			= '',
												image_height		= '',
												image_bits			= '',
												image_channels		= '',
												kayttotarkoitus		= 'EDI',
												jarjestys			= '1',
												laatija				= '$kukarow[kuka]',
												luontiaika			= now()";
									$editilaus_laskulle_result = mysql_query($query) or die ("$query\n\n".mysql_error());

									// Katotaan onko eroja vai onko ok
									list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $tunnus);

									if ($invoice != FALSE and $invoice != 'ei_loydy_edia') {
										if (count($invoice_ei_loydy) == 0 and count($loytyy_kummastakin) > 0) {
											$ok = 'ok';

											foreach ($loytyy_kummastakin as $tuoteno => $null) {
												if ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']) > 1) {
													$ok = '';
													break;
												}
											}

											if ($ok == 'ok') {
												// Ei eroja, laitetaan lasku suoraan maksuvalmiiksi
												$query = "	UPDATE lasku set
															tila 		  = 'M',
															hyvak1 		  = 'verkkolas',
															h1time 		  = now(),
															hyvak2 		  = '',
															hyvak3 		  = '',
															hyvak4 		  = '',
															hyvak5 		  = '',
															hyvaksyja_nyt = '',
															comments 	  = trim(concat(comments, '\n Lasku siirrettiin suoraan maksuvalmiiksi, koska se täsmäsi ostotilaukseen.'))
															WHERE tunnus = '$tunnus'";
												$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
											}
										}
									}

									// ulos loopista
									break;
								}
							}
						}
					}
				}

				// päästiin onnillisesti loppuun
		    	$sub = "$yhtiorow[nimi] vastaanotti laskun $verkapunimi:lta";
		    	$meili = "Verkkolasku $verkapunimi yritykselle $yhtiorow[nimi]\nTiedosto: $file\nLähettäjä: $laskuttajan_nimi\nLaskunro: $laskun_numero\nPäivä: $laskun_paiva\nSumma: $laskun_summa_eur\nViite: $laskun_pankkiviite\nEnsimmäinen hyväksyjä: $hyvaksyja_nyt\n$laskuvirhe\n";
			    $tulos = mail($yhtiorow['alert_email'], $sub, $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

				if ($tulos === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $yhtiorow[alert_email]<br>";
				return 0;
			}
			else {
				return array($yhtiorow, $trow);
			}
		}
	}
?>