<?php

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-pitkälistaus")."<hr></font>";

	// tutkaillaan saadut muuttujat
	$osasto 		= trim($osasto);
	$try    		= trim($try);
	$tuotemerkki    = trim($tuotemerkki);

	if ($osasto 	 == "")	$osasto 	 = trim($osasto2);
	if ($try    	 == "")	$try 		 = trim($try2);
	if ($tuotemerkki == "")	$tuotemerkki = trim($tuotemerkki2);

	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='PITKALISTA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='aja' value='AJA'>";
	
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse osasto").":</th>";
	echo "<td><input type='text' name='osasto' size='10'></td>";

	// tehdään avainsana query
	$sresult = avainsana("OSASTO", $kukarow['kieli']);

	echo "<td><select name='osasto2'>";
	echo "<option value=''>".t("Osasto")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($osasto == $srow["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse tuoteryhmä").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	// tehdään avainsana query
	$sresult = avainsana("TRY", $kukarow['kieli']);

	echo "<td><select name='try2'>";
	echo "<option value=''>".t("Tuoteryhmä")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>".t("Syötä tai valitse tuotemerkki").":</th>";
	echo "<td><input type='text' name='tuotemerkki' size='10'></td>";

	$query = "	SELECT distinct tuotemerkki
				FROM abc_aputaulu
				WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuotemerkki2'>";
	echo "<option value=''>".t("Tuotemerkki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($tuotemerkki == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0]</option>";
	}

	echo "</select></td></tr>";
	echo "<tr>";
	echo "<th>".t("Varastopaikoittain").":</th>";
	
	$sel = "";
	if ($paikoittain == 'JOO') {
		$sel = "CHECKED";
	}
	
	echo "<td><input type='checkbox' name='paikoittain' value='JOO' $sel></td><td></td>";
	
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr>";
	echo "</form>";
	echo "</table><br>";

	if ($aja == "AJA") {
		
		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');
			
			$excelrivi = 0;
			
			$worksheet->write($excelrivi, 0,  t("ABC"));
			$worksheet->write($excelrivi, 1,  t("Tuoteno"));
			$worksheet->write($excelrivi, 2,  t("Toim_tuoteno"));
			$worksheet->write($excelrivi, 3,  t("Nimitys"));
			$worksheet->write($excelrivi, 4,  t("Merkki"));
			$worksheet->write($excelrivi, 5,  t("Osasto"));
			$worksheet->write($excelrivi, 6,  t("Try"));
			$worksheet->write($excelrivi, 7,  t("Tulopvm"));
			$worksheet->write($excelrivi, 8,  t("Myynti$yhtiorow[valkoodi]"));
			$worksheet->write($excelrivi, 9,  t("Kate"));
			$worksheet->write($excelrivi, 10,  t("Kate%"));
			$worksheet->write($excelrivi, 11,  t("Kateosuus"));
			$worksheet->write($excelrivi, 12,  t("Vararvo"));
			$worksheet->write($excelrivi, 13,  t("Kierto"));
			$worksheet->write($excelrivi, 14,  t("MyyntiKpl"));
			$worksheet->write($excelrivi, 15,  t("MyyntieraKpl"));
			$worksheet->write($excelrivi, 16,  t("Myyntiera$yhtiorow[valkoodi]"));
			$worksheet->write($excelrivi, 17,  t("Myyntirivit"));
			$worksheet->write($excelrivi, 18,  t("Puuterivit"));
			$worksheet->write($excelrivi, 19,  t("Palvelutaso"));
			$worksheet->write($excelrivi, 20,  t("OstoeraKPL"));
			$worksheet->write($excelrivi, 21,  t("Ostoera$yhtiorow[valkoodi]"));
			$worksheet->write($excelrivi, 22,  t("Ostorivit"));
			$worksheet->write($excelrivi, 23,  t("KustannusMyynti"));
			$worksheet->write($excelrivi, 24,  t("KustannusOsto"));
			$worksheet->write($excelrivi, 25,  t("KustannusYht"));
			$worksheet->write($excelrivi, 26,  t("Kate-Kustannus"));
			$worksheet->write($excelrivi, 27,  t("Tuotepaikka"));
			$worksheet->write($excelrivi, 28,  t("Saldo"));
			$excelrivi++;
		}
				
		echo "<pre>";
		echo "ABC\t";
		echo "Tuoteno\t";
		echo "Toim_tuoteno\t";
		echo "Nimitys\t";
		echo "Merkki\t";
		echo "Osasto\t";
		echo "Try\t";
		echo "Tulopvm\t";
		echo "Myynti$yhtiorow[valkoodi]\t";
		echo "Kate\t";
		echo "Kate%\t";
		echo "Kateosuus\t";
		echo "Vararvo\t";
		echo "Kierto\t";
		echo "MyyntiKpl\t";
		echo "MyyntieraKpl\t";
		echo "Myyntiera$yhtiorow[valkoodi]\t";
		echo "Myyntirivit\t";
		echo "Puuterivit\t";
		echo "Palvelutaso\t";
		echo "OstoeraKPL\t";
		echo "Ostoera$yhtiorow[valkoodi]\t";
		echo "Ostorivit\t";
		echo "KustannusMyynti\t";
		echo "KustannusOsto\t";
		echo "KustannusYht\t";
		echo "Kate-Kustannus\t";
		echo "Tuotepaikka\t";
		echo "Saldo\t";
		echo "\n";

		$osastolisa = $trylisa = $tuotemerkkilisa = "";

		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
		}
		if ($tuotemerkki != '') {
			$tuotemerkkilisa = " and tuotemerkki='$tuotemerkki' ";
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
						and tyyppi='$abcchar'
						$osastolisa
						$trylisa
						and luokka = '$luokkarow[luokka]'";
			$sumres = mysql_query($query) or pupe_error($query);
			$sumrow = mysql_fetch_array($sumres);
			
			$sumrow['yhtkate'] = (float) $sumrow['yhtkate'];
			$sumrow['yhtmyynti'] = (float) $sumrow['yhtmyynti'];

			//haetaan rivien arvot
			$query = "	SELECT
						luokka,
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
						kate - kustannus_yht total
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi='$abcchar'
						$osastolisa
						$trylisa
						and luokka = '$luokkarow[luokka]'
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

					$l = $row["luokka"];
					
					// Lisätään rivi exceltiedostoon
					if(isset($workbook)) {
						$worksheet->write($excelrivi, 0,  "$ryhmanimet[$l]");
						$worksheet->write($excelrivi, 1,  "$row[tuoteno]");
						$worksheet->write($excelrivi, 2,  "$tuorow[toim_tuoteno]");
						$worksheet->write($excelrivi, 3,  asana('nimitys_',$row['tuoteno'],$tuorow['nimitys']));
						$worksheet->write($excelrivi, 4,  "$tuorow[tuotemerkki]");
						$worksheet->write($excelrivi, 5,  "$row[osasto]");
						$worksheet->write($excelrivi, 6,  "$row[try]");
						$worksheet->write($excelrivi, 7,  "$row[tulopvm]");
						$worksheet->write($excelrivi, 8,  sprintf('%.1f',$row["summa"]));
						$worksheet->write($excelrivi, 9,  sprintf('%.1f',$row["kate"]));
						$worksheet->write($excelrivi, 10, sprintf('%.1f',$row["katepros"]));
						$worksheet->write($excelrivi, 11, sprintf('%.1f',$row["kateosuus"]));
						$worksheet->write($excelrivi, 12, sprintf('%.1f',$row["vararvo"]));
						$worksheet->write($excelrivi, 13, sprintf('%.1f',$row["varaston_kiertonop"]));
						$worksheet->write($excelrivi, 14, sprintf('%.1f',$row["kpl"]));
						$worksheet->write($excelrivi, 15, sprintf('%.1f',$row["myyntierankpl"]));
						$worksheet->write($excelrivi, 16, sprintf('%.1f',$row["myyntieranarvo"]));
						$worksheet->write($excelrivi, 17, sprintf('%.0f',$row["rivia"]));
						$worksheet->write($excelrivi, 18, sprintf('%.0f',$row["puuterivia"]));
						$worksheet->write($excelrivi, 19, sprintf('%.1f',$row["palvelutaso"]));
						$worksheet->write($excelrivi, 20, sprintf('%.1f',$row["ostoerankpl"]));
						$worksheet->write($excelrivi, 21, sprintf('%.1f',$row["ostoeranarvo"]));
						$worksheet->write($excelrivi, 22, sprintf('%.0f',$row["osto_rivia"]));
						$worksheet->write($excelrivi, 23, sprintf('%.1f',$row["kustannus"]));
						$worksheet->write($excelrivi, 24, sprintf('%.1f',$row["kustannus_osto"]));
						$worksheet->write($excelrivi, 25, sprintf('%.1f',$row["kustannus_yht"]));
						$worksheet->write($excelrivi, 26, sprintf('%.1f',$row["total"]));
						$worksheet->write($excelrivi, 27, "$paikrow[paikka]");
						$worksheet->write($excelrivi, 28, sprintf('%.0f',$paikrow["saldo"]));
						$excelrivi++;
					}
				
					echo "$ryhmanimet[$l]\t";
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
