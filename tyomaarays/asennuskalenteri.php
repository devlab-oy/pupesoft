<?php

	// Parametrit
	require('inc/parametrit.inc');

	echo "<font class='head'>".t("Asennuskalenteri").":</font><hr><br>";

	$AIKA_ARRAY = array("08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00");
	$ASENTAJA_ARRAY = array("Pekka","Sauli","Lefa");

	//kuukaudet ja p‰iv‰t ja ajat
	$MONTH_ARRAY = array(1=>'Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kes‰kuu','Hein‰kuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu');
	$DAY_ARRAY = array("Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai", "Lauantai", "Sunnuntai");
	
	// otetaan oletukseksi t‰m‰ kuukausi ja t‰m‰ vuosi
	if ($month == '') 	$month = date("n");
	if ($year == '')  	$year  = date("Y");
	if ($day == '') 	$day   = date("j");

	//lasketaan edellinen ja seuraava paiva kun siiryt‰‰n yksi p‰iv‰
	$backmday = date("n",mktime(0, 0, 0, $month, $day-1,  $year));
	$backyday = date("Y",mktime(0, 0, 0, $month, $day-1,  $year));
	$backdday = date("j",mktime(0, 0, 0, $month, $day-1,  $year));

	$nextmday = date("n",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextyday = date("Y",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextdday = date("j",mktime(0, 0, 0, $month, $day+1,  $year));

	//lasketaan edellinen ja seuraava paiva kun siiryt‰‰n yksi kuukausi
	$backmmonth = date("n",mktime(0, 0, 0, $month-1, $day,  $year));
	$backymonth = date("Y",mktime(0, 0, 0, $month-1, $day,  $year));
	$backdmonth = date("j",mktime(0, 0, 0, $month-1, $day,  $year));

	$nextmmonth = date("n",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextymonth = date("Y",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextdmonth = date("j",mktime(0, 0, 0, $month+1, $day,  $year));
	
	//kalenteritoiminnot
	function days_in_month($month, $year) {
		// calculate number of days in a month
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}

	function weekday_name($day, $month, $year) {
		// calculate weekday name
		$days = array("Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai", "Lauantai","Sunnuntai");
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
	
	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";
		
		while ($row = mysql_fetch_array($result)) {	
			$konsernit .= " '".$row["yhtio"]."' ,";
		}		
		$konsernit = "yhtio in (".substr($konsernit, 0, -1).") ";			
	}
	else {
		$konsernit = "yhtio = '$kukarow[yhtio]' ";
	}
	
	
	if ($tee == "LISAA") {
		
		$alku  = str_replace(array(":","-"," "), "", "$year-$month-$day $AIKA_ARRAY[$aika]");
		$loppu = str_replace(array(":","-"," "), "", "$lyear-$lmonth-$lday $laika");
		
		//tarkistetaan, etta alku ja loppu ovat eri..
		if ($alku == $loppu) {
			echo "<font class='error'>".t("VIRHE: Alku- ja p‰‰ttymisajankohta ovat samat")."!</font><br><br>";
			$tee = "VARAA";
		}
		if ($alku > $loppu) {
			echo "<font class='error'>".t("VIRHE: P‰‰ttymisjankohta on aikaisempi kuin alkamisajankohta")."!</font><br><br>";
			$tee = "VARAA";
		}

		$query = "	SELECT tunnus 
					FROM kalenteri
					WHERE 
					$konsernit
					and tyyppi 	= 'asennuskalenteri'
					and tapa    = '$tyojono' 
					and (	(pvmalku >= '$year-$month-$day $aika:00' and pvmalku <= '$year-$month-$day $aika:00') or 
							(pvmalku <  '$year-$month-$day $aika:00' and pvmloppu > '$lyear-$lmonth-$lday $laika:00') or
							(pvmloppu>= '$year-$month-$day $aika:00' and pvmloppu<= '$lyear-$lmonth-$lday $laika:00'))
					and tunnus != '$tyotunnus'
					order by pvmalku";		
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			echo "<font class='error'>".t("VIRHE: P‰‰llekk‰isi‰ tapahtumia")."!</font><br><br>";
			$tee = "VARAA";
		}
		
		if ($tee == "LISAA" and $tyotunnus > 0) {
			$query = "	UPDATE kalenteri
						SET
						muuttaja	= '$kukarow[kuka]',
						muutospvm	= now(),
						tapa		= '$tyojono',
						kuka 		= '$asentaja',
						pvmalku 	= '$year-$month-$day $aika',
						pvmloppu 	= '$lyear-$lmonth-$lday $laika',
						liitostunnus= '$liitostunnus',
						tyyppi 		= 'asennuskalenteri'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi  = 'asennuskalenteri'
						and tunnus  = '$tyotunnus'";
			mysql_query($query) or pupe_error($query);
		
			$tee = "";
		}
		elseif ($tee == "LISAA") {
			$query = "	INSERT INTO kalenteri
						SET
						yhtio 		= '$kukarow[yhtio]',
						laatija		= '$kukarow[kuka]',
						luontiaika	= now(),
						tapa		= '$tyojono',
						kuka 		= '$asentaja',
						pvmalku 	= '$year-$month-$day $aika',
						pvmloppu 	= '$lyear-$lmonth-$lday $laika',
						liitostunnus= '$liitostunnus',
						tyyppi 		= 'asennuskalenteri'";
			mysql_query($query) or pupe_error($query);
		
			$tee = "";
		}
	}
	
	if ($tee == "MUOKKAA") {
		$query = "	SELECT * 
					FROM kalenteri
					WHERE 
					$konsernit
					and tunnus = '$tyotunnus'
					and tyyppi = 'asennuskalenteri'";		
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 1) {
			$kalerow = mysql_fetch_array($result);

			$year			= substr($kalerow["pvmalku"], 0, 4);
			$month			= substr($kalerow["pvmalku"], 5, 2);
			$day			= substr($kalerow["pvmalku"], 8, 2);
			$aika			= substr($kalerow["pvmalku"], 11, 5);
			$lyear			= substr($kalerow["pvmloppu"], 0, 4);
			$lmonth			= substr($kalerow["pvmloppu"], 5, 2);
			$lday			= substr($kalerow["pvmloppu"], 8, 2);
			$aikaloppu		= substr($kalerow["pvmloppu"], 11, 5);
			$liitostunnus	= $kalerow["liitostunnus"];
			$asentaja		= $kalerow["kuka"];
			$tyojono		= $kalerow["tapa"];
		
			$tee = "VARAA";
		}
		else {
			$tee = "";
		}	
	}
	
	if ($tee == "POISTA") {
		$query = "	DELETE 
					FROM kalenteri
					WHERE 
					$konsernit
					and tunnus = '$tyotunnus'
					and tyyppi = 'asennuskalenteri'";		
		$result = mysql_query($query) or pupe_error($query);
		
		echo "<font class='error'>".t("Kalenterimerkint‰ poistettu")."!</font><br><br>";
		$tee = "";
	}
	
	if ($tee == "VARAA") {
		echo "<table>";
		
		echo "<form method='POST' action='$PHP_SELF'>
				<input type='hidden' name='tee'  			value='LISAA'>
				<input type='hidden' name='year'  			value='$year'>
				<input type='hidden' name='month'  			value='$month'>
				<input type='hidden' name='day'  			value='$day'>
				<input type='hidden' name='liitostunnus'  	value='$liitostunnus'>
				<input type='hidden' name='asentaja'  		value='$asentaja'>
				<input type='hidden' name='tyojono'  		value='$tyojono'>
				<input type='hidden' name='aika'  			value='$aika'>";
		
		if ($tyotunnus > 0) {
			echo "<input type='hidden' name='tyotunnus' 	value='$tyotunnus'>";
		}
		
		echo "<tr><th>Asentaja:</th><td>$ASENTAJA_ARRAY[$asentaja]</td></tr>";
		echo "<tr><th>Tyˆjono:</th><td>$tyojono</td></tr>";
		
		if (!isset($lday)) $lday     = $day;
		if (!isset($lmonth)) $lmonth = $month;
		if (!isset($lyear)) $lyear   = $year;
		
		echo  "<tr>
			<th nowrap>".t("Kesto").":</th>
			<td>$aika - 
			<input type='text' size='3' name='lday' 	value='$lday'>
			<input type='text' size='3' name='lmonth'   value='$lmonth'>
			<input type='text' size='5' name='lyear'  	value='$lyear'>
			<select name='laika'>";

		
		$whileaika = $AIKA_ARRAY[0];
		if (!isset($aikaloppu)) $aikaloppu = date("H:i", mktime(substr($aika,0,2), substr($aika,3,2)+60, 0));
		
		while ($whileaika!='18:00'){
			
			$sel = '';
			if ($whileaika == $aikaloppu) {
				$sel = "SELECTED";
			}
			echo  "<option value='$whileaika' $sel>$whileaika</option>";
			
			$whileaika = date("H:i", mktime(substr($whileaika,0,2), substr($whileaika,3,2)+60, 0));
		}

		echo  "</select></td>";
		echo "</tr>";
		echo "</table><br>";
		
		if ($tyotunnus > 0) {
			echo "<input type='submit' value='".t("Muokkaa")."'>";
		}
		else {
			echo "<input type='submit' value='".t("Lis‰‰")."'>";
		}

		echo "</form>";
		
		if ($tyotunnus > 0) {
			echo "<br><br><br><form method='POST' action='$PHP_SELF'>
					<input type='hidden' name='tee'  			value='POISTA'>
					<input type='hidden' name='year'  			value='$year'>
					<input type='hidden' name='month'  			value='$month'>
					<input type='hidden' name='day'  			value='$day'>
					<input type='hidden' name='liitostunnus'  	value='$liitostunnus'>
					<input type='hidden' name='asentaja'  		value='$asentaja'>
					<input type='hidden' name='tyojono'  		value='$tyojono'>
					<input type='hidden' name='aika'  			value='$aika'>
					<input type='hidden' name='tyotunnus' 	value='$tyotunnus'>";
			echo "<input type='submit' value='".t("Poista")."'>";	
		}
	}

	if ($tee == "") {
		echo "<table>";
		echo "<th>".t("Kuukausi").":</th>";
		echo "<td><a class='menu' href='$PHP_SELF?day=1&month=$backmmonth&year=$backymonth'> << </a></td>";
		echo "<td>
				<form method='POST' action='$PHP_SELF'>
				<input type='hidden' name='liitostunnus'  value='$liitostunnus'>
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
	
		echo "</select></td>";
		echo "<td><a class='menu' href='$PHP_SELF?day=1&month=$nextmmonth&year=$nextymonth'> >> </a></td>";
		
		echo "<th>".t("Tyˆjono").":</th><td>";
				
		echo "<select name='tyojono' Onchange='submit();'>";
	
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TYOM_TYOJONO'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($tyojono == $vrow['selite']) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		echo "</select>";
		echo "</td>";
		echo "<td class='back'><input type='submit' value='".t("N‰yt‰")."'></form></td>";
		echo "</tr>";
		echo "</table><br>";
	}
	
	if ($tee == "" and $tyojono != "") {
		echo "<table width='100%'>";
		echo "<tr>";
	
		echo "<th>Aika</th>";
		
		for($r=0; $r < sizeof($DAY_ARRAY); $r++) {
			echo "	<th nowrap><b>$DAY_ARRAY[$r]</b>
					<br>
					<table width='100%'>
					<tr>";
				
			for($s=0; $s < sizeof($ASENTAJA_ARRAY); $s++) {
				echo "<td align='center' width='35' nowrap>$ASENTAJA_ARRAY[$s]</td>";
			}
	        echo "	</tr>
					</table>
					</th>";
		}
		
		echo "</tr>";
		echo "<tr>";
		
		echo "<td><br><table width='100%'>";
		
		for($a = 0; $a < count($AIKA_ARRAY); $a++) {
			echo "<tr><td>$AIKA_ARRAY[$a]</td></tr>";
		}
		echo "</table>";
		echo "</td>";
	
		// Kirjotetaan alkuun tyhji‰ soluja
		for ($i = 0; $i < weekday_number("1", $month, $year); $i++) {
			echo "<td>&nbsp;</td>";
		}
	
	    for($i = 1; $i <= days_in_month($month, $year); $i++) {
						
			if (date('N', mktime(0, 0, 0, $month, $i, $year))-1 < count($DAY_ARRAY)) {
				
				echo "<td class='day'><b>$i</b><br>";

				$query = "	SELECT kalenteri.kuka, kalenteri.liitostunnus, kalenteri.pvmalku, kalenteri.pvmloppu
							FROM kalenteri
							LEFT JOIN avainsana ON kalenteri.yhtio = avainsana.yhtio and avainsana.laji = 'KALETAPA' and avainsana.selitetark = kalenteri.tapa
							WHERE kalenteri.yhtio = '$kukarow[yhtio]'
							and kalenteri.tyyppi = 'asennuskalenteri'
							and kalenteri.tapa	= '$tyojono'
							and (	(pvmalku >= '$year-$month-$i 00:00:00' and pvmalku <= '$year-$month-$i 23:59:00') or 
									(pvmalku <  '$year-$month-$i 00:00:00' and pvmloppu > '$year-$month-$i 00:00:00') or
									(pvmloppu >='$year-$month-$i 00:00:00' and pvmloppu<= '$year-$month-$i 23:59:00'))
							order by pvmalku";
				$vres = mysql_query($query) or pupe_error($query);
	
				$varaukset 	= array();
		
				if (mysql_num_rows($vres) > 0) {
					while($vrow = mysql_fetch_array($vres)) {
						for($b = 0; $b < count($ASENTAJA_ARRAY); $b++) {
							for($a = 0; $a < count($AIKA_ARRAY); $a++) {
								$slot = str_replace(array(":","-"," "), "", $year."-".sprintf('%02d', $month)."-".sprintf('%02d', $i)." ".$AIKA_ARRAY[$a]);
								$alku = str_replace(array(":","-"," "), "", substr($vrow["pvmalku"],0,16));
								$lopp = str_replace(array(":","-"," "), "", substr($vrow["pvmloppu"],0,16));
							
								if ($alku <= $slot and $lopp > $slot and $vrow["kuka"] == $b) {
									$varaukset[$b][$a] = $ASENTAJA_ARRAY[$vrow["kuka"]]."|||".$vrow["liitostunnus"];
								}
							}
						}
					}
				}
		
				echo "<table width='100%'>";
		
				for($a = 0; $a < count($AIKA_ARRAY); $a++) {
					echo "<tr>";
					for($b = 0; $b < count($ASENTAJA_ARRAY); $b++) {
				
						if (isset($varaukset[$b][$a])) {
							list($varausid, $pinnamies) = explode("|||", $varaukset[$b][$a]);
					
							echo "<th align='center' style='width: 35px; height: 15px;'><a class='td' href='tyojono.php?myyntitilaus_haku=$pinnamies'>$pinnamies</a></th>";
						}
						elseif($liitostunnus > 0 and $tyojono != "") {
		                    echo "<th align='center' style='width: 35px; height: 15px;'><a class='td' href='$PHP_SELF?year=$year&month=$month&day=$i&liitostunnus=$liitostunnus&tyojono=$tyojono&asentaja=$b&aika=$AIKA_ARRAY[$a]&tee=VARAA'>&nbsp;</a></th>";			
		                }				
						else {
							echo "<th align='center' style='width: 35px; height: 15px;'>&nbsp;</th>";
						}
					}
					echo "</tr>";
				}
		
				echo "</table>";
				echo "</td>";
			}
		
			if (weekday_number($i, $month, $year) == 6) {
				// Rivinvaihto jos seuraava viikko on olemassa
				if (days_in_month($month, $year)!=$i) {
					echo "</tr><tr>";
				
					echo "<td><br><table width='100%'>";

					for($a = 0; $a < count($AIKA_ARRAY); $a++) {
						echo "<tr><td>$AIKA_ARRAY[$a]</td></tr>";
					}
					echo "</table>";
					echo "</td>";
				}	
			}
		}
	
		// Kirjotetaan loppuun tyhji‰ soluja
		if (weekday_number($i, $month, $year) < count($DAY_ARRAY) and weekday_number($i, $month, $year) > 0) {
			for ($a = weekday_number($i, $month, $year); $a <= count($DAY_ARRAY)-1; $a++) {
				echo "<td>&nbsp;</td>";
			}
		}
		echo "</tr>";
		echo "</table>";		
	}
	
	require("../inc/footer.inc");
?>