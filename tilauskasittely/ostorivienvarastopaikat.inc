<?php

$query  = "	SELECT *
			from lasku
			where tunnus = '$otunnus'
			and yhtio 	 = '$kukarow[yhtio]'
			and tila 	 = 'K'";

$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	echo "<font style='error'>".t("VIRHE: Tilausta ei enää löydy")."!</font>";
	exit;
}

if ($tee == "valmis") {

	foreach($tunnukset as $tunnus) {

		$t1[$tunnus] = trim(strtoupper($t1[$tunnus]));
		$t2[$tunnus] = trim(strtoupper($t2[$tunnus]));
		$t3[$tunnus] = trim(strtoupper($t3[$tunnus]));
		$t4[$tunnus] = trim(strtoupper($t4[$tunnus]));

		$query = "	SELECT *
					FROM tilausrivi
					WHERE (tunnus='$tunnus' or perheid2='$tunnus')
					and yhtio='$kukarow[yhtio]'
					and tyyppi='O' and uusiotunnus='$otunnus'
					ORDER BY tunnus";
		$perheresult = pupe_query($query);

		if (mysql_num_rows($perheresult) == 0) {
			echo "<font style='error'>".t("Tilausrivi katosi!")."$query</font><br>";
		}
		else {

			while($tilausrivirow = mysql_fetch_assoc($perheresult)) {

				$query2 = '';

				// Jaaha mitäs tuotepaikalle pitäisi tehdä
				if ($tilausrivirow["perheid2"] != $tilausrivirow["tunnus"] and $tilausrivirow["perheid2"] > 0 and !isset($rivivarasto[$tilausrivirow["tunnus"]])) {

					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
								from tuotepaikat
								WHERE yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t1"]."'
								and hyllynro	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t2"]."'
								and hyllyvali	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t3"]."'
								and hyllytaso	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t4"]."'
								and tuoteno		= '$tilausrivirow[tuoteno]'";
					$result = pupe_query($query);
					//echo "FAIJALTA: $tilausrivirow[tuoteno] $query<br><br>";

					//	Lisätään nämä paikka arrayhin
					$t1[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t1"];
					$t2[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t2"];
					$t3[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t3"];
					$t4[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t4"];
				}
				elseif (($rivivarasto[$tunnus] != 'x') and ($rivivarasto[$tunnus] != '')) {  //Varastopaikka vaihdettiin pop-upista, siellä on paikan tunnus

					if ($yhtiorow['varastopaikkojen_maarittely'] == 'M' and strpos($rivivarasto[$tunnus], '##') !== FALSE) {

						list($oletus_paikan_tunnus, $uuden_paikan_tunnus, $montako_tuotetta_mahtuu, $paljon_pitaa_mahtua_muualle) = explode('##', $rivivarasto[$tunnus]);

						$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
									FROM tuotepaikat
									WHERE yhtio	= '{$kukarow['yhtio']}'
									AND tunnus 	= '{$oletus_paikan_tunnus}'
									AND tuoteno	= '{$tilausrivirow['tuoteno']}'";
						$result = pupe_query($query);

						// katsotaan löytyykö tuotteen uudelle paikalle jo tuotepaikka
						$query2 = "	SELECT '{$tilausrivirow['tuoteno']}' as tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
									FROM tuotepaikat
									WHERE yhtio	= '{$kukarow['yhtio']}'
									AND tunnus 	= '{$uuden_paikan_tunnus}'";
						$result2 = pupe_query($query2);
						$paikkarow2 = mysql_fetch_assoc($result2);

						// JÄMÄHAARA
						if ($paljon_pitaa_mahtua_muualle != 'kaikki') {
							$update_query = "	UPDATE tilausrivi SET
												varattu = '{$montako_tuotetta_mahtuu}'
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND tunnus = '{$tilausrivirow['tunnus']}'";
							$update_res = pupe_query($update_query);

							$insert_query = "INSERT INTO tilausrivi SET ";

							foreach ($tilausrivirow as $key => $val) {
								switch ($key) {
									case 'hyllyalue':
									case 'hyllynro':
									case 'hyllytaso':
									case 'hyllyvali':
										$insert_query .= "{$key}='{$paikkarow2[$key]}',";
										break;
									case 'varattu':
										$insert_query .= "varattu='{$paljon_pitaa_mahtua_muualle}',";
										break;
									case 'laatija':
										$insert_query .= "laatija='{$kukarow['kuka']}',";
										break;
									case 'laadittu':
										$insert_query .= "laadittu=now(),";
										break;
									case 'tunnus':
										break;
									default:
										$insert_query .= "{$key}='$val',";
								}
							}

							$insert_query = substr($insert_query, 0, -1);
							$insert_res = pupe_query($insert_query);

							$tilausrivirow['tunnus'] = mysql_insert_id();
						}

						unset($result);

						$t1[$tilausrivirow['tunnus']] = $paikkarow2['hyllyalue'];
						$t2[$tilausrivirow['tunnus']] = $paikkarow2['hyllynro'];
						$t3[$tilausrivirow['tunnus']] = $paikkarow2['hyllyvali'];
						$t4[$tilausrivirow['tunnus']] = $paikkarow2['hyllytaso'];
					}
					else {
						$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
									from tuotepaikat
									WHERE yhtio	= '$kukarow[yhtio]'
									and tunnus 	= '$rivivarasto[$tunnus]'
									and tuoteno	= '$tilausrivirow[tuoteno]'";
						$result = pupe_query($query);
					}

					//echo "VALIKKOON: $tilausrivirow[tuoteno] $query<br><br>";
				}
				else {

					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
								from tuotepaikat
								WHERE yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$t1[$tunnus]'
								and hyllynro	= '$t2[$tunnus]'
								and hyllyvali	= '$t3[$tunnus]'
								and hyllytaso	= '$t4[$tunnus]'
								and tuoteno		= '$tilausrivirow[tuoteno]'";
					$result = pupe_query($query);
					//echo "KÄSINSYÖTETTY: $tilausrivirow[tuoteno] $query<br><br>";
				}

				if (mysql_num_rows($result) == 0) {

					// Tämä on uusi kokonaan varastopaikka tälle tuotteelle, joten perustetaan se
					// Jos tuotteen eka paikka, se on oletuspaikka
					$query = "	SELECT tuoteno
								from tuotepaikat
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]'";
					$aresult = pupe_query($query);

					if (mysql_num_rows($aresult) == 0) {
						$oletus='X';
						//echo t("oletus")."<br>";
					}
					else {
						$oletus='';
					}

					if ($t1[$tilausrivirow["tunnus"]] != '' and $t2[$tilausrivirow["tunnus"]] != '' and $t3[$tilausrivirow["tunnus"]] != '' and $t4[$tilausrivirow["tunnus"]] != '') {

						//dubbelchekataan, ettei nyt varmasti mene duplikaattina mitään paikkaa
						$query = "	SELECT tuoteno
									FROM tuotepaikat
									WHERE yhtio		= '$kukarow[yhtio]'
									and hyllyalue	= '".$t1[$tilausrivirow["tunnus"]]."'
									and hyllynro	= '".$t2[$tilausrivirow["tunnus"]]."'
									and hyllyvali	= '".$t3[$tilausrivirow["tunnus"]]."'
									and hyllytaso	= '".$t4[$tilausrivirow["tunnus"]]."'
									and tuoteno		= '$tilausrivirow[tuoteno]'";
						$checkres = pupe_query($query);

						//jos duplikaattia ei löydy
						if (mysql_num_rows($checkres) == 0) {
							$virheita = 0;
							if (kuuluukovarastoon($t1[$tilausrivirow["tunnus"]],$t2[$tilausrivirow["tunnus"]],'') != 0) {

								$query = "	insert into tuotepaikat (hyllyalue,hyllynro,hyllyvali,hyllytaso,oletus,saldo,saldoaika,tuoteno,yhtio,laatija,luontiaika)
											values ('".$t1[$tilausrivirow["tunnus"]]."','".$t2[$tilausrivirow["tunnus"]]."','".$t3[$tilausrivirow["tunnus"]]."','".$t4[$tilausrivirow["tunnus"]]."','$oletus','0',now(),'$tilausrivirow[tuoteno]','$kukarow[yhtio]','$kukarow[kuka]',now())";
								$insre = pupe_query($query);

								$uusipaikka = mysql_insert_id();

								$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
											from tuotepaikat
											WHERE yhtio	= '$kukarow[yhtio]'
											and tunnus 	= '$uusipaikka'
											and tuoteno	= '$tilausrivirow[tuoteno]'";
								$result = pupe_query($query);

								$paikkarow = mysql_fetch_array($result);

								$query2 = "	INSERT into tapahtuma set
											yhtio		= '$kukarow[yhtio]',
											tuoteno 	= '$tilausrivirow[tuoteno]',
											kpl 		= '0',
											hinta		= '0',
											kplhinta	= '0',
											laji 		= 'uusipaikka',
											hyllyalue	= '".$t1[$tilausrivirow["tunnus"]]."',
											hyllynro	= '".$t2[$tilausrivirow["tunnus"]]."',
											hyllyvali	= '".$t3[$tilausrivirow["tunnus"]]."',
											hyllytaso	= '".$t4[$tilausrivirow["tunnus"]]."',
											selite		= '".t("Saavutettaessa lisättiin tuotepaikka")." ".$t1[$tilausrivirow["tunnus"]]." ".$t2[$tilausrivirow["tunnus"]]." ".$t3[$tilausrivirow["tunnus"]]." ".$t4[$tilausrivirow["tunnus"]]."',
											laatija 	= '$kukarow[kuka]',
											laadittu	= now()";
								$result2 = pupe_query($query2);

								echo "<font class='message'>".t("Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("perustetaan uusi paikka")." ".$t1[$tilausrivirow["tunnus"]]."-".$t2[$tilausrivirow["tunnus"]]."-".$t3[$tilausrivirow["tunnus"]]."-".$t4[$tilausrivirow["tunnus"]]."</font><br>";
							}
							else {
								echo "<font class='message'>".t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("annettu paikka")." ".$t1[$tilausrivirow["tunnus"]]."-".$t2[$tilausrivirow["tunnus"]]."-".$t3[$tilausrivirow["tunnus"]]."-".$t4[$tilausrivirow["tunnus"]]." ".t("ei kuulu mihinkään varastoon, ei voitu perustaa!!!")."</font><br>";
								$virheita++;
							}
						}
					}
					elseif($t3[$tilausrivirow["tunnus"]] == '' or $t4[$tilausrivirow["tunnus"]] == '') {
						echo t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno]. ".t("on annettava täydellinen varastopaikka")."!<br>";
					}
					else {
						echo t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno]. ".t("ei voitu perustaa tyhjää varastopaikkaa")."!<br>";
					}
				}
				else {
					$paikkarow = mysql_fetch_array($result);
				}

				//	Jos meillä on isätuotenumero niin laitetaan tuotepaikka talteen!!
				if(!isset($faijanmesta[$tilausrivirow["perheid2"]]) and $tilausrivirow["perheid2"] == $tilausrivirow["tunnus"]) {

					$faijanmesta[$tilausrivirow["perheid2"]]["t1"] = $paikkarow["hyllyalue"];
					$faijanmesta[$tilausrivirow["perheid2"]]["t2"] = $paikkarow["hyllynro"];
					$faijanmesta[$tilausrivirow["perheid2"]]["t3"] = $paikkarow["hyllyvali"];
					$faijanmesta[$tilausrivirow["perheid2"]]["t4"] = $paikkarow["hyllytaso"];
				}

				// Päivitetäään tilausrivi
				$query = "	UPDATE tilausrivi
							SET hyllyalue='$paikkarow[hyllyalue]', hyllynro='$paikkarow[hyllynro]', hyllyvali='$paikkarow[hyllyvali]', hyllytaso='$paikkarow[hyllytaso]'
							WHERE tunnus	= '$tilausrivirow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and tyyppi		= 'O'";
				$result = pupe_query($query);

				// Päivitetään myös sarjanumero-oliot jos meillä on niitä
				$query = "	SELECT sarjanumeroseuranta
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tilausrivirow[tuoteno]'";
				$tres = pupe_query($query);
				$trow = mysql_fetch_array($tres);

				if ($trow["sarjanumeroseuranta"] != '') {
					$query = "	UPDATE sarjanumeroseuranta
								SET hyllyalue	= '$paikkarow[hyllyalue]',
								hyllynro		= '$paikkarow[hyllynro]',
								hyllyvali		= '$paikkarow[hyllyvali]',
								hyllytaso		= '$paikkarow[hyllytaso]',
								muuttaja		= '$kukarow[kuka]',
								muutospvm		= now()
								WHERE yhtio			= '$kukarow[yhtio]'
								and tuoteno 		= '$tilausrivirow[tuoteno]'
								and ostorivitunnus	= '$tilausrivirow[tunnus]'";
					$result = pupe_query($query);
				}

				//jos halutaan tehdä tästä paikasta jolle nyt viedään saldoa, oli se uusi tai vanha niin poistetaan kaikilta paikoilta oletustieto ja tehdään tästä oletuspaikka
				if ($oletusbox[$tunnus]!= '' and $virheita == 0) {
					$query = "	UPDATE tuotepaikat
								SET oletus 		= '',
								muuttaja		= '$kukarow[kuka]',
								muutospvm		= now()
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$paikkarow[tuoteno]'";
					$upresult = pupe_query($query);

					$query = "	UPDATE tuotepaikat
								SET oletus 	= 'X',
								muuttaja	= '$kukarow[kuka]',
								muutospvm	= now()
								WHERE yhtio 	= '$kukarow[yhtio]'
								and tuoteno 	= '$paikkarow[tuoteno]'
								and hyllyalue	= '$paikkarow[hyllyalue]'
								and hyllynro	= '$paikkarow[hyllynro]'
								and hyllyvali	= '$paikkarow[hyllyvali]'
								and hyllytaso	= '$paikkarow[hyllytaso]'
								LIMIT 1";
					$upresult = pupe_query($query);

					$query = "	SELECT *
								FROM tuotepaikat
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$paikkarow[tuoteno]'
								and oletus != ''";
					$upresult = pupe_query($query);

					if (mysql_num_rows($upresult) == 0) {
						$query = "	UPDATE tuotepaikat
									SET oletus 	= 'X',
									muuttaja	= '$kukarow[kuka]',
									muutospvm	= now()
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$paikkarow[tuoteno]'
									ORDER BY hyllyalue,hyllynro,hyllyvali,hyllytaso LIMIT 1";
						$upresult = pupe_query($query);
						echo "<font class='error'>".t("Tuotteelta")." $paikkarow[tuoteno] ".t("katosi oletuspaikka")."!!! ".t("Päivitetään ensimmäinen joka löytyy oletuspaikaksi")."!!!<br>";
					}
					echo "<font class='message'>".t("Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("muutettiin oletuspaikaksi")." $paikkarow[hyllyalue]-$paikkarow[hyllynro]-$paikkarow[hyllyvali]-$paikkarow[hyllytaso]</font><br>";

				}
			}
		}
	}

	$toiminto = "";
	$ytunnus  = $laskurow["ytunnus"];
}

// ollaan valittu tilaus
if ($tee == "") {
	$lisa = '';
	$selx = '';
	$sely = '';

	if ($varasto == '')		$varasto = $laskurow['varasto'];
	if ($varasto == '0')	$varasto = '';
	if ($varasto == '')		$varasto = 'y';

	if ($varasto == 'x') {
		$lisa = "and oletus != ''";
		$selx = "SELECTED";
	}
	elseif ($varasto == 'y') {
		$lisa = "";
		$sely = "SELECTED";
	}
	else {
		$query  = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$varasto'";
		$vares = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Varastopaikka katosi")."!</font>";
			$varasto = "";
		}
		else {
			$varow = mysql_fetch_array($vares);
			$lisa =  " and tuotepaikat.hyllyalue <= '$varow[loppuhyllyalue]'";
			$lisa .= " and tuotepaikat.hyllyalue >= '$varow[alkuhyllyalue]'";

			$alkuhyllyalue = $varow["alkuhyllyalue"];
		}
	}

	echo "<br><table>";
	echo "<tr>";
	echo "<th>".t("saapuminen")."</th>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "</tr>";
	echo "<tr>
			<td>$laskurow[laskunro]</td>
			<td>$laskurow[ytunnus]</td>
			<td>$laskurow[nimi]</td>
			</tr>";
	echo "</table><br>";

	echo "	<form method='post'>";
	echo "	<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "	<input type='hidden' name='toiminto' value='varastopaikat'>
			<input type='hidden' name='otunnus' value='$otunnus'>";

	echo "<table><tr><th>".t("Saavuta saapuminen").":</th>
			<td><select name='varasto' onchange = 'submit()'>
			<option value='x' $selx>".t("Tuotteiden oletuspaikoille")."</option>
			<option value='y' $sely>".t("Rivellä oleville paikoille")."</option>";

	$query  = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	while ($varow = mysql_fetch_array($vares)) {
		$sel='';
		if ($varow['tunnus'] == $varasto) $sel = 'selected';
		echo "<option value='$varow[tunnus]' $sel>".t("Varastoon")." - $varow[nimitys]</option>";
	}

	echo "</select></td>";
	echo "</form>";

	if ($varasto == 'y') {
		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>
			<input type='hidden' name='toiminto' value='varastopaikat'>
			<input type='hidden' name='varasto' value='$varasto'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='hidden' name='taytapaikat' value='jees'>";

		echo "<td class='back'><input type='submit' value='".t("Täytä paikkakentät")."'></td>";
		echo "</form>";
	}

	echo "</tr>";
	echo "</table><br>";

	// otsikko valmis

	//haetaan ostorivit
	$query = "	SELECT tilausrivi.tuoteno, tilausrivi.tunnus, tilausrivi.perheid2,
				tilausrivi.hyllyalue r1, tilausrivi.hyllynro r2, tilausrivi.hyllyvali r3, tilausrivi.hyllytaso r4,
				tuote.tuotekorkeus, tuote.tuoteleveys, tuote.tuotesyvyys, tuote.tuotemassa, tuote.keraysvyohyke,
				group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno, tilausrivi.varattu,
				(select kpl from tilausrivi t2 where t2.yhtio=tilausrivi.yhtio and t2.tunnus=tilausrivi.perheid2 and t2.uusiotunnus='$otunnus') isakpl
				FROM tilausrivi
				JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno)
				LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
				WHERE tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.uusiotunnus='$otunnus'
				and tilausrivi.tyyppi = 'O'
				and tilausrivi.varattu <> 0
				group by tilausrivi.tunnus
				having (tilausrivi.perheid2=0 or tilausrivi.perheid2=tilausrivi.tunnus) or (isakpl != 0 and tilausrivi.perheid2 > 0 and (tilausrivi.hyllyalue='' or tilausrivi.hyllynro='' or tilausrivi.hyllyvali='' or tilausrivi.hyllytaso=''))
				ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno";
	$result = pupe_query($query);

	//kahtsotaan löytyikö yhtään tilausriviä
	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Yhtään tilausriviä ei löytynyt!")."</font>";
	}
	else {
		echo "<table>";

		echo "	<script language='javascript'>
				function WriteText(sarake) {
					var count = document.varastopaikkaformi.elements.length;

					for (j=0; j<count; j++) {
						var element = document.varastopaikkaformi.elements[j];

						if (sarake == 't1' && element.id.substring(0,2) == 't1') {
							element.value=document.varastopaikkaformi.t1_kaikki.value;
						}

						if (sarake == 't2' && element.id.substring(0,2) == 't2') {
							element.value=document.varastopaikkaformi.t2_kaikki.value;
						}

						if (sarake == 't3' && element.id.substring(0,2) == 't3') {
							element.value=document.varastopaikkaformi.t3_kaikki.value;
						}

						if (sarake == 't4' && element.id.substring(0,2) == 't4') {
							element.value=document.varastopaikkaformi.t4_kaikki.value;
						}
					}
				}
			</script> ";

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function toggleAll(toggleBox) {

					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;

					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].type == 'checkbox') {
							currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}

				//-->
				</script>";

		// hakukentät
		echo "<form name='varastopaikkaformi' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value='varastopaikat'>";
		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<input type='hidden' name='tee' value='valmis'>";

		// itse rivit
		echo "<tr>";
		echo "<th>".t("Toimittajan tuotenumero")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Määrä")."</th>";
		echo "<th>".t("Viedään paikalle")."</th>";
		echo "<th>".t("Tee tästä<br>Oletuspaikka")."</th>";
		echo "<th>".t("Hyllyalue")."</th>";
		echo "<th>".t("Hyllynro")."</th>";
		echo "<th>".t("Hyllyväli")."</th>";
		echo "<th>".t("Hyllytaso")."</th>";

		while ($rivirow = mysql_fetch_array($result)) {

			echo "<tr>";
			echo "<input type='hidden' name='tunnukset[]' value='$rivirow[tunnus]'>";
			echo "<td>$rivirow[toim_tuoteno]</td>";
			echo "<td>$rivirow[tuoteno]</td>";
			echo "<td>$rivirow[varattu]</td>";

			//Missä tuotetta on?
			$query  = "	SELECT *
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$rivirow[tuoteno]'
						ORDER BY oletus DESC, hyllyalue, hyllynro, hyllyvali, hyllytaso";
			$paikkares = pupe_query($query);

			if (mysql_num_rows($paikkares) >= 1) {
				echo "<td><select name='rivivarasto[$rivirow[tunnus]]' onchange = \"if(this.options[this.selectedIndex].value=='') { document.getElementById('oletusbox_{$rivirow["tunnus"]}').checked=true;} else {document.getElementById('oletusbox_{$rivirow["tunnus"]}').checked=false;}\">";
				echo "<option value=''>".t("Uusi paikka")." --></option>";

				$virheviesti = '';

				while ($paikkarow = mysql_fetch_array($paikkares)) {

					$query  = "	SELECT tunnus
								FROM varastopaikat
								WHERE yhtio = '$kukarow[yhtio]'
								and alkuhyllyalue <= '$paikkarow[hyllyalue]'
								and loppuhyllyalue >= '$paikkarow[hyllyalue]'";
					$vares = pupe_query($query);
					$varow = mysql_fetch_array($vares);

					$sel = "";

					// Valkataan oletuspaikka tässä vaiheessa jos ei viedä tiettyyn varastoon
					if(!is_numeric($varasto) and $paikkarow['oletus'] != '') {
						$sel = 'selected';
					}

					if (is_numeric($varasto) and $varow['tunnus'] == $varasto) {
						$sel = 'selected';
					}
					elseif($varasto == 'x' and $paikkarow['oletus'] != '') {
						$sel = 'selected';
					}
					elseif($varasto == 'y' and $rivirow['r1'] == $paikkarow["hyllyalue"] and $rivirow['r2'] == $paikkarow["hyllynro"] and $rivirow['r3'] == $paikkarow["hyllyvali"] and $rivirow['r4'] == $paikkarow["hyllytaso"]) {
						$sel = 'selected';
					}

					$ole = "";

					if ($paikkarow['oletus'] != '') {
						$ole = "(".t("oletus").")";

						if ($yhtiorow['varastopaikkojen_maarittely'] == 'M') {

							if ($rivirow['tuoteleveys'] == 0 or $rivirow['tuotekorkeus'] == 0 or $rivirow['tuotekorkeus'] == 0 or $rivirow['tuotemassa'] == 0) {
								$virheviesti = "<br /><font class='error'>".t("Tuotteen mitta-/painotiedot ovat virheelliset")."!</font>";
							}
							else {
								$query = "	SELECT *
											FROM varaston_hyllypaikat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND hyllyalue = '{$paikkarow['hyllyalue']}'
											AND hyllynro = '{$paikkarow['hyllynro']}'
											AND hyllyvali = '{$paikkarow['hyllyvali']}'
											AND hyllytaso = '{$paikkarow['hyllytaso']}'";
								$hyllypaikat_res = pupe_query($query);
								$hyllypaikat_row = mysql_fetch_assoc($hyllypaikat_res);

								$hyllypaikat_varasto = ($varasto != 'y' and $varasto != 'x') ? $varasto : $hyllypaikat_row['varasto'];

								$hyllypaikan_tilavuus 	= $hyllypaikat_row['leveys'] * $hyllypaikat_row['korkeus'] * $hyllypaikat_row['syvyys'];
								$tuotteen_tilavuus 		= $rivirow['tuoteleveys'] * $rivirow['tuotekorkeus'] * $rivirow['tuotesyvyys'];

								$paljon_hyllypaikalla_nyt 		= $tuotteen_tilavuus * $paikkarow['saldo'];
								$paljon_hyllypaikalla_vapaata 	= $hyllypaikan_tilavuus - $paljon_hyllypaikalla_nyt;

								$montako_tuotetta_mahtuu = floor($paljon_hyllypaikalla_vapaata / $tuotteen_tilavuus);

								if ($montako_tuotetta_mahtuu < 0 or $rivirow['tuoteleveys'] > $hyllypaikat_row['leveys'] or $rivirow['tuotekorkeus'] > $hyllypaikat_row['korkeus'] or $rivirow['tuotesyvyys'] > $hyllypaikat_row['syvyys'] or ($montako_tuotetta_mahtuu * $rivirow['tuotemassa']) > $hyllypaikat_row['maksimitaakka']) {
									$montako_tuotetta_mahtuu = 0;
								}

								$paljon_pitaa_mahtua_muualle = $rivirow['varattu'] - $montako_tuotetta_mahtuu;

								if ($paljon_pitaa_mahtua_muualle > 0) {

									// jos ii on nolla, lasketaan paikka mihin kaikki mahtuu
									// jos ii on 1, lasketaan mihin ylijäävät kappaleet mahtuu
									for ($ii = 0; $ii < 2; $ii++) {

										// jos tuotteita mahtuu 0 nykyiselle paikalle, niin ehdotetaan vain paikkaa mihin mahtuu KAIKKI tuotteet
										if ($ii > 0 and $montako_tuotetta_mahtuu == 0) continue;

										$kpllisa = $ii == 0 ? $rivirow['varattu'] : $paljon_pitaa_mahtua_muualle;
										$limitlisa = $ii == 0 ? "LIMIT 1" : "LIMIT 5";

										$query2 = "	SELECT DISTINCT
													ABS(ORD(LEFT('{$paikkarow['hyllyalue']}', 1)) - ORD(LEFT(varaston_hyllypaikat.hyllyalue, 1))) etaisyys,
													(varaston_hyllypaikat.korkeus * varaston_hyllypaikat.leveys * varaston_hyllypaikat.syvyys) tilavuus,
													floor(((varaston_hyllypaikat.korkeus * varaston_hyllypaikat.leveys * varaston_hyllypaikat.syvyys) / {$tuotteen_tilavuus})) paljon_mahtuu_kpl,
													varaston_hyllypaikat.hyllyalue,
													varaston_hyllypaikat.hyllynro,
													varaston_hyllypaikat.hyllyvali,
													varaston_hyllypaikat.hyllytaso,
													tuotepaikat.tunnus
													FROM varaston_hyllypaikat
													JOIN tuotepaikat ON (tuotepaikat.yhtio = varaston_hyllypaikat.yhtio
																		AND tuotepaikat.saldo = 0
																		AND tuotepaikat.hyllyalue = varaston_hyllypaikat.hyllyalue
																		AND tuotepaikat.hyllynro = varaston_hyllypaikat.hyllynro
																		AND tuotepaikat.hyllyvali = varaston_hyllypaikat.hyllyvali
																		AND tuotepaikat.hyllytaso = varaston_hyllypaikat.hyllytaso
																		AND tuotepaikat.oletus = '')
													WHERE varaston_hyllypaikat.yhtio = '{$kukarow['yhtio']}'
													AND varaston_hyllypaikat.korkeus >= '{$hyllypaikat_row['korkeus']}'
													AND varaston_hyllypaikat.leveys >= '{$hyllypaikat_row['leveys']}'
													AND varaston_hyllypaikat.syvyys >= '{$hyllypaikat_row['syvyys']}'
													AND varaston_hyllypaikat.minimi_korkeus <= '{$rivirow['tuotekorkeus']}'
													AND varaston_hyllypaikat.minimi_leveys <= '{$rivirow['tuoteleveys']}'
													AND varaston_hyllypaikat.minimi_syvyys <= '{$rivirow['tuotesyvyys']}'
													AND varaston_hyllypaikat.maksimitaakka >= ({$kpllisa} * {$rivirow['tuotemassa']})
													AND varaston_hyllypaikat.keraysvyohyke = '{$rivirow['keraysvyohyke']}'
													GROUP BY 1,2,3,4,5,6,7
													HAVING paljon_mahtuu_kpl >= {$kpllisa}
													ORDER BY 1,2,3,4,5,6,7
													{$limitlisa}";
										$loput_res = pupe_query($query2);

										if (mysql_num_rows($loput_res) > 0) {

											$virheviesti = '';

											while ($loput_row = mysql_fetch_assoc($loput_res)) {
												if ($ii == 0) {
													echo "<option value='{$paikkarow['tunnus']}##{$loput_row['tunnus']}##{$rivirow['varattu']}##kaikki'>",t("Vie kaikki paikalle")," $loput_row[hyllyalue] $loput_row[hyllynro] $loput_row[hyllyvali] $loput_row[hyllytaso]</option>";
												}
												else {
													echo "<option value='{$paikkarow['tunnus']}##{$loput_row['tunnus']}##{$montako_tuotetta_mahtuu}##{$paljon_pitaa_mahtua_muualle}'>",t("Vie")," $montako_tuotetta_mahtuu ",t("oletuspaikalle")," $paikkarow[hyllyalue] $paikkarow[hyllynro] $paikkarow[hyllyvali] $paikkarow[hyllytaso] ",t("ja loput")," $paljon_pitaa_mahtua_muualle ",t("paikalle")," $loput_row[hyllyalue] $loput_row[hyllynro] $loput_row[hyllyvali] $loput_row[hyllytaso]</option>";
												}
											}
										}
										else {
											$virheviesti = "<br /><font class='error'>".t("Tuotteet eivät mahdu varastoon")."! ".t("Syötä varastopaikka käsin").".</font>";
										}
									}
								}
							}
						}
					}

					$riv = "";

					if($rivirow['r1'] == $paikkarow["hyllyalue"] and $rivirow['r2'] == $paikkarow["hyllynro"] and $rivirow['r3'] == $paikkarow["hyllyvali"] and $rivirow['r4'] == $paikkarow["hyllytaso"]) {
						$riv = "(".t("rivillä").")";
					}

					echo "<option value='$paikkarow[tunnus]' $sel>$paikkarow[hyllyalue] $paikkarow[hyllynro] $paikkarow[hyllyvali] $paikkarow[hyllytaso] $ole $riv</option>";
				}

				echo "</select>{$virheviesti}</td>";

				echo "<td>".t("Oletus").":<input type='checkbox' id='oletusbox_{$rivirow["tunnus"]}' name='oletusbox[$rivirow[tunnus]]'></td>";
			}
			else {
				echo "<td><font class='error'>".t("Ei varastopaikkaa")."!";
				if ($rivirow["r1"] != "") {
					echo " (".t("rivillä")." $rivirow[r1]-$rivirow[r2]-$rivirow[r3]-$rivirow[r4])";
				}

				echo "<td></td>";
			}

			if ($varasto == 'y' and $taytapaikat == 'jees') {
				$rivlla1 = $rivirow["r1"];
				$rivlla2 = $rivirow["r2"];
				$rivlla3 = $rivirow["r3"];
				$rivlla4 = $rivirow["r4"];
			}

			echo "<td>",hyllyalue("t1[{$rivirow['tunnus']}]", $rivlla1),"</td>";
			echo "<td><input type='text' id='t2[$rivirow[tunnus]]' name='t2[$rivirow[tunnus]]' value='$rivlla2' maxlength='5' size='5'></td>";
			echo "<td><input type='text' id='t3[$rivirow[tunnus]]' name='t3[$rivirow[tunnus]]' value='$rivlla3' maxlength='5' size='5'></td>";
			echo "<td><input type='text' id='t4[$rivirow[tunnus]]' name='t4[$rivirow[tunnus]]' value='$rivlla4' maxlength='5' size='5'></td>";
		}

		echo "</tr><tr>";
		echo "<td colspan='4' class='back'></td>";
		echo "<td colspan='1' class='back'>Ruksaa kaikki:";
		echo "<input type='checkbox' name='chbox' onclick='toggleAll(this)'></td>";

		echo "</tr><tr>";
		echo "<td colspan='3' class='back'></td>";
		echo "<td colspan='2' align='right' class='back'>".t("Täytä kaikki kentät").":</td>";
		echo "<td align='right'><input type='text' name='t1_kaikki' onKeyUp='WriteText(\"t1\");' value='' maxlength='5' size='5'></td>";
		echo "<td><input type='text' name='t2_kaikki' onKeyUp='WriteText(\"t2\");' value='' maxlength='5' size='5'></td>";
		echo "<td><input type='text' name='t3_kaikki' onKeyUp='WriteText(\"t3\");' value='' maxlength='5' size='5'></td>";
		echo "<td><input type='text' name='t4_kaikki' onKeyUp='WriteText(\"t4\");' value='' maxlength='5' size='5'></td>";

		echo "</tr>";
		echo "</table>";

		if ($nappikeikalle == 'menossa') {
			echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
		}

		echo "<br><input type='submit' name='Laheta' value='".t("Päivitä varastopaikat")."'>";
		echo "</form>";

	}

	if ($toiminto != "") {
		echo "<hr>";
		echo "<form method='post'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";

		if ($nappikeikalle == 'menossa') {
			echo "<input type='hidden' name='otunnus' value='$otunnus'>";
			echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
		}

		echo "<input type='submit' value='".t("Takaisin saapumiselle")."'>";
		echo "</form>";
	}
}
