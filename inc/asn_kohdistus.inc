<?php

	if (!function_exists("asn_kohdistus")) {

		function asn_kohdistus() {

			global $yhtiorow, $kukarow;

			// Määritellään käytettävät muuttujat
			unset($edellinen_toimittaja);
			unset($edellinen_asn_numero);
			unset($edellinen_paketinnumero);
			$paketin_tunnukset = array();
			$paketin_rivit = array();
			$paketin_tuotteet = array();
			$virheet = 0;
			$virhe = "";

			// Haetaan kaikki käsittelemättömät ASN-rivit
			$query = "	SELECT *
						FROM asn_sanomat
						WHERE yhtio = '$kukarow[yhtio]'
						AND status = ''
						ORDER BY toimittajanumero, asn_numero, paketinnumero";
			$result = pupe_query($query);

			// Loopataan tuoterivit läpi
			while ($row = mysql_fetch_assoc($result)) {

				// Toimittaja vaihtuu tai ASN-sanoma vaihtuu tai paketti vaihtuu ja ei ole virheitä, teidään keikka ja suuntalava sekä kohdistetaan rivit
				if ((isset($edellinen_toimittaja) and $edellinen_toimittaja != $row["toimittajanumero"]) or (isset($edellinen_asn_numero) and $edellinen_asn_numero != $row["asn_numero"]) or (isset($edellinen_paketinnumero) and $edellinen_paketinnumero != $row["paketinnumero"])) {
					if ($virheet == 0) {
						asn_kohdista_suuntalava($edellinen_toimittaja, $edellinen_asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset);
					}
					else {
						$virheet = 0;
						echo "VIRHE: Paketti hylatty. Toimittaja $edellinen_toimittaja ASN-sanoma $edellinen_asn_numero $virhe Paketti $edellinen_paketinnumero\n\n";
					}
					$paketin_rivit = array();
					$paketin_tunnukset = array();
					$paketin_tuotteet = array();
				}

				// Katsotaan, löytyykö tämä rivi ostotilaukselta
				$query = "	SELECT tilausrivi.*
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.toimittajanro = '$row[toimittajanumero]')
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '$row[toim_tuoteno]')
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus = 0
							AND tilausrivi.otunnus = '$row[tilausnumero]'
							AND (tilausrivi.tunnus = '$row[tilausrivinpositio]' OR tilausrivi.tilaajanrivinro = '$row[tilausrivinpositio]')";
				$checkres = pupe_query($query);
				$tilausrivirow = mysql_fetch_array($checkres);

				// Tarkistetaan kappaleet ja splitataan ostotilausrivi, jos kappaleita on asn-sanomalla VÄHEMMÄN kuin tilausrivillä, vanhan tilausrivin tunnus jää jäljellä
				if (mysql_num_rows($checkres) == 0 or $row["kappalemaara"] > $tilausrivirow["varattu"]) {
					// Tuli enemmän, tämä on virhe
					$virheet++;
					if (mysql_num_rows($checkres) == 0) {
						echo "VIRHE: Sopivaa ostotilausrivia ei loydy. Toimittaja $row[toimittajanumero] ASN-sanoma $row[asn_numero] Paketti $row[paketinnumero] Tuote $row[toim_tuoteno]\n";
					}
					else {
						echo "VIRHE: Toimitettu enemman kuin tilattu. Toimittaja $row[toimittajanumero] ASN-sanoma $row[asn_numero] Paketti $row[paketinnumero] Tuote $row[toim_tuoteno] Tilattu $tilausrivirow[varattu] Toimitettu $row[kappalemaara]\n";
					}
				}
				elseif ($row["kappalemaara"] < $tilausrivirow["varattu"]) {
					// Splitataan tilausrivi, tallennetaan vanha rivitunnus tilausrivi.tilaajanrivinro kenttään

					$kappaleerotus = $tilausrivirow["varattu"] - $row["kappalemaara"];

					// Päivitetään alkuperäiselle riville saapunut kappalemäärä
					$query = "	UPDATE tilausrivi SET
								varattu = '$row[kappalemaara]'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$tilausrivirow[tunnus]'";
					$upres = pupe_query($query);

					// Tehdään uusi rivi, jossa on jäljelle jääneet kappaleet
					$query = "	INSERT INTO tilausrivi SET
								yhtio = '$tilausrivirow[yhtio]',
								tyyppi = '$tilausrivirow[tyyppi]',
								toimaika = '$tilausrivirow[toimaika]',
								kerayspvm = '$tilausrivirow[kerayspvm]',
								otunnus = '$tilausrivirow[otunnus]',
								tuoteno = '$tilausrivirow[tuoteno]',
								try = '$tilausrivirow[try]',
								osasto = '$tilausrivirow[osasto]',
								nimitys = '$tilausrivirow[nimitys]',
								yksikko = '$tilausrivirow[yksikko]',
								varattu = '$kappaleerotus',
								hinta = '$tilausrivirow[hinta]',
								laatija = '$tilausrivirow[laatija]',
								laadittu = '$tilausrivirow[laadittu]',
								hyllyalue = '$tilausrivirow[hyllyalue]',
								hyllynro = '$tilausrivirow[hyllynro]',
								hyllytaso = '$tilausrivirow[hyllytaso]',
								hyllyvali = '$tilausrivirow[hyllyvali]',
								tilaajanrivinro = '$tilausrivirow[tunnus]'";
					$inskres = pupe_query($query);
				}

				// Otetaan talteen tämän kierroksen tiedot
				$edellinen_toimittaja = $row["toimittajanumero"];
				$edellinen_asn_numero = $row["asn_numero"];
				$edellinen_paketinnumero = $row["paketinnumero"];
				$paketin_tunnukset[] = $row["tunnus"];
				$paketin_rivit[] = $tilausrivirow["tunnus"];
				$paketin_tuotteet[] = $tilausrivirow["tuoteno"];
			}

			if ($virheet == 0 and count($paketin_rivit) > 0) {
				asn_kohdista_suuntalava($edellinen_toimittaja, $edellinen_asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset);
			}
			elseif ($virheet != 0) {
				echo "VIRHE: Paketti hylatty. Toimittaja $edellinen_toimittaja ASN-sanoma $edellinen_asn_numero $virhe Paketti $edellinen_paketinnumero\n\n";
			}

		}

	}

	if (!function_exists("asn_kohdista_suuntalava")) {

		function asn_kohdista_suuntalava($edellinen_toimittaja, $edellinen_asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset) {

			global $yhtiorow, $kukarow;

			// Haetaan toimittajan tiedot
			$toimhaku = "	SELECT *
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]'
							AND toimittajanro = '$edellinen_toimittaja'";
			$checkres = pupe_query($toimhaku);

			if (mysql_num_rows($checkres) != 1) {
				echo "VIRHE: Toimittajanumero: $edellinen_toimittaja ei löydy jarjestelmasta!\n";
			}
			else {
				// Jos kaikki paketin rivit olivat kunnossa, tehdään suuntalava ja linkataan ostotilausrivi keikkaan sekä suuntalavaan
				$toimittajarow = mysql_fetch_array($checkres);

				// Tehdään keikka, jos sitä ei vielä ole. ASN-numero löytyy comments kentästä.
				$query = "	SELECT lasku.tunnus
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila = 'K'
							AND vanhatunnus = 0
							AND liitostunnus = '$toimittajarow[tunnus]'
							AND comments = '$edellinen_asn_numero'";
				$checkres = pupe_query($query);

				// Tehdään keikka
				if (mysql_num_rows($checkres) == 0) {
					
					$query = "	SELECT kurssi
								FROM valuu
								WHERE yhtio = '$kukarow[yhtio]'
								AND nimi = '$toimittajarow[oletus_valkoodi]'";
					$checkres = pupe_query($query);
					$row = mysql_fetch_array($checkres);
					$kurssi = $row["kurssi"];
					
					$query  = "LOCK TABLE lasku WRITE";
					$result = pupe_query($query);

					$query = "	SELECT max(laskunro)
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tila = 'K'";
					$checkres = pupe_query($query);
					$row = mysql_fetch_array($checkres);
					$id = $row[0] + 1;

					$maa_lahetys = $toimittajarow['maa_lahetys'] != '' ? $toimittajarow['maa_lahetys'] : $toimittajarow['maa'];

					// meillä on $toimittajarow haettuna ylhäällä
					$query = "	INSERT into lasku set
								yhtio        			= '$kukarow[yhtio]',
								laskunro     			= '$id',
								ytunnus	     			= '$toimittajarow[ytunnus]',
								nimi         			= '$toimittajarow[nimi]',
								valkoodi     			= '$toimittajarow[oletus_valkoodi]',
								vienti       			= '$toimittajarow[oletus_vienti]',
								vienti_kurssi			= '$kurssi',
								toimitusehto 			= '$toimittajarow[toimitusehto]',
								osoite       			= '$toimittajarow[osoite]',
								postitp      			= '$toimittajarow[postitp]',
								maa			 			= '$toimittajarow[maa]',
								maa_lahetys 			= '$maa_lahetys',
								comments				= '$edellinen_asn_numero',
								kauppatapahtuman_luonne	= '$toimittajarow[kauppatapahtuman_luonne]',
								kuljetusmuoto			= '$toimittajarow[kuljetusmuoto]',
								rahti					= '$toimittajarow[oletus_kulupros]',
								swift					= '$toimittajarow[swift]',
								liitostunnus 			= '$toimittajarow[tunnus]',
								tila         			= 'K',
								luontiaika	 			= now(),
								laatija		 			= '$kukarow[kuka]'";
					$insertres = pupe_query($query);
					$keikan_tunnus = mysql_insert_id();
					
					$query  = "UNLOCK TABLE";
					$result = pupe_query($query);
				}
				else {
					$keikkarow = mysql_fetch_array($checkres);
					$keikan_tunnus = $keikkarow["tunnus"];
				}

				$query = "	SELECT tuote.keraysvyohyke, keraysvyohyke.terminaalialue, count(*)
							FROM tuote
							JOIN keraysvyohyke on (keraysvyohyke.yhtio = tuote.yhtio and keraysvyohyke.tunnus = tuote.keraysvyohyke)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							AND tuote.tuoteno IN (".implode(",", $paketin_tuotteet).")
							GROUP BY 1
							ORDER BY 3 DESC
							LIMIT 1";
				$checkres = pupe_query($query);
				$row = mysql_fetch_array($checkres);

				// Tehdään uusi suuntalava
				$tee = "lisaa";
				$otunnus = $keikan_tunnus;
				$sscc = $edellinen_asn_numero;
				$tyyppi = 10;
				$keraysvyohyke = $row['keraysvyohyke'];
				$kaytettavyys = "A";
				$terminaalialue = $row['terminaalialue'];
				$korkeus = 0;
				$paino = 0;
				$alkuhyllyalue = "";
				$alkuhyllynro = "";
				$alkuhyllyvali = "";
				$alkuhyllytaso = "";
				$loppuhyllyalue = "";
				$loppuhyllynro = "";
				$loppuhyllyvali = "";
				$loppuhyllytaso = "";
				$automaattinen_paivitys = "";
				$suuntalavat_ei_kayttoliittymaa = "KYLLA";

				require ("tilauskasittely/suuntalavat.inc");
				// Saadaan $uusi_suuntalavan_tunnus ^ incistä.
				// Päivitetään paketin ostotilausrivit kohdistetuksi keikkan ja oikealle suuntalavalle
				$query = "	UPDATE tilausrivi SET
							uusiotunnus = '$keikan_tunnus',
							suuntalava = '$uusi_suuntalavan_tunnus'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tilausrivi.tunnus IN (".implode(",", $paketin_rivit).")";
				$updateres = pupe_query($query);

				$query = "	UPDATE asn_sanomat SET
							status = 'X'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus IN (".implode(",", $paketin_tunnukset).")";
				$updateres = pupe_query($query);
			}

		}
	}

?>