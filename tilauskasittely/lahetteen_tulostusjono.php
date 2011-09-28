<?php

	require ("../inc/parametrit.inc");

	if ($toim == "VASTAANOTA_REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] != 'U') {
		echo "<font class='error'>".t("HUOM: Ohjelma on k�yt�ss� vain kun k�ytet��n laajaa reklamaatioprosessia")."!</font>";
		exit;
	}

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';
	$lasku_yhtio_originaali = $kukarow['yhtio'];

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

	$DAY_ARRAY = array(1 => t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

	js_popup();

	if ($toim == 'SIIRTOLISTA') {
		$tila 				= "G";
		$lalatila			= "J";
		$tila_lalatila_lisa = "";
		$tilaustyyppi 		= " and tilaustyyppi!='M' ";
	}
	elseif ($toim == 'SIIRTOTYOMAARAYS') {
		$tila 				= "S";
		$lalatila			= "J";
		$tila_lalatila_lisa = "";
		$tilaustyyppi 		= " and tilaustyyppi='S' ";
	}
	elseif ($toim == 'MYYNTITILI') {
		$tila 				= "G";
		$lalatila			= "J";
		$tila_lalatila_lisa = "";
		$tilaustyyppi 		= " and tilaustyyppi='M' ";
	}
	elseif ($toim == 'VALMISTUS') {
		$tila 				= "V";
		$lalatila			= "J";
		$tila_lalatila_lisa = "";
		$tilaustyyppi 		= "";
	}
	elseif ($toim == 'VALMISTUSMYYNTI') {
		$tila 				= "V";
		$lalatila			= "J";
		$tila_lalatila_lisa = " or (lasku.tila='N' and lasku.alatila='A')";
		$tilaustyyppi 		= "";
	}
	elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
		$tila 				= "C";
		$lalatila			= "B";
		$tila_lalatila_lisa = "";
		$tilaustyyppi 		= "";
	}
	else {
		$tila 				= "N";
		$lalatila			= "A";
		$tila_lalatila_lisa = "";
		$tilaustyyppi 		= "";
	}

	if ($tee2 == 'NAYTATILAUS') {

		if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
			echo "<font class='head'>",t("Yhti�n")," $yhtiorow[nimi] ",t("tilaus")," $tunnus:</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		}

		require ("raportit/naytatilaus.inc");
		echo "<br><br><br>";
		$tee2 = $vanha_tee2;

		if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
			$logistiikka_yhtio = $konsernivarasto_yhtiot;
		}
	}

	if ($tee2 == 'TULOSTA') {

		unset($tilausnumerorypas);
		$tulostetaanko_kaikki = "";

		if (isset($tulostukseen) and ($toim == 'VALMISTUS' or $toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS' or $toim == 'MYYNTITILI')) {
			$lask 	= 0;

			foreach ($tulostukseen as $tun) {
				$tilausnumerorypas[] = $tun;
				$lask++;
			}

			//ja niiden lukum��r�
			$laskuja = $lask;
		}
		elseif (isset($tulostukseen)) {
			$laskut	= "";
			$lask 	= 0;

			foreach ($tulostukseen as $tun) {
				$laskut .= "$tun,";
				$lask++;
			}

			//tulostettavat tilausket
			$tilausnumerorypas[] = substr($laskut,0,-1);
			//ja niiden lukum��r�
			$laskuja = $lask;
		}
		elseif (isset($tulostukseen_kaikki)) {
			$tilausnumerorypas = unserialize(urldecode($tulostukseen_kaikki));
			$tulostukseen_kaikki = "KYLLA";
		}

		if (is_array($tilausnumerorypas)) {
			foreach ($tilausnumerorypas as $tilausrypas_key => $tilausrypas_value) {

				if ($tulostukseen_kaikki == 'KYLLA') {
					$kukarow['yhtio'] = $tilausrypas_value;
					$tilausnumeroita = $tilausrypas_key;
				}
				else {
					$tilausnumeroita = $tilausrypas_value;
				}

				$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

				// katsotaan, ettei tilaus ole kenell�k��n auki ruudulla
				$query = "	SELECT *
							FROM kuka
							WHERE kesken in ($tilausnumeroita)
							and yhtio='$kukarow[yhtio]'";
				$keskenresult = mysql_query($query) or pupe_error($query);

				//jos kaikki on ok...
				if (mysql_num_rows($keskenresult)==0) {

					$query    = "	SELECT *
									from lasku
									where tunnus in ($tilausnumeroita)
									and ((tila = '$tila' and alatila = '$lalatila') $tila_lalatila_lisa)
									and yhtio	= '$kukarow[yhtio]'
									LIMIT 1";
					$result   = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) > 0) {

						$laskurow = mysql_fetch_array($result);

						// jos tulostetaan kaikki ruudun ker�yslistat, k�ytet��n ainoastaa EIPAKKAAMOA oletusta
						if ($tulostukseen_kaikki == "KYLLA") {
							$query = "	SELECT ei_pakkaamoa
										FROM toimitustapa
										WHERE yhtio = '$kukarow[yhtio]'
										AND selite = '$laskurow[toimitustapa]'";
							$ei_pakkaamoa_res = mysql_query($query) or pupe_error($query);
							$ei_pakkaamoa_row = mysql_fetch_assoc($ei_pakkaamoa_res);

							if ($ei_pakkaamoa_row['ei_pakkaamoa'] == '1' or $tilrow["t_tyyppi"] == "E") {
								$ei_pakkaamoa = "X";
							}
							else {
								$ei_pakkaamoa = "";
							}
						}

						if ($laskurow["tila"] == 'G' or $laskurow["tila"] == 'S') {
							$tilausnumero	= $laskurow["tunnus"];
							$tee			= "valmis";
							$tulostetaan	= "OK";

							require("tilaus-valmis-siirtolista.inc");

						}
						elseif ($laskurow["tila"] == 'V') {
							$tilausnumero	= $laskurow["tunnus"];
							$tulostetaan	= "OK";

							$toim_bck		= $toim;
							$toim 			= "VALMISTAVARASTOON";

							require("tilaus-valmis-siirtolista.inc");

							$toim 			= $toim_bck;
						}
						elseif ($laskurow["tila"] == 'C' and $laskurow["alatila"] == 'B') {
							$tee			= "VALMIS";
							$tulostetaan	= "OK";
							$toim_bck		= $toim;
							$takas 			= 1;
							$tyyppi 		= "REKLAMAATIO";

							require("tilaus-valmis-tulostus.inc");

						}
						else {
							require("tilaus-valmis-tulostus.inc");
						}
					}
					else {
						echo "<font class='error'>".t("Ker�yslista on jo tulostettu")."! ($tilausnumeroita)</font><br>";
					}
				}
				else {
					$keskenrow = mysql_fetch_array($keskenresult);
					echo t("Tilaus on kesken k�ytt�j�ll�").", $keskenrow[nimi], ".t("ota yhteytt� h�neen ja k�ske h�nen laittaa v�h�n vauhtia t�h�n touhuun")."!<br>";
					$tee2 = "";
				}
			}
		}
		else {
			echo "<font class='error'>".t("Et valinnut mit��n tulostettavaa")."!</font><br>";
		}
		$tee2 = "";
	}

	// valiitaan ker�yskl�ntin tilaukset jotka tulostetaan
	if ($tee2 == 'VALITSE') {

		//Haetaan sopivat tilaukset
		$query = "	SELECT lasku.tunnus, lasku.ytunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto,
					if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos) prioriteetti,
					if (min(lasku.clearing)='','N',if (min(lasku.clearing)='JT-TILAUS','J',if (min(lasku.clearing)='ENNAKKOTILAUS','E',''))) t_tyyppi,
					left(min(lasku.kerayspvm),10) kerayspvm,
					left(min(lasku.toimaika),10) toimaika,
					min(keraysvko) keraysvko,
					min(toimvko) toimvko,
					varastopaikat.nimitys varastonimi,
					varastopaikat.tunnus varastotunnus,
					lasku.tunnus otunnus,
					lasku.viesti,
					lasku.sisviesti2,
					GROUP_CONCAT(if (kommentti='',NULL,kommentti) separator '<br>') AS kommentit,
					count(*) riveja,
					lasku.yhtio yhtio,
					lasku.yhtio_nimi yhtio_nimi
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus
					LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tunnus in ($tilaukset)
					$tilaustyyppi
					GROUP BY lasku.tunnus
					ORDER BY prioriteetti, kerayspvm";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre)==0) {
			$tee2 		= "";
			$tuytunnus 	= "";
			$tuvarasto	= "";
			$tumaa		= "";
		}
		else {
			// katsotaan, ettei tilaus ole kenell�k��n auki ruudulla
			$query = "	SELECT *
						FROM kuka
						WHERE kesken in ($tilaukset)
						and yhtio='$kukarow[yhtio]'";
			$keskenresult = mysql_query($query) or pupe_error($query);

			//jos kaikki on ok...
			if (mysql_num_rows($keskenresult)==0) {

				if ($toim == 'SIIRTOLISTA') {
					echo "<font class='head'>".t("Tulosta siirtolista").":</font><hr>";
				}
				elseif ($toim == 'SIIRTOTYOMAARAYS') {
					echo "<font class='head'>".t("Tulosta sis�inen ty�m��r�ys").":</font><hr>";
				}
				elseif ($toim == 'VALMISTUS') {
					echo "<font class='head'>".t("Tulosta valmistuslista").":</font><hr>";
				}
				elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
					echo "<font class='head'>".t("Tulosta purkulista").":</font><hr>";
				}
				else {
					echo "<font class='head'>".t("Tulosta ker�yslista").":</font><hr>";
				}

				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='toim' 			value='$toim'>";
				echo "<input type='hidden' name='jarj' 			value='$jarj'>";
				echo "<input type='hidden' name='tuvarasto' 	value='$tuvarasto'>";
				echo "<input type='hidden' name='tumaa' 		value='$tumaa'>";
				echo "<input type='hidden' name='tutyyppi' 		value='$tutyyppi'>";
				echo "<input type='hidden' name='tutoimtapa'	value='$tutoimtapa'>";
				echo "<input type='hidden' name='karajaus' 		value='$karajaus'>";
				echo "<input type='hidden' name='tee2' 			value='TULOSTA'>";

				echo "<table>";
				echo "<tr>";
				if ($logistiikka_yhtio != '') {
					echo "<th>",t("Yhti�"),"</th>";
				}
				echo "<th>".t("Pri")."</th>";
				echo "<th>".t("Varastoon")."</th>";
				echo "<th>".t("Tilaus")."</th>";
				echo "<th>".t("Asiakas")."</th>";
				echo "<th>".t("Nimi")."</th>";
				echo "<th>".t("Viite")."</th>";
				echo "<th>".t("Ker�yspvm")."</th>";

				if ($kukarow['resoluutio'] == 'I') {
					echo "<th>".t("Toimaika")."</th>";
				}

				echo "<th>".t("Riv")."</th>";
				echo "<th>".t("Tulosta")."</th>";
				echo "<th>".t("N�yt�")."</th>";
				echo "</tr>";

				$keskenlask = 0;

				while ($tilrow = mysql_fetch_array($tilre)) {

					$keskenlask ++;
					//otetaan t�m� muutuja talteen
					$tul_varastoon = $tilrow["varasto"];

					echo "<tr class='aktiivi'>";

					$ero="td";
					if ($tunnus==$tilrow['otunnus']) $ero="th";

					if ($logistiikka_yhtio != '') {
						echo "<$ero valign='top'>$tilrow[yhtio_nimi]</$ero>";
					}

					if (trim($tilrow["sisviesti2"]) != "" or trim($tilrow['kommentit'] != '')) {
						echo "<div id='div_$tilrow[otunnus]' class='popup' style='width:500px;'>";

						if (trim($tilrow["sisviesti2"]) != "") {
							echo t("Lis�tiedot").":<br>";
							echo $tilrow["sisviesti2"];
							echo "<br>";
						}
						if (trim($tilrow['kommentit'] != '')) {
							echo t("Rivikommentit").":<br>";
							echo $tilrow["kommentit"];
							echo "<br>";
						}

						echo "</div>";
						echo "<$ero valign='top' class='tooltip' id='$tilrow[otunnus]'>$tilrow[t_tyyppi] $tilrow[prioriteetti] <img src='$palvelin2/pics/lullacons/info.png'></$ero>";


					}
					else {
						echo "<$ero valign='top'>$tilrow[t_tyyppi] $tilrow[prioriteetti]</$ero>";
					}

					echo "<$ero valign='top'>$tilrow[varastonimi]</$ero>";
					echo "<$ero valign='top'>$tilrow[tunnus]</$ero>";
					echo "<$ero valign='top'>$tilrow[ytunnus]</$ero>";

					$nimitarklisa = "";

					if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
						echo "<$ero valign='top'>$tilrow[nimi]</$ero>";
					}
					else {
						if ($tilrow['toim_nimitark'] != '') {
							$nimitarklisa = ", $tilrow[toim_nimitark]";
						}
						echo "<$ero valign='top'>$tilrow[toim_nimi]$nimitarklisa</$ero>";
					}

					echo "<$ero valign='top'>$tilrow[viesti]</$ero>";

					if ($tilrow['keraysvko'] != '') {
						echo "<$ero valign='top' nowrap>".t("Vko")." ".date("W", strtotime($tilrow["kerayspvm"]));

						if ($tilrow['keraysvko'] != '7') {
							echo "/".$DAY_ARRAY[$tilrow["keraysvko"]];
						}

						echo "</$ero>";
					}
					else {
						echo "<$ero valign='top'>".tv1dateconv($tilrow["kerayspvm"])."</$ero>";
					}


					if ($kukarow['resoluutio'] == 'I') {
						if ($tilrow["toimvko"] != '') {
							echo "<$ero valign='top' nowrap>".t("Vko")." ".date("W", strtotime($tilrow["toimaika"]));

							if ($tilrow['toimvko'] != '7') {
								echo "/".$DAY_ARRAY[$tilrow["toimvko"]];
							}

							echo "</$ero>";
						}
						else {
							echo "<$ero valign='top'>".tv1dateconv($tilrow["toimaika"])."</$ero>";
						}

					}

					echo "<$ero valign='top'>$tilrow[riveja]</$ero>";
					echo "<$ero valign='top'><input type='checkbox' name='tulostukseen[]' value='$tilrow[otunnus]' CHECKED></$ero>";

					echo "<$ero valign='top'><a href='$PHP_SELF?lasku_yhtio=$kukarow[yhtio]&toim=$toim&tilaukset=$tilaukset&vanha_tee2=VALITSE&tee2=NAYTATILAUS&tunnus=$tilrow[otunnus]'>".t("N�yt�")."</a></$ero>";

					echo "</tr>";
				}
			}
			else {
				$keskenrow = mysql_fetch_array($keskenresult);
				echo t("Tilaus on kesken k�ytt�j�ll�").", $keskenrow[nimi], ".t("ota yhteytt� h�neen ja k�ske h�nen laittaa v�h�n vauhtia t�h�n touhuun")."!<br>";
				$tee2 = '';
			}

			if ($tee2 != '') {
				echo "</table><br>";
				echo "<table>";

				if ($yhtiorow["pakkaamolokerot"] != "") {

					echo "<tr>";
					echo "<th>",t("Ei lokeroa"),"</th>";

					$ei_pakkaamoa_sel = '';
					if ($tila == 'N' or ($toim == 'SIIRTOLISTA' and $tila == "G")) {

						if (mysql_num_rows($tilre) > 0) {
							mysql_data_seek($tilre, 0);
							$tilrow = mysql_fetch_array($tilre);
						}

						$query = "	SELECT ei_pakkaamoa
									FROM toimitustapa
									WHERE yhtio = '$kukarow[yhtio]'
									AND selite = '$tilrow[toimitustapa]'";
						$ei_pakkaamoa_res = mysql_query($query) or pupe_error($query);

						$ei_pakkaamoa_row = mysql_fetch_assoc($ei_pakkaamoa_res);

						if ($ei_pakkaamoa_row['ei_pakkaamoa'] == '1' or $tilrow["t_tyyppi"] == "E") {
							$ei_pakkaamoa_sel = "checked";
						}
					}

					echo "<td valign='top'><input type='checkbox' name='ei_pakkaamoa' id='ei_pakkaamoa' value='EI' $ei_pakkaamoa_sel>";
					echo "<input type='hidden' name='ei_pakkaamoa_selected' id='ei_pakkaamoa_selected' value='$ei_pakkaamoa_sel'>";
					echo "</td>";
					echo "</tr>";
				}

				//haetaan ker�yslistan oletustulostin
				$query = "	SELECT *
							from varastopaikat
							where yhtio = '$kukarow[yhtio]' and tunnus = '$tul_varastoon'";
				$prires = mysql_query($query) or pupe_error($query);
				$prirow = mysql_fetch_array($prires);
				$kirjoitin = $prirow['printteri0'];

				$varasto = $tul_varastoon;
				$tilaus  = $tilaukset;

				require("varaston_tulostusalue.inc");

				echo "<tr>";
				echo "<th>",t("Tulostin"),"</th>";
				echo "<td>";

				$query = "	SELECT *
							FROM kirjoittimet
							WHERE
							yhtio = '$kukarow[yhtio]'
							ORDER by kirjoitin";
				$kirre = mysql_query($query) or pupe_error($query);

				echo "<select name='valittu_tulostin'>";

				while ($kirrow = mysql_fetch_array($kirre)) {
					$sel = '';

					//t�ss� vaiheessa k�ytt�j�n oletustulostin ylikirjaa optimaalisen varastotulostimen
					if (($kirrow['tunnus'] == $kirjoitin and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				echo "</select></td></tr>";
				echo "</table><br><br>";
				echo "<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>";
				echo "<input type='submit' name='tila' value='".t("Tulosta")."'></form>";
			}
		}
	}

	//valitaan ker�yskl�ntti
	if ($tee2 == '') {

		if ($lasku_yhtio_originaali != '' and $kukarow['yhtio'] != $lasku_yhtio_originaali) {
			$logistiikka_yhtio = $konsernivarasto_yhtiot;
			$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio_originaali);
			$kukarow['yhtio'] = $lasku_yhtio_originaali;
		}

		if ($toim == 'SIIRTOLISTA') {
			echo "<font class='head'>".t("Tulosta siirtolista").":</font><hr>";
		}
		elseif ($toim == 'SIIRTOTYOMAARAYS') {
			echo "<font class='head'>".t("Tulosta sis�inen ty�m��r�ys").":</font><hr>";
		}
		elseif ($toim == 'VALMISTUS') {
			echo "<font class='head'>".t("Tulosta valmistuslista").":</font><hr>";
		}
		elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
			echo "<font class='head'>".t("Tulosta purkulista").":</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Tulosta ker�yslista").":</font><hr>";
		}

		/*
			Oletuksia
		*/
		if (isset($indexvas) and $indexvas == 1) {

			$karajaus = 1;

			if ($yhtiorow["keraysaikarajaus"] != "") {
				$karajaus = $yhtiorow["keraysaikarajaus"];
			}

			//	Varastorajaus jos k�ytt�j�ll� on joku varasto valittuna
			if ($kukarow['varasto'] != '' and $kukarow['varasto'] != 0) {
				$tuvarasto 	= $kukarow['varasto'];
			}
			else {
				$tuvarasto 	= "KAIKKI";
			}

			$tutoimtapa = "KAIKKI";
			$tutyyppi 	= "KAIKKI";
		}

		$haku = '';

		if (is_numeric($karajaus)) {
			$haku .= " and lasku.kerayspvm<=date_add(now(), INTERVAL $karajaus day)";
		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			if (strpos($tuvarasto,"##")) {
				$temp_tuvarasto = explode("##",$tuvarasto);
				$haku .= " and lasku.varasto='$temp_tuvarasto[0]' and lasku.tulostusalue = '$temp_tuvarasto[1]'";
			}
			else {
				$haku .= " and lasku.varasto in ($tuvarasto) ";
			}
		}

		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != ''
						and $logistiikka_yhtiolisa
						and maa = '$tumaa'";
			$maare = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_array($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif ($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif ($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		if (!is_numeric($etsi) and $etsi != '') {
			$haku = "and (lasku.nimi LIKE '%$etsi%' or lasku.toim_nimi LIKE '%$etsi%')";
		}

		if (is_numeric($etsi) and $etsi != '') {
			$haku = "and lasku.tunnus='$etsi'";
		}

		$formi	= "find";
		$kentta	= "etsi";

		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' id='jarj' name='jarj' value='$jarj'>";

		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT lasku.yhtio_nimi, varastopaikat.tunnus, varastopaikat.nimitys, lasku.tulostusalue, count(*) kpl
					FROM varastopaikat
					JOIN lasku ON (varastopaikat.yhtio = lasku.yhtio and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa) $tilaustyyppi and lasku.varasto = varastopaikat.tunnus)
					WHERE varastopaikat.$logistiikka_yhtiolisa
					GROUP BY lasku.yhtio_nimi, varastopaikat.tunnus, varastopaikat.nimitys, lasku.tulostusalue
					ORDER BY varastopaikat.tyyppi, varastopaikat.nimitys, lasku.tulostusalue, varastopaikat.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		$sel = array();
		$sel[$tuvarasto] = "SELECTED";

		while ($row = mysql_fetch_array($result)){

			if ($row['tulostusalue'] != '') {
				echo "<option value='$row[tunnus]##$row[tulostusalue]' ".$sel[$row['tunnus']."##".$row['tulostusalue']].">$row[nimitys] $row[tulostusalue] ($row[kpl])";
			}
			else {
				echo "<option value='$row[tunnus]' ".$sel[$row['tunnus']].">$row[nimitys] ($row[kpl])";
			}

			if ($logistiikka_yhtio != '') {
				echo " ($row[yhtio_nimi])";
			}

			echo "</option>";
		}
		echo "</select>";

		$query = "	SELECT varastopaikat.maa, count(*) kpl
					FROM varastopaikat
					JOIN lasku ON (varastopaikat.yhtio = lasku.yhtio and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa) $tilaustyyppi and lasku.varasto = varastopaikat.tunnus)
					WHERE varastopaikat.maa != '' and varastopaikat.$logistiikka_yhtiolisa
					GROUP BY varastopaikat.maa
					ORDER BY varastopaikat.maa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			$sel=array();
			$sel[$tumaa] = "selected";
			while ($row = mysql_fetch_array($result)){
				echo "<option value='$row[maa]' ".$sel[$row['maa']].">$row[maa] ($row[kpl])</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

		$query = "	SELECT clearing, count(*) kpl
					FROM lasku
					WHERE $logistiikka_yhtiolisa
					and ((tila = '$tila' and alatila = '$lalatila') $tila_lalatila_lisa) $tilaustyyppi
					GROUP BY clearing
					ORDER by clearing";

		$result = mysql_query($query) or pupe_error($query);
		$sel=array();
		$sel[$tutyyppi]="selected";

		echo "<option value='KAIKKI' $seltuty[KAIKKI]>".t("N�yt� kaikki")."</option>";

		if (mysql_num_rows($result)>0) {
			while ($row = mysql_fetch_array($result)) {
				if ($row["clearing"] == "") {
					echo "<option value='NORMAA' $sel[NORMAA]>".t("N�yt� normaalitilaukset")." ($row[kpl])</option>";
				}
 				elseif ($row["clearing"] == "ENNAKKOTILAUS") {
					echo "<option value='ENNAKK' $sel[ENNAKK]>".t("N�yt� ennakkotilaukset")." ($row[kpl])</option>";
				}
 				elseif ($row["clearing"] == "JT-TILAUS") {
					echo "<option value='JTTILA' $sel[JTTILA]>".t("N�yt� jt-tilaukset")." ($row[kpl])</option>";
				}
			}
		}

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT toimitustapa.selite, count(*) kpl, MIN(toimitustapa.tunnus) tunnus
					FROM toimitustapa
					JOIN lasku ON (toimitustapa.yhtio = lasku.yhtio and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa) $tilaustyyppi and lasku.toimitustapa = toimitustapa.selite)
					WHERE toimitustapa.$logistiikka_yhtiolisa
					GROUP BY toimitustapa.selite
					ORDER BY toimitustapa.selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		$sel=array();
		$sel[$tutoimtapa] = "selected";
		while ($row = mysql_fetch_array($result)){
			echo "<option value='$row[selite]' ".$sel[$row["selite"]].">".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")." ($row[kpl])</option>";
		}

		echo "</select></td><td></td><td></td>";

		$sel=array();
		$sel[$karajaus] = "selected";

		echo "<tr><td>".t("Ker�ysaikarajaus:")."</td>
				<td>
					<select name='karajaus' onchange='submit()'>
						<option value='1'  $sel[1]>".t("Huominen")."</option>
						<option value='3'  $sel[3]>".t("Seuraavat 3 p�iv��")."</option>
						<option value='5'  $sel[5]>".t("Seuraavat 5 p�iv��")."</option>
						<option value='7'  $sel[7]>".t("Seuraava viikko")."</option>
						<option value='14' $sel[14]>".t("Seuraavat 2 viikkoa")."</option>
						<option value='KK' $sel[KK]>".t("N�yt� kaikki")."</option>
					</select>

				</td>";
		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></td></tr>";

		echo "</table>";

		if ($jarj != "") {
			$jarjx = " ORDER BY t_tyyppi desc, $jarj ";
		}
		else {
			$jarjx = " ORDER BY t_tyyppi desc, prioriteetti, kerayspvm ";
		}

		if ($toim == 'SIIRTOLISTA') {
			$selectlisa = " if (lasku.chn = 'GEN', '2', '1') t_tyyppi2, ";
		}
		else {
			$selectlisa = " if (lasku.clearing = 'ENNAKKOTILAUS', '2', '1') t_tyyppi2, ";
		}

		// Vain ker�yslistat saa groupata
		if ($yhtiorow["lahetteen_tulostustapa"] == "K" and $yhtiorow["kerayslistojen_yhdistaminen"] == "Y") {
			//jos halutaan eritell� tulostusalueen mukaan , lasku.tulostusalue
			$grouppi = "GROUP BY lasku.yhtio, lasku.yhtio_nimi, lasku.ytunnus, lasku.toim_ovttunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto, jvgrouppi, vientigrouppi, varastonimi, varastotunnus, keraysviikko, lasku.mapvm, t_tyyppi2";
		}
		elseif ($yhtiorow["lahetteen_tulostustapa"] == "K" and $yhtiorow["kerayslistojen_yhdistaminen"] == "T") {
			$grouppi = "GROUP BY lasku.yhtio, lasku.yhtio_nimi, lasku.ytunnus";
		}
		else {
			$grouppi = "GROUP BY lasku.tunnus";
		}

		if ($yhtiorow["pakkaamolokerot"] != "") {
			$grouppi .= ", lasku.varasto, lasku.tulostusalue";
		}

		$query = "	SELECT lasku.yhtio, lasku.yhtio_nimi, lasku.ytunnus, lasku.toim_ovttunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.varasto,
					if (tila = 'V', lasku.viesti, lasku.toimitustapa) toimitustapa,
					if (maksuehto.jv!='', lasku.tunnus, '') jvgrouppi,
					if (lasku.vienti!='', lasku.tunnus, '') vientigrouppi,
					varastopaikat.nimitys varastonimi,
					varastopaikat.tunnus varastotunnus,
					week(lasku.kerayspvm, 1) keraysviikko,
					min(if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos)) prioriteetti,
					max(if (lasku.clearing = '', 'N', if (lasku.clearing = 'JT-TILAUS', 'J', if (lasku.clearing = 'ENNAKKOTILAUS', 'E', '')))) t_tyyppi,
					$selectlisa
					min(lasku.luontiaika) laadittu,
					min(lasku.h1time) h1time,
					min(lasku.kerayspvm) kerayspvm,
					min(lasku.toimaika) toimaika,
					min(lasku.keraysvko) keraysvko,
					min(lasku.toimvko) toimvko,
					GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ',') otunnus,
					GROUP_CONCAT(distinct lasku.tunnus SEPARATOR '_') div_id,
					count(distinct otunnus) tilauksia,
					count(*) riveja,
					group_concat(DISTINCT concat_ws('\n\n', if (comments!='',concat('".t("L�hetteen lis�tiedot").":\n',comments),NULL), if (sisviesti2!='',concat('".t("Ker�yslistan lis�tiedot").":\n',sisviesti2),NULL)) SEPARATOR '\n') ohjeet,
					GROUP_CONCAT(DISTINCT if (kommentti='',NULL,kommentti) separator '\n') AS kommentit,
					lasku.mapvm
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus
					LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
					LEFT JOIN maksuehto ON maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus
					WHERE
					lasku.$logistiikka_yhtiolisa
					and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa)
					$haku
					$tilaustyyppi
					$grouppi
					$jarjx";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre)==0) {
			echo "<br><br><font class='message'>".t("Tulostusjonossa ei ole yht��n tilausta")."...</font>";
		}
		else {
			echo "<br>";
			echo "<table>";
			echo "<tr>";
			if ($logistiikka_yhtio != '') {
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">".t("Yhti�")."</a></th>";
			}
			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='prioriteetti'; document.forms['find'].submit();\">".t("Pri")."</a><br>
					  <a href='#' onclick=\"getElementById('jarj').value='varastonimi'; document.forms['find'].submit();\">".t("Varastoon")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tilauksia'; document.forms['find'].submit();\">".t("Tilaus")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='lasku.ytunnus'; document.forms['find'].submit();\">".t("Asiakas")."</a><br>
					  <a href='#' onclick=\"getElementById('jarj').value='lasku.nimi'; document.forms['find'].submit();\">".t("Nimi")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='lasku.luontiaika'; document.forms['find'].submit();\">".t("Laadittu")."</a><br>
				  	  <a href='#' onclick=\"getElementById('jarj').value='lasku.h1time'; document.forms['find'].submit();\">".t("Valmis")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">".t("Ker�ysaika")."</a><br>
					  <a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">".t("Toimitusaika")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">".t("Toimitustapa")."</a></th>";
			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">".t("Riv")."</a></th>";

			if ($yhtiorow["pakkaamolokerot"] != "" or $logistiikka_yhtio != '') {
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">".t("Ei lokeroa")."</a></th>";
			}

			echo "<th valign='top'>".t("Tulostin")."</th>";
			echo "<th valign='top'>".t("Tulosta")."</th>";
			echo "<th valign='top'>".t("N�yt�")."</th>";
			echo "</tr></form>";

			$tulostakaikki_tun = array();
			$edennakko = "";
			$riveja_yht = 0;

			while ($tilrow = mysql_fetch_array($tilre)) {
				if ($logistiikka_yhtio != '') {
					$kukarow['yhtio'] = $tilrow['yhtio'];
					$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);
				}

				if ($edennakko != "" and $edennakko != $tilrow["t_tyyppi"] and $tilrow["t_tyyppi"] == "E") {
					echo "<tr><td colspan='11' class='back'><br></td></tr>";
				}

				$edennakko = $tilrow["t_tyyppi"];

				$ero="td";
				if ($tunnus==$tilrow['otunnus']) $ero="th";

				echo "<tr class='aktiivi'>";

				if ($logistiikka_yhtio != '') {
					echo "<$ero valign='top'>$tilrow[yhtio_nimi]</$ero>";
				}

				if (trim($tilrow["ohjeet"]) != "" or trim($tilrow['kommentit'] != '')) {
					echo "<div id='div_$tilrow[div_id]' class='popup' style='width:500px;'>";
					if (trim($tilrow["ohjeet"]) != "") {
						echo t("Lis�tiedot").":<br>";
						echo str_replace("\n", "<br>", $tilrow["ohjeet"]);
						echo "<br>";
					}
					if (trim($tilrow['kommentit'] != '')) {
						echo t("Rivikommentit").":<br>";
						echo str_replace("\n", "<br>", $tilrow["kommentit"]);
						echo "<br>";
					}
					echo "</div>";
					echo "<$ero valign='top' class='tooltip' id='$tilrow[div_id]'>$tilrow[t_tyyppi] $tilrow[prioriteetti] <img src='$palvelin2/pics/lullacons/info.png'>";
				}
				else {
					echo "<$ero valign='top'>$tilrow[t_tyyppi] $tilrow[prioriteetti]";
				}

				echo "<br>$tilrow[varastonimi]</$ero>";
				echo "<$ero valign='top'>".str_replace(',','<br>',$tilrow["otunnus"])."</$ero>";
				echo "<$ero valign='top'>$tilrow[ytunnus]";

				if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
					echo "<br>$tilrow[nimi]</$ero>";
				}
				else {
					echo "<br>$tilrow[toim_nimi]</$ero>";
				}

				$laadittu_e 	= tv1dateconv($tilrow["laadittu"], "P", "LYHYT");
				$h1time_e		= tv1dateconv($tilrow["h1time"], "P", "LYHYT");
				$h1time_e		= str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

				echo "<$ero valign='top' nowrap align='right'>$laadittu_e<br>$h1time_e</$ero>";

				if ($tilrow['keraysvko'] != '') {
					echo "<$ero valign='top' nowrap align='right'>".t("Vko")." ".date("W", strtotime($tilrow["kerayspvm"]));

					if ($tilrow['keraysvko'] != '7') {
						echo "/".$DAY_ARRAY[$tilrow["keraysvko"]];
					}
				}
				else {
					echo "<$ero valign='top' align='right'>".tv1dateconv($tilrow["kerayspvm"], "", "LYHYT");
				}


				if ($kukarow['resoluutio'] == 'I') {
					if ($tilrow["toimvko"] != '') {
						echo "<br>".t("Vko")." ".date("W", strtotime($tilrow["toimaika"]));

						if ($tilrow['toimvko'] != '7') {
							echo "/".$DAY_ARRAY[$tilrow["toimvko"]];
						}

						echo "</$ero>";
					}
					else {
						echo "<br>".tv1dateconv($tilrow["toimaika"], "", "LYHYT")."</$ero>";
					}

				}

				echo "<$ero valign='top'>$tilrow[toimitustapa]</$ero>";
				echo "<$ero valign='top'>$tilrow[riveja]</$ero>";

				if ($tilrow["tilauksia"] > 1) {
					echo "<$ero valign='top'></$ero>";

					if ($yhtiorow["pakkaamolokerot"] != "" or $logistiikka_yhtio != '') {
						echo "<$ero valign='top'></$ero>";
					}

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='toim' 			value='$toim'>";
					echo "<input type='hidden' name='jarj' 			value='$jarj'>";
					echo "<input type='hidden' name='tuvarasto' 	value='$tuvarasto'>";
					echo "<input type='hidden' name='tumaa' 		value='$tumaa'>";
					echo "<input type='hidden' name='tutyyppi' 		value='$tutyyppi'>";
					echo "<input type='hidden' name='tutoimtapa' 	value='$tutoimtapa'>";
					echo "<input type='hidden' name='karajaus' 		value='$karajaus'>";
					echo "<input type='hidden' name='lasku_yhtio' 	value='$tilrow[yhtio]'>";
					echo "<input type='hidden' name='tee2' 			value='VALITSE'>";
					echo "<input type='hidden' name='tilaukset'		value='$tilrow[otunnus]'>";
					echo "<$ero valign='top'><input type='submit' name='tila' 	value='".t("Valitse")."'></form></$ero>";
					echo "<$ero valign='top'></$ero>";
					echo "</tr>";
				}
				else {
					//haetaan ker�yslistan oletustulostin
					$query = "	SELECT *
								from varastopaikat
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$tilrow[varasto]'";
					$prires = mysql_query($query) or pupe_error($query);
					$prirow = mysql_fetch_array($prires);
					$kirjoitin = $prirow['printteri0'];

					$varasto = $tilrow["varasto"];
					$tilaus  = $tilrow["otunnus"];

					require("varaston_tulostusalue.inc");

					echo "<form method='post' action='$PHP_SELF'>";

					if ($yhtiorow["pakkaamolokerot"] != "") {

						$ei_pakkaamoa_sel = '';

						if ($tila == 'N' or ($toim == 'SIIRTOLISTA' and $tila == "G")) {
							$query = "	SELECT ei_pakkaamoa
										FROM toimitustapa
										WHERE yhtio = '$kukarow[yhtio]'
										AND selite = '$tilrow[toimitustapa]'";
							$ei_pakkaamoa_res = mysql_query($query) or pupe_error($query);

							$ei_pakkaamoa_row = mysql_fetch_assoc($ei_pakkaamoa_res);

							if ($ei_pakkaamoa_row['ei_pakkaamoa'] == '1' or $tilrow["t_tyyppi"] == "E") {
								$ei_pakkaamoa_sel = "checked";
							}
						}
						echo "<$ero valign='top'><input type='checkbox' name='ei_pakkaamoa' id='ei_pakkaamoa' value='$tilaus' $ei_pakkaamoa_sel>";
						echo "<input type='hidden' name='ei_pakkaamoa_selected' id='ei_pakkaamoa_selected' value='$ei_pakkaamoa_sel'>";
						echo "</$ero>";
					}
					elseif ($logistiikka_yhtio != '') {
						echo "<$ero></$ero>";
					}

					$query = "	SELECT *
								FROM kirjoittimet
								WHERE yhtio = '$kukarow[yhtio]'
								ORDER by kirjoitin";
					$kirre = mysql_query($query) or pupe_error($query);

					echo "<$ero valign='top'><select name='valittu_tulostin'>";

					while ($kirrow = mysql_fetch_array($kirre)) {
						$sel = '';

						// t�ss� vaiheessa k�ytt�j�n oletustulostin ylikirjaa optimaalisen varastotulostimen
						if (($kirrow['tunnus'] == $kirjoitin and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
							$sel = "SELECTED";
						}

						echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
					}

					echo "</select></$ero>";

					echo "<input type='hidden' name='toim' 			value='$toim'>";
					echo "<input type='hidden' name='jarj' 			value='$jarj'>";
					echo "<input type='hidden' name='tuvarasto'		value='$tuvarasto'>";
					echo "<input type='hidden' name='tumaa' 		value='$tumaa'>";
					echo "<input type='hidden' name='tutyyppi'		value='$tutyyppi'>";
					echo "<input type='hidden' name='tutoimtapa'	value='$tutoimtapa'>";
					echo "<input type='hidden' name='karajaus'		value='$karajaus'>";
					echo "<input type='hidden' name='tee2' 			value='TULOSTA'>";
					echo "<input type='hidden' name='tulostukseen[]' value='$tilrow[otunnus]'>";
					echo "<input type='hidden' name='lasku_yhtio' 	value='$tilrow[yhtio]'>";
					echo "<$ero valign='top'><input type='submit' 	value='".t("Tulosta")."'></form></$ero>";

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='toim' 			value='$toim'>";
					echo "<input type='hidden' name='jarj' 			value='$jarj'>";
					echo "<input type='hidden' name='tuvarasto' 	value='$tuvarasto'>";
					echo "<input type='hidden' name='tumaa' 		value='$tumaa'>";
					echo "<input type='hidden' name='tutyyppi' 		value='$tutyyppi'>";
					echo "<input type='hidden' name='tutoimtapa'	value='$tutoimtapa'>";
					echo "<input type='hidden' name='karajaus' 		value='$karajaus'>";
					echo "<input type='hidden' name='etsi' 			value='$etsi'>";
					echo "<input type='hidden' name='tee2' 			value='NAYTATILAUS'>";
					echo "<input type='hidden' name='vanha_tee2' 	value=''>";
					echo "<input type='hidden' name='lasku_yhtio' 	value='$tilrow[yhtio]'>";
					echo "<input type='hidden' name='tunnus' 		value='$tilrow[otunnus]'>";
					echo "<$ero valign='top'><input type='submit' value='".t("N�yt�")."'></form></$ero>";

					echo "</tr>";
				}

				// Ker�t��n tunnukset tulosta kaikki-toimintoa varten
				$tulostakaikki_tun[$tilrow['otunnus']] = $tilrow["yhtio"];
				$riveja_yht += $tilrow["riveja"];
			}

			$spanni = $logistiikka_yhtio != '' ? 7 : 6;

			echo "<tr class='aktiivi'>";
			echo "<th colspan='$spanni'>";

			echo t("Rivej� yhteens�")."</th>";
			echo "<th>".$riveja_yht."</th>";

			$spanni = ($yhtiorow["pakkaamolokerot"] != "" or $logistiikka_yhtio != '') ? 4 : 3;

			echo "<th colspan='$spanni'></th>";
			echo "</tr>";

			echo "</table>";
			echo "<br>";

			if ($oikeurow['paivitys'] == 1) {
				echo "<table>";
				echo "<form method='post' action='$PHP_SELF'>";

				if ($toim == 'SIIRTOLISTA') {
					echo "<tr><th colspan='2'>".t("Tulosta kaikki siirtolistat")."</th></tr>";
				}
				elseif ($toim == 'SIIRTOTYOMAARAYS') {
					echo "<tr><th colspan='2'>".t("Tulosta kaikki ty�m��r�ykset")."</th></tr>";
				}
				elseif ($toim == 'VALMISTUS') {
					echo "<tr><th colspan='2'>".t("Tulosta kaikki valmistuslistat")."</th></tr>";
				}
				elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
					echo "<tr><th colspan='2'>".t("Tulosta kaikki purkulistat")."</th></tr>";
				}
				else {
					echo "<tr><th colspan='2'>".t("Tulosta kaikki ker�yslistat")."</th></tr>";
				}

				if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
					$logistiikka_yhtio = $konsernivarasto_yhtiot;
				}

				$query = "	SELECT komento, min(kirjoitin) kirjoitin, min(tunnus) tunnus
							FROM kirjoittimet
							WHERE
							$logistiikka_yhtiolisa
							GROUP BY komento
							ORDER BY kirjoitin";
				$kirre = mysql_query($query) or pupe_error($query);

				echo "<tr><td><select name='valittu_tulostin'>";

				while ($kirrow = mysql_fetch_array($kirre)) {
					$sel = '';

					//t�ss� vaiheessa k�ytt�j�n oletustulostin ylikirjaa optimaalisen varastotulostimen
					if ($kirrow['tunnus'] == $kukarow['kirjoitin']) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}

				echo "</select></td>";

				$tulostakaikki_tun = urlencode(serialize($tulostakaikki_tun));

				echo "<input type='hidden' name='toim' 			value='$toim'>";
				echo "<input type='hidden' name='jarj' 			value='$jarj'>";
				echo "<input type='hidden' name='tuvarasto' 	value='$tuvarasto'>";
				echo "<input type='hidden' name='tumaa' 		value='$tumaa'>";
				echo "<input type='hidden' name='tutyyppi' 		value='$tutyyppi'>";
				echo "<input type='hidden' name='tutoimtapa'	value='$tutoimtapa'>";
				echo "<input type='hidden' name='karajaus' 		value='$karajaus'>";
				echo "<input type='hidden' name='tee2' 			value='TULOSTA'>";
				echo "<input type='hidden' name='tulostukseen_kaikki' value='$tulostakaikki_tun'>";
				echo "<td><input type='submit' value='".t("Tulosta kaikki")."'></td></tr></form>";

				echo "</table>";
			}
		}
	}

	require ("../inc/footer.inc");
?>