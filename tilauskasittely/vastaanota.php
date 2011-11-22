<?php

	require ("../inc/parametrit.inc");

	if ($toim == "MYYNTITILI") {
		echo "<font class='head'>".t("Toimita myyntitili").":</font><hr>";
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
			$alkuresult = mysql_query($query) or pupe_error($query);


			while($alkurow = mysql_fetch_array($alkuresult)) {

				$bpaikka = explode(' ', $alkurow["kommentti"]);

				$ka = sizeof($bpaikka)-1;

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

		echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>";
		echo "<input type='hidden' name='id' value='$id'>";
		echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
		echo "<input type='hidden' name='tee' value='failista'>";

		echo "	<font class='message'>".t("Tiedostomuoto").":</font><br><br>

				<table>
				<tr><th colspan='5'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("M��r�")."</td><td>".t("Kommentti")."</td><td>".t("L�hett�v� varastopaikka")."</td><td>".t("Vastaanottava varastopaikka")."</td></tr>
				</table>
				<br>
				<table>
				<tr>";

		echo "
				<th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("L�heta")."'></td>
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
				die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
			}

			$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");

			// luetaan tiedosto alusta loppuun...
			$rivi = fgets($file, 4096);

			while (!feof($file)) {


				$tuoteno  	= '';
				$varattu  	= '';
				$teksti   	= '';
				$avarasto 	= '';
				$bvarasto	= '';

				// luetaan rivi tiedostosta..
				$poista	  = array("'", "\\");
				$rivi	  = str_replace($poista,"",$rivi);
				$rivi	  = explode("\t", trim($rivi));

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
					$alkuresult = mysql_query($query) or pupe_error($query);


					if (mysql_num_rows($alkuresult) == 1) {
						$alkurow = mysql_fetch_array($alkuresult);

						$bpaikka = explode('#', $bvarasto);

						$tunnus[] = $alkurow["tunnus"];
						$t1[$alkurow["tunnus"]] = $bpaikka[0];
						$t2[$alkurow["tunnus"]] = $bpaikka[1];
						$t3[$alkurow["tunnus"]] = $bpaikka[2];
						$t4[$alkurow["tunnus"]] = $bpaikka[3];

					}
				}

				$rivi = fgets($file, 4096);
			} // end while eof
		}
	}

	if ($tee == 'paikat' and $vainlistaus == '') {

		$virheita = 0;

		//k�yd��n kaikki rivit l�pi ja tarkastetaan varastopaika ja perustetaan uusia jos on tarvis
		foreach ($tunnus as $tun) {

			$t1[$tun] = trim($t1[$tun]);
			$t2[$tun] = trim($t2[$tun]);
			$t3[$tun] = trim($t3[$tun]);
			$t4[$tun] = trim($t4[$tun]);

			$t1[$tun] = strtoupper($t1[$tun]);

			$query = "	SELECT tilausrivi.tuoteno, tuote.ei_saldoa, tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso
						FROM tilausrivi
						JOIN tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.tunnus='$tun'
						and tilausrivi.yhtio='$kukarow[yhtio]'
						and tilausrivi.tyyppi='G'
						and tilausrivi.toimitettu=''";
			$result = mysql_query($query) or pupe_error($query);
			$tilausrivirow = mysql_fetch_array($result);

			if (mysql_num_rows($result) != 1) {
				echo "<font class='error'>".t("VIRHE: Rivi� ei l�ydy tai se on jo siirretty uudelle paikalle")."!</font><br>";
			}
			elseif ($tilausrivirow["ei_saldoa"] == "") {

				// Jaaha mit�s tuotepaikalle pit�isi tehd�
				if (($rivivarasto[$tun] != 'x') and ($rivivarasto[$tun] != '')) {  //Varastopaikka vaihdettiin pop-upista, siell� on paikan tunnus
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

				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {

					// T�m� on uusi kokonaan varastopaikka t�lle tuotteelle, joten perustetaan se
					// Jos tuotteen eka paikka, se on oletuspaikka

					$query  = "	SELECT tunnus
								FROM varastopaikat
								WHERE yhtio='$kukarow[yhtio]'
								and tunnus='$varasto'
								and alkuhyllyalue <= '$t1[$tun]'
								and loppuhyllyalue >= '$t1[$tun]'";
					$vares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($vares) == 1) {

						echo "<font class='message'>".t("Perustan")." ";

						$query = "	SELECT tuoteno
									from tuotepaikat
									WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]'";
						$aresult = mysql_query($query) or pupe_error($query);

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
							$ynsre = mysql_query($query) or pupe_error($query);

							$uusipaikka = mysql_insert_id();

							$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
										from tuotepaikat
										WHERE yhtio	= '$kukarow[yhtio]'
										and tunnus 	= '$uusipaikka'
										and tuoteno	= '$tilausrivirow[tuoteno]'";
							$result = mysql_query($query) or pupe_error($query);

							$paikkarow = mysql_fetch_array($result);

							echo t("Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("perustetaan uusi paikka")." $t1[$tun]-$t2[$tun]-$t3[$tun]-$t4[$tun]</font><br>";
						}
						else {
							echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno]. ".t("ei voitu perustaa tyhj�� varastopaikkaa")."!</font><br>";
							$virheita++;
						}
					}
					else {
						echo "<font class='error'>".t("VIRHE: Sy�tt�m�si varastopaikka ei kuulu kohdevaraston alueeseen")."!</font><br>";

						$t1[$tun] = '';
						$t2[$tun] = '';
						$t3[$tun] = '';
						$t4[$tun] = '';

						$virheita++;
					}
				}
				else {
					$paikkarow = mysql_fetch_array($result);

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
				$resulteankoodi = mysql_query($query) or pupe_error($query);
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
			$presult = mysql_query($query) or pupe_error($query);
			$prow = mysql_fetch_array($presult);

			if ($prow["inventointilista_aika"] > 0) {
				echo "<font class='error'>$tilausrivirow[hyllyalue]-$tilausrivirow[hyllynro]-$tilausrivirow[hyllyvali]-$tilausrivirow[hyllytaso] ".t("VIRHE: L�hdepaikalla on inventointi kesken, ei voida jatkaa")."!</font><br>";
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

		//k�yd��n kaikki riviti l�pi ja siirret��n saldoja
		foreach ($tunnus as $tun) {

			//nollataan n�m� t�rke�t
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
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font style='error'>".t("VIRHE: Rivi� ei l�ydy tai se on jo siirretty uudelle paikalle")."!</font><br>";

			}
			else {
				$tilausrivirow = mysql_fetch_array($result);

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
					$presult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($presult) != 1) {
						echo "<font style='error'>".t("VIRHE: Antavaa varastopaikkaa ei l�ydy")."$tuoteno!</font>";
						exit;
					}
					else {
						$prow = mysql_fetch_array($presult);

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
					$presult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($presult) != 1) {
						echo "<font style='error'>".t("VIRHE: Vastaanottavaa varastopaikkaa ei l�ydy")."$tuoteno!</font>";
					}
					else {
						$prow = mysql_fetch_array($presult);

						$minne = $prow["tunnus"];
						$uusiol = $prow["tunnus"];
					}

					//laitetaan sarjanumerot kuntoon
					if ($tilausrivirow["sarjanumeroseuranta"] != "") {
						$query = "	SELECT tunnus, era_kpl, sarjanumero
									FROM sarjanumeroseuranta
									WHERE siirtorivitunnus	= '$tun'
									and yhtio = '$kukarow[yhtio]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						$sarjano_array = array();

						while ($sarjarow = mysql_fetch_array($sarjares)) {
							if ($tilausrivirow["sarjanumeroseuranta"] == "E" or $tilausrivirow["sarjanumeroseuranta"] == "F" or $tilausrivirow["sarjanumeroseuranta"] == "G") {
								// er�numeroseurannassa pit�� etsi� ostotunnuksella er� josta kappaleeet otetaan

								// koitetaan l�yt�� vapaita ostettuja eri� mit� myyd�
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
								$erajaljella_res = mysql_query($query) or pupe_error($query);

								// jos l�ytyy ostettuja eri� myyt�v�ks niin menn��n t�nne
								if (mysql_num_rows($erajaljella_res) == 1) {
									$erajaljella_row = mysql_fetch_array($erajaljella_res);



									$sarjano_array[] = $erajaljella_row["tunnus"];
									$sarjano_kpl_array[$erajaljella_row["tunnus"]] = $erajaljella_row["era_kpl"];
								}
							}
							else {
								$sarjano_array[] = $sarjarow["tunnus"];
							}
						}
					}

					// muuvarastopaikka.php palauttaa tee=X jos t�rm�ttiin johonkin virheeseen
					$tee = "N";
					$kutsuja = "vastaanota.php";

					require("muuvarastopaikka.php");


					if ($eancheck[$tun]!='' and $kirjoitin!='') {
						$query = "select komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kirjoitin'";
						$komres = mysql_query($query) or pupe_error($query);
						$komrow = mysql_fetch_array($komres);
						$komento = $komrow['komento'];

						for($a = 0; $a < $tkpl; $a++) {
							require("inc/tulosta_tuotetarrat_tec.inc");
						}
					}

					if ($tee != 'X') {
						if ($oletuspaiv != '') {
							echo "<font class='message'>".t("Siirret��n oletuspaikka")."</font><br><br>";

							$query = "	UPDATE tuotepaikat
										SET oletus 	= '',
										muuttaja	= '$kukarow[kuka]',
										muutospvm	= now()
										WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
							$rresult = mysql_query($query) or pupe_error($query);

							$query = "	UPDATE tuotepaikat
										SET oletus = 'X',
										muuttaja	= '$kukarow[kuka]',
										muutospvm	= now()
										WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$uusiol'";
							$rresult = mysql_query($query) or pupe_error($query);
						}
					}
				}

				if ($tee != 'X') {
					// jos kaikki meni ok niin p�ivitet��n rivi vastaanotetuksi, laitetaan rivihinnaks tuotteen myyntihinat (t�t� k�ytet��n sit intrastatissa jos on tarve)
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
					$result = mysql_query($query) or pupe_error($query);

					//Irrotetaan sarjanumerot
					if ($tilausrivirow["sarjanumeroseuranta"] != "") {
						$query = "	UPDATE sarjanumeroseuranta
									SET siirtorivitunnus = 0
									WHERE siirtorivitunnus		= '$tun'
									and yhtio					= '$kukarow[yhtio]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}

					if ($toim == "MYYNTITILI") {
						$uprquery = "UPDATE tilausrivi SET
									hyllyalue 	= '$t1[$tun]',
									hyllynro 	= '$t2[$tun]',
									hyllyvali 	= '$t3[$tun]',
									hyllytaso 	= '$t4[$tun]'
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tun'";
						$uprresult = mysql_query($uprquery) or pupe_error($uprquery);
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

			//p�ivitet��n otsikko vastaanotetuksi ja tapvmm��n p�iv�
			$query  = "select sum(rivihinta) rivihinta from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$id' and tyyppi='G'";
			$result = mysql_query($query) or pupe_error($query);
			$apusummarow = mysql_fetch_array($result);

			$query = "	UPDATE lasku
						SET alatila							= 'V',
						tapvm								= now(),
						summa								= '$apusummarow[rivihinta]',
						bruttopaino 						= '$aputoimirow[bruttopaino]',
						lisattava_era 						= '$aputoimirow[lisattava_era]',
						vahennettava_era 					= '$aputoimirow[vahennettava_era]'
						WHERE lasku.tunnus					= '$id'
						and lasku.yhtio						= '$kukarow[yhtio]'
						and lasku.tila						= 'G'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	//Tulostetaan vastaanotetut listaus
	if (($tee == "OK" or $tee == "paikat") and $id != '0' and $toim != "MYYNTITILI") {

		$query  = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$id'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		$query = "SELECT komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$listaus'";
		$komres = mysql_query($query) or pupe_error($query);
		$komrow = mysql_fetch_array($komres);
		$komento["Vastaanotetut"] = $komrow['komento'];

		$otunnus = $laskurow["tunnus"];
		$mista = 'vastaanota';

		require('tulosta_purkulista.inc');
		$id = 0;
	}
	elseif ($tee == "OK" and $id != '0' and $toim == "MYYNTITILI") {
		$id = 0;
	}

	if ($id == '') $id = 0;

	// meill� ei ole valittua tilausta
	if ($id == '0') {

		$formi  = "find";
		$kentta = "etsi";

		// tehd��n etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>";

		if ($toim == "MYYNTITILI") {
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
		echo "<td><select name='varasto'>";
		echo "<option value=''>" . t('Kaikki varastot') . "</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares)) {
			$sel='';
			if ($varow['tunnus'] == $varasto) $sel = 'selected';

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
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<tr><th>" . t('Maa') . "</th><td><select name='maa'>";

		echo "<option value=''>" . t('Kaikki maat') . "</option>";

		while ($maarow = mysql_fetch_array($vresult)) {
			$sel = ($maarow['koodi'] == $_POST['maa']) ? 'selected' : '';

			echo "<option value='{$maarow['koodi']}' $sel>{$maarow['nimi']}</option>";
		}

		echo "</select></td><td class='back'><input type='Submit' value='".t("Etsi")."'></td></tr></table></form><br>";

		$haku = '';
		if (is_string($etsi))  $haku = " and nimi LIKE '%$etsi%' ";
		if (is_numeric($etsi)) $haku = " and tunnus='$etsi' ";

		$myytili = "";
		if ($toim == "MYYNTITILI") {
			$myytili = " and tilaustyyppi='M' ";
		}

		$varasto = '';
		if (isset($_POST['varasto']) and !empty($_POST['varasto'])) {
			$varasto = ' AND lasku.clearing=' . (int) $_POST['varasto'];
		}

		$maa = '';
		if (isset($_POST['maa']) and !empty($_POST['maa'])) {
			$varasto = " AND varastopaikat.maa='" . mysql_real_escape_string($_POST['maa']) . "'";
		}

		$query = "	SELECT otunnus
					FROM tilausrivi
					JOIN lasku on lasku.yhtio = tilausrivi.yhtio
						and lasku.tunnus = tilausrivi.otunnus
						and lasku.tila = 'G'
						and lasku.alatila in ('C','B','D')
					LEFT JOIN varastopaikat ON lasku.clearing=varastopaikat.tunnus
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and toimitettu = ''
					and keratty != ''
					$varasto
					GROUP BY otunnus";
		$tilre = mysql_query($query) or pupe_error($query);

		while ($tilrow = mysql_fetch_array($tilre)) {

			if ($toim == "MYYNTITILI") {
				$qnimi1 = 'Myyntitili';
				$qnimi2 = 'Vastaanottaja';
			}
			else {
				$qnimi1 = 'Siirtolista';
				$qnimi2 = 'Vastaanottava varasto';
			}

			// etsit��n sopivia tilauksia
			$query = "	SELECT tunnus '$qnimi1', nimi '$qnimi2', date_format(luontiaika, '%Y-%m-%d') Laadittu, Laatija
						FROM lasku
						WHERE tunnus = '$tilrow[0]'
						and tila = 'G'
						$haku
						$myytili
						and yhtio = '$kukarow[yhtio]'
						and alatila in ('C','B','D')
						ORDER by laadittu DESC";
			$result = mysql_query($query) or pupe_error($query);

			//piirret��n taulukko...
			if (mysql_num_rows($result) != 0) {

				while ($row = mysql_fetch_array($result)) {
					// piirret��n vaan kerran taulukko-otsikot
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

					echo "<form method='post' action='$PHP_SELF'><td class='back'>
						  <input type='hidden' name='id' value='$row[0]'>
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
				echo "<font class='message'>".t("Yht��n toimitettavaa myyntitili� ei l�ytynyt")."...</font>";
			}
			else {
				echo "<font class='message'>".t("Yht��n vastaanotettavaa siirtolistaa ei l�ytynyt")."...</font>";
			}
		}
	}

	if ($id != '0') {
		if ($toim == "MYYNTITILI") {
			$qnimi1 = 'Myyntitili';
			$qnimi2 = 'Vastaanottaja';
		}
		else {
			$qnimi1 = 'Siirtolista';
			$qnimi2 = 'Vastaanottava varasto';
		}

		//t�ss� on valittu tilaus
		$query = "	SELECT tunnus '$qnimi1', nimi '$qnimi2', date_format(luontiaika, '%Y-%m-%d') Laadittu, Laatija, clearing
					FROM lasku
					WHERE tunnus = '$id'
					and tila = 'G'
					and yhtio = '$kukarow[yhtio]'
					and alatila in ('B','C','D')";
		$result = mysql_query($query) or pupe_error($query);

		$row = mysql_fetch_array($result);

		echo "<table>";

		echo "	<script language='javascript'>
				function WriteText(sarake) {
					var count = document.siirtolistaformi.elements.length;

					for (j=0; j<count; j++) {
						var element = document.siirtolistaformi.elements[j];

						if(sarake == 't1' && element.name.substring(0,2) == 't1') {
							element.value=document.siirtolistaformi.t1_kaikki.value;
						}

						if(sarake == 't2' && element.name.substring(0,2) == 't2') {
							element.value=document.siirtolistaformi.t2_kaikki.value;
						}

						if(sarake == 't3' && element.name.substring(0,2) == 't3') {
							element.value=document.siirtolistaformi.t3_kaikki.value;
						}

						if(sarake == 't4' && element.name.substring(0,2) == 't4') {
							element.value=document.siirtolistaformi.t4_kaikki.value;
						}
					}
				}
			</script> ";

		echo "<tr>";

		for ($y=0; $y < mysql_num_fields($result)-1; $y++) {
			echo "<th align='left'>".t(mysql_field_name($result,$y))."</th>";
		}

		if ($toim == "") {
			echo "<th>Lue paikat tiedostosta</th>";
			echo "<th>Lue paikat rivikommentista</th>";
			echo "<th>P�ivitet��n oletuspaikka</th>";
		}

		echo "</tr>";
		echo "<tr>";

		for ($y=0; $y<mysql_num_fields($result)-1; $y++) {
			echo "<td>$row[$y]</td>";
		}

		if ($toim == "") {
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='id' value='$id'>";
			echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
			echo "<input type='hidden' name='tee' value='mikrotila'>";
			echo "<td>";
			echo "<input type='submit' value='".t("Valitse tiedosto")."'>";
			echo "</td>";
			echo "</form>";

			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='id' value='$id'>";
			echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
			echo "<input type='hidden' name='tee' value='kommentista'>";
			echo "<td>";
			echo "<input type='submit' value='".t("Lue rivikommentista")."'>";
			echo "</td>";
			echo "</form>";
		}

		//hakukent�t
		echo "<form action='$PHP_SELF' method='post' name='siirtolistaformi'>";
		echo "<input type='hidden' name='id' value='$id'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		if ($toim == "") {
			echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
		}
		else {
			$query = "	SELECT tunnus
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and alkuhyllyalue = '!!M'
						and loppuhyllyalue = '!!M'";
			$tresult = mysql_query($query) or pupe_error($query);
			$mrow = mysql_fetch_array($tresult);
			echo "<input type='hidden' name='varasto' value='$mrow[tunnus]'>";
		}
		echo "<input type='hidden' name='tee' value='paikat'>";

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
		$query  = "SELECT * FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' and tunnus = '$row[clearing]'";
		$vares = mysql_query($query) or pupe_error($query);
		$varow = mysql_fetch_array($vares);

		$lisa = " and concat(rpad(upper('$varow[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) ";
		$lisa .= " and concat(rpad(upper('$varow[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) ";

		//siirtolistan rivit
		$query = "	SELECT tilausrivi.nimitys, tilausrivi.tuoteno, tilausrivi.tunnus,  tilausrivi.varattu,
					concat_ws(' ',  tilausrivi.hyllyalue,  tilausrivi.hyllynro,  tilausrivi.hyllyvali,  tilausrivi.hyllytaso) paikka,
					tilausrivi.toimitettu, tuote.ei_saldoa,
					concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$id'
					and tilausrivi.tyyppi  = 'G'
					and tilausrivi.varattu != 0
					and var not in ('P','J','S')
					ORDER BY sorttauskentta, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";

		//itse rivit
		echo "<tr>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Paikka")."</th>";
		echo "<th>".t("M��r�")."</th>";
		echo "<th>".t("Hyllyalue")."</th>";
		echo "<th>".t("Hyllynro")."</th>";
		echo "<th>".t("Hyllyv�li")."</th>";
		echo "<th>".t("Hyllytaso")."</th>";

		if ($toim != "MYYNTITILI") {
			echo "<th>".t("Varastopaikka")."</th>";
			echo "<th>".t("Paikan L�hde")."</th>";
			echo "<th>".t("EANkoodi")."</th>";
			echo "<th>".t("Tarrat")."</th></tr>";
		}

		while ($rivirow = mysql_fetch_array ($result)) {

			if ($rivirow["ei_saldoa"] == "") {
				$query = "	SELECT tuotepaikat.hyllyalue t1, tuotepaikat.hyllynro t2, tuotepaikat.hyllyvali t3, tuotepaikat.hyllytaso t4,
							concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
							FROM tuotepaikat
							WHERE yhtio='$kukarow[yhtio]'
							and tuoteno='$rivirow[tuoteno]'
							$lisa
							ORDER BY sorttauskentta";
				$presult = mysql_query($query) or pupe_error($query);
				$privirow = mysql_fetch_array ($presult);
			}
			else {
				$privirow = array();
			}

			// N�ytet��nk� rivin vai tuotteen varastopaikka
			$lahde = "Tuote";

			if ($rivirow["ei_saldoa"] != "") {
				$privirow['t1'] = "SALDOTON-TUOTE";
				$lahde = "Tuote";
			}
			elseif ($privirow['t1'] == '' and $toim == "") { // ei l�ytynyt varastopaikkaa
				$privirow['t1'] = "";
				$privirow['t2'] = "";
				$privirow['t3'] = "";
				$privirow['t4'] = "";
				$lahde = "Vastaanottavaa paikkaa ei l�ydy";
			}
			elseif ($privirow['t1'] == '' and $toim == "MYYNTITILI") { // ei l�ytynyt varastopaikkaa
				$privirow['t1'] = "!!M";
				$privirow['t2'] = "0";
				$privirow['t3'] = "0";
				$privirow['t4'] = "0";
				$lahde = "Uusi paikka";
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
			else  {
				echo "<td>$rivirow[paikka]</td>";
			}

			echo "<td>$rivirow[varattu]</td>";

			if ($rivirow["ei_saldoa"] != "") {
				echo "<td colspan='6'></td>";
				echo "<input type='hidden' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxlength='5' size='5'>";
			}
			else {
				if ($toim == "") {
					echo "<td><input type='text' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxlength='5' size='5'></td>";
				}
				else {
					echo "<td align='center'><input type='hidden' name='t1[$rivirow[tunnus]]' value='!!M'>!!M</td>";
				}

				echo "<td><input type='text' name='t2[$rivirow[tunnus]]' value='$privirow[t2]' maxlength='5' size='5'></td>";
				echo "<td><input type='text' name='t3[$rivirow[tunnus]]' value='$privirow[t3]' maxlength='5' size='5'></td>";
				echo "<td><input type='text' name='t4[$rivirow[tunnus]]' value='$privirow[t4]' maxlength='5' size='5'></td>";

				//Miss� tuotetta on?
				$query  = "	SELECT *
							FROM tuotepaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivirow[tuoteno]'
							$lisa
							ORDER BY oletus DESC, hyllyalue, hyllynro, hyllyvali, hyllytaso";
				$vares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($vares) > 1) {
					echo "<td><select name='rivivarasto[$rivirow[tunnus]]'><option value='x'>Ei muutosta";

					while ($varow = mysql_fetch_array($vares)) {
						$sel='';
//						if ($varow['tunnus'] == $varasto) $sel = 'selected';
						echo "<option value='$varow[tunnus]' $sel>$varow[hyllyalue] $varow[hyllynro] $varow[hyllyvali] $varow[hyllytaso]</option>";
					}

					echo "</select></td>";
				}
				else {
					echo "<input type='hidden' name='rivivarasto[$rivirow[tunnus]]' value=''>";

					if (mysql_num_rows($vares) == 1) {
						$varow = mysql_fetch_array($vares);

						echo "<td>$varow[hyllyalue] $varow[hyllynro] $varow[hyllyvali] $varow[hyllytaso]</td>";
					}
					else {
						if ($toim != "MYYNTITILI") {
							echo "<td><font class='error'>Ei varastopaikkaa!</font></td>";
						}
					}
				}

				if ($toim != "MYYNTITILI") {
					//Valikko, josta voi valita muun varastopaikan
					echo "<td>$lahde</td>";
				}
			}

			if ($toim != "MYYNTITILI") {

				//haetaan eankoodi tuotteelta
				$query  = "	SELECT eankoodi
							FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$rivirow[tuoteno]'";
				$eanres = mysql_query($query) or pupe_error($query);
				$eanrow = mysql_fetch_array($eanres);
				$eankoodi = $eanrow['eankoodi'];

				if ($eankoodi== 0) {
					$eankoodi = '';
				}
				echo "<td><input type='text' name='eankoodi[$rivirow[tunnus]]' value='$eankoodi' maxlength='13' size='13'></td>";

				//annetaan mahdollisuus tulostaa tuotetarroja
				$echk = '';
				if ($eancheck[$rivirow['tunnus']] != '') {
					$echk = "CHECKED";
				}
				echo "<td align='center'><input type='checkbox' name='eancheck[$rivirow[tunnus]]' $echk></td>";

			}

			echo"</tr>";

		}

		echo "<tr><td colspan='4' class='back' align='right' valign='center'>",t("T�yt� kaikki kent�t"),":</td>";
		echo "<td><input type='text' name='t1_kaikki' onKeyUp='WriteText(\"t1\");' value='' maxlength='5' size='5'></td>";
		echo "<td><input type='text' name='t2_kaikki' onKeyUp='WriteText(\"t2\");' value='' maxlength='5' size='5'></td>";
		echo "<td><input type='text' name='t3_kaikki' onKeyUp='WriteText(\"t3\");' value='' maxlength='5' size='5'></td>";
		echo "<td><input type='text' name='t4_kaikki' onKeyUp='WriteText(\"t4\");' value='' maxlength='5' size='5'></td>";
		echo "</tr>";

		echo "</table><br>";

		if ($toim != "MYYNTITILI") {
			echo "<table><tr><td>";
			echo t("Kirjoitin johon tuotetarrat tulostetaan")."<br>";
			$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
			$kires = mysql_query($query) or pupe_error($query);
			echo "<select name='kirjoitin'>";
			echo "<option value='$kirow[tunnus]'>".t("Ei kirjoitinta")."</option>";

			while ($kirow=mysql_fetch_array($kires)) {
				if ($kirow['tunnus']==$kirjoitin) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select></td><td>";

			echo t("Kirjoitin johon vastaanotetut listaus tulostetaan")."<br>";

			mysql_data_seek($kires, 0);
			echo "<select name='listaus'>";
			echo "<option value='$kirow[tunnus]'>".t("Ei kirjoitinta")."</option>";

			while ($kirow=mysql_fetch_array($kires)) {
				if ($kirow['tunnus']==$listaus) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select></td><td>";

			echo t("Vain listaus")."<br>";
			echo "<input type='checkbox' name='vainlistaus'></td>";
			echo "</table>";
		}
		echo "<br><br><input type='submit' name='Laheta' value='".t("Valitse")."'>";
		echo "</form>";


	}

	require ("../inc/footer.inc");
?>
