<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}
	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	if ($toim == 'MYYNTI') {
		echo "<font class='head'>".t("Asiakkaan tuoteostot").":</font><hr>";
	}
	if ($toim == 'OSTO') {
		echo "<font class='head'>".t("Tilatut tuotteet").":</font><hr>";
	}
	
	if (isset($muutparametrit) and $muutparametrit != '') {
		$muut = explode('/',$muutparametrit);

		$vva 		= $muut[0];
		$kka 		= $muut[1];
		$ppa 		= $muut[2];
		$vvl 		= $muut[3];
		$kkl		= $muut[4];
		$ppl 		= $muut[5];
		$tuoteno	= $muut[6];
	}

	if ($tee == 'NAYTATILAUS') {
		require ("naytatilaus.inc");
		echo "<hr>";
		$tee = "TULOSTA";
	}

	if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {
		
		$muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl."/".$tuoteno;
		
		if ($toim == 'MYYNTI') {
			require ("../inc/asiakashaku.inc");
		}
		if ($toim == 'OSTO') {
			require ("../inc/kevyt_toimittajahaku.inc");
		}
	}
	
	if ($tuoteno != '') {
		require ('inc/tuotehaku.inc');
	}
	
	
	//Etsi-kenttä
	echo "<br><table><form action = '$PHP_SELF' method='post'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='tee' value='ETSI'>";
			
	if ($kka == '')
		$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if ($vva == '')
		$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if ($ppa == '')
		$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

	if ($kkl == '')
		$kkl = date("m");
	if ($vvl == '')
		$vvl = date("Y");
	if ($ppl == '')
		$ppl = date("d");

	if ($toim == 'MYYNTI') {
		echo "<tr><th>".t("Asiakas").":</th>";
	}
	if ($toim == 'OSTO') {
		echo "<tr><th>".t("Toimittaja").":</th>";
	}

	if ($toim == 'OSTO' and $pvmtapa == 'toimaika') {
		$pvmtapa = "toimaika";
		$pvm_select2 = "SELECTED";
		$pvm_select1 = "";
	} else if ($toim == 'OSTO') {
		$pvmtapa = "laadittu";
		$pvm_select1 = "SELECTED";
		$pvm_select2 = "";
	}

	
	if (((int) $asiakasid > 0 or (int) $toimittajaid > 0)) {
		if ($toim == 'MYYNTI') {
			echo "<td colspan='3'>$asiakasrow[nimi] $asiakasrow[nimitark]<input type='hidden' name='asiakasid' value='$asiakasid'></td></tr>";
		}
		if ($toim == 'OSTO') {
			echo "<td colspan='3'>$toimittajarow[nimi] $toimittajarow[nimitark]<input type='hidden' name='toimittajaid' value='$toimittajaid'></td></tr>";
		}
	}
	else {
		echo "<td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='20'></td></tr>";
	}
	
	
	echo "	<tr><th>".t("Syötä tuotenumero").":</th>
			<td colspan='3'>";
	
	if (isset($tuoteno) and trim($ulos) != '') {
		echo $ulos;
	} 
	else {
		echo "<input type='text' name='tuoteno' value='$tuoteno' size='20'>";
	}
			
	echo "</td></tr>";

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
			
			if ($toim == 'OSTO') {
			echo "</tr><tr><th>".t("Valitse päivämäärän tyyppi")."</th>
				<td colspan='3'><select name='pvmtapa'>
					<option value='laadittu' $pvm_select1>".t("Tilauksen laatimispäivämäärä")."</option>
					<option value='toimaika' $pvm_select2>".t("Tilauksen toivottu toimituspäivämäärä")."</option>
				</select></td>";
			}
			
	echo "<td class='back'><input type='submit' value='".t("Etsi")."'></td></tr></form></table>";

	if ($ytunnus != '' or $tuoteno != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {
		
		if(include('Spreadsheet/Excel/Writer.php')) {

			//keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$format_num =& $workbook->addFormat();
			$format_num->setNumFormat('0.00');

			$excelrivi = 0;
		}
			
		if ($jarj != '') {
			$jarj = "ORDER BY $jarj";
		}
		else {
			$jarj = "ORDER BY lasku.laskunro desc";
		}


		if ($toim == 'OSTO') {			
			$query = "	SELECT distinct tilausrivi.tunnus, otunnus tilaus, ytunnus, 
						if(nimi!=toim_nimi and toim_nimi!='', concat(nimi,'<br>(',toim_nimi,')'), nimi) nimi, 
						if(postitp!=toim_postitp and toim_postitp!='', concat(postitp,'<br>(',toim_postitp,')'), postitp) postitp, 
						tuoteno, REPLACE(kpl+varattu,'.',',') kpl, 
						REPLACE(tilausrivi.hinta,'.',',') hinta, 
						REPLACE(rivihinta,'.',',') rivihinta, 
						lasku.toimaika, 
						tilausrivi.laskutettuaika tuloutettu, 
						lasku.tila, lasku.alatila
						FROM tilausrivi, lasku
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and lasku.yhtio=tilausrivi.yhtio
						and lasku.tunnus=tilausrivi.otunnus
						and lasku.tila IN ('O','K')
						and tilausrivi.tyyppi = 'O'
						and tilausrivi.$pvmtapa >='$vva-$kka-$ppa 00:00:00'
						and tilausrivi.$pvmtapa <='$vvl-$kkl-$ppl 23:59:59'
						and lasku.tunnus=tilausrivi.otunnus ";
		}
		else {			
			$query = "	SELECT distinct tilausrivi.tunnus, otunnus tilaus, laskunro, ytunnus, 
						if(nimi!=toim_nimi and toim_nimi!='', concat(nimi,'<br>(',toim_nimi,')'), nimi) nimi,
						if(postitp!=toim_postitp and toim_postitp!='', concat(postitp,'<br>(',toim_postitp,')'), postitp) postitp, 
						tuoteno, (kpl+varattu) kpl, 
						tilausrivi.hinta hinta, 
						rivihinta rivihinta, 
						lasku.toimaika, 
						lasku.lahetepvm, 
						lasku.tila, lasku.alatila
						FROM tilausrivi, lasku
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and lasku.yhtio=tilausrivi.yhtio
						and lasku.tunnus=tilausrivi.otunnus
						and lasku.tila in ('L','N','U')
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.laadittu >='$vva-$kka-$ppa 00:00:00'
						and tilausrivi.laadittu <='$vvl-$kkl-$ppl 23:59:59'
						and lasku.tunnus=tilausrivi.otunnus ";
		}
		
		if ($tuoteno != '') {
			$query .= " and tilausrivi.tuoteno='$tuoteno' ";
		}

		if ((int) $asiakasid > 0) {
			$query .= " and lasku.liitostunnus = '$asiakasid' ";
		}
		if ((int) $toimittajaid > 0) {
			$query .= " and lasku.liitostunnus = '$toimittajaid' ";
		}

		$query .= "$jarj";		
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<br><table border='0' cellpadding='2' cellspacing='1'>";
			echo "<tr>";
			
			for ($i=1; $i < mysql_num_fields($result)-2; $i++) {
				if ($toim == 'OSTO' and $pvmtapa == 'toimaika') {
					$pvmtapa_url = "&pvmtapa=toimaika";
				}
				echo "<th align='left'><a href='$PHP_SELF?tee=$tee&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&tuoteno=$tuoteno&ytunnus=$ytunnus&asiakasid=$asiakasid&jarj=".mysql_field_name($result,$i)."$pvmtapa_url'>".t(mysql_field_name($result,$i))."</a></th>";
				
				if(isset($workbook)) {
					$worksheet->write($excelrivi, ($i-1), ucfirst(t(mysql_field_name($result,$i))), $format_bold);
				}
			}
			
			if ($toim != "OSTO") {
				echo "<th align='left'>".t("Tyyppi")."</th>";
				
				if(isset($workbook)) {
					$worksheet->write($excelrivi, $i, t("Tyyppi"), $format_bold);
				}
			}
			
			if(isset($workbook)) {
				$excelrivi++;
			}
			
			echo "</tr>";

			$kplsumma = 0;
			$rivihintasumma = 0;
			
			while ($row = mysql_fetch_array($result)) {
				
				$excelsarake = 0;
				
				$ero = "td";
				if ($tunnus == $row['tilaus']) $ero="th";

				echo "<tr class='aktiivi'>";
				
				for ($i=1; $i<mysql_num_fields($result)-2; $i++) {
					if (mysql_field_name($result,$i) == 'kpl' or mysql_field_name($result,$i) == 'hinta' or mysql_field_name($result,$i) == 'rivihinta') {
						echo "<$ero valign='top' align='right'>".str_replace(".", ",", $row[$i])."</$ero>";
					}
					elseif (mysql_field_name($result,$i) == 'toimaika' or mysql_field_name($result,$i) == 'lahetepvm' or mysql_field_name($result,$i) == 'tuloutettu') {
						if (substr($row[$i], 0, 10) == '0000-00-00') {
							echo "<$ero valign='top'></$ero>";
						}
						else {
							echo "<$ero valign='top'>".tv1dateconv($row[$i],"pitka")."</$ero>";
						}
					}
					else {
						echo "<$ero valign='top'>$row[$i]</$ero>";
					}
					
					if(isset($workbook)) {
						if (mysql_field_name($result,$i) == 'kpl' or mysql_field_name($result,$i) == 'hinta' or mysql_field_name($result,$i) == 'rivihinta') {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row[$i], $format_num);
						}
						else {
							$worksheet->write($excelrivi, $excelsarake, $row[$i]);
						}
						$excelsarake++;
					}
				}
				
				$kplsumma += $row["kpl"];
				$rivihintasumma += $row["rivihinta"];

				if ($toim != "OSTO") {
					$laskutyyppi=$row["tila"];
					$alatila=$row["alatila"];
					
					//tehdään selväkielinen tila/alatila
					require "../inc/laskutyyppi.inc";
                	
					echo "<$ero valign='top'>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, t("$laskutyyppi")." ".t("$alatila"));
					}
				}
				
				echo "<form method='post' action='$PHP_SELF'><td class='back' valign='top'>
						<input type='hidden' name='tee' value='NAYTATILAUS'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnus' value='$row[tilaus]'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='tuoteno' value='$tuoteno'>
						<input type='hidden' name='ppa' value='$ppa'>
						<input type='hidden' name='kka' value='$kka'>
						<input type='hidden' name='vva' value='$vva'>
						<input type='hidden' name='ppl' value='$ppl'>
						<input type='hidden' name='kkl' value='$kkl'>
						<input type='hidden' name='vvl' value='$vvl'>
						<input type='submit' value='".t("Näytä tilaus")."'></td></form>";

				echo "</tr>";
				
				if(isset($workbook)) {
					$excelrivi++;
				}
			}
			
			if ($toim == "OSTO") {
				$csp = 5;	
			}
			else {
				$csp = 6;
			}
			
			echo "<tr><td colspan='$csp'>".t("Yhteensä").":</td><td>".sprintf('%01.2f', $kplsumma)."</td><td></td><td>".sprintf('%01.2f', $rivihintasumma)."</td></tr>";
			echo "</table>";
			
			if(isset($workbook)) {
				if($toim == "OSTO") {
					$worksheet->writeFormula($excelrivi, 5, "=sum(F2:F$excelrivi)");
					$worksheet->write($excelrivi, 6, "");
					$worksheet->writeFormula($excelrivi, 7, "=sum(H2:H$excelrivi)");
				}
				else {
					$worksheet->writeFormula($excelrivi, 6, "=sum(G2:G$excelrivi)");
					$worksheet->write($excelrivi, 7, "");
					$worksheet->writeFormula($excelrivi, 8, "=sum(I2:I$excelrivi)");
				}
				$workbook->close();
				
				echo "<br><table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				
				if($toim == "MYYNTI") {
					echo "<input type='hidden' name='kaunisnimi' value='Asiakkaan_tuoteostot-$ytunnus.xls'>";
				}
				else {
					echo "<input type='hidden' name='kaunisnimi' value='Toimittajalta_tilatut-$ytunnus.xls'>";
				}
				
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
							
			}	
		}
		
		else {
			echo t("Ei ostettuja tuotteita")."...<br><br>";
		}
	}

	require ("../inc/footer.inc");
?>
