<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Inventointi")."</font><hr>";

	if ($oikeurow["paivitys"] != '1') { // Saako päivittää
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lisätä tätä tietoa")."</b><br>";
			$uusi = '';
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa tätä tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa tätä tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
			exit;
		}
	}

	//katotaan onko tiedosto ladattu
	if ($tee == "FILE") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {

			list($name,$ext) = split("\.", $_FILES['userfile']['name']);

			if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "XLS" || strtoupper($ext) == "CSV")) {
				die ("<font class='error'><br>".t("Ainoastaa .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
			}
			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
			}

			$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

			// luetaan tiedosto alusta loppuun...
			$rivi = fgets($file, 4096);

			$tuote = array();
			$maara = array();

			while (!feof($file)) {

				// luetaan rivi tiedostosta..
				$poista	  = array("'", "\\", " ","\"");
				$rivi	  = str_replace($poista,"",$rivi);
				$rivi	  = str_replace(",",".",$rivi);
				$rivi	  = explode("\t", trim($rivi));

				$tuo 		= $rivi[0];
				$hyl 		= $rivi[1];
				$maa 		= $rivi[2];
				$lisaselite = $rivi[3];


				if ($tuo != '' and $hyl != '' and $maa != '') {

					$hylp = split("-", $hyl);

					$tuote[] = $tuo."#".$hylp[0]."#".$hylp[1]."#".$hylp[2]."#".$hylp[3];
					$maara[] = $maa;
				}

				$rivi = fgets($file, 4096);
			}

			fclose($file);

			if (count($tuote) > 0) {
				$tee 		= "VALMIS";
				$valmis 	= "OK";
				$fileesta 	= "ON";
			}
		}
	}

	// lukitaan tableja
	$query = "lock tables tuotepaikat write, tapahtuma write, lasku write, tiliointi write, sanakirja write, tuote read, tilausrivi read, tuotteen_avainsanat read, sarjanumeroseuranta write, tilausrivi as tilausrivi_myynti read, tilausrivi as tilausrivi_osto read, tuotepaikat as tt read";
	$result = mysql_query($query) or pupe_error($query);

	//tuotteen varastostatus
	if ($tee == 'VALMIS') {

		$virhe = 0;

		if (count($tuote) > 0) {
			foreach($tuote as $i => $tuotteet) {
				
				$tuotetiedot = split("#", $tuotteet);

				//näitä muuttujia me tarvitaan
				$tuoteno 	= $tuotetiedot[0];
				$hyllyalue 	= $tuotetiedot[1];
				$hyllynro	= $tuotetiedot[2];
				$hyllyvali  = $tuotetiedot[3];
				$hyllytaso	= $tuotetiedot[4];
				$kpl		= $maara[$i];				
				$poikkeama  = 0;
				$skp		= 0;

				if ($kpl != '') {

					//Sarjanumerot
					if (substr($kpl,0,1) == '+' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) substr($kpl,1)) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
						$virhe = 1;
					}
					elseif (substr($kpl,0,1) == '-' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) substr($kpl,1)) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
						$virhe = 1;
					}
					elseif(substr($kpl,0,1) != '-' and substr($kpl,0,1) != '+' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) $kpl) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
						$virhe = 1;
					}
					
					
					if (is_array($eranumero_kaikki[$i])) {
						if (is_array($eranumero_valitut[$i])) {
							$erasyotetyt = 0;
						
							foreach($eranumero_valitut[$i] as $ekpl) {
								$erasyotetyt += $ekpl;
								
							}
							
							if (substr($kpl,0,1) == '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != (int) substr($kpl,1)) {
								echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
								$virhe = 1;
							}
							elseif (substr($kpl,0,1) == '-' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != (int) substr($kpl,1)) {
								echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
								$virhe = 1;
							}
							elseif(substr($kpl,0,1) != '-' and substr($kpl,0,1) != '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != (int) $kpl) {
								echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
								$virhe = 1;
							}
						}
						else {
							echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
							$virhe = 1;	
						}
					}

					//Haetaan tuotepaikan tiedot
					$query = "	SELECT *
								FROM tuotepaikat, tuote
								WHERE tuotepaikat.yhtio	  = '$kukarow[yhtio]'
								and tuotepaikat.tuoteno	  = '$tuoteno'
								and tuotepaikat.hyllyalue = '$hyllyalue'
								and tuotepaikat.hyllynro  = '$hyllynro'
								and tuotepaikat.hyllyvali = '$hyllyvali'
								and tuotepaikat.hyllytaso = '$hyllytaso'
								and tuote.tuoteno		  = tuotepaikat.tuoteno
								and tuote.yhtio			  = tuotepaikat.yhtio";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0 and $virhe == 0) {

						if ($lisaselite == "PERUSTA") {
							// PERUSTETAAN tuotepaikka

							// katotaa löytyykö tuote
							$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) == 1) {

								// katotaan onko tuotteella jo oletuspaikka
								$query = "select * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and oletus!=''";
								$result = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($result) > 0) {
									$oletus = "";
								}
								else {
									$oletus = "X";
								}

								$query = "insert into tuotepaikat set
											yhtio     = '$kukarow[yhtio]',
											tuoteno	  = '$tuoteno',
											hyllyalue = '$hyllyalue',
											hyllynro  = '$hyllynro',
											hyllyvali = '$hyllyvali',
											hyllytaso = '$hyllytaso',
											oletus    = '$oletus'";
								$result = mysql_query($query) or pupe_error($query);

								$query = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$tuoteno',
											kpl 		= '0',
											kplhinta	= '0',
											hinta 		= '0',
											laji 		= 'uusipaikka',
											selite 		= '".t("Inventoidessa lisättiin tuotepaikka")." $hyllyalue $hyllynro $hyllyvali $hyllytaso',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$result = mysql_query($query) or pupe_error($query);

								// haetaan perustettu resultti (sama query ku ylhäällä)
								$query = "	SELECT *
											FROM tuotepaikat, tuote
											WHERE tuotepaikat.yhtio	  = '$kukarow[yhtio]'
											and tuotepaikat.tuoteno	  = '$tuoteno'
											and tuotepaikat.hyllyalue = '$hyllyalue'
											and tuotepaikat.hyllynro  = '$hyllynro'
											and tuotepaikat.hyllyvali = '$hyllyvali'
											and tuotepaikat.hyllytaso = '$hyllytaso'
											and tuote.tuoteno		  = tuotepaikat.tuoteno
											and tuote.yhtio			  = tuotepaikat.yhtio";
								$result = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($result) == 1) {
									//echo "<font class='error'>".t("Perustettiin varastopaikka tuotteelle")." $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso</font><br>";
								}
								else {
									echo "<font class='error'>(".mysql_num_rows($result).") ".t("Varastopaikan perustus epäonnistui")." $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso $query</font><br>";
								}
							}
							else {
								echo "<font class='error'>".t("Tuotetta ei löydy")." $tuoteno</font><br>";
							}
						}
						else {
							echo "<font class='error'>".t("Varastopaikka ei löydy tuotteelta")." $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso</font><br>";
						}
					}

					if (mysql_num_rows($result) == 1 and $virhe == 0) {
						$row = mysql_fetch_array($result);

						if (($lista != '' and $row["inventointilista_aika"] != "0000-00-00 00:00:00") or ($lista == '' and $row["inventointilista_aika"] == "0000-00-00 00:00:00")) {
							//jos invataan raportin avulla niin tehdään päivämäärätsekit ja lasketaan saldo takautuvasti
							$saldomuutos = 0;

							if ($row["inventointilista_aika"] != "0000-00-00 00:00:00") {
								//katotaan paljonko saldot on muuttunut listan ajoajankohdasta
								$query = "	SELECT sum(tapahtuma.kpl) muutos
											FROM tapahtuma
											JOIN tilausrivi ON tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.rivitunnus = tilausrivi.tunnus
											and tilausrivi.hyllyalue	= '$hyllyalue'
											and tilausrivi.hyllynro 	= '$hyllynro'
											and tilausrivi.hyllyvali 	= '$hyllyvali'
											and tilausrivi.hyllytaso 	= '$hyllytaso'
											WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
											and tapahtuma.tuoteno = '$tuoteno'
											and tapahtuma.laadittu >= '$row[inventointilista_aika]'
											and tapahtuma.kpl <> 0
											and laji != 'Inventointi'";
								$result = mysql_query($query) or pupe_error($query);

								$trow = mysql_fetch_array ($result);

								if ($trow["muutos"] != 0) {
									$saldomuutos = $trow["muutos"];
								}

								//kuinka monta kerättyä oli listan ajohetkellä, mutta nyt ne ovat laskutettu tai laskuttamatta
								$query = "	SELECT ifnull(sum(if(laskutettuaika='0000-00-00 00:00:00', varattu, kpl)), 0) keratty
											FROM tilausrivi
											WHERE yhtio 	= '$kukarow[yhtio]'
											and tyyppi 		in ('L','G','V')
											and tuoteno		= '$tuoteno'
											and varattu    <> 0
											and kerattyaika		< '$row[inventointilista_aika]'
											and kerattyaika		> '0000-00-00 00:00:00'
											and (laskutettuaika	> '$row[inventointilista_aika]' or laskutettuaika	= '0000-00-00 00:00:00')
											and hyllyalue	= '$hyllyalue'
											and hyllynro 	= '$hyllynro'
											and hyllyvali 	= '$hyllyvali'
											and hyllytaso 	= '$hyllytaso'";
								$hylresult = mysql_query($query) or pupe_error($query);
								$hylrow = mysql_fetch_array($hylresult);
							}
							else {
								//Haetaan kerätty määrä
								$query = "	SELECT ifnull(sum(if(keratty!='', tilausrivi.varattu, 0)), 0) keratty
											FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
											WHERE yhtio 	= '$kukarow[yhtio]'
											and tyyppi 		in ('L','G','V')
											and tuoteno		= '$tuoteno'
											and varattu    <> 0
											and laskutettu 	= ''
											and hyllyalue	= '$hyllyalue'
											and hyllynro 	= '$hyllynro'
											and hyllyvali 	= '$hyllyvali'
											and hyllytaso 	= '$hyllytaso'";
								$hylresult = mysql_query($query) or pupe_error($query);
								$hylrow = mysql_fetch_array($hylresult);
							}
							
							if (substr($kpl,0,1) == '+') {
								$kpl = substr($kpl,1);
								$skp = $kpl;
								$kpl = $row['saldo'] + $kpl;
								
							}
							elseif (substr($kpl,0,1) == '-') {
								$kpl = substr($kpl,1);
								$skp = $kpl*-1;
								$kpl = $row['saldo'] - $kpl;
							}
							else {
								//$kpl on käyttäjän syöttämä hyllysäoleva määrä joka muutetaan saldoksi lisäämällä siihen kerätyt kappaleet
								//ja ottamalla huomioon $saldomuutos joka on saldon muutos listan ajohetkestä
								$kpl = $kpl + $hylrow['keratty'] + $saldomuutos;
								$skp = 0;
							}

							$nykyinensaldo = $row['saldo'];
							$erotus = $kpl - $row['saldo'];
							$cursaldo = $nykyinensaldo + $erotus;

							//triggeröidään erohälytys
							if (abs($erotus) > 10 and $lista != '') {
								$virhe = 1;
							}

							//echo "Tuoteno: $tuoteno Saldomuutos: $saldomuutos Kerätty: $hylrow[keratty] Syötetty: $kpl Hyllyssä: $hyllyssa Nykyinen: $nykyinensaldo Erotus: $erotus<br>";

							///* Inventointipoikkeama prosenteissa *///
							if ($nykyinensaldo != 0) {
								$poikkeama = ($erotus/$nykyinensaldo)*100;
							}
							///* Tehdään jonkinlainen arvaus jos saldo on nolla *///
							else {
								$poikkeama = ($erotus/1)*100;
							}

							// Lasketaan varastonarvon muutos
							if ($row["sarjanumeroseuranta"] == "S") {
								$varvo_ennen = 0;
								$varvo_jalke = 0;
								$varvo_muuto = 0;
								
								for ($aa = 0; $aa < $row['saldo']; $aa++) {									
									$varvo_ennen += sarjanumeron_ostohinta("tunnus", $sarjanumero_kaikki[$i][$aa]);
								}
								for ($aa = 0; $aa < $kpl; $aa++) {
									$varvo_jalke += sarjanumeron_ostohinta("tunnus", $sarjanumero_valitut[$i][$aa]);
								}
								
								$summa = round($erotus * abs($varvo_ennen - $varvo_jalke), 2);
								
								if ($erotus != 0) {
									$row['kehahin'] = round($summa/abs($erotus), 2);
								}
							}
							else {
								if 		($row['epakurantti100pvm'] != '0000-00-00') $row['kehahin'] = 0;							
								elseif 	($row['epakurantti75pvm']  != '0000-00-00') $row['kehahin'] = $row['kehahin'] * 0.25;
								elseif 	($row['epakurantti50pvm']  != '0000-00-00') $row['kehahin'] = $row['kehahin'] * 0.5;
								elseif	($row['epakurantti25pvm']  != '0000-00-00') $row['kehahin'] = $row['kehahin'] * 0.75;
																		
								$summa = round($erotus * $row['kehahin'],2);
							}

							///* Tehdään tapahtuma *///
							$query = "	INSERT into tapahtuma set
										yhtio   = '$kukarow[yhtio]',
										tuoteno = '$row[tuoteno]',
										laji    = 'Inventointi',
										kpl     = '$erotus',
										kplhinta= '$row[kehahin]',
										hinta   = '$row[kehahin]',
										selite  = ";

							if ($erotus > 0) {
								$query .= " '".t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("lisättiin")." $erotus ".t("kappaleella. Saldo nyt")." $cursaldo. $lisaselite',";
							}
							elseif ($erotus < 0) {
								$query .= " '".t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("vähennettiin")." ".abs($erotus)." ".t("kappaleella. Saldo nyt")." $cursaldo. $lisaselite',";
							}
							else {
								$query .= " '".t("Saldo")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("täsmäsi.")." $lisaselite',";
							}
							
							$query .= "	laatija  = '$kukarow[kuka]',
										laadittu = now()";
							$result = mysql_query($query) or pupe_error($query);

							// otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
							$tapahtumaid = mysql_insert_id($link);

							///* Päivitetään tuotepaikka *///
							$query = "UPDATE tuotepaikat";

							if ($erotus > 0) {
								$query .= " SET saldo = saldo+$erotus, ";
							}
							elseif ($erotus < 0) {
								$query .= " SET saldo = saldo-".abs($erotus).", ";
							}
							else {
								$query .= " SET saldo = saldo, ";
							}
							
							$query .= " saldoaika 				= now(),
										inventointiaika 		= now(),
										inventointipoikkeama 	= '$poikkeama',
										inventointilista_aika	= '0000-00-00 00:00:00'
										WHERE yhtio		= '$kukarow[yhtio]'
										and tuoteno		= '$tuoteno'
										and hyllyalue	= '$hyllyalue'
										and hyllynro	= '$hyllynro'
										and hyllyvali	= '$hyllyvali'
										and hyllytaso	= '$hyllytaso'";
							$result = mysql_query($query) or pupe_error($query);
							
							if (($summa <> 0) and (mysql_affected_rows() > 0)) {

								$query = "	INSERT into lasku set
											yhtio      = '$kukarow[yhtio]',
											tapvm      = now(),
											tila       = 'X',
											laatija    = '$kukarow[kuka]',
											viite      = '$tapahtumaid',
											luontiaika = now()";

								$result = mysql_query($query) or pupe_error($query);
								$laskuid = mysql_insert_id($link);

								$query = "INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varasto]',
											kustp    = '',
											tapvm    = now(),
											summa    = '$summa',
											vero     = '0',
											lukko    = '',
											selite   = 'Inventointi $row[tuoteno] $erotus kpl',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = mysql_query($query) or pupe_error($query);

								$query = "INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varastonmuutos]',
											kustp    = '',
											tapvm    = now(),
											summa    = $summa * -1,
											vero     = '0',
											lukko    = '',
											selite   = 'Inventointi $row[tuoteno] $erotus kpl',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = mysql_query($query) or pupe_error($query);
							}
							
							// Piilotetaan tän tuotepaikan pois-invatut sarjanumerot
							if (is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_kaikki[$i]) > 0) {
								if ((float) $skp == 0) {
									// Ei ruksatut sarjanumerot poistetaan
									foreach ($sarjanumero_kaikki[$i] as $snro_tun) {
										if(!is_array($sarjanumero_valitut[$i]) or !in_array($snro_tun, $sarjanumero_valitut[$i])) {
											$query = "	UPDATE sarjanumeroseuranta
														SET myyntirivitunnus = '-1',
														siirtorivitunnus 	 = '-1',
														muuttaja			 = '$kukarow[kuka]',
														muutospvm			 = now()
														WHERE yhtio	= '$kukarow[yhtio]'
														and tunnus = $snro_tun";
											$sarjares = mysql_query($query) or pupe_error($query);								
										}
									}
								}
								elseif ((float) $skp < 0) {
									// Mutetaan $skp-verrran miinus etumerkeillä poistetaan
									foreach ($sarjanumero_valitut[$i] as $snro_tun) {
										$query = "	UPDATE sarjanumeroseuranta
													SET myyntirivitunnus = '-1',
													siirtorivitunnus 	 = '-1',
													muuttaja			 = '$kukarow[kuka]',
													muutospvm			 = now()
													WHERE yhtio	= '$kukarow[yhtio]'
													and tunnus = $snro_tun";
										$sarjares = mysql_query($query) or pupe_error($query);
									}
								}
							}
							
							//Piilotetaan tän tuotepaikan pois-invatut eränumerot
							if (is_array($eranumero_kaikki[$i]) and count($eranumero_kaikki[$i]) > 0) {
								if ((float) $skp == 0) {
									foreach ($eranumero_kaikki[$i] as $snro_tun) {
										$query = "	UPDATE sarjanumeroseuranta
													SET era_kpl 		 = '".$eranumero_valitut[$i][$snro_tun]."',
													muuttaja			 = '$kukarow[kuka]',
													muutospvm			 = now()
													WHERE yhtio	= '$kukarow[yhtio]'
													and tunnus = $snro_tun";
										$sarjares = mysql_query($query) or pupe_error($query);									
									}
								}
								elseif ((float) $skp < 0) {
									// Mutetaan $skp-verrran miinus etumerkeillä poistetaan
									foreach ($sarjanumero_valitut[$i] as $snro_tun) {
										$query = "	UPDATE sarjanumeroseuranta
													SET myyntirivitunnus = '-1',
													siirtorivitunnus 	 = '-1',
													muuttaja			 = '$kukarow[kuka]',
													muutospvm			 = now()
													WHERE yhtio	= '$kukarow[yhtio]'
													and tunnus = $snro_tun";
										$sarjares = mysql_query($query) or pupe_error($query);
									}
								}
							}
						}

						if($fileesta == "ON") {
							echo "<font class='message'>".t("Tuote")."   $tuoteno $hyllyalue $hyllynro $hyllyvali $hyllytaso ".t("inventoitu")."!</font><br>";
						}
					}
				}
				else{
					//echo "Tuote $tuoteno $hyllyalue $hyllynro $hyllyvali $hyllytaso Kappalemäärää ei syötetty!<br>";
				}
			}
		}
		
		if ($virhe == 0) {
			if(isset($prev)) {
				$alku = $alku-18;
				$tee = "INVENTOI";
			}
			elseif(isset($next)) {
				$alku = $alku+18;
				$tee = "INVENTOI";
			}
			elseif(isset($valmis)) {
				$tee = "";
			}
		
			//seuraava sivu
			$tuoteno 	= "";
			$hyllyalue 	= "";
			$hyllynro	= "";
			$hyllyvali  = "";
			$hyllytaso	= "";
			$kpl		= "";
			$poikkeama  = "";
		}
		else {
			$tee = "INVENTOI";
		}
	}


	if ($tee == 'INVENTOI') {

		//hakulause, tämä on sama kaikilla vaihtoehdoilla
		$select = " tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.tunnus tptunnus, tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo, tuotepaikat.inventointilista, tuotepaikat.inventointilista_aika, concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta";

		if ($tuoteno != "" and $lista == "") {
			///* Inventoidaan tuotenumeron perusteella *///
			$kutsu = " ".t("Tuote")." $tuoteno ";
			
			$query = "	SELECT $select
						FROM tuote use index (tuoteno_index)
						JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.tuoteno		= '$tuoteno'
						and tuote.ei_saldoa		= ''
						ORDER BY sorttauskentta, tuoteno";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Tuote")." '$tuoteno' ".t("ei löydy!")." ".t("Onko tuote saldoton tuote")."? ".t("Onko tuotteella varastopaikka")."?</font><br><br>";
				$tee='';
			}
		}
		elseif($lista != "") {
			///* Inventoidaan listan perusteella *///
			$kutsu = " ".t("Inventointilista")." $lista ";

			if ($alku == '' or $alku < 0) {
				$alku = 0;
			}
			
			$query = "	SELECT $select
						FROM tuotepaikat USE INDEX (yhtio_inventointilista)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno and tuote.ei_saldoa = '')
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and tuotepaikat.inventointilista = '$lista'
						ORDER BY sorttauskentta, tuoteno
						LIMIT $alku, 18";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Listaa")." '$lista' ".t("ei löydy, tai se on jo inventoitu")."!</font><br><br>";
				$tee='';
			}
		}
		else {
			echo "<font class='error'>".t("VIRHE: Tarkista syötetyt tiedot")."!</font><br><br>";
			$tee='';
		}

		if ($tee == 'INVENTOI') {
			
			echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function toggleAll(toggleBox, toggleBoxBoxes) {
										
					var currForm  = toggleBox.form;
					var isChecked = toggleBox.checked;
					var nimi      = toggleBoxBoxes;

					for (var elementIdx=0; elementIdx < currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name == nimi) {
							currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}

				//-->
				</script>";

			$thlisa = "<th>".t("Varastosaldo")."</th><th>".t("Ennpois")."/".t("Kerätty")."</th><th>".t("Hyllyssä")."</th>";

			echo "<form name='inve' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='lista' value='$lista'>";
			echo "<input type='hidden' name='alku' value='$alku'>";

			echo "<table>";
			echo "<tr><td colspan='7' class='back'>".t("Syötä joko hyllyssä oleva määrä, tai lisättävä määrä + etuliitteellä, tai vähennettävä määrä - etuliitteellä")."</td></tr>";

			echo "<tr>";
			echo "<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th>$thlisa<th>".t("Laskettu hyllyssä")."</th>";
			echo "</tr>";

			while($tuoterow = mysql_fetch_array($saldoresult)) {
				//Haetaan kerätty määrä
				$query = "	SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,	ifnull(sum(tilausrivi.varattu),0) ennpois
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tyyppi 		in ('L','G','V')
							and tuoteno		= '$tuoterow[tuoteno]'
							and varattu    <> '0'
							and laskutettu 	= ''
							and hyllyalue	= '$tuoterow[hyllyalue]'
							and hyllynro 	= '$tuoterow[hyllynro]'
							and hyllyvali 	= '$tuoterow[hyllyvali]'
							and hyllytaso 	= '$tuoterow[hyllytaso]'";
				$hylresult = mysql_query($query) or pupe_error($query);
				$hylrow = mysql_fetch_array($hylresult);

				$hyllyssa = sprintf('%.2f',$tuoterow['saldo']-$hylrow['keratty']);
				$tdlisa = "<td valign='top'>".$tuoterow["saldo"]."</td><td valign='top'>$hylrow[ennpois]/$hylrow[keratty]</td><td valign='top'>".$hyllyssa."</td>";
				
				if ($tuoterow["sarjanumeroseuranta"] != "") {
					$query = "	SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus, round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta, era_kpl
								FROM sarjanumeroseuranta
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
								LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
								WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
								and sarjanumeroseuranta.tuoteno		= '$tuoterow[tuoteno]'
								and sarjanumeroseuranta.myyntirivitunnus	!= -1
								and (	(sarjanumeroseuranta.hyllyalue		= '$tuoterow[hyllyalue]' 
										 and sarjanumeroseuranta.hyllynro 	= '$tuoterow[hyllynro]' 
										 and sarjanumeroseuranta.hyllyvali 	= '$tuoterow[hyllyvali]' 
										 and sarjanumeroseuranta.hyllytaso 	= '$tuoterow[hyllytaso]') 
									 or ('$tuoterow[oletus]' != '' and 
										(	SELECT tunnus 
											FROM tuotepaikat tt 
											WHERE sarjanumeroseuranta.yhtio = tt.yhtio and sarjanumeroseuranta.tuoteno = tt.tuoteno and sarjanumeroseuranta.hyllyalue = tt.hyllyalue
											and sarjanumeroseuranta.hyllynro = tt.hyllynro and sarjanumeroseuranta.hyllyvali = tt.hyllyvali and sarjanumeroseuranta.hyllytaso = tt.hyllytaso) is null))
								and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00')
								ORDER BY sarjanumero";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
				
				if (($tuoterow["inventointilista_aika"] == '0000-00-00 00:00:00' and $lista == '') or ($tuoterow["inventointilista"] == $lista and $tuoterow["inventointilista_aika"] != '0000-00-00 00:00:00')) {

					echo "<tr>";
					echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']); 
					
					if ($tuoterow["sarjanumeroseuranta"] == "S") {	
						if (mysql_num_rows($sarjares) > 0) {
							echo "<br><table>";
							
							$sarjalaskk = 1;
							
							while($sarjarow = mysql_fetch_array($sarjares)) {							
								if ($sarjanumero[$tuoterow["tptunnus"]][$sarjarow["tunnus"]] != '') {
									$chk = "CHECKED";	
								}
								else {
									$chk = "";	
								}
							
								echo "<tr>
										<td>$sarjalaskk. $sarjarow[sarjanumero]</td><td>$sarjarow[ostohinta]</td>
										<td>
										<input type='hidden' name='sarjanumero_kaikki[$tuoterow[tptunnus]][]' value='$sarjarow[tunnus]'>
										<input type='checkbox' name='sarjanumero_valitut[$tuoterow[tptunnus]][]' value='$sarjarow[tunnus]' $chk>
										</td></tr>";
										
								$sarjalaskk++;
							}
							
							echo "<tr><td colspan='2'>".t("Ruksaa kaikki")."</th><td align='center'><input type='checkbox' onclick='toggleAll(this, \"sarjanumero_valitut[$tuoterow[tptunnus]][]\");'></td></tr>";				
							echo "</table>";
						}
						echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=$tuoterow[tuoteno]&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//alku=$alku'>".t("Uusi sarjanumero")."</a>";
					}
					elseif ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F") {	
						if (mysql_num_rows($sarjares) > 0) {
							echo "<br><table>";
							
							$sarjalaskk = 1;
							
							while($sarjarow = mysql_fetch_array($sarjares)) {
								echo "<tr><td>$sarjalaskk. $sarjarow[sarjanumero]</td>
										<td>$sarjarow[era_kpl] ".t("KPL")."</td>
										<td>
										<input type='hidden' 		name='eranumero_kaikki[$tuoterow[tptunnus]][$sarjarow[tunnus]]' 	value='$sarjarow[tunnus]'>
										<input type='text' size='5' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' 	value='$sarjarow[era_kpl]'>
										</td></tr>";
																					
								$sarjalaskk++;
							}
							
							echo "</table>";
						}
						echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=$tuoterow[tuoteno]&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//alku=$alku'>".t("Uusi eränumero")."</a>";
					}
					
					echo "</td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";
					echo "<input type='hidden' name='tuote[$tuoterow[tptunnus]]' value='$tuoterow[tuoteno]#$tuoterow[hyllyalue]#$tuoterow[hyllynro]#$tuoterow[hyllyvali]#$tuoterow[hyllytaso]'>";
					echo "<td valign='top'><input type='text' size='7' name='maara[$tuoterow[tptunnus]]' value='".$maara[$tuoterow["tptunnus"]]."'></td>";
					echo "</tr>";

				}
				elseif ($tuoterow["inventointilista_aika"] == '0000-00-00 00:00:00' and $tuoterow["inventointilista"] == $lista) {

					//jos invauserohälytys on triggeröity
					$viesti = "";
					$pv = date("Y-m-d")." 00:00:00";

					$query = "	SELECT abs(kpl) kpl
								FROM tapahtuma
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tuoterow[tuoteno]'
								and laadittu >= '$pv'
								and laji='Inventointi'
								and kpl <> 0
								ORDER BY tunnus desc
								LIMIT 1";
					$tapresult = mysql_query($query) or pupe_error($query);
					$taptrow = mysql_fetch_array ($tapresult);

					if ($taptrow["kpl"] > 10) {
						$viesti = t("HUOM: Tuotteen saldo muuttui yli 10 kappaletta! Tarkista inventointi!");
					}

					echo "<tr>";
					echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']);
					
					if ($tuoterow["sarjanumeroseuranta"] == "S") {
						if (mysql_num_rows($sarjares) > 0) {
							echo "<br><table>";
							
							while($sarjarow = mysql_fetch_array($sarjares)) {							
								echo "<tr><td>$sarjarow[sarjanumero]</td><td>$sarjarow[ostohinta]</td></tr>";
							}
							
							echo "</table>";
						}
					}
					
					echo "</td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";

					if ($viesti == '') {
						echo "<td valign='top' class='green'>".t("Tuote on inventoitu!");
					}
					else {
						echo "<td valign='top'><font class='error'>$viesti</font>";
					}
					echo "</td>";
					echo "</tr>";
				}
				else {
					echo "<tr>";
					echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys'])." </td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";
					echo "<td valign='top'>".sprintf(t("Tätä tuotetta inventoidaan listalla %s. Inventointi estetty"), $tuoterow['inventointilista']).".</td>";
					echo "</tr>";
				}
			}

			echo "</table>";

			echo "<br><font class='message'>".t("Syötä inventointiselite:")."</font><br>";
			echo "<input type='text' size='50' name='lisaselite' value='$lisaselite'><br><br>";

			if (mysql_num_rows($saldoresult) == 18) {
				echo "<input type='submit' name='next' value='".t("Inventoi/Seuraava sivu")."'>";
				//echo "<input type='submit' name='prev' value='".t("Inventoi/Edellinen sivu")."'> ";
			}
			else {
				echo "<input type='submit' name='valmis' value='".t("Inventoi/Valmis")."'>";
			}

			echo "</form>";
		}
	}

	if ($tee == 'MITATOI') {
		$query = "	UPDATE tuotepaikat
					SET inventointilista_aika = '0000-00-00 00:00:00'
					WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
					and inventointilista = '$lista'";
		$result = mysql_query($query) or pupe_error($query);

		echo t("Inventointilista")." $lista ".t("kuitattu pois")."!<br>";

		$lista 	= "";
		$tee 	= "";
	}


	if ($tee == '') {

		$formi  = "inve";
		$kentta = "tuoteno";

		echo "<form name='inve' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='INVENTOI'>";

		echo "<br><table>";
		echo "<tr><th>".t("Tuotenumero:")."</th><td><input type='text' size='25' name='tuoteno'></td></tr>";
		echo "<tr><th>".t("Inventointilistan numero:")."</th><td><input type='text' size='25' name='lista'></td><td><input type='Submit' value='".t("Valitse")."'></td></tr>";

		echo "</form>";
		echo "</table>";
		echo "<br><br>";

		echo "<form method='post' action='$PHP_SELF' enctype='multipart/form-data'>
				<input type='hidden' name='tee' value='FILE'>

				<font class='message'>".t("Inventoi tiedostosta").":</font><br>
				<table border='0' cellpadding='3' cellspacing='2'>
				<tr><th colspan='4'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
				<tr>";

		echo "	<td>".t("Tuoteno")."</td><td>".t("Hyllyalue-Hyllynro-Hyllyväli-Hyllytaso")."</td><td>".t("Määrä")."</td><td>".t("Selite")."</td>";
		echo "	</tr>
				<tr><th>".t("Valitse tiedosto").":</th>
				<td colspan='3'><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Inventoi")."'></td>
				</tr>
				</form>
				</table>";
		echo "<br><br><table>";
		echo "<tr><th colspan='3'>".t("Avoimet inventointilistat").":</th></tr>";

		//haetaan inventointilista numero tässä vaiheessa
		$query = "	SELECT distinct inventointilista, inventointilista_aika
					FROM tuotepaikat
					WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
					and inventointilista > 0
					and inventointilista_aika > '0000-00-00 00:00:00'
					ORDER BY inventointilista";
		$result = mysql_query($query) or pupe_error($query);

		while($lrow = mysql_fetch_array($result)) {

			echo "<tr>
					<td>$lrow[inventointilista]</td>
					<td><a href='$PHP_SELF?tee=INVENTOI&lista=$lrow[inventointilista]'>".t("Inventoi")."</a></td>
					<td><a href='$PHP_SELF?tee=MITATOI&lista=$lrow[inventointilista]'>".t("Mitätöi lista")."</a></td>
				</tr>";
		}
		echo "</table>";

	}

	// lukitaan tableja
	$query = "unlock tables";
	$result = mysql_query($query) or pupe_error($query);

	require ("inc/footer.inc");

?>