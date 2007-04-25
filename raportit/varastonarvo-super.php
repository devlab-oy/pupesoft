<?php
	// k‰ytet‰‰n slavea
	$useslave = 1;

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);	
		exit;
	}
	else {

		echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

		if (!isset($pp)) $pp = date("d");
		if (!isset($kk)) $kk = date("m");
		if (!isset($vv)) $vv = date("Y");

		// tutkaillaan saadut muuttujat
		$osasto = trim($osasto);
		$try    = trim($try);
		$pp 	= sprintf("%02d", trim($pp));
		$kk 	= sprintf("%02d", trim($kk));
		$vv 	= sprintf("%04d", trim($vv));

		// h‰rski oikeellisuustzekki
		if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

		// n‰it‰ k‰ytet‰‰n queryss‰
		$sel_osasto = "";
		$sel_tuoteryhma = "";

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";

		// piirrell‰‰n formi
		echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";

		if ($valitaan_useita == "") {
	
			echo "<table>";
	
			// n‰ytet‰‰n soveltuvat osastot
			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
			$res2  = mysql_query($query) or die($query);

			$sel = "";
			$seltyhjat = "";
			if ($osasto == "kaikki") $sel = "selected";
			if ($osasto == "tyhjat") $seltyhjat = "selected";
	
			echo "<tr><th>Osasto:</th><td>";
			echo "<select name='osasto'>";
			echo "<option value=''>Valitse osasto</option>";
			echo "<option value='kaikki' $sel>N‰yt‰ kaikki</option>";
			echo "<option value='tyhjat' $seltyhjat>Osasto puuttuu</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$sel = "";
				if ($osasto == $rivi["selite"]) {
					$sel = "selected";
					$sel_osasto = $rivi["selite"];
				}
				echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select></td></tr>";

			$trylisa = "";
			$sort_osastot = "";
			if ($osasto != "kaikki" and $sel_osasto != "") {
				$trylisa = " and osasto='$sel_osasto' ";
				$sort_osastot = "&osasto=$sel_osasto";
			}

			// n‰ytet‰‰n soveltuvat tuoteryhm‰t
			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
			$res2   = mysql_query($query) or die($query);

			echo "<tr><th>Tuoteryhm‰:</th><td>";
			echo "<select name='tuoteryhma'>";
			echo "<option value=''>Valitse tuoteryhm‰</option>";

			$sel = "";
			$seltyhjat = "";
			if ($tuoteryhma == "kaikki") $sel = "selected";
			if ($tuoteryhma == "tyhjat") $seltyhjat = "selected";
			echo "<option value='kaikki' $sel>N‰yt‰ kaikki</option>";
			echo "<option value='tyhjat' $seltyhjat>Tuoteryhm‰ puuttuu</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$sel = "";
				if ($tuoteryhma == $rivi["selite"]) {
					$sel = "selected";
					$sel_tuoteryhma = $rivi["selite"];
				}

				echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select></td>";

			$sort_tryt = "";
			if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "") {
				$sort_tryt = "&tuoteryhma=$sel_tuoteryhma";
			}
	
			echo "<td class='back'><input type='submit' name='valitaan_useita' value='Valitse useita'></td></tr>";
			echo "</table>";
		}
		else {
			if ($mul_osasto == "") {

				echo "<table><tr><td valign='top' class='back'>";
		
				// n‰ytet‰‰n soveltuvat osastot
				$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
				$res2  = mysql_query($query) or die($query);
		
				if (mysql_num_rows($res2) > 11) {
					echo "<div style='height:265;overflow:auto;'>";
				}
	
				echo "<table>";
				echo "<tr><th colspan='2'>Valitse osasto(t):</th></tr>";
		
				echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='".t("Ei valintaa")."'></td><td>".t("Ei valintaa")."</td></tr>";
				echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td>".t("Ruksaa kaikki")."</td></tr>";
		
		
				while ($rivi = mysql_fetch_array($res2)) {
					echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='$rivi[selite]'></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
				}

				echo "</table>";
		
				if (mysql_num_rows($res2) > 11) {
					echo "</div>";
				}
		
				echo "<br>";
				echo "<input type='submit' name='valitaan_useita' value='Jatka'>";

				echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksitt‰in'></td></tr></table>";

			}
			elseif ($mul_try == "") {

				echo "<table><tr><td valign='top' class='back'>";
		
				if (count($mul_osasto) > 11) {
					echo "<div style='height:265;overflow:auto;'>";
				}
		
				echo "<table>";
				echo "<tr><th>Osasto(t):</th></tr>";

				$osastot = "";
				foreach ($mul_osasto as $kala) {
					echo "<input type='hidden' name='mul_osasto[]' value='$kala'>";

					$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' and avainsana.selite='$kala'";
					$res3   = mysql_query($query) or die($query);
					$selrow = mysql_fetch_array($res3);

					echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
					$osastot .= "'$kala',";
				}
				$osastot = substr($osastot,0,-1);

				echo "</table>";
		
				if (count($mul_osasto) > 11) {
					echo "</div>";
				}
		
				echo "</td><td valign='top' class='back'>";
		
				// n‰ytet‰‰n soveltuvat osastot
				$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
				$res2  = mysql_query($query) or die($query);
		
				if (mysql_num_rows($res2) > 11) {
					echo "<div style='height:265;overflow:auto;'>";
				}
		
				echo "<table>";
				echo "<tr><th colspan='2'>Valitse tuoteryhm‰(t):</th></tr>";
				echo "<tr><td><input type='checkbox' name='mul_try[]' value='".t("Ei valintaa")."'></td><td>".t("Ei valintaa")."</td></tr>";
				echo "<tr><td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td>Ruksaa kaikki</td></tr>";
		
				while ($rivi = mysql_fetch_array($res2)) {
					echo "<tr><td><input type='checkbox' name='mul_try[]' value='$rivi[selite]'></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
				}

				echo "</table>";
		
				if (mysql_num_rows($res2) > 11) {
					echo "</div>";
				}
			
				echo "<br>";
				echo "<input type='submit' name='valitaan_useita' value='Jatka'>";

				echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksitt‰in'></td></tr></table>";


			}
			else {

				echo "<table><tr><td valign='top' class='back'>";

				if (count($mul_osasto) > 11) {
					echo "<div style='height:265;overflow:auto;'>";
				}
		
				echo "<table>";
				echo "<tr><th>Osasto(t):</th></tr>";

				$osastot = "";
				$sort_osastot = "";

				foreach ($mul_osasto as $kala) {
					echo "<input type='hidden' name='mul_osasto[]' value='$kala'>";
			
					$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' and avainsana.selite='$kala'";
					$res3   = mysql_query($query) or die($query);
					$selrow = mysql_fetch_array($res3);

					echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
					$osastot .= "'$kala',";
					$sort_osastot .= "&mul_osasto[]=$kala";
				}
				$osastot = substr($osastot,0,-2); // vika pilkku ja vika hipsu pois
				$osastot = substr($osastot, 1);   // eka hipsu pois

				echo "</table>";
		
				if (count($mul_osasto) > 11) {
					echo "</div>";
				}

				echo "</td><td valign='top' class='back'>";

				if (count($mul_try) > 11) {
					echo "<div style='height:265;overflow:auto;'>";
				}
		
				echo "<table>";
				echo "<tr><th colspan='2'>Tuoteryhm‰(t):</th></tr>";

				$tryt = "";
				$sort_tryt = "";

				foreach ($mul_try as $kala) {
					echo "<input type='hidden' name='mul_try[]' value='$kala'>";
			
					$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' and avainsana.selite='$kala'";
					$res3   = mysql_query($query) or die($query);
					$selrow = mysql_fetch_array($res3);

					echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
					$tryt .= "'$kala',";
					$sort_tryt .= "&mul_try[]=$kala";
				}
				$tryt = substr($tryt,0,-2);  // vika pilkku ja vika hipsu pois
				$tryt = substr($tryt, 1);    // eka hipsu pois

				$sel_osasto = $osastot;
				$sel_tuoteryhma = $tryt;

				echo "</table>";
		
				if (count($mul_try) > 11) {
					echo "</div>";
				}
		
				echo "<br>";

				echo "</td><td valign='top' class='back'>";

				echo "<input type='submit' name='valitaan_useita' value='Valitse useita'>";
				echo "<input type='submit' name='dummy' value='Valitse yksitt‰in'>";

				echo "</td></tr></table>";
			}
		}

		echo "<br><table>";
		echo "<tr>";
		echo "<th>Syˆt‰ vvvv-kk-pp:</th>";
		echo "<td colspan='2'><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Tyyppi:</th>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($tyyppi == "A") {
			$sel1 = "SELECTED";
		}
		elseif($tyyppi == "B") {
			$sel2 = "SELECTED";
		}

		echo "<td>
				<select name='tyyppi'>
				<option value='A' $sel1>".t("N‰ytet‰‰n tuotteet joilla on saldoa")."</option>
				<option value='B' $sel2>".t("N‰ytet‰‰n tuotteet joilla ei ole saldoa")."</option>
				</select>
				</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Varastonarvorajaus:</th>";
		echo "<td>Alaraja:<input type='text' name='alaraja' size='7' value='$alaraja'> Yl‰raja:<input type='text' name='ylaraja' size='7' value='$ylaraja'></td>";
		echo "</tr>";
		
		
		$sel1 = "";
		$sel2 = "";

		if ($summaustaso == "S") {
			$sel1 = "SELECTED";
		}
		elseif($summaustaso == "P") {
			$sel2 = "SELECTED";
		}
		echo "<tr>";
		echo "<th>Summaustaso:</th>";
		
		echo "<td>
				<select name='summaustaso'>
				<option value='S' $sel1>".t("Varastonarvo summattuna")."</option>
				<option value='P' $sel2>".t("Varastonarvo varastopaikoittain (HUOM: Vain nykyinen varastonarvo lasketaan.)")."</option>
				</select>
				</td>";
		echo "</tr>";
		
		
		echo "</table>";
		echo "<br>";

		if($valitaan_useita == '') {
			echo "<input type='submit' value='Laske varastonarvot'>";
		}
		else {
			echo "<input type='submit' name='valitaan_useita' value='Laske varastonarvot'>";
		}

		echo "</form>";


		if ($sel_tuoteryhma != "" or $sel_osasto != "" or $osasto == "kaikki" or $tuoteryhma == "kaikki" or $osasto == "tyhjat" or $tuoteryhma == "tyhjat") {

			$trylisa1 = "";
			$trylisa2 = "";

			if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "" and $sel_tuoteryhma != t("Ei valintaa")) {
				$trylisa1 .= " and try in ('$sel_tuoteryhma') ";
			}
			if ($osasto != "kaikki" and $sel_osasto != "" and $sel_osasto != t("Ei valintaa")) {
				$trylisa1 .= " and osasto in ('$sel_osasto') ";
			}
			
			if ($tuoteryhma == "tyhjat") {
				$trylisa2 .= " HAVING try='0' ";
			}
			if ($osasto == "tyhjat") {
				if ($tuoteryhma == "tyhjat") {
					$trylisa2 .= " or osasto='0' ";
				}
				else {
					$trylisa2 .= " HAVING osasto='0' ";
				}
			}
		
			// haetaan halutut tuotteet
			$query  = "	SELECT tuote.tuoteno, 
						if(atry.selite is not null, atry.selite, 0) try, 
						if(aosa.selite is not null, aosa.selite, 0) osasto,
						tuote.nimitys, tuote.kehahin, tuote.epakurantti1pvm, tuote.epakurantti2pvm, tuote.sarjanumeroseuranta
						FROM tuote
						LEFT JOIN avainsana atry use index (yhtio_laji_selite) on atry.yhtio=tuote.yhtio and atry.selite=tuote.try and atry.laji='TRY'
						LEFT JOIN avainsana aosa use index (yhtio_laji_selite) on aosa.yhtio=tuote.yhtio and aosa.selite=tuote.try and aosa.laji='OSASTO'
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.ei_saldoa = ''
						$trylisa1
						$trylisa2
						ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
			$result = mysql_query($query) or pupe_error($query);
									
			$lask  = 0;
			$varvo = 0; // t‰h‰n summaillaan
	
			if(include('Spreadsheet/Excel/Writer.php')) {

				//keksit‰‰n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}
	
			if(isset($workbook)) {
				$excelsarake = 0;
				
				if ($summaustaso == "P") {
					$worksheet->write($excelrivi, $excelsarake, t("Varasto"), 	$format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllyalue"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllynro"), 	$format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllyvali"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllytaso"), $format_bold);
					$excelsarake++;
				}
				
				$worksheet->write($excelrivi, $excelsarake, t("Osasto"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm‰"), 	$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tuoteno"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Nimitys"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Saldo"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Kehahin"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Varastonarvo"), 	$format_bold);
				
				$excelrivi++;
				$excelsarake = 0;
			}

			while ($row = mysql_fetch_array($result)) {

				$kehahin = 0;
		
				// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilˆiden ostohinnoista (ostetut yksilˆt jotka eiv‰t viel‰ ole myyty(=laskutettu))
				if ($row["sarjanumeroseuranta"] != '') {
					$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
								FROM sarjanumeroseuranta
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
								LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
								WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
								and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
								and tilausrivi_osto.laskutettuaika != '0000-00-00'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);
						
					$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
				}
				else {
					$kehahin = sprintf('%.2f', $row["kehahin"]);
				}
				
				
				if ($summaustaso == "S") {
					// tuotteen muutos varastossa annetun p‰iv‰n j‰lkeen
					$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
					 			FROM tapahtuma use index (yhtio_tuote_laadittu)
					 			WHERE yhtio = '$kukarow[yhtio]'
					 			and tuoteno = '$row[tuoteno]'
					 			and laadittu > '$vv-$kk-$pp 23:59:59'";
					$mres = mysql_query($query) or pupe_error($query);
					$mrow = mysql_fetch_array($mres);
				}
				else {
					$mrow["muutoshinta"]	= 0;
					$mrow["muutoskpl"]		= 0;
					
					$pp = date("d");
					$kk = date("m");
					$vv = date("Y");
				}
				
				
				// katotaan onko tuote ep‰kurantti nyt
				$kerroin = 1;
				if ($row['epakurantti1pvm'] != '0000-00-00') {
					$kerroin = 0.5;
				}
				if ($row['epakurantti2pvm'] != '0000-00-00') {
					$kerroin = 0;
				}

				// tuotteen m‰‰r‰ varastossa nyt
				
				if ($summaustaso == "S") {
					$query = "	SELECT sum(saldo) varasto
				   				FROM tuotepaikat use index (tuote_index)
				   				WHERE yhtio = '$kukarow[yhtio]'
				   				and tuoteno = '$row[tuoteno]'";
				}
				else {
					$query = "	SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso, saldo varasto
				   				FROM tuotepaikat use index (tuote_index)
				   				WHERE yhtio = '$kukarow[yhtio]'
				   				and tuoteno = '$row[tuoteno]'";
				}
				$vres = mysql_query($query) or pupe_error($query);
				
				while ($vrow = mysql_fetch_array($vres)) {

					// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
					$muutoshinta = ($vrow["varasto"] * $kehahin * $kerroin) - $mrow["muutoshinta"];

					// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
					$muutoskpl = $vrow["varasto"] - $mrow["muutoskpl"];

		
					if ($tyyppi == "A" and $muutoskpl != 0) {
						$ok = "GO";
					}		
					elseif ($tyyppi == "B" and $muutoskpl == 0) {
						$ok = "GO";
					}
					else {
						$ok = "NO-GO";
					}
		
					if ($muutoshinta < $alaraja and $alaraja != '') {
						$ok = "NO-GO";
					}
		
					if ($muutoshinta > $ylaraja and $ylaraja != '') {
						$ok = "NO-GO";
					}
		
					if ($ok == "GO") {
			
						$lask++;
			
						// summataan varastonarvoa
						$varvo += $muutoshinta;

						// yritet‰‰n kaivaa listaan viel‰ sen hetkinen kehahin jos se halutaan kerran n‰hd‰
						$kehasilloin = $kehahin * $kerroin; // nykyinen kehahin

						// jos ollaan annettu t‰m‰ p‰iv‰ niin ei ajeta t‰t‰ , koska nykyinen kehahin on oikein ja n‰in on nopeempaa! wheee!
						if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {
							// katotaan mik‰ oli tuotteen viimeisin hinta annettuna p‰iv‰n‰ tai sitten sit‰ ennen
							$query = "	SELECT hinta
										FROM tapahtuma use index (yhtio_tuote_laadittu)
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
										and laadittu <= '$vv-$kk-$pp 23:59:59'
										and hinta <> 0
										ORDER BY laadittu desc
										LIMIT 1";
							$ares = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($ares) == 1) {
								// lˆydettiin keskihankintahinta tapahtumista k‰ytet‰‰n
								$arow = mysql_fetch_array($ares);
								$kehasilloin = $arow["hinta"];
								$kehalisa = "";
							}
							else {
								// ei lˆydetty alasp‰in, kokeillaan kattoo l‰hin hinta ylˆsp‰in
								$query = "	SELECT hinta
											FROM tapahtuma use index (yhtio_tuote_laadittu)
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$row[tuoteno]'
											and laadittu >= '$vv-$kk-$pp 23:59:59'
											and hinta <> 0
											ORDER BY laadittu
											LIMIT 1";
								$ares = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($ares) == 1) {
									// lˆydettiin keskihankintahinta tapahtumista k‰ytet‰‰n
									$arow = mysql_fetch_array($ares);
									$kehasilloin = $arow["hinta"];
									$kehalisa = "";
								}
								else {
									$kehalisa = "~";	
								}
							}
						}

						if(isset($workbook)) {						
							if ($summaustaso == "P") {
								$rivipaikka = kuuluukovarastoon($vrow["hyllyalue"], $vrow["hyllynro"]);
								
								if ($rivipaikka > 0) {
									$query  = "SELECT nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' and tunnus = '$rivipaikka'";
									$paikkaresult = mysql_query($query) or pupe_error($query);
									$paikkarow    = mysql_fetch_array($paikkaresult);
								}
								else {
									$paikkarow = array();
								}
								
								$worksheet->write($excelrivi, $excelsarake, $paikkarow["nimitys"], 	$format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, $vrow["hyllyalue"], $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, $vrow["hyllynro"], 	$format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, $vrow["hyllyvali"], $format_bold);
								$excelsarake++;
								$worksheet->write($excelrivi, $excelsarake, $vrow["hyllytaso"], $format_bold);
								$excelsarake++;
							}
							
							$worksheet->write($excelrivi, $excelsarake, $row["osasto"]);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $row["try"]);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $row["tuoteno"]);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, asana('nimitys_',$row['tuoteno'],$row['nimitys']));
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$muutoskpl));
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$kehasilloin));
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$muutoshinta));
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $kehalisa);
				
							$excelrivi++;
							$excelsarake = 0;
						}
					}
				}
			}
	
			echo "<br><br>Lˆytyi $lask tuotetta.<br><br>";

			// We need to explicitly close the workbook
			$workbook->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Varastonarvo.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

			echo "<table>";
			echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
			echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
			echo "</table>";

		}

		require ("../inc/footer.inc");
	}
?>