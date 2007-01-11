<?php

	// tarvitaan arrayt jossa indexinumero on tilausrivin tunnus
	// $kpl[tunnus]   = monta toimitetaan
	// $loput[tunnus] = KAIKKI/POISTA/JATA/MITA/SUORA mitä tehdään lopuille
	// sekä $tunnus jossa on tilausrivin tunnus
	
	if ($loput[$tunnus] == 'MITA') {
		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tee_jt_tilaus.inc (1)")."<br>";
		$query = "	UPDATE tilausrivi SET tyyppi='D', kommentti = '$komm'
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus' and varattu=0 and kpl=0 and var='J' and tyyppi in ('L','G')";
		$tresult = mysql_query($query) or pupe_error($query);
	}
	else {
		///* Tämä on alkuperäinen jtrivi *///
		$query = "	SELECT tilausrivi.*, tuote.ei_saldoa
					FROM tilausrivi, tuote
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tunnus='$tunnus'
					and tilausrivi.varattu=0
					and tilausrivi.jt<>0
					and tilausrivi.kpl=0
					and tilausrivi.var='J'
					and tilausrivi.tyyppi in ('L','G')
					and tilausrivi.yhtio=tuote.yhtio
					and tilausrivi.tuoteno=tuote.tuoteno";
		$tresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tresult) == 0) {
			echo "Tilausriviä '$tunnus' ei löytynyt<br><br>$query";
			exit;
		}

		$trow = mysql_fetch_array ($tresult);

		///* Etsitään alkuperäisen-rivin laskun kaikki tiedot *///
		if ($tilaus_on_jo == "KYLLA") {
			//Tulaan myntitilauksen jtrivijutusta
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio	= '$kukarow[yhtio]' and
						tunnus		= '$kukarow[kesken]'";
			$stresult = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "SELECT * FROM lasku WHERE yhtio='$kukarow[yhtio]' and tunnus='$trow[otunnus]'";
			$stresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($stresult) == 0) {
				echo "Otsikkoa '$trow[otunnus]' ei löytynyt";
				exit;
			}
			$otsikkorivi=mysql_fetch_array ($stresult);

			if ($debug==1) echo t("Perusotsikko löytyi")." $otsikkorivi[nimi]<br>";

			if ($otsikkorivi['tila']== 'G') {
				$tila = 'G';
				$alatila = 'P';
			}
			else {
				$tila = 'N';
				$alatila = 'J';
			}
			// Tarvitsemmeko uuden otsikon?
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]' and
						laatija			= '$kukarow[kuka]' and
						alatila			= '$alatila' and
						tila 			= '$tila' and
						myyja			= '$otsikkorivi[myyja]' and
						ytunnus			= '$otsikkorivi[ytunnus]' and
						nimi 			= '$otsikkorivi[nimi]' and
						nimitark 		= '$otsikkorivi[nimitark]' and
						osoite 			= '$otsikkorivi[osoite]' and
						postino			= '$otsikkorivi[postino]' and
						postitp 		= '$otsikkorivi[postitp]' and
						toim_nimi		= '$otsikkorivi[toim_nimi]' and
						toim_nimitark	= '$otsikkorivi[toim_nimitark]' and
						toim_osoite 	= '$otsikkorivi[toim_osoite]' and
						toim_postino 	= '$otsikkorivi[toim_postino]' and
						toim_postitp 	= '$otsikkorivi[toim_postitp]' and
						toimitustapa 	= '$otsikkorivi[toimitustapa]' and
						maksuehto 		= '$otsikkorivi[maksuehto]' and
						vienti	 		= '$otsikkorivi[vienti]' and
						alv		 		= '$otsikkorivi[alv]' and
						ketjutus 		= '$otsikkorivi[ketjutus]' and
						kohdistettu		= '$otsikkorivi[kohdistettu]' and
						toimitusehto	= '$otsikkorivi[toimitusehto]' and 
						valkoodi 		= '$otsikkorivi[valkoodi]' and
						vienti_kurssi	= '$otsikkorivi[vienti_kurssi]'";
			$stresult = mysql_query($query) or pupe_error($query);
		}

		if (mysql_num_rows($stresult) != 0) {
			$otsikkorivi = mysql_fetch_array ($stresult);
			$id = $otsikkorivi['tunnus'];
			if ($debug==1) echo t("Lisään laskun olemassaolevaan")." $otsikkorivi[nimi] $id<br>";
		}
		else {

			// tehdään vanhasta laskusta 1:1 kopio...
			$query = "insert into lasku set ";
			for ($i=0; $i<mysql_num_fields($stresult); $i++) {

				// paitsi tilaan laitetaan N
				if (mysql_field_name($stresult,$i)=='tila') {
					if ($otsikkorivi['tila']== 'G') {
						$query .= "tila='G',";
					}
					else {
						$query .= "tila='N',";
					}
				}
				// alatilaan laitetaan J
				elseif (mysql_field_name($stresult,$i)=='alatila') {
					if ($otsikkorivi['tila']== 'G') {
						$query .= "alatila='P',";
					}
					else {
						$query .= "alatila='J',";
					}
				}
				// laatijaksi klikkaaja
				elseif (mysql_field_name($stresult,$i)=='laatija') {
					$query .= "laatija='$kukarow[kuka]',";
				}
				// keräysaika, luontiaika ja toimitusaikaan now
				elseif (mysql_field_name($stresult,$i)=='kerayspvm' or
						mysql_field_name($stresult,$i)=='luontiaika' or
						mysql_field_name($stresult,$i)=='toimaika') {
					$query .= mysql_field_name($stresult,$i)."=now(),";
				}
				// nämä kentät tyhjennetään
				elseif (mysql_field_name($stresult,$i)=='kapvm' or
						mysql_field_name($stresult,$i)=='tapvm' or
						mysql_field_name($stresult,$i)=='olmapvm' or
						mysql_field_name($stresult,$i)=='summa' or
						mysql_field_name($stresult,$i)=='kasumma' or
						mysql_field_name($stresult,$i)=='hinta' or
						mysql_field_name($stresult,$i)=='kate' or
						mysql_field_name($stresult,$i)=='arvo' or
						mysql_field_name($stresult,$i)=='maksuaika' or
						mysql_field_name($stresult,$i)=='lahetepvm' or
						mysql_field_name($stresult,$i)=='viite' or
						mysql_field_name($stresult,$i)=='laskunro' or
						mysql_field_name($stresult,$i)=='mapvm' or
						mysql_field_name($stresult,$i)=='tilausvahvistus' or
						mysql_field_name($stresult,$i)=='viikorkoeur' or
						mysql_field_name($stresult,$i)=='tullausnumero' or
						mysql_field_name($stresult,$i)=='laskutuspvm' or
						mysql_field_name($stresult,$i)=='erpcm' or
						mysql_field_name($stresult,$i)=='laskuttaja' or
						mysql_field_name($stresult,$i)=='laskutettu' or
						mysql_field_name($stresult,$i)=='lahetepvm' or
						mysql_field_name($stresult,$i)=='maksaja' or
						mysql_field_name($stresult,$i)=='maksettu' or
						mysql_field_name($stresult,$i)=='maa_maara' or
						mysql_field_name($stresult,$i)=='kuljetusmuoto' or
						mysql_field_name($stresult,$i)=='kauppatapahtuman_luonne' or
						mysql_field_name($stresult,$i)=='sisamaan_kuljetus' or
						mysql_field_name($stresult,$i)=='sisamaan_kuljetusmuoto' or
						mysql_field_name($stresult,$i)=='poistumistoimipaikka' or
						mysql_field_name($stresult,$i)=='vanhatunnus' or
						mysql_field_name($stresult,$i)=='poistumistoimipaikka_koodi') {
					$query .= mysql_field_name($stresult,$i)."='',";
				}
				// kommenttii speciaalia
				elseif (mysql_field_name($stresult,$i)=='comments') {
					$query .= "comments='$otsikkorivi[comments] ".t("JÄLKITOIMITUS")."',";
				}
				//HUOM HUOM: Laitetaan clearing kenttään JT-TILAUS kun tiedetään että nämä ovat jt-rivejä jotka nyt toimitetaan
				// tämä mähdollistaa raportoinninjt-riveistä!
				elseif (mysql_field_name($stresult,$i)=='clearing' and $otsikkorivi['tila'] != 'G') {
					$query .= "clearing='JT-TILAUS',";
				}
				// ja kaikki muut paitsi tunnus sellaisenaan
				elseif (mysql_field_name($stresult,$i)!='tunnus') {
					$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
				}
			}
			$query = substr($query,0,-1);

			$stresult = mysql_query($query) or pupe_error($query);
			$id = mysql_insert_id();
			if ($debug==1) echo t("Perustin laskun")." $otsikkorivi[nimi] $id<br>";
		}

		///* Tässä meillä on otsikko hallussa se on $id muuttujassa*///

		// Jos yhtion rahtihinnoittelu tapahtuu alkuperäisen tilausarvon mukaan, niin JT-tilauksiin ei enää laiteta rahtikulua uudestaan
		// Eli lisätään rahtikulurivi-nollahintaisena
		if ($yhtiorow["rahti_hinnoittelu"] != "") {

			// Otetaan alkuperäinen talteen koska se ylikirjoitetaan
			$rivitrow = $trow;

			$query = "	SELECT *
						FROM lasku
						WHERE tunnus = '$id'";
			$aresult = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($aresult);

			$query = "	select tunnus
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			//jos tilauksella on jo rahtikulu niin ei lisätä sitä uudestaan
			if (mysql_num_rows($result) == 0) {

				//lisätään rahtikulurivi
				$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
				$rhire = mysql_query($query) or pupe_error($query);
				$trow  = mysql_fetch_array($rhire);

				$hinta		= 0; // rahtihinta
				$alv 		= '';
				$trow["alv"]= $laskurow["alv"];
				$nimitys	= $laskurow["toimitustapa"];

				require("alv.inc");

				$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('$hinta', 'N', '1', '1', '$laskurow[tunnus]', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
				$addtil = mysql_query($query) or pupe_error($query);

				// JA palautetaan se
				$trow = $rivitrow;
			}
		}
		
		//tässä haarassa käyttäjä on syöttänyt jonkun kappalemäärän
		if ($kpl[$tunnus] > 0 or $loput[$tunnus] == 'KAIKKI') {

			if ($loput[$tunnus] == 'KAIKKI') {
				$kpl[$tunnus] = $trow["jt"];
				$loput[$tunnus] = 'POISTA';
			}

			// haetaan tuotteelle saldo
			$akeraysaika   = date("Y-m-d");
			$tuoteno       = $trow["tuoteno"];
			$atil          = $kpl[$tunnus];
			$kappaleet     = array();
			$updatehyllyt  = array();
			$taaltaloytyy  = array();
			$voiollajotain = array();

			if ($trow["ei_saldoa"] == '' and $ohitasaldotarkistus != 'ON' and $suoratoim[$tunnus] != 'SUORA') {
				// otetaan tilausrivirow talteen
				$rivitrow = $trow;
				// laitetaan tuoteno kenttään tuoteno
				$tuoteno = $trow["tuoteno"];
				unset($trow);

				require("tarkistasaldo.inc");

				// ja palautetaan se
				$trow = $rivitrow;

				$total = 0;

				for ($i=1; $i <= count($hyllyalue); $i++) {
					$query = "	SELECT *
								FROM varastopaikat
								WHERE yhtio			=  '$kukarow[yhtio]'
								and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$hyllyalue[$i]') ,5,'0'),lpad(upper('$hyllynro[$i]') ,5,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$hyllyalue[$i]') ,5,'0'),lpad(upper('$hyllynro[$i]') ,5,'0'))";
					$vares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($vares)!=0) {
						$varow = mysql_fetch_array($vares);

						if ($tilaus_on_jo == "") {
							foreach ($varastosta as $vara) {
								if ($varow["tunnus"] == $vara) {

									if ($saldo[$i] >= 0) {
										$taaltaloytyy[] = $i;
									}
									else {
										$voiollajotain[] = $i;
									}

									$total += $varastossa[$i];
								}
							}
						}
						else {
							if ($varow["tyyppi"] == "") {
								if ($saldo[$i] >= 0) {
									$taaltaloytyy[] = $i;
								}
								else {
									$voiollajotain[] = $i;
								}

								$total += $varastossa[$i];
							}
						}
					}
				}

				$voidaankolisata = 0;

				//jollain paikalla on riittävästi saldoa koko syötetylle määrälle
				if (count($taaltaloytyy) > 0) {
					$kappaleet[]	= $kpl[$tunnus];
					$updatehyllyt[] = "	hyllyalue = '".$hyllyalue[$taaltaloytyy[0]]."',
										hyllynro  =  '".$hyllynro[$taaltaloytyy[0]]."',
										hyllyvali = '".$hyllyvali[$taaltaloytyy[0]]."',
										hyllytaso = '".$hyllytaso[$taaltaloytyy[0]]."', ";

					$voidaankolisata = 1;
				}
				//monen paikan yhteinen saldo riittää syötetylle määrälle
				elseif ($total >= $kpl[$tunnus]) {
					// näin paljon käyttäjä on syöttänyt $kpl[$tunnus]
					$jaljella = $kpl[$tunnus];

					foreach($voiollajotain as $i) {

						$updatehyllyt[] = "	hyllyalue = '$hyllyalue[$i]',
											hyllynro  =  '$hyllynro[$i]',
											hyllyvali = '$hyllyvali[$i]',
											hyllytaso = '$hyllytaso[$i]', ";

						if ($jaljella > $varastossa[$i] and $varastossa[$i] > 0) {
							$kappaleet[]	= $varastossa[$i];
						}
						if ($jaljella <= $varastossa[$i] and $varastossa[$i] > 0) {
							$kappaleet[]	= $jaljella;
							break;
						}

						$jaljella -= $varastossa[$i];
					}

					$voidaankolisata = 1;
				}
			}
			else {
				//saldottomat tuotteet
				$voidaankolisata = 1;
				$kappaleet[] = $atil;
			}


			if ($voidaankolisata == 1) {
				
				//katotaan, ettei yritetä toimittaa enemmän kuin rivillä oli tilauksessa
				$varattu = $trow["jt"]-$kpl[$tunnus];

				if ($varattu >= 0 or $ohitasaldotarkistus == "ON") {
					///* jos kappalemäärä on syötetty niin splitataan rivi *///

					for ($i=0; $i < count($kappaleet); $i++) {
						
						$kommentti = trim($otsikkorivi['viesti']." ".$trow['kommentti']);
						
						//tässä tehdään uusi rivi
						$query = "	INSERT into tilausrivi set ";
						
						if ($suoratoim[$tunnus] == 'SUORA') {
							$query .= "	hyllyalue 		= '$hyllyalue[$tunnus]',
										hyllynro  		= '$hyllynro[$tunnus]',
										hyllyvali 		= '$hyllyvali[$tunnus]',
										hyllytaso 		= '$hyllytaso[$tunnus]',
										tilaajanrivinro = '$rivinumero[$tunnus]',
										varattu	 		= 0,
										tilkpl 	 		= '$kappaleet[$i]',
										jt		 		= '$kappaleet[$i]',
										var				= '$var[$tunnus]',";
						}
						else {
							$query .= " $updatehyllyt[$i] 
										varattu = '$kappaleet[$i]',
										tilkpl 	= '$kappaleet[$i]',
										jt		= 0,
										var		= '',";
						}
						
						
						$query .= "	otunnus 		= '$id',
									keratty			= '',
									kerattyaika		= '',
									kerayspvm 		= now(),
									laatija			= '$kukarow[kuka]',
									perheid			= '$trow[perheid]',
									laadittu		= now(),
									toimitettu		= '',
									toimitettuaika	= '',
									toimaika 		= now(),
									laskutettu		= '',
									laskutettuaika	= '',
									yhtio 			= '$trow[yhtio]',
									tuoteno 		= '$trow[tuoteno]',
									ale 			= '$trow[ale]',
									netto 			= '$trow[netto]',
									yksikko 		= '$trow[yksikko]',
									try 			= '$trow[try]',
									osasto 			= '$trow[osasto]',
									kpl 			= 0,
									alv 			= '$trow[alv]',
									hinta 			= '$trow[hinta]',
									nimitys 		= '$trow[nimitys]',
									tyyppi 			= '$trow[tyyppi]',
									kommentti 		= '$kommentti'";
						$result = mysql_query($query) or pupe_error($query);

						//echo "$query Perustetaan uusi!<br><br>";
					}

					//ja lopuille tehdään näin
					if (($loput[$tunnus] == '' or $loput[$tunnus] == 'JATA') and $varattu > 0) {
						$query = "	UPDATE tilausrivi
									SET jt = '$varattu'
									WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus' and kpl=0 and tyyppi in('L','G')";
						$tresult = mysql_query($query) or pupe_error($query);

					}
					elseif ($loput[$tunnus] == 'POISTA' or $varattu == 0) {
						$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tee_jt_tilaus.inc (2)")."<br>";
						$query = "	UPDATE tilausrivi SET tyyppi='D', kommentti = '$komm'
									WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus' and tyyppi in ('L','G')";
						$tresult = mysql_query($query) or pupe_error($query);
					}
					
					//jos ollaan päästy näin pitkälle voidaan varmaan olettaa että $varaosavirheen voi yhjentää
					$varaosavirhe = '';
					
				}
				else {
					echo t("Syötit liian ison numeron! Ei voitu tehdä mitään").".<br>";
				}
			}
			else {
				echo t("Olit liian ahne! Saldo loppui kesken ja siksi riville ei voitu tehdä mitään")."!<br>";
			}
		}
	}
?>
