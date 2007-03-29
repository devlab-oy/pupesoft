<?php

	// otetaan sis‰‰n voidaan ottaa $myyntirivitunnus tai $ostorivitunnus
	// ja $from niin tiedet‰‰n mist‰ tullaan ja minne palata

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
		
		if ($rivirow["varattu"] < 0 and ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA")) {
			// t‰ss‰ muutetaan myyntirivitunnus ostorivitunnukseksi jos $rivirow["varattu"] eli kappalem‰‰r‰ on negatiivinen
			$tunnuskentta 		= "ostorivitunnus";
			$rivirow["varattu"] = abs($rivirow["varattu"]);
			$hyvitysrivi 		= "ON";
		}
		elseif ($rivirow["varattu"] < 0 and ($from == "riviosto" or $from == "kohdista")) {
			// t‰ss‰ muutetaan ostorivitunnus myyntirivitunnukseksi jos $rivirow["varattu"] eli kappalem‰‰r‰ on negatiivinen
			$tunnuskentta 		= "myyntirivitunnus";
			$rivirow["varattu"] = abs($rivirow["varattu"]);
			$ostonhyvitysrivi 	= "ON";
		}
	}

	//liitet‰‰n kululasku sarjanumeroon
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

	// Halutaan muuttaa sarjanumeron tietoja
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
				$query = "	SELECT tunnus, kaytetty, ostorivitunnus, myyntirivitunnus
							FROM sarjanumeroseuranta
							WHERE tunnus = '$sarjatunnus'";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$query = "	UPDATE tilausrivi
							SET alv=alv+500
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus in ($sarrow[ostorivitunnus], $sarrow[myyntirivitunnus])
							and laskutettuaika='0000-00-00'
							and alv <= 500";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	SELECT tunnus, kaytetty, ostorivitunnus, myyntirivitunnus
							FROM sarjanumeroseuranta
							WHERE tunnus = '$sarjatunnus'";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$query = "	UPDATE tilausrivi
							SET alv=alv-500
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus in ($sarrow[ostorivitunnus], $sarrow[myyntirivitunnus])
							and laskutettuaika='0000-00-00'
							and alv >= 500";
				$sarjares = mysql_query($query) or pupe_error($query);
			}

			echo "<font class='message'>".t("P‰vitettiin sarjanumeron tiedot")."!</font><br><br>";

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
						<input type='hidden' name='aputoim' 			value='$aputoim'>
						<input type='hidden' name='otunnus' 			value='$otunnus'>
						<input type='hidden' name='toiminto' 			value='MUOKKAA'>
						<input type='hidden' name='sarjatunnus' 		value='$sarjatunnus'>
						<input type='hidden' name='sarjanumero_haku' 	value='$sarjanumero_haku'>
						<input type='hidden' name='tuoteno_haku' 		value='$tuoteno_haku'>
						<input type='hidden' name='nimitys_haku' 		value='$nimitys_haku'>					
						<input type='hidden' name='varasto_haku' 		value='$varasto_haku'>
						<input type='hidden' name='ostotilaus_haku' 	value='$ostotilaus_haku'
						<input type='hidden' name='myyntitilaus_haku'	value='$myyntitilaus_haku'>
						<input type='hidden' name='lisatieto_haku' 		value='$lisatieto_haku'>";

				echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$muutarow[sarjanumero]'></td></tr>";
				echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$muutarow[lisatieto]</textarea></td></tr>";

				$chk = "";
				if ($muutarow["kaytetty"] == 'K') {
					$chk = "CHECKED";
				}

				echo "<tr><th>".t("K‰ytetty")."</th><td><input type='checkbox' name='kaytetty' value='K' $chk></td>";
				echo "<td class='back'><input type='submit' name='PAIVITA' value='".t("P‰ivit‰")."'></td>";
				echo "</tr></form></table><br><br>";
			}
			else {
				echo t("Muutettava sarjanumero on kadonnut")."!!!!<br>";
			}
		}
	}

	// ollaan syˆtetty uusi
	if ($toiminto == 'LISAA' and trim($sarjanumero) != '') {

		$query = "	SELECT *
					FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
					WHERE yhtio = '$kukarow[yhtio]'
					and sarjanumero = '$sarjanumero'
					and tuoteno = '$rivirow[tuoteno]'
					and (ostorivitunnus=0 or myyntirivitunnus=0)";
		$sarjares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sarjares) == 0) {
			//jos ollaan syˆtetty kokonaan uusi sarjanuero
			$query = "insert into sarjanumeroseuranta (yhtio, tuoteno, sarjanumero, lisatieto, $tunnuskentta, kaytetty) VALUES ('$kukarow[yhtio]','$rivirow[tuoteno]','$sarjanumero','$lisatieto','','$kaytetty')";
			$sarjares = mysql_query($query) or pupe_error($query);
			$tun = mysql_insert_id();
			
			echo "<font class='message'>".t("Lis‰ttiin sarjanumero")." $sarjanumero.</font><br><br>";

			$sarjanumero	= "";
			$lisatieto		= "";
			$kaytetty		= "";			
		}
		else {
			$sarjarow = mysql_fetch_array($sarjares);
			echo "<font class='error'>".t("Sarjanumero lˆytyy jo tuotteelta")." $sarjarow[tuoteno]/$sarjanumero.</font><br><br>";
		}
	}

	// ollaan valittu joku tunnus listasta ja halutaan liitt‰‰ se tilausriviin tai poistaa se tilausrivilt‰
	if ($from != '' and $rivitunnus != "" and $formista == "kylla") {
		// jos olemme ruksanneet v‰hemm‰n tai yht‰ paljon kuin tuotteita on rivill‰, voidaan p‰ivitt‰‰ muutokset
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
			echo "<font class='error'>".sprintf(t('Riviin voi liitt‰‰ enint‰‰n %s sarjanumeroa'), abs($rivirow["varattu"])).".</font><br><br>";
		}
				
		if ($rivirow["varattu"] >= count($sarjataan)) {
			//jos mik‰‰n ei ole ruksattu niin ei tietenk‰‰n halutakkaan lis‰t‰ mit‰‰n sarjanumeroa
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

					// Tutkitaan oliko t‰m‰ sarjanumero k‰ytettytuote?
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

					//Tutkitaan lis‰varusteita
					if ($tunnuskentta == 'myyntirivitunnus') {
						//Hanskataan sarjanumerollisten tuotteiden lis‰varusteet
						if ($sarjatun > 0 and $rivitunnus > 0) {
							require("sarjanumeron_lisavarlisays.inc");
							
							$palautus = lisavarlisays($sarjatun, $rivitunnus);
								
							if ($palautus != "OK") {	
								echo "<font class='error'>$palautus</font><br><br>";
								
								$query = "	UPDATE sarjanumeroseuranta
											SET $tunnuskentta=''
											$paikkalisa
											WHERE yhtio='$kukarow[yhtio]'
											and tunnus='$sarjatun'";
								$sarjares = mysql_query($query) or pupe_error($query);
							}					
						}
					}
				}
			}
		}
	}
	
	$lisa  = "";
	$lisa2 = "";

	if ($ostotilaus_haku != "") {
		if (is_numeric($ostotilaus_haku)) {
			if ($ostotilaus_haku == 0) {
				$lisa .= " and lasku_osto.tunnus is null ";
			}
			else {
				$lisa .= " and lasku_osto.tunnus='$ostotilaus_haku' ";
			}
		}
		else {
			$lisa .= " and match (lasku_osto.nimi) against ('$ostotilaus_haku*' IN BOOLEAN MODE) ";
		}
	}

	if ($myyntitilaus_haku != "") {
		if (is_numeric($myyntitilaus_haku)) {				
			if ($myyntitilaus_haku == 0) {
				$lisa .= " and (lasku_myynti.tunnus is null or lasku_myynti.tila = 'T') ";
			}
			else {
				$lisa .= " and lasku_myynti.tunnus='$myyntitilaus_haku' ";
			}
		}
		else {
			$lisa .= " and match (lasku_myynti.nimi) against ('$myyntitilaus_haku*' IN BOOLEAN MODE) ";
		}
	}

	if ($lisatieto_haku != "") {
		$lisa .= " and sarjanumeroseuranta.lisatieto like '%$lisatieto_haku%' ";
	}

	if ($tuoteno_haku != "") {
		$lisa .= " and sarjanumeroseuranta.tuoteno like '%$tuoteno_haku%' ";
	}

	if ($sarjanumero_haku != "") {
		$lisa .= " and sarjanumeroseuranta.sarjanumero like '%$sarjanumero_haku%' ";
	}
	
	if ($varasto_haku != "") {
		$lisa .= " and varastopaikat.nimitys like '%$varasto_haku%' ";
	}
	
	if ($nimitys_haku != "") {
		$lisa2 = " HAVING nimitys like '%$nimitys_haku%' ";
	}		
	
	if ($lisa == "" and $lisa2 == "" and $from == "") {
		$lisa2 = " HAVING osto_tunnus is null or myynti_tunnus is null";
	}

	if ((($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi == "ON") or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA") and $hyvitysrivi != "ON")) {
		//Jos tuote on marginaaliverotuksen alainen niin sen pit‰‰ olla onnistuneesti ostettu jotta sen voi myyd‰ and (sarjanumeroseuranta.kaytetty = '' or (sarjanumeroseuranta.kaytetty != '' and sarjanumeroseuranta.ostorivitunnus > 0 and tilausrivi_osto.laskutettuaika > '0000-00-00'))
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka					
					FROM sarjanumeroseuranta use index (yhtio_myyntirivi)
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno					
					LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
					and concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					and concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					WHERE
					sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
					and (sarjanumeroseuranta.myyntirivitunnus in (0, $rivitunnus) or lasku_myynti.tila='T')
					$lisa
					$lisa2
					ORDER BY sarjanumeroseuranta.sarjanumero";
	}
	elseif((($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi != "ON") or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA") and $hyvitysrivi == "ON")) {
		// Haetaan vain sellaiset sarjanumerot jotka on viel‰ vapaita
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,					if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka					
					FROM sarjanumeroseuranta use index (yhtio_ostorivi)
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
					LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
					and concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					and concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
					and sarjanumeroseuranta.ostorivitunnus in (0, $rivitunnus)
					$lisa
					$lisa2
					ORDER BY sarjanumeroseuranta.sarjanumero";
	}
	else {
		// N‰ytet‰‰n kaikki
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka
					FROM sarjanumeroseuranta
					LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
					and concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					and concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					$lisa
					$lisa2
					ORDER BY sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.sarjanumero
					LIMIT 50";
	}
	$sarjares = mysql_query($query) or pupe_error($query);
	
	if ($rivirow["tuoteno"] != '') {
		echo "<table>";
		echo "<tr><th>".t("Tuotenumero")."</th><td>$rivirow[tuoteno] $rivirow[nimitys]</td></tr>";
		echo "<tr><th>".t("M‰‰r‰")."</th><td>$rivirow[varattu] $rivirow[yksikko]</td></tr>";
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
	echo "<th>".t("Varastopaikka")."</th>";
	echo "<th>".t("Ostotilaus")."</th>";
	echo "<th>".t("Myyntitilaus")."</th>";
	//echo "<th>".t("Hinnat")."</th>";
	
	if (($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
		echo "<th>".t("Valitse")."</th>";
	}

	echo "<th>".t("Lis‰tiedot")."</th>";
	echo "</tr>";
	
	echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
				function verify(){
					msg = '".t("Haluatko todella poistaa t‰m‰n sarjanumeron")."?';
					return confirm(msg);
				}
			</SCRIPT>";

	//Kursorinohjaus
	$formi	= "haku";
	$kentta = "sarjanumero_haku";

	echo "<form name='haku' action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value = '$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value = '$from'>";
	echo "<input type='hidden' name='aputoim' 			value = '$aputoim'>";
	echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
	echo "<input type='hidden' name='toiminto' 			value = '$toiminto'>";
	echo "<input type='hidden' name='sarjatunnus' 		value = '$sarjatunnus'>";
	echo "<input type='hidden' name='otunnus' 			value = '$otunnus'>";
	echo "<tr>";
	echo "<td><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
	echo "<td><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
	echo "<td><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
	echo "<td><input type='text' size='10' name='varasto_haku' 			value='$varasto_haku'></td>";
	echo "<td><input type='text' size='10' name='ostotilaus_haku' 		value='$ostotilaus_haku'></td>";
	echo "<td><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'></td>";
	
	if (($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
		echo "<td></td>";
	}

	echo "<td></td>";
	echo "<td class='back'><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value='$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value='$from'>";
	echo "<input type='hidden' name='aputoim' 			value='$aputoim'>";
	echo "<input type='hidden' name='muut_siirrettavat' value='$muut_siirrettavat'>";
	echo "<input type='hidden' name='toiminto' 			value='$toiminto'>";
	echo "<input type='hidden' name='sarjatunnus' 		value='$sarjatunnus'>";
	echo "<input type='hidden' name='otunnus' 			value='$otunnus'>";
	echo "<input type='hidden' name='formista' 			value='kylla'>";
	echo "<input type='hidden' name='sarjanumero_haku' 	value='$sarjanumero_haku'>";
	echo "<input type='hidden' name='tuoteno_haku' 		value='$tuoteno_haku'>";
	echo "<input type='hidden' name='nimitys_haku' 		value='$nimitys_haku'>";
	echo "<input type='hidden' name='varasto_haku' 		value='$varasto_haku'>";	
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
					and alatila		 = 'S'
					and liitostunnus = '$sarjarow[tunnus]'
					and ytunnus 	 = '$sarjarow[tunnus]'";
		$keikkares = mysql_query($query) or pupe_error($query);

		unset($kulurow);
		unset($keikkarow);
		
		if (mysql_num_rows($keikkares) == 1) {
			$keikkarow = mysql_fetch_array($keikkares);
		}
		
		echo "<tr>";
		echo "<td valign='top'>$sarjarow[sarjanumero]</td>";
		echo "<td colspan='2' valign='top'>$sarjarow[tuoteno]<br>$sarjarow[nimitys]</td>";
		echo "<td valign='top'>$sarjarow[varastonimi]<br>$sarjarow[tuotepaikka]</td>";
		
		if ($sarjarow["ostorivitunnus"] == 0) {
			$sarjarow["ostorivitunnus"] = "";
		}
		if ($sarjarow["myyntirivitunnus"] == 0) {
			$sarjarow["myyntirivitunnus"] = "";
		} 
		
		echo "<td colspan='2' valign='top'><a href='../raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$sarjarow[osto_tunnus]'>$sarjarow[osto_tunnus] $sarjarow[osto_nimi]</a><br>";
		
		if ($sarjarow["myynti_tila"] == 'T') {
			$fnlina1 = "<font class='message'>(Tarjous: ";
			$fnlina2 = ")</font>";
		}
		else {
			$fnlina1 = "";
			$fnlina2 = "";
		}
		
		echo "<a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]'>$fnlina1 $sarjarow[myynti_tunnus] $sarjarow[myynti_nimi] $fnlina2</a></td>";
		
		if (($sarjarow[$tunnuskentta] == 0 or $sarjarow["myynti_tila"] == 'T' or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
			$chk = "";
			if ($sarjarow[$tunnuskentta] == $rivitunnus) {
				$chk="CHECKED";
			}

			if ($tunnuskentta == "ostorivitunnus" and $sarjarow["kpl"] != 0) {
				echo "<td valign='top'>".t("Lukittu")."</td>";
			}
			elseif (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA") or ($from == "riviosto" or $from == "kohdista")) {
				echo "<input type='hidden' name='sarjat[]' value='$sarjarow[tunnus]'>";
				echo "<td valign='top'><input type='checkbox' name='sarjataan[]' value='$sarjarow[tunnus]' $chk onclick='submit();'></td>";
			}
		}
		
		//jos saa muuttaa niin n‰ytet‰‰n muokkaa linkki
		echo "<td valign='top' nowrap><a href='$PHP_SELF?toiminto=MUOKKAA&$tunnuskentta=$rivitunnus&from=$from&aputoim=$aputoim&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&varasto_haku=$varasto_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku'>".t("Muokkaa")."</a>";
		
		if ($sarjarow['ostorivitunnus'] > 0 and $from == "") {
			if ($keikkarow["tunnus"] > 0) {
				$keikkalisa = "&otunnus=$keikkarow[tunnus]";
			}
			else {
				$keikkalisa = "&luouusikeikka=OK&liitostunnus=$sarjarow[tunnus]";
			}
			
			echo "<br><a href='$PHP_SELF?toiminto=kululaskut$keikkalisa'>".t("Liit‰ kululasku")."</a>";
		}
		
		// aika karseeta, mutta katotaan voidaanko t‰ll‰st‰ optiota n‰ytt‰‰ yks tosi firma specific juttu
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
			
			echo "<br><a href='../yllapito.php?toim=sarjanumeron_lisatiedot$ylisa&lopetus=$PHP_SELF////$tunnuskentta=$rivitunnus//from=$from//aputoim=$aputoim//otunnus=$otunnus//sarjanumero_haku=$sarjanumero_haku//tuoteno_haku=$tuoteno_haku//nimitys_haku=$nimitys_haku//varasto_haku=$varasto_haku//ostotilaus_haku=$ostotilaus_haku//myyntitilaus_haku=$myyntitilaus_haku//lisatieto_haku=$lisatieto_haku' onmouseout=\"popUp(event,'$sarjarow[tunnus]')\" onmouseover=\"popUp(event,'$sarjarow[tunnus]')\">".t("Lis‰tiedot")."</a>";
			
		}
		
		if ($sarjarow['ostorivitunnus'] == 0 and $sarjarow['myyntirivitunnus'] == 0 and $keikkarow["tunnus"] == 0) {
			echo "<br><a href='$PHP_SELF?toiminto=POISTA&$tunnuskentta=$rivitunnus&from=$from&aputoim=$aputoim&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&varasto_haku=$varasto_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku' onclick=\"return verify()\">".t("Poista")."</a>";
		}

		echo "</tr>";
	}

	echo "</form>";
	echo "</table>";

	//Piilotetut divit jotka popappaa javascriptill‰
	echo $divit;

	if ($toiminto== '') {
		$sarjanumero 	= '';
		$lisatieto 		= '';
		$chk 			= '';
	}

	if ($rivirow["tuoteno"] != '') {
		echo "	<form name='sarjaformi' action='$PHP_SELF' method='post'>
				<input type='hidden' name='$tunnuskentta' 		value='$rivitunnus'>
				<input type='hidden' name='from' 				value='$from'>
				<input type='hidden' name='aputoim' 			value='$aputoim'>
				<input type='hidden' name='otunnus' 			value='$otunnus'>
				<input type='hidden' name='muut_siirrettavat'	value='$muut_siirrettavat'>
				<input type='hidden' name='toiminto' 			value='LISAA'>";


		$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
					FROM sarjanumeroseuranta
					WHERE yhtio='$kukarow[yhtio]'
					and sarjanumero like '".t("PUUTTUU")."-%'";
		$vresult = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vresult);
		
		$nxt = t("PUUTTUU")."-".$vrow["sarjanumero"];
		
		echo "<br><table>";
		echo "<tr><th colspan='2'>".t("Lis‰‰ uusi sarjanumero")."</th></tr>";
		echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td><td class='back'><a href='#' onclick='document.sarjaformi.sarjanumero.value=\"$nxt\";'>".t("Sarjanumero ei tiedossa")."</a></td></tr>";
		echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$lisatieto</textarea></td></tr>";

		$chk = "";
		if ($kaytetty == "K") {
			$chk = "CHECKED";
		}

		echo "<tr><th>".t("K‰ytetty")."</th><td><input type='checkbox' name='kaytetty' value='K'></td>";
		echo "<td class='back'><input type='submit' value='".t("Lis‰‰")."'></td>";
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
			<input type='hidden' name='toim' value='$aputoim'>
			<input type='hidden' name='id'   value='$otunnus'>
			<input type='submit' value='".t("Takaisin ker‰ykseen")."'>
			</form>";
	}

	require ("../inc/footer.inc");

?>