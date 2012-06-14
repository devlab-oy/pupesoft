<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
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

		echo "<br><br>";
		$tee = "TULOSTA";
	}

	if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {

		$muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl."/".$tuoteno;

		if ($toim == 'MYYNTI') {
			require ("inc/asiakashaku.inc");
		}
		if ($toim == 'OSTO') {
			require ("../inc/kevyt_toimittajahaku.inc");
		}
	}

	if ($tuoteno != '') {
		require ('inc/tuotehaku.inc');
	}


	//Etsi-kentt‰
	echo "<br><table><form method='post'>
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
		$pvm_select1 = "";
		$pvm_select2 = "SELECTED";
	}
	elseif ($toim == 'MYYNTI' and $pvmtapa == 'laskutettu') {
		$pvmtapa = "laskutettuaika";
		$pvm_select1 = "";
		$pvm_select2 = "SELECTED";
	}
	else {
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


	echo "	<tr><th>".t("Syˆt‰ tuotenumero").":</th>
			<td colspan='3'>";

	if (isset($tuoteno) and trim($ulos) != '') {
		echo $ulos;
	}
	else {
		echo "<input type='text' name='tuoteno' value='$tuoteno' size='20'>";
	}

	echo "</td></tr>";

	echo "<tr><th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";

			if ($toim == 'OSTO') {
				echo "</tr><tr><th>".t("Valitse p‰iv‰m‰‰r‰n tyyppi")."</th>
					<td colspan='3'><select name='pvmtapa'>
						<option value='laadittu' $pvm_select1>".t("Tilauksen laatimisp‰iv‰m‰‰r‰")."</option>
						<option value='toimaika' $pvm_select2>".t("Tilauksen toivottu toimitusp‰iv‰m‰‰r‰")."</option>
					</select></td>";
			}
			else {
				echo "</tr><tr><th>".t("Valitse p‰iv‰m‰‰r‰n tyyppi")."</th>
					<td colspan='3'><select name='pvmtapa'>
						<option value='laadittu' $pvm_select1>".t("Tilauksen laatimisp‰iv‰m‰‰r‰")."</option>
						<option value='laskutettu' $pvm_select2>".t("Tilauksen laskutusp‰iv‰m‰‰r‰")."</option>
					</select></td>";
			}

	echo "<td class='back'><input type='submit' value='".t("Etsi")."'></td></tr></form></table>";

	if ($ytunnus != '' or $tuoteno != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {

		if(@include('Spreadsheet/Excel/Writer.php')) {
			//keksit‰‰n failille joku varmasti uniikki nimi:
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

			$query_ale_lisa = generoi_alekentta('O');

			$ale_query_select_lisa = generoi_alekentta_select('erikseen', 'O');

			$query = "	SELECT
						tilausrivi.tunnus,
						lasku.tunnus tilaus,
			 			lasku.ytunnus,
						lasku.nimi,
						lasku.postitp,
						tilausrivi.tuoteno,
						round((tilausrivi.varattu+tilausrivi.kpl),4) m‰‰r‰,
						round((tilausrivi.varattu+tilausrivi.kpl)*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) ulkm‰‰r‰,
						round(tilausrivi.hinta*if(lasku.vienti_kurssi=0, 1, lasku.vienti_kurssi), '$yhtiorow[hintapyoristys]') hinta,
						{$ale_query_select_lisa}
						round((tilausrivi.varattu+tilausrivi.kpl)*tilausrivi.hinta*if(lasku.vienti_kurssi=0, 1, lasku.vienti_kurssi)*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
						lasku.toimaika,
						tilausrivi.laskutettuaika tuloutettu,
						lasku.tila, lasku.alatila
						FROM tilausrivi
						JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and lasku.tila IN ('O','K')
						and tilausrivi.tyyppi = 'O'
						and tilausrivi.$pvmtapa >='$vva-$kka-$ppa 00:00:00'
						and tilausrivi.$pvmtapa <='$vvl-$kkl-$ppl 23:59:59'";
		}
		else {

			$query_ale_lisa = generoi_alekentta('M');

			if ((int) $asiakasid > 0) {
				$asiakaslisa = "";
			}
			else {
				$asiakaslisa = "ytunnus, if(nimi!=toim_nimi and toim_nimi!='', concat(nimi,'<br>(',toim_nimi,')'), nimi) nimi, if(postitp!=toim_postitp and toim_postitp!='', concat(postitp,'<br>(',toim_postitp,')'), postitp) postitp, ";
			}

			if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
				$katelisa = " tilausrivi.kate, ";
			}
			else {
				$katelisa = "";
			}

			$ale_query_select_lisa = generoi_alekentta_select("erikseen", "M");

			$query = "	SELECT distinct
						tilausrivi.tunnus,
						tilausrivi.otunnus tilaus,
						lasku.laskunro,
						$asiakaslisa
						tilausrivi.tuoteno,
						tilausrivi.nimitys,
						(tilausrivi.kpl+tilausrivi.varattu) m‰‰r‰,
						tilausrivi.hinta,
						{$ale_query_select_lisa}
						if (tilausrivi.kpl!=0, tilausrivi.rivihinta, tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) rivihinta,
						tilausrivi.kate,
						lasku.toimaika,
						lasku.lahetepvm K‰sittelyyn,
						lasku.tila,
						lasku.alatila,
						lasku.tapvm,
						tilausrivi.tyyppi,
						tuote.kehahin,
						tuote.sarjanumeroseuranta,
						tilausrivi.var
						FROM tilausrivi
						JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
						JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and lasku.yhtio=tilausrivi.yhtio
						and lasku.tunnus=tilausrivi.otunnus
						and lasku.tila in ('L','N','U')
						and tilausrivi.tyyppi = 'L'
						and tilausrivi.$pvmtapa >='$vva-$kka-$ppa 00:00:00'
						and tilausrivi.$pvmtapa <='$vvl-$kkl-$ppl 23:59:59'
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

		if ($toim == 'OSTO') {
			$miinus = 2;
		}
		else {
			$miinus = 7;
		}

		if (mysql_num_rows($result) > 0) {

			echo "<br><table>";
			echo "<tr>";

			if ($toim == 'OSTO' and $pvmtapa == 'toimaika') {
				$pvmtapa_url = "&pvmtapa=toimaika";
			}

			$j = 0;

			for ($i=1; $i < mysql_num_fields($result)-$miinus; $i++) {

				echo "<th align='left'><a href='$PHP_SELF?tee=$tee&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&tuoteno=".urlencode($tuoteno)."&ytunnus=$ytunnus&asiakasid=$asiakasid&jarj=".mysql_field_name($result,$i)."$pvmtapa_url'>".t(mysql_field_name($result,$i))."</a></th>";

				if (isset($workbook)) {
					$worksheet->write($excelrivi, $j, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
				}

				$j++;

				if (mysql_field_name($result,$i) == 'kate') {
					echo "<th align='left'><a href='$PHP_SELF?tee=$tee&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&tuoteno=".urlencode($tuoteno)."&ytunnus=$ytunnus&asiakasid=$asiakasid&jarj=".mysql_field_name($result,$i)."$pvmtapa_url'>".t("Katepros")."</a></th>";

					if (isset($workbook)) {
						$worksheet->write($excelrivi, $j, ucfirst(t("Katepros")), $format_bold);
						$j++;
					}
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

			$kplsumma 		= 0;
			$rivihintasumma = 0;
			$kate_yht  		= 0;

			while ($row = mysql_fetch_array($result)) {

				$excelsarake = 0;

				$ero = "td";
				if ($tunnus == $row['tilaus']) $ero = "th";

				if ($row["var"] == "P") {
					$class = " class='spec' ";
				}
				elseif ($row["var"] == "J") {
					$class = " class='green' ";
				}
				else {
					$class = "";
				}

				echo "<tr class='aktiivi'>";

				for ($i=1; $i<mysql_num_fields($result)-$miinus; $i++) {

					if (mysql_field_name($result,$i) == 'kerattyaika' or mysql_field_name($result,$i) == 'toimaika' or mysql_field_name($result,$i) == 'tuloutettu' or mysql_field_name($result,$i) == 'K‰sittelyyn') {
						echo "<$ero valign='top' $class>".tv1dateconv($row[$i],"pitka")."</$ero>";
					}
					elseif (substr(mysql_field_name($result,$i), 0, 3) == 'ale' or mysql_field_name($result,$i) == 'm‰‰r‰') {
						if ($row[$i] == 0) {
							echo "<$ero valign='top' align='right' $class></$ero>";
						}
						else {
							echo "<$ero valign='top' align='right' $class>".(float) $row[$i]."</$ero>";
						}
					}
					elseif (mysql_field_name($result,$i) == 'tuoteno') {
						echo "<$ero valign='top' $class><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($row[$i])."'>$row[$i]</a></$ero>";
					}
					elseif (mysql_field_name($result,$i) == 'kate') {
						if ($row["var"] == "P") {
							echo "<$ero colspan='2' valign='top' nowrap $class>".t("PUUTE")."</$ero>";
						}
						elseif ($row["var"] == "J") {
							echo "<$ero colspan='2' valign='top' nowrap $class>".t("JT")."</$ero>";
						}
						else {
							// T‰n rivin kate
							$kate 		= 0;
							$kate_eur	= 0;

							if ($row["tapvm"] != '0000-00-00') {

								if ($row["m‰‰r‰"] == 0) {
									$kate = "";
									$kate_eur = 0;
								}
								elseif ($row["rivihinta"] != 0) {
									if ($row["kate"] < 0) {
										$kate = sprintf('%.2f', -1 * abs(100 * $row["kate"] / $row["rivihinta"]))."%";
									}
									else {
										$kate = sprintf('%.2f', abs(100 * $row["kate"] / $row["rivihinta"]))."%";
									}
								}
								elseif ($row["kate"] != 0) {
									$kate = "-100.00%";
								}

								$kate_eur  = $row["kate"];
								$kate_yht += $kate_eur;
							}
							elseif ($kukarow['extranet'] == '' and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U")) {
								if ($kpl > 0) {
									//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on myynti‰
									$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $row["tunnus"]);

									// Kate = Hinta - Ostohinta
									if ($row["rivihinta"] != 0) {
										$kate = sprintf('%.2f',100*($row["rivihinta"] - ($ostohinta * $kpl))/$row["rivihinta"])."%";
									}

									$kate_eur  = ($row["rivihinta"] - ($ostohinta * $kpl));
									$kate_yht += $kate_eur;
								}
								elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "O") {
									//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on OSTOA

									// Kate = 0
									$kate = "0%";
								}
								elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "") {
									//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on HYVITYSTƒ

									//T‰h‰n hyvitysriviin liitetyt sarjanumerot
									$query = "	SELECT sarjanumero, kaytetty
												FROM sarjanumeroseuranta
												WHERE yhtio 		= '$kukarow[yhtio]'
												and ostorivitunnus 	= '$row[tunnus]'";
									$sarjares = mysql_query($query) or pupe_error($query);

									$ostohinta = 0;

									while($sarjarow = mysql_fetch_array($sarjares)) {

										// Haetaan hyvitett‰vien myyntirivien kautta alkuper‰iset ostorivit
										$query  = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
													FROM sarjanumeroseuranta
													JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
													WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
													and sarjanumeroseuranta.tuoteno 	= '$row[tuoteno]'
													and sarjanumeroseuranta.sarjanumero = '$sarjarow[sarjanumero]'
													and sarjanumeroseuranta.kaytetty 	= '$sarjarow[kaytetty]'
													and sarjanumeroseuranta.myyntirivitunnus > 0
													and sarjanumeroseuranta.ostorivitunnus   > 0
													ORDER BY sarjanumeroseuranta.tunnus
													LIMIT 1";
										$sarjares1 = mysql_query($query) or pupe_error($query);
										$sarjarow1 = mysql_fetch_array($sarjares1);

										$ostohinta += $sarjarow1["ostohinta"];
									}

									// Kate = Hinta - Alkuper‰inen ostohinta
									if ($row["rivihinta"] != 0) {
										$kate = sprintf('%.2f',100 * ($row["rivihinta"]*-1 - $ostohinta)/$row["rivihinta"])."%";
									}
									else {
										$kate = "100.00%";
									}

									$kate_eur  = ($row["rivihinta"]*-1 - $ostohinta);
									$kate_yht += $kate_eur;
								}
								else {
									$kate = "N/A";
								}
							}
							elseif ($kukarow['extranet'] == '') {

								if ($row["rivihinta"] != 0) {
									$kate = sprintf('%.2f',100*($row["rivihinta"] - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"]+$row['m‰‰r‰'])))/$row["rivihinta"])."%";
								}
								elseif (kehahin($row["tuoteno"]) != 0) {
									$kate = "-100.00%";
								}

								$kate_eur  = ($row["rivihinta"] - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"]+$row['m‰‰r‰'])));
								$kate_yht += $kate_eur;
							}

							$row[$i] = $kate;

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $kate_eur, $format_num);
								$excelsarake++;
							}

							echo "<$ero align='right' valign='top' nowrap $class>".sprintf("%.2f", $kate_eur)."</$ero>";
							echo "<$ero align='right' valign='top' nowrap $class>$kate</$ero>";
						}
					}
					elseif (mysql_field_name($result,$i) == 'hinta' or mysql_field_name($result,$i) == 'rivihinta') {
						echo "<$ero valign='top' align='right' nowrap $class>".sprintf("%.2f", $row[$i])."</$ero>";
					}
					else {
						echo "<$ero valign='top' $class>$row[$i]</td>";
					}

					if (isset($workbook)) {
						if (is_numeric($row[$i]) and mysql_field_name($result,$i) != 'ytunnus') {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row[$i], $format_num);
						}
						else {
							$worksheet->write($excelrivi, $excelsarake, $row[$i]);
						}
						$excelsarake++;
					}
				}

				if ($row["var"] != "P" and $row["var"] != "J") {
					$kplsumma += $row["m‰‰r‰"];
					$rivihintasumma += $row["rivihinta"];
				}

				if ($toim != "OSTO") {
					$laskutyyppi= $row["tila"];
					$alatila	= $row["alatila"];

					//tehd‰‰n selv‰kielinen tila/alatila
					require "../inc/laskutyyppi.inc";

					echo "<$ero valign='top' $class>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, t("$laskutyyppi")." ".t("$alatila"));
					}
				}

				echo "<form method='post'><td class='back' valign='top'>
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
						<input type='submit' value='".t("N‰yt‰ tilaus")."'></td></form>";

				echo "</tr>";

				if (isset($workbook)) {
					$excelrivi++;
				}
			}

			if ($toim == "OSTO") {
				$csp = 4;
			}
			else {
				if ($asiakaslisa != "") {
					$csp = 6;
				}
				else {
					$csp = 3;
				}
			}

			$csp2 = 2;

			$loopattava_maara = $toim == 'OSTO' ? 1 : $yhtiorow['myynnin_alekentat'];

			for ($alepostfix = 1; $alepostfix <= $loopattava_maara; $alepostfix++) {
				if ($alepostfix > 1) {
					$csp2++;
				}
			}

			echo "<tr>
					<td colspan='$csp' class='back'></td>
					<td align='right' class='back'>".t("Yhteens‰").":</td>
					<td align='right' class='spec'>".(float) $kplsumma."</td>
					<td colspan='{$csp2}' align='right' class='back'></td>
					<td align='right' class='spec'>".sprintf('%01.2f', $rivihintasumma)."</td>";

			if ($toim != "OSTO" and $kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

				if ($kate_yht < 0) {
					$ykate = @round(-1 * abs($kate_yht / $rivihintasumma * 100), 2);
				}
				else {
					$ykate = @round(abs($kate_yht / $rivihintasumma * 100), 2);
				}

				echo "<td align='right' class='spec'>".sprintf('%.2f',$kate_yht)."</td><td class='spec' align='right' nowrap>".sprintf('%.2f',$ykate)."%</td>";
			}


			echo "</tr>";
			echo "</table>";

			if (isset($workbook)) {
				if ($toim == "OSTO") {
					$worksheet->writeFormula($excelrivi, 5, "=sum(F2:F$excelrivi)");
					$worksheet->write($excelrivi, 6, "");
					$worksheet->writeFormula($excelrivi, 7, "=sum(H2:H$excelrivi)");
					$worksheet->writeFormula($excelrivi, 8, "=sum(I2:I$excelrivi)");
				}
				else {

					if ($asiakaslisa != "") {
						$worksheet->writeFormula($excelrivi, 7, "=sum(H2:H$excelrivi)");
						$worksheet->writeFormula($excelrivi, 10, "=sum(K2:K$excelrivi)");

						if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

							$worksheet->write($excelrivi, 11, $kate_yht);
							$worksheet->write($excelrivi, 12, $ykate);
						}
					}
					else {
						$worksheet->writeFormula($excelrivi, 4, "=sum(E2:E$excelrivi)");
						$worksheet->writeFormula($excelrivi, 7, "=sum(H2:H$excelrivi)");

						if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

							$worksheet->write($excelrivi, 8, $kate_yht);
							$worksheet->write($excelrivi, 9, $ykate);
						}
					}
				}

				$workbook->close();

				echo "<br><table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<form method='post' class='multisubmit'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";

				if($toim == "MYYNTI") {
					echo "<input type='hidden' name='kaunisnimi' value='Asiakkaan_tuoteostot-$ytunnus.xls'>";
				}
				else {
					echo "<input type='hidden' name='kaunisnimi' value='Toimittajalta_tilatut-$ytunnus.xls'>";
				}

				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
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
