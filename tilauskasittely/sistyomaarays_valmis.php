<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Kuittaa sis�inen ty�m��r�ys valmiiksi").":<hr></font>";

	if ($tee == "VALMIS") {
		// katsotaan onko muilla aktiivisena
		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
		$result = mysql_query($query) or pupe_error($query);

		unset($row);

		if (mysql_num_rows($result) != 0) {
			$row=mysql_fetch_array($result);
		}

		if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
			echo "<font class='error'>".t("Tilaus on aktiivisena k�ytt�j�ll�")." $row[nimi]. ".t("Tilausta ei voi t�ll� hetkell� muokata").".</font><br>";

			// poistetaan aktiiviset tilaukset jota t�ll� k�ytt�j�ll� oli
			$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

			$tee = "";
		}
		else {
			// lock tables
			$query = "LOCK TABLES lasku WRITE, tapahtuma WRITE, tiliointi WRITE, tilausrivi WRITE, tilausrivi as tilausrivi2 WRITE, sanakirja WRITE, tilausrivi as tilausrivi_osto READ, tuote READ, sarjanumeroseuranta WRITE, tuotepaikat WRITE, tilausrivin_lisatiedot WRITE, avainsana as avainsana_kieli READ";
			$locre = mysql_query($query) or pupe_error($query);

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and alatila	= 'C'
						and tila 	= 'S'
						and tunnus 	= '$tilausnumero'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				//Haetaan jatkojalostettavat tuotteet
				$query    = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno	and tuote.sarjanumeroseuranta != ''
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								and tilausrivi.otunnus='$tilausnumero'";
				$jres = mysql_query($query) or pupe_error($query);

				while($srow1 = mysql_fetch_array($jres)) {
					$query = "	SELECT count(distinct sarjanumero) kpl
								FROM sarjanumeroseuranta
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno 			= '$srow1[tuoteno]'
								and siirtorivitunnus 	= '$srow1[tunnus]'";
					$sarjares2 = mysql_query($query) or pupe_error($query);
					$srow2 = mysql_fetch_array($sarjares2);

					if ($srow2["kpl"] != abs($srow1["varattu"])) {
						echo t("Tilaukselta puuttuu sarjanumeroita, ei voida merkata valmiiksi").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";

						require ("../inc/footer.inc");
						exit;
					}
				}

				$query = "	UPDATE lasku
							SET alatila = 'X'
							WHERE yhtio = '$kukarow[yhtio]' and alatila='C' and tila = 'S' and tunnus = '$tilausnumero'";
				$result = mysql_query($query) or pupe_error($query);

				//Haetaan jatkojalostettavat tuotteet
				$query    = "	SELECT tilausrivi.tunnus, tilausrivi.varattu varattu,
								tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								and tilausrivi.otunnus='$tilausnumero'
								and tilausrivi.perheid2=tilausrivi.tunnus";
				$jres = mysql_query($query) or pupe_error($query);

				while($srow = mysql_fetch_array($jres)) {

					//Jotta saadaan oma kulukeikka per laite
					unset($otunnus);

					//Haetaan jatkojalostettavan tuotteen ostorivitunnus
					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tuoteno			 = '$srow[tuoteno]'
								and siirtorivitunnus = '$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					// Jos samalla ostorivill� on useita kappaleita niin meid�n on splitattava ostorivi osiin
					$query = "	SELECT *
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$sarjarow[ostorivitunnus]'";
					$monista = mysql_query($query) or pupe_error($query);
					$ostorow = mysql_fetch_array($monista);

					if ($ostorow["kpl"] > 1) {
						//Haetaan alkuper�inen ostorivi ja sen tehdaslis�varusteet
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
						$monistares2 = mysql_query($query) or pupe_error($query);

						while ($ostokokorow = mysql_fetch_array($monistares2)) {

							$maara = round($ostokokorow["kpl"] / $ostorow["kpl"],2);

							if ($maara > 0) {

								$kplhinta  		= ($ostokokorow["rivihinta"] / $ostokokorow["kpl"]);
								$rivihinta 		= round(($ostokokorow["kpl"]-$maara) * $kplhinta, $yhtiorow['hintapyoristys']);
								$koprivihinta 	= round($maara * $kplhinta, $yhtiorow['hintapyoristys']);

								// V�hennet��n alkuper�isen ostorivin m��r�� yhdell�
								$query = "	UPDATE tilausrivi
											SET kpl=kpl-$maara, tilkpl=tilkpl-$maara, rivihinta=$rivihinta
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus  = '$ostokokorow[tunnus]'";
								$insres2 = mysql_query($query) or pupe_error($query);

								//Kopioidaan tilausrivi
								$kysely = "	INSERT INTO tilausrivi SET ";

								for($i=0; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta
									switch (mysql_field_name($monistares2,$i)) {
										case 'kpl':
										case 'tilkpl':
											$kysely .= mysql_field_name($monistares2,$i)."='$maara',";
											break;
										case 'rivihinta':
											$kysely .= mysql_field_name($monistares2,$i)."='$koprivihinta',";
											break;
										default:
											$kysely .= mysql_field_name($monistares2,$i)."='".$ostokokorow[$i]."',";
									}
								}

								$kysely  = substr($kysely, 0, -1);
								$insres2 = mysql_query($kysely) or pupe_error($kysely);
								$insid   = mysql_insert_id();

								//Haetaan alkuper�isen ostorivin tapahtuma
								$query = "	SELECT *
											FROM tapahtuma
											WHERE yhtio 	= '$kukarow[yhtio]'
											and rivitunnus  = '$ostokokorow[tunnus]'";
								$tapares = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($tapares) == 1) {
									$taparow = mysql_fetch_array($tapares);

									// V�hennet��n alkuper�isen ostorivin tapahtuman m��r�� yhdell�
									$query = "	UPDATE tapahtuma
												SET kpl=kpl-$maara
												WHERE yhtio 	= '$kukarow[yhtio]'
												and rivitunnus  = '$ostokokorow[tunnus]'";
									$insres2 = mysql_query($query) or pupe_error($query);

									//Kopioidaan tapahtuma
									$kysely = "	INSERT INTO tapahtuma SET ";

									for($i=0; $i < mysql_num_fields($tapares)-1; $i++) { // Ei monisteta tunnusta
										switch (mysql_field_name($tapares,$i)) {
											case 'kpl':
												$kysely .= mysql_field_name($tapares,$i)."='$maara',";
												break;
											case 'rivitunnus':
												$kysely .= mysql_field_name($tapares,$i)."='$insid',";
												break;
											default:
												$kysely .= mysql_field_name($tapares,$i)."='".$taparow[$i]."',";
										}
									}

									$kysely  = substr($kysely, 0, -1);
									$insres2 = mysql_query($kysely) or pupe_error($kysely);
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
									$insres2 = mysql_query($query) or pupe_error($query);
								}

								// Siirret��n jatkojalostettavan tuotteen sarjanumero uudelle tilausriville
								if ($ostokokorow["tunnus"] == $ostorow["tunnus"]) {
									$query = "	UPDATE sarjanumeroseuranta
												SET ostorivitunnus = $insid
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus  = '$sarjarow[tunnus]'";
									$insres2 = mysql_query($query) or pupe_error($query);

									$sarjarow["ostorivitunnus"] = $insid;
								}
							}
						}
					}

					// Haetaan liitetyt lis�varusteet ja ty�kulut
					$query    = "	SELECT tilausrivi.*, tuote.ei_saldoa, tuote.kehahin, tuote.myyntihinta, tuote.alv, tuote.sarjanumeroseuranta, tilausrivi.tunnus as rivitunnus
									FROM tilausrivi
									JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
									WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
									and tilausrivi.otunnus	= '$tilausnumero'
									and tilausrivi.perheid2	= '$srow[tunnus]'
									and tilausrivi.tunnus  != '$srow[tunnus]'";
					$kres = mysql_query($query) or pupe_error($query);

					while ($lisarow = mysql_fetch_array($kres)) {
						$ostohinta 			  = 0;
						$ostorivinsarjanumero = 0;

						if ($lisarow["sarjanumeroseuranta"] == "S") {
							//Hateaan lis�varusteen ostohinta
							$query = "	SELECT round(sum(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl),2) ostohinta, group_concat(sarjanumeroseuranta.tunnus) tunnus
										FROM sarjanumeroseuranta
										JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
										WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno = '$lisarow[tuoteno]'
										and sarjanumeroseuranta.siirtorivitunnus = '$lisarow[rivitunnus]'";
							$sarjares = mysql_query($query) or pupe_error($query);
							$sarjarow2 = mysql_fetch_array($sarjares);

							$ostohinta 				= $sarjarow2["ostohinta"];
							$ostorivinsarjanumero	= $sarjarow2["tunnus"];
						}
						else {
							// Lis�varuste on bulkkikamaa
							$ostohinta = $lisarow["kehahin"];
						}

						$ostohinta = (float) $ostohinta;

						// Ruuvataan liitetyt lis�varusteet kiinni laitteen alkuper�iseen ostoriviin
						// Jos lis�varuste on sarjanumeroitava, niin sarjanumeron tunnus menee
						// lis�varusterivin sistyomaarays_sarjatunnus-kent�ss�
						$query = "	UPDATE tilausrivi
									SET perheid2	= '$sarjarow[ostorivitunnus]',
									rivihinta		= round(varattu*$ostohinta,2),
									toimitettu		= '$kukarow[kuka]',
									toimitettuaika 	= now(),
									tilkpl			= varattu,
									varattu 		= 0
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$lisarow[rivitunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						$query = "	UPDATE tilausrivin_lisatiedot
									SET sistyomaarays_sarjatunnus = '$ostorivinsarjanumero'
									WHERE yhtio 			= '$kukarow[yhtio]'
									and tilausrivitunnus  	= '$lisarow[rivitunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						if ($lisarow["ei_saldoa"] == '') {
							//Siirret��n lis�varuste samaan paikkaan ku is�tuote ja varataan lis�varusteen saldoa
							if($lisarow["hyllyalue"] != $srow["hyllyalue"] or $lisarow["hyllynro"] != $srow["hyllynro"] or $lisarow["hyllyvali"] != $srow["hyllyvali"] or $lisarow["hyllytaso"] != $srow["hyllytaso"]) {
								// Tehd��n saldosiirto

								// Katsotaan l�ytyyk� jatkojalostettavan tuotteen paikka my�s lis�varusteelta
								$query = "	SELECT *
											FROM tuotepaikat
											WHERE tuoteno 	= '$lisarow[tuoteno]'
											and yhtio 		= '$kukarow[yhtio]'
											and hyllyalue	= '$srow[hyllyalue]'
											and hyllynro 	= '$srow[hyllynro]'
											and hyllyvali 	= '$srow[hyllyvali]'
											and hyllytaso	= '$srow[hyllytaso]'";
								$sarjares = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($sarjares) == 0) {
									// Lis�t��n tuotepaikkoja
									$query = "	INSERT INTO tuotepaikat
												SET tuoteno = '$lisarow[tuoteno]',
												yhtio 		= '$kukarow[yhtio]',
												hyllyalue	= '$srow[hyllyalue]',
												hyllynro 	= '$srow[hyllynro]',
												hyllyvali 	= '$srow[hyllyvali]',
												hyllytaso	= '$srow[hyllytaso]'";
									$sarjares = mysql_query($query) or pupe_error($query);
									$minne  = mysql_insert_id();

									$query = "	SELECT *
												FROM tuotepaikat
												WHERE tuoteno 	= '$lisarow[tuoteno]'
												and yhtio 		= '$kukarow[yhtio]'
												and tunnus		= '$minne'";
									$sarjares = mysql_query($query) or pupe_error($query);
									$minnerow  = mysql_fetch_array($sarjares);

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
												selite 		= '".t("Lis�ttiin tuotepaikka")." $srow[hyllyalue] $srow[hyllynro] $srow[hyllyvali] $srow[hyllytaso]. ".t("Sis�inen ty�m��r�ys")."',
												laatija 	= '$kukarow[kuka]',
												laadittu 	= now()";
									$sarjares = mysql_query($query) or pupe_error($query);
								}
								else {
									$minnerow  = mysql_fetch_array($sarjares);
								}

								$kehahin_query = "	SELECT kehahin
													FROM tuote
													WHERE yhtio = '$kukarow[yhtio]'
													and tuoteno = '$lisarow[tuoteno]'";
								$kehahin_result = mysql_query($kehahin_query) or pupe_error($kehahin_query);
								$kehahin_row = mysql_fetch_array($kehahin_result);

								// V�hennet��n
								$query = "	UPDATE tuotepaikat
											SET saldo = saldo - $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$lisarow[hyllyalue]'
											and hyllynro  	= '$lisarow[hyllynro]'
											and hyllyvali 	= '$lisarow[hyllyvali]'
											and hyllytaso 	= '$lisarow[hyllytaso]'
											LIMIT 1";
								$sarjares = mysql_query($query) or pupe_error($query);

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
											selite 		= '".t("Paikasta")." $lisarow[hyllyalue] $lisarow[hyllynro] $lisarow[hyllyvali] $lisarow[hyllytaso] ".t("v�hennettiin")." $lisarow[varattu]. ".t("Sis�inen ty�m��r�ys")."',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$sarjares = mysql_query($query) or pupe_error($query);

								//Lis�t��n
								$query = "	UPDATE tuotepaikat
											SET saldo = saldo + $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$minnerow[hyllyalue]'
											and hyllynro  	= '$minnerow[hyllynro]'
											and hyllyvali 	= '$minnerow[hyllyvali]'
											and hyllytaso 	= '$minnerow[hyllytaso]'
											LIMIT 1";
								$sarjares = mysql_query($query) or pupe_error($query);

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
											selite 		= '".t("Paikalle")." $minnerow[hyllyalue] $minnerow[hyllynro] $minnerow[hyllyvali] $minnerow[hyllytaso] ".t("lis�ttiin")." $lisarow[varattu]. ".t("Sis�inen ty�m��r�ys")."',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$sarjares = mysql_query($query) or pupe_error($query);

								// Varataan lis�varusteet
								$query = "	UPDATE tuotepaikat
											SET saldo_varattu = saldo_varattu + $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$minnerow[hyllyalue]'
											and hyllynro  	= '$minnerow[hyllynro]'
											and hyllyvali 	= '$minnerow[hyllyvali]'
											and hyllytaso 	= '$minnerow[hyllytaso]'
											LIMIT 1";
								$sarjares = mysql_query($query) or pupe_error($query);
							}
							else {
								// Varataan lis�varusteet
								$query = "	UPDATE tuotepaikat
											SET saldo_varattu = saldo_varattu + $lisarow[varattu]
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$lisarow[tuoteno]'
											and hyllyalue 	= '$lisarow[hyllyalue]'
											and hyllynro  	= '$lisarow[hyllynro]'
											and hyllyvali 	= '$lisarow[hyllyvali]'
											and hyllytaso 	= '$lisarow[hyllytaso]'
											LIMIT 1";
								$sarjares = mysql_query($query) or pupe_error($query);
							}

							if ($lisarow["sarjanumeroseuranta"] == "S") {
								//P�ivitet��n sarjanumeroiden varastopaikkatiedot
								$query = "	UPDATE sarjanumeroseuranta
											SET hyllyalue	= '$srow[hyllyalue]',
											hyllynro 		= '$srow[hyllynro]',
											hyllyvali 		= '$srow[hyllyvali]',
											hyllytaso		= '$srow[hyllytaso]'
											WHERE yhtio				= '$kukarow[yhtio]'
											and tuoteno				= '$lisarow[tuoteno]'
											and siirtorivitunnus	= '$lisarow[rivitunnus]'";
								$sarjares = mysql_query($query) or pupe_error($query);
							}
						}
						else {
							// Lis�varuste on ty�kulu

							// Ty�kulujen arvo
							// Alvihinta korjataan vain jos yhti�lla on verolliset myyntihinnat
							if ($lisarow["alv"] != '' and $yhtiorow["alv_kasittely"] == "" ) {
								$tyokulut = round($lisarow["varattu"] * $lisarow["myyntihinta"] / (1+$lisarow['alv']/100), 2);
							}
							else {
								$tyokulut = round($lisarow["varattu"] * $lisarow["myyntihinta"], 2);
							}

							if ($tyokulut != 0) {

								if (!isset($otunnus)) {
									// haetaan seuraava vapaa keikkaid
									$query  = "SELECT max(laskunro)+1 from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
									$result = mysql_query($query) or pupe_error($query);
									$row    = mysql_fetch_array($result);
									$id		= $row[0];

									$query = "	INSERT into lasku set
												yhtio        = '$kukarow[yhtio]',
												laskunro     = '$id',
												ytunnus	     = '$sarjarow[tunnus]',
												nimi         = 'Sis�inen ty�m��r�ys: $tilausnumero',
												liitostunnus = '$sarjarow[tunnus]',
												tila         = 'K',
												alatila      = 'S',
												luontiaika	 = now(),
												laatija		 = '$kukarow[kuka]'";
									$result = mysql_query($query) or pupe_error($query);
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
								$result = mysql_query($query) or pupe_error($query);
								$laskuid = mysql_insert_id($link);

								$query = "INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varasto]',
											kustp    = 0,
											tapvm    = now(),
											summa    = '$tyokulut',
											vero     = 0,
											lukko    = '',
											selite   = 'Varastonmuutos sis�inen ty�m��r�ys $tilausnumero: $srow[tuoteno] ($sarjarow[sarjanumero])',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = mysql_query($query) or pupe_error($query);

								$query = "INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varastonmuutos_valmistuksesta]',
											kustp    = 0,
											tapvm    = now(),
											summa    = $tyokulut * -1,
											vero     = 0,
											lukko    = '',
											selite   = 'Varastonmuutos sis�inen ty�m��r�ys $tilausnumero: $srow[tuoteno] ($sarjarow[sarjanumero])',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = mysql_query($query) or pupe_error($query);
							}
						}

						//P�ivitet��n varmuuden vuoksi alkuper�isen ostorivin perheid2 (se voi olla nolla)
						$query = "	UPDATE tilausrivi
									SET perheid2 = '$sarjarow[ostorivitunnus]'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$sarjarow[ostorivitunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						if ($lisarow["sarjanumeroseuranta"] != '') {
							//Varataan sarjanumero jatkojalostettavalle tuotteelle
							$query = "	UPDATE sarjanumeroseuranta
										SET siirtorivitunnus 	= $sarjarow[ostorivitunnus]
										WHERE yhtio				= '$kukarow[yhtio]'
										and tuoteno				= '$lisarow[tuoteno]'
										and siirtorivitunnus	= '$lisarow[rivitunnus]'";
							$sarjares = mysql_query($query) or pupe_error($query);
						}
					}

					//Irroitetaan jatkojalostettavan tuotteen sarjanumero
					$query = "	UPDATE sarjanumeroseuranta
								SET siirtorivitunnus = 0
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);

					//P�ivitet��n jatkojalostettava rivi valmiiksi
					$query = "	UPDATE tilausrivi
								SET toimitettu='$kukarow[kuka]', toimitettuaika = now(), tilkpl=varattu, varattu = 0
								WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}

				echo "<font class='message'>".t("Sis�inen ty�m��r�ys merkattiin valmiiksi")."!</font><br><br>";
				$tee = "";
			}
			else {
				echo "<font class='error'>".t("VIRHE: Sis�inen ty�m��r�ys on v��r�ss� tilassa")."!</font><br><br>";
				$tee = "";
			}

			$query = "UNLOCK TABLES";
			$locre = mysql_query($query) or pupe_error($query);
		}
	}


	if ($tee == "") {

		// N�ytet��n muuten vaan sopivia tilauksia
		echo "<br><form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<font class='head'>".t("Etsi sis�inen ty�m��r�ys").":<hr></font>
				".t("Sy�t� tilausnumero, nimen tai laatijan osa").":
				<input type='text' name='etsi'>
				<input type='Submit' value = '".t("Etsi")."'>
				</form>";

		// pvm 30 pv taaksep�in
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		$haku='';
		if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
		if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila='C'
					$haku
					order by luontiaika desc";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {

			echo "<table>";

			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th align='left'>".t("tyyppi")."</th></tr>";

			while ($row = mysql_fetch_array($result)) {

				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
					echo "<td>$row[$i]</td>";
				}

				$laskutyyppi=$row["tila"];
				$alatila=$row["alatila"];

				//tehd��n selv�kielinen tila/alatila
				require ("inc/laskutyyppi.inc");

				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

				echo "<td class='back'>
						<form method='post' action='tilaus_myynti.php'>
						<input type='hidden' name='toim' value='SIIRTOTYOMAARAYS'>
						<input type='hidden' name='tee' value='AKTIVOI'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>
						<input type='submit' value='".t("Muokkaa")."'>
						</form>
						</td>";

				echo "<td class='back'>
						<form method='post' action='$PHP_SELF'>
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
				echo "<th>".t("Tilausten arvo yhteens�")." ($sumrow[kpl] ".t("kpl")."): </th>";
				echo "<td>$sumrow[arvo] $yhtiorow[valkoodi]</td>";
				echo "</tr></table>";
			}

		}
		else {
			echo t("Ei tilauksia")."...";
		}
	}
	require ("../inc/footer.inc");
?>
