<?php

	if (!function_exists("jtautomatiikka")) {
		//tarvitaan liitostunnus
		function jtautomatiikka($liitostunnus, $filesta, $otunnus) {
			global $yhtiorow, $kukarow;

			//tehdään näin jotta ei varmasti löydy mitään jos liitostunnus on tyhjä
			if ($liitostunnus == '') {
				$liitostunnus = 'tyhja';
			}

			$lask=0;
			$ulos="\n\n";

			$query = "	SELECT distinct otunnus, if(perheid!=0, concat('JOPERHE',tilausrivi.perheid), concat('EIPERHE',tilausrivi.tunnus)) perheid, tilaajanrivinro
						FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','G')
						and tilausrivi.var = 'J'
						and tilausrivi.keratty = ''
						and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
						and tilausrivi.uusiotunnus = 0
						and tilausrivi.varattu = 0
						and tilausrivi.kpl = 0
						and tilausrivi.jt <> 0
						and lasku.liitostunnus='$liitostunnus'
						ORDER BY lasku.ytunnus, tuote.tuoteno";
			$isaresult = mysql_query($query) or pupe_error($query);

			//rivejä löytyi?
			if (mysql_num_rows($isaresult) > 0) {
				while ($isarow = mysql_fetch_array($isaresult)) {

					//tutkitaan onko tämä superjtrivi
					$onkosuper = "";

					if ($isarow["tilaajanrivinro"] != 0) {
						$query = "	SELECT *
									FROM toimi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$isarow[tilaajanrivinro]'";
						$sjtres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($sjtres) == 1) {
							$sjtrow = mysql_fetch_array($sjtres);

							// Tutkitaan onko tätä tuotetta jollain ostotilauksella auki tällä toimittajalla
							// jos on niin tiedetään, että tämä on superjt
							$query = "	SELECT *
										FROM lasku use index (yhtio_liitostunnus), tilausrivi use index (yhtio_otunnus)
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and lasku.liitostunnus = '$sjtrow[tunnus]'
										and lasku.tila='O'
										and tilausrivi.yhtio=lasku.yhtio
										and tilausrivi.otunnus=lasku.tunnus
										and tilausrivi.tyyppi='O'
										and tilausrivi.uusiotunnus=0
										and tilausrivi.varattu!=0
										and tilausrivi.tuoteno='$isarow[tuoteno]'";
							$sjtres = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($sjtres) > 0) {
								$onkosuper = "ON";
							}
						}
					}

					//ei ole super
					if ($onkosuper == "") {
						$lirt = "";

						if (substr($isarow["perheid"],0,7) == "JOPERHE") {
							$lirt = " and tilausrivi.perheid = '".substr($isarow["perheid"],7)."' ";
						}
						elseif(substr($isarow["perheid"],0,7) == "EIPERHE") {
							$lirt = " and tilausrivi.tunnus = '".substr($isarow["perheid"],7)."' ";
						}

						$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, lasku.ytunnus, tilausrivi.jt, lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.tilkpl,
									lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.otunnus
									FROM tilausrivi use index (yhtio_otunnus), lasku use index (PRIMARY), tuote use index (tuoteno_index)
									WHERE tilausrivi.yhtio='$kukarow[yhtio]'
									and tilausrivi.tyyppi in ('L','G')
									and tilausrivi.var = 'J'
									and lasku.yhtio=tilausrivi.yhtio
									and tilausrivi.otunnus=lasku.tunnus
									and tuote.yhtio=tilausrivi.yhtio
									and tuote.tuoteno=tilausrivi.tuoteno
									and tilausrivi.varattu = 0
									and tilausrivi.kpl = 0
									and tilausrivi.jt <> 0
									and tilausrivi.otunnus = '$isarow[otunnus]'
									$lirt
									and lasku.liitostunnus='$liitostunnus'
									ORDER BY tilausrivi.tunnus";
						$etsresult2 = mysql_query($query) or pupe_error($query);

						$okperhe='';
						$perhelask=0;
						$persaldolask=0;

						//loopataan eka kerran läpi ja katotaan perheitten kohdalla voiko ne toimittaa kokonaisuudessaan
						while ($jtrow = mysql_fetch_array($etsresult2)) {
							$tuoteno     = $jtrow["tuoteno"];

							if (substr($isarow["perheid"],0,7) == "JOPERHE") {
								if ($jtrow["ei_saldoa"] == "") {

									list( , , $myytavissa) = saldo_myytavissa($tuoteno);

									$query = "	SELECT sum(jt) jt, count(*) kpl
												FROM tilausrivi use index(yhtio_tyyppi_var)
												WHERE yhtio='$kukarow[yhtio]'
												and tyyppi in ('L','G')
												and tuoteno='$tuoteno'
												and varattu=0
												and jt<>0
												and kpl=0
												and var='J'";

									$juresult = mysql_query($query) or pupe_error($query);
									$jurow    = mysql_fetch_array ($juresult);

									if ($myytavissa >= $jurow['jt']) {
										$persaldolask++;
									}
								}
								else {
									$persaldolask++;
								}
								$perhelask++;
							}
						}

						if (substr($isarow["perheid"],0,7) == "JOPERHE") {
							if ($perhelask != 0 and $persaldolask == $perhelask) {
								$okperhe = $isarow["perheid"];
							}
							else {
								$okperhe = '';
							}
						}
						else {
							$okperhe = '';
						}

						//kelataan alkuun resultti
						mysql_data_seek ($etsresult2	, 0);

						//loopataan toisen kerran, mutta jätetään ne perheet pois jotka eivät ole koknaisuudessaan toimitettavissa
						while ($jtrow = mysql_fetch_array($etsresult2)) {
							if ($isarow["perheid"] == $okperhe or substr($isarow["perheid"],0,7) == "EIPERHE") {
								// haetaan tuotteelle saldo
								$akeraysaika = date("Y-m-d");
								$tuoteno     = $jtrow["tuoteno"];
								$atil        = $jtrow["jt"];

								if ($jtrow["ei_saldoa"] == "") {
									$myytavissa = saldo_myytavissa($tuoteno);
								}
								else {
									//saldottomat tuotteet
									$voidaankolisata = 1;
									$kappaleet[] = $atil;
								}

								// saldo riittää tai halutaan nähdä kaikki rivit
								if ($myytavissa > 0  or $jtrow["ei_saldoa"] != "") {
									if ($jtrow["ei_saldoa"] == "") {
										$query = "	SELECT sum(jt) jt, count(*) kpl
													FROM tilausrivi use index(yhtio_tyyppi_var)
													WHERE yhtio='$kukarow[yhtio]'
													and tyyppi in ('L','G')
													and tuoteno='$tuoteno'
													and varattu=0
													and jt<>0
													and kpl=0
													and var='J'";

										$juresult = mysql_query($query) or pupe_error($query);
										$jurow    = mysql_fetch_array ($juresult);
									}

									// jos toimituksen jälkeen oleva määrä on enemmän kun kaikki jt-kpllät miinus tämä tilaus niin riittää kaikille
									if (($jurow["jt"] <= $myytavissa or $jtrow["ei_saldoa"] != "") and ($isarow["perheid"] == $okperhe or substr($isarow["perheid"],0,7) == "EIPERHE")) {
										//haetaan lisäykseen tarvittavat tiedot
										$query =	"SELECT tilausrivi.hinta, tilausrivi.netto, tilausrivi.ale, tilausrivi.alv
													FROM tilausrivi, lasku
													WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
													and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.tunnus = '$jtrow[tunnus]'";
										$alkupres = mysql_query($query) or pupe_error($query);
										$alkuprow    = mysql_fetch_array ($alkupres);

										$query =	"SELECT *
													FROM lasku
													WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$otunnus'";
										$laskures = mysql_query($query) or pupe_error($query);
										$laskurow    = mysql_fetch_array ($laskures);

										// haetaan tuotteen tiedot
										$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
										$tuoteres = mysql_query($query);

										// lisätään rivi
										$trow = mysql_fetch_array($tuoteres);

										$ytunnus         = $laskurow["ytunnus"];
										$kpl             = (float) $jtrow["jt"];
										$tuoteno         = $tuoteno;
										$toimaika 	     = $laskurow["toimaika"];
										$kerayspvm	     = $laskurow["kerayspvm"];
										$hinta 		     = $alkuprow["hinta"];
										$netto 		     = $alkuprow["netto"];
										$ale 		     = $alkuprow["alennus"];
										$var		     = "";
										$alv		     = "";
										$paikka		     = "";
										$varasto 	     = "";
										$rivitunnus		 = "";
										$korvaavakielto	 = "";
										$varataan_saldoa = "";

										if (substr($isarow["perheid"],0,7) == "JOPERHE") {
											$perheid	 = substr($isarow["perheid"],7);
										}
										else {
											$perheid	 = "";
										}

										//$kommentti		 = t("JT-rivi, toimitettu automaattisesti");

										require ("lisaarivi.inc");

										//ja sit dellataan vanha
										$query = "	UPDATE tilausrivi SET tyyppi='D'
													WHERE yhtio = '$kukarow[yhtio]' and tunnus='$jtrow[tunnus]' and varattu=0 and kpl=0 and var='J' and tyyppi = 'L'";
										$tresult = mysql_query($query) or pupe_error($query);

										if ($filesta == 'EDI') {
											$ulos .= t("Lisättiin jälkitoimituksesta tuotetta ")."$tuoteno $atil ".t("kpl").".\n";
										}
										else {
											echo "<font class='message'>".t("Tuote")." $tuoteno ".t("lisättiin tilaukseen jälkitoimituksesta")."!</font><br>";
										}
										$tunnus = $jtrow["tunnus"];
										$loput[$tunnus] = "KAIKKI";

										$lask++;
									}
								}
							}
						}
					}
				}
				if ($lask == 0) {
					if ($filesta == 'EDI') {
						$ulos .= "\n".t("Jälkitoimituksesta ei löytynyt lisättäviä rivejä").".\n";
					}
					else {
						echo t("Jälkitoimituksesta ei löytynyt lisättäviä rivejä")."!<br>";
					}
				}
			}
			else {
				if ($filesta == 'EDI') {
					$ulos .= "\n".t("Jälkitoimituksesta ei löytynyt lisättäviä rivejä.")."\n";
				}
				else {
					echo t("Jälkitoimituksesta ei löytynyt lisättäviä rivejä")."!<br>";
				}
			}

			return $ulos;
		}
	}
?>