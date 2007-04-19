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

			$query = "	select tunnus, kommentti
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

				<table border='0' cellpadding='3' cellspacing='2'>
				<tr><th colspan='5'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("Kpl")."</td><td>".t("Kommentti")."</td><td>".t("Lähettävä varastopaikka")."</td><td>".t("Vastaanottava varastopaikka")."</td></tr>
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
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
			$timeparts = explode(" ",microtime());
			$starttime = $timeparts[1].substr($timeparts[0],1);

			list($name,$ext) = split("\.", $_FILES['userfile']['name']);

			if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "CSV"))
			{
				die ("<font class='error'><br>".t("Ainoastaa .txt tai .csv tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0)
			{
				die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
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

					$query = "	select *
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

	if ($tee == 'paikat') {

		$virheita = 0;

		//käydään kaikki rivit läpi ja tarkastetaan varastopaika ja perustetaan uusia jos on tarvis
		foreach($tunnus as $tun) {

			$t1[$tun] = trim($t1[$tun]);
			$t2[$tun] = trim($t2[$tun]);
			$t3[$tun] = trim($t3[$tun]);
			$t4[$tun] = trim($t4[$tun]);

			$t1[$tun] = strtoupper($t1[$tun]);

			$query = "	SELECT tilausrivi.tuoteno, tuote.ei_saldoa
						FROM tilausrivi
						JOIN tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.tunnus='$tun'
						and tilausrivi.yhtio='$kukarow[yhtio]'
						and tilausrivi.tyyppi='G'
						and tilausrivi.toimitettu=''";
			$result = mysql_query($query) or pupe_error($query);
			$tilausrivirow = mysql_fetch_array($result);

			if (mysql_num_rows($result) != 1) {
				echo "<font style='error'>".t("VIRHE: Riviä ei löydy tai se on jo siirretty uudelle paikalle")."!</font><br>";
			}
			elseif ($tilausrivirow["ei_saldoa"] == "") {

				// Jaaha mitäs tuotepaikalle pitäisi tehdä
				if (($rivivarasto[$tun] != 'x') and ($rivivarasto[$tun] != '')) {  //Varastopaikka vaihdettiin pop-upista, siellä on paikan tunnus
					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
								from tuotepaikat
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus 	= '$rivivarasto[$tun]'
								and tuoteno	= '$tilausrivirow[tuoteno]'";
				}
				else {
					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
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

					// Tämä on uusi kokonaan varastopaikka tälle tuotteelle, joten perustetaan se
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
							$query = "	insert into tuotepaikat (hyllyalue, hyllynro, hyllyvali, hyllytaso, oletus, saldo, saldoaika, tuoteno, yhtio, laatija, luontiaika)
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
							echo t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno]. ".t("ei voitu perustaa tyhjää varastopaikkaa")."!<br>";
							$virheita++;
						}
					}
					else {
						echo t("VIRHE: Syöttämäsi varastopaikka ei kuulu kohdevaraston alueeseen")."!<br>";

						$t1[$tun] = '';
						$t2[$tun] = '';
						$t3[$tun] = '';
						$t4[$tun] = '';

						$virheita++;
					}
				}
				else {
					$paikkarow = mysql_fetch_array($result);

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
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font style='error'>".t("VIRHE: Riviä ei löydy tai se on jo siirretty uudelle paikalle")."!</font><br>";

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
						echo "<font style='error'>".t("VIRHE: Antavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
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
						echo "<font style='error'>".t("VIRHE: Vastaanottavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
					}
					else {
						$prow = mysql_fetch_array($presult);

						$minne = $prow["tunnus"];
						$uusiol = $prow["tunnus"];
					}

					//laitetaan sarjanumerot kuntoon
					if ($tilausrivirow["sarjanumeroseuranta"] != "") {
						$query = "	SELECT tunnus
									FROM sarjanumeroseuranta
									WHERE siirtorivitunnus		= '$tun'
									and yhtio					= '$kukarow[yhtio]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						$sarjano_array = array();

						while($sarjarow = mysql_fetch_array($sarjares)) {
							$sarjano_array[] = $sarjarow["tunnus"];
						}
					}

					// muuvarastopaikka.php palauttaa tee=X jos törmättiin johonkin virheeseen
					$tee = "N";
					$kutsuja = "vastaanota.php";

					require("../muuvarastopaikka.php");


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
							echo "<font class='message'>".t("Siirretään oletuspaikka")."</font><br><br>";

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
					// jos kaikki meni ok niin päivitetään rivi vastaanotetuksi, laitetaan rivihinnaks tuotteen myyntihinat (tätä käytetään sit intrastatissa jos on tarve)
					$query = "	UPDATE tilausrivi, tuote
								SET tilausrivi.toimitettu	= '$kukarow[kuka]',
								toimitettuaika				= now(),
								kpl							= varattu,
								varattu						= 0,
								rivihinta					= round(tilausrivi.kpl * tuote.myyntihinta / if('$yhtiorow[alv_kasittely]' = '', (1+tuote.alv/100), 1), 2)
								WHERE tilausrivi.tunnus		= '$tun'
								and tilausrivi.yhtio		= '$kukarow[yhtio]'
								and tilausrivi.tyyppi		= 'G'
								and tuote.yhtio				= tilausrivi.yhtio
								and tuote.tuoteno			= tilausrivi.tuoteno";
					$result = mysql_query($query) or pupe_error($query);
				}
				if ($tee == "X") {
					// Summataan virhecountteria
					$virheita++;
				}
			}
		}

		echo "<br><br>";

		if ($virheita == 0) {

			//päivitetään otsikko vastaanotetuksia ja tapvmmään päivä
			$query  = "select sum(rivihinta) rivihinta from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$id' and tyyppi='G'";
			$result = mysql_query($query) or pupe_error($query);
			$apusummarow = mysql_fetch_array($result);

			// katotaan onko toimitustavalle jotain intrastat oletuksia
			$query  = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$id'";
			$result = mysql_query($query) or pupe_error($query);
			$apulaskurow = mysql_fetch_array($result);

			// katotaan onko toimitustavalle jotain intrastat oletuksia
			$query  = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$apulaskurow[toimitustapa]'";
			$result = mysql_query($query) or pupe_error($query);
			$aputoimirow = mysql_fetch_array($result);

			// clearing on kohdevarasto
			$query  = "select maa from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$apulaskurow[clearing]'";
			$result = mysql_query($query) or pupe_error($query);
			$apuvararow = mysql_fetch_array($result);
			$maa_maara = $apuvararow["maa"];

			// varasto on lähdevarasto
			$query  = "select maa from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$apulaskurow[varasto]'";
			$result = mysql_query($query) or pupe_error($query);
			$apuvararow = mysql_fetch_array($result);
			$maa_lahetys = $apuvararow["maa"];

			$query = "	UPDATE lasku
						SET alatila							= 'V',
						tapvm								= now(),
						summa								= '$apusummarow[rivihinta]',
						maa_maara							= '$maa_maara',
						maa_lahetys							= '$maa_lahetys',
						kauppatapahtuman_luonne 			= '$aputoimirow[kauppatapahtuman_luonne]',
						kuljetusmuoto						= '$aputoimirow[kuljetusmuoto]',
						sisamaan_kuljetus					= '$aputoimirow[sisamaan_kuljetus]',
						sisamaan_kuljetusmuoto  			= '$aputoimirow[sisamaan_kuljetusmuoto]',
						sisamaan_kuljetus_kansallisuus		= '$aputoimirow[sisamaan_kuljetus_kansallisuus]',
						kontti								= '$aputoimirow[kontti]',
						aktiivinen_kuljetus 				= '$aputoimirow[aktiivinen_kuljetus]',
						aktiivinen_kuljetus_kansallisuus	= '$aputoimirow[aktiivinen_kuljetus_kansallisuus]',
						poistumistoimipaikka 				= '$aputoimirow[poistumistoimipaikka]',
						poistumistoimipaikka_koodi 			= '$aputoimirow[poistumistoimipaikka_koodi]',
						bruttopaino 						= '$aputoimirow[bruttopaino]',
						lisattava_era 						= '$aputoimirow[lisattava_era]',
						vahennettava_era 					= '$aputoimirow[vahennettava_era]'
						WHERE lasku.tunnus					= '$id'
						and lasku.yhtio						= '$kukarow[yhtio]'
						and lasku.tila						= 'G'";
			$result = mysql_query($query) or pupe_error($query);
			$id = 0;
		}
	}

	if ($id == '') $id = 0;

	// meillä ei ole valittua tilausta
	if ($id == '0') {

		$formi  = "find";
		$kentta = "etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi siirtolistaa").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku = '';
		if (is_string($etsi))  $haku = " and nimi LIKE '%$etsi%' ";
		if (is_numeric($etsi)) $haku = " and tunnus='$etsi' ";

		$myytili = "";
		if ($toim == "MYYNTITILI") {
			$myytili = " and tilaustyyppi='M' ";
		}

		$query = "	select distinct otunnus, count(rahtikirjat.tunnus) rtunnuksia, ultilno
					from tilausrivi
					JOIN lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.tila='G' and lasku.alatila in ('B','C','D')
					LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
					where tilausrivi.yhtio='$kukarow[yhtio]'
					and toimitettu=''
					and keratty!=''
					GROUP BY 1
					HAVING ultilno not in ('-1','-2') or rtunnuksia > 0";
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

			// etsitään sopivia tilauksia
			$query = "	SELECT tunnus '$qnimi1', nimi '$qnimi2', date_format(luontiaika, '%Y-%m-%d') Laadittu, Laatija
						FROM lasku
						WHERE tunnus = '$tilrow[0]'
						and tila = 'G'
						$haku
						$myytili
						and yhtio = '$kukarow[yhtio]'
						and alatila in ('B','C','D')
						ORDER by laadittu desc";
			$result = mysql_query($query) or pupe_error($query);

			//piirretään taulukko...
			if (mysql_num_rows($result) != 0) {

				while ($row = mysql_fetch_array($result)) {
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
				echo "<font class='message'>".t("Yhtään toimitettavaa myyntitiliä ei löytynyt")."...</font>";
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
		else {
			$qnimi1 = 'Siirtolista';
			$qnimi2 = 'Vastaanottava varasto';
		}

		//tässä on valittu tilaus
		$query = "	SELECT tunnus '$qnimi1', nimi '$qnimi2', date_format(luontiaika, '%Y-%m-%d') Laadittu, Laatija, clearing
					FROM lasku
					WHERE tunnus = '$id'
					and tila = 'G'
					and yhtio = '$kukarow[yhtio]'
					and alatila in ('B','C','D')";
		$result = mysql_query($query) or pupe_error($query);

		$row = mysql_fetch_array($result);

		echo "<table>";
		echo "<tr>";

		for ($y=0; $y < mysql_num_fields($result)-1; $y++) {
			echo "<th align='left'>".t(mysql_field_name($result,$y))."</th>";
		}

		if ($toim == "") {
			echo "<th>Lue paikat tiedostosta</th>";
			echo "<th>Lue paikat rivikommentista</th>";
			echo "<th>Päivitetään oletuspaikka</th>";
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

		//hakukentät
		echo "<form action='$PHP_SELF' method='post'>";
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

			$cchk = '';

			if ($oletuspaiv != '') {
				$cchk = "CHECKED";
			}

			echo "<input type='checkbox' name='oletuspaiv' $cchk>";
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
					ORDER BY sorttauskentta, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";

		//itse rivit
		echo "<tr>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Paikka")."</th>";
		echo "<th>".t("Määrä")."</th>";
		echo "<th>".t("Hyllyalue")."</th>";
		echo "<th>".t("Hyllynro")."</th>";
		echo "<th>".t("Hyllyväli")."</th>";
		echo "<th>".t("Hyllytaso")."</th>";

		if ($toim != "MYYNTITILI") {
			echo "<th>".t("Varastopaikka")."</th>";
			echo "<th>".t("Paikan Lähde")."</th>";
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

			// Näytetäänkö rivin vai tuotteen varastopaikka
			$lahde="Tuote";

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
			elseif ($privirow['t1'] == '' and $toim == "MYYNTITILI") { // ei löytynyt varastopaikkaa
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
			echo "<td>".asana('nimitys_',$rivirow['tuoteno'],$rivirow['nimitys'])."</td>";
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
				echo "<input type='hidden' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxzise='3' size='5'>";
			}
			else {
				if ($toim == "") {
					echo "<td><input type='text' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxzise='3' size='5'></td>";
				}
				else {
					echo "<td align='center'><input type='hidden' name='t1[$rivirow[tunnus]]' value='!!M'>!!M</td>";
				}

				echo "<td><input type='text' name='t2[$rivirow[tunnus]]' value='$privirow[t2]' maxzise='3' size='5'></td>";
				echo "<td><input type='text' name='t3[$rivirow[tunnus]]' value='$privirow[t3]' maxzise='3' size='5'></td>";
				echo "<td><input type='text' name='t4[$rivirow[tunnus]]' value='$privirow[t4]' maxzise='3' size='5'></td>";

				//Missä tuotetta on?
				$query  = "	SELECT *
							FROM tuotepaikat
							WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$rivirow[tuoteno]' $lisa";
				$vares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($vares) > 1) {
					echo "<td><select name='rivivarasto[$rivirow[tunnus]]'><option value='x'>Ei muutosta";

					while ($varow = mysql_fetch_array($vares)) {
						$sel='';
						if ($varow['tunnus'] == $varasto) $sel = 'selected';
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
				echo "<td><input type='text' name='eankoodi[$rivirow[tunnus]]' value='$eankoodi' maxzise='13' size='13'></td>";

				//annetaan mahdollisuus tulostaa tuotetarroja
				$echk = '';
				if ($eancheck[$rivirow['tunnus']] != '') {
					$echk = "CHECKED";
				}
				echo "<td align='center'><input type='checkbox' name='eancheck[$rivirow[tunnus]]' $echk></td>";
			}

			echo"</tr>";

		}

		echo "</table><br>";

		if ($toim != "MYYNTITILI") {

			echo t("Kirjoitin johon tuotetarrat tulostetaan")."<br>";
			$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
			$kires = mysql_query($query) or pupe_error($query);
			echo "<td><select name='kirjoitin'>";
			echo "<option value='$kirow[tunnus]'>".t("Ei kirjoitinta")."</option>";

			while ($kirow=mysql_fetch_array($kires)) {
				if ($kirow['tunnus']==$kirjoitin) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select></td>";
		}
		echo "<br><br><input type='submit' name='Laheta' value='".t("Valitse")."'>";
		echo "</form>";

	}

	require ("../inc/footer.inc");
?>
