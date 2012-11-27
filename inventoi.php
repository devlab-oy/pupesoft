<?php
	if (strpos($_SERVER['SCRIPT_NAME'], "inventoi.php") !== FALSE) {
			require ("inc/parametrit.inc");
		}

	if (!isset($fileesta))			$fileesta = "";
	if (!isset($filusta))			$filusta = "";
	if (!isset($livesearch_tee))	$livesearch_tee = "";
	if (!isset($mobiili))			$mobiili = "";

	if ($livesearch_tee == "TUOTEHAKU") {
		livesearch_tuotehaku();
		exit;
	}

	// Enaboidaan ajax kikkare
	enable_ajax();

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
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$name	= strtoupper($path_parts['filename']);
			$ext	= strtoupper($path_parts['extension']);

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
			}

			$retval = tarkasta_liite("userfile", array("XLSX","XLS","ODS","SLK","XML","GNUMERIC","CSV","TXT","DATAIMPORT"));

			if ($retval !== TRUE) {
				die ("<font class='error'><br>".t("Väärä tiedostomuoto")."!</font>");
			}

			$excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

			$tuote = array();
			$maara = array();
			$selis = array();
			$lajis = array();

			for ($excei = 0; $excei < count($excelrivit); $excei++) {
				// luetaan rivi tiedostosta..
				$tuo		= mysql_real_escape_string(trim($excelrivit[$excei][0]));
				$hyl		= mysql_real_escape_string(trim($excelrivit[$excei][1]));
				$maa		= str_replace(",", ".", trim($excelrivit[$excei][2]));
				$lisaselite	= mysql_real_escape_string(trim($excelrivit[$excei][3]));
				$inven_laji = "";

				if (strpos($lisaselite, "/") !== FALSE) {
					list($inven_laji, $lisaselite) = explode("/", $lisaselite);
					$inven_laji = trim($inven_laji);
					$lisaselite = trim($lisaselite);
				}

				if ($tuo != '' and $hyl != '' and $maa != '') {
					$hylp = explode("-", $hyl);

					$tuote[] = $tuo."###".$hylp[0]."###".$hylp[1]."###".$hylp[2]."###".$hylp[3];
					$maara[] = $maa;
					$selis[] = $lisaselite;
					$lajis[] = $inven_laji;
				}
			}

			if (count($tuote) > 0) {
				$tee 		= "VALMIS";
				$valmis 	= "OK";
				$fileesta 	= "ON";
			}
		}
	}

	// lukitaan tableja
	$query = "	LOCK TABLES tuotepaikat WRITE,
				tapahtuma WRITE,
				lasku WRITE,
				tiliointi WRITE,
				sanakirja WRITE,
				tuote READ,
				tilausrivi WRITE,
				tuotteen_avainsanat READ,
				sarjanumeroseuranta WRITE,
				sarjanumeroseuranta_arvomuutos READ,
				tilausrivi as tilausrivi_myynti READ,
				tilausrivi as tilausrivi_osto READ,
				tuotepaikat as tt READ,
				avainsana as avainsana_kieli READ,
				avainsana READ,
				tili READ,
				asiakas READ";
	$result = pupe_query($query);

	//tuotteen varastostatus
	if ($tee == 'VALMIS') {

		$virhe = 0;

		// Inventoidaan EAN-koodilla
		if (isset($tuoteno_ean) and $tuoteno_ean == "EAN") {
			$tuoteno_ean_kentta = "eankoodi";
		}
		else {
			$tuoteno_ean_kentta = "tuoteno";
		}

		if (count($tuote) > 0) {
			foreach($tuote as $i => $tuotteet) {

				$tuotetiedot = explode("###", $tuotteet);

				//näitä muuttujia me tarvitaan
				$tuoteno 	= $tuotetiedot[0];
				$hyllyalue 	= $tuotetiedot[1];
				$hyllynro	= $tuotetiedot[2];
				$hyllyvali  = $tuotetiedot[3];
				$hyllytaso	= $tuotetiedot[4];
				$kpl		= str_replace(",", ".", $maara[$i]);
				$poikkeama  = 0;
				$skp		= 0;

				if ($fileesta == "ON") {
					$inven_laji = $lajis[$i];
					$lisaselite = $selis[$i];
				}

				if ($kpl != '' and is_numeric($kpl)) {

					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND $tuoteno_ean_kentta = '$tuoteno'";
					$tuote_res = pupe_query($query);
					$tuote_row = mysql_fetch_assoc($tuote_res);

					if (mysql_num_rows($tuote_res) != 1) {
						echo "<font class='error'>".t("VIRHE: Tuotetta ei löydy")."! ($tuoteno)</font><br>";
						$virhe = 1;
					}
					else {
						$tuoteno = $tuote_row["tuoteno"];
					}

					if ($tuote_row['sarjanumeroseuranta'] != '' and !is_array($sarjanumero_kaikki[$i]) and !is_array($eranumero_kaikki[$i]) and (substr($kpl,0,1) == '+' or substr($kpl,0,1) == '-' or (float) $kpl != 0)) {
						echo "<font class='error'>".t("VIRHE: Et valinnut yhtään sarja- tai eränumeroa").": $tuoteno!</font><br>";
						$virhe = 1;
					}

					// Jos lajit on käytössä niin myös selite on syötettävä
					if ($inven_laji != "" and trim($lisaselite) == "") {
						echo "<font class='error'>".t("VIRHE: Inventointiselite on syötettävä")."!: $tuoteno</font><br>";
						$virhe = 1;
					}

					// käydään kaikki ruudulla näkyvät läpi ja katsotaan onko joku niistä uusi
					$onko_uusia = 0;

					if (isset($sarjanumero_kaikki[$i])) {
						foreach ($sarjanumero_kaikki[$i] as $snro => $schk) {
							if ($sarjanumero_uudet[$i][$snro] == '0000-00-00') {
								$onko_uusia++;
							}
						}
					}

					// käydään kaikki valitut checkboxit läpi ja katsotaan onko joku niistä vanha
					$onko_vanhoja = 0;

					if (isset($sarjanumero_valitut[$i])) {
						foreach ($sarjanumero_valitut[$i] as $snro => $schk) {
							if ($sarjanumero_uudet[$i][$snro] != '0000-00-00') {
								$onko_vanhoja++;
							}
						}
					}

					if (in_array($tuote_row["sarjanumeroseuranta"], array("S","U","G")) and $onko_vanhoja > 0 and $onko_uusia > 0) {
						echo "<font class='error'>".t("VIRHE: Voit lisätä / poistaa vain uuden sarjanumeron")."!</font><br>";
						$virhe = 1;
					}

					//Sarjanumerot
					if (in_array($tuote_row["sarjanumeroseuranta"], array("S","U")) and is_array($sarjanumero_kaikki[$i]) and substr($kpl,0,1) != '+' and substr($kpl,0,1) != '-' and (int) $kpl!=count($sarjanumero_kaikki[$i]) and ($onko_uusia > 0 or $hyllyssa[$i] < $kpl)) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroita ei voi lisätä kuin relatiivisella määrällä")."! (+1)</font><br>";
						$virhe = 1;
					}
					elseif (in_array($tuote_row["sarjanumeroseuranta"], array("S","U")) and substr($kpl,0,1) == '+' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) substr($kpl,1)) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
						$virhe = 1;
					}
					elseif (substr($kpl,0,1) == '+' and is_array($sarjanumero_kaikki[$i]) and $onko_vanhoja > 0) {
						echo "<font class='error'>".t("VIRHE: Et voi lisätä kuin uusia sarjanumeroita relatiivisella määrällä")."! $tuoteno $kpl</font><br>";
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

					if (isset($eranumero_kaikki[$i]) and is_array($eranumero_kaikki[$i])) {
						if (is_array($eranumero_valitut[$i])) {

							$erasyotetyt = 0;

							foreach ($eranumero_valitut[$i] as $enro => $ekpl) {

								$erasyotetyt += $ekpl;
								if ($ekpl != '' and ($ekpl{0} == '+' or $ekpl{0} == '-' or !is_numeric($ekpl))) {
									echo "<font class='error'>".t("VIRHE: Erien määrät oltava absoluuttisia arvoja")."!</font><br>";
									$virhe = 1;
									break;
								}

								if (($kpl{0} == '+' or $kpl{0} == '-') and (int) $ekpl == 0 and $ekpl != '' and $onko_uusia == 0) {
									echo "<font class='error'>".t("VIRHE: Et voi nollata erää, jos olet syöttänyt relatiivisen määrän")."!</font><br>";
									$virhe = 1;
									break;
								}

								if ($eranumero_uudet[$i][$enro] == '0000-00-00') {
									$onko_uusia++;
								}
							}

							if (is_array($eranumero_kaikki[$i]) and substr($kpl,0,1) != '+' and substr($kpl,0,1) != '-' and ($onko_uusia > 0 or $hyllyssa[$i] < $erasyotetyt)) {
								echo "<font class='error'>".t("VIRHE: Eränumeroita ei voi lisätä kuin relatiivisella määrällä")."! (+1)</font><br>";
								$virhe = 1;
							}
							elseif (substr($kpl,0,1) == '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != substr($kpl,1)) {
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

					if ($fileesta == "ON" and $virhe == 1) {
						$virhe = 0;
						continue;
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
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 0 and $virhe != 1) {

						if ($lisaselite == "PERUSTA" or $fileesta == "ON") {
							// PERUSTETAAN tuotepaikka

							// katotaa löytyykö tuote
							$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
							$result = pupe_query($query);

							if (mysql_num_rows($result) == 1) {

								// katotaan onko tuotteella jo oletuspaikka
								$query = "SELECT * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and oletus!=''";
								$result = pupe_query($query);

								if (mysql_num_rows($result) > 0) {
									$oletus = "";
								}
								else {
									$oletus = "X";
								}

								lisaa_tuotepaikka($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Inventoidessa', $oletus);

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
								$result = pupe_query($query);

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

					if (mysql_num_rows($result) == 1 and $virhe != 1) {
						$row = mysql_fetch_assoc($result);

						if (($lista != '' and $row["inventointilista_aika"] != "0000-00-00 00:00:00") or ($lista == '' and $row["inventointilista_aika"] == "0000-00-00 00:00:00")) {
							//jos invataan raportin avulla niin tehdään päivämäärätsekit ja lasketaan saldo takautuvasti
							$saldomuutos = 0;
							$kerattymuut = 0;

							if ($row["sarjanumeroseuranta"] == "" and $row["inventointilista_aika"] != "0000-00-00 00:00:00") {
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
								$result = pupe_query($query);

								$trow = mysql_fetch_assoc ($result);

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
								$hylresult = pupe_query($query);
								$hylrow = mysql_fetch_assoc($hylresult);

								if ($hylrow['keratty'] != 0) {
									$kerattymuut = $hylrow['keratty'];
								}
							}
							elseif ($row["sarjanumeroseuranta"] == "") {
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
								$hylresult = pupe_query($query);
								$hylrow = mysql_fetch_assoc($hylresult);

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
							 	$virhe = 2;
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
							// S = Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo
							// T = Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo
							// U = Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo. Automaattinen sarjanumerointi
							// V = Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
							// E = Eränumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
							// F = Eränumeroseuranta parasta-ennen päivällä. Osto-Myynti / Keskihinta-varastonarvo
							// G = Eränumeroseuranta. Osto-Myynti / In-Out varastonarvo
							if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "G") {

								$varvo_ennen = 0;
								$varvo_jalke = 0;
								$varvo_muuto = 0;

								// ollaan syötetty absoluuttinen määrä
								if ((float) $skp == 0) {
									if ($row["sarjanumeroseuranta"] == "G") {
										foreach ($eranumero_kaikki[$i] as $enro_tun => $enro_arvo) {
											$varvo_ennen += (sarjanumeron_ostohinta("tunnus", $enro_tun) * $enro_arvo);
										}

										foreach ($eranumero_valitut[$i] as $enro_tun => $enro_arvo) {
											$varvo_jalke += (sarjanumeron_ostohinta("tunnus", $enro_tun) * $enro_arvo);
										}
									}
									else {
										// Ei ruksatut sarjanumerot poistetaan
										foreach ($sarjanumero_kaikki[$i] as $snro_tun => $snro_arvo) {
											$varvo_ennen += sarjanumeron_ostohinta("tunnus", $snro_tun);
										}

										foreach ($sarjanumero_valitut[$i] as $snro_tun => $snro_arvo) {
											$varvo_jalke += sarjanumeron_ostohinta("tunnus", $snro_tun);
										}
									}

									$summa = round($varvo_jalke - $varvo_ennen, 6);
								}
								// ollaan syötetty relatiivinen määrä
								elseif ((float) $skp != 0) {
									if ($row["sarjanumeroseuranta"] == "G") {
										foreach ($eranumero_valitut[$i] as $enro_tun => $enro_arvo) {
											// katsotaan varastonarvo vain, jos ollaan lisäämässä tai kyseessä on vanha tuote
											if ($eranumero_uudet[$i][$enro_tun] != '0000-00-00' or $skp > 0) {
												$varvo_muuto += (sarjanumeron_ostohinta("tunnus", $enro_tun) * $enro_arvo);
											}
											else {
												// ollaan poistamatta uutta eränumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
												$erotus = 0;
												break;
											}
										}
									}
									else {
										foreach ($sarjanumero_valitut[$i] as $snro_tun => $snro_arvo) {
											// katsotaan varastonarvo vain, jos ollaan lisäämässä tai kyseessä on vanha tuote
											if ($sarjanumero_uudet[$i][$snro_tun] != '0000-00-00' or $skp > 0) {
												$varvo_muuto += sarjanumeron_ostohinta("tunnus", $snro_tun);
											}
											else {
												// ollaan poistamatta uutta eränumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
												$erotus = 0;
												break;
											}
										}
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

								if ($row['sarjanumeroseuranta'] == 'T' or $row['sarjanumeroseuranta'] == 'V') {
									foreach ($sarjanumero_valitut[$i] as $snro_tun => $snro_arvo) {
										// katsotaan varastonarvo vain, jos ollaan lisäämässä tai kyseessä on vanha tuote
										if ($sarjanumero_uudet[$i][$snro_tun] == '0000-00-00' and $skp < 0) {
											// ollaan poistamatta uutta sarjanumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
											$erotus = 0;
											break;
										}
									}
								}
								elseif ($row['sarjanumeroseuranta'] == 'E' or $row['sarjanumeroseuranta'] == 'F') {
									foreach ($eranumero_valitut[$i] as $enro_tun => $enro_arvo) {
										if ($eranumero_uudet[$i][$enro_tun] == '0000-00-00' and $skp < 0) {
											// ollaan poistamatta uutta eränumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
											$erotus = 0;
											break;
										}
									}
								}

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

							if ($erotus > 0) {
								$selite = t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("lisättiin")." $erotus ".t("kappaleella. Saldo nyt")." $cursaldo. <br>$lisaselite<br>$inven_laji";
							}
							elseif ($erotus < 0) {
								$selite = t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("vähennettiin")." ".abs($erotus)." ".t("kappaleella. Saldo nyt")." $cursaldo. <br>$lisaselite<br>$inven_laji";
							}
							else {
								$selite = t("Saldo")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("täsmäsi.")." <br>$lisaselite<br>$inven_laji";
							}

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
										selite  	= '$selite',
										laatija  	= '$kukarow[kuka]',
										laadittu 	= now()";
							$result = pupe_query($query);

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
										WHERE yhtio				= '$kukarow[yhtio]'
										and tuoteno				= '$tuoteno'
										and hyllyalue			= '$hyllyalue'
										and hyllynro			= '$hyllynro'
										and hyllyvali			= '$hyllyvali'
										and hyllytaso			= '$hyllytaso'";
							$result = pupe_query($query);

							if ($summa <> 0 and mysql_affected_rows() > 0) {

								$query = "	INSERT into lasku set
											yhtio      = '$kukarow[yhtio]',
											tapvm      = now(),
											tila       = 'X',
											alatila    = 'I',
											laatija    = '$kukarow[kuka]',
											viite      = '$tapahtumaid',
											luontiaika = now()";
								$result = pupe_query($query);
								$laskuid = mysql_insert_id($link);

								if ($yhtiorow["varastonmuutos_inventointi"] != "") {
									$varastonmuutos_tili = $yhtiorow["varastonmuutos_inventointi"];
								}
								else {
									$varastonmuutos_tili = $yhtiorow["varastonmuutos"];
								}

								// Otetaan ensisijaisesti kustannuspaikka tuotteen takaa
								$kustp_ins 		= $tuote_row["kustp"];
								$kohde_ins 		= $tuote_row["kohde"];
								$projekti_ins 	= $tuote_row["projekti"];

								// Kokeillaan varastonmuutos tilin oletuskustannuspaikalle
								list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutos_tili, $kustp_ins, $kohde_ins, $projekti_ins);

								// Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
								list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

								$query = "	INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$yhtiorow[varasto]',
											kustp    = '{$kustp_ins}',
											kohde	 = '{$kohde_ins}',
											projekti = '{$projekti_ins}',
											tapvm    = now(),
											summa    = '$summa',
											vero     = 0,
											lukko    = '',
											selite   = 'Inventointi: ".t("Tuotteen")." {$row["tuoteno"]} $selite',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = pupe_query($query);

								$query = "	INSERT into tiliointi set
											yhtio    = '$kukarow[yhtio]',
											ltunnus  = '$laskuid',
											tilino   = '$varastonmuutos_tili',
											kustp    = '{$kustp_ins}',
											kohde	 = '{$kohde_ins}',
											projekti = '{$projekti_ins}',
											tapvm    = now(),
											summa    = $summa * -1,
											vero     = 0,
											lukko    = '',
											selite   = 'Inventointi: ".t("Tuotteen")." {$row["tuoteno"]} $selite',
											laatija  = '$kukarow[kuka]',
											laadittu = now()";
								$result = pupe_query($query);
							}

							// SARJANUMEROIDEN KÄSITTELY
							if (is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_kaikki[$i]) > 0) {
								if ((float) $skp == 0) {
									// Ei ruksatut sarjanumerot poistetaan
									foreach ($sarjanumero_kaikki[$i] as $snro_tun) {
										if (!is_array($sarjanumero_valitut[$i]) or !in_array($snro_tun, $sarjanumero_valitut[$i])) {
											$query = "	UPDATE sarjanumeroseuranta
														SET myyntirivitunnus = '-1',
														siirtorivitunnus 	 = '-1',
														muuttaja			 = '$kukarow[kuka]',
														muutospvm			 = now(),
														inventointitunnus	 = $tapahtumaid
														WHERE yhtio	= '$kukarow[yhtio]'
														and tunnus = $snro_tun";
											$sarjares = pupe_query($query);
										}
										elseif (isset($sarjanumero_uudet[$i])) {
											foreach ($sarjanumero_uudet[$i] as $snro_key => $snro_val) {

												$query = "	SELECT ostorivitunnus
															FROM sarjanumeroseuranta
															WHERE yhtio = '$kukarow[yhtio]'
															AND tunnus = $snro_key";
												$sarjares = pupe_query($query);
												$sarjarow_x = mysql_fetch_assoc($sarjares);

												$query = "	UPDATE tilausrivi
															SET laskutettuaika = now()
															WHERE yhtio	= '$kukarow[yhtio]'
															AND tunnus = '$sarjarow_x[ostorivitunnus]'
															AND laskutettuaika = '0000-00-00'";
												$sarjares = pupe_query($query);
											}
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
										$sarjares = pupe_query($query);
									}
								}
								elseif ((float) $skp > 0 and $onko_uusia > 0) {
									foreach ($sarjanumero_uudet[$i] as $snro_key => $snro_val) {

										$query = "	SELECT ostorivitunnus
													FROM sarjanumeroseuranta
													WHERE yhtio = '$kukarow[yhtio]'
													AND tunnus = $snro_key";
										$sarjares = pupe_query($query);
										$sarjarow_x = mysql_fetch_assoc($sarjares);

										$query = "	UPDATE tilausrivi
													SET laskutettuaika = now()
													WHERE yhtio	= '$kukarow[yhtio]'
													AND tunnus = '$sarjarow_x[ostorivitunnus]'
													AND laskutettuaika = '0000-00-00'";
										$sarjares = pupe_query($query);
									}
								}
							}

							//ERÄNUMEROIDEN KÄSITTELY
							if (is_array($eranumero_kaikki[$i]) and count($eranumero_kaikki[$i]) > 0) {

								// Ollaan syötetty absoluuttinen määrä ($skp:ssa relatiivinen määrä)
								if ((float) $skp == 0) {

									foreach ($eranumero_valitut[$i] as $enro_key => $enro_val) {
										$sarjaquerylisa = '';

										// jos erä loppuu, niin poistetaan kyseinen erä
										if ((float) $enro_val == 0) {
											$sarjaquerylisa = "myyntirivitunnus = '-1', siirtorivitunnus = '-1', ";
										}

										$query = "	UPDATE sarjanumeroseuranta
													SET era_kpl = '$enro_val',
													$sarjaquerylisa
													muuttaja = '$kukarow[kuka]',
													muutospvm = now()
													WHERE yhtio	= '$kukarow[yhtio]'
													and tunnus = $enro_key";
										$sarjares = pupe_query($query);
									}
								}
								elseif ((float) $skp < 0 or (float) $skp > 0) {

									// Ollaan syötetty relatiivinen määrä
									foreach ($eranumero_valitut[$i] as $enro_key => $enro_val) {

										if ((float) $enro_val > 0) {

											if ($skp < 0) {
												$mita_jaa = $eranumero_kaikki[$i][$enro_key] - $enro_val;
											}
											elseif ($skp > 0 and $onko_uusia == 0) {
												$mita_jaa = $eranumero_kaikki[$i][$enro_key] + $enro_val;
											}
											else {
												$mita_jaa = $enro_val;
											}

											$sarjaquerylisa = '';

											// jos erä loppuu niin poistetaan kyseinen erä
											if ($mita_jaa == 0) {
												$sarjaquerylisa = "myyntirivitunnus = '-1', siirtorivitunnus = '-1', ";
											}

											$query = "	UPDATE sarjanumeroseuranta
														SET era_kpl = '$mita_jaa',
														$sarjaquerylisa
														muuttaja = '$kukarow[kuka]',
														muutospvm = now()
														WHERE yhtio	= '$kukarow[yhtio]'
														and tunnus = $enro_key";
										}
										elseif ($enro_val != '' and (float) $enro_val == 0) {
											$query = "	UPDATE sarjanumeroseuranta
														SET myyntirivitunnus = '-1',
														siirtorivitunnus 	 = '-1',
														muuttaja			 = '$kukarow[kuka]',
														muutospvm			 = now()
														WHERE yhtio	= '$kukarow[yhtio]'
														and tunnus = $enro_key";
										}
										$sarjares = pupe_query($query);
									}

									// päivitetään uusille sarjanumeroille laskutettuaika
									if ($onko_uusia > 0) {
										foreach ($eranumero_uudet[$i] as $enro_key => $enro_val) {

											$query = "	SELECT ostorivitunnus
														FROM sarjanumeroseuranta
														WHERE yhtio = '$kukarow[yhtio]'
														AND tunnus = $enro_key";
											$sarjares = pupe_query($query);
											$sarjarow_x = mysql_fetch_assoc($sarjares);

											$query = "	UPDATE tilausrivi
														SET laskutettuaika = now()
														WHERE yhtio	= '$kukarow[yhtio]'
														AND tunnus = '$sarjarow_x[ostorivitunnus]'
														AND laskutettuaika = '0000-00-00'";
											$sarjares = pupe_query($query);
										}
									}
								}
							}
						}

						if ($fileesta == "ON") {
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
			if (isset($prev)) {
				$alku = $alku-$rivimaara;
				$tee = "INVENTOI";
			}
			elseif (isset($next)) {
				$alku = $alku+$rivimaara;
				$tee = "INVENTOI";
			}
			elseif (isset($valmis)) {
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
		$select = " tuote.kehahin, tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.tunnus tptunnus, tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo, tuotepaikat.inventointilista, tuotepaikat.inventointilista_aika, concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta";

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
			$saldoresult = pupe_query($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Tuote")." '$tuoteno' ".t("ei löydy!")." ".t("Onko tuote saldoton tuote")."? ".t("Onko tuotteella varastopaikka")."?</font><br><br>";
				$tee='';
			}
		}
		elseif ($ean_koodi != "" and $lista == "") {
			///* Inventoidaan tuotenumeron perusteella *///
			$kutsu = " ".t("EAN-koodi")." $ean_koodi ";

			$query = "	SELECT $select
						FROM tuote use index (tuoteno_index)
						JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.eankoodi		= '$ean_koodi'
						and tuote.ei_saldoa		= ''
						ORDER BY sorttauskentta, tuoteno";
			$saldoresult = pupe_query($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("EAN-koodi")." '$ean_koodi' ".t("ei löydy!")." ".t("Onko tuote saldoton tuote")."? ".t("Onko tuotteella varastopaikka")."?</font><br><br>";
				$tee='';
			}
			else {
				$tuoterow = mysql_fetch_assoc($saldoresult);
				$tuoteno = $tuoterow["tuoteno"];
				mysql_data_seek($saldoresult, 0);
			}
		}
		elseif ($lista != "") {
			///* Inventoidaan listan perusteella *///
			$kutsu = " ".t("Inventointilista")." $lista ";

			if ($alku == '' or $alku < 0) {
				$alku = 0;
			}

			$loppu = "18";

			if ($rivimaara != "18" and $rivimaara != '') {
				$loppu = $rivimaara;
			}

			if ($jarjestys == 'tuoteno') {
				$order = " tuoteno, sorttauskentta ";
			}
			elseif ($jarjestys == 'osastotrytuoteno') {
				$order = " osasto, try, tuoteno, sorttauskentta ";
			}
			elseif ($jarjestys == 'nimityssorttaus') {
				$order = " nimitys, sorttauskentta ";
			}
			else {
				$order = " sorttauskentta, tuoteno ";
			}

			$query = "	SELECT $select
						FROM tuotepaikat USE INDEX (yhtio_inventointilista)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno and tuote.ei_saldoa = '' $joinon)
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and tuotepaikat.inventointilista = '$lista'
						ORDER BY $order
						LIMIT $alku, $loppu";
			$saldoresult = pupe_query($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Listaa")." '$lista' ".t("ei löydy, tai se on jo inventoitu")."!</font><br><br>";
				$tee='';
			}
		}
		else {
			echo "<font class='error'>".t("VIRHE: Tarkista syötetyt tiedot")."!</font><br><br>";
			$tee='';
		}
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

		$seljarj1 = "";
		$seljarj2 = "";
		$seljarj3 = "";
		$seljarj4 = "";

		if ($jarjestys == 'tuoteno') {
			$seljarj2 = "SELECTED";
		}
		elseif ($jarjestys == 'osastotrytuoteno') {
			$seljarj3 = "SELECTED";
		}
		elseif ($jarjestys == 'nimityssorttaus') {
			$seljarj4 = "SELECTED";
		}
		else {
			$seljarj1 = "SELECTED";
		}

		if ($lista != "") {
			echo "<form method='post'>";
			echo "<select name='rivimaara' onchange='submit()'>";
			echo "<option value='180' $sel180rivi>".t("Näytetään 180 riviä")."</option>";
			echo "<option value='18' $sel18rivi>".t("Näytetään 18 riviä")."</option>";
			echo "<option value='1' $sel1rivi>".t("Näytetään 1 rivi")."</option>";
			echo "</select>";
			echo "<select name='jarjestys' onchange='submit()'>";
			echo "<option value='' $seljarj1>".t("Tuotepaikkajärjestys")."</option>";
			echo "<option value='tuoteno' $seljarj2>".t("Tuotenumerojärjestys")."</option>";
			echo "<option value='nimityssorttaus' $seljarj4>".t("Nimitysjärjestykseen")."</option>";
			echo "<option value='osastotrytuoteno' $seljarj3>".t("Osasto/Tuoteryhmä/Tuotenumerojärjestykseen")."</option>";
			echo "</select>";
			echo "<input type='hidden' name='tee' value='INVENTOI'>";
			echo "<input type='hidden' name='lista' value='$lista'>";
			echo "<input type='hidden' name='lista_aika' value='$lista_aika'>";
			echo "<input type='hidden' name='alku' value='$alku'>";
			echo "</form>";
		}


		echo "<form name='inve' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='VALMIS'>";
		echo "<input type='hidden' name='lista' value='$lista'>";
		echo "<input type='hidden' name='lista_aika' value='$lista_aika'>";
		echo "<input type='hidden' name='alku' value='$alku'>";
		echo "<input type='hidden' name='rivimaara' value='$rivimaara'>";
		echo "<input type='hidden' name='jarjestys' value='$jarjestys'>";

		echo "<table>";
		echo "<tr><td colspan='7' class='back'>".t("Syötä joko hyllyssä oleva määrä, tai lisättävä määrä + etuliitteellä, tai vähennettävä määrä - etuliitteellä")."</td></tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th><th>".t("Inventointiaika")."</th><th>".t("Varastosaldo")."</th><th>".t("Ennpois")."/".t("Kerätty")."</th><th>".t("Hyllyssä")."</th><th>".t("Laskettu hyllyssä")."</th>";
		echo "</tr>";

		$rivilask = 0;

		while ($tuoterow = mysql_fetch_assoc($saldoresult)) {

			//Haetaan kerätty määrä
			$query = "	SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,	ifnull(sum(tilausrivi.varattu),0) ennpois
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
						WHERE yhtio 	= '$kukarow[yhtio]'
						and tyyppi 		in ('L','G','V')
						and tuoteno		= '$tuoterow[tuoteno]'
						and varattu    <> 0
						and laskutettu 	= ''
						and hyllyalue	= '$tuoterow[hyllyalue]'
						and hyllynro 	= '$tuoterow[hyllynro]'
						and hyllyvali 	= '$tuoterow[hyllyvali]'
						and hyllytaso 	= '$tuoterow[hyllytaso]'";
			$hylresult = pupe_query($query);
			$hylrow = mysql_fetch_assoc($hylresult);

			$hyllyssa = sprintf('%.2f',$tuoterow['saldo']-$hylrow['keratty']);

			if ($tuoterow["sarjanumeroseuranta"] != "") {
				$query = "	SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus, tilausrivi_myynti.otunnus myyntitunnus, tilausrivi_myynti.varattu myyntikpl,
							round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta, era_kpl, tilausrivi_osto.yksikko, tilausrivi_osto.laskutettuaika
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
							and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and (tilausrivi_osto.laskutettuaika != '0000-00-00' or (tilausrivi_osto.laatija = 'Invent' and tilausrivi_osto.laskutettuaika = '0000-00-00')))
							and ('$tuoterow[sarjanumeroseuranta]' not in ('E','F','G') or era_kpl != 0)
							ORDER BY sarjanumero";
				$sarjares = pupe_query($query);
			}

			if (($tuoterow["inventointilista_aika"] == '0000-00-00 00:00:00' and $lista == '') or ($tuoterow["inventointilista"] == $lista and $tuoterow["inventointilista_aika"] != '0000-00-00 00:00:00')) {

				echo "<tr>";
				echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys');

				if (in_array($tuoterow["sarjanumeroseuranta"], array("S","T","U","V"))) {
					if (mysql_num_rows($sarjares) > 0) {
						echo "<br><table width='100%'>";

						$sarjalaskk = 1;

						while ($sarjarow = mysql_fetch_assoc($sarjares)) {
							if ($sarjanumero[$tuoterow["tptunnus"]][$sarjarow["tunnus"]] != '') {
								$chk = "CHECKED";
							}
							else {
								$chk = "";
							}

							echo "<tr><td>$sarjalaskk. $sarjarow[sarjanumero]</td><td align='right'>";

							if ($tuoterow['sarjanumeroseuranta'] == 'T' or $tuoterow['sarjanumeroseuranta'] == 'V') {
								echo sprintf("%.02f", $tuoterow['kehahin']);
							}
							else {
								echo sprintf("%.02f",sarjanumeron_ostohinta("tunnus", $sarjarow["tunnus"]));
							}

							echo "</td><td>";
							echo "	<input type='hidden' name='sarjanumero_kaikki[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[tunnus]'>
									<input type='hidden' name='sarjanumero_uudet[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value = '$sarjarow[laskutettuaika]'>";

							if ($sarjarow['laskutettuaika'] == '0000-00-00') {
								echo "<input type='hidden' name='sarjanumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[tunnus]'>";
								echo "<font class='message'>**",t("UUSI"),"**</font>";
							}
							else {
								echo "<input type='checkbox' name='sarjanumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[tunnus]' $chk></td>";
							}

							echo "</td>";

							if ($sarjarow["myyntitunnus"] != 0) {
								echo "<td><font class='message'>",t("Tilauksella")," $sarjarow[myyntitunnus]</font></td>";
							}
							echo "</tr>";

							$sarjalaskk++;
						}
						echo "</table>";
					}
					echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=",$palvelin2,"inventoi.php////tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//lista_aika=$lista_aika//alku=$alku'>".t("Uusi sarjanumero")."</a>";
				}
				elseif (in_array($tuoterow["sarjanumeroseuranta"], array("E","F","G"))) {
					if (mysql_num_rows($sarjares) > 0) {
						echo "<br><table width='100%'>";

						$sarjalaskk = 1;

						while($sarjarow = mysql_fetch_assoc($sarjares)) {
							echo "<tr><td>$sarjalaskk. $sarjarow[sarjanumero]</td>
									<td>$sarjarow[era_kpl] ".t_avainsana("Y", "", "and avainsana.selite='$sarjarow[yksikko]'", "", "", "selite")."</td>
									<td>";

							if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F") {
								echo sprintf('%.02f', $tuoterow['kehahin']);
							}
							else {
								echo sprintf('%.02f', $sarjarow['ostohinta']);
							}

							echo "</td>
									<td>
									<input type='hidden' name='eranumero_kaikki[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>
									<input type='hidden' name='eranumero_uudet[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value = '$sarjarow[laskutettuaika]'>";

							if ($sarjarow['laskutettuaika'] == '0000-00-00' or $sarjarow["myyntitunnus"] != 0) {
								if ($sarjarow['laskutettuaika'] == '0000-00-00') {
									echo "<input type='hidden' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>";
									echo "<font class='message'>**",t("UUSI"),"**</font>";
								}
								else {
									echo "<input type='hidden' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>";
									echo "<font class='message'>",t("Tilauksella")," $sarjarow[myyntitunnus] $sarjarow[myyntikpl]</font>";
								}
							}
							else {
								if ($onko_uusia > 0) {
									$apu_era_kpl = "";
								}
								else {
									$apu_era_kpl = $sarjarow["era_kpl"];
								}
								echo "<input type='text' size='5' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$apu_era_kpl'>";
							}
							echo "</td>";
							echo "</tr>";

							$sarjalaskk++;
						}

						echo "</table>";
					}
					echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=",$palvelin2,"inventoi.php////tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//lista_aika=$lista_aika//alku=$alku'>".t("Uusi eränumero")."</a>";
				}

				echo "</td><td valign='top'>";

				if ($tuoterow["hyllyalue"] == "!!M") {
					$asiakkaan_tunnus = (int) $tuoterow["hyllynro"].$tuoterow["hyllyvali"].$tuoterow["hyllytaso"];
					$query = "	SELECT if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, ' / ', toim_nimi)) asiakkaan_nimi
								FROM asiakas
								WHERE yhtio = '{$kukarow["yhtio"]}'
								AND tunnus = '$asiakkaan_tunnus'";
					$asiakasresult = pupe_query($query);
					$asiakasrow = mysql_fetch_assoc($asiakasresult);
					echo t("Myyntitili"), " ", $asiakasrow["asiakkaan_nimi"];
				}
				else {
					echo "$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]";
				}

				echo "</td>";

				echo "<td>{$tuoterow['inventointiaika']}</td>";

				if ($tuoterow["sarjanumeroseuranta"] != "S") {
					echo "<td valign='top'>$tuoterow[saldo]</td><td valign='top'>$hylrow[ennpois]/$hylrow[keratty]</td><td valign='top'>".$hyllyssa."</td>";
				}
				else {
					echo "<td valign='top'>$tuoterow[saldo]</td><td valign='top'></td><td valign='top'>$tuoterow[saldo]</td>";
				}

				echo "<input type='hidden' name='hyllyssa[$tuoterow[tptunnus]]' value='$tuoterow[saldo]'>";
				echo "<input type='hidden' name='tuote[$tuoterow[tptunnus]]' value='$tuoterow[tuoteno]###$tuoterow[hyllyalue]###$tuoterow[hyllynro]###$tuoterow[hyllyvali]###$tuoterow[hyllytaso]'>";
				echo "<td valign='top'><input type='text' size='7' name='maara[$tuoterow[tptunnus]]' id='maara_$tuoterow[tptunnus]' value='".$maara[$tuoterow["tptunnus"]]."'></td>";

				if (in_array($tuoterow["sarjanumeroseuranta"], array("S","T","U","V"))) {
					echo "<td valign='top' class='back'>".t("Tuote on sarjanumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
				}
				elseif (in_array($tuoterow["sarjanumeroseuranta"], array("E","F","G"))) {
					echo "<td valign='top' class='back'>".t("Tuote on eränumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
				}

				echo "</tr>";

				if ($rivilask == 0) {
					echo "<script LANGUAGE='JavaScript'>document.getElementById('maara_$tuoterow[tptunnus]').focus();</script>";
					$kentta = "";
					$rivilask++;
				}

			}
			elseif ($tuoterow["inventointilista_aika"] == '0000-00-00 00:00:00' and $tuoterow["inventointilista"] == $lista) {

				echo "<tr>";
				echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys');

				if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "U") {
					if (mysql_num_rows($sarjares) > 0) {
						echo "<br><table>";

						while($sarjarow = mysql_fetch_assoc($sarjares)) {
							echo "<tr><td>$sarjarow[sarjanumero]</td><td>$sarjarow[ostohinta]</td></tr>";
						}

						echo "</table>";
					}
				}

				echo "</td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";

				$query = "	SELECT *
							FROM tapahtuma
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tuoteno 	= '$tuoterow[tuoteno]'
							and laji		= 'Inventointi'
							and laadittu   >= '$lista_aika'
							and hyllyalue 	= '$tuoterow[hyllyalue]'
							and hyllynro 	= '$tuoterow[hyllynro] '
							and hyllyvali 	= '$tuoterow[hyllyvali]'
							and hyllytaso 	= '$tuoterow[hyllytaso]'
							ORDER BY tunnus desc
							LIMIT 1";
				$tapresult = pupe_query($query);
				$taptrow = mysql_fetch_assoc($tapresult);

				$taptrow["selite"] = preg_replace("/".t("paikalla")." .*?\-.*?\-.*?\-.*? /", "", $taptrow["selite"]);

				echo "<td valign='top' class='green' colspan='4'>".t("Tuote on inventoitu!")." $taptrow[selite]";

				//Jos invauserohälytys on triggeröity
				$query = "	SELECT abs(kpl) kpl
							FROM tapahtuma
								WHERE yhtio   = '$kukarow[yhtio]'
							and tuoteno   = '$tuoterow[tuoteno]'
							and laji	  = 'Inventointi'
							and laadittu >= '$lista_aika'
							and kpl 	 <> 0
							ORDER BY tunnus desc
							LIMIT 1";
				$tapresult = pupe_query($query);
				$taptrow = mysql_fetch_assoc ($tapresult);

				if ($taptrow["kpl"] > 10) {
					echo "<br><font class='error'>".t("HUOM: Tuotteen saldo muuttui yli 10 kappaletta! Tarkista inventointi!")."</font>";
				}

				echo "</td>";

				if (in_array($tuoterow["sarjanumeroseuranta"], array("S","T","U","V"))) {
					echo "<td valign='top' class='back'>".t("Tuote on sarjanumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
				}
				elseif (in_array($tuoterow["sarjanumeroseuranta"], array("E","F","G"))) {
					echo "<td valign='top' class='back'>".t("Tuote on eränumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
				}

				echo "</tr>";
			}
			else {
				echo "<tr>";
				echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys')." </td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";
				echo "<td valign='top'>".sprintf(t("Tätä tuotetta inventoidaan listalla %s. Inventointi estetty"), $tuoterow['inventointilista']).".</td>";
				echo "</tr>";
			}
		}

		echo "</table><br>";

		echo "<table>";

		$tresult = t_avainsana("INVEN_LAJI");

		if (mysql_num_rows($tresult) > 0) {
			echo "<tr><th>".t("Inventoinnin laji").":</th>";

			echo "<td><select name='inven_laji'>";

			while($itrow = mysql_fetch_assoc($tresult)) {
				$sel = "";
				if ($itrow["selite"] == $inven_laji) $sel = 'selected';
				echo "<option value='$itrow[selite]' $sel>$itrow[selite]</option>";
			}
			echo "</select></td></tr>";
		}

		echo "<tr><th>".t("Syötä inventointiselite:")."</th>";
		echo "<td><input type='text' size='50' name='lisaselite' value='$lisaselite'></td></tr>";
		echo "</table><br><br>";


		if (mysql_num_rows($saldoresult) == $rivimaara) {
			echo "<input type='submit' name='next' value='".t("Inventoi/Seuraava sivu")."'>";
			//echo "<input type='submit' name='prev' value='".t("Inventoi/Edellinen sivu")."'> ";
		}
		else {
			echo "<input type='submit' name='valmis' value='".t("Inventoi/Valmis")."'>";
		}

		echo "</form>";
	}

	if ($tee == 'MITATOI') {
		$query = "	UPDATE tuotepaikat
					SET inventointilista_aika = '0000-00-00 00:00:00'
					WHERE tuotepaikat.yhtio	= '$kukarow[yhtio]'
					and inventointilista = '$lista'";
		$result = pupe_query($query);

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
			$noperes = pupe_query($query);
			$noperow = mysql_fetch_assoc($noperes);

			echo "<table>";
			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='INVENTOI'>";
			echo "<input type='hidden' name='seuraava_tuote' value='nope'>";
			echo "<input type='hidden' name='tuoteno' value='$noperow[tuoteno]'>";
			echo "<tr><td class='back'><input type='submit' value='".t("Edellinen tuote")."'></td>";
			echo "</form>";

			$query = "	SELECT tuoteno
						FROM tuote use index (tuoteno_index)
						JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
						WHERE tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.tuoteno		> '$tmp_tuoteno'
						and tuote.ei_saldoa		= ''
						ORDER BY tuoteno
						LIMIT 1";
			$yesres = pupe_query($query);
			$yesrow = mysql_fetch_assoc($yesres);

			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='INVENTOI'>";
			echo "<input type='hidden' name='seuraava_tuote' value='yes'>";
			echo "<input type='hidden' name='tuoteno' value='$yesrow[tuoteno]'>";
			echo "<td class='back'><input type='submit' value='".t("Seuraava tuote")."'></td></tr>";
			echo "</form>";
			echo "</table>";
		}

		echo "<form name='inve' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='INVENTOI'>";

		echo "<br><table>";
		echo "<tr><th>".t("Tuotenumero:")."</th><td>";

		echo livesearch_kentta("inve", "TUOTEHAKU", "tuoteno", 210);

		echo "</td></tr>";

		echo "<tr><th>".t("EAN-koodi:")."</th><td><input type='text' size='15' name='ean_koodi'></td></tr>";

		echo "<tr><th>".t("Inventointilistan numero:")."</th><td><input type='text' size='6' name='lista'></td><td class='back'><input type='Submit' value='".t("Inventoi")."'></td></tr>";

		echo "</form>";
		echo "</table>";
		echo "<br><br>";

		echo "<form method='post' enctype='multipart/form-data'>
				<input type='hidden' name='tee' value='FILE'>
				<input type='hidden' name='filusta' value='yep'>
				<br><br>
				<font class='head'>".t("Inventoi tiedostosta")."</font><hr>
				<table>
				<tr><th colspan='4'>".t("Tiedostomuoto").".</th></tr>
				<tr>";
		echo "	<td>".t("Tuoteno")." / ".t("EAN")."</td><td>".t("Hyllyalue-Hyllynro-Hyllyväli-Hyllytaso")."</td><td>".t("Määrä")."</td><td>".t("Laji")." / ".t("Selite")."</td>";
		echo "	</tr>";

		echo "	<tr><td class='back'><br></td></tr>";

		echo "	<tr><th>".t("Valitse tiedosto").":</th>
				<td colspan='3'><input name='userfile' type='file'></td></tr>
				<tr><th>".t("Valitse tyyppi").":</th>
				<td colspan='3'>
				<select name='tuoteno_ean'>
				<option value=''>".t("Tiedosto tuotenumerolla")."</option>
				<option value='EAN' $tuoteno_ean_sel>".t("Tiedosto EAN-koodilla")."</option>
				</select>
				</td>

				<td class='back'><input type='submit' value='".t("Inventoi")."'></td>
				</tr>
				</form>
				</table>";
		echo "<br><br>";

		//haetaan inventointilista numero tässä vaiheessa
		$query = "	SELECT distinct inventointilista, inventointilista_aika
					FROM tuotepaikat
					WHERE yhtio	= '$kukarow[yhtio]'
					and inventointilista > 0
					and inventointilista_aika > '0000-00-00 00:00:00'
					ORDER BY inventointilista";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			echo "<font class='message'>".t("Avoimet inventointilistat").":</font><br>";
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Numero")."</th>";
			echo "<th>".t("Luontiaika")."</th>";
			echo "<th colspan='2'></th>";
			echo "</tr>";

			while ($lrow = mysql_fetch_assoc($result)) {
				echo "<tr>
						<td>$lrow[inventointilista]</td>
						<td>".tv1dateconv($lrow["inventointilista_aika"], "PITKA")."</td>
						<td>
							<form action='inventoi.php' method='post'>
							<input type='hidden' name='tee' value='INVENTOI'>
							<input type='hidden' name='lista' value='$lrow[inventointilista]'>
							<input type='hidden' name='lista_aika' value='$lrow[inventointilista_aika]'>
							<input type='submit' value='".t("Inventoi")."'>
							</form>
						</td>
						<td>
							<form action='inventoi.php' method='post'>
							<input type='hidden' name='tee' value='MITATOI'>
							<input type='hidden' name='lista' value='$lrow[inventointilista]'>
							<input type='hidden' name='lista_aika' value='$lrow[inventointilista_aika]'>
							<input type='submit' value='".t("Mitätöi lista")."'>
							</form>
						</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	// lukitaan tableja
	$query = "UNLOCK TABLES";
	$result = pupe_query($query);

	if ($mobiili != "YES") {
		require ("inc/footer.inc");
	}

?>