<?php

	echo "<font class='head'>".t("ABC-Analyysi‰: ABC-pitk‰listaus")."<hr></font>";
	
	//ryhm‰jako
	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
		$saapumispp = $saapumispp; 
		$saapumiskk = $saapumiskk;  
		$saapumisvv	= $saapumisvv;
	}
	elseif (trim($saapumispvm) != '') {
		list($saapumisvv, $saapumiskk, $saapumispp) = split('-', $saapumispvm);
	}

	$lisa_haku_osasto 		 = "";
	$lisa_haku_try 			 = "";
	$lisa_haku_tme 			 = "";
	$lisa_haku_malli 		 = "";
	$lisa_haku_myyja		 = "";
	$lisa_haku_ostaja		 = "";

	if (!isset($mul_osasto)) {
		$mul_osasto = array();
	}

	if (!isset($mul_try)) {
		$mul_try = array();
	}

	if (!isset($mul_tme)) {
		$mul_tme = array();
	}

	$ulisa = '';
	$ulisa_ilman_os = '';
	$ulisa_ilman_try = '';

	// jos on valittu jotakin dropdowneista (muu kuin osasto) niin tehd‰‰n niill‰ rajaukset muihin dropdowneihin
	if (count($mul_osasto) > 0) {
		$osastot = '';

		foreach ($mul_osasto as $osx) {
			if (trim($osx) != '') {
				$osx = trim(mysql_real_escape_string($osx));
				$osastot .= "'$osx',";
			}
		}

		$osastot = substr($osastot, 0, -1);
	
		if (trim($osastot) != '') {
			$lisa_haku_osasto = " and tuote.osasto in ($osastot) ";
			$lisa .= " and abc_aputaulu.osasto in ($osastot) ";
			$ulisa .= "&mul_osasto[]=".urlencode($osastot);
		}
	}

	if (count($mul_try) > 0) {
		$tryt = '';

		foreach ($mul_try as $tryx) {
			if (trim($tryx) != '') {
				$tryx = trim(mysql_real_escape_string($tryx));
				$tryt .= "'$tryx',";
			}
		}

		$tryt = substr($tryt, 0, -1);
	
		if (trim($tryt) != '') {
			$lisa_haku_try = " and tuote.try in ($tryt) ";
			$lisa .= " and abc_aputaulu.try in ($tryt) ";
			$ulisa .= "&mul_try[]=".urlencode($tryt);
			$ulisa_ilman_os .= "&mul_try[]=".urlencode($tryt);
		}
	}

	if (count($mul_tme) > 0) {
		$tmet = '';

		foreach ($mul_tme as $tmex) {
			if (trim($tmex) != '') {
				$tmex = trim(mysql_real_escape_string(urldecode($tmex)));
				$tmet .= "'$tmex',";
			}
		}

		$tmet = substr($tmet, 0, -1);
	
		if (trim($tmet) != '') {
			$lisa_haku_tme = " and tuote.tuotemerkki in ($tmet) ";
			$lisa .= " and abc_aputaulu.tuotemerkki in ($tmet) ";
			$ulisa .= "&mul_tme[]=".urlencode($tmet);
			$ulisa_ilman_os .= "&mul_tme[]=".urlencode($tmet);
			$ulisa_ilman_try .= "&mul_tme[]=".urlencode($tmet);	
		}
	}

	if (count($mul_malli) > 0) {
		$mallit = '';

		foreach ($mul_malli as $mallix) {
			if (trim($mallix) != '') {
				if (count($_GET['mul_malli']) > 0) {
					$mallix = rawurldecode($mallix);
				}
				$mallit .= "'".mysql_real_escape_string($mallix)."',";
				$ulisa .= "&mul_malli[]=".rawurlencode($mallix);
				$ulisa_ilman_os .= "&mul_malli[]=".rawurlencode($mallix);
				$ulisa_ilman_try .= "&mul_malli[]=".rawurlencode($mallix);
			}
		}

		$mallit = substr($mallit, 0, -1);
		
		if (trim($mallit) != '') {
			$lisa_haku_malli = " and tuote.malli in ($mallit) ";
			$lisa .= " and abc_aputaulu.malli in ($mallit) ";
		}
	}

	if (count($mul_tuotemyyja) > 0) {
		$tuotemyyjat = '';

		foreach ($mul_tuotemyyja as $tuotemyyjax) {
			if (trim($tuotemyyjax) != '') {
				if (count($_GET['mul_tuotemyyja']) > 0) {
					$tuotemyyjax = rawurldecode($tuotemyyjax);
				}
				$tuotemyyjat .= "'".mysql_real_escape_string($tuotemyyjax)."',";
				$ulisa .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
				$ulisa_ilman_os .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
				$ulisa_ilman_try .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
			}
		}

		$tuotemyyjat = substr($tuotemyyjat, 0, -1);
		
		if (trim($tuotemyyjat) != '') {
			$lisa_haku_myyja = " and kuka.myyja in ($tuotemyyjat) ";
			$lisa .= " and abc_aputaulu.myyjanro in ($tuotemyyjat) ";
		}
	}

	if (count($mul_tuoteostaja) > 0) {
		$tuoteostajat = '';

		foreach ($mul_tuoteostaja as $tuoteostajax) {
			if (trim($tuoteostajax) != '') {
				if (count($_GET['mul_tuoteostaja']) > 0) {
					$tuoteostajax = rawurldecode($tuoteostajax);
				}
				$tuoteostajat .= "'".mysql_real_escape_string($tuoteostajax)."',";
				$ulisa .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
				$ulisa_ilman_os .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
				$ulisa_ilman_try .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
			}
		}

		$tuoteostajat = substr($tuoteostajat, 0, -1);
		
		if (trim($tuoteostajat) != '') {
			$lisa_haku_ostaja = " and kuka.myyja in ($tuoteostajat) ";
			$lisa .= " and abc_aputaulu.ostajanro in ($tuoteostajat) ";
		}
	}

	$orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
	
	// piirrell‰‰n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='aja' value='AJA'>";
	echo "<input type='hidden' name='tee' value='PITKALISTA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	
	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Osasto"),"</th></tr>";
	echo "<tr>";

	// tehd‰‰n avainsana query
	$query = "	SELECT DISTINCT avainsana.selite,
	            IFNULL((SELECT avainsana_kieli.selitetark
	            FROM avainsana as avainsana_kieli
	            WHERE avainsana_kieli.yhtio = avainsana.yhtio
	            and avainsana_kieli.laji = avainsana.laji
	            and avainsana_kieli.selite = avainsana.selite
	            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
	            FROM avainsana
	            WHERE avainsana.yhtio = '$kukarow[yhtio]'
	            and avainsana.laji = 'OSASTO'
	            and avainsana.kieli in ('$yhtiorow[kieli]', '')
	            $orderlisa";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top' class='back'><select name='mul_osasto[]' multiple size='7' onchange='submit();'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_osasto) > 0) {
			if (in_array(trim($sxrow['selite']), $mul_osasto)) {
				$sel = 'SELECTED';
			}
		}
	
		echo "<option value='$sxrow[selite]' $sel>";
		if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
			echo $sxrow['selite']." ";
		}
		echo "$sxrow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	if ($lisa_haku_osasto == "") {
		$query = "	SELECT DISTINCT avainsana.selite,
		            IFNULL((SELECT avainsana_kieli.selitetark
		            FROM avainsana as avainsana_kieli
		            WHERE avainsana_kieli.yhtio = avainsana.yhtio
		            and avainsana_kieli.laji = avainsana.laji
		            and avainsana_kieli.selite = avainsana.selite
		            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
		            FROM avainsana
		            WHERE avainsana.yhtio = '$kukarow[yhtio]'
		            and avainsana.laji = 'TRY'
		            and avainsana.kieli in ('$yhtiorow[kieli]', '')
					$orderlisa";
	}
	else {
		$query = "	SELECT distinct avainsana.selite,
					IFNULL((SELECT avainsana_kieli.selitetark
			        FROM avainsana as avainsana_kieli
			        WHERE avainsana_kieli.yhtio = avainsana.yhtio
			        and avainsana_kieli.laji = avainsana.laji
			        and avainsana_kieli.selite = avainsana.selite
			        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
					FROM tuote
					JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', ''))
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa_haku_osasto
					$orderlisa";
	}

	$sresult = mysql_query($query) or pupe_error($query);

	echo "<table style='display:inline;'><tr><th>",t("Tuoteryhm‰"),"</th></tr>";
	echo "<tr><td nowrap valign='top' class='back'><select name='mul_try[]' onchange='submit();' multiple='TRUE' size='7'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_try) > 0 and in_array(trim($srow['selite']), $mul_try)) {
			$sel = 'SELECTED';
		}

		echo "<option value='$srow[selite]' $sel>";
		if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
			echo $srow['selite']." ";
		}
		echo "$srow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	if ($lisa_haku_osasto == "" and $lisa_haku_try == "") {
		$query = "	SELECT avainsana.selite, avainsana.selitetark		         
		            FROM avainsana
		            WHERE avainsana.yhtio 	= '$kukarow[yhtio]'
		            and avainsana.laji 		= 'TUOTEMERKKI'
					$orderlisa";
	}
	else {
		$query = "	SELECT distinct avainsana.selite, avainsana.selitetark
					FROM tuote
					JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI')
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa_haku_osasto
					$lisa_haku_try
					$orderlisa";
	}
	$sresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($sresult) > 0) {
		echo "<table style='display:inline;'><tr><th>",t("Tuotemerkki"),"</th></tr>";
		echo "<tr><td nowrap valign='top' class='back'>";
		echo "<select name='mul_tme[]' multiple='TRUE' size='7' onchange='submit();'>";
		echo "<option value=''>",t("Ei valintaa"),"</option>";

		while($srow = mysql_fetch_array ($sresult)){
			$sel = '';

			if (count($mul_tme) > 0 and in_array(trim($srow['selite']), $mul_tme)) {
				$sel = 'SELECTED';
			}

			echo "<option value='$srow[selite]' $sel>$srow[selite]</option>";
		}

		echo "</select></td>";
		echo "</tr></table>";
	}

	if ($lisa_haku_tme != '' or  $lisa_haku_try != '') {
		$query = "	SELECT DISTINCT tuote.malli
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.malli != ''
					$lisa_haku_osasto
					$lisa_haku_try
					$lisa_haku_tme
					ORDER BY malli";
		$sxresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sxresult) > 0) {
			echo "<table style='display:inline;'><tr><th>",t("Malli"),"</th></tr>";
			echo "<tr><td nowrap valign='top' class='back'>";
			echo "<select name='mul_malli[]' multiple='TRUE' size='7' onchange='submit();'>";
			echo "<option value=''>",t("Ei valintaa"),"</option>";

			while($mallirow = mysql_fetch_array ($sxresult)){
				$sel = '';

				if (count($mul_malli) > 0 and in_array(trim($mallirow['malli']), $mul_malli)) {
					$sel = 'SELECTED';
				}

				echo "<option value='$mallirow[malli]' $sel>$mallirow[malli]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr></table>";
		}
	}

	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Tuotemyyj‰"),"</th></tr>";
	echo "<tr>";

	// tehd‰‰n query
	$query = "	SELECT DISTINCT myyja, nimi 
				FROM kuka 
				WHERE yhtio = '$kukarow[yhtio]' 
				AND myyja>0
				ORDER BY myyja";
	$sresult = mysql_query($query) or pupe_error($query);

	/*
	if ($tuotemyyja == "KAIKKI") $sel = "selected";
	echo "<option value='KAIKKI' $sel>".t("Tuotemyyjitt‰in")."</option>";
	*/

	echo "<td nowrap valign='top' class='back'><select name='mul_tuotemyyja[]' multiple size='7' onchange='submit();'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_tuotemyyja) > 0) {
			if (in_array(trim($sxrow['myyja']), $mul_tuotemyyja)) {
				$sel = 'SELECTED';
			}
		}
	
		echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Tuoteostaja"),"</th></tr>";
	echo "<tr>";

	$query = "	SELECT distinct myyja, nimi 
				FROM kuka 
				WHERE yhtio='$kukarow[yhtio]' 
				AND myyja>0
				ORDER BY myyja";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top' class='back'><select name='mul_tuoteostaja[]' multiple size='7' onchange='submit();'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_tuoteostaja) > 0) {
			if (in_array(trim($sxrow['myyja']), $mul_tuoteostaja)) {
				$sel = 'SELECTED';
			}
		}
	
		echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";
	echo "<br>";
	echo "<table style='display:inline;'>";
	echo "<tr>";
	echo "<th>".t("Valitse luokka").":</th>";
	echo "<td><select name='luokka'>";
	echo "<option value=''>Valitse luokka</option>";

	$sel = array();
	$sel[$luokka] = "selected";

	$i=0;
	foreach ($ryhmanimet as $nimi) {
		echo "<option value='$i' $sel[$i]>$nimi</option>";
		$i++;
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Syˆt‰ viimeinen saapumisp‰iv‰").":</th>";
	echo "	<td><input type='text' name='saapumispp' value='$saapumispp' size='2'>
			<input type='text' name='saapumiskk' value='$saapumiskk' size='2'>
			<input type='text' name='saapumisvv' value='$saapumisvv'size='4'></td></tr>";
	
	echo "<tr>";
	echo "<th>".t("Varastopaikoittain").":</th>";

	$sel = "";
	if ($paikoittain == 'JOO') {
		$sel = "CHECKED";
	}

	echo "<td><input type='checkbox' name='paikoittain' value='JOO' $sel></td></tr>";
	
	echo "<tr>";
	echo "<th>".t("Taso").":</th>";
	
	if ($lisatiedot != '') $sel = "selected";
	else $sel = "";
	
	echo "<td><select name='lisatiedot'>";
	echo "<option value=''>".t("Normaalitiedot")."</option>";
	echo "<option value='TARK' $sel>".t("N‰ytet‰‰n kaikki sarakkeet")."</option>";
	echo "</select></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	
	echo "</form>";
	echo "</table><br>";

	if ($aja == "AJA") {
		
		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksit‰‰n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');
			
			$excelrivi = 0;
			
			$worksheet->write($excelrivi, 0,  t("ABC"));			
			$worksheet->write($excelrivi, 1,  t("ABC osaston luokka"));
			$worksheet->write($excelrivi, 2,  t("ABC tuoteryhm‰n luokka"));			
			$worksheet->write($excelrivi, 3,  t("Tuoteno"));
			$worksheet->write($excelrivi, 4,  t("Toim_tuoteno"));
			$worksheet->write($excelrivi, 5,  t("Nimitys"));
			$worksheet->write($excelrivi, 6,  t("Merkki"));
			$worksheet->write($excelrivi, 7,  t("Osasto"));
			$worksheet->write($excelrivi, 8,  t("Try"));
			$worksheet->write($excelrivi, 9,  t("Tulopvm"));
			$worksheet->write($excelrivi, 10,  t("Myynti$yhtiorow[valkoodi]"));
			$worksheet->write($excelrivi, 11,  t("Kate"));
			$worksheet->write($excelrivi, 12,  t("Kate%"));
			$worksheet->write($excelrivi, 13,  t("Kateosuus"));
			$worksheet->write($excelrivi, 14,  t("Vararvo"));
			$worksheet->write($excelrivi, 15,  t("Kierto"));
			$worksheet->write($excelrivi, 16,  t("MyyntiKpl"));
			$worksheet->write($excelrivi, 17,  t("MyyntieraKpl"));
			$worksheet->write($excelrivi, 18,  t("Myyntiera$yhtiorow[valkoodi]"));
			$worksheet->write($excelrivi, 19,  t("Myyntirivit"));
			$worksheet->write($excelrivi, 20,  t("Puuterivit"));
			$worksheet->write($excelrivi, 21,  t("Palvelutaso"));
			$worksheet->write($excelrivi, 22,  t("OstoeraKPL"));
			$worksheet->write($excelrivi, 23,  t("Ostoera$yhtiorow[valkoodi]"));
			$worksheet->write($excelrivi, 24,  t("Ostorivit"));
			$worksheet->write($excelrivi, 25,  t("KustannusMyynti"));
			$worksheet->write($excelrivi, 26,  t("KustannusOsto"));
			$worksheet->write($excelrivi, 27,  t("KustannusYht"));
			$worksheet->write($excelrivi, 28,  t("Kate-Kustannus"));
			$worksheet->write($excelrivi, 29,  t("Tuotepaikka"));
			$worksheet->write($excelrivi, 30,  t("Saldo"));
			$worksheet->write($excelrivi, 31,  t("Myyj‰"));
			$worksheet->write($excelrivi, 32,  t("Ostaja"));
			$worksheet->write($excelrivi, 33,  t("Malli"));
			$worksheet->write($excelrivi, 34,  t("Mallitarkenne"));
			$worksheet->write($excelrivi, 35,  t("Saapumispvm"));
			$excelrivi++;
		}
				
		echo "<pre>";
		echo t("ABC")."\t";		
		echo t("ABC osaston luokka");
		echo t("ABC tuoteryhm‰n luokka");		
		echo t("Tuoteno")."\t";
		echo t("Toim_tuoteno")."\t";
		echo t("Nimitys")."\t";
		echo t("Merkki")."\t";
		echo t("Osasto")."\t";
		echo t("Try")."\t";
		echo t("Tulopvm")."\t";
		echo t("Myynti")." $yhtiorow[valkoodi]\t";
		echo t("Kate")."\t";
		echo t("Kate%")."\t";
		echo t("Kateosuus")."\t";
		echo t("Vararvo")."\t";
		echo t("Kierto")."\t";
		echo t("MyyntiKpl")."\t";
		echo t("MyyntieraKpl")."\t";
		echo t("Myyntiera")." $yhtiorow[valkoodi]\t";
		echo t("Myyntirivit")."\t";
		echo t("Puuterivit")."\t";
		echo t("Palvelutaso")."\t";
		echo t("OstoeraKPL")."\t";
		echo t("Ostoera")." $yhtiorow[valkoodi]\t";
		echo t("Ostorivit")."\t";
		echo t("KustannusMyynti")."\t";
		echo t("KustannusOsto")."\t";
		echo t("KustannusYht")."\t";
		echo t("Kate-Kustannus")."\t";
		echo t("Tuotepaikka")."\t";
		echo t("Saldo")."\t";
		echo t("Myyja")."\t";
		echo t("Ostaja")."\t";
		echo t("Malli")."\t";
		echo t("Mallitarkenne")."\t";
		echo t("Saapumispvm")."\t";
		echo "\n";

		if (count($haku) > 0) {
			foreach ($haku as $kentta => $arvo) {
				if (strlen($arvo) > 0 and $kentta != 'kateosuus') {
					$lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
					$ulisa2 .= "&haku[$kentta]=$arvo";
				}
				if (strlen($arvo) > 0 and $kentta == 'kateosuus') {
					$hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
					$ulisa2 .= "&haku[$kentta]=$arvo";
				}
			}
		}

		$saapumispvmlisa = "";

		if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
			$saapumispvm = "$saapumisvv-$saapumiskk-$saapumispp";
			$saapumispvmlisa = " and abc_aputaulu.saapumispvm <= '$saapumispvm' ";
		}
	
		$query = "	SELECT
					distinct luokka
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					ORDER BY luokka";
		$luokkares = mysql_query($query) or pupe_error($query);

		while($luokkarow = mysql_fetch_array($luokkares)) {

			//kauden yhteismyynnit ja katteet
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kate)  yhtkate
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'						
						and luokka = '$luokkarow[luokka]'
						$lisa
						$saapumispvmlisa";
			$sumres = mysql_query($query) or pupe_error($query);
			$sumrow = mysql_fetch_array($sumres);
			
			$sumrow['yhtkate'] = (float) $sumrow['yhtkate'];
			$sumrow['yhtmyynti'] = (float) $sumrow['yhtmyynti'];

			//haetaan rivien arvot
			$query = "	SELECT
						luokka,
						luokka_osasto,
						luokka_try,
						tuoteno,
						osasto,
						try,
						summa,
						kate,
						tulopvm,
						katepros,
						if ($sumrow[yhtkate] = 0, 0, kate/$sumrow[yhtkate]*100)	kateosuus,
						vararvo,
						varaston_kiertonop,
						myyntierankpl,
						myyntieranarvo,
						rivia,
						kpl,
						puuterivia,
						palvelutaso,
						ostoerankpl,
						ostoeranarvo,
						osto_rivia,
						osto_kpl,
						osto_summa,
						kustannus,
						kustannus_osto,
						kustannus_yht,
						kate - kustannus_yht total,
						saldo,
						myyjanro,
						ostajanro,
						malli,
						mallitarkenne,
						saapumispvm
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and luokka = '$luokkarow[luokka]'
						$lisa
						$saapumispvmlisa
						ORDER BY $abcwhat desc";
			$res = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($res)) {

				//tuotenimi
				$query = "	SELECT tuote.nimitys, tuote.tuotemerkki, group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno
							FROM tuotteen_toimittajat
							JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno
							WHERE tuotteen_toimittajat.tuoteno='$row[tuoteno]'
							and tuotteen_toimittajat.yhtio='$kukarow[yhtio]'
							group by tuote.tuoteno";
				$tuoresult = mysql_query($query) or pupe_error($query);
				$tuorow = mysql_fetch_array($tuoresult);
				
				$query = "	SELECT distinct myyja, nimi 
							FROM kuka 
							WHERE yhtio='$kukarow[yhtio]' 
							AND myyja = '$row[myyjanro]'
							AND myyja != ''
							ORDER BY myyja";
				$myyjaresult = mysql_query($query) or pupe_error($query);
				$myyjarow = mysql_fetch_array($myyjaresult);

				$query = "	SELECT distinct myyja, nimi 
							FROM kuka 
							WHERE yhtio='$kukarow[yhtio]' 
							AND myyja = '$row[ostajanro]'
							AND myyja != ''
							ORDER BY myyja";
				$ostajaresult = mysql_query($query) or pupe_error($query);
				$ostajarow = mysql_fetch_array($ostajaresult);

				//haetaan varastopaikat ja saldot
				if ($paikoittain == 'JOO') {
					$query = "	SELECT concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, saldo
								from tuotepaikat
								where tuoteno 	= '$row[tuoteno]'
								and yhtio 		= '$kukarow[yhtio]'";
				}
				else {
					$query = "	SELECT sum(saldo) saldo	
								from tuotepaikat
								where tuoteno	= '$row[tuoteno]'
								and yhtio 		= '$kukarow[yhtio]'";
					
				}						
				$paikresult = mysql_query($query) or pupe_error($query);

				while ($paikrow = mysql_fetch_array($paikresult)) {
					
					// Lis‰t‰‰n rivi exceltiedostoon
					if(isset($workbook)) {
						$worksheet->write($excelrivi, 0,  $ryhmanimet[$row["luokka"]]);						
						$worksheet->write($excelrivi, 1,  $ryhmanimet[$row["luokka_osasto"]]);
						$worksheet->write($excelrivi, 2,  $ryhmanimet[$row["luokka_try"]]);						
						$worksheet->write($excelrivi, 3,  "$row[tuoteno]");
						$worksheet->write($excelrivi, 4,  "$tuorow[toim_tuoteno]");
						$worksheet->write($excelrivi, 5,  asana('nimitys_',$row['tuoteno'],$tuorow['nimitys']));
						$worksheet->write($excelrivi, 6,  "$tuorow[tuotemerkki]");
						$worksheet->write($excelrivi, 7,  "$row[osasto]");
						$worksheet->write($excelrivi, 8,  "$row[try]");
						$worksheet->write($excelrivi, 9,  "$row[tulopvm]");
						$worksheet->write($excelrivi, 10,  sprintf('%.1f',$row["summa"]));
						$worksheet->write($excelrivi, 11,  sprintf('%.1f',$row["kate"]));
						$worksheet->write($excelrivi, 12, sprintf('%.1f',$row["katepros"]));
						$worksheet->write($excelrivi, 13, sprintf('%.1f',$row["kateosuus"]));
						$worksheet->write($excelrivi, 14, sprintf('%.1f',$row["vararvo"]));
						$worksheet->write($excelrivi, 15, sprintf('%.1f',$row["varaston_kiertonop"]));
						$worksheet->write($excelrivi, 16, sprintf('%.1f',$row["kpl"]));
						$worksheet->write($excelrivi, 17, sprintf('%.1f',$row["myyntierankpl"]));
						$worksheet->write($excelrivi, 18, sprintf('%.1f',$row["myyntieranarvo"]));
						$worksheet->write($excelrivi, 19, sprintf('%.0f',$row["rivia"]));
						$worksheet->write($excelrivi, 20, sprintf('%.0f',$row["puuterivia"]));
						$worksheet->write($excelrivi, 21, sprintf('%.1f',$row["palvelutaso"]));
						$worksheet->write($excelrivi, 22, sprintf('%.1f',$row["ostoerankpl"]));
						$worksheet->write($excelrivi, 23, sprintf('%.1f',$row["ostoeranarvo"]));
						$worksheet->write($excelrivi, 24, sprintf('%.0f',$row["osto_rivia"]));
						$worksheet->write($excelrivi, 25, sprintf('%.1f',$row["kustannus"]));
						$worksheet->write($excelrivi, 26, sprintf('%.1f',$row["kustannus_osto"]));
						$worksheet->write($excelrivi, 27, sprintf('%.1f',$row["kustannus_yht"]));
						$worksheet->write($excelrivi, 28, sprintf('%.1f',$row["total"]));
						$worksheet->write($excelrivi, 29, "$paikrow[paikka]");
						$worksheet->write($excelrivi, 30, sprintf('%.0f',$paikrow["saldo"]));
						$worksheet->write($excelrivi, 31, "$myyjarow[nimi]");
						$worksheet->write($excelrivi, 32, "$ostajarow[nimi]");
						$worksheet->write($excelrivi, 33, "$row[malli]");
						$worksheet->write($excelrivi, 34, "$row[mallitarkenne]");
						$worksheet->write($excelrivi, 35, tv1dateconv($row["saapumispvm"]));
						$excelrivi++;
					}
				
					echo $ryhmanimet[$row["luokka"]]."\t";
					echo $ryhmanimet[$row["luokka_osasto"]]."\t";
					echo $ryhmanimet[$row["luokka_try"]]."\t";
					echo "$row[tuoteno]\t";
					echo "$tuorow[toim_tuoteno]\t";
					echo asana('nimitys_',$row['tuoteno'],$tuorow['nimitys'])."\t";
					echo "$tuorow[tuotemerkki]\t";
					echo "$row[osasto]\t";
					echo "$row[try]\t";
					echo "$row[tulopvm]\t";
					echo str_replace(".",",",sprintf('%.1f',$row["summa"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kate"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["katepros"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["varaston_kiertonop"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kpl"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."\t";
					echo str_replace(".",",",sprintf('%.0f',$row["rivia"]))."\t";
					echo str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["ostoerankpl"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."\t";
					echo str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["total"]))."\t";
					echo "$paikrow[paikka]\t";
					echo str_replace(".",",",sprintf('%.0f',$paikrow["saldo"]))."\t";
					echo "$myyjarow[nimi]\t";
					echo "$ostajarow[nimi]\t";
					echo "$row[malli]\t";
					echo "$row[mallitarkenne]\t";
					echo tv1dateconv($row["saapumispvm"])."\t";
					echo "\n";					
				}
			}
		}

		echo "</pre>";
		
		if(isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna Excel").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='exceltee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='ABC_listaus.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}
?>
