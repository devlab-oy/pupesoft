<?php

	// $tunnukset jossa on k‰sitelt‰vien tilausrivien tunnukset
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
			global $yhtiorow, $kukarow, $toim;

			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			if ($loput[$tunnukset] == 'MITA') {
				if ($toim == "ENNAKKO") {
					$query = "	DELETE FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	in ($tunnukset)
								and kpl		= 0
								and tyyppi	= 'E'";
				}
				else {
					$query = "	DELETE FROM tilausrivi
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
					$query = "	SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen
								FROM tilausrivi use index (PRIMARY)
								JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tunnus  = '$tunnusarray[0]'
								and tilausrivi.varattu > 0
								and tilausrivi.tyyppi  = 'E'";
				}
				else {
					$query = "	SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen
								FROM tilausrivi use index (PRIMARY)
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
								tila        in ('N', 'L', 'E', 'G')";
					$stresult = mysql_query($query) or pupe_error($query);
				}
				else {
					// tila pit‰‰ olla l‰hete, tilaus, ennakko tai siirtolista
					$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$isarivirow[otunnus]' and tila in ('N', 'L', 'E', 'G')";
					$stresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($stresult) == 0) {
						echo "Otsikkoa '$isarivirow[otunnus]' ei lˆytynyt";
						exit;
					}
					$otsikkorivi=mysql_fetch_array ($stresult);

					if ($debug==1) echo t("Perusotsikko lˆytyi")." $otsikkorivi[nimi] $isarivirow[otunnus]<br>";

					$erpcmlisa1 = "";
					$erpcmlisa2 = "";

					if ($toim == "ENNAKKO") {
						// Tutkitaan perusotsikon er‰p‰iv‰m‰‰r‰‰
						$erpquery = "select * from maksuehto where yhtio = '$kukarow[yhtio]' and tunnus='$otsikkorivi[maksuehto]'";
						$erpres = mysql_query($erpquery) or pupe_error($erpquery);
						$meapurow = mysql_fetch_array($erpres);

						list($erpcmvv, $erpcmkk, $erpcmpp) = split('-', $otsikkorivi["erpcm"]);

						$nyt = (int) date("Ymd");
						$erp = (int) $erpcmvv.$erpcmkk.$erpcmpp;

						//Jos alkuper‰isell‰ tilauksella on er‰p‰iv‰m‰‰r‰ syˆtetty k‰sin niin huomioidaan se nyt rivien poiminnassa
						if ($meapurow["erapvmkasin"] != "" and checkdate($erpcmkk, $erpcmpp, $erpcmvv) and $erp > $nyt) {
							$erpcmlisa1 = " and erpcm = '$otsikkorivi[erpcm]' ";
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
								vienti_kurssi	= '$otsikkorivi[vienti_kurssi]' and
								erikoisale		= '$otsikkorivi[erikoisale]' and
								eilahetetta		= '$isarivirow[suoraan_laskutukseen]' and
								tunnusnippu		= '$otsikkorivi[tunnusnippu]' and
								piiri			= '$otsikkorivi[piiri]'
								$erpcmlisa1";
					$stresult = mysql_query($query) or pupe_error($query);
				}

				if (mysql_num_rows($stresult) != 0) {
					$otsikkorivi = mysql_fetch_array ($stresult);
					$id = $otsikkorivi['tunnus'];
					if ($debug==1) echo t("Lis‰‰n laskun olemassaolevaan")." $otsikkorivi[nimi] $id<br>";
				}
				else {

					// tehd‰‰n vanhasta laskusta 1:1 kopio...
					$query = "insert into lasku set ";
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
						// ker‰ysaika, luontiaika ja toimitusaikaan now
						elseif (mysql_field_name($stresult,$i)=='kerayspvm' or
								mysql_field_name($stresult,$i)=='luontiaika' or
								mysql_field_name($stresult,$i)=='toimaika') {
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
						// n‰m‰ kent‰t tyhjennet‰‰n
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
								mysql_field_name($stresult,$i)=='viite' or
								mysql_field_name($stresult,$i)=='laskunro' or
								mysql_field_name($stresult,$i)=='mapvm' or
								mysql_field_name($stresult,$i)=='tilausvahvistus' or
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
								mysql_field_name($stresult,$i)=='sisviesti1' or
								mysql_field_name($stresult,$i)=='sisviesti2' or
								mysql_field_name($stresult,$i)=='poistumistoimipaikka_koodi') {
							$query .= mysql_field_name($stresult,$i)."='',";
						}
						elseif (mysql_field_name($stresult,$i) == 'viesti') {
							if ($toim == "ENNAKKO") {
								$query .= "viesti='".$otsikkorivi[$i]."',";
							}
							else {
								$query .= "viesti='',";
							}
						}
						// kommenttii speciaalia
						elseif (mysql_field_name($stresult,$i) == 'comments') {
							if ($toim == "ENNAKKO") {
								$query .= "comments='".t("ENNAKKOTILAUS")."',";
							}
							elseif ($otsikkorivi['tila'] == 'G') {
								$query .= "comments=concat('".t("JƒLKITOIMITUS")." ', comments),";
							}
							else {
								$query .= "comments='".t("JƒLKITOIMITUS")."',";
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
						elseif (mysql_field_name($stresult,$i)=='eilahetetta') {
							$query .= "eilahetetta='".$isarivirow["suoraan_laskutukseen"]."',";
						}
						elseif (mysql_field_name($stresult,$i)=='rahtivapaa') {
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
							$maksuehto_result = mysql_query($maksuehto_query) or pupe_error($query);
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

					if ($debug==1) echo t("Perustin laskun")." $otsikkorivi[nimi] $id<br>";
				}

				///* T‰ss‰ meill‰ on otsikko hallussa se on $id muuttujassa*///

// 				// Jos yhtion rahtihinnoittelu tapahtuu alkuper‰isen tilausarvon mukaan, niin JT-tilauksiin ei en‰‰ laiteta rahtikulua uudestaan
// 				// Eli lis‰t‰‰n rahtikulurivi-nollahintaisena
// 				if ($yhtiorow["rahti_hinnoittelu"] != "") {
//
// 					$query = "	SELECT *
// 								FROM lasku
// 								WHERE yhtio = '$kukarow[yhtio]'
// 								AND tunnus = '$id'";
// 					$aresult = mysql_query($query) or pupe_error($query);
// 					$laskurow = mysql_fetch_array($aresult);
//
// 					$query = "	SELECT tunnus
// 								from tilausrivi
// 								where yhtio = '$kukarow[yhtio]'
// 								and otunnus = '$laskurow[tunnus]'
// 								and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
// 					$result = mysql_query($query) or pupe_error($query);
//
// // ($yhtiorow['jt_rahti'] == 'Y' and ($laskurow['summa'] < $asiakas_row['rahtivapaa_alarajasumma'] or $laskurow['summa'] < $yhtiorow['rahtivapaa_alarajasumma']))
//
// 					//jos tilauksella on jo rahtikulu niin ei lis‰t‰ sit‰ uudestaan
// 					if (mysql_num_rows($result) == 0 and $laskurow['rahtivapaa'] == '') {
//
// 						//lis‰t‰‰n rahtikulurivi
// 						$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
// 						$rhire = mysql_query($query) or pupe_error($query);
// 						$trow  = mysql_fetch_array($rhire);
//
// 						$hinta		= 0; // rahtihinta
// 						$trow["alv"]= $laskurow["alv"];
// 						$nimitys	= $laskurow["toimitustapa"];
//
// 						list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');
//
// 						$query  = "	INSERT into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
// 									values ('$hinta', 'N', '1', '1', '$laskurow[tunnus]', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
// 						$addtil = mysql_query($query) or pupe_error($query);
//
// 						if ($debug==1) echo t("Lis‰ttiin rahtikulu")." $otsikkorivi[nimi] $id<br>";
// 					}
// 				}

				// T‰ss‰ haarassa k‰ytt‰j‰ on syˆtt‰nyt jonkun kappalem‰‰r‰n
				if ($kpl[$tunnukset] > 0 or $loput[$tunnukset] == 'KAIKKI') {

					if ($loput[$tunnukset] == 'KAIKKI') {
						if ($toim == "ENNAKKO") {
							$kpl[$tunnukset] = $isarivirow["varattu"];
						}
						else {
							$kpl[$tunnukset] = $isarivirow["jt"];
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

					if ($kpl[$tunnukset] <= $isa_kpl and $kpl[$tunnukset] > 0) {

						$luodut_rivit 		= array();
						$luodut_kpl			= array();

						foreach($tunnusarray as $tunnus_nyt) {
							if ($toim == "ENNAKKO") {
								$query = "	SELECT tilausrivi.*, tuote.ei_saldoa
											FROM tilausrivi use index (PRIMARY), tuote
											WHERE tilausrivi.yhtio 	= '$kukarow[yhtio]'
											and tilausrivi.tunnus  	= '$tunnus_nyt'
											and tilausrivi.varattu 	> 0
											and tilausrivi.tyyppi  	= 'E'
											and tilausrivi.yhtio 	= tuote.yhtio
											and tilausrivi.tuoteno	= tuote.tuoteno";
							}
							else {
								$query = "	SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa
											FROM tilausrivi, tuote
											WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
											and tilausrivi.tunnus  			= '$tunnus_nyt'
											and tilausrivi.jt $lisavarattu	> 0
											and tilausrivi.kpl     			= 0
											and tilausrivi.var     			= 'J'
											and tilausrivi.tyyppi			in ('L','G')
											and tilausrivi.yhtio			= tuote.yhtio
											and tilausrivi.tuoteno			= tuote.tuoteno";
							}
							$rivitres = mysql_query($query) or pupe_error($query);
							$lisataanrow = mysql_fetch_array ($rivitres);

							if ($toim == "ENNAKKO") {
								$lisataanrow_kpl = round($lisataanrow["varattu"] * $isa_kerroin,2);
							}
							else {
								$lisataanrow_kpl = round($lisataanrow["jt"] * $isa_kerroin,2);
							}

							if ($debug==1) echo t("Yritet‰‰n myyd‰ tuotetta").": $lisataanrow[tuoteno] $lisataanrow_kpl kappaletta.<br>";

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
							$ale 			 	= $lisataanrow["ale"];
							$kpl				= $lisataanrow_kpl;
							$varasto 		 	= $varastosta;
							$korvaavakielto	 	= "Y";
							$perhekielto     	= "Y";
							$osatoimkielto		= "Y";
							$var				= "";
							$perheid		 	= $lisataanrow["perheid"];
							$perheid2		 	= $lisataanrow["perheid2"];
							$kommentti		 	= trim($otsikkorivi['viesti']." ".$lisataanrow['kommentti']);

							$jtspec				= "JTSPEC";

							// Tutkitaan onko t‰m‰ myyty ulkomaan alvilla
							list(,,,$tsek_alehinta_alv,) = alehinta($laskurow, $trow, $kpl, '', '', '');

							if ($tsek_alehinta_alv > 0) {
								$trow["alv"] = $tsek_alehinta_alv;
							}

							if ($yhtiorow["alv_kasittely"] == "" and $trow["alv"] != $lisataanrow["alv"] and $lisataanrow["alv"] < 500) {
								$hinta 			= sprintf('%.2f',round($lisataanrow["hinta"] / (1+$lisataanrow['alv']/100) * (1+$trow["alv"]/100), $yhtiorow['hintapyoristys']));
							}
							else {
								$hinta			= $lisataanrow["hinta"];
							}

							if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
								$hinta 			= laskuval($hinta, $laskurow["vienti_kurssi"]);
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
								foreach($lisatyt_rivit1 as $lisatty_tun) {
									$luodut_rivit[$lisatty_tun] = $lisatty_tun;

									//Pidet‰‰n muistissa lis‰tyt kappaleet
									$luodut_kpl[$tunnus_nyt] += $lisataanrow_kpl;
								}
							}
							if (count($lisatyt_rivit2) > 0) {
								// Puute - JT - Ostohaara
								foreach($lisatyt_rivit2 as $lisatty_tun) {
									$luodut_rivit[] = $lisatty_tun;
								}
							}

							//Jos saldo ei riitt‰nyt
							if (count($lisatyt_rivit2) > 0) {
								foreach($luodut_rivit as $poistetaan) {
									$query = "	DELETE FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]' and tunnus=$poistetaan and kpl=0";
									$tresult = mysql_query($query) or pupe_error($query);

									$query = "update sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus=$poistetaan";
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

								foreach($lisatyt_rivit1 as $lisatty_tun) {
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

										for($i=0; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta

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
												default:
													$kysely .= mysql_field_name($monistares2,$i)."='".$monistarow2[$i]."',";
											}
										}

										$kysely  = substr($kysely, 0, -1);
										$kysely .= " WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus='$lisrow[tunnus]'";
										$insres2 = mysql_query($kysely) or pupe_error($kysely);
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
								foreach($tunnusarray as $tunnus_nyt) {
									$query = "	DELETE FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus_nyt' and kpl=0";
									$tresult = mysql_query($query) or pupe_error($query);

									if ($debug==1) echo t("Poistetaan loput rivilt‰").": $tunnus_nyt<br>";
								}
							}
							// J‰tet‰‰n loput
							else {
								foreach($tunnusarray as $tunnus_nyt) {
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
															WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus_nyt' and kpl=0";
											}
											else {
												$query = "	UPDATE tilausrivi
															SET jt = '$jaljella'
															WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus_nyt' and kpl=0";
											}
											$tresult = mysql_query($query) or pupe_error($query);

											if ($debug==1) echo t("J‰tet‰‰n loput rivilt‰").": $tunnus_nyt<br>";

										}
										else {
											$query = "	DELETE FROM tilausrivi
														WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus_nyt' and kpl=0";
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
