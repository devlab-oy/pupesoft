<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t('Matkalasku / kulukorvaus')."</font><hr>";

if ($tee == "VALMIS") {
	$query = "	UPDATE lasku SET
				alatila = ''
				WHERE yhtio 	 = '$kukarow[yhtio]'
				and tunnus 		 = '$tilausnumero'
				and tila		 = 'H'
				and tilaustyyppi = 'M'
				and alatila 	 = 'M'";
	$updres = pupe_query($query);

	$tee 		  = "";
	$tunnus 	  = 0;
	$tilausnumero = 0;
}

if ($tee == "UUSI") {

	//	tarkastetaan että käyttäjälle voidaan perustaa matkalaskuja
	if ($toim == "SUPER" and $kayttaja != "") {
		$kayttaja_tsk = $kayttaja;
	}
	else {
		$kayttaja_tsk = $kukarow["kuka"];
	}

	$query = "	SELECT toimi.*, kuka.kuka kuka, kuka.nimi kayttajanimi
				FROM toimi
				JOIN kuka ON (kuka.yhtio = toimi.yhtio and kuka.kuka = toimi.nimi)
				WHERE toimi.yhtio = '$kukarow[yhtio]'
				and toimi.nimi = '$kayttaja_tsk'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$trow = mysql_fetch_assoc($result);
	}
	else {
		if ($toim == "SUPER" and $kayttaja != "") {
			echo "<font class='error'>".t("VIRHE: Henkilölle ei voida perustaa matkalaskua")."!</font>";
			$tee = "";
		}
		else {
			echo "<font class='error'>".t("VIRHE: Matkustaja puuttuu Matkalaskukäyttäjärekisteristä")."!</font>";
			$tee = "";
		}
	}

	/*
		Täältä löytyy kaikki verottajan ulkomaanpäivärahat, sekä ohjeet niiden käsittelyyn
		http://www.vero.fi/fi-FI/Henkiloasiakkaat/Tyosuhde/Verohallinnon_paatos_verovapaista_matkak(12356)
		(tai hakusanalla päivärahat yyyy)
	*/

	$query = "	SELECT tuoteno
				FROM tuote
				JOIN tili ON tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.tuotetyyppi in ('A','B')
				and tuote.status != 'P'
				and tuote.tilino != ''
				LIMIT 1";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("VIRHE: Viranomaistuotteet puuttuu")."!</font>";
		$tee = "";
	}
}

if ($tee == "UUSI") {
	if ($ytunnus != '' or isset($EI_ASIAKASTA_X)) {

		if ($ytunnus != '') {
			require ("inc/asiakashaku.inc");
			unset($EI_ASIAKASTA_X);
		}
		else {
			$asiakasrow = array();
		}

		if ($asiakasid > 0 or isset($EI_ASIAKASTA_X)) {

			if ($trow["oletus_erapvm"] > 0) $erpaivia = $trow["oletus_erapvm"];
			else $erpaivia = 1;

			// haetaan seuraava vapaa matkalaskunumero
			$query  = "	SELECT max(laskunro) laskunro
						FROM lasku
						WHERE yhtio	= '$kukarow[yhtio]'
						and tila IN ('H','Y','M','P','Q')
						and alatila IN ('','M','H')
						and tilaustyyppi = 'M'";
			$result = pupe_query($query);
			$row    = mysql_fetch_assoc($result);
			$maxmatkalasku = $row["laskunro"]+1;

			// Perustetaan lasku
			$query = "	INSERT into lasku set
						yhtio 			= '$kukarow[yhtio]',
						valkoodi 		= 'EUR',
						hyvak1 			= '$trow[kuka]',
						hyvaksyja_nyt 	= '$trow[kuka]',
						hyvak2 			= '$trow[oletus_hyvak2]',
						hyvak3 			= '$trow[oletus_hyvak3]',
						hyvak4 			= '$trow[oletus_hyvak4]',
						hyvak5 			= '$trow[oletus_hyvak5]',
						ytunnus 		= '$trow[ytunnus]',
						toim_ovttunnus 	= '$trow[kuka]',
						tilinumero 		= '$trow[tilinumero]',
						ultilno			= '$trow[ultilno]',
						nimi 			= '$trow[kayttajanimi]',
						nimitark 		= '".t("Matkalasku")."',
						osoite 			= '$trow[osoite]',
						osoitetark 		= '$trow[osoitetark]',
						postino 		= '$trow[postino]',
						postitp 		= '$trow[postitp]',
						maa 			= '$trow[maa]',
						toim_maa		= '$trow[verovelvollinen]',
						ultilno_maa		= '$trow[ultilno_maa]',
						vienti 			= 'A',
						ebid 			= '',
						tila 			= 'H',
						alatila 		= 'M',
						swift 			= '$trow[swift]',
						pankki1 		= '$trow[pankki1]',
						pankki2 		= '$trow[pankki2]',
						pankki3 		= '$trow[pankki3]',
						pankki4 		= '$trow[pankki4]',
						vienti_kurssi 	= '1',
						laatija 		= '$kukarow[kuka]',
						luontiaika 		= now(),
						tapvm			= current_date,
						erpcm			= date_add(current_date, INTERVAL $erpaivia day),
						liitostunnus 	= '$trow[tunnus]',
						hyvaksynnanmuutos = '$trow[oletus_hyvaksynnanmuutos]',
						suoraveloitus 	= '',
						tilaustyyppi	= 'M',
						laskunro		= '$maxmatkalasku'";
			$result = pupe_query($query);
			$tilausnumero = mysql_insert_id();

			//	Tänne voisi laittaa myös tuon asiakasidn jos tästä voitaisiin lähettää myös lasku asiakkaalle
			$query = "	INSERT into laskun_lisatiedot set
						yhtio 				= '$kukarow[yhtio]',
						laskutus_nimi    	= '$asiakasrow[nimi]',
						laskutus_nimitark	= '".t("Matkalasku")."',
						laskutus_osoite		= '$asiakasrow[osoite]',
						laskutus_postino	= '$asiakasrow[postino]',
						laskutus_postitp	= '$asiakasrow[postitp]',
						laskutus_maa		= '$asiakasrow[maa]',
						otunnus 			= '$tilausnumero'";
			$result = pupe_query($query);

			$tee = "MUOKKAA";
		}
		else {
			$tee = "";
		}
	}
	else {
		echo "<font class='error'>".t("VIRHE: Anna asiakkaan nimi")."</font><br>";
		$tee="";
	}
}

if ($tee != "") {
	$muokkauslukko = TRUE;

	if ((int) $tilausnumero == 0) {
		echo "<font class='error'>".t("VIRHE: Matkalaskun numero kateissa")."!</font>";
		$tee = "";
	}
	else {

		$query = "	SELECT lasku.*,
					laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa,
					toimi.kustannuspaikka, toimi.kohde, toimi.projekti
					FROM lasku
					LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					JOIN toimi on (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
					WHERE lasku.tunnus = '$tilausnumero'
					AND lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tilaustyyppi IN ('M', '')
					AND lasku.tila IN ('H','Y','M','P','Q')";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("VIRHE: Matkalaskun numero kateissa")."!</font>";
			$tee = "";
		}
		else {
			$laskurow = mysql_fetch_assoc($result);

			if ($laskurow["tila"] == "H" and $laskurow["h1time"] == "0000-00-00 00:00:00" and ($laskurow["toim_ovttunnus"] == $kukarow["kuka"] or $toim == "SUPER") and strtotime($laskurow["tapvm"]) > strtotime($yhtiorow["ostoreskontrakausi_alku"]) and strtotime($laskurow["tapvm"]) < strtotime($yhtiorow["ostoreskontrakausi_loppu"])) {
				$muokkauslukko = FALSE;
			}
			else {
				$tee = "MUOKKAA";
			}

			function lisaa_paivaraha($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku, $loppu, $kommentti, $kustp, $kohde, $projekti) {
				return lisaa_kulurivi($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku, $loppu, "", 0, "", "", $kommentti, "A", $kustp, $kohde, $projekti);
			}

			function lisaa_kulu($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $kpl, $vero, $hinta, $kommentti, $maa, $kustp, $kohde, $projekti) {
				return lisaa_kulurivi($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, "", "", $kpl, $vero, $hinta, $maa, $kommentti, "B", $kustp, $kohde, $projekti);
			}

			function lisaa_kulurivi($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku, $loppu, $kpl, $vero, $hinta, $maa, $kommentti, $tyyppi, $kustp, $kohde, $projekti) {
				global $yhtiorow, $kukarow, $toim, $muokkauslukko, $laskurow;

				if ($muokkauslukko) {
					echo "<font class='error'>".t("VIRHE: Matkalaskua ei voi muokata")."!</font><br>";
					return false;
				}

				$query = "	SELECT *
							from tuote
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tuoteno'
							and tuotetyyppi = '$tyyppi'
							and status != 'P'";
				$tres = pupe_query($query);

				if (mysql_num_rows($tres) != 1) {
					echo "<font class='error'>".t("VIRHE: Viranomaistuote puuttuu")." (2) $tuoteno</font><br>";
					return;
				}
				else {
					$trow = mysql_fetch_assoc($tres);
				}

				$tyyppi = $trow["tuotetyyppi"];
				$tuoteno_array = array();
				$errori = "";

				if ($tyyppi == "A") {

					list($alkupaiva, $alkuaika) = explode(" ", $alku);
					list($alkuvv, $alkukk, $alkupp) = explode("-", $alkupaiva);
					list($alkuhh, $alkumm) = explode(":", $alkuaika);

					list($loppupaiva, $loppuaika) = explode(" ", $loppu);
					list($loppuvv, $loppukk, $loppupp) = explode("-", $loppupaiva);
					list($loppuhh, $loppumm) = explode(":", $loppuaika);

					/*
						Päivärahoilla ratkaistaan päivät
						Samalla oletetaan että puolipäiväraha on aina P+tuoteno
					*/

					//	Lasketaan tunnit
					$alkupp = sprintf("%02d", $alkupp);
					$alkukk = sprintf("%02d", $alkukk);
					$alkuvv = (int) $alkuvv;
					$alkuhh = sprintf("%02d", $alkuhh);
					$alkumm = sprintf("%02d", $alkumm);

					$loppupp = sprintf("%02d", $loppupp);
					$loppukk = sprintf("%02d", $loppukk);
					$loppuvv = (int) $loppuvv;
					$loppuhh = sprintf("%02d", $loppuhh);
					$loppumm = sprintf("%02d", $loppumm);

					if (($alkupp >= 1 and $alkupp <= 31) and ($alkukk >= 1 and $alkukk <= 12) and $alkuvv > 0 and ($alkuhh >= 0 and $alkuhh <= 24) and ($loppupp >= 1 and $loppupp <= 31) and ($loppukk >= 1 and $loppukk <= 12) and $loppuvv > 0 and ($loppuhh >= 0 and $loppuhh <= 24)) {
						$alku = mktime($alkuhh, $alkumm, 0, $alkukk, $alkupp, $alkuvv);
						$loppu = mktime($loppuhh, $loppumm, 0, $loppukk, $loppupp, $loppuvv);

						//	Tarkastetaan että tällä välillä ei jo ole jotain arvoa
						//	HUOM: Koitetaan tarkastaa kaikki käyttäjän matkalaskut..
						$query = "	SELECT laskun_lisatiedot.laskutus_nimi,
									lasku.summa,
									lasku.tapvm tapvm,
									tilausrivi.nimitys,
									tilausrivi.tuoteno,
									date_format(tilausrivi.kerattyaika, '%d.%m.%Y') kerattyaika,
									date_format(tilausrivi.toimitettuaika, '%d.%m.%Y') toimitettuaika,
									tilausrivi.kommentti kommentti
									FROM lasku
									LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
									LEFT JOIN tilausrivi on tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi 	= 'M'
									JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuotetyyppi IN ('A')
									WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
									and lasku.tilaustyyppi	= 'M'
									and lasku.tila IN ('H','Y','M','P','Q')
									and lasku.liitostunnus = '$laskurow[liitostunnus]'
									and ((tilausrivi.kerattyaika >= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and tilausrivi.kerattyaika < '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm') or
										(tilausrivi.kerattyaika < '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and tilausrivi.toimitettuaika > '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm') or
										(tilausrivi.toimitettuaika > '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and tilausrivi.toimitettuaika <= '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm'))
									GROUP BY tilausrivi.otunnus";
						$result = pupe_query($query);

						if (mysql_num_rows($result) > 0) {
							$errori .= "<font class='error'>".t("VIRHE: Päivämäärä on menee päällekkäin toisen matkalaskun kanssa")."</font><br>";

							$errori .= "<table><tr><th>".t("Asiakas")."</th><th>".t("viesti")."</th><th>".t("Summa/tapvm")."</th><th>".t("Tuote")."</th><th>".t("Ajalla")."</th><th>".t("Viesti")."</th></tr>";

							while ($erow = mysql_fetch_assoc($result)) {
								$errori .=  "<tr>
												<td>$erow[laskutus_nimi]</td>
												<td>$erow[viesti]</td>
												<td>$erow[summa]@$erow[tapvm]</td>
												<td>$erow[tuoteno] - $erow[nimitys]</td>
												<td>$erow[kerattyaika] - $erow[toimitettuaika]</td>
												<td>$erow[kommentti]</td>
											</tr>";
							}
							$errori .= "</table><br>";
						}

						if ($loppuvv.$loppukk.$loppupp > date("Ymd")) {
							$errori .= "<font class='error'>".t("VIRHE: Matkalaskua ei voi tehdä etukäteen!")."</font><br>";
						}

						$paivat = $puolipaivat = $ylitunnit = $tunnit = 0;

						//	montako tuntia on oltu matkalla?
						$tunnit = ($loppu - $alku) / 3600;
						$paivat = floor($tunnit / 24);

						$alkuaika = "$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm:00";
						$loppuaika = "$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm:00";

						$ylitunnit = $tunnit - ($paivat * 24);

						if (($ylitunnit > 10 and $paivat == 0) or ($ylitunnit > 6 and $paivat > 0)) {
							$paivat++;
						}
						elseif (($ylitunnit > 6 and $paivat == 0) or ($ylitunnit > 2 and $paivat > 0)) {

							//	Tarkastetaan että päivärahalle on puolipäiväraha
							$query = "	SELECT *
										FROM tuote
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuotetyyppi = '$tyyppi'
										AND tuoteno = 'P$tuoteno'
										AND status != 'P'";
							$tres2 = pupe_query($query);

							if (mysql_num_rows($tres2) != 1) {
								$errori .= "<font class='error'>".t("VIRHE: Viranomaistuote puuttuu. Puolipäivärahaa ei voitu lisätä")."! P$tuoteno</font><br>";
							}
							else {
								$trow2 = mysql_fetch_assoc($tres2);
								$puolipaivat++;

								$tuoteno_array[$trow2["tuoteno"]]	=$trow2["tuoteno"];
								$kpl_array[$trow2["tuoteno"]]		=$puolipaivat;

								$hinta = $trow2["myyntihinta"];
								$hinta_array[$trow2["tuoteno"]]		= $hinta;

								$selite .= "<br>$trow2[tuoteno] - $trow2[nimitys] $puolipaivat kpl á $hinta";
							}
						}
						elseif ($ylitunnit <= 10 and $trow["vienti"] != "FI" and $paivat == 0) {
							$errori .= "<font class='error'>".t("VIRHE: Ulkomaanpäivärahalla on oltava vähintään 10 tuntia")."</font><br>";
							//	Tänne pitäisi joskus koodata se puolikas ulkomaanpäiväraha..
						}
						elseif ($paivat == 0 and $puolipaivat == 0) {
							$errori .= "<font class='error'>".t("VIRHE: Liian lyhyt aikaväli")."</font><br>";
						}

						//	Lisätään myös saldoton isatuote jotta tiedämme mistä puolipäiväraha periytyy!
						if ($paivat > 0 or $puolipaivat > 0) {
							$tuoteno_array[$tuoteno] = $tuoteno;
							$kpl_array[$tuoteno] = $paivat;
							$hinta = $trow["myyntihinta"];
							$hinta_array[$trow["tuoteno"]] = $hinta;
							$selite = "$trow[tuoteno] - $trow[nimitys] $paivat kpl á $hinta";
						}

						$selite .= "<br>Ajalla: $alkupp.$alkukk.$alkuvv klo. $alkuhh:$alkumm - $loppupp.$loppukk.$loppuvv klo. $loppuhh:$loppumm";

						//echo "SAATIIN päivarahoja: $paivat puolipäivärahoja: $puolipaivat<br>";
					}
					else {
						$errori .= "<font class='error'>".t("VIRHE: Päivärahalle on annettava alku ja loppuaika")."</font><br>";
					}
				}
				elseif ($tyyppi == "B") {
					if ($kpl == 0) {
						$errori .= "<font class='error'>".t("VIRHE: kappalemäärä on annettava")."</font><br>";
					}

					if ($kommentti == "" and $trow["kommentoitava"] != "") {
						$errori .= "<font class='error'>".t("VIRHE: Kululle on annettava selite")."</font><br>";
					}

					if ($trow["myyntihinta"]>0) {
						$hinta=$trow["myyntihinta"];
					}

					$hinta = str_replace ( ",", ".", $hinta);
					if ($hinta <= 0) {
						$errori .= "<font class='error'>".t("VIRHE: Kulun hinta puuttuu")."</font><br>";
					}

					$tuoteno_array[$trow["tuoteno"]] = $trow["tuoteno"];
					$kpl_array[$trow["tuoteno"]] = $kpl;
					$hinta_array[$trow["tuoteno"]] = $hinta;
					$selite = "$trow[tuoteno] - $trow[nimitys] $kpl kpl á $hinta";
				}

				//	poistetan return carriage ja newline -> <br>
				$kommentti = str_replace("\n","<br>",str_replace("\r","",$kommentti));
				if ($kommentti != "") {
					$selite .="<br><i>$kommentti</i>";
				}

				//	Lisätään annetut rivit
				$perheid = $isatunnus = 0;

				if ($errori == "") {
					$tuoteno_array = array_reverse($tuoteno_array);
					foreach($tuoteno_array as $lisaa_tuoteno) {

						//	Haetaan tuotteen tiedot
						$query = "	SELECT *
									FROM tuote
									JOIN tili ON (tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino)
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									and tuotetyyppi = '$tyyppi'
									and tuoteno = '$lisaa_tuoteno'
									and status != 'P'
									and tuote.tilino != ''";
						$tres = pupe_query($query);

						if (mysql_num_rows($tres) == 1) {
							$trow = mysql_fetch_assoc($tres);
							$kpl = str_replace(",",".",$kpl_array[$trow["tuoteno"]]);
							$hinta = str_replace(",",".",$hinta_array[$trow["tuoteno"]]);
							$rivihinta = round($kpl*$hinta,$yhtiorow['hintapyoristys']);

							//	Ratkaistaan alv..
							if ($tyyppi == "B") {
								//	Haetaan tuotteen oletusalv jos ollaan ulkomailla, tälläin myös kotimaan alv on aina zero
								if ($maa != "" and $maa != $yhtiorow["maa"]) {
									if ($alvulk == "") {
										$query = "	SELECT *
													FROM tuotteen_alv
													WHERE yhtio = '$kukarow[yhtio]'
													AND maa = '$maa'
													AND tuoteno = '$tuoteno'
													LIMIT 1";
										$alhire = pupe_query($query);
										$alvrow = mysql_fetch_assoc($alhire);
										$alvulk = $alvrow["alv"];
									}
									$vero = 0;
								}
							}
							else {
								$vero = 0;
							}

							//	Otetaan korvaava tilinumero
							if ($tilino == "" or $toim != "SUPER") {
								$tilino = $trow["tilino"];
							}

							$query = "	INSERT into tilausrivi set
										hyllyalue   = '0',
										hyllynro    = '0',
										hyllytaso   = '0',
										hyllyvali   = '0',
										laatija 	= '$kukarow[kuka]',
										laadittu 	= now(),
										yhtio 		= '$kukarow[yhtio]',
										tuoteno 	= '$lisaa_tuoteno',
										varattu 	= '0',
										yksikko 	= '$trow[yksikko]',
										kpl 		= '$kpl',
										tilkpl 		= '$kpl',
										ale1 		= '0',
										alv 		= '$vero',
										netto		= 'N',
										hinta 		= '$hinta',
										rivihinta 	= '$rivihinta',
										otunnus 	= '$tilausnumero',
										tyyppi 		= 'M',
										toimaika 	= '',
										kommentti 	= '".mysql_real_escape_string($kommentti)."',
										var 		= '',
										try			= '$trow[try]',
										osasto		= '$trow[osasto]',
										perheid		= '$perheid',
										perheid2	= '$perheid2',
										tunnus 		= '$rivitunnus',
										nimitys 	= '$trow[nimitys]',
										kerattyaika = '$alkuaika',
										toimitettuaika = '$loppuaika'";
							$insres = pupe_query($query);
							$lisatty_tun = mysql_insert_id();

							if ($isatunnus == 0) $isatunnus = $lisatty_tun;

							//	Jos meillä on splitattu rivi niin pidetään nippu kasassa
							if ($perheid == 0 and count($tuoteno_array) > 1) {
								$perheid = $lisatty_tun;

								$query = " 	UPDATE tilausrivi SET perheid = '$lisatty_tun'
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus = '$perheid'";
								$updres = pupe_query($query);
							}

							if ((int) $perheid2 == 0) {
								$perheid2 = $lisatty_tun;
								$query = " 	UPDATE tilausrivi SET perheid2 = '$lisatty_tun'
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus = '$perheid2'";
								$updres = pupe_query($query);
							}

							//	Jos muokattiin perheen isukkia halutaan oikea kommentti!
							if ((int) $perheid2 == 0 or $perheid2 == $lisatty_tun) {
								$tapahtumarow["kommentti"] = $kommentti;
							}

							$rivitunnus = 0;
							$summa += $rivihinta;
						}
						else {
							echo "<font class='error'>".t("VIRHE: Viranomaistuote puuttuu")." (1) $lisaa_tuoteno</font><br>";
						}
					}

					/*
						Hoidetaan kirjanpito
						copypastea teetiliointi.inc
					*/

					$summa = round($summa,2);

					// Netotetaan alvi
					if ($vero != 0) {
						$alv = round($summa - $summa / (1 + ($vero / 100)), 2);
						$summa -= $alv;
					}

					if ($kpexport != 1 and strtoupper($yhtiorow['maa']) == 'FI') $tositenro = 0; // Jos tätä ei tarvita

					if ($toim == "SUPER" and $tilino > 0 and $trow["tilino"] != $tilino) {
						echo "<font class='message'>".t("HUOM: tiliöidään poikkeavalle tilille '$tilino'<br>");
						$trow["tilino"] = $tilino;
					}

					list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tilino, $kustp, $kohde, $projekti);

					// Kulutili
					$query = "	INSERT into tiliointi set
								yhtio 		= '$kukarow[yhtio]',
								ltunnus 	= '$tilausnumero',
								tilino 		= '{$tilino}',
								kustp    	= '{$kustp_ins}',
								kohde	 	= '{$kohde_ins}',
								projekti 	= '{$projekti_ins}',
								tapvm 		= '$laskurow[tapvm]',
								summa 		= '$summa',
								vero 		= '$vero',
								selite 		= '".mysql_real_escape_string($selite)."',
								lukko 		= '',
								tosite 		= '$tositenro',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now()";
					$result = pupe_query($query);
					$isa = mysql_insert_id(); // Näin löydämme tähän liittyvät alvit....

					if ($vero != 0) {
						// Alv
						$query = "	INSERT into tiliointi set
									yhtio 		= '$kukarow[yhtio]',
									ltunnus 	= '$tilausnumero',
									tilino 		= '$yhtiorow[alv]',
									kustp 		= 0,
									kohde 		= 0,
									projekti 	= 0,
									tapvm 		= '$laskurow[tapvm]',
									summa 		= '$alv',
									vero 		= 0,
									selite 		= '".mysql_real_escape_string($selite)."',
									lukko 		= '1',
									laatija 	= '$kukarow[kuka]',
									laadittu 	= now(),
									aputunnus 	= $isa";
						$result = pupe_query($query);
					}

					//	Laitetaan lisätietoihin ainakin se ulkomaanalv jne..
					$query  = "	SELECT *
								FROM tilausrivin_lisatiedot
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tilausrivitunnus = '$isatunnus'";
					$lisatied_res = pupe_query($query);

					if (mysql_num_rows($lisatied_res) > 0) {;
						$query = "	UPDATE tilausrivin_lisatiedot SET ";
						$where = "	WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus = '$isatunnus'";
					}
					else {
						$query = "	INSERT INTO tilausrivin_lisatiedot SET
									yhtio				= '$kukarow[yhtio]',
									luontiaika			= now(),
									tilausrivitunnus	= '$isatunnus',
									laatija 			= '$kukarow[kuka]',";
						$where = "";
					}

					$query .= "	tiliointirivitunnus = '$isa',
								kulun_kohdemaa		= '$maa',
								kulun_kohdemaan_alv	= '$alvulk',
								muutospvm			= now(),
								muuttaja			= '$kukarow[kuka]'";
					$query  = $query.$where;
					$updres = pupe_query($query);

					//	Fiksataan ostovelka
					korjaa_ostovelka($tilausnumero);
				}

				return $errori;
			}

			function korjaa_ostovelka ($tilausnumero) {
				global $yhtiorow, $kukarow, $toim, $muokkauslukko, $laskurow;

				if ($muokkauslukko or $tilausnumero != $laskurow["tunnus"]) {
					echo "<font class='error'>".t("VIRHE: Matkalaskua ei voi muokata")."!</font><br>";
					return false;
				}

				$debug = 0;

				if ($debug == 1) echo "Korjataan ostovelka laskulle $tilausnumero<br>";

				if ($yhtiorow["ostovelat"] == "") {
					echo t("VIRHE: Yhtiön ostovelkatili puuttuu")."!<br>";
					return false;
				}

				$query = "	SELECT sum((-1*summa)) summa, count(*) kpl
							FROM tiliointi
							WHERE yhtio  = '$kukarow[yhtio]'
							AND ltunnus  = '$tilausnumero'
							AND korjattu = ''
							AND tilino  != '$yhtiorow[ostovelat]'";
				$summares = pupe_query($query);
				$summarow = mysql_fetch_assoc($summares);

				if ($yhtiorow["kirjanpidon_tarkenteet"] == "K") {
					$query = "	SELECT kustp, kohde, projekti
								FROM tiliointi
								WHERE yhtio  = '$kukarow[yhtio]'
								AND ltunnus  = '$tilausnumero'
								AND korjattu = ''
								AND tilino not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
								ORDER BY abs(summa) DESC
								LIMIT 1";
					$kpres = pupe_query($query);
					$kprow = mysql_fetch_assoc($kpres);

					list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["ostovelat"], $kprow["kustp"], $kprow["kohde"], $kprow["projekti"]);
				}
				else {
					list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["ostovelat"]);
				}

				//	Onko meillä jo ostovelkatiliöinti vai perustetaanko uusi?
				$query = "	SELECT tunnus
							FROM tiliointi
							WHERE yhtio  = '$kukarow[yhtio]'
							and ltunnus  = '$tilausnumero'
							and tilino   = '$yhtiorow[ostovelat]'
							and korjattu = ''";
				$velkares = pupe_query($query);

				if (mysql_num_rows($velkares) == 1) {
					$velkarow = mysql_fetch_assoc($velkares);
					if ($debug == 1) echo "Löydettiin ostovelkatiliöinti tunnuksella $velkarow[tunnus] tiliöintejä ($summarow[kpl]) kpl<br>";

					$query = "	UPDATE tiliointi SET
								summa 		= '$summarow[summa]',
								tapvm 		= '$laskurow[tapvm]',
								kustp    	= '{$kustp_ins}',
								kohde	 	= '{$kohde_ins}',
								projekti 	= '{$projekti_ins}',
								vero 		= 0,
								tosite 		= '$tositenro'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$velkarow[tunnus]'";
					$updres = pupe_query($query);
				}
				else {
					if ($debug == 1) echo "Luodaan uusi ostovelkatiliöinti<br>";

					// Ostovelka
					$query = "	INSERT into tiliointi SET
								yhtio 		= '$kukarow[yhtio]',
								ltunnus 	= '$tilausnumero',
								lukko 		= '1',
								tilino 		= '$yhtiorow[ostovelat]',
								summa 		= '$summarow[summa]',
								kustp    	= '{$kustp_ins}',
								kohde	 	= '{$kohde_ins}',
								projekti 	= '{$projekti_ins}',
								tapvm 		= '$laskurow[tapvm]',
								vero 		= 0,
								tosite 		= '$tositenro',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now()";
					$updres = pupe_query($query);
				}

				if ($debug == 1) echo "Korjattiin ostovelkatiliöinti uusi summa on $summarow[summa]";

				//	Päivitetään laskun summa
				if ($laskurow["tilaustyyppi"] == "M") {
					$query = "	UPDATE lasku
								set summa = '".(-1 * $summarow["summa"])."'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$tilausnumero'";
					$updres = pupe_query($query);
				}

				//	Ollaanko vielä synkissä?
				$query = "	SELECT sum(rivihinta) summa
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilausnumero'
							and tyyppi = 'M'";
				$result = pupe_query($query);
				$rivisumma = mysql_fetch_assoc($result);

				$ero = round($rivisumma["summa"], 2) + round($summarow["summa"], 2);

				if ($ero <> 0) {
					echo "	<font class='error'>".t("VIRHE: Matkalasku ja kirjanpito ei täsmää!!!")."</font><br>
							<font class='message'>".t("Heitto on")." $ero [rivit $rivirow[summa]] (kp $summarow[summa])</font><br>";
				}
			}

			function erittele_rivit($tilausnumero) {
				global $yhtiorow, $kukarow, $toim, $muokkauslukko, $laskurow, $verkkolaskuvirheet_ok;

				if ($muokkauslukko or $tilausnumero != $laskurow["tunnus"]) {
					echo "<font class='error'>".t("VIRHE: Matkalaskua ei voi muokata")."!</font><br>";
					return false;
				}

				if ($laskurow["ebid"] == "") {
					return false;
				}

				$query = "	SELECT tunnus
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$laskurow[liitostunnus]'
							and laskun_erittely != ''";
				$toimires = pupe_query($query);

				if (mysql_num_rows($toimires) == 0) {
					echo t("VIRHE: Toimittajan laskuja ei saa eritellä")."!";
					return false;
				}

				$query = "	SELECT tunnus
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilausnumero'
							and tyyppi != 'D'
							LIMIT 1";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					return true;
				}

				$query = " 	UPDATE tiliointi
							SET korjattu = '$kukarow[kuka]',
							korjausaika  = now()
							WHERE yhtio = '$kukarow[yhtio]'
							and ltunnus = '$tilausnumero'";
				$result = pupe_query($query);

				$file = $verkkolaskuvirheet_ok."/$laskurow[ebid]";

				if (file_exists($file)) {

					// luetaan file muuttujaan
					$xmlstr = file_get_contents($file);

					if ($xmlstr === FALSE) {
						echo ("Tiedosto $file luku epäonnistui!\n");
						return ("Tiedosto $file luku epäonnistui!");
					}

					// luetaan sisään xml
					$xml = simplexml_load_string($xmlstr);

					require("inc/verkkolasku-in-pupevoice.inc");

					if (count($tuotetiedot) > 0) {
						for ($i=0; $i < count($tuotetiedot); $i++) {

							//	Näyttäisikö tämä korkoriviltä, otetaan se talteen ja laitetaan myös riville?
							if (preg_match("/KORKO\s+[0-9]{2}\.[0-9]{2}\.\s+-\s+[0-9]{2}\.[0-9]{2}\./", $rtuoteno[$i]["riviinfo"])) {
								$korkosumma = (float) $rtuoteno[$i]["rivihinta"];
							}

							if ($rtuoteno[$i]["kpl"] > 0) {

								$kpl = (float) $rtuoteno[$i]["kpl"];
								$hinta = (float) $rtuoteno[$i]["rivihinta"];

								if ($hinta < 0) {
									$kpl = $kpl * -1;
									$hinta = $hinta * -1;
								}

								$kommentti = "";
								$rivihinta = (float) $kpl * (float) $hinta;

								if ($rtuoteno[$i]["laskutettuaika"] != "") {
									$kommentti .= "Tapahtuma-aika: ".preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\$3.\$2. \$1", $rtuoteno[$i]["laskutettuaika"]);
								}

								$kommentti .= "<br>Tapahtuman selite: ".$rtuoteno[$i]["riviinfo"];

								if (preg_match("/([A-Z]{3})\s*([0-9\.,]*)/", $rtuoteno[$i]["riviviite"], $match)) {
									$kommentti .= "<br>Alkupeärinen summa: $match[2] $match[1] ($hinta $yhtiorow[valkoodi])";
								}

								$kommentti = preg_replace("/\.{2,}/", "", $kommentti);

								//	Laitetaan tilausrivi kantaan
								$query = "	INSERT into tilausrivi set
											hyllyalue   = '0',
											hyllynro    = '0',
											hyllytaso   = '0',
											hyllyvali   = '0',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now(),
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$tno',
											varattu 	= '0',
											yksikko 	= '',
											kpl 		= '$kpl',
											tilkpl 		= '$kpl',
											ale1 		= '0',
											alv 		= '0',
											netto		= 'N',
											hinta 		= '$hinta',
											rivihinta 	= '$rivihinta',
											otunnus 	= '$tilausnumero',
											tyyppi 		= 'M',
											toimaika 	= '',
											kommentti 	= '".mysql_real_escape_string($kommentti)."',
											var 		= '',
											try			= '$trow[try]',
											osasto		= '$trow[osasto]',
											perheid		= '0',
											perheid2	= '0',
											tunnus 		= '0',
											nimitys 	= '',
											kerattyaika = '$alkuaika',
											toimitettuaika = '$loppuaika'";
								$insres = pupe_query($query);
								$lisatty_tun = mysql_insert_id();

								//	Päivitetään perheid2
								$query = " 	UPDATE tilausrivi
											set perheid2 = '$lisatty_tun'
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus  = '$lisatty_tun'";
								$updres = pupe_query($query);

								//	Tehdään oletustiliöinti
								$query = "	INSERT into tiliointi set
											yhtio 		= '$kukarow[yhtio]',
											ltunnus 	= '$tilausnumero',
											tilino 		= '$yhtiorow[selvittelytili]',
											kustp 		= 0,
											kohde 		= 0,
											projekti 	= 0,
											tapvm 		= '$laskurow[tapvm]',
											summa 		= '$rivihinta',
											vero 		= 0,
											selite 		= 'EC selvittely',
											lukko 		= '1',
											tosite 		= '$tositenro',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$result = pupe_query($query);
								$isa = mysql_insert_id();

								$query = "	INSERT INTO tilausrivin_lisatiedot SET
											yhtio				= '$kukarow[yhtio]',
											luontiaika			= now(),
											tilausrivitunnus	= '$lisatty_tun',
											laatija 			= '$kukarow[kuka]',
											tiliointirivitunnus = '$isa',
											kulun_kohdemaa		= '$yhtiorow[maa]',
											kulun_kohdemaan_alv	= '',
											muutospvm			= now(),
											muuttaja			= '$kukarow[kuka]'";
								$updres = pupe_query($query);

								korjaa_ostovelka($tilausnumero);
							}
						}
					}

					return $korkosumma;
				}
				else {
					return false;
				}
			}
		}
	}
}

if ($tee == "POISTA") {
	$tunnus  = $tilausnumero;
	$tee 	 = "D";
	$kutsuja = "MATKALASKU";

    require ("hyvak.php");

	$tee 		  = "";
	$tunnus 	  = 0;
	$tilausnumero = 0;
}

if ($tee == "UUDELLEENKASITTELE" and $toim == "SUPER") {

	echo "<font class='message'>".t("Poistetaan vanhat tiliöinnit ja laskut").".</font><br><br>";

	$query = "	DELETE
				FROM tilausrivi
				WHERE yhtio = '$kukarow[yhtio]'
				AND otunnus = '$tilausnumero'
				AND tyyppi = 'M'";
	$result = pupe_query($query);

	$tee = "ERITTELE";
}

if ($tee == "ERITTELE") {

	//	Onko tässä jo jotain tilausrivejä?
	$query = "	SELECT tunnus
				FROM tilausrivi
				WHERE yhtio = '$kukarow[yhtio]'
				and otunnus = '$tilausnumero'
				and tyyppi = 'M'
				LIMIT 1";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {

		$hinta = (float) erittele_rivit($tilausnumero);

		//	Lisätään korko ja korjataan ostovelka
		if ($hinta > 0) {
			$tuoteno = "KORKO";
			$lisaa 	 = "JOO";
			$kpl 	 = 1;
			$tyyppi  = "B";
		}
	}

	$tee = "MUOKKAA";
}

if ($tee == "TUO_KALENTERISTA") {

	//	Onko tässä jo jotain tilausrivejä?
	$query = "	SELECT kalenteri.*, asiakas.maa, concat_ws(' ', asiakas.nimi, asiakas.nimitark) asiakas
				FROM kalenteri
				LEFT JOIN asiakas ON asiakas.yhtio = kalenteri.yhtio and asiakas.tunnus = kalenteri.liitostunnus
				WHERE kalenteri.yhtio = '$kukarow[yhtio]' and (kentta10 = 'M' or kentta10 = '$tilausnumero') and kalenteri.kuka = '$kukarow[kuka]' and pvmloppu <= now()";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_assoc($result)) {

			$tapahtuma = "$row[tapa]: $row[pvmalku] - $row[pvmloppu]";

			if ($row["maa"] == "") $row["maa"] = "FI";

			$query = "	SELECT tuoteno
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]'
						and tuotetyyppi = 'A'
						and status != 'P'
						and vienti = '$row[maa]'
						and tuoteno NOT LIKE ('PPR%')";
			$tres = pupe_query($query);

			if (mysql_num_rows($tres) > 0) {
				$trow = mysql_fetch_assoc($tres);
				$errori = lisaa_paivaraha($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino,$trow["tuoteno"], $row["pvmalku"], $row["pvmloppu"], "Asiakas: $row[asiakas]\nTapahtuma: $row[tapa]", "", "", "");

				if ($errori == "") {
					$query = "	UPDATE kalenteri SET
								kentta10 = '$tilausnumero'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$row[tunnus]'";
					$updres = pupe_query($query);
				}
				else {
					echo "<font class='message'>".t("Tapahtumaa ei voitu siirtää.")." '$tapahtuma'</font><br>$errori<br><br>";
				}
			}
			else {
				echo "<font class='error'>".t("Siirto epäonnistui sopivaa päivärahaa ei löytynyt.")." '$tapahtuma'</font><br>";
			}
		}
	}
	else {
		echo "<font class='error'>".t("Sinulla ei ollut siirrettäviä kalenteritapahtumia.")."</font><br><br>";
	}

	$tee = "MUOKKAA";
}

if ($tee == "POIMI_KALENTERISTA") {
	function erittele_rivit($tilausnumero) {
		global $kukarow, $yhtiorow, $verkkolaskuvirheet_ok;

		$query = "	SELECT *
					FROM kalenteri
					WHERE yhtio='$kukarow[yhtio]' and kentta10='M'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);

		$query = " 	DELETE
					FROM tiliointi
					WHERE yhtio='$kukarow[yhtio]' and ltunnus = '$tilausnumero'";
		$result = pupe_query($query);

		$file = $verkkolaskuvirheet_ok."/$laskurow[ebid]";
	}
}

if ($tee == "TALLENNA") {
	if ((int) $tilausnumero == 0) {
		echo "<font class='error'>".t("Matkalaskun numero puuttuu")."</font>";
		$tee = "";
	}
	else {
		//	Koitetaan tallennella kuva
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			if ($kuvaselite == "") {
				$errormsg = t("Anna kuvalle selite");
			}
			else {
				//	chekataan erroit
				switch ($_FILES['userfile']['error']) {
					case 1:
					case 2:
						$errormsg .= t("Kuva on liian suuri, suurin sallittu koko on")." ".ini_get('post_max_size');
						break;
					case 3:
						$errormsg .= t("Kuvan lataus keskeytyi")."!";
						break;
					case 6:
					case 7:
					case 8:
						$errormsg .= t("Tallennus epäonnistui")."!";
						break;
					case 0:
						//	OK tallennetaan

						// otetaan file extensio
						$path_parts = pathinfo($_FILES['userfile']['name']);
						$ext = $path_parts['extension'];
						if (strtoupper($ext) == "JPEG") $ext = "jpg";

						$query = "SHOW variables like 'max_allowed_packet'";
						$result = pupe_query($query);
						$varirow = mysql_fetch_row($result);

						if ($_FILES['userfile']['size'] > $varirow[1]) {
							$errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! ($varirow[1]) </font>";
						}
						else {
							// lisätään kuva
							$kuva = tallenna_liite("userfile", "lasku", $tilausnumero, $kuvaselite, "", 0, 0, "");

							$kuvaselite = "";
						}
						break;
					}
			}

			if ($errormsg != "") {
				echo "<font class='error'>$errormsg</font><br>";
			}
		}

		$query = "	UPDATE lasku SET
					viite = '$viesti'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tilausnumero'";
		$updres = pupe_query($query);

		$laskurow["viite"] = $viesti;

		$tee = "MUOKKAA";
	}
}

if ($tee == "MUOKKAA") {

	// Onko tosite liitetty keikkaan
	$query = "	SELECT nimi, laskunro
				from lasku
				where yhtio = '$kukarow[yhtio]'
				and tila = 'K'
				and vanhatunnus = '$laskurow[tunnus]'";
	$keikres = pupe_query($query);

	if (mysql_num_rows($keikres) > 0) {
		$keikrow = mysql_fetch_assoc($keikres);

		echo "<br><font class='message'>".t("Lasku on liitetty saapumiseen, alv tiliöintejä ei voi muuttaa")."! ".t("Saapuminen").": $keikrow[nimi] / $keikrow[laskunro]</font>";
	}

	if ($poistakuva > 0) {
		$query = "	DELETE from liitetiedostot
					WHERE yhtio 		= '$kukarow[yhtio]'
					and liitos			= 'lasku'
					and liitostunnus	= '$tilausnumero'
					and tunnus 			= '$poistakuva'";
		$result = pupe_query($query);

		if (mysql_affected_rows() == 0) {
			echo "<font class='error'>".t("VIRHE: Poistettavaa liitetiedostoa ei löydy")."!</font><br>";
		}
	}

	if ($kuivat != "JOO") {
		if ($perheid2 > 0) {
			$query	= "	SELECT tilausrivi.*, tuote.tuotetyyppi, tuote.tilino,
						tilausrivin_lisatiedot.tiliointirivitunnus,
						tilausrivin_lisatiedot.kulun_kohdemaa,
						tilausrivin_lisatiedot.kulun_kohdemaan_alv,
						kustp.tunnus kustp,
						kohde.tunnus kohde,
						projekti.tunnus projekti,
						if(tuotetyyppi='A', tuote.vienti, tilausrivin_lisatiedot.kulun_kohdemaa) vienti
						FROM tilausrivi use index (PRIMARY)
						LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
						LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
						LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
						LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
						LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilausnumero'
						and perheid2 = $perheid2
						and tilausrivi.tunnus = perheid2";
			$abures = pupe_query($query);
			$tapahtumarow = mysql_fetch_assoc($abures);
		}

		//	Muokataan kuluriviä, poistetaan koko nippu ja laitetaan muokattavaksi
		if ($rivitunnus > 0) {
			$query	= "	SELECT tilausrivi.*, tuote.tuotetyyppi, tuote.tilino,
						tilausrivin_lisatiedot.tiliointirivitunnus,
						tilausrivin_lisatiedot.kulun_kohdemaa,
						tilausrivin_lisatiedot.kulun_kohdemaan_alv,
						kustp.tunnus kustp,
						kohde.tunnus kohde,
						tiliointi.tilino tiliointitili,
						tiliointi.vero tiliointialv,
						projekti.tunnus projekti
						FROM tilausrivi use index (PRIMARY)
						LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
						LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
						LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
						LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
						LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilausnumero'
						and tilausrivi.tunnus  = '$rivitunnus'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$tilausrivi = mysql_fetch_assoc($result);

				// Poistetaan muokattava tilausrivi
				if ($tilausrivi["perheid"] > 0) {
					$query = "	DELETE from tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$tilausnumero'
								AND perheid = '$rivitunnus'";
					$result = pupe_query($query);
				}
				else {
					$tiliointisumma = $tilausrivi["summa"];
					$query = "	DELETE from tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$rivitunnus'";
					$result = pupe_query($query);
				}

				//	Poistetaan myös vastaava tiliöinti
				$query = "	UPDATE tiliointi
							SET korjattu = '$kukarow[kuka]',
							korjausaika  = now()
							WHERE yhtio = '$kukarow[yhtio]'
							and ltunnus = '$tilausnumero'
							and (tunnus = '$tilausrivi[tiliointirivitunnus]' or aputunnus = '$tilausrivi[tiliointirivitunnus]')";
				$result = pupe_query($query);

				if (mysql_affected_rows() == 0) {
					echo "<font class='error'>".t("Tiliöintirivin poistaminen epäonnistui! Matkalasku ja kp voi olla out of sync!!!")."</font><br>";
				}

				//	Fiksataan ostovelka
				korjaa_ostovelka($tilausnumero);

				//	Jos muokataan otetaan dada talteen
				if ($tapa == "MUOKKAA") {
					list($pv, $aika)=explode(" ",$tilausrivi["kerattyaika"]);
					list($alkuvv,$alkukk,$alkupp)=explode("-",$pv);
					list($alkuhh,$alkumm)=explode(":",$aika);

					list($pv, $aika)=explode(" ",$tilausrivi["toimitettuaika"]);
					list($loppuvv,$loppukk,$loppupp)=explode("-",$pv);
					list($loppuhh,$loppumm)=explode(":",$aika);

					$kpl		= $tilausrivi["kpl"];
					$vero		= $tilausrivi["tiliointialv"];
					$hinta		= round($tilausrivi["hinta"], 2);
					$kommentti	= $tilausrivi["kommentti"];
					$rivitunnus	= $tilausrivi["tunnus"];
					$kustp		= $tilausrivi["kustp"];
					$kohde		= $tilausrivi["kohde"];
					$projekti	= $tilausrivi["projekti"];

					if ($toim == "SUPER") {
						$tilino = $tilausrivi["tiliointitili"];
					}

					//	Otetaan tuote suoraan riviltä
					if ($vaihda_tuote != "") {
						$tuoteno = $vaihda_tuote;
						$tyyppi = $vaihda_tyyppi;
					}
					else {
						$tuoteno = $tilausrivi["tuoteno"];
						$tyyppi = $tilausrivi["tuotetyyppi"];
					}

					$alvulk = $tilausrivi["kulun_kohdemaan_alv"];
					$maa = $tilausrivi["kulun_kohdemaa"];
				}
				else {
					$tyhjenna = "joo";
					unset($tapahtumarow);
					$perheid2 = 0;
				}
			}
		}
	}

	//	Koitetaan lisätä uusi rivi!
	if ($tuoteno != "" and isset($lisaa) and $kuivat != "JOO") {
		if ($tyyppi == "A") {
			$errori = lisaa_paivaraha($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, "$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm", "$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm", $kommentti, $kustp, $kohde, $projekti);
		}
		else {
			$errori = lisaa_kulu($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $kpl, $vero, $hinta, $kommentti, $maa, $kustp, $kohde, $projekti);
		}

		if ($errori == "") {
			$tyhjenna = "JOO";
		}
	}

	if ($tyhjenna != "") {
		$tuoteno	= "";
		$tyyppi		= "";
		$kommentti	= "";
		$rivitunnus	= "";
		//$perheid2	= "";

		$kpl		= "";
		$hinta		= "";

		unset($alkupp);
		unset($alkukk);
		unset($alkuvv);
		unset($alkuhh);
		unset($alkumm);

		unset($loppupp);
		unset($loppukk);
		unset($loppuvv);
		unset($loppuhh);
		unset($loppumm);

	}

	//	Haetaan tapahtuman rivitiedot jos se on valittu

	// kirjoitellaan otsikko
	echo "<table>";

	echo "<tr>";
	echo "<th align='left'>".t("Henkilö")."</th>";
	echo "<td>$laskurow[nimi]<br>$laskurow[nimitark]<br>$laskurow[osoite]<br>$laskurow[postino] $laskurow[postitp]</td>";
	echo "</tr>";

	if ($laskurow["laskutus_nimi"] != "") {
		echo "<tr>";
		echo "<th align='left'>".t("Asiakas").":</th>";
		echo "<td>$laskurow[laskutus_nimi]<br>$laskurow[laskutus_nimitark]<br>$laskurow[laskutus_osoite]<br>$laskurow[laskutus_postino] $laskurow[laskutus_postitp]</td>";
		echo "</tr>";
	}

	// Näytetäänkö käyttöliittymä
	if (!$muokkauslukko) {

		if ($rivitunnus == "") {
			// tässä alotellaan koko formi.. tämä pitää kirjottaa aina
			echo "	<form name='tilaus' method='post' autocomplete='off' enctype='multipart/form-data'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='TALLENNA'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tapa' value='$tapa'>";

			if ($laskurow["tilaustyyppi"] == "M") {
				echo "	<tr><th>".t("Viite")."</th>
						<td><input type='text' size='30' name='viesti' value='$laskurow[viite]'><input type='submit' value='".t("Tallenna viite")."'></td></tr>";
			}
			else {
				echo "	<tr><th>".t("Viite")."</th>
						<td>$laskurow[viite]</td></tr>";
			}

			echo "<tr>";
			echo "<th align='left'>".t("Liitteet")."</th>";

			echo "<td>";

			$query = "	SELECT *
						from liitetiedostot
						where yhtio = '$kukarow[yhtio]'
						and liitos  = 'lasku'
						and liitostunnus = '$tilausnumero'";
			$liiteres = pupe_query($query);

			if (mysql_num_rows($liiteres) > 0) {
				while ($liiterow = mysql_fetch_assoc($liiteres)) {
					echo "<a target='kuvaikkuna' href='".$palvelin2."view.php?id=$liiterow[tunnus]'>$liiterow[selite]</a>";

					if (!$muokkauslukko) {
						echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='matkalasku.php?tee=$tee&tilausnumero=$tilausnumero&poistakuva=$liiterow[tunnus]'>*".t("poista")."*</a>";
					}

					echo "<br>\n";
				}
			}

			echo "</td></tr>";
			echo "</table>";

			echo "<br>";
			echo "<font class='message'>".t("Lisää kuittikopio tai liite")."</font><hr>";

			echo "<table>";
			echo "<tr>
					<th>".t("Valitse tiedosto")."</th>
					<td><input name='userfile' type='file'></td>
					</tr>
					<th>".t("Liitteen kuvaus")."</th>
					<td><input type='text' size='40' name='kuvaselite' value='$kuvaselite'></td>
					<td class='back'><input type='submit' value='".t("Tallenna liite")."'></td>
					</tr>
					</table><br>";
			echo "</form>";
		}
		else {
			echo "</table><br>";
		}

		if (mysql_num_rows($keikres) == 0 and ($laskurow["tilaustyyppi"] == "M" or $lisaatapahtumaan == "OK" or $toim == "SUPER")) {

			echo "<font class='message'>".t("Lisää uusi kulu")."</font><hr>";

			//	Vaihdettaessa tuotetta pitää pysyä oikeassa tyypissä
			if ($matkalaskupaivat_vain_kalenterista != "") {
				if ($rivitunnus != "") {
					$a = array($tyyppi);
				}
				else {
					$a = array("B");
				}
			}
			else {
				if ($tyyppi != "") {
					$a = array($tyyppi);
				}
				else {
					$a = array("A","B");
				}
			}

			echo "<table>";

			foreach ($a as $viranomaistyyppi) {

				$tyyppi_nimi = "";
				$lisat = "";

				switch ($viranomaistyyppi) {
					case "A":
						$tyyppi_nimi = "Päiväraha";
						$lisat = " and left(tuote.tuoteno, 3) != 'PPR' ";
						break;
					case "B":
						$tyyppi_nimi = "Muu kulu";
						$lisat = "";
						break;
				}

				$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.vienti,
							IF(tuote.vienti = '$yhtiorow[maa]' or tuote.nimitys like '%ateria%', 1, if(tuote.vienti != '', 2, 3)) sorttaus
							FROM tuote
							JOIN tili ON (tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.tuotetyyppi = '$viranomaistyyppi'
							and tuote.status != 'P'
							and tuote.tilino != ''
							$lisat
							ORDER BY sorttaus, tuote.nimitys";
				$tres = pupe_query($query);
				$valinta = "";

				if (mysql_num_rows($tres) > 0) {

					if ($rivitunnus > 0) {
						$onchange = "document.getElementById('tuoteno').value=this.value; document.getElementById('kuivat').value='JOO'; document.getElementById('lisaarivi').submit(); return false;";
					}
					else {
						$onchange = "submit();";
					}

					$valinta = "<tr><th>$tyyppi_nimi</th>";
					$valinta .= "<td>";
					$valinta .= "<select style=\"width: 350px\" name='tuoteno' $extra onchange=\"$onchange\">";

					if ($tapa != 'MUOKKAA' and $kuivat != 'JOO') {
						$valinta .= "<option value=''>".t("Valitse")."</option>";
					}

					while ($trow = mysql_fetch_assoc($tres)) {

						$trow["nimitys"] = t_tuotteen_avainsanat($trow, 'nimitys', $kukarow["kieli"]);

						if ($trow["tuoteno"] == $tuoteno) {
							$sel = "selected";
						}
						else {
							$sel = "";
						}

						if ($viranomaistyyppi == "A" and $trow["vienti"] != $yhtiorow["maa"] and $trow["vienti"] != '') {
							$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[vienti] - $trow[nimitys]</option>";
						}
						else {
							$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";
						}

					}
					$valinta .= "</select>";
					$valinta .= "</td></tr>";
				}

				if ($valinta != "") {
					echo "<form method='post' autocomplete='off'>";
					echo "<input type='hidden' name='tee' value='$tee'>";
					echo "<input type='hidden' name='lopetus' value='$lopetus'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='perheid2' value='$perheid2'>";
					echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
					echo "<input type='hidden' name='tyyppi' value='$viranomaistyyppi'>";
					echo "$valinta";
					echo "</form>";
				}
			}

			echo "</table>";
		}
	}
	else {
		echo "	<tr><th>".t("Viite")."</th>
				<td>$laskurow[viite]</td></tr>";

		echo "<tr>";
		echo "<th align='left'>".t("Liitteet")."</th>";

		echo "<td>";

		$query = "	SELECT *
					from liitetiedostot
					where yhtio = '$kukarow[yhtio]'
					and liitos  = 'lasku'
					and liitostunnus = '$tilausnumero'";
		$liiteres = pupe_query($query);

		if (mysql_num_rows($liiteres) > 0) {
			while ($liiterow = mysql_fetch_assoc($liiteres)) {
				echo "<a target='kuvaikkuna' href='".$palvelin2."view.php?id=$liiterow[tunnus]'>$liiterow[selite]</a>";
				echo "<br>\n";
			}
		}

		echo "</td></tr>";
		echo "</table><br>";
	}

	if ($tyyppi != "" and $tuoteno != "") {

		$query = "	SELECT *
					from tuote
					where yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'
					and tuotetyyppi = '$tyyppi'
					and status != 'P'";
		$tres = pupe_query($query);

		if (mysql_num_rows($tres) == 1) {
			$trow = mysql_fetch_assoc($tres);
		}
		else {
			die("<font class='error'>".t("VIRHE: Viranomaistuote puuttuu")." (3)</font><br>");
		}

		echo "<br><font class='message'>".t("Lisää")." $trow[nimitys]</font><hr>$errori";
		echo "<form id='lisaarivi' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' id='kuivat' name='kuivat' value=''>";
		echo "<input type='hidden' id='tuoteno' name='tuoteno' value='$tuoteno'>";
		echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
		echo "<input type='hidden' name='perheid2' value='$perheid2'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

		if ($rivitunnus > 0) {
			echo "<font class='error'>".t("HUOM: Jos et lisää riviä se poistetaan erittelystä/matkalaskusta")."</font><br>";
		}

		echo "<table><tr>";

		if ($tapa != "MUOKKAA" and $perheid2 > 0) {
			if (!isset($kustp)) {
				$kustp = $tapahtumarow["kustp"];
			}
			if (!isset($kohde)) {
				$kohde = $tapahtumarow["kohde"];
			}
			if (!isset($projekti)) {
				$projekti = $tapahtumarow["projekti"];
			}
			if (!isset($maa)) {
				$maa = $tapahtumarow["maa"];
			}
		}

		//	Tehdään kustannuspaikkamenut
		$query = "	SELECT tunnus, nimi, koodi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					and tyyppi = 'K'
					ORDER BY koodi+0, koodi, nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$kustannuspaikka = "<select name = 'kustp' style=\"width: 100px\"><option value = ' '>".t("Ei kustannuspaikkaa")."</option>";

			if (!isset($kustp)) {
				if ($trow["kustp"] > 0) {
					$kustp = $trow["kustp"];
				}
				else {
					$kustp = $laskurow["kustannuspaikka"];
				}
			}

			while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
				$valittu = "";

				if ($kustannuspaikkarow["tunnus"] == $kustp) {
					$valittu = "selected";
				}

				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
			}

			$kustannuspaikka .= "</select>";
		}

		$query = "	SELECT tunnus, nimi, koodi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					and tyyppi = 'O'
					ORDER BY koodi+0, koodi, nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$kustannuspaikka .= " <select name = 'kohde' style=\"width: 100px\"><option value = ' '>".t("Ei kohdetta");

			if ($trow["kohde"] > 0) {
				$kohde = $trow["kohde"];
			}
			else {
				$kohde = $laskurow["kohde"];
			}

			while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
				$valittu = "";
				if ($kustannuspaikkarow["tunnus"] == $kohde) {
					$valittu = "selected";
				}

				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
			}

			$kustannuspaikka .= "</select>";
		}

		$query = "	SELECT tunnus, nimi, koodi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					and tyyppi = 'P'
					ORDER BY koodi+0, koodi, nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$kustannuspaikka .= " <select name = 'projekti' style=\"width: 100px\"><option value = ' '>".t("Ei projektia");

			if ($trow["projekti"] > 0) {
				$projekti = $trow["projekti"];
			}
			else {
				$projekti = $laskurow["projekti"];
			}

			while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
				$valittu = "";
				if ($kustannuspaikkarow["tunnus"] == $projekti) {
					$valittu = "selected";
				}
				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
			}

			$kustannuspaikka .= "</select>";
		}

		if (!isset($alkukk)) $alkukk = date("m");
		if (!isset($alkuvv)) $alkuvv = date("Y");

		if (!isset($loppukk)) $loppukk = date("m");
		if (!isset($loppuvv)) $loppuvv = date("Y");

		if ($tyyppi == "A") {
			echo "<th>".t("Kustannuspaikka")."</th><th>".t("Alku")."</th><th>".t("Loppu")."</th><th>".t("Hinta")."</th></tr>";
			echo "<tr><td>";

			if ($kustannuspaikka != "") {
				echo $kustannuspaikka;
			}
			else {
				echo t("Ei kustannuspaikkaa");
			}

			echo "</td><td><input type='text' name='alkupp' value='$alkupp' size='3' maxlength='2'> <input type='text' name='alkukk' value='$alkukk' size='3' maxlength='2'> <input type='text' name='alkuvv' value='$alkuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='alkuhh' value='$alkuhh' size='3' maxlength='2'>:<input type='text' name='alkumm' value='$alkumm' size='3' maxlength='2'>&nbsp;</td>
					<td>&nbsp;<input type='text' name='loppupp' value='$loppupp' size='3' maxlength='2'> <input type='text' name='loppukk' value='$loppukk' size='3' maxlength='2'> <input type='text' name='loppuvv' value='$loppuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='loppuhh' value='$loppuhh' size='3' maxlength='2'>:<input type='text' name='loppumm' value='$loppumm' size='3' maxlength='2'></td><td align='center'>$trow[myyntihinta]</td>";

			$cols = 4;
			$leveys = 80;
		}
		elseif ($tyyppi == "B") {
			$lisa = "";
			if ($maa != "" and $maa != $yhtiorow["maa"]) {
				$lisa = "<th>".t("Ulkomaan ALV")."</th>";
				$cols = 6;
			}
			else {
				$cols = 5;
			}

			echo "<th>".t("Kustannuspaikka")."</th><th>".t("Kohdemaa")."</th><th>".t("Määrä")."</th><th>".t("Hinta")."</th><th>".t("Alv")."</th>$lisa</tr>";
			echo "<tr><td>";

			if ($kustannuspaikka != "") {
				echo $kustannuspaikka;
			}
			else {
				echo t("Ei kustannuspaikkaa");
			}

			echo "</td>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = pupe_query($query);

				echo "<td><select name='maa' onchange='submit();' style='width: 150px;'>";

			while ($vrow = mysql_fetch_assoc($vresult)) {
				$sel = "";
				if ($maa == "" and $yhtiorow["maa"] == $vrow["koodi"]) {
					$sel = "selected";
				}
				elseif ($maa == $vrow["koodi"]) {
					$sel = "selected";
				}

				echo "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
			}

				echo "</select></td>";
			echo "<td><input type='text' name='kpl' value='$kpl' size='6'></td>";

			//	Hinta saadaan antaa, jos meillä ei ole ennettu hintaa
			if ($trow["myyntihinta"] > 0) {
				echo "<td align='center'><input type='hidden' name='hinta' value='$trow[myyntihinta]'>$trow[myyntihinta]</td>";
			}
			else {
				echo "<td><input type='text' name='hinta' value='$hinta' size='8'></td>";
			}

			if ($maa != "" and $maa != $yhtiorow["maa"]) {
				echo "<td>0 %</td>";

				//	Haetaan oletusalv tuotteelta
				if ($alvulk == "") {
					$query = "SELECT * from tuotteen_alv where yhtio = '$kukarow[yhtio]' and maa = '$maa' and tuoteno = '$tuoteno' limit 1";
					$alhire = pupe_query($query);
					$alvrow = mysql_fetch_assoc($alhire);

					$alvulk = $alvrow["alv"];

					if ($alvulk == "") {
						echo "<font class='error'>".t("Kulun arvonlisäveroa kohdemaassa ei ole määritelty")."</font><br>";
					}
				}
				echo "<td><input type='hidden' name='vero' value='0'>".alv_popup_oletus("alvulk", $alvulk, $maa, 'lista')."</td>";
			}
			else {
				echo "<td><input type='hidden' name='vero' value='$trow[alv]'> $trow[alv] %</td>";
			}

			$leveys = 50;
		}

		echo "<td class='back'><input type='submit' name='lisaa' value='".t("Lisää")."'></td></tr>";
		echo "<tr><th colspan='$cols'>".t("Kommentti")."</th></tr>";
		echo "<tr><td colspan='$cols'><textarea name='kommentti' rows='4' cols='80'>".str_replace("<br>","\n",$kommentti)."</textarea></td>";

		if ($toim == "SUPER") {
			echo "<tr>";
			echo "<th colspan='3'>".t("Poikkeava tilinumero")." (".t("oletus on")." $trow[tilino])</th>";

			if ($tilino == $trow["tilino"] or $kuivat == "JOO" or $kulupoiminta == "JOO") {
				$tilino	= "";
			}

			echo "<td colspan='2'><input type='text' name='tilino' value = '$tilino' size='20'></td>";
		}

		echo "<td class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'></td>";

		if ($laskurow["tilaustyyppi"] != "M") {
			//	Jos laskun ja rivien loppusumma heittää näytetään erotus..
			$query = "	SELECT sum(rivihinta) summa
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi='M'";
			$result = pupe_query($query);
			$rivisumma = mysql_fetch_assoc($result);

			if ((float) $rivisumma["summa"] + ((float) $hinta * (float) $kpl) !=  (float) $laskurow["summa"]) {
				echo "<tr><td class='back' align='right' colspan='3'>".("Käsittelemättä")."</td><td class='back' align='right' colspan='2'><font clasS='error'>".number_format(( (float) $laskurow["summa"] - ((float) $rivisumma["summa"] + ((float) $hinta * (float) $kpl))),2, ', ', ' ')."</font></td></tr>";
			}
		}

		echo "</tr></table></form>";
	}

	/*
		Piilotetaan rivit joilla ei ole kappaleita (päiväraha, jos vain puolikas..)
	*/
	$query = "	SELECT tilausrivi.*, tuotetyyppi,
				if (tuote.tuotetyyppi='A' or tuote.tuotetyyppi='B', concat(date_format(kerattyaika, '%d.%m.%Y %k:%i'),' - ',date_format(toimitettuaika, '%d.%m.%Y %k:%i')), '') ajalla,
				concat_ws('/',kustp.nimi,kohde.nimi,projekti.nimi) kustannuspaikka,
				if(tilausrivi.perheid=0, tilausrivi.tunnus,
				(select max(tunnus) from tilausrivi t use index(yhtio_otunnus) where tilausrivi.yhtio = t.yhtio and tilausrivi.otunnus = t.otunnus and tilausrivi.perheid=t.perheid and tilausrivi.tyyppi=t.tyyppi)) viimonen,
				if(tilausrivi.perheid=0, tilausrivi.tunnus, tilausrivi.perheid) perhe,
				if(tilausrivi.perheid=0, 1,
				(select count(*) from tilausrivi t use index(yhtio_otunnus) where tilausrivi.yhtio = t.yhtio and tilausrivi.otunnus = t.otunnus and tilausrivi.perheid=t.perheid and tilausrivi.tyyppi=t.tyyppi)) montako,
				tiliointi.tilino tilino,
				tilausrivin_lisatiedot.kulun_kohdemaa, kulun_kohdemaa
				FROM tilausrivi use index(yhtio_otunnus)
				LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
				LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
				LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
				LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
				LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilausnumero'
				and tilausrivi.tyyppi  = 'M'
				ORDER BY tilausrivi.perheid2, tilausrivi.tunnus";
	$result = pupe_query($query);

	$kuluriveja = mysql_num_rows($result);

	if (mysql_num_rows($result) > 0) {
		echo "<br><font class='message'>".t("Kulurivit")."</font><hr>";
		echo "<table>";
		echo "<tr>";
		echo "<th>#</th>";
		echo "<th>".t("Kulu")."</th>";
		echo "<th>".t("Kustannuspaikka")."</th>";
		echo "<th>".t("Kpl")."</th>";
		echo "<th>".t("Hinta")."</th>";
		echo "<th>".t("Alv")."</th>";
		echo "<th>".t("Yhteensä")."</th>";
		echo "</tr>";

		$tapahtumia = 0;
		$summa = 0;

		while ($row = mysql_fetch_assoc($result)) {

			$row["nimitys"] = t_tuotteen_avainsanat($row, 'nimitys', $kukarow["kieli"]);

			$tapahtumia++;

			echo "<tr class='aktiivi'>";

			// jos tää rivi on samaa perhettä edellisen kanssa, ei kasvatella rivinumeroa
			if ($row["perhe"] == $edperhe) {
				$tapahtumia--;
			}
			else {
				$rowspan = $row["montako"] + 1;
				if ($row["kommentti"] != "") {
					$rowspan++;
				}
				echo "<td rowspan='$rowspan'>$tapahtumia</td>";
			}

			if ($laskurow["tilaustyyppi"] != "M" and $row["tuoteno"] == "") {
				$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.vienti
							FROM tuote
							JOIN tili ON tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.tuotetyyppi = 'B'
							and tuote.status != 'P'
							and tuote.tilino != ''
							ORDER BY tuote.vienti IN ('$yhtiorow[maa]') DESC, tuote.vienti ASC, tuote.nimitys";
				$tres = pupe_query($query);
				$valinta = "";

				if (mysql_num_rows($tres) > 0){
					$valinta = "<select name='vaihda_tuote' onchange='submit();' style='width: 125px;'>";
					$valinta .= "<option value=''>".t("Määrittele kulu")."</option>";

					while ($trow = mysql_fetch_assoc($tres)) {
						if ($trow["tuoteno"] == $row["tuoteno"]) {
							$sel = "selected";
						}
						else {
							$sel = "";
						}

						$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";

					}
					$valinta .= "</select>";
				}

				echo "<td>
						<form action = '$PHP_SELF#ankkuri_$row[tunnus]' method='post' autocomplete='off'>
							<input type='hidden' name='tee' value='$tee'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='tapa' value='MUOKKAA'>
							<input type='hidden' name='vaihda_tyyppi' value='B'>
							<input type='hidden' name='kulupoiminta' value='JOO'>
							<input type='hidden' name='rivitunnus' value='$row[perhe]'>
							$valinta
						</form>
					</td>";
				$edrivi = $row["tunnus"];
			}
			else {
				echo "<td style='font-weight:bold'>$row[nimitys]<a name='ankkuri_$row[tunnus]'></a></td>";
			}

			echo "<td>$row[kustannuspaikka]</td>";
			echo "<td align='right'>$row[kpl]</td>";
			echo "<td align='right'>".number_format($row["hinta"], 2, ', ', ' ')."</td>";
			echo "<td align='right'>".number_format($row["alv"], 2, ', ', ' ')."</td>";
			echo "<td align='right'>".number_format($row["rivihinta"], 2, ', ', ' ')."</td>";

			//	Aina kun perhe vaihtuu voidaan näyttää nappulat!
			if (mysql_num_rows($keikres) == 0 and $row["perhe"] != $edperhe and $row["tuoteno"] != "" and !$muokkauslukko) {

				echo "<td class='back'>";
				echo "<form method='post' autocomplete='off'>";
				echo "<input type='hidden' name='tee' value='$tee'>";
				echo "<input type='hidden' name='lopetus' value='$lopetus'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='tapa' value='MUOKKAA'>";
				echo "<input type='hidden' name='rivitunnus' value='$row[perhe]'>";
				echo "<input type='hidden' name='perheid2' value='$row[perheid2]'>";
				echo "<input type='submit' value='".t("Muokkaa")."'>";
				echo "</form>";
				echo "</td>";

				if ($laskurow["tilaustyyppi"] == "M") {
					echo "<td class='back'>";
					echo "<form method='post' autocomplete='off'>";
					echo "<input type='hidden' name='tee' value='$tee'>";
					echo "<input type='hidden' name='lopetus' value='$lopetus'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
					echo "<input type='hidden' name='tapa' value='POISTA'>";
					echo "<input type='hidden' name='rivitunnus' value='$row[perhe]'>";
					echo "<input type='hidden' name='perheid2' value='$row[perheid2]'>";
					echo "<input type='submit' value='".t("Poista")."'>";
					echo "</form>";
					echo "</td>";
				}
/*
				if ($row["perheid2"] == $row["tunnus"]) {
					echo "<td class='back'>";
					echo "<form method='post' autocomplete='off'>";
					echo "<input type='hidden' name='tee' value='$tee'>";
					echo "<input type='hidden' name='lopetus' value='$lopetus'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
					echo "<input type='hidden' name='perheid2' value='$row[perheid2]'>";
					echo "<input type='hidden' name='lisaatapahtumaan' value='OK'>";
					echo "<input type='submit' value='".t("Lisää kulu tapahtumaan")."'>";
					echo "</form>";
					echo "</td>";
				}
*/
			}

			echo "</tr>";

			//	Kommentit aina vain perheen loppuun!
			if ($row["tunnus"] == $row["viimonen"]) {
				if ($row["tuotetyyppi"] == "A") {
					echo "<tr class='aktiivi'>";
					echo "<td>".t("Ajalla")."</td>";
					echo "<td colspan='5' style='font-style:italic'>$row[ajalla]</td>";
					echo "</tr>";
				}
				else {
					echo "<tr class='aktiivi'>";
					echo "<td>".t("Kohdemaa")."</td>";
					echo "<td colspan='5' style='font-style:italic'>".maa($row["kulun_kohdemaa"])."</td>";
					echo "</tr>";
				}

				if ($row["kommentti"] != "") {
					echo "<tr class='aktiivi'><td colspan='6' style='font-style:italic'>$row[kommentti]</td></tr>";
				}

				if ($toim == "SUPER") {
					$query = "	SELECT nimi
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and tilino = '$row[tilino]'";
					$tilires = pupe_query($query);
					$tilirow = mysql_fetch_assoc($tilires);

					echo "<tr class='aktiivi'>";
					echo "<td></td>";
					echo "<td colspan='6'>".t("Kirjanpidon tili").": $row[tilino] $tilirow[nimi]</td>";
					echo "</tr>";
				}

			}

			$summa += $row["rivihinta"];
			$edperhe = $row["perhe"];
			$edperheid2 = $row["perheid2"];
		}

		echo "<tr>";
		echo "<th colspan='6' style='text-align:right;'>".t("Yhteensä")."</th>";
		echo "<th style='text-align:right;'>".number_format($summa, 2, ', ', ' ')."</th>";
		echo "</tr>";
		echo "</table>";
	}

	echo "<br><hr>";

	if ($laskurow["tilaustyyppi"] != "M") {
		// Jos laskun ja rivien loppusumma heittää näytetään erotus..
		$query = "	SELECT sum(rivihinta) summa
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilausnumero'
					and tyyppi = 'M'";
		$result = pupe_query($query);
		$rivisumma = mysql_fetch_assoc($result);

		echo t("Laskun summa")." ".number_format($laskurow["summa"], 2, ', ', ' ')."<br>";

		if (round($rivisumma["summa"], 2) != round($laskurow["summa"], 2)) {
			echo "<font class='error'>".t("Käsittelemättä")." ".number_format(($laskurow["summa"] - $rivisumma["summa"]), 2, ', ', ' ')."</font><br>";
		}
	}
	else {
		/*
		if (mysql_num_rows($keikres) == 0) {
			echo "	<td class='back' align='left' colspan='3'>
						<form name='palaa' method='post'>
						<input type = 'hidden' name='tee' value = 'TUO_KALENTERISTA'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='toim' value='$toim'>
						<input type = 'hidden' name='tilausnumero' value = '$tilausnumero'>
						<input type='submit' value='".t("Tuo kalenterista")."'>
						</form>
					</td>
					<td colspan='3' class='back' align='right'></td>";
		}
		*/
	}

	if ($lopetus == "") {
		echo "	<form name='palaa' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='VALMIS'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='submit' value='".t("Matkalasku valmis")."'>
				</form>";
	}

	/*
	if (mysql_num_rows($keikres) == 0 and $toim == "SUPER") {
		echo "<br><br><form method='post' autocomplete='off' onsubmit=\"return confirm('".t("Oletko varma, että haluat käsitellä kululaskun uudestaan.\\n\\nLaskun uudelleenkäsittely poistaa kaikki erittelyrivit ja tiliöinnit.\\n\\nTietoja EI VOI PALAUTTAA.")."')\">";
		echo "<input type='hidden' name='tee' value='UUDELLEENKASITTELE'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<td class='back' colspan='4'><input type = 'submit' value='".t("Uudelleenkäsittele lasku")."'></td>";
		echo "</form>";
	}
	*/


	if ($id > 0) {
		echo "<iframe src='view.php?id=$id' name='alaikkuna' width='100%' height='60%' align='bottom' scrolling='auto'></iframe>";
	}
}

if ($tee == "") {

	echo "<br><br><font class='message'>".t("Perusta uusi matkalasku")."</font><hr>";

	echo "<form method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='UUSI'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	echo "<br><table>";
	echo "<tr>";
	echo "<th>".t("Matkustaja")."</th>";
	echo "<td>";

	if ($toim == "SUPER") {
		$query = "	SELECT toimi.nimi kayttaja, kuka.nimi kayttajanimi
					FROM toimi
		 			JOIN kuka ON kuka.yhtio=toimi.yhtio and kuka.kuka=toimi.nimi
		 			WHERE toimi.yhtio='$kukarow[yhtio]'
					and toimi.tyyppi='K'
					ORDER BY kayttajanimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			echo "<select name = 'kayttaja'>";

			while ($krow = mysql_fetch_assoc($result)) {
				$valittu = "";

				if ($krow["kayttaja"] == $kukarow["kuka"]) {
					$valittu = "selected";
				}

				echo "<option value = '$krow[kayttaja]' $valittu>$krow[kayttajanimi]</option>";
			}

 			echo "</select>";
		}
	}
	else {
		echo "$kukarow[nimi]";
	}

	echo "</td>";
	echo "<td class='back'><input type='Submit' name='EI_ASIAKASTA_X' value='".t("Perusta")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

	echo "<br><br><font class='message'>".t("Perusta uusi matkalasku ja liitä asiakas laskuun")."</font><hr>";

	echo "<form method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='UUSI'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	echo "<br><table>";
	echo "<tr>";
	echo "<th>".t("Matkustaja")."</th>";
	echo "<td>";

	if ($toim == "SUPER") {
		$query = "	SELECT toimi.nimi kayttaja, kuka.nimi kayttajanimi
					FROM toimi
		 			JOIN kuka ON kuka.yhtio=toimi.yhtio and kuka.kuka=toimi.nimi
		 			WHERE toimi.yhtio='$kukarow[yhtio]'
					and toimi.tyyppi='K'
					ORDER BY kayttajanimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			echo "<select name = 'kayttaja'>";

			while ($krow = mysql_fetch_assoc($result)) {
				$valittu = "";

				if ($krow["kayttaja"] == $kukarow["kuka"]) {
					$valittu = "selected";
				}

				echo "<option value = '$krow[kayttaja]' $valittu>$krow[kayttajanimi]</option>";
			}

 			echo "</select>";
		}
	}
	else {
		echo "$kukarow[nimi]";
	}

	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Asiakas")."</th>";
	echo "<td><input type='text' size='20' name='ytunnus'></td>";
	echo "<td class='back'><input type='Submit' value='".t("Perusta")."'></td>";
	echo "</tr>";

	echo "</table>";
	echo "</form>";

	$query = "	SELECT lasku.*,
				laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa,
				kuka.nimi kayttajanimi
				FROM lasku
				LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and kuka.kuka=lasku.hyvak1
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila = 'H'
				and lasku.mapvm = '0000-00-00'
				and (
					(lasku.hyvak2 = '$kukarow[kuka]' and lasku.h2time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]') or
					(lasku.hyvak3 = '$kukarow[kuka]' and lasku.h3time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]') or
					(lasku.hyvak4 = '$kukarow[kuka]' and lasku.h4time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]') or
					(lasku.hyvak5 = '$kukarow[kuka]' and lasku.h5time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]')
				)
				and lasku.tilaustyyppi = 'M'";
	$result = pupe_query($query);

	if (mysql_num_rows($result)) {

		echo "<br><br><font class='message'>".t("Hyväksynnässä olevat matkalaskut")."</font><hr>";
		echo "<table><tr><th>".t("Laskunro")."</th><th>".t("Käyttäjä")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><tr>";

		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";
			echo "<td>$row[laskunro]</td>";
			echo "<td>$row[kayttajanimi]</td>";
			echo "<td>$row[laskutus_nimi]</td>";
			echo "<td>$row[viite]</td>";
			echo "<td>$row[summa]</td>";
			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";

			if ($kukarow["taso"] == 2) {
				echo "<td class='back'><input type='Submit' value='".t("Muokkaa")."'></td>";
			}
			else {
				echo "<td class='back'><input type='Submit' value='".t("Tarkastele")."'></td>";
			}

			echo "</form>";
			echo "</tr>";

		}
		echo "</table>";
	}

	$query = "	SELECT lasku.*,
				laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
				FROM lasku
				LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				WHERE lasku.yhtio 		 = '$kukarow[yhtio]'
				and lasku.tila 			 = 'H'
				and lasku.mapvm 		 = '0000-00-00'
				and lasku.toim_ovttunnus = '$kukarow[kuka]'
				and lasku.h1time 		 = '0000-00-00 00:00:00'
				and lasku.tilaustyyppi 	 = 'M'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<br><br><font class='message'>".t("Avoimet matkalaskusi")."</font><hr>";
		echo "<table><tr><th>".t("Laskunro")."</th><th>".t("Henkilö")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><tr>";

		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";
			echo "<td>$row[laskunro]</td>";
			echo "<td>$row[nimi]</td>";
			echo "<td>$row[laskutus_nimi]</td>";
			echo "<td>$row[viite]</td>";
			echo "<td>$row[summa]</td>";
			echo "<td class='back'><form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
			echo "<input type='Submit' value='".t("Muokkaa")."'>";
			echo "</form></td>";
			echo "<td class='back'><form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='POISTA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
			echo "<input type='Submit' value='".t("Poista")."'>";
			echo "</form></td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	$query = "	SELECT lasku.*,
				laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
				FROM lasku
				LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				WHERE lasku.yhtio 		 = '$kukarow[yhtio]'
				and lasku.tila 		    IN ('H','Y','M','P','Q')
				and lasku.mapvm 		 = '0000-00-00'
				and lasku.toim_ovttunnus = '$kukarow[kuka]'
				and lasku.h1time 		!= '0000-00-00 00:00:00'
				and lasku.tilaustyyppi 	 = 'M'
				ORDER BY luontiaika DESC";
	$result = pupe_query($query);

	if (mysql_num_rows($result)) {

		echo "<br><br><font class='message'>".t("Vanhat matkalaskusi")."</font><hr>";
		echo "<table><tr><th>".t("Laskunro")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><th>".t("Tila")."</th><tr>";

		while ($row = mysql_fetch_assoc($result)) {
			$laskutyyppi = $row["tila"];
			$alatila = $row["alatila"];

			//tehdään selväkielinen tila/alatila
			require ("inc/laskutyyppi.inc");

			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
			echo "<tr>";
			echo "<td>$row[laskunro]</td>";
			echo "<td>$row[laskutus_nimi]</td>";
			echo "<td>$row[viite]</td>";
			echo "<td>$row[summa]</td>";
			echo "<td>".t($laskutyyppi)."</td>";
			echo "<td class='back'><input type='Submit' value='".t("Tarkastele")."'></td>";
			echo "</tr>";
			echo "</form>";
		}
		echo "</table>";
	}
}

require ("inc/footer.inc");

?>