<?php
	require ("../inc/parametrit.inc");

	if ($toim == 'SIIRTOLISTA') {
		echo "<font class='head'>".t("Ker‰‰ siirtolista").":</font><hr>";
		$tila = "G";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi!='M' ";
	}
	elseif ($toim == 'MYYNTITILI') {
		echo "<font class='head'>".t("Ker‰‰ myyntitili").":</font><hr>";
		$tila = "G";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi='M' ";
	}
	elseif ($toim == 'VALMISTUS') {
		echo "<font class='head'>".t("Ker‰‰ valmistus").":</font><hr>";
		$tila = "V";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	else {
		echo "<font class='head'>".t("Ker‰‰ tilaus").":</font><hr>";
		$tila = "L";
		$tyyppi = "'L'";
		$tilaustyyppi = "";
	}

	$var_lisa = "";

	if ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
		$var_lisa .= ",'J'";
	}

	if ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
		$var_lisa .= ",'P'";
	}


	if ($tee == "P") {
		//haetaan kaikki t‰lle klˆntille kuuluvat otsikot
		$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and kerayslista='$id' and kerayslista != 0 and tila='$tila' $tilaustyyppi
					HAVING tunnukset is not null";
		$toimresult = mysql_query($query) or pupe_error($query);

		//jos rivej‰ lˆytyy niin tiedet‰‰n, ett‰ t‰m‰ on ker‰ysklˆntti
		if (mysql_num_rows($toimresult) > 0) {
			$toimrow = mysql_fetch_array($toimresult);
			$tilausnumeroita = $toimrow["tunnukset"];
		}
		else {
			$tilausnumeroita = $id;
		}

		// katotaan aluks onko yht‰‰n tuotetta sarjanumeroseurannassa t‰ll‰ ker‰yslitalla
		$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
					tilausrivi.otunnus in ($tilausnumeroita) and
					tilausrivi.tyyppi='L'";
		$toimresult = mysql_query($query) or pupe_error($query);

		while ($toimrow = mysql_fetch_array($toimresult)) {

			if ($toimrow["sarjanumeroseuranta"] == "M" and $toimrow["varattu"] < 0) {
				$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$toimrow[tuoteno]' and ostorivitunnus='$toimrow[tunnus]'";
			}
			else {
				$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$toimrow[tuoteno]' and myyntirivitunnus='$toimrow[tunnus]'";
			}
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);

			if ($sarjarow["kpl"] != abs($toimrow["varattu"])) {
				echo "<font class='error'>Sarjanumeroseurannassa oleville tuotteille pit‰‰ liitt‰‰ sarjanumero, ennenkuin ker‰yksen voi suorittaa. Tuote: $toimrow[tuoteno]</font><br><br>";
				$tee = "";
			}
		}
	}

	if ($tee == 'P') {

		if ((int) $keraajanro == 0) $keraajanro=$keraajalist;

		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and keraajanro='$keraajanro'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==0) {
			echo "<font class='error'>".t("Ker‰‰j‰‰")." $keraajanro ".t("ei lˆydy")."!</font><br>";
		}
		else {
			$keraaja = mysql_fetch_array($result);
			$who = $keraaja['kuka'];

			$muuttuiko='';
			$mitkamuuttu='';

			for ($i=0; $i < count($kerivi); $i++) {

				$query1 = "	SELECT if(kerattyaika='0000-00-00 00:00:00', 'keraamaton', 'keratty') status
							FROM tilausrivi
							WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
				$ktresult = mysql_query($query1) or pupe_error($query1);
				$statusrow = mysql_fetch_array($ktresult);
				$keraamaton=0;

				if ($statusrow["status"] == "keraamaton") {
					if($kerivi[$i] > 0) {

						$apui = $kerivi[$i];

						//Kysess‰ voi olla ker‰ysklˆntti, haetaan muuttuneen rivin otunnus
						$query1 = "	SELECT otunnus
									FROM tilausrivi
									WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result  = mysql_query($query1) or pupe_error($query1);
						$otsikko = mysql_fetch_array($result);

						//Haetaan otsikon kaikki tiedot
						$query1 = "	SELECT *
									FROM lasku
									WHERE tunnus = '$otsikko[otunnus]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query1) or pupe_error($query1);
						$otsikkorivi = mysql_fetch_array($result);

						//Haetaan tilausrivin kaikki tiedot
						$query1 = "	SELECT *
									FROM tilausrivi
									WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query1) or pupe_error($query1);
						$tilrivirow = mysql_fetch_array($result);


						//Aloitellaan tilausrivi p‰ivitysquery‰
						if ($tilrivirow["var"] != "J") {
							//Muut kuin JT-rivit p‰ivitet‰‰n aina ker‰tyiksi
							$query = "	UPDATE tilausrivi
										SET keratty = '$who',
										kerattyaika = now() ";
						}
						else {
							//JT-rivit p‰ivitet‰‰n ker‰tyksi varauksella
							$query = "	UPDATE tilausrivi
										SET yhtio=yhtio ";
						}

						// K‰ytt‰j‰ on syˆtt‰nyt jonkun luvun
						if (trim($maara[$apui]) != '') {

							//Siivotaan hieman k‰ytt‰j‰n syˆtt‰m‰‰ kappalem‰‰r‰‰
							$maara[$apui] = str_replace ( ",", ".", $maara[$apui]);
							$maara[$apui] = (float) $maara[$apui];

							if ($tilrivirow["var"] == 'P' and $maara[$apui] > 0) {
								// puuterivi lˆytyi poistetaan VAR
								$query .= "	, var		= ''
											, varattu	= '".$maara[$apui]."'";

								//Poistetaan tuote loppukommentti jos tuotetta sittenkin lˆytyi
								$korvataan_pois = t("Tuote Loppu.");
								$query .= "	, kommentti	= replace(kommentti, '$korvataan_pois', '') ";

							}
							elseif($tilrivirow["var"] == 'J' and $maara[$apui] > 0) {
								//JT-rivi lˆytyi, poistetaan VAR ja merkataan rivi ker‰tyksi
								$query .= "	, keratty 	= '$who'
											, kerattyaika = now()
											, var			= ''
											, jt			= 0
											, varattu	= '".$maara[$apui]."'";

								if($maara[$apui] < $tilrivirow['jt']) {
									//JT-riville tehd‰‰n osatoimitus ja loput j‰tet‰‰n j‰lkitoimitukseen
									$jatetaan = round($tilrivirow['jt']-$maara[$apui],2);

									// ja tehd‰‰n uusi JT-rivi
									$inquery = "INSERT into tilausrivi set
												hyllyalue		= '$tilrivirow[hyllyalue]',
												hyllynro		= '$tilrivirow[hyllynro]',
												hyllyvali		= '$tilrivirow[hyllyvali]',
												hyllytaso		= '$tilrivirow[hyllytaso]',
												varattu 		= '0',
												kpl 			= 0,
												tilkpl 			= '$jatetaan',
												jt				= '$jatetaan',
												otunnus 		= '$tilrivirow[otunnus]',
												var				= '$tilrivirow[var]',
												keratty			= '',
												kerattyaika		= '',
												kerayspvm 		= now(),
												laatija			= '$kukarow[kuka]',
												perheid			= '$tilrivirow[perheid]',
												laadittu		= now(),
												toimitettu		= '',
												toimitettuaika	= '',
												toimaika 		= now(),
												laskutettu		= '',
												laskutettuaika	= '',
												yhtio 			= '$tilrivirow[yhtio]',
												tuoteno 		= '$tilrivirow[tuoteno]',
												ale 			= '$tilrivirow[ale]',
												netto 			= '$tilrivirow[netto]',
												yksikko 		= '$tilrivirow[yksikko]',
												try 			= '$tilrivirow[try]',
												osasto 			= '$tilrivirow[osasto]',
												alv 			= '$tilrivirow[alv]',
												hinta 			= '$tilrivirow[hinta]',
												nimitys 		= '$tilrivirow[nimitys]',
												tyyppi 			= '$tilrivirow[tyyppi]',
												kommentti 		= '$tilrivirow[kommentti]'";
									$jtres = mysql_query($inquery) or pupe_error($inquery);
								}
							}
							elseif($tilrivirow["var"] == 'J' and $maara[$apui] == 0) {
								//JT-rivi‰ ei lˆytynyt, ei tehd‰ sille mit‰‰n (ei ainakaan mit‰‰n fiksua)
								$query .= ", jt = jt ";
							}
							elseif ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' or $otsikkorivi['clearing'] == 'JT-TILAUS') {
								// Jos t‰m‰ on toimitettava ennakkotilaus tai jt-tilaus
								// ja meill‰ syntyy negatiivista ker‰yspoikkeamaa, niin laitetaan erotus takaisin ennakkoriviksi/jt-riviksi

								$query .= ", varattu='".$maara[$apui]."'";

								if ($maara[$apui] < $tilrivirow['varattu']) {

									//Etsit‰‰n ennakko/jt-otsikko jolle rivi laitetaan
									if($otsikkorivi['clearing'] == 'ENNAKKOTILAUS') {
										$ejttila    = "('E')";
										$ejtalatila = "('A')";
										$ejttyyppi  = "E";

										$til = $tilrivirow['varattu'] - $maara[$apui];
										$kpl = $tilrivirow['varattu'] - $maara[$apui];
										$jt  = 0;
										$vr  = "";
									}
									if($otsikkorivi['clearing'] == 'JT-TILAUS') {
										$ejttila    = "('N','L')";
										$ejtalatila = "('T','X')";
										$ejttyyppi  = "L";

										$til = $tilrivirow['varattu'] - $maara[$apui];
										$kpl = 0;
										$jt  = $tilrivirow['varattu'] - $maara[$apui];
										$vr  = "J";
									}

									$query1 = "	SELECT *
												FROM lasku
												WHERE tila		in $ejttila
												and alatila		in $ejtalatila
												and yhtio		= '$kukarow[yhtio]'
												and ytunnus		= '$otsikkorivi[ytunnus]' and
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
												toimitusehto	= '$otsikkorivi[toimitusehto]'";
									$stresult = mysql_query($query1) or pupe_error($query1);

									//sopiva otsikko lˆytyi
									if (mysql_num_rows($stresult) > 0) {
										$strow = mysql_fetch_array($stresult);

										//t‰ss‰ tehd‰‰n uusi ennakko-rivi ja varattukentt‰‰n laitetaan ker‰yspoikkeama
										$querys = "	INSERT into tilausrivi set
													hyllyalue		= '$tilrivirow[hyllyalue]',
													hyllynro		= '$tilrivirow[hyllynro]',
													hyllyvali		= '$tilrivirow[hyllyvali]',
													hyllytaso		= '$tilrivirow[hyllytaso]',
													varattu 		= '$kpl',
													tilkpl 			= '$til',
													jt				= '$jt',
													otunnus 		= '$strow[tunnus]',
													var				= '$vr',
													keratty			= '',
													kerattyaika		= '',
													kerayspvm 		= now(),
													laatija			= '$kukarow[kuka]',
													laadittu		= now(),
													toimitettu		= '',
													toimitettuaika	= '',
													toimaika 		= now(),
													laskutettu		= '',
													laskutettuaika	= '',
													yhtio 			= '$tilrivirow[yhtio]',
													tuoteno 		= '$tilrivirow[tuoteno]',
													ale 			= '$tilrivirow[ale]',
													netto 			= '$tilrivirow[netto]',
													yksikko 		= '$tilrivirow[yksikko]',
													try 			= '$tilrivirow[try]',
													osasto 			= '$tilrivirow[osasto]',
													kpl 			= 0,
													alv 			= '$tilrivirow[alv]',
													hinta 			= '$tilrivirow[hinta]',
													nimitys 		= '$tilrivirow[nimitys]',
													tyyppi 			= '$ejttyyppi',
													kommentti 		= '$tilrivirow[kommentti]'";
										$riviresult = mysql_query($querys) or pupe_error($querys);
									}
								}
							}
							else {
								$query .= ", varattu='".$maara[$apui]."'";
							}

							//p‰ivitet‰‰n tuoteperheiden saldottomat j‰senet oikeisiin m‰‰riin
							if ($tilrivirow["perheid"] != 0) {
								$query1 = "	SELECT tilausrivi.tunnus
											FROM tilausrivi, tuote
											WHERE tilausrivi.otunnus = '$tilrivirow[otunnus]'
											and tilausrivi.tunnus	!= '$kerivi[$i]'
											and tilausrivi.perheid	 = '$tilrivirow[perheid]'
											and tilausrivi.yhtio	 = '$kukarow[yhtio]'
											and tuote.yhtio			 = tilausrivi.yhtio
											and tuote.tuoteno	 	 = tilausrivi.tuoteno
											and tuote.ei_saldoa		!= ''";
								$result = mysql_query($query1) or pupe_error($query1);

								while($tilrivirow2 = mysql_fetch_array($result)) {
									$query1 = "	UPDATE tilausrivi
												SET varattu='".$maara[$apui]."'
												WHERE tunnus='$tilrivirow2[tunnus]' and yhtio='$kukarow[yhtio]'";
									$result1 = mysql_query($query1) or pupe_error($query1);
								}
							}

							$muuttuiko = 'kylsemuuttu';
							$mitkamuuttu .= "'".$kerivi[$i]."',";
						}

						//p‰ivitet‰‰n alkuper‰inen rivi
						$query .= " WHERE tunnus='$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);

						if ($toim == 'SIIRTOLISTA') {
							$tapquery = "SELECT * FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kerivi[$i]'";
							$tapresult = mysql_query($tapquery) or pupe_error($tapquery);
							$taprow = mysql_fetch_array($tapresult);

							if ($taprow['varattu'] != 0) {
								$tapsiirto = $taprow['varattu'] * -1;
								$mista = $taprow['hyllyalue']."-".$taprow['hyllynro']."-".$taprow['hyllyvali']."-".$taprow['hyllytaso'];
								$query = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$taprow[tuoteno]',
											kpl 		= '$tapsiirto',
											hinta 		= '0',
											laji 		= 'siirto',
											selite 		= '".t("Paikasta")." $mista ".t("v‰hennettiin")." $taprow[varattu]',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$result = mysql_query($query) or pupe_error($query);
							}


							$tapsiirto = '';
							$mista = '';
						}
					}

					//Ker‰‰m‰tˆn rivi
					$keraamaton++;
				}
				else {
					echo t("HUOM: T‰m‰ rivi oli jo ker‰tty! Ei voida ker‰t‰ uudestaan.")."<br>";
				}
			}

			if ($keraamaton > 0) {
				// Jos tilauksella oli yht‰‰n ker‰‰m‰tˆnt‰ rivi‰
				$query  = "	update lasku
							set alatila='C'
							where yhtio='$kukarow[yhtio]' and tunnus in ($tilausnumeroita) and tila = '$tila' and alatila='A'";
				$result = mysql_query($query) or pupe_error($query);

				//Jos ker‰yspoikkeamia syntyi, niin l‰hetet‰‰n mailit myyj‰lle ja asiakkaalle
				if ($muuttuiko == 'kylsemuuttu') {

					$mitkamuuttu = substr($mitkamuuttu,0,-1);

					$query = "	SELECT distinct(otunnus)
								FROM tilausrivi
								WHERE tunnus in ($mitkamuuttu) and yhtio='$kukarow[yhtio]'";
					$otsresult = mysql_query($query) or pupe_error($query);

					while ($otsrow = mysql_fetch_array($otsresult)) {

						$query = "	SELECT *
									FROM tilausrivi
									WHERE tunnus in ($mitkamuuttu) and otunnus='$otsrow[otunnus]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);

						$rivit = '';
						while ($tvtilausrivirow = mysql_fetch_array($result)) {

							$nimitysloput = '';

							if (strlen($tvtilausrivirow['nimitys']) > 27) {
								$nimitysloput = substr($tvtilausrivirow['nimitys'],28);
								$tvtilausrivirow['nimitys'] = substr($tvtilausrivirow['nimitys'],0,28);
							}

							$rivit .= sprintf("%-30.s",$tvtilausrivirow['nimitys']);
							$rivit .= sprintf("%-20.s",$tvtilausrivirow['tuoteno']);
							$rivit .= sprintf("%8.s"  ,$tvtilausrivirow['tilkpl']);
							$rivit .= sprintf("%23.s"  ,$tvtilausrivirow['varattu']);
							$rivit .= "\r\n";

							if ($nimitysloput != '') {
								$rivit .= sprintf("%-75.s",$nimitysloput);
								$rivit .= "\r\n";
							}
						}


						$query = "	SELECT lasku.*, asiakas.email, asiakas.kerayspoikkeama, kuka.nimi kukanimi
									FROM lasku
									JOIN asiakas on asiakas.yhtio=lasku.yhtio and asiakas.ytunnus=lasku.ytunnus
									LEFT JOIN kuka on kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
									WHERE lasku.tunnus='$otsrow[otunnus]' and lasku.yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);
						$laskurow = mysql_fetch_array($result);

						$header  = "From: <mailer@pupesoft.com>\r\n";

						$ulos  = sprintf("%-50.s",$yhtiorow['nimi'])								."".t("Ker‰yspoikkeamat")."\r\n";
						$ulos .= sprintf("%-50.s",$yhtiorow['osoite'])								."\r\n";
						$ulos .= sprintf("%-50.s",$yhtiorow['postino']." ".$yhtiorow['postitp'])	.tv1dateconv($laskurow['luontiaika'])."\r\n";
						$ulos .= "\r\n";
						$ulos .= sprintf("%-50.s","".t("Tilaaja").":")										."".t("Toimitusosoite").":\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['nimi'])								.$laskurow['toim_nimi']."\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['nimitark'])							.$laskurow['toim_nimitark']."\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['osoite'])								.$laskurow['toim_osoite']."\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['postino']." ".$laskurow['postitp'])	.$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\r\n";
						$ulos .= "\r\n";
						$ulos .= sprintf("%-50.s","".t("Toimitus").": ".$laskurow['toimitustapa'])			."".t("Tilausnumero").": ".$laskurow['tunnus']."\r\n";
						$ulos .= sprintf("%-50.s","".t("Tilausviite").": ".$laskurow['viesti'])				."".t("Myyj‰").": ".$laskurow['kukanimi']."\r\n";

						if ($laskurow['comments']!='')
							$ulos .= "".t("Kommentti").": ".$laskurow['comments']."\n";
							$ulos .= "\r\n";
							$ulos .= "".t("Nimitys")."                       ".t("Tuotenumero")."          ".t("Tilattu")."            ".t("Toimitetaan")."\r\n";
							$ulos .= "---------------------------------------------------------------------------------\r\n";
							$ulos .= $rivit."\r\n";


						if ($laskurow["email"] != '' and $laskurow["kerayspoikkeama"] == 0) {
							$boob = mail($laskurow["email"],  "$yhtiorow[nimi] - ".t("Ker‰yspoikkeamat")."", $ulos, $header);
							if ($boob===FALSE) echo " - ".t("Email l‰hetys ep‰onnistui")."!<br>";
						}

						if ($kukarow["eposti"] != '') {
							if (($laskurow["email"] == '' or $boob === FALSE) and $laskurow["kerayspoikkeama"] == 0) {
								$ulos = t("Asiakkaalta puuttuu s‰hkˆpostiosoite! Ker‰yspoikkeamia ei voitu l‰hett‰‰!")."\r\n\r\n\r\n".$ulos;
							}
							elseif ($laskurow["kerayspoikkeama"] == 1) {
								$ulos = t("Asiakkaalle on merkitty ett‰ h‰n ei halua ker‰yspoikkeama ilmoituksia!")."\r\n\r\n\r\n".$ulos;
							}
							else {
								$ulos = t("T‰m‰ viesti on l‰hetetty myˆs asiakkaalle")."!\r\n\r\n\r\n".$ulos;
							}

							$ulos = t("Tilauksen ker‰si").": $keraaja[nimi]\r\n\r\n".$ulos;

							$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Ker‰yspoikkeamat")."", $ulos, $header);
							if ($boob===FALSE) echo " - ".t("Email l‰hetys ep‰onnistui")."!<br>";
						}
					}
				}

				// Tutkitaan viel‰ aivan lopuksi mihin tilaan me laitetaan t‰m‰ otsikko
				// Ker‰ysvaiheessahan tilausrivit muuttuvat ja tarkastamme nyt tilanteen uudestaan
				// T‰m‰ tehd‰‰n vain myyntitilauksille
				if ($tila == "L") {
					$kutsuja = "keraa.php";

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus in ($tilausnumeroita)
								and yhtio = '$kukarow[yhtio]'
								and tila  = 'L'";
					$lasresult = mysql_query($query) or pupe_error($query);

					while($laskurow = mysql_fetch_array($lasresult)) {
						require("tilaus-valmis-valitsetila.inc");
					}
				}

				//Tulostetaan uusi l‰hete jos k‰ytt‰j‰ valitsi drop-downista printterin
				//Paitsi jos tilauksen tila p‰ivitettiin sellaiseksi, ett‰ l‰hetett‰ ei kuulu tulostaa
				if ($valittu_tulostin != '') {

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus in ($tilausnumeroita)
								and yhtio = '$kukarow[yhtio]'
								and alatila = 'C'";
					$lasresult = mysql_query($query) or pupe_error($query);

					while($laskurow = mysql_fetch_array($lasresult)) {

						//tulostetaan faili ja valitaan sopivat printterit
						if ($laskurow["varasto"] == '') {
							$query = "	select *
										from varastopaikat
										where yhtio='$kukarow[yhtio]'
										order by alkuhyllyalue,alkuhyllynro
										limit 1";
						}
						else {
							$query = "	select *
										from varastopaikat
										where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
										order by alkuhyllyalue,alkuhyllynro";
						}
						$prires= mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($prires)>0) {
							$prirow= mysql_fetch_array($prires);

							// k‰teinen muuttuja viritet‰‰n tilaus-valmis.inc:iss‰ jos maksuehto on k‰teinen
							// ja silloin pit‰‰ kaikki l‰hetteet tulostaa aina printteri5:lle (lasku printteri)
							if ($kateinen=='X') {
								$apuprintteri=$prirow['printteri5']; // laskuprintteri
							}
							else {
								if ($valittu_tulostin == "oletukselle") {
									$apuprintteri = $prirow['printteri1']; // l‰heteprintteri
								}
								else {
									$apuprintteri = $valittu_tulostin;
								}
							}

							//haetaan l‰hetteen tulostuskomento
							$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
							$kirres= mysql_query($query) or pupe_error($query);
							$kirrow= mysql_fetch_array($kirres);
							$komento=$kirrow['komento'];

							//haetaan osoitelapun tulostuskomento
							$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri3]'";
							$kirres= mysql_query($query) or pupe_error($query);
							$kirrow= mysql_fetch_array($kirres);
							$oslapp=$kirrow['komento'];

						}

						//hatetaan asiakkaan l‰hetetyyppi
						$query = "	SELECT lahetetyyppi, luokka, puhelin
									FROM asiakas
									WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);
						$asrow = mysql_fetch_array($result);

						if ($asrow["lahetetyyppi"] != '' and file_exists($asrow["lahetetyyppi"])) {
							require_once ($asrow["lahetetyyppi"]);
						}
						else {
							//Haetaan yhtiˆn oletusl‰hetetyyppi
							$query = "	SELECT selite
										FROM avainsana
										WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
										ORDER BY jarjestys, selite
										LIMIT 1";
							$vres = mysql_query($query) or pupe_error($query);
							$vrow = mysql_fetch_array($vres);

							if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
								require_once ($vrow["selite"]);
							}
							else {
								echo "<font class='error'>".t("Emme lˆyt‰neet yht‰‰n l‰hetetyyppi‰. L‰hetett‰ ei voida tulostaa.")."</font><br>";
							}
						}

						$otunnus = $laskurow["tunnus"];

						//tehd‰‰n uusi PDF failin olio
						$pdf= new pdffile;
						$pdf->set_default('margin', 0);

						//ovhhintaa tarvitaan jos l‰hetetyyppi on sellainen, ett‰ sinne tulostetaan bruttohinnat
						if ($yhtiorow["alv_kasittely"] != "") {
							$lisa2 = " round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta*(1+(tilausrivi.alv/100))),2) ovhhinta ";
						}
						else {
							$lisa2 = " round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta),2) ovhhinta ";
						}

						//generoidaan l‰hetteelle ja ker‰yslistalle rivinumerot
						$query = "	SELECT tilausrivi.*,
									round((tilausrivi.varattu+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100)),2) rivihinta,
									if(perheid = 0,
									(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(hyllynro, 5, '0'),lpad(hyllyvali, 5, '0'),lpad(hyllytaso, 5, '0'), tuoteno, tunnus)  from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.tunnus),
									(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(hyllynro, 5, '0'),lpad(hyllyvali, 5, '0'),lpad(hyllytaso, 5, '0'), tuoteno, perheid) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid)
									) as sorttauskentta,
									$lisa2
									FROM tilausrivi left join tuote on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
									WHERE tilausrivi.otunnus = '$otunnus'
									and tilausrivi.yhtio = '$kukarow[yhtio]'
									ORDER BY sorttauskentta";
						$riresult = mysql_query($query) or pupe_error($query);

						//generoidaan rivinumerot
						$rivinumerot = array();

						$kal = 1;

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

						//pdf:n header..
						$firstpage = alku();


						while ($row = mysql_fetch_array($riresult)) {
							//piirr‰ rivi
							$firstpage = rivi($firstpage);

							if ($row["netto"] != 'N' and $row["laskutettu"] == "") {
								$total += $row["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT.. (pit‰‰ olla laskuttamaton, muuten erikoisale on jo jyvitetty)
							}
							else {
								$total_netto += $row["rivihinta"]; // lasketaan tilauksen loppusummaa NETTORIVIT..
							}
						}

						//Vikan rivin loppuviiva
						$x[0] = 20;
						$x[1] = 580;
						$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
						$pdf->draw_line($x, $y, $firstpage, $rectparam);

						loppu($firstpage, 1);

						//tulostetaan sivu
						print_pdf($komento);

						//tulostetaan osoitelappu
						//jos osoitelappuprintteri lˆytyy tulostetaan osoitelappu..
						$tunnus = $laskurow["tunnus"];

						if ($oslapp != '' and $oslappkpl != 0) {
							if ($oslappkpl > 0) {
								$oslapp .= " -#$oslappkpl ";
							}
							require ("osoitelappu_pdf.inc");
						}
					}
					echo "<br><br>";
				} //tulostetaan uusi l‰hete jos k‰ytt‰j‰ ruksasi ruudun. lopuu t‰h‰n
			}

			$tilausnumeroita 	= '';
			$boob    			= '';
			$header  			= '';
			$content 			= '';
			$rivit   			= '';
			$id      			= 0;
		}
	}

	if ($id=='') $id=0;

	// meill‰ ei ole valittua tilausta
	if ($id=='0') {

		$formi	= "find";
		$kentta	= "etsi";

		// tehd‰‰n etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>
				<input type='hidden' name='toim' value='$toim'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

		$query = "	select distinct
					if(lasku.kerayslista!=0, lasku.kerayslista, lasku.tunnus) tunnus,
					concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) asiakas,
					date_format(lasku.luontiaika, '%Y-%m-%d') laadittu,
					lasku.laatija
					from lasku use index (tila_index),
					tilausrivi use index (yhtio_otunnus)
					where
					lasku.yhtio						= '$kukarow[yhtio]'
					and lasku.tila					= '$tila'
					and lasku.alatila				= 'A'
					$tilaustyyppi
					and tilausrivi.yhtio			= lasku.yhtio
					and tilausrivi.otunnus			= lasku.tunnus
					and tilausrivi.tyyppi			in ($tyyppi)
					and tilausrivi.var				in ('', 'H' $var_lisa)
					and tilausrivi.keratty	 		= ''
					and tilausrivi.kerattyaika		= '0000-00-00 00:00:00'
					and tilausrivi.laskutettu		= ''
					and tilausrivi.laskutettuaika 	= '0000-00-00'
					$haku
					GROUP BY tunnus
					ORDER BY laadittu";
		$result = mysql_query($query) or pupe_error($query);

		//piirret‰‰n taulukko...
		if (mysql_num_rows($result)!=0) {

			echo "<table>";

			echo "<tr>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Laatija")."</th>";
			echo "<th>".t("Laadittu")."</th>";
			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {
				echo "<tr>";

				echo "<td valign='top'>$row[tunnus]</td>";
				echo "<td valign='top'>$row[asiakas]</td>";
				echo "<td valign='top'>$row[laatija]</td>";
				echo "<td valign='top'>$row[laadittu]</td>";

				echo "<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='id' value='$row[tunnus]'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='submit' name='tila' value='".t("Ker‰‰")."'></td></tr></form>";
			}

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yht‰‰n ker‰‰m‰tˆnt‰ tilausta ei lˆytynyt")."...</font>";
		}
	}


	if($id != 0) {

		//p‰ivit‰ ker‰tyt formi
		$formi="rivit";
		$kentta="keraajanro";

		//haetaan kaikki t‰lle klˆntille kuuluvat otsikot
		$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and kerayslista='$id' and kerayslista != 0 and tila='$tila' $tilaustyyppi
					HAVING tunnukset is not null";
		$toimresult = mysql_query($query) or pupe_error($query);

		//jos rivej‰ lˆytyy niin tiedet‰‰n, ett‰ t‰m‰ on ker‰ysklˆntti
		if (mysql_num_rows($toimresult) > 0) {
			$toimrow = mysql_fetch_array($toimresult);
			$tilausnumeroita = $toimrow["tunnukset"];
		}
		else {
			$tilausnumeroita = $id;
		}

		echo "<table>";

		if ($toim == 'SIIRTOLISTA')	 {
			echo "<tr><th align='left'>".t("Siirtolista")."</th><td>$id</td></tr>";
		}
		if ($toim == 'MYYNTITILI')	 {
			echo "<tr><th align='left'>".t("Myyntitili")."</th><td>$id</td></tr>";
		}
		else {
			$query = "	SELECT concat_ws(' ',lasku.nimi, lasku.nimitark) nimi,
						concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) toim_nimi,
						osoite, concat_ws(' ', postino, postitp) postitp,
						toim_osoite, concat_ws(' ', toim_postino, toim_postitp) toim_postitp, tila
						FROM lasku LEFT JOIN kuka ON lasku.myyja = kuka.tunnus
						WHERE lasku.tunnus in ($tilausnumeroita) and lasku.yhtio='$kukarow[yhtio]' and tila='$tila' and alatila='A'";
			$result = mysql_query($query) or pupe_error($query);
			$row    = mysql_fetch_array($result);

			echo "<tr><th>" . t("Tilaus") ."</th><td>$tilausnumeroita</td></tr>";
			echo "<tr><th>" . t("Asiakas") ."</th><td>$row[nimi]<br>$row[toim_nimi]</td></tr>";
			echo "<tr><th>" . t("Laskutusosoite") ."</th><td>$row[osoite], $row[postitp]</td></tr>";
			echo "<tr><th>" . t("Toimitusosoite") ."</th><td>$row[toim_osoite], $row[toim_postitp]</td></tr>";
		}

		$query = "	SELECT
					concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka,
					concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
					tilausrivi.tuoteno puhdas_tuoteno,
					tilausrivi.varattu, tilausrivi.jt, tilausrivi.keratty, tilausrivi.tunnus, tilausrivi.var, tilausrivi.tilkpl,
					tuote.ei_saldoa, tuote.sarjanumeroseuranta, tuote.tuoteno tuote
					FROM tilausrivi, tuote
					WHERE tuote.yhtio=tilausrivi.yhtio
					and tuote.tuoteno=tilausrivi.tuoteno
					and tilausrivi.var in ('', 'H' $var_lisa)
					and otunnus in ($tilausnumeroita)
					and tilausrivi.yhtio	= '$kukarow[yhtio]'
					and tilausrivi.tyyppi	in ($tyyppi)
					and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
					ORDER BY tilausrivi.perheid, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($result);

		if ($riveja > 0) {
			echo "<form name = 'rivit' method='post' action='$PHP_SELF' autocomplete='off'>";
			echo "	<input type='hidden' name='tee' value='P'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='id'  value='$id'>";
			echo "<th>".t("ker‰‰j‰")."</th><td><input type='text' size='5' name='keraajanro'> ".t("tai")." ";
			echo "<select name='keraajalist'>";

			if ($kukarow["keraajanro"] != 0) {
				echo "<option value='$kukarow[keraajanro]'>$kukarow[nimi]</option>";
			}

			$query = "select * from kuka where yhtio='$kukarow[yhtio]' and keraajanro!=0";
			$kuresult = mysql_query($query) or pupe_error($query);

			while($kurow = mysql_fetch_array($kuresult)) {
				if ($kukarow['kuka']!=$kurow['kuka'])
					echo "<option value='$kurow[keraajanro]'>$kurow[nimi]</option>";
			}

			echo "</table><br><br>";

			echo "	<table>
					<tr>
					<th>".t("Varastopaikka")."</th>
					<th>".t("Tuoteno")."</th>
					<th>".t("Kpl")."</th>
					<th>".t("Poikkeava m‰‰r‰")."</th>
					</tr>";

			$i=0;

			while($row = mysql_fetch_array($result)) {

				if ($row['var']=='P') {
					// jos kyseess‰ on puuterivi
					$puute 			= t("PUUTE");
					$row['varattu']	= $row['tilkpl'];
				}
				elseif ($row['var']=='J') {
					// jos kyseess‰ on JT-rivi
					$puute 			= t("**JT**");
					$row['varattu']	= $row['jt'];
				}
				elseif ($row['var']=='H') {
					// jos kyseess‰ on v‰kisinhyv‰ksytty-rivi
					$puute 			= "...........";
				}
				else {
					$puute			= '';
					$ker			= '';
				}

				if ($row['ei_saldoa'] != '') {
					echo "	<tr>
							<td>*</td>
							<td>$row[tuoteno]</td>
							<td>$row[varattu]</td>
							<td>".t("Saldoton tuote")."</td>
							</tr>
							<input type='hidden' name='kerivi[]' value='$row[tunnus]'>
							<input type='hidden' name='maara[$row[tunnus]]'>";
				}
				else {
					echo "	<tr>
							<td>$row[varastopaikka]</td>
							<td>$row[tuoteno]</td>
							<td>$row[varattu]</td>
							<td><input type='text' size='4' name='maara[$row[tunnus]]' value='$maara[$i]'> $puute";

					if ($row["sarjanumeroseuranta"] != "") {
						if ($row["sarjanumeroseuranta"] == "M" and $row["varattu"] < 0) {
							$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[puhdas_tuoteno]' and ostorivitunnus='$row[tunnus]'";
						}
						else {
							$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[puhdas_tuoteno]' and myyntirivitunnus='$row[tunnus]'";
						}
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						if ($sarjarow["kpl"] == abs($row["varattu"])) {
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[puhdas_tuoteno]&myyntirivitunnus=$row[tunnus]&from=KERAA&otunnus=$id' style='color:00FF00'>sarjanro OK</font></a>)";
						}
						else {
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[puhdas_tuoteno]&myyntirivitunnus=$row[tunnus]&from=KERAA&otunnus=$id'>sarjanro</a>)";
						}
					}

					echo "	</td>
							<input type='hidden' name='kerivi[]' value='$row[tunnus]'>
							</tr>";
				}
				$i++;
			}

			//jos yhtiˆll‰ on tulostusjono niin t‰ss‰ vaiheessa tulostetaan sit se virallinen l‰hete
			$sel = "";
			$oslappkpl = "";

			if ($yhtiorow["lahetteen_tulostustapa"] == 'K' and $toim != 'VALMISTUS') {
				$sel = 'SELECTED';
				$oslappkpl = 1;
			}

			echo "<tr><th colspan='2'>".t("Tulosta uusi l‰hete").": ";

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

			echo "</select>";
			echo t(" Osoitelappum‰‰r‰").":</th>";
			echo "<th><input type='text' size='4' name='oslappkpl' value='$oslappkpl'></th>";
			echo "<th><input type='submit' value='".t("Merkkaa ker‰tyksi")."'></th></form></tr>";
			echo "</table>";
		}
		else {
			echo t("T‰ll‰ tilauksella ei ole yht‰‰n ker‰tt‰v‰‰ rivi‰!");
		}
	}

	require ("../inc/footer.inc");
?>