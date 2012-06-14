<?php

	if (!isset($eiliittymaa) or $eiliittymaa != 'ON') {
		if (isset($_POST["supertee"])) {
			if($_POST["supertee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
			if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
		}

		//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
		$useslave = 1;

		$pupe_DataTables = "saatanat_taulu";

		require ("../inc/parametrit.inc");

		if (isset($supertee)) {
			if ($supertee == "lataa_tiedosto") {
				readfile("/tmp/".$tmpfilenimi);
				exit;
			}
		}
	}

	if (!isset($eiliittymaa)) 		$eiliittymaa = "";
	if (!isset($ylilimiitin)) 		$ylilimiitin = "";
	if (!isset($sytunnus)) 			$sytunnus = "";
	if (!isset($sanimi)) 			$sanimi = "";
	if (!isset($grouppaus)) 		$grouppaus = "";
	if (!isset($savalkoodi)) 		$savalkoodi = "";
	if (!isset($yli)) 				$yli = "";
	if (!isset($valuutassako)) 		$valuutassako = "";
	if (!isset($laji)) 				$laji = "";
	if (!isset($luottovakuutettu))	$luottovakuutettu = "";
	if (!isset($tee)) 				$tee = "";
	if (!isset($pupe_DataTables)) 	$pupe_DataTables = "";
	if (!isset($luottolisa)) 		$luottolisa = "";

	if ($eiliittymaa != 'ON') {

		// Livesearch jutut
		enable_ajax();

		echo "<font class='head'>".t("Saatavat")." - $yhtiorow[nimi]</font><hr>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='NAYTA'>";

		if (!isset($sakkl)) $sakkl = date("m");
		if (!isset($savvl)) $savvl = date("Y");
		if (!isset($sappl)) $sappl = date("d");

		$yli = str_replace(',','.', $yli);
		$yli = (float) $yli;

		echo "<table>";
		echo "<tr><th>".t("N�yt� vain t�m� ytunnus").":</th><td valign='top'><input type='text' name='sytunnus' size ='15' value='$sytunnus'></td><td valign='top' class='back'>".t("J�t� kaikki hakukent�t tyhj�ksi jos haluat listata kaikki saatavat").".</td></tr>";
		echo "<tr><th>".t("N�yt� vain t�m� nimi").":</th><td valign='top'><input type='text' name='sanimi' size ='15' value='$sanimi'></td></tr>";
		echo "<tr><th>".t("N�yt� vain ne joilla saatavaa on yli").":</th><td valign='top'><input type='text' name='yli' size ='15' value='$yli'></td></tr>";
		echo "<tr><th>".t("Anna p�iv�m��r�, muodossa pp-kk-vvvv:")."</th><td><input type = 'text' name = 'sappl' value='$sappl' size=2><input type = 'text' name = 'sakkl' value='$sakkl' size=2><input type = 'text' name = 'savvl' value='$savvl' size=4></td></tr>";

		$query = "	SELECT tunnus
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'K'
					and kaytossa != 'E'
					LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			echo "<tr><th>".t("Kustannuspaikka")."</th><td>";

			$monivalintalaatikot = array("KUSTP");
			$noautosubmit = TRUE;
			$piirra_otsikot = FALSE;

			require ("tilauskasittely/monivalintalaatikot.inc");

			echo "</td></tr>";
		}

		$sel = array();
		$sel[$grouppaus] = "SELECTED";

		echo "<tr><th>".t("Summaustaso").":</th><td valign='top'><select name='grouppaus'>";
		echo "<option value = 'asiakas' $sel[asiakas]>".t("Asiakas")."</option>";
		echo "<option value = 'ytunnus' $sel[ytunnus]>".t("Ytunnus")."</option>";
		echo "<option value = 'nimi'    $sel[nimi]>".t("Nimi")."</option>";
		echo "<option value = 'kustannuspaikka'    $sel[kustannuspaikka]>".t("Kustannuspaikka")."</option>";
		echo "</select></td><td class='back'>".t("Kaatotilin saldo voidaan n�ytt�� vain jos summaustaso on Asiakas tai Ytunnus").".</td></tr>";

		$query = "	SELECT nimi, tunnus
	                FROM valuu
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY jarjestys";
		$vresult = pupe_query($query);

		echo "<tr><th>".t("Valitse valuutta").":</th><td><select name='savalkoodi'>";
		echo "<option value = ''>".t("Kaikki")."</option>";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel="";
			if (strtoupper($vrow['nimi']) == strtoupper($savalkoodi)) {
				$sel = "selected";
			}

			echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
		}

		echo "</select></td></tr>";

		$sel1 = '';

		if ($valuutassako == 'V') {
			$sel1 = "SELECTED";
		}

		echo "<tr><th>".t("Summat").":</th>";
		echo "<td><select name='valuutassako'>";
		echo "<option value = ''>".t("Yrityksen valuutassa")."</option>";
		echo "<option value = 'V' $sel1>".t("Laskun valuutassa")."</option>";
		echo "</select></td></tr>";

		$sel[$laji] = " selected ";

		echo "<th>".t("Mitk� Laskut Listataan").":</th>";
		echo "<td><select name='laji'>
				<option value='M'   $sel[M]>".t("myyntisaamiset")."</option>
				<option value='MF'  $sel[MF]>".t("factoringmyyntisaamiset")."</option>
				<option value='MK'  $sel[MK]>".t("konsernimyyntisaamiset")."</option>
				<option value='MMK' $sel[MMK]>".t("myyntisaamiset + konsernimyyntisaamiset")."</option>
				<option value='MA'  $sel[MA]>".t("myyntisaamiset + factoringmyyntisaamiset + konsernimyyntisaamiset")."</option>
				</select></td>";
		echo "</tr>";

		$chk = '';

		if ($ylilimiitin != '') {
			$chk = "CHECKED";
		}

		echo "<tr><th>".t("N�yt� vain ne joilla luottoraja on ylitetty").":</th><td valign='top'><input type='checkbox' name='ylilimiitin' value='ON' $chk></td>";

		echo "<tr>";

		$checked = "";

		if ($luottovakuutettu == "K") {
			$luottolisa = " JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.luottovakuutettu = 'K') ";
			$checked = "CHECKED";
		}

		echo "<th>".t("N�yt� vain luottovakuutetut asiakkaat").":</th>";
		echo "<td><input type='checkbox' name='luottovakuutettu' value='K' $checked></td>";
		echo "</tr>";
		echo "</table><br>";
		echo "<input type='submit' value='".t("Aja raportti")."'>";
		echo "</form><br><br>";
	}

	if ($tee == 'NAYTA' or $eiliittymaa == 'ON') {
		if (!isset($sakkl)) $sakkl = date("m");
		if (!isset($savvl)) $savvl = date("Y");
		if (!isset($sappl)) $sappl = date("d");

		if ($laji == 'MK') 		$tili = "'$yhtiorow[konsernimyyntisaamiset]'";
		elseif ($laji == 'MF') 	$tili = "'$yhtiorow[factoringsaamiset]'";
		elseif ($laji == 'MA') 	$tili = "'$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]'";
		elseif ($laji == 'MMK') $tili = "'$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]'";
		else 					$tili = "'$yhtiorow[myyntisaamiset]'";

		$generoitumuuttuja	= '';
		$kustpmuuttuja		= "";
		$saatavat_yhtio		= $kukarow['yhtio'];
		$eta_asiakaslisa	= '';

		if ($sanimi != '') {
			$generoitumuuttuja .= " and lasku.nimi like '%$sanimi%' ";
		}

		if ($sytunnus != '') {

			// KAUTTALASKUTUSKIKKARE
			if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $kukarow['yhtio'] == $GLOBALS['koti_yhtio'] and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS')) {

				$query = "	SELECT osasto, ifnull(group_concat(tunnus), 0) tunnukset
							FROM asiakas
							WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
							AND laji != 'P'
							AND ytunnus = '{$laskurow['ytunnus']}'
							AND toim_ovttunnus = '{$laskurow['toim_ovttunnus']}'
							GROUP BY 1";
				$result = pupe_query($query);
				$row = mysql_fetch_assoc($result);

				if ($row['osasto'] != '6') {
					$GLOBALS['eta_yhtio'] = "";
				}
				else {
					$saatavat_yhtio  = $GLOBALS['eta_yhtio'];
					$eta_asiakaslisa = " AND asiakas.tunnus in ({$row['tunnukset']}) ";
				}
			}
			else {
				$GLOBALS['eta_yhtio'] = "";
			}

			if (!isset($GLOBALS['eta_yhtio']) or trim($GLOBALS['eta_yhtio']) == '' or $GLOBALS['eta_yhtio'] == $kukarow['yhtio']) {
				$query = "	SELECT ifnull(group_concat(tunnus), 0) tunnukset
							FROM asiakas
							WHERE yhtio = '$saatavat_yhtio'
							AND ytunnus = '$sytunnus'
							AND laji != 'P'";
				$result = pupe_query($query);
				$row = mysql_fetch_assoc($result);
			}

			$generoitumuuttuja .= " and lasku.liitostunnus in ($row[tunnukset]) ";
		}

		if ($yli != 0) {
			$having = " HAVING avoimia >= '$yli' ";
		}
		else {
			$having = " HAVING avoimia != 0 ";
		}

		if ($grouppaus == 'ytunnus') {
			$selecti = "lasku.ytunnus, group_concat(distinct lasku.nimi separator '<br>') nimi, group_concat(distinct lasku.liitostunnus) liitostunnus, group_concat(distinct lasku.toim_nimi separator '<br>') toim_nimi";
			$grouppauslisa = "lasku.ytunnus";
		}
		elseif ($grouppaus == 'nimi') {
			$selecti = "group_concat(distinct lasku.ytunnus separator '<br>') ytunnus, lasku.nimi, group_concat(distinct lasku.liitostunnus) liitostunnus, group_concat(distinct lasku.toim_nimi separator '<br>') toim_nimi";
			$grouppauslisa = "lasku.nimi";
		}
		elseif ($grouppaus == 'kustannuspaikka') {
			$selecti = "tiliointi.kustp kustannuspaikka";
			$grouppauslisa = "tiliointi.kustp";
		}
		else {
			// grouppaus = asiakas
			$selecti = "group_concat(distinct lasku.ytunnus separator '<br>') ytunnus, group_concat(distinct lasku.nimi separator '<br>') nimi, lasku.liitostunnus, group_concat(distinct lasku.toim_nimi separator '<br>') toim_nimi";
			$grouppauslisa = "lasku.liitostunnus";
		}

		$salisa1 = "";
		$salisa2 = "";

		if ($savalkoodi != "") {
			$salisa1 = " and lasku.valkoodi='$savalkoodi' ";
			$salisa2 = " and suoritus.valkoodi='$savalkoodi' ";
		}

		if ($eiliittymaa != 'ON' and isset($lisa) and strpos($lisa, "tiliointi.kustp") !== FALSE) {
			$tiliointilisa = $lisa;
		}
		else {
			$tiliointilisa = '';
		}

		if ($grouppaus != "kustannuspaikka" and $tiliointilisa != "") {
			$selecti .= ",tiliointi.kustp kustannuspaikka";
			$grouppauslisa .= ",tiliointi.kustp";
		}

		// Rapparin sarakkeet voidaan m��ritell� my�s salasanat.php:ss�
		if (!isset($saatavat_array)) {
			$saatavat_array = array(0,15,30,60,90,120);
		}

		if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
			$summalisa  = " round(sum(tiliointi.summa_valuutassa),2) avoimia,\n";
			$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) > 15, tiliointi.summa_valuutassa, 0)) 'ylivito',\n";
			$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) <= $saatavat_array[0], tiliointi.summa_valuutassa, 0)) 'alle_$saatavat_array[0]',\n";

			for ($sa = 1; $sa < count($saatavat_array); $sa++) {
				$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) > {$saatavat_array[$sa-1]} and TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) <= {$saatavat_array[$sa]}, tiliointi.summa_valuutassa, 0)) '".($saatavat_array[$sa-1]+1)."_$saatavat_array[$sa]',\n";
			}

			$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) > {$saatavat_array[count($saatavat_array)-1]}, tiliointi.summa_valuutassa, 0)) 'yli_{$saatavat_array[count($saatavat_array)-1]}',\n";
		}
		else {
			$summalisa  = " round(sum(tiliointi.summa),2) avoimia,\n";
			$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) > 15, tiliointi.summa, 0)) 'ylivito',\n";
			$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) <= $saatavat_array[0], tiliointi.summa, 0)) 'alle_$saatavat_array[0]',\n";

			for ($sa = 1; $sa < count($saatavat_array); $sa++) {
				$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) > {$saatavat_array[$sa-1]} and TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) <= {$saatavat_array[$sa]}, tiliointi.summa, 0)) '".($saatavat_array[$sa-1]+1)."_$saatavat_array[$sa]',\n";
			}

			$summalisa .= " sum(if(TO_DAYS('$savvl-$sakkl-$sappl')-TO_DAYS(lasku.erpcm) > {$saatavat_array[count($saatavat_array)-1]}, tiliointi.summa, 0)) 'yli_{$saatavat_array[count($saatavat_array)-1]}',\n";
		}

		$query = "	SELECT
					{$selecti},
					{$summalisa}
					min(lasku.liitostunnus) litu,
					min(lasku.tunnus) latunnari
					FROM lasku use index (yhtio_tila_mapvm)
					JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tilino in ($tili) and tiliointi.korjattu = '' and tiliointi.tapvm <= '$savvl-$sakkl-$sappl' {$tiliointilisa})
					{$luottolisa}
					WHERE lasku.yhtio = '{$saatavat_yhtio}'
					and (lasku.mapvm > '{$savvl}-{$sakkl}-{$sappl}' or lasku.mapvm = '0000-00-00')
					and lasku.tapvm	<= '{$savvl}-{$sakkl}-{$sappl}'
					and lasku.tapvm > '0000-00-00'
					and lasku.tila = 'U'
					and lasku.alatila = 'X'
					{$generoitumuuttuja}
					{$salisa1}
					GROUP BY {$grouppauslisa}
					{$having}
					ORDER BY 1,2,3";
		$result = pupe_query($query);

		$saatavat_yhteensa 			= array();
		$avoimia_yhteensa 			= 0;
		$kaato_yhteensa				= 0;
		$ylivito					= 0;
		$rivilask 					= 0;
		$avoimettilaukset_yhteensa 	= 0;

		if (mysql_num_rows($result) > 0) {

			if ($eiliittymaa != 'ON' and @include('Spreadsheet/Excel/Writer.php')) {

				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}

			if (isset($workbook)) {
				$excelsarake = 0;

				if ($grouppaus != "kustannuspaikka") {
					$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
					$excelsarake++;
				}

				if ($grouppaus == "kustannuspaikka" or $tiliointilisa != "") {
					$worksheet->write($excelrivi, $excelsarake, t("Kustannuspaikka"), $format_bold);
					$excelsarake++;
				}

				$worksheet->write($excelrivi, $excelsarake, t("Alle")." {$saatavat_array[0]} ".t("pv"), $format_bold);
				$excelsarake++;

				for ($sa = 1; $sa < count($saatavat_array); $sa++) {
					$worksheet->write($excelrivi, $excelsarake, ($saatavat_array[$sa-1]+1)."-".$saatavat_array[$sa]." ".t("pv"), $format_bold);
					$excelsarake++;
				}

				$worksheet->write($excelrivi, $excelsarake, t("Yli")." {$saatavat_array[count($saatavat_array)-1]} ".t("pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Avoimet")." ".t("laskut"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Avoimet")." ".t("tilaukset"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Kaatotili"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Yhteens�"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Luottoraja"), $format_bold);

				$excelsarake = 0;
				$excelrivi++;
			}

			echo "<font class='head'>".t("Saatavat")." - $yhtiorow[nimi] - $sappl.$sakkl.$savvl</font><hr>";

			if ($eiliittymaa != 'ON') {
				if ($grouppaus == "kustannuspaikka") {
					$sarakemaara = count($saatavat_array)+7;
				}
				else {
					$sarakemaara = count($saatavat_array)+8;

					if ($tiliointilisa != "") {
						$sarakemaara++;
					}
				}

				pupe_DataTables(array(array($pupe_DataTables, $sarakemaara, $sarakemaara)));
			}

			// Linkki asiakasrappariin
			if ($grouppaus == 'asiakas') {
				$asirappari_linkki_alatila = "T";
			}
			else {
				$asirappari_linkki_alatila = "Y";
			}

			echo "<table class='display dataTable' id='$pupe_DataTables'>";
			echo "<thead>";
			echo "<tr>";

			if ($grouppaus != "kustannuspaikka") {
				echo "<th>".t("Ytunnus")."</th>";
				echo "<th>".t("Nimi")."</th>";
			}

			if ($grouppaus == "kustannuspaikka" or $tiliointilisa != "") {
				echo "<th>".t("Kustannuspaikka")."</th>";
			}

			echo "<th align='right'>".t("Alle")." {$saatavat_array[0]} ".t("pv")."</th>";

			for ($sa = 1; $sa < count($saatavat_array); $sa++) {
				echo "<th align='right'>".($saatavat_array[$sa-1]+1)."-".$saatavat_array[$sa]."<br>".t("pv")."</th>";
			}

			echo "<th align='right'>".t("Yli")." ".$saatavat_array[count($saatavat_array)-1]."<br>".t("pv")."</th>";
			echo "<th align='right'>".t("Avoimet")."<br>".t("laskut")."</th>";
			echo "<th align='right'>".t("Avoimet")."<br>".t("tilaukset")."</th>";
			echo "<th align='right'>".t("Kaatotili")."</th>";
			echo "<th align='right'>".t("Yhteens�")."</th>";
			echo "<th align='right'>".t("Luottoraja")."</th>";
			echo "</tr>";
			echo "</thead>";

			echo "<tbody>";

			$divi = "";
			$query_alennuksia = generoi_alekentta('M');

			while ($row = mysql_fetch_assoc($result)) {

				if (isset($row["liitostunnus"]) and $row["liitostunnus"] != "") {

					$query = "	SELECT luottoraja
								FROM asiakas
								WHERE yhtio = '$saatavat_yhtio'
								and tunnus in ($row[liitostunnus])";
					$asresult = pupe_query($query);
					$asrow = mysql_fetch_assoc($asresult);

					if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
						// Suorituksen valuutassa
						$suorilisa = " sum(summa) summa ";
					}
					else {
						// Yhti�n valuutassa
						$suorilisa = " sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa ";
					}

					// Haetaan kaatotilin summa
					$query = "	SELECT
								$suorilisa
								FROM suoritus
								WHERE yhtio = '$saatavat_yhtio'
								and ltunnus > 0
								and kohdpvm = '0000-00-00'
								and asiakas_tunnus in ($row[liitostunnus])
								$salisa2";
					$kaatotilires = pupe_query($query);
					$kaatotilirow = mysql_fetch_assoc($kaatotilires);

					if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
						// Laskun valuutassa
						$avtilisa = "(tilausrivi.hinta/if(tilausrivi.vienti_kurssi=0, 1, tilausrivi.vienti_kurssikurssi))";
					}
					else {
						// Yhti�n valuutassa
						$avtilisa = "tilausrivi.hinta";
					}

					// Avoimet tilaukset
					$query = "	SELECT
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_alennuksia}),2) tilausavoinsaldo
								FROM lasku
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi IN ('L','W'))
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								AND ((lasku.tila = 'L' and lasku.alatila in ('A','B','C','D','E','J','V'))	# Kaikki myyntitilaukset, paitsi laskutetut
								  OR (lasku.tila = 'N' and lasku.alatila in ('','A','F'))					# Myyntitilaus kesken, tulostusjonossa tai odottaa hyv�ksynt��
								  OR (lasku.tila = 'V' and lasku.alatila in ('','A','C','J','V'))			# Valmistukset
								)
								AND lasku.liitostunnus in ($row[liitostunnus])";
					$avoimettilauksetres = pupe_query($query);
					$avoimettilauksetrow = mysql_fetch_assoc($avoimettilauksetres);

					// Lasketaan luottotilanne nyt
					$luottotilanne_nyt = round($asrow['luottoraja'] - $row["avoimia"] + $kaatotilirow["summa"] - $avoimettilauksetrow["tilausavoinsaldo"], 2);
				}
				else {
					$asrow				 = array();
					$kaatotilirow 		 = array();
					$avoimettilauksetrow = array();
					$luottotilanne_nyt   = 0;
				}

				if ($ylilimiitin == '' or ($ylilimiitin == 'ON' and $asrow["luottoraja"] > 0 and $luottotilanne_nyt < 0)) {

					if (isset($row["toim_nimi"]) and $row["nimi"] != $row["toim_nimi"]) $row["nimi"] .= "<br>$row[toim_nimi]";

					if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $kukarow['yhtio'] == $GLOBALS['koti_yhtio']) {
						if (isset($laskurow['liitostunnus']) and trim($laskurow['liitostunnus']) != '') {
							$row['litu'] = $laskurow['liitostunnus'];
						}
					}

					if (isset($asrow["luottoraja"]) and $asrow["luottoraja"] > 0 and $luottotilanne_nyt < 0) {
						$luottorajavirhe = 'kyll�';
					}
					else {
						$luottorajavirhe = '';
					}

					// Ei n�ytet� nollia ruudulla
					if (isset($asrow["luottoraja"]) and $asrow['luottoraja'] == 0) $asrow['luottoraja'] = '';
					if ($row["alle_{$saatavat_array[0]}"] == 0) $row["alle_{$saatavat_array[0]}"] = "";

					for ($sa = 1; $sa < count($saatavat_array); $sa++) {
						if ($row[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]] == 0) $row[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]] = "";
					}

					if ($row["yli_".$saatavat_array[count($saatavat_array)-1]] == 0) $row["yli_".$saatavat_array[count($saatavat_array)-1]] = "";

					echo "<tr class='aktiivi'>";

					if ($grouppaus != "kustannuspaikka") {
						echo "<td valign='top'>";
						echo "<a name='$row[latunnari]' href='{$palvelin2}myyntires/myyntilaskut_asiakasraportti.php?ytunnus=$row[ytunnus]&asiakasid=$row[litu]&alatila=$asirappari_linkki_alatila&tila=tee_raportti&lopetus=$PHP_SELF////tee=$tee//sytunnus=$sytunnus//sanimi=$sanimi//yli=$yli//sappl=$sappl//sakkl=$sakkl//savvl=$savvl//grouppaus=$grouppaus//savalkoodi=$savalkoodi//valuutassako=$valuutassako///$row[latunnari]'>$row[ytunnus]</a>";
						echo "</td>";

						if (substr_count($row['nimi'], '<br>') > 5) {

							$divi .= "<div id='div_".str_replace(",", "", $row['liitostunnus'])."' class='popup' style='width:250px;'>";
							$divi .= "<table style='width:250px;'>";
							$divi .= "<tr><th nowrap>".t("Nimi")."</th></tr>";

							$iii = 0;

							echo "<td valign='top'>";

							foreach (explode('<br>', $row['nimi']) as $_nimi) {

								if ($iii > 4) {
									$divi .= "<tr><td>$_nimi</td></tr>";
								}
								else {
									echo "{$_nimi}<br>";
								}

								$iii++;
							}

							$divi .= "</table></div>";

							echo "<br><a class='tooltip' id='".str_replace(",", "", $row['liitostunnus'])."'>",t("Useita"),"...</a></td>";
						}
						else {
							echo "<td valign='top'>$row[nimi]</td>";
						}
					}

					if ($grouppaus == "kustannuspaikka" or $tiliointilisa != "") {
						$query = "	SELECT nimi, koodi
									FROM kustannuspaikka
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '{$row['kustannuspaikka']}'";
						$nimiresult = pupe_query($query);

						if (mysql_num_rows($nimiresult) == 1) {
							$nimirow = mysql_fetch_assoc($nimiresult);

							$kustpmuuttuja = (strpos($nimirow["nimi"], $nimirow["koodi"]) === FALSE) ? $nimirow["nimi"]." ".$nimirow["koodi"] : $nimirow["nimi"];
						}
						else {
							$kustpmuuttuja = t("Ei Kustannuspaikkaa");
						}

						echo "<td valign='top'>{$kustpmuuttuja}</td>";
					}

					echo "<td valign='top' align='right' nowrap>".$row["alle_$saatavat_array[0]"]."</td>";

					for ($sa = 1; $sa < count($saatavat_array); $sa++) {
						echo "<td valign='top' align='right' nowrap>".$row[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]]."</td>";
					}

					echo "<td valign='top' align='right'>".$row["yli_{$saatavat_array[count($saatavat_array)-1]}"]."</td>";
					echo "<td valign='top' align='right'>$row[avoimia]</td>";
					echo "<td valign='top' align='right'>$avoimettilauksetrow[tilausavoinsaldo]</td>";
					echo "<td valign='top' align='right'>$kaatotilirow[summa]</td>";
					echo "<td valign='top' align='right'>".($row["avoimia"]+$avoimettilauksetrow["tilausavoinsaldo"]-$kaatotilirow["summa"])."</td>";
					echo "<td valign='top' align='right'>$asrow[luottoraja]</td>";
					echo "</tr>";

					if (isset($workbook)) {
						$excelsarake = 0;

						if ($grouppaus != "kustannuspaikka") {
							$worksheet->writeString($excelrivi, $excelsarake, str_replace("<br>","\n", $row["ytunnus"]));
							$excelsarake++;
							$worksheet->writeString($excelrivi, $excelsarake, str_replace("<br>","\n", $row["nimi"]));
							$excelsarake++;
						}

						if ($grouppaus == "kustannuspaikka" or $tiliointilisa != "") {
							$worksheet->writeString($excelrivi, $excelsarake, $kustpmuuttuja);
							$excelsarake++;
						}

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["alle_$saatavat_array[0]"]);
						$excelsarake++;

						for ($sa = 1; $sa < count($saatavat_array); $sa++) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]]);
							$excelsarake++;
						}

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["yli_{$saatavat_array[count($saatavat_array)-1]}"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["avoimia"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $avoimettilauksetrow["tilausavoinsaldo"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $kaatotilirow["summa"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, ($row["avoimia"]+$avoimettilauksetrow["tilausavoinsaldo"]-$kaatotilirow["summa"]));
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $asrow["luottoraja"]);

						$excelsarake = 0;
						$excelrivi++;
					}

					// Lasketaan yhteen
					if (!isset($saatavat_yhteensa["alle_$saatavat_array[0]"])) $saatavat_yhteensa["alle_$saatavat_array[0]"] = $row["alle_$saatavat_array[0]"];
					else $saatavat_yhteensa["alle_$saatavat_array[0]"] += $row["alle_$saatavat_array[0]"];

					for ($sa = 1; $sa < count($saatavat_array); $sa++) {
						if (!isset($saatavat_yhteensa[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]])) $saatavat_yhteensa[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]] = $row[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]];
						else $saatavat_yhteensa[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]] += $row[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]];
					}

					if (!isset($saatavat_yhteensa["yli_{$saatavat_array[count($saatavat_array)-1]}"])) $saatavat_yhteensa["yli_{$saatavat_array[count($saatavat_array)-1]}"] = $row["yli_{$saatavat_array[count($saatavat_array)-1]}"];
					else $saatavat_yhteensa["yli_{$saatavat_array[count($saatavat_array)-1]}"] += $row["yli_{$saatavat_array[count($saatavat_array)-1]}"];



					$kaato_yhteensa 			+= $kaatotilirow["summa"];
					$avoimia_yhteensa 			+= $row["avoimia"];
					$ylivito					+= $row["ylivito"];
					$avoimettilaukset_yhteensa 	+= $avoimettilauksetrow["tilausavoinsaldo"];
					$rivilask++;
				}
			}

			echo "</tbody>";
			echo "<tfoot>";

			if ($eiliittymaa != 'ON' or $rivilask >= 1) {
				$colspan = 2;
				$sumlask = 2;

				echo "<tr>";

				if ($grouppaus == "kustannuspaikka") {
					$colspan = 1;
					$sumlask = 1;
				}

				if ($grouppaus != "kustannuspaikka" and $tiliointilisa != "") {
					$colspan++;
					$sumlask++;
				}

				echo "<td valign='top' class='tumma' align='right' colspan='$colspan'>".t("Yhteens�").":</td>";

				echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>".$saatavat_yhteensa["alle_$saatavat_array[0]"]."</td>";
				$sumlask++;

				for ($sa = 1; $sa < count($saatavat_array); $sa++) {
					echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>".$saatavat_yhteensa[($saatavat_array[$sa-1]+1)."_".$saatavat_array[$sa]]."</td>";
					$sumlask++;
				}

				echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>".$saatavat_yhteensa["yli_{$saatavat_array[count($saatavat_array)-1]}"]."</td>";
				$sumlask++;
				echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>$avoimia_yhteensa</td>";
				$sumlask++;
				echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>$avoimettilaukset_yhteensa</td>";
				$sumlask++;
				echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>$kaato_yhteensa</td>";
				$sumlask++;
				echo "<td valign='top' class='tumma' name='saatavat_yhteensa' id='saatavat_yhteensa_$sumlask' align='right' nowrap>".($avoimia_yhteensa+$avoimettilaukset_yhteensa-$kaato_yhteensa)."</td>";
				echo "<td valign='top' class='tumma'></td>";
				echo "</tr>";
			}

			echo "</tfoot>";
			echo "</table>";

			if (trim($divi) != "") {
				echo "{$divi}";
			}

			if ($sytunnus != '') {

				$liitoslisa = "";

				if ($sliitostunnus !="") {
					$liitoslisa = "AND asiakas.tunnus='{$sliitostunnus}' ";
				}

				$query = "	SELECT jv
							FROM asiakas
							JOIN maksuehto ON (maksuehto.yhtio = asiakas.yhtio and maksuehto.tunnus = asiakas.maksuehto and maksuehto.kaytossa = '' and maksuehto.jv != '')
							WHERE asiakas.yhtio = '$saatavat_yhtio'
							AND asiakas.ytunnus = '$sytunnus'
							AND asiakas.laji != 'P'
							{$liitoslisa}
							$eta_asiakaslisa
							LIMIT 1";

				$maksuehto_chk_res = pupe_query($query);
				$maksuehto_chk_row = mysql_fetch_assoc($maksuehto_chk_res);

				if ($maksuehto_chk_row['jv'] != '') {
					$jvvirhe = 'kyll�';

					if ($eiliittymaa != 'ON') {
						echo "<br/>";
						echo "<font class='error'>",t("HUOM! T�m� on j�lkivaatimusasiakas"),"</font>";
						echo "<br/>";
					}
				}

				if ($eiliittymaa != 'ON' and $luottorajavirhe != '') {
					echo "<br/>";
					echo "<font class='error'>",t("HUOM! Luottoraja ylittynyt"),"</font>";
					echo "<br/>";
				}

				//katsotaan onko asiakkaalla maksamattomia trattoja, jos on niin ei anneta tehd� tilausta
				$query = " 	SELECT count(lasku.tunnus) kpl
							FROM karhu_lasku
							JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.yhtio = '$saatavat_yhtio' and lasku.mapvm = '0000-00-00' and lasku.ytunnus = '$sytunnus')
							JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = 'T')";
				$trattares = pupe_query($query);
				$tratat = mysql_fetch_assoc($trattares);

				if ($tratat['kpl'] > 0) {
					$trattavirhe = 'kyll�';

					if ($eiliittymaa != 'ON') {
						echo "<br/>";
						echo "<font class='error'>".t("HUOM! Asiakkaalla on maksamattomia trattoja")."</font>";
						echo "<br/>";
					}
				}

				if ($ylivito > 0 and $eiliittymaa != 'ON') {
					echo "<br/>";
					echo "<font class='error'>".t("HUOM! Asiakkaalla on yli 15 p�iv�� sitten er��ntyneit� laskuja, olkaa yst�v�llinen ja ottakaa yhteytt� myyntireskontran hoitajaan")."</font>";
					echo "<br/>";
				}
			}

			if (isset($workbook)) {

				// We need to explicitly close the workbook
				$workbook->close();

				echo "<br><br><form method='post' class='multisubmit'>";
				echo "<input type='hidden' name='supertee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Saatavat.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<br><table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<td valign='top' class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
				echo "</table>";
				echo "</form><br>";
			}

		}
		elseif ($eiliittymaa != 'ON') {
			echo "<br><br>".t("Ei saatavia!")."<br>";
		}
	}

	if ($eiliittymaa != 'ON') {
		require ("inc/footer.inc");
	}
?>