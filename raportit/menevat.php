<?php

	//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
	$useslave = 1;

	if (!isset($sakkl)) $sakkl = date("m");
	if (!isset($savvl)) $savvl = date("Y");
	if (!isset($sappl)) $sappl = date("d");

	if (isset($_POST["supertee"])) {
		if($_POST["supertee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("../inc/parametrit.inc");

	if (isset($supertee)) {
		if ($supertee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}

	echo "<font class='head'>".t("Maksettavat")." - $yhtiorow[nimi]</font><hr>";

	echo "<table>";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='NAYTA'>";
	echo "<tr><th>".t("N�yt� vain t�m� ytunnus").":</th><td valign='top'><input type='text' name='sytunnus' size ='15' value='$sytunnus'></td></tr>";
	echo "<tr><th>".t("N�yt� vain t�m� nimi").":</th><td valign='top'><input type='text' name='nimi' size ='15' value='$nimi'></td></tr>";
	echo "<tr><th>".t("N�yt� vain ne joilla maksettavaa on yli").":</th><td valign='top'><input type='text' name='yli' size ='15' value='$yli'></td></tr>";

	echo "<tr>
			<th>".t("N�yt� vain ne laskut jotka on p�iv�tty ennen").":</th>
			<td valign='top'><input type='text' name='sappl' value='$sappl' size='3'><input type='text' name='sakkl' value='$sakkl' size='3'><input type='text' name='savvl' value='$savvl' size='5'></td>
			</tr>";

	$sel1 = '';
	$sel2 = '';
	$sel3 = '';
	$sel4 = '';
	$sel5 = '';
	$sel6 = '';

	if ($grouppaus == '1,2') {
		$sel1 = "SELECTED";
	}
	elseif ($grouppaus == '2,3') {
		$sel2 = "SELECTED";
	}
	elseif ($grouppaus == '1,3') {
		$sel3 = "SELECTED";
	}
	elseif ($grouppaus == '1') {
		$sel4 = "SELECTED";
	}
	elseif ($grouppaus == '2') {
		$sel5 = "SELECTED";
	}
	elseif ($grouppaus == '3') {
		$sel6 = "SELECTED";
	}

	echo "<tr><th>".t("Summaustaso").":</th><td valign='top'><select name='grouppaus'>";
	echo "<option value = '1,2,3'>".t("Ytunnus, Nimi, Asiakastunnus")."</option>";
	echo "<option value = '1,2' $sel1>".t("Ytunnus, Nimi")."</option>";
	echo "<option value = '2,3' $sel2>".t("Nimi, Asiakastunnus")."</option>";
	echo "<option value = '1,3' $sel3>".t("Ytunnus, Asiakastunnus")."</option>";
	echo "<option value = '1'   $sel4>".t("Ytunnus")."</option>";
	echo "<option value = '2'   $sel5>".t("Nimi")."</option>";
	echo "<option value = '3'   $sel6>".t("Asiakastunnus")."</option>";
	echo "</select></td></tr>";


	$query = "	SELECT nimi, tunnus
                FROM valuu
             	WHERE yhtio = '$kukarow[yhtio]'
               	ORDER BY jarjestys";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>Valitse valuutta:</th><td><select name='savalkoodi'>";
	echo "<option value = ''>".t("Kaikki")."</option>";


	while ($vrow = mysql_fetch_array($vresult)) {
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
	echo "</select></td><td valign='top' class='back'><input type='submit' value='".t("N�yt�")."'></td><td valign='top' class='back'>".t("J�t� kaikki kent�t tyhj�ksi jos haluat listata kaikki saatavat").".</td></tr>";
	echo "</form>";
	echo "</table><br>";

	if ($tee == 'NAYTA') {

		$lisa = '';

		if ($nimi != '') {
			$lisa .= " and lasku.nimi like '%$nimi%' ";
		}
		if ($sytunnus != '') {
			$lisa .= " and lasku.ytunnus='$sytunnus' ";
		}
		if ($yli != 0) {
			$having = " HAVING ll >= $yli ";
		}
		else {
			$having = " HAVING ll != 0 ";
		}

		if ($grouppaus == '1,2') {
			$selecti = "lasku.ytunnus, lasku.nimi, group_concat(distinct lasku.liitostunnus) liitostunnus ";
		}
		elseif ($grouppaus == '2,3') {
			$selecti = "group_concat(distinct lasku.ytunnus separator '<br>') ytunnus, lasku.nimi, lasku.liitostunnus ";
		}
		elseif ($grouppaus == '1,3') {
			$selecti = "lasku.ytunnus, group_concat(distinct lasku.nimi separator '<br>') nimi, lasku.liitostunnus ";
		}
		elseif ($grouppaus == '1') {
			$selecti = "lasku.ytunnus, group_concat(distinct lasku.nimi separator '<br>') nimi, group_concat(distinct lasku.liitostunnus) liitostunnus ";
		}
		elseif ($grouppaus == '2') {
			$selecti = "group_concat(distinct lasku.ytunnus separator '<br>') ytunnus, lasku.nimi, group_concat(distinct lasku.liitostunnus) liitostunnus ";
		}
		elseif ($grouppaus == '3') {
			$selecti = "group_concat(distinct lasku.ytunnus separator '<br>') ytunnus, group_concat(distinct lasku.nimi separator '<br>') nimi, lasku.liitostunnus ";
		}
		else {
			$selecti = "lasku.ytunnus, lasku.nimi, lasku.liitostunnus ";
		}


		if ($savalkoodi != "") {
			$salisa = " and lasku.valkoodi='$savalkoodi' ";
		}


		if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
			$summalisa = "	sum(summa) ll,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) <= 0, summa, 0)) aa,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) >  0 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 15,  summa, 0)) aabb,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 15 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 30,  summa, 0)) bb,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 30 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 60,  summa, 0)) cc,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 60 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 90,  summa, 0)) dd,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 90 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 120, summa, 0)) ee,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 120,	summa, 0)) ff ";
		}
		else {
			$summalisa = "	sum(round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2)) ll,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) <= 0, round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) aa,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) >  0 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 15,  round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) aabb,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 15 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 30,  round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) bb,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 30 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 60,  round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) cc,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 60 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 90,  round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) dd,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 90 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 120, round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) ee,
							sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 120, round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2), 0)) ff ";
		}

		$query = "	SELECT
					$selecti,
					$summalisa
					FROM lasku use index (yhtio_tila_mapvm)
					WHERE tila	in ('H','Y','M','P','Q')
					AND mapvm = '0000-00-00'
					and lasku.tapvm <= '$savvl-$sakkl-$sappl'
					$lisa
					$salisa
					AND lasku.yhtio = '$kukarow[yhtio]'
					GROUP BY $grouppaus
					$having
					order by 1,2,3";
		$result = mysql_query($query) or pupe_error($query);

		$aay = 0;
		$aabby = 0;
		$bby = 0;
		$ccy = 0;
		$ddy = 0;
		$eey = 0;
		$ffy = 0;
		$kky = 0;
		$lly = 0;
		$ylikolkyt = 0;
		$rivilask = 0;

		if (mysql_num_rows($result) > 0) {

			if(include('Spreadsheet/Excel/Writer.php')) {

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

			if(isset($workbook)) {
				$excelsarake = 0;

				$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Alle 0 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("0-15 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("16-30 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("31-60 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("61-90 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("91-120 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("yli 121 pv"), $format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Yhteens�"), $format_bold);

				$excelsarake = 0;
				$excelrivi++;
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th align='right'>".t("Alle 0 pv")."</th>";
			echo "<th align='right'>".t("0-15 pv")."</th>";
			echo "<th align='right'>".t("16-30 pv")."</th>";
			echo "<th align='right'>".t("31-60 pv")."</th>";
			echo "<th align='right'>".t("61-90 pv")."</th>";
			echo "<th align='right'>".t("91-120 pv")."</th>";
			echo "<th align='right'>".t("yli 121 pv")."</th>";
			echo "<th align='right'>".t("Yhteens�")."</th>";
			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {

				if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
					$suorilisa = " sum(summa) summa ";
				}
				else {
					$suorilisa = " sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa ";
				}

				$query = "	SELECT
							$suorilisa
							FROM suoritus
							WHERE yhtio='$kukarow[yhtio]'
							and asiakas_tunnus in ($row[liitostunnus])
							and kohdpvm = '0000-00-00'";
				$suresult = mysql_query($query) or pupe_error($query);
				$surow = mysql_fetch_array($suresult);

				if (($ylilimiitin == '') or ($ylilimiitin == 'ON' and $row["ll"] > $asrow["luottoraja"] and $asrow["luottoraja"] != '')) {

					echo "<tr class='aktiivi'>";
					echo "<td valign='top'><a href='../raportit.php?toim=toimittajahaku&tee=A&ytunnus=$row[ytunnus]&alku=0&laji=M'>$row[ytunnus]</a></td>";
					echo "<td valign='top'>$row[nimi]</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["aa"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["aabb"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["bb"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["cc"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["dd"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["ee"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["ff"])."</td>";
					echo "<td valign='top' align='right'>".str_replace(".",",",$row["ll"])."</td>";
					echo "</tr>";

					if(isset($workbook)) {
						$excelsarake = 0;

						$worksheet->writeString($excelrivi, $excelsarake, str_replace("<br>","\n", $row["ytunnus"]));
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, str_replace("<br>","\n", $row["nimi"]));
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["aa"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["aabb"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["bb"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["cc"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["dd"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["ee"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["ff"]);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, $row["ll"]);
						$excelsarake++;

						$excelsarake = 0;
						$excelrivi++;
					}

					$aay 		+= $row["aa"];
					$aabby 		+= $row["aabb"];
					$bby 		+= $row["bb"];
					$ccy 		+= $row["cc"];
					$ddy 		+= $row["dd"];
					$eey 		+= $row["ee"];
					$ffy 		+= $row["ff"];
					$lly 		+= $row["ll"];
					$ylikolkyt	+= $row["cc"];
					$ylikolkyt 	+= $row["dd"];
					$ylikolkyt 	+= $row["ee"];
					$ylikolkyt 	+= $row["ff"];
					$rivilask++;
				}
			}

			if ($rivilask >= 1) {
				echo "<tr>";
				echo "<td valign='top' class='tumma' align='right' colspan='2'>".t("Yhteens�").":</th>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$aay))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$aabby))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$bby))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$ccy))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$ddy))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$eey))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$ffy))."</td>";
				echo "<td valign='top' class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$lly))."</td>";
				echo "<td valign='top' class='tumma'></td>";
				echo "</tr>";
			}

			echo "</table>";

			if(isset($workbook)) {

				// We need to explicitly close the workbook
				$workbook->close();

				echo "<br><table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='supertee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Maksettavat.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td valign='top' class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}

		}
		else {
			echo "<br><br>".t("Ei saatavia!")."<br>";
		}
	}

	require ("../inc/footer.inc");
?>