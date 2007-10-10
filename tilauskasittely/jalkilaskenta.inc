<?php

/* Tälle on anenttava

$tuoteno 	= korjattava tuote
$pvm 		= mihin päivään asti korjataan
$uusihinta 	= mikä on tuon pvm:n oikea ostohinta
$rivitunnus = mikä on tapahtuman tehneen rivin tunnus

Palautetaan $jlerror, joka on 1, jos jotain epätavallista sattui

*/

$jalkilaskenta_debug = 0; // 0 = ei echoilla debug koodia, 1 = ehchoillaa infoa, 2 = echoillaan infoa plus queryt

if (!function_exists("kopioitiliointi")) {

	function kopioitiliointi ($tunnus, $viivaaja) {

		//Tehdään kopio tiliöinnistä $tunnus
		//Jos $viivaajassa on jotain tehdään uusi vienti yliviivattuna

		global $kukarow;

		$query = "SELECT * FROM tiliointi WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "Tiliöintirivi kateissa Systeemivirhe!";
		}
		else {
			$tiliointirow = mysql_fetch_array($result);

			$query = "INSERT into tiliointi set ";

			for ($i=0; $i<mysql_num_fields($result); $i++) {

				if (mysql_field_name($result, $i) != 'tunnus') {

					if ($viivaaja != '') {

						if (mysql_field_name($result, $i) == 'korjattu') {
							$query .= "korjattu ='$viivaaja',";
						}
						elseif (mysql_field_name($result, $i) == 'korjausaika') {
							$query .= "korjausaika = now(),";
						}
						else {
							$query .= mysql_field_name($result,$i) . "='" . $tiliointirow[$i] . "',";
						}
					}
					else {
						$query .= mysql_field_name($result,$i) . "='" . $tiliointirow[$i] . "',";
					}

				}
			}

			$query  = substr($query,0,-1);
			$result = mysql_query($query) or pupe_error($query);
		}
	}
}

if (!function_exists("korjaalaskut")) {

	function korjaalaskut($tuoteno, $kehahin, $alku, $loppu, $korjtunnus) {

		global $kukarow, $yhtiorow, $jalkilaskenta_debug;
		$jlerror=0;

		//Etsitään tuotteen tiliöintitiedot
		$query = "	SELECT tuoteno, tilino, kustp, kohde, projekti, osasto, try
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);

 		// So far so good
		if (mysql_num_rows($result) == 1) {

			$tuote = mysql_fetch_array ($result);

			if ($korjtunnus != 0) {
				$lisa = " and tunnus = '$korjtunnus' ";
			}
			else {
				$lisa = "";
			}

			//etstään tuotteen kaikki myyntitilausrivit
			$query= "	SELECT otunnus oltunnus, uusiotunnus ltunnus, tunnus ttunnus, kpl, kate, rivihinta, laskutettuaika, alv
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'L'
						and tuoteno = '$tuoteno'
						and laskutettuaika >= left('$alku',10)
						and laskutettuaika <= left('$loppu',10)
						$lisa";
			$tilausriviresult = mysql_query($query) or pupe_error($query);

			if ($jalkilaskenta_debug > 1) echo "$query<br>";
			if ($jalkilaskenta_debug >= 1) echo "<font class='message'>".t("Löydettiin tuotteelle")." $tuoteno ".mysql_num_rows($tilausriviresult)." ".t("tilausriviä").".</font><br>";

			while ($tilausrivi = mysql_fetch_array ($tilausriviresult)) {

				// haetaan laskun tiedot
				$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausrivi[ltunnus]'";
				$laskures = mysql_query($query) or pupe_error($query);
				$lasku    = mysql_fetch_array($laskures);

				// katotaan saadaanko tehdä tiliöintejä
				// tehdään tilikausi_alku ja korjauksen_alku muuttujista PHP:ssä vertailukelpoisia
				list($tkvv,$tkkk,$tkpp) = split("-",$yhtiorow["tilikausi_alku"]);
				list($klvv,$klkk,$klpp) = split("-",$tilausrivi["laskutettuaika"]);

				$tilikausi_alku = (int) date('Ymd',mktime(0,0,0,$tkkk,$tkpp,$tkvv));
				$korjaus_alku   = (int) date('Ymd',mktime(0,0,0,$klkk,$klpp,$klvv));

				// yritetään korjata jotain joka on lukitulla tilikaudella.. estetään se
				if ($korjaus_alku < $tilikausi_alku) {
					echo "<font class='error'>".t("Korjauksia tiliöinteihin/katteisiin ei voida tehdä, koska myyntilasku on lukitulla tilikaudella")."! (".t("Lasku").": $tilausrivi[ltunnus] ".t("Tapvm").": $tilausrivi[laskutettuaika])</font><br>";
				}
				else {

					//Ok, löydämmekö vastaavan tiliöinnin laskulta
					$query = "	SELECT *
								FROM tiliointi
				 				WHERE yhtio 	= '$kukarow[yhtio]'
				 				and ltunnus		= '$tilausrivi[ltunnus]'
				 				and tilino 		= '$yhtiorow[varastonmuutos]'
				 				and kustp 		= '$tuote[kustp]'
				 				and kohde 		= '$tuote[kohde]'
				 				and projekti 	= '$tuote[projekti]'
								and korjattu 	= ''
								ORDER BY summa desc
								LIMIT 1";
					$result = mysql_query($query) or pupe_error($query);

					// otetaan löydettyjen rivien määrä talteen
					$tilikpl = mysql_num_rows($result);

					// lasketaan uusi kate
					$uusikate = $tilausrivi['rivihinta'] - $tilausrivi['kpl'] * $kehahin;

					// katotaan mikä on muutos
					$muutos = round($uusikate - $tilausrivi["kate"], 2); // roundataan kahteen desimaaliin, koska meillä ei ole sen tarkempaa infoa tietokannassa niin vältytään turhilta muutoksilta

					// debug
					if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Uusikate = $tilausrivi[rivihinta] - $tilausrivi[kpl] * $kehahin (muutos = $muutos)</font><br>";

					// jos ei löydetä varastonmuutostiliöintiä ja kate on muuttunut...
					if ($tilikpl == 0 and $muutos <> 0) {

						// tutkaillaan ensiks josko kustannuspaikat, kohteet tai projektit on muuttunut...
						$query = "	SELECT *
									FROM tiliointi
					 				WHERE yhtio  = '$kukarow[yhtio]'
					 				and ltunnus  = '$tilausrivi[ltunnus]'
					 				and tilino   = '$yhtiorow[varastonmuutos]'
									and korjattu = ''
									ORDER BY summa desc
									LIMIT 1";
						$result = mysql_query($query) or pupe_error($query);

						// löydettiin varastonmuutos tiliöintijä.. otetaan kpl talteen ja jatketaan chekeissä eteenpän
						if (mysql_num_rows($result) != 0) {
							$tilikpl = mysql_num_rows($result);
						}
						else {
							// ei löydetty yhtään varastonmuutos tiliöintiä.. lisätään se nyt.
							$tiliointisumma = $muutos;

							// echotaan tää toistaiseksi aina.. että joku zekkaa, että menee oikein.
							if ($jalkilaskenta_debug >= 1)
								echo "<font class='message'>luodaan uudet tiliöinnit, tarkista pliis että meni oikein. laskunro: $lasku[laskunro] ($lasku[nimi]) varasto: $tiliointisumma varastonmuutos: $muutos</font><br>";

							// Kirjataan 'varasto'-tilille
							$query = "INSERT into tiliointi set
										yhtio 		= '$kukarow[yhtio]',
										ltunnus 	= '$lasku[tunnus]',
										tilino 		= '$yhtiorow[varasto]',
										kustp 		= '$tuote[kustp]',
										kohde 		= '$tuote[kohde]',
										projekti	= '$tuote[projekti]',
										tapvm 		= '$lasku[tapvm]',
										summa		= '$tiliointisumma',
										vero		= '0',
										selite 		= 'Varastostamyynti $lasku[nimi]',
										lukko 		= '',
										laatija 	= '$kukarow[kuka]',
										laadittu 	= now()";
							$laskutusres = mysql_query($query) or pupe_error($query);
							if ($jalkilaskenta_debug > 1) echo "$query<br>";

							$tiliointisumma = $muutos * -1;

							// Kirjataan 'varastonmuutos'-tilille
							$query = "INSERT into tiliointi set
										yhtio 		= '$kukarow[yhtio]',
										ltunnus		= '$lasku[tunnus]',
										tilino 		= '$yhtiorow[varastonmuutos]',
										kustp 		= '$tuote[kustp]',
										kohde 		= '$tuote[kohde]',
										projekti 	= '$tuote[projekti]',
										tapvm 		= '$lasku[tapvm]',
										summa 		= '$tiliointisumma',
										vero 		= '0',
										selite 		= 'Varastostamyynti $lasku[nimi]',
										lukko 		= '',
										laatija 	= '$kukarow[kuka]',
										laadittu 	= now()";
							$laskutusres = mysql_query($query) or pupe_error($query);
							if ($jalkilaskenta_debug > 1) echo "$query<br>";

							// korjataan laskun kate
							$query = "UPDATE lasku SET kate = kate - $tilausrivi[kate] + $uusikate WHERE tunnus='$tilausrivi[ltunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

							// korjataan tilauksen kate
							$query = "UPDATE lasku SET kate = kate - $tilausrivi[kate] + $uusikate WHERE tunnus='$tilausrivi[oltunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

							// ja lopuksi korjataan tilausrivin kate
							$query = "UPDATE tilausrivi SET kate = $uusikate WHERE tunnus='$tilausrivi[ttunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

						}
					} // end jos ei löydetty yhtään

					// jos löydetään varastonmuutostiliöinti ja kate on muuttunut.. tehdään tarvittavat muutokset
					if ($tilikpl == 1 and $muutos <> 0) {

						if ($jalkilaskenta_debug >= 1) echo "<font class='message'>".t("Laskun")." $tilausrivi[ltunnus] ".t("varastonmuutostiliöinti löytyi").".</font><br>";

						$tiliointi1 = mysql_fetch_array($result);

						$query = "	SELECT *
									FROM tiliointi
				 					WHERE yhtio 	= '$kukarow[yhtio]'
									and ltunnus		= '$tilausrivi[ltunnus]'
									and tilino 		= '$yhtiorow[varasto]'
									and kustp 		= '$tuote[kustp]'
									and kohde 		= '$tuote[kohde]'
									and projekti 	= '$tuote[projekti]'
									and korjattu 	= ''
									ORDER BY summa asc
									LIMIT 1";
						$result = mysql_query($query) or pupe_error($query);

						// varmistetaan vielä, että löydetään varastotiliöintikin...
						if (mysql_num_rows($result) == 0) {
							$query = "	SELECT *
										FROM tiliointi
					 					WHERE yhtio 	= '$kukarow[yhtio]'
										and ltunnus		= '$tilausrivi[ltunnus]'
										and tilino 		= '$yhtiorow[varasto]'
										and korjattu 	= ''
										ORDER BY summa asc
										LIMIT 1";
							$result = mysql_query($query) or pupe_error($query);
						}

						// Toivottavasti jotain löyty
						if (mysql_num_rows($result) == 1) {

							if ($jalkilaskenta_debug >= 1) echo "<font class='message'>".t("Laskun")." $tilausrivi[ltunnus] ".t("varastotiliöinti löytyi").".</font><br>";

							$tiliointi2 = mysql_fetch_array ($result);

							// korjataan varastonmuutostiliöinti
							kopioitiliointi($tiliointi1['tunnus'], $kukarow['kuka']); // kopioidaan vanha tiliöintirivi ja yliviivataan se
							$query = "UPDATE tiliointi SET summa = summa + $tilausrivi[kate] - $uusikate, laadittu=now(), laatija='$kukarow[kuka]' WHERE tunnus='$tiliointi1[tunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

							// korjataan varastotiliöinti
							kopioitiliointi($tiliointi2['tunnus'], $kukarow['kuka']); // kopioidaan vanha tiliöintirivi ja yliviivataan se
							$query = "UPDATE tiliointi SET summa = summa - $tilausrivi[kate] + $uusikate, laadittu=now(), laatija='$kukarow[kuka]' WHERE tunnus='$tiliointi2[tunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

							// korjataan laskun kate
							$query = "UPDATE lasku SET kate = kate - $tilausrivi[kate] + $uusikate WHERE tunnus='$tilausrivi[ltunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

							// korjataan tilauksen kate
							$query = "UPDATE lasku SET kate = kate - $tilausrivi[kate] + $uusikate WHERE tunnus='$tilausrivi[oltunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

							// ja lopuksi korjataan tilausrivin kate
							$query = "UPDATE tilausrivi SET kate = $uusikate WHERE tunnus='$tilausrivi[ttunnus]'";
							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$katekorjres = mysql_query($query) or pupe_error($query);

						}
						else {

							echo "<font class='error'>".t("Emme osanneet korjata kirjanpitoa eikä katteita!")." ".t("Laskulta ei löydy sopivaa tiliöintiä (varasto)")."
							tuoteno:									$tuote[tuoteno],
							tilino: 									$yhtiorow[varastonmuutos],
							kustp:  									$tuote[kustp],
							kohde:  									$tuote[kohde],
							projekti:									$tuote[projekti],
							tiliöinnin summa: 							summa - $tilausrivi[kate] + $uusikate,
							laskun ($tilausrivi[ltunnus]) kate: 		kate - $tilausrivi[kate] + $uusikate,
							tilausrivin ($tilausrivi[ttunnus]) kate: 	$uusikate
							</font><br><br>";

							if ($jalkilaskenta_debug > 1) echo "$query<br>";
							$jlerror = 1;
						}
					} // end jos löydettiin yks

					// jos löydetään monta varastonmuutostiliöintiä ja kate on muuttunut...ei osata vielä tehä mitään :(
					if ($tilikpl > 1 and $muutos <> 0) {

						echo "<font class='error'>".t("Emme osanneet korjata kirjanpitoa eikä katteita!")." ".t("Laskulta löytyi monta tiliöintiä (varastonmuutos)")."
						".t("tuoteno").":							    		    $tuote[tuoteno],
						".t("tilino").":										    $yhtiorow[varastonmuutos],
						".t("kustp").":							    			    $tuote[kustp],
						".t("kohde").":							    			    $tuote[kohde],
						".t("projekti").":						    			    $tuote[projekti],
						".t("tiliöinnin summa").":						    	    summa + $tilausrivi[kate] - $uusikate,
						".t("laskun")." ($tilausrivi[ltunnus]) ".t("kate").":	    kate - $tilausrivi[kate] + $uusikate,
						".t("tilausrivin")." ($tilausrivi[ttunnus]) ".t("kate").":	$uusikate
						</font><br><br>";

						if ($jalkilaskenta_debug > 1) echo "$query<br>";
						$jlerror = 1;

					} // end jos löydettiin enemmän ku yks

					// Jos tuote on käytetty niin joudumme korjaamaan marginaaleja
					if ($muutos <> 0) {
						$query = "	SELECT kaytetty, sarjanumero
									FROM sarjanumeroseuranta
									WHERE yhtio 		 = '$kukarow[yhtio]'
									and tuoteno 		 = '$tuoteno'
									and myyntirivitunnus = '$tilausrivi[ttunnus]'
									and kaytetty   	    != ''";
						$margres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($margres) == 1) {
							if ($jalkilaskenta_debug > 1) echo "$tuoteno on käytetty ja marginaaliverotuksen alainen! Korjataan tiliöintejä.<br>";

							$query = "	SELECT tiliointi.tunnus, tiliointi.summa, tiliointi.vero, alvitiliointi.tunnus alvitunnus, alvitiliointi.summa alvisumma
										FROM tiliointi
										LEFT JOIN tiliointi alvitiliointi ON tiliointi.tunnus=alvitiliointi.aputunnus and alvitiliointi.korjattu=''
					 					WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
										and tiliointi.ltunnus  = '$tilausrivi[ltunnus]'
										and tiliointi.tilino   = '$yhtiorow[myynti_marginaali]'
										and tiliointi.kustp    = '$tuote[kustp]'
										and tiliointi.kohde    = '$tuote[kohde]'
										and tiliointi.projekti = '$tuote[projekti]'
										and tiliointi.selite like 'Marginaaliverolasku/Marginaali%'
										and tiliointi.korjattu = ''
										ORDER BY tiliointi.summa asc
										LIMIT 1";
							$result = mysql_query($query) or pupe_error($query);

							// otetaan löydettyjen rivien määrä talteen
							$tilikpl = mysql_num_rows($result);

							if ($tilikpl == 1) {
								$tilirivi = mysql_fetch_array($result);

								$edmarg    = $tilirivi["summa"] + $tilirivi["alvisumma"];
								$uusimarg  = $tilirivi["summa"] + $tilirivi["alvisumma"] + $tilausrivi["kate"] - $uusikate;

								$uusialv   = round($uusimarg - ($uusimarg / (1+ $tilirivi["vero"] / 100)), 2);
								$uusimarg -= $uusialv;

								// korjataan marginaalitiliöinti
								kopioitiliointi($tilirivi['tunnus'], $kukarow['kuka']); // kopioidaan vanha tiliöintirivi ja yliviivataan se
								$query = "UPDATE tiliointi SET summa = '$uusimarg', laadittu=now(), laatija='$kukarow[kuka]' WHERE tunnus='$tilirivi[tunnus]'";
								if ($jalkilaskenta_debug > 1) echo "$query<br>";
								$result = mysql_query($query) or pupe_error($query);

								if ($tilirivi["alvitunnus"] > 0) {
									// korjataan marginaalin alvi tiliöinti
									kopioitiliointi($tilirivi['alvitunnus'], $kukarow['kuka']); // kopioidaan vanha tiliöintirivi ja yliviivataan se
									$query = "UPDATE tiliointi SET summa = '$uusialv', laadittu=now(), laatija='$kukarow[kuka]' WHERE tunnus='$tilirivi[alvitunnus]'";
									if ($jalkilaskenta_debug > 1) echo "$query<br>";
									$result = mysql_query($query) or pupe_error($query);
								}
							}
							else {
								if($tilausrivi["alv"] != 0) {
									if ($tilausrivi["alv"] >= 500) {
										$tilausrivi['alv'] = $tilausrivi['alv'] - 500;
									}

									$uusimarg  = $tilausrivi["kate"] - $uusikate;
									$uusialv   = round($uusimarg - ($uusimarg / (1+ $tilausrivi["alv"] / 100)), 2);
									$uusimarg -= $uusialv;
								}
								else {
									$uusimarg  = $tilausrivi["kate"] - $uusikate;
									$uusialv   = 0;
								}

								$query = "	INSERT into tiliointi set
											yhtio 		= '$kukarow[yhtio]',
											ltunnus		= '$lasku[tunnus]',
											tilino 		= '$yhtiorow[myynti_marginaali]',
											kustp 		= '$tuote[kustp]',
											kohde 		= '$tuote[kohde]',
											projekti 	= '$tuote[projekti]',
											tapvm 		= '$lasku[tapvm]',
											summa 		= '$uusimarg',
											vero 		= '$tilausrivi[alv]',
											selite 		= 'Marginaaliverolasku/Marginaali $lasku[nimi] $lasku[nimitark]',
											lukko 		= '1',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$laskutusres = mysql_query($query) or pupe_error($query);
								$isa = mysql_insert_id();

								if ($jalkilaskenta_debug > 1) echo "$query<br>";

								if ($uusialv != 0) {
									$query = "	INSERT into tiliointi set
												yhtio 		= '$kukarow[yhtio]',
												ltunnus		= '$lasku[tunnus]',
												tilino 		= '$yhtiorow[alv]',
												kustp 		= '$tuote[kustp]',
												kohde 		= '$tuote[kohde]',
												projekti 	= '$tuote[projekti]',
												tapvm 		= '$lasku[tapvm]',
												summa 		= '$uusialv',
												vero 		= '0',
												selite 		= 'Marginaaliverolasku/Marginaali $lasku[nimi] $lasku[nimitark]',
												lukko 		= '1',
												laatija 	= '$kukarow[kuka]',
												laadittu 	= now(),
												aputunnus	= $isa";
									$laskutusres = mysql_query($query) or pupe_error($query);
									if ($jalkilaskenta_debug > 1) echo "$query<br>";
								}
							}

							$query = "	SELECT tiliointi.tunnus
										FROM tiliointi
					 					WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
										and tiliointi.ltunnus  = '$tilausrivi[ltunnus]'
										and tiliointi.tilino   = '$yhtiorow[myynti_marginaali]'
										and tiliointi.kustp    = '$tuote[kustp]'
										and tiliointi.kohde    = '$tuote[kohde]'
										and tiliointi.projekti = '$tuote[projekti]'
										and tiliointi.selite like 'Marginaaliverolasku/Myynti %'
										and tiliointi.korjattu = ''
										ORDER BY tiliointi.summa asc
										LIMIT 1";
							$result = mysql_query($query) or pupe_error($query);

							// otetaan löydettyjen rivien määrä talteen
							$tilikpl = mysql_num_rows($result);

							if ($tilikpl == 1) {
								$tilirivi = mysql_fetch_array($result);

								// korjataan marginaamyyntilitiliöinti
								kopioitiliointi($tilirivi['tunnus'], $kukarow['kuka']); // kopioidaan vanha tiliöintirivi ja yliviivataan se

								$query = "UPDATE tiliointi SET summa = summa - $tilausrivi[kate] + $uusikate, laadittu=now(), laatija='$kukarow[kuka]' WHERE tunnus='$tilirivi[tunnus]'";
								if ($jalkilaskenta_debug > 1) echo "$query<br>";
								$result = mysql_query($query) or pupe_error($query);
							}
							else {
								$query = "INSERT into tiliointi set
											yhtio 		= '$kukarow[yhtio]',
											ltunnus		= '$lasku[tunnus]',
											tilino 		= '$yhtiorow[myynti_marginaali]',
											kustp 		= '$tuote[kustp]',
											kohde 		= '$tuote[kohde]',
											projekti 	= '$tuote[projekti]',
											tapvm 		= '$lasku[tapvm]',
											summa 		= '".(0 - $tilausrivi["kate"] + $uusikate)."',
											vero 		= '0',
											selite 		= 'Marginaaliverolasku/Myynti $lasku[nimi] $lasku[nimitark]',
											lukko 		= '1',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$laskutusres = mysql_query($query) or pupe_error($query);
								if ($jalkilaskenta_debug > 1) echo "$query<br>";
							}
						}
					}
				}
			}
		}
		else {
			echo "<font class='error'>".t("Tuote katosi")."</font><br>";
			if ($jalkilaskenta_debug > 1) echo "$query<br>";
			$jlerror = 1;
		}
		return $jlerror;
	}
}

$jlerror=0;

if ($tuoteno != '') {

	if ($jalkilaskenta_debug >= 1) echo "<hr><font class='message'>".t("Aloitellaan käsittely tuotteelle")." $tuoteno.</font><br>";

	//Tuote
	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]'
				and tuoteno = '$tuoteno'";
	$result = mysql_query($query) or pupe_error($query);
	$tuote = mysql_fetch_array($result);

	if (mysql_num_rows($result) == 1 and $tuote["sarjanumeroseuranta"] == "S") {
		//Tähän haaraan mennään jos tuotteella on sarjanumeroseurantaa ja varastonarvo lasketaan suoraan osto ja myyntiriveiltä
		if ($jalkilaskenta_debug >= 1) echo "Tuotenumero $tuoteno on sarjanumeroseurannassa<br>";

		$query  = "	SELECT yhtio
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and ((tyyppi = 'O' and kpl > 0) or (tyyppi = 'L' and kpl < 0))
					and laskutettuaika != '0000-00-00'
					and tunnus = '$rivitunnus'";
		$rxiviresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($rxiviresult) == 1 and is_numeric($uusihinta)) {
			// Päivitetään ostotilausrivin rivihinta
			$query  = "	UPDATE tilausrivi
						SET rivihinta = round($uusihinta*kpl,2)
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			$selite_txt = ", Jälkilaskennan ostohinta: $uusihinta (".tv1dateconv(date("Y-m-d")).")";

			// Päivitetään korjattava tapahtuma
			$query = "	UPDATE tapahtuma
						SET kplhinta = '$uusihinta', selite = concat(selite, '$selite_txt')
						WHERE yhtio = '$kukarow[yhtio]'
						and laji in ('tulo','valmistus')
						and rivitunnus='$rivitunnus'";
			$paivresult = mysql_query($query) or pupe_error($query);

			// Haetaan kaikki tiedot sarjanumerosta
			$query = "	SELECT distinct myyntirivitunnus
						FROM sarjanumeroseuranta
						WHERE yhtio 		= '$kukarow[yhtio]'
						and ostorivitunnus  = '$rivitunnus'
						and ostorivitunnus != 0";
			$sarjares = mysql_query($query) or pupe_error($query);
			if ($jalkilaskenta_debug > 1) echo "$query<br>";

			while ($sarjarow = mysql_fetch_array($sarjares)) {
				// Hetaaan laskutetut myntirivit
				$query = "	SELECT *
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$sarjarow[myyntirivitunnus]'
							and tyyppi = 'L'
							and uusiotunnus != 0
							and laskutettuaika != '0000-00-00'
							and kpl != 0";
				$rivires = mysql_query($query) or pupe_error($query);
				if ($jalkilaskenta_debug > 1) echo "$query<br>";

				if (mysql_num_rows($rivires) == 1) {
					$rivirow = mysql_fetch_array($rivires);

					// Haetaan nyt tämän myyntirivin kaikki ostohinnat
					$uusisarjahin = sarjanumeron_ostohinta("myyntirivitunnus", $rivirow["tunnus"]);

					if ($uusisarjahin != 0 and $uusihinta <> $uusisarjahin) {
						if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Tän tuotteen ostohinta:$uusihinta - Myyyntirivin ($rivirow[tunnus]) ostohinta keskimäärin:$uusisarjahin</font><br>";

						$uusihinta = $uusisarjahin;
					}

					// Korjaus
					if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Korjataan in-out hintoja! Laskuja (1) Uusi hinta: $uusihinta $rivirow[uusiotunnus]</font><br>";

					$mitenkavi = korjaalaskut($tuoteno, $uusihinta, $rivirow["laskutettuaika"], $rivirow["laskutettuaika"], $rivirow["tunnus"]);

					$query = "	UPDATE tapahtuma
								SET hinta = '$uusihinta'
								WHERE yhtio = '$kukarow[yhtio]'
								and rivitunnus = '$sarjarow[myyntirivitunnus]'
								and laji in ('tulo', 'valmistus', 'laskutus', 'inventointi')";
					if ($jalkilaskenta_debug > 1) echo "$query<br>";
					$upresu = mysql_query($query) or pupe_error($query);
				}
			}
		}
		else {
			echo "<font class='error'>".t("Tilausriviä tai hintaa ei löydy")."</font><br>";
			$jlerror=1;
		}
	}
	elseif (mysql_num_rows($result) == 1) {
		//Tähän haaraan mennään jos tuotteella ei ole sarjanumeroseurantaa eli ylläpidetään keskihankintahintaa tuotteella

		//Alkusaldo
		$query  = "SELECT sum(saldo) saldo FROM tuotepaikat WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		$saldo  = mysql_fetch_array ($result);

		//Kääntöpiste
		$query = "SELECT * FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and laji in ('tulo','valmistus') and rivitunnus='$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);

		//taaksepäin yhteensopivuuden vuoksi kokeillaan vielä näin jos ei rivitunnuksella löydy
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Tarkkaa kääntöpisteen tapahtumaa ei löytynyt! Käytetään päivän ensimmäistä tuloa")." $pvm.</font><br>";

			$query = "SELECT * FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and left(laadittu,10)='$pvm' and laji in ('tulo','valmistus') limit 1";
			$result = mysql_query($query) or pupe_error($query);
		}
		//Jos samalla rivitunuksella löytyy monta tapahtumaa, niin oletamme että olemme saaneet sisään $tapahtumatunnus muuttujan
		elseif (mysql_num_rows($result) > 1) {
			echo "<font class='error'>".t("Samalla rivitunnuksella löytyi useita kääntöpisteitä! Käytämme sekä rivitunnusta että tapahtumatunnusta.")."</font><br>";

			$query = "SELECT * FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and laji in ('tulo','valmistus') and rivitunnus='$rivitunnus' and tunnus='$tapahtumatunnus'";
			$result = mysql_query($query) or pupe_error($query);
		}

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Ei tuloa tuolla päivällä")."</font><br>";
			if ($jalkilaskenta_debug > 1) echo "$query<br>";
			$jlerror=1;
		}
		else {
			$tapahtuma = mysql_fetch_array($result);
			$pvm = $tapahtuma["laadittu"]; // kääntöpisteen tarkka aika talteen
		}

		if (($tuote['epakurantti25pvm'] != '0000-00-00') or ($tuote['epakurantti50pvm'] != '0000-00-00') or ($tuote['epakurantti75pvm'] != '0000-00-00') or ($tuote['epakurantti50pvm'] != '0000-00-00')) {

			echo "<font class='error'>".t("Tätä tuotetta ei enää voi korjata")."</font><br>";
			if ($jalkilaskenta_debug > 1) echo "$query<br>";
			$jlerror=1;
		}

		if ($jlerror != 1) {

			$query = "	SELECT sum(kpl) saldomuutos, sum(if(laji='Epäkurantti',1,0)) epaku
						FROM tapahtuma
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoteno'
						and laadittu >= '$pvm'
						and kpl <> 0
						ORDER BY laadittu desc";
			$result = mysql_query($query) or pupe_error($query);

			if ($jalkilaskenta_debug > 1) echo "$query<br>";

			if (mysql_num_rows($result) > 0) {

				$trow = mysql_fetch_array ($result);

				// epäkuranttisuutta ei osata poistaa --> We are fucked!
				if ($trow['epaku'] > 0) {
					echo "<font class='error'>".t("Tuotteella on epäkuranntisuuden muutos. Emme osaa toimia!")."</font><br>";
					$jlerror=1;
				}

				$seliselilisa = "";
				$uusisaldo    = $saldo["saldo"];

				if ($trow["saldomuutos"] != 0) {
					$uusisaldo -= $trow["saldomuutos"];
					$seliselilisa = " ($saldo[saldo] - $trow[saldomuutos]) ";
				}

				// tämä muuttuja tulee valmistusten korjauksesta, ja muutellaan saldoa jos niin pitää tehdä
				if ($kaantopisteen_saldomuutos != 0) {
					$uusisaldo += $kaantopisteen_saldomuutos;
					$seliselilisa .= " + $kaantopisteen_saldomuutos ";
				}

				// haetaan tuotteelle edellinen kehahin
				$query = "	SELECT hinta
							FROM tapahtuma
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tuoteno'
							and laadittu < '$pvm'
							and hinta <> 0
							ORDER BY laadittu desc
							LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					$trow = mysql_fetch_array($result);
					$uusikehahin = $trow["hinta"];
				}
				else {
					$uusikehahin = 0;
				}

				// Nyt ollaan takaisin peruspäivässä.
				if ($jalkilaskenta_debug >= 1) echo "<br><font class='error'>".t("Olemme kääntöpisteessä. Saldo kääntöpisteessä:")." $uusisaldo $seliselilisa ".t("Kehahin kääntöpisteessä:")." $uusikehahin</font><br>";

				if ($jlerror == 0) {
					// Päivitetään korjattava tapahtuma
					$selite_txt = ", Jälkilaskennan ostohinta: $uusihinta (".tv1dateconv(date("Y-m-d")).")";
					$query = "UPDATE tapahtuma SET kplhinta='$uusihinta', selite = concat(selite, '$selite_txt') WHERE tunnus='$tapahtuma[tunnus]'";
					$result = mysql_query($query) or pupe_error($query);

					// haetaan ostotilausrivin tiedot
					$query = "SELECT * from tilausrivi WHERE yhtio='$kukarow[yhtio]' and tunnus='$rivitunnus'";
					$result = mysql_query($query) or pupe_error($query);
					$apujlrow = mysql_fetch_array($result);

					// lasketaan uusi rivihinta
					$testiapukala = round($uusihinta * $apujlrow["kpl"], 2);

					// päivitetään ostotilausrivin rivihinta
					$query  = "UPDATE tilausrivi set rivihinta = '$testiapukala' WHERE yhtio='$kukarow[yhtio]' and tunnus='$rivitunnus'";
					$result = mysql_query($query) or pupe_error($query);

					// Nyt lasketaan kaikki hankintahinnat uusiksi
					$query = "	SELECT tuoteno, laadittu, laji, kpl, kplhinta, hinta, tunnus
								FROM tapahtuma
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tuoteno'
								and laadittu >= '$pvm'
								and kpl <> 0
								ORDER BY laadittu";
					$result = mysql_query($query) or pupe_error($query);
					if ($jalkilaskenta_debug > 1) echo "$query<br>";

					if (mysql_num_rows($result) > 0) {
						$jlerror = 0;
						$eka = 1;
						$laskuja = 0;
						$korjattavia_laskuja = 0;
						$kpl = 0;
						$vlaadittu = '3000-01-01'; // varmuuden vuoks ettei korjailla nollasta lähtien jos käy jotain todellaa weird...

						if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Lasketaan uudet hinnat tuotteelle: $tuote[tuoteno] Sort: Asc</font><br><br>";

						while (($trow = mysql_fetch_array($result)) and ($jlerror == 0)) {

							// kun löydetään eka tulo, voidaan alkaa hommiin...
							if (($trow['laji'] == 'tulo' or $trow['laji'] == 'valmistus') and $eka == 1) {
								$vlaadittu = $trow['laadittu'];
								$eka = 0;
							}

							// jos kyseessä on tulo ja ollaan jo löydetty eka tulo
							if (($trow['laji'] == 'tulo' or $trow['laji'] == 'valmistus') and $eka == 0) {

								// jos ollaan käsitelty jo jotain laskuja, niin nyt on sitte alkamassa uusi tulo ni korjataan laskut ja nollataan laskurit
								if ($korjattavia_laskuja != 0) {
									// korjaus
									if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Korjataan! Laskuja ($laskuja) Kpl: $kpl Saldo: $uusisaldo Keskihinta: $uusikehahin Alku: $vlaadittu Loppu: $trow[laadittu] Case: 1</font><br>";
									$mitenkavi = korjaalaskut($tuoteno, $uusikehahin, $vlaadittu, $trow['laadittu'], 0);
									$uusisaldo += $kpl;
									// nollaillaan
									$laskuja = 0;
									$korjattavia_laskuja = 0;
									$kpl = 0;
								}

								if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Tulo. $trow[kpl] kappaletta. Saldo ennen tuloa: $uusisaldo Kehahin ennen tuloa: $uusikehahin Sort: Asc</font><br>";

								// kehahin matikka. lasketaan uusi kehahin jos päädytään plussalle ja lähdetään plussalta
								if ($uusisaldo + $trow['kpl'] > 0 and $uusisaldo > 0 and ($uusikehahin * $uusisaldo + $trow['kplhinta'] * $trow['kpl']) > 0) {
									if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Uusi kehahin (Matikka): uusikehahin = ($uusikehahin * $uusisaldo + $trow[kplhinta] * $trow[kpl]) / ($uusisaldo + $trow[kpl])</font><br>";
									$uusikehahin = round(($uusikehahin * $uusisaldo + $trow['kplhinta'] * $trow['kpl']) / ($uusisaldo + $trow['kpl']),4);
								}
								else {
									if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Uusi kehahin: (Ei matikkaa): uusikehahin = $trow[kplhinta]</font><br>";
									$uusikehahin = $trow['kplhinta'];
								}

								// otetaan tämä päivä laskennan alkupäiväksi
								$vlaadittu = $trow['laadittu'];

								// summaillaan uutta saldoa
								$uusisaldo += $trow['kpl'];

							} // end if laji == tulo


							// jos kyseessä on laskutus ja ollaan löydetty eka tulo, summaillaan kappaleita
							if ($trow['laji'] == 'laskutus' and $eka == 0) {
								$laskuja++;
								$kpl += $trow['kpl'];

								// plussaillaan tätä vaan jos hinta muuttuu niin tiedetään pitääkö tehdä jälkilaskentaa
								if ($trow["hinta"] != $uusikehahin) {
									$korjattavia_laskuja++;
								}
							}

							// jos kyseessä on inventointi ja ollaan löydetty jo eka tulo
							if ($trow['laji'] == 'Inventointi' and $eka == 0) {
								// etsitään tuotteen tiliöinti
								$query = "	select *
											from lasku use index (yhtio_tila_tapvm)
											where yhtio='$kukarow[yhtio]' and
											tila = 'X' and
											tapvm >= '$trow[laadittu]' and
											viite = '$trow[tunnus]'";
								$invresult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($invresult) == 1) {
									// löydettiin inventointi tosite
									$invrow = mysql_fetch_array($invresult);

									if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Korjattiin tiliöinti! ".t("Inventoitu").": $trow[tuoteno] @ $trow[laadittu]</font><br>";

									// haetaan varastotiliöinti
									$query = "select * from tiliointi WHERE yhtio='$kukarow[yhtio]' and ltunnus='$invrow[tunnus]' and tilino='$yhtiorow[varasto]' and korjattu=''";
									if ($jalkilaskenta_debug > 1) echo "$query<br>";
									$invupd = mysql_query($query) or pupe_error($query);
									$invrow = mysql_fetch_array($invupd);

									// katotaan saadaanko tehdä tiliöintejä
									// tehdään tilikausi_alku ja korjauksen_alku muuttujista PHP:ssä vertailukelpoisia
									list($tkvv,$tkkk,$tkpp) = split("-",$yhtiorow["tilikausi_alku"]);
									list($klvv,$klkk,$klpp) = split("-",$trow["laadittu"]);

									$tilikausi_alku = (int) date('Ymd',mktime(0,0,0,$tkkk,$tkpp,$tkvv));
									$korjaus_alku   = (int) date('Ymd',mktime(0,0,0,$klkk,$klpp,$klvv));

									// yritetään korjata jotain joka on lukitulla tilikaudella.. estetään se
									if ($korjaus_alku < $tilikausi_alku) {
										echo "<font class='error'>".t("Korjauksia tiliöinteihin ei voida tehdä, koska tapahtuma on lukitulla tilikaudella")."! (".t("Inventointi").": $trow[laadittu])</font><br>";
									}
									else {
										// yliviivataan kaikki tositteen aktiivit tiliöinnit
										$query = "UPDATE tiliointi SET korjattu='$kukarow[kuka]', korjausaika=now() WHERE yhtio='$kukarow[yhtio]' and ltunnus='$invrow[ltunnus]' and korjattu=''";
										if ($jalkilaskenta_debug > 1) echo "$query<br>";
										$invupd = mysql_query($query) or pupe_error($query);

										// kirjotetaan uudet tiliönnit
										$query = "INSERT INTO tiliointi SET
													yhtio    = '$kukarow[yhtio]',
													ltunnus  = '$invrow[ltunnus]',
													tilino   = '$yhtiorow[varastonmuutos]',
													kustp    = '',
													tapvm    = '$invrow[tapvm]',
													summa    = $trow[kpl] * $uusikehahin * -1,
													vero     = '0',
													lukko    = '',
													selite   = '$invrow[selite]',
													laatija  = '$kukarow[kuka]',
													laadittu = now()";
										if ($jalkilaskenta_debug > 1) echo "$query<br>";
										$invupd = mysql_query($query) or pupe_error($query);

										// kirjotetaan uudet tiliönnit
										$query = "INSERT INTO tiliointi SET
													yhtio    = '$kukarow[yhtio]',
													ltunnus  = '$invrow[ltunnus]',
													tilino   = '$yhtiorow[varasto]',
													kustp    = '',
													tapvm    = '$invrow[tapvm]',
													summa    = $trow[kpl] * $uusikehahin,
													vero     = '0',
													lukko    = '',
													selite   = '$invrow[selite]',
													laatija  = '$kukarow[kuka]',
													laadittu = now()";
										if ($jalkilaskenta_debug > 1) echo "$query<br>";
										$invupd = mysql_query($query) or pupe_error($query);
									}
								}
								else {
									$invapuluku = $trow["kpl"] * $uusikehahin;
									echo "<font class='error'>".t("Törmättiin inventointitapahtumaan, mutta ei osattu korjata sen tiliöintejä! Korjaa tiliöinti käsin!")." ".t("Inventoitu").": $trow[tuoteno] @ $trow[laadittu] ".t("Summa").": $trow[kpl] * $uusikehahin = $invapuluku</font><br>";
								}
							}


							// jos ollaa löydetty eka tulo voidaan aina päivitetään tapahtuma jos se on tulo, laskutus tai inventointi
							if ($eka == 0) {

								if ($trow["laji"] == "kulutus") {
									$kulutuslisa = ", kplhinta='$uusikehahin' ";
								}
								else {
									$kulutuslisa = "";
								}

								// päivitetään hinta vain jos hinta on eri
								if ($trow["hinta"] != $uusikehahin) {
									$query = "UPDATE tapahtuma SET hinta = '$uusikehahin' $kulutuslisa WHERE tunnus = '$trow[tunnus]' and laji in ('tulo', 'valmistus', 'laskutus', 'inventointi', 'kulutus')";
									if ($jalkilaskenta_debug > 1) echo "$query<br>";
									$upresu = mysql_query($query) or pupe_error($query);
								}
							}

						} // end while

						// jos meillä on jotain korjattavia laskuja, jota ei korjattu whilen sisällä ni korjataan ne ny
						if ($korjattavia_laskuja != 0 and $eka == 0) {
							$uusisaldo += $kpl;
							if ($jalkilaskenta_debug >= 1) echo "<font class='message'>Korjataan! Laskuja ($laskuja) Kpl: $kpl Saldo: $uusisaldo Keskihinta: $uusikehahin Alku: $vlaadittu Loppu: 3000-01-01 Case: 2</font><br>";
							$mitenkavi = korjaalaskut($tuoteno, $uusikehahin, $vlaadittu, '3000-01-01', 0);
						}

					} // end if numrows > 0

					// päivitetään lopulta tuotteelle uusi kehahin ja vihahin
					$query = "UPDATE tuote SET kehahin = '$uusikehahin', vihahin = '$uusihinta' WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
					if ($jalkilaskenta_debug > 1) echo "$query<br>";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					echo "<font class='error'>".t("Tuotteella ei tapahtumia")."</font><br><br>";
					if ($jalkilaskenta_debug > 1) echo "$query<br>";
					$jlerror=1;
				}
			}
			else {
				echo "<font class='error'>".t("Tuotetta ei löydy")."</font><br>";
				if ($jalkilaskenta_debug > 1) echo "$query<br>";
				$jlerror=1;
			}
		}
	}
	else {
		echo "<font class='error'>".t("Tuotetta ei löydy")."</font><br>";

		if ($jalkilaskenta_debug > 1) echo "$query<br>";
		$jlerror=1;
	}
}

?>
