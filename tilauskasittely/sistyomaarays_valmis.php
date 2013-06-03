<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Kuittaa sisäinen työmääräys valmiiksi").":<hr></font>";

	if ($tee == "VALMIS") {
		// katsotaan onko muilla aktiivisena
		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
		$result = pupe_query($query);

		unset($row);

		if (mysql_num_rows($result) != 0) {
			$row=mysql_fetch_assoc($result);
		}

		if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
			echo "<font class='error'>".t("Tilaus on aktiivisena käyttäjällä")." $row[nimi]. ".t("Tilausta ei voi tällä hetkellä muokata").".</font><br>";

			// poistetaan aktiiviset tilaukset jota tällä käyttäjällä oli
			$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = pupe_query($query);

			$tee = "";
		}
		else {
			// lock tables
			$query = "	LOCK TABLES lasku WRITE,
						tapahtuma WRITE,
						tiliointi WRITE,
						tilausrivi WRITE,
						tilausrivi as tilausrivi2 WRITE,
						sanakirja WRITE,
						tilausrivi as tilausrivi_osto READ,
						tuote READ,
						sarjanumeroseuranta WRITE,
						sarjanumeroseuranta_arvomuutos READ,
						tuotepaikat WRITE,
						tilausrivin_lisatiedot WRITE,
						avainsana as avainsana_kieli READ,
						tili WRITE";
			$locre = pupe_query($query);

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and alatila	= 'C'
						and tila 	= 'S'
						and tunnus 	= '$tilausnumero'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				//Haetaan jatkojalostettavat tuotteet
				$query    = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno	and tuote.sarjanumeroseuranta != ''
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								and tilausrivi.otunnus='$tilausnumero'";
				$jres = pupe_query($query);

				while($srow1 = mysql_fetch_assoc($jres)) {
					$query = "	SELECT count(distinct sarjanumero) kpl
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno 			= '$srow1[tuoteno]'
								and siirtorivitunnus 	= '$srow1[tunnus]'";
					$sarjares2 = pupe_query($query);
					$srow2 = mysql_fetch_assoc($sarjares2);

					if ($srow2["kpl"] != abs($srow1["varattu"])) {
						echo t("Tilaukselta puuttuu sarjanumeroita, ei voida merkata valmiiksi").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";

						require ("../inc/footer.inc");
						exit;
					}
				}

				$query = "	UPDATE lasku
							SET alatila = 'X'
							WHERE yhtio = '$kukarow[yhtio]' and alatila='C' and tila = 'S' and tunnus = '$tilausnumero'";
				$result = pupe_query($query);

				//Haetaan jatkojalostettavat tuotteet
				$query    = "	SELECT tilausrivi.tunnus, tilausrivi.varattu varattu,
								tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								and tilausrivi.otunnus='$tilausnumero'
								and tilausrivi.perheid2=tilausrivi.tunnus";
				$jres = pupe_query($query);

				while($srow = mysql_fetch_assoc($jres)) {

					//Jotta saadaan oma kulukeikka per laite
					unset($otunnus);

					//Haetaan jatkojalostettavan tuotteen ostorivitunnus
					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tuoteno			 = '$srow[tuoteno]'
								and siirtorivitunnus = '$srow[tunnus]'";
					$sarjares = pupe_query($query);
					$sarjarow = mysql_fetch_assoc($sarjares);

					// Jos samalla ostorivillä on useita kappaleita niin meidän on splitattava ostorivi osiin
					$query = "	SELECT *
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$sarjarow[ostorivitunnus]'";
					$monista = pupe_query($query);
					$ostorow = mysql_fetch_assoc($monista);

					if ($ostorow["kpl"] > 1) {
						//Haetaan alkuperäinen ostorivi ja sen tehdaslisävarusteet
						$query = " (SELECT *
									FROM tilausrivi
									WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
									and tilausrivi.tunnus 	= '$ostorow[tunnus]')
									UNION
								   (SELECT *
									FROM tilausrivi as tilausrivi2
									WHERE tilausrivi2.yhtio  = '$kukarow[yhtio]'
									and tilausrivi2.perheid2 = '$ostorow[tunnus]'
									and tilausrivi2.tyyppi  != 'D'
									and tilausrivi2.tunnus  != '$ostorow[tunnus]'
									and tilausrivi2.perheid2!= 0)";
						$monistares2 = pupe_query($query);

						while ($ostokokorow = mysql_fetch_assoc($monistares2)) {

							$maara = round($ostokokorow["kpl"] / $ostorow["kpl"],2);

							if ($maara > 0) {

								$kplhinta  		= ($ostokokorow["rivihinta"] / $ostokokorow["kpl"]);
								$rivihinta 		= round(($ostokokorow["kpl"]-$maara) * $kplhinta, $yhtiorow['hintapyoristys']);
								$koprivihinta 	= round($maara * $kplhinta, $yhtiorow['hintapyoristys']);

								// Vähennetään alkuperäisen ostorivin määrää yhdellä
								$query = "	UPDATE tilausrivi
											SET kpl=kpl-$maara, tilkpl=tilkpl-$maara, rivihinta=$rivihinta
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus  = '$ostokokorow[tunnus]'";
								$insres2 = pupe_query($query);

								//Kopioidaan tilausrivi
								$kysely = "	INSERT INTO tilausrivi SET ";

								for ($i=0; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta

									$kennimi = mysql_field_name($monistares2, $i);

									switch ($kennimi) {
										case 'kpl':
										case 'tilkpl':
											$kysely .= $kennimi."='$maara',";
											break;
										case 'rivihinta':
											$kysely .= $kennimi."='$koprivihinta',";
											break;
										case 'kate_korjattu':
											$kysely .= $kennimi."=NULL,";
											break;
										default:
											$kysely .= $kennimi."='".$ostokokorow[$kennimi]."',";
									}
								}

								$kysely  = substr($kysely, 0, -1);
								$insres2 = pupe_query($kysely);
								$insid   = mysql_insert_id();

								//Haetaan alkuperäisen ostorivin tapahtuma
								$query = "	SELECT *
											FROM tapahtuma
											WHERE yhtio 	= '$kukarow[yhtio]'
											and rivitunnus  = '$ostokokorow[tunnus]'";
								$tapares = pupe_query($query);

								if (mysql_num_rows($tapares) == 1) {
									$taparow = mysql_fetch_assoc($tapares);

									// Vähennetään alkuperäisen ostorivin tapahtuman määrää yhdellä
									$query = "	UPDATE tapahtuma
												SET kpl=kpl-$maara
												WHERE yhtio 	= '$kukarow[yhtio]'
												and rivitunnus  = '$ostokokorow[tunnus]'";
									$insres2 = pupe_query($query);

									//Kopioidaan tapahtuma
									$kysely = "	INSERT INTO tapahtuma SET ";

									for($i=0; $i < mysql_num_fields($tapares)-1; $i++) { // Ei monisteta tunnusta

										$kennimi = mysql_field_name($tapares, $i);

										switch ($kennimi) {
											case 'kpl':
												$kysely .= $kennimi."='$maara',";
												break;
											case 'rivitunnus':
												$kysely .= $kennimi."='$insid',";
												break;
											default:
												$kysely .= $kennimi."='".$taparow[$kennimi]."',";
										}
									}

									$kysely  = substr($kysely, 0, -1);
									$insres2 = pupe_query($kysely);
								}

								// Laitetaan perheidt kuntoon
								if ($ostokokorow["tunnus"] == $ostokokorow["perheid2"] or $ostokokorow["perheid2"] == $perheid2) {
									if ($ostokokorow["tunnus"] == $ostokokorow["perheid2"]) {
										$uusi_perheid2 	= $insid;
									}

									$perheid2 = $ostokokorow["perheid2"];

									$query = "	UPDATE tilausrivi
												SET perheid2 = $uusi_perheid2
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus  = '$insid'";
									$insres2 = pupe_query($query);
								}

								// Siirretään jatkojalostettavan tuotteen sarjanumero uudelle tilausriville
								if ($ostokokorow["tunnus"] == $ostorow["tunnus"]) {
									$query = "	UPDATE sarjanumeroseuranta
												SET ostorivitunnus = $insid
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus  = '$sarjarow[tunnus]'";
									$insres2 = pupe_query($query);

									$sarjarow["ostorivitunnus"] = $insid;
								}
							}
						}
					}

					// Haetaan liitetyt lisävarusteet ja työkulut
					$query    = "	SELECT tilausrivi.*, tuote.ei_saldoa, tuote.kehahin, tuote.myyntihinta, tuote.alv, tuote.sarjanumeroseuranta, tilausrivi.tunnus as rivitunnus
									FROM tilausrivi
									JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
									WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
									and tilausrivi.otunnus	= '$tilausnumero'
									and tilausrivi.perheid2	= '$srow[tunnus]'
									and tilausrivi.tunnus  != '$srow[tunnus]'";
					$kres = pupe_query($query);

					while ($lisarow = mysql_fetch_assoc($kres)) {
						$ostohinta 			  = 0;
						$ostorivinsarjanumero = 0;

						if ($lisarow["sarjanumeroseuranta"] == "S") {
							//Hateaan lisävarusteen ostohinta
							$query = "	SELECT round(sum(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl),2) ostohinta, group_concat(sarjanumeroseuranta.tunnus) tunnus
										FROM sarjanumeroseuranta
										JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
										WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno = '$lisarow[tuoteno]'
										and sarjanumeroseuranta.siirtorivitunnus = '$lisarow[rivitunnus]'";
							$sarjares = pupe_query($query);
							$sarjarow2 = mysql_fetch_assoc($sarjares);

							$ostohinta 				= $sarjarow2["ostohinta"];
							$ostorivinsarjanumero	= $sarjarow2["tunnus"];
						}
						else {
							// Lisävaruste on bulkkikamaa
							$ostohinta = $lisarow["kehahin"];
						}

						$ostohinta = (float) $ostohinta;

						// Ruuvataan liitetyt lisävarusteet kiinni laitteen alkuperäiseen ostoriviin
						// Jos lisävaruste on sarjanumeroitava, niin sarjanumeron tunnus menee
						// lisävarusterivin sistyomaarays_sarjatunnus-kentässä
						$query = "	UPDATE tilausrivi
									SET perheid2	= '$sarjarow[ostorivitunnus]',
									rivihinta		= round(varattu*$ostohinta,2),
									toimitettu		= '$kukarow[kuka]',
									toimitettuaika 	= now(),
									tilkpl			= varattu,
									varattu 		= 0
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$lisarow[rivitunnus]'";
						$sarjares = pupe_query($query);

						$query = "	UPDATE tilausrivin_lisatiedot
									SET sistyomaarays_sarjatunnus = '$ostorivinsarjanumero'
									WHERE yhtio 			= '$kukarow[yhtio]'
									and tilausrivitunnus  	= '$lisarow[rivitunnus]'";
						$sarjares = pupe_query($query);

						if ($lisarow["ei_saldoa"] == '') {
							//Siirretään lisävaruste samaan paikkaan ku isätuote ja varataan lisävarusteen saldoa
							if($lisarow["hyllyalue"] != $srow["hyllyalue"] or $lisarow["hyllynro"] != $srow["hyllynro"] or $lisarow["hyllyvali"] != $srow["hyllyvali"] or $lisarow["hyllytaso"] != $srow["hyllytaso"]) {
								// Tehdään saldosiirto

								// Katsotaan löytyykö jatkojalostettavan tuotteen paikka myös lisävarusteelta
								$query = "	SELECT *
											FROM tuotepaikat
											WHERE tuoteno 	= '$lisarow[tuoteno]'
											and yhtio 		= '$kukarow[yhtio]'
											and hyllyalue	= '$srow[hyllyalue]'
											and hyllynro 	= '$srow[hyllynro]'
											and hyllyvali 	= '$srow[hyllyvali]'
											and hyllytaso	= '$srow[hyllytaso]'";
								$sarjares = pupe_query($query);

								if (mysql_num_rows($sarjares) == 0) {
									// Lisätään tuotepaikkoja
									$query = "	INSERT INTO tuotepaikat
												SET tuoteno = '$lisarow[tuoteno]',
												yhtio 		= '$kukarow[yhtio]',
												hyllyalue	= '$srow[hyllyalue]',
												hyllynro 	= '$srow[hyllynro]',
												hyllyvali 	= '$srow[hyllyvali]',
												hyllytaso	= '$srow[hyllytaso]'";
									$sarjares = pupe_query($query);
									$minne  = mysql_insert_id();

									$query = "	SELECT *
												FROM tuotepaikat
												WHERE tuoteno 	= '$lisarow[tuoteno]'
												and yhtio 		= '$kukarow[yhtio]'
												and tunnus		= '$minne'";
									$sarjares = pupe_query($query);
									$minnerow  = mysql_fetch_assoc($sarjares);

									$query = "	INSERT into tapahtuma set
												yhtio 		= '$kukarow[yhtio]',
												tuoteno 	= '$lisarow[tuoteno]',
												kpl 		= '0',
												kplhinta	= '0',
												hinta 		= '0',
												hyllyalue	= '$srow[hyllyalue]',
												hyllynro	= '$srow[hyllynro]',
												hyllyvali	= '$srow[hyllyvali]',
												hyllytaso	= '$srow[hyllytaso]',
												laji 		= 'uusipaikka',
												selite 		= '".t("Lisättiin tuotepaikka")." $srow[hyllyalue] $srow[hyllynro] $srow[hyllyvali] $srow[hyllytaso]. ".t("Sisäinen työmääräys")."',
												laatija 	= '$kukarow[kuka]',
												laadittu 	= now()";
									$sarjares = pupe_query($query);
								}
								else {
									$minnerow  = mysql_fetch_assoc($sarjares);
								}

								$kehahin_query = "	SELECT
													round(if (tuote.epakurantti100pvm = '0000-00-00',
															if (tuote.epakurantti75pvm = '0000-00-00',
																if (tuote.epakurantti50pvm = '0000-00-00',
																	if (tuote.epakurantti25pvm = '0000-00-00',
																		tuote.kehahin,
																	tuote.kehahin * 0.75),
																tuote.kehahin * 0.5),
															tuote.kehahin * 0.25),
														0),
													6) kehahin
													FROM tuote
													WHERE yhtio = '$kukarow[yhtio]'
													and tuoteno = '$lisarow[tuoteno]'";
								$kehahin_result = pupe_query($kehahin_query);
								$kehahin_row = mysql_fetch_assoc($kehahin_result);

								// Vähennetään
								$query = "	UPDATE tuotepaikat
											SET saldo = saldo - $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$lisarow[hyllyalue]'
											and hyllynro  	= '$lisarow[hyllynro]'
											and hyllyvali 	= '$lisarow[hyllyvali]'
											and hyllytaso 	= '$lisarow[hyllytaso]'
											LIMIT 1";
								$sarjares = pupe_query($query);

								$query = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$lisarow[tuoteno]',
											kpl 		= $lisarow[varattu] * -1,
											hinta 		= '$kehahin_row[kehahin]',
											laji 		= 'siirto',
											hyllyalue	= '$lisarow[hyllyalue]',
											hyllynro	= '$lisarow[hyllynro]',
											hyllyvali	= '$lisarow[hyllyvali]',
											hyllytaso	= '$lisarow[hyllytaso]',
											selite 		= '".t("Paikasta")." $lisarow[hyllyalue] $lisarow[hyllynro] $lisarow[hyllyvali] $lisarow[hyllytaso] ".t("vähennettiin")." $lisarow[varattu]. ".t("Sisäinen työmääräys")."',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$sarjares = pupe_query($query);

								//Lisätään
								$query = "	UPDATE tuotepaikat
											SET saldo = saldo + $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$minnerow[hyllyalue]'
											and hyllynro  	= '$minnerow[hyllynro]'
											and hyllyvali 	= '$minnerow[hyllyvali]'
											and hyllytaso 	= '$minnerow[hyllytaso]'
											LIMIT 1";
								$sarjares = pupe_query($query);

								$query = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$lisarow[tuoteno]',
											kpl 		= $lisarow[varattu],
											hinta 		= '$kehahin_row[kehahin]',
											laji 		= 'siirto',
											hyllyalue 	= '$minnerow[hyllyalue]',
											hyllynro  	= '$minnerow[hyllynro]',
											hyllyvali 	= '$minnerow[hyllyvali]',
											hyllytaso 	= '$minnerow[hyllytaso]',
											selite 		= '".t("Paikalle")." $minnerow[hyllyalue] $minnerow[hyllynro] $minnerow[hyllyvali] $minnerow[hyllytaso] ".t("lisättiin")." $lisarow[varattu]. ".t("Sisäinen työmääräys")."',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$sarjares = pupe_query($query);

								// Varataan lisävarusteet
								$query = "	UPDATE tuotepaikat
											SET saldo_varattu = saldo_varattu + $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$minnerow[hyllyalue]'
											and hyllynro  	= '$minnerow[hyllynro]'
											and hyllyvali 	= '$minnerow[hyllyvali]'
											and hyllytaso 	= '$minnerow[hyllytaso]'
											LIMIT 1";
								$sarjares = pupe_query($query);
							}
							else {
								// Varataan lisävarusteet
								$query = "	UPDATE tuotepaikat
											SET saldo_varattu = saldo_varattu + $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$lisarow[hyllyalue]'
											and hyllynro  	= '$lisarow[hyllynro]'
											and hyllyvali 	= '$lisarow[hyllyvali]'
											and hyllytaso 	= '$lisarow[hyllytaso]'
											LIMIT 1";
								$sarjares = pupe_query($query);
							}

							if ($lisarow["sarjanumeroseuranta"] == "S") {
								//Päivitetään sarjanumeroiden varastopaikkatiedot
								$query = "	UPDATE sarjanumeroseuranta
											SET hyllyalue	= '$srow[hyllyalue]',
											hyllynro 		= '$srow[hyllynro]',
											hyllyvali 		= '$srow[hyllyvali]',
											hyllytaso		= '$srow[hyllytaso]'
											WHERE yhtio				= '$kukarow[yhtio]'
											and tuoteno				= '$lisarow[tuoteno]'
											and siirtorivitunnus	= '$lisarow[rivitunnus]'";
								$sarjares = pupe_query($query);
							}
						}
						else {
							// Lisävaruste on työkulu

							// Työkulujen arvo
							// Alvihinta korjataan vain jos yhtiölla on verolliset myyntihinnat
							if ($lisarow["alv"] != '' and $yhtiorow["alv_kasittely"] == "" ) {
								$tyokulut = round($lisarow["varattu"] * $lisarow["myyntihinta"] / (1+$lisarow['alv']/100), 2);
							}
							else {
								$tyokulut = round($lisarow["varattu"] * $lisarow["myyntihinta"], 2);
							}

							if ($tyokulut != 0) {

								if (!isset($otunnus)) {
									// haetaan seuraava vapaa keikkaid
									$query  = "	SELECT max(laskunro)+1 keikkanro
												FROM lasku
												WHERE yhtio = '$kukarow[yhtio]'
												AND tila = 'K'";
									$result = pupe_query($query);
									$row    = mysql_fetch_assoc($result);
									$id		= $row["keikkanro"];

									$query = "	INSERT into lasku set
												yhtio        = '$kukarow[yhtio]',
												laskunro     = '$id',
												ytunnus	     = '$sarjarow[tunnus]',
												nimi         = 'Sisäinen työmääräys: $tilausnumero',
												liitostunnus = '$sarjarow[tunnus]',
												tila         = 'K',
												alatila      = 'S',
												luontiaika	 = now(),
												laatija		 = '$kukarow[kuka]'";
									$result = pupe_query($query);
									$otunnus = mysql_insert_id();
								}

								$query = "	INSERT into lasku set
											yhtio      		= '$kukarow[yhtio]',
											tapvm      		= now(),
											tila       		= 'X',
											laskunro    	= '$id',
											maksu_kurssi 	= '1',
											vienti_kurssi	= '1',
											laatija    		= '$kukarow[kuka]',
											vanhatunnus		= '$otunnus',
											arvo	   		= $tyokulut,
											summa	   		= $tyokulut,
											vienti	   		= 'B',
											luontiaika 		= now()";
								$result = pupe_query($query);
								$laskuid = mysql_insert_id($link);

								list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"]);

								$query = "	INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varasto]',
											kustp    = '{$kustp_ins}',
											kohde	 = '{$kohde_ins}',
											projekti = '{$projekti_ins}',
											tapvm    = now(),
											summa    = '$tyokulut',
											vero     = 0,
											lukko    = '',
											selite   = 'Varastonmuutos sisäinen työmääräys $tilausnumero: $srow[tuoteno] ($sarjarow[sarjanumero])',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = pupe_query($query);

								list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varastonmuutos_valmistuksesta"]);

								$query = "	INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varastonmuutos_valmistuksesta]',
											kustp    = '{$kustp_ins}',
											kohde	 = '{$kohde_ins}',
											projekti = '{$projekti_ins}',
											tapvm    = now(),
											summa    = $tyokulut * -1,
											vero     = 0,
											lukko    = '',
											selite   = 'Varastonmuutos sisäinen työmääräys $tilausnumero: $srow[tuoteno] ($sarjarow[sarjanumero])',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = pupe_query($query);
							}
						}

						//Päivitetään varmuuden vuoksi alkuperäisen ostorivin perheid2 (se voi olla nolla)
						$query = "	UPDATE tilausrivi
									SET perheid2 = '$sarjarow[ostorivitunnus]'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$sarjarow[ostorivitunnus]'";
						$sarjares = pupe_query($query);

						if ($lisarow["sarjanumeroseuranta"] != '') {
							//Varataan sarjanumero jatkojalostettavalle tuotteelle
							$query = "	UPDATE sarjanumeroseuranta
										SET siirtorivitunnus 	= $sarjarow[ostorivitunnus]
										WHERE yhtio				= '$kukarow[yhtio]'
										and tuoteno				= '$lisarow[tuoteno]'
										and siirtorivitunnus	= '$lisarow[rivitunnus]'";
							$sarjares = pupe_query($query);
						}
					}

					//Irroitetaan jatkojalostettavan tuotteen sarjanumero
					$query = "	UPDATE sarjanumeroseuranta
								SET siirtorivitunnus = 0
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
					$sarjares = pupe_query($query);

					//Päivitetään jatkojalostettava rivi valmiiksi
					$query = "	UPDATE tilausrivi
								SET toimitettu='$kukarow[kuka]', toimitettuaika = now(), tilkpl=varattu, varattu = 0
								WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tunnus='$srow[tunnus]'";
					$sarjares = pupe_query($query);
				}

				echo "<font class='message'>".t("Sisäinen työmääräys merkattiin valmiiksi")."!</font><br><br>";
				$tee = "";
			}
			else {
				echo "<font class='error'>".t("VIRHE: Sisäinen työmääräys on väärässä tilassa")."!</font><br><br>";
				$tee = "";
			}

			$query = "UNLOCK TABLES";
			$locre = pupe_query($query);
		}
	}


	if ($tee == "") {

		// Näytetään muuten vaan sopivia tilauksia
		echo "<br><form method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<font class='head'>".t("Etsi sisäinen työmääräys").":<hr></font>
				".t("Syötä tilausnumero, nimen tai laatijan osa").":
				<input type='text' name='etsi'>
				<input type='Submit' value = '".t("Etsi")."'>
				</form>";

		// pvm 30 pv taaksepäin
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		$haku='';
		if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
		if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]'
					and tila	= 'S'
					and alatila = 'C'
					$haku
					order by luontiaika desc";
		$result = pupe_query($query);

		if (mysql_num_rows($result)!=0) {

			echo "<table>";

			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th align='left'>".t("tyyppi")."</th></tr>";

			while ($row = mysql_fetch_assoc($result)) {

				echo "<tr>";

				for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
					echo "<td>".$row[mysql_field_name($result, $i)]."</td>";
				}

				$laskutyyppi = $row["tila"];
				$alatila	 = $row["alatila"];

				//tehdään selväkielinen tila/alatila
				require ("inc/laskutyyppi.inc");

				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

				echo "<td class='back'>
						<form method='post' action='tilaus_myynti.php'>
						<input type='hidden' name='toim' value='SIIRTOTYOMAARAYS'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>
						<input type='submit' value='".t("Muokkaa")."'>
						</form>
						</td>";

				echo "<td class='back'>
						<form method='post'>
						<input type='hidden' name='tee' value='VALMIS'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>
						<input type='submit' value='".t("Valmis")."'>
						</form>
						</td>";

				echo "</tr>";
			}

			echo "</table>";

			if (is_array($sumrow)) {
				echo "<br><table cellpadding='5'><tr>";
				echo "<th>".t("Tilausten arvo yhteensä")." ($sumrow[kpl] ".t("kpl")."): </th>";
				echo "<td>$sumrow[arvo] $yhtiorow[valkoodi]</td>";
				echo "</tr></table>";
			}

		}
		else {
			echo t("Ei tilauksia")."...";
		}
	}
	require ("inc/footer.inc");
?>
