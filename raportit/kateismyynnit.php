<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Käteismyynnit")." $myy:</font><hr>";

	// Tarkistetaan että jos ei ole täsmäys päällä niin lukitaan täsmäyksen päivämäärät. Jos täsmäys on päällä, lukitaan normaalin raportin päivämäärät
	echo "	<script type='text/javascript' language='JavaScript'>
			<!--
				function disableDates() {
					if (document.getElementById('tasmays').checked != true) {
						document.getElementById('pp').disabled = true;
						document.getElementById('kk').disabled = true;
						document.getElementById('vv').disabled = true;

						document.getElementById('ppa').disabled = false;
						document.getElementById('kka').disabled = false;
						document.getElementById('vva').disabled = false;

						document.getElementById('ppl').disabled = false;
						document.getElementById('kkl').disabled = false;
						document.getElementById('vvl').disabled = false;
					}
					else {
						document.getElementById('pp').disabled = false;
						document.getElementById('kk').disabled = false;
						document.getElementById('vv').disabled = false;

						document.getElementById('ppa').disabled = true;
						document.getElementById('kka').disabled = true;
						document.getElementById('vva').disabled = true;

						document.getElementById('ppl').disabled = true;
						document.getElementById('kkl').disabled = true;
						document.getElementById('vvl').disabled = true;
					}
				}
			-->
			</script>";

	$lockdown = "";
	$disabled = "";
	$pohjakassa_tasmays = "";
	$loppukassa_tasmays = "";
	$solut = array();

	// Lockdown-funktio, joka tarkistaa onko kyseinen kassalipas jo täsmätty.
	function lockdown($vv, $kk, $pp, $tasmayskassa) {
		global $kukarow;

		if ($tasmayskassa == 'MUUT') {
			$row["nimi"] = 'MUUT';
		}
		else {
			$query = "SELECT nimi FROM kassalipas WHERE tunnus='$tasmayskassa' AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);
		}

		$tasmays_query = "	SELECT group_concat(distinct lasku.tunnus) ltunnukset
							FROM lasku
							JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
							AND tiliointi.ltunnus = lasku.tunnus
							AND tiliointi.selite LIKE '%$row[nimi]%'
							AND tiliointi.korjattu = '')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							AND lasku.tila = 'X'
							AND lasku.tapvm = '$vv-$kk-$pp'";
		$tasmays_result = mysql_query($tasmays_query) or pupe_error($tasmays_query);
		$tasmaysrow = mysql_fetch_array($tasmays_result);

		if ($tasmaysrow["ltunnukset"] != "") {
			$tasmatty = array();
			$tasmatty["ltunnukset"] = $tasmaysrow["ltunnukset"];
			$tasmatty["kassalipas"] = $row["nimi"];
			return $tasmatty;
		}
		else {
			return false;
		}
	}

	// Jos täsmäys on päällä ja ei olla valittu mitään kassalipasta -> error
	if ($tasmays != '' and count($kassakone) == 0 and $muutkassat == '') {
		echo "<font class='error'>".t("Valitse kassalipas")."!</font><br>";
		$tee = '';
	}

	// Jos täsmäys on päällä ja tilitettävien sarakkeiden määrä on jotain muuta kuin väliltä 1-9 -> error
	if ($tasmays != '' and ((int)$tilityskpl < 1 or (int)$tilityskpl > 9)) {
		echo "<font class='error'>".t("Tilitysten määrä pitää olla väliltä 1 - 9")."!</font><br>";
		$tee = '';
	}

	// Jos täsmäys on päällä ja ei olla annettu päivämäärää -> error
	if ($tasmays != '' and ($vv == '' or $kk == '' or $pp == '')) {
		echo "<font class='error'>".t("Syötä päivämäärä (pp-kk-vvvv)")."</font><br>";
		$tee = '';
	}

	if ($tasmays != '' and $katsuori != '') {
		echo "<font class='error'>".t("Sinä et osaa vielä täsmäyttää käteissuorituksia.")."</font><br>";
		$tee = '';
	}

	if ($tasmays != '' and count($kassakone) > 0) {
		$kassat_temp = "";

		foreach($kassakone as $var) {
			$kassat_temp .= "'".$var."',";
		}
		$kassat_temp = substr($kassat_temp,0,-1);

		$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp) and kassa != '' and pankkikortti != '' and luottokortti != '' and kateistilitys != '' and kassaerotus != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != count($kassakone)) {
			echo "<font class='error'>".t("Ei voida täsmäyttää. Kassalippaan pakollisia tietoja puuttuu").".</font><br>";
			$tee = '';
		}
	}

	// Aloitetaan tiliöinti
	if ($tee == "tiliointi") {

		// Pohjakassan pilkut pisteiksi
		$pohjakassa = str_replace(",",".",sprintf('%.2f',$pohjakassa));

		// Haetaan kassalippaat tietokannasta
		$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$kassalipasrow = mysql_fetch_array($result);

		// Laitetaan talteen pohjakassa -> sisviesti1 ja loppukassa -> sisviesti2
		$query = "INSERT INTO lasku SET
					yhtio      = '$kukarow[yhtio]',
					tapvm      = '$vv-$kk-$pp',
					tila       = 'X',
					sisviesti1 = '$pohjakassa',
					sisviesti2 = '$loppukassa2',
					laatija    = '$kukarow[kuka]',
					luontiaika = now()";
		$result = mysql_query($query) or pupe_error($query);
		$laskuid = mysql_insert_id();

		$tilino = "";
		$maksutapa = "";
		$kassalipas = "";

		foreach ($_POST as $kentta => $arvo) {

			if (stristr($arvo, "pankkikortti")) {
				$maksutapa = t("Pankkikortti");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);
			}
			elseif (stristr($arvo, "luottokortti")) {
				$maksutapa = t("Luottokortti");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);
			}
			elseif (stristr($arvo, "kateinen")) {
				$maksutapa = t("Käteinen");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);
				$kateistilitys = $tilino;
			}

			// Tarkistetaan ettei arvo ole nolla ja jos kentän nimi on joko solu tai erotus
			// Ei haluta tositteeseen nollarivejä
			if (abs($arvo) > 0 and (stristr($kentta, "solu") or stristr($kentta, "erotus"))) {

				// Pilkut pisteiksi
				$arvo = str_replace(",",".",$arvo);

				// Aletaan rakentaa inserttiä
				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',";

				// Jos kentän nimi on soluerotus niin se tiliöidään kassaerotustilille (eli täsmäyserot), muuten normaalisti ylempänä parsetettu tilinumero
				if (stristr($kentta, "soluerotus")) {
					$query .= "tilino = '$kassalipasrow[kassaerotus]',";
				}
				else {
					$query .= "tilino   = '$tilino',";
				}

				$query .=  "kustp    = '',
							tapvm    = '$vv-$kk-$pp',";

				// Jos kenttä on soluerotus tai erotus niin kerrotaan arvo -1:llä
				if (stristr($kentta, "soluerotus") or stristr($kentta, "erotus")) {
					$query .= "summa = $arvo * -1,";
				}
				else {
					$query .= "summa = '$arvo',";
				}

				$query .= "	vero     = '0',
							lukko    = '',
							selite   = '$kassalipas $maksutapa";

				// Jos kenttä on erotus niin lisätään selitteeseen "erotus"
				if (stristr($kentta, "erotus")) {
					$query .= " ".t("erotus")."',";
				}
				// Jos kenttä on soluerotus niin lisätään selitteeseen "kassaero"
				elseif (stristr($kentta, "soluerotus")) {
					$query .= " ".t("kassaero")."',";
				}
				else {
					$query .= "',";
				}

				$query .=  "laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos kenttä on käteistilitys, niin toinen tiliöidään käteistilitys-tilille ja se summa myös miinustetaan kassasta
			if (abs($arvo) > 0 and stristr($kentta, "kateistilitys")) {
				$arvo = str_replace(",",".",$arvo);
				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$yhtiorow[kateistilitys]',
							kustp    = '',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo,
							vero     = '0',
							lukko    = '',
							selite   = '$kassalipas ".t("Käteistilitys pankkiin")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);

				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kassa]',
							kustp    = '',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo * -1,
							vero     = '0',
							lukko    = '',
							selite   = '$kassalipas ".t("Käteistilitys pankkiin")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}

	elseif ($tee != '') {

		//Jos halutaa failiin
		if ($printteri != '') {
			$vaiht = 1;
		}
		else {
			$vaiht = 0;
		}

		$kassat = "";
		$lisa   = "";

		if (is_array($kassakone)) {
			foreach($kassakone as $var) {
				$kassat .= "'".$var."',";
			}
			$kassat = substr($kassat,0,-1);
		}

		if ($muutkassat != '') {
			if ($kassat != '') {
				$kassat .= ",''";
			}
			else {
				$kassat = "''";
			}
		}

		if ($kassat != "") {
			$kassat = " and lasku.kassalipas in ($kassat) ";
		}
		else {
			$kassat = " and lasku.kassalipas = 'ei nayteta eihakat, akja'";
		}

		if ((int) $myyjanro > 0) {
			$query = "	SELECT tunnus
						FROM kuka
						WHERE yhtio	= '$kukarow[yhtio]'
						and myyja 	= '$myyjanro'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			$lisa = " and lasku.myyja='$row[tunnus]' ";
		}
		elseif ($myyja != '') {
			$lisa = " and lasku.laatija='$myyja' ";
		}

		$lisa .= " and lasku.vienti in (";

		if ($koti == 'KOTI' or ($koti=='' and $ulko=='')) {
			$lisa .= "''";
		}

		if ($ulko == 'ULKO') {
			if ($koti == 'KOTI') {
				$lisa .= ",";
				}
			$lisa .= "'K','E'";
		}
		$lisa .= ") ";

		if ($tasmays != '') {
			//ylikirjotetaan koko lisä, koska ei saa olla muita rajauksia
			$lisa = " and lasku.tapvm = '$vv-$kk-$pp'";
		}
		else {
			if ($vva == $vvl and $kka == $kkl and $ppa == $ppl) {
				$lisa .= " and lasku.tapvm = '$vva-$kka-$ppa'";
			}
			else {
				$lisa .= " and lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl'";
			}
		}

		$myyntisaamiset_tilit = "'{$yhtiorow['kassa']}','{$yhtiorow['pankkikortti']}','{$yhtiorow['luottokortti']}',";

		if (count($kassakone) > 0) {
			$kassat_temp = "";

			foreach($kassakone as $var) {
				$kassat_temp .= "'".$var."',";
			}

			$kassat_temp = substr($kassat_temp,0,-1);

			$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp)";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == count($kassakone)) {
				while ($row = mysql_fetch_array($result)) {
					if ($row["kassa"] != $yhtiorow["kassa"]) {
						$myyntisaamiset_tilit .= "'{$row['kassa']}',";
					}
					if ($row["pankkikortti"] != $yhtiorow["pankkikortti"]) {
						$myyntisaamiset_tilit .= "'{$row['pankkikortti']}',";
					}
					if ($row["luottokortti"] != $yhtiorow["luottokortti"]) {
						$myyntisaamiset_tilit .= "'{$row['luottokortti']}',";
					}
				}
			}
			else {
				die("virhe");
			}

		}

		$myyntisaamiset_tilit = substr($myyntisaamiset_tilit, 0, -1);

		//jos monta kassalipasta niin tungetaan tämä queryyn.
		if (count($kassakone) > 1 and $tasmays != '') {
			$selecti = "if(tiliointi.tilino = kassalipas.kassa OR tiliointi.tilino = '$yhtiorow[kassa]', concat('Kateinen ', kassalipas.nimi),
				if(tiliointi.tilino = kassalipas.pankkikortti OR tiliointi.tilino = '$yhtiorow[pankkikortti]', 'Pankkikortti',
				if(tiliointi.tilino = kassalipas.luottokortti OR tiliointi.tilino = '$yhtiorow[luottokortti]', 'Luottokortti', 'Muut'))) tyyppi, ";
		}
		else {
			$selecti = "if(tiliointi.tilino = kassalipas.kassa OR tiliointi.tilino = '$yhtiorow[kassa]', 'Kateinen',
				if(tiliointi.tilino = kassalipas.pankkikortti OR tiliointi.tilino = '$yhtiorow[pankkikortti]', 'Pankkikortti',
				if(tiliointi.tilino = kassalipas.luottokortti OR tiliointi.tilino = '$yhtiorow[luottokortti]', 'Luottokortti', 'Muut'))) tyyppi, ";
		}

		//Haetaan käteislaskut
		$query = "	SELECT
					$selecti
					if(lasku.kassalipas = '', 'Muut', lasku.kassalipas) kassa,
					if(ifnull(kassalipas.nimi, '') = '', 'Muut', kassalipas.nimi) kassanimi,
					tiliointi.tilino,
					tiliointi.summa tilsumma,
					lasku.nimi,
					lasku.ytunnus,
					lasku.laskunro,
					lasku.tunnus,
					lasku.summa,
					lasku.laskutettu,
					lasku.tapvm,
					lasku.kassalipas,
					tiliointi.ltunnus,
					kassalipas.tunnus ktunnus
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN maksuehto ON (maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen != '')
					LEFT JOIN tiliointi ON (tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.korjattu = '' and tiliointi.tilino in ($myyntisaamiset_tilit))
					LEFT JOIN kassalipas ON (kassalipas.yhtio=lasku.yhtio and kassalipas.tunnus=lasku.kassalipas)
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'U' and lasku.alatila = 'X'
					$lisa
					$kassat
					ORDER BY kassa, kassanimi, tyyppi";
		$result = mysql_query($query) or pupe_error($query);

		$i = 1;

		// Tarkistetaan ensiksi onko kassalippaat jo tiliöity lockdown-funktion avulla
		if ($tasmays != '') {
			$ltunnusx = array();
			if ($kassakone != '') {
				foreach ($kassakone as $kassax) {
					if ($ltunnusx = lockdown($vv, $kk, $pp, $kassax)) {
						echo "$ltunnusx[kassalipas] ".t("on jo täsmätty. Tosite löytyy")." <a href='".$palvelin2."muutosite.php?tee=E&tunnus=$ltunnusx[ltunnukset]'>".t("täältä")."</a><br>";
						$i++;
					}
				}
				if ($muutkassat != '') {
					if ($ltunnusx = lockdown($vv, $kk, $pp, $muutkassat)) {
						echo "$ltunnusx[kassalipas] ".t("kassat on jo täsmätty. Tosite löytyy")." <a href='".$palvelin2."muutos	ite.php?tee=E&tunnus=$ltunnusx[ltunnukset]'>".t("täältä")."</a><br>";
						$i++;
					}
				}
			}
			elseif ($kassakone == '' and $muutkassat != '') {
				if ($ltunnusx = lockdown($vv, $kk, $pp, $muutkassat)) {
					echo "$ltunnusx[kassalipas] ".t("kassat on jo täsmätty. Tosite löytyy")." <a href='".$palvelin2."muutos	ite.php?tee=E&tunnus=$ltunnusx[ltunnukset]'>".t("täältä")."</a><br>";
					$i++;
				}
			}
		}

		if ($i > 1) {
			// Jos tositteita löytyy niin ei tehdä mitään
		}
		else {
			if ($tasmays != '') {
				echo "<table><td>";
				echo "<font class='head'>".t("Täsmäys").":</font><br>";
				echo "<form method='post' action='$PHP_SELF' id='tasmaytysform' onSubmit='return verify();'>";
				echo "<input type='hidden' name='tee' value='tiliointi'>";
				echo "<table width='100%'>";
				echo "<tr><th colspan='";
					if ($tilityskpl > 1) {
						echo $tilityskpl+1;
					}
					else {
						echo "2";
					}
				echo "' align='center'>".t("Pohjakassa").":</th><td align='center' class='tumma' style='width:100px'>";
				echo "<input type='text' id='pohjakassa' name='pohjakassa' value='' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'>";
				echo "</td><td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";
				echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td align='center' style='width:100px'>".strtoupper(t("Tilitys"))." 1</td>";
					if ($tilityskpl > 1) {
						for ($yyy = 1; $yyy < $tilityskpl; $yyy++) {
							$yyyy = $yyy + 1;
							echo "<td align='center' style='width:100px'>".strtoupper(t("Tilitys"))." $yyyy</td>";
						}
					}

				echo "<td align='center' style='width:100px'>".strtoupper(t("Myynti"))."</td><td align='center' style='width:100px'>".strtoupper(t("Erotus"))."</td></tr>";
				echo "";
				echo "</tr></table>";
			}
			else {
				echo "<table><td>";
			}

			echo "<table width='100%' id='nayta$i' style='display:none'><tr>
					<th nowrap>".t("Kassa")."</th>
					<th nowrap>".t("Asiakas")."</th>
					<th nowrap>".t("Ytunnus")."</th>
					<th nowrap>".t("Laskunumero")."</th>
					<th nowrap>".t("Pvm")."</th>
					<th nowrap>$yhtiorow[valkoodi]</th></tr>";

			if ($vaiht == 1) {
				//kirjoitetaan  faili levylle..
				$filenimi = "/tmp/KATKIRJA.txt";
				$fh = fopen($filenimi, "w+");

				$ots  = t("Käteismyynnin päiväkirja")." $yhtiorow[nimi] $ppa.$kka.$vva-$ppl.$kkl.$vvl\n\n";
				$ots .= sprintf ('%-20.20s', t("Kassa"));
				$ots .= sprintf ('%-25.25s', t("Asiakas"));
				$ots .= sprintf ('%-10.10s', t("Y-tunnus"));
				$ots .= sprintf ('%-12.12s', t("Laskunumero"));
				$ots .= sprintf ('%-12.12s', t("Pvm"));
				$ots .= sprintf ('%-13.13s', "$yhtiorow[valkoodi]");
				$ots .= "\n";
				$ots .= "---------------------------------------------------------------------------------------\n";
				fwrite($fh, $ots);
				$ots = chr(12).$ots;
			}

			$rivit = 1;
			$yhteensa = 0;
			$kassayhteensa = 0;

			$myynti_yhteensa = 0;
			$pankkikortti = "";
			$luottokortti = "";
			$edkassa = "";
			$solu = "";
			$tilinumero = array();
			$kassalippaat = array();

			if ($tasmays != '') {
				while ($row = mysql_fetch_array($result)) {

					if ($row["tyyppi"] == 'Pankkikortti') {
						$pankkikortti = true;
					}
					if ($row["tyyppi"] == 'Luottokortti') {
						$luottokortti = true;
					}

					if (substr($row["tyyppi"], 0, 8) == 'Kateinen') {

						if ($edkassa != $row["kassa"] or ($kateinen != $row["tilino"] and $kateinen != '')) {

							if (substr($kateismaksu, 0, 8) == 'Kateinen') {

								$kassalippaat[$edkassanimi] = $edkassanimi;

								if ($row["tilino"] != '') {
									$tilinumero["kateinen"] = $row["tilino"];
								}
								elseif ($kateinen != '') {
									$tilinumero["kateinen"] = $kateinen;
								}

								$solu = "kateinen";

								echo "</table><table width='100%'>";
								echo "<tr>";
								echo "<td colspan='6'";
								echo "' class='tumma'>$kateismaksu ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
								echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[kateinen]#$edkassanimi'>";
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
								if ($tilityskpl > 1) {
									$y = $i;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
									}
								}
								echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' size='10' disabled></td>";
								echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
								echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
								echo "</tr>";
								$i++;
							}
						}

						if ($edkassa != $row["kassa"] and $edkassa != '') {
							echo "<tr><td>&nbsp;</td></tr>";
							echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
							echo "<tr>
									<th>".t("Kassa")."</th>
									<th>".t("Asiakas")."</th>
									<th>".t("Ytunnus")."</th>
									<th>".t("Laskunumero")."</th>
									<th>".t("Pvm")."</th>
									<th>$yhtiorow[valkoodi]</th></tr>";

							$kassayhteensa = 0;
							$kateismaksuyhteensa = 0;
						}

						echo "<tr class='aktiivi'>";
						echo "<td>$row[kassanimi]</td>";
						echo "<td>".substr($row["nimi"],0,23)."</td>";
						echo "<td>$row[ytunnus]</td>";
						echo "<td>$row[laskunro]</td>";
						echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
						echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

						$kateismaksu = $row['tyyppi'];
						$kateismaksuyhteensa += $row["tilsumma"];
						$yhteensa += $row["tilsumma"];
						$kassayhteensa += $row["tilsumma"];

						$kateinen    = $row["tilino"];
						$edkassa 	 = $row["kassa"];
						$edkassanimi = $row["kassanimi"];
						$edkateismaksu = $kateismaksu;
					}

				}

				if ($edkassa != '') {

					if (substr($kateismaksu, 0, 8) == 'Kateinen') {

						if ($row["tilino"] != '') {
							$tilinumero["kateinen"] = $row["tilino"];
						}
						elseif ($kateinen != '') {
							$tilinumero["kateinen"] = $kateinen;
						}

						$solu = "kateinen";

						$kassalippaat[$edkassanimi] = $edkassanimi;

						echo "</table><table width='100%'>";
						echo "<tr><td colspan='6' class='tumma'>$kateismaksu ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
						echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[kateinen]#$edkassanimi'>";
						echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						if ($tilityskpl > 1) {
							$y = $i;
							for ($yy = 1; $yy < $tilityskpl; $yy++) {
								$y .= $i;
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
							}
						}
						echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
						echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
						echo "</tr>";
						echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
						echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
						$i++;
					}
				}

				if (count($kassakone) > 1) {
					echo "<tr><td>&nbsp;</td></tr>";
				}

				if ($pankkikortti) {
					mysql_data_seek($result,0);
					$kateismaksuyhteensa = 0;
					$i++;

					echo "</table><table id='nayta$i' style='display:none' width='100%'>";
					echo "<tr>
							<th>".t("Kassa")."</th>
							<th>".t("Asiakas")."</th>
							<th>".t("Ytunnus")."</th>
							<th>".t("Laskunumero")."</th>
							<th>".t("Pvm")."</th>
							<th>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_array($result)) {

						if ($row["tyyppi"] == 'Pankkikortti') {

							if ($row["tilino"] != '') {
								$tilinumero["pankkikortti"] = $row["tilino"];
							}
							elseif ($kateinen != '') {
								$tilinumero["pankkikortti"] = $kateinen;
							}

							$solu = "pankkikortti";

							echo "<tr class='aktiivi'>";
							echo "<td>$row[kassanimi]</td>";
							echo "<td>".substr($row["nimi"],0,23)."</td>";
							echo "<td>$row[ytunnus]</td>";
							echo "<td>$row[laskunro]</td>";
							echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
							echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

							$kateinen    = $row["tilino"];
							$edkassa 	 = $row["kassa"];
							$edkassanimi = $row["kassanimi"];
							$edkateismaksu = $kateismaksu;

							$kateismaksu = $row['tyyppi'];
							$kateismaksuyhteensa += $row["tilsumma"];
							$yhteensa += $row["tilsumma"];
							$kassayhteensa += $row["tilsumma"];

							$kateismaksu = "";
						}
					}

					$kassalippaat[$edkassanimi] = $edkassanimi;

					echo "</table><table width='100%'>";
					echo "<tr><input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[pankkikortti]#";
						if (count($kassakone) > 1) {
							foreach ($kassalippaat as $key => $lipas) {
								if (reset($kassalippaat) == $lipas) {
									echo "$lipas ";
								}
								else {
									echo " / $lipas";
								}
							}
						}
						else {
							echo "$edkassanimi";
						}
					echo "'>";
					echo "<td colspan='6' class='tumma'>Pankkikortti ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
					if ($tilityskpl > 1) {
						$y = $i;
						for ($yy = 1; $yy < $tilityskpl; $yy++) {
							$y .= $i;
							echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						}
					}
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
					echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
					echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
					echo "</tr>";
				}

				if ($luottokortti) {
					mysql_data_seek($result,0);
					$kateismaksuyhteensa = 0;
					$i++;

					echo "</table><table id='nayta$i' style='display:none' width='100%'>";
					echo "<tr>
							<th>".t("Kassa")."</th>
							<th>".t("Asiakas")."</th>
							<th>".t("Ytunnus")."</th>
							<th>".t("Laskunumero")."</th>
							<th>".t("Pvm")."</th>
							<th>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_array($result)) {

						if ($row["tyyppi"] == 'Luottokortti') {

							if ($row["tilino"] != '') {
								$tilinumero["luottokortti"] = $row["tilino"];
							}
							elseif ($kateinen != '') {
								$tilinumero["luottokortti"] = $kateinen;
							}

							$solu = "luottokortti";

							echo "<tr class='aktiivi'>";
							echo "<td>$row[kassanimi]</td>";
							echo "<td>".substr($row["nimi"],0,23)."</td>";
							echo "<td>$row[ytunnus]</td>";
							echo "<td>$row[laskunro]</td>";
							echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
							echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

							$kateinen    = $row["tilino"];
							$edkassa 	 = $row["kassa"];
							$edkassanimi = $row["kassanimi"];
							$edkateismaksu = $kateismaksu;

							$kateismaksu = $row['tyyppi'];
							$kateismaksuyhteensa += $row["tilsumma"];
							$yhteensa += $row["tilsumma"];
							$kassayhteensa += $row["tilsumma"];

							$kateismaksu = "";
						}
					}

					$kassalippaat[$edkassanimi] = $edkassanimi;

					echo "</table><table width='100%'>";
					echo "<tr>";
					echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[luottokortti]#";
						if (count($kassakone) > 1) {
							foreach ($kassalippaat as $key => $lipas) {
								if (reset($kassalippaat) == $lipas) {
									echo "$lipas";
								}
								else {
									echo " / $lipas";
								}
							}
						}
						else {
							echo "$edkassanimi";
						}
					echo "'>";
					echo "<td colspan='6' class='tumma'>Luottokortti ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
					if ($tilityskpl > 1) {
						$y = $i;
						for ($yy = 1; $yy < $tilityskpl; $yy++) {
							$y .= $i;
							echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						}
					}
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
					echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
					echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
					echo "</tr>";
				}
			}
			else {
				while ($row = mysql_fetch_array($result)) {

					if ((($edkassa != $row["kassa"] and $edkassa != '') or ($kateinen != $row["tilino"] and $kateinen != ''))) {
						echo "</table><table width='100%'>";
						echo "<tr><td colspan='7' class='tumma'>$edtyyppi ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
						echo "<td align='right' class='tumma' style='width:100px'><b><div id='erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td></tr>";
						$i++;

						if ($edkassa == $row["kassa"]) {
							echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
							echo "<tr>
									<th nowrap>".t("Kassa")."</th>
									<th nowrap>".t("Asiakas")."</th>
									<th nowrap>".t("Ytunnus")."</th>
									<th nowrap>".t("Laskunumero")."</th>
									<th nowrap>".t("Pvm")."</th>
									<th nowrap>$yhtiorow[valkoodi]</th></tr>";
						}

						if ($vaiht == 1) {
							$prn  = sprintf ('%-35.35s', 	$kateismaksu." ".t("yhteensä").":");
							$prn .= "............................................";
							$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kateismaksuyhteensa)));
							$prn .= "\n";

							fwrite($fh, $prn);
							$rivit++;
						}
						$kateismaksuyhteensa = 0;
					}

					if ($edkassa != $row["kassa"] and $edkassa != '') {

						echo "<tr><th colspan='7'>$edkassanimi yhteensä:</th>";
						echo "<td align='right' class='tumma'><b>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</b></td></tr>";
						echo "<tr><td>&nbsp;</td></tr>";
						echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
						echo "<tr>
								<th>".t("Kassa")."</th>
								<th>".t("Asiakas")."</th>
								<th>".t("Ytunnus")."</th>
								<th>".t("Laskunumero")."</th>
								<th>".t("Pvm")."</th>
								<th>$yhtiorow[valkoodi]</th></tr>";

						if ($vaiht == 1) {
							$prn  = sprintf ('%-35.35s', 	$edkassanimi." ".t("yhteensä").":");
							$prn .= "............................................";
							$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
							$prn .= "\n\n";

							fwrite($fh, $prn);
							$rivit++;
						}

						$kassayhteensa = 0;
						$kateismaksuyhteensa = 0;
					}

					echo "<tr class='aktiivi'>";
					echo "<td>$row[kassanimi]</td>";
					echo "<td>".substr($row["nimi"],0,23)."</td>";
					echo "<td>$row[ytunnus]</td>";
					echo "<td>$row[laskunro]</td>";
					echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
					echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

					$kateinen    = $row["tilino"];
					$edkassa 	 = $row["kassa"];
					$edkassanimi = $row["kassanimi"];
					$edkateismaksu = $kateismaksu;
					$edtyyppi = $row["tyyppi"];

					$kateismaksu = $row['tyyppi'];

					if ($vaiht == 1) {
						if ($rivit >= 60) {
							fwrite($fh, $ots);
							$rivit = 1;
						}
						$prn  = sprintf ('%-20.20s', 	$row["kassanimi"]);
						$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
						$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
						$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
						$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
						$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
						$prn .= "\n";

						fwrite($fh, $prn);
						$rivit++;
					}

					$kateismaksuyhteensa += $row["tilsumma"];
					$yhteensa += $row["tilsumma"];
					$kassayhteensa += $row["tilsumma"];
				}

				if ($edkassa != '') {
					echo "</table><table width='100%'>";
					echo "<tr><td colspan='6' class='tumma'>$edtyyppi ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></th>";
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td></tr>";

					echo "<tr><th colspan='6'>$edkassanimi yhteensä:</th>";
					echo "<td align='right' class='tumma'><b>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</b></td></tr>";

					if ($vaiht == 1) {
						$prn  = sprintf ('%-35.35s', 	$kateismaksu." ".t("yhteensä").":");
						$prn .= "............................................";
						$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kateismaksuyhteensa)));
						$prn .= "\n";

						fwrite($fh, $prn);
						$rivit++;

						$prn  = sprintf ('%-35.35s', 	$edkassanimi." ".t("yhteensä").":");
						$prn .= "............................................";
						$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
						$prn .= "\n\n";
						fwrite($fh, $prn);
					}

					$kassayhteensa = 0;
				}
			}
			if ($katsuori != '') {
				//Haetaan kassatilille laitetut suoritukset
				$query = "	SELECT suoritus.nimi_maksaja nimi, tiliointi.summa, lasku.tapvm
							FROM lasku use index (yhtio_tila_tapvm)
							JOIN tiliointi use index (tositerivit_index) ON (tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.tilino='$yhtiorow[kassa]')
							JOIN suoritus use index (tositerivit_index) ON (suoritus.yhtio=tiliointi.yhtio and suoritus.ltunnus=tiliointi.aputunnus)
							LEFT JOIN kuka ON (lasku.laatija=kuka.kuka and lasku.yhtio=kuka.yhtio)
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila	= 'X'
							and lasku.tapvm >= '$vva-$kka-$ppa'
							and lasku.tapvm <= '$vvl-$kkl-$ppl'
							ORDER BY lasku.laskunro";
				$result = mysql_query($query) or pupe_error($query);

				$kassayhteensa = 0;

				if (mysql_num_rows($result) > 0) {
					echo "<br><table id='nayta$i' style='display:none'>";
					echo "<tr>
							<th nowrap>".t("Kassa")."</th>
							<th nowrap>".t("Asiakas")."</th>
							<th nowrap>".t("Ytunnus")."</th>
							<th nowrap>".t("Laskunumero")."</th>
							<th nowrap>".t("Pvm")."</th>
							<th nowrap>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_array($result)) {

						echo "<tr>";
						echo "<td>".t("Käteissuoritus")."</td>";
						echo "<td>".substr($row["nimi"],0,23)."</td>";
						echo "<td>$row[ytunnus]</td>";
						echo "<td>$row[laskunro]</td>";
						echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
						echo "<td align='right'>".str_replace(".",",",$row['summa'])."</td></tr>";

						if ($vaiht == 1) {
							if ($rivit >= 60) {
								fwrite($fh, $ots);
								$rivit = 1;
							}
							$prn  = sprintf ('%-20.20s', 	t("Käteissuoritus"));
							$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
							$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
							$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
							$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
							$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
							$prn .= "\n";

							fwrite($fh, $prn);
							$rivit++;
						}
						$yhteensa += $row["summa"];
						$kassayhteensa += $row["summa"];
					}

					if ($kassayhteensa != 0) {
					}
				}
			}

			if ($tasmays != '') {
				echo "<tr><td colspan='8'>&nbsp;</td></tr>";
			}

			echo "</table>";
			echo "<table width='100%'>";
			echo "<input type='hidden' id='myynti_yhteensa_hidden' name='myynti_yhteensa' value='$yhteensa'>";
			echo "<tr><td align='left' colspan='3'><font class='head'>";

			if ($tasmays != '') {
				echo t("Myynti yhteensä");
			}
			else {
				echo t("Kaikki kassat yhteensä");
			}

			echo ":</font></td><td align='right'><input type='text' size='10'";

			echo "id='myynti_yhteensa' value='".sprintf('%.2f',$yhteensa);

			echo "' disabled></td></tr>";

			if ($tasmays != '') {
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Kassalippaassa käteistä").":</td><td align='right'>";
				echo "<input type='text' id='kaikkiyhteensa' size='10' value='' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Käteistilitys pankkiin").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='kateistilitys' id='kateistilitys' size='10' onkeyup='update_summa(\"tasmaytysform\");'></td></tr>";
				echo "<tr><th colspan='3'>".t("Loppukassa").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='loppukassa' id='loppukassa' size='10' disabled>";
				echo "</td></tr>";
			}

			if ($vaiht == 1) {
				$prn  = sprintf ('%-35.35s', 	t("Yhteensä").":");
				$prn .= "............................................";
				$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$yhteensa)));
				$prn .= "\n";
				fwrite($fh, $prn);

				echo "<pre>",file_get_contents($filenimi),"</pre>";
				fclose($fh);

				//haetaan tilausken tulostuskomento
				$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
				$kirres  = mysql_query($query) or pupe_error($query);
				$kirrow  = mysql_fetch_array($kirres);
				$komento = $kirrow['komento'];

				$line = exec("a2ps -o $filenimi.ps -R --medium=A4 --chars-per-line=94 --no-header --columns=1 --margin=0 --borders=0 $filenimi");

				// itse print komento...
				$line = exec("$komento $filenimi.ps");

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $filenimi");
				system("rm -f $filenimi.ps");
			}

			if ($tasmays != '') {
				echo "<tr><td align='right' colspan='4'><input type='submit' value='".t("Hyväksy")."'></td></tr>";
				echo "<input type='hidden' name='loppukassa2' id='loppukassa2' value=''>";
				echo "<input type='hidden' name='pp' id='pp' value='$pp'>";
				echo "<input type='hidden' name='kk' id='kk' value='$kk'>";
				echo "<input type='hidden' name='vv' id='vv' value='$vv'>";
				echo "</form>";
			}
			echo "</table>";
		}
		echo "</table>";
	}

	echo "	<script type='text/javascript' language='JavaScript'>
			<!--
				function update_summa(ID) {
					obj = document.getElementById(ID);
					var summa = 0;
					var solusumma = 0;
					var solut = 0;
					var erotus = 0;
					var pointer = 1;
					var kala = '';
					var kassa = 0;

			 		for (i=0; i<obj.length; i++) {
						//kala = kala+'\\n '+i+'. NIMI: '+obj.elements[i].id+' VALUE: '+obj.elements[i].value;

						if (obj.elements[i].id.substring(0,8) == ('kateinen') && !isNaN(obj.elements[i].id.substring(13,14))) {
							if (pointer != obj.elements[i].id.substring(13,14)) {
								solut = 0;
							}

							if (obj.elements[i].value != '') {
								pointer = obj.elements[i].id.substring(13,14);

								if (document.getElementById('kateinen erotus'+pointer).innerHTML !== null || document.getElementById('kateinen erotus'+pointer).innerHTML != '') {
									erotus = Number(document.getElementById('kateinen erotus'+pointer).innerHTML.replace(\",\",\".\"));
									document.getElementById('erotus'+pointer).value = Number(document.getElementById('kateinen erotus'+pointer).innerHTML.replace(\",\",\".\"));
								}
								else {
									erotus = 0;
								}

								solut += Number(obj.elements[i].value.replace(\",\",\".\"));
								kassa += Number(obj.elements[i].value.replace(\",\",\".\"));

								solusumma = solut.toFixed(2) - erotus.toFixed(2);

								kassa = Number(kassa.toFixed(2));
								document.getElementById('kaikkiyhteensa').value = kassa.toFixed(2);
								document.getElementById('kateinen soluerotus'+pointer).value = solusumma.toFixed(2);

								if (solusumma.toFixed(2) == 0.00) {
									document.getElementById('kateinen soluerotus'+pointer).style.color = 'darkgreen';
								}
								else {
									document.getElementById('kateinen soluerotus'+pointer).style.color = '#FF5555';
								}

								document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
							}
						}
						else if (obj.elements[i].id.substring(0,12) == ('pankkikortti') && !isNaN(obj.elements[i].id.substring(17,18))) {
							if (pointer != obj.elements[i].id.substring(17,18)) {
								solut = 0;
							}

							if (obj.elements[i].value != '') {
								pointer = obj.elements[i].id.substring(17,18);

								if (document.getElementById('pankkikortti erotus'+pointer).innerHTML != '') {
									erotus = Number(document.getElementById('pankkikortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
									document.getElementById('erotus'+pointer).value = Number(document.getElementById('pankkikortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
								}
								else {
									erotus = 0;
								}

								solut += Number(obj.elements[i].value.replace(\",\",\".\"));
								solusumma = solut - erotus;
								document.getElementById('pankkikortti soluerotus'+pointer).value = solusumma.toFixed(2);

								if (solusumma.toFixed(2) == 0.00) {
									document.getElementById('pankkikortti soluerotus'+pointer).style.color = 'darkgreen';
								}
								else {
									document.getElementById('pankkikortti soluerotus'+pointer).style.color = '#FF5555';
								}

								document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
							}
						}
						else if (obj.elements[i].id.substring(0,12) == ('luottokortti') && !isNaN(obj.elements[i].id.substring(17,18))) {
							if (pointer != obj.elements[i].id.substring(17,18)) {
								solut = 0;
							}

							if (obj.elements[i].value != '') {
								pointer = obj.elements[i].id.substring(17,18);

								if (document.getElementById('luottokortti erotus'+pointer).innerHTML != '') {
									erotus = Number(document.getElementById('luottokortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
									document.getElementById('erotus'+pointer).value = Number(document.getElementById('luottokortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
								}
								else {
									erotus = 0;
								}

								solut += Number(obj.elements[i].value.replace(\",\",\".\"));
								solusumma = solut - erotus;
								document.getElementById('luottokortti soluerotus'+pointer).value = solusumma.toFixed(2);

								if (solusumma.toFixed(2) == 0.00) {
									document.getElementById('luottokortti soluerotus'+pointer).style.color = 'darkgreen';
								}
								else {
									document.getElementById('luottokortti soluerotus'+pointer).style.color = '#FF5555';
								}

								document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
							}
						}

						if (obj.elements[i].value != '' && obj.elements[i].id == 'pohjakassa') {
							summa += Number(obj.elements[i].value.replace(\",\",\".\"));
						}

						if (obj.elements[i].value != '' && obj.elements[i].id == 'kateistilitys') {
							summa -= Number(obj.elements[i].value.replace(\",\",\".\"));
						}

						if (obj.elements[i].value != '' && obj.elements[i].id == 'kaikkiyhteensa') {
							temp_value = Number(obj.elements[i].value.replace(\",\",\".\")) + Number(document.getElementById('pohjakassa').value.replace(\",\",\".\"));
							obj.elements[i].value = temp_value.toFixed(2);
							summa = temp_value.toFixed(2);
						}

						summa = Math.round(summa*100)/100;
						document.getElementById('loppukassa').value = summa.toFixed(2);
						document.getElementById('loppukassa2').value = summa.toFixed(2);
					}
					//alert(kala);
				}

				function toggleGroup(id) {
					if (document.getElementById(id).style.display != 'none') {
						document.getElementById(id).style.display = 'none';
					}
					else {
						document.getElementById(id).style.display = 'block';
					}
				}

				function verify() {
					msg = '".t("Oletko varma?")."';
					return confirm(msg);
				}
			-->
			</script>";

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kka))
		$kka = date("m");
	if (!isset($vva))
		$vva = date("Y");
	if (!isset($ppa))
		$ppa = date("d");


	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<tr><th>".t("Syötä myyjänumero")."</th><td colspan='3'><input type='text' size='10' name='myyjanro' value='$myyjanro'>";

	$query = "	SELECT tunnus, kuka, nimi, myyja
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY nimi";
	$yresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("TAI valitse käyttäjä")."</th><td colspan='3'><select name='myyja'>";
	echo "<option value='' >".t("Kaikki")."</option>";

	while($row = mysql_fetch_array($yresult)) {
		$sel = "";

		if ($row['kuka'] == $myyja) {
			$sel = 'selected';
		}

		echo "<option value='$row[kuka]' $sel>($row[kuka]) $row[nimi]</option>";
	}
	echo "</select></td></tr>";

	echo "<tr><td class='back'><br></td></tr>";

	if (!$tasmays) {
		$dis = "disabled";
		$dis2 = "";
	}
	else {
		$dis = "";
		$dis2 = "disabled";
	}

	if ($oikeurow['paivitys'] == 1) {

		if ($tasmays != '') {
			$sel = 'CHECKED';
		}
		if ($tilityskpl == '') {
			$tilityskpl = 3;
		}

		echo "<tr><th>".t("Täsmää käteismyynnit")."</th><td colspan='3'><input type='checkbox' id='tasmays' name='tasmays' $sel onClick='disableDates();' disabled><br></td></tr>";
		echo "<tr><th>".t("Tilitettävien sarakkeiden määrä")."</th><td colspan='3'><input type='text' id='tilityskpl' name='tilityskpl' size='3' maxlength='1' value='$tilityskpl'><br></td></tr>";
		echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='pp' id='pp' value='$pp' size='3' $dis></td>
				<td><input type='text' name='kk' id='kk' value='$kk' size='3' $dis></td>
				<td><input type='text' name='vv' id='vv' value='$vv' size='5' $dis></td></tr>";
		echo "<tr><td class='back'><br></td></tr>";
	}

	$query  = "	SELECT *
				FROM kassalipas
				WHERE yhtio='$kukarow[yhtio]'
				order by tunnus";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares)) {
		$sel='';

		if ($kassakone[$varow["tunnus"]] != '') $sel = 'CHECKED';
		echo "<tr><th>".t("Näytä")."</th><td colspan='3'><input type='checkbox' name='kassakone[$varow[tunnus]]' value='$varow[tunnus]' $sel> $varow[nimi]</td></tr>";
	}

	$sel='';
	if ($muutkassat != '') $sel = 'CHECKED';
	echo "<tr><th>".t("Näytä")."</th><td colspan='3'><input type='checkbox' name='muutkassat' value='MUUT' $sel>".t("Muut kassat")."</td></tr>";

	$sel='';
	if ($katsuori != '') $sel = 'CHECKED';
	echo "<tr><th>".t("Näytä")."</th><td colspan='3'><input type='checkbox' name='katsuori' value='MUUT' $sel>".t("Käteissuoritukset")."</td></tr>";

	echo "<tr><td class='back'><br></td></tr>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' id='ppa' value='$ppa' size='3' $dis2></td>
			<td><input type='text' name='kka' id='kka' value='$kka' size='3' $dis2></td>
			<td><input type='text' name='vva' id='vva' value='$vva' size='5' $dis2></td></tr>";

	echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' id='ppl' value='$ppl' size='3' $dis2></td>
			<td><input type='text' name='kkl' id='kkl' value='$kkl' size='3' $dis2></td>
			<td><input type='text' name='vvl' id='vvl' value='$vvl' size='5' $dis2></td></tr>";

	$chk1 = '';
	$chk2 = '';

	if ($koti == 'KOTI')
		$chk1 = "CHECKED";

	if ($ulko == 'ULKO')
		$chk2 = "CHECKED";

	if ($chk1 == '' and $chk2 == '') {
		$chk1 = 'CHECKED';
	}


	echo "<tr><th>".t("Kotimaan myynti")."</th>
			<td colspan='3'><input type='checkbox' name='koti' value='KOTI' $chk1></td></tr>";

	echo "<tr><th>".t("Vienti")."</th>
			<td colspan='3'><input type='checkbox' name='ulko' value='ULKO' $chk2></td></tr>";

	$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
	$kires = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Valitse tulostuspaikka").":</th>";

	echo "<td colspan='3'><select name='printteri'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	while ($kirow=mysql_fetch_array($kires)) {
		$select = '';

		if ($kirow["tunnus"] == $printteri)
			$select = "SELECTED";

		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";
	echo "</td></table></form>";


	require ("../inc/footer.inc");
?>
