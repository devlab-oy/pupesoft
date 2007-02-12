<?php

	// otetaan sisään voidaan ottaa $myyntirivitunnus tai $ostorivitunnus
	// ja $from niin tiedetään mistä tullaan ja minne palata

	if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php")  !== FALSE) {
		require("../inc/parametrit.inc");
	}

	echo "<font class='head'>".t("Sarjanumeroseuranta")."</font><hr>";

	$tunnuskentta 	= "";
	$rivitunnus 	= "";
	$hyvitysrivi 	= "";

	if ($myyntirivitunnus != "") {
		$tunnuskentta 	= "myyntirivitunnus";
		$rivitunnus 	= $myyntirivitunnus;
	}

	if ($ostorivitunnus != "") {
		$tunnuskentta 	= "ostorivitunnus";
		$rivitunnus	 	= $ostorivitunnus;
	}
	
	if ($siirtorivitunnus != "") {
		$tunnuskentta 	= "siirtorivitunnus";
		$rivitunnus	 	= $siirtorivitunnus;
	}

	// haetaan tilausrivin tiedot
	if ($from != '' and $rivitunnus != "") {
		$query    = "	SELECT *
						FROM tilausrivi use index (PRIMARY)
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$rivitunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$rivirow  = mysql_fetch_array($sarjares);

		$query    = "	SELECT *
						FROM lasku use index (PRIMARY)
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$rivirow[otunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$laskurow  = mysql_fetch_array($sarjares);

		//Jotta jt:tkin toimisi
		$rivirow["varattu"] = $rivirow["varattu"] + $rivirow["jt"];

		// jos varattu on nollaa ja kpl ei niin otetaan kpl (esim varastoon viedyt ostotilausrivit)
		if ($rivirow["varattu"] == 0 and $rivirow["kpl"] != 0) {
			$rivirow["varattu"] = $rivirow["kpl"];
		}

		// tässä muutetaan myyntirivitunnus ostorivitunnukseksi jos $rivirow["varattu"] eli kappalemäärä on negatiivinen
		if ($rivirow["varattu"] < 0 and $tunnuskentta = "myyntirivitunnus") {
			$tunnuskentta 		= "ostorivitunnus";
			$rivirow["varattu"] = abs($rivirow["varattu"]);
			$hyvitysrivi 		= "ON";
		}
	}

	//liitetään kululasku sarjanumeroon
	if ($toiminto == "kululaskut") {
		require('kululaskut.inc');
		exit;
	}
	
	
	//ollaan poistamassa sarjanumero-olio kokonaan
	if ($toiminto == 'POISTA') {
		$query = "	DELETE
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$sarjatunnus'
					and myyntirivitunnus=0
					and ostorivitunnus=0";
		$dellares = mysql_query($query) or pupe_error($query);

		$sarjanumero	= "";
		$lisatieto		= "";
		$sarjatunnus	= "";
		$toiminto		= "";
		$kaytetty		= "";

		echo "<font class='message'>".t("Sarjanumero poistettu")."!</font><br><br>";
	}

	//halutaan muuttaa sarjanumeron tietoja
	if ($toiminto == 'MUOKKAA') {
		if (isset($PAIVITA)) {
			$query = "	UPDATE sarjanumeroseuranta
						SET lisatieto = '$lisatieto',
						sarjanumero = '$sarjanumero',
						kaytetty	= '$kaytetty'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$sarjatunnus'";
			$sarjares = mysql_query($query) or pupe_error($query);
			
			if ($kaytetty != '') {
				$query = "	SELECT tunnus, kaytetty, $tunnuskentta trivitunnus
							FROM sarjanumeroseuranta
							WHERE tunnus = '$sarjatunnus'";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$query = "	UPDATE tilausrivi
							SET alv=alv+500
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$sarrow[trivitunnus]'
							and (tyyppi='O' or uusiotunnus = 0)
							and alv <= 500";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	SELECT tunnus, kaytetty, $tunnuskentta trivitunnus
							FROM sarjanumeroseuranta
							WHERE tunnus = '$sarjatunnus'";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$query = "	UPDATE tilausrivi
							SET alv=alv-500
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$sarrow[trivitunnus]'
							and (tyyppi='O' or uusiotunnus = 0)
							and alv >= 500";
				$sarjares = mysql_query($query) or pupe_error($query);
			}

			echo "<font class='message'>".t("Pävitettiin sarjanumeron tiedot")."!</font><br><br>";

			$sarjanumero	= "";
			$lisatieto		= "";
			$sarjatunnus	= "";
			$toiminto		= "";
			$kaytetty		= "";
		}
		else {
			$query = "	SELECT sarjanumeroseuranta.* , tuote.tuoteno, tuote.nimitys
						FROM sarjanumeroseuranta
						LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
						WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
						and sarjanumeroseuranta.tunnus='$sarjatunnus'";
			$muutares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($muutares) == 1) {

				$muutarow = mysql_fetch_array($muutares);

				echo "<table>";
				echo "<tr><th colspan='2'>".t("Muuta sarjanumerotietoja").":</th></tr>";
				echo "<tr><th>".t("Tuotenumero")."</th><td>$muutarow[tuoteno] $muutarow[nimitys]</td></tr>";

				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='muut_siirrettavat'	value='$muut_siirrettavat'>
						<input type='hidden' name='$tunnuskentta' 		value='$rivitunnus'>
						<input type='hidden' name='from' 				value='$from'>
						<input type='hidden' name='otunnus' 			value='$otunnus'>
						<input type='hidden' name='toiminto' 			value='MUOKKAA'>
						<input type='hidden' name='sarjatunnus' 		value='$sarjatunnus'>
						<input type='hidden' name='sarjanumero_haku' 	value='$sarjanumero_haku'>
						<input type='hidden' name='tuoteno_haku' 		value='$tuoteno_haku'>
						<input type='hidden' name='nimitys_haku' 		value='$nimitys_haku'>
						<input type='hidden' name='ostotilaus_haku' 	value='$ostotilaus_haku'
						<input type='hidden' name='myyntitilaus_haku'	value='$myyntitilaus_haku'>
						<input type='hidden' name='lisatieto_haku' 		value='$lisatieto_haku'>";

				echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$muutarow[sarjanumero]'></td></tr>";
				echo "<tr><th>".t("Lisätieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$muutarow[lisatieto]</textarea></td></tr>";

				$chk = "";
				if ($muutarow["kaytetty"] == 'K') {
					$chk = "CHECKED";
				}

				echo "<tr><th>".t("Käytetty")."</th><td><input type='checkbox' name='kaytetty' value='K' $chk></td>";
				echo "<td class='back'><input type='submit' name='PAIVITA' value='".t("Päivitä")."'></td>";
				echo "</tr></form></table><br><br>";
			}
			else {
				echo t("Muutettava sarjanumero on kadonnut")."!!!!<br>";
			}
		}
	}

	// ollaan syötetty uusi
	if ($toiminto == 'LISAA' and trim($sarjanumero) != '') {

		$query = "	SELECT *
					FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
					WHERE yhtio = '$kukarow[yhtio]'
					and sarjanumero = '$sarjanumero'
					and tuoteno = '$rivirow[tuoteno]'
					and (ostorivitunnus=0 or myyntirivitunnus=0)";
		$sarjares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sarjares) == 0) {
			//jos ollaan syötetty kokonaan uusi sarjanuero
			$query = "insert into sarjanumeroseuranta (yhtio, tuoteno, sarjanumero, lisatieto, $tunnuskentta, kaytetty) VALUES ('$kukarow[yhtio]','$rivirow[tuoteno]','$sarjanumero','$lisatieto','','$kaytetty')";
			$sarjares = mysql_query($query) or pupe_error($query);
			$tun = mysql_insert_id();
			
			echo "<font class='message'>".t("Lisättiin sarjanumero")." $sarjanumero.</font><br><br>";

			$sarjanumero	= "";
			$lisatieto		= "";
			$kaytetty		= "";			
		}
		else {
			$sarjarow = mysql_fetch_array($sarjares);
			echo "<font class='error'>".t("Sarjanumero löytyy jo tuotteelta")." $sarjarow[tuoteno]/$sarjanumero.</font><br><br>";
		}
	}

	// ollaan valittu joku tunnus listasta ja halutaan liittää se tilausriviin tai poistaa se tilausriviltä
	if ($from != '' and $rivitunnus != "" and $formista == "kylla") {
		// jos olemme ruksanneet vähemmän tai yhtä paljon kuin tuotteita on rivillä, voidaan päivittää muutokset
		if ($rivirow["varattu"] >= count($sarjataan)) {
			foreach ($sarjat as $sarjatun) {
				$query = "	SELECT tunnus, kaytetty, $tunnuskentta trivitunnus
							FROM sarjanumeroseuranta
							WHERE tunnus = '$sarjatun'";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$query = "	update sarjanumeroseuranta
							set $tunnuskentta=''
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$sarrow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);

				if ($sarrow["kaytetty"] == 'K') {
					$query = "	UPDATE tilausrivi
								SET alv=alv-500
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$sarrow[trivitunnus]'
								and alv >= 500";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}
		}
		else {
			echo "<font class='error'>".sprintf(t('Riviin voi liittää enintään %s sarjanumeroa'), abs($rivirow["varattu"])).".</font><br><br>";
		}
				
		if ($rivirow["varattu"] >= count($sarjataan)) {
			//jos mikään ei ole ruksattu niin ei tietenkään halutakkaan lisätä mitään sarjanumeroa
			if (count($sarjataan) > 0) {
				foreach ($sarjataan as $sarjatun) {

					if ($tunnuskentta == "ostorivitunnus") {
						//Hanskataan sarjanumeron varastopaikkaa
						$paikkalisa = "	,
										hyllyalue	= '$rivirow[hyllyalue]',
										hyllynro	= '$rivirow[hyllynro]',
										hyllyvali	= '$rivirow[hyllyvali]',
										hyllytaso	= '$rivirow[hyllytaso]'";
					}
					else {
						$paikkalisa = "";
					}

					$query = "	UPDATE sarjanumeroseuranta
								SET $tunnuskentta='$rivitunnus'
								$paikkalisa
								WHERE yhtio='$kukarow[yhtio]'
								and tunnus='$sarjatun'";
					$sarjares = mysql_query($query) or pupe_error($query);

					// Tutkitaan oliko tämä sarjanumero käytettytuote?
					$query = "	SELECT $tunnuskentta rivitunnus, kaytetty
								FROM sarjanumeroseuranta
								WHERE tunnus = '$sarjatun'";
					$sarres = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarres);

					if ($sarjarow["kaytetty"] == 'K') {
						$query = "	UPDATE tilausrivi
									SET alv=alv+500
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$sarjarow[rivitunnus]'
									and alv < 500";
						$sarjares = mysql_query($query) or pupe_error($query);
					}

					//Tutkitaan lisävarusteita
					if ($tunnuskentta == 'myyntirivitunnus') {
						//$rivitunnus rikkoontuu lisaarivi.incissä
						$rivitunnus_sarjans = $rivitunnus;
						$rivitunnus 		= "";
						
						//Tutkitaan lisävarusteita
						$query = "	SELECT tilausrivi_osto.perheid2
									FROM sarjanumeroseuranta 
									JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus=tilausrivi_osto.perheid2
									WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
									and sarjanumeroseuranta.tunnus  = '$sarjatun'";
						$sarjares = mysql_query($query) or pupe_error($query);
						
						if (mysql_num_rows($sarjares) == 1) {
							$sarjarow = mysql_fetch_array($sarjares);
												
							$query = "	SELECT tunnus, perheid2
										FROM tilausrivi 
										WHERE yhtio  = '$kukarow[yhtio]' 
										and tunnus   = '$rivitunnus_sarjans'";
							$myyres = mysql_query($query) or pupe_error($query);
							$myyrow = mysql_fetch_array($myyres);
														
							if($myyrow["perheid2"] == 0) {
								$query = "	UPDATE tilausrivi
								 			SET perheid2 = tunnus
											WHERE yhtio  = '$kukarow[yhtio]' 
											and tunnus   = '$rivitunnus_sarjans'";
								$myyres = mysql_query($query) or pupe_error($query);
							
								$myyrow["perheid2"] = $myyrow["tunnus"];	
							}
							
							$query = "	SELECT tuoteno, kpl, tilkpl, var
										FROM tilausrivi 
										WHERE yhtio  = '$kukarow[yhtio]' 
										and perheid2 = '$sarjarow[perheid2]'
										and tyyppi  != 'D'
										and tunnus  != '$sarjarow[perheid2]'
										and perheid2!= 0";
							$sarjares = mysql_query($query) or pupe_error($query);
						
							while ($sarjarow = mysql_fetch_array($sarjares)) {
						
								// haetaan tuotteen tiedot
								$query = "	select *
											from tuote
											where yhtio = '$kukarow[yhtio]'
											and tuoteno = '$sarjarow[tuoteno]'";
								$tuoteres = mysql_query($query);

								if (mysql_num_rows($tuoteres) == 0) {
									echo "<font class='error'>Tuotetta $sarjarow[tuoteno] ei löydy!</font><br>";
								}
								else {
									// tuote löytyi ok, lisätään rivi
									$trow = mysql_fetch_array($tuoteres);

									$ytunnus         = $laskurow["ytunnus"];
									
									if ($sarjarow["var"] != 'P') {
										$kpl		 = $sarjarow["kpl"];
										$var		 = "";
									}
									else {
										$kpl		 = $sarjarow["tilkpl"];
										$var		 = "P";	
									}
									
									$tuoteno         = $sarjarow["tuoteno"];
									$perheid2		 = $myyrow["perheid2"];
									
									//Tutkitaan vielä ettei tämä lisävaruste ole jo tän tuotteen alla
									$query = "	SELECT tunnus
												FROM tilausrivi 
												WHERE yhtio  = '$kukarow[yhtio]' 
												and tuoteno  = '$tuoteno'
												and perheid2 = '$perheid2'";
									$tarkres = mysql_query($query) or pupe_error($query);
									 
									if (mysql_num_rows($tarkres) == 0) {
									
										$toimaika 	     = $laskurow["toimaika"];
										$kerayspvm	     = $laskurow["kerayspvm"];
										$hinta 		     = "";
										$netto 		     = "";
										$ale 		     = "";
										$alv		     = "";									
										$varasto 	     = "";
										$rivitunnus		 = "";
										$korvaavakielto	 = "";
										$varataan_saldoa = "";
										$myy_sarjatunnus = ""; // Pitää fixata
										$perheid 		 = 0;
								
										// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
										if (is_numeric($ostoskori)) {
											lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
											$kukarow["kesken"] = "";
										}
										elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
											require ("../tilauskasittely/lisaarivi.inc");
										}
										else {
											require ("lisaarivi.inc");
										}

										echo "<font class='message'>Lisättiin $kpl kpl tuotetta $tuoteno.</font><br>";
									}
								}
							}
						}
						
						echo "<br><br>";
						$rivitunnus 		= $rivitunnus_sarjans;
						$rivitunnus_sarjans = "";
					}
				}
			}
		}
	}

	if (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA") and $hyvitysrivi != "ON") {
		//Jos tuote on marginaaliverotuksen alainen niin sen pitää olla onnistuneesti ostettu jotta sen voi myydä
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus				osto_tunnus,
					lasku_osto.nimi					osto_nimi,
					lasku_myynti.tunnus				myynti_tunnus,
					lasku_myynti.nimi				myynti_nimi,
					tilausrivi_osto.rivihinta		ostohinta,
					tilausrivi_osto.perheid2		osto_perheid2,
					tilausrivi_myynti.rivihinta		myyntihinta
					FROM sarjanumeroseuranta use index (yhtio_myyntirivi)
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno					
					WHERE
					sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
					and sarjanumeroseuranta.myyntirivitunnus in (0,$rivitunnus)
					and (sarjanumeroseuranta.kaytetty = ''
					or  (sarjanumeroseuranta.kaytetty != ''
					and sarjanumeroseuranta.ostorivitunnus > 0
					and tilausrivi_osto.laskutettuaika > '0000-00-00'))
					ORDER BY sarjanumeroseuranta.sarjanumero";
	}
	elseif($from == "riviosto" or $from == "kohdista" or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA") and $hyvitysrivi == "ON")) {
		// Haetaan vain sellaiset sarjanumerot jotka on vielä vapaita
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus				osto_tunnus,
					lasku_osto.nimi					osto_nimi,
					lasku_myynti.tunnus				myynti_tunnus,
					lasku_myynti.nimi				myynti_nimi,
					tilausrivi_osto.rivihinta		ostohinta,
					tilausrivi_osto.perheid2		osto_perheid2,
					tilausrivi_myynti.rivihinta		myyntihinta
					FROM sarjanumeroseuranta use index (yhtio_ostorivi)
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
					and sarjanumeroseuranta.ostorivitunnus in (0,$rivitunnus)
					order by sarjanumero";
	}
	else {
		$lisa  = "";

		if ($ostotilaus_haku != "") {
			if (is_numeric($ostotilaus_haku)) {
				$lisa .= " and lasku_osto.tunnus='$ostotilaus_haku' ";
			}
			else {
				$lisa .= " and match (lasku_osto.nimi) against ('$ostotilaus_haku*' IN BOOLEAN MODE) ";
			}
		}

		if ($myyntitilaus_haku != "") {
			if (is_numeric($myyntitilaus_haku)) {
				$lisa .= " and lasku_myynti.tunnus='$myyntitilaus_haku' ";
			}
			else {
				$lisa .= " and match (lasku_myynti.nimi) against ('$myyntitilaus_haku*' IN BOOLEAN MODE) ";
			}
		}

		if ($lisatieto_haku) {
			$lisa .= " and sarjanumeroseuranta.lisatieto like '$lisatieto_haku%' ";
		}

		if ($tuoteno_haku) {
			$lisa .= " and sarjanumeroseuranta.tuoteno like '$tuoteno_haku%' ";
		}

		if ($nimitys_haku) {
			$lisa .= " and tuote.nimitys like '$nimitys_haku%' ";
		}

		if ($sarjanumero_haku) {
			$lisa .= " and sarjanumeroseuranta.sarjanumero like '$sarjanumero_haku%' ";
		}
		
		
		if ($lisa == "") {
			$lisa = " HAVING osto_tunnus is null or myynti_tunnus is null";
		}

		// Näytetään kaikki
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus				osto_tunnus,
					lasku_osto.nimi					osto_nimi,
					lasku_myynti.tunnus				myynti_tunnus,
					lasku_myynti.nimi				myynti_nimi,
					tilausrivi_osto.rivihinta		ostohinta,
					tilausrivi_osto.perheid2		osto_perheid2,
					tilausrivi_myynti.rivihinta		myyntihinta
					FROM sarjanumeroseuranta
					LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					$lisa
					ORDER BY tuoteno, myyntirivitunnus
					LIMIT 50";
	}
	$sarjares = mysql_query($query) or pupe_error($query);
	
	if ($rivirow["tuoteno"] != '') {
		echo "<table>";
		echo "<tr><th>".t("Tuotenumero")."</th><td>$rivirow[tuoteno] $rivirow[nimitys]</td></tr>";
		echo "<tr><th>".t("Määrä")."</th><td>$rivirow[varattu] $rivirow[yksikko]</td></tr>";
		echo "</table><br>";
	}

	if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
		require("sarjanumeron_lisatiedot_popup.inc");
	}

	echo js_popup(500);
	$divit = "";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Sarjanumero")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Ostotilaus")."</th>";
	echo "<th>".t("Myyntitilaus")."</th>";
	echo "<th>".t("Hinnat")."</th>";
	echo "<th>".t("Valitse")."</th>";
	echo "<th>".t("Muokkaa")."</th>";
	echo "<th>".t("Poista")."</th>";
	echo "<th>".t("Lisätiedot")."</th>";
	echo "</tr>";

	//Kursorinohjaus
	$formi	= "haku";
	$kentta = "sarjanumero_haku";

	echo "<form name='haku' action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value = '$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value = '$from'>";
	echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
	echo "<input type='hidden' name='toiminto' 			value = '$toiminto'>";
	echo "<input type='hidden' name='sarjatunnus' 		value = '$sarjatunnus'>";
	echo "<input type='hidden' name='otunnus' 			value = '$otunnus'>";
	echo "<tr>";
	echo "<td><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
	echo "<td><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
	echo "<td><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
	echo "<td><input type='text' size='10' name='ostotilaus_haku' 		value='$ostotilaus_haku'></td>";
	echo "<td><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'></td>";
	echo "<td></td><td></td><td></td><td></td><td></td><td class='back'><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value='$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value='$from'>";
	echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
	echo "<input type='hidden' name='toiminto' 			value='$toiminto'>";
	echo "<input type='hidden' name='sarjatunnus' 		value='$sarjatunnus'>";
	echo "<input type='hidden' name='otunnus' 			value='$otunnus'>";
	echo "<input type='hidden' name='formista' 			value='kylla'>";
	echo "<input type='hidden' name='sarjanumero_haku' 	value='$sarjanumero_haku'>";
	echo "<input type='hidden' name='tuoteno_haku' 		value='$tuoteno_haku'>";
	echo "<input type='hidden' name='nimitys_haku' 		value='$nimitys_haku'>";
	echo "<input type='hidden' name='ostotilaus_haku' 	value='$ostotilaus_haku'>";
	echo "<input type='hidden' name='myyntitilaus_haku'	value='$myyntitilaus_haku'>";
	echo "<input type='hidden' name='lisatieto_haku' 	value='$lisatieto_haku'>";
	
	while ($sarjarow = mysql_fetch_array($sarjares)) {

		if (function_exists("sarjanumeronlisatiedot_popup")) {
			list($divitx, $text_output) = sarjanumeronlisatiedot_popup ($sarjarow["tunnus"], '', 'popup', '');
			$divit .= $divitx;
		}

		$sarjarow["nimitys"] = str_replace("\n", "<br>", $sarjarow["nimitys"]);
		 
		//katsotaan onko sarjanumerolle liitetty kulukeikka
		$query  = "	select *
					from lasku
					where yhtio		 = '$kukarow[yhtio]'
					and tila		 = 'K'
					and alatila		 = ''
					and liitostunnus = '$sarjarow[tunnus]'
					and ytunnus 	 = '$sarjarow[tunnus]'";
		$keikkares = mysql_query($query) or pupe_error($query);
		
		unset($kulurow);
		unset($keikkarow);
		
		if (mysql_num_rows($keikkares) == 1) {
			$keikkarow = mysql_fetch_array($keikkares);

			//Haetaan kaikki keittaan liitettyjen laskujen summa
			// katsotaan onko tälle keikalle jo liitetty kululaskuja
			$query = "	SELECT sum(summa) summa, valkoodi, vienti
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and tila 		= 'K'
						and laskunro 	= '$keikkarow[laskunro]'
						and vanhatunnus <> 0
						and vienti in ('B','E','H')
						GROUP BY valkoodi, vienti";
			$result = mysql_query($query) or pupe_error($query);

			// jos on, haetaan liitettyjen laskujen
			if (mysql_num_rows($result) == 1) {
				$kulurow = mysql_fetch_array($result);
			}
		}
		
		echo "<tr>";
		echo "<td valign='top'>$sarjarow[sarjanumero]</td>";
		echo "<td colspan='2' valign='top'>$sarjarow[tuoteno]<br>$sarjarow[nimitys]</td>";

		if ($sarjarow["ostorivitunnus"] == 0) {
			$sarjarow["ostorivitunnus"] = "";
		}
		if ($sarjarow["myyntirivitunnus"] == 0) {
			$sarjarow["myyntirivitunnus"] = "";
		} 

		//echo "<td colspan='2' valign='top'>$sarjarow[osto_tunnus] $sarjarow[osto_nimi]<br>$sarjarow[myynti_tunnus] $sarjarow[myynti_nimi]</td>";
		
		echo "<td colspan='2' valign='top'><a href='../raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$sarjarow[osto_tunnus]'>$sarjarow[osto_tunnus] $sarjarow[osto_nimi]</a><br><a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]'>$sarjarow[myynti_tunnus] $sarjarow[myynti_nimi]</a></td>";
		
		//Haetaan myös ns. lisävarusteiden hinnat
		if ($sarjarow["osto_perheid2"] != 0) {
			$query = "	select sum(rivihinta) rivihinta
						from tilausrivi
						where yhtio 	= '$yhtiorow[yhtio]'
						and perheid2 	= '$sarjarow[osto_perheid2]'
						and tyyppi 	   != 'D'
						and tunnus     != perheid2
						order by tunnus";
			$tilrivires = mysql_query($query) or pupe_error($query);
			$tilrivirow = mysql_fetch_array($tilrivires);
		}
		else {
			$tilrivirow = "";
		}
		
		$sarjarow["ostohinta"] 		= sprintf('%.2f', $sarjarow["ostohinta"]);
		$sarjarow["myyntihinta"] 	= sprintf('%.2f', $sarjarow["myyntihinta"]);
		$kulurow["summa"] 			= sprintf('%.2f', $kulurow["summa"]);
		$yhteensa = $sarjarow["myyntihinta"] - $sarjarow["ostohinta"] - $kulurow["summa"] - $tilrivirow["rivihinta"];
		
		echo "<td valign='top' align='right' nowrap>";
		if ($sarjarow["ostohinta"] != 0) 	echo "-$sarjarow[ostohinta]<br>";
		if ($tilrivirow["rivihinta"] != 0) 	echo "-$tilrivirow[rivihinta]<br>";
		if ($kulurow["summa"] != 0) 		echo "-$kulurow[summa]<br>";
		if ($sarjarow["myyntihinta"] != 0) 	echo "+$sarjarow[myyntihinta]<br>";
		if ($sarjarow["ostohinta"] != 0 or $kulurow["summa"] != 0 or $sarjarow["myyntihinta"] != 0) echo "=$yhteensa";
		
		echo "</td>";

		if (($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
			$chk = "";
			if ($sarjarow[$tunnuskentta] == $rivitunnus) {
				$chk="CHECKED";
			}

			if ($tunnuskentta == "ostorivitunnus" and $sarjarow["kpl"] != 0) {
				echo "<td valign='top'>".t("Lukittu")."</td>";
			}
			elseif (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA") or ($from == "riviosto" or $from == "kohdista")) {
				echo "<input type='hidden' name='sarjat[]' value='$sarjarow[tunnus]'>";
				echo "<td valign='top'><input type='checkbox' name='sarjataan[]' value='$sarjarow[tunnus]' $chk onclick='submit()'></td>";
			}
		}
		else {
			echo "<td valign='top'></td>";
		}
		
		//jos saa muuttaa niin näytetään muokkaa linkki
		echo "<td valign='top' nowrap><a href='$PHP_SELF?toiminto=MUOKKAA&$tunnuskentta=$rivitunnus&from=$from&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku'>".t("Muokkaa")."</a>";
		
		if ($sarjarow['ostorivitunnus'] > 0 and $sarjarow['myyntirivitunnus'] == 0 and $from == "") {
			if ($keikkarow["tunnus"] > 0) {
				$keikkalisa = "&otunnus=$keikkarow[tunnus]";
			}
			else {
				$keikkalisa = "&luouusikeikka=OK&liitostunnus=$sarjarow[tunnus]";
			}
			
			echo "<br><a href='$PHP_SELF?toiminto=kululaskut$keikkalisa'>".t("Liitä kululasku")."</a>";
		}
		
		echo "</td>";
		
		if ($sarjarow['ostorivitunnus'] == 0 and $sarjarow['myyntirivitunnus'] == 0) {
			echo "<td valign='top'><a href='$PHP_SELF?toiminto=POISTA&$tunnuskentta=$rivitunnus&from=$from&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]'>".t("Poista")."</a></td>";
		}
		else {
			echo "<td valign='top'></td>";
		}

		// aika karseeta, mutta katotaan voidaanko tällästä optiota näyttää yks tosi firma specific juttu
		$query = "describe sarjanumeron_lisatiedot";
		$res = mysql_query($query);

		if (mysql_error() == "") {
			$query = "	SELECT *
						FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
						WHERE yhtio		 = '$kukarow[yhtio]'
						and liitostunnus = '$sarjarow[tunnus]'";
			$lisares = mysql_query($query) or pupe_error($query);
			$lisarow = mysql_fetch_array($lisares);
			
			if ($lisarow["tunnus"] != 0) {
				$ylisa = "&tunnus=$lisarow[tunnus]";
			}
			else {
				$ylisa = "&liitostunnus=$sarjarow[tunnus]&uusi=1";
			}
			
			echo "<td valign='top' class='menu' onmouseout=\"popUp(event,'$sarjarow[tunnus]')\" onmouseover=\"popUp(event,'$sarjarow[tunnus]')\"><a href='../yllapito.php?toim=sarjanumeron_lisatiedot$ylisa&lopetus=$PHP_SELF!!!!$tunnuskentta=$rivitunnus!!from=$from!!otunnus=$otunnus!!sarjanumero_haku=$sarjanumero_haku!!tuoteno_haku=$tuoteno_haku!!nimitys_haku=$nimitys_haku!!ostotilaus_haku=$ostotilaus_haku!!myyntitilaus_haku=$myyntitilaus_haku!!lisatieto_haku=$lisatieto_haku'>".t("Lisätiedot")."</a></td>";
			
		}
		else {
			echo "<td></td>";			
		}
		echo "</tr>";
	}

	echo "</form>";
	echo "</table>";

	//Piilotetut divit jotka popappaa javascriptillä
	echo $divit;

	if ($toiminto== '') {
		$sarjanumero 	= '';
		$lisatieto 		= '';
		$chk 			= '';
	}

	if ($rivirow["tuoteno"] != '') {
		echo "	<form name='sarjaformi' action='$PHP_SELF' method='post'>
				<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>
				<input type='hidden' name='from' value='$from'>
				<input type='hidden' name='otunnus' value='$otunnus'>
				<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
				<input type='hidden' name='toiminto' value='LISAA'>";


		$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
					FROM sarjanumeroseuranta
					WHERE yhtio='$kukarow[yhtio]'
					and sarjanumero like '".t("PUUTTUU")."-%'";
		$vresult = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vresult);
		
		$nxt = t("PUUTTUU")."-".$vrow["sarjanumero"];
		
		echo "<br><table>";
		echo "<tr><th colspan='2'>".t("Lisää uusi sarjanumero")."</th></tr>";
		echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td><td class='back'><a href='#' onclick='document.sarjaformi.sarjanumero.value=\"$nxt\";'>".t("Sarjanumero ei tiedossa")."</a></td></tr>";
		echo "<tr><th>".t("Lisätieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$lisatieto</textarea></td></tr>";

		$chk = "";
		if ($kaytetty == "K") {
			$chk = "CHECKED";
		}

		echo "<tr><th>".t("Käytetty")."</th><td><input type='checkbox' name='kaytetty' value='K'></td>";
		echo "<td class='back'><input type='submit' value='".t("Lisää")."'></td>";
		echo "</form>";
		echo "</tr></table>";
	}

	echo "<br>";

	if ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS") {
		echo "<form method='post' action='tilaus_myynti.php'>
			<input type='hidden' name='toim' value='$from'>
			<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
			<input type='submit' value='".t("Takaisin tilaukselle")."'>
			</form>";
	}

	if ($from == "riviosto") {
		echo "<form method='post' action='tilaus_osto.php'>
			<input type='hidden' name='tee' value='Y'>
			<input type='hidden' name='aktivoinnista' value='true'>
			<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
			<input type='submit' value='".t("Takaisin tilaukselle")."'>
			</form>";
	}

	if ($from == "kohdista") {
		echo "<form method='post' action='keikka.php'>
			<input type='hidden' name='toiminto' value='kohdista'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='submit' value='".t("Takaisin keikkaan")."'>
			</form>";
	}

	if ($from == "KERAA") {
		echo "<form method='post' action='keraa.php'>
			<input type='hidden' name='id' value='$otunnus'>
			<input type='submit' value='".t("Takaisin keräykseen")."'>
			</form>";
	}

	require ("../inc/footer.inc");

?>