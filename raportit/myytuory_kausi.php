<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}
	
	$useslave = 1;
	require("../inc/parametrit.inc");

	//Ja tässä laitetaan ne takas
	$sqlhaku = $sqlapu;

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);	
			exit;
		}
	}
	else {
	
		echo "<font class='head'>".t("Myynnit tuoteryhmittäin")."</font><hr>";

		flush();


		if ($myytuorytee != '') {
			$query = "	SELECT distinct osasto, try
						FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
						WHERE yhtio='$kukarow[yhtio]' and tyyppi='L' and
							laskutettuaika >= '$vva-$kka-$ppa' and
							laskutettuaika <= '$vvl-$kkl-$ppl'
						ORDER BY osasto, try";
			$result = mysql_query($query) or pupe_error($query);

			$ulos  = "Os\t";
			$ulos .= "Try\t";
			$ulos .= "Myynti $ppa.$kka.$vva - $ppl.$kkl.$vvl\t";
			$ulos .= "Kate\t";
			$ulos .= "K% \t";
			$ulos .= "Kpl\t";
			$ulos .= "Varasto\t";
			$ulos .= "\n";

			$kate30=0;
			$myyn30=0;
			$varTOT=0;

			$yhtkate30=0;
			$yhtmyyn30=0;
			$yhtvarTOT=0;

			$edosasto = "X";
			
			require_once ('inc/ProgressBar.class.php');
			$bar = new ProgressBar();
			$elements = mysql_num_rows($result); // total number of elements to process
			$bar->initialize($elements); // print the empty bar

			while ($trow = mysql_fetch_array($result)) {
				
				$bar->increase();

				$query = "	SELECT
							osasto,
							try,
							sum(rivihinta)	summa30,
							sum(kate)  		kate30,
							sum(kpl)  		kpl30
							FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
							WHERE yhtio='$kukarow[yhtio]' and tyyppi='L'
							and osasto='$trow[osasto]' and try='$trow[try]'
							and laskutettuaika >= '$vva-$kka-$ppa'
							and laskutettuaika <= '$vvl-$kkl-$ppl'
							group by 1,2";

				$eresult = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($eresult);

				//varaston arvo
				$query = "	SELECT sum(tuotepaikat.saldo*if(epakurantti75pvm='0000-00-00', if(epakurantti50pvm='0000-00-00', if(epakurantti25pvm='0000-00-00', kehahin, kehahin*0.75), kehahin*0.5), kehahin*0.25)) varasto
							FROM tuotepaikat, tuote
							WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
							and tuote.yhtio 		  = tuotepaikat.yhtio
							and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
							and tuote.osasto 		  = '$trow[osasto]'
							and tuote.try 			  = '$trow[try]'
							and tuote.ei_saldoa 	  = ''
							and tuote.epakurantti100pvm = '0000-00-00'";
				$result4 = mysql_query($query) or pupe_error($query);
				$rowarvo = mysql_fetch_array($result4);

				$varastonarvo = $rowarvo["varasto"];

				// jos osasto vaihtuu ja ei olla ekalla rundilla, tulostetaan yhteensärivi
				if ($trow["osasto"] != $edosasto and $edosasto != 'X') {

					// jos ei haluta turhia ryhmiä ja kaikki summat on nollaa ei tulosteta
					// tai jos ei haluta yhteensärivejä ollenkaan
					if (($turhatpois != 1 or $myyn30 != 0 or $kate30 != 0 or $varTOT != 0) and ($eiyhteensa != 1)) {

						if ($myyn30 > 0)
							$kate30pros = sprintf("%.02f",round($kate30/$myyn30*100,2));
						else
							$kate30pros="0.00";

						$ulos .= "Osasto $edosasto yhteensä:\t\t";
						$ulos .= "$myyn30\t";
						$ulos .= "$kate30\t";
						$ulos .= "$kate30pros\t";
						$ulos .= "$kpl30\t";
						$ulos .= "$varTOT\t";
						$ulos .= "\n";
						$ulos .= "\n";
					}

					// nollataan muuttujat
					$kate30=0;
					$myyn30=0;
					$varTOT=0;
					$kpl30 =0;;
				}

				$kate30+=$row["kate30"];
				$myyn30+=$row["summa30"];
				$kpl30 +=$row["kpl30"];
				$varTOT+=$varastonarvo;

				$yhtkate30+=$row["kate30"];
				$yhtmyyn30+=$row["summa30"];
				$yhtkpl30 +=$row["kpl30"];
				$yhtvarTOT+=$varastonarvo;

				if ($row["summa30"] > 0)
					$kate30pros = sprintf("%.02f",round($row["kate30"]/$row["summa30"]*100,2));
				else
					$kate30pros = "0.00";

				// jos ei haluta turhia tuoteryhmiä ja kaikki summat on nollaa niin ei tulosteta riviä
				if ($turhatpois != 1 or $row['summa30'] != 0 or $row['kate30'] != 0 or $varastonarvo != 0) {
					$ulos .= "$trow[osasto]\t$trow[try]\t";
					$ulos .= "$row[summa30]\t";
					$ulos .= "$row[kate30]\t";
					$ulos .= "$kate30pros\t";
					$ulos .= "$row[kpl30]\t";
					$ulos .= "$varastonarvo\t";
					$ulos .= "\n";
				}

				$edosasto = $trow["osasto"];

			} // end while

			// vielä vikan ryhmän yhteensärivit jos ne halutaan
			if ($eiyhteensa != 1) {

				// jos ei haluta turhia ryhmiä ja kaikki summat on nollaa ei tulosteta
				if ($turhatpois != 1 or $myyn30 != 0 or $kate30 != 0 or $varTOT != 0) {

					if ($myyn30 > 0)
						$kate30pros = sprintf("%.02f",round($kate30/$myyn30*100,2));
					else
						$kate30pros="0.00";

					$ulos .= "Osasto $edosasto yhteensä:\t\t";
					$ulos .= "$myyn30\t";
					$ulos .= "$kate30\t";
					$ulos .= "$kate30pros\t";
					$ulos .= "$kpl30\t";
					$ulos .= "$varTOT\t";
					$ulos .= "\n";
					$ulos .= "\n";
				}

				if ($yhtmyyn30 > 0)
					$yhtkatepros = sprintf("%.02f",round($yhtkate30/$yhtmyyn30*100,2));
				else
					$yhtkatepros="0.00";

				///* Kaikkiyhteensä *///
				$ulos .= "Kaikki yhteensä:\t\t";
				$ulos .= "$yhtmyyn30\t";
				$ulos .= "$yhtkate30\t";
				$ulos .= "$yhtkatepros\t";
				$ulos .= "$yhtkpl30\t";
				$ulos .= "$yhtvarTOT\t";
				$ulos .= "\n";
			}

			if(include('Spreadsheet/Excel/Writer.php')) {

				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet = $workbook->addWorksheet('Myynnit tuoteryhmittäin');

				$format_bold = $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}

			if(isset($workbook)) {
				$rivit = explode("\n", $ulos);

				$rivi = explode("\t", $rivit[0]);
				for ($i=0; $i < count($rivi); $i++) $worksheet->write($excelrivi, $i, $rivi[$i], $format_bold);
				$excelrivi++;

				for($j = 1; $j<count($rivit); $j++) {
					$rivi = explode("\t", $rivit[$j]);
					for ($i=0; $i < count($rivi); $i++) $worksheet->write($excelrivi, $i, $rivi[$i]);
					$excelrivi++;
				}
			}

			if(isset($workbook)) {

				// We need to explicitly close the workbook
				$workbook->close();

				echo "<br><table>";
				echo "<tr><th>".t("Tallenna raportti (xls)").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Myynnit_tuoteryhmittain_$pp$kk$vv.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
		
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$txtnimi = md5(uniqid(mt_rand(), true)).".txt";
		
			file_put_contents("/tmp/$txtnimi", $ulos);

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (txt)").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Myynnit_tuoteryhmittain_$pp$kk$vv.txt'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		
			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (csv)").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Myynnit_tuoteryhmittain_$pp$kk$vv.csv'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}


		//Käyttöliittymä
		echo "<br>";
		echo "<table><form method='post' action='$PHP_SELF'>";

		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		echo "<input type='hidden' name='myytuorytee' value='kaikki'>";
		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";

		if ($turhatpois == 1)
			$chk = "CHECKED";

		echo "<tr><th>".t("Piilota turhat tuoteryhmät")."</th>
				<td><input type='checkbox' name='turhatpois' value='1' $chk></td></tr>";
		if ($eiyhteensa == 1)
			$chk2 = "CHECKED";

		echo "<tr><th>".t("Piilota yhteensärivit")."</th>
				<td><input type='checkbox' name='eiyhteensa' value='1' $chk2></td>";

		echo "<td class='back' colspan='2'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

		require ("../inc/footer.inc");
	}
?>
