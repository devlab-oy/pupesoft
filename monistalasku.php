<?php

if($vain_monista == ""){
	require('inc/parametrit.inc');

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("raportit/naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "ETSILASKU";
	}	
	
	if ($toim == 'SOPIMUS') {
		echo "<font class='head'>".t("Monista sopimus")."</font><hr>";
	}
	elseif ($toim == 'TARJOUS') {
		echo "<font class='head'>".t("Monista tarjous")."</font><hr>";
	}
	elseif ($toim == 'TYOMAARAYS') {
		echo "<font class='head'>".t("Monista tyˆm‰‰r‰ys")."</font><hr>";
	}
	elseif ($toim == 'TILAUS') {
		echo "<font class='head'>".t("Monista tilaus")."</font><hr>";
	}
	else {
		echo "<font class='head'>".t("Monista lasku")."</font><hr>";
	}
}

if ($toim == 'TYOMAARAYS') {
	// Halutaanko saldot koko konsernista?
	$query = "	SELECT *
				FROM yhtio
				WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		$yhtiot = array();

		while ($row = mysql_fetch_array($result)) {
			$yhtiot[] = $row["yhtio"];
		}
	}
	else {
		$yhtiot = array();
		$yhtiot[] = $kukarow["yhtio"];
	}
}
else {
	$yhtiot = array();
	$yhtiot[] = $kukarow["yhtio"];	
}

if ($tee == '') {
	if ($ytunnus != '') {
		require ("inc/asiakashaku.inc");
	}
	
	if ($ytunnus != '') {
		$tee = "ETSILASKU";
	}
	else {
		$tee = "";
	}

	if ($laskunro > 0) {
		$tee = "ETSILASKU";
	}

	if ($otunnus > 0) {
		$tee = 'ETSILASKU';
	}
}

if ($tee == "mikrotila" or $tee == "file") {
	require ('tilauskasittely/mikrotilaus_monistalasku.inc');
}

if ($tee == "ETSILASKU") {	
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));


	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");
	
	if ($toim != 'SOPIMUS') {
		echo "<form method='post' action='$PHP_SELF' autocomplete='off'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='asiakasid' value='$asiakasid'>
				<input type='hidden' name='tunnukset' value='$tunnukset'>
				<input type='hidden' name='tee' value='ETSILASKU'>";
		
		echo "<table>";

		echo "<tr><th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table><br>";
	}
	
	if ($tunnukset != '') {
		$where 	= " tila = 'U' and lasku.tunnus in ($tunnukset) ";
		$use 	= " ";
	}
	elseif ($laskunro > 0) {
		$where 	= " tila = 'U' and laskunro = '$laskunro' ";
		$use 	= " use index (lasno_index) ";
	}
	elseif ($otunnus > 0) {
		//katotaan lˆytyykˆ lasku ja sen kaikki tilaukset
		$query = "  SELECT laskunro
					FROM lasku
					WHERE tunnus = '$otunnus' 
					and yhtio = '$kukarow[yhtio]'";
		$laresult = mysql_query($query) or pupe_error($query);
		$larow = mysql_fetch_array($laresult);

		if ($toim == 'SOPIMUS') {
			$where 	= " tila = '0' and tunnus = '$otunnus' ";
			$use 	= " ";
			
		}
		elseif ($toim == 'TARJOUS') {
			$where 	= " tila = 'T' and tunnus = '$otunnus' ";
			$use 	= " ";	
		}
		elseif ($toim == 'TYOMAARAYS') {
			$where 	= " tila in ('N','L','A') and tunnus = '$otunnus' ";
			$use 	= " ";
		}
		elseif ($toim == 'TILAUS') {
			$where 	= " tila in ('N','L') and tunnus = '$otunnus' ";
			$use 	= " ";
		}
		else {
			if ($larow["laskunro"] > 0) {
				$where 	= " tila = 'U' and laskunro = '$larow[laskunro]' ";
				$use 	= " use index (lasno_index) ";
			}
			else {
				$where 	= " tila = 'U' and tunnus = '$otunnus' ";
				$use 	= " ";
			}
		}
	}
	else {
		if ($toim == 'SOPIMUS') {
			$where = "	tila = '0'
						and lasku.liitostunnus = '$asiakasid' ";
			$use 	= " ";		
		}
		elseif ($toim == 'TARJOUS') {
			$where = "	tila = 'T'
						and lasku.liitostunnus = '$asiakasid' ";
			$use 	= " ";
		}
		elseif ($toim == 'TYOMAARAYS') {
			$where 	= " tila in ('N','L','A') 
						and lasku.liitostunnus = '$asiakasid' ";
			$use 	= " ";
		}
		elseif ($toim == 'TILAUS') {
			$where 	= " tila in ('N','L') 
						and lasku.liitostunnus = '$asiakasid' ";
			$use 	= " ";
		}
		else {
			$where = "	tila = 'U'
						and lasku.liitostunnus = '$asiakasid'
						and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";
			$use 	= " use index (yhtio_tila_liitostunnus_tapvm) ";	
		}
	}

	// Etsit‰‰n muutettavaa tilausta
	$query = "	SELECT yhtio, tunnus 'tilaus', laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, summa, tapvm, laatija, tila, alatila
				FROM lasku $use
				WHERE $where and lasku.yhtio in ('".implode("','", $yhtiot)."')
				ORDER BY tapvm, lasku.tunnus desc LIMIT 100";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table border='0' cellpadding='2' cellspacing='1'>";
		echo "<tr>";

		for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
			$jarj = $i+1;
			echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
		}

		echo "<th>".t("Tyyppi")."</th>";

		echo "<th>".t("Monista")."</th>";
		
		if ($toim == '') {
			echo "<th>".t("Hyvit‰")."</th>";
			echo "<th>".t("Korjaa alvit")."</th>";
			echo "<th>".t("Suoraan laskutukseen")."</th>";
		}
		
		echo "<th>".t("N‰yt‰")."</th></tr>";
		
		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>
				<input type='hidden' name='kklkm' value='1'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='MONISTA'>";

		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";
			$ero="td";

			if ($tunnus==$row['tilaus']) $ero="th";

			echo "<tr class='aktiivi'>";
			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
				if(mysql_field_name($result,$i) == 'tapvm')
					echo "<$ero>".tv1dateconv($row["$i"])."</$ero>";
				else {
					echo "<$ero>$row[$i]</$ero>";
				}

			}

			$laskutyyppi = $row["tila"];
			$alatila	 = $row["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require ("inc/laskutyyppi.inc");

			echo "<$ero>".t($laskutyyppi)." ".t($alatila)."</$ero>";

			$sel = "";
			if ($monistettavat[$row["tilaus"]] == 'MONISTA') {
				$sel = "CHECKED";
			}
			if ($toim == 'TILAUS') {
				echo "<$ero><input type='checkbox' name='monistettavat[$row[tilaus]]' value='MONISTA' $sel></$ero>";
			}
			else {
				echo "<$ero><input type='radio' name='monistettavat[$row[tilaus]]' value='MONISTA' $sel></$ero>";
			}

			if ($toim == '') {
				$sel = "";
				if ($monistettavat[$row["tilaus"]] == 'HYVITA') {
					$sel = "CHECKED";
				}
				echo "<$ero><input type='radio' name='monistettavat[$row[tilaus]]' value='HYVITA' $sel></$ero>";

				$sel = "";
				if ($korjaaalvit[$row["tilaus"]] != '') {
					$sel = "CHECKED";
				}
				echo "<$ero><input type='checkbox' name='korjaaalvit[$row[tilaus]]' value='on' $sel></$ero>";

				// Katotaan ettei yksik‰‰n tuote ole sarjanumeroseurannassa, silloin ei voida turvallisesti laittaa suoraan laskutukseen
				$query = "	SELECT tuote.sarjanumeroseuranta
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno and tuote.sarjanumeroseuranta!=''
							WHERE tilausrivi.yhtio='$row[yhtio]'
							and tilausrivi.uusiotunnus='$row[tilaus]'";
				$res = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($res) == 0) {
					$sel = "";
					if ($suoraanlasku[$row["tilaus"]] != '') {
						$sel = "CHECKED";
					}
					echo "<$ero><input type='checkbox' name='suoraanlasku[$row[tilaus]]' value='on' $sel></$ero>";
				}
				else {
					echo "<$ero></$ero>";
				}
			}
			
			echo "<$ero><a href='$PHP_SELF?tunnus=$row[tilaus]&tunnukset=$tunnukset&asiakasid=$asiakasid&otunnus=$otunnus&laskunro=$laskunro&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&tee=NAYTATILAUS&toim=$toim'>".t("N‰yt‰")."</a></$ero>";
			echo "</tr>";
		}

		echo "</table><br>";
		echo "<input type='submit' value='".t("Monista")."'></form>";
	}
	else {
		echo t("Ei tilauksia")."...<br><br>";
	}
}

if ($tee == 'MONISTA') {

	// $tunnus joka on array joss on monistettavat laskut
	// $kklkm kopioiden m‰‰r‰
	// Jos hyvit‰ on 'on', niin silloin $kklkm t‰ytyy aina olla 1
	// $korjaaalvit array kertoo korjataanko kopioitavat tilauksen alvit
	// $suoraanlasku array sanoo ett‰ tilausta ei ker‰t‰ vaan se menee suoraan laskutusjonoon
	
	// Otetaan uudet tunnukset talteen
	$tulos_ulos = array();	
	
	foreach($monistettavat as $lasku => $kumpi) {

		$alvik 		= "";
		$slask 		= "";

		if ($korjaaalvit[$lasku] != '')  $alvik = "on";
		if ($suoraanlasku[$lasku] != '') $slask = "on";

		if ($kumpi == 'HYVITA') {
				$kklkm = 1;
				echo t("Hyvitet‰‰n")." ";
		}
		else {
				echo t("Kopioidaan")." ";
		}
		
		if ($toim == 'SOPIMUS') {
			echo "$kklkm ".t("sopimus(ta)").".<br><br>";
		}
		elseif ($toim == 'TARJOUS') {
			echo "$kklkm ".t("tarjous(ta)").".<br><br>";
		}
		elseif ($toim == 'TYOMAARAYS') {
			echo "$kklkm ".t("tyˆm‰‰r‰ys(t‰)").".<br><br>";
		}
		elseif ($toim == 'TILAUS') {
			echo "$kklkm ".t("tilaus(ta)").".<br><br>";
		}
		else {
			echo "$kklkm ".t("lasku(a)").".<br><br>";
		}

		for($monta=1; $monta <= $kklkm; $monta++) {

			$query = "	SELECT * 
						FROM lasku 
						WHERE tunnus = '$lasku' 
						and yhtio in ('".implode("','", $yhtiot)."')";
			$monistares = mysql_query($query) or pupe_error($query);
			$monistarow = mysql_fetch_array($monistares);

			$fields = "yhtio";
			$values = "'$kukarow[yhtio]'";
			
			// Ei monisteta tunnusta
			for($i=1; $i < mysql_num_fields($monistares)-1; $i++) {

				$fields .= ", ".mysql_field_name($monistares,$i);

				switch (mysql_field_name($monistares,$i)) {
					case 'ytunnus':
					case 'liitostunnus':
					case 'nimi':
					case 'nimitark':
					case 'osoite':
					case 'postino':
					case 'postitp':
					case 'toim_nimi':
					case 'toim_nimitark':
					case 'toim_osoite':
					case 'toim_postino':
					case 'toim_postitp':
					case 'yhtio_nimi':	   
					case 'yhtio_osoite':	   
					case 'yhtio_postino':   
					case 'yhtio_postitp':	   
					case 'yhtio_maa':		   
					case 'yhtio_ovttunnus':
					case 'yhtio_kotipaikka': 
					case 'yhtio_toimipaikka':
					case 'verkkotunnus':
					case 'maksuehto':
					case 'myyja':	
					case 'kassalipas':
					case 'ovttunnus':
					case 'toim_ovttunnus':
					case 'maa':
					case 'toim_maa':
						if ($kukarow["yhtio"] != $monistarow["yhtio"]) {
							$values .= ", ''";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
					break;
					case 'kerayspvm':
					case 'toimaika':
					case 'luontiaika':
						$values .= ", now()";
						break;
					case 'alatila':
						if ($toim == 'SOPIMUS') {
							$values .= ", 'V'";
						}
						else {
							$values .= ", ''";							
						}
						break;
					case 'tila':
						if ($toim == 'SOPIMUS') {
							$values .= ", '0'";
						}
						elseif ($toim == 'TARJOUS') {
							$values .= ", 'T'";
						}
						elseif ($toim == 'TYOMAARAYS') {
							$values .= ", 'A'";
						}
						else {
							$values .= ", 'N'";
						}
						break;
					case 'tilaustyyppi':
						if ($toim == 'TYOMAARAYS') {
							$values .= ", 'A'";
							break;
						}
					case 'tunnus':
					case 'tapvm':
					case 'kapvm':
					case 'erpcm':					
					case 'suoraveloitus':
					case 'olmapvm':
					case 'summa':
					case 'summa_valuutassa':
					case 'kasumma':
					case 'kasumma_valuutassa':
					case 'hinta':
					case 'kate':
					case 'arvo':
					case 'arvo_valuutassa':	
					case 'saldo_maksettu':	
					case 'saldo_maksettu_valuutassa':	
					case 'pyoristys':				
					case 'pyoristys_valuutassa':
					case 'maksaja':
					case 'lahetepvm':					
					case 'lahetepvm':
					case 'laskuttaja':
					case 'laskutettu':
					case 'viite':
					case 'laskunro':
					case 'mapvm':
					case 'tilausvahvistus':
					case 'viikorkoeur':
					case 'tullausnumero':
					case 'kerayslista':
					case 'viikorkoeur':					
					case 'noutaja':
					case 'jaksotettu':
					case 'factoringsiirtonumero':					
					case 'vanhatunnus':
					case 'tunnusnippu':										
					case 'laskutuspvm':										
					case 'maksuaika':					
					case 'maa_maara':
					case 'kuljetusmuoto':
					case 'kauppatapahtuman_luonne':
					case 'sisamaan_kuljetus':
					case 'sisamaan_kuljetusmuoto':
					case 'poistumistoimipaikka':
					case 'poistumistoimipaikka_koodi':
						$values .= ", ''";
						break;
					case 'laatija':
						$values .= ", '$kukarow[kuka]'";
						break;
					case 'eilahetetta':
						if ($slask == 'on') {
							echo t("Tilaus laitetaan suoraan laskutusjonoon")."<br>";
							$values .= ", 'o'";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					case 'alv':
						//Korjataanko laskun alvit
						if ($alvik == "on") {
							$squery = "	SELECT *
										FROM asiakas
										WHERE yhtio='$monistarow[yhtio]' and tunnus = '$monistarow[liitostunnus]'";
							$asiakres = mysql_query($squery) or pupe_error($squery);
							$asiakrow = mysql_fetch_array($asiakres);

							$values .= ", '$asiakrow[alv]'";

							$laskurow["vienti"]	 = $monistarow["vienti"];
							$laskurow["ytunnus"] = $monistarow["ytunnus"];
							$laskurow["tila"]	 = $monistarow["tila"];
							$laskurow["alv"] 	 = $asiakrow["alv"];

							echo t("Korjataan laskun ALVia").":  $monistarow[alv] --> $asiakrow[alv]<br>";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					case 'ketjutus':
						if ($kumpi == 'HYVITA' or $alvik == "on") {
							echo t("Hyvityst‰/ALV-korjausta ei ketjuteta")."<br>";
							$values .= ", 'x'";
						}
						else {
							$values .= ", '".$monistarow[$i]."'";
						}
						break;
					case 'viesti':
						if ($kumpi == 'HYVITA' and $alvik == "on") {
							$values .= ", '".t("Hyvitet‰‰n ja tehd‰‰n ALV-korjaus laskuun").": ".$monistarow["laskunro"].".'";
						}
						elseif($kumpi == 'MONISTA' and $alvik == "on") {
							$values .= ", '".t("ALV-korjaus laskuun").": ".$monistarow["laskunro"].".'";
						}
						elseif ($kumpi == 'HYVITA') {
							$values .= ", '".t("Hyvitys laskuun").": ".$monistarow["laskunro"].".'";
						}
						else {
							$values .= ", ''";
						}
						break;
					case 'vienti_kurssi';
						// hyvityksiss‰ pidet‰‰n kurssi samana
						if ($kumpi == 'HYVITA') {
							if ($monistarow[$i] == 0) {
								// Vanhoilla u-laskuilla ei ole vienti kurssia....
								$vienti_kurssi = round($monistarow["arvo"]/$monistarow["arvo_valuutassa"], 6);
								
								$values .= ", '$vienti_kurssi'";
							}
							else {
								$values .= ", '".$monistarow[$i]."'";
							}																				
						}
						else {
							$vquery = "	SELECT kurssi
										FROM valuu
										WHERE yhtio = '$monistarow[yhtio]'
										and nimi	= '$monistarow[valkoodi]'";
							$vresult = mysql_query($vquery) or pupe_error($vquery);
							$valrow = mysql_fetch_array($vresult);
							$values .= ", '$valrow[kurssi]'";
						}
						break; 
					default:
						$values .= ", '".$monistarow[$i]."'";
				}
			}

			$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
			$insres  = mysql_query($kysely) or pupe_error($kysely);
			$utunnus = mysql_insert_id();
			
			$tulos_ulos[] = $utunnus;
			
			if ($toim == 'SOPIMUS') {
				echo t("Uusi sopimusnumero on")." $utunnus<br><br>";
			}
			else {
				echo t("Uusi tilausnumero on")." $utunnus<br><br>";
			}
			
			//Kopioidaan otsikon lisatiedot
			$query = "	SELECT * 
						FROM laskun_lisatiedot 
						WHERE otunnus='$lasku' and yhtio ='$monistarow[yhtio]'";
			$monistalisres = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($monistalisres) > 0) {
				$monistalisrow = mysql_fetch_array($monistalisres);

				$fields = "yhtio";
				$values = "'$kukarow[yhtio]'";

				// Ei monisteta tunnusta
				for($i=1; $i < mysql_num_fields($monistalisres)-1; $i++) { 

					$fields .= ", ".mysql_field_name($monistalisres,$i);

					switch (mysql_field_name($monistalisres,$i)) {
						case 'otunnus':
							$values .= ", '$utunnus'";
							break;
						default:
							$values .= ", '".$monistalisrow[$i]."'";
					}
				}

				$kysely  = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
				$insres2 = mysql_query($kysely) or pupe_error($kysely);
			}
			
			if ($toim == 'TYOMAARAYS') {
				//Kopioidaan otsikon tyˆm‰‰r‰ystiedot
				$query = "	SELECT * 
							FROM tyomaarays 
							WHERE otunnus	= '$lasku' 
							and yhtio 		= '$monistarow[yhtio]'";
				$monistalisres = mysql_query($query) or pupe_error($query);
				$monistalisrow = mysql_fetch_array($monistalisres);

				$fields = "yhtio";
				$values = "'$kukarow[yhtio]'";

				for($i=1; $i < mysql_num_fields($monistalisres); $i++) { 

					$fields .= ", ".mysql_field_name($monistalisres,$i);

					switch (mysql_field_name($monistalisres,$i)) {
						case 'otunnus':
							$values .= ", '$utunnus'";
							break;
						default:
							$values .= ", '".$monistalisrow[$i]."'";
					}
				}

				$kysely  = "INSERT into tyomaarays ($fields) VALUES ($values)";
				$insres2 = mysql_query($kysely) or pupe_error($kysely);			
			}
			
			if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS') {
				$query = "SELECT * from tilausrivi where otunnus='$lasku' and yhtio ='$monistarow[yhtio]' ORDER BY tunnus";
			}
			else {
				$query = "SELECT * from tilausrivi where uusiotunnus='$lasku' and kpl<>0 and tyyppi = 'L' and yhtio ='$monistarow[yhtio]' ORDER BY tunnus";
			}
			$rivires = mysql_query($query) or pupe_error($query);

			while ($rivirow = mysql_fetch_array($rivires)) {
				$paikkavaihtu = 0;
				$uusikpl = 0;

				$pquery = "	SELECT tunnus
							FROM tuotepaikat
							WHERE yhtio =	'$monistarow[yhtio]'
							and tuoteno =	'$rivirow[tuoteno]'
							and hyllyalue =	'$rivirow[hyllyalue]'
							and hyllynro =	'$rivirow[hyllynro]'
							and hyllyvali =	'$rivirow[hyllyvali]'
							and hyllytaso =	'$rivirow[hyllytaso]'
							LIMIT 1";
				$presult = mysql_query($pquery) or pupe_error($pquery);

				if (mysql_num_rows($presult) == 0) {
					$p2query = "SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
								FROM tuotepaikat
								WHERE yhtio = '$monistarow[yhtio]'
								and tuoteno = '$rivirow[tuoteno]'
								and oletus != ''
								LIMIT 1";
					$p2result = mysql_query($p2query) or pupe_error($p2query);

					if (mysql_num_rows($p2result) == 1) {
						$paikka2row = mysql_fetch_array($p2result);
						$paikkavaihtu = 1;
					}
				}

				$rfields = "yhtio";
				$rvalues = "'$kukarow[yhtio]'";

				for($i=1; $i < mysql_num_fields($rivires)-1; $i++) { // Ei tunnusta

					$rfields .= ", ".mysql_field_name($rivires,$i);

					switch (mysql_field_name($rivires,$i)) {
						case 'kerayspvm':
						case 'toimaika':
						case 'laadittu':
							$rvalues .= ", now()";
							break;
						case 'tunnus':
						case 'laskutettu':
						case 'laskutettuaika':
						case 'toimitettu':
						case 'toimitettuaika':
						case 'keratty':
						case 'kerattyaika':
						case 'kpl':
						case 'rivihinta':
						case 'rivihinta_valuutassa':
						case 'kate':
						case 'uusiotunnus':
						case 'jaksotettu':
							$rvalues .= ", ''";
							break;						
						case 'kommentti':
							if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS') {
								$rvalues .= ", '$rivirow[kommentti]'";
							}
							else {
								$rvalues .= ", ''";
							}
							break;
						case 'otunnus':
							$rvalues .= ", '$utunnus'";
							break;
						case 'laatija':
							$rvalues .= ", '$kukarow[kuka]'";
							break;
						case 'varattu':
							if ($kumpi == 'HYVITA') {
								$rvalues .= ", $rivirow[kpl] * -1";
								$uusikpl = $rivirow["kpl"] * -1;
							}
							else {
								if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS') {
									$rvalues .= ", '$rivirow[varattu]'";
									$uusikpl = $rivirow["varattu"];
								}
								else {
									$rvalues .= ", '$rivirow[kpl]'";
									$uusikpl = $rivirow["kpl"];	
								}
							}
							break;
						case 'tilkpl':
							if ($kumpi == 'HYVITA') {
								$rvalues .= ", $rivirow[kpl] * -1";
							}
							else {
								if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS') {
									$rvalues .= ", '$rivirow[tilkpl]'";
								}
								else {
									$rvalues .= ", '$rivirow[kpl]'";
								}
							}
							break;
						case 'hyllyalue':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '$paikka2row[hyllyalue]'";
							}
							else {
								$rvalues .= ", '$rivirow[hyllyalue]'";
							}
							break;
						case 'hyllynro':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '$paikka2row[hyllynro]'";
							}
							else {
								$rvalues .= ", '$rivirow[hyllynro]'";
							}
							break;
						case 'hyllyvali':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '$paikka2row[hyllyvali]'";
							}
							else {
								$rvalues .= ", '$rivirow[hyllyvali]'";
							}
							break;
						case 'hyllytaso':
							if ($paikkavaihtu == 1) {
								$rvalues .= ", '$paikka2row[hyllytaso]'";
							}
							else {
								$rvalues .= ", '$rivirow[hyllytaso]'";
							}
							break;
						default:
							$rvalues .= ", '".$rivirow[$i]."'";
					}
				}

				$kysely = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
				$insres = mysql_query($kysely) or pupe_error($kysely);
				$insid  = mysql_insert_id();
				
				//Kopioidaan tilausrivin lisatiedot
				$query = "	SELECT *
							FROM tilausrivin_lisatiedot
							WHERE tilausrivitunnus = '$rivirow[tunnus]' 
							and yhtio = '$monistarow[yhtio]'";
				$monistares2 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($monistares2) > 0) {
					$monistarow2 = mysql_fetch_array($monistares2);

					$kysely = "	INSERT INTO tilausrivin_lisatiedot 
								SET yhtio 			= '$kukarow[yhtio]',
								laatija				= '$kukarow[kuka]',
								luontiaika 			= now(), 
								tilausrivitunnus	= $insid,";

					for($i=0; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta
						switch (mysql_field_name($monistares2,$i)) {
							case 'yhtio':
							case 'laatija':
							case 'luontiaika':
							case 'tilausrivitunnus':
							case 'tiliointirivitunnus':
							case 'tilausrivilinkki':
							case 'toimittajan_tunnus':
							case 'tunnus':
							case 'muutospvm':
							case 'muuttaja':
								break;
							case 'osto_vai_hyvitys':
								if ($monistarow2[$i] == "O" and $kumpi == 'HYVITA') {
									$kysely .= mysql_field_name($monistares2, $i)."='H',";	
								}
								elseif ($monistarow2[$i] == "H" and $kumpi == 'HYVITA') {
									$kysely .= mysql_field_name($monistares2, $i)."='O',";	
								}
								else {
									$kysely .= mysql_field_name($monistares2, $i)."='".$monistarow2[$i]."',";	
								}
								break;
							default:
								$kysely .= mysql_field_name($monistares2, $i)."='".$monistarow2[$i]."',";
						}
					}
					
					$kysely  = substr($kysely, 0, -1);
					$insres2 = mysql_query($kysely) or pupe_error($kysely);					
				}
				
				// Kopsataan sarjanumerot kuntoon jos tilauskella oli sellaisia
				if ($kumpi == 'HYVITA' and $kukarow["yhtio"] == $monistarow["yhtio"]) {
					if ($rivirow["kpl"] > 0) {
						$tunken = "myyntirivitunnus";
					}
					else {
						$tunken = "ostorivitunnus";
					}

					$query = "	SELECT * 
								FROM sarjanumeroseuranta 
								WHERE yhtio ='$kukarow[yhtio]' 
								and tuoteno ='$rivirow[tuoteno]' 
								and $tunken ='$rivirow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
										
					while($sarjarow = mysql_fetch_array($sarjares)) {
						if ($uusikpl > 0) {
							$uusi_tunken = "myyntirivitunnus";
						}
						else {
							$uusi_tunken = "ostorivitunnus";
						}
						
						//Tutkitaan lˆytyykˆ t‰llanen vapaa sarjanumero jo?
						$query = "	SELECT tunnus 
									FROM sarjanumeroseuranta 
									WHERE yhtio			= '$kukarow[yhtio]' 
									and tuoteno			= '$rivirow[tuoteno]' 
									and sarjanumero 	= '$sarjarow[sarjanumero]' 
									and $uusi_tunken	= 0 
									LIMIT 1";
						$sarjares1 = mysql_query($query) or pupe_error($query);
						
						if (mysql_num_rows($sarjares1) == 1) {
							$sarjarow1 = mysql_fetch_array($sarjares1);
							
							$query = "	UPDATE sarjanumeroseuranta
										SET $uusi_tunken= '$insid'
										WHERE tunnus 	= '$sarjarow1[tunnus]'
										and yhtio		= '$kukarow[yhtio]'";
							$sres = mysql_query($query) or pupe_error($query);
						}
						else {
							$query = "	INSERT INTO sarjanumeroseuranta
										SET yhtio		= '$kukarow[yhtio]',
										tuoteno			= '$rivirow[tuoteno]',
										sarjanumero		= '$sarjarow[sarjanumero]',
										lisatieto		= '$sarjarow[lisatieto]',
										kaytetty		= '$sarjarow[kaytetty]',
										$uusi_tunken	= '$insid',
										takuu_alku 		= '$sarjarow[takuu_alku]',
										takuu_loppu		= '$sarjarow[takuu_loppu]',
										era_kpl			= '$sarjarow[era_kpl]',
										hyllyalue   	= '$sarjarow[hyllyalue]',
										hyllynro    	= '$sarjarow[hyllynro]',
										hyllytaso   	= '$sarjarow[hyllytaso]',
										hyllyvali   	= '$sarjarow[hyllyvali]'";
							$sres = mysql_query($query) or pupe_error($query);
						}
					}
				}
				
				//tehd‰‰n alvikorjaus jos k‰ytt‰j‰ on pyyt‰nyt sit‰
				if ($alvik == "on" and $rivirow["hinta"] != 0) {

					$query = "	SELECT * 
								from tuote 
								where yhtio = '$monistarow[yhtio]' 
								and tuoteno = '$rivirow[tuoteno]'";
					$tres  = mysql_query($query) or pupe_error($query);
					$trow  = mysql_fetch_array($tres);

					$vanhahinta = $rivirow["hinta"];

					if ($yhtiorow["alv_kasittely"] == "") {
						$uusihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f",round($rivirow['hinta'] / (1+$rivirow['alv']/100) * (1+$trow["alv"]/100),$yhtiorow['hintapyoristys']));
					}
					else {
						$uusihinta = $rivirow['hinta'];
					}

					//lasketaan alvit
					list($uusihinta, $alv) = alv($laskurow, $trow, $uusihinta, '', '');					
					
					if ($vanhahinta != $uusihinta) {
						echo t("Korjataan hinta").": $vanhahinta --> $uusihinta<br>";

						$query = "	UPDATE tilausrivi 
									set hinta='$uusihinta', alv='$alv' 
									where yhtio	= '$kukarow[yhtio]' 
									and otunnus	= '$utunnus' 
									and tunnus	= '$insid'";
						$tres  = mysql_query($query) or pupe_error($query);
					}
				}
			}

			//Korjataan perheid:t uusilla riveill‰
			$query = "	SELECT perheid, min(tunnus) uusiperheid
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$utunnus'
						and perheid != 0
						GROUP by perheid";
			$copresult = mysql_query($query) or pupe_error($query);

			while ($coprivirow = mysql_fetch_array($copresult)) {
				$query = "	UPDATE tilausrivi
							SET perheid = '$coprivirow[uusiperheid]'
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$utunnus'
							and perheid = '$coprivirow[perheid]'";
				$cores = mysql_query($query) or pupe_error($query);
			}

			//Korjataan perheid2:t uusilla riveill‰
			$query = "	SELECT perheid2, min(tunnus) uusiperheid2
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$utunnus'
						and perheid2 != 0
						GROUP by perheid2";
			$copresult = mysql_query($query) or pupe_error($query);

			while ($coprivirow = mysql_fetch_array($copresult)) {
				$query = "	UPDATE tilausrivi
							SET perheid2 = '$coprivirow[uusiperheid2]'
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$utunnus'
							and perheid2 = '$coprivirow[perheid2]'";
				$cores = mysql_query($query) or pupe_error($query);
			}
			
			if($slask == "on") {
				$query = "	SELECT *
							from lasku
							where yhtio = '$kukarow[yhtio]'
							and tunnus	= '$utunnus'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				$kukarow["kesken"] = $laskurow["tunnus"];

				require("tilauskasittely/tilaus-valmis.inc");
			}
		} # end for $monta
	}
	$tee = ''; //menn‰‰n alkuun
}

if ($tee == '' and $vain_monista == "") {
	//syˆtet‰‰n tilausnumero
	echo "<br><table>";
	echo "<form action = '$PHP_SELF' method = 'post'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<tr><th>".t("Asiakkaan nimi")."</th><td class='back'></td><td><input type='text' size='10' name='ytunnus'></td></tr>";
	echo "<tr><th>".t("Tilausnumero")."</th><td class='back'></td><td><input type='text' size='10' name='otunnus'></td></tr>";
	
	if ($toim == '') {
		echo "<tr><th>".t("Laskunumero")."</th><td class='back'></td><td><input type='text' size='10' name='laskunro'></td></tr>";		
	}

	echo "</table>";

	echo "<br><input type='submit' value='".t("Jatka")."'>";
	echo "</form>";
	
	if ($toim == '') {
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='mikrotila'>";
		echo "<br><input type='submit' value='".t("Lue monistettavat laskut tiedostosta")."'>";
		echo "</form>";
	}

	require ('inc/footer.inc');
}


?>
