<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Toimita tilaus").":</font><hr>";

	if ($tee == 'P' and $maksutapa == 'seka') {
		$query_maksuehto = " SELECT *
							 FROM maksuehto
							 WHERE yhtio='$kukarow[yhtio]' and kateinen != '' and kaytossa = '' and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$maa%')";
		$maksuehtores = mysql_query($query_maksuehto) or pupe_error($query_maksuehto);
		
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
		
		echo "<tr><td>".t("Käteisellä")."</td><td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";
		echo "<tr><td>".t("Pankkikortilla")."</td><td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";
		echo "<tr><td>".t("Luottokortilla")."</td><td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";

		echo "<tr><th>".t("Erotus")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$valkoodi</td></tr>";
		echo "<tr><td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyväksy")."' disabled></td></tr>";

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

	if($tee=='P') {
		
		// jos kyseessä ei ole nouto tai noutajan nimi on annettu, voidaan merkata tilaus toimitetuksi..
		if (($nouto != 'yes') or ($noutaja != '')) {
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]',
						toimitettuaika = now() 
						WHERE otunnus = '$otunnus' 
						and var not in ('P','J') 
						and yhtio = '$kukarow[yhtio]' 
						and keratty != ''
						and tyyppi = 'L'";
			$result = mysql_query($query) or pupe_error($query);

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
			$result = mysql_query($query) or pupe_error($query);

			// jos kyseessä on käteismyyntiä, tulostetaaan käteislasku
			$query  = "SELECT * from lasku, maksuehto where lasku.tunnus='$otunnus' and lasku.yhtio='$kukarow[yhtio]' and maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto";
			$result = mysql_query($query) or pupe_error($query);
			$tilrow = mysql_fetch_array($result);

			// Etukäteen maksetut tilaukset pitää muuttaa takaisin "maksettu"-tilaan
			$query = "	UPDATE lasku SET
						alatila = 'X'
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$otunnus'
						AND mapvm != '0000-00-00'
						AND chn = '999'";
			$ures  = mysql_query($query) or pupe_error($query);

			// jos kyseessä on käteiskauppaa ja EI vientiä, laskutetaan ja tulostetaan tilaus..
			if ($tilrow['kateinen']!='' and $tilrow["vienti"]=='') {
				
				//tulostetaan käteislasku...				
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
			
			//Tulostetaan uusi lähete jos käyttäjä valitsi drop-downista printterin
			//Paitsi jos tilauksen tila päivitettiin sellaiseksi, että lähetettä ei kuulu tulostaa
			$query = "	SELECT *
						FROM lasku
						WHERE tunnus in ($otunnus)
						and yhtio = '$kukarow[yhtio]'";
			$lasresult = mysql_query($query) or pupe_error($query);

			while($laskurow = mysql_fetch_array($lasresult)) {
				
				//tulostetaan faili ja valitaan sopivat printterit
				if ($laskurow["varasto"] == '') {
					$query = "	SELECT *
								from varastopaikat
								where yhtio='$kukarow[yhtio]'
								order by alkuhyllyalue,alkuhyllynro
								limit 1";
				}
				else {
					$query = "	SELECT *
								from varastopaikat
								where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
								order by alkuhyllyalue,alkuhyllynro";
				}
				$prires = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($prires) > 0) {

					$prirow= mysql_fetch_array($prires);

					// käteinen muuttuja viritetään tilaus-valmis.inc:issä jos maksuehto on käteinen
					// ja silloin pitää kaikki lähetteet tulostaa aina printteri5:lle (lasku printteri)
					if ($kateinen == 'X') {
						$apuprintteri = $prirow['printteri5']; // laskuprintteri
					}
					else {
						if ($valittu_tulostin == "oletukselle") {
							$apuprintteri = $prirow['printteri1']; // läheteprintteri
						}
						else {
							$apuprintteri = $valittu_tulostin;
						}
					}

					//haetaan lähetteen tulostuskomento
					$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
					$kirres  = mysql_query($query) or pupe_error($query);
					$kirrow  = mysql_fetch_array($kirres);
					$komento = $kirrow['komento'];


					if ($valittu_oslapp_tulostin == "oletukselle") {
						$apuprintteri = $prirow['printteri3']; // osoitelappuprintteri
					}
					else {
						$apuprintteri = $valittu_oslapp_tulostin;
					}

					//haetaan osoitelapun tulostuskomento
					$query  = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
					$kirres = mysql_query($query) or pupe_error($query);
					$kirrow = mysql_fetch_array($kirres);
					$oslapp = $kirrow['komento'];
				}

				if ($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) {

					$otunnus = $laskurow["tunnus"];

					//hatetaan asiakkaan lähetetyyppi
					$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
								FROM asiakas
								WHERE tunnus='$tilrow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
					$asrow = mysql_fetch_array($result);

					$lahetetyyppi = "";
					
					if ($sellahetetyyppi != '') {
						$lahetetyyppi = $sellahetetyyppi;
					}
					elseif ($asrow["lahetetyyppi"] != '') {
						$lahetetyyppi = $asrow["lahetetyyppi"];
					}
					else {
						//Haetaan yhtiön oletuslähetetyyppi
						$query = "  SELECT selite
									FROM avainsana
									WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
									ORDER BY jarjestys, selite
									LIMIT 1";
						$vres = mysql_query($query) or pupe_error($query);
						$vrow = mysql_fetch_array($vres);

						if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
							$lahetetyyppi = $vrow["selite"];
						}
					}

					$utuotteet_mukaan = 0;
					
					if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
						require_once ("tulosta_lahete_alalasku.inc");
					}
					elseif (strpos($lahetetyyppi,'simppeli') !== FALSE) {
						require_once ("$lahetetyyppi");
					}
					else {
						require_once ("tulosta_lahete.inc");
						$utuotteet_mukaan = 1;
					}

					//	Jos meillä on funktio tulosta_lahete meillä on suora funktio joka hoitaa koko tulostuksen
					if(function_exists("tulosta_lahete")) {
						if($vrow["selite"] != '') {
							$tulostusversio = $vrow["selite"];
						}
						else {
							$tulostusversio = $asrow["lahetetyyppi"];
						}

						tulosta_lahete($otunnus, $komento["Lähete"], $kieli = "", $toim, $tee, $tulostusversio);
					}
					else {
						// katotaan miten halutaan sortattavan
						// haetaan asiakkaan tietojen takaa sorttaustiedot
						$order_sorttaus = '';

						$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta 
												FROM asiakas 
												WHERE yhtio='$kukarow[yhtio]' 
												and tunnus='$laskurow[liitostunnus]'";
						$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

						if (mysql_num_rows($asiakas_apu_res) == 1) {
							$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
							$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"]);
							$order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"];
						}
						else {
							$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
							$order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
						}

						if($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
							$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
						}
						else {
							$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
						}

						//generoidaan lähetteelle ja keräyslistalle rivinumerot
						$query = "  SELECT tilausrivi.*,
									round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
									round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
									$sorttauskentta,
									if(tilausrivi.var='J', 1, 0) jtsort
									FROM tilausrivi
									JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
									JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
									LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
									WHERE tilausrivi.otunnus = '$otunnus'
									and tilausrivi.yhtio = '$kukarow[yhtio]'
									$tyyppilisa
									and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
									ORDER BY jtsort, sorttauskentta $order_sorttaus, tilausrivi.tunnus";
						$riresult = mysql_query($query) or pupe_error($query);

						//generoidaan rivinumerot
						$rivinumerot = array();

						while ($row = mysql_fetch_array($riresult)) {
							$rivinumerot[$row["tunnus"]] = $row["tunnus"];
						}

						sort($rivinumerot);

						$kal = 1;

						foreach($rivinumerot as $rivino) {
							$rivinumerot[$rivino] = $kal;
							$kal++;
						}

						mysql_data_seek($riresult,0);


						unset($pdf);
						unset($page);

						$sivu  = 1;
						$total = 0;
						$save_tyyppi =	$tyyppi;

						if ($laskurow["tila"] == "G") {
							$lah_tyyppi = "SIIRTOLISTA";
						}
						else {
							$lah_tyyppi = "";
						}

						// Aloitellaan lähetteen teko
						$page[$sivu] = alku($lah_tyyppi);

						while ($row = mysql_fetch_array($riresult)) {
							rivi($page[$sivu], $lah_tyyppi);

							$total+= $row["rivihinta"];
						}

						//Haetaan erikseen toimitettavat tuotteet
						if ($laskurow["vanhatunnus"] != 0 and $utuotteet_mukaan == 1) {
							$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
										FROM lasku use index (yhtio_vanhatunnus)
										WHERE yhtio		= '$kukarow[yhtio]'
										and vanhatunnus = '$laskurow[vanhatunnus]'
										and tunnus != '$laskurow[tunnus]'";
							$perheresult = mysql_query($query) or pupe_error($query);
							$tunrow = mysql_fetch_array($perheresult);

							//generoidaan lähetteelle ja keräyslistalle rivinumerot
							if ($tunrow["tunnukset"] != "") {
								$query = "  SELECT tilausrivi.*,
											round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
											round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
											$sorttauskentta,
											if(tilausrivi.var='J', 1, 0) jtsort
											FROM tilausrivi
											JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
											JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
											WHERE tilausrivi.otunnus in ('$tunrow[tunnukset]')
											and tilausrivi.yhtio = '$kukarow[yhtio]'
											$tyyppilisa
											ORDER BY jtsort, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
								$riresult = mysql_query($query) or pupe_error($query);

								while ($row = mysql_fetch_array($riresult)) {
									if ($row['toimitettu'] == '') {
										$row['kommentti'] .= "\n*******".t("Toimitetaan erikseen",$kieli).".*******";
									}
									else {
										$row['kommentti'] .= "\n*******".t("Toimitettu erikseen",$kieli).".*******";
									}

									$row['rivihinta'] 	= "";
									$row['varattu'] 	= "";
									$row['kpl']			= "";
									$row['jt'] 			= "";
									$row['d_erikseen'] 	= "JOO";
									
									rivi($page[$sivu], $lah_tyyppi);
								}
							}
						}

						//Vikan rivin loppuviiva
						$x[0] = 20;
						$x[1] = 580;
						$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
						$pdf->draw_line($x, $y, $page[$sivu], $rectparam);

						loppu($page[$sivu], 1);

						//katotaan onko laskutus nouto
						$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
									FROM lasku
									JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
									JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
									WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
									and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
						$kures = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($kures) > 0 and $yhtiorow["lahete_nouto_allekirjoitus"] != "") {
							kuittaus();
						}

						if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
							alvierittely($page[$sivu]);
						}

						//tulostetaan sivu
						if ($lahetekpl > 1 and $komento != "email") {
							$komento .= " -#$lahetekpl ";
						}

						print_pdf($komento);
					}
				}

				echo "<br><br>";
			}
			
			$id=0;
		}
		else {
			$id=$otunnus;
			$virhe="<font class='error'>".t("Noutajan nimi on syötettävä")."!</font><br><br>";
		}
	}

	if ($id=='') $id=0;

	// meillä ei ole valittua tilausta
	if ($id == '0') {
		$formi	= "find";
		$kentta	= "etsi";
		$boob 	= "";
		
		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

		$query = "	SELECT distinct otunnus
					from tilausrivi, lasku, toimitustapa
					where tilausrivi.yhtio='$kukarow[yhtio]' 
					and lasku.yhtio='$kukarow[yhtio]' 
					and lasku.tunnus=tilausrivi.otunnus 
					and lasku.tila='L' 
					and (lasku.alatila='C' or alatila='B') 
					and toimitustapa.selite=lasku.toimitustapa 
					and toimitustapa.nouto!='' 
					and toimitettu='' 
					and keratty!='' 
					and vienti=''";
		$tilre = mysql_query($query) or pupe_error($query);

		while ($tilrow = mysql_fetch_array($tilre)) {
			// etsitään sopivia tilauksia
			$query = "	SELECT lasku.tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, maksuehto.teksti maksuehto, toimitustapa, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, lasku.laatija, toimaika
						FROM lasku
						LEFT JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio AND maksuehto.tunnus = lasku.maksuehto)
						WHERE lasku.tunnus='$tilrow[0]' and tila='L' $haku and lasku.yhtio='$kukarow[yhtio]' and (alatila='C' or alatila='B') ORDER by laadittu desc";
			$result = mysql_query($query) or pupe_error($query);

			//piirretään taulukko...
			if (mysql_num_rows($result)!=0) {
				while ($row = mysql_fetch_array($result)) {
					// piirretään vaan kerran taulukko-otsikot
					if ($boob=='') {
						$boob='kala';
						
						echo "<table>";
						echo "<tr>";
						for ($i=0; $i<mysql_num_fields($result); $i++)
							echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
						echo "</tr>";
					}

					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result); $i++) {
						if (mysql_field_name($result,$i) == 'laadittu' or mysql_field_name($result,$i) == 'toimaika') {
							echo "<td>".tv1dateconv($row[$i])."</td>";
						}
						else {
							echo "<td>$row[$i]</td>";
						}
					}
					
					echo "<form method='post' action='$PHP_SELF'><td class='back'>
						  <input type='hidden' name='id' value='$row[0]'>
						  <input type='submit' name='tila' value='".t("Toimita")."'></td></tr></form>";
				}
			}
		}

		if ($boob!='')
			echo "</table>";
		else
			echo "<font class='message'>".t("Yhtään toimitettavaa tilausta ei löytynyt")."...</font>";
	}

	if($id != '0') {
		$query = "	SELECT *, concat_ws(' ',lasku.nimi, nimitark) nimi,  
					lasku.osoite, concat_ws(' ', lasku.postino, lasku.postitp) postitp, 
					toim_osoite, concat_ws(' ', toim_postino, toim_postitp) toim_postitp,
					lasku.tunnus laskutunnus, lasku.liitostunnus
					FROM lasku, maksuehto
					WHERE lasku.tunnus='$id' and lasku.yhtio='$kukarow[yhtio]' and tila='L' and (alatila='C' or alatila='B')
					and maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==0){
			die(t("Tilausta")." $id ".t("ei voida toimittaa, koska kaikkia tilauksen tietoja ei löydy! Uuuuuuuhhhhhhh")."!");
		}

		$row = mysql_fetch_array($result);

		echo "<table>";
		echo "<tr><th>" . t("Tilaus") ."</th><td>$row[laskutunnus]</td></tr>";	
		echo "<tr><th>" . t("Asiakas") ."</th><td>$row[nimi]<br>$row[toim_nimi]</td></tr>";
		echo "<tr><th>" . t("Laskutusosoite") ."</th><td>$row[osoite], $row[postitp]</td></tr>";
		echo "<tr><th>" . t("Toimitusosoite") ."</th><td>$row[toim_osoite], $row[toim_postitp]</td></tr>";
		echo "<tr><th>" . t("Maksuehto") ."</th><td>$row[teksti]</td></tr>";	
		echo "<tr><th>" . t("Toimitustapa") ."</th><td>$row[toimitustapa]</td></tr>";		
		echo "</table><br><br>";

		if ($row["valkoodi"] != '' and trim(strtoupper($row["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $row["vienti_kurssi"] != 0) {
			$hinta_riv = "(tilausrivi.hinta/$row[vienti_kurssi])";
		}
		else {
			$hinta_riv = "tilausrivi.hinta";
		}

		$lisa = " 	round($hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$row[erikoisale]-(tilausrivi.ale*$row[erikoisale]/100))/100)),$yhtiorow[hintapyoristys]) rivihinta, 
					(tilausrivi.varattu+tilausrivi.kpl) kpl ";

		$query = "	SELECT concat_ws(' ',hyllyalue, hyllynro, hyllytaso, hyllyvali) varastopaikka, concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno, varattu, concat_ws('@',keratty,kerattyaika) keratty, tilausrivi.tunnus, var, $lisa
					FROM tilausrivi, tuote
					WHERE tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and var!='J' and otunnus = '$id' and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY varastopaikka";

		$result = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($result);

		echo "	<table cellpadding='2' cellspacing='1' border='0'>
				<tr>
				<th>".t("Varastopaikka")."</th>
				<th>".t("Tuoteno")."</th>
				<th>".t("Määrä")."</th>
				<th>".t("Kerätty")."</th>
				</tr>";

		$rivihinta = "";

		$query = "	SELECT laskunsummapyoristys
					FROM asiakas
					WHERE tunnus='$row[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$asres = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($asres);

		$summa = "";

		while($rivi = mysql_fetch_array($result)) {

			$summa = $rivi["rivihinta"];

			//Käsin syötetty summa johon lasku pyöristetään
			if (abs($row["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
				$summa = sprintf("%.2f",$row["hinta"]);
			}

			//Jos laskun loppusumma pyöristetään lähimpään tasalukuun
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
		$tores = mysql_query($query) or pupe_error($query);
		$toita = mysql_fetch_array($tores);

		echo "<form name = 'rivit' method='post' action='$PHP_SELF'>
				<input type='hidden' name='otunnus' value='$id'>
				<input type='hidden' name='tee' value='P'>";


		if ($toita['nouto'] != '' and $row['kateinen'] != '' and $row["chn"] != '999' and ($row["mapvm"] == "" or $row["mapvm"] == '0000-00-00')) {

			echo "<table><tr><th>".t("Valitse kassalipas")."</th><td>";

			$query = "SELECT * FROM kassalipas WHERE yhtio='{$kukarow['yhtio']}'";
			$kassares = mysql_query($query) or pupe_error($query);
			
			$sel = "";

			echo "<input type='hidden' name='noutaja' value=''>";
			echo "<input type='hidden' name='rivihinta' value='$rivihinta'";
			echo "<input type='hidden' name='valkoodi' value='$row[valkoodi]'";
			echo "<input type='hidden' name='maa' value='$row[maa]'";
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
				
				echo "<option value='{$kassarow['tunnus']}' $sel>{$kassarow['nimi']}</option>";
				
				$sel = "";
			}
			echo "</select>";
			echo "</td></tr>";

			$query_maksuehto = "SELECT *
								FROM maksuehto
								WHERE yhtio='$kukarow[yhtio]' 
								and kateinen != '' 
								and kaytossa = '' 
								and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$row[maa]%') 
								ORDER BY tunnus";
			$maksuehtores = mysql_query($query_maksuehto) or pupe_error($query_maksuehto);

			if (mysql_num_rows($maksuehtores) > 1) {
				echo "<table><tr><th>".t("Maksutapa")."</th><td>";

				echo "<select name='maksutapa'>";

				while ($maksuehtorow = mysql_fetch_array($maksuehtores)) {
					
					$sel = "";
					
					if ($maksuehtorow["tunnus"] == $row["maksuehto"]) {
						$sel = "selected";
					}
					echo "<option value='$maksuehtorow[tunnus]' $sel>{$maksuehtorow['teksti']} {$maksuehtorow['kassa_teksti']}</option>";
				}

				echo "<option value='seka'>Seka</option>";
				echo "</select>";
				echo "</td></tr></table>";
				
			}
			else {
				$maksuehtorow = mysql_fetch_array($maksuehtores);
				echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
			}
			
			
			
			echo "</table><br>";
		}
		
		if ($row["chn"] == '999' and $row["mapvm"] != "" and $row["mapvm"] != '0000-00-00') {
			echo "<font class='error'>Tilaus on maksettu jo etukäteen luottokortilla.</font><br><br>";
		}

		if (($toita['nouto'] !='' and $row['kateinen']) == '' or ($row["chn"] == '999' and $row["mapvm"] != "" and $row["mapvm"] != '0000-00-00')) {
			// jos kyseessä on nouto jota *EI* makseta käteisellä, kysytään noutajan nimeä..	
			echo "<table><tr><th>".t("Syötä noutajan nimi")."</th></tr>";
			echo "<tr><td><input size='60' type='text' name='noutaja'></td></tr></table><br>";
			echo "<input type='hidden' name='nouto' value='yes'>";
			echo "<input type='hidden' name='kassalipas' value=''>";

			//kursorinohjausta
			$formi="rivit";
			$kentta="noutaja";
		}
		
		echo "<table>";
		echo "<tr><th>".t("Lähete").":</th><th>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE
					yhtio='$kukarow[yhtio]'
					ORDER by kirjoitin";
		$kirre = mysql_query($query) or pupe_error($query);

		echo "<select name='valittu_tulostin'>";

		echo "<option value=''>".t("Ei tulosteta")."</option>";
		echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

		while ($kirrow = mysql_fetch_array($kirre)) {
			echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
		}

		echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'></th>";
		echo "</tr></table>";

		echo "$virhe";
		echo "<input type='submit' value='".t("Merkkaa toimitetuksi")."'></form>";
	}

	require "../inc/footer.inc";
?>
