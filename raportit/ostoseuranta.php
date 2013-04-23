<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "ostoseuranta.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Ostoseuranta")."</font><hr>";

		if (!aja_kysely()) {
			unset($_POST);
		}

		//* Tämä skripti käyttää slave-tietokantapalvelinta *//
		$useslave = 1;
		require ("inc/connect.inc");

		if (count($_POST) > 0) {
			if (isset($muutparametrit)) {
				foreach (explode("##", $muutparametrit) as $muutparametri) {
					list($a, $b) = explode("=", $muutparametri);


					if (strpos($a, "[") !== FALSE) {
						$i = substr($a, strpos($a, "[")+1, strpos($a, "]")-(strpos($a, "[")+1));
						$a = substr($a, 0, strpos($a, "["));

						${$a}[$i] = $b;
					}
					else {
						${$a} = $b;
					}
				}
			}

			// tutkaillaan saadut muuttujat
			$toimittaja = trim($toimittaja);

			// hehe, näin on helpompi verrata päivämääriä
			$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
			$result = mysql_query($query) or pupe_error($query);
			$row    = mysql_fetch_array($result);

			if ($row["ero"] > 365 and $ajotapa != 'tilausauki') {
				echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
				$tee = "";
			}

			// jos on joku toimittajajuttu valittuna, niin ei saa valita ku yhen yrityksen
			if ($toimittaja != "" or $mukaan == "toimittaja") {
				if (count($yhtiot) != 1) {
					echo "<font class='error'>".t("Toimittajahauissa voi valita vain yhden yrityksen")."!</font><br>";
					$tee = "";
				}
			}

			// jos ei ole mitään yritystä valittuna ei tehdä mitään
			if (count($yhtiot) == 0) {
				$tee = "";
			}
			else {
				$yhtio  = "";
				foreach ($yhtiot as $apukala) {
					$yhtio .= "'$apukala',";
				}
				$yhtio = substr($yhtio,0,-1);
			}

			// jos joku päiväkenttä on tyhjää ei tehdä mitään
			if ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "") {
				$tee = "";
			}

			if ($tee == 'go' and $toimittaja != '') {
				$muutparametrit = "";

				foreach ($_POST as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $a => $b) {
							$muutparametrit .= $key."[".$a."]=".$b."##";
						}
					}
					else {
						$muutparametrit .= $key."=".$value."##";
					}
				}
			}

			if ($tee == 'go' and $toimittaja != '') {
				$ytunnus = $toimittaja;

				require("../inc/kevyt_toimittajahaku.inc");

				if ($ytunnus != '') {
					$toimittaja = $ytunnus;
					$ytunnus = '';
				}
				else {
					$tee 			= "";
					$toimittajaid 	= "";
				}
			}

			if ($tee == 'go') {

				// no hacking, please.
				$lisa   = "";
				$query  = "";
				$group  = "";
				$order  = "";
				$select = "";
				$gluku  = 0;

				// näitä käytetään queryssä
				$sel_osasto = "";
				$sel_tuoteryhma = "";

				$apu = array();

				if (count($yhtiot) > 1) {
					if ($group!="") $group .= ",lasku.yhtio";
					else $group .= "lasku.yhtio";
					$select .= "lasku.yhtio yhtio, ";
					$order  .= "lasku.yhtio,";
					$gluku++;
				}

				foreach ($jarjestys as $ind => $arvo) {
					if (trim($arvo) != "") $apu[] = $arvo;
				}

				if (count($apu) == 0) {
					ksort($jarjestys);
				}
				else {
					asort($jarjestys);
				}

				$apu = array();

				foreach ($jarjestys as $i => $arvo) {
					if ($ruksit[$i] != "") {
						$apu[$i] = $ruksit[$i];
					}
				}

				foreach ($apu as $i => $mukaan) {

					if ($mukaan == "osasto") {
						if ($group!="") $group .= ",tuote.osasto";
						else $group .= "tuote.osasto";
						$select .= "tuote.osasto tuos, ";
						$order  .= "tuote.osasto,";
						$gluku++;
					}

					if ($mukaan == "tuoteryhma") {
						if ($group!="") $group .= ",tuote.try";
						else $group .= "tuote.try";
						$select .= "tuote.try 'tuoteryhmä', ";
						$order  .= "tuote.try,";
						$gluku++;
					}

					if ($mukaan == "tuote") {
						if ($nimitykset == "") {
							if ($group!="") $group .= ",tuote.tuoteno";
							else $group  .= "tuote.tuoteno";
							$select .= "tuote.tuoteno tuoteno, ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}
						else {
							if ($group!="") $group .= ",tuote.tuoteno, tuote.nimitys";
							else $group  .= "tuote.tuoteno, tuote.nimitys";
							$select .= "tuote.tuoteno tuoteno, tuote.nimitys nimitys, ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.tuoteno='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "tuoteostaja") {
						if ($group!="") $group .= ",tuote.ostajanro";
						else $group  .= "tuote.ostajanro";
						$select .= "tuote.ostajanro tuoteostaja, ";
						$order  .= "tuote.ostajanro,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.ostajanro='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "merkki") {
						if ($group!="") $group .= ",tuote.tuotemerkki";
						else $group  .= "tuote.tuotemerkki";
						$select .= "tuote.tuotemerkki merkki, ";
						$order  .= "tuote.tuotemerkki,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.tuotemerkki='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "toimittaja") {
						if ($group!="") $group .= ",ytunnus";
						else $group  .= "ytunnus";
						$select .= "lasku.ytunnus, group_concat(distinct lasku.nimi SEPARATOR '<br>') nimi,";
						$order  .= "ytunnus, nimi,";
						$gluku++;
					}
				}

				if ($order != "") {
					$order = substr($order,0,-1);
				}
				else {
					$order = "1";
				}

				if ($tilrivikomm != "") {
					if ($group!="") $group .= ",tilausrivi.tunnus";
					else $group  .= "tilausrivi.tunnus";
					$select .= "tilausrivi.kommentti, ";
					$gluku++;
				}

				if (is_array($mul_osasto) and count($mul_osasto) > 0) {
					$sel_osasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_osasto))."')";
					$lisa .= " and tuote.osasto in $sel_osasto ";
				}

				if (is_array($mul_try) and count($mul_try) > 0) {
					$sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_try))."')";
					$lisa .= " and tuote.try in $sel_tuoteryhma ";
				}

				if ($toimittajaid != "") {
					$lisa .= " and lasku.liitostunnus = '$toimittajaid' ";
				}

				$vvaa = $vva - '1';
				$vvll = $vvl - '1';

				// Jos ei olla valittu mitään
				if ($group == "") {
					$select = "tuote.yhtio, ";
					$group = "lasku.yhtio";
				}

				// Kumpaa päivämäärää käytetään
				if (strpos($ajotapa, "mapvm") !== FALSE) {
					$pvmvar = " lasku.mapvm ";
				}
				else {
					$pvmvar = " tilausrivi.laskutettuaika ";
				}

				# Ajetaanko kuusausittain
				if ($kuukausittain == "ALLEKKAIN") {
					$select = " substring($pvmvar, 6, 2) kuukausi,".$select;
					$group = " kuukausi, ".$group;
					$order = " kuukausi, ".$order;
					$gluku++;
				}

				$query_ale_lisa = generoi_alekentta('O');

				// Tehdään query
				$query = "SELECT $select";

				// Katotaan mistä kohtaa queryä alkaa varsinaiset numerosarakkeet (HUOM: toi ', ' pilkkuspace erottaa sarakket toisistaan)
				if ($kuukausittain == "SARAKE") {
					$MONTH_ARRAY  	= array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

					$startmonth	= date("Ymd",mktime(0, 0, 0, $kka, 1,  $vva));
					$endmonth 	= date("Ymd",mktime(0, 0, 0, $kkl, 1,  $vvl));

					for ($i = $startmonth;  $i <= $endmonth;) {

						$alku  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));
						$loppu = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)));

						$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));
						$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)-1));

						//Osto
						$query .= " sum(if($pvmvar >= '$alku'  and $pvmvar <= '$loppu', tilausrivi.hinta*lasku.vienti_kurssi*tilausrivi.kpl*{$query_ale_lisa},0)) '".substr($MONTH_ARRAY[(substr($i,4,2)*1)],0,3)." ".substr($i,0,4)." ".t("Ostot")."', ";

						//Ostoed
						if ($piiloed == "") {
							$query .= " sum(if($pvmvar >= '$alku_ed'  and $pvmvar <= '$loppu_ed', tilausrivi.hinta*lasku.vienti_kurssi*tilausrivi.kpl*{$query_ale_lisa},0)) '".substr($MONTH_ARRAY[(substr($i,4,2)*1)],0,3)." ".(substr($i,0,4)-1)." ".t("Ostot")."', ";
						}

						if ($piilota_kappaleet == "") {
							$query .= "	sum(if($pvmvar >= '$alku'  and $pvmvar <= '$loppu', tilausrivi.kpl,0)) '".substr($MONTH_ARRAY[(substr($i,4,2)*1)],0,3)." ".substr($i,0,4)." ".t("Ostokpl")."', ";

							//KPLED
							if ($piiloed == "") {
								$query .= "	sum(if($pvmvar >= '$alku_ed' and $pvmvar <= '$loppu_ed',tilausrivi.kpl,0)) '".substr($MONTH_ARRAY[(substr($i,4,2)*1)],0,3)." ".(substr($i,0,4)-1)." ".t("Ostokpl")."', ";
							}
						}

						$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1,  substr($i,0,4)));
					}

					// Vika pilkku pois
					$query = substr($query, 0 , -2);
				}
				else {
					//Osto
					$query .= " sum(if($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl', tilausrivi.hinta*lasku.vienti_kurssi*tilausrivi.kpl*{$query_ale_lisa},0)) ostonyt, ";

					//Ostoed
					if ($piiloed == "") {
						$query .= " sum(if($pvmvar >= '$vvaa-$kka-$ppa'  and $pvmvar <= '$vvll-$kkl-$ppl', tilausrivi.hinta*lasku.vienti_kurssi*tilausrivi.kpl*{$query_ale_lisa},0)) ostoed, ";
						$query .= " round(sum(if($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl', tilausrivi.hinta*lasku.vienti_kurssi*tilausrivi.kpl*{$query_ale_lisa},0))/sum(if($pvmvar >= '$vvaa-$kka-$ppa'  and $pvmvar <= '$vvll-$kkl-$ppl', tilausrivi.hinta*lasku.vienti_kurssi*tilausrivi.kpl*{$query_ale_lisa},0)),2) ostoind, ";
					}

					$query .= " sum(if($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) ostoarvonyt, ";

					//Ostoed
					if ($piiloed == "") {
						$query .= " sum(if($pvmvar >= '$vvaa-$kka-$ppa'  and $pvmvar <= '$vvll-$kkl-$ppl', tilausrivi.rivihinta,0)) ostoarvoed, ";
						$query .= " round(sum(if($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0))/sum(if($pvmvar >= '$vvaa-$kka-$ppa'  and $pvmvar <= '$vvll-$kkl-$ppl', tilausrivi.rivihinta,0)),2) ostoarvoind, ";
					}

					if ($piilota_kappaleet == "") {
						$query .= "	sum(if($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl', tilausrivi.kpl,0)) ostokplnyt, ";

						//KPLED
						if ($piiloed == "") {
							$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)) ostokpled, ";
							$query .= " round(sum(if($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl', tilausrivi.kpl,0))/sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)),2) ostokplind, ";
						}
					}

					// Vika pilkku ja space pois
					$query = substr($query, 0 ,-2);
				}

				$query .= "	FROM lasku use index (yhtio_tila_tapvm)
							JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi='O'
							JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
							LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							LEFT JOIN toimi use index (PRIMARY) ON toimi.yhtio=lasku.yhtio and toimi.tunnus=lasku.liitostunnus
							WHERE lasku.yhtio in ($yhtio)
							$asiakasrajaus
							and lasku.tila = 'K'";

				if (strpos($ajotapa, "valmiit") !== FALSE) {
					$query .= " and kohdistettu = 'X' ";
					$query .= " and lasku.alatila = 'X' ";
				}

				$query .= " and (($pvmvar >= '$vva-$kka-$ppa'  and $pvmvar <= '$vvl-$kkl-$ppl') ";

				if ($piiloed == "") {
					$query .= " or ($pvmvar >= '$vvaa-$kka-$ppa' and $pvmvar <= '$vvll-$kkl-$ppl') ";
				}

				$query .= " ) ";
				$query .= "	$lisa
							GROUP BY $group
							ORDER BY $order";

				// ja sitten ajetaan itte query
				if ($query != "") {

					//echo "<pre>$query</pre><br>";

					$result = mysql_query($query) or pupe_error($query);

					$rivilimitti = 1000;

					if($vain_excel != "") {
						echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
						$rivilimitti = 0;
					}
					else {
						if (mysql_num_rows($result) > $rivilimitti) {
							echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
							echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
						}
					}
				}

				if ($query != "") {
					if (strpos($_SERVER['SCRIPT_NAME'], "ostoseuranta.php") !== FALSE) {
						if(@include('Spreadsheet/Excel/Writer.php')) {

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
					echo "</table><br>";

					if (mysql_num_rows($result) <= $rivilimitti) echo "<table><tr>";

					// echotaan kenttien nimet
					for ($i=0; $i < mysql_num_fields($result); $i++) {
						if (mysql_num_rows($result) <= $rivilimitti) echo "<th>".t(mysql_field_name($result,$i))."</th>";
					}

					if (isset($workbook)) {
						for ($i=0; $i < mysql_num_fields($result); $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
						$excelrivi++;
					}

					if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>\n";

					$edluku 		= "x";
					$valisummat 	= array();
					$totsummat  	= array();
					$tarra_aineisto = "";

					if (mysql_num_rows($result) > $rivilimitti) {

						require_once ('inc/ProgressBar.class.php');
						$bar = new ProgressBar();
						$elements = mysql_num_rows($result); // total number of elements to process
						$bar->initialize($elements); // print the empty bar
					}

					while ($row = mysql_fetch_array($result)) {

						if (mysql_num_rows($result) > $rivilimitti) $bar->increase();

						if ($osoitetarrat != "" and $row["astunnus"] > 0) {
							$tarra_aineisto .= $row["astunnus"].",";
						}

						if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

						// echotaan kenttien sisältö
						for ($i=0; $i < mysql_num_fields($result); $i++) {

							// jos kyseessa on tuote
							if (mysql_field_name($result, $i) == "tuoteno") {
								$row[$i] = "<a href='../tuote.php?tee=Z&tuoteno=".urlencode($row[$i])."'>$row[$i]</a>";
							}

							// jos kyseessa on tuoteosasto, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuos") {
								$osre = t_avainsana("OSASTO", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
								$osrow = mysql_fetch_array($osre);

								if ($osrow['selitetark'] != "" and $osrow['selite'] != $osrow['selitetark']) {
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on tuoteosasto, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuoteryhmä") {
								$osre = t_avainsana("TRY", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
								$osrow = mysql_fetch_array($osre);

								if ($osrow['selitetark'] != "" and $osrow['selite'] != $osrow['selitetark']) {
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on ostaja, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuoteostaja") {
								$query = "	SELECT nimi
											FROM kuka
											WHERE yhtio in ($yhtio)
											and myyja = '$row[$i]'
											AND myyja > 0
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['nimi'];
								}
							}

							// Jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
							if ($gluku > 1 and $edluku != $row[0] and $edluku != 'x' and $piiyhteensa == '' and strpos($group, ',') !== FALSE and substr($group, 0, 13) != "tuote.tuoteno") {
								$excelsarake = $ostoind = $ostoarvoind = $ostokplind = 0;

								foreach ($valisummat as $vnim => $vsum) {
									if ((string) $vsum != '') {
										$vsum = sprintf("%.2f", $vsum);
									}

									if ($vnim == "ostoind") {
										if ($valisummat["ostoed"] <> 0) 		$vsum = round($valisummat["ostonyt"] / $valisummat["ostoed"],2);
									}
									if ($vnim == "ostoarvoind") {
										if ($valisummat["ostoarvoed"] <> 0) 	$vsum = round($valisummat["ostoarvonyt"] / $valisummat["ostoarvoed"],2);
									}
									if ($vnim == "ostokplind") {
										if ($valisummat["ostokpled"] <> 0)		$vsum = round($valisummat["ostokplnyt"] / $valisummat["ostokpled"],2);
									}

									if (mysql_num_rows($result) <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

									if(isset($workbook)) {
										$worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
									}

									$excelsarake++;

								}
								$excelrivi++;
								if (mysql_num_rows($result) <= $rivilimitti) echo "</tr><tr>";

								$valisummat = array();
							}
							$edluku = $row[0];

							// hoidetaan pisteet piluiksi!!
							if (is_numeric($row[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int')) {
								if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top' align='right'>".sprintf("%.02f",$row[$i])."</td>";

								if(isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
								}
							}
							elseif (mysql_field_name($result, $i) == 'sarjanumero') {
								if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top'>$row[$i]</td>";

								if(isset($workbook)) {
									$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", "\n", $row[$i])));
								}
							}
							else {
								if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top'>$row[$i]</td>";

								if(isset($workbook)) {
									$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", " / ", $row[$i])));
								}
							}
						}

						if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>\n";
						$excelrivi++;

						for ($i=0; $i < mysql_num_fields($result); $i++) {

							if ($i < substr_count($select, ", ")) {
								$valisummat[mysql_field_name($result, $i)] = "";
								$totsummat[mysql_field_name($result, $i)]  = "";
							}
							else {
								$valisummat[mysql_field_name($result, $i)] += $row[mysql_field_name($result, $i)];
								$totsummat[mysql_field_name($result, $i)]  += $row[mysql_field_name($result, $i)];
							}
						}
					}

					$apu = mysql_num_fields($result)-11;

					// jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
					if ($gluku > 1 and $mukaan != 'tuote' and $piiyhteensa == '') {

						if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

						$excelsarake = $ostoind = $ostoarvoind = $ostokplind = 0;

						foreach($valisummat as $vnim => $vsum) {
							if ((string) $vsum != '') {
								$vsum = sprintf("%.2f", $vsum);
							}

							if ($vnim == "ostoind") {
								if ($valisummat["ostoed"] <> 0) 		$vsum = round($valisummat["ostonyt"] / $valisummat["ostoed"],2);
							}
							if ($vnim == "ostoarvoind") {
								if ($valisummat["ostoarvoed"] <> 0) 		$vsum = round($valisummat["ostoarvonyt"] / $valisummat["ostoarvoed"],2);
							}
							if ($vnim == "ostokplind") {
								if ($valisummat["ostokpled"] <> 0)		$vsum = round($valisummat["ostokplnyt"] / $valisummat["ostokpled"],2);
							}

							if (mysql_num_rows($result) <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

							if(isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
							}

							$excelsarake++;

						}
						$excelrivi++;
						if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>";
					}

					if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

					$excelsarake = $ostoind = $ostoarvoind = $ostokplind = 0;

					foreach($totsummat as $vnim => $vsum) {
						if ((string) $vsum != '') {
							$vsum = sprintf("%.2f", $vsum);
						}
						if ($vnim == "ostoind") {
							if ($totsummat["ostoed"] <> 0) 		$vsum = round($totsummat["ostonyt"] / $totsummat["ostoed"],2);
						}
						if ($vnim == "ostoarvoind") {
							if ($totsummat["ostoarvoed"] <> 0) 		$vsum = round($totsummat["ostoarvonyt"] / $totsummat["ostoarvoed"],2);
						}
						if ($vnim == "ostokplind") {
							if ($totsummat["ostokpled"] <> 0)		$vsum = round($totsummat["ostokplnyt"] / $totsummat["ostokpled"],2);
						}

						if (mysql_num_rows($result) <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

						if(isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
							$excelsarake++;
						}
					}
					$excelrivi++;

					if (mysql_num_rows($result) <= $rivilimitti) echo "</tr></table>";

					echo "<br>";

					if(isset($workbook)) {
						// We need to explicitly close the workbook
						$workbook->close();

						echo "<table>";
						echo "<tr><th>".t("Tallenna tulos").":</th>";
						echo "<form method='post' class='multisubmit'>";
						echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
						echo "<input type='hidden' name='kaunisnimi' value='Ostoseuranta.xls'>";
						echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
						echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
						echo "</table><br>";
					}

					if ($osoitetarrat != "" and $tarra_aineisto != '')  {
						$tarra_aineisto = substr($tarra_aineisto, 0, -1);


						echo "<br><table>";
						echo "<tr><th>".t("Tulosta osoitetarrat").":</th>";
						echo "<form method='post' action='../crm/tarrat.php'>";
						echo "<input type='hidden' name='tee' value=''>";
						echo "<input type='hidden' name='tarra_aineisto' value='$tarra_aineisto'>";
						echo "<td class='back'><input type='submit' value='".t("Siirry")."'></td></tr></form>";
						echo "</table><br>";
					}
				}
				echo "<br><br><hr>";
			}
		}

		if ($lopetus == "") {
			//Käyttöliittymä
			if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($kkl)) $kkl = date("m");
			if (!isset($vvl)) $vvl = date("Y");
			if (!isset($ppl)) $ppl = date("d");
			if (!isset($yhtio)) $yhtio = "'$kukarow[yhtio]'";

			echo "<br>\n\n\n";
			echo "<form method='post'>";
			echo "<input type='hidden' name='tee' value='go'>";
			echo "<input type='hidden' name='yhtiot[]' value='$kukarow[yhtio]'>";

			if ($ruksit[10]  != '') 	$ruk10chk  	= "CHECKED";
			if ($ruksit[20]  != '') 	$ruk20chk  	= "CHECKED";
			if ($ruksit[30]  != '') 	$ruk30chk  	= "CHECKED";
			if ($ruksit[40]  != '') 	$ruk40chk  	= "CHECKED";
			if ($ruksit[50]  != '') 	$ruk50chk 	= "CHECKED";
			if ($ruksit[55]  != '') 	$ruk55chk 	= "CHECKED";

			echo "<table><tr>";

			if ($ajotapa == "valmiit") {
				$chk1 = "SELECTED";
			}
			elseif ($ajotapa == "kaikki") {
				$chk2 = "SELECTED";
			}
			elseif ($ajotapa == "valmiit_mapvm") {
				$chk3 = "SELECTED";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Valitse ajotapa:")."</th>";
			echo "<td><select name='ajotapa'>";
			echo "<option value='valmiit'  				$chk1>".t("Valmiit saapumiset")." (".t("Varastoonvientipäivän mukaan").")</option>";
			echo "<option value='kaikki' 				$chk2>".t("Valmiit saapumiset ja keskeneräiset saapumiset")." (".t("Varastoonvientipäivän mukaan").")</option>";
			echo "<option value='valmiit_mapvm'			$chk3>".t("Valmiit saapumiset")." (".t("Virallisen varastonarvolaskentapäivän mukaan").")</option>";
			echo "</select></td>";

			echo "</tr>";
			echo "</table><br><table>";

			echo "<tr>";
			echo "<th>".t("Valitse tuoteosastot").":</th>";
			echo "<th>".t("Valitse tuoteryhmät").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// näytetään soveltuvat osastot
			// tehdään avainsana query
			$res2 = t_avainsana("OSASTO");

			echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_osasto!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteosastoa")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_osasto!="") {
					if (in_array($rivi['selite'],$mul_osasto)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";

			echo "</td>";
			echo "<td valign='top' class='back'>";

			// näytetään soveltuvat tryt
			// tehdään avainsana query
			$res2 = t_avainsana("TRY");

			echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_try!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteryhmää")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_try!="") {
					if (in_array($rivi['selite'],$mul_try)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[40]' size='2' value='$jarjestys[40]'> ".t("Osastoittain")." <input type='checkbox' name='ruksit[40]' value='osasto' $ruk40chk></th>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[50]' size='2' value='$jarjestys[50]'> ".t("Tuoteryhmittäin")." <input type='checkbox' name='ruksit[50]' value='tuoteryhma' $ruk50chk></th></tr>";

			echo "</table><br>\n";

			// lisärajaukset näkymä..
			if ($ruksit[60]  != '') 			$ruk60chk  				= "CHECKED";
			if ($ruksit[70]  != '') 			$ruk70chk  				= "CHECKED";
			if ($ruksit[80]  != '') 			$ruk80chk  				= "CHECKED";
			if ($ruksit[90]  != '') 			$ruk90chk  				= "CHECKED";
			if ($ruksit[100]  != '') 			$ruk100chk 				= "CHECKED";
			if ($ruksit[110]  != '') 			$ruk110chk 				= "CHECKED";
			if ($ruksit[120]  != '') 			$ruk120chk 				= "CHECKED";
			if ($ruksit[130]  != '') 			$ruk130chk 				= "CHECKED";
			if ($ruksit[140]  != '') 			$ruk140chk 				= "CHECKED";
			if ($ruksit[150] != '') 			$ruk150chk 				= "CHECKED";
			if ($ruksit[160] != '')				$ruk160chk 				= "CHECKED";
			if ($ruksit[170] != '')				$ruk170chk 				= "CHECKED";
			if ($nimitykset != '')   			$nimchk   				= "CHECKED";
			if ($piiyhteensa != '')  			$piychk   				= "CHECKED";
			if ($kuukausittain == 'SARAKE')		$kuuchk1	  			= "CHECKED";
			if ($kuukausittain == 'ALLEKKAIN')	$kuuchk2	  			= "CHECKED";
			if ($piiloed != '')					$piiloedchk 			= "CHECKED";
			if ($tilrivikomm != '')				$tilrivikommchk 		= "CHECKED";
			if ($vain_excel != '')				$vain_excelchk 			= "CHECKED";
			if ($piilota_kappaleet != '')		$piilota_kappaleet_sel 	= "CHECKED";

			echo "<table>
				<tr>
				<th>".t("Lisärajaus")."</th>
				<th>".t("Prio")."</th>
				<th> x</th>
				<th>".t("Rajaus")."</th>
				</tr>
				<tr>
				<tr>
				<th>".t("Listaa tuotteittain")."</th>
				<td><input type='text' name='jarjestys[80]' size='2' value='$jarjestys[80]'></td>
				<td><input type='checkbox' name='ruksit[80]' value='tuote' $ruk80chk></td>
				<td><input type='text' name='rajaus[80]' value='$rajaus[80]'></td>
				</tr>
				<tr>
				<th>".t("Listaa tuoteostajittain")."</th>
				<td><input type='text' name='jarjestys[110]' size='2' value='$jarjestys[110]'></td>
				<td><input type='checkbox' name='ruksit[110]' value='tuoteostaja' $ruk110chk></td>
				<td>";

			$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<select name='rajaus[110]'>";
			echo "<option value = '' ></option>";

			while ($vrow=mysql_fetch_array($vresult)) {

				if ($rajaus[110] == $vrow['myyja']) {
					$sel = "selected";
				}
				else {
					$sel = "";
				}

				echo "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
			}

			echo "</select>";

			echo "	</td>
				</tr>
				<tr>
				<th>".t("Listaa merkeittäin")."</th>
				<td><input type='text' name='jarjestys[130]' size='2' value='$jarjestys[130]'></td>
				<td><input type='checkbox' name='ruksit[130]' value='merkki' $ruk130chk></td>
				<td><input type='text' name='rajaus[130]' value='$rajaus[130]'></td>
				</tr>
				<tr>
				<th>".t("Listaa toimittajittain")."</th>
				<td><input type='text' name='jarjestys[140]' size='2' value='$jarjestys[140]'></td>
				<td><input type='checkbox' name='ruksit[140]' value='toimittaja' $ruk140chk></td>
				<td><input type='text' name='toimittaja' value='$toimittaja'></td>
				</tr>
				<td class='back'><br></td>
				</tr>
				<tr>
				<th>".t("Piilota kappaleet")."</th>
				<td><input type='checkbox' name='piilota_kappaleet' $piilota_kappaleet_sel></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Piilota edellisen kauden sarakkeet")."</th>
				<td><input type='checkbox' name='piiloed' $piiloedchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Piilota välisummat")."</th>
				<td><input type='checkbox' name='piiyhteensa' $piychk></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Näytä tuotteiden nimitykset")."</th>
				<td><input type='checkbox' name='nimitykset' $nimchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
				</tr>
				<tr>
				<th>".t("Tulosta ostot kuukausittain")."</th>
				<td><input type='radio' name='kuukausittain' value='SARAKE' $kuuchk1>".t("Kuukaudet sarakkeisiin")."<br><input type='radio' name='kuukausittain' value='ALLEKKAIN' $kuuchk2>".t("Kukaudet allekkain")."</td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Raportti vain Exceliin")."</th>
				<td><input type='checkbox' name='vain_excel' $vain_excelchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				</table><br>";

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
				</tr>\n";
			echo "</table><br>";

			echo "<br>";
			echo "<input type='submit' value='".t("Aja raportti")."'>";
			echo "</form>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "ostoseuranta.php") !== FALSE) {
			require ("../inc/footer.inc");
		}
	}
?>
