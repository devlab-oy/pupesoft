<?php

	require ("inc/parametrit.inc");
	
	echo "<font class='head'>".t("Tulosta inventointilista")."</font><hr>";

	if ($tee == 'TULOSTA') {

		$tulostimet[0] = "Inventointi";
		if (count($komento) == 0) {
			require("inc/valitse_tulostin.inc");
		}
		
		
		// jos ollaan ruksattu nayta myös inventoidut
		if ($naytainvtuot == '') {
			$datesubnow = " and tuotepaikat.inventointiaika <= date_sub(now(),interval 14 day) ";
		}
		else {
			$datesubnow = "";
		}
		
		// jos ollaan ruksattu vain saldolliset tuotteet
		if ($arvomatikka!='') {
			$extra = " and tuotepaikat.saldo > 0 ";
		}
		else {
			$extra = "";
		}
		
		$kutsu = "";
		
		//hakulause, tämä on samam kaikilla vaihtoehdolilla  ja gorup by lauyse joka on sama kaikilla
		$select  = " tuote.tuoteno, group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo,
		concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta";		
		$groupby = " tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, varastopaikka, inventointiaika, tuotepaikat.saldo ";
		
		if(($try != '' and $osasto != '') or ($ahyllyalue != '' and $lhyllyalue != '') or ($toimittaja != '') or ($tuotemerkki != '')) {
			///* Inventoidaan *///
			
			
			
			//näytetään vain $top myydyintä tuotetta
			if ($top > 0) {
				$kutsu .= " ".t("Top:").$top." ";
				//Rullaava 6 kuukautta taaksepäin 
				$kka = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
				$vva = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
				$ppa = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
				
				$query = "	SELECT tuote.tuoteno, sum(rivihinta) summa 
							FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = ''
							WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]' 
							and tilausrivi.tyyppi 			= 'L' 
							and tilausrivi.osasto 			= '$osasto' 
							and tilausrivi.try 				= '$try' 
							and tilausrivi.laskutettuaika	>= '$vva-$kka-$ppa' 
							GROUP BY 1
							ORDER BY summa desc 
							LIMIT $top";
				$tuotresult = mysql_query($query) or pupe_error($query);
				
				$tuotenumerot = "";
				
				while ($tuotrow = mysql_fetch_array($tuotresult)) {
					$tuotenumerot .= "'".$tuotrow["tuoteno"]."',";
				}	
				$tuotenumerot = substr($tuotenumerot,0,-1);
					
				if ($tuotenumerot != '') {
					$lisa = " and tuote.tuoteno in ($tuotenumerot) ";
				}
				else {
					$lisa = "";
				}			
			
			}
			
			$where = "";
			
			if ($try != '' and $osasto != '') {
				///* Inventoidaan osaston tai tuoteryhmän perusteella *///
				$kutsu .= " ".t("Osasto:")."$osasto ".t("Tuoteryhmä:")."$try ";
				
				$yhtiotaulu = "tuote";
				$from 		= " FROM tuote use index (osasto_try_index) ";
				$join 		= " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $datesubnow $extra ";
				$lefttoimi 	= " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno ";
				
				$where		= " and tuote.osasto = '$osasto' 
								and tuote.try = '$try' 
								and tuote.ei_saldoa	= '' $lisa ";
			}
			
			if ($tuotemerkki != '') {
				///* Inventoidaan tuotemerkin perusteella *///
				$kutsu .= " ".t("Tuotemerkki").": $tuotemerkki ";
				
				if ($from == '') {
					$yhtiotaulu = "tuote";
					$from 		= " FROM tuote use index (osasto_try_index) ";
					$join 		= " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $datesubnow $extra ";
					$lefttoimi 	= " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno ";
				}
				$where		.= " and tuote.tuotemerkki = '$tuotemerkki' ";
			}
			
			if ($ahyllyalue != '' and $lhyllyalue != '') {
				///* Inventoidaan tietty varastoalue *///
				$apaikka = strtoupper(sprintf("%05s",$ahyllyalue)).strtoupper(sprintf("%05s",$ahyllynro)).strtoupper(sprintf("%05s",$ahyllyvali)).strtoupper(sprintf("%05s",$ahyllytaso));
				$lpaikka = strtoupper(sprintf("%05s",$lhyllyalue)).strtoupper(sprintf("%05s",$lhyllynro)).strtoupper(sprintf("%05s",$lhyllyvali)).strtoupper(sprintf("%05s",$lhyllytaso));

				$kutsu .= " ".t("Varastopaikat").": $apaikka - $lpaikka ";
			
				if ($from == '') {
					$yhtiotaulu = "tuotepaikat";
					$from 		= " FROM tuotepaikat ";
					$join 		= " JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = '' $lisa ";
					$lefttoimi 	= " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuotepaikat.yhtio and tuotteen_toimittajat.tuoteno = tuotepaikat.tuoteno ";
					
						$where		= " and concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) >= '$apaikka'
										and concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) <= '$lpaikka'												
										and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $datesubnow $extra ";

					}
					else {
						$join		.= " and concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) >= '$apaikka'
										 and concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) <= '$lpaikka' ";
				}
				
			}
			
			if ($toimittaja != '') {
				///* Inventoidaan tietyn toimittajan tuotteet *///
				$kutsu .= " ".t("Toimittaja:")."$toimittaja ";
				
				if ($from == '') {
					$yhtiotaulu = "tuotteen_toimittajat";
					$from 		= " FROM tuotteen_toimittajat ";
					$join 		= " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' $datesubnow $extra 
									JOIN tuote on tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.ei_saldoa = '' $lisa ";
					
					$where		= " and tuotteen_toimittajat.toimittaja = '$toimittaja'";
				}
				else {
					$join 		.= " JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno and tuotteen_toimittajat.toimittaja = '$toimittaja' ";
				}
				$lefttoimi = "";
			}
			
			$query = "	SELECT $select
						$from
						$join
						$lefttoimi
						WHERE $yhtiotaulu.yhtio	= '$kukarow[yhtio]'
						$where
						group by $groupby
						ORDER BY sorttauskentta, tuoteno";						
			$saldoresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Ei löytynyt rivejä")."</font><br><br>";
				$tee='';
			}
		}
		elseif($raportti != '') {
			///* Inventoidaan jonkun raportin avulla *///	
		
			if ($raportti == 'loppuneet') {
				
				$kutsu = " ".t("Loppuneet tuotteet")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";	
			
				$query = "	SELECT $select
							FROM tuotepaikat use index (saldo_index)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
							and tuotepaikat.saldoaika >= '$vva-$kka-$ppa 00:00:00'
							and tuotepaikat.saldoaika <= '$vvl-$kkl-$ppl 23:59:59'
							and tuotepaikat.saldo 	  <= 0
							$datesubnow							
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00'
							group by $groupby
							ORDER BY sorttauskentta, tuoteno";
				$saldoresult = mysql_query($query) or pupe_error($query);
            }
			
			if ($raportti == 'vaarat') {
				
				$kutsu = " ".t("Väärät Saldot")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";	
							
				$query = "	SELECT distinct $select
							FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
							JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tilausrivi.yhtio			= '$kukarow[yhtio]' 
							and tilausrivi.tyyppi 			= 'L' 
							and tilausrivi.laskutettuaika	>= '$vva-$kka-$ppa'
							and tilausrivi.laskutettuaika	<= '$vvl-$kkl-$ppl' 
							and tilausrivi.tilkpl	   		<> tilausrivi.kpl
							and tilausrivi.var 		   		in ('H','')	
							and tuotepaikat.hyllyalue 		= tilausrivi.hyllyalue 
							and tuotepaikat.hyllynro  		= tilausrivi.hyllynro 
							and tuotepaikat.hyllyvali		= tilausrivi.hyllyvali 
							and tuotepaikat.hyllytaso		= tilausrivi.hyllytaso
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00'
							group by $groupby
							ORDER BY sorttauskentta, tuotepaikat.tuoteno";
				$saldoresult = mysql_query($query) or pupe_error($query);
			}
			
			if ($raportti == 'negatiiviset') {
				
				$kutsu = " ".t("Tuotteet miinus-saldolla")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";	
			
				$query = "	SELECT $select
							FROM tuotepaikat use index (saldo_index)
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
							and tuotepaikat.saldo 	  < 0
							$datesubnow	
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' 
							group by $groupby
							ORDER BY sorttauskentta, tuoteno";
				$saldoresult = mysql_query($query) or pupe_error($query);
            }
			
			
			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Näillä ehdoilla ei nyt löytynyt mitään! Skarppaas vähän!")."</font><br><br>";
				$tee='';
			}
		}
		elseif ($tila == "SIIVOUS") {
				$query = "	SELECT $select
							FROM tuotepaikat
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
							WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
							and tuotepaikat.tunnus in ($saldot)
							$datesubnow							
							and tuotepaikat.inventointilista_aika = '0000-00-00 00:00:00' 
							group by $groupby
							ORDER BY sorttauskentta, tuoteno";
				$saldoresult = mysql_query($query) or pupe_error($query);
		}
		else {
			echo "<font class='error'>".t("Et syöttänyt mitään järkevää! Skarppaas vähän!")."</font><br><br>";
			$tee='';
		}
	}

	if ($tee == "TULOSTA") {
		if (mysql_num_rows($saldoresult) > 0 ) {
			//kirjoitetaan  faili levylle..
			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$filenimi = "/tmp/Inventointilista-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($filenimi, "w+");

			$pp = date('d');
			$kk = date('m');
			$vv = date('Y');
			$kello = date('H:i:s');
			
			//rivinleveys default
			$rivinleveys = 135;
				
			//haetaan inventointilista numero tässä vaiheessa
			$query = "	SELECT max(inventointilista) listanro
						FROM tuotepaikat
						WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$lrow = mysql_fetch_array($result);
			
			$listanro = $lrow["listanro"]+1;
			$listaaika = date("Y-m-d H:i:s");	
				
			$ots  = t("Inventointilista")." $kutsu  Listanumero: $listanro   $pp.$kk.$vv - $kello\n$yhtiorow[nimi]\n\n";
			$ots .= sprintf ('%-14.14s', 	t("Paikka"));
			$ots .= sprintf ('%-21.21s', 	t("Tuoteno"));
			$ots .= sprintf ('%-21.21s', 	t("Toim.Tuoteno"));
			$ots .= sprintf ('%-30.30s', 	t("Nimitys"));
			
			if ($naytasaldo != '') {
				$rivinleveys += 10;
				$ots .= sprintf ('%-10.10s',t("Hyllyssä"));
				$katkoviiva = '----------';
			}
			
			$ots .= sprintf ('%-7.7s',		t("Määrä"));
			$ots .= sprintf ('%-9.9s', 		t("Yksikkö"));
			$ots .= sprintf ('%-20.20s', 	t("Inv.pvm"));
			$ots .= sprintf ('%8.8s',	 	t("Tikpl"));
			$ots .= "\n";
			$ots .= "---------------------------------------------------------------------------------------------------------------------------------------$katkoviiva\n\n";
			fwrite($fh, $ots);
			$ots = chr(12).$ots;
						
			$rivit = 1;
			
			while($tuoterow = mysql_fetch_array($saldoresult)) {
				// Joskus halutaan vain tulostaa lista, mutta ei oikeasti invata tuotteita
				if ($ei_inventointi == "") {
					//päivitetään tuotepaikan listanumero ja listaaika
					$query = "	UPDATE tuotepaikat
								SET inventointilista	= '$listanro', 
								inventointilista_aika	= '$listaaika'
								WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
								and tuoteno		= '$tuoterow[tuoteno]' 
								and hyllyalue	= '$tuoterow[hyllyalue]' 
								and hyllynro 	= '$tuoterow[hyllynro]'
								and hyllyvali 	= '$tuoterow[hyllyvali]'
								and hyllytaso 	= '$tuoterow[hyllytaso]'
								LIMIT 1";
					$munresult = mysql_query($query) or pupe_error($query);
				}
								
				if ($rivit >= 25) {
					fwrite($fh, $ots);
					$rivit = 1;				
				}
				
				if ($naytasaldo != '') {
					//Haetaan kerätty määrä
					$query = "	SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,
								ifnull(sum(tilausrivi.varattu),0) ennpois
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE yhtio 	= '$kukarow[yhtio]'
								and tyyppi 	   in ('L','G','V')
								and tuoteno		= '$tuoterow[tuoteno]' 
								and varattu    <> '0'
								and laskutettu 	= '' 
								and hyllyalue	= '$tuoterow[hyllyalue]' 
								and hyllynro 	= '$tuoterow[hyllynro]'
								and hyllyvali 	= '$tuoterow[hyllyvali]'
								and hyllytaso 	= '$tuoterow[hyllytaso]'";																					
					$hylresult = mysql_query($query) or pupe_error($query);					
					$hylrow = mysql_fetch_array($hylresult);		
					
					$hyllyssa = $tuoterow['saldo']-$hylrow['keratty'];
				}
				else {
					$hyllyssa = '';
				}
				
				//katsotaan onko tuotetta tilauksessa
				$query = "	SELECT sum(varattu) varattu, min(toimaika) toimaika
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoterow[tuoteno]' and varattu>0 and tyyppi='O'";
				$result1 = mysql_query($query) or pupe_error($query);
				$prow    = mysql_fetch_array($result1);

				if ($tuoterow["inventointiaika"]=='0000-00-00 00:00:00') {
					$tuoterow["inventointiaika"] = t("Ei inventoitu");
				}

				$prn  = sprintf ('%-14.14s', 	$tuoterow["varastopaikka"]);
				$prn .= sprintf ('%-21.21s', 	$tuoterow["tuoteno"]);
				$prn .= sprintf ('%-21.21s', 	$tuoterow["toim_tuoteno"]);
				$prn .= sprintf ('%-30.30s', 	$tuoterow["nimitys"]);
				
				if ($naytasaldo != '') {
					$prn .= sprintf ('%-10.10s', 		$hyllyssa);
				}
				
				$prn .= sprintf ('%-7.7s', 		"_____");
				$prn .= sprintf ('%-9.9s', 		$tuoterow["yksikko"]);
				$prn .= sprintf ('%-20.20s', 	$tuoterow["inventointiaika"]);
				$prn .= sprintf ('%8.8s', 	$prow["varattu"]);
				$prn .= "\n\n";
				fwrite($fh, $prn);
				$rivit++;
			}

			fclose($fh);

			//käännetään kaunniksi
			system("a2ps -o ".$filenimi.".ps -r --medium=A4 --chars-per-line=$rivinleveys --no-header --columns=1 --margin=0 --borders=0 $filenimi");
			
			if ($komento["Inventointi"] == 'email') {
				
				system("ps2pdf ".$filenimi.".ps ".$filenimi.".pdf");
				
				$liite = $filenimi.".pdf";
				$kutsu = "Inventointilista";
				
				require("inc/sahkoposti.inc");				
			}
			elseif ($komento["Inventointi"] != '') {
				// itse print komento...
				$line = exec("$komento[Inventointi] ".$filenimi.".ps");
			}

			echo "<font class='message'>".t("Inventointilista tulostuu!")."</font><br><br>";

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $filenimi");
			system("rm -f ".$filenimi.".ps");
			system("rm -f ".$filenimi.".pdf");
			$tee = "";
		}
	}

	if ($tee == '') {

		echo "<form name='inve' action='$PHP_SELF' method='post' enctype='multipart/form-data' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";

		echo "<br><table>";
		echo "<tr><th>".t("Anna osasto ja tuoteryhmä:")."</th>
		<td><input type='text' size='6' name='osasto'>
		<input type='text' size='6' name='try'>
		</td></tr>";
		echo "<tr><th>".t("Ja listaa vain myydyimmät:")."</th>
		<td><input type='text' size='6' name='top'> ".t("tuotetta").".
		</td></tr>";

		echo "<tr><td class='back'>".t("ja/tai")."...</td></tr>";
		
		echo "<tr><th>".t("Anna alkuvarastopaikka:")."</th>
		<td><input type='text' size='6' name='ahyllyalue'>
		<input type='text' size='6' name='ahyllynro'>
		<input type='text' size='6' name='ahyllyvali'>
		<input type='text' size='6' name='ahyllytaso'>
		</td></tr>";

		echo "<tr><th>".t("ja loppuvarastopaikka:")."</th>
		<td><input type='text' size='6' name='lhyllyalue'>
		<input type='text' size='6' name='lhyllynro'>
		<input type='text' size='6' name='lhyllyvali'>
		<input type='text' size='6' name='lhyllytaso'>
		</td></tr>";
		
		echo "<tr><td class='back'>".t("ja/tai")."...</td></tr>";
		
		echo "<tr><th>".t("Anna toimittajanumero(ytunnus):")."</th>
		<td><input type='text' size='25' name='toimittaja'>
		</td></tr>";

		echo "<tr><td class='back'>".t("ja/tai")."...</td></tr>";
		
		echo "<tr><th>".t("Valitse tuotemerkki:")."</th>";
		
		$query = "	SELECT distinct tuotemerkki
					FROM tuote use index (yhtio_tuotemerkki)
					WHERE yhtio='$kukarow[yhtio]' 
					$poislisa
					and tuotemerkki != ''
					ORDER BY tuotemerkki";
		$sresult = mysql_query($query) or pupe_error($query);
		
		echo "<td><select name='tuotemerkki'>";
		echo "<option value=''>".t("Ei valintaa")."</option>";
		while($srow = mysql_fetch_array ($sresult)){
			echo "<option value='$srow[0]'>$srow[0]</option>";
		}
		echo "</td></tr>";

		echo "<tr><td class='back'><br></td></tr>";
        
        echo "<tr><td class='back' colspan='2'>".t("tai inventoi raportin avulla")."...</th></tr>
			<tr><th>".t("Valitse raportti")."</th><td><select name='raportti'>
			<option value=''>".t("Valitse")."</option>
			<option value='vaarat'>".t("Väärät saldot")."</option>
			<option value='loppuneet'>".t("Loppuneet tuotteet")."</option>
			<option value='negatiiviset'>".t("Kaikki miinus-saldolliset")."</option>
			</select>
			</td></tr>";
		
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");
			
			
		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'>
			<input type='text' name='kka' value='$kka' size='3'>
			<input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'>
			<input type='text' name='kkl' value='$kkl' size='3'>
			<input type='text' name='vvl' value='$vvl' size='5'>";

		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><td class='back' colspan='2'>".t("Valitse ehdoista")."...</th></tr>";
		
		echo "<tr><th>".t("Listaa vain saldolliset tuotteet:")."</th>
		<td><input type='checkbox' name='arvomatikka'></td>
		</tr>";
			
		
		echo "<tr><th>".t("Tulosta hyllyssä oleva määrä:")."</th>
		<td><input type='checkbox' name='naytasaldo'></td>
		</tr>";
		
		
		echo "<tr><th>".t("Listaa myös tuotteet jotka ovat inventoitu lähiaikoina:")."</th>
		<td><input type='checkbox' name='naytainvtuot'></td>
		</tr>";
		
		echo "</table>";

		echo "<br><input type='Submit' value='".t("Valitse")."'>";
		echo "</form>";
	}

	require ("inc/footer.inc");
?>