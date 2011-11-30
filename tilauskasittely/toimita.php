<?php

	require ("../inc/parametrit.inc");

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	echo "<font class='head'>".t("Toimita tilaus").":</font><hr>";

	$query_ale_lisa = generoi_alekentta('M');

	if ($tee == 'P' and $maksutapa == 'seka') {
		$query_maksuehto = "SELECT *
							FROM maksuehto
							WHERE yhtio='$kukarow[yhtio]'
							and kateinen != ''
							and kaytossa = ''
							and (sallitut_maat = '' or sallitut_maat like '%$maa%')";
		$maksuehtores = pupe_query($query_maksuehto);
		$maksuehtorow = mysql_fetch_array($maksuehtores);

		echo "<table><form action='' name='laskuri' method='post'>";

		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<input type='hidden' name='tee' value='P'>";
		echo "<input type='hidden' name='kassalipas' value='$kassalipas'>";
		echo "<input type='hidden' name='vaihdakateista' value='$vaihdakateista'>";
		echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";

		echo "	<script type='text/javascript' language='JavaScript'>
				<!--
					function update_summa(rivihinta) {

						kateinen = Number(document.getElementById('kateismaksu').value.replace(\",\",\".\"));
						pankki = Number(document.getElementById('pankkikortti').value.replace(\",\",\".\"));
						luotto = Number(document.getElementById('luottokortti').value.replace(\",\",\".\"));

						summa = rivihinta - (kateinen + pankki + luotto);

						summa = Math.round(summa*100)/100;

						if (summa == 0 && (document.getElementById('kateismaksu').value != '' || document.getElementById('pankkikortti').value != '' || document.getElementById('luottokortti').value != '')) {
							summa = 0.00;
							document.getElementById('hyvaksy_nappi').disabled = false;
						} else {
							document.getElementById('hyvaksy_nappi').disabled = true;
						}

						document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>';
					}
				-->
				</script>";

		echo "<tr><th>".t("Laskun loppusumma")."</th><td align='right'>$rivihinta</td><td>$valkoodi</td></tr>";

		echo "<tr><td>".t("K�teisell�")."</td><td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";
		echo "<tr><td>".t("Pankkikortilla")."</td><td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";
		echo "<tr><td>".t("Luottokortilla")."</td><td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";

		echo "<tr><th>".t("Erotus")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$valkoodi</td></tr>";
		echo "<tr><td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyv�ksy")."' disabled></td></tr>";

		echo "</form><br><br>";

		$formi = "laskuri";
		$kentta = "kateismaksu";

		exit;
	}

	if ($tee == 'maksu') {
		if ($seka == '') {
			$tee == 'P';
		}
	}

	if ($tee=='P') {

		// jos kyseess� ei ole nouto tai noutajan nimi on annettu, voidaan merkata tilaus toimitetuksi..
		if (($nouto != 'yes') or ($noutaja != '')) {
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
						WHERE otunnus 	= '$otunnus'
						and var not in ('P','J')
						and yhtio 		= '$kukarow[yhtio]'
						and keratty    != ''
						and toimitettu  = ''
						and tyyppi 		= 'L'";
			$result = pupe_query($query);

			if (isset($vaihdakateista) and $vaihdakateista == "KYLLA") {
				$katlisa = ", kassalipas = '$kassalipas', maksuehto = '$maksutapa'";
			}
			else {
				$katlisa = "";
			}

			$query = "	UPDATE lasku
						set alatila = 'D',
						noutaja = '$noutaja'
						$katlisa
						WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
			$result = pupe_query($query);

			// jos kyseess� on k�teismyynti�, tulostetaaan k�teislasku
			$query  = "	SELECT *
						from lasku, maksuehto
						where lasku.tunnus = '$otunnus'
						and lasku.yhtio	= '$kukarow[yhtio]'
						and maksuehto.yhtio = lasku.yhtio
						and maksuehto.tunnus = lasku.maksuehto";
			$result = pupe_query($query);
			$tilrow = mysql_fetch_array($result);

			// Etuk�teen maksetut tilaukset pit�� muuttaa takaisin "maksettu"-tilaan
			$query = "	UPDATE lasku SET
						alatila = 'X'
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$otunnus'
						AND mapvm != '0000-00-00'
						AND chn = '999'";
			$ures  = pupe_query($query);

			// jos kyseess� on k�teiskauppaa ja EI vienti�, laskutetaan ja tulostetaan tilaus..
			if ($tilrow['kateinen']!='' and $tilrow["vienti"]=='') {

				//tulostetaan k�teislasku...
				$laskutettavat	= $otunnus;
				$tee 			= "TARKISTA";
				$laskutakaikki 	= "KYLLA";
				$silent		 	= "KYLLA";

				if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == "") {
					$valittu_tulostin = $kukarow["kirjoitin"];
				}
				elseif($valittu_tulostin == "") {
					$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
				}

				require ("verkkolasku.php");
			}

			//Tulostetaan uusi l�hete jos k�ytt�j� valitsi drop-downista printterin
			//Paitsi jos tilauksen tila p�ivitettiin sellaiseksi, ett� l�hetett� ei kuulu tulostaa
			$query = "	SELECT *
						FROM lasku
						WHERE tunnus in ($otunnus)
						and yhtio = '$kukarow[yhtio]'";
			$lasresult = pupe_query($query);

			while($laskurow = mysql_fetch_array($lasresult)) {

				//tulostetaan faili ja valitaan sopivat printterit
				if ($laskurow["varasto"] == '') {
					$query = "	SELECT *
								from varastopaikat
								where yhtio = '$kukarow[yhtio]'
								order by alkuhyllyalue,alkuhyllynro
								limit 1";
				}
				else {
					$query = "	SELECT *
								from varastopaikat
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$laskurow[varasto]'
								order by alkuhyllyalue,alkuhyllynro";
				}
				$prires = pupe_query($query);

				if (mysql_num_rows($prires) > 0) {

					$prirow= mysql_fetch_array($prires);

					// k�teinen muuttuja viritet��n tilaus-valmis.inc:iss� jos maksuehto on k�teinen
					// ja silloin pit�� kaikki l�hetteet tulostaa aina printteri5:lle (lasku printteri)
					if ($kateinen == 'X') {
						$apuprintteri = $prirow['printteri5']; // laskuprintteri
					}
					else {
						if ($valittu_tulostin == "oletukselle") {
							$apuprintteri = $prirow['printteri1']; // l�heteprintteri
						}
						else {
							$apuprintteri = $valittu_tulostin;
						}
					}

					//haetaan l�hetteen tulostuskomento
					$query   = "SELECT * FROM kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$apuprintteri'";
					$kirres  = pupe_query($query);
					$kirrow  = mysql_fetch_array($kirres);
					$komento = $kirrow['komento'];


					if ($valittu_oslapp_tulostin == "oletukselle") {
						$apuprintteri = $prirow['printteri3']; // osoitelappuprintteri
					}
					else {
						$apuprintteri = $valittu_oslapp_tulostin;
					}

					//haetaan osoitelapun tulostuskomento
					$query  = "SELECT * FROM kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$apuprintteri'";
					$kirres = pupe_query($query);
					$kirrow = mysql_fetch_array($kirres);
					$oslapp = $kirrow['komento'];
				}

				if ($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) {

					$otunnus = $laskurow["tunnus"];

					//hatetaan asiakkaan l�hetetyyppi
					$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro != '', asiakasnro, ytunnus) asiakasnro
								FROM asiakas
								WHERE tunnus='$tilrow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
					$result = pupe_query($query);
					$asrow = mysql_fetch_array($result);

					$lahetetyyppi = "";

					if ($sellahetetyyppi != '') {
						$lahetetyyppi = $sellahetetyyppi;
					}
					elseif ($asrow["lahetetyyppi"] != '') {
						$lahetetyyppi = $asrow["lahetetyyppi"];
					}
					else {
						//Haetaan yhti�n oletusl�hetetyyppi
						$query = "  SELECT selite
									FROM avainsana
									WHERE yhtio = '$kukarow[yhtio]'
									AND laji = 'LAHETETYYPPI'
									ORDER BY jarjestys, selite
									LIMIT 1";
						$vres = pupe_query($query);
						$vrow = mysql_fetch_array($vres);

						if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
							$lahetetyyppi = $vrow["selite"];
						}
					}

					require("tulosta_lahete.inc");

					//	Jos meill� on funktio tulosta_lahete meill� on suora funktio joka hoitaa koko tulostuksen
					if (function_exists("tulosta_lahete")) {
						if($vrow["selite"] != '') {
							$tulostusversio = $vrow["selite"];
						}
						else {
							$tulostusversio = $asrow["lahetetyyppi"];
						}

						tulosta_lahete($otunnus, $komento["L�hete"], $kieli = "", $toim, $tee, $tulostusversio);
					}
					else {
						// katotaan miten halutaan sortattavan
						// haetaan asiakkaan tietojen takaa sorttaustiedot
						$order_sorttaus = '';

						$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
												FROM asiakas
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus = '$laskurow[liitostunnus]'";
						$asiakas_apu_res = pupe_query($asiakas_apu_query);

						if (mysql_num_rows($asiakas_apu_res) == 1) {
							$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
							$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
							$order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
						}
						else {
							$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
							$order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
						}

						if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
						else $pjat_sortlisa = "";

						if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
							$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
						}
						else {
							$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
						}

						//generoidaan l�hetteelle ja ker�yslistalle rivinumerot
						$query = "  SELECT tilausrivi.*,
									round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
									round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
									$sorttauskentta,
									if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
									if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
									if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
									tuote.sarjanumeroseuranta
									FROM tilausrivi
									JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
									JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
									LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
									WHERE tilausrivi.otunnus = '$otunnus'
									and tilausrivi.yhtio = '$kukarow[yhtio]'
									$tyyppilisa
									and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
									ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
						$riresult = pupe_query($query);

						$params_lahete = array(
						'arvo'						=> 0,
						'asrow'						=> $asrow,
						'boldi'						=> $boldi,
						'ei_otsikoita'				=> '',
						'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
						'iso'						=> $iso,
						'jtid'						=> '',
						'kala'						=> 0,
						'kassa_ale'					=> '',
						'kieli'						=> $kieli,
						'lahetetyyppi'				=> $lahetetyyppi,
						'laskurow'					=> $laskurow,
						'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
						'norm'						=> $norm,
						'page'						=> NULL,
						'pdf'						=> NULL,
						'perheid'					=> 0,
						'pieni'						=> $pieni,
						'pieni_boldi'				=> $pieni_boldi,
						'pitkattuotteet'			=> FALSE,
						'rectparam'					=> $rectparam,
						'riviresult'				=> $riresult,
						'rivinkorkeus'				=> $rivinkorkeus,
						'rivinumerot'				=> "",
						'row'						=> NULL,
						'sivu'						=> 1,
						'summa'						=> 0,
						'tee'						=> $tee,
						'thispage'					=> NULL,
						'toim'						=> $toim,
						'tots'						=> 0,
						'tuotenopituus'				=> '',
						'nimityskohta'				=> '',
						'nimitysleveys'				=> '',
						'tyyppi'					=> '',
						'useita'					=> '',
						);

						if ($laskurow["tila"] == "G") {
							$params_lahete["tyyppi"] = "SIIRTOLISTA";
						}

						// Aloitellaan l�hetteen teko
						$params_lahete = alku_lahete($params_lahete);

						// Piirret��n rivit
						mysql_data_seek($riresult,0);

						while ($row = mysql_fetch_assoc($riresult)) {
							$params_lahete["row"] = $row;
							$params_lahete = rivi_lahete($params_lahete);
						}

						//Haetaan erikseen toimitettavat tuotteet
						if ($laskurow["vanhatunnus"] != 0) {
							$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
										FROM lasku use index (yhtio_vanhatunnus)
										WHERE yhtio		= '$kukarow[yhtio]'
										and vanhatunnus = '$laskurow[vanhatunnus]'
										and tunnus != '$laskurow[tunnus]'";
							$perheresult = pupe_query($query);
							$tunrow = mysql_fetch_assoc($perheresult);

							//generoidaan l�hetteelle ja ker�yslistalle rivinumerot
							if ($tunrow["tunnukset"] != "") {

								$toimitettulisa = "";

								if ($laskurow["clearing"] == "ENNAKKOTILAUS" or $laskurow["clearing"] == "JT-TILAUS") {
									$toimitettulisa = " and tilausrivi.toimitettu = '' ";
								}

								$query = "  SELECT tilausrivi.*,
											round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
											round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
											$sorttauskentta,
											if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
											if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
											if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
											tuote.sarjanumeroseuranta
											FROM tilausrivi
											JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
											JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
											LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
											WHERE tilausrivi.otunnus in ('$tunrow[tunnukset]')
											and tilausrivi.yhtio = '$kukarow[yhtio]'
											$tyyppilisa
											$toimitettulisa
											and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
											ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
								$riresult = pupe_query($query);

								while ($row = mysql_fetch_assoc($riresult)) {

									if ($row['toimitettu'] == '') {
										$row['kommentti'] .= "\n******* ".t("Toimitetaan erikseen",$kieli).". *******";
									}
									else {
										$row['kommentti'] .= "\n******* ".t("Toimitettu erikseen tilauksella",$kieli)." ".$row['otunnus'].". *******";
									}

									$row['rivihinta'] 	= "";
									$row['varattu'] 	= "";
									$row['kpl']			= "";
									$row['jt'] 			= "";
									$row['d_erikseen'] 	= "JOO";

									$params_lahete["row"] = $row;
									$params_lahete = rivi_lahete($params_lahete);
								}
							}
						}

						// Loppulaatikot
						$params_lahete["tots"] = 1;
						$params_lahete = loppu_lahete($params_lahete);

						//katotaan onko laskutus nouto
						$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
									FROM lasku
									JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
									JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
									WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
									and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
						$kures = pupe_query($query);

						if (mysql_num_rows($kures) > 0 and $yhtiorow["lahete_nouto_allekirjoitus"] != "") {
							$params_lahete = kuittaus_lahete($params_lahete);
						}

						//tulostetaan sivu
						if ($lahetekpl > 1 and $komento != "email") {
							$komento .= " -#$lahetekpl ";
						}

						$params_lahete["komento"] = $komento;

						//tulostetaan sivu
						print_pdf_lahete($params_lahete);
					}
				}
			}

			echo t("Tilaus toimitettu")."!<br><br>";
			$id = 0;
		}
		else {
			$id = $otunnus;
			$virhe = "<font class='error'>".t("Noutajan nimi on sy�tett�v�")."!</font><br><br>";
		}
	}

	if ($id == '') $id = 0;

	// meill� ei ole valittua tilausta
	if ($id == '0') {
		$formi	= "find";
		$kentta	= "etsi";
		$boob 	= "";

		// tehd��n etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form><br><br>";

		$haku = '';
		if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

		$query = "	SELECT distinct otunnus
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != '')
					JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa and toimitustapa.nouto != '')
					where lasku.$logistiikka_yhtiolisa
					and lasku.tila = 'L'
					and lasku.alatila in ('C','B')
					and lasku.vienti = ''
					ORDER BY lasku.toimaika";
		$tilre = pupe_query($query);
		
		while ($tilrow = mysql_fetch_array($tilre)) {
			// etsit��n sopivia tilauksia
			$query = "	SELECT lasku.yhtio, lasku.yhtio_nimi, lasku.tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, maksuehto.teksti maksuehto, toimitustapa, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, lasku.laatija, toimaika
						FROM lasku
						LEFT JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio AND maksuehto.tunnus = lasku.maksuehto)
						WHERE lasku.tunnus = '$tilrow[otunnus]' 
						and lasku.tila = 'L' 
						$haku 
						and lasku.$logistiikka_yhtiolisa 
						and lasku.alatila in ('C','B')
						ORDER by laadittu desc";
			$result = pupe_query($query);

			//piirret��n taulukko...
			if (mysql_num_rows($result) != 0) {
				while ($row = mysql_fetch_array($result)) {
					// piirret��n vaan kerran taulukko-otsikot
					if ($boob == '') {
						$boob = 'kala';

						echo "<table>";
						echo "<tr>";
						for ($i=0; $i<mysql_num_fields($result); $i++) {
							if (mysql_field_name($result, $i) == 'yhtio_nimi') {
								if ($logistiikka_yhtio != '') {
									echo "<th align='left'>",t("Yhti�"),"</th>";
								}
							}
							elseif (mysql_field_name($result, $i) == 'yhtio') {
								// skipataan t��
							}
							else {
								echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
							}
						}
						echo "</tr>";
					}

					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result); $i++) {
						if (mysql_field_name($result,$i) == 'laadittu' or mysql_field_name($result,$i) == 'toimaika') {
							echo "<td>".tv1dateconv($row[$i])."</td>";
						}
						elseif (mysql_field_name($result, $i) == 'yhtio_nimi') {
							if ($logistiikka_yhtio != '') {
								echo "<td>$row[yhtio_nimi]</td>";
							}
						}
						elseif (mysql_field_name($result, $i) == 'yhtio') {
							// skipataan t��
						}
						else {
							echo "<td>$row[$i]</td>";
						}
					}

					echo "<form method='post' action='$PHP_SELF'><td class='back'>
						  <input type='hidden' name='id' value='$row[tilaus]'>
						  <input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
						  <input type='submit' name='tila' value='".t("Toimita")."'></td></tr></form>";
				}
			}
		}

		if ($boob != '')
			echo "</table>";
		else
			echo "<font class='message'>".t("Yht��n toimitettavaa tilausta ei l�ytynyt")."...</font>";
	}

	if ($id > 0) {
		$query = "	SELECT lasku.*,
					concat_ws(' ',lasku.nimi, nimitark) nimi,
					lasku.osoite,
					concat_ws(' ', lasku.postino, lasku.postitp) postitp,
					toim_osoite,
					concat_ws(' ', toim_postino, toim_postitp) toim_postitp,
					lasku.tunnus laskutunnus,
					lasku.liitostunnus,
					maksuehto.tunnus,
					maksuehto.teksti,
					maksuehto.kateinen
					FROM lasku
					JOIN maksuehto ON maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto
					WHERE lasku.tunnus	= '$id'
					and lasku.yhtio		= '$kukarow[yhtio]'
					and lasku.tila		= 'L'
					and lasku.alatila in ('C','B')";
		$result = pupe_query($query);

		if (mysql_num_rows($result)==0){
			die(t("Tilausta")." $id ".t("ei voida toimittaa, koska kaikkia tilauksen tietoja ei l�ydy!")."!");
		}

		$row = mysql_fetch_array($result);

		echo "<table>";
		echo "<tr><th>" . t("Tilaus") ."</th><td>$row[laskutunnus]</td></tr>";
		echo "<tr><th>" . t("Asiakas") ."</th><td>$row[nimi]<br>$row[toim_nimi]</td></tr>";
		echo "<tr><th>" . t("Ostajan osoite") ."</th><td>$row[osoite], $row[postitp]</td></tr>";
		echo "<tr><th>" . t("Toimitusosoite") ."</th><td>$row[toim_osoite], $row[toim_postitp]</td></tr>";
		echo "<tr><th>" . t("Maksuehto") ."</th><td>".t_tunnus_avainsanat($row, "teksti", "MAKSUEHTOKV")."</td></tr>";
		echo "<tr><th>" . t("Toimitustapa") ."</th><td>$row[toimitustapa]</td></tr>";
		echo "</table><br><br>";

		if ($row["valkoodi"] != '' and trim(strtoupper($row["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $row["vienti_kurssi"] != 0) {
			$hinta_riv = "(tilausrivi.hinta/$row[vienti_kurssi])";
		}
		else {
			$hinta_riv = "tilausrivi.hinta";
		}

		$lisa = " 	round($hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},$yhtiorow[hintapyoristys]) rivihinta,
					(tilausrivi.varattu+tilausrivi.kpl) kpl ";

		$query = "	SELECT concat_ws(' ',hyllyalue, hyllynro, hyllytaso, hyllyvali) varastopaikka, concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno, varattu, concat_ws('@',keratty,kerattyaika) keratty, tilausrivi.tunnus, var, $lisa
					FROM tilausrivi, tuote
					WHERE tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and var!='J' and otunnus = '$id' and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY varastopaikka";

		$result = pupe_query($query);
		$riveja = mysql_num_rows($result);

		echo "	<table>
				<tr>
				<th>".t("Varastopaikka")."</th>
				<th>".t("Tuoteno")."</th>
				<th>".t("M��r�")."</th>
				<th>".t("Ker�tty")."</th>
				</tr>";

		$rivihinta = "";

		$query = "	SELECT laskunsummapyoristys
					FROM asiakas
					WHERE tunnus='$row[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$asres = pupe_query($query);
		$asrow = mysql_fetch_array($asres);

		$summa = "";

		while($rivi = mysql_fetch_array($result)) {

			$summa = $rivi["rivihinta"];

			//K�sin sy�tetty summa johon lasku py�ristet��n
			if (abs($row["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
				$summa = sprintf("%.2f",$row["hinta"]);
			}

			//Jos laskun loppusumma py�ristet��n l�himp��n tasalukuun
			if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
				$summa = sprintf("%.2f",round($summa ,0));
			}

			$rivihinta += $summa;

			if ($rivi['var']=='P') $rivi['varattu']=t("*puute*");

			echo "<tr><td>$rivi[varastopaikka]</td>
					<td>$rivi[tuoteno]</td>
					<td>$rivi[varattu]</td>
					<td>$rivi[keratty]</td>
					</tr>";
		}

		echo "</table><br>";

		$query = "SELECT * FROM toimitustapa WHERE yhtio='$kukarow[yhtio]' AND selite='$row[toimitustapa]'";
		$tores = pupe_query($query);
		$toita = mysql_fetch_array($tores);

		echo "<form name = 'rivit' method='post' action='$PHP_SELF'>
				<input type='hidden' name='otunnus' value='$id'>
				<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
				<input type='hidden' name='tee' value='P'>";

		echo "<table>";

		if ($toita['nouto'] != '' and $row['kateinen'] != '' and $row["chn"] != '999' and ($row["mapvm"] == "" or $row["mapvm"] == '0000-00-00')) {

			echo "<tr><th>".t("Valitse kassalipas")."</th><td>";

			$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]'";
			$kassares = pupe_query($query);

			$sel = "";

			echo "<input type='hidden' name='noutaja' value=''>";
			echo "<input type='hidden' name='rivihinta' value='$rivihinta'>";
			echo "<input type='hidden' name='valkoodi' value='$row[valkoodi]'>";
			echo "<input type='hidden' name='maa' value='$row[maa]'>";
			echo "<input type='hidden' name='vaihdakateista' value='KYLLA'>";
			echo "<select name='kassalipas'>";
			echo "<option value=''>".t("Ei kassalipasta")."</option>";

			while ($kassarow = mysql_fetch_array($kassares)) {
				if ($kukarow["kassamyyja"] == $kassarow["tunnus"]) {
					$sel = "selected";
				}
				elseif ($kassalipas == $kassarow["tunnus"]) {
					$sel = "selected";
				}

				echo "<option value='$kassarow[tunnus]' $sel>$kassarow[nimi]</option>";

				$sel = "";
			}
			echo "</select></td></tr>";

			$query_maksuehto = "SELECT *
								FROM maksuehto
								WHERE yhtio='$kukarow[yhtio]'
								and kateinen != ''
								and kaytossa = ''
								and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$row[maa]%')
								ORDER BY tunnus";
			$maksuehtores = pupe_query($query_maksuehto);

			if (mysql_num_rows($maksuehtores) > 1) {
				echo "<tr><th>".t("Maksutapa")."</th><td>";

				echo "<select name='maksutapa'>";

				while ($maksuehtorow = mysql_fetch_array($maksuehtores)) {
					$sel = "";
					if ($maksuehtorow["tunnus"] == $row["maksuehto"]) {
						$sel = "selected";
					}

					echo "<option value='$maksuehtorow[tunnus]' $sel>".t_tunnus_avainsanat($maksuehtorow, "teksti", "MAKSUEHTOKV", $kieli)."</option>";
				}

				echo "<option value='seka'>".t("Seka")."</option>";
				echo "</select></td></tr>";

			}
			else {
				$maksuehtorow = mysql_fetch_array($maksuehtores);
				echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
			}
		}

		if ($row["chn"] == '999' and $row["mapvm"] != "" and $row["mapvm"] != '0000-00-00') {
			echo "<tr><th>".t("Maksutapa")."</th><td><font class='error'>".t("Tilaus on maksettu jo etuk�teen luottokortilla").".</font></td></tr>";
		}

		if (($toita['nouto'] !='' and $row['kateinen'] == '' ) or ($row["chn"] == '999' and $row["mapvm"] != "" and $row["mapvm"] != '0000-00-00')) {

			// jos kyseess� on nouto jota *EI* makseta k�teisell�, kysyt��n noutajan nime�..
			echo "<tr><th>".t("Sy�t� noutajan nimi")."</th>";
			echo "<td><input size='60' type='text' name='noutaja'></td></tr>";
			echo "<input type='hidden' name='nouto' value='yes'>";
			echo "<input type='hidden' name='kassalipas' value=''>";

			//kursorinohjausta
			$formi	= "rivit";
			$kentta	= "noutaja";
		}

		echo "<tr><th>".t("L�hete")."</th><td>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE
					yhtio = '$kukarow[yhtio]'
					ORDER by kirjoitin";
		$kirre = pupe_query($query);

		echo "<select name='valittu_tulostin'>";

		echo "<option value=''>".t("Ei tulosteta")."</option>";
		echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

		while ($kirrow = mysql_fetch_array($kirre)) {
			echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
		}

		echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'></td>";
		echo "</tr></table><br><br>";

		echo "$virhe";
		echo "<input type='submit' value='".t("Merkkaa toimitetuksi")."'></form>";
	}

	require ("../inc/footer.inc");

?>