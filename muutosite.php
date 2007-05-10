<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Tiliöintien muutos/selailu")."</font><hr>";

	if (($tee == 'U' or $tee == 'P' or $tee == 'M' or $tee == 'J') and ($oikeurow['paivitys'] != 1)) {
		echo "<font class='error'>".t("Yritit päivittää vaikka sinulla ei ole siihen oikeuksia")."</font>";
		exit;
	}
	if ($tee == 'J') { // Jaksotus
		require "inc/jaksota.inc";
	}
	if ($tee == 'M') { // Otsikon muutokseen
		require "inc/muutosite.inc";
	}
	if ($tee == 'G') { // Seuraava "tosite"
			$query = "SELECT tapvm, tunnus
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and
							  tunnus > '$tunnus'
						ORDER by tunnus
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) > 0) {
					$trow=mysql_fetch_array ($result);
					$tunnus=$trow['tunnus'];
					$tee='E';
			}
			else {
				echo "<font class='error'>".t("Ei seuraavaa tositetta")."</font><br>";
				$tee = 'E';
			}				
	}
	if (($tee == 'Y') or ($tee == 'Z') or ($tee == 'X') or ($tee == 'W') or ($tee == 'T')) { // Tositeselailu
		if  (($tee == 'Z') or ($tee == 'X') or ($tee == 'W') or ($tee == 'T')) {
// Etsitään virheet vain kuluvalta tilikaudelta!

			if ($tee == 'Z') {
				$query = "SELECT ltunnus, tapvm, round(sum(summa),2) summa, 'n/a', 'n/a', 'n/a', selite
							FROM tiliointi use index (yhtio_tapvm_tilino)
							WHERE yhtio = '$kukarow[yhtio]' and korjattu='' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]'
							GROUP BY ltunnus, tapvm
							HAVING summa <> 0";
			}
			if ($tee == 'X') {
				$query = "SELECT ltunnus, tapvm, summa, 'n/a', 'n/a', 'n/a', selite
							FROM tiliointi use index (yhtio_tilino_tapvm), tili use index (tili_index)
							WHERE tiliointi.yhtio = '$kukarow[yhtio]' and tili.yhtio = '$kukarow[yhtio]' and
									tiliointi.tilino = tili.tilino and
									korjattu='' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' and
									sisainen_taso like '3%' and kustp = '' and tiliointi.tilino!='$yhtiorow[myynti]' and tiliointi.tilino!='$yhtiorow[myynti_ei_eu]' and tiliointi.tilino!='$yhtiorow[myynti_eu]' and tiliointi.tilino!='$yhtiorow[varastonmuutos]' and tiliointi.tilino!='$yhtiorow[pyoristys]'";
			}
			if ($tee == 'W') {
				$query = "SELECT ltunnus, count(*) maara, round(sum(summa),2) heitto, 'n/a', 'n/a', 'n/a', selite
							FROM tiliointi use index (yhtio_tilino_tapvm)
							WHERE yhtio='$kukarow[yhtio]' and korjattu='' and
							tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' and tilino = '$yhtiorow[ostovelat]'
							GROUP BY ltunnus
							HAVING maara > 1 and heitto <> 0";
			}
			if ($tee == 'T') {
				$query = "SELECT ltunnus, count(*) maara, tila, 'n/a', 'n/a', 'n/a', selite
							FROM tiliointi use index (yhtio_tilino_tapvm)
							LEFT JOIN lasku ON  lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
							WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu='' and
							tiliointi.tapvm >= '$yhtiorow[tilikausi_alku]' and tiliointi.tapvm <= '$yhtiorow[tilikausi_loppu]' and tilino = '$yhtiorow[ostovelat]' and tila < 'R'
							GROUP BY ltunnus
							HAVING maara > 1";
			}
		}
		else {
			
			$plisa = "";
			$lisa  = "";
			$vlisa = "";
			$summa = str_replace ( ",", ".", $summa);

			$tav += 0; // Tehdään pvmstä numeroita
			$tak += 0;
			$tap += 0;

			if ($tav > 0 and $tav < 1000) $tav += 2000;

			if ($tav != 0 and $tak != 0 and $tap != 0) {
				$plisa = " and tapvm = '$tav-$tak-$tap' ";
			}
			elseif ($tav != 0 and $tak != 0) {
				$plisa = " and tapvm >= '$tav-$tak-01' and tapvm < '".date("Y-m-d",mktime(0, 0, 0, $tak+1, 1, $tav))."' ";
			}
			elseif ($tav != 0) {
				$plisa = " and tapvm >= '$tav-01-01' and tapvm < '".date("Y-m-d",mktime(0, 0, 0, 1, 1, $tav+1))."' ";
			}
			else {
				$plisa = " and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' ";
			}

			if (strlen($selite) > 0) {
				$lisa = " and selite";
				if ($ohita == 'on') {
					$lisa .= " not ";
				}
				$lisa .= " like '%" . $selite . "%'";
			}
			
			if (strlen($tilino) > 0) {
				$lisa .= " and tiliointi.tilino = '" . $tilino . "'";
			}
			
			if (strlen($summa) > 0) {
				$summa += 0; // tehdään siitä numero
				$lisa .= " and abs(tiliointi.summa) = $summa";
			}
			
			if (strlen($laatija) > 0) {
				$lisa .= " and tiliointi.laatija = '" . $laatija . "'";
			}
			
			if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
				if (strlen($tositenro) > 0) {
					list($tositenro1, $tositenro2) = split("-",$tositenro);
					$tositenro1 = (int) $tositenro1;
					$tositenro2 = (int) $tositenro2;
					$tositenro = sprintf ('%02d', $tositenro1) . sprintf ('%06d', $tositenro2);
					$lisa .= " and tiliointi.tosite = '$tositenro' ";
				}
			}
			$summa = "";

			if ($viivatut != 'on') {
				$vlisa = "and tiliointi.korjattu=''";
			}
			else {
				$slisa = ", concat_ws('@', korjattu, korjausaika) korjaus";
			}

			$query = "	SELECT tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, tili.tilino,
						tili.nimi, vero, selite $slisa
						FROM tiliointi use index (yhtio_tapvm_tilino), tili
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
						$plisa
						$vlisa
						$lisa
						ORDER BY tiliointi.ltunnus desc, tiliointi.tunnus";
		}

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Haulla ei löytynyt yhtään tositetta")."</font>";
			$tyhja = 1;
		}
		else {
			echo "<table><tr>";
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";
			echo "<tr>";
			while ($trow=mysql_fetch_array ($result)) {
				// Ei anneta tämän hämätä meitä!
				if ($trow[7] == '@0000-00-00 00:00:00') $trow[7] = '';
				if ($trow[7] == '0000-00-00 00:00:00') $trow[7] = '';				
				//Laitetaan linkki tuonne pvm:ään, näin voimme avata tositteita tab:eihin
				
				if (strlen($edtunnus) > 0) {
					if ($trow[0] != $edtunnus) { // Tosite vaihtui
						echo "<tr><th height='10' colspan='".mysql_num_fields($result)."'></th></tr><tr>";

					}
					else {
						echo "<td></td></tr><tr>";
					}
				}

				$edtunnus = $trow[0];

				for ($i=1; $i<mysql_num_fields($result); $i++) {
					if ($i==1) 
						echo "<td><a href = '$PHP_SELF?tee=E&tunnus=$edtunnus&viivatut=$viivatut'>$trow[$i]</td>";
					else {
						if (($viivatut == 'on') && (strlen($trow[7]) > 0)) {
							echo "<th>$trow[$i]</th>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
				}
			}
		}
		echo "</tr></table><br><br>";
		$tee = "";
	}

	if ($tee == 'P') { // Olemassaolevaa tiliöintiä muutetaan, joten yliviivataan rivi ja annetaan perustettavaksi
		$query = "SELECT tilino, kustp, kohde, projekti, summa, vero, selite, tapvm, tosite
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo t("Tiliöintiä ei löydy! Systeemivirhe!");
			exit;
		}
		$tiliointirow=mysql_fetch_array($result);
		$tili = $tiliointirow['tilino'];
		$kustp = $tiliointirow['kustp'];
		$kohde = $tiliointirow['kohde'];
		$projekti = $tiliointirow['projekti'];
		$summa = $tiliointirow['summa'];
		$vero = $tiliointirow['vero'];
		$selite = $tiliointirow['selite'];
		$tiliointipvm = $tiliointirow['tapvm'];
		$tositenro = $tiliointirow['tosite'];
		$ok = 1;

// Etsitään kaikki tiliöintirivit, jotka kuuluvat tähän tiliöintiin ja lasketaan niiden summa

		$query = "SELECT sum(summa) FROM tiliointi
					WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu='' GROUP BY aputunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {
			$summarow=mysql_fetch_array($result);
			$summa += $summarow[0];
			$query = "UPDATE tiliointi SET korjattu = '$kukarow[kuka]', korjausaika = now()
						WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu=''";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "UPDATE tiliointi
					SET korjattu = '$kukarow[kuka]', korjausaika = now()
					WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "E"; // Näytetään miltä tosite nyt näyttää
	}

	if ($tee == 'U') { // Lisätään tiliöintirivi
		$summa = str_replace ( ",", ".", $summa);
		$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?
		require "inc/tarkistatiliointi.inc";
		$tiliulos=$ulos;
		$ulos='';
		$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) != 1) {
			echo t("Laskua ei enää löydy! Systeemivirhe!");
			exit;
		}
		else $laskurow=mysql_fetch_array($result);
		
		if (($kpexport==1) or (strtoupper($yhtiorow['maa']) != 'FI')) { //Tarvitaan kenties tositenro
			if ($tositenro != 0) {
				$query = "SELECT tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and tosite='$tositenro'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) == 0) {
					echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
					$tositenro=0;
				}
			} else { //Tällä ei vielä ole tositenroa. Yritetään jotain
				switch ($laskurow['tila']) {
					case "X" : // Tämä on muistiotosite, sillä voi olla vain yksi tositenro
						$query = "SELECT distinct tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus'";
						$result = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($result) != 1) {
							echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
							$tositenro=0;
						}
						else {
							$tositerow=mysql_fetch_array ($result);
							$tositenro = $tositerow['tosite'];
						}
						break;
				
					case 'U' : //Tämä on myyntilasku
						$query = "SELECT tosite FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus'";
						$result = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($result) != 0) {
							if ($laskurow['tapvm'] == $tiliointipvm) { // Tälle saamme tositenron myyntisaamisista
								$query = "SELECT tosite FROM tiliointi
											WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
													tapvm='$tiliointipvm' and tilino = '$yhtiorow[myyntisaamiset]' and
													summa = $laskurow[summa]";
								$result = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($result) == 0) {
									echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
									$tositenro=0;
								}
								else {
									$tositerow=mysql_fetch_array ($result);
									$tositenro = $tositerow['tosite'];
								}
							}
							else {
								if ($laskurow['tapvm'] != $tiliointipvm) { // Tälle saamme tositenron jostain samanlaisesta viennistä
									$query = "SELECT tosite FROM tiliointi
												WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
														tapvm='$tiliointipvm' and tilino != '$yhtiorow[myyntisaamiset]' and
														summa != $laskurow[summa]";
									$result = mysql_query($query) or pupe_error($query);
									if (mysql_num_rows($result) == 0) {
										echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
										$tositenro=0;
									}
									else {
										$tositerow=mysql_fetch_array ($result);
										$tositenro = $tositerow['tosite'];
									}
								}
							}
						}
						else {
							echo t("Tositenumeron tarkistus ei onnistu, koska tositteen kaikki tiliöinnit puuttuvat")."<br>";
							$tositenro=0;
						}
					default: //Tämän pitäisi olla nyt ostolasku
						if ($laskurow['tapvm'] == $tiliointipvm) { // Tälle saamme tositenron ostoveloista
							$query = "SELECT tosite FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
												tapvm='$tiliointipvm' and tilino = '$yhtiorow[ostovelat]' and
												summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
							$result = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($result) == 0) {
								echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
								$tositenro=0;
							}
							else {
								$tositerow=mysql_fetch_array ($result);
								$tositenro = $tositerow['tosite'];
							}
						}
						
						if ($laskurow['mapvm'] == $tiliointipvm) { // Tälle saamme tositenron ostoveloista
							$query = "SELECT tosite FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
												tapvm='$tiliointipvm' and tilino = '$yhtiorow[ostovelat]' and
												summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
							$result = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($result) == 0) {
								echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
								$tositenro=0;
							}
							else {
								$tositerow=mysql_fetch_array ($result);
								$tositenro = $tositerow['tosite'];
							}
						}
				}
			}
			echo "<font class='message'>Tiliöintirivi liitettiin tositteeseen $tositenro</font><br>";
		}
		$tee = 'E';
		if ($ok != 1) {
			require "inc/teetiliointi.inc";
			if ($jaksota == 'on') {
				$tee = 'U';
				require "inc/jaksota.inc"; // Jos jotain jaksotetaan on $tee J
			}
		}
	}
	if (($tee == 'E') or ($tee=='F')) { // Tositeen näyttö muokkausta varten
// Näytetään laskun tai tositteen tiedot....

		$query = "SELECT tila, concat_ws('@',lasku.laatija, lasku.luontiaika) Laatija,
						ytunnus, lasku.nimi, nimitark, osoite, osoitetark, postino, postitp, maa,
						lasku.valkoodi,
						concat_ws(' ',tapvm, mapvm) 'tapvm mapvm',
						if(kasumma = 0,'',
						if (tila = 'U',
						if(lasku.valkoodi='$yhtiorow[valkoodi]',concat_ws('@',kasumma, kapvm),concat(kasumma, ' (', round(kasumma/if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),lasku.valkoodi,')', '@', kapvm)),
						if(lasku.valkoodi='$yhtiorow[valkoodi]',concat_ws('@',kasumma, kapvm),concat(kasumma, ' (', round(kasumma*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),'$yhtiorow[valkoodi])', '@', kapvm)))) kassa_ale,
						if (tila = 'U',
						if(lasku.valkoodi='$yhtiorow[valkoodi]', concat_ws('@', summa, erpcm),concat(summa, ' (', round(summa/if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),lasku.valkoodi,')', '@', erpcm)),
						if(lasku.valkoodi='$yhtiorow[valkoodi]', concat_ws('@', summa, erpcm),concat(summa, ' (', round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),'$yhtiorow[valkoodi])', '@', erpcm))) summa,
						concat_ws('@',hyvak1,h1time) Hyväksyjä1,
						concat_ws('@',hyvak2,h2time) Hyväksyjä2,
						concat_ws('@',hyvak3,h3time) Hyväksyjä3,
						concat_ws('@',hyvak4,h4time) Hyväksyjä4,
						concat_ws('@',hyvak5,h5time) Hyväksyjä5,
						concat_ws('@',maksaja,maksuaika) Maksaja,
						tilinumero, concat_ws(' ', viite, viesti) Maksutieto,
						maa, ultilno, pankki1, pankki2, pankki3, pankki4, swift, clearing, maksutyyppi,
						ebid,
						toim_osoite, '' toim_osoitetark, toim_postino, toim_postitp, toim_maa, alatila, vienti, comments, yriti.nimi maksajanpankkitili
						FROM lasku
						LEFT JOIN yriti ON lasku.yhtio=yriti.yhtio and maksu_tili=yriti.tunnus
						WHERE lasku.tunnus = '$tunnus' and lasku.yhtio = '$kukarow[yhtio]'";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Tiliöintiä ei löydy! Systeemivirhe!");
			exit;
		}

		$trow=mysql_fetch_array($result);

		// jos pitää näyttää keikan tietoja
		if ($tee2 == "1") {

			// jos myyntilasku
			if ($trow["tila"] == "U" or $trow["tila"] == "L") {
				$query = "	select maa_maara,kauppatapahtuman_luonne,
							kuljetusmuoto,sisamaan_kuljetus,
							sisamaan_kuljetusmuoto,sisamaan_kuljetus_kansallisuus,
							kontti,aktiivinen_kuljetus,
							aktiivinen_kuljetus_kansallisuus,
							poistumistoimipaikka,poistumistoimipaikka_koodi,
							bruttopaino,lisattava_era,vahennettava_era
							from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$keikres = mysql_query($query) or pupe_error($query);
				$keikrow = mysql_fetch_array($keikres);
			}
			// muissa keiseissa varmaan sitten keikkajuttuja
			else {
				$query = "	select laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$tunnus'";
				$keikres = mysql_query($query) or pupe_error($query);
				$keekrow = mysql_fetch_array($keikres);

				$query = "	select laskunro keikka, maa_lahetys 'lähetysmaa',
							kuljetusmuoto, kauppatapahtuman_luonne, rahti, rahti_etu, rahti_huolinta, erikoisale, bruttopaino, toimaika, comments
							from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='' and laskunro='$keekrow[laskunro]'";
				$keikres = mysql_query($query) or pupe_error($query);
				$keikrow = mysql_fetch_array($keikres);
			
				$query = "select vanhatunnus, summa, nimi from lasku where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keekrow[laskunro]' and vanhatunnus!='0' and vanhatunnus!='$tunnus'";
				$muutkeikres = mysql_query($query) or pupe_error($query);
			}
		} 
		
		$laskuntila=$trow['tila'];
		$laskunpvm =$trow[11];
		if ($trow[14] == '@0000-00-00 00:00:00') $trow[14] = '';
		if ($trow[14] == '0000-00-00 00:00:00') $trow[14] = '';
		if ($trow[15] == '@0000-00-00 00:00:00') $trow[15] = '';
		if ($trow[15] == '0000-00-00 00:00:00') $trow[15] = '';
		if ($trow[16] == '@0000-00-00 00:00:00') $trow[16] = '';
		if ($trow[16] == '0000-00-00 00:00:00') $trow[16] = '';
		if ($trow[17] == '@0000-00-00 00:00:00') $trow[17] = '';
		if ($trow[17] == '0000-00-00 00:00:00') $trow[17] = '';
		if ($trow[18] == '@0000-00-00 00:00:00') $trow[18] = '';
		if ($trow[18] == '0000-00-00 00:00:00') $trow[18] = '';
		if ($trow[19] == '@0000-00-00 00:00:00') $trow[19] = '';
		if ($trow[19] == '0000-00-00 00:00:00') $trow[19] = '';		
		echo "<table><tr><td valign='top'>"; // Tämä taulukko pitää sisällään kaikki tositteen perustiedot

		// Yleiset tiedot
		echo "<table>"; // Tämä aloittaa vasemman sarakkeen
		for ($i = 1; $i < 2; $i++) {
			echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>";
		}
		if ($trow[0] != 'X') {
		// Laskut
			for ($i = 2; $i < 10; $i++) {
				echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
						<td>$trow[$i]</td></tr>";
			}
			// Tehdään nappula, jolla voidaan vaihtaa näkymäksi tilausrivit/tiliöintirivit
			if ($tee == 'F') {
				$ftee = 'E';
				$fnappula = t('Näytä tiliöinnit');
			}
			else {
				$ftee = 'F';
				$fnappula = t('Näytä tilausrivit');
			}
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value='$ftee'>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<tr><td><input type = 'submit' value = '$fnappula'></form></td>
				<form action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name = 'tee' value='G'>
					<input type = 'hidden' name = 'tunnus' value='$tunnus'>
					<td><input type = 'submit' value = '".t("Seuraava")."'><td></form>
				</tr>";
			echo "</table></td><td valign='top'>"; // Lopetettiin vasen sarake
			
			echo "<table>"; // Tässä tehdään uusi sarake oikealle
			//Ei näytetä myyntilaskulla hyväksyntää vain toimitustiedot
			if (($trow['tila'] == 'U') or ($trow['tila'] == 'L')) {

				if ($tee2!=1) { //Perustiedot
					for ($i = 10; $i < 14; $i++) {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
								<td>$trow[$i]</td></tr>";
					}
					for ($i = 32; $i < 37; $i++) {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
								<td>$trow[$i]</td></tr>";
					}
				}
				else { //Laajennetut
					for ($i = 0; $i < 14; $i++) {
						echo "<tr><th>" . t(mysql_field_name($keikres,$i)) ."</th>
							<td>$keikrow[$i]</td></tr>";
					}
				}
			}
			else { 
				if ($tee2!=1) { //Perustiedot
					for ($i = 10; $i < 19; $i++) {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
							<td>$trow[$i]</td></tr>";
					}
				}
				else { //Laajennetut
					for ($i = 0; $i < 11; $i++) {
						echo "<tr><th>" . t(mysql_field_name($keikres,$i)) ."</th>
							<td>$keikrow[$i]</td></tr>";
					}
					
					if (mysql_num_rows($muutkeikres) != 0) {
						echo "<tr><th>keikan muut laskut</td><td>";
						while ($muutkeikrow = mysql_fetch_array($muutkeikres)) {
							echo "<a href='muutosite.php?tee=E&tunnus=$muutkeikrow[vanhatunnus]'>$muutkeikrow[nimi] ($muutkeikrow[summa])</a><br>";
						}
						echo "</td></tr>";
					}
					
				}
			}
			echo "</table></td><td valign='top'>"; // Lopetettiin tämä sarake
			 
			
			echo "<table>"; // Tässä tehdään uusi sarake oikealle
			$alatila=$trow['alatila'];
			$laskutyyppi=$trow['tila'];
			require "inc/laskutyyppi.inc";
			echo "<tr><th>".t("Tila").":</th><td>".t("$laskutyyppi")." ".t("$alatila")."</td></tr>";
			// Myynnille 
			if (($trow['tila'] == 'U') or ($trow['tila'] == 'L')) {
				for ($i = 21; $i < 22; $i++) {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
								<td>$trow[$i]</td></tr>";
				}		
			}
			else {
				//Ulkomaan ostolaskuille
				if (strtoupper($trow[9]) != 'FI') {
					for ($i = 21; $i < 29; $i++) {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
								<td>$trow[$i]</td></tr>";
					}
				}
				else {
					//Kotimaan ostolaskuille
					for ($i = 19; $i < 22; $i++) {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th>
								<td>$trow[$i]</td></tr>";
					}
				}
				if ($trow['maksajanpankkitili'] != '') 
					echo "<tr><th>Oma pankkitili</th><td>$trow[maksajanpankkitili]</td></tr>";
			}
			// en jaksa miettiä indeksilukuja perkele!					
			echo "<tr><th>comments</th><td>$trow[comments]</td></tr>";
			if (strlen($trow['ebid']) > 0) {
				$ebid = $trow['ebid'];
				require "inc/ebid.inc";
				echo "<td></td><td><a href='$url'>".t("Näytä lasku")."</a></td></tr>";
			}
		}
		else {
// Muu tosite
			echo "<tr><td>".t("Muu tosite")."</td>";
			if (strlen($trow['ebid']) > 0) {
				$ebid = $trow['ebid'];
				require "inc/ebid.inc";
				echo "<td><a href='$url'>".t("Näytä tosite/liite")."</a></td></tr>";
			}
			else {
				echo "<td></td></tr>";
			}

		}
		echo "<tr>";
		if ($oikeurow['paivitys'] == 1) { // Näytetään nappi vain jos siihen on oikeus
			echo "<form action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name = 'tee' value='M'>
					<input type = 'hidden' name = 'tila' value=''>
					<input type = 'hidden' name = 'tunnus' value='$tunnus'>
					<td><input type = 'submit' value = '".t("Muuta tietoja")."'></td>
					</form>";
		}
		else {
			echo "<td></td>";
		}

		if (($trow['vienti'] != '')  and ($trow['vienti'] != 'A')  and ($trow['vienti'] != 'D')  and ($trow['vienti'] != 'G')) { // Näytetään nappi vain jos tieoja on
			if ($tee2 != 1) {
				echo "<form action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name = 'tee' value='$tee'>
					<input type = 'hidden' name = 'tee2' value='1'>
					<input type = 'hidden' name = 'tunnus' value='$tunnus'>
					<td><input type = 'submit' value = '".t("Lisää tietoja")."'></td></form>";
			}
			else {
				echo "<form action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name = 'tee' value='$tee'>
					<input type = 'hidden' name = 'tunnus' value='$tunnus'>
					<td><input type = 'submit' value = '".t("Normaalit tiedot")."'></td></form>";
			}
			echo "</tr>";
		}
		else {
			echo "<td></td></tr>";
		}
		//
		echo "</table></td></tr>"; //Lopetettiin viimeinen sarake
		echo "</table>";



		if ($tee == 'F') {
// Laskun tilausrivit
			require "inc/tilausrivit.inc";
			$tee = '';
		}
		else {
// Tositteen tiliöintirivit...
			require "inc/tiliointirivit.inc";
			$tee = "";
		}
	}
	if (strlen($tee) == 0) {
		if (strlen($formi) == 0) {
			$formi = 'valikko';
			$kentta = 'tap';
		}
		echo "<form name = 'valikko' action = '$PHP_SELF' method='post'><table>";
		echo "<tr>
			  <td>".t("Etsi tositetta")."</td>
			  <td>".t("tapahtumapvm")."</td>
			  <td>
			  	<input type='hidden' name='tee' value='Y'>
				<input type='text' name='tap' maxlength='2' size=2>
				<input type='text' name='tak' maxlength='2' size=2>
				<input type='text' name='tav' maxlength='4' size=4></td>
			  <td></td>
			  </tr>
			  <tr>
			  <td></td>
			  <td>summa</td>
			  <td><input type='text' name='summa' size=10></td>
			  <td></td>
			  </tr>
			  <tr>
			  <td></td>
			  <td>tili</td>
			  <td><input type='text' name='tilino' size=10></td>
			  <td></td>
			  </tr>
			  <tr>
			  <td></td>
			  <td>".t("osa selitteestä")."</td>
			  <td><input type='text' name='selite' maxlength='15' size=10></td>
			  <td><input type='checkbox' name='ohita' maxlength='15' size=10>".t("Ohita nämä")."</td>
			  </tr>
			  <tr>
			  <td></td>
			  <td>laatija</td>
			  <td><input type='text' name='laatija' size=10></td>
			  <td></td>
			  </tr>";
		if (($kpexport==1) or (strtoupper($yhtiorow['maa']) != 'FI')) { //$kpexport tulee salanasat.php:stä
			echo "
			  <tr>
			  <td></td>
			  <td>".t("tositenumero")."</td>
			  <td><input type='text' name='tositenro' size=10></td>
			  <td></td>
			  </tr>";
		}
		echo "
			  <tr>
			  <td></td>
			  <td>".t("näytä muutetut rivit")."</td>
			  <td><input type='checkbox' name='viivatut'></td>
			  <td><input type = 'submit' value = '".t("Etsi")."'></td></tr></form>
			  <tr><form action = '$PHP_SELF?tee=Z' method='post'>
			  <td>".t("Etsi virhettä")."</td>
			  <td>".t("näytä tositteet, jotka eivät stemmaa")."</td>
			  <td></td>
			  <td><input type = 'submit' value = '".t("Näytä")."'></td></form>
			  </tr><tr>
			  <td><form action = '$PHP_SELF?tee=X' method='post'></td>
			  <td>".t("näytä tositteet, joilta puuttuu kustannuspaikka")."</td>
			  <td></td>
			  <td><input type = 'submit' value = '".t("Näytä")."'></td></form>
			  </tr><tr>
			  <td><form action = '$PHP_SELF?tee=W' method='post'></td>
			  <td>".t("näytä tositteet, joiden ostovelat ei stemmaa")."</td>
			  <td></td>
			  <td><input type = 'submit' value = '".t("Näytä")."'></td></form>
			  </tr><tr>
			  <td><form action = '$PHP_SELF?tee=T' method='post'></td>
			  <td>".t("näytä tositteet, joiden tila tuntuu väärältä")."</td>
			  <td></td><form action = '$PHP_SELF?tee=M' method='post'>
			  <td><input type = 'submit' value = '".t("Näytä")."'></td></tr></form></table>";
	}

	require "inc/footer.inc";
?>
