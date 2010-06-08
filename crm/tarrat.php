<?php
	require "../inc/parametrit.inc";

	echo "<font class='head'>".t("Tulosta osoitetarrat")."</font><hr>";
	
	if ($oikeurow[2] != '1') { // Saako p‰ivitt‰‰
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lis‰t‰ t‰t‰ tietoa")."</b><br>";
			$uusi = '';
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa t‰t‰ tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa t‰t‰ tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
		}
	}

	if ($tee == "TULOSTA" and count($otunnus) > 0) {
		$tulostimet[0] = 'Tarrat';

		if (is_array($otunnus)) {
			$otunnu = "";
			
			foreach($otunnus as $otun) {
				$otunnu .= $otun.",";
			}
			
			$otunnus = substr($otunnu, 0, -1);
		}

		if (isset($rajaus)) {
			$rajaus = unserialize(urldecode($rajaus));
			$arvomatikka 		= $rajaus[0];
			$tarra_aineisto 	= $rajaus[1];
			$raportti			= $rajaus[2];
			$toimas				= $rajaus[3];
			$as_yht_tiedot		= $rajaus[4];
		}
		
		$rajaus = array($arvomatikka, $tarra_aineisto, $raportti, $toimas, $as_yht_tiedot);
		$rajaus = urlencode(serialize($rajaus));

		if (count($komento) == 0) {
			require("../inc/valitse_tulostin.inc");
		}

		$joinilisa = "";
		$selectilisa = "";
		
		if ($as_yht_tiedot == 'on') {
			$selectilisa = ", yht.nimi AS yht_nimi, yht.titteli AS yht_titteli ";
			$joinilisa = " LEFT JOIN yhteyshenkilo yht on yht.yhtio = asiakas.yhtio and yht.liitostunnus = asiakas.tunnus ";
		}

		$query = "	SELECT asiakas.* $selectilisa
					FROM asiakas
					$joinilisa
					WHERE asiakas.yhtio = '$kukarow[yhtio]' 
					and asiakas.tunnus in ($otunnus)";
		$res = mysql_query($query) or pupe_error($query);
	    
		$laskuri = 1;
		$sarake  = 1;	
		$sisalto = "";
				
		if ($raportti == 33) {
			$rivinpituus_ps	= 28;
			$rivinpituus	= 27;
			$sarakkeet 		= 3;
			$rivit 			= 11;
			$sisalto .= "\n";
			$sisalto .= "\n";
			$sisalto .= "\n";
		}
		elseif ($raportti == 24) {
			$rivinpituus_ps	= 28;
			$rivinpituus	= 27;
			$sarakkeet 		= 3;
			$rivit 			= 8;

			if ($as_yht_tiedot == 'on') {
				$sisalto .= "\n";
				$sisalto .= "\n";
			}
		}
		
		while ($row = mysql_fetch_array($res)) {
			
			if ($yhtiorow["kalenterimerkinnat"] == "") {
				$kysely = "	INSERT INTO kalenteri
							SET tapa 		= '".t("Osoitetarrat")."',
							asiakas  		= '$row[ytunnus]',
							liitostunnus 	= '$row[tunnus]',
							kuka     		= '$kukarow[kuka]',
							yhtio    		= '$kukarow[yhtio]',
							tyyppi   		= 'Memo',
							pvmalku  		= now(),
							kentta01 		= '$kukarow[nimi] tulosti osoitetarrat.\n$arvomatikka',
							laatija			= '$kukarow[kuka]',
							luontiaika		= now()";
				$result = mysql_query($kysely) or pupe_error($kysely);
			}			
			
    	    // k‰ytet‰‰n toim_ tietoja jos niin halutaan
    		if ($_POST['toimas'] == 'on') {
				
				// tarkistetaan tiedot
				$nimi    = (trim($row['toim_nimi']) != '')       ? true : false;
				$osoite  = (trim($row['toim_osoite']) != '')     ? true : false;
				$postino = (trim($row['toim_postino']) != ''
							and $row['toim_postino'] != '00000') ? true : false;
				$postitp = (trim($row['toim_postitp']) != '')    ? true : false;
				$maa     = (trim($row['toim_maa']) != '')        ? true : false;

 				// ovatko tiedot validit
				if ($nimi and $osoite and $postino and $postitp and $maa) {
					$row['nimi']     = $row['toim_nimi'];
					$row['nimitark'] = $row['toim_nimitark'];
					$row['osoite']   = $row['toim_osoite'];
					$row['postino']  = $row['toim_postino'];
					$row['postitp']  = $row['toim_postitp'];
					$row['maa']      = $row['toim_maa'];
				}
    		}
    	    
			if ($sarake == 3) {
				$lisa = " ";
			}
			else {
				$lisa = "";
			}
			
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["nimi"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["nimitark"]))."\n";
			if ($as_yht_tiedot == 'on' and $row["yht_nimi"] != '') {
				$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["yht_nimi"]))."\n";
			}
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["osoite"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["postino"]." ".$row["postitp"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["maa"]))."\n";
			
			if ($as_yht_tiedot == 'on' and $row["yht_nimi"] != '') {
				$sisalto .= "\n";
			}
			else {
				$sisalto .= "\n\n";
			}
			
			if ($raportti == 24 and $laskuri != $rivit) {
				$sisalto .= "\n\n\n";
			}
			
			if ($raportti == 33 and $laskuri == ($rivit-1)) {
				$sisalto .= "\n";
				$sisalto .= "\n";
				$sisalto .= "\n";
				$sisalto .= "\n";
				$laskuri++;	
			}
			
			
			if ($laskuri == $rivit) {
				if ($raportti == 33) {
					$sisalto .= "\n";
					$sisalto .= "\n";
					$sisalto .= "\n";
				}
				
				$laskuri = 0;
				$sarake++;
			}
			
			$laskuri++;
		}

		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$filenimi = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".txt";
		$fh = fopen($filenimi, "w+");
		fputs($fh, $sisalto);
		fclose($fh);

		$line = exec("a2ps -o ".$filenimi.".ps --no-header --columns=$sarakkeet -R --medium=a4 --chars-per-line=$rivinpituus_ps --margin=0 --major=columns --borders=0 $filenimi");

		// itse print komento...
		if ($komento["Tarrat"] == 'email') {
			$liite = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".pdf";
			$kutsu = "Tarrat";
			$ctype = "pdf";

			system("ps2pdf -sPAPERSIZE=a4 ".$filenimi.".ps $liite");
			
			require("inc/sahkoposti.inc");
		}
		else {
			$cmd = $komento["Tarrat"]." ".$filenimi.".ps";
			$line = exec($cmd);
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $filenimi");
		system("rm -f ".$filenimi.".ps");

		echo "<br>".t("Tarrat tulostuu")."!<br><br>";
		$tee='';
	}

	// Nyt selataan
	if ($tee == '') {
		
		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,6) == nimi) {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";
		
		
		$kentat = "nimi, osoite, postino, postitp, maa, ryhma, piiri, flag_1, flag_2, flag_3, flag_4";

		$array = split(",", $kentat);
        $count = count($array);
        for ($i=0; $i<=$count; $i++) {
			if (strlen($haku[$i]) > 0) {

				if (isset($negaatio_haku) and $negaatio_haku == 'on') {
					$lisa .= " and " . $array[$i] . " not like '%" . $haku[$i] . "%'";
				}
				else {
					$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
				}

				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
        }
        
		if (strlen($ojarj) > 0) {
        	$jarjestys = $ojarj;
        }
		else {
			$jarjestys = 'asiakas.nimi';
		}

		if ($tarra_aineisto != "") {
			$lisa .= " and tunnus in ($tarra_aineisto) ";
			$limit = "";
		}
		else {
			$limit = "LIMIT 1000";
		}

		//haetaan omat asiakkaat
		$query = "	SELECT nimi, osoite, postino, postitp, maa, ryhma, piiri, flag_1, flag_2, flag_3, flag_4, tunnus
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' 
					and nimi != '' 
					$lisa
					ORDER BY $jarjestys
					$limit";
		$result = mysql_query($query) or pupe_error($query);
			
		echo "<form action = '$PHP_SELF' method = 'post'>
			<input type='hidden' name='arvomatikka' value='$arvomatikka'>
			<input type='hidden' name='tarra_aineisto' value='$tarra_aineisto'>
			<input type='hidden' name='raportti' value='$raportti'>
			<input type='hidden' name='toimas' value='$toimas'>
			<input type='hidden' name='as_yht_tiedot' value='$as_yht_tiedot'>";
				
		echo "<table><tr>";
		echo "<th></th>";
		
		for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th><a href='$PHP_SELF?ojarj=".mysql_field_table($result,$i).".".mysql_field_name($result,$i).$ulisa."&raportti=$raportti&tarra_aineisto=$tarra_aineisto&arvomatikka=$arvomatikka&toimas=$toimas&negaatio_haku=$negaatio_haku'>".t(mysql_field_name($result,$i))."</a>";

			echo "<br><input type='text' size='10' name = 'haku[$i]' value = '$haku[$i]'></th>";
		}

		$neg_chk = '';

		if (isset($negaatio_haku) and $negaatio_haku == 'on') {
			$neg_chk = ' checked';
		}

		echo "<th valign='bottom'>".t("Negaatio")."<br><input type='checkbox' name='negaatio_haku'$neg_chk></th>";
		echo "<th valign='bottom'><input type='Submit' value = '".t("Etsi")."'></th></tr>";
		echo "</form>";


		echo "<form action = '$PHP_SELF' method = 'post'>
				<input type='hidden' name='tarra_aineisto' value='$tarra_aineisto'>
				<input type='hidden' name='tee' value='TULOSTA'>
				<input type='hidden' name='as_yht_tiedot' value='$as_yht_tiedot'>";

		while ($trow = mysql_fetch_array ($result)) {
			
			echo "<tr>";
			echo "<td><input type='checkbox' name = 'otunnus[]' value = '$trow[tunnus]' CHECKED></td>";
			
			for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
				if(strlen($trow[$i]) <= 40){
					echo "<td nowrap>$trow[$i]</td>";
				}
				else {
					echo "<td nowrap>".substr($trow[$i],0,39)."...</td>";
				}
			}
			echo "</tr>";
		}
		
		
		echo "<tr><td><input type='checkbox' name='otunnu' onclick='toggleAll(this);'></td><td>".t("Ruksaa kaikki")."</td></tr>";
		
		echo "</table>";
		echo "<br><br>";

		$otunnus = substr($otunnus,0,-1);

		$tck = "";
		$chk = "";
		$sel = "";
		
		if ($toimas != "") {
			$tck = "CHECKED";
		}

		if ($as_yht_tiedot != "") {
			$chk = "CHECKED";
		}
		
		$sel[$raportti] = "SELECTED";

		echo "<table>";
		if ($yhtiorow['kalenterimerkinnat'] == '') {
			echo "<tr><th>".t("Asiakasmemon viesti").":</th><td><input type='text' size='20' name='arvomatikka' value='$arvomatikka'></td></tr>";
		}
		
		echo "<tr><th>".t("Tulosta toimitusosoitteen tiedot").":</th><td><input type='checkbox' name='toimas' value='on' $tck></td></tr>";
		echo "<tr><th>".t("Luo aineisto yhteyshenkilˆn osoitetiedoista").":</th><td><input type='checkbox' name='as_yht_tiedot' value='on' $chk></td></tr>";
		echo "<tr><th>".t("Valitse tarra-arkin tyyppi").":</th>
				<td><select name='raportti'>
				<option value='33' $sel[33]>33 ".t("Tarraa")."</option>
				<option value='24' $sel[24]>24 ".t("Tarraa")."</option>
				</select></td></tr>";
		
		echo "<tr><td class='back'><input type='Submit' value = '".t("Tulosta")."'></td><td class='back'></td></tr></table></form>";
	}

	require ("../inc/footer.inc");
?>
