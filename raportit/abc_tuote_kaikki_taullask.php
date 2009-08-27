<?php

	echo "<font class='head'>".t("ABC-Analyysi‰: ABC-pitk‰listaus")."<hr></font>";

	if ($toim == "kulutus") {
		$myykusana = t("Kulutus");
	}
	else {
		$myykusana = t("Myynti");
	}

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

	// piirrell‰‰n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='aja' value='AJA'>";
	echo "<input type='hidden' name='tee' value='PITKALISTA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	// Monivalintalaatikot (osasto, try tuotemerkki...)
	// M‰‰ritell‰‰n mitk‰ latikot halutaan mukaan
	$lisa  = "";
	$ulisa = "";
	$mulselprefix = "abc_aputaulu";
	$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "TUOTEMYYJA", "TUOTEOSTAJA");

	require ("../tilauskasittely/monivalintalaatikot.inc");

	echo "<br>";
	echo "<table style='display:inline;'>";
	echo "<tr>";
	echo "<th>".t("Valitse luokka").":</th>";
	echo "<td><select name='luokka'>";
	echo "<option value=''>".t("Valitse luokka")."</option>";

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

	echo "<td><input type='checkbox' name='paikoittain' value='JOO' $sel></td>";
	echo "<td class='back'><input type='submit' name='ajoon' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</form>";
	echo "</table><br>";

	if ($aja == "AJA" and isset($ajoon)) {

		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksit‰‰n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$excelrivi = 0;

			$worksheet->writeString($excelrivi, 0,  t("ABC"));
			$worksheet->writeString($excelrivi, 1,  t("ABC osaston luokka"));
			$worksheet->writeString($excelrivi, 2,  t("ABC tuoteryhm‰n luokka"));
			$worksheet->writeString($excelrivi, 3,  t("Tuoteno"));
			$worksheet->writeString($excelrivi, 4,  t("Toim_tuoteno"));
			$worksheet->writeString($excelrivi, 5,  t("Nimitys"));
			$worksheet->writeString($excelrivi, 6,  t("Merkki"));
			$worksheet->writeString($excelrivi, 7,  t("Osasto"));
			$worksheet->writeString($excelrivi, 8,  t("Try"));
			$worksheet->writeString($excelrivi, 9,  t("Tulopvm"));
			$worksheet->writeString($excelrivi, 10, $myykusana.$yhtiorow["valkoodi"]);
			$worksheet->writeString($excelrivi, 11, t("Kate"));
			$worksheet->writeString($excelrivi, 12, t("Kate%"));
			$worksheet->writeString($excelrivi, 13, t("Kateosuus"));
			$worksheet->writeString($excelrivi, 14, t("Vararvo"));
			$worksheet->writeString($excelrivi, 15, t("Kierto"));
			$worksheet->writeString($excelrivi, 16, $myykusana.t("m‰‰r‰"));
			$worksheet->writeString($excelrivi, 17, $myykusana.t("er‰").t("m‰‰r‰"));
			$worksheet->writeString($excelrivi, 18, $myykusana.t("er‰").$yhtiorow["valkoodi"]);
			$worksheet->writeString($excelrivi, 19, $myykusana.t("rivit"));
			$worksheet->writeString($excelrivi, 20, t("Puuterivit"));
			$worksheet->writeString($excelrivi, 21, t("Palvelutaso"));
			$worksheet->writeString($excelrivi, 22, t("Ostoer‰").t("m‰‰r‰"));
			$worksheet->writeString($excelrivi, 23, t("Ostoer‰").$yhtiorow["valkoodi"]);
			$worksheet->writeString($excelrivi, 24, t("Ostorivit"));
			$worksheet->writeString($excelrivi, 25, t("KustannusMyynti"));
			$worksheet->writeString($excelrivi, 26, t("KustannusOsto"));
			$worksheet->writeString($excelrivi, 27, t("KustannusYht"));
			$worksheet->writeString($excelrivi, 28, t("Kate-Kustannus"));
			$worksheet->writeString($excelrivi, 29, t("Tuotepaikka"));
			$worksheet->writeString($excelrivi, 30, t("Saldo"));
			$worksheet->writeString($excelrivi, 31, t("Myyj‰"));
			$worksheet->writeString($excelrivi, 32, t("Ostaja"));
			$worksheet->writeString($excelrivi, 33, t("Malli"));
			$worksheet->writeString($excelrivi, 34, t("Mallitarkenne"));
			$worksheet->writeString($excelrivi, 35, t("Saapumispvm"));
			$excelrivi++;
		}

		/*
		echo "<pre>";
		echo t("ABC")."\t";
		echo t("ABC osaston luokka")."\t";
		echo t("ABC tuoteryhm‰n luokka")."\t";
		echo t("Tuoteno")."\t";
		echo t("Toim_tuoteno")."\t";
		echo t("Nimitys")."\t";
		echo t("Merkki")."\t";
		echo t("Osasto")."\t";
		echo t("Try")."\t";
		echo t("Tulopvm")."\t";
		echo $myykusana.$yhtiorow["valkoodi"]."\t";
		echo t("Kate")."\t";
		echo t("Kate%")."\t";
		echo t("Kateosuus")."\t";
		echo t("Vararvo")."\t";
		echo t("Kierto")."\t";
		echo $myykusana.t("m‰‰r‰")."\t";
		echo $myykusana.t("er‰").t("m‰‰r‰")."\t";
		echo $myykusana.t("er‰").$yhtiorow["valkoodi"]."\t";
		echo $myykusana.t("rivit")."\t";
		echo t("Puuterivit")."\t";
		echo t("Palvelutaso")."\t";
		echo t("Ostoer‰").t("m‰‰r‰")."\t";
		echo t("Ostoer‰").$yhtiorow["valkoodi"]."\t";
		echo t("Ostorivit")."\t";
		echo t("KustannusMyynti")."\t";
		echo t("KustannusOsto")."\t";
		echo t("KustannusYht")."\t";
		echo t("Kate-Kustannus")."\t";
		echo t("Tuotepaikka")."\t";
		echo t("Saldo")."\t";
		echo t("Myyj‰")."\t";
		echo t("Ostaja")."\t";
		echo t("Malli")."\t";
		echo t("Mallitarkenne")."\t";
		echo t("Saapumispvm")."\t";
		echo "\n";
		*/

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
		
		if ($luokka != "") {
			$luokkalisa = " and luokka = '$luokka' ";
		}
		else {
			$luokkalisa =  "";
		}		

		$query = "	SELECT
					distinct luokka
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi  = '$abcchar'
					$luokkalisa
					ORDER BY luokka";
		$luokkares = mysql_query($query) or pupe_error($query);

		while ($luokkarow = mysql_fetch_array($luokkares)) {

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
				$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.tuotemerkki, group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno
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
						$worksheet->writeString($excelrivi, 0,  $ryhmanimet[$row["luokka"]]);
						$worksheet->writeString($excelrivi, 1,  $ryhmanimet[$row["luokka_osasto"]]);
						$worksheet->writeString($excelrivi, 2,  $ryhmanimet[$row["luokka_try"]]);
						$worksheet->writeString($excelrivi, 3,  "$row[tuoteno]");
						$worksheet->writeString($excelrivi, 4,  "$tuorow[toim_tuoteno]");
						$worksheet->writeString($excelrivi, 5,  t_tuotteen_avainsanat($tuorow, 'nimitys'));
						$worksheet->writeString($excelrivi, 6,  "$tuorow[tuotemerkki]");
						$worksheet->writeString($excelrivi, 7,  "$row[osasto]");
						$worksheet->writeString($excelrivi, 8,  "$row[try]");
						$worksheet->write($excelrivi, 9,  tv1dateconv($row["tulopvm"]));
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
						$worksheet->writeString($excelrivi, 29, "$paikrow[paikka]");
						$worksheet->write($excelrivi, 30, sprintf('%.0f',$paikrow["saldo"]));
						$worksheet->writeString($excelrivi, 31, "$myyjarow[nimi]");
						$worksheet->writeString($excelrivi, 32, "$ostajarow[nimi]");
						$worksheet->writeString($excelrivi, 33, "$row[malli]");
						$worksheet->writeString($excelrivi, 34, "$row[mallitarkenne]");
						$worksheet->write($excelrivi, 35, tv1dateconv($row["saapumispvm"]));
						$excelrivi++;
					}

					/*
					echo $ryhmanimet[$row["luokka"]]."\t";
					echo $ryhmanimet[$row["luokka_osasto"]]."\t";
					echo $ryhmanimet[$row["luokka_try"]]."\t";
					echo "$row[tuoteno]\t";
					echo "$tuorow[toim_tuoteno]\t";
					echo t_tuotteen_avainsanat($tuorow, 'nimitys')."\t";
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
					*/
				}
			}
		}

		//echo "</pre>";

		if(isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<br><br><table>";
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
