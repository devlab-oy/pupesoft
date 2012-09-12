<?php
	require ("inc/parametrit.inc");

	if ($livesearch_tee == "TUOTEHAKU") {
		livesearch_tuotehaku();
		exit;
	}

	// Enaboidaan ajax kikkare
	enable_ajax();

	if ($toim == "PERHE") {
		echo "<font class='head'>".t("Tuoteperheet")."(".t("Vain isätuotetta voi tilata").")</font><hr>";
		$hakutyyppi = "P";
	}
	elseif ($toim == "OSALUETTELO") {
		echo "<font class='head'>".t("Tuotteen osaluettelo")."(".t("Kaikkia tuotteita voi tilata").")</font><hr>";
		$hakutyyppi = "O";
	}
	elseif ($toim == "TUOTEKOOSTE") {
		echo "<font class='head'>".t("Tuotteen koosteluettelo")."(".t("Vain lapsituotteita voi tilata").")</font><hr>";
		$hakutyyppi = "V";
	}
	elseif ($toim == "LISAVARUSTE") {
		echo "<font class='head'>".t("Tuotteen lisävarusteet")."</font><hr>";
		$hakutyyppi = "L";
	}
	elseif ($toim == "VSUUNNITTELU") {
		echo "<font class='head'>".t("Samankaltaisten tuotteiden määrittely")."</font><hr>";
		$hakutyyppi = "S";
	}
	else {
		echo "<font class='head'>".t("Tuotereseptit")."</font><hr>";
		$hakutyyppi = "R";
	}

	if ($tee == "KOPIOI") {

		if ($kop_isatuo == "") {
				echo "<br><br>";
				echo "<table>";
				echo "	<form method='post' name='valinta' autocomplete='off'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='KOPIOI'>
						<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

				echo "<tr><th>";

				if ($toim == "PERHE") {
					echo t("Syötä isä jolle perhe kopioidaan");
				}
				elseif ($toim == "LISAVARUSTE") {
					echo t("Syötä tuote jolle lisävarusteet kopioidaan");
				}
				elseif ($toim == "OSALUETTELO") {
					echo t("Syötä tuote jolle osaluettelo kopioidaan");
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo t("Syötä tuote jolle tuotekooste kopioidaan");
				}
				elseif ($toim == "VSUUNNITTELU") {
					echo t("Syötä tuote jolle samankaltaisuus kopioidaan");
				}
				else {
					echo t("Syötä valmiste jolle resepti kopioidaan");
				}

				echo ": </th><td>".livesearch_kentta("valinta", "TUOTEHAKU", "kop_isatuo", 140, $kop_isatuo, 'X')."</td>";

				foreach ($kop_tuoteno as $kop_index => $tuoteno) {
					echo "<input type='hidden' name='kop_tuoteno[$kop_index]' value='$kop_tuoteno[$kop_index]'>";
					echo "<input type='hidden' name='kop_kerroin[$kop_index]' value='$kop_kerroin[$kop_index]'>";
					echo "<input type='hidden' name='kop_hinkerr[$kop_index]' value='$kop_hinkerr[$kop_index]'>";
					echo "<input type='hidden' name='kop_alekerr[$kop_index]' value='$kop_alekerr[$kop_index]'>";
					#echo "<input type='hidden' name='kop_rivikom[$kop_index]' value='$kop_rivikom[$kop_index]'>";
					echo "<input type='hidden' name='kop_fakta[$kop_index]' value='$kop_fakta[$kop_index]'>";

					if ($toim == "PERHE") {
						echo "<input type='hidden' name='kop_ohita_kerays[$kop_index]' value='$kop_ohita_kerays[$kop_index]'>";
					}
				}

				echo "<td><input type='submit' value='".t("Kopioi")."'></td></tr>";
				echo "</form>";
				echo "</table>";
		}
		else {

			$query  = "	SELECT tuote.*, tuoteperhe.isatuoteno isa
						from tuote
						LEFT JOIN tuoteperhe ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.isatuoteno and tuoteperhe.tyyppi='$hakutyyppi'
						where tuote.tuoteno 	= '$kop_isatuo'
						and tuote.yhtio 		= '$kukarow[yhtio]'
						and tuote.status 	   NOT IN ('P','X')
						HAVING isa is null";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				foreach ($kop_tuoteno as $kop_index => $tuoteno) {
					if ($tuoteno != $kop_isatuo) {

						$insert_lisa = "";

						if ($toim == "PERHE") {
							$insert_lisa = "ohita_kerays = '{$kop_ohita_kerays[$kop_index]}',";
						}

						$query = "	INSERT into	tuoteperhe set
									isatuoteno		= '$kop_isatuo',
									tuoteno 		= '$kop_tuoteno[$kop_index]',
									kerroin 		= '$kop_kerroin[$kop_index]',
									hintakerroin	= '$kop_hinkerr[$kop_index]',
									alekerroin 		= '$kop_alekerr[$kop_index]',
									#rivikommentti	= '$kop_rivikom[$kop_index]',
									fakta	 		= '$kop_fakta[$kop_index]',
									{$insert_lisa}
									yhtio 			= '$kukarow[yhtio]',
									laatija			= '$kukarow[kuka]',
									luontiaika		= now(),
									tyyppi 			= '$hakutyyppi'";
						$result = pupe_query($query);
					}
				}

				echo "<br><br><font class='message'>";

				if ($toim == "PERHE") {
					echo t("Tuoteperhe kopioitu");
				}
				elseif ($toim == "LISAVARUSTE") {
					echo t("Lisävarusteet kopioitu");
				}
				elseif ($toim == "OSALUETTELO") {
					echo t("Osaluettelo kopioitu");
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo t("Tuotekooste kopioitu");
				}
				elseif ($toim == "VSUUNNITTELU") {
					echo t("Samankaltaisuus kopioitu");
				}
				else {
					echo t("Resepti kopioitu");
				}

				echo "!</font><br>";

				$hakutuoteno = $kop_isatuo;
				$isatuoteno  = $kop_isatuo;
				$tee 		 = "";
			}
			else {
				echo "<br><br><font class='error'>";

				if ($toim == "PERHE") {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo perhe");
				}
				elseif ($toim == "LISAVARUSTE") {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo lisävarusteita");
				}
				elseif ($toim == "OSALUETTELO") {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo osaluettelo");
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo tuotekooste");
				}
				else {
					echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo resepti");
				}

				echo "!</font><br>";
				$tee = "";
			}
		}
	}

	if ($tee != "KOPIOI") {

		$formi  = 'performi';
		$kentta = 'hakutuoteno';

		if ($hakutuoteno2 != "") $hakutuoteno = trim($hakutuoteno2);

		echo "<br><table>";
		echo "<form method='post' name='$formi' autocomplete='off'>
				<input type='hidden' name='toim' value='$toim'>
			<tr>";

		if ($toim == "PERHE") {
			echo "<th>".t("Etsi tuoteperhettä").": </th>";
		}
		elseif ($toim == "LISAVARUSTE") {
			echo "<th>".t("Etsi tuotteen lisävausteita").": </th>";
		}
		elseif ($toim == "OSALUETTELO") {
			echo t("Etsi osaluetteloa");
		}
		elseif ($toim == "TUOTEKOOSTE") {
			echo t("Etsi tuotekoostetta");
		}
		elseif ($toim == "VSUUNNITTELU") {
			echo "<th>".t("Etsi samankaltaisia")."</th>";
		}
		else{
			echo "<th>".t("Etsi tuotereseptiä").": </th>";
		}

		echo "<td>".livesearch_kentta($formi, "TUOTEHAKU", "hakutuoteno", 210)."</td>";
		echo "<tr><th>".t("Rajaa hakumäärää")."</th>";
		echo "<td>";

		if (!isset($limitti)) $limitti = 100;

		if ($limitti == 100) {
			$sel1 = "selected";
		}
		elseif ($limitti == 1000) {
			$sel2 = "selected";
		}
		elseif ($limitti == 5000) {
			$sel3 = "selected";
		}
		elseif ($limitti == "U") {
			$sel5 = "selected";
		}
		else {
			$sel4 = "selected";
		}

		echo "<select name='limitti'>";
		echo "<option value='100' $sel1>".t("100 ensimmäistä")."</option>";
		echo "<option value='1000' $sel2>".t("1000 ensimmäistä")."</option>";
		echo "<option value='5000' $sel3>".t("5000 ensimmäistä")."</option>";
		echo "<option value='' $sel4>".t("Koko lista")."</option>";
		echo "<option value='U' $sel5>".t("Tuoreimmasta vanhimpaan näyttäen koko lista")."</option>";
		echo "</select>";
		echo "</td>";
		echo "<td class='back'><input type='Submit' value='".t("Jatka")."'></td>";
		echo "</table></form>";
	}

	if ($tee == 'LISAA') {

		echo "<br>";

		if (trim($isatuoteno) != trim($tuoteno)) {
			$ok = 1;
			$isatuoteno = trim($isatuoteno);
			$tuoteno = trim($tuoteno);

			$query  = "	SELECT *
						from tuoteperhe
						where isatuoteno 	= '$isatuoteno'
						and yhtio 			= '$kukarow[yhtio]'
						and tyyppi 			= '$hakutyyppi'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				//katsotaan ettei tämä isa/lapsi kombinaatio ole jo olemassa
				$query  = "	SELECT *
							from tuoteperhe
							where isatuoteno 	= '$isatuoteno'
							and tuoteno 		= '$tuoteno'
							and yhtio 			= '$kukarow[yhtio]'
							and tyyppi 			= '$hakutyyppi'";
				$result = pupe_query($query);

				//Jos tunnus on erisuuri kuin tyhjä niin ollan päivittämässä olemassa olevaaa kombinaatiota
				if (mysql_num_rows($result) > 0 and $tunnus == "") {
					if ($yhtiorow["tuoteperhe_kasittely"] == "") {
						echo "<font class='error'>".t("Tämä tuoteperhekombinaatio on jo olemassa, sitä ei voi lisätä toiseen kertaan")."</font><br>";
						$ok = 0;
					}
					else {
						echo "<font class='message'>".t("HUOM: Tämä tuoteperhekombinaatio on jo olemassa, laspsituote löytyy tästä perheestä %s kertaa", "", (mysql_num_rows($result)+1))."!</font><br>";
					}
				}
			}

			if ($ok == 1) {
				//tarkistetaan tuotteiden olemassaolo
				$error = '';

				$query = "SELECT * from tuote where tuoteno='$isatuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = pupe_query($query);
				if (mysql_num_rows($res)==0) $error .= "<font class='error'>".t("Tuotenumero")." $isatuoteno ".t("ei ole tuoterekisterissä, riviä ei voida lisätä")."!</font><br>";

				$query = "SELECT * from tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = pupe_query($query);
				if (mysql_num_rows($res)==0) $error .= "<font class='error'>".t("Tuotenumero")." $tuoteno ".t("ei ole tuoterekisterissä, riviä ei voida lisätä")."!</font><br>";

				if ($error == '') {
					$kerroin 		= str_replace(',', '.', $kerroin);
					$hintakerroin 	= str_replace(',', '.', $hintakerroin);
					$alekerroin 	= str_replace(',', '.', $alekerroin);

					//lisätään rivi...
					if ($kerroin == '') {
						$kerroin = '1';
					}
					if ($hintakerroin == '') {
						$hintakerroin = '1';
					}
					if ($alekerroin == '') {
						$alekerroin = '1';
					}

					if ($tunnus == "") {
						$query = "	INSERT INTO ";
						$postq = " , laatija	= '$kukarow[kuka]',
								 	 luontiaika	= now()";
					}
					else {
						$query = " 	UPDATE ";
						$postq = " 	, muuttaja='$kukarow[kuka]',
									muutospvm=now()
									WHERE tunnus='$tunnus' ";
					}

					$querylisa = "";

					if ($toim == "PERHE") {
						$querylisa = "ohita_kerays = '{$ohita_kerays}',";
					}

					$query  .= "	tuoteperhe set
									isatuoteno		= '$isatuoteno',
									tuoteno 		= '$tuoteno',
									kerroin 		= '$kerroin',
									omasivu			= '$kpl2',
									hintakerroin	= '$hintakerroin',
									alekerroin 		= '$alekerroin',
									#rivikommentti 	= '$rivikommentti',
									yhtio 			= '$kukarow[yhtio]',
									tyyppi 			= '$hakutyyppi',
									{$querylisa}
									ei_nayteta		= '$ei_nayteta'
									$postq";
					$result = pupe_query($query);

					$tunnus = "";
					$tee 	= "";
				}
				else {
					echo "$error<br>";
					$tee = "";
				}
			}
		}
		else {
			echo t("Tuoteperheen isä ei voi olla sekä isä että lapsi samassa perhessä")."!<br>";
		}
		$tee = "";
	}

	if ($tee == 'POISTA') {
		$isatuoteno = trim($isatuoteno);

		// Varmistetaan, että faktat ei mene rikki
		$query = "	SELECT
					group_concat(distinct if(fakta = '', null, fakta)) fakta,
					group_concat(distinct if(fakta2 = '', null, fakta2)) fakta2,
					group_concat(distinct if(omasivu = '', null, omasivu)) omasivu
					FROM tuoteperhe
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$hakutyyppi'
					and isatuoteno = '$isatuoteno'";
		$result = pupe_query($query);
		$fakrow = mysql_fetch_array($result);

		$fakta 		= $fakrow["fakta"];
		$fakta2 	= $fakrow["fakta2"];
		$omasivu 	= $fakrow["omasivu"];
		$tee 		= "TALLENNAFAKTA";

		//poistetaan rivi..
		$query  = "	DELETE from tuoteperhe
					where tunnus = '$tunnus'
					and yhtio = '$kukarow[yhtio]'
					and tyyppi = '$hakutyyppi'";
		$result = pupe_query($query);
		$tunnus = '';
	}

	if ($tee == 'TALLENNAFAKTA') {
		$isatuoteno = trim($isatuoteno);

		$query = "	UPDATE tuoteperhe
					SET fakta = '', fakta2 = '', omasivu = ''
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$hakutyyppi'
					and isatuoteno = '$isatuoteno'";
		$result = pupe_query($query);

		$query = "	UPDATE tuoteperhe
					SET fakta = '$fakta', fakta2 = '$fakta2', omasivu = '$omasivu'
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$hakutyyppi'
					and isatuoteno = '$isatuoteno'
					ORDER BY isatuoteno, tuoteno
					LIMIT 1";
		$result = pupe_query($query);

		echo "<br><br><font class='message'>".t("Reseptin tiedot tallennettu")."!</font><br>";

		$query = "	UPDATE tuoteperhe
					SET ei_nayteta = '$ei_nayteta'
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$hakutyyppi'
					and isatuoteno = '$isatuoteno'";
		$result = pupe_query($query);

		echo "<font class='message'>".t("Esitysmuoto tallennettu")."!</font><br>";

		$tee = '';
	}

	if (($hakutuoteno != '' or $isatuoteno != '') and $tee == "") {
		$lisa = "";
		$tchk = "";

		$isatuoteno = trim($isatuoteno);
		$hakutuoteno = trim($hakutuoteno);

		if ($isatuoteno != '') {
			$lisa = " and isatuoteno='$isatuoteno'";
			$tchk = $isatuoteno;
		}
		else {
			$lisa = " and isatuoteno='$hakutuoteno'";
			$tchk = $hakutuoteno;
		}

		$query  = "	SELECT tuoteno
					from tuote
					where yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tchk'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$query  = "	SELECT distinct isatuoteno
						from tuoteperhe
						where yhtio = '$kukarow[yhtio]'
						$lisa
						and tyyppi = '$hakutyyppi'
						order by isatuoteno, tuoteno";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0 and ($hakutuoteno != '' or $isatuoteno != '')) {
				if ($toim == "PERHE") {
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei löydy mistään tuoteperheestä")."!</font><br>";
					echo "<br><font class='head'>".t("Perusta uusi tuoteperhe tuotteelle").": $hakutuoteno</font><hr><br>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<br><font class='error'>".t("Lisävarusteita ei ole määritelty tuotteelle")." $hakutuoteno!</font><br>";
					echo "<br><font class='head'>".t("Lisää lisävaruste tuotteelle").": $hakutuoteno</font><hr><br>";
				}
				elseif ($toim == "OSALUETTELO") {
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei löydy mistään osaluettelosta")."!</font><br>";
					echo "<br><font class='head'>".t("Perusta uusi osaluettelo tuotteelle").": $hakutuoteno</font><hr><br>";
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo "<br><font class='error'>".t("Tuotekoostetta ei ole määritelty tuotteelle")." $hakutuoteno!</font><br>";
					echo "<br><font class='head'>".t("Lisää tuotekooste tuotteelle").": $hakutuoteno</font><hr><br>";
				}
				elseif ($toim == "VSUUNNITTELU") {
					echo "<br><font class='error'>".t("Samankaltaisuutta ei ole määritelty tuotteelle")." $hakutuoteno!</font><br>";
					echo "<br><font class='head'>".t("Lisää samankaltaisuus tuotteelle").": $hakutuoteno</font><hr><br>";
				}
				else{
					echo "<br><font class='error'>".t("Tuotenumeroa")." $hakutuoteno ".t("ei löydy mistään tuotereseptistä")."!</font><br>";
					echo "<br><font class='head'>".t("Lisää raaka-aine valmisteelle").": $hakutuoteno</font><hr><br>";
				}

				echo "<table>";
				echo "<th>".t("Tuoteno")."</th><th>".t("Määräkerroin")."</th><th>".t("Hintakerroin")."</th><th>".t("Alennuskerroin")."</th>";
				#echo "<th>".t("Rivikommentti")."</th>";
				echo "<td class='back'></td></tr>";

				echo "	<form method='post' name='lisaa' autocomplete='off'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnus' value='$zrow[tunnus]'>
						<input type='hidden' name='tee' value='LISAA'>
						<input type='hidden' name='isatuoteno' value='$hakutuoteno'>
						<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

				echo "<tr>";

				echo "<tr><td>".livesearch_kentta("lisaa", "TUOTEHAKU", "tuoteno", 140, '', 'X')."</td>";

				if ($toim == "VSUUNNITTELU") {
					echo "<td><input type='hidden' name='kerroin' value='1'>1</td>";
				}
				else{
					echo "	<td><input type='text' name='kerroin' size='20'></td>
							<td><input type='text' name='hintakerroin' size='20'></td>
							<td><input type='text' name='alekerroin' size='20'></td>";
					#echo "	<td><input type='text' name='rivikommentti' size='20'></td>";
				}

				echo "	<td class='back'><input type='submit' value='".t("Lisää rivi")."'></td></form></tr>";
				echo "</table>";
			}
			elseif (mysql_num_rows($result) == 1) {
				$row = mysql_fetch_array($result);
				$isatuoteno	= $row['isatuoteno'];

				//isätuotteen checkki
				$error = "";
				$query = "SELECT * from tuote where tuoteno='$isatuoteno' and yhtio='$kukarow[yhtio]'";
				$res   = pupe_query($query);

				if (mysql_num_rows($res) == 0) {
					echo "<font class='error'>".t("Tuote ei enää rekisterissä")."!</font><br>";
				}
				else {
					$isarow = mysql_fetch_array($res);
				}

				echo "<br><table>";
				echo "<tr>";

				if ($toim == "PERHE") {
					echo "<th>".t("Tuoteperheen isätuote").": </th>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Tuotenumero").": </th>";
				}
				elseif ($toim == "OSALUETTELO") {
					echo "<th>".t("Tuotenumero").": </th>";
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo "<th>".t("Tuotenumero").": </th>";
				}
				elseif ($toim == "VSUUNNITTELU") {
					echo "<th>".t("Tuotenumero").": </th>";
				}
				else {
					echo "<th>".t("Tuotereseptin valmiste").": </th>";
				}

				echo "<tr><td>$isatuoteno - $isarow[nimitys]</td></tr></table><br>";

				$query = "SELECT ei_nayteta FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' and ei_nayteta != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = pupe_query($query);
				$faktarow = mysql_fetch_array($ressu);

				if ($faktarow["ei_nayteta"] == "") {
					$sel1 = "SELECTED";
				}
				elseif($faktarow["ei_nayteta"] == "E") {
					$sel2 = "SELECTED";
				}

				echo "<table><form method='post' autocomplete='off'>
						<input type='hidden' name='toim' value='$toim'>
				  		<input type='hidden' name='tee' value='TALLENNAESITYSMUOTO'>
				  		<input type='hidden' name='tunnus' value='$prow[tunnus]'>
				  		<input type='hidden' name='isatuoteno' value='$isatuoteno'>
				  		<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

				echo "<tr><th>".t("Esitysmuoto").": </th></tr>";
				echo "<tr><td>";
				echo "	<select name='ei_nayteta'>
						<option value='' $sel1>".t("Kaikki rivit näytetään")."</option>
						<option value='E' $sel2>".t("Lapsirivejä ei näytetä")."</option>
						</select></td>";

				$query = "SELECT omasivu FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$hakutyyppi' and isatuoteno = '$isatuoteno' and omasivu != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = pupe_query($query);
				$faktarow = mysql_fetch_array($ressu);

				if ($toim == "RESEPTI") {
					if($faktarow["omasivu"] != "") {
						$sel1 = "";
						$sel2 = "SELECTED";
					}
					elseif ($omasivu != 'X') {
						$sel1 = "SELECTED";
						$sel2 = "";
					}
					else {
						$sel1 = "";
						$sel2 = "SELECTED";
					}

					echo "<tr><th>".t("Reseptin tulostus").": </th></tr>";
					echo "<tr><td>";
					echo "	<select name='omasivu'>
							<option value='' $sel1>".t("Resepti tulostetaan normaalisti")."</option>
							<option value='X' $sel2>".t("Resepti tulostetaan omalle sivulle")."</option>
							</select></td>";
				}

				echo "</table><br>";

				echo "<form method='post' autocomplete='off'>
						<input type='hidden' name='toim' value='$toim'>
				  		<input type='hidden' name='tee' value='TALLENNAFAKTA'>
				  		<input type='hidden' name='tunnus' value='$prow[tunnus]'>
				  		<input type='hidden' name='isatuoteno' value='$isatuoteno'>
				  		<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

				echo "<table>";
				echo "<tr>";

				if ($toim == "PERHE") {
					echo "<th>".t("Tuoteperheen faktat").": </th></tr>";
				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Lisävarusteiden faktat").": </th></tr>";
				}
				elseif ($toim == "OSALUETTELO") {
					echo "<th>".t("Osaluettelon faktat").": </th>";
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo "<th>".t("Tuotekoosteen faktat").": </th>";
				}
				elseif ($toim == "VSUUNNITTELU") {
					echo "<th>".t("Samankaltaisuuden faktat").": </th>";
				}
				else {
					echo "<th>".t("Reseptin faktat").": </th></tr>";
				}

				$query = "	SELECT fakta
							FROM tuoteperhe
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = '$hakutyyppi'
							and isatuoteno = '$isatuoteno'
							and trim(fakta) != ''
							ORDER BY LENGTH(fakta) desc
							LIMIT 1";
				$ressu = pupe_query($query);
				$faktarow = mysql_fetch_array($ressu);

				echo "<td><textarea cols='35' rows='7' name='fakta'>{$faktarow["fakta"]}</textarea></td>";

				if ($toim == "RESEPTI") {

					$query = "	SELECT fakta2
								FROM tuoteperhe
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = '$hakutyyppi'
								and isatuoteno = '$isatuoteno'
								and trim(fakta2) != ''
								ORDER BY LENGTH(fakta2) desc
								LIMIT 1";
					$ressu = pupe_query($query);
					$faktarow = mysql_fetch_array($ressu);

					echo "</tr><tr>";
					echo "<th>".t("Yhdistämisen lisätiedot").": </th></tr>";
					echo "<td><textarea cols='35' rows='4' name='fakta2'>{$faktarow["fakta2"]}</textarea></td>";
				}
				echo "<td class='back'>
					  <input type='submit' value='".t("Tallenna")."'>
					  </td></form>";
				echo "</tr></table><br>";


				echo "<table><tr>";

				if ($toim == "PERHE") {
					echo "<th>".t("Lapset")."</th><th>".t("Nimitys")."</th><th>".t("Määräkerroin")."</th><th>".t("Hintakerroin")."</th><th>".t("Alennuskerroin")."</th>";
					#echo "<th>".t("Rivikommentti")."</th>";
					echo "<th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><th>",t("Ohita keräys"),"</th><td class='back'></td></tr>";

				}
				elseif ($toim == "LISAVARUSTE") {
					echo "<th>".t("Lisävarusteet")."</th><th>".t("Nimitys")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				}
				elseif ($toim == "OSALUETTELO") {
					echo "<th>".t("Osaluettelot")."</th><th>".t("Nimitys")."</th><th>".t("Kerroin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				}
				elseif ($toim == "TUOTEKOOSTE") {
					echo "<th>".t("Tuotekoosteet")."</th><th>".t("Nimitys")."</th><th>".t("Kerroin")."</th><th>".t("Kehahin*Kerroin")."</th><td class='back'></td></tr>";
				}
				elseif ($toim == "VSUUNNITTELU") {
					echo "<th>".t("Samankaltaisuudet")."</th><th>".t("Nimitys")."</th><th>".t("Kerroin")."</th><td class='back'></td></tr>";
				}
				else {
					echo "<th>".t("Raaka-aineet")."</th><th>".t("Nimitys")."</th><th>".t("Määräkerroin")."</th><th>".t("Kehahin")."</th><th>".t("Kehahin*Kerroin")."</th><th>".t("Pituus kerroin")."</th><td class='back'></td></tr>";
				}

				$query = "	SELECT *
							FROM tuoteperhe
							WHERE isatuoteno = '$row[isatuoteno]'
							and yhtio = '$kukarow[yhtio]'
							and tyyppi = '$hakutyyppi'
							order by isatuoteno, tuoteno";
				$res   = pupe_query($query);

				$resyht = 0;

				$kop_index   = 0;
				$kop_tuoteno = array();
				$kop_kerroin = array();
				$kop_hinkerr = array();
				$kop_alekerr = array();
				$kop_rivikom = array();
				$kop_fakta   = array();
				$kop_ohita_kerays = array();

				if ($tunnus == "") {
					echo "	<form method='post' name='lisaa' autocomplete='off'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='LISAA'>
							<input type='hidden' name='isatuoteno' value='$row[isatuoteno]'>
							<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";


					echo "<tr><td>".livesearch_kentta("lisaa", "TUOTEHAKU", "tuoteno", 140, '', 'X')."</td><td></td>";

					if ($toim != "LISAVARUSTE" and $toim != "VSUUNNITTELU") {
						echo "<td><input type='text' name='kerroin' size='10'></td>";
					}

					if ($toim == "PERHE") {
						echo "<td><input type='text' name='hintakerroin' size='10'></td>
								<td><input type='text' name='alekerroin' size='10'></td>";
						#echo "<td><input type='text' name='rivikommentti' size='10'></td>";
					}
					elseif ($toim != "LISAVARUSTE" and $toim != "VSUUNNITTELU") {
						echo "<td></td>";
					}

					if ($toim == "VSUUNNITTELU"){
						echo "<td><input type='hidden' name='kerroin' value='1'/>1</td>";
					}
					else echo "<td></td><td></td>";

					if ($toim == "PERHE") {
						echo "<td></td>";
					}

					echo "	<td class='back'><input type='submit' value='".t("Lisää")."'></td>
							</form>
							</tr>";
				}

				while ($prow = mysql_fetch_array($res)) {
					//Tarkistetaan löytyyko tuote enää rekisteristä
					$query = "SELECT * from tuote where tuoteno='$prow[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$res1   = pupe_query($query);

					if (mysql_num_rows($res1)==0) {
						$error="<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
					}
					else {
						$error = "";
						$tuoterow = mysql_fetch_array($res1);

						//Tehdään muuttujat jotta voidaan tarvittaessa kopioida resepti
						$kop_tuoteno[$kop_index] = $prow['tuoteno'];
						$kop_kerroin[$kop_index] = $prow['kerroin'];
						$kop_hinkerr[$kop_index] = $prow['hintakerroin'];
						$kop_alekerr[$kop_index] = $prow['alekerroin'];
						#$kop_rivikom[$kop_index] = $prow['rivikommentti'];
						$kop_fakta[$kop_index]   = $prow['fakta'];

						if ($toim == "PERHE") {
							$kop_ohita_kerays[$kop_index] = $prow['ohita_kerays'];
						}

						$kop_index++;
					}

					$lapsiyht = $tuoterow['kehahin']*$prow['kerroin'];
					$resyht += $lapsiyht;

					if ($tunnus != $prow["tunnus"]) {
						echo "<tr class='aktiivi'><td>$prow[tuoteno] $error</td><td>".t_tuotteen_avainsanat($tuoterow, 'nimitys')."</td>";

						if ($toim != "LISAVARUSTE") {
							echo "<td align='right'>$prow[kerroin]</td>";
						}

						if ($toim == "PERHE") {
							echo"<td align='right'>$prow[hintakerroin]</td><td align='right'>$prow[alekerroin]</td>";
							#echo "<td align='right'>$prow[rivikommentti]</td>";
						}

						if ($toim != "VSUUNNITTELU") {
							echo "<td align='right'>$tuoterow[kehahin]</td><td align='right'>".round($lapsiyht, 6)."</td>";
						}

						if ($toim == "PERHE") {
							$chk_ohita_kerays = (isset($prow['ohita_kerays']) and trim($prow['ohita_kerays']) != '') ? t("Kyllä") : t("Ei");
							echo "<td>{$chk_ohita_kerays}</td>";
						}

						if ($toim == "RESEPTI") {
							if ($prow["omasivu"] != "") {
								echo "<td>".t("Ei kerrota")."</td>";
							}
							else {
								echo "<td>".("Kerrotaan")."</td>";
							}
						}

						echo "<form method='post' autocomplete='off'>
								<td class='back'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$prow[tunnus]'>
								<input type='hidden' name='isatuoteno' value='$isatuoteno'>
								<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>
								<input type='submit' value='".t("Muuta")."'>
								</td></form>";


						echo "<form method='post' autocomplete='off'>
								<td class='back'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tee' value='POISTA'>
								<input type='hidden' name='tunnus' value='$prow[tunnus]'>
								<input type='hidden' name='isatuoteno' value='$isatuoteno'>
								<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>
								<input type='submit' value='".t("Poista")."'>
								</td></form>";

						echo "</tr>";
					}
					elseif ($tunnus == $prow["tunnus"]) {
						$query  = "	SELECT *
									FROM tuoteperhe
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$tunnus'
									and tyyppi = '$hakutyyppi'";
						$zresult = pupe_query($query);
						$zrow = mysql_fetch_array($zresult);


						echo "	<form method='post' autocomplete='off'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$zrow[tunnus]'>
								<input type='hidden' name='tee' value='LISAA'>
								<input type='hidden' name='isatuoteno' value='$zrow[isatuoteno]'>
								<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";


						echo "<tr>
							<td><input type='text' name='tuoteno' size='20' value='$zrow[tuoteno]'></td>
							<td></td>";

						if ($toim != "LISAVARUSTE" and $toim != "VSUUNNITTELU") {
							echo "	<td><input type='text' name='kerroin' size='10' value='$zrow[kerroin]'></td>";
						}

						if ($toim == "PERHE") {
							echo "	<td><input type='text' name='hintakerroin' size='10' value='$zrow[hintakerroin]'></td>
									<td><input type='text' name='alekerroin' size='10' value='$zrow[alekerroin]'></td>";
							#echo "	<td><input type='text' name='rivikommentti' size='10' value='$zrow[rivikommentti]'></td>";
						}

						if ($toim != "VSUUNNITTELU") {
							echo "<td>$tuoterow[kehahin]</td><td>".round($lapsiyht, 6)."</td>";
						}

						if ($toim == "PERHE") {
							$chk_ohita_kerays = (isset($zrow['ohita_kerays']) and trim($zrow['ohita_kerays']) != '') ? " checked" : "";
							echo "<td><input type='checkbox' name='ohita_kerays'{$chk_ohita_kerays}></td>";
						}

						if($toim == "RESEPTI") {
							$sel1=$sel2="";

							if ($prow["omasivu"] != "") {
								$sel2 = "SELECTED";
							}
							else {
								$sel1 = "SELECTED";
							}

							echo "<td>
										<select name='kpl2' style='width: 150px;'>
											<option value='' $sel1>".t("Määrää kerrotaan vaihdettaessa isätuotteen pituutta/määrää (kpl2)")."</option>
											<option value='X' $sel2>".t("Määrää ei kerrota vaihdettaessa isätuotteen pituutta/määrää (kpl2)")."</option>
										</select>
									</td>";
						}

						echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></td></form></tr>";
					}
				}

				if ($tunnus == "") {

					echo "	<td class='back' colspan='2'></td>";

					if ($toim != "LISAVARUSTE") {
						echo "<td class='back'></td>";
					}
					if ($toim == "PERHE") {
						echo "<td class='back' colspan='2'></td>";
					}

					echo "<th align='right'>".t("Yhteensä").":</th>
							<td class='tumma' align='right'>".round($resyht,6)."</td></tr>";
				}

				echo "</table>";

				echo "<br><br>";
				echo "	<form method='post' autocomplete='off'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='KOPIOI'>
						<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";


				foreach ($kop_tuoteno as $kop_index => $tuoteno) {
					echo "<input type='hidden' name='kop_tuoteno[$kop_index]' value='$kop_tuoteno[$kop_index]'>";
					echo "<input type='hidden' name='kop_kerroin[$kop_index]' value='$kop_kerroin[$kop_index]'>";
					echo "<input type='hidden' name='kop_hinkerr[$kop_index]' value='$kop_hinkerr[$kop_index]'>";
					echo "<input type='hidden' name='kop_alekerr[$kop_index]' value='$kop_alekerr[$kop_index]'>";
					#echo "<input type='hidden' name='kop_rivikom[$kop_index]' value='$kop_rivikom[$kop_index]'>";
					echo "<input type='hidden' name='kop_fakta[$kop_index]' value='$kop_fakta[$kop_index]'>";

					if ($toim == "PERHE") {
						echo "<input type='hidden' name='kop_ohita_kerays[$kop_index]' value='$kop_ohita_kerays[$kop_index]'>";
					}
				}

				echo "<input type='submit' value='".t("Kopioi")."'>";
				echo "</form>";

			}
			else {
				echo "<table>";

				while($row = mysql_fetch_array($result)) {
					$query = "	SELECT *
								from tuoteperhe
								where isatuoteno = '$row[isatuoteno]'
								and yhtio = '$kukarow[yhtio]'
								and tyyppi = '$hakutyyppi'
								order by isatuoteno, tuoteno";
					$res   = pupe_query($query);

					echo "<tr><td><a href='$PHP_SELF?toim=$toim&isatuoteno=".urlencode($row["isatuoteno"])."&hakutuoteno=".urlencode($row["isatuoteno"])."'>$row[isatuoteno]</a></td>";

					while($prow = mysql_fetch_array($res)) {
						echo "<td>$prow[tuoteno]</td>";
					}

					echo "</tr>";
				}
				echo "</table>";
			}
		}
		else {
			echo "<br><font class='error'>".t("Tuotenumeroa")." $tchk ".t("ei löydy")."!</font><br>";
		}
	}
	elseif ($tee == "") {

		$lisa1 = "";
		$lisalimit = "";

		if ($limitti !='' and $limitti !="U") {
			$limitteri = " limit $limitti";
		}
		elseif ($limitti == "U") {
			$lisalimit = "tuoteperhe.tunnus desc, ";
		}
		else {
			$limitteri = "";
		}

		if ($isatuoteno_haku != '') {
			$lisa1 .= " and tuoteperhe.isatuoteno like '%$isatuoteno_haku%' ";
		}

		if ($tuoteno_haku != '') {
			$lisa1 .= " and tuoteperhe.tuoteno like '%$tuoteno_haku%' ";
		}

		$query  = "	SELECT tuoteperhe.isatuoteno, ti.nimitys,
					group_concat(concat(tuoteperhe.tuoteno, ' ' , tl.nimitys) order by tuoteperhe.tuoteno, tuoteperhe.tunnus separator '<br>') tuotteet
					from tuoteperhe
					join tuote ti on ti.yhtio=tuoteperhe.yhtio and ti.tuoteno=tuoteperhe.isatuoteno
					join tuote tl on tl.yhtio=tuoteperhe.yhtio and tl.tuoteno=tuoteperhe.tuoteno
					where tuoteperhe.yhtio = '$kukarow[yhtio]'
					and tuoteperhe.tyyppi = '$hakutyyppi'
					$lisa1
					group by tuoteperhe.isatuoteno
					order by $lisalimit tuoteperhe.isatuoteno, tuoteperhe.tuoteno
					$limitteri";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			// Piirrellään formi
			// Kursorinohjaus
			$formi	= "haku";
			$kentta = "isatuoteno_haku";

			echo "<form name='haku' method='post'>";
			echo "<input type='hidden' name='toim' value='$toim'>";

			echo "<br><br><table>";
			echo "<tr><th>".t("Tuoteperhe")."</th><th>".t("Tuotteet")."</th></tr>";

			echo "<tr>";
			echo "<td><input type='text' size='20' name='isatuoteno_haku' value='$isatuoteno_haku'></td>";
			echo "<td><input type='text' size='20' name='tuoteno_haku' value='$tuoteno_haku'></td>";
			echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
			echo "</tr>";

			while ($prow = mysql_fetch_array($result)) {
				echo "<tr class='aktiivi'><td><a href='$PHP_SELF?toim=$toim&isatuoteno=".urlencode($prow["isatuoteno"])."&hakutuoteno=".urlencode($prow["isatuoteno"])."'>$prow[isatuoteno] $prow[nimitys]</a></td><td>$prow[tuotteet] $prow[nimitykset]</td></tr>";
			}

			echo "</table>";
			echo "</form>";
		}
	}

	require ("inc/footer.inc");

?>