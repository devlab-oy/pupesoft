<?php

$query  = "select * from lasku where tunnus = '$otunnus' and yhtio = '$kukarow[yhtio]' and tila = 'K'";

$result = mysql_query($query) or pupe_error($query);

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
		$perheresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($perheresult) == 0) {
			echo "<font style='error'>".t("Tilausrivi katosi!")."$query</font><br>";
		}
		else {

			while($tilausrivirow = mysql_fetch_array($perheresult)) {

				// Jaaha mitäs tuotepaikalle pitäisi tehdä
				if($tilausrivirow["perheid2"] != $tilausrivirow["tunnus"] and $tilausrivirow["perheid2"] > 0 and !isset($rivivarasto[$tilausrivirow["tunnus"]])) {
					
					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
								from tuotepaikat
								WHERE yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t1"]."'
								and hyllynro	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t2"]."'
								and hyllyvali	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t3"]."'
								and hyllytaso	= '".$faijanmesta[$tilausrivirow["perheid2"]]["t4"]."'
								and tuoteno		= '$tilausrivirow[tuoteno]'";
					//echo "FAIJALTA: $tilausrivirow[tuoteno] $query<br><br>";
					
					//	Lisätään nämä paikka arrayhin
					$t1[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t1"];
					$t2[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t2"];
					$t3[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t3"];
					$t4[$tilausrivirow["tunnus"]] = $faijanmesta[$tilausrivirow["perheid2"]]["t4"];
					
				}
				elseif (($rivivarasto[$tunnus] != 'x') and ($rivivarasto[$tunnus] != '')) {  //Varastopaikka vaihdettiin pop-upista, siellä on paikan tunnus
					$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
								from tuotepaikat
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus 	= '$rivivarasto[$tunnus]'
								and tuoteno	= '$tilausrivirow[tuoteno]'";
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
					//echo "KÄSINSYÖTETTY: $tilausrivirow[tuoteno] $query<br><br>";
				}

				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {

					// Tämä on uusi kokonaan varastopaikka tälle tuotteelle, joten perustetaan se
					// Jos tuotteen eka paikka, se on oletuspaikka
					$query = "	SELECT tuoteno
								from tuotepaikat
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]'";
					$aresult = mysql_query($query) or pupe_error($query);

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
						$checkres = mysql_query($query) or pupe_error($query);

						//jos duplikaattia ei löydy
						if (mysql_num_rows($checkres) == 0) {
							$virheita = 0;
							if (kuuluukovarastoon($t1[$tilausrivirow["tunnus"]],$t2[$tilausrivirow["tunnus"]],'') != 0) {
								$query = "	insert into tuotepaikat (hyllyalue,hyllynro,hyllyvali,hyllytaso,oletus,saldo,saldoaika,tuoteno,yhtio,laatija,luontiaika)
											values ('".$t1[$tilausrivirow["tunnus"]]."','".$t2[$tilausrivirow["tunnus"]]."','".$t3[$tilausrivirow["tunnus"]]."','".$t4[$tilausrivirow["tunnus"]]."','$oletus','0',now(),'$tilausrivirow[tuoteno]','$kukarow[yhtio]','$kukarow[kuka]',now())";
								$insre = mysql_query($query) or pupe_error($query);
								
								$uusipaikka = mysql_insert_id();

								$query = "	SELECT tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
											from tuotepaikat
											WHERE yhtio	= '$kukarow[yhtio]'
											and tunnus 	= '$uusipaikka'
											and tuoteno	= '$tilausrivirow[tuoteno]'";
								$result = mysql_query($query) or pupe_error($query);

								$paikkarow = mysql_fetch_array($result);

								$query2 = "	INSERT into tapahtuma set
											yhtio		= '$kukarow[yhtio]',
											tuoteno 	= '$tilausrivirow[tuoteno]',
											kpl 		= '0',
											hinta		= '0',
											kplhinta	= '0',
											laji 		= 'uusipaikka',
											selite		= '".t("Saavutettaessa lisättiin tuotepaikka")." ".$t1[$tilausrivirow["tunnus"]]." ".$t2[$tilausrivirow["tunnus"]]." ".$t3[$tilausrivirow["tunnus"]]." ".$t4[$tilausrivirow["tunnus"]]."',
											laatija 	= '$kukarow[kuka]',
											laadittu	= now()";
								$result2 = mysql_query($query2) or pupe_error($query2);

								echo "<font class='message'>".t("Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("perustetaan uusi paikka")." ".$t1[$tilausrivirow["tunnus"]]."-".$t2[$tilausrivirow["tunnus"]]."-".$t3[$tilausrivirow["tunnus"]]."-".$t4[$tilausrivirow["tunnus"]]."</font><br>";
							}
							else {
								echo "<font class='message'>".t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("annettu paikka")." ".$t1[$tilausrivirow["tunnus"]]."-".$t2[$tilausrivirow["tunnus"]]."-".$t3[$tilausrivirow["tunnus"]]."-".$t4[$tunnus]." ".t("ei kuulu mihinkään varastoon, ei voitu perustaa!!!")."</font><br>";
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
				$result = mysql_query($query) or pupe_error($query);

				// Päivitetään myös sarjanumero-oliot jos meillä on niitä
				$query = "	SELECT sarjanumeroseuranta
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tilausrivirow[tuoteno]'";
				$tres = mysql_query($query) or pupe_error($query);
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
					$result = mysql_query($query) or pupe_error($query);
				}

				//jos halutaan tehdä tästä paikasta jolle nyt viedään saldoa, oli se uusi tai vanha niin poistetaan kaikilta paikoilta oletustieto ja tehdään tästä oletuspaikka
				if ($oletusbox[$tunnus]!= '' and $virheita == 0) {
					$query = "	UPDATE tuotepaikat
								SET oletus 		= '',
								muuttaja		= '$kukarow[kuka]', 
								muutospvm		= now()
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$paikkarow[tuoteno]'";
					$upresult = mysql_query($query) or pupe_error($query);

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
					$upresult = mysql_query($query) or pupe_error($query);

					$query = "	SELECT *
								FROM tuotepaikat
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$paikkarow[tuoteno]'
								and oletus != ''";
					$upresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($upresult) == 0) {
						$query = "	UPDATE tuotepaikat
									SET oletus 	= 'X',
									muuttaja	= '$kukarow[kuka]', 
									muutospvm	= now()
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$paikkarow[tuoteno]'
									ORDER BY hyllyalue,hyllynro,hyllyvali,hyllytaso LIMIT 1";
						$upresult = mysql_query($query) or pupe_error($query);
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
		$vares = mysql_query($query) or pupe_error($query);

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

	echo "<table>
		<tr>
			<th>".t("Keikka")."</th><td>$laskurow[laskunro]</td>
			<th>".t("Nimi")."</th><td>$laskurow[nimi]</td>
		</tr>";

	echo "	<form action='$PHP_SELF' method='post'>";
	echo "	<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "	<input type='hidden' name='toiminto' value='varastopaikat'>
			<input type='hidden' name='otunnus' value='$otunnus'>";

	echo "<tr><th>".t("Saavuta keikka").":</th>
			<td><select name='varasto' onchange = 'submit()'>
			<option value='x' $selx>".t("Tuotteiden oletuspaikoille")."</option>
			<option value='y' $sely>".t("Rivellä oleville paikoille")."</option>";

	$query  = "SELECT * FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares))
	{
		$sel='';
		if ($varow['tunnus'] == $varasto) $sel = 'selected';
		echo "<option value='$varow[tunnus]' $sel>".t("Varastoon")." - $varow[nimitys]</option>";
	}

	echo "</select></td></tr>";
	echo "</form>
		</table><br>";

	// otsikko valmis

	//haetaan ostorivit
	$query = "	SELECT tilausrivi.tuoteno, tilausrivi.tunnus, tilausrivi.perheid2, tilausrivi.hyllyalue r1, tilausrivi.hyllynro r2, tilausrivi.hyllyvali r3, tilausrivi.hyllytaso r4,
				group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno, tilausrivi.varattu, (select kpl from tilausrivi t2 where t2.yhtio=tilausrivi.yhtio and t2.tunnus=tilausrivi.perheid2 and t2.uusiotunnus='$otunnus') isakpl
				FROM tilausrivi
				JOIN tuote USING (yhtio, tuoteno)
				JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
				WHERE tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.uusiotunnus='$otunnus'
				and tilausrivi.tyyppi = 'O'
				and tilausrivi.varattu <> 0
				and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
				group by tilausrivi.tunnus
				having (tilausrivi.perheid2=0 or tilausrivi.perheid2=tilausrivi.tunnus) or (isakpl != 0 and tilausrivi.perheid2 > 0 and (tilausrivi.hyllyalue='' or tilausrivi.hyllynro='' or tilausrivi.hyllyvali='' or tilausrivi.hyllytaso=''))
				ORDER BY tuotteen_toimittajat.toim_tuoteno";
	$result = mysql_query($query) or pupe_error($query);

	//kahtsotaan löytyikö yhtään tilausriviä
	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Yhtään tilausriviä ei löydytynyt!")."</font>";
	}
	else {
		echo "<table>";

		echo "	<script language='javascript'>
				function WriteText(sarake) {
					var count = document.varastopaikkaformi.elements.length;

					for (j=0; j<count; j++) {
						var element = document.varastopaikkaformi.elements[j];

						if(sarake == 't1' && element.name.substring(0,2) == 't1') {
							element.value=document.varastopaikkaformi.t1_kaikki.value;
						}

						if(sarake == 't2' && element.name.substring(0,2) == 't2') {
							element.value=document.varastopaikkaformi.t2_kaikki.value;
						}

						if(sarake == 't3' && element.name.substring(0,2) == 't3') {
							element.value=document.varastopaikkaformi.t3_kaikki.value;
						}

						if(sarake == 't4' && element.name.substring(0,2) == 't4') {
							element.value=document.varastopaikkaformi.t4_kaikki.value;
						}
					}
				}
			</script> ";

		//hakukentät
		echo "<form action='$PHP_SELF' name='varastopaikkaformi' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value='varastopaikat'>";
		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<input type='hidden' name='tee' value='valmis'>";

		//itse rivit
		echo "<tr>";
		echo "<th>".t("Toimittajan tuotenumero")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Kpl")."</th>";
		echo "<th>".t("Viedään paikalle")."</th>";
		echo "<th>".t("Tee tästä<br>Oletuspaikka")."</th>";
		echo "<th>".t("Hyllyalue")."</th>";
		echo "<th>".t("Hyllynro")."</th>";
		echo "<th>".t("Hyllyväli")."</th>";
		echo "<th>".t("Hyllytaso")."</th>";

		while ($rivirow = mysql_fetch_array ($result)) {

			echo "<tr>";
			echo "<input type='hidden' name='tunnukset[]' value='$rivirow[tunnus]'>";
			echo "<td>$rivirow[toim_tuoteno]</td>";
			echo "<td>$rivirow[tuoteno]</td>";
			echo "<td>$rivirow[varattu]</td>";

			//Missä tuotetta on?
			$query  = "	SELECT *
						FROM tuotepaikat
						WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$rivirow[tuoteno]'";
			$paikkares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($paikkares) >= 1) {
				echo "<td><select name='rivivarasto[$rivirow[tunnus]]'>";
				echo "<option value=''>".t("Uusi paikka")." --></option>";

				while ($paikkarow = mysql_fetch_array($paikkares)) {

					$query  = "	SELECT tunnus
								FROM varastopaikat
								WHERE yhtio='$kukarow[yhtio]'
								and alkuhyllyalue<='$paikkarow[hyllyalue]'
								and loppuhyllyalue>='$paikkarow[hyllyalue]'";
					$vares = mysql_query($query) or pupe_error($query);
					$varow = mysql_fetch_array($vares);

					$sel = "";

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
					}

					$riv = "";
					if($rivirow['r1'] == $paikkarow["hyllyalue"] and $rivirow['r2'] == $paikkarow["hyllynro"] and $rivirow['r3'] == $paikkarow["hyllyvali"] and $rivirow['r4'] == $paikkarow["hyllytaso"]) {
						$riv = "(".t("rivillä").")";
					}

					echo "<option value='$paikkarow[tunnus]' $sel>$paikkarow[hyllyalue] $paikkarow[hyllynro] $paikkarow[hyllyvali] $paikkarow[hyllytaso] $ole $riv</option>";
				}
				
				echo "</select></td>";
				
				echo "<td>".t("Oletus").":<input type='checkbox' name='oletusbox[$rivirow[tunnus]]'></td>";
			}
			else {
				echo "<td><font class='error'>".t("Ei varastopaikkaa")."!";
				if ($rivirow["r1"] != "") {
					echo " (".t("rivillä")." $rivirow[r1]-$rivirow[r2]-$rivirow[r3]-$rivirow[r4])";
				}
				
				echo "<td></td>";
			}
			
			if ($varasto == 'y') {
				$rivlla1 = $rivirow["r1"];
				$rivlla2 = $rivirow["r2"];
				$rivlla3 = $rivirow["r3"];
				$rivlla4 = $rivirow["r4"];
			}

			echo "<td><input type='text' name='t1[$rivirow[tunnus]]' value='$rivlla1' maxzise='3' size='5'></td>";
			echo "<td><input type='text' name='t2[$rivirow[tunnus]]' value='$rivlla2' maxzise='3' size='5'></td>";
			echo "<td><input type='text' name='t3[$rivirow[tunnus]]' value='$rivlla3' maxzise='3' size='5'></td>";
			echo "<td><input type='text' name='t4[$rivirow[tunnus]]' value='$rivlla4' maxzise='3' size='5'></td>";

		}

		echo "</tr><tr>";
		echo "<td colspan='3' class='back'></td>";
		echo "<td colspan='2' align='right' class='back'>".t("Täytä kaikki kentät").":</td>";
		echo "<td><input type='text' name='t1_kaikki' onKeyUp='WriteText(\"t1\");' value='' maxzise='3' size='5'></td>";
		echo "<td><input type='text' name='t2_kaikki' onKeyUp='WriteText(\"t2\");' value='' maxzise='3' size='5'></td>";
		echo "<td><input type='text' name='t3_kaikki' onKeyUp='WriteText(\"t3\");' value='' maxzise='3' size='5'></td>";
		echo "<td><input type='text' name='t4_kaikki' onKeyUp='WriteText(\"t4\");' value='' maxzise='3' size='5'></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br><input type='submit' name='Laheta' value='".t("Päivitä varastopaikat")."'>";
		echo "</form>";

	}

	if ($toiminto != "") {
		echo "<hr>";
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
		echo "<input type='submit' value='".t("Takaisin keikalle")."'>";
		echo "</form>";
	}

}

?>
