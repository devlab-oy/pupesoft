<?php

	require ("inc/parametrit.inc");

	if (!isset($konserni)) 	$konserni = '';
	if (!isset($tee)) 		$tee = '';
	if (!isset($oper)) 		$oper = '';
	
	echo "<font class='head'>".t("Yhdistä asiakkaita")."</font><hr>";

	if ($tee == 'YHDISTA' and $jataminut != '' and count($yhdista) != '') {

		// tässä on jätettävän asiakkaan tiedot kannasta. ei saa koskea kirveellä
		$jquery	= "	SELECT *
					FROM asiakas
					where yhtio = '$kukarow[yhtio]'
					and tunnus = '$jataminut' ";
		$jresult = mysql_query($jquery) or pupe_error($jquery);
		$jrow = mysql_fetch_assoc($jresult);

		echo "<br>".t("Jätetään asiakas").": $jrow[ytunnus] $jrow[nimi] ".$jrow['osoite']." ".$jrow['postino']." ".$jrow['postitp']."<br>";

		// Otetaan jätettävä pois poistettavista jos se on sinne ruksattu
		unset($yhdista[$jataminut]);

		foreach ($yhdista as $haettava) {

			// haetaan "Yhdistettävän" firman tiedot esille niin saadaan oikeat parametrit.
			$asquery = "SELECT * FROM asiakas WHERE yhtio='$kukarow[yhtio]' AND tunnus = '{$haettava}'";
			$asresult = mysql_query($asquery) or pupe_error($asquery);

			if (mysql_num_rows($asresult) == 1) {

				$asrow = mysql_fetch_assoc($asresult);

				echo "<br>".t("Yhdistetään").": $asrow[ytunnus] $asrow[nimi] ".$asrow['osoite']." ".$asrow['postino']." ".$asrow['postitp']."<br>";

				// haetaan asiakashinta ensin Ytunnuksella.
				$hquery = "	SELECT * FROM asiakashinta WHERE ytunnus = '$asrow[ytunnus]' AND yhtio ='$kukarow[yhtio]'";
				$hresult = mysql_query($hquery) or pupe_error($hquery);

				if (mysql_num_rows($hresult) == 0) {
					echo t("Ei asiakashintoja y-tunnuksella").".<br>";
				}
				else {
					while ($ahrow = mysql_fetch_assoc($hresult)) {
						$ahinsert = "INSERT INTO asiakashinta SET
									yhtio         		= '$kukarow[yhtio]',
									tuoteno          	= '$ahrow[tuoteno]',
									ryhma            	= '$ahrow[ryhma]',
									asiakas          	= '',
									ytunnus          	= '$jrow[ytunnus]',
									asiakas_ryhma    	= '$ahrow[asiakas_ryhma]',
									asiakas_segmentti	= '$ahrow[asiakas_segmentti]',
									piiri            	= '$ahrow[piiri]',
									hinta            	= '$ahrow[hinta]',
									valkoodi         	= '$ahrow[valkoodi]',
									minkpl           	= '$ahrow[minkpl]',
									maxkpl           	= '$ahrow[maxkpl]',
									alkupvm          	= '$ahrow[alkupvm]',
									loppupvm         	= '$ahrow[loppupvm]',
									laji             	= '$ahrow[laji]',
									laatija          	= '$kukarow[kuka]',
									luontiaika       	= now()";
						$ahinsertresult = mysql_query($ahinsert) or pupe_error($ahinsert);
					}
				}

				// haetaan asiakashinta sitten asiakastunnuksella.
				$hquery = "	SELECT * FROM asiakashinta WHERE asiakas = '$asrow[tunnus]' AND yhtio ='$kukarow[yhtio]'";
				$hresult = mysql_query($hquery) or pupe_error($hquery);

				if (mysql_num_rows($hresult) == 0) {
					echo t("Ei asiakashintoja asiakastunnuksella").".<br>";
				}
				else {
					while ($ahrow = mysql_fetch_assoc($hresult)) {
						$ahinsert = "INSERT INTO asiakashinta SET
									yhtio         		= '$kukarow[yhtio]',
									tuoteno          	= '$ahrow[tuoteno]',
									ryhma            	= '$ahrow[ryhma]',
									asiakas          	= '$jrow[tunnus]',
									ytunnus          	= '',
									asiakas_ryhma    	= '$ahrow[asiakas_ryhma]',
									asiakas_segmentti	= '$ahrow[asiakas_segmentti]',
									piiri            	= '$ahrow[piiri]',
									hinta            	= '$ahrow[hinta]',
									valkoodi         	= '$ahrow[valkoodi]',
									minkpl           	= '$ahrow[minkpl]',
									maxkpl           	= '$ahrow[maxkpl]',
									alkupvm          	= '$ahrow[alkupvm]',
									loppupvm         	= '$ahrow[loppupvm]',
									laji             	= '$ahrow[laji]',
									laatija          	= '$kukarow[kuka]',
									luontiaika       	= now()";

						$ahinsertresult = mysql_query($ahinsert) or pupe_error($ahinsert);
					}
				}

				// haetaan asiakasALENNUS ensin Ytunnuksella.
				$hquery = "	SELECT * FROM asiakasalennus WHERE ytunnus = '$asrow[ytunnus]' AND yhtio ='$kukarow[yhtio]'";
				$hresult = mysql_query($hquery) or pupe_error($hquery);

				if (mysql_num_rows($hresult) == 0) {
					echo t("Ei asiakasalennuksia y-tunnuksella").".<br>";
				}
				else {
					while ($alrow = mysql_fetch_assoc($hresult)) {
						$alinsert = "INSERT INTO asiakashinta SET
									yhtio         		= '$kukarow[yhtio]',
									tuoteno          	= '$alrow[tuoteno]',
									ryhma            	= '$alrow[ryhma]',
									asiakas          	= '',
									ytunnus          	= '$jrow[ytunnus]',
									asiakas_ryhma    	= '$alrow[asiakas_ryhma]',
									asiakas_segmentti	= '$alrow[asiakas_segmentti]',
									piiri            	= '$alrow[piiri]',
									hinta            	= '$alrow[hinta]',
									valkoodi         	= '$alrow[valkoodi]',
									minkpl           	= '$alrow[minkpl]',
									maxkpl           	= '$alrow[maxkpl]',
									alkupvm          	= '$alrow[alkupvm]',
									loppupvm         	= '$alrow[loppupvm]',
									laji             	= '$alrow[laji]',
									laatija          	= '$kukarow[kuka]',
									luontiaika       	= now()";

						$alinsertresult = mysql_query($alinsert) or pupe_error($alinsert);
					}
				}

				// haetaan asiakasALENNUS sitten asiakastunnuksella.
				$hquery = "	SELECT * FROM asiakasalennus WHERE asiakas = '$asrow[tunnus]' AND yhtio ='$kukarow[yhtio]'";
				$hresult = mysql_query($hquery) or pupe_error($hquery);

				if (mysql_num_rows($hresult) == 0) {
					echo t("Ei asiakashintoja asiakastunnuksella").".<br>";
				}
				else {
					while ($alrow = mysql_fetch_assoc($hresult)) {
						$alinsert = "INSERT INTO asiakashinta SET
									yhtio         		= '$kukarow[yhtio]',
									tuoteno          	= '$alrow[tuoteno]',
									ryhma            	= '$alrow[ryhma]',
									asiakas          	= '$jrow[tunnus]',
									ytunnus          	= '',
									asiakas_ryhma    	= '$alrow[asiakas_ryhma]',
									asiakas_segmentti	= '$alrow[asiakas_segmentti]',
									piiri            	= '$alrow[piiri]',
									hinta            	= '$alrow[hinta]',
									valkoodi         	= '$alrow[valkoodi]',
									minkpl           	= '$alrow[minkpl]',
									maxkpl           	= '$alrow[maxkpl]',
									alkupvm          	= '$alrow[alkupvm]',
									loppupvm         	= '$alrow[loppupvm]',
									laji             	= '$alrow[laji]',
									laatija          	= '$kukarow[kuka]',
									luontiaika       	= now()";
						$alinsertresult = mysql_query($alinsert) or pupe_error($alinsert);
					}
				}

				// !!!!!!!! LASKUTUS OSIO !!!!!!!!!!!!
				$lquery = "	SELECT group_concat(tunnus) tunnukset FROM lasku WHERE yhtio ='$kukarow[yhtio]' AND liitostunnus = '$asrow[tunnus]' AND tila not IN ('h','y','m','p','q','x')";
				$lresult = mysql_query($lquery) or pupe_error($lquery);
				$lrow = mysql_fetch_assoc($lresult);

				if (trim($lrow['tunnukset']) != "") {
					$lupdate = "UPDATE lasku SET liitostunnus = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' and liitostunnus='$asrow[tunnus]' AND tunnus IN ($lrow[tunnukset])";
					$lupdateresult = mysql_query($lupdate) or pupe_error($lupdate);
				}
				else {
					echo t("Ei löytynyt laskuja asiakkaalta").".<br>";
				}

				// !!!!!!!! RAHTISOPIMUS OSIO !!!!!!!!!!!!
				$rquery = "	SELECT group_concat(tunnus) tunnukset FROM rahtisopimukset WHERE yhtio ='$kukarow[yhtio]' AND asiakas = '$asrow[tunnus]'";
				$rresult = mysql_query($rquery) or pupe_error($rquery);
				$rrow = mysql_fetch_assoc($rresult);

				if (trim($rrow['tunnukset']) != "") {
					$rupdate = "UPDATE rahtisopimukset SET asiakas = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' and asiakas='$asrow[tunnus]' AND tunnus IN ($rrow[tunnukset])";
					$rupdateresult = mysql_query($rupdate) or pupe_error($rupdate);
				}
				else {
					echo t("Ei löytynyt rahtisopimuksia asiakkaalta").".<br>";
				}

				// !!!!!!!! YHTEYSHENKILÖ OSIO !!!!!!!!!!!!
				$yquery = "	SELECT group_concat(tunnus) tunnukset FROM yhteyshenkilo WHERE yhtio ='$kukarow[yhtio]' AND liitostunnus = '$asrow[tunnus]'";
				$yresult = mysql_query($yquery) or pupe_error($yquery);
				$yrow = mysql_fetch_assoc($yresult);

				if (trim($yrow['tunnukset']) != "") {
					$yupdate = "UPDATE yhteyshenkilo SET liitostunnus = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' and liitostunnus='$asrow[tunnus]' AND tunnus IN ($yrow[tunnukset])";
					$yupdateresult = mysql_query($yupdate) or pupe_error($yupdate);
				}
				else {
					echo t("Ei löytynyt rahtisopimuksia asiakkaalta").".<br>";
				}

				// !!!!!!!! ASIAKASKOMMENTTI OSIO !!!!!!!!!!!!
				$akquery = "	SELECT group_concat(tunnus) tunnukset FROM asiakaskommentti WHERE yhtio ='$kukarow[yhtio]' AND ytunnus = '$asrow[ytunnus]'";
				$akresult = mysql_query($akquery) or pupe_error($akquery);
				$akrow = mysql_fetch_assoc($akresult);

				if (trim($akrow['tunnukset']) != "") {
					$akupdate = "UPDATE asiakaskommentti SET ytunnus = '$jrow[ytunnus]' WHERE yhtio ='$kukarow[yhtio]' and ytunnus='$asrow[ytunnus]' AND tunnus IN ($akrow[tunnukset])";
					$akupdateresult = mysql_query($akupdate) or pupe_error($akupdate);
				}
				else {
					echo t("Ei löytynyt asiakaskommentteja asiakkaalta").".<br>";
				}

				// !!!!!!!! ASIAKASLIITE OSIO !!!!!!!!!!!!
				$liitequery = "	SELECT group_concat(tunnus) tunnukset FROM liitetiedostot WHERE yhtio ='$kukarow[yhtio]' AND liitos='asiakas' AND liitostunnus = '$asrow[tunnus]'";
				$liiteresult = mysql_query($liitequery) or pupe_error($liitequery);
				$liitteet = mysql_fetch_assoc($liiteresult);

				if (trim($liitteet['tunnukset']) != "") {
					$liiteupdate = "UPDATE liitetiedostot SET liitostunnus = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' and liitos='asiakas' and tunnus IN ($liitteet[tunnukset])";
					$liiteupdateresult = mysql_query($liiteupdate) or pupe_error($liiteupdate);
				}
				else {
					echo t("Ei löytynyt liitteitä asiakkaalta").".<br>";
				}

				// !!!!!!!! ASIAKKAAN_AVAINSANA OSIO !!!!!!!!!!!!
				$avainquery = "	SELECT group_concat(tunnus) tunnukset FROM asiakkaan_avainsanat WHERE yhtio ='$kukarow[yhtio]' AND liitostunnus = '$asrow[tunnus]'";
				$avainresult = mysql_query($avainquery) or pupe_error($avainquery);
				$avaimet = mysql_fetch_assoc($avainresult);

				if (trim($avaimet['tunnukset']) != "") {
					$avainupdate = "UPDATE asiakkaan_avainsanat SET liitostunnus = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' AND liitostunnus = '$asrow[tunnus]' and tunnus IN ($avaimet[tunnukset])";
					$avainupdateresult = mysql_query($avainupdate) or pupe_error($avainupdate);
				}
				else {
					echo t("Ei löytynyt avainsanoja asiakkaalta").".<br>";
				}

				// !!!!!!!! PUUN_ALKI OSIO !!!!!!!!!!!!
				$paquery = "	SELECT group_concat(tunnus) tunnukset FROM puun_alkio WHERE yhtio ='$kukarow[yhtio]' AND liitos = '$asrow[tunnus]'";
				$paresult = mysql_query($paquery) or pupe_error($paquery);
				$puut = mysql_fetch_assoc($paresult);

				if (trim($puut['tunnukset']) != "") {
					$avainupdate = "UPDATE puun_alkio SET liitos = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' AND liitos = '$asrow[tunnus]' and tunnus IN ($puut[tunnukset])";
					$avainupdateresult = mysql_query($avainupdate) or pupe_error($avainupdate);
				}
				else {
					echo t("Ei löytynyt dynaamisen puun liitoksia asiakkaalta").".<br>";
				}

				// Muutetaan asiakkaan laji = 'P'
				$paivitys = "UPDATE asiakas set laji='P' where yhtio ='$kukarow[yhtio]' AND tunnus = '$asrow[tunnus]'";
				$pairesult = mysql_query($paivitys) or pupe_error($paivitys);
			}
		}
	}
	
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='YHDISTA'>";

	$monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "ASIAKASMYYJA", "ASIAKASTILA", "<br>DYNAAMINEN_ASIAKAS");
	$monivalintalaatikot_normaali = array();

	require ("tilauskasittely/monivalintalaatikot.inc");

	$kentat    = "asiakas.ytunnus::asiakas.ytunnus::asiakas.nimi::asiakas.osoite::asiakas.postino::asiakas.asiakasnro::asiakas.toim_postino";
	$jarjestys = 'ytunnus, nimi, selaus, tunnus';

	$array = explode("::", $kentat);
	$count = count($array);

	for ($i = 0; $i <= $count; $i++) {
		if (isset($haku[$i]) and strlen($haku[$i]) > 0) {
			$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
		}
	}

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}

	$lisa .= " and asiakas.laji != 'P' ";

	$query = "	SELECT
				asiakas.tunnus,
				asiakas.ytunnus,
				concat(asiakas.nimi ,'<br>', asiakas.toim_nimi,'<br>',	asiakas.laskutus_nimi) 'nimi'  ,
				concat(asiakas.osoite ,'<br>', asiakas.toim_osoite,'<br>',	asiakas.laskutus_osoite) 'osoite',
				concat(asiakas.postino, '<br>', asiakas.toim_postino, asiakas.laskutus_postino) 'postino',
				concat(asiakas.postitp, '<br>', asiakas.toim_postitp, asiakas.laskutus_postitp) 'postitp',
				asiakas.asiakasnro,
				asiakas.yhtio
				FROM asiakas
				$lisa_dynaaminen
				WHERE asiakas.yhtio = '$kukarow[yhtio]'
				$lisa
				ORDER BY $jarjestys
				LIMIT 500";
	$result = mysql_query($query) or pupe_error($query);

	echo "<br><table>";
	echo "<tr>";

	for ($i = 1; $i < mysql_num_fields($result)-1; $i++) { // HAKUKENTÄT
		echo "<th><a href='$PHP_SELF?ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

		if 	(mysql_field_len($result,$i)>20) $size='20';
		elseif	(mysql_field_len($result,$i)<=20)  $size='10';
		else	$size='10';

		if (!isset($haku[$i])) $haku[$i] = '';

		echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
		echo "</th>";
	}
	echo "<th>".t("Yhdistä")."</th><th>".t("jätä tämä")."</th>";
	echo "<td class='back'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi / yhdistä")."'></td></tr>\n\n";

	$kalalask = 1;

	while ($trow = mysql_fetch_array ($result)) { // tiedot
		echo "<tr class='aktiivi'>";

		for ($i=1; $i<mysql_num_fields($result)-1; $i++) {

			if ($i == 1) {
				if (trim($trow[1]) == '') $trow[1] = "".t("*tyhjä*")."";
				echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."yhdistaasiakas.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
			}
			elseif (mysql_field_name($result,$i) == 'ytunnus') {
				echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."yhdistaasiakas.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}

		echo "<td><input type='checkbox' name='yhdista[$trow[tunnus]]' value='$trow[tunnus]' $sel/></td>";
		echo "<td><input type='radio' name='jataminut' value='$trow[tunnus]'/></td>";
		echo "</tr>\n\n";

		$kalalask++;
	}

	echo "</table><br><br>";
	
	echo "<input type='submit' value='".t("Yhdistä asiakkaat")."'>";
	echo "</form>";

	require ("inc/footer.inc");

?>