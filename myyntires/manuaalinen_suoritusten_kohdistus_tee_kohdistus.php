<?php
	// estetään sivun lataus suoraan
	if (!empty($HTTP_GET_VARS["oikeus"]) ||
	    !empty($HTTP_POST_VARS["oikeus"]) ||
	    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
	    !isset($oikeus)) {

	  echo "<p>".t("Kielletty toiminto")."!</p>";
	  exit;
	}

	$laskutunnukset = "";
	$laskutunnuksetkale = "";

	// $lasku_tunnukset[]
	if (is_array($lasku_tunnukset)){
		for ($i=0;$i<sizeof($lasku_tunnukset);$i++) {
			if($i!=0) $laskutunnukset=$laskutunnukset . ",";
			$laskutunnukset=$laskutunnukset . "$lasku_tunnukset[$i]";
		}
	}
	else {
		$laskutunnukset = 0;
	}

	// $lasku_tunnukset_kale[]
	if (is_array($lasku_tunnukset_kale)) {
		for ($i=0;$i<sizeof($lasku_tunnukset_kale);$i++) {
			if($i!=0) $laskutunnuksetkale=$laskutunnuksetkale . ",";
			$laskutunnuksetkale=$laskutunnuksetkale . "$lasku_tunnukset_kale[$i]";
		}
	}
	else {
		$laskutunnuksetkale = 0;
	}

	// Tarkistetaan muutama asia
	if ($laskutunnukset == 0 and $laskutunnuksetkale == 0) {
		echo "<font class='error'>".t("Olet kohdistamassa, mutta et ole valinnut mitään kohdistettavaa")."!</font>";
		exit;
	}

	if ($osasuoritus == 1) {
		if (sizeof($lasku_tunnukset) != 1) {
			echo "<font class='error'>".t("Osasuoritukseen ei ole valittu yhtä laskua")."</font>";
			exit;
		}
		if (sizeof($lasku_tunnukset_kale) > 0) {
			echo "<font class='error'>".t("Osasuoritukseen ei voi valita käteisalennusta")."</font>";
			exit;
		}
	}

	$query = "LOCK TABLES yriti READ, yhtio READ, tili READ, lasku WRITE, suoritus WRITE, tiliointi WRITE, sanakirja WRITE";
	$result = mysql_query($query) or pupe_error($query);

	// haetaan suorituksen tiedot
	$query = "	SELECT suoritus.tunnus tunnus,
				suoritus.asiakas_tunnus asiakas_tunnus,
				suoritus.tilino tilino,
				suoritus.summa summa,
				suoritus.valkoodi valkoodi,
				suoritus.kurssi kurssi,
				suoritus.asiakas_tunnus asiakastunnus,
				suoritus.kirjpvm maksupvm,
				suoritus.ltunnus ltunnus,
				suoritus.nimi_maksaja nimi_maksaja,
				yriti.oletus_rahatili kassatilino,
				tiliointi.tilino myyntisaamiset_tilino,
				yhtio.myynninkassaale kassa_ale_tilino,
				yhtio.pyoristys pyoristys_tilino,
				yhtio.myynninvaluuttaero myynninvaluuttaero_tilino
				FROM suoritus
				JOIN yriti ON (yriti.yhtio = suoritus.yhtio and yriti.tilino = suoritus.tilino)
				JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio and tiliointi.tunnus = suoritus.ltunnus and tiliointi.korjattu = '')
				JOIN yhtio ON (yhtio.yhtio = suoritus.yhtio)
				WHERE suoritus.yhtio = '$kukarow[yhtio]' and
				suoritus.tunnus = '$suoritus_tunnus' and
				suoritus.ltunnus != 0 and
				suoritus.kohdpvm = '0000-00-00'";
	$result = mysql_query($query) or pupe_error($query);

	// tehdään nätimpi errorihandlaus
	if (mysql_num_rows($result) == 0) {

		$query = "	SELECT * FROM suoritus
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$suoritus_tunnus' and
					ltunnus != 0 and
					kohdpvm = '0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suoritus katosi!")."</font>";
			exit;
		}

		$errorrow = mysql_fetch_array ($result);

		$query = "	SELECT * FROM yriti
					WHERE yhtio = '$errorrow[yhtio]' and
					tilino = '$errorrow[tilino]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suorituksen tilinumero ei löydy! Käy lisäämässä tili yhtiölle!")."</font>";
			exit;
		}

		$query = "	SELECT * FROM tiliointi
					WHERE yhtio = '$errorrow[yhtio]' and
					tunnus = '$errorrow[ltunnus]' and
					korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Suorituksen myyntisaamis-tiliöinnit eivät löydy! Kumoa tämä suoritus päittäin ja lisää se uudelleen järjestelmään!")."</font>";

			$query = "select ltunnus from tiliointi where yhtio='$errorrow[yhtio]' and tunnus='$errorrow[ltunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			$errorrow = mysql_fetch_array ($result);

			echo "<br><br><a href='../muutosite.php?tee=E&tunnus=$errorrow[ltunnus]'>Näytä tosite</a>";
			exit;
		}

	}

	$suoritus = mysql_fetch_array ($result);

	// otetaan talteen, jos suorituksen kassatilillä on kustannuspaikka.. tarvitaan jos suoritukselle jää saldoa
	$query = "select * from tiliointi WHERE aputunnus='$suoritus[ltunnus]' AND yhtio='$kukarow[yhtio]' and tilino='$suoritus[kassatilino]' and korjattu=''";
	$result = mysql_query($query) or pupe_error($query);
	$apurow = mysql_fetch_array($result);
	$apukustp = $apurow["kustp"];

	// haetaan laskujen tiedot
	$laskujen_summa=0;

	if ($osasuoritus == 1) {
		//*** Tässä yritetään hoitaa osasuoritus mahdollisimman elegantisti ***

		//Haetaan osasuoritettava lasku
		$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 0 AS alennus, tunnus, vienti_kurssi
					FROM lasku
					WHERE tunnus = '$laskutunnukset'
					and  mapvm='0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Osasuoritettava lasku katosi! (joku maksoi sen sinua ennen?)")."</font><br>";
			exit;
		}
		$lasku = mysql_fetch_array($result);

		$ltunnus			= $lasku["tunnus"];
		$maksupvm			= $suoritus["maksupvm"];
		$suoritussumma		= $suoritus["summa"];
		$suoritussummaval	= $suoritus["summa"];

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $suoritus["kurssi"],2);

		require ("manuaalinen_suoritusten_kohdistus_tee_korkolasku.php");

		// Aloitetaan kirjanpidon kirjaukset
		// Kassatili
		$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, ltunnus, selite, kustp)
	            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]','$suoritus[kassatilino]', $suoritussumma, '$ltunnus','Manuaalisesti kohdistettu suoritus (osasuoritus)','$apukustp')";
		$result = mysql_query($query) or pupe_error($query);

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $lasku["vienti_kurssi"],2);

		// Myyntisaamiset
		$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
	            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$ltunnus', '$suoritus[myyntisaamiset_tilino]', -1 * $suoritussumma,'Manuaalisesti kohdistettu suoritus (osasuoritus)')";
		$result = mysql_query($query) or pupe_error($query);

		// Suoritetaan valuuttalaskua
		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
			$valuuttaero = round($suoritussummaval * $lasku["vienti_kurssi"],2) - round($suoritussummaval * $suoritus["kurssi"],2);

			// Tuliko valuuttaeroa?
			if (abs($valuuttaero) >= 0.01) {
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
		            		VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$ltunnus', '$suoritus[myynninvaluuttaero_tilino]', $valuuttaero,'Manuaalisesti kohdistettu suoritus (osasuoritus)')";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "	UPDATE lasku
						SET saldo_maksettu_valuutassa=saldo_maksettu_valuutassa+$suoritussummaval
						WHERE tunnus=$ltunnus
						AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// Jos tämän suorituksen jälkeen ei enää jää maksettavaa valuutassa
			if ($lasku["summa_valuutassa"] == $suoritus["summa"]) {
				 $lisa = ", mapvm=now()";
			}
		}
		else {
			//jos tämän suorituksen jälkeen ei enää jää maksettavaa niin merkataan lasku maksetuksi
			if ($lasku["summa"] == $suoritus["summa"]) {
				 $lisa = ", mapvm=now()";
			}
		}

		$query = "	UPDATE lasku
					SET viikorkoeur = '$korkosumma', saldo_maksettu=saldo_maksettu+$suoritussumma $lisa
					WHERE tunnus	= $ltunnus
					AND yhtio		= '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		//Merkataan suoritus käytetyksi ja yliviivataan sen tiliöinnit
		$query = "UPDATE suoritus SET kohdpvm=now(), summa=0 WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)
		$query = "SELECT aputunnus, ltunnus FROM tiliointi WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			die ("Tiliöinti1 kateissa " . $suoritus["tunnus"]);
		}
		$tiliointi = mysql_fetch_array ($result);

		$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		//echo "<font class='message'>$query</font><br>";

		if (mysql_num_rows($result) != 1) {
			die ("Tiliöinti2 kateissa " . $suoritus["tunnus"]);
		}

		$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	else {
		//*** Tässä käsitellään tavallinen suoritus ***
		$laskujen_summa = 0;

		if($laskutunnukset != 0) {
			$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 0 AS alennus, 0 AS alennus_valuutassa, tunnus, vienti_kurssi, tapvm
						FROM lasku WHERE tunnus IN ($laskutunnukset)
						and yhtio = '$kukarow[yhtio]'
						and mapvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != sizeof($lasku_tunnukset)) {
				echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset)."'</font><br>";
				exit;
			}

			while($lasku = mysql_fetch_array($result)){
				$laskut[] = $lasku;
				$laskujen_summa				+=$lasku["summa"];
				$laskujen_summa_valuutassa	+=$lasku["summa_valuutassa"];
			}
		}

		// Alennukset
		if($laskutunnuksetkale != 0) {
			$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, kasumma AS alennus, kasumma_valuutassa AS alennus_valuutassa, tunnus, vienti_kurssi, tapvm
						FROM lasku WHERE tunnus IN ($laskutunnuksetkale)
						AND yhtio = '$kukarow[yhtio]'
						and mapvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != sizeof($lasku_tunnukset_kale)) {
				echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset_kale)."'</font><br>";
				exit;
			}

		    while($lasku = mysql_fetch_array($result)){
				$laskut[] = $lasku;
				$laskujen_summa				+= $lasku["summa"] - $lasku["alennus"];
				$laskujen_summa_valuutassa	+= $lasku["summa_valuutassa"] - $lasku["alennus_valuutassa"];
			}
		}

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
			$kaatosumma = round($suoritus["summa"] - $laskujen_summa_valuutassa,2);

			echo "<font class='message'>".t("Tilitapahtumalle jää pyöristyksen jälkeen")." $kaatosumma $suoritus[valkoodi]</font><br>";
		}
		else {
			$kaatosumma = round($suoritus["summa"] - $laskujen_summa,2);

			echo "<font class='message'>".t("Tilitapahtumalle jää pyöristyksen jälkeen")." $kaatosumma $suoritus[valkoodi]</font><br>";
		}

		//Jos heittoa ja kirjataan kassa-alennuksiin etsitään joku sopiva lasku (=iso summa)
		if($kaatosumma != 0 and $pyoristys_virhe_ok == 1) {
			echo "<font class='message'>".t("Kirjataan kassa-aleen")."</font> ";

			$query = "	SELECT tunnus, laskunro
						FROM lasku
						WHERE tunnus IN ($laskutunnukset,$laskutunnuksetkale)
						AND yhtio = '$kukarow[yhtio]'
						ORDER BY summa desc
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kaikki laskut katosivat")." (sys err)</font<br>";
				exit;
			}
			else {
				$kohdistuslasku = mysql_fetch_array($result);
			}
		}

		// Tiliöidään myyntisaamiset
		if (is_array($laskut)) {

			$kassaan = 0;

			foreach ($laskut as $lasku) {

				// lasketaan korko
				$ltunnus			= $lasku["tunnus"];
				$maksupvm			= $suoritus["maksupvm"];
				$suoritussumma		= $suoritus["summa"];
				$suoritussummaval	= $suoritus["summa"];

				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $suoritus["kurssi"],2);

				require ("manuaalinen_suoritusten_kohdistus_tee_korkolasku.php");

				//Kohdistammeko pyöristykset ym:t tähän?
			 	if($kaatosumma != 0 and $pyoristys_virhe_ok == 1 and $lasku["tunnus"] == $kohdistuslasku["tunnus"]) {
			 		echo "<font class='message'>".t("Sijoitin lisäkassa-alen laskulle").": $kohdistuslasku[laskunro]</font> ";

					if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
						$lasku["alennus_valuutassa"] = round($lasku["alennus_valuutassa"] - $kaatosumma,2);

						echo "<font class='message'>".t("Uusi kassa-ale").": $lasku[alennus_valuutassa] $suoritus[valkoodi]</font> ";
					}
					else {
						$lasku["alennus"] = round($lasku["alennus"] - $kaatosumma,2);

						echo "<font class='message'>".t("Uusi kassa-ale").": $lasku[alennus] $suoritus[valkoodi]</font> ";
					}

					$kaatosumma = 0;
			 	}

				// Tehdään valuuttakonversio kassa-alennukselle
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
					$lasku["alennus"] = round($lasku["alennus_valuutassa"] * $suoritus["kurssi"],2);
				}

				// Mahdollinen kassa-alennus
				if($lasku["alennus"] != 0) {
					// Kassa-alessa on huomioitava alv, joka voi olla useita vientejä
					$totkasumma = 0;

					#Etsitään myynti-tiliöinnit
					$query = "	SELECT summa, vero, kustp, kohde, projekti
								FROM tiliointi use index (tositerivit_index)
								WHERE ltunnus	= '$lasku[tunnus]'
								and yhtio 		= '$kukarow[yhtio]'
								and tapvm 		= '$lasku[tapvm]'
								and abs(summa) <> 0
								and tilino	   <> '$yhtiorow[myyntisaamiset]'
								and tilino	   <> '$yhtiorow[konsernimyyntisaamiset]'
								and tilino	   <> '$yhtiorow[alv]'
								and tilino	   <> '$yhtiorow[varasto]'
								and tilino	   <> '$yhtiorow[varastonmuutos]'
								and tilino	   <> '$yhtiorow[pyoristys]'
								and tilino	   <> '$yhtiorow[myynninkassaale]'
								and tilino	   <> '$yhtiorow[factoringsaamiset]'
								and korjattu 	= ''";
					$yresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($yresult) == 0) {
						echo "<font class='error'>".t("En löytänyt laskun myynnin vientejä! Alv varmaankin heittää")."</font> ";

						$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite, vero)
									VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[kassa_ale_tilino]', '$lasku[alennus]', 'Manuaalisesti kohdistettu suoritus (alv ongelma)', '0')";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {

						$isa = 0;

						while ($tiliointirow = mysql_fetch_array ($yresult)) {
							// Kuinka paljon on tämän viennin osuus
							$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) * -1 / $lasku["summa"] * $lasku["alennus"],2);

							if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
								//$alv:ssa on alennuksen alv:n maara
								$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
								//$summa on alviton alennus
								$summa -= $alv;
							}

							// Kuinka paljon olemme kumulatiivisesti tiliöineet
							$totkasumma += $summa + $alv;

							$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite, vero, kustp, kohde, projekti)
										VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[kassa_ale_tilino]', $summa, 'Manuaalisesti kohdistettu suoritus', '$tiliointirow[vero]', '$tiliointirow[kustp]', '$tiliointirow[kohde]', '$tiliointirow[projekti]')";
							$result = mysql_query($query) or pupe_error($query);
							$isa = mysql_insert_id ($link);

							if ($tiliointirow['vero'] != 0) {

								$query = "	INSERT into tiliointi set
											yhtio 		= '$kukarow[yhtio]',
											ltunnus 	= '$lasku[tunnus]',
											tilino 		= '$yhtiorow[alv]',
											tapvm 		= '$suoritus[maksupvm]',
											summa 		= $alv,
											vero 		= '',
											selite 		= '$selite',
											lukko 		= '1',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now(),
											aputunnus 	= $isa";
								$xresult = mysql_query($query) or pupe_error($query);
							}
						}

						//Hoidetaan mahdolliset pyöristykset
						$heitto = $totkasumma - $lasku["alennus"];

						if (abs($heitto) >= 0.01) {
							echo "<font class='message'>".t("Kassa-alvpyöristys")." $heitto</font> ";

							$query = "	UPDATE tiliointi SET summa = summa - $totkasumma + $lasku[alennus]
										WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
							$xresult = mysql_query($query) or pupe_error($query);

							$isa = 0;
						}
					}
				}

				// Tehdään valuuttakonversio kassasuoritukselle
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
					$suoritettu_kassaan = round(($lasku["summa_valuutassa"] * $suoritus["kurssi"])-$lasku["alennus"], 2);
				}
				else {
					$suoritettu_kassaan = $lasku["summa"] - $lasku["alennus"];
				}

				// Kassatili
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, ltunnus, selite, kustp)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]','$suoritus[kassatilino]', $suoritettu_kassaan, $lasku[tunnus], 'Manuaalisesti kohdistettu suoritus','$apukustp')";
				$result = mysql_query($query) or pupe_error($query);

				// Lasketaan summasummarum paljonko ollaan tiliöity kassaan
				$kassaan += $suoritettu_kassaan;

				// Myyntisaamiset
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[myyntisaamiset_tilino]', -1*$lasku[summa], 'Manuaalisesti kohdistettu suoritus')";
				$result = mysql_query($query) or pupe_error($query);

				// Tuliko valuuttaeroa?
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {

					$valero = round($lasku["summa"] - $suoritettu_kassaan - $lasku["alennus"], 2);

					if (abs($valero) >= 0.01) {
						$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
				            		VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[myynninvaluuttaero_tilino]', $valero, 'Manuaalisesti kohdistettu suoritus')";
						$result = mysql_query($query) or pupe_error($query);
					}
				}

				$query = "	UPDATE lasku
							SET mapvm='$suoritus[maksupvm]',  viikorkoeur='$korkosumma', saldo_maksettu=0, saldo_maksettu_valuutassa=0
							WHERE tunnus = $lasku[tunnus]
							AND yhtio	 = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "UPDATE suoritus SET kohdpvm=now(), summa=$kaatosumma WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)
			$query = "SELECT aputunnus, ltunnus, summa FROM tiliointi WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				die ("Tiliöinti1 kateissa " . $suoritus["tunnus"]);
			}
			$tiliointi = mysql_fetch_array ($result);

			$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				die ("Tiliöinti2 kateissa " . $suoritus["tunnus"]);
			}

			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// Jääkö suoritukselle vielä saldoa
			$erotus = round($tiliointi["summa"] + $kassaan, 2);

			if ($erotus != 0) {
				//Myyntisaamiset
				$query="INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus[maksupvm]','$tiliointi[ltunnus]','$suoritus[myyntisaamiset_tilino]', $erotus,'Käsin syötetty suoritus')";
				$result = mysql_query($query) or pupe_error($query);
				$ttunnus = mysql_insert_id($link);

				//Kassatili
				$query="INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,aputunnus,lukko,kustp) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus[maksupvm]','$tiliointi[ltunnus]','$suoritus[kassatilino]',$erotus*-1,'Käsin syötetty suoritus',$ttunnus,'1','$apukustp')";
				$result = mysql_query($query) or pupe_error($query);

				// Päivitetään osoitin
				$query = "UPDATE suoritus SET ltunnus = '$ttunnus', kohdpvm = '0000-00-00' WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}

	echo "<br><font class='message'>".t("Kohdistus onnistui").".</font><br>";

	$query = "UNLOCK TABLES";
	$result = mysql_query($query) or pupe_error($query);

	$tila			= "suorituksenvalinta";
	$asiakas_tunnus = $suoritus["asiakas_tunnus"];
?>
