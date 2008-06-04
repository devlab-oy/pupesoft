<?php

	if (isset($_POST["supertee"])) {
		if($_POST["supertee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require ("../inc/parametrit.inc");
	
	if (isset($supertee)) {
		if ($supertee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	
	echo "<font class='head'>".t("Myöhässä olevat myyntitilaukset")."</font><hr>";
	
	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
		require ("naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "HAE";
		$mul_tuoteryhma = unserialize(base64_decode($se_tuoteryhma));
		$mul_kustannuspaikka = unserialize(base64_decode($se_kustannuspaikka));
	}
	
	if ($tee == 'JARJESTA') {
		if ($suunta == '' or $suunta == "DESC") {
			$suunta = "ASC";
		}
		else {
			$suunta = "DESC";
		}
		
		$tee = "HAE";
		$mul_tuoteryhma = unserialize(base64_decode($se_tuoteryhma));
		$mul_kustannuspaikka = unserialize(base64_decode($se_kustannuspaikka));
	}
		
	if ($tee == "HAE") {
				
		if (is_array($mul_tuoteryhma) and count($mul_tuoteryhma) > 0) {
			$sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_tuoteryhma))."')";
			$lisa .= " and tuote.try in $sel_tuoteryhma ";
		}		
		
		if (is_array($mul_kustannuspaikka) and count($mul_kustannuspaikka) > 0) {
			$sel_kustannuspaikka = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_kustannuspaikka))."')";
			$lisa .= " and (asiakas.kustannuspaikka in $sel_kustannuspaikka or tuote.kustp in $sel_kustannuspaikka) ";
		}
		
		$se_tuoteryhma = base64_encode(serialize($mul_tuoteryhma));
		$se_kustannuspaikka = base64_encode(serialize($mul_kustannuspaikka));
		
		
		echo "<table><tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Asiakas")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "<th>".t("Tilaus")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimike")."</th>";
		echo "<th>".t("Kpl")."</th>";
		echo "<th>".t("Yksikkö")."</th>";
		echo "<th>".t("Arvo")."</th>";
		echo "<th>".t("Myytävissä")."</th>";
		echo "<th><a href='?tee=JARJESTA&haku=toimaika&suunta=$suunta&tunnus=$tunnus&myovv=$myovv&myokk=$myokk&myopp=$myopp&se_tuoteryhma=$se_tuoteryhma&se_kustannuspaikka=$se_kustannuspaikka'>".t("Toimitusaika")."</a></th>";
		echo "<th>".t("Tila")."</th>";
		echo "</tr>";
		
		if ($vain_excel != '' and @include('Spreadsheet/Excel/Writer.php')) {
			//keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi = 0;

			if(isset($workbook)) {
				$excelsarake = 0;

				$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Asiakas"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Postitp"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tilaus"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tuoteno"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Nimike"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Kpl"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Yksikkö"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Arvo"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Myytävissä"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Toimitusaika"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tila"), $format_bold);
				
				$excelsarake = 0;
				$excelrivi++;
			}
		}
		
		if (($myovv == "" or !is_numeric($myovv)) or ($myokk == "" or !is_numeric($myokk)) or ($myopp == "" or !is_numeric($myopp))) {
			$myovv = "0000";
			$myokk = "00";
			$myopp = "00";
		}		
		
		$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.postitp, lasku.tunnus, tilausrivi.tuoteno, tilausrivi.nimitys, sum(tilausrivi.tilkpl) myydyt, tilausrivi.yksikko,
					sum(tilausrivi.tilkpl * tilausrivi.hinta) arvo, lasku.toimaika, lasku.tila, lasku.alatila
					FROM lasku
					JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.kpl = 0
					JOIN tuote ON lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN asiakas ON lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila IN ('L','N')
					and (lasku.alatila != 'X' or tilausrivi.var='J')
					and lasku.toimaika <= '$myovv-$myokk-$myopp' 
					$lisa
					group by 1,2,3,4,5,6,8,10,11,12 
					ORDER BY lasku.toimaika $suunta";
		$result = mysql_query($query) or pupe_error($query);
		
		
		while ($tulrow = mysql_fetch_array($result)) {
			
			list(,, $myytavissa) = saldo_myytavissa($tulrow["tuoteno"], '', '', '', '', '', '', '', '', '');
			
			if ($yhtiorow['saldo_kasittely'] != '') {
				list(,, $myytavissa_tul) = saldo_myytavissa($tulrow["tuoteno"], '', '', '', '', '', '', '', '', $myovv."-".$myokk."-".$myopp);
			}
			
			if ($tulrow["alatila"] == 'X') {
				$laskutyyppi = t("Jäkitoimitus");
				$alatila	 = "";
			}
			else {
				$laskutyyppi = $tulrow["tila"];
				$alatila	 = $tulrow["alatila"];
				require ("inc/laskutyyppi.inc");
			}
			
			echo "<tr class='aktiivi'>";
			echo "<td>$tulrow[ytunnus]</td>";
			echo "<td>$tulrow[nimi]</td>";
			echo "<td>$tulrow[postitp]</td>";
			echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&myovv=$myovv&myokk=$myokk&myopp=$myopp&se_tuoteryhma=$se_tuoteryhma&se_kustannuspaikka=$se_kustannuspaikka'>$tulrow[tunnus]</a></td>";
			echo "<td>$tulrow[tuoteno]</td>";
			echo "<td>$tulrow[nimitys]</td>";
			echo "<td>$tulrow[myydyt]</td>";
			echo "<td>$tulrow[yksikko]</td>";
			echo "<td>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $tulrow[arvo])."</td>";
			if ($yhtiorow['saldo_kasittely'] != '') {
				echo "<td>$myytavissa ($myytavissa_tul)</td>";
			}
			else {
				echo "<td>$myytavissa</td>";
			}
			echo "<td>".tv1dateconv($tulrow[toimaika])."</td>";
			
			if ($tulrow['tila'] == "L" and $tulrow['alatila'] == "D") {
				echo "<td><font class='OK'>".t($laskutyyppi)."<br>".t($alatila)."</font></td>";
			}
			else {
				echo "<td>".t($laskutyyppi)."<br>".t($alatila)."</td>";
			}
			
			echo "</tr>";
			
			if(isset($workbook)) {
				$excelsarake = 0;

				$worksheet->write($excelrivi, $excelsarake, $tulrow[ytunnus], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[nimi], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[postitp], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[tunnus], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[tuoteno], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[nimitys], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[myydyt], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, $tulrow[yksikko], $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, sprintf("%.".$yhtiorow['hintapyoristys']."f", $tulrow[arvo]), $format_bold);
				$excelsarake++;
				if ($yhtiorow['saldo_kasittely'] != '') {
					$worksheet->write($excelrivi, $excelsarake, $myytavissa ."(".$myytavissa_tul.")", $format_bold);
					$excelsarake++;
				}
				else {
					$worksheet->write($excelrivi, $excelsarake, $myytavissa, $format_bold);
					$excelsarake++;
				}
				
				
				$worksheet->write($excelrivi, $excelsarake, tv1dateconv($tulrow[toimaika]), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t($laskutyyppi)."\n".t($alatila), $format_bold);
				
				$excelsarake = 0;
				$excelrivi++;
			}
		}
		
		echo "</table>";
		
		if(isset($workbook)) {

			// We need to explicitly close the workbook
			$workbook->close();

			echo "<br><table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='supertee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Myohassa_olevat.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td valign='top' class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}


	if ($myovv == '') {
		$myopp = date("j");
		$myokk = date("n");
		$myovv = date("Y");
	}
			
	echo "<form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value = 'HAE'>";
	echo "<table><tr>";
	echo "<th>".t("Anna toimituspäivä")."</th>";
	echo "<td><input type='text' name='myopp' value='$myopp' size='3'>";
	echo "<input type='text' name='myokk' value='$myokk' size='3'>";
	echo "<input type='text' name='myovv' value='$myovv' size='6'></td>";
	echo "</tr>";

	echo "<tr><th>".t("Valitse tuoteryhmä")."</th>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]'
				and avainsana.laji='TRY'
				$avainlisa
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td>";
	
	echo "<select name='mul_tuoteryhma[]' multiple='TRUE' size='10' style='width:100%;'>";

	$mul_check = '';
	if ($mul_tuoteryhma!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_tuoteryhma)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei kustannuspaikkaa")."</option>";

	while ($rivi = mysql_fetch_array($sresult)) {
		$mul_check = '';
		if ($mul_tuoteryhma!="") {
			if (in_array($rivi[0],$mul_tuoteryhma)) {
				$mul_check = 'SELECTED';
			}
		}

		echo "<option value='$rivi[0]' $mul_check>$rivi[0] $rivi[1]</option>";
	}

	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>".t("Valitse kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='mul_kustannuspaikka[]' multiple='TRUE' size='10' style='width:100%;'>";

	$mul_check = '';
	if ($mul_kustannuspaikka!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_kustannuspaikka)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei kustannuspaikkaa")."</option>";

	while ($rivi = mysql_fetch_array($vresult)) {
		$mul_check = '';
		if ($mul_kustannuspaikka!="") {
			if (in_array($rivi[0],$mul_kustannuspaikka)) {
				$mul_check = 'SELECTED';
			}
		}

		echo "<option value='$rivi[0]' $mul_check>$rivi[1]</option>";
	}

	$vain_excelchk = "";
	if ($vain_excel != '') {
		$vain_excelchk = "CHECKED";
	}

	echo "</select></td></tr>";	
	echo "<tr><th>".t("Raportti Exceliin")."</th>";
	echo "<td><input type='checkbox' name='vain_excel' $vain_excelchk></td><tr>";
	echo "<tr><td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";
	echo "</form></table>";

	

	require ("../inc/footer.inc");

?>