<?php

	require("inc/parametrit.inc");

	echo "	<script type='text/javascript'>
				$(document).ready(function() {
					$('.kollibutton').click(function(){
						var kollitunniste = $(this).attr('id');
						$('#kolli').val(kollitunniste);
						$('#formi').submit();
					});

					$('.ostolaskubutton').click(function(){
						var lasku = $(this).attr('id');
						$('#lasku').val(lasku);
						$('#formi').submit();
					});

					$('.toimittajabutton').click(function(){
						$('#tee').val('vaihdatoimittaja');

						var valitse = $(this).attr('valitse');

						if (valitse == 'asn') {
							var asn = $(this).attr('id');
							$('#formi').attr('action', '?valitse=asn&asn_numero='+asn+'&lopetus={$PHP_SELF}////tee=').submit();
						}
						else {
							var tilausnumero = $(this).attr('id');
							$('#formi').attr('action', '?valitse=ostolasku&tilausnumero='+tilausnumero+'&lopetus={$PHP_SELF}////tee=').submit();
						}
					});

					$('.hintapoikkeavuusbutton').click(function(){
						$('#tee').val('hintavertailu');
						$('#kolliformi').submit();
					});

					$('.etsibutton').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#asn_rivi').val(rivitunniste);
						$('#kolliformi').submit();
					});

					$('.etsibutton_osto').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#lasku').val(rivitunniste);
						$('#kolliformi').submit();
					});

					$('.erobutton_osto').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#lasku').val(rivitunniste);

						$('#tee').val('erolistalle');

						var lopetus = $('#lopetus').val();
						lopetukset = lopetus.split('/SPLIT/');

						$('#lopetus').val(lopetukset[0]);

						$('#kolliformi').submit();
					});

					$('.vahvistabutton').click(function(){
						$('#tee').val('vahvistakolli');
						$('#kolliformi').attr('action', '?').submit();
					});

					$('.vahvistavakisinbutton').click(function(){
						$('#tee').val('vahvistavakisinkolli');
						$('#kolliformi').attr('action', '?').submit();
					});

					$('.poistakohdistus').click(function(){
						$('#tee').val('poistakohdistus');
						$('#kolliformi').attr('action', '?').submit();
					});

					$('#kohdista_tilausrivi_formi').submit(function(){
						var kohdistettu = false;

						$('.tunnukset').each(function(){
							if ($(this).is(':checked')) {
								kohdistettu = true;
							}
						});

						if (kohdistettu) {
							var lopetus = $('#lopetus').val();
							lopetukset = lopetus.split('/SPLIT/');

							$('#lopetus').val(lopetukset[0]);
						}
					});
				});
			</script>";

	echo "<font class='head'>",t("Ostolasku / ASN-sanomien tarkastelu"),"</font><hr><br />";

	if (!isset($tee)) $tee = '';
	if (!isset($valitse)) $valitse = '';
	if (!isset($asn_rivi)) $asn_rivi = '';
	if (isset($muut_siirrettavat) and trim($muut_siirrettavat) != "") list($asn_rivi, $toimittaja, $tilausnro, $tuoteno, $tilaajanrivinro, $kpl, $valitse) = explode("!°!", $muut_siirrettavat);

	if ($tee == 'erolistalle') {

		if (isset($lasku) and strpos($lasku, '##') !== false) {
			list($lasku, $tuoteno, $tilaajanrivinro, $toimittaja, $kpl, $rivitunnus, $tilausnumero, $toim_tuoteno) = explode('##', $lasku);
		}

		if (isset($rivitunnus) and trim($rivitunnus) != '') {

			$rivitunnus = (int) $rivitunnus;

			$query = "	UPDATE asn_sanomat SET
						status = 'E'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$rivitunnus}'";
			$res = pupe_query($query);
		}

		$tee = 'nayta';
	}

	if ($tee == 'hintavertailu') {

		$komento = isset($komento) ? $komento : '';

		erolista($lasku, $valitse, $komento);

	}

	if ($tee == 'vaihdatoimittaja') {

		$tila = '';

		if (isset($nimi) and trim($nimi) != '') {

			//tehd‰‰n asiakas- ja toimittajahaku yhteensopivuus
			$ytunnus = $nimi;

			$lause = "<font class='head'>".t("Valitse toimittaja").":</font><hr><br>";
			require ("inc/kevyt_toimittajahaku.inc");

			if ($ytunnus == '' and $monta > 1) {
				//Lˆytyi monta sopivaa, n‰ytet‰‰n formi, mutta ei otsikkoa
				$tila = 'monta';
			}
			elseif ($ytunnus == '' and $monta < 1) {
				//yht‰‰n asiakasta ei lˆytynyt, n‰ytet‰‰n otsikko
				$tila = '';
			}
			else {
				//oikea asiakas on lˆytynyt
				$tunnus = $toimittajaid;
				$tila = 'ok';
			}
		}

		if (isset($toimittajaid) and trim($toimittajaid) != '') {

			$toimittajaid = (int) $toimittajaid;

			$query = "SELECT toimittajanro FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$toimittajaid}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			list($nro, $valitse) = explode("##", $muutparametrit);

			if ($valitse == 'asn') {
				$asn_numero = (int) $nro;

				$query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND asn_numero = '{$asn_numero}'";
				$res = pupe_query($query);
			}
			else {
				$tilausnumero = (int) $nro;

				$query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND tilausnumero = '{$tilausnumero}'";
				$res = pupe_query($query);
			}

			$tee = '';
			$tila = 'ok';
		}

		if ($tila == '') {

			if ($valitse == 'asn') {
				$action = "&muutparametrit={$asn_numero}##{$valitse}";
			}
			else {
				$action = "&muutparametrit={$tilausnumero}##{$valitse}";
			}

			echo "<form method='post' action='?tee={$tee}{$action}'>";
			echo "<table>";
			echo "<tr><th>",t("Etsi toimittajaa")," (",t("nimi")," / ",t("ytunnus"),")</th><td><input type='text' name='nimi' value='' />&nbsp;<input type='submit' value='",t("Etsi"),"' /></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'vahvistakolli' or $tee == 'vahvistavakisinkolli') {

		$automaattikohdistukseen = true;

		if ($valitse != 'asn' and $tee == 'vahvistavakisinkolli') {

			// Kun vahvistetaan katsotaan onko lasku liitetty jo keikkaan
			// Jos on, niin merkataan asn_sanoma status x eik‰ menn‰ automaattikohdistukseen
			$query = "	SELECT tunnus
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tila IN ('H','Y','M','P','Q')
						AND laskunro = '".mysql_real_escape_string($lasku)."'";
			$ostolasku_chk_res = pupe_query($query);

			if (mysql_num_rows($ostolasku_chk_res) > 0) {
				$ostolasku_chk_row = mysql_fetch_assoc($ostolasku_chk_res);

				$query = "	SELECT tunnus
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tila = 'K'
							AND vanhatunnus = '{$ostolasku_chk_row['tunnus']}'";
				$liitosotsikko_chk_res = pupe_query($query);

				if (mysql_num_rows($liitosotsikko_chk_res) > 0) {

					$query = "	UPDATE asn_sanomat SET
								status = 'X'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND asn_numero = '{$lasku}'
								AND status != 'E'";
					$upd_res = pupe_query($query);

					$automaattikohdistukseen = false;

					echo "<font class='message'>",t("Lasku oli jo liitetty saapumiseen"),". ",t("P‰ivitet‰‰n lasku k‰sitellyksi"),".</font><br /><br />";
				}
			}
		}

		if ($automaattikohdistukseen) {

			if ($valitse == 'asn') {
				$kolli = mysql_real_escape_string($kolli);
				$wherelisa = "AND paketintunniste = '{$kolli}'";
			}
			else {
				$wherelisa = "AND asn_numero = '".mysql_real_escape_string($lasku)."'";
			}

			$paketin_rivit 		= array();
			$paketin_tunnukset 	= array();
			$rtuoteno			= array();
			$laskuttajan_toimittajanumero = "";
			$lasku_manuaalisesti_check = 0;

			$tilausrivilisa = $tee == 'vahvistavakisinkolli' ? "" : "AND tilausrivi != ''";

			$query = "	SELECT *
						FROM asn_sanomat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND status != 'E'
						{$tilausrivilisa}
						{$wherelisa}";
			$kollires = pupe_query($query);

			$i = $x = 0;
			$keikoilla	= array();
			$ostoilla	= array();
			$tullaan_virhetarkistuksesta = $tee == 'vahvistavakisinkolli' ? false : true;

			while ($kollirow = mysql_fetch_assoc($kollires)) {

				if ($valitse == 'asn') {

					$toimittaja = $kollirow['toimittajanumero'];
					$asn_numero = $kollirow['asn_numero'];
					$paketin_tunnukset[] = $kollirow['tunnus'];

					// Otetaan ASN-sanomalta tilausrivi(e)n tunnus ja laitetaan $paketin_rivit muuttujaan
					if (strpos($kollirow['tilausrivi'], ",") !== false) {
						foreach (explode(",", $kollirow['tilausrivi']) as $tunnus) {
							$paketin_rivit[] = $tunnus;
						}
					}
					else {
						$paketin_rivit[] = $kollirow['tilausrivi'];
					}

					// Haetaan tuotteen lapset jotka ovat runkoveloituksia
					$query = "	SELECT GROUP_CONCAT(tuoteperhe.tuoteno) lapset
								FROM tuoteperhe
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND isatuoteno = '{$kollirow['tuoteno']}'
								AND ohita_kerays != ''";
					$result = pupe_query($query);
					$lapset = mysql_fetch_assoc($result);

					// Lapsia lˆytyi, t‰m‰ on is‰tuote
					if ($lapset["lapset"] != NULL) {

						// Haetaan tilausnumerot joilla t‰m‰ tuote on
						$query = "	SELECT group_concat(otunnus) tilaukset
									FROM tilausrivi
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.tunnus IN ({$kollirow['tilausrivi']})";
						$result = pupe_query($query);
						$tilaukset = mysql_fetch_assoc($result);

						foreach (explode(",", $lapset['lapset']) as $lapsi_tuoteno) {

							// Haetaan t‰m‰n is‰tuotteen lapsituotteiden tunnukset
							$query = " 	SELECT tunnus, tuoteno
										FROM tilausrivi
										WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
										AND tilausrivi.otunnus IN ({$tilaukset['tilaukset']})
										AND tilausrivi.tuoteno = '{$lapsi_tuoteno}'";
							$result = pupe_query($query);

							if (mysql_num_rows($result) == 0) {

								// otetaan ensimm‰isen is‰tuotteen tilausrivin tiedot
								$query = "	SELECT *
											FROM tilausrivi
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus IN ({$kollirow['tilausrivi']})";
								$isa_chk_res = pupe_query($query);
								$isa_chk_row = mysql_fetch_assoc($isa_chk_res);

								$query = "	SELECT tuoteperhe.isatuoteno,
											tuoteperhe.tuoteno,
											tuote.tuoteno,
											tuote.try,
											tuote.osasto,
											tuote.nimitys,
											tuote.yksikko,
											tuote.myyntihinta,
											tuotepaikat.hyllyalue,
											tuotepaikat.hyllynro,
											tuotepaikat.hyllytaso,
											tuotepaikat.hyllyvali
											FROM tuote
											JOIN tuoteperhe ON (tuoteperhe.yhtio = tuote.yhtio AND tuoteperhe.isatuoteno = '{$kollirow['tuoteno']}' AND tuoteperhe.tyyppi IN ('P','')  AND tuoteperhe.ohita_kerays != '')
											JOIn tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.oletus !='')
											WHERE tuote.yhtio = '{$kukarow['yhtio']}'
											AND tuote.status != 'P'
											AND tuote.tuoteno = '{$lapsi_tuoteno}'";
								$lapsiresult = pupe_query($query);

								while ($lapsitieto = mysql_fetch_assoc($lapsiresult)) {
									// hae tuotteen ostohinta
									$laskuselect = "SELECT *
													FROM lasku
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND tunnus = '{$isa_chk_row['otunnus']}'";
									$laskures	= pupe_query($laskuselect);
									$laskurow	= mysql_fetch_assoc($laskures);

									list($hinta,,,) = alehinta_osto($laskurow, $lapsitieto, $isa_chk_row["tilkpl"]);

									$lisainsert = "	INSERT INTO tilausrivi SET
													yhtio			= '{$kukarow['yhtio']}',
													tyyppi			= 'O',
													toimaika		= '{$isa_chk_row['toimaika']}',
													kerayspvm		= '{$isa_chk_row['kerayspvm']}',
													otunnus			= '{$isa_chk_row['otunnus']}',
													tuoteno			= '{$lapsitieto['tuoteno']}',
													try				= '{$lapsitieto['try']}',
													osasto			= '{$lapsitieto['osasto']}',
													nimitys			= '{$lapsitieto['nimitys']}',
													yksikko			= '{$lapsitieto['yksikko']}',
													tilkpl			= '{$isa_chk_row['tilkpl']}',
													varattu			= '{$isa_chk_row['varattu']}',
													kpl				= '{$isa_chk_row['kpl']}',
													hinta			= '{$hinta}',
													laatija			= 'lapset',
													kommentti		= 'ASN-sanomalta: TL:{$lapsitieto['tunnus']} tuotteelle: {$lapsitieto['isatuoteno']} lis‰t‰‰n lapsituote: {$lapsitieto['tuoteno']}',
													laadittu		=  now(),
													hyllyalue		= '{$lapsitieto['hyllyalue']}',
													hyllynro		= '{$lapsitieto['hyllynro']}',
													hyllytaso		= '{$lapsitieto['hyllytaso']}',
													hyllyvali		= '{$lapsitieto['hyllyvali']}',
													uusiotunnus		= '{$isa_chk_row['uusiotunnus']}',
													perheid			= '{$isa_chk_row['tunnus']}'";
									$inskres = pupe_query($lisainsert);

									$id = mysql_insert_id();
									$paketin_rivit[] = $id;

									// p‰ivitet‰‰n is‰
									$updateisa = "	UPDATE tilausrivi SET
													perheid = tunnus
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND tunnus = '{$isa_chk_row['tunnus']}'";
									$updateres = pupe_query($updateisa);

									$query = "	SELECT *
												FROM asn_sanomat
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND laji = 'asn'
												AND tilausrivi LIKE '%{$isa_chk_row['tunnus']}%'";
									$info_res = pupe_query($query);
									$info_row = mysql_fetch_assoc($info_res);

									// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
									$fields = "yhtio";
									$values = "'{$kukarow['yhtio']}'";

									// Ei monisteta tunnusta
									for ($ii = 1; $ii < mysql_num_fields($info_res) - 1; $ii++) {

										$fieldname = mysql_field_name($info_res,$ii);

										$fields .= ", ".$fieldname;

										switch ($fieldname) {
											case 'tilausrivi':
												$values .= ", '{$id}'";
												break;
											case 'tuoteno':
											case 'toim_tuoteno':
											case 'toim_tuoteno2':
												$values .= ", '{$lapsitieto['tuoteno']}'";
												break;
											case 'hinta':
												$values .= ", '{$hinta}'";
												break;
											default:
												$values .= ", '".$info_row[$fieldname]."'";
										}
									}

									$kysely  = "INSERT INTO asn_sanomat ({$fields}) VALUES ({$values})";
									$uusires = pupe_query($kysely);
								}

								$paketin_rivit[] = $isa_chk_row["tunnus"];
							}
							else {
								while ($rivi = mysql_fetch_assoc($result)) {
									$paketin_rivit[] = $rivi["tunnus"];
								}
							}
						}
					}

					$sscc_paketti_tunnus = $kollirow["paketintunniste"];
				}
				else {

					$rtuoteno[$i]['tuoteno'] 			= trim($kollirow['tuoteno']) != "" ? $kollirow['tuoteno'] : $kollirow['toim_tuoteno2'];
					$rtuoteno[$i]['tuoteno2'] 			= $kollirow['toim_tuoteno'];
					$rtuoteno[$i]['tuoteno3'] 			= trim($kollirow['tuoteno']) != "" ? $kollirow['toim_tuoteno2'] : "";
					$rtuoteno[$i]['ostotilausnro'] 		= $kollirow['tilausnumero'];
					$rtuoteno[$i]['tilaajanrivinro'] 	= $kollirow['tilausrivinpositio'];
					$rtuoteno[$i]['kpl'] 				= $kollirow['kappalemaara'];
					$rtuoteno[$i]['hinta'] 				= $kollirow['hinta'];
					$rtuoteno[$i]['ale1'] 				= $kollirow['lasku_ale1'];
					$rtuoteno[$i]['ale2'] 				= $kollirow['lasku_ale2'];
					$rtuoteno[$i]['ale3'] 				= $kollirow['lasku_ale3'];
					$rtuoteno[$i]['lisakulu'] 			= $kollirow['lisakulu'];
					$rtuoteno[$i]['kulu'] 				= $kollirow['kulu'];
					$rtuoteno[$i]['kauttalaskutus']		= "";
					$rtuoteno[$i]['insert_id']			= $kollirow['tunnus'];
					$rtuoteno[$i]['status']				= $kollirow['status'];

					if ($tullaan_virhetarkistuksesta) {
						$query = "	SELECT *
									FROM tilausrivi
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus IN ({$kollirow['tilausrivi']})";
						$tilausrivires = pupe_query($query);



						while ($tilausrivirow = mysql_fetch_assoc($tilausrivires)) {
							if ($tilausrivirow['uusiotunnus'] == 0) {
								// lˆytyi, ei ole keikalla
								$ostoilla[$x]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
								$ostoilla[$x]["hinta"] = $rtuoteno[$i]["hinta"];
								$ostoilla[$x]["kpl"] = $rtuoteno[$i]["kpl"];
								$ostoilla[$x]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
								$ostoilla[$x]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
								$ostoilla[$x]["insert_id"] = $rtuoteno[$i]["insert_id"];
								$ostoilla[$x]["lisakulu"] = $rtuoteno[$i]["lisakulu"];
								$ostoilla[$x]["kulu"] = $rtuoteno[$i]["kulu"];
								$ostoilla[$x]["ale1"] = $rtuoteno[$i]["ale1"];
								$ostoilla[$x]["ale2"] = $rtuoteno[$i]["ale2"];
								$ostoilla[$x]["ale3"] = $rtuoteno[$i]["ale3"];
								$ostoilla[$x]['tuoteno'] = $tilausrivirow['tuoteno'];
							}
							else {
								// lˆytyi, on jo keikalla
								$keikoilla[$x]["tunnus"] = $tilausrivirow['tunnus'];
								$keikoilla[$x]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
								$keikoilla[$x]["hinta"] = $rtuoteno[$i]["hinta"];
								$keikoilla[$x]["kpl"] = $rtuoteno[$i]["kpl"];
								$keikoilla[$x]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
								$keikoilla[$x]["insert_id"] = $rtuoteno[$i]["insert_id"];
								$keikoilla[$x]["lisakulu"] = $rtuoteno[$i]["lisakulu"];
								$keikoilla[$x]["kulu"] = $rtuoteno[$i]["kulu"];
								$keikoilla[$x]["ale1"] = $rtuoteno[$i]["ale1"];
								$keikoilla[$x]["ale2"] = $rtuoteno[$i]["ale2"];
								$keikoilla[$x]["ale3"] = $rtuoteno[$i]["ale3"];
								$keikoilla[$x]['tuoteno'] = $tilausrivirow['tuoteno'];
								
								// jos tilausrivin saapumisella onkin jo vaihto-omaisuuslasku, ei edet‰ ja nollataan asn_sanomat.tilausrivi
								$query = "	SELECT saapuminen.tunnus
											FROM lasku AS saapuminen
											WHERE saapuminen.yhtio = '{$kukarow['yhtio']}'
											AND saapuminen.tunnus = '{$tilausrivirow['uusiotunnus']}'
											AND saapuminen.tapvm = '0000-00-00'";
								$saapres = pupe_query($query);
								
								if (mysql_num_rows($saapres) != 0) {
									$lasku_manuaalisesti_check = 1;
									$query = "	UPDATE asn_sanomat SET
												tilausrivi = ''
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND tunnus	= '{$kollirow['tunnus']}'";
									$upd_res = pupe_query($query);
								}
							}

							$x++;
						}
					}

					$i++;

					$laskuttajan_toimittajanumero = $kollirow['toimittajanumero'];
				}
			}

			if ($valitse != 'asn' and count($rtuoteno) > 0 and $laskuttajan_toimittajanumero != "" and $lasku_manuaalisesti_check == 0) {

				if ($tee == 'vahvistavakisinkolli' and !$tullaan_virhetarkistuksesta) {
					$query = "	UPDATE asn_sanomat SET
								status = '',
								tilausrivi = ''
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND laji = 'tec'
								AND asn_numero = '{$lasku}'
								AND status != 'E'";
					$upd_res = pupe_query($query);
				}

				$query = "	SELECT tunnus
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laskunro = '{$lasku}'
							AND tila IN ('H','Y','M','P','Q')";
				$tunnus_fetch_res = pupe_query($query);
				$tunnus_fetch_row = mysql_fetch_assoc($tunnus_fetch_res);

				$tunnus = $tunnus_fetch_row['tunnus'];

				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '{$kukarow['yhtio']}'
							and toimittajanro = '{$laskuttajan_toimittajanumero}'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);

					require('inc/verkkolasku-in-luo-keikkafile.inc');

					if ($virheet == 0) {

						$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND status != 'E' {$wherelisa}";
						$kollires = pupe_query($query);

						while ($kollirow = mysql_fetch_assoc($kollires)) {
							$query = "	UPDATE asn_sanomat SET
										status = 'X'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$kollirow['tunnus']}'";
							$updateres = pupe_query($query);
						}

					}

					// verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
				}
			}
			else if ($valitse != 'asn' and count($rtuoteno) > 0 and $laskuttajan_toimittajanumero != "" and $lasku_manuaalisesti_check != 0) {
				echo "<font class='message'>",t("Laskun rivej‰ oli liitetty saapumiseen jossa oli jo vaihto-omaisuus lasku"),". ",t("Poistetaan t‰mmˆisten rivien liitos"),". ",t("K‰sittele lasku"), " $lasku ",t("uudestaan"),".</font><br /><br />";
			}
			
			if ($valitse == 'asn' and count($paketin_rivit) > 0) {

				$query = "SELECT GROUP_CONCAT(tuoteno) AS tuotenumerot FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN (".implode(",", $paketin_rivit).")";
				$tuotenores = pupe_query($query);
				$tuotenorow = mysql_fetch_assoc($tuotenores);

				$paketin_tuotteet = explode(",", $tuotenorow['tuotenumerot']);

				require('inc/asn_kohdistus.inc');

				asn_kohdista_suuntalava($toimittaja, $asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset, $sscc_paketti_tunnus);

			}
		}

		$tee = '';
	}

	if ($tee == 'poistakohdistus') {

		$kolli = mysql_real_escape_string($kolli);
		$wherelisa = "AND paketintunniste = '{$kolli}'";

		$query = "SELECT * from asn_sanomat where yhtio = '{$kukarow["yhtio"]}' {$wherelisa}";
		$kollires = pupe_query($query);
		$rivi = mysql_fetch_assoc($kollires);

		// poistetaan STATUS-t‰pp‰
		$query = "UPDATE asn_sanomat SET status ='', tilausrivi='' WHERE yhtio = '{$kukarow['yhtio']}' {$wherelisa}";
		$kollires = pupe_query($query);

		$tee = '';
	}

	if ($tee == 'uusirivi') {

		$var = 'O';

		if (trim($tilausnro) == '' or $tilausnro == 0) {

			$asn_rivi = (int) $asn_rivi;

			$query = "SELECT tilausnumero FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			$tilausnro = $row['tilausnumero'];
		}

		$tilausnro = (int) $tilausnro;

		$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnro}'";
		$res = pupe_query($query);

		if (mysql_num_rows($res) > 0) {

			$laskurow = mysql_fetch_assoc($res);

			if ($laskurow['alatila'] == 'X') {
				$error = t("Tilaus").' '.$tilausnro.' '.t("ei ole sopiva")."!";
				$tee = 'etsi';
			}
			else {
				$query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$tuoteno}'";
				$result = pupe_query($query);
				$trow = mysql_fetch_assoc($result);

				list($hinta,,,) = alehinta_osto($laskurow, $trow, $kpl);

				//pidet‰‰n kaikki muuttujat tallessa
				$muut_siirrettavat = $asn_rivi."!°!".$toimittaja."!°!".$tilausnro."!°!".$tuoteno."!°!".$tilaajanrivinro."!°!".$kpl."!°!".$valitse;

				$rivinotunnus = $tilausnro;
				$toimaika = date('Y')."-".date('m')."-".date('d');

				echo t("Tee uusi rivi").":<br>";

				require('tilauskasittely/syotarivi_ostotilaus.inc');
				require('inc/footer.inc');
				exit;
			}
		}
		else {
			$error = t("Ostotilausta").' '.$tilausnro.' '.t("ei lˆydy")."!";
			$tee = 'etsi';
		}
	}

	//poistetaan rivi, n‰ytet‰‰n lista
	if ($tee == 'TI' and isset($tyhjenna)) {
		$tee = 'etsi';
	}

	//tarkastetaan tilausrivi
	if ($tee == 'TI') {
		// Parametreja joita tarkistarivi tarvitsee
		$laskurow["tila"] 	= "O";
		$toim_tarkistus 	= "EI";
		$prow["tuoteno"] 	= $tuoteno;
		$prow["var"]		= $rivinvar;
		$kukarow["kesken"]	= $rivinotunnus;

		$kpl 	= str_replace(',','.',$kpl);
		$hinta 	= str_replace(',','.',$hinta);

		for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
			${'ale'.$alepostfix} = str_replace(',','.', ${'ale'.$alepostfix});
		}

		if (checkdate($toimkka,$toimppa,$toimvva)) {
			$toimaika = $toimvva."-".$toimkka."-".$toimppa;
		}

		if ($hinta == "") {

			$toimittaja = (int) $toimittaja;

			$query = "	SELECT tunnus
						FROM toimi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND toimittajanro = '{$toimittaja}'";
			$toimires = pupe_query($query);
			$toimirow = mysql_fetch_assoc($toimires);

			$laskurow['liitostunnus'] = $toimirow['tunnus'];

			$query = "	SELECT *
						FROM tuotteen_toimittajat
						WHERE tuoteno = '{$prow['tuoteno']}'
						AND yhtio = '{$kukarow['yhtio']}'
						AND liitostunnus = '{$laskurow['liitostunnus']}'";
			$rarres1 = pupe_query($query);
			$hinrow1 = mysql_fetch_assoc($rarres1);

			$prow["hinta"] = $hinrow1["ostohinta"];
		}
		else {
			$prow["hinta"] = $hinta;
		}

		$multi = "";
		require("inc/tuotehaku.inc");
		$prow["tuoteno"] = $tuoteno;

		require('tilauskasittely/tarkistarivi_ostotilaus.inc');

		//n‰ytet‰‰n virhe ja annetaan mahis korjata se
		if (trim($varaosavirhe) != '' or trim($tuoteno) == "") {

			//rivien splittausvaihtoehtot n‰kyviin
			$automatiikka = 'ON';

			//pidet‰‰n kaikki muuttujat tallessa
			$muut_siirrettavat = $asn_rivi."!°!".$toimittaja."!°!".$tilausnro."!°!".$tuoteno."!°!".$tilaajanrivinro."!°!".$kpl."!°!".$valitse;

			$rivinotunnus = $tilausnro;

			echo t("Muuta rivi‰"),":<br>";
			require('tilauskasittely/syotarivi_ostotilaus.inc');
			require('inc/footer.inc');
			exit;
		}

	}

	//rivi on tarkistettu ja se lisataan tietokantaan
	if ((isset($varaosavirhe) and trim($varaosavirhe) == '') and ($tee == "TI") and trim($tuoteno) != '') {

		$laskurow["tila"] = "O";
		$kukarow["kesken"] = $rivinotunnus;

		$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivinotunnus}'";
		$laskures = pupe_query($query);
		$laskurow = mysql_fetch_assoc($laskures);

		if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
			$tuoteno_array[] = $tuoteno;
		}

		//K‰ytt‰j‰n syˆtt‰m‰ hinta ja ale ja netto, pit‰‰ s‰ilˆ‰ jotta tuotehaussakin voidaan syˆtt‰‰ n‰m‰
		for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
			${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
		}

		$kayttajan_hinta	= $hinta;
		$kayttajan_netto 	= $netto;
		$kayttajan_var		= $var;
		$kayttajan_kpl		= $kpl;
		$kayttajan_alv		= $alv;

		foreach ($tuoteno_array as $tuoteno) {

			$query	= "	SELECT *
						FROM tuote
						WHERE tuoteno = '{$tuoteno}'
						and yhtio = '{$kukarow['yhtio']}'
						and ei_saldoa = ''";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				//Tuote lˆytyi
				$trow = mysql_fetch_array($result);
			}
			else {
				//Tuotetta ei lˆydy, arvataan muutamia muuttujia
				$trow["alv"] = $laskurow["alv"];
			}

			if (checkdate($toimkka,$toimppa,$toimvva)) {
				$toimaika = $toimvva."-".$toimkka."-".$toimppa;
			}
			if (checkdate($kerayskka,$keraysppa,$keraysvva)) {
				$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
			}
			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}
			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}

			$varasto = $laskurow["varasto"];

			//Tehd‰‰n muuttujaswitchit
			if (is_array($hinta_array)) {
				$hinta = $hinta_array[$tuoteno];
			}
			else {
				$hinta = $kayttajan_hinta;
			}

			for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
				if (is_array(${'ale_array'.$alepostfix})) {
					${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
				}
				else {
					${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
				}
			}

			if (is_array($netto_array)) {
				$netto = $netto_array[$tuoteno];
			}
			else {
				$netto = $kayttajan_netto;
			}

			if (is_array($var_array)) {
				$var = $var_array[$tuoteno];
			}
			else {
				$var = $kayttajan_var;
			}

			if (is_array($kpl_array)) {
				$kpl = $kpl_array[$tuoteno];
			}
			else {
				$kpl = $kayttajan_kpl;
			}

			if (is_array($alv_array)) {
				$alv = $alv_array[$tuoteno];
			}
			else {
				$alv = $kayttajan_alv;
			}

			$tmp_rivitunnus = $rivitunnus;

			$rivitunnus = 0;

			if ($kpl != 0) {
				require ('tilauskasittely/lisaarivi.inc');
			}

			$rivitunnus = $tmp_rivitunnus;

			$query = "	UPDATE asn_sanomat SET
						tuoteno = '{$tuoteno}'
						WHERE yhtio = '{$kukarow['yhtio']}'
						#AND laji = 'tec'
						AND tunnus = '{$rivitunnus}'";
			$upd_res = pupe_query($query);

			$hinta 	= '';
			$netto 	= '';
			$var 	= '';
			$kpl 	= '';
			$alv 	= '';
			$paikka	= '';

			for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
				${'ale'.$alepostfix} = '';
			}
		}

		if ($lisavarusteita == "ON" and $perheid2 > 0) {
			//P‰ivitet‰‰n is‰lle perheid jotta tiedet‰‰n, ett‰ lis‰varusteet on nyt lis‰tty
			$query = "	UPDATE tilausrivi set
						perheid2	= '{$perheid2}'
						where yhtio = '{$kukarow['yhtio']}'
						and tunnus 	= '{$perheid2}'";
			$updres = pupe_query($query);
		}

		$tee = 'etsi';
	}

	//korjataan siirrett‰v‰t muuttujat taas talteen
	if (isset($muut_siirrettavat)) {
		list($asn_rivi, $toimittaja, $tilausnro, $tuoteno, $tilaajanrivinro, $kpl, $valitse) = explode("!°!", $muut_siirrettavat);
	}

	if ($tee == 'kohdista_tilausrivi') {

		if (count($tunnukset) == 0) {
			$error = t("Halusit kohdistaa, mutta et valinnut yht‰‰n rivi‰")."!";
			$tee = 'etsi';
		}
		else {
			// typecastataan formista tulleet tunnukset stringeist‰ inteiksi
			$tunnukset = array_map('intval', $tunnukset);
			$poista_tilausrivi = array_map('intval', $poista_tilausrivi);
			$ostotilauksella_tilaajanrivinro = array_map('intval',$ostotilauksella_tilaajanrivinro);
			// otetaan ostotilausrivin kpl m‰‰r‰, splitataan ja menn‰‰ eteenp‰in...
			// T‰m‰ pit‰‰ sitten jollain tavalla muuttaa paremmaksi, t‰m‰ on versio 1.0
			$kpl_maara_ostolla = $ostotilauksella_kpl[$tunnukset[0]];

			// haetaan ostotilauksen rivitiedot kyseiselle riville.
			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$tunnukset[0]}'";
			$ostores = pupe_query($query);
			$ostotilausrivirow = mysql_fetch_assoc($ostores);

			$query = "	SELECT liitostunnus
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ostotilausrivirow['otunnus']}'";
			$liit_res = pupe_query($query);
			$liit_row = mysql_fetch_assoc($liit_res);

			$query = "	SELECT tuotteen_toimittajat.toim_tuoteno
						FROM tuotteen_toimittajat
						JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P' AND tuote.tuoteno = '{$tuoteno}')
						WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
						AND tuotteen_toimittajat.liitostunnus = '{$liit_row['liitostunnus']}'";
			$tuoteno_res = pupe_query($query);
			$tuoteno_row = mysql_fetch_assoc($tuoteno_res);

			$_tunn = $valitse == 'asn' ? $asn_rivi : $rivitunnus;
			$lajilisa = $valitse == 'asn' ? "and laji = 'asn'" : "and laji = 'tec'";

			// haetaan ASN-sanomalta kpl m‰‰r‰
			$hakuquery = "	SELECT *
							FROM asn_sanomat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$_tunn}'
							{$lajilisa}";
			$hakures = pupe_query($hakuquery);
			$asn_row_haku = mysql_fetch_assoc($hakures);

			$asn_kpl_tilaukselta = $asn_row_haku["kappalemaara"];
			$erotus = $kpl_maara_ostolla - $asn_kpl_tilaukselta;

			// tehd‰‰n splitti
			if ($erotus != 0) {

				// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
				$query = "	UPDATE tilausrivi SET
							varattu = '{$asn_kpl_tilaukselta}',
							tilkpl	= '{$asn_kpl_tilaukselta}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$ostotilausrivirow['tunnus']}'";
				$upres = pupe_query($query);

				// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
				$query = "	INSERT INTO tilausrivi SET
							yhtio 		= '$ostotilausrivirow[yhtio]',
							tyyppi		= '$ostotilausrivirow[tyyppi]',
							toimaika	= '$ostotilausrivirow[toimaika]',
							kerayspvm	= '$ostotilausrivirow[kerayspvm]',
							otunnus		= '$ostotilausrivirow[otunnus]',
							tuoteno		= '$ostotilausrivirow[tuoteno]',
							try			= '$ostotilausrivirow[try]',
							osasto		= '$ostotilausrivirow[osasto]',
							nimitys		= '$ostotilausrivirow[nimitys]',
							yksikko		= '$ostotilausrivirow[yksikko]',
							tilkpl		= '$erotus',
							varattu		= '$erotus',
							hinta		= '$ostotilausrivirow[hinta]',
							laatija		= '$ostotilausrivirow[laatija]',
							laadittu	= '$ostotilausrivirow[laadittu]',
							hyllyalue	= '$ostotilausrivirow[hyllyalue]',
							hyllynro	= '$ostotilausrivirow[hyllynro]',
							hyllytaso	= '$ostotilausrivirow[hyllytaso]',
							hyllyvali	= '$ostotilausrivirow[hyllyvali]',
							tilaajanrivinro = '$ostotilausrivirow[tilaajanrivinro]'";
				$inskres = pupe_query($query);
			}

			// p‰ivitet‰‰n t‰ss‰ vaiheessa tilaukselle tilaajanrivipositio t‰lle uudelle riville, mik‰li ollaan poistamassa samalla vanha.
			if ($poista_tilausrivi["0"] != 0) {

				$updatequery2 = "	UPDATE tilausrivi SET
									tilaajanrivinro = '{$poista_tilausrivi[0]}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$asn_row_haku['tilausnumero']}'
									AND tunnus = '".implode(",", $tunnukset)."'";
				pupe_query($updatequery2);
			}

			$query = "	SELECT paketintunniste, asn_numero
						FROM asn_sanomat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$_tunn}'";
			$res = pupe_query($query);
			$row = mysql_fetch_assoc($res);

			$kolli = $row['paketintunniste'];

			$toim_tuotenolisa = trim($tuoteno_row['toim_tuoteno']) != "" ? ", toim_tuoteno = '{$tuoteno_row['toim_tuoteno']}' " : "";

			$query = "	UPDATE asn_sanomat SET
						tilausrivi = '".implode(",", $tunnukset)."',
						muuttaja = '{$kukarow['kuka']}',
						muutospvm = now(),
						tuoteno = '{$ostotilausrivirow['tuoteno']}'
						{$toim_tuotenolisa}
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$_tunn}'";
			$updres = pupe_query($query);

			$tee = 'nayta';
		}
	}

	if ($tee == 'etsi') {

		if ($valitse == 'asn') {
			if (isset($asn_rivi) and strpos($asn_rivi, '##') !== false) {
				list($asn_rivi, , $tilaajanrivinro) = explode('##', $asn_rivi); // ei otetan linkist‰ tuoteno:a koska jos siin‰ on v‰li niin hajoilee

				$asn_rivi = (int) $asn_rivi;

				$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
				$result = pupe_query($query);
				$asn_row = mysql_fetch_assoc($result);

				if ($asn_row["toimittajanumero"] == "123067") {
					$orgtuote = $asn_row["toim_tuoteno"];
					$lyhennetty_tuoteno = substr($asn_row["toim_tuoteno"], 0, -3);
					$jatkettu_tuoteno = $lyhennetty_tuoteno."090";

					if ($asn_row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}'";
					}

					$poikkeus_tuoteno =" in ('$orgtuote','$lyhennetty_tuoteno','$jatkettu_tuoteno' $toinen_tuoteno)";
				}
				elseif ($asn_row["toimittajanumero"] == "123453") {
					$suba = substr($asn_row["toim_tuoteno"],0,3);
					$subb = substr($asn_row["toim_tuoteno"],3);
					$tuote = $suba."-".$subb;
					$yhteen = $asn_row["toim_tuoteno"];

					if ($asn_row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}'";
					}

					$poikkeus_tuoteno = " in ('$tuote','$yhteen' $toinen_tuoteno) ";
				}
				elseif ($asn_row["toimittajanumero"] == "123178") {
					$orgtuote = $asn_row["toim_tuoteno"];
					$lyhennetty = substr($asn_row["toim_tuoteno"],3);

					if ($row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = substr($asn_row["toim_tuoteno2"],3);
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}

					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				elseif ($asn_row["toimittajanumero"] == "123084") {
					$orgtuote = $asn_row["toim_tuoteno"];
					$lyhennetty = ltrim($asn_row["toim_tuoteno"],'0');

					if ($asn_row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = ltrim($asn_row["toim_tuoteno2"],'0');
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}
					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				else {

					if ($asn_row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$asn_row["toim_tuoteno2"]}'";
					}

					$poikkeus_tuoteno = " in ('{$asn_row["toim_tuoteno"]}' $toinen_tuoteno) ";
				}

				$query = "	SELECT tt.tuoteno ttuoteno, tt.toim_tuoteno, tuote.tuoteno tuoteno
							FROM tuotteen_toimittajat AS tt
							JOIN toimi ON (toimi.tunnus = tt.liitostunnus AND toimi.yhtio = tt.yhtio AND toimi.toimittajanro = '{$asn_row['toimittajanumero']}' AND tt.toim_tuoteno {$poikkeus_tuoteno} AND toimi.tyyppi != 'P')
							JOIN tuote ON (tuote.yhtio = toimi.yhtio AND tuote.tuoteno = tt.tuoteno AND tuote.status != 'P')
							WHERE tt.yhtio = '{$kukarow['yhtio']}'";
				$result = pupe_query($query);
				$apurivi = mysql_fetch_assoc($result);

				if ($apurivi["tuoteno"] !="") {
					$tuoteno = $apurivi['tuoteno'];
				}
				else {
					$tuoteno = $asn_row['toim_tuoteno'];
				}

			}

			// pakotetaan tuoteno asn_sanomasta. V‰lilyˆnnit tekee kiusaa
			// if (!isset($tuoteno)) $tuoteno =  $asn_row['toim_tuoteno'];
			if (!isset($toimittaja) and isset($asn_row)) $toimittaja = $asn_row['toimittajanumero'];
			if (!isset($tilausnro) and isset($asn_row)) $tilausnro = $asn_row['tilausnumero'];
			if (!isset($kpl) and isset($asn_row)) $kpl = $asn_row['kappalemaara'];
		}
		else {
			if (isset($lasku) and strpos($lasku, '##') !== false) {
				list($lasku, $tuoteno, $tilaajanrivinro, $toimittaja, $kpl, $rivitunnus, $tilausnumero, $toim_tuoteno) = explode('##', $lasku);

				if ($tuoteno == '') $tuoteno = $toim_tuoteno;

				$tilausnro = $tilausnumero;
			}
		}

		echo "<form method='post' action='?tee=etsi&valitse={$valitse}&lasku={$lasku}&rivitunnus={$rivitunnus}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&lopetus={$lopetus}'>";

		echo "<table>";
		echo "<tr><th colspan='6'>",t("Etsi tilausrivi"),"</th></tr>";

		echo "<tr>";
		echo "<th>",t("Toimittaja"),"</th>";
		echo "<th>",t("Tilausnro"),"</th>";
		echo "<th>",t("Tuotenro"),"</th>";
		echo "<th>",t("Tilaajan rivinro"),"</th>";
		echo "<th>",t("Kpl"),"</th>";
		echo "<th>&nbsp;</th>";
		echo "</tr>";

		$toimittaja = (int) $toimittaja;

		$query = "	SELECT tunnus, asn_sanomat
					FROM toimi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND toimittajanro = '{$toimittaja}'
					AND tyyppi != 'P'";
		$toimires = pupe_query($query);
		$toimirow = mysql_fetch_assoc($toimires);

		$chk = (isset($rivit_ei_asnlla) and trim($rivit_ei_asnlla) != '') ? " checked" : "";

		echo "<tr>";
		echo "<td>{$toimittaja}</td>";
		echo "<td><input type='text' name='tilausnro' value='{$tilausnro}' /></td>";
		echo "<td><input type='text' name='tuoteno' value='{$tuoteno}' /></td>";
		echo "<td><input type='text' name='tilaajanrivinro' value='{$tilaajanrivinro}' /></td>";
		echo "<td><input type='text' name='kpl' value='{$kpl}' /></td>";
		echo "<td><input type='submit' value='",t("Etsi"),"' /></td>";
		echo "</tr>";

		if ($toimirow['asn_sanomat'] == 'K') {
			echo "<tr>";
			echo "<td colspan='6' class='back'><input type='checkbox' name='rivit_ei_asnlla'{$chk} /> ",t("N‰yt‰ myˆs rivit joita ei ole ASN:ll‰ tullut"),"</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "</form>";

		echo "<br /><hr /><br />";

		echo "<form method='post' action='?tee=uusirivi&valitse={$valitse}&lasku={$lasku}&rivitunnus={$rivitunnus}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}&lopetus={$lopetus}'>";
		echo "<input type='submit' value='",t("Tee uusi tilausrivi"),"' />";
		echo "</form>";
		echo "<br />";

		if (trim($toimittaja) != '' or trim($tilausnro) != '' or trim($tuoteno) != '' or trim($tilaajanrivinro) != '' or trim($kpl) != '') {
			echo "<br /><hr /><br />";

			if (isset($error)) echo "<font class='error'>{$error}</font><br /><br />";

			echo "<form method='post' id='kohdista_tilausrivi_formi' action='?tee=kohdista_tilausrivi&rivitunnus={$rivitunnus}&valitse={$valitse}&lasku={$lasku}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}'>";
			echo "<input type='hidden' name='lopetus' id='lopetus' value='{$lopetus}' />";

			echo "<table>";
			echo "<tr><th colspan='6'>",t("Haun tulokset"),"</th><th><input type='submit' value='",t("Kohdista"),"' /></th></tr>";
			echo "<tr>";
			echo "<th>",t("Tilausnro"),"</th>";
			echo "<th>",t("Tuoteno"),"</th>";
			echo "<th>",t("Varattu")," / ",t("Kpl"),"</th>";
			echo "<th>",t("Keikka"),"</th>";
			echo "<th>",t("Keikan tila"),"</th>";
			echo "<th>",t("Kohdistus"),"</th>";
			echo "<th>",t("Poista"),"</th>";
			echo "</tr>";

			$tilausnro = (int) $tilausnro;

			$tilaajanrivinrolisa = trim($tilaajanrivinro) != '' ? " and tilausrivi.tilaajanrivinro = ".(int) $tilaajanrivinro : '';
			$tilausnrolisa = (trim($tilausnro) != '' and trim($tilausnro) != 0) ? " and lasku.tunnus LIKE '%{$tilausnro}'" : '';
			$tuoteno_valeilla = str_replace(' ','_',$tuoteno);
			$tuoteno_ilman_valeilla =str_replace(' ','',$tuoteno);
			$tuotenolisa = trim($tuoteno) != '' ? " and (tuote.tuoteno like '".mysql_real_escape_string($tuoteno)."%' or tuote.tuoteno = '".mysql_real_escape_string($tuoteno_valeilla)."' or tuote.tuoteno = '".mysql_real_escape_string($tuoteno_ilman_valeilla)."')" : '';
			$kpllisa = trim($kpl) != '' ? " and tilausrivi.varattu = ".(float) $kpl : '';

			if (trim($kpl) != '' and $valitse != 'asn') {
				$kpl = (float) $kpl;
				$kpllisa = " and (tilausrivi.varattu + tilausrivi.kpl = '{$kpl}')";
			}

			if ($tuotenolisa != "") {
				$query = "	SELECT status, tuoteno
							FROM tuote
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND status = 'P'
							{$tuotenolisa}";
				$status_chk_res = pupe_query($query);

				if (mysql_num_rows($status_chk_res) > 0) {

					while ($status_chk_row = mysql_fetch_assoc($status_chk_res)) {
						echo "<tr>";
						echo "<td colspan='7'>",t("Tuote on poistunut"),"! ({$status_chk_row['tuoteno']})</td>";
						echo "</tr>";
					}
				}
			}

			$tuotenolisa = trim($tuoteno) != '' ? " and (tilausrivi.tuoteno like '".mysql_real_escape_string($tuoteno)."%' or tilausrivi.tuoteno = '".mysql_real_escape_string($tuoteno_valeilla)."' or tilausrivi.tuoteno = '".mysql_real_escape_string($tuoteno_ilman_valeilla)."')" : '';

			$alatilalisa = $valitse == 'asn' ? " AND lasku.alatila != 'X' " : "";

			$query = "	SELECT DISTINCT tilausrivi.tunnus,
						tilausrivi.tuoteno,
						tilausrivi.otunnus,
						tilausrivi.varattu,
						tilausrivi.kpl,
						tilausrivi.tilaajanrivinro,
						IF(tilausrivi.uusiotunnus = 0, '', tilausrivi.uusiotunnus) AS uusiotunnus,
						IF(lasku.tila = 'O', lasku.laskunro, 0) AS onko_lasku
						FROM lasku
						JOIN tilausrivi ON (
							tilausrivi.yhtio = lasku.yhtio
							AND tilausrivi.tyyppi = 'O'
							AND (tilausrivi.otunnus = lasku.tunnus OR tilausrivi.uusiotunnus = lasku.tunnus)
							{$tilaajanrivinrolisa}
							{$tuotenolisa}
							{$kpllisa})
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.status != 'P')
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND (lasku.tila = 'O' OR (lasku.tila = 'K' AND lasku.alatila != 'X'))
						AND lasku.liitostunnus = '{$toimirow['tunnus']}'
						{$alatilalisa}
						{$tilausnrolisa}
						HAVING onko_lasku = 0
						ORDER BY tilausrivi.tunnus, tilausrivi.uusiotunnus, lasku.tunnus";
			$result = pupe_query($query);

			while ($row = mysql_fetch_assoc($result)) {

				$querylisa = $valitse == 'asn' ? " AND laji = 'asn' " : " AND laji = 'tec' ";

				// katsotaan ettei rivi‰ ole jo kohdistettu muuhun riviin
				$query = "	SELECT tunnus
							FROM asn_sanomat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND toimittajanumero = '{$toimittaja}'
							AND tilausrivi LIKE '%{$row['tunnus']}%'
							{$querylisa}";
				$chkres = pupe_query($query);

				if (mysql_num_rows($chkres) > 0) continue;

				if ($valitse != 'asn' and $toimirow['asn_sanomat'] == 'K' and (!isset($rivit_ei_asnlla) or trim($rivit_ei_asnlla) == '')) {
					// katsotaan ett‰ asn rivi pit‰‰ olla olemassa
					$query = "	SELECT *
								FROM asn_sanomat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND toimittajanumero = '{$toimittaja}'
								AND tilausrivi LIKE '%{$row['tunnus']}%'
								AND laji = 'asn'";
					$chkres = pupe_query($query);

					if (mysql_num_rows($chkres) == 0) continue;
				}

				// katsotaan ett‰ rivi ei ole saapumisella johon on liitetty vaihto-omaisuuslasku
				if ($row['uusiotunnus'] != '') {
					$query = "	SELECT saapuminen.tunnus
								FROM lasku AS saapuminen
								WHERE saapuminen.yhtio = '{$kukarow['yhtio']}'
								AND saapuminen.tunnus = '{$row['uusiotunnus']}'
								AND saapuminen.tapvm = '0000-00-00'";
					$chkres = pupe_query($query);
					if (mysql_num_rows($chkres) != 0) continue;
				}

				echo "<tr>";
				echo "<td align='right'>{$row['otunnus']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td align='right'>{$row['varattu']} / {$row['kpl']}</td>";

				if ($row['uusiotunnus'] != '') {
					$query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['uusiotunnus']}'";
					$keikkares = pupe_query($query);
					$keikkarow = mysql_fetch_assoc($keikkares);
					$row['uusiotunnus'] = $keikkarow['laskunro'];
				}

				echo "<td align='right'>$row[uusiotunnus]</td>";

				if ($row['uusiotunnus'] != '' and $row['kpl'] == 0 and $valitse == 'asn') {
					echo "<td>",t("Rivi on kohdistettu"),"</td>";
					echo "<td>&nbsp;</td>";
				}
				elseif ($valitse == 'asn' and $row['uusiotunnus'] != '' and $row['kpl'] != 0) {
					echo "<td>",t("Viety varastoon"),"</td>";
					echo "<td>&nbsp;</td>";
				}
				else {
					if ($row['uusiotunnus'] != '' and $row['kpl'] != 0) {
						echo "<td>",t("Viety varastoon"),"</td>";
					}
					else {
						echo "<td>&nbsp;</td>";
					}
					echo "<td align='center'>";
					echo "<input type='checkbox' name='tunnukset[]' class='tunnukset' value='{$row['tunnus']}' />";
					echo "<input type='hidden' name='ostotilauksella_kpl[{$row['tunnus']}]' value='{$row['varattu']}' />";
					echo "<input type='hidden' name='ostotilauksella_tilaajanrivinro[{$row['tunnus']}]' value='{$row['tilaajanrivinro']}' />";
					echo "</td>";
				}
				echo "<td><input type='checkbox' name='poista_tilausrivi[]' class='tunnukset' value='{$row['tunnus']}' /></td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'nayta') {

		if ($valitse == 'asn') {
			list($kolli, $asn_numero, $toimittajanumero) = explode("##", $kolli);

			$query = "	SELECT asn_sanomat.toimittajanumero,
						asn_sanomat.toim_tuoteno,
						asn_sanomat.tilausrivinpositio,
						asn_sanomat.kappalemaara,
						asn_sanomat.status,
						asn_sanomat.tilausnumero,
						tilausrivi.tuoteno,
						tilausrivi.otunnus,
						tilausrivi.tilkpl as tilattu,
						if(tilausrivi.ale1 = 0, '', tilausrivi.ale1) AS alennus
						FROM asn_sanomat
						JOIN tilausrivi ON (tilausrivi.yhtio = asn_sanomat.yhtio AND tilausrivi.tunnus IN (asn_sanomat.tilausrivi))
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi!='P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.paketintunniste = '{$kolli}'
						AND asn_sanomat.asn_numero = '{$asn_numero}'
						AND asn_sanomat.toimittajanumero = '{$toimittajanumero}'
						AND asn_sanomat.tilausrivi != ''
						AND asn_sanomat.laji = 'asn'
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);

			echo "<form method='post' action='?valitse={$valitse}&lopetus={$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//kolli={$kolli}//valitse={$valitse}' id='kolliformi'>";
			echo "<input type='hidden' id='tee' name='tee' value='etsi' />";
			echo "<input type='hidden' id='kolli' name='kolli' value='{$kolli}' />";
			echo "<input type='hidden' id='asn_rivi' name='asn_rivi' value='' />";
			echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Toimittajanro"),"</th>";
			echo "<th>",t("Laskun numero"),"</th>";
			echo "<th>",t("Ostotilausnro"),"</th>";
			echo "<th>",t("Tuotenro"),"</th>";
			echo "<th>",t("Toimittajan"),"<br />",t("Tuotenro"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Tilattu kpl"),"</th>";
			echo "<th>",t("Asn-kpl"),"</th>";
			echo "<th>",t("Alennukset"),"</th>";
			echo "<th>",t("Status"),"</th>";
			echo "<td class='back'></td>";
			echo "</tr>";

			$ok = 0;

			while ($row = mysql_fetch_assoc($result)) {

				$ok++;

				echo "<tr>";

				$query = "SELECT nimitys FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$row['tuoteno']}' and status !='P'";
				$tuoteres = pupe_query($query);
				$tuoterow = mysql_fetch_assoc($tuoteres);

				$row['nimitys'] = $tuoterow['nimitys'];

				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>{$row['otunnus']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilattu']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td></td>";
				echo "<td><font class='ok'>",t("Ok"),"</font></td>";
				echo "<td class='back'></td>";
				echo "</tr>";
			}

			$virhe = 0;

			$query = "	SELECT asn_sanomat.toimittajanumero,
						asn_sanomat.toim_tuoteno,
						asn_sanomat.toim_tuoteno2,
						asn_sanomat.tilausrivinpositio,
						asn_sanomat.kappalemaara,
						asn_sanomat.status,
						asn_sanomat.tilausnumero,
						toimi.tunnus AS toimi_tunnus, asn_sanomat.tunnus AS asn_tunnus
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi !='P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.paketintunniste = '{$kolli}'
						AND asn_sanomat.tilausrivi = ''
						AND asn_sanomat.laji = 'asn'
						AND asn_sanomat.asn_numero = '{$asn_numero}'
						AND asn_sanomat.toimittajanumero = '{$toimittajanumero}'
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);


			while ($row = mysql_fetch_assoc($result)) {

				$virhe++;

				echo "<tr>";

				if ($row["toimittajanumero"] == "123067") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty_tuoteno = substr($row["toim_tuoteno"], 0, -3);
					$jatkettu_tuoteno = $lyhennetty_tuoteno."090";

					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}

					$poikkeus_tuoteno =" in ('$orgtuote','$lyhennetty_tuoteno','$jatkettu_tuoteno' $toinen_tuoteno)";
				}
				elseif ($row["toimittajanumero"] == "123453") {
					$suba = substr($row["toim_tuoteno"],0,3);
					$subb = substr($row["toim_tuoteno"],3);
					$tuote = $suba."-".$subb;
					$yhteen = $row["toim_tuoteno"];

					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}

					$poikkeus_tuoteno = " in ('$tuote','$yhteen' $toinen_tuoteno) ";
				}
				elseif ($row["toimittajanumero"] == "123178") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty = substr($row["toim_tuoteno"],3);

					if ($row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = substr($row["toim_tuoteno2"],3);
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}

					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				elseif ($row["toimittajanumero"] == "123084") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty = ltrim($row["toim_tuoteno"],'0');

					if ($row["toim_tuoteno2"] != "") {
						$lyhennetty_toinen = ltrim($row["toim_tuoteno2"],'0');
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}','$lyhennetty_toinen'";
					}
					$poikkeus_tuoteno = " in ('$orgtuote','$lyhennetty' $toinen_tuoteno) ";
				}
				else {

					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}

					$poikkeus_tuoteno = " in ('$row[toim_tuoteno]' $toinen_tuoteno) ";
				}


				$query = "	SELECT tt.tuoteno
							FROM tuotteen_toimittajat as tt
							JOIN tuote on (tuote.yhtio=tt.yhtio and tt.tuoteno = tuote.tuoteno and tuote.status !='P')
							WHERE tt.yhtio = '{$kukarow['yhtio']}'
							AND tt.toim_tuoteno {$poikkeus_tuoteno}
							AND tt.liitostunnus = '{$row['toimi_tunnus']}'";
				$res = pupe_query($query);

				if (mysql_num_rows($res) > 0) {
					$ttrow = mysql_fetch_assoc($res);

					$row['tuoteno'] = $ttrow['tuoteno'];

					$query = "	SELECT nimitys
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$ttrow['tuoteno']}'";
					$tres = pupe_query($query);
					$trow = mysql_fetch_assoc($tres);

					$row['nimitys'] = $trow['nimitys'];

					$query = "	SELECT tuoteno, uusiotunnus, tilaajanrivinro
								FROM tilausrivi
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$row['tilausnumero']}'
								AND tuoteno = '{$row['tuoteno']}'
								AND tilaajanrivinro = '{$row['tilausrivinpositio']}'
								AND tyyppi = 'O'";
					$tilres = pupe_query($query);


					if (mysql_num_rows($tilres) > 0) {
						$tilrow = mysql_fetch_assoc($tilres);
						// $row['nimitys'] = $tilrow['nimitys'];
						$row['uusiotunnus'] = $tilrow['uusiotunnus'];
						$row['tilausrivinpositio'] = $tilrow['tilaajanrivinro'];
					}
					else {
						$row['uusiotunnus'] = 0;
					}
				}
				else {
					$row['tuoteno'] = '';
					$row['nimitys'] = t("Tuntematon tuote");
				}

				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>{$row['tilausnumero']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilattu']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td></td>";

				echo "<td><font class='error'>",t("Virhe"),"</font></td>";

				echo "<td class='back'>";
				if ($row['uusiotunnus'] == 0) echo "<input type='button' class='etsibutton' id={$row['asn_tunnus']}##{$row['tuoteno']}##{$row['tilausrivinpositio']}' value='",t("Etsi"),"' />";
				echo "</td>";

				echo "</tr>";
			}

			if ($ok and !$virhe) {
				echo "<tr><th colspan='10' class='back'><input type='button' class='vahvistabutton' value='",t("Vahvista"),"' /></th></tr>";
				if ($valitse == "asn") {
					echo "<tr><th colspan='10' class='back'><input type='button' class='poistakohdistus' value='",t("Poista Kohdistus"),"' /></th></tr>";
				}
			}

			echo "</table>";
			echo "</form>";
		}
		else {

			echo "<form method='post' action='?valitse={$valitse}' id='kolliformi'>";
			echo "<input type='hidden' id='tee' name='tee' value='etsi' />";
			echo "<input type='hidden' id='lopetus' name='lopetus' value='{$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//lasku={$lasku}//valitse={$valitse}' />";
			echo "<input type='hidden' id='lasku' name='lasku' value='{$lasku}' />";
			echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Toimittajanro"),"</th>";
			echo "<th>",t("Ostotilausnro"),"</th>";
			echo "<th>",t("Tuotenro"),"</th>";
			echo "<th>",t("Toimittajan"),"<br />",t("Tuotenro"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Rivinro"),"</th>";
			echo "<th>",t("Kpl"),"</th>";
			echo "<th>",t("Hinta"),"</th>";
			echo "<th>",t("Status"),"</th>";
			echo "<td class='back'>&nbsp;</td>";
			echo "</tr>";

			$query = "SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND laskunro = '{$lasku}' AND tila = 'H'";
			$laskures = pupe_query($query);

			if (mysql_num_rows($laskures) == 0) {
				$query = "SELECT liitostunnus FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND comments = '{$lasku}' AND tila = 'H'";
				$laskures = pupe_query($query);
			}

			$laskurow = mysql_fetch_assoc($laskures);

			$query = "	SELECT asn_sanomat.toimittajanumero,
						asn_sanomat.toim_tuoteno,
						asn_sanomat.toim_tuoteno2,
						asn_sanomat.tuoteno,
						asn_sanomat.tilausrivinpositio,
						asn_sanomat.status,
						asn_sanomat.tilausnumero,
						asn_sanomat.kappalemaara,
						asn_sanomat.tilausrivi,
						asn_sanomat.hinta,
						asn_sanomat.keikkarivinhinta,
						asn_sanomat.tunnus,
						toimi.asn_sanomat
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero AND toimi.tyyppi != 'P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.asn_numero LIKE '%{$lasku}'
						AND asn_sanomat.laji = 'tec'
						#AND asn_sanomat.tilausrivi != ''
						ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
			$result = pupe_query($query);

			$ok = $virhe = 0;
			$hintapoikkeavuus = false;

			while ($row = mysql_fetch_assoc($result)) {

				$query = "	SELECT tuote.tuoteno
							FROM tuotteen_toimittajat
							JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
							AND tuotteen_toimittajat.toim_tuoteno IN ('{$row['toim_tuoteno']}', '{$row['toim_tuoteno2']}')
							AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}'";
				$res = pupe_query($query);

				if (mysql_num_rows($res) > 0 and $row['tuoteno'] == '') {
					$ttrow = mysql_fetch_assoc($res);

					$row['tuoteno'] = $ttrow['tuoteno'];

					$query = "	SELECT nimitys
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$ttrow['tuoteno']}'
								AND status != 'P'";
					$tres = pupe_query($query);
					$trow = mysql_fetch_assoc($tres);

					$row['nimitys'] = $trow['nimitys'];
				}

				if ($row['tilausrivi'] != '') {
					$query = "SELECT hinta, otunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ({$row['tilausrivi']})";
					$hinta_chk_res = pupe_query($query);
					$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

					$row['tilausnumero'] = $hinta_chk_row['otunnus'];
				}

				echo "<tr>";
				echo "<td align='right'>{$row['toimittajanumero']}</td>";
				echo "<td align='right'>{$row['tilausnumero']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['toim_tuoteno']}</td>";
				echo "<td>{$row['nimitys']}</td>";
				echo "<td align='right'>{$row['tilausrivinpositio']}</td>";
				echo "<td align='right'>{$row['kappalemaara']}</td>";
				echo "<td align='right'>",hintapyoristys($row['hinta']),"</td>";

				echo "<td>";

				if ($row['status'] == 'E') {
					echo "<font class='message'>",t("Erolistalla"),"</font>";
				}
				elseif ($row['tilausrivi'] != '') {
					echo "<font class='ok'>Ok</font>";
					$ok++;

					if ($row['hinta'] != $hinta_chk_row['hinta']) {
						echo "<br /><font class='error'>",t("Hintapoikkeavuus"),"</font>";
						$hintapoikkeavuus = true;
					}
				}
				else {
					echo "<font class='error'>",t("Virhe"),"</font>";
					$virhe++;
				}
				echo "</td>";

				echo "<td class='back'>";

				if ($row['tilausrivi'] == '' and $row['status'] != 'E') {
					echo "<input type='button' class='etsibutton_osto' id='{$lasku}##{$row['tuoteno']}##{$row['tilausrivinpositio']}##{$row['toimittajanumero']}##{$row['kappalemaara']}##{$row['tunnus']}##{$row['tilausnumero']}##{$row['toim_tuoteno']}' value='",t("Etsi"),"' />";
					echo "<input type='button' class='erobutton_osto' id='{$lasku}##{$row['tuoteno']}##{$row['tilausrivinpositio']}##{$row['toimittajanumero']}##{$row['kappalemaara']}##{$row['tunnus']}##{$row['tilausnumero']}##{$row['toim_tuoteno']}' value='",t("Erolistalle"),"' />";
				}

				echo "</td>";
				echo "</tr>";
			}

			if ($ok and !$virhe) {
				if ($hintapoikkeavuus) {
					echo "<tr><th colspan='9'><input type='button' class='hintapoikkeavuusbutton' value='",t("Hintapoikkeavuus raportti"),"' /></th></tr>";
				}

				echo "<tr><th colspan='9'><input type='button' class='vahvistabutton' value='",t("Vahvista"),"' /></th></tr>";
			}

			echo "<tr><th colspan='9'><input type='button' class='vahvistavakisinbutton' value='",t("Vahvista v‰kisin"),"' /></th></tr>";

			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == '') {

		if (isset($nayta_asn)) {
			$valitse = "asn";
		}
		elseif (isset($nayta_ostolasku)) {
			$valitse = "ostolasku";
		}

		if ($valitse == '') {
			echo "<form method='post' action='?tee='>";
			echo "<table><tr>";
			echo "<th>",t("Valitse"),"</th>";
			echo "<td><input type='submit' name='nayta_ostolasku' value='",t("Ostolaskut"),"' /></td>";
			echo "<td><input type='submit' name='nayta_asn' value='",t("ASN-sanomat"),"' /></td>";
			echo "</tr></table>";
			echo "</form>";
		}
		elseif ($valitse == 'asn') {
				$query = "	SELECT 	toimi.ytunnus,
								toimi.nimi,
								toimi.nimitark,
								toimi.osoite,
								toimi.osoitetark,
								toimi.postino,
								toimi.postitp,
								toimi.maa,
								toimi.swift,
								asn_sanomat.asn_numero,
								asn_sanomat.paketintunniste,
								asn_sanomat.toimittajanumero,
								asn_sanomat.status,
								count(asn_sanomat.tunnus) AS rivit,
								sum(if(asn_sanomat.tilausrivi != '', 1, 0)) AS ok
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi !='P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.laji = 'asn'
						GROUP BY asn_sanomat.paketintunniste, asn_sanomat.asn_numero, asn_sanomat.toimittajanumero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
						ORDER BY asn_sanomat.asn_numero, asn_sanomat.paketintunniste";
			$result = pupe_query($query);

			echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=asn&tee=' id='formi'>";
			echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
			echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
			echo "<input type='hidden' id='kolli' name='kolli' value='' />";
			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Ytunnus"),"</th>";
			echo "<th>",t("Nimi"),"</th>";
			echo "<th>",t("Osoite"),"</th>";
			echo "<th>",t("Swift"),"</th>";
			echo "<th>",t("ASN sanomanumero"),"</th>";
			echo "<th>",t("ASN kollinumero"),"</th>";
			echo "<th>",t("Rivim‰‰r‰"),"<br />",t("ok")," / ",t("kaikki"),"</th>";
			echo "</tr>";

			$ed_asn = '';
			$ed_toimittaja = '';
			$naytetaanko_toimittajabutton = true;

			while ($row = mysql_fetch_assoc($result)) {
				$naytetaanko_toimittajabutton = true;

				// n‰ytet‰‰n vain vialliset rivit
				if ($row["rivit"] == $row["ok"] and $row["status"] == "X") {
					continue;
				}

				if ($ed_toimittaja != '' and $ed_toimittaja != $row['toimittajanumero']) {

					if ($naytetaanko_toimittajabutton) {
						echo "<tr><th colspan='7'><input type='button' class='toimittajabutton' id='{$ed_asn}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
					}

					echo "<tr><td colspan='8' class='back'>&nbsp;</td></tr>";
				}

				echo "<tr>";
				echo "<td>{$row['ytunnus']}</td>";

				echo "<td>{$row['nimi']}";
				if (trim($row['nimitark']) != '') echo " {$row['nimitark']}";
				echo "</td>";

				echo "<td>{$row['osoite']} ";
				if (trim($row['osoitetark']) != '') echo "{$row['osoitetark']} ";
				echo "{$row['postino']} {$row['postitp']} {$row['maa']}</td>";

				echo "<td>{$row['swift']}</td>";
				echo "<td align='right'>{$row['asn_numero']}</td>";
				echo "<td>{$row['paketintunniste']}</td>";
				echo "<td>{$row['ok']} / {$row['rivit']}</td>";
				echo "<td class='back'><input type='button' class='kollibutton' id='{$row['paketintunniste']}##{$row['asn_numero']}##{$row['toimittajanumero']}' value='",t("Valitse"),"' /></td>";
				echo "</tr>";

				if (($ed_toimittaja == '' or $ed_toimittaja == $row['toimittajanumero']) and $row['ok'] == $row['rivit']) {
					$naytetaanko_toimittajabutton = false;
				}

				$ed_asn = $row['asn_numero'];
				$ed_toimittaja = $row['toimittajanumero'];
			}

			if (mysql_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
				echo "<tr><th colspan='7'><input type='button' class='toimittajabutton' id='{$ed_asn}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
			}

			echo "</table>";
			echo "</form>";
		}
		elseif ($valitse == 'ostolasku') {
			$query = "	SELECT 	toimi.ytunnus,
								toimi.nimi,
								toimi.nimitark,
								toimi.osoite,
								toimi.osoitetark,
								toimi.postino,
								toimi.postitp,
								toimi.maa,
								toimi.swift,
								asn_sanomat.asn_numero as tilausnumero,
								asn_sanomat.paketintunniste,
								asn_sanomat.toimittajanumero,
								count(asn_sanomat.tunnus) AS rivit,
								sum(if(asn_sanomat.tilausrivi != '', 1, 0)) AS ok
						FROM asn_sanomat
						JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero AND toimi.tyyppi != 'P')
						WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
						AND asn_sanomat.laji = 'tec'
						AND asn_sanomat.status NOT IN ('X', 'E')
						GROUP BY asn_sanomat.asn_numero, asn_sanomat.toimittajanumero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
						ORDER BY toimi.nimi, toimi.ytunnus";
			$result = pupe_query($query);

			echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=ostolasku&tee=' id='formi'>";
			echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
			echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
			echo "<input type='hidden' id='lasku' name='lasku' value='' />";
			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Ytunnus"),"</th>";
			echo "<th>",t("Nimi"),"</th>";
			echo "<th>",t("Osoite"),"</th>";
			echo "<th>",t("Swift"),"</th>";
			echo "<th>",t("Ostolaskunro"),"</th>";
			echo "<th>",t("Rivim‰‰r‰"),"<br />",t("ok")," / ",t("kaikki"),"</th>";
			echo "</tr>";

			$ed_toimittaja = '';
			$ed_tilausnumero = '';
			$naytetaanko_toimittajabutton = true;

			while ($row = mysql_fetch_assoc($result)) {

				if ($ed_toimittaja != '' and $ed_toimittaja != $row['toimittajanumero']) {

					if ($naytetaanko_toimittajabutton) {
						echo "<tr><th colspan='6'><input type='button' class='toimittajabutton' id='{$ed_tilausnumero}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
					}

					echo "<tr><td colspan='7' class='back'>&nbsp;</td></tr>";
				}

				echo "<tr>";
				echo "<td>{$row['ytunnus']}</td>";
				echo "<td>{$row['nimi']}</td>";
				echo "<td>{$row['osoite']} {$row['postino']} {$row['postitp']} {$row['maa']}</td>";
				echo "<td>{$row['swift']}</td>";
				echo "<td>{$row['tilausnumero']}</td>";
				echo "<td>{$row['ok']} / {$row['rivit']}</td>";
				echo "<td class='back'><input type='button' class='ostolaskubutton' id='{$row['tilausnumero']}' value='",t("Valitse"),"' /></td>";
				echo "</tr>";

				if (($ed_toimittaja == '' or $ed_toimittaja == $row['toimittajanumero']) and $row['ok'] == $row['rivit']) {
					$naytetaanko_toimittajabutton = false;
				}

				$ed_toimittaja = $row['toimittajanumero'];
				$ed_tilausnumero = $row['tilausnumero'];
			}

			if (mysql_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
				echo "<tr><th colspan='6'><input type='button' class='toimittajabutton' id='{$ed_tilausnumero}' value='",t("Vaihda toimittajaa"),"' /></th></tr>";
			}

			echo "</table>";
			echo "</form>";
		}
	}

	require ("inc/footer.inc");