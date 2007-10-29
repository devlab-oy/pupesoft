<?php


	// käytetään slavea
	$useslave = 1;

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "tilauskanta_laskutus.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Tilauskanta, Tilausten vastaanotto ja Laskutus")."</font><hr>";
			
		
		// hehe, näin on helpompi verrata päivämääriä
		$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);

		if ($row["ero"] > 365) {
			echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
			$tee = "";
		}
		
		// jos joku päiväkenttä on tyhjää ei tehdä mitään
		if ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "") {
			$tee = "";
		}

		if ($tee == 'go') {

			$vvaa = $vva - '1';
			$vvll = $vvl - '1';

			$query = " SELECT ";
	
			$MONTH_ARRAY = array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));
			
			$start 		= date("Y-m-d",mktime(0, 0, 0, $kka, $ppa,  $vva));
			$start_ed 	= date("Y-m-d",mktime(0, 0, 0, $kka, $ppa,  $vvaa));
			
			$startmonth	= date("Ymd",mktime(0, 0, 0, $kka, $ppa,  $vva));
			$endmonth 	= date("Ymd",mktime(0, 0, 0, $kkl, $ppl,  $vvl));
						
			for ($i = $startmonth;  $i <= $endmonth;) {
				
				$alku  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));
				$loppu = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)));
				
				$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));
				$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)-1));
				
				if ($i == $startmonth) {
					$query .= " round(sum(if(tilausrivi.laadittu < '$alku 00:00:00' and (tilausrivi.laskutettuaika = '0000-00-00' or tilausrivi.laskutettuaika >= '$alku'), if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) 'TKVA',\n";
				}
				
				$query .= " round(sum(if(tilausrivi.laadittu <= '$loppu 23:59:59' and (tilausrivi.laskutettuaika = '0000-00-00' or tilausrivi.laskutettuaika > '$loppu'), if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) '".(substr($i,4,2)*1).".1',\n";
				$query .= " round(sum(if(tilausrivi.laadittu <= '$loppu_ed 23:59:59' and (tilausrivi.laskutettuaika = '0000-00-00' or tilausrivi.laskutettuaika > '$loppu_ed'), if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) '".(substr($i,4,2)*1).".2',\n";
				
				$query .= " round(sum(if(tilausrivi.laadittu >= '$alku 00:00:00' and tilausrivi.laadittu <= '$loppu 23:59:59', if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) '".(substr($i,4,2)*1).".3',\n";
				$query .= " round(sum(if(tilausrivi.laadittu >= '$alku_ed 00:00:00' and tilausrivi.laadittu <= '$loppu_ed 23:59:59', if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) '".(substr($i,4,2)*1).".4',\n";
				
				$query .= " round(sum(if(tilausrivi.laadittu >= '$start 00:00:00' and tilausrivi.laadittu <= '$loppu 23:59:59', if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) '".(substr($i,4,2)*1).".5',\n";
				$query .= " round(sum(if(tilausrivi.laadittu >= '$start_ed 00:00:00' and tilausrivi.laadittu <= '$loppu_ed 23:59:59', if(tilausrivi.laskutettuaika = '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)), tilausrivi.rivihinta),0))/1000,0) '".(substr($i,4,2)*1).".6',\n";
				
				$query .= " round(sum(if(tilausrivi.laskutettuaika >= '$alku'  and tilausrivi.laskutettuaika <= '$loppu', tilausrivi.rivihinta, 0))/1000,0) '".(substr($i,4,2)*1).".7',\n";
				$query .= " round(sum(if(tilausrivi.laskutettuaika >= '$alku_ed'  and tilausrivi.laskutettuaika <= '$loppu_ed', tilausrivi.rivihinta, 0))/1000,0) '".(substr($i,4,2)*1).".8',\n";
				
				$query .= " round(sum(if(tilausrivi.laskutettuaika >= '$start'  and tilausrivi.laskutettuaika <= '$loppu', tilausrivi.rivihinta, 0))/1000,0) '".(substr($i,4,2)*1).".9',\n";
				$query .= " round(sum(if(tilausrivi.laskutettuaika >= '$start_ed'  and tilausrivi.laskutettuaika <= '$loppu_ed', tilausrivi.rivihinta, 0))/1000,0) '".(substr($i,4,2)*1).".10',\n";
													
				$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1,  substr($i,0,4)));
			}
			
			if ($kustannuspaikka != '') {
				$kustp1 = ", asiakas, tuote";
				$kustp2 = "and asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno and (tuote.kustp='$kustannuspaikka' or asiakas.kustannuspaikka='$kustannuspaikka')";
			}
			
			// Vika pilkku pois
			$query  = substr($query, 0 ,-2);
			
			$query .= "	FROM lasku use index (yhtio_tila_tapvm), tilausrivi use index (yhtio_otunnus) $kustp1
						WHERE tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='L'
						and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('L','N')
						and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
						and ((lasku.tapvm = '0000-00-00') or (lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl') or (lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl'))
						$kustp2";
			$result = mysql_query($query) or pupe_error($query);
			
			//echo "<pre>$query</pre>";
						
			if (strpos($_SERVER['SCRIPT_NAME'], "tilauskanta_laskutus.php") !== FALSE) {
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

					$excelrivi = 0;
				}
			}

			echo "<table>";
			echo "<tr>
				<th>".t("Kausi nyt")."</th>
				<td>$ppa</td>
				<td>$kka</td>
				<td>$vva</td>
				<th>-</th>
				<td>$ppl</td>
				<td>$kkl</td>
				<td>$vvl</td>
				</tr>\n";
			echo "<tr>
				<th>".t("Kausi ed")."</th>
				<td>$ppa</td>
				<td>$kka</td>
				<td>$vvaa</td>
				<th>-</th>
				<td>$ppl</td>
				<td>$kkl</td>
				<td>$vvll</td>
				</tr>\n";
			if ($kustannuspaikka != '') {
				echo "<tr>
					<th>".t("Kustannuspaikka")."</th>
					<td>$kustannuspaikka</td>
					</tr>\n";
				
			}
			echo "</table><br>";

			echo "<table><tr>";

			$row = mysql_fetch_array($result);
			
			echo "<tr>";
			echo "<td class='back'>".t("Tilauskanta")."</td>";
			
			if(isset($workbook)) {
				$worksheet->write($excelrivi, 0, t("Tilauskanta"));
			}
		
			for ($i=$kka; $i <= $kkl; $i++) { 	
				echo "<th>".$MONTH_ARRAY[$i]."</td>";
				
				if(isset($workbook)) {
					$worksheet->write($excelrivi, $i, $MONTH_ARRAY[$i], $format_bold);
				}
			}
			$excelrivi++;
			echo "</tr>";
			
			echo "<tr><th>".t("Kauden alussa").":</th><td valign='top' align='right'>".(float) $row["TKVA"]."</td></tr>";
			
			if(isset($workbook)) {
				$worksheet->write($excelrivi, 0, $vva." alku:", $format_bold);
				$worksheet->writeNumber($excelrivi, 1, $row["TKVA"]);
			}
			$excelrivi++;
			
			for ($ii=1; $ii <= 10; $ii++) { 
				if ($ii == 3) {
					echo "<tr><td class='back'>Tilauksia sisään</td></tr>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, 0, "Tilauksia sisään");
					}
					$excelrivi++;
				}
				if ($ii == 7) {
					echo "<tr><td class='back'>Laskutettu</td></tr>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, 0, "Laskutettu");
					}
					$excelrivi++;
				}
				
				echo "<tr>";
				
				if ($ii % 2 != 0) {
					echo "<th>Kauden lopussa: $vva";
					
					if ($ii == 5 or $ii == 9)  {
						echo " kum.";	
					}
					
					echo "</th>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, 0, "Kauden lopussa: $vva", $format_bold);
					}
				}
				else {
					echo "<th>Kauden lopussa: $vvaa";
					
					if ($ii == 6 or $ii == 10)  {
						echo " kum.";	
					}
					
					echo "</th>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, 0, "Kauden lopussa: $vvaa", $format_bold);
					}
				}
				
				for ($i=$kka; $i <= $kkl; $i++) { 	
					echo "<td valign='top' align='right'>".(float) $row[$i.".".$ii]."</td>";
					
					if(isset($workbook)) {
						$worksheet->writeNumber($excelrivi, $i, $row[$i.".".$ii]);
					}
				}
				echo "</tr>";
				$excelrivi++;
				
				if ($ii == 6) {
					echo "<tr><th>Index</th>";
					
					for ($i=$kka; $i <= $kkl; $i++) { 	
						echo "<td valign='top' align='right'>".(float) round(($row[$i.".3"]/$row[$i.".4"]*100),0)."</td>";

						if(isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $i, $row[$i.".".$ii]);
						}
					}
					echo "</tr>";
				}
				if ($ii == 10) {
					echo "<tr><th>Index</th>";
					
					for ($i=$kka; $i <= $kkl; $i++) { 	
						echo "<td valign='top' align='right'>".(float) round(($row[$i.".7"]/$row[$i.".8"]*100),0)."</td>";

						if(isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $i, $row[$i.".".$ii]);
						}
					}
					echo "</tr>";
				}
			}
		
			echo "</table>";
			echo "<br>";
				
			if(isset($workbook)) {
				// We need to explicitly close the workbook
				$workbook->close();

				echo "<table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Tilauskanta_Laskutus.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
			
			echo "<br><br><hr>";
		}

		if ($lopetus == "") {
			//Käyttöliittymä
			if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, 1, 1, date("Y")));
			if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, 1, 1, date("Y")));
			if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, 1, 1, date("Y")));
			
			if (!isset($kkl)) $kkl = date("m");
			if (!isset($vvl)) $vvl = date("Y");
			if (!isset($ppl)) $ppl = date("d");
			if (!isset($yhtio)) $yhtio = "'$kukarow[yhtio]'";

			echo "<br>\n\n\n";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='go'>";

			// päivämäärärajaus
			echo "<table>";
			echo "<tr>
				<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr>\n
				<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>
				</tr>\n
				</table>";
			echo "<table><tr><th>".t("Valitse kustannuspaikka")."</th><td>";

			$query = "	SELECT nimi, tunnus
						FROM kustannuspaikka
						WHERE yhtio='$kukarow[yhtio]' order by tunnus";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<select name='kustannuspaikka'>";
			echo "<option value=''>".t("Kaikki kustannuspaikat")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($kustannuspaikka == $srow[1]) {
					$sel = "selected";
				}
				echo "<option value='$srow[1]' $sel>$srow[0]</option>";
			}
			echo "</select>";
			echo "</tr>\n";
			
			echo "</table>";
			
			echo "<br>";
			echo "<input type='submit' value='".t("Aja raportti")."'>";
			echo "</form>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "tilauskanta_laskutus.php") !== FALSE) {
			require ("../inc/footer.inc");
		}
	}
?>
