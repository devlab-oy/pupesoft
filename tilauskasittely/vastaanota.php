<?php

	require ("../inc/parametrit.inc");

	if (!isset($tee)) 			$tee = "";
	if (!isset($etsi)) 			$etsi = "";
	if (!isset($id)) 			$id = "";
	if (!isset($boob)) 			$boob = "";
	if (!isset($maa)) 			$maa = "";
	if (!isset($varastorajaus))	$varastorajaus = "";

	if ($toim == "MYYNTITILI") {
		echo "<font class='head'>".t("Toimita myyntitili asiakkaalle").":</font><hr>";
	}
	elseif ($toim == "MYYNTITILIVASTAANOTA") {
		echo "<font class='head'>".t("Vastaanota myyntitili asiakkaalta").":</font><hr>";
	}
	else {
		echo "<font class='head'>".t("Vastaanota siirtolista").":</font><hr>";
	}

	if ($tee == 'kommentista') {
		if ($id != '') {

			$query = "	SELECT tunnus, kommentti
						from tilausrivi
						where tyyppi='G'
						and otunnus 	= '$id'
						and yhtio		= '$kukarow[yhtio]'";
			$alkuresult = pupe_query($query);

			while ($alkurow = mysql_fetch_assoc($alkuresult)) {

				$bpaikka = explode(' ', $alkurow["kommentti"]);

				$ka = count($bpaikka)-1;

				$ok = 1;

				if (!is_numeric($bpaikka[$ka])) 	$ok = 0;
				if (!is_numeric($bpaikka[$ka-1])) 	$ok = 0;
				if (!is_numeric($bpaikka[$ka-2])) 	$ok = 0;


				if ($ok == 1) {
					$tunnus[] = $alkurow["tunnus"];
					$t1[$alkurow["tunnus"]] = $bpaikka[$ka-3];
					$t2[$alkurow["tunnus"]] = $bpaikka[$ka-2];
					$t3[$alkurow["tunnus"]] = $bpaikka[$ka-1];
					$t4[$alkurow["tunnus"]] = $bpaikka[$ka];
				}
				else {
					$tunnus[] = $alkurow["tunnus"];
					$t1[$alkurow["tunnus"]] = $bpaikka[$ka-2];
					$t2[$alkurow["tunnus"]] = $bpaikka[$ka-1];
					$t3[$alkurow["tunnus"]] = $bpaikka[$ka];
					$t4[$alkurow["tunnus"]] = '0';
				}

			}
		}

	}

	if ($tee == 'mikrotila') {

		echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
		echo "<input type='hidden' name='id' value='$id'>";
		echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
		echo "<input type='hidden' name='maa' value='$maa'>";
		echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
		echo "<input type='hidden' name='tee' value='failista'>";

		echo "	<font class='message'>".t("Tiedostomuoto").":</font><br><br>

				<table>
				<tr><th colspan='5'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("Määrä")."</td><td>".t("Kommentti")."</td><td>".t("Lähettävä varastopaikka")."</td><td>".t("Vastaanottava varastopaikka")."</td></tr>
				</table>
				<br>
				<table>
				<tr>";

		echo "
				<th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Läheta")."'></td>
				</tr>
				</table>
				</form>";
		exit;
	}

	if ($tee == 'failista') {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
			$timeparts = explode(" ",microtime());
			$starttime = $timeparts[1].substr($timeparts[0],1);

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$name	= strtoupper($path_parts['filename']);
			$ext	= strtoupper($path_parts['extension']);

			if ($ext != "TXT" and $ext != "CSV") {
				die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
			}

			$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

			// luetaan tiedosto alusta loppuun...
			$rivi = fgets($file, 4096);

			while (!feof($file)) {

				$tuoteno  	= '';
				$varattu  	= '';
				$teksti   	= '';
				$avarasto 	= '';
				$bvarasto	= '';

				// luetaan rivi tiedostosta..
				$rivi = explode("\t", pupesoft_cleanstring($rivi));

				$tuoteno  = $rivi[0];
				$varattu  = $rivi[1];
				$teksti   = $rivi[2];
				$avarasto = $rivi[3];
				$bvarasto = $rivi[4];

				if ($bvarasto!='' and $avarasto!='' and $tuoteno!='') {

					$paikka = explode('#', $avarasto);

					$query = "	SELECT *
								from tilausrivi
								where tyyppi='G'
								and otunnus 	= '$id'
								and	tuoteno		= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and hyllyalue  	= '$paikka[0]'
								and hyllynro   	= '$paikka[1]'
								and hyllyvali  	= '$paikka[2]'
								and hyllytaso  	= '$paikka[3]'";
					$alkuresult = pupe_query($query);


					if (mysql_num_rows($alkuresult) == 1) {
						$alkurow = mysql_fetch_assoc($alkuresult);

						$bpaikka = explode('#', $bvarasto);

						$tunnus[] = $alkurow["tunnus"];
						$t1[$alkurow["tunnus"]] = $bpaikka[0];
						$t2[$alkurow["tunnus"]] = $bpaikka[1];
						$t3[$alkurow["tunnus"]] = $bpaikka[2];
						$t4[$alkurow["tunnus"]] = $bpaikka[3];

					}
				}

				$rivi = fgets($file, 4096);
			}
		}
	}

	if ($tee == 'paikat' and $vainlistaus == '') {

		$virheita = 0;

		//käydään kaikki rivit läpi ja tarkastetaan varastopaika ja perustetaan uusia jos on tarvis
		foreach ($tunnus as $tun) {

			$t1[$tun] = trim($t1[$tun]);
			$t2[$tun] = trim($t2[$tun]);
			$t3[$tun] = trim($t3[$tun]);
			$t4[$tun] = trim($t4[$tun]);

			$t1[$tun] = strtoupper($t1[$tun]);

			$query = "	SELECT tilausrivi.tuoteno, tuote.ei_saldoa, tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso
						FROM tilausrivi
						JOIN tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.tunnus		= '$tun'
						and tilausrivi.yhtio		= '$kukarow[yhtio]'
						and tilausrivi.tyyppi		= 'G'
						and tilausrivi.toimitettu	= ''";
			$result = pupe_query($query);
			$tilausrivirow = mysql_fetch_assoc($result);

			if (mysql_num_rows($result) != 1) {
				echo "<font class='error'>".t("VIRHE: Riviä ei löydy tai se on jo siirretty uudelle paikalle")."!</font><br>";
			}
			elseif ($tilausrivirow["ei_saldoa"] == "") {

				// Jaahas mitäs tuotepaikalle pitäisi tehdä
				if (($rivivarasto[$tun] != 'x') and ($rivivarasto[$tun] != '')) {  //Varastopaikka vaihdettiin pop-upista, siellä on paikan tunnus
					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso, inventointilista_aika
								from tuotepaikat
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus 	= '$rivivarasto[$tun]'
								and tuoteno	= '$tilausrivirow[tuoteno]'";
				}
				else {
					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso, inventointilista_aika
								from tuotepaikat
								WHERE yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$t1[$tun]'
								and hyllynro	= '$t2[$tun]'
								and hyllyvali	= '$t3[$tun]'
								and hyllytaso	= '$t4[$tun]'
								and tuoteno		= '$tilausrivirow[tuoteno]'";
				}

				$result = pupe_query($query);

				if (mysql_num_rows($result) == 0) {

					// Tämä on uusi kokonaan varastopaikka tälle tuotteelle, joten perustetaan se
					// Jos tuotteen eka paikka, se on oletuspaikka

					$query  = "	SELECT tunnus
								FROM varastopaikat
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus	= '$varasto'
								and alkuhyllyalue <= '$t1[$tun]'
								and loppuhyllyalue >= '$t1[$tun]'";
					$vares = pupe_query($query);

					if (mysql_num_rows($vares) == 1) {

						$query = "	SELECT tuoteno
									FROM tuotepaikat
									WHERE yhtio = '$kukarow[yhtio]'
									AND tuoteno = '$tilausrivirow[tuoteno]'";
						$aresult = pupe_query($query);

						if (mysql_num_rows($aresult) == 0) {
							$oletus='X';
							echo t("oletus")." ";
						}
						else {
							$oletus='';
						}

						if ($t1[$tun] != '' and $t2[$tun] != '' and $t3[$tun] != '' and $t4[$tun] != '') {
							$query = "	INSERT into tuotepaikat (hyllyalue, hyllynro, hyllyvali, hyllytaso, oletus, saldo, saldoaika, tuoteno, yhtio, laatija, luontiaika)
										values ('$t1[$tun]','$t2[$tun]','$t3[$tun]','$t4[$tun]','$oletus','0',now(),'$tilausrivirow[tuoteno]','$kukarow[yhtio]','$kukarow[kuka]',now())";
							$ynsre = pupe_query($query);
							$uusipaikka = mysql_insert_id();

							$tapahtumaquery = "	INSERT into tapahtuma set
												yhtio 		= '$kukarow[yhtio]',
												tuoteno 	= '$tilausrivirow[tuoteno]',
												kpl 		= 0,
												kplhinta	= 0,
												hinta 		= 0,
												laji 		= 'uusipaikka',
												hyllyalue 	= '$t1[$tun]',
												hyllynro 	= '$t2[$tun]',
												hyllyvali 	= '$t3[$tun]',
												hyllytaso 	= '$t4[$tun]',
												selite 		= '".t("Tuotteella ei varastopaikkaa, luotiin uusi paikka ")." $t1[$tun] $t2[$tun] $t3[$tun] $t4[$tun]',
												laatija 	= '$kukarow[kuka]',
												laadittu 	= now()";
							$tapahtumaresult = pupe_query($tapahtumaquery);

							$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
										from tuotepaikat
										WHERE yhtio	= '$kukarow[yhtio]'
										and tunnus 	= '$uusipaikka'
										and tuoteno	= '$tilausrivirow[tuoteno]'";
							$result = pupe_query($query);
							$paikkarow = mysql_fetch_assoc($result);

							if ($toim == "MYYNTITILI") {
								echo "<font class='message'>".t("Tuote")." $tilausrivirow[tuoteno] ".t("siirretty myyntitiliin").".</font><br>";
							}
							else {
								echo "<font class='message'>".t("Perustan")." ".t("Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("perustetaan uusi paikka")." $t1[$tun]-$t2[$tun]-$t3[$tun]-$t4[$tun]</font><br>";
							}
						}
						else {
							echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno]. ".t("ei voitu perustaa tyhjää varastopaikkaa")."!</font><br>";
							$virheita++;
						}
					}
					else {
						echo "<font class='error'>".t("VIRHE: Syöttämäsi varastopaikka ei kuulu kohdevaraston alueeseen")."!</font><br>";

						$t1[$tun] = '';
						$t2[$tun] = '';
						$t3[$tun] = '';
						$t4[$tun] = '';

						$virheita++;
					}
				}
				else {
					$paikkarow = mysql_fetch_assoc($result);

					if ($paikkarow["inventointilista_aika"] > 0) {
						echo "<font class='error'>$paikkarow[hyllyalue]-$paikkarow[hyllynro]-$paikkarow[hyllyvali]-$paikkarow[hyllytaso] ".t("VIRHE: Kohdepaikalla on inventointi kesken, ei voida jatkaa")."!</font><br>";
						$virheita++;
					}

					$t1[$tun] = $paikkarow['hyllyalue'];
					$t2[$tun] = $paikkarow['hyllynro'];
					$t3[$tun] = $paikkarow['hyllyvali'];
					$t4[$tun] = $paikkarow['hyllytaso'];
				}
			}
			if ($eankoodi[$tun]!= '') {
				$query = "	UPDATE tuote
							SET eankoodi = '$eankoodi[$tun]',
							muuttaja	= '$kukarow[kuka]',
							muutospvm	= now()
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tilausrivirow[tuoteno]'";
				$resulteankoodi = pupe_query($query);
			}

			//haetaan antavan varastopaikan tunnus
			$query = "	SELECT inventointilista_aika
						FROM tuotepaikat
						WHERE yhtio		= '$kukarow[yhtio]'
						and hyllyalue	= '$tilausrivirow[hyllyalue]'
						and hyllynro	= '$tilausrivirow[hyllynro]'
						and hyllyvali	= '$tilausrivirow[hyllyvali]'
						and hyllytaso	= '$tilausrivirow[hyllytaso]'
						and tuoteno		= '$tilausrivirow[tuoteno]'";
			$presult = pupe_query($query);
			$prow = mysql_fetch_assoc($presult);

			if ($prow["inventointilista_aika"] > 0) {
				echo "<font class='error'>$tilausrivirow[hyllyalue]-$tilausrivirow[hyllynro]-$tilausrivirow[hyllyvali]-$tilausrivirow[hyllytaso] ".t("VIRHE: Lähdepaikalla on inventointi kesken, ei voida jatkaa")."!</font><br>";
				$virheita++;
			}
		}

		if ($virheita == 0) {
			$tee = 'valmis';
		}
		else {
			$tee = '';
		}

		echo "<br><br>";
	}

	if ($tee == 'valmis') {

		$virheita = 0;

		//käydään kaikki riviti läpi ja siirretään saldoja
		foreach ($tunnus as $tun) {

			//nollataan nämä tärkeät
			$tuoteno	= '';
			$mista		= '';
			$minne		= '';
			$asaldo		= '';

			$t1[$tun]=strtoupper($t1[$tun]);

			$query = "	SELECT tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.varattu, tuote.ei_saldoa, tuote.sarjanumeroseuranta
						FROM tilausrivi
						JOIN tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.tunnus		= '$tun'
						and tilausrivi.yhtio		= '$kukarow[yhtio]'
						and tilausrivi.tyyppi		= 'G'
						and tilausrivi.toimitettu 	= ''";
			$result = pupe_query($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font style='error'>".t("VIRHE: Riviä ei löydy tai se on jo siirretty uudelle paikalle")."!</font><br>";

			}
			else {
				$tilausrivirow = mysql_fetch_assoc($result);

				$tuoteno = $tilausrivirow["tuoteno"];
				$asaldo  = $tilausrivirow["varattu"];
				$tee 	 = "";

				if ($asaldo != 0 and $tilausrivirow["ei_saldoa"] == "") {

					$tkpl = $asaldo;
					//haetaan antavan varastopaikan tunnus
					$query = "	SELECT tunnus
								FROM tuotepaikat
								WHERE yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$tilausrivirow[hyllyalue]'
								and hyllynro	= '$tilausrivirow[hyllynro]'
								and hyllyvali	= '$tilausrivirow[hyllyvali]'
								and hyllytaso	= '$tilausrivirow[hyllytaso]'
								and tuoteno		= '$tilausrivirow[tuoteno]'";
					$presult = pupe_query($query);

					if (mysql_num_rows($presult) != 1) {
						echo "<font style='error'>".t("VIRHE: Antavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
						exit;
					}
					else {
						$prow = mysql_fetch_assoc($presult);

						$mista = $prow["tunnus"];
					}

					//haetaan vastaanottavan varastopaikan tunnus
					$query = "	SELECT tunnus
								FROM tuotepaikat
								WHERE yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$t1[$tun]'
								and hyllynro	= '$t2[$tun]'
								and hyllyvali	= '$t3[$tun]'
								and hyllytaso	= '$t4[$tun]'
								and tuoteno		= '$tilausrivirow[tuoteno]'";
					$presult = pupe_query($query);

					if (mysql_num_rows($presult) != 1) {
						echo "<font style='error'>".t("VIRHE: Vastaanottavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
					}
					else {
						$prow = mysql_fetch_assoc($presult);

						$minne = $prow["tunnus"];
						$uusiol = $prow["tunnus"];
					}

					//laitetaan sarjanumerot kuntoon
					if ($tilausrivirow["sarjanumeroseuranta"] != "") {
						$query = "	SELECT tunnus, era_kpl, sarjanumero
									FROM sarjanumeroseuranta
									WHERE siirtorivitunnus	= '$tun'
									and yhtio = '$kukarow[yhtio]'";
						$sarjares = pupe_query($query);

						$sarjano_array = array();

						while ($sarjarow = mysql_fetch_assoc($sarjares)) {
							if ($tilausrivirow["sarjanumeroseuranta"] == "E" or $tilausrivirow["sarjanumeroseuranta"] == "F" or $tilausrivirow["sarjanumeroseuranta"] == "G") {
								// eränumeroseurannassa pitää etsiä ostotunnuksella erä josta kappaleeet otetaan

								// koitetaan löytää vapaita ostettuja eriä mitä myydä
								$query =   "SELECT era_kpl, tunnus, ostorivitunnus
											FROM sarjanumeroseuranta
											WHERE yhtio 			= '$kukarow[yhtio]'
											and tuoteno				= '$tilausrivirow[tuoteno]'
											and ostorivitunnus 		> 0
											and myyntirivitunnus 	= 0
											and sarjanumero 		= '$sarjarow[sarjanumero]'
											and era_kpl 			> 0
											and hyllyalue   		= '$tilausrivirow[hyllyalue]'
											and hyllynro    		= '$tilausrivirow[hyllynro]'
											and hyllyvali   		= '$tilausrivirow[hyllyvali]'
											and hyllytaso   		= '$tilausrivirow[hyllytaso]'
											ORDER BY era_kpl DESC, tunnus
											LIMIT 1";
								$erajaljella_res = pupe_query($query);

								// jos löytyy ostettuja eriä myytäväks niin mennään tänne
								if (mysql_num_rows($erajaljella_res) == 1) {
									$erajaljella_row = mysql_fetch_assoc($erajaljella_res);



									$sarjano_array[] = $erajaljella_row["tunnus"];
									$sarjano_kpl_array[$erajaljella_row["tunnus"]] = $erajaljella_row["era_kpl"];
								}
							}
							else {
								$sarjano_array[] = $sarjarow["tunnus"];
							}
						}
					}

					// muuvarastopaikka.php palauttaa tee=X jos törmättiin johonkin virheeseen
					$tee = "N";
					$kutsuja = "vastaanota.php";

					require("muuvarastopaikka.php");

					if ($eancheck[$tun] != '' and (int) $kirjoitin > 0) {
						$query = "SELECT komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kirjoitin'";
						$komres = pupe_query($query);
						$komrow = mysql_fetch_assoc($komres);
						$komento = $komrow['komento'];

						for($a = 0; $a < $tkpl; $a++) {
							require("inc/tulosta_tuotetarrat_tec.inc");
						}
					}

					if ($tee != 'X') {
						if ($oletuspaiv != '') {
							echo "<font class='message'>".t("Siirretään oletuspaikka")."</font><br><br>";

							$query = "	UPDATE tuotepaikat
										SET oletus 	= '',
										muuttaja	= '$kukarow[kuka]',
										muutospvm	= now()
										WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
							$rresult = pupe_query($query);

							$query = "	UPDATE tuotepaikat
										SET oletus = 'X',
										muuttaja	= '$kukarow[kuka]',
										muutospvm	= now()
										WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$uusiol'";
							$rresult = pupe_query($query);
						}
					}
				}

				if ($tee != 'X') {
					// jos kaikki meni ok niin päivitetään rivi vastaanotetuksi, laitetaan rivihinnaks tuotteen myyntihinat (tätä käytetään sit intrastatissa jos on tarve)
					$query = "	UPDATE tilausrivi, tuote
								SET tilausrivi.toimitettu	= '$kukarow[kuka]',
								toimitettuaika				= now(),
								kpl							= varattu,
								varattu						= 0,
								rivihinta					= round(tilausrivi.kpl * tuote.myyntihinta / if('$yhtiorow[alv_kasittely]' = '', (1+tuote.alv/100), 1), '$yhtiorow[hintapyoristys]')
								WHERE tilausrivi.tunnus		= '$tun'
								and tilausrivi.yhtio		= '$kukarow[yhtio]'
								and tilausrivi.tyyppi		= 'G'
								and tuote.yhtio				= tilausrivi.yhtio
								and tuote.tuoteno			= tilausrivi.tuoteno";
					$result = pupe_query($query);

					//Irrotetaan sarjanumerot
					if ($tilausrivirow["sarjanumeroseuranta"] != "") {
						$query = "	UPDATE sarjanumeroseuranta
									SET siirtorivitunnus = 0
									WHERE siirtorivitunnus		= '$tun'
									and yhtio					= '$kukarow[yhtio]'";
						$sarjares = pupe_query($query);
					}

					if ($toim == "MYYNTITILI") {
						$uprquery = "UPDATE tilausrivi SET
									hyllyalue 	= '$t1[$tun]',
									hyllynro 	= '$t2[$tun]',
									hyllyvali 	= '$t3[$tun]',
									hyllytaso 	= '$t4[$tun]'
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tun'";
						$uprresult = pupe_query($uprquery);
					}
				}

				if ($tee == "X") {
					// Summataan virhecountteria
					$virheita++;
				}
			}
		}

		echo "<br><br>";

		if ($virheita == 0) {

			//päivitetään otsikko vastaanotetuksi ja tapvmmään päivä
			$query  = "	SELECT sum(rivihinta) rivihinta
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$id'
						AND tyyppi  = 'G'";
			$result = pupe_query($query);
			$apusummarow = mysql_fetch_assoc($result);

			// Nää oli tossa updatessa mutta muuttujia ei ollut eikä tullut
			#bruttopaino 		= '$aputoimirow[bruttopaino]',
			#lisattava_era 		= '$aputoimirow[lisattava_era]',
			#vahennettava_era	= '$aputoimirow[vahennettava_era]'

			$query = "	UPDATE lasku
						SET alatila		= 'V',
						tapvm			= now(),
						summa			= '$apusummarow[rivihinta]'
						WHERE tunnus	= '$id'
						and yhtio		= '$kukarow[yhtio]'
						and tila		= 'G'";
			$result = pupe_query($query);
		}
	}

	// Tulostetaan vastaanotetut listaus
	if (($tee == "OK" or $tee == "paikat") and $id != '0' and $toim != "MYYNTITILI") {

		if ((int) $listaus > 0) {
			$query  = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$id'";
			$result = pupe_query($query);
			$laskurow = mysql_fetch_assoc($result);

			$query = "SELECT komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$listaus'";
			$komres = pupe_query($query);
			$komrow = mysql_fetch_assoc($komres);
			$komento["Vastaanotetut"] = $komrow['komento'];

			$otunnus = $laskurow["tunnus"];
			$mista = 'vastaanota';

			require('tulosta_purkulista.inc');
		}

		$id 	 = 0;
		$varasto = "";
	}
	elseif ($tee == "OK" and $id != '0' and $toim == "MYYNTITILI") {
		$id 	 = 0;
		$varasto = "";
	}

	if ($id == '') $id = 0;

	// meillä ei ole valittua tilausta
	if ($id == '0') {

		$formi  = "find";
		$kentta = "etsi";

		// tehdään etsi valinta
		echo "<form name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>";

		if ($toim == "MYYNTITILI" or $toim == "MYYNTITILIVASTAANOTA") {
			echo t("Etsi myyntitili");
		}
		else {
			echo t("Etsi siirtolistaa");
		}

		echo "</th>";
		echo "<td><input type='text' name='etsi'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>".t("Varasto")."</th>";
		echo "<td><select name='varastorajaus'>";
		echo "<option value=''>" . t('Kaikki varastot') . "</option>";

		$query  = "	SELECT tunnus, nimitys, maa
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
					ORDER BY tyyppi, nimitys";
		$vares = pupe_query($query);

		while ($varow = mysql_fetch_assoc($vares)) {
			$sel='';
			if (isset($varastorajaus) and $varow['tunnus'] == $varastorajaus) $sel = 'selected';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = strtoupper($varow['maa']);
			}

			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = pupe_query($query);
		echo "<tr><th>" . t('Maa') . "</th><td><select name='maa'>";

		echo "<option value=''>".t('Kaikki maat')."</option>";

		while ($maarow = mysql_fetch_assoc($vresult)) {
			$sel = (isset($maa) and $maarow['koodi'] == $maa) ? 'selected' : '';

			echo "<option value='{$maarow['koodi']}' $sel>{$maarow['nimi']}</option>";
		}

		echo "</select></td><td class='back'><input type='Submit' value='".t("Etsi")."'></td></tr></table></form><br>";

		$haku = '';
		if (is_string($etsi) and $etsi != "")  $haku = " and nimi LIKE '%$etsi%' ";
		if (is_numeric($etsi) and $etsi > 0) $haku = " and tunnus='$etsi' ";

		$myytili = " and tilaustyyppi != 'M' ";

		if ($toim == "MYYNTITILI") {
			$myytili = " and tilaustyyppi = 'M' ";
		}

		$varasto = '';

		if (isset($varastorajaus) and !empty($varastorajaus)) {
			$varasto .= ' AND lasku.clearing = '.(int) $varastorajaus;
		}

		if (isset($maa) and !empty($maa)) {
			$varasto .= " AND varastopaikat.maa = '".mysql_real_escape_string($maa)."'";
		}

		$query = "	SELECT tilausrivi.otunnus
					FROM tilausrivi
					JOIN lasku on lasku.yhtio = tilausrivi.yhtio
						and lasku.tunnus = tilausrivi.otunnus
						and lasku.tila = 'G'
						and lasku.alatila in ('C','B','D')
						$myytili
					LEFT JOIN varastopaikat ON lasku.clearing=varastopaikat.tunnus
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.toimitettu = ''
					and tilausrivi.keratty != ''
					$varasto
					GROUP BY tilausrivi.otunnus";
		$tilre = pupe_query($query);

		while ($tilrow = mysql_fetch_assoc($tilre)) {

			if ($toim == "MYYNTITILI") {
				$qnimi1 = 'Myyntitili';
				$qnimi2 = 'Vastaanottaja';
			}
			else {
				$qnimi1 = 'Siirtolista';
				$qnimi2 = 'Vastaanottava varasto';
			}

			// etsitään sopivia tilauksia
			$query = "	SELECT tunnus '$qnimi1', nimi '$qnimi2', date_format(luontiaika, '%Y-%m-%d') Laadittu, Laatija
						FROM lasku
						WHERE tunnus = '$tilrow[otunnus]'
						and tila = 'G'
						$haku
						$myytili
						and yhtio = '$kukarow[yhtio]'
						and alatila in ('C','B','D')
						ORDER by laadittu DESC";
			$result = pupe_query($query);

			//piirretään taulukko...
			if (mysql_num_rows($result) != 0) {

				while ($row = mysql_fetch_row($result)) {
					// piirretään vaan kerran taulukko-otsikot
					if ($boob == '') {
						$boob = 'kala';

						echo "<table>";
						echo "<tr>";
						for ($y=0; $y<mysql_num_fields($result); $y++)
							echo "<th align='left'>".t(mysql_field_name($result,$y))."</th>";
						echo "</tr>";
					}

					echo "<tr>";

					for ($y=0; $y<mysql_num_fields($result); $y++)
						echo "<td>$row[$y]</td>";

					echo "<form method='post'><td class='back'>
						  	<input type='hidden' name='id' value='$row[0]'>
							<input type='hidden' name='varastorajaus' value='$varastorajaus'>
							<input type='hidden' name='maa' value='$maa'>
						  	<input type='hidden' name='toim' value='$toim'>";

					if ($toim == "MYYNTITILI") {
						echo "<input type='submit' name='tila' value='".t("Toimita")."'></td></tr></form>";
					}
					else {
						echo "<input type='submit' name='tila' value='".t("Vastaanota")."'></td></tr></form>";
					}
				}
			}
		}

		if ($boob != '') {
			echo "</table>";
		}
		else {
			if ($toim == "MYYNTITILI") {
				echo "<font class='message'>".t("Yhtään toimitettavaa myyntitiliä ei löytynyt")."...</font>";
			}
			elseif ($toim == "MYYNTITILIVASTAANOTA") {
				echo "<font class='message'>".t("Yhtään vastaanotettavaa myyntitiliä ei löytynyt")."...</font>";
			}
			else {
				echo "<font class='message'>".t("Yhtään vastaanotettavaa siirtolistaa ei löytynyt")."...</font>";
			}
		}
	}

	if ($id != '0') {

		if ($toim == "MYYNTITILI") {
			$qnimi1 = 'Myyntitili';
			$qnimi2 = 'Vastaanottaja';
		}
		elseif ($toim == "MYYNTITILIVASTAANOTA") {
			$qnimi1 = 'Myyntitili';
			$qnimi2 = 'Vastaanottava varasto';
		}
		else {
			$qnimi1 = 'Siirtolista';
			$qnimi2 = 'Vastaanottava varasto';
		}

		//tässä on valittu tilaus
		$query = "	SELECT tunnus '$qnimi1',
					nimi '$qnimi2',
					date_format(luontiaika, '%Y-%m-%d')
					laadittu,
					laatija,
					clearing,
					if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, '<br>', toim_nimi)) asiakkaan_nimi,
					liitostunnus asiakkaan_tunnus
					FROM lasku
					WHERE tunnus = '$id'
					and tila = 'G'
					and yhtio = '$kukarow[yhtio]'
					and alatila in ('B','C','D')";
		$result = pupe_query($query);
		$row = mysql_fetch_array($result);

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--
				$(document).ready(function(){
					var taytasarake = function() {

						var sarake_id = $(this).attr('id').replace('taytasarake_', '');
						var teksti = $(this).val();

						$('input[id^='+sarake_id+']').each(
							function() {
								$(this).val(teksti);
								$(this).trigger('change');
							}
						);
					};

					$('input[id^=taytasarake_]').on('keyup change blur', taytasarake);
				});
				//-->
				</script>";

		echo "<table>";
		echo "<tr>";

		for ($y=0; $y < mysql_num_fields($result)-1; $y++) {
			echo "<th align='left'>".t(mysql_field_name($result,$y))."</th>";
		}

		if ($toim == "") {
			echo "<th>".t("Lue paikat tiedostosta")."</th>";
			echo "<th>".t("Lue paikat rivikommentista")."</th>";
			echo "<th>".t("Päivitetään oletuspaikka")."</th>";
		}

		echo "</tr>";
		echo "<tr>";

		for ($y=0; $y<mysql_num_fields($result)-1; $y++) {
			echo "<td>$row[$y]</td>";
		}

		if ($toim == "") {
			echo "<form method='post'>";
			echo "<input type='hidden' name='id' value='$id'>";
			echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
			echo "<input type='hidden' name='maa' value='$maa'>";
			echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
			echo "<input type='hidden' name='tee' value='mikrotila'>";
			echo "<td>";
			echo "<input type='submit' value='".t("Valitse tiedosto")."'>";
			echo "</td>";
			echo "</form>";

			echo "<form method='post'>";
			echo "<input type='hidden' name='id' value='$id'>";
			echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
			echo "<input type='hidden' name='maa' value='$maa'>";
			echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
			echo "<input type='hidden' name='tee' value='kommentista'>";
			echo "<td>";
			echo "<input type='submit' value='".t("Lue rivikommentista")."'>";
			echo "</td>";
			echo "</form>";
		}

		//hakukentät
		echo "<form method='post' name='siirtolistaformi'>";
		echo "<input type='hidden' name='id' value='$id'>";
		echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
		echo "<input type='hidden' name='maa' value='$maa'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='paikat'>";

		if ($toim == "") {
			echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
		}
		else {
			$query = "	SELECT tunnus
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and alkuhyllyalue = '!!M'
						and loppuhyllyalue = '!!M'";
			$tresult = pupe_query($query);
			$mrow = mysql_fetch_assoc($tresult);
			echo "<input type='hidden' name='varasto' value='$mrow[tunnus]'>";
		}

		if ($toim == "") {
			echo "<td>";
			$chk = '';

			if ($oletuspaiv != '') {
				$chk = 'checked';
			}

			echo "<input type='checkbox' name='oletuspaiv' $chk>";
			echo "</td>";
		}

		echo "</tr>";
		echo "</table><br>";

		//vastaanottavan varaston tiedot
		$query  = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$row[clearing]'";
		$vares = pupe_query($query);
		$varow2 = mysql_fetch_assoc($vares);

		$lisa = " and concat(rpad(upper('$varow2[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow2[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) ";
		$lisa .= " and concat(rpad(upper('$varow2[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow2[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) ";

		//siirtolistan rivit
		$query = "	SELECT tilausrivi.nimitys,
					tilausrivi.tuoteno,
					tilausrivi.tunnus,
					tilausrivi.varattu,
					tilausrivi.toimitettu,
					tilausrivi.hyllyalue,
					tilausrivi.hyllynro,
					tilausrivi.hyllyvali,
					tilausrivi.hyllytaso,
					tuote.ei_saldoa,
					concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
					concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'), lpad(upper(tilausrivi.hyllyvali), 5, '0'), lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$id'
					and tilausrivi.tyyppi  = 'G'
					and tilausrivi.varattu != 0
					and var not in ('P','J','S')
					ORDER BY sorttauskentta, tuoteno";
		$result = pupe_query($query);

		echo "<table>";

		//itse rivit
		echo "<tr>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Paikka")."</th>";
		echo "<th>".t("Määrä")."</th>";

		if ($toim == "MYYNTITILI") {
			echo "<th>".t("Asiakas")."</th>";
		}
		else {
			echo "<th>".t("Hyllyalue")."</th>";
			echo "<th>".t("Hyllynro")."</th>";
			echo "<th>".t("Hyllyväli")."</th>";
			echo "<th>".t("Hyllytaso")."</th>";
			echo "<th>".t("Varastopaikka")."</th>";
			echo "<th>".t("Paikan Lähde")."</th>";
			echo "<th>".t("EANkoodi")."</th>";
			echo "<th>".t("Tarrat")."</th></tr>";
		}

		while ($rivirow = mysql_fetch_assoc($result)) {

			if ($rivirow["ei_saldoa"] == "") {
				$query = "	SELECT tuotepaikat.hyllyalue t1, tuotepaikat.hyllynro t2, tuotepaikat.hyllyvali t3, tuotepaikat.hyllytaso t4,
							concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
							FROM tuotepaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivirow[tuoteno]'
							$lisa
							ORDER BY sorttauskentta";
				$presult = pupe_query($query);
				$privirow = mysql_fetch_assoc($presult);
			}
			else {
				$privirow = array();
			}

			// Näytetäänkö rivin vai tuotteen varastopaikka
			$lahde = "Tuote";

			if ($rivirow["ei_saldoa"] != "") {
				$privirow['t1'] = "SALDOTON-TUOTE";
				$lahde = "Tuote";
			}
			elseif ($privirow['t1'] == '' and $toim == "") { // ei löytynyt varastopaikkaa
				$privirow['t1'] = "";
				$privirow['t2'] = "";
				$privirow['t3'] = "";
				$privirow['t4'] = "";
				$lahde = "Vastaanottavaa paikkaa ei löydy";
			}

			if (isset($tunnus)) {
				foreach ($tunnus as $tun) {
					if ($tun == $rivirow["tunnus"]) {
						$privirow["t1"] = $t1[$tun];
						$privirow["t2"] = $t2[$tun];
						$privirow["t3"] = $t3[$tun];
						$privirow["t4"]	= $t4[$tun];
					}
				}
			}

			echo "<tr>";
			echo "<input type='hidden' name='tunnus[]' value='$rivirow[tunnus]'>";
			echo "<td>".t_tuotteen_avainsanat($rivirow, 'nimitys')."</td>";
			echo "<td>$rivirow[tuoteno]</td>";

			if ($rivirow["ei_saldoa"] != "") {
				echo "<td></td>";
			}
			elseif ($rivirow["hyllyalue"] == "!!M") {
				$asiakkaan_tunnus = (int) $rivirow["hyllynro"].$rivirow["hyllyvali"].$rivirow["hyllytaso"];
				$query = "	SELECT if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, ' / ', toim_nimi)) asiakkaan_nimi
							FROM asiakas
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND tunnus = '$asiakkaan_tunnus'";
				$asiakasresult = pupe_query($query);
				$asiakasrow = mysql_fetch_assoc($asiakasresult);
				echo "<td>{$asiakasrow["asiakkaan_nimi"]}</td>";
			}
			else  {
				echo "<td>$rivirow[paikka]</td>";
			}

			echo "<td>$rivirow[varattu]</td>";

			// Myyntitileillä on speciaali vastaanotto
			if ($toim == "MYYNTITILI") {

				// Tehdään asiakkaan tunnuksesta myyntitili-varastopaikka
				list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = myyntitili_varastopaikka($row["asiakkaan_tunnus"]);

				echo "<td>";
				echo "<input type='hidden' name='t1[$rivirow[tunnus]]' value='$hyllyalue' maxlength='5' size='5'>";
				echo "<input type='hidden' name='t2[$rivirow[tunnus]]' value='$hyllynro' maxlength='5' size='5'>";
				echo "<input type='hidden' name='t3[$rivirow[tunnus]]' value='$hyllyvali' maxlength='5' size='5'>";
				echo "<input type='hidden' name='t4[$rivirow[tunnus]]' value='$hyllytaso' maxlength='5' size='5'>";
				echo "{$row["asiakkaan_nimi"]}";
				echo "</td>";
			}
			else {

				if ($rivirow["ei_saldoa"] != "") {
					echo "<td colspan='6'></td>";
					echo "<input type='hidden' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxlength='5' size='5'>";
				}
				else {
					echo "<td><input type='text' id='t1[$rivirow[tunnus]]' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxlength='5' size='5'></td>";
					echo "<td><input type='text' id='t2[$rivirow[tunnus]]' name='t2[$rivirow[tunnus]]' value='$privirow[t2]' maxlength='5' size='5'></td>";
					echo "<td><input type='text' id='t3[$rivirow[tunnus]]' name='t3[$rivirow[tunnus]]' value='$privirow[t3]' maxlength='5' size='5'></td>";
					echo "<td><input type='text' id='t4[$rivirow[tunnus]]' name='t4[$rivirow[tunnus]]' value='$privirow[t4]' maxlength='5' size='5'></td>";

					//Missä tuotetta on?
					$query  = "	SELECT *
								FROM tuotepaikat
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$rivirow[tuoteno]'
								$lisa
								ORDER BY oletus DESC, hyllyalue, hyllynro, hyllyvali, hyllytaso";
					$vares = pupe_query($query);

					if (mysql_num_rows($vares) > 1) {
						echo "<td><select name='rivivarasto[$rivirow[tunnus]]'><option value='x'>Ei muutosta";

						while ($varow = mysql_fetch_assoc($vares)) {
							$sel='';
							echo "<option value='$varow[tunnus]' $sel>$varow[hyllyalue] $varow[hyllynro] $varow[hyllyvali] $varow[hyllytaso]</option>";
						}

						echo "</select></td>";
					}
					else {
						echo "<input type='hidden' name='rivivarasto[$rivirow[tunnus]]' value=''>";

						if (mysql_num_rows($vares) == 1) {
							$varow = mysql_fetch_assoc($vares);

							echo "<td>$varow[hyllyalue] $varow[hyllynro] $varow[hyllyvali] $varow[hyllytaso]</td>";
						}
						else {
							echo "<td><font class='error'>".t("Ei varastopaikkaa")."!</font></td>";
						}
					}

					// Valikko, josta voi valita muun varastopaikan
					echo "<td>$lahde</td>";
				}

				//haetaan eankoodi tuotteelta
				$query  = "	SELECT eankoodi
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivirow[tuoteno]'";
				$eanres = pupe_query($query);
				$eanrow = mysql_fetch_assoc($eanres);
				$eankoodi = $eanrow['eankoodi'];

				if ($eankoodi== 0) {
					$eankoodi = '';
				}
				echo "<td><input type='text' name='eankoodi[$rivirow[tunnus]]' value='$eankoodi' maxlength='13' size='13'></td>";

				//annetaan mahdollisuus tulostaa tuotetarroja
				$echk = '';
				if (isset($eancheck[$rivirow['tunnus']]) and $eancheck[$rivirow['tunnus']] != '') {
					$echk = "CHECKED";
				}
				echo "<td align='center'><input type='checkbox' name='eancheck[$rivirow[tunnus]]' $echk></td>";
			}

			echo "</tr>";
		}

		if ($toim != "MYYNTITILI") {
			echo "<tr><td colspan='4' class='back' align='right' valign='center'>",t("Täytä kaikki kentät"),":</td>";
			echo "<td><input type='text' id='taytasarake_t1' maxlength='5' size='5'></td>";
			echo "<td><input type='text' id='taytasarake_t2' maxlength='5' size='5'></td>";
			echo "<td><input type='text' id='taytasarake_t3' maxlength='5' size='5'></td>";
			echo "<td><input type='text' id='taytasarake_t4' maxlength='5' size='5'></td>";
			echo "</tr>";
		}

		echo "</table><br>";

		if ($toim != "MYYNTITILI") {
			echo "<table><tr><td>";
			echo t("Kirjoitin johon tuotetarrat tulostetaan")."<br>";

			$query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]'";
			$kires = pupe_query($query);

			echo "<select name='kirjoitin'>";
			echo "<option value=''>".t("Ei kirjoitinta")."</option>";

			while ($kirow = mysql_fetch_assoc($kires)) {
				if (isset($kirjoitin) and $kirow['tunnus'] == $kirjoitin) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select></td><td>";

			echo t("Kirjoitin johon vastaanotetut listaus tulostetaan")."<br>";

			mysql_data_seek($kires, 0);
			echo "<select name='listaus'>";
			echo "<option value='$kirow[tunnus]'>".t("Ei kirjoitinta")."</option>";

			while ($kirow = mysql_fetch_assoc($kires)) {
				if (isset($listaus) and $kirow['tunnus'] == $listaus) $select='SELECTED';
				elseif ($toim == '' and !isset($listaus) and $kirow['tunnus'] == $varow2['printteri9']) $select = 'selected';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select></td><td>";

			echo t("Vain listaus")."<br>";
			echo "<input type='checkbox' name='vainlistaus'></td>";
			echo "</table>";
		}
		echo "<br>";

		if ($toim == "MYYNTITILI") {
			echo "<input type='submit' name='Laheta' value='".t("Toimita myyntitili")."'>";
		}
		elseif ($toim == "MYYNTITILIVASTAANOTA") {
			echo "<input type='submit' name='Laheta' value='".t("Vastaanota myyntitili")."'>";
		}
		else {
			echo "<input type='submit' name='Laheta' value='".t("Vastaanota siirtolista")."'>";
		}

		echo "</form>";

	}

	require ("inc/footer.inc");
