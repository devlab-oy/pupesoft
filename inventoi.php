<?php

	require ("inc/parametrit.inc");
	
	if ($mobiili != "YES") {
		echo "<font class='head'>".t("Inventointi")."</font><hr>";
	}
	
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

	if ($rivimaara == '') {
		$rivimaara = '18';
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
					if (is_array($sarjanumero_kaikki[$i]) and substr($kpl,0,1) != '+' and substr($kpl,0,1) != '-' and $hyllyssa[$i] < $kpl) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroita ei voi lisätä kuin relatiivisella määrällä")."! (+1)</font><br>";
						$virhe = 1;
					}
					elseif (substr($kpl,0,1) == '+' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) substr($kpl,1)) {
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

							if (substr($kpl,0,1) == '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != substr($kpl,1)) {
								echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
								$virhe = 1;
							}
							elseif (substr($kpl,0,1) == '-' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != substr($kpl,1)) {
								echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
								$virhe = 1;
							}
							elseif(substr($kpl,0,1) != '-' and substr($kpl,0,1) != '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != $kpl) {
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
							$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) == 1) {

								// katotaan onko tuotteella jo oletuspaikka
								$query = "SELECT * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and oletus!=''";
								$result = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($result) > 0) {
									$oletus = "";
								}
								else {
									$oletus = "X";
								}

								$query = "INSERT into tuotepaikat set
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
											hyllyalue 	= '$hyllyalue',
											hyllynro 	= '$hyllynro',
											hyllyvali 	= '$hyllyvali',
											hyllytaso 	= '$hyllytaso',
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

						if (($lista != '' and $row["inventointilista_aika"] != "0000-00-00 00:00:00") or ($lista == '' and $row["inventointilista_aika"] == "0000-00-00 00:00:00") or ($lista != '' and $tee2 == 'KORJAA')) {
							//jos invataan raportin avulla niin tehdään päivämäärätsekit ja lasketaan saldo takautuvasti
							$saldomuutos = 0;
							$kerattymuut = 0;

							if ($row["sarjanumeroseuranta"] != "S" and $row["inventointilista_aika"] != "0000-00-00 00:00:00") {
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

								if ($hylrow['keratty'] != 0) {
									$kerattymuut = $hylrow['keratty'];
								}
							}
							elseif ($row["sarjanumeroseuranta"] != "S") {
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

								if ($hylrow['keratty'] != 0) {
									$kerattymuut = $hylrow['keratty'];
								}
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
								$kpl = $kpl + $kerattymuut + $saldomuutos;
								$skp = 0;
							}

							$nykyinensaldo = $row['saldo'];
							$erotus = $kpl - $row['saldo'];
							$cursaldo = $nykyinensaldo + $erotus;

							//triggeröidään erohälytys
							if (abs($erotus) > 10 and $lista != '') {
								$virhe = 1;
							}

							//echo "Tuoteno: $tuoteno Saldomuutos: $saldomuutos Kerätty: $kerattymuut Syötetty: $kpl Hyllyssä: $hyllyssa Nykyinen: $nykyinensaldo Erotus: $erotus<br>";

							///* Inventointipoikkeama prosenteissa *///
							if ($nykyinensaldo != 0) {
								$poikkeama = ($erotus/$nykyinensaldo)*100;
							}
							///* Tehdään jonkinlainen arvaus jos saldo on nolla *///
							else {
								$poikkeama = ($erotus/1)*100;
							}

							// Lasketaan varastonarvon muutos
							if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "G") {

								$varvo_ennen = 0;
								$varvo_jalke = 0;
								$varvo_muuto = 0;

								// ollaan syötetty absoluuttinen määrä
								if ((float) $skp == 0) {
									// Ei ruksatut sarjanumerot poistetaan
									foreach ($sarjanumero_kaikki[$i] as $snro_tun) {
										$varvo_ennen += sarjanumeron_ostohinta("tunnus", $snro_tun);
									}

									foreach ($sarjanumero_valitut[$i] as $snro_tun) {
										$varvo_jalke += sarjanumeron_ostohinta("tunnus", $snro_tun);
									}

									$summa = round($varvo_jalke - $varvo_ennen, 6);
								}
								elseif ((float) $skp != 0) {
									// ollaan syötetty relatiivinen määrä
									foreach ($sarjanumero_valitut[$i] as $snro_tun) {
										$varvo_muuto += sarjanumeron_ostohinta("tunnus", $snro_tun);
									}

									// ruksatut on varastonmuutos
									if ($skp < 0) {
										$summa = round($varvo_muuto * -1, 6);
									}
									else {
										$summa = round($varvo_muuto, 6);
									}
								}
								else {
									echo "<font class='error'>".t("VIRHE: Tänne ei pitäisi päästä")."! $tuoteno $kpl</font><br>";
									exit;
								}
								
								$row['kehahin'] = round(abs($summa) / abs($erotus), 6);
							}
							else {
								if 		($row['epakurantti100pvm'] != '0000-00-00') $row['kehahin'] = 0;
								elseif 	($row['epakurantti75pvm']  != '0000-00-00') $row['kehahin'] = round($row['kehahin'] * 0.25, 6);
								elseif 	($row['epakurantti50pvm']  != '0000-00-00') $row['kehahin'] = round($row['kehahin'] * 0.5, 6);
								elseif	($row['epakurantti25pvm']  != '0000-00-00') $row['kehahin'] = round($row['kehahin'] * 0.75, 6);

								$summa = round($erotus * $row['kehahin'],2);
							}
							
							// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
							if ($summa != '' and abs($summa) > 0) {
								$ylitettava_summa_chk = $cursaldo*$summa;

								if (abs($ylitettava_summa_chk) > 9999999999.99) {
									echo "<font class='error'>".t("VIRHE: liian iso loppusumma")."!<br/>",t("Tuote"),": $tuoteno ",t("lopullinen kappalemäärä")," $kpl (",t("loppusumma"),": $ylitettava_summa_chk)</font><br>";
									$virhe = 1;
									break;
								}
							}
							
							if ($tee2 == "") {
								///* Tehdään tapahtuma *///
								$query = "	INSERT into tapahtuma set
											yhtio   	= '$kukarow[yhtio]',
											tuoteno 	= '$row[tuoteno]',
											laji    	= 'Inventointi',
											kpl     	= '$erotus',
											kplhinta	= '$row[kehahin]',
											hinta   	= '$row[kehahin]',
											hyllyalue	= '$hyllyalue',
											hyllynro 	= '$hyllynro',
											hyllyvali 	= '$hyllyvali',
											hyllytaso 	= '$hyllytaso',
											selite  	= ";

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

								// Päivitetään tuotepaikka
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
											inventointilista_aika	= '0000-00-00 00:00:00',
											muuttaja			 	= '$kukarow[kuka]',
											muutospvm			 	= now()
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
															muutospvm			 = now(),
															inventointitunnus	 = $tapahtumaid
															WHERE yhtio	= '$kukarow[yhtio]'
															and tunnus = $snro_tun";
												$sarjares = mysql_query($query) or pupe_error($query);
											}
										}
									}
									elseif ((float) $skp < 0) {
										// Muutetaan $skp-verrran miinus etumerkeillä poistetaan
										foreach ($sarjanumero_valitut[$i] as $snro_tun) {
											$query = "	UPDATE sarjanumeroseuranta
														SET myyntirivitunnus = '-1',
														siirtorivitunnus 	 = '-1',
														muuttaja			 = '$kukarow[kuka]',
														muutospvm			 = now(),
														inventointitunnus	 = $tapahtumaid
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
										// Muutetaan $skp-verrran miinus etumerkeillä poistetaan
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
							elseif ($tee2 == "KORJAA" and $row["sarjanumeroseuranta"] == "") {
								
								// Pitää löytää alkuperäinen tapahtuma ja korjata sitä.
								$query = "	SELECT tunnus, kpl, kplhinta, laadittu, left(laadittu,10) as laadittuleft
											FROM tapahtuma
											WHERE yhtio   	= '$kukarow[yhtio]'
											and tuoteno 	= '$row[tuoteno]'
											and laji    	= 'Inventointi'
											and hyllyalue	= '$hyllyalue'
											and hyllynro 	= '$hyllynro'
											and hyllyvali 	= '$hyllyvali'
											and hyllytaso 	= '$hyllytaso'
											ORDER BY tunnus DESC
											LIMIT 1";
								$korjresult = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($korjresult) != 1) {
									die ("<font class='error'><br>".t("Korjattavaa tapahtumaa ei löydetty, ei uskalleta jatkaa")."!</font>");
								}
								
								$korjrow = mysql_fetch_array($korjresult);
								
								// otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
								$tapahtumaid = $korjrow["tunnus"];
								
								$query = "	SELECT sum(kpl) kpl
											FROM tapahtuma
											WHERE yhtio   	= '$kukarow[yhtio]'
											and tuoteno 	= '$row[tuoteno]'
											and hyllyalue	= '$hyllyalue'
											and hyllynro 	= '$hyllynro'
											and hyllyvali 	= '$hyllyvali'
											and hyllytaso 	= '$hyllytaso'
											and laadittu 	> '$korjrow[laadittu]'";
								$muutosresult = mysql_query($query) or pupe_error($query);
								
								$muutosrow = mysql_fetch_array($muutosresult);
								
								$query = "	SELECT tunnus FROM lasku WHERE
											yhtio     	= '$kukarow[yhtio]'
											and tila	= 'X'
											and viite	= '$tapahtumaid'
											and tapvm	 = '$korjrow[laadittuleft]'";

								$korj2result = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($korj2result) != 1) {
									die ("<font class='error'><br>".t("Korjattavaa tositetta ei löydetty, ei uskalleta jatkaa")."!</font>");
								}
								
								$korj2row = mysql_fetch_array($korj2result);
								
								$laskuid = $korj2row["tunnus"];
								
								$query = "SELECT laadittu, tapvm
											FROM tiliointi
											WHERE yhtio  = '$kukarow[yhtio]'
											AND ltunnus  = '$laskuid'
											ORDER BY tunnus LIMIT 1"; 
								$tilidateresult = mysql_query($query) or pupe_error($query);
								$tilidaterow = mysql_fetch_array($tilidateresult);
								
								if ($tilidaterow["tapvm"] > $yhtiorow["tilikausi_alku"]) {
								
									$erotus = $erotus+$muutosrow["kpl"];
								
									//echo "# uuserotus = $korjrow[kpl]+$erotus | nykyinensaldo = $row[saldo]-$muutosrow[kpl]-$korjrow[kpl]<br>";
								
								
									$uuserotus = $korjrow["kpl"]+$erotus;
								
									$nykyinensaldo = $row["saldo"]-$muutosrow["kpl"]-$korjrow["kpl"];
								
									///* Inventointipoikkeama prosenteissa *///
									if ($nykyinensaldo != 0) {
										$poikkeama = ($uuserotus/$nykyinensaldo)*100;
									}
									///* Tehdään jonkinlainen arvaus jos saldo on nolla *///
									else {
										$poikkeama = ($uuserotus/1)*100;
									}
								
									//UPDATE tapahtuma set kpl = '-13', selite = 'Saldoa (16) paikalla X00-0-0-0 vähennettiin 13 kappaleella. Saldo nyt 3. KORJATTU 2008-09-30 04:37:51', WHERE yhtio = 'allr' and tunnus = '6656422'
								
									$lisaselite .= " (KORJATTU ".date("Y-m-d H:i:s")." $kukarow[kuka])";
								
									///* Tehdään tapahtuma *///
									$query = "	UPDATE tapahtuma set
												kpl     	= '$uuserotus',
												selite  	= ";

									if ($uuserotus > 0) {
										$query .= " '".t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("lisättiin")." $uuserotus ".t("kappaleella. Saldo nyt")." $cursaldo. $lisaselite'";
									}
									elseif ($uuserotus < 0) {
										$query .= " '".t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("vähennettiin")." ".abs($uuserotus)." ".t("kappaleella. Saldo nyt")." $cursaldo. $lisaselite'";
									}
									else {
										$query .= " '".t("Saldo")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("täsmäsi.")." $lisaselite'";
									}

									$query .= "	WHERE yhtio	= '$kukarow[yhtio]' and tunnus = '$korjrow[tunnus]'";
								
									//die($query);
								
									$result = mysql_query($query) or pupe_error($query);

									// Päivitetään tuotepaikka
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

									$query .= " inventointipoikkeama 	= '$poikkeama',
												inventointilista_aika	= '0000-00-00 00:00:00',
												muuttaja			 	= '$kukarow[kuka]',
												muutospvm			 	= now()
												WHERE yhtio		= '$kukarow[yhtio]'
												and tuoteno		= '$tuoteno'
												and hyllyalue	= '$hyllyalue'
												and hyllynro	= '$hyllynro'
												and hyllyvali	= '$hyllyvali'
												and hyllytaso	= '$hyllytaso'";
									$result = mysql_query($query) or pupe_error($query);
								
									$summa = $uuserotus*$korjrow["kplhinta"];
								
									if (($summa <> 0) and (mysql_affected_rows() > 0)) {

									
									
									
										$query = "UPDATE tiliointi set
													korjattu 	 = '$kukarow[kuka]',
													korjausaika  = now()
													WHERE yhtio  = '$kukarow[yhtio]'
													and ltunnus  = '$laskuid'"; 
										$result = mysql_query($query) or pupe_error($query);

										$query = "INSERT into tiliointi set
													yhtio    = '$kukarow[yhtio]',
													ltunnus  = '$laskuid',
													tilino   = '$yhtiorow[varasto]',
													kustp    = '',
													tapvm    = '$tilidaterow[tapvm]',
													summa    = '$summa',
													vero     = '0',
													lukko    = '',
													selite   = 'Inventointi $row[tuoteno] $uuserotus kpl',
													laatija  = '$kukarow[kuka]',
													laadittu = '$tilidaterow[laadittu]'";
										$result = mysql_query($query) or pupe_error($query);
										
										$negsumma = $summa * -1;
										
										$query = "INSERT into tiliointi set
													yhtio    = '$kukarow[yhtio]',
													ltunnus  = '$laskuid',
													tilino   = '$yhtiorow[varastonmuutos]',
													kustp    = '',
													tapvm    = '$tilidaterow[tapvm]',
													summa    = '$negsumma',
													vero     = '0',
													lukko    = '',
													selite   = 'Inventointi $row[tuoteno] $uuserotus kpl',
													laatija  = '$kukarow[kuka]',
													laadittu = '$tilidaterow[laadittu]'";
										$result = mysql_query($query) or pupe_error($query);
									}
								}
								else {
									echo "<font class='message'>".t("Tuote")."   $tuoteno $hyllyalue $hyllynro $hyllyvali $hyllytaso ".t("Inventointia ei voida korjata koska se on tehty lukitulla tilikaudella")."!</font><br>";
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

		if ($virhe == 0 and $mobiili != "YES") {
			if(isset($prev)) {
				$alku = $alku-$rivimaara;
				$tee = "INVENTOI";
			}
			elseif(isset($next)) {
				$alku = $alku+$rivimaara;
				$tee = "INVENTOI";
			}
			elseif(isset($valmis)) {
				$tee = "";
				$tmp_tuoteno = "";

				if ($lista == '' and $filusta == '') {
					$tmp_tuoteno = $tuoteno;
				}
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
		elseif ($mobiili == "YES") {
			$tee = "MOBIILI";
		}		
		else {
			$tee = "INVENTOI";
		}
	}

	if ($tee == 'INVENTOI') {

		if (isset($tmp_tuoteno) and $tmp_tuoteno != '') {
			$tuoteno = $tmp_tuoteno;
		}

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

			$loppu = "18";

			if ($rivimaara != "18" and $rivimaara != '') {
				$loppu = $rivimaara;
			}

			$order = "sorttauskentta, tuoteno";

			if ($jarjestys == 'tuoteno') {
				$order = "tuoteno, sorttauskentta";
			}
			
			$where = "";
			
			if ($tee2 == 'KORJAA') {
				$where = " and inventointilista_aika = '0000-00-00 00:00:00' ";
				// toistaseks ei voi sarjanumeroita korjata tätä kautta.
				$joinon = " and tuote.sarjanumeroseuranta = '' ";
			}
			
			$query = "	SELECT $select
						FROM tuotepaikat USE INDEX (yhtio_inventointilista)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno and tuote.ei_saldoa = '' $joinon)
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and tuotepaikat.inventointilista = '$lista'
						$where
						ORDER BY $order
						LIMIT $alku, $loppu";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Listaa")." '$lista' ".t("ei löydy, tai se on jo inventoitu")."!</font><br><br>";
				$tee='';
				$tee2='';
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

			$sel1rivi=$sel18rivi=$sel180rivi="";

			if ($rivimaara == '1') {
				$sel1rivi = "SELECTED";
			}
			elseif ($rivimaara == '18') {
				$sel18rivi = "SELECTED";
			}
			else {
				$sel180rivi = "SELECTED";
			}

			$seljarj1=$seljarj2="";

			if ($jarjestys == '') {
				$seljarj1 = "SELECTED";
			}
			else {
				$seljarj2 = "SELECTED";
			}
			
			if ($lista != "") {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<select name='rivimaara' onchange='submit()'>";
				echo "<option value='180' $sel180rivi>".t("Näytetään 180 riviä")."</option>";
				echo "<option value='18' $sel18rivi>".t("Näytetään 18 riviä")."</option>";
				echo "<option value='1' $sel1rivi>".t("Näytetään 1 rivi")."</option>";
				echo "</select>";
				echo "<select name='jarjestys' onchange='submit()'>";
				echo "<option value='' $seljarj1>".t("Tuotepaikkajärjestys")."</option>";
				echo "<option value='tuoteno' $seljarj2>".t("Tuotenumerojärjestys")."</option>";
				echo "</select>";
				echo "<input type='hidden' name='tee' value='INVENTOI'>";
				echo "<input type='hidden' name='tee2' value='$tee2'>";
				echo "<input type='hidden' name='lista' value='$lista'>";
				echo "<input type='hidden' name='alku' value='$alku'>";
				echo "</form>";
			}
			

			echo "<form name='inve' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='tee2' value='$tee2'>";
			echo "<input type='hidden' name='lista' value='$lista'>";
			echo "<input type='hidden' name='alku' value='$alku'>";
			echo "<input type='hidden' name='rivimaara' value='$rivimaara'>";
			echo "<input type='hidden' name='jarjestys' value='$jarjestys'>";

			echo "<table>";
			echo "<tr><td colspan='7' class='back'>".t("Syötä joko hyllyssä oleva määrä, tai lisättävä määrä + etuliitteellä, tai vähennettävä määrä - etuliitteellä")."</td></tr>";

			echo "<tr>";
			echo "<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th><th>".t("Varastosaldo")."</th><th>".t("Ennpois")."/".t("Kerätty")."</th><th>".t("Hyllyssä")."</th><th>".t("Laskettu hyllyssä")."</th>";
			echo "</tr>";

			$rivilask = 0;

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

				if ($tuoterow["sarjanumeroseuranta"] != "") {
					$query = "	SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus, 
								round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta, era_kpl, tilausrivi_osto.yksikko
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
								and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and (tilausrivi_osto.laskutettuaika != '0000-00-00' or tilausrivi_osto.laskutettuaika is null))
								ORDER BY sarjanumero";
					$sarjares = mysql_query($query) or pupe_error($query);
				}

				if (($tuoterow["inventointilista_aika"] == '0000-00-00 00:00:00' and $lista == '') or ($tuoterow["inventointilista"] == $lista and $tuoterow["inventointilista_aika"] != '0000-00-00 00:00:00') or ($tee2=='KORJAA' and $tuoterow["inventointilista"] == $lista)) {

					echo "<tr>";
					echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']);

					if ($tuoterow["sarjanumeroseuranta"] == "S") {
						if (mysql_num_rows($sarjares) > 0) {
							echo "<br><table width='100%'>";

							$sarjalaskk = 1;

							while($sarjarow = mysql_fetch_array($sarjares)) {
								if ($sarjanumero[$tuoterow["tptunnus"]][$sarjarow["tunnus"]] != '') {
									$chk = "CHECKED";
								}
								else {
									$chk = "";
								}

								echo "<tr>
										<td>$sarjalaskk. $sarjarow[sarjanumero]</td><td align='right'>".sprintf("%.02f",sarjanumeron_ostohinta("tunnus", $sarjarow["tunnus"]))." 
										<input type='hidden' name='sarjanumero_kaikki[$tuoterow[tptunnus]][]' value='$sarjarow[tunnus]'>
										<input type='checkbox' name='sarjanumero_valitut[$tuoterow[tptunnus]][]' value='$sarjarow[tunnus]' $chk>
										</td></tr>";

								$sarjalaskk++;
							}

							echo "<tr><td>".t("Ruksaa kaikki")."</th><td align='right'><input type='checkbox' onclick='toggleAll(this, \"sarjanumero_valitut[$tuoterow[tptunnus]][]\");'></td></tr>";
							echo "</table>";
						}
						echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//alku=$alku'>".t("Uusi sarjanumero")."</a>";
					}
					elseif ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F" or $tuoterow["sarjanumeroseuranta"] == "G") {
						if (mysql_num_rows($sarjares) > 0) {
							echo "<br><table>";

							$sarjalaskk = 1;

							while($sarjarow = mysql_fetch_array($sarjares)) {
								echo "<tr><td>$sarjalaskk. $sarjarow[sarjanumero]</td>
										<td>$sarjarow[era_kpl] ".t_avainsana("Y", "", "and avainsana.selite='$sarjarow[yksikko]'", "", "", "selite")."</td>
										<td>
										<input type='hidden' 		name='eranumero_kaikki[$tuoterow[tptunnus]][$sarjarow[tunnus]]' 	value='$sarjarow[tunnus]'>
										<input type='text' size='5' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' 	value='$sarjarow[era_kpl]'>
										</td></tr>";

								$sarjalaskk++;
							}

							echo "</table>";
						}
						echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//alku=$alku'>".t("Uusi eränumero")."</a>";
					}

					echo "</td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>";

					if ($tuoterow["sarjanumeroseuranta"] != "S") {
						echo "<td valign='top'>$tuoterow[saldo]</td><td valign='top'>$hylrow[ennpois]/$hylrow[keratty]</td><td valign='top'>".$hyllyssa."</td>";
					}
					else {
						echo "<td valign='top'>$tuoterow[saldo]</td><td valign='top'></td><td valign='top'>$tuoterow[saldo]</td>";
					}

					echo "<input type='hidden' name='hyllyssa[$tuoterow[tptunnus]]' value='$tuoterow[saldo]'>";
					echo "<input type='hidden' name='tuote[$tuoterow[tptunnus]]' value='$tuoterow[tuoteno]#$tuoterow[hyllyalue]#$tuoterow[hyllynro]#$tuoterow[hyllyvali]#$tuoterow[hyllytaso]'>";
					echo "<td valign='top'><input type='text' size='7' name='maara[$tuoterow[tptunnus]]' id='maara_$tuoterow[tptunnus]' value='".$maara[$tuoterow["tptunnus"]]."'></td>";
					echo "</tr>";

					if ($rivilask == 0) {
						echo "<script LANGUAGE='JavaScript'>document.getElementById('maara_$tuoterow[tptunnus]').focus();</script>";
						$kentta = "";
						$rivilask++;
					}

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

			if (mysql_num_rows($saldoresult) == $rivimaara) {
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

		if (isset($tmp_tuoteno) and $tmp_tuoteno != '') {
			$query = "	SELECT tuoteno
						FROM tuote use index (tuoteno_index)
						JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.tuoteno		< '$tmp_tuoteno'
						and tuote.ei_saldoa		= ''
						ORDER BY tuoteno desc
						LIMIT 1";
			$noperes = mysql_query($query) or pupe_error($query);
			$noperow = mysql_fetch_array($noperes);

			echo "<table>";
			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='INVENTOI'>";
			echo "<input type='hidden' name='tee2' value='$tee2'>";
			echo "<input type='hidden' name='seuraava_tuote' value='nope'>";
			echo "<input type='hidden' name='tuoteno' value='".$noperow[tuoteno]."'>";
			echo "<tr><td><input type='submit' value='".t("Edellinen tuote")."'></td>";
			echo "</form>";

			$query = "	SELECT tuoteno
						FROM tuote use index (tuoteno_index)
						JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.tuoteno		> '$tmp_tuoteno'
						and tuote.ei_saldoa		= ''
						ORDER BY tuoteno
						LIMIT 1";
			$yesres = mysql_query($query) or pupe_error($query);
			$yesrow = mysql_fetch_array($yesres);

			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='INVENTOI'>";
			echo "<input type='hidden' name='tee2' value='$tee2'>";
			echo "<input type='hidden' name='seuraava_tuote' value='yes'>";
			echo "<input type='hidden' name='tuoteno' value='".$yesrow[tuoteno]."'>";
			echo "<td><input type='submit' value='".t("Seuraava tuote")."'></td></tr>";
			echo "</form>";
			echo "</table>";
		}

		echo "<form name='inve' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='INVENTOI'>";
		echo "<input type='hidden' name='tee2' value='$tee2'>";

		echo "<br><table>";
		echo "<tr><th>".t("Tuotenumero:")."</th><td><input type='text' size='25' name='tuoteno'></td></tr>";
		echo "<tr><th>".t("Inventointilistan numero:")."</th><td><input type='text' size='25' name='lista'></td><td><input type='Submit' value='".t("Valitse")."'></td></tr>";

		echo "</form>";
		echo "</table>";
		echo "<br><br>";

		echo "<form method='post' action='$PHP_SELF' enctype='multipart/form-data'>
				<input type='hidden' name='tee' value='FILE'>
				<input type='hidden' name='tee2' value='$tee2'>
				<input type='hidden' name='filusta' value='yep'>

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

		echo "<font class='message'>".t("Avoimet inventointilistat").":</font><br>";
		echo "<tr><th>".t("Nro")."</th>
		<th>".t("Luontiaika")."</th>";
		// Saako päivittää
		if ($oikeurow["paivitys"] == '1') {
			echo "<th colspan='3'></th>";
		}
		else {
			echo "<th colspan='2'></th>";			
		}
		echo "</tr>";

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
					<td>".tv1dateconv($lrow["inventointilista_aika"])."</td>
					<td>
						<form action='inventoi.php' method='post'>
						<input type='hidden' name='tee' value='INVENTOI'>
						<input type='hidden' name='lista' value='$lrow[inventointilista]'>
						<input type='submit' value='".t("Inventoi")."'>
						</form>
					</td>
					<td>
						<form action='inventoi.php' method='post'>
						<input type='hidden' name='tee' value='MITATOI'>
						<input type='hidden' name='lista' value='$lrow[inventointilista]'>
						<input type='submit' value='".t("Mitätöi lista")."'>
						</form>
					</td>";
					// Saako päivittää
					if ($oikeurow["paivitys"] == '1') {
					
						//katotaan löytyykö inventoituja rivejä
						$query = "	SELECT inventointilista
									FROM tuotepaikat
									WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
									and inventointilista = '$lrow[inventointilista]'
									and inventointilista_aika = '0000-00-00 00:00:00'
									ORDER BY inventointilista";
						$resultchk = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($resultchk) > 0) {
								echo "<td>
										<form action='inventoi.php' method='post'>
										<input type='hidden' name='tee' value='INVENTOI'>
										<input type='hidden' name='tee2' value='KORJAA'>
										<input type='hidden' name='lista' value='$lrow[inventointilista]'>
										<input type='submit' value='".t("Korjaa")."'>
										</form>
									</td>";
						}
					}

			echo "</tr>";
		}
		echo "</table>";

	}

	// lukitaan tableja
	$query = "unlock tables";
	$result = mysql_query($query) or pupe_error($query);
	
	if ($mobiili != "YES") {
		require ("inc/footer.inc");
	}

?>
