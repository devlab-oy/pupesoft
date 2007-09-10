<?php
	
	//parametrit
	include('../inc/parametrit.inc');
	
	js_popup();
	
	//Näytetään aina konsernikohtaisesti
	$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = mysql_query($query) or pupe_error($query);
	$konsernit = "";

	while ($row = mysql_fetch_array($result)) {
		$konsernit .= " '".$row["yhtio"]."' ,";
	}
	$konsernit1 = " and yhtio in (".substr($konsernit, 0, -1).") ";
	$konsernit2 = " and kalenteri.yhtio in (".substr($konsernit, 0, -1).") ";
	$konsernit3 = " and kuka.yhtio in (".substr($konsernit, 0, -1).") ";

	//korjataan joitain numeroita mysqllaan sopiviksi
	function to_mysql($expr) {
    	if(strlen($expr) < 2) {
    		$expr = '0'.$expr;
		}
		return ("$expr");
	}

	//kuukaudet ja päivät ja ajat
	$MONTH_ARRAY = array(1=>t('Tammi'),t('Helmi'),t('Maalis'),t('Huhti'),t('Touko'),t('Kesä'),t('Heinä'),t('Elo'),t('Syys'),t('Loka'),t('Marras'),t('Joulu'));
	$DAY_ARRAY = array(t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));


	//kalenteritoiminnot
	function days_in_month($month, $year){
		// calculate number of days in a month
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}

	function weekday_name($day, $month, $year) {
		// calculate weekday name
		$days = array(t("Maanantai"), t("Tiistai"), t("Keskiviikko"), t("Torstai"), t("Perjantai"), t("Lauantai"),t("Sunnuntai"));
		$nro = date("w", mktime(0, 0, 0, $month, $day, $year));
		if ($nro==0) $nro=6;
		else $nro--;

		return $days[$nro];
	}

	function weekday_number($day, $month, $year) {
		// calculate weekday number
		$nro = date("w", mktime(0, 0, 0, $month, $day, $year));
		if ($nro==0) $nro=6;
		else $nro--;

		return $nro;
	}

	function month_name($month) {
		// display long month name
		$kk = $MONTH_ARRAY;
		return $kk[$month-1];
	}
	
	// otetaan oletukseksi tämä monthkausi ja tämä vuosi
	if ($month == '') $month=date("n");
	if ($year == '')  $year=date("Y");
	if ($day == '') $day=date("j");

	//lasketaan edellinen ja seuraava paiva kun siirytaan yksi paiva
	$backmday = date("n",mktime(0, 0, 0, $month, $day-1,  $year));
	$backyday = date("Y",mktime(0, 0, 0, $month, $day-1,  $year));
	$backdday = date("j",mktime(0, 0, 0, $month, $day-1,  $year));

	$nextmday = date("n",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextyday = date("Y",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextdday = date("j",mktime(0, 0, 0, $month, $day+1,  $year));

	//lasketaan edellinen ja seuraava paiva kun siirytaan yksi kuukausi
	$backmmonth = date("n",mktime(0, 0, 0, $month-1, $day,  $year));
	$backymonth = date("Y",mktime(0, 0, 0, $month-1, $day,  $year));
	$backdmonth = date("j",mktime(0, 0, 0, $month-1, $day,  $year));

	$nextmmonth = date("n",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextymonth = date("Y",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextdmonth = date("j",mktime(0, 0, 0, $month+1, $day,  $year));

	//viela muuttujat mysql kyselyja varten, (etunollat pitaa olla...)
	$mymonth = to_mysql($month);
	$myday = to_mysql($day);

	$lopetus = "../crm/kuukausinakyma.php////osasto=$osasto//year=$year//month=$month//day=$pva";
	
	echo "<font class='head'>".t("Kuukausinäkymä").": ". $MONTH_ARRAY[$month] ." $year</font><hr>";

	echo "<table>";
	echo "<th>".t("Valitse kuukausi").":</th><td>
			<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='year'  value='$year'>
			<a href='$PHP_SELF?day=1&month=$backmmonth&year=$backymonth&osasto=$osasto'> << </a>
			<select name='month' Onchange='submit();'>";

	$i=1;
	foreach($MONTH_ARRAY as $val) {
		if($i == $month) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		echo "<option value='$i' $sel>$val</option>";
		$i++;
	}

	echo "</select><a href='$PHP_SELF?day=1&month=$nextmmonth&year=$nextymonth&osasto=$osasto'> >> </a></td>";
	echo "<td class='back' width='20'></td>";
	echo "<th>".t("Valitse osasto").":</th><td><select name='osasto' Onchange='submit();'>";

	//Haetaan kaikki käyttäjät
	$query = "	SELECT distinct kuka.osasto
				FROM kuka, oikeu
				WHERE oikeu.yhtio	= kuka.yhtio
				and oikeu.kuka		= kuka.kuka
				and oikeu.nimi		= 'crm/kalenteri.php'
				and kuka.osasto	   != ''
				$konsernit3
				ORDER BY kuka.osasto";
	$result = mysql_query($query) or pupe_error($query);

	// jos ei olla valittu osastoa ja käyttäjällä on oma osasto valitaan se
	if ($osasto == "" and $kukarow["osasto"] != "") {
		$osasto = $kukarow["osasto"];
	}

	if ($osasto == "kaikki") {
		$osasto = "";
	}

	echo "<option value='kaikki'>".t("Näytetään kaikki")."</option>";

	while($row = mysql_fetch_array($result)) {
		if($row["osasto"] == $osasto) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		echo "<option value='$row[osasto]' $sel>$row[osasto]</option>";

	}

	if($osasto == "varauskalenterit") {
		$sel = "selected";
	}
	else {
		$sel = "";
	}

	echo "<option value='varauskalenterit' $sel>".t("Varauskalenterit")."</option>";

	//	Onko projekteja?
	$query = "SELECT tunnus FROM lasku WHERE yhtio='{$kukarow["yhtio"]}' and tila = 'R' LIMIT 1";
	$tarkres = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($tarkres)==1) {
		if($osasto == "projektikalenterit") {
			$sel = "selected";
		}
		else {
			$sel = "";
		}

		echo "<option value='projektikalenterit' $sel>".t("Projektikalenterit")."</option>";		
	}

	echo "</select></td></form>";


	echo "</table>";

	echo "<table cellspacing='0' cellpadding='0' >";


	if ($osasto != '') {
		$osastolisa = " and kuka.osasto='".$osasto."'";
	}
	else {
		$osastolisa = "";
	}

	//Haetaan kaikki käyttäjät
	$query = "	SELECT distinct kuka.nimi, kuka.kuka, kuka.osasto
				FROM kuka, oikeu
				WHERE oikeu.yhtio	= kuka.yhtio
				and oikeu.kuka		= kuka.kuka
				and oikeu.nimi		= 'crm/kalenteri.php'
				and kuka.osasto	   != ''
				$konsernit3
				$osastolisa
				ORDER BY kuka.osasto, kuka.nimi";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)) {

		echo "\n";

		$nimi = explode(' ', $row["nimi"]);

		if ($edosasto != $row["osasto"]) {
			echo "<tr><td class='back'></td></tr>";

			echo "<tr>";
			echo "<th>$row[osasto]</th><th></th>";

			$y = weekday_number("1", $month, $year);
			$x = days_in_month($month, $year);
			$u = 1;

			for($r = $y; $r < sizeof($DAY_ARRAY); $r++) {
				echo "<th>$DAY_ARRAY[$r]<br>$u</th>";

				if ($r == 6) {
					$r = -1;

					echo "<td class='back'></td>";
				}

				if ($u >= $x) {
					break;
				}
				$u++;
			}
			echo "</tr>";
		}

		if ($row["kuka"] == $valkuka) {
			$varitys = "th";
		}
		else {
			$varitys = "td";
		}

		echo "<tr><$varitys><a href='$PHP_SELF?day=1&month=$month&year=$year&valkuka=$row[kuka]&osasto=$osasto' title='$row[nimi]'>".substr($nimi[0],0,1).". $nimi[1]</a></$varitys><td></td>";

		for($i = 1; $i <= days_in_month($month, $year); $i++) {
			$pva = to_mysql($i);

			$query = "	SELECT avainsana.selite tapa, avainsana.selitetark, kalenteri.tunnus, pvmalku, pvmloppu, kalenteri.yhtio,
						date_format(pvmalku,'%H:%i') kello, date_format(pvmalku,'%d') paiva, date_format(pvmalku,'%m') kuu, date_format(pvmalku,'%Y') vuosi,
						kalenteri.kuka, date_format(pvmloppu,'%H:%i') lkello,
						kalenteri.kuittaus,
						kalenteri.laatija,
						replace(kentta01,'\r\n',' ') kentta01
						FROM kalenteri
						LEFT JOIN avainsana ON kalenteri.yhtio = avainsana.yhtio and avainsana.laji = 'KALETAPA' and avainsana.selitetark = kalenteri.tapa
						WHERE kalenteri.kuka = '$row[kuka]'
						and kalenteri.tyyppi = 'kalenteri'
						and ((pvmalku >= '$year-$mymonth-$pva 00:00:00' and pvmalku <= '$year-$mymonth-$pva 23:59:00') or
						(pvmalku < '$year-$mymonth-$pva 00:00:00' and pvmloppu > '$year-$mymonth-$pva 00:00:00'))
						$konsernit2
						order by pvmalku";
			$kresult = mysql_query($query) or pupe_error($query);
						
			if (mysql_num_rows($kresult) > 0) {
				$krow = mysql_fetch_array($kresult);
		
				$etufontti  = "";
				$takafontti = "";
	
				if ($krow["kuittaus"] == "" and ($krow["selitetark"] == "Palkaton vapaa" or $krow["selitetark"] == "Sairasloma" or $krow["selitetark"] == "Kesäloma" or $krow["selitetark"] == "Talviloma" or $krow["selitetark"] == "Ylityövapaa")) {
					$etufontti = "<font color='#FF0000'>";
					$takafontti = "</font>";
				}
				elseif ($krow["kuittaus"] != "" and ($krow["selitetark"] == "Palkaton vapaa" or $krow["selitetark"] == "Sairasloma" or $krow["selitetark"] == "Kesäloma" or $krow["selitetark"] == "Talviloma" or $krow["selitetark"] == "Ylityövapaa")) {
					$etufontti = "<font color='#00FF00'>";
					$takafontti = "</font>";
				}
	
				//Vanhoja kalenteritapahtumia ei saa enää muuttaa
				list($rvv,$rkk,$rpp) = split("-",substr($krow["pvmloppu"],0,10));
				$kaleloppu  = (int) date('Ymd',mktime(0,0,0,$rkk,$rpp,$rvv));
				$aikanyt 	= (int) date('Ymd',mktime(0,0,0,date('m'),date('d'),date('Y')));		
							
				// Vanhoja kalenteritapahtumia ei saa enää muuttaa ja Hyväksyttyjä lomia ei saa ikinä muokata
				if($krow['tunnus'] != '' and $krow["kuittaus"] == "" and $kaleloppu >= $aikanyt and ($krow['kuka'] == $kukarow["kuka"] or $krow['laatija'] == $kukarow["kuka"])) {
					echo "<div id='$krow[tunnus]' class='popup' style='width:200px;'>";
	
					if (mysql_num_rows($kresult) > 0) {
						mysql_data_seek($kresult,0);
	
						while ($krow2 = mysql_fetch_array($kresult)) {
							echo "$krow2[kello]-$krow2[lkello] $row2[nimi]<br>$krow2[selitetark].<br>$krow2[kentta01]<br><br>";
						}
					}
					echo "</div>";
	
					echo "<$varitys align='center' width='35'><a class='td' href='kalenteri.php?valitut=$row[kuka]&kenelle=$row[kuka]&tee=SYOTA&kello=$krow[kello]&year=$krow[vuosi]&kuu=$krow[kuu]&paiva=$krow[paiva]&tunnus=$krow[tunnus]&konserni=XXX' onmouseout=\"popUp(event,'$krow[tunnus]')\" onmouseover=\"popUp(event,'$krow[tunnus]')\">$etufontti $krow[tapa] $takafontti</a></$varitys>";
				}
				else {
					echo "	<div id='$krow[tunnus]' class='popup' style='width:200px;'>
							$krow[kello]-$krow[lkello] $row[nimi]<br>$krow[selitetark].<br>$krow[kentta01]
							</div>";
	
					echo "<$varitys align='center' width='35'><a class='td' href='#' onmouseout=\"popUp(event,'$krow[tunnus]')\" onmouseover=\"popUp(event,'$krow[tunnus]')\">$etufontti $krow[tapa] $takafontti</a></$varitys>";
				}
			}
			else {
				echo "<$varitys align='center' width='35'><a class='td' href='kalenteri.php?valitut=$row[kuka]&kenelle=$row[kuka]&tee=SYOTA&kello=&year=$year&kuu=$month&paiva=$pva&konserni=XXX'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></$varitys>";
			}

			if (weekday_number($i, $month, $year)==6) {
				echo "<td class='back'></td>";
			}

			echo "\n";
		}

		$edosasto = $row["osasto"];

		echo "</tr>";
	}

	echo "<tr><td class='back'><br></td></tr>";
	
	$query = "SELECT tunnus FROM lasku WHERE yhtio='{$kukarow["yhtio"]}' and tila = 'R' LIMIT 1";
	$tarkres = mysql_query($query) or pupe_error($query);	
	if (($osasto == "" or $osasto == "projektikalenterit") and mysql_num_rows($tarkres) == 1) {
		if ($edosasto != $row["osasto"] or ($edosasto=="")) {
			echo "<tr><td class='back'></td></tr>";

			echo "<tr>";
			echo "<th>".t("Projektikalenterit")."</th><th></th>";
			$y = weekday_number("1", $month, $year);
			$x = days_in_month($month, $year);
			$u = 1;

			for($r = $y; $r < sizeof($DAY_ARRAY); $r++) {
				echo "<th>$DAY_ARRAY[$r]<br>$u</th>";

				if ($r == 6) {
					$r = -1;

					echo "<td class='back'></td>";
				}

				if ($u >= $x) {
					break;
				}
				$u++;
			}
			echo "</tr>";
		}
		
		//	Haetaan sallitut seurannat
		$query = "	SELECT group_concat(distinct selite SEPARATOR \"','\") lajit
					FROM avainsana
					WHERE yhtio = '{$kukarow["yhtio"]}' and laji = 'SEURANTA' and selitetark_2 = 'kale'
					GROUP BY laji";
		$abures = mysql_query($query) or pupe_error($query);
		$aburow = mysql_fetch_array($abures);
		if($aburow["lajit"] != "") {
			$lajilisa = " and seuranta IN ('','{$aburow["lajit"]}')";
		}
		
		//	Haetaan kaikki projektiotsikot
		$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) projekti, nimi, nimitark, seuranta, tunnusnippu, lasku.tunnus, tila, alatila
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio = laskun_lisatiedot.yhtio and otunnus=lasku.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and (tila =  'R' and alatila IN ('', 'A') or (tila IN ('N','L') and alatila != 'X' $lajilisa)) and (tunnusnippu = lasku.tunnus or tunnusnippu = 0)
					ORDER BY tunnusnippu";
		$result = mysql_query($query) or pupe_error($query);

		while($row = mysql_fetch_array($result)) {
			
			$href = "../tilauskasittely/tilaus_myynti.php?toim=PROJEKTI&valitsetoimitus={$row["projekti"]}&lopetus=$lopetus";
			
			if($row["tunnusnippu"] == 0) {
				$nippu = " lasku.tunnus = '{$row["tunnus"]}'";
			}
			else {
				$nippu = " tunnusnippu = '{$row["tunnusnippu"]}'";
			}
			
			$query = "	SELECT 	tunnus, nimi, tila, alatila, 
								toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, toim_maa,
								date_format(kerayspvm, '%d. %m. %Y') kerayspvm, date_format(toimaika, '%d. %m. %Y') toimaika,
								day(kerayspvm) kerpvm,
								day(toimaika) toimpvm
						FROM lasku
						WHERE $nippu and ((tila IN ('L','G','E','V','W','A') and alatila NOT IN ('X','J')) or tila = 'N') and clearing NOT IN ('loppulasku', 'ENNAKKOLASKU')
						and (	
								(kerayspvm >= '$year-$mymonth-01 00:00:00' and kerayspvm < '$year-".($mymonth+1)."-01 00:00:00') or
								(toimaika >= '$year-$mymonth-01 00:00:00' and toimaika < '$year-".($mymonth+1)."-01 00:00:00') or 
								(kerayspvm <= '$year-$mymonth-01 00:00:00' and toimaika > '$year-".($mymonth+1)."-01 00:00:00')
							)
						$konsernit1
						GROUP BY tunnus";
			$kresult = mysql_query($query) or pupe_error($query);
			//echo str_replace("\t", " ", $query)."<br>";
			unset($eka);
			if(mysql_num_rows($kresult)>0) {

				echo "<tr class='aktiivi'><td rowspan='".mysql_num_rows($kresult)."' NOWRAP>{$row[projekti]} - {$row[seuranta]} - {$row[nimi]}</td><td rowspan='".mysql_num_rows($kresult)."' NOWRAP>";
				
				//	Tehdään infobalooni				
				$id=md5(uniqid());
				$laskutyyppi = $row["tila"];
				$alatila 	 = $row["alatila"];
			 	require ("../inc/laskutyyppi.inc");
				
				if($row["tunnusnippu"] > 0) {
					$laskutyyppi .= " TOIMITUKSET";
				}
				
				echo "<div id='$id' class='popup' style=\"width: 500px\">
				<table width='500px' align='center'>
				<caption><font class='head'>$laskutyyppi</font></caption>
				<tr>
					<th>".t("Toimitus")."</th>
					<th>".t("Keräysaika")."</th>						
					<th>".t("Toimitusaika")."</th>
				</tr>";
				
				while ($krow = mysql_fetch_array($kresult)) {
					$laskutyyppi = $krow["tila"];
					$alatila 	 = $krow["alatila"];
				 	require ("../inc/laskutyyppi.inc");
					
					echo "
					<tr>
						<td>{$krow["tunnus"]} - ".t($laskutyyppi)." ".t($alatila)."</td>
						<td>{$krow["kerayspvm"]}</td>
						<td>{$krow["toimaika"]}</td>												
					</tr>";										
				}
				mysql_data_seek($kresult, 0);	
				echo "</table></div>";					

				echo "<a href='$href'><img src='../pics/lullacons/folder-open-green.png' class='edit'></a>&nbsp;<img src='../pics/lullacons/info.png' class='info' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"></td>";

				while ($krow = mysql_fetch_array($kresult)) {
					if(isset($eka)) {
						echo "<tr>";
					}
					else $eka = true;
					
					if($krow["tila"] == "L" or $krow["tila"] == "N") {
						$vari = "rgb(".rand(80,100).",".rand(150,255).",".rand(20,40).")";
						$vari2 = "green";						
					}
					elseif($krow["tila"] == "V" or $krow["tila"] == "W") {
						$vari = "rgb(".rand(20,40).",".rand(80,100).",".rand(150,255).")";
						$vari2 = "blue";
					}
					elseif($krow["tila"] == "A") {
						$vari = "rgb(".rand(210,255).",".rand(180,255).",0)";
						$vari2 = "yellow";
					}
					else {
						$vari = "rgb(".rand(200,255).",135,135)";
						$vari2 = "black";
					}
					
					if($krow["toimpvm"] < $krow["kerpvm"]) {
						$krow["toimpvm"] = days_in_month($month, $year);
					}
					
					for($i = 1; $i <= days_in_month($month, $year); $i++) {
												
						if($krow["kerpvm"] == $i) {
							$paivia = $krow["toimpvm"] - $krow["kerpvm"];

							$i+=$paivia;
							//	Viikonloput..
							for($x=$krow["kerpvm"];$x<=$krow["toimpvm"];$x++) {
								if (weekday_number($x, $month, $year)==6) {
									$paivia++;
								}
							}
								
							$paivia++;
							echo "<td colspan = '$paivia' style=\"border: 2px solid $vari2; background-color: $vari; text-align: center;\" NOWRAP>";
							
							$laskutyyppi = $krow["tila"];
							$alatila 	 = $krow["alatila"];
						 	require ("../inc/laskutyyppi.inc");
							
							$id=md5(uniqid());
							echo "<div id='$id' class='popup' style=\"width: 500px\">
							<table width='500px' align='center'>
							<caption><font class='head'>{$krow["tunnus"]} - ".t($laskutyyppi)." ".t($alatila)."</font></caption>
							<tr>
								<th>".t("Toimitusosoite")."</th>
								<th>".t("Keräysaika")."</th>						
								<th>".t("Toimitusaika")."</th>
							</tr>
							<tr>
								<td>{$krow["toim_nimi"]}<br>{$krow["toim_nimitark"]}<br>{$krow["toim_osoite"]}<br>{$krow["toim_postino"]} {$krow["toim_postitp"]}<br>".maa($krow["toim_maa"])."</td>
								<td>{$krow["kerayspvm"]}</td>
								<td>{$krow["toimaika"]}</td>
							</tr>
							</table>";
						
							$query = "	SELECT concat_ws(' ',tuoteno, nimitys) tuote, (varattu+jt) maara, kommentti
										FROM tilausrivi
										WHERE yhtio = '{$kukarow["yhtio"]}' and  otunnus = '{$krow["tunnus"]}' and tyyppi != 'D' and (perheid = 0 or perheid = tunnus)";
							$rivires = mysql_query($query) or pupe_error($query);
							if(mysql_num_rows($rivires)) {
								echo "<br><table width='500px' align='center'>
								<tr>
									<th>".t("Tuote")."</th>
									<th>".t("määrä")."</th>						
								</tr>";
								
								while($rivirow = mysql_fetch_array($rivires)) {
									echo "
									<tr>
										<td>{$rivirow["tuote"]}</td>
										<td>{$rivirow["maara"]}</td>
									</tr>";
									if($rivirow["kommentti"] != "") {
										echo "
										<tr>
											<td colspan = '2'><em> *{$rivirow["kommentti"]}</em></td>
										</tr>";										
									}
								}			
								echo "</table>";					
							}

							echo "</div>";
							
							echo "<font style=\"color: black; text-shadow: #e8ffe0 2px 2px 2px;\">{$krow["tunnus"]}</font>";
							
							if($row["tila"] == "R") {
								$href = "../tilauskasittely/tilaus_myynti.php?toim=PROJEKTI&tee=AKTIVOI&tilausnumero={$krow["tunnus"]}&from=PROJEKTIKALENTERI&lopetus=$lopetus";
								echo "&nbsp;<a href='$href'><img src='../pics/lullacons/folder-open-green.png' class='info'></a>";	
							}
							else {
								$href = "../tilauskasittely/tilaus_myynti.php?toim=RIVISYOTTO&tee=AKTIVOI&tilausnumero={$krow["tunnus"]}&from=PROJEKTIKALENTERI&lopetus=$lopetus";
								echo "&nbsp;<a href='$href'><img src='../pics/lullacons/folder-open-green.png' class='info'></a>";
							}
							
							echo "&nbsp;<img src='../pics/lullacons/info.png' class='info' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\">";
							
							echo "</td>";							
						}
						else {
							echo "<td NOWRAP>&nbsp;</td>";
							if (weekday_number($i, $month, $year)==6) {
								echo "<td class='back'></td>";
							}							
						}						
					}
				
					echo "</tr>";
				}
			}
		}
	}
	
	if ($osasto == "" or $osasto == "varauskalenterit") {
		if ($edosasto != $row["osasto"] or ($edosasto=="")) {
			echo "<tr><td class='back'></td></tr>";

			echo "<tr>";
			echo "<th>".t("Varauskalenterit")."</th><th></th>";
			$y = weekday_number("1", $month, $year);
			$x = days_in_month($month, $year);
			$u = 1;

			for($r = $y; $r < sizeof($DAY_ARRAY); $r++) {
				echo "<th>$DAY_ARRAY[$r]<br>$u</th>";

				if ($r == 6) {
					$r = -1;

					echo "<td class='back'></td>";
				}

				if ($u >= $x) {
					break;
				}
				$u++;
			}
			echo "</tr>";
		}

		$query = "	SELECT distinct alanimi
					FROM oikeu
					WHERE
					oikeu.nimi	= 'varauskalenteri/varauskalenteri.php'
					and oikeu.kuka  = ''
					and yhtio = '$kukarow[yhtio]'
					ORDER BY alanimi";
		$result = mysql_query($query) or pupe_error($query);

		while($row = mysql_fetch_array($result)) {

			echo "<tr><td width='35'>$row[alanimi]</td><td></td>";

			for($i = 1; $i <= days_in_month($month, $year); $i++) {
				$pva = to_mysql($i);

				$query = "	SELECT kalenteri.tunnus tunnus, pvmalku, pvmloppu, kalenteri.yhtio, kuka.nimi nimi, replace(kentta01,'\r\n',' ') kentta01,
							date_format(pvmalku,'%H:%i') kello, date_format(pvmloppu,'%H:%i') lkello
							FROM kalenteri, kuka
							WHERE kalenteri.tyyppi = 'varauskalenteri'
							and pvmalku  <= '$year-$mymonth-$pva 23:59:00'
							and pvmloppu >= '$year-$mymonth-$pva 00:00:00'
							and kalenteri.tapa  = '$row[alanimi]'
							and kalenteri.yhtio	= kuka.yhtio
							and kalenteri.kuka	= kuka.kuka
							$konsernit2
							order by pvmalku";
				$kresult = mysql_query($query) or pupe_error($query);
				$krow = mysql_fetch_array($kresult);

				if($krow["nimi"] == '') {
					echo "<td align='center' width='35'><a class='td' href='../varauskalenteri/varauskalenteri.php?lopetus=$lopetus&tee=SYOTA&year=$year&month=$month&day=$pva&toim=$row[alanimi]'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></td>";
				}
				else {
					echo "<div id='$krow[tunnus]' class='popup' style='width:200px;'>";

					if (mysql_num_rows($kresult) > 0) {
						mysql_data_seek($kresult,0);

						while ($krow2 = mysql_fetch_array($kresult)) {
							echo "$krow2[kello]-$krow2[lkello] $krow2[nimi]<br>$row[alanimi].<br>$krow2[kentta01]<br><br>";
						}
					}
					echo "</div>";


					$nimi = explode(' ', $krow["nimi"]);
					echo "<td align='center' width='35'><a class='td' href='../varauskalenteri/varauskalenteri.php?lopetus=$lopetus&tee=NAYTA&year=$year&month=$month&day=$pva&tunnus=$krow[tunnus]&toim=$row[alanimi]' onmouseout=\"popUp(event,'$krow[tunnus]')\" onmouseover=\"popUp(event,'$krow[tunnus]')\">".substr($nimi[0],0,1).".".substr($nimi[1],0,1).".</a></td>";
				}

				if (weekday_number($i, $month, $year)==6) {
					echo "<td class='back'></td>";
				}

				echo "\n";

			}

			echo "</tr>";
		}
	}

	echo "</table>";

	require ("inc/footer.inc");

?>
