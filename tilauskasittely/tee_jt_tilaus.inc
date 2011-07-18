<?php

	// $tunnukset jossa on k‰sitelt‰vien tilausrivien tunnukset (tuoteperhe)
	// $tunnusarray array jossa on k‰sitelt‰vien tilausrivien tunnukset arrayssa
	// $kpl[$tunnukset]   = monta toimitetaan
	// $loput[$tunnukset] = KAIKKI/POISTA/JATA/MITA/SUORA mit‰ tehd‰‰n lopuille
	// $suoratoimpaikka[$tunnukset] = paikat josta myyd‰‰n suoratoimituksia
	// $tilaus_on_jo = KYLLA niin lis‰t‰‰n rivit $kukarow[kesken] tilaukselle
	// $olpaikalta = pakotetaan myym‰‰n oletuspaikalta
	// $varataan_saldoa = varataanko saldoa
	// $alv = alviprossa
	// $varastosta = mist‰ varastosta myyd‰‰n

	if (!function_exists("tee_jt_tilaus")) {
		function tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka = '', $tilaus_on_jo = '', $varastosta = '', $olpaikalta = '', $varataan_saldoa = '', $alv = '') {
			global $yhtiorow, $kukarow, $toim, $automaattinen_poiminta;

			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			if ($loput[$tunnukset] == 'MITA') {
				if ($toim == "ENNAKKO") {
					$query = "	UPDATE tilausrivi SET
								tyyppi = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	in ($tunnukset)
								and kpl		= 0
								and tyyppi	= 'E'";
				}
				else {
					$query = "	UPDATE tilausrivi SET
								tyyppi = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	in ($tunnukset)
								and kpl		= 0
								and var		= 'J'
								and tyyppi in ('L','G')";
				}
				$tresult = mysql_query($query) or pupe_error($query);

				$query = "UPDATE sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus in ($tunnukset)";
				$sarjares = mysql_query($query) or pupe_error($query);

			}
			else {
				if ($toim == "ENNAKKO") {
					$query = "	SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen, lasku.vienti_kurssi
								FROM tilausrivi use index (PRIMARY)
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
								JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tunnus  = '$tunnusarray[0]'
								and tilausrivi.varattu > 0
								and tilausrivi.tyyppi  = 'E'";
				}
				else {
					$query = "	SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen, lasku.osatoimitus, lasku.vienti_kurssi
								FROM tilausrivi use index (PRIMARY)
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
								JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
								WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
								and tilausrivi.tunnus  			= '$tunnusarray[0]'
								and tilausrivi.jt $lisavarattu	> 0
								and tilausrivi.kpl     			= 0
								and tilausrivi.var     			= 'J'
								and tilausrivi.tyyppi 			in ('L','G')";
				}
				$rivitres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($rivitres) == 0) {
					echo "Tilausrivi‰ '$tunnusarray[0]' ei lˆytynyt<br><br>$query";
					exit;
				}

				///* T‰m‰ on alkuper‰isen rivinipun eka ja vanhin rivi, k‰ytet‰‰n sit‰ otsikon tekoa varten *///
				$isarivirow = mysql_fetch_array ($rivitres);

				///* Etsit‰‰n alkuper‰isen-rivin laskun kaikki tiedot *///
				if ($tilaus_on_jo == "KYLLA") {
					//Tullaan myntitilauksen jtrivijutusta
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio	= '$kukarow[yhtio]' and
								tunnus		= '$kukarow[kesken]' and
								tila        in ('N', 'L', 'E', 'G', 'C')";
					$stresult = mysql_query($query) or pupe_error($query);

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$kukarow[kesken]'";
					$lisatiedot_result = mysql_query($query) or pupe_error($query);
				}
				elseif ($isarivirow["osatoimitus"] != "") {
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus	= '$isarivirow[otunnus]'
								and tila	in ('N', 'L')
								and alatila != 'X'";
					$stresult = mysql_query($query) or pupe_error($query);

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$isarivirow[otunnus]'";
					$lisatiedot_result = mysql_query($query) or pupe_error($query);
				}
				else {
					// tila pit‰‰ olla l‰hete, tilaus, ennakko tai siirtolista
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$isarivirow[otunnus]' and tila in ('N', 'L', 'E', 'G', 'C')";
					$stresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($stresult) == 0) {
						echo "Otsikkoa '$isarivirow[otunnus]' ei lˆytynyt";
						exit;
					}
					$otsikkorivi = mysql_fetch_array ($stresult);

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$isarivirow[otunnus]'";
					$lisatiedot_result = mysql_query($query) or pupe_error($query);
					$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

					if ($debug==1) echo t("Perusotsikko lˆytyi")." $otsikkorivi[nimi] $isarivirow[otunnus]<br>";

					$erpcmlisa1 = "";
					$erpcmlisa2 = "";

					if ($toim == "ENNAKKO") {
						// Tutkitaan perusotsikon er‰p‰iv‰m‰‰r‰‰
						$erpquery = "SELECT * from maksuehto where yhtio = '$kukarow[yhtio]' and tunnus='$otsikkorivi[maksuehto]'";
						$erpres = mysql_query($erpquery) or pupe_error($erpquery);
						$meapurow = mysql_fetch_array($erpres);

						list($erpcmvv, $erpcmkk, $erpcmpp) = explode('-', $otsikkorivi["erpcm"]);

						$nyt = (int) date("Ymd");
						$erp = (int) $erpcmvv.$erpcmkk.$erpcmpp;

						//Jos alkuper‰isell‰ tilauksella on er‰p‰iv‰m‰‰r‰ syˆtetty k‰sin niin huomioidaan se nyt rivien poiminnassa
						if ($meapurow["erapvmkasin"] != "" and checkdate($erpcmkk, $erpcmpp, $erpcmvv) and $erp > $nyt) {
							$erpcmlisa1 = " and lasku.erpcm = '$otsikkorivi[erpcm]' ";
							$erpcmlisa2 = " erpcm = '$otsikkorivi[erpcm]', ";
						}

						$tila		= 'N';
						$alatila	= 'E';
					}
					elseif ($otsikkorivi['tila']== 'G') {
						$tila 		= 'G';
						$alatila 	= 'P';
					}
					else {
						$tila		= 'N';
						$alatila	= 'J';
					}

					// Vain saman alkuper‰isen tilauksen rivej‰ samalle otsikolle
					if ($otsikkorivi['jtkielto'] == "Y") {
						// v‰‰rink‰ytet‰‰n hyvaksyja_nyt kentt‰‰...
						$yhdlisa = " and lasku.hyvaksyja_nyt = '$otsikkorivi[tunnus]' ";
					}
					else {
						$yhdlisa = "";
					}

					// Tarvitsemmeko uuden otsikon?
					$query = "	SELECT lasku.*
								FROM lasku
								JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
								WHERE lasku.yhtio		= '$kukarow[yhtio]'
								and lasku.laatija		= '$kukarow[kuka]'
								and lasku.alatila		= '$alatila'
								and lasku.tila 			= '$tila'
								and lasku.myyja			= '$otsikkorivi[myyja]'
								and lasku.ytunnus		= '$otsikkorivi[ytunnus]'
								and lasku.nimi 			= '$otsikkorivi[nimi]'
								and lasku.nimitark 		= '$otsikkorivi[nimitark]'
								and lasku.osoite 		= '$otsikkorivi[osoite]'
								and lasku.postino		= '$otsikkorivi[postino]'
								and lasku.postitp 		= '$otsikkorivi[postitp]'
								and lasku.toim_nimi		= '$otsikkorivi[toim_nimi]'
								and lasku.toim_nimitark	= '$otsikkorivi[toim_nimitark]'
								and lasku.toim_osoite 	= '$otsikkorivi[toim_osoite]'
								and lasku.toim_postino 	= '$otsikkorivi[toim_postino]'
								and lasku.toim_postitp 	= '$otsikkorivi[toim_postitp]'
								and lasku.toimitustapa 	= '$otsikkorivi[toimitustapa]'
								and lasku.maksuehto 	= '$otsikkorivi[maksuehto]'
								and lasku.vienti	 	= '$otsikkorivi[vienti]'
								and lasku.alv		 	= '$otsikkorivi[alv]'
								and lasku.ketjutus 		= '$otsikkorivi[ketjutus]'
								and lasku.kohdistettu	= '$otsikkorivi[kohdistettu]'
								and lasku.toimitusehto	= '$otsikkorivi[toimitusehto]'
								and lasku.valkoodi 		= '$otsikkorivi[valkoodi]'
								and lasku.vienti_kurssi	= '$otsikkorivi[vienti_kurssi]'
								and lasku.erikoisale	= '$otsikkorivi[erikoisale]'
								and lasku.eilahetetta	= '$isarivirow[suoraan_laskutukseen]'
								and lasku.tunnusnippu	= '$otsikkorivi[tunnusnippu]'
								and lasku.piiri			= '$otsikkorivi[piiri]'
								and laskun_lisatiedot.laskutus_nimi 	= '$lisatiedot_row[laskutus_nimi]'
								and laskun_lisatiedot.laskutus_nimitark = '$lisatiedot_row[laskutus_nimitark]'
								and laskun_lisatiedot.laskutus_osoite 	= '$lisatiedot_row[laskutus_osoite]'
								and laskun_lisatiedot.laskutus_postino 	= '$lisatiedot_row[laskutus_postino]'
								and laskun_lisatiedot.laskutus_postitp 	= '$lisatiedot_row[laskutus_postitp]'
								and laskun_lisatiedot.laskutus_maa 		= '$lisatiedot_row[laskutus_maa]'
								$erpcmlisa1
								$yhdlisa";
					$stresult = mysql_query($query) or pupe_error($query);
				}

				if (mysql_num_rows($stresult) != 0) {
					$otsikkorivi = mysql_fetch_array ($stresult);
					$id = $otsikkorivi['tunnus'];
					if ($debug == 1) echo t("Lis‰‰n laskun olemassaolevaan")." $otsikkorivi[nimi] $id<br>";
				}
				else {
					// tehd‰‰n vanhasta laskusta 1:1 kopio...
					$query = "INSERT into lasku set ";

					for ($i=0; $i<mysql_num_fields($stresult); $i++) {

						// Tilaan laitetaan jotain speciaalia
						if (mysql_field_name($stresult,$i)=='tila') {
							if ($toim == "ENNAKKO") {
								$query .= "tila='N',";
							}
							elseif ($otsikkorivi['tila']== 'G') {
								$query .= "tila='G',";
							}
							else {
								$query .= "tila='N',";
							}
						}
						// alatilaan laitetaan jotain speciaalia
						elseif (mysql_field_name($stresult,$i)=='alatila') {
							if ($toim == "ENNAKKO") {
								$query .= "alatila='E',";
							}
							elseif ($otsikkorivi['tila']== 'G') {
								$query .= "alatila='P',";
							}
							else {
								$query .= "alatila='J',";
							}
						}
						// tilaustyyppi
						elseif (mysql_field_name($stresult,$i)=='tilaustyyppi') {
							if ($toim == "ENNAKKO") {
								$query .= "tilaustyyppi='N',";
							}
							elseif ($otsikkorivi['tila']== 'G') {
								$query .= "tilaustyyppi='".$otsikkorivi[$i]."',";
							}
							else {
								$query .= "tilaustyyppi='".$otsikkorivi[$i]."',";
							}
						}
						// laatijaksi klikkaaja
						elseif (mysql_field_name($stresult,$i)=='laatija') {
							$query .= "laatija='$kukarow[kuka]',";
						}
						// ker‰ysaika ja toimitusaikaan now
						elseif (mysql_field_name($stresult,$i)=='kerayspvm' or
								mysql_field_name($stresult,$i)=='toimaika') {
							
							
							## Miksi?
							$query .= mysql_field_name($stresult,$i)."=now(),";
						
						}
						// Katotaan oisko k‰sinsyˆtetty er‰p‰iv‰m‰‰r‰
						elseif (mysql_field_name($stresult,$i)=='erpcm') {
							if ($erpcmlisa2 != '') {
								$query .= $erpcmlisa2;
							}
							else {
								$query .= "erpcm='',";
							}
						}
						// n‰m‰ kent‰t tyhjennet‰‰n aina
						elseif (mysql_field_name($stresult,$i)=='kapvm' or
								mysql_field_name($stresult,$i)=='tapvm' or
								mysql_field_name($stresult,$i)=='erpcm' or
								mysql_field_name($stresult,$i)=='olmapvm' or
								mysql_field_name($stresult,$i)=='summa' or
								mysql_field_name($stresult,$i)=='kasumma' or
								mysql_field_name($stresult,$i)=='hinta' or
								mysql_field_name($stresult,$i)=='kate' or
								mysql_field_name($stresult,$i)=='arvo' or
								mysql_field_name($stresult,$i)=='maksuaika' or
								mysql_field_name($stresult,$i)=='lahetepvm' or
								mysql_field_name($stresult,$i)=='h1time' or
								mysql_field_name($stresult,$i)=='viite' or
								mysql_field_name($stresult,$i)=='laskunro' or
								mysql_field_name($stresult,$i)=='mapvm' or
								mysql_field_name($stresult,$i)=='kerayslista' or
								mysql_field_name($stresult,$i)=='viikorkoeur' or
								mysql_field_name($stresult,$i)=='tullausnumero' or
								mysql_field_name($stresult,$i)=='laskutuspvm' or
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
						elseif (mysql_field_name($stresult,$i) == 'tilausvahvistus') {

							$tv_query = "	SELECT tilausvahvistus, tilausvahvistus_jttoimituksista
											FROM asiakas
											where yhtio = '$yhtiorow[yhtio]'
											and tunnus = '$otsikkorivi[liitostunnus]'";
							$tv_res = mysql_query($tv_query) or pupe_error($tv_query);
							$tv_row = mysql_fetch_array($tv_res);

							if ($tv_row["tilausvahvistus_jttoimituksista"] == "K" or ($yhtiorow["tilausvahvistus_jttoimituksista"] == "K" and $tv_row["tilausvahvistus_jttoimituksista"] != "E")) {
								if (trim($otsikkorivi["tilausvahvistus"]) != "") {
									$query .= mysql_field_name($stresult,$i)."='$otsikkorivi[tilausvahvistus]',";
								}
								else {
									$query .= mysql_field_name($stresult,$i)."='$tv_row[tilausvahvistus]',";
								}
							}
							else {
								$query .= mysql_field_name($stresult,$i)."='',";
							}
						}
						elseif (mysql_field_name($stresult,$i)=='hyvaksyja_nyt' and $otsikkorivi['jtkielto'] == "Y") {
							$query .= mysql_field_name($stresult,$i)."='$otsikkorivi[tunnus]',";
						}
						// n‰m‰ kent‰t tyhjennet‰‰n jos jtrivit kootaan yhdelle otsikolle (siis normikeissi)
						elseif (mysql_field_name($stresult,$i) == 'sisviesti1' or
								mysql_field_name($stresult,$i) == 'tilausyhteyshenkilo' or
								mysql_field_name($stresult,$i) == 'asiakkaan_tilausnumero' or
								mysql_field_name($stresult,$i) == 'kohde' or
								mysql_field_name($stresult,$i) == 'viesti') {

							if ($toim == "ENNAKKO" or $otsikkorivi['jtkielto'] == "Y") {
								$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
							}
							else {
								$query .= mysql_field_name($stresult,$i)."='',";
							}
						}
						// kommentti speciaalia
						elseif (mysql_field_name($stresult,$i) == 'comments') {
							if ($toim == "ENNAKKO" or $otsikkorivi['jtkielto'] == "Y") {
								if ($toim == "ENNAKKO") {
									$query .= mysql_field_name($stresult,$i)."=trim(concat('".t("ENNAKKOTILAUS")."\n', '".$otsikkorivi[$i]."')),";
								}
								else {
									$query .= mysql_field_name($stresult,$i)."=trim(concat('".t("JƒLKITOIMITUS")."\n', '".$otsikkorivi[$i]."')),";
								}
							}
							else {
								if ($toim == "ENNAKKO") {
									$query .= mysql_field_name($stresult,$i)."='".t("ENNAKKOTILAUS")."',";
								}
								else {
									$query .= mysql_field_name($stresult,$i)."='".t("JƒLKITOIMITUS")."',";
								}
							}
						}
						//HUOM: Laitetaan clearing kentt‰‰n jotain speciaalia
						elseif (mysql_field_name($stresult,$i)=='clearing') {
							if ($toim == "ENNAKKO") {
								$query .= "clearing='ENNAKKOTILAUS',";
							}
							elseif ($otsikkorivi['tila'] == 'G') {
								$query .= "clearing='".$otsikkorivi[$i]."',";
							}
							else {
								$query .= "clearing='JT-TILAUS',";
							}
						}
						elseif (mysql_field_name($stresult,$i) == 'eilahetetta') {
							$query .= "eilahetetta='".$isarivirow["suoraan_laskutukseen"]."',";
						}
						elseif (mysql_field_name($stresult,$i) == 'rahtivapaa') {
							if ($yhtiorow["jt_rahti"] == "B") {
								// Ennakot/J‰lkitoimitukset ovat aina rahtivapaita
								$query .= "rahtivapaa='o',";
							}
							elseif ($yhtiorow["jt_rahti"] == "C") {
								// Ennakoihin/J‰lkitoimituksiin lis‰t‰‰n aina rahtikulu
								$query .= "rahtivapaa='',";
							}
							elseif ($yhtiorow["jt_rahti"] == "D") {
								// Ennakot/J‰lkitoimitukset ovat rahtivapaita, jos tilauksen arvo on yli rahtivapausrajan (tilaus-valmis.inc hoitaa rahtikulun kuntoon)
								$query .= "rahtivapaa='',";
							}
							else {
								// Ennakko/J‰lkitoimitus on rahtivapaa, jos alkuper‰inen tilaus oli rahtivapaa
								$query .= "rahtivapaa='".$otsikkorivi[$i]."',";
							}
						}
						elseif (mysql_field_name($stresult,$i)=='maksuehto') {
							//katsotaan onko asiakkaan maksuehto muuttunut j‰lkivaatimukseksi
							$maksuehto_query = "SELECT maksuehto.jv, maksuehto.tunnus
												FROM asiakas
												join maksuehto ON asiakas.yhtio = maksuehto.yhtio and asiakas.maksuehto = maksuehto.tunnus
												where asiakas.yhtio = '$yhtiorow[yhtio]'
												and asiakas.tunnus = '$otsikkorivi[liitostunnus]'";
							$maksuehto_result = mysql_query($maksuehto_query) or pupe_error($maksuehto_query);
							$maksuehtorow = mysql_fetch_array($maksuehto_result);

							if ($maksuehtorow['jv'] != '') {
								$query .= "maksuehto='$maksuehtorow[tunnus]',";
							}
							else {
								$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
							}

						}
						// ja kaikki muut paitsi tunnus sellaisenaan
						elseif (mysql_field_name($stresult,$i)!='tunnus') {
							$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
						}
					}
					$query = substr($query,0,-1);
					$stresult = mysql_query($query) or pupe_error($query);
					$id = mysql_insert_id();

					$query = "INSERT INTO laskun_lisatiedot SET ";

					for ($i = 0; $i < mysql_num_fields($lisatiedot_result); $i++) {
						if (mysql_field_name($lisatiedot_result, $i) == 'laatija') {
							$query .= mysql_field_name($lisatiedot_result, $i)."='$kukarow[kuka]',";
						}
						elseif (mysql_field_name($lisatiedot_result, $i) == 'luontiaika') {
							$query .= mysql_field_name($lisatiedot_result, $i)."=now(),";
						}
						elseif (mysql_field_name($lisatiedot_result, $i) == 'otunnus') {
							$query .= mysql_field_name($lisatiedot_result, $i)."='$id',";
						}
						elseif (mysql_field_name($lisatiedot_result, $i) != 'tunnus') {
							$query .= mysql_field_name($lisatiedot_result, $i)."='".$lisatiedot_row[$i]."',";
						}
					}

					$query = substr($query, 0, -1);
					$lisatiedot_result = mysql_query($query) or pupe_error($query);

					if ($debug==1) echo t("Perustin laskun")." $otsikkorivi[nimi] $id<br>";
				}

				///* T‰ss‰ meill‰ on otsikko hallussa se on $id muuttujassa*///

				// T‰ss‰ haarassa k‰ytt‰j‰ on syˆtt‰nyt jonkun kappalem‰‰r‰n
				if ($kpl[$tunnukset] > 0 or $loput[$tunnukset] == 'KAIKKI' or $loput[$tunnukset] == 'VAKISIN') {

					$jt_vakisin = FALSE;

					if ($loput[$tunnukset] == 'KAIKKI' or $loput[$tunnukset] == 'VAKISIN') {
						if ($toim == "ENNAKKO") {
							$kpl[$tunnukset] = $isarivirow["varattu"];
						}
						else {
							$kpl[$tunnukset] = $isarivirow["jt"];
						}

						if ($loput[$tunnukset] == 'VAKISIN') {
							$jt_vakisin = TRUE;
						}

						$loput[$tunnukset] = 'POISTA';
					}

					if ($toim == "ENNAKKO") {
						$isa_kerroin	= $kpl[$tunnukset] / $isarivirow["varattu"];
						$isa_kpl		= $isarivirow["varattu"];
					}
					else {
						$isa_kerroin 	= $kpl[$tunnukset] / $isarivirow["jt"];
						$isa_kpl		= $isarivirow["jt"];
					}

					if ($suoratoimpaikka[$tunnukset] != '') {
						$paikka = $suoratoimpaikka;
					}
					else {
						$paikka = "";
					}

					if (($kpl[$tunnukset] <= $isa_kpl and $kpl[$tunnukset] > 0) or $jt_vakisin === TRUE) {

						$luodut_rivit = array();
						$luodut_kpl = array();

						foreach ($tunnusarray as $tunnus_nyt) {
							if ($toim == "ENNAKKO") {
								$query = "	SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.vanha_otunnus
											FROM tilausrivi
											LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
											JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
											WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
											and tilausrivi.tunnus = '$tunnus_nyt'
											and tilausrivi.varattu > 0
											and tilausrivi.tyyppi = 'E'";
							}
							else {
								$query = "	SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.vanha_otunnus
											FROM tilausrivi
											LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
											JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
											WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
											and tilausrivi.tunnus = '$tunnus_nyt'
											and tilausrivi.jt $lisavarattu > 0
											and tilausrivi.kpl = 0
											and tilausrivi.var = 'J'
											and tilausrivi.tyyppi in ('L','G')";
							}
							$rivitres = mysql_query($query) or pupe_error($query);
							$lisataanrow = mysql_fetch_array ($rivitres);

							if ($toim == "ENNAKKO") {
								$lisataanrow_kpl = round($lisataanrow["varattu"] * $isa_kerroin,2);
							}
							else {
								$lisataanrow_kpl = round($lisataanrow["jt"] * $isa_kerroin,2);
							}

							if ($debug == 1) echo t("Yritet‰‰n myyd‰ tuotetta").": $lisataanrow[tuoteno] $lisataanrow_kpl kappaletta.<br>";

							$query = "	SELECT *
										FROM tuote
										WHERE yhtio  = '$kukarow[yhtio]'
										and  tuoteno = '$lisataanrow[tuoteno]'";
							$aresult = mysql_query($query) or pupe_error($query);
							$trow = mysql_fetch_array($aresult);

							$query = "	SELECT *
										FROM lasku
										WHERE tunnus = '$id'";
							$aresult = mysql_query($query) or pupe_error($query);
							$laskurow = mysql_fetch_array($aresult);

							$talteen_kpl		= $kpl; // lis‰‰rivi rikkoo t‰n, otetaan talteen
							$kukarow["kesken"]	= $id;
							$tuoteno 		 	= $lisataanrow["tuoteno"];
							$netto 			 	= $lisataanrow["netto"];

							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								${'ale'.$alepostfix} = $lisataanrow["ale{$alepostfix}"];
							}

							$kpl				= $lisataanrow_kpl;
							$varasto 		 	= $varastosta;
							$korvaavakielto	 	= "Y";
							$perhekielto     	= "Y";
							$osatoimkielto		= "Y";

							if ($jt_vakisin === TRUE) {
								$var = "H";
							}
							else {
								$var = "";
							}

							$perheid = $lisataanrow["perheid"];
							$perheid2 = $lisataanrow["perheid2"];

							// Rivin kommentti
							$kommentti = "";

							if (trim($lisataanrow['kommentti']) != "") {
								// Alkuper‰isen rivin kommentti
								$kommentti .= trim($lisataanrow['kommentti']);
							}

							if ($lisataanrow["vanha_otunnus"] > 0) {
								$alkupotunnus = $lisataanrow["vanha_otunnus"];
							}
							else {
								$alkupotunnus = $lisataanrow["otunnus"];
							}

							$query = "	SELECT tunnus, asiakkaan_tilausnumero, viesti, tilausyhteyshenkilo
										FROM lasku
										WHERE tunnus = '$alkupotunnus'";
							$aresult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($aresult) > 0) {
								$orig_laskurow = mysql_fetch_array($aresult);

								// Joitain tietoja alkuper‰iselt‰ tilaukselta, vain jos osatoimitus ei ole estetty ja jtrivit yhdistet‰‰n samalle otsikolle ja kyseess‰ ei ole ennakkotilaus
								if ($otsikkorivi["osatoimitus"] == "" and $toim != "ENNAKKO" and $otsikkorivi['jtkielto'] != "Y") {

									if (strpos($kommentti, t("Alkuper‰inen tilaus").": ".trim($orig_laskurow['tunnus'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Alkuper‰inen tilaus").": ".trim($orig_laskurow['tunnus']);
									}

									if (trim($orig_laskurow['asiakkaan_tilausnumero']) != "" and strpos($kommentti, t("Asiakkaan tilaus").": ".trim($orig_laskurow['asiakkaan_tilausnumero'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Asiakkaan tilaus").": ".trim($orig_laskurow['asiakkaan_tilausnumero']);
									}

									if (trim($orig_laskurow['viesti']) != "" and strpos($kommentti, t("Tilausviite").": ".trim($orig_laskurow['viesti'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Tilausviite").": ".trim($orig_laskurow['viesti']);
									}

									if (trim($orig_laskurow['tilausyhteyshenkilo']) != "" and strpos($kommentti, t("Tilaaja").": ".trim($orig_laskurow['tilausyhteyshenkilo'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Tilaaja").": ".trim($orig_laskurow['tilausyhteyshenkilo']);
									}
								}
								elseif ($otsikkorivi["osatoimitus"] == "" and $otsikkorivi['jtkielto'] == "Y") {
									if (strpos($kommentti, t("Alkuper‰inen tilaus").": ".trim($orig_laskurow['tunnus'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Alkuper‰inen tilaus").": ".trim($orig_laskurow['tunnus']);
									}
								}
								elseif ($toim == "ENNAKKO") {
									if (strpos($kommentti, t("Ennakkotilaus").": ".trim($orig_laskurow['tunnus'])) === FALSE and $toim == "ENNAKKO") {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Ennakkotilaus").": ".trim($orig_laskurow['tunnus']);
									}
								}
							}

							if ($automaattinen_poiminta != "" and $toim != "ENNAKKO" and $tilaus_on_jo == "KYLLA" and strpos($kommentti, t("Toimitettavissa oleva JT-rivi")."!") === FALSE) {
								if (trim($kommentti) != "") $kommentti .= " / ";
								// Tilausrivin systeemikommentti
								$kommentti .= t("Toimitettavissa oleva JT-rivi")."!";
							}

							$jtspec = "JTSPEC";

							// Tutkitaan onko t‰m‰ myyty ulkomaan alvilla
							list(,,,$tsek_alehinta_alv,) = alehinta($laskurow, $trow, $kpl, '', '', '');

							if ($tsek_alehinta_alv > 0) {
								$trow["alv"] = $tsek_alehinta_alv;
							}

							// jos is‰rivi oli k‰‰nteinen alvi, niin laitetaan t‰m‰kin rivi k‰‰nteiseksi
							if ($isarivirow["alv"] >= 600) {
								$alv = $trow["alv"] + 600;
							}

							if ($yhtiorow["alv_kasittely"] == "" and $trow["alv"] != $lisataanrow["alv"] and $lisataanrow["alv"] < 500) {
								$hinta = sprintf('%.2f',round($lisataanrow["hinta"] / (1+$lisataanrow['alv']/100) * (1+$trow["alv"]/100), $yhtiorow['hintapyoristys']));
							}
							else {
								$hinta = $lisataanrow["hinta"];
							}

							// jos k‰ytˆss‰ on myyntihinnan poikkeava m‰‰r‰, kerrotaan hinta takaisin kuntoon.
							if ($trow["myyntihinta_maara"] != 0) {
								$hinta = $hinta * $trow["myyntihinta_maara"];
							}

							if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
								//K‰ytet‰‰n alkuper‰isen JT-rivin otsikon valuuttakurssia
								$hinta = laskuval($hinta, $isarivirow["vienti_kurssi"]);
							}

							require("lisaarivi.inc");

							$kpl = $talteen_kpl; // lis‰‰rivi rikko t‰n, otetaan haltuun

							if ($debug==1) echo t("Lis‰ttiin tuote").": $tuoteno kpl: $lisataanrow_kpl<br>";

							// Suoratoimitukset saliitaan jos ne ovat toivottuja
							if ($var == "S" and $suoratoimpaikka[$tunnukset] != '') {
								$lisatyt_rivit1 = $lisatyt_rivit2;
								unset($lisatyt_rivit2);
							}

							// Pidet‰‰n muistissa kaikki lis‰tyt rivit
							if (count($lisatyt_rivit1) > 0) {
								// Myyntihaara
								foreach ($lisatyt_rivit1 as $lisatty_tun) {
									$luodut_rivit[$lisatty_tun] = $lisatty_tun;

									//Pidet‰‰n muistissa lis‰tyt kappaleet
									$luodut_kpl[$tunnus_nyt] += $lisataanrow_kpl;
								}
							}

							if (count($lisatyt_rivit2) > 0) {
								// Puute - JT - Ostohaara
								foreach ($lisatyt_rivit2 as $lisatty_tun) {
									$luodut_rivit[] = $lisatty_tun;
								}
							}

							//Jos saldo ei riitt‰nyt
							if (count($lisatyt_rivit2) > 0) {
								foreach ($luodut_rivit as $poistetaan) {
									$query = "	DELETE FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus = $poistetaan
												and kpl = 0";
									$tresult = mysql_query($query) or pupe_error($query);

									$query = "UPDATE sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus=$poistetaan";
									$sarjares = mysql_query($query) or pupe_error($query);

									if ($debug==1) echo t("Saldo ei riitt‰nyt. Tehd‰‰n rollback")."<br>";
								}

								unset($luodut_rivit);
								unset($luodut_kpl);
								break;
							}

							if (count($lisatyt_rivit1) > 0) {
								// Korjataan sarjanumero-oliot, eli siirret‰‰n ne jtrivilt‰/ennakkorivilt‰ tilausriville
								$query = "	SELECT myyntirivitunnus, count(*) kpl
											FROM sarjanumeroseuranta
											WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
											and sarjanumeroseuranta.tuoteno = '$lisataanrow[tuoteno]'
											and sarjanumeroseuranta.myyntirivitunnus  = '$lisataanrow[tunnus]'
											group by myyntirivitunnus";
								$sarjares = mysql_query($query) or pupe_error($query);
								$sarjarow = mysql_fetch_array($sarjares);

								foreach ($lisatyt_rivit1 as $lisatty_tun) {
									$query = "	SELECT *
												FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												and otunnus = '$id'
												and tunnus  = '$lisatty_tun'";
									$lisres = mysql_query($query) or pupe_error($query);
									$lisrow = mysql_fetch_array($lisres);

									if ($lisrow["varattu"] != 0) {
										$lisrow["varattu"] = (int) abs(round($lisrow["varattu"], 0));

										$query = "	UPDATE sarjanumeroseuranta
													SET myyntirivitunnus = '$lisrow[tunnus]'
													WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
													and sarjanumeroseuranta.tuoteno = '$lisataanrow[tuoteno]'
													and sarjanumeroseuranta.myyntirivitunnus  = '$lisataanrow[tunnus]'
													LIMIT $lisrow[varattu]";
										$cores = mysql_query($query) or pupe_error($query);
									}

									//Kopioidaan tilausrivin lisatiedot
									$query = "	SELECT *
												FROM tilausrivin_lisatiedot
												WHERE tilausrivitunnus='$lisataanrow[tunnus]' and yhtio ='$kukarow[yhtio]'";
									$monistares2 = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($monistares2) > 0) {
										$monistarow2 = mysql_fetch_array($monistares2);

										$kysely = "	UPDATE tilausrivin_lisatiedot SET ";

 										// Ei monisteta tunnusta
										for ($i=0; $i < mysql_num_fields($monistares2)-1; $i++) {

											$fields .= ", ".mysql_field_name($monistares2,$i);

											switch (mysql_field_name($monistares2,$i)) {
												case 'yhtio':
												case 'tilausrivitunnus':
												case 'tiliointirivitunnus':
												case 'tilausrivilinkki':
												case 'toimittajan_tunnus':
												case 'luontiaika':
												case 'laatija':
													$values .= "";
													break;
												case 'positio':
													if ($kukarow['extranet'] != '' and $tilaus_on_jo == "KYLLA") {
														$kysely .= "positio='JT',";
													}
													else {
														$kysely .= mysql_field_name($monistares2,$i)."='".$monistarow2[$i]."',";
													}
													break;
												default:
													$kysely .= mysql_field_name($monistares2,$i)."='".$monistarow2[$i]."',";
											}
										}

										$kysely  = substr($kysely, 0, -1);
										$kysely .= " WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus='$lisrow[tunnus]'";
										$insres2 = mysql_query($kysely) or pupe_error($kysely);
									}

									// jos alkuper‰inen rivi oli suoratoimitettu asiakkaalle, merkataan rivit ker‰tyksi ja toimitetuksi
									if ($isarivirow["suoraan_laskutukseen"] != "") {
										$toimquery = "	UPDATE tilausrivi
														SET keratty = '$kukarow[kuka]',
														kerattyaika = now(),
														toimitettu = '$kukarow[kuka]',
														toimitettuaika = now(),
														kommentti = concat(kommentti, ' ".t("Suoratoimitus")."')
														WHERE yhtio = '$kukarow[yhtio]'
														AND tunnus = '$lisrow[tunnus]'";
										$toimupdres = mysql_query($toimquery) or pupe_error($toimquery);
									}
								}

								//Jos jotakin kopioitavan rivin sarjanumeroista ei saatu kopioitua niin irrotetaan se silt‰ rivilt‰
								$query = "	UPDATE sarjanumeroseuranta
											SET myyntirivitunnus = 0
											WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
											and sarjanumeroseuranta.tuoteno = '$lisataanrow[tuoteno]'
											and sarjanumeroseuranta.myyntirivitunnus  = '$lisataanrow[tunnus]'";
								$cores = mysql_query($query) or pupe_error($query);
							}
						}

						if (count($luodut_rivit) > 0) {
							// Katotaan mit‰ tehd‰‰n lopuille
							// Poistetaan loput
							if ($loput[$tunnukset] == 'POISTA') {
								foreach ($tunnusarray as $tunnus_nyt) {
									if ($toim == "ENNAKKO") {
										$query = "	SELECT tilausrivi.varattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									else {
										$query = "	SELECT tilausrivi.jt $lisavarattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									$rivitres = mysql_query($query) or pupe_error($query);

									$poista_update_lisa = "";

									if (mysql_num_rows($rivitres) == 1) {
										$row = mysql_fetch_array ($rivitres);

										if ($row["jt"] > $luodut_kpl[$tunnus_nyt]) {

											$jaljella = $row["jt"] - $luodut_kpl[$tunnus_nyt];

											if ($toim == "ENNAKKO" or $yhtiorow["varaako_jt_saldoa"] != "") {
												$poista_update_lisa = ", varattu = '$jaljella', jt = '0' ";

											}
											else {
												$poista_update_lisa = ", jt = '$jaljella', varattu = '0' ";
											}
										}
										elseif ($row['jt'] == $luodut_kpl[$tunnus_nyt]) {
											$poista_update_lisa = ", varattu = '0', jt = '0' ";
										}
									}

									$query = "	UPDATE tilausrivi SET
												tyyppi = 'D'
												$poista_update_lisa
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus = '$tunnus_nyt'
												and kpl = 0";
									$tresult = mysql_query($query) or pupe_error($query);

									if ($debug==1) echo t("Poistetaan loput rivilt‰").": $tunnus_nyt<br>";
								}
							}
							// J‰tet‰‰n loput
							else {
								foreach ($tunnusarray as $tunnus_nyt) {
									if ($toim == "ENNAKKO") {
										$query = "	SELECT tilausrivi.varattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									else {
										$query = "	SELECT tilausrivi.jt $lisavarattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									$rivitres = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($rivitres) == 1) {
										$row = mysql_fetch_array ($rivitres);

										if ($row["jt"] > $luodut_kpl[$tunnus_nyt]) {

											$jaljella = $row["jt"] - $luodut_kpl[$tunnus_nyt];

											if ($toim == "ENNAKKO" or $yhtiorow["varaako_jt_saldoa"] != "") {
												$query = "	UPDATE tilausrivi
															SET varattu = '$jaljella'
															WHERE yhtio = '$kukarow[yhtio]'
															and tunnus = '$tunnus_nyt'
															and kpl = 0";
											}
											else {
												$query = "	UPDATE tilausrivi
															SET jt = '$jaljella'
															WHERE yhtio = '$kukarow[yhtio]'
															and tunnus = '$tunnus_nyt'
															and kpl = 0";
											}
											$tresult = mysql_query($query) or pupe_error($query);

											if ($debug==1) echo t("J‰tet‰‰n loput rivilt‰").": $tunnus_nyt<br>";

										}
										else {
											$query = "	DELETE FROM tilausrivi
														WHERE yhtio = '$kukarow[yhtio]'
														and tunnus = '$tunnus_nyt'
														and kpl = 0";
											$tresult = mysql_query($query) or pupe_error($query);

											if ($debug==1) echo t("J‰tet‰‰n loput, mutta j‰tett‰v‰‰ ei ollut, joten dellataan rivi").": $tunnus_nyt<br>";
										}
									}
								}
							}

							//Korjataan perheid:t uusilla riveill‰
							$query = "	SELECT perheid, min(tunnus) uusiperheid
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus = '$id'
										and perheid != 0
										GROUP by perheid";
							$copresult = mysql_query($query) or pupe_error($query);

							while ($coprivirow = mysql_fetch_array($copresult)) {
								$query = "	UPDATE tilausrivi
											SET perheid = '$coprivirow[uusiperheid]'
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus = '$id'
											and perheid = '$coprivirow[perheid]'";
								$cores = mysql_query($query) or pupe_error($query);
							}

							//Korjataan perheid2:t uusilla riveill‰
							$query = "	SELECT perheid2, min(tunnus) uusiperheid2
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus = '$id'
										and perheid2 != 0
										GROUP by perheid2";
							$copresult = mysql_query($query) or pupe_error($query);

							while ($coprivirow = mysql_fetch_array($copresult)) {
								$query = "	UPDATE tilausrivi
											SET perheid2 = '$coprivirow[uusiperheid2]'
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus = '$id'
											and perheid2 = '$coprivirow[perheid2]'";
								$cores = mysql_query($query) or pupe_error($query);
							}
						}
					}
					else {
						echo "<br>".t("Syˆtit liian ison numeron! Ei voitu tehd‰ mit‰‰n").".<br><br>";
					}
				}
			}
		}
	}
?>