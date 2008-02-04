<?php

	// otetaan sis‰‰n voidaan ottaa $myyntirivitunnus tai $ostorivitunnus
	// ja $from niin tiedet‰‰n mist‰ tullaan ja minne palata

	if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php") !== FALSE) {
		require("../inc/parametrit.inc");
	}
	
	echo "<SCRIPT type='text/javascript'>
			<!--
				function sarjanumeronlisatiedot_popup(tunnus) {
					window.open('$PHP_SELF?tunnus='+tunnus+'&toiminto=sarjanumeronlisatiedot_popup', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top=0,width=800,height=600');
				}
			//-->
			</SCRIPT>";
	
	if ($toiminto == "sarjanumeronlisatiedot_popup") {
		@include('sarjanumeron_lisatiedot_popup.inc');
		
		if ($kukarow["extranet"] != "") {
			$hinnat = 'MY';
		}
		else {
			$hinnat = '';
		}
		
		list($divitx, , , ,) = sarjanumeronlisatiedot_popup($tunnus, '', '', $hinnat, '');		
		echo "$divitx";		
		exit;
	}

	// Tarkastetaan k‰sitell‰‰nkˆ lis‰tietoja
	$query = "describe sarjanumeron_lisatiedot";
	$sarjatestres = mysql_query($query);

	if (mysql_error() == "") {
		$sarjanumeronLisatiedot = "OK";
	}
	else {
		$sarjanumeronLisatiedot = "";
	}
	
	if ($toiminto == "luouusitulo") {
		require('sarjanumeroseuranta_luouusitulo.inc');
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

	// Haetaan tilausrivin tiedot
	if ($from != '' and $rivitunnus != "") {
		$query    = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta, tuote.yksikko
						FROM tilausrivi use index (PRIMARY)
						JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus  = '$rivitunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$rivirow  = mysql_fetch_array($sarjares);

		$query    = "	SELECT *
						FROM lasku use index (PRIMARY)
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus	= '$rivirow[otunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$laskurow  = mysql_fetch_array($sarjares);

		//Jotta jt:tkin toimisi
		$rivirow["varattu"] = $rivirow["varattu"] + $rivirow["jt"];

		// jos varattu on nollaa ja kpl ei niin otetaan kpl (esim varastoon viedyt ostotilausrivit)
		if ($rivirow["varattu"] == 0 and $rivirow["kpl"] != 0) {
			$rivirow["varattu"] = $rivirow["kpl"];
		}

		if ($rivirow["varattu"] < 0 and ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA")) {
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

	// Liitet‰‰n kululasku sarjanumeroon
	if ($toiminto == "kululaskut") {
		require('kululaskut.inc');
		exit;
	}

	// Ollaan poistamassa sarjanumero-olio kokonaan
	if ($toiminto == 'POISTA') {
		$query = "	DELETE
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$sarjatunnus'
					and myyntirivitunnus = 0
					and ostorivitunnus   = 0";
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
			$query = "	SELECT tunnus, kaytetty, ostorivitunnus, myyntirivitunnus, tuoteno, perheid
						FROM sarjanumeroseuranta
						WHERE tunnus = '$sarjatunnus'";
			$sarres = mysql_query($query) or pupe_error($query);
			$sarrow = mysql_fetch_array($sarres);
			
			if ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F") {
				$query = "	UPDATE sarjanumeroseuranta
							SET lisatieto 	= '$lisatieto',
							sarjanumero 	= '$sarjanumero',
							kaytetty		= '$kaytetty',
							muuttaja		= '$kukarow[kuka]',
							muutospvm		= now(), 
							era_kpl			= '$era_kpl',
							parasta_ennen 	= '$pevva-$pekka-$peppa'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$sarjatunnus'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	UPDATE sarjanumeroseuranta
							SET lisatieto 	= '$lisatieto',
							sarjanumero 	= '$sarjanumero',
							kaytetty		= '$kaytetty',
							muuttaja		= '$kukarow[kuka]',
							muutospvm		= now(), 
							takuu_alku		= '$tvva-$tkka-$tppa',
							takuu_loppu		= '$tvvl-$tkkl-$tppl'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$sarjatunnus'";
				$sarjares = mysql_query($query) or pupe_error($query);

				if ($kaytetty != '') {
					$query = "	UPDATE tilausrivi
								SET alv=alv+500
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus in ($sarrow[ostorivitunnus], $sarrow[myyntirivitunnus])
								and laskutettuaika='0000-00-00'
								and alv <= 500";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	UPDATE tilausrivi
								SET alv=alv-500
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus in ($sarrow[ostorivitunnus], $sarrow[myyntirivitunnus])
								and laskutettuaika='0000-00-00'
								and alv >= 500";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}
			
			if (trim($nimitys_nimitys) != "") {
				$query = "	UPDATE tilausrivi
							SET nimitys = '$nimitys_nimitys'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$sarrow[ostorivitunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}

			echo "<font class='message'>".t("P‰vitettiin sarjanumeron tiedot")."!</font><br><br>";

			$sarjanumero	= "";
			$lisatieto		= "";
			$sarjatunnus	= "";
			$toiminto		= "";
			$kaytetty		= "";
			$era_kpl		= "";
		}
		else {
			$query = "	SELECT sarjanumeroseuranta.* , tuote.tuoteno, tuote.nimitys, tuote.sarjanumeroseuranta
						FROM sarjanumeroseuranta
						LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.tunnus  = '$sarjatunnus'";
			$muutares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($muutares) == 1) {

				$muutarow = mysql_fetch_array($muutares);

				echo "<table>";
				
				if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F") {
					echo "<tr><th colspan='2'>".t("Muuta er‰numerotietoja").":</th></tr>";
				}
				else {
					echo "<tr><th colspan='2'>".t("Muuta sarjanumerotietoja").":</th></tr>";
				}
				
				echo "<tr><th>".t("Tuotenumero")."</th><td>$muutarow[tuoteno] $muutarow[nimitys]</td></tr>";

				echo "	<form action='$PHP_SELF' method='post' name='muokkaaformi'>
						<input type='hidden' name='muut_siirrettavat'	value='$muut_siirrettavat'>
						<input type='hidden' name='$tunnuskentta' 		value='$rivitunnus'>
						<input type='hidden' name='from' 				value='$from'>
						<input type='hidden' name='lopetus' 			value='$lopetus'>
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
				
				if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F") {
					echo "<tr><th>".t("Er‰numero")."</th>";
				}
				else {
					echo "<tr><th>".t("Sarjanumero")."</th>";
				}
				
				$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio='$kukarow[yhtio]'
							and tuoteno = '$muutarow[tuoteno]'
							and sarjanumero like '".t("PUUTTUU")."-%'";
				$vresult = mysql_query($query) or pupe_error($query);
				$vrow = mysql_fetch_array($vresult);

				if ($vrow["sarjanumero"] > 0) {
					$nxt = t("PUUTTUU")."-".$vrow["sarjanumero"];	
				}
				else {
					$nxt = t("PUUTTUU")."-1";		
				}

				$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio='$kukarow[yhtio]'
							and tuoteno = '$muutarow[tuoteno]'
							and sarjanumero like '".t("EI SARJANUMEROA")."-%'";
				$vresult = mysql_query($query) or pupe_error($query);
				$vrow = mysql_fetch_array($vresult);

				if ($vrow["sarjanumero"] > 0) {
					$nxt2 = t("EI SARJANUMEROA")."-".$vrow["sarjanumero"];	
				}
				else {
					$nxt2 = t("EI SARJANUMEROA")."-1";		
				}
								
				echo "<td><input type='text' size='30' name='sarjanumero' value='$muutarow[sarjanumero]'> <a onclick='document.muokkaaformi.sarjanumero.value=\"$nxt\";'><u>".t("Sarjanumero ei tiedossa")."</u></a> <a onclick='document.muokkaaformi.sarjanumero.value=\"$nxt2\";'><u>".t("Ei Sarjanumeroa")."</u></a></td></tr>";
				
				if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F") {
					if ($muutarow["era_kpl"] >= 0 and $muutarow["myyntirivitunnus"] == 0 and ($muutarow["ostorivitunnus"] == 0 or $from == "kohdista")) {
						echo "<tr><th>".t("Er‰n suuruus")."</th><td><input type='text' size='30' name='era_kpl' value='$muutarow[era_kpl]'></td></tr>";
					}
					else {
						echo "<tr><th>".t("Er‰n suuruus")."</th><td>$muutarow[era_kpl]</td></tr>";	
						echo "<input type='hidden' name='era_kpl' value='$muutarow[era_kpl]'>";
					}
				}
				
				if ($muutarow["sarjanumeroseuranta"] == "F") {
					
					$pevva = substr($muutarow["parasta_ennen"],0,4);
					$pekka = substr($muutarow["parasta_ennen"],5,2);
					$peppa = substr($muutarow["parasta_ennen"],8,2);
					
					echo "<tr><th>".t("Parasta ennen")."</th><td>
						<input type='text' name='peppa' value='$peppa' size='3'>
						<input type='text' name='pekka' value='$pekka' size='3'>
						<input type='text' name='pevva' value='$pevva' size='5'></td></tr>";
				}
				
				
				if ($muutarow["sarjanumeroseuranta"] == "S") {
					$query	= "	SELECT tilausrivi.nimitys nimitys, tilausrivi.tunnus ostotunnus
								FROM tilausrivi						
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tunnus  = '$muutarow[ostorivitunnus]'";
					$nimires = mysql_query($query) or pupe_error($query);					
					$nimirow = mysql_fetch_array($nimires);
					
					echo "<tr><th>".t("Nimitys")."</th><td><input type='text' name='nimitys_nimitys' value='$nimirow[nimitys]' size='15'></td>";
				}
				
				echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$muutarow[lisatieto]</textarea></td></tr>";
																				
				if ($muutarow["sarjanumeroseuranta"] == "S") {					
					
					$chk = "";
					if ($muutarow["kaytetty"] == 'K') {
						$chk = "CHECKED";
					}

					echo "<tr><th>".t("K‰ytetty")."</th><td><input type='checkbox' name='kaytetty' value='K' $chk></td></tr>";
				
					$tvva = substr($muutarow["takuu_alku"],0,4);
					$tkka = substr($muutarow["takuu_alku"],5,2);
					$tppa = substr($muutarow["takuu_alku"],8,2);
				
					$tvvl = substr($muutarow["takuu_loppu"],0,4);
					$tkkl = substr($muutarow["takuu_loppu"],5,2);
					$tppl = substr($muutarow["takuu_loppu"],8,2);
				
					echo "<tr><th>".t("Takuu")."</th><td>
					<input type='text' name='tppa' value='$tppa' size='3'>
					<input type='text' name='tkka' value='$tkka' size='3'>
					<input type='text' name='tvva' value='$tvva' size='5'>
					- <input type='text' name='tppl' value='$tppl' size='3'>
					<input type='text' name='tkkl' value='$tkkl' size='3'>
					<input type='text' name='tvvl' value='$tvvl' size='5'></td>";
				}
				
				echo "<td class='back'><input type='submit' name='PAIVITA' value='".t("P‰ivit‰")."'></td>";
				echo "</tr></form></table><br><br>";
			}
			else {
				echo t("Muutettava sarjanumero on kadonnut")."!!!!<br>";
			}
		}
	}

	// Ollaan syˆtetty uusi
	if ($toiminto == 'LISAA' and trim($sarjanumero) != '') {
		
		$sarjanumero = trim($sarjanumero);
		$insok = "OK";
		
		if($rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "S") {
			$query = "	SELECT *
						FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
						WHERE yhtio 		= '$kukarow[yhtio]'
						and sarjanumero 	= '$sarjanumero'
						and tuoteno 		= '$rivirow[tuoteno]'
						and (ostorivitunnus = 0 or myyntirivitunnus = 0)";
		}
		else {
			if ((float) $era_kpl <= 0) {
				$insok = "EI";
				echo "<font class='error'>".t("Er‰lle on syˆtett‰v‰ kappalem‰‰r‰")." $rivirow[tuoteno]/$sarjanumero.</font><br><br>";
			}
			
			if ((float) $era_kpl > $rivirow["varattu"]) {
				$insok = "EI";
				echo "<font class='error'>".t("Er‰n koko on liian suuri")." $rivirow[varattu]/$era_kpl.</font><br><br>";
			}
			
			if ($from == "KERAA" and (float) $era_kpl != $rivirow["varattu"]) {
				$insok = "EI";
				echo "<font class='error'>".t("Er‰n koko on oltava sama kuin rivin m‰‰r‰")." $rivirow[varattu]/$era_kpl.</font><br><br>";
			}
			
			if ($from == "KERAA" and $rivirow["hyllyalue"] == "") {
				// Haetaan oletuspaikka
				$query = "	SELECT *
							FROM tuotepaikat 
							WHERE yhtio = '$kukarow[yhtio]' 
							and tuoteno = '$rivirow[tuoteno]'
							and oletus != ''";
				$result = mysql_query($query) or pupe_error($query);
				$saldorow = mysql_fetch_array($result);
				
				$rivirow["hyllyalue"] = $saldorow["hyllyalue"];
				$rivirow["hyllynro"]  = $saldorow["hyllynro"];
				$rivirow["hyllyvali"] = $saldorow["hyllyvali"];
				$rivirow["hyllytaso"] = $saldorow["hyllytaso"];
			}
			
			if ($from == "KERAA" and $tunnuskentta == "myyntirivitunnus") {
				$tunnuskentta = "ostorivitunnus";
			}

			// Samaan ostoriviin ei voida liitt‰‰ samaa er‰numeroa useaan kertaan, mutta muuten er‰numerot eiv‰t ole uniikkeja.
			$query = "	SELECT *
						FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
						WHERE yhtio 		 = '$kukarow[yhtio]'
						and sarjanumero 	 = '$sarjanumero'
						and tuoteno 		 = '$rivirow[tuoteno]'
						and $tunnuskentta	 = '$rivitunnus' 
						and myyntirivitunnus = 0";	
		}
				
		$sarjares = mysql_query($query) or pupe_error($query);

		if ($insok == "OK" and mysql_num_rows($sarjares) == 0) {
			
			//jos ollaan syˆtetty kokonaan uusi sarjanuero
			$query = "	INSERT into sarjanumeroseuranta 
						(yhtio, tuoteno, sarjanumero, lisatieto, $tunnuskentta, kaytetty, era_kpl, laatija, luontiaika, takuu_alku, takuu_loppu, hyllyalue, hyllynro, hyllyvali, hyllytaso)
						VALUES ('$kukarow[yhtio]','$rivirow[tuoteno]','$sarjanumero','$lisatieto','','$kaytetty','$era_kpl','$kukarow[kuka]',now(),'$tvva-$tkka-$tppa','$tvvl-$tkkl-$tppl', '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]')";
			$sarjares = mysql_query($query) or pupe_error($query);
			
			$tun = mysql_insert_id();

			if($sarjanumeronLisatiedot == "OK" and ($rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "S")) {
				$query = "	SELECT *
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivirow[tuoteno]'";
				$tuoteres = mysql_query($query) or pupe_error($query);
				$tuoterow = mysql_fetch_array($tuoteres);

				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio 	 = '$kukarow[yhtio]'
							and laji 		 = 'SARJANUMERON_LI'
							and selite  	 = 'MERKKI'
							and selitetark_2 = '$tuoterow[tuotemerkki]'
							ORDER BY jarjestys, selitetark_2";
				$vresult = mysql_query($query) or pupe_error($query);
				$vrow = mysql_fetch_array($vresult);
				
				$query = "	INSERT INTO sarjanumeron_lisatiedot
							SET yhtio			= '$kukarow[yhtio]',
							liitostunnus		= '$tun',
							laatija 			= '$kukarow[kuka]',
							luontiaika 			= now(),
							Leveys				= '$tuoterow[tuoteleveys]',
							Pituus				= '$tuoterow[tuotepituus]',
							Varirunko			= '$tuoterow[vari]',
							Suurin_henkiloluku	= '$tuoterow[suurin_henkiloluku]',
							Runkotyyppi			= '$tuoterow[runkotyyppi]',
							Materiaali			= '$tuoterow[materiaali]',
							Koneistus			= '$tuoterow[koneistus]',
							Tyyppi				= '$tuoterow[laitetyyppi]',
							Kilpi				= '$tuoterow[kilpi]',
							Sprinkleri 			= '$tuoterow[sprinkleri]',
							Teho_kw				= '$tuoterow[teho_kw]',
							Malli				= '$tuoterow[nimitys]',
							Merkki				= '$vrow[selitetark]'";
				$lisatietores_apu = mysql_query($query) or pupe_error($query);
			}

			
			if($rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "S") {
				echo "<font class='message'>".t("Lis‰ttiin sarjanumero")." $sarjanumero.</font><br><br>";
			}
			else {
				echo "<font class='message'>".t("Lis‰ttiin er‰numero")." $sarjanumero.</font><br><br>";
			}
			
			// Yritet‰‰n liitt‰‰ luotu sarjanumero t‰h‰n riviin
			if($rivitunnus > 0) {
				
				if ($valitut_sarjat != "") {
					$valitut_sarjat = $valitut_sarjat.",".$tun;
				}
				else {
					$valitut_sarjat = $tun;
				}
				
				$sarjataan 	= explode(",", $valitut_sarjat);
				$sarjat		= explode(",", $valitut_sarjat);
				$formista	= "kylla";
			}

			$sarjanumero	= "";
			$lisatieto		= "";
			$kaytetty		= "";
			$era_kpl		= "";
		}
		elseif ($insok != "EI") {
			$sarjarow = mysql_fetch_array($sarjares);
			
			$sarjanumero_haku = $sarjanumero;
			
			echo "<font class='error'>".t("Sarjanumero lˆytyy jo tuotteelta")." $sarjarow[tuoteno]/$sarjanumero.</font><br><br>";
		}
	}

	// Ollaan valittu joku tunnus listasta ja halutaan liitt‰‰ se tilausriviin tai poistaa se tilausrivilt‰
	if ($from != '' and $rivitunnus != "" and $formista == "kylla") {
		
		$lisaysok = "OK";
		
		// Jos t‰m‰ on er‰seurantaa niin tehd‰‰n tsekit lis‰t‰‰n kaikki t‰m‰n er‰n sarjanumerot
		if (count($sarjataan) > 0 and ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F")) {
			$ktark = implode(",", $sarjataan);
			
			$query = "	SELECT sum(abs(era_kpl)) kpl 
						FROM sarjanumeroseuranta
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus in ($ktark)";
			$sarres = mysql_query($query) or pupe_error($query);
			$sarrow = mysql_fetch_array($sarres);
			
			if ($from == "KERAA" and $rivirow["varattu"] != $sarrow["kpl"]) {
				echo "<font class='error'>".t('Riviin voi liitt‰‰ vain')." $rivirow[varattu] $rivirow[yksikko].</font><br><br>";

				$lisaysok = "";
			}
			elseif ($rivirow["varattu"] < $sarrow["kpl"]) {
				echo "<font class='error'>".t('Riviin voi liitt‰‰ enint‰‰n')." $rivirow[varattu] $rivirow[yksikko].</font><br><br>";	
				
				$lisaysok = "";
			}
		}
		
		// Tutkitaan ettei liitet‰ sek‰ uusia ett‰ vanhoja sarjanumeroita samaan riviin
		if (count($sarjataan) > 0) {			
			$ktark = implode(",", $sarjataan);
						
			$query = "	SELECT distinct kaytetty 
						FROM sarjanumeroseuranta
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus in (".$ktark.")";						
			$sarres = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($sarres) > 1) {
				echo "<font class='error'>".t('Riviin ei voi liitt‰‰ sek‰ k‰ytettyj‰ ett‰ uusia sarjanumeroita')."</font><br><br>";	
				
				$lisaysok = "";
			}
		}
		
		// jos olemme ruksanneet v‰hemm‰n tai yht‰ paljon kuin tuotteita on rivill‰, voidaan p‰ivitt‰‰ muutokset
		if ($rivirow["varattu"] >= count($sarjataan) and $lisaysok == "OK") {
			foreach ($sarjat as $sarjatun) {				
				$query = "	SELECT tunnus, kaytetty, $tunnuskentta trivitunnus
							FROM sarjanumeroseuranta
							WHERE tunnus in ($sarjatun)";
				$sarres = mysql_query($query) or pupe_error($query);
				$sarrow = mysql_fetch_array($sarres);

				$query = "	UPDATE sarjanumeroseuranta
							set $tunnuskentta='',
							muuttaja	= '$kukarow[kuka]',
							muutospvm	= now()
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus in ($sarjatun)";
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
		elseif($rivirow["varattu"] < count($sarjataan)) {
			echo "<font class='error'>".sprintf(t('Riviin voi liitt‰‰ enint‰‰n %s sarjanumeroa'), abs($rivirow["varattu"])).". ".$rivirow["varattu"]." ".count($sarjataan)."</font><br><br>";
		}
		
		if ($rivirow["varattu"] >= count($sarjataan) and count($sarjataan) > 0 and $lisaysok == "OK") {
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
							SET $tunnuskentta='$rivitunnus',
							muuttaja	= '$kukarow[kuka]',
							muutospvm	= now()
							$paikkalisa
							WHERE yhtio='$kukarow[yhtio]'
							and tunnus='$sarjatun'";
				$sarjares = mysql_query($query) or pupe_error($query);

				// Tutkitaan oliko t‰m‰ sarjanumero k‰ytettytuote?
				$query = "	SELECT $tunnuskentta rivitunnus, kaytetty, ostorivitunnus
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
				
				if ($rivirow["sarjanumeroseuranta"] == "S" and $tunnuskentta == "myyntirivitunnus") {
					$query = "	SELECT nimitys
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$sarjarow[ostorivitunnus]'";
					$nimires = mysql_query($query) or pupe_error($query);
					$nimirow = mysql_fetch_array($nimires);
					
					if ($nimirow["nimitys"] != "") {
						$query = "	UPDATE tilausrivi
									SET nimitys = '$nimirow[nimitys]'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$sarjarow[rivitunnus]'";
						$nimires = mysql_query($query) or pupe_error($query);					
					}
				}

				//Tutkitaan lis‰varusteita
				if ($tunnuskentta == 'myyntirivitunnus' and $from != "riviosto" and $from != "kohdista") {
					//Hanskataan sarjanumerollisten tuotteiden lis‰varusteet
					if ($sarjatun > 0 and $rivitunnus > 0) {
						require("sarjanumeron_lisavarlisays.inc");

						$palautus = lisavarlisays($sarjatun, $rivitunnus);

						if ($palautus != "OK") {
							echo "<font class='error'>$palautus</font><br><br>";

							$query = "	UPDATE sarjanumeroseuranta
										SET $tunnuskentta='',
										muuttaja	= '$kukarow[kuka]',
										muutospvm	= now()
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

	
	// N‰ytet‰‰n koneella olevat sarjanumerot
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
		
		if ($myyntitilaus_haku == -1) {
			$lisa .= " and sarjanumeroseuranta.myyntirivitunnus = -1 ";
		}
		elseif (is_numeric($myyntitilaus_haku)) {
			if ($myyntitilaus_haku == 0) {
				$lisa .= " and (lasku_myynti.tunnus is null or lasku_myynti.tila = 'T') and sarjanumeroseuranta.myyntirivitunnus != -1 ";
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
	
	if ($tervetuloa_haku != "") {
		$lisa .= " and (lasku_osto.laatija='$kukarow[kuka]' or lasku_myynti.laatija='$kukarow[kuka]' or lasku_myynti.myyja='$kukarow[tunnus]')";
	}

	if ($nimitys_haku != "") {
		$lisa2 = " HAVING nimitys like '%$nimitys_haku%' ";
	}

	if ((($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi == "ON") or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA") and $hyvitysrivi != "ON")) {
		//Myyd‰‰n sarjanumeroita
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					tilausrivi_osto.tunnus								osto_rivitunnus,
					tilausrivi_osto.laskutettuaika						osto_laskaika,
					tilausrivi_myynti.laskutettuaika					myynti_laskaika,
					DATEDIFF(now(), tilausrivi_osto.laskutettuaika)		varpvm,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka,
					era_kpl
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
					ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
		$sarjaresiso = mysql_query($query) or pupe_error($query);
	}
	elseif((($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi != "ON") or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA") and $hyvitysrivi == "ON")) {
		// Ostetaan sarjanumeroita
		$query	= "	SELECT sarjanumeroseuranta.*,
					min(sarjanumeroseuranta.tunnus) tunnus,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys, if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					tilausrivi_osto.tunnus								osto_rivitunnus,
					tilausrivi_osto.laskutettuaika						osto_laskaika,
					tilausrivi_myynti.laskutettuaika					myynti_laskaika,
					DATEDIFF(now(), tilausrivi_osto.laskutettuaika)		varpvm,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka,
					era_kpl
					FROM sarjanumeroseuranta use index (yhtio_ostorivi)
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno					
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
					and sarjanumeroseuranta.ostorivitunnus in (0, $rivitunnus)
					$lisa";
					
		if ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "T") {
			$query	.= " GROUP BY sarjanumeroseuranta.ostorivitunnus, sarjanumeroseuranta.sarjanumero ";
		}
		else {
			$query	.= " GROUP BY sarjanumeroseuranta.tunnus ";
		}
		
		$query .= "	$lisa2
					ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
		$sarjaresiso = mysql_query($query) or pupe_error($query);
	}
	elseif($from == "INVENTOINTI") {
		// Inventoidaan
		$query	= "	SELECT sarjanumeroseuranta.*,
					min(sarjanumeroseuranta.tunnus) tunnus,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys, if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					tilausrivi_osto.tunnus								osto_rivitunnus,
					tilausrivi_osto.laskutettuaika						osto_laskaika,
					tilausrivi_myynti.laskutettuaika					myynti_laskaika,
					DATEDIFF(now(), tilausrivi_osto.laskutettuaika)		varpvm,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka,
					era_kpl
					FROM sarjanumeroseuranta use index (yhtio_ostorivi)
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno					
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
					and sarjanumeroseuranta.ostorivitunnus in (0, $rivitunnus)
					and sarjanumeroseuranta.myyntirivitunnus = 0
					$lisa
					GROUP BY sarjanumeroseuranta.ostorivitunnus, sarjanumeroseuranta.sarjanumero
					$lisa2
					ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
		$sarjaresiso = mysql_query($query) or pupe_error($query);
	}
	elseif ($lisa != "" or $lisa2 != "") {
		// Listataan
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					tilausrivi_osto.tunnus								osto_rivitunnus,
					tilausrivi_osto.laskutettuaika						osto_laskaika,
					tilausrivi_myynti.laskutettuaika					myynti_laskaika,
					DATEDIFF(now(), tilausrivi_osto.laskutettuaika)		varpvm,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka,
					era_kpl
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
					ORDER BY sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus
					LIMIT 100";
		$sarjaresiso = mysql_query($query) or pupe_error($query);
	}
	

	if ($rivirow["tuoteno"] != '') {
		echo "<table>";
		echo "<tr><th>".t("Tuotenumero")."</th><td>$rivirow[tuoteno] $rivirow[nimitys]</td></tr>";
		echo "<tr><th>".t("M‰‰r‰")."</th><td>$rivirow[varattu] $rivirow[yksikko]</td></tr>";
		echo "</table><br>";
	}
	
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Sarjanumero")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Varastopaikka")."</th>";
	echo "<th>".t("Ostotilaus")."</th>";
	echo "<th>".t("Myyntitilaus")."</th>";

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

	if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php") !== FALSE or $PHP_SELF == "sarjanumeroseuranta.php") {
		echo "<form name='haku' action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='$tunnuskentta' 	value = '$rivitunnus'>";
		echo "<input type='hidden' name='from' 				value = '$from'>";
		echo "<input type='hidden' name='lopetus' 			value = '$lopetus'>";
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
	}
	
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value='$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value='$from'>";
	echo "<input type='hidden' name='lopetus' 			value='$lopetus'>";
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
	
	$valitut_sarjat = array();

	if (is_resource($sarjaresiso) and mysql_num_rows($sarjaresiso) > 0) {
		while ($sarjarow = mysql_fetch_array($sarjaresiso)) {

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
			echo "<td valign='top'>".strtoupper($sarjarow["sarjanumero"])."<a name='$sarjarow[sarjanumero]'></a>";
		
			if ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F") {			
				if ($sarjarow["era_kpl"] < 0) {
					echo "<br>".t("Er‰ss‰").": ".abs($sarjarow["era_kpl"])." $rivirow[yksikko]<br><font class='error'>".t("Er‰ on jo myyty")."!</font>";
				}
				else {
					echo "<br>".t("Er‰ss‰").": $sarjarow[era_kpl] $rivirow[yksikko]";
				}		
			}
		
			echo "</td>";
			echo "<td colspan='2' valign='top'>$sarjarow[tuoteno]<br>$sarjarow[nimitys]";
		
			if ($sarjarow["takuu_alku"] != '' and $sarjarow["takuu_alku"] != '0000-00-00') {
				echo "<br>".t("Takuu").": ".tv1dateconv($sarjarow["takuu_alku"])." - ".tv1dateconv($sarjarow["takuu_loppu"]);
			}

			if ($rivirow["sarjanumeroseuranta"] != "E" and $rivirow["sarjanumeroseuranta"] != "F" and ($sarjarow["myynti_laskaika"] == "0000-00-00" or $sarjarow["myynti_laskaika"] == "")) {		
				echo "<br>".t("Varastointiaika").": ".$sarjarow["varpvm"]." ".t("pva").". (".tv1dateconv($sarjarow["osto_laskaika"]).")";
			}
		
			echo "</td>";
			echo "<td valign='top'>$sarjarow[varastonimi]<br>$sarjarow[tuotepaikka]</td>";

			if ($sarjarow["ostorivitunnus"] == 0) {
				$sarjarow["ostorivitunnus"] = "";
			}
			if ($sarjarow["myyntirivitunnus"] == 0) {
				$sarjarow["myyntirivitunnus"] = "";
			}

			echo "<td colspan='2' valign='top'><a href='../raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$sarjarow[osto_tunnus]'>$sarjarow[osto_tunnus] $sarjarow[osto_nimi]</a><br>";

			if (($sarjarow["siirtorivitunnus"] > 0 and $tunnuskentta!= 'siirtorivitunnus') or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"]!=$sarjarow["osto_rivitunnus"])) {
				$fnlina1 = "<font class='message'>(Varattu lis‰varusteena tuotteelle: ";
				$fnlina2 = ")</font>";
			
				if ($sarjarow["osto_perheid2"] > 0) {
					$ztun = $sarjarow["osto_perheid2"];
				}
				else {
					$ztun = $sarjarow["siirtorivitunnus"];		
				}
			
				$query = "	SELECT tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero 
							FROM tilausrivi 
							LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
							WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
				$siires = mysql_query($query) or pupe_error($query);
				$siirow = mysql_fetch_array($siires);
			
				$sarjarow["myynti_tunnus"] = 0;
				$sarjarow["myynti_nimi"] = $siirow["tuoteno"]." ".$siirow["sarjanumero"];
		
			}
			elseif ($sarjarow["myynti_tila"] == 'T') {
				$fnlina1 = "<font class='message'>(".t("Tarjous").": ";
				$fnlina2 = ")</font>";
			}
			else {
				$fnlina1 = "";
				$fnlina2 = "";
			}
		
			if ($sarjarow["myyntirivitunnus"] == -1) {
				$sarjarow["myynti_nimi"] = t("Inventointi");
			}
		
			if ($sarjarow["myynti_tunnus"] > 0) {
				echo "<a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]'>$fnlina1 $sarjarow[myynti_tunnus] $sarjarow[myynti_nimi] $fnlina2</a></td>";
			}
			else {
				echo "$fnlina1 $sarjarow[myynti_nimi] $fnlina2</td>";
			}

			if (($sarjarow[$tunnuskentta] == 0 or $sarjarow["myynti_tila"] == 'T' or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
				$chk = "";
				if ($sarjarow[$tunnuskentta] == $rivitunnus) {
					$chk = "CHECKED";
				
					// T‰t‰ voidaan tarvita myˆhemmin
					$valitut_sarjat[] = $sarjarow["tunnus"];
				}

				if ($tunnuskentta == "ostorivitunnus" and $sarjarow["kpl"] != 0) {
					echo "<td valign='top'>".t("Lukittu")."</td>";
				}
				elseif ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA" or $from == "riviosto" or $from == "kohdista" or $from == "INVENTOINTI") {
					if (($from != "SIIRTOTYOMAARAYS" and $laskurow["tila"] != "G" and $from != "SIIRTOLISTA" and $sarjarow["siirtorivitunnus"] > 0) or (($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi != "ON" and $sarjarow["osto_laskaika"] > '0000-00-00' and ($sarjarow["siirtorivitunnus"] > 0 or $sarjarow["myyntirivitunnus"] > 0))) {
						$dis = "DISABLED";
					}
					else {
						$dis = "";
					}
				
					echo "<input type='hidden' name='sarjat[]' value='$sarjarow[tunnus]'>";
					echo "<td valign='top'><input type='checkbox' name='sarjataan[]' value='$sarjarow[tunnus]' $chk onclick='submit();' $dis></td>";
				}
				else {
					echo "<td valign='top'></td>";	
				}
			}
		
			echo "<td valign='top' nowrap>";

			//jos saa muuttaa niin n‰ytet‰‰n muokkaa linkki
			if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php") !== FALSE or $PHP_SELF == "sarjanumeroseuranta.php") {
				echo "<a href='$PHP_SELF?toiminto=MUOKKAA&$tunnuskentta=$rivitunnus&from=$from&aputoim=$aputoim&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&varasto_haku=$varasto_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku&muut_siirrettavat=$muut_siirrettavat'>".t("Muokkaa")."</a>";
			}
		
			if ($sarjarow['ostorivitunnus'] > 0 and $from == "") {
				if ($keikkarow["tunnus"] > 0) {
					$keikkalisa = "&otunnus=$keikkarow[tunnus]";
				}
				else {
					$keikkalisa = "&luouusikeikka=OK&liitostunnus=$sarjarow[tunnus]";
				}

				echo "<br><a href='$PHP_SELF?toiminto=kululaskut$keikkalisa'>".t("Liit‰ kululasku")."</a>";
			}

			if ($sarjanumeronLisatiedot == "OK") {
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
						
				echo "<br><a href='".$palvelin2."yllapito.php?toim=sarjanumeron_lisatiedot$ylisa&lopetus=$PHP_SELF////$tunnuskentta=$rivitunnus//from=$from//aputoim=$aputoim//otunnus=$otunnus//sarjanumero_haku=$sarjanumero_haku//tuoteno_haku=$tuoteno_haku//nimitys_haku=$nimitys_haku//varasto_haku=$varasto_haku//ostotilaus_haku=$ostotilaus_haku//myyntitilaus_haku=$myyntitilaus_haku//lisatieto_haku=$lisatieto_haku//muut_siirrettavat=$muut_siirrettavat'>".t("Lis‰tiedot")."</a>";			
				echo "<br><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$sarjarow[tunnus]')\"><u>".t("Lis‰tietoikkuna")."</u></a>";
			}

			if ($sarjarow['ostorivitunnus'] == 0 and $sarjarow['myyntirivitunnus'] == 0 and $keikkarow["tunnus"] == 0 and $sarjarow["era_kpl"] >= 0) {
				echo "<br><a href='$PHP_SELF?toiminto=POISTA&$tunnuskentta=$rivitunnus&from=$from&aputoim=$aputoim&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&varasto_haku=$varasto_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku&muut_siirrettavat=$muut_siirrettavat' onclick=\"return verify()\">".t("Poista")."</a>";
			}

			echo "</tr>";
		}
	}
	echo "</form>";
	echo "</table>";

	if ($toiminto== '') {
		$sarjanumero 	= '';
		$lisatieto 		= '';
		$chk 			= '';
	}
	
	//Kursorinohjaus
	if($rivirow["sarjanumeroseuranta"] == "T") {
		$formi	= "sarjaformi";
		$kentta = "sarjanumero";	
	}
	else {
		$formi	= "haku";
		$kentta = "sarjanumero_haku";
	}
	
	if ($rivirow["tuoteno"] != '') {
		echo "	<form name='sarjaformi' action='$PHP_SELF' method='post'>
				<input type='hidden' name='$tunnuskentta' 		value='$rivitunnus'>
				<input type='hidden' name='from' 				value='$from'>
				<input type='hidden' name='lopetus' 			value='$lopetus'>
				<input type='hidden' name='aputoim' 			value='$aputoim'>
				<input type='hidden' name='otunnus' 			value='$otunnus'>
				<input type='hidden' name='muut_siirrettavat'	value='$muut_siirrettavat'>
				<input type='hidden' name='toiminto' 			value='LISAA'>
				<input type='hidden' name='valitut_sarjat' 		value='".implode(",", $valitut_sarjat)."'>";

		if ($rivirow["tuoteno"] != '' and ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F")) {
			$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
						FROM sarjanumeroseuranta
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$rivirow[tuoteno]'
						and sarjanumero like '".t("Er‰")."-%'";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vresult);

			if ($vrow["sarjanumero"] > 0) {
				$nxt = t("Er‰")."-".$vrow["sarjanumero"];	
			}
			else {
				$nxt = t("Er‰")."-1";		
			}

			echo "<br><table>";
			echo "<tr><th colspan='2'>".t("Lis‰‰ uusi er‰numero")."</th></tr>";
			echo "<tr><th>".t("Er‰numero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td><td class='back'><a href='#' onclick='document.sarjaformi.sarjanumero.value=\"$nxt\";'>".t("Seuraava er‰")."</a></td></tr>";
			
			echo "<tr><th>".t("Er‰n suuruus")."</th><td><input type='text' size='30' name='era_kpl' value='$era_kpl'></td></tr>";
			
			
			if ($rivirow["sarjanumeroseuranta"] == "F") {
				echo "<tr><th>".t("Parasta ennen")."</th><td>
				<input type='text' name='peppa' value='' size='3'>
				<input type='text' name='pekka' value='' size='3'>
				<input type='text' name='pevva' value='' size='5'></td>";			
			}
			
			echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$lisatieto</textarea></td></tr>";
		}
		elseif($rivirow["sarjanumeroseuranta"] == "T") {
			echo "<br><table>";
			echo "<tr><th colspan='2'>".t("Lis‰‰ uusi sarjanumero")."</th></tr>";
			echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td></tr>";
		}
		else {
			$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
						FROM sarjanumeroseuranta
						WHERE yhtio='$kukarow[yhtio]'
						and tuoteno = '$rivirow[tuoteno]'
						and sarjanumero like '".t("PUUTTUU")."-%'";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vresult);
            
			if ($vrow["sarjanumero"] > 0) {
				$nxt = t("PUUTTUU")."-".$vrow["sarjanumero"];	
			}
			else {
				$nxt = t("PUUTTUU")."-1";		
			}
			
			$query = "	SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
						FROM sarjanumeroseuranta
						WHERE yhtio='$kukarow[yhtio]'
						and tuoteno = '$rivirow[tuoteno]'
						and sarjanumero like '".t("EI SARJANUMEROA")."-%'";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vresult);
            
			if ($vrow["sarjanumero"] > 0) {
				$nxt2 = t("EI SARJANUMEROA")."-".$vrow["sarjanumero"];	
			}
			else {
				$nxt2 = t("EI SARJANUMEROA")."-1";		
			}

			echo "<br><table>";
			echo "<tr><th colspan='2'>".t("Lis‰‰ uusi sarjanumero")."</th></tr>";
			echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td><td class='back'><a onclick='document.sarjaformi.sarjanumero.value=\"$nxt\";'><u>".t("Sarjanumero ei tiedossa")."</u></a> <a onclick='document.sarjaformi.sarjanumero.value=\"$nxt2\";'><u>".t("Ei Sarjanumeroa")."</u></a></td></tr>";
			echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$lisatieto</textarea></td></tr>";

			$chk = "";
			if ($kaytetty == "K") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("K‰ytetty")."</th><td><input type='checkbox' name='kaytetty' value='K'></td></tr>";
		
			echo "<tr><th>".t("Takuu")."</th><td>
			<input type='text' name='tppa' value='' size='3'>
			<input type='text' name='tkka' value='' size='3'>
			<input type='text' name='tvva' value='' size='5'>
			- 
			<input type='text' name='tppl' value='' size='3'>
			<input type='text' name='tkkl' value='' size='3'>
			<input type='text' name='tvvl' value='' size='5'></td>";
		}
		
		echo "<td class='back'><input type='submit' value='".t("Lis‰‰")."'></td>";
		echo "</form>";
		echo "</tr></table>";
	}

	echo "<br>";

	if ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS") {
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
	
	if ($from == "KORJAA") {
		
		$lopetus = str_replace('//','&',  $lopetus);
		
		echo "<form method='post' action='../raportit/sarjanumerotarkistukset.php?$lopetus'>
			<input type='hidden' name='toim' value='$aputoim'>
			<input type='hidden' name='id'   value='$otunnus'>
			<input type='submit' value='".t("Takaisin laitemyyntien tarkistukseen")."'>
			</form>";
	}
	
	if ($from == "INVENTOINTI") {
		
		$lopetus = str_replace('//','&',  $lopetus);
		
		echo "<form method='post' action='".$palvelin2."inventoi.php?$lopetus'>
			<input type='hidden' name='toim' value='$aputoim'>
			<input type='hidden' name='id'   value='$otunnus'>
			<input type='submit' value='".t("Takaisin inventointiin")."'>
			</form>";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php")  !== FALSE) {
		require ("../inc/footer.inc");
	}

?>
